<?php

declare(strict_types=1);

use Fc\Admin\Controllers\DashboardController;
use Fc\Admin\Controllers\EntriesPageController;
use Fc\Admin\Controllers\GalleryPageController;
use Fc\Admin\Controllers\LoginPageController;
use Fc\Admin\Controllers\LogoutController;
use Fc\Admin\Controllers\ProductsPageController;
use Fc\Admin\Controllers\SettingsPageController;
use Fc\Admin\Controllers\UsersPageController;
use Fc\Admin\Controllers\GroupPermissionsPageController;
use Fc\Admin\Core\Router;
use Fc\Admin\Services\AdminContext;

/**
 * @param Router $router
 */
return static function (Router $router): void {
    $router->get('login', static function (AdminContext $context): void {
        (new LoginPageController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('logout', static function (AdminContext $context): void {
        (new LogoutController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('', static function (AdminContext $context): void {
        (new DashboardController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('dashboard', static function (AdminContext $context): void {
        (new DashboardController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('planner-entries', static function (AdminContext $context): void {
        (new EntriesPageController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('planner-entries/{id}', static function (AdminContext $context, array $params): void {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        (new EntriesPageController(new \Fc\Admin\Core\Request()))->show($context, $id);
    });

    $router->get('users', static function (AdminContext $context): void {
        (new UsersPageController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('users/group-permissions', static function (AdminContext $context): void {
        (new GroupPermissionsPageController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('users/login-as/{id}', static function (AdminContext $context, array $params): void {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        (new UsersPageController(new \Fc\Admin\Core\Request()))->loginAs($context, $id);
    });

    $router->get('users/switch-back', static function (AdminContext $context): void {
        (new UsersPageController(new \Fc\Admin\Core\Request()))->switchBack($context);
    });

    $router->get('users/switch-site', static function (AdminContext $context): void {
        (new UsersPageController(new \Fc\Admin\Core\Request()))->switchSite($context);
    });

    $router->get('settings', static function (AdminContext $context): void {
        (new SettingsPageController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('gallery', static function (AdminContext $context): void {
        (new GalleryPageController(new \Fc\Admin\Core\Request()))->index($context);
    });

    $router->get('products/fence-styles', static function (AdminContext $context): void {
        (new ProductsPageController(new \Fc\Admin\Core\Request()))->fenceStyles($context);
    });

    $router->get('products/fence-styles/edit/{slug}', static function (AdminContext $context, array $params): void {
        $slug = isset($params['slug']) ? (string) $params['slug'] : '';
        (new ProductsPageController(new \Fc\Admin\Core\Request()))->fenceStyleEdit($context, $slug);
    });

    $router->get('products/store-products', static function (AdminContext $context): void {
        (new ProductsPageController(new \Fc\Admin\Core\Request()))->storeProducts($context);
    });

    $router->get('products/system-products', static function (AdminContext $context): void {
        (new ProductsPageController(new \Fc\Admin\Core\Request()))->systemProducts($context);
    });
};
