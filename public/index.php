<?php
require __DIR__ . '/../app/bootstrap.php';

use Fc\Admin\Core\Application;
use Fc\Admin\Services\ConsoleSettings;

// Fatal errors/uncaught exceptions are controlled by display_errors, not error_reporting() —
// without this, an unhandled error in the admin panel leaks a full stack trace (absolute
// server paths, internal class layout) to the browser whenever the server's own php.ini
// default happens to have display_errors on (common on dev-oriented stacks).
if (!ConsoleSettings::debugMode()) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

$fcAdminContext = Application::handleWebRequest();
extract($fcAdminContext->toLayoutVars(), EXTR_SKIP);

$fcAdminFillLayout = !empty($fcAdminIsEntries)
    || !empty($fcAdminIsUsers)
    || !empty($fcAdminIsGroupPermissions)
    || !empty($fcAdminIsSettings)
    || !empty($fcAdminIsGallery)
    || !empty($fcAdminIsProductsPage);

$fcCan = static function (string $key): bool {
    return \Fc\Admin\Services\PermissionService::can($key);
};

if (!empty($fcAdminIsLogin) && is_array($fcLoginPage ?? null)) {
    include __DIR__ . '/../app/views/admin/login.php';
    return;
}

include __DIR__ . '/../app/views/admin/layouts/main.php';
