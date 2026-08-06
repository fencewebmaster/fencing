<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\FormatHelper;

/**
 * Centralized app storage paths (cache + sessions).
 */
final class CacheStorageService
{
    /**
     * Absolute path to writable/storage.
     */
    public static function root(): string
    {
        $root = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($root)) {
            @mkdir($root, 0775, true);
        }

        return $root;
    }

    /**
     * Absolute path to writable/storage/cache or writable/storage/cache/{bucket}.
     */
    public static function cacheDir(string $bucket = ''): string
    {
        $dir = self::root() . DIRECTORY_SEPARATOR . 'cache';
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
     * Absolute path to writable/storage/sessions.
     */
    public static function sessionsDir(): string
    {
        $dir = self::root() . DIRECTORY_SEPARATOR . 'sessions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Absolute path to writable/storage/presence.
     */
    public static function presenceDir(): string
    {
        $dir = self::root() . DIRECTORY_SEPARATOR . 'presence';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Known file-cache buckets under writable/storage/cache/.
     *
     * @return list<string>
     */
    public static function cacheBuckets(): array
    {
        return ['lookup', 'products'];
    }

    /**
     * Count *.json files and total bytes in one cache bucket.
     *
     * @return array{files:int,bytes:int,label:string}
     */
    public static function cacheBucketStats(string $bucket): array
    {
        $files = 0;
        $bytes = 0;
        $dir = self::cacheDir($bucket);
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
            'label' => number_format($files) . ' ' . $noun . ' (' . FormatHelper::bytes($bytes) . ')',
        ];
    }

    /**
     * Stats for each known bucket plus an "all" aggregate.
     *
     * @return array{all:array{files:int,bytes:int,label:string},buckets:array<string,array{files:int,bytes:int,label:string}>}
     */
    public static function cacheStats(): array
    {
        $buckets = [];
        $totalFiles = 0;
        $totalBytes = 0;

        foreach (self::cacheBuckets() as $name) {
            $stat = self::cacheBucketStats($name);
            $buckets[$name] = $stat;
            $totalFiles += $stat['files'];
            $totalBytes += $stat['bytes'];
        }

        $buckets['cloudflare'] = CloudflareService::cacheStats();

        $noun = $totalFiles === 1 ? 'item' : 'items';

        return [
            'all' => [
                'files' => $totalFiles,
                'bytes' => $totalBytes,
                'label' => number_format($totalFiles) . ' ' . $noun . ' (' . FormatHelper::bytes($totalBytes) . ')',
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
    public static function purgeCache(string $bucket = ''): array
    {
        $bucket = strtolower(trim($bucket));
        $known = self::cacheBuckets();

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
            $dir = self::cacheDir($name);
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
}
