<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\FenceCatalogService;
use Fc\Admin\Services\FenceColorSettings;

/**
 * Dashboard row shaping — pure, DB-free formatting/view-model helpers, plus the
 * page-level pageData() orchestrator. Mirrors StoreProductPresenter / FenceStylePresenter:
 * pure computation lives here, all I/O lives in DashboardModel.
 */
final class DashboardPresenter
{
    public static function formatCustomerAddress(string $address = '', string $postcode = '', string $state = ''): string
    {
        $parts = [];
        $address = trim($address);
        if ($address !== '') {
            $parts[] = $address;
        }

        $locality = trim(trim($postcode) . (trim($postcode) !== '' && trim($state) !== '' ? ' ' : '') . trim($state));
        if ($locality !== '') {
            $parts[] = $locality;
        }

        return implode(', ', $parts);
    }

    /**
     * Resolve planner app base path (parent of /public) for asset URLs.
     */
    public static function resolveAppBase(): string
    {
        $adminBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');

        return rtrim(str_replace('\\', '/', dirname($adminBase)), '/');
    }

    /**
     * Featured image URL for a fence style slug from the catalog.
     *
     * @param array<string, array<string, mixed>> $fences
     */
    public static function fenceStyleImageUrl(string $slug, array $fences, string $appBase = ''): string
    {
        $norm = FenceCatalogService::normalizePlannerFenceSlug(trim($slug));
        $info = null;
        foreach ([$norm, trim($slug)] as $key) {
            if ($key !== '' && isset($fences[$key]) && is_array($fences[$key])) {
                $info = $fences[$key];
                break;
            }
        }
        if ($info === null) {
            return '';
        }

        $imagePath = isset($info['image']) ? trim(str_replace('\\', '/', (string) $info['image'])) : '';
        $imagePath = ltrim($imagePath, '/');
        if ($imagePath === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $imagePath)) {
            return $imagePath;
        }

        $base = $appBase !== '' ? $appBase : self::resolveAppBase();

