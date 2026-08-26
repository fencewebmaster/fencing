<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Core\FrontendApplication;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\CatalogSettings;
use Fc\Admin\Services\ProductLookupService;

/**
 * Data layer for the public product lookup page (/lookup).
 */
final class LookupPageModel
{
    /**
     * Build every value app/views/frontend/lookup/index.php renders from.
     *
     * @param array<string, mixed> $query Raw $_GET.
     * @return array{page:array<string,mixed>,catalog:array<string,mixed>,title:string,appBase:string,logoUrl:string,toolbar:array<string,mixed>}
     */
    public static function build(array $query): array
    {
        $page    = ProductLookupService::buildPage($query);
        $catalog = is_array($page['catalog'] ?? null) ? $page['catalog'] : CatalogSettings::get();

        $title = trim((string) ($catalog['sidebarTitle'] ?? 'Product Lookup'));
        if ($title === '') {
            $title = 'Product Lookup';
        }

        $appBase = self::appBase();

        return [
            'page'       => $page,
            'catalog'    => $catalog,
            'title'      => $title,
            'appBase'    => $appBase,
            'logoUrl'    => BrandingSettings::logoUrl($appBase),
            'toolbar'    => self::toolbarData($page),
            'shell'      => self::shellData($page, $catalog, $title),
            'filters'    => self::filtersData($page, $catalog),
            'results'    => self::resultsData($page),
            'pager'      => self::pagerData($page),
            'quickView'  => self::quickViewData($page),
            'emptyState' => self::emptyStateData($page),
        ];
    }

    /**
     * View-model for lookup/page.php's shell chrome (layout + sidebar heading).
     *
     * @param array<string, mixed> $page
     * @param array<string, mixed> $catalog
     * @return array<string, mixed>
     */
    private static function shellData(array $page, array $catalog, string $title): array
    {
        $req = is_array($page['request'] ?? null) ? $page['request'] : [];

        return [
            'layout'           => (($req['layout'] ?? 'grid') === 'list') ? 'list' : 'grid',
            'sidebar_title'    => $title,
            'sidebar_subtitle' => trim((string) ($catalog['sidebarSubtitle'] ?? 'Search the live catalog')),
        ];
    }

