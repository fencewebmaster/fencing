<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

/**
 * Version history (writable/versions.csv) data access — the storage half of the Version
 * Manager. VersionService owns every rule (validation, semver, sanitizing); this class only
 * reads and rewrites rows, so a move to MySQL replaces this file and nothing above it.
 *
 * The CSV is header-driven: columns are matched by name, and a column this build does not
 * know (added by a newer one) rides along on every rewrite instead of being dropped.
 * Quoting is RFC 4180 (escape character disabled), so HTML with quotes, commas and line
 * breaks round-trips exactly; PHP's default backslash escape corrupts any `\"` in a field.
 */
final class VersionModel
{
    public const COLUMNS = [
        'id', 'date_time', 'type', 'version', 'version_level', 'version_mode',
        'published', 'description', 'created_at', 'updated_at',
    ];

    public static function csvPath(): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'versions.csv';
    }

    /**
     * Every row in file order, keyed by column name. A missing file is an empty history.
     *
     * @return array{ok:bool,rows:list<array<string,string>>,columns:list<string>,error?:string}
     */
    public static function read(): array
    {
        $path = self::csvPath();
        if (!is_file($path)) {
            return ['ok' => true, 'rows' => [], 'columns' => self::COLUMNS];
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return ['ok' => false, 'rows' => [], 'columns' => self::COLUMNS, 'error' => 'versions.csv is not readable.'];
        }

        try {
            return self::readHandle($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Read-modify-write under an exclusive lock, so two saves can never interleave and lose
     * a row (products.csv has no such lock). $change gets the current rows and returns
     * ['ok' => bool, 'rows' => list<row>, ...]; the rows are written atomically when ok is
     * true and 'rows' is present. Everything else it returns is passed back to the caller.
     *
     * @param callable(list<array<string,string>>):array<string,mixed> $change
     * @return array<string,mixed>
     */
    public static function mutate(callable $change): array
    {
        $path = self::csvPath();
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            return ['ok' => false, 'error' => 'The writable/ folder is not writable.'];
        }
        if (is_file($path) && !is_writable($path)) {
            return ['ok' => false, 'error' => 'versions.csv is not writable.'];
        }

        $lock = @fopen($path . '.lock', 'c');
        if ($lock === false) {
            return ['ok' => false, 'error' => 'Could not open the versions.csv lock file.'];
        }
        if (!flock($lock, LOCK_EX)) {
            fclose($lock);

            return ['ok' => false, 'error' => 'Could not lock versions.csv.'];
        }

        try {
            $current = self::read();
            if (!$current['ok']) {
                return ['ok' => false, 'error' => (string) ($current['error'] ?? 'versions.csv is not readable.')];
            }

            $result = $change($current['rows']);
            if (empty($result['ok']) || !array_key_exists('rows', $result)) {
                return $result;
            }

            $written = self::write($result['rows'], $current['columns']);
            if (!$written['ok']) {
                return $written;
            }
            unset($result['rows']);

            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @param resource $handle
     * @return array{ok:bool,rows:list<array<string,string>>,columns:list<string>}
     */
    private static function readHandle($handle): array
    {
        $header = fgetcsv($handle, null, ',', '"', '');
        if ($header === false || $header === [null]) {
            return ['ok' => true, 'rows' => [], 'columns' => self::COLUMNS];
        }

        $fileColumns = [];
        foreach ($header as $i => $name) {
            $name = strtolower(trim((string) $name));
            if ($i === 0) {
                $name = preg_replace('/^\xEF\xBB\xBF/', '', $name) ?? $name; // Excel's UTF-8 BOM
            }
            $fileColumns[$i] = $name;
        }

        $rows = [];
        while (($data = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($data === [null]) {
                continue; // blank line
            }
            $row = [];
            foreach ($fileColumns as $i => $name) {
                if ($name !== '') {
                    $row[$name] = isset($data[$i]) ? (string) $data[$i] : '';
                }
            }
            $rows[] = $row;
        }

        $extra = array_values(array_diff(array_filter($fileColumns, 'strlen'), self::COLUMNS));

        return ['ok' => true, 'rows' => $rows, 'columns' => array_merge(self::COLUMNS, $extra)];
    }

    /**
     * @param list<array<string,string>> $rows
     * @param list<string> $columns
     * @return array{ok:bool,error?:string}
     */
    private static function write(array $rows, array $columns): array
    {
        $path = self::csvPath();
        $columns = array_values(array_unique(array_merge(self::COLUMNS, $columns)));
        $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));

        $handle = @fopen($tmp, 'wb');
        if ($handle === false) {
            return ['ok' => false, 'error' => 'Could not create a temporary file in writable/.'];
        }

        $ok = fputcsv($handle, $columns, ',', '"', '') !== false;
        foreach ($rows as $row) {
            if (!$ok) {
                break;
            }
            $line = [];
            foreach ($columns as $column) {
                $line[] = isset($row[$column]) ? (string) $row[$column] : '';
            }
            $ok = fputcsv($handle, $line, ',', '"', '') !== false;
        }
        $ok = fflush($handle) && $ok;
        fclose($handle);

        if (!$ok) {
            @unlink($tmp);

            return ['ok' => false, 'error' => 'Could not write versions.csv (is the disk full?).'];
        }

        // Windows refuses to replace a file another request has open for reading; that clears in milliseconds.
        for ($attempt = 0; $attempt < 10; $attempt++) {
            if (@rename($tmp, $path)) {
                return ['ok' => true];
            }
            usleep(50000);
        }
        @unlink($tmp);

        return ['ok' => false, 'error' => 'Could not save versions.csv.'];
    }
}
