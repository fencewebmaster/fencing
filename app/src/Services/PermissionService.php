<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\GroupPermissionsModel;
use Fc\Admin\Models\UserModel;

/**
 * Role/permission-matrix checks (config/auth.php migration).
 */
final class PermissionService
{
    /**
     * Whether the given (or current session) user is the configured Super Admin.
     */
    public static function isSuperAdmin(?int $userId = null): bool
    {
        if ($userId === null) {
            $session = AuthService::user();
            $userId = is_array($session) ? (int) ($session['id'] ?? 0) : 0;
        }
        if ($userId <= 0) {
            return false;
        }

        $super = self::superAdminUser();

        return $super !== null && (int) $super['ID'] === $userId;
    }

    /**
     * Only Super Admin may edit Administrator role permissions.
     */
    public static function canManageAdministratorPermissions(?int $userId = null): bool
    {
        return self::isSuperAdmin($userId);
    }

    /**
     * Whether the user has at least one granted FC permission leaf.
     */
    public static function hasAnyPermission(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if (self::isSuperAdmin($userId)) {
            return true;
        }

        foreach (UserModel::roles($userId) as $role) {
            $matrix = GroupPermissionsModel::get($role);
            if (GroupPermissionsModel::matrixHasGrant($matrix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check a dotted permission leaf for the current session user.
     */
    public static function can(string $permKey): bool
    {
        $permKey = trim($permKey);
        if ($permKey === '') {
            return true;
        }

        $user = AuthService::user();
        if ($user === null) {
            return false;
        }

        $userId = (int) $user['id'];
        if (self::isSuperAdmin($userId)) {
            return true;
        }

        foreach (UserModel::roles($userId) as $role) {
            $matrix = GroupPermissionsModel::get($role);
            if (GroupPermissionsModel::getPath($matrix, $permKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $keys
     */
    public static function canAny(array $keys): bool
    {
        if ($keys === []) {
            return true;
        }
        foreach ($keys as $key) {
            if (self::can((string) $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin access: Super Admin, role with FC grants, or Login As from an authorized admin.
     */
    public static function canAccessAdmin(): bool
    {
        $user = AuthService::user();
        if ($user === null) {
            return false;
        }

        if (self::isSuperAdmin((int) $user['id'])) {
            return true;
        }

        if (self::hasAnyPermission((int) $user['id'])) {
            return true;
        }

        $from = ImpersonationService::switchFrom();
        if ($from !== null) {
            $fromId = (int) $from['id'];
            if (self::isSuperAdmin($fromId) || self::hasAnyPermission($fromId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fixed Super Admin email from config.php primary_admin.email.
     */
    public static function primaryAdminEmail(): string
    {
        $app = DatabaseConfigService::loadAppConfig();
        $primary = is_array($app['primary_admin'] ?? null) ? $app['primary_admin'] : [];

        return strtolower(trim((string) ($primary['email'] ?? '')));
    }

    /**
     * Resolve the configured Super Admin WordPress user (by email).
     *
     * @return array{ID:int,user_login:string,user_email:string,display_name:string}|null
     */
    public static function superAdminUser(): ?array
    {
        static $resolved = false;
        static $user = null;

        if ($resolved) {
            return $user;
        }
        $resolved = true;

        $email = self::primaryAdminEmail();
        if ($email === '') {
            return null;
        }

        $found = UserModel::findByLogin($email);
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
}
