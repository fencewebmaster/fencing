<?php
/**
 * FC Admin — fence styles API (writable/fences/*.php).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\Request;
use Fc\Admin\Models\FenceStyleModel;
use Fc\Admin\Models\FenceStylePresenter;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\FenceFileService;
use Fc\Admin\Services\FenceStyleMaintenanceService;

final class FenceStylesController extends BaseApiController
{
    public static function dispatch(): void
    {
        (new self(new Request()))->handle();
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = $this->request->method();
        $action = (string) $this->request->query('action', '');

        if ($action === 'list' && $method === 'GET') {
            $this->listStyles();
            return;
        }

        if ($action === 'get' && $method === 'GET') {
            $this->getStyle();
            return;
        }

        if ($action === 'export' && $method === 'GET') {
            $this->exportStyle();
            return;
        }

        if ($action === 'save' && $method === 'POST') {
            $this->saveStyle();
            return;
        }

        if ($action === 'bulk-export' && $method === 'GET') {
            $this->bulkExportStyles();
            return;
        }

        if ($action === 'bulk-status' && $method === 'POST') {
            $this->bulkSetStatus();
            return;
        }

        if ($action === 'bulk-import' && $method === 'POST') {
            $this->bulkImportStyles();
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

    private function listStyles(): void
    {
        echo json_encode(FenceStylePresenter::listPayload(self::appBase()), JSON_UNESCAPED_UNICODE);
    }

    private function getStyle(): void
    {
        $slug = trim((string) $this->request->query('slug', ''));
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

    private function exportStyle(): void
    {
        $slug = trim((string) $this->request->query('slug', ''));
        if ($slug === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing slug.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $catalog = FenceStyleModel::catalog();
        if (!isset($catalog['fences'][$slug]) || !is_array($catalog['fences'][$slug])) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Fence style not found.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $config = $catalog['fences'][$slug];
        $title = (string) ($config['title'] ?? $slug);
        $fileSlug = (string) preg_replace('/[^a-z0-9\-_]+/i', '-', $slug);

        $json = json_encode([
            'ok' => true,
            'type' => 'fc-fence-style-export',
            'version' => 1,
            'exportedAt' => date('c'),
            'slug' => $slug,
            'title' => $title,
            'config' => $config,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        header('Content-Disposition: attachment; filename="fence-style-' . $fileSlug . '-' . date('Y-m-d') . '.json"');
        header('X-Content-Type-Options: nosniff');
        echo $json;
    }

    private function saveStyle(): void
    {
        $payload = $this->request->jsonBody();
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

    private function bulkExportStyles(): void
    {
        $slugs = array_values(array_filter(array_map('trim', explode(',', (string) $this->request->query('slugs', '')))));
        if ($slugs === []) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No fence styles selected.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $catalog = FenceStyleModel::catalog();
        $styles = [];
        foreach ($slugs as $slug) {
            if (isset($catalog['fences'][$slug]) && is_array($catalog['fences'][$slug])) {
                $styles[] = [
                    'slug' => $slug,
                    'title' => (string) ($catalog['fences'][$slug]['title'] ?? $slug),
                    'config' => $catalog['fences'][$slug],
                ];
            }
        }

        if ($styles === []) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'None of the selected fence styles were found.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $json = json_encode([
            'ok' => true,
            'type' => 'fc-fence-style-bulk-export',
            'version' => 1,
            'exportedAt' => date('c'),
            'styles' => $styles,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        header('Content-Disposition: attachment; filename="fence-styles-export-' . date('Y-m-d') . '.json"');
        header('X-Content-Type-Options: nosniff');
        echo $json;
    }

    private function bulkSetStatus(): void
    {
        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON body.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!AuthService::verifyCsrf(isset($payload['csrf']) ? (string) $payload['csrf'] : null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $live = !empty($payload['live']);
        $slugs = is_array($payload['slugs'] ?? null) ? array_map('strval', $payload['slugs']) : [];
        if ($slugs === []) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No fence styles selected.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $catalog = FenceStyleModel::catalog();
        $updated = 0;
        $failed = [];

        foreach ($slugs as $slug) {
            $slug = trim($slug);
            $filePath = $catalog['fileSlugMap'][$slug] ?? '';
            if ($slug === '' || $filePath === '' || !isset($catalog['fences'][$slug]) || !is_array($catalog['fences'][$slug])) {
                $failed[] = $slug;
                continue;
            }

            $config = $catalog['fences'][$slug];
            $config['live'] = $live;

            $result = FenceStyleMaintenanceService::save($filePath, $slug, $config);
            if (!empty($result['ok'])) {
                $updated++;
            } else {
                $failed[] = $slug;
            }
        }

        $ok = $failed === [];
        if (!$ok) {
            http_response_code(400);
        }

        echo json_encode([
            'ok' => $ok,
            'updated' => $updated,
            'failed' => $failed,
            'message' => $updated . ' fence style' . ($updated === 1 ? '' : 's') . ' marked as ' . ($live ? 'Live' : 'Draft')
                . ($failed !== [] ? ('. ' . count($failed) . ' failed.') : '.'),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function bulkImportStyles(): void
    {
        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON body.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!AuthService::verifyCsrf(isset($payload['csrf']) ? (string) $payload['csrf'] : null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Refresh and try again.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $styles = is_array($payload['styles'] ?? null) ? $payload['styles'] : [];
        if ($styles === []) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No fence styles found to import.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $catalog = FenceStyleModel::catalog();
        $updated = 0;
        $created = 0;
        $failed = [];

        foreach ($styles as $entry) {
            if (!is_array($entry)) {
                $failed[] = '';
                continue;
            }

            $slug = trim((string) ($entry['slug'] ?? ''));
            $config = isset($entry['config']) && is_array($entry['config']) ? $entry['config'] : null;

            if ($slug === '' || $config === null || trim((string) ($config['title'] ?? '')) === '') {
                $failed[] = $slug;
                continue;
            }

            $filePath = $catalog['fileSlugMap'][$slug] ?? '';

            if ($filePath !== '') {
                $result = FenceStyleMaintenanceService::save($filePath, $slug, $config);
                if (!empty($result['ok'])) {
                    $updated++;
                } else {
                    $failed[] = $slug;
                }
                continue;
            }

            // Slug has no writable/fences/*.php file yet — create one instead of failing,
            // so importing a backup/export into an empty or partial catalog can bootstrap it.
            $result = FenceFileService::createFile($slug, $config);
            if (!empty($result['ok'])) {
                $created++;
                $catalog['fileSlugMap'][$slug] = (string) $result['filePath'];
            } else {
                $failed[] = $slug;
            }
        }

        $ok = $failed === [];
        if (!$ok) {
            http_response_code(400);
        }

        $imported = $updated + $created;
        $detail = [];
        if ($created > 0) {
            $detail[] = $created . ' created';
        }
        if ($updated > 0) {
            $detail[] = $updated . ' updated';
        }

        echo json_encode([
            'ok' => $ok,
            'updated' => $imported,
            'created' => $created,
            'failed' => $failed,
            'message' => $imported . ' fence style' . ($imported === 1 ? '' : 's') . ' imported'
                . ($detail !== [] ? (' (' . implode(', ', $detail) . ')') : '')
                . ($failed !== [] ? ('. ' . count($failed) . ' failed (invalid config).') : '.'),
        ], JSON_UNESCAPED_UNICODE);
    }
}
