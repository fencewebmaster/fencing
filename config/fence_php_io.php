<?php
/**
 * Read/write fence definition PHP files while preserving project formatting conventions.
 */

declare(strict_types=1);

/**
 * @param scalar|null $value
 */
function fc_fence_php_string_literal($value): string
{
    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value) . "'";
}

/**
 * @param scalar|null $value
 */
function fc_fence_php_bool_literal($value): string
{
    return $value ? 'TRUE' : 'FALSE';
}

/**
 * @param int|string $key
 */
function fc_fence_php_array_key($key): string
{
    if (is_int($key)) {
        return (string) $key;
    }
    $key = (string) $key;
    if ($key !== '' && ctype_digit($key)) {
        return $key;
    }

    return fc_fence_php_string_literal($key);
}

/**
 * @param array<mixed> $value
 */
function fc_fence_is_inline_array(array $value): bool
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

/**
 * @param mixed $value
 */
function fc_fence_php_export($value, int $depth = 0): string
{
    if (is_bool($value)) {
        return fc_fence_php_bool_literal($value);
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if ($value === null) {
        return 'NULL';
    }
    if (is_string($value)) {
        return fc_fence_php_string_literal($value);
    }
    if (!is_array($value)) {
        return fc_fence_php_string_literal((string) $value);
    }

    if ($value === []) {
        return '[]';
    }

    $isList = array_keys($value) === range(0, count($value) - 1);
    if ($isList && fc_fence_is_inline_array($value)) {
        $parts = [];
        foreach ($value as $item) {
            $parts[] = fc_fence_php_export($item, 0);
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
            $line .= fc_fence_php_export($item, $depth + 1);
        } else {
            $line .= fc_fence_php_array_key($key) . ' => ' . fc_fence_php_export($item, $depth + 1);
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
 * @return 'block'|'hybrid'|'unknown'
 */
function fc_fence_detect_file_type(string $content, string $slug): string
{
    $escapedSlug = preg_quote($slug, '/');
    if (preg_match('/^\$fences\[\'' . $escapedSlug . '\'\]\s*=\s*\[/m', $content)) {
        return 'block';
    }
    if (preg_match('/^\$fences\[\'' . $escapedSlug . '\'\]\s*=\s*\$fences\[/m', $content)) {
        return 'hybrid';
    }

    return 'unknown';
}

function fc_fence_detect_parent_slug(string $content, string $slug): ?string
{
    $escapedSlug = preg_quote($slug, '/');
    if (!preg_match(
        '/^\$fences\[\'' . $escapedSlug . '\'\]\s*=\s*\$fences\[\'' . '([^\']+)' . '\'\]\s*;/m',
        $content,
        $matches
    )) {
        return null;
    }

    return $matches[1];
}

/**
 * @param array<string, mixed> $config
 */
function fc_fence_replace_block(string $content, string $slug, array $config): ?string
{
    $needle = "\$fences['" . $slug . "'] = [";
    $start = strpos($content, $needle);
    if ($start === false) {
        return null;
    }

    $bracketPos = $start + strlen($needle) - 1;
    if ($content[$bracketPos] !== '[') {
        return null;
    }

    $depth = 0;
    $length = strlen($content);
    $inString = false;
    $stringChar = '';
    $escaped = false;

    for ($i = $bracketPos; $i < $length; $i++) {
        $char = $content[$i];

        if ($inString) {
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            if ($char === $stringChar) {
                $inString = false;
            }
            continue;
        }

        if ($char === "'" || $char === '"') {
            $inString = true;
            $stringChar = $char;
            continue;
        }

        if ($char === '[') {
            $depth++;
            continue;
        }

        if ($char === ']') {
            $depth--;
            if ($depth === 0) {
                $end = $i + 1;
                if ($end < $length && $content[$end] === ';') {
                    $end++;
                }
                $replacement = "\$fences['" . $slug . "'] = " . fc_fence_php_export($config, 0) . ';';

                return substr($content, 0, $start) . $replacement . substr($content, $end);
            }
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $config
 */
function fc_fence_build_hybrid_content(string $content, string $slug, string $parentSlug, array $config): ?string
{
    $copyLine = "\$fences['" . $slug . "'] = \$fences['" . $parentSlug . "'];";
    $copyPos = strpos($content, $copyLine);
    if ($copyPos === false) {
        return null;
    }

    $headerEnd = $copyPos + strlen($copyLine);
    $header = rtrim(substr($content, 0, $headerEnd));

    $lines = [$header, ''];
    foreach ($config as $key => $value) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        $exported = fc_fence_php_export($value, 0);
        $lines[] = "\$fences['" . $slug . "']['" . $key . "'] = " . $exported . ';';
        $lines[] = '';
    }

    return rtrim(implode("\n", $lines)) . "\n";
}

/**
 * @param array<string, mixed> $config
 */
function fc_fence_write_config(string $filePath, string $slug, array $config): array
{
    if (!is_file($filePath) || !is_writable($filePath)) {
        return ['ok' => false, 'error' => 'Fence file is not writable.'];
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        return ['ok' => false, 'error' => 'Could not read fence file.'];
    }

    $fileType = fc_fence_detect_file_type($content, $slug);
    $newContent = null;

    if ($fileType === 'block') {
        $newContent = fc_fence_replace_block($content, $slug, $config);
    } elseif ($fileType === 'hybrid') {
        $parentSlug = fc_fence_detect_parent_slug($content, $slug);
        if ($parentSlug === null) {
            return ['ok' => false, 'error' => 'Could not detect parent fence for hybrid file.'];
        }
        $newContent = fc_fence_build_hybrid_content($content, $slug, $parentSlug, $config);
    } else {
        return ['ok' => false, 'error' => 'Unsupported fence file format.'];
    }

    if ($newContent === null || $newContent === '') {
        return ['ok' => false, 'error' => 'Could not build updated fence file content.'];
    }

    if (file_put_contents($filePath, $newContent) === false) {
        return ['ok' => false, 'error' => 'Could not write fence file.'];
    }

    return ['ok' => true, 'fileType' => $fileType];
}
