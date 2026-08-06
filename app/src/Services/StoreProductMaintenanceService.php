<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\StoreProductModel;

/**
 * Store products (writable/products.csv) mutation operations. CSRF verification stays in the
 * controller/dispatch layer — these methods take clean, already-parsed values.
 */
final class StoreProductMaintenanceService
{
    /**
     * @param list<int> $order Permutation of row indices (each 0..n-1 exactly once).
     * @return array{ok:bool,total?:int,error?:string}
     */
    public static function reorder(array $order): array
    {
        $load = StoreProductModel::all();
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

        return self::writeCsv($columns, $ordered);
    }

    /**
     * @param array<string, string> $fields Column => value (CSV columns only).
     * @return array{ok:bool,total?:int,rowIndex?:int,error?:string}
     */
    public static function update(int $rowIndex, array $fields): array
    {
        $load = StoreProductModel::all();
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
                        $line[$column] = self::sanitizeCsvFieldValue((string) $fields[$column]);
                    }
                }
            }
            $rows[] = $line;
        }

        $written = self::writeCsv($columns, $rows);
        if (!$written['ok']) {
            return $written;
        }

        return [
            'ok' => true,
            'total' => $rowCount,
            'rowIndex' => $rowIndex,
        ];
    }

    public static function invalidateCache(): void
    {
        $cacheDir = CacheStorageService::cacheDir('products');
        @unlink($cacheDir . DIRECTORY_SEPARATOR . 'products-csv-count.json');
        @unlink($cacheDir . DIRECTORY_SEPARATOR . 'products-csv-filters.json');
    }

    /**
     * Prevents CSV/spreadsheet formula injection: a cell opened in Excel/Sheets that starts
     * with =, +, -, or @ can execute as a formula. Prefixing with a quote keeps the value
     * as visible plain text.
     */
    private static function sanitizeCsvFieldValue(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * @param list<string> $columns
     * @param list<array<string,string>> $rows
     * @return array{ok:bool,total?:int,error?:string}
     */
    private static function writeCsv(array $columns, array $rows): array
    {
        $path = StoreProductModel::csvPath();
        $dir = dirname($path);

        if (!is_writable($dir)) {
            return [
                'ok' => false,
                'error' => 'writable/ directory is not writable.',
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

        self::invalidateCache();

        return [
            'ok' => true,
            'total' => count($rows),
        ];
    }
}
