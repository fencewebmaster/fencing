<?php
/**
 * FC Admin — fence styles API (writable/fences/*.php).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Models\FenceStyleModel;
use Fc\Admin\Models\FenceStylePresenter;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\FenceStyleMaintenanceService;

final class FenceStylesController
{
    public static function dispatch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

        if ($action === 'list' && $method === 'GET') {
            self::listStyles();
            return;
        }

        if ($action === 'get' && $method === 'GET') {
            self::getStyle();
            return;
        }

        if ($action === 'save' && $method === 'POST') {
            self::saveStyle();
            return;
        }

        http_response_code($method !== 'GET' && $method !== 'POST' ? 405 : 400);
        echo json_encode([
            'ok' => false,
            'error' => $method !== 'GET' && $method !== 'POST' ? 'Method not allowed.' : 'Unknown action.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function appBase(): string
    {
        $adminBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');

        return rtrim(str_replace('\\', '/', dirname($adminBase)), '/');
    }

    private static function listStyles(): void
    {
        echo json_encode(FenceStylePresenter::listPayload(self::appBase()), JSON_UNESCAPED_UNICODE);
    }

    private static function getStyle(): void
    {
        $slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
        if ($slug === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing slug.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = FenceStylePresenter::getStylePayload($slug, self::appBase());
        if (empty($result['ok'])) {
            http_response_code(404);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    private static function saveStyle(): void
    {
        $raw = file_get_contents('php://input');
        $payload = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($payload) || empty($payload['slug'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON. Expected { "slug": "...", ... }.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!AuthService::verifyCsrf(isset($payload['csrf']) ? (string) $payload['csrf'] : null)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid security token. Refresh and try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $slug = trim((string) $payload['slug']);
        $filePath = FenceStyleModel::filePath($slug);
        if ($filePath === '') {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Fence style not found.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $config = isset($payload['config']) && is_array($payload['config']) ? $payload['config'] : null;
        if ($config === null || trim((string) ($config['title'] ?? '')) === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Title is required in config.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = FenceStyleMaintenanceService::save($filePath, $slug, $config);
        if (!$result['ok']) {
            http_response_code(500);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
