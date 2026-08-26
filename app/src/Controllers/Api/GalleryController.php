<?php
/**
 * FC Admin — media gallery API (public/assets/uploads).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Models\GalleryModel;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\GalleryMaintenanceService;

final class GalleryController extends BaseApiController
{
    public function handle(): void
    {
        $this->sendJsonHeaders();

        $method = $this->request->method();
        $action = (string) $this->request->query('action', '');

        if ($action === 'list') {
            $this->listItems();
            return;
        }

        if ($action === 'upload' && $method === 'POST') {
            $this->upload();
            return;
        }

        if ($action === 'delete' && $method === 'POST') {
            $this->delete();
            return;
        }

        $isKnownAction = $action === 'upload' || $action === 'delete';
        http_response_code($isKnownAction ? 405 : 400);
        echo json_encode([
            'ok' => false,
            'error' => $isKnownAction ? 'Method not allowed.' : 'Unknown action.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private function listItems(): void
    {
        echo json_encode(GalleryModel::listItems(), JSON_UNESCAPED_UNICODE);
    }

    private function upload(): void
    {
        $csrfPosted = $this->request->post('csrf');
        $csrf = $csrfPosted !== null ? (string) $csrfPosted : null;
        if (!AuthService::verifyCsrf($csrf)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid security token. Refresh and try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = GalleryMaintenanceService::upload();
        if (empty($result['ok'])) {
            http_response_code(400);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    private function delete(): void
    {
        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        $csrf = isset($payload['csrf']) ? (string) $payload['csrf'] : null;
        if (!AuthService::verifyCsrf($csrf)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid security token. Refresh and try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $path = (string) ($payload['path'] ?? '');
        $result = GalleryMaintenanceService::delete($path);
        if (empty($result['ok'])) {
            http_response_code(400);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
