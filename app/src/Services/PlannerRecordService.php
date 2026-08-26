<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Helpers\StringHelper;
use Fc\Admin\Settings\IntegrationsSettings;

/**
 * FC planners table (wp_planners) — public-site quote lifecycle.
 *
 * Session hydration, planner-ID resolution, client-metadata capture, and the
 * shared request-scoped DB connection used by both this file and the admin
 * OOP classes (Fc\Admin\Models\PlannerEntryModel, Fc\Admin\Presenters\PlannerEntryPresenter,
 * Fc\Admin\Services\PlannerEntryMaintenanceService). Admin list/filter/search,
 * duplicate detection, bulk actions, import/export, and row presentation
 * formatting live in those classes now, not here.
 */
final class PlannerRecordService
{
    /**
     * Create wp_planners when missing (local setup).
     */
    public static function ensureTable(\mysqli $conn, string $table): bool
    {
        $safe = $conn->real_escape_string($table);
        $check = $conn->query("SHOW TABLES LIKE '{$safe}'");
        if ($check && $check->num_rows > 0) {
            return true;
        }

        $schemaFile = dirname(__DIR__, 3) . '/writable/schema/wp_planners.sql';
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
    public static function ensureColumns(\mysqli $conn, string $table): void
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
            'webhook_sent_at' => 'datetime DEFAULT NULL',
        ];

        foreach ($columns as $column => $definition) {
            $colCheck = $conn->query("SHOW COLUMNS FROM `{$safe}` LIKE '{$column}'");
            if ($colCheck && $colCheck->num_rows > 0) {
                continue;
            }
            $conn->query("ALTER TABLE `{$safe}` ADD COLUMN `{$column}` {$definition}");
        }

