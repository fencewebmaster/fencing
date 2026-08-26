<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\PermissionService;

/**
 * Group permissions — role business rules + page/API orchestration.
 */
final class GroupPermissionsPresenter
{
    /**
     * Whether a WP role slug has FC admin access.
     */
    public static function roleHasAccess(string $role): bool
    {
        $slug = GroupPermissionsModel::sanitizeRole($role);
        if ($slug === '' || $slug === 'customer') {
            return false;
        }
        if ($slug === 'super_admin') {
            return true;
        }

        return GroupPermissionsModel::matrixHasGrant(GroupPermissionsModel::get($slug));
    }

    /**
     * Roles that can be configured on the Group Permissions page.
     */
    public static function isManageableRole(string $role): bool
    {
        $slug = GroupPermissionsModel::sanitizeRole($role);

        return $slug !== '' && $slug !== 'customer' && $slug !== 'super_admin';
    }

    /**
     * Whether the current user may edit permissions for a role.
     */
    public static function canEditRole(string $role): bool
    {
        $slug = GroupPermissionsModel::sanitizeRole($role);
        if ($slug === '' || $slug === 'customer' || $slug === 'super_admin') {
            return false;
        }

        return self::isManageableRole($slug);
    }

    /**
     * Visible dashboard widgets for the current user.
     *
     * @return array<string, bool>
     */
    public static function dashboardWidgetsVisible(): array
    {
        $visible = [];
        foreach (GroupPermissionsModel::dashboardWidgetMap() as $widgetId => $permKey) {
            $visible[$widgetId] = PermissionService::can($permKey);
        }

        return $visible;
    }

    /**
     * Roles for the left panel: Super Admin + known WP roles + discovered + files on disk.
     *
     * @return list<array{key:string,label:string,is_super_admin:bool,is_administrator:bool,is_locked:bool,can_edit:bool,has_permissions:bool}>
     */
    public static function listRoles(): array
    {
        $options = UserPresenter::roleOptions();
        $counts = [];
        $payload = UserModel::roleCounts();
        if (!empty($payload['ok']) && is_array($payload['roles'] ?? null)) {
            $counts = $payload['roles'];
        }

        $seen = [];
        $roles = [];

        $push = static function (string $slug, string $label) use (&$seen, &$roles): void {
            $slug = GroupPermissionsModel::sanitizeRole($slug);
            if ($slug === '' || $slug === 'customer' || isset($seen[$slug])) {
                return;
            }
            $seen[$slug] = true;
            $canEdit = self::canEditRole($slug);
            $roles[] = [
                'key' => $slug,
                'label' => $label,
                'is_super_admin' => $slug === 'super_admin',
                'is_administrator' => $slug === 'administrator',
                'is_locked' => !$canEdit,
                'can_edit' => $canEdit,
                'has_permissions' => self::roleHasAccess($slug),
            ];
        };

        $push('super_admin', 'Super Admin');
        $push('administrator', 'Administrator');
        foreach ($options as $slug => $label) {
            $push((string) $slug, (string) $label);
        }
        foreach ($counts as $slug => $_count) {
            $slug = (string) $slug;
            $push($slug, UserPresenter::roleSlugLabel($slug));
        }

        foreach (glob(GroupPermissionsModel::dir() . '/*.json') ?: [] as $file) {
            $slug = basename((string) $file, '.json');
            $push($slug, UserPresenter::roleSlugLabel($slug));
        }

        return $roles;
    }

