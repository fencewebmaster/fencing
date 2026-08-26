<?php

declare(strict_types=1);

use Fc\Admin\Controllers\Api\AuthController;
use Fc\Admin\Controllers\Api\CacheController;
use Fc\Admin\Controllers\Api\DashboardApiController;
use Fc\Admin\Controllers\Api\EntriesApiController;
use Fc\Admin\Controllers\Api\FenceStylesController;
use Fc\Admin\Controllers\Api\GalleryController;
use Fc\Admin\Controllers\Api\GroupPermissionsController;
use Fc\Admin\Controllers\Api\ProductsController;
use Fc\Admin\Controllers\Api\SettingsController;
use Fc\Admin\Controllers\Api\UsersApiController;

/**
 * API module registry for the unified admin JSON API
 * (public/api.php?module={module}&action={action}, dispatched by Core\Application).
 *
 * Modules map to callables rather than instances so a request only ever loads the
 * one controller it needs.
 *
 * @return array<string, callable>
 */
$fcApiModules = [
    'auth'             => static function (): void { AuthController::dispatch(); },
    'cache'            => static function (): void { CacheController::dispatch(); },
    'dashboard'        => static function (): void { DashboardApiController::dispatch(); },
    'entries'          => static function (): void { EntriesApiController::dispatch(); },
    'fenceStyles'      => static function (): void { FenceStylesController::dispatch(); },
    'gallery'          => static function (): void { GalleryController::dispatch(); },
    'groupPermissions' => static function (): void { GroupPermissionsController::dispatch(); },
    'products'         => static function (): void { ProductsController::dispatch(); },
    'settings'         => static function (): void { SettingsController::dispatch(); },
    'users'            => static function (): void { UsersApiController::dispatch(); },
];

/*
 * Legacy '{module}Controller' aliases — required, not redundant. public/.htaccess
 * still rewrites the old {module}Controller.php URLs onto api.php, and
 * Core\Application::detectApiModule() derives the module from the executing script's
 * basename when no ?module= is given. GroupPermissionsPresenter::keysForApi() gates
 * both spellings identically.
 *
 * 'auth' deliberately has no alias: no authController.php ever shipped, and the auth
 * module skips AuthFilter — an alias would only widen the unauthenticated surface.
 */
foreach ([
    'cache',
    'dashboard',
    'entries',
    'fenceStyles',
    'gallery',
    'groupPermissions',
    'products',
    'settings',
    'users',
] as $fcApiModule) {
    $fcApiModules[$fcApiModule . 'Controller'] = $fcApiModules[$fcApiModule];
}

unset($fcApiModule);

return $fcApiModules;