        self::ensureIndexes($conn, $table);
    }

    /**
     * Add list/filter indexes used by planner-entries (idempotent).
     *
     * Avoid UNIQUE(planner_id) while duplicate planner_ids may still exist.
     */
    public static function ensureIndexes(\mysqli $conn, string $table): void
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
     * Client metadata captured on planner submission.
     *
     * @return array{ip_address:string,device:string,user_agent:string}
     */
    public static function submissionMeta(): array
    {
        $userAgent = RequestHelper::clientUserAgent();

        return [
            'ip_address' => RequestHelper::clientIp(),
            'device' => RequestHelper::clientDevice($userAgent),
            'user_agent' => $userAgent,
        ];
    }

    /**
     * Normalize other-items value for the planners `extra` DB column (JSON array string).
     */
    public static function extraForDb($extra, $nothing_extra = null): string
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
                return self::extraForDb($decoded);
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
    public static function buildDataInputsFromSession(array $fences, string $plannerId): ?array
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

        $extra = self::extraForDb(
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
            'mobile'             => CartBuilderService::normalizeMobileForStorage( $fc_data['mobile'] ?? '' ) ?: null,
            'email'              => $fc_data['email'] ?? null,
            'address'            => $fc_data['address'] ?? null,
            'postcode'           => $fc_data['postcode'] ?? null,
            'state'              => $fc_data['state'] ?? null,
            'fence_type'         => PlannerSessionService::selectedFences($fences, 'slug'),
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
    public static function persistSession(array $fences): array
    {
        $planner_id = trim((string) ($_SESSION['planner_id'] ?? ''));
        if ($planner_id === '' || !self::isValidPlannerId($planner_id)) {
            return ['success' => false, 'skipped' => true];
        }

        $data_inputs = self::buildDataInputsFromSession($fences, $planner_id);
        if ($data_inputs === null) {
            return ['success' => false, 'skipped' => true];
        }

        $db = new Database();
        $existing = $db->select_where('planners', '`planner_id`="' . $planner_id . '"', 'id');
        if (!$existing) {
            $data_inputs['created_at'] = date('Y-m-d H:i:s');
        }

        $result = $db->updateOrCreate('planners', $data_inputs, ['planner_id' => $planner_id]);

        if ( ! empty( $result['success'] ) ) {
            $_SESSION['fc_data']['project_plans'] = PlannerSessionService::clientProjectPlansFromSession();
        }

        return $result;
    }

    /**
     * Planner ids are get_uid() output — uppercase letters and digits only.
     */
    public static function isValidPlannerId(string $plannerId): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{4,64}$/', $plannerId);
    }

    /**
     * Whether the planners table exposes `trashed_at` (older tables predate the column).
     */
    public static function plannersHasTrashedColumn(\mysqli $conn, string $table): bool
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
    public static function plannerIdState(string $plannerId): array
    {
        $state = ['found' => false, 'trashed' => false];

        $plannerId = trim($plannerId);
        if ($plannerId === '') {
            return $state;
        }

        $db = new Database();
        $conn = $db->connect();
        if (!$conn instanceof \mysqli) {
            return $state;
        }

        $table = $db->tableName('planners');
        $select = self::plannersHasTrashedColumn($conn, $table) ? '`id`, `trashed_at`' : '`id`';

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
     * Current request's site PID Prefix (Settings → Integrations), or '' when unset.
     * Prepended on top of the random portion of a newly-minted planner id — e.g. a "LH"
     * prefix turns "ABCDEF" into "LHABCDEF" — never eating into the random length itself.
     */
    private static function currentSitePidPrefix(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $key = SiteRegistryService::keyFromDomain($host);

        return $key !== '' ? IntegrationsSettings::pidPrefixForKey($key) : '';
    }

    /**
     * Mint a planner id that is not taken yet (get_uid() on its own can collide).
     */
    public static function newPlannerId(): string
    {
        $prefix = self::currentSitePidPrefix();

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $candidate = $prefix . StringHelper::randomId(6);
            if (!self::plannerIdState($candidate)['found']) {
                return $candidate;
            }
        }

        return $prefix . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Decide which planner row a public save (/submit or /checkout) writes to.
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
    public static function resolveSubmissionPlannerId($postedPlannerId = null, bool $allowNew = true): array
    {
        $sessionId = trim((string) ($_SESSION['planner_id'] ?? ''));
        if ($sessionId !== '') {
            $state = self::plannerIdState($sessionId);
            if (!$state['trashed']) {
                return [
                    'planner_id' => $sessionId,
                    'exists' => $state['found'],
                    'source' => 'session',
                ];
            }
        }

        $posted = is_scalar($postedPlannerId) ? trim((string) $postedPlannerId) : '';
        if ($posted !== '' && self::isValidPlannerId($posted)) {
            $state = self::plannerIdState($posted);
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

        $newId = self::newPlannerId();
        $_SESSION['planner_id'] = $newId;

        return ['planner_id' => $newId, 'exists' => false, 'source' => 'new'];
    }

    /**
     * Whether a planners row is soft-deleted (in trash).
     *
     * @param object|array|null $row
     */
    public static function rowIsTrashed($row): bool
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
     * Mark a planner row as reloaded — called when a saved quote is opened via ?qid=.
     * Increments quote_load_count, sets status='reloaded', and stamps status_updated_at,
     * all in one UPDATE so every reload writes each field exactly once.
     */
    public static function markReloaded(string $plannerId): void
    {
        $plannerId = trim($plannerId);
        if ($plannerId === '') {
            return;
        }

        try {
            $ctx = self::openDb();
        } catch (\RuntimeException $e) {
            return;
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];

        $sql = 'UPDATE `' . $table . '`
                SET
                    `quote_load_count` = COALESCE(`quote_load_count`, 0) + 1,
                    `status` = \'reloaded\',
                    `status_updated_at` = NOW()
                WHERE `planner_id` = ?
                LIMIT 1';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            self::closeDb($conn);
            return;
        }

        $stmt->bind_param('s', $plannerId);
        $stmt->execute();
        $stmt->close();
        self::closeDb($conn);
    }

    public static function dbErrorMessage(?Database $db = null): string
    {
        $technical = $db instanceof Database ? (string) $db->last_connect_error : '';
        return DatabaseConfigService::connectErrorMessage($technical);
    }

    /**
     * @return array{db:Database,table:string,conn:\mysqli}
     */
    public static function openDb(): array
    {
        // One mysqli handle per request — list/statuses/counts used to open 3 separate connections.
        static $ctx = null;
        if (is_array($ctx) && ($ctx['conn'] ?? null) instanceof \mysqli) {
            return $ctx;
        }

        $db = new Database();
        $table = $db->tableName('planners');
        $conn  = $db->connect();

        if (!$conn instanceof \mysqli) {
            throw new \RuntimeException(self::dbErrorMessage($db));
        }

        static $ensured = [];
        if (!isset($ensured[$table])) {
            self::ensureTable($conn, $table);
            self::ensureColumns($conn, $table);
            $ensured[$table] = true;
        }

        $ctx = [
            'db' => $db,
            'table' => $table,
            'conn' => $conn,
        ];

        register_shutdown_function(static function () use (&$ctx): void {
            if (is_array($ctx) && ($ctx['conn'] ?? null) instanceof \mysqli) {
                @$ctx['conn']->close();
                $ctx = null;
            }
        });

        return $ctx;
    }

    public static function closeDb(\mysqli $conn): void
    {
        // Request-scoped connection is closed on shutdown — keep it open for reuse.
    }
}
