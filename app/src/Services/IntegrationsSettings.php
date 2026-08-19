<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * FC third-party integrations stored in the root config.php file.
 */
final class IntegrationsSettings
{
    public static function configPath(): string
    {
        return dirname(__DIR__, 3) . '/config.php';
    }

    /**
     * @return array<string, mixed>
     */
    public static function readConfig(): array
    {
        $path = self::configPath();
        if (!is_readable($path)) {
            return [];
        }

        $loaded = null;
        (static function (string $file, &$result): void {
            $config = null;
            include $file;
            $result = is_array($config) ? $config : [];
        })($path, $loaded);

        return is_array($loaded) ? $loaded : [];
    }

    /**
     * @return list<string>
     */
    public static function siteKeys(?array $config = null): array
    {
        $config = $config ?? self::readConfig();
        $sites = is_array($config['sites'] ?? null) ? $config['sites'] : [];
        $keys = [];
        foreach (array_keys($sites) as $key) {
            if (is_string($key) && preg_match('/^[a-z0-9.-]+$/', $key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public static function siteLabel(string $key): string
    {
        $label = preg_replace('/[^a-z0-9]+/i', ' ', $key) ?? $key;

        return ucwords(trim($label));
    }

    /**
     * Default supplier codes (JG|GO), keyed by mysql/config site key.
     *
     * @return array<string, string>
     */
    public static function defaultSuppliers(): array
    {
        return [
            'localhost' => 'JG',
            'fencesperth' => 'JG',
            'fencesbrisbane' => 'JG',
            'fencingwarehouse' => 'JG',
            'fencinggoldcoast' => 'GO',
            'fencesadelaide' => 'GO',
            'fencessydney' => 'GO',
            'fencesmelbourne' => 'GO',
            'fencesnewcastle' => 'GO',
        ];
    }

    public static function normalizeSupplier(string $value): string
    {
        $value = strtoupper(trim($value));

        return ($value === 'GO' || $value === 'JG') ? $value : '';
    }

    /**
     * PID Prefix for a site key, prepended to newly-minted planner/quote IDs
     * (see PlannerRecordService::newPlannerId()). Blank when unset — no site should ever
     * be treated as having a prefix it wasn't explicitly given.
     */
    public static function pidPrefixForKey(string $key, ?array $config = null): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        $config = $config ?? self::readConfig();
        $site = is_array($config['sites'][$key] ?? null) ? $config['sites'][$key] : [];
        $value = strtoupper(trim((string) ($site['pid_prefix'] ?? '')));

        return preg_match('/^[A-Z0-9]{1,10}$/', $value) ? $value : '';
    }

    /**
     * Resolve supplier for a site key (config override, then built-in default).
     */
    public static function supplierForKey(string $key, ?array $config = null): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        $config = $config ?? self::readConfig();
        $site = is_array($config['sites'][$key] ?? null) ? $config['sites'][$key] : [];
        $fromConfig = self::normalizeSupplier((string) ($site['supplier'] ?? ''));
        if ($fromConfig !== '') {
            return $fromConfig;
        }

        $defaults = self::defaultSuppliers();

        return self::normalizeSupplier((string) ($defaults[$key] ?? ''));
    }

    /**
     * Site logo path for a site key, via the canonical domain->logo map (SiteRegistryService::all()).
     * Falls back to the Perth logo when a site has no dedicated asset (matches AdminSiteRegistry's fallback).
     */
    public static function logoForKey(string $key): string
    {
        $fallback = 'public/assets/img/logo/fencesperth.webp';
        $key = trim($key);
        if ($key === '') {
            return $fallback;
        }

        $rows = SiteRegistryService::all();
        if (!is_array($rows)) {
            return $fallback;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $domain = (string) ($row['domain'] ?? '');
            if ($domain === '') {
                continue;
            }
            if (AdminSiteRegistry::mysqlKeyFromDomain($domain) === $key) {
                $logo = trim((string) ($row['logo'] ?? ''));

                return $logo !== '' ? $logo : $fallback;
            }
        }

        return $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $config = self::readConfig();
        $api = is_array($config['apikey'] ?? null) ? $config['apikey'] : [];
        $webhooks = is_array($config['webhook_url'] ?? null) ? $config['webhook_url'] : [];
        $sitesConfig = is_array($config['sites'] ?? null) ? $config['sites'] : [];
        $sites = [];

        foreach (self::siteKeys($config) as $key) {
            $site = is_array($sitesConfig[$key] ?? null) ? $sitesConfig[$key] : [];
            // 'logo' is the raw stored override (blank = no override, use the auto-derived default).
            // 'logoDefault' is always populated so the UI can preview/fall back to it without saving it.
            $sites[] = [
                'key' => $key,
                'label' => self::siteLabel($key),
                'supplier' => self::supplierForKey($key, $config) ?: 'JG',
                'logo' => trim((string) ($site['site_logo'] ?? '')),
                'logoDefault' => self::logoForKey($key),
                'gtagId' => (string) ($site['gtag_id'] ?? ''),
                'gtmId' => (string) ($site['gtm_id'] ?? ''),
                'cloudflareZoneId' => (string) ($site['cloudflare_zone_id'] ?? ''),
                'pidPrefix' => (string) ($site['pid_prefix'] ?? ''),
            ];
        }

        return [
            'googleMapsApiKey' => (string) ($api['google_map'] ?? ''),
            'chatraApiKey' => (string) ($api['chatra'] ?? ''),
            'cloudflareApiToken' => (string) ($api['cloudflare_api_token'] ?? ''),
            'webhookUrl' => (string) ($webhooks['zap'] ?? ''),
            'sites' => $sites,
        ];
    }

    public static function revision(): string
    {
        $path = self::configPath();

        return is_file($path) ? (string) hash_file('sha256', $path) : '';
    }

    /**
     * @return array{ok:bool,integrations:array<string,mixed>,revision:string,superAdmin?:array<string,mixed>}
     */
    public static function apiPayload(): array
    {
        $payload = [
            'ok' => true,
            'integrations' => self::get(),
            'revision' => self::revision(),
        ];

        $email = PermissionService::primaryAdminEmail();
        $user = PermissionService::superAdminUser();
        $payload['superAdmin'] = [
            'email' => $email,
            'userId' => $user !== null ? (int) $user['ID'] : 0,
            'userLogin' => $user !== null ? (string) $user['user_login'] : '',
            'displayName' => $user !== null ? (string) $user['display_name'] : '',
            'resolved' => $user !== null,
            'label' => $user !== null
                ? trim((string) $user['display_name'] . ' <' . (string) $user['user_email'] . '>')
                : ($email !== '' ? $email . ' (not found)' : 'Not configured'),
        ];

        return $payload;
    }

    public static function cleanValue($value, int $maxLength): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if (str_contains($value, "\0") || preg_match('/[\r\n]/', $value)) {
            return null;
        }

        return mb_strlen($value) <= $maxLength ? $value : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool,integrations?:array<string,mixed>,error?:string}
     */
    public static function normalize(array $input): array
    {
        $google = self::cleanValue($input['googleMapsApiKey'] ?? '', 200);
        $chatra = self::cleanValue($input['chatraApiKey'] ?? '', 200);
        $cfToken = self::cleanValue($input['cloudflareApiToken'] ?? '', 200);
        $webhook = self::cleanValue($input['webhookUrl'] ?? '', 1000);

        if ($google === null || ($google !== '' && !preg_match('/^[A-Za-z0-9_-]+$/', $google))) {
            return ['ok' => false, 'error' => 'Google Maps API key contains invalid characters.'];
        }
        if ($chatra === null || ($chatra !== '' && !preg_match('/^[A-Za-z0-9_-]+$/', $chatra))) {
            return ['ok' => false, 'error' => 'Chatra API key contains invalid characters.'];
        }
        if ($cfToken === null || ($cfToken !== '' && !preg_match('/^[A-Za-z0-9_-]+$/', $cfToken))) {
            return ['ok' => false, 'error' => 'Cloudflare API token contains invalid characters.'];
        }
        if ($webhook === null || ($webhook !== '' && !preg_match('#^(?:https?://)?[A-Za-z0-9.-]+(?::\d+)?(?:/[^\\s]*)?$#', $webhook))) {
            return ['ok' => false, 'error' => 'Webhook URL is invalid.'];
        }

        $current = self::get();
        $allowedSites = array_fill_keys(self::siteKeys(), true);
        $incomingSites = is_array($input['sites'] ?? null) ? $input['sites'] : [];
        $sitesByKey = [];
        foreach ($incomingSites as $site) {
            if (!is_array($site)) {
                continue;
            }
            $key = (string) ($site['key'] ?? '');
            if (!isset($allowedSites[$key])) {
                return ['ok' => false, 'error' => 'Unknown integration site: ' . $key . '.'];
            }
            $gtag = self::cleanValue($site['gtagId'] ?? '', 50);
            $gtm = self::cleanValue($site['gtmId'] ?? '', 50);
            $zone = self::cleanValue($site['cloudflareZoneId'] ?? '', 64);
            $logo = self::cleanValue($site['logo'] ?? '', 500);
            $pidPrefix = self::cleanValue($site['pidPrefix'] ?? '', 10);
            $supplier = self::normalizeSupplier((string) ($site['supplier'] ?? ''));
            if ($supplier === '') {
                $supplier = self::supplierForKey($key);
            }
            if ($gtag === null || ($gtag !== '' && !preg_match('/^(?:AW|G)-[A-Z0-9-]+$/i', $gtag))) {
                return ['ok' => false, 'error' => 'Invalid Google tag ID for ' . self::siteLabel($key) . '.'];
            }
            if ($gtm === null || ($gtm !== '' && !preg_match('/^GTM-[A-Z0-9]+$/i', $gtm))) {
                return ['ok' => false, 'error' => 'Invalid GTM ID for ' . self::siteLabel($key) . '.'];
            }
            if ($zone === null || ($zone !== '' && !preg_match('/^[a-f0-9]{32}$/i', $zone))) {
                return ['ok' => false, 'error' => 'Invalid Cloudflare Zone ID for ' . self::siteLabel($key) . '.'];
            }
            if ($logo === null) {
                return ['ok' => false, 'error' => 'Logo path/URL is invalid for ' . self::siteLabel($key) . '.'];
            }
            if ($supplier === '') {
                return ['ok' => false, 'error' => 'Supplier must be GO or JG for ' . self::siteLabel($key) . '.'];
            }
            if ($pidPrefix === null || ($pidPrefix !== '' && !preg_match('/^[A-Za-z0-9]+$/', $pidPrefix))) {
                return ['ok' => false, 'error' => 'PID Prefix must be letters/numbers only for ' . self::siteLabel($key) . '.'];
            }
            $sitesByKey[$key] = [
                'key' => $key,
                'label' => self::siteLabel($key),
                'supplier' => $supplier,
                'logo' => $logo,
                'logoDefault' => self::logoForKey($key),
                'gtagId' => strtoupper($gtag),
                'gtmId' => strtoupper($gtm),
                'cloudflareZoneId' => strtolower((string) $zone),
                'pidPrefix' => strtoupper($pidPrefix),
            ];
        }

        $sites = [];
        foreach ($current['sites'] as $site) {
            $key = (string) ($site['key'] ?? '');
            $sites[] = $sitesByKey[$key] ?? $site;
        }

        return [
            'ok' => true,
            'integrations' => [
                'googleMapsApiKey' => $google,
                'chatraApiKey' => $chatra,
                'cloudflareApiToken' => (string) $cfToken,
                'webhookUrl' => $webhook,
                'sites' => $sites,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool,integrations?:array<string,mixed>,revision?:string,error?:string,conflict?:bool}
     */
    public static function save(array $input, string $expectedRevision = ''): array
    {
        $normalized = self::normalize($input);
        if (empty($normalized['ok'])) {
            return $normalized;
        }

        $path = self::configPath();
        $lockPath = $path . '.lock';
        $lock = @fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            return ['ok' => false, 'error' => 'Unable to lock config.php for writing.'];
        }

        try {
            $currentRevision = self::revision();
            if ($expectedRevision !== '' && !hash_equals($currentRevision, $expectedRevision)) {
                return [
                    'ok' => false,
                    'error' => 'config.php changed since this page loaded. Refresh and try again.',
                    'conflict' => true,
                ];
            }
            if (!is_writable($path)) {
                return ['ok' => false, 'error' => 'config.php is not writable.'];
            }

            $config = self::readConfig();
            $next = $normalized['integrations'];
            $config['apikey'] = is_array($config['apikey'] ?? null) ? $config['apikey'] : [];
            $config['webhook_url'] = is_array($config['webhook_url'] ?? null) ? $config['webhook_url'] : [];
            $config['sites'] = is_array($config['sites'] ?? null) ? $config['sites'] : [];

            $config['apikey']['google_map'] = (string) $next['googleMapsApiKey'];
            $config['apikey']['chatra'] = (string) $next['chatraApiKey'];
            $config['apikey']['cloudflare_api_token'] = (string) $next['cloudflareApiToken'];
            // Zone IDs are per-site; drop legacy global string under apikey.
            unset($config['apikey']['cloudflare_zone_id'], $config['apikey']['cloudflare_account_id']);
            $config['webhook_url']['zap'] = (string) $next['webhookUrl'];
            foreach ($next['sites'] as $site) {
                $key = (string) $site['key'];
                if (!isset($config['sites'][$key]) || !is_array($config['sites'][$key])) {
                    $config['sites'][$key] = [];
                }
                // Only these six integration fields are ever touched here — 'mysql' (DB
                // credentials) already living under this site key is left completely alone.
                $config['sites'][$key]['gtag_id'] = (string) $site['gtagId'];
                $config['sites'][$key]['gtm_id'] = (string) $site['gtmId'];
                $config['sites'][$key]['cloudflare_zone_id'] = (string) $site['cloudflareZoneId'];
                $config['sites'][$key]['supplier'] = (string) ($site['supplier'] ?? '');
                // Blank = no override; get() falls back to the auto-derived logo (logoForKey()).
                $config['sites'][$key]['site_logo'] = (string) ($site['logo'] ?? '');
                $config['sites'][$key]['pid_prefix'] = (string) ($site['pidPrefix'] ?? '');
            }

            $php = "<?php\n\n\$config = " . var_export($config, true) . ";\n";
            $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4)) . '.php';
            if (file_put_contents($tmp, $php, LOCK_EX) === false) {
                return ['ok' => false, 'error' => 'Unable to write the temporary config file.'];
            }

            $test = null;
            (static function (string $file, &$result): void {
                $config = null;
                include $file;
                $result = $config;
            })($tmp, $test);
            if (!is_array($test)) {
                @unlink($tmp);
                return ['ok' => false, 'error' => 'Generated config.php did not pass validation.'];
            }

            // Keep every temporary copy PHP-executable so credentials can never be
            // downloaded as plain text if the web server receives a request mid-save.
            $backup = $path . '.bak.php';
            @unlink($backup);
            if (!@rename($path, $backup) || !@rename($tmp, $path)) {
                if (is_file($backup) && !is_file($path)) {
                    @rename($backup, $path);
                }
                @unlink($tmp);
                return ['ok' => false, 'error' => 'Unable to replace config.php.'];
            }
            @unlink($backup);

            return [
                'ok' => true,
                'integrations' => $next,
                'revision' => self::revision(),
            ];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
