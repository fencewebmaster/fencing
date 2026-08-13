<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\ColorHelper;
use Fc\Admin\Helpers\FormatHelper;
use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Helpers\StringHelper;

/**
 * Product Lookup — PDO data access against WooCommerce MySQL tables.
 * Never loads WordPress (no wp-load.php).
 */
final class ProductLookupQueryService
{
    /**
     * @return array{ok:bool,error?:string,pdo?:\PDO,prefix?:string}
     */
    public static function boot(): array
    {
        static $state = null;
        if (is_array($state)) {
            return $state;
        }

        $conn = DatabaseConfigService::pdo();
        if (!($conn['pdo'] instanceof \PDO)) {
            $state = [
                'ok' => false,
                'error' => DatabaseConfigService::connectErrorMessage((string) ($conn['error'] ?? '')),
            ];

            return $state;
        }

        $state = [
            'ok' => true,
            'pdo' => $conn['pdo'],
            'prefix' => (string) ($conn['prefix'] ?? 'wp_'),
        ];

        return $state;
    }

    public static function pdo(): \PDO
    {
        $boot = self::boot();
        if (empty($boot['ok']) || !($boot['pdo'] instanceof \PDO)) {
            throw new \RuntimeException((string) ($boot['error'] ?? 'Database unavailable.'));
        }

        return $boot['pdo'];
    }

    public static function table(string $name): string
    {
        $boot = self::boot();
        $prefix = (string) ($boot['prefix'] ?? 'wp_');

        return $prefix . $name;
    }

    public static function option(string $key, string $default = ''): string
    {
        static $cache = [];
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $pdo = self::pdo();
            $stmt = $pdo->prepare(
                'SELECT option_value FROM `' . self::table('options') . '` WHERE option_name = ? LIMIT 1'
            );
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            $cache[$key] = is_string($val) ? $val : $default;
        } catch (\Throwable $e) {
            $cache[$key] = $default;
        }

