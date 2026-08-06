<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic string utilities (slugs, escaping, random ids) consolidated out of config/.
 */
final class StringHelper
{
    /**
     * Slug-safe string (lowercase, [a-z0-9_-] only) without requiring WordPress.
     */
    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '', $value) ?? '';

        return $value;
    }

    /**
     * Short uppercase alphanumeric id (e.g. planner ids).
     */
    public static function randomId(int $length = 10): string
    {
        return strtoupper(substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, $length));
    }

    /**
     * Escape for HTML text/attribute context.
     */
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Decode WP/HTML entities so labels like "Panel &amp; Gate" become plain text.
     */
    public static function decodeHtmlEntities(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('wp_specialchars_decode')) {
            $value = wp_specialchars_decode($value, ENT_QUOTES);
        }

        // Second pass covers numeric entities / leftover &amp; stored in term names.
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return is_string($decoded) ? $decoded : $value;
    }
}
