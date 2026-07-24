<?php
/**
 * Shared PHP session bootstrap — fixes unwritable XAMPP session.save_path.
 */

declare(strict_types=1);

/**
 * Start a session using a writable save path when configured path is unavailable.
 */
function fc_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $savePath = (string) ini_get('session.save_path');
    if ($savePath === '' || !is_dir($savePath) || !is_writable($savePath)) {
        if (!function_exists('fc_storage_sessions_dir')) {
            require_once __DIR__ . '/storage.php';
        }
        $candidates = [
            fc_storage_sessions_dir(),
            sys_get_temp_dir(),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (!is_dir($candidate)) {
                @mkdir($candidate, 0775, true);
            }

            if (is_dir($candidate) && is_writable($candidate)) {
                ini_set('session.save_path', $candidate);
                break;
            }
        }
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}
