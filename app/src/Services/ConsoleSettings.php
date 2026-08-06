<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * FC Console settings — developer toggles (saved to writable/console.json).
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

    public static function filePath(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'console.json';
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
        $path = self::filePath();
        if (!is_file($path) || !is_readable($path)) {
            return self::defaults();
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return self::defaults();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return self::defaults();
        }

        return self::normalize($decoded);
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
        $path = self::filePath();
        $dir = dirname($path);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'writable/ directory is not writable.'];
        }
        if (!is_writable($dir)) {
            return ['ok' => false, 'error' => 'writable/ directory is not writable.'];
        }
        if (file_exists($path) && !is_writable($path)) {
            return ['ok' => false, 'error' => 'console.json is not writable.'];
        }

        $payload = array_merge($next, [
            'updatedAt' => gmdate('c'),
        ]);

        $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $written = file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            return ['ok' => false, 'error' => 'Unable to write console settings.'];
        }
        if (!rename($tmp, $path)) {
            @unlink($tmp);

            return ['ok' => false, 'error' => 'Unable to save console.json.'];
        }

        return [
            'ok' => true,
            'console' => $next,
        ];
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
