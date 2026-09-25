<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Settings\IntegrationsSettings;

/**
 * Cloudflare CDN helpers (zone purge via Integration settings credentials).
 */
final class CloudflareService
{
    /**
     * Site key used when resolving the per-site Cloudflare Zone ID.
     */
    public static function siteKey(): string
    {
        if (defined('FC_ADMIN_ROOT')) {
            $adminKey = AdminSiteRegistry::siteKey();
            if ($adminKey !== '' && $adminKey !== 'localhost') {
                return $adminKey;
            }
        }

        $hostKey = DatabaseConfigService::hostMysqlKey();
        return $hostKey !== '' ? $hostKey : 'localhost';
    }

    /**
     * @return array{siteKey:string,zoneId:string,apiToken:string,configured:bool,label:string}
     */
    public static function credentials(?string $siteKey = null): array
    {
        $integrations = IntegrationsSettings::get();
        $key = trim((string) ($siteKey ?? ''));
        if ($key === '') {
            $key = self::siteKey();
        }

        $zoneId = '';
        foreach (is_array($integrations['sites'] ?? null) ? $integrations['sites'] : [] as $site) {
            if (!is_array($site)) {
                continue;
            }
            if ((string) ($site['key'] ?? '') === $key) {
                $zoneId = trim((string) ($site['cloudflareZoneId'] ?? ''));
                break;
            }
        }

        $apiToken = trim((string) ($integrations['cloudflareApiToken'] ?? ''));
        $configured = $zoneId !== '' && $apiToken !== '';

        $label = ($zoneId !== '' && $apiToken !== '') ? 'CDN ready' : 'CDN not ready';

        return [
            'siteKey' => $key,
            'zoneId' => $zoneId,
            'apiToken' => $apiToken,
            'configured' => $configured,
            'label' => $label,
        ];
    }

    /**
     * Stats entry for the Clear cache dropdown (not a local file bucket).
     *
     * @return array{files:int,bytes:int,label:string,configured:bool}
     */
    public static function cacheStats(): array
    {
        $creds = self::credentials();
        $ready = !empty($creds['configured']);

        return [
            // files drives enable/disable in the Clear cache dropdown (0 = disabled).
            'files' => $ready ? 1 : 0,
            'bytes' => 0,
            'label' => $creds['label'],
            'configured' => $ready,
        ];
    }

    /**
     * Purge everything for the configured Cloudflare zone of the current site.
     *
     * @return array{ok:bool,deleted:int,targets:list<string>,error?:string,message?:string}
     */
    public static function purgeCache(?string $siteKey = null): array
    {
        $creds = self::credentials($siteKey);
        if ($creds['zoneId'] === '') {
            $siteLabel = $creds['siteKey'] !== '' ? $creds['siteKey'] : 'this site';
            return [
                'ok' => false,
                'deleted' => 0,
                'targets' => [],
                'error' => 'Cloudflare Zone ID is not configured for ' . $siteLabel . '. Add it under Settings → Integration.',
            ];
        }
        if ($creds['apiToken'] === '') {
            return [
                'ok' => false,
                'deleted' => 0,
                'targets' => [],
                'error' => 'Cloudflare API token is not configured. Add it under Settings → Integration.',
            ];
        }

        $result = self::purgeZone($creds['apiToken'], $creds['zoneId'], $creds['siteKey']);
        if (empty($result['ok'])) {
            $error = (string) ($result['error'] ?? 'Cloudflare purge failed.');
            // A zoneName on a refusal means the zone belongs to another domain: say where to fix it.
            if (!empty($result['zoneName'])) {
                $error .= ' Correct it under Settings → Integration.';
            }

            return [
                'ok' => false,
                'deleted' => 0,
                'targets' => [],
                'error' => $error,
            ];
        }

        return [
            'ok' => true,
            'deleted' => 1,
            'targets' => ['cloudflare'],
            'message' => 'Purged the Cloudflare cache for ' . $result['zoneName'] . '.',
        ];
    }

    /**
     * The site's own domain from the site registry, which its zone must cover. '' for localhost
     * (a dev copy with no domain of its own) and for keys the registry does not know.
     */
    public static function siteDomain(string $siteKey): string
    {
        $key = trim($siteKey);
        if ($key === '' || $key === 'localhost') {
            return '';
        }

        $rows = SiteRegistryService::all();
        foreach (is_array($rows) ? $rows : [] as $row) {
            $domain = is_array($row) ? strtolower(trim((string) ($row['domain'] ?? ''))) : '';
            if ($domain !== '' && AdminSiteRegistry::mysqlKeyFromDomain($domain) === $key) {
                return $domain;
            }
        }

        return '';
    }

    /**
     * '' when the zone serves the site (its domain, or a subdomain such as staging.), else why not.
     * A Zone ID copied from another site's row purges that other site, which is how it goes wrong.
     */
    public static function zoneMismatch(string $siteKey, string $zoneName): string
    {
        $domain = self::siteDomain($siteKey);
        $zone = strtolower(trim($zoneName));
        if ($domain === '' || $zone === '' || $domain === $zone || str_ends_with($domain, '.' . $zone)) {
            return '';
        }

        return 'This Zone ID is for ' . $zone . ', not ' . $domain . '.';
    }

