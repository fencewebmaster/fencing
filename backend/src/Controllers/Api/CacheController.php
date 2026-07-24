<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Core\Request;

final class CacheController
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(): void
    {
        fc_auth_require_login();

        if (!function_exists('fc_storage_purge_cache')) {
            require_once FC_ROOT . '/config/storage.php';
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : ($method === 'GET' ? 'stats' : 'purge');

        if ($method === 'GET') {
            switch ($action) {
                case 'stats':
                    JsonResponse::ok([
                        'ok' => true,
                        'stats' => fc_storage_cache_stats(),
                    ]);
                    break;
                default:
                    JsonResponse::error('Unknown action.', 400);
            }
        }

        if ($method !== 'POST') {
            JsonResponse::error('Method not allowed.', 405);
        }

        switch ($action) {
            case 'purge':
                $this->purge();
                break;
            default:
                JsonResponse::error('Unknown action.', 400);
        }
    }

    private function purge(): void
    {
        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            JsonResponse::error('Invalid JSON body.', 400);
        }

        if (
            function_exists('fc_auth_verify_csrf')
            && !fc_auth_verify_csrf(isset($payload['csrf']) ? (string) $payload['csrf'] : null)
        ) {
            JsonResponse::error('Invalid security token. Refresh and try again.', 403);
        }

        $target = isset($payload['target']) ? (string) $payload['target'] : 'all';
        $result = fc_storage_purge_cache($target);

        if (empty($result['ok'])) {
            JsonResponse::error((string) ($result['error'] ?? 'Could not purge cache.'), 400);
        }

        $result['stats'] = fc_storage_cache_stats();
        JsonResponse::ok($result);
    }

    public static function dispatch(): void
    {
        (new self(new Request()))->handle();
    }
}
