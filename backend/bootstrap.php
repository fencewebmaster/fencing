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

require_once FC_ROOT . '/config/helpers.php';
require_once FC_ROOT . '/config/theme.php';
require_once FC_ROOT . '/config/branding.php';
require_once FC_ROOT . '/config/system.php';
require_once FC_ROOT . '/config/permissions.php';
require_once FC_ROOT . '/config/auth.php';
require_once FC_ROOT . '/config/admin_sites.php';
require_once FC_ADMIN_ROOT . '/src/Core/Autoloader.php';

Fc\Admin\Core\Autoloader::register();
fc_auth_boot();

// Ensure logged-in admins always have a site + auth DB context.
if (function_exists('fc_auth_is_logged_in') && fc_auth_is_logged_in()) {
    $hostKey = function_exists('fc_db_host_mysql_key') ? fc_db_host_mysql_key() : 'localhost';
    if (function_exists('fc_admin_auth_db_key') && fc_admin_auth_db_key() === '' && function_exists('fc_admin_set_auth_db_key')) {
        fc_admin_set_auth_db_key($hostKey);
    }
    if (function_exists('fc_admin_site_key') && fc_admin_site_key() === '' && function_exists('fc_admin_set_site_key')) {
        fc_admin_set_site_key($hostKey);
    }
}
