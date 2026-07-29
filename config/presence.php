<?php
/**
 * FC Admin — near-realtime online presence (file-backed session heartbeats).
 */

declare(strict_types=1);

const FC_PRESENCE_ONLINE_WINDOW = 180; // 3 minutes
const FC_PRESENCE_STALE_MAX_AGE = 86400; // prune files older than 24h
const FC_PRESENCE_TOUCH_THROTTLE = 20; // seconds between disk writes
const FC_PRESENCE_LAST_LOGIN_META = 'fc_last_login';
const FC_PRESENCE_LAST_ACTIVITY_META = 'fc_last_activity';
const FC_PRESENCE_LAST_DEVICE_META = 'fc_last_device';
const FC_PRESENCE_LAST_UA_META = 'fc_last_user_agent';
const FC_PRESENCE_ACTIVITY_META_THROTTLE = 60;

/**
 * Absolute path to data/storage/presence.
 */
function fc_presence_dir(): string
{
    if (!function_exists('fc_storage_presence_dir')) {
        require_once __DIR__ . '/storage.php';
    }

    return fc_storage_presence_dir();
}

/**
 * Safe presence filename based on the WordPress username.
 */
function fc_presence_username_filename(?string $username = null): string
{
    if ($username === null && session_status() === PHP_SESSION_ACTIVE) {
        $username = (string) ($_SESSION['fc_admin_user']['login'] ?? '');
    }

    $username = strtolower(trim((string) $username));
    $username = preg_replace('/[^a-z0-9]+/', '-', $username) ?: '';
    $username = trim($username, '-');

    return $username !== '' ? $username . '.json' : '';
}

/**
 * Absolute path for a user's presence file.
 */
function fc_presence_path(?string $username = null): string
{
    $file = fc_presence_username_filename($username);
    if ($file === '') {
        return '';
    }

    return fc_presence_dir() . DIRECTORY_SEPARATOR . $file;
}

/**
 * Legacy session-id path, used only to clean up old presence filenames.
 */
function fc_presence_legacy_session_path(?string $sessionId = null): string
{
    $id = $sessionId ?? (session_status() === PHP_SESSION_ACTIVE ? session_id() : '');
    $id = preg_replace('/[^a-zA-Z0-9,\-]+/', '', (string) $id) ?: '';

    return $id !== ''
        ? fc_presence_dir() . DIRECTORY_SEPARATOR . $id . '.json'
        : '';
}

/**
 * @return array{ip:string,user_agent:string,device:string}
 */
function fc_presence_client_meta(): array
{
    if (!function_exists('fc_planner_client_ip')) {
        require_once __DIR__ . '/planners.php';
    }

    $ua = function_exists('fc_planner_client_user_agent') ? fc_planner_client_user_agent() : '';
    $device = function_exists('fc_planner_client_device') ? fc_planner_client_device($ua) : 'Unknown';

    return [
        'ip' => function_exists('fc_planner_client_ip') ? fc_planner_client_ip() : '',
        'user_agent' => $ua,
        'device' => $device,
    ];
}

/**
 * Format a unix timestamp for presence JSON (Y-m-d H:i:s).
 */
function fc_presence_format_datetime(int $ts): string
{
    if ($ts <= 0) {
        return '';
    }

    return date('Y-m-d H:i:s', $ts);
}

/**
 * Write / refresh the presence file for the current session.
 *
 * @param array{id:int,login?:string,email?:string,display_name?:string,logged_in_at?:int} $user
 */
function fc_presence_touch(array $user, bool $force = false): void
{
    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $login = (string) ($user['login'] ?? ($_SESSION['fc_admin_user']['login'] ?? ''));
    $path = fc_presence_path($login);
    if ($path === '') {
        return;
    }

    $now = time();
    $existing = null;
    if (!$force && is_readable($path)) {
        $raw = @file_get_contents($path);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $existing = $decoded;
            $last = strtotime((string) ($decoded['last_activity_at'] ?? '')) ?: 0;
            if ($last > 0 && ($now - $last) < fc_presence_touch_throttle_seconds()) {
                return;
            }
        }
    }

    $loggedInAt = (int) ($user['logged_in_at'] ?? 0);
    if ($loggedInAt <= 0 && is_array($existing)) {
        $loggedInAt = strtotime((string) ($existing['logged_in_at'] ?? '')) ?: 0;
    }
    if ($loggedInAt <= 0 && isset($_SESSION['fc_admin_user']['logged_in_at'])) {
        $loggedInAt = (int) $_SESSION['fc_admin_user']['logged_in_at'];
    }
    if ($loggedInAt <= 0) {
        $loggedInAt = $now;
    }

    $meta = fc_presence_client_meta();
    $remember = !empty($_SESSION['fc_admin_remember']);
    $authDbKey = function_exists('fc_admin_auth_db_key') ? (string) fc_admin_auth_db_key() : '';

    $payload = [
        'user_id' => $userId,
        'login' => (string) ($user['login'] ?? ($existing['login'] ?? '')),
        'display_name' => (string) ($user['display_name'] ?? ($existing['display_name'] ?? '')),
        'email' => (string) ($user['email'] ?? ($existing['email'] ?? '')),
        'logged_in_at' => fc_presence_format_datetime($loggedInAt),
        'last_activity_at' => fc_presence_format_datetime($now),
        'ip' => $meta['ip'],
        'user_agent' => $meta['user_agent'],
        'device' => $meta['device'],
        'remember' => $remember,
        'auth_db_key' => $authDbKey,
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return;
    }

    @file_put_contents($path, $json, LOCK_EX);
    $legacyPath = fc_presence_legacy_session_path();
    if ($legacyPath !== '' && $legacyPath !== $path && is_file($legacyPath)) {
        @unlink($legacyPath);
    }
    fc_presence_set_last_activity($userId, $now, $meta['device'], $meta['user_agent']);
}

