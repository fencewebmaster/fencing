<?php
/**
 * FC Admin — authentication against WordPress wp_users.
 */

declare(strict_types=1);

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/presence.php';

const FC_AUTH_SESSION_KEY = 'fc_admin_user';
const FC_AUTH_SWITCH_KEY = 'fc_admin_switch_from';
const FC_AUTH_REMEMBER_COOKIE = 'fc_admin_remember';
const FC_AUTH_REMEMBER_SESSION_KEY = 'fc_admin_remember';
const FC_AUTH_REMEMBER_META_PREFIX = 'fc_remember_token_';
/** Max remembered devices stored per user. */
const FC_AUTH_REMEMBER_MAX_TOKENS = 10;
/** Non–Remember me sessions last 24 hours from login. */
const FC_AUTH_SESSION_TTL = 86400;
/** Remember me: long-lived cookie (~10 years) until intentional logout. */
const FC_AUTH_REMEMBER_TTL = 315360000;

function fc_auth_cookie_secure(): bool
{
    $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwarded === 'https') {
        return true;
    }

    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

function fc_auth_session_ttl(bool $remember): int
{
    return $remember ? FC_AUTH_REMEMBER_TTL : FC_AUTH_SESSION_TTL;
}

/**
 * Dedicated writable directory for admin PHP sessions (isolated from public GC).
 */
function fc_auth_session_save_path(): string
{
    if (!function_exists('fc_storage_sessions_dir')) {
        require_once __DIR__ . '/storage.php';
    }

    return fc_storage_sessions_dir();
}

/**
 * Apply / refresh the PHP session cookie + GC lifetime.
 */
function fc_auth_refresh_session_cookie(int $lifetime): void
{
    if (headers_sent()) {
        return;
    }

    $lifetime = max(0, $lifetime);
    $secure = fc_auth_cookie_secure();
    @ini_set('session.gc_maxlifetime', (string) max($lifetime, FC_AUTH_SESSION_TTL));

    // session_set_cookie_params() only works before the session starts.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return;
    }

    // Session already active — extend via Set-Cookie only.
    setcookie(session_name(), session_id(), [
        'expires'  => $lifetime > 0 ? time() + $lifetime : 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * @return array{selector:string,validator:string}|null
 */
function fc_auth_parse_remember_cookie(?string $raw): ?array
{
    $raw = trim((string) $raw);
    if ($raw === '' || !preg_match('/^[a-f0-9]{32}:[a-f0-9]{64}$/', $raw)) {
        return null;
    }

    $parts = explode(':', $raw, 2);

    return [
        'selector' => $parts[0],
        'validator' => $parts[1],
    ];
}

function fc_auth_remember_meta_key(string $selector): string
{
    return FC_AUTH_REMEMBER_META_PREFIX . $selector;
}

function fc_auth_password_fingerprint(string $passwordHash): string
{
    return hash('sha256', $passwordHash);
}

function fc_auth_hash_remember_validator(string $validator): string
{
    return hash('sha256', $validator);
}

/**
 * Write the persistent remember cookie (selector:validator).
 */
function fc_auth_write_remember_cookie(string $token): void
{
    if (headers_sent()) {
        return;
    }

    $secure = fc_auth_cookie_secure();
    setcookie(FC_AUTH_REMEMBER_COOKIE, $token, [
        'expires'  => time() + FC_AUTH_REMEMBER_TTL,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[FC_AUTH_REMEMBER_COOKIE] = $token;
}

/**
 * Clear the persistent remember cookie from the browser.
 */
function fc_auth_clear_remember_cookie(): void
{
    if (headers_sent()) {
        return;
    }

    if (!isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE])) {
        return;
    }

    $secure = fc_auth_cookie_secure();
    setcookie(FC_AUTH_REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]);
}

/**
 * @deprecated Use fc_auth_issue_remember_token / fc_auth_clear_remember_cookie.
 */
function fc_auth_set_remember_cookie(bool $remember): void
{
    if ($remember) {
        return;
    }

    fc_auth_clear_remember_cookie();
}

function fc_auth_is_remembered(): bool
{
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION[FC_AUTH_REMEMBER_SESSION_KEY])) {
        return true;
    }

    return fc_auth_parse_remember_cookie(
        isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : null
    ) !== null;
}

/**
 * Fetch a single usermeta value.
 */
