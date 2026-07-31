<?php
/**
 * FC planners table — admin entries list (wp_planners).
 */

declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Full row columns (detail view).
 *
 * @return list<string>
 */
function fc_planners_entry_columns(): array
{
    return [
        'id',
        'planner_id',
        'site_id',
        'site_url',
        'status',
        'section_count',
        'notes',
        'name',
        'mobile',
        'email',
        'address',
        'postcode',
        'state',
        'fence_type',
        'timeframe',
        'extra',
        'ip_address',
        'device',
        'user_agent',
        'quote_load_count',
        'created_at',
        'updated_at',
    ];
}

/**
 * Columns used for portable JSON import/export (full quote restore).
 *
 * @return list<string>
 */
function fc_planners_export_columns(): array
{
    return [
        'planner_id',
        'site_id',
        'site_url',
        'order_id',
        'status',
        'status_updated_at',
        'section_count',
        'notes',
        'name',
        'mobile',
        'email',
        'address',
        'postcode',
        'state',
        'fence_type',
        'timeframe',
        'extra',
        'color_data',
        'products_data',
        'fence_data',
        'cart_data',
        'cart_items_data',
        'project_plans_data',
        'ip_address',
        'device',
        'user_agent',
        'quote_load_count',
        'created_at',
        'updated_at',
        'trashed_at',
    ];
}

/**
 * Lightweight columns for list/grid (no LONGTEXT blobs).
 *
 * @return list<string>
 */
function fc_planners_list_columns(): array
{
    return [
        'id',
        'planner_id',
        'status',
        'name',
        'email',
        'mobile',
        'fence_type',
        'timeframe',
        'section_count',
        'quote_load_count',
        'state',
        'device',
        'user_agent',
        'created_at',
        'updated_at',
    ];
}

/**
 * @return bool
 */
function fc_planners_list_is_all_limit(int $limit): bool
{
    return $limit <= 0;
}

/**
 * @return bool
 */
function fc_planners_list_is_cacheable(
    string $search,
    string $status,
    int $offset,
    bool $isAll = false,
    string $timeframe = '',
    string $state = '',
    array $fenceTypes = []
): bool {
    return false;
}

/**
 * Create wp_planners when missing (local setup).
 */
function fc_planners_ensure_table(mysqli $conn, string $table): bool
{
    $safe = $conn->real_escape_string($table);
    $check = $conn->query("SHOW TABLES LIKE '{$safe}'");
    if ($check && $check->num_rows > 0) {
        return true;
    }

    $schemaFile = dirname(__DIR__) . '/data/schema/wp_planners.sql';
    if (!is_readable($schemaFile)) {
        return false;
    }

    $sql = (string) file_get_contents($schemaFile);
    if ($sql === '') {
        return false;
    }

    if (!$conn->multi_query($sql)) {
        return false;
    }

    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    $verify = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $verify && $verify->num_rows > 0;
}

/**
 * Add client metadata columns on existing wp_planners tables.
 */
function fc_planners_ensure_columns(mysqli $conn, string $table): void
{
    $safe = $conn->real_escape_string($table);
    $check = $conn->query("SHOW TABLES LIKE '{$safe}'");
    if (!$check || $check->num_rows === 0) {
        return;
    }

    $columns = [
        'ip_address' => 'varchar(45) DEFAULT NULL',
        'device' => 'varchar(32) DEFAULT NULL',
        'user_agent' => 'varchar(512) DEFAULT NULL',
        'quote_load_count' => 'int unsigned NOT NULL DEFAULT 0',
        'trashed_at' => 'datetime DEFAULT NULL',
    ];

    foreach ($columns as $column => $definition) {
        $colCheck = $conn->query("SHOW COLUMNS FROM `{$safe}` LIKE '{$column}'");
        if ($colCheck && $colCheck->num_rows > 0) {
            continue;
        }
        $conn->query("ALTER TABLE `{$safe}` ADD COLUMN `{$column}` {$definition}");
    }

    fc_planners_ensure_indexes($conn, $table);
}

/**
 * Add list/filter indexes used by planner-entries (idempotent).
 *
 * Avoid UNIQUE(planner_id) while duplicate planner_ids may still exist.
 */
function fc_planners_ensure_indexes(mysqli $conn, string $table): void
{
    $safe = $conn->real_escape_string($table);
    $existing = [];
    $idxResult = $conn->query('SHOW INDEX FROM `' . $safe . '`');
    if ($idxResult) {
        while ($row = $idxResult->fetch_assoc()) {
            $name = (string) ($row['Key_name'] ?? '');
            if ($name !== '') {
                $existing[$name] = true;
            }
        }
    }

    $indexes = [
        'planner_id' => 'ADD KEY `planner_id` (`planner_id`)',
        'status' => 'ADD KEY `status` (`status`)',
        'trashed_at' => 'ADD KEY `trashed_at` (`trashed_at`)',
        'idx_list_active_created' => 'ADD KEY `idx_list_active_created` (`trashed_at`, `status`, `created_at`, `id`)',
        'idx_trash_status' => 'ADD KEY `idx_trash_status` (`trashed_at`, `status`)',
        'idx_list_updated' => 'ADD KEY `idx_list_updated` (`trashed_at`, `updated_at`, `id`)',
        'idx_created_at' => 'ADD KEY `idx_created_at` (`created_at`, `id`)',
        'idx_email' => 'ADD KEY `idx_email` (`email`)',
        'idx_email_cover' => 'ADD KEY `idx_email_cover` (`email`, `id`, `created_at`)',
        'idx_state' => 'ADD KEY `idx_state` (`state`)',
        'idx_device' => 'ADD KEY `idx_device` (`device`)',
    ];

    foreach ($indexes as $name => $ddl) {
        if (isset($existing[$name])) {
            continue;
        }
        // Ignore failures (e.g. race with another request adding the same key).
        @$conn->query('ALTER TABLE `' . $safe . '` ' . $ddl);
    }
}

/**
 * Best-effort client IP (supports common proxy headers).
 */
function fc_planner_client_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['HTTP_X_REAL_IP'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($candidates as $raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            continue;
        }

        foreach (explode(',', $raw) as $part) {
            $ip = trim($part);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '';
}

/**
 * Classify device type from a user-agent string.
 */
function fc_planner_client_device(?string $userAgent = null): string
{
    $ua = strtolower(trim((string) ($userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''))));
    if ($ua === '') {
        return 'Unknown';
    }
    if (preg_match('/bot|crawl|spider|slurp|mediapartners/i', $ua)) {
        return 'Bot';
    }
    if (preg_match('/tablet|ipad|playbook|silk|(android(?!.*mobile))/i', $ua)) {
        return 'Tablet';
    }
    if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua)) {
        return 'Mobile';
    }

    return 'Desktop';
}

/**
 * Truncated HTTP user-agent for storage.
 */
function fc_planner_client_user_agent(): string
{
    $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '') {
        return '';
    }
    if (mb_strlen($ua) > 512) {
        return mb_substr($ua, 0, 512);
    }

    return $ua;
}

/**
 * Client metadata captured on planner submission.
 *
 * @return array{ip_address:string,device:string,user_agent:string}
 */
function fc_planner_submission_meta(): array
{
    $userAgent = fc_planner_client_user_agent();

    return [
        'ip_address' => fc_planner_client_ip(),
        'device' => fc_planner_client_device($userAgent),
        'user_agent' => $userAgent,
    ];
}

/**
 * Normalize other-items value for the planners `extra` DB column (JSON array string).
 */
