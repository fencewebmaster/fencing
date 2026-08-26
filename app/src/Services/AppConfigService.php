<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Root config.php accessor. Re-includes the file on every call to `all()` — no caching.
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
        // Site-level fields (supplier, gtag_id, gtm_id, cloudflare_zone_id, site_logo,
        // pid_prefix) live nested under sites.{key}.{section} — transpose that into the
        // same {siteKey: value} shape this method has always returned.
        $sites = $cfg->sites ?? null;
        if (is_object($sites) || is_array($sites)) {
            foreach ((array) $sites as $siteKey => $siteValue) {
                if (!is_object($siteValue) && !is_array($siteValue)) {
                    continue;
                }
                $siteValue = (array) $siteValue;
                $value = $siteValue[$section] ?? null;
                if (!is_scalar($value) && $value !== null) {
                    continue;
                }
                $normalized = trim((string) $value);
                if ($normalized === '') {
                    continue;
                }
                $map[(string) $siteKey] = $normalized;
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
