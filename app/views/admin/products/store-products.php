<?php
/**
 * FC Admin — System products (products.csv) server-rendered table.
 * Used on /products/system-products after the store/system swap.
 *
 * Read-only template: StoreProductPresenter::viewData() guarantees every shape
 * here, including the precomputed 'csv_ready' flag and 'csv_name' download name.
 * Escaping via the global e() helper; the layout's is_array() check is the
 * render gate.
 *
 * @var array<string, mixed> $fcStoreProductsPage
 */

$page            = $fcStoreProductsPage;
$filters         = $page['filters'];
$pagination      = $page['pagination'];
$paginationLinks = $page['pagination_links'];
$supplierOptions = $page['supplier_options'];
$styleOptions    = $page['style_options'];
$colorOptions    = $page['color_options'];
$perPageOptions  = $page['per_page_options'];
$fcStoreCsvReady = $page['csv_ready'];
$fcStoreCsvName  = $page['csv_name'];
?>
<div class="flex min-h-0 flex-1 flex-col overflow-hidden" data-fc-store-products-server="1" data-fc-store-products-php="1">
    <script type="application/json" id="fc-store-products-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <div class="fc-entries-page__notice" data-fc-store-products-notice hidden role="status" aria-live="polite"></div>

    <div class="fc-entries-page__toolbar fc-sp-toolbar fc-admin-sticky-header sticky top-0 z-20 shrink-0">
        <form class="fc-entries-page__toolbar-form" method="get" action="<?php echo e((string) ($page['form_action'] ?? '')); ?>">
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
                        value="<?php echo e((string) ($filters['q'] ?? '')); ?>"
                    >
                </label>
                <select id="fc-store-products-filter-supplier" name="supplier" aria-label="Supplier" class="fc-entries-page__filter" onchange="this.form.submit()">
                    <?php foreach ($supplierOptions as $option) : ?>
                    <option value="<?php echo e((string) ($option['value'] ?? '')); ?>"<?php echo !empty($option['is_selected']) ? ' selected' : ''; ?>>
                        <?php echo e((string) ($option['label'] ?? '')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select id="fc-store-products-filter-style" name="style" aria-label="Style" class="fc-entries-page__filter" onchange="this.form.submit()">
                    <?php foreach ($styleOptions as $option) : ?>
                    <option value="<?php echo e((string) ($option['value'] ?? '')); ?>"<?php echo !empty($option['is_selected']) ? ' selected' : ''; ?>>
                        <?php echo e((string) ($option['label'] ?? '')); ?>
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
                        <span class="fc-entries-fence-dropdown__label" data-fc-store-products-color-label><?php echo e((string) ($page['color_filter_label'] ?? 'All colors')); ?></span>
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
                                    value="<?php echo e((string) ($colorOption['column'] ?? '')); ?>"
                                    data-fc-store-products-color-checkbox
                                    <?php echo !empty($colorOption['is_checked']) ? 'checked' : ''; ?>
                                >
                                <span class="fc-store-products-color-option__label">
                                    <span
                                        class="fc-sys-product-color__swatch"
                                        style="background: <?php echo e((string) ($colorOption['background'] ?? '#cbd5e1')); ?>;"
                                        aria-hidden="true"
                                    ></span>
                                    <span><?php echo e((string) ($colorOption['label'] ?? '')); ?></span>
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
                <label
                    class="fc-entries-page__filter fc-sp-incomplete-toggle<?php echo !empty($page['incomplete_sku']) ? ' is-active' : ''; ?>"
                    title="Show all products — turn off to show only products missing a store SKU"
                >
                    <input type="hidden" name="incomplete" value="1">
                    <input
                        type="checkbox"
                        class="fc-sp-incomplete-toggle__input"
                        name="incomplete"
                        value="0"
                        onchange="this.form.submit()"
                        <?php echo empty($page['incomplete_sku']) ? ' checked' : ''; ?>
                    >
                    <span class="fc-sp-incomplete-toggle__track" aria-hidden="true">
                        <span class="fc-sp-incomplete-toggle__thumb"></span>
                    </span>
                    <span class="fc-sp-incomplete-toggle__label">SKU</span>
                </label>
                <input type="hidden" name="per_page" value="<?php echo !empty($page['is_all']) ? 'all' : (int) ($page['per_page'] ?? 50); ?>">
                <?php if (!empty($page['has_active_filters'])) : ?>
                <a
                    href="<?php echo e((string) ($page['clear_url'] ?? '')); ?>"
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
                        class="btn btn-sm btn-orange fw-semibold fc-products-download-trigger fc-entries-toolbar-menu__toggle"
                        data-fc-store-products-download-toggle
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="fc-store-products-download-menu"
                        aria-label="Actions"
                        title="Actions"
                        id="fc-store-products-download-toggle"
                    >
                        <span>Actions</span>
                        <i class="fa-solid fa-chevron-down fc-products-download-dropdown__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-products-download-dropdown__panel fc-admin-menu__panel"
                        id="fc-store-products-download-menu"
                        role="menu"
                        aria-labelledby="fc-store-products-download-toggle"
                        hidden
                    >
                        <div class="fc-admin-menu__head">
                            <span class="fc-admin-menu__head-title">Product actions</span>
                            <span class="fc-admin-menu__head-hint">Importing replaces <?php echo e((string) $page['file_label']); ?> straight away</span>
                        </div>
                        <div class="fc-admin-menu__group">
                            <button
                                type="button"
                                class="fc-products-download-dropdown__option fc-admin-menu__option<?php echo $fcStoreCsvReady ? '' : ' is-disabled'; ?>"
                                role="menuitem"
                                data-fc-store-products-download-csv
                                data-fc-store-products-csv-name="<?php echo e($fcStoreCsvName); ?>"
                                <?php echo $fcStoreCsvReady ? '' : ' disabled aria-disabled="true"'; ?>
                            >
                                <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-solid fa-download"></i></span>
                                <span class="fc-admin-menu__option-text">
                                    <span class="fc-admin-menu__option-label">Export CSV</span>
                                    <span class="fc-admin-menu__option-meta"><?php echo $fcStoreCsvReady ? 'Download ' . e($fcStoreCsvName) : 'Nothing to export yet'; ?></span>
                                </span>
                            </button>
                            <button
                                type="button"
                                class="fc-products-download-dropdown__option fc-admin-menu__option"
                                role="menuitem"
                                data-fc-store-products-import-csv
                            >
                                <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-solid fa-file-import"></i></span>
                                <span class="fc-admin-menu__option-text">
                                    <span class="fc-admin-menu__option-label">Import CSV</span>
                                    <span class="fc-admin-menu__option-meta">Replace every row from a .csv file</span>
                                </span>
                            </button>
                        </div>
                        <div class="fc-admin-menu__divider" role="separator"></div>
                        <div class="fc-admin-menu__group">
                            <button
                                type="button"
                                class="fc-products-download-dropdown__option fc-admin-menu__option fc-admin-menu__option--accent"
                                role="menuitem"
                                data-fc-desc-update-open
                            >
                                <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></span>
                                <span class="fc-admin-menu__option-text">
                                    <span class="fc-admin-menu__option-label">Update Products</span>
                                    <span class="fc-admin-menu__option-meta">Rebuild names and descriptions</span>
                                </span>
                            </button>
                        </div>
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
            <span><span id="fc-store-products-count"><?php echo e((string) ($page['count_label'] ?? '0')); ?></span> Items</span>
            <span id="fc-store-products-file" class="fc-sys-toolbar-meta__file"><?php echo e((string) ($page['file_label'] ?? 'products.csv')); ?></span>
        </div>
    </div>

    <?php if (($page['error'] ?? '') !== '') : ?>
    <div class="fc-entries-page__content fc-store-products-body min-h-0 flex-1 overflow-auto">
        <div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">Could not load system products</p>
            <p class="mt-1 text-sm"><?php echo e((string) $page['error']); ?></p>
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

            <form class="fc-entries-page__per-page" method="get" action="<?php echo e((string) ($page['form_action'] ?? '')); ?>">
                <?php if (($filters['supplier'] ?? '') !== '') : ?>
                <input type="hidden" name="supplier" value="<?php echo e((string) $filters['supplier']); ?>">
                <?php endif; ?>
                <?php if (($filters['style'] ?? '') !== '') : ?>
                <input type="hidden" name="style" value="<?php echo e((string) $filters['style']); ?>">
                <?php endif; ?>
                <?php foreach (($filters['colors'] ?? []) as $selectedColor) : ?>
                <input type="hidden" name="color[]" value="<?php echo e((string) $selectedColor); ?>">
                <?php endforeach; ?>
                <?php if (($filters['q'] ?? '') !== '') : ?>
                <input type="hidden" name="q" value="<?php echo e((string) $filters['q']); ?>">
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
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo e((string) $pagination['prev_url']); ?>" aria-label="Previous page">&lsaquo;</a>
                <?php endif; ?>

                <?php foreach ($paginationLinks as $paginationLink) : ?>
                    <?php if (($paginationLink['type'] ?? '') === 'ellipsis') : ?>
                <span class="fc-entries-pagination__ellipsis" aria-hidden="true">…</span>
                    <?php elseif (($paginationLink['type'] ?? '') === 'current') : ?>
                <span class="fc-entries-pagination__btn fc-entries-pagination__btn--active" aria-current="page"><?php echo e((string) ($paginationLink['label'] ?? '')); ?></span>
                    <?php else : ?>
                <a class="fc-entries-pagination__btn" href="<?php echo e((string) ($paginationLink['url'] ?? '')); ?>"><?php echo e((string) ($paginationLink['label'] ?? '')); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (($pagination['next_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo e((string) $pagination['next_url']); ?>" aria-label="Next page">&rsaquo;</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </footer>
    <?php endif; ?>

    <?php if (!empty($page['can_edit'])) : ?>
    <div class="fc-desc-update" data-fc-desc-update-modal hidden>
        <div class="fc-desc-update__backdrop" aria-hidden="true"></div>
        <section
            class="fc-desc-update__dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="fc-desc-update-title"
            tabindex="-1"
        >
            <button type="button" class="fencing-modal-close" data-fc-desc-update-close aria-label="Close"></button>
            <header class="fc-desc-update__header">
                <span class="fc-desc-update__icon" aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></span>
                <div>
                    <h2 id="fc-desc-update-title">Update Products</h2>
                    <p>Rebuilds the product name and description from the store catalogue.</p>
                </div>
            </header>
            <div class="fc-desc-update__body">
                <p class="fc-desc-update__intro" data-fc-desc-update-intro>
                    Each product is matched to the store catalogue by its colour SKUs. The
                    <strong>name</strong> is the WooCommerce name with the colour stripped out, the
                    <strong>description</strong> is the one written for those SKUs. Nothing is ever
                    blanked, and nothing is written to <strong>products.csv</strong> until you review
                    the list and press Apply.
                </p>

                <div class="fc-desc-update__progress" data-fc-desc-update-progress hidden>
                    <div class="fc-desc-update__status">
                        <strong data-fc-desc-update-status>Reading products…</strong>
                        <span data-fc-desc-update-percent>0%</span>
                    </div>
                    <div
                        class="fc-desc-update__track"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                        data-fc-desc-update-track
                    >
                        <span data-fc-desc-update-bar></span>
                    </div>
                    <dl class="fc-desc-update__details">
                        <div><dt>Scanned</dt><dd data-fc-desc-update-scanned>0 of 0</dd></div>
                        <div><dt>To update</dt><dd data-fc-desc-update-count>0</dd></div>
                        <div><dt>Product names</dt><dd data-fc-desc-update-names>0</dd></div>
                        <div><dt>Descriptions</dt><dd data-fc-desc-update-descs>0</dd></div>
                        <div><dt>Left alone</dt><dd data-fc-desc-update-skipped>0</dd></div>
                        <div><dt>Elapsed</dt><dd data-fc-desc-update-elapsed>0s</dd></div>
                    </dl>
                </div>

                <div class="fc-desc-update__changes" data-fc-desc-update-changes hidden>
                    <p class="fc-desc-update__changes-head">
                        <span data-fc-desc-update-changes-label>Proposed changes</span>
                    </p>
                    <ul class="fc-desc-update__list" data-fc-desc-update-list></ul>
                </div>

                <div class="fc-desc-update__error" data-fc-desc-update-error hidden role="alert"></div>
            </div>
            <footer class="fc-desc-update__footer">
                <button type="button" class="btn btn-sm btn-light fw-semibold" data-fc-desc-update-close>Close</button>
                <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-desc-update-scan>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Preview changes</span>
                </button>
                <button type="button" class="btn btn-sm btn-orange fw-semibold" data-fc-desc-update-apply hidden>
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    <span>Apply to products.csv</span>
                </button>
            </footer>
        </section>
    </div>
    <?php endif; ?>
</div>
