<?php

declare(strict_types=1);

namespace Fc\Admin\Presenters;

use Fc\Admin\Helpers\ColorHelper;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\CatalogSettings;
use Fc\Admin\Settings\ConsoleSettings;
use Fc\Admin\Settings\FenceColorSettings;
use Fc\Admin\Settings\IntegrationsSettings;
use Fc\Admin\Settings\PlannerOptionSettings;
use Fc\Admin\Settings\SystemSettings;
use Fc\Admin\Settings\ThemeSettings;

/**
 * Settings page — pure formatting + page orchestration (config/settings_admin.php migration).
 * The underlying theme/branding/fence-colors/catalog/system/integrations/console config files
 * are shared, cross-cutting infrastructure (used far beyond this page) and stay untouched;
 * this class calls their existing *_api_payload()/defaults()/choices() functions unchanged.
 */
final class SettingsPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function viewData(string $adminBase, string $appBase, string $initialTab): array
    {
        $theme = ThemeSettings::apiPayload();
        $brandingPayload = BrandingSettings::apiPayload();
        $fencePayload = FenceColorSettings::apiPayload();
        // Avoid booting WP for every settings tab; load WC options only on Catalog.
        $catalogPayload = CatalogSettings::apiPayload($initialTab === 'catalog');
        $systemPayload = SystemSettings::apiPayload();
        $integrationsPayload = IntegrationsSettings::apiPayload();
        $integrationsData = is_array($integrationsPayload['integrations'] ?? null) ? $integrationsPayload['integrations'] : [];
        if (!empty($integrationsData['sites']) && is_array($integrationsData['sites'])) {
            foreach ($integrationsData['sites'] as &$siteRow) {
                if (is_array($siteRow)) {
                    $logoPath = (string) ($siteRow['logo'] ?? '');
                    if ($logoPath === '') {
                        $logoPath = (string) ($siteRow['logoDefault'] ?? '');
                    }
                    $siteRow['logoUrl'] = BrandingSettings::logoUrl($appBase, ['logo' => $logoPath]);
                }
            }
            unset($siteRow);
        }
        $projectPlanPayload = PlannerOptionSettings::apiPayload();
        $projectPlanItems = is_array($projectPlanPayload['extraItems'] ?? null) ? $projectPlanPayload['extraItems'] : [];
        foreach ($projectPlanItems as &$ppItem) {
            if (is_array($ppItem)) {
                $imgPath = (string) ($ppItem['image'] ?? '');
                if ($imgPath === '') {
                    $imgPath = (string) ($ppItem['imageDefault'] ?? '');
                }
                $ppItem['imageUrl'] = BrandingSettings::logoUrl($appBase, ['logo' => $imgPath]);
            }
        }
        unset($ppItem);
        $projectPlanDefaults = is_array($projectPlanPayload['defaults'] ?? null) ? $projectPlanPayload['defaults'] : [];
        $consolePayload = ConsoleSettings::apiPayload();
        $console = is_array($consolePayload['console'] ?? null)
            ? $consolePayload['console']
            : ConsoleSettings::defaults();

        $activePreset = (string) ($theme['activePreset'] ?? ThemeSettings::detectPreset($theme['colors'] ?? []) ?? '');
        $colors = is_array($theme['colors'] ?? null) ? $theme['colors'] : [];
        $schema = is_array($theme['schema'] ?? null) ? $theme['schema'] : [];
        $presetsRaw = is_array($theme['presets'] ?? null) ? array_values($theme['presets']) : [];
        $branding = is_array($brandingPayload['branding'] ?? null) ? $brandingPayload['branding'] : [];
        $brandingSchema = is_array($brandingPayload['schema'] ?? null) ? $brandingPayload['schema'] : [];
        $fenceColors = is_array($fencePayload['fenceColors'] ?? null) ? $fencePayload['fenceColors'] : [];
        $fenceDefaults = is_array($fencePayload['defaults'] ?? null) ? $fencePayload['defaults'] : [];
        $catalog = is_array($catalogPayload['catalog'] ?? null) ? $catalogPayload['catalog'] : CatalogSettings::defaults();
        $system = is_array($systemPayload['system'] ?? null) ? $systemPayload['system'] : SystemSettings::defaults();

        $bootstrap = [
            'activeTab' => $initialTab,
            'colors' => $colors,
            'defaults' => $theme['defaults'] ?? [],
            'schema' => $schema,
            'presets' => $presetsRaw,
            'activePreset' => $activePreset !== '' ? $activePreset : null,
            'selectedPreset' => $activePreset !== '' ? $activePreset : null,
            'branding' => $branding,
            'brandingDefaults' => $brandingPayload['defaults'] ?? [],
            'brandingSchema' => $brandingSchema,
            'fenceColors' => $fenceColors,
            'fenceColorsDefaults' => $fenceDefaults,
            'catalog' => $catalog,
            'catalogDefaults' => $catalogPayload['defaults'] ?? CatalogSettings::defaults(),
            'catalogOrderbyChoices' => $catalogPayload['orderbyChoices'] ?? CatalogSettings::orderbyChoices(),
            'catalogResultsPerPageChoices' => $catalogPayload['resultsPerPageChoices'] ?? CatalogSettings::resultsPerPageChoices(),
            'catalogCategories' => $catalogPayload['categories'] ?? [],
            'catalogAttributes' => $catalogPayload['attributes'] ?? [],
            'catalogOptionsError' => (string) ($catalogPayload['optionsError'] ?? ''),
            'system' => $system,
            'systemDefaults' => $systemPayload['defaults'] ?? SystemSettings::defaults(),
            'systemDatePeriodChoices' => $systemPayload['datePeriodChoices'] ?? SystemSettings::datePeriodChoices(),
            'systemDateFieldChoices' => $systemPayload['dateFieldChoices'] ?? SystemSettings::dateFieldChoices(),
            'systemDateFormatChoices' => $systemPayload['dateFormatChoices'] ?? SystemSettings::dateFormatChoices(),
            'integrations' => $integrationsData,
            'integrationsInitial' => $integrationsData,
            'integrationsRevision' => (string) ($integrationsPayload['revision'] ?? ''),
            'superAdmin' => is_array($integrationsPayload['superAdmin'] ?? null)
                ? $integrationsPayload['superAdmin']
                : [],
            'projectPlanItems' => $projectPlanItems,
            'projectPlanDefaults' => $projectPlanDefaults,
            'console' => $console,
            'consoleDefaults' => $consolePayload['defaults'] ?? ConsoleSettings::defaults(),
            'csrf' => AuthService::csrfToken(),
        ];

        $presets = [];
        foreach ($presetsRaw as $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $presetId = (string) ($preset['id'] ?? '');
            $presetColors = is_array($preset['colors'] ?? null) ? $preset['colors'] : [];
            $accent = self::presetAccent($preset);
            $presets[] = [
                'id' => $presetId,
                'label' => (string) ($preset['label'] ?? $presetId),
                'description' => (string) ($preset['description'] ?? ''),
                'accent' => $accent,
                'brand_primary' => (string) ($presetColors['--fc-brand-primary'] ?? '#d4112f'),
                'is_active' => $activePreset === $presetId,
                'is_selected' => $activePreset === $presetId,
                'badge_styles' => self::presetBadgeStyles($accent),
                'card_class' => $activePreset === $presetId
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
                $fieldId = self::themeFieldId($varName);
                $fields[] = [
                    'var' => $varName,
                    'label' => (string) $label,
                    'value' => $value,
                    'field_id' => $fieldId,
                    'picker_value' => self::fencePickerValue($value),
                ];
            }
            $themeGroups[] = [
                'key' => (string) $groupKey,
                'label' => (string) ($group['label'] ?? $groupKey),
                'fields' => $fields,
            ];
        }

        $brandingFields = [];
        foreach (self::brandingFieldOrder() as $key) {
            if (!isset($brandingSchema[$key]) || !is_array($brandingSchema[$key])) {
                continue;
            }
            $field = $brandingSchema[$key];
            $brandingFields[] = [
                'key' => $key,
                'type' => (string) ($field['type'] ?? 'text'),
                'field_id' => 'fc-branding-' . $key,
                'label' => (string) ($field['label'] ?? $key),
                'value' => (string) ($branding[$key] ?? ''),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'title' => (string) ($field['help'] ?? ''),
                'help' => (string) ($field['help'] ?? ''),
                'logo_url' => $key === 'logo' ? BrandingSettings::logoUrl($appBase, $branding) : '',
                'favicon_url' => $key === 'favicon' ? BrandingSettings::faviconUrl($appBase, $branding) : '',
            ];
        }

        $fenceRows = [];
        foreach ($fenceColors as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = (string) ($row['slug'] ?? '');
            $isOriginal = self::isOriginalFenceSlug($slug, $fenceDefaults);
            $fenceRows[] = [
                'index' => (int) $index,
                'slug' => $slug,
                'label' => (string) ($row['label'] ?? ''),
                'sub_label' => (string) ($row['subLabel'] ?? ''),
                'color' => (string) ($row['color'] ?? ''),
                'image' => (string) ($row['image'] ?? ''),
                'is_original' => $isOriginal,
                'row_class' => $isOriginal ? ' fc-fs-kv-row--locked' : '',
                'bg' => self::fenceRowBackground($row),
                'preview_url' => self::fencePreviewUrl($row, $appBase),
                'picker_value' => self::fencePickerValue((string) ($row['color'] ?? '')),
            ];
        }

        $showPreview = self::showPreview($initialTab);

        return [
            'initial_tab' => $initialTab,
            'active_tab' => $initialTab,
            'admin_base' => $adminBase,
            'app_base' => $appBase,
            'show_preview' => $showPreview,
            'layout_class' => $showPreview ? 'lg:grid-cols-2' : '',
            'preview_hidden' => $showPreview ? '' : 'hidden ',
            'preview_mode' => $initialTab === 'branding' ? 'branding' : '',
            'bootstrap_json' => json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'tabs' => [
                'theme' => 'Theme',
                'branding' => 'Branding',
                'fence-colors' => 'Fence colors',
                'catalog' => 'Catalog',
                'system' => 'System',
                'project-plan' => 'Project Plan',
                'integration' => 'Integration',
                'console' => 'Console',
            ],
            'btn_secondary' => 'btn btn-sm btn-dark fw-semibold',
            'btn_primary' => 'btn btn-sm btn-orange fw-semibold',
            'presets' => $presets,
            'theme_groups' => $themeGroups,
            'branding_fields' => $brandingFields,
            'branding_preview' => [
                'app_name' => (string) ($branding['appName'] ?? 'Fencing Calculator'),
                'tagline' => (string) ($branding['tagline'] ?? ''),
                'version' => (string) ($branding['version'] ?? ''),
                'logo_url' => BrandingSettings::logoUrl($appBase, $branding),
                'favicon_url' => BrandingSettings::faviconUrl($appBase, $branding),
            ],
            'fence_sort_columns' => self::fenceSortColumns(),
            'fence_rows' => $fenceRows,
            'has_fence_rows' => $fenceRows !== [],
            'catalog' => $catalog,
            'catalog_orderby_choices' => $catalogPayload['orderbyChoices'] ?? CatalogSettings::orderbyChoices(),
            'catalog_results_per_page_choices' => $catalogPayload['resultsPerPageChoices'] ?? CatalogSettings::resultsPerPageChoices(),
            'system' => $system,
            'system_date_period_choices' => $systemPayload['datePeriodChoices'] ?? SystemSettings::datePeriodChoices(),
            'system_date_field_choices' => $systemPayload['dateFieldChoices'] ?? SystemSettings::dateFieldChoices(),
            'system_date_format_choices' => $systemPayload['dateFormatChoices'] ?? SystemSettings::dateFormatChoices(),
            'integrations' => $integrationsData,
            'integration_webhook_mode_choices' => $integrationsPayload['webhookModeChoices'] ?? IntegrationsSettings::webhookModeChoices(),
            'super_admin' => is_array($integrationsPayload['superAdmin'] ?? null)
                ? $integrationsPayload['superAdmin']
                : [],
            'project_plan_items' => $projectPlanItems,
            'console' => $console,
            'panel_class' => [
                'theme' => $initialTab === 'theme' ? '' : 'hidden ',
                'branding' => $initialTab === 'branding' ? '' : 'hidden ',
                'fence_colors' => $initialTab === 'fence-colors' ? '' : 'hidden ',
                'catalog' => $initialTab === 'catalog' ? '' : 'hidden ',
                'system' => $initialTab === 'system' ? '' : 'hidden ',
                'integration' => $initialTab === 'integration' ? '' : 'hidden ',
                'project_plan' => $initialTab === 'project-plan' ? '' : 'hidden ',
                'console' => $initialTab === 'console' ? '' : 'hidden ',
            ],
            'header_actions_class' => [
                'theme' => $initialTab === 'theme' ? 'flex' : 'hidden',
                'branding' => $initialTab === 'branding' ? 'flex' : 'hidden',
                'fence_colors' => $initialTab === 'fence-colors' ? 'flex' : 'hidden',
                'catalog' => $initialTab === 'catalog' ? 'flex' : 'hidden',
                'system' => $initialTab === 'system' ? 'flex' : 'hidden',
                'integration' => $initialTab === 'integration' ? 'flex' : 'hidden',
                'project_plan' => $initialTab === 'project-plan' ? 'flex' : 'hidden',
                'console' => $initialTab === 'console' ? 'flex' : 'hidden',
            ],
            'bootstrap' => $bootstrap,
        ];
    }

    private static function themeFieldId(string $varName): string
    {
        return 'fc-theme-' . str_replace('-', '_', preg_replace('/^--fc-/', '', $varName) ?? $varName);
    }

    /**
     * @param array<string, mixed> $preset
     */
    private static function presetAccent(array $preset): string
    {
        if (!empty($preset['swatch'])) {
            return (string) $preset['swatch'];
        }

        $colors = is_array($preset['colors'] ?? null) ? $preset['colors'] : [];

        return (string) ($colors['--fc-princeton-orange'] ?? '#f67925');
    }

    private static function hexToRgb(string $hex): ?array
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

    private static function presetBadgeStyles(string $accent): string
    {
        $rgb = self::hexToRgb($accent);
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

    private static function fencePickerValue(string $color): string
    {
        return ColorHelper::normalizeHex($color) ?? '#cccccc';
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function fencePreviewUrl(array $row, string $appBase): string
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
    private static function fenceRowBackground(array $row): string
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
    private static function isOriginalFenceSlug(string $slug, array $defaults): bool
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
    private static function brandingFieldOrder(): array
    {
        return ['logo', 'favicon', 'appName', 'tagline', 'version'];
    }

    /**
     * @return list<array{id:string,label:string}>
     */
    private static function fenceSortColumns(): array
    {
        return [
            ['id' => 'slug', 'label' => 'Slug'],
            ['id' => 'label', 'label' => 'Label'],
            ['id' => 'subLabel', 'label' => 'Sub label'],
            ['id' => 'color', 'label' => 'Color'],
            ['id' => 'image', 'label' => 'Image'],
        ];
    }

    private static function showPreview(string $activeTab): bool
    {
        return $activeTab === 'branding';
    }
}
