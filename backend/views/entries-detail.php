<?php
/**
 * FC Admin — Single entry detail (server-rendered).
 *
 * @var array<string, mixed> $fcEntriesDetailPage
 */

declare(strict_types=1);

if (!isset($fcEntriesDetailPage) || !is_array($fcEntriesDetailPage)) {
    return;
}

$h = 'fc_entries_admin_h';
$cell = 'fc_entries_admin_cell';
$page = $fcEntriesDetailPage;
$item = $page['item'] ?? null;
?>
<div class="fc-entries-detail-page">
    <header class="fc-entries-detail-page__header">
        <a class="fc-entries-detail-page__back" href="<?php echo $h((string) ($page['list_url'] ?? '')); ?>">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to planner entries
        </a>
        <?php if (is_array($item)) : ?>
        <div class="fc-entries-detail-page__actions">
            <a
                class="btn btn-sm btn-orange fw-semibold"
                href="<?php echo $h((string) ($page['planner_url'] ?? '#')); ?>"
                target="_blank"
                rel="noopener noreferrer"
            >Open planner</a>
        </div>
        <?php endif; ?>
    </header>

    <?php if (($page['error'] ?? '') !== '') : ?>
    <div class="fc-entries-error">
        <p class="fc-entries-error__title">Could not load entry</p>
        <p><?php echo $h((string) $page['error']); ?></p>
    </div>
    <?php elseif (is_array($item)) : ?>
    <div class="fc-entries-detail-page__body">
        <div class="fc-entries-detail-page__grid">
            <section class="fc-entries-detail-panel fc-entries-detail-panel--entry" data-fc-entries-detail-panel="planner">
                <header class="fc-entries-detail-panel__head fc-entries-detail-panel__head--copyable">
                    <div class="fc-entries-detail-panel__head-icon" aria-hidden="true">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="fc-entries-detail-panel__head-text">
                        <h3 class="fc-entries-detail-panel__title">Planner details</h3>
                        <p class="fc-entries-detail-panel__subtitle">Contact, project, and quote information</p>
                    </div>
                    <button
                        type="button"
                        class="fc-entries-detail-copy-btn fc-entries-detail-copy-btn--all"
                        data-fc-copy-text="<?php echo $h((string) ($page['copy_all_text'] ?? '')); ?>"
                        aria-label="Copy all planner details"
                        title="Copy all details"
                    >
                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="fc-entries-detail-panel__body">
                <div class="fc-entries-modal__meta fc-entries-modal__meta--detail">
                    <?php foreach ($page['detail_rows'] as $detailRow) : ?>
                    <div class="fc-entries-modal__row fc-entries-detail-copy-row">
                        <div class="fc-entries-modal__label"><?php echo $h((string) ($detailRow['label'] ?? '')); ?></div>
                        <div class="fc-entries-modal__value-wrap">
                            <div class="fc-entries-modal__value">
                            <?php if (($detailRow['link_type'] ?? '') === 'planner_id' && ($detailRow['display'] ?? '') !== '—') : ?>
                            <a href="<?php echo $h((string) ($detailRow['planner_url'] ?? '')); ?>" target="_blank" rel="noopener noreferrer"><?php echo $cell($detailRow['display'] ?? ''); ?></a>
                            <?php elseif (($detailRow['link_type'] ?? '') === 'site_url' && ($detailRow['display'] ?? '') !== '—') : ?>
                            <a href="<?php echo $h((string) ($detailRow['display'] ?? '')); ?>" target="_blank" rel="noopener noreferrer"><?php echo $cell($detailRow['display'] ?? ''); ?></a>
                            <?php elseif (($detailRow['link_type'] ?? '') === 'email' && ($detailRow['display'] ?? '') !== '—') : ?>
                            <a href="mailto:<?php echo $h((string) ($detailRow['display'] ?? '')); ?>"><?php echo $cell($detailRow['display'] ?? ''); ?></a>
                            <?php elseif (($detailRow['link_type'] ?? '') === 'ip_address' && ($detailRow['ipinfo_url'] ?? '') !== '') : ?>
                            <a href="<?php echo $h((string) ($detailRow['ipinfo_url'] ?? '')); ?>" target="_blank" rel="noopener noreferrer"><?php echo $cell($detailRow['display'] ?? ''); ?></a>
                            <?php elseif (($detailRow['link_type'] ?? '') === 'status' && ($detailRow['display'] ?? '') !== '—') : ?>
                            <span class="<?php echo $h((string) ($detailRow['status_class'] ?? '')); ?>"><?php echo $cell($detailRow['display'] ?? ''); ?></span>
                            <?php elseif (($detailRow['key'] ?? '') === 'device' && ($detailRow['display'] ?? '') !== '—') : ?>
                            <span class="fc-entries-device">
                                <i class="<?php echo $h((string) ($detailRow['device_icon'] ?? 'fa-solid fa-circle-question')); ?>" aria-hidden="true"></i>
                                <span><?php echo $cell($detailRow['display'] ?? ''); ?></span>
                            </span>
                            <?php elseif (($detailRow['key'] ?? '') === 'fence_type' && !empty($detailRow['display_items']) && is_array($detailRow['display_items'])) : ?>
                            <span class="fc-entries-fence-types">
                                <?php foreach ($detailRow['display_items'] as $fenceLine) : ?>
                                <span class="fc-entries-fence-types__item"><?php echo $cell($fenceLine); ?></span>
                                <?php endforeach; ?>
                            </span>
                            <?php elseif (!empty($detailRow['display_items']) && is_array($detailRow['display_items'])) : ?>
                            <ul class="fc-entries-detail-extra-list mb-0">
                                <?php foreach ($detailRow['display_items'] as $extraItem) : ?>
                                <li><?php echo $cell($extraItem); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else : ?>
                            <?php echo $cell($detailRow['display'] ?? ''); ?>
                            <?php endif; ?>
                            </div>
                            <button
                                type="button"
                                class="fc-entries-detail-copy-btn"
                                data-fc-copy-text="<?php echo $h((string) ($detailRow['copy'] ?? '')); ?>"
                                aria-label="<?php echo $h('Copy ' . ($detailRow['label'] ?? '')); ?>"
                                title="Copy"
                            >
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                </div>
            </section>

            <section class="fc-entries-detail-panel fc-entries-detail-panel--cart">
                <header class="fc-entries-detail-panel__head">
                    <div class="fc-entries-detail-panel__head-icon fc-entries-detail-panel__head-icon--cart" aria-hidden="true">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <div class="fc-entries-detail-panel__head-text">
                        <h3 class="fc-entries-detail-panel__title">Cart items</h3>
                        <p class="fc-entries-detail-panel__subtitle"><?php echo $h((string) ($page['cart_subtitle'] ?? '')); ?></p>
                    </div>
                    <?php if ((int) ($page['cart_item_count'] ?? 0) > 0) : ?>
                    <span class="fc-entries-detail-panel__count"><?php echo (int) ($page['cart_item_count'] ?? 0); ?></span>
                    <?php endif; ?>
                </header>

                <div class="fc-entries-detail-panel__body fc-entries-detail-panel__body--flush fc-entries-detail-panel__body--cart">
                <div class="fc-entries-detail-panel__cart-main" data-fc-lazy-root>
                <?php if (empty($page['has_cart_items'])) : ?>
                <div class="fc-entries-detail-page__cart-empty">
                    <i class="fa-solid fa-box-open fc-entries-detail-page__cart-empty-icon" aria-hidden="true"></i>
                    <p class="fc-entries-detail-page__cart-empty-title">No cart items</p>
                    <p class="fc-entries-detail-page__cart-empty-text">This planner does not have any saved cart lines yet.</p>
                </div>
                <?php else : ?>
                <div
                    class="fc-entries-cart-filters"
                    data-fc-entries-cart-filters
                    data-fc-cart-total-lines="<?php echo (int) ($page['cart_item_count'] ?? 0); ?>"
                    data-fc-cart-total-units="<?php echo (int) ($page['cart_total_qty'] ?? 0); ?>"
                >
                    <label class="fc-entries-cart-filters__search-wrap">
                        <i class="fa-solid fa-magnifying-glass fc-entries-cart-filters__search-icon" aria-hidden="true"></i>
                        <input
                            type="search"
                            class="fc-entries-cart-filters__search"
                            data-fc-cart-search
                            placeholder="Search products…"
                            autocomplete="off"
                            spellcheck="false"
                        >
                    </label>
                    <?php if (($page['cart_fence_options'] ?? []) !== []) : ?>
                    <div
                        class="fc-entries-fence-dropdown fc-entries-cart-filters__fence"
                        data-fc-entries-fence-dropdown
                        data-fc-entries-fence-default-label="All fence styles"
                    >
                        <button
                            type="button"
                            class="fc-entries-page__filter fc-entries-fence-dropdown__toggle"
                            id="fc-entries-cart-fence-toggle"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="fc-entries-cart-fence-panel"
                        >
                            <span class="fc-entries-fence-dropdown__label" data-fc-entries-fence-label>All fence styles</span>
                            <i class="fa-solid fa-chevron-down fc-entries-fence-dropdown__caret" aria-hidden="true"></i>
                        </button>
                        <div
                            class="fc-entries-fence-dropdown__panel"
                            id="fc-entries-cart-fence-panel"
                            role="listbox"
                            aria-labelledby="fc-entries-cart-fence-toggle"
                            hidden
                        >
                            <div class="fc-entries-fence-dropdown__options">
                                <?php foreach ($page['cart_fence_options'] as $fenceOption) : ?>
                                <label class="fc-entries-fence-dropdown__option" role="option" aria-selected="false">
                                    <input
                                        type="checkbox"
                                        value="<?php echo $h((string) ($fenceOption['slug'] ?? '')); ?>"
                                        data-fc-entries-fence-checkbox
                                    >
                                    <span><?php echo $h((string) ($fenceOption['name'] ?? '')); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="fc-entries-fence-dropdown__footer">
                                <button type="button" class="fc-entries-fence-dropdown__clear" data-fc-entries-fence-clear>
                                    Clear selection
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-dark fw-semibold fc-entries-cart-filters__clear"
                        data-fc-cart-clear
                        disabled
                    >Clear</button>
                </div>
                <div class="fc-entries-cart-table-wrap">
                    <table class="fc-entries-cart-table">
                        <thead>
                            <tr>
                                <th scope="col" class="fc-entries-cart-table__th-image"><span class="screen-reader-text">Image</span></th>
                                <th scope="col" class="fc-entries-cart-table__th-qty">Qty</th>
                                <th scope="col">Product</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($page['cart_rows'] as $cartRow) : ?>
                            <tr
                                class="fc-entries-cart-table__row<?php echo $h((string) ($cartRow['row_class'] ?? '')); ?>"
                                data-fc-cart-row
                                data-fc-cart-search="<?php echo $h((string) ($cartRow['search_haystack'] ?? '')); ?>"
                                data-fc-cart-fence="<?php echo $h((string) ($cartRow['fence_slug'] ?? '')); ?>"
                                data-fc-cart-qty="<?php echo (int) ($cartRow['row_qty'] ?? 0); ?>"
                            >
                                <td class="fc-entries-cart-table__image">
                                    <div class="fc-entries-cart-table__image-fill">
                                    <?php if (($cartRow['image_url'] ?? '') !== '') : ?>
                                    <button
                                        type="button"
                                        class="fc-entries-cart-table__thumb-btn"
                                        data-fc-cart-gallery-open
                                        data-fc-cart-gallery-url="<?php echo $h((string) ($cartRow['image_url'] ?? '')); ?>"
                                        data-fc-cart-gallery-caption="<?php echo $h((string) ($cartRow['gallery_caption'] ?? '')); ?>"
                                        aria-label="View image: <?php echo $h((string) ($cartRow['gallery_caption'] ?? '')); ?>"
                                    >
                                        <img alt="" class="fc-entries-cart-table__thumb fc-lazy" data-fc-lazy data-fc-lazy-src="<?php echo $h((string) ($cartRow['image_url'] ?? '')); ?>" decoding="async">
                                        <span class="fc-entries-cart-table__thumb-overlay" aria-hidden="true">
                                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                                        </span>
                                    </button>
                                    <?php else : ?>
                                    <span class="fc-entries-cart-table__thumb-placeholder" aria-hidden="true">
                                        <i class="fa-solid fa-image"></i>
                                    </span>
                                    <?php endif; ?>
                                    </div>
                                </td>
                                <td class="fc-entries-cart-table__qty">
                                    <div class="fc-entries-cart-table__qty-cell">
                                        <?php if (!empty($cartRow['is_optional'])) : ?>
                                        <span class="fc-entries-cart-table__qty-value fc-entries-cart-table__qty-value--muted">—</span>
                                        <span class="fc-entries-cart-table__qty-note"><?php echo (int) ($cartRow['optional_qty'] ?? 0); ?> if added</span>
                                        <?php else : ?>
                                        <span class="fc-entries-cart-table__qty-value"><?php echo (int) ($cartRow['row_qty'] ?? 0); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="fc-entries-cart-table__desc">
                                    <div class="fc-entries-cart-table__name-row">
                                        <div class="fc-entries-cart-table__name"><?php echo $cell($cartRow['name'] ?? ''); ?></div>
                                        <?php if (!empty($cartRow['is_optional'])) : ?>
                                        <span class="fc-entries-cart-table__badge">Optional</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (($cartRow['sku'] ?? '') !== '') : ?>
                                    <div class="fc-entries-cart-table__sku"><?php echo $cell($cartRow['sku'] ?? ''); ?></div>
                                    <?php endif; ?>
                                    <?php if (($cartRow['fence_label'] ?? '') !== '') : ?>
                                    <span class="fc-entries-cart-table__fence"><?php echo $h((string) ($cartRow['fence_label'] ?? '')); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="fc-entries-cart-table__no-results" data-fc-cart-no-results hidden>
                    <i class="fa-solid fa-filter fc-entries-cart-table__no-results-icon" aria-hidden="true"></i>
                    <p>No cart items match your search or fence style filters.</p>
                </div>
                <?php endif; ?>
                </div>
                </div>
                <footer class="fc-entries-detail-panel__foot">
                    <span data-fc-cart-footer-lines><?php echo $h((string) ($page['cart_lines_label'] ?? '')); ?></span>
                    <span class="fc-entries-detail-panel__foot-total" data-fc-cart-footer-units><?php echo $h((string) ($page['cart_units_label'] ?? '')); ?></span>
                </footer>
            </section>
        </div>
    </div>
    <?php endif; ?>
</div>
