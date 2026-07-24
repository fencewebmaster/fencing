<?php
/**
 * FC Admin — Dashboard analytics and page data.
 */

declare(strict_types=1);

require_once __DIR__ . '/planners.php';

function fc_dashboard_admin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fc_dashboard_admin_date_expr(): string
{
    return 'COALESCE(created_at, updated_at)';
}

/**
 * @return array{ok:bool,error?:string,table?:string}
 */
function fc_dashboard_admin_db_ctx(): array
{
    try {
        $ctx = fc_planners_open_db();

        return [
            'ok' => true,
            'conn' => $ctx['conn'],
            'table' => $ctx['table'],
        ];
    } catch (\Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function fc_dashboard_admin_scalar_count(mysqli $conn, string $table, string $where = '1=1', string $types = '', array $params = []): int
{
    $sql = 'SELECT COUNT(*) AS c FROM `' . $conn->real_escape_string($table) . '` WHERE ' . $where;
    if ($types === '') {
        $result = $conn->query($sql);

        return $result ? (int) ($result->fetch_object()->c ?? 0) : 0;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        $stmt->close();

        return 0;
    }
    $result = $stmt->get_result();
    $count = $result ? (int) ($result->fetch_object()->c ?? 0) : 0;
    $stmt->close();

    return $count;
}

/**
 * Count distinct non-empty values for a column (optionally scoped by WHERE).
 */
function fc_dashboard_admin_distinct_count(
    mysqli $conn,
    string $table,
    string $columnExpr,
    string $where = '1=1',
    string $types = '',
    array $params = []
): int {
    $sql = 'SELECT COUNT(DISTINCT ' . $columnExpr . ') AS c FROM `'
        . $conn->real_escape_string($table) . '` WHERE ' . $where;
    if ($types === '') {
        $result = $conn->query($sql);

        return $result ? (int) ($result->fetch_object()->c ?? 0) : 0;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        $stmt->close();

        return 0;
    }
    $result = $stmt->get_result();
    $count = $result ? (int) ($result->fetch_object()->c ?? 0) : 0;
    $stmt->close();

    return $count;
}

/**
 * @return array{period:string,from:string,to:string,bounds:?array{from:string,to:string},label:string,cache_key:string}
 */
function fc_dashboard_admin_parse_chart_date_filter(string $period = '', string $from = '', string $to = ''): array
{
    if (!function_exists('fc_entries_admin_parse_date_filter')) {
        require_once __DIR__ . '/entries_admin.php';
    }

    $parsed = fc_entries_admin_parse_date_filter($period, $from, $to);
    $label = fc_entries_admin_date_filter_label(
        (string) ($parsed['period'] ?? ''),
        (string) ($parsed['from'] ?? ''),
        (string) ($parsed['to'] ?? '')
    );

    $cacheKey = 'charts_' . md5(
        (string) ($parsed['period'] ?? '') . "\0"
        . (string) ($parsed['from'] ?? '') . "\0"
        . (string) ($parsed['to'] ?? '')
    );

    return [
        'period' => (string) ($parsed['period'] ?? ''),
        'from' => (string) ($parsed['from'] ?? ''),
        'to' => (string) ($parsed['to'] ?? ''),
        'bounds' => $parsed['bounds'] ?? null,
        'label' => $label,
        'cache_key' => $cacheKey,
    ];
}

/**
 * @param ?array{from:string,to:string} $bounds
 * @return array{clause:string,types:string,params:list<string>}
 */
function fc_dashboard_admin_date_bounds_clause(string $dateExpr, ?array $bounds): array
{
    if (!is_array($bounds) || empty($bounds['from']) || empty($bounds['to'])) {
        return ['clause' => '', 'types' => '', 'params' => []];
    }

    return [
        'clause' => $dateExpr . ' >= ? AND ' . $dateExpr . ' <= ?',
        'types' => 'ss',
        'params' => [(string) $bounds['from'], (string) $bounds['to']],
    ];
}

/**
 * @param ?array{from:string,to:string} $bounds
 * @return list<array{label:string,count:int}>
 */
function fc_dashboard_admin_group_count(
    mysqli $conn,
    string $table,
    string $column,
    int $limit = 12,
    string $extraWhere = '',
    ?array $bounds = null
): array {
    $safeCol = preg_replace('/[^a-z0-9_]+/i', '', $column) ?: $column;
    $dateExpr = fc_dashboard_admin_date_expr();
    $dateFilter = fc_dashboard_admin_date_bounds_clause($dateExpr, $bounds);

    $where = $safeCol . " IS NOT NULL AND TRIM(" . $safeCol . ") <> ''";
    if ($extraWhere !== '') {
        $where .= ' AND (' . $extraWhere . ')';
    }
    if ($dateFilter['clause'] !== '') {
        $where .= ' AND (' . $dateFilter['clause'] . ')';
    }

    $sql = 'SELECT ' . $safeCol . ' AS label, COUNT(*) AS c FROM `'
        . $conn->real_escape_string($table) . '` WHERE ' . $where
        . ' GROUP BY ' . $safeCol . ' ORDER BY c DESC LIMIT ' . max(1, min(50, $limit));

    $rows = [];
    if ($dateFilter['types'] === '') {
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_object()) {
                $rows[] = [
                    'label' => (string) ($row->label ?? ''),
                    'count' => (int) ($row->c ?? 0),
                ];
            }
        }

        return $rows;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param($dateFilter['types'], ...$dateFilter['params']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_object()) {
            $rows[] = [
                'label' => (string) ($row->label ?? ''),
                'count' => (int) ($row->c ?? 0),
            ];
        }
    }
    $stmt->close();

    return $rows;
}

