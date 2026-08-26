<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic file utilities.
 */
final class FileHelper
{
    /**
     * Load a CSV with a header row into a list of associative rows keyed by
     * normalized header name (lowercase, spaces -> underscores, hyphens stripped).
     *
     * @return array<int, array<string, mixed>>|false
     */
    public static function loadCsv(string $file = '')
    {
        if (!file_exists($file)) {
            return false;
        }

        // Defense-in-depth: every current caller already passes a path under writable/, so
        // this changes nothing for them — it only rejects a path that has traversed outside
        // it, in case a future caller ever forwards unsanitized request input here directly.
        $real = realpath($file);
        $base = realpath(defined('FC_ROOT') ? FC_ROOT . '/writable' : '');
        if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return false;
        }

        $handle = fopen($file, 'r');

        $i = 0;
        $header = [];
        $orderInfo = [];
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($i == 0) {
                $header = $data;
            } else {
                $e = 0;
                foreach ($data as $d) {
                    if (@$header[$e]) {
                        $col = str_replace([' ', '-'], ['_', ''], strtolower(rtrim($header[$e])));
                        $orderInfo[$col] = $d;
                        $e++;
                    }
                }

                $rows[$i - 1] = $orderInfo;
            }

            $i++;
        }

        return $rows;
    }

    /**
     * Minify a CSS file to disk as `*.min.css` alongside it.
     */
    public static function minifyCss(string $file = ''): void
    {
        if (!file_exists($file)) {
            return;
        }

        $css = file_get_contents($file);

        $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
        $css = preg_replace('/\s{2,}/', ' ', $css);
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
        $css = preg_replace('/;}/', '}', $css);

        $minFile = str_replace('.css', '.min.css', $file);

        file_put_contents($minFile, $css);
    }

    /**
     * Read and json-decode a file; null when missing, unreadable, empty, or not an array payload.
     *
     * @return array<mixed>|null
     */
    public static function readJsonFile(string $path): ?array
    {
        if (!is_readable($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }
}