    /**
     * Purge everything cached in one zone. The Integration tab's per-site button passes the Zone ID
     * and token as typed, like verifyZone(), so an unsaved edit purges the zone the admin can see.
     * With a site key, a zone that does not serve that site's domain is refused before any purge.
     *
     * @return array{ok:bool,zoneName?:string,error?:string}
     */
    public static function purgeZone(string $apiToken, string $zoneId, string $siteKey = ''): array
    {
        $token = trim($apiToken);
        $zone = strtolower(trim($zoneId));

        if ($token === '') {
            return [
                'ok' => false,
                'error' => 'Cloudflare API token is not configured. Add it under Settings → Integration.',
            ];
        }

        if (!preg_match('/^[a-f0-9]{32}$/', $zone)) {
            return [
                'ok' => false,
                'error' => 'Cloudflare Zone ID must be a 32-character hex string.',
            ];
        }

        // Read the zone first: the result names its domain, and another site's zone is refused unpurged.
        $lookup = self::verifyZone($token, $zone);
        if (empty($lookup['ok'])) {
            return [
                'ok' => false,
                'error' => (string) ($lookup['error'] ?? 'Cloudflare zone check failed.'),
            ];
        }
        $zoneName = (string) ($lookup['zoneName'] ?? $zone);
        $mismatch = self::zoneMismatch($siteKey, $zoneName);
        if ($mismatch !== '') {
            return [
                'ok' => false,
                'error' => $mismatch,
                'zoneName' => $zoneName,
            ];
        }

        $url = 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode($zone) . '/purge_cache';
        $body = json_encode(['purge_everything' => true], JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return [
                'ok' => false,
                'error' => 'Unable to build Cloudflare purge request.',
            ];
        }

        $responseBody = '';
        $status = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return [
                    'ok' => false,
                    'error' => 'Unable to start Cloudflare request.',
                ];
            }
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_POSTFIELDS => $body,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                return [
                    'ok' => false,
                    'error' => 'Cloudflare request failed' . ($curlError !== '' ? ': ' . $curlError : '.'),
                ];
            }
            $responseBody = (string) $raw;
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", [
                        'Authorization: Bearer ' . $token,
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ]),
                    'content' => $body,
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents($url, false, $context);
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $headerLine) {
                    if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string) $headerLine, $m)) {
                        $status = (int) $m[1];
                        break;
                    }
                }
            }
            if ($raw === false) {
                return [
                    'ok' => false,
                    'error' => 'Cloudflare request failed.',
                ];
            }
            $responseBody = (string) $raw;
        }

        $decoded = json_decode($responseBody, true);
        $success = is_array($decoded) && !empty($decoded['success']);
        if (!$success) {
            $errors = is_array($decoded['errors'] ?? null) ? $decoded['errors'] : [];
            $first = is_array($errors[0] ?? null) ? $errors[0] : [];
            $message = trim((string) ($first['message'] ?? ''));
            if ($message === '') {
                $message = $status > 0
                    ? 'Cloudflare purge failed (HTTP ' . $status . ').'
                    : 'Cloudflare purge failed.';
            }

            return [
                'ok' => false,
                'error' => $message,
            ];
        }

        return ['ok' => true, 'zoneName' => $zoneName];
    }

    /**
     * Verify that an API token can read a Cloudflare zone (connectivity check; no purge).
     *
     * @return array{ok:bool,zoneName?:string,status?:string,error?:string}
     */
    public static function verifyZone(string $apiToken, string $zoneId): array
    {
        $token = trim($apiToken);
        $zone = strtolower(trim($zoneId));

        if ($token === '') {
            return [
                'ok' => false,
                'error' => 'Cloudflare API token is required.',
            ];
        }

        if ($zone === '' || !preg_match('/^[a-f0-9]{32}$/', $zone)) {
            return [
                'ok' => false,
                'error' => 'Cloudflare Zone ID must be a 32-character hex string.',
            ];
        }

        $url = 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode($zone);
        $responseBody = '';
        $status = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return [
                    'ok' => false,
                    'error' => 'Unable to start Cloudflare request.',
                ];
            }
            curl_setopt_array($ch, [
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json',
                ],
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                return [
                    'ok' => false,
                    'error' => 'Cloudflare request failed' . ($curlError !== '' ? ': ' . $curlError : '.'),
                ];
            }
            $responseBody = (string) $raw;
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", [
                        'Authorization: Bearer ' . $token,
                        'Accept: application/json',
                    ]),
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents($url, false, $context);
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $headerLine) {
                    if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string) $headerLine, $m)) {
                        $status = (int) $m[1];
                        break;
                    }
                }
            }
            if ($raw === false) {
                return [
                    'ok' => false,
                    'error' => 'Cloudflare request failed.',
                ];
            }
            $responseBody = (string) $raw;
        }

        $decoded = json_decode($responseBody, true);
        $success = is_array($decoded) && !empty($decoded['success']);
        if (!$success) {
            $errors = is_array($decoded['errors'] ?? null) ? $decoded['errors'] : [];
            $first = is_array($errors[0] ?? null) ? $errors[0] : [];
            $message = trim((string) ($first['message'] ?? ''));
            if ($message === '') {
                $message = $status > 0
                    ? 'Cloudflare zone check failed (HTTP ' . $status . ').'
                    : 'Cloudflare zone check failed.';
            }

            return [
                'ok' => false,
                'error' => $message,
            ];
        }

        $result = is_array($decoded['result'] ?? null) ? $decoded['result'] : [];
        $zoneName = trim((string) ($result['name'] ?? ''));
        $zoneStatus = trim((string) ($result['status'] ?? ''));

        return [
            'ok' => true,
            'zoneName' => $zoneName !== '' ? $zoneName : $zone,
            'status' => $zoneStatus !== '' ? $zoneStatus : 'unknown',
        ];
    }
}