function fc_planners_extra_for_db($extra, $nothing_extra = null): string
{
    if ($nothing_extra === 'nothing' || $extra === 'nothing') {
        return '[]';
    }

    if ($extra === null || $extra === '') {
        return '[]';
    }

    if (is_array($extra)) {
        $items = array_values(array_filter(array_map(static function ($part): string {
            return trim((string) $part);
        }, $extra)));

        return json_encode($items, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    $text = trim((string) $extra);
    if ($text === '' || $text === 'nothing' || $text === '[]') {
        return '[]';
    }

    if ($text[0] === '[') {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return fc_planners_extra_for_db($decoded);
        }
    }

    if (str_contains($text, ',')) {
        $parts = array_values(array_filter(array_map('trim', explode(',', $text))));

        return json_encode($parts, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    return json_encode(array($text), JSON_UNESCAPED_UNICODE) ?: '[]';
}

/**
 * Build planners table payload from the current PHP session.
 *
 * @return array<string, mixed>|null
 */
function fc_planner_build_data_inputs_from_session(array $fences, string $plannerId): ?array
{
    $plannerId = trim($plannerId);
    if ($plannerId === '') {
        return null;
    }

    $fc_data = isset($_SESSION['fc_data']) && is_array($_SESSION['fc_data']) ? $_SESSION['fc_data'] : null;
    if ($fc_data === null || $fc_data === []) {
        return null;
    }

    $fc_products = $_SESSION['custom_fence_products'] ?? [];
    $fc_cart = isset($_SESSION['fc_cart']) && is_array($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];
    $fc_site = isset($_SESSION['site']) && is_array($_SESSION['site']) ? $_SESSION['site'] : [];

    $fences_raw = $fc_data['fences'] ?? '[]';
    $decoded_fences = is_array($fences_raw) ? $fences_raw : json_decode((string) $fences_raw, true);
    $section_count = is_array($decoded_fences) ? count($decoded_fences) : 0;

    $extra = fc_planners_extra_for_db(
        $fc_data['extra'] ?? null,
        isset($fc_data['nothing_extra']) ? (string) $fc_data['nothing_extra'] : null
    );

    return [
        'planner_id'         => $plannerId,
        'site_id'            => $fc_site['id'] ?? null,
        'site_url'           => $fc_site['url'] ?? null,
        'order_id'           => 0,
        'status'             => 'planning',
        'status_updated_at'  => date('Y-m-d H:i:s'),
        'section_count'      => $section_count,
        'notes'              => $fc_data['notes'] ?? null,
        'name'               => $fc_data['name'] ?? null,
        'mobile'             => fc_normalize_mobile_for_storage( $fc_data['mobile'] ?? '' ) ?: null,
        'email'              => $fc_data['email'] ?? null,
        'address'            => $fc_data['address'] ?? null,
        'postcode'           => $fc_data['postcode'] ?? null,
        'state'              => $fc_data['state'] ?? null,
        'fence_type'         => function_exists('selected_fences') ? selected_fences($fences, 'slug') : null,
        'timeframe'          => $fc_data['timeframe'] ?? null,
        'extra'              => $extra,
        'color_data'         => $fc_data['color'] ?? null,
        'products_data'      => $fc_products,
        'fence_data'         => $fc_data['fences'] ?? null,
        'cart_data'          => $fc_cart['items'] ?? [],
        'cart_items_data'    => $fc_data['cart_items'] ?? null,
        'project_plans_data' => $fc_data['project_plans'] ?? null,
        'updated_at'         => date('Y-m-d H:i:s'),
    ];
}

/**
 * Persist the active planner session row (project-plan / checkout edits).
 *
 * @return array{success:bool,skipped?:bool,message?:string}
 */
function fc_planner_persist_session(array $fences): array
{
    $planner_id = trim((string) ($_SESSION['planner_id'] ?? ''));
    if ($planner_id === '') {
        return ['success' => false, 'skipped' => true];
    }

    $data_inputs = fc_planner_build_data_inputs_from_session($fences, $planner_id);
    if ($data_inputs === null) {
        return ['success' => false, 'skipped' => true];
    }

    $db = new Database();
    $safe_id = str_replace('"', '""', $planner_id);
    $existing = $db->select_where('planners', '`planner_id`="' . $safe_id . '"', 'id');
    if (!$existing) {
        $data_inputs['created_at'] = date('Y-m-d H:i:s');
    }

    $result = $db->updateOrCreate('planners', $data_inputs, ['planner_id' => $planner_id]);

    if ( ! empty( $result['success'] ) && function_exists( 'fc_planner_client_project_plans_from_session' ) ) {
        $_SESSION['fc_data']['project_plans'] = fc_planner_client_project_plans_from_session();
    }

    return $result;
}

/**
 * Planner ids are get_uid() output — uppercase letters and digits only.
 */
function fc_planner_is_valid_planner_id(string $plannerId): bool
{
    return (bool) preg_match('/^[A-Za-z0-9]{4,64}$/', $plannerId);
}

/**
 * Whether the planners table exposes `trashed_at` (older tables predate the column).
 */
function fc_planner_planners_has_trashed_column(mysqli $conn, string $table): bool
{
    static $cache = [];

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $result = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE 'trashed_at'");
        $cache[$table] = (bool) ($result && $result->num_rows > 0);
    } catch (\mysqli_sql_exception $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

/**
 * Exact planner_id lookup (prepared, so all-digit ids match as strings).
 *
 * @return array{found:bool,trashed:bool}
 */
function fc_planner_planner_id_state(string $plannerId): array
{
    $state = ['found' => false, 'trashed' => false];

    $plannerId = trim($plannerId);
    if ($plannerId === '') {
        return $state;
    }

    $db = new Database();
    $conn = $db->connect();
    if (!$conn instanceof mysqli) {
        return $state;
    }

    $table = implode('_', array_filter([$db->prefix . 'planners', $db->is_demo]));
    $select = fc_planner_planners_has_trashed_column($conn, $table) ? '`id`, `trashed_at`' : '`id`';

    try {
        $stmt = $conn->prepare(
            'SELECT ' . $select . ' FROM `' . $table . '` WHERE `planner_id` = ? ORDER BY `id` DESC LIMIT 1'
        );
    } catch (\mysqli_sql_exception $e) {
        $conn->close();

        return $state;
    }

    if (!$stmt) {
        $conn->close();

        return $state;
    }

    $stmt->bind_param('s', $plannerId);

    try {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
    } catch (\mysqli_sql_exception $e) {
        $stmt->close();
        $conn->close();

        return $state;
    }

    $stmt->close();
    $conn->close();

    if (!is_array($row)) {
        return $state;
    }

    $state['found'] = true;
    $state['trashed'] = trim((string) ($row['trashed_at'] ?? '')) !== '';

    return $state;
}

/**
 * Mint a planner id that is not taken yet (get_uid() on its own can collide).
 */
function fc_planner_new_planner_id(): string
{
    if (!function_exists('get_uid')) {
        require_once __DIR__ . '/helpers.php';
    }

    for ($attempt = 0; $attempt < 12; $attempt++) {
        $candidate = get_uid(6);
        if (!fc_planner_planner_id_state($candidate)['found']) {
            return $candidate;
        }
    }

    return strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Decide which planner row a public save (submit.php / checkout.php) writes to.
 *
 * The session is authoritative. When it holds no id — session GC, dropped cookie, another tab —
 * fall back to the id the page rendered and posted back, accepted only when that quote still
 * exists and is not trashed, so a stale or guessed id cannot resurrect a deleted quote. Without
 * that fallback every lost session minted a fresh id and inserted a second row for one quote.
 *
 * @param mixed $postedPlannerId Untrusted planner_id from the request.
 * @param bool  $allowNew        False for order pushes, which must never create a new quote.
 * @return array{planner_id:string,exists:bool,source:string}
 */
function fc_planner_resolve_submission_planner_id($postedPlannerId = null, bool $allowNew = true): array
{
    $sessionId = trim((string) ($_SESSION['planner_id'] ?? ''));
    if ($sessionId !== '') {
        $state = fc_planner_planner_id_state($sessionId);
        if (!$state['trashed']) {
            return [
                'planner_id' => $sessionId,
                'exists' => $state['found'],
                'source' => 'session',
            ];
        }
    }

    $posted = is_scalar($postedPlannerId) ? trim((string) $postedPlannerId) : '';
    if ($posted !== '' && fc_planner_is_valid_planner_id($posted)) {
        $state = fc_planner_planner_id_state($posted);
        // A trashed quote must not be written to; anything else keeps the id the visitor sees,
        // so the Quote ID on screen and the stored row stay the same even after a failed save.
        if (!$state['trashed']) {
            $_SESSION['planner_id'] = $posted;

            return [
                'planner_id' => $posted,
                'exists' => $state['found'],
                'source' => 'request',
            ];
        }
    }

    if (!$allowNew) {
        return ['planner_id' => '', 'exists' => false, 'source' => 'none'];
    }

    $newId = fc_planner_new_planner_id();
    $_SESSION['planner_id'] = $newId;

    return ['planner_id' => $newId, 'exists' => false, 'source' => 'new'];
}

/**
 * Whether a planners row is soft-deleted (in trash).
 *
 * @param object|array|null $row
 */
function fc_planners_row_is_trashed($row): bool
{
    if (is_object($row)) {
        $trashedAt = $row->trashed_at ?? null;
    } elseif (is_array($row)) {
        $trashedAt = $row['trashed_at'] ?? null;
    } else {
        return false;
    }

    if ($trashedAt === null || $trashedAt === '') {
        return false;
    }

    return trim((string) $trashedAt) !== '';
}

/**
 * Increment how many times a saved quote was loaded via ?qid= or the Load Quote form.
 */
function fc_planners_increment_quote_load_count(string $plannerId): void
{
    $plannerId = trim($plannerId);
    if ($plannerId === '') {
        return;
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return;
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $sql = 'UPDATE `' . $table . '` SET `quote_load_count` = COALESCE(`quote_load_count`, 0) + 1 WHERE `planner_id` = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        fc_planners_close_db($conn);
        return;
    }

    $stmt->bind_param('s', $plannerId);
    $stmt->execute();
    $stmt->close();
    fc_planners_close_db($conn);
}

function fc_planners_db_error_message(?Database $db = null): string
{
    $technical = $db instanceof Database ? (string) $db->last_connect_error : '';
    return fc_db_connect_error_message($technical);
}

/**
 * @return array{db:Database,table:string,conn:mysqli}
 */
function fc_planners_open_db(): array
{
    // One mysqli handle per request — list/statuses/counts used to open 3 separate connections.
    static $ctx = null;
    if (is_array($ctx) && ($ctx['conn'] ?? null) instanceof mysqli) {
        return $ctx;
    }

    $db = new Database();
    $table = implode('_', array_filter([$db->prefix . 'planners', $db->is_demo]));
    $conn  = $db->connect();

    if (!$conn instanceof \mysqli) {
        throw new \RuntimeException(fc_planners_db_error_message($db));
    }

    static $ensured = [];
    if (!isset($ensured[$table])) {
        fc_planners_ensure_table($conn, $table);
        fc_planners_ensure_columns($conn, $table);
        $ensured[$table] = true;
    }

    $ctx = [
        'db' => $db,
        'table' => $table,
        'conn' => $conn,
    ];

    register_shutdown_function(static function () use (&$ctx): void {
        if (is_array($ctx) && ($ctx['conn'] ?? null) instanceof mysqli) {
            @$ctx['conn']->close();
            $ctx = null;
        }
    });

    return $ctx;
}

function fc_planners_close_db(mysqli $conn): void
{
    // Request-scoped connection is closed on shutdown — keep it open for reuse.
}

/**
 * @param list<string> $columns
 * @param object $row
 * @return array<string, mixed>
 */
function fc_planners_normalize_row(array $columns, object $row, bool $withInstaller = true): array
{
    $out = $withInstaller ? ['installer' => ''] : [];

    foreach ($columns as $column) {
        $value = $row->{$column} ?? null;
        if ($column === 'id' || $column === 'section_count') {
            $out[$column] = isset($row->{$column}) ? (int) $row->{$column} : 0;
            continue;
        }
        $out[$column] = $value === null ? '' : (string) $value;
    }

    return $out;
}

/**
 * @return array<string, array<string, mixed>>
 */
function fc_planners_fence_catalog(): array
{
    static $catalog = null;

    if ($catalog !== null) {
        return $catalog;
    }

    if (!function_exists('fc_normalize_planner_fence_slug')) {
        require_once __DIR__ . '/helpers.php';
    }

    $fences = [];
    foreach (glob(dirname(__DIR__) . '/data/fences/*.php') ?: [] as $fenceFile) {
        include $fenceFile;
    }

    $catalog = is_array($fences) ? $fences : [];

    return $catalog;
}

/**
 * @param mixed $raw
 * @return array<mixed>
 */
function fc_planners_decode_json_field($raw): array
{
    if (is_array($raw)) {
        return $raw;
    }

    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @param array<string, array<string, mixed>> $fences
 * @return list<array{slug:string,name:string,count:int}>
 */
function fc_planners_fence_section_types_from_rows(array $rows, array $fences): array
{
    if (!function_exists('fc_normalize_planner_fence_slug')) {
        require_once __DIR__ . '/helpers.php';
    }

    $order = [];
    $counts = [];

    foreach ($rows as $fence) {
        if (!is_array($fence) || empty($fence['form'][0]) || !is_array($fence['form'][0])) {
            continue;
        }

        $tab0 = $fence['form'][0];
        $raw = '';
        if (!empty($tab0['fence'])) {
            $raw = (string) $tab0['fence'];
        } elseif (!empty($tab0['style'])) {
            $raw = (string) $tab0['style'];
        }

        if ($raw === '') {
            continue;
        }

        $norm = fc_normalize_planner_fence_slug($raw);
        if (!isset($counts[$norm])) {
            $counts[$norm] = 0;
            $order[] = $norm;
        }
        $counts[$norm]++;
    }

    $out = [];
    foreach ($order as $norm) {
        $out[] = [
            'slug' => $norm,
            'name' => fc_fence_style_title_from_slug($norm, $fences),
            'count' => (int) $counts[$norm],
        ];
    }

    return $out;
}

/**
 * @param array<string, array<string, mixed>> $fences
 * @return list<array{slug:string,name:string,count:int}>
 */
function fc_planners_fence_section_types_from_type_map(string $fenceTypeRaw, array $fences): array
{
    if (!function_exists('fc_normalize_planner_fence_slug')) {
        require_once __DIR__ . '/helpers.php';
    }

    $map = fc_planners_decode_json_field($fenceTypeRaw);
    if ($map === []) {
        return [];
    }

    $order = [];
    $counts = [];

    foreach ($map as $slug => $value) {
        $raw = is_string($slug) && $slug !== '' ? $slug : (string) $value;
        if ($raw === '') {
            continue;
        }

        $norm = fc_normalize_planner_fence_slug($raw);
        if (!isset($counts[$norm])) {
            $counts[$norm] = 0;
            $order[] = $norm;
        }
        $counts[$norm]++;
    }

    $out = [];
    foreach ($order as $norm) {
        $out[] = [
            'slug' => $norm,
            'name' => fc_fence_style_title_from_slug($norm, $fences),
            'count' => (int) $counts[$norm],
        ];
    }

    return $out;
}

/**
 * @param list<array{slug:string,name:string,count:int}> $rows
 */
function fc_planners_format_fence_type_summary(array $rows): string
{
    if ($rows === []) {
        return '';
    }

    $parts = [];
    foreach ($rows as $row) {
        $count = (int) ($row['count'] ?? 0);
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '' || $count <= 0) {
            continue;
        }
        $parts[] = $count . ' × ' . $name;
    }

    return implode("\n", $parts);
}

/**
 * Single-line fence type summary (CSV, titles, tooltips).
 */
function fc_planners_format_fence_type_summary_inline(string $label): string
{
    $label = trim(str_replace(["\r\n", "\r"], "\n", $label));
    if ($label === '') {
        return '';
    }

    $parts = preg_split('/\n+/', $label) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn(string $part): bool => $part !== ''));

    return implode(', ', $parts);
}

function fc_planners_fence_type_label(string $fenceTypeRaw = '', $fenceDataRaw = null, int $sectionCount = 0): string
{
    $fences = fc_planners_fence_catalog();
    $rows = fc_planners_fence_section_types_from_rows(
        fc_planners_decode_json_field($fenceDataRaw),
        $fences
    );

    if ($rows === []) {
        $rows = fc_planners_fence_section_types_from_type_map($fenceTypeRaw, $fences);
        if (count($rows) === 1 && $sectionCount > 0) {
            $rows[0]['count'] = $sectionCount;
        }
    }

    return fc_planners_format_fence_type_summary($rows);
}

/**
 * @deprecated Use fc_planners_fence_type_label()
 */
function fc_planners_fence_type_label_list(string $fenceTypeRaw, int $sectionCount = 0): string
{
    return fc_planners_fence_type_label($fenceTypeRaw, null, $sectionCount);
}

/**
 * @param object $row
 * @return array<string, mixed>
 */
function fc_planners_normalize_list_row(object $row): array
{
    $item = fc_planners_normalize_row(fc_planners_list_columns(), $row, false);
    // Labels come from the lightweight fence_type column (no fence_data blob on list).
    $item['fence_type_label'] = fc_planners_fence_type_label(
        (string) ($row->fence_type ?? ''),
        null,
        isset($row->section_count) ? (int) $row->section_count : 0
    );

    return $item;
}

function fc_planners_browser_case_sql(): string
{
    return "CASE"
        . " WHEN TRIM(COALESCE(user_agent, '')) = '' THEN 'unknown'"
        . " WHEN LOWER(user_agent) LIKE '%edg/%'"
        . " OR LOWER(user_agent) LIKE '%edge/%'"
        . " OR LOWER(user_agent) LIKE '%edga/%'"
        . " OR LOWER(user_agent) LIKE '%edgios/%' THEN 'edge'"
        . " WHEN LOWER(user_agent) LIKE '%opr/%'"
        . " OR LOWER(user_agent) LIKE '%opera%' THEN 'opera'"
        . " WHEN LOWER(user_agent) LIKE '%samsungbrowser/%' THEN 'samsung_internet'"
        . " WHEN LOWER(user_agent) LIKE '%brave/%' THEN 'brave'"
        . " WHEN LOWER(user_agent) LIKE '%chrome/%'"
        . " OR LOWER(user_agent) LIKE '%crios/%' THEN 'chrome'"
        . " WHEN LOWER(user_agent) LIKE '%firefox/%'"
        . " OR LOWER(user_agent) LIKE '%fxios/%' THEN 'firefox'"
        . " WHEN LOWER(user_agent) LIKE '%safari/%' THEN 'safari'"
        . " WHEN LOWER(user_agent) LIKE '%msie %'"
        . " OR LOWER(user_agent) LIKE '%trident/%' THEN 'internet_explorer'"
        . " ELSE 'other' END";
}

/**
 * Normalize planner-entries list view key.
 */
function fc_planners_normalize_list_view(string $view): string
{
    $view = strtolower(trim($view));
    if ($view === 'trash') {
        return 'trash';
    }
    if ($view === 'duplicates') {
        return 'duplicates';
    }

    return 'all';
}

/**
 * @return array{where:string,types:string,params:list<mixed>}|null
 */
function fc_planners_build_filters(
    mysqli $conn,
    string $search = '',
    string $status = '',
    string $timeframe = '',
    string $state = '',
    array $fenceTypes = [],
    ?array $dateBounds = null,
    string $trashView = 'all',
    string $dateField = 'created_at',
    string $postcode = '',
    array $devices = [],
    array $browsers = [],
    ?int $sectionsMin = null,
    ?int $sectionsMax = null,
    ?int $quoteLoadsMin = null,
    ?int $quoteLoadsMax = null
): ?array {
    $parts = [];
    $types = '';
    $params = [];

    $trashView = fc_planners_normalize_list_view($trashView);
    if ($trashView === 'trash') {
        $parts[] = 'trashed_at IS NOT NULL';
    } elseif ($trashView === 'duplicates') {
        // Soft-archived duplicates: still live rows, excluded from All.
        $parts[] = 'trashed_at IS NULL';
        $parts[] = "status = 'duplicate'";
    } else {
        $parts[] = 'trashed_at IS NULL';
        // Index-friendly: avoid LOWER/TRIM on status for every list load.
        $parts[] = "(status IS NULL OR status <> 'duplicate')";
    }

    $status = trim($status);
    // Duplicates tab already scopes by status; ignore a conflicting status filter.
    if ($status !== '' && $trashView !== 'duplicates') {
        $parts[] = 'status = ?';
        $types .= 's';
        $params[] = $status;
    }

    $timeframe = trim($timeframe);
    if ($timeframe !== '') {
        $parts[] = 'timeframe = ?';
        $types .= 's';
        $params[] = $timeframe;
    }

    $state = trim($state);
    if ($state !== '') {
        $parts[] = 'state = ?';
        $types .= 's';
        $params[] = $state;
    }

    $postcode = trim($postcode);
    if ($postcode !== '') {
        $parts[] = 'TRIM(postcode) = ?';
        $types .= 's';
        $params[] = $postcode;
    }

    $devices = array_values(array_intersect(
        array_map(static fn($value): string => strtolower(trim((string) $value)), $devices),
        ['desktop', 'mobile', 'tablet', 'bot', 'unknown']
    ));
    if ($devices !== []) {
        $deviceParts = [];
        if (in_array('unknown', $devices, true)) {
            $deviceParts[] = "(device IS NULL OR TRIM(device) = '' OR LOWER(TRIM(device)) = 'unknown')";
        }
        $knownDevices = array_values(array_diff($devices, ['unknown']));
        if ($knownDevices !== []) {
            $deviceParts[] = 'LOWER(TRIM(device)) IN (' . implode(', ', array_fill(0, count($knownDevices), '?')) . ')';
            $types .= str_repeat('s', count($knownDevices));
            array_push($params, ...$knownDevices);
        }
        $parts[] = '(' . implode(' OR ', $deviceParts) . ')';
    }

    $browsers = array_values(array_intersect(
        array_map(static fn($value): string => strtolower(trim((string) $value)), $browsers),
        [
        'chrome',
        'edge',
        'firefox',
        'safari',
        'opera',
        'samsung_internet',
        'brave',
        'internet_explorer',
        'other',
        'unknown',
        ]
    ));
    if ($browsers !== []) {
        $parts[] = '(' . fc_planners_browser_case_sql() . ') IN ('
            . implode(', ', array_fill(0, count($browsers), '?')) . ')';
        $types .= str_repeat('s', count($browsers));
        array_push($params, ...$browsers);
    }

    if ($fenceTypes !== []) {
        $fenceParts = [];
        foreach ($fenceTypes as $slug) {
            $escaped = addcslashes($slug, '%_\\');
            // fence_type is JSON like {"slat":"slat"} — avoid fence_data LONGTEXT scans.
            $fenceParts[] = 'fence_type LIKE ?';
            $types .= 's';
            $params[] = '%"' . $escaped . '":%';
        }
        $parts[] = '(' . implode(' OR ', $fenceParts) . ')';
    }

    $search = trim($search);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $parts[] = '(planner_id LIKE ? OR name LIKE ? OR email LIKE ? OR mobile LIKE ? OR site_url LIKE ?)';
        $types .= 'sssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if (is_array($dateBounds) && !empty($dateBounds['from']) && !empty($dateBounds['to'])) {
        $column = $dateField === 'updated_at' ? 'updated_at' : 'created_at';
        $parts[] = $column . ' >= ? AND ' . $column . ' <= ?';
        $types .= 'ss';
        $params[] = (string) $dateBounds['from'];
        $params[] = (string) $dateBounds['to'];
    }

    foreach ([
        ['column' => 'section_count', 'operator' => '>=', 'value' => $sectionsMin],
        ['column' => 'section_count', 'operator' => '<=', 'value' => $sectionsMax],
        ['column' => 'quote_load_count', 'operator' => '>=', 'value' => $quoteLoadsMin],
        ['column' => 'quote_load_count', 'operator' => '<=', 'value' => $quoteLoadsMax],
    ] as $rangeFilter) {
        if ($rangeFilter['value'] === null) {
            continue;
        }
        $parts[] = $rangeFilter['column'] . ' ' . $rangeFilter['operator'] . ' ?';
        $types .= 'i';
        $params[] = max(0, (int) $rangeFilter['value']);
    }

    $where = $parts ? implode(' AND ', $parts) : '1=1';

    return [
        'where' => $where,
        'types' => $types,
        'params' => $params,
    ];
}

/**
 * @param array{where:string,types:string,params:list<mixed>} $filters
 */
function fc_planners_bind_filters(mysqli_stmt $stmt, array $filters): void
{
    if ($filters['types'] === '') {
        return;
    }

    $stmt->bind_param($filters['types'], ...$filters['params']);
}

function fc_planners_get_statuses(): array
{
    // Known planner statuses — avoids a full DISTINCT scan on every list page load.
    // Keep in sync with submit/checkout flows and Find Duplicates (status=duplicate).
    return ['duplicate', 'ordered', 'planning'];
}

/**
 * @return array{ok:bool,items?:list<array<string,mixed>>,total?:int,has_more?:bool,statuses?:list<string>,error?:string}
 */
function fc_planners_list_entries(
    string $search = '',
    string $status = '',
    int $limit = 50,
    int $offset = 0,
    bool $withStatuses = false,
    bool $withTotal = false,
    string $timeframe = '',
    string $state = '',
    array $fenceTypes = [],
    ?array $dateBounds = null,
    string $trashView = 'all',
    string $dateField = 'created_at',
    string $postcode = '',
    array $devices = [],
    array $browsers = [],
    ?int $sectionsMin = null,
    ?int $sectionsMax = null,
    ?int $quoteLoadsMin = null,
    ?int $quoteLoadsMax = null
): array {
    $isAll = fc_planners_list_is_all_limit($limit);
    if ($isAll) {
        $offset = 0;
    } else {
        $limit = max(1, min(500, $limit));
    }
    $offset = max(0, $offset);
    $search = trim($search);
    $status = trim($status);
    $timeframe = trim($timeframe);
    $state = trim($state);
    $postcode = trim($postcode);
    $devices = array_values($devices);
    $browsers = array_values($browsers);
    $fenceTypes = array_values($fenceTypes);
    $trashView = fc_planners_normalize_list_view($trashView);
    $dateField = $dateField === 'updated_at' ? 'updated_at' : 'created_at';

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $filters = fc_planners_build_filters(
        $conn,
        $search,
        $status,
        $timeframe,
        $state,
        $fenceTypes,
        $dateBounds,
        $trashView,
        $dateField,
        $postcode,
        $devices,
        $browsers,
        $sectionsMin,
        $sectionsMax,
        $quoteLoadsMin,
        $quoteLoadsMax
    );
    if ($filters === null) {
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Invalid filters.'];
    }

    // List columns only — never pull LONGTEXT fence_data on the list page.
    $listSelect = implode(', ', fc_planners_list_columns());
    $total = null;
    $orderBy = $dateField . ' DESC, id DESC';

    if ($isAll) {
        $sql = 'SELECT ' . $listSelect . ' FROM `' . $table . '` WHERE ' . $filters['where']
            . ' ORDER BY ' . $orderBy;
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            fc_planners_close_db($conn);
            return ['ok' => false, 'error' => 'Planners table not found. Import data/schema/wp_planners.sql.'];
        }
        if ($filters['types'] !== '') {
            $stmt->bind_param($filters['types'], ...$filters['params']);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            fc_planners_close_db($conn);
            return ['ok' => false, 'error' => 'Could not load planner entries.'];
        }
        $result = $stmt->get_result();
    } else {
        $fetchLimit = $limit + 1;
        $sql = 'SELECT ' . $listSelect . ' FROM `' . $table . '` WHERE ' . $filters['where']
            . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            fc_planners_close_db($conn);
            return ['ok' => false, 'error' => 'Planners table not found. Import data/schema/wp_planners.sql.'];
        }

        $types = $filters['types'] . 'ii';
        $params = array_merge($filters['params'], [$fetchLimit, $offset]);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $stmt->close();
            fc_planners_close_db($conn);
            return ['ok' => false, 'error' => 'Could not load planner entries.'];
        }

        $result = $stmt->get_result();
    }

    $items = [];
    while ($row = $result->fetch_object()) {
        $items[] = fc_planners_normalize_list_row($row);
    }
    $stmt->close();

    $hasMore = false;
    if (!$isAll) {
        $hasMore = count($items) > $limit;
        if ($hasMore) {
            array_pop($items);
        }
    }

    if ($withTotal) {
        $countSql = 'SELECT COUNT(*) AS total FROM `' . $table . '` WHERE ' . $filters['where'];
        $countStmt = $conn->prepare($countSql);
        if ($countStmt) {
            if ($filters['types'] !== '') {
                $countStmt->bind_param($filters['types'], ...$filters['params']);
            }
            if ($countStmt->execute()) {
                $countRes = $countStmt->get_result();
                if ($countRes && ($countRow = $countRes->fetch_object())) {
                    $total = (int) ($countRow->total ?? 0);
                }
            }
            $countStmt->close();
        }
    }

    $statuses = [];
    if ($withStatuses) {
        $statuses = fc_planners_get_statuses();
    }

    fc_planners_close_db($conn);

    $out = [
        'ok' => true,
        'items' => $items,
        'has_more' => $hasMore,
    ];

    if ($total !== null) {
        $out['total'] = $total;
    } elseif ($offset > 0) {
        $out['total'] = $offset + count($items) + ($hasMore ? 1 : 0);
    } else {
        $out['total'] = count($items);
    }

    if ($withStatuses) {
        $out['statuses'] = $statuses;
    }

    if ($isAll) {
        $out['limit'] = 'all';
    } else {
        $out['limit'] = $limit;
    }

    return $out;
}

