<?php
/**
 * Centralized app storage paths (cache + sessions).
 */

declare(strict_types=1);

/**
 * Absolute path to data/storage.
 */
function fc_storage_root(): string
{
    $root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'storage';
    if (!is_dir($root)) {
        @mkdir($root, 0775, true);
    }

    return $root;
}

/**
 * Absolute path to data/storage/cache or data/storage/cache/{bucket}.
 */
function fc_storage_cache_dir(string $bucket = ''): string
{
    $dir = fc_storage_root() . DIRECTORY_SEPARATOR . 'cache';
    $bucket = trim(str_replace(['\\', '/'], '', $bucket));
    if ($bucket !== '') {
        $dir .= DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_\-]+/i', '_', $bucket);
    }

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

/**
 * Absolute path to data/storage/sessions.
 */
function fc_storage_sessions_dir(): string
{
    $dir = fc_storage_root() . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

/**
 * Known file-cache buckets under data/storage/cache/.
 *
 * @return list<string>
 */
function fc_storage_cache_buckets(): array
{
    return ['lookup', 'products'];
}

/**
 * Human-readable byte size (e.g. 1MB, 512KB).
 */
function fc_storage_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . 'B';
    }

    $units = ['KB', 'MB', 'GB', 'TB'];
    $value = (float) $bytes;
    $unit = 'KB';
    foreach ($units as $candidate) {
        $value /= 1024;
        $unit = $candidate;
        if ($value < 1024) {
            break;
        }
    }

    if ($value >= 100 || abs($value - round($value)) < 0.05) {
        return (string) (int) round($value) . $unit;
    }

    return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . $unit;
}

/**
 * Count *.json files and total bytes in one cache bucket.
 *
 * @return array{files:int,bytes:int,label:string}
 */
function fc_storage_cache_bucket_stats(string $bucket): array
{
    $files = 0;
    $bytes = 0;
    $dir = fc_storage_cache_dir($bucket);
    if (is_dir($dir)) {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }
            $files++;
            $bytes += (int) @filesize($file);
        }
    }

    $noun = $files === 1 ? 'item' : 'items';

    return [
        'files' => $files,
        'bytes' => $bytes,
        'label' => number_format($files) . ' ' . $noun . ' (' . fc_storage_format_bytes($bytes) . ')',
    ];
}

/**
 * Stats for each known bucket plus an "all" aggregate.
 *
 * @return array{all:array{files:int,bytes:int,label:string},buckets:array<string,array{files:int,bytes:int,label:string}>}
 */
function fc_storage_cache_stats(): array
{
    $buckets = [];
    $totalFiles = 0;
    $totalBytes = 0;

    if (!function_exists('fc_cloudflare_cache_stats')) {
        require_once __DIR__ . '/cloudflare.php';
    }

    foreach (fc_storage_cache_buckets() as $name) {
        $stat = fc_storage_cache_bucket_stats($name);
        $buckets[$name] = $stat;
        $totalFiles += $stat['files'];
        $totalBytes += $stat['bytes'];
    }

    $buckets['cloudflare'] = fc_cloudflare_cache_stats();

    $noun = $totalFiles === 1 ? 'item' : 'items';

    return [
        'all' => [
            'files' => $totalFiles,
            'bytes' => $totalBytes,
            'label' => number_format($totalFiles) . ' ' . $noun . ' (' . fc_storage_format_bytes($totalBytes) . ')',
        ],
        'buckets' => $buckets,
    ];
}

/**
 * Delete *.json cache files in one bucket, or all buckets when $bucket is empty / "all".
 * Leaves .gitkeep and sessions untouched.
 *
 * @return array{ok:bool,deleted:int,targets:list<string>,error?:string}
 */
function fc_storage_purge_cache(string $bucket = ''): array
{
    $bucket = strtolower(trim($bucket));
    $known = fc_storage_cache_buckets();

    if ($bucket === '' || $bucket === 'all') {
        $targets = $known;
    } elseif (in_array($bucket, $known, true)) {
        $targets = [$bucket];
    } else {
        return [
            'ok' => false,
            'deleted' => 0,
            'targets' => [],
            'error' => 'Unknown cache target.',
        ];
    }

    $deleted = 0;
    foreach ($targets as $name) {
        $dir = fc_storage_cache_dir($name);
        if (!is_dir($dir)) {
            continue;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }
            if (@unlink($file)) {
                $deleted++;
            }
        }
    }

    return [
        'ok' => true,
        'deleted' => $deleted,
        'targets' => $targets,
    ];
}
