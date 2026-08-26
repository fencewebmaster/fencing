<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\UrlHelper;

/**
 * Public-facing site catalog + environment helpers.
 */
final class SiteRegistryService
{
    /**
     * @return list<string>
     */
    public static function demoStages(): array
    {
        return [
            'html',
            'dev',
            'demo',
            'staging',
            'test',
        ];
    }

    /**
     * Config site key derived from a domain (matches admin mysql key rules).
     * Staging hosts (staging.example.com) resolve to the production key (example).
     */
    public static function keyFromDomain(string $domain): string
    {
        $host = parse_url('//' . trim($domain), PHP_URL_HOST);
        if (!$host) {
            $host = trim($domain);
        }
        $host = strtolower((string) $host);
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '') {
            return 'localhost';
        }

        $pathinfo = pathinfo($host);
        $key = (string) ($pathinfo['filename'] ?? '');
        if (str_starts_with($key, 'staging.')) {
            $key = substr($key, strlen('staging.'));
        }

        return $key;
    }

    /**
     * Per-site logo overrides saved in Settings → Integrations (config.php `site_logo`).
     * Blank entries mean "no override" and are dropped by siteMap().
     *
     * @return array<string, string>
     */
    public static function logoOverrides(): array
    {
        return AppConfigService::siteMap('site_logo');
    }

    /**
     * Logo path for a config site key: settings override first, then the supplied fallback asset.
     *
     * @param array<string, string>|null $overrides Pre-loaded map (avoids re-reading config per row).
     */
    public static function logoForKey(string $key, string $fallback = '', ?array $overrides = null): string
    {
        $key = trim($key);
        if ($key === '') {
            return $fallback;
        }

        $overrides = $overrides ?? self::logoOverrides();
        $override = trim((string) ($overrides[$key] ?? ''));

        return $override !== '' ? $override : $fallback;
    }

    /**
     * Logo path for a domain. Staging hosts use their own override when set,
     * otherwise they inherit the production site's override.
     */
    public static function logoForDomain(string $domain, string $fallback = ''): string
    {
        $overrides = self::logoOverrides();
        $keys = [AdminSiteRegistry::mysqlKeyFromDomain($domain), self::keyFromDomain($domain)];

        foreach ($keys as $key) {
            $logo = self::logoForKey($key, '', $overrides);
            if ($logo !== '') {
                return $logo;
            }
        }

        return $fallback;
    }

    /**
     * Browser-ready logo URL for a site row from all() (or a bare domain), settings override applied.
     * Absolute http(s)/protocol-relative/data values pass through untouched.
     *
     * @param array<string, mixed>|string $site
     */
    public static function logoUrl(array|string $site): string
    {
        $domain = is_array($site) ? (string) ($site['domain'] ?? '') : (string) $site;
        $fallback = is_array($site) ? trim((string) ($site['logo'] ?? '')) : '';

        $logo = self::logoForDomain($domain, $fallback);
        if ($logo === '') {
            return '';
        }

        if (preg_match('#^(?:https?:)?//#i', $logo) || preg_match('/^data:/i', $logo)) {
            return $logo;
        }

        return UrlHelper::baseUrl(ltrim(str_replace('\\', '/', $logo), '/'));
    }

    /**
     * Supplier code for a site key (or domain) from config.php `supplier`.
     */
    public static function supplier(string $siteKeyOrDomain, string $fallback = ''): string
    {
        $input = trim($siteKeyOrDomain);
        if ($input === '') {
            return strtoupper(trim($fallback));
        }

        $siteKey = $input;
        if (str_contains($input, '.') || strcasecmp($input, 'localhost') === 0) {
            // Domain-like input → resolve config key dynamically.
            $siteKey = self::keyFromDomain($input);
        }

        $value = AppConfigService::siteValue('supplier', $siteKey, $fallback);

        return strtoupper(trim($value));
    }

    /**
     * WordPress/WooCommerce base URL when the planner runs on localhost.
     * Derived from the current request path: everything before "/fc".
     * e.g. /wp/fencing/fc/project-plan → http://localhost/wp/fencing
     */
    public static function wpSiteUrl(): ?string
    {
        $hostHeader = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = parse_url('//' . $hostHeader, PHP_URL_HOST);

        if (!$host) {
            $host = $hostHeader;
        }

        if (!in_array($host, ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        }

        $path = '/' . trim($path, '/');
        $fcPos = stripos($path, '/fc');

        if ($fcPos === false) {
            return null;
        }

        $wpBase = substr($path, 0, $fcPos);

        return $scheme . '://' . $hostHeader . rtrim($wpBase, '/');
    }

    /**
     * Replace localhost site URL with the path-derived WP base when applicable.
     *
     * @param array<string, mixed> $row Site row from all().
     * @return array<string, mixed>
     */
    public static function applyLocalhostUrl(array $row): array
    {
        $domainHost = parse_url('//' . ($row['domain'] ?? ''), PHP_URL_HOST);
        if (!$domainHost) {
            $domainHost = $row['domain'] ?? '';
        }

        if (in_array($domainHost, ['localhost', '127.0.0.1'], true)) {
            $localWp = self::wpSiteUrl();
            if ($localWp) {
                $row['url'] = $localWp;
            }
        }

        return $row;
    }

    /**
     * @param mixed $key Site id (int|float), domain (string), or '' to list all rows.
     * @return array<int, array<string, mixed>>|array<string, mixed>|false
     */
    public static function all(mixed $key = '', string $value = 'id', bool $search = false)
    {
        $cfg = AppConfigService::all();
        $data = [
            [
                'id'       => 999999,
                'domain'   => 'localhost',
                'url'      => '',
                'logo'     => 'public/assets/img/logo/fencesperth.webp',
                'name'     => 'Localhost Fencing Outlet',
                'gtagID'   => $cfg->sites->fencesperth->gtag_id,
                'gtmID'    => $cfg->sites->fencesperth->gtm_id,
                'restrict' => [
                    'left_raked',
                    'right_raked',
                ],
            ],
            [
                'id'     => 1,
                'domain' => 'fencesperth.com',
                'url'    => UrlHelper::toUrl('fencesperth.com'),
                'logo'   => 'public/assets/img/logo/fencesperth.webp',
                'name'   => "Perth's Fencing Outlet",
                'gtagID' => $cfg->sites->fencesperth->gtag_id,
                'gtmID'  => $cfg->sites->fencesperth->gtm_id,
            ],
            [
                'id'     => 1.1,
                'domain' => 'staging.fencesperth.com',
                'url'    => UrlHelper::toUrl('staging.fencesperth.com'),
                'logo'   => 'public/assets/img/logo/fencesperth.webp',
                'name'   => "Perth's Fencing Outlet",
                'gtagID' => $cfg->sites->fencesperth->gtag_id,
                'gtmID'  => $cfg->sites->fencesperth->gtm_id,
            ],
            [
                'id'     => 2,
                'domain' => 'fencesbrisbane.au',
                'url'    => UrlHelper::toUrl('fencesbrisbane.au'),
                'logo'   => 'public/assets/img/logo/fencesbrisbane.webp',
                'name'   => "Brisbane's Fencing Outlet",
                'gtagID' => $cfg->sites->fencesbrisbane->gtag_id,
                'gtmID'  => $cfg->sites->fencesbrisbane->gtm_id,
            ],
            [
                'id'     => 2.1,
                'domain' => 'staging.fencesbrisbane.au',
                'url'    => UrlHelper::toUrl('staging.fencesbrisbane.au'),
                'logo'   => 'public/assets/img/logo/fencesbrisbane.webp',
                'name'   => "Brisbane's Fencing Outlet",
                'gtagID' => $cfg->sites->fencesbrisbane->gtag_id,
                'gtmID'  => $cfg->sites->fencesbrisbane->gtm_id,
            ],
            [
                'id'     => 3,
                'domain' => 'fencingwarehouse.au',
                'url'    => UrlHelper::toUrl('fencingwarehouse.au'),
                'logo'   => 'public/assets/img/logo/fencesperth.webp',
                'name'   => 'Fencing Warehouse',
            ],
            [
                'id'     => 4,
                'domain' => 'fencinggoldcoast.au',
                'url'    => UrlHelper::toUrl('fencinggoldcoast.au'),
                'logo'   => 'public/assets/img/logo/fencinggoldcoast.webp',
                'name'   => "Gold Coast's Fencing Outlet",
                'gtagID' => $cfg->sites->fencinggoldcoast->gtag_id,
                'gtmID'  => $cfg->sites->fencinggoldcoast->gtm_id,
            ],
            [
                'id'     => 5,
                'domain' => 'fencesadelaide.au',
                'url'    => UrlHelper::toUrl('fencesadelaide.au'),
                'logo'   => 'public/assets/img/logo/fencesadelaide.webp',
                'name'   => "Adelaide's Fencing Outlet",
                'gtagID' => $cfg->sites->fencesadelaide->gtag_id,
                'gtmID'  => $cfg->sites->fencesadelaide->gtm_id,
            ],
            [
                'id'     => 6,
                'domain' => 'fencessydney.au',
                'url'    => UrlHelper::toUrl('fencessydney.au'),
                'logo'   => 'public/assets/img/logo/fencessydney.webp',
                'name'   => "Sydney's Fencing Outlet",
                'gtagID' => $cfg->sites->fencessydney->gtag_id,
                'gtmID'  => $cfg->sites->fencessydney->gtm_id,
            ],
            [
                'id'     => 7,
                'domain' => 'fencesmelbourne.au',
                'url'    => UrlHelper::toUrl('fencesmelbourne.au'),
                'logo'   => 'public/assets/img/logo/fencesmelbourne.webp',
                'name'   => "Melbourne's Fencing Outlet",
                'gtagID' => $cfg->sites->fencesmelbourne->gtag_id,
                'gtmID'  => $cfg->sites->fencesmelbourne->gtm_id,
            ],
            [
                'id'     => 8,
                'domain' => 'fencesnewcastle.au',
                'url'    => UrlHelper::toUrl('fencesnewcastle.au'),
                'logo'   => 'public/assets/img/logo/fencesnewcastle.webp',
                'name'   => '',
                'gtagID' => $cfg->sites->fencesnewcastle->gtag_id,
                'gtmID'  => $cfg->sites->fencesnewcastle->gtm_id,
            ],
        ];

        foreach ($data as &$row) {
            $row['supplier'] = self::supplier((string) ($row['domain'] ?? ''), 'JG');
        }

        unset($row);

        if ($search) {
            if ($value === 'domain') {
                $searchHost = parse_url('//' . $key, PHP_URL_HOST);
                if (!$searchHost) {
                    $searchHost = $key;
                }

                foreach ($data as $row) {
                    $rowHost = parse_url('//' . $row['domain'], PHP_URL_HOST);
                    if (!$rowHost) {
                        $rowHost = $row['domain'];
                    }

                    if ($searchHost === $rowHost) {
                        return self::applyLocalhostUrl($row);
                    }
                }

                return false;
            }

            $foundKey = array_search($key, array_column($data, $value));

            if (!empty($foundKey) || $foundKey === 0) {
                return self::applyLocalhostUrl($data[$foundKey]);
            }

            return false;
        }

        return $data;
    }
}
