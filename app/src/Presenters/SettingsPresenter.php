<?php

declare(strict_types=1);

namespace Fc\Admin\Presenters;

use Fc\Admin\Helpers\ColorHelper;
use Fc\Admin\Helpers\FormatHelper;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\MinifyService;
use Fc\Admin\Services\PermissionService;
use Fc\Admin\Services\SiteHealthService;
use Fc\Admin\Services\SiteRegistryService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\CatalogSettings;
use Fc\Admin\Settings\ConsoleSettings;
use Fc\Admin\Settings\FenceColorSettings;
use Fc\Admin\Settings\IntegrationsSettings;
use Fc\Admin\Settings\MinifySettings;
use Fc\Admin\Settings\PlannerOptionSettings;
use Fc\Admin\Settings\SeoSettings;
use Fc\Admin\Settings\SystemSettings;
use Fc\Admin\Settings\ThemeSettings;

/**
 * Settings page — pure formatting + page orchestration. The underlying settings groups
 * (theme/branding/fence-colors/catalog/system/integrations/seo/console) are shared,
 * cross-cutting infrastructure used far beyond this page; this class only calls their
 * apiPayload()/defaults()/choices() methods and never mutates them.
 */
final class SettingsPresenter
{
    /**
     * @param bool $siteHealth whether to show the Site Health tab (the Super Admin only)
     * @param bool $cloudflarePurge whether the Integration tab offers each site's Cloudflare purge (settings.cache)
     * @return array<string, mixed>
     */
    public static function viewData(
        string $adminBase,
        string $appBase,
        string $initialTab,
        bool $siteHealth = false,
        bool $cloudflarePurge = false
    ): array
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
        $projectPlanStock = is_array($projectPlanPayload['stock'] ?? null)
            ? $projectPlanPayload['stock']
            : PlannerOptionSettings::stockDefaults();
        $projectPlanStockDefaults = is_array($projectPlanPayload['stockDefaults'] ?? null)
            ? $projectPlanPayload['stockDefaults']
            : PlannerOptionSettings::stockDefaults();
        $consolePayload = ConsoleSettings::apiPayload();
        $console = is_array($consolePayload['console'] ?? null)
            ? $consolePayload['console']
            : ConsoleSettings::defaults();
        $minifyStatus = self::minifyStatus();

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
        $seoPayload = SeoSettings::apiPayload();
        $seo = $seoPayload['seo'];
        $seoContext = self::seoContext($appBase, $branding);

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
            'systemEntriesDatePeriodChoices' => $systemPayload['entriesDatePeriodChoices'] ?? SystemSettings::entriesDatePeriodChoices(),
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
            'projectPlanStock' => $projectPlanStock,
            'projectPlanStockDefaults' => $projectPlanStockDefaults,
            'seo' => $seo,
            'seoDefaults' => $seoPayload['defaults'],
            'seoContext' => $seoContext,
            'console' => $console,
            'consoleDefaults' => $consolePayload['defaults'] ?? ConsoleSettings::defaults(),
            'minify' => $minifyStatus['minify'],
            'minifyDefaults' => MinifySettings::defaults(),
            'minifyTool' => $minifyStatus['tool'],
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
                // Card titles are Title Case across the Settings page.
                'label' => ucwords((string) ($group['label'] ?? $groupKey)),
                // Two fields to a row, the card's section lines running between rows.
                'rows' => array_chunk($fields, 2),
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
                'initial' => (string) ($row['initial'] ?? ''),
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
        $navGroups = self::navGroups($initialTab, $siteHealth);
        $integrationSites = self::integrationSites($integrationsData);

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
            'nav_groups' => $navGroups,
            'section' => self::activeSection($navGroups),
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
            'system_entries_date_period_choices' => $systemPayload['entriesDatePeriodChoices'] ?? SystemSettings::entriesDatePeriodChoices(),
            'system_date_field_choices' => $systemPayload['dateFieldChoices'] ?? SystemSettings::dateFieldChoices(),
            'system_date_format_choices' => $systemPayload['dateFormatChoices'] ?? SystemSettings::dateFormatChoices(),
            'integrations' => $integrationsData,
            'integration_sites' => $integrationSites,
            'integration_sites_label' => count($integrationSites) . (count($integrationSites) === 1 ? ' site' : ' sites'),
            'cloudflare_purge_enabled' => $cloudflarePurge,
            'integration_webhook_options' => self::webhookOptions(
                $integrationsData,
                $integrationsPayload['webhookModeChoices'] ?? IntegrationsSettings::webhookModeChoices()
            ),
            'super_admin' => is_array($integrationsPayload['superAdmin'] ?? null)
                ? $integrationsPayload['superAdmin']
                : [],
            'project_plan_items' => $projectPlanItems,
            'project_plan_stock' => $projectPlanStock,
            'project_plan_stock_defaults' => $projectPlanStockDefaults,
            'project_plan_stock_hours_min' => PlannerOptionSettings::ORDER_WITHIN_HOURS_MIN,
            'project_plan_stock_hours_max' => PlannerOptionSettings::ORDER_WITHIN_HOURS_MAX,
            'seo' => $seo,
            'seo_defaults' => $seoPayload['defaults'],
            'seo_context' => $seoContext,
            'seo_visible' => !empty($seo['searchEngineVisible']),
            'seo_checks' => self::seoChecks(),
            // Google's and Facebook's testers fetch the page from outside, which a localhost copy can't serve.
            'seo_tests_enabled' => $seoContext['blockReason'] !== 'localhost',
            'seo_pages' => self::seoPages($seo),
            'seo_placeholders' => self::seoPlaceholders($seoPayload['placeholders']),
            'seo_verification' => self::seoVerificationFields($seo, $seoPayload['verificationServices']),
            'seo_same_as' => implode("\n", $seo['schemaSameAs']),
            'seo_language_choices' => $seoPayload['languageChoices'],
            'seo_image_preview_choices' => $seoPayload['imagePreviewChoices'],
            'seo_twitter_card_choices' => $seoPayload['twitterCardChoices'],
            'seo_title_limit' => SeoSettings::TITLE_LIMIT,
            'seo_description_limit' => SeoSettings::DESCRIPTION_LIMIT,
            'console' => $console,
            'minify' => $minifyStatus,
            'site_health_enabled' => $siteHealth,
            'site_health_groups' => self::siteHealthGroups(),
            'panel_class' => [
                'theme' => $initialTab === 'theme' ? '' : 'hidden ',
                'branding' => $initialTab === 'branding' ? '' : 'hidden ',
                'fence_colors' => $initialTab === 'fence-colors' ? '' : 'hidden ',
                'catalog' => $initialTab === 'catalog' ? '' : 'hidden ',
                'system' => $initialTab === 'system' ? '' : 'hidden ',
                'integration' => $initialTab === 'integration' ? '' : 'hidden ',
                'project_plan' => $initialTab === 'project-plan' ? '' : 'hidden ',
                'seo' => $initialTab === 'seo' ? '' : 'hidden ',
                'console' => $initialTab === 'console' ? '' : 'hidden ',
                'minify' => $initialTab === 'minify' ? '' : 'hidden ',
                'site_health' => $initialTab === 'site-health' ? '' : 'hidden ',
            ],
            'header_actions_class' => [
                'theme' => $initialTab === 'theme' ? 'flex' : 'hidden',
                'branding' => $initialTab === 'branding' ? 'flex' : 'hidden',
                'fence_colors' => $initialTab === 'fence-colors' ? 'flex' : 'hidden',
                'catalog' => $initialTab === 'catalog' ? 'flex' : 'hidden',
                'system' => $initialTab === 'system' ? 'flex' : 'hidden',
                'integration' => $initialTab === 'integration' ? 'flex' : 'hidden',
                'project_plan' => $initialTab === 'project-plan' ? 'flex' : 'hidden',
                'seo' => $initialTab === 'seo' ? 'flex' : 'hidden',
                'console' => $initialTab === 'console' ? 'flex' : 'hidden',
                'minify' => $initialTab === 'minify' ? 'flex' : 'hidden',
                'site_health' => $initialTab === 'site-health' ? 'flex' : 'hidden',
            ],
            'bootstrap' => $bootstrap,
        ];
    }

    /**
     * The section rail: every tab under its group, in rail order. The header shows the open tab's
     * label and description, and settings.js swaps them in from the tab's data attributes.
     *
     * @return list<array{label:string, items:list<array{id:string, label:string, icon:string, description:string, tab_id:string, panel_id:string, is_active:bool}>}>
     */
    private static function navGroups(string $activeTab, bool $siteHealth): array
    {
        $groups = [
            'Appearance' => [
                'theme'        => ['Theme', 'fa-palette', 'Colour presets and the brand palette shared by the planner and admin.'],
                'branding'     => ['Branding', 'fa-pen-nib', 'Logo, favicon, app name, tagline and version shown across the app.'],
                'fence-colors' => ['Fence colors', 'fa-fill-drip', 'Finishes customers pick in the planner, with their SKU colour codes.'],
            ],
            'Storefront' => [
                'catalog'      => ['Catalog', 'fa-book-open', 'Layout, sorting and filters for the public Product Lookup page.'],
                'project-plan' => ['Project Plan', 'fa-list-check', 'Extra items and the Stock & Delivery panel on each project plan.'],
            ],
            'Site' => [
                'system'       => ['System', 'fa-gear', 'Admin defaults for dates, the dashboard, entries and online presence.'],
                'integration'  => ['Integration', 'fa-plug', 'API keys, the planner webhook, custom code and per-site tracking IDs.'],
                'seo'          => ['SEO', 'fa-magnifying-glass-chart', 'How the planner appears in search results and when shared.'],
            ],
            'Developer' => [
                'console'      => ['Console', 'fa-terminal', 'Debug mode and the admin command console.'],
                'minify'       => ['Minify CSS & JS', 'fa-file-zipper', 'The minified copies of the frontend\'s and the admin\'s stylesheets and scripts, and whether they are served.'],
                'site-health'  => ['Site Health', 'fa-heart-pulse', 'Server, database and security checks for this install.'],
            ],
        ];
        if (!$siteHealth) {
            unset($groups['Developer']['site-health']);
        }

        $nav = [];
        foreach ($groups as $groupLabel => $tabs) {
            $items = [];
            foreach ($tabs as $id => [$label, $icon, $description]) {
                $items[] = [
                    'id'          => $id,
                    'label'       => $label,
                    'icon'        => $icon,
                    'description' => $description,
                    'tab_id'      => 'fc-settings-tab-' . $id,
                    'panel_id'    => 'fc-settings-panel-' . $id,
                    'is_active'   => $id === $activeTab,
                ];
            }
            $nav[] = ['label' => $groupLabel, 'items' => $items];
        }

        return $nav;
    }

    /**
     * The open tab's label and description for the section header (the first tab if none is open).
     *
     * @param list<array{label:string, items:list<array<string, mixed>>}> $navGroups
     * @return array{label:string, description:string}
     */
    private static function activeSection(array $navGroups): array
    {
        $first = null;
        foreach ($navGroups as $group) {
            foreach ($group['items'] as $item) {
                $section = ['label' => (string) $item['label'], 'description' => (string) $item['description']];
                if (!empty($item['is_active'])) {
                    return $section;
                }
                $first ??= $section;
            }
        }

        return $first ?? ['label' => 'Settings', 'description' => ''];
    }

    /**
     * The Integration tab's Sites table: one row per site, with the element ids its inputs, copy
     * buttons and logo drawer share. integration-tab.js paints and binds by these ids and data keys.
     *
     * @param array<string, mixed> $integrations
     * @return list<array<string, mixed>>
     */
    private static function integrationSites(array $integrations): array
    {
        $token = trim((string) ($integrations['cloudflareApiToken'] ?? ''));
        $rows = [];
        foreach (is_array($integrations['sites'] ?? null) ? $integrations['sites'] : [] as $site) {
            if (!is_array($site)) {
                continue;
            }
            $key = (string) ($site['key'] ?? '');
            $idBase = 'fc-integration-' . (preg_replace('/[^a-zA-Z0-9_-]+/', '-', $key !== '' ? $key : 'site') ?? 'site');
            $supplier = strtoupper(trim((string) ($site['supplier'] ?? '')));
            $zone = trim((string) ($site['cloudflareZoneId'] ?? ''));
            $rows[] = [
                'key' => $key,
                'label' => (string) ($site['label'] ?? '') !== '' ? (string) $site['label'] : $key,
                'supplier' => in_array($supplier, ['JG', 'GO'], true) ? $supplier : '',
                'logo' => (string) ($site['logo'] ?? ''),
                'logo_default' => (string) ($site['logoDefault'] ?? ''),
                'logo_url' => (string) ($site['logoUrl'] ?? ''),
                'gtag_id' => (string) ($site['gtagId'] ?? ''),
                'gtm_id' => (string) ($site['gtmId'] ?? ''),
                'cloudflare_zone_id' => (string) ($site['cloudflareZoneId'] ?? ''),
                'pid_prefix' => (string) ($site['pidPrefix'] ?? ''),
                // Verify and Purge need the token and a 32-character zone, as the server does;
                // integration-tab.js syncCloudflareButtons() applies the same rule live.
                'cloudflare_ready' => $token !== '' && preg_match('/^[a-f0-9]{32}$/i', $zone) === 1,
                'ids' => [
                    'logo' => $idBase . '-logo',
                    'logo_panel' => $idBase . '-logo-panel',
                    'supplier' => $idBase . '-supplier',
                    'pid' => $idBase . '-pidprefix',
                    'gtag' => $idBase . '-gtag',
                    'gtm' => $idBase . '-gtm',
                    'zone' => $idBase . '-cfzone',
                ],
            ];
        }

        return $rows;
    }

    /**
     * One row per webhook for the Integrations tab: its radio picks webhookMode, its field holds
     * the URL. Both the planner's submit and the checkout push follow the mode picked here.
     *
     * @param array<string, mixed> $integrations
     * @param array<string, string> $choices webhook modes the settings group accepts
     * @return list<array<string, mixed>>
     */
    private static function webhookOptions(array $integrations, array $choices): array
    {
        $rows = [
            'live' => [
                'title'       => 'LIVE Webhook',
                'hint'        => 'Planner submissions and checkout orders post here.',
                'field'       => 'webhookUrl',
                'placeholder' => 'https://hooks.zapier.com/hooks/catch/…',
                'copy_label'  => 'Copy webhook URL',
            ],
            'test' => [
                'title'       => 'TEST Webhook',
                'hint'        => 'Select to post submissions and orders here instead, e.g. while testing a Zap.',
                'field'       => 'webhookTestUrl',
                'placeholder' => 'https://webhook.site/…',
                'copy_label'  => 'Copy test webhook URL',
            ],
        ];
        $mode = (string) ($integrations['webhookMode'] ?? 'live');

        $options = [];
        foreach (array_keys($choices) as $value) {
            if (!isset($rows[$value])) {
                continue;
            }
            $options[] = $rows[$value] + [
                'mode'     => (string) $value,
                'input_id' => 'fc-integration-' . $rows[$value]['field'],
                'url'      => (string) ($integrations[$rows[$value]['field']] ?? ''),
                'checked'  => (string) $value === $mode,
            ];
        }

        return $options;
    }

    /**
     * What the SEO tab's previews need to fill the templates the way PlannerPageModel::seo() does:
     * the Branding values, this host's registry name and logo, and the planner's own address. The
     * admin shares its host with the planner, so the registry row is the one visitors get.
     *
     * @param array<string, mixed> $branding
     * @return array<string, string>
     */
    private static function seoContext(string $appBase, array $branding): array
    {
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $site   = SiteRegistryService::all($host, 'domain', true);
        $site   = is_array($site) ? $site : null;
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
        $logo   = SiteRegistryService::logoForDomain($host, trim((string) ($site['logo'] ?? '')));

        return [
            'appName'      => (string) ($branding['appName'] ?? ''),
            'tagline'      => (string) ($branding['tagline'] ?? ''),
            'autoSiteName' => SeoSettings::autoSiteName($site),
            'plannerUrl'   => $scheme . '://' . $host . rtrim($appBase, '/') . '/planner',
            'storeUrl'     => trim((string) ($site['url'] ?? '')),
            'logoPath'     => $logo,
            'logoUrl'      => BrandingSettings::logoUrl($appBase, ['logo' => $logo]),
            'faviconUrl'   => BrandingSettings::faviconUrl($appBase, $branding),
            'blockReason'  => SiteRegistryService::searchBlockReason(),
        ];
    }

    /**
     * The header's at-a-glance checks and the field each one jumps to. seo-tab.js works out every
     * tile's state and wording from the live form, so the tiles follow unsaved edits.
     *
     * @return list<array{key:string, label:string, short:string, target:string}>
     */
    private static function seoChecks(): array
    {
        // 'short' labels the pinned overview bar's chips, which must fit six to a line.
        return [
            ['key' => 'listing',     'label' => 'Search listing',   'short' => 'Listing',     'target' => 'fc-seo-indexPlanner'],
            ['key' => 'title',       'label' => 'SEO title',        'short' => 'Title',       'target' => 'fc-seo-titleTemplate'],
            ['key' => 'description', 'label' => 'Meta description', 'short' => 'Description', 'target' => 'fc-seo-descriptionTemplate'],
            ['key' => 'canonical',   'label' => 'Canonical URL',    'short' => 'Canonical',   'target' => 'fc-seo-canonicalEnabled'],
            ['key' => 'shareImage',  'label' => 'Share image',      'short' => 'Image',       'target' => 'fc-seo-socialImage'],
            ['key' => 'schema',      'label' => 'Structured data',  'short' => 'Schema',      'target' => 'fc-seo-schemaEnabled'],
        ];
    }

    /**
     * The Indexing list: the two pages an admin may open to search engines, then the ones kept out
     * for good because they hold a visitor's own quote or are the admin itself.
     *
     * @param array<string, mixed> $seo
     * @return list<array{label:string, path:string, hint:string, field:string, checked:bool, locked:bool}>
     */
    private static function seoPages(array $seo): array
    {
        return [
            [
                'label'   => 'Planner',
                'path'    => '/planner',
                'hint'    => 'The fence planner. Quote links (?qid=) stay hidden either way.',
                'field'   => 'indexPlanner',
                'checked' => !empty($seo['indexPlanner']),
                'locked'  => false,
            ],
            [
                'label'   => 'Product Lookup',
                'path'    => '/lookup',
                'hint'    => "Repeats the store's own product pages, so leave it off unless the store does not list them.",
                'field'   => 'indexLookup',
                'checked' => !empty($seo['indexLookup']),
                'locked'  => false,
            ],
            [
                'label'   => 'Project plans',
                'path'    => '/project-plan',
                'hint'    => "A visitor's own quote and materials list.",
                'field'   => '',
                'checked' => false,
                'locked'  => true,
            ],
            [
                'label'   => 'Shared cart links',
                'path'    => '/share-cart-url/…',
                'hint'    => "Rebuilds a saved quote's cart in the store.",
                'field'   => '',
                'checked' => false,
                'locked'  => true,
            ],
            [
                'label'   => 'Admin',
                'path'    => '/backend',
                'hint'    => 'Including the login page.',
                'field'   => '',
                'checked' => false,
                'locked'  => true,
            ],
        ];
    }

    /**
     * @param array<string, string> $placeholders token => what it stands for
     * @return list<array{token:string, label:string}>
     */
    private static function seoPlaceholders(array $placeholders): array
    {
        $chips = [];
        foreach ($placeholders as $token => $label) {
            $chips[] = ['token' => (string) $token, 'label' => (string) $label];
        }

        return $chips;
    }

    /**
     * @param array<string, mixed> $seo
     * @param array<string, array{label:string, meta:string, hint:string}> $services
     * @return list<array{key:string, field_id:string, label:string, meta:string, hint:string, value:string}>
     */
    private static function seoVerificationFields(array $seo, array $services): array
    {
        $fields = [];
        foreach ($services as $key => $service) {
            $fields[] = [
                'key'      => (string) $key,
                'field_id' => 'fc-seo-' . $key,
                'label'    => $service['label'],
                'meta'     => $service['meta'],
                'hint'     => $service['hint'],
                'value'    => (string) ($seo[$key] ?? ''),
            ];
        }

        return $fields;
    }

    /**
     * One Site Health card per check group, in SiteHealthService::GROUPS order. The cards fill in
     * once site-health-tab.js has run their checks; Security has the most rows, so it spans the row.
     *
     * @return list<array{key:string,label:string,title_id:string,card_class:string}>
     */
    private static function siteHealthGroups(): array
    {
        $groups = [];
        foreach (SiteHealthService::GROUPS as $key => $label) {
            $groups[] = [
                'key' => $key,
                'label' => $label,
                'title_id' => 'fc-health-title-' . $key,
                'card_class' => $key === 'security' ? 'fc-health-card--wide' : '',
            ];
        }

        return $groups;
    }

    /** Copy state => [row chip label, chip tone, chip title]; a failed row's title is its esbuild message. */
    private const MINIFY_STATES = [
        'fresh' => ['Up to date', 'good', 'The copy is at least as new as its source'],
        'stale' => ['Source newer', 'warn', 'The source was edited after this copy was built'],
        'missing' => ['Not built', 'bad', 'No copy under public/assets/min/'],
        'failed' => ['Build failed', 'bad', ''],
    ];

    /** Area => [heading, what its files serve], in tab order; each MinifyService group names its area. */
    private const MINIFY_AREAS = [
        'frontend' => ['Frontend', 'The public planner, Product Lookup and shared-cart pages.'],
        'admin' => ['Admin', 'This back office. The Product Lookup page also loads its buttons.css and theme.css.'],
    ];

    /**
     * The file grid's columns, each sortable by the matching minifyRow() 'sort' value. 'first' is the
     * direction of the first click: problems first for the text columns, newest or biggest first for the rest.
     */
    private const MINIFY_COLUMNS = [
        ['key' => 'file', 'label' => 'File', 'title' => '', 'first' => 'asc'],
        ['key' => 'status', 'label' => 'Status', 'title' => '', 'first' => 'asc'],
        ['key' => 'serving', 'label' => 'Serving', 'title' => 'What is served for this file right now', 'first' => 'asc'],
        ['key' => 'original', 'label' => 'Original', 'title' => 'Last modified, from the file on disk', 'first' => 'desc'],
        ['key' => 'minified', 'label' => 'Minified', 'title' => 'When public/assets/min/ was last written for this file', 'first' => 'desc'],
        ['key' => 'saved', 'label' => 'Smaller by', 'title' => '', 'first' => 'desc'],
    ];

    /** Status column order: what needs attention sorts first. */
    private const MINIFY_STATE_RANK = ['failed' => 0, 'missing' => 1, 'stale' => 2, 'fresh' => 3];

    /**
     * Settings → Minify CSS & JS: the four switches, the build tool, and every frontend and admin
     * CSS/JS file with its original and minified dates, grouped by area and then by file type. Every
     * string is final display text. The minify API answers with this same shape after a save or
     * build, and minify-tab.js patches the rendered rows and chips from it: each row key here is a
     * data-fc-minify-cell slot in admin/partials/minify-row.php and in paintRow() — edit the three in pairs.
     *
     * @return array<string, mixed>
     */
    public static function minifyStatus(): array
    {
        $settings = MinifySettings::get();
        $tool = MinifyService::tool();
        // Rebuilding runs esbuild on the server, so it follows the console's developer key.
        $canBuild = $tool['available'] && PermissionService::can('settings.dev_console');
        $format = SystemSettings::dateFormatPhp();
        $now = time();

        $groups = [];
        foreach (MinifyService::GROUPS as $key => $meta) {
            $type = strtoupper($meta['type']);
            $groups[$key] = [
                'key' => $key,
                'area' => $meta['area'],
                'type' => $meta['type'],
                // The card sits under its area's heading; the switch, toasts and messages name the area too.
                'label' => 'Minify ' . $type,
                'full_label' => 'Minify ' . $meta['area'] . ' ' . $type,
                'title_id' => 'fc-minify-title-' . $key,
                'noun' => $meta['area'] . ' ' . ($meta['type'] === 'css' ? 'stylesheets' : 'scripts'),
                'folder' => $meta['dir'] . '/',
                'enabled' => $settings[$key],
                'count' => 0,
                'fresh' => 0,
                'stale' => 0,
                'missing' => 0,
                'failed' => 0,
                'served' => 0,
                'files' => [],
                'newest' => 0,
                'source_bytes' => 0,
                'copy_bytes' => 0,
                'problems' => [],
                'errors' => [],
            ];
        }

        foreach (MinifyService::files() as $file) {
            $group = &$groups[$file['group']];
            $group['files'][] = self::minifyRow($file, $group['full_label'], $format, $now);
            $group['count']++;
            $group[$file['state']]++;
            $group['served'] += $file['served'] ? 1 : 0;
            if ($file['state'] === 'fresh') {
                $group['source_bytes'] += $file['source']['bytes'];
                $group['copy_bytes'] += $file['copy']['bytes'];
            } else {
                $group['problems'][] = basename($file['path']);
            }
            if ($file['state'] === 'failed') {
                $group['errors'][] = $file['path'] . ': ' . strtok($file['error'], "\n");
            }
            if ($file['copy'] !== null) {
                $group['newest'] = max($group['newest'], $file['copy']['mtime']);
            }
            unset($group);
        }

        foreach ($groups as &$group) {
            self::minifyGroupText($group, $canBuild, $format, $now);
            unset($group['newest'], $group['source_bytes'], $group['copy_bytes'], $group['problems'], $group['errors']);
        }
        unset($group);

        $areas = [];
        foreach (self::MINIFY_AREAS as $area => [$heading, $description]) {
            $members = array_values(array_filter($groups, static fn (array $group): bool => $group['area'] === $area));
            $areas[] = self::minifyArea($area, $heading, $description, $members);
        }
        $overview = self::minifyOverview($groups);

        $blank = self::minifyRow(['path' => '', 'group' => 'js', 'area' => 'frontend', 'type' => 'js', 'source' => ['bytes' => 0, 'mtime' => 0], 'copy' => null, 'state' => 'fresh', 'fresh' => false, 'served' => false, 'error' => ''], '', $format, $now);
        foreach ($blank as $key => $value) {
            if (is_string($value) && $key !== 'state' && $key !== 'chip_state') {
                $blank[$key] = '';
            }
        }

        return [
            // The page paints a status only when it is newer than the one it shows: a toggle answered mid-build must not undo the build's.
            'snapshot' => microtime(true),
            'minify' => $settings,
            'tool' => $tool,
            'can_build' => $canBuild,
            'build_unavailable_text' => $canBuild ? '' : ($tool['available'] ? 'Rebuilding is limited to developer roles' : 'Build not available on this server'),
            'build_unavailable_title' => $canBuild ? '' : ($tool['available'] ? 'Needs the Console permission (settings.dev_console).' : $tool['reason']),
            'overview' => $overview,
            'areas' => $areas,
            'blank_row' => $blank,
            'columns' => self::MINIFY_COLUMNS,
        ];
    }

    /** Tile icon per chip state, as the Site Health overview draws them. */
    private const MINIFY_STATE_ICONS = [
        'good' => 'fa-circle-check',
        'warn' => 'fa-triangle-exclamation',
        'bad' => 'fa-circle-xmark',
        'info' => 'fa-circle-info',
    ];

    /**
     * The overview card, like Site Health's: one score over every file, and a tile per group that
     * jumps to its card. A build failure scores bad, copies needing a rebuild warn; a group that is
     * only switched off stays information, since that is a choice.
     *
     * @param array<string, array<string, mixed>> $groups
     * @return array<string, mixed>
     */
    private static function minifyOverview(array $groups): array
    {
        $count = 0;
        $served = 0;
        $failed = 0;
        $faults = 0;
        $tiles = [];
        foreach ($groups as $group) {
            $count += $group['count'];
            $served += $group['served'];
            $failed += $group['failed'];
            if ($group['enabled']) {
                $faults += $group['count'] - $group['served'];
            }
            // A failed build outranks the card chip's wording, which only speaks of what is served.
            $state = $group['failed'] > 0 ? 'bad' : $group['chip_state'];
            $label = ucfirst($group['area']) . ' ' . strtoupper($group['type']);
            $value = $group['failed'] > 0 ? $group['failed'] . ' could not be minified' : $group['chip_text'];
            $tiles[] = [
                'key' => $group['key'],
                'label' => $label,
                'state' => $state,
                'icon' => self::MINIFY_STATE_ICONS[$state],
                'value' => $value,
                // The pinned bar's chips show the label only; the status rides in the tooltip.
                'title' => $label . ': ' . $value,
            ];
        }

        if ($count === 0) {
            $state = 'info';
        } elseif ($failed > 0) {
            $state = 'bad';
        } elseif ($served === $count) {
            $state = 'good';
        } else {
            $state = $faults > 0 ? 'warn' : 'info';
        }

        return [
            'state' => $state,
            'text' => $count === 0 ? 'No files found' : $served . ' of ' . $count . ' files served minified',
            'short' => $count === 0 ? 'No files' : $served . ' of ' . $count . ' minified',
            'percent' => $count > 0 ? (int) round($served / $count * 100) : 0,
            'tiles' => $tiles,
        ];
    }

    /**
     * One area's heading and summary chip over its type cards. A shortfall that only comes from a
     * switch being off reads as information, not a warning: that is a choice, not a fault.
     *
     * @param list<array<string, mixed>> $groups
     * @return array<string, mixed>
     */
    private static function minifyArea(string $area, string $heading, string $description, array $groups): array
    {
        $count = 0;
        $served = 0;
        $enabled = false;
        $faults = 0;
        foreach ($groups as $group) {
            $count += $group['count'];
            $served += $group['served'];
            if ($group['enabled']) {
                $enabled = true;
                $faults += $group['count'] - $group['served'];
            }
        }

        if ($count === 0) {
            [$chipState, $chipText] = ['info', 'No files'];
        } elseif (!$enabled) {
            [$chipState, $chipText] = ['info', 'Serving originals'];
        } else {
            $chipState = $served === $count ? 'good' : ($faults > 0 ? 'warn' : 'info');
            $chipText = $served . ' of ' . $count . ' served minified';
        }

        return [
            'key' => $area,
            'label' => $heading,
            'description' => $description,
            'title_id' => 'fc-minify-area-' . $area,
            'chip_state' => $chipState,
            'chip_text' => $chipText,
            'groups' => $groups,
        ];
    }

    /**
     * One file's display strings. Savings are printed for a fresh copy only: a copy of an older
     * source is not a number worth showing.
     *
     * @param array{path:string,group:string,area:string,type:string,source:array{bytes:int,mtime:int},copy:array{bytes:int,mtime:int}|null,state:string,fresh:bool,served:bool,error:string} $file
     * @return array<string, mixed>
     */
    private static function minifyRow(array $file, string $groupLabel, string $format, int $now): array
    {
        $state = $file['state'];
        [$stateLabel, $chip, $stateTitle] = self::MINIFY_STATES[$state];
        if ($state === 'failed') {
            $stateTitle = $file['error'];
        }
        $copy = $file['copy'];
        $sourceBytes = $file['source']['bytes'];
        $saved = $state === 'fresh' && $copy !== null && $sourceBytes > 0 ? (int) round((1 - $copy['bytes'] / $sourceBytes) * 100) : null;
        $servedTitle = $file['served'] ? 'The minified copy is served' : match ($state) {
            'stale' => 'The original is newer than its copy — the original is served',
            'missing' => 'No minified copy — the original is served',
            'failed' => 'The last build failed for this file — the original is served',
            default => $groupLabel . ' is off — the original is served',
        };

        return [
            'path' => $file['path'],
            'name' => basename($file['path']),
            'dir' => dirname($file['path']) . '/',
            'full_path' => 'public/assets/' . $file['path'],
            'state' => $state,
            'state_label' => $stateLabel,
            'chip_state' => $chip,
            'state_title' => $stateTitle,
            'served' => $file['served'],
            'served_label' => $file['served'] ? 'Minified' : 'Original',
            'served_title' => $servedTitle,
            'source_main' => date($format, $file['source']['mtime']),
            'source_sub' => FormatHelper::bytes($sourceBytes),
            'source_title' => self::ago($file['source']['mtime'], $now),
            'source_newer' => $state === 'stale',
            'copy_main' => $copy === null ? '—' : date($format, $copy['mtime']),
            'copy_sub' => $copy === null ? 'No copy' : FormatHelper::bytes($copy['bytes']),
            'copy_title' => $copy === null ? 'No minified copy' : self::ago($copy['mtime'], $now),
            'saved_main' => $saved === null ? '—' : ($saved > 0 ? '−' . $saved . '%' : ($saved === 0 ? '0%' : '+' . abs($saved) . '%')),
            'saved_sub' => $saved === null ? '' : FormatHelper::bytes(max(0, $sourceBytes - $copy['bytes'])),
            'saved_title' => $saved !== null ? '' : ($state === 'missing' ? 'No copy' : 'Rebuild to measure'),
            'error' => $file['error'],
            // Raw values per MINIFY_COLUMNS key; null (no copy, nothing saved) sorts last either way.
            'sort_json' => (string) json_encode([
                'file' => strtolower(basename($file['path'])),
                'status' => self::MINIFY_STATE_RANK[$state],
                'serving' => $file['served'] ? 1 : 0,
                'original' => $file['source']['mtime'],
                'minified' => $copy['mtime'] ?? null,
                'saved' => $saved,
            ]),
        ];
    }

    /**
     * A type card's chrome from its counts: the meta line, the header chip, the status row and the
     * filter pills. Precedence: no files, then a failed build, then the switch being off, then
     * copies needing a rebuild, then all good.
     *
     * @param array<string, mixed> $group by reference
     */
    private static function minifyGroupText(array &$group, bool $canBuild, string $format, int $now): void
    {
        $count = $group['count'];
        $fresh = $group['fresh'];
        $served = $group['served'];
        $problems = count($group['problems']);

        $pct = $fresh > 0 && $group['source_bytes'] > 0 ? (int) round((1 - $group['copy_bytes'] / $group['source_bytes']) * 100) : null;
        $saving = $pct === null
            ? ''
            : FormatHelper::bytes($group['source_bytes']) . ' → ' . FormatHelper::bytes($group['copy_bytes'])
                . ' (' . ($pct < 0 ? '+' . abs($pct) : '−' . $pct) . '%)';
        $meta = $count . ' ' . ($count === 1 ? 'file' : 'files');
        if ($fresh > 0 && $fresh < $count) {
            $meta .= ' · ' . $fresh . ' up to date';
        }
        if ($saving !== '') {
            $meta .= ' · ' . $saving . ($group['enabled'] ? '' : ' when on');
        } elseif ($count > 0) {
            $meta .= ' · not built';
        }
        $group['meta'] = $meta;

        // The chip counts what is served (mtime-fresh copies); "up to date" below excludes a remembered build failure.
        if ($count === 0) {
            [$group['chip_state'], $group['chip_text']] = ['info', 'No files'];
        } elseif (!$group['enabled']) {
            [$group['chip_state'], $group['chip_text']] = ['info', 'Serving originals'];
        } elseif ($served === $count) {
            [$group['chip_state'], $group['chip_text']] = ['good', 'Serving minified'];
        } elseif ($served === 0) {
            [$group['chip_state'], $group['chip_text']] = ['warn', 'Nothing to serve yet'];
        } else {
            [$group['chip_state'], $group['chip_text']] = ['warn', $served . ' of ' . $count . ' minified'];
        }

        $shown = array_slice($group['problems'], 0, 3);
        $more = $problems - count($shown);
        $problemList = implode(', ', $shown) . ($more > 0 ? ' (+' . $more . ' more)' : '');
        $copies = $count . ' ' . ($count === 1 ? 'copy' : 'copies');
        $are = $count === 1 ? 'is' : 'are';
        $one = $problems === 1;
        if ($count === 0) {
            $status = ['info', 'fa-circle-info', 'No ' . strtoupper($group['type']) . ' files found under public/assets/' . $group['folder'], ''];
        } elseif ($group['failed'] > 0) {
            $errors = array_slice($group['errors'], 0, 1);
            $status = ['bad', 'fa-circle-xmark', $group['failed'] . ' of ' . $count . ' could not be minified',
                $errors[0] . (count($group['errors']) > 1 ? ' (+' . (count($group['errors']) - 1) . ' more)' : '')];
        } elseif (!$group['enabled']) {
            $status = ['info', 'fa-circle-info', 'Off — originals served', $problems === 0
                ? 'All ' . $copies . ' ' . $are . ' up to date and ready; turn ' . $group['full_label'] . ' on to serve them.'
                : $problems . ' of ' . $copies . ' ' . ($one ? 'needs a rebuild before it' : 'need a rebuild before they') . ' can be served.'];
        } elseif ($problems > 0) {
            $status = [$group['missing'] > 0 ? 'bad' : 'warn', $group['missing'] > 0 ? 'fa-circle-xmark' : 'fa-triangle-exclamation',
                $problems . ' of ' . $copies . ' ' . ($one ? 'needs' : 'need') . ' a rebuild',
                ($one ? 'The original of ' . $problemList . ' is served until it is rebuilt.' : 'The originals of ' . $problemList . ' are served until they are rebuilt.')];
        } else {
            $status = ['good', 'fa-circle-check', 'All ' . $copies . ' ' . $are . ' up to date', ''];
        }
        if (!$canBuild && $count > 0) {
            $status[3] = trim($status[3] . ' Copies are built on a developer machine and committed to the site.');
        }
        $group['status'] = [
            'state' => $status[0],
            'icon' => $status[1],
            'label' => $status[2],
            // The newest copy's date is a checkout time after a deploy, so it is never called a build time.
            'value' => $group['newest'] > 0 ? 'Newest copy ' . date($format, $group['newest']) : '',
            'value_title' => $group['newest'] > 0 ? self::ago($group['newest'], $now) : '',
            'detail' => $status[3],
        ];
        $group['build_label'] = $group['label'] . ' now';
        $group['filters'] = [
            ['key' => 'all', 'label' => 'All', 'count' => $count, 'disabled' => false, 'title' => ''],
            ['key' => 'fresh', 'label' => 'Up to date', 'count' => $fresh, 'disabled' => $fresh === 0, 'title' => $fresh === 0 ? 'No copies are up to date' : ''],
            ['key' => 'rebuild', 'label' => 'Needs rebuild', 'count' => $problems, 'disabled' => $problems === 0, 'title' => $problems === 0 ? 'Every copy is up to date' : ''],
        ];
    }

    /** "5 min ago" style age for a timestamp; empty when it is in the future. */
    private static function ago(int $timestamp, int $now): string
    {
        $seconds = $now - $timestamp;
        if ($seconds < 0) {
            return '';
        }
        if ($seconds < 60) {
            return 'just now';
        }
        foreach ([[86400 * 30, 'month'], [86400 * 7, 'week'], [86400, 'day'], [3600, 'hour'], [60, 'min']] as [$size, $unit]) {
            if ($seconds >= $size) {
                $amount = (int) floor($seconds / $size);

                return $amount . ' ' . $unit . ($amount === 1 || $unit === 'min' ? '' : 's') . ' ago';
            }
        }

        return 'just now';
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
            ['id' => 'initial', 'label' => 'Initial'],
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
