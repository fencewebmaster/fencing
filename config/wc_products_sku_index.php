<?php
/**
 * Cached SKU indexes for wc-products-{GO|JG}.csv (store catalogue).
 */

declare(strict_types=1);

/**
 * @return list<string>
 */
function fc_wc_products_sku_sources(): array
{
    return ['GO', 'JG'];
}

function fc_wc_products_csv_path(string $source): string
{
    $source = strtoupper(trim($source));

    return dirname(__DIR__) . '/data/wc-products-' . $source . '.csv';
}

function fc_wc_products_sku_index_cache_path(string $source): string
{
    if (!function_exists('fc_storage_cache_dir')) {
        require_once __DIR__ . '/storage.php';
    }

    return fc_storage_cache_dir('products')
        . DIRECTORY_SEPARATOR . 'wc-products-' . strtoupper(trim($source)) . '-sku-index.json';
}

/**
 * Drop SKU index cache for one source (or all known sources when $source is empty).
 */
function fc_wc_products_sku_index_invalidate(string $source = ''): void
{
    $sources = $source !== ''
        ? [strtoupper(trim($source))]
        : fc_wc_products_sku_sources();

    foreach ($sources as $key) {
        if ($key === '') {
            continue;
        }
        @unlink(fc_wc_products_sku_index_cache_path($key));
    }
}

/**
 * Build / load mtime-cached SKU set for one WC catalogue source.
 *
 * @return list<string>
 */
function fc_wc_products_sku_list_for_source(string $source): array
{
    $source = strtoupper(trim($source));
    if (!in_array($source, fc_wc_products_sku_sources(), true)) {
        return [];
    }

    $path = fc_wc_products_csv_path($source);
    if (!is_readable($path) || !is_file($path)) {
        return [];
    }

    $mtime = (int) @filemtime($path);
    $cachePath = fc_wc_products_sku_index_cache_path($source);
    if (is_readable($cachePath)) {
        $cached = json_decode((string) file_get_contents($cachePath), true);
        if (
            is_array($cached)
            && (int) ($cached['mtime'] ?? 0) === $mtime
            && is_array($cached['skus'] ?? null)
        ) {
            $skus = [];
            foreach ($cached['skus'] as $sku) {
                if (!is_scalar($sku)) {
                    continue;
                }
                $sku = trim((string) $sku);
                if ($sku !== '') {
                    $skus[] = $sku;
                }
            }

            return array_values(array_unique($skus));
        }
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    $header = fgetcsv($handle);
    $skuIndex = 1;
    if (is_array($header)) {
        foreach ($header as $i => $col) {
            if (strtoupper(trim((string) $col)) === 'SKU') {
                $skuIndex = (int) $i;
                break;
            }
        }
    }

    $seen = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (!is_array($row)) {
            continue;
        }
        $sku = trim((string) ($row[$skuIndex] ?? ''));
        if ($sku === '') {
            continue;
        }
        $seen[$sku] = true;
    }
    fclose($handle);

    $skus = array_keys($seen);
    $payload = json_encode(
        [
            'mtime' => $mtime,
            'skus' => $skus,
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

    return $skus;
}

/**
 * Union of SKUs across GO + JG catalogues.
 *
 * @return list<string>
 */
function fc_wc_products_sku_union(): array
{
    $seen = [];
    foreach (fc_wc_products_sku_sources() as $source) {
        foreach (fc_wc_products_sku_list_for_source($source) as $sku) {
            $seen[$sku] = true;
        }
    }

    return array_keys($seen);
}

/**
 * @return array{ok:bool,skus:list<string>}
 */
function fc_wc_products_sku_index_payload(): array
{
    return [
        'ok' => true,
        'skus' => fc_wc_products_sku_union(),
    ];
}
