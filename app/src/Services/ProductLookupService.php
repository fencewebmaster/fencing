<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\ArrayHelper;
use Fc\Admin\Helpers\ColorHelper;
use Fc\Admin\Helpers\StringHelper;

/**
 * FC Product Lookup — PDO catalog helpers (no WordPress bootstrap).
 * Server-rendered only (no JSON API).
 */
final class ProductLookupService
{
    /**
     * Bootstrap PDO connection to WooCommerce MySQL tables (never loads wp-load.php).
     *
     * @return array{ok:bool,error?:string}
     */
    public static function bootstrap(): array
    {
        $boot = ProductLookupQueryService::boot();
        if (empty($boot['ok'])) {
            return [
                'ok' => false,
                'error' => (string) ($boot['error'] ?? 'Database unavailable.'),
            ];
        }

        return ['ok' => true];
    }

    /**
     * File cache directory for lookup payloads.
     */
    public static function cacheDir(): string
    {
        return CacheStorageService::cacheDir('lookup');
    }

    /**
     * @return mixed|null
     */
    public static function cacheGet(string $key, int $ttl)
    {
        if (function_exists('apcu_fetch')) {
            $ok = false;
            $val = apcu_fetch('fc_lookup_' . $key, $ok);
            if ($ok) {
                return $val;
            }
        }


        $file = self::cacheDir() . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key) . '.json';
        if (!is_file($file)) {
            return null;
        }
        if ((filemtime($file) ?: 0) + $ttl < time()) {
            @unlink($file);

            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);

