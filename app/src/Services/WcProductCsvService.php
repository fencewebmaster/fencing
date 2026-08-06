<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\FileHelper;

/**
 * WooCommerce product CSV row lookups for cart images (config/helpers.php migration).
 */
final class WcProductCsvService
{
    /**
     * WooCommerce product CSV rows for a supplier (cached per request).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function csvRows(?string $supplier = null): array
    {
        static $cache = [];

        if ($supplier === null) {
            $supplier = isset($_SESSION['site']['supplier']) ? strtoupper((string) $_SESSION['site']['supplier']) : 'JG';
        } else {
            $supplier = strtoupper($supplier);
        }

        if (!isset($cache[$supplier])) {
            $rows = FileHelper::loadCsv('writable/wc-products-' . $supplier . '.csv');
            $cache[$supplier] = is_array($rows) ? $rows : [];
        }

        return $cache[$supplier];
    }

    /**
     * Find a WooCommerce CSV row by SKU (site supplier first, then JG/GO fallback).
     *
     * @return array<string, mixed>|null
     */
    public static function rowBySku(string $sku, ?string $supplier = null): ?array
    {
        $sku = trim($sku);
        if ($sku === '') {
            return null;
        }

        $primary = $supplier !== null ? strtoupper($supplier) : null;
        $try = [];
        if ($primary) {
            $try[] = $primary;
        }
        foreach (['JG', 'GO'] as $sup) {
            if (!in_array($sup, $try, true)) {
                $try[] = $sup;
            }
        }

        foreach ($try as $sup) {
            foreach (self::csvRows($sup) as $row) {
                if (isset($row['sku']) && (string) $row['sku'] === $sku) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * First product image URL from a WooCommerce CSV row.
     *
     * @param array<string, mixed>|null $row
     */
    public static function firstImageUrl(?array $row): string
    {
        if (!is_array($row)) {
            return '';
        }

        $raw = isset($row['images']) ? (string) $row['images'] : '';
        if (trim($raw) === '') {
            return '';
        }

        $parts = preg_split('/\s*,\s*/', trim($raw), 2);

        return isset($parts[0]) ? trim($parts[0]) : '';
    }

    /**
     * Resolve cart line image URL from SKU via WooCommerce CSV.
     */
    public static function imageUrlForSku(string $sku, ?string $supplier = null): string
    {
        return self::firstImageUrl(self::rowBySku($sku, $supplier));
    }

    /**
     * URL for cart thumbnail display (full image — CSS scales; avoids missing WP -150x150 sizes).
     */
    public static function displayImageUrl(string $url): string
    {
        return trim($url);
    }

    /**
     * Fill missing `image` keys on cart rows from WooCommerce CSV.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public static function ensureItemsHaveImages(array &$items, ?string $supplier = null): void
    {
        foreach ($items as $idx => $row) {
            if (!is_array($row) || !empty($row['image'])) {
                continue;
            }
            $sku = isset($row['sku']) ? (string) $row['sku'] : '';
            if ($sku === '') {
                continue;
            }
            $img = self::imageUrlForSku($sku, $supplier);
            if ($img !== '') {
                $items[$idx]['image'] = $img;
            }
        }
    }
}
