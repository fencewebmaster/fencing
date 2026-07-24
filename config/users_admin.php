<?php
/**
 * FC Admin — Users page (server-rendered, GET forms).
 * Lists WordPress `{prefix}users` with optional role from usermeta.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function fc_users_admin_route_slug(): string
{
    return 'users';
}

function fc_users_admin_page_title(): string
{
    return 'Users';
}

function fc_users_admin_list_path(string $adminBase): string
{
    return rtrim($adminBase, '/') . '/' . fc_users_admin_route_slug();
}

/**
 * @return list<int>
 */
function fc_users_admin_per_page_options(): array
{
    return [30, 50, 100, 250, 500];
}

function fc_users_admin_default_per_page(): int
{
    return 30;
}

function fc_users_admin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fc_users_admin_cell(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return fc_users_admin_h((string) $value);
}

/**
 * Gravatar URL (same default WordPress uses).
 */
function fc_users_admin_avatar_url(string $email, int $size = 64): string
{
    $email = strtolower(trim($email));
    $hash = $email !== '' ? md5($email) : md5('unknown@example.com');
    $size = max(24, min(256, $size));

    return 'https://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=mp&r=g';
}

/**
 * Login As URL with CSRF token.
 */
function fc_users_admin_login_as_url(string $adminBase, int $userId): string
{
    $token = function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '';

    return rtrim($adminBase, '/') . '/users/login-as/' . $userId
        . '?_token=' . rawurlencode($token);
}

function fc_users_admin_switch_back_url(string $adminBase): string
{
    $token = function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '';

    return rtrim($adminBase, '/') . '/users/switch-back'
        . '?_token=' . rawurlencode($token);
}

function fc_users_admin_format_datetime($value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    try {
        $dt = new DateTime($value);
    } catch (Exception $e) {
        return $value;
    }

    $format = 'M. j, Y h:i A';
    if (!function_exists('fc_system_date_format_php')) {
        require_once __DIR__ . '/system.php';
    }
    if (function_exists('fc_system_date_format_php')) {
        $format = fc_system_date_format_php();
    }

    return $dt->format($format);
}

/**
 * @return list<int|string>
 */
function fc_users_admin_pagination_window(int $current, int $total): array
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
 * @return array{
 *   q:string,
 *   role:string,
 *   page:int,
 *   per_page:int|string,
 *   is_all:bool,
 *   offset:int
 * }
 */
function fc_users_admin_parse_request(): array
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $role = strtolower(trim((string) ($_GET['role'] ?? '')));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $defaultPerPage = fc_users_admin_default_per_page();
    $perPageRaw = strtolower(trim((string) ($_GET['per_page'] ?? (string) $defaultPerPage)));

    if ($role !== '' && !fc_users_admin_is_valid_role_slug($role)) {
        $role = '';
    }

    $isAll = ($perPageRaw === 'all');
    $perPage = $defaultPerPage;

    if ($isAll) {
        $perPage = 0;
    } elseif (in_array((int) $perPageRaw, fc_users_admin_per_page_options(), true)) {
        $perPage = (int) $perPageRaw;
    }

    $offset = $isAll ? 0 : ($page - 1) * $perPage;

    return [
        'q' => $q,
        'role' => $role,
        'page' => $page,
        'per_page' => $isAll ? 'all' : $perPage,
        'is_all' => $isAll,
        'offset' => $offset,
    ];
}

/**
 * @return array<string, string>
 */
function fc_users_admin_role_options(): array
{
    return [
        'administrator' => 'Administrator',
        'editor' => 'Editor',
        'author' => 'Author',
        'contributor' => 'Contributor',
        'subscriber' => 'Subscriber',
        'customer' => 'Customer',
        'shop_manager' => 'Shop manager',
    ];
}

/**
 * Human label for a WP role slug.
 */
