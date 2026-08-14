<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Assembles the fence catalog from `writable/fences/*.php` (dropping non-live styles
 * outside dev/localhost) and loads the legacy `fc_color()` / `fc_state()` /
 * `fc_timeframe()` / `fc_extra_needed()` view helpers (`app/src/Helpers/fc_functions.php`).
 *
 * The frontend entry scripts used to `include 'writable/settings.php'` at global
 * scope, which is what made `$fences` a true global. Controllers run inside a
 * method, so boot() has to publish `$fences` into `$GLOBALS` explicitly —
 * CartBuilderService and FenceCatalogService both read it via `global $fences`.
 */
final class FenceSettingsService
{
    private static bool $loaded = false;

    /**
     * Load the catalog (once) and return it.
     *
     * @return array<string, mixed>
     */
    public static function fences(): array
    {
        self::boot();

        return isset($GLOBALS['fences']) && is_array($GLOBALS['fences'])
            ? $GLOBALS['fences']
            : [];
    }

    /**
     * Ensure the fence catalog has been assembled and `$GLOBALS['fences']` is set.
     */
    public static function boot(): void
    {
        // Always cheap (every definition is function_exists-guarded) and must happen even
        // when the catalog is already loaded, so the fc_*() view helpers are never missing.
        require_once dirname(__DIR__) . '/Helpers/fc_functions.php';

        if (self::$loaded && isset($GLOBALS['fences'])) {
            return;
        }

        self::$loaded = true;

        if (isset($GLOBALS['fences']) && is_array($GLOBALS['fences'])) {
            return;
        }

        $GLOBALS['fences'] = self::loadFenceCatalog();
    }

    /**
     * Assemble the fence catalog from writable/fences/*.php, dropping non-live
     * styles outside dev/localhost (previously writable/settings.php).
     *
     * @return array<string, mixed>
     */
    private static function loadFenceCatalog(): array
    {
        $uriSegments = explode('/', trim((string) parse_url((string) $_SERVER['PHP_SELF'], PHP_URL_PATH), '/'));
        $hostHeader = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $host = parse_url('//' . $hostHeader, PHP_URL_HOST);
        if (!$host) {
            $host = $hostHeader;
        }

        $fences = [];
        foreach (glob(FC_ROOT . '/writable/fences/*.php') ?: [] as $fenceFile) {
            include $fenceFile;
        }

        $isDevContext = in_array('dev', $uriSegments, true) || in_array($host, ['localhost', '192.168.1.8'], true);

        foreach ($fences as $slug => $fenceInfo) {
            if (!$fenceInfo['live'] && !$isDevContext) {
                unset($fences[$slug]);
            }
        }

        return $fences;
    }
}
