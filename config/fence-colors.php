<?php
/**
 * FC fence colors — planner colour swatches (saved to data/theme.json as fenceColors).
 */

declare(strict_types=1);

require_once __DIR__ . '/theme.php';

/**
 * Legacy colour map (source of truth for defaults until saved in theme.json).
 *
 * @return array<string, array{title:string,sub_title:string,background_color:string,text_color:string}>
 */
function fc_fence_colors_legacy_defaults_map(): array
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
function fc_fence_colors_defaults(): array
{
    $items = [];
    foreach (fc_fence_colors_legacy_defaults_map() as $slug => $legacy) {
        $items[] = fc_fence_colors_legacy_to_item($slug, $legacy);
    }

    return $items;
}

/**
 * @param array{title:string,sub_title:string,background_color:string,text_color:string} $legacy
 * @return array{slug:string,label:string,subLabel:string,color:string,image:string}
 */
function fc_fence_colors_legacy_to_item(string $slug, array $legacy): array
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
function fc_fence_colors_get(): array
{
    $defaults = fc_fence_colors_defaults();
    $file = fc_theme_read_file();
    $saved = isset($file['fenceColors']) && is_array($file['fenceColors']) ? $file['fenceColors'] : [];

    if ($saved === []) {
        return $defaults;
    }

    $merged = [];
    foreach ($saved as $row) {
        if (!is_array($row)) {
            continue;
        }
        $normalized = fc_fence_colors_normalize_row($row);
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
function fc_fence_colors_normalize_row(array $row): ?array
{
    $slug = isset($row['slug']) ? fc_fence_colors_normalize_slug((string) $row['slug']) : null;
    if ($slug === null || $slug === '') {
        return null;
    }

    $label = trim((string) ($row['label'] ?? ''));
    $subLabel = trim((string) ($row['subLabel'] ?? $row['sub_label'] ?? ''));

    if ($subLabel === '' && $label !== '' && preg_match('/\s·\s/u', $label)) {
        $parsed = fc_fence_color_parse_label($label);
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

function fc_fence_colors_normalize_slug(string $slug): ?string
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
function fc_fence_colors_save(array $rows): array
{
    $next = [];
    $seen = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $normalized = fc_fence_colors_normalize_row($row);
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

    $requiredSlugs = array_keys(fc_fence_colors_legacy_defaults_map());
    $savedSlugs = array_column($next, 'slug');
    foreach ($requiredSlugs as $requiredSlug) {
        if (!in_array($requiredSlug, $savedSlugs, true)) {
            return [
                'ok' => false,
                'error' => 'Cannot remove required fence color: ' . $requiredSlug,
            ];
        }
    }

    $path = fc_theme_file_path();
    $dir = dirname($path);

    if (!is_writable($dir)) {
        return [
            'ok' => false,
            'error' => 'data/ directory is not writable.',
        ];
    }

    if (file_exists($path) && !is_writable($path)) {
        return [
            'ok' => false,
            'error' => 'theme.json is not writable.',
        ];
    }

    $existing = fc_theme_read_file();
    $payload = [
        'colors' => isset($existing['colors']) && is_array($existing['colors']) ? $existing['colors'] : fc_theme_get(),
        'branding' => isset($existing['branding']) && is_array($existing['branding']) ? $existing['branding'] : null,
        'fenceColors' => $next,
        'updatedAt' => gmdate('c'),
    ];
    if ($payload['branding'] === null) {
        unset($payload['branding']);
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
            'error' => 'Unable to write settings file.',
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
        'fenceColors' => $next,
    ];
}

/**
 * @return array<string, array{title:string,sub_title:string,background_color:string,text_color:string}>
 */
function fc_fence_colors_legacy_map(): array
{
    $out = [];
    foreach (fc_fence_colors_get() as $item) {
        $slug = $item['slug'];
        $bg = fc_fence_color_background($item);
        $out[$slug] = [
            'title' => trim($item['label']),
            'sub_title' => trim($item['subLabel'] ?? ''),
            'background_color' => $bg,
            'text_color' => fc_fence_color_text_color($item, $bg),
        ];
    }

    return $out;
}

/**
 * @return array{title:string,sub_title:string}
 */
function fc_fence_color_parse_label(string $label): array
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
function fc_fence_color_background(array $item): string
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
function fc_fence_color_text_color(array $item, string $background): string
{
    if (trim($item['image']) !== '') {
        return '#000';
    }

    if (stripos($background, 'gradient') !== false || preg_match('#^url\(#i', $background)) {
        return '#000';
    }

    $hex = fc_theme_normalize_color($background);
    if ($hex === null) {
        return '#000';
    }

    $rgb = fc_theme_hex_to_rgb($hex);
    if ($rgb === null) {
        return '#000';
    }

    $luminance = (0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']) / 255;

    return $luminance > 0.55 ? '#000' : '#fff';
}

/**
 * @return array{ok:bool,fenceColors:list<array{slug:string,label:string,subLabel:string,color:string,image:string}>,defaults:list<array{slug:string,label:string,subLabel:string,color:string,image:string}>,updatedAt?:string|null}
 */
function fc_fence_colors_api_payload(): array
{
    $file = fc_theme_read_file();

    return [
        'ok' => true,
        'fenceColors' => fc_fence_colors_get(),
        'defaults' => fc_fence_colors_defaults(),
        'updatedAt' => isset($file['updatedAt']) ? (string) $file['updatedAt'] : null,
    ];
}
