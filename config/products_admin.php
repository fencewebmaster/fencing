<?php
/**
 * FC Admin — products pages view models.
 */

declare(strict_types=1);

use Fc\Admin\Controllers\Api\FenceStylesController;
use Fc\Admin\Controllers\Api\ProductsController;

function fc_products_admin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fc_products_admin_json_attr(mixed $value): string
{
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    return fc_products_admin_h(is_string($json) ? $json : '[]');
}

function fc_products_admin_bootstrap_json(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    return is_string($json) ? $json : '{}';
}

function fc_products_admin_format_header(string $label): string
{
    $text = strtolower(str_replace('_', ' ', trim($label)));
    if ($text === '') {
        return '';
    }

    return ucwords($text);
}

/**
 * Columns shown on the system-products list table (products.csv).
 *
 * @return list<string>
 */
function fc_store_products_admin_list_columns(): array
{
    return ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'SKUs', 'STYLE', 'Colors'];
}

function fc_store_products_admin_csv_style_for_fence(string $fenceSlug): string
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
function fc_store_products_admin_style_labels(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $cache = [];
    $fencesDir = FC_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'fences';
    $files = glob($fencesDir . DIRECTORY_SEPARATOR . '*.php') ?: [];
    sort($files, SORT_NATURAL);

    $fences = [];
    foreach ($files as $fenceFile) {
        if (!is_readable($fenceFile)) {
            continue;
        }
        include $fenceFile;
    }

    foreach ($fences as $fenceKey => $info) {
        if (!is_array($info)) {
            continue;
        }
        $plannerSlug = (string) ($info['slug'] ?? $fenceKey);
        $styleKey = fc_store_products_admin_csv_style_for_fence($plannerSlug);
        $title = trim((string) ($info['title'] ?? ''));
        if ($title === '') {
            $title = fc_products_admin_format_header($styleKey);
        }
        $cache[$styleKey] = $title;
    }

    return $cache;
}

function fc_store_products_admin_style_label(string $styleKey, array $styleLabels = []): string
{
    $styleKey = trim($styleKey);
    if ($styleKey === '') {
        return '';
    }
    if ($styleLabels !== [] && isset($styleLabels[$styleKey])) {
        return (string) $styleLabels[$styleKey];
    }

    return fc_products_admin_format_header($styleKey);
}

function fc_store_products_admin_csv_column_to_color_slug(string $column): string
{
    return strtolower(str_replace('-', '_', trim($column)));
}

function fc_store_products_admin_color_background(string $csvColumn): string
{
    if (!function_exists('fc_color')) {
        require_once FC_ROOT . '/data/settings.php';
    }

    $slug = fc_store_products_admin_csv_column_to_color_slug($csvColumn);
    $colors = fc_color();
    if (is_array($colors[$slug] ?? null)) {
        return (string) ($colors[$slug]['background_color'] ?? '#cbd5e1');
    }

    return '#cbd5e1';
}

