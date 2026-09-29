<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

/**
 * FC Console settings — developer toggles (stored in the root config.php `console` section).
 */
final class ConsoleSettings
{
    /**
     * @return array{debugMode:bool}
     */
    public static function defaults(): array
    {
        // Debug Mode is the whole group: the Debugbar's capture knobs are constants on
        // DebugbarServer, not settings.
        return ['debugMode' => false];
    }

    public static function configPath(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config.php';
    }

    /**
     * @return array<string, mixed>
     */
    private static function readConfig(): array
    {
        $path = self::configPath();
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

    private static function normalizeBool(mixed $value, bool $default): bool
    {
        $value = $value ?? $default;
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * @return array{debugMode:bool}
     */
    public static function normalize(array $input): array
    {
        $defaults = self::defaults();

        return [
            'debugMode' => self::normalizeBool($input['debugMode'] ?? null, $defaults['debugMode']),
        ];
    }

    /**
     * @return array{debugMode:bool}
     */
    public static function get(): array
    {
        $config = self::readConfig();
        $console = is_array($config['console'] ?? null) ? $config['console'] : [];

        return self::normalize($console);
    }

    public static function debugMode(): bool
    {
        return !empty(self::get()['debugMode']);
    }

    /**
     * @param array<string, mixed> $console
     * @return array{ok:bool,console?:array{debugMode:bool},error?:string}
     */
    public static function save(array $console): array
    {
        $next = self::normalize($console);
        $path = self::configPath();

        if (!is_readable($path)) {
            return ['ok' => false, 'error' => 'config.php not found or not readable.'];
        }

        $lock = @fopen($path . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            return ['ok' => false, 'error' => 'Unable to lock config.php for writing.'];
        }

        try {
            if (!is_writable($path)) {
                return ['ok' => false, 'error' => 'config.php is not writable.'];
            }

            $config = self::readConfig();
            $config['console'] = $next;

            $php = "<?php\n\n\$config = " . var_export($config, true) . ";\n";
            $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4)) . '.php';
            if (file_put_contents($tmp, $php, LOCK_EX) === false) {
                return ['ok' => false, 'error' => 'Unable to write the temporary config file.'];
            }
            self::keepPermissions($path, $tmp);

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

            // Without this, a production server with opcache.validate_timestamps=0 keeps
            // serving the old compiled config.php after the rename above — the save
            // "succeeds" but every read (get()/apiPayload()) still returns stale values.
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }

            return [
                'ok' => true,
                'console' => $next,
            ];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Gives a freshly written config.php copy the live file's permissions. Both writers save through a
     * temp file, which PHP creates at the server default (644), so a 600 set by hand or by Site Health's
     * fix fell back to 644 on the next Settings save. The owner (PHP, which wrote the copy) keeps read+write.
     */
    public static function keepPermissions(string $path, string $tmp): void
    {
        $mode = @fileperms($path);
        if ($mode !== false) {
            @chmod($tmp, ($mode & 0777) | 0600);
        }
    }

    /**
     * Site Health's one-click fix: config.php owner-only (600), so other accounts on the server can't
     * read the database passwords in it. chmod() only succeeds for the file's owner, and PHP must still
     * read and write the file afterwards, or the old mode goes back.
     *
     * @return array{ok:bool,mode?:string,error?:string}
     */
    public static function restrictPermissions(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return ['ok' => false, 'error' => 'File permissions don\'t apply on a Windows server.'];
        }

        $path = self::configPath();
        if (!is_file($path)) {
            return ['ok' => false, 'error' => 'config.php not found.'];
        }

        // The writers' lock: a save swapping in its new copy mid-way would bring back the old mode.
        $lock = @fopen($path . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            return ['ok' => false, 'error' => 'Unable to lock config.php.'];
        }

        try {
            clearstatcache(true, $path);
            $before = @fileperms($path);
            if ($before === false) {
                return ['ok' => false, 'error' => 'Unable to read config.php\'s permissions.'];
            }
            $before &= 0777;
            if ($before === 0600) {
                return ['ok' => true, 'mode' => '600'];
            }

            if (!@chmod($path, 0600)) {
                return ['ok' => false, 'error' => 'PHP doesn\'t own config.php, so it can\'t change its permissions. Ask your host to set it to 600.'];
            }

            clearstatcache(true, $path);
            if (!is_readable($path) || !is_writable($path)) {
                @chmod($path, $before);

                return ['ok' => false, 'error' => 'PHP couldn\'t read and write config.php at 600, so it was put back to ' . sprintf('%o', $before) . '. Ask your host which user PHP runs as.'];
            }

            return ['ok' => true, 'mode' => '600'];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @return array{ok:bool,console:array{debugMode:bool},defaults:array{debugMode:bool}}
     */
    public static function apiPayload(): array
    {
        return [
            'ok' => true,
            'console' => self::get(),
            'defaults' => self::defaults(),
        ];
    }
}