/**
 * @return array<string, mixed>
 */
function fc_dashboard_admin_summary_stats(): array
{
    $ctx = fc_dashboard_admin_db_ctx();
    if (!$ctx['ok']) {
        return [
            'ok' => false,
            'error' => $ctx['error'] ?? 'Database unavailable.',
        ];
    }

    /** @var mysqli $conn */
    $conn = $ctx['conn'];
    $table = (string) $ctx['table'];
    $dateExpr = fc_dashboard_admin_date_expr();
    $now = new DateTime('now');
    $today = $now->format('Y-m-d');
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
    $weekStart = (clone $now)->modify('monday this week')->format('Y-m-d 00:00:00');
    $monthStart = $now->format('Y-m-01 00:00:00');
    $yearStart = $now->format('Y-01-01 00:00:00');

    $plannerWhere = "planner_id IS NOT NULL AND TRIM(planner_id) <> ''";
    $emailWhere = "email IS NOT NULL AND TRIM(email) <> ''";
    $plannerCol = 'planner_id';
    $emailCol = 'LOWER(TRIM(email))';

    $periods = [
        'today' => [
            'where' => 'DATE(' . $dateExpr . ') = ?',
            'types' => 's',
            'params' => [$today],
        ],
        'yesterday' => [
            'where' => 'DATE(' . $dateExpr . ') = ?',
            'types' => 's',
            'params' => [$yesterday],
        ],
        'week' => [
            'where' => $dateExpr . ' >= ?',
            'types' => 's',
            'params' => [$weekStart],
        ],
        'month' => [
            'where' => $dateExpr . ' >= ?',
            'types' => 's',
            'params' => [$monthStart],
        ],
        'year' => [
            'where' => $dateExpr . ' >= ?',
            'types' => 's',
            'params' => [$yearStart],
        ],
    ];

    $entries = [];
    $customers = [];
    foreach ($periods as $key => $period) {
        $entries[$key] = fc_dashboard_admin_distinct_count(
            $conn,
            $table,
            $plannerCol,
            $plannerWhere . ' AND ' . $period['where'],
            $period['types'],
            $period['params']
        );
        $customers[$key] = fc_dashboard_admin_distinct_count(
            $conn,
            $table,
            $emailCol,
            $emailWhere . ' AND ' . $period['where'],
            $period['types'],
            $period['params']
        );
    }

    $payload = [
        'ok' => true,
        'today_entries' => $entries['today'],
        'yesterday_entries' => $entries['yesterday'],
        'week_entries' => $entries['week'],
        'month_entries' => $entries['month'],
        'year_entries' => $entries['year'],
        'today_customers' => $customers['today'],
        'yesterday_customers' => $customers['yesterday'],
        'week_customers' => $customers['week'],
        'month_customers' => $customers['month'],
        'year_customers' => $customers['year'],
    ];

    fc_planners_close_db($conn);

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function fc_dashboard_admin_system_counts(): array
{
    $storeTotal = 0;
    $systemTotal = 0;
    $productsCsv = dirname(__DIR__) . '/data/products.csv';
    if (is_readable($productsCsv)) {
        $lines = 0;
        $handle = fopen($productsCsv, 'r');
        if ($handle !== false) {
            fgetcsv($handle);
            while (fgetcsv($handle) !== false) {
                $lines++;
            }
            fclose($handle);
        }
        $storeTotal = $lines;
    }

    foreach (glob(dirname(__DIR__) . '/data/wc-products-*.csv') ?: [] as $csvFile) {
        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            continue;
        }
        fgetcsv($handle);
        while (fgetcsv($handle) !== false) {
            $systemTotal++;
        }
        fclose($handle);
    }

    $fenceStyles = count(glob(dirname(__DIR__) . '/data/fences/*.php') ?: []);

    $galleryItems = 0;
    $uploadDir = dirname(__DIR__) . '/assets/uploads';
    if (is_dir($uploadDir)) {
        foreach (glob($uploadDir . '/*') ?: [] as $file) {
            if (is_file($file) && !str_starts_with(basename($file), '.')) {
                $galleryItems++;
            }
        }
    }

    $adminUsers = 0;
    if (function_exists('fc_auth_db')) {
        $db = fc_auth_db();
        if ($db instanceof mysqli) {
            $usersTable = function_exists('fc_auth_users_table') ? fc_auth_users_table() : 'wp_users';
            $result = $db->query('SELECT COUNT(*) AS c FROM `' . $db->real_escape_string($usersTable) . '`');
            if ($result) {
                $adminUsers = (int) ($result->fetch_object()->c ?? 0);
            }
            $db->close();
        }
    }

    $payload = [
        'ok' => true,
        'store_products' => $storeTotal,
        'system_products' => $systemTotal,
        'total_products' => $storeTotal + $systemTotal,
        'fence_styles' => $fenceStyles,
        'gallery_items' => $galleryItems,
        'registered_users' => $adminUsers,
        'project_plan_downloads' => null,
    ];

    return $payload;
}

