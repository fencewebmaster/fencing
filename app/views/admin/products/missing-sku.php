<?php
/**
 * FC Admin — Product SKUs (route products/product-skus).
 * Every System Products row with its colour SKU fields editable inline — no modal. The SKU
 * switch narrows the list to the rows still blank or unknown to the store catalogue.
 *
 * Read-only template: StoreProductPresenter::missingSkuViewData() guarantees every shape
 * here. Escaping via the global e() helper; the layout's is_array() check is the render gate.
 *
 * @var array<string, mixed> $fcMissingSkuPage
 */

$page            = $fcMissingSkuPage;
$filters         = $page['filters'];
$rows            = $page['rows'];
$supplierOptions = $page['supplier_options'];
$styleOptions    = $page['style_options'];
$canEdit         = $page['can_edit'];
?>
<div class="flex min-h-0 flex-1 flex-col overflow-hidden" data-fc-missing-sku-server="1">
    <script type="application/json" id="fc-missing-sku-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <div class="fc-entries-page__notice" data-fc-missing-sku-notice hidden role="status" aria-live="polite"></div>

    <div class="fc-entries-page__toolbar fc-sp-toolbar fc-admin-sticky-header sticky top-0 z-20 shrink-0">
        <form class="fc-entries-page__toolbar-form" method="get" action="<?php echo e((string) $page['form_action']); ?>">
            <div class="fc-entries-page__toolbar-row">
                <label class="fc-entries-page__search-wrap">
                    <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="fc-missing-sku-search"
                        name="q"
                        class="fc-entries-page__search"
                        placeholder="Search products…"
                        aria-label="Search products"
                        autocomplete="off"
                        value="<?php echo e((string) ($filters['q'] ?? '')); ?>"
                    >
                </label>
                <select id="fc-missing-sku-filter-supplier" name="supplier" aria-label="Supplier" class="fc-entries-page__filter" onchange="this.form.submit()">
                    <?php foreach ($supplierOptions as $option) : ?>
                    <option value="<?php echo e((string) ($option['value'] ?? '')); ?>"<?php echo !empty($option['is_selected']) ? ' selected' : ''; ?>>
                        <?php echo e((string) ($option['label'] ?? '')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select id="fc-missing-sku-filter-style" name="style" aria-label="Style" class="fc-entries-page__filter" onchange="this.form.submit()">
                    <?php foreach ($styleOptions as $option) : ?>
                    <option value="<?php echo e((string) ($option['value'] ?? '')); ?>"<?php echo !empty($option['is_selected']) ? ' selected' : ''; ?>>
                        <?php echo e((string) ($option['label'] ?? '')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
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
                <span class="fc-ms-count"><?php echo e((string) $page['count_label']); ?></span>
                <?php /* Hidden until you have worked on a row here: the page lists every product,
                     so on a page nobody has touched there is nothing for this to count or filter to.
                     refreshFilledToggle() in missing-sku.js reveals it. */ ?>
                <label class="fc-ms-only-filled" title="Show only the rows you filled or edited here" hidden>
                    <input type="checkbox" data-fc-ms-only-filled>
                    <span class="fc-ms-filled">Filled Rows <strong data-fc-ms-filled-count><?php echo e((string) $page['filled_label']); ?></strong></span>
                </label>
                <?php if ($canEdit) : ?>
                <?php /* Select-all for the toolbar Save. Only rows with unsaved edits are ever
                     selectable, so this is disabled alongside Save until there is something
                     to write. refreshPicks() in missing-sku.js drives all three states. */ ?>
                <label class="fc-ms-pick fc-ms-pick--all" title="Select or clear every row with unsaved edits">
                    <input
                        type="checkbox"
                        class="fc-ms-pick__input"
                        data-fc-ms-pick-all
                        disabled
                        aria-label="Select every row with unsaved edits"
                    >
                    <span class="fc-ms-pick__box" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                </label>
                <button
                    type="button"
                    class="btn btn-sm btn-orange fw-semibold fc-ms-save-all shrink-0"
                    data-fc-ms-save-all
                    disabled
                    aria-disabled="true"
                    title="Save the rows you have ticked"
                >
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    <span data-fc-ms-save-all-label>Save</span>
                </button>
                <?php endif; ?>
                <?php if (!empty($page['menu_available'])) : ?>
                <div class="fc-entries-date-dropdown fc-ms-actions shrink-0" data-fc-ms-actions>
                    <button
                        type="button"
                        class="btn btn-sm btn-orange fw-semibold fc-ms-actions__toggle"
                        id="fc-ms-actions-toggle"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="fc-ms-actions-panel"
                        aria-label="Actions"
                        title="Actions"
                        data-fc-ms-actions-toggle
                    >
                        <span>Actions</span>
                        <i class="fa-solid fa-chevron-down fc-ms-actions__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-entries-date-dropdown__panel fc-ms-actions__panel"
                        id="fc-ms-actions-panel"
                        role="menu"
                        aria-labelledby="fc-ms-actions-toggle"
                        hidden
                    >
                        <div class="fc-ms-actions__head">
                            <span class="fc-ms-actions__head-title">Scan actions</span>
                            <span class="fc-ms-actions__head-hint">Nothing is saved until you press Save on a row</span>
                        </div>
                        <div class="fc-entries-date-dropdown__presets fc-ms-actions__presets">
                            <?php if (!empty($page['scan_available'])) : ?>
                            <button type="button" class="fc-entries-date-dropdown__option fc-ms-actions__option" role="menuitem" data-fc-ms-rescan>
                                <span class="fc-ms-actions__option-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                                <span class="fc-ms-actions__option-text">
                                    <span class="fc-ms-actions__option-label" data-fc-ms-label>Scan</span>
                                    <span class="fc-ms-actions__option-meta"><?php echo e((string) $page['scan_meta']); ?></span>
                                </span>
                            </button>
                            <?php endif; ?>
                            <?php if (!empty($page['deep_scan_available'])) : ?>
                            <button type="button" class="fc-entries-date-dropdown__option fc-ms-actions__option fc-ms-actions__option--deep" role="menuitem" data-fc-ms-deep-scan>
                                <span class="fc-ms-actions__option-icon" aria-hidden="true"><i class="fa-solid fa-brain"></i></span>
                                <span class="fc-ms-actions__option-text">
                                    <span class="fc-ms-actions__option-label" data-fc-ms-label>Deep Scan</span>
                                    <span class="fc-ms-actions__option-meta"><?php echo e((string) $page['deep_scan_meta']); ?></span>
                                </span>
                            </button>
                            <?php endif; ?>
                            <?php if ($canEdit) : ?>
                            <button type="button" class="fc-entries-date-dropdown__option fc-ms-actions__option" role="menuitem" data-fc-ms-scan<?php echo empty($page['fill_available']) ? ' disabled aria-disabled="true"' : ''; ?>>
                                <span class="fc-ms-actions__option-icon" aria-hidden="true"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                                <span class="fc-ms-actions__option-text">
                                    <span class="fc-ms-actions__option-label" data-fc-ms-label><?php echo e((string) $page['fill_label']); ?></span>
                                    <span class="fc-ms-actions__option-meta" data-fc-ms-fill-meta><?php echo e((string) $page['fill_meta']); ?></span>
                                </span>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($page['clear_available'])) : ?>
                        <div class="fc-ms-actions__divider" role="separator"></div>
                        <div class="fc-ms-actions__footer">
                            <button type="button" class="fc-entries-date-dropdown__option fc-ms-actions__option fc-ms-actions__option--clear" role="menuitem" data-fc-ms-clear<?php echo empty($page['clear_enabled']) ? ' disabled aria-disabled="true"' : ''; ?>>
                                <span class="fc-ms-actions__option-icon" aria-hidden="true"><i class="fa-solid fa-trash-can"></i></span>
                                <span class="fc-ms-actions__option-text">
                                    <span class="fc-ms-actions__option-label" data-fc-ms-label>Clear Scan</span>
                                    <span class="fc-ms-actions__option-meta" data-fc-ms-clear-meta><?php echo e((string) $page['clear_meta']); ?></span>
                                </span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="fc-ms-scroll min-h-0 flex-1 overflow-y-auto">
        <?php if ((string) $page['error'] !== '') : ?>
        <p class="fc-ms-empty"><?php echo e((string) $page['error']); ?></p>
        <?php elseif ($rows === []) : ?>
        <p class="fc-ms-empty"><?php echo e((string) $page['empty_message']); ?></p>
        <?php else : ?>
        <ul class="fc-ms-list" data-fc-missing-sku-list>
            <?php foreach ($rows as $row) : ?>
            <li class="fc-ms-row" data-fc-ms-row data-row-index="<?php echo e((string) $row['row_index']); ?>" data-slug="<?php echo e((string) $row['slug']); ?>" data-style="<?php echo e((string) $row['style']); ?>">
                <div class="fc-ms-row__head">
                    <div class="fc-ms-row__ident">
                        <?php if ((string) $row['style_image'] !== '') : ?>
                        <img
                            class="fc-ms-row__style-image"
                            src="<?php echo e((string) $row['style_image']); ?>"
                            alt=""
                            title="<?php echo e((string) $row['style_label']); ?>"
                            loading="lazy"
                            decoding="async"
                        >
                        <?php else : ?>
                        <span class="fc-ms-row__style-image fc-ms-row__style-image--empty" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="fc-ms-row__names">
                            <span class="fc-ms-row__product"><?php echo e((string) $row['product']); ?></span>
                            <span class="fc-ms-row__slug"><?php echo e((string) $row['slug']); ?></span>
                        </span>
                    </div>
                    <div class="fc-ms-row__meta">
                        <span class="fc-ms-chip"><?php echo e((string) $row['style_label']); ?></span>
                        <span class="fc-ms-chip<?php echo e((string) $row['supplier_class']); ?>"><?php echo e((string) $row['supplier']); ?></span>
                        <span class="fc-ms-chip fc-ms-chip--gap" data-fc-ms-missing-label><?php echo e((string) $row['missing_label']); ?></span>
                    </div>
                    <?php if ($canEdit) : ?>
                    <div class="fc-ms-row__actions">
                        <button type="button" class="btn btn-sm btn-orange fw-semibold fc-ms-save" data-fc-ms-save>
                            <i class="fa-solid fa-check" aria-hidden="true"></i><span>Save</span>
                        </button>
                        <span class="fc-ms-row__status" data-fc-ms-status role="status" aria-live="polite"></span>
                    </div>
                    <label class="fc-ms-pick" title="Include this row when you press Save">
                        <input
                            type="checkbox"
                            class="fc-ms-pick__input"
                            data-fc-ms-pick
                            disabled
                            aria-label="Include <?php echo e((string) $row['product']); ?> in the bulk save"
                        >
                        <span class="fc-ms-pick__box" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                    </label>
                    <?php endif; ?>
                </div>

                <div class="fc-sp-field-grid fc-sp-field-grid--sku fc-ms-row__fields">
                    <?php foreach ($row['fields'] as $field) : ?>
                    <div
                        class="fc-sp-field fc-sp-field--sku<?php echo !empty($field['is_gap']) ? ' fc-ms-field--gap' : ''; ?>"
                        data-fc-ms-field
                        data-column="<?php echo e((string) $field['column']); ?>"
                        <?php if ((string) $field['scan_sku'] !== '') : ?>data-fc-ms-scan-sku="<?php echo e((string) $field['scan_sku']); ?>"<?php endif; ?>
                        <?php if ((string) $field['scan_note'] !== '') : ?>data-fc-ms-scan-note="<?php echo e((string) $field['scan_note']); ?>"<?php endif; ?>
                    >
                        <span class="fc-sp-sku-thumb fc-sp-sku-thumb--empty" data-fc-sp-sku-thumb aria-hidden="true"></span>
                        <div class="fc-sp-field__main">
                            <label class="fc-sp-field__label" for="<?php echo e((string) $field['id']); ?>">
                                <span class="fc-ms-swatch" style="background: <?php echo e((string) $field['swatch']); ?>;" aria-hidden="true"></span>
                                <span><?php echo e((string) $field['label']); ?></span>
                                <?php if ((string) $field['initial'] !== '') : ?>
                                <span class="fc-ms-initial" title="SKU code for this colour">(<?php echo e((string) $field['initial']); ?>)</span>
                                <?php endif; ?>
                            </label>
                            <div class="fc-sp-field-input-wrap fc-sp-field-input-wrap--sku">
                                <button
                                    type="button"
                                    class="fc-sp-sku-check <?php echo e((string) $field['check_class']); ?>"
                                    data-fc-sp-sku-check
                                    aria-expanded="false"
                                    aria-haspopup="listbox"
                                    aria-label="<?php echo e((string) $field['check_title']); ?>"
                                    title="<?php echo e((string) $field['check_title']); ?>"
                                >
                                    <i class="<?php echo e((string) $field['check_icon']); ?>" aria-hidden="true"></i>
                                </button>
                                <input
                                    type="text"
                                    id="<?php echo e((string) $field['id']); ?>"
                                    name="<?php echo e((string) $field['column']); ?>"
                                    value="<?php echo e((string) $field['value']); ?>"
                                    class="fc-sp-field-control fc-sp-field-control--sku<?php echo (string) $field['value'] === '' ? ' fc-sp-field-control--empty' : ''; ?>"
                                    autocomplete="off"
                                    placeholder="No SKU"
                                    <?php echo $canEdit ? '' : 'readonly'; ?>
                                >
                                <button
                                    type="button"
                                    class="fc-sp-field-copy"
                                    data-fc-sp-copy-for="<?php echo e((string) $field['id']); ?>"
                                    aria-label="Copy <?php echo e((string) $field['label']); ?>"
                                    title="Copy to clipboard"
                                >
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                </button>
                                <div class="fc-sp-sku-suggest" data-fc-sp-sku-suggest hidden></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
