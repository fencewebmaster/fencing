<?php
/**
 * FC Admin — settings page data and view helpers.
 */

declare(strict_types=1);

require_once __DIR__ . '/theme.php';
require_once __DIR__ . '/branding.php';
require_once __DIR__ . '/fence-colors.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/system.php';
require_once __DIR__ . '/integrations.php';

/**
 * @return array<string, mixed>
 */
function fc_settings_admin_page_data(string $adminBase, string $appBase, string $initialTab): array
{
    return fc_settings_admin_view_data($adminBase, $appBase, $initialTab);
}

/**
 * View model for settings.php — prepared in the controller layer.
 *
 * @return array<string, mixed>
 */
function fc_settings_admin_view_data(string $adminBase, string $appBase, string $initialTab): array
{
    $theme = fc_theme_api_payload();
    $brandingPayload = fc_branding_api_payload();
    $fencePayload = fc_fence_colors_api_payload();
    // Avoid booting WP for every settings tab; load WC options only on Catalog.
    $catalogPayload = fc_catalog_api_payload($initialTab === 'catalog');
    $systemPayload = fc_system_api_payload();
    $integrationsPayload = fc_integrations_api_payload();

    $activePreset = (string) ($theme['activePreset'] ?? fc_theme_detect_preset($theme['colors'] ?? []) ?? '');
    $colors = is_array($theme['colors'] ?? null) ? $theme['colors'] : [];
    $schema = is_array($theme['schema'] ?? null) ? $theme['schema'] : [];
    $presetsRaw = is_array($theme['presets'] ?? null) ? array_values($theme['presets']) : [];
    $branding = is_array($brandingPayload['branding'] ?? null) ? $brandingPayload['branding'] : [];
    $brandingSchema = is_array($brandingPayload['schema'] ?? null) ? $brandingPayload['schema'] : [];
    $fenceColors = is_array($fencePayload['fenceColors'] ?? null) ? $fencePayload['fenceColors'] : [];
    $fenceDefaults = is_array($fencePayload['defaults'] ?? null) ? $fencePayload['defaults'] : [];
    $catalog = is_array($catalogPayload['catalog'] ?? null) ? $catalogPayload['catalog'] : fc_catalog_defaults();
    $system = is_array($systemPayload['system'] ?? null) ? $systemPayload['system'] : fc_system_defaults();

    $bootstrap = [
        'activeTab'         => $initialTab,
        'colors'            => $colors,
        'defaults'          => $theme['defaults'] ?? [],
        'schema'            => $schema,
        'presets'           => $presetsRaw,
        'activePreset'      => $activePreset !== '' ? $activePreset : null,
        'selectedPreset'    => $activePreset !== '' ? $activePreset : null,
        'branding'          => $branding,
        'brandingDefaults'  => $brandingPayload['defaults'] ?? [],
        'brandingSchema'    => $brandingSchema,
        'fenceColors'       => $fenceColors,
        'fenceColorsDefaults' => $fenceDefaults,
        'catalog'           => $catalog,
        'catalogDefaults'   => $catalogPayload['defaults'] ?? fc_catalog_defaults(),
        'catalogOrderbyChoices' => $catalogPayload['orderbyChoices'] ?? fc_catalog_orderby_choices(),
        'catalogResultsPerPageChoices' => $catalogPayload['resultsPerPageChoices'] ?? fc_catalog_results_per_page_choices(),
        'catalogCategories' => $catalogPayload['categories'] ?? [],
        'catalogAttributes' => $catalogPayload['attributes'] ?? [],
        'catalogOptionsError' => (string) ($catalogPayload['optionsError'] ?? ''),
        'system'            => $system,
        'systemDefaults'    => $systemPayload['defaults'] ?? fc_system_defaults(),
        'systemDatePeriodChoices' => $systemPayload['datePeriodChoices'] ?? fc_system_date_period_choices(),
        'systemDateFieldChoices' => $systemPayload['dateFieldChoices'] ?? fc_system_date_field_choices(),
        'systemDateFormatChoices' => $systemPayload['dateFormatChoices'] ?? fc_system_date_format_choices(),
        'integrations'      => $integrationsPayload['integrations'] ?? [],
        'integrationsInitial' => $integrationsPayload['integrations'] ?? [],
        'integrationsRevision' => (string) ($integrationsPayload['revision'] ?? ''),
        'csrf'              => function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '',
    ];

    $presets = [];
    foreach ($presetsRaw as $preset) {
        if (!is_array($preset)) {
            continue;
        }
        $presetId = (string) ($preset['id'] ?? '');
        $presetColors = is_array($preset['colors'] ?? null) ? $preset['colors'] : [];
        $accent = fc_settings_admin_preset_accent($preset);
        $presets[] = [
            'id'            => $presetId,
            'label'         => (string) ($preset['label'] ?? $presetId),
            'description'   => (string) ($preset['description'] ?? ''),
            'accent'        => $accent,
            'brand_primary' => (string) ($presetColors['--fc-brand-primary'] ?? '#d4112f'),
            'is_active'     => $activePreset === $presetId,
            'is_selected'   => $activePreset === $presetId,
            'badge_styles'  => fc_settings_admin_preset_badge_styles($accent),
            'card_class'    => $activePreset === $presetId
                ? 'fc-theme-preset--selected'
                : 'border-slate-200 bg-slate-50/50 hover:border-slate-300 hover:bg-white',
        ];
    }

    $themeGroups = [];
    foreach ($schema as $groupKey => $group) {
        if (!is_array($group)) {
            continue;
        }
        $vars = is_array($group['vars'] ?? null) ? $group['vars'] : [];
        $fields = [];
        foreach ($vars as $varName => $label) {
            $varName = (string) $varName;
            $value = (string) ($colors[$varName] ?? '#000000');
            $fieldId = fc_settings_admin_theme_field_id($varName);
            $fields[] = [
                'var'           => $varName,
                'label'         => (string) $label,
                'value'         => $value,
                'field_id'      => $fieldId,
                'picker_value'  => fc_settings_admin_fence_picker_value($value),
            ];
        }
        $themeGroups[] = [
            'key'    => (string) $groupKey,
            'label'  => (string) ($group['label'] ?? $groupKey),
            'fields' => $fields,
        ];
    }

    $brandingFields = [];
    foreach (fc_settings_admin_branding_field_order() as $key) {
        if (!isset($brandingSchema[$key]) || !is_array($brandingSchema[$key])) {
            continue;
        }
        $field = $brandingSchema[$key];
        $brandingFields[] = [
            'key'         => $key,
            'type'        => (string) ($field['type'] ?? 'text'),
            'field_id'    => 'fc-branding-' . $key,
            'label'       => (string) ($field['label'] ?? $key),
            'value'       => (string) ($branding[$key] ?? ''),
            'placeholder' => (string) ($field['placeholder'] ?? ''),
            'title'       => (string) ($field['help'] ?? ''),
            'help'        => (string) ($field['help'] ?? ''),
            'logo_url'    => $key === 'logo' ? fc_branding_logo_url($appBase, $branding) : '',
        ];
    }

    $fenceRows = [];
    foreach ($fenceColors as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $slug = (string) ($row['slug'] ?? '');
        $isOriginal = fc_settings_admin_is_original_fence_slug($slug, $fenceDefaults);
        $fenceRows[] = [
            'index'        => (int) $index,
            'slug'         => $slug,
            'label'        => (string) ($row['label'] ?? ''),
            'sub_label'    => (string) ($row['subLabel'] ?? ''),
            'color'        => (string) ($row['color'] ?? ''),
            'image'        => (string) ($row['image'] ?? ''),
            'is_original'  => $isOriginal,
            'row_class'    => $isOriginal ? ' fc-fs-kv-row--locked' : '',
            'bg'           => fc_settings_admin_fence_row_background($row),
            'preview_url'  => fc_settings_admin_fence_preview_url($row, $appBase),
            'picker_value' => fc_settings_admin_fence_picker_value((string) ($row['color'] ?? '')),
        ];
    }

    $showPreview = fc_settings_admin_show_preview($initialTab);

    return [
        'initial_tab'      => $initialTab,
        'active_tab'       => $initialTab,
        'admin_base'       => $adminBase,
        'app_base'         => $appBase,
        'show_preview'     => $showPreview,
        'layout_class'     => $showPreview ? 'lg:grid-cols-2' : '',
        'preview_hidden'   => $showPreview ? '' : 'hidden ',
        'preview_mode'     => $initialTab === 'theme' ? 'theme' : ($initialTab === 'branding' ? 'branding' : ''),
        'bootstrap_json'   => json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'tabs'             => [
            'theme'        => 'Theme',
            'branding'     => 'Branding',
            'fence-colors' => 'Fence colors',
            'catalog'      => 'Catalog',
            'system'       => 'System',
            'integration'  => 'Integration',
            'dev-mode'     => 'Dev Mode',
        ],
        'btn_secondary'    => 'btn btn-sm btn-dark fw-semibold',
        'btn_primary'      => 'btn btn-sm btn-orange fw-semibold',
        'presets'          => $presets,
        'theme_groups'     => $themeGroups,
        'branding_fields'  => $brandingFields,
        'branding_preview' => [
            'app_name' => (string) ($branding['appName'] ?? 'Fencing Calculator'),
            'tagline'  => (string) ($branding['tagline'] ?? ''),
            'version'  => (string) ($branding['version'] ?? ''),
            'logo_url' => fc_branding_logo_url($appBase, $branding),
        ],
        'fence_sort_columns' => fc_settings_admin_fence_sort_columns(),
        'fence_rows'       => $fenceRows,
        'has_fence_rows'   => $fenceRows !== [],
        'catalog'          => $catalog,
        'catalog_orderby_choices' => $catalogPayload['orderbyChoices'] ?? fc_catalog_orderby_choices(),
        'catalog_results_per_page_choices' => $catalogPayload['resultsPerPageChoices'] ?? fc_catalog_results_per_page_choices(),
        'system'           => $system,
        'system_date_period_choices' => $systemPayload['datePeriodChoices'] ?? fc_system_date_period_choices(),
        'system_date_field_choices' => $systemPayload['dateFieldChoices'] ?? fc_system_date_field_choices(),
        'system_date_format_choices' => $systemPayload['dateFormatChoices'] ?? fc_system_date_format_choices(),
        'integrations'     => $integrationsPayload['integrations'] ?? [],
        'panel_class'      => [
            'theme'        => $initialTab === 'theme' ? '' : 'hidden ',
            'branding'     => $initialTab === 'branding' ? '' : 'hidden ',
            'fence_colors' => $initialTab === 'fence-colors' ? '' : 'hidden ',
            'catalog'      => $initialTab === 'catalog' ? '' : 'hidden ',
            'system'       => $initialTab === 'system' ? '' : 'hidden ',
            'integration'  => $initialTab === 'integration' ? '' : 'hidden ',
            'dev_mode'     => $initialTab === 'dev-mode' ? '' : 'hidden ',
        ],
        'header_actions_class' => [
            'theme'        => $initialTab === 'theme' ? 'flex' : 'hidden',
            'branding'     => $initialTab === 'branding' ? 'flex' : 'hidden',
            'fence_colors' => $initialTab === 'fence-colors' ? 'flex' : 'hidden',
            'catalog'      => $initialTab === 'catalog' ? 'flex' : 'hidden',
            'system'       => $initialTab === 'system' ? 'flex' : 'hidden',
            'integration'  => $initialTab === 'integration' ? 'flex' : 'hidden',
            'dev_mode'     => $initialTab === 'dev-mode' ? 'flex' : 'hidden',
        ],
        'bootstrap'        => $bootstrap,
    ];
}

