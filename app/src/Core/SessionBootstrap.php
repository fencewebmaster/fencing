<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

/**
 * Shared PHP session bootstrap — fixes unwritable XAMPP session.save_path.
 */
final class SessionBootstrap
{
    /**
     * Resolve a writable session save path.
     *
     * When $preferred is provided (admin auth), it's tried first so public PHP
     * session GC cannot delete admin session files — but if it isn't writable
     * (wrong permissions/ownership, not yet created, etc.), this falls through
     * to the PHP-configured save_path and then the system temp directory rather
     * than returning an unwritable path, which would silently break every
     * session-backed feature (staying logged in, CSRF).
     *
     * Public callers (no preferred path) never get routed into the admin
     * sessions directory — they only ever see the configured path / temp dir.
     */
    public static function resolveSavePath(?string $preferred = null): string
    {
        $candidates = [];

        $preferred = $preferred !== null ? trim($preferred) : '';
        if ($preferred !== '') {
            $candidates[] = $preferred;
        }

        // Always keep these as fallbacks, even when a preferred (admin) path was given.
        // Without this, an unwritable preferred directory (wrong permissions/ownership
        // after deploy, not yet created, etc.) makes resolveSavePath() return it anyway,
        // ini_set() a save_path PHP can't actually write to, and session_start() then
        // fails silently — every request starts a session that never persists to disk,
        // which breaks login state and CSRF (a stored token that's never actually saved
        // can never match on the next request) without ever surfacing as an obvious error.
        $configured = (string) ini_get('session.save_path');
        if ($configured !== '') {
            $candidates[] = $configured;
        }
        $candidates[] = sys_get_temp_dir();

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            if (!is_dir($candidate)) {
                @mkdir($candidate, 0775, true);
            }

            if (is_dir($candidate) && is_writable($candidate)) {
                return $candidate;
            }
        }

        return $preferred !== '' ? $preferred : (string) ini_get('session.save_path');
    }

    /**
     * Start a session using a writable save path.
     *
     * @param string|null $savePath Optional preferred directory (admin uses writable/storage/sessions).
     */
    public static function start(?string $savePath = null): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $resolved = self::resolveSavePath($savePath);
        if ($resolved !== '') {
            @ini_set('session.save_path', $resolved);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
}