function fc_users_admin_role_slug_label(string $role): string
{
    $role = strtolower(trim($role));
    if ($role === '') {
        return '';
    }

    $options = fc_users_admin_role_options();

    return $options[$role] ?? ucwords(str_replace(['_', '-'], ' ', $role));
}

/**
 * Whether a role query value is a safe WP role slug.
 */
function fc_users_admin_is_valid_role_slug(string $role): bool
{
    return $role !== '' && (bool) preg_match('/^[a-z0-9_-]+$/', $role);
}

/**
 * Count users per role (users with multiple roles count in each).
 *
 * @return array{ok:bool,total:int,roles:array<string,int>,error:?string}
 */
function fc_users_admin_role_counts(): array
{
    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return ['ok' => false, 'total' => 0, 'roles' => [], 'error' => 'Could not connect to database.'];
    }

    $usersTable = fc_auth_users_table();
    $metaTable = fc_auth_usermeta_table();
    $capKey = fc_users_admin_capabilities_meta_key();

    $total = 0;
    $totalResult = $conn->query('SELECT COUNT(*) AS c FROM `' . $conn->real_escape_string($usersTable) . '`');
    if ($totalResult) {
        $totalRow = $totalResult->fetch_assoc();
        $total = (int) ($totalRow['c'] ?? 0);
        $totalResult->free();
    }

    $sql = "SELECT um.meta_value AS capabilities
        FROM `{$usersTable}` u
        INNER JOIN `{$metaTable}` um
            ON um.user_id = u.ID AND um.meta_key = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error = $conn->error ?: 'Failed to prepare role counts query.';
        $conn->close();

        return ['ok' => false, 'total' => $total, 'roles' => [], 'error' => $error];
    }

    $stmt->bind_param('s', $capKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $roles = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            foreach (fc_users_admin_roles_from_caps($row['capabilities'] ?? null) as $role) {
                if (!isset($roles[$role])) {
                    $roles[$role] = 0;
                }
                $roles[$role]++;
            }
        }
    }

    $stmt->close();
    $conn->close();

    return ['ok' => true, 'total' => $total, 'roles' => $roles, 'error' => null];
}

/**
 * Ordered role tabs: known roles first (with count > 0), then other roles A–Z.
 *
 * @param array<string, int> $roleCounts
 * @return list<array{key:string,label:string,count:int}>
 */
function fc_users_admin_build_role_tabs(array $roleCounts): array
{
    $tabs = [];
    $seen = [];

    foreach (fc_users_admin_role_options() as $slug => $label) {
        $count = (int) ($roleCounts[$slug] ?? 0);
        if ($count <= 0) {
            continue;
        }
        $tabs[] = [
            'key' => $slug,
            'label' => $label,
            'count' => $count,
        ];
        $seen[$slug] = true;
    }

    $extra = [];
    foreach ($roleCounts as $slug => $count) {
        $slug = strtolower(trim((string) $slug));
        $count = (int) $count;
        if ($slug === '' || $count <= 0 || isset($seen[$slug])) {
            continue;
        }
        $extra[] = [
            'key' => $slug,
            'label' => fc_users_admin_role_slug_label($slug),
            'count' => $count,
        ];
    }

    usort($extra, static function (array $a, array $b): int {
        return strcasecmp((string) $a['label'], (string) $b['label']);
    });

    return array_merge($tabs, $extra);
}

/**
 * @param array<string, mixed> $request
 * @return array<string, mixed>
 */
function fc_users_admin_query_from_request(array $request): array
{
    $query = [];
    $q = trim((string) ($request['q'] ?? ''));
    $role = trim((string) ($request['role'] ?? ''));
    $page = (int) ($request['page'] ?? 1);
    $perPage = $request['per_page'] ?? fc_users_admin_default_per_page();

    if ($q !== '') {
        $query['q'] = $q;
    }
    if ($role !== '') {
        $query['role'] = $role;
    }
    if ($page > 1) {
        $query['page'] = $page;
    }
    if ($perPage === 'all' || (is_int($perPage) && $perPage !== fc_users_admin_default_per_page())) {
        $query['per_page'] = $perPage;
    }

    return $query;
}

