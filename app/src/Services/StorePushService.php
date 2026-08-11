<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Hands a completed quote off to the WooCommerce store (`?fc_action=push`).
 */
final class StorePushService
{
    /**
     * Resolve the store URL to push to for the session's site.
     *
     * @param array<string, mixed> $site Session `site` row.
     */
    public static function storeUrl(array $site): string
    {
        $wpSiteUrl = SiteRegistryService::wpSiteUrl();

        return (string) ($wpSiteUrl ?: ($site['url'] ?? ''));
    }

    /**
     * POST the session payload to the store.
     *
     * @return array{ok:bool,message:string,body:string}
     *         On success `body` is the raw store response (a JSON object with token + url),
     *         which the browser expects to receive verbatim.
     */
    public static function push(string $storeUrl, string $payload): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => rtrim($storeUrl, '/') . '/?fc_action=push&date=' . date('mdYHis'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
        ]);

        $response  = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode  = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $httpCode >= 400) {
            return [
                'ok'      => false,
                'message' => 'Could not reach the store (' . ($curlError ?: 'HTTP ' . $httpCode) . ').',
                'body'    => '',
            ];
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded) || empty($decoded['token']) || empty($decoded['url'])) {
            return [
                'ok'      => false,
                'message' => 'The store returned an invalid push response.',
                'body'    => '',
            ];
        }

        return [
            'ok'      => true,
            'message' => '',
            'body'    => (string) $response,
        ];
    }
}
