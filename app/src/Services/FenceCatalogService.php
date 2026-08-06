<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Fence style/color/height-tier resolution against the planner catalog
 * (config/helpers.php migration).
 */
final class FenceCatalogService
{
    /**
     * Match JS `normalizeFenceStyleSlug` for planner section keys vs catalog.
     */
    public static function normalizePlannerFenceSlug(string $slug): string
    {
        return $slug === 'slat_fence' ? 'slat' : $slug;
    }

    /**
     * Human-readable fence style label for cart / item list (matches planner `fc_data[slug].title`).
     *
     * @param array<string, mixed>|null $fences Fence catalog from `writable/settings.php`.
     */
    public static function styleTitleFromSlug(string $slug, ?array $fences = null): string
    {
        if ($fences === null && isset($GLOBALS['fences'])) {
            $fences = $GLOBALS['fences'];
        }
        if (!is_array($fences)) {
            $fences = [];
        }

        $raw = trim($slug);
        $norm = self::normalizePlannerFenceSlug($raw);

        foreach ([$norm, $raw] as $key) {
            if ($key === '' || !isset($fences[$key]) || !is_array($fences[$key])) {
                continue;
            }
            $row = $fences[$key];
            if (!empty($row['title'])) {
                return (string) $row['title'];
            }
            if (!empty($row['name'])) {
                return (string) $row['name'];
            }
        }

        if ($raw === '') {
            return '';
        }

        return ucwords(str_replace(['_', '-'], ' ', $raw));
    }

    /**
     * Fence style label for a cart line (prefers stored title from `post_product_skus`).
     *
     * @param array<string, mixed> $cartItem Cart row from `$_SESSION['fc_cart']['items']`.
     * @param array<string, mixed>|null $fences Fence catalog.
     */
    public static function cartItemFenceStyleLabel(array $cartItem, ?array $fences = null): string
    {
        if (!empty($cartItem['fence_style_title'])) {
            return (string) $cartItem['fence_style_title'];
        }
        if (empty($cartItem['fence'])) {
            return '';
        }

        return self::styleTitleFromSlug($cartItem['fence'], $fences);
    }

    /**
     * Parse cart row id from localStorage (`flat_top-0`, `barr-1`).
     *
     * @return array{fence:string,section:int|null}
     */
    public static function parseCartRowKey(string $cartItemKey): array
    {
        if (preg_match('/-(\d+)$/', $cartItemKey, $m)) {
            $fence = substr($cartItemKey, 0, -strlen($m[0]));

            return [
                'fence' => self::normalizePlannerFenceSlug($fence),
                'section' => (int) $m[1],
            ];
        }

        return [
            'fence' => self::normalizePlannerFenceSlug($cartItemKey),
            'section' => null,
        ];
    }

    /**
     * Resolve colour for a planner section (per-section row first, then by fence style).
     *
     * @param array<int|string, mixed> $colors
     */
    public static function resolvePlannerCartFenceColor(string $fenceSlug, ?int $sectionIndex, mixed $colors): string
    {
        if (!is_array($colors)) {
            return '';
        }

        $norm = self::normalizePlannerFenceSlug($fenceSlug);

        if ($sectionIndex !== null && isset($colors[$sectionIndex]) && is_array($colors[$sectionIndex])) {
            $rowFence = self::normalizePlannerFenceSlug((string) ($colors[$sectionIndex]['fence'] ?? ''));
            if ($rowFence === $norm && !empty($colors[$sectionIndex]['color'])) {
                return (string) $colors[$sectionIndex]['color'];
            }
        }

        foreach ($colors as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowFence = self::normalizePlannerFenceSlug((string) ($row['fence'] ?? ''));
            if ($rowFence === $norm && !empty($row['color'])) {
                return (string) $row['color'];
            }
        }

        return '';
    }

