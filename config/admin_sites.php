<?php
/**
 * FC Admin — site switcher context (admin DB only; does not affect frontend).
 */

declare(strict_types=1);

const FC_ADMIN_SITE_SESSION_KEY = 'fc_admin_site_key';
const FC_ADMIN_AUTH_DB_SESSION_KEY = 'fc_admin_auth_db_key';

/**
 * MySQL keys available in fc/config.php.
 *
 * @return list<string>
 */
function fc_admin_mysql_keys(): array
{
    if (!function_exists('fc_db_load_app_config')) {
        require_once __DIR__ . '/db_config.php';
    }

    $app = fc_db_load_app_config();
    $mysql = isset($app['mysql']) && is_array($app['mysql']) ? $app['mysql'] : [];
    $keys = [];
    foreach ($mysql as $key => $row) {
        if (!is_string($key) || $key === '' || !is_array($row)) {
            continue;
        }
        if (trim((string) ($row['database'] ?? '')) === '') {
            continue;
        }
        $keys[] = $key;
    }

    return $keys;
}

function fc_admin_is_valid_mysql_key(string $key): bool
{
    $key = trim($key);

    return $key !== '' && in_array($key, fc_admin_mysql_keys(), true);
}

/**
 * Map a site domain to a mysql config key (same rules as host resolution).
 */
function fc_admin_mysql_key_from_domain(string $domain): string
{
    $host = parse_url('//' . $domain, PHP_URL_HOST);
    if (!$host) {
        $host = $domain;
    }
    $host = strtolower((string) $host);
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '') {
        return 'localhost';
    }

    $pathinfo = pathinfo($host);

    return (string) ($pathinfo['filename'] ?? 'localhost');
}

/**
 * Sites available in the admin switcher (sites() ∩ mysql credentials).
 * Skips staging-style ids (e.g. 1.1) unless they are the only match for a mysql key.
 *
 * @return list<array{key:string,name:string,logo:string,domain:string,id:int|float}>
 */
function fc_admin_sites_list(): array
{
    if (!function_exists('sites')) {
        require_once __DIR__ . '/helpers.php';
    }

    $mysqlKeys = fc_admin_mysql_keys();
    $mysqlLookup = array_fill_keys($mysqlKeys, true);
    $all = sites();
    if (!is_array($all)) {
        return [];
    }

    $byKey = [];
    foreach ($all as $row) {
        if (!is_array($row)) {
            continue;
        }
        $domain = (string) ($row['domain'] ?? '');
        if ($domain === '') {
            continue;
        }
        $key = fc_admin_mysql_key_from_domain($domain);
        if (!isset($mysqlLookup[$key])) {
            continue;
        }

        $id = $row['id'] ?? 0;
        $isStaging = is_float($id) || (is_numeric($id) && str_contains((string) $id, '.'));

        if (isset($byKey[$key])) {
            // Prefer non-staging production row when both exist.
            if ($isStaging) {
                continue;
            }
        }

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $name = ucwords(str_replace(['.', '-', '_'], ' ', $key));
        }

        $byKey[$key] = [
            'key' => $key,
            'name' => $name,
            'logo' => (string) ($row['logo'] ?? ''),
            'domain' => $domain,
            'id' => $id,
        ];
    }

    // Ensure every mysql key with credentials can appear (even without sites() row).
    foreach ($mysqlKeys as $key) {
        if (isset($byKey[$key])) {
            continue;
        }
        $byKey[$key] = [
            'key' => $key,
            'name' => $key === 'localhost' ? 'Localhost' : ucwords(str_replace(['.', '-', '_'], ' ', $key)),
            'logo' => 'assets/img/logo/fencesperth.webp',
            'domain' => $key === 'localhost' ? 'localhost' : $key,
            'id' => 0,
        ];
    }

    $list = array_values($byKey);
    usort($list, static function (array $a, array $b): int {
        if ($a['key'] === 'localhost') {
            return -1;
        }
        if ($b['key'] === 'localhost') {
            return 1;
        }

        return strcasecmp((string) $a['name'], (string) $b['name']);
    });

    // Hide localhost from the switcher when the admin is not running on localhost.
    if (!function_exists('fc_db_host_mysql_key')) {
        require_once __DIR__ . '/db_config.php';
    }
    if (fc_db_host_mysql_key() !== 'localhost') {
        $list = array_values(array_filter(
            $list,
            static fn(array $site): bool => ($site['key'] ?? '') !== 'localhost'
        ));
    }

    return $list;
}

