<?php
/**
 * Product Lookup — PDO data access against WooCommerce MySQL tables.
 * Never loads WordPress (no wp-load.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/db_config.php';

/**
 * @return array{ok:bool,error?:string,pdo?:PDO,prefix?:string}
 */
function fc_lookup_pdo_boot(): array
{
    static $state = null;
    if (is_array($state)) {
        return $state;
    }

    $conn = fc_db_pdo();
    if (!($conn['pdo'] instanceof PDO)) {
        $state = [
            'ok' => false,
            'error' => fc_db_connect_error_message((string) ($conn['error'] ?? '')),
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

function fc_lookup_pdo(): PDO
{
    $boot = fc_lookup_pdo_boot();
    if (empty($boot['ok']) || !($boot['pdo'] instanceof PDO)) {
        throw new RuntimeException((string) ($boot['error'] ?? 'Database unavailable.'));
    }

    return $boot['pdo'];
}

function fc_lookup_table(string $name): string
{
    $boot = fc_lookup_pdo_boot();
    $prefix = (string) ($boot['prefix'] ?? 'wp_');

    return $prefix . $name;
}

function fc_lookup_option(string $key, string $default = ''): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $pdo = fc_lookup_pdo();
        $stmt = $pdo->prepare(
            'SELECT option_value FROM `' . fc_lookup_table('options') . '` WHERE option_name = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        $cache[$key] = is_string($val) ? $val : $default;
    } catch (Throwable $e) {
        $cache[$key] = $default;
    }

    return $cache[$key];
}

function fc_lookup_site_url(): string
{
    $url = fc_lookup_option('home');
    if ($url === '') {
        $url = fc_lookup_option('siteurl');
    }
    $url = rtrim($url, '/');
    if ($url === '' && function_exists('fc_lookup_is_localhost') && fc_lookup_is_localhost()) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $url = $scheme . '://' . $host . '/wp/fencing';
    }

    return $url;
}

function fc_lookup_uploads_base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $custom = rtrim(fc_lookup_option('upload_url_path'), '/');
    if ($custom !== '') {
        $base = $custom;

        return $base;
    }

    $base = rtrim(fc_lookup_site_url(), '/') . '/wp-content/uploads';

    return $base;
}

/**
 * @return list<string>
 */
function fc_lookup_placeholders(int $count): array
{
    return $count > 0 ? array_fill(0, $count, '?') : [];
}

