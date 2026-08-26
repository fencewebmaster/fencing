<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\GroupPermissionsModel;
use Fc\Admin\Presenters\GroupPermissionsPresenter;

/**
 * Group permissions mutations (save / import). CSRF checks stay in the Controller.
 */
final class GroupPermissionsService
{
    /**
     * @param array<string, mixed> $matrix
     * @return array{ok:bool,error?:string,permissions?:array<string,mixed>}
     */
    public static function save(string $role, array $matrix): array
    {
        $slug = GroupPermissionsModel::sanitizeRole($role);
        if ($slug === '') {
            return ['ok' => false, 'error' => 'Invalid role.'];
        }
        if ($slug === 'super_admin') {
            return ['ok' => false, 'error' => 'Super Admin always has full system access.'];
        }
        if ($slug === 'customer') {
            return ['ok' => false, 'error' => 'Customer role cannot be managed here.'];
        }
        if (!GroupPermissionsPresenter::isManageableRole($slug)) {
            return ['ok' => false, 'error' => 'This role cannot be managed here.'];
        }
        if ($slug === 'administrator' && !PermissionService::canManageAdministratorPermissions()) {
            return ['ok' => false, 'error' => 'Only Super Admin may edit Administrator role permissions.'];
        }

        $next = GroupPermissionsModel::normalizeMatrix($matrix);
        $dir = GroupPermissionsModel::dir();
        if (!is_writable($dir)) {
            return ['ok' => false, 'error' => 'writable/groups-permissions/ is not writable.'];
        }

        $path = $dir . '/' . $slug . '.json';
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
     * Import a single-role envelope or multi-role export bundle.
     *
     * @param array<string, mixed> $payload
     * @return array{ok:bool,error?:string,imported?:list<string>,skipped?:list<string>,message?:string}
     */
    public static function importPayload(array $payload): array
    {
        $entries = [];

        if (isset($payload['fcGroupPermissionsExport']) || isset($payload['roles'])) {
            $roles = is_array($payload['roles'] ?? null) ? $payload['roles'] : [];
            foreach ($roles as $slug => $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $role = GroupPermissionsModel::sanitizeRole(is_string($slug) ? $slug : (string) ($entry['role'] ?? ''));
                $matrix = isset($entry['permissions']) && is_array($entry['permissions'])
                    ? $entry['permissions']
                    : $entry;
                if ($role === '') {
                    continue;
                }
                $entries[] = ['role' => $role, 'permissions' => $matrix];
            }
        } elseif (isset($payload['role']) || isset($payload['permissions'])) {
            $role = GroupPermissionsModel::sanitizeRole((string) ($payload['role'] ?? ''));
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

        $imported = [];
        $skipped = [];
        foreach ($entries as $entry) {
            $role = (string) $entry['role'];
            if (!GroupPermissionsPresenter::isManageableRole($role)) {
                $skipped[] = $role !== '' ? $role : '(empty)';
                continue;
            }
            $result = self::save($role, is_array($entry['permissions']) ? $entry['permissions'] : []);
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
}
