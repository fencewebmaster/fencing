<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use PDO;
use RuntimeException;
use Throwable;
use Fc\Admin\Models\SystemProductModel;

/**
 * Exports WooCommerce products from the WordPress MySQL database into
 * writable/wc-products-{GO|JG}.csv (ID,Slug,SKU,Name,Images,Colour,Description).
 *
 * Uses the database rather than the public Store API so private/draft products
 * are included (3000+ rows), matching the full WooCommerce catalogue. Variable
 * product variations are included too, one CSV row per variation SKU — each
 * variation's Name/Images/Colour/Description fall back to its parent product
 * where it has none of its own (see fetchProductsPage()).
 *
 * Colour and Description exist for MissingSkuDeepScan: the colour constrains which
 * catalogue rows may answer a gap, the description gives its title matcher more words
 * to work with. Both were appended to the end of the header — every reader of this file
 * looks columns up by name or keeps a fixed subset, so order stays free.
 */
final class WooCommerceProductExportService
{
    private const SOURCES = ['GO', 'JG'];

    private const PER_PAGE = 100;

    /** Written by start(), re-checked by finalize() — the two must never drift. */
    private const CSV_HEADER = ['ID', 'Slug', 'SKU', 'Name', 'Images', 'Colour', 'Description'];

    /**
     * Enough for the formatted description without turning a 650KB catalogue into a 5MB one.
     * Measured on the live store: median 172 characters, 90th percentile 1,216, four over 4,000.
     */
    private const DESCRIPTION_LIMIT = 4000;

    /** The formatting worth keeping out of a storefront description. */
    private const DESCRIPTION_TAGS = '<p><br><ul><ol><li><strong><b><em><i><u><h3><h4><h5>';

    /** Variation colour attribute meta keys, in the order they are preferred. */
    private const COLOR_ATTRIBUTE_KEYS = [
        'attribute_pa_color',
        'attribute_pa_colour',
        'attribute_color',
        'attribute_colour',
    ];

    /** Product colour taxonomies, for the rows that are not variations. */
    private const COLOR_TAXONOMIES = ['pa_color', 'pa_colour'];

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

            $headerWritten = fputcsv($handle, self::CSV_HEADER);
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

