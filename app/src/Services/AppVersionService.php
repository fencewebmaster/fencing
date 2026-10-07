<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\FileHelper;

/**
 * The app's version label ("v13.12.0") printed in the planner footer, the admin sidebar and the
 * login page: the newest published release in Releases (admin; the one /versions marks Latest),
 * or '' while nothing is published. Branding no longer holds a version.
 *
 * Every page view asks for it, so this class stays small and answers from
 * writable/storage/cache/app-version.json, keyed to versions.csv's path, mtime, size and inode (a
 * copied-up history refreshes it). Only a miss loads VersionService and parses the history (~3 ms
 * at 133 releases, growing with every report); VersionService::afterWrite() calls forget().
 */
final class AppVersionService
{
    /** The label for the rest of this request; null until first asked or after forget(). */
    private static ?string $label = null;

    public static function label(): string
    {
        if (self::$label !== null) {
            return self::$label;
        }

        // VersionModel::csvPath(), spelled out so a page view does not load VersionModel for it.
        $source = FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'versions.csv';
        $stat = @stat($source);
        if ($stat === false) {
            return self::$label = '';
        }

        $key = ['source' => $source, 'mtime' => (int) $stat['mtime'], 'size' => (int) $stat['size'], 'ino' => (string) $stat['ino']];
        $cached = FileHelper::readJsonFile(self::cacheFile());
        $fresh = $cached !== null && is_string($cached['version'] ?? null);
        foreach ($key as $field => $value) {
            $fresh = $fresh && ($cached[$field] ?? null) === $value;
        }
        if ($fresh) {
            return self::$label = $cached['version'];
        }

        $latest = VersionService::latest(true);
        self::$label = $latest !== null ? 'v' . $latest['version'] : '';
        CacheStorageService::cacheDir(); // creates writable/storage/cache on a fresh install
        @file_put_contents(self::cacheFile(), (string) json_encode($key + ['version' => self::$label], JSON_UNESCAPED_SLASHES), LOCK_EX);

        return self::$label;
    }

    /**
     * Drop the cached label after a Releases write: a Publish/Unpublish toggle keeps the
     * file's size and can land in the same second as the cache key's mtime.
     */
    public static function forget(): void
    {
        self::$label = null;
        @unlink(self::cacheFile());
    }

    /** CacheStorageService::cacheDir() . '/app-version.json', spelled out for the same reason. */
    private static function cacheFile(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'app-version.json';
    }
}
