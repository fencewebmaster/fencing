<?php
/**
 * FC Admin — media gallery API (public/assets/uploads).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Models\GalleryModel;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\GalleryMaintenanceService;

final class GalleryController
{
    public static function dispatch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

        if ($action === 'list') {
            self::listItems();
            return;
        }

        if ($action === 'upload' && $method === 'POST') {
            self::upload();
            return;
        }

        if ($action === 'delete' && $method === 'POST') {
            self::delete();
            return;
        }

        $isKnownAction = $action === 'upload' || $action === 'delete';
        http_response_code($isKnownAction ? 405 : 400);
        echo json_encode([
            'ok' => false,
            'error' => $isKnownAction ? 'Method not allowed.' : 'Unknown action.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function listItems(): void
    {
        echo json_encode(GalleryModel::listItems(), JSON_UNESCAPED_UNICODE);
    }

    private static function upload(): void
    {
        $csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : null;
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

    private static function delete(): void
    {
        $raw = file_get_contents('php://input');
        $payload = is_string($raw) ? json_decode($raw, true) : null;
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
