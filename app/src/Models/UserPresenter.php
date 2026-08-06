<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\ImpersonationService;
use Fc\Admin\Services\PermissionService;
use Fc\Admin\Services\PresenceService;
use Fc\Admin\Services\SystemSettings;

/**
 * Users list — pure formatting + page orchestration (config/users_admin.php migration).
 */
final class UserPresenter
{
    private const PER_PAGE_OPTIONS = [30, 50, 100, 250, 500];
    private const DEFAULT_PER_PAGE = 30;

    private const ROLE_OPTIONS = [
        'administrator' => 'Administrator',
        'editor' => 'Editor',
        'author' => 'Author',
        'contributor' => 'Contributor',
        'subscriber' => 'Subscriber',
        'customer' => 'Customer',
        'shop_manager' => 'Shop manager',
    ];

    private static function routeSlug(): string
    {
        return 'users';
    }

    private static function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function listPath(string $adminBase): string
    {
        return rtrim($adminBase, '/') . '/' . self::routeSlug();
    }

    /**
     * @return list<int>
     */
    public static function perPageOptions(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    public static function defaultPerPage(): int
    {
        return self::DEFAULT_PER_PAGE;
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return self::ROLE_OPTIONS;
    }

    public static function isValidRoleSlug(string $role): bool
    {
        return $role !== '' && (bool) preg_match('/^[a-z0-9_-]+$/', $role);
    }

    public static function roleSlugLabel(string $role): string
    {
        $role = strtolower(trim($role));
        if ($role === '') {
            return '';
        }

        return self::ROLE_OPTIONS[$role] ?? ucwords(str_replace(['_', '-'], ' ', $role));
    }

    /**
     * @param list<string> $roles
     */
    public static function roleLabel(array $roles): string
    {
        if ($roles === []) {
            return '';
        }

        $labels = [];
        foreach ($roles as $role) {
            $labels[] = self::roleSlugLabel((string) $role);
        }

        return implode(', ', $labels);
    }

    /**
     * Gravatar URL (same default WordPress uses).
     */
    public static function avatarUrl(string $email, int $size = 64): string
    {
        $email = strtolower(trim($email));
        $hash = $email !== '' ? md5($email) : md5('unknown@example.com');
        $size = max(24, min(256, $size));

        return 'https://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=mp&r=g';
    }

    public static function loginAsUrl(string $adminBase, int $userId): string
    {
        $token = AuthService::csrfToken();

        return rtrim($adminBase, '/') . '/users/login-as/' . $userId
            . '?_token=' . rawurlencode($token);
    }

    public static function switchBackUrl(string $adminBase): string
    {
        $token = AuthService::csrfToken();

        return rtrim($adminBase, '/') . '/users/switch-back'
            . '?_token=' . rawurlencode($token);
    }

    public static function formatDatetime(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (ctype_digit($value)) {
            $ts = (int) $value;
            if ($ts > 0) {
                try {
                    $dt = new \DateTime('@' . $ts);
                    $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                } catch (\Exception $e) {
                    return '';
                }

                return $dt->format(self::dateFormat());
            }
        }

        try {
            $dt = new \DateTime($value);
        } catch (\Exception $e) {
            return $value;
        }

        return $dt->format(self::dateFormat());
    }

    /**
     * Show recent activity as elapsed time; use a timestamp after the configured window.
     */
    public static function formatActivityDatetime(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        try {
            if (ctype_digit($value) && (int) $value > 0) {
                $dt = new \DateTime('@' . (int) $value);
                $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            } else {
                $dt = new \DateTime($value);
            }
        } catch (\Exception $e) {
            return '';
        }

        $relativeSeconds = SystemSettings::activityRelativeSeconds();

        $elapsed = time() - $dt->getTimestamp();
        if ($elapsed >= 0 && $elapsed < $relativeSeconds) {
            if ($elapsed < 60) {
                return 'just now';
            }
            if ($elapsed < 3600) {
                $amount = (int) floor($elapsed / 60);
                $unit = 'minute';
            } else {
                $amount = (int) floor($elapsed / 3600);
                $unit = 'hour';
            }

            return $amount . ' ' . $unit . ($amount === 1 ? '' : 's') . ' ago';
        }

        return $dt->format('Y/d/m h:i A');
    }

    private static function dateFormat(): string
    {
        return SystemSettings::dateFormatPhp();
    }

    /**
     * Device + browser icons for the Users list (same icons as planner entries).
     *
     * @return array{device:string,device_icon:string,browser:string,browser_icon:string,user_agent:string}
     */
    public static function deviceFields(string $device = '', string $userAgent = ''): array
    {
        $device = trim($device);
        if ($device === '') {
            $device = 'Unknown';
        }
        $browser = PlannerEntryPresenter::browserName($userAgent);
        if ($browser === '') {
            $browser = 'Unknown';
        }

        return [
            'device' => $device,
            'device_icon' => PlannerEntryPresenter::deviceIcon($device),
            'browser' => $browser,
            'browser_icon' => PlannerEntryPresenter::browserIcon($browser),
            'user_agent' => trim($userAgent),
        ];
    }

    /**
     * @return list<int|string>
     */
    public static function paginationWindow(int $current, int $total): array
    {
        if ($total <= 7) {
            $pages = [];
            for ($i = 1; $i <= $total; $i++) {
                $pages[] = $i;
            }

            return $pages;
        }

        $pages = [1];
        $start = max(2, $current - 1);
        $end = min($total - 1, $current + 1);

        if ($start > 2) {
            $pages[] = '…';
        }
        for ($p = $start; $p <= $end; $p++) {
            $pages[] = $p;
        }
        if ($end < $total - 1) {
            $pages[] = '…';
        }
        $pages[] = $total;

        return $pages;
    }

    /**
     * @param array<string, mixed> $query
     * @return array{q:string,role:string,online:bool,page:int,per_page:int|string,is_all:bool,offset:int}
     */
    private static function parseRequest(array $query): array
    {
        $q = trim((string) ($query['q'] ?? ''));
        $role = strtolower(trim((string) ($query['role'] ?? '')));
        $onlineRaw = strtolower(trim((string) ($query['online'] ?? '')));
        $online = in_array($onlineRaw, ['1', 'true', 'yes', 'on'], true);
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPageRaw = strtolower(trim((string) ($query['per_page'] ?? (string) self::DEFAULT_PER_PAGE)));

        if ($role !== '' && !self::isValidRoleSlug($role)) {
            $role = '';
        }

        $isAll = ($perPageRaw === 'all');
        $perPage = self::DEFAULT_PER_PAGE;

        if ($isAll) {
            $perPage = 0;
        } elseif (in_array((int) $perPageRaw, self::PER_PAGE_OPTIONS, true)) {
            $perPage = (int) $perPageRaw;
        }

        $offset = $isAll ? 0 : ($page - 1) * $perPage;

        return [
            'q' => $q,
            'role' => $role,
            'online' => $online,
            'page' => $page,
            'per_page' => $isAll ? 'all' : $perPage,
            'is_all' => $isAll,
            'offset' => $offset,
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private static function queryFromRequest(array $request): array
    {
        $query = [];
        $q = trim((string) ($request['q'] ?? ''));
        $role = trim((string) ($request['role'] ?? ''));
        $online = !empty($request['online']);
        $page = (int) ($request['page'] ?? 1);
        $perPage = $request['per_page'] ?? self::DEFAULT_PER_PAGE;

        if ($q !== '') {
            $query['q'] = $q;
        }
        if ($role !== '') {
            $query['role'] = $role;
        }
        if ($online) {
            $query['online'] = 1;
        }
        if ($page > 1) {
            $query['page'] = $page;
        }
        if ($perPage === 'all' || (is_int($perPage) && $perPage !== self::DEFAULT_PER_PAGE)) {
            $query['per_page'] = $perPage;
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function buildQueryString(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = rawurlencode((string) $key) . '[]=' . rawurlencode((string) $item);
                }
                continue;
            }
            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return implode('&', $parts);
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $overrides
     */
    public static function url(string $adminBase, array $request, array $overrides = []): string
    {
        $base = self::listPath($adminBase);
        $query = array_merge(self::queryFromRequest($request), $overrides);

        foreach (['q', 'role', 'online', 'page', 'per_page'] as $key) {
            if (
                array_key_exists($key, $overrides)
                && ($overrides[$key] === '' || $overrides[$key] === null || $overrides[$key] === false || $overrides[$key] === 0)
            ) {
                unset($query[$key]);
            }
        }

        $qs = self::buildQueryString($query);

        return $qs === '' ? $base : $base . '?' . $qs;
    }

    /**
     * @param array<string, mixed> $req
     */
    public static function filterHiddenHtml(array $req): string
    {
        $html = '';
        $q = trim((string) ($req['q'] ?? ''));
        $role = trim((string) ($req['role'] ?? ''));

        if ($q !== '') {
            $html .= '<input type="hidden" name="q" value="' . self::escapeAttr($q) . '">';
        }
        if ($role !== '') {
            $html .= '<input type="hidden" name="role" value="' . self::escapeAttr($role) . '">';
        }
        if (!empty($req['online'])) {
            $html .= '<input type="hidden" name="online" value="1">';
        }

        return $html;
    }

    /**
     * Ordered role tabs: known roles first (with count > 0), then other roles A-Z.
     *
     * @param array<string, int> $roleCounts
     * @return list<array{key:string,label:string,count:int}>
     */
    private static function buildRoleTabs(array $roleCounts): array
    {
        $tabs = [];
        $seen = [];

        foreach (self::ROLE_OPTIONS as $slug => $label) {
            $count = (int) ($roleCounts[$slug] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $tabs[] = ['key' => $slug, 'label' => $label, 'count' => $count];
            $seen[$slug] = true;
        }

        $extra = [];
        foreach ($roleCounts as $slug => $count) {
            $slug = strtolower(trim((string) $slug));
            $count = (int) $count;
            if ($slug === '' || $count <= 0 || isset($seen[$slug])) {
                continue;
            }
            $extra[] = ['key' => $slug, 'label' => self::roleSlugLabel($slug), 'count' => $count];
        }

        usort($extra, static function (array $a, array $b): int {
            return strcasecmp((string) $a['label'], (string) $b['label']);
        });

        return array_merge($tabs, $extra);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function listViewData(string $adminBase, string $appBase, array $query = []): array
    {
        $appBase = rtrim($appBase, '/');
        $request = self::parseRequest($query);
        $limit = $request['is_all'] ? 0 : (int) $request['per_page'];

        $onlineUserIds = array_keys(PresenceService::onlineMap());

        if (!empty($request['online']) && $onlineUserIds === []) {
            $list = ['ok' => true, 'items' => [], 'total' => 0, 'error' => null];
        } else {
            $list = UserModel::list(
                $request['q'],
                $request['role'],
                $limit,
                (int) $request['offset'],
                !empty($request['online']) ? $onlineUserIds : []
            );
        }

        $error = '';
        if (empty($list['ok'])) {
            $error = (string) ($list['error'] ?? 'Could not load users.');
            $list = ['ok' => false, 'items' => [], 'total' => 0];
        }

        $allCount = $total = (int) ($list['total'] ?? 0);
        $roleCountsPayload = UserModel::roleCounts();
        $roleCounts = is_array($roleCountsPayload['roles'] ?? null) ? $roleCountsPayload['roles'] : [];
        if (!empty($roleCountsPayload['ok'])) {
            $allCount = (int) ($roleCountsPayload['total'] ?? $allCount);
        } elseif ($request['q'] !== '' || $request['role'] !== '' || !empty($request['online'])) {
            $allList = UserModel::list('', '', 1, 0);
            if (!empty($allList['ok'])) {
                $allCount = (int) ($allList['total'] ?? $allCount);
            }
        }

        $items = is_array($list['items'] ?? null) ? $list['items'] : [];
        $total = (int) ($list['total'] ?? count($items));
        $perPage = $request['is_all'] ? max(1, count($items)) : max(1, (int) $request['per_page']);
        $totalPages = $request['is_all'] ? 1 : max(1, (int) ceil($total / $perPage));

        if ($request['page'] > $totalPages && $totalPages > 0) {
            return ['redirect_url' => self::url($adminBase, $request, ['page' => $totalPages])];
        }

        $shownFrom = count($items) ? $request['offset'] + 1 : 0;
        $shownTo = $request['offset'] + count($items);

        if (!count($items)) {
            $countLabel = '0 users';
        } elseif ($request['is_all']) {
            $countLabel = count($items) . ' user' . (count($items) === 1 ? '' : 's');
        } elseif ($total > 0) {
            $countLabel = $shownFrom . '–' . $shownTo . ' of ' . $total;
        } else {
            $countLabel = $shownFrom . '–' . $shownTo;
        }

        $url = static fn (array $overrides = []): string => self::url($adminBase, $request, $overrides);

        $activeRole = trim((string) $request['role']);
        $tabs = [
            [
                'key' => 'all',
                'label' => 'All',
                'count' => $allCount,
                'is_active' => $activeRole === '' && empty($request['online']),
                'href' => $url(['role' => '', 'online' => 0, 'page' => 1]),
            ],
            [
                'key' => 'online',
                'label' => 'Online',
                'count' => count($onlineUserIds),
                'is_active' => !empty($request['online']),
                'href' => $url(['role' => '', 'online' => 1, 'page' => 1]),
            ],
        ];
        foreach (self::buildRoleTabs($roleCounts) as $roleTab) {
            $slug = (string) ($roleTab['key'] ?? '');
            if ($slug === '') {
                continue;
            }
            $tabs[] = [
                'key' => $slug,
                'label' => (string) ($roleTab['label'] ?? self::roleSlugLabel($slug)),
                'count' => (int) ($roleTab['count'] ?? 0),
                'is_active' => empty($request['online']) && $activeRole === $slug,
                'href' => $url(['role' => $slug, 'online' => 0, 'page' => 1]),
            ];
        }

        $canLoginAs = PermissionService::can('users.login_as');
        $canManageGroupPermissions = PermissionService::can('users.group_permissions');

        $activeRoleNeedsPermissions = false;
        $activeRoleSetPermissionsUrl = '';
        if (
            $canManageGroupPermissions
            && $activeRole !== ''
            && GroupPermissionsPresenter::isManageableRole($activeRole)
            && !GroupPermissionsPresenter::roleHasAccess($activeRole)
        ) {
            $activeRoleNeedsPermissions = true;
            $activeRoleSetPermissionsUrl = rtrim($adminBase, '/') . '/users/group-permissions?role=' . rawurlencode($activeRole);
        }

        $authUser = AuthService::user();
        $currentUserId = is_array($authUser) ? (int) ($authUser['id'] ?? 0) : 0;

        $userIds = [];
        foreach ($items as $item) {
            $uid = (int) ($item['id'] ?? 0);
            if ($uid > 0) {
                $userIds[] = $uid;
            }
        }

        $onlineMap = PresenceService::onlineMap();
        $lastLoginMap = PresenceService::lastLoginMap($userIds);
        $lastActivityMap = PresenceService::lastActivityMap($userIds);
        foreach ($onlineMap as $onlineUserId => $onlineActivityTs) {
            $lastActivityMap[$onlineUserId] = max(
                (int) ($lastActivityMap[$onlineUserId] ?? 0),
                (int) $onlineActivityTs
            );
        }
        $clientMap = PresenceService::clientMapForUsers($userIds);

        $tableRows = [];
        foreach ($items as $item) {
            $userId = (int) ($item['id'] ?? 0);
            $email = (string) ($item['user_email'] ?? '');
            $roles = is_array($item['roles'] ?? null) ? $item['roles'] : [];

            $isCustomer = false;
            foreach ($roles as $roleSlug) {
                if (strtolower(trim((string) $roleSlug)) === 'customer') {
                    $isCustomer = true;
                    break;
                }
            }
            $rowCanLoginAs = $canLoginAs && $userId > 0 && $userId !== $currentUserId && !$isCustomer;

            $setPermissionRole = '';
            if ($canManageGroupPermissions) {
                foreach ($roles as $roleSlug) {
                    $roleSlug = (string) $roleSlug;
                    if (
                        GroupPermissionsPresenter::isManageableRole($roleSlug)
                        && !GroupPermissionsPresenter::roleHasAccess($roleSlug)
                    ) {
                        $setPermissionRole = $roleSlug;
                        break;
                    }
                }
            }

            $isOnline = isset($onlineMap[$userId]);
            $lastLoginTs = (int) ($lastLoginMap[$userId] ?? 0);
            $lastActivityTs = (int) ($lastActivityMap[$userId] ?? 0);
            $client = is_array($clientMap[$userId] ?? null) ? $clientMap[$userId] : [];
            $deviceFields = self::deviceFields(
                (string) ($client['device'] ?? ''),
                (string) ($client['user_agent'] ?? '')
            );

            $tableRows[] = [
                'id' => $userId,
                'avatar_url' => self::avatarUrl($email, 64),
                'user_login' => (string) ($item['user_login'] ?? ''),
                'display_name' => (string) ($item['display_name'] ?? ''),
                'user_email' => $email,
                'is_online' => $isOnline,
                'last_activity_at' => $lastActivityTs > 0 ? self::formatActivityDatetime((string) $lastActivityTs) : '',
                'last_login_at' => $lastLoginTs > 0 ? self::formatActivityDatetime((string) $lastLoginTs) : '',
                'last_login_ts' => $lastLoginTs,
                'device' => $deviceFields['device'],
                'device_icon' => $deviceFields['device_icon'],
                'browser' => $deviceFields['browser'],
                'browser_icon' => $deviceFields['browser_icon'],
                'user_agent' => $deviceFields['user_agent'],
                'role_label' => self::roleLabel($roles),
                'registered_at' => self::formatDatetime($item['user_registered'] ?? ''),
                'can_login_as' => $rowCanLoginAs,
                'login_as_url' => ($rowCanLoginAs && $adminBase !== '') ? self::loginAsUrl($adminBase, $userId) : '',
                'needs_permissions' => $setPermissionRole !== '',
                'set_permissions_url' => ($setPermissionRole !== '' && $adminBase !== '')
                    ? rtrim($adminBase, '/') . '/users/group-permissions?role=' . rawurlencode($setPermissionRole)
                    : '',
            ];
        }

        $totalPagesInt = $totalPages;
        $currentPage = (int) $request['page'];
        $pagination = [
            'show' => !$request['is_all'] && $totalPagesInt > 1,
            'pages' => self::paginationWindow($currentPage, $totalPagesInt),
            'prev_url' => $currentPage > 1 ? $url(['page' => $currentPage - 1]) : '',
            'next_url' => $currentPage < $totalPagesInt ? $url(['page' => $currentPage + 1]) : '',
        ];
        $paginationLinks = [];
        foreach ($pagination['pages'] as $pageNum) {
            if ($pageNum === '…') {
                $paginationLinks[] = ['type' => 'ellipsis'];
                continue;
            }
            $num = (int) $pageNum;
            $paginationLinks[] = [
                'type' => $num === $currentPage ? 'current' : 'link',
                'label' => (string) $num,
                'url' => $url(['page' => $num]),
            ];
        }

        return [
            'redirect_url' => null,
            'request' => $request,
            'admin_base' => $adminBase,
            'app_base' => $appBase,
            'current_page' => $currentPage,
            'is_all' => $request['is_all'],
            'online_only' => !empty($request['online']),
            'has_active_filters' => $request['q'] !== '' || !empty($request['online']),
            'form_action' => self::routeSlug(),
            'tabs' => $tabs,
            'clear_filters_url' => $url(['q' => '', 'online' => 0, 'page' => 1]),
            'show_per_page_hidden' => (string) $request['per_page'] === 'all' || (int) $request['per_page'] !== self::DEFAULT_PER_PAGE,
            'per_page_options' => self::PER_PAGE_OPTIONS,
            'filter_hidden_html' => self::filterHiddenHtml($request),
            'role_options' => self::ROLE_OPTIONS,
            'can_login_as' => $canLoginAs,
            'can_manage_group_permissions' => $canManageGroupPermissions,
            'active_role_needs_permissions' => $activeRoleNeedsPermissions,
            'active_role_set_permissions_url' => $activeRoleSetPermissionsUrl,
            'active_role_label' => $activeRole !== '' ? self::roleSlugLabel($activeRole) : '',
            'table_rows' => $tableRows,
            'has_table_rows' => $tableRows !== [],
            'presence_api_url' => $adminBase !== ''
                ? rtrim($adminBase, '/') . '/api.php?module=users&action=presence'
                : 'api.php?module=users&action=presence',
            'is_switched' => ImpersonationService::isSwitched(),
            'switch_back_url' => $adminBase !== '' ? self::switchBackUrl($adminBase) : '',
            'switch_from' => ImpersonationService::switchFrom(),
            'total' => $total,
            'all_count' => $allCount,
            'online_count' => count($onlineUserIds),
            'role_counts' => $roleCounts,
            'total_pages' => $totalPagesInt,
            'count_label' => $countLabel,
            'error' => $error,
            'pagination' => $pagination,
            'pagination_links' => $paginationLinks,
        ];
    }
}
