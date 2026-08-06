<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

/**
 * Group permissions storage and matrix primitives.
 * Storage: writable/groups-permissions/{role-slug}.json
 */
final class GroupPermissionsModel
{
    /**
     * Nested permission tree for the editor UI.
     *
     * @return list<array{key:string,label:string,children?:list<array<string,mixed>>}>
     */
    public static function permissionTree(): array
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
                    ['key' => 'find_duplicates', 'label' => 'Find Duplicates'],
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
    public static function leafKeys(?array $nodes = null, string $prefix = ''): array
    {
        $nodes = $nodes ?? self::permissionTree();
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
                foreach (self::leafKeys($children, $path) as $childKey) {
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
    public static function defaultsMatrix(bool $value = false): array
    {
        $matrix = [];
        foreach (self::leafKeys() as $path) {
            self::setPath($matrix, $path, $value);
        }

        return $matrix;
    }

    /**
     * @param array<string, mixed> $matrix
     */
    public static function getPath(array $matrix, string $path): bool
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
     * Keep only known leaves; coerce to bool; fill missing with false.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalizeMatrix(array $input): array
    {
        $flat = self::flatten($input);
        $matrix = self::defaultsMatrix(false);
        foreach (self::leafKeys() as $path) {
            if (array_key_exists($path, $flat)) {
                self::setPath($matrix, $path, (bool) $flat[$path]);
            }
        }

        return $matrix;
    }

    /**
     * Whether any leaf is true.
     *
     * @param array<string, mixed> $matrix
     */
    public static function matrixHasGrant(array $matrix): bool
    {
        foreach (self::flatten($matrix) as $granted) {
            if ($granted) {
                return true;
            }
        }

        return false;
    }

    public static function sanitizeRole(string $role): string
    {
        $role = strtolower(trim($role));
        $role = preg_replace('/[^a-z0-9_-]+/', '-', $role) ?? '';

        return trim($role, '-_');
    }

    public static function dir(): string
    {
        $dir = FC_ROOT . '/writable/groups-permissions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Dashboard widget id -> permission leaf key.
     *
     * @return array<string, string>
     */
    public static function dashboardWidgetMap(): array
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
    public static function dashboardKeys(): array
    {
        return array_values(self::dashboardWidgetMap());
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(string $role): array
    {
        $slug = self::sanitizeRole($role);
        if ($slug === '' || $slug === 'customer') {
            return self::defaultsMatrix(false);
        }
        if ($slug === 'super_admin') {
            return self::defaultsMatrix(true);
        }

        $path = self::path($slug);
        if (!is_file($path)) {
            // Migration: Administrator keeps full access until first explicit save.
            return $slug === 'administrator' ? self::defaultsMatrix(true) : self::defaultsMatrix(false);
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $slug === 'administrator' ? self::defaultsMatrix(true) : self::defaultsMatrix(false);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $slug === 'administrator' ? self::defaultsMatrix(true) : self::defaultsMatrix(false);
        }

        // Allow either { permissions: {...} } or bare matrix.
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data = $data['permissions'];
        }

        $data = self::migrateDashboardMatrix($data);
        $data = self::migrateStoreProductsMatrix($data);
        $data = self::migrateFenceStylesMatrix($data);
        $data = self::migratePlannerEntriesMatrix($data);

        return self::normalizeMatrix($data);
    }

    /**
     * @param array<string, mixed> $matrix
     */
    private static function setPath(array &$matrix, string $path, bool $value): void
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
     * Flatten nested matrix to path => bool.
     *
     * @param array<string, mixed> $matrix
     * @return array<string, bool>
     */
    private static function flatten(array $matrix, string $prefix = ''): array
    {
        $out = [];
        foreach ($matrix as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $out = array_merge($out, self::flatten($value, $path));
            } else {
                $out[$path] = (bool) $value;
            }
        }

        return $out;
    }

    private static function path(string $role): string
    {
        return self::dir() . '/' . self::sanitizeRole($role) . '.json';
    }

    /**
     * Apply legacy dashboard.* grants onto the new widget leaves.
     *
     * @param array<string, mixed> $matrix
     * @return array<string, mixed>
     */
    private static function migrateDashboardMatrix(array $matrix): array
    {
        $flat = self::flatten($matrix);
        $widgetKeys = self::dashboardKeys();
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
     * Map legacy products.store_products.edit -> download.
     *
     * @param array<string, mixed> $matrix
     * @return array<string, mixed>
     */
    private static function migrateStoreProductsMatrix(array $matrix): array
    {
        $flat = self::flatten($matrix);
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
     * Map legacy fence_styles shape (view=Listing, edit=View/Edit) -> view_list / view / edit.
     *
     * @param array<string, mixed> $matrix
     * @return array<string, mixed>
     */
    private static function migrateFenceStylesMatrix(array $matrix): array
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
     * Map legacy planner_entries import/export/trash/delete/restore -> combined leaves.
     *
     * @param array<string, mixed> $matrix
     * @return array<string, mixed>
     */
    private static function migratePlannerEntriesMatrix(array $matrix): array
    {
        if (!isset($matrix['planner_entries']) || !is_array($matrix['planner_entries'])) {
            return $matrix;
        }

        $pe = &$matrix['planner_entries'];
        $flat = self::flatten(['planner_entries' => $pe]);

        if (
            !array_key_exists('import_export', $pe)
            && (!empty($flat['planner_entries.import']) || !empty($flat['planner_entries.export']))
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

        // Previously gated by trash_delete_restore — preserve access until first explicit save.
        if (!array_key_exists('find_duplicates', $pe) && !empty($pe['trash_delete_restore'])) {
            $pe['find_duplicates'] = true;
        }

        return $matrix;
    }
}
