<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Shared database configuration from fc/config.php (PDO / mysqli).
 * Does not load WordPress or read wp-config.php.
 *
 * Frontend: always host-based (never reads admin site session).
 * Admin: may use AdminSiteRegistry::siteKey() when FC_ADMIN_ROOT is defined.
 */
final class DatabaseConfigService
{
    /**
     * Load FC app config array from fc/config.php.
     *
     * @return array<string, mixed>
     */
    public static function loadAppConfig(): array
    {
        static $config = null;
        if (is_array($config)) {
            return $config;
        }

        $path = dirname(__DIR__, 3) . '/config.php';
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
     * Flat site-key => mysql-row map, extracted from the nested `sites.{key}.mysql`
     * structure. Every reader of per-site DB credentials goes through this so there's one
     * place that knows the shape of config.php's `sites` tree.
     *
     * @param array<string, mixed> $app
     * @return array<string, array<string, mixed>>
     */
    public static function mysqlBySite(array $app): array
    {
        $sites = isset($app['sites']) && is_array($app['sites']) ? $app['sites'] : [];
        $mysql = [];
        foreach ($sites as $key => $site) {
            if (is_string($key) && $key !== '' && is_array($site) && is_array($site['mysql'] ?? null)) {
                $mysql[$key] = $site['mysql'];
            }
        }

        return $mysql;
    }

    /**
     * MySQL config key derived from the current HTTP host (frontend + default).
     */
    public static function hostMysqlKey(): string
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
    public static function resolveConfigForKey(string $siteKey): array
    {
        $app = self::loadAppConfig();
        $mysql = self::mysqlBySite($app);

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
    public static function resolveConfig(): array
    {
        if (defined('FC_ADMIN_ROOT')) {
            $adminKey = AdminSiteRegistry::siteKey();
            if ($adminKey !== '') {
                return self::resolveConfigForKey($adminKey);
            }
        }

        return self::resolveConfigForKey(self::hostMysqlKey());
    }

    /**
     * Auth DB config (pinned login DB in admin; host-based otherwise).
     *
     * @return array{host:string,database:string,username:string,password:string,prefix:string,source:string,key:string}
     */
    public static function resolveAuthConfig(): array
    {
        if (defined('FC_ADMIN_ROOT')) {
            $authKey = AdminSiteRegistry::authDbKey();
            if ($authKey !== '') {
                return self::resolveConfigForKey($authKey);
            }
        }

        return self::resolveConfigForKey(self::hostMysqlKey());
    }

    /**
     * @return array{conn:?\mysqli,error:string}
     */
    public static function connectMysqli(array $cfg): array
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
                $conn = @new \mysqli(
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

            // Pin the connection charset so mysqli_real_escape_string()'s escaping can't be
            // bypassed by a multi-byte charset that swallows the escape backslash (the same
            // class of issue the classic "GBK SQL injection" technique relies on). The PDO
            // half of this service already pins utf8mb4 via its DSN; mysqli needs it explicitly.
            @$conn->set_charset('utf8mb4');

            return ['conn' => $conn, 'error' => ''];
        }

        return ['conn' => null, 'error' => $lastError];
    }

    public static function connectErrorMessage(string $technicalError = ''): string
    {
        $hint = 'Start MySQL from the Server';
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
     * @return array{pdo:?\PDO,error:string,prefix:string}
     */
    public static function pdo(?array $cfg = null): array
    {
        static $cache = [];

        $cfg = $cfg ?? self::resolveConfig();
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
                $pdo = new \PDO($dsn, (string) $cfg['username'], (string) $cfg['password'], [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $result = [
                    'pdo' => $pdo,
                    'error' => '',
                    'prefix' => (string) ($cfg['prefix'] ?? 'wp_'),
                ];
                $cache[$cacheKey] = $result;

                return $result;
            } catch (\PDOException $e) {
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
}
