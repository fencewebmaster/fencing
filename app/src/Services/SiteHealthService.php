<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\AssetHelper;
use Fc\Admin\Helpers\FileHelper;
use Fc\Admin\Helpers\FormatHelper;
use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Models\GalleryModel;
use Fc\Admin\Models\GroupPermissionsModel;
use Fc\Admin\Models\StoreProductModel;
use Fc\Admin\Models\SystemProductModel;
use Fc\Admin\Models\UserModel;
use Fc\Admin\Settings\ConsoleSettings;
use Fc\Admin\Settings\IntegrationsSettings;
use Fc\Admin\Settings\ThemeSettings;

/**
 * Settings → Site Health (Super Admin only): read-only checks of the server, the database and
 * the site's security, one card per group.
 *
 * Nothing here writes. The database checks never go through PlannerRecordService::openDb(),
 * which creates and alters the planners table, and the web checks only request files that do
 * nothing when opened — never the CRM webhook (a request fires the Zap) and never
 * build/minify/build.php (a request runs the build).
 *
 * Each check returns [state, value, detail] — state is 'good', 'warn', 'bad' or 'info' (shown,
 * not scored) — or null when it doesn't apply to this server.
 */
final class SiteHealthService
{
    /** Card order on the tab. */
    public const GROUPS = [
        'server' => 'Server & App',
        'database' => 'Database',
        'security' => 'Security',
    ];

    /** Last day of security fixes per PHP branch (php.net/supported-versions). */
    private const PHP_SUPPORT_ENDS = [
        '8.1' => '2025-12-31',
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
        '8.5' => '2029-12-31',
    ];

    /** Last day of support per database branch, oldest first (the vendors' lifecycle pages). */
    private const DB_SUPPORT_ENDS = [
        'MySQL' => ['5.6' => '2021-02-28', '5.7' => '2023-10-31', '8.0' => '2026-04-30', '8.4' => '2032-04-30'],
        'MariaDB' => [
            '10.3' => '2023-05-25',
            '10.4' => '2024-06-18',
            '10.5' => '2025-06-24',
            '10.6' => '2026-07-06',
            '10.11' => '2028-02-16',
            '11.4' => '2029-05-29',
        ],
    ];

    /** Extension => what stops working without it. */
    private const REQUIRED_EXTENSIONS = [
        'mysqli' => 'planner entries and sign-in',
        'pdo_mysql' => 'store products',
        'mbstring' => 'text handling',
        'curl' => 'the CRM webhook and store push',
        'fileinfo' => 'Media Library uploads',
    ];

    /** theme.json section => the tab that saves it. */
    private const SETTINGS_SECTIONS = [
        'colors' => 'Theme',
        'branding' => 'Branding',
        'fenceColors' => 'Fence colors',
        'catalog' => 'Catalog',
        'system' => 'System',
        'plannerExtraOptions' => 'Project Plan items',
        'projectPlanStock' => 'Stock & Delivery',
        'seo' => 'SEO',
    ];

    /** Must never open in a browser. Each is requested only when it exists on disk. */
    private const PRIVATE_PATHS = ['config.php', 'config.php.lock', 'writable/theme.json', 'app/bootstrap.php', '.git/HEAD', '.gitignore'];

    /** Harmless files that show whether a developer folder is public. */
    private const DEVELOPER_PATHS = ['CLAUDE.md', 'build/minify/README.md'];

    /** A real, harmless file: proves the server can reach its own site, and carries its headers. */
    private const CONTROL_PATHS = ['public/assets/css/fonts.css', 'public/assets/css/admin/theme.css'];

    private const SUPPORT_WARN_DAYS = 183;
    private const INACTIVE_ADMIN_DAYS = 90;
    private const SESSION_FILES_WARN = 20000;
    private const CACHE_BYTES_WARN = 524288000;
    private const ERROR_LOG_TAIL_BYTES = 262144;
    private const PROBE_BODY_BYTES = 4096;

    /**
     * @return list<array{key:string,label:string,state:string,value:string,detail:string}>
     */
    public static function run(string $group): array
    {
        // A local copy runs on XAMPP defaults (no HTTPS, root without a password, errors shown).
        $local = SiteRegistryService::searchBlockReason() === 'localhost';

        $rows = match ($group) {
            'server' => self::serverChecks($local),
            'database' => self::databaseChecks(),
            'security' => self::securityChecks($local),
            default => [],
        };

        return array_values(array_filter($rows));
    }

