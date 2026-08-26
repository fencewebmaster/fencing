<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

use Fc\Admin\Filters\AuthFilter;
use Fc\Admin\Filters\PermissionFilter;
use Fc\Admin\Presenters\GroupPermissionsPresenter;
use Fc\Admin\Services\AdminContext;
use Fc\Admin\Services\AdminSiteRegistry;
use Fc\Admin\Services\ImpersonationService;

final class Application
{
    private Router $webRouter;

    /** @var array<string, callable> */
    private array $apiRoutes;

    public function __construct()
    {
        $this->webRouter = new Router();
        $this->apiRoutes = require FC_ADMIN_ROOT . '/routes/api.php';

        RouteLoader::apply('admin', $this->webRouter);
    }

    public static function handleWebRequest(): AdminContext
    {
        // Before constructing anything: AdminContext reads the admin session, and
        // AuthService can only set its cookie while no output has been sent.
        AdminBootstrap::boot();

        return (new self())->dispatchWeb();
    }

    public static function handleApiRequest(?string $module = null): void
    {
        AdminBootstrap::boot();

        (new self())->dispatchApi($module);
    }

    public function dispatchWeb(): AdminContext
    {
        $request = new Request();
        $context = new AdminContext();
        $tail    = $this->resolveRouteTail($context->adminBase);

        if ($this->handleLegacyRedirects($context, $tail)) {
            exit;
        }

        $publicRoutes = ['login', 'logout'];
        if (!in_array($tail, $publicRoutes, true)) {
            (new AuthFilter())->before($request);
            $this->enforceSiteSwitchScope($context, $tail);
            $this->enforceRoutePermission($request, $tail);
        }

        $match = $this->webRouter->match('GET', $tail);
        if ($match !== null) {
            $handler = $match['handler'];
            $params  = $match['params'];

            if (isset($params['id']) || isset($params['slug'])) {
                $handler($context, $params);
            } else {
                $handler($context);
            }

            (new AuthFilter())->after($request);

            return $context;
        }

        // Unknown admin route.
        NotFoundHandler::abort('admin');
    }

    public function dispatchApi(?string $module = null): void
    {
        $request = new Request();
        $module  = $module ?? $this->detectApiModule();

        if ($module === '' || !isset($this->apiRoutes[$module])) {
            JsonResponse::error('Unknown API module.', 404);
        }

        if ($module !== 'auth') {
            (new AuthFilter())->before($request);
            if (AdminSiteRegistry::isSiteSwitched() && !AdminSiteRegistry::siteSwitchedApiAllowed($module)) {
                JsonResponse::error('This module is not available while viewing another site.', 403);
            }
            $action = (string) $request->query('action', '');
            $keys = GroupPermissionsPresenter::keysForApi($module, $action);
            if ($keys !== []) {
                (new PermissionFilter())->before($request, $keys);
            }
        }

        // Note: PermissionFilter/AuthFilter::after() is not called here — every module
        // callable ends in JsonResponse::send()/ok()/error(), which echoes and exit()s
        // immediately, so control never returns here on the success path.
        ($this->apiRoutes[$module])();
    }

    private function enforceSiteSwitchScope(AdminContext $context, string $tail): void
    {
        if (!AdminSiteRegistry::isSiteSwitched()) {
            return;
        }
        if (AdminSiteRegistry::siteSwitchedRouteAllowed($tail)) {
            return;
        }

        Response::redirect(rtrim($context->adminBase, '/') . '/dashboard');
    }

    private function enforceRoutePermission(Request $request, string $tail): void
    {
        // Always allow switch-back while impersonating.
        if ($tail === 'users/switch-back' && ImpersonationService::isSwitched()) {
            return;
        }

        // Site switcher: any logged-in admin may switch DB context.
        if ($tail === 'users/switch-site') {
            return;
        }

        // Switched-site mode: dashboard + planner entries only (permissions already checked at login).
        if (AdminSiteRegistry::isSiteSwitched() && AdminSiteRegistry::siteSwitchedRouteAllowed($tail)) {
            return;
        }

        $keys = GroupPermissionsPresenter::keysForRoute($tail);
        if ($keys === []) {
            return;
        }

        (new PermissionFilter())->before($request, $keys);
    }

    private function detectApiModule(): string
    {
        $script = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));

        return str_replace('.php', '', $script);
    }

    private function resolveRouteTail(string $adminBase): string
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '/');
        $path = rtrim($path, '/');

        if ($adminBase !== '' && strncmp($path, $adminBase, strlen($adminBase)) === 0) {
            return ltrim(substr($path, strlen($adminBase)), '/');
        }

        return ltrim($path, '/');
    }

    private function handleLegacyRedirects(AdminContext $context, string $tail): bool
    {
        if (preg_match('#^entries(?:/(\d+))?$#', $tail, $legacyMatch)) {
            $destination = $context->adminBase . '/' . $context->plannerEntriesRoute;
            if (!empty($legacyMatch[1])) {
                $destination .= '/' . $legacyMatch[1];
            }
            $query = $_SERVER['QUERY_STRING'] ?? '';
            if ($query !== '') {
                $destination .= '?' . $query;
            }
            Response::redirect($destination, 301);
        }

        return false;
    }
}
