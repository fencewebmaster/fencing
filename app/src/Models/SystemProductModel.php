<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\CacheStorageService;

/**
 * System products (writable/wc-products-GO.csv, writable/wc-products-JG.csv) data access.
 */
final class SystemProductModel
{
    public static function csvPath(string $source): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'wc-products-' . $source . '.csv';
    }

    private static function cacheDir(): string
    {
        return CacheStorageService::cacheDir('products');
    }

    public static function invalidateCache(string $source): void
    {
        $source = strtoupper(trim($source));
        @unlink(self::cacheDir() . DIRECTORY_SEPARATOR . 'wc-products-' . $source . '-count.json');
    }

    /**
     * @return array{ok:bool,columns:list<string>,rows:list<array<string,string>>,total:int,file:string,source?:string,error?:string}
     */
    public static function all(string $source): array
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
        $path = self::csvPath($source);

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
    public static function count(string $source): array
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
        $path = self::csvPath($source);

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
        $cacheDir = self::cacheDir();
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
            if (!is_array($data) || !self::rowIsValid($data, $firstCol)) {
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
    public static function query(
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
        $path = self::csvPath($source);

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
            $countMeta = self::count($source);
            if (!empty($countMeta['ok'])) {
                $cachedTotal = (int) $countMeta['total'];
            }
        }

        $matched = 0;
        $unfiltered = 0;
        $rows = [];
        $rowIndex = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (!is_array($data) || !self::rowIsValid($data, $firstCol)) {
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
    private static function rowIsValid(array $data, string $firstCol): bool
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
}