    /**
     * @param callable(): ?array $check
     * @return array{key:string,label:string,state:string,value:string,detail:string}|null
     */
    private static function attempt(string $key, string $label, callable $check): ?array
    {
        try {
            $result = $check();
        } catch (\Throwable $e) {
            $result = ['warn', 'Not checked', 'This check stopped with an error: ' . $e->getMessage()];
        }
        if ($result === null) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'state' => (string) $result[0],
            'value' => (string) $result[1],
            'detail' => (string) ($result[2] ?? ''),
        ];
    }

    // —— Server & App ——

    /**
     * @return list<array<string, string>|null>
     */
    private static function serverChecks(bool $local): array
    {
        return [
            self::attempt('php', 'PHP version', static fn (): array => self::phpVersion()),
            self::attempt('extensions', 'PHP extensions', static fn (): array => self::phpExtensions()),
            self::attempt('opcache', 'OPcache', static fn (): array => self::opcache($local)),
            self::attempt('limits', 'PHP limits', static fn (): array => self::phpLimits()),
            self::attempt('disk', 'Disk space', static fn (): array => self::diskSpace()),
            self::attempt('writable', 'Writable files and folders', static fn (): array => self::writablePaths()),
            self::attempt('config', 'config.php', static fn (): array => self::appConfig()),
            self::attempt('settings', 'Saved settings', static fn (): array => self::settingsFile()),
            self::attempt('fences', 'Fence styles', static fn (): array => self::fenceStyles()),
            self::attempt('products', 'Product files', static fn (): array => self::productFiles()),
            self::attempt('minified', 'Minified frontend files', static fn (): ?array => self::minifiedAssets()),
            self::attempt('storage', 'Cache and sessions', static fn (): array => self::storage()),
            self::attempt('errors', 'PHP error log', static fn (): array => self::errorLog($local)),
            self::attempt('webhook', 'CRM webhook', static fn (): array => self::webhook($local)),
            self::attempt('maps', 'Google Maps key', static fn (): array => self::mapsKey()),
        ];
    }

    private static function phpVersion(): array
    {
        if (PHP_VERSION_ID < 80200) {
            return ['bad', PHP_VERSION, 'FC is built for PHP 8.2 or later.'];
        }

        return self::supportWindow(
            PHP_VERSION,
            self::PHP_SUPPORT_ENDS[PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION] ?? '',
            'Plan the move to a newer PHP version with the host.'
        );
    }

    /**
     * @param string $ends last supported day (Y-m-d); blank for a branch newer than the table
     */
    private static function supportWindow(string $value, string $ends, string $upgrade): array
    {
        if ($ends === '') {
            return ['good', $value, ''];
        }

        $endsAt = (int) strtotime($ends . ' 23:59:59');
        $date = date('j M Y', $endsAt);
        $days = (int) floor(($endsAt - time()) / 86400);
        if ($days < 0) {
            return ['bad', $value, 'Security support ended on ' . $date . ', so new security holes are no longer fixed. ' . $upgrade];
        }
        if ($days <= self::SUPPORT_WARN_DAYS) {
            return ['warn', $value, 'Security support ends on ' . $date . '. ' . $upgrade];
        }

        return ['good', $value, 'Supported until ' . $date . '.'];
    }

    private static function phpExtensions(): array
    {
        $missing = [];
        foreach (self::REQUIRED_EXTENSIONS as $extension => $feature) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension . ' (' . $feature . ')';
            }
        }
        if ($missing !== []) {
            return ['bad', count($missing) . ' missing', 'Ask the host to turn on ' . implode(', ', $missing) . '.'];
        }

        return ['good', 'All ' . count(self::REQUIRED_EXTENSIONS) . ' enabled', implode(', ', array_keys(self::REQUIRED_EXTENSIONS)) . '.'];
    }

    private static function opcache(bool $local): array
    {
        if (!extension_loaded('Zend OPcache') || !filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            return [$local ? 'info' : 'warn', 'Off', 'PHP compiles every file again on each request. Turning OPcache on makes every page faster.'];
        }

        // Settings saves call opcache_invalidate() on config.php; restrict_api can refuse it.
        $restrict = trim((string) ini_get('opcache.restrict_api'));
        if (!filter_var(ini_get('opcache.validate_timestamps'), FILTER_VALIDATE_BOOLEAN)
            && $restrict !== ''
            && !self::pathWithin((string) FC_ROOT, $restrict)) {
            return ['bad', 'On', 'opcache.restrict_api stops FC refreshing config.php after a save, and validate_timestamps is off, so settings saved to config.php never take effect.'];
        }

        return ['good', 'On', ''];
    }

    private static function pathWithin(string $path, string $prefix): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/') . '/';
        $prefix = rtrim(str_replace('\\', '/', $prefix), '/') . '/';

        return PHP_OS_FAMILY === 'Windows' ? stripos($path, $prefix) === 0 : str_starts_with($path, $prefix);
    }

    private static function phpLimits(): array
    {
        $memory = ini_parse_quantity((string) ini_get('memory_limit'));
        $upload = self::uploadCap();
        $time = (int) ini_get('max_execution_time');
        $media = GalleryMaintenanceService::MAX_UPLOAD_BYTES;

        $issues = [];
        if ($memory > 0 && $memory < 134217728) {
            $issues[] = 'memory_limit is ' . ini_get('memory_limit') . '; big exports and product downloads need 128M or more';
        }
        if ($upload > 0 && $upload < $media) {
            $issues[] = 'PHP stops uploads at ' . FormatHelper::bytes($upload) . ', below the Media Library\'s ' . FormatHelper::bytes($media) . ' limit';
        }
        if ($time > 0 && $time < 30) {
            $issues[] = 'max_execution_time is ' . $time . 's; product downloads can need 30s or more';
        }

        $value = 'Memory ' . ($memory > 0 ? FormatHelper::bytes($memory) : 'unlimited')
            . ' · Uploads ' . ($upload > 0 ? FormatHelper::bytes($upload) : 'unlimited')
            . ' · Time ' . ($time > 0 ? $time . 's' : 'unlimited');

        return $issues === [] ? ['good', $value, ''] : ['warn', $value, ucfirst(implode('. ', $issues)) . '.'];
    }

    /** The smaller of upload_max_filesize and post_max_size; 0 when neither limits an upload. */
    private static function uploadCap(): int
    {
        $caps = array_filter([
            ini_parse_quantity((string) ini_get('upload_max_filesize')),
            ini_parse_quantity((string) ini_get('post_max_size')),
        ], static fn (int $bytes): bool => $bytes > 0);

        return $caps === [] ? 0 : min($caps);
    }

    private static function diskSpace(): array
    {
        $free = @disk_free_space((string) FC_ROOT);
        $total = @disk_total_space((string) FC_ROOT);
        if ($free === false) {
            return ['info', 'Unknown', 'The server doesn\'t report free space to PHP.'];
        }

        $value = FormatHelper::bytes((int) $free) . ' free' . ($total ? ' of ' . FormatHelper::bytes((int) $total) : '');
        if ($free < 209715200) {
            return ['bad', $value, 'Saves, uploads and sign-ins start failing when the disk fills up.'];
        }
        if ($free < 1073741824 || ($total && $free / $total < 0.05)) {
            return ['warn', $value, 'Free up space, or ask the host for more, before saves and uploads start failing.'];
        }

        return ['good', $value, ''];
    }

    private static function writablePaths(): array
    {
        $config = ConsoleSettings::configPath();
        $targets = [
            'config.php' => [$config, 'settings kept in config.php'],
            'writable/theme.json' => [ThemeSettings::filePath(), 'the other settings tabs'],
            'writable/products.csv' => [StoreProductModel::csvPath(), 'System Products edits'],
            'writable/fences' => [FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'fences', 'the fence style editor'],
            'writable/groups-permissions' => [GroupPermissionsModel::dir(), 'Group Permissions'],
            'writable/storage/cache' => [CacheStorageService::cacheDir(), 'the product caches'],
            'writable/storage/sessions' => [CacheStorageService::sessionsDir(), 'staying signed in'],
            'writable/storage/presence' => [CacheStorageService::presenceDir(), 'the Users page\'s online list'],
            'public/assets/uploads' => [GalleryModel::uploadDir(), 'Media Library uploads'],
        ];

        $blocked = [];
        foreach ($targets as $label => [$path, $feature]) {
            if (is_dir($path) || is_file($path)) {
                $ok = is_writable($path);
            } else {
                // Not created yet: it is written into its folder on first save.
                $ok = is_dir(dirname($path)) && is_writable(dirname($path));
            }
            // config.php is saved through a temp file beside it, then swapped in.
            if ($path === $config) {
                $ok = $ok && is_writable(dirname($path));
            }
            if (!$ok) {
                $blocked[] = $label . ' (' . $feature . ')';
            }
        }

        if ($blocked !== []) {
            return ['bad', count($blocked) . ' not writable', 'PHP can\'t write to ' . implode(', ', $blocked) . '. Give the web server user write access to them.'];
        }

        return ['good', 'All ' . count($targets) . ' writable', ''];
    }

    private static function appConfig(): array
    {
        $app = DatabaseConfigService::loadAppConfig();
        if ($app === []) {
            return ['bad', 'Unreadable', 'config.php is missing or doesn\'t return its settings, so nothing that needs the database works.'];
        }

        $sites = count(DatabaseConfigService::mysqlBySite($app));
        if ($sites === 0) {
            return ['bad', 'No sites', 'config.php has no site database settings.'];
        }

        return ['good', $sites . ' ' . ($sites === 1 ? 'site' : 'sites') . ' configured', ''];
    }

    private static function settingsFile(): array
    {
        $path = ThemeSettings::filePath();
        if (!is_file($path)) {
            return ['info', 'Defaults', 'Nothing has been saved yet, so every settings tab shows its defaults.'];
        }

        $data = FileHelper::readJsonFile($path);
        if ($data === null) {
            return ['bad', 'Unreadable', 'writable/theme.json isn\'t valid JSON, so every settings tab has fallen back to its defaults. Restore it from a backup, or save each tab again.'];
        }

        $defaults = [];
        foreach (self::SETTINGS_SECTIONS as $key => $tab) {
            if (!is_array($data[$key] ?? null)) {
                $defaults[] = $tab;
            }
        }
        $saved = count(self::SETTINGS_SECTIONS) - count($defaults);

        return [
            'good',
            $saved . ' of ' . count(self::SETTINGS_SECTIONS) . ' groups saved',
            $defaults === [] ? '' : 'On defaults until saved: ' . implode(', ', $defaults) . '.',
        ];
    }

    private static function fenceStyles(): array
    {
        $files = glob(FC_ROOT . '/writable/fences/*.php') ?: [];
        if ($files === []) {
            return ['bad', 'None', 'writable/fences is empty, so the planner has no fence styles to offer. The folder isn\'t in git; copy it from the live site or a backup.'];
        }

        return ['good', count($files) . ' ' . (count($files) === 1 ? 'file' : 'files'), ''];
    }

    private static function productFiles(): array
    {
        // Store ⇄ System are inverted in the class names: StoreProductModel holds System Products.
        $system = self::csvRows(StoreProductModel::csvPath());
        if ($system === null) {
            return ['bad', 'Missing', 'System Products (writable/products.csv) is missing, so the planner\'s materials list has no products to match.'];
        }

        $parts = ['System ' . number_format($system)];
        $missing = [];
        foreach (['GO', 'JG'] as $source) {
            $rows = self::csvRows(SystemProductModel::csvPath($source));
            if ($rows === null) {
                $missing[] = $source;
                continue;
            }
            $parts[] = 'Store ' . $source . ' ' . number_format($rows);
        }

        $value = implode(' · ', $parts);
        if ($missing !== []) {
            return ['warn', $value, 'Store Products for ' . implode(' and ', $missing) . ' hasn\'t been downloaded yet (Products → Store Products).'];
        }

        return ['good', $value, ''];
    }

    /** Data rows in a CSV (header excluded), or null when it can't be read. */
    private static function csvRows(string $path): ?int
    {
        $handle = is_readable($path) ? @fopen($path, 'rb') : false;
        if ($handle === false) {
            return null;
        }

        $rows = -1;
        while (($row = fgetcsv($handle)) !== false) {
            if ($row !== [null]) {
                $rows++;
            }
        }
        fclose($handle);

        return max(0, $rows);
    }

    private static function minifiedAssets(): ?array
    {
        $root = str_replace('\\', '/', (string) FC_ROOT) . '/';
        $total = 0;
        $stale = [];
        foreach (['js', 'css'] as $type) {
            $dir = FC_ROOT . '/public/assets/' . $type . '/frontend';
            if (!is_dir($dir)) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $path = str_replace('\\', '/', $file->getPathname());
                if (!str_ends_with($path, '.' . $type) || str_ends_with($path, '.min.' . $type)) {
                    continue;
                }
                $relative = substr($path, strlen($root));
                $total++;
                // asset() falls back to the source whenever the min/ copy is missing or older.
                if (AssetHelper::minified($relative) === $relative) {
                    $stale[] = substr($relative, strlen('public/assets/'));
                }
            }
        }

        if ($total === 0) {
            return null;
        }
        if ($stale !== []) {
            return ['warn', count($stale) . ' of ' . $total . ' unminified', 'Visitors download the full-size ' . self::listSome($stale) . ' until php build/minify/build.php is run.'];
        }

        return ['good', 'All ' . $total . ' up to date', ''];
    }

    private static function storage(): array
    {
        $cacheBytes = 0;
        foreach (CacheStorageService::cacheBuckets() as $bucket) {
            $cacheBytes += CacheStorageService::cacheBucketStats($bucket)['bytes'];
        }
        $sessions = count(glob(CacheStorageService::sessionsDir() . DIRECTORY_SEPARATOR . 'sess_*', GLOB_NOSORT) ?: []);
        $value = 'Cache ' . FormatHelper::bytes($cacheBytes) . ' · ' . number_format($sessions) . ' session ' . ($sessions === 1 ? 'file' : 'files');

        $issues = [];
        if ($sessions > self::SESSION_FILES_WARN) {
            // RememberTokenService::TTL (10 years) is also the session GC lifetime.
            $issues[] = 'Admin sign-ins last 10 years, so their session files are never cleared and the folder only grows. Files untouched for months can be deleted; their owners just sign in again.';
        }
        if ($cacheBytes > self::CACHE_BYTES_WARN) {
            $issues[] = 'The cache is unusually large. Clearing it frees the space, and it rebuilds itself.';
        }

        return $issues === [] ? ['good', $value, ''] : ['warn', $value, implode(' ', $issues)];
    }

    private static function errorLog(bool $local): array
    {
        if (!filter_var(ini_get('log_errors'), FILTER_VALIDATE_BOOLEAN)) {
            return [$local ? 'info' : 'warn', 'Off', 'PHP isn\'t logging errors (log_errors is off), so problems leave no trace.'];
        }

        $log = trim((string) ini_get('error_log'));
        if ($log === '' || strcasecmp($log, 'syslog') === 0) {
            return ['info', 'Web server log', 'PHP writes its errors to the web server\'s own log, which FC can\'t read.'];
        }
        if (!is_file($log)) {
            if (!is_dir(dirname($log)) || !is_writable(dirname($log))) {
                return ['warn', 'Not writable', 'PHP is set to log errors to ' . $log . ', but that folder is missing or read-only, so errors are lost.'];
            }

            return ['good', 'None logged', ''];
        }

        $tail = self::fileTail($log, self::ERROR_LOG_TAIL_BYTES);
        if ($tail === null) {
            return ['info', 'Unreadable', 'PHP\'s error log can\'t be read from here.'];
        }

        // The log is shared with WordPress: count only lines from FC's own files or FC's own error_log() calls.
        $root = str_replace('\\', '/', (string) FC_ROOT);
        $since = time() - 86400;
        $counts = ['error' => 0, 'warning' => 0, 'notice' => 0];
        $latest = '';
        foreach (preg_split('/\R/', $tail) ?: [] as $line) {
            if (!preg_match('/^\[([^\]]+)\]\s+(.+)$/', $line, $m)) {
                continue;
            }
            $message = str_replace('\\', '/', $m[2]);
            if (!str_starts_with($message, 'FC ') && stripos($message, $root) === false) {
                continue;
            }
            $at = strtotime($m[1]);
            if ($at === false || $at < $since) {
                continue;
            }
            if (preg_match('/^PHP (Fatal|Parse|Recoverable fatal) error|Uncaught /i', $message)) {
                $counts['error']++;
            } elseif (preg_match('/^PHP (Notice|Deprecated|Strict)/i', $message)) {
                $counts['notice']++;
            } else {
                $counts['warning']++;
            }
            $latest = $message;
        }

        if ($latest === '') {
            return ['good', 'None in 24 hours', ''];
        }

        $parts = [];
        foreach ($counts as $kind => $count) {
            if ($count > 0) {
                $parts[] = number_format($count) . ' ' . $kind . ($count === 1 ? '' : 's');
            }
        }
        $state = $counts['error'] > 0 ? 'bad' : ($counts['warning'] > 0 ? 'warn' : 'good');
        $latest = str_ireplace($root . '/', '', $latest);

        return [$state, implode(', ', $parts) . ' in 24 hours', 'Latest: ' . (strlen($latest) > 240 ? substr($latest, 0, 237) . '…' : $latest)];
    }

    /** The last $bytes of a file, starting at a whole line, or null when it can't be read. */
    private static function fileTail(string $path, int $bytes): ?string
    {
        $handle = is_readable($path) ? @fopen($path, 'rb') : false;
        if ($handle === false) {
            return null;
        }

        $cut = (int) @filesize($path) > $bytes;
        if ($cut) {
            fseek($handle, -$bytes, SEEK_END);
        }
        $tail = (string) stream_get_contents($handle);
        fclose($handle);

        if ($cut && ($newline = strpos($tail, "\n")) !== false) {
            $tail = substr($tail, $newline + 1);
        }

        return $tail;
    }

    private static function webhook(bool $local): array
    {
        $integrations = IntegrationsSettings::get();
        $test = $integrations['webhookMode'] === 'test';
        $url = trim((string) ($test ? $integrations['webhookTestUrl'] : $integrations['webhookUrl']));
        if ($url === '') {
            return ['warn', 'Not set', ($test ? 'Test mode is on, but the Test Webhook URL is empty' : 'No Webhook URL is set') . ', so planner submissions and checkouts don\'t reach the CRM.'];
        }

        // Same rule as PlannerWebhookService::send(): an address without a scheme goes out as https://.
        $full = preg_match('#^https?://#i', $url) ? $url : 'https://' . $url;
        // Only the host is shown: the rest of a Zapier URL is the hook's secret.
        $host = (string) parse_url($full, PHP_URL_HOST);
        if (strtolower((string) parse_url($full, PHP_URL_SCHEME)) !== 'https') {
            return ['warn', $host !== '' ? $host : 'Invalid', 'The webhook address starts with http://, so customer details travel unencrypted.'];
        }
        if ($test && !$local) {
            return ['warn', 'Test · ' . $host, 'Live planner submissions and checkouts are going to the Test Webhook URL. Switch Settings → Integration back to Live when testing is done.'];
        }

        return ['good', ($test ? 'Test' : 'Live') . ' · ' . $host, ''];
    }

    private static function mapsKey(): array
    {
        if (trim((string) IntegrationsSettings::get()['googleMapsApiKey']) === '') {
            return ['warn', 'Not set', 'The planner\'s address search needs a Google Maps key (Settings → Integration).'];
        }

        return ['good', 'Set', ''];
    }

    // —— Database ——

    /**
     * @return list<array<string, string>|null>
     */
    private static function databaseChecks(): array
    {
        $cfg = DatabaseConfigService::resolveConfig();
        $db = new Database($cfg);
        $started = microtime(true);
        $conn = $db->connect();
        $ms = (int) round((microtime(true) - $started) * 1000);

        if (!$conn instanceof \mysqli) {
            $error = DatabaseConfigService::connectErrorMessage($db->last_connect_error);

            return [self::attempt('connection', 'Connection', static fn (): array => ['bad', 'Failed', $error])];
        }

        try {
            $table = $db->tableName('planners');
            try {
                $info = self::tableInfo($conn, $table);
                $indexes = $info === null ? [] : self::tableIndexes($conn, $table);
            } catch (\Throwable) {
                $info = null;
                $indexes = [];
            }

            return [
                self::attempt('connection', 'Connection', static fn (): array => self::dbConnection($cfg, $ms)),
                self::attempt('server', 'Database server', static fn (): array => self::dbServer($conn)),
                self::attempt('connections', 'Connections in use', static fn (): ?array => self::dbConnections($conn)),
                self::attempt('planners', 'Planner entries', static fn (): array => self::plannerTable($conn, $table, $info)),
                self::attempt('structure', 'Table structure', static fn (): ?array => $info === null ? null : self::plannerStructure($conn, $table, $indexes)),
                self::attempt('engine', 'Storage engine and encoding', static fn (): ?array => $info === null ? null : self::plannerEngine($info)),
                self::attempt('quoteIds', 'Duplicate quote guard', static fn (): ?array => self::quoteIdGuard($indexes)),
                self::attempt('store', 'Store products', static fn (): array => self::storeProducts($cfg)),
                self::attempt('login', 'Sign-in database', static fn (): ?array => self::loginDatabase($cfg)),
                self::attempt('size', 'Database size', static fn (): array => self::databaseSize($conn)),
            ];
        } finally {
            $conn->close();
        }
    }

    private static function ident(string $name): string
    {
        return str_replace('`', '``', $name);
    }

    private static function scalar(\mysqli $conn, string $sql, int $column = 0): ?string
    {
        $result = $conn->query($sql);
        if (!$result instanceof \mysqli_result) {
            return null;
        }
        $row = $result->fetch_row();
        $result->free();

        return is_array($row) && isset($row[$column]) ? (string) $row[$column] : null;
    }

    /**
     * @return array{engine:string,collation:string,bytes:int}|null null when the table doesn't exist
     */
    private static function tableInfo(\mysqli $conn, string $table): ?array
    {
        $stmt = $conn->prepare('SELECT ENGINE, TABLE_COLLATION, DATA_LENGTH + INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_row() : null;
        $stmt->close();

        return is_array($row) ? ['engine' => (string) $row[0], 'collation' => (string) $row[1], 'bytes' => (int) $row[2]] : null;
    }

    /**
     * @return array<string, bool> index name => unique
     */
    private static function tableIndexes(\mysqli $conn, string $table): array
    {
        $indexes = [];
        $result = $conn->query('SHOW INDEX FROM `' . self::ident($table) . '`');
        while ($result instanceof \mysqli_result && ($row = $result->fetch_assoc())) {
            $indexes[(string) $row['Key_name']] = (int) $row['Non_unique'] === 0;
        }

        return $indexes;
    }

    private static function dbConnection(array $cfg, int $ms): array
    {
        $detail = 'Database ' . $cfg['database'] . ' for the ' . $cfg['key'] . ' site.';
        if ($ms > 1000) {
            return ['warn', 'Slow (' . number_format($ms) . ' ms)', 'Opening a connection took over a second, which slows every page. ' . $detail];
        }

        return ['good', 'Connected in ' . $ms . ' ms', $detail];
    }

    private static function dbServer(\mysqli $conn): array
    {
        $version = (string) self::scalar($conn, 'SELECT VERSION()');
        $vendor = stripos($version, 'mariadb') !== false ? 'MariaDB' : 'MySQL';
        $number = (string) preg_replace('/-.*$/', '', $version);
        if (!preg_match('/^(\d+)\.(\d+)/', $number, $m)) {
            return ['info', $version !== '' ? $version : 'Unknown', ''];
        }

        $label = $vendor . ' ' . $number;
        $branch = $m[1] . '.' . $m[2];
        $known = self::DB_SUPPORT_ENDS[$vendor];
        $upgrade = 'Ask the host to upgrade the database server.';
        if (isset($known[$branch])) {
            return self::supportWindow($label, $known[$branch], $upgrade);
        }

        $branches = array_map('strval', array_keys($known));
        if (version_compare($branch, $branches[0], '<')) {
            return ['bad', $label, 'This version has been out of support for years. ' . $upgrade];
        }
        if (version_compare($branch, $branches[count($branches) - 1], '>')) {
            return ['good', $label, ''];
        }

        return ['info', $label, 'A short-term release. Check its end-of-support date with the host.'];
    }

    private static function dbConnections(\mysqli $conn): ?array
    {
        try {
            $max = (int) self::scalar($conn, 'SELECT @@max_connections');
            $used = (int) self::scalar($conn, "SHOW GLOBAL STATUS LIKE 'Threads_connected'", 1);
        } catch (\Throwable) {
            // Not every host lets a site's user read the server status.
            return null;
        }
        if ($max <= 0) {
            return null;
        }

        $value = number_format($used) . ' of ' . number_format($max);
        if ($used / $max >= 0.9) {
            return ['bad', $value, 'The server is close to refusing new connections, which shows up as "Could not connect" errors.'];
        }
        if ($used / $max >= 0.75) {
            return ['warn', $value, 'The server is running short of free connections.'];
        }

        return ['good', $value, ''];
    }

    private static function plannerTable(\mysqli $conn, string $table, ?array $info): array
    {
        if ($info === null) {
            return ['bad', 'Table missing', $table . ' doesn\'t exist. FC creates it on the next planner visit; if it stays missing, the database user can\'t create tables.'];
        }

        $count = (int) self::scalar($conn, 'SELECT COUNT(*) FROM `' . self::ident($table) . '`');

        return ['good', number_format($count) . ' ' . ($count === 1 ? 'entry' : 'entries') . ' · ' . FormatHelper::bytes($info['bytes']), $table];
    }

    /**
     * @param array<string, bool> $indexes
     */
    private static function plannerStructure(\mysqli $conn, string $table, array $indexes): array
    {
        $expected = self::schemaExpectations();
        if ($expected['columns'] === []) {
            return ['info', 'Not checked', 'writable/schema is missing, so there is nothing to compare the table with.'];
        }

        $columns = [];
        $result = $conn->query('SHOW COLUMNS FROM `' . self::ident($table) . '`');
        while ($result instanceof \mysqli_result && ($row = $result->fetch_assoc())) {
            $columns[] = (string) $row['Field'];
        }

        $missingColumns = array_values(array_diff($expected['columns'], $columns));
        if ($missingColumns !== []) {
            $count = count($missingColumns);

            return ['bad', $count . ' missing ' . ($count === 1 ? 'column' : 'columns'), 'Missing ' . implode(', ', $missingColumns) . ', so saves that use them fail. Run the matching file in writable/schema.'];
        }

        $missingIndexes = array_values(array_diff($expected['indexes'], array_keys($indexes)));
        if ($missingIndexes !== []) {
            $count = count($missingIndexes);

            return ['warn', $count . ' missing ' . ($count === 1 ? 'index' : 'indexes'), 'Missing ' . implode(', ', $missingIndexes) . '. Planner Entries and the dashboard run slower without them (writable/schema/wp_planners_list_indexes.sql).'];
        }

        return ['good', 'Up to date', count($expected['columns']) . ' columns and ' . count($expected['indexes']) . ' indexes match writable/schema.'];
    }

    /**
     * What writable/schema/wp_planners*.sql (the CREATE plus every migration) says the table holds.
     *
     * @return array{columns:list<string>,indexes:list<string>}
     */
    private static function schemaExpectations(): array
    {
        $columns = [];
        $indexes = [];
        foreach (glob(FC_ROOT . '/writable/schema/wp_planners*.sql') ?: [] as $file) {
            $sql = (string) preg_replace('/^\s*--.*$/m', '', (string) @file_get_contents($file));
            if (preg_match_all('/^\s*`(\w+)`\s+[a-z]/mi', $sql, $m)) {
                array_push($columns, ...$m[1]);
            }
            if (preg_match_all('/ADD\s+COLUMN\s+`(\w+)`/i', $sql, $m)) {
                array_push($columns, ...$m[1]);
            }
            if (preg_match_all('/\bKEY\s+`(\w+)`/i', $sql, $m)) {
                array_push($indexes, ...$m[1]);
            }
        }

        return ['columns' => array_values(array_unique($columns)), 'indexes' => array_values(array_unique($indexes))];
    }

    /**
     * @param array{engine:string,collation:string,bytes:int} $info
     */
    private static function plannerEngine(array $info): array
    {
        $issues = [];
        if (strcasecmp($info['engine'], 'InnoDB') !== 0) {
            $issues[] = $info['engine'] . ' locks the whole table on every save and can\'t recover after a crash; convert it to InnoDB';
        }
        if (!str_starts_with(strtolower($info['collation']), 'utf8mb4')) {
            $issues[] = 'emoji and some other characters in customer notes can\'t be stored; convert the table to utf8mb4';
        }

        $value = $info['engine'] . ' · ' . $info['collation'];

        return $issues === [] ? ['good', $value, ''] : ['warn', $value, ucfirst(implode('. ', $issues)) . '.'];
    }

    /**
     * @param array<string, bool> $indexes
     */
    private static function quoteIdGuard(array $indexes): ?array
    {
        if (!isset($indexes['planner_id'])) {
            return null;
        }
        if ($indexes['planner_id']) {
            return ['good', 'On', 'The database refuses a second row for the same quote.'];
        }

        return ['info', 'Off', 'Two saves at the same moment can still create twin rows for one quote. Once Planner Entries → Find Duplicates finds none, run writable/schema/wp_planners_planner_id_unique.sql.'];
    }

    private static function storeProducts(array $cfg): array
    {
        $ctx = DatabaseConfigService::pdo($cfg);
        if (!$ctx['pdo'] instanceof \PDO) {
            return ['bad', 'Can\'t connect', DatabaseConfigService::connectErrorMessage($ctx['error'])];
        }

        $prefix = (string) preg_replace('/[^A-Za-z0-9_]/', '', $ctx['prefix']);
        $statement = $ctx['pdo']->query("SELECT COUNT(*) FROM `{$prefix}posts` WHERE post_type = 'product' AND post_status = 'publish'");
        $count = $statement === false ? 0 : (int) $statement->fetchColumn();
        if ($count === 0) {
            return ['warn', 'None published', 'WooCommerce has no published products, so product lookups come back empty.'];
        }

        return ['good', number_format($count) . ' published', ''];
    }

    private static function loginDatabase(array $cfg): ?array
    {
        $auth = DatabaseConfigService::resolveAuthConfig();
        if ($auth['key'] === ($cfg['key'] ?? '')) {
            return null;
        }

        $db = new Database($auth);
        $conn = $db->connect();
        if (!$conn instanceof \mysqli) {
            return ['bad', 'Failed', 'Admins sign in against the ' . $auth['key'] . ' site\'s database, which isn\'t reachable. ' . DatabaseConfigService::connectErrorMessage($db->last_connect_error)];
        }
        $conn->close();

        return ['good', 'Connected', 'Admins sign in against the ' . $auth['key'] . ' site\'s database.'];
    }

    private static function databaseSize(\mysqli $conn): array
    {
        $result = $conn->query('SELECT COUNT(*), COALESCE(SUM(DATA_LENGTH + INDEX_LENGTH), 0) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()');
        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;
        $tables = (int) ($row[0] ?? 0);

        return ['info', FormatHelper::bytes((int) ($row[1] ?? 0)) . ' · ' . number_format($tables) . ' tables', 'The whole WordPress database, store included.'];
    }

    // —— Security ——

    /**
     * @return list<array<string, string>|null>
     */
    private static function securityChecks(bool $local): array
    {
        $probes = self::probeSite();
        $web = $probes !== null && self::served($probes['control'] ?? null) ? $probes : null;

        return [
            self::attempt('https', 'HTTPS', static fn (): array => self::https($local, $web)),
            self::attempt('web', 'Web checks', static fn (): ?array => self::webReach($probes)),
            self::attempt('private', 'Private files', static fn (): ?array => $web === null ? null : self::privateFiles($web)),
            self::attempt('listing', 'Folder listings', static fn (): ?array => $web === null ? null : self::folderListing($web)),
            self::attempt('developer', 'Developer files', static fn (): ?array => $web === null ? null : self::developerFiles($web)),
            self::attempt('headers', 'Security headers', static fn (): ?array => $web === null ? null : self::securityHeaders($web['control'])),
            self::attempt('signature', 'Server versions shown', static fn (): ?array => $web === null ? null : self::serverSignature($web['control'], $local)),
            self::attempt('errorDisplay', 'PHP error display', static fn (): array => self::errorDisplay($local)),
            self::attempt('debug', 'Debug Mode', static fn (): array => self::debugMode($local)),
            self::attempt('dbLogin', 'Database login', static fn (): array => self::databaseLogin($local)),
            self::attempt('configCopies', 'Leftover config copies', static fn (): array => self::configCopies()),
            self::attempt('configPermissions', 'config.php permissions', static fn (): ?array => self::configPermissions()),
            self::attempt('signIn', 'Sign-in protection', static fn (): array => self::signInProtection()),
            self::attempt('admins', 'Admin accounts', static fn (): array => self::inactiveAdmins(self::adminAccounts())),
            self::attempt('passwords', 'Admin password storage', static fn (): ?array => self::passwordStorage(self::adminAccounts())),
            self::attempt('devConsole', 'Dev Console access', static fn (): array => self::devConsoleAccess(self::adminAccounts())),
        ];
    }

    /**
     * Requests the site's own URLs the way a visitor would: anonymous, no redirects followed.
     *
     * @return array<string, array{status:int,headers:array<string,string>,body:string,error:string}>|null
     */
    private static function probeSite(): ?array
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (!preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/', $host) || !function_exists('curl_multi_init')) {
            return null;
        }

        $control = '';
        foreach (self::CONTROL_PATHS as $path) {
            if (is_file(FC_ROOT . '/' . $path)) {
                $control = $path;
                break;
            }
        }
        if ($control === '') {
            return null;
        }

        // api.php answers from public/, so the app root is two levels above the script.
        $root = UrlHelper::plannerAppBaseFromAdminScript() . '/';
        $base = (RequestHelper::isHttps() ? 'https' : 'http') . '://' . $host . $root;

        $urls = ['control' => $base . $control];
        if (RequestHelper::isHttps()) {
            $urls['http'] = 'http://' . preg_replace('/:\d+$/', '', $host) . $root . $control;
        }
        foreach (self::PRIVATE_PATHS as $path) {
            if (is_file(FC_ROOT . '/' . $path)) {
                $urls['private:' . $path] = $base . $path;
            }
        }
        if (is_dir(GalleryModel::uploadDir())) {
            $urls['listing'] = $base . 'public/assets/uploads/';
        }
        foreach (self::DEVELOPER_PATHS as $path) {
            if (is_file(FC_ROOT . '/' . $path)) {
                $urls['developer:' . $path] = $base . $path;
            }
        }

        return self::fetchAll($urls);
    }

    /**
     * @param array<string, string> $urls
     * @return array<string, array{status:int,headers:array<string,string>,body:string,error:string}>
     */
    private static function fetchAll(array $urls): array
    {
        $multi = curl_multi_init();
        $handles = [];
        $results = [];
        foreach ($urls as $key => $url) {
            $results[$key] = ['status' => 0, 'headers' => [], 'body' => '', 'error' => ''];
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_NOSIGNAL => true,
                CURLOPT_USERAGENT => 'FC Site Health',
                CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$results, $key): int {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $results[$key]['headers'][strtolower(trim($parts[0]))] = trim($parts[1]);
                    }

                    return strlen($line);
                },
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$results, $key): int {
                    $room = self::PROBE_BODY_BYTES - strlen($results[$key]['body']);
                    if ($room > 0) {
                        $results[$key]['body'] .= substr($chunk, 0, $room);
                    }

                    return strlen($chunk);
                },
            ]);
            curl_multi_add_handle($multi, $handle);
            $handles[$key] = $handle;
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running > 0) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === CURLM_OK);

        $errors = [];
        while (($done = curl_multi_info_read($multi)) !== false) {
            if ($done['result'] !== CURLE_OK) {
                $errors[spl_object_id($done['handle'])] = curl_strerror($done['result']);
            }
        }

        foreach ($handles as $key => $handle) {
            $results[$key]['status'] = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $results[$key]['error'] = (string) ($errors[spl_object_id($handle)] ?? '');
            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
        }
        curl_multi_close($multi);

        return $results;
    }

    private static function served(?array $response): bool
    {
        return $response !== null && $response['status'] >= 200 && $response['status'] < 300;
    }

    private static function https(bool $local, ?array $web): array
    {
        if (!RequestHelper::isHttps()) {
            return $local
                ? ['info', 'Off', 'Fine on a local copy; the live site must use https://.']
                : ['bad', 'Off', 'The admin is open over plain http://, so passwords and customer details cross the network unencrypted.'];
        }
        if ($web !== null && self::served($web['http'] ?? null)) {
            return ['warn', 'On', 'The site still answers on plain http:// instead of redirecting to https://.'];
        }

        return ['good', 'On', ''];
    }

    private static function webReach(?array $probes): ?array
    {
        if ($probes === null) {
            return ['warn', 'Skipped', 'The server couldn\'t test its own site from this request, so the file, folder and header checks were skipped.'];
        }

        $control = $probes['control'];
        if (self::served($control)) {
            return null;
        }
        $reason = $control['error'] !== '' ? $control['error'] : 'HTTP ' . $control['status'];

        return ['warn', 'Couldn\'t run', 'The server couldn\'t open its own site (' . $reason . '), so the file, folder and header checks were skipped. Some hosts and firewalls block this.'];
    }

    private static function privateFiles(array $web): array
    {
        $tried = [];
        $open = [];
        foreach ($web as $key => $response) {
            if (!str_starts_with($key, 'private:')) {
                continue;
            }
            $path = substr($key, strlen('private:'));
            $tried[] = $path;
            if (self::served($response)) {
                $open[] = $path;
            }
        }

        if ($open !== []) {
            return ['bad', count($open) . ' open', 'These open in a browser: ' . implode(', ', $open) . '. The .htaccess rules that hide config.php, writable/, app/ and dot-files aren\'t working on this server.'];
        }

        return ['good', 'Blocked', 'Tried ' . implode(', ', $tried) . '.'];
    }

    private static function folderListing(array $web): ?array
    {
        $response = $web['listing'] ?? null;
        if ($response === null) {
            return null;
        }
        if (self::served($response) && stripos($response['body'], 'Index of') !== false) {
            return ['bad', 'On', 'public/assets/uploads/ lists every file in it. Add "Options -Indexes" to .htaccess.'];
        }

        return ['good', 'Off', ''];
    }

    private static function developerFiles(array $web): ?array
    {
        $tried = 0;
        $open = [];
        foreach ($web as $key => $response) {
            if (!str_starts_with($key, 'developer:')) {
                continue;
            }
            $tried++;
            if (self::served($response)) {
                $open[] = substr($key, strlen('developer:'));
            }
        }

        if ($tried === 0) {
            return null;
        }
        if ($open === []) {
            return ['good', 'Blocked', ''];
        }

        $builds = array_filter($open, static fn (string $path): bool => str_starts_with($path, 'build/'));
        $detail = implode(' and ', $open) . ' can be opened from the web'
            . ($builds !== [] ? ', and build/ also holds the build scripts' : '')
            . '. Block build/, tests/ and the .md notes in .htaccess.';

        return ['warn', count($open) . ' public', $detail];
    }

    private static function securityHeaders(array $control): array
    {
        $headers = $control['headers'];
        $baseline = [];
        if (strtolower($headers['x-content-type-options'] ?? '') !== 'nosniff') {
            $baseline[] = 'X-Content-Type-Options';
        }
        if (!isset($headers['x-frame-options']) && !str_contains(strtolower($headers['content-security-policy'] ?? ''), 'frame-ancestors')) {
            $baseline[] = 'X-Frame-Options';
        }
        if (!isset($headers['referrer-policy'])) {
            $baseline[] = 'Referrer-Policy';
        }
        $hsts = RequestHelper::isHttps() && !isset($headers['strict-transport-security']);

        if ($baseline === [] && !$hsts) {
            return ['good', 'All set', ''];
        }

        $detail = [];
        if ($baseline !== []) {
            $detail[] = 'No ' . implode(', ', $baseline) . '. The root .htaccess sends these, so mod_headers is probably off on this server.';
        }
        if ($hsts) {
            $detail[] = 'No Strict-Transport-Security (HSTS), which keeps browsers on https://. Turn it on at the host or in Cloudflare.';
        }
        $missing = count($baseline) + ($hsts ? 1 : 0);

        return ['warn', $missing . ' missing', implode(' ', $detail)];
    }

    private static function serverSignature(array $control, bool $local): array
    {
        $shown = [];
        $server = (string) ($control['headers']['server'] ?? '');
        if (preg_match('/\d/', $server)) {
            $shown[] = 'Server: ' . $server;
        }
        if (isset($control['headers']['x-powered-by'])) {
            $shown[] = 'X-Powered-By: ' . $control['headers']['x-powered-by'];
        }
        if ($shown === []) {
            return ['good', 'Hidden', ''];
        }

        return [$local ? 'info' : 'warn', 'Shown', implode(' · ', $shown) . '. Exact versions tell attackers which known holes to try; ask the host to set ServerTokens Prod and expose_php = Off.'];
    }

    private static function errorDisplay(bool $local): array
    {
        // php.ini's own value: api.php and public/index.php switch display off for themselves at runtime.
        $display = strtolower(trim((string) (ini_get_all(null, true)['display_errors']['global_value'] ?? '')));
        if (!in_array($display, ['1', 'on', 'yes', 'true'], true)) {
            return ['good', 'Off', ''];
        }

        return [$local ? 'info' : 'bad', 'On', 'php.ini shows PHP errors to visitors. The admin hides them, but the planner and the other public pages don\'t, and the messages reveal file paths. Set display_errors = Off.'];
    }

    private static function debugMode(bool $local): array
    {
        if (!ConsoleSettings::debugMode()) {
            return ['good', 'Off', ''];
        }

        return [$local ? 'info' : 'warn', 'On', 'Debug Mode records every request\'s queries and errors, which slows the site down. Turn it off in Settings → Console when you\'re done.'];
    }

    private static function databaseLogin(bool $local): array
    {
        $cfg = DatabaseConfigService::resolveConfig();
        if ($cfg['password'] === '') {
            return [$local ? 'info' : 'bad', 'No password', 'The site\'s database login has no password.'];
        }
        if (strtolower($cfg['username']) === 'root') {
            return [$local ? 'info' : 'warn', 'root', 'The site connects to MySQL as root, which can read and change every database on the server. Give it a user of its own.'];
        }

        return ['good', 'Own user', ''];
    }

    private static function configCopies(): array
    {
        $config = ConsoleSettings::configPath();
        $copies = array_merge(glob($config . '.tmp.*') ?: [], glob($config . '.bak*') ?: []);
        if ($copies === []) {
            return ['good', 'None', ''];
        }

        return ['warn', count($copies) . ' found', 'Left behind by an interrupted save, with the same database passwords inside: ' . implode(', ', array_map('basename', $copies)) . '. Delete them.'];
    }

    private static function configPermissions(): ?array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }
        $mode = @fileperms(ConsoleSettings::configPath());
        if ($mode === false) {
            return null;
        }

        $mode &= 0777;
        $value = sprintf('%o', $mode);
        if ($mode & 0002) {
            return ['bad', $value, 'Any account on the server can change config.php. Set it to 640 or 600.'];
        }
        if ($mode & 0004) {
            return ['warn', $value, 'Other accounts on this server can read config.php and the database passwords in it. Set it to 640 or 600.'];
        }

        return ['good', $value, ''];
    }

    private static function signInProtection(): array
    {
        // Known gaps (see CLAUDE.md): no lockout, and enforceSessionTtl() enforces nothing.
        return ['warn', 'Not limited', 'Failed sign-ins aren\'t rate-limited, so a password can be guessed over and over, and a sign-in lasts until you sign out. Both need a code change.'];
    }

    /**
     * Everyone who can open the admin: the Super Admin plus every user of a role with an FC grant.
     *
     * @return array{users:array<int,string>,devConsole:list<string>}
     */
    private static function adminAccounts(): array
    {
        static $accounts = null;
        if ($accounts !== null) {
            return $accounts;
        }

        $users = [];
        $devConsole = [];
        foreach (array_keys(UserModel::roleCounts()['roles']) as $role) {
            $role = (string) $role;
            $matrix = GroupPermissionsModel::get($role);
            if (!GroupPermissionsModel::matrixHasGrant($matrix)) {
                continue;
            }
            if (GroupPermissionsModel::getPath($matrix, 'settings.dev_console')) {
                $devConsole[] = ucwords(str_replace(['_', '-'], ' ', $role));
            }
            foreach (UserModel::list('', $role, 500)['items'] as $item) {
                $users[(int) $item['id']] = $item['display_name'] !== '' ? (string) $item['display_name'] : (string) $item['user_login'];
            }
        }

        $super = PermissionService::superAdminUser();
        if ($super !== null) {
            $users[$super['ID']] = $super['display_name'];
        }

        return $accounts = ['users' => $users, 'devConsole' => $devConsole];
    }

    /**
     * @param array{users:array<int,string>,devConsole:list<string>} $accounts
     */
    private static function inactiveAdmins(array $accounts): array
    {
        $users = $accounts['users'];
        if ($users === []) {
            return ['info', 'None found', ''];
        }

        $lastLogin = PresenceService::lastLoginMap(array_keys($users));
        $cutoff = time() - self::INACTIVE_ADMIN_DAYS * 86400;
        $idle = [];
        foreach ($users as $id => $name) {
            $at = (int) ($lastLogin[$id] ?? 0);
            if ($at < $cutoff) {
                $idle[] = $name . ' (' . ($at > 0 ? 'last ' . date('j M Y', $at) : 'never') . ')';
            }
        }

        $value = count($users) . ' with admin access';
        if ($idle !== []) {
            return ['warn', $value, 'No FC sign-in for ' . self::INACTIVE_ADMIN_DAYS . '+ days: ' . self::listSome($idle, 6) . '. Take admin access away from accounts that no longer need it.'];
        }

        return ['good', $value, 'Everyone has signed in within ' . self::INACTIVE_ADMIN_DAYS . ' days.'];
    }

    /**
     * Reads only each hash's format prefix; no hash leaves this method.
     *
     * @param array{users:array<int,string>,devConsole:list<string>} $accounts
     */
    private static function passwordStorage(array $accounts): ?array
    {
        $ids = array_map('intval', array_keys($accounts['users']));
        $conn = $ids === [] ? null : UserModel::db();
        if (!$conn instanceof \mysqli) {
            return null;
        }

        $total = 0;
        $old = 0;
        try {
            $result = $conn->query('SELECT user_pass FROM `' . self::ident(UserModel::usersTable()) . '` WHERE ID IN (' . implode(',', $ids) . ')');
            while ($result instanceof \mysqli_result && ($row = $result->fetch_row())) {
                $total++;
                $hash = (string) $row[0];
                // phpass ($P$/$H$) and bare MD5 predate WordPress 6.8's bcrypt.
                if (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$') || preg_match('/^[a-f0-9]{32}$/i', $hash)) {
                    $old++;
                }
            }
        } finally {
            $conn->close();
        }

        if ($total === 0) {
            return null;
        }
        if ($old > 0) {
            return ['warn', $old . ' of ' . $total . ' in the old format', 'These admin passwords are stored in WordPress\'s older format, which is far quicker to crack if the database ever leaks. WordPress 6.8 and later upgrades each one the next time that person signs in to WordPress.'];
        }

        return ['good', 'Modern hashing', 'Every admin password uses bcrypt or stronger.'];
    }

    /**
     * @param array{users:array<int,string>,devConsole:list<string>} $accounts
     */
    private static function devConsoleAccess(array $accounts): array
    {
        if ($accounts['devConsole'] === []) {
            return ['good', 'Super Admin only', ''];
        }

        return ['warn', implode(', ', $accounts['devConsole']), 'These roles can run git commands (pull, reset and more) from Settings → Console. Keep it to the people who deploy, in Group Permissions.'];
    }

    /**
     * @param list<string> $items
     */
    private static function listSome(array $items, int $limit = 3): string
    {
        $shown = array_slice($items, 0, $limit);
        $more = count($items) - count($shown);

        return implode(', ', $shown) . ($more > 0 ? ' and ' . $more . ' more' : '');
    }
}
