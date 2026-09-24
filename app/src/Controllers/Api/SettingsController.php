<?php
/**
 * FC Admin — settings API (the settings groups, settings import/export, Cloudflare verify,
 * the dev console, and the Site Health checks).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Services\AuthService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\CatalogSettings;
use Fc\Admin\Services\CloudflareService;
use Fc\Admin\Settings\ConsoleSettings;
use Fc\Admin\Services\DevConsoleService;
use Fc\Admin\Settings\FenceColorSettings;
use Fc\Admin\Settings\IntegrationsSettings;
use Fc\Admin\Services\PermissionService;
use Fc\Admin\Settings\PlannerOptionSettings;
use Fc\Admin\Settings\SeoSettings;
use Fc\Admin\Services\SiteHealthService;
use Fc\Admin\Settings\SystemSettings;
use Fc\Admin\Settings\ThemeSettings;

final class SettingsController extends BaseApiController
{
    public function handle(): void
    {
        $this->sendJsonHeaders();

        $method = $this->request->method();
        $action = (string) $this->request->query('action', '');

        if ($action === 'theme') {
            $this->handleTheme($method);
            return;
        }

        if ($action === 'branding') {
            $this->handleBranding($method);
            return;
        }

        if ($action === 'fence-colors') {
            $this->handleFenceColors($method);
            return;
        }

        if ($action === 'catalog') {
            $this->handleCatalog($method);
            return;
        }

        if ($action === 'system') {
            $this->handleSystem($method);
            return;
        }

        if ($action === 'integrations') {
            $this->handleIntegrations($method);
            return;
        }

        if ($action === 'seo') {
            $this->handleSeo($method);
            return;
        }

        if ($action === 'site-health') {
            $this->handleSiteHealth($method);
            return;
        }

        if ($action === 'cloudflare-verify') {
            $this->handleCloudflareVerify($method);
            return;
        }

        if ($action === 'project-plan') {
            $this->handleProjectPlan($method);
            return;
        }

        if ($action === 'git-pull') {
            $this->handleGitPull($method);
            return;
        }

        if ($action === 'console') {
            $this->handleConsole($method);
            return;
        }

        if ($action === 'export') {
            $this->handleExport($method);
            return;
        }

        if ($action === 'import') {
            $this->handleImport($method);
            return;
        }

        if ($action === 'dev-console') {
            $this->handleDevConsole($method);
            return;
        }

        http_response_code($action === '' ? 400 : 405);
        echo json_encode([
            'ok' => false,
            'error' => $action === '' ? 'Unknown action.' : 'Method not allowed.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private function handleTheme(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(ThemeSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['colors']) || !is_array($payload['colors'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "colors": { "--fc-princeton-orange": "#f67925" } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
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

    private function handleBranding(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(BrandingSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['branding']) || !is_array($payload['branding'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "branding": { "appName": "Fencing Calculator" } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
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

    private function handleFenceColors(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(FenceColorSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['fenceColors']) || !is_array($payload['fenceColors'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "fenceColors": [ { "slug": "black", "label": "Black · Satin", "color": "#404040", "image": "" } ] }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
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

    private function handleProjectPlan(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(PlannerOptionSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['items']) || !is_array($payload['items'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "items": [ { "slug": "pump-enclosure", "label": "Pump Enclosure", "image": "" } ] }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
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

            // Stock & Delivery rides along with the item list: one tab, one Save button. Optional
            // so an older client (or the import path) posting only `items` keeps working.
            if (isset($payload['stock']) && is_array($payload['stock'])) {
                $stockResult = PlannerOptionSettings::saveStock($payload['stock']);
                if (empty($stockResult['ok'])) {
                    http_response_code(400);
                    echo json_encode($stockResult, JSON_UNESCAPED_UNICODE);
                    return;
                }
                $result['stock'] = $stockResult['stock'];
            }

            $result['message'] = 'Project Plan settings saved.';
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private function handleCatalog(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(CatalogSettings::apiPayload(true), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['catalog']) || !is_array($payload['catalog'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "catalog": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
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

    private function handleSystem(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(SystemSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['system']) || !is_array($payload['system'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "system": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
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

    private function handleIntegrations(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(IntegrationsSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['integrations']) || !is_array($payload['integrations'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "integrations": { ... } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
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

    private function handleSeo(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(SeoSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['seo']) || !is_array($payload['seo'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "seo": { "searchEngineVisible": true } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = SeoSettings::save($payload['seo']);
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = SeoSettings::apiPayload();
            $response['message'] = 'SEO settings saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * One Site Health card's checks (?group=server|database|security). Read-only, so a GET with
     * no CSRF token; the Super Admin check is here because the report maps the server's weak spots
     * and settings.settings alone would open it to every role that can edit settings.
     */
    private function handleSiteHealth(string $method): void
    {
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!PermissionService::isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Only the Super Admin can run Site Health.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $group = (string) $this->request->query('group', '');
        if (!array_key_exists($group, SiteHealthService::GROUPS)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown check group.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $checks = SiteHealthService::run($group);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'The checks stopped: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            return;
        }

        // Error-log lines can carry invalid UTF-8, which would otherwise blank the whole response.
        echo json_encode([
            'ok' => true,
            'group' => $group,
            'checks' => $checks,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function handleCloudflareVerify(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!self::csrfOk($payload)) {
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

    private function handleConsole(string $method): void
    {
        if ($method === 'GET') {
            echo json_encode(ConsoleSettings::apiPayload(), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method === 'POST') {
            $payload = $this->request->jsonBody();
            if (!is_array($payload) || !isset($payload['console']) || !is_array($payload['console'])) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON. Expected { "console": { "debugMode": false } }.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!self::csrfOk($payload)) {
                http_response_code(403);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid security token. Refresh and try again.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // The action saves the whole console section (Debug Mode + Debugbar fields);
            // only a debugMode flip keeps the toggle's dedicated wording.
            $current = ConsoleSettings::get();
            $debugModeBefore = !empty($current['debugMode']);

            // Merge onto what is on disk so a client may post ONLY the keys it changed.
            // The page's console snapshot is taken once at load and never refreshed, so a
            // whole-object save let a stale tab re-arm (or clear) Debug Mode site-wide for
            // everyone else just by ticking an unrelated Debugbar checkbox.
            $result = ConsoleSettings::save(array_merge($current, $payload['console']));
            if (!$result['ok']) {
                http_response_code(400);
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            $response = ConsoleSettings::apiPayload();
            $response['message'] = $debugModeBefore !== !empty($response['console']['debugMode'])
                ? (!empty($response['console']['debugMode']) ? 'Debug Mode turned on.' : 'Debug Mode turned off.')
                : 'Console settings saved.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    }

    private function handleExport(string $method): void
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
            'projectPlanStock' => PlannerOptionSettings::stock(),
            'seo'          => SeoSettings::get(),
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

    private function handleImport(string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $csrf = (string) $this->request->post('csrf', '');
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

        foreach (['theme', 'branding', 'fenceColors', 'catalog', 'system', 'integrations', 'projectPlan', 'seo', 'console'] as $key) {
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
                'seo' => SeoSettings::save($value),
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

    private function handleGitPull(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!self::csrfOk($payload)) {
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

    private function handleDevConsole(string $method): void
    {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!self::csrfOk($payload)) {
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
}