        return $cache[$key];
    }

    public static function siteUrl(): string
    {
        $url = self::option('home');
        if ($url === '') {
            $url = self::option('siteurl');
        }
        $url = rtrim($url, '/');
        if ($url === '' && RequestHelper::isLocalhost()) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $url = $scheme . '://' . $host . '/wp/fencing';
        }

        return $url;
    }

    public static function uploadsBaseUrl(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $custom = rtrim(self::option('upload_url_path'), '/');
        if ($custom !== '') {
            $base = $custom;

            return $base;
        }

        $base = rtrim(self::siteUrl(), '/') . '/wp-content/uploads';

        return $base;
    }

    /**
     * @return list<string>
     */
    public static function placeholders(int $count): array
    {
        return $count > 0 ? array_fill(0, $count, '?') : [];
    }

    public static function tableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        try {
            $pdo = self::pdo();
            // Prefer information_schema: SHOW TABLES LIKE + PDO binds is unreliable,
            // and "_" is a LIKE wildcard (breaks table names like wp_woocommerce_*).
            $stmt = $pdo->prepare(
                'SELECT 1 FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?
                 LIMIT 1'
            );
            $stmt->execute([$table]);
            $cache[$table] = (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $cache[$table] = false;
        }

        return $cache[$table];
    }

    /**
     * @return list<array{attribute_name:string,attribute_label:string}>
     */
    public static function attributeTaxonomies(): array
    {
        static $rows = null;
        if (is_array($rows)) {
            return $rows;
        }

        $table = self::table('woocommerce_attribute_taxonomies');
        if (!self::tableExists($table)) {
            $rows = [];

            return $rows;
        }

        $pdo = self::pdo();
        $stmt = $pdo->query(
            "SELECT attribute_name, attribute_label FROM `{$table}` ORDER BY attribute_name ASC"
        );
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $label = (string) ($row['attribute_label'] ?? '');
            $out[] = [
                'attribute_name' => (string) ($row['attribute_name'] ?? ''),
                'attribute_label' => StringHelper::decodeHtmlEntities($label),
            ];
        }
        $rows = $out;

        return $rows;
    }

    /**
     * @return list<array{id:int,name:string,slug:string,parent:int,count:int}>
     */
    public static function terms(string $taxonomy, bool $hideEmpty = true, int $limit = 0): array
    {
        $pdo = self::pdo();
        $t = self::table('terms');
        $tt = self::table('term_taxonomy');
        $sql = "SELECT t.term_id AS id, t.name, t.slug, tt.parent, tt.count
                FROM `{$t}` t
                INNER JOIN `{$tt}` tt ON tt.term_id = t.term_id
                WHERE tt.taxonomy = ?";
        if ($hideEmpty) {
            $sql .= ' AND tt.count > 0';
        }
        $sql .= ' ORDER BY t.name ASC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$taxonomy]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'name' => StringHelper::decodeHtmlEntities((string) $row['name']),
                'slug' => (string) $row['slug'],
                'parent' => (int) $row['parent'],
                'count' => (int) $row['count'],
            ];
        }

        return $out;
    }

    /**
     * Expand category term IDs to include all descendant term IDs.
     *
     * @param list<int> $termIds
     * @return list<int>
     */
    public static function expandCategoryIds(array $termIds): array
    {
        $termIds = array_values(array_unique(array_filter(array_map('intval', $termIds))));
        if ($termIds === []) {
            return [];
        }

        $all = self::terms('product_cat', false);
        $childrenOf = [];
        foreach ($all as $term) {
            $parent = (int) $term['parent'];
            if ($parent > 0) {
                $childrenOf[$parent][] = (int) $term['id'];
            }
        }

        $out = [];
        $stack = $termIds;
        while ($stack !== []) {
            $id = array_pop($stack);
            if (isset($out[$id])) {
                continue;
            }
            $out[$id] = true;
            foreach ($childrenOf[$id] ?? [] as $child) {
                $stack[] = $child;
            }
        }

        return array_map('intval', array_keys($out));
    }

    /**
     * @return list<int> term_taxonomy_id values for product_visibility slugs
     */
    public static function visibilityTermTaxonomyIds(array $slugs): array
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            try {
                $pdo = self::pdo();
                $tt = self::table('term_taxonomy');
                $t = self::table('terms');
                $stmt = $pdo->query(
                    "SELECT t.slug, tt.term_taxonomy_id
                     FROM `{$tt}` tt
                     INNER JOIN `{$t}` t ON t.term_id = tt.term_id
                     WHERE tt.taxonomy = 'product_visibility'"
                );
                foreach ($stmt->fetchAll() as $row) {
                    $map[(string) $row['slug']] = (int) $row['term_taxonomy_id'];
                }
            } catch (\Throwable $e) {
                $map = [];
            }
        }

        $ids = [];
        foreach ($slugs as $slug) {
            if (isset($map[$slug])) {
                $ids[] = (int) $map[$slug];
            }
        }

        return $ids;
    }

    public static function colorTaxonomy(): ?string
    {
        static $tax = false;
        if ($tax !== false) {
            return $tax;
        }

        $preferred = ['color', 'colour', 'colors', 'colours'];
        foreach ($preferred as $name) {
            foreach (self::attributeTaxonomies() as $row) {
                if (strtolower((string) ($row['attribute_name'] ?? '')) === $name) {
                    $tax = 'pa_' . $row['attribute_name'];

                    return $tax;
                }
            }
        }
        $tax = null;

        return $tax;
    }

    /**
     * @return list<int>
     */
    public static function termTaxonomyIdsForTerms(string $taxonomy, array $termIds): array
    {
        $termIds = array_values(array_unique(array_filter(array_map('intval', $termIds))));
        if ($termIds === []) {
            return [];
        }

        $pdo = self::pdo();
        $tt = self::table('term_taxonomy');
        $ph = implode(',', self::placeholders(count($termIds)));
        $stmt = $pdo->prepare(
            "SELECT term_taxonomy_id FROM `{$tt}` WHERE taxonomy = ? AND term_id IN ({$ph})"
        );
        $stmt->execute(array_merge([$taxonomy], $termIds));

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @return list<int>
     */
    public static function onSaleIds(): array
    {
        static $ids = null;
        if (is_array($ids)) {
            return $ids;
        }

        $pdo = self::pdo();
        $pm = self::table('postmeta');
        $p = self::table('posts');
        $now = time();
        $sql = "SELECT DISTINCT pm.post_id
                FROM `{$pm}` pm
                INNER JOIN `{$p}` p ON p.ID = pm.post_id AND p.post_type = 'product' AND p.post_status = 'publish'
                WHERE pm.meta_key = '_sale_price' AND pm.meta_value <> '' AND pm.meta_value IS NOT NULL
                AND (
                    NOT EXISTS (
                        SELECT 1 FROM `{$pm}` f WHERE f.post_id = pm.post_id AND f.meta_key = '_sale_price_dates_from' AND f.meta_value <> '' AND CAST(f.meta_value AS UNSIGNED) > ?
                    )
                )
                AND (
                    NOT EXISTS (
                        SELECT 1 FROM `{$pm}` t WHERE t.post_id = pm.post_id AND t.meta_key = '_sale_price_dates_to' AND t.meta_value <> '' AND CAST(t.meta_value AS UNSIGNED) > 0 AND CAST(t.meta_value AS UNSIGNED) < ?
                    )
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$now, $now]);
        $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));

        return $ids;
    }

    /**
     * @param array<string, mixed> $request
     * @return array{ids:list<int>,total:int}
     */
    public static function queryIds(array $request): array
    {
        $pdo = self::pdo();
        $posts = self::table('posts');
        $postmeta = self::table('postmeta');
        $tr = self::table('term_relationships');

        $joins = [];
        $wheres = [
            "p.post_type = 'product'",
            "p.post_status = 'publish'",
        ];
        $joinParams = [];
        $whereParams = [];
        $joinIdx = 0;

        // Exclude catalog/search hidden products.
        $excludeTt = self::visibilityTermTaxonomyIds(['exclude-from-catalog', 'exclude-from-search']);
        if ($excludeTt !== []) {
            $ph = implode(',', self::placeholders(count($excludeTt)));
            $wheres[] = "p.ID NOT IN (
                SELECT object_id FROM `{$tr}`
                WHERE term_taxonomy_id IN ({$ph})
            )";
            foreach ($excludeTt as $ttid) {
                $whereParams[] = $ttid;
            }
        }

        $addTaxIn = static function (array $ttIds) use (&$joins, &$joinParams, &$joinIdx, $tr): void {
            if ($ttIds === []) {
                return;
            }
            $alias = 'tr' . $joinIdx++;
            $ph = implode(',', self::placeholders(count($ttIds)));
            $joins[] = "INNER JOIN `{$tr}` {$alias} ON {$alias}.object_id = p.ID AND {$alias}.term_taxonomy_id IN ({$ph})";
            foreach ($ttIds as $id) {
                $joinParams[] = $id;
            }
        };

        if ($request['cat'] !== []) {
            $catIds = self::expandCategoryIds($request['cat']);
            $addTaxIn(self::termTaxonomyIdsForTerms('product_cat', $catIds));
        }

        if ($request['tag'] !== []) {
            $addTaxIn(self::termTaxonomyIdsForTerms('product_tag', $request['tag']));
        }

        $colorTax = self::colorTaxonomy();
        if ($colorTax && $request['color'] !== []) {
            $addTaxIn(self::termTaxonomyIdsForTerms($colorTax, $request['color']));
        }

        foreach ($request['attr'] as $slug => $termIds) {
            if ($termIds === []) {
                continue;
            }
            $addTaxIn(self::termTaxonomyIdsForTerms('pa_' . $slug, $termIds));
        }

        if (!empty($request['featured']) || ($request['orderby'] ?? '') === 'featured') {
            $featuredTt = self::visibilityTermTaxonomyIds(['featured']);
            $addTaxIn($featuredTt);
        }

        $stock = is_array($request['stock'] ?? null) ? $request['stock'] : [];
        if ($stock !== []) {
            $alias = 'pm_stock';
            $ph = implode(',', self::placeholders(count($stock)));
            $joins[] = "INNER JOIN `{$postmeta}` {$alias} ON {$alias}.post_id = p.ID AND {$alias}.meta_key = '_stock_status' AND {$alias}.meta_value IN ({$ph})";
            foreach ($stock as $s) {
                $joinParams[] = $s;
            }
        }

        $needsPrice = ($request['min_price'] !== null || $request['max_price'] !== null)
            || in_array((string) ($request['orderby'] ?? ''), ['price', 'price-desc'], true);
        if ($needsPrice) {
            $joins[] = "LEFT JOIN `{$postmeta}` pm_price ON pm_price.post_id = p.ID AND pm_price.meta_key = '_price'";
            if ($request['min_price'] !== null || $request['max_price'] !== null) {
                $min = $request['min_price'] !== null ? (float) $request['min_price'] : 0.0;
                $max = $request['max_price'] !== null ? (float) $request['max_price'] : PHP_FLOAT_MAX;
                $wheres[] = 'CAST(pm_price.meta_value AS DECIMAL(20,4)) BETWEEN ? AND ?';
                $whereParams[] = $min;
                $whereParams[] = $max;
            }
        }

        $orderby = (string) ($request['orderby'] ?? 'default');
        if ($orderby === 'popularity') {
            $joins[] = "LEFT JOIN `{$postmeta}` pm_sales ON pm_sales.post_id = p.ID AND pm_sales.meta_key = 'total_sales'";
        }
        if ($orderby === 'rating') {
            $joins[] = "LEFT JOIN `{$postmeta}` pm_rating ON pm_rating.post_id = p.ID AND pm_rating.meta_key = '_wc_average_rating'";
        }

        $postIn = null;
        $postNotIn = [];

        if (($request['sale'] ?? '') === 'on') {
            $onSale = self::onSaleIds();
            $postIn = $onSale;
            if ($postIn === []) {
                return ['ids' => [], 'total' => 0];
            }
        } elseif (($request['sale'] ?? '') === 'regular') {
            $postNotIn = self::onSaleIds();
        }

        $q = trim((string) ($request['q'] ?? ''));
        if ($q !== '') {
            $merged = ProductLookupService::searchExtraIds($q);
            if ($merged === []) {
                return ['ids' => [], 'total' => 0];
            }
            $postIn = $postIn === null ? $merged : array_values(array_intersect($postIn, $merged));
            if ($postIn === []) {
                return ['ids' => [], 'total' => 0];
            }
        }

        if ($postIn !== null) {
            if (count($postIn) > 800) {
                $postIn = array_slice($postIn, 0, 800);
            }
            $ph = implode(',', self::placeholders(count($postIn)));
            $wheres[] = "p.ID IN ({$ph})";
            foreach ($postIn as $id) {
                $whereParams[] = $id;
            }
        }
        if ($postNotIn !== []) {
            $ph = implode(',', self::placeholders(count($postNotIn)));
            $wheres[] = "p.ID NOT IN ({$ph})";
            foreach ($postNotIn as $id) {
                $whereParams[] = $id;
            }
        }

        switch ($orderby) {
            case 'popularity':
                $orderSql = 'CAST(COALESCE(pm_sales.meta_value, 0) AS UNSIGNED) DESC, p.post_title ASC';
                break;
            case 'rating':
                $orderSql = 'CAST(COALESCE(pm_rating.meta_value, 0) AS DECIMAL(10,2)) DESC, p.post_title ASC';
                break;
            case 'date':
                $orderSql = 'p.post_date DESC';
                break;
            case 'price':
                $orderSql = 'CAST(COALESCE(pm_price.meta_value, 0) AS DECIMAL(20,4)) ASC, p.post_title ASC';
                break;
            case 'price-desc':
                $orderSql = 'CAST(COALESCE(pm_price.meta_value, 0) AS DECIMAL(20,4)) DESC, p.post_title ASC';
                break;
            case 'title':
                $orderSql = 'p.post_title ASC';
                break;
            case 'title-desc':
                $orderSql = 'p.post_title DESC';
                break;
            case 'featured':
                $orderSql = 'p.menu_order ASC, p.post_title ASC';
                break;
            default:
                if ($postIn !== null) {
                    // Preserve search relevance order when possible.
                    $orderSql = 'FIELD(p.ID,' . implode(',', array_map('intval', $postIn)) . ')';
                } else {
                    $orderSql = 'p.menu_order ASC, p.post_title ASC';
                }
                break;
        }

        $from = ' FROM `' . $posts . '` p ' . implode(' ', $joins) . ' WHERE ' . implode(' AND ', $wheres);
        $params = array_merge($joinParams, $whereParams);

        $countStmt = $pdo->prepare('SELECT COUNT(DISTINCT p.ID)' . $from);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $perPageRaw = (int) ($request['per_page'] ?? 12);
        $idSql = 'SELECT DISTINCT p.ID' . $from . ' ORDER BY ' . $orderSql;
        if ($perPageRaw === CatalogSettings::ALL_PER_PAGE) {
            // "All" — every matching product on one page, no LIMIT/OFFSET.
        } else {
            $perPage = max(1, $perPageRaw);
            $page = max(1, (int) ($request['page'] ?? 1));
            $offset = ($page - 1) * $perPage;
            $idSql .= ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        }
        $idStmt = $pdo->prepare($idSql);
        $idStmt->execute($params);
        $ids = array_map('intval', $idStmt->fetchAll(\PDO::FETCH_COLUMN));

        return ['ids' => $ids, 'total' => $total];
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, string>>
     */
    public static function postmetaMap(array $ids, array $keys): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] || $keys === []) {
            return [];
        }

        $pdo = self::pdo();
        $pm = self::table('postmeta');
        $idPh = implode(',', self::placeholders(count($ids)));
        $keyPh = implode(',', self::placeholders(count($keys)));
        $stmt = $pdo->prepare(
            "SELECT post_id, meta_key, meta_value FROM `{$pm}`
             WHERE post_id IN ({$idPh}) AND meta_key IN ({$keyPh})"
        );
        $stmt->execute(array_merge($ids, $keys));
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int) $row['post_id'];
            $map[$pid][(string) $row['meta_key']] = (string) $row['meta_value'];
        }

        return $map;
    }

    /**
     * @param list<int> $ids
     * @return array<int, list<array{id:int,name:string,slug:string}>>
     */
    public static function objectTerms(array $ids, string $taxonomy): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $pdo = self::pdo();
        $tr = self::table('term_relationships');
        $tt = self::table('term_taxonomy');
        $t = self::table('terms');
        $ph = implode(',', self::placeholders(count($ids)));
        $stmt = $pdo->prepare(
            "SELECT tr.object_id, t.term_id, t.name, t.slug
             FROM `{$tr}` tr
             INNER JOIN `{$tt}` tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = ?
             INNER JOIN `{$t}` t ON t.term_id = tt.term_id
             WHERE tr.object_id IN ({$ph})
             ORDER BY t.name ASC"
        );
        $stmt->execute(array_merge([$taxonomy], $ids));
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $oid = (int) $row['object_id'];
            $map[$oid][] = [
                'id' => (int) $row['term_id'],
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
            ];
        }

        return $map;
    }

    public static function attachmentUrl(int $attachmentId): string
    {
        if ($attachmentId <= 0) {
            return '';
        }

        static $cache = [];
        if (isset($cache[$attachmentId])) {
            return $cache[$attachmentId];
        }

        $pdo = self::pdo();
        $pm = self::table('postmeta');
        $stmt = $pdo->prepare(
            "SELECT meta_value FROM `{$pm}` WHERE post_id = ? AND meta_key = '_wp_attached_file' LIMIT 1"
        );
        $stmt->execute([$attachmentId]);
        $file = (string) ($stmt->fetchColumn() ?: '');
        if ($file === '') {
            $cache[$attachmentId] = '';

            return '';
        }

        if (preg_match('#^https?://#i', $file)) {
            $cache[$attachmentId] = $file;

            return $file;
        }

        $cache[$attachmentId] = rtrim(self::uploadsBaseUrl(), '/') . '/' . ltrim($file, '/');

        return $cache[$attachmentId];
    }

    /**
     * @return array{ID:int,post_title:string,post_name:string,post_excerpt:string,post_content:string,post_date:string,post_status:string}|null
     */
    public static function getPost(int $id): ?array
    {
        $pdo = self::pdo();
        $p = self::table('posts');
        $stmt = $pdo->prepare(
            "SELECT ID, post_title, post_name, post_excerpt, post_content, post_date, post_status, menu_order
             FROM `{$p}` WHERE ID = ? AND post_type = 'product' LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Resolve a published product ID from post_name (slug).
     */
    public static function productIdBySlug(string $slug): int
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return 0;
        }

        static $cache = [];
        if (array_key_exists($slug, $cache)) {
            return $cache[$slug];
        }

        $pdo = self::pdo();
        $p = self::table('posts');
        $stmt = $pdo->prepare(
            "SELECT ID FROM `{$p}`
             WHERE post_type = 'product' AND post_status = 'publish' AND post_name = ?
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $id = (int) ($stmt->fetchColumn() ?: 0);
        $cache[$slug] = $id;

        return $id;
    }

    /**
     * @param array<string, string> $meta
     */
    public static function priceHtml(array $meta): string
    {
        $price = isset($meta['_price']) && $meta['_price'] !== '' ? (float) $meta['_price'] : null;
        if ($price === null) {
            return '';
        }
        $regular = isset($meta['_regular_price']) && $meta['_regular_price'] !== '' ? (float) $meta['_regular_price'] : null;
        $sale = isset($meta['_sale_price']) && $meta['_sale_price'] !== '' ? (float) $meta['_sale_price'] : null;
        $symbol = ProductLookupService::currencySymbol();
        if ($sale !== null && $regular !== null && $sale < $regular) {
            return '<del>' . FormatHelper::money($regular, $symbol) . '</del> <ins>' . FormatHelper::money($sale, $symbol) . '</ins>';
        }

        return FormatHelper::money($price, $symbol);
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapProductCardId(int $productId, bool $lightweight = false): array
    {
        $post = self::getPost($productId);
        if ($post === null || ($post['post_status'] ?? '') !== 'publish') {
            return [];
        }

        $metaKeys = [
            '_sku', '_price', '_regular_price', '_sale_price', '_stock_status',
            '_thumbnail_id', '_product_image_gallery', '_wc_average_rating',
            '_wc_review_count', 'total_sales',
        ];
        $metaMap = self::postmetaMap([$productId], $metaKeys);
        $meta = $metaMap[$productId] ?? [];

        $thumbId = (int) ($meta['_thumbnail_id'] ?? 0);
        $images = [];
        if ($thumbId > 0) {
            $url = self::attachmentUrl($thumbId);
            if ($url !== '') {
                $images[] = $url;
            }
        }
        $galleryRaw = (string) ($meta['_product_image_gallery'] ?? '');
        $galleryIds = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $galleryRaw) ?: [])));
        $extra = $lightweight ? array_slice($galleryIds, 0, 1) : array_slice($galleryIds, 0, 4);
        foreach ($extra as $gid) {
            $url = self::attachmentUrl($gid);
            if ($url !== '') {
                $images[] = $url;
            }
        }

        $catMap = self::objectTerms([$productId], 'product_cat');
        $cats = [];
        foreach (array_slice($catMap[$productId] ?? [], 0, 4) as $term) {
            $cats[] = ['id' => $term['id'], 'name' => $term['name']];
        }

        $colors = [];
        $colorTax = self::colorTaxonomy();
        if ($colorTax) {
            $colorMap = self::objectTerms([$productId], $colorTax);
            foreach (array_slice($colorMap[$productId] ?? [], 0, 8) as $term) {
                $hex = ColorHelper::guessNamedColorHex((string) $term['name']);
                $colors[] = [
                    'id' => $term['id'],
                    'name' => $term['name'],
                    'hex' => $hex,
                ];
            }
        }

        $featuredTt = self::visibilityTermTaxonomyIds(['featured']);
        $featured = false;
        if ($featuredTt !== []) {
            $vis = self::objectTerms([$productId], 'product_visibility');
            foreach ($vis[$productId] ?? [] as $term) {
                if ($term['slug'] === 'featured') {
                    $featured = true;
                    break;
                }
            }
        }

        $regular = (string) ($meta['_regular_price'] ?? '');
        $sale = (string) ($meta['_sale_price'] ?? '');
        $onSale = $sale !== '' && $regular !== '' && (float) $sale < (float) $regular;
        $stockStatus = (string) ($meta['_stock_status'] ?? 'instock');
        $createdTs = strtotime((string) ($post['post_date'] ?? '')) ?: 0;
        $isNew = $createdTs >= (time() - 30 * 86400);

        $badges = [];
        if ($onSale) {
            $badges[] = 'sale';
        }
        if ($featured) {
            $badges[] = 'featured';
        }
        if ($isNew) {
            $badges[] = 'new';
        }
        if ($stockStatus === 'outofstock') {
            $badges[] = 'outofstock';
        }

        $short = '';
        if (!$lightweight) {
            // Full excerpt — cards clamp visually via CSS; quick view shows the full text.
            $short = trim(strip_tags((string) ($post['post_excerpt'] ?? '')));
        }

        $attrSummary = [];
        if (!$lightweight) {
            $colorTaxName = self::colorTaxonomy();
            foreach (self::attributeTaxonomies() as $row) {
                $taxonomy = 'pa_' . $row['attribute_name'];
                if ($colorTaxName && $taxonomy === $colorTaxName) {
                    continue;
                }
                $terms = self::objectTerms([$productId], $taxonomy);
                $names = array_column($terms[$productId] ?? [], 'name');
                if ($names === []) {
                    continue;
                }
                $attrSummary[] = [
                    'label' => (string) ($row['attribute_label'] !== '' ? $row['attribute_label'] : $row['attribute_name']),
                    'values' => array_slice($names, 0, 6),
                ];
                if (count($attrSummary) >= 4) {
                    break;
                }
            }
        }

        $site = rtrim(self::siteUrl(), '/');
        $permalink = $site !== '' ? $site . '/product/' . rawurlencode((string) $post['post_name']) . '/' : '';

        return [
            'id' => $productId,
            'name' => (string) $post['post_title'],
            'slug' => (string) $post['post_name'],
            'permalink' => $permalink,
            'sku' => (string) ($meta['_sku'] ?? ''),
            'images' => $images,
            'price_html' => self::priceHtml($meta),
            'regular_price' => $regular,
            'sale_price' => $sale,
            'on_sale' => $onSale,
            'featured' => $featured,
            'is_new' => $isNew,
            'stock_status' => $stockStatus,
            'stock_label' => ProductLookupService::stockLabel($stockStatus),
            'rating' => (float) ($meta['_wc_average_rating'] ?? 0),
            'review_count' => (int) ($meta['_wc_review_count'] ?? 0),
            'categories' => $cats,
            'colors' => $colors,
            'attributes_summary' => $attrSummary,
            'short_description' => $short,
            'badges' => $badges,
            '_content' => (string) ($post['post_content'] ?? ''),
            '_gallery_ids' => $galleryIds,
            '_thumb_id' => $thumbId,
        ];
    }

    /**
     * @return list<int>
     */
    public static function relatedIds(int $productId, int $limit = 4): array
    {
        $cats = self::objectTerms([$productId], 'product_cat');
        $catIds = array_column($cats[$productId] ?? [], 'id');
        if ($catIds === []) {
            return [];
        }

        $ttIds = self::termTaxonomyIdsForTerms('product_cat', $catIds);
        if ($ttIds === []) {
            return [];
        }

        $pdo = self::pdo();
        $tr = self::table('term_relationships');
        $p = self::table('posts');
        $ph = implode(',', self::placeholders(count($ttIds)));
        $stmt = $pdo->prepare(
            "SELECT DISTINCT tr.object_id
             FROM `{$tr}` tr
             INNER JOIN `{$p}` p ON p.ID = tr.object_id AND p.post_type = 'product' AND p.post_status = 'publish'
             WHERE tr.term_taxonomy_id IN ({$ph}) AND tr.object_id <> ?
             LIMIT " . (int) $limit
        );
        $stmt->execute(array_merge($ttIds, [$productId]));

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