function fc_admin_site_key(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    $key = isset($_SESSION[FC_ADMIN_SITE_SESSION_KEY]) ? (string) $_SESSION[FC_ADMIN_SITE_SESSION_KEY] : '';
    if ($key === '' || !fc_admin_is_valid_mysql_key($key)) {
        return '';
    }

    return $key;
}

function fc_admin_set_site_key(string $key): bool
{
    $key = trim($key);
    if (!fc_admin_is_valid_mysql_key($key)) {
        return false;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    $_SESSION[FC_ADMIN_SITE_SESSION_KEY] = $key;

    return true;
}

function fc_admin_auth_db_key(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    $key = isset($_SESSION[FC_ADMIN_AUTH_DB_SESSION_KEY]) ? (string) $_SESSION[FC_ADMIN_AUTH_DB_SESSION_KEY] : '';
    if ($key === '' || !fc_admin_is_valid_mysql_key($key)) {
        return '';
    }

    return $key;
}

function fc_admin_set_auth_db_key(string $key): bool
{
    $key = trim($key);
    if (!fc_admin_is_valid_mysql_key($key)) {
        return false;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    $_SESSION[FC_ADMIN_AUTH_DB_SESSION_KEY] = $key;

    return true;
}

function fc_admin_clear_site_context(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    unset($_SESSION[FC_ADMIN_SITE_SESSION_KEY], $_SESSION[FC_ADMIN_AUTH_DB_SESSION_KEY]);
}

/**
 * Login / home site key (auth DB), used for quick-switch back.
 */
function fc_admin_home_site_key(): string
{
    $authKey = fc_admin_auth_db_key();
    if ($authKey !== '') {
        return $authKey;
    }
    if (!function_exists('fc_db_host_mysql_key')) {
        require_once __DIR__ . '/db_config.php';
    }

    return fc_db_host_mysql_key();
}

/**
 * @return array{key:string,name:string,logo:string,domain:string,id:int|float}|null
 */
function fc_admin_home_site(): ?array
{
    $key = fc_admin_home_site_key();
    if ($key === '') {
        return null;
    }

    foreach (fc_admin_sites_list() as $site) {
        if (($site['key'] ?? '') === $key) {
            return $site;
        }
    }

    return [
        'key' => $key,
        'name' => $key === 'localhost' ? 'Localhost' : ucwords(str_replace(['.', '-', '_'], ' ', $key)),
        'logo' => 'assets/img/logo/fencesperth.webp',
        'domain' => $key === 'localhost' ? 'localhost' : $key,
        'id' => 0,
    ];
}

/**
 * True when admin data DB differs from the login (auth) DB.
 */
function fc_admin_is_site_switched(): bool
{
    $siteKey = fc_admin_site_key();
    if ($siteKey === '') {
        return false;
    }

    return $siteKey !== fc_admin_home_site_key();
}

/**
 * Routes allowed while viewing a switched site (data DB ≠ auth DB).
 */
function fc_admin_site_switched_route_allowed(string $route): bool
{
    $route = trim($route, '/');
    if ($route === '' || $route === 'dashboard') {
        return true;
    }
    if ($route === 'planner-entries' || str_starts_with($route, 'planner-entries/')) {
        return true;
    }
    if ($route === 'users/switch-site') {
        return true;
    }
    if ($route === 'users/switch-back') {
        return true;
    }
    if ($route === 'logout' || $route === 'login') {
        return true;
    }

    return false;
}

/**
 * API modules allowed while viewing a switched site.
 */
function fc_admin_site_switched_api_allowed(string $module): bool
{
    $module = trim($module);

    return in_array($module, [
        'auth',
        'cache',
        'cacheController',
        'dashboard',
        'dashboardController',
        'entries',
        'entriesController',
    ], true);
}

/**
 * @return array{key:string,name:string,logo:string,domain:string,id:int|float}|null
 */
function fc_admin_current_site(): ?array
{
    $key = fc_admin_site_key();
    if ($key === '') {
        if (!function_exists('fc_db_host_mysql_key')) {
            require_once __DIR__ . '/db_config.php';
        }
        $key = fc_db_host_mysql_key();
    }

    foreach (fc_admin_sites_list() as $site) {
        if (($site['key'] ?? '') === $key) {
            return $site;
        }
    }

    return [
        'key' => $key !== '' ? $key : 'localhost',
        'name' => $key !== '' ? $key : 'Localhost',
        'logo' => 'assets/img/logo/fencesperth.webp',
        'domain' => $key !== '' ? $key : 'localhost',
        'id' => 0,
    ];
}
