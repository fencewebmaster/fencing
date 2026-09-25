<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

/**
 * FC Minify settings — whether the minified CSS and JS copies are served, per group (saved to
 * writable/theme.json as minify). Each default is what the group did before the switches existed:
 * the frontend already served a fresh public/assets/min/ copy (on), the admin never did (off).
 * The frontend keys predate the admin ones, so they keep their plain names 'css' and 'js'.
 */
final class MinifySettings
{
    /** Cached per request: AssetHelper::minified() asks for every asset tag on a page. */
    private static ?array $current = null;

    /**
     * @return array{css:bool,js:bool,adminCss:bool,adminJs:bool}
     */
    public static function defaults(): array
    {
        return ['css' => true, 'js' => true, 'adminCss' => false, 'adminJs' => false];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{css:bool,js:bool,adminCss:bool,adminJs:bool}
     */
    public static function normalize(array $input): array
    {
        $defaults = self::defaults();

        return [
            'css' => self::normalizeBool($input['css'] ?? null, $defaults['css']),
            'js' => self::normalizeBool($input['js'] ?? null, $defaults['js']),
            'adminCss' => self::normalizeBool($input['adminCss'] ?? null, $defaults['adminCss']),
            'adminJs' => self::normalizeBool($input['adminJs'] ?? null, $defaults['adminJs']),
        ];
    }

    /**
     * @return array{css:bool,js:bool,adminCss:bool,adminJs:bool}
     */
    public static function get(): array
    {
        if (self::$current === null) {
            self::$current = self::normalize(ThemeSettings::section('minify'));
        }

        return self::$current;
    }

    /** Whether one group's minified copies are served ('css', 'js', 'adminCss' or 'adminJs'). */
    public static function enabled(string $group): bool
    {
        return !empty(self::get()[$group]);
    }

    /**
     * @param array<string, mixed> $minify
     * @return array{ok:bool,minify?:array{css:bool,js:bool,adminCss:bool,adminJs:bool},error?:string}
     */
    public static function save(array $minify): array
    {
        $next = self::normalize($minify);

        $result = ThemeSettings::writeSection('minify', $next);
        if (!$result['ok']) {
            return $result;
        }
        self::$current = $next;

        return ['ok' => true, 'minify' => $next];
    }

    /**
     * @return array{ok:bool,minify:array{css:bool,js:bool,adminCss:bool,adminJs:bool},defaults:array{css:bool,js:bool,adminCss:bool,adminJs:bool}}
     */
    public static function apiPayload(): array
    {
        return [
            'ok' => true,
            'minify' => self::get(),
            'defaults' => self::defaults(),
        ];
    }

    private static function normalizeBool(mixed $value, bool $default): bool
    {
        $value = $value ?? $default;
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
