<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

/**
 * Registers a named route group from app/routes/web.php into a Router.
 *
 * The groups are kept behind this loader rather than each front controller requiring the
 * file itself so that a missing/renamed group fails loudly. Registering nothing would
 * otherwise look exactly like a working app whose every URL happens to 404.
 */
final class RouteLoader
{
    /** @var array<string, callable>|null */
    private static ?array $groups = null;

    public static function apply(string $group, Router $router): void
    {
        $groups = self::groups();

        if (!isset($groups[$group]) || !is_callable($groups[$group])) {
            throw new \RuntimeException(sprintf(
                'Route group "%s" is not defined in routes/web.php (found: %s).',
                $group,
                $groups === [] ? 'none' : implode(', ', array_keys($groups))
            ));
        }

        ($groups[$group])($router);
    }

    /**
     * @return array<string, callable>
     */
    private static function groups(): array
    {
        if (self::$groups === null) {
            // Plain require, cached here: require_once would return true rather than the
            // route map if anything else had already pulled the file in.
            $loaded = require FC_ADMIN_ROOT . '/routes/web.php';
            self::$groups = is_array($loaded) ? $loaded : [];
        }

        return self::$groups;
    }
}