    /**
     * View-model for lookup/filters.php — price-slider math, selected filters,
     * group open/count state and every clear URL. The template keeps only its
     * rendering closures ($renderCats/$groupHead) and pure aliases of these keys.
     *
     * @param array<string, mixed> $page
     * @param array<string, mixed> $catalog
     * @return array<string, mixed>
     */
    private static function filtersData(array $page, array $catalog): array
    {
        $req = is_array($page['request'] ?? null) ? $page['request'] : [];
        $facets = is_array($page['facets'] ?? null) ? $page['facets'] : [];

        $price = is_array($facets['price'] ?? null) ? $facets['price'] : ['min' => 0, 'max' => 0];
        $priceMinBound = (float) ($price['min'] ?? 0);
        $priceMaxBound = (float) ($price['max'] ?? 0);
        if ($priceMaxBound <= $priceMinBound) {
            $priceMaxBound = $priceMinBound + 1;
        }
        $minPriceVal = $req['min_price'] !== null ? (string) $req['min_price'] : '';
        $maxPriceVal = $req['max_price'] !== null ? (string) $req['max_price'] : '';

        $currency = ProductLookupService::currencySymbol();
        $sliderMin = $minPriceVal !== '' ? (float) $minPriceVal : $priceMinBound;
        $sliderMax = $maxPriceVal !== '' ? (float) $maxPriceVal : $priceMaxBound;
        $sliderMin = max($priceMinBound, min($priceMaxBound, $sliderMin));
        $sliderMax = max($priceMinBound, min($priceMaxBound, $sliderMax));
        if ($sliderMin > $sliderMax) {
            $tmp = $sliderMin;
            $sliderMin = $sliderMax;
            $sliderMax = $tmp;
        }
        $span = max(1.0, $priceMaxBound - $priceMinBound);
        $fmtPrice = static function (float $n) use ($currency): string {
            return $currency . number_format($n, 0);
        };

        $colorFacet = is_array($facets['color'] ?? null) ? $facets['color'] : ['terms' => []];
        $selectedColors = array_map('intval', $req['color'] ?? []);
        $selectedCats = array_map('intval', $req['cat'] ?? []);
        $selectedTags = array_map('intval', $req['tag'] ?? []);
        $selectedStock = array_map('strval', $req['stock'] ?? []);
        $selectedAttrs = is_array($req['attr'] ?? null) ? $req['attr'] : [];

        $priceActive = ($req['min_price'] !== null || $req['max_price'] !== null) ? 1 : 0;
        $saleActive = (($req['sale'] ?? '') !== '') ? 1 : 0;
        $featuredActive = !empty($req['featured']) ? 1 : 0;

        $clear = static function (array $overrides) use ($req): string {
            $overrides['page'] = null;
            $overrides['view'] = null;

            return ProductLookupService::url($req, $overrides);
        };

        $attrGroups = [];
        foreach (($facets['attributes'] ?? []) as $attrGroup) {
            if (!is_array($attrGroup)) {
                continue;
            }
            $attrName = (string) ($attrGroup['name'] ?? '');
            $attrLabel = (string) ($attrGroup['label'] ?? $attrName);
            $terms = is_array($attrGroup['terms'] ?? null) ? $attrGroup['terms'] : [];
            if ($attrName === '' || $terms === []) {
                continue;
            }
            $sel = array_map('intval', $selectedAttrs[$attrName] ?? []);
            $attrsWithout = $selectedAttrs;
            unset($attrsWithout[$attrName]);
            $attrGroups[] = [
                'name'      => $attrName,
                'label'     => $attrLabel,
                'terms'     => $terms,
                'selected'  => $sel,
                'count'     => count($sel),
                'open'      => $sel !== [],
                'clear_url' => $clear(['attr' => $attrsWithout !== [] ? $attrsWithout : null]),
            ];
        }

        $availCount = count($selectedStock) + $saleActive + $featuredActive;

        return [
            'action_url'      => ProductLookupService::basePath(),
            'per_page_hidden' => (int) ($req['per_page'] ?? (int) ($catalog['resultsPerPage'] ?? 12)),
            'facets'          => $facets,
            'price' => [
                'min_bound'       => $priceMinBound,
                'max_bound'       => $priceMaxBound,
                'min_val'         => $minPriceVal,
                'max_val'         => $maxPriceVal,
                'slider_min'      => $sliderMin,
                'slider_max'      => $sliderMax,
                'pct_min'         => (($sliderMin - $priceMinBound) / $span) * 100,
                'pct_max'         => (($sliderMax - $priceMinBound) / $span) * 100,
                'currency'        => $currency,
                'display_min'     => $fmtPrice($sliderMin),
                'display_max'     => $fmtPrice($sliderMax),
                'bound_min_label' => $fmtPrice($priceMinBound),
                'bound_max_label' => $fmtPrice($priceMaxBound),
            ],
            'selected_cats'      => $selectedCats,
            'selected_colors'    => $selectedColors,
            'selected_tags'      => $selectedTags,
            'selected_stock'     => $selectedStock,
            'cats_count'         => count($selectedCats),
            'colors_count'       => count($selectedColors),
            'tags_count'         => count($selectedTags),
            'price_active_count' => $priceActive,
            'avail_count'        => $availCount,
            'color_terms'        => is_array($colorFacet['terms'] ?? null) ? $colorFacet['terms'] : [],
            'color_open'         => $selectedColors !== [],
            'avail_open'         => $availCount > 0,
            'tags_open'          => $selectedTags !== [],
            'attr_groups'        => $attrGroups,
            'clear_urls' => [
                'q'     => $clear(['q' => null]),
                'cats'  => $clear(['cat' => null]),
                'price' => $clear(['min_price' => null, 'max_price' => null]),
                'color' => $clear(['color' => null]),
                'avail' => $clear(['stock' => null, 'sale' => null, 'featured' => null]),
                'tags'  => $clear(['tag' => null]),
            ],
            'has_active'    => !empty($page['has_active_filters']),
            'clear_all_url' => (string) ($page['clear_url'] ?? ProductLookupService::basePath()),
        ];
    }