    /**
     * Map web route tail -> required permission leaf key(s). Empty = no extra check.
     *
     * @return list<string>
     */
    public static function keysForRoute(string $route): array
    {
        $route = trim($route, '/');
        if ($route === '' || $route === 'dashboard') {
            return GroupPermissionsModel::dashboardKeys();
        }
        if ($route === 'planner-entries' || str_starts_with($route, 'planner-entries/')) {
            return str_contains($route, '/')
                ? ['planner_entries.view']
                : ['planner_entries.view_list'];
        }
        if ($route === 'gallery') {
            return ['media_library.view_list'];
        }
        if ($route === 'users') {
            return ['users.view_list'];
        }
        if ($route === 'users/group-permissions') {
            return ['users.group_permissions'];
        }
        if ($route === 'users/switch-back' || str_starts_with($route, 'users/login-as/')) {
            return ['users.login_as'];
        }
        if ($route === 'users/switch-site') {
            return [];
        }
        if ($route === 'settings') {
            return ['settings.settings'];
        }
        if ($route === 'products/system-products' || str_starts_with($route, 'products/system-products/')) {
            return ['products.system_products.view'];
        }
        if ($route === 'products/store-products' || str_starts_with($route, 'products/store-products/')) {
            return ['products.store_products.view'];
        }
        if ($route === 'products/fence-styles') {
            return ['products.fence_styles.view_list'];
        }
        if (str_starts_with($route, 'products/fence-styles/')) {
            return ['products.fence_styles.view', 'products.fence_styles.edit'];
        }

        return [];
    }

