<?php

declare(strict_types=1);

namespace Fc\Admin\Presenters;

use Fc\Admin\Helpers\StringHelper;
use Fc\Admin\Helpers\ViewHelper;
use Fc\Admin\Models\PlannerEntryModel;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\FenceCatalogService;
use Fc\Admin\Services\PermissionService;
use Fc\Admin\Services\WcProductCsvService;
use Fc\Admin\Settings\PlannerOptionSettings;
use Fc\Admin\Settings\SystemSettings;

/**
 * Planner entry row shaping — formatting/view-model helpers for wp_planners rows.
 * Calls into PlannerEntryModel for list/detail data (and the Model calls back into
 * this class for row presentation — a documented two-way legacy coupling, tolerated
 * because the PSR-4 autoloader resolves either order without a circular require).
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
            'webhook_sent_at',
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


        $fences = [];
        foreach (glob(FC_ROOT . '/writable/fences/*.php') ?: [] as $fenceFile) {
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

            $norm = FenceCatalogService::normalizePlannerFenceSlug($raw);
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
                'name' => FenceCatalogService::styleTitleFromSlug($norm, $fences),
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

            $norm = FenceCatalogService::normalizePlannerFenceSlug($raw);
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
                'name' => FenceCatalogService::styleTitleFromSlug($norm, $fences),
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

        $slug = trim((string) ($item['fence'] ?? ''));
        if ($slug !== '') {
            return FenceCatalogService::normalizePlannerFenceSlug($slug);
        }

        $label = FenceCatalogService::cartItemFenceStyleLabel($item, $fences);
        if ($label === '') {
            return '';
        }

        foreach ($fences as $key => $fence) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            if (strcasecmp(FenceCatalogService::styleTitleFromSlug($key, $fences), $label) === 0) {
                return FenceCatalogService::normalizePlannerFenceSlug($key);
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
                'fence_label' => FenceCatalogService::cartItemFenceStyleLabel($item, $fences),
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

        $out = [];

        foreach ($grouped as $group) {
            if (!is_array($group)) {
                continue;
            }

            $fenceSlug = trim((string) ($group['slug'] ?? ''));
            $normalizedFenceSlug = $fenceSlug !== '' ? FenceCatalogService::normalizePlannerFenceSlug($fenceSlug) : '';
            $fenceLabel = $normalizedFenceSlug !== '' ? FenceCatalogService::styleTitleFromSlug($normalizedFenceSlug, $fences) : '';
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

    private static function routeSlug(): string
    {
        return 'planner-entries';
    }

    public static function listPath(string $adminBase): string
    {
        return ViewHelper::adminUrl($adminBase, self::routeSlug());
    }

    /**
     * @param list<array{slug:string,name:string}> $options
     * @param list<string> $selected
     */
    public static function fenceTypeFilterLabel(array $options, array $selected): string
    {
        if ($selected === []) {
            return 'All fence types';
        }

        $namesBySlug = [];
        foreach ($options as $option) {
            $slug = (string) ($option['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $namesBySlug[$slug] = (string) ($option['name'] ?? $slug);
        }

        $labels = [];
        foreach ($selected as $slug) {
            $labels[] = $namesBySlug[$slug] ?? (string) $slug;
        }

        return ViewHelper::multiSelectSummaryLabel($labels, 'All fence types');
    }

    public static function defaultPerPage(): int
    {
        return 30;
    }

    /**
     * @return list<int>
     */
    public static function perPageOptions(): array
    {
        return [30, 50, 100, 250, 500];
    }

    /**
     * @return array<string, string>
     */
    public static function datePeriodOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_7_days' => 'Last 7 Days',
            'last_2_weeks' => 'Last 2 Weeks',
            'last_3_weeks' => 'Last 3 Weeks',
            'last_4_weeks' => 'Last 4 Weeks',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'last_3_months' => 'Last 3 Months',
            'last_6_months' => 'Last 6 Months',
            'last_9_months' => 'Last 9 Months',
            'last_12_months' => 'Last 12 Months',
            'this_year' => 'This Year',
            'custom' => 'Custom Range',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateFieldOptions(): array
    {
        return [
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function defaultDateField(): string
    {
        $field = (string) (SystemSettings::get()['entriesDefaultDateField'] ?? 'updated_at');
        $options = self::dateFieldOptions();

        return array_key_exists($field, $options) ? $field : 'updated_at';
    }

    /**
     * Default date period for Planner Entries when no date_period is in the URL.
     * Empty string means all dates.
     */
    public static function defaultDatePeriod(): string
    {
        return SystemSettings::resolvedEntriesDefaultDatePeriod();
    }

    private static function normalizeDateField(string $value): string
    {
        $value = trim($value);
        $options = self::dateFieldOptions();

        return array_key_exists($value, $options) ? $value : self::defaultDateField();
    }

    private static function parseDateInput(string $value): ?\DateTime
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        if ($dt instanceof \DateTime) {
            return $dt->setTime(0, 0, 0);
        }

        try {
            return (new \DateTime($value))->setTime(0, 0, 0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array{from:string,to:string}|null
     */
    private static function dateBoundsForPeriod(string $period): ?array
    {
        $options = self::datePeriodOptions();
        if (!array_key_exists($period, $options) || $period === 'custom') {
            return null;
        }

        $today = new \DateTime('today');
        $end = (clone $today)->setTime(23, 59, 59);
        $start = clone $today;

        switch ($period) {
            case 'today':
                $start->setTime(0, 0, 0);
                break;
            case 'yesterday':
                $start->modify('-1 day')->setTime(0, 0, 0);
                $end = (clone $today)->modify('-1 day')->setTime(23, 59, 59);
                break;
            case 'this_week':
                // Monday of the current week through today (ISO weekday: 1=Mon … 7=Sun).
                $dow = (int) $today->format('N');
                $start = (clone $today)->modify('-' . ($dow - 1) . ' days')->setTime(0, 0, 0);
                break;
            case 'last_7_days':
                $start->modify('-6 days')->setTime(0, 0, 0);
                break;
            case 'last_2_weeks':
                $start->modify('-13 days')->setTime(0, 0, 0);
                break;
            case 'last_3_weeks':
                $start->modify('-20 days')->setTime(0, 0, 0);
                break;
            case 'last_4_weeks':
                $start->modify('-27 days')->setTime(0, 0, 0);
                break;
            case 'this_month':
                $start->modify('first day of this month')->setTime(0, 0, 0);
                break;
            case 'last_month':
                $start->modify('first day of last month')->setTime(0, 0, 0);
                $end = (clone $today)->modify('last day of last month')->setTime(23, 59, 59);
                break;
            case 'last_3_months':
                $start->modify('-3 months')->setTime(0, 0, 0);
                break;
            case 'last_6_months':
                $start->modify('-6 months')->setTime(0, 0, 0);
                break;
            case 'last_9_months':
                $start->modify('-9 months')->setTime(0, 0, 0);
                break;
            case 'last_12_months':
                $start->modify('-12 months')->setTime(0, 0, 0);
                break;
            case 'this_year':
                $start->modify('first day of january this year')->setTime(0, 0, 0);
                break;
            default:
                return null;
        }

        return [
            'from' => $start->format('Y-m-d H:i:s'),
            'to' => $end->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array{
     *   period:string,
     *   from:string,
     *   to:string,
     *   bounds:?array{from:string,to:string}
     * }
     */
    public static function parseDateFilter(string $period, string $from = '', string $to = ''): array
    {
        $period = trim($period);
        if ($period === '') {
            return ['period' => '', 'from' => '', 'to' => '', 'bounds' => null];
        }

        $options = self::datePeriodOptions();
        if (!array_key_exists($period, $options)) {
            return ['period' => '', 'from' => '', 'to' => '', 'bounds' => null];
        }

        if ($period === 'custom') {
            $fromDt = self::parseDateInput($from);
            $toDt = self::parseDateInput($to);
            if (!$fromDt || !$toDt) {
                return ['period' => '', 'from' => '', 'to' => '', 'bounds' => null];
            }

            if ($fromDt > $toDt) {
                [$fromDt, $toDt] = [$toDt, $fromDt];
            }

            return [
                'period' => 'custom',
                'from' => $fromDt->format('Y-m-d'),
                'to' => $toDt->format('Y-m-d'),
                'bounds' => [
                    'from' => $fromDt->format('Y-m-d 00:00:00'),
                    'to' => $toDt->format('Y-m-d 23:59:59'),
                ],
            ];
        }

        $bounds = self::dateBoundsForPeriod($period);
        if ($bounds === null) {
            return ['period' => '', 'from' => '', 'to' => '', 'bounds' => null];
        }

        return [
            'period' => $period,
            'from' => '',
            'to' => '',
            'bounds' => $bounds,
        ];
    }

    public static function dateFilterLabel(string $period, string $from = '', string $to = ''): string
    {
        if ($period === '') {
            return 'All dates';
        }

        $options = self::datePeriodOptions();
        if ($period !== 'custom') {
            return $options[$period] ?? 'All dates';
        }

        if ($from === '' || $to === '') {
            return 'Custom Range';
        }

        try {
            $fromDt = new \DateTime($from);
            $toDt = new \DateTime($to);

            return $fromDt->format('M j, Y') . ' – ' . $toDt->format('M j, Y');
        } catch (\Exception $e) {
            return 'Custom Range';
        }
    }

    /**
     * Display datetime for planner entries admin using System → Date display format.
     */
    public static function formatDatetime(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        try {
            $dt = new \DateTime($value);
        } catch (\Exception $e) {
            return $value;
        }

        $format = SystemSettings::dateFormatPhp();

        return $dt->format($format);
    }

    /**
     * @return array<string, string>
     */
    public static function timeframeOptions(): array
    {
        return PlannerOptionSettings::timeframes();
    }

    /**
     * @return array<string, string>
     */
    public static function stateOptions(): array
    {
        return PlannerOptionSettings::states();
    }

    /**
     * @return list<array{slug:string,name:string}>
     */
    public static function fenceTypeOptions(): array
    {

        $catalog = self::fenceCatalog();
        $options = [];

        foreach ($catalog as $slug => $fence) {
            if (!is_string($slug) || $slug === '') {
                continue;
            }
            $options[] = [
                'slug' => $slug,
                'name' => FenceCatalogService::styleTitleFromSlug($slug, $catalog),
            ];
        }

        usort($options, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        return $options;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    public static function normalizeFenceTypes($raw): array
    {
        if (!is_array($raw)) {
            $raw = trim((string) $raw) === '' ? [] : [trim((string) $raw)];
        }

        $valid = array_keys(self::fenceCatalog());
        $out = [];

        foreach ($raw as $slug) {
            $slug = trim((string) $slug);
            if ($slug !== '' && in_array($slug, $valid, true)) {
                $out[] = $slug;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<string, string>
     */
    public static function deviceOptions(): array
    {
        return [
            '' => 'All devices',
            'desktop' => 'Desktop',
            'mobile' => 'Mobile',
            'tablet' => 'Tablet',
            'bot' => 'Bot',
            'unknown' => 'Unknown',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function browserOptions(): array
    {
        return [
            '' => 'All browsers',
            'chrome' => 'Chrome',
            'edge' => 'Microsoft Edge',
            'firefox' => 'Firefox',
            'safari' => 'Safari',
            'opera' => 'Opera',
            'samsung_internet' => 'Samsung Internet',
            'brave' => 'Brave',
            'internet_explorer' => 'Internet Explorer',
            'other' => 'Other',
            'unknown' => 'Unknown',
        ];
    }

    /**
     * @return list<string>
     */
    private static function normalizeOptions(mixed $values, array $options): array
    {
        if (!is_array($values)) {
            $values = trim((string) $values) === '' ? [] : [$values];
        }

        $normalized = [];
        foreach ($values as $value) {
            $value = strtolower(trim((string) $value));
            if ($value !== '' && array_key_exists($value, $options)) {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private static function normalizeRangeValue(mixed $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }

        return min(4294967295, (int) $value);
    }

    /**
     * @return array{min:?int,max:?int}
     */
    private static function normalizeRange(mixed $minimum, mixed $maximum): array
    {
        $min = self::normalizeRangeValue($minimum);
        $max = self::normalizeRangeValue($maximum);
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return ['min' => $min, 'max' => $max];
    }

    /**
     * @param array<string, mixed> $query
     * @return array{
     *   q:string,
     *   status:string,
     *   timeframe:string,
     *   state:string,
     *   fence_types:list<string>,
     *   date_period:string,
     *   date_from:string,
     *   date_to:string,
     *   date_field:string,
     *   date_bounds:?array{from:string,to:string},
     *   page:int,
     *   per_page:int|string,
     *   is_all:bool,
     *   offset:int
     * }
     */
    private static function parseRequest(array $query): array
    {
        $q = trim((string) ($query['q'] ?? ''));
        $status = trim((string) ($query['status'] ?? ''));
        $timeframe = trim((string) ($query['timeframe'] ?? ''));
        $state = trim((string) ($query['state'] ?? ''));
        $postcode = substr(trim((string) ($query['postcode'] ?? '')), 0, 32);
        $devices = self::normalizeOptions(
            $query['device'] ?? [],
            self::deviceOptions()
        );
        $browsers = self::normalizeOptions(
            $query['browser'] ?? [],
            self::browserOptions()
        );
        $sections = self::normalizeRange(
            $query['sections_min'] ?? null,
            $query['sections_max'] ?? null
        );
        $quoteLoads = self::normalizeRange(
            $query['quote_loads_min'] ?? null,
            $query['quote_loads_max'] ?? null
        );
        $viewRaw = strtolower(trim((string) ($query['view'] ?? 'all')));
        if ($viewRaw === 'trash') {
            $view = 'trash';
        } elseif ($viewRaw === 'duplicates') {
            $view = 'duplicates';
        } else {
            $view = 'all';
        }
        $fenceTypes = self::normalizeFenceTypes($query['fence_type'] ?? []);
        $dateField = self::normalizeDateField((string) ($query['date_field'] ?? self::defaultDateField()));
        // First visit (no date_period param) uses System → Date Settings default.
        $datePeriodRaw = array_key_exists('date_period', $query)
            ? (string) ($query['date_period'] ?? '')
            : self::defaultDatePeriod();
        $dateFilter = self::parseDateFilter(
            $datePeriodRaw,
            (string) ($query['date_from'] ?? ''),
            (string) ($query['date_to'] ?? '')
        );
        $paging = ViewHelper::parseListPagination($query, self::perPageOptions(), self::defaultPerPage());

        if ($timeframe !== '' && !array_key_exists($timeframe, PlannerOptionSettings::timeframes())) {
            $timeframe = '';
        }
        if ($state !== '' && !array_key_exists($state, PlannerOptionSettings::states())) {
            $state = '';
        }

        return [
            'q' => $q,
            'status' => $status,
            'timeframe' => $timeframe,
            'state' => $state,
            'postcode' => $postcode,
            'device' => $devices,
            'browser' => $browsers,
            'sections_min' => $sections['min'],
            'sections_max' => $sections['max'],
            'quote_loads_min' => $quoteLoads['min'],
            'quote_loads_max' => $quoteLoads['max'],
            'view' => $view,
            'fence_types' => $fenceTypes,
            'date_period' => $dateFilter['period'],
            'date_from' => $dateFilter['from'],
            'date_to' => $dateFilter['to'],
            'date_field' => $dateField,
            'date_bounds' => $dateFilter['bounds'],
            'page' => $paging['page'],
            'per_page' => $paging['per_page_value'],
            'is_all' => $paging['is_all'],
            'offset' => $paging['offset'],
        ];
    }

    /**
     * Allow only same-origin relative planner-entries list URLs (not detail routes).
     */
    private static function sanitizeListReturnUrl(string $adminBase, string $candidate): string
    {
        $fallback = self::listPath($adminBase);
        $candidate = trim($candidate);
        if ($candidate === '') {
            return $fallback;
        }

        // Reject absolute / protocol-relative URLs (open-redirect safety).
        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $candidate) === 1) {
            return $fallback;
        }

        // Accept query-only return values.
        if ($candidate[0] === '?') {
            return $fallback . $candidate;
        }

        $listPath = $fallback;
        if (!str_starts_with($candidate, $listPath)) {
            return $fallback;
        }

        $after = substr($candidate, strlen($listPath));
        // List URL only: "" or "?…" — reject "/123" detail paths.
        if ($after !== '' && $after[0] !== '?') {
            return $fallback;
        }

        return $candidate;
    }

    public static function entryUrl(string $adminBase, int $entryId, ?string $returnUrl = null): string
    {
        if ($entryId <= 0) {
            return self::listPath($adminBase);
        }

        $url = self::listPath($adminBase) . '/' . $entryId;
        if ($returnUrl === null || $returnUrl === '') {
            return $url;
        }

        $safeReturn = self::sanitizeListReturnUrl($adminBase, $returnUrl);

        return $url . '?return=' . rawurlencode($safeReturn);
    }

    /**
     * @param array<string, mixed> $request
     */
    private static function queryFromRequest(array $request): array
    {
        $query = [
            'q' => $request['q'] ?? '',
            'status' => $request['status'] ?? '',
            'timeframe' => $request['timeframe'] ?? '',
            'state' => $request['state'] ?? '',
            'postcode' => $request['postcode'] ?? '',
            'device' => $request['device'] ?? [],
            'browser' => $request['browser'] ?? [],
            'sections_min' => $request['sections_min'] ?? null,
            'sections_max' => $request['sections_max'] ?? null,
            'quote_loads_min' => $request['quote_loads_min'] ?? null,
            'quote_loads_max' => $request['quote_loads_max'] ?? null,
            'view' => $request['view'] ?? 'all',
            'date_period' => $request['date_period'] ?? '',
            'date_from' => $request['date_from'] ?? '',
            'date_to' => $request['date_to'] ?? '',
            'date_field' => $request['date_field'] ?? self::defaultDateField(),
            'page' => $request['page'] ?? 1,
            'per_page' => $request['per_page'] ?? self::defaultPerPage(),
        ];

        if (($query['view'] ?? 'all') === 'all') {
            unset($query['view']);
        }

        if (($query['date_field'] ?? self::defaultDateField()) === self::defaultDateField()) {
            unset($query['date_field']);
        }

        $fenceTypes = $request['fence_types'] ?? [];
        if (is_array($fenceTypes) && $fenceTypes !== []) {
            $query['fence_type'] = array_values($fenceTypes);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function buildQueryString(array $query): string
    {
        $fenceTypes = [];
        if (isset($query['fence_type'])) {
            $fenceTypes = self::normalizeFenceTypes($query['fence_type']);
            unset($query['fence_type']);
        }

        foreach ($query as $key => $value) {
            if ($value === '' || $value === null) {
                unset($query[$key]);
                continue;
            }
            if ($key === 'page' && (int) $value <= 1) {
                unset($query[$key]);
            }
            if ($key === 'per_page' && (string) $value === (string) self::defaultPerPage()) {
                unset($query[$key]);
            }
        }

        $parts = [];
        $baseQs = http_build_query($query);
        if ($baseQs !== '') {
            $parts[] = $baseQs;
        }

        if ($fenceTypes !== []) {
            foreach ($fenceTypes as $slug) {
                $parts[] = 'fence_type[]=' . rawurlencode($slug);
            }
        }

        return implode('&', $parts);
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, scalar|null|list<string>> $overrides
     */
    public static function url(string $adminBase, array $request, array $overrides = []): string
    {
        $base = self::listPath($adminBase);
        $query = array_merge(
            self::queryFromRequest($request),
            $overrides
        );

        if (isset($overrides['fence_types']) && is_array($overrides['fence_types'])) {
            $query['fence_type'] = $overrides['fence_types'];
            unset($query['fence_types']);
        }

        $qs = self::buildQueryString($query);

        return $qs === '' ? $base : $base . '?' . $qs;
    }

    public static function statusClass(string $status): string
    {
        $status = strtolower(trim($status));
        if (in_array($status, ['planning', 'duplicate', 'reloaded', 'submitted'], true)) {
            return 'fc-entries-status fc-entries-status--' . $status;
        }

        return 'fc-entries-status';
    }

    public static function plannerUrl(string $appBase, string $plannerId): string
    {
        $plannerId = trim($plannerId);
        if ($plannerId === '') {
            return '#';
        }

        return rtrim($appBase, '/') . '?qid=' . rawurlencode($plannerId);
    }

    public static function plannerShareUrl(string $appBase, string $plannerId): string
    {
        $plannerId = trim($plannerId);
        if ($plannerId === '') {
            return '';
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

        return $scheme . '://' . $host . self::plannerUrl($appBase, $plannerId);
    }

    public static function ipinfoUrl(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return '';
        }

        return 'https://ipinfo.io/' . rawurlencode($ip);
    }

    public static function deviceIcon(string $device): string
    {
        return match (strtolower(trim($device))) {
            'desktop' => 'fa-solid fa-desktop',
            'mobile' => 'fa-solid fa-mobile-screen',
            'tablet' => 'fa-solid fa-tablet-screen-button',
            'bot' => 'fa-solid fa-robot',
            default => 'fa-solid fa-circle-question',
        };
    }

    public static function browserName(string $userAgent): string
    {
        $ua = strtolower(trim($userAgent));
        if ($ua === '') {
            return '';
        }

        if (
            str_contains($ua, 'edg/')
            || str_contains($ua, 'edge/')
            || str_contains($ua, 'edga/')
            || str_contains($ua, 'edgios/')
        ) {
            return 'Microsoft Edge';
        }
        if (str_contains($ua, 'opr/') || str_contains($ua, 'opera')) {
            return 'Opera';
        }
        if (str_contains($ua, 'samsungbrowser/')) {
            return 'Samsung Internet';
        }
        if (str_contains($ua, 'brave/')) {
            return 'Brave';
        }
        if (str_contains($ua, 'chrome/') || str_contains($ua, 'crios/')) {
            return 'Chrome';
        }
        if (str_contains($ua, 'firefox/') || str_contains($ua, 'fxios/')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'safari/') && !str_contains($ua, 'chromium/')) {
            return 'Safari';
        }
        if (str_contains($ua, 'msie ') || str_contains($ua, 'trident/')) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    public static function browserIcon(string $browser): string
    {
        return match (strtolower(trim($browser))) {
            'chrome' => 'fa-brands fa-chrome',
            'microsoft edge' => 'fa-brands fa-edge',
            'firefox' => 'fa-brands fa-firefox-browser',
            'safari' => 'fa-brands fa-safari',
            'opera' => 'fa-brands fa-opera',
            'internet explorer' => 'fa-brands fa-internet-explorer',
            'samsung internet' => 'fa-brands fa-android',
            'brave' => 'fa-solid fa-shield-halved',
            default => 'fa-solid fa-globe',
        };
    }

    public static function cartImageUrl(string $appBase, string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        $image = WcProductCsvService::displayImageUrl($image);

        if ($image !== '' && $image[0] === '/') {
            return $image;
        }

        return rtrim($appBase, '/') . '/' . ltrim($image, '/');
    }

    public static function extraItems(mixed $raw): array
    {
        if ($raw === null || $raw === '' || (is_string($raw) && trim($raw) === '[]')) {
            return ['Nothing Extra, Just Fencing'];
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '' || $trimmed === '[]') {
                return ['Nothing Extra, Just Fencing'];
            }

            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } elseif ($trimmed === 'nothing') {
                return ['Nothing Extra, Just Fencing'];
            } elseif (str_contains($trimmed, ',')) {
                $raw = array_values(array_filter(array_map('trim', explode(',', $trimmed))));
            } else {
                $raw = [$trimmed];
            }
        }

        if (!is_array($raw)) {
            return ['Nothing Extra, Just Fencing'];
        }

        $items = array_values(array_filter(array_map(static function ($part): string {
            return trim((string) $part);
        }, $raw)));

        if ($items === [] || (count($items) === 1 && $items[0] === 'nothing')) {
            return ['Nothing Extra, Just Fencing'];
        }

        $labels = [];
        foreach ($items as $slug) {
            if ($slug === '' || $slug === 'nothing') {
                continue;
            }

            $label = PlannerOptionSettings::extraLabel($slug);
            $labels[] = $label !== '' ? $label : $slug;
        }

        return $labels !== [] ? $labels : ['Nothing Extra, Just Fencing'];
    }

    /**
     * Resolve a legacy `?detail=<planner_id|id>` URL to its new detail-route URL, or null if
     * no redirect is needed. Pure — the caller (Controller) performs the actual header()/exit.
     */
    public static function resolveLegacyDetailRedirect(string $adminBase, string $detailParam): ?string
    {
        $detail = trim($detailParam);
        if ($detail === '') {
            return null;
        }

        $entryId = ctype_digit($detail) ? (int) $detail : (int) (PlannerEntryModel::entryIdForPlanner($detail) ?? 0);
        if ($entryId <= 0) {
            return null;
        }

        return self::entryUrl($adminBase, $entryId);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function listViewData(string $adminBase, string $appBase, array $query = []): array
    {
        $appBase = rtrim($appBase, '/');
        $request = self::parseRequest($query);
        $limit = $request['is_all'] ? 0 : (int) $request['per_page'];

        $list = PlannerEntryModel::list(
            $request['q'],
            $request['status'],
            $limit,
            $request['offset'],
            false,
            true,
            $request['timeframe'],
            $request['state'],
            $request['fence_types'],
            $request['date_bounds'],
            (string) ($request['view'] ?? 'all'),
            (string) ($request['date_field'] ?? self::defaultDateField()),
            (string) ($request['postcode'] ?? ''),
            is_array($request['device'] ?? null) ? $request['device'] : [],
            is_array($request['browser'] ?? null) ? $request['browser'] : [],
            $request['sections_min'] ?? null,
            $request['sections_max'] ?? null,
            $request['quote_loads_min'] ?? null,
            $request['quote_loads_max'] ?? null
        );

        $statuses = PlannerEntryModel::statusList();
        $timeframes = self::timeframeOptions();
        $states = self::stateOptions();
        $fenceTypeOptions = self::fenceTypeOptions();
        $viewCounts = PlannerEntryModel::trashViewCounts();
        $view = (string) ($request['view'] ?? 'all');

        $error = '';
        if (empty($list['ok'])) {
            $error = (string) ($list['error'] ?? 'Could not load planner entries.');
            $list = ['ok' => false, 'items' => [], 'total' => 0, 'has_more' => false, 'statuses' => []];
        }

        $items = is_array($list['items'] ?? null) ? $list['items'] : [];
        $total = (int) ($list['total'] ?? count($items));
        $perPage = $request['is_all'] ? max(1, count($items)) : max(1, (int) $request['per_page']);
        $totalPages = $request['is_all'] ? 1 : max(1, (int) ceil($total / $perPage));

        if ($request['page'] > $totalPages && $totalPages > 0) {
            return ['redirect_url' => self::url($adminBase, $request, ['page' => $totalPages])];
        }

        $shownFrom = count($items) ? $request['offset'] + 1 : 0;
        $shownTo = $request['offset'] + count($items);

        if (!count($items)) {
            $countLabel = '0 entries';
        } elseif ($request['is_all']) {
            $countLabel = count($items) . ' entr' . (count($items) === 1 ? 'y' : 'ies');
        } elseif ($total > 0) {
            $countLabel = $shownFrom . '–' . $shownTo . ' of ' . $total;
        } else {
            $countLabel = $shownFrom . '–' . $shownTo . (!empty($list['has_more']) ? '+' : '');
        }

        $selectedFenceTypes = is_array($request['fence_types'] ?? null) ? $request['fence_types'] : [];
        $datePeriod = (string) ($request['date_period'] ?? '');
        $dateFrom = (string) ($request['date_from'] ?? '');
        $dateTo = (string) ($request['date_to'] ?? '');
        $dateField = self::normalizeDateField((string) ($request['date_field'] ?? self::defaultDateField()));

        $hasActiveFilters = ($request['q'] ?? '') !== ''
            || ($request['status'] ?? '') !== ''
            || ($request['timeframe'] ?? '') !== ''
            || ($request['state'] ?? '') !== ''
            || ($request['postcode'] ?? '') !== ''
            || (is_array($request['device'] ?? null) && $request['device'] !== [])
            || (is_array($request['browser'] ?? null) && $request['browser'] !== [])
            || ($request['sections_min'] ?? null) !== null
            || ($request['sections_max'] ?? null) !== null
            || ($request['quote_loads_min'] ?? null) !== null
            || ($request['quote_loads_max'] ?? null) !== null
            || $datePeriod !== ''
            || $selectedFenceTypes !== [];

        $viewCounts = is_array($viewCounts) ? $viewCounts : ['all' => 0, 'trash' => 0, 'duplicates' => 0];
        $tabs = [
            [
                'key' => 'all',
                'label' => 'All',
                'count' => (int) ($viewCounts['all'] ?? 0),
                'is_active' => $view === 'all',
                'href' => self::url($adminBase, $request, ['view' => 'all', 'page' => 1]),
            ],
            [
                'key' => 'trash',
                'label' => 'Trash',
                'count' => (int) ($viewCounts['trash'] ?? 0),
                'is_active' => $view === 'trash',
                'href' => self::url($adminBase, $request, ['view' => 'trash', 'page' => 1]),
            ],
            [
                'key' => 'duplicates',
                'label' => 'Duplicates',
                'count' => (int) ($viewCounts['duplicates'] ?? 0),
                'is_active' => $view === 'duplicates',
                'href' => self::url($adminBase, $request, ['view' => 'duplicates', 'page' => 1]),
            ],
        ];

        $clearFiltersUrl = self::url($adminBase, $request, [
            'q' => '',
            'status' => '',
            'timeframe' => '',
            'state' => '',
            'postcode' => '',
            'device' => [],
            'browser' => [],
            'sections_min' => '',
            'sections_max' => '',
            'quote_loads_min' => '',
            'quote_loads_max' => '',
            'date_period' => self::defaultDatePeriod(),
            'date_from' => '',
            'date_to' => '',
            'date_field' => self::defaultDateField(),
            'fence_types' => [],
            'view' => $view,
            'page' => '',
        ]);

        $canDedupe = PermissionService::can('planner_entries.find_duplicates');
        // Always available on All when permitted — scanning happens on click, not page load.
        $canRemoveDuplicates = $canDedupe && $view === 'all';

        if ($view === 'trash') {
            $bulkOptions = [
                ['value' => 'export', 'label' => 'Export as JSON', 'perm' => 'planner_entries.import_export'],
                ['value' => 'restore', 'label' => 'Restore', 'perm' => 'planner_entries.trash_delete_restore'],
                ['value' => 'delete', 'label' => 'Delete permanently', 'perm' => 'planner_entries.trash_delete_restore'],
            ];
        } elseif ($view === 'duplicates') {
            $bulkOptions = [
                ['value' => 'export', 'label' => 'Export as JSON', 'perm' => 'planner_entries.import_export'],
                ['value' => 'restore-duplicate', 'label' => 'Restore to All', 'perm' => 'planner_entries.find_duplicates'],
                ['value' => 'trash', 'label' => 'Move to trash', 'perm' => 'planner_entries.trash_delete_restore'],
                ['value' => 'delete', 'label' => 'Delete permanently', 'perm' => 'planner_entries.trash_delete_restore'],
            ];
        } else {
            $bulkOptions = [
                ['value' => 'export', 'label' => 'Export as JSON', 'perm' => 'planner_entries.import_export'],
                ['value' => 'trash', 'label' => 'Move to trash', 'perm' => 'planner_entries.trash_delete_restore'],
            ];
        }
        $bulkActionOptions = [];
        foreach ($bulkOptions as $option) {
            $perm = (string) ($option['perm'] ?? '');
            if ($perm !== '' && !PermissionService::can($perm)) {
                continue;
            }
            $bulkActionOptions[] = [
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['label'] ?? ''),
            ];
        }

        $canView = PermissionService::can('planner_entries.view');

        $fenceOptions = [];
        foreach ($fenceTypeOptions as $fenceOption) {
            if (!is_array($fenceOption)) {
                continue;
            }
            $fenceSlug = (string) ($fenceOption['slug'] ?? '');
            $fenceOptions[] = [
                'slug' => $fenceSlug,
                'name' => (string) ($fenceOption['name'] ?? $fenceSlug),
                'is_checked' => in_array($fenceSlug, $selectedFenceTypes, true),
            ];
        }

        $tableRows = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $plannerId = (string) ($item['planner_id'] ?? '');
            $entryId = (int) ($item['id'] ?? 0);
            $fenceLabel = (string) ($item['fence_type_label'] ?? '');
            if ($fenceLabel === '') {
                $fenceLabel = self::fenceTypeLabel(
                    (string) ($item['fence_type'] ?? ''),
                    null,
                    (int) ($item['section_count'] ?? 0)
                );
            }
            $fenceLabelLines = preg_split('/\r\n|\r|\n/', $fenceLabel) ?: [];
            $fenceLabelLines = array_values(array_filter(
                array_map(static fn($line): string => trim((string) $line), $fenceLabelLines),
                static fn(string $line): bool => $line !== ''
            ));
            $timeframeSlug = (string) ($item['timeframe'] ?? '');
            $status = (string) ($item['status'] ?? '');
            $device = trim((string) ($item['device'] ?? ''));
            $browser = self::browserName((string) ($item['user_agent'] ?? ''));
            $dateValue = $dateField === 'updated_at'
                ? ($item['updated_at'] ?? '')
                : ($item['created_at'] ?? '');
            $canOpen = $canView && $entryId > 0;
            $tableRows[] = [
                'id' => $entryId,
                'planner_id' => $plannerId,
                'can_open' => $canOpen,
                'row_href' => $canOpen ? self::entryUrl($adminBase, $entryId, self::url($adminBase, $request)) : '',
                'planner_share_url' => self::plannerShareUrl($appBase, $plannerId),
                'status' => $status,
                'status_class' => self::statusClass($status),
                'name' => (string) ($item['name'] ?? ''),
                'email' => (string) ($item['email'] ?? ''),
                'mobile' => (string) ($item['mobile'] ?? ''),
                'fence_label' => $fenceLabel,
                'fence_label_inline' => self::formatFenceTypeSummaryInline($fenceLabel),
                'fence_label_lines' => $fenceLabelLines,
                'timeframe_label' => (string) ($timeframes[$timeframeSlug] ?? $timeframeSlug),
                'section_count' => (string) ($item['section_count'] ?? ''),
                'quote_load_count' => (string) max(0, (int) ($item['quote_load_count'] ?? 0)),
                'state' => (string) ($item['state'] ?? ''),
                'device' => $device !== '' ? $device : 'Unknown',
                'device_icon' => self::deviceIcon($device),
                'browser' => $browser !== '' ? $browser : 'Unknown',
                'browser_icon' => self::browserIcon($browser),
                'date_at' => self::formatDatetime($dateValue),
            ];
        }

        $currentPage = (int) ($request['page'] ?? 1);
        $perPageUrlValue = $request['is_all'] ? 'all' : $perPage;
        $paginationPages = ViewHelper::paginationWindow($currentPage, $totalPages);
        $pagination = [
            'show' => !$request['is_all'] && $totalPages > 1,
            'pages' => $paginationPages,
            'prev_url' => ($currentPage > 1) ? self::url($adminBase, $request, ['page' => $currentPage - 1]) : '',
            'next_url' => ($currentPage < $totalPages) ? self::url($adminBase, $request, ['page' => $currentPage + 1]) : '',
        ];
        $paginationLinks = ViewHelper::paginationLinks(
            $paginationPages,
            $currentPage,
            static fn (int $num): string => self::url($adminBase, $request, ['page' => $num])
        );

        return [
            'redirect_url' => null,
            'request' => $request,
            'admin_base' => $adminBase,
            'app_base' => $appBase,
            'api_url' => 'api.php?module=entries',
            'error' => $error,
            'items' => $items,
            'statuses' => $statuses,
            'timeframes' => $timeframes,
            'states' => $states,
            'device_options' => self::deviceOptions(),
            'browser_options' => self::browserOptions(),
            'fence_type_options' => $fenceTypeOptions,
            'fence_options' => $fenceOptions,
            'view' => $view,
            'view_counts' => $viewCounts,
            'duplicate_candidate_count' => 0,
            'total' => $total,
            'total_pages' => $totalPages,
            'count_label' => $countLabel,
            'selected_fence_types' => $selectedFenceTypes,
            'date_period' => $datePeriod,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_field' => $dateField,
            'date_field_options' => self::dateFieldOptions(),
            'date_period_options' => self::datePeriodOptions(),
            'date_filter_label' => self::dateFilterLabel($datePeriod, $dateFrom, $dateTo),
            'date_column_label' => $dateField === 'updated_at' ? 'Updated At' : 'Created At',
            'current_page' => $currentPage,
            'is_all' => $request['is_all'],
            'has_active_filters' => $hasActiveFilters,
            'fence_type_filter_label' => self::fenceTypeFilterLabel($fenceTypeOptions, $selectedFenceTypes),
            'form_action' => self::routeSlug(),
            'tabs' => $tabs,
            'clear_filters_url' => $clearFiltersUrl,
            'show_per_page_hidden' => (string) ($request['per_page'] ?? '') === 'all'
                || (int) ($request['per_page'] ?? 0) !== self::defaultPerPage(),
            'per_page_options' => self::perPageOptions(),
            'filter_hidden_html' => self::filterHiddenHtml($request, $selectedFenceTypes),
            'is_trash_view' => $view === 'trash',
            'is_duplicates_view' => $view === 'duplicates',
            'can_remove_duplicates' => $canRemoveDuplicates,
            'bulk_action_options' => $bulkActionOptions,
            'csrf' => AuthService::csrfToken(),
            'can_import' => PermissionService::can('planner_entries.import_export'),
            'can_view' => $canView,
            'table_rows' => $tableRows,
            'has_table_rows' => $tableRows !== [],
            'pagination' => $pagination,
            'pagination_links' => $paginationLinks,
        ];
    }

    /**
     * @param array<string, mixed> $req
     * @param list<string> $selectedFenceTypes
     */
    public static function filterHiddenHtml(array $req, array $selectedFenceTypes, array $exclude = []): string
    {
        $parts = [];
        if (!in_array('q', $exclude, true) && ($req['q'] ?? '') !== '') {
            $parts[] = '<input type="hidden" name="q" value="' . StringHelper::escapeHtml((string) $req['q']) . '">';
        }
        if (!in_array('status', $exclude, true) && ($req['status'] ?? '') !== '') {
            $parts[] = '<input type="hidden" name="status" value="' . StringHelper::escapeHtml((string) $req['status']) . '">';
        }
        if (!in_array('timeframe', $exclude, true) && ($req['timeframe'] ?? '') !== '') {
            $parts[] = '<input type="hidden" name="timeframe" value="' . StringHelper::escapeHtml((string) $req['timeframe']) . '">';
        }
        if (!in_array('state', $exclude, true) && ($req['state'] ?? '') !== '') {
            $parts[] = '<input type="hidden" name="state" value="' . StringHelper::escapeHtml((string) $req['state']) . '">';
        }
        foreach ([
            'postcode',
            'sections_min',
            'sections_max',
            'quote_loads_min',
            'quote_loads_max',
        ] as $field) {
            $value = $req[$field] ?? null;
            if (!in_array($field, $exclude, true) && $value !== null && $value !== '') {
                $parts[] = '<input type="hidden" name="' . $field . '" value="'
                    . StringHelper::escapeHtml((string) $value) . '">';
            }
        }
        foreach (['device', 'browser'] as $field) {
            if (in_array($field, $exclude, true) || !is_array($req[$field] ?? null)) {
                continue;
            }
            foreach ($req[$field] as $value) {
                $parts[] = '<input type="hidden" name="' . $field . '[]" value="'
                    . StringHelper::escapeHtml((string) $value) . '">';
            }
        }
        if (!in_array('view', $exclude, true) && in_array(($req['view'] ?? 'all'), ['trash', 'duplicates'], true)) {
            $parts[] = '<input type="hidden" name="view" value="'
                . StringHelper::escapeHtml((string) $req['view']) . '">';
        }
        if (!in_array('date_period', $exclude, true) && ($req['date_period'] ?? '') !== '') {
            $parts[] = '<input type="hidden" name="date_period" value="' . StringHelper::escapeHtml((string) $req['date_period']) . '">';
        }
        if (!in_array('date_from', $exclude, true) && ($req['date_from'] ?? '') !== '') {
            $parts[] = '<input type="hidden" name="date_from" value="' . StringHelper::escapeHtml((string) $req['date_from']) . '">';
        }
        if (!in_array('date_to', $exclude, true) && ($req['date_to'] ?? '') !== '') {
            $parts[] = '<input type="hidden" name="date_to" value="' . StringHelper::escapeHtml((string) $req['date_to']) . '">';
        }
        if (
            !in_array('date_field', $exclude, true)
            && self::normalizeDateField((string) ($req['date_field'] ?? self::defaultDateField())) !== self::defaultDateField()
        ) {
            $parts[] = '<input type="hidden" name="date_field" value="' . StringHelper::escapeHtml(
                self::normalizeDateField((string) ($req['date_field'] ?? self::defaultDateField()))
            ) . '">';
        }
        if (!in_array('fence_type', $exclude, true)) {
            foreach ($selectedFenceTypes as $fenceSlug) {
                $parts[] = '<input type="hidden" name="fence_type[]" value="' . StringHelper::escapeHtml((string) $fenceSlug) . '">';
            }
        }

        return implode('', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public static function detailViewData(string $adminBase, string $appBase, int $entryId, string $returnParam = ''): array
    {
        $appBase = rtrim($appBase, '/');
        $error = '';
        $item = null;

        if ($entryId <= 0) {
            $error = 'Entry ID required.';
        } else {
            $result = PlannerEntryModel::getById($entryId);
            if (!empty($result['ok']) && isset($result['item']) && is_array($result['item'])) {
                $item = $result['item'];
            } else {
                $error = (string) ($result['error'] ?? 'Entry not found.');
            }
        }

        $listUrl = self::sanitizeListReturnUrl($adminBase, $returnParam);
        $cartItems = is_array($item['cart_items'] ?? null) ? $item['cart_items'] : [];

        $cartTotalQty = 0;
        $cartOptionalCount = 0;
        foreach ($cartItems as $cartItem) {
            if (!is_array($cartItem)) {
                continue;
            }
            if (!empty($cartItem['optional']) && empty($cartItem['optional_included'])) {
                $cartOptionalCount++;
                continue;
            }
            $cartTotalQty += (int) ($cartItem['qty'] ?? 0);
        }

        $cartFenceSlugs = [];
        foreach ($cartItems as $cartItem) {
            if (!is_array($cartItem)) {
                continue;
            }
            $slug = trim((string) ($cartItem['fence_slug'] ?? ''));
            if ($slug !== '') {
                $cartFenceSlugs[$slug] = true;
            }
        }

        $cartFenceOptions = [];
        foreach (self::fenceTypeOptions() as $fenceOption) {
            if (!is_array($fenceOption)) {
                continue;
            }
            $slug = (string) ($fenceOption['slug'] ?? '');
            if ($slug !== '' && isset($cartFenceSlugs[$slug])) {
                $cartFenceOptions[] = [
                    'slug' => $slug,
                    'name' => (string) ($fenceOption['name'] ?? $slug),
                ];
            }
        }

        $detailFields = [
            'planner_id' => 'Planner ID',
            'site_id' => 'Site ID',
            'site_url' => 'Site URL',
            'status' => 'Status',
            'section_count' => 'Sections',
            'notes' => 'Notes',
            'name' => 'Name',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'address' => 'Address',
            'postcode' => 'Postcode',
            'state' => 'State',
            'fence_type' => 'Fence type',
            'timeframe' => 'Timeframe',
            'extra' => 'Other Items Needed',
            'installer' => 'Installer',
            'ip_address' => 'IP address',
            'device' => 'Device',
            'user_agent' => 'User agent',
            'quote_load_count' => 'Quote loads',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
            'webhook_sent_at' => 'Webhook sent',
        ];

        $detailRows = [];
        if (is_array($item)) {
            foreach ($detailFields as $fieldKey => $fieldLabel) {
                $extraItems = null;
                $raw = $item[$fieldKey] ?? '';
                if ($fieldKey === 'fence_type') {
                    $raw = (string) ($item['fence_type_label'] ?? '');
                    if ($raw === '') {
                        $raw = self::fenceTypeLabel(
                            (string) ($item['fence_type'] ?? ''),
                            $item['fence_data'] ?? null,
                            (int) ($item['section_count'] ?? 0)
                        );
                    }
                    $fenceLines = preg_split('/\r\n|\r|\n/', (string) $raw) ?: [];
                    $fenceLines = array_values(array_filter(
                        array_map(static fn($line): string => trim((string) $line), $fenceLines),
                        static fn(string $line): bool => $line !== ''
                    ));
                    if ($fenceLines !== []) {
                        $extraItems = $fenceLines;
                        $raw = implode("\n", $fenceLines);
                    }
                } elseif ($fieldKey === 'timeframe') {
                    $slug = trim((string) $raw);
                    if ($slug !== '') {
                        $raw = PlannerOptionSettings::timeframeLabel($slug) ?: $slug;
                    }
                } elseif ($fieldKey === 'extra') {
                    $extraItems = self::extraItems($raw);
                    $raw = implode(', ', $extraItems);
                } elseif ($fieldKey === 'user_agent') {
                    $raw = self::browserName((string) $raw);
                } elseif (in_array($fieldKey, ['created_at', 'updated_at', 'webhook_sent_at'], true)) {
                    $raw = self::formatDatetime($raw);
                } elseif ($fieldKey === 'quote_load_count') {
                    $raw = (string) max(0, (int) $raw);
                }

                $display = ($raw === null || $raw === '') ? '—' : (string) $raw;
                $ipAddress = $fieldKey === 'ip_address' ? trim((string) $raw) : '';
                $ipinfoUrl = $ipAddress !== '' ? self::ipinfoUrl($ipAddress) : '';
                $deviceIcon = ($fieldKey === 'device' && $display !== '—')
                    ? self::deviceIcon($display)
                    : '';
                $detailRows[] = [
                    'key' => $fieldKey,
                    'label' => $fieldLabel,
                    'display' => $display,
                    'copy' => $display,
                    'display_items' => is_array($extraItems) ? $extraItems : null,
                    'status_class' => $fieldKey === 'status' ? self::statusClass((string) $raw) : '',
                    'planner_url' => ($fieldKey === 'planner_id' && $raw !== '')
                        ? self::plannerUrl($appBase, (string) $raw)
                        : '',
                    'ipinfo_url' => $ipinfoUrl,
                    'device_icon' => $deviceIcon,
                    'is_link' => (
                        in_array($fieldKey, ['planner_id', 'site_url', 'email'], true) && $raw !== ''
                    ) || ($fieldKey === 'ip_address' && $ipinfoUrl !== ''),
                    'link_type' => $fieldKey,
                ];
            }
        }

        $copyAllText = implode(
            "\n",
            array_map(
                static fn(array $row): string => $row['label'] . ': ' . $row['copy'],
                $detailRows
            )
        );

        $cartRows = [];
        foreach ($cartItems as $cartItem) {
            if (!is_array($cartItem)) {
                continue;
            }
            $imageUrl = self::cartImageUrl($appBase, (string) ($cartItem['image'] ?? ''));
            $isOptional = !empty($cartItem['optional']) && empty($cartItem['optional_included']);
            $fenceLabel = trim((string) ($cartItem['fence_label'] ?? ''));
            $fenceSlug = trim((string) ($cartItem['fence_slug'] ?? ''));
            $rowQty = $isOptional ? 0 : (int) ($cartItem['qty'] ?? 0);
            $productName = trim((string) ($cartItem['name'] ?? ''));
            $searchHaystack = strtolower(implode(' ', array_filter([
                $productName,
                (string) ($cartItem['sku'] ?? ''),
                $fenceLabel,
            ], static fn($part): bool => trim((string) $part) !== '')));

            $cartRows[] = [
                'image_url' => $imageUrl,
                'gallery_caption' => $productName !== '' ? $productName : 'Product image',
                'is_optional' => $isOptional,
                'row_qty' => $rowQty,
                'optional_qty' => (int) ($cartItem['qty'] ?? 0),
                'name' => (string) ($cartItem['name'] ?? ''),
                'sku' => (string) ($cartItem['sku'] ?? ''),
                'fence_label' => $fenceLabel,
                'fence_slug' => $fenceSlug,
                'search_haystack' => $searchHaystack,
                'row_class' => $isOptional ? ' fc-entries-cart-table__row--optional' : '',
            ];
        }

        return [
            'entry_id' => $entryId,
            'item' => $item,
            'error' => $error,
            'admin_base' => $adminBase,
            'app_base' => $appBase,
            'list_url' => $listUrl,
            'planner_url' => is_array($item)
                ? self::plannerUrl($appBase, (string) ($item['planner_id'] ?? ''))
                : '#',
            'cart_item_count' => count($cartItems),
            'cart_total_qty' => $cartTotalQty,
            'cart_optional_count' => $cartOptionalCount,
            'cart_fence_options' => $cartFenceOptions,
            'detail_rows' => $detailRows,
            'copy_all_text' => $copyAllText,
            'cart_rows' => $cartRows,
            'has_cart_items' => $cartItems !== [],
            'cart_subtitle' => count($cartItems) > 0
                ? $cartTotalQty . ' units across ' . count($cartItems) . ' line' . (count($cartItems) === 1 ? '' : 's')
                    . ($cartOptionalCount > 0 ? ' · ' . $cartOptionalCount . ' optional' : '')
                : 'Saved products for this quote',
            'cart_lines_label' => count($cartItems) . ' line' . (count($cartItems) === 1 ? '' : 's'),
            'cart_units_label' => $cartTotalQty . ' total units',
        ];
    }
}