    /**
     * View-model for lookup/results.php — layout, badge labels, and products
     * decorated with their quick-view URL (built here, not per card in the loop).
     *
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private static function resultsData(array $page): array
    {
        $req = is_array($page['request'] ?? null) ? $page['request'] : [];
        $products = [];
        foreach ((is_array($page['products'] ?? null) ? $page['products'] : []) as $product) {
            if (!is_array($product)) {
                continue;
            }
            $id = (int) ($product['id'] ?? 0);
            $slug = (string) ($product['slug'] ?? '');
            $product['quick_url'] = ProductLookupService::url($req, ['view' => $slug !== '' ? $slug : (string) $id]);
            $products[] = $product;
        }

        return [
            'layout'   => (($req['layout'] ?? 'grid') === 'list') ? 'list' : 'grid',
            'products' => $products,
            'badge_labels' => [
                'sale' => 'Sale',
                'featured' => 'Featured',
                'new' => 'New',
                'outofstock' => 'Out of stock',
            ],
        ];
    }

    /**
     * View-model for lookup/footer.php — result summary numbers plus the fully
     * resolved pagination link set (window of 2 around the current page).
     *
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private static function pagerData(array $page): array
    {
        $req = is_array($page['request'] ?? null) ? $page['request'] : [];
        $pages = (int) ($page['pages'] ?? 0);
        $current = (int) ($req['page'] ?? 1);

        $pageUrl = static function (int $p) use ($req): string {
            return ProductLookupService::url($req, ['page' => $p, 'view' => null]);
        };

        $window = 2;
        $start = max(1, $current - $window);
        $end = min($pages, $current + $window);

        $windowLinks = [];
        for ($p = $start; $p <= $end; $p++) {
            $windowLinks[] = [
                'num'     => $p,
                'current' => $p === $current,
                'url'     => $p === $current ? '' : $pageUrl($p),
            ];
        }

        return [
            'from'          => (int) ($page['from'] ?? 0),
            'to'            => (int) ($page['to'] ?? 0),
            'total'         => (int) ($page['total'] ?? 0),
            'pages'         => $pages,
            'current'       => $current,
            'prev_url'      => $current > 1 ? $pageUrl($current - 1) : '',
            'next_url'      => $current < $pages ? $pageUrl($current + 1) : '',
            'show_first'    => $start > 1,
            'first_url'     => $start > 1 ? $pageUrl(1) : '',
            'first_ellipsis' => $start > 2,
            'window'        => $windowLinks,
            'show_last'     => $end < $pages,
            'last_url'      => $end < $pages ? $pageUrl($pages) : '',
            'last_ellipsis' => $end < $pages - 1,
        ];
    }

    /**
     * View-model for lookup/quick-view.php — every derived scalar the modal
     * shows, with related products decorated with their quick-view URLs. The
     * template keeps its $renderRow rendering closure and pure aliases.
     *
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private static function quickViewData(array $page): array
    {
        $qv = is_array($page['quick_view'] ?? null) ? $page['quick_view'] : [];
        $req = is_array($page['request'] ?? null) ? $page['request'] : [];

        $priceHtml = (string) ($qv['price_html'] ?? '');
        if (($qv['sale_price'] ?? '') !== '') {
            $pricePlain = (string) $qv['sale_price'];
        } elseif (($qv['regular_price'] ?? '') !== '') {
            $pricePlain = (string) $qv['regular_price'];
        } else {
            $pricePlain = trim(html_entity_decode(strip_tags($priceHtml), ENT_QUOTES, 'UTF-8'));
        }

        $categories = is_array($qv['categories'] ?? null) ? $qv['categories'] : [];
        $tags = is_array($qv['tags'] ?? null) ? $qv['tags'] : [];
        $qvDescription = trim((string) ($qv['description'] ?? ''));

        $related = [];
        foreach (($qv['related'] ?? []) as $r) {
            if (!is_array($r)) {
                continue;
            }
            $rid = (int) ($r['id'] ?? 0);
            $rSlug = (string) ($r['slug'] ?? '');
            $r['quick_url'] = ProductLookupService::url($req, ['view' => $rSlug !== '' ? $rSlug : (string) $rid]);
            $related[] = $r;
        }

        return [
            'qv'           => $qv,
            'close_url'    => ProductLookupService::url($req, ['view' => null]),
            'gallery'      => is_array($qv['gallery'] ?? null) ? $qv['gallery'] : [],
            'permalink'    => (string) ($qv['permalink'] ?? ''),
            'sku'          => (string) ($qv['sku'] ?? ''),
            'name'         => (string) ($qv['name'] ?? ''),
            'categories'   => $categories,
            'tags'         => $tags,
            'attributes'   => is_array($qv['attributes'] ?? null) ? $qv['attributes'] : [],
            'description'  => $qvDescription,
            'rating'       => (float) ($qv['rating'] ?? 0),
            'stock_status' => (string) ($qv['stock_status'] ?? ''),
            'stock_label'  => (string) ($qv['stock_label'] ?? ''),
            'price_html'   => $priceHtml,
            'price_plain'  => $pricePlain,
            'category_names' => array_values(array_filter(array_map(static function ($c) {
                return is_array($c) ? trim((string) ($c['name'] ?? '')) : '';
            }, $categories))),
            'tag_names' => array_values(array_filter(array_map(static function ($t) {
                return is_array($t) ? trim((string) ($t['name'] ?? '')) : '';
            }, $tags))),
            'description_plain' => trim(preg_replace(
                '/\s+/u',
                ' ',
                html_entity_decode(strip_tags($qvDescription), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ) ?? ''),
            'related' => $related,
        ];
    }

    /**
     * View-model for lookup/empty.php.
     *
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private static function emptyStateData(array $page): array
    {
        return [
            'is_boot_error' => empty($page['ok']),
            'clear_url'     => (string) ($page['clear_url'] ?? ProductLookupService::basePath()),
            'error'         => (string) ($page['error'] ?? 'Could not load WordPress / WooCommerce.'),
            'has_active'    => !empty($page['has_active_filters']),
        ];
    }

    /**
     * View-model for lookup/toolbar.php (sort / layout / per-page controls). Kept
     * here rather than in ProductLookupService::buildPage(): that payload's shape is
     * cached under schema-versioned keys, and presentation-only values don't belong in it.
     *
     * @param array<string, mixed> $page ProductLookupService::buildPage() result.
     * @return array<string, mixed>
     */
    private static function toolbarData(array $page): array
    {
        $req = is_array($page['request'] ?? null) ? $page['request'] : [];
        $layout = (($req['layout'] ?? 'grid') === 'list') ? 'list' : 'grid';
        $catalog = is_array($page['catalog'] ?? null) ? $page['catalog'] : [];

        $defaultPerPage = CatalogSettings::clampResultsPerPage($catalog['resultsPerPage'] ?? 12);
        $perPageOptions = is_array($page['per_page_options'] ?? null)
            ? $page['per_page_options']
            : CatalogSettings::resultsPerPageChoices($defaultPerPage);
        // The "all" sentinel is an admin-side option; the public toolbar never offers it.
        $perPageOptions = array_values(array_filter(
            $perPageOptions,
            static fn($opt): bool => (int) $opt !== CatalogSettings::ALL_PER_PAGE
        ));

        $currentPerPage = (int) ($req['per_page'] ?? $defaultPerPage);
        if (!in_array($currentPerPage, array_map('intval', $perPageOptions), true)) {
            $currentPerPage = $defaultPerPage;
        }

        return [
            'layout'           => $layout,
            'orderby_options'  => is_array($page['orderby_options'] ?? null) ? $page['orderby_options'] : [],
            'per_page_options' => $perPageOptions,
            'current_per_page' => $currentPerPage,
            'grid_url'         => ProductLookupService::url($req, ['layout' => 'grid', 'view' => null, 'page' => null]),
            'list_url'         => ProductLookupService::url($req, ['layout' => 'list', 'view' => null, 'page' => null]),
            'action_url'       => ProductLookupService::basePath(),
        ];
    }

    /**
     * Web path the app is mounted at, used to build asset URLs (no trailing slash).
     */
    public static function appBase(): string
    {
        return FrontendApplication::basePath();
    }
}