    /**
     * Default colour column for SKU lookup when session colour rows are missing.
     */
    public static function defaultColorForFenceSlug(string $fenceSlug): string
    {
        global $fences;

        $norm = self::normalizePlannerFenceSlug($fenceSlug);
        if (isset($fences) && is_array($fences) && isset($fences[$norm]['color']) && is_array($fences[$norm]['color'])) {
            $first = reset($fences[$norm]['color']);
            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        return 'black';
    }

    /**
     * Merge section cart BOM lines into fence+colour buckets for `CartBuilderService::getProductSkus()`.
     *
     * @param array<int|string, mixed> $cartItemsGrouped
     * @param array<int|string, mixed> $colors
     * @return array<string, array<string, mixed>>
     */
    public static function regroupPlannerCartItemsForSkus(mixed $cartItemsGrouped, mixed $colors): array
    {
        $regrouped = [];

        if (!is_array($cartItemsGrouped)) {
            return $regrouped;
        }

        foreach ($cartItemsGrouped as $cartItem) {
            if (!is_array($cartItem)) {
                continue;
            }
            foreach ($cartItem as $cartItemKey => $ciItems) {
                if (!is_array($ciItems)) {
                    continue;
                }

                $parsed = self::parseCartRowKey((string) $cartItemKey);
                $color = self::resolvePlannerCartFenceColor($parsed['fence'], $parsed['section'], $colors);
                if ($color === '') {
                    $color = self::defaultColorForFenceSlug($parsed['fence']);
                }
                if ($color === '') {
                    continue;
                }

                $bucketId = $parsed['fence'] . '+' . $color;
                foreach ($ciItems as $ciV) {
                    if (empty($ciV['slug'])) {
                        continue;
                    }

                    if (!empty($ciV['optional'])) {
                        $suggested = (int) ($ciV['suggested_qty'] ?? 0);
                        if ($suggested <= 0) {
                            continue;
                        }
                        $slug = (string) $ciV['slug'];
                        if (
                            !isset($regrouped[$bucketId][$slug])
                            || !is_array($regrouped[$bucketId][$slug])
                            || empty($regrouped[$bucketId][$slug]['optional'])
                        ) {
                            $regrouped[$bucketId][$slug] = [
                                'optional' => true,
                                'qty' => 0,
                                'suggested_qty' => 0,
                            ];
                        }
                        $regrouped[$bucketId][$slug]['suggested_qty'] += $suggested;
                        continue;
                    }

                    $qty = (int) ($ciV['qty'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $regrouped[$bucketId][$ciV['slug']][] = $qty;
                }
            }
        }

        return $regrouped;
    }

    /**
     * Build `CartBuilderService::postProductSkus()` input from regrouped slug/qty buckets.
     *
     * @param array<string, mixed> $regrouped
     * @return array<string, mixed>
     */
    public static function formatRegroupedCartItemsForProductSkus(mixed $regrouped, mixed $fencesJson = null): array
    {
        $cartItemsData = [];

        if (!is_array($regrouped)) {
            return $cartItemsData;
        }

        foreach ($regrouped as $cirK => $cirItems) {
            $cartItemsFormatted = [];
            foreach ($cirItems as $ciriK => $ciriV) {
                if (is_array($ciriV) && !empty($ciriV['optional'])) {
                    $cartItemsFormatted[] = [
                        'slug' => $ciriK,
                        'qty' => 0,
                        'optional' => true,
                        'suggested_qty' => (int) ($ciriV['suggested_qty'] ?? 0),
                    ];
                    continue;
                }
                $cartItemsFormatted[] = [
                    'slug' => $ciriK,
                    'qty' => array_sum((array) $ciriV),
                ];
            }

            $parts = explode('+', (string) $cirK, 2);
            $fenceSlug = $parts[0];
            $maxH = self::plannerMaxFenceHeightMmForFenceSlug($fenceSlug, $fencesJson);

            $cartItemsData[$cirK] = [
                'slug' => $fenceSlug,
                'color' => $parts[1] ?? '',
                'items' => $cartItemsFormatted,
                'max_fence_height_mm' => $maxH,
            ];
        }

        return $cartItemsData;
    }

    /**
     * Sort cart rows: fence style title (A–Z), then product name (A–Z).
     *
     * @param array<int, array<string, mixed>> $cart
     * @param array<string, mixed>|null $fences
     */
    public static function sortCartItemsByFenceStyleAndName(array &$cart, ?array $fences = null): void
    {
        if ($fences === null && isset($GLOBALS['fences'])) {
            $fences = $GLOBALS['fences'];
        }
        if (!is_array($fences)) {
            $fences = [];
        }

        usort(
            $cart,
            function ($a, $b) use ($fences) {
                $styleA = self::cartItemFenceStyleLabel(is_array($a) ? $a : [], $fences);
                $styleB = self::cartItemFenceStyleLabel(is_array($b) ? $b : [], $fences);
                $cmp = strcasecmp($styleA, $styleB);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return strcasecmp(
                    (string) (is_array($a) ? ($a['name'] ?? '') : ''),
                    (string) (is_array($b) ? ($b['name'] ?? '') : '')
                );
            }
        );
    }

    /**
     * Fence types in session `fc_data['fences']`, with section counts (first-seen order).
     *
     * @param array<string, mixed> $fences Global fence catalog.
     * @return array<int, array{slug:string,name:string,count:int}>
     */
    public static function fenceSectionTypesWithCounts(array $fences): array
    {
        $info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
        if (empty($info['fences'])) {
            return [];
        }
        $rows = CartBuilderService::convertInputs($info['fences']);
        if (!is_array($rows)) {
            return [];
        }
        $order = [];
        $counts = [];
        foreach ($rows as $fence) {
            if (!is_array($fence) || empty($fence['form']) || !isset($fence['form'][0]) || !is_array($fence['form'][0])) {
                continue;
            }
            $tab0 = $fence['form'][0];
            $raw = '';
            if (!empty($tab0['fence'])) {
                $raw = $tab0['fence'];
            } elseif (!empty($tab0['style'])) {
                $raw = $tab0['style'];
            }
            if ($raw === '' || $raw === null) {
                continue;
            }
            $norm = self::normalizePlannerFenceSlug($raw);
            if (!isset($counts[$norm])) {
                $counts[$norm] = 0;
                $order[] = $norm;
            }
            $counts[$norm]++;
        }
        $out = [];
        foreach ($order as $norm) {
            $name = '';
            if (isset($fences[$norm]['name']) && (string) $fences[$norm]['name'] !== '') {
                $name = $fences[$norm]['name'];
            } elseif (isset($fences[$norm]['title']) && (string) $fences[$norm]['title'] !== '') {
                $name = $fences[$norm]['title'];
            } else {
                $name = $norm;
            }
            $out[] = [
                'slug' => $norm,
                'name' => $name,
                'count' => $counts[$norm],
            ];
        }

        return $out;
    }

    /**
     * How many planner sections use this fence style (session `fc_data['fences']`), slug-normalized
     * like {@see self::fenceSectionTypesWithCounts()}.
     */
    public static function plannerSectionCountForFenceSlug(string $slug): int
    {
        $info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
        if (empty($info['fences'])) {
            return 0;
        }
        $rows = CartBuilderService::convertInputs($info['fences']);
        if (!is_array($rows)) {
            return 0;
        }
        $target = self::normalizePlannerFenceSlug($slug);
        $n = 0;
        foreach ($rows as $fence) {
            if (!is_array($fence) || empty($fence['form']) || !isset($fence['form'][0]) || !is_array($fence['form'][0])) {
                continue;
            }
            $tab0 = $fence['form'][0];
            $raw = '';
            if (!empty($tab0['fence'])) {
                $raw = $tab0['fence'];
            } elseif (!empty($tab0['style'])) {
                $raw = $tab0['style'];
            }
            if ($raw === '' || $raw === null) {
                continue;
            }
            $norm = self::normalizePlannerFenceSlug($raw);
            if ($norm === $target) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Maps fence planner slug to products.csv STYLE column (see writable/products.csv).
     */
    public static function productsCsvStyleForFence(string $fenceSlug): string
    {
        if ($fenceSlug === 'slat_fence_infill') {
            return 'slat_infill';
        }
        if ($fenceSlug === 'slat_fence') {
            return 'slat';
        }

        return $fenceSlug;
    }

    /**
     * Main Slat: stock post length tier (mm) from Step 2 Fence Height.
     * <=1800 → 1800, <=2400 → 2400, <=2700 → 2700, else 6000.
     */
    public static function slatPostHeightTierMm(int $fenceHeightMm): int
    {
        $h = $fenceHeightMm;
        if ($h <= 0) {
            return 2400;
        }
        if ($h <= 1800) {
            return 1800;
        }
        if ($h <= 2400) {
            return 2400;
        }
        if ($h <= 2700) {
            return 2700;
        }

        return 6000;
    }

    public static function slatPostCatalogSlugFromFenceHeightMm(int $fenceHeightMm): string
    {
        $tier = self::slatPostHeightTierMm($fenceHeightMm);

        return 'slat_post+50x50_' . $tier;
    }

    /**
     * Read max_fence_height from a planner tab row (`custom_fence-{n}` shape).
     *
     * @param array<string, mixed>|mixed $row
     */
    public static function readMaxFenceHeightMmFromFormRow(mixed $row): int
    {
        if (!is_array($row)) {
            return 0;
        }
        $tryStyleKeys = ['slat', 'slat_fence'];
        $fbs = isset($row['fieldsByStyle']) && is_array($row['fieldsByStyle']) ? $row['fieldsByStyle'] : [];
        foreach ($tryStyleKeys as $sk) {
            if (empty($fbs[$sk]) || !is_array($fbs[$sk])) {
                continue;
            }
            foreach ($fbs[$sk] as $f) {
                if (!is_array($f)) {
                    continue;
                }
                if (($f['name'] ?? '') === 'max_fence_height' && ($f['value'] ?? '') !== '') {
                    return (int) $f['value'];
                }
            }
        }
        if (!empty($row['fields']) && is_array($row['fields'])) {
            foreach ($row['fields'] as $f) {
                if (!is_array($f)) {
                    continue;
                }
                if (($f['name'] ?? '') === 'max_fence_height' && ($f['value'] ?? '') !== '') {
                    return (int) $f['value'];
                }
            }
        }

        return 0;
    }

    /**
     * Highest Step 2 Fence Height (mm) among planner sections for main Slat (`slat` / `slat_fence`).
     */
    public static function plannerMaxFenceHeightMmForFenceSlug(string $fenceSlug, mixed $fencesJson = null): int
    {
        $wantStyle = self::productsCsvStyleForFence($fenceSlug);
        if ($wantStyle !== 'slat') {
            return 0;
        }
        if ($fencesJson === null) {
            $fencesJson = isset($_SESSION['fc_data']['fences']) ? $_SESSION['fc_data']['fences'] : '[]';
        }
        $sections = is_string($fencesJson) ? json_decode($fencesJson, true) : $fencesJson;
        if (!is_array($sections)) {
            return 0;
        }
        $max = 0;
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $form = $section['form'] ?? [];
            $row = (is_array($form) && isset($form[0])) ? $form[0] : [];
            $rowStyle = self::productsCsvStyleForFence((string) ($row['style'] ?? $row['fence'] ?? ''));
            if ($rowStyle !== 'slat') {
                continue;
            }
            $h = self::readMaxFenceHeightMmFromFormRow($row);
            if ($h > $max) {
                $max = $h;
            }
        }

        return $max;
    }

    /**
     * Maps planner/cart line slugs (e.g. panel_post+opt-1) to products.csv SLUG for main Slat.
     * Post row follows Fence Height tiers (1800 / 2400 / 2700 / 6000 mm stock lengths).
     *
     * Slat Infill: FSQ infill mode uses no fence posts — do not map to slat_post+*.
     *
     * @param int|null $fenceHeightMm Step 2 Fence Height; resolved from session when omitted.
     */
    public static function slatCatalogSlugForPlannerLine(
        string $itemSlug,
        string $fenceSlug,
        mixed $colorColumnKey,
        ?int $fenceHeightMm = null
    ): string {
        $style = self::productsCsvStyleForFence($fenceSlug);
        if ($style !== 'slat' && $style !== 'slat_infill') {
            return $itemSlug;
        }
        if ($style === 'slat_infill') {
            return $itemSlug;
        }
        $height = $fenceHeightMm !== null ? $fenceHeightMm : 0;
        if ($height <= 0) {
            $height = self::plannerMaxFenceHeightMmForFenceSlug($fenceSlug);
        }
        $postCat = self::slatPostCatalogSlugFromFenceHeightMm($height);
        if (preg_match('/^panel_post\+opt-(\d+(?:-\d+)?)(?:\+(\d+))?$/', $itemSlug)) {
            return $postCat;
        }
        if (preg_match('/^raked_post\+opt-(\d+(?:-\d+)?)(?:\+(\d+))?$/', $itemSlug)) {
            return $postCat;
        }

        return $itemSlug;
    }
}
