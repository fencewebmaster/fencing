<?php

declare(strict_types=1);

namespace Fc\Admin\Filters;

use Fc\Admin\Core\Request;
use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\PermissionService;

/**
 * Route-level "must be logged in" gate.
 */
final class AuthFilter implements FilterInterface
{
    public function before(Request $request, array|string|null $arguments = null): void
    {
        if (PermissionService::canAccessAdmin()) {
            return;
        }

        // Logged in but no admin access (e.g. stale non-admin session).
        if (AuthService::isLoggedIn()) {
            AuthService::logout();
        }

        $wantsJson = RequestHelper::wantsJson();

        if ($wantsJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
            }
            echo json_encode([
                'ok'       => false,
                'error'    => 'Unauthorized. Please sign in.',
                'redirect' => AuthService::loginUrl(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $returnTo = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $login = AuthService::loginUrl();
        if ($returnTo !== '' && !str_contains($returnTo, '/login')) {
            $login .= '?redirect=' . rawurlencode($returnTo);
        }

        header('Location: ' . $login);
        exit;
    }

    public function after(Request $request, array|string|null $arguments = null): void
    {
        // No-op: nothing to do after dispatch for this filter.
    }
}
