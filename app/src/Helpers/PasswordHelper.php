<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * WordPress password-hash verification.
 */
final class PasswordHelper
{
    /**
     * Verify a WordPress password hash (phpass $P$ / $H$, or WP 6.8+ $wp$ bcrypt).
     */
    public static function verify(string $password, string $hash): bool
    {
        $hash = trim($hash);
        if ($password === '' || $hash === '') {
            return false;
        }

        // WordPress 6.8+ bcrypt hashes prefixed with $wp$
        if (str_starts_with($hash, '$wp$')) {
            $passwordToVerify = base64_encode(hash_hmac('sha384', $password, 'wp-sha384', true));

            return password_verify($passwordToVerify, substr($hash, 3)) === true;
        }

        // Native bcrypt / argon (rare but possible)
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$argon')) {
            return password_verify($password, $hash);
        }

        // Classic phpass ($P$ / $H$)
        $phpass = dirname(FC_ROOT) . '/wp-includes/class-phpass.php';
        if (is_readable($phpass)) {
            require_once $phpass;
            if (class_exists('PasswordHash', false)) {
                $hasher = new \PasswordHash(8, true);

                return (bool) $hasher->CheckPassword($password, $hash);
            }
        }

        return false;
    }
}
