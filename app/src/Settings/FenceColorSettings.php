<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

use Fc\Admin\Helpers\ColorHelper;

/**
 * FC fence colors — planner colour swatches (saved to writable/theme.json as fenceColors).
 */
final class FenceColorSettings
{
    /**
     * Legacy colour map (source of truth for defaults until saved in theme.json).
     *
     * @return array<string, array{title:string,sub_title:string,background_color:string,text_color:string}>
     */
    public static function legacyDefaultsMap(): array
    {
        return [
            'black' => [
                'title' => 'Black',
                'sub_title' => 'Satin',
                'background_color' => '#404040',
                'text_color' => '#fff',
            ],
            'white' => [
                'title' => 'Pearl White',
                'sub_title' => 'Gloss',
                'background_color' => '#ffffff',
                'text_color' => '#000',
            ],
            'surfmist' => [
                'title' => 'Surfmist',
                'sub_title' => 'Matt',
                'background_color' => '#ebefe9',
                'text_color' => '#000',
            ],
            'dune' => [
                'title' => 'Dune',
                'sub_title' => 'Satin',
                'background_color' => '#c4c5be',
                'text_color' => '#000',
            ],
            'basalt' => [
                'title' => 'Basalt',
                'sub_title' => 'Satin',
                'background_color' => '#828989',
                'text_color' => '#fff',
            ],
            'woodland_grey' => [
                'title' => 'Woodland Grey',
                'sub_title' => 'Matt',
                'background_color' => '#868983',
                'text_color' => '#fff',
            ],
            'monument' => [
                'title' => 'Monument',
                'sub_title' => 'Matt',
                'background_color' => '#6e6e6a',
                'text_color' => '#fff',
            ],
            'kwila' => [
                'title' => 'Kwila',
                'sub_title' => 'Textured',
                'background_color' => '#785e4a',
                'text_color' => '#fff',
            ],
            'western_red_cedar' => [
                'title' => 'Western Red Cedar',
                'sub_title' => 'Textured',
                'background_color' => '#9a6b50',
                'text_color' => '#fff',
            ],
            'pearl_white_gloss' => [
                'title' => 'Pearl White',
                'sub_title' => 'Gloss',
                'background_color' => '#ffffff',
                'text_color' => '#000',
            ],
            'surfmist_matt' => [
                'title' => 'Surfmist',
                'sub_title' => 'Matt',
                'background_color' => '#ebefe9',
                'text_color' => '#000',
            ],
            'dune_satin' => [
                'title' => 'Dune',
                'sub_title' => 'Satin',
                'background_color' => '#c4c5be',
                'text_color' => '#000',
            ],
            'basalt_satin' => [
                'title' => 'Basalt',
                'sub_title' => 'Satin',
                'background_color' => '#828989',
                'text_color' => '#fff',
            ],
            'woodland_grey_matt' => [
                'title' => 'Woodland Grey',
                'sub_title' => 'Matt',
                'background_color' => '#868983',
                'text_color' => '#fff',
            ],
            'monument_matt' => [
                'title' => 'Monument',
                'sub_title' => 'Matt',
                'background_color' => '#6e6e6a',
                'text_color' => '#fff',
            ],
            'black_satin' => [
                'title' => 'Black',
                'sub_title' => 'Satin',
                'background_color' => '#404040',
                'text_color' => '#fff',
            ],
            'kwila_textured' => [
                'title' => 'Kwila',
                'sub_title' => 'Textured',
                'background_color' => '#785e4a',
                'text_color' => '#fff',
            ],
            'matt_black' => [
                'title' => 'Black',
                'sub_title' => 'Matt',
                'background_color' => '#000',
                'text_color' => '#fff',
            ],
            'polished_stainless_steel' => [
                'title' => 'Polished',
                'sub_title' => 'Stainless Steel',
                'background_color' => 'linear-gradient(90deg, rgba(168,168,168,1) 0%, rgba(251,251,251,1) 36%, rgba(255,255,255,1) 60%, rgba(168,168,168,1) 100%);',
                'text_color' => '#000',
            ],
            'satin_stainless_steel' => [
                'title' => 'Satin',
                'sub_title' => 'Stainless Steel',
                'background_color' => 'url(https://www.rigidized.com/wp-content/uploads/4Satin-01.jpg);',
                'text_color' => '#000',
            ],
        ];
    }

    /**
     * @return list<array{slug:string,label:string,subLabel:string,color:string,image:string}>
     */
    public static function defaults(): array
    {
        $items = [];
        foreach (self::legacyDefaultsMap() as $slug => $legacy) {
            $items[] = self::legacyToItem($slug, $legacy);
        }

        return $items;
    }

    /**
     * @param array{title:string,sub_title:string,background_color:string,text_color:string} $legacy
     * @return array{slug:string,label:string,subLabel:string,color:string,image:string}
     */
    public static function legacyToItem(string $slug, array $legacy): array
    {
        $bg = trim($legacy['background_color']);
        $color = '';
        $image = '';

        if (preg_match('#^url\((.+)\)\s*;?\s*$#i', $bg, $matches)) {
            $image = trim($matches[1], " \t\n\r\0\x0B'\"");
        } elseif (stripos($bg, 'gradient') !== false) {
            $color = $bg;
        } else {
            $color = $bg;
        }

        return [
            'slug' => $slug,
            'label' => trim($legacy['title']),
            'subLabel' => trim($legacy['sub_title']),
            'color' => $color,
            'image' => $image,
        ];
    }