/**
 * @return array{all:int,trash:int,duplicates:int}
 */
function fc_planners_trash_view_counts(): array
{
    $counts = ['all' => 0, 'trash' => 0, 'duplicates' => 0];

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return $counts;
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $safe = $conn->real_escape_string($table);

    // Three indexed COUNTs beat one full-table SUM(CASE…) once idx_trash_status exists.
    $queries = [
        'all' => 'SELECT COUNT(*) AS c FROM `' . $safe . '`'
            . ' WHERE trashed_at IS NULL AND (status IS NULL OR status <> \'duplicate\')',
        'trash' => 'SELECT COUNT(*) AS c FROM `' . $safe . '` WHERE trashed_at IS NOT NULL',
        'duplicates' => 'SELECT COUNT(*) AS c FROM `' . $safe . '`'
            . ' WHERE trashed_at IS NULL AND status = \'duplicate\'',
    ];

    foreach ($queries as $key => $sql) {
        $result = $conn->query($sql);
        if ($result && ($row = $result->fetch_object())) {
            $counts[$key] = (int) ($row->c ?? 0);
        }
    }

    fc_planners_close_db($conn);

    return $counts;
}

/**
 * Fast check: whether any active planner_id appears more than once.
 *
 * Uses a self-join + LIMIT 1 so MySQL can stop at the first match.
 * Full grouping still happens only in fc_planners_dedupe_scan() on click.
 */
