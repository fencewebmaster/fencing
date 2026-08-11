<?php
/**
 * FC — application bootstrap, shared by every entry point.
 *
 * Defines the path constants and registers the autoloader. Nothing area-specific happens
 * here: the admin session/auth/presence boot lives in Core\AdminBootstrap (run by
 * Core\Application), and the public side starts its own session per controller, because
 * the lookup route must not set a session cookie at all.
 *
 * Required by index.php (frontend), public/index.php and public/api.php (admin).
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
