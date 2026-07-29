<?php
/**
 * FC Admin — group permissions tree, storage, and helpers.
 * Storage: data/groups-permissions/{role-slug}.json
 */

declare(strict_types=1);

/**
 * Nested permission tree for the editor UI.
 *
 * @return list<array{key:string,label:string,children?:list<array<string,mixed>>}>
 */
function fc_permissions_tree(): array
{
    return [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'children' => [
                ['key' => 'kpis', 'label' => 'Dashboard KPIs'],
                ['key' => 'planner_submissions', 'label' => 'Planner submissions'],
                ['key' => 'entries_by_state', 'label' => 'Entries by state'],
                ['key' => 'performance', 'label' => 'Performance'],
                ['key' => 'popular_fence_styles', 'label' => 'Popular fence styles'],
                ['key' => 'product_selections', 'label' => 'Product selections'],
                ['key' => 'latest_entries', 'label' => 'Latest entries'],
                ['key' => 'customer_analytics', 'label' => 'Customer analytics'],
            ],
        ],
        [
            'key' => 'products',
            'label' => 'Products',
            'children' => [
                [
                    'key' => 'system_products',
                    'label' => 'System Products',
                    'children' => [
                        ['key' => 'view', 'label' => 'Listing'],
                        ['key' => 'edit', 'label' => 'Edit'],
                    ],
                ],
                [
                    'key' => 'store_products',
                    'label' => 'Store Products',
                    'children' => [
                        ['key' => 'view', 'label' => 'Listing'],
                        ['key' => 'download', 'label' => 'Download Products'],
                    ],
                ],
                [
                    'key' => 'fence_styles',
                    'label' => 'Fence Styles',
                    'children' => [
                        ['key' => 'view_list', 'label' => 'Listing'],
                        ['key' => 'view', 'label' => 'View'],
                        ['key' => 'edit', 'label' => 'Edit'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'media_library',
            'label' => 'Media Library',
            'children' => [
                ['key' => 'view_list', 'label' => 'Listing'],
                ['key' => 'upload', 'label' => 'Upload'],
                ['key' => 'delete', 'label' => 'Delete'],
            ],
        ],
        [
            'key' => 'planner_entries',
            'label' => 'Planner Entries',
            'children' => [
                ['key' => 'view_list', 'label' => 'Listing'],
                ['key' => 'view', 'label' => 'View'],
                ['key' => 'import_export', 'label' => 'Import/Export'],
                ['key' => 'trash_delete_restore', 'label' => 'Trash/Delete/Restore'],
            ],
        ],
        [
            'key' => 'users',
            'label' => 'Users',
            'children' => [
                ['key' => 'view_list', 'label' => 'Listing'],
                ['key' => 'login_as', 'label' => 'Login As'],
                ['key' => 'group_permissions', 'label' => 'Group Permissions'],
            ],
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'children' => [
                ['key' => 'settings', 'label' => 'Settings'],
            ],
        ],
    ];
}

/**
 * Dot-path leaf keys from the tree (e.g. products.system_products.view).
 *
 * @return list<string>
 */
function fc_permissions_leaf_keys(?array $nodes = null, string $prefix = ''): array
{
    $nodes = $nodes ?? fc_permissions_tree();
    $keys = [];

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $key = (string) ($node['key'] ?? '');
        if ($key === '') {
            continue;
        }
        $path = $prefix === '' ? $key : $prefix . '.' . $key;
        $children = $node['children'] ?? null;
        if (is_array($children) && $children !== []) {
            foreach (fc_permissions_leaf_keys($children, $path) as $childKey) {
                $keys[] = $childKey;
            }
        } else {
            $keys[] = $path;
        }
    }

    return $keys;
}

/**
 * Nested matrix with every leaf set to $value.
 *
 * @return array<string, mixed>
 */
function fc_permissions_defaults_matrix(bool $value = false): array
{
    $matrix = [];
    foreach (fc_permissions_leaf_keys() as $path) {
        fc_permissions_set_path($matrix, $path, $value);
    }

    return $matrix;
}

/**
 * @param array<string, mixed> $matrix
 */
function fc_permissions_set_path(array &$matrix, string $path, bool $value): void
{
    $parts = explode('.', $path);
    $ref = &$matrix;
    $last = count($parts) - 1;
    foreach ($parts as $i => $part) {
        if ($i === $last) {
            $ref[$part] = $value;
            return;
        }
        if (!isset($ref[$part]) || !is_array($ref[$part])) {
            $ref[$part] = [];
        }
        $ref = &$ref[$part];
    }
}

/**
 * @param array<string, mixed> $matrix
 */
function fc_permissions_get_path(array $matrix, string $path): bool
{
    $parts = explode('.', $path);
    $ref = $matrix;
    foreach ($parts as $part) {
        if (!is_array($ref) || !array_key_exists($part, $ref)) {
            return false;
        }
        $ref = $ref[$part];
    }

    return $ref === true;
}

/**
 * Flatten nested matrix to path => bool.
 *
 * @param array<string, mixed> $matrix
 * @return array<string, bool>
 */
function fc_permissions_flatten(array $matrix, string $prefix = ''): array
{
    $out = [];
    foreach ($matrix as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) {
            $out = array_merge($out, fc_permissions_flatten($value, $path));
        } else {
            $out[$path] = (bool) $value;
        }
    }

    return $out;
}

/**
 * Keep only known leaves; coerce to bool; fill missing with false.
 *
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function fc_permissions_normalize_matrix(array $input): array
{
    $flat = fc_permissions_flatten($input);
    $matrix = fc_permissions_defaults_matrix(false);
    foreach (fc_permissions_leaf_keys() as $path) {
        if (array_key_exists($path, $flat)) {
            fc_permissions_set_path($matrix, $path, (bool) $flat[$path]);
        }
    }

    return $matrix;
}

/**
 * Whether any leaf is true.
 *
 * @param array<string, mixed> $matrix
 */
function fc_permissions_matrix_has_grant(array $matrix): bool
{
    foreach (fc_permissions_flatten($matrix) as $granted) {
        if ($granted) {
            return true;
        }
    }

    return false;
}

/**
 * Dashboard widget id → permission leaf key.
 *
 * @return array<string, string>
 */
function fc_permissions_dashboard_widget_map(): array
{
    return [
        'kpis' => 'dashboard.kpis',
        'trend' => 'dashboard.planner_submissions',
        'states' => 'dashboard.entries_by_state',
        'performance' => 'dashboard.performance',
        'fence-styles' => 'dashboard.popular_fence_styles',
        'insights' => 'dashboard.product_selections',
        'recent' => 'dashboard.latest_entries',
        'customers' => 'dashboard.customer_analytics',
    ];
}

/**
 * @return list<string>
 */
function fc_permissions_dashboard_keys(): array
{
    return array_values(fc_permissions_dashboard_widget_map());
}

/**
 * Visible dashboard widgets for the current user.
 *
 * @return array<string, bool>
 */
function fc_permissions_dashboard_widgets_visible(): array
{
    $visible = [];
    foreach (fc_permissions_dashboard_widget_map() as $widgetId => $permKey) {
        $visible[$widgetId] = !function_exists('fc_auth_user_can') || fc_auth_user_can($permKey);
    }

    return $visible;
}

/**
 * Apply legacy dashboard.* grants onto the new widget leaves.
 *
 * @param array<string, mixed> $matrix
 * @return array<string, mixed>
 */
function fc_permissions_migrate_dashboard_matrix(array $matrix): array
{
    $flat = fc_permissions_flatten($matrix);
    $widgetKeys = fc_permissions_dashboard_keys();
    $legacyAll = !empty($flat['dashboard.dashboard']);
    $legacyReports = !empty($flat['dashboard.reports']);
    $legacyAnalytics = !empty($flat['dashboard.analytics']);

    if (!$legacyAll && !$legacyReports && !$legacyAnalytics) {
        return $matrix;
    }

    foreach ($widgetKeys as $key) {
        $parts = explode('.', $key);
        $cur = &$matrix;
        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                if ($legacyAll) {
                    $cur[$part] = true;
                } elseif ($legacyAnalytics && in_array($key, [
                    'dashboard.customer_analytics',
                    'dashboard.planner_submissions',
                    'dashboard.entries_by_state',
                    'dashboard.performance',
                ], true)) {
                    $cur[$part] = true;
                } elseif ($legacyReports && in_array($key, [
                    'dashboard.popular_fence_styles',
                    'dashboard.product_selections',
                    'dashboard.latest_entries',
                    'dashboard.kpis',
                ], true)) {
                    $cur[$part] = true;
                }
            } else {
                if (!isset($cur[$part]) || !is_array($cur[$part])) {
                    $cur[$part] = [];
                }
                $cur = &$cur[$part];
            }
        }
        unset($cur);
    }

    return $matrix;
}