/**
 * @param ?array{from:string,to:string} $bounds
 * @return list<array<string, mixed>>
 */
function fc_dashboard_admin_recent_entries_query(mysqli $conn, string $table, int $limit = 8, ?array $bounds = null): array
{
    $dateExpr = fc_dashboard_admin_date_expr();
    $dateClause = fc_dashboard_admin_date_bounds_clause($dateExpr, $bounds);
    $sql = 'SELECT id, planner_id, name, email, mobile, address, postcode, state, status, section_count,'
        . ' fence_type, fence_data, updated_at, created_at FROM `'
        . $conn->real_escape_string($table) . '`';
    if ($dateClause['clause'] !== '') {
        $sql .= ' WHERE ' . $dateClause['clause'];
    }
    $sql .= ' ORDER BY ' . $dateExpr . ' DESC LIMIT ' . max(1, min(20, $limit));

    $items = [];
    $result = null;
    if ($dateClause['types'] === '') {
        $result = $conn->query($sql);
    } else {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
            }
            $stmt->close();
        }
    }

    if ($result) {
        while ($row = $result->fetch_object()) {
            $normalized = fc_planners_normalize_list_row($row);
            $name = trim((string) ($normalized['name'] ?? ''));
            $plannerId = trim((string) ($normalized['planner_id'] ?? ''));
            $items[] = [
                'id' => (int) ($normalized['id'] ?? 0),
                'planner_id' => $plannerId,
                'name' => $name,
                'email' => trim((string) ($normalized['email'] ?? '')),
                'mobile' => trim((string) ($normalized['mobile'] ?? '')),
                'address' => fc_dashboard_admin_format_customer_address(
                    (string) ($normalized['address'] ?? ''),
                    (string) ($normalized['postcode'] ?? ''),
                    (string) ($normalized['state'] ?? '')
                ),
                'status' => (string) ($normalized['status'] ?? ''),
                'state' => (string) ($normalized['state'] ?? ''),
                'section_count' => (int) ($normalized['section_count'] ?? 0),
                'fence_label' => (string) ($normalized['fence_type_label'] ?? ''),
                'updated_at' => (string) ($normalized['updated_at'] ?? ''),
            ];
        }
    }

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function fc_dashboard_admin_recent_entries(int $limit = 8, ?array $bounds = null): array
{
    $ctx = fc_dashboard_admin_db_ctx();
    if (!$ctx['ok']) {
        return [];
    }

    /** @var mysqli $conn */
    $conn = $ctx['conn'];
    $items = fc_dashboard_admin_recent_entries_query($conn, (string) $ctx['table'], $limit, $bounds);
    fc_planners_close_db($conn);

    return $items;
}

/**
 * @return array<string, mixed>
 */
