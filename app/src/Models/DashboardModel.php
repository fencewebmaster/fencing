<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Presenters\DashboardPresenter;
use Fc\Admin\Presenters\PlannerEntryPresenter;
use Fc\Admin\Services\CacheStorageService;
use Fc\Admin\Services\FenceCatalogService;
use Fc\Admin\Services\PlannerRecordService;
use Fc\Admin\Settings\SystemSettings;

/**
 * Dashboard analytics data access (wp_planners + products/fence CSV & file counts).
 */
final class DashboardModel
{
    private static function dateExpr(): string
    {
        return 'created_at';
    }

    /**
     * @return array{from:string,to:string}
     */
    private static function dayBounds(string $ymd): array
    {
        return [
            'from' => $ymd . ' 00:00:00',
            'to' => $ymd . ' 23:59:59',
        ];
    }

    /**
     * @return array{ok:bool,error?:string,conn?:\mysqli,table?:string}
     */
    private static function dbCtx(): array
    {
        PlannerEntryModel::ensureLoaded();

        try {
            $ctx = PlannerRecordService::openDb();

            return [
                'ok' => true,
                'conn' => $ctx['conn'],
                'table' => $ctx['table'],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{period:string,from:string,to:string,bounds:?array{from:string,to:string},label:string}
     */
    private static function parseChartDateFilter(string $period = '', string $from = '', string $to = ''): array
    {
        $parsed = PlannerEntryPresenter::parseDateFilter($period, $from, $to);
        $label = PlannerEntryPresenter::dateFilterLabel(
            (string) ($parsed['period'] ?? ''),
            (string) ($parsed['from'] ?? ''),
            (string) ($parsed['to'] ?? '')
        );

        return [
            'period' => (string) ($parsed['period'] ?? ''),
            'from' => (string) ($parsed['from'] ?? ''),
            'to' => (string) ($parsed['to'] ?? ''),
            'bounds' => $parsed['bounds'] ?? null,
            'label' => $label,
        ];
    }

    /**
     * @param ?array{from:string,to:string} $bounds
     * @return array{clause:string,types:string,params:list<string>}
     */
    private static function dateBoundsClause(string $dateExpr, ?array $bounds): array
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
    private static function groupCount(
        \mysqli $conn,
        string $table,
        string $column,
        int $limit = 12,
        string $extraWhere = '',
        ?array $bounds = null
    ): array {
        $safeCol = preg_replace('/[^a-z0-9_]+/i', '', $column) ?: $column;
        $dateExpr = self::dateExpr();
        $dateFilter = self::dateBoundsClause($dateExpr, $bounds);

        $where = $safeCol . " IS NOT NULL AND " . $safeCol . " <> ''";
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
     * @param ?array{from:string,to:string} $bounds
     * @return list<array{date:string,count:int}>
     */
    private static function trendCounts(\mysqli $conn, string $table, ?array $bounds): array
    {
        $dateExpr = self::dateExpr();
        $dateClause = self::dateBoundsClause($dateExpr, $bounds);

        $sql = 'SELECT DATE(' . $dateExpr . ') AS d, COUNT(*) AS c FROM `'
            . $conn->real_escape_string($table) . '`';
        if ($dateClause['clause'] !== '') {
            $sql .= ' WHERE ' . $dateClause['clause'];
        }
        $sql .= ' GROUP BY d ORDER BY d ASC';

        $trend = [];
        if ($dateClause['types'] === '') {
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_object()) {
                    $trend[] = ['date' => (string) ($row->d ?? ''), 'count' => (int) ($row->c ?? 0)];
                }
            }

            return $trend;
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_object()) {
                    $trend[] = ['date' => (string) ($row->d ?? ''), 'count' => (int) ($row->c ?? 0)];
                }
            }
            $stmt->close();
        }

        return $trend;
    }

