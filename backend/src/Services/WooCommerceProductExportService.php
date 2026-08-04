<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Exports WooCommerce products from the WordPress MySQL database into
 * data/wc-products-{GO|JG}.csv (ID,SKU,Name,Images).
 *
 * Uses the database rather than the public Store API so private/draft products
 * are included (3000+ rows), matching the full WooCommerce catalogue. Variable
 * product variations are included too, one CSV row per variation SKU — each
 * variation's Name/Images fall back to its parent product where it has none
 * of its own (see fetchProductsPage()).
 */
final class WooCommerceProductExportService
{
    private const SOURCES = ['GO', 'JG'];

    private const PER_PAGE = 100;

    /** Post types included in the export. */
    private const POST_TYPES = ['product', 'product_variation'];

    /** Product statuses included in the export (excludes trash). */
    private const STATUSES = ['publish', 'private', 'draft', 'pending'];

    /**
     * Last-resort per-source image host when WP home/siteurl is missing.
     * Connected store home/siteurl always wins (including localhost).
     *
     * @var array<string, string>
     */
    private const IMAGE_HOSTS = [
        'GO' => 'https://fencinggoldcoast.au',
        'JG' => 'https://fencesperth.com',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function start(string $source): array
    {
        $source = self::normalizeSource($source);
        if ($source === '') {
            return self::error('Invalid product source.');
        }

        return self::withLock($source, static function () use ($source): array {
            $db = self::connectDb();
            if (empty($db['ok'])) {
                return self::error((string) ($db['error'] ?? 'Unable to connect to the WooCommerce database.'));
            }

            /** @var PDO $pdo */
            $pdo = $db['pdo'];
            $prefix = (string) $db['prefix'];
            $total = self::countProducts($pdo, $prefix);
            if ($total <= 0) {
                return self::error('No WooCommerce products were found in the database.');
            }

            $workingPath = self::workingPath($source);
            $handle = @fopen($workingPath, 'wb');
            if ($handle === false) {
                return self::error('Unable to create the downloading CSV file.');
            }

            $headerWritten = fputcsv($handle, ['ID', 'SKU', 'Name', 'Images']);
            fflush($handle);
            fclose($handle);
            if ($headerWritten === false) {
                @unlink($workingPath);
                return self::error('Unable to write the CSV header.');
            }

            $now = gmdate('c');
            $totalPages = (int) max(1, (int) ceil($total / self::PER_PAGE));
            $state = [
                'id' => bin2hex(random_bytes(16)),
                'source' => $source,
                'status' => 'running',
                'page' => 0,
                'totalPages' => $totalPages,
                'processed' => 0,
                'total' => $total,
                'percent' => 0,
                'lastBatch' => 0,
                'lastId' => 0,
                'workingFile' => basename($workingPath),
                'finalFile' => basename(self::finalPath($source)),
                'store' => self::storeLabel($source, $db),
                'startedAt' => $now,
                'updatedAt' => $now,
                'completedAt' => null,
                'message' => 'Ready to export page 1 of ' . $totalPages . ' (' . $total . ' products).',
                'error' => '',
            ];

            if (!self::writeState($source, $state)) {
                @unlink($workingPath);
                return self::error('Unable to create the download job.');
            }

            return ['ok' => true, 'job' => self::publicState($state)];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function step(string $source, string $jobId): array
    {
        $source = self::normalizeSource($source);
        $jobId = trim($jobId);
        if ($source === '' || $jobId === '') {
            return self::error('Invalid download job.');
        }

        return self::withLock($source, static function () use ($source, $jobId): array {
            $state = self::readState($source);
            if ($state === null || !hash_equals((string) ($state['id'] ?? ''), $jobId)) {
                return self::error('Download job not found.');
            }
            if (($state['status'] ?? '') === 'complete') {
                return ['ok' => true, 'job' => self::publicState($state)];
            }
            if (($state['status'] ?? '') !== 'running') {
                return self::error((string) ($state['error'] ?? 'Download job is not running.'), $state);
            }

            $db = self::connectDb();
            if (empty($db['ok'])) {
                return self::failState(
                    $source,
                    $state,
                    (string) ($db['error'] ?? 'Unable to connect to the WooCommerce database.')
                );
            }

            /** @var PDO $pdo */
            $pdo = $db['pdo'];
            $prefix = (string) $db['prefix'];
            $lastId = max(0, (int) ($state['lastId'] ?? 0));
            $batch = self::fetchProductsPage($pdo, $prefix, $source, $lastId, self::PER_PAGE);
            if (empty($batch['ok'])) {
                return self::failState(
                    $source,
                    $state,
                    (string) ($batch['error'] ?? 'Product export failed.')
                );
            }

            $products = is_array($batch['products'] ?? null) ? $batch['products'] : [];
            $handle = @fopen(self::workingPath($source), 'ab');
            if ($handle === false) {
                return self::failState($source, $state, 'Unable to append to the downloading CSV file.');
            }

            $written = 0;
            $maxId = $lastId;
            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $id = (int) ($product['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $row = [
                    (string) $id,
                    (string) ($product['sku'] ?? ''),
                    (string) ($product['name'] ?? ''),
                    (string) ($product['images'] ?? ''),
                ];
                if (fputcsv($handle, $row) === false) {
                    fclose($handle);
                    return self::failState($source, $state, 'Unable to write a product row to the CSV file.');
                }
                $written++;
                if ($id > $maxId) {
                    $maxId = $id;
                }
            }
            fflush($handle);
            fclose($handle);

            $total = max(0, (int) ($state['total'] ?? 0));
            $totalPages = max(1, (int) ($state['totalPages'] ?? 1));
            $nextPage = max(1, (int) ($state['page'] ?? 0) + 1);
            $state['page'] = $nextPage;
            $state['lastId'] = $maxId;
            $state['processed'] = (int) ($state['processed'] ?? 0) + $written;
            $state['lastBatch'] = $written;
            $state['percent'] = $total > 0
                ? min(100, (int) floor(((int) $state['processed'] / $total) * 100))
                : 0;
            $state['updatedAt'] = gmdate('c');
            $state['store'] = self::storeLabel($source, $db);

            $isComplete = $written === 0 || count($products) < self::PER_PAGE
                || (int) $state['processed'] >= $total;
            if ($isComplete) {
                $finalized = self::finalize(
                    $source,
                    (int) $state['processed'],
                    $total
                );
                if (empty($finalized['ok'])) {
                    return self::failState(
                        $source,
                        $state,
                        (string) ($finalized['error'] ?? 'Unable to replace the products CSV file.')
                    );
                }
                $state['status'] = 'complete';
                $state['percent'] = 100;
                $state['completedAt'] = gmdate('c');
                $state['updatedAt'] = $state['completedAt'];
                $state['message'] = 'Download complete. Exported '
                    . (int) $state['processed']
                    . ' products to '
                    . basename(self::finalPath($source))
                    . '.';
            } else {
                $state['message'] = 'Exported page ' . $nextPage . ' of ' . $totalPages
                    . ' (' . (int) $state['processed'] . ' of ' . $total . ' products).';
            }

            self::writeState($source, $state);
            return ['ok' => true, 'job' => self::publicState($state)];
        });
    }

    /**
     * Cancel an active export and remove its partial CSV.
     *
     * @return array<string, mixed>
     */
    public static function cancel(string $source, string $jobId): array
    {
        $source = self::normalizeSource($source);
        $jobId = trim($jobId);
        if ($source === '' || $jobId === '') {
            return self::error('Invalid download job.');
        }

        return self::withLock($source, static function () use ($source, $jobId): array {
            $state = self::readState($source);
            if ($state === null || !hash_equals((string) ($state['id'] ?? ''), $jobId)) {
                return self::error('Download job not found.');
            }

            $workingPath = self::workingPath($source);
            if (is_file($workingPath) && !@unlink($workingPath)) {
                return self::error('Unable to delete the temporary download file.', $state);
            }

            $state['status'] = 'cancelled';
            $state['updatedAt'] = gmdate('c');
            $state['message'] = 'Download cancelled. The temporary file was deleted.';
            $state['error'] = '';
            self::writeState($source, $state);

            return ['ok' => true, 'job' => self::publicState($state)];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function status(string $source): array
    {
        $source = self::normalizeSource($source);
        if ($source === '') {
            return self::error('Invalid product source.');
        }

        return self::withLock($source, static function () use ($source): array {
            $state = self::readState($source);
            if ($state === null) {
                return [
                    'ok' => true,
                    'job' => [
                        'source' => $source,
                        'status' => 'idle',
                        'workingFile' => basename(self::workingPath($source)),
                        'finalFile' => basename(self::finalPath($source)),
                        'store' => 'WordPress database',
                        'message' => 'Ready to start.',
                    ],
                ];
            }

            return ['ok' => true, 'job' => self::publicState($state)];
        });
    }

    private static function normalizeSource(string $source): string
    {
        $source = strtoupper(trim($source));
        return in_array($source, self::SOURCES, true) ? $source : '';
    }

    private static function dataPath(string $filename): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $filename;
    }

    private static function finalPath(string $source): string
    {
        return self::dataPath('wc-products-' . $source . '.csv');
    }

    private static function workingPath(string $source): string
    {
        return self::dataPath('wc-products-' . $source . '-downloading.csv');
    }

    private static function statePath(string $source): string
    {
        return self::dataPath('.wc-products-' . $source . '-job.json');
    }

    private static function lockPath(string $source): string
    {
        return self::dataPath('.wc-products-' . $source . '.lock');
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function readState(string $source): ?array
    {
        $path = self::statePath($source);
        if (!is_readable($path)) {
            return null;
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $state
     */
    private static function writeState(string $source, array $state): bool
    {
        $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json)
            && file_put_contents(self::statePath($source), $json, LOCK_EX) !== false;
    }

    /**
     * @return array{ok:bool,pdo?:PDO,prefix?:string,database?:string,error?:string}
     */
    private static function connectDb(): array
    {
        if (!function_exists('fc_db_pdo')) {
            $dbConfig = FC_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db_config.php';
            if (!is_readable($dbConfig)) {
                return ['ok' => false, 'error' => 'Database config is missing.'];
            }
            require_once $dbConfig;
        }

        $conn = fc_db_pdo();
        if (!($conn['pdo'] instanceof PDO)) {
            $message = function_exists('fc_db_connect_error_message')
                ? fc_db_connect_error_message((string) ($conn['error'] ?? ''))
                : (string) ($conn['error'] ?? 'Database connection failed.');
            return ['ok' => false, 'error' => $message !== '' ? $message : 'Database connection failed.'];
        }

        $cfg = function_exists('fc_db_resolve_config') ? fc_db_resolve_config() : [];

        return [
            'ok' => true,
            'pdo' => $conn['pdo'],
            'prefix' => (string) ($conn['prefix'] ?? 'wp_'),
            'database' => (string) ($cfg['database'] ?? ''),
        ];
    }

    /**
     * @param array{database?:string} $db
     */
    private static function storeLabel(string $source, array $db): string
    {
        $database = trim((string) ($db['database'] ?? ''));
        return $database !== ''
            ? $database . ' (' . $source . ')'
            : 'WordPress database (' . $source . ')';
    }

    private static function countProducts(PDO $pdo, string $prefix): int
    {
        $posts = $prefix . 'posts';
        $typePlaceholders = implode(',', array_fill(0, count(self::POST_TYPES), '?'));
        $statusPlaceholders = implode(',', array_fill(0, count(self::STATUSES), '?'));
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM `{$posts}`
             WHERE post_type IN ({$typePlaceholders})
               AND post_status IN ({$statusPlaceholders})"
        );
        $stmt->execute(array_merge(self::POST_TYPES, self::STATUSES));
        return max(0, (int) $stmt->fetchColumn());
    }

    /**
     * @return array<string, mixed>
     */
    private static function fetchProductsPage(
        PDO $pdo,
        string $prefix,
        string $source,
        int $afterId,
        int $limit
    ): array {
        $posts = $prefix . 'posts';
        $postmeta = $prefix . 'postmeta';
        $typePlaceholders = implode(',', array_fill(0, count(self::POST_TYPES), '?'));
        $statusPlaceholders = implode(',', array_fill(0, count(self::STATUSES), '?'));

        try {
            $stmt = $pdo->prepare(
                "SELECT p.ID, p.post_title, p.post_type, p.post_parent
                 FROM `{$posts}` p
                 WHERE p.post_type IN ({$typePlaceholders})
                   AND p.post_status IN ({$statusPlaceholders})
                   AND p.ID > ?
                 ORDER BY p.ID ASC
                 LIMIT {$limit}"
            );
            $stmt->execute(array_merge(self::POST_TYPES, self::STATUSES, [$afterId]));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return self::error('Database query failed while loading products.');
        }

        if ($rows === []) {
            return ['ok' => true, 'products' => []];
        }

        $ids = array_map(static fn(array $row): int => (int) ($row['ID'] ?? 0), $rows);
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
        $metaByPost = self::loadProductMeta($pdo, $postmeta, $ids, [
            '_sku',
            '_thumbnail_id',
            '_product_image_gallery',
        ]);

        // Variations need their parent's title (name fallback) and image meta (image fallback).
        $variationParents = [];
        foreach ($rows as $row) {
            if (($row['post_type'] ?? '') !== 'product_variation') {
                continue;
            }
            $vid = (int) ($row['ID'] ?? 0);
            $pid = (int) ($row['post_parent'] ?? 0);
            if ($vid > 0 && $pid > 0) {
                $variationParents[$vid] = $pid;
            }
        }
        $parentIds = array_values(array_unique(array_values($variationParents)));
        $parentTitles = self::loadPostTitles($pdo, $posts, $parentIds);
        $parentMeta = self::loadProductMeta($pdo, $postmeta, $parentIds, [
            '_thumbnail_id',
            '_product_image_gallery',
        ]);
        $variationAttrs = self::fetchVariationAttributes($pdo, $postmeta, array_keys($variationParents));

        $attachmentIds = [];
        foreach ($ids as $id) {
            self::collectAttachmentIds($metaByPost[$id] ?? [], $attachmentIds);
        }
        foreach ($parentIds as $pid) {
            self::collectAttachmentIds($parentMeta[$pid] ?? [], $attachmentIds);
        }

        $urls = self::resolveAttachmentUrls($pdo, $prefix, $source, array_keys($attachmentIds));
        $products = [];
        foreach ($rows as $row) {
            $id = (int) ($row['ID'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $meta = $metaByPost[$id] ?? [];

            if (($row['post_type'] ?? '') !== 'product_variation') {
                $products[] = [
                    'id' => $id,
                    'sku' => (string) ($meta['_sku'] ?? ''),
                    'name' => (string) ($row['post_title'] ?? ''),
                    'images' => implode(', ', self::resolveImageList($meta, $urls)),
                ];
                continue;
            }

            $parentId = $variationParents[$id] ?? 0;
            $parentTitle = trim((string) ($parentTitles[$parentId] ?? ''));

            $attrs = $variationAttrs[$id] ?? [];
            ksort($attrs);
            $suffixParts = [];
            foreach ($attrs as $value) {
                $humanized = self::humanizeAttributeValue($value);
                if ($humanized !== '') {
                    $suffixParts[] = $humanized;
                }
            }
            $suffix = implode(' / ', $suffixParts);

            if ($parentTitle !== '') {
                $name = $suffix !== '' ? $parentTitle . " \u{2013} " . $suffix : $parentTitle;
            } else {
                // Orphaned variation (parent missing/deleted): fall back to the variation's
                // own post_title rather than dropping the row — finalize() requires the row
                // count to exactly match countProducts(), so every counted row must be emitted.
                $name = (string) ($row['post_title'] ?? '');
            }

            $imageList = self::resolveImageList($meta, $urls);
            if ($imageList === [] && $parentId > 0) {
                $imageList = self::resolveImageList($parentMeta[$parentId] ?? [], $urls);
            }

            $products[] = [
                'id' => $id,
                'sku' => (string) ($meta['_sku'] ?? ''),
                'name' => $name,
                'images' => implode(', ', $imageList),
            ];
        }

        return ['ok' => true, 'products' => $products];
    }

    /**
     * @param list<int> $postIds
     * @param list<string> $keys
     * @return array<int, array<string, string>>
     */
    private static function loadProductMeta(PDO $pdo, string $postmeta, array $postIds, array $keys): array
    {
        if ($postIds === [] || $keys === []) {
            return [];
        }

        $idPlaceholders = implode(',', array_fill(0, count($postIds), '?'));
        $keyPlaceholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare(
            "SELECT post_id, meta_key, meta_value
             FROM `{$postmeta}`
             WHERE post_id IN ({$idPlaceholders})
               AND meta_key IN ({$keyPlaceholders})"
        );
        $stmt->execute(array_merge($postIds, $keys));

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) ($row['post_id'] ?? 0);
            $key = (string) ($row['meta_key'] ?? '');
            if ($id <= 0 || $key === '') {
                continue;
            }
            $out[$id][$key] = (string) ($row['meta_value'] ?? '');
        }

        return $out;
    }

    /**
     * Batched wp_posts.post_title lookup — used for a variation's parent-name fallback.
     * No post_status/post_type filter: a parent that exists but was excluded by our own
     * filters (e.g. trashed) is still valid fallback data; only a truly deleted parent
     * (no row at all) falls through to the variation's own post_title.
     *
     * @param list<int> $postIds
     * @return array<int, string>
     */
    private static function loadPostTitles(PDO $pdo, string $posts, array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter(
            array_map('intval', $postIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($postIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT ID, post_title FROM `{$posts}` WHERE ID IN ({$placeholders})"
        );
        $stmt->execute($postIds);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) ($row['ID'] ?? 0);
            if ($id > 0) {
                $out[$id] = (string) ($row['post_title'] ?? '');
            }
        }

        return $out;
    }

    /**
     * Batched variation attribute postmeta lookup (dynamic key names, e.g. attribute_pa_color,
     * attribute_size — unknown in advance per product, hence LIKE rather than an exact IN list).
     *
     * @param list<int> $variationIds
     * @return array<int, array<string, string>>
     */
    private static function fetchVariationAttributes(PDO $pdo, string $postmeta, array $variationIds): array
    {
        $variationIds = array_values(array_unique(array_filter(
            array_map('intval', $variationIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($variationIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($variationIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT post_id, meta_key, meta_value
             FROM `{$postmeta}`
             WHERE post_id IN ({$placeholders})
               AND meta_key LIKE 'attribute_%'"
        );
        $stmt->execute($variationIds);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) ($row['post_id'] ?? 0);
            $key = (string) ($row['meta_key'] ?? '');
            $value = trim((string) ($row['meta_value'] ?? ''));
            if ($id > 0 && $key !== '' && $value !== '') {
                $out[$id][$key] = $value;
            }
        }

        return $out;
    }

    /**
     * Turns a raw attribute value (often a taxonomy term slug, e.g. "matte-black") into a
     * readable label ("Matte Black"). Not multibyte-aware — a leading non-ASCII character
     * won't be capitalized, which is a cosmetic no-op, not data corruption.
     */
    private static function humanizeAttributeValue(string $value): string
    {
        $value = trim($value);
        return $value === '' ? '' : ucwords(str_replace(['-', '_'], ' ', $value));
    }

    /**
     * @param array<string, string> $meta
     * @param array<int, bool> $attachmentIds
     */
    private static function collectAttachmentIds(array $meta, array &$attachmentIds): void
    {
        $thumbId = (int) ($meta['_thumbnail_id'] ?? 0);
        if ($thumbId > 0) {
            $attachmentIds[$thumbId] = true;
        }
        $galleryRaw = trim((string) ($meta['_product_image_gallery'] ?? ''));
        if ($galleryRaw !== '') {
            foreach (preg_split('/\s*,\s*/', $galleryRaw) ?: [] as $gid) {
                $aid = (int) $gid;
                if ($aid > 0) {
                    $attachmentIds[$aid] = true;
                }
            }
        }
    }

    /**
     * @param array<string, string> $meta
     * @param array<int, string> $urls
     * @return list<string>
     */
    private static function resolveImageList(array $meta, array $urls): array
    {
        $imageList = [];
        $thumbId = (int) ($meta['_thumbnail_id'] ?? 0);
        if ($thumbId > 0 && isset($urls[$thumbId]) && $urls[$thumbId] !== '') {
            $imageList[] = $urls[$thumbId];
        }
        $galleryRaw = trim((string) ($meta['_product_image_gallery'] ?? ''));
        if ($galleryRaw !== '') {
            foreach (preg_split('/\s*,\s*/', $galleryRaw) ?: [] as $gid) {
                $aid = (int) $gid;
                if ($aid > 0 && isset($urls[$aid]) && $urls[$aid] !== '' && !in_array($urls[$aid], $imageList, true)) {
                    $imageList[] = $urls[$aid];
                }
            }
        }
        return $imageList;
    }

    /**
     * @param list<int> $attachmentIds
     * @return array<int, string>
     */
    private static function resolveAttachmentUrls(
        PDO $pdo,
        string $prefix,
        string $source,
        array $attachmentIds
    ): array {
        $attachmentIds = array_values(array_unique(array_filter(array_map('intval', $attachmentIds))));
        if ($attachmentIds === []) {
            return [];
        }

        $uploadsBase = self::uploadsBaseUrl($pdo, $prefix, $source);
        $postmeta = $prefix . 'postmeta';
        $placeholders = implode(',', array_fill(0, count($attachmentIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT post_id, meta_value
             FROM `{$postmeta}`
             WHERE meta_key = '_wp_attached_file'
               AND post_id IN ({$placeholders})"
        );
        $stmt->execute($attachmentIds);
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) ($row['post_id'] ?? 0);
            $file = trim((string) ($row['meta_value'] ?? ''));
            if ($id <= 0 || $file === '') {
                continue;
            }
            if (preg_match('#^https?://#i', $file)) {
                $map[$id] = self::rebaseMediaUrl($file, $uploadsBase);
                continue;
            }
            $map[$id] = rtrim($uploadsBase, '/') . '/' . ltrim($file, '/');
        }

        return $map;
    }

    /**
     * Point absolute media URLs at the connected store's uploads base (keeps path/query).
     */
    private static function rebaseMediaUrl(string $url, string $uploadsBase): string
    {
        $url = trim($url);
        $uploadsBase = rtrim($uploadsBase, '/');
        if ($url === '' || $uploadsBase === '') {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return $url;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $suffix = '';
        if (is_string($query) && $query !== '') {
            $suffix .= '?' . $query;
        }
        if (is_string($fragment) && $fragment !== '') {
            $suffix .= '#' . $fragment;
        }

        if (preg_match('#(/wp-content/uploads/.*)$#i', $path, $match)) {
            $origin = preg_replace('#/wp-content/uploads/?$#i', '', $uploadsBase);

            return rtrim((string) $origin, '/') . $match[1] . $suffix;
        }

        return $uploadsBase . '/' . ltrim($path, '/') . $suffix;
    }

    private static function uploadsBaseUrl(PDO $pdo, string $prefix, string $source): string
    {
        $options = $prefix . 'options';
        $custom = '';
        $home = '';
        try {
            $stmt = $pdo->prepare(
                "SELECT option_name, option_value
                 FROM `{$options}`
                 WHERE option_name IN ('upload_url_path', 'home', 'siteurl')"
            );
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $name = (string) ($row['option_name'] ?? '');
                $value = rtrim((string) ($row['option_value'] ?? ''), '/');
                if ($name === 'upload_url_path' && $value !== '') {
                    $custom = $value;
                } elseif (($name === 'home' || $name === 'siteurl') && $home === '' && $value !== '') {
                    $home = $value;
                }
            }
        } catch (Throwable $e) {
            // Fall through to configured host.
        }

        if ($custom !== '') {
            return $custom;
        }

        // Always honour the connected WP home/siteurl (including localhost).
        if ($home !== '') {
            return $home . '/wp-content/uploads';
        }

        $override = rtrim((string) (self::IMAGE_HOSTS[$source] ?? ''), '/');

        return ($override !== '' ? $override : 'https://fencesperth.com') . '/wp-content/uploads';
    }

    /**
     * @return array<string, mixed>
     */
    private static function finalize(string $source, int $expectedRows, int $storeTotal): array
    {
        $working = self::workingPath($source);
        $final = self::finalPath($source);
        if (!is_readable($working)) {
            return self::error('The downloading CSV file is missing.');
        }

        $handle = fopen($working, 'rb');
        if ($handle === false) {
            return self::error('Unable to validate the downloading CSV file.');
        }
        $header = fgetcsv($handle);
        $rows = 0;
        $productIds = [];
        $hasInvalidId = false;
        $hasDuplicateId = false;
        while (($row = fgetcsv($handle)) !== false) {
            // Skip broken/empty lines from a bad write.
            if (!is_array($row) || $row === [null] || $row === false) {
                continue;
            }
            $productId = trim((string) ($row[0] ?? ''));
            if ($productId === '' || !ctype_digit($productId)) {
                // Ignore malformed trailing fragments.
                continue;
            }
            $rows++;
            if (isset($productIds[$productId])) {
                $hasDuplicateId = true;
                continue;
            }
            $productIds[$productId] = true;
        }
        fclose($handle);

        if (
            $header !== ['ID', 'SKU', 'Name', 'Images']
            || $rows !== $expectedRows
            || ($storeTotal > 0 && $rows !== $storeTotal)
            || $hasInvalidId
            || $hasDuplicateId
            || $rows < 1
        ) {
            return self::error(
                'CSV validation failed before replacement. Expected '
                . $storeTotal
                . ' products, found '
                . $rows
                . '.'
            );
        }

        $backup = $final . '.backup';
        @unlink($backup);
        $hadFinal = is_file($final);
        if ($hadFinal && !@rename($final, $backup)) {
            return self::error('Unable to prepare the existing products CSV for replacement.');
        }
        if (!@rename($working, $final)) {
            if ($hadFinal && is_file($backup)) {
                @rename($backup, $final);
            }
            return self::error('Unable to replace the products CSV file.');
        }
        @unlink($backup);
        if (!function_exists('fc_storage_cache_dir')) {
            require_once FC_ROOT . '/config/storage.php';
        }
        @unlink(
            fc_storage_cache_dir('products')
            . DIRECTORY_SEPARATOR . 'wc-products-' . $source . '-count.json'
        );
        if (!function_exists('fc_wc_products_sku_index_invalidate')) {
            require_once FC_ROOT . '/config/wc_products_sku_index.php';
        }
        fc_wc_products_sku_index_invalidate($source);

        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private static function failState(string $source, array $state, string $message): array
    {
        $state['status'] = 'failed';
        $state['error'] = $message;
        $state['message'] = $message;
        $state['updatedAt'] = gmdate('c');
        self::writeState($source, $state);
        return self::error($message, $state);
    }

    /**
     * @param array<string, mixed>|null $state
     * @return array<string, mixed>
     */
    private static function error(string $message, ?array $state = null): array
    {
        $result = ['ok' => false, 'error' => $message];
        if ($state !== null) {
            $result['job'] = self::publicState($state);
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private static function publicState(array $state): array
    {
        $started = isset($state['startedAt']) ? strtotime((string) $state['startedAt']) : false;
        $state['elapsedSeconds'] = $started !== false ? max(0, time() - $started) : 0;
        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private static function withLock(string $source, callable $callback): array
    {
        $lock = @fopen(self::lockPath($source), 'c+');
        if ($lock === false) {
            return self::error('Unable to acquire the product download lock.');
        }
        if (!flock($lock, LOCK_EX)) {
            fclose($lock);
            return self::error('Unable to lock the product download.');
        }

        try {
            $result = $callback();
        } catch (RuntimeException $exception) {
            $result = self::error($exception->getMessage());
        } catch (Throwable $exception) {
            $result = self::error('Unexpected product download error.');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return is_array($result) ? $result : self::error('Invalid product download response.');
    }
}
