<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\PhpValueExporter;

/**
 * Read/write fence definition PHP files while preserving project formatting conventions.
 */
final class FenceFileService
{
    /**
     * @return 'block'|'hybrid'|'unknown'
     */
    public static function detectFileType(string $content, string $slug): string
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

    public static function detectParentSlug(string $content, string $slug): ?string
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
    private static function replaceBlock(string $content, string $slug, array $config): ?string
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
                    $replacement = "\$fences['" . $slug . "'] = " . PhpValueExporter::export($config, 0) . ';';

                    return substr($content, 0, $start) . $replacement . substr($content, $end);
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function buildHybridContent(string $content, string $slug, string $parentSlug, array $config): ?string
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
            $exported = PhpValueExporter::export($value, 0);
            $lines[] = "\$fences['" . $slug . "']['" . $key . "'] = " . $exported . ';';
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function writeConfig(string $filePath, string $slug, array $config): array
    {
        if (!is_file($filePath) || !is_writable($filePath)) {
            return ['ok' => false, 'error' => 'Fence file is not writable.'];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['ok' => false, 'error' => 'Could not read fence file.'];
        }

        $fileType = self::detectFileType($content, $slug);
        $newContent = null;

        if ($fileType === 'block') {
            $newContent = self::replaceBlock($content, $slug, $config);
        } elseif ($fileType === 'hybrid') {
            $parentSlug = self::detectParentSlug($content, $slug);
            if ($parentSlug === null) {
                return ['ok' => false, 'error' => 'Could not detect parent fence for hybrid file.'];
            }
            $newContent = self::buildHybridContent($content, $slug, $parentSlug, $config);
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
}
