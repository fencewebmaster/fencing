<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\StringHelper;
use Fc\Admin\Models\PlannerEntryModel;
use Fc\Admin\Models\PlannerEntryPresenter;

/**
 * Admin bulk-mutation / export / import operations for wp_planners.
 */
final class PlannerEntryMaintenanceService
{
    /**
     * Scan for duplicate groups (same planner_id on more than one active row).
     * Keeps the newest row in each group; older ids are marked duplicate.
     *
     * @return array{
     *   ok:bool,
     *   groups?:int,
     *   keep_count?:int,
     *   mark_count?:int,
     *   mark_ids?:list<int>,
     *   keep_ids?:list<int>,
     *   sample?:list<array{planner_id:string,keep_id:int,mark_ids:list<int>,total:int}>,
     *   error?:string
     * }
     */
    public static function dedupeScan(int $sampleLimit = 8): array
    {
        PlannerEntryModel::ensureLoaded();

        $sampleLimit = max(0, min(50, $sampleLimit));

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $safe = $conn->real_escape_string($table);

        // Group in PHP by planner_id — keep newest (created_at DESC, id DESC).
        $sql = 'SELECT `id`, TRIM(`planner_id`) AS planner_key, `created_at`'
            . ' FROM `' . $safe . '`'
            . ' WHERE `trashed_at` IS NULL'
            . "   AND (`status` IS NULL OR `status` <> 'duplicate')"
            . "   AND `planner_id` IS NOT NULL AND `planner_id` <> ''"
            . ' ORDER BY planner_key ASC, `created_at` DESC, `id` DESC';

        $result = $conn->query($sql);
        if (!$result) {
            $err = $conn->error;
            PlannerRecordService::closeDb($conn);

            return ['ok' => false, 'error' => 'Could not scan for duplicates.' . ($err !== '' ? ' ' . $err : '')];
        }

        /** @var array<string, list<array{id:int,planner_id:string}>> $byGroup */
        $byGroup = [];
        while ($row = $result->fetch_assoc()) {
            $plannerKey = (string) ($row['planner_key'] ?? '');
            if ($plannerKey === '') {
                continue;
            }
            if (!isset($byGroup[$plannerKey])) {
                $byGroup[$plannerKey] = [];
            }
            $byGroup[$plannerKey][] = [
                'id' => (int) ($row['id'] ?? 0),
                'planner_id' => $plannerKey,
            ];
        }

        PlannerRecordService::closeDb($conn);

        $markIds = [];
        $keepIds = [];
        $sample = [];
        $groups = 0;

        foreach ($byGroup as $plannerKey => $rows) {
            if (count($rows) < 2) {
                continue;
            }
            $groups++;
            $keepId = (int) ($rows[0]['id'] ?? 0);
            if ($keepId > 0) {
                $keepIds[] = $keepId;
            }
            $groupMarkIds = [];
            for ($i = 1, $n = count($rows); $i < $n; $i++) {
                $id = (int) ($rows[$i]['id'] ?? 0);
                if ($id > 0) {
                    $markIds[] = $id;
                    $groupMarkIds[] = $id;
                }
            }
            if (count($sample) < $sampleLimit) {
                $sample[] = [
                    'planner_id' => (string) $plannerKey,
                    'keep_id' => $keepId,
                    'mark_ids' => $groupMarkIds,
                    'total' => count($rows),
                ];
            }
        }

        return [
            'ok' => true,
            'groups' => $groups,
            'keep_count' => count($keepIds),
            'mark_count' => count($markIds),
            'mark_ids' => $markIds,
            'keep_ids' => $keepIds,
            'sample' => $sample,
        ];
    }

    /**
     * Mark planner rows as status=duplicate (soft archive; keeps newest siblings).
     *
     * @param list<int> $ids
     * @return array{ok:bool,updated?:int,error?:string}
     */
    public static function bulkMarkDuplicate(array $ids): array
    {
        return self::bulkSetStatus($ids, 'duplicate');
    }

    /**
     * Restore rows previously marked duplicate back to planning.
     *
     * @param list<int> $ids
     * @return array{ok:bool,updated?:int,error?:string}
     */
    public static function bulkRestoreFromDuplicate(array $ids): array
    {
        return self::bulkSetStatus($ids, 'planning', 'duplicate');
    }