    /**
     * @param ?array{from:string,to:string} $bounds
     * @return list<array{hour:int,count:int}>
     */
    private static function hourCounts(\mysqli $conn, string $table, ?array $bounds): array
    {
        $dateExpr = self::dateExpr();
        $dateClause = self::dateBoundsClause($dateExpr, $bounds);

        $sql = 'SELECT HOUR(' . $dateExpr . ') AS h, COUNT(*) AS c FROM `'
            . $conn->real_escape_string($table) . '`';
        if ($dateClause['clause'] !== '') {
            $sql .= ' WHERE ' . $dateClause['clause'];
        }
        $sql .= ' GROUP BY h ORDER BY h ASC';

        $byHour = [];
        if ($dateClause['types'] === '') {
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_object()) {
                    $byHour[] = ['hour' => (int) ($row->h ?? 0), 'count' => (int) ($row->c ?? 0)];
                }
            }

            return $byHour;
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($dateClause['types'], ...$dateClause['params']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_object()) {
                    $byHour[] = ['hour' => (int) ($row->h ?? 0), 'count' => (int) ($row->c ?? 0)];
                }
            }
            $stmt->close();
        }

        return $byHour;
    }

    /**
     * @return array<string, mixed>
     */
    public static function summaryStats(): array
    {
        $ctx = self::dbCtx();
        if (!$ctx['ok']) {
            return [
                'ok' => false,
                'error' => $ctx['error'] ?? 'Database unavailable.',
            ];
        }

        /** @var \mysqli $conn */
        $conn = $ctx['conn'];
        $table = (string) $ctx['table'];
        $safe = $conn->real_escape_string($table);
        $now = new \DateTime('now');
        $today = $now->format('Y-m-d');
        $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
        $todayBounds = self::dayBounds($today);
        $yesterdayBounds = self::dayBounds($yesterday);
        $weekStart = (clone $now)->modify('monday this week')->format('Y-m-d 00:00:00');
        $monthStart = $now->format('Y-m-01 00:00:00');
        $yearStart = $now->format('Y-01-01 00:00:00');

        $zeros = [
            'ok' => true,
            'today_entries' => 0,
            'yesterday_entries' => 0,
            'week_entries' => 0,
            'month_entries' => 0,
            'year_entries' => 0,
            'today_customers' => 0,
            'yesterday_customers' => 0,
            'week_customers' => 0,
            'month_customers' => 0,
            'year_customers' => 0,
        ];

        // Indexed day ranges for today/yesterday; one pass for week/month/year.
        $daySql = "SELECT"
            . " COUNT(DISTINCT CASE WHEN planner_id IS NOT NULL AND planner_id <> '' THEN planner_id END) AS entries,"
            . " COUNT(DISTINCT CASE WHEN email IS NOT NULL AND email <> '' THEN LOWER(email) END) AS customers"
            . " FROM `{$safe}` WHERE created_at >= ? AND created_at <= ?";

        $rangeSql = "SELECT"
            . " COUNT(DISTINCT CASE WHEN created_at >= ? AND planner_id IS NOT NULL AND planner_id <> '' THEN planner_id END) AS week_entries,"
            . " COUNT(DISTINCT CASE WHEN created_at >= ? AND email IS NOT NULL AND email <> '' THEN LOWER(email) END) AS week_customers,"
            . " COUNT(DISTINCT CASE WHEN created_at >= ? AND planner_id IS NOT NULL AND planner_id <> '' THEN planner_id END) AS month_entries,"
            . " COUNT(DISTINCT CASE WHEN created_at >= ? AND email IS NOT NULL AND email <> '' THEN LOWER(email) END) AS month_customers,"
            . " COUNT(DISTINCT CASE WHEN created_at >= ? AND planner_id IS NOT NULL AND planner_id <> '' THEN planner_id END) AS year_entries,"
            . " COUNT(DISTINCT CASE WHEN created_at >= ? AND email IS NOT NULL AND email <> '' THEN LOWER(email) END) AS year_customers"
            . " FROM `{$safe}` WHERE created_at >= ?";

        $payload = $zeros;

        $fetchDay = static function (\mysqli $conn, string $sql, string $from, string $to): array {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['entries' => 0, 'customers' => 0];
            }
            $stmt->bind_param('ss', $from, $to);
            if (!$stmt->execute()) {
                $stmt->close();

                return ['entries' => 0, 'customers' => 0];
            }
            $row = $stmt->get_result()->fetch_object();
            $stmt->close();

            return [
                'entries' => (int) ($row->entries ?? 0),
                'customers' => (int) ($row->customers ?? 0),
            ];
        };

        $todayStats = $fetchDay($conn, $daySql, $todayBounds['from'], $todayBounds['to']);
        $yesterdayStats = $fetchDay($conn, $daySql, $yesterdayBounds['from'], $yesterdayBounds['to']);
        $payload['today_entries'] = $todayStats['entries'];
        $payload['today_customers'] = $todayStats['customers'];
        $payload['yesterday_entries'] = $yesterdayStats['entries'];
        $payload['yesterday_customers'] = $yesterdayStats['customers'];

        $rangeStmt = $conn->prepare($rangeSql);
        if ($rangeStmt) {
            $rangeStmt->bind_param(
                'sssssss',
                $weekStart,
                $weekStart,
                $monthStart,
                $monthStart,
                $yearStart,
                $yearStart,
                $yearStart
            );
            if ($rangeStmt->execute()) {
                $row = $rangeStmt->get_result()->fetch_object();
                if ($row) {
                    $payload['week_entries'] = (int) ($row->week_entries ?? 0);
                    $payload['week_customers'] = (int) ($row->week_customers ?? 0);
                    $payload['month_entries'] = (int) ($row->month_entries ?? 0);
                    $payload['month_customers'] = (int) ($row->month_customers ?? 0);
                    $payload['year_entries'] = (int) ($row->year_entries ?? 0);
                    $payload['year_customers'] = (int) ($row->year_customers ?? 0);
                }
            }
            $rangeStmt->close();
        }

        PlannerRecordService::closeDb($conn);

        return $payload;
    }

    /**
     * @return array{ok:bool,store_products:int,system_products:int,total_products:int,fence_styles:int,gallery_items:int,registered_users:int,project_plan_downloads:null}
     */
    public static function systemCounts(): array
    {
        $storeTotal = (int) StoreProductModel::all()['total'];
        $systemTotal = (int) SystemProductModel::all('GO')['total'] + (int) SystemProductModel::all('JG')['total'];

        // data/ was renamed writable/ (Aug 2026); the old path made this count silently 0.
        $fenceStyles = count(glob(FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'fences' . DIRECTORY_SEPARATOR . '*.php') ?: []);

        $galleryItems = 0;
        $uploadDir = FC_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
        if (is_dir($uploadDir)) {
            foreach (glob($uploadDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                if (is_file($file) && !str_starts_with(basename($file), '.')) {
                    $galleryItems++;
                }
            }
        }

        $adminUsers = 0;
        $db = UserModel::db();
        if ($db instanceof \mysqli) {
            $usersTable = UserModel::usersTable();
            $result = $db->query('SELECT COUNT(*) AS c FROM `' . $db->real_escape_string($usersTable) . '`');
            if ($result) {
                $adminUsers = (int) ($result->fetch_object()->c ?? 0);
            }
            $db->close();
        }

        return [
            'ok' => true,
            'store_products' => $storeTotal,
            'system_products' => $systemTotal,
            'total_products' => $storeTotal + $systemTotal,
            'fence_styles' => $fenceStyles,
            'gallery_items' => $galleryItems,
            'registered_users' => $adminUsers,
            'project_plan_downloads' => null,
        ];
    }

    /**
     * @param ?array{from:string,to:string} $bounds
     * @return list<array<string, mixed>>
     */
    private static function recentEntriesQuery(\mysqli $conn, string $table, int $limit = 8, ?array $bounds = null): array
    {
        $dateExpr = self::dateExpr();
        $dateClause = self::dateBoundsClause($dateExpr, $bounds);
        // List columns only — fence_type is enough for labels (no fence_data LONGTEXT).
        $sql = 'SELECT id, planner_id, name, email, mobile, address, postcode, state, status, section_count,'
            . ' fence_type, updated_at, created_at FROM `'
            . $conn->real_escape_string($table) . '`';
        if ($dateClause['clause'] !== '') {
            $sql .= ' WHERE ' . $dateClause['clause'];
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . max(1, min(20, $limit));

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
                $normalized = PlannerEntryPresenter::normalizeListRow($row);
                $name = trim((string) ($normalized['name'] ?? ''));
                $plannerId = trim((string) ($normalized['planner_id'] ?? ''));
                $items[] = [
                    'id' => (int) ($normalized['id'] ?? 0),
                    'planner_id' => $plannerId,
                    'name' => $name,
                    'email' => trim((string) ($normalized['email'] ?? '')),
                    'mobile' => trim((string) ($normalized['mobile'] ?? '')),
                    'address' => DashboardPresenter::formatCustomerAddress(
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
     * @param ?array{from:string,to:string} $bounds
     * @return list<array<string, mixed>>
     */
    public static function recentEntries(int $limit = 8, ?array $bounds = null): array
    {
        $ctx = self::dbCtx();
        if (!$ctx['ok']) {
            return [];
        }

        /** @var \mysqli $conn */
        $conn = $ctx['conn'];
        $items = self::recentEntriesQuery($conn, (string) $ctx['table'], $limit, $bounds);
        PlannerRecordService::closeDb($conn);

        return $items;
    }

    /**
     * Top customers grouped by email with latest contact details.
     *
     * @param ?array{from:string,to:string} $bounds
     * @return list<array{email:string,name:string,mobile:string,address:string,state:string,count:int,last_seen:string}>
     */
    private static function topCustomers(\mysqli $conn, string $table, int $limit = 8, ?array $bounds = null): array
    {
        $dateExpr = self::dateExpr();
        $dateClause = self::dateBoundsClause($dateExpr, $bounds);
        $safe = $conn->real_escape_string($table);
        $limit = max(1, min(20, $limit));

        // Pass 1: cheap GROUP BY — covering idx_email_cover avoids LONGTEXT row reads.
        $sql = 'SELECT email, COUNT(*) AS c, MAX(id) AS last_id, MAX(' . $dateExpr . ') AS last_seen'
            . ' FROM `' . $safe . '`'
            . " WHERE email IS NOT NULL AND email <> ''";

        if ($dateClause['clause'] !== '') {
            $sql .= ' AND (' . $dateClause['clause'] . ')';
        }

        $sql .= ' GROUP BY email ORDER BY c DESC LIMIT ' . $limit;

        $aggregates = [];
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
                $lastId = (int) ($row->last_id ?? 0);
                if ($email === '' || $lastId <= 0) {
                    continue;
                }
                $aggregates[$lastId] = [
                    'email' => $email,
                    'count' => (int) ($row->c ?? 0),
                    'last_seen' => (string) ($row->last_seen ?? ''),
                ];
            }
        }

        if ($aggregates === []) {
            return [];
        }

        // Pass 2: latest contact details for the top N only.
        $ids = array_keys($aggregates);
        $idList = implode(',', array_map('intval', $ids));
        $detailSql = 'SELECT id, name, mobile, address, postcode, state FROM `' . $safe . '`'
            . ' WHERE id IN (' . $idList . ')';
        $details = [];
        $detailResult = $conn->query($detailSql);
        if ($detailResult) {
            while ($row = $detailResult->fetch_object()) {
                $details[(int) ($row->id ?? 0)] = $row;
            }
        }

        $rows = [];
        foreach ($aggregates as $lastId => $agg) {
            $detail = $details[$lastId] ?? null;
            $email = $agg['email'];
            $name = trim((string) ($detail->name ?? ''));
            $state = strtoupper(trim((string) ($detail->state ?? '')));
            $rows[] = [
                'email' => $email,
                'name' => $name !== '' ? $name : $email,
                'mobile' => trim((string) ($detail->mobile ?? '')),
                'address' => DashboardPresenter::formatCustomerAddress(
                    (string) ($detail->address ?? ''),
                    (string) ($detail->postcode ?? ''),
                    (string) ($detail->state ?? '')
                ),
                'state' => $state,
                'count' => (int) $agg['count'],
                'last_seen' => (string) $agg['last_seen'],
            ];
        }

        return $rows;
    }

    /**
     * Aggregate fence style section counts across planner entries.
     *
     * @param ?array{from:string,to:string} $bounds
     * @return list<array{label:string,slug:string,count:int,image:string,image_url:string,swatch:string}>
     */
    private static function fenceStyleCounts(\mysqli $conn, string $table, ?array $bounds = null): array
    {
        $dateExpr = self::dateExpr();
        $dateClause = self::dateBoundsClause($dateExpr, $bounds);
        // Use fence_type (varchar) only — avoid scanning fence_data LONGTEXT for every row.
        $sql = 'SELECT fence_type, section_count FROM `'
            . $conn->real_escape_string($table) . '`';
        if ($dateClause['clause'] !== '') {
            $sql .= ' WHERE ' . $dateClause['clause'];
        }
        $sql .= ' ORDER BY id DESC LIMIT 3000';

        $counts = [];
        $labels = [];
        $fences = PlannerEntryPresenter::fenceCatalog();
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
                $styleRows = PlannerEntryPresenter::fenceSectionTypesFromTypeMap(
                    (string) ($row->fence_type ?? ''),
                    $fences
                );
                if (count($styleRows) === 1 && (int) ($row->section_count ?? 0) > 0) {
                    $styleRows[0]['count'] = (int) $row->section_count;
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
                    $slug = FenceCatalogService::normalizePlannerFenceSlug($slug);
                    $counts[$slug] = ($counts[$slug] ?? 0) + $sectionCount;
                    if ($name !== '') {
                        $labels[$slug] = $name;
                    }
                }
            }
        }

        $appBase = DashboardPresenter::resolveAppBase();
        arsort($counts);
        $rows = [];
        foreach (array_slice($counts, 0, 12, true) as $slug => $count) {
            $label = $labels[$slug] ?? '';
            if ($label === '') {
                $label = FenceCatalogService::styleTitleFromSlug((string) $slug, $fences);
            }
            if ($label === '') {
                $label = (string) $slug;
            }
            $imageUrl = DashboardPresenter::fenceStyleImageUrl((string) $slug, $fences, $appBase);
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
     * @param ?array{from:string,to:string} $bounds
     * @return array{colours:list<array{label:string,count:int}>,gate_types:list<array{label:string,count:int}>,heights:list<array{label:string,count:int}>}
     */
    private static function productInsights(\mysqli $conn, string $table, int $sampleLimit = 120, ?array $bounds = null): array
    {
        $dateExpr = self::dateExpr();
        $dateClause = self::dateBoundsClause($dateExpr, $bounds);
        $sql = 'SELECT color_data, fence_data FROM `'
            . $conn->real_escape_string($table) . '`';
        if ($dateClause['clause'] !== '') {
            $sql .= ' WHERE ' . $dateClause['clause'];
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . max(20, min(200, $sampleLimit));

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
                $colors = PlannerEntryPresenter::decodeJsonField($row->color_data ?? null);
                foreach ($colors as $colorRow) {
                    if (!is_array($colorRow)) {
                        continue;
                    }
                    $colour = (string) ($colorRow['color'] ?? '');
                    if ($colour !== '') {
                        $colours[$colour] = ($colours[$colour] ?? 0) + 1;
                    }
                }

                $fences = PlannerEntryPresenter::decodeJsonField($row->fence_data ?? null);
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
                $meta = DashboardPresenter::colourMeta((string) $slug);
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

    /**
     * @return array<string, mixed>
     */
    public static function chartPayload(string $period = '', string $from = '', string $to = ''): array
    {
        $dateFilter = self::parseChartDateFilter($period, $from, $to);

        $ctx = self::dbCtx();
        if (!$ctx['ok']) {
            return ['ok' => false, 'error' => $ctx['error'] ?? 'Database unavailable.'];
        }

        /** @var \mysqli $conn */
        $conn = $ctx['conn'];
        $table = (string) $ctx['table'];
        $bounds = $dateFilter['bounds'];
        $dateExpr = self::dateExpr();
        $dateClause = self::dateBoundsClause($dateExpr, $bounds);

        $trend = self::trendCounts($conn, $table, $bounds);
        $byState = self::groupCount($conn, $table, 'state', 12, '', $bounds);
        $byStatus = self::groupCount($conn, $table, 'status', 10, '', $bounds);
        $byDevice = self::groupCount($conn, $table, 'device', 6, '', $bounds);
        $byHour = self::hourCounts($conn, $table, $bounds);
        $topSites = self::groupCount($conn, $table, 'site_url', 8, '', $bounds);
        $topPostcodes = self::groupCount($conn, $table, 'postcode', 8, '', $bounds);
        $topCustomers = self::topCustomers($conn, $table, 16, $bounds);
        $recentEntries = self::recentEntriesQuery($conn, $table, 16, $bounds);
        $fenceStyles = self::fenceStyleCounts($conn, $table, $bounds);
        $productInsights = self::productInsights($conn, $table, 120, $bounds);

        $browsers = [];
        $osList = [];
        $deviceBrowserCombinations = [];
        $uaSql = 'SELECT user_agent FROM `' . $conn->real_escape_string($table) . '` WHERE user_agent IS NOT NULL AND user_agent <> \'\'';
        if ($dateClause['clause'] !== '') {
            $uaSql .= ' AND (' . $dateClause['clause'] . ')';
        }
        $uaSql .= ' ORDER BY id DESC LIMIT 250';

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
                $browser = DashboardPresenter::parseBrowser($ua);
                $os = DashboardPresenter::parseOs($ua);
                $combination = DashboardPresenter::deviceBrowserCombination($ua);
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
            foreach (DashboardPresenter::deviceBrowserDesignations() as $key => $designation) {
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
        if ($uaStmt instanceof \mysqli_stmt) {
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

        PlannerRecordService::closeDb($conn);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function health(): array
    {
        $checks = [];
        $healthy = true;

        $db = self::dbCtx();
        $checks[] = [
            'label' => 'Database',
            'status' => $db['ok'] ? 'ok' : 'error',
            'detail' => $db['ok'] ? 'Connected' : (string) ($db['error'] ?? 'Unavailable'),
        ];
        if (!$db['ok']) {
            $healthy = false;
        } elseif (isset($db['conn']) && $db['conn'] instanceof \mysqli) {
            PlannerRecordService::closeDb($db['conn']);
        }

        $uploadDir = FC_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
        $uploadOk = is_dir($uploadDir) && is_writable($uploadDir);
        $checks[] = [
            'label' => 'Media uploads',
            'status' => $uploadOk ? 'ok' : 'warn',
            'detail' => $uploadOk ? 'Writable' : 'Not writable',
        ];
        if (!$uploadOk) {
            $healthy = false;
        }

        $cacheDir = CacheStorageService::cacheDir();
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
     * @return array{period:string,from:string,to:string,label:string,bounds:mixed}
     */
    public static function resolveDateFilterFromQuery(array $query): array
    {
        $hasDateParam = array_key_exists('date', $query);
        $dateParam = $hasDateParam ? trim((string) $query['date']) : null;
        $from = isset($query['from']) ? trim((string) $query['from']) : '';
        $to = isset($query['to']) ? trim((string) $query['to']) : '';

        if (!$hasDateParam || $dateParam === null || $dateParam === '') {
            $period = SystemSettings::resolvedDashboardDefaultDatePeriod();
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

        return self::parseChartDateFilter($period, $from, $to);
    }
}