function fc_lookup_table_exists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $pdo = fc_lookup_pdo();
        // Prefer information_schema: SHOW TABLES LIKE + PDO binds is unreliable,
        // and "_" is a LIKE wildcard (breaks table names like wp_woocommerce_*).
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1'
        );
        $stmt->execute([$table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

/**
 * @return list<array{attribute_name:string,attribute_label:string}>
 */
function fc_lookup_pdo_attribute_taxonomies(): array
{
    static $rows = null;
    if (is_array($rows)) {
        return $rows;
    }

    $table = fc_lookup_table('woocommerce_attribute_taxonomies');
    if (!fc_lookup_table_exists($table)) {
        $rows = [];

        return $rows;
    }

    $pdo = fc_lookup_pdo();
    $stmt = $pdo->query(
        "SELECT attribute_name, attribute_label FROM `{$table}` ORDER BY attribute_name ASC"
    );
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $label = (string) ($row['attribute_label'] ?? '');
        $out[] = [
            'attribute_name' => (string) ($row['attribute_name'] ?? ''),
            'attribute_label' => function_exists('fc_lookup_plain_text')
                ? fc_lookup_plain_text($label)
                : html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];
    }
    $rows = $out;

    return $rows;
}

/**
 * @return list<array{id:int,name:string,slug:string,parent:int,count:int}>
 */
function fc_lookup_pdo_terms(string $taxonomy, bool $hideEmpty = true, int $limit = 0): array
{
    $pdo = fc_lookup_pdo();
    $t = fc_lookup_table('terms');
    $tt = fc_lookup_table('term_taxonomy');
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
            'name' => function_exists('fc_lookup_plain_text')
                ? fc_lookup_plain_text((string) $row['name'])
                : html_entity_decode((string) $row['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
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
function fc_lookup_expand_category_ids(array $termIds): array
{
    $termIds = array_values(array_unique(array_filter(array_map('intval', $termIds))));
    if ($termIds === []) {
        return [];
    }

    $all = fc_lookup_pdo_terms('product_cat', false);
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
function fc_lookup_visibility_term_taxonomy_ids(array $slugs): array
{
    static $map = null;
    if ($map === null) {
        $map = [];
        try {
            $pdo = fc_lookup_pdo();
            $tt = fc_lookup_table('term_taxonomy');
            $t = fc_lookup_table('terms');
            $stmt = $pdo->query(
                "SELECT t.slug, tt.term_taxonomy_id
                 FROM `{$tt}` tt
                 INNER JOIN `{$t}` t ON t.term_id = tt.term_id
                 WHERE tt.taxonomy = 'product_visibility'"
            );
            foreach ($stmt->fetchAll() as $row) {
                $map[(string) $row['slug']] = (int) $row['term_taxonomy_id'];
            }
        } catch (Throwable $e) {
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

function fc_lookup_pdo_color_taxonomy(): ?string
{
    static $tax = false;
    if ($tax !== false) {
        return $tax;
    }

    $preferred = ['color', 'colour', 'colors', 'colours'];
    foreach ($preferred as $name) {
        foreach (fc_lookup_pdo_attribute_taxonomies() as $row) {
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
function fc_lookup_term_taxonomy_ids_for_terms(string $taxonomy, array $termIds): array
{
    $termIds = array_values(array_unique(array_filter(array_map('intval', $termIds))));
    if ($termIds === []) {
        return [];
    }

    $pdo = fc_lookup_pdo();
    $tt = fc_lookup_table('term_taxonomy');
    $ph = implode(',', fc_lookup_placeholders(count($termIds)));
    $stmt = $pdo->prepare(
        "SELECT term_taxonomy_id FROM `{$tt}` WHERE taxonomy = ? AND term_id IN ({$ph})"
    );
    $stmt->execute(array_merge([$taxonomy], $termIds));

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * @return list<int>
 */
function fc_lookup_on_sale_ids(): array
{
    static $ids = null;
    if (is_array($ids)) {
        return $ids;
    }

    $pdo = fc_lookup_pdo();
    $pm = fc_lookup_table('postmeta');
    $p = fc_lookup_table('posts');
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
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    return $ids;
}

/**
 * @param array<string, mixed> $request
 * @return array{ids:list<int>,total:int}
 */
function fc_lookup_pdo_query_ids(array $request): array
{
    $pdo = fc_lookup_pdo();
    $posts = fc_lookup_table('posts');
    $postmeta = fc_lookup_table('postmeta');
    $tr = fc_lookup_table('term_relationships');

    $joins = [];
    $wheres = [
        "p.post_type = 'product'",
        "p.post_status = 'publish'",
    ];
    $joinParams = [];
    $whereParams = [];
    $joinIdx = 0;

    // Exclude catalog/search hidden products.
    $excludeTt = fc_lookup_visibility_term_taxonomy_ids(['exclude-from-catalog', 'exclude-from-search']);
    if ($excludeTt !== []) {
        $ph = implode(',', fc_lookup_placeholders(count($excludeTt)));
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
        $ph = implode(',', fc_lookup_placeholders(count($ttIds)));
        $joins[] = "INNER JOIN `{$tr}` {$alias} ON {$alias}.object_id = p.ID AND {$alias}.term_taxonomy_id IN ({$ph})";
        foreach ($ttIds as $id) {
            $joinParams[] = $id;
        }
    };

    if ($request['cat'] !== []) {
        $catIds = fc_lookup_expand_category_ids($request['cat']);
        $addTaxIn(fc_lookup_term_taxonomy_ids_for_terms('product_cat', $catIds));
    }

    if ($request['tag'] !== []) {
        $addTaxIn(fc_lookup_term_taxonomy_ids_for_terms('product_tag', $request['tag']));
    }

    $colorTax = fc_lookup_pdo_color_taxonomy();
    if ($colorTax && $request['color'] !== []) {
        $addTaxIn(fc_lookup_term_taxonomy_ids_for_terms($colorTax, $request['color']));
    }

    foreach ($request['attr'] as $slug => $termIds) {
        if ($termIds === []) {
            continue;
        }
        $addTaxIn(fc_lookup_term_taxonomy_ids_for_terms('pa_' . $slug, $termIds));
    }

    if (!empty($request['featured']) || ($request['orderby'] ?? '') === 'featured') {
        $featuredTt = fc_lookup_visibility_term_taxonomy_ids(['featured']);
        $addTaxIn($featuredTt);
    }

    $stock = is_array($request['stock'] ?? null) ? $request['stock'] : [];
    if ($stock !== []) {
        $alias = 'pm_stock';
        $ph = implode(',', fc_lookup_placeholders(count($stock)));
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
        $onSale = fc_lookup_on_sale_ids();
        $postIn = $onSale;
        if ($postIn === []) {
            return ['ids' => [], 'total' => 0];
        }
    } elseif (($request['sale'] ?? '') === 'regular') {
        $postNotIn = fc_lookup_on_sale_ids();
    }

    $q = trim((string) ($request['q'] ?? ''));
    if ($q !== '') {
        $merged = fc_lookup_search_extra_ids($q);
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
        $ph = implode(',', fc_lookup_placeholders(count($postIn)));
        $wheres[] = "p.ID IN ({$ph})";
        foreach ($postIn as $id) {
            $whereParams[] = $id;
        }
    }
    if ($postNotIn !== []) {
        $ph = implode(',', fc_lookup_placeholders(count($postNotIn)));
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

    $perPage = max(1, (int) ($request['per_page'] ?? 12));
    $page = max(1, (int) ($request['page'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $idSql = 'SELECT DISTINCT p.ID' . $from . ' ORDER BY ' . $orderSql . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $idStmt = $pdo->prepare($idSql);
    $idStmt->execute($params);
    $ids = array_map('intval', $idStmt->fetchAll(PDO::FETCH_COLUMN));

    return ['ids' => $ids, 'total' => $total];
}

/**
 * @param list<int> $ids
 * @return array<int, array<string, string>>
 */
function fc_lookup_pdo_postmeta_map(array $ids, array $keys): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if ($ids === [] || $keys === []) {
        return [];
    }

    $pdo = fc_lookup_pdo();
    $pm = fc_lookup_table('postmeta');
    $idPh = implode(',', fc_lookup_placeholders(count($ids)));
    $keyPh = implode(',', fc_lookup_placeholders(count($keys)));
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
function fc_lookup_pdo_object_terms(array $ids, string $taxonomy): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if ($ids === []) {
        return [];
    }

    $pdo = fc_lookup_pdo();
    $tr = fc_lookup_table('term_relationships');
    $tt = fc_lookup_table('term_taxonomy');
    $t = fc_lookup_table('terms');
    $ph = implode(',', fc_lookup_placeholders(count($ids)));
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

function fc_lookup_attachment_url(int $attachmentId): string
{
    if ($attachmentId <= 0) {
        return '';
    }

    static $cache = [];
    if (isset($cache[$attachmentId])) {
        return $cache[$attachmentId];
    }

    $pdo = fc_lookup_pdo();
    $pm = fc_lookup_table('postmeta');
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

    $cache[$attachmentId] = rtrim(fc_lookup_uploads_base_url(), '/') . '/' . ltrim($file, '/');

    return $cache[$attachmentId];
}

/**
 * @return array{ID:int,post_title:string,post_name:string,post_excerpt:string,post_content:string,post_date:string,post_status:string}|null
 */
function fc_lookup_pdo_get_post(int $id): ?array
{
    $pdo = fc_lookup_pdo();
    $p = fc_lookup_table('posts');
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
function fc_lookup_pdo_product_id_by_slug(string $slug): int
{
    $slug = strtolower(trim($slug));
    if ($slug === '') {
        return 0;
    }

    static $cache = [];
    if (array_key_exists($slug, $cache)) {
        return $cache[$slug];
    }

    $pdo = fc_lookup_pdo();
    $p = fc_lookup_table('posts');
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

function fc_lookup_format_money(float $amount): string
{
    $symbol = function_exists('fc_lookup_currency_symbol') ? fc_lookup_currency_symbol() : '$';
    $formatted = number_format($amount, 2, '.', ',');

    return htmlspecialchars($symbol . $formatted, ENT_QUOTES, 'UTF-8');
}

/**
 * @param array<string, string> $meta
 */
function fc_lookup_pdo_price_html(array $meta): string
{
    $price = isset($meta['_price']) && $meta['_price'] !== '' ? (float) $meta['_price'] : null;
    if ($price === null) {
        return '';
    }
    $regular = isset($meta['_regular_price']) && $meta['_regular_price'] !== '' ? (float) $meta['_regular_price'] : null;
    $sale = isset($meta['_sale_price']) && $meta['_sale_price'] !== '' ? (float) $meta['_sale_price'] : null;
    if ($sale !== null && $regular !== null && $sale < $regular) {
        return '<del>' . fc_lookup_format_money($regular) . '</del> <ins>' . fc_lookup_format_money($sale) . '</ins>';
    }

    return fc_lookup_format_money($price);
}

/**
 * @return array<string, mixed>
 */
function fc_lookup_map_product_card_id(int $productId, bool $lightweight = false): array
{
    $post = fc_lookup_pdo_get_post($productId);
    if ($post === null || ($post['post_status'] ?? '') !== 'publish') {
        return [];
    }

    $metaKeys = [
        '_sku', '_price', '_regular_price', '_sale_price', '_stock_status',
        '_thumbnail_id', '_product_image_gallery', '_wc_average_rating',
        '_wc_review_count', 'total_sales',
    ];
    $metaMap = fc_lookup_pdo_postmeta_map([$productId], $metaKeys);
    $meta = $metaMap[$productId] ?? [];

    $thumbId = (int) ($meta['_thumbnail_id'] ?? 0);
    $images = [];
    if ($thumbId > 0) {
        $url = fc_lookup_attachment_url($thumbId);
        if ($url !== '') {
            $images[] = $url;
        }
    }
    $galleryRaw = (string) ($meta['_product_image_gallery'] ?? '');
    $galleryIds = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $galleryRaw) ?: [])));
    $extra = $lightweight ? array_slice($galleryIds, 0, 1) : array_slice($galleryIds, 0, 4);
    foreach ($extra as $gid) {
        $url = fc_lookup_attachment_url($gid);
        if ($url !== '') {
            $images[] = $url;
        }
    }

    $catMap = fc_lookup_pdo_object_terms([$productId], 'product_cat');
    $cats = [];
    foreach (array_slice($catMap[$productId] ?? [], 0, 4) as $term) {
        $cats[] = ['id' => $term['id'], 'name' => $term['name']];
    }

    $colors = [];
    $colorTax = fc_lookup_pdo_color_taxonomy();
    if ($colorTax) {
        $colorMap = fc_lookup_pdo_object_terms([$productId], $colorTax);
        foreach (array_slice($colorMap[$productId] ?? [], 0, 8) as $term) {
            $hex = function_exists('fc_lookup_guess_color_hex')
                ? fc_lookup_guess_color_hex($term['name'])
                : '#cbd5e1';
            $colors[] = [
                'id' => $term['id'],
                'name' => $term['name'],
                'hex' => $hex,
            ];
        }
    }

    $featuredTt = fc_lookup_visibility_term_taxonomy_ids(['featured']);
    $featured = false;
    if ($featuredTt !== []) {
        $vis = fc_lookup_pdo_object_terms([$productId], 'product_visibility');
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
        $colorTaxName = fc_lookup_pdo_color_taxonomy();
        foreach (fc_lookup_pdo_attribute_taxonomies() as $row) {
            $taxonomy = 'pa_' . $row['attribute_name'];
            if ($colorTaxName && $taxonomy === $colorTaxName) {
                continue;
            }
            $terms = fc_lookup_pdo_object_terms([$productId], $taxonomy);
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

    $site = rtrim(fc_lookup_site_url(), '/');
    $permalink = $site !== '' ? $site . '/product/' . rawurlencode((string) $post['post_name']) . '/' : '';

    return [
        'id' => $productId,
        'name' => (string) $post['post_title'],
        'slug' => (string) $post['post_name'],
        'permalink' => $permalink,
        'sku' => (string) ($meta['_sku'] ?? ''),
        'images' => $images,
        'price_html' => fc_lookup_pdo_price_html($meta),
        'regular_price' => $regular,
        'sale_price' => $sale,
        'on_sale' => $onSale,
        'featured' => $featured,
        'is_new' => $isNew,
        'stock_status' => $stockStatus,
        'stock_label' => function_exists('fc_lookup_stock_label')
            ? fc_lookup_stock_label($stockStatus)
            : ucfirst($stockStatus),
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
function fc_lookup_pdo_related_ids(int $productId, int $limit = 4): array
{
    $cats = fc_lookup_pdo_object_terms([$productId], 'product_cat');
    $catIds = array_column($cats[$productId] ?? [], 'id');
    if ($catIds === []) {
        return [];
    }

    $ttIds = fc_lookup_term_taxonomy_ids_for_terms('product_cat', $catIds);
    if ($ttIds === []) {
        return [];
    }

    $pdo = fc_lookup_pdo();
    $tr = fc_lookup_table('term_relationships');
    $p = fc_lookup_table('posts');
    $ph = implode(',', fc_lookup_placeholders(count($ttIds)));
    $stmt = $pdo->prepare(
        "SELECT DISTINCT tr.object_id
         FROM `{$tr}` tr
         INNER JOIN `{$p}` p ON p.ID = tr.object_id AND p.post_type = 'product' AND p.post_status = 'publish'
         WHERE tr.term_taxonomy_id IN ({$ph}) AND tr.object_id <> ?
         LIMIT " . (int) $limit
    );
    $stmt->execute(array_merge($ttIds, [$productId]));

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