    /**
     * Apply duplicate marking in batches (for progress UI).
     *
     * @param list<int> $ids
     * @return array{ok:bool,updated?:int,processed?:int,remaining?:int,done?:bool,error?:string}
     */
    public static function dedupeApplyBatch(array $ids, int $offset = 0, int $batchSize = 100): array
    {
        PlannerEntryModel::ensureLoaded();

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
            return $id > 0;
        })));
        $offset = max(0, $offset);
        $batchSize = max(1, min(200, $batchSize));

        if ($ids === []) {
            return ['ok' => true, 'updated' => 0, 'processed' => 0, 'remaining' => 0, 'done' => true];
        }

        $slice = array_slice($ids, $offset, $batchSize);
        if ($slice === []) {
            return [
                'ok' => true,
                'updated' => 0,
                'processed' => count($ids),
                'remaining' => 0,
                'done' => true,
            ];
        }

        $result = self::bulkMarkDuplicate($slice);
        if (empty($result['ok'])) {
            return $result;
        }

        $processed = $offset + count($slice);
        $remaining = max(0, count($ids) - $processed);

        return [
            'ok' => true,
            'updated' => (int) ($result['updated'] ?? 0),
            'processed' => $processed,
            'remaining' => $remaining,
            'done' => $remaining === 0,
        ];
    }

    /**
     * @param list<int> $ids
     * @return array{ok:bool,updated?:int,error?:string}
     */
    public static function bulkSetTrashed(array $ids, bool $trash): array
    {
        PlannerEntryModel::ensureLoaded();

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
            return $id > 0;
        })));
        if ($ids === []) {
            return ['ok' => false, 'error' => 'No entries selected.'];
        }
        if (count($ids) > 500) {
            return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
        }

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        if ($trash) {
            $sql = 'UPDATE `' . $table . '` SET `trashed_at` = NOW(), `updated_at` = NOW()'
                . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NULL';
        } else {
            $sql = 'UPDATE `' . $table . '` SET `trashed_at` = NULL, `updated_at` = NOW()'
                . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NOT NULL';
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            PlannerRecordService::closeDb($conn);

            return ['ok' => false, 'error' => 'Could not update entries.'];
        }

        $stmt->bind_param($types, ...$ids);
        if (!$stmt->execute()) {
            $stmt->close();
            PlannerRecordService::closeDb($conn);

            return ['ok' => false, 'error' => 'Could not update entries.'];
        }

        $updated = (int) $stmt->affected_rows;
        $stmt->close();
        PlannerRecordService::closeDb($conn);

        return ['ok' => true, 'updated' => $updated];
    }

    /**
     * Permanently delete planner entries that are already in trash.
     *
     * @param list<int> $ids
     * @return array{ok:bool,updated?:int,error?:string}
     */
    public static function bulkDeletePermanently(array $ids): array
    {
        PlannerEntryModel::ensureLoaded();

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
            return $id > 0;
        })));
        if ($ids === []) {
            return ['ok' => false, 'error' => 'No entries selected.'];
        }
        if (count($ids) > 500) {
            return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
        }

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = 'DELETE FROM `' . $table . '`'
            . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NOT NULL';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            PlannerRecordService::closeDb($conn);

            return ['ok' => false, 'error' => 'Could not delete entries.'];
        }

        $stmt->bind_param($types, ...$ids);
        if (!$stmt->execute()) {
            $stmt->close();
            PlannerRecordService::closeDb($conn);

            return ['ok' => false, 'error' => 'Could not delete entries.'];
        }

        $updated = (int) $stmt->affected_rows;
        $stmt->close();
        PlannerRecordService::closeDb($conn);

        return ['ok' => true, 'updated' => $updated];
    }

    /**
     * Export selected planner entries as portable JSON payload.
     *
     * @param list<int> $ids
     * @return array{ok:bool,payload?:array<string,mixed>,exported?:int,error?:string}
     */
    public static function exportEntriesByIds(array $ids): array
    {
        PlannerEntryModel::ensureLoaded();

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
            return $id > 0;
        })));
        if ($ids === []) {
            return ['ok' => false, 'error' => 'No entries selected.'];
        }
        if (count($ids) > 500) {
            return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
        }

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $columns = PlannerEntryPresenter::exportColumns();
        $select = implode(', ', array_map(static function (string $col): string {
            return '`' . $col . '`';
        }, $columns));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sql = 'SELECT ' . $select . ' FROM `' . $table . '` WHERE `id` IN (' . $placeholders . ') ORDER BY `id` ASC';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            PlannerRecordService::closeDb($conn);
            return ['ok' => false, 'error' => 'Could not export entries.'];
        }

        $stmt->bind_param($types, ...$ids);
        if (!$stmt->execute()) {
            $stmt->close();
            PlannerRecordService::closeDb($conn);
            return ['ok' => false, 'error' => 'Could not export entries.'];
        }

        $result = $stmt->get_result();
        $entries = [];
        while ($result && ($row = $result->fetch_assoc())) {
            if (!is_array($row)) {
                continue;
            }
            $entry = [];
            foreach ($columns as $column) {
                $value = $row[$column] ?? null;
                if ($column === 'order_id' || $column === 'section_count' || $column === 'quote_load_count') {
                    $entry[$column] = (int) $value;
                    continue;
                }
                $entry[$column] = $value === null ? null : (string) $value;
            }
            $entries[] = $entry;
        }
        $stmt->close();
        PlannerRecordService::closeDb($conn);

        if ($entries === []) {
            return ['ok' => false, 'error' => 'No matching entries found.'];
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $payload = [
            'format' => 'fc-planner-entries',
            'version' => 1,
            'exported_at' => date('c'),
            'source' => [
                'site_host' => $host,
                'table' => $table,
            ],
            'count' => count($entries),
            'entries' => $entries,
        ];

        return [
            'ok' => true,
            'exported' => count($entries),
            'payload' => $payload,
        ];
    }

    /**
     * Import portable planner entry JSON rows.
     *
     * @param list<array<string,mixed>> $entries
     * @param string $mode skip|overwrite|remint
     * @return array{
     *   ok:bool,
     *   imported?:int,
     *   updated?:int,
     *   inserted?:int,
     *   skipped?:int,
     *   remapped?:list<array{from:string,to:string}>,
     *   message?:string,
     *   error?:string
     * }
     */
    public static function importEntries(array $entries, string $mode = 'overwrite'): array
    {
        PlannerEntryModel::ensureLoaded();

        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['skip', 'overwrite', 'remint'], true)) {
            $mode = 'overwrite';
        }
        if ($entries === []) {
            return ['ok' => false, 'error' => 'No entries to import.'];
        }
        if (count($entries) > 500) {
            return ['ok' => false, 'error' => 'Too many entries to import (max 500).'];
        }

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $columns = PlannerEntryPresenter::exportColumns();
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $remapped = [];

        foreach ($entries as $raw) {
            if (!is_array($raw)) {
                $skipped++;
                continue;
            }

            $plannerId = trim((string) ($raw['planner_id'] ?? ''));
            if ($plannerId === '') {
                $skipped++;
                continue;
            }

            // Match only by planner_id — never by database id / source_id.
            unset($raw['id'], $raw['source_id']);

            $existingId = PlannerEntryModel::entryIdForPlanner($plannerId) ?? 0;
            if ($existingId > 0) {
                if ($mode === 'skip') {
                    $skipped++;
                    continue;
                }
                if ($mode === 'remint') {
                    $newId = self::generateUniquePlannerId();
                    $remapped[] = ['from' => $plannerId, 'to' => $newId];
                    $plannerId = $newId;
                    $existingId = 0;
                }
            }

            $row = [];
            foreach ($columns as $column) {
                if ($column === 'planner_id') {
                    $row[$column] = $plannerId;
                    continue;
                }
                if (!array_key_exists($column, $raw)) {
                    continue;
                }
                $value = $raw[$column];
                if ($column === 'order_id' || $column === 'section_count' || $column === 'quote_load_count') {
                    $row[$column] = (int) $value;
                    continue;
                }
                if ($value === null || $value === '') {
                    if (in_array($column, ['trashed_at', 'status_updated_at', 'created_at', 'updated_at'], true)) {
                        $row[$column] = null;
                    } else {
                        $row[$column] = $value === null ? null : '';
                    }
                    continue;
                }
                if (is_array($value) || is_object($value)) {
                    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $row[$column] = is_string($encoded) ? $encoded : '';
                    continue;
                }
                $row[$column] = (string) $value;
            }

            if (!isset($row['updated_at']) || $row['updated_at'] === null || $row['updated_at'] === '') {
                $row['updated_at'] = date('Y-m-d H:i:s');
            }
            if ($existingId <= 0 && (!isset($row['created_at']) || $row['created_at'] === null || $row['created_at'] === '')) {
                $row['created_at'] = date('Y-m-d H:i:s');
            }

            if ($existingId > 0) {
                $sets = [];
                foreach ($row as $column => $value) {
                    $sets[] = '`' . $column . '` = ' . self::encodeImportValue($conn, $value);
                }
                $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `id` = ' . (int) $existingId . ' LIMIT 1';
                if ($conn->query($sql)) {
                    $updated++;
                } else {
                    $skipped++;
                }
                continue;
            }

            $insertColumns = array_keys($row);
            $insertValues = [];
            foreach ($insertColumns as $column) {
                $insertValues[] = self::encodeImportValue($conn, $row[$column]);
            }
            $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $insertColumns) . '`)'
                . ' VALUES (' . implode(', ', $insertValues) . ')';
            if ($conn->query($sql)) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        PlannerRecordService::closeDb($conn);

        $imported = $inserted + $updated;
        $noun = $imported === 1 ? 'entry' : 'entries';
        $message = $imported . ' ' . $noun . ' imported'
            . ($updated > 0 ? ' (' . $updated . ' updated)' : '')
            . ($inserted > 0 ? ' (' . $inserted . ' inserted)' : '')
            . ($skipped > 0 ? ', ' . $skipped . ' skipped' : '')
            . '.';

        return [
            'ok' => true,
            'imported' => $imported,
            'updated' => $updated,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'remapped' => $remapped,
            'message' => $message,
        ];
    }

    /**
     * @param list<int> $ids
     * @return array{ok:bool,updated?:int,error?:string}
     */
    private static function bulkSetStatus(array $ids, string $status, ?string $onlyCurrentStatus = null): array
    {
        PlannerEntryModel::ensureLoaded();

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
            return $id > 0;
        })));
        if ($ids === []) {
            return ['ok' => false, 'error' => 'No entries selected.'];
        }
        if (count($ids) > 500) {
            return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
        }

        $status = strtolower(trim($status));
        if ($status === '' || !preg_match('/^[a-z0-9_\-]{1,64}$/', $status)) {
            return ['ok' => false, 'error' => 'Invalid status.'];
        }

        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = 's' . str_repeat('i', count($ids));
        $params = array_merge([$status], $ids);

        $sql = 'UPDATE `' . $table . '` SET `status` = ?, `status_updated_at` = NOW(), `updated_at` = NOW()'
            . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NULL';

        if ($onlyCurrentStatus !== null) {
            $only = strtolower(trim($onlyCurrentStatus));
            if ($only !== '' && preg_match('/^[a-z0-9_\-]{1,64}$/', $only)) {
                $sql .= ' AND `status` = ?';
                $types .= 's';
                $params[] = $only;
            }
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            PlannerRecordService::closeDb($conn);

            return ['ok' => false, 'error' => 'Could not update entries.'];
        }

        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            PlannerRecordService::closeDb($conn);

            return ['ok' => false, 'error' => 'Could not update entries.'];
        }

        $updated = (int) $stmt->affected_rows;
        $stmt->close();
        PlannerRecordService::closeDb($conn);

        return ['ok' => true, 'updated' => $updated];
    }

    /**
     * @param mixed $value
     */
    private static function encodeImportValue(\mysqli $conn, $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $value = is_string($encoded) ? $encoded : '';
        }

        return "'" . $conn->real_escape_string((string) $value) . "'";
    }

    /**
     * Generate a unique planner_id for remint imports.
     */
    private static function generateUniquePlannerId(): string
    {
        for ($i = 0; $i < 12; $i++) {
            $candidate = StringHelper::randomId(6);
            if (PlannerEntryModel::entryIdForPlanner($candidate) === null) {
                return $candidate;
            }
        }

        return strtoupper(bin2hex(random_bytes(4)));
    }
}
