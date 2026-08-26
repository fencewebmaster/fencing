<?php
/**
 * FC Admin — Store products (WC GO/JG) server-rendered table.
 * Used on /products/store-products after the store/system swap.
 *
 * Read-only template: SystemProductPresenter::viewData() guarantees 'pagination'
 * and 'pagination_links' are always arrays ([] when showing all). Escaping via
 * the global e() helper; the layout's is_array() check is the render gate.
 *
 * @var array<string, mixed> $fcSystemProductsPage
 */

$page            = $fcSystemProductsPage;
$pagination      = $page['pagination'];
$paginationLinks = $page['pagination_links'];
?>
<div class="flex min-h-0 flex-1 flex-col overflow-hidden" data-fc-system-products-server="1" data-fc-system-products-php="1">
    <script type="application/json" id="fc-system-products-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <div class="fc-entries-page__notice" data-fc-system-products-notice hidden role="status" aria-live="polite"></div>

    <nav class="fc-gallery-page__tabs fc-system-products-tabs" role="tablist" aria-label="Store product source">
        <?php foreach (($page['tabs'] ?? []) as $tab) : ?>
        <a
            role="tab"
            href="<?php echo e((string) ($tab['href'] ?? '#')); ?>"
            data-fc-sys-tab="<?php echo e((string) ($tab['id'] ?? '')); ?>"
            aria-selected="<?php echo !empty($tab['is_active']) ? 'true' : 'false'; ?>"
            class="fc-gallery-page__tab fc-system-products-tab<?php echo !empty($tab['is_active']) ? ' is-active' : ''; ?>"
        >
            <span><?php echo e((string) ($tab['label'] ?? '')); ?></span>
            <?php if ((int) ($tab['count'] ?? 0) > 0) : ?>
            <span
                class="fc-system-products-tab__count"
                data-fc-sys-tab-count="<?php echo e((string) ($tab['id'] ?? '')); ?>"
            ><?php echo e((string) ($tab['count_label'] ?? '0')); ?></span>
            <?php else : ?>
            <span
                class="fc-system-products-tab__count"
                data-fc-sys-tab-count="<?php echo e((string) ($tab['id'] ?? '')); ?>"
                hidden
            >0</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="fc-entries-page__toolbar fc-sp-toolbar fc-admin-sticky-header sticky top-0 z-20 shrink-0">
        <form class="fc-entries-page__toolbar-form" method="get" action="<?php echo e((string) ($page['form_action'] ?? '')); ?>">
            <div class="fc-entries-page__toolbar-row">
                <label class="fc-entries-page__search-wrap">
                    <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="fc-system-products-search"
                        name="q"
                        class="fc-entries-page__search"
                        placeholder="Search products…"
                        aria-label="Search products"
                        autocomplete="off"
                        value="<?php echo e((string) ($page['search'] ?? '')); ?>"
                    >
                </label>
                <?php if (!empty($page['can_edit'])) : ?>
                <?php
                $fcDownloadCsvName = 'wc-products-' . (string) ($page['source'] ?? 'GO') . '.csv';
                $fcDownloadCsvReady = ($page['error'] ?? '') === '' && is_string($page['file_label'] ?? null) && (string) ($page['file_label'] ?? '') !== '';
                ?>
                <div class="fc-products-download-dropdown" data-fc-products-download-dropdown>
                    <button
                        type="button"
                        class="btn btn-sm btn-orange fw-semibold fc-products-download-trigger"
                        data-fc-products-download-toggle
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="fc-products-download-menu"
                        id="fc-products-download-toggle"
                    >
                        <span>Download</span>
                        <i class="fa-solid fa-chevron-down fc-products-download-dropdown__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-products-download-dropdown__panel"
                        id="fc-products-download-menu"
                        role="menu"
                        aria-labelledby="fc-products-download-toggle"
                        hidden
                    >
                        <button
                            type="button"
                            class="fc-products-download-dropdown__option"
                            role="menuitem"
                            data-fc-products-download-open
                        >
                            <span>Update Products</span>
                        </button>
                        <button
                            type="button"
                            class="fc-products-download-dropdown__option<?php echo $fcDownloadCsvReady ? '' : ' is-disabled'; ?>"
                            role="menuitem"
                            data-fc-products-download-csv
                            data-fc-products-csv-name="<?php echo e($fcDownloadCsvName); ?>"
                            <?php echo $fcDownloadCsvReady ? '' : ' disabled aria-disabled="true"'; ?>
                        >
                            <span>Export CSV</span>
                        </button>
                        <button
                            type="button"
                            class="fc-products-download-dropdown__option"
                            role="menuitem"
                            data-fc-products-import-csv
                        >
                            <span>Import CSV</span>
                        </button>
                    </div>
                    <input
                        type="file"
                        class="sr-only"
                        accept=".csv,text/csv"
                        data-fc-products-import-input
                        tabindex="-1"
                        aria-hidden="true"
                    >
                </div>
                <?php endif; ?>
                <input type="hidden" name="source" value="<?php echo e((string) ($page['source'] ?? 'GO')); ?>">
                <input type="hidden" name="per_page" value="<?php echo !empty($page['is_all']) ? 'all' : (int) ($page['per_page'] ?? 50); ?>">
                <?php if (!empty($page['has_active_search'])) : ?>
                <a
                    href="<?php echo e((string) ($page['clear_url'] ?? '')); ?>"
                    id="fc-system-products-clear-search"
                    class="btn btn-sm btn-dark fw-semibold fc-entries-clear-filters"
                >
                    <span>Clear Search</span>
                </a>
                <?php else : ?>
                <button
                    type="button"
                    id="fc-system-products-clear-search"
                    class="btn btn-sm btn-light fw-semibold fc-entries-clear-filters"
                    disabled
                >
                    <span>Clear Search</span>
                </button>
                <?php endif; ?>
                <button type="submit" class="sr-only">Search</button>
            </div>
        </form>
        <div class="fc-entries-page__count fc-sys-toolbar-meta">
            <span><span id="fc-system-products-count"><?php echo e((string) ($page['count_label'] ?? '0')); ?></span> Items</span>
            <span id="fc-system-products-file" class="fc-sys-toolbar-meta__file"><?php echo e((string) ($page['file_label'] ?? '')); ?></span>
        </div>
    </div>
    <div class="fc-products-download-modal" data-fc-products-download-modal hidden>
        <div class="fc-products-download-modal__backdrop" aria-hidden="true"></div>
        <section
            class="fc-products-download-modal__dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="fc-products-download-title"
            tabindex="-1"
        >
            <button type="button" class="fencing-modal-close" data-fc-products-download-close aria-label="Close download modal"></button>
            <header class="fc-products-download-modal__header">
                <span class="fc-products-download-modal__icon" aria-hidden="true">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </span>
                <div>
                    <h2 id="fc-products-download-title">Download <?php echo e((string) ($page['source'] ?? 'GO')); ?> Products</h2>
                    <p>Refresh the active store products CSV safely.</p>
                </div>
            </header>
            <div class="fc-products-download-modal__body">
                <p class="fc-products-download-modal__intro" data-fc-products-download-intro>
                    Exports all WooCommerce products (published, private, and draft) to
                    <strong><?php echo e('wc-products-' . (string) ($page['source'] ?? 'GO') . '.csv'); ?></strong>.
                    The current file remains available until the new download is complete.
                </p>
                <div class="fc-products-download-progress" data-fc-products-download-progress hidden>
                    <div class="fc-products-download-progress__status">
                        <strong data-fc-products-download-status>Preparing download…</strong>
                        <span data-fc-products-download-percent>0%</span>
                    </div>
                    <div
                        class="fc-products-download-progress__track"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                        data-fc-products-download-track
                    >
                        <span data-fc-products-download-bar></span>
                    </div>
                    <dl class="fc-products-download-progress__details">
                        <div><dt>Source</dt><dd data-fc-products-download-source>—</dd></div>
                        <div><dt>Progress</dt><dd data-fc-products-download-count>0 products</dd></div>
                        <div><dt>Page</dt><dd data-fc-products-download-page>—</dd></div>
                        <div><dt>Working file</dt><dd data-fc-products-download-working>—</dd></div>
                        <div><dt>Final file</dt><dd data-fc-products-download-final>—</dd></div>
                        <div><dt>Elapsed</dt><dd data-fc-products-download-elapsed>0s</dd></div>
                    </dl>
                    <p class="fc-products-download-progress__message" data-fc-products-download-message></p>
                </div>
                <div class="fc-products-download-modal__error" data-fc-products-download-error hidden role="alert"></div>
            </div>
            <footer class="fc-products-download-modal__footer">
                <button type="button" class="btn btn-sm btn-danger fw-semibold" data-fc-products-download-cancel hidden>
                    <i class="fa-solid fa-ban" aria-hidden="true"></i>
                    <span>Cancel download</span>
                </button>
                <button type="button" class="btn btn-sm btn-light fw-semibold" data-fc-products-download-close>Close</button>
                <button type="button" class="btn btn-sm btn-orange fw-semibold" data-fc-products-download-start>
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    <span>Start download</span>
                </button>
            </footer>
        </section>
    </div>
    <?php if (($page['error'] ?? '') === '') : ?>
    <div class="fc-entries-page__content fc-system-products-body min-h-0 flex-1 overflow-auto">
        <div id="fc-system-products-table-wrap" class="flex flex-col">
            <?php echo $page['table_html'] ?? ''; ?>
        </div>
    </div>
    <footer class="fc-entries-page__footer fc-system-products-footer shrink-0">
        <div class="fc-entries-page__footer-row">
            <div class="fc-entries-page__count">
                <?php if (!empty($page['is_all'])) : ?>
                Showing all <?php echo (int) ($page['total'] ?? 0); ?> items
                <?php else : ?>
                Page <?php echo (int) ($page['page'] ?? 1); ?> of <?php echo (int) ($page['total_pages'] ?? 1); ?>
                <?php endif; ?>
            </div>

            <form class="fc-entries-page__per-page" method="get" action="<?php echo e((string) ($page['form_action'] ?? '')); ?>">
                <input type="hidden" name="source" value="<?php echo e((string) ($page['source'] ?? 'GO')); ?>">
                <?php if (($page['search'] ?? '') !== '') : ?>
                <input type="hidden" name="q" value="<?php echo e((string) $page['search']); ?>">
                <?php endif; ?>
                <span class="fc-entries-page__per-page-label">Display per page</span>
                <select class="fc-entries-page__per-page-select" name="per_page" aria-label="Display per page" onchange="this.form.submit()">
                    <?php foreach (($page['per_page_options'] ?? []) as $option) : ?>
                    <option value="<?php echo (int) $option; ?>"<?php echo empty($page['is_all']) && (int) ($page['per_page'] ?? 0) === (int) $option ? ' selected' : ''; ?>><?php echo (int) $option; ?></option>
                    <?php endforeach; ?>
                    <option value="all"<?php echo !empty($page['is_all']) ? ' selected' : ''; ?>>All</option>
                </select>
            </form>

            <?php if (!empty($pagination['show'])) : ?>
            <nav class="fc-entries-page__pagination" aria-label="Store products pagination">
                <?php if (($pagination['prev_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo e((string) $pagination['prev_url']); ?>" aria-label="Previous page">&lsaquo;</a>
                <?php endif; ?>

                <?php foreach ($paginationLinks as $paginationLink) : ?>
                    <?php if (($paginationLink['type'] ?? '') === 'ellipsis') : ?>
                <span class="fc-entries-pagination__ellipsis" aria-hidden="true">…</span>
                    <?php elseif (($paginationLink['type'] ?? '') === 'current') : ?>
                <span class="fc-entries-pagination__btn fc-entries-pagination__btn--active" aria-current="page"><?php echo e((string) ($paginationLink['label'] ?? '')); ?></span>
                    <?php else : ?>
                <a class="fc-entries-pagination__btn" href="<?php echo e((string) ($paginationLink['url'] ?? '#')); ?>"><?php echo e((string) ($paginationLink['label'] ?? '')); ?></a>
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
</div>
