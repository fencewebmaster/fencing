<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Debug Mode only: what the store charges for the Item List & Cart rows.
 *
 * FC holds no prices - a total is only ever made in the store's cart - so this reads the store's
 * WooCommerce tables over the lookup page's connection (never loads WordPress, never writes) and
 * applies the store's own tax settings, so every figure is the one its cart page shows.
 */
final class CartDebugPriceService
{
    /**
     * Price and sub total for every cart row and the totals of the rows that are in the cart, as the store's
     * cart page shows them: with tax inside when it displays its cart that way (`taxIncluded`), without when not.
     * An optional row not yet added is priced at its suggested quantity and stays out of the totals.
     *
     * @param array<int|string, mixed> $items $_SESSION['fc_cart']['items']
     * @return array{
     *     rows: array<int, array{price:?float,qty:int,subtotal:float,pending:bool}>,
     *     subtotal: float,
     *     tax: float,
     *     total: float,
     *     taxIncluded: bool,
     *     missing: int,
     *     error: string
     * }
     */
    public static function forCart(array $items): array
    {
        $out = [
            'rows' => [], 'subtotal' => 0.0, 'tax' => 0.0, 'total' => 0.0,
            'taxIncluded' => false, 'missing' => 0, 'error' => '',
        ];

        $skus = [];
        foreach ($items as $item) {
            $sku = self::skuKey($item);
            if ($sku !== '') {
                $skus[$sku] = true;
            }
        }

        try {
            $products = self::productsBySku(array_map('strval', array_keys($skus)));
            $tax = self::taxSettings();
        } catch (\Throwable $e) {
            // Debug output must never take the cart down with it.
            error_log('FC CartDebugPriceService: ' . $e->getMessage());
            $out['error'] = 'Store prices could not be read.';

            return $out;
        }

        $out['taxIncluded'] = $tax['displayIncl'];

        $subtotal = 0.0;
        $taxExact = 0.0;
        $taxByLine = 0.0;
        // Rows are numbered in cart order, as the table numbers them, whatever keys the session array carries.
        $index = -1;
        foreach ($items as $item) {
            $index++;
            if (!is_array($item)) {
                continue;
            }

            $pending = !empty($item['optional']) && empty($item['optional_included']);
            $qty = $pending ? (int) ($item['suggested_qty'] ?? 0) : (int) ($item['qty'] ?? 0);
            $product = $products[self::skuKey($item)] ?? null;

            if ($product === null) {
                $out['rows'][$index] = ['price' => null, 'qty' => $qty, 'subtotal' => 0.0, 'pending' => $pending];
                if (!$pending && $qty > 0) {
                    $out['missing']++;
                }
                continue;
            }

            $rate = $tax['enabled'] && $product['taxable'] ? (float) ($tax['rates'][$product['taxClass']] ?? 0.0) : 0.0;
            $unit = $tax['inclusive'] ? $product['price'] / (1 + $rate / 100) : $product['price'];
            $lineSub = $unit * $qty;
            $lineTax = $lineSub * $rate / 100;

            // The tax a figure carries on the cart page: none when the cart shows prices without it, and
            // rounded on the figure itself unless the store rounds at the subtotal.
            $shownTax = static function (float $amount) use ($tax, $rate): float {
                if (!$tax['displayIncl']) {
                    return 0.0;
                }

                return $tax['roundAtSubtotal'] ? $amount * $rate / 100 : round($amount * $rate / 100, 2);
            };

            $out['rows'][$index] = [
                'price' => round($unit + $shownTax($unit), 2),
                'qty' => $qty,
                'subtotal' => round($lineSub + $shownTax($lineSub), 2),
                'pending' => $pending,
            ];

            if (!$pending && $qty > 0) {
                $subtotal += $lineSub;
                $taxExact += $lineTax;
                $taxByLine += round($lineTax, 2);
            }
        }

        $out['tax'] = $tax['roundAtSubtotal'] ? round($taxExact, 2) : round($taxByLine, 2);
        $out['total'] = round(round($subtotal, 2) + $out['tax'], 2);
        // With tax shown inside, the cart's Subtotal line already carries it, as its Total does.
        $out['subtotal'] = $tax['displayIncl'] ? $out['total'] : round($subtotal, 2);

        return $out;
    }

    /** SKUs compare case-insensitively in the store's tables, so the lookup key does too. */
    private static function skuKey(mixed $item): string
    {
        return is_array($item) ? strtoupper(trim((string) ($item['sku'] ?? ''))) : '';
    }