/**
 * Remove the presence file for the current (or given) username.
 */
function fc_presence_forget(?string $username = null): void
{
    $path = fc_presence_path($username);
    if ($path !== '' && is_file($path)) {
        @unlink($path);
    }

    $legacyPath = fc_presence_legacy_session_path();
    if ($legacyPath !== '' && $legacyPath !== $path && is_file($legacyPath)) {
        @unlink($legacyPath);
    }
}

/**
 * @return array<string, mixed>|null
 */
function fc_presence_read_file(string $path): ?array
{
    if (!is_readable($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);

    return is_array($data) ? $data : null;
}

/**
 * Resolved activity update interval (system setting, falling back to the constant).
 */
function fc_presence_touch_throttle_seconds(): int
{
    if (function_exists('fc_system_presence_update_interval_seconds')) {
        return fc_system_presence_update_interval_seconds();
    }

    return FC_PRESENCE_TOUCH_THROTTLE;
}

/**
 * How often last-activity usermeta is written (at least once per minute by default).
 */
function fc_presence_activity_meta_throttle_seconds(): int
{
    $touch = fc_presence_touch_throttle_seconds();

    return max(FC_PRESENCE_ACTIVITY_META_THROTTLE, $touch);
}

/**
 * Resolved online window (system setting, falling back to the constant).
 */
function fc_presence_online_window_seconds(): int
{
    if (function_exists('fc_system_presence_online_window_seconds')) {
        return fc_system_presence_online_window_seconds();
    }

    return FC_PRESENCE_ONLINE_WINDOW;
}

/**
 * Map of online user_id => last_activity unix timestamp.
 *
 * @return array<int, int>
 */
function fc_presence_online_map(?int $windowSeconds = null): array
{
    $windowSeconds = $windowSeconds ?? fc_presence_online_window_seconds();
    $dir = fc_presence_dir();
    $now = time();
    $cutoff = $now - max(30, $windowSeconds);
    $staleCutoff = $now - FC_PRESENCE_STALE_MAX_AGE;
    $map = [];

    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
        if (!is_string($file) || !is_file($file)) {
            continue;
        }

        $mtime = (int) @filemtime($file);
        if ($mtime > 0 && $mtime < $staleCutoff) {
            @unlink($file);
            continue;
        }

        $data = fc_presence_read_file($file);
        if ($data === null) {
            continue;
        }

        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }

        $last = strtotime((string) ($data['last_activity_at'] ?? '')) ?: $mtime;
        if ($last < $cutoff) {
            continue;
        }

        if (!isset($map[$userId]) || $last > $map[$userId]) {
            $map[$userId] = $last;
        }
    }

    return $map;
}

function fc_presence_is_online(int $userId, ?int $windowSeconds = null): bool
{
    if ($userId <= 0) {
        return false;
    }
    $map = fc_presence_online_map($windowSeconds);

    return isset($map[$userId]);
}

/**
 * Persist an FC timestamp in WordPress usermeta.
 */