function fc_planners_duplicate_candidate_count(): int
{
    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return 0;
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $safe = $conn->real_escape_string($table);

    $sql = 'SELECT 1 FROM `' . $safe . '` a'
        . ' INNER JOIN `' . $safe . '` b'
        . '   ON a.`planner_id` = b.`planner_id` AND a.`id` < b.`id`'
        . ' WHERE a.`trashed_at` IS NULL'
        . "   AND (a.`status` IS NULL OR a.`status` <> 'duplicate')"
        . "   AND a.`planner_id` IS NOT NULL AND a.`planner_id` <> ''"
        . '   AND b.`trashed_at` IS NULL'
        . "   AND (b.`status` IS NULL OR b.`status` <> 'duplicate')"
        . ' LIMIT 1';

    $result = $conn->query($sql);
    $found = $result && $result->num_rows > 0;
    fc_planners_close_db($conn);

    return $found ? 1 : 0;
}

/**
 * Scan for duplicate groups (same planner_id on more than one active row).
 * Keeps the newest row in each group; older ids are marked duplicate.
 *
 * @return array{
 *   ok:bool,
 *   groups?:int,
 *   keep_count?:int,
 *   mark_count?:int,
 *   mark_ids?:list<int>,
 *   keep_ids?:list<int>,
 *   sample?:list<array{planner_id:string,keep_id:int,mark_ids:list<int>,total:int}>,
 *   error?:string
 * }
 */
