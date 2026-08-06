<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Core\SessionBootstrap;
use Fc\Admin\Helpers\PasswordHelper;
use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Models\UserModel;

/**
 * Session bootstrap, CSRF, current-user accessors, and login/logout mutations
 * (config/auth.php migration).
 */
final class AuthService
{
    public const SESSION_KEY = 'fc_admin_user';
    public const SWITCH_KEY = 'fc_admin_switch_from';
    public const REMEMBER_SESSION_KEY = 'fc_admin_remember';
    /** Non–Remember me sessions last 24 hours from login. */
    public const SESSION_TTL = 86400;

    /**
     * Idempotent session bootstrap: starts the session, attempts remember-cookie
     * restore, and enforces the session TTL.
     */
    public static function boot(): void
    {
        static $booting = false;

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!$booting) {
                $booting = true;
                $user = $_SESSION[self::SESSION_KEY] ?? null;
                if (!is_array($user) || empty($user['id'])) {
                    RememberTokenService::tryRestore();
                }
                self::enforceSessionTtl();
                $booting = false;
            }

            return;
        }

        $rememberHint = RememberTokenService::hasCookie();
        $lifetime = self::sessionTtl($rememberHint);

        if (!headers_sent()) {
            session_name('fc_admin_sess');
            self::refreshSessionCookie($lifetime);
        } else {
            @ini_set('session.gc_maxlifetime', (string) max($lifetime, self::SESSION_TTL));
        }

        // Always isolate admin sessions from public PHP session GC.
        SessionBootstrap::start(self::sessionSavePath());

        if (!$booting) {
            $booting = true;
            $user = $_SESSION[self::SESSION_KEY] ?? null;
            if (!is_array($user) || empty($user['id'])) {
                RememberTokenService::tryRestore();
            }
            self::enforceSessionTtl();
            $booting = false;
        }
    }

    /**
     * @return array{id:int,login:string,email:string,display_name:string}|null
     */
    public static function user(): ?array
    {
        self::boot();
        $user = $_SESSION[self::SESSION_KEY] ?? null;
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

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function csrfToken(): string
    {
        self::boot();
        if (empty($_SESSION['fc_csrf']) || !is_string($_SESSION['fc_csrf'])) {
            $_SESSION['fc_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['fc_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::boot();
        $expected = $_SESSION['fc_csrf'] ?? '';

        return is_string($expected)
            && $expected !== ''
            && is_string($token)
            && hash_equals($expected, $token);
    }

    public static function adminBase(): string
    {
        $base = UrlHelper::resolveAdminMountBase();

        return $base === '' ? '/' : $base;
    }

    public static function loginUrl(): string
    {
        return self::adminBase() . '/login';
    }

    public static function dashboardUrl(): string
    {
        return self::adminBase() . '/dashboard';
    }

    /**
     * Apply / refresh the PHP session cookie + GC lifetime.
     */
    public static function refreshSessionCookie(int $lifetime): void
    {
        if (headers_sent()) {
            return;
        }

        $lifetime = max(0, $lifetime);
        $secure = RequestHelper::isHttps();
        @ini_set('session.gc_maxlifetime', (string) max($lifetime, self::SESSION_TTL));

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

    private static function sessionTtl(bool $remember): int
    {
        return $remember ? RememberTokenService::TTL : self::SESSION_TTL;
    }

    /**
     * Dedicated writable directory for admin PHP sessions (isolated from public GC).
     */
    private static function sessionSavePath(): string
    {
        return CacheStorageService::sessionsDir();
    }

    /**
     * Expire or keep-alive the logged-in session based on Remember me.
     */
    private static function enforceSessionTtl(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $user = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            return;
        }

        $remember = RememberTokenService::isRemembered();
        $_SESSION[self::REMEMBER_SESSION_KEY] = $remember;

        if ($remember) {
            self::refreshSessionCookie(RememberTokenService::TTL);
            if (RememberTokenService::hasCookie()) {
                RememberTokenService::renewCookie();
            } else {
                // Upgrade legacy remember=1 sessions to a secure token.
                RememberTokenService::issue((int) $user['id']);
            }

            return;
        }

        $loggedInAt = (int) ($user['logged_in_at'] ?? 0);
        if ($loggedInAt <= 0) {
            $loggedInAt = time();
            $_SESSION[self::SESSION_KEY]['logged_in_at'] = $loggedInAt;
        }

        $expiresAt = $loggedInAt + self::SESSION_TTL;
        if (time() >= $expiresAt) {
            // Absolute 24h expiry — clear without re-entering boot.
            PresenceService::forget();
            AdminSiteRegistry::clearSiteContext();
            unset(
                $_SESSION[self::SESSION_KEY],
                $_SESSION[self::SWITCH_KEY],
                $_SESSION[self::REMEMBER_SESSION_KEY],
                $_SESSION['fc_csrf']
            );
            RememberTokenService::clearCookie();
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

        self::refreshSessionCookie(max(60, $expiresAt - time()));
    }

    /**
     * @return array{ok:bool,message:string,user?:array<string,mixed>,redirect?:string,errors?:array<string,string>}
     */
    public static function attemptLogin(string $username, string $password, bool $remember = false): array
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
                'ok' => false,
                'message' => 'Please enter your username and password.',
                'errors' => $errors,
            ];
        }

        $user = UserModel::findByLogin($username);
        if ($user === null || !PasswordHelper::verify($password, $user['user_pass'])) {
            return [
                'ok' => false,
                'message' => 'Invalid username or password.',
                'errors' => ['password' => 'Invalid username or password.'],
            ];
        }

        if (!PermissionService::isSuperAdmin($user['ID']) && !PermissionService::hasAnyPermission($user['ID'])) {
            return [
                'ok' => false,
                'message' => 'Access denied. You do not have permission to access the admin.',
                'errors' => ['username' => 'Insufficient permissions.'],
            ];
        }

        self::loginUser($user, $remember);

        $display = $user['display_name'] !== '' ? $user['display_name'] : $user['user_login'];

        return [
            'ok' => true,
            'message' => 'Login successful! Welcome back, ' . $display . '.',
            'redirect' => 'dashboard',
            'user' => [
                'id' => $user['ID'],
                'login' => $user['user_login'],
                'email' => $user['user_email'],
                'display_name' => $display,
            ],
        ];
    }

    /**
     * @param array{ID:int,user_login:string,user_email:string,display_name:string,user_pass?:string} $user
     * @param bool $manageRememberTokens When false (e.g. Login As), leave the persistent cookie alone.
     */
    public static function loginUser(
        array $user,
        bool $remember = false,
        bool $recordLastLogin = true,
        bool $manageRememberTokens = true
    ): void {
        self::boot();
        PresenceService::forget();
        session_regenerate_id(true);
        unset($_SESSION['fc_presence_activity_meta_at']);

        $now = time();
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['ID'],
            'login' => (string) $user['user_login'],
            'email' => (string) $user['user_email'],
            'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
            'logged_in_at' => $now,
        ];
        $_SESSION[self::REMEMBER_SESSION_KEY] = $remember;

        // Pin auth DB to the login-time database; default admin data site to the same key.
        $hostKey = DatabaseConfigService::hostMysqlKey();
        AdminSiteRegistry::setAuthDbKey($hostKey);
        if (AdminSiteRegistry::siteKey() === '') {
            AdminSiteRegistry::setSiteKey($hostKey);
        }

        self::refreshSessionCookie(self::sessionTtl($remember));

        $userId = (int) $user['ID'];
        if ($manageRememberTokens) {
            $passwordHash = isset($user['user_pass']) ? (string) $user['user_pass'] : '';
            if ($remember) {
                RememberTokenService::issue($userId, $passwordHash !== '' ? $passwordHash : null);
            } else {
                RememberTokenService::revokeFromCookie($userId);
            }
        }

        $sessionUser = [
            'id' => $userId,
            'login' => (string) $user['user_login'],
            'email' => (string) $user['user_email'],
            'display_name' => (string) ($user['display_name'] ?: $user['user_login']),
            'logged_in_at' => $now,
        ];

        if ($recordLastLogin) {
            PresenceService::setLastLogin($userId, $now);
        }
        PresenceService::touch($sessionUser, true);
    }

    public static function logout(): void
    {
        self::boot();

        $userId = 0;
        if (is_array($_SESSION[self::SESSION_KEY] ?? null)) {
            $userId = (int) ($_SESSION[self::SESSION_KEY]['id'] ?? 0);
        }

        PresenceService::forget();
        AdminSiteRegistry::clearSiteContext();

        // Revoke persistent token before clearing the session cookie.
        RememberTokenService::revokeFromCookie($userId > 0 ? $userId : null);

        unset(
            $_SESSION[self::SESSION_KEY],
            $_SESSION[self::SWITCH_KEY],
            $_SESSION[self::REMEMBER_SESSION_KEY],
            $_SESSION['fc_csrf']
        );

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => 'Lax',
            ]);
        }

        $_SESSION = [];
        session_destroy();
    }
}
