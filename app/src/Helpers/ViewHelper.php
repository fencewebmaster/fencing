<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic server-rendered-view formatting utilities consolidated out of config/.
 */
final class ViewHelper
{
    /**
     * Em-dash placeholder for empty table cell values, else escaped.
     */
    public static function cell(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return StringHelper::escapeHtml((string) $value);
    }

    public static function jsonAttr(mixed $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return StringHelper::escapeHtml(is_string($json) ? $json : '[]');
    }

    public static function bootstrapJson(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return is_string($json) ? $json : '{}';
    }

    public static function formatHeader(string $label): string
    {
        $text = strtolower(str_replace('_', ' ', trim($label)));
        if ($text === '') {
            return '';
        }

        return ucwords($text);
    }

    public static function adminUrl(string $adminBase, string $route): string
    {
        $base = rtrim(str_replace('\\', '/', $adminBase), '/');
        $path = ltrim(str_replace('\\', '/', $route), '/');

        return $base . '/' . $path;
    }

    /**
     * Page-number window for pagination controls (shared by store-products and
     * system-products view models — purely generic, no page-specific logic).
     *
     * @return list<int|string>
     */
    public static function paginationWindow(int $current, int $total): array
    {
        if ($total <= 7) {
            $pages = [];
            for ($i = 1; $i <= $total; $i++) {
                $pages[] = $i;
            }

            return $pages;
        }

        $pages = [1];
        $start = max(2, $current - 1);
        $end = min($total - 1, $current + 1);

        if ($start > 2) {
            $pages[] = '…';
        }
        for ($p = $start; $p <= $end; $p++) {
            $pages[] = $p;
        }
        if ($end < $total - 1) {
            $pages[] = '…';
        }
        $pages[] = $total;

        return $pages;
    }
}