/**
 * Map legacy products.store_products.edit → download.
 *
 * @param array<string, mixed> $matrix
 * @return array<string, mixed>
 */
function fc_permissions_migrate_store_products_matrix(array $matrix): array
{
    $flat = fc_permissions_flatten($matrix);
    if (empty($flat['products.store_products.edit'])) {
        return $matrix;
    }

    if (!isset($matrix['products']) || !is_array($matrix['products'])) {
        $matrix['products'] = [];
    }
    if (!isset($matrix['products']['store_products']) || !is_array($matrix['products']['store_products'])) {
        $matrix['products']['store_products'] = [];
    }
    $matrix['products']['store_products']['download'] = true;

    return $matrix;
}

/**
 * Map legacy fence_styles shape (view=Listing, edit=View/Edit) → view_list / view / edit.
 *
 * @param array<string, mixed> $matrix
 * @return array<string, mixed>
 */
function fc_permissions_migrate_fence_styles_matrix(array $matrix): array
{
    if (!isset($matrix['products']) || !is_array($matrix['products'])) {
        return $matrix;
    }
    if (!isset($matrix['products']['fence_styles']) || !is_array($matrix['products']['fence_styles'])) {
        return $matrix;
    }

    $fs = &$matrix['products']['fence_styles'];
    // Already on new shape.
    if (array_key_exists('view_list', $fs)) {
        return $matrix;
    }

    $hadListing = !empty($fs['view']);
    $hadViewEdit = !empty($fs['edit']);

    $fs['view_list'] = $hadListing;
    $fs['view'] = $hadViewEdit;
    $fs['edit'] = $hadViewEdit;

    return $matrix;
}

