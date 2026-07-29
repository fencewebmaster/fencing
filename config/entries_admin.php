<?php
/**
 * FC Admin — Entries page (server-rendered, GET forms).
 */

declare(strict_types=1);

require_once __DIR__ . '/planners.php';

function fc_entries_admin_route_slug(): string
{
    return 'planner-entries';
}

function fc_entries_admin_page_title(): string
{
    return 'Planner Entries';
}

function fc_entries_admin_detail_page_title(): string
{
    return 'Planner Entry';
}

function fc_entries_admin_list_path(string $adminBase): string
{
    return rtrim($adminBase, '/') . '/' . fc_entries_admin_route_slug();
}

function fc_entries_admin_fence_type_filter_label(array $options, array $selected): string
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

    if ($labels === []) {
        return 'All fence types';
    }

    if (count($labels) === 1) {
        return $labels[0];
    }

    if (count($labels) === 2) {
        return $labels[0] . ', ' . $labels[1];
    }

    return $labels[0] . ', ' . $labels[1] . ' (+' . (count($labels) - 2) . ')';
}

function fc_entries_admin_default_per_page(): int
{
    return 30;
}

/**
 * @return list<int>
 */
function fc_entries_admin_per_page_options(): array
{
    return [30, 50, 100, 250, 500];
}

/**
 * @return array<string, string>
 */
function fc_entries_admin_date_period_options(): array
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
function fc_entries_admin_date_field_options(): array
{
    return [
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ];
}

function fc_entries_admin_default_date_field(): string
{
    if (!function_exists('fc_system_get')) {
        require_once __DIR__ . '/system.php';
    }
    $field = (string) (fc_system_get()['entriesDefaultDateField'] ?? 'updated_at');
    $options = fc_entries_admin_date_field_options();

    return array_key_exists($field, $options) ? $field : 'updated_at';
}

/**
 * Default date period for Planner Entries when no date_period is in the URL.
 * Empty string means all dates.
 */
function fc_entries_admin_default_date_period(): string
{
    if (!function_exists('fc_system_resolved_entries_default_date_period')) {
        require_once __DIR__ . '/system.php';
    }

    return fc_system_resolved_entries_default_date_period();
}

function fc_entries_admin_normalize_date_field(string $value): string
{
    $value = trim($value);
    $options = fc_entries_admin_date_field_options();

    return array_key_exists($value, $options) ? $value : fc_entries_admin_default_date_field();
}

