<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Helpers\StringHelper;
use Fc\Admin\Helpers\ViewHelper;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\PermissionService;

/**
 * Store products (writable/products.csv) row shaping — pure, DB-free formatting/view-model
 * helpers, plus the page-level viewData() orchestrator. No dependency on StoreProductModel
 * (kept one-directional: Model depends on this class, not the other way around).
 */
final class StoreProductPresenter
{
    /**
     * Columns shown on the system-products list table (products.csv).
     *
     * @return list<string>
     */
    public static function listColumns(): array
    {
        return ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'SKUs', 'STYLE', 'Colors'];
    }

    public static function csvStyleForFence(string $fenceSlug): string
    {
        if ($fenceSlug === 'slat_fence_infill') {
            return 'slat_infill';
        }
        if ($fenceSlug === 'slat_fence') {
            return 'slat';
        }

        return $fenceSlug;
    }

    /**
     * @return array<string, string> STYLE csv key => human label
     */
    public static function styleLabels(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [];
        foreach (PlannerEntryPresenter::fenceCatalog() as $fenceKey => $info) {
            if (!is_array($info)) {
                continue;
            }
            $plannerSlug = (string) ($info['slug'] ?? $fenceKey);
            $styleKey = self::csvStyleForFence($plannerSlug);
            $title = trim((string) ($info['title'] ?? ''));
            if ($title === '') {
                $title = ViewHelper::formatHeader($styleKey);
            }
            $cache[$styleKey] = $title;
        }

        return $cache;
    }

    public static function styleLabel(string $styleKey, array $styleLabels = []): string
    {
        $styleKey = trim($styleKey);
        if ($styleKey === '') {
            return '';
        }
        if ($styleLabels !== [] && isset($styleLabels[$styleKey])) {
            return (string) $styleLabels[$styleKey];
        }

        return ViewHelper::formatHeader($styleKey);
    }

    /**
     * Maps products.csv STYLE values to allowed color column names (uppercase CSV headers).
     *
     * @return array<string, list<string>>
     */
    public static function styleColorsMap(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [];
        foreach (PlannerEntryPresenter::fenceCatalog() as $fenceKey => $info) {
            if (!is_array($info) || !isset($info['color']) || !is_array($info['color'])) {
                continue;
            }

            $plannerSlug = (string) ($info['slug'] ?? $fenceKey);
            $styleKey = self::csvStyleForFence($plannerSlug);
            $columns = [];
            foreach ($info['color'] as $colorSlug) {
                $columns[] = self::colorSlugToCsvColumn((string) $colorSlug);
            }
            $cache[$styleKey] = array_values(array_unique($columns));
        }

        return $cache;
    }

    public static function csvColumnToColorSlug(string $column): string
    {
        return strtolower(str_replace('-', '_', trim($column)));
    }

    public static function colorSlugToCsvColumn(string $slug): string
    {
        return strtoupper(str_replace('-', '_', $slug));
    }

    public static function colorBackground(string $csvColumn): string
    {
        if (!function_exists('fc_color')) {
            require_once FC_ROOT . '/writable/settings.php';
        }

        $slug = self::csvColumnToColorSlug($csvColumn);
        $colors = fc_color();
        if (is_array($colors[$slug] ?? null)) {
            return (string) ($colors[$slug]['background_color'] ?? '#cbd5e1');
        }

        return '#cbd5e1';
    }

    public static function colorLabel(string $csvColumn): string
    {
        if (!function_exists('fc_color')) {
            require_once FC_ROOT . '/writable/settings.php';
        }

        $slug = self::csvColumnToColorSlug($csvColumn);
        $colors = fc_color();
        if (is_array($colors[$slug] ?? null)) {
            $title = trim((string) ($colors[$slug]['title'] ?? ''));
            if ($title !== '') {
                return $title;
            }
        }

        return ViewHelper::formatHeader($csvColumn);
    }

    /**
     * Strips the fixed detail columns (SLUG, PRODUCT, DESCRIPTION, SUPPLIER, STYLE) from a
     * CSV header list, leaving only color-SKU columns.
     *
     * @param list<string> $columns
     * @return list<string>
     */
    public static function colorColumnsFromList(array $columns): array
    {
        $detailColumns = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE'];

        return array_values(array_filter(
            $columns,
            static fn(string $col): bool => !in_array($col, $detailColumns, true)
        ));
    }

    /**
     * @return list<string>
     */
    public static function parseColorFilters(array $query, array $allowedColumns = []): array
    {
        $raw = $query['color'] ?? [];
        if (is_string($raw)) {
            $raw = trim($raw) !== '' ? [$raw] : [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $allowedLookup = [];
        foreach ($allowedColumns as $column) {
            $allowedLookup[strtoupper($column)] = $column;
        }

        $selected = [];
        foreach ($raw as $value) {
            $key = strtoupper(trim((string) $value));
            if ($key === '') {
                continue;
            }
            if ($allowedLookup !== [] && !isset($allowedLookup[$key])) {
                continue;
            }
            $selected[] = $allowedLookup[$key] ?? $key;
        }

        return array_values(array_unique($selected));
    }

    /**
     * @param list<array{column:string,label:string,background:string}> $options
     * @param list<string> $selected
     */
    public static function colorFilterLabel(array $options, array $selected): string
    {
        if ($selected === []) {
            return 'All colors';
        }

        $labelsByColumn = [];
        foreach ($options as $option) {
            $column = (string) ($option['column'] ?? '');
            if ($column === '') {
                continue;
            }
            $labelsByColumn[strtoupper($column)] = (string) ($option['label'] ?? $column);
        }

        $labels = [];
        foreach ($selected as $column) {
            $labels[] = $labelsByColumn[strtoupper($column)] ?? self::colorLabel($column);
        }

        if ($labels === []) {
            return 'All colors';
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
     * @param list<string> $columns
     * @param list<string> $selected
     * @return list<array{column:string,label:string,background:string,is_checked:bool}>
     */
    public static function colorOptions(array $columns, array $selected): array
    {
        $selectedLookup = [];
        foreach ($selected as $column) {
            $selectedLookup[strtoupper($column)] = true;
        }

        $options = [];
        foreach ($columns as $column) {
            $options[] = [
                'column'      => $column,
                'label'       => self::colorLabel($column),
                'background'  => self::colorBackground($column),
                'is_checked'  => isset($selectedLookup[strtoupper($column)]),
            ];
        }

        return $options;
    }

    /**
     * Style-allowed color SKU columns for a product row (same rules as the edit modal).
     *
     * @param list<string> $columns
     * @param array<string, list<string>> $styleColors
     * @return list<string>
     */
    public static function allowedColorColumns(array $row, array $columns, array $styleColors): array
    {
        $style = trim((string) ($row['STYLE'] ?? ''));
        if (isset($styleColors[$style]) && is_array($styleColors[$style])) {
            return array_values($styleColors[$style]);
        }

        return self::colorColumnsFromList($columns);
    }

    /**
     * @param list<string> $allColumns
     * @param array<string, list<string>> $styleColors
     * @param array<string, true> $skuSetLookup Uppercased/exact trimmed SKU => true
     * @return array{found:int,total:int,complete:bool}
     */
    public static function skusSummary(
        array $row,
        array $allColumns,
        array $styleColors,
        array $skuSetLookup
    ): array {
        $allowed = self::allowedColorColumns($row, $allColumns, $styleColors);
        $total = count($allowed);
        $found = 0;

        foreach ($allowed as $column) {
            $sku = trim((string) ($row[$column] ?? ''));
            if ($sku === '' || strtoupper($sku) === 'OFF') {
                continue;
            }
            if (isset($skuSetLookup[$sku])) {
                $found++;
            }
        }

        return [
            'found'    => $found,
            'total'    => $total,
            'complete' => $total > 0 && $found === $total,
        ];
    }

    /**
     * @param list<string> $allColumns
     * @param array<string, list<string>> $styleColors
     * @param array<string, true> $skuSetLookup
     */
    public static function skusCellHtml(
        array $row,
        array $allColumns,
        array $styleColors,
        array $skuSetLookup
    ): string {
        $summary = self::skusSummary($row, $allColumns, $styleColors, $skuSetLookup);
        if ($summary['total'] === 0) {
            return '<span class="text-slate-300">—</span>';
        }

        $complete = $summary['complete'];
        $statusClass = $complete ? 'fc-sp-sku-status--found' : 'fc-sp-sku-status--missing';
        $wrapClass = $complete ? 'fc-sp-skus-summary--complete' : 'fc-sp-skus-summary--incomplete';
        $ratio = $summary['found'] . '/' . $summary['total'];
        $label = $complete
            ? 'All style SKUs found in store catalogue'
            : 'Some style SKUs missing from store catalogue';

        return '<span class="fc-sp-skus-summary ' . $wrapClass . '" title="' . StringHelper::escapeHtml($label) . '">'
            . '<span class="fc-sp-sku-status ' . $statusClass . '" aria-hidden="true"></span>'
            . '<span>' . StringHelper::escapeHtml($ratio) . '</span>'
            . '</span>';
    }

    /**
     * @param list<string> $allColumns
     * @param array<string, list<string>> $styleColors
     */
    public static function colorsCellHtml(array $row, array $allColumns, array $styleColors): string
    {
        $allowed = self::allowedColorColumns($row, $allColumns, $styleColors);

        $items = [];
        foreach ($allowed as $column) {
            $sku = trim((string) ($row[$column] ?? ''));
            if ($sku === '' || strtoupper($sku) === 'OFF') {
                continue;
            }

            $label = strtoupper(str_replace(['-', '_'], ' ', $column));
            $background = self::colorBackground($column);
            $items[] = '<span class="fc-sys-product-color">'
                . '<span class="fc-sys-product-color__swatch" style="background:'
                . StringHelper::escapeHtml($background)
                . ';" title="' . StringHelper::escapeHtml($label) . '" aria-hidden="true"></span>'
                . '<span class="fc-sys-product-color__label">' . StringHelper::escapeHtml($label) . '</span>'
                . '</span>';
        }

        if ($items === []) {
            return '<span class="text-slate-300">—</span>';
        }

        return '<div class="fc-sys-product-colors">' . implode('', $items) . '</div>';
    }

    /**
     * @param list<string> $values
     * @param array<string, string> $labelMap
     * @return list<array{value:string,label:string,is_selected:bool}>
     */
    public static function selectOptionsLabeled(
        array $values,
        string $selected,
        string $allLabel,
        array $labelMap = []
    ): array {
        $options = [
            ['value' => '', 'label' => $allLabel, 'is_selected' => $selected === ''],
        ];
        foreach ($values as $value) {
            $options[] = [
                'value'        => $value,
                'label'        => $labelMap[$value] ?? ViewHelper::formatHeader($value),
                'is_selected'  => $value === $selected,
            ];
        }

        return $options;
    }

    /**
     * @param list<string> $values
     * @return list<array{value:string,label:string,is_selected:bool}>
     */
    public static function selectOptions(array $values, string $selected, string $allLabel): array
    {
        $options = [
            ['value' => '', 'label' => $allLabel, 'is_selected' => $selected === ''],
        ];
        foreach ($values as $value) {
            $options[] = [
                'value'        => $value,
                'label'        => $value,
                'is_selected'  => $value === $selected,
            ];
        }

        return $options;
    }

    /**
     * @param list<string> $allColumns
     * @param list<array<string, mixed>> $rows
     * @param list<string> $displayColumns
     * @param array<string, string> $styleLabels
     * @param array<string, list<string>> $styleColors
     * @param list<string>|array<string, true> $skuSet
     */
    public static function tableHtml(
        array $allColumns,
        array $rows,
        bool $draggable,
        bool $flatScroll = false,
        array $displayColumns = [],
        array $styleLabels = [],
        array $styleColors = [],
        bool $canEdit = true,
        array $skuSet = [],
        string $sortColumn = '',
        string $sortDir = 'asc'
    ): string {
        if ($displayColumns === []) {
            $displayColumns = $allColumns;
        }

        $skuSetLookup = [];
        foreach ($skuSet as $key => $value) {
            if (is_string($key) && $value === true) {
                $skuSetLookup[$key] = true;
                continue;
            }
            $sku = trim((string) $value);
            if ($sku !== '') {
                $skuSetLookup[$sku] = true;
            }
        }

        $colgroup = ($draggable ? '<col class="w-10" />' : '');
        foreach ($displayColumns as $col) {
            $widthClass = match ($col) {
                'SLUG' => 'w-[14rem]',
                'PRODUCT' => 'w-[18rem]',
                'SUPPLIER' => 'w-[6rem]',
                'STYLE' => 'w-[7rem]',
                'Colors' => 'fc-sys-product-colors-col',
                'DESCRIPTION' => 'fc-sys-product-desc-col',
                'SKUs' => 'fc-sys-product-skus-col',
                default => 'w-[8rem]',
            };
            $colgroup .= '<col class="' . $widthClass . '" />';
        }

        $thead = '<thead class="fc-sp-table-head text-left"><tr>';
        if ($draggable) {
            $thead .= '<th scope="col" class="fc-sp-sticky fc-sp-sticky-grip px-2 py-2 w-10" aria-label="Reorder"></th>';
        }
        foreach ($displayColumns as $colIndex => $col) {
            $sticky = $colIndex === 0 ? ' fc-sp-sticky fc-sp-sticky-col relative' : '';
            $header = $col === 'Colors' || $col === 'SKUs' ? $col : ViewHelper::formatHeader($col);
            $sortable = $col !== 'Colors';
            $isActiveSort = $sortable && $sortColumn === $col;
            $ariaSort = $isActiveSort ? ($sortDir === 'desc' ? 'descending' : 'ascending') : 'none';
            $iconClass = $isActiveSort
                ? ($sortDir === 'desc' ? 'fa-sort-down text-slate-600' : 'fa-sort-up text-slate-600')
                : 'fa-sort text-slate-300';
            $headClass = 'whitespace-nowrap px-3 py-2' . $sticky
                . ($col === 'DESCRIPTION' ? ' fc-sys-product-desc-cell' : '')
                . ($col === 'Colors' ? ' fc-sys-product-colors-cell' : '')
                . ($col === 'SKUs' ? ' fc-sys-product-skus-cell' : '')
                . ($sortable ? ' cursor-pointer select-none hover:bg-slate-100' : '');
            $thead .= '<th scope="col" class="' . trim($headClass) . '"'
                . ($sortable ? ' data-sort-col="' . StringHelper::escapeHtml($col) . '" role="button" tabindex="0" aria-sort="' . $ariaSort . '"' : '')
                . '>' . ($sortable
                    ? '<div class="flex items-center justify-between gap-1"><span>' . StringHelper::escapeHtml($header) . '</span>'
                        . '<i class="fa-solid ' . $iconClass . ' fc-sp-sort-icon" aria-hidden="true"></i></div>'
                    : '<span>' . StringHelper::escapeHtml($header) . '</span>')
                . '</th>';
        }
        $thead .= '</tr></thead>';

        $tbody = '<tbody id="fc-store-products-tbody" class="divide-y divide-slate-100 text-sm text-slate-700">';
        foreach ($rows as $rowIdx => $row) {
            $dataRowIndex = (int) ($row['_rowIndex'] ?? $rowIdx);
            $stripeBg = $rowIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
            $rowClass = 'fc-store-products-row ' . $stripeBg . '/50';
            if ($canEdit) {
                $rowClass .= ' fc-store-products-row--clickable';
            }
            $tbody .= '<tr data-row-index="' . $dataRowIndex . '"'
                . ($canEdit ? ' data-fc-sp-editable="1"' : '')
                . ' class="' . $rowClass . '">';
            if ($draggable) {
                $tbody .= '<td class="fc-sp-sticky fc-sp-sticky-grip cursor-grab border-b border-slate-100 px-2 py-2 text-center text-slate-400 active:cursor-grabbing '
                    . $stripeBg . '" title="Drag to reorder"><i class="fa-solid fa-grip-vertical pointer-events-none" aria-hidden="true"></i></td>';
            }
            foreach ($displayColumns as $colIndex => $col) {
                $sticky = $colIndex === 0 ? ' fc-sp-sticky fc-sp-sticky-col relative ' . $stripeBg : '';
                if ($col === 'Colors') {
                    $cellHtml = self::colorsCellHtml($row, $allColumns, $styleColors);
                    $tbody .= '<td class="border-b border-slate-100 px-3 py-2 fc-sys-product-colors-cell' . $sticky . '">'
                        . $cellHtml . '</td>';
                    continue;
                }
                if ($col === 'SKUs') {
                    $cellHtml = self::skusCellHtml($row, $allColumns, $styleColors, $skuSetLookup);
                    $tbody .= '<td class="border-b border-slate-100 px-3 py-2 fc-sys-product-skus-cell' . $sticky . '">'
                        . $cellHtml . '</td>';
                    continue;
                }

                $val = (string) ($row[$col] ?? '');
                $empty = $val === '';
                $displayVal = $col === 'STYLE'
                    ? self::styleLabel($val, $styleLabels)
                    : $val;
                $cellClass = 'border-b border-slate-100 px-3 py-2' . $sticky
                    . ($empty ? ' text-slate-300' : '')
                    . ($col === 'DESCRIPTION' ? ' fc-sys-product-desc-cell' : '');
                $tbody .= '<td class="' . trim($cellClass) . '"'
                    . ($col === 'DESCRIPTION' && !$empty ? ' title="' . StringHelper::escapeHtml($displayVal) . '"' : '')
                    . '>'
                    . ($empty ? '—' : StringHelper::escapeHtml($displayVal))
                    . '</td>';
            }
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        $table = '<table class="fc-store-products-table fc-sp-table-fixed border-collapse text-left">'
            . '<colgroup>' . $colgroup . '</colgroup>'
            . $thead . $tbody
            . '</table>';

        if ($flatScroll) {
            return $table;
        }

        $scrollClass = 'fc-store-products-scroll fc-sp-table-body fc-sp-hide-x-scrollbar min-h-0 flex-1 overflow-x-hidden overflow-y-auto'
            . ($draggable ? ' fc-sp-has-grip' : '');

        return '<div class="fc-sp-table-layout flex min-h-0 flex-1 flex-col">'
            . '<div class="' . $scrollClass . '">'
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
        $base = ViewHelper::adminUrl($adminBase, 'products/system-products');
        $perPageRaw = $overrides['per_page'] ?? 50;
        $isAll = is_string($perPageRaw) && strtolower(trim($perPageRaw)) === 'all';

        $colors = [];
        if (isset($overrides['colors']) && is_array($overrides['colors'])) {
            $colors = array_values(array_filter(array_map(
                static fn($value): string => trim((string) $value),
                $overrides['colors']
            )));
        }
        unset($overrides['colors']);

        $params = array_filter(
            [
                'supplier' => trim((string) ($overrides['supplier'] ?? '')),
                'style'    => trim((string) ($overrides['style'] ?? '')),
                'q'        => trim((string) ($overrides['q'] ?? '')),
                'sort'     => trim((string) ($overrides['sort'] ?? '')),
                'dir'      => strtolower(trim((string) ($overrides['dir'] ?? ''))),
                'page'     => $isAll ? 1 : (int) ($overrides['page'] ?? 1),
                'per_page' => $isAll ? 'all' : (int) $perPageRaw,
            ],
            static function ($value, string $key): bool {
                if ($key === 'page') {
                    return (int) $value > 1;
                }
                if ($key === 'per_page') {
                    if ($value === 'all') {
                        return true;
                    }

                    return (int) $value > 0 && (int) $value !== 50;
                }
                if ($key === 'dir') {
                    return $value === 'desc';
                }

                return $value !== '' && $value !== null;
            },
            ARRAY_FILTER_USE_BOTH
        );

        if ($colors !== []) {
            $params['color'] = $colors;
        }

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
        array $filters,
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
                'url'   => self::url($adminBase, array_merge($filters, [
                    'page'     => $num,
                    'per_page' => $perPage,
                ])),
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
        $filterMeta = StoreProductModel::filterOptions();
        $colorColumnValues = is_array($filterMeta['colors'] ?? null) ? $filterMeta['colors'] : [];
        $selectedColors = self::parseColorFilters($query, $colorColumnValues);

        $sortableColumns = array_values(array_diff(self::listColumns(), ['Colors']));
        $sortColumn = trim((string) ($query['sort'] ?? ''));
        if (!in_array($sortColumn, $sortableColumns, true)) {
            $sortColumn = '';
        }
        $sortDir = strtolower(trim((string) ($query['dir'] ?? 'asc'))) === 'desc' ? 'desc' : 'asc';

        $filters = [
            'supplier' => trim((string) ($query['supplier'] ?? '')),
            'style'    => trim((string) ($query['style'] ?? '')),
            'q'        => trim((string) ($query['q'] ?? '')),
            'colors'   => $selectedColors,
            'sort'     => $sortColumn,
            'dir'      => $sortDir,
        ];
        $hasActiveFilters = $filters['supplier'] !== ''
            || $filters['style'] !== ''
            || $filters['q'] !== ''
            || $filters['colors'] !== [];

        $perPageOptions = [50, 100, 250, 500];
        $perPageRaw = strtolower(trim((string) ($query['per_page'] ?? '50')));
        $isAll = $perPageRaw === 'all';
        $perPage = $isAll
            ? 0
            : (in_array((int) $perPageRaw, $perPageOptions, true) ? (int) $perPageRaw : 50);
        $page = $isAll ? 1 : max(1, (int) ($query['page'] ?? 1));
        $perPageUrlValue = $isAll ? 'all' : $perPage;

        $supplierValues = is_array($filterMeta['suppliers'] ?? null) ? $filterMeta['suppliers'] : [];
        $styleValues = is_array($filterMeta['styles'] ?? null) ? $filterMeta['styles'] : [];
        $colorOptions = self::colorOptions($colorColumnValues, $selectedColors);

        $payload = StoreProductModel::query($filters, $page, $isAll ? 50 : $perPage, $isAll);
        if (
            !$isAll
            && !empty($payload['ok'])
            && $page > 1
            && (int) ($payload['total_pages'] ?? 1) >= 1
            && $page > (int) $payload['total_pages']
        ) {
            $page = max(1, (int) $payload['total_pages']);
            $payload = StoreProductModel::query($filters, $page, $perPage, false);
        }

        $error = !empty($payload['ok']) ? '' : (string) ($payload['error'] ?? 'Could not load system products.');
        $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        $styleLabels = self::styleLabels();
        $styleColors = is_array($payload['styleColors'] ?? null) ? $payload['styleColors'] : [];
        $displayColumns = self::listColumns();
        $total = (int) ($payload['total'] ?? 0);
        $totalPages = $isAll ? 1 : max(1, (int) ($payload['total_pages'] ?? 1));
        $currentPage = $isAll ? 1 : max(1, (int) ($payload['page'] ?? $page));
        $formAction = ViewHelper::adminUrl($adminBase, 'products/system-products');
        $canEdit = PermissionService::can('products.system_products.edit');
        $canReorder = $canEdit && $isAll && !$hasActiveFilters && $sortColumn === '';

        $countLabel = $hasActiveFilters
            ? $total . ' match' . ($total === 1 ? '' : 'es')
            : (string) $total;

        $skuSet = [];
        if ($error === '' && $rows !== []) {
            $skuSet = \Fc\Admin\Services\WcProductSkuIndex::skuUnion();
        }

        $tableHtml = '';
        if ($error === '') {
            if ($rows === []) {
                $tableHtml = '<p class="p-8 text-center text-sm text-slate-500">'
                    . ($hasActiveFilters ? 'No system products match your filters.' : 'No system products found.')
                    . '</p>';
            } else {
                $tableHtml = self::tableHtml(
                    $columns,
                    $rows,
                    $canReorder,
                    true,
                    $displayColumns,
                    $styleLabels,
                    $styleColors,
                    $canEdit,
                    $skuSet,
                    $sortColumn,
                    $sortDir
                );
            }
        }

        $paginationPages = ViewHelper::paginationWindow($currentPage, $totalPages);
        $pagination = [
            'show'     => !$isAll && $totalPages > 1,
            'prev_url' => (!$isAll && $currentPage > 1)
                ? self::url($adminBase, array_merge($filters, [
                    'page'     => $currentPage - 1,
                    'per_page' => $perPageUrlValue,
                ]))
                : '',
            'next_url' => (!$isAll && $currentPage < $totalPages)
                ? self::url($adminBase, array_merge($filters, [
                    'page'     => $currentPage + 1,
                    'per_page' => $perPageUrlValue,
                ]))
                : '',
        ];

        $clearUrl = self::url($adminBase, [
            'per_page' => $perPageUrlValue,
        ]);

        return [
            'error'              => $error,
            'columns'            => $columns,
            'filters'            => $filters,
            'has_active_filters' => $hasActiveFilters,
            'supplier_options'   => self::selectOptions(
                $supplierValues,
                $filters['supplier'],
                'All suppliers'
            ),
            'style_options'      => self::selectOptionsLabeled(
                $styleValues,
                $filters['style'],
                'All styles',
                $styleLabels
            ),
            'color_options'      => $colorOptions,
            'selected_colors'    => $selectedColors,
            'color_filter_label' => self::colorFilterLabel($colorOptions, $selectedColors),
            'count_label'        => $countLabel,
            'file_label'         => (string) ($payload['file'] ?? 'products.csv'),
            'table_html'         => $tableHtml,
            'form_action'        => $formAction,
            'clear_url'          => $clearUrl,
            'page'               => $currentPage,
            'per_page'           => $isAll ? 'all' : $perPage,
            'is_all'             => $isAll,
            'can_reorder'        => $canReorder,
            'can_edit'           => $canEdit,
            'per_page_options'   => $perPageOptions,
            'total'              => $total,
            'total_pages'        => $totalPages,
            'pagination'         => $pagination,
            'pagination_links'   => $isAll
                ? []
                : self::paginationLinks(
                    $adminBase,
                    $filters,
                    $perPageUrlValue,
                    $currentPage,
                    $paginationPages
                ),
            'bootstrap_json'     => ViewHelper::bootstrapJson([
                'ok'          => !empty($payload['ok']),
                'phpRendered' => true,
                'deferLoad'   => false,
                'columns'     => $columns,
                'rows'        => $rows,
                'total'       => $total,
                'file'        => (string) ($payload['file'] ?? 'products.csv'),
                'styleColors' => $styleColors,
                'filters'     => array_merge($filters, [
                    'page'     => $currentPage,
                    'per_page' => $perPageUrlValue,
                ]),
                'canReorder'  => $canReorder,
                'canEdit'     => $canEdit,
                'csrf'        => AuthService::csrfToken(),
            ]),
        ];
    }
}