function fc_store_products_admin_color_label(string $csvColumn): string
{
    if (!function_exists('fc_color')) {
        require_once FC_ROOT . '/data/settings.php';
    }

    $slug = fc_store_products_admin_csv_column_to_color_slug($csvColumn);
    $colors = fc_color();
    if (is_array($colors[$slug] ?? null)) {
        $title = trim((string) ($colors[$slug]['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }
    }

    return fc_products_admin_format_header($csvColumn);
}

/**
 * @return list<string>
 */
function fc_store_products_admin_color_columns_from_list(array $columns): array
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
function fc_store_products_admin_parse_color_filters(array $query, array $allowedColumns = []): array
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
function fc_store_products_admin_color_filter_label(array $options, array $selected): string
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
        $labels[] = $labelsByColumn[strtoupper($column)] ?? fc_store_products_admin_color_label($column);
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
function fc_store_products_admin_color_options(array $columns, array $selected): array
{
    $selectedLookup = [];
    foreach ($selected as $column) {
        $selectedLookup[strtoupper($column)] = true;
    }

    $options = [];
    foreach ($columns as $column) {
        $options[] = [
            'column'      => $column,
            'label'       => fc_store_products_admin_color_label($column),
            'background'  => fc_store_products_admin_color_background($column),
            'is_checked'  => isset($selectedLookup[strtoupper($column)]),
        ];
    }

    return $options;
}

/**
 * Style-allowed color SKU columns for a product row (same rules as the edit modal).
 *
 * @param list<string> $allColumns
 * @param array<string, list<string>> $styleColors
 * @return list<string>
 */
function fc_store_products_admin_allowed_color_columns(array $row, array $allColumns, array $styleColors): array
{
    $detailColumns = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE'];
    $style = trim((string) ($row['STYLE'] ?? ''));
    if (isset($styleColors[$style]) && is_array($styleColors[$style])) {
        return array_values($styleColors[$style]);
    }

    return array_values(array_filter(
        $allColumns,
        static fn(string $col): bool => !in_array($col, $detailColumns, true)
    ));
}

/**
 * @param list<string> $allColumns
 * @param array<string, list<string>> $styleColors
 * @param array<string, true> $skuSetLookup Uppercased/exact trimmed SKU => true
 * @return array{found:int,total:int,complete:bool}
 */
function fc_store_products_admin_skus_summary(
    array $row,
    array $allColumns,
    array $styleColors,
    array $skuSetLookup
): array {
    $allowed = fc_store_products_admin_allowed_color_columns($row, $allColumns, $styleColors);
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
function fc_store_products_admin_skus_cell_html(
    array $row,
    array $allColumns,
    array $styleColors,
    array $skuSetLookup
): string {
    $summary = fc_store_products_admin_skus_summary($row, $allColumns, $styleColors, $skuSetLookup);
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

    return '<span class="fc-sp-skus-summary ' . $wrapClass . '" title="' . fc_products_admin_h($label) . '">'
        . '<span class="fc-sp-sku-status ' . $statusClass . '" aria-hidden="true"></span>'
        . '<span>' . fc_products_admin_h($ratio) . '</span>'
        . '</span>';
}

/**
 * @param list<string> $allColumns
 * @param array<string, list<string>> $styleColors
 */
function fc_store_products_admin_colors_cell_html(array $row, array $allColumns, array $styleColors): string
{
    $allowed = fc_store_products_admin_allowed_color_columns($row, $allColumns, $styleColors);

    $items = [];
    foreach ($allowed as $column) {
        $sku = trim((string) ($row[$column] ?? ''));
        if ($sku === '' || strtoupper($sku) === 'OFF') {
            continue;
        }

        $label = strtoupper(str_replace('-', '_', $column));
        $background = fc_store_products_admin_color_background($column);
        $items[] = '<span class="fc-sys-product-color">'
            . '<span class="fc-sys-product-color__swatch" style="background:'
            . fc_products_admin_h($background)
            . ';" title="' . fc_products_admin_h($label) . '" aria-hidden="true"></span>'
            . '<span class="fc-sys-product-color__label">' . fc_products_admin_h($label) . '</span>'
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
function fc_store_products_admin_select_options_labeled(
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
            'label'        => $labelMap[$value] ?? fc_products_admin_format_header($value),
            'is_selected'  => $value === $selected,
        ];
    }

    return $options;
}

function fc_products_admin_admin_url(string $adminBase, string $route): string
{
    $base = rtrim(str_replace('\\', '/', $adminBase), '/');
    $path = ltrim(str_replace('\\', '/', $route), '/');

    return $base . '/' . $path;
}

/**
 * @param array<string, string> $query
 * @return array<string, mixed>
 */
function fc_fence_styles_admin_view_data(string $adminBase, array $query = []): array
{
    $payload = FenceStylesController::listPayload();
    $styles = is_array($payload['styles'] ?? null) ? $payload['styles'] : [];
    $error = !empty($payload['ok']) ? '' : (string) ($payload['error'] ?? 'Could not load fence styles.');

    $cards = [];
    $canEdit = !function_exists('fc_auth_user_can') || fc_auth_user_can('products.fence_styles.edit');
    $canView = $canEdit
        || !function_exists('fc_auth_user_can')
        || fc_auth_user_can('products.fence_styles.view');
    foreach ($styles as $style) {
        if (!is_array($style)) {
            continue;
        }
        $slug = (string) ($style['slug'] ?? '');
        $title = (string) ($style['title'] ?? $slug);
        $imageUrl = (string) ($style['imageUrl'] ?? '');
        $editRoute = 'products/fence-styles/edit/' . rawurlencode($slug);
        $cards[] = [
            'slug'       => $slug,
            'title'      => $title,
            'image_url'  => $imageUrl,
            'has_image'  => $imageUrl !== '',
            'is_live'    => !empty($style['live']),
            'badge_class'=> !empty($style['live']) ? 'fc-admin-fence-style-badge--live' : 'fc-admin-fence-style-badge--draft',
            'badge_label'=> !empty($style['live']) ? 'Live' : 'Draft',
            'edit_route' => $editRoute,
            'edit_href'  => fc_products_admin_admin_url($adminBase, $editRoute),
            'can_view'   => $canView,
            'can_edit'   => $canEdit,
        ];
    }

    return [
        'error'          => $error,
        'has_styles'     => $cards !== [],
        'cards'          => $cards,
        'can_view'       => $canView,
        'can_edit'       => $canEdit,
        'bootstrap_json' => fc_products_admin_bootstrap_json([
            'styles'  => $styles,
            'total'   => count($cards),
            'canView' => $canView,
            'canEdit' => $canEdit,
        ]),
    ];
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<string>
 */
function fc_store_products_admin_unique_values(array $rows, string $column): array
{
    $seen = [];
    $values = [];
    $columnKey = strtoupper(trim($column));

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $val = trim((string) ($row[$column] ?? ''));
        if ($val === '' || isset($seen[$val]) || strtoupper($val) === $columnKey) {
            continue;
        }
        $seen[$val] = true;
        $values[] = $val;
    }

    natcasesort($values);

    return array_values($values);
}

/**
 * @param list<string> $values
 * @return list<array{value:string,label:string,is_selected:bool}>
 */
function fc_store_products_admin_select_options(array $values, string $selected, string $allLabel): array
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
 * @param list<array<string, mixed>> $rows
 * @param array<string, string> $filters
 * @return list<array<string, mixed>>
 */
function fc_store_products_admin_filter_rows(array $rows, array $filters): array
{
    $result = $rows;
    $supplier = trim((string) ($filters['supplier'] ?? ''));
    $style = trim((string) ($filters['style'] ?? ''));
    $query = strtolower(trim((string) ($filters['q'] ?? '')));

    if ($supplier !== '') {
        $result = array_values(array_filter(
            $result,
            static fn(array $row): bool => trim((string) ($row['SUPPLIER'] ?? '')) === $supplier
        ));
    }

    if ($style !== '') {
        $result = array_values(array_filter(
            $result,
            static fn(array $row): bool => trim((string) ($row['STYLE'] ?? '')) === $style
        ));
    }

    if ($query !== '') {
        $result = array_values(array_filter($result, static function (array $row) use ($query): bool {
            foreach ($row as $value) {
                if (str_contains(strtolower((string) $value), $query)) {
                    return true;
                }
            }

            return false;
        }));
    }

    return $result;
}

/**
 * @param list<string> $allColumns
 * @param list<array<string, mixed>> $rows
 * @param list<string> $displayColumns
 * @param array<string, string> $styleLabels
 * @param array<string, list<string>> $styleColors
 * @param list<string>|array<string, true> $skuSet
 */
function fc_store_products_admin_table_html(
    array $allColumns,
    array $rows,
    bool $draggable,
    bool $flatScroll = false,
    array $displayColumns = [],
    array $styleLabels = [],
    array $styleColors = [],
    bool $canEdit = true,
    array $skuSet = []
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

    $primaryColumns = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'SKUs', 'STYLE', 'Colors'];
    $colgroup = ($draggable ? '<col class="w-10" />' : '');
    foreach ($displayColumns as $col) {
        $widthClass = match ($col) {
            'Colors' => 'fc-sys-product-colors-col',
            'DESCRIPTION' => 'fc-sys-product-desc-col',
            'SKUs' => 'fc-sys-product-skus-col',
            default => in_array($col, $primaryColumns, true) ? 'min-w-[8rem]' : 'min-w-[6rem]',
        };
        $colgroup .= '<col class="' . $widthClass . '" />';
    }

    $thead = '<thead class="fc-sp-table-head text-left"><tr>';
    if ($draggable) {
        $thead .= '<th scope="col" class="fc-sp-sticky fc-sp-sticky-grip px-2 py-2 w-10" aria-label="Reorder"></th>';
    }
    foreach ($displayColumns as $colIndex => $col) {
        $sticky = $colIndex === 0 ? ' fc-sp-sticky fc-sp-sticky-col relative' : '';
        $header = $col === 'Colors' || $col === 'SKUs' ? $col : fc_products_admin_format_header($col);
        $headClass = 'whitespace-nowrap px-3 py-2' . $sticky
            . ($col === 'DESCRIPTION' ? ' fc-sys-product-desc-cell' : '')
            . ($col === 'Colors' ? ' fc-sys-product-colors-cell' : '')
            . ($col === 'SKUs' ? ' fc-sys-product-skus-cell' : '');
        $thead .= '<th scope="col" class="' . trim($headClass) . '">' . fc_products_admin_h($header) . '</th>';
    }
    $thead .= '</tr></thead>';

    $tbody = '<tbody id="fc-store-products-tbody" class="divide-y divide-slate-100 text-sm text-slate-700">';
    foreach ($rows as $rowIdx => $row) {
        $dataRowIndex = (int) ($row['_rowIndex'] ?? $rowIdx);
        $stripeBg = $rowIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
        $rowClass = 'fc-store-products-row ' . $stripeBg . '/50';
        if ($canEdit) {
            $rowClass .= ' fc-store-products-row--clickable hover:bg-indigo-50/40';
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
                $cellHtml = fc_store_products_admin_colors_cell_html($row, $allColumns, $styleColors);
                $tbody .= '<td class="border-b border-slate-100 px-3 py-2 fc-sys-product-colors-cell' . $sticky . '">'
                    . $cellHtml . '</td>';
                continue;
            }
            if ($col === 'SKUs') {
                $cellHtml = fc_store_products_admin_skus_cell_html($row, $allColumns, $styleColors, $skuSetLookup);
                $tbody .= '<td class="border-b border-slate-100 px-3 py-2 fc-sys-product-skus-cell whitespace-nowrap' . $sticky . '">'
                    . $cellHtml . '</td>';
                continue;
            }

            $val = (string) ($row[$col] ?? '');
            $empty = $val === '';
            $displayVal = $col === 'STYLE'
                ? fc_store_products_admin_style_label($val, $styleLabels)
                : $val;
            $cellClass = 'border-b border-slate-100 px-3 py-2' . $sticky
                . ($empty ? ' text-slate-300' : '')
                . ($col === 'DESCRIPTION' ? ' fc-sys-product-desc-cell max-w-md' : ' whitespace-nowrap');
            $tbody .= '<td class="' . trim($cellClass) . '"'
                . ($col === 'DESCRIPTION' && !$empty ? ' title="' . fc_products_admin_h($displayVal) . '"' : '')
                . '>'
                . ($empty ? '—' : fc_products_admin_h($displayVal))
                . '</td>';
        }
        $tbody .= '</tr>';
    }
    $tbody .= '</tbody>';

    $table = '<table class="fc-store-products-table w-full min-w-max border-collapse text-left">'
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
function fc_store_products_admin_url(string $adminBase, array $overrides = []): string
{
    $base = fc_products_admin_admin_url($adminBase, 'products/system-products');
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
function fc_store_products_admin_pagination_links(
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
            'url'   => fc_store_products_admin_url($adminBase, array_merge($filters, [
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
function fc_store_products_admin_view_data(string $adminBase, array $query = []): array
{
    $filterMeta = ProductsController::getStoreProductFilterOptions();
    $colorColumnValues = is_array($filterMeta['colors'] ?? null) ? $filterMeta['colors'] : [];
    $selectedColors = fc_store_products_admin_parse_color_filters($query, $colorColumnValues);

    $filters = [
        'supplier' => trim((string) ($query['supplier'] ?? '')),
        'style'    => trim((string) ($query['style'] ?? '')),
        'q'        => trim((string) ($query['q'] ?? '')),
        'colors'   => $selectedColors,
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
    $colorOptions = fc_store_products_admin_color_options($colorColumnValues, $selectedColors);

    $payload = ProductsController::queryStoreProducts($filters, $page, $isAll ? 50 : $perPage, $isAll);
    if (
        !$isAll
        && !empty($payload['ok'])
        && $page > 1
        && (int) ($payload['total_pages'] ?? 1) >= 1
        && $page > (int) $payload['total_pages']
    ) {
        $page = max(1, (int) $payload['total_pages']);
        $payload = ProductsController::queryStoreProducts($filters, $page, $perPage, false);
    }

    $error = !empty($payload['ok']) ? '' : (string) ($payload['error'] ?? 'Could not load system products.');
    $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];
    $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
    $styleLabels = fc_store_products_admin_style_labels();
    $styleColors = is_array($payload['styleColors'] ?? null) ? $payload['styleColors'] : [];
    $displayColumns = fc_store_products_admin_list_columns();
    $total = (int) ($payload['total'] ?? 0);
    $totalPages = $isAll ? 1 : max(1, (int) ($payload['total_pages'] ?? 1));
    $currentPage = $isAll ? 1 : max(1, (int) ($payload['page'] ?? $page));
    $formAction = fc_products_admin_admin_url($adminBase, 'products/system-products');
    $canEdit = !function_exists('fc_auth_user_can') || fc_auth_user_can('products.system_products.edit');
    $canReorder = $canEdit && $isAll && !$hasActiveFilters;

    $countLabel = $hasActiveFilters
        ? $total . ' match' . ($total === 1 ? '' : 'es')
        : (string) $total;

    $skuSet = [];
    if ($error === '' && $rows !== []) {
        if (!function_exists('fc_wc_products_sku_union')) {
            require_once FC_ROOT . '/config/wc_products_sku_index.php';
        }
        $skuSet = fc_wc_products_sku_union();
    }

    $tableHtml = '';
    if ($error === '') {
        if ($rows === []) {
            $tableHtml = '<p class="p-8 text-center text-sm text-slate-500">'
                . ($hasActiveFilters ? 'No system products match your filters.' : 'No system products found.')
                . '</p>';
        } else {
            $tableHtml = fc_store_products_admin_table_html(
                $columns,
                $rows,
                $canReorder,
                true,
                $displayColumns,
                $styleLabels,
                $styleColors,
                $canEdit,
                $skuSet
            );
        }
    }

    $paginationPages = fc_system_products_admin_pagination_window($currentPage, $totalPages);
    $pagination = [
        'show'     => !$isAll && $totalPages > 1,
        'prev_url' => (!$isAll && $currentPage > 1)
            ? fc_store_products_admin_url($adminBase, array_merge($filters, [
                'page'     => $currentPage - 1,
                'per_page' => $perPageUrlValue,
            ]))
            : '',
        'next_url' => (!$isAll && $currentPage < $totalPages)
            ? fc_store_products_admin_url($adminBase, array_merge($filters, [
                'page'     => $currentPage + 1,
                'per_page' => $perPageUrlValue,
            ]))
            : '',
    ];

    $clearUrl = fc_store_products_admin_url($adminBase, [
        'per_page' => $perPageUrlValue,
    ]);

    $styleColors = is_array($payload['styleColors'] ?? null) ? $payload['styleColors'] : [];

    return [
        'error'              => $error,
        'columns'            => $columns,
        'filters'            => $filters,
        'has_active_filters' => $hasActiveFilters,
        'supplier_options'   => fc_store_products_admin_select_options(
            $supplierValues,
            $filters['supplier'],
            'All suppliers'
        ),
        'style_options'      => fc_store_products_admin_select_options_labeled(
            $styleValues,
            $filters['style'],
            'All styles',
            $styleLabels
        ),
        'color_options'      => $colorOptions,
        'selected_colors'    => $selectedColors,
        'color_filter_label' => fc_store_products_admin_color_filter_label($colorOptions, $selectedColors),
        'count_label'        => $countLabel,
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
            : fc_store_products_admin_pagination_links(
                $adminBase,
                $filters,
                $perPageUrlValue,
                $currentPage,
                $paginationPages
            ),
        'bootstrap_json'     => fc_products_admin_bootstrap_json([
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
            'csrf'        => function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '',
        ]),
    ];
}

/**
 * @param list<string> $columns
 */
function fc_system_products_admin_order_columns(array $columns): array
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
function fc_system_products_admin_cell_html(string $col, string $val, array $row): array
{
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
            . fc_products_admin_json_attr($urls) . '" data-fc-sys-images-title="'
            . fc_products_admin_h($productName) . '" aria-label="View ' . count($urls)
            . (count($urls) === 1 ? ' image' : ' images') . ' for ' . fc_products_admin_h($productName) . '">'
            . '<img src="' . fc_products_admin_h($thumbUrl) . '" alt="" class="fc-sys-images-thumb" loading="lazy" decoding="async" />'
            . '<span class="fc-sys-images-overlay" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>';
        if ($extraCount > 0) {
            $html .= '<span class="fc-sys-images-badge">+' . $extraCount . '</span>';
        }
        $html .= '</button></div>';

        return ['html' => $html, 'empty' => false];
    }

    if ($col === 'Name') {
        return [
            'html' => '<span class="block max-w-md truncate" title="' . fc_products_admin_h($val) . '">'
                . fc_products_admin_h($val) . '</span>',
            'empty' => false,
        ];
    }

    if ($col === 'SKU') {
        return [
            'html' => '<span class="fc-sys-sku-value block text-sm text-slate-800">'
                . fc_products_admin_h($val) . '</span>',
            'empty' => false,
        ];
    }

    return ['html' => fc_products_admin_h($val), 'empty' => false];
}

/**
 * @param list<string> $columns
 * @param list<array<string, mixed>> $rows
 */
function fc_system_products_admin_table_html(array $columns, array $rows, bool $flatScroll = false): string
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
        $thead .= '<th scope="col" class="' . $class . '">' . fc_products_admin_h(fc_products_admin_format_header($col)) . '</th>';
    }
    $thead .= '</tr></thead>';

    $tbody = '<tbody class="divide-y divide-slate-100 text-sm text-slate-700">';
    foreach ($rows as $rowIdx => $row) {
        $stripeBg = $rowIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
        $tbody .= '<tr class="' . $stripeBg . '/50">';
        foreach ($columns as $colIndex => $col) {
            $val = (string) ($row[$col] ?? '');
            $formatted = fc_system_products_admin_cell_html($col, $val, $row);
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
 * @param list<array<string, mixed>> $rows
 * @param string $query
 * @return list<array<string, mixed>>
 */
function fc_system_products_admin_filter_rows(array $rows, string $query): array
{
    $q = strtolower(trim($query));
    if ($q === '') {
        return $rows;
    }

    return array_values(array_filter($rows, static function (array $row) use ($q): bool {
        foreach ($row as $value) {
            if (str_contains(strtolower((string) $value), $q)) {
                return true;
            }
        }

        return false;
    }));
}

/**
 * @return list<int|string>
 */
function fc_system_products_admin_pagination_window(int $current, int $total): array
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
 * @param array<string, scalar|null> $overrides
 */
function fc_system_products_admin_url(string $adminBase, array $overrides = []): string
{
    $base = fc_products_admin_admin_url($adminBase, 'products/store-products');
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
function fc_system_products_admin_pagination_links(
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
            'url'   => fc_system_products_admin_url($adminBase, [
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
function fc_system_products_admin_view_data(string $adminBase, array $query = []): array
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

    $tabs = [];
    foreach (['GO', 'JG'] as $tab) {
        $tabMeta = ProductsController::countSystemProducts($tab);
        $tabs[] = [
            'id'          => $tab,
            'label'       => $tab,
            'is_active'   => $tab === $source,
            'count'       => (int) ($tabMeta['total'] ?? 0),
            'count_label' => !empty($tabMeta['ok']) ? (string) (int) ($tabMeta['total'] ?? 0) : '…',
            'href'        => fc_system_products_admin_url($adminBase, [
                'source'   => $tab,
                'q'        => $search,
                'page'     => 1,
                'per_page' => $isAll ? 'all' : $perPage,
            ]),
        ];
    }

    // Counts first (cached), then stream only the visible page from CSV.
    $queryPerPage = $isAll ? PHP_INT_MAX : $perPage;
    $payload = ProductsController::querySystemProducts($source, $search, $page, $queryPerPage, $displayColumns);
    if (
        !$isAll
        && !empty($payload['ok'])
        && $page > 1
        && (int) ($payload['total_pages'] ?? 1) >= 1
        && $page > (int) $payload['total_pages']
    ) {
        $page = max(1, (int) $payload['total_pages']);
        $payload = ProductsController::querySystemProducts($source, $search, $page, $queryPerPage, $displayColumns);
    }

    $error = !empty($payload['ok']) ? '' : (string) ($payload['error'] ?? 'Could not load store products.');
    $columns = fc_system_products_admin_order_columns(
        array_values(array_filter(
            is_array($payload['columns'] ?? null) ? $payload['columns'] : [],
            static fn($col): bool => is_string($col)
        ))
    );
    $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
    $total = (int) ($payload['total'] ?? 0);
    $totalPages = $isAll ? 1 : max(1, (int) ($payload['total_pages'] ?? 1));
    $currentPage = $isAll ? 1 : max(1, (int) ($payload['page'] ?? $page));
    $formAction = fc_products_admin_admin_url($adminBase, 'products/store-products');
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
            $tableHtml = fc_system_products_admin_table_html($columns, $rows, true);
        }
    }

    $paginationPages = fc_system_products_admin_pagination_window($currentPage, $totalPages);

    $pagination = [
        'show'     => !$isAll && $totalPages > 1,
        'prev_url' => (!$isAll && $currentPage > 1)
            ? fc_system_products_admin_url($adminBase, [
                'source'   => $source,
                'q'        => $search,
                'page'     => $currentPage - 1,
                'per_page' => $perPageUrlValue,
            ])
            : '',
        'next_url' => (!$isAll && $currentPage < $totalPages)
            ? fc_system_products_admin_url($adminBase, [
                'source'   => $source,
                'q'        => $search,
                'page'     => $currentPage + 1,
                'per_page' => $perPageUrlValue,
            ])
            : '',
    ];

    $clearUrl = fc_system_products_admin_url($adminBase, [
        'source'   => $source,
        'per_page' => $perPageUrlValue,
    ]);

    $canEdit = !function_exists('fc_auth_user_can') || fc_auth_user_can('products.store_products.download');

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
            : fc_system_products_admin_pagination_links(
                $adminBase,
                $source,
                $search,
                $perPage,
                $currentPage,
                $paginationPages
            ),
        'can_edit'           => $canEdit,
        'bootstrap_json'     => fc_products_admin_bootstrap_json([
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
            'csrf'        => function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '',
            'tabTotals'   => [
                'GO' => (int) ($tabs[0]['count'] ?? 0),
                'JG' => (int) ($tabs[1]['count'] ?? 0),
            ],
            'canEdit'     => $canEdit,
        ]),
    ];
}