function fc_settings_admin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fc_settings_admin_theme_field_id(string $varName): string
{
    return 'fc-theme-' . str_replace('-', '_', preg_replace('/^--fc-/', '', $varName) ?? $varName);
}

/**
 * @param array<string, mixed> $preset
 */
function fc_settings_admin_preset_accent(array $preset): string
{
    if (!empty($preset['swatch'])) {
        return (string) $preset['swatch'];
    }

    $colors = is_array($preset['colors'] ?? null) ? $preset['colors'] : [];

    return (string) ($colors['--fc-princeton-orange'] ?? '#f67925');
}

function fc_settings_admin_hex_to_rgb(string $hex): ?array
{
    $normalized = strtolower(ltrim($hex, '#'));
    if (strlen($normalized) === 3) {
        $normalized = $normalized[0] . $normalized[0] . $normalized[1] . $normalized[1] . $normalized[2] . $normalized[2];
    }
    if (!preg_match('/^[0-9a-f]{6}$/', $normalized)) {
        return null;
    }

    return [
        'r' => hexdec(substr($normalized, 0, 2)),
        'g' => hexdec(substr($normalized, 2, 2)),
        'b' => hexdec(substr($normalized, 4, 2)),
    ];
}

function fc_settings_admin_preset_badge_styles(string $accent): string
{
    $rgb = fc_settings_admin_hex_to_rgb($accent);
    if ($rgb === null) {
        return 'color:#f67925;border-color:rgba(246,121,37,0.35);background:rgba(246,121,37,0.12);';
    }

    return sprintf(
        'color:%s;border-color:rgba(%d,%d,%d,0.35);background:rgba(%d,%d,%d,0.12);',
        $accent,
        $rgb['r'],
        $rgb['g'],
        $rgb['b'],
        $rgb['r'],
        $rgb['g'],
        $rgb['b']
    );
}