function fc_dashboard_admin_chart_payload(string $period = '', string $from = '', string $to = ''): array
{
    $dateFilter = fc_dashboard_admin_parse_chart_date_filter($period, $from, $to);

    $ctx = fc_dashboard_admin_db_ctx();
    if (!$ctx['ok']) {
        return ['ok' => false, 'error' => $ctx['error'] ?? 'Database unavailable.'];
    }

    /** @var mysqli $conn */
    $conn = $ctx['conn'];
    $table = (string) $ctx['table'];
    $dateExpr = fc_dashboard_admin_date_expr();
    $bounds = $dateFilter['bounds'];
    $dateClause = fc_dashboard_admin_date_bounds_clause($dateExpr, $bounds);

    $trend = [];
    $trendSql = 'SELECT DATE(' . $dateExpr . ') AS d, COUNT(*) AS c FROM `'
        . $conn->real_escape_string($table) . '`';
    if ($dateClause['clause'] !== '') {
        $trendSql .= ' WHERE ' . $dateClause['clause'];
    }
    $trendSql .= ' GROUP BY d ORDER BY d ASC';

    if ($dateClause['types'] === '') {
        $result = $conn->query($trendSql);
        if ($result) {
            while ($row = $result->fetch_object()) {
                $trend[] = [
                    'date' => (string) ($row->d ?? ''),
                    'count' => (int) ($row->c ?? 0),
                ];
            }
        }
    } else {
        $stmt = $conn->prepare($trendSql);
        if ($stmt) {
            $stmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_object()) {
                    $trend[] = [
                        'date' => (string) ($row->d ?? ''),
                        'count' => (int) ($row->c ?? 0),
                    ];
                }
            }
            $stmt->close();
        }
    }

    $byState = fc_dashboard_admin_group_count($conn, $table, 'state', 12, '', $bounds);
    $byStatus = fc_dashboard_admin_group_count($conn, $table, 'status', 10, '', $bounds);
    $byDevice = fc_dashboard_admin_group_count($conn, $table, 'device', 6, '', $bounds);

    $byHour = [];
    $hourSql = 'SELECT HOUR(' . $dateExpr . ') AS h, COUNT(*) AS c FROM `'
        . $conn->real_escape_string($table) . '`';
    if ($dateClause['clause'] !== '') {
        $hourSql .= ' WHERE ' . $dateClause['clause'];
    }
    $hourSql .= ' GROUP BY h ORDER BY h ASC';

    if ($dateClause['types'] === '') {
        $hourResult = $conn->query($hourSql);
        if ($hourResult) {
            while ($row = $hourResult->fetch_object()) {
                $byHour[] = [
                    'hour' => (int) ($row->h ?? 0),
                    'count' => (int) ($row->c ?? 0),
                ];
            }
        }
    } else {
        $hourStmt = $conn->prepare($hourSql);
        if ($hourStmt) {
            $hourStmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($hourStmt->execute()) {
                $result = $hourStmt->get_result();
                while ($row = $result->fetch_object()) {
                    $byHour[] = [
                        'hour' => (int) ($row->h ?? 0),
                        'count' => (int) ($row->c ?? 0),
                    ];
                }
            }
            $hourStmt->close();
        }
    }

    $topSites = fc_dashboard_admin_group_count($conn, $table, 'site_url', 8, '', $bounds);
    $topPostcodes = fc_dashboard_admin_group_count($conn, $table, 'postcode', 8, '', $bounds);

    $topCustomers = fc_dashboard_admin_top_customers($conn, $table, 16, $bounds);

    $recentEntries = fc_dashboard_admin_recent_entries_query($conn, $table, 16, $bounds);

    $fenceStyles = fc_dashboard_admin_fence_style_counts($conn, $table, $bounds);
    $productInsights = fc_dashboard_admin_product_insights($conn, $table, 120, $bounds);

    $browsers = [];
    $osList = [];
    $deviceBrowserCombinations = [];
    $uaSql = 'SELECT user_agent FROM `' . $conn->real_escape_string($table) . '` WHERE user_agent IS NOT NULL AND TRIM(user_agent) <> \'\'';
    if ($dateClause['clause'] !== '') {
        $uaSql .= ' AND (' . $dateClause['clause'] . ')';
    }
    $uaSql .= ' ORDER BY id DESC LIMIT 500';

    $uaStmt = null;
    $uaResult = null;
    if ($dateClause['types'] === '') {
        $uaResult = $conn->query($uaSql);
    } else {
        $uaStmt = $conn->prepare($uaSql);
        if ($uaStmt) {
            $uaStmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($uaStmt->execute()) {
                $uaResult = $uaStmt->get_result();
            }
        }
    }

    if ($uaResult) {
        $browserCounts = [];
        $osCounts = [];
        $combinationCounts = [];
        while ($row = $uaResult->fetch_object()) {
            $ua = (string) ($row->user_agent ?? '');
            $browser = fc_dashboard_admin_parse_browser($ua);
            $os = fc_dashboard_admin_parse_os($ua);
            $combination = fc_dashboard_admin_device_browser_combination($ua);
            $browserCounts[$browser] = ($browserCounts[$browser] ?? 0) + 1;
            $osCounts[$os] = ($osCounts[$os] ?? 0) + 1;
            if ($combination !== null) {
                $key = (string) $combination['key'];
                $combinationCounts[$key] = ($combinationCounts[$key] ?? 0) + 1;
            }
        }
        arsort($browserCounts);
        arsort($osCounts);
        foreach (array_slice($browserCounts, 0, 6, true) as $label => $count) {
            $browsers[] = ['label' => $label, 'count' => $count];
        }
        foreach (array_slice($osCounts, 0, 6, true) as $label => $count) {
            $osList[] = ['label' => $label, 'count' => $count];
        }
        foreach (fc_dashboard_admin_device_browser_designations() as $key => $designation) {
            $count = (int) ($combinationCounts[$key] ?? 0);
            if ($count < 1) {
                continue;
            }
            $deviceBrowserCombinations[] = [
                'key' => $key,
                'label' => $designation['label'],
                'count' => $count,
                'color' => $designation['color'],
                'color_name' => $designation['color_name'],
            ];
        }
    }
    if ($uaStmt instanceof mysqli_stmt) {
        $uaStmt->close();
    }

    $payload = [
        'ok' => true,
        'date_period' => $dateFilter['period'],
        'date_from' => $dateFilter['from'],
        'date_to' => $dateFilter['to'],
        'date_label' => $dateFilter['label'],
        'trend' => $trend,
        'by_state' => $byState,
        'by_status' => $byStatus,
        'by_device' => $byDevice,
        'by_hour' => $byHour,
        'top_sites' => $topSites,
        'top_postcodes' => $topPostcodes,
        'top_customers' => $topCustomers,
        'recent_entries' => $recentEntries,
        'fence_styles' => $fenceStyles,
        'product_insights' => $productInsights,
        'browsers' => $browsers,
        'device_browser_combinations' => $deviceBrowserCombinations,
        'operating_systems' => $osList,
    ];

    fc_planners_close_db($conn);

    return $payload;
}

