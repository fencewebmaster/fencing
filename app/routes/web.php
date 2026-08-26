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
use Fc\Admin\Controllers\GroupPermissionsPageController;
use Fc\Admin\Controllers\LoginPageController;
use Fc\Admin\Controllers\LogoutController;
use Fc\Admin\Controllers\ProductsPageController;
use Fc\Admin\Controllers\SettingsPageController;
use Fc\Admin\Controllers\UsersPageController;
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
 * Core\Request built by Core\FrontendApplication. Core\Application only forwards route
 * params to a handler when the placeholder is named {id} or {slug} — keep those names
 * for any new admin route parameter.
 *
 * Router::group() applies a URL prefix only. Auth and permissions are enforced by the
 * dispatchers (AuthFilter, GroupPermissionsPresenter::keysForRoute()), never here.
 * Route names are deliberately absent: nothing in this app resolves a URL from a route
 * name, so a name registry would be dead code.
 *
 * @return array<string, callable(Router):void>
 */
return [

    /*
     * Admin — GET only; every write goes through routes/api.php instead.
     */
    'admin' => static function (Router $router): void {

        // Authentication — the only routes reachable while logged out (see the
        // $publicRoutes list in Core\Application::dispatchWeb()).
        $router->get('login', static function (AdminContext $context): void {
            (new LoginPageController(new Request()))->index($context);
        });

        $router->get('logout', static function (AdminContext $context): void {
            (new LogoutController(new Request()))->index($context);
        });

        // Dashboard — '' is the bare /backend root; both patterns render the dashboard.
        $router->get('', static function (AdminContext $context): void {
            (new DashboardController(new Request()))->index($context);
        });

        $router->get('dashboard', static function (AdminContext $context): void {
            (new DashboardController(new Request()))->index($context);
        });

        // Planner entries.
        $router->group('planner-entries', static function (Router $router): void {
            $router->get('', static function (AdminContext $context): void {
                (new EntriesPageController(new Request()))->index($context);
            });

            $router->get('{id}', static function (AdminContext $context, array $params): void {
                $id = isset($params['id']) ? (int) $params['id'] : 0;
                (new EntriesPageController(new Request()))->show($context, $id);
            });
        });

        // Users.
        $router->group('users', static function (Router $router): void {
            $router->get('', static function (AdminContext $context): void {
                (new UsersPageController(new Request()))->index($context);
            });

            $router->get('group-permissions', static function (AdminContext $context): void {
                (new GroupPermissionsPageController(new Request()))->index($context);
            });

            $router->get('login-as/{id}', static function (AdminContext $context, array $params): void {
                $id = isset($params['id']) ? (int) $params['id'] : 0;
                (new UsersPageController(new Request()))->loginAs($context, $id);
            });

            $router->get('switch-back', static function (AdminContext $context): void {
                (new UsersPageController(new Request()))->switchBack($context);
            });

            $router->get('switch-site', static function (AdminContext $context): void {
                (new UsersPageController(new Request()))->switchSite($context);
            });
        });

        // Settings.
        $router->get('settings', static function (AdminContext $context): void {
            (new SettingsPageController(new Request()))->index($context);
        });

        // Media gallery.
        $router->get('gallery', static function (AdminContext $context): void {
            (new GalleryPageController(new Request()))->index($context);
        });

        // Products. The route paths here tell the truth; several of the class/view/JS
        // names they resolve to are deliberately cross-wired — see ProductsPageController
        // before "fixing" any single layer.
        $router->group('products', static function (Router $router): void {
            $router->get('fence-styles', static function (AdminContext $context): void {
                (new ProductsPageController(new Request()))->fenceStyles($context);
            });

            $router->get('fence-styles/edit/{slug}', static function (AdminContext $context, array $params): void {
                $slug = isset($params['slug']) ? (string) $params['slug'] : '';
                (new ProductsPageController(new Request()))->fenceStyleEdit($context, $slug);
            });

            $router->get('store-products', static function (AdminContext $context): void {
                (new ProductsPageController(new Request()))->storeProducts($context);
            });

            $router->get('system-products', static function (AdminContext $context): void {
                (new ProductsPageController(new Request()))->systemProducts($context);
            });
        });
    },

    /*
     * Frontend — real URL paths off the app root (/planner, /checkout, /lookup).
     * Registered with any() because the planner POSTs to /project-plan and the AJAX
     * endpoints are POST-only.
     */
    'frontend' => static function (Router $router): void {

        // Pages.
        $router->any('', static function (Request $request): void {
            (new HomeController($request))->index();
        });

        $router->any('planner', static function (Request $request): void {
            (new PlannerController($request))->index();
        });

        $router->any('project-plan', static function (Request $request): void {
            (new ProjectPlanController($request))->index();
        });

        // AJAX endpoints — no page view of their own.
        $router->any('checkout', static function (Request $request): void {
            (new CheckoutController($request))->index();
        });

        $router->any('submit', static function (Request $request): void {
            (new SubmitController($request))->index();
        });

        $router->any('ajax', static function (Request $request): void {
            (new AjaxController($request))->index();
        });

        // Product lookup.
        $router->group('lookup', static function (Router $router): void {
            $router->any('', static function (Request $request): void {
                (new LookupController($request))->index();
            });

            // Quick view pretty path — was an .htaccess rewrite to lookup.php?view=$1.
            $router->any('view/{slug}', static function (Request $request, array $params): void {
                $request->setQuery('view', rawurldecode((string) ($params['slug'] ?? '')));
                (new LookupController($request))->index();
            });
        });

        // Errors.
        $router->any('404', static function (Request $request): void {
            (new NotFoundController($request))->index();
        });
    },
];