        $result = self::withLock($source, static function () use ($source, $jobId): array {
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
                    (string) ($product['slug'] ?? ''),
                    (string) ($product['sku'] ?? ''),
                    (string) ($product['name'] ?? ''),
                    (string) ($product['images'] ?? ''),
                    (string) ($product['colour'] ?? ''),
                    (string) ($product['description'] ?? ''),
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

        // Outside withLock() on purpose: the lock file's handle is still open (and, on
        // Windows, exclusively so) until withLock() closes it — unlinking it any earlier
        // fails there.
        if (($result['job']['status'] ?? '') === 'complete') {
            self::cleanupJobFiles($source);
        }

        return $result;
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

        $result = self::withLock($source, static function () use ($source, $jobId): array {
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

        // Outside withLock() on purpose — see the same note in step().
        if (($result['job']['status'] ?? '') === 'cancelled') {
            self::cleanupJobFiles($source);
        }

        return $result;
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

        // Deliberately lock-free: a plain status read shouldn't create the .lock file —
        // that should only appear once a download actually starts (see withLock()).
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
    }

    private static function normalizeSource(string $source): string
    {
        $source = strtoupper(trim($source));
        return in_array($source, self::SOURCES, true) ? $source : '';
    }

    private static function dataPath(string $filename): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . $filename;
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
     * Remove the job state/lock files once a download reaches a terminal "done" state
     * (complete or cancelled) — nothing left to poll or resume, so writable/ shouldn't
     * keep them around. Failed jobs are left in place for diagnostics.
     */
    private static function cleanupJobFiles(string $source): void
    {
        @unlink(self::statePath($source));
        @unlink(self::lockPath($source));
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
        $conn = DatabaseConfigService::pdo();
        if (!($conn['pdo'] instanceof PDO)) {
            $message = DatabaseConfigService::connectErrorMessage((string) ($conn['error'] ?? ''));
            return ['ok' => false, 'error' => $message !== '' ? $message : 'Database connection failed.'];
        }

        $cfg = DatabaseConfigService::resolveConfig();

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
                "SELECT p.ID, p.post_title, p.post_name, p.post_type, p.post_parent,
                        p.post_content, p.post_excerpt
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
        $parentPosts = self::loadPostTitlesAndSlugs($pdo, $posts, $parentIds);
        $parentMeta = self::loadProductMeta($pdo, $postmeta, $parentIds, [
            '_thumbnail_id',
            '_product_image_gallery',
        ]);
        $variationAttrs = self::fetchVariationAttributes($pdo, $postmeta, array_keys($variationParents));

        // A variation carries its colour in postmeta; a plain product carries it as a taxonomy
        // term, so both halves of the catalogue need asking separately.
        $termColors = self::fetchColorTerms($pdo, $prefix, array_merge($ids, $parentIds));

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
                    'slug' => (string) ($row['post_name'] ?? ''),
                    'sku' => (string) ($meta['_sku'] ?? ''),
                    'name' => (string) ($row['post_title'] ?? ''),
                    'images' => implode(', ', self::resolveImageList($meta, $urls)),
                    'colour' => $termColors[$id] ?? '',
                    'description' => self::richText(
                        (string) ($row['post_content'] ?? ''),
                        (string) ($row['post_excerpt'] ?? '')
                    ),
                ];
                continue;
            }

            $parentId = $variationParents[$id] ?? 0;
            $parentTitle = trim((string) ($parentPosts[$parentId]['title'] ?? ''));
            $parentSlug = trim((string) ($parentPosts[$parentId]['slug'] ?? ''));

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

            // Same orphaned-parent fallback as $name above: use the variation's own slug.
            $slug = $parentSlug !== '' ? $parentSlug : (string) ($row['post_name'] ?? '');

            $imageList = self::resolveImageList($meta, $urls);
            if ($imageList === [] && $parentId > 0) {
                $imageList = self::resolveImageList($parentMeta[$parentId] ?? [], $urls);
            }

            // A variation's own colour attribute is the precise one; the parent's colour terms
            // only stand in when the variation does not vary by colour at all.
            $colour = self::colourFromAttributes($attrs);
            if ($colour === '') {
                $colour = $termColors[$parentId] ?? '';
            }

            // Variations are nearly always described by their parent, so try it first here.
            $description = self::richText(
                (string) ($row['post_content'] ?? ''),
                (string) ($row['post_excerpt'] ?? '')
            );
            if ($description === '' && $parentId > 0) {
                $description = self::richText(
                    (string) ($parentPosts[$parentId]['content'] ?? ''),
                    (string) ($parentPosts[$parentId]['excerpt'] ?? '')
                );
            }

            $products[] = [
                'id' => $id,
                'slug' => $slug,
                'sku' => (string) ($meta['_sku'] ?? ''),
                'name' => $name,
                'images' => implode(', ', $imageList),
                'colour' => $colour,
                'description' => $description,
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
     * Batched wp_posts lookup — used for a variation's parent-name, parent-slug and
     * parent-description fallback. No post_status/post_type filter: a parent that exists but was
     * excluded by our own filters (e.g. trashed) is still valid fallback data; only a truly
     * deleted parent (no row at all) falls through to the variation's own post_title/post_name.
     *
     * @param list<int> $postIds
     * @return array<int, array{title:string,slug:string,content:string,excerpt:string}>
     */
    private static function loadPostTitlesAndSlugs(PDO $pdo, string $posts, array $postIds): array
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
            "SELECT ID, post_title, post_name, post_content, post_excerpt
             FROM `{$posts}` WHERE ID IN ({$placeholders})"
        );
        $stmt->execute($postIds);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) ($row['ID'] ?? 0);
            if ($id > 0) {
                $out[$id] = [
                    'title' => (string) ($row['post_title'] ?? ''),
                    'slug' => (string) ($row['post_name'] ?? ''),
                    'content' => (string) ($row['post_content'] ?? ''),
                    'excerpt' => (string) ($row['post_excerpt'] ?? ''),
                ];
            }
        }

