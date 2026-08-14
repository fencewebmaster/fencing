<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class Autoloader
{
    private const PREFIX = 'Fc\\Admin\\';

    private const BASE_DIR = __DIR__ . '/..';

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        if (strncmp($class, self::PREFIX, strlen(self::PREFIX)) !== 0) {
            return;
        }

        $relative = substr($class, strlen(self::PREFIX));
        $file     = self::BASE_DIR . '/' . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($file)) {
            require $file;
        }
    }
}