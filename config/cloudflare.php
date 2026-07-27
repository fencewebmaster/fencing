<?php
/**
 * Cloudflare CDN helpers (zone purge via Integration settings credentials).
 */

declare(strict_types=1);

/**
 * @return array{zoneId:string,apiToken:string,configured:bool,label:string}
 */
function fc_cloudflare_credentials(): array
{
    if (!function_exists('fc_integrations_get')) {
        require_once __DIR__ . '/integrations.php';
    }

    $integrations = fc_integrations_get();
    $zoneId = trim((string) ($integrations['cloudflareZoneId'] ?? ''));
    $apiToken = trim((string) ($integrations['cloudflareApiToken'] ?? ''));
    $configured = $zoneId !== '' && $apiToken !== '';

    return [
        'zoneId' => $zoneId,
        'apiToken' => $apiToken,
        'configured' => $configured,
        'label' => $configured ? 'CDN ready' : 'Not configured',
    ];
}

/**
 * Stats entry for the Clear cache dropdown (not a local file bucket).
 *
 * @return array{files:int,bytes:int,label:string,configured:bool}
 */
function fc_cloudflare_cache_stats(): array
{
    $creds = fc_cloudflare_credentials();

    return [
        // Keep the option clickable even when empty; purge returns a clear error.
        'files' => $creds['configured'] ? 1 : 1,
        'bytes' => 0,
        'label' => $creds['label'],
        'configured' => $creds['configured'],
    ];
}

/**
 * Purge everything for the configured Cloudflare zone.
 *
 * @return array{ok:bool,deleted:int,targets:list<string>,error?:string,message?:string}
 */
function fc_cloudflare_purge_cache(): array
{
    $creds = fc_cloudflare_credentials();
    if ($creds['zoneId'] === '') {
        return [
            'ok' => false,
            'deleted' => 0,
            'targets' => [],
            'error' => 'Cloudflare Zone ID is not configured. Add it under Settings → Integration.',
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

    $url = 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode($creds['zoneId']) . '/purge_cache';
    $body = json_encode(['purge_everything' => true], JSON_UNESCAPED_SLASHES);
    if ($body === false) {
        return [
            'ok' => false,
            'deleted' => 0,
            'targets' => [],
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
                'deleted' => 0,
                'targets' => [],
                'error' => 'Unable to start Cloudflare request.',
            ];
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $creds['apiToken'],
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
                'deleted' => 0,
                'targets' => [],
                'error' => 'Cloudflare request failed' . ($curlError !== '' ? ': ' . $curlError : '.'),
            ];
        }
        $responseBody = (string) $raw;
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Authorization: Bearer ' . $creds['apiToken'],
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
                'deleted' => 0,
                'targets' => [],
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
            'deleted' => 0,
            'targets' => [],
            'error' => $message,
        ];
    }

    return [
        'ok' => true,
        'deleted' => 1,
        'targets' => ['cloudflare'],
        'message' => 'Purged Cloudflare CDN cache.',
    ];
}
