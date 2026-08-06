<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\ColorHelper;

/**
 * FC theme — editable CSS custom properties (saved to writable/theme.json).
 */
final class ThemeSettings
{
    /**
     * @return array<string, string> CSS variable => hex color
     */
    public static function defaults(): array
    {
        return [
            '--fc-brand-primary' => '#d4112f',
            '--fc-princeton-orange' => '#f67925',
            '--fc-pumpkin' => '#ff6b08',
            '--fc-jaffa' => '#f5853a',
            '--fc-fiery-orange' => '#cb6520',
            '--fc-green' => '#04a725',
            '--fc-texas-green' => '#0b9321',
            '--fc-deep-blue' => '#143052',
            '--fc-allports' => '#0a6593',
            '--fc-dark-charcoal' => '#333333',
            '--fc-dark-medium-gray' => '#a9a9a9',
            '--fc-gray' => '#e9e9e9',
            '--fc-bright-gray' => '#eeeeee',
            '--fc-white' => '#ffffff',
            '--fc-black' => '#000000',
        ];
    }

    /**
     * Built-in theme presets (full color maps).
     *
     * @return array<string, array{id:string,label:string,description:string,swatch:string,colors:array<string,string>}>
     */
    public static function presets(): array
    {
        $neutral = [
            '--fc-green' => '#04a725',
            '--fc-texas-green' => '#0b9321',
            '--fc-deep-blue' => '#143052',
            '--fc-allports' => '#0a6593',
            '--fc-dark-charcoal' => '#333333',
            '--fc-dark-medium-gray' => '#a9a9a9',
            '--fc-gray' => '#e9e9e9',
            '--fc-bright-gray' => '#eeeeee',
            '--fc-white' => '#ffffff',
            '--fc-black' => '#000000',
        ];

        return [
            'orange' => [
                'id' => 'orange',
                'label' => 'Orange (default)',
                'description' => 'Princeton orange accents with brand red primary.',
                'swatch' => '#f67925',
                'colors' => self::defaults(),
            ],
            'brand-red' => [
                'id' => 'brand-red',
                'label' => 'Brand red',
                'description' => 'Accent palette based on #D4112F — crimson buttons, tabs, and highlights.',
                'swatch' => '#d4112f',
                'colors' => array_merge($neutral, [
                    '--fc-brand-primary' => '#d4112f',
                    '--fc-princeton-orange' => '#d4112f',
                    '--fc-pumpkin' => '#ad0101',
                    '--fc-jaffa' => '#eb2424',
                    '--fc-fiery-orange' => '#8f0e26',
                ]),
            ],
        ];
    }