/**
 * Map legacy planner_entries import/export/trash/delete/restore → combined leaves.
 *
 * @param array<string, mixed> $matrix
 * @return array<string, mixed>
 */
function fc_permissions_migrate_planner_entries_matrix(array $matrix): array
{
    if (!isset($matrix['planner_entries']) || !is_array($matrix['planner_entries'])) {
        return $matrix;
    }

    $pe = &$matrix['planner_entries'];
    $flat = fc_permissions_flatten(['planner_entries' => $pe]);

    if (
        !array_key_exists('import_export', $pe)
        && (
            !empty($flat['planner_entries.import'])
            || !empty($flat['planner_entries.export'])
        )
    ) {
        $pe['import_export'] = true;
    }

    if (
        !array_key_exists('trash_delete_restore', $pe)
        && (
            !empty($flat['planner_entries.trash'])
            || !empty($flat['planner_entries.delete'])
            || !empty($flat['planner_entries.restore'])
        )
    ) {
        $pe['trash_delete_restore'] = true;
    }

    return $matrix;
}

/**
 * Whether a WP role slug has FC admin access.
 */
function fc_group_permissions_role_has_access(string $role): bool
{
    $slug = fc_group_permissions_sanitize_role($role);
    if ($slug === '' || $slug === 'customer') {
        return false;
    }
    if ($slug === 'super_admin') {
        return true;
    }

    return fc_permissions_matrix_has_grant(fc_group_permissions_get($slug));
}

/**
 * Roles that can be configured on the Group Permissions page.
 */
function fc_group_permissions_is_manageable_role(string $role): bool
{
    $slug = fc_group_permissions_sanitize_role($role);

    return $slug !== '' && $slug !== 'customer' && $slug !== 'super_admin';
}

/**
 * Whether the current user may edit permissions for a role.
 */
function fc_group_permissions_can_edit_role(string $role): bool
{
    $slug = fc_group_permissions_sanitize_role($role);
    if ($slug === '' || $slug === 'customer' || $slug === 'super_admin') {
        return false;
    }

    return fc_group_permissions_is_manageable_role($slug);
}

function fc_group_permissions_dir(): string
{
    $dir = dirname(__DIR__) . '/data/groups-permissions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir;
}

function fc_group_permissions_sanitize_role(string $role): string
{
    $role = strtolower(trim($role));
    $role = preg_replace('/[^a-z0-9_-]+/', '-', $role) ?? '';
    $role = trim($role, '-_');

    return $role;
}

function fc_group_permissions_path(string $role): string
{
    $slug = fc_group_permissions_sanitize_role($role);

    return fc_group_permissions_dir() . '/' . $slug . '.json';
}

/**
 * @return array<string, mixed>
 */
