<?php
require __DIR__ . '/../app/bootstrap.php';

use Fc\Admin\Core\Application;

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
