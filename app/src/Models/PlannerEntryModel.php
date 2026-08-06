<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Core\Model;
use Fc\Admin\Services\PlannerRecordService;

/**
 * Planner entries data access (wp_planners).
 *
 * Extends Core\Model for the CodeIgniter-4-like base-Model contract (find/insert/update/delete) —
 * currently inert/additive: every existing static method below is unchanged, nothing yet calls
 * the inherited instance methods. $primaryKey is 'id' (Core\Model's own default) and
 * connectionConfig() returns null (Database's own default), matching PlannerRecordService::openDb()'s
 * `new Database()` with no args exactly.
 */
final class PlannerEntryModel extends Model
{
    protected function resolveTable(): string
    {
        // Bare table name — see UserModel's resolveTable() for why this must not be fully-qualified.
        return 'planners';
    }

    /**
     * @deprecated PlannerRecordService is autoloaded on demand; this is now a no-op kept for callers.
     */
    public static function ensureLoaded(): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function list(
        string $search = '',
        string $status = '',
        int $limit = 50,
        int $offset = 0,
        bool $withStatuses = false,
        bool $withTotal = false,
        string $timeframe = '',
        string $state = '',
        array $fenceTypes = [],
        ?array $dateBounds = null,
        string $trashView = 'all',
        string $dateField = 'created_at',
        string $postcode = '',
        array $devices = [],
        array $browsers = [],
        ?int $sectionsMin = null,
        ?int $sectionsMax = null,
        ?int $quoteLoadsMin = null,
        ?int $quoteLoadsMax = null
    ): array {
        self::ensureLoaded();

        $isAll = self::isAllLimit($limit);
        if ($isAll) {
            $offset = 0;
        } else {
            $limit = max(1, min(500, $limit));
        }
        $offset = max(0, $offset);
        $search = trim($search);
        $status = trim($status);
        $timeframe = trim($timeframe);
        $state = trim($state);
        $postcode = trim($postcode);
        $devices = array_values($devices);
        $browsers = array_values($browsers);
        $fenceTypes = array_values($fenceTypes);
        $trashView = self::normalizeListView($trashView);
        $dateField = $dateField === 'updated_at' ? 'updated_at' : 'created_at';

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $filters = self::buildFilters(
            $conn,
            $search,
            $status,
            $timeframe,
            $state,
            $fenceTypes,
            $dateBounds,
            $trashView,
            $dateField,
            $postcode,
            $devices,
            $browsers,
            $sectionsMin,
            $sectionsMax,
            $quoteLoadsMin,
            $quoteLoadsMax
        );
        if ($filters === null) {
            PlannerRecordService::closeDb($conn);
            return ['ok' => false, 'error' => 'Invalid filters.'];
        }

        // List columns only — never pull LONGTEXT fence_data on the list page.
        $listSelect = implode(', ', PlannerEntryPresenter::listColumns());
        $total = null;
        $orderBy = $dateField . ' DESC, id DESC';

        if ($isAll) {
            $sql = 'SELECT ' . $listSelect . ' FROM `' . $table . '` WHERE ' . $filters['where']
                . ' ORDER BY ' . $orderBy;
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                PlannerRecordService::closeDb($conn);
                return ['ok' => false, 'error' => 'Planners table not found. Import writable/schema/wp_planners.sql.'];
            }
            if ($filters['types'] !== '') {
                $stmt->bind_param($filters['types'], ...$filters['params']);
            }
            if (!$stmt->execute()) {
                $stmt->close();
                PlannerRecordService::closeDb($conn);
                return ['ok' => false, 'error' => 'Could not load planner entries.'];
            }
            $result = $stmt->get_result();
        } else {
            $fetchLimit = $limit + 1;
            $sql = 'SELECT ' . $listSelect . ' FROM `' . $table . '` WHERE ' . $filters['where']
                . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                PlannerRecordService::closeDb($conn);
                return ['ok' => false, 'error' => 'Planners table not found. Import writable/schema/wp_planners.sql.'];
            }

