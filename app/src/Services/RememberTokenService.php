<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Models\UserModel;

/**
 * Persistent "remember me" login cookie lifecycle.
 */
final class RememberTokenService
{
    public const REMEMBER_COOKIE = 'fc_admin_remember';
    private const META_PREFIX = 'fc_remember_token_';
    private const MAX_TOKENS = 10;
    /** Remember me: long-lived cookie (~10 years) until intentional logout. */
    public const TTL = 315360000;

    /**
     * Issue a new rotating remember token for the user and set the cookie.
     */
    public static function issue(int $userId, ?string $passwordHash = null): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $passwordHash = $passwordHash !== null && $passwordHash !== ''
            ? $passwordHash
            : UserModel::passwordHash($userId);
        if ($passwordHash === '') {
            return false;
        }

        // Replace any existing cookie token for this browser.
        self::revokeFromCookie();

        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $now = time();
        $payload = json_encode([
            'hash' => self::hashValidator($validator),
            'pw' => self::passwordFingerprint($passwordHash),
            'created' => $now,
            'used' => $now,
        ], JSON_UNESCAPED_SLASHES);

        if ($payload === false || !UserModel::usermetaSet($userId, self::metaKey($selector), $payload)) {
            return false;
        }

        self::pruneTokens($userId, $selector);
        self::writeCookie($selector . ':' . $validator);

