<?php
/**
 * FC Admin — settings API (theme colors + branding).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

final class SettingsController
{
    public static function dispatch(): void
    {
        require_once FC_ROOT . '/config/theme.php';
        require_once FC_ROOT . '/config/branding.php';
        require_once FC_ROOT . '/config/fence-colors.php';
        require_once FC_ROOT . '/config/catalog.php';
        require_once FC_ROOT . '/config/system.php';
        require_once FC_ROOT . '/config/integrations.php';

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

        if ($action === 'theme') {
            self::handleTheme($method);
            return;
        }

        if ($action === 'branding') {
            self::handleBranding($method);
            return;
        }

        if ($action === 'fence-colors') {
            self::handleFenceColors($method);
            return;
        }

        if ($action === 'catalog') {
            self::handleCatalog($method);
            return;
        }

        if ($action === 'system') {
            self::handleSystem($method);
            return;
        }

        if ($action === 'integrations') {
            self::handleIntegrations($method);
            return;
        }

        if ($action === 'git-pull') {
            self::handleGitPull($method);
            return;
        }

        http_response_code($action === '' ? 400 : 405);
        echo json_encode([
            'ok' => false,
            'error' => $action === '' ? 'Unknown action.' : 'Method not allowed.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function handleTheme(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_theme_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['colors']) || !is_array($payload['colors'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "colors": { "--fc-princeton-orange": "#f67925" } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_theme_save($payload['colors']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_theme_api_payload();
            $response['message'] = 'Theme saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleBranding(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_branding_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['branding']) || !is_array($payload['branding'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "branding": { "appName": "Fencing Calculator" } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_branding_save($payload['branding']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_branding_api_payload();
            $response['message'] = 'Branding saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleFenceColors(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_fence_colors_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['fenceColors']) || !is_array($payload['fenceColors'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "fenceColors": [ { "slug": "black", "label": "Black · Satin", "color": "#404040", "image": "" } ] }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_fence_colors_save($payload['fenceColors']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_fence_colors_api_payload();
            $response['message'] = 'Fence colors saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleCatalog(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_catalog_api_payload(true), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['catalog']) || !is_array($payload['catalog'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "catalog": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_catalog_save($payload['catalog']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_catalog_api_payload(true);
            $response['message'] = 'Catalog settings saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleSystem(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_system_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['system']) || !is_array($payload['system'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "system": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_system_save($payload['system']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = fc_system_api_payload();
            $response['message'] = 'System settings saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleIntegrations(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(fc_integrations_api_payload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['integrations']) || !is_array($payload['integrations'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "integrations": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_integrations_save(
                $payload['integrations'],
                isset($payload['revision']) ? (string) $payload['revision'] : ''
            );
            if (empty($result['ok'])) {
                http_response_code(!empty($result['conflict']) ? 409 : 400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $result['message'] = 'Integration settings saved.';
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleGitPull(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = self::jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf(
            isset($payload['csrf']) ? (string) $payload['csrf'] : null
        )) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $root = defined('FC_ROOT') ? (string) FC_ROOT : '';
        if ($root === '' || !is_dir($root)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Project root is not available.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!is_dir($root . DIRECTORY_SEPARATOR . '.git')) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'This install is not a git repository.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = self::runGitPull($root);
        if (empty($result['ok'])) {
            http_response_code(500);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{ok:bool,output:string,message?:string,error?:string,exitCode?:int}
     */
    private static function runGitPull(string $root): array
    {
        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = null;
        $pipes = [];

        // Prefer argv form so the shell is not required for argument parsing.
        if (PHP_VERSION_ID >= 70400) {
            $process = @proc_open(['git', 'pull'], $descriptor, $pipes, $root);
        }

        if (!is_resource($process)) {
            $process = @proc_open('git pull', $descriptor, $pipes, $root);
        }

        if (!is_resource($process)) {
            return [
                'ok' => false,
                'error' => 'Could not start git pull.',
                'output' => '',
            ];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $stdout = is_string($stdout) ? trim($stdout) : '';
        $stderr = is_string($stderr) ? trim($stderr) : '';
        $output = trim(implode("\n", array_filter([$stdout, $stderr], static fn(string $chunk): bool => $chunk !== '')));

        if ($exitCode !== 0) {
            return [
                'ok' => false,
                'error' => 'git pull failed.',
                'output' => $output !== '' ? $output : 'git pull exited with code ' . $exitCode . '.',
                'exitCode' => $exitCode,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Updates pulled successfully.',
            'output' => $output !== '' ? $output : 'Already up to date.',
            'exitCode' => $exitCode,
        ];
    }

    /** @return mixed */
    private static function jsonBody()
    {
        $raw = file_get_contents('php://input');
        return is_string($raw) ? json_decode($raw, true) : null;
    }
}
