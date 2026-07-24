<?php
/**
 * FC Catalog settings — Product Lookup configuration (saved to data/theme.json as catalog).
 */

declare(strict_types=1);

require_once __DIR__ . '/theme.php';

/**
 * @return array<string, mixed>
 */
function fc_catalog_defaults(): array
{
    return [
        'categoryIds' => [],
        'attributeSlugs' => [],
        'priceMin' => 0,
        'priceMax' => 30000,
        'defaultOrderby' => 'default',
        'resultsPerPage' => 12,
        'columnsDesktop' => 4,
        'columnsLaptop' => 3,
        'columnsTablet' => 2,
        'columnsMobile' => 1,
        'sidebarTitle' => 'Product Lookup',
        'sidebarSubtitle' => 'Search the live catalog',
    ];
}

/**
 * @return array<string, string>
 */
function fc_catalog_orderby_choices(): array
{
    return [
        'default' => 'Default sorting',
        'featured' => 'Featured',
        'date' => 'Newest',
        'price' => 'Price: Low to High',
        'price-desc' => 'Price: High to Low',
        'title' => 'Name (A–Z)',
        'title-desc' => 'Name (Z–A)',
        'popularity' => 'Popularity',
        'rating' => 'Rating',
    ];
}

/**
 * Clamp the admin "Results per page" base value (first option on Lookup).
 */
function fc_catalog_clamp_results_per_page($value): int
{
    $n = (int) $value;
    if ($n < 1) {
        $n = 1;
    }
    if ($n > 100) {
        $n = 100;
    }

    return $n;
}

/**
 * Product Lookup "Per page" options: base × 1 … × 5 (always 5 items).
 * e.g. 20 → 20, 40, 60, 80, 100
 *
 * @return list<int>
 */
function fc_catalog_results_per_page_choices(?int $base = null): array
{
    if ($base === null) {
        $base = (int) (fc_catalog_get()['resultsPerPage'] ?? 12);
    }
    $base = fc_catalog_clamp_results_per_page($base);
    $out = [];
    for ($i = 1; $i <= 5; $i++) {
        $out[] = $base * $i;
    }

    return $out;
}

/**
 * @return array<string, mixed>
 */
function fc_catalog_get(): array
{
    $defaults = fc_catalog_defaults();
    $file = fc_theme_read_file();
    $saved = isset($file['catalog']) && is_array($file['catalog']) ? $file['catalog'] : [];

    return fc_catalog_normalize(array_merge($defaults, $saved));
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function fc_catalog_normalize(array $input): array
{
    $defaults = fc_catalog_defaults();
    $orderbyChoices = fc_catalog_orderby_choices();

    $categoryIds = [];
    if (isset($input['categoryIds']) && is_array($input['categoryIds'])) {
        foreach ($input['categoryIds'] as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $categoryIds[] = $n;
            }
        }
    }
    $categoryIds = array_values(array_unique($categoryIds));

    $attributeSlugs = [];
    if (isset($input['attributeSlugs']) && is_array($input['attributeSlugs'])) {
        foreach ($input['attributeSlugs'] as $slug) {
            $s = strtolower(trim((string) $slug));
            $s = preg_replace('/[^a-z0-9_\-]/', '', $s) ?? '';
            if ($s !== '') {
                $attributeSlugs[] = $s;
            }
        }
    }
    $attributeSlugs = array_values(array_unique($attributeSlugs));

    $priceMin = isset($input['priceMin']) ? (float) $input['priceMin'] : (float) $defaults['priceMin'];
    $priceMax = isset($input['priceMax']) ? (float) $input['priceMax'] : (float) $defaults['priceMax'];
    if ($priceMin < 0) {
        $priceMin = 0.0;
    }
    if ($priceMax < 1) {
        $priceMax = 1.0;
    }
    if ($priceMax <= $priceMin) {
        $priceMax = $priceMin + 1.0;
    }
    $priceMin = (float) floor($priceMin);
    $priceMax = (float) ceil($priceMax);

    $orderby = (string) ($input['defaultOrderby'] ?? $defaults['defaultOrderby']);
    if (!isset($orderbyChoices[$orderby])) {
        $orderby = (string) $defaults['defaultOrderby'];
    }

    $resultsPerPage = fc_catalog_clamp_results_per_page(
        $input['resultsPerPage'] ?? $defaults['resultsPerPage']
    );

    $clampCols = static function ($value, int $fallback): int {
        $n = (int) $value;
        if ($n < 1) {
            $n = $fallback;
        }
        if ($n > 6) {
            $n = 6;
        }

        return $n;
    };

    $title = trim((string) ($input['sidebarTitle'] ?? $defaults['sidebarTitle']));
    if ($title === '') {
        $title = (string) $defaults['sidebarTitle'];
    }
    if (mb_strlen($title) > 80) {
        $title = mb_substr($title, 0, 80);
    }

    $subtitle = trim((string) ($input['sidebarSubtitle'] ?? $defaults['sidebarSubtitle']));
    if (mb_strlen($subtitle) > 160) {
        $subtitle = mb_substr($subtitle, 0, 160);
    }

    return [
        'categoryIds' => $categoryIds,
        'attributeSlugs' => $attributeSlugs,
        'priceMin' => $priceMin,
        'priceMax' => $priceMax,
        'defaultOrderby' => $orderby,
        'resultsPerPage' => $resultsPerPage,
        'columnsDesktop' => $clampCols($input['columnsDesktop'] ?? $defaults['columnsDesktop'], 4),
        'columnsLaptop' => $clampCols($input['columnsLaptop'] ?? $defaults['columnsLaptop'], 3),
        'columnsTablet' => $clampCols($input['columnsTablet'] ?? $defaults['columnsTablet'], 2),
        'columnsMobile' => $clampCols($input['columnsMobile'] ?? $defaults['columnsMobile'], 1),
        'sidebarTitle' => $title,
        'sidebarSubtitle' => $subtitle,
    ];
}

