<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

use Fc\Admin\Services\ProductLookupService;

/**
 * FC Catalog settings — Product Lookup configuration (saved to writable/theme.json as catalog).
 */
final class CatalogSettings
{
    /** Sentinel "per page" value meaning "show every matching product on one page". */
    public const ALL_PER_PAGE = -1;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'categoryIds' => [],
            'attributeSlugs' => [],
            'priceMin' => 0,
            'priceMax' => 30000,
            'defaultOrderby' => 'default',
            'resultsPerPage' => 12,
            'resultsPerPageAllEnabled' => false,
            'resultsPerPageListSize' => 5,
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
    public static function orderbyChoices(): array
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
    public static function clampResultsPerPage($value): int
    {
        return self::clampInt($value, 1, 100);
    }

    /**
     * Clamp the admin "Per page list size" value (how many multiples of the base are offered).
     */
    public static function clampResultsPerPageListSize($value): int
    {
        return self::clampInt($value, 1, 10);
    }

    /**
     * @param mixed $value
     */
    private static function clampInt($value, int $min, int $max): int
    {
        $n = (int) $value;
        if ($n < $min) {
            $n = $min;
        }
        if ($n > $max) {
            $n = $max;
        }

        return $n;
    }

    /**
     * Product Lookup "Per page" options: base × 1 … × listSize, plus ALL_PER_PAGE
     * when the admin has enabled the "All" option.
     * e.g. base 20, listSize 5 → 20, 40, 60, 80, 100[, -1]
     *
     * @return list<int>
     */
    public static function resultsPerPageChoices(
        ?int $base = null,
        ?bool $allEnabled = null,
        ?int $listSize = null
    ): array {
        if ($base === null || $allEnabled === null || $listSize === null) {
            $settings = self::get();
            if ($base === null) {
                $base = (int) ($settings['resultsPerPage'] ?? 12);
            }
            if ($allEnabled === null) {
                $allEnabled = !empty($settings['resultsPerPageAllEnabled']);
            }
            if ($listSize === null) {
                $listSize = (int) ($settings['resultsPerPageListSize'] ?? 5);
            }
        }
        $base = self::clampResultsPerPage($base);
        $listSize = self::clampResultsPerPageListSize($listSize);
        $out = [];
        for ($i = 1; $i <= $listSize; $i++) {
            $out[] = $base * $i;
        }
        if ($allEnabled) {
            $out[] = self::ALL_PER_PAGE;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $defaults = self::defaults();
        $saved = ThemeSettings::section('catalog');

        return self::normalize(array_merge($defaults, $saved));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $defaults = self::defaults();
        $orderbyChoices = self::orderbyChoices();

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

        $resultsPerPage = self::clampResultsPerPage(
            $input['resultsPerPage'] ?? $defaults['resultsPerPage']
        );
        $resultsPerPageAllEnabled = array_key_exists('resultsPerPageAllEnabled', $input)
            ? !empty($input['resultsPerPageAllEnabled'])
            : (bool) $defaults['resultsPerPageAllEnabled'];
        $resultsPerPageListSize = self::clampResultsPerPageListSize(
            $input['resultsPerPageListSize'] ?? $defaults['resultsPerPageListSize']
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
            'resultsPerPageAllEnabled' => $resultsPerPageAllEnabled,
            'resultsPerPageListSize' => $resultsPerPageListSize,
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
    public static function save(array $catalog): array
    {
        $next = self::normalize($catalog);

        $result = ThemeSettings::writeSection('catalog', $next);
        if (!$result['ok']) {
            return $result;
        }

        // Bust lookup facet cache so category/attribute visibility updates promptly.
        $cacheDir = ProductLookupService::cacheDir();
        foreach (glob($cacheDir . '/facets_*.json') ?: [] as $file) {
            @unlink($file);
        }

        return [
            'ok' => true,
            'catalog' => $next,
        ];
    }

    /**
     * WooCommerce category + attribute pick-lists for the admin UI.
     *
     * @return array{ok:bool,error?:string,categories?:list<array<string,mixed>>,attributes?:list<array{slug:string,label:string}>}
     */
    public static function wcOptions(): array
    {
        $boot = ProductLookupService::bootstrap();
        if (empty($boot['ok'])) {
            return [
                'ok' => false,
                'error' => (string) ($boot['error'] ?? 'WordPress / WooCommerce is unavailable.'),
                'categories' => [],
                'attributes' => [],
            ];
        }

        $categories = ProductLookupService::buildCategoryTree();
        $attributes = [];
        foreach (ProductLookupService::attributeDefinitions() as $def) {
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
        $colorTax = ProductLookupService::colorTaxonomy();
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
    public static function apiPayload(bool $includeOptions = true): array
    {
        $catalog = self::get();
        $payload = [
            'ok' => true,
            'catalog' => $catalog,
            'defaults' => self::defaults(),
            'orderbyChoices' => self::orderbyChoices(),
            'resultsPerPageChoices' => self::resultsPerPageChoices(
                (int) ($catalog['resultsPerPage'] ?? 12),
                !empty($catalog['resultsPerPageAllEnabled']),
                (int) ($catalog['resultsPerPageListSize'] ?? 5)
            ),
            'updatedAt' => ThemeSettings::updatedAt(),
            'categories' => [],
            'attributes' => [],
            'optionsError' => '',
        ];

        if ($includeOptions) {
            $opts = self::wcOptions();
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
    public static function filterCategoryTree(array $nodes, array $allowedIds): array
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
    public static function filterAttributes(array $attributes, array $allowedSlugs): array
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
}