function fc_presence_set_user_timestamp(int $userId, string $metaKey, ?int $timestamp = null): void
{
    $metaKey = trim($metaKey);
    if ($userId <= 0 || $metaKey === '' || !function_exists('fc_auth_db')) {
        return;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return;
    }

    $metaTable = fc_auth_usermeta_table();
    $ts = $timestamp ?? time();
    $metaValue = (string) $ts;

    $select = $conn->prepare("SELECT umeta_id FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1");
    if (!$select) {
        $conn->close();

        return;
    }
    $select->bind_param('is', $userId, $metaKey);
    $select->execute();
    $result = $select->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $select->close();

    if (is_array($row) && !empty($row['umeta_id'])) {
        $umetaId = (int) $row['umeta_id'];
        $update = $conn->prepare("UPDATE `{$metaTable}` SET meta_value = ? WHERE umeta_id = ? LIMIT 1");
        if ($update) {
            $update->bind_param('si', $metaValue, $umetaId);
            $update->execute();
            $update->close();
        }
    } else {
        $insert = $conn->prepare("INSERT INTO `{$metaTable}` (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
        if ($insert) {
            $insert->bind_param('iss', $userId, $metaKey, $metaValue);
            $insert->execute();
            $insert->close();
        }
    }

    $conn->close();
}

/**
 * Persist last FC admin login timestamp in usermeta.
 */
function fc_presence_set_last_login(int $userId, ?int $timestamp = null): void
{
    fc_presence_set_user_timestamp($userId, FC_PRESENCE_LAST_LOGIN_META, $timestamp);
}

/**
 * Persist recent FC admin activity without writing usermeta on every request.
 */
function fc_presence_set_last_activity(
    int $userId,
    ?int $timestamp = null,
    string $device = '',
    string $userAgent = ''
): void {
    $now = $timestamp ?? time();
    $lastWrite = isset($_SESSION['fc_presence_activity_meta_at'])
        ? (int) $_SESSION['fc_presence_activity_meta_at']
        : 0;
    if ($lastWrite > 0 && ($now - $lastWrite) < fc_presence_activity_meta_throttle_seconds()) {
        return;
    }

    fc_presence_set_user_timestamp($userId, FC_PRESENCE_LAST_ACTIVITY_META, $now);
    if ($device !== '') {
        fc_presence_set_user_meta_string($userId, FC_PRESENCE_LAST_DEVICE_META, $device);
    }
    if ($userAgent !== '') {
        fc_presence_set_user_meta_string($userId, FC_PRESENCE_LAST_UA_META, $userAgent);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['fc_presence_activity_meta_at'] = $now;
    }
}

/**
 * Persist a string usermeta value.
 */
function fc_presence_set_user_meta_string(int $userId, string $metaKey, string $metaValue): void
{
    $metaKey = trim($metaKey);
    if ($userId <= 0 || $metaKey === '' || !function_exists('fc_auth_db')) {
        return;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return;
    }

    $metaTable = fc_auth_usermeta_table();
    $select = $conn->prepare("SELECT umeta_id FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1");
    if (!$select) {
        $conn->close();

        return;
    }
    $select->bind_param('is', $userId, $metaKey);
    $select->execute();
    $result = $select->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $select->close();

    if (is_array($row) && !empty($row['umeta_id'])) {
        $umetaId = (int) $row['umeta_id'];
        $update = $conn->prepare("UPDATE `{$metaTable}` SET meta_value = ? WHERE umeta_id = ? LIMIT 1");
        if ($update) {
            $update->bind_param('si', $metaValue, $umetaId);
            $update->execute();
            $update->close();
        }
    } else {
        $insert = $conn->prepare("INSERT INTO `{$metaTable}` (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
        if ($insert) {
            $insert->bind_param('iss', $userId, $metaKey, $metaValue);
            $insert->execute();
            $insert->close();
        }
    }

    $conn->close();
}

/**
 * @param list<int> $userIds
 * @return array<int, int> user_id => unix timestamp
 */
function fc_presence_user_timestamp_map(array $userIds, string $metaKey): array
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
    if ($ids === [] || $metaKey === '' || !function_exists('fc_auth_db')) {
        return [];
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return [];
    }

    $metaTable = fc_auth_usermeta_table();
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
            $ts = (int) ($row['meta_value'] ?? 0);
            if ($uid > 0 && $ts > 0) {
                $map[$uid] = $ts;
            }
        }
    }
    $stmt->close();
    $conn->close();

    return $map;
}

/**
 * @param list<int> $userIds
 * @return array<int, int>
 */
function fc_presence_last_login_map(array $userIds): array
{
    return fc_presence_user_timestamp_map($userIds, FC_PRESENCE_LAST_LOGIN_META);
}

/**
 * @param list<int> $userIds
 * @return array<int, int>
 */
function fc_presence_last_activity_map(array $userIds): array
{
    return fc_presence_user_timestamp_map($userIds, FC_PRESENCE_LAST_ACTIVITY_META);
}

/**
 * @param list<int> $userIds
 * @return array<int, string>
 */
function fc_presence_user_string_meta_map(array $userIds, string $metaKey): array
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
    if ($ids === [] || $metaKey === '' || !function_exists('fc_auth_db')) {
        return [];
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return [];
    }

    $metaTable = fc_auth_usermeta_table();
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
            $val = trim((string) ($row['meta_value'] ?? ''));
            if ($uid > 0 && $val !== '') {
                $map[$uid] = $val;
            }
        }
    }
    $stmt->close();
    $conn->close();

    return $map;
}

