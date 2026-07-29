<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

use Fc\Admin\Services\AdminContext;

final class Application
{
    private Router $webRouter;

    /** @var array<string, callable> */
    private array $apiRoutes;

    public function __construct()
    {
        $this->webRouter = new Router();
        $this->apiRoutes = require FC_ADMIN_ROOT . '/routes/api.php';

        $register = require FC_ADMIN_ROOT . '/routes/web.php';
        if (is_callable($register)) {
            $register($this->webRouter);
        }
    }

    public static function handleWebRequest(): AdminContext
    {
        return (new self())->dispatchWeb();
    }

    public static function handleApiRequest(?string $module = null): void
    {
        (new self())->dispatchApi($module);
    }

    public function dispatchWeb(): AdminContext
    {
        $context = new AdminContext();
        $tail    = $this->resolveRouteTail($context->adminBase);

        if ($this->handleLegacyRedirects($context, $tail)) {
            exit;
        }

        $publicRoutes = ['login', 'logout'];
        if (!in_array($tail, $publicRoutes, true)) {
            fc_auth_require_login();
            $this->enforceSiteSwitchScope($context, $tail);
            $this->enforceRoutePermission($tail);
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

            return $context;
        }

        // Unknown admin route.
        if (function_exists('fc_abort_404')) {
            fc_abort_404('admin');
        }

        http_response_code(404);
        echo '404 Not Found';
        exit;
    }

    public function dispatchApi(?string $module = null): void
    {
        $module = $module ?? $this->detectApiModule();

        if ($module === '' || !isset($this->apiRoutes[$module])) {
            JsonResponse::error('Unknown API module.', 404);
        }

        if ($module !== 'auth') {
            fc_auth_require_login();
            if (function_exists('fc_admin_is_site_switched') && fc_admin_is_site_switched()) {
                if (!function_exists('fc_admin_site_switched_api_allowed') || !fc_admin_site_switched_api_allowed($module)) {
                    JsonResponse::error('This module is not available while viewing another site.', 403);
                }
            }
            $action = isset($_GET['action']) ? (string) $_GET['action'] : '';
            if (function_exists('fc_permissions_keys_for_api')) {
                $keys = fc_permissions_keys_for_api($module, $action);
                if ($keys !== []) {
                    fc_auth_require_permission($keys);
                }
            }
        }

        ($this->apiRoutes[$module])();
    }

    private function enforceSiteSwitchScope(AdminContext $context, string $tail): void
    {
        if (!function_exists('fc_admin_is_site_switched') || !fc_admin_is_site_switched()) {
            return;
        }
        if (function_exists('fc_admin_site_switched_route_allowed') && fc_admin_site_switched_route_allowed($tail)) {
            return;
        }

        Response::redirect(rtrim($context->adminBase, '/') . '/dashboard');
    }

    private function enforceRoutePermission(string $tail): void
    {
        // Always allow switch-back while impersonating.
        if ($tail === 'users/switch-back' && function_exists('fc_auth_is_switched') && fc_auth_is_switched()) {
            return;
        }

        // Site switcher: any logged-in admin may switch DB context.
        if ($tail === 'users/switch-site') {
            return;
        }

        // Switched-site mode: dashboard + planner entries only (permissions already checked at login).
        if (function_exists('fc_admin_is_site_switched') && fc_admin_is_site_switched()
            && function_exists('fc_admin_site_switched_route_allowed')
            && fc_admin_site_switched_route_allowed($tail)) {
            return;
        }

        if (!function_exists('fc_permissions_keys_for_route')) {
            return;
        }

        $keys = fc_permissions_keys_for_route($tail);
        if ($keys === []) {
            return;
        }

        fc_auth_require_permission($keys);
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
