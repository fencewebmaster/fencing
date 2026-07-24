<?php
/**
 * Shared database configuration from fc/config.php (PDO / mysqli).
 * Does not load WordPress or read wp-config.php.
 *
 * Frontend: always host-based (never reads admin site session).
 * Admin: may use fc_admin_site_key() when FC_ADMIN_ROOT is defined.
 */

declare(strict_types=1);

/**
 * Load FC app config array from fc/config.php.
 *
 * @return array<string, mixed>
 */
function fc_db_load_app_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $path = dirname(__DIR__) . '/config.php';
    if (!is_readable($path)) {
        $config = [];

        return $config;
    }

    /** @var array<string, mixed>|null $loaded */
    $loaded = null;
    (static function () use ($path, &$loaded): void {
        include $path;
        $loaded = isset($config) && is_array($config) ? $config : [];
    })();

    $config = is_array($loaded) ? $loaded : [];

    return $config;
}

/**
 * MySQL config key derived from the current HTTP host (frontend + default).
 */
function fc_db_host_mysql_key(): string
{
    $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
    $host = parse_url('//' . $hostHeader, PHP_URL_HOST);
    if (!$host) {
        $host = $hostHeader;
    }
    $host = strtolower((string) $host);

    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '') {
        return 'localhost';
    }

    $pathinfo = pathinfo($host);

    return (string) ($pathinfo['filename'] ?? 'localhost');
}

/**
 * Resolve credentials for an explicit mysql key from config.php.
 *
 * @return array{host:string,database:string,username:string,password:string,prefix:string,source:string,key:string}
 */
function fc_db_resolve_config_for_key(string $siteKey): array
{
    $app = fc_db_load_app_config();
    $mysql = isset($app['mysql']) && is_array($app['mysql']) ? $app['mysql'] : [];

    $key = trim($siteKey);
    if ($key === '' || !isset($mysql[$key]) || !is_array($mysql[$key])) {
        $key = 'localhost';
        if (!isset($mysql[$key]) || !is_array($mysql[$key])) {
            $key = (string) (array_key_first($mysql) ?? 'localhost');
        }
    }

    $row = isset($mysql[$key]) && is_array($mysql[$key]) ? $mysql[$key] : [];
    if ($row === [] && isset($mysql['localhost']) && is_array($mysql['localhost'])) {
        $key = 'localhost';
        $row = $mysql['localhost'];
    }

    return [
        'host' => 'localhost',
        'database' => (string) ($row['database'] ?? ''),
        'username' => (string) ($row['username'] ?? ''),
        'password' => (string) ($row['password'] ?? ''),
        'prefix' => (string) ($row['prefix'] ?? 'wp_'),
        'source' => 'fc/config.php',
        'key' => $key,
    ];
}

/**
 * Default DB config for the current request.
 *
 * - Frontend / non-admin: always host-based.
 * - Admin (FC_ADMIN_ROOT): uses session site key when set; otherwise host-based.
 *
 * @return array{host:string,database:string,username:string,password:string,prefix:string,source:string,key?:string}
 */
function fc_db_resolve_config(): array
{
    if (defined('FC_ADMIN_ROOT') && function_exists('fc_admin_site_key')) {
        $adminKey = fc_admin_site_key();
        if ($adminKey !== '') {
            return fc_db_resolve_config_for_key($adminKey);
        }
    }

    return fc_db_resolve_config_for_key(fc_db_host_mysql_key());
}

/**
 * Auth DB config (pinned login DB in admin; host-based otherwise).
 *
 * @return array{host:string,database:string,username:string,password:string,prefix:string,source:string,key:string}
 */
function fc_db_resolve_auth_config(): array
{
    if (defined('FC_ADMIN_ROOT') && function_exists('fc_admin_auth_db_key')) {
        $authKey = fc_admin_auth_db_key();
        if ($authKey !== '') {
            return fc_db_resolve_config_for_key($authKey);
        }
    }

    return fc_db_resolve_config_for_key(fc_db_host_mysql_key());
}

/**
 * @return array{conn:?mysqli,error:string}
 */
function fc_db_connect_mysqli(array $cfg): array
{
    $hosts = [];
    $primary = (string) ($cfg['host'] ?? 'localhost');
    $hosts[] = $primary;

    if ($primary === 'localhost') {
        $hosts[] = '127.0.0.1';
    } elseif ($primary === '127.0.0.1') {
        $hosts[] = 'localhost';
    }

    $hosts = array_values(array_unique($hosts));
    $lastError = '';

    foreach ($hosts as $host) {
        try {
            $conn = @new mysqli(
                $host,
                (string) $cfg['username'],
                (string) $cfg['password'],
                (string) $cfg['database']
            );
        } catch (\mysqli_sql_exception $e) {
            $lastError = $e->getMessage();
            continue;
        }

        if ($conn->connect_error) {
            $lastError = $conn->connect_error;
            continue;
        }

        return ['conn' => $conn, 'error' => ''];
    }

    return ['conn' => null, 'error' => $lastError];
}

function fc_db_connect_error_message(string $technicalError = ''): string
{
    $hint = 'Start MySQL from the XAMPP Control Panel';
    $lower = strtolower($technicalError);

    if ($technicalError === '' || str_contains($lower, 'actively refused') || str_contains($lower, 'connection refused')) {
        return $hint . ' (MySQL is not running on port 3306).';
    }

    if (str_contains($lower, 'access denied')) {
        return 'Database login failed. Check credentials in fc/config.php.';
    }

    if (str_contains($lower, 'unknown database')) {
        return 'Database not found. Create the database or update fc/config.php.';
    }

    return 'Could not connect to the database. ' . $hint . '.';
}

/**
 * Shared PDO connection (WooCommerce MySQL schema via fc/config.php — never loads WordPress).
 * Cached per database fingerprint so auth + data DBs can coexist in one request.
 *
 * @return array{pdo:?PDO,error:string,prefix:string}
 */
function fc_db_pdo(?array $cfg = null): array
{
    static $cache = [];

    $cfg = $cfg ?? fc_db_resolve_config();
    $cacheKey = implode("\0", [
        (string) ($cfg['host'] ?? ''),
        (string) ($cfg['database'] ?? ''),
        (string) ($cfg['username'] ?? ''),
        (string) ($cfg['prefix'] ?? 'wp_'),
    ]);

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $hosts = [];
    $primary = (string) ($cfg['host'] ?? 'localhost');
    $hosts[] = $primary;
    if ($primary === 'localhost') {
        $hosts[] = '127.0.0.1';
    } elseif ($primary === '127.0.0.1') {
        $hosts[] = 'localhost';
    }
    $hosts = array_values(array_unique($hosts));

    $lastError = '';
    foreach ($hosts as $host) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $host,
            (string) $cfg['database']
        );
        try {
            $pdo = new PDO($dsn, (string) $cfg['username'], (string) $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $result = [
                'pdo' => $pdo,
                'error' => '',
                'prefix' => (string) ($cfg['prefix'] ?? 'wp_'),
            ];
            $cache[$cacheKey] = $result;

            return $result;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
        }
    }

    $result = [
        'pdo' => null,
        'error' => $lastError,
        'prefix' => (string) ($cfg['prefix'] ?? 'wp_'),
    ];
    $cache[$cacheKey] = $result;

    return $result;
}