function fc_dashboard_admin_format_customer_address(string $address = '', string $postcode = '', string $state = ''): string
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
 * Top customers grouped by email with latest contact details.
 *
 * @param ?array{from:string,to:string} $bounds
 * @return list<array{email:string,name:string,mobile:string,address:string,state:string,count:int,last_seen:string}>
 */
function fc_dashboard_admin_top_customers(mysqli $conn, string $table, int $limit = 8, ?array $bounds = null): array
{
    $dateExpr = fc_dashboard_admin_date_expr();
    $dateClause = fc_dashboard_admin_date_bounds_clause($dateExpr, $bounds);
    $sep = "\x1f";
    $escapedSep = str_replace("'", "''", $sep);

    $sql = 'SELECT email, COUNT(*) AS c,'
        . ' MAX(' . $dateExpr . ') AS last_seen,'
        . ' SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(TRIM(name), \'\') ORDER BY ' . $dateExpr . ' DESC SEPARATOR \'' . $escapedSep . '\'), \'' . $escapedSep . '\', 1) AS name,'
        . ' SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(TRIM(mobile), \'\') ORDER BY ' . $dateExpr . ' DESC SEPARATOR \'' . $escapedSep . '\'), \'' . $escapedSep . '\', 1) AS mobile,'
        . ' SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(TRIM(address), \'\') ORDER BY ' . $dateExpr . ' DESC SEPARATOR \'' . $escapedSep . '\'), \'' . $escapedSep . '\', 1) AS address,'
        . ' SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(TRIM(postcode), \'\') ORDER BY ' . $dateExpr . ' DESC SEPARATOR \'' . $escapedSep . '\'), \'' . $escapedSep . '\', 1) AS postcode,'
        . ' SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(TRIM(state), \'\') ORDER BY ' . $dateExpr . ' DESC SEPARATOR \'' . $escapedSep . '\'), \'' . $escapedSep . '\', 1) AS state'
        . ' FROM `' . $conn->real_escape_string($table) . '`'
        . " WHERE email IS NOT NULL AND TRIM(email) <> ''";

    if ($dateClause['clause'] !== '') {
        $sql .= ' AND (' . $dateClause['clause'] . ')';
    }

    $sql .= ' GROUP BY email ORDER BY c DESC LIMIT ' . max(1, min(20, $limit));

    $rows = [];
    $result = null;
    if ($dateClause['types'] === '') {
        $result = $conn->query($sql);
    } else {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
            }
            $stmt->close();
        }
    }

    if ($result) {
        while ($row = $result->fetch_object()) {
            $email = trim((string) ($row->email ?? ''));
            if ($email === '') {
                continue;
            }

            $name = trim((string) ($row->name ?? ''));
            $state = strtoupper(trim((string) ($row->state ?? '')));
            $rows[] = [
                'email' => $email,
                'name' => $name !== '' ? $name : $email,
                'mobile' => trim((string) ($row->mobile ?? '')),
                'address' => fc_dashboard_admin_format_customer_address(
                    (string) ($row->address ?? ''),
                    (string) ($row->postcode ?? ''),
                    (string) ($row->state ?? '')
                ),
                'state' => $state,
                'count' => (int) ($row->c ?? 0),
                'last_seen' => (string) ($row->last_seen ?? ''),
            ];
        }
    }

    return $rows;
}

