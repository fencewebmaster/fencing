<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Serializes a PHP value back into formatted PHP source (used by fence config
 * file writers). Consolidated out of config/fence_php_io.php.
 */
final class PhpValueExporter
{
    /**
     * @param mixed $value
     */
    public static function export($value, int $depth = 0): string
    {
        if (is_bool($value)) {
            return self::boolLiteral($value);
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if ($value === null) {
            return 'NULL';
        }
        if (is_string($value)) {
            return self::stringLiteral($value);
        }
        if (!is_array($value)) {
            return self::stringLiteral((string) $value);
        }

        if ($value === []) {
            return '[]';
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        if ($isList && self::isInlineArray($value)) {
            $parts = [];
            foreach ($value as $item) {
                $parts[] = self::export($item, 0);
            }

            return '[' . implode(', ', $parts) . ']';
        }

        $indent = str_repeat("\t", $depth);
        $childIndent = str_repeat("\t", $depth + 1);
        $lines = ['['];
        $keys = array_keys($value);
        $lastKey = $keys[count($keys) - 1] ?? null;

        foreach ($value as $key => $item) {
            $line = $childIndent;
            if ($isList) {
                $line .= self::export($item, $depth + 1);
            } else {
                $line .= self::arrayKey($key) . ' => ' . self::export($item, $depth + 1);
            }
            if ($key !== $lastKey) {
                $line .= ',';
            }
            $lines[] = $line;
        }

        $lines[] = $indent . ']';

        return implode("\n", $lines);
    }

    /**
     * @param scalar|null $value
     */
    private static function stringLiteral($value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value) . "'";
    }

    /**
     * @param scalar|null $value
     */
    private static function boolLiteral($value): string
    {
        return $value ? 'TRUE' : 'FALSE';
    }

    /**
     * @param int|string $key
     */
    private static function arrayKey($key): string
    {
        if (is_int($key)) {
            return (string) $key;
        }
        $key = (string) $key;
        if ($key !== '' && ctype_digit($key)) {
            return $key;
        }

        return self::stringLiteral($key);
    }

    /**
     * @param array<mixed> $value
     */
    private static function isInlineArray(array $value): bool
    {
        if ($value === [] || count($value) > 10) {
            return false;
        }

        foreach ($value as $item) {
            if (is_array($item)) {
                return false;
            }
            if (is_string($item) && strlen($item) > 48) {
                return false;
            }
        }

        return true;
    }
}
