<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Helpers\StringHelper;
use Fc\Admin\Helpers\ViewHelper;
use Fc\Admin\Services\AdminSiteRegistry;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\PermissionService;
use Fc\Admin\Services\SiteRegistryService;

/**
 * System products (writable/wc-products-GO.csv, writable/wc-products-JG.csv) row shaping — pure,
 * DB-free formatting/view-model helpers, plus the page-level viewData() orchestrator.
 * No dependency on SystemProductModel (kept one-directional: Model depends on this class,
 * not the other way around) — mirrors StoreProductModel / StoreProductPresenter.
 */
final class SystemProductPresenter
{
    /**
     * @param list<string> $columns
     * @return list<string>
     */
    public static function orderColumns(array $columns): array
    {
        $order = ['ID', 'Images', 'SKU', 'Name'];
        $result = [];
        foreach ($order as $col) {
            if (in_array($col, $columns, true)) {
                $result[] = $col;
            }
        }
        foreach ($columns as $col) {
            if (!in_array($col, $result, true)) {
                $result[] = $col;
            }
        }

        return $result;
    }

    /**
     * @return array{html:string,empty:bool}
     */
    public static function cellHtml(string $col, string $val, array $row): array
    {
        if ($col === 'SKU') {
            $skuHtml = $val === ''
                ? '—'
                : '<span class="fc-sys-sku-value block text-sm text-slate-800">' . StringHelper::escapeHtml($val) . '</span>';

            $viewUrl = self::productViewUrl(trim((string) ($row['Slug'] ?? '')));
            if ($viewUrl !== '') {
                $skuHtml .= '<a href="' . StringHelper::escapeHtml($viewUrl) . '" target="_blank" rel="noopener noreferrer" '
                    . 'class="fc-sys-sku-view-link mt-0.5 inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-700 hover:underline">'
                    . 'View Product</a>';
            }

            return ['html' => $skuHtml, 'empty' => $val === '' && $viewUrl === ''];
        }

        if ($val === '') {
            if ($col === 'Images') {
                return [
                    'html' => '<div class="fc-sys-images-fill"><span class="fc-sys-images-placeholder" title="No images">'
                        . '<i class="fa-solid fa-image" aria-hidden="true"></i>'
                        . '<span class="sr-only">No images</span></span></div>',
                    'empty' => true,
                ];
            }

            return ['html' => '—', 'empty' => true];
        }

        if ($col === 'Images') {
            $urls = array_values(array_filter(array_map('trim', explode(',', $val))));
            $productName = trim((string) ($row['Name'] ?? $row['SKU'] ?? ''));
            if ($productName === '') {
                $productName = 'Product images';
            }
            if ($urls === []) {
                return [
                    'html' => '<div class="fc-sys-images-fill"><span class="fc-sys-images-placeholder" title="No images">'
                        . '<i class="fa-solid fa-image" aria-hidden="true"></i>'
                        . '<span class="sr-only">No images</span></span></div>',
                    'empty' => true,
                ];
            }
            $thumbUrl = $urls[0];
            $extraCount = count($urls) > 1 ? count($urls) - 1 : 0;
            $html = '<div class="fc-sys-images-fill"><button type="button" class="fc-sys-images-trigger" data-fc-sys-images="'
                . ViewHelper::jsonAttr($urls) . '" data-fc-sys-images-title="'
                . StringHelper::escapeHtml($productName) . '" aria-label="View ' . count($urls)
                . (count($urls) === 1 ? ' image' : ' images') . ' for ' . StringHelper::escapeHtml($productName) . '">'
                . '<img src="' . StringHelper::escapeHtml($thumbUrl) . '" alt="" class="fc-sys-images-thumb" loading="lazy" decoding="async" />'
                . '<span class="fc-sys-images-overlay" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>';
            if ($extraCount > 0) {
                $html .= '<span class="fc-sys-images-badge">+' . $extraCount . '</span>';
            }
            $html .= '</button></div>';

            return ['html' => $html, 'empty' => false];
        }

        if ($col === 'Name') {
            return [
                'html' => '<span class="block max-w-md truncate" title="' . StringHelper::escapeHtml($val) . '">'
                    . StringHelper::escapeHtml($val) . '</span>',
                'empty' => false,
            ];
        }

        return ['html' => StringHelper::escapeHtml($val), 'empty' => false];
    }