/**
 * @param array<string, mixed> $catalog
 * @return array{ok:bool,catalog?:array<string,mixed>,error?:string}
 */
function fc_catalog_save(array $catalog): array
{
    $next = fc_catalog_normalize($catalog);

    $path = fc_theme_file_path();
    $dir = dirname($path);

    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'data/ directory is not writable.'];
    }
    if (file_exists($path) && !is_writable($path)) {
        return ['ok' => false, 'error' => 'theme.json is not writable.'];
    }

    $existing = fc_theme_read_file();
    $payload = [
        'colors' => isset($existing['colors']) && is_array($existing['colors']) ? $existing['colors'] : fc_theme_get(),
        'catalog' => $next,
        'updatedAt' => gmdate('c'),
    ];
    if (isset($existing['branding']) && is_array($existing['branding'])) {
        $payload['branding'] = $existing['branding'];
    }
    if (isset($existing['fenceColors']) && is_array($existing['fenceColors'])) {
        $payload['fenceColors'] = $existing['fenceColors'];
    }
    if (isset($existing['system']) && is_array($existing['system'])) {
        $payload['system'] = $existing['system'];
    }

    $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    $written = file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($written === false) {
        return ['ok' => false, 'error' => 'Unable to write settings file.'];
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);

        return ['ok' => false, 'error' => 'Unable to save theme.json.'];
    }

    // Bust lookup facet cache so category/attribute visibility updates promptly.
    if (function_exists('fc_lookup_cache_dir')) {
        $cacheDir = fc_lookup_cache_dir();
        foreach (glob($cacheDir . '/facets_*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    return [
        'ok' => true,
        'catalog' => $next,
    ];
}

/**
 * Merge helper used by other theme.json writers.
 *
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $existing
 * @return array<string, mixed>
 */
function fc_catalog_attach_to_theme_payload(array $payload, array $existing): array
{
    if (isset($existing['catalog']) && is_array($existing['catalog'])) {
        $payload['catalog'] = $existing['catalog'];
    }

    return $payload;
}

/**
 * WooCommerce category + attribute pick-lists for the admin UI.
 *
 * @return array{ok:bool,error?:string,categories?:list<array<string,mixed>>,attributes?:list<array{slug:string,label:string}>}
 */
function fc_catalog_wc_options(): array
{
    if (!function_exists('fc_lookup_bootstrap')) {
        require_once __DIR__ . '/lookup.php';
    }

    $boot = fc_lookup_bootstrap();
    if (empty($boot['ok'])) {
        return [
            'ok' => false,
            'error' => (string) ($boot['error'] ?? 'WordPress / WooCommerce is unavailable.'),
            'categories' => [],
            'attributes' => [],
        ];
    }

    $categories = fc_lookup_build_category_tree();
    $attributes = [];
    foreach (fc_lookup_attribute_definitions() as $def) {
        if (!is_array($def)) {
            continue;
        }
        $slug = (string) ($def['name'] ?? '');
        $label = (string) ($def['label'] ?? $slug);
        if ($slug === '') {
            continue;
        }
        $attributes[] = [
            'slug' => $slug,
            'label' => $label !== '' ? $label : $slug,
        ];
    }

    // Include colour taxonomy as a selectable attribute filter source too.
    $colorTax = fc_lookup_color_taxonomy();
    if (is_string($colorTax) && $colorTax !== '') {
        $slug = str_starts_with($colorTax, 'pa_') ? substr($colorTax, 3) : $colorTax;
        $has = false;
        foreach ($attributes as $row) {
            if (($row['slug'] ?? '') === $slug) {
                $has = true;
                break;
            }
        }
        if (!$has) {
            array_unshift($attributes, [
                'slug' => $slug,
                'label' => 'Color',
            ]);
        }
    }

    return [
        'ok' => true,
        'categories' => $categories,
        'attributes' => $attributes,
    ];
}

/**
 * @param bool $includeOptions
 * @return array<string, mixed>
 */
function fc_catalog_api_payload(bool $includeOptions = true): array
{
    $file = fc_theme_read_file();
    $catalog = fc_catalog_get();
    $payload = [
        'ok' => true,
        'catalog' => $catalog,
        'defaults' => fc_catalog_defaults(),
        'orderbyChoices' => fc_catalog_orderby_choices(),
        'resultsPerPageChoices' => fc_catalog_results_per_page_choices(
            (int) ($catalog['resultsPerPage'] ?? 12)
        ),
        'updatedAt' => isset($file['updatedAt']) ? (string) $file['updatedAt'] : null,
        'categories' => [],
        'attributes' => [],
        'optionsError' => '',
    ];

    if ($includeOptions) {
        $opts = fc_catalog_wc_options();
        $payload['categories'] = is_array($opts['categories'] ?? null) ? $opts['categories'] : [];
        $payload['attributes'] = is_array($opts['attributes'] ?? null) ? $opts['attributes'] : [];
        if (empty($opts['ok'])) {
            $payload['optionsError'] = (string) ($opts['error'] ?? 'Unable to load WooCommerce options.');
        }
    }

    return $payload;
}

/**
 * @param list<array<string, mixed>> $nodes
 * @param list<int> $allowedIds
 * @return list<array<string, mixed>>
 */
function fc_catalog_filter_category_tree(array $nodes, array $allowedIds): array
{
    if ($allowedIds === []) {
        return $nodes;
    }

    $allowed = array_fill_keys(array_map('intval', $allowedIds), true);
    $filter = static function (array $list) use (&$filter, $allowed): array {
        $out = [];
        foreach ($list as $node) {
            if (!is_array($node)) {
                continue;
            }
            $id = (int) ($node['id'] ?? 0);
            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            $keptChildren = $filter($children);
            if (isset($allowed[$id]) || $keptChildren !== []) {
                $node['children'] = $keptChildren;
                $out[] = $node;
            }
        }

        return $out;
    };

    return $filter($nodes);
}

/**
 * @param list<array<string, mixed>> $attributes
 * @param list<string> $allowedSlugs
 * @return list<array<string, mixed>>
 */
function fc_catalog_filter_attributes(array $attributes, array $allowedSlugs): array
{
    if ($allowedSlugs === []) {
        return $attributes;
    }

    $allowed = array_fill_keys(array_map('strval', $allowedSlugs), true);
    $out = [];
    foreach ($attributes as $attr) {
        if (!is_array($attr)) {
            continue;
        }
        $slug = (string) ($attr['name'] ?? $attr['slug'] ?? '');
        if ($slug !== '' && isset($allowed[$slug])) {
            $out[] = $attr;
        }
    }

    return $out;
}
