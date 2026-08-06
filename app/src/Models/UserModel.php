<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Core\Model;
use Fc\Admin\Services\Database;
use Fc\Admin\Services\DatabaseConfigService;

/**
 * WordPress admin users data access ({prefix}users / {prefix}usermeta, pinned auth DB).
 *
 * Extends Core\Model for the CodeIgniter-4-like base-Model contract (find/insert/update/delete) —
 * currently inert/additive: every existing static method below is unchanged, nothing yet calls
 * the inherited instance methods. connectionConfig() MUST return the pinned auth DB config
 * (mirroring db() below exactly), not the switchable site DB, or the inherited methods would
 * silently target the wrong database the moment something starts calling them.
 */
final class UserModel extends Model
{
    protected string $primaryKey = 'ID';

    protected function resolveTable(): string
    {
        // Bare table name — Database::insert()/update()/select_where()/delete() prepend the
        // prefix themselves via their own $this->prefix. usersTable() returns the ALREADY
        // fully-qualified name (for UserModel's own raw-SQL prepared statements below) — do
        // not reuse it here or the prefix gets applied twice (e.g. "wp_wp_users").
        return 'users';
    }

    protected function connectionConfig(): ?array
    {
        return DatabaseConfigService::resolveAuthConfig();
    }

    public static function db(): ?\mysqli
    {
        $db = new Database(DatabaseConfigService::resolveAuthConfig());

        return $db->connect();
    }

    public static function usersTable(): string
    {
        $cfg = DatabaseConfigService::resolveAuthConfig();
        $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';

        return $prefix . 'users';
    }

    public static function usermetaTable(): string
    {
        $cfg = DatabaseConfigService::resolveAuthConfig();
        $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';

        return $prefix . 'usermeta';
    }

    /**
     * @return array{ID:int,user_login:string,user_email:string,user_pass:string,display_name:string}|null
     */
    public static function findByLogin(string $loginOrEmail): ?array
    {
        $loginOrEmail = trim($loginOrEmail);
        if ($loginOrEmail === '') {
            return null;
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return null;
        }

        $table = self::usersTable();
        $sql = "SELECT ID, user_login, user_email, user_pass, display_name
                FROM `{$table}`
                WHERE user_login = ? OR user_email = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();

            return null;
        }

        $stmt->bind_param('ss', $loginOrEmail, $loginOrEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();

        if (!is_array($row) || empty($row['ID'])) {
            return null;
        }

        return [
            'ID'           => (int) $row['ID'],
            'user_login'   => (string) $row['user_login'],
            'user_email'   => (string) $row['user_email'],
            'user_pass'    => (string) $row['user_pass'],
            'display_name' => (string) ($row['display_name'] ?? $row['user_login']),
        ];
    }

    /**
     * @return array{ID:int,user_login:string,user_email:string,display_name:string}|null
     */
    public static function findById(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return null;
        }

        $table = self::usersTable();
        $sql = "SELECT ID, user_login, user_email, display_name
                FROM `{$table}`
                WHERE ID = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();

            return null;
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();

        if (!is_array($row) || empty($row['ID'])) {
            return null;
        }

