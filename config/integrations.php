<?php
/**
 * FC third-party integrations stored in the root config.php file.
 */

declare(strict_types=1);

function fc_integrations_config_path(): string
{
    return dirname(__DIR__) . '/config.php';
}

/**
 * @return array<string, mixed>
 */
function fc_integrations_read_config(): array
{
    $path = fc_integrations_config_path();
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
function fc_integrations_site_keys(?array $config = null): array
{
    $config = $config ?? fc_integrations_read_config();
    $keys = [];
    foreach (['gtag_id', 'gtm_id', 'cloudflare_zone_id', 'supplier'] as $section) {
        $sectionValue = $config[$section] ?? null;
        // Legacy single Zone ID lived under apikey.cloudflare_zone_id (string) — skip that.
        if ($section === 'cloudflare_zone_id' && !is_array($sectionValue)) {
            continue;
        }
        foreach (array_keys(is_array($sectionValue) ? $sectionValue : []) as $key) {
            if (is_string($key) && preg_match('/^[a-z0-9.-]+$/', $key)) {
                $keys[$key] = true;
            }
        }
    }

    return array_keys($keys);
}

function fc_integrations_site_label(string $key): string
{
    $label = preg_replace('/[^a-z0-9]+/i', ' ', $key) ?? $key;

    return ucwords(trim($label));
}

/**
 * Default supplier codes (JG|GO), keyed by mysql/config site key.
 *
 * @return array<string, string>
 */
function fc_integrations_default_suppliers(): array
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

function fc_integrations_normalize_supplier(string $value): string
{
    $value = strtoupper(trim($value));

    return ($value === 'GO' || $value === 'JG') ? $value : '';
}

/**
 * Resolve supplier for a site key (config override, then built-in default).
 */
function fc_integrations_supplier_for_key(string $key, ?array $config = null): string
{
    $key = trim($key);
    if ($key === '') {
        return '';
    }

    $config = $config ?? fc_integrations_read_config();
    $stored = is_array($config['supplier'] ?? null) ? $config['supplier'] : [];
    $fromConfig = fc_integrations_normalize_supplier((string) ($stored[$key] ?? ''));
    if ($fromConfig !== '') {
        return $fromConfig;
    }

    $defaults = fc_integrations_default_suppliers();

    return fc_integrations_normalize_supplier((string) ($defaults[$key] ?? ''));
}

/**
 * @return array<string, mixed>
 */
function fc_integrations_get(): array
{
    $config = fc_integrations_read_config();
    $api = is_array($config['apikey'] ?? null) ? $config['apikey'] : [];
    $webhooks = is_array($config['webhook_url'] ?? null) ? $config['webhook_url'] : [];
    $gtag = is_array($config['gtag_id'] ?? null) ? $config['gtag_id'] : [];
    $gtm = is_array($config['gtm_id'] ?? null) ? $config['gtm_id'] : [];
    $zones = is_array($config['cloudflare_zone_id'] ?? null) ? $config['cloudflare_zone_id'] : [];
    $sites = [];

    foreach (fc_integrations_site_keys($config) as $key) {
        $sites[] = [
            'key' => $key,
            'label' => fc_integrations_site_label($key),
            'supplier' => fc_integrations_supplier_for_key($key, $config) ?: 'JG',
            'gtagId' => (string) ($gtag[$key] ?? ''),
            'gtmId' => (string) ($gtm[$key] ?? ''),
            'cloudflareZoneId' => (string) ($zones[$key] ?? ''),
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

function fc_integrations_revision(): string
{
    $path = fc_integrations_config_path();

    return is_file($path) ? (string) hash_file('sha256', $path) : '';
}

/**
 * @return array{ok:bool,integrations:array<string,mixed>,revision:string,superAdmin?:array<string,mixed>}
 */
function fc_integrations_api_payload(): array
{
    $payload = [
        'ok' => true,
        'integrations' => fc_integrations_get(),
        'revision' => fc_integrations_revision(),
    ];

    if (!function_exists('fc_auth_primary_admin_email')) {
        require_once __DIR__ . '/auth.php';
    }

    $email = fc_auth_primary_admin_email();
    $user = function_exists('fc_auth_super_admin_user') ? fc_auth_super_admin_user() : null;
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

function fc_integrations_clean_value($value, int $maxLength): ?string
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
function fc_integrations_normalize(array $input): array
{
    $google = fc_integrations_clean_value($input['googleMapsApiKey'] ?? '', 200);
    $chatra = fc_integrations_clean_value($input['chatraApiKey'] ?? '', 200);
    $cfToken = fc_integrations_clean_value($input['cloudflareApiToken'] ?? '', 200);
    $webhook = fc_integrations_clean_value($input['webhookUrl'] ?? '', 1000);

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

    $current = fc_integrations_get();
    $allowedSites = array_fill_keys(fc_integrations_site_keys(), true);
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
        $gtag = fc_integrations_clean_value($site['gtagId'] ?? '', 50);
        $gtm = fc_integrations_clean_value($site['gtmId'] ?? '', 50);
        $zone = fc_integrations_clean_value($site['cloudflareZoneId'] ?? '', 64);
        $supplier = fc_integrations_normalize_supplier((string) ($site['supplier'] ?? ''));
        if ($supplier === '') {
            $supplier = fc_integrations_supplier_for_key($key);
        }
        if ($gtag === null || ($gtag !== '' && !preg_match('/^(?:AW|G)-[A-Z0-9-]+$/i', $gtag))) {
            return ['ok' => false, 'error' => 'Invalid Google tag ID for ' . fc_integrations_site_label($key) . '.'];
        }
        if ($gtm === null || ($gtm !== '' && !preg_match('/^GTM-[A-Z0-9]+$/i', $gtm))) {
            return ['ok' => false, 'error' => 'Invalid GTM ID for ' . fc_integrations_site_label($key) . '.'];
        }
        if ($zone === null || ($zone !== '' && !preg_match('/^[a-f0-9]{32}$/i', $zone))) {
            return ['ok' => false, 'error' => 'Invalid Cloudflare Zone ID for ' . fc_integrations_site_label($key) . '.'];
        }
        if ($supplier === '') {
            return ['ok' => false, 'error' => 'Supplier must be GO or JG for ' . fc_integrations_site_label($key) . '.'];
        }
        $sitesByKey[$key] = [
            'key' => $key,
            'label' => fc_integrations_site_label($key),
            'supplier' => $supplier,
            'gtagId' => strtoupper($gtag),
            'gtmId' => strtoupper($gtm),
            'cloudflareZoneId' => strtolower((string) $zone),
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
function fc_integrations_save(array $input, string $expectedRevision = ''): array
{
    $normalized = fc_integrations_normalize($input);
    if (empty($normalized['ok'])) {
        return $normalized;
    }

    $path = fc_integrations_config_path();
    $lockPath = $path . '.lock';
    $lock = @fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        return ['ok' => false, 'error' => 'Unable to lock config.php for writing.'];
    }

    try {
        $currentRevision = fc_integrations_revision();
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

        $config = fc_integrations_read_config();
        $next = $normalized['integrations'];
        $config['apikey'] = is_array($config['apikey'] ?? null) ? $config['apikey'] : [];
        $config['webhook_url'] = is_array($config['webhook_url'] ?? null) ? $config['webhook_url'] : [];
        $config['gtag_id'] = is_array($config['gtag_id'] ?? null) ? $config['gtag_id'] : [];
        $config['gtm_id'] = is_array($config['gtm_id'] ?? null) ? $config['gtm_id'] : [];
        $config['cloudflare_zone_id'] = is_array($config['cloudflare_zone_id'] ?? null)
            ? $config['cloudflare_zone_id']
            : [];
        $config['supplier'] = is_array($config['supplier'] ?? null) ? $config['supplier'] : [];

        $config['apikey']['google_map'] = (string) $next['googleMapsApiKey'];
        $config['apikey']['chatra'] = (string) $next['chatraApiKey'];
        $config['apikey']['cloudflare_api_token'] = (string) $next['cloudflareApiToken'];
        // Zone IDs are per-site; drop legacy global string under apikey.
        unset($config['apikey']['cloudflare_zone_id'], $config['apikey']['cloudflare_account_id']);
        $config['webhook_url']['zap'] = (string) $next['webhookUrl'];
        foreach ($next['sites'] as $site) {
            $key = (string) $site['key'];
            $config['gtag_id'][$key] = (string) $site['gtagId'];
            $config['gtm_id'][$key] = (string) $site['gtmId'];
            $config['cloudflare_zone_id'][$key] = (string) $site['cloudflareZoneId'];
            $config['supplier'][$key] = (string) ($site['supplier'] ?? '');
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
            'revision' => fc_integrations_revision(),
        ];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
