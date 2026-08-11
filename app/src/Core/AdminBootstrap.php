<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

use Fc\Admin\Services\AdminSiteRegistry;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\DatabaseConfigService;
use Fc\Admin\Services\PresenceService;

/**
 * Admin-area boot: session, auth restore, DB/site context and the presence heartbeat.
 *
 * This used to sit at the top of app/app_bootstrap.php. It runs from Core\Application's
 * entry points instead, so app/bootstrap.php can be shared with the public side, which
 * must not start an admin session.
 *
 * Must still run before anything is echoed — AuthService::boot() sets the fc_admin_sess
 * cookie and silently degrades to an ini_set() once headers are sent.
 */
final class AdminBootstrap
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        AuthService::boot();

        // Ensure logged-in admins always have a site + auth DB context.
        if (!AuthService::isLoggedIn()) {
            return;
        }

        $hostKey = DatabaseConfigService::hostMysqlKey();
        if (AdminSiteRegistry::authDbKey() === '') {
            AdminSiteRegistry::setAuthDbKey($hostKey);
        }
        if (AdminSiteRegistry::siteKey() === '') {
            AdminSiteRegistry::setSiteKey($hostKey);
        }

        // Heartbeat for near-realtime online presence.
        $presenceUser = AuthService::user();
        if (is_array($presenceUser)) {
            if (isset($_SESSION['fc_admin_user']['logged_in_at'])) {
                $presenceUser['logged_in_at'] = (int) $_SESSION['fc_admin_user']['logged_in_at'];
            }
            PresenceService::touch($presenceUser);
        }
    }
}
