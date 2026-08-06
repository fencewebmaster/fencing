<?php
/**
 * FC Admin — System products (products.csv) server-rendered table.
 * Used on /products/system-products after the store/system swap.
 *
 * @var array<string, mixed> $fcStoreProductsPage
 */

declare(strict_types=1);

if (!isset($fcStoreProductsPage) || !is_array($fcStoreProductsPage)) {
    return;
}

$h = static fn(string $v): string => \Fc\Admin\Helpers\StringHelper::escapeHtml($v);
$page = $fcStoreProductsPage;
$filters = is_array($page['filters'] ?? null) ? $page['filters'] : [];
$pagination = is_array($page['pagination'] ?? null) ? $page['pagination'] : [];
$paginationLinks = is_array($page['pagination_links'] ?? null) ? $page['pagination_links'] : [];
$supplierOptions = is_array($page['supplier_options'] ?? null) ? $page['supplier_options'] : [];
$styleOptions = is_array($page['style_options'] ?? null) ? $page['style_options'] : [];
$colorOptions = is_array($page['color_options'] ?? null) ? $page['color_options'] : [];
$perPageOptions = is_array($page['per_page_options'] ?? null) ? $page['per_page_options'] : [];
$fcStoreCsvReady = ($page['error'] ?? '') === '';
?>
<div class="flex min-h-0 flex-1 flex-col overflow-hidden" data-fc-store-products-server="1" data-fc-store-products-php="1">
    <script type="application/json" id="fc-store-products-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <div class="fc-entries-page__notice" data-fc-store-products-notice hidden role="status" aria-live="polite"></div>

    <div class="fc-entries-page__toolbar fc-sp-toolbar fc-admin-sticky-header sticky top-0 z-20 shrink-0">
        <form class="fc-entries-page__toolbar-form" method="get" action="<?php echo $h((string) ($page['form_action'] ?? '')); ?>">
            <div class="fc-entries-page__toolbar-row">
                <label class="fc-entries-page__search-wrap">
                    <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="fc-store-products-search"
                        name="q"
                        class="fc-entries-page__search"
                        placeholder="Search products…"
                        aria-label="Search products"
                        autocomplete="off"
                        value="<?php echo $h((string) ($filters['q'] ?? '')); ?>"
                    >
                </label>
                <select id="fc-store-products-filter-supplier" name="supplier" aria-label="Supplier" class="fc-entries-page__filter" onchange="this.form.submit()">
                    <?php foreach ($supplierOptions as $option) : ?>
                    <option value="<?php echo $h((string) ($option['value'] ?? '')); ?>"<?php echo !empty($option['is_selected']) ? ' selected' : ''; ?>>
                        <?php echo $h((string) ($option['label'] ?? '')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select id="fc-store-products-filter-style" name="style" aria-label="Style" class="fc-entries-page__filter" onchange="this.form.submit()">
                    <?php foreach ($styleOptions as $option) : ?>
                    <option value="<?php echo $h((string) ($option['value'] ?? '')); ?>"<?php echo !empty($option['is_selected']) ? ' selected' : ''; ?>>
                        <?php echo $h((string) ($option['label'] ?? '')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div
                    class="fc-entries-fence-dropdown fc-store-products-color-dropdown<?php echo ($page['selected_colors'] ?? []) !== [] ? ' is-active' : ''; ?>"
                    data-fc-store-products-color-dropdown
                    data-fc-store-products-color-default-label="All colors"
                >
                    <button
                        type="button"
                        class="fc-entries-page__filter fc-entries-fence-dropdown__toggle"
                        id="fc-store-products-color-toggle"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="fc-store-products-color-panel"
                    >
                        <span class="fc-entries-fence-dropdown__label" data-fc-store-products-color-label><?php echo $h((string) ($page['color_filter_label'] ?? 'All colors')); ?></span>
                        <i class="fa-solid fa-chevron-down fc-entries-fence-dropdown__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-entries-fence-dropdown__panel"
                        id="fc-store-products-color-panel"
                        role="listbox"
                        aria-labelledby="fc-store-products-color-toggle"
                        aria-multiselectable="true"
                        hidden
                    >
                        <div class="fc-entries-fence-dropdown__options">
                            <?php foreach ($colorOptions as $colorOption) : ?>
                            <label class="fc-entries-fence-dropdown__option fc-store-products-color-option" role="option" aria-selected="<?php echo !empty($colorOption['is_checked']) ? 'true' : 'false'; ?>">
                                <input
                                    type="checkbox"
                                    name="color[]"
                                    value="<?php echo $h((string) ($colorOption['column'] ?? '')); ?>"
                                    data-fc-store-products-color-checkbox
                                    <?php echo !empty($colorOption['is_checked']) ? 'checked' : ''; ?>
                                >
                                <span class="fc-store-products-color-option__label">
                                    <span
                                        class="fc-sys-product-color__swatch"
                                        style="background: <?php echo $h((string) ($colorOption['background'] ?? '#cbd5e1')); ?>;"
                                        aria-hidden="true"
                                    ></span>
                                    <span><?php echo $h((string) ($colorOption['label'] ?? '')); ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="fc-entries-fence-dropdown__footer">
                            <button type="submit" class="fc-entries-fence-dropdown__apply" data-fc-store-products-color-apply>Apply</button>
                            <button type="button" class="fc-entries-fence-dropdown__clear" data-fc-store-products-color-clear>
                                Clear selection
                            </button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="per_page" value="<?php echo !empty($page['is_all']) ? 'all' : (int) ($page['per_page'] ?? 50); ?>">
                <?php if (!empty($page['has_active_filters'])) : ?>
                <a
                    href="<?php echo $h((string) ($page['clear_url'] ?? '')); ?>"
                    id="fc-store-products-clear-filters"
                    class="btn btn-sm btn-dark fw-semibold fc-entries-clear-filters"
                >
                    <span>Clear Filters</span>
                </a>
                <?php else : ?>
                <button
                    type="button"
                    id="fc-store-products-clear-filters"
                    class="btn btn-sm btn-light fw-semibold fc-entries-clear-filters"
                    disabled
                >
                    <span>Clear Filters</span>
                </button>
                <?php endif; ?>
                <?php if (!empty($page['can_edit'])) : ?>
                <div class="fc-products-download-dropdown" data-fc-store-products-download-dropdown>
                    <button
                        type="button"
                        class="btn btn-sm btn-orange fw-semibold fc-products-download-trigger"
                        data-fc-store-products-download-toggle
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="fc-store-products-download-menu"
                        id="fc-store-products-download-toggle"
                    >
                        <span>Download</span>
                        <i class="fa-solid fa-chevron-down fc-products-download-dropdown__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-products-download-dropdown__panel"
                        id="fc-store-products-download-menu"
                        role="menu"
                        aria-labelledby="fc-store-products-download-toggle"
                        hidden
                    >
                        <button
                            type="button"
                            class="fc-products-download-dropdown__option<?php echo $fcStoreCsvReady ? '' : ' is-disabled'; ?>"
                            role="menuitem"
                            data-fc-store-products-download-csv
                            data-fc-store-products-csv-name="products.csv"
                            <?php echo $fcStoreCsvReady ? '' : ' disabled aria-disabled="true"'; ?>
                        >
                            <span>Export CSV</span>
                        </button>
                        <button
                            type="button"
                            class="fc-products-download-dropdown__option"
                            role="menuitem"
                            data-fc-store-products-import-csv
                        >
                            <span>Import CSV</span>
                        </button>
                    </div>
                    <input
                        type="file"
                        class="sr-only"
                        accept=".csv,text/csv"
                        data-fc-store-products-import-input
                        tabindex="-1"
                        aria-hidden="true"
                    >
                </div>
                <?php endif; ?>
                <button type="submit" class="sr-only">Search</button>
            </div>
        </form>
        <div class="fc-entries-page__count fc-sys-toolbar-meta">
            <span><span id="fc-store-products-count"><?php echo $h((string) ($page['count_label'] ?? '0')); ?></span> Items</span>
            <span id="fc-store-products-file" class="fc-sys-toolbar-meta__file"><?php echo $h((string) ($page['file_label'] ?? 'products.csv')); ?></span>
        </div>
    </div>

    <?php if (($page['error'] ?? '') !== '') : ?>
    <div class="fc-entries-page__content fc-store-products-body min-h-0 flex-1 overflow-auto">
        <div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">Could not load system products</p>
            <p class="mt-1 text-sm"><?php echo $h((string) $page['error']); ?></p>
            <?php if (!empty($page['can_edit'])) : ?>
            <p class="mt-2 text-sm">Use <strong>Download → Import CSV</strong> to upload a products.csv file.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php else : ?>
    <div class="fc-entries-page__content fc-store-products-body min-h-0 flex-1 overflow-auto">
        <div id="fc-store-products-table-wrap" class="flex flex-col">
            <?php echo $page['table_html'] ?? ''; ?>
        </div>
    </div>
    <footer class="fc-entries-page__footer fc-store-products-footer shrink-0">
        <div class="fc-entries-page__footer-row">
            <div class="fc-entries-page__count">
                <?php if (!empty($page['is_all'])) : ?>
                Showing all <?php echo (int) ($page['total'] ?? 0); ?> items
                <?php else : ?>
                Page <?php echo (int) ($page['page'] ?? 1); ?> of <?php echo (int) ($page['total_pages'] ?? 1); ?>
                <?php endif; ?>
            </div>

            <form class="fc-entries-page__per-page" method="get" action="<?php echo $h((string) ($page['form_action'] ?? '')); ?>">
                <?php if (($filters['supplier'] ?? '') !== '') : ?>
                <input type="hidden" name="supplier" value="<?php echo $h((string) $filters['supplier']); ?>">
                <?php endif; ?>
                <?php if (($filters['style'] ?? '') !== '') : ?>
                <input type="hidden" name="style" value="<?php echo $h((string) $filters['style']); ?>">
                <?php endif; ?>
                <?php foreach (($filters['colors'] ?? []) as $selectedColor) : ?>
                <input type="hidden" name="color[]" value="<?php echo $h((string) $selectedColor); ?>">
                <?php endforeach; ?>
                <?php if (($filters['q'] ?? '') !== '') : ?>
                <input type="hidden" name="q" value="<?php echo $h((string) $filters['q']); ?>">
                <?php endif; ?>
                <span class="fc-entries-page__per-page-label">Display per page</span>
                <select class="fc-entries-page__per-page-select" name="per_page" aria-label="Display per page" onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $option) : ?>
                    <option value="<?php echo (int) $option; ?>"<?php echo empty($page['is_all']) && (int) ($page['per_page'] ?? 0) === (int) $option ? ' selected' : ''; ?>><?php echo (int) $option; ?></option>
                    <?php endforeach; ?>
                    <option value="all"<?php echo !empty($page['is_all']) ? ' selected' : ''; ?>>All</option>
                </select>
            </form>

            <?php if (!empty($pagination['show'])) : ?>
            <nav class="fc-entries-page__pagination" aria-label="System products pagination">
                <?php if (($pagination['prev_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo $h((string) $pagination['prev_url']); ?>" aria-label="Previous page">&lsaquo;</a>
                <?php endif; ?>

                <?php foreach ($paginationLinks as $paginationLink) : ?>
                    <?php if (($paginationLink['type'] ?? '') === 'ellipsis') : ?>
                <span class="fc-entries-pagination__ellipsis" aria-hidden="true">…</span>
                    <?php elseif (($paginationLink['type'] ?? '') === 'current') : ?>
                <span class="fc-entries-pagination__btn fc-entries-pagination__btn--active" aria-current="page"><?php echo $h((string) ($paginationLink['label'] ?? '')); ?></span>
                    <?php else : ?>
                <a class="fc-entries-pagination__btn" href="<?php echo $h((string) ($paginationLink['url'] ?? '')); ?>"><?php echo $h((string) ($paginationLink['label'] ?? '')); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (($pagination['next_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo $h((string) $pagination['next_url']); ?>" aria-label="Next page">&rsaquo;</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </footer>
    <?php endif; ?>
</div>