    /**
     * @return list<array{slug:string,label:string,subLabel:string,color:string,image:string}>
     */
    public static function get(): array
    {
        $defaults = self::defaults();
        $saved = ThemeSettings::section('fenceColors');

        if ($saved === []) {
            return $defaults;
        }

        $merged = [];
        foreach ($saved as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = self::normalizeRow($row);
            if ($normalized !== null) {
                $merged[] = $normalized;
            }
        }

        return $merged !== [] ? $merged : $defaults;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{slug:string,label:string,subLabel:string,color:string,image:string}|null
     */
    public static function normalizeRow(array $row): ?array
    {
        $slug = isset($row['slug']) ? self::normalizeSlug((string) $row['slug']) : null;
        if ($slug === null || $slug === '') {
            return null;
        }

        $label = trim((string) ($row['label'] ?? ''));
        $subLabel = trim((string) ($row['subLabel'] ?? $row['sub_label'] ?? ''));

        if ($subLabel === '' && $label !== '' && preg_match('/\s·\s/u', $label)) {
            $parsed = self::parseLabel($label);
            $label = $parsed['title'];
            $subLabel = $parsed['sub_title'];
        }

        if ($label === '') {
            return null;
        }

        if (mb_strlen($label) > 120) {
            $label = mb_substr($label, 0, 120);
        }
        if ($subLabel !== '' && mb_strlen($subLabel) > 120) {
            $subLabel = mb_substr($subLabel, 0, 120);
        }

        $color = trim((string) ($row['color'] ?? ''));
        $image = trim((string) ($row['image'] ?? ''));

        if ($color !== '' && mb_strlen($color) > 500) {
            $color = mb_substr($color, 0, 500);
        }
        if ($image !== '' && mb_strlen($image) > 500) {
            $image = mb_substr($image, 0, 500);
        }

        if ($color === '' && $image === '') {
            return null;
        }

        return [
            'slug' => $slug,
            'label' => $label,
            'subLabel' => $subLabel,
            'color' => $color,
            'image' => $image,
        ];
    }

    public static function normalizeSlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }
        if (!preg_match('/^[a-z0-9_]+$/', $slug)) {
            return null;
        }
        if (mb_strlen($slug) > 80) {
            $slug = mb_substr($slug, 0, 80);
        }

        return $slug;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{ok:bool,fenceColors?:list<array{slug:string,label:string,subLabel:string,color:string,image:string}>,error?:string}
     */
    public static function save(array $rows): array
    {
        $next = [];
        $seen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = self::normalizeRow($row);
            if ($normalized === null) {
                continue;
            }
            if (isset($seen[$normalized['slug']])) {
                return [
                    'ok' => false,
                    'error' => 'Duplicate slug: ' . $normalized['slug'],
                ];
            }
            $seen[$normalized['slug']] = true;
            $next[] = $normalized;
        }

        if ($next === []) {
            return [
                'ok' => false,
                'error' => 'Add at least one fence color with slug, label, and color or image.',
            ];
        }

        $requiredSlugs = array_keys(self::legacyDefaultsMap());
        $savedSlugs = array_column($next, 'slug');
        foreach ($requiredSlugs as $requiredSlug) {
            if (!in_array($requiredSlug, $savedSlugs, true)) {
                return [
                    'ok' => false,
                    'error' => 'Cannot remove required fence color: ' . $requiredSlug,
                ];
            }
        }

        $result = ThemeSettings::writeSection('fenceColors', $next);
        if (!$result['ok']) {
            return $result;
        }

        return [
            'ok' => true,
            'fenceColors' => $next,
        ];
    }

    /**
     * @return array<string, array{title:string,sub_title:string,background_color:string,text_color:string}>
     */
    public static function legacyMap(): array
    {
        $out = [];
        foreach (self::get() as $item) {
            $slug = $item['slug'];
            $bg = self::background($item);
            $out[$slug] = [
                'title' => trim($item['label']),
                'sub_title' => trim($item['subLabel'] ?? ''),
                'background_color' => $bg,
                'text_color' => self::textColor($item, $bg),
            ];
        }

        return $out;
    }

    /**
     * @return array{title:string,sub_title:string}
     */
    public static function parseLabel(string $label): array
    {
        $parts = preg_split('/\s·\s/u', $label, 2);

        return [
            'title' => trim($parts[0] ?? $label),
            'sub_title' => trim($parts[1] ?? ''),
        ];
    }

    /**
     * @param array{slug:string,label:string,subLabel:string,color:string,image:string} $item
     */
    public static function background(array $item): string
    {
        $image = trim($item['image']);
        if ($image !== '') {
            if (preg_match('#^url\(#i', $image)) {
                return $image;
            }

            return 'url(' . $image . ')';
        }

        $color = trim($item['color']);

        return $color !== '' ? $color : '#cccccc';
    }

    /**
     * @param array{slug:string,label:string,subLabel:string,color:string,image:string} $item
     */
    public static function textColor(array $item, string $background): string
    {
        if (trim($item['image']) !== '') {
            return '#000';
        }

        if (stripos($background, 'gradient') !== false || preg_match('#^url\(#i', $background)) {
            return '#000';
        }

        $hex = ColorHelper::normalizeHex($background);
        if ($hex === null) {
            return '#000';
        }

        $rgb = ColorHelper::hexToRgb($hex);
        if ($rgb === null) {
            return '#000';
        }

        $luminance = (0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']) / 255;

        return $luminance > 0.55 ? '#000' : '#fff';
    }

    /**
     * @return array{ok:bool,fenceColors:list<array{slug:string,label:string,subLabel:string,color:string,image:string}>,defaults:list<array{slug:string,label:string,subLabel:string,color:string,image:string}>,updatedAt?:string|null}
     */
    public static function apiPayload(): array
    {
        return [
            'ok' => true,
            'fenceColors' => self::get(),
            'defaults' => self::defaults(),
            'updatedAt' => ThemeSettings::updatedAt(),
        ];
    }
}
