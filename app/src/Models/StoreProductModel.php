<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Presenters\StoreProductPresenter;
use Fc\Admin\Services\CacheStorageService;

/**
 * Store products (writable/products.csv) data access.
 */
final class StoreProductModel
{
    /**
     * Columns the products.csv list view can be sorted by — 'SKUs' is a derived key
     * (first non-empty SKU across the row's style-appropriate color columns), not a
     * literal CSV column.
     */
    public const STORE_SORT_COLUMNS = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE', 'SKUs'];

    public static function csvPath(): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'products.csv';
    }

    private static function cacheDir(): string
    {
        return CacheStorageService::cacheDir('products');
    }

    /**
     * @return array{ok:bool,columns:list<string>,rows:list<array<string,string>>,total:int,file:string,styleColors?:array<string,list<string>>,error?:string}
     */
    public static function all(): array
    {
        $path = self::csvPath();

        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => 'products.csv not found or not readable.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'file' => 'products.csv',
            ];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to open products.csv.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'file' => 'products.csv',
            ];
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || $header === []) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => 'products.csv has no header row.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'file' => 'products.csv',
            ];
        }

        $columns = array_map('strval', $header);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data)) {
                continue;
            }

            $row = [];
            $hasValue = false;

            foreach ($columns as $index => $column) {
                $value = isset($data[$index]) ? (string) $data[$index] : '';
                $row[$column] = $value;
                if ($value !== '') {
                    $hasValue = true;
                }
            }

            if ($hasValue) {
                $row['_rowIndex'] = count($rows);
                $rows[] = $row;
            }
        }

        fclose($handle);

        return [
            'ok' => true,
            'columns' => $columns,
            'rows' => $rows,
            'total' => count($rows),
            'file' => 'products.csv',
            'styleColors' => StoreProductPresenter::styleColorsMap(),
        ];
    }

    /**
     * @return array{ok:bool,total:int,file:string,error?:string}
     */
    public static function count(): array
    {
        $path = self::csvPath();
        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => 'products.csv not found or not readable.',
                'total' => 0,
                'file' => 'products.csv',
            ];
        }

        $mtime = (int) filemtime($path);
        $cacheDir = self::cacheDir();
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'products-csv-count.json';

        if (is_readable($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && (int) ($cached['mtime'] ?? 0) === $mtime && isset($cached['total'])) {
                return [
                    'ok' => true,
                    'total' => (int) $cached['total'],
                    'file' => 'products.csv',
                ];
            }
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to open products.csv.',
                'total' => 0,
                'file' => 'products.csv',
            ];
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || $header === []) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => 'products.csv has no header row.',
                'total' => 0,
                'file' => 'products.csv',
            ];
        }

        $total = 0;
        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::rowHasValue($data)) {
                continue;
            }
            $total++;
        }
        fclose($handle);

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            @file_put_contents(
                $cacheFile,
                json_encode(['mtime' => $mtime, 'total' => $total], JSON_UNESCAPED_UNICODE)
            );
        }

        return [
            'ok' => true,
            'total' => $total,
            'file' => 'products.csv',
        ];
    }

    /**
     * Unique SUPPLIER / STYLE / color-column values for filter dropdowns (mtime-aware cache).
     *
     * @return array{ok:bool,suppliers:list<string>,styles:list<string>,colors:list<string>,file:string,error?:string}
     */
    public static function filterOptions(): array
    {
        $path = self::csvPath();
        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => 'products.csv not found or not readable.',
                'suppliers' => [],
                'styles' => [],
                'colors' => [],
                'file' => 'products.csv',
            ];
        }

        $mtime = (int) filemtime($path);
        $cacheDir = self::cacheDir();
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'products-csv-filters.json';

        if (is_readable($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (
                is_array($cached)
                && (int) ($cached['mtime'] ?? 0) === $mtime
                && isset($cached['suppliers'], $cached['styles'], $cached['colors'])
                && is_array($cached['suppliers'])
                && is_array($cached['styles'])
                && is_array($cached['colors'])
            ) {
                return [
                    'ok' => true,
                    'suppliers' => array_values(array_map('strval', $cached['suppliers'])),
                    'styles' => array_values(array_map('strval', $cached['styles'])),
                    'colors' => array_values(array_map('strval', $cached['colors'])),
                    'file' => 'products.csv',
                ];
            }
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to open products.csv.',
                'suppliers' => [],
                'styles' => [],
                'colors' => [],
                'file' => 'products.csv',
            ];
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || $header === []) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => 'products.csv has no header row.',
                'suppliers' => [],
                'styles' => [],
                'colors' => [],
                'file' => 'products.csv',
            ];
        }

        $columns = array_map('strval', $header);
        $colorColumns = StoreProductPresenter::colorColumnsFromList($columns);
        $supplierIdx = array_search('SUPPLIER', $columns, true);
        $styleIdx = array_search('STYLE', $columns, true);
        $suppliers = [];
        $styles = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::rowHasValue($data)) {
                continue;
            }
            if ($supplierIdx !== false) {
                $supplier = trim((string) ($data[$supplierIdx] ?? ''));
                if ($supplier !== '' && strtoupper($supplier) !== 'SUPPLIER') {
                    $suppliers[$supplier] = true;
                }
            }
            if ($styleIdx !== false) {
                $style = trim((string) ($data[$styleIdx] ?? ''));
                if ($style !== '' && strtoupper($style) !== 'STYLE') {
                    $styles[$style] = true;
                }
            }
        }
        fclose($handle);

        $supplierList = array_keys($suppliers);
        $styleList = array_keys($styles);
        natcasesort($supplierList);
        natcasesort($styleList);
        $supplierList = array_values($supplierList);
        $styleList = array_values($styleList);

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            @file_put_contents(
                $cacheFile,
                json_encode(
                    [
                        'mtime' => $mtime,
                        'suppliers' => $supplierList,
                        'styles' => $styleList,
                        'colors' => $colorColumns,
                    ],
                    JSON_UNESCAPED_UNICODE
                )
            );
        }

        return [
            'ok' => true,
            'suppliers' => $supplierList,
            'styles' => $styleList,
            'colors' => $colorColumns,
            'file' => 'products.csv',
        ];
    }

    /**
     * Stream products.csv with filters + pagination (avoids loading full catalogue into HTML).
     *
     * @param array{supplier?:string,style?:string,q?:string,colors?:list<string>,sort?:string,dir?:string} $filters
     * @return array{
     *   ok:bool,
     *   columns:list<string>,
     *   rows:list<array<string,string>>,
     *   total:int,
     *   total_unfiltered:int,
     *   page:int,
     *   per_page:int,
     *   total_pages:int,
     *   file:string,
     *   styleColors:array<string,list<string>>,
     *   error?:string
     * }
     */
    public static function query(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        bool $loadAll = false
    ): array {
        $path = self::csvPath();
        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => 'products.csv not found or not readable.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'total_unfiltered' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'file' => 'products.csv',
                'styleColors' => [],
            ];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to open products.csv.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'total_unfiltered' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'file' => 'products.csv',
                'styleColors' => [],
            ];
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || $header === []) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => 'products.csv has no header row.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'total_unfiltered' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'file' => 'products.csv',
                'styleColors' => [],
            ];
        }

        $columns = array_map('strval', $header);
        $supplierFilter = trim((string) ($filters['supplier'] ?? ''));
        $styleFilter = trim((string) ($filters['style'] ?? ''));
        $search = strtolower(trim((string) ($filters['q'] ?? '')));
        $colorFilters = [];
        if (isset($filters['colors']) && is_array($filters['colors'])) {
            foreach ($filters['colors'] as $colorColumn) {
                $colorColumn = trim((string) $colorColumn);
                if ($colorColumn !== '') {
                    $colorFilters[] = $colorColumn;
                }
            }
        }
        $colorFilters = array_values(array_unique($colorFilters));
        $incompleteOnly = !empty($filters['incomplete']);
        $hasFilters = $supplierFilter !== ''
            || $styleFilter !== ''
            || $search !== ''
            || $colorFilters !== []
            || $incompleteOnly;

        $sortColumn = trim((string) ($filters['sort'] ?? ''));
        if (!in_array($sortColumn, self::STORE_SORT_COLUMNS, true)) {
            $sortColumn = '';
        }
        $sortDir = strtolower(trim((string) ($filters['dir'] ?? 'asc'))) === 'desc' ? 'desc' : 'asc';
        $isSorted = $sortColumn !== '';

        $page = max(1, $page);
        if ($loadAll) {
            $perPage = PHP_INT_MAX;
            $offset = 0;
        } else {
            $perPage = max(1, min(5000, $perPage));
            $offset = ($page - 1) * $perPage;
        }

        // Sorting needs the whole filtered set before pagination can slice it, so the
        // cached-total/early-exit fast path only applies when there is no sort.
        $cachedTotal = null;
        if (!$hasFilters && !$loadAll && !$isSorted) {
            $countMeta = self::count();
            if (!empty($countMeta['ok'])) {
                $cachedTotal = (int) $countMeta['total'];
            }
        }

        $styleColors = StoreProductPresenter::styleColorsMap();
        // Only pay for the WC catalogue index when the incomplete-SKU filter is on.
        $skuSetLookup = $incompleteOnly ? \Fc\Admin\Services\WcProductSkuIndex::skuLookup() : [];

        $matched = 0;
        $unfiltered = 0;
        $rows = [];
        $rowIndex = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::rowHasValue($data)) {
                continue;
            }

            $unfiltered++;
            $row = [];
            foreach ($columns as $index => $column) {
                $row[$column] = isset($data[$index]) ? (string) $data[$index] : '';
            }

            if ($supplierFilter !== '' && trim((string) ($row['SUPPLIER'] ?? '')) !== $supplierFilter) {
                $rowIndex++;
                continue;
            }
            if ($styleFilter !== '' && trim((string) ($row['STYLE'] ?? '')) !== $styleFilter) {
                $rowIndex++;
                continue;
            }
            if ($colorFilters !== [] && !self::rowMatchesColorFilters($row, $colorFilters)) {
                $rowIndex++;
                continue;
            }
            if ($search !== '') {
                $haystack = strtolower(implode(' ', array_map(static fn($v): string => (string) $v, $row)));
                if (!str_contains($haystack, $search)) {
                    $rowIndex++;
                    continue;
                }
            }
            if ($incompleteOnly) {
                // Same summary the SKUs column shows. Keep anything worth reviewing: a real gap, or
                // a colour set to OFF (complete, but flagged red and still worth eyeballing). Rows
                // with no colour columns for their style render "—" and have nothing to review.
                $summary = StoreProductPresenter::skusSummary($row, $columns, $styleColors, $skuSetLookup);
                $needsReview = $summary['total'] > 0
                    && (!$summary['complete'] || ($summary['off'] ?? 0) > 0);
                if (!$needsReview) {
                    $rowIndex++;
                    continue;
                }
            }

            if ($isSorted || $loadAll || ($matched >= $offset && count($rows) < $perPage)) {
                $row['_rowIndex'] = $rowIndex;
                $rows[] = $row;
            }

            $matched++;
            $rowIndex++;

            if (
                !$isSorted
                && !$loadAll
                && $cachedTotal !== null
                && count($rows) >= $perPage
                && $matched >= ($offset + $perPage)
            ) {
                $matched = $cachedTotal;
                break;
            }
        }

        fclose($handle);

        if ($cachedTotal !== null) {
            $matched = $cachedTotal;
            $unfiltered = $cachedTotal;
        }

        if ($isSorted) {
            usort($rows, static function (array $a, array $b) use ($sortColumn, $columns, $styleColors, $sortDir): int {
                $av = self::sortKey($a, $sortColumn, $columns, $styleColors);
                $bv = self::sortKey($b, $sortColumn, $columns, $styleColors);
                $cmp = $av <=> $bv;

                return $sortDir === 'desc' ? -$cmp : $cmp;
            });
            $matched = count($rows);
            if (!$loadAll) {
                $rows = array_slice($rows, $offset, $perPage);
            }
        }

        if ($loadAll) {
            $totalPages = 1;
            $page = 1;
        } else {
            $totalPages = max(1, (int) ceil(max(0, $matched) / max(1, $perPage)));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
        }

        return [
            'ok' => true,
            'columns' => $columns,
            'rows' => $rows,
            'total' => $matched,
            'total_unfiltered' => $unfiltered,
            'page' => $page,
            'per_page' => $loadAll ? $matched : $perPage,
            'total_pages' => $totalPages,
            'file' => 'products.csv',
            'styleColors' => $styleColors,
        ];
    }

    /**
     * @param list<string> $data
     */
    private static function rowHasValue(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function colorColumnHasSku(array $row, string $column): bool
    {
        $sku = trim((string) ($row[$column] ?? ''));

        return $sku !== '' && strtoupper($sku) !== 'OFF';
    }

    /**
     * First non-empty, non-"OFF" SKU value across a row's style-appropriate color columns.
     *
     * @param array<string, string> $row
     * @param list<string> $columns
     * @param array<string, list<string>> $styleColors
     */
    private static function rowFirstSku(array $row, array $columns, array $styleColors): string
    {
        foreach (StoreProductPresenter::allowedColorColumns($row, $columns, $styleColors) as $column) {
            if (self::colorColumnHasSku($row, $column)) {
                return trim((string) ($row[$column] ?? ''));
            }
        }

        return '';
    }

    /**
     * @param array<string, string> $row
     * @param list<string> $columns
     * @param array<string, list<string>> $styleColors
     */
    private static function sortKey(array $row, string $sortColumn, array $columns, array $styleColors): string
    {
        if ($sortColumn === 'SKUs') {
            return strtolower(self::rowFirstSku($row, $columns, $styleColors));
        }

        return strtolower(trim((string) ($row[$sortColumn] ?? '')));
    }

    /**
     * @param array<string, string> $row
     * @param list<string> $colorFilters
     */
    private static function rowMatchesColorFilters(array $row, array $colorFilters): bool
    {
        foreach ($colorFilters as $column) {
            if (self::colorColumnHasSku($row, $column)) {
                return true;
            }
        }

        return false;
    }
}
