<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Same-origin copies of the store's product images, for the project plan's PNG/PDF capture.
 *
 * The images live on the store (fencesperth.com), which sends no CORS header, so modern-screenshot
 * could not read them and the PDF's item list printed empty frames. checkout.js routes the capture's
 * cross-origin image fetches here instead. Only exact image URLs listed in the WooCommerce product
 * CSVs are fetched, so this is not an open proxy; a fetched copy is kept for a week.
 */
final class CaptureImageService
{
    private const BUCKET = 'capture-images';
    private const MAX_BYTES = 8388608;
    private const TTL = 604800;
    private const TYPES = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * The local copy of a catalogue image, fetched on first use.
     *
     * @return array{status:int, type?:string, path?:string}
     */
    public static function resolve(string $url): array
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || !preg_match('#^https?://#i', $url)) {
            return ['status' => 400];
        }

        $base = CacheStorageService::cacheDir(self::BUCKET) . DIRECTORY_SEPARATOR . sha1($url);
        // A copy only exists for a URL that passed the catalogue check when it was fetched.
        foreach (self::TYPES as $type => $ext) {
            $file = $base . '.' . $ext;
            if (is_file($file) && filemtime($file) + self::TTL > time()) {
                return ['status' => 200, 'type' => $type, 'path' => $file];
            }
        }

        if (!self::isCatalogueImage($url)) {
            return ['status' => 404];
        }

        $body = self::fetch($url);
        $info = $body !== null ? @getimagesizefromstring($body) : false;
        $type = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        if (!isset(self::TYPES[$type])) {
            return ['status' => 502];
        }

        foreach (self::TYPES as $ext) {
            @unlink($base . '.' . $ext);
        }
        $file = $base . '.' . self::TYPES[$type];
        $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $body) === false || !@rename($tmp, $file)) {
            @unlink($tmp);
            return ['status' => 500];
        }

        return ['status' => 200, 'type' => $type, 'path' => $file];
    }

    /** True when the URL is one of the product images in either supplier's WooCommerce CSV. */
    public static function isCatalogueImage(string $url): bool
    {
        foreach (['GO', 'JG'] as $supplier) {
            foreach (WcProductCsvService::csvRows($supplier) as $row) {
                $raw = isset($row['images']) ? (string) $row['images'] : '';
                if ($raw === '' || strpos($raw, $url) === false) {
                    continue;
                }
                foreach (preg_split('/\s*,\s*/', trim($raw)) ?: [] as $image) {
                    if (trim($image) === $url) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /** The image bytes, or null on any failure; no redirects, so the host checked is the host fetched. */
    private static function fetch(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_MAXFILESIZE    => self::MAX_BYTES,
            CURLOPT_USERAGENT      => 'FC project plan capture',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $status !== 200 || $body === '' || strlen($body) > self::MAX_BYTES) {
            return null;
        }

        return $body;
    }
}
