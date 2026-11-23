<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Settings\FenceColorSettings;

/**
 * Fence style/color/height-tier resolution against the planner catalog.
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
     * @param array<string, mixed>|null $fences Fence catalog from FenceSettingsService.
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
     * "Barr - Black Satin, Slat - Woodland Grey Matt" from planner colour rows — the fence and
     * colour names the project plan shows. The pre-planner webhook and the store push both send it,
     * because the store plugin only ever receives the slugs.
     *
     * @param array<int|string, mixed> $colors Rows of ['fence' => slug, 'color' => slug].
     * @param array<string, mixed>|null $fences Fence catalog.
     */
    public static function fenceColorLabels(array $colors, ?array $fences = null): string
    {
        $fences = $fences ?? FenceSettingsService::fences();
        $colorNames = FenceColorSettings::legacyMap();

        $labels = [];
        foreach ($colors as $row) {
            if (!is_array($row)) {
                continue;
            }
            $fence = trim((string) ($row['fence'] ?? ''));
            $color = trim((string) ($row['color'] ?? ''));
            if ($fence === '' && $color === '') {
                continue;
            }
            $colorLabel = isset($colorNames[$color])
                ? trim($colorNames[$color]['title'] . ' ' . $colorNames[$color]['sub_title'])
                : ucwords(str_replace(['_', '-'], ' ', $color));
            $labels[] = implode(' - ', array_filter(
                [self::styleTitleFromSlug($fence, $fences), $colorLabel],
                static fn(string $part): bool => $part !== ''
            ));
        }

        return implode(', ', array_unique($labels));
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
     * @param mixed $fencesJson Planner sections (`fc_data['fences']`); the session's when omitted. A Slat post line reads its gate from them.
     * @return array<string, array<string, mixed>>
     */
    public static function regroupPlannerCartItemsForSkus(mixed $cartItemsGrouped, mixed $colors, mixed $fencesJson = null): array
    {
        $regrouped = [];

        if (!is_array($cartItemsGrouped)) {
            return $regrouped;
        }

        $slatSections = self::slatSectionPostHeightsMm($fencesJson);

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
                $slatSection = self::productsCsvStyleForFence($parsed['fence']) === 'slat'
                    ? self::slatSectionForCartRow($slatSections, $parsed['section'])
                    : null;
                foreach ($ciItems as $ciV) {
                    if (empty($ciV['slug'])) {
                        continue;
                    }
                    // Slat posts are cut to their own section's height, so each post line carries it (slatPostCutForPlannerLine()).
                    $slug = $slatSection !== null
                        ? self::slatPostSlugForSection((string) $ciV['slug'], $slatSection)
                        : (string) $ciV['slug'];

                    if (!empty($ciV['optional'])) {
                        $suggested = (int) ($ciV['suggested_qty'] ?? 0);
                        if ($suggested <= 0) {
                            continue;
                        }
                        // Keyed apart from a billed line on the same slug: a Slat Gate ONLY section's optional posts
                        // used to replace a fence section's billed ones, or be swallowed by them.
                        $optionalKey = $slug . '|optional';
                        if (!isset($regrouped[$bucketId][$optionalKey])) {
                            $regrouped[$bucketId][$optionalKey] = [
                                'slug' => $slug,
                                'optional' => true,
                                'qty' => 0,
                                'suggested_qty' => 0,
                            ];
                        }
                        $regrouped[$bucketId][$optionalKey]['suggested_qty'] += $suggested;
                        continue;
                    }

                    $qty = (int) ($ciV['qty'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $regrouped[$bucketId][$slug][] = $qty;
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
                        'slug' => (string) ($ciriV['slug'] ?? $ciriK),
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
     * The cutting table for main Slat posts (owner's rule, Nov 2026): [longest post, stock post, pieces]. 1800 first,
     * then 2400, the 6000 only past 2400; no saw allowance. It is followed where a section turns Cut posts on site on
     * (Post Options), and for a post no capped stock post covers, which comes out of a 6000 either way
     * (slatPostRulePick()). No 50x50 post is made in 2700 (the store's are 65x65 steel). The table keeps to 3 pieces at
     * most; a customer can choose more for a picked post, and the Item List note counts the stock posts cut each way
     * (`cut_in_N`, CartBuilderService::slatPostLines()).
     */
    private const SLAT_POST_CUTS = [
        [600, 1800, 3],
        [900, 1800, 2],
        [1200, 2400, 2],
        [1800, 1800, 1],
        [2400, 2400, 1],
        [3000, 6000, 2],
    ];

    /**
     * What a Slat post has in the ground, by post option: [tallest Fence Height, mm], the last row for any height.
     * Cement In (2) was a flat 500, which left a 2000 high fence's 2400 post 100 short; deeper with height is a stopgap
     * from other makers' guides until GO and JG give their own. Core Drilled (4) is the old form's 250.
     */
    private const SLAT_POST_IN_GROUND_MM = [
        2 => [[900, 500], [1200, 550], [1800, 600], [PHP_INT_MAX, 700]],
        4 => [[PHP_INT_MAX, 250]],
    ];

    /**
     * Stock posts the store sells with a press-in top cap, shortest first. The 6000 comes without one: it is a length
     * to cut from, so with cutting off a post is one of these, whole, wherever one is long enough.
     */
    private const SLAT_POST_STOCK_WITH_CAP = [1800, 2400];

    /**
     * The stock posts a customer can pick (Post Options, Post Length) and the most pieces each is cut into unasked: the
     * owner's limits, which SLAT_POST_CUTS above already keeps to. A picked post gives as many pieces as fit, up to
     * these, unless the customer chose a number of cuts for it in the drawer (asked for Nov 2026): any that fits.
     */
    private const SLAT_POST_STOCK_MAX_PIECES = [1800 => 3, 2400 => 2, 6000 => 2];

    public static function slatPostInGroundMm(int $postOption, int $fenceHeightMm): int
    {
        foreach (self::SLAT_POST_IN_GROUND_MM[$postOption] ?? [] as [$tallest, $mm]) {
            if ($fenceHeightMm <= $tallest) {
                return $mm;
            }
        }

        return 0;
    }

    /**
     * The cutting rule as the planner reads it (`fc_data.slat.post_stock`): the Post Length tiles and the Fence Summary
     * work out the recommended post and the ones too short from the same tables the cart bills by. A stock row is
     * [length, most pieces, sold with a cap]; "no limit" is null.
     *
     * @return array{stocks:list<array{0:int,1:int,2:bool}>,cuts:list<array{0:int,1:int,2:int}>,in_ground:array<string,list<array{0:int|null,1:int}>>}
     */
    public static function slatPostStockRule(): array
    {
        $stocks = [];
        foreach (self::SLAT_POST_STOCK_MAX_PIECES as $stock => $pieces) {
            $stocks[] = [$stock, $pieces, in_array($stock, self::SLAT_POST_STOCK_WITH_CAP, true)];
        }
        $inGround = [];
        foreach (self::SLAT_POST_IN_GROUND_MM as $postOption => $rows) {
            $inGround['opt-' . $postOption] = array_map(
                static fn(array $row): array => [$row[0] === PHP_INT_MAX ? null : $row[0], $row[1]],
                $rows
            );
        }

        return ['stocks' => $stocks, 'cuts' => self::SLAT_POST_CUTS, 'in_ground' => $inGround];
    }

    /**
     * The catalogue for a page's `fc_data`, main Slat carrying the cutting rule (slatPostStockRule()) and, as
     * `cut_offered`, whether its drawer may show the Cut posts on site switch (slatPostCutOffered()).
     *
     * @param array<string, array<string, mixed>> $fences
     * @return array<string, array<string, mixed>>
     */
    public static function fencesWithSlatPostStock(array $fences): array
    {
        if (isset($fences['slat']) && is_array($fences['slat'])) {
            $fences['slat']['post_stock'] = self::slatPostStockRule() + ['cut_offered' => self::slatPostCutOffered($fences)];
        }

        return $fences;
    }

    /**
     * Whether main Slat's Post Options offers the Cut posts on site switch: `cut_on_site` on its Post Length field, a
     * site's own choice in Fence Styles (asked for Nov 2026). Off unless set, and a `post_cut` saved while it was on
     * is then not read: the planner hides the switch, so the cart must not bill a choice the customer cannot see.
     *
     * @param array<string, mixed>|null $fences Fence catalog; the loaded one when omitted.
     */
    public static function slatPostCutOffered(?array $fences = null): bool
    {
        $fences = $fences ?? FenceSettingsService::fences();
        $fields = $fences['slat']['settings']['post_options']['fields'] ?? [];
        foreach (is_array($fields) ? $fields : [] as $field) {
            if (is_array($field) && ($field['slug'] ?? '') === 'post_length') {
                return !empty($field['cut_on_site']);
            }
        }

        return false;
    }

    /**
     * The post the rule gives a post length: [stock post, pieces]. With cutting off (the default, asked for Nov 2026)
     * one whole capped post, the shortest that covers it. With cutting on, and for a post no capped one covers, the
     * cutting table; past the table (over 3000) a whole 6000, also where even that is too short.
     *
     * @return array{0:int,1:int}
     */
    private static function slatPostRulePick(int $lengthMm, bool $cut): array
    {
        if (!$cut) {
            foreach (self::SLAT_POST_STOCK_WITH_CAP as $stock) {
                if ($lengthMm <= $stock) {
                    return [$stock, 1];
                }
            }
        }
        foreach (self::SLAT_POST_CUTS as [$longest, $stock, $pieces]) {
            if ($lengthMm <= $longest) {
                return [$stock, $pieces];
            }
        }

        return [6000, 1];
    }

    /**
     * Pieces a picked stock post gives at a post length; 0 where the pick cannot stand (too short, not sold, or a
     * capless 6000 with cutting off, which would have to be cut). Not cut: 1. Cut: the customer's own number of cuts
     * where that many fit (`$wanted`, 2 or more), else as many as fit up to the stock post's limit.
     */
    private static function slatPostPiecesFromStock(int $stock, int $lengthMm, bool $cut, int $wanted = 0): int
    {
        if ($lengthMm <= 0 || !isset(self::SLAT_POST_STOCK_MAX_PIECES[$stock]) || intdiv($stock, $lengthMm) < 1) {
            return 0;
        }
        if (!$cut) {
            return in_array($stock, self::SLAT_POST_STOCK_WITH_CAP, true) ? 1 : 0;
        }
        $fit = intdiv($stock, $lengthMm);

        return $wanted >= 2 && $wanted <= $fit ? $wanted : min(self::SLAT_POST_STOCK_MAX_PIECES[$stock], $fit);
    }

    /**
     * The stock post a main Slat post line comes from: its length, the pieces one gives, its products.csv slug and
     * whether it is sold with a top cap. Null for any other line.
     *
     * The line carries its section's Post Options picks (slatPostSlugForSection()): `+C` where Cut posts on site is on,
     * `+L2400` for a picked Post Length, `+L6000x4` where the customer also chose its number of cuts. A pick is used
     * where it can stand for the post (slatPostPiecesFromStock()); any other is ignored and the rule's own post stands.
     *
     * @param int $fenceHeightMm The job's tallest Slat height, for a line that carries no height of its own (old quotes).
     * @return array{stock:int,pieces:int,slug:string,capped:bool}|null
     */
    public static function slatPostCutForPlannerLine(string $itemSlug, string $fenceSlug, int $fenceHeightMm = 0): ?array
    {
        if (self::productsCsvStyleForFence($fenceSlug) !== 'slat'
            || !preg_match('/^(?:panel_post|raked_post)\+opt-(\d+)(?:-\d+)?(?:\+(\d+))?(\+C)?(?:\+L(\d+)(?:x(\d+))?)?$/', $itemSlug, $m)) {
            return null;
        }

        // An unmatched height group before a matched pick is '', not unset.
        $height = ($m[2] ?? '') !== '' ? (int) $m[2] : $fenceHeightMm;
        if ($height <= 0) {
            $height = self::plannerMaxFenceHeightMmForFenceSlug($fenceSlug);
        }
        $cut = ($m[3] ?? '') !== '';
        $picked = (int) ($m[4] ?? 0);

        // No height at all: a whole 2400, as before.
        $stock = 2400;
        $pieces = 1;
        if ($height > 0) {
            $length = $height + self::slatPostInGroundMm((int) $m[1], $height);
            [$stock, $pieces] = self::slatPostRulePick($length, $cut);
            $pickedPieces = self::slatPostPiecesFromStock($picked, $length, $cut, (int) ($m[5] ?? 0));
            if ($pickedPieces > 0) {
                $stock = $picked;
                $pieces = $pickedPieces;
            }
        } elseif (isset(self::SLAT_POST_STOCK_MAX_PIECES[$picked])) {
            // Nothing to measure the pick against: taken whole.
            $stock = $picked;
        }

        return [
            'stock' => $stock,
            'pieces' => $pieces,
            'slug' => 'slat_post+50x50_' . $stock,
            'capped' => in_array($stock, self::SLAT_POST_STOCK_WITH_CAP, true),
        ];
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
        $max = 0;
        foreach (self::slatSectionPostHeightsMm($fencesJson) as $section) {
            $max = max($max, $section['height'], $section['gate']);
        }

        return $max;
    }

    /**
     * Main Slat sections by position in the plan: Step 2 Fence Height and Gate Height (0 without a gate) in mm, then its
     * Post Options picks: Cut posts on site (off unless switched on, and always off where the site does not offer the
     * switch: slatPostCutOffered()), the Post Length (0 where the rule picks) and the number of cuts chosen for it (0
     * where the stock post's own limit stands).
     *
     * @return array<int, array{height:int,gate:int,cut:bool,stock:int,pieces:int}>
     */
    private static function slatSectionPostHeightsMm(mixed $fencesJson = null): array
    {
        if ($fencesJson === null) {
            $fencesJson = isset($_SESSION['fc_data']['fences']) ? $_SESSION['fc_data']['fences'] : '[]';
        }
        $sections = is_string($fencesJson) ? json_decode($fencesJson, true) : $fencesJson;
        if (!is_array($sections)) {
            return [];
        }
        $cutOffered = self::slatPostCutOffered();
        $out = [];
        foreach (array_values($sections) as $index => $section) {
            if (!is_array($section)) {
                continue;
            }
            $form = $section['form'] ?? [];
            $row = (is_array($form) && isset($form[0])) ? $form[0] : [];
            $rowStyle = self::productsCsvStyleForFence((string) ($row['style'] ?? $row['fence'] ?? ''));
            if ($rowStyle !== 'slat') {
                continue;
            }
            $height = self::readMaxFenceHeightMmFromFormRow($row);
            // A pick is saved with the fence height it was made at ("2400@1787"): at any other height the rule's post
            // stands, as the planner resets it (SlatFence.resetPostLengthForNewHeight()). An older pick has no height.
            // A number of cuts chosen for the post sits between them ("6000x4@1787").
            $pick = explode('@', self::readPostOptionsPick($section, 'post_length'), 2);
            $post = explode('x', isset($pick[1]) && (int) $pick[1] !== $height ? '' : $pick[0], 2);
            $stock = isset(self::SLAT_POST_STOCK_MAX_PIECES[(int) $post[0]]) ? (int) $post[0] : 0;
            $out[$index] = [
                'height' => $height,
                'gate' => self::readGateHeightMmFromSection($section),
                'cut' => $cutOffered && self::readPostOptionsPick($section, 'post_cut') === '1',
                'stock' => $stock,
                'pieces' => $stock > 0 ? (int) ($post[1] ?? 0) : 0,
            ];
        }

        return $out;
    }

    /**
     * The Slat section a cart row (`slat-0`) belongs to. Rows count from 0; a July 2026 quote counts from 1, so the
     * row before is tried where the row itself is not a Slat section.
     *
     * @param array<int, array{height:int,gate:int,cut:bool,stock:int,pieces:int}> $sections
     * @return array{height:int,gate:int,cut:bool,stock:int,pieces:int}|null
     */
    private static function slatSectionForCartRow(array $sections, ?int $index): ?array
    {
        if ($index === null) {
            return null;
        }

        return $sections[$index] ?? ($sections[$index - 1] ?? null);
    }

    /**
     * A Slat post line at the height its section's posts stand: the tallest of the line's own height, the section's
     * Fence Height and its gate. The job's tallest used to lift every post in the job; and a cart saved with another
     * section's height (the old stale-height fault) still gets posts for the fence as drawn. The section's Post Options
     * picks follow: `+C` where Cut posts on site is on, `+L2400` for a picked Post Length (`+L6000x4` with its number of
     * cuts), so its posts stay apart from another section's until they are sized.
     *
     * @param array{height:int,gate:int,cut:bool,stock:int,pieces:int} $section
     */
    private static function slatPostSlugForSection(string $slug, array $section): string
    {
        if (!preg_match('/^((?:panel_post|raked_post)\+opt-\d+(?:-\d+)?)(?:\+(\d+))?$/', $slug, $m)) {
            return $slug;
        }
        $height = max(isset($m[2]) ? (int) $m[2] : 0, $section['height'], $section['gate']);
        $picks = ($section['cut'] ? '+C' : '')
            . ($section['stock'] > 0 ? '+L' . $section['stock'] . ($section['pieces'] > 1 ? 'x' . $section['pieces'] : '') : '');

        return ($height > 0 ? $m[1] . '+' . $height : $slug) . $picks;
    }

    /**
     * One pick from a planner section's Post Options drawer (`post_cut`, `post_length`), as saved; '' where it has none.
     *
     * @param array<string, mixed> $section
     */
    private static function readPostOptionsPick(array $section, string $key): string
    {
        $settings = $section['settings'] ?? [];
        foreach (is_array($settings) ? $settings : [] as $row) {
            if (!is_array($row) || ($row['control_key'] ?? '') !== 'post_options') {
                continue;
            }
            foreach (is_array($row['settings'] ?? null) ? $row['settings'] : [] as $field) {
                if (is_array($field) && ($field['key'] ?? '') === $key) {
                    return trim(str_replace(',', '', (string) ($field['val'] ?? '')));
                }
            }
        }

        return '';
    }

    /**
     * Gate Height from a planner section's gate row; 0 when it has no gate. A gate taller than its fence needs the taller post.
     *
     * @param array<string, mixed> $section
     */
    private static function readGateHeightMmFromSection(array $section): int
    {
        $settings = $section['settings'] ?? [];
        if (!is_array($settings)) {
            return 0;
        }
        foreach ($settings as $row) {
            if (!is_array($row) || ($row['control_key'] ?? '') !== 'gate') {
                continue;
            }
            $fields = $row['settings']['fields'] ?? [];
            foreach (is_array($fields) ? $fields : [] as $field) {
                if (is_array($field) && ($field['key'] ?? '') === 'gate_max_fence_height') {
                    return (int) str_replace(',', '', (string) ($field['val'] ?? ''));
                }
            }
        }

        return 0;
    }

    /**
     * Maps planner/cart line slugs (e.g. panel_post+opt-1) to products.csv SLUG for main Slat.
     * A post line maps to the stock post it is cut from (slatPostCutForPlannerLine()).
     * A spacer line saved as slat_gate+qsg_spacer_{gap} maps to slat_spacer+{gap}, the spacer row again since Nov 2026.
     *
     * Slat Infill: FSQ infill mode uses no fence posts — do not map to slat_post+*.
     *
     * @param int|null $fenceHeightMm The job's tallest Slat height; resolved from session when omitted.
     */
    public static function slatCatalogSlugForPlannerLine(
        string $itemSlug,
        string $fenceSlug,
        mixed $colorColumnKey,
        ?int $fenceHeightMm = null
    ): string {
        // Quotes saved 12-24 Nov 2026 name the gate kit's spacer rows, which were removed: without this their spacers drop.
        if (preg_match('/^slat_gate\+qsg_spacer_(\d+)$/', $itemSlug, $gap) === 1) {
            return 'slat_spacer+' . $gap[1];
        }

        $cut = self::slatPostCutForPlannerLine($itemSlug, $fenceSlug, (int) $fenceHeightMm);

        return $cut !== null ? $cut['slug'] : $itemSlug;
    }
}