function fc_planners_dedupe_scan(int $sampleLimit = 8): array
{
    $sampleLimit = max(0, min(50, $sampleLimit));

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $safe = $conn->real_escape_string($table);

    // Group in PHP by planner_id — keep newest (created_at DESC, id DESC).
    $sql = 'SELECT `id`, TRIM(`planner_id`) AS planner_key, `created_at`'
        . ' FROM `' . $safe . '`'
        . ' WHERE `trashed_at` IS NULL'
        . "   AND (`status` IS NULL OR `status` <> 'duplicate')"
        . "   AND `planner_id` IS NOT NULL AND `planner_id` <> ''"
        . ' ORDER BY planner_key ASC, `created_at` DESC, `id` DESC';

    $result = $conn->query($sql);
    if (!$result) {
        $err = $conn->error;
        fc_planners_close_db($conn);

        return ['ok' => false, 'error' => 'Could not scan for duplicates.' . ($err !== '' ? ' ' . $err : '')];
    }

    /** @var array<string, list<array{id:int,planner_id:string}>> $byGroup */
    $byGroup = [];
    while ($row = $result->fetch_assoc()) {
        $plannerKey = (string) ($row['planner_key'] ?? '');
        if ($plannerKey === '') {
            continue;
        }
        if (!isset($byGroup[$plannerKey])) {
            $byGroup[$plannerKey] = [];
        }
        $byGroup[$plannerKey][] = [
            'id' => (int) ($row['id'] ?? 0),
            'planner_id' => $plannerKey,
        ];
    }

    fc_planners_close_db($conn);

    $markIds = [];
    $keepIds = [];
    $sample = [];
    $groups = 0;

    foreach ($byGroup as $plannerKey => $rows) {
        if (count($rows) < 2) {
            continue;
        }
        $groups++;
        $keepId = (int) ($rows[0]['id'] ?? 0);
        if ($keepId > 0) {
            $keepIds[] = $keepId;
        }
        $groupMarkIds = [];
        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $id = (int) ($rows[$i]['id'] ?? 0);
            if ($id > 0) {
                $markIds[] = $id;
                $groupMarkIds[] = $id;
            }
        }
        if (count($sample) < $sampleLimit) {
            $sample[] = [
                'planner_id' => (string) $plannerKey,
                'keep_id' => $keepId,
                'mark_ids' => $groupMarkIds,
                'total' => count($rows),
            ];
        }
    }

    return [
        'ok' => true,
        'groups' => $groups,
        'keep_count' => count($keepIds),
        'mark_count' => count($markIds),
        'mark_ids' => $markIds,
        'keep_ids' => $keepIds,
        'sample' => $sample,
    ];
}