/**
 * @param array<string, mixed> $query
 */
function fc_users_admin_build_query_string(array $query): string
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
 * @param array<string, mixed> $overrides
 */
function fc_users_admin_url(string $adminBase, array $overrides = []): string
{
    $base = fc_users_admin_list_path($adminBase);
    $query = array_merge(
        fc_users_admin_query_from_request(fc_users_admin_parse_request()),
        $overrides
    );

    foreach (['q', 'role', 'page', 'per_page'] as $key) {
        if (array_key_exists($key, $overrides) && ($overrides[$key] === '' || $overrides[$key] === null)) {
            unset($query[$key]);
        }
    }

    $qs = fc_users_admin_build_query_string($query);

    return $qs === '' ? $base : $base . '?' . $qs;
}

/**
 * @param array<string, mixed> $req
 */
function fc_users_admin_filter_hidden_html(array $req): string
{
    $html = '';
    $q = trim((string) ($req['q'] ?? ''));
    $role = trim((string) ($req['role'] ?? ''));

    if ($q !== '') {
        $html .= '<input type="hidden" name="q" value="' . fc_users_admin_h($q) . '">';
    }
    if ($role !== '') {
        $html .= '<input type="hidden" name="role" value="' . fc_users_admin_h($role) . '">';
    }

    return $html;
}

function fc_users_admin_capabilities_meta_key(): string
{
    $cfg = fc_db_resolve_config();
    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($cfg['prefix'] ?? 'wp_')) ?: 'wp_';

    return $prefix . 'capabilities';
}

/**
 * @param mixed $capsRaw
 * @return list<string>
 */
function fc_users_admin_roles_from_caps($capsRaw): array
{
    if ($capsRaw === null || $capsRaw === '') {
        return [];
    }

    $caps = @unserialize((string) $capsRaw);
    if (!is_array($caps)) {
        return [];
    }

    $roles = [];
    foreach ($caps as $role => $enabled) {
        if (!$enabled) {
            continue;
        }
        $role = strtolower(trim((string) $role));
        if ($role === '') {
            continue;
        }
        $roles[] = $role;
    }

    return $roles;
}

/**
 * @param list<string> $roles
 */
function fc_users_admin_role_label(array $roles): string
{
    if ($roles === []) {
        return '';
    }

    $labels = [];
    foreach ($roles as $role) {
        $labels[] = fc_users_admin_role_slug_label($role);
    }

    return implode(', ', $labels);
}

/**
 * @return array{ok:bool,items:list<array<string,mixed>>,total:int,error:?string}
 */
