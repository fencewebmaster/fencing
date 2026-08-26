<?php

declare(strict_types=1);

namespace Fc\Admin\Filters;

use Fc\Admin\Core\Request;
use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\ImpersonationService;
use Fc\Admin\Services\PermissionService;

/**
 * Route-level "must have permission" gate (renamed+converted from Middleware\PermissionMiddleware).
 */
final class PermissionFilter implements FilterInterface
{
    /**
     * Abort with 403 when the current user lacks permission.
     *
     * @param list<string>|string|null $arguments Permission key(s) required for this route.
     */
    public function before(Request $request, array|string|null $arguments = null): void
    {
        $list = is_array($arguments) ? $arguments : ($arguments === null ? [] : [$arguments]);
        if ($list === [] || PermissionService::canAny($list)) {
            return;
        }

        $this->abort403('You do not have permission to access this resource.');
    }

    public function after(Request $request, array|string|null $arguments = null): void
    {
        // No-op: unreachable on the API dispatch path anyway (JsonResponse::send() exits
        // before control returns to Application::dispatchApi()); kept as a stub for the contract.
    }

    /**
     * Render a 403 response (HTML or JSON) and exit.
     */
    public function abort403(string $message = 'Forbidden.'): void
    {
        $wantsJson = RequestHelper::wantsJson();

        if ($wantsJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
            }
            echo json_encode([
                'ok' => false,
                'error' => $message,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!headers_sent()) {
            http_response_code(403);
        }

        // Full view-model for errors/403.php, assembled here — this filter is the
        // page's only producer, so the template stays read-only. The is_readable
        // check keeps the plain-HTML fallback below working when the view file is
        // missing, where view() would throw instead of degrading.
        $view = dirname(__DIR__, 2) . '/views/errors/403.php';
        if (is_readable($view)) {
            $fcAdminBase = AuthService::adminBase();
            $base        = rtrim($fcAdminBase, '/');
            $isSwitched  = ImpersonationService::isSwitched();
            $switchFrom  = $isSwitched ? ImpersonationService::switchFrom() : null;
            $currentUser = AuthService::user();

            $switchBackUrl = '';
            if ($isSwitched && $fcAdminBase !== '') {
                $token = AuthService::mintOneTimeToken('switch-back');
                $switchBackUrl = $base . '/users/switch-back?_token=' . rawurlencode($token);
            }

            $asName = '';
            if (is_array($currentUser)) {
                $asName = (string) (($currentUser['display_name'] ?? '') !== ''
                    ? $currentUser['display_name']
                    : ($currentUser['login'] ?? ''));
            }

            $fromName = '';
            if (is_array($switchFrom)) {
                $fromName = (string) (($switchFrom['display_name'] ?? '') !== ''
                    ? $switchFrom['display_name']
                    : ($switchFrom['login'] ?? 'admin'));
            }

            view('errors.403', [
                'message'       => $message,
                'home'          => $base !== '' ? $base . '/dashboard' : '/',
                'isSwitched'    => $isSwitched,
                'switchBackUrl' => $switchBackUrl,
                'asName'        => $asName,
                'fromName'      => $fromName,
            ]);
            exit;
        }

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head><body>';
        echo '<h1>403 Forbidden</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</body></html>';
        exit;
    }
}
