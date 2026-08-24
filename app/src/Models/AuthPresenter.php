<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\BrandingSettings;

/**
 * Login/logout page formatting (config/auth.php's login-specific migration).
 */
final class AuthPresenter
{
    /**
     * View model for the login page.
     *
     * @return array<string, mixed>
     */
    public static function loginViewData(string $adminBase, string $appBase, string $redirect): array
    {
        $branding = BrandingSettings::get();

        $appName = (string) ($branding['appName'] ?? 'Fencing Calculator');
        $tagline = (string) ($branding['tagline'] ?? '');
        if ($tagline === '') {
            $tagline = 'Plan fences, manage products, and keep your catalogue in sync.';
        }

        $redirect = trim($redirect);
        if ($redirect !== '' && (str_contains($redirect, '://') || str_starts_with($redirect, '//'))) {
            $redirect = '';
        }

        return [
            'admin_base' => $adminBase,
            'app_base' => $appBase,
            'app_name' => $appName,
            'tagline' => $tagline,
            'version' => (string) ($branding['version'] ?? ''),
            'logo_url' => BrandingSettings::logoUrl($appBase, $branding),
            'csrf' => AuthService::csrfToken(),
            'redirect' => $redirect,
            'login_api' => rtrim($adminBase, '/') . '/api.php?module=auth&action=login',
            'features' => [
                ['icon' => 'fa-ruler-combined', 'label' => 'Fence planner & quote tools'],
                ['icon' => 'fa-boxes-stacked', 'label' => 'Store & system product catalogues'],
                ['icon' => 'fa-shield-halved', 'label' => 'Secure account sign-in'],
            ],
        ];
    }

    /**
     * Logout URL with an embedded CSRF token (same pattern as UserPresenter::switchBackUrl()).
     */
    public static function logoutUrl(string $adminBase): string
    {
        $token = AuthService::mintOneTimeToken('logout');

        return rtrim($adminBase, '/') . '/logout?_token=' . rawurlencode($token);
    }
}
