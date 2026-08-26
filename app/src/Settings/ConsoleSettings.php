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
        return [
            'debugMode' => false,
        ];
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

    /**
     * @return array{debugMode:bool}
     */
    public static function normalize(array $input): array
    {
        $defaults = self::defaults();
        $debug = $input['debugMode'] ?? $defaults['debugMode'];
        if (is_string($debug)) {
            $debug = in_array(strtolower(trim($debug)), ['1', 'true', 'yes', 'on'], true);
        }

        return [
            'debugMode' => (bool) $debug,
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
