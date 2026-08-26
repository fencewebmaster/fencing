<?php
require __DIR__ . '/../app/bootstrap.php';

use Fc\Admin\Core\Application;
use Fc\Admin\Settings\ConsoleSettings;

// Fatal errors/uncaught exceptions are controlled by display_errors, not error_reporting() —
// without this, an unhandled error in the admin panel leaks a full stack trace (absolute
// server paths, internal class layout) to the browser whenever the server's own php.ini
// default happens to have display_errors on (common on dev-oriented stacks).
if (!ConsoleSettings::debugMode()) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

$fcAdminContext = Application::handleWebRequest();

// The layout's variable set: everything AdminContext exposes, plus the two values
// this front controller adds. Views receive it via view()'s extract() — the same
// names the old top-level extract() + include put in scope.
$fcAdminVars = $fcAdminContext->toLayoutVars();

$fcAdminVars['fcAdminFillLayout'] = !empty($fcAdminVars['fcAdminIsEntries'])
    || !empty($fcAdminVars['fcAdminIsUsers'])
    || !empty($fcAdminVars['fcAdminIsGroupPermissions'])
    || !empty($fcAdminVars['fcAdminIsSettings'])
    || !empty($fcAdminVars['fcAdminIsGallery'])
    || !empty($fcAdminVars['fcAdminIsProductsPage']);

$fcAdminVars['fcCan'] = static function (string $key): bool {
    return \Fc\Admin\Services\PermissionService::can($key);
};

if (!empty($fcAdminVars['fcAdminIsLogin']) && is_array($fcAdminVars['fcLoginPage'] ?? null)) {
    view('admin.login', $fcAdminVars);
    return;
}

view('admin.layouts.main', $fcAdminVars);