/**
 * Mark planner rows as status=duplicate (soft archive; keeps newest siblings).
 *
 * @param list<int> $ids
 * @return array{ok:bool,updated?:int,error?:string}
 */
function fc_planners_bulk_mark_duplicate(array $ids): array
{
    return fc_planners_bulk_set_status($ids, 'duplicate');
}

/**
 * Restore rows previously marked duplicate back to planning.
 *
 * @param list<int> $ids
 * @return array{ok:bool,updated?:int,error?:string}
 */
function fc_planners_bulk_restore_from_duplicate(array $ids): array
{
    return fc_planners_bulk_set_status($ids, 'planning', 'duplicate');
}

/**
 * @param list<int> $ids
 * @return array{ok:bool,updated?:int,error?:string}
 */
function fc_planners_bulk_set_status(array $ids, string $status, ?string $onlyCurrentStatus = null): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
        return $id > 0;
    })));
    if ($ids === []) {
        return ['ok' => false, 'error' => 'No entries selected.'];
    }
    if (count($ids) > 500) {
        return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
    }

    $status = strtolower(trim($status));
    if ($status === '' || !preg_match('/^[a-z0-9_\-]{1,64}$/', $status)) {
        return ['ok' => false, 'error' => 'Invalid status.'];
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = 's' . str_repeat('i', count($ids));
    $params = array_merge([$status], $ids);

    $sql = 'UPDATE `' . $table . '` SET `status` = ?, `status_updated_at` = NOW(), `updated_at` = NOW()'
        . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NULL';

    if ($onlyCurrentStatus !== null) {
        $only = strtolower(trim($onlyCurrentStatus));
        if ($only !== '' && preg_match('/^[a-z0-9_\-]{1,64}$/', $only)) {
            $sql .= ' AND `status` = ?';
            $types .= 's';
            $params[] = $only;
        }
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        fc_planners_close_db($conn);

        return ['ok' => false, 'error' => 'Could not update entries.'];
    }

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        $stmt->close();
        fc_planners_close_db($conn);

        return ['ok' => false, 'error' => 'Could not update entries.'];
    }

    $updated = (int) $stmt->affected_rows;
    $stmt->close();
    fc_planners_close_db($conn);

    return ['ok' => true, 'updated' => $updated];
}

/**
 * Apply duplicate marking in batches (for progress UI).
 *
 * @param list<int> $ids
 * @return array{ok:bool,updated?:int,processed?:int,remaining?:int,done?:bool,error?:string}
 */
function fc_planners_dedupe_apply_batch(array $ids, int $offset = 0, int $batchSize = 100): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
        return $id > 0;
    })));
    $offset = max(0, $offset);
    $batchSize = max(1, min(200, $batchSize));

    if ($ids === []) {
        return ['ok' => true, 'updated' => 0, 'processed' => 0, 'remaining' => 0, 'done' => true];
    }

    $slice = array_slice($ids, $offset, $batchSize);
    if ($slice === []) {
        return [
            'ok' => true,
            'updated' => 0,
            'processed' => count($ids),
            'remaining' => 0,
            'done' => true,
        ];
    }

    $result = fc_planners_bulk_mark_duplicate($slice);
    if (empty($result['ok'])) {
        return $result;
    }

    $processed = $offset + count($slice);
    $remaining = max(0, count($ids) - $processed);

    return [
        'ok' => true,
        'updated' => (int) ($result['updated'] ?? 0),
        'processed' => $processed,
        'remaining' => $remaining,
        'done' => $remaining === 0,
    ];
}

/**
 * @param list<int> $ids
 * @return array{ok:bool,updated?:int,error?:string}
 */
function fc_planners_bulk_set_trashed(array $ids, bool $trash): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
        return $id > 0;
    })));
    if ($ids === []) {
        return ['ok' => false, 'error' => 'No entries selected.'];
    }
    if (count($ids) > 500) {
        return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    if ($trash) {
        $sql = 'UPDATE `' . $table . '` SET `trashed_at` = NOW(), `updated_at` = NOW()'
            . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NULL';
    } else {
        $sql = 'UPDATE `' . $table . '` SET `trashed_at` = NULL, `updated_at` = NOW()'
            . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NOT NULL';
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        fc_planners_close_db($conn);

        return ['ok' => false, 'error' => 'Could not update entries.'];
    }

    $stmt->bind_param($types, ...$ids);
    if (!$stmt->execute()) {
        $stmt->close();
        fc_planners_close_db($conn);

        return ['ok' => false, 'error' => 'Could not update entries.'];
    }

    $updated = (int) $stmt->affected_rows;
    $stmt->close();
    fc_planners_close_db($conn);

    return ['ok' => true, 'updated' => $updated];
}

/**
 * Export selected planner entries as portable JSON payload.
 *
 * @param list<int> $ids
 * @return array{ok:bool,payload?:array<string,mixed>,exported?:int,error?:string}
 */
function fc_planners_export_entries_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
        return $id > 0;
    })));
    if ($ids === []) {
        return ['ok' => false, 'error' => 'No entries selected.'];
    }
    if (count($ids) > 500) {
        return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $columns = fc_planners_export_columns();
    $select = implode(', ', array_map(static function (string $col): string {
        return '`' . $col . '`';
    }, $columns));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = 'SELECT ' . $select . ' FROM `' . $table . '` WHERE `id` IN (' . $placeholders . ') ORDER BY `id` ASC';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Could not export entries.'];
    }

    $stmt->bind_param($types, ...$ids);
    if (!$stmt->execute()) {
        $stmt->close();
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Could not export entries.'];
    }

    $result = $stmt->get_result();
    $entries = [];
    while ($result && ($row = $result->fetch_assoc())) {
        if (!is_array($row)) {
            continue;
        }
        $entry = [];
        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            if ($column === 'order_id' || $column === 'section_count' || $column === 'quote_load_count') {
                $entry[$column] = (int) $value;
                continue;
            }
            $entry[$column] = $value === null ? null : (string) $value;
        }
        $entries[] = $entry;
    }
    $stmt->close();
    fc_planners_close_db($conn);

    if ($entries === []) {
        return ['ok' => false, 'error' => 'No matching entries found.'];
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $payload = [
        'format' => 'fc-planner-entries',
        'version' => 1,
        'exported_at' => date('c'),
        'source' => [
            'site_host' => $host,
            'table' => $table,
        ],
        'count' => count($entries),
        'entries' => $entries,
    ];

    return [
        'ok' => true,
        'exported' => count($entries),
        'payload' => $payload,
    ];
}

/**
 * @param mixed $value
 */
function fc_planners_import_encode_value(mysqli $conn, $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if (is_array($value) || is_object($value)) {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $value = is_string($encoded) ? $encoded : '';
    }

    return "'" . $conn->real_escape_string((string) $value) . "'";
}

/**
 * Find existing planner id by natural key.
 */