function fc_group_permissions_get(string $role): array
{
    $slug = fc_group_permissions_sanitize_role($role);
    if ($slug === '' || $slug === 'customer') {
        return fc_permissions_defaults_matrix(false);
    }
    if ($slug === 'super_admin') {
        return fc_permissions_defaults_matrix(true);
    }

    $path = fc_group_permissions_path($slug);
    if (!is_file($path)) {
        // Migration: Administrator keeps full access until first explicit save.
        if ($slug === 'administrator') {
            return fc_permissions_defaults_matrix(true);
        }

        return fc_permissions_defaults_matrix(false);
    }

    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $slug === 'administrator'
            ? fc_permissions_defaults_matrix(true)
            : fc_permissions_defaults_matrix(false);
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return $slug === 'administrator'
            ? fc_permissions_defaults_matrix(true)
            : fc_permissions_defaults_matrix(false);
    }

    // Allow either { permissions: {...} } or bare matrix.
    if (isset($data['permissions']) && is_array($data['permissions'])) {
        $data = $data['permissions'];
    }

    $data = fc_permissions_migrate_dashboard_matrix($data);
    $data = fc_permissions_migrate_store_products_matrix($data);
    $data = fc_permissions_migrate_fence_styles_matrix($data);
    $data = fc_permissions_migrate_planner_entries_matrix($data);

    return fc_permissions_normalize_matrix($data);
}

/**
 * @param array<string, mixed> $matrix
 * @return array{ok:bool,error?:string,permissions?:array<string,mixed>}
 */
function fc_group_permissions_save(string $role, array $matrix): array
{
    $slug = fc_group_permissions_sanitize_role($role);
    if ($slug === '') {
        return ['ok' => false, 'error' => 'Invalid role.'];
    }
    if ($slug === 'super_admin') {
        return ['ok' => false, 'error' => 'Super Admin always has full system access.'];
    }
    if ($slug === 'customer') {
        return ['ok' => false, 'error' => 'Customer role cannot be managed here.'];
    }
    if (!fc_group_permissions_is_manageable_role($slug)) {
        return ['ok' => false, 'error' => 'This role cannot be managed here.'];
    }

    $next = fc_permissions_normalize_matrix($matrix);
    $dir = fc_group_permissions_dir();
    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'data/groups-permissions/ is not writable.'];
    }

    $path = fc_group_permissions_path($slug);
    $payload = [
        'role' => $slug,
        'permissions' => $next,
        'updatedAt' => gmdate('c'),
    ];

    $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    $written = file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($written === false) {
        return ['ok' => false, 'error' => 'Unable to write permissions file.'];
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);

        return ['ok' => false, 'error' => 'Unable to save permissions file.'];
    }

    return ['ok' => true, 'permissions' => $next];
}

/**
 * Build an exportable envelope for one manageable role.
 *
 * @return array{role:string,permissions:array<string,mixed>,updatedAt:string}|null
 */
function fc_group_permissions_export_role(string $role): ?array
{
    $slug = fc_group_permissions_sanitize_role($role);
    if ($slug === '' || !fc_group_permissions_is_manageable_role($slug)) {
        return null;
    }

    $updatedAt = gmdate('c');
    $path = fc_group_permissions_path($slug);
    if (is_readable($path)) {
        $raw = @file_get_contents($path);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (is_array($decoded) && !empty($decoded['updatedAt'])) {
            $updatedAt = (string) $decoded['updatedAt'];
        }
    }

    return [
        'role' => $slug,
        'permissions' => fc_group_permissions_get($slug),
        'updatedAt' => $updatedAt,
    ];
}

/**
 * Export all manageable roles as a versioned bundle.
 *
 * @return array{fcGroupPermissionsExport:int,exportedAt:string,roles:array<string,array{permissions:array<string,mixed>,updatedAt:string}>}
 */