function fc_auth_usermeta_get(int $userId, string $metaKey): ?string
{
    if ($userId <= 0 || $metaKey === '') {
        return null;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return null;
    }

    $metaTable = fc_auth_usermeta_table();
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

/**
 * Upsert a usermeta value.
 */
function fc_auth_usermeta_set(int $userId, string $metaKey, string $metaValue): bool
{
    if ($userId <= 0 || $metaKey === '') {
        return false;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return false;
    }

    $metaTable = fc_auth_usermeta_table();
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

/**
 * Delete a usermeta row.
 */
function fc_auth_usermeta_delete(int $userId, string $metaKey): void
{
    if ($userId <= 0 || $metaKey === '') {
        return;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return;
    }

    $metaTable = fc_auth_usermeta_table();
    $stmt = $conn->prepare("DELETE FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('is', $userId, $metaKey);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}

/**
 * @return list<array{selector:string,meta_key:string,created:int,used:int}>
 */
function fc_auth_list_remember_tokens(int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return [];
    }

    $metaTable = fc_auth_usermeta_table();
    $like = FC_AUTH_REMEMBER_META_PREFIX . '%';
    $stmt = $conn->prepare(
        "SELECT meta_key, meta_value FROM `{$metaTable}` WHERE user_id = ? AND meta_key LIKE ?"
    );
    if (!$stmt) {
        $conn->close();

        return [];
    }

    $stmt->bind_param('is', $userId, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $metaKey = (string) ($row['meta_key'] ?? '');
        $selector = substr($metaKey, strlen(FC_AUTH_REMEMBER_META_PREFIX));
        if ($selector === '' || !preg_match('/^[a-f0-9]{32}$/', $selector)) {
            continue;
        }
        $payload = json_decode((string) ($row['meta_value'] ?? ''), true);
        $rows[] = [
            'selector' => $selector,
            'meta_key' => $metaKey,
            'created' => is_array($payload) ? (int) ($payload['created'] ?? 0) : 0,
            'used' => is_array($payload) ? (int) ($payload['used'] ?? 0) : 0,
        ];
    }
    $stmt->close();
    $conn->close();

    return $rows;
}

/**
 * Keep at most FC_AUTH_REMEMBER_MAX_TOKENS remembered devices per user.
 */
function fc_auth_prune_remember_tokens(int $userId, ?string $keepSelector = null): void
{
    $tokens = fc_auth_list_remember_tokens($userId);
    if (count($tokens) <= FC_AUTH_REMEMBER_MAX_TOKENS) {
        return;
    }

    usort($tokens, static function (array $a, array $b): int {
        $aTouch = max((int) ($a['used'] ?? 0), (int) ($a['created'] ?? 0));
        $bTouch = max((int) ($b['used'] ?? 0), (int) ($b['created'] ?? 0));

        return $aTouch <=> $bTouch;
    });

    $overflow = count($tokens) - FC_AUTH_REMEMBER_MAX_TOKENS;
    for ($i = 0; $i < $overflow; $i++) {
        $selector = (string) ($tokens[$i]['selector'] ?? '');
        if ($keepSelector !== null && $selector === $keepSelector) {
            continue;
        }
        fc_auth_usermeta_delete($userId, (string) ($tokens[$i]['meta_key'] ?? ''));
    }
}

/**
 * Current WordPress password hash for a user (for remember-token binding).
 */
function fc_auth_user_password_hash(int $userId): string
{
    if ($userId <= 0) {
        return '';
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return '';
    }

    $table = fc_auth_users_table();
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
 * Issue a new rotating remember token for the user and set the cookie.
 */
function fc_auth_issue_remember_token(int $userId, ?string $passwordHash = null): bool
{
    if ($userId <= 0) {
        return false;
    }

    $passwordHash = $passwordHash !== null && $passwordHash !== ''
        ? $passwordHash
        : fc_auth_user_password_hash($userId);
    if ($passwordHash === '') {
        return false;
    }

    // Replace any existing cookie token for this browser.
    fc_auth_revoke_remember_token_from_cookie();

    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $now = time();
    $payload = json_encode([
        'hash' => fc_auth_hash_remember_validator($validator),
        'pw' => fc_auth_password_fingerprint($passwordHash),
        'created' => $now,
        'used' => $now,
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false || !fc_auth_usermeta_set($userId, fc_auth_remember_meta_key($selector), $payload)) {
        return false;
    }

    fc_auth_prune_remember_tokens($userId, $selector);
    fc_auth_write_remember_cookie($selector . ':' . $validator);

    return true;
}

/**
 * Revoke the remember token referenced by the current cookie (if any).
 */
function fc_auth_revoke_remember_token_from_cookie(?int $userId = null): void
{
    $parsed = fc_auth_parse_remember_cookie(
        isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : null
    );
    if ($parsed === null) {
        fc_auth_clear_remember_cookie();

        return;
    }

    $metaKey = fc_auth_remember_meta_key($parsed['selector']);
    if ($userId !== null && $userId > 0) {
        fc_auth_usermeta_delete($userId, $metaKey);
    } else {
        // Lookup owner by scanning is expensive; cookie selector is unique enough —
        // delete by meta_key across users via a targeted query.
        $conn = fc_auth_db();
        if ($conn instanceof mysqli) {
            $metaTable = fc_auth_usermeta_table();
            $stmt = $conn->prepare("DELETE FROM `{$metaTable}` WHERE meta_key = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $metaKey);
                $stmt->execute();
                $stmt->close();
            }
            $conn->close();
        }
    }

    fc_auth_clear_remember_cookie();
}

/**
 * Renew cookie expiry for an already-valid remember token (no rotation).
 */
function fc_auth_renew_remember_cookie(): void
{
    $raw = isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : '';
    if (fc_auth_parse_remember_cookie($raw) === null) {
        return;
    }

    fc_auth_write_remember_cookie($raw);
}

/**
 * Resolve remember-token owner user id from selector meta row.
 */
function fc_auth_remember_token_owner(string $selector): int
{
    if ($selector === '' || !preg_match('/^[a-f0-9]{32}$/', $selector)) {
        return 0;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return 0;
    }

    $metaTable = fc_auth_usermeta_table();
    $metaKey = fc_auth_remember_meta_key($selector);
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
 * Restore an authenticated admin session from a valid remember cookie.
 */
function fc_auth_try_restore_from_remember(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $existing = $_SESSION[FC_AUTH_SESSION_KEY] ?? null;
    if (is_array($existing) && !empty($existing['id'])) {
        return true;
    }

    $raw = isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : '';
    $parsed = fc_auth_parse_remember_cookie($raw);
    if ($parsed === null) {
        if ($raw !== '') {
            // Legacy boolean cookie or malformed — drop it.
            fc_auth_clear_remember_cookie();
        }

        return false;
    }

    $userId = fc_auth_remember_token_owner($parsed['selector']);
    if ($userId <= 0) {
        fc_auth_clear_remember_cookie();

        return false;
    }

    $metaKey = fc_auth_remember_meta_key($parsed['selector']);
    $metaRaw = fc_auth_usermeta_get($userId, $metaKey);
    $payload = is_string($metaRaw) ? json_decode($metaRaw, true) : null;
    if (!is_array($payload)) {
        fc_auth_usermeta_delete($userId, $metaKey);
        fc_auth_clear_remember_cookie();

        return false;
    }

    $storedHash = (string) ($payload['hash'] ?? '');
    $expectedHash = fc_auth_hash_remember_validator($parsed['validator']);
    if ($storedHash === '' || !hash_equals($storedHash, $expectedHash)) {
        fc_auth_usermeta_delete($userId, $metaKey);
        fc_auth_clear_remember_cookie();

        return false;
    }

    $passwordHash = fc_auth_user_password_hash($userId);
    $storedPw = (string) ($payload['pw'] ?? '');
    if ($passwordHash === '' || $storedPw === '' || !hash_equals($storedPw, fc_auth_password_fingerprint($passwordHash))) {
        // Password changed — invalidate this and all remember tokens for safety.
        foreach (fc_auth_list_remember_tokens($userId) as $token) {
            fc_auth_usermeta_delete($userId, (string) ($token['meta_key'] ?? ''));
        }
        fc_auth_clear_remember_cookie();

        return false;
    }

    $user = fc_auth_find_user_by_id($userId);
    if ($user === null) {
        fc_auth_usermeta_delete($userId, $metaKey);
        fc_auth_clear_remember_cookie();

        return false;
    }

    if (!fc_auth_user_is_super_admin($userId) && !fc_auth_user_has_any_permission($userId)) {
        fc_auth_usermeta_delete($userId, $metaKey);
        fc_auth_clear_remember_cookie();

        return false;
    }

    // Rotate validator so stolen cookies become single-use after restore.
    $newValidator = bin2hex(random_bytes(32));
    $now = time();
    $rotated = json_encode([
        'hash' => fc_auth_hash_remember_validator($newValidator),
        'pw' => fc_auth_password_fingerprint($passwordHash),
        'created' => (int) ($payload['created'] ?? $now),
        'used' => $now,
    ], JSON_UNESCAPED_SLASHES);
    if ($rotated === false || !fc_auth_usermeta_set($userId, $metaKey, $rotated)) {
        fc_auth_clear_remember_cookie();

        return false;
    }

    session_regenerate_id(true);
    unset($_SESSION['fc_presence_activity_meta_at']);

    $_SESSION[FC_AUTH_SESSION_KEY] = [
        'id'           => (int) $user['ID'],
        'login'        => (string) $user['user_login'],
        'email'        => (string) $user['user_email'],
        'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
        'logged_in_at' => $now,
    ];
    $_SESSION[FC_AUTH_REMEMBER_SESSION_KEY] = true;

    if (function_exists('fc_db_host_mysql_key')) {
        $hostKey = fc_db_host_mysql_key();
        if (function_exists('fc_admin_set_auth_db_key') && function_exists('fc_admin_auth_db_key') && fc_admin_auth_db_key() === '') {
            fc_admin_set_auth_db_key($hostKey);
        }
        if (function_exists('fc_admin_set_site_key') && function_exists('fc_admin_site_key') && fc_admin_site_key() === '') {
            fc_admin_set_site_key($hostKey);
        }
    }

    fc_auth_refresh_session_cookie(FC_AUTH_REMEMBER_TTL);
    fc_auth_write_remember_cookie($parsed['selector'] . ':' . $newValidator);

    if (function_exists('fc_presence_touch')) {
        fc_presence_touch([
            'id' => (int) $user['ID'],
            'login' => (string) $user['user_login'],
            'email' => (string) $user['user_email'],
            'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
            'logged_in_at' => $now,
        ], true);
    }

    return true;
}

/**
 * Expire or keep-alive the logged-in session based on Remember me.
 */
function fc_auth_enforce_session_ttl(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $user = $_SESSION[FC_AUTH_SESSION_KEY] ?? null;
    if (!is_array($user) || empty($user['id'])) {
        return;
    }

    $remember = !empty($_SESSION[FC_AUTH_REMEMBER_SESSION_KEY])
        || fc_auth_parse_remember_cookie(
            isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : null
        ) !== null;
    $_SESSION[FC_AUTH_REMEMBER_SESSION_KEY] = $remember;

    if ($remember) {
        fc_auth_refresh_session_cookie(FC_AUTH_REMEMBER_TTL);
        $raw = isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : '';
        if (fc_auth_parse_remember_cookie($raw) !== null) {
            fc_auth_renew_remember_cookie();
        } else {
            // Upgrade legacy remember=1 sessions to a secure token.
            fc_auth_issue_remember_token((int) $user['id']);
        }

        return;
    }

    $loggedInAt = (int) ($user['logged_in_at'] ?? 0);
    if ($loggedInAt <= 0) {
        $loggedInAt = time();
        $_SESSION[FC_AUTH_SESSION_KEY]['logged_in_at'] = $loggedInAt;
    }

    $expiresAt = $loggedInAt + FC_AUTH_SESSION_TTL;
    if (time() >= $expiresAt) {
        // Absolute 24h expiry — clear without re-entering boot.
        if (function_exists('fc_presence_forget')) {
            fc_presence_forget();
        }
        if (function_exists('fc_admin_clear_site_context')) {
            fc_admin_clear_site_context();
        }
        unset(
            $_SESSION[FC_AUTH_SESSION_KEY],
            $_SESSION[FC_AUTH_SWITCH_KEY],
            $_SESSION[FC_AUTH_REMEMBER_SESSION_KEY],
            $_SESSION['fc_csrf']
        );
        fc_auth_clear_remember_cookie();
        if (!headers_sent() && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'] ?: '/',
                'secure'   => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => 'Lax',
            ]);
        }
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }

        return;
    }

    fc_auth_refresh_session_cookie(max(60, $expiresAt - time()));
}

function fc_auth_boot(): void
{
    static $booting = false;

    if (session_status() === PHP_SESSION_ACTIVE) {
        if (!$booting) {
            $booting = true;
            $user = $_SESSION[FC_AUTH_SESSION_KEY] ?? null;
            if (!is_array($user) || empty($user['id'])) {
                fc_auth_try_restore_from_remember();
            }
            fc_auth_enforce_session_ttl();
            $booting = false;
        }

        return;
    }

    $rememberHint = fc_auth_parse_remember_cookie(
        isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : null
    ) !== null;
    $lifetime = fc_auth_session_ttl($rememberHint);

    if (!headers_sent()) {
        session_name('fc_admin_sess');
        fc_auth_refresh_session_cookie($lifetime);
    } else {
        @ini_set('session.gc_maxlifetime', (string) max($lifetime, FC_AUTH_SESSION_TTL));
    }

    // Always isolate admin sessions from public PHP session GC.
    fc_session_start(fc_auth_session_save_path());

    if (!$booting) {
        $booting = true;
        $user = $_SESSION[FC_AUTH_SESSION_KEY] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            fc_auth_try_restore_from_remember();
        }
        fc_auth_enforce_session_ttl();
        $booting = false;
    }
}

function fc_auth_csrf_token(): string
{
    fc_auth_boot();
    if (empty($_SESSION['fc_csrf']) || !is_string($_SESSION['fc_csrf'])) {
        $_SESSION['fc_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['fc_csrf'];
}

function fc_auth_verify_csrf(?string $token): bool
{
    fc_auth_boot();
    $expected = $_SESSION['fc_csrf'] ?? '';

    return is_string($expected)
        && $expected !== ''
        && is_string($token)
        && hash_equals($expected, $token);
}

function fc_auth_users_table(): string
{
    $cfg = fc_db_resolve_auth_config();
    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';

    return $prefix . 'users';
}

function fc_auth_usermeta_table(): string
{
    $cfg = fc_db_resolve_auth_config();
    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';

    return $prefix . 'usermeta';
}

/**
 * @return mysqli|null
 */
function fc_auth_db()
{
    require_once __DIR__ . '/database.php';
    $db = new Database(fc_db_resolve_auth_config());

    return $db->connect();
}

/**
 * Verify a WordPress password hash (phpass $P$ / $H$, or WP 6.8+ $wp$ bcrypt).
 */
function fc_auth_check_password(string $password, string $hash): bool
{
    $hash = trim($hash);
    if ($password === '' || $hash === '') {
        return false;
    }

    // WordPress 6.8+ bcrypt hashes prefixed with $wp$
    if (str_starts_with($hash, '$wp$')) {
        $passwordToVerify = base64_encode(hash_hmac('sha384', $password, 'wp-sha384', true));
        $check = password_verify($passwordToVerify, substr($hash, 3));

        return $check === true;
    }

    // Native bcrypt / argon (rare but possible)
    if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$argon')) {
        return password_verify($password, $hash);
    }

    // Classic phpass ($P$ / $H$)
    $phpass = dirname(__DIR__, 2) . '/wp-includes/class-phpass.php';
    if (is_readable($phpass)) {
        require_once $phpass;
        if (class_exists('PasswordHash', false)) {
            $hasher = new PasswordHash(8, true);

            return (bool) $hasher->CheckPassword($password, $hash);
        }
    }

    return false;
}

/**
 * @return array{ID:int,user_login:string,user_email:string,user_pass:string,display_name:string}|null
 */
function fc_auth_find_user(string $loginOrEmail): ?array
{
    $loginOrEmail = trim($loginOrEmail);
    if ($loginOrEmail === '') {
        return null;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return null;
    }

    $table = fc_auth_users_table();
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

function fc_auth_user_is_administrator(int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return false;
    }

    $metaTable = fc_auth_usermeta_table();
    // Always use the auth DB prefix — never the switched admin data site prefix.
    $cfg = fc_db_resolve_auth_config();
    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';
    $capKey = $prefix . 'capabilities';

    $sql = "SELECT meta_value FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();

        return false;
    }

    $stmt->bind_param('is', $userId, $capKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    $conn->close();

    if (!is_array($row) || empty($row['meta_value'])) {
        return false;
    }

    $caps = @unserialize((string) $row['meta_value']);
    if (!is_array($caps)) {
        return false;
    }

    return !empty($caps['administrator']);
}

/**
 * Fixed Super Admin email from config.php primary_admin.email.
 */
function fc_auth_primary_admin_email(): string
{
    $app = function_exists('fc_db_load_app_config') ? fc_db_load_app_config() : [];
    $primary = is_array($app['primary_admin'] ?? null) ? $app['primary_admin'] : [];

    return strtolower(trim((string) ($primary['email'] ?? '')));
}

/**
 * Resolve the configured Super Admin WordPress user (by email).
 *
 * @return array{ID:int,user_login:string,user_email:string,display_name:string}|null
 */
function fc_auth_super_admin_user(): ?array
{
    static $resolved = false;
    static $user = null;

    if ($resolved) {
        return $user;
    }
    $resolved = true;

    $email = fc_auth_primary_admin_email();
    if ($email === '') {
        return null;
    }

    $found = fc_auth_find_user($email);
    if ($found === null) {
        return null;
    }

    if (strtolower(trim((string) ($found['user_email'] ?? ''))) !== $email) {
        return null;
    }

    $user = [
        'ID' => (int) $found['ID'],
        'user_login' => (string) $found['user_login'],
        'user_email' => (string) $found['user_email'],
        'display_name' => (string) ($found['display_name'] ?? $found['user_login']),
    ];

    return $user;
}

/**
 * Whether the given (or current session) user is the configured Super Admin.
 */
function fc_auth_user_is_super_admin(?int $userId = null): bool
{
    if ($userId === null) {
        $session = fc_auth_user();
        $userId = is_array($session) ? (int) ($session['id'] ?? 0) : 0;
    }
    if ($userId <= 0) {
        return false;
    }

    $super = fc_auth_super_admin_user();

    return $super !== null && (int) $super['ID'] === $userId;
}

/**
 * Only Super Admin may edit Administrator role permissions.
 */
function fc_auth_can_manage_administrator_permissions(?int $userId = null): bool
{
    return fc_auth_user_is_super_admin($userId);
}

/**
 * @return array{ID:int,user_login:string,user_email:string,display_name:string}|null
 */
function fc_auth_find_user_by_id(int $userId): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return null;
    }

    $table = fc_auth_users_table();
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
 * Original admin while using Login As.
 *
 * @return array{id:int,login:string,email:string,display_name:string}|null
 */
function fc_auth_switch_from(): ?array
{
    fc_auth_boot();
    $from = $_SESSION[FC_AUTH_SWITCH_KEY] ?? null;
    if (!is_array($from) || empty($from['id'])) {
        return null;
    }

    return [
        'id'           => (int) $from['id'],
        'login'        => (string) ($from['login'] ?? ''),
        'email'        => (string) ($from['email'] ?? ''),
        'display_name' => (string) ($from['display_name'] ?? ''),
    ];
}

function fc_auth_is_switched(): bool
{
    return fc_auth_switch_from() !== null;
}

/**
 * Admin access: Super Admin, role with FC grants, or Login As from an authorized admin.
 */
function fc_auth_can_access_admin(): bool
{
    $user = fc_auth_user();
    if ($user === null) {
        return false;
    }

    if (fc_auth_user_is_super_admin((int) $user['id'])) {
        return true;
    }

    if (fc_auth_user_has_any_permission((int) $user['id'])) {
        return true;
    }

    $from = fc_auth_switch_from();
    if ($from !== null) {
        $fromId = (int) $from['id'];
        if (fc_auth_user_is_super_admin($fromId) || fc_auth_user_has_any_permission($fromId)) {
            return true;
        }
    }

    return false;
}

/**
 * WP role slugs for a user from capabilities meta.
 *
 * @return list<string>
 */
function fc_auth_user_roles(int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return [];
    }

    $metaTable = fc_auth_usermeta_table();
    // Always use the auth DB prefix — never the switched admin data site prefix.
    $cfg = fc_db_resolve_auth_config();
    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';
    $capKey = $prefix . 'capabilities';

    $sql = "SELECT meta_value FROM `{$metaTable}` WHERE user_id = ? AND meta_key = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
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

    if (!is_array($row) || empty($row['meta_value'])) {
        return [];
    }

    $caps = @unserialize((string) $row['meta_value']);
    if (!is_array($caps)) {
        return [];
    }

    $roles = [];
    foreach ($caps as $role => $enabled) {
        if (!$enabled) {
            continue;
        }
        $role = strtolower(trim((string) $role));
        if ($role !== '') {
            $roles[] = $role;
        }
    }

    return $roles;
}

/**
 * Whether the user has at least one granted FC permission leaf.
 */
function fc_auth_user_has_any_permission(int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }
    if (fc_auth_user_is_super_admin($userId)) {
        return true;
    }
    if (!function_exists('fc_group_permissions_get')) {
        require_once __DIR__ . '/permissions.php';
    }

    foreach (fc_auth_user_roles($userId) as $role) {
        $matrix = fc_group_permissions_get($role);
        if (fc_permissions_matrix_has_grant($matrix)) {
            return true;
        }
    }

    return false;
}

