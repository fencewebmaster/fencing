<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Cached SKU / product indexes for wc-products-{GO|JG}.csv (store catalogue).
 */
final class WcProductSkuIndex
{
    /**
     * @return list<string>
     */
    public static function skuSources(): array
    {
        return ['GO', 'JG'];
    }

    public static function csvPath(string $source): string
    {
        $source = strtoupper(trim($source));

        return dirname(__DIR__, 3) . '/writable/wc-products-' . $source . '.csv';
    }

    public static function cachePath(string $source): string
    {
        return CacheStorageService::cacheDir('products')
            . DIRECTORY_SEPARATOR . 'wc-products-' . strtoupper(trim($source)) . '-sku-index.json';
    }

    /**
     * Drop SKU index cache for one source (or all known sources when $source is empty).
     */
    public static function invalidate(string $source = ''): void
    {
        $sources = $source !== ''
            ? [strtoupper(trim($source))]
            : self::skuSources();

        foreach ($sources as $key) {
            if ($key === '') {
                continue;
            }
            @unlink(self::cachePath($key));
        }
    }

    /**
     * First image URL from a WC Images CSV cell (comma-separated URLs).
     */
    public static function firstImageUrl(string $images): string
    {
        $parts = preg_split('/\s*,\s*/', trim($images));
        if (!is_array($parts)) {
            return '';
        }
        foreach ($parts as $part) {
            $url = trim((string) $part);
            if ($url !== '' && preg_match('#^https?://#i', $url) === 1) {
                return $url;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $cached
     */
    public static function cacheIsValid(array $cached, int $mtime): bool
    {
        if ((int) ($cached['mtime'] ?? 0) !== $mtime) {
            return false;
        }
        if ((int) ($cached['version'] ?? 0) < 3) {
            return false;
        }
        if (!is_array($cached['products'] ?? null)) {
            return false;
        }

        return true;
    }

    /**
     * @param list<array{sku:string,name:string,image:string}> $products
     * @return list<array{sku:string,name:string,image:string}>
     */
    public static function normalizeProductEntries(array $products): array
    {
        $out = [];
        $seen = [];
        foreach ($products as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '' || isset($seen[$sku])) {
                continue;
            }
            $seen[$sku] = true;
            $out[] = [
                'sku' => $sku,
                'name' => html_entity_decode(
                    trim((string) ($row['name'] ?? '')),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ),
                'image' => trim((string) ($row['image'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * Build / load mtime-cached catalogue entries for one WC source.
     *
     * @return list<array{sku:string,name:string,image:string}>
     */
    public static function entriesForSource(string $source): array
    {
        $source = strtoupper(trim($source));
        if (!in_array($source, self::skuSources(), true)) {
            return [];
        }

        $path = self::csvPath($source);
        if (!is_readable($path) || !is_file($path)) {
            return [];
        }

        $mtime = (int) @filemtime($path);
        $cachePath = self::cachePath($source);
        if (is_readable($cachePath)) {
            $cached = json_decode((string) file_get_contents($cachePath), true);
            if (is_array($cached) && self::cacheIsValid($cached, $mtime)) {
                return self::normalizeProductEntries($cached['products']);
            }
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        $skuIndex = 1;
        $nameIndex = 2;
        $imagesIndex = 3;
        if (is_array($header)) {
            foreach ($header as $i => $col) {
                $key = strtoupper(trim((string) $col));
                if ($key === 'SKU') {
                    $skuIndex = (int) $i;
                } elseif ($key === 'NAME') {
                    $nameIndex = (int) $i;
                } elseif ($key === 'IMAGES') {
                    $imagesIndex = (int) $i;
                }
            }
        }

        $seen = [];
        $products = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row)) {
                continue;
            }
            $sku = trim((string) ($row[$skuIndex] ?? ''));
            if ($sku === '' || isset($seen[$sku])) {
                continue;
            }
            $seen[$sku] = true;
            $products[] = [
                'sku' => $sku,
                'name' => html_entity_decode(
                    trim((string) ($row[$nameIndex] ?? '')),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ),
                'image' => self::firstImageUrl((string) ($row[$imagesIndex] ?? '')),
            ];
        }
        fclose($handle);

        $payload = json_encode(
            [
                'version' => 3,
                'mtime' => $mtime,
                'products' => $products,
                'skus' => array_column($products, 'sku'),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if ($payload !== false) {
            $dir = dirname($cachePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($cachePath, $payload, LOCK_EX);
        }

        return $products;
    }

    /**
     * Union of catalogue entries across GO + JG (first SKU wins).
     *
     * @return list<array{sku:string,name:string,image:string}>
     */
    public static function catalogueUnion(): array
    {
        $seen = [];
        $products = [];
        foreach (self::skuSources() as $source) {
            foreach (self::entriesForSource($source) as $row) {
                $sku = $row['sku'];
                if ($sku === '' || isset($seen[$sku])) {
                    continue;
                }
                $seen[$sku] = true;
                $products[] = $row;
            }
        }

        return $products;
    }

    /**
     * Union of SKUs across GO + JG catalogues.
     *
     * @return list<string>
     */
    public static function skuUnion(): array
    {
        return array_column(self::catalogueUnion(), 'sku');
    }

    /**
     * @return array{ok:bool,skus:list<string>,products:list<array{sku:string,name:string,image:string}>}
     */
    public static function indexPayload(): array
    {
        $products = self::catalogueUnion();

        return [
            'ok' => true,
            'skus' => array_column($products, 'sku'),
            'products' => $products,
        ];
    }
}