function fc_users_admin_list_users(string $q = '', string $role = '', int $limit = 30, int $offset = 0): array
{
    $conn = fc_auth_db();
    if (!$conn instanceof mysqli) {
        return ['ok' => false, 'items' => [], 'total' => 0, 'error' => 'Could not connect to database.'];
    }

    $usersTable = fc_auth_users_table();
    $metaTable = fc_auth_usermeta_table();
    $capKey = fc_users_admin_capabilities_meta_key();

    $where = ['1=1'];
    $types = '';
    $params = [];

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(u.user_login LIKE ? OR u.user_email LIKE ? OR u.display_name LIKE ? OR CAST(u.ID AS CHAR) LIKE ?)';
        $types .= 'ssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($role !== '') {
        // Serialized WP caps look like: a:1:{s:13:"administrator";b:1;}
        $roleNeedle = '%"' . $role . '";b:1%';
        $where[] = 'um.meta_value LIKE ?';
        $types .= 's';
        $params[] = $roleNeedle;
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(DISTINCT u.ID) AS c
        FROM `{$usersTable}` u
        LEFT JOIN `{$metaTable}` um
            ON um.user_id = u.ID AND um.meta_key = ?
        WHERE {$whereSql}";

    $countTypes = 's' . $types;
    $countParams = array_merge([$capKey], $params);

    $total = 0;
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        $error = $conn->error ?: 'Failed to prepare user count query.';
        $conn->close();

        return ['ok' => false, 'items' => [], 'total' => 0, 'error' => $error];
    }

    $countStmt->bind_param($countTypes, ...$countParams);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult ? $countResult->fetch_assoc() : null;
    $countStmt->close();
    $total = (int) ($countRow['c'] ?? 0);

    $sql = "SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered, um.meta_value AS capabilities
        FROM `{$usersTable}` u
        LEFT JOIN `{$metaTable}` um
            ON um.user_id = u.ID AND um.meta_key = ?
        WHERE {$whereSql}
        ORDER BY u.ID DESC";

    $listTypes = 's' . $types;
    $listParams = array_merge([$capKey], $params);

    if ($limit > 0) {
        $sql .= ' LIMIT ? OFFSET ?';
        $listTypes .= 'ii';
        $listParams[] = $limit;
        $listParams[] = max(0, $offset);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error = $conn->error ?: 'Failed to prepare users query.';
        $conn->close();

        return ['ok' => false, 'items' => [], 'total' => $total, 'error' => $error];
    }

    $stmt->bind_param($listTypes, ...$listParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!is_array($row)) {
                continue;
            }
            $roles = fc_users_admin_roles_from_caps($row['capabilities'] ?? null);
            $items[] = [
                'id' => (int) ($row['ID'] ?? 0),
                'user_login' => (string) ($row['user_login'] ?? ''),
                'user_email' => (string) ($row['user_email'] ?? ''),
                'display_name' => (string) ($row['display_name'] ?? ''),
                'user_registered' => (string) ($row['user_registered'] ?? ''),
                'roles' => $roles,
                'role_label' => fc_users_admin_role_label($roles),
            ];
        }
    }

    $stmt->close();
    $conn->close();

    return ['ok' => true, 'items' => $items, 'total' => $total, 'error' => null];
}

/**
 * @return array<string, mixed>
 */