    /**
     * Map API module (+ optional action) -> permission keys.
     *
     * @return list<string>
     */
    public static function keysForApi(string $module, string $action = ''): array
    {
        $module = trim($module);
        $action = trim($action);

        return match ($module) {
            'dashboard', 'dashboardController' => GroupPermissionsModel::dashboardKeys(),
            'products', 'productsController' => match ($action) {
                'update-store-product',
                'reorder-store-products',
                'download-store-products-csv',
                'import-store-products-csv' => ['products.system_products.edit'],
                'download-products-start',
                'download-products-step',
                'download-products-cancel',
                'download-products-status',
                'download-products-csv',
                'import-products-csv' => ['products.store_products.download'],
                default => ['products.system_products.view', 'products.store_products.view'],
            },
            'fenceStyles', 'fenceStylesController' => match ($action) {
                'save' => ['products.fence_styles.edit'],
                default => ['products.fence_styles.view_list', 'products.fence_styles.view', 'products.fence_styles.edit'],
            },
            'entries', 'entriesController' => match ($action) {
                'trash', 'restore', 'delete' => ['planner_entries.trash_delete_restore'],
                'dedupe-scan', 'dedupe-apply', 'restore-duplicate' => ['planner_entries.find_duplicates'],
                'export', 'import' => ['planner_entries.import_export'],
                'get' => ['planner_entries.view'],
                default => ['planner_entries.view_list'],
            },
            'gallery', 'galleryController' => match ($action) {
                'delete', 'bulk-delete' => ['media_library.delete'],
                'upload' => ['media_library.upload'],
                default => ['media_library.view_list'],
            },
            'settings', 'settingsController' => match ($action) {
                'dev-console', 'git-pull' => ['settings.dev_console'],
                default => ['settings.settings'],
            },
            'cache', 'cacheController' => ['settings.cache'],
            'groupPermissions', 'groupPermissionsController' => ['users.group_permissions'],
            'users', 'usersController' => ['users.view_list'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminViewData(string $adminBase, string $role): array
    {
        $roles = self::listRoles();
        $selected = GroupPermissionsModel::sanitizeRole($role);
        $roleKeys = [];
        foreach ($roles as $role) {
            $roleKeys[(string) ($role['key'] ?? '')] = true;
        }
        if ($selected === '' || $selected === 'customer' || !isset($roleKeys[$selected])) {
            $selected = (string) ($roles[0]['key'] ?? 'super_admin');
        }

        $canEdit = self::canEditRole($selected);

        $selectedRoleLabel = 'Permission';
        foreach ($roles as $roleOption) {
            if ((string) ($roleOption['key'] ?? '') === $selected) {
                $selectedRoleLabel = (string) ($roleOption['label'] ?? $selected);
                break;
            }
        }

        $tree = GroupPermissionsModel::permissionTree();
        $permissions = GroupPermissionsModel::get($selected);
        $csrf = AuthService::csrfToken();
        $lockNotice = $selected === 'super_admin' ? 'Super Admin always has full system access.' : '';
        $apiUrl = rtrim($adminBase, '/') . '/api.php?module=groupPermissions';

        return [
            'admin_base' => rtrim($adminBase, '/'),
            'roles' => $roles,
            'selected_role' => $selected,
            'selected_role_label' => $selectedRoleLabel,
            'is_super_admin_role' => $selected === 'super_admin',
            'is_administrator_role' => $selected === 'administrator',
            'is_locked' => !$canEdit,
            'can_edit' => $canEdit,
            'lock_notice' => $lockNotice,
            'tree' => $tree,
            'permissions' => $permissions,
            'csrf' => $csrf,
            'api_url' => $apiUrl,
            'form_action' => 'users/group-permissions',
            // JS bootstrap payload the view embeds verbatim — assembled here so the
            // template stays read-only (it used to build this array itself).
            'bootstrap' => [
                'roles' => $roles,
                'selectedRole' => $selected,
                'isSuperAdminRole' => $selected === 'super_admin',
                'isAdministratorRole' => $selected === 'administrator',
                'isLocked' => !$canEdit,
                'canEdit' => $canEdit,
                'lockNotice' => $lockNotice,
                'tree' => $tree,
                'permissions' => $permissions,
                'csrf' => $csrf,
                'apiUrl' => $apiUrl,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function apiPayload(?string $role = null): array
    {
        $role = GroupPermissionsModel::sanitizeRole((string) ($role ?? ''));
        $roles = self::listRoles();
        $roleKeys = [];
        foreach ($roles as $item) {
            $roleKeys[(string) ($item['key'] ?? '')] = true;
        }
        if ($role === '' || $role === 'customer' || !isset($roleKeys[$role])) {
            $role = (string) ($roles[0]['key'] ?? '');
        }

        $canEdit = self::canEditRole($role);

        return [
            'ok' => true,
            'roles' => $roles,
            'role' => $role,
            'isSuperAdminRole' => $role === 'super_admin',
            'isAdministratorRole' => $role === 'administrator',
            'isLocked' => !$canEdit,
            'canEdit' => $canEdit,
            'lockNotice' => $role === 'super_admin' ? 'Super Admin always has full system access.' : '',
            'tree' => GroupPermissionsModel::permissionTree(),
            'permissions' => GroupPermissionsModel::get($role),
            'csrf' => AuthService::csrfToken(),
        ];
    }

    /**
     * Build an exportable envelope for one manageable role.
     *
     * @return array{role:string,permissions:array<string,mixed>,updatedAt:string}|null
     */
    public static function exportRole(string $role): ?array
    {
        $slug = GroupPermissionsModel::sanitizeRole($role);
        if ($slug === '' || !self::isManageableRole($slug)) {
            return null;
        }

        $updatedAt = gmdate('c');
        $path = GroupPermissionsModel::dir() . '/' . $slug . '.json';
        if (is_readable($path)) {
            $raw = @file_get_contents($path);
            $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (is_array($decoded) && !empty($decoded['updatedAt'])) {
                $updatedAt = (string) $decoded['updatedAt'];
            }
        }

        return [
            'role' => $slug,
            'permissions' => GroupPermissionsModel::get($slug),
            'updatedAt' => $updatedAt,
        ];
    }

    /**
     * Export all manageable roles as a versioned bundle.
     *
     * @return array{fcGroupPermissionsExport:int,exportedAt:string,roles:array<string,array{permissions:array<string,mixed>,updatedAt:string}>}
     */
    public static function exportAll(): array
    {
        $roles = [];
        foreach (self::listRoles() as $item) {
            $slug = (string) ($item['key'] ?? '');
            if (!self::isManageableRole($slug)) {
                continue;
            }
            $envelope = self::exportRole($slug);
            if ($envelope === null) {
                continue;
            }
            $roles[$slug] = [
                'permissions' => $envelope['permissions'],
                'updatedAt' => $envelope['updatedAt'],
            ];
        }

        return [
            'fcGroupPermissionsExport' => 1,
            'exportedAt' => gmdate('c'),
            'roles' => $roles,
        ];
    }
}
