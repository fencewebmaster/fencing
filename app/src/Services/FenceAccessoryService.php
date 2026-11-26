<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\StoreProductModel;

/**
 * A fence style's accessory lines (concrete, grout, glue, gun, fixings, screws) as the Fence Styles editor's
 * Accessories rows. The rows here are the rules the planner has always billed them by; a style's fence file keeps
 * only the rows changed there, under `accessories`. The planner reads those over the same rules written at each
 * line in its own scripts (FENCES.cartItems.accessoryRule() in shared/cart-items.js and its callers), so a default
 * changed here has to change there too.
 */
final class FenceAccessoryService
{
    /** The setting in the fence file: [accessory key] => ['status' => ..., 'qty' => ..., 'per' => ...]. */
    public const SETTING_KEY = 'accessories';

    /** included = billed, optional = listed with a suggested quantity and an "Add to cart" button, off = never listed. */
    public const STATUSES = ['included', 'optional', 'off'];

    private const MAX_COUNT = 999;

    /**
     * A line counted from posts or spigots: `qty` for every `per` of them.
     *
     * @return array<string, mixed>
     */
    private static function perUnit(string $title, string $slug, string $follows, string $unit, string $status, int $per = 1, string $round = 'up'): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'follows' => $follows,
            'basis' => 'unit',
            'unit' => $unit,
            'status' => $status,
            'qty' => 1,
            'per' => $per,
            'round' => $round,
        ];
    }

    /**
     * A line with a set quantity, once per job or once per fence section.
     *
     * @return array<string, mixed>
     */
    private static function flat(string $title, string $slug, string $follows, string $scope, string $status): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'follows' => $follows,
            'basis' => 'flat',
            'scope' => $scope,
            'status' => $status,
            'qty' => 1,
        ];
    }

    /**
     * Every style's rows, in the order the editor lists them. Glass Pool's grout is the one line rounded to the
     * nearest, as the planner always did: under 4 spigots suggests none.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function table(): array
    {
        return [
            'flat_top' => [
                'dynabolts' => self::perUnit('Base plate fixings', 'base_plate+dynabolts', 'Base Plated posts', 'posts', 'included'),
            ],
            'barr' => [
                'dynabolts' => self::perUnit('Base plate fixings', 'base_plate+dynabolts', 'Base Plated posts', 'posts', 'optional'),
            ],
            'glass_pool' => [
                'fixings' => self::perUnit('Concrete fixing kit', 'fixings_stone', 'Base Plated spigots', 'spigots', 'optional'),
                'glue' => self::flat('Chemical anchor glue', 'chem_achor+glue', 'Base Plated spigots', 'section', 'optional'),
                'glue_gun' => self::flat('Glue gun', 'chem_achor+glue_gun', 'Base Plated spigots', 'section', 'optional'),
                'grout' => self::perUnit('Grout', 'grout', 'Core Drilled spigots', 'spigots', 'optional', 8, 'nearest'),
            ],
            'slat' => [
                'cement' => self::perUnit('Concrete', 'slat_fixings+cement', 'Cement In posts', 'posts', 'optional'),
                'thread_rods' => self::perUnit('Concrete fixing kit', 'slat_fixings+thread_rods', 'Base Plated and Wall Fix posts', 'posts', 'optional'),
                'glue' => self::flat('Chemical anchor glue', 'slat_fixings+glue_tube', 'Base Plated posts', 'section', 'optional'),
                'glue_gun' => self::flat('Glue gun', 'slat_fixings+glue_gun', 'Base Plated posts', 'section', 'optional'),
                'grout' => self::perUnit('Grout', 'slat_fixings+grout', 'Core Drilled posts', 'posts', 'included', 11),
                'screws' => self::flat('Wafer screws', 'slat_gate+qsg_screws', 'Every job', 'job', 'included'),
            ],
            'slat_fence_infill' => [
                'screws' => self::flat('Wafer screws', 'slat_gate+qsg_screws', 'Every job', 'job', 'included'),
            ],
            'perforated_pool' => [
                'dynabolts' => self::perUnit('Base plate fixings', 'base_plate+dynabolts', 'Base Plated posts', 'posts', 'included'),
                'cement' => self::perUnit('Concrete', 'cement_in+cement', 'Cement In posts', 'posts', 'optional'),
                'glue' => self::perUnit('Chemical anchor glue', 'chem_achor+glue', 'Base Plated posts', 'posts', 'optional', 5),
                'glue_gun' => self::flat('Glue gun', 'chem_achor+glue_gun', 'Base Plated posts', 'job', 'optional'),
                'screws' => self::flat('Wafer screws', 'panel_options+wafer_screws', 'Every job', 'job', 'included'),
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>> [accessory key] => row; empty for a style with no accessory rows
     */
    public static function defaults(string $styleSlug): array
    {
        return self::table()[$styleSlug] ?? [];
    }

    /**
     * The editor's rows for a style: its defaults, each with the name of the System Products row it sells.
     *
     * @return list<array<string, mixed>>
     */
    public static function editorRows(string $styleSlug): array
    {
        $defaults = self::defaults($styleSlug);
        if ($defaults === []) {
            return [];
        }

        $csvStyle = FenceCatalogService::productsCsvStyleForFence($styleSlug);
        $names = self::productNames($csvStyle);
        $rows = [];
        foreach ($defaults as $key => $row) {
            $rows[] = ['key' => $key] + $row + [
                'product' => $names[$row['slug']] ?? '',
                'productStyle' => $csvStyle,
            ];
        }

        return $rows;
    }

    /**
     * What a fence file may keep under `accessories`: rows of this style that differ from their default, with a known
     * status and whole quantities. Anything else is dropped, so a row put back to its default leaves the file as it was.
     *
     * @return array<string, array<string, int|string>>
     */
    public static function normalize(string $styleSlug, mixed $saved): array
    {
        if (!is_array($saved)) {
            return [];
        }

        $out = [];
        foreach (self::defaults($styleSlug) as $key => $row) {
            $in = $saved[$key] ?? null;
            if (!is_array($in)) {
                continue;
            }

            $status = strtolower(trim((string) ($in['status'] ?? '')));
            if (!in_array($status, self::STATUSES, true)) {
                $status = (string) $row['status'];
            }
            $entry = ['status' => $status, 'qty' => self::count($in['qty'] ?? null, (int) $row['qty'])];
            $changed = $status !== $row['status'] || $entry['qty'] !== $row['qty'];
            if ($row['basis'] === 'unit') {
                $entry['per'] = self::count($in['per'] ?? null, (int) $row['per']);
                $changed = $changed || $entry['per'] !== $row['per'];
            }
            if ($changed) {
                $out[$key] = $entry;
            }
        }

        return $out;
    }

    /**
     * A style config as it may be saved: its `accessories` normalised in place, or gone when no row differs.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function applyToConfig(string $styleSlug, array $config): array
    {
        if (!array_key_exists(self::SETTING_KEY, $config)) {
            return $config;
        }

        $rows = self::normalize($styleSlug, $config[self::SETTING_KEY]);
        if ($rows === []) {
            unset($config[self::SETTING_KEY]);
        } else {
            $config[self::SETTING_KEY] = $rows;
        }

        return $config;
    }

    private static function count(mixed $value, int $fallback): int
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^\d+$/', trim($value)) === 1)) {
            return $fallback;
        }
        $n = (int) $value;

        return $n >= 1 && $n <= self::MAX_COUNT ? $n : $fallback;
    }

    /**
     * System Products names for one style, by slug: the first row that has one.
     *
     * @return array<string, string>
     */
    private static function productNames(string $csvStyle): array
    {
        $load = StoreProductModel::all();
        if (empty($load['ok'])) {
            return [];
        }

        $names = [];
        foreach ($load['rows'] as $row) {
            $slug = trim((string) ($row['SLUG'] ?? ''));
            $name = trim((string) ($row['PRODUCT'] ?? ''));
            if ($slug === '' || $name === '' || isset($names[$slug]) || trim((string) ($row['STYLE'] ?? '')) !== $csvStyle) {
                continue;
            }
            $names[$slug] = $name;
        }

        return $names;
    }
}