        return [
            'ID'           => (int) $row['ID'],
            'user_login'   => (string) $row['user_login'],
            'user_email'   => (string) $row['user_email'],
            'display_name' => (string) ($row['display_name'] ?? $row['user_login']),
        ];
    }

    /**
     * Current WordPress password hash for a user (for remember-token binding).
     */
    public static function passwordHash(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return '';
        }

        $table = self::usersTable();
        $stmt = $conn->prepare("SELECT user_pass FROM `{$table}` WHERE ID = ? LIMIT 1");
        if (!$stmt) {
            $conn->close();

            return '';
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();

        return is_array($row) ? (string) ($row['user_pass'] ?? '') : '';
    }

    /**
     * WP role slugs for a user from capabilities meta (always the auth DB prefix,
     * never the switched admin data site prefix).
     *
     * @return list<string>
     */
    public static function roles(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return [];
        }

        $metaTable = self::usermetaTable();
        $capKey = self::capabilitiesMetaKey();

        $stmt = $conn->prepare("SELECT meta_value FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1");
        if (!$stmt) {
            $conn->close();

            return [];
        }

        $stmt->bind_param('is', $userId, $capKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();

        return is_array($row) ? self::rolesFromCaps($row['meta_value'] ?? null) : [];
    }

    public static function usermetaGet(int $userId, string $metaKey): ?string
    {
        if ($userId <= 0 || $metaKey === '') {
            return null;
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return null;
        }

        $metaTable = self::usermetaTable();
        $stmt = $conn->prepare("SELECT meta_value FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1");
        if (!$stmt) {
            $conn->close();

            return null;
        }

        $stmt->bind_param('is', $userId, $metaKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();

        if (!is_array($row) || !array_key_exists('meta_value', $row)) {
            return null;
        }

        return (string) $row['meta_value'];
    }

    public static function usermetaSet(int $userId, string $metaKey, string $metaValue): bool
    {
        if ($userId <= 0 || $metaKey === '') {
            return false;
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return false;
        }

        $metaTable = self::usermetaTable();
        $select = $conn->prepare("SELECT umeta_id FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1");
        if (!$select) {
            $conn->close();

            return false;
        }

        $select->bind_param('is', $userId, $metaKey);
        $select->execute();
        $result = $select->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $select->close();

        $ok = false;
        if (is_array($row) && !empty($row['umeta_id'])) {
            $umetaId = (int) $row['umeta_id'];
            $update = $conn->prepare("UPDATE `{$metaTable}` SET meta_value = ? WHERE umeta_id = ? LIMIT 1");
            if ($update) {
                $update->bind_param('si', $metaValue, $umetaId);
                $ok = $update->execute();
                $update->close();
            }
        } else {
            $insert = $conn->prepare("INSERT INTO `{$metaTable}` (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
            if ($insert) {
                $insert->bind_param('iss', $userId, $metaKey, $metaValue);
                $ok = $insert->execute();
                $insert->close();
            }
        }

        $conn->close();

        return $ok;
    }

    public static function usermetaDelete(int $userId, string $metaKey): void
    {
        if ($userId <= 0 || $metaKey === '') {
            return;
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return;
        }

        $metaTable = self::usermetaTable();
        $stmt = $conn->prepare("DELETE FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('is', $userId, $metaKey);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }

    /**
     * Delete a usermeta row by meta_key alone, regardless of owner (used when the
     * owning user id isn't known — e.g. revoking a remember-token cookie).
     */
    public static function usermetaDeleteByKey(string $metaKey): void
    {
        if ($metaKey === '') {
            return;
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return;
        }

        $metaTable = self::usermetaTable();
        $stmt = $conn->prepare("DELETE FROM `{$metaTable}` WHERE meta_key = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $metaKey);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }

    /**
     * @return list<array{meta_key:string,meta_value:string}>
     */
    public static function usermetaLike(int $userId, string $likePattern): array
    {
        if ($userId <= 0) {
            return [];
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return [];
        }

        $metaTable = self::usermetaTable();
        $stmt = $conn->prepare(
            "SELECT meta_key, meta_value FROM `{$metaTable}` WHERE user_id = ? AND meta_key LIKE ?"
        );
        if (!$stmt) {
            $conn->close();

            return [];
        }

        $stmt->bind_param('is', $userId, $likePattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = [
                'meta_key' => (string) ($row['meta_key'] ?? ''),
                'meta_value' => (string) ($row['meta_value'] ?? ''),
            ];
        }
        $stmt->close();
        $conn->close();

        return $rows;
    }

    /**
     * @param list<int> $userIds
     * @return array<int, string> user_id => meta_value
     */
    public static function usermetaMapForUsers(array $userIds, string $metaKey): array
    {
        $ids = [];
        foreach ($userIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        $ids = array_keys($ids);
        $metaKey = trim($metaKey);
        if ($ids === [] || $metaKey === '') {
            return [];
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return [];
        }

        $metaTable = self::usermetaTable();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids)) . 's';
        $sql = "SELECT user_id, meta_value FROM `{$metaTable}`
            WHERE user_id IN ({$placeholders}) AND meta_key = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();

            return [];
        }

        $bind = $ids;
        $bind[] = $metaKey;
        $stmt->bind_param($types, ...$bind);
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!is_array($row)) {
                    continue;
                }
                $uid = (int) ($row['user_id'] ?? 0);
                if ($uid > 0) {
                    $map[$uid] = (string) ($row['meta_value'] ?? '');
                }
            }
        }
        $stmt->close();
        $conn->close();

        return $map;
    }

    /**
     * Resolve the owning user id for an exact meta_key match.
     */
    public static function usermetaOwnerByKey(string $metaKey): int
    {
        if ($metaKey === '') {
            return 0;
        }

        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return 0;
        }

        $metaTable = self::usermetaTable();
        $stmt = $conn->prepare("SELECT user_id FROM `{$metaTable}` WHERE meta_key = ? LIMIT 1");
        if (!$stmt) {
            $conn->close();

            return 0;
        }

        $stmt->bind_param('s', $metaKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();

        return is_array($row) ? (int) ($row['user_id'] ?? 0) : 0;
    }

    /**
     * Meta key for the serialized WP roles blob, resolved against the pinned auth DB
     * (not the switchable data DB) so it always matches self::usersTable()/self::usermetaTable().
     */
    private static function capabilitiesMetaKey(): string
    {
        $cfg = DatabaseConfigService::resolveAuthConfig();
        $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';

        return $prefix . 'capabilities';
    }

    /**
     * Decode a serialized WP capabilities blob (a:1:{s:13:"administrator";b:1;}) into role slugs.
     *
     * @param mixed $capsRaw
     * @return list<string>
     */
    private static function rolesFromCaps($capsRaw): array
    {
        if ($capsRaw === null || $capsRaw === '') {
            return [];
        }

        $caps = @unserialize((string) $capsRaw);
        if (!is_array($caps)) {
            return [];
        }

        $roles = [];
        foreach ($caps as $role => $enabled) {
            if (!$enabled) {
                continue;
            }
            $role = strtolower(trim((string) $role));
            if ($role === '') {
                continue;
            }
            $roles[] = $role;
        }

        return $roles;
    }

    /**
     * Count users per role (users with multiple roles count in each).
     *
     * @return array{ok:bool,total:int,roles:array<string,int>,error:?string}
     */
    public static function roleCounts(): array
    {
        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return ['ok' => false, 'total' => 0, 'roles' => [], 'error' => 'Could not connect to database.'];
        }

        $usersTable = self::usersTable();
        $metaTable = self::usermetaTable();
        $capKey = self::capabilitiesMetaKey();

        $total = 0;
        $totalResult = $conn->query('SELECT COUNT(*) AS c FROM `' . $conn->real_escape_string($usersTable) . '`');
        if ($totalResult) {
            $totalRow = $totalResult->fetch_assoc();
            $total = (int) ($totalRow['c'] ?? 0);
            $totalResult->free();
        }

        $sql = "SELECT um.meta_value AS capabilities
            FROM `{$usersTable}` u
            INNER JOIN `{$metaTable}` um
                ON um.user_id = u.ID AND um.meta_key = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = $conn->error ?: 'Failed to prepare role counts query.';
            $conn->close();

            return ['ok' => false, 'total' => $total, 'roles' => [], 'error' => $error];
        }

        $stmt->bind_param('s', $capKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $roles = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                foreach (self::rolesFromCaps($row['capabilities'] ?? null) as $role) {
                    if (!isset($roles[$role])) {
                        $roles[$role] = 0;
                    }
                    $roles[$role]++;
                }
            }
        }

        $stmt->close();
        $conn->close();

        return ['ok' => true, 'total' => $total, 'roles' => $roles, 'error' => null];
    }

    /**
     * @param list<int> $userIds When non-empty, restrict results to these user IDs.
     * @return array{ok:bool,items:list<array<string,mixed>>,total:int,error:?string}
     */
    public static function list(
        string $q = '',
        string $role = '',
        int $limit = 30,
        int $offset = 0,
        array $userIds = []
    ): array {
        $conn = self::db();
        if (!$conn instanceof \mysqli) {
            return ['ok' => false, 'items' => [], 'total' => 0, 'error' => 'Could not connect to database.'];
        }

        $ids = [];
        foreach ($userIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        $ids = array_keys($ids);
        if ($userIds !== [] && $ids === []) {
            $conn->close();

            return ['ok' => true, 'items' => [], 'total' => 0, 'error' => null];
        }

        $usersTable = self::usersTable();
        $metaTable = self::usermetaTable();
        $capKey = self::capabilitiesMetaKey();

        $where = ['1=1'];
        $types = '';
        $params = [];

        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(u.user_login LIKE ? OR u.user_email LIKE ? OR u.display_name LIKE ? OR CAST(u.ID AS CHAR) LIKE ?)';
            $types .= 'ssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($role !== '') {
            // Serialized WP caps look like: a:1:{s:13:"administrator";b:1;}
            $roleNeedle = '%"' . $role . '";b:1%';
            $where[] = 'um.meta_value LIKE ?';
            $types .= 's';
            $params[] = $roleNeedle;
        }

        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $where[] = "u.ID IN ({$placeholders})";
            $types .= str_repeat('i', count($ids));
            foreach ($ids as $id) {
                $params[] = $id;
            }
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(DISTINCT u.ID) AS c
            FROM `{$usersTable}` u
            LEFT JOIN `{$metaTable}` um
                ON um.user_id = u.ID AND um.meta_key = ?
            WHERE {$whereSql}";

        $countTypes = 's' . $types;
        $countParams = array_merge([$capKey], $params);

        $countStmt = $conn->prepare($countSql);
        if (!$countStmt) {
            $error = $conn->error ?: 'Failed to prepare user count query.';
            $conn->close();

            return ['ok' => false, 'items' => [], 'total' => 0, 'error' => $error];
        }

        $countStmt->bind_param($countTypes, ...$countParams);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult ? $countResult->fetch_assoc() : null;
        $countStmt->close();
        $total = (int) ($countRow['c'] ?? 0);

        $sql = "SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered, um.meta_value AS capabilities
            FROM `{$usersTable}` u
            LEFT JOIN `{$metaTable}` um
                ON um.user_id = u.ID AND um.meta_key = ?
            WHERE {$whereSql}
            ORDER BY u.ID DESC";

        $listTypes = 's' . $types;
        $listParams = array_merge([$capKey], $params);

        if ($limit > 0) {
            $sql .= ' LIMIT ? OFFSET ?';
            $listTypes .= 'ii';
            $listParams[] = $limit;
            $listParams[] = max(0, $offset);
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = $conn->error ?: 'Failed to prepare users query.';
            $conn->close();

            return ['ok' => false, 'items' => [], 'total' => $total, 'error' => $error];
        }

        $stmt->bind_param($listTypes, ...$listParams);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!is_array($row)) {
                    continue;
                }
                $items[] = [
                    'id' => (int) ($row['ID'] ?? 0),
                    'user_login' => (string) ($row['user_login'] ?? ''),
                    'user_email' => (string) ($row['user_email'] ?? ''),
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'user_registered' => (string) ($row['user_registered'] ?? ''),
                    'roles' => self::rolesFromCaps($row['capabilities'] ?? null),
                ];
            }
        }

        $stmt->close();
        $conn->close();

        return ['ok' => true, 'items' => $items, 'total' => $total, 'error' => null];
    }
}
