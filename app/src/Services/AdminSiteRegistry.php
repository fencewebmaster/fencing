<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * FC Admin — site switcher context (admin DB only; does not affect frontend).
 */
final class AdminSiteRegistry
{
    private const SITE_SESSION_KEY = 'fc_admin_site_key';
    private const AUTH_DB_SESSION_KEY = 'fc_admin_auth_db_key';
    private const DEFAULT_SITE_LOGO = 'public/assets/img/logo/fencesperth.webp';

    /**
     * MySQL keys available in fc/config.php.
     *
     * @return list<string>
     */
    public static function mysqlKeys(): array
    {
        $app = DatabaseConfigService::loadAppConfig();
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

    public static function isValidMysqlKey(string $key): bool
    {
        $key = trim($key);

        return $key !== '' && in_array($key, self::mysqlKeys(), true);
    }

    /**
     * Map a site domain to a mysql config key (same rules as host resolution).
     */
    public static function mysqlKeyFromDomain(string $domain): string
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
     * @return list<array{key:string,name:string,logo:string,domain:string,id:int|float,supplier:string}>
     */
    public static function sitesList(): array
    {
        $mysqlKeys = self::mysqlKeys();
        $mysqlLookup = array_fill_keys($mysqlKeys, true);
        $logoOverrides = SiteRegistryService::logoOverrides();
        $all = SiteRegistryService::all();
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
            $key = self::mysqlKeyFromDomain($domain);
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
                'logo' => SiteRegistryService::logoForKey($key, (string) ($row['logo'] ?? ''), $logoOverrides),
                'domain' => $domain,
                'id' => $id,
                'supplier' => strtoupper(trim((string) ($row['supplier'] ?? ''))),
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
                'logo' => SiteRegistryService::logoForKey($key, self::DEFAULT_SITE_LOGO, $logoOverrides),
                'domain' => $key === 'localhost' ? 'localhost' : $key,
                'id' => 0,
                'supplier' => '',
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
        if (DatabaseConfigService::hostMysqlKey() !== 'localhost') {
            $list = array_values(array_filter(
                $list,
                static fn(array $site): bool => ($site['key'] ?? '') !== 'localhost'
            ));
        }

        return $list;
    }

    public static function siteKey(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }
        $key = isset($_SESSION[self::SITE_SESSION_KEY]) ? (string) $_SESSION[self::SITE_SESSION_KEY] : '';
        if ($key === '' || !self::isValidMysqlKey($key)) {
            return '';
        }

        return $key;
    }

    public static function setSiteKey(string $key): bool
    {
        $key = trim($key);
        if (!self::isValidMysqlKey($key)) {
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        $_SESSION[self::SITE_SESSION_KEY] = $key;

        return true;
    }

    public static function authDbKey(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }
        $key = isset($_SESSION[self::AUTH_DB_SESSION_KEY]) ? (string) $_SESSION[self::AUTH_DB_SESSION_KEY] : '';
        if ($key === '' || !self::isValidMysqlKey($key)) {
            return '';
        }

        return $key;
    }

    public static function setAuthDbKey(string $key): bool
    {
        $key = trim($key);
        if (!self::isValidMysqlKey($key)) {
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        $_SESSION[self::AUTH_DB_SESSION_KEY] = $key;

        return true;
    }

    public static function clearSiteContext(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        unset($_SESSION[self::SITE_SESSION_KEY], $_SESSION[self::AUTH_DB_SESSION_KEY]);
    }

    /**
     * Login / home site key (auth DB), used for quick-switch back.
     */
    public static function homeSiteKey(): string
    {
        $authKey = self::authDbKey();
        if ($authKey !== '') {
            return $authKey;
        }

        return DatabaseConfigService::hostMysqlKey();
    }

    /**
     * @return array{key:string,name:string,logo:string,domain:string,id:int|float,supplier:string}|null
     */
    public static function homeSite(): ?array
    {
        $key = self::homeSiteKey();
        if ($key === '') {
            return null;
        }

        foreach (self::sitesList() as $site) {
            if (($site['key'] ?? '') === $key) {
                return $site;
            }
        }

        return [
            'key' => $key,
            'name' => $key === 'localhost' ? 'Localhost' : ucwords(str_replace(['.', '-', '_'], ' ', $key)),
            'logo' => SiteRegistryService::logoForKey($key, self::DEFAULT_SITE_LOGO),
            'domain' => $key === 'localhost' ? 'localhost' : $key,
            'id' => 0,
            'supplier' => '',
        ];
    }

    /**
     * True when admin data DB differs from the login (auth) DB.
     */
    public static function isSiteSwitched(): bool
    {
        $siteKey = self::siteKey();
        if ($siteKey === '') {
            return false;
        }

        return $siteKey !== self::homeSiteKey();
    }

    /**
     * Routes allowed while viewing a switched site (data DB ≠ auth DB).
     */
    public static function siteSwitchedRouteAllowed(string $route): bool
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
    public static function siteSwitchedApiAllowed(string $module): bool
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
     * @return array{key:string,name:string,logo:string,domain:string,id:int|float,supplier:string}|null
     */
    public static function currentSite(): ?array
    {
        $key = self::siteKey();
        if ($key === '') {
            $key = DatabaseConfigService::hostMysqlKey();
        }

        foreach (self::sitesList() as $site) {
            if (($site['key'] ?? '') === $key) {
                return $site;
            }
        }

        return [
            'key' => $key !== '' ? $key : 'localhost',
            'name' => $key !== '' ? $key : 'Localhost',
            'logo' => SiteRegistryService::logoForKey($key !== '' ? $key : 'localhost', self::DEFAULT_SITE_LOGO),
            'domain' => $key !== '' ? $key : 'localhost',
            'id' => 0,
            'supplier' => '',
        ];
    }
}
