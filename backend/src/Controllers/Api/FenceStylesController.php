<?php
/**
 * FC Admin â€” fence styles API (data/fences/*.php).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

final class FenceStylesController
{
    public static function dispatch(): void
    {
        require_once FC_ROOT . '/config/fence_php_io.php';

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

    private static function listStyles(): void
    {
        echo json_encode(self::listPayload(), JSON_UNESCAPED_UNICODE);
    }

    /** @return array{ok:bool,styles:list<array<string,mixed>>,total:int} */
    public static function listPayload(): array
    {
        $catalog = self::fenceCatalog();
        $styles = [];

        foreach ($catalog['fences'] as $slug => $info) {
            if (!is_array($info)) {
                continue;
            }
            $sourceFile = isset($catalog['fileSlugMap'][$slug]) ? basename($catalog['fileSlugMap'][$slug]) : '';
            $styles[] = self::stylePayload($slug, $info, $sourceFile, $catalog['appBase']);
        }

        usort($styles, static function (array $a, array $b): int {
            if ($a['sortOrder'] !== $b['sortOrder']) {
                return $a['sortOrder'] <=> $b['sortOrder'];
            }

            return strcasecmp($a['title'], $b['title']);
        });

        return [
            'ok' => true,
            'styles' => $styles,
            'total' => count($styles),
            'canEdit' => !function_exists('fc_auth_user_can') || fc_auth_user_can('products.fence_styles.edit'),
            'canView' => !function_exists('fc_auth_user_can')
                || fc_auth_user_can('products.fence_styles.view')
                || fc_auth_user_can('products.fence_styles.edit'),
        ];
    }

    private static function getStyle(): void
    {
        $slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
        if ($slug === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing slug.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $catalog = self::fenceCatalog();
        if (!isset($catalog['fences'][$slug]) || !is_array($catalog['fences'][$slug])) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Fence style not found.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filePath = $catalog['fileSlugMap'][$slug] ?? '';
        $sourceFile = $filePath !== '' ? basename($filePath) : '';
        $style = self::stylePayload($slug, $catalog['fences'][$slug], $sourceFile, $catalog['appBase']);
        $fileMeta = $filePath !== '' ? self::fileMeta($filePath, $slug) : ['fileType' => 'unknown', 'parentSlug' => ''];

        echo json_encode([
            'ok' => true,
            'style' => $style,
            'config' => $catalog['fences'][$slug],
            'fileMeta' => $fileMeta,
            'canEdit' => !function_exists('fc_auth_user_can') || fc_auth_user_can('products.fence_styles.edit'),
            'canView' => !function_exists('fc_auth_user_can')
                || fc_auth_user_can('products.fence_styles.view')
                || fc_auth_user_can('products.fence_styles.edit'),
        ], JSON_UNESCAPED_UNICODE);
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

        $slug = trim((string) $payload['slug']);
        $catalog = self::fenceCatalog();

        if (!isset($catalog['fileSlugMap'][$slug])) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Fence style not found.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filePath = $catalog['fileSlugMap'][$slug];

        if (isset($payload['config']) && is_array($payload['config'])) {
            $config = $payload['config'];
            $title = trim((string) ($config['title'] ?? ''));
            if ($title === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Title is required in config.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = fc_fence_write_config($filePath, $slug, $config);
            if (empty($result['ok'])) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'error' => $result['error'] ?? 'Could not save fence configuration.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'ok' => true,
                'message' => 'Fence style saved.',
                'fileType' => $result['fileType'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $name = trim((string) ($payload['name'] ?? ''));
        $image = trim((string) ($payload['image'] ?? ''));
        $live = !empty($payload['live']);

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Title is required.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $image = ltrim(str_replace('\\', '/', $image), '/');
        $updates = [
            'title' => $title,
            'name' => $name,
            'image' => $image,
            'live' => $live,
        ];

        foreach ($updates as $key => $value) {
            if (!self::updateFenceField($filePath, $slug, $key, $value)) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Could not update "' . $key . '" in ' . basename($filePath) . '.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        echo json_encode([
            'ok' => true,
            'message' => 'Fence style saved.',
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{fences: array<string, array<string, mixed>>, fileSlugMap: array<string, string>, appBase: string, fencesDir: string}
     */
    private static function fenceCatalog(): array
    {
        $fencesDir = FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'fences';
        $files = glob($fencesDir . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_NATURAL);

        $fences = [];
        $fileSlugMap = [];

        foreach ($files as $fenceFile) {
            if (!is_readable($fenceFile)) {
                continue;
            }
            $beforeKeys = array_keys($fences);
            include $fenceFile;
            $newKeys = array_diff(array_keys($fences), $beforeKeys);
            foreach ($newKeys as $key) {
                $fileSlugMap[$key] = $fenceFile;
            }
        }

        $adminBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');
        $appBase = rtrim(str_replace('\\', '/', dirname($adminBase)), '/');

        return [
            'fences' => $fences,
            'fileSlugMap' => $fileSlugMap,
            'appBase' => $appBase,
            'fencesDir' => $fencesDir,
        ];
    }

    /**
     * @param array<string, mixed> $info
     * @return array<string, mixed>
     */
    private static function stylePayload(string $slug, array $info, string $sourceFile, string $appBase): array
    {
        $imagePath = isset($info['image']) ? ltrim((string) $info['image'], '/') : '';
        $sortOrder = 0;
        if ($sourceFile !== '' && preg_match('/^(\d+)-/', $sourceFile, $m)) {
            $sortOrder = (int) $m[1];
        }

        return [
            'slug' => (string) ($info['slug'] ?? $slug),
            'title' => (string) ($info['title'] ?? $slug),
            'name' => (string) ($info['name'] ?? ''),
            'image' => $imagePath,
            'imageUrl' => $imagePath !== '' ? $appBase . '/' . $imagePath : '',
            'live' => !empty($info['live']),
            'file' => $sourceFile,
            'sortOrder' => $sortOrder,
            'panel_group' => isset($info['panel_group']) ? (string) $info['panel_group'] : '',
            'panel_count' => isset($info['panel_count']) ? (string) $info['panel_count'] : '',
            'color' => isset($info['color']) && is_array($info['color']) ? array_values($info['color']) : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fileMeta(string $filePath, string $slug): array
    {
        $meta = [
            'fileType' => 'unknown',
            'parentSlug' => '',
        ];

        if (!is_readable($filePath)) {
            return $meta;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return $meta;
        }

        $meta['fileType'] = fc_fence_detect_file_type($content, $slug);
        $parentSlug = fc_fence_detect_parent_slug($content, $slug);
        if ($parentSlug !== null) {
            $meta['parentSlug'] = $parentSlug;
        }

        return $meta;
    }

    /**
     * @param scalar $value
     */
    private static function updateFenceField(string $filePath, string $slug, string $key, $value): bool
    {
        if (!is_file($filePath) || !is_writable($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $escapedSlug = preg_quote($slug, '/');
        $escapedKey = preg_quote($key, '/');

        if (is_bool($value)) {
            $literal = fc_fence_php_bool_literal($value);
            $patterns = [
                '/(\$fences\[\'' . $escapedSlug . '\'\]\[\'' . $escapedKey . '\'\]\s*=\s*)(?:TRUE|FALSE)\s*;/',
                '/^(\t\'' . $escapedKey . '\'\s*=>\s*)(?:TRUE|FALSE)(\s*,)/m',
            ];
            $replacements = ['$1' . $literal . ';', '$1' . $literal . '$2'];
        } else {
            $literal = fc_fence_php_string_literal((string) $value);
            $patterns = [
                '/(\$fences\[\'' . $escapedSlug . '\'\]\[\'' . $escapedKey . '\'\]\s*=\s*)(?:\'(?:\\\\.|[^\'])*\'|"[^"]*")\s*;/',
                '/^(\t\'' . $escapedKey . '\'\s*=>\s*)(?:\'(?:\\\\.|[^\'])*\'|"[^"]*")(\s*,)/m',
            ];
            $replacements = ['$1' . $literal . ';', '$1' . $literal . '$2'];
        }

        $updated = $content;
        $matched = false;
        foreach ($patterns as $idx => $pattern) {
            $next = preg_replace($pattern, $replacements[$idx], $updated, 1, $count);
            if ($count > 0 && is_string($next)) {
                $updated = $next;
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return false;
        }

        if ($updated === $content) {
            return true;
        }

        return file_put_contents($filePath, $updated) !== false;
    }
}