        return is_array($data) && array_key_exists('payload', $data) ? $data['payload'] : null;
    }

    /**
     * @param mixed $payload
     */
    public static function cacheSet(string $key, $payload, int $ttl): void
    {
        if (function_exists('apcu_store')) {
            @apcu_store('fc_lookup_' . $key, $payload, $ttl);
        }

        $file = self::cacheDir() . '/' . preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $key) . '.json';
        @file_put_contents(
            $file,
            json_encode(['saved' => time(), 'payload' => $payload]),
            LOCK_EX
        );
    }

    /**
     * WooCommerce product meta lookup table name if present.
     */
    public static function metaLookupTable(): ?string
    {
        static $table = null;
        if ($table !== null) {
            return $table !== '' ? $table : null;
        }

        $candidate = ProductLookupQueryService::table('wc_product_meta_lookup');
        $table = ProductLookupQueryService::tableExists($candidate) ? $candidate : '';

        return $table !== '' ? $table : null;
    }

    /**
     * Base path for this page (e.g. /wp/fencing/fc/lookup).
     */
    public static function basePath(): string
    {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/lookup.php');
        $path = preg_replace('/\.php$/i', '', $script);

        return $path !== null && $path !== '' ? $path : '/lookup';
    }

    /**
     * Build a lookup URL preserving filters, with optional overrides.
     * Quick view uses a pretty path: /lookup/view/{slug}
     *
     * @param array<string, mixed> $request Current sanitized request
     * @param array<string, mixed> $overrides Keys to replace; use null to remove
     */
    public static function url(array $request, array $overrides = []): string
    {
        $params = array_merge($request, $overrides);

        $viewSlug = '';
        if (array_key_exists('view', $params)) {
            $viewVal = $params['view'];
            unset($params['view']);
            if ($viewVal !== null && $viewVal !== '' && $viewVal !== false && $viewVal !== 0 && $viewVal !== '0') {
                $viewSlug = StringHelper::slug((string) $viewVal);
            }
        }

        foreach ($params as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
                continue;
            }
            if ($value === '' || $value === [] || $value === false) {
                unset($params[$key]);
                continue;
            }
            if ($key === 'page' && (int) $value <= 1) {
                unset($params[$key]);
                continue;
            }
            if ($key === 'per_page') {
                $defaultPerPage = (int) (CatalogSettings::get()['resultsPerPage'] ?? 12);
                if ((int) $value === $defaultPerPage) {
                    unset($params[$key]);
                    continue;
                }
            }
            if ($key === 'orderby') {
                $orderbyValue = (string) $value;
                $defaultOrderby = (string) (CatalogSettings::get()['defaultOrderby'] ?? 'default');
                if ($orderbyValue === 'default' || $orderbyValue === $defaultOrderby) {
                    unset($params[$key]);
                    continue;
                }
            }
            if ($key === 'layout' && (string) $value === 'grid') {
                unset($params[$key]);
                continue;
            }
            if ($key === 'featured' && !(int) $value) {
                unset($params[$key]);
                continue;
            }
        }

        // Drop internal non-query keys
        unset($params['_raw']);

        $query = http_build_query($params);
        $base = self::basePath();
        if ($viewSlug !== '') {
            $base .= '/view/' . rawurlencode($viewSlug);
        }

        return $query !== '' ? $base . '?' . $query : $base;
    }

    /**
     * Prepare product description HTML for safe, readable display (no WP runtime).
     */
    public static function formatDescriptionHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // Drop Gutenberg block comments and classic <!--more--> markers.
        $html = preg_replace('/<!--\s*wp:[\s\S]*?-->/i', '', $html) ?? $html;
        $html = preg_replace('/<!--\s*\/wp:[\s\S]*?-->/i', '', $html) ?? $html;
        $html = preg_replace('/<!--more(.*?)?-->/s', '', $html) ?? $html;

        // Decode entities once so nested markup from WC/CMS exports renders.
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Allow common description markup only.
        $allowed = '<p><br><br/><strong><b><em><i><u><s><strike><a><ul><ol><li>'
            . '<h1><h2><h3><h4><h5><h6><blockquote><pre><code><hr><hr/>'
            . '<figure><figcaption><img><span><div><table><thead><tbody><tfoot><tr><th><td>';
        $html = strip_tags($html, $allowed);

        // Classic editor plain text → paragraphs.
        if ($html !== '' && !preg_match('/<(p|div|h[1-6]|ul|ol|table|blockquote|figure)\b/i', $html)) {
            $parts = preg_split("/\n\s*\n/", $html) ?: [];
            $blocks = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $blocks[] = '<p>' . nl2br($part, false) . '</p>';
            }
            $html = $blocks !== [] ? implode("\n", $blocks) : '<p>' . nl2br($html, false) . '</p>';
        }

        // Normalize empty paragraphs / leftover whitespace-only nodes.
        $html = preg_replace('/<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $html) ?? $html;
        $html = preg_replace("/[ \t]+\n/", "\n", $html) ?? $html;

        return trim($html);
    }

    /**
     * Resolve quick-view target (slug or legacy numeric ID) to a product ID.
     *
     * @param mixed $view
     */
    public static function resolveViewId($view): int
    {
        if ($view === null || $view === '' || $view === false) {
            return 0;
        }

        if (is_int($view) || (is_string($view) && ctype_digit($view))) {
            return max(0, (int) $view);
        }

        $slug = StringHelper::slug((string) $view);
        if ($slug === '') {
            return 0;
        }

        // Pure numeric slug still treated as legacy ID for ?view=11440 /view/11440
        if (ctype_digit($slug)) {
            return max(0, (int) $slug);
        }

        return ProductLookupQueryService::productIdBySlug($slug);
    }

    /**
     * Parse and sanitize $_GET into a typed request.
     *
     * @return array<string, mixed>
     */
    public static function parseRequest(array $get): array
    {
        $catalog = CatalogSettings::get();

        // Pretty path /lookup/view/{slug} may arrive as PATH_INFO when rewrite maps to lookup.php
        if (!isset($get['view']) || trim((string) $get['view']) === '') {
            $pathInfo = (string) ($_SERVER['PATH_INFO'] ?? '');
            if (preg_match('#(^|/)view/([^/]+)/?$#', $pathInfo, $m)) {
                $get['view'] = rawurldecode((string) $m[2]);
            } else {
                $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
                $pathOnly = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
                if (preg_match('#/lookup/view/([^/]+)/?$#', $pathOnly, $m)) {
                    $get['view'] = rawurldecode((string) $m[1]);
                }
            }
        }

        $defaultPerPage = CatalogSettings::clampResultsPerPage($catalog['resultsPerPage'] ?? 12);
        $perPageAllowed = CatalogSettings::resultsPerPageChoices($defaultPerPage);
        $perPage = array_key_exists('per_page', $get) ? (int) $get['per_page'] : $defaultPerPage;
        if (!in_array($perPage, $perPageAllowed, true)) {
            $perPage = $defaultPerPage;
        }

        $orderbyAllowed = array_keys(CatalogSettings::orderbyChoices());
        $defaultOrderby = (string) ($catalog['defaultOrderby'] ?? 'default');
        if (!in_array($defaultOrderby, $orderbyAllowed, true)) {
            $defaultOrderby = 'default';
        }
        $orderby = (string) ($get['orderby'] ?? $defaultOrderby);
        if (!in_array($orderby, $orderbyAllowed, true)) {
            $orderby = $defaultOrderby;
        }

        $layout = strtolower((string) ($get['layout'] ?? 'grid')) === 'list' ? 'list' : 'grid';

        $sale = (string) ($get['sale'] ?? '');
        if (!in_array($sale, ['on', 'regular', ''], true)) {
            $sale = '';
        }

        $stockAllowed = ['instock', 'outofstock', 'onbackorder'];
        $stock = [];
        foreach (ArrayHelper::toStringList($get['stock'] ?? []) as $s) {
            if (in_array($s, $stockAllowed, true)) {
                $stock[] = $s;
            }
        }

        $attrs = [];
        $attrRaw = $get['attr'] ?? [];
        if (is_array($attrRaw)) {
            foreach ($attrRaw as $slug => $terms) {
                $slug = StringHelper::slug((string) $slug);
                if ($slug === '') {
                    continue;
                }
                $ids = ArrayHelper::toIntList($terms);
                if ($ids !== []) {
                    $attrs[$slug] = $ids;
                }
            }
        }

        $minPrice = isset($get['min_price']) && $get['min_price'] !== '' ? (float) $get['min_price'] : null;
        $maxPrice = isset($get['max_price']) && $get['max_price'] !== '' ? (float) $get['max_price'] : null;
        if ($minPrice !== null && $minPrice < 0) {
            $minPrice = 0.0;
        }
        if ($maxPrice !== null && $maxPrice < 0) {
            $maxPrice = 0.0;
        }

        return [
            'q' => trim((string) ($get['q'] ?? '')),
            'cat' => ArrayHelper::toIntList($get['cat'] ?? []),
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'color' => ArrayHelper::toIntList($get['color'] ?? []),
            'attr' => $attrs,
            'stock' => $stock,
            'sale' => $sale,
            'featured' => !empty($get['featured']) ? 1 : 0,
            'tag' => ArrayHelper::toIntList($get['tag'] ?? []),
            'orderby' => $orderby,
            'per_page' => $perPage,
            'page' => max(1, (int) ($get['page'] ?? 1)),
            'layout' => $layout,
            // Product slug for pretty URLs (/lookup/view/{slug}); legacy numeric IDs still accepted.
            'view' => StringHelper::slug((string) ($get['view'] ?? '')),
        ];
    }

    /**
     * Detect color attribute taxonomy (pa_color / pa_colour first match).
     */
    public static function colorTaxonomy(): ?string
    {
        return ProductLookupQueryService::colorTaxonomy();
    }

    /**
     * @return list<array{name:string,label:string,taxonomy:string}>
     */
    public static function attributeDefinitions(): array
    {
        $colorTax = self::colorTaxonomy();
        $out = [];
        foreach (ProductLookupQueryService::attributeTaxonomies() as $tax) {
            $name = (string) ($tax['attribute_name'] ?? '');
            if ($name === '') {
                continue;
            }
            $taxonomy = 'pa_' . $name;
            if ($colorTax && $taxonomy === $colorTax) {
                continue;
            }
            $label = (string) ($tax['attribute_label'] ?? $name);
            $out[] = [
                'name' => $name,
                'label' => $label !== '' ? $label : $name,
                'taxonomy' => $taxonomy,
            ];
        }

        return $out;
    }

    /**
     * Cached filter facets.
     *
     * @return array<string, mixed>
     */
    public static function getFacets(): array
    {
        // v7: decode HTML entities in term/attribute labels (e.g. &amp; → &).
        $cacheKey = 'facets_v7';
        $cached = self::cacheGet($cacheKey, 3600);
        if (is_array($cached)) {
            return self::applyCatalogToFacets($cached);
        }

        $facets = [
            'categories' => self::buildCategoryTree(),
            'price' => self::priceBounds(),
            'color' => self::buildColorFacet(),
            'attributes' => [],
            'tags' => self::buildTagFacet(),
            'stock' => [
                ['value' => 'instock', 'label' => 'In Stock'],
                ['value' => 'outofstock', 'label' => 'Out of Stock'],
                ['value' => 'onbackorder', 'label' => 'On Backorder'],
            ],
        ];

        foreach (self::attributeDefinitions() as $def) {
            $terms = ProductLookupQueryService::terms($def['taxonomy'], true);
            if ($terms === []) {
                continue;
            }
            $items = [];
            foreach ($terms as $term) {
                $items[] = [
                    'id' => (int) $term['id'],
                    'name' => (string) $term['name'],
                    'slug' => (string) $term['slug'],
                    'count' => (int) $term['count'],
                ];
            }
            $facets['attributes'][] = [
                'name' => $def['name'],
                'label' => $def['label'],
                'taxonomy' => $def['taxonomy'],
                'terms' => $items,
            ];
        }

        self::cacheSet($cacheKey, $facets, 3600);

        return self::applyCatalogToFacets($facets);
    }

    /**
     * Apply admin Catalog Settings (categories, attributes, price bounds) to facets.
     *
     * @param array<string, mixed> $facets
     * @return array<string, mixed>
     */
    public static function applyCatalogToFacets(array $facets): array
    {
        $catalog = CatalogSettings::get();
        $facets['price'] = [
            'min' => (float) $catalog['priceMin'],
            'max' => (float) $catalog['priceMax'],
        ];
        $facets['categories'] = CatalogSettings::filterCategoryTree(
            is_array($facets['categories'] ?? null) ? $facets['categories'] : [],
            is_array($catalog['categoryIds'] ?? null) ? $catalog['categoryIds'] : []
        );
        $facets['attributes'] = CatalogSettings::filterAttributes(
            is_array($facets['attributes'] ?? null) ? $facets['attributes'] : [],
            is_array($catalog['attributeSlugs'] ?? null) ? $catalog['attributeSlugs'] : []
        );

        $allowedAttrs = is_array($catalog['attributeSlugs'] ?? null) ? $catalog['attributeSlugs'] : [];
        if ($allowedAttrs !== []) {
            $color = is_array($facets['color'] ?? null) ? $facets['color'] : [];
            $taxonomy = (string) ($color['taxonomy'] ?? '');
            $slug = $taxonomy !== '' && str_starts_with($taxonomy, 'pa_')
                ? substr($taxonomy, 3)
                : $taxonomy;
            if ($slug === '' || !in_array($slug, $allowedAttrs, true)) {
                $facets['color'] = [
                    'taxonomy' => null,
                    'terms' => [],
                ];
            }
        }

        return $facets;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function buildCategoryTree(): array
    {
        $terms = ProductLookupQueryService::terms('product_cat', true);
        $byParent = [];
        foreach ($terms as $term) {
            $byParent[(int) $term['parent']][] = $term;
        }

        $build = static function (int $parent) use (&$build, &$byParent): array {
            $nodes = [];
            foreach ($byParent[$parent] ?? [] as $term) {
                $nodes[] = [
                    'id' => (int) $term['id'],
                    'name' => (string) $term['name'],
                    'slug' => (string) $term['slug'],
                    'count' => (int) $term['count'],
                    'children' => $build((int) $term['id']),
                ];
            }
            usort($nodes, static function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            return $nodes;
        };

        return $build(0);
    }

    /**
     * @return array{min:float,max:float}
     */
    public static function priceBounds(): array
    {
        $catalog = CatalogSettings::get();

        return [
            'min' => (float) ($catalog['priceMin'] ?? 0),
            'max' => (float) ($catalog['priceMax'] ?? 30000),
        ];
    }

    /**
     * Currency symbol for lookup price UI (AUD/$ etc).
     */
    public static function currencySymbol(): string
    {
        static $symbol = null;
        if ($symbol !== null) {
            return $symbol;
        }

        $currency = strtoupper(ProductLookupQueryService::option('woocommerce_currency', 'AUD'));
        $map = [
            'AUD' => '$',
            'USD' => '$',
            'NZD' => '$',
            'CAD' => '$',
            'GBP' => '£',
            'EUR' => '€',
        ];
        $symbol = $map[$currency] ?? '$';

        return $symbol;
    }

    /**
     * @return array{taxonomy:?string,terms:list<array<string,mixed>>}
     */
    public static function buildColorFacet(): array
    {
        $taxonomy = self::colorTaxonomy();
        if (!$taxonomy) {
            return ['taxonomy' => null, 'terms' => []];
        }

        $terms = ProductLookupQueryService::terms($taxonomy, true);
        $items = [];
        $termMetaKeys = ['product_attribute_color', 'color', 'cfvsw_color'];
        $pdo = ProductLookupQueryService::pdo();
        $tm = ProductLookupQueryService::table('termmeta');
        $hasTermmeta = ProductLookupQueryService::tableExists($tm);

        foreach ($terms as $term) {
            $hex = '';
            if ($hasTermmeta) {
                $stmt = $pdo->prepare(
                    "SELECT meta_value FROM `{$tm}` WHERE term_id = ? AND meta_key IN (?,?,?) LIMIT 1"
                );
                $stmt->execute([(int) $term['id'], ...$termMetaKeys]);
                $hex = trim((string) ($stmt->fetchColumn() ?: ''));
            }
            if ($hex !== '' && $hex[0] !== '#') {
                $hex = '#' . $hex;
            }
            if ($hex === '' || !preg_match('/^#[0-9a-fA-F]{3,8}$/', $hex)) {
                $hex = ColorHelper::guessNamedColorHex((string) $term['name']);
            }

            $items[] = [
                'id' => (int) $term['id'],
                'name' => (string) $term['name'],
                'slug' => (string) $term['slug'],
                'count' => (int) $term['count'],
                'hex' => $hex,
            ];
        }

        return ['taxonomy' => $taxonomy, 'terms' => $items];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function buildTagFacet(): array
    {
        $items = [];
        foreach (ProductLookupQueryService::terms('product_tag', true, 200) as $term) {
            $items[] = [
                'id' => (int) $term['id'],
                'name' => (string) $term['name'],
                'slug' => (string) $term['slug'],
                'count' => (int) $term['count'],
            ];
        }

        return $items;
    }

    /**
     * Expand search to product IDs (title/excerpt/content + SKU + taxonomy names) in one pass.
     *
     * @return list<int>
     */
    public static function searchExtraIds(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $cacheKey = 'search_' . md5(strtolower($q));
        $cached = self::cacheGet($cacheKey, 300);
        if (is_array($cached)) {
            return array_map('intval', $cached);
        }

        $pdo = ProductLookupQueryService::pdo();
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $ids = [];

        $posts = ProductLookupQueryService::table('posts');
        $stmt = $pdo->prepare(
            "SELECT ID FROM `{$posts}`
             WHERE post_type = 'product' AND post_status = 'publish'
             AND (post_title LIKE ? OR post_excerpt LIKE ? OR post_content LIKE ?)
             LIMIT 400"
        );
        $stmt->execute([$like, $like, $like]);
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }

        $lookup = self::metaLookupTable();
        if ($lookup) {
            $skuStmt = $pdo->prepare("SELECT product_id FROM `{$lookup}` WHERE sku LIKE ? LIMIT 200");
            $skuStmt->execute([$like]);
            foreach ($skuStmt->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                $ids[] = (int) $id;
            }
        } else {
            $pm = ProductLookupQueryService::table('postmeta');
            $skuStmt = $pdo->prepare(
                "SELECT DISTINCT post_id FROM `{$pm}` WHERE meta_key = '_sku' AND meta_value LIKE ? LIMIT 200"
            );
            $skuStmt->execute([$like]);
            foreach ($skuStmt->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                $ids[] = (int) $id;
            }
        }

        $t = ProductLookupQueryService::table('terms');
        $tt = ProductLookupQueryService::table('term_taxonomy');
        $tr = ProductLookupQueryService::table('term_relationships');
        $termStmt = $pdo->prepare(
            "SELECT DISTINCT tr.object_id
             FROM `{$t}` t
             INNER JOIN `{$tt}` tt ON tt.term_id = t.term_id
             INNER JOIN `{$tr}` tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN `{$posts}` p ON p.ID = tr.object_id AND p.post_type = 'product' AND p.post_status = 'publish'
             WHERE (tt.taxonomy LIKE ? OR tt.taxonomy IN ('product_cat','product_tag'))
             AND (t.name LIKE ? OR t.slug LIKE ?)
             LIMIT 200"
        );
        $termStmt->execute(['pa_%', $like, $like]);
        foreach ($termStmt->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }

        $ids = array_values(array_unique(array_filter($ids)));
        self::cacheSet($cacheKey, $ids, 300);

        return $ids;
    }

    /**
     * Run product query for current request.
     *
     * @param array<string, mixed> $request
     * @return array{products:list<array<string,mixed>>,total:int,pages:int,from:int,to:int}
     */
    public static function queryProducts(array $request): array
    {
        $perPage = (int) $request['per_page'];
        $page = (int) $request['page'];

        $cacheKey = 'q_v3_' . md5(json_encode([
            'q' => $request['q'],
            'cat' => $request['cat'],
            'min' => $request['min_price'],
            'max' => $request['max_price'],
            'color' => $request['color'],
            'attr' => $request['attr'],
            'stock' => $request['stock'],
            'sale' => $request['sale'],
            'featured' => $request['featured'],
            'tag' => $request['tag'],
            'orderby' => $request['orderby'],
            'per_page' => $perPage,
            'page' => $page,
        ]));
        $cached = self::cacheGet($cacheKey, 300);
        if (is_array($cached) && isset($cached['products'], $cached['total'])) {
            return $cached;
        }

        $result = ProductLookupQueryService::queryIds($request);
        $ids = $result['ids'];
        $total = (int) $result['total'];
        $pages = $perPage > 0 ? (int) ceil($total / $perPage) : 0;

        $products = [];
        foreach ($ids as $pid) {
            $card = ProductLookupQueryService::mapProductCardId((int) $pid, true);
            if ($card !== []) {
                unset($card['_content'], $card['_gallery_ids'], $card['_thumb_id']);
                $products[] = $card;
            }
        }

        $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($total, $page * $perPage);

        $payload = [
            'products' => $products,
            'total' => $total,
            'pages' => $pages,
            'from' => $from,
            'to' => $to,
        ];
        self::cacheSet($cacheKey, $payload, 300);

        return $payload;
    }

    public static function stockLabel(string $status): string
    {
        switch ($status) {
            case 'instock':
                return 'In Stock';
            case 'outofstock':
                return 'Out of Stock';
            case 'onbackorder':
                return 'On Backorder';
            default:
                return ucfirst($status);
        }
    }

    /**
     * Quick view payload for a product ID.
     *
     * @return array<string, mixed>|null
     */
    public static function getQuickView(int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        $card = ProductLookupQueryService::mapProductCardId($productId, false);
        if ($card === [] || (($card['id'] ?? 0) <= 0)) {
            return null;
        }

        $gallery = [];
        $thumbId = (int) ($card['_thumb_id'] ?? 0);
        if ($thumbId > 0) {
            $large = ProductLookupQueryService::attachmentUrl($thumbId);
            if ($large !== '') {
                $gallery[] = $large;
            }
        }
        foreach (($card['_gallery_ids'] ?? []) as $gid) {
            $url = ProductLookupQueryService::attachmentUrl((int) $gid);
            if ($url !== '') {
                $gallery[] = $url;
            }
        }
        if ($gallery === []) {
            $gallery = $card['images'] ?? [];
        }

        $attributes = [];
        foreach (($card['attributes_summary'] ?? []) as $attr) {
            if (!is_array($attr)) {
                continue;
            }
            $values = is_array($attr['values'] ?? null) ? $attr['values'] : [];
            if ($values === []) {
                continue;
            }
            $attributes[] = [
                'label' => (string) ($attr['label'] ?? ''),
                'value' => implode(', ', array_map('strval', $values)),
            ];
        }

        $tagMap = ProductLookupQueryService::objectTerms([$productId], 'product_tag');
        $tags = [];
        foreach ($tagMap[$productId] ?? [] as $term) {
            $tags[] = ['id' => $term['id'], 'name' => $term['name']];
        }

        $related = [];
        foreach (ProductLookupQueryService::relatedIds($productId, 4) as $rid) {
            $rp = ProductLookupQueryService::mapProductCardId((int) $rid, true);
            if ($rp !== []) {
                unset($rp['_content'], $rp['_gallery_ids'], $rp['_thumb_id']);
                $related[] = $rp;
            }
        }

        $description = self::formatDescriptionHtml((string) ($card['_content'] ?? ''));
        $shortFull = trim((string) ($card['short_description'] ?? ''));
        if ($description === '' && $shortFull !== '') {
            $description = self::formatDescriptionHtml($shortFull);
        }

        unset($card['_content'], $card['_gallery_ids'], $card['_thumb_id']);

        return array_merge($card, [
            'gallery' => $gallery,
            'description' => $description,
            'short_description' => $shortFull,
            'attributes' => $attributes,
            'tags' => $tags,
            'related' => $related,
        ]);
    }

    public static function hasActiveFilters(array $request): bool
    {
        if (($request['q'] ?? '') !== '') {
            return true;
        }
        if (!empty($request['cat']) || !empty($request['color']) || !empty($request['tag']) || !empty($request['stock'])) {
            return true;
        }
        if (!empty($request['attr'])) {
            return true;
        }
        if (($request['sale'] ?? '') !== '') {
            return true;
        }
        if (!empty($request['featured'])) {
            return true;
        }
        if ($request['min_price'] !== null || $request['max_price'] !== null) {
            return true;
        }

        return false;
    }

    /**
     * Build full page view model.
     *
     * @return array<string, mixed>
     */
    public static function buildPage(array $get): array
    {
        $catalog = CatalogSettings::get();

        $boot = self::bootstrap();
        if (empty($boot['ok'])) {
            return [
                'ok' => false,
                'error' => (string) ($boot['error'] ?? 'Lookup unavailable.'),
                'request' => self::parseRequest($get),
                'facets' => [],
                'products' => [],
                'total' => 0,
                'pages' => 0,
                'from' => 0,
                'to' => 0,
                'quick_view' => null,
                'has_active_filters' => false,
                'clear_url' => self::basePath(),
                'orderby_options' => self::orderbyOptions(),
                'per_page_options' => CatalogSettings::resultsPerPageChoices(
                    (int) ($catalog['resultsPerPage'] ?? 12)
                ),
                'catalog' => $catalog,
            ];
        }

        $request = self::parseRequest($get);
        $facets = self::getFacets();
        $result = self::queryProducts($request);

        $quickView = null;
        $viewId = self::resolveViewId($request['view'] ?? '');
        if ($viewId > 0) {
            $quickView = self::getQuickView($viewId);
            // Canonicalize stored request view to the product slug when available.
            if (is_array($quickView) && (($quickView['slug'] ?? '') !== '')) {
                $request['view'] = StringHelper::slug((string) $quickView['slug']);
            }
        }

        $clearUrl = self::url([
            'layout' => $request['layout'],
        ]);

        return [
            'ok' => true,
            'error' => '',
            'request' => $request,
            'facets' => $facets,
            'products' => $result['products'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'from' => $result['from'],
            'to' => $result['to'],
            'quick_view' => $quickView,
            'has_active_filters' => self::hasActiveFilters($request),
            'clear_url' => $clearUrl,
            'orderby_options' => self::orderbyOptions(),
            'per_page_options' => CatalogSettings::resultsPerPageChoices(
                (int) ($catalog['resultsPerPage'] ?? 12)
            ),
            'catalog' => $catalog,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function orderbyOptions(): array
    {
        return CatalogSettings::orderbyChoices();
    }
}
