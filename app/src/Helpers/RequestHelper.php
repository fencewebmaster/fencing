<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic HTTP-request inspection utilities.
 */
final class RequestHelper
{
    /**
     * Whether the caller wants a JSON response: an XHR request, or an Accept header
     * that mentions application/json. Shared by AuthFilter and PermissionFilter.
     */
    public static function wantsJson(): bool
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return $isAjax || str_contains($accept, 'application/json');
    }
    /**
     * Whether the current request is over HTTPS (direct or via a trusted proxy header).
     */
    public static function isHttps(): bool
    {
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwarded === 'https') {
            return true;
        }

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    }

    /**
     * Whether the current request host is local development.
     */
    public static function isLocalhost(): bool
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $host = strtolower((string) (preg_replace('/:\d+$/', '', $host) ?: $host));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * Best-effort client IP (supports common proxy headers).
     */
    public static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['HTTP_X_REAL_IP'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        foreach ($candidates as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }

            foreach (explode(',', $raw) as $part) {
                $ip = trim($part);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '';
    }

    /**
     * Classify device type from a user-agent string.
     */
    public static function clientDevice(?string $userAgent = null): string
    {
        $ua = strtolower(trim((string) ($userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''))));
        if ($ua === '') {
            return 'Unknown';
        }
        if (preg_match('/bot|crawl|spider|slurp|mediapartners/i', $ua)) {
            return 'Bot';
        }
        if (preg_match('/tablet|ipad|playbook|silk|(android(?!.*mobile))/i', $ua)) {
            return 'Tablet';
        }
        if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * Truncated HTTP user-agent for storage.
     */
    public static function clientUserAgent(): string
    {
        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') {
            return '';
        }
        if (mb_strlen($ua) > 512) {
            return mb_substr($ua, 0, 512);
        }

        return $ua;
    }
}