function fc_group_permissions_export_all(): array
{
    $roles = [];
    foreach (fc_group_permissions_list_roles() as $item) {
        $slug = (string) ($item['key'] ?? '');
        if (!fc_group_permissions_is_manageable_role($slug)) {
            continue;
        }
        $envelope = fc_group_permissions_export_role($slug);
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

/**
 * Import a single-role envelope or multi-role export bundle.
 *
 * @param array<string, mixed> $payload
 * @return array{ok:bool,error?:string,imported?:list<string>,skipped?:list<string>,message?:string}
 */
function fc_group_permissions_import_payload(array $payload): array
{
    $imported = [];
    $skipped = [];
    $entries = [];

    if (isset($payload['fcGroupPermissionsExport']) || isset($payload['roles'])) {
        $roles = is_array($payload['roles'] ?? null) ? $payload['roles'] : [];
        foreach ($roles as $slug => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $role = fc_group_permissions_sanitize_role(is_string($slug) ? $slug : (string) ($entry['role'] ?? ''));
            $matrix = isset($entry['permissions']) && is_array($entry['permissions'])
                ? $entry['permissions']
                : $entry;
            if ($role === '') {
                continue;
            }
            $entries[] = ['role' => $role, 'permissions' => $matrix];
        }
    } elseif (isset($payload['role']) || isset($payload['permissions'])) {
        $role = fc_group_permissions_sanitize_role((string) ($payload['role'] ?? ''));
        $matrix = isset($payload['permissions']) && is_array($payload['permissions'])
            ? $payload['permissions']
            : [];
        if ($role !== '') {
            $entries[] = ['role' => $role, 'permissions' => $matrix];
        }
    } else {
        // Bare matrix is not enough without a role slug.
        return ['ok' => false, 'error' => 'Invalid import file. Expected a role permissions JSON export.'];
    }

    if ($entries === []) {
        return ['ok' => false, 'error' => 'No role permissions found in the import file.'];
    }

    foreach ($entries as $entry) {
        $role = (string) $entry['role'];
        if (!fc_group_permissions_is_manageable_role($role)) {
            $skipped[] = $role !== '' ? $role : '(empty)';
            continue;
        }
        $result = fc_group_permissions_save($role, is_array($entry['permissions']) ? $entry['permissions'] : []);
        if (empty($result['ok'])) {
            return [
                'ok' => false,
                'error' => (string) ($result['error'] ?? ('Failed to import role: ' . $role)),
                'imported' => $imported,
                'skipped' => $skipped,
            ];
        }
        $imported[] = $role;
    }

    if ($imported === []) {
        return [
            'ok' => false,
            'error' => 'No manageable roles were imported.'
                . ($skipped !== [] ? ' Skipped: ' . implode(', ', $skipped) . '.' : ''),
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    $message = 'Imported ' . count($imported) . ' role' . (count($imported) === 1 ? '' : 's') . '.';
    if ($skipped !== []) {
        $message .= ' Skipped: ' . implode(', ', $skipped) . '.';
    }

    return [
        'ok' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'message' => $message,
    ];
}

/**
 * Roles for the left panel: Super Admin + known WP roles + discovered + files on disk.
 *
 * @return list<array{key:string,label:string,is_super_admin:bool,is_administrator:bool,is_locked:bool,can_edit:bool,has_permissions:bool}>
 */
function fc_group_permissions_list_roles(): array
{
    if (!function_exists('fc_users_admin_role_options')) {
        require_once __DIR__ . '/users_admin.php';
    }
    if (!function_exists('fc_users_admin_role_counts')) {
        require_once __DIR__ . '/users_admin.php';
    }

    $options = fc_users_admin_role_options();
    $counts = [];
    $payload = fc_users_admin_role_counts();
    if (!empty($payload['ok']) && is_array($payload['roles'] ?? null)) {
        $counts = $payload['roles'];
    }

    $seen = [];
    $roles = [];

    $push = static function (string $slug, string $label) use (&$seen, &$roles): void {
        $slug = fc_group_permissions_sanitize_role($slug);
        if ($slug === '' || $slug === 'customer' || isset($seen[$slug])) {
            return;
        }
        $seen[$slug] = true;
        $isSuper = $slug === 'super_admin';
        $isAdmin = $slug === 'administrator';
        $canEdit = fc_group_permissions_can_edit_role($slug);
        $roles[] = [
            'key' => $slug,
            'label' => $label,
            'is_super_admin' => $isSuper,
            'is_administrator' => $isAdmin,
            'is_locked' => !$canEdit,
            'can_edit' => $canEdit,
            'has_permissions' => fc_group_permissions_role_has_access($slug),
        ];
    };

    $push('super_admin', 'Super Admin');
    $push('administrator', 'Administrator');
    foreach ($options as $slug => $label) {
        $push((string) $slug, (string) $label);
    }
    foreach ($counts as $slug => $_count) {
        $slug = (string) $slug;
        $label = function_exists('fc_users_admin_role_slug_label')
            ? fc_users_admin_role_slug_label($slug)
            : ucwords(str_replace(['_', '-'], ' ', $slug));
        $push($slug, $label);
    }

    foreach (glob(fc_group_permissions_dir() . '/*.json') ?: [] as $file) {
        $slug = basename((string) $file, '.json');
        $label = function_exists('fc_users_admin_role_slug_label')
            ? fc_users_admin_role_slug_label($slug)
            : ucwords(str_replace(['_', '-'], ' ', $slug));
        $push($slug, $label);
    }

    return $roles;
}

/**
 * Map web route tail → required permission leaf key(s). Empty = no extra check.
 *
 * @return list<string>
 */
function fc_permissions_keys_for_route(string $route): array
{
    $route = trim($route, '/');
    if ($route === '' || $route === 'dashboard') {
        return fc_permissions_dashboard_keys();
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
 * Map API module (+ optional action) → permission keys.
 *
 * @return list<string>
 */
function fc_permissions_keys_for_api(string $module, string $action = ''): array
{
    $module = trim($module);
    $action = trim($action);

    return match ($module) {
        'dashboard', 'dashboardController' => fc_permissions_dashboard_keys(),
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
            'export', 'import' => ['planner_entries.import_export'],
            'get' => ['planner_entries.view'],
            default => ['planner_entries.view_list'],
        },
        'gallery', 'galleryController' => match ($action) {
            'delete', 'bulk-delete' => ['media_library.delete'],
            'upload' => ['media_library.upload'],
            default => ['media_library.view_list'],
        },
        'settings', 'settingsController' => ['settings.settings'],
        'groupPermissions', 'groupPermissionsController' => ['users.group_permissions'],
        'users', 'usersController' => ['users.view_list'],
        default => [],
    };
}

/**
 * @return array<string, mixed>
 */
function fc_group_permissions_admin_view_data(string $adminBase): array
{
    $roles = fc_group_permissions_list_roles();
    $selected = fc_group_permissions_sanitize_role((string) ($_GET['role'] ?? ''));
    $roleKeys = [];
    foreach ($roles as $role) {
        $roleKeys[(string) ($role['key'] ?? '')] = true;
    }
    if ($selected === '' || $selected === 'customer' || !isset($roleKeys[$selected])) {
        $selected = (string) ($roles[0]['key'] ?? 'super_admin');
    }

    $isSuperAdminRole = $selected === 'super_admin';
    $isAdminRole = $selected === 'administrator';
    $canEdit = fc_group_permissions_can_edit_role($selected);
    $isLocked = !$canEdit;
    $permissions = fc_group_permissions_get($selected);
    $lockNotice = $isSuperAdminRole ? 'Super Admin always has full system access.' : '';

    return [
        'admin_base' => rtrim($adminBase, '/'),
        'roles' => $roles,
        'selected_role' => $selected,
        'is_super_admin_role' => $isSuperAdminRole,
        'is_administrator_role' => $isAdminRole,
        'is_locked' => $isLocked,
        'can_edit' => $canEdit,
        'lock_notice' => $lockNotice,
        'tree' => fc_permissions_tree(),
        'permissions' => $permissions,
        'csrf' => function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '',
        'api_url' => rtrim($adminBase, '/') . '/api.php?module=groupPermissions',
        'form_action' => 'users/group-permissions',
    ];
}

/**
 * @return array<string, mixed>
 */
function fc_group_permissions_api_payload(?string $role = null): array
{
    $role = fc_group_permissions_sanitize_role((string) ($role ?? ''));
    $roles = fc_group_permissions_list_roles();
    $roleKeys = [];
    foreach ($roles as $item) {
        $roleKeys[(string) ($item['key'] ?? '')] = true;
    }
    if ($role === '' || $role === 'customer' || !isset($roleKeys[$role])) {
        $role = (string) ($roles[0]['key'] ?? '');
    }

    $isSuperAdminRole = $role === 'super_admin';
    $isAdmin = $role === 'administrator';
    $canEdit = fc_group_permissions_can_edit_role($role);
    $lockNotice = $isSuperAdminRole ? 'Super Admin always has full system access.' : '';

    return [
        'ok' => true,
        'roles' => $roles,
        'role' => $role,
        'isSuperAdminRole' => $isSuperAdminRole,
        'isAdministratorRole' => $isAdmin,
        'isLocked' => !$canEdit,
        'canEdit' => $canEdit,
        'lockNotice' => $lockNotice,
        'tree' => fc_permissions_tree(),
        'permissions' => fc_group_permissions_get($role),
        'csrf' => function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '',
    ];
}
