<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\UserModel;

/**
 * Admin "Login As" another user (config/auth.php migration).
 */
final class ImpersonationService
{
    /**
     * Original admin while using Login As.
     *
     * @return array{id:int,login:string,email:string,display_name:string}|null
     */
    public static function switchFrom(): ?array
    {
        AuthService::boot();
        $from = $_SESSION[AuthService::SWITCH_KEY] ?? null;
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

    public static function isSwitched(): bool
    {
        return self::switchFrom() !== null;
    }

    /**
     * @return array{ok:bool,message:string,redirect?:string}
     */
    public static function switchToUser(int $userId): array
    {
        if (!PermissionService::canAccessAdmin()) {
            return ['ok' => false, 'message' => 'Access denied.'];
        }
        if (!PermissionService::can('users.login_as') && !PermissionService::isSuperAdmin((int) (AuthService::user()['id'] ?? 0))) {
            return ['ok' => false, 'message' => 'You do not have Login As permission.'];
        }

        $current = AuthService::user();
        if ($current === null) {
            return ['ok' => false, 'message' => 'Not signed in.'];
        }

        if ((int) $current['id'] === $userId) {
            return ['ok' => false, 'message' => 'You are already logged in as this user.'];
        }

        $target = UserModel::findById($userId);
        if ($target === null) {
            return ['ok' => false, 'message' => 'User not found.'];
        }

        $targetRoles = UserModel::roles($userId);
        foreach ($targetRoles as $role) {
            if (strtolower(trim((string) $role)) === 'customer') {
                return ['ok' => false, 'message' => 'Cannot Login As a Customer user.'];
            }
        }

        AuthService::boot();
        $wasRemembered = RememberTokenService::isRemembered();
        if (!self::isSwitched()) {
            $_SESSION[AuthService::SWITCH_KEY] = [
                'id'           => (int) $current['id'],
                'login'        => (string) $current['login'],
                'email'        => (string) $current['email'],
                'display_name' => (string) $current['display_name'],
                'remember'     => $wasRemembered,
            ];
        }

        // Do not revoke the original admin's remember token while switched.
        AuthService::loginUser($target, false, false, false);

        $label = $target['display_name'] !== '' ? $target['display_name'] : $target['user_login'];

        return [
            'ok'       => true,
            'message'  => 'Now logged in as ' . $label . '.',
            'redirect' => AuthService::dashboardUrl(),
        ];
    }

    /**
     * @return array{ok:bool,message:string,redirect?:string}
     */
    public static function switchBack(): array
    {
        $from = self::switchFrom();
        if ($from === null) {
            return ['ok' => false, 'message' => 'Not switched to another user.'];
        }

        $original = UserModel::findById((int) $from['id']);
        if ($original === null) {
            AuthService::boot();
            unset($_SESSION[AuthService::SWITCH_KEY]);

            return ['ok' => false, 'message' => 'Original admin account could not be restored.'];
        }

        AuthService::boot();
        $remember = !empty($from['remember']) || RememberTokenService::isRemembered();
        unset($_SESSION[AuthService::SWITCH_KEY]);
        // Restore session flag; keep existing remember cookie if present.
        AuthService::loginUser($original, $remember, false, false);
        if ($remember && !RememberTokenService::hasCookie()) {
            RememberTokenService::issue((int) $original['ID']);
        }

        $label = $original['display_name'] !== '' ? $original['display_name'] : $original['user_login'];

        return [
            'ok'       => true,
            'message'  => 'Switched back to ' . $label . '.',
            'redirect' => rtrim(AuthService::adminBase(), '/') . '/users',
        ];
    }
}