/**
 * Resolve planner app base path (parent of /backend) for asset URLs.
 */
function fc_dashboard_admin_resolve_app_base(): string
{
    $adminBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');

    return rtrim(str_replace('\\', '/', dirname($adminBase)), '/');
}

/**
 * Featured image URL for a fence style slug from the catalog.
 *
 * @param array<string, array<string, mixed>> $fences
 */
function fc_dashboard_admin_fence_style_image_url(string $slug, array $fences, string $appBase = ''): string
{
    if (!function_exists('fc_normalize_planner_fence_slug')) {
        require_once __DIR__ . '/helpers.php';
    }

    $norm = fc_normalize_planner_fence_slug(trim($slug));
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

    $base = $appBase !== '' ? $appBase : fc_dashboard_admin_resolve_app_base();

    return rtrim($base, '/') . '/' . $imagePath;
}

/**
 * Aggregate fence style section counts across planner entries.
 *
 * @param ?array{from:string,to:string} $bounds
 * @return list<array{label:string,slug:string,count:int,image:string,image_url:string,swatch:string}>
 */
function fc_dashboard_admin_fence_style_counts(mysqli $conn, string $table, ?array $bounds = null): array
{
    $dateExpr = fc_dashboard_admin_date_expr();
    $dateClause = fc_dashboard_admin_date_bounds_clause($dateExpr, $bounds);
    $sql = 'SELECT fence_type, fence_data, section_count FROM `'
        . $conn->real_escape_string($table) . '`';
    if ($dateClause['clause'] !== '') {
        $sql .= ' WHERE ' . $dateClause['clause'];
    }
    $sql .= ' ORDER BY id DESC';

    $counts = [];
    $labels = [];
    $fences = fc_planners_fence_catalog();
    $result = null;
    if ($dateClause['types'] === '') {
        $result = $conn->query($sql);
    } else {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
            }
            $stmt->close();
        }
    }

    if ($result) {
        while ($row = $result->fetch_object()) {
            $styleRows = fc_planners_fence_section_types_from_rows(
                fc_planners_decode_json_field($row->fence_data ?? null),
                $fences
            );

            if ($styleRows === []) {
                $styleRows = fc_planners_fence_section_types_from_type_map(
                    (string) ($row->fence_type ?? ''),
                    $fences
                );
                if (count($styleRows) === 1 && (int) ($row->section_count ?? 0) > 0) {
                    $styleRows[0]['count'] = (int) $row->section_count;
                }
            }

            foreach ($styleRows as $styleRow) {
                $slug = trim((string) ($styleRow['slug'] ?? ''));
                $name = trim((string) ($styleRow['name'] ?? ''));
                $sectionCount = (int) ($styleRow['count'] ?? 0);
                if ($sectionCount <= 0) {
                    continue;
                }
                if ($slug === '' && $name === '') {
                    continue;
                }
                if ($slug === '') {
                    $slug = strtolower(str_replace([' ', '-'], '_', $name));
                }
                if (!function_exists('fc_normalize_planner_fence_slug')) {
                    require_once __DIR__ . '/helpers.php';
                }
                $slug = fc_normalize_planner_fence_slug($slug);
                $counts[$slug] = ($counts[$slug] ?? 0) + $sectionCount;
                if ($name !== '') {
                    $labels[$slug] = $name;
                }
            }
        }
    }

    $appBase = fc_dashboard_admin_resolve_app_base();
    arsort($counts);
    $rows = [];
    foreach (array_slice($counts, 0, 12, true) as $slug => $count) {
        $label = $labels[$slug] ?? '';
        if ($label === '' && function_exists('fc_fence_style_title_from_slug')) {
            $label = fc_fence_style_title_from_slug((string) $slug, $fences);
        }
        if ($label === '') {
            $label = (string) $slug;
        }
        $imageUrl = fc_dashboard_admin_fence_style_image_url((string) $slug, $fences, $appBase);
        $imagePath = '';
        $info = is_array($fences[$slug] ?? null) ? $fences[$slug] : [];
        if (isset($info['image'])) {
            $imagePath = ltrim(str_replace('\\', '/', (string) $info['image']), '/');
        }
        $rows[] = [
            'label' => $label,
            'slug' => (string) $slug,
            'count' => (int) $count,
            'image' => $imagePath,
            'image_url' => $imageUrl,
            'swatch' => $imageUrl !== '' ? 'url("' . str_replace(['\\', '"'], ['/', '\\"'], $imageUrl) . '")' : '',
        ];
    }

    return $rows;
}

/**
 * Resolve colour display meta for dashboard insights.
 *
 * @return array{label:string,slug:string,swatch:string,color:string}
 */
