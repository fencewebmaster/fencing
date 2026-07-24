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

/**
 * API module registry.
 *
 * @return array<string, callable>
 */
return [
    'auth'                  => static function (): void { AuthController::dispatch(); },
    'cache'                 => static function (): void { CacheController::dispatch(); },
    'cacheController'       => static function (): void { CacheController::dispatch(); },
    'dashboard'             => static function (): void { DashboardApiController::dispatch(); },
    'dashboardController'   => static function (): void { DashboardApiController::dispatch(); },
    'entries'               => static function (): void { EntriesApiController::dispatch(); },
    'entriesController'     => static function (): void { EntriesApiController::dispatch(); },
    'products'              => static function (): void { ProductsController::dispatch(); },
    'productsController'    => static function (): void { ProductsController::dispatch(); },
    'gallery'               => static function (): void { GalleryController::dispatch(); },
    'galleryController'     => static function (): void { GalleryController::dispatch(); },
    'settings'              => static function (): void { SettingsController::dispatch(); },
    'settingsController'    => static function (): void { SettingsController::dispatch(); },
    'fenceStyles'           => static function (): void { FenceStylesController::dispatch(); },
    'fenceStylesController' => static function (): void { FenceStylesController::dispatch(); },
    'groupPermissions'      => static function (): void { GroupPermissionsController::dispatch(); },
    'groupPermissionsController' => static function (): void { GroupPermissionsController::dispatch(); },
];
