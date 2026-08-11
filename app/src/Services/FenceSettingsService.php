<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Loads the fence catalog (`writable/settings.php`) and the legacy `fc_color()` /
 * `fc_state()` / `fc_timeframe()` / `fc_extra_needed()` view helpers
 * (`app/src/Helpers/fc_functions.php`).
 *
 * The frontend entry scripts used to `include 'writable/settings.php'` at global
 * scope, which is what made `$fences` a true global. Controllers run inside a
 * method, so the include has to publish `$fences` into `$GLOBALS` explicitly —
 * CartBuilderService and FenceCatalogService both read it via `global $fences`.
 */
final class FenceSettingsService
{
    private static bool $loaded = false;

    /**
     * Load the settings file (once) and return the fence catalog.
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
     * Ensure `writable/settings.php` has been evaluated and `$GLOBALS['fences']` is set.
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

        // Plain require (not require_once): the file is idempotent — every helper is
        // wrapped in function_exists() and $fences is rebuilt from writable/fences/*.php.
        // require_once would silently no-op if some other code path already pulled it in
        // from *function* scope, leaving $GLOBALS['fences'] unset and the catalog empty.
        $fences = [];
        require FC_ROOT . '/writable/settings.php';

        $GLOBALS['fences'] = is_array($fences) ? $fences : [];
    }
}