function fc_dashboard_admin_colour_meta(string $slugOrLabel): array
{
    $raw = trim($slugOrLabel);
    $slug = strtolower(str_replace([' ', '-'], '_', $raw));
    $fallbackLabel = fc_dashboard_admin_colour_humanize_slug($raw);

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

    if (!function_exists('fc_fence_colors_get')) {
        require_once __DIR__ . '/fence-colors.php';
    }

    foreach (fc_fence_colors_get() as $item) {
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
        $swatch = function_exists('fc_fence_color_background')
            ? trim((string) fc_fence_color_background($item))
            : trim((string) ($item['color'] ?? ''));
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
function fc_dashboard_admin_colour_humanize_slug(string $slugOrLabel): string
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

/**
 * Human-readable colour label for dashboard insights (slug → "Pearl White Gloss").
 */
function fc_dashboard_admin_colour_display_label(string $slugOrLabel): string
{
    return fc_dashboard_admin_colour_meta($slugOrLabel)['label'];
}

/**
 * @return array{colours:list<array{label:string,count:int}>,gate_types:list<array{label:string,count:int}>,heights:list<array{label:string,count:int}>}
 */
function fc_dashboard_admin_product_insights(mysqli $conn, string $table, int $sampleLimit = 120, ?array $bounds = null): array
{
    $dateExpr = fc_dashboard_admin_date_expr();
    $dateClause = fc_dashboard_admin_date_bounds_clause($dateExpr, $bounds);
    $sql = 'SELECT color_data, fence_data FROM `'
        . $conn->real_escape_string($table) . '`';
    if ($dateClause['clause'] !== '') {
        $sql .= ' WHERE ' . $dateClause['clause'];
    }
    $sql .= ' ORDER BY id DESC LIMIT ' . max(20, min(300, $sampleLimit));

    $colours = [];
    $gates = [];
    $heights = [];

    $result = null;
    if ($dateClause['types'] === '') {
        $result = $conn->query($sql);
    } else {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
            }
            $stmt->close();
        }
    }

    if ($result) {
        while ($row = $result->fetch_object()) {
            $colors = fc_planners_decode_json_field($row->color_data ?? null);
            foreach ($colors as $colorRow) {
                if (!is_array($colorRow)) {
                    continue;
                }
                $colour = (string) ($colorRow['color'] ?? '');
                if ($colour !== '') {
                    $colours[$colour] = ($colours[$colour] ?? 0) + 1;
                }
            }

            $fences = fc_planners_decode_json_field($row->fence_data ?? null);
            foreach ($fences as $fenceRow) {
                if (!is_array($fenceRow)) {
                    continue;
                }
                if (isset($fenceRow['fields']) && is_array($fenceRow['fields'])) {
                    foreach ($fenceRow['fields'] as $field) {
                        if (!is_array($field)) {
                            continue;
                        }
                        $name = (string) ($field['name'] ?? '');
                        $value = trim((string) ($field['value'] ?? ''));
                        if ($name === 'gate_type' && $value !== '') {
                            $gates[$value] = ($gates[$value] ?? 0) + 1;
                        }
                        if (($name === 'max_fence_height' || $name === 'gate_max_fence_height') && $value !== '') {
                            $heights[$value . ' mm'] = ($heights[$value . ' mm'] ?? 0) + 1;
                        }
                    }
                }
            }
        }
    }

    $format = static function (array $counts, int $limit = 8): array {
        arsort($counts);
        $rows = [];
        foreach (array_slice($counts, 0, $limit, true) as $label => $count) {
            $rows[] = ['label' => (string) $label, 'count' => (int) $count];
        }

        return $rows;
    };

    $formatColours = static function (array $counts, int $limit = 10): array {
        arsort($counts);
        $rows = [];
        foreach (array_slice($counts, 0, $limit, true) as $slug => $count) {
            $meta = fc_dashboard_admin_colour_meta((string) $slug);
            $rows[] = [
                'label' => $meta['label'],
                'slug' => $meta['slug'],
                'swatch' => $meta['swatch'],
                'color' => $meta['color'],
                'count' => (int) $count,
            ];
        }

        return $rows;
    };

    return [
        'colours' => $formatColours($colours, 10),
        'gate_types' => $format($gates),
        'heights' => $format($heights),
    ];
}

function fc_dashboard_admin_parse_browser(string $ua): string
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
function fc_dashboard_admin_device_browser_designations(): array
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
function fc_dashboard_admin_device_browser_combination(string $ua): ?array
{
    $normalizedUa = strtolower($ua);
    if (
        $normalizedUa === ''
        || preg_match('/(?:bot|crawler|spider|slurp|headlesschrome)/i', $normalizedUa) === 1
    ) {
        return null;
    }

    $browser = fc_dashboard_admin_parse_browser($ua);
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
    $designations = fc_dashboard_admin_device_browser_designations();
    if (!isset($designations[$key])) {
        return null;
    }

    return ['key' => $key] + $designations[$key];
}

