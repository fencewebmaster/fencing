<?php

declare(strict_types=1);

use Fc\Admin\Controllers\DashboardController;
use Fc\Admin\Controllers\EntriesPageController;
use Fc\Admin\Controllers\Frontend\AjaxController;
use Fc\Admin\Controllers\Frontend\CheckoutController;
use Fc\Admin\Controllers\Frontend\HomeController;
use Fc\Admin\Controllers\Frontend\LookupController;
use Fc\Admin\Controllers\Frontend\NotFoundController;
use Fc\Admin\Controllers\Frontend\PlannerController;
use Fc\Admin\Controllers\Frontend\ProjectPlanController;
use Fc\Admin\Controllers\Frontend\SubmitController;
use Fc\Admin\Controllers\GalleryPageController;
use Fc\Admin\Controllers\LoginPageController;
use Fc\Admin\Controllers\LogoutController;
use Fc\Admin\Controllers\ProductsPageController;
use Fc\Admin\Controllers\SettingsPageController;
use Fc\Admin\Controllers\UsersPageController;
use Fc\Admin\Controllers\GroupPermissionsPageController;
use Fc\Admin\Core\Request;
use Fc\Admin\Core\Router;
use Fc\Admin\Services\AdminContext;

/**
 * Application routes, grouped by the front controller that serves them.
 *
 *   'admin'    public/index.php  -> Core\Application           (URLs under /backend)
 *   'frontend' index.php         -> Core\FrontendApplication   (public URLs off the app root)
 *
 * Each group is registered into its own Router by Core\RouteLoader. They must stay
 * separate rather than becoming one flat table: both define a route for '' — the admin
 * dashboard and the planner redirect — so a merged table would resolve one of them to
 * the wrong controller, and every frontend path would additionally be reachable under
 * /backend.
 *
 * Handler signatures differ per group because their dispatchers differ: admin handlers
 * receive the AdminContext built by Core\Application, frontend handlers receive the
 * Core\Request built by Core\FrontendApplication.
 *
 * @return array<string, callable(Router):void>
 */
return [

    /*
     * Admin — GET only; every write goes through routes/api.php instead.
     */
    'admin' => static function (Router $router): void {
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
    },

    /*
     * Frontend — real URL paths off the app root (/planner, /checkout, /lookup).
     * Registered with any() because the planner POSTs to /project-plan and the AJAX
     * endpoints are POST-only.
     */
    'frontend' => static function (Router $router): void {
        $router->any('', static function (Request $request): void {
            (new HomeController($request))->index();
        });

        $router->any('planner', static function (Request $request): void {
            (new PlannerController($request))->index();
        });

        $router->any('project-plan', static function (Request $request): void {
            (new ProjectPlanController($request))->index();
        });

        $router->any('checkout', static function (Request $request): void {
            (new CheckoutController($request))->index();
        });

        $router->any('submit', static function (Request $request): void {
            (new SubmitController($request))->index();
        });

        $router->any('ajax', static function (Request $request): void {
            (new AjaxController($request))->index();
        });

        $router->any('lookup', static function (Request $request): void {
            (new LookupController($request))->index();
        });

        // Quick view pretty path — was an .htaccess rewrite to lookup.php?view=$1.
        $router->any('lookup/view/{slug}', static function (Request $request, array $params): void {
            $_GET['view'] = rawurldecode((string) ($params['slug'] ?? ''));
            (new LookupController($request))->index();
        });

        $router->any('404', static function (Request $request): void {
            (new NotFoundController($request))->index();
        });
    },
];