    /**
     * "View Product" link for the SKU cell — the currently selected admin site's own URL
     * (session site switcher), not the CSV's GO/JG source, + the product slug.
     */
    private static function productViewUrl(string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        $domain = trim((string) (AdminSiteRegistry::currentSite()['domain'] ?? ''));
        if ($domain === '') {
            return '';
        }

        $site = SiteRegistryService::all($domain, 'domain', true);
        $base = is_array($site) ? rtrim((string) ($site['url'] ?? ''), '/') : '';

        return $base !== '' ? $base . '/product/' . rawurlencode($slug) . '/' : '';
    }

    /**
     * @param list<string> $columns
     * @param list<array<string, mixed>> $rows
     */
    public static function tableHtml(array $columns, array $rows, bool $flatScroll = false): string
    {
        $primaryColumns = ['ID', 'Images', 'SKU', 'Name'];
        $colgroup = '';
        foreach ($columns as $col) {
            $widthClass = match ($col) {
                'ID' => 'fc-sys-id-col',
                'Images' => 'fc-sys-images-col',
                'SKU' => 'fc-sys-sku-col',
                'Name' => 'fc-sys-name-col',
                default => in_array($col, $primaryColumns, true) ? 'min-w-[8rem]' : 'min-w-[6rem]',
            };
            $colgroup .= '<col class="' . $widthClass . '" />';
        }

        $thead = '<thead class="fc-sp-table-head text-left"><tr>';
        foreach ($columns as $colIndex => $col) {
            $sticky = $colIndex === 0 ? ' fc-sp-sticky fc-sp-sticky-col relative' : '';
            $class = ($col === 'Images' ? 'fc-sys-images-cell ' : ($col === 'SKU' ? 'fc-sys-sku-cell ' : ($col === 'Name' ? 'fc-sys-name-cell ' : 'whitespace-nowrap ')))
                . 'px-3 py-2' . ($col === 'Images' ? ' fc-sys-images-cell--head' : '') . $sticky;
            $thead .= '<th scope="col" class="' . $class . '">' . StringHelper::escapeHtml(ViewHelper::formatHeader($col)) . '</th>';
        }
        $thead .= '</tr></thead>';

        $tbody = '<tbody class="divide-y divide-slate-100 text-sm text-slate-700">';
        foreach ($rows as $rowIdx => $row) {
            $stripeBg = $rowIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
            $tbody .= '<tr class="' . $stripeBg . '/50">';
            foreach ($columns as $colIndex => $col) {
                $val = (string) ($row[$col] ?? '');
                $formatted = self::cellHtml($col, $val, $row);
                $sticky = $colIndex === 0 ? ' fc-sp-sticky fc-sp-sticky-col relative ' . $stripeBg : '';
                $class = 'border-b border-slate-100' . $sticky
                    . ($formatted['empty'] && $col !== 'Images' ? ' text-slate-300' : '')
                    . ($col === 'Images' ? ' fc-sys-images-cell' : ' px-3 py-2')
                    . ($col === 'SKU' ? ' fc-sys-sku-cell' : '')
                    . ($col === 'Name' ? ' fc-sys-name-cell' : '')
                    . (in_array($col, ['Name', 'Images', 'SKU'], true) ? '' : ' whitespace-nowrap');
                $tbody .= '<td class="' . trim($class) . '">' . $formatted['html'] . '</td>';
            }
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        $table = '<table class="fc-system-products-table fc-system-products-table--fixed w-full border-collapse text-left">'
            . '<colgroup>' . $colgroup . '</colgroup>'
            . $thead . $tbody
            . '</table>';

        if ($flatScroll) {
            return $table;
        }

        return '<div class="fc-sp-table-layout flex min-h-0 flex-1 flex-col">'
            . '<div class="fc-system-products-scroll fc-sp-table-body fc-sp-hide-x-scrollbar min-h-0 flex-1 overflow-x-hidden overflow-y-auto">'
            . $table
            . '</div>'
            . '<div class="fc-sp-bottom-scrollbar" aria-label="Scroll table horizontally">'
            . '<div class="fc-sp-bottom-scrollbar-spacer h-px"></div></div></div>';
    }

    /**
     * @param array<string, scalar|null> $overrides
     */
    public static function url(string $adminBase, array $overrides = []): string
    {
        $base = ViewHelper::adminUrl($adminBase, 'products/store-products');
        $perPageRaw = $overrides['per_page'] ?? 50;
        $isAll = is_string($perPageRaw) && strtolower(trim($perPageRaw)) === 'all';
        $params = array_filter(
            [
                'source'   => strtoupper(trim((string) ($overrides['source'] ?? 'GO'))),
                'q'        => trim((string) ($overrides['q'] ?? '')),
                'page'     => $isAll ? 1 : (int) ($overrides['page'] ?? 1),
                'per_page' => $isAll ? 'all' : (int) $perPageRaw,
            ],
            static function ($value, string $key): bool {
                if ($key === 'source') {
                    return $value !== '' && $value !== 'GO';
                }
                if ($key === 'page') {
                    return (int) $value > 1;
                }
                if ($key === 'per_page') {
                    if ($value === 'all') {
                        return true;
                    }

                    return (int) $value > 0 && (int) $value !== 50;
                }

                return $value !== '' && $value !== null;
            },
            ARRAY_FILTER_USE_BOTH
        );

        if ($params === []) {
            return $base;
        }

        return $base . '?' . http_build_query($params);
    }

    /**
     * @param list<int|string> $pages
     * @return list<array{type:string,label:string,url:string}>
     */
    public static function paginationLinks(
        string $adminBase,
        string $source,
        string $search,
        int|string $perPage,
        int $currentPage,
        array $pages
    ): array {
        $links = [];
        foreach ($pages as $pageNum) {
            if ($pageNum === '…') {
                $links[] = ['type' => 'ellipsis', 'label' => '…', 'url' => ''];
                continue;
            }
            $num = (int) $pageNum;
            $links[] = [
                'type'  => $num === $currentPage ? 'current' : 'link',
                'label' => (string) $num,
                'url'   => self::url($adminBase, [
                    'source'   => $source,
                    'q'        => $search,
                    'page'     => $num,
                    'per_page' => $perPage,
                ]),
            ];
        }

        return $links;
    }

    /**
     * @param array<string, string> $query
     * @return array<string, mixed>
     */
    public static function viewData(string $adminBase, array $query = []): array
    {
        $source = strtoupper(trim((string) ($query['source'] ?? 'GO')));
        if (!in_array($source, ['GO', 'JG'], true)) {
            $source = 'GO';
        }
        $search = trim((string) ($query['q'] ?? ''));
        $perPageOptions = [25, 50, 100, 200];
        $perPageRaw = strtolower(trim((string) ($query['per_page'] ?? '50')));
        $isAll = $perPageRaw === 'all';
        $perPage = $isAll
            ? 0
            : (in_array((int) $perPageRaw, $perPageOptions, true) ? (int) $perPageRaw : 50);
        $page = $isAll ? 1 : max(1, (int) ($query['page'] ?? 1));

        $displayColumns = ['Images', 'SKU', 'Name'];
        // 'Slug' rides along on each row (for the SKU cell's "View Product" link) without
        // becoming its own header column — stripped back out of $columns below.
        $fetchColumns = array_merge($displayColumns, ['Slug']);

        $tabs = [];
        foreach (['GO', 'JG'] as $tab) {
            $tabMeta = SystemProductModel::count($tab);
            $tabs[] = [
                'id'          => $tab,
                'label'       => $tab,
                'is_active'   => $tab === $source,
                'count'       => (int) ($tabMeta['total'] ?? 0),
                'count_label' => !empty($tabMeta['ok']) ? (string) (int) ($tabMeta['total'] ?? 0) : '…',
                'href'        => self::url($adminBase, [
                    'source'   => $tab,
                    'q'        => $search,
                    'page'     => 1,
                    'per_page' => $isAll ? 'all' : $perPage,
                ]),
            ];
        }

        // Counts first (cached), then stream only the visible page from CSV.
        $queryPerPage = $isAll ? PHP_INT_MAX : $perPage;
        $payload = SystemProductModel::query($source, $search, $page, $queryPerPage, $fetchColumns);
        if (
            !$isAll
            && !empty($payload['ok'])
            && $page > 1
            && (int) ($payload['total_pages'] ?? 1) >= 1
            && $page > (int) $payload['total_pages']
        ) {
            $page = max(1, (int) $payload['total_pages']);
            $payload = SystemProductModel::query($source, $search, $page, $queryPerPage, $fetchColumns);
        }

        $error = !empty($payload['ok']) ? '' : (string) ($payload['error'] ?? 'Could not load store products.');
        $columns = self::orderColumns(
            array_values(array_filter(
                is_array($payload['columns'] ?? null) ? $payload['columns'] : [],
                static fn($col): bool => is_string($col) && $col !== 'Slug'
            ))
        );
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        $total = (int) ($payload['total'] ?? 0);
        $totalPages = $isAll ? 1 : max(1, (int) ($payload['total_pages'] ?? 1));
        $currentPage = $isAll ? 1 : max(1, (int) ($payload['page'] ?? $page));
        $formAction = ViewHelper::adminUrl($adminBase, 'products/store-products');
        $perPageUrlValue = $isAll ? 'all' : $perPage;

        $countLabel = $search !== ''
            ? $total . ' match' . ($total === 1 ? '' : 'es')
            : (string) $total;

        $tableHtml = '';
        if ($error === '') {
            if ($rows === []) {
                $tableHtml = '<p class="p-8 text-center text-sm text-slate-500">'
                    . ($search !== '' ? 'No store products match your search.' : 'No store products found.')
                    . '</p>';
            } else {
                $tableHtml = self::tableHtml($columns, $rows, true);
            }
        }

        $paginationPages = ViewHelper::paginationWindow($currentPage, $totalPages);

        $pagination = [
            'show'     => !$isAll && $totalPages > 1,
            'prev_url' => (!$isAll && $currentPage > 1)
                ? self::url($adminBase, [
                    'source'   => $source,
                    'q'        => $search,
                    'page'     => $currentPage - 1,
                    'per_page' => $perPageUrlValue,
                ])
                : '',
            'next_url' => (!$isAll && $currentPage < $totalPages)
                ? self::url($adminBase, [
                    'source'   => $source,
                    'q'        => $search,
                    'page'     => $currentPage + 1,
                    'per_page' => $perPageUrlValue,
                ])
                : '',
        ];

        $clearUrl = self::url($adminBase, [
            'source'   => $source,
            'per_page' => $perPageUrlValue,
        ]);

        $canEdit = PermissionService::can('products.store_products.download');

        return [
            'error'              => $error,
            'source'             => $source,
            'search'             => $search,
            'has_active_search'  => $search !== '',
            'columns'            => $columns,
            'tabs'               => $tabs,
            'file_label'         => (string) ($payload['file'] ?? ''),
            'count_label'        => $countLabel,
            'table_html'         => $tableHtml,
            'form_action'        => $formAction,
            'clear_url'          => $clearUrl,
            'page'               => $currentPage,
            'per_page'           => $isAll ? 'all' : $perPage,
            'is_all'             => $isAll,
            'per_page_options'   => $perPageOptions,
            'total'              => $total,
            'total_pages'        => $totalPages,
            'pagination'         => $pagination,
            'pagination_links'   => $isAll
                ? []
                : self::paginationLinks(
                    $adminBase,
                    $source,
                    $search,
                    $perPage,
                    $currentPage,
                    $paginationPages
                ),
            'can_edit'           => $canEdit,
            'bootstrap_json'     => ViewHelper::bootstrapJson([
                'ok'          => !empty($payload['ok']),
                'phpRendered' => true,
                'deferLoad'   => false,
                'source'      => $source,
                'filters'     => [
                    'source'   => $source,
                    'q'        => $search,
                    'page'     => $currentPage,
                    'per_page' => $perPageUrlValue,
                ],
                'total'       => $total,
                'file'        => (string) ($payload['file'] ?? ''),
                'csrf'        => AuthService::csrfToken(),
                'tabTotals'   => [
                    'GO' => (int) ($tabs[0]['count'] ?? 0),
                    'JG' => (int) ($tabs[1]['count'] ?? 0),
                ],
                'canEdit'     => $canEdit,
            ]),
        ];
    }
}
