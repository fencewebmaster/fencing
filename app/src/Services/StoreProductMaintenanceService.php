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
    /** Rows the Add / Update Rows preview carries per response; the counts stay exact. */
    public const MERGE_ITEMS_SHOWN = 300;

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

    /**
     * Writes derived values onto many rows in one pass, for the System Products page's "Update
     * Products". Keyed by row index, never by SLUG — products.csv carries several rows per slug
     * (one per option/size), so a slug would rewrite the wrong ones.
     *
     * Only PRODUCT and DESCRIPTION may be written this way: SLUG identifies the row, and the
     * colour columns are SKUs that belong to the Missing SKUs tooling. A field missing from a
     * row's map, or blank, keeps what it has — this never empties a cell.
     *
     * @param array<int, array<string, string>> $fieldsByIndex
     * @return array{ok:bool,total?:int,updated?:int,error?:string}
     */
    public static function updateFields(array $fieldsByIndex): array
    {
        $writable = ['PRODUCT', 'DESCRIPTION'];
        $load = StoreProductModel::all();
        if (!$load['ok']) {
            return [
                'ok' => false,
                'error' => $load['error'] ?? 'Could not read products.csv.',
            ];
        }

        $columns = $load['columns'];
        $targets = array_values(array_intersect($writable, $columns));
        if ($targets === []) {
            return [
                'ok' => false,
                'error' => 'products.csv has no PRODUCT or DESCRIPTION column.',
            ];
        }

        $rows = [];
        $updated = 0;
        foreach ($load['rows'] as $i => $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[$column] = (string) ($row[$column] ?? '');
            }
            $index = (int) ($row['_rowIndex'] ?? $i);
            $fields = $fieldsByIndex[$index] ?? null;
            if (is_array($fields)) {
                foreach ($targets as $column) {
                    if (!array_key_exists($column, $fields)) {
                        continue;
                    }
                    $value = trim((string) $fields[$column]);
                    if ($value !== '') {
                        $line[$column] = self::sanitizeCsvFieldValue($value);
                        $updated++;
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
            'total' => count($rows),
            'updated' => $updated,
        ];
    }

    /**
     * Appends or updates rows from an uploaded CSV, for the System Products page's "Add / Update
     * Rows". A row is matched on SLUG + SUPPLIER + STYLE: a match updates that row in place,
     * anything else is appended at the end, so no existing row moves. The upload may carry any
     * subset of the file's columns, and a blank cell keeps what the row already has — switching a
     * colour off takes a literal OFF, because a half-filled sheet must never wipe SKUs by accident.
     *
     * Preview and apply run the same plan: apply recomputes it against the file as it is now rather
     * than trusting a preview the user looked at a while ago. Keys that products.csv holds more than
     * once (Barr's gate+kit) are matched to the upload's rows in order, so a Download CSV → edit →
     * upload round trip of the whole file is a no-op; an upload that repeats a key more often than
     * the file holds it is a problem row, since the extra copy could only be appended as a duplicate
     * the cart would never read. Problem rows block the apply rather than being skipped, so what was
     * applied is exactly what the sheet said. The wrong-format cases Excel produces (xlsx, UTF-16,
     * cp1252, semicolons, CR-only) are named rather than reported as a missing SLUG column.
     *
     * @return array{ok:bool,error?:string,serverError?:bool,unknownColumns?:list<string>,add?:int,update?:int,unchanged?:int,problems?:list<string>,warnings?:list<string>,items?:list<array{action:string,slug:string,supplier:string,style:string,changes:list<array{column:string,from:string,to:string}>}>,itemsTotal?:int,applied?:bool,total?:int}
     */
    public static function mergeCsv(string $uploadPath, bool $apply): array
    {
        $load = StoreProductModel::all();
        if (!$load['ok']) {
            return [
                'ok' => false,
                'serverError' => true,
                'error' => $load['error'] ?? 'Could not read products.csv.',
            ];
        }
        $columns = $load['columns'];
        $keyColumns = ['SLUG', 'SUPPLIER', 'STYLE'];

        $content = @file_get_contents($uploadPath);
        if ($content === false) {
            return ['ok' => false, 'serverError' => true, 'error' => 'Unable to read the uploaded CSV.'];
        }
        $formatProblem = self::csvFormatProblem($content);
        if ($formatProblem !== '') {
            return ['ok' => false, 'error' => $formatProblem];
        }
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        $handle = fopen('php://memory', 'r+');
        if ($handle === false) {
            return ['ok' => false, 'serverError' => true, 'error' => 'Unable to read the uploaded CSV.'];
        }
        fwrite($handle, $content);
        rewind($handle);
        unset($content);
        // RFC 4180 (what Excel and Sheets write) doubles quotes and has no escape character; PHP's
        // default backslash escape would keep a stray quote out of a cell like a\"b.
        $readRow = static fn () => fgetcsv($handle, null, ',', '"', '');

        $header = $readRow();
        if (!is_array($header) || $header === [null]) {
            fclose($handle);
            return ['ok' => false, 'error' => 'The CSV has no header row.'];
        }
        $header = array_map([self::class, 'trimCell'], array_map('strval', $header));
        if (count($header) === 1 && (str_contains($header[0], ';') || str_contains($header[0], "\t"))) {
            fclose($handle);
            return ['ok' => false, 'error' => 'The file is not comma-separated. In Excel use Save As → CSV UTF-8 (Comma delimited).'];
        }
        // Excel leaves empty names after the last column; a blank name between two real ones is a
        // column whose values would be dropped without a word.
        $namedCount = 0;
        foreach ($header as $index => $column) {
            if ($column !== '') {
                $namedCount = $index + 1;
            }
        }
        $named = array_slice($header, 0, $namedCount);
        foreach ($named as $index => $column) {
            if ($column === '') {
                fclose($handle);
                return ['ok' => false, 'error' => 'Header column ' . ($index + 1) . ' has no name. Fix the header and try again.'];
            }
        }
        $missing = array_values(array_diff($keyColumns, $named));
        if ($missing !== []) {
            fclose($handle);
            return ['ok' => false, 'error' => 'CSV header must include ' . implode(', ', $keyColumns) . ' — missing ' . implode(', ', $missing) . '.'];
        }
        $unknown = array_values(array_unique(array_filter($named, static fn (string $col): bool => !in_array($col, $columns, true))));
        if ($unknown !== []) {
            // A misspelt colour column that was skipped would look updated and never be.
            fclose($handle);
            return [
                'ok' => false,
                'error' => 'products.csv has no column named ' . implode(', ', $unknown) . '. Fix the header and try again.',
                'unknownColumns' => $unknown,
            ];
        }
        $repeated = array_keys(array_filter(array_count_values($named), static fn (int $n): bool => $n > 1));
        if ($repeated !== []) {
            fclose($handle);
            return ['ok' => false, 'error' => 'The CSV header repeats ' . implode(', ', $repeated) . '.'];
        }

        $current = [];
        $occurrences = [];
        $knownSuppliers = [];
        $knownStyles = [];
        foreach ($load['rows'] as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[$column] = (string) ($row[$column] ?? '');
            }
            $parts = array_map(static fn (string $col): string => self::trimCell($line[$col]), $keyColumns);
            $occurrences[implode("\x1F", $parts)][] = count($current);
            $knownSuppliers[$parts[1]] = true;
            $knownStyles[$parts[2]] = true;
            $current[] = $line;
        }

        $plan = [];
        $planOrder = [];
        $problems = [];
        $warnings = [];
        $uploadSeen = [];
        $firstRowOf = [];
        $rowNumber = 1;
        while (($data = $readRow()) !== false) {
            $rowNumber++;
            if (!is_array($data) || $data === [null]) {
                continue;
            }
            $incoming = [];
            $hasValue = false;
            foreach ($named as $index => $column) {
                $value = self::trimCell((string) ($data[$index] ?? ''));
                $incoming[$column] = $value;
                if ($value !== '') {
                    $hasValue = true;
                }
            }
            $overflow = false;
            foreach (array_slice($data, $namedCount) as $extra) {
                if (self::trimCell((string) $extra) !== '') {
                    $overflow = true;
                }
            }
            if ($overflow) {
                $problems[] = 'Row ' . $rowNumber . ' has a value past the last header column.';
                continue;
            }
            if (!$hasValue) {
                continue;
            }
            $blank = array_values(array_filter($keyColumns, static fn (string $col): bool => $incoming[$col] === ''));
            if ($blank !== []) {
                $problems[] = 'Row ' . $rowNumber . ': ' . implode(' and ', $blank) . (count($blank) === 1 ? ' is' : ' are') . ' blank.';
                continue;
            }
            if ($incoming['SLUG'] === 'SLUG' && $incoming['SUPPLIER'] === 'SUPPLIER' && $incoming['STYLE'] === 'STYLE') {
                $problems[] = 'Row ' . $rowNumber . ' is a second header row.';
                continue;
            }
            $hazard = '';
            foreach ($incoming as $column => $value) {
                if (in_array($column, $keyColumns, true) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                    $hazard = $column . ' cannot start with ' . $value[0] . '.';
                    break;
                }
                // writeCsv's backslash escape turns a trailing backslash, or \", into a quote the
                // next read never closes, swallowing the rows after it.
                if (str_ends_with($value, '\\') || str_contains($value, '\\"')) {
                    $hazard = $column . ' has a backslash products.csv cannot store safely.';
                    break;
                }
            }
            if ($hazard !== '') {
                $problems[] = 'Row ' . $rowNumber . ': ' . $hazard;
                continue;
            }

            $parts = [$incoming['SLUG'], $incoming['SUPPLIER'], $incoming['STYLE']];
            $key = implode("\x1F", $parts);
            $label = implode(' / ', $parts);
            $nth = $uploadSeen[$key] = ($uploadSeen[$key] ?? 0) + 1;
            $firstRowOf[$key] = $firstRowOf[$key] ?? $rowNumber;
            $fileRows = $occurrences[$key] ?? [];
            if ($nth > 1 && $nth > count($fileRows)) {
                $problems[] = 'Row ' . $rowNumber . ' repeats row ' . $firstRowOf[$key] . ' (' . $label . ')'
                    . ($fileRows === [] ? '.' : '; products.csv holds it only ' . (count($fileRows) === 1 ? 'once.' : count($fileRows) . ' times.'));
                continue;
            }
            // A value the planner can never match on: every live row uses one of six styles and GO or JG.
            if (!isset($knownSuppliers[$incoming['SUPPLIER']])) {
                $warnings[] = 'Row ' . $rowNumber . ': SUPPLIER ' . $incoming['SUPPLIER'] . ' is not used by any existing row.';
            }
            if (!isset($knownStyles[$incoming['STYLE']])) {
                $warnings[] = 'Row ' . $rowNumber . ': STYLE ' . $incoming['STYLE'] . ' is not used by any existing row.';
            }

            $changes = [];
            $planKey = $key . "\x1F" . $nth;
            $planOrder[] = $planKey;
            if ($fileRows !== []) {
                $index = $fileRows[$nth - 1];
                $existing = $current[$index];
                foreach ($incoming as $column => $value) {
                    if ($value === '' || in_array($column, $keyColumns, true)) {
                        continue;
                    }
                    $value = self::sanitizeCsvFieldValue($value);
                    if ($value !== $existing[$column]) {
                        $changes[] = ['column' => $column, 'from' => $existing[$column], 'to' => $value];
                    }
                }
                if (count($fileRows) > 1) {
                    $warnings[] = 'products.csv holds ' . $label . ' ' . count($fileRows) . ' times; its rows are matched to the upload in order.';
                }
                $plan[$planKey] = ['action' => $changes === [] ? 'unchanged' : 'update', 'index' => $index, 'changes' => $changes, 'parts' => $parts];
            } else {
                $line = array_fill_keys($columns, '');
                foreach ($incoming as $column => $value) {
                    $line[$column] = in_array($column, $keyColumns, true) ? $value : self::sanitizeCsvFieldValue($value);
                    if ($value !== '' && !in_array($column, $keyColumns, true)) {
                        $changes[] = ['column' => $column, 'from' => '', 'to' => $line[$column]];
                    }
                }
                $plan[$planKey] = ['action' => 'add', 'line' => $line, 'changes' => $changes, 'parts' => $parts];
            }
        }
        fclose($handle);

        $items = [];
        $tally = ['add' => 0, 'update' => 0, 'unchanged' => 0];
        foreach ($planOrder as $planKey) {
            $entry = $plan[$planKey];
            $tally[$entry['action']]++;
            $items[] = [
                'action' => $entry['action'],
                'slug' => $entry['parts'][0],
                'supplier' => $entry['parts'][1],
                'style' => $entry['parts'][2],
                'changes' => $entry['changes'],
            ];
        }
        $result = [
            'ok' => true,
            'add' => $tally['add'],
            'update' => $tally['update'],
            'unchanged' => $tally['unchanged'],
            'problems' => $problems,
            'warnings' => array_values(array_unique($warnings)),
            // The dialog lists the first few hundred; a whole-file upload would otherwise answer with
            // every cell of every row.
            'items' => array_slice($items, 0, self::MERGE_ITEMS_SHOWN),
            'itemsTotal' => count($items),
            'applied' => false,
            'total' => count($current),
        ];
        if ($planOrder === [] && $problems === []) {
            $result['ok'] = false;
            $result['error'] = 'The CSV has no product rows.';
            return $result;
        }
        if (!$apply) {
            return $result;
        }
        if ($problems !== []) {
            $result['ok'] = false;
            $result['error'] = count($problems) . ' row' . (count($problems) === 1 ? ' needs' : 's need') . ' fixing. Fix the file and try again.';
            return $result;
        }
        if ($tally['add'] + $tally['update'] === 0) {
            $result['ok'] = false;
            $result['error'] = 'Every row already matches products.csv — nothing to apply.';
            return $result;
        }

        $rows = $current;
        foreach ($planOrder as $planKey) {
            $entry = $plan[$planKey];
            if ($entry['action'] === 'update') {
                foreach ($entry['changes'] as $change) {
                    $rows[$entry['index']][$change['column']] = $change['to'];
                }
            } elseif ($entry['action'] === 'add') {
                $rows[] = $entry['line'];
            }
        }
        $written = self::writeCsv($columns, $rows);
        if (!$written['ok']) {
            return $written + ['serverError' => true];
        }
        $result['applied'] = true;
        $result['total'] = count($rows);

        return $result;
    }

    /**
     * Names the file shapes Excel and Sheets produce that are not a UTF-8 comma CSV, so the user
     * is told how to save the file instead of being told SLUG is missing.
     */
    private static function csvFormatProblem(string $content): string
    {
        if (str_starts_with($content, "PK\x03\x04")) {
            return 'This is an Excel workbook, not a CSV. In Excel use Save As → CSV UTF-8 (Comma delimited).';
        }
        if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
            return 'The file is UTF-16 (Unicode Text). Save it as CSV UTF-8 (Comma delimited).';
        }
        if (preg_match('//u', $content) !== 1) {
            return 'The file is not UTF-8 (Excel\'s plain "CSV" is not). Save it as CSV UTF-8 (Comma delimited).';
        }
        if (str_contains($content, "\r") && !str_contains($content, "\n")) {
            return 'The file uses old Mac line endings. Save it as CSV UTF-8 (Comma delimited).';
        }

        return '';
    }

    /** trim() plus the no-break space and BOM a spreadsheet can leave around a cell. */
    private static function trimCell(string $value): string
    {
        $trimmed = preg_replace('/^[\s\x{00A0}\x{FEFF}]+|[\s\x{00A0}\x{FEFF}]+$/u', '', $value);

        return is_string($trimmed) ? $trimmed : trim($value);
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