function fc_settings_admin_normalize_hex(string $color): ?string
{
    $value = trim($color);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        return strtolower($value);
    }
    if (preg_match('/^#[0-9a-fA-F]{3}$/', $value)) {
        return strtolower(
            '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3]
        );
    }

    return null;
}

function fc_settings_admin_fence_picker_value(string $color): string
{
    return fc_settings_admin_normalize_hex($color) ?? '#cccccc';
}

/**
 * @param array<string, mixed> $row
 */
function fc_settings_admin_fence_preview_url(array $row, string $appBase): string
{
    $image = trim((string) ($row['image'] ?? ''));
    if ($image === '') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $image) || preg_match('/^data:/i', $image) || str_starts_with($image, '//')) {
        return $image;
    }
    if (preg_match('/^url\(/i', $image)) {
        $inner = preg_replace('/^url\(\s*/i', '', $image);
        $inner = preg_replace('/\s*\)\s*;?\s*$/', '', (string) $inner);
        $inner = trim($inner, "\"'");
        if (preg_match('/^https?:\/\//i', $inner) || preg_match('/^data:/i', $inner)) {
            return $inner;
        }

        return $appBase !== '' ? rtrim($appBase, '/') . '/' . ltrim($inner, '/') : $inner;
    }

    return $appBase !== '' ? rtrim($appBase, '/') . '/' . ltrim($image, '/') : $image;
}

