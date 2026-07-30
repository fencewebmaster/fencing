<?php
/**
 * Shared PHP session bootstrap — fixes unwritable XAMPP session.save_path.
 */

declare(strict_types=1);

/**
 * Resolve a writable session save path.
 *
 * When $preferred is provided (admin auth), always prefer that directory so
 * public PHP session GC cannot delete admin session files.
 *
 * Public callers (no preferred path) never fall back into the admin sessions
 * directory — they use the PHP configured path or the system temp directory.
 */
function fc_session_resolve_save_path(?string $preferred = null): string
{
    $candidates = [];

    $preferred = $preferred !== null ? trim($preferred) : '';
    if ($preferred !== '') {
        $candidates[] = $preferred;
    } else {
        $configured = (string) ini_get('session.save_path');
        if ($configured !== '') {
            $candidates[] = $configured;
        }
        $candidates[] = sys_get_temp_dir();
    }

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
 * @param string|null $savePath Optional preferred directory (admin uses data/storage/sessions).
 */
function fc_session_start(?string $savePath = null): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $resolved = fc_session_resolve_save_path($savePath);
    if ($resolved !== '') {
        @ini_set('session.save_path', $resolved);
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}