    public static function detectPreset(array $colors): ?string
    {
        $defaults = self::defaults();
        foreach (self::presets() as $id => $preset) {
            $match = true;
            foreach ($preset['colors'] as $var => $value) {
                $current = isset($colors[$var]) ? strtolower((string) $colors[$var]) : '';
                $expected = strtolower((string) $value);
                if ($current !== $expected) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label:string,vars:array<string,string>}>
     */
    public static function schema(): array
    {
        return [
            'brand' => [
                'label' => 'Brand & accent',
                'vars' => [
                    '--fc-brand-primary' => 'Brand primary',
                    '--fc-princeton-orange' => 'Princeton orange',
                    '--fc-pumpkin' => 'Pumpkin (hover accent)',
                    '--fc-jaffa' => 'Jaffa',
                    '--fc-fiery-orange' => 'Fiery orange (button border / shadow)',
                ],
            ],
            'semantic' => [
                'label' => 'Semantic',
                'vars' => [
                    '--fc-green' => 'Success green',
                    '--fc-texas-green' => 'Texas green',
                    '--fc-deep-blue' => 'Deep blue',
                    '--fc-allports' => 'Link blue',
                ],
            ],
            'neutral' => [
                'label' => 'Text & surfaces',
                'vars' => [
                    '--fc-dark-charcoal' => 'Dark charcoal (body text)',
                    '--fc-dark-medium-gray' => 'Medium gray (labels)',
                    '--fc-gray' => 'Gray (borders / fills)',
                    '--fc-bright-gray' => 'Bright gray (backgrounds)',
                    '--fc-white' => 'White',
                    '--fc-black' => 'Black',
                ],
            ],
        ];
    }

    public static function filePath(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'theme.json';
    }

    /**
     * @return array<string, mixed>
     */
    public static function readFile(): array
    {
        $path = self::filePath();
        if (!is_readable($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, string>
     */
    public static function get(): array
    {
        $defaults = self::defaults();
        $file = self::readFile();
        $saved = isset($file['colors']) && is_array($file['colors']) ? $file['colors'] : [];

        $merged = $defaults;
        foreach ($saved as $var => $value) {
            if (!is_string($var) || !array_key_exists($var, $defaults)) {
                continue;
            }
            $normalized = ColorHelper::normalizeHex($value);
            if ($normalized !== null) {
                $merged[$var] = $normalized;
            }
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $colors
     * @return array{ok:bool,colors?:array<string,string>,error?:string}
     */
    public static function save(array $colors): array
    {
        $defaults = self::defaults();
        $next = $defaults;

        foreach ($colors as $var => $value) {
            if (!is_string($var) || !array_key_exists($var, $defaults)) {
                continue;
            }
            $normalized = ColorHelper::normalizeHex((string) $value);
            if ($normalized === null) {
                return [
                    'ok' => false,
                    'error' => 'Invalid color for ' . $var . '.',
                ];
            }
            $next[$var] = $normalized;
        }

        $path = self::filePath();
        $dir = dirname($path);

        if (!is_writable($dir)) {
            return [
                'ok' => false,
                'error' => 'writable/ directory is not writable.',
            ];
        }

        if (file_exists($path) && !is_writable($path)) {
            return [
                'ok' => false,
                'error' => 'theme.json is not writable.',
            ];
        }

        $existing = self::readFile();
        $payload = [
            'colors' => $next,
            'updatedAt' => gmdate('c'),
        ];
        if (isset($existing['branding']) && is_array($existing['branding'])) {
            $payload['branding'] = $existing['branding'];
        }
        if (isset($existing['fenceColors']) && is_array($existing['fenceColors'])) {
            $payload['fenceColors'] = $existing['fenceColors'];
        }
        if (isset($existing['catalog']) && is_array($existing['catalog'])) {
            $payload['catalog'] = $existing['catalog'];
        }
        if (isset($existing['system']) && is_array($existing['system'])) {
            $payload['system'] = $existing['system'];
        }

        $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $written = file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            return [
                'ok' => false,
                'error' => 'Unable to write theme file.',
            ];
        }

        if (!rename($tmp, $path)) {
            @unlink($tmp);
            return [
                'ok' => false,
                'error' => 'Unable to save theme.json.',
            ];
        }

        return [
            'ok' => true,
            'colors' => $next,
        ];
    }

    /**
     * Derived CSS variables (alpha tints, etc.) from the saved palette.
     *
     * @param array<string, string> $colors
     * @return array<string, string>
     */
    public static function expandColors(array $colors): array
    {
        $expanded = $colors;
        $accent = $colors['--fc-princeton-orange'] ?? '#f67925';
        $rgb = ColorHelper::hexToRgb($accent);

        if ($rgb !== null) {
            $expanded['--fc-a-orange-3'] = ColorHelper::rgba($rgb, 0.031);
            $expanded['--fc-a-orange-20'] = ColorHelper::rgba($rgb, 0.2);
            $expanded['--fc-a-orange-22'] = ColorHelper::rgba($rgb, 0.22);
            $expanded['--fc-a-orange-25'] = ColorHelper::rgba($rgb, 0.25);
            $expanded['--fc-a-orange-35'] = ColorHelper::rgba($rgb, 0.35);
            $expanded['--fc-a-orange-38'] = ColorHelper::rgba($rgb, 0.38);
        }

        return $expanded;
    }

    public static function cssBlock(): string
    {
        $colors = self::expandColors(self::get());
        $lines = [];
        foreach ($colors as $var => $value) {
            $lines[] = '    ' . $var . ': ' . $value . ';';
        }

        if ($lines === []) {
            return '';
        }

        return "<style id=\"fc-theme-vars\">\n:root {\n" . implode("\n", $lines) . "\n}\n</style>\n";
    }

    /**
     * @return array{ok:bool,colors:array<string,string>,schema:array<string,array{label:string,vars:array<string,string>}>,defaults:array<string,string>,updatedAt?:string}
     */
    public static function apiPayload(): array
    {
        $file = self::readFile();

        return [
            'ok' => true,
            'colors' => self::get(),
            'defaults' => self::defaults(),
            'schema' => self::schema(),
            'presets' => array_values(self::presets()),
            'activePreset' => self::detectPreset(self::get()),
            'updatedAt' => isset($file['updatedAt']) ? (string) $file['updatedAt'] : null,
        ];
    }
}
