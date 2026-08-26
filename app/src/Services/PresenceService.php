<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\FileHelper;
use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Models\UserModel;
use Fc\Admin\Settings\SystemSettings;

/**
 * FC Admin — near-realtime online presence (file-backed session heartbeats).
 */
final class PresenceService
{
    private const ONLINE_WINDOW = 180; // 3 minutes
    private const STALE_MAX_AGE = 86400; // prune files older than 24h
    private const TOUCH_THROTTLE = 20; // seconds between disk writes
    private const LAST_LOGIN_META = 'fc_last_login';
    private const LAST_ACTIVITY_META = 'fc_last_activity';
    private const LAST_DEVICE_META = 'fc_last_device';
    private const LAST_UA_META = 'fc_last_user_agent';
    private const ACTIVITY_META_THROTTLE = 60;

    /**
     * Absolute path to writable/storage/presence.
     */
    public static function dir(): string
    {
        return CacheStorageService::presenceDir();
    }

    /**
     * Safe presence filename based on the WordPress username.
     */
    public static function usernameFilename(?string $username = null): string
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
    public static function path(?string $username = null): string
    {
        $file = self::usernameFilename($username);
        if ($file === '') {
            return '';
        }

        return self::dir() . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Legacy session-id path, used only to clean up old presence filenames.
     */
    public static function legacySessionPath(?string $sessionId = null): string
    {
        $id = $sessionId ?? (session_status() === PHP_SESSION_ACTIVE ? session_id() : '');
        $id = preg_replace('/[^a-zA-Z0-9,\-]+/', '', (string) $id) ?: '';

        return $id !== ''
            ? self::dir() . DIRECTORY_SEPARATOR . $id . '.json'
            : '';
    }

    /**
     * @return array{ip:string,user_agent:string,device:string}
     */
    public static function clientMeta(): array
    {
        $ua = RequestHelper::clientUserAgent();
        $device = RequestHelper::clientDevice($ua);

        return [
            'ip' => RequestHelper::clientIp(),
            'user_agent' => $ua,
            'device' => $device,
        ];
    }

    /**
     * Format a unix timestamp for presence JSON (Y-m-d H:i:s).
     */
    public static function formatDatetime(int $ts): string
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
    public static function touch(array $user, bool $force = false): void
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $login = (string) ($user['login'] ?? ($_SESSION['fc_admin_user']['login'] ?? ''));
        $path = self::path($login);
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
                if ($last > 0 && ($now - $last) < self::touchThrottleSeconds()) {
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

        $meta = self::clientMeta();
        $remember = !empty($_SESSION['fc_admin_remember']);
        $authDbKey = AdminSiteRegistry::authDbKey();

        $payload = [
            'user_id' => $userId,
            'login' => (string) ($user['login'] ?? ($existing['login'] ?? '')),
            'display_name' => (string) ($user['display_name'] ?? ($existing['display_name'] ?? '')),
            'email' => (string) ($user['email'] ?? ($existing['email'] ?? '')),
            'logged_in_at' => self::formatDatetime($loggedInAt),
            'last_activity_at' => self::formatDatetime($now),
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
        $legacyPath = self::legacySessionPath();
        if ($legacyPath !== '' && $legacyPath !== $path && is_file($legacyPath)) {
            @unlink($legacyPath);
        }
        self::setLastActivity($userId, $now, $meta['device'], $meta['user_agent']);
    }

    /**
     * Remove the presence file for the current (or given) username.
     */
    public static function forget(?string $username = null): void
    {
        $path = self::path($username);
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }

        $legacyPath = self::legacySessionPath();
        if ($legacyPath !== '' && $legacyPath !== $path && is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function readFile(string $path): ?array
    {
        return FileHelper::readJsonFile($path);
    }

    /**
     * Resolved activity update interval (system setting, falling back to the constant).
     */
    public static function touchThrottleSeconds(): int
    {
        return SystemSettings::presenceUpdateIntervalSeconds();
    }

    /**
     * How often last-activity usermeta is written (at least once per minute by default).
     */
    public static function activityMetaThrottleSeconds(): int
    {
        $touch = self::touchThrottleSeconds();

        return max(self::ACTIVITY_META_THROTTLE, $touch);
    }

    /**
     * Resolved online window (system setting, falling back to the constant).
     */
    public static function onlineWindowSeconds(): int
    {
        return SystemSettings::presenceOnlineWindowSeconds();
    }

    /**
     * Map of online user_id => last_activity unix timestamp.
     *
     * @return array<int, int>
     */
    public static function onlineMap(?int $windowSeconds = null): array
    {
        $windowSeconds = $windowSeconds ?? self::onlineWindowSeconds();
        $cutoff = time() - max(30, $windowSeconds);
        $map = [];

        self::scanPresence(static function (int $userId, int $last, array $data) use ($cutoff, &$map): void {
            if ($last < $cutoff) {
                return;
            }
            if (!isset($map[$userId]) || $last > $map[$userId]) {
                $map[$userId] = $last;
            }
        });

        return $map;
    }

    /**
     * Persist an FC timestamp in WordPress usermeta.
     */
    public static function setUserTimestamp(int $userId, string $metaKey, ?int $timestamp = null): void
    {
        $metaKey = trim($metaKey);
        if ($userId <= 0 || $metaKey === '') {
            return;
        }

        UserModel::usermetaSet($userId, $metaKey, (string) ($timestamp ?? time()));
    }

    /**
     * Persist last FC admin login timestamp in usermeta.
     */
    public static function setLastLogin(int $userId, ?int $timestamp = null): void
    {
        self::setUserTimestamp($userId, self::LAST_LOGIN_META, $timestamp);
    }

    /**
     * Persist recent FC admin activity without writing usermeta on every request.
     */
    public static function setLastActivity(
        int $userId,
        ?int $timestamp = null,
        string $device = '',
        string $userAgent = ''
    ): void {
        $now = $timestamp ?? time();
        $lastWrite = isset($_SESSION['fc_presence_activity_meta_at'])
            ? (int) $_SESSION['fc_presence_activity_meta_at']
            : 0;
        if ($lastWrite > 0 && ($now - $lastWrite) < self::activityMetaThrottleSeconds()) {
            return;
        }

        self::setUserTimestamp($userId, self::LAST_ACTIVITY_META, $now);
        if ($device !== '') {
            self::setUserMetaString($userId, self::LAST_DEVICE_META, $device);
        }
        if ($userAgent !== '') {
            self::setUserMetaString($userId, self::LAST_UA_META, $userAgent);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['fc_presence_activity_meta_at'] = $now;
        }
    }

    /**
     * Persist a string usermeta value.
     */
    public static function setUserMetaString(int $userId, string $metaKey, string $metaValue): void
    {
        $metaKey = trim($metaKey);
        if ($userId <= 0 || $metaKey === '') {
            return;
        }

        UserModel::usermetaSet($userId, $metaKey, $metaValue);
    }

    /**
     * @param list<int> $userIds
     * @return array<int, int> user_id => unix timestamp
     */
    public static function userTimestampMap(array $userIds, string $metaKey): array
    {
        $map = [];
        foreach (UserModel::usermetaMapForUsers($userIds, $metaKey) as $uid => $value) {
            $ts = (int) $value;
            if ($ts > 0) {
                $map[$uid] = $ts;
            }
        }

        return $map;
    }

    /**
     * @param list<int> $userIds
     * @return array<int, int>
     */
    public static function lastLoginMap(array $userIds): array
    {
        return self::userTimestampMap($userIds, self::LAST_LOGIN_META);
    }

    /**
     * @param list<int> $userIds
     * @return array<int, int>
     */
    public static function lastActivityMap(array $userIds): array
    {
        return self::userTimestampMap($userIds, self::LAST_ACTIVITY_META);
    }

    /**
     * @param list<int> $userIds
     * @return array<int, string>
     */
    public static function userStringMetaMap(array $userIds, string $metaKey): array
    {
        $map = [];
        foreach (UserModel::usermetaMapForUsers($userIds, $metaKey) as $uid => $value) {
            $val = trim($value);
            if ($val !== '') {
                $map[$uid] = $val;
            }
        }

        return $map;
    }

    /**
     * Latest known client details per user from presence files (most recent activity wins).
     *
     * @return array<int, array{device:string,user_agent:string,last_activity:int}>
     */
    public static function latestClientMap(): array
    {
        $map = [];

        self::scanPresence(static function (int $userId, int $last, array $data) use (&$map): void {
            if ($last <= 0) {
                return;
            }
            if (isset($map[$userId]) && $last <= (int) ($map[$userId]['last_activity'] ?? 0)) {
                return;
            }
            $map[$userId] = [
                'device' => trim((string) ($data['device'] ?? '')),
                'user_agent' => trim((string) ($data['user_agent'] ?? '')),
                'last_activity' => $last,
            ];
        });

        return $map;
    }

    /**
     * Scan the presence dir once: prune files older than STALE_MAX_AGE, decode each, and
     * hand every valid entry (user_id > 0) to $onEntry as (userId, last, data). The
     * stale-prune @unlink fires exactly once per file.
     *
     * @param callable(int, int, array<string, mixed>): void $onEntry
     */
    private static function scanPresence(callable $onEntry): void
    {
        $dir = self::dir();
        $staleCutoff = time() - self::STALE_MAX_AGE;

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (!is_string($file) || !is_file($file)) {
                continue;
            }

            $mtime = (int) @filemtime($file);
            if ($mtime > 0 && $mtime < $staleCutoff) {
                @unlink($file);
                continue;
            }

            $data = self::readFile($file);
            if ($data === null) {
                continue;
            }

            $userId = (int) ($data['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $last = strtotime((string) ($data['last_activity_at'] ?? '')) ?: $mtime;
            $onEntry($userId, $last, $data);
        }
    }

    /**
     * Resolve device + user agent for users (presence file first, then usermeta).
     *
     * @param list<int> $userIds
     * @return array<int, array{device:string,user_agent:string}>
     */
    public static function clientMapForUsers(array $userIds): array
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

        $fromFiles = self::latestClientMap();
        $deviceMeta = self::userStringMetaMap($ids, self::LAST_DEVICE_META);
        $uaMeta = self::userStringMetaMap($ids, self::LAST_UA_META);

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
    public static function apiPayload(array $userIds = []): array
    {
        $onlineMap = self::onlineMap();
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

        $lastLogin = self::lastLoginMap($ids);
        $persistedActivity = self::lastActivityMap($ids);
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
        foreach (self::clientMapForUsers($ids) as $id => $client) {
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
            'window' => self::onlineWindowSeconds(),
        ];
    }
}