            $types = $filters['types'] . 'ii';
            $params = array_merge($filters['params'], [$fetchLimit, $offset]);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                $stmt->close();
                PlannerRecordService::closeDb($conn);
                return ['ok' => false, 'error' => 'Could not load planner entries.'];
            }

            $result = $stmt->get_result();
        }

        $items = [];
        while ($row = $result->fetch_object()) {
            $items[] = PlannerEntryPresenter::normalizeListRow($row);
        }
        $stmt->close();

        $hasMore = false;
        if (!$isAll) {
            $hasMore = count($items) > $limit;
            if ($hasMore) {
                array_pop($items);
            }
        }

        if ($withTotal) {
            $countSql = 'SELECT COUNT(*) AS total FROM `' . $table . '` WHERE ' . $filters['where'];
            $countStmt = $conn->prepare($countSql);
            if ($countStmt) {
                if ($filters['types'] !== '') {
                    $countStmt->bind_param($filters['types'], ...$filters['params']);
                }
                if ($countStmt->execute()) {
                    $countRes = $countStmt->get_result();
                    if ($countRes && ($countRow = $countRes->fetch_object())) {
                        $total = (int) ($countRow->total ?? 0);
                    }
                }
                $countStmt->close();
            }
        }

        $statuses = [];
        if ($withStatuses) {
            $statuses = self::statusList();
        }

        PlannerRecordService::closeDb($conn);

        $out = [
            'ok' => true,
            'items' => $items,
            'has_more' => $hasMore,
        ];

        if ($total !== null) {
            $out['total'] = $total;
        } elseif ($offset > 0) {
            $out['total'] = $offset + count($items) + ($hasMore ? 1 : 0);
        } else {
            $out['total'] = count($items);
        }

        if ($withStatuses) {
            $out['statuses'] = $statuses;
        }

        if ($isAll) {
            $out['limit'] = 'all';
        } else {
            $out['limit'] = $limit;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getByPlannerId(string $plannerId): array
    {
        self::ensureLoaded();

        $plannerId = trim($plannerId);
        if ($plannerId === '') {
            return ['ok' => false, 'error' => 'Planner ID required.'];
        }

        return self::fetchEntryRow('planner_id', $plannerId, 's', true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getById(int $entryId): array
    {
        self::ensureLoaded();

        if ($entryId <= 0) {
            return ['ok' => false, 'error' => 'Entry ID required.'];
        }

        return self::fetchEntryRow('id', $entryId, 'i', false);
    }

    /**
     * @return array<string, mixed>
     */
    public static function statuses(): array
    {
        self::ensureLoaded();

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $sql = "SELECT DISTINCT status FROM `" . $table . "` WHERE status IS NOT NULL AND status <> '' ORDER BY status ASC";
        $result = $conn->query($sql);

        if ($result === false) {
            PlannerRecordService::closeDb($conn);
            return ['ok' => false, 'error' => 'Could not load statuses.'];
        }

        $statuses = [];
        while ($row = $result->fetch_object()) {
            $statuses[] = (string) ($row->status ?? '');
        }

        PlannerRecordService::closeDb($conn);

        return ['ok' => true, 'statuses' => $statuses];
    }

    /**
     * @return list<string>
     */
    public static function statusList(): array
    {
        self::ensureLoaded();

        // Known planner statuses — avoids a full DISTINCT scan on every list page load.
        // Keep in sync with submit/checkout/reload flows and Find Duplicates (status=duplicate).
        return ['duplicate', 'planning', 'reloaded', 'submitted'];
    }

    /**
     * @return array{all:int,trash:int,duplicates:int}
     */
    public static function trashViewCounts(): array
    {
        self::ensureLoaded();

        $counts = ['all' => 0, 'trash' => 0, 'duplicates' => 0];

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return $counts;
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $safe = $conn->real_escape_string($table);

        // Three indexed COUNTs beat one full-table SUM(CASE…) once idx_trash_status exists.
        $queries = [
            'all' => 'SELECT COUNT(*) AS c FROM `' . $safe . '`'
                . ' WHERE trashed_at IS NULL AND (status IS NULL OR status <> \'duplicate\')',
            'trash' => 'SELECT COUNT(*) AS c FROM `' . $safe . '` WHERE trashed_at IS NOT NULL',
            'duplicates' => 'SELECT COUNT(*) AS c FROM `' . $safe . '`'
                . ' WHERE trashed_at IS NULL AND status = \'duplicate\'',
        ];

        foreach ($queries as $key => $sql) {
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_object())) {
                $counts[$key] = (int) ($row->c ?? 0);
            }
        }

        PlannerRecordService::closeDb($conn);

        return $counts;
    }

    /**
     * Find the internal numeric id for a natural-key planner_id, or null if not found.
     */
    public static function entryIdForPlanner(string $plannerId): ?int
    {
        self::ensureLoaded();

        $plannerId = trim($plannerId);
        if ($plannerId === '') {
            return null;
        }

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return null;
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $sql = 'SELECT id FROM `' . $table . '` WHERE planner_id = ? ORDER BY id DESC LIMIT 1';
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            PlannerRecordService::closeDb($conn);
            return null;
        }

        $stmt->bind_param('s', $plannerId);
        if (!$stmt->execute()) {
            $stmt->close();
            PlannerRecordService::closeDb($conn);
            return null;
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_object() : null;
        $stmt->close();
        PlannerRecordService::closeDb($conn);

        if (!$row || !isset($row->id)) {
            return null;
        }

        return (int) $row->id;
    }

    /**
     * @param mixed $value
     * @return array{ok:bool,item?:array<string,mixed>,error?:string}
     */
    private static function fetchEntryRow(string $column, $value, string $bindType, bool $withOrderBy): array
    {
        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $columns = implode(', ', PlannerEntryPresenter::entryColumns());
        $sql = 'SELECT ' . $columns . ', fence_data, cart_data, cart_items_data, project_plans_data'
            . ' FROM `' . $table . '` WHERE `' . $column . '` = ?'
            . ($withOrderBy ? ' ORDER BY id DESC' : '') . ' LIMIT 1';
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            PlannerRecordService::closeDb($conn);
            return ['ok' => false, 'error' => 'Could not load entry.'];
        }

        $stmt->bind_param($bindType, $value);
        if (!$stmt->execute()) {
            $stmt->close();
            PlannerRecordService::closeDb($conn);
            return ['ok' => false, 'error' => 'Could not load entry.'];
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_object() : null;
        $stmt->close();
        PlannerRecordService::closeDb($conn);

        if (!$row) {
            return ['ok' => false, 'error' => 'Entry not found.'];
        }

        return [
            'ok' => true,
            'item' => PlannerEntryPresenter::normalizeDetailRow($row),
        ];
    }

    private static function isAllLimit(int $limit): bool
    {
        return $limit <= 0;
    }

    /**
     * Normalize planner-entries list view key.
     */
    private static function normalizeListView(string $view): string
    {
        $view = strtolower(trim($view));
        if ($view === 'trash') {
            return 'trash';
        }
        if ($view === 'duplicates') {
            return 'duplicates';
        }

        return 'all';
    }

    private static function browserCaseSql(): string
    {
        return "CASE"
            . " WHEN TRIM(COALESCE(user_agent, '')) = '' THEN 'unknown'"
            . " WHEN LOWER(user_agent) LIKE '%edg/%'"
            . " OR LOWER(user_agent) LIKE '%edge/%'"
            . " OR LOWER(user_agent) LIKE '%edga/%'"
            . " OR LOWER(user_agent) LIKE '%edgios/%' THEN 'edge'"
            . " WHEN LOWER(user_agent) LIKE '%opr/%'"
            . " OR LOWER(user_agent) LIKE '%opera%' THEN 'opera'"
            . " WHEN LOWER(user_agent) LIKE '%samsungbrowser/%' THEN 'samsung_internet'"
            . " WHEN LOWER(user_agent) LIKE '%brave/%' THEN 'brave'"
            . " WHEN LOWER(user_agent) LIKE '%chrome/%'"
            . " OR LOWER(user_agent) LIKE '%crios/%' THEN 'chrome'"
            . " WHEN LOWER(user_agent) LIKE '%firefox/%'"
            . " OR LOWER(user_agent) LIKE '%fxios/%' THEN 'firefox'"
            . " WHEN LOWER(user_agent) LIKE '%safari/%' THEN 'safari'"
            . " WHEN LOWER(user_agent) LIKE '%msie %'"
            . " OR LOWER(user_agent) LIKE '%trident/%' THEN 'internet_explorer'"
            . " ELSE 'other' END";
    }

    /**
     * @return array{where:string,types:string,params:list<mixed>}|null
     */
    private static function buildFilters(
        \mysqli $conn,
        string $search = '',
        string $status = '',
        string $timeframe = '',
        string $state = '',
        array $fenceTypes = [],
        ?array $dateBounds = null,
        string $trashView = 'all',
        string $dateField = 'created_at',
        string $postcode = '',
        array $devices = [],
        array $browsers = [],
        ?int $sectionsMin = null,
        ?int $sectionsMax = null,
        ?int $quoteLoadsMin = null,
        ?int $quoteLoadsMax = null
    ): ?array {
        $parts = [];
        $types = '';
        $params = [];

        $trashView = self::normalizeListView($trashView);
        if ($trashView === 'trash') {
            $parts[] = 'trashed_at IS NOT NULL';
        } elseif ($trashView === 'duplicates') {
            // Soft-archived duplicates: still live rows, excluded from All.
            $parts[] = 'trashed_at IS NULL';
            $parts[] = "status = 'duplicate'";
        } else {
            $parts[] = 'trashed_at IS NULL';
            // Index-friendly: avoid LOWER/TRIM on status for every list load.
            $parts[] = "(status IS NULL OR status <> 'duplicate')";
        }

        $status = trim($status);
        // Duplicates tab already scopes by status; ignore a conflicting status filter.
        if ($status !== '' && $trashView !== 'duplicates') {
            $parts[] = 'status = ?';
            $types .= 's';
            $params[] = $status;
        }

        $timeframe = trim($timeframe);
        if ($timeframe !== '') {
            $parts[] = 'timeframe = ?';
            $types .= 's';
            $params[] = $timeframe;
        }

        $state = trim($state);
        if ($state !== '') {
            $parts[] = 'state = ?';
            $types .= 's';
            $params[] = $state;
        }

        $postcode = trim($postcode);
        if ($postcode !== '') {
            $parts[] = 'TRIM(postcode) = ?';
            $types .= 's';
            $params[] = $postcode;
        }

        $devices = array_values(array_intersect(
            array_map(static fn($value): string => strtolower(trim((string) $value)), $devices),
            ['desktop', 'mobile', 'tablet', 'bot', 'unknown']
        ));
        if ($devices !== []) {
            $deviceParts = [];
            if (in_array('unknown', $devices, true)) {
                $deviceParts[] = "(device IS NULL OR TRIM(device) = '' OR LOWER(TRIM(device)) = 'unknown')";
            }
            $knownDevices = array_values(array_diff($devices, ['unknown']));
            if ($knownDevices !== []) {
                $deviceParts[] = 'LOWER(TRIM(device)) IN (' . implode(', ', array_fill(0, count($knownDevices), '?')) . ')';
                $types .= str_repeat('s', count($knownDevices));
                array_push($params, ...$knownDevices);
            }
            $parts[] = '(' . implode(' OR ', $deviceParts) . ')';
        }

        $browsers = array_values(array_intersect(
            array_map(static fn($value): string => strtolower(trim((string) $value)), $browsers),
            [
            'chrome',
            'edge',
            'firefox',
            'safari',
            'opera',
            'samsung_internet',
            'brave',
            'internet_explorer',
            'other',
            'unknown',
            ]
        ));
        if ($browsers !== []) {
            $parts[] = '(' . self::browserCaseSql() . ') IN ('
                . implode(', ', array_fill(0, count($browsers), '?')) . ')';
            $types .= str_repeat('s', count($browsers));
            array_push($params, ...$browsers);
        }

        if ($fenceTypes !== []) {
            $fenceParts = [];
            foreach ($fenceTypes as $slug) {
                $escaped = addcslashes($slug, '%_\\');
                // fence_type is JSON like {"slat":"slat"} — avoid fence_data LONGTEXT scans.
                $fenceParts[] = 'fence_type LIKE ?';
                $types .= 's';
                $params[] = '%"' . $escaped . '":%';
            }
            $parts[] = '(' . implode(' OR ', $fenceParts) . ')';
        }

        $search = trim($search);
        if ($search !== '') {
            $like = '%' . $search . '%';
            $parts[] = '(planner_id LIKE ? OR name LIKE ? OR email LIKE ? OR mobile LIKE ? OR site_url LIKE ?)';
            $types .= 'sssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (is_array($dateBounds) && !empty($dateBounds['from']) && !empty($dateBounds['to'])) {
            $column = $dateField === 'updated_at' ? 'updated_at' : 'created_at';
            $parts[] = $column . ' >= ? AND ' . $column . ' <= ?';
            $types .= 'ss';
            $params[] = (string) $dateBounds['from'];
            $params[] = (string) $dateBounds['to'];
        }

        foreach ([
            ['column' => 'section_count', 'operator' => '>=', 'value' => $sectionsMin],
            ['column' => 'section_count', 'operator' => '<=', 'value' => $sectionsMax],
            ['column' => 'quote_load_count', 'operator' => '>=', 'value' => $quoteLoadsMin],
            ['column' => 'quote_load_count', 'operator' => '<=', 'value' => $quoteLoadsMax],
        ] as $rangeFilter) {
            if ($rangeFilter['value'] === null) {
                continue;
            }
            $parts[] = $rangeFilter['column'] . ' ' . $rangeFilter['operator'] . ' ?';
            $types .= 'i';
            $params[] = max(0, (int) $rangeFilter['value']);
        }

        $where = $parts ? implode(' AND ', $parts) : '1=1';

        return [
            'where' => $where,
            'types' => $types,
            'params' => $params,
        ];
    }
}