/**
 * @param array<string, mixed> $row
 */
function fc_settings_admin_fence_row_background(array $row): string
{
    $image = trim((string) ($row['image'] ?? ''));
    $color = trim((string) ($row['color'] ?? ''));
    if ($image !== '') {
        return preg_match('/^url\(/i', $image) ? $image : 'url(' . $image . ')';
    }

    return $color !== '' ? $color : '#e2e8f0';
}

/**
 * @param list<array<string, mixed>> $defaults
 */
function fc_settings_admin_is_original_fence_slug(string $slug, array $defaults): bool
{
    $slug = trim($slug);
    foreach ($defaults as $row) {
        if (trim((string) ($row['slug'] ?? '')) === $slug) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function fc_settings_admin_branding_field_order(): array
{
    return ['logo', 'appName', 'tagline', 'version'];
}

/**
 * @return list<array{id:string,label:string}>
 */
function fc_settings_admin_fence_sort_columns(): array
{
    return [
        ['id' => 'slug', 'label' => 'Slug'],
        ['id' => 'label', 'label' => 'Label'],
        ['id' => 'subLabel', 'label' => 'Sub label'],
        ['id' => 'color', 'label' => 'Color'],
        ['id' => 'image', 'label' => 'Image'],
    ];
}

function fc_settings_admin_tab_visible(string $activeTab, string $tab): bool
{
    return $activeTab === $tab;
}

function fc_settings_admin_show_preview(string $activeTab): bool
{
    return $activeTab === 'theme' || $activeTab === 'branding';
}