/**
 * Latest known client details per user from presence files (most recent activity wins).
 *
 * @return array<int, array{device:string,user_agent:string,last_activity:int}>
 */
function fc_presence_latest_client_map(): array
{
    $dir = fc_presence_dir();
    $now = time();
    $staleCutoff = $now - FC_PRESENCE_STALE_MAX_AGE;
    $map = [];

    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
        if (!is_string($file) || !is_file($file)) {
            continue;
        }

        $mtime = (int) @filemtime($file);
        if ($mtime > 0 && $mtime < $staleCutoff) {
            @unlink($file);
            continue;
        }

        $data = fc_presence_read_file($file);
        if ($data === null) {
            continue;
        }

        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }

        $last = strtotime((string) ($data['last_activity_at'] ?? '')) ?: $mtime;
        if ($last <= 0) {
            continue;
        }

        if (isset($map[$userId]) && $last <= (int) ($map[$userId]['last_activity'] ?? 0)) {
            continue;
        }

        $map[$userId] = [
            'device' => trim((string) ($data['device'] ?? '')),
            'user_agent' => trim((string) ($data['user_agent'] ?? '')),
            'last_activity' => $last,
        ];
    }

    return $map;
}

/**
 * Resolve device + user agent for users (presence file first, then usermeta).
 *
 * @param list<int> $userIds
 * @return array<int, array{device:string,user_agent:string}>
 */
function fc_presence_client_map_for_users(array $userIds): array
{
    $ids = [];
    foreach ($userIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    if ($ids === []) {
        return [];
    }

    $fromFiles = fc_presence_latest_client_map();
    $deviceMeta = fc_presence_user_string_meta_map($ids, FC_PRESENCE_LAST_DEVICE_META);
    $uaMeta = fc_presence_user_string_meta_map($ids, FC_PRESENCE_LAST_UA_META);

    $out = [];
    foreach ($ids as $id) {
        $device = '';
        $ua = '';
        if (isset($fromFiles[$id])) {
            $device = (string) ($fromFiles[$id]['device'] ?? '');
            $ua = (string) ($fromFiles[$id]['user_agent'] ?? '');
        }
        if ($device === '' && isset($deviceMeta[$id])) {
            $device = (string) $deviceMeta[$id];
        }
        if ($ua === '' && isset($uaMeta[$id])) {
            $ua = (string) $uaMeta[$id];
        }
        if ($device === '' && $ua === '') {
            continue;
        }
        $out[$id] = [
            'device' => $device !== '' ? $device : 'Unknown',
            'user_agent' => $ua,
        ];
    }

    return $out;
}

/**
 * Payload for Users presence API / list enrichment.
 *
 * @param list<int> $userIds
 * @return array{
 *   ok:bool,
 *   online:array<string,bool>,
 *   last_activity:array<string,int>,
 *   last_login:array<string,int>,
 *   devices:array<string,array{device:string,user_agent:string}>
 * }
 */
function fc_presence_api_payload(array $userIds = []): array
{
    $onlineMap = fc_presence_online_map();
    $ids = [];
    foreach ($userIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    if ($ids === []) {
        $ids = array_keys($onlineMap);
    }

    $lastLogin = fc_presence_last_login_map($ids);
    $persistedActivity = fc_presence_last_activity_map($ids);
    $online = [];
    $lastActivity = $persistedActivity;
    foreach ($ids as $id) {
        $key = (string) $id;
        $online[$key] = isset($onlineMap[$id]);
        if (isset($onlineMap[$id])) {
            $lastActivity[$id] = max(
                (int) ($lastActivity[$id] ?? 0),
                (int) $onlineMap[$id]
            );
        }
    }

    // Also include online users not in the requested id list.
    foreach ($onlineMap as $id => $ts) {
        $key = (string) $id;
        if (!isset($online[$key])) {
            $online[$key] = true;
            $lastActivity[$id] = (int) $ts;
        }
    }

    $lastLoginOut = [];
    foreach ($lastLogin as $id => $ts) {
        $lastLoginOut[(string) $id] = (int) $ts;
    }
    $lastActivityOut = [];
    foreach ($lastActivity as $id => $ts) {
        $lastActivityOut[(string) $id] = (int) $ts;
    }

    $devicesOut = [];
    foreach (fc_presence_client_map_for_users($ids) as $id => $client) {
        $device = (string) ($client['device'] ?? 'Unknown');
        $ua = (string) ($client['user_agent'] ?? '');
        $devicesOut[(string) $id] = [
            'device' => $device !== '' ? $device : 'Unknown',
            'user_agent' => $ua,
        ];
    }

    return [
        'ok' => true,
        'online' => $online,
        'last_activity' => $lastActivityOut,
        'last_login' => $lastLoginOut,
        'devices' => $devicesOut,
        'window' => fc_presence_online_window_seconds(),
    ];
}
