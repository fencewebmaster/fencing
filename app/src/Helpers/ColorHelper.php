<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic hex/RGB color utilities.
 */
final class ColorHelper
{
    /**
     * @return array{r:int,g:int,b:int}|null
     */
    public static function hexToRgb(string $hex): ?array
    {
        $hex = self::normalizeHex($hex);
        if ($hex === null) {
            return null;
        }

        return [
            'r' => hexdec(substr($hex, 1, 2)),
            'g' => hexdec(substr($hex, 3, 2)),
            'b' => hexdec(substr($hex, 5, 2)),
        ];
    }

    /**
     * @param array{r:int,g:int,b:int} $rgb
     */
    public static function rgba(array $rgb, float $alpha): string
    {
        $alphaStr = rtrim(rtrim(sprintf('%.3f', $alpha), '0'), '.');

        return sprintf(
            'rgba(%d, %d, %d, %s)',
            $rgb['r'],
            $rgb['g'],
            $rgb['b'],
            $alphaStr
        );
    }

    /**
     * Requires a leading '#'; expands 3-digit shorthand (#abc -> #aabbcc).
     */
    public static function normalizeHex(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $value) !== 1) {
            return null;
        }

        if (strlen($value) === 4) {
            $r = $value[1];
            $g = $value[2];
            $b = $value[3];
            $value = '#' . $r . $r . $g . $g . $b . $b;
        }

        return strtolower($value);
    }

    /**
     * Best-effort hex color for a human color name (product/fence color swatches).
     */
    public static function guessNamedColorHex(string $name): string
    {
        $map = [
            'black' => '#111827',
            'white' => '#f8fafc',
            'grey' => '#9ca3af',
            'gray' => '#9ca3af',
            'silver' => '#c0c0c0',
            'red' => '#dc2626',
            'blue' => '#2563eb',
            'green' => '#16a34a',
            'yellow' => '#eab308',
            'orange' => '#f67925',
            'brown' => '#92400e',
            'beige' => '#d6c6a8',
            'cream' => '#f5f0e1',
            'bronze' => '#cd7f32',
            'gold' => '#d4af37',
            'charcoal' => '#36454f',
            'anthracite' => '#383e42',
            'monument' => '#323433',
            'woodland grey' => '#59595b',
            'surfmist' => '#e4e2d8',
            'paperbark' => '#c5b7a0',
            'dusk' => '#5d5c5d',
            'shale grey' => '#939492',
            'basalt' => '#6d6d6d',
            'ironstone' => '#44403c',
            'deep ocean' => '#1e3a5f',
            'manor red' => '#6b1f1f',
            'cottage green' => '#3d5c3a',
            'pale eucalypt' => '#7a8a6e',
            'jaspe' => '#8b6f4e',
            'jasper' => '#8b6f4e',
            'wallaby' => '#7a7067',
            'terrain' => '#6b5a45',
            'gully' => '#6e6a61',
            'woodland' => '#59595b',
        ];

        $key = strtolower(trim($name));
        if (isset($map[$key])) {
            return $map[$key];
        }
        foreach ($map as $needle => $hex) {
            if (str_contains($key, $needle)) {
                return $hex;
            }
        }

        return '#cbd5e1';
    }
}
