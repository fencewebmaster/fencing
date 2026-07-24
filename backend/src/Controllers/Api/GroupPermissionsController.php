<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

final class GroupPermissionsController
{
    public static function dispatch(): void
    {
        require_once FC_ROOT . '/config/permissions.php';
        require_once FC_ROOT . '/config/auth.php';

        $action = isset($_GET['action']) ? (string) $_GET['action'] : 'get';
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($action === 'get' || $action === '') {
            $role = isset($_GET['role']) ? (string) $_GET['role'] : '';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(fc_group_permissions_api_payload($role), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($action === 'save' && $method === 'POST') {
            $raw = file_get_contents('php://input');
            $payload = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($payload)) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Invalid JSON body.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $csrf = (string) ($payload['csrf'] ?? $payload['_token'] ?? '');
            if (!fc_auth_verify_csrf($csrf)) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $role = (string) ($payload['role'] ?? '');
            $permissions = isset($payload['permissions']) && is_array($payload['permissions'])
                ? $payload['permissions']
                : [];

            $result = fc_group_permissions_save($role, $permissions);
            if (empty($result['ok'])) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'error' => (string) ($result['error'] ?? 'Save failed.'),
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'message' => 'Permissions saved.',
                'role' => fc_group_permissions_sanitize_role($role),
                'permissions' => $result['permissions'] ?? [],
                'csrf' => fc_auth_csrf_token(),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Unknown action.'], JSON_UNESCAPED_UNICODE);
    }
}