function fc_planners_find_id_by_planner_id(mysqli $conn, string $table, string $plannerId): int
{
    $sql = 'SELECT `id` FROM `' . $table . '` WHERE `planner_id` = ? ORDER BY `id` DESC LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $plannerId);
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int) ($row['id'] ?? 0);
}

/**
 * Generate a unique planner_id for remint imports.
 */
function fc_planners_generate_unique_planner_id(mysqli $conn, string $table): string
{
    if (!function_exists('get_uid')) {
        require_once __DIR__ . '/helpers.php';
    }

    for ($i = 0; $i < 12; $i++) {
        $candidate = get_uid(6);
        if (fc_planners_find_id_by_planner_id($conn, $table, $candidate) <= 0) {
            return $candidate;
        }
    }

    return strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Import portable planner entry JSON rows.
 *
 * @param list<array<string,mixed>> $entries
 * @param string $mode skip|overwrite|remint
 * @return array{
 *   ok:bool,
 *   imported?:int,
 *   updated?:int,
 *   inserted?:int,
 *   skipped?:int,
 *   remapped?:list<array{from:string,to:string}>,
 *   message?:string,
 *   error?:string
 * }
 */
function fc_planners_import_entries(array $entries, string $mode = 'overwrite'): array
{
    $mode = strtolower(trim($mode));
    if (!in_array($mode, ['skip', 'overwrite', 'remint'], true)) {
        $mode = 'overwrite';
    }
    if ($entries === []) {
        return ['ok' => false, 'error' => 'No entries to import.'];
    }
    if (count($entries) > 500) {
        return ['ok' => false, 'error' => 'Too many entries to import (max 500).'];
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $columns = fc_planners_export_columns();
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $remapped = [];

    foreach ($entries as $raw) {
        if (!is_array($raw)) {
            $skipped++;
            continue;
        }

        $plannerId = trim((string) ($raw['planner_id'] ?? ''));
        if ($plannerId === '') {
            $skipped++;
            continue;
        }

        // Match only by planner_id — never by database id / source_id.
        unset($raw['id'], $raw['source_id']);

        $existingId = fc_planners_find_id_by_planner_id($conn, $table, $plannerId);
        if ($existingId > 0) {
            if ($mode === 'skip') {
                $skipped++;
                continue;
            }
            if ($mode === 'remint') {
                $newId = fc_planners_generate_unique_planner_id($conn, $table);
                $remapped[] = ['from' => $plannerId, 'to' => $newId];
                $plannerId = $newId;
                $existingId = 0;
            }
        }

        $row = [];
        foreach ($columns as $column) {
            if ($column === 'planner_id') {
                $row[$column] = $plannerId;
                continue;
            }
            if (!array_key_exists($column, $raw)) {
                continue;
            }
            $value = $raw[$column];
            if ($column === 'order_id' || $column === 'section_count' || $column === 'quote_load_count') {
                $row[$column] = (int) $value;
                continue;
            }
            if ($value === null || $value === '') {
                if (in_array($column, ['trashed_at', 'status_updated_at', 'created_at', 'updated_at'], true)) {
                    $row[$column] = null;
                } else {
                    $row[$column] = $value === null ? null : '';
                }
                continue;
            }
            if (is_array($value) || is_object($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $row[$column] = is_string($encoded) ? $encoded : '';
                continue;
            }
            $row[$column] = (string) $value;
        }

        if (!isset($row['updated_at']) || $row['updated_at'] === null || $row['updated_at'] === '') {
            $row['updated_at'] = date('Y-m-d H:i:s');
        }
        if ($existingId <= 0 && (!isset($row['created_at']) || $row['created_at'] === null || $row['created_at'] === '')) {
            $row['created_at'] = date('Y-m-d H:i:s');
        }

        if ($existingId > 0) {
            $sets = [];
            foreach ($row as $column => $value) {
                $sets[] = '`' . $column . '` = ' . fc_planners_import_encode_value($conn, $value);
            }
            $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `id` = ' . (int) $existingId . ' LIMIT 1';
            if ($conn->query($sql)) {
                $updated++;
            } else {
                $skipped++;
            }
            continue;
        }

        $insertColumns = array_keys($row);
        $insertValues = [];
        foreach ($insertColumns as $column) {
            $insertValues[] = fc_planners_import_encode_value($conn, $row[$column]);
        }
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $insertColumns) . '`)'
            . ' VALUES (' . implode(', ', $insertValues) . ')';
        if ($conn->query($sql)) {
            $inserted++;
        } else {
            $skipped++;
        }
    }

    fc_planners_close_db($conn);

    $imported = $inserted + $updated;
    $noun = $imported === 1 ? 'entry' : 'entries';
    $message = $imported . ' ' . $noun . ' imported'
        . ($updated > 0 ? ' (' . $updated . ' updated)' : '')
        . ($inserted > 0 ? ' (' . $inserted . ' inserted)' : '')
        . ($skipped > 0 ? ', ' . $skipped . ' skipped' : '')
        . '.';

    return [
        'ok' => true,
        'imported' => $imported,
        'updated' => $updated,
        'inserted' => $inserted,
        'skipped' => $skipped,
        'remapped' => $remapped,
        'message' => $message,
    ];
}

/**
 * Permanently delete planner entries that are already in trash.
 *
 * @param list<int> $ids
 * @return array{ok:bool,updated?:int,error?:string}
 */
function fc_planners_bulk_delete_permanently(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function (int $id): bool {
        return $id > 0;
    })));
    if ($ids === []) {
        return ['ok' => false, 'error' => 'No entries selected.'];
    }
    if (count($ids) > 500) {
        return ['ok' => false, 'error' => 'Too many entries selected (max 500).'];
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = 'DELETE FROM `' . $table . '`'
        . ' WHERE `id` IN (' . $placeholders . ') AND `trashed_at` IS NOT NULL';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        fc_planners_close_db($conn);

        return ['ok' => false, 'error' => 'Could not delete entries.'];
    }

    $stmt->bind_param($types, ...$ids);
    if (!$stmt->execute()) {
        $stmt->close();
        fc_planners_close_db($conn);

        return ['ok' => false, 'error' => 'Could not delete entries.'];
    }

    $updated = (int) $stmt->affected_rows;
    $stmt->close();
    fc_planners_close_db($conn);

    return ['ok' => true, 'updated' => $updated];
}

/**
 * @return int|null
 */
