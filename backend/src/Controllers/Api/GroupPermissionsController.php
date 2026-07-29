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

        if (($action === 'export' || $action === 'export-all') && $method === 'GET') {
            self::exportJson($action);
            return;
        }

        if ($action === 'import' && $method === 'POST') {
            self::importJson();
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

    private static function exportJson(string $action): void
    {
        if ($action === 'export') {
            $role = isset($_GET['role']) ? (string) $_GET['role'] : '';
            $slug = fc_group_permissions_sanitize_role($role);
            if ($slug === '' || $slug === 'super_admin') {
                $payload = fc_group_permissions_export_all();
                $filename = 'group-permissions.json';
            } else {
                $envelope = fc_group_permissions_export_role($slug);
                if ($envelope === null) {
                    http_response_code(400);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => false,
                        'error' => 'This role cannot be exported.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
                $payload = $envelope;
                $filename = 'group-permissions-' . $slug . '.json';
            }
        } else {
            $payload = fc_group_permissions_export_all();
            $filename = 'group-permissions.json';
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Unable to build export file.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo $json;
    }

    private static function importJson(): void
    {
        $csrf = (string) ($_POST['csrf'] ?? $_POST['_token'] ?? '');
        if (!fc_auth_verify_csrf($csrf)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'No import file uploaded.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $file = $_FILES['file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Upload failed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $name = strtolower((string) ($file['name'] ?? ''));
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Invalid upload.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Import file must be between 1 byte and 5 MB.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!str_ends_with($name, '.json')) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Only .json files can be imported.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $raw = @file_get_contents($tmp);
        if (!is_string($raw) || $raw === '') {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Could not read import file.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Import file is not valid JSON.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = fc_group_permissions_import_payload($decoded);
        if (empty($result['ok'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => (string) ($result['error'] ?? 'Import failed.'),
                'imported' => $result['imported'] ?? [],
                'skipped' => $result['skipped'] ?? [],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'message' => (string) ($result['message'] ?? 'Permissions imported.'),
            'imported' => $result['imported'] ?? [],
            'skipped' => $result['skipped'] ?? [],
            'csrf' => fc_auth_csrf_token(),
        ], JSON_UNESCAPED_UNICODE);
    }
}