        return true;
    }

    /**
     * Revoke the remember token referenced by the current cookie (if any).
     */
    public static function revokeFromCookie(?int $userId = null): void
    {
        $parsed = self::parseCookie(
            isset($_COOKIE[self::REMEMBER_COOKIE]) ? (string) $_COOKIE[self::REMEMBER_COOKIE] : null
        );
        if ($parsed === null) {
            self::clearCookie();

            return;
        }

        $metaKey = self::metaKey($parsed['selector']);
        if ($userId !== null && $userId > 0) {
            UserModel::usermetaDelete($userId, $metaKey);
        } else {
            // Lookup owner by scanning is expensive; cookie selector is unique enough —
            // delete by meta_key across users via a targeted query.
            UserModel::usermetaDeleteByKey($metaKey);
        }

        self::clearCookie();
    }

    /**
     * Restore an authenticated admin session from a valid remember cookie.
     */
    public static function tryRestore(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $existing = $_SESSION[AuthService::SESSION_KEY] ?? null;
        if (is_array($existing) && !empty($existing['id'])) {
            return true;
        }

        $raw = isset($_COOKIE[self::REMEMBER_COOKIE]) ? (string) $_COOKIE[self::REMEMBER_COOKIE] : '';
        $parsed = self::parseCookie($raw);
        if ($parsed === null) {
            if ($raw !== '') {
                // Legacy boolean cookie or malformed — drop it.
                self::clearCookie();
            }

            return false;
        }

        $userId = self::tokenOwner($parsed['selector']);
        if ($userId <= 0) {
            self::clearCookie();

            return false;
        }

        $metaKey = self::metaKey($parsed['selector']);
        $metaRaw = UserModel::usermetaGet($userId, $metaKey);
        $payload = is_string($metaRaw) ? json_decode($metaRaw, true) : null;
        if (!is_array($payload)) {
            UserModel::usermetaDelete($userId, $metaKey);
            self::clearCookie();

            return false;
        }

        $storedHash = (string) ($payload['hash'] ?? '');
        $expectedHash = self::hashValidator($parsed['validator']);
        if ($storedHash === '' || !hash_equals($storedHash, $expectedHash)) {
            UserModel::usermetaDelete($userId, $metaKey);
            self::clearCookie();

            return false;
        }

        $passwordHash = UserModel::passwordHash($userId);
        $storedPw = (string) ($payload['pw'] ?? '');
        if ($passwordHash === '' || $storedPw === '' || !hash_equals($storedPw, self::passwordFingerprint($passwordHash))) {
            // Password changed — invalidate this and all remember tokens for safety.
            foreach (self::listTokens($userId) as $token) {
                UserModel::usermetaDelete($userId, (string) ($token['meta_key'] ?? ''));
            }
            self::clearCookie();

            return false;
        }

        $user = UserModel::findById($userId);
        if ($user === null) {
            UserModel::usermetaDelete($userId, $metaKey);
            self::clearCookie();

            return false;
        }

        if (!PermissionService::isSuperAdmin($userId) && !PermissionService::hasAnyPermission($userId)) {
            UserModel::usermetaDelete($userId, $metaKey);
            self::clearCookie();

            return false;
        }

        // Rotate validator so stolen cookies become single-use after restore.
        $newValidator = bin2hex(random_bytes(32));
        $now = time();
        $rotated = json_encode([
            'hash' => self::hashValidator($newValidator),
            'pw' => self::passwordFingerprint($passwordHash),
            'created' => (int) ($payload['created'] ?? $now),
            'used' => $now,
        ], JSON_UNESCAPED_SLASHES);
        if ($rotated === false || !UserModel::usermetaSet($userId, $metaKey, $rotated)) {
            self::clearCookie();

            return false;
        }

        session_regenerate_id(true);
        unset($_SESSION['fc_presence_activity_meta_at']);

        $_SESSION[AuthService::SESSION_KEY] = [
            'id'           => (int) $user['ID'],
            'login'        => (string) $user['user_login'],
            'email'        => (string) $user['user_email'],
            'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
            'logged_in_at' => $now,
        ];
        $_SESSION[AuthService::REMEMBER_SESSION_KEY] = true;

        $hostKey = DatabaseConfigService::hostMysqlKey();
        if (AdminSiteRegistry::authDbKey() === '') {
            AdminSiteRegistry::setAuthDbKey($hostKey);
        }
        if (AdminSiteRegistry::siteKey() === '') {
            AdminSiteRegistry::setSiteKey($hostKey);
        }

        AuthService::refreshSessionCookie(self::TTL);
        self::writeCookie($parsed['selector'] . ':' . $newValidator);

        PresenceService::touch([
            'id' => (int) $user['ID'],
            'login' => (string) $user['user_login'],
            'email' => (string) $user['user_email'],
            'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
            'logged_in_at' => $now,
        ], true);

        return true;
    }

    public static function isRemembered(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION[AuthService::REMEMBER_SESSION_KEY])) {
            return true;
        }

        return self::hasCookie();
    }

    /**
     * Whether a validly-formatted remember cookie is present (regardless of DB validity).
     */
    public static function hasCookie(): bool
    {
        return self::parseCookie(
            isset($_COOKIE[self::REMEMBER_COOKIE]) ? (string) $_COOKIE[self::REMEMBER_COOKIE] : null
        ) !== null;
    }

    /**
     * @return list<array{selector:string,meta_key:string,created:int,used:int}>
     */
    public static function listTokens(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $like = self::META_PREFIX . '%';
        $rows = [];
        foreach (UserModel::usermetaLike($userId, $like) as $row) {
            $metaKey = $row['meta_key'];
            $selector = substr($metaKey, strlen(self::META_PREFIX));
            if ($selector === '' || !preg_match('/^[a-f0-9]{32}$/', $selector)) {
                continue;
            }
            $payload = json_decode($row['meta_value'], true);
            $rows[] = [
                'selector' => $selector,
                'meta_key' => $metaKey,
                'created' => is_array($payload) ? (int) ($payload['created'] ?? 0) : 0,
                'used' => is_array($payload) ? (int) ($payload['used'] ?? 0) : 0,
            ];
        }

        return $rows;
    }

    /**
     * Keep at most self::MAX_TOKENS remembered devices per user.
     */
    public static function pruneTokens(int $userId, ?string $keepSelector = null): void
    {
        $tokens = self::listTokens($userId);
        if (count($tokens) <= self::MAX_TOKENS) {
            return;
        }

        usort($tokens, static function (array $a, array $b): int {
            $aTouch = max((int) ($a['used'] ?? 0), (int) ($a['created'] ?? 0));
            $bTouch = max((int) ($b['used'] ?? 0), (int) ($b['created'] ?? 0));

            return $aTouch <=> $bTouch;
        });

        $overflow = count($tokens) - self::MAX_TOKENS;
        for ($i = 0; $i < $overflow; $i++) {
            $selector = (string) ($tokens[$i]['selector'] ?? '');
            if ($keepSelector !== null && $selector === $keepSelector) {
                continue;
            }
            UserModel::usermetaDelete($userId, (string) ($tokens[$i]['meta_key'] ?? ''));
        }
    }

    /**
     * Renew cookie expiry for an already-valid remember token (no rotation).
     */
    public static function renewCookie(): void
    {
        $raw = isset($_COOKIE[self::REMEMBER_COOKIE]) ? (string) $_COOKIE[self::REMEMBER_COOKIE] : '';
        if (self::parseCookie($raw) === null) {
            return;
        }

        self::writeCookie($raw);
    }

    /**
     * Clear the persistent remember cookie from the browser.
     */
    public static function clearCookie(): void
    {
        if (headers_sent()) {
            return;
        }

        if (!isset($_COOKIE[self::REMEMBER_COOKIE])) {
            return;
        }

        $secure = RequestHelper::isHttps();
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }

    /**
     * Resolve remember-token owner user id from selector meta row.
     */
    public static function tokenOwner(string $selector): int
    {
        if ($selector === '' || !preg_match('/^[a-f0-9]{32}$/', $selector)) {
            return 0;
        }

        return UserModel::usermetaOwnerByKey(self::metaKey($selector));
    }

    private static function metaKey(string $selector): string
    {
        return self::META_PREFIX . $selector;
    }

    private static function passwordFingerprint(string $passwordHash): string
    {
        return hash('sha256', $passwordHash);
    }

    private static function hashValidator(string $validator): string
    {
        return hash('sha256', $validator);
    }

    /**
     * @return array{selector:string,validator:string}|null
     */
    private static function parseCookie(?string $raw): ?array
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

    /**
     * Write the persistent remember cookie (selector:validator).
     */
    private static function writeCookie(string $token): void
    {
        if (headers_sent()) {
            return;
        }

        $secure = RequestHelper::isHttps();
        setcookie(self::REMEMBER_COOKIE, $token, [
            'expires'  => time() + self::TTL,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::REMEMBER_COOKIE] = $token;
    }
}