function fc_planners_entry_id_for_planner(string $plannerId): ?int
{
    $plannerId = trim($plannerId);
    if ($plannerId === '') {
        return null;
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return null;
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $sql = 'SELECT id FROM `' . $table . '` WHERE planner_id = ? ORDER BY id DESC LIMIT 1';
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        fc_planners_close_db($conn);
        return null;
    }

    $stmt->bind_param('s', $plannerId);
    if (!$stmt->execute()) {
        $stmt->close();
        fc_planners_close_db($conn);
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_object() : null;
    $stmt->close();
    fc_planners_close_db($conn);

    if (!$row || !isset($row->id)) {
        return null;
    }

    return (int) $row->id;
}

/**
 * @return array{ok:bool,item?:array<string,mixed>,error?:string}
 */
function fc_planners_get_entry_by_id(int $entryId): array
{
    if ($entryId <= 0) {
        return ['ok' => false, 'error' => 'Entry ID required.'];
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $columns = implode(', ', fc_planners_entry_columns());
    $sql = 'SELECT ' . $columns . ', fence_data, cart_data, cart_items_data, project_plans_data FROM `' . $table . '` WHERE id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Could not load entry.'];
    }

    $stmt->bind_param('i', $entryId);
    if (!$stmt->execute()) {
        $stmt->close();
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Could not load entry.'];
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_object() : null;
    $stmt->close();
    fc_planners_close_db($conn);

    if (!$row) {
        return ['ok' => false, 'error' => 'Entry not found.'];
    }

    return [
        'ok' => true,
        'item' => fc_planners_normalize_detail_row($row),
    ];
}

/**
 * @return array{ok:bool,item?:array<string,mixed>,error?:string}
 */
function fc_planners_get_entry(string $plannerId): array
{
    $plannerId = trim($plannerId);
    if ($plannerId === '') {
        return ['ok' => false, 'error' => 'Planner ID required.'];
    }

    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $columns = implode(', ', fc_planners_entry_columns());
    $sql = 'SELECT ' . $columns . ', fence_data, cart_data, cart_items_data, project_plans_data FROM `' . $table . '` WHERE planner_id = ? ORDER BY id DESC LIMIT 1';
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Could not load entry.'];
    }

    $stmt->bind_param('s', $plannerId);
    if (!$stmt->execute()) {
        $stmt->close();
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Could not load entry.'];
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_object() : null;
    $stmt->close();
    fc_planners_close_db($conn);

    if (!$row) {
        return ['ok' => false, 'error' => 'Entry not found.'];
    }

    return [
        'ok' => true,
        'item' => fc_planners_normalize_detail_row($row),
    ];
}

/**
 * @return list<array{
 *   qty:int,
 *   name:string,
 *   sku:string,
 *   image:string,
 *   fence_label:string,
 *   fence_slug:string,
 *   optional:bool,
 *   optional_included:bool,
 *   suggested_qty:int
 * }>
 */
function fc_planners_parse_entry_cart_items(object $row): array
{
    if (!function_exists('fc_cart_item_fence_style_label')) {
        require_once __DIR__ . '/helpers.php';
    }

    $fences = fc_planners_fence_catalog();
    $cartData = fc_planners_decode_json_field($row->cart_data ?? null);

    if ($cartData !== []) {
        if (isset($cartData['items']) && is_array($cartData['items'])) {
            $cartData = $cartData['items'];
        }

        if (array_is_list($cartData)) {
            $rows = fc_planners_normalize_admin_cart_rows($cartData, $fences);
            if ($rows !== []) {
                return $rows;
            }
        }
    }

    $grouped = fc_planners_decode_json_field($row->cart_items_data ?? null);
    if ($grouped === []) {
        return [];
    }

    return fc_planners_admin_cart_rows_from_grouped($grouped, $fences);
}

/**
 * @param array<string,mixed> $item
 * @param array<string, array<string, mixed>> $fences
 */
function fc_planners_cart_item_fence_slug(array $item, array $fences): string
{
    if (!function_exists('fc_normalize_planner_fence_slug')) {
        require_once __DIR__ . '/helpers.php';
    }

    $slug = trim((string) ($item['fence'] ?? ''));
    if ($slug !== '') {
        return fc_normalize_planner_fence_slug($slug);
    }

    $label = fc_cart_item_fence_style_label($item, $fences);
    if ($label === '') {
        return '';
    }

    foreach ($fences as $key => $fence) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        if (strcasecmp(fc_fence_style_title_from_slug($key, $fences), $label) === 0) {
            return fc_normalize_planner_fence_slug($key);
        }
    }

    return '';
}

/**
 * @param list<array<string,mixed>> $items
 * @param array<string, array<string, mixed>> $fences
 * @return list<array<string,mixed>>
 */
function fc_planners_normalize_admin_cart_rows(array $items, array $fences): array
{
    $out = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $optional = !empty($item['optional']);
        $included = empty($item['optional']) || !empty($item['optional_included']);
        $qty = (int) ($item['qty'] ?? 0);
        $suggestedQty = (int) ($item['suggested_qty'] ?? 0);

        if ($optional && !$included) {
            if ($suggestedQty <= 0) {
                continue;
            }
            $qty = $suggestedQty;
        } elseif ($qty <= 0) {
            continue;
        }

        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($item['slug'] ?? ''));
        }

        $out[] = [
            'qty' => $qty,
            'name' => $name,
            'sku' => trim((string) ($item['sku'] ?? '')),
            'image' => trim((string) ($item['image'] ?? '')),
            'fence_label' => fc_cart_item_fence_style_label($item, $fences),
            'fence_slug' => fc_planners_cart_item_fence_slug($item, $fences),
            'optional' => $optional,
            'optional_included' => $included,
            'suggested_qty' => $suggestedQty,
        ];
    }

    return $out;
}

/**
 * @param array<string, mixed> $grouped
 * @param array<string, array<string, mixed>> $fences
 * @return list<array<string,mixed>>
 */
function fc_planners_admin_cart_rows_from_grouped(array $grouped, array $fences): array
{
    if (!function_exists('fc_normalize_planner_fence_slug')) {
        require_once __DIR__ . '/helpers.php';
    }

    $out = [];

    foreach ($grouped as $group) {
        if (!is_array($group)) {
            continue;
        }

        $fenceSlug = trim((string) ($group['slug'] ?? ''));
        $normalizedFenceSlug = $fenceSlug !== '' ? fc_normalize_planner_fence_slug($fenceSlug) : '';
        $fenceLabel = $normalizedFenceSlug !== '' ? fc_fence_style_title_from_slug($normalizedFenceSlug, $fences) : '';
        $color = trim((string) ($group['color'] ?? ''));
        if ($fenceLabel !== '' && $color !== '') {
            $fenceLabel .= ' (' . $color . ')';
        }

        $lines = $group['items'] ?? [];
        if (!is_array($lines)) {
            continue;
        }

        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $optional = !empty($line['optional']);
            $qty = $optional ? (int) ($line['suggested_qty'] ?? 0) : (int) ($line['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $slug = trim((string) ($line['slug'] ?? ''));
            $out[] = [
                'qty' => $qty,
                'name' => $slug,
                'sku' => '',
                'image' => '',
                'fence_label' => $fenceLabel,
                'fence_slug' => $normalizedFenceSlug,
                'optional' => $optional,
                'optional_included' => !$optional,
                'suggested_qty' => (int) ($line['suggested_qty'] ?? 0),
            ];
        }
    }

    return $out;
}

/**
 * Resolve saved "other items needed" from planners row (column or project_plans JSON).
 *
 * @param mixed $extraColumn
 * @param mixed $projectPlansRaw
 * @return mixed
 */
function fc_planners_resolve_extra_value($extraColumn, $projectPlansRaw)
{
    $extra = $extraColumn;
    $hasExtra = $extra !== null && $extra !== '';

    if ($hasExtra) {
        if (is_string($extra) && trim($extra) === '[]') {
            return array();
        }

        return $extra;
    }

    if ($projectPlansRaw === null || $projectPlansRaw === '') {
        return $extra;
    }

    $pp = fc_planners_decode_json_field($projectPlansRaw);

    if (!empty($pp['extra'])) {
        return $pp['extra'];
    }

    if (!empty($pp['nothing_extra'])) {
        return 'nothing';
    }

    return $extra;
}

/**
 * Normalize extra / other-items value for detail rows (always a string).
 *
 * @param mixed $extra
 */
function fc_planners_extra_to_string($extra): string
{
    if ($extra === null || $extra === '') {
        return '[]';
    }

    if (is_array($extra)) {
        $items = array_values(array_filter(array_map(static function ($part): string {
            return trim((string) $part);
        }, $extra)));

        if ($items === []) {
            return '[]';
        }

        $encoded = json_encode($items, JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : '[]';
    }

    $text = trim((string) $extra);
    if ($text === '' || $text === 'nothing') {
        return '[]';
    }

    return $text;
}

/**
 * @param object $row
 * @return array<string, mixed>
 */
function fc_planners_normalize_detail_row(object $row): array
{
    $item = fc_planners_normalize_row(fc_planners_entry_columns(), $row);
    $item['extra'] = fc_planners_extra_to_string(
        fc_planners_resolve_extra_value(
            $row->extra ?? '',
            $row->project_plans_data ?? null
        )
    );
    $item['fence_type_label'] = fc_planners_fence_type_label(
        (string) ($row->fence_type ?? ''),
        $row->fence_data ?? null,
        isset($row->section_count) ? (int) $row->section_count : 0
    );
    $item['cart_items'] = fc_planners_parse_entry_cart_items($row);

    return $item;
}

function fc_planners_distinct_statuses(): array
{
    try {
        $ctx = fc_planners_open_db();
    } catch (\RuntimeException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $table = $ctx['table'];
    $conn = $ctx['conn'];
    $sql = "SELECT DISTINCT status FROM `" . $table . "` WHERE status IS NOT NULL AND status <> '' ORDER BY status ASC";
    $result = $conn->query($sql);

    if ($result === false) {
        fc_planners_close_db($conn);
        return ['ok' => false, 'error' => 'Could not load statuses.'];
    }

    $statuses = [];
    while ($row = $result->fetch_object()) {
        $statuses[] = (string) ($row->status ?? '');
    }

    fc_planners_close_db($conn);

    return ['ok' => true, 'statuses' => $statuses];
}
