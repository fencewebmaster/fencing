<?php
/**
 * FC Admin — application bootstrap.
 */

declare(strict_types=1);

if (!defined('FC_ADMIN_ROOT')) {
    define('FC_ADMIN_ROOT', __DIR__);
}

if (!defined('FC_ROOT')) {
    define('FC_ROOT', dirname(__DIR__));
}

require_once FC_ADMIN_ROOT . '/src/Core/Autoloader.php';
Fc\Admin\Core\Autoloader::register();

\Fc\Admin\Services\AuthService::boot();

// Ensure logged-in admins always have a site + auth DB context.
if (\Fc\Admin\Services\AuthService::isLoggedIn()) {
    $hostKey = \Fc\Admin\Services\DatabaseConfigService::hostMysqlKey();
    if (\Fc\Admin\Services\AdminSiteRegistry::authDbKey() === '') {
        \Fc\Admin\Services\AdminSiteRegistry::setAuthDbKey($hostKey);
    }
    if (\Fc\Admin\Services\AdminSiteRegistry::siteKey() === '') {
        \Fc\Admin\Services\AdminSiteRegistry::setSiteKey($hostKey);
    }

    // Heartbeat for near-realtime online presence.
    $presenceUser = \Fc\Admin\Services\AuthService::user();
    if (is_array($presenceUser)) {
        if (isset($_SESSION['fc_admin_user']['logged_in_at'])) {
            $presenceUser['logged_in_at'] = (int) $_SESSION['fc_admin_user']['logged_in_at'];
        }
        \Fc\Admin\Services\PresenceService::touch($presenceUser);
    }
}
