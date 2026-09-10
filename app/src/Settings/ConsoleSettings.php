<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

/**
 * FC Console settings — developer toggles (stored in the root config.php `console` section).
 */
final class ConsoleSettings
{
    /**
     * @return array{debugMode:bool,showDebugbar:bool,debugVerbose:bool,debugMaxEntries:int,debugRedactKeys:string}
     */
    public static function defaults(): array
    {
        return [
            'debugMode' => false,
            // Debugbar sub-settings. All of them are inert unless debugMode is on - debugMode
            // stays the single master switch; showDebugbar only lets a developer keep server
            // debug behaviour (error display, slug rows) without the toolbar overlay.
            'showDebugbar' => true,
            'debugVerbose' => false,
            'debugMaxEntries' => 200,
            'debugRedactKeys' => 'password,secret,token,nonce,key,auth,cookie,credential,db_password,database_password',
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

    private static function normalizeBool(mixed $value, bool $default): bool
    {
        $value = $value ?? $default;
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * @return array{debugMode:bool,showDebugbar:bool,debugVerbose:bool,debugMaxEntries:int,debugRedactKeys:string}
     */
    public static function normalize(array $input): array
    {
        $defaults = self::defaults();

        $maxEntries = $input['debugMaxEntries'] ?? $defaults['debugMaxEntries'];
        $maxEntries = is_numeric($maxEntries) ? (int) $maxEntries : $defaults['debugMaxEntries'];
        $maxEntries = max(50, min(2000, $maxEntries));

        // Kept as a CSV string (what the admin field holds); consumers split via debugRedactKeys().
        // An emptied field falls back to the defaults rather than turning redaction off silently.
        $redact = trim((string) ($input['debugRedactKeys'] ?? $defaults['debugRedactKeys']));
        $redact = implode(',', array_values(array_filter(array_map(
            static fn (string $k): string => strtolower(trim($k)),
            explode(',', $redact)
        ), static fn (string $k): bool => $k !== '')));
        if ($redact === '') {
            $redact = $defaults['debugRedactKeys'];
        }

        return [
            'debugMode' => self::normalizeBool($input['debugMode'] ?? null, $defaults['debugMode']),
            'showDebugbar' => self::normalizeBool($input['showDebugbar'] ?? null, $defaults['showDebugbar']),
            'debugVerbose' => self::normalizeBool($input['debugVerbose'] ?? null, $defaults['debugVerbose']),
            'debugMaxEntries' => $maxEntries,
            'debugRedactKeys' => $redact,
        ];
    }

    /**
     * @return array{debugMode:bool,showDebugbar:bool,debugVerbose:bool,debugMaxEntries:int,debugRedactKeys:string}
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

    public static function showDebugbar(): bool
    {
        return !empty(self::get()['showDebugbar']);
    }

    public static function debugVerbose(): bool
    {
        return !empty(self::get()['debugVerbose']);
    }

    public static function debugMaxEntries(): int
    {
        return (int) self::get()['debugMaxEntries'];
    }

    /**
     * @return list<string>
     */
    public static function debugRedactKeys(): array
    {
        return array_values(array_filter(explode(',', (string) self::get()['debugRedactKeys'])));
    }

    /**
     * @param array<string, mixed> $console
     * @return array{ok:bool,console?:array{debugMode:bool,showDebugbar:bool,debugVerbose:bool,debugMaxEntries:int,debugRedactKeys:string},error?:string}
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
     * @return array{ok:bool,console:array{debugMode:bool,showDebugbar:bool,debugVerbose:bool,debugMaxEntries:int,debugRedactKeys:string},defaults:array{debugMode:bool,showDebugbar:bool,debugVerbose:bool,debugMaxEntries:int,debugRedactKeys:string}}
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