/**
 * Check a dotted permission leaf for the current session user.
 */
function fc_auth_user_can(string $permKey): bool
{
    $permKey = trim($permKey);
    if ($permKey === '') {
        return true;
    }

    $user = fc_auth_user();
    if ($user === null) {
        return false;
    }

    $userId = (int) $user['id'];
    if (fc_auth_user_is_super_admin($userId)) {
        return true;
    }

    if (!function_exists('fc_group_permissions_get')) {
        require_once __DIR__ . '/permissions.php';
    }

    foreach (fc_auth_user_roles($userId) as $role) {
        $matrix = fc_group_permissions_get($role);
        if (fc_permissions_get_path($matrix, $permKey)) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<string> $keys
 */
function fc_auth_user_can_any(array $keys): bool
{
    if ($keys === []) {
        return true;
    }
    foreach ($keys as $key) {
        if (fc_auth_user_can((string) $key)) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<string>|string $keys
 */
function fc_auth_user_can_all(array|string $keys): bool
{
    $list = is_array($keys) ? $keys : [$keys];
    if ($list === []) {
        return true;
    }
    foreach ($list as $key) {
        if (!fc_auth_user_can((string) $key)) {
            return false;
        }
    }

    return true;
}

/**
 * Abort with 403 when the current user lacks permission.
 *
 * @param list<string>|string $keys
 */
function fc_auth_require_permission(array|string $keys): void
{
    $list = is_array($keys) ? $keys : [$keys];
    if ($list === [] || fc_auth_user_can_any($list)) {
        return;
    }

    fc_admin_abort_403('You do not have permission to access this resource.');
}

/**
 * Render a 403 response (HTML or JSON) and exit.
 */
function fc_admin_abort_403(string $message = 'Forbidden.'): void
{
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $wantsJson = $isAjax || str_contains($accept, 'application/json');

    if ($wantsJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
        }
        echo json_encode([
            'ok' => false,
            'error' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!headers_sent()) {
        http_response_code(403);
    }

    $view = dirname(__DIR__) . '/backend/views/errors/403.php';
    if (is_readable($view)) {
        $fc403Message = $message;
        $fcAdminBase = function_exists('fc_auth_admin_base') ? fc_auth_admin_base() : '';
        $fc403IsSwitched = function_exists('fc_auth_is_switched') && fc_auth_is_switched();
        $fc403SwitchFrom = $fc403IsSwitched && function_exists('fc_auth_switch_from')
            ? fc_auth_switch_from()
            : null;
        $fc403CurrentUser = function_exists('fc_auth_user') ? fc_auth_user() : null;
        $fc403SwitchBackUrl = '';
        if ($fc403IsSwitched && $fcAdminBase !== '') {
            $token = function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '';
            $fc403SwitchBackUrl = rtrim($fcAdminBase, '/') . '/users/switch-back?_token=' . rawurlencode($token);
        }
        include $view;
        exit;
    }

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head><body>';
    echo '<h1>403 Forbidden</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit;
}

/**
 * @return array{ok:bool,message:string,redirect?:string}
 */
function fc_auth_switch_to_user(int $userId): array
{
    if (!fc_auth_can_access_admin()) {
        return ['ok' => false, 'message' => 'Access denied.'];
    }
    if (!fc_auth_user_can('users.login_as') && !fc_auth_user_is_super_admin((int) (fc_auth_user()['id'] ?? 0))) {
        return ['ok' => false, 'message' => 'You do not have Login As permission.'];
    }

    $current = fc_auth_user();
    if ($current === null) {
        return ['ok' => false, 'message' => 'Not signed in.'];
    }

    if ((int) $current['id'] === $userId) {
        return ['ok' => false, 'message' => 'You are already logged in as this user.'];
    }

    $target = fc_auth_find_user_by_id($userId);
    if ($target === null) {
        return ['ok' => false, 'message' => 'User not found.'];
    }

    $targetRoles = fc_auth_user_roles($userId);
    foreach ($targetRoles as $role) {
        if (strtolower(trim((string) $role)) === 'customer') {
            return ['ok' => false, 'message' => 'Cannot Login As a Customer user.'];
        }
    }

    fc_auth_boot();
    $wasRemembered = fc_auth_is_remembered();
    if (!fc_auth_is_switched()) {
        $_SESSION[FC_AUTH_SWITCH_KEY] = [
            'id'           => (int) $current['id'],
            'login'        => (string) $current['login'],
            'email'        => (string) $current['email'],
            'display_name' => (string) $current['display_name'],
            'remember'     => $wasRemembered,
        ];
    }

    // Do not revoke the original admin's remember token while switched.
    fc_auth_login_user($target, false, false, false);

    $label = $target['display_name'] !== '' ? $target['display_name'] : $target['user_login'];

    return [
        'ok'       => true,
        'message'  => 'Now logged in as ' . $label . '.',
        'redirect' => fc_auth_dashboard_url(),
    ];
}

/**
 * @return array{ok:bool,message:string,redirect?:string}
 */
function fc_auth_switch_back(): array
{
    $from = fc_auth_switch_from();
    if ($from === null) {
        return ['ok' => false, 'message' => 'Not switched to another user.'];
    }

    $original = fc_auth_find_user_by_id((int) $from['id']);
    if ($original === null) {
        fc_auth_boot();
        unset($_SESSION[FC_AUTH_SWITCH_KEY]);

        return ['ok' => false, 'message' => 'Original admin account could not be restored.'];
    }

    fc_auth_boot();
    $remember = !empty($from['remember']) || fc_auth_is_remembered();
    unset($_SESSION[FC_AUTH_SWITCH_KEY]);
    // Restore session flag; keep existing remember cookie if present.
    fc_auth_login_user($original, $remember, false, false);
    if ($remember && fc_auth_parse_remember_cookie(
        isset($_COOKIE[FC_AUTH_REMEMBER_COOKIE]) ? (string) $_COOKIE[FC_AUTH_REMEMBER_COOKIE] : null
    ) === null) {
        fc_auth_issue_remember_token((int) $original['ID']);
    }

    $label = $original['display_name'] !== '' ? $original['display_name'] : $original['user_login'];

    return [
        'ok'       => true,
        'message'  => 'Switched back to ' . $label . '.',
        'redirect' => rtrim(fc_auth_admin_base(), '/') . '/users',
    ];
}

/**
 * @return array{id:int,login:string,email:string,display_name:string}|null
 */
function fc_auth_user(): ?array
{
    fc_auth_boot();
    $user = $_SESSION[FC_AUTH_SESSION_KEY] ?? null;
    if (!is_array($user) || empty($user['id'])) {
        return null;
    }

    return [
        'id'           => (int) $user['id'],
        'login'        => (string) ($user['login'] ?? ''),
        'email'        => (string) ($user['email'] ?? ''),
        'display_name' => (string) ($user['display_name'] ?? ''),
    ];
}

function fc_auth_is_logged_in(): bool
{
    return fc_auth_user() !== null;
}

/**
 * @param array{ID:int,user_login:string,user_email:string,display_name:string,user_pass?:string} $user
 * @param bool $manageRememberTokens When false (e.g. Login As), leave the persistent cookie alone.
 */
function fc_auth_login_user(
    array $user,
    bool $remember = false,
    bool $recordLastLogin = true,
    bool $manageRememberTokens = true
): void {
    fc_auth_boot();
    if (function_exists('fc_presence_forget')) {
        fc_presence_forget();
    }
    session_regenerate_id(true);
    unset($_SESSION['fc_presence_activity_meta_at']);

    $now = time();
    $_SESSION[FC_AUTH_SESSION_KEY] = [
        'id'           => (int) $user['ID'],
        'login'        => (string) $user['user_login'],
        'email'        => (string) $user['user_email'],
        'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
        'logged_in_at' => $now,
    ];
    $_SESSION[FC_AUTH_REMEMBER_SESSION_KEY] = $remember;

    // Pin auth DB to the login-time database; default admin data site to the same key.
    if (function_exists('fc_db_host_mysql_key')) {
        $hostKey = fc_db_host_mysql_key();
        if (function_exists('fc_admin_set_auth_db_key')) {
            fc_admin_set_auth_db_key($hostKey);
        }
        if (function_exists('fc_admin_set_site_key') && function_exists('fc_admin_site_key') && fc_admin_site_key() === '') {
            fc_admin_set_site_key($hostKey);
        }
    }

    fc_auth_refresh_session_cookie(fc_auth_session_ttl($remember));

    $userId = (int) $user['ID'];
    if ($manageRememberTokens) {
        $passwordHash = isset($user['user_pass']) ? (string) $user['user_pass'] : '';
        if ($remember) {
            fc_auth_issue_remember_token($userId, $passwordHash !== '' ? $passwordHash : null);
        } else {
            fc_auth_revoke_remember_token_from_cookie($userId);
        }
    }

    $sessionUser = [
        'id' => $userId,
        'login' => (string) $user['user_login'],
        'email' => (string) $user['user_email'],
        'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
        'logged_in_at' => $now,
    ];

    if ($recordLastLogin && function_exists('fc_presence_set_last_login')) {
        fc_presence_set_last_login($userId, $now);
    }
    if (function_exists('fc_presence_touch')) {
        fc_presence_touch($sessionUser, true);
    }
}

function fc_auth_logout(): void
{
    fc_auth_boot();

    $userId = 0;
    if (is_array($_SESSION[FC_AUTH_SESSION_KEY] ?? null)) {
        $userId = (int) ($_SESSION[FC_AUTH_SESSION_KEY]['id'] ?? 0);
    }

    if (function_exists('fc_presence_forget')) {
        fc_presence_forget();
    }
    if (function_exists('fc_admin_clear_site_context')) {
        fc_admin_clear_site_context();
    }

    // Revoke persistent token before clearing the session cookie.
    fc_auth_revoke_remember_token_from_cookie($userId > 0 ? $userId : null);

    unset(
        $_SESSION[FC_AUTH_SESSION_KEY],
        $_SESSION[FC_AUTH_SWITCH_KEY],
        $_SESSION[FC_AUTH_REMEMBER_SESSION_KEY],
        $_SESSION['fc_csrf']
    );

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'] ?: '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => 'Lax',
        ]);
    }

    $_SESSION = [];
    session_destroy();
}

/**
 * @return array{ok:bool,message:string,user?:array<string,mixed>,redirect?:string,errors?:array<string,string>}
 */
function fc_auth_attempt_login(string $username, string $password, bool $remember = false): array
{
    $username = trim($username);
    $errors = [];

    if ($username === '') {
        $errors['username'] = 'Username is required.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if ($errors !== []) {
        return [
            'ok'      => false,
            'message' => 'Please enter your username and password.',
            'errors'  => $errors,
        ];
    }

    $user = fc_auth_find_user($username);
    if ($user === null || !fc_auth_check_password($password, $user['user_pass'])) {
        return [
            'ok'      => false,
            'message' => 'Invalid username or password.',
            'errors'  => ['password' => 'Invalid username or password.'],
        ];
    }

    if (!fc_auth_user_is_super_admin($user['ID']) && !fc_auth_user_has_any_permission($user['ID'])) {
        return [
            'ok'      => false,
            'message' => 'Access denied. You do not have permission to access the admin.',
            'errors'  => ['username' => 'Insufficient permissions.'],
        ];
    }

    fc_auth_login_user($user, $remember);

    $display = $user['display_name'] !== '' ? $user['display_name'] : $user['user_login'];

    return [
        'ok'       => true,
        'message'  => 'Login successful! Welcome back, ' . $display . '.',
        'redirect' => 'dashboard',
        'user'     => [
            'id'           => $user['ID'],
            'login'        => $user['user_login'],
            'email'        => $user['user_email'],
            'display_name' => $display,
        ],
    ];
}

function fc_auth_admin_base(): string
{
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/fc/backend/index.php');
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    return $base === '' ? '/' : $base;
}

function fc_auth_login_url(): string
{
    return fc_auth_admin_base() . '/login';
}

function fc_auth_dashboard_url(): string
{
    return fc_auth_admin_base() . '/dashboard';
}

function fc_auth_require_login(): void
{
    if (fc_auth_can_access_admin()) {
        return;
    }

    // Logged in but no admin access (e.g. stale non-admin session).
    if (fc_auth_is_logged_in()) {
        fc_auth_logout();
    }

    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $wantsJson = $isAjax || str_contains($accept, 'application/json');

    if ($wantsJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
        }
        echo json_encode([
            'ok'       => false,
            'error'    => 'Unauthorized. Please sign in.',
            'redirect' => fc_auth_login_url(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $returnTo = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $login = fc_auth_login_url();
    if ($returnTo !== '' && !str_contains($returnTo, '/login')) {
        $login .= '?redirect=' . rawurlencode($returnTo);
    }

    header('Location: ' . $login);
    exit;
}

/**
 * View model for the login page.
 *
 * @return array<string, mixed>
 */
function fc_auth_login_view_data(string $adminBase, string $appBase): array
{
    $branding = function_exists('fc_branding_get') ? fc_branding_get() : [
        'appName' => 'Fencing Calculator',
        'tagline' => '',
        'version' => '',
    ];

    $appName = (string) ($branding['appName'] ?? 'Fencing Calculator');
    $tagline = (string) ($branding['tagline'] ?? '');
    if ($tagline === '') {
        $tagline = 'Plan fences, manage products, and keep your catalogue in sync.';
    }

    $redirect = trim((string) ($_GET['redirect'] ?? ''));
    if ($redirect !== '' && (str_contains($redirect, '://') || str_starts_with($redirect, '//'))) {
        $redirect = '';
    }

    return [
        'admin_base'   => $adminBase,
        'app_base'     => $appBase,
        'app_name'     => $appName,
        'tagline'      => $tagline,
        'version'      => (string) ($branding['version'] ?? ''),
        'logo_url'     => function_exists('fc_branding_logo_url') ? fc_branding_logo_url($appBase, $branding) : '',
        'csrf'         => fc_auth_csrf_token(),
        'redirect'     => $redirect,
        'login_api'    => rtrim($adminBase, '/') . '/api.php?module=auth&action=login',
        'features'     => [
            ['icon' => 'fa-ruler-combined', 'label' => 'Fence planner & quote tools'],
            ['icon' => 'fa-boxes-stacked', 'label' => 'Store & system product catalogues'],
            ['icon' => 'fa-shield-halved', 'label' => 'Secure account sign-in'],
        ],
    ];
}
