<?php
/**
 * FC branding — app name, tagline, version (saved to data/theme.json).
 */

declare(strict_types=1);

require_once __DIR__ . '/theme.php';

/**
 * @return array<string, string>
 */
function fc_branding_defaults(): array
{
    return [
        'logo' => '',
        'appName' => 'Fencing Calculator',
        'tagline' => 'Calculate your fence cost and the materials needed.',
        'version' => 'v10.0.0 beta',
    ];
}

/**
 * @return array<string, array{label:string,type:string,placeholder?:string,help?:string}>
 */
function fc_branding_schema(): array
{
    return [
        'logo' => [
            'label' => 'Logo',
            'type' => 'image',
            'placeholder' => 'assets/uploads/logo.png',
            'help' => 'Shown on the admin login page and sidebar header. SVG, PNG, JPG, WebP, or GIF recommended.',
        ],
        'appName' => [
            'label' => 'App name',
            'type' => 'text',
            'placeholder' => 'Fencing Calculator',
            'help' => 'Shown in the page title, header, footer, and admin sidebar.',
        ],
        'tagline' => [
            'label' => 'Tagline',
            'type' => 'text',
            'placeholder' => 'Calculate your fence cost and the materials needed.',
            'help' => 'Short description under the title on the planner header.',
        ],
        'version' => [
            'label' => 'Version label',
            'type' => 'text',
            'placeholder' => 'v10.0.0 beta',
            'help' => 'Shown next to the app name in the planner footer.',
        ],
    ];
}

/**
 * @return array<string, string>
 */
function fc_branding_get(): array
{
    $defaults = fc_branding_defaults();
    $file = fc_theme_read_file();
    $saved = isset($file['branding']) && is_array($file['branding']) ? $file['branding'] : [];

    $merged = $defaults;
    foreach ($saved as $key => $value) {
        if (!array_key_exists($key, $defaults) || !is_scalar($value)) {
            continue;
        }
        $normalized = fc_branding_normalize_field($key, (string) $value);
        if ($normalized !== null) {
            $merged[$key] = $normalized;
        }
    }

    return $merged;
}

function fc_branding_normalize_field(string $key, string $value): ?string
{
    $value = trim($value);

    if ($key === 'logo') {
        if ($value === '') {
            return '';
        }

        if (mb_strlen($value) > 500) {
            $value = mb_substr($value, 0, 500);
        }

        if (!fc_branding_is_valid_logo_path($value)) {
            return null;
        }

        return str_replace('\\', '/', $value);
    }

    if ($value === '') {
        return null;
    }

    $limits = [
        'appName' => 120,
        'tagline' => 500,
        'version' => 50,
    ];

    $max = $limits[$key] ?? 500;
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }

    return $value;
}

function fc_branding_is_valid_logo_path(string $value): bool
{
    if (preg_match('/^https?:\/\//i', $value) || preg_match('/^data:image\//i', $value)) {
        return true;
    }

    if (str_contains($value, '..') || str_contains($value, "\0")) {
        return false;
    }

    $path = ltrim(str_replace('\\', '/', $value), '/');

    return (bool) preg_match('#^(?:assets/uploads|assets/img)/[^/]+$#', $path);
}

/**
 * @param array<string, string>|null $branding
 */
function fc_branding_logo_url(string $appBase, ?array $branding = null): string
{
    if ($branding === null) {
        $branding = fc_branding_get();
    }

    $path = trim((string) ($branding['logo'] ?? ''));
    if ($path === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path) || preg_match('/^data:/i', $path) || str_starts_with($path, '//')) {
        return $path;
    }

    $base = rtrim(str_replace('\\', '/', $appBase), '/');
    $rel = ltrim(str_replace('\\', '/', $path), '/');

    return $base !== '' ? $base . '/' . $rel : $rel;
}

/**
 * @param array{img_class?:string,fallback_icon?:string} $options
 * @param array<string, string>|null $branding
 */
function fc_branding_logo_markup(string $appBase, ?array $branding = null, array $options = []): string
{
    $imgClass = (string) ($options['img_class'] ?? '');
    $fallbackIcon = (string) ($options['fallback_icon'] ?? 'fa-solid fa-border-all');

    $logoUrl = fc_branding_logo_url($appBase, $branding);
    if ($logoUrl !== '') {
        return '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="" class="' . htmlspecialchars($imgClass, ENT_QUOTES, 'UTF-8') . '" decoding="async">';
    }

    return '<i class="' . htmlspecialchars($fallbackIcon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
}

/**
 * @param array<string, mixed> $branding
 * @return array{ok:bool,branding?:array<string,string>,error?:string}
 */
function fc_branding_save(array $branding): array
{
    $defaults = fc_branding_defaults();
    $next = $defaults;

    foreach ($branding as $key => $value) {
        if (!is_string($key) || !array_key_exists($key, $defaults)) {
            continue;
        }
        $normalized = fc_branding_normalize_field($key, (string) $value);
        if ($normalized === null) {
            return [
                'ok' => false,
                'error' => 'Invalid value for ' . $key . '.',
            ];
        }
        $next[$key] = $normalized;
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
        'branding' => $next,
        'updatedAt' => gmdate('c'),
    ];
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
        'branding' => $next,
    ];
}

/**
 * @return array{ok:bool,branding:array<string,string>,defaults:array<string,string>,schema:array<string,array{label:string,type:string,placeholder?:string,help?:string}>,updatedAt?:string}
 */
function fc_branding_api_payload(): array
{
    $file = fc_theme_read_file();

    return [
        'ok' => true,
        'branding' => fc_branding_get(),
        'defaults' => fc_branding_defaults(),
        'schema' => fc_branding_schema(),
        'updatedAt' => isset($file['updatedAt']) ? (string) $file['updatedAt'] : null,
    ];
}
