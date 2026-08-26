<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic string utilities (slugs, escaping, random ids).
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
     * Uppercase alphanumeric id (e.g. planner ids) generated with a CSPRNG.
     *
     * Uses random_int() per character over a fixed alphabet rather than str_shuffle()'s
     * non-cryptographic RNG. str_shuffle() also only ever produces a *permutation* of the
     * alphabet — no repeated characters — which silently shrinks the effective keyspace
     * versus independent per-character draws (36P6 vs 36^6, for example); random_int()
     * draws independently, so callers get the full 36^length combinations they'd expect.
     */
    public static function randomId(int $length = 10): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($alphabet) - 1;

        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $alphabet[random_int(0, $max)];
        }

        return $id;
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