function fc_users_admin_page_data(string $adminBase, string $appBase): array
{
    $request = fc_users_admin_parse_request();
    $limit = $request['is_all'] ? 0 : (int) $request['per_page'];

    $list = fc_users_admin_list_users(
        (string) $request['q'],
        (string) $request['role'],
        $limit,
        (int) $request['offset']
    );

    $error = '';
    if (empty($list['ok'])) {
        $error = (string) ($list['error'] ?? 'Could not load users.');
        $list = ['ok' => false, 'items' => [], 'total' => 0];
    }

    $allCount = $total = (int) ($list['total'] ?? 0);
    $roleCountsPayload = fc_users_admin_role_counts();
    $roleCounts = is_array($roleCountsPayload['roles'] ?? null) ? $roleCountsPayload['roles'] : [];
    if (!empty($roleCountsPayload['ok'])) {
        $allCount = (int) ($roleCountsPayload['total'] ?? $allCount);
    } elseif (($request['q'] ?? '') !== '' || ($request['role'] ?? '') !== '') {
        $allList = fc_users_admin_list_users('', '', 1, 0);
        if (!empty($allList['ok'])) {
            $allCount = (int) ($allList['total'] ?? $allCount);
        }
    }

    $items = is_array($list['items'] ?? null) ? $list['items'] : [];
    $total = (int) ($list['total'] ?? count($items));
    $perPage = $request['is_all'] ? max(1, count($items)) : max(1, (int) $request['per_page']);
    $totalPages = $request['is_all'] ? 1 : max(1, (int) ceil($total / $perPage));

    if ($request['page'] > $totalPages && $totalPages > 0) {
        header('Location: ' . fc_users_admin_url($adminBase, ['page' => $totalPages]));
        exit;
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

    return [
        'request' => $request,
        'list' => $list,
        'items' => $items,
        'total' => $total,
        'all_count' => $allCount,
        'role_counts' => $roleCounts,
        'total_pages' => $totalPages,
        'count_label' => $countLabel,
        'error' => $error,
        'admin_base' => $adminBase,
        'app_base' => rtrim($appBase, '/'),
        'role_options' => fc_users_admin_role_options(),
        'url' => static function (array $overrides = []) use ($adminBase): string {
            return fc_users_admin_url($adminBase, $overrides);
        },
    ];
}

/**
 * @param array<string, mixed> $page
 * @return array<string, mixed>
 */
function fc_users_admin_build_list_view(array $page): array
{
    $req = is_array($page['request'] ?? null) ? $page['request'] : [];
    $items = is_array($page['items'] ?? null) ? $page['items'] : [];
    $url = $page['url'] ?? null;
    $total = (int) ($page['total'] ?? 0);

    $page['current_page'] = (int) ($req['page'] ?? 1);
    $page['is_all'] = !empty($req['is_all']);
    $page['has_active_filters'] = ($req['q'] ?? '') !== '';
    $page['form_action'] = fc_users_admin_route_slug();

    $activeRole = trim((string) ($req['role'] ?? ''));
    $roleCounts = is_array($page['role_counts'] ?? null) ? $page['role_counts'] : [];
    $tabs = [
        [
            'key' => 'all',
            'label' => 'All',
            'count' => (int) ($page['all_count'] ?? $total),
            'is_active' => $activeRole === '',
            'href' => is_callable($url)
                ? (string) $url(['role' => '', 'page' => 1])
                : '?',
        ],
    ];
    foreach (fc_users_admin_build_role_tabs($roleCounts) as $roleTab) {
        $slug = (string) ($roleTab['key'] ?? '');
        if ($slug === '') {
            continue;
        }
        $tabs[] = [
            'key' => $slug,
            'label' => (string) ($roleTab['label'] ?? fc_users_admin_role_slug_label($slug)),
            'count' => (int) ($roleTab['count'] ?? 0),
            'is_active' => $activeRole === $slug,
            'href' => is_callable($url)
                ? (string) $url(['role' => $slug, 'page' => 1])
                : '?role=' . rawurlencode($slug),
        ];
    }
    $page['tabs'] = $tabs;

    $page['clear_filters_url'] = is_callable($url)
        ? (string) $url([
            'q' => '',
            'page' => 1,
        ])
        : '';
    $page['show_per_page_hidden'] = (string) ($req['per_page'] ?? '') === 'all'
        || (int) ($req['per_page'] ?? 0) !== fc_users_admin_default_per_page();
    $page['per_page_options'] = fc_users_admin_per_page_options();
    $page['filter_hidden_html'] = fc_users_admin_filter_hidden_html($req);
    $page['role_options'] = is_array($page['role_options'] ?? null)
        ? $page['role_options']
        : fc_users_admin_role_options();

    $page['can_login_as'] = function_exists('fc_auth_user_can')
        ? fc_auth_user_can('users.login_as')
        : true;
    $page['can_manage_group_permissions'] = function_exists('fc_auth_user_can')
        ? fc_auth_user_can('users.group_permissions')
        : true;

    if (!function_exists('fc_group_permissions_role_has_access')) {
        require_once __DIR__ . '/permissions.php';
    }

    $adminBase = (string) ($page['admin_base'] ?? '');
    $activeRoleNeedsPermissions = false;
    $activeRoleSetPermissionsUrl = '';
    if (
        !empty($page['can_manage_group_permissions'])
        && $activeRole !== ''
        && function_exists('fc_group_permissions_is_manageable_role')
        && fc_group_permissions_is_manageable_role($activeRole)
        && !fc_group_permissions_role_has_access($activeRole)
    ) {
        $activeRoleNeedsPermissions = true;
        $activeRoleSetPermissionsUrl = rtrim($adminBase, '/') . '/users/group-permissions?role=' . rawurlencode($activeRole);
    }
    $page['active_role_needs_permissions'] = $activeRoleNeedsPermissions;
    $page['active_role_set_permissions_url'] = $activeRoleSetPermissionsUrl;
    $page['active_role_label'] = $activeRole !== ''
        ? fc_users_admin_role_slug_label($activeRole)
        : '';

    $tableRows = [];
    $currentUserId = 0;
    if (function_exists('fc_auth_user')) {
        $authUser = fc_auth_user();
        $currentUserId = is_array($authUser) ? (int) ($authUser['id'] ?? 0) : 0;
    }

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
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
        $canLoginAs = !empty($page['can_login_as'])
            && $userId > 0
            && $userId !== $currentUserId
            && !$isCustomer;
        $setPermissionRole = '';
        if (!empty($page['can_manage_group_permissions'])) {
            foreach ($roles as $roleSlug) {
                $roleSlug = (string) $roleSlug;
                if (
                    function_exists('fc_group_permissions_is_manageable_role')
                    && fc_group_permissions_is_manageable_role($roleSlug)
                    && !fc_group_permissions_role_has_access($roleSlug)
                ) {
                    $setPermissionRole = $roleSlug;
                    break;
                }
            }
        }
        $tableRows[] = [
            'id' => $userId,
            'avatar_url' => fc_users_admin_avatar_url($email, 64),
            'user_login' => (string) ($item['user_login'] ?? ''),
            'display_name' => (string) ($item['display_name'] ?? ''),
            'user_email' => $email,
            'role_label' => (string) ($item['role_label'] ?? ''),
            'registered_at' => fc_users_admin_format_datetime($item['user_registered'] ?? ''),
            'can_login_as' => $canLoginAs,
            'login_as_url' => ($canLoginAs && $adminBase !== '')
                ? fc_users_admin_login_as_url($adminBase, $userId)
                : '',
            'needs_permissions' => $setPermissionRole !== '',
            'set_permissions_url' => ($setPermissionRole !== '' && $adminBase !== '')
                ? rtrim($adminBase, '/') . '/users/group-permissions?role=' . rawurlencode($setPermissionRole)
                : '',
        ];
    }
    $page['table_rows'] = $tableRows;
    $page['has_table_rows'] = $tableRows !== [];
    $page['is_switched'] = function_exists('fc_auth_is_switched') && fc_auth_is_switched();
    $page['switch_back_url'] = $adminBase !== '' ? fc_users_admin_switch_back_url($adminBase) : '';
    $page['switch_from'] = function_exists('fc_auth_switch_from') ? fc_auth_switch_from() : null;

    $totalPages = (int) ($page['total_pages'] ?? 1);
    $currentPage = (int) ($req['page'] ?? 1);
    $pagination = [
        'show' => !$page['is_all'] && $totalPages > 1,
        'pages' => fc_users_admin_pagination_window($currentPage, $totalPages),
        'prev_url' => ($currentPage > 1 && is_callable($url)) ? (string) $url(['page' => $currentPage - 1]) : '',
        'next_url' => ($currentPage < $totalPages && is_callable($url)) ? (string) $url(['page' => $currentPage + 1]) : '',
    ];
    $page['pagination'] = $pagination;
    $page['pagination_links'] = [];
    foreach ($pagination['pages'] as $pageNum) {
        if ($pageNum === '…') {
            $page['pagination_links'][] = ['type' => 'ellipsis'];
            continue;
        }
        $num = (int) $pageNum;
        $page['pagination_links'][] = [
            'type' => $num === $currentPage ? 'current' : 'link',
            'label' => (string) $num,
            'url' => is_callable($url) ? (string) $url(['page' => $num]) : '',
        ];
    }

    return $page;
}
