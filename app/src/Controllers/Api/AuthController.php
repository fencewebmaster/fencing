<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Services\AuthService;

final class AuthController
{
    public static function dispatch(): void
    {
        AuthService::boot();

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

        if ($action === 'login' && $method === 'POST') {
            self::login();
            return;
        }

        if ($action === 'logout' && ($method === 'POST' || $method === 'GET')) {
            self::logout();
            return;
        }

        if ($action === 'me' && $method === 'GET') {
            self::me();
            return;
        }

        JsonResponse::error('Unknown action.', 400);
    }

    private static function login(): void
    {
        $raw = file_get_contents('php://input');
        $payload = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($payload)) {
            JsonResponse::send(['ok' => false, 'message' => 'Invalid JSON body.'], 400);
        }

        if (!AuthService::verifyCsrf(isset($payload['csrf_token']) ? (string) $payload['csrf_token'] : null)) {
            JsonResponse::send([
                'ok'      => false,
                'message' => 'Invalid security token. Please refresh the page.',
            ], 403);
        }

        if (AuthService::isLoggedIn()) {
            JsonResponse::send([
                'ok'       => true,
                'message'  => 'Already signed in.',
                'redirect' => 'dashboard',
                'user'     => AuthService::user(),
            ]);
        }

        $result = AuthService::attemptLogin(
            (string) ($payload['username'] ?? ''),
            (string) ($payload['password'] ?? ''),
            !empty($payload['remember'])
        );

        JsonResponse::send($result, !empty($result['ok']) ? 200 : 401);
    }

    private static function logout(): void
    {
        // A GET-carried ?_token= (same "logout" purpose/bucket as the web /logout route's
        // link — see AuthPresenter::logoutUrl()) is single-use, since it's exposed to
        // history/Referer/access-log leakage the same way; a POST JSON body isn't, so that
        // path keeps using the ordinary reusable session CSRF token.
        if (isset($_GET['_token'])) {
            if (!AuthService::consumeOneTimeToken('logout', (string) $_GET['_token'])) {
                JsonResponse::send([
                    'ok'      => false,
                    'message' => 'Invalid security token. Please refresh the page.',
                ], 403);
            }
        } else {
            $raw = file_get_contents('php://input');
            $payload = is_string($raw) ? json_decode($raw, true) : null;
            $token = (string) (is_array($payload) ? ($payload['csrf_token'] ?? '') : '');

            if (!AuthService::verifyCsrf($token)) {
                JsonResponse::send([
                    'ok'      => false,
                    'message' => 'Invalid security token. Please refresh the page.',
                ], 403);
            }
        }

        AuthService::logout();
        JsonResponse::send([
            'ok'       => true,
            'message'  => 'Signed out.',
            'redirect' => 'login',
        ]);
    }

    private static function me(): void
    {
        $user = AuthService::user();
        if ($user === null) {
            JsonResponse::send(['ok' => false, 'message' => 'Not signed in.'], 401);
        }

        JsonResponse::send(['ok' => true, 'user' => $user]);
    }
}