        return $out;
    }

    /**
     * Batched colour-taxonomy lookup for the rows that are not variations. A product with
     * several colour terms keeps them all, comma separated — MissingSkuDeepScan reads the cell
     * as a set, so "Black, White" is a product that answers either gap.
     *
     * @param list<int> $postIds
     * @return array<int, string>
     */
    private static function fetchColorTerms(PDO $pdo, string $prefix, array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter(
            array_map('intval', $postIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($postIds === []) {
            return [];
        }

        $relationships = $prefix . 'term_relationships';
        $taxonomy = $prefix . 'term_taxonomy';
        $terms = $prefix . 'terms';
        $idPlaceholders = implode(',', array_fill(0, count($postIds), '?'));
        $taxPlaceholders = implode(',', array_fill(0, count(self::COLOR_TAXONOMIES), '?'));

        try {
            $stmt = $pdo->prepare(
                "SELECT tr.object_id, t.name
                 FROM `{$relationships}` tr
                 INNER JOIN `{$taxonomy}` tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                 INNER JOIN `{$terms}` t ON t.term_id = tt.term_id
                 WHERE tr.object_id IN ({$idPlaceholders})
                   AND tt.taxonomy IN ({$taxPlaceholders})"
            );
            $stmt->execute(array_merge($postIds, self::COLOR_TAXONOMIES));
        } catch (Throwable $e) {
            // A store with no colour taxonomy is not an export failure — the column stays empty
            // and the deep scan falls back to reading the colour out of the SKU.
            return [];
        }

        $byPost = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) ($row['object_id'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));
            if ($id > 0 && $name !== '') {
                $byPost[$id][$name] = true;
            }
        }

        $out = [];
        foreach ($byPost as $id => $names) {
            $out[$id] = implode(', ', array_keys($names));
        }

        return $out;
    }

    /**
     * The colour out of a variation's attribute meta. Falls back to nothing rather than guessing
     * from another attribute — a size or a length in the colour column would reject every gap.
     *
     * @param array<string, string> $attrs
     */
    private static function colourFromAttributes(array $attrs): string
    {
        foreach (self::COLOR_ATTRIBUTE_KEYS as $key) {
            $value = self::humanizeAttributeValue((string) ($attrs[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * A WooCommerce description, kept as formatted markup rather than flattened to one line.
     *
     * Three things in the source carry the formatting and all three have to survive:
     *  - <strong> around each bullet's lead-in;
     *  - underlines, written as <span style="text-decoration: underline"> — style attributes are
     *    dropped here, so the one that means something is promoted to <u> before that happens;
     *  - the line breaks, which are plain newlines in post_content because WordPress runs
     *    wpautop() on the way to the storefront. Nothing runs wpautop here, so they become <br>.
     *
     * Entities are deliberately NOT decoded: "&amp;" is already correct markup, and decoding
     * would turn an escaped "&lt;script&gt;" in someone's copy into a live tag.
     */
    private static function richText(string $content, string $fallback = ''): string
    {
        foreach ([$content, $fallback] as $raw) {
            $text = (string) $raw;
            $text = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $text);
            $text = (string) preg_replace('/<!--.*?-->/s', ' ', $text);
            $text = (string) preg_replace('/\[[^\]]*\]/', ' ', $text);
            $text = (string) preg_replace(
                '#<span[^>]*text-decoration\s*:\s*underline[^>]*>(.*?)</span>#is',
                '<u>$1</u>',
                $text
            );
            $text = strip_tags($text, self::DESCRIPTION_TAGS);
            // No surviving tag needs an attribute, and dropping them all is cheaper to be sure of
            // than deciding which are safe.
            $text = (string) preg_replace('/<([a-z0-9]+)\s[^>]*?(\/?)>/i', '<$1$2>', $text);

            // Runs of spaces go, newlines stay: they are the structure.
            $text = str_replace(["\r\n", "\r"], "\n", $text);
            $text = (string) preg_replace('/[ \t]+/', ' ', $text);
            $text = (string) preg_replace('/ *\n */', "\n", $text);
            $text = trim($text);
            $text = (string) preg_replace('/\n{2,}/', '<br><br>', $text);
            $text = str_replace("\n", '<br>', $text);
            $text = (string) preg_replace('#(<br>\s*){3,}#i', '<br><br>', $text);

            if (trim(strip_tags($text)) === '') {
                continue;
            }
            // Cutting markup at a character count leaves tags hanging open, so anything this long
            // falls back to its words.
            if (mb_strlen($text, 'UTF-8') > self::DESCRIPTION_LIMIT) {
                return self::plainText($raw);
            }

            return $text;
        }

        return '';
    }

    /**
     * The words alone, for a description too long to keep as markup.
     */
    private static function plainText(string $content): string
    {
        $text = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $content);
        $text = (string) preg_replace('/<!--.*?-->/s', ' ', $text);
        $text = (string) preg_replace('/\[[^\]]*\]/', ' ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text, 'UTF-8') > self::DESCRIPTION_LIMIT
            ? rtrim(mb_substr($text, 0, self::DESCRIPTION_LIMIT, 'UTF-8'))
            : $text;
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
            $header !== self::CSV_HEADER
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
        SystemProductModel::invalidateCache($source);
        WcProductSkuIndex::invalidate($source);

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
