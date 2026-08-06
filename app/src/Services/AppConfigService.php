<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Root config.php accessor (config/helpers.php migration). Re-includes the file on
 * every call to `all()` — no caching — matching the original `config()` semantics.
 */
final class AppConfigService
{
    public static function all(): \stdClass
    {
        include dirname(__DIR__, 3) . '/config.php';

        return json_decode(json_encode($config));
    }

    /**
     * Site-keyed map from config.php (e.g. supplier, gtag_id, gtm_id).
     *
     * @return array<string, string>
     */
    public static function siteMap(string $section): array
    {
        static $cache = [];
        $section = trim($section);
        if ($section === '') {
            return [];
        }
        if (array_key_exists($section, $cache)) {
            return $cache[$section];
        }

        $map = [];
        $cfg = self::all();
        $sectionValue = $cfg->{$section} ?? null;
        if (is_object($sectionValue) || is_array($sectionValue)) {
            foreach ((array) $sectionValue as $key => $value) {
                if (!is_scalar($value) && $value !== null) {
                    continue;
                }
                $normalized = trim((string) $value);
                if ($normalized === '') {
                    continue;
                }
                $map[(string) $key] = $normalized;
            }
        }

        $cache[$section] = $map;

        return $map;
    }

    /**
     * Read one site value from a config.php site map.
     */
    public static function siteValue(string $section, string $siteKey, string $fallback = ''): string
    {
        $siteKey = trim($siteKey);
        if ($siteKey === '') {
            return trim($fallback);
        }

        $map = self::siteMap($section);
        if (isset($map[$siteKey]) && $map[$siteKey] !== '') {
            return $map[$siteKey];
        }

        return trim($fallback);
    }
}
