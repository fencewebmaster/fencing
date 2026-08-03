<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

/**
 * Planner entry row shaping — pure, DB-free formatting/view-model helpers for wp_planners rows.
 * No dependency on PlannerEntryModel (kept one-directional: Model depends on this class, not
 * the other way around) so both can be lazily loaded in either order without a circular require.
 */
final class PlannerEntryPresenter
{
    /**
     * Full row columns (detail view).
     *
     * @return list<string>
     */
    public static function entryColumns(): array
    {
        return [
            'id',
            'planner_id',
            'site_id',
            'site_url',
            'status',
            'section_count',
            'notes',
            'name',
            'mobile',
            'email',
            'address',
            'postcode',
            'state',
            'fence_type',
            'timeframe',
            'extra',
            'ip_address',
            'device',
            'user_agent',
            'quote_load_count',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Columns used for portable JSON import/export (full quote restore).
     *
     * @return list<string>
     */
    public static function exportColumns(): array
    {
        return [
            'planner_id',
            'site_id',
            'site_url',
            'order_id',
            'status',
            'status_updated_at',
            'section_count',
            'notes',
            'name',
            'mobile',
            'email',
            'address',
            'postcode',
            'state',
            'fence_type',
            'timeframe',
            'extra',
            'color_data',
            'products_data',
            'fence_data',
            'cart_data',
            'cart_items_data',
            'project_plans_data',
            'ip_address',
            'device',
            'user_agent',
            'quote_load_count',
            'created_at',
            'updated_at',
            'trashed_at',
        ];
    }

    /**
     * Lightweight columns for list/grid (no LONGTEXT blobs).
     *
     * @return list<string>
     */
    public static function listColumns(): array
    {
        return [
            'id',
            'planner_id',
            'status',
            'name',
            'email',
            'mobile',
            'fence_type',
            'timeframe',
            'section_count',
            'quote_load_count',
            'state',
            'device',
            'user_agent',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @param list<string> $columns
     * @return array<string, mixed>
     */
    public static function normalizeRow(array $columns, object $row, bool $withInstaller = true): array
    {
        $out = $withInstaller ? ['installer' => ''] : [];

        foreach ($columns as $column) {
            $value = $row->{$column} ?? null;
            if ($column === 'id' || $column === 'section_count') {
                $out[$column] = isset($row->{$column}) ? (int) $row->{$column} : 0;
                continue;
            }
            $out[$column] = $value === null ? '' : (string) $value;
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fenceCatalog(): array
    {
        static $catalog = null;

        if ($catalog !== null) {
            return $catalog;
        }

        self::ensureHelpersLoaded();

        $fences = [];
        foreach (glob(FC_ROOT . '/data/fences/*.php') ?: [] as $fenceFile) {
            include $fenceFile;
        }

        $catalog = is_array($fences) ? $fences : [];

        return $catalog;
    }

    /**
     * @param mixed $raw
     * @return array<mixed>
     */
    public static function decodeJsonField($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, array<string, mixed>> $fences
     * @return list<array{slug:string,name:string,count:int}>
     */
    public static function fenceSectionTypesFromRows(array $rows, array $fences): array
    {
        self::ensureHelpersLoaded();

        $order = [];
        $counts = [];

        foreach ($rows as $fence) {
            if (!is_array($fence) || empty($fence['form'][0]) || !is_array($fence['form'][0])) {
                continue;
            }

            $tab0 = $fence['form'][0];
            $raw = '';
            if (!empty($tab0['fence'])) {
                $raw = (string) $tab0['fence'];
            } elseif (!empty($tab0['style'])) {
                $raw = (string) $tab0['style'];
            }

            if ($raw === '') {
                continue;
            }

            $norm = fc_normalize_planner_fence_slug($raw);
            if (!isset($counts[$norm])) {
                $counts[$norm] = 0;
                $order[] = $norm;
            }
            $counts[$norm]++;
        }

        $out = [];
        foreach ($order as $norm) {
            $out[] = [
                'slug' => $norm,
                'name' => fc_fence_style_title_from_slug($norm, $fences),
                'count' => (int) $counts[$norm],
            ];
        }

        return $out;
    }

    /**
     * @param array<string, array<string, mixed>> $fences
     * @return list<array{slug:string,name:string,count:int}>
     */
    public static function fenceSectionTypesFromTypeMap(string $fenceTypeRaw, array $fences): array
    {
        self::ensureHelpersLoaded();

        $map = self::decodeJsonField($fenceTypeRaw);
        if ($map === []) {
            return [];
        }

        $order = [];
        $counts = [];

        foreach ($map as $slug => $value) {
            $raw = is_string($slug) && $slug !== '' ? $slug : (string) $value;
            if ($raw === '') {
                continue;
            }

            $norm = fc_normalize_planner_fence_slug($raw);
            if (!isset($counts[$norm])) {
                $counts[$norm] = 0;
                $order[] = $norm;
            }
            $counts[$norm]++;
        }

        $out = [];
        foreach ($order as $norm) {
            $out[] = [
                'slug' => $norm,
                'name' => fc_fence_style_title_from_slug($norm, $fences),
                'count' => (int) $counts[$norm],
            ];
        }

        return $out;
    }

    /**
     * @param list<array{slug:string,name:string,count:int}> $rows
     */
    public static function formatFenceTypeSummary(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $parts = [];
        foreach ($rows as $row) {
            $count = (int) ($row['count'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' || $count <= 0) {
                continue;
            }
            $parts[] = $count . ' × ' . $name;
        }

        return implode("\n", $parts);
    }

    /**
     * Single-line fence type summary (CSV, titles, tooltips).
     */
    public static function formatFenceTypeSummaryInline(string $label): string
    {
        $label = trim(str_replace(["\r\n", "\r"], "\n", $label));
        if ($label === '') {
            return '';
        }

        $parts = preg_split('/\n+/', $label) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn(string $part): bool => $part !== ''));

        return implode(', ', $parts);
    }

    /**
     * @param mixed $fenceDataRaw
     */
    public static function fenceTypeLabel(string $fenceTypeRaw = '', $fenceDataRaw = null, int $sectionCount = 0): string
    {
        $fences = self::fenceCatalog();
        $rows = self::fenceSectionTypesFromRows(
            self::decodeJsonField($fenceDataRaw),
            $fences
        );

        if ($rows === []) {
            $rows = self::fenceSectionTypesFromTypeMap($fenceTypeRaw, $fences);
            if (count($rows) === 1 && $sectionCount > 0) {
                $rows[0]['count'] = $sectionCount;
            }
        }

        return self::formatFenceTypeSummary($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public static function normalizeListRow(object $row): array
    {
        $item = self::normalizeRow(self::listColumns(), $row, false);
        // Labels come from the lightweight fence_type column (no fence_data blob on list).
        $item['fence_type_label'] = self::fenceTypeLabel(
            (string) ($row->fence_type ?? ''),
            null,
            isset($row->section_count) ? (int) $row->section_count : 0
        );

        return $item;
    }

    /**
     * @param array<string,mixed> $item
     * @param array<string, array<string, mixed>> $fences
     */
    public static function cartItemFenceSlug(array $item, array $fences): string
    {
        self::ensureHelpersLoaded();

        $slug = trim((string) ($item['fence'] ?? ''));
        if ($slug !== '') {
            return fc_normalize_planner_fence_slug($slug);
        }

        $label = fc_cart_item_fence_style_label($item, $fences);
        if ($label === '') {
            return '';
        }

        foreach ($fences as $key => $fence) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            if (strcasecmp(fc_fence_style_title_from_slug($key, $fences), $label) === 0) {
                return fc_normalize_planner_fence_slug($key);
            }
        }

        return '';
    }

    /**
     * @param list<array<string,mixed>> $items
     * @param array<string, array<string, mixed>> $fences
     * @return list<array<string,mixed>>
     */
    public static function normalizeAdminCartRows(array $items, array $fences): array
    {
        self::ensureHelpersLoaded();

        $out = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $optional = !empty($item['optional']);
            $included = empty($item['optional']) || !empty($item['optional_included']);
            $qty = (int) ($item['qty'] ?? 0);
            $suggestedQty = (int) ($item['suggested_qty'] ?? 0);

            if ($optional && !$included) {
                if ($suggestedQty <= 0) {
                    continue;
                }
                $qty = $suggestedQty;
            } elseif ($qty <= 0) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($item['slug'] ?? ''));
            }

            $out[] = [
                'qty' => $qty,
                'name' => $name,
                'sku' => trim((string) ($item['sku'] ?? '')),
                'image' => trim((string) ($item['image'] ?? '')),
                'fence_label' => fc_cart_item_fence_style_label($item, $fences),
                'fence_slug' => self::cartItemFenceSlug($item, $fences),
                'optional' => $optional,
                'optional_included' => $included,
                'suggested_qty' => $suggestedQty,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $grouped
     * @param array<string, array<string, mixed>> $fences
     * @return list<array<string,mixed>>
     */
    public static function adminCartRowsFromGrouped(array $grouped, array $fences): array
    {
        self::ensureHelpersLoaded();

        $out = [];

        foreach ($grouped as $group) {
            if (!is_array($group)) {
                continue;
            }

            $fenceSlug = trim((string) ($group['slug'] ?? ''));
            $normalizedFenceSlug = $fenceSlug !== '' ? fc_normalize_planner_fence_slug($fenceSlug) : '';
            $fenceLabel = $normalizedFenceSlug !== '' ? fc_fence_style_title_from_slug($normalizedFenceSlug, $fences) : '';
            $color = trim((string) ($group['color'] ?? ''));
            if ($fenceLabel !== '' && $color !== '') {
                $fenceLabel .= ' (' . $color . ')';
            }

            $lines = $group['items'] ?? [];
            if (!is_array($lines)) {
                continue;
            }

            foreach ($lines as $line) {
                if (!is_array($line)) {
                    continue;
                }

                $optional = !empty($line['optional']);
                $qty = $optional ? (int) ($line['suggested_qty'] ?? 0) : (int) ($line['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $slug = trim((string) ($line['slug'] ?? ''));
                $out[] = [
                    'qty' => $qty,
                    'name' => $slug,
                    'sku' => '',
                    'image' => '',
                    'fence_label' => $fenceLabel,
                    'fence_slug' => $normalizedFenceSlug,
                    'optional' => $optional,
                    'optional_included' => !$optional,
                    'suggested_qty' => (int) ($line['suggested_qty'] ?? 0),
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array{
     *   qty:int,
     *   name:string,
     *   sku:string,
     *   image:string,
     *   fence_label:string,
     *   fence_slug:string,
     *   optional:bool,
     *   optional_included:bool,
     *   suggested_qty:int
     * }>
     */
    public static function parseEntryCartItems(object $row): array
    {
        self::ensureHelpersLoaded();

        $fences = self::fenceCatalog();
        $cartData = self::decodeJsonField($row->cart_data ?? null);

        if ($cartData !== []) {
            if (isset($cartData['items']) && is_array($cartData['items'])) {
                $cartData = $cartData['items'];
            }

            if (array_is_list($cartData)) {
                $rows = self::normalizeAdminCartRows($cartData, $fences);
                if ($rows !== []) {
                    return $rows;
                }
            }
        }

        $grouped = self::decodeJsonField($row->cart_items_data ?? null);
        if ($grouped === []) {
            return [];
        }

        return self::adminCartRowsFromGrouped($grouped, $fences);
    }

    /**
     * Resolve saved "other items needed" from planners row (column or project_plans JSON).
     *
     * @param mixed $extraColumn
     * @param mixed $projectPlansRaw
     * @return mixed
     */
    public static function resolveExtraValue($extraColumn, $projectPlansRaw)
    {
        $extra = $extraColumn;
        $hasExtra = $extra !== null && $extra !== '';

        if ($hasExtra) {
            if (is_string($extra) && trim($extra) === '[]') {
                return array();
            }

            return $extra;
        }

        if ($projectPlansRaw === null || $projectPlansRaw === '') {
            return $extra;
        }

        $pp = self::decodeJsonField($projectPlansRaw);

        if (!empty($pp['extra'])) {
            return $pp['extra'];
        }

        if (!empty($pp['nothing_extra'])) {
            return 'nothing';
        }

        return $extra;
    }

    /**
     * Normalize extra / other-items value for detail rows (always a string).
     *
     * @param mixed $extra
     */
    public static function extraToString($extra): string
    {
        if ($extra === null || $extra === '') {
            return '[]';
        }

        if (is_array($extra)) {
            $items = array_values(array_filter(array_map(static function ($part): string {
                return trim((string) $part);
            }, $extra)));

            if ($items === []) {
                return '[]';
            }

            $encoded = json_encode($items, JSON_UNESCAPED_UNICODE);

            return is_string($encoded) ? $encoded : '[]';
        }

        $text = trim((string) $extra);
        if ($text === '' || $text === 'nothing') {
            return '[]';
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    public static function normalizeDetailRow(object $row): array
    {
        $item = self::normalizeRow(self::entryColumns(), $row);
        $item['extra'] = self::extraToString(
            self::resolveExtraValue(
                $row->extra ?? '',
                $row->project_plans_data ?? null
            )
        );
        $item['fence_type_label'] = self::fenceTypeLabel(
            (string) ($row->fence_type ?? ''),
            $row->fence_data ?? null,
            isset($row->section_count) ? (int) $row->section_count : 0
        );
        $item['cart_items'] = self::parseEntryCartItems($row);

        return $item;
    }

    private static function ensureHelpersLoaded(): void
    {
        if (!function_exists('fc_normalize_planner_fence_slug')) {
            require_once FC_ROOT . '/config/helpers.php';
        }
    }
}
