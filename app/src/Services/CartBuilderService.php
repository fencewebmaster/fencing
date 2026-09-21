<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\ArrayHelper;
use Fc\Admin\Helpers\FileHelper;

/**
 * Core BOM/SKU resolution + cart assembly.
 */
final class CartBuilderService
{
    /**
     * Supplier code for this request. config.php keys it by host, so the session is only a
     * shortcut - reading it unguarded is what made a missing site a fatal strtoupper(null).
     */
    private static function supplier(): string
    {
        $fromSession = $_SESSION['site']['supplier'] ?? null;
        if (is_string($fromSession) && $fromSession !== '') {
            return strtoupper($fromSession);
        }

        return SiteRegistryService::supplier((string) ($_SERVER['HTTP_HOST'] ?? ''), 'JG');
    }

    /**
     * @param array<int, array<string, mixed>> $data
     * @return array<int, array<string, mixed>>
     */
    public static function getProductSkus(array $data = []): array
    {
        $products = $skus = [];
        $theProducts = FileHelper::loadCsv('writable/products.csv');

        foreach ($data as $d) {
            $items = $d['items'];
            $color = $d['color'];
            $supplier = self::supplier();
            $styleKey = FenceCatalogService::productsCsvStyleForFence($d['slug']);

            foreach ($items as $item) {
                // Infill panels: no aluminium posts in BOM (see slat-fence-app.html infill matrix → No Post).
                if ($styleKey === 'slat_infill' && preg_match('/^(panel_post|raked_post)\+/', (string) $item['slug'])) {
                    continue;
                }

                $fenceHeightMm = isset($d['max_fence_height_mm']) ? (int) $d['max_fence_height_mm'] : 0;
                if ($fenceHeightMm <= 0) {
                    $fenceHeightMm = FenceCatalogService::plannerMaxFenceHeightMmForFenceSlug($d['slug']);
                }
                $lookupSlug = FenceCatalogService::slatCatalogSlugForPlannerLine(
                    $item['slug'],
                    $d['slug'],
                    $color,
                    $fenceHeightMm
                );
                $trySlugs = ($lookupSlug !== $item['slug']) ? [$lookupSlug, $item['slug']] : [$item['slug']];

                $filteredProduct = [];
                $resolvedSlug = $item['slug'];
                foreach ($trySlugs as $trySlug) {
                    $filteredProduct = array_filter($theProducts, function ($val) use ($trySlug, $supplier, $styleKey) {
                        return ($val['slug'] == $trySlug && $val['supplier'] == $supplier && $styleKey == $val['style']);
                    });
                    if ($filteredProduct) {
                        $resolvedSlug = $trySlug;
                        break;
                    }
                }

                if ($filteredProduct) {
                    $key = array_keys($filteredProduct)[0];

                    // Some rows may not have all color columns (csv header can evolve).
                    // Treat missing/unset values as OFF so SKU resolution stays robust.
                    $sku = isset($theProducts[$key][$color]) ? $theProducts[$key][$color] : 'off';
                    if (!is_string($sku) || $sku === '') {
                        $sku = 'off';
                    }

                    if ($key !== false && strtolower($sku) != 'off') {
                        $products[] = [
                            'sku' => $sku,
                            'qty' => (int) ($item['qty'] ?? 0),
                            'slug' => $resolvedSlug,
                            'fence' => $d['slug'],
                            'color' => $d['color'],
                            'product_name' => $theProducts[$key]['product'] ?? '',
                            'optional' => !empty($item['optional']),
                            'suggested_qty' => (int) ($item['suggested_qty'] ?? ($item['qty'] ?? 0)),
                        ];
                    }

                    $skus[] = $sku;
                }
            }
        }

        $_SESSION['custom_fence_products'] = $products;

        return $products;
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     */
    /**
     * Re-reads every cart line's title from products.csv.
     *
     * A saved quote stores its cart with the titles baked in, so reloading one brings back
     * whatever each line was called at the time — including the old WooCommerce variant name,
     * colour and all. products.csv is the current answer, so a restored cart has to look its
     * titles up again rather than trust what it carries.
     *
     * Matched on slug AND sku together first: several products.csv rows share a slug (one per
     * option or size) and a SKU can serve more than one of them, so neither alone is unique.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public static function refreshCartItemNames(array &$items): void
    {
        if ($items === []) {
            return;
        }

        $rows = FileHelper::loadCsv('writable/products.csv');
        if (!is_array($rows) || $rows === []) {
            return;
        }

        // Everything that is not one of the detail columns is a colour column holding a SKU.
        $detail = ['slug', 'product', 'description', 'supplier', 'style'];
        $byPair = [];
        $bySku = [];
        $bySlug = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['product'] ?? ''));
            if ($name === '') {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug !== '' && !isset($bySlug[$slug])) {
                $bySlug[$slug] = $name;
            }
            foreach ($row as $col => $value) {
                if (in_array($col, $detail, true)) {
                    continue;
                }
                $sku = trim((string) $value);
                if ($sku === '' || strtolower($sku) === 'off') {
                    continue;
                }
                $byPair[$slug . '|' . $sku] = $name;
                if (!isset($bySku[$sku])) {
                    $bySku[$sku] = $name;
                }
            }
        }

        foreach ($items as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            $slug = trim((string) ($item['slug'] ?? ''));
            $sku = trim((string) ($item['sku'] ?? ''));
            $name = $byPair[$slug . '|' . $sku] ?? ($bySku[$sku] ?? ($bySlug[$slug] ?? ''));
            if ($name !== '') {
                $items[$i]['name'] = $name;
            }
        }
    }

    public static function postProductSkus(array $cartItems = []): void
    {
        global $fences;
        $supplier = self::supplier();
        $items = $carts = [];
        $skus = self::getProductSkus($cartItems);

        $postQuery = [];

        foreach ($skus as $sku) {
            $postQuery[] = $sku;
        }

        $theProducts = FileHelper::loadCsv('writable/wc-products-' . $supplier . '.csv');
        if (!is_array($theProducts)) {
            $theProducts = [];
        }

        foreach ($postQuery as $query) {
            $key = array_search($query['sku'], array_column($theProducts, 'sku'), true);

            $image = WcProductCsvService::imageUrlForSku($query['sku'], $supplier);
            $wcName = '';
            if ($image === '' && $key !== false && isset($theProducts[$key])) {
                $image = WcProductCsvService::firstImageUrl($theProducts[$key]);
            }
            if ($key !== false && isset($theProducts[$key])) {
                $wcName = $theProducts[$key]['name'] ?? '';
            }

            $items[] = [
                'sku' => $query['sku'],
                'name' => $wcName,
                'slug' => $query['slug'],
                'color' => $query['color'],
                'fence' => $query['fence'],
                'image' => $image,
            ];
        }

        $count = count($items);
        $rand = rand(2, $count);

        $customFenceProducts = $_SESSION['custom_fence_products'];
        $optionalIncluded = self::cartOptionalIncludedSnapshot();

        $i = 1;
        foreach ($customFenceProducts as $customFenceProduct) {
            $key = array_search($customFenceProduct['sku'], array_column($items, 'sku'), true);

            $isOptional = !empty($customFenceProduct['optional']);
            $suggestedQty = (int) ($customFenceProduct['suggested_qty'] ?? 0);
            $optKey = self::optionalCartItemKey($customFenceProduct);
            $included = $isOptional && !empty($optionalIncluded[$optKey]);
            $lineQty = $isOptional
                ? ($included ? max($suggestedQty, 0) : 0)
                : (int) ($customFenceProduct['qty'] ?? 0);

            if (!$isOptional && empty($lineQty)) {
                $i++;
                continue;
            }

            if ($isOptional && $suggestedQty <= 0) {
                $i++;
                continue;
            }

            if ($lineQty || $isOptional) {
                $sku = $customFenceProduct['sku'];
                // products.csv first: it is the name an admin curates on System Products, and
                // Update Products takes the colour out of it, so a white fence's line does not
                // read "… Black" the way the raw WooCommerce variant name does. The catalogue
                // name only stands in where that column is empty.
                $displayName = trim((string) ($customFenceProduct['product_name'] ?? ''));
                if ($displayName === '') {
                    $displayName = ($key !== false && !empty($items[$key]['name']))
                        ? $items[$key]['name']
                        : $sku;
                }
                $displayImage = ($key !== false) ? ($items[$key]['image'] ?? '') : '';
                if ($displayImage === '') {
                    $displayImage = WcProductCsvService::imageUrlForSku($sku, $supplier);
                }
                if ($displayImage === '' && $key !== false) {
                    $displayImage = $items[$key]['image'] ?? '';
                }

                $carts[] = [
                    'name' => $displayName,
                    'image' => $displayImage,
                    'sku' => $sku,
                    'slug' => $customFenceProduct['slug'],
                    'color' => $customFenceProduct['color'],
                    'fence' => $customFenceProduct['fence'],
                    'fence_style_title' => FenceCatalogService::styleTitleFromSlug(
                        $customFenceProduct['fence'],
                        isset($fences) ? $fences : []
                    ),
                    '_dedupe_key' => implode(
                        '|',
                        [
                            (string) $sku,
                            FenceCatalogService::normalizePlannerFenceSlug((string) ($customFenceProduct['fence'] ?? '')),
                            (string) ($customFenceProduct['color'] ?? ''),
                        ]
                    ),
                    'stock' => $i == 1 || $i == $rand ? 'low' : 'yes',
                    'qty' => $lineQty,
                    'original_qty' => $lineQty,
                    'optional' => $isOptional,
                    'optional_included' => $included,
                    'optional_key' => $optKey,
                    'suggested_qty' => $suggestedQty,
                ];
                $i++;
            }
        }

        $cart = ArrayHelper::dedupeSummingFields($carts, '_dedupe_key', ['qty', 'original_qty', 'suggested_qty']);

        foreach ($cart as $ck => $crow) {
            if (!is_array($crow)) {
                continue;
            }
            if (!empty($crow['optional'])) {
                $optKey = !empty($crow['optional_key'])
                    ? (string) $crow['optional_key']
                    : self::optionalCartItemKey($crow);
                $included = !empty($optionalIncluded[$optKey]);
                $suggested = (int) ($crow['suggested_qty'] ?? 0);
                $cart[$ck]['optional_included'] = $included;
                $cart[$ck]['qty'] = $included ? $suggested : 0;
                $cart[$ck]['original_qty'] = $cart[$ck]['qty'];
            }
            if (array_key_exists('_dedupe_key', $crow)) {
                unset($cart[$ck]['_dedupe_key']);
            }
        }

        FenceCatalogService::sortCartItemsByFenceStyleAndName($cart, isset($fences) ? $fences : []);

        $_SESSION['fc_cart']['items'] = $cart;
    }

    /**
     * Stable key for optional cart line opt-in (Barr base_plate+dynabolts, etc.).
     *
     * @param array<string, mixed> $product
     */
    public static function optionalCartItemKey(array $product): string
    {
        return implode(
            '|',
            [
                FenceCatalogService::normalizePlannerFenceSlug((string) ($product['fence'] ?? '')),
                (string) ($product['color'] ?? ''),
                (string) ($product['slug'] ?? ''),
            ]
        );
    }

    /**
     * Remember optional line opt-in across cart rebuilds.
     *
     * @return array<string, bool>
     */
    public static function cartOptionalIncludedSnapshot(): array
    {
        $snapshot = [];
        if (empty($_SESSION['fc_cart']['items']) || !is_array($_SESSION['fc_cart']['items'])) {
            return $snapshot;
        }
        foreach ($_SESSION['fc_cart']['items'] as $row) {
            if (!is_array($row) || empty($row['optional'])) {
                continue;
            }
            $key = !empty($row['optional_key'])
                ? (string) $row['optional_key']
                : self::optionalCartItemKey($row);
            if ($key !== '') {
                $snapshot[$key] = !empty($row['optional_included']);
            }
        }

        return $snapshot;
    }

    /**
     * Count cart lines with qty > 0 (optional lines excluded when not added).
     *
     * @param array<int, array<string, mixed>> $items
     */
    public static function cartIncludedItemCount(array $items): int
    {
        $count = 0;
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((int) ($row['qty'] ?? 0) > 0) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Normalize JSON / dates / scalars for planner form data.
     */
    public static function convertInputs(mixed $val = ''): mixed
    {
        if (is_array($val)) {
            return json_encode($val);
        }

        // Everything below needs a string: json_decode() and preg_match() both reject anything else
        // under strict_types, and callers legitimately hand over a record field or a POST key that
        // was never set (CheckoutCartModel::applyProjectDetails passes null when a quote carries no
        // colour rows). Nothing else can be decoded, so it comes back untouched.
        if (!is_string($val)) {
            return $val;
        }

        // Digit strings with a leading zero must stay strings (json_decode would drop the zero).
        if (preg_match('/^0\d+$/', trim($val))) {
            return trim($val);
        }

        if ($data = json_decode($val, true)) {
            return $data;
        }

        if (preg_match("/\d{4}\-\d{2}-\d{2}/", $val) || preg_match("/\d{2}\-\d{2}-\d{4}/", $val)) {
            return $val;
        }

        return $val;
    }

    /**
     * Store mobile as a string; never coerce to number (leading 0 must be preserved).
     */
    public static function normalizeMobileForStorage(mixed $mobile): string
    {
        if ($mobile === null || $mobile === '') {
            return '';
        }

        $raw = trim((string) $mobile);
        if ($raw === '') {
            return '';
        }

        // The planner displays the number spaced (0412 345 678) since the validation rework, and a
        // direct POST can still arrive as +61 4.. - both land here as the bare digits every row
        // written before that already holds.
        $digits = (string) preg_replace('/[^0-9]/', '', $raw);
        if ($digits === '') {
            return $raw;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '61')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Group a stored mobile the way the planner field shows it: 0412 345 678. The column holds bare
     * digits, so a read-only view of the number would otherwise read differently from the input the
     * customer edits it in. Short or non-numeric values group as far as they go, matching the mask.
     */
    public static function formatMobileForDisplay(mixed $mobile): string
    {
        $digits = self::normalizeMobileForStorage($mobile);
        if ($digits === '' || !ctype_digit($digits)) {
            return $digits;
        }

        $digits = substr($digits, 0, 10);
        $length = strlen($digits);

        if ($length <= 4) {
            return $digits;
        }

        if ($length <= 7) {
            return substr($digits, 0, 4) . ' ' . substr($digits, 4);
        }

        return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function deliverOptions(): array
    {
        return [
            [
                'value' => 'shipping_1',
                'label' => 'Warehouse Pickup',
                'price' => 0,
                'default' => true,
            ],
            [
                'value' => 'shipping_2',
                'label' => 'Deliver to Site (Metro $89)',
                'price' => 89,
                'default' => false,
            ],
            [
                'value' => 'shipping_3',
                'label' => 'Deliver to Site (Rural - $TBA)',
                'price' => 0,
                'default' => false,
            ],
        ];
    }
}
