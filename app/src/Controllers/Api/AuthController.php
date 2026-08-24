<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Core\Request;
use Fc\Admin\Services\AuthService;

final class AuthController extends BaseApiController
{
    public static function dispatch(): void
    {
        (new self(new Request()))->handle();
    }

    public function handle(): void
    {
        AuthService::boot();

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = $this->request->method();
        $action = (string) $this->request->query('action', '');

        if ($action === 'login' && $method === 'POST') {
            $this->login();
            return;
        }

        if ($action === 'logout' && ($method === 'POST' || $method === 'GET')) {
            $this->logout();
            return;
        }

        if ($action === 'me' && $method === 'GET') {
            self::me();
            return;
        }

        JsonResponse::error('Unknown action.', 400);
    }

    private function login(): void
    {
        $payload = $this->request->jsonBody();
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

    private function logout(): void
    {
        // A GET-carried ?_token= (same "logout" purpose/bucket as the web /logout route's
        // link — see AuthPresenter::logoutUrl()) is single-use, since it's exposed to
        // history/Referer/access-log leakage the same way; a POST JSON body isn't, so that
        // path keeps using the ordinary reusable session CSRF token.
        if ($this->request->has('_token')) {
            if (!AuthService::consumeOneTimeToken('logout', (string) $this->request->query('_token', ''))) {
                JsonResponse::send([
                    'ok'      => false,
                    'message' => 'Invalid security token. Please refresh the page.',
                ], 403);
            }
        } else {
            $payload = $this->request->jsonBody();
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
