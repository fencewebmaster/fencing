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

    /**
     * Typed pagination link items for a paginationWindow() page list.
     * $urlForPage receives the page number and returns its href.
     *
     * @param list<int|string> $pages
     * @return list<array{type:string,label:string,url:string}>
     */
    public static function paginationLinks(array $pages, int $currentPage, callable $urlForPage): array
    {
        $links = [];
        foreach ($pages as $pageNum) {
            if ($pageNum === '…') {
                $links[] = ['type' => 'ellipsis', 'label' => '…', 'url' => ''];
                continue;
            }
            $num = (int) $pageNum;
            $links[] = [
                'type' => $num === $currentPage ? 'current' : 'link',
                'label' => (string) $num,
                'url' => $urlForPage($num),
            ];
        }

        return $links;
    }

    /**
     * Shared page/per_page/'all' parsing for the list pages. 'page' keeps the raw
     * parsed page (users/entries echo it even in 'all' mode); 'page_or_first' is
     * the forced-to-1 form the product pages use; 'per_page_value' is what URLs
     * and view data echo back ('all' or the int).
     *
     * @param array<string, mixed> $query
     * @param list<int> $options
     * @return array{page:int,page_or_first:int,per_page:int,per_page_value:int|string,is_all:bool,offset:int}
     */
    public static function parseListPagination(array $query, array $options, int $default): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPageRaw = strtolower(trim((string) ($query['per_page'] ?? (string) $default)));
        $isAll = ($perPageRaw === 'all');
        $perPage = $default;

        if ($isAll) {
            $perPage = 0;
        } elseif (in_array((int) $perPageRaw, $options, true)) {
            $perPage = (int) $perPageRaw;
        }

        return [
            'page' => $page,
            'page_or_first' => $isAll ? 1 : $page,
            'per_page' => $perPage,
            'per_page_value' => $isAll ? 'all' : $perPage,
            'is_all' => $isAll,
            'offset' => $isAll ? 0 : ($page - 1) * $perPage,
        ];
    }

    /**
     * "A, B (+N)" summary for a multi-select filter button.
     *
     * @param list<string> $labels
     */
    public static function multiSelectSummaryLabel(array $labels, string $allLabel): string
    {
        if ($labels === []) {
            return $allLabel;
        }
        if (count($labels) === 1) {
            return $labels[0];
        }
        if (count($labels) === 2) {
            return $labels[0] . ', ' . $labels[1];
        }

        return $labels[0] . ', ' . $labels[1] . ' (+' . (count($labels) - 2) . ')';
    }

    /**
     * Clamp an overflowing page to the payload's last page and re-query once.
     * $requery receives the clamped page and returns the fresh payload.
     *
     * @param array<string, mixed> $payload
     * @return array{0:array<string, mixed>,1:int} [payload, page]
     */
    public static function clampPageRequery(array $payload, int $page, bool $isAll, callable $requery): array
    {
        if (
            !$isAll
            && !empty($payload['ok'])
            && $page > 1
            && (int) ($payload['total_pages'] ?? 1) >= 1
            && $page > (int) $payload['total_pages']
        ) {
            $page = max(1, (int) $payload['total_pages']);
            $payload = $requery($page);
        }

        return [$payload, $page];
    }
}
