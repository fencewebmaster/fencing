<?php
/**
 * FC Console settings — developer toggles (saved to data/console.json).
 */

declare(strict_types=1);

/**
 * @return array{debugMode:bool}
 */
function fc_console_defaults(): array
{
    return [
        'debugMode' => false,
    ];
}

function fc_console_file_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'console.json';
}

/**
 * @return array{debugMode:bool}
 */
function fc_console_normalize(array $input): array
{
    $defaults = fc_console_defaults();
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
function fc_console_get(): array
{
    $path = fc_console_file_path();
    if (!is_file($path) || !is_readable($path)) {
        return fc_console_defaults();
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return fc_console_defaults();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return fc_console_defaults();
    }

    return fc_console_normalize($decoded);
}

function fc_console_debug_mode(): bool
{
    return !empty(fc_console_get()['debugMode']);
}

/**
 * @param array<string, mixed> $console
 * @return array{ok:bool,console?:array{debugMode:bool},error?:string}
 */
function fc_console_save(array $console): array
{
    $next = fc_console_normalize($console);
    $path = fc_console_file_path();
    $dir = dirname($path);

    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'data/ directory is not writable.'];
    }
    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'data/ directory is not writable.'];
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
function fc_console_api_payload(): array
{
    return [
        'ok' => true,
        'console' => fc_console_get(),
        'defaults' => fc_console_defaults(),
    ];
}
