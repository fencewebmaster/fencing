<?php
/**
 * FC Admin — settings API (theme colors + branding).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\BrandingSettings;
use Fc\Admin\Services\CatalogSettings;
use Fc\Admin\Services\CloudflareService;
use Fc\Admin\Services\ConsoleSettings;
use Fc\Admin\Services\DevConsoleService;
use Fc\Admin\Services\FenceColorSettings;
use Fc\Admin\Services\IntegrationsSettings;
use Fc\Admin\Services\PlannerOptionSettings;
use Fc\Admin\Services\SystemSettings;
use Fc\Admin\Services\ThemeSettings;

final class SettingsController
{
    public static function dispatch(): void
    {

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

        if ($action === 'cloudflare-verify') {
            self::handleCloudflareVerify($method);
            return;
        }

        if ($action === 'project-plan') {
            self::handleProjectPlan($method);
            return;
        }

        if ($action === 'git-pull') {
            self::handleGitPull($method);
            return;
        }

        if ($action === 'console') {
            self::handleConsole($method);
            return;
        }

        if ($action === 'export') {
            self::handleExport($method);
            return;
        }

        if ($action === 'import') {
            self::handleImport($method);
            return;
        }

        if ($action === 'dev-console') {
            self::handleDevConsole($method);
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
            echo json_encode(ThemeSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
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

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = ThemeSettings::save($payload['colors']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = ThemeSettings::apiPayload();
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
            echo json_encode(BrandingSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
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

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = BrandingSettings::save($payload['branding']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = BrandingSettings::apiPayload();
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
            echo json_encode(FenceColorSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
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

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = FenceColorSettings::save($payload['fenceColors']);
            if (!$result['ok']) {
                http_response_code(500);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = FenceColorSettings::apiPayload();
            $response['message'] = 'Fence colors saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleProjectPlan(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(PlannerOptionSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['items']) || !is_array($payload['items'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "items": [ { "slug": "pump-enclosure", "label": "Pump Enclosure", "image": "" } ] }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = PlannerOptionSettings::saveExtraItems($payload['items']);
            if (empty($result['ok'])) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $result['message'] = 'Project Plan settings saved.';
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleCatalog(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(CatalogSettings::apiPayload(true), JSON_UNESCAPED_UNICODE);
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

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = CatalogSettings::save($payload['catalog']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = CatalogSettings::apiPayload(true);
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
            echo json_encode(SystemSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
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

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = SystemSettings::save($payload['system']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = SystemSettings::apiPayload();
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
            echo json_encode(IntegrationsSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
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

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = IntegrationsSettings::save(
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

    private static function handleCloudflareVerify(string $method): void
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

        if (!AuthService::verifyCsrf(
            isset($payload['csrf']) ? (string) $payload['csrf'] : null
        )) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $token = trim((string) ($payload['cloudflareApiToken'] ?? ''));
        if ($token === '') {
            $saved = IntegrationsSettings::get();
            $token = trim((string) ($saved['cloudflareApiToken'] ?? ''));
        }

        $zoneId = trim((string) ($payload['cloudflareZoneId'] ?? ''));
        $siteKey = trim((string) ($payload['siteKey'] ?? ''));

        $result = CloudflareService::verifyZone($token, $zoneId);
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => (string) ($result['error'] ?? 'Cloudflare zone check failed.'),
                'siteKey' => $siteKey,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'ok' => true,
            'zoneName' => (string) ($result['zoneName'] ?? ''),
            'status' => (string) ($result['status'] ?? ''),
            'siteKey' => $siteKey,
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function handleConsole(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(ConsoleSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = self::jsonBody();
            if (!is_array($payload) || !isset($payload['console']) || !is_array($payload['console'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "console": { "debugMode": false } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!AuthService::verifyCsrf(
                isset($payload['csrf']) ? (string) $payload['csrf'] : null
            )) {
                http_response_code(403);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid security token. Refresh and try again.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = ConsoleSettings::save($payload['console']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = ConsoleSettings::apiPayload();
            $response['message'] = !empty($response['console']['debugMode'])
                ? 'Debug Mode turned on.'
                : 'Debug Mode turned off.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private static function handleExport(string $method): void
    {
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $settings = [
            'theme'        => ThemeSettings::get(),
            'branding'     => BrandingSettings::get(),
            'fenceColors'  => FenceColorSettings::get(),
            'catalog'      => CatalogSettings::get(),
            'system'       => SystemSettings::get(),
            'integrations' => IntegrationsSettings::get(),
            'projectPlan'  => PlannerOptionSettings::extraItems(),
            'console'      => ConsoleSettings::get(),
        ];

        $json = json_encode([
            'ok' => true,
            'type' => 'fc-admin-settings-export',
            'version' => 1,
            'exportedAt' => date('c'),
            'settings' => $settings,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="fc-settings-' . date('Y-m-d') . '.json"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $json;
    }

    private static function handleImport(string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
        if (!AuthService::verifyCsrf($csrf)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Choose a settings .json file to import.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $file = $_FILES['file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Upload failed. Please try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'settings.json');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Uploaded file is not readable.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Settings file must be between 1 byte and 5MB.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'json') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Only .json files can be imported.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $raw = file_get_contents($tmp);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'That file is not valid JSON.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $settings = is_array($decoded['settings'] ?? null) ? $decoded['settings'] : $decoded;

        $results = [];
        $appliedCount = 0;
        $failedSections = [];

        foreach (['theme', 'branding', 'fenceColors', 'catalog', 'system', 'integrations', 'projectPlan', 'console'] as $key) {
            if (!array_key_exists($key, $settings) || !is_array($settings[$key])) {
                continue;
            }

            $value = $settings[$key];
            $result = match ($key) {
                'theme' => ThemeSettings::save($value),
                'branding' => BrandingSettings::save($value),
                'fenceColors' => FenceColorSettings::save($value),
                'catalog' => CatalogSettings::save($value),
                'system' => SystemSettings::save($value),
                'integrations' => IntegrationsSettings::save($value, ''),
                'projectPlan' => PlannerOptionSettings::saveExtraItems($value),
                'console' => ConsoleSettings::save($value),
                default => ['ok' => false, 'error' => 'Unknown section.'],
            };

            $ok = !empty($result['ok']);
            $results[$key] = [
                'ok' => $ok,
                'error' => $ok ? null : (string) ($result['error'] ?? 'Failed to save.'),
            ];

            if ($ok) {
                $appliedCount++;
            } else {
                $failedSections[] = $key;
            }
        }

        if ($results === []) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'No recognized settings sections were found in that file.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $ok = $failedSections === [];
        if (!$ok) {
            http_response_code(400);
        }

        echo json_encode([
            'ok' => $ok,
            'applied' => $appliedCount,
            'results' => $results,
            'message' => $ok
                ? 'Imported ' . $appliedCount . ' setting section' . ($appliedCount === 1 ? '' : 's') . '.'
                : 'Some sections failed to import: ' . implode(', ', $failedSections) . '.',
        ], JSON_UNESCAPED_UNICODE);
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

        if (!AuthService::verifyCsrf(
            isset($payload['csrf']) ? (string) $payload['csrf'] : null
        )) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (trim((string) ($payload['confirm'] ?? '')) !== 'CONFIRM') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Type CONFIRM to pull updates.'], JSON_UNESCAPED_UNICODE);
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

        $result = DevConsoleService::pull($root);
        if (empty($result['ok'])) {
            http_response_code(500);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    private static function handleDevConsole(string $method): void
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

        if (!AuthService::verifyCsrf(
            isset($payload['csrf']) ? (string) $payload['csrf'] : null
        )) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $command = trim((string) ($payload['command'] ?? ''));
        if ($command === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Enter a command.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $root = defined('FC_ROOT') ? (string) FC_ROOT : '';
        if ($root === '' || !is_dir($root)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Project root is not available.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = DevConsoleService::runCommand($command, $root, $payload);
        if (empty($result['ok'])) {
            http_response_code(!empty($result['forbidden']) ? 403 : 400);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /** @return mixed */
    private static function jsonBody()
    {
        $raw = file_get_contents('php://input');
        return is_string($raw) ? json_decode($raw, true) : null;
    }
}
