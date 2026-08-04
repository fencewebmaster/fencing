<?php
/**
 * FC Admin — products API (reads/writes fc/data/products.csv).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Services\WooCommerceProductExportService;

final class ProductsController
{
    /**
     * Columns the products.csv list view can be sorted by — 'SKUs' is a derived key
     * (first non-empty SKU across the row's style-appropriate color columns), not a
     * literal CSV column.
     */
    public const STORE_SORT_COLUMNS = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE', 'SKUs'];

    private static function productsCsvPath(): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'products.csv';
    }

    private static function productsCacheDir(): string
    {
        if (!function_exists('fc_storage_cache_dir')) {
            require_once FC_ROOT . '/config/storage.php';
        }

        return fc_storage_cache_dir('products');
    }

    /**
     * @return array{ok:bool,columns:list<string>,rows:list<array<string,string>>,total:int,file:string,error?:string}
     */
    public static function getStoreProducts(): array
    {
        $path = self::productsCsvPath();

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
            'styleColors' => self::getFenceStyleColors(),
        ];
    }

    /**
     * @return array{ok:bool,total:int,file:string,error?:string}
     */
    public static function countStoreProducts(): array
    {
        $path = self::productsCsvPath();
        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => 'products.csv not found or not readable.',
                'total' => 0,
                'file' => 'products.csv',
            ];
        }

        $mtime = (int) filemtime($path);
        $cacheDir = self::productsCacheDir();
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
            if (!is_array($data) || !self::storeCsvRowHasValue($data)) {
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
     * Unique SUPPLIER / STYLE values for filter dropdowns (mtime-aware cache).
     *
     * @return array{ok:bool,suppliers:list<string>,styles:list<string>,colors:list<string>,file:string,error?:string}
     */
    public static function getStoreProductFilterOptions(): array
    {
        $path = self::productsCsvPath();
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
        $cacheDir = self::productsCacheDir();
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
        $colorColumns = self::storeCsvColorColumns($columns);
        $supplierIdx = array_search('SUPPLIER', $columns, true);
        $styleIdx = array_search('STYLE', $columns, true);
        $suppliers = [];
        $styles = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::storeCsvRowHasValue($data)) {
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
     * @param array{supplier?:string,style?:string,q?:string,colors?:list<string>} $filters
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
    public static function queryStoreProducts(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        bool $loadAll = false
    ): array {
        $path = self::productsCsvPath();
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
        $hasFilters = $supplierFilter !== ''
            || $styleFilter !== ''
            || $search !== ''
            || $colorFilters !== [];

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
            $countMeta = self::countStoreProducts();
            if (!empty($countMeta['ok'])) {
                $cachedTotal = (int) $countMeta['total'];
            }
        }

        $styleColors = self::getFenceStyleColors();

        $matched = 0;
        $unfiltered = 0;
        $rows = [];
        $rowIndex = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::storeCsvRowHasValue($data)) {
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
            if ($colorFilters !== [] && !self::storeCsvRowMatchesColorFilters($row, $colorFilters)) {
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
                $av = self::storeCsvSortKey($a, $sortColumn, $columns, $styleColors);
                $bv = self::storeCsvSortKey($b, $sortColumn, $columns, $styleColors);
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
    private static function storeCsvRowHasValue(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $columns
     * @return list<string>
     */
    private static function storeCsvColorColumns(array $columns): array
    {
        $detailColumns = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE'];

        return array_values(array_filter(
            $columns,
            static fn(string $col): bool => !in_array($col, $detailColumns, true)
        ));
    }

    private static function storeCsvColorColumnHasSku(array $row, string $column): bool
    {
        $sku = trim((string) ($row[$column] ?? ''));

        return $sku !== '' && strtoupper($sku) !== 'OFF';
    }

    /**
     * @param array<string, string> $row
     * @param list<string> $columns
     * @param array<string, list<string>> $styleColors
     * @return list<string>
     */
    private static function storeCsvAllowedColorColumns(array $row, array $columns, array $styleColors): array
    {
        $style = trim((string) ($row['STYLE'] ?? ''));
        if (isset($styleColors[$style]) && is_array($styleColors[$style])) {
            return array_values($styleColors[$style]);
        }

        return self::storeCsvColorColumns($columns);
    }

    /**
     * First non-empty, non-"OFF" SKU value across a row's style-appropriate color columns.
     *
     * @param array<string, string> $row
     * @param list<string> $columns
     * @param array<string, list<string>> $styleColors
     */
    private static function storeCsvRowFirstSku(array $row, array $columns, array $styleColors): string
    {
        foreach (self::storeCsvAllowedColorColumns($row, $columns, $styleColors) as $column) {
            if (self::storeCsvColorColumnHasSku($row, $column)) {
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
    private static function storeCsvSortKey(array $row, string $sortColumn, array $columns, array $styleColors): string
    {
        if ($sortColumn === 'SKUs') {
            return strtolower(self::storeCsvRowFirstSku($row, $columns, $styleColors));
        }

        return strtolower(trim((string) ($row[$sortColumn] ?? '')));
    }

    /**
     * @param array<string, string> $row
     * @param list<string> $colorFilters
     */
    private static function storeCsvRowMatchesColorFilters(array $row, array $colorFilters): bool
    {
        foreach ($colorFilters as $column) {
            if (self::storeCsvColorColumnHasSku($row, $column)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Maps products.csv STYLE values to allowed color column names (uppercase CSV headers).
     *
     * @return array<string, list<string>>
     */
    private static function getFenceStyleColors(): array
    {
        $fencesDir = FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'fences';
        $files = glob($fencesDir . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_NATURAL);

        $fences = [];
        foreach ($files as $fenceFile) {
            if (!is_readable($fenceFile)) {
                continue;
            }
            include $fenceFile;
        }

        $map = [];
        foreach ($fences as $fenceKey => $info) {
            if (!is_array($info) || !isset($info['color']) || !is_array($info['color'])) {
                continue;
            }

            $plannerSlug = (string) ($info['slug'] ?? $fenceKey);
            $styleKey = self::productsCsvStyleForFence($plannerSlug);
            $columns = [];
            foreach ($info['color'] as $colorSlug) {
                $columns[] = self::colorSlugToCsvColumn((string) $colorSlug);
            }
            $map[$styleKey] = array_values(array_unique($columns));
        }

        return $map;
    }

    private static function productsCsvStyleForFence(string $fenceSlug): string
    {
        if ($fenceSlug === 'slat_fence_infill') {
            return 'slat_infill';
        }
        if ($fenceSlug === 'slat_fence') {
            return 'slat';
        }

        return $fenceSlug;
    }

    private static function colorSlugToCsvColumn(string $slug): string
    {
        return strtoupper(str_replace('-', '_', $slug));
    }

    /**
     * @return array{ok:bool,columns:list<string>,rows:list<array<string,string>>,total:int,file:string,source?:string,error?:string}
     */
    public static function getSystemProducts(string $source): array
    {
        $source = strtoupper(trim($source));
        if (!in_array($source, ['GO', 'JG'], true)) {
            return [
                'ok' => false,
                'error' => 'Invalid source. Use GO or JG.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'file' => '',
                'source' => $source,
            ];
        }

        $filename = 'wc-products-' . $source . '.csv';
        $path = FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $filename;

        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => $filename . ' not found or not readable.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to open ' . $filename . '.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || $header === []) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => $filename . ' has no header row.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'file' => $filename,
                'source' => $source,
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

            if (!$hasValue) {
                continue;
            }

            $firstCol = $columns[0] ?? '';
            $firstVal = $firstCol !== '' ? trim((string) ($row[$firstCol] ?? '')) : '';
            if ($firstVal !== '' && strtoupper($firstVal) === strtoupper($firstCol)) {
                continue;
            }

            $row['_rowIndex'] = count($rows);
            $rows[] = $row;
        }

        fclose($handle);

        return [
            'ok' => true,
            'columns' => $columns,
            'rows' => $rows,
            'total' => count($rows),
            'file' => $filename,
            'source' => $source,
        ];
    }

    /**
     * Lightweight total for toolbar badges (filemtime-aware cache).
     *
     * @return array{ok:bool,total:int,file:string,source:string,error?:string}
     */
    public static function countSystemProducts(string $source): array
    {
        $source = strtoupper(trim($source));
        if (!in_array($source, ['GO', 'JG'], true)) {
            return [
                'ok' => false,
                'error' => 'Invalid source. Use GO or JG.',
                'total' => 0,
                'file' => '',
                'source' => $source,
            ];
        }

        $filename = 'wc-products-' . $source . '.csv';
        $path = FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $filename;

        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => $filename . ' not found or not readable.',
                'total' => 0,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $mtime = (int) filemtime($path);
        $cacheDir = self::productsCacheDir();
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'wc-products-' . $source . '-count.json';

        if (is_readable($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (
                is_array($cached)
                && (int) ($cached['mtime'] ?? 0) === $mtime
                && isset($cached['total'])
            ) {
                return [
                    'ok' => true,
                    'total' => (int) $cached['total'],
                    'file' => $filename,
                    'source' => $source,
                ];
            }
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to open ' . $filename . '.',
                'total' => 0,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || $header === []) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => $filename . ' has no header row.',
                'total' => 0,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $columns = array_map('strval', $header);
        $firstCol = $columns[0] ?? '';
        $total = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::systemCsvRowIsValid($data, $firstCol)) {
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
            'file' => $filename,
            'source' => $source,
        ];
    }

    /**
     * Stream CSV and return one page of filtered rows (no full in-memory catalogue).
     *
     * @param list<string>|null $keepColumns When set, only these columns are kept on each row.
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
     *   source:string,
     *   error?:string
     * }
     */
    public static function querySystemProducts(
        string $source,
        string $search = '',
        int $page = 1,
        int $perPage = 50,
        ?array $keepColumns = null
    ): array {
        $source = strtoupper(trim($source));
        if (!in_array($source, ['GO', 'JG'], true)) {
            return [
                'ok' => false,
                'error' => 'Invalid source. Use GO or JG.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'total_unfiltered' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'file' => '',
                'source' => $source,
            ];
        }

        $filename = 'wc-products-' . $source . '.csv';
        $path = FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $filename;

        if (!is_readable($path)) {
            return [
                'ok' => false,
                'error' => $filename . ' not found or not readable.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'total_unfiltered' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to open ' . $filename . '.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'total_unfiltered' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || $header === []) {
            fclose($handle);
            return [
                'ok' => false,
                'error' => $filename . ' has no header row.',
                'columns' => [],
                'rows' => [],
                'total' => 0,
                'total_unfiltered' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'file' => $filename,
                'source' => $source,
            ];
        }

        $allColumns = array_map('strval', $header);
        $firstCol = $allColumns[0] ?? '';
        $columns = $keepColumns !== null
            ? array_values(array_filter($keepColumns, static fn(string $col): bool => in_array($col, $allColumns, true)))
            : $allColumns;
        if ($columns === []) {
            $columns = $allColumns;
        }

        $search = strtolower(trim($search));
        $page = max(1, $page);
        $loadAll = $perPage >= 100000 || $perPage === PHP_INT_MAX;
        $perPage = $loadAll ? PHP_INT_MAX : max(1, min(5000, $perPage));
        $offset = ($page - 1) * ($loadAll ? 0 : $perPage);

        // No search: use cached floor count and stop streaming after the requested page is filled.
        $cachedTotal = null;
        if ($search === '' && !$loadAll) {
            $countMeta = self::countSystemProducts($source);
            if (!empty($countMeta['ok'])) {
                $cachedTotal = (int) $countMeta['total'];
            }
        }

        $matched = 0;
        $unfiltered = 0;
        $rows = [];
        $rowIndex = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::systemCsvRowIsValid($data, $firstCol)) {
                continue;
            }

            $unfiltered++;

            if ($search !== '') {
                $haystack = strtolower(implode(' ', array_map(static fn($v): string => (string) $v, $data)));
                if (!str_contains($haystack, $search)) {
                    $rowIndex++;
                    continue;
                }
            }

            if ($matched >= $offset && ($loadAll || count($rows) < $perPage)) {
                $row = ['_rowIndex' => $rowIndex];
                foreach ($columns as $column) {
                    $colPos = array_search($column, $allColumns, true);
                    $row[$column] = $colPos === false
                        ? ''
                        : (string) ($data[$colPos] ?? '');
                }
                $rows[] = $row;
            }

            $matched++;
            $rowIndex++;

            if (
                !$loadAll
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

        $totalPages = $loadAll ? 1 : max(1, (int) ceil(max(0, $matched) / max(1, $perPage === PHP_INT_MAX ? 1 : $perPage)));
        if ($loadAll) {
            $page = 1;
        } elseif ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'ok' => true,
            'columns' => $columns,
            'rows' => $rows,
            'total' => $matched,
            'total_unfiltered' => $unfiltered,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'file' => $filename,
            'source' => $source,
        ];
    }

    /**
     * @param list<string> $data
     */
    private static function systemCsvRowIsValid(array $data, string $firstCol): bool
    {
        $hasValue = false;
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                $hasValue = true;
                break;
            }
        }
        if (!$hasValue) {
            return false;
        }

        $firstVal = $firstCol !== '' ? trim((string) ($data[0] ?? '')) : '';
        if ($firstVal !== '' && strtoupper($firstVal) === strtoupper($firstCol)) {
            return false;
        }

        return true;
    }

    /**
     * @param list<int> $order Permutation of row indices (each 0..n-1 exactly once).
     * @return array{ok:bool,total?:int,error?:string}
     */
    public static function reorderStoreProducts(array $order): array
    {
        $load = self::getStoreProducts();
        if (!$load['ok']) {
            return [
                'ok' => false,
                'error' => $load['error'] ?? 'Could not read products.csv.',
            ];
        }

        $columns = $load['columns'];
        $rowCount = count($load['rows']);

        if ($rowCount === 0) {
            return [
                'ok' => false,
                'error' => 'No product rows to reorder.',
            ];
        }

        if (count($order) !== $rowCount) {
            return [
                'ok' => false,
                'error' => 'Order length does not match row count.',
            ];
        }

        $normalized = [];
        foreach ($order as $index) {
            if (!is_int($index) && !is_float($index) && !is_string($index)) {
                return [
                    'ok' => false,
                    'error' => 'Invalid order entry.',
                ];
            }
            $index = (int) $index;
            if ($index < 0 || $index >= $rowCount) {
                return [
                    'ok' => false,
                    'error' => 'Order contains out-of-range row index.',
                ];
            }
            $normalized[] = $index;
        }

        $sorted = $normalized;
        sort($sorted, SORT_NUMERIC);
        $expected = range(0, $rowCount - 1);

        if ($sorted !== $expected) {
            return [
                'ok' => false,
                'error' => 'Order must list each row index exactly once.',
            ];
        }

        $ordered = [];
        foreach ($normalized as $index) {
            $row = $load['rows'][$index];
            unset($row['_rowIndex']);
            $ordered[] = $row;
        }

        return self::writeProductsCsv($columns, $ordered);
    }

    /**
     * @param array<string, string> $fields Column => value (CSV columns only).
     * @return array{ok:bool,total?:int,rowIndex?:int,error?:string}
     */
    public static function updateStoreProduct(int $rowIndex, array $fields): array
    {
        $load = self::getStoreProducts();
        if (!$load['ok']) {
            return [
                'ok' => false,
                'error' => $load['error'] ?? 'Could not read products.csv.',
            ];
        }

        $columns = $load['columns'];
        $rowCount = count($load['rows']);

        if ($rowIndex < 0 || $rowIndex >= $rowCount) {
            return [
                'ok' => false,
                'error' => 'Row index out of range.',
            ];
        }

        $rows = [];
        foreach ($load['rows'] as $i => $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[$column] = (string) ($row[$column] ?? '');
            }
            if ($i === $rowIndex) {
                foreach ($columns as $column) {
                    if ($column === 'SLUG') {
                        continue;
                    }
                    if (array_key_exists($column, $fields)) {
                        $line[$column] = (string) $fields[$column];
                    }
                }
            }
            $rows[] = $line;
        }

        $written = self::writeProductsCsv($columns, $rows);
        if (!$written['ok']) {
            return $written;
        }

        return [
            'ok' => true,
            'total' => $rowCount,
            'rowIndex' => $rowIndex,
        ];
    }

    /**
     * @param list<string> $columns
     * @param list<array<string,string>> $rows
     * @return array{ok:bool,total?:int,error?:string}
     */
    private static function writeProductsCsv(array $columns, array $rows): array
    {
        $path = self::productsCsvPath();
        $dir = dirname($path);

        if (!is_writable($dir)) {
            return [
                'ok' => false,
                'error' => 'data/ directory is not writable.',
            ];
        }

        if (file_exists($path) && !is_writable($path)) {
            return [
                'ok' => false,
                'error' => 'products.csv is not writable.',
            ];
        }

        $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $handle = fopen($tmp, 'wb');

        if ($handle === false) {
            return [
                'ok' => false,
                'error' => 'Unable to create temporary file.',
            ];
        }

        fputcsv($handle, $columns);

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line);
        }

        fclose($handle);

        if (!rename($tmp, $path)) {
            @unlink($tmp);
            return [
                'ok' => false,
                'error' => 'Unable to save products.csv.',
            ];
        }

        self::invalidateStoreProductsCache();

        return [
            'ok' => true,
            'total' => count($rows),
        ];
    }

    private static function invalidateStoreProductsCache(): void
    {
        $cacheDir = self::productsCacheDir();
        @unlink($cacheDir . DIRECTORY_SEPARATOR . 'products-csv-count.json');
        @unlink($cacheDir . DIRECTORY_SEPARATOR . 'products-csv-filters.json');
    }

    public static function handleApiRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

        if ($method === 'POST') {
            if ($action === 'import-products-csv') {
                self::importProductsCsv();
                return;
            }
            if ($action === 'import-store-products-csv') {
                self::importStoreProductsCsv();
                return;
            }

            $raw = file_get_contents('php://input');
            $payload = is_string($raw) ? json_decode($raw, true) : null;

            if (!is_array($payload)) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON body.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (in_array($action, [
                'download-products-start',
                'download-products-step',
                'download-products-cancel',
            ], true)) {
                if (
                    !function_exists('fc_auth_verify_csrf')
                    || !fc_auth_verify_csrf(isset($payload['csrf']) ? (string) $payload['csrf'] : null)
                ) {
                    http_response_code(403);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Invalid security token. Refresh and try again.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $source = isset($payload['source']) ? (string) $payload['source'] : '';
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                $jobId = isset($payload['jobId']) ? (string) $payload['jobId'] : '';
                $result = match ($action) {
                    'download-products-start' => WooCommerceProductExportService::start($source),
                    'download-products-cancel' => WooCommerceProductExportService::cancel($source, $jobId),
                    default => WooCommerceProductExportService::step($source, $jobId),
                };
                if (empty($result['ok'])) {
                    http_response_code(500);
                } elseif (($result['job']['status'] ?? '') === 'running') {
                    http_response_code(202);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return;
            }

            if ($action === 'reorder-store-products') {
                if (!isset($payload['order']) || !is_array($payload['order'])) {
                    http_response_code(400);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Invalid JSON. Expected { "order": [0, 2, 1, ...] }.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $result = self::reorderStoreProducts($payload['order']);
                if (!$result['ok']) {
                    http_response_code(500);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($action === 'update-store-product') {
                if (!isset($payload['rowIndex']) || !isset($payload['fields']) || !is_array($payload['fields'])) {
                    http_response_code(400);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Invalid JSON. Expected { "rowIndex": 0, "fields": { ... } }.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $result = self::updateStoreProduct((int) $payload['rowIndex'], $payload['fields']);
                if (!$result['ok']) {
                    http_response_code(500);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Unknown POST action.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'ok' => false,
                'error' => 'Method not allowed.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        switch ($action) {
            case 'store-products':
                echo json_encode(self::getStoreProducts(), JSON_UNESCAPED_UNICODE);
                break;
            case 'system-products':
                $source = isset($_GET['source']) ? (string) $_GET['source'] : '';
                $result = self::getSystemProducts($source);
                if (!$result['ok']) {
                    http_response_code($source === '' ? 400 : 404);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
            case 'download-products-status':
                $source = isset($_GET['source']) ? (string) $_GET['source'] : '';
                $result = WooCommerceProductExportService::status($source);
                if (empty($result['ok'])) {
                    http_response_code(400);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case 'download-products-csv':
                self::downloadProductsCsv(
                    isset($_GET['source']) ? (string) $_GET['source'] : ''
                );
                break;
            case 'download-store-products-csv':
                self::downloadStoreProductsCsv();
                break;
            case 'wc-sku-index':
                if (!function_exists('fc_wc_products_sku_index_payload')) {
                    require_once FC_ROOT . '/config/wc_products_sku_index.php';
                }
                echo json_encode(
                    fc_wc_products_sku_index_payload(),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                break;
            default:
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Unknown action.',
                ], JSON_UNESCAPED_UNICODE);
        }
    }

    private static function downloadStoreProductsCsv(): void
    {
        $filename = 'products.csv';
        $path = self::productsCsvPath();
        if (!is_readable($path) || !is_file($path)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => $filename . ' not found or not readable.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $size = @filesize($path);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        if (is_int($size) && $size >= 0) {
            header('Content-Length: ' . $size);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to open ' . $filename . '.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        fpassthru($handle);
        fclose($handle);
        exit;
    }

    private static function importStoreProductsCsv(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
        if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf($csrf)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid security token. Refresh and try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Choose a CSV file to import.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $file = $_FILES['file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Upload failed. Please try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'upload.csv');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Uploaded file is not readable.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($size <= 0 || $size > 50 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must be between 1 byte and 50MB.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Only .csv files can be imported.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $handle = fopen($tmp, 'rb');
        if ($handle === false) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to read the uploaded CSV.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $header = fgetcsv($handle);
        $rowCount = 0;
        $hasSlug = false;
        if (is_array($header)) {
            while (($row = fgetcsv($handle)) !== false) {
                if (!is_array($row) || $row === [null]) {
                    continue;
                }
                $slug = trim((string) ($row[0] ?? ''));
                if ($slug === '') {
                    continue;
                }
                $rowCount++;
                $hasSlug = true;
            }
        }
        fclose($handle);

        $normalizedHeader = is_array($header)
            ? array_map(static fn($col): string => trim((string) $col), $header)
            : [];
        if ($normalizedHeader !== [] && isset($normalizedHeader[0])) {
            $normalizedHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', $normalizedHeader[0]) ?? $normalizedHeader[0];
        }

        $required = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE'];
        $missing = [];
        foreach ($required as $column) {
            if (!in_array($column, $normalizedHeader, true)) {
                $missing[] = $column;
            }
        }
        if ($missing !== []) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV header must include: ' . implode(', ', $required) . '.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($rowCount < 1 || !$hasSlug) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must contain at least one product row with a SLUG.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filename = 'products.csv';
        $dataDir = FC_ROOT . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dataDir) && !@mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to access the data directory.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $final = self::productsCsvPath();
        $tmpDest = $dataDir . DIRECTORY_SEPARATOR . '.products-import-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.csv';
        if (!@move_uploaded_file($tmp, $tmpDest)) {
            if (!@copy($tmp, $tmpDest)) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Unable to store the uploaded CSV.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            @unlink($tmp);
        }

        $backup = $final . '.backup';
        @unlink($backup);
        $hadFinal = is_file($final);
        if ($hadFinal && !@rename($final, $backup)) {
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to prepare the existing products.csv for replacement.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!@rename($tmpDest, $final)) {
            if ($hadFinal && is_file($backup)) {
                @rename($backup, $final);
            }
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to replace products.csv.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        @unlink($backup);

        self::invalidateStoreProductsCache();

        echo json_encode([
            'ok' => true,
            'message' => 'Imported ' . number_format($rowCount) . ' products into ' . $filename . '.',
            'file' => $filename,
            'total' => $rowCount,
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function downloadProductsCsv(string $source): void
    {
        $source = strtoupper(trim($source));
        if (!in_array($source, ['GO', 'JG'], true)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid source. Use GO or JG.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filename = 'wc-products-' . $source . '.csv';
        $path = FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $filename;
        if (!is_readable($path) || !is_file($path)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => $filename . ' not found or not readable.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $size = @filesize($path);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        if (is_int($size) && $size >= 0) {
            header('Content-Length: ' . $size);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to open ' . $filename . '.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        fpassthru($handle);
        fclose($handle);
        exit;
    }

    private static function importProductsCsv(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
        if (!function_exists('fc_auth_verify_csrf') || !fc_auth_verify_csrf($csrf)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid security token. Refresh and try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $source = strtoupper(trim((string) ($_POST['source'] ?? '')));
        if (!in_array($source, ['GO', 'JG'], true)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid source. Use GO or JG.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Choose a CSV file to import.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $file = $_FILES['file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Upload failed. Please try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'upload.csv');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Uploaded file is not readable.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($size <= 0 || $size > 50 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must be between 1 byte and 50MB.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Only .csv files can be imported.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $handle = fopen($tmp, 'rb');
        if ($handle === false) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to read the uploaded CSV.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $header = fgetcsv($handle);
        $rowCount = 0;
        $hasValidId = false;
        if (is_array($header)) {
            while (($row = fgetcsv($handle)) !== false) {
                if (!is_array($row) || $row === [null]) {
                    continue;
                }
                $first = trim((string) ($row[0] ?? ''));
                if ($first === '') {
                    continue;
                }
                $rowCount++;
                if (ctype_digit($first)) {
                    $hasValidId = true;
                }
            }
        }
        fclose($handle);

        $normalizedHeader = is_array($header)
            ? array_map(static fn($col): string => trim((string) $col), $header)
            : [];
        // Strip UTF-8 BOM from first column when present.
        if ($normalizedHeader !== [] && isset($normalizedHeader[0])) {
            $normalizedHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', $normalizedHeader[0]) ?? $normalizedHeader[0];
        }

        $required = ['ID', 'SKU', 'Name', 'Images'];
        $missing = [];
        foreach ($required as $column) {
            if (!in_array($column, $normalizedHeader, true)) {
                $missing[] = $column;
            }
        }
        if ($missing !== []) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV header must include: ' . implode(', ', $required) . '.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($rowCount < 1 || !$hasValidId) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must contain at least one product row with a numeric ID.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filename = 'wc-products-' . $source . '.csv';
        $dataDir = FC_ROOT . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dataDir) && !@mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to access the data directory.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $final = $dataDir . DIRECTORY_SEPARATOR . $filename;
        $tmpDest = $dataDir . DIRECTORY_SEPARATOR . '.wc-products-' . $source . '-import-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.csv';
        if (!@move_uploaded_file($tmp, $tmpDest)) {
            // Fallback for environments where move_uploaded_file is restricted after validation reads.
            if (!@copy($tmp, $tmpDest)) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Unable to store the uploaded CSV.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            @unlink($tmp);
        }

        $backup = $final . '.backup';
        @unlink($backup);
        $hadFinal = is_file($final);
        if ($hadFinal && !@rename($final, $backup)) {
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to prepare the existing products CSV for replacement.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!@rename($tmpDest, $final)) {
            if ($hadFinal && is_file($backup)) {
                @rename($backup, $final);
            }
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to replace the products CSV file.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        @unlink($backup);

        if (!function_exists('fc_storage_cache_dir')) {
            require_once FC_ROOT . '/config/storage.php';
        }
        @unlink(
            fc_storage_cache_dir('products')
            . DIRECTORY_SEPARATOR . 'wc-products-' . $source . '-count.json'
        );
        if (!function_exists('fc_wc_products_sku_index_invalidate')) {
            require_once FC_ROOT . '/config/wc_products_sku_index.php';
        }
        fc_wc_products_sku_index_invalidate($source);

        echo json_encode([
            'ok' => true,
            'message' => 'Imported ' . number_format($rowCount) . ' products into ' . $filename . '.',
            'source' => $source,
            'file' => $filename,
            'total' => $rowCount,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function dispatch(): void
    {
        self::handleApiRequest();
    }
}