    /**
     * Published products and variations for these SKUs, keyed by upper-case SKU. `_price` is the price the
     * cart charges (the sale price while one runs); a product without one cannot be bought and is left out.
     *
     * @param list<string> $skus
     * @return array<string, array{price:float,taxable:bool,taxClass:string}>
     */
    private static function productsBySku(array $skus): array
    {
        if ($skus === []) {
            return [];
        }

        $posts = ProductLookupQueryService::table('posts');
        $meta = ProductLookupQueryService::table('postmeta');
        $in = implode(',', array_fill(0, count($skus), '?'));

        $stmt = ProductLookupQueryService::pdo()->prepare(
            "SELECT s.meta_value AS sku,
                    MAX(CASE WHEN m.meta_key = '_price' THEN m.meta_value END) AS price,
                    MAX(CASE WHEN m.meta_key = '_tax_status' THEN m.meta_value END) AS tax_status,
                    MAX(CASE WHEN m.meta_key = '_tax_class' THEN m.meta_value END) AS tax_class,
                    MAX(pm.meta_value) AS parent_tax_class
             FROM `{$meta}` s
             INNER JOIN `{$posts}` p ON p.ID = s.post_id
                 AND p.post_type IN ('product', 'product_variation') AND p.post_status = 'publish'
             LEFT JOIN `{$meta}` m ON m.post_id = p.ID AND m.meta_key IN ('_price', '_tax_status', '_tax_class')
             LEFT JOIN `{$meta}` pm ON pm.post_id = p.post_parent AND pm.meta_key = '_tax_class'
             WHERE s.meta_key = '_sku' AND s.meta_value IN ({$in})
             GROUP BY s.meta_value, p.ID
             ORDER BY p.ID DESC"
        );
        $stmt->execute($skus);

        $out = [];
        foreach ($stmt as $row) {
            $price = trim((string) ($row['price'] ?? ''));
            if ($price === '' || !is_numeric($price)) {
                continue;
            }

            // A variation's class can read 'parent': the product it belongs to holds the real one.
            $class = (string) ($row['tax_class'] ?? '');
            if ($class === 'parent') {
                $class = (string) ($row['parent_tax_class'] ?? '');
            }

            // Rows arrive highest ID first, so the oldest product keeps a SKU the store holds twice.
            $out[strtoupper(trim((string) $row['sku']))] = [
                'price' => (float) $price,
                'taxable' => !in_array((string) ($row['tax_status'] ?? 'taxable'), ['none', 'shipping'], true),
                'taxClass' => $class,
            ];
        }

        return $out;
    }

    /**
     * The store's tax switches and its rate per tax class ('' is the standard class), picked by the shop's
     * base address as the cart does before a customer has entered theirs.
     *
     * @return array{enabled:bool,inclusive:bool,displayIncl:bool,roundAtSubtotal:bool,rates:array<string,float>}
     */
    private static function taxSettings(): array
    {
        $enabled = ProductLookupQueryService::option('woocommerce_calc_taxes', 'no') === 'yes';
        $rates = [];

        if ($enabled) {
            $base = explode(':', ProductLookupQueryService::option('woocommerce_default_country'), 2);
            $stmt = ProductLookupQueryService::pdo()->prepare(
                'SELECT tax_rate_class, tax_rate FROM `' . ProductLookupQueryService::table('woocommerce_tax_rates') . '`'
                . " WHERE tax_rate_country IN ('', ?) AND tax_rate_state IN ('', ?)"
                . ' ORDER BY tax_rate_priority, tax_rate_order'
            );
            $stmt->execute([(string) ($base[0] ?? ''), (string) ($base[1] ?? '')]);
            foreach ($stmt as $row) {
                $class = (string) ($row['tax_rate_class'] ?? '');
                if (!isset($rates[$class])) {
                    $rates[$class] = (float) $row['tax_rate'];
                }
            }
        }

        return [
            'enabled' => $enabled,
            'inclusive' => $enabled && ProductLookupQueryService::option('woocommerce_prices_include_tax', 'no') === 'yes',
            'displayIncl' => $enabled && ProductLookupQueryService::option('woocommerce_tax_display_cart', 'excl') === 'incl',
            'roundAtSubtotal' => ProductLookupQueryService::option('woocommerce_tax_round_at_subtotal', 'no') === 'yes',
            'rates' => $rates,
        ];
    }
}