        return rtrim($base, '/') . '/' . $imagePath;
    }

    /**
     * Resolve colour display meta for dashboard insights.
     *
     * @return array{label:string,slug:string,swatch:string,color:string}
     */
    public static function colourMeta(string $slugOrLabel): array
    {
        $raw = trim($slugOrLabel);
        $slug = strtolower(str_replace([' ', '-'], '_', $raw));
        $fallbackLabel = self::colourHumanizeSlug($raw);

        $meta = [
            'label' => $fallbackLabel,
            'slug' => $raw !== '' ? $raw : '',
            'swatch' => '#94a3b8',
            'color' => '#94a3b8',
        ];

        if ($raw === '') {
            $meta['label'] = '—';

            return $meta;
        }

        foreach (FenceColorSettings::get() as $item) {
            if (!is_array($item)) {
                continue;
            }
            $itemSlug = strtolower((string) ($item['slug'] ?? ''));
            if ($itemSlug === '' || $itemSlug !== $slug) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $sub = trim((string) ($item['subLabel'] ?? ''));
            if ($label !== '' && $sub !== '') {
                $meta['label'] = $label . ' ' . $sub;
            } elseif ($label !== '') {
                $meta['label'] = $label;
            }

            $meta['slug'] = (string) ($item['slug'] ?? $raw);
            $swatch = trim((string) FenceColorSettings::background($item));
            if ($swatch !== '') {
                $meta['swatch'] = $swatch;
            }

            $solid = trim((string) ($item['color'] ?? ''));
            if ($solid !== '' && !preg_match('#^url\(#i', $solid) && stripos($solid, 'gradient') === false) {
                $meta['color'] = $solid;
            } elseif ($swatch !== '' && !preg_match('#^url\(#i', $swatch) && stripos($swatch, 'gradient') === false) {
                $meta['color'] = $swatch;
            }

            return $meta;
        }

        return $meta;
    }

    /**
     * Fallback humanize: pearl_white_gloss → Pearl White Gloss.
     */
    private static function colourHumanizeSlug(string $slugOrLabel): string
    {
        $raw = trim($slugOrLabel);
        if ($raw === '') {
            return '—';
        }

        $human = str_replace(['_', '-'], ' ', $raw);
        $human = preg_replace('/\s+/', ' ', $human) ?? $human;
        $human = trim($human);
        if ($human === '') {
            return $raw;
        }

        return ucwords(strtolower($human));
    }

    public static function parseBrowser(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'samsungbrowser/')) {
            return 'Samsung Internet';
        }
        if (str_contains($ua, 'opr/') || str_contains($ua, 'opera')) {
            return 'Opera';
        }
        if (
            str_contains($ua, 'edg/')
            || str_contains($ua, 'edge/')
            || str_contains($ua, 'edga/')
            || str_contains($ua, 'edgios/')
        ) {
            return 'Edge';
        }
        if (str_contains($ua, 'firefox/') || str_contains($ua, 'fxios/')) {
            return 'Firefox';
        }
        if (
            (str_contains($ua, 'chrome/') && !str_contains($ua, 'chromium'))
            || str_contains($ua, 'crios/')
        ) {
            return 'Chrome';
        }
        if (str_contains($ua, 'safari/')) {
            return 'Safari';
        }

        return 'Other';
    }

    /**
     * Fixed color designations for supported device and browser combinations.
     *
     * @return array<string, array{label:string,color:string,color_name:string}>
     */
    public static function deviceBrowserDesignations(): array
    {
        return [
            'desktop_chrome' => ['label' => 'Desktop Chrome', 'color' => '#facc15', 'color_name' => 'Yellow'],
            'desktop_edge' => ['label' => 'Desktop Edge', 'color' => '#22c55e', 'color_name' => 'Green'],
            'desktop_firefox' => ['label' => 'Desktop Firefox', 'color' => '#f97316', 'color_name' => 'Orange'],
            'desktop_safari' => ['label' => 'Desktop Safari', 'color' => '#3b82f6', 'color_name' => 'Blue'],
            'desktop_opera' => ['label' => 'Desktop Opera', 'color' => '#a855f7', 'color_name' => 'Purple'],
            'ipad_safari' => ['label' => 'iPad Safari', 'color' => '#ec4899', 'color_name' => 'Pink'],
            'ipad_chrome' => ['label' => 'iPad Chrome', 'color' => '#06b6d4', 'color_name' => 'Cyan'],
            'ipad_firefox' => ['label' => 'iPad Firefox', 'color' => '#92400e', 'color_name' => 'Brown'],
            'android_tablet_chrome' => ['label' => 'Tablet (Android) Chrome', 'color' => '#ef4444', 'color_name' => 'Red'],
            'android_tablet_firefox' => ['label' => 'Tablet (Android) Firefox', 'color' => '#8b5cf6', 'color_name' => 'Violet'],
            'android_tablet_edge' => ['label' => 'Tablet (Android) Edge', 'color' => '#84cc16', 'color_name' => 'Lime'],
            'android_mobile_chrome' => ['label' => 'Mobile (Android) Chrome', 'color' => '#111111', 'color_name' => 'Black'],
            'android_mobile_firefox' => ['label' => 'Mobile (Android) Firefox', 'color' => '#6b7280', 'color_name' => 'Gray'],
            'android_mobile_samsung_internet' => ['label' => 'Mobile (Android) Samsung Internet', 'color' => '#4a2c1a', 'color_name' => 'Dark Brown'],
            'iphone_mobile_safari' => ['label' => 'Mobile (iPhone) Safari', 'color' => '#14b8a6', 'color_name' => 'Teal'],
            'iphone_mobile_chrome' => ['label' => 'Mobile (iPhone) Chrome', 'color' => '#38bdf8', 'color_name' => 'Sky Blue'],
            'iphone_mobile_firefox' => ['label' => 'Mobile (iPhone) Firefox', 'color' => '#c4b5fd', 'color_name' => 'Lavender'],
        ];
    }

    /**
     * @return array{key:string,label:string,color:string,color_name:string}|null
     */
    public static function deviceBrowserCombination(string $ua): ?array
    {
        $normalizedUa = strtolower($ua);
        if (
            $normalizedUa === ''
            || preg_match('/(?:bot|crawler|spider|slurp|headlesschrome)/i', $normalizedUa) === 1
        ) {
            return null;
        }

        $browser = self::parseBrowser($ua);
        if ($browser === 'Other') {
            return null;
        }

        if (str_contains($normalizedUa, 'ipad')) {
            $deviceKey = 'ipad';
        } elseif (str_contains($normalizedUa, 'iphone') || str_contains($normalizedUa, 'ipod')) {
            $deviceKey = 'iphone_mobile';
        } elseif (str_contains($normalizedUa, 'android')) {
            $deviceKey = str_contains($normalizedUa, 'mobile') ? 'android_mobile' : 'android_tablet';
        } else {
            $deviceKey = 'desktop';
        }

        $browserKey = strtolower(str_replace(' ', '_', $browser));
        $key = $deviceKey . '_' . $browserKey;
        $designations = self::deviceBrowserDesignations();
        if (!isset($designations[$key])) {
            return null;
        }

        return ['key' => $key] + $designations[$key];
    }

    public static function parseOs(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'mac os x') || str_contains($ua, 'macintosh')) {
            return 'macOS';
        }
        if (str_contains($ua, 'android')) {
            return 'Android';
        }
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios')) {
            return 'iOS';
        }
        if (str_contains($ua, 'linux')) {
            return 'Linux';
        }

        return 'Other';
    }

    /**
     * @param array<string, mixed>|null $query Optional request query (defaults to $_GET).
     * @return array<string, mixed>
     */
    public static function pageData(string $adminBase, string $appBase, ?array $query = null): array
    {
        // KPIs only on initial HTML — system/health/recent load via charts/API when needed.
        $summary = DashboardModel::summaryStats();

        $entriesBase = PlannerEntryPresenter::listPath($adminBase);
        $today = (new \DateTime('now'))->format('Y-m-d');

        $dashboardFilter = DashboardModel::resolveDateFilterFromQuery($query ?? $_GET);

        $widgetsVisible = GroupPermissionsPresenter::dashboardWidgetsVisible();

        return [
            'ok' => !empty($summary['ok']),
            'summary' => $summary,
            'system' => ['ok' => true],
            'health' => ['ok' => true],
            'recent_entries' => [],
            'widgets_visible' => $widgetsVisible,
            'links' => [
                'entries' => $entriesBase,
                'entries_today' => $entriesBase . '?date_period=today',
                'entries_week' => $entriesBase . '?date_period=this_week',
                'entries_month' => $entriesBase . '?date_period=this_month',
                'gallery' => rtrim($adminBase, '/') . '/gallery',
                'settings' => rtrim($adminBase, '/') . '/settings',
                'fence_styles' => rtrim($adminBase, '/') . '/products/fence-styles',
                'store_products' => rtrim($adminBase, '/') . '/products/store-products',
                'system_products' => rtrim($adminBase, '/') . '/products/system-products',
                'planner_app' => rtrim($appBase, '/') . '/',
            ],
            'quick_actions' => [
                ['label' => 'View entries', 'icon' => 'fa-list', 'href' => $entriesBase, 'route' => 'planner-entries'],
                ['label' => 'Media library', 'icon' => 'fa-images', 'href' => rtrim($adminBase, '/') . '/gallery', 'route' => 'gallery'],
                ['label' => 'Store products', 'icon' => 'fa-box', 'href' => rtrim($adminBase, '/') . '/products/store-products', 'route' => 'products/store-products'],
                ['label' => 'Fence styles', 'icon' => 'fa-border-all', 'href' => rtrim($adminBase, '/') . '/products/fence-styles', 'route' => 'products/fence-styles'],
                ['label' => 'Settings', 'icon' => 'fa-gear', 'href' => rtrim($adminBase, '/') . '/settings', 'route' => 'settings'],
                ['label' => 'Open planner', 'icon' => 'fa-compass-drafting', 'href' => rtrim($appBase, '/') . '/', 'external' => true],
            ],
            'shortcuts' => [
                ['label' => "Today's entries", 'href' => $entriesBase . '?date_period=today'],
                ['label' => 'This week', 'href' => $entriesBase . '?date_period=this_week'],
                ['label' => 'Search entries', 'href' => $entriesBase],
                ['label' => 'Export entries', 'href' => $entriesBase . '?per_page=500'],
            ],
            'au_states' => ['NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT', 'ACT'],
            'date_period' => (string) ($dashboardFilter['period'] ?? ''),
            'date_from' => (string) ($dashboardFilter['from'] ?? ''),
            'date_to' => (string) ($dashboardFilter['to'] ?? ''),
            'date_filter_label' => (string) ($dashboardFilter['label'] ?? 'All dates'),
            'date_period_options' => PlannerEntryPresenter::datePeriodOptions(),
            'generated_at' => $today,
            'api_url' => rtrim($adminBase, '/') . '/api.php?module=dashboard',
        ];
    }
}