function fc_entries_admin_parse_date_input(string $value): ?DateTime
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if ($dt instanceof DateTime) {
        return $dt->setTime(0, 0, 0);
    }

    try {
        return (new DateTime($value))->setTime(0, 0, 0);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * @return array{from:string,to:string}|null
 */
function fc_entries_admin_date_bounds_for_period(string $period): ?array
{
    $options = fc_entries_admin_date_period_options();
    if (!array_key_exists($period, $options) || $period === 'custom') {
        return null;
    }

    $today = new DateTime('today');
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
function fc_entries_admin_parse_date_filter(string $period, string $from = '', string $to = ''): array
{
    $period = trim($period);
    if ($period === '') {
        return ['period' => '', 'from' => '', 'to' => '', 'bounds' => null];
    }

    $options = fc_entries_admin_date_period_options();
    if (!array_key_exists($period, $options)) {
        return ['period' => '', 'from' => '', 'to' => '', 'bounds' => null];
    }

    if ($period === 'custom') {
        $fromDt = fc_entries_admin_parse_date_input($from);
        $toDt = fc_entries_admin_parse_date_input($to);
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

    $bounds = fc_entries_admin_date_bounds_for_period($period);
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

function fc_entries_admin_date_filter_label(string $period, string $from = '', string $to = ''): string
{
    if ($period === '') {
        return 'All dates';
    }

    $options = fc_entries_admin_date_period_options();
    if ($period !== 'custom') {
        return $options[$period] ?? 'All dates';
    }

    if ($from === '' || $to === '') {
        return 'Custom Range';
    }

    try {
        $fromDt = new DateTime($from);
        $toDt = new DateTime($to);

        return $fromDt->format('M j, Y') . ' – ' . $toDt->format('M j, Y');
    } catch (Exception $e) {
        return 'Custom Range';
    }
}

/**
 * Display datetime for planner entries admin using System → Date display format.
 */
function fc_entries_admin_format_datetime($value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    try {
        $dt = new DateTime($value);
    } catch (Exception $e) {
        return $value;
    }

    $format = 'M. j, Y h:i A';
    if (!function_exists('fc_system_date_format_php')) {
        require_once __DIR__ . '/system.php';
    }
    if (function_exists('fc_system_date_format_php')) {
        $format = fc_system_date_format_php();
    }

    return $dt->format($format);
}

function fc_entries_admin_settings(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    require_once dirname(__DIR__) . '/data/settings.php';
    $loaded = true;
}

/**
 * @return array<string, string>
 */
function fc_entries_admin_timeframe_options(): array
{
    fc_entries_admin_settings();

    return fc_timeframe();
}

/**
 * @return array<string, string>
 */
function fc_entries_admin_state_options(): array
{
    fc_entries_admin_settings();

    return fc_state();
}

/**
 * @return list<array{slug:string,name:string}>
 */
function fc_entries_admin_fence_type_options(): array
{
    $catalog = fc_planners_fence_catalog();
    $options = [];

    foreach ($catalog as $slug => $fence) {
        if (!is_string($slug) || $slug === '') {
            continue;
        }
        $options[] = [
            'slug' => $slug,
            'name' => fc_fence_style_title_from_slug($slug, $catalog),
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
function fc_entries_admin_normalize_fence_types($raw): array
{
    if (!is_array($raw)) {
        $raw = trim((string) $raw) === '' ? [] : [trim((string) $raw)];
    }

    $valid = array_keys(fc_planners_fence_catalog());
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
function fc_entries_admin_device_options(): array
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
function fc_entries_admin_browser_options(): array
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
function fc_entries_admin_normalize_options(mixed $values, array $options): array
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

function fc_entries_admin_normalize_range_value(mixed $value): ?int
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
function fc_entries_admin_normalize_range(mixed $minimum, mixed $maximum): array
{
    $min = fc_entries_admin_normalize_range_value($minimum);
    $max = fc_entries_admin_normalize_range_value($maximum);
    if ($min !== null && $max !== null && $min > $max) {
        [$min, $max] = [$max, $min];
    }

    return ['min' => $min, 'max' => $max];
}

/**
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
function fc_entries_admin_parse_request(): array
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? ''));
    $timeframe = trim((string) ($_GET['timeframe'] ?? ''));
    $state = trim((string) ($_GET['state'] ?? ''));
    $postcode = substr(trim((string) ($_GET['postcode'] ?? '')), 0, 32);
    $devices = fc_entries_admin_normalize_options(
        $_GET['device'] ?? [],
        fc_entries_admin_device_options()
    );
    $browsers = fc_entries_admin_normalize_options(
        $_GET['browser'] ?? [],
        fc_entries_admin_browser_options()
    );
    $sections = fc_entries_admin_normalize_range(
        $_GET['sections_min'] ?? null,
        $_GET['sections_max'] ?? null
    );
    $quoteLoads = fc_entries_admin_normalize_range(
        $_GET['quote_loads_min'] ?? null,
        $_GET['quote_loads_max'] ?? null
    );
    $viewRaw = strtolower(trim((string) ($_GET['view'] ?? 'all')));
    $view = $viewRaw === 'trash' ? 'trash' : 'all';
    $fenceTypes = fc_entries_admin_normalize_fence_types($_GET['fence_type'] ?? []);
    $dateField = fc_entries_admin_normalize_date_field((string) ($_GET['date_field'] ?? fc_entries_admin_default_date_field()));
    // First visit (no date_period param) uses System → Date Settings default.
    $datePeriodRaw = array_key_exists('date_period', $_GET)
        ? (string) ($_GET['date_period'] ?? '')
        : fc_entries_admin_default_date_period();
    $dateFilter = fc_entries_admin_parse_date_filter(
        $datePeriodRaw,
        (string) ($_GET['date_from'] ?? ''),
        (string) ($_GET['date_to'] ?? '')
    );
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $defaultPerPage = fc_entries_admin_default_per_page();
    $perPageRaw = strtolower(trim((string) ($_GET['per_page'] ?? (string) $defaultPerPage)));

    fc_entries_admin_settings();

    if ($timeframe !== '' && !array_key_exists($timeframe, fc_timeframe())) {
        $timeframe = '';
    }
    if ($state !== '' && !array_key_exists($state, fc_state())) {
        $state = '';
    }

    $isAll = ($perPageRaw === 'all');
    $perPage = $defaultPerPage;

    if ($isAll) {
        $perPage = 0;
    } elseif (in_array((int) $perPageRaw, fc_entries_admin_per_page_options(), true)) {
        $perPage = (int) $perPageRaw;
    }

    $offset = $isAll ? 0 : ($page - 1) * $perPage;

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
        'page' => $page,
        'per_page' => $isAll ? 'all' : $perPage,
        'is_all' => $isAll,
        'offset' => $offset,
    ];
}

/**
 * Allow only same-origin relative planner-entries list URLs (not detail routes).
 */
function fc_entries_admin_sanitize_list_return_url(string $adminBase, string $candidate): string
{
    $fallback = fc_entries_admin_list_path($adminBase);
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

function fc_entries_admin_entry_url(string $adminBase, int $entryId, ?string $returnUrl = null): string
{
    if ($entryId <= 0) {
        return fc_entries_admin_list_path($adminBase);
    }

    $url = fc_entries_admin_list_path($adminBase) . '/' . $entryId;
    if ($returnUrl === null || $returnUrl === '') {
        return $url;
    }

    $safeReturn = fc_entries_admin_sanitize_list_return_url($adminBase, $returnUrl);

    return $url . '?return=' . rawurlencode($safeReturn);
}

/**
 * @param array<string, mixed> $request
 */
function fc_entries_admin_query_from_request(array $request): array
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
        'date_field' => $request['date_field'] ?? fc_entries_admin_default_date_field(),
        'page' => $request['page'] ?? 1,
        'per_page' => $request['per_page'] ?? fc_entries_admin_default_per_page(),
    ];

    if (($query['view'] ?? 'all') === 'all') {
        unset($query['view']);
    }

    if (($query['date_field'] ?? fc_entries_admin_default_date_field()) === fc_entries_admin_default_date_field()) {
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
function fc_entries_admin_build_query_string(array $query): string
{
    $fenceTypes = [];
    if (isset($query['fence_type'])) {
        $fenceTypes = fc_entries_admin_normalize_fence_types($query['fence_type']);
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
        if ($key === 'per_page' && (string) $value === (string) fc_entries_admin_default_per_page()) {
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
 * @param array<string, scalar|null|list<string>> $overrides
 */
function fc_entries_admin_url(string $adminBase, array $overrides = []): string
{
    $base = fc_entries_admin_list_path($adminBase);
    $query = array_merge(
        fc_entries_admin_query_from_request(fc_entries_admin_parse_request()),
        $overrides
    );

    if (isset($overrides['fence_types']) && is_array($overrides['fence_types'])) {
        $query['fence_type'] = $overrides['fence_types'];
        unset($query['fence_types']);
    }

    $qs = fc_entries_admin_build_query_string($query);

    return $qs === '' ? $base : $base . '?' . $qs;
}

/**
 * @return list<int|string>
 */
function fc_entries_admin_pagination_window(int $current, int $total): array
{
    if ($total <= 7) {
        $pages = [];
        for ($i = 1; $i <= $total; $i++) {
            $pages[] = $i;
        }
        return $pages;
    }

    $pages = [1];
    $start = max(2, $current - 1);
    $end = min($total - 1, $current + 1);

    if ($start > 2) {
        $pages[] = '…';
    }
    for ($p = $start; $p <= $end; $p++) {
        $pages[] = $p;
    }
    if ($end < $total - 1) {
        $pages[] = '…';
    }
    $pages[] = $total;

    return $pages;
}

function fc_entries_admin_status_class(string $status): string
{
    return strtolower($status) === 'planning'
        ? 'fc-entries-status fc-entries-status--planning'
        : 'fc-entries-status';
}

/**
 * @return array{
 *   request: array<string, mixed>,
 *   list: array<string, mixed>,
 *   total_pages: int,
 *   count_label: string,
 *   error: string
 * }
 */
function fc_entries_admin_page_data(string $adminBase, string $appBase): array
{
    $request = fc_entries_admin_parse_request();
    $limit = $request['is_all'] ? 0 : (int) $request['per_page'];

    $list = fc_planners_list_entries(
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
        (string) ($request['date_field'] ?? fc_entries_admin_default_date_field()),
        (string) ($request['postcode'] ?? ''),
        is_array($request['device'] ?? null) ? $request['device'] : [],
        is_array($request['browser'] ?? null) ? $request['browser'] : [],
        $request['sections_min'] ?? null,
        $request['sections_max'] ?? null,
        $request['quote_loads_min'] ?? null,
        $request['quote_loads_max'] ?? null
    );

    $statuses = fc_planners_get_statuses();
    $timeframes = fc_entries_admin_timeframe_options();
    $states = fc_entries_admin_state_options();
    $fenceTypeOptions = fc_entries_admin_fence_type_options();
    $viewCounts = fc_planners_trash_view_counts();

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
        header('Location: ' . fc_entries_admin_url($adminBase, ['page' => $totalPages]));
        exit;
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

    $view = (string) ($request['view'] ?? 'all');

    return [
        'request' => $request,
        'list' => $list,
        'items' => $items,
        'statuses' => $statuses,
        'timeframes' => $timeframes,
        'states' => $states,
        'device_options' => fc_entries_admin_device_options(),
        'browser_options' => fc_entries_admin_browser_options(),
        'fence_type_options' => $fenceTypeOptions,
        'view' => $view,
        'view_counts' => $viewCounts,
        'total' => $total,
        'total_pages' => $totalPages,
        'count_label' => $countLabel,
        'error' => $error,
        'admin_base' => $adminBase,
        'app_base' => rtrim($appBase, '/'),
        'api_url' => 'api.php?module=entries',
        'url' => static function (array $overrides = []) use ($adminBase): string {
            return fc_entries_admin_url($adminBase, $overrides);
        },
        'entry_url' => static function (int $entryId) use ($adminBase): string {
            return fc_entries_admin_entry_url($adminBase, $entryId, fc_entries_admin_url($adminBase));
        },
    ];
}

/**
 * @return array{
 *   entry_id:int,
 *   item:array<string,mixed>|null,
 *   error:string,
 *   admin_base:string,
 *   app_base:string,
 *   list_url:string
 * }
 */
function fc_entries_admin_detail_page_data(string $adminBase, string $appBase, int $entryId): array
{
    $error = '';
    $item = null;

    if ($entryId <= 0) {
        $error = 'Entry ID required.';
    } else {
        $result = fc_planners_get_entry_by_id($entryId);
        if (!empty($result['ok']) && isset($result['item']) && is_array($result['item'])) {
            $item = $result['item'];
        } else {
            $error = (string) ($result['error'] ?? 'Entry not found.');
        }
    }

    $returnCandidate = isset($_GET['return']) ? (string) $_GET['return'] : '';

    return [
        'entry_id' => $entryId,
        'item' => $item,
        'error' => $error,
        'admin_base' => $adminBase,
        'app_base' => rtrim($appBase, '/'),
        'list_url' => fc_entries_admin_sanitize_list_return_url($adminBase, $returnCandidate),
    ];
}

function fc_entries_admin_redirect_legacy_detail(string $adminBase): void
{
    $detail = trim((string) ($_GET['detail'] ?? ''));
    if ($detail === '') {
        return;
    }

    $entryId = ctype_digit($detail) ? (int) $detail : (int) (fc_planners_entry_id_for_planner($detail) ?? 0);
    if ($entryId <= 0) {
        return;
    }

    header('Location: ' . fc_entries_admin_entry_url($adminBase, $entryId), true, 301);
    exit;
}

function fc_entries_admin_planner_url(string $appBase, string $plannerId): string
{
    $plannerId = trim($plannerId);
    if ($plannerId === '') {
        return '#';
    }

    return rtrim($appBase, '/') . '?qid=' . rawurlencode($plannerId);
}

function fc_entries_admin_planner_share_url(string $appBase, string $plannerId): string
{
    $plannerId = trim($plannerId);
    if ($plannerId === '') {
        return '';
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

    return $scheme . '://' . $host . fc_entries_admin_planner_url($appBase, $plannerId);
}

function fc_entries_admin_ipinfo_url(string $ip): string
{
    $ip = trim($ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }

    return 'https://ipinfo.io/' . rawurlencode($ip);
}

function fc_entries_admin_device_icon(string $device): string
{
    return match (strtolower(trim($device))) {
        'desktop' => 'fa-solid fa-desktop',
        'mobile' => 'fa-solid fa-mobile-screen',
        'tablet' => 'fa-solid fa-tablet-screen-button',
        'bot' => 'fa-solid fa-robot',
        default => 'fa-solid fa-circle-question',
    };
}

function fc_entries_admin_browser_name(string $userAgent): string
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

function fc_entries_admin_browser_icon(string $browser): string
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

function fc_entries_admin_cart_image_url(string $appBase, string $image): string
{
    $image = trim($image);
    if ($image === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    if (function_exists('fc_cart_display_image_url')) {
        $image = fc_cart_display_image_url($image);
    }

    if ($image !== '' && $image[0] === '/') {
        return $image;
    }

    return rtrim($appBase, '/') . '/' . ltrim($image, '/');
}

function fc_entries_admin_extra_items(mixed $raw): array
{
    fc_entries_admin_settings();

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

    if (!function_exists('fc_extra_needed')) {
        require_once __DIR__ . '/../data/settings.php';
    }

    $labels = [];
    foreach ($items as $slug) {
        if ($slug === '' || $slug === 'nothing') {
            continue;
        }

        $label = fc_extra_needed($slug);
        $labels[] = is_string($label) && $label !== '' ? $label : $slug;
    }

    return $labels !== [] ? $labels : ['Nothing Extra, Just Fencing'];
}

function fc_entries_admin_extra_label(mixed $raw): string
{
    return implode(', ', fc_entries_admin_extra_items($raw));
}

function fc_entries_admin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fc_entries_admin_cell(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return fc_entries_admin_h((string) $value);
}

/**
 * @param array<string, mixed> $page
 * @return array<string, mixed>
 */
function fc_entries_admin_build_list_view(array $page): array
{
    $req = is_array($page['request'] ?? null) ? $page['request'] : [];
    $items = is_array($page['items'] ?? null) ? $page['items'] : [];
    $timeframes = is_array($page['timeframes'] ?? null) ? $page['timeframes'] : [];
    $fenceTypeOptions = is_array($page['fence_type_options'] ?? null) ? $page['fence_type_options'] : [];
    $appBase = (string) ($page['app_base'] ?? '');
    $url = $page['url'] ?? null;
    $entryUrl = $page['entry_url'] ?? null;

    $selectedFenceTypes = is_array($req['fence_types'] ?? null) ? $req['fence_types'] : [];
    $datePeriod = (string) ($req['date_period'] ?? '');
    $dateFrom = (string) ($req['date_from'] ?? '');
    $dateTo = (string) ($req['date_to'] ?? '');
    $dateField = fc_entries_admin_normalize_date_field((string) ($req['date_field'] ?? fc_entries_admin_default_date_field()));

    $page['selected_fence_types'] = $selectedFenceTypes;
    $page['date_period'] = $datePeriod;
    $page['date_from'] = $dateFrom;
    $page['date_to'] = $dateTo;
    $page['date_field'] = $dateField;
    $page['date_field_options'] = fc_entries_admin_date_field_options();
    $page['date_period_options'] = fc_entries_admin_date_period_options();
    $page['date_filter_label'] = fc_entries_admin_date_filter_label($datePeriod, $dateFrom, $dateTo);
    $page['date_column_label'] = $dateField === 'updated_at' ? 'Updated At' : 'Created At';
    $page['current_page'] = (int) ($req['page'] ?? 1);
    $page['is_all'] = !empty($req['is_all']);
    $page['has_active_filters'] = ($req['q'] ?? '') !== ''
        || ($req['status'] ?? '') !== ''
        || ($req['timeframe'] ?? '') !== ''
        || ($req['state'] ?? '') !== ''
        || ($req['postcode'] ?? '') !== ''
        || (is_array($req['device'] ?? null) && $req['device'] !== [])
        || (is_array($req['browser'] ?? null) && $req['browser'] !== [])
        || ($req['sections_min'] ?? null) !== null
        || ($req['sections_max'] ?? null) !== null
        || ($req['quote_loads_min'] ?? null) !== null
        || ($req['quote_loads_max'] ?? null) !== null
        || $datePeriod !== ''
        || $selectedFenceTypes !== [];
    $page['fence_type_filter_label'] = fc_entries_admin_fence_type_filter_label($fenceTypeOptions, $selectedFenceTypes);
    $page['form_action'] = fc_entries_admin_route_slug();
    $view = ((string) ($req['view'] ?? 'all')) === 'trash' ? 'trash' : 'all';
    $page['view'] = $view;
    $viewCounts = is_array($page['view_counts'] ?? null) ? $page['view_counts'] : ['all' => 0, 'trash' => 0];
    $page['tabs'] = [
        [
            'key' => 'all',
            'label' => 'All',
            'count' => (int) ($viewCounts['all'] ?? 0),
            'is_active' => $view === 'all',
            'href' => is_callable($url) ? (string) $url(['view' => 'all', 'page' => 1]) : '?view=all',
        ],
        [
            'key' => 'trash',
            'label' => 'Trash',
            'count' => (int) ($viewCounts['trash'] ?? 0),
            'is_active' => $view === 'trash',
            'href' => is_callable($url) ? (string) $url(['view' => 'trash', 'page' => 1]) : '?view=trash',
        ],
    ];
    $page['clear_filters_url'] = is_callable($url)
        ? (string) $url([
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
            'date_period' => fc_entries_admin_default_date_period(),
            'date_from' => '',
            'date_to' => '',
            'date_field' => fc_entries_admin_default_date_field(),
            'fence_types' => [],
            'view' => $view,
            'page' => '',
        ])
        : '';
    $page['show_per_page_hidden'] = (string) ($req['per_page'] ?? '') === 'all'
        || (int) ($req['per_page'] ?? 0) !== fc_entries_admin_default_per_page();
    $page['per_page_options'] = fc_entries_admin_per_page_options();
    $page['filter_hidden_html'] = fc_entries_admin_filter_hidden_html($req, $selectedFenceTypes);
    $page['api_url'] = (string) ($page['api_url'] ?? 'api.php?module=entries');
    $page['is_trash_view'] = $view === 'trash';
    $bulkOptions = $view === 'trash'
        ? [
            ['value' => 'export', 'label' => 'Export as JSON', 'perm' => 'planner_entries.import_export'],
            ['value' => 'restore', 'label' => 'Restore', 'perm' => 'planner_entries.trash_delete_restore'],
            ['value' => 'delete', 'label' => 'Delete permanently', 'perm' => 'planner_entries.trash_delete_restore'],
        ]
        : [
            ['value' => 'export', 'label' => 'Export as JSON', 'perm' => 'planner_entries.import_export'],
            ['value' => 'trash', 'label' => 'Move to trash', 'perm' => 'planner_entries.trash_delete_restore'],
        ];
    $page['bulk_action_options'] = [];
    foreach ($bulkOptions as $option) {
        $perm = (string) ($option['perm'] ?? '');
        if ($perm !== '' && function_exists('fc_auth_user_can') && !fc_auth_user_can($perm)) {
            continue;
        }
        $page['bulk_action_options'][] = [
            'value' => (string) ($option['value'] ?? ''),
            'label' => (string) ($option['label'] ?? ''),
        ];
    }
    $page['csrf'] = function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '';
    $page['can_import'] = !function_exists('fc_auth_user_can') || fc_auth_user_can('planner_entries.import_export');
    $page['can_view'] = !function_exists('fc_auth_user_can') || fc_auth_user_can('planner_entries.view');
    $canView = !empty($page['can_view']);

    $fenceOptions = [];
    foreach ($fenceTypeOptions as $fenceOption) {
        if (!is_array($fenceOption)) {
            continue;
        }
        $fenceSlug = (string) ($fenceOption['slug'] ?? '');
        $fenceOptions[] = [
            'slug'       => $fenceSlug,
            'name'       => (string) ($fenceOption['name'] ?? $fenceSlug),
            'is_checked' => in_array($fenceSlug, $selectedFenceTypes, true),
        ];
    }
    $page['fence_options'] = $fenceOptions;

    $tableRows = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $plannerId = (string) ($item['planner_id'] ?? '');
        $entryId = (int) ($item['id'] ?? 0);
        $fenceLabel = (string) ($item['fence_type_label'] ?? '');
        if ($fenceLabel === '') {
            $fenceLabel = fc_planners_fence_type_label(
                (string) ($item['fence_type'] ?? ''),
                null,
                (int) ($item['section_count'] ?? 0)
            );
        }
        $timeframeSlug = (string) ($item['timeframe'] ?? '');
        $status = (string) ($item['status'] ?? '');
        $device = trim((string) ($item['device'] ?? ''));
        $browser = fc_entries_admin_browser_name((string) ($item['user_agent'] ?? ''));
        $dateValue = $dateField === 'updated_at'
            ? ($item['updated_at'] ?? '')
            : ($item['created_at'] ?? '');
        $canOpen = $canView && $entryId > 0 && is_callable($entryUrl);
        $tableRows[] = [
            'id'                 => $entryId,
            'planner_id'         => $plannerId,
            'can_open'           => $canOpen,
            'row_href'           => $canOpen ? (string) $entryUrl($entryId) : '',
            'planner_share_url'  => fc_entries_admin_planner_share_url($appBase, $plannerId),
            'status'             => $status,
            'status_class'       => fc_entries_admin_status_class($status),
            'name'               => (string) ($item['name'] ?? ''),
            'email'              => (string) ($item['email'] ?? ''),
            'mobile'             => (string) ($item['mobile'] ?? ''),
            'fence_label'        => $fenceLabel,
            'timeframe_label'    => (string) ($timeframes[$timeframeSlug] ?? $timeframeSlug),
            'section_count'      => (string) ($item['section_count'] ?? ''),
            'state'              => (string) ($item['state'] ?? ''),
            'device'             => $device !== '' ? $device : 'Unknown',
            'device_icon'        => fc_entries_admin_device_icon($device),
            'browser'            => $browser !== '' ? $browser : 'Unknown',
            'browser_icon'       => fc_entries_admin_browser_icon($browser),
            'date_at'            => fc_entries_admin_format_datetime($dateValue),
        ];
    }
    $page['table_rows'] = $tableRows;
    $page['has_table_rows'] = $tableRows !== [];

    $totalPages = (int) ($page['total_pages'] ?? 1);
    $currentPage = (int) ($req['page'] ?? 1);
    $pagination = [
        'show'     => !$page['is_all'] && $totalPages > 1,
        'pages'    => fc_entries_admin_pagination_window($currentPage, $totalPages),
        'prev_url' => ($currentPage > 1 && is_callable($url)) ? (string) $url(['page' => $currentPage - 1]) : '',
        'next_url' => ($currentPage < $totalPages && is_callable($url)) ? (string) $url(['page' => $currentPage + 1]) : '',
    ];
    $page['pagination'] = $pagination;
    $page['pagination_links'] = [];
    foreach ($pagination['pages'] as $pageNum) {
        if ($pageNum === '…') {
            $page['pagination_links'][] = ['type' => 'ellipsis'];
            continue;
        }
        $num = (int) $pageNum;
        $page['pagination_links'][] = [
            'type'    => $num === $currentPage ? 'current' : 'link',
            'label'   => (string) $num,
            'url'     => is_callable($url) ? (string) $url(['page' => $num]) : '',
        ];
    }

    return $page;
}

/**
 * @param array<string, mixed> $req
 * @param list<string> $selectedFenceTypes
 */
function fc_entries_admin_filter_hidden_html(array $req, array $selectedFenceTypes, array $exclude = []): string
{
    $parts = [];
    if (!in_array('q', $exclude, true) && ($req['q'] ?? '') !== '') {
        $parts[] = '<input type="hidden" name="q" value="' . fc_entries_admin_h((string) $req['q']) . '">';
    }
    if (!in_array('status', $exclude, true) && ($req['status'] ?? '') !== '') {
        $parts[] = '<input type="hidden" name="status" value="' . fc_entries_admin_h((string) $req['status']) . '">';
    }
    if (!in_array('timeframe', $exclude, true) && ($req['timeframe'] ?? '') !== '') {
        $parts[] = '<input type="hidden" name="timeframe" value="' . fc_entries_admin_h((string) $req['timeframe']) . '">';
    }
    if (!in_array('state', $exclude, true) && ($req['state'] ?? '') !== '') {
        $parts[] = '<input type="hidden" name="state" value="' . fc_entries_admin_h((string) $req['state']) . '">';
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
                . fc_entries_admin_h((string) $value) . '">';
        }
    }
    foreach (['device', 'browser'] as $field) {
        if (in_array($field, $exclude, true) || !is_array($req[$field] ?? null)) {
            continue;
        }
        foreach ($req[$field] as $value) {
            $parts[] = '<input type="hidden" name="' . $field . '[]" value="'
                . fc_entries_admin_h((string) $value) . '">';
        }
    }
    if (!in_array('view', $exclude, true) && ($req['view'] ?? 'all') === 'trash') {
        $parts[] = '<input type="hidden" name="view" value="trash">';
    }
    if (!in_array('date_period', $exclude, true) && ($req['date_period'] ?? '') !== '') {
        $parts[] = '<input type="hidden" name="date_period" value="' . fc_entries_admin_h((string) $req['date_period']) . '">';
    }
    if (!in_array('date_from', $exclude, true) && ($req['date_from'] ?? '') !== '') {
        $parts[] = '<input type="hidden" name="date_from" value="' . fc_entries_admin_h((string) $req['date_from']) . '">';
    }
    if (!in_array('date_to', $exclude, true) && ($req['date_to'] ?? '') !== '') {
        $parts[] = '<input type="hidden" name="date_to" value="' . fc_entries_admin_h((string) $req['date_to']) . '">';
    }
    if (
        !in_array('date_field', $exclude, true)
        && fc_entries_admin_normalize_date_field((string) ($req['date_field'] ?? fc_entries_admin_default_date_field())) !== fc_entries_admin_default_date_field()
    ) {
        $parts[] = '<input type="hidden" name="date_field" value="' . fc_entries_admin_h(
            fc_entries_admin_normalize_date_field((string) ($req['date_field'] ?? fc_entries_admin_default_date_field()))
        ) . '">';
    }
    if (!in_array('fence_type', $exclude, true)) {
        foreach ($selectedFenceTypes as $fenceSlug) {
            $parts[] = '<input type="hidden" name="fence_type[]" value="' . fc_entries_admin_h((string) $fenceSlug) . '">';
        }
    }

    return implode('', $parts);
}

/**
 * @param array<string, mixed> $page
 * @return array<string, mixed>
 */
function fc_entries_admin_build_detail_view(array $page): array
{
    $item = is_array($page['item'] ?? null) ? $page['item'] : null;
    $appBase = (string) ($page['app_base'] ?? '');
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
    foreach (fc_entries_admin_fence_type_options() as $fenceOption) {
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
    ];

    $detailRows = [];
    if (is_array($item)) {
        foreach ($detailFields as $fieldKey => $fieldLabel) {
            $extraItems = null;
            $raw = $item[$fieldKey] ?? '';
            if ($fieldKey === 'fence_type') {
                $raw = (string) ($item['fence_type_label'] ?? '');
                if ($raw === '') {
                    $raw = fc_planners_fence_type_label(
                        (string) ($item['fence_type'] ?? ''),
                        null,
                        (int) ($item['section_count'] ?? 0)
                    );
                }
            } elseif ($fieldKey === 'timeframe') {
                $slug = trim((string) $raw);
                if ($slug !== '') {
                    fc_entries_admin_settings();
                    $raw = fc_timeframe($slug) ?: $slug;
                }
            } elseif ($fieldKey === 'extra') {
                $extraItems = fc_entries_admin_extra_items($raw);
                $display = implode(', ', $extraItems);
                $raw = $display;
            } elseif ($fieldKey === 'user_agent') {
                $raw = fc_entries_admin_browser_name((string) $raw);
            } elseif (in_array($fieldKey, ['created_at', 'updated_at'], true)) {
                $raw = fc_entries_admin_format_datetime($raw);
            } elseif ($fieldKey === 'quote_load_count') {
                $raw = (string) max(0, (int) $raw);
            }

            $display = ($raw === null || $raw === '') ? '—' : (string) $raw;
            $ipAddress = $fieldKey === 'ip_address' ? trim((string) $raw) : '';
            $ipinfoUrl = $ipAddress !== '' ? fc_entries_admin_ipinfo_url($ipAddress) : '';
            $deviceIcon = ($fieldKey === 'device' && $display !== '—')
                ? fc_entries_admin_device_icon($display)
                : '';
            $detailRows[] = [
                'key'          => $fieldKey,
                'label'        => $fieldLabel,
                'display'      => $display,
                'copy'         => $display,
                'display_items' => is_array($extraItems) ? $extraItems : null,
                'status_class' => $fieldKey === 'status' ? fc_entries_admin_status_class((string) $raw) : '',
                'planner_url'  => ($fieldKey === 'planner_id' && $raw !== '')
                    ? fc_entries_admin_planner_url($appBase, (string) $raw)
                    : '',
                'ipinfo_url'   => $ipinfoUrl,
                'device_icon'  => $deviceIcon,
                'is_link'      => (
                    in_array($fieldKey, ['planner_id', 'site_url', 'email'], true) && $raw !== ''
                ) || ($fieldKey === 'ip_address' && $ipinfoUrl !== ''),
                'link_type'    => $fieldKey,
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
        $imageUrl = fc_entries_admin_cart_image_url($appBase, (string) ($cartItem['image'] ?? ''));
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
            'image_url'        => $imageUrl,
            'gallery_caption'  => $productName !== '' ? $productName : 'Product image',
            'is_optional'      => $isOptional,
            'row_qty'          => $rowQty,
            'optional_qty'     => (int) ($cartItem['qty'] ?? 0),
            'name'             => (string) ($cartItem['name'] ?? ''),
            'sku'              => (string) ($cartItem['sku'] ?? ''),
            'fence_label'      => $fenceLabel,
            'fence_slug'       => $fenceSlug,
            'search_haystack'  => $searchHaystack,
            'row_class'        => $isOptional ? ' fc-entries-cart-table__row--optional' : '',
        ];
    }

    $page['planner_url'] = is_array($item)
        ? fc_entries_admin_planner_url($appBase, (string) ($item['planner_id'] ?? ''))
        : '#';
    $page['cart_item_count'] = count($cartItems);
    $page['cart_total_qty'] = $cartTotalQty;
    $page['cart_optional_count'] = $cartOptionalCount;
    $page['cart_fence_options'] = $cartFenceOptions;
    $page['detail_rows'] = $detailRows;
    $page['copy_all_text'] = $copyAllText;
    $page['cart_rows'] = $cartRows;
    $page['has_cart_items'] = $cartItems !== [];
    $page['cart_subtitle'] = count($cartItems) > 0
        ? $cartTotalQty . ' units across ' . count($cartItems) . ' line' . (count($cartItems) === 1 ? '' : 's')
            . ($cartOptionalCount > 0 ? ' · ' . $cartOptionalCount . ' optional' : '')
        : 'Saved products for this quote';
    $page['cart_lines_label'] = count($cartItems) . ' line' . (count($cartItems) === 1 ? '' : 's');
    $page['cart_units_label'] = $cartTotalQty . ' total units';

    return $page;
}
