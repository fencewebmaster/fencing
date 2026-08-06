<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Core\Request;
use Fc\Admin\Filters\AuthFilter;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\CacheStorageService;
use Fc\Admin\Services\CloudflareService;

final class CacheController extends BaseApiController
{
    public function handle(): void
    {
        (new AuthFilter())->before($this->request);

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : ($method === 'GET' ? 'stats' : 'purge');

        if ($method === 'GET') {
            switch ($action) {
                case 'stats':
                    JsonResponse::ok([
                        'ok' => true,
                        'stats' => CacheStorageService::cacheStats(),
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

        if (!AuthService::verifyCsrf(isset($payload['csrf']) ? (string) $payload['csrf'] : null)) {
            JsonResponse::error('Invalid security token. Refresh and try again.', 403);
        }

        $target = isset($payload['target']) ? (string) $payload['target'] : 'all';

        if (strtolower(trim($target)) === 'cloudflare') {
            $result = CloudflareService::purgeCache();
        } else {
            $result = CacheStorageService::purgeCache($target);
        }

        if (empty($result['ok'])) {
            JsonResponse::error((string) ($result['error'] ?? 'Could not purge cache.'), 400);
        }

        $result['stats'] = CacheStorageService::cacheStats();
        JsonResponse::ok($result);
    }

    public static function dispatch(): void
    {
        (new self(new Request()))->handle();
    }
}