function fc_dashboard_admin_parse_os(string $ua): string
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
 * @return array<string, mixed>
 */
function fc_dashboard_admin_health(): array
{
    $checks = [];
    $healthy = true;

    $db = fc_dashboard_admin_db_ctx();
    $checks[] = [
        'label' => 'Database',
        'status' => $db['ok'] ? 'ok' : 'error',
        'detail' => $db['ok'] ? 'Connected' : (string) ($db['error'] ?? 'Unavailable'),
    ];
    if (!$db['ok']) {
        $healthy = false;
    } elseif (isset($db['conn']) && $db['conn'] instanceof mysqli) {
        fc_planners_close_db($db['conn']);
    }

    $uploadDir = dirname(__DIR__) . '/assets/uploads';
    $uploadOk = is_dir($uploadDir) && is_writable($uploadDir);
    $checks[] = [
        'label' => 'Media uploads',
        'status' => $uploadOk ? 'ok' : 'warn',
        'detail' => $uploadOk ? 'Writable' : 'Not writable',
    ];
    if (!$uploadOk) {
        $healthy = false;
    }

    if (!function_exists('fc_storage_cache_dir')) {
        require_once __DIR__ . '/storage.php';
    }
    $cacheDir = fc_storage_cache_dir();
    $cacheOk = is_dir($cacheDir) && is_writable($cacheDir);
    $checks[] = [
        'label' => 'Cache',
        'status' => $cacheOk ? 'ok' : 'warn',
        'detail' => $cacheOk ? 'Writable' : 'Not writable',
    ];

    return [
        'ok' => $healthy,
        'status' => $healthy ? 'healthy' : 'degraded',
        'checks' => $checks,
    ];
}

/**
 * Resolve dashboard date filter from URL query (`?date=today`, `?date=all`, `?date=custom&from=&to=`).
 *
 * @param array<string, mixed> $query
 * @return array{period:string,from:string,to:string,label:string,bounds:mixed,cache_key:string}
 */
function fc_dashboard_admin_resolve_date_filter_from_query(array $query): array
{
    require_once __DIR__ . '/entries_admin.php';
    if (!function_exists('fc_system_resolved_dashboard_default_date_period')) {
        require_once __DIR__ . '/system.php';
    }

    $hasDateParam = array_key_exists('date', $query);
    $dateParam = $hasDateParam ? trim((string) $query['date']) : null;
    $from = isset($query['from']) ? trim((string) $query['from']) : '';
    $to = isset($query['to']) ? trim((string) $query['to']) : '';

    if (!$hasDateParam || $dateParam === null || $dateParam === '') {
        $period = fc_system_resolved_dashboard_default_date_period();
        $from = '';
        $to = '';
    } elseif ($dateParam === 'all') {
        $period = '';
        $from = '';
        $to = '';
    } elseif ($dateParam === 'custom') {
        $period = 'custom';
    } else {
        $period = $dateParam;
        $from = '';
        $to = '';
    }

    return fc_dashboard_admin_parse_chart_date_filter($period, $from, $to);
}

/**
 * @param array<string, mixed>|null $query Optional request query (defaults to $_GET).
 * @return array<string, mixed>
 */
function fc_dashboard_admin_page_data(string $adminBase, string $appBase, ?array $query = null): array
{
    require_once __DIR__ . '/entries_admin.php';

    $summary = fc_dashboard_admin_summary_stats();
    $system = fc_dashboard_admin_system_counts();
    $health = fc_dashboard_admin_health();
    $recent = fc_dashboard_admin_recent_entries(8);

    $entriesBase = fc_entries_admin_list_path($adminBase);
    $today = (new DateTime('now'))->format('Y-m-d');

    $dashboardFilter = fc_dashboard_admin_resolve_date_filter_from_query($query ?? $_GET);

    $widgetsVisible = function_exists('fc_permissions_dashboard_widgets_visible')
        ? fc_permissions_dashboard_widgets_visible()
        : [
            'kpis' => true,
            'trend' => true,
            'states' => true,
            'performance' => true,
            'fence-styles' => true,
            'insights' => true,
            'recent' => true,
            'customers' => true,
        ];

    return [
        'ok' => ($summary['ok'] ?? false) && ($system['ok'] ?? false),
        'summary' => $summary,
        'system' => $system,
        'health' => $health,
        'recent_entries' => $recent,
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
        'date_period_options' => fc_entries_admin_date_period_options(),
        'generated_at' => $today,
        'api_url' => rtrim($adminBase, '/') . '/api.php?module=dashboard',
    ];
}
