<?php

declare(strict_types=1);

namespace Fc\Admin\Debug;

/**
 * Key-based redaction for Debugbar payloads. The key list is DebugbarServer::REDACT_KEYS.
 */
final class DebugRedactor
{
    /**
     * Replace the value of any array key whose name contains one of $keys
     * (case-insensitive substring match) with '[redacted]'. Recurses into arrays;
     * scalars and objects pass through untouched - the Debugbar only serializes arrays.
     *
     * @param list<string> $keys
     */
    public static function redact(mixed $value, array $keys): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $k => $v) {
            if (is_string($k) && self::keyIsSensitive($k, $keys)) {
                $out[$k] = '[redacted]';
                continue;
            }
            $out[$k] = is_array($v) ? self::redact($v, $keys) : $v;
        }

        return $out;
    }

    /**
     * @param list<string> $keys
     */
    public static function keyIsSensitive(string $key, array $keys): bool
    {
        $lower = strtolower($key);
        foreach ($keys as $needle) {
            if ($needle !== '' && str_contains($lower, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask quoted string literals in SQL shown by the query log. Customer data (names,
     * mobiles, addresses) travels as literals in wp_planners INSERT/UPDATEs, and the
     * frontend Debugbar is visible to anyone while Debug Mode is on - so by default the
     * query panel shows query SHAPE, not payload. Verbose Trace shows literals in full.
     * Both quote styles are masked: Database::where_clause builds "double-quoted" values.
     */
    public static function maskSqlLiterals(string $sql): string
    {
        return (string) preg_replace_callback(
            '/\'(?:[^\'\\\\]|\\\\.|\'\')*\'|"(?:[^"\\\\]|\\\\.|"")*"/s',
            static function (array $m): string {
                $inner = substr($m[0], 1, -1);
                return strlen($inner) <= 2 ? $m[0] : "'\u{2026}'";
            },
            $sql
        );
    }
}
