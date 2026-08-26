<?php
/**
 * FC Admin — Entries list (server-rendered).
 *
 * Read-only template: PlannerEntryPresenter::listViewData() guarantees 'request'
 * is always an array (redirect returns exit at the controller). Escaping via the
 * global e()/cell() helpers; the layout's is_array() check is the render gate.
 *
 * @var array<string, mixed> $fcEntriesPage
 */

$page = $fcEntriesPage;
$req  = $page['request'];
?>
<div
    class="fc-entries-page"
    data-fc-entries-list
    data-fc-entries-api="<?php echo e((string) ($page['api_url'] ?? 'api.php?module=entries')); ?>"
    data-fc-entries-view="<?php echo e((string) ($page['view'] ?? 'all')); ?>"
    data-fc-entries-csrf="<?php echo e((string) ($page['csrf'] ?? '')); ?>"
>
    <nav class="fc-entries-page__tabs" aria-label="Planner entries views">
        <?php foreach (($page['tabs'] ?? []) as $tab) : ?>
        <a
            class="fc-entries-page__tab<?php echo !empty($tab['is_active']) ? ' is-active' : ''; ?>"
            href="<?php echo e((string) ($tab['href'] ?? '#')); ?>"
            <?php echo !empty($tab['is_active']) ? 'aria-current="page"' : ''; ?>
        >
            <span><?php echo e((string) ($tab['label'] ?? '')); ?></span>
            <span class="fc-entries-page__tab-count"><?php echo number_format((int) ($tab['count'] ?? 0)); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="fc-entries-page__notice" data-fc-entries-notice hidden role="status" aria-live="polite"></div>

    <div class="fc-entries-page__toolbar">
        <form class="fc-entries-page__toolbar-form" method="get" action="<?php echo e((string) $page['form_action']); ?>">
            <?php if (($page['view'] ?? 'all') === 'trash') : ?>
            <input type="hidden" name="view" value="trash">
            <?php elseif (($page['view'] ?? 'all') === 'duplicates') : ?>
            <input type="hidden" name="view" value="duplicates">
            <?php endif; ?>
            <div class="fc-entries-page__toolbar-row">
                <div class="fc-entries-page__search-group">
                    <label class="fc-entries-page__search-wrap">
                        <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                        <input
                            type="search"
                            name="q"
                            class="fc-entries-page__search"
                            placeholder="Search planner ID, name, email, mobile…"
                            value="<?php echo e((string) ($req['q'] ?? '')); ?>"
                            autocomplete="off"
                        >
                    </label>
                    <?php if (!empty($page['has_active_filters'])) : ?>
                    <a
                        class="btn btn-sm btn-dark fw-semibold fc-entries-clear-filters"
                        href="<?php echo e((string) ($page['clear_filters_url'] ?? '')); ?>"
                        data-fc-entries-clear-filters
                    >
                        <span>Clear Filters</span>
                    </a>
                    <?php else : ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-light fw-semibold fc-entries-clear-filters"
                        data-fc-entries-clear-filters
                        disabled
                    >
                        <span>Clear Filters</span>
                    </button>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-orange fw-semibold fc-entries-advanced-search__trigger<?php echo !empty($page['has_active_filters']) ? ' is-active' : ''; ?>"
                        data-fc-entries-advanced-open
                        aria-haspopup="dialog"
                        aria-controls="fc-entries-advanced-search"
                        aria-expanded="false"
                    >
                        <span>Filters</span>
                    </button>
                    <?php if (!empty($page['can_import']) || !empty($page['can_remove_duplicates'])) : ?>
                    <div class="fc-entries-toolbar-menu" data-fc-entries-toolbar-menu>
                        <button
                            type="button"
                            class="btn btn-sm btn-dark fw-semibold fc-entries-toolbar-menu__toggle"
                            data-fc-entries-toolbar-menu-toggle
                            aria-haspopup="true"
                            aria-expanded="false"
                            title="More actions"
                        >
                            <i class="fa-solid fa-gear" aria-hidden="true"></i>
                        </button>
                        <div class="fc-entries-toolbar-menu__panel" data-fc-entries-toolbar-menu-panel hidden>
                            <?php if (!empty($page['can_import'])) : ?>
                            <button
                                type="button"
                                class="fc-entries-toolbar-menu__item"
                                data-fc-entries-import-open
                            >
                                <i class="fa-solid fa-file-import" aria-hidden="true"></i>
                                <span>Import</span>
                            </button>
                            <input
                                type="file"
                                class="fc-entries-import-file"
                                data-fc-entries-import-file
                                accept="application/json,.json"
                                hidden
                            >
                            <?php endif; ?>
                            <?php if (!empty($page['can_remove_duplicates'])) : ?>
                            <button
                                type="button"
                                class="fc-entries-toolbar-menu__item fc-entries-toolbar-menu__item--danger"
                                data-fc-entries-dedupe-open
                                data-fc-entries-dedupe-candidates="<?php echo (int) ($page['duplicate_candidate_count'] ?? 0); ?>"
                            >
                                <i class="fa-solid fa-clone" aria-hidden="true"></i>
                                <span>Find Duplicates</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="fc-entries-advanced-search" id="fc-entries-advanced-search" data-fc-entries-advanced-modal hidden>
                    <div class="fc-entries-advanced-search__backdrop" aria-hidden="true"></div>
                    <section
                        class="fc-entries-advanced-search__dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="fc-entries-advanced-search-title"
                        tabindex="-1"
                    >
                        <header class="fc-entries-advanced-search__header">
                            <div>
                                <h2 id="fc-entries-advanced-search-title">Advanced Search</h2>
                                <p>Refine entries using one or more filters.</p>
                            </div>
                            <button type="button" class="fencing-modal-close" data-fc-entries-advanced-close aria-label="Close advanced search"></button>
                        </header>
                        <div class="fc-entries-advanced-search__body">
                            <div class="fc-entries-advanced-search__grid">
                                <label class="fc-entries-advanced-search__field">
                                    <span>Status</span>
                                    <select class="fc-entries-page__filter" name="status">
                                        <option value="">All statuses</option>
                                        <?php foreach ($page['statuses'] as $status) : ?>
                                        <option value="<?php echo e((string) $status); ?>"<?php echo ($req['status'] ?? '') === $status ? ' selected' : ''; ?>>
                                            <?php echo e((string) $status); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="fc-entries-advanced-search__field">
                                    <span>Timeframe</span>
                                    <select class="fc-entries-page__filter" name="timeframe">
                                        <option value="">All timeframes</option>
                                        <?php foreach ($page['timeframes'] as $timeframeKey => $timeframeLabel) : ?>
                                        <option value="<?php echo e((string) $timeframeKey); ?>"<?php echo ($req['timeframe'] ?? '') === $timeframeKey ? ' selected' : ''; ?>>
                                            <?php echo e((string) $timeframeLabel); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="fc-entries-advanced-search__field">
                                    <span>State</span>
                                    <select class="fc-entries-page__filter" name="state">
                                        <option value="">All states</option>
                                        <?php foreach ($page['states'] as $stateKey => $stateLabel) : ?>
                                        <option value="<?php echo e((string) $stateKey); ?>"<?php echo ($req['state'] ?? '') === $stateKey ? ' selected' : ''; ?>>
                                            <?php echo e((string) $stateKey); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="fc-entries-advanced-search__field">
                                    <span>Postcode</span>
                                    <input
                                        type="text"
                                        class="fc-entries-advanced-search__input"
                                        name="postcode"
                                        value="<?php echo e((string) ($req['postcode'] ?? '')); ?>"
                                        maxlength="32"
                                        inputmode="numeric"
                                        placeholder="Enter postcode"
                                    >
                                </label>
                                <div class="fc-entries-advanced-search__field">
                                    <span>Device</span>
                                    <div
                                        class="fc-entries-fence-dropdown<?php echo !empty($req['device']) ? ' is-active' : ''; ?>"
                                        data-fc-entries-multi-dropdown
                                        data-fc-entries-multi-default-label="All devices"
                                    >
                                        <button
                                            type="button"
                                            class="fc-entries-page__filter fc-entries-fence-dropdown__toggle"
                                            id="fc-entries-device-toggle"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                            aria-controls="fc-entries-device-panel"
                                        >
                                            <span class="fc-entries-fence-dropdown__label" data-fc-entries-multi-label>All devices</span>
                                            <i class="fa-solid fa-chevron-down fc-entries-fence-dropdown__caret" aria-hidden="true"></i>
                                        </button>
                                        <div
                                            class="fc-entries-fence-dropdown__panel"
                                            id="fc-entries-device-panel"
                                            role="listbox"
                                            aria-labelledby="fc-entries-device-toggle"
                                            aria-multiselectable="true"
                                            hidden
                                        >
                                            <div class="fc-entries-fence-dropdown__options">
                                        <?php foreach (($page['device_options'] ?? []) as $deviceKey => $deviceLabel) : ?>
                                        <?php if ($deviceKey === '') {
                                            continue;
                                        } ?>
                                                <label class="fc-entries-fence-dropdown__option" role="option" aria-selected="<?php echo in_array($deviceKey, (array) ($req['device'] ?? []), true) ? 'true' : 'false'; ?>">
                                                    <input
                                                        type="checkbox"
                                                        name="device[]"
                                                        value="<?php echo e((string) $deviceKey); ?>"
                                                        data-fc-entries-multi-checkbox
                                                        <?php echo in_array($deviceKey, (array) ($req['device'] ?? []), true) ? 'checked' : ''; ?>
                                                    >
                                                    <span><?php echo e((string) $deviceLabel); ?></span>
                                                </label>
                                        <?php endforeach; ?>
                                            </div>
                                            <div class="fc-entries-fence-dropdown__footer">
                                                <button type="button" class="fc-entries-fence-dropdown__clear" data-fc-entries-multi-clear>Clear selection</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="fc-entries-advanced-search__field">
                                    <span>Browser</span>
                                    <div
                                        class="fc-entries-fence-dropdown<?php echo !empty($req['browser']) ? ' is-active' : ''; ?>"
                                        data-fc-entries-multi-dropdown
                                        data-fc-entries-multi-default-label="All browsers"
                                    >
                                        <button
                                            type="button"
                                            class="fc-entries-page__filter fc-entries-fence-dropdown__toggle"
                                            id="fc-entries-browser-toggle"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                            aria-controls="fc-entries-browser-panel"
                                        >
                                            <span class="fc-entries-fence-dropdown__label" data-fc-entries-multi-label>All browsers</span>
                                            <i class="fa-solid fa-chevron-down fc-entries-fence-dropdown__caret" aria-hidden="true"></i>
                                        </button>
                                        <div
                                            class="fc-entries-fence-dropdown__panel"
                                            id="fc-entries-browser-panel"
                                            role="listbox"
                                            aria-labelledby="fc-entries-browser-toggle"
                                            aria-multiselectable="true"
                                            hidden
                                        >
                                            <div class="fc-entries-fence-dropdown__options">
                                        <?php foreach (($page['browser_options'] ?? []) as $browserKey => $browserLabel) : ?>
                                        <?php if ($browserKey === '') {
                                            continue;
                                        } ?>
                                                <label class="fc-entries-fence-dropdown__option" role="option" aria-selected="<?php echo in_array($browserKey, (array) ($req['browser'] ?? []), true) ? 'true' : 'false'; ?>">
                                                    <input
                                                        type="checkbox"
                                                        name="browser[]"
                                                        value="<?php echo e((string) $browserKey); ?>"
                                                        data-fc-entries-multi-checkbox
                                                        <?php echo in_array($browserKey, (array) ($req['browser'] ?? []), true) ? 'checked' : ''; ?>
                                                    >
                                                    <span><?php echo e((string) $browserLabel); ?></span>
                                                </label>
                                        <?php endforeach; ?>
                                            </div>
                                            <div class="fc-entries-fence-dropdown__footer">
                                                <button type="button" class="fc-entries-fence-dropdown__clear" data-fc-entries-multi-clear>Clear selection</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="fc-entries-advanced-search__field fc-entries-advanced-search__field--wide fc-entries-advanced-search__field--date">
                                    <span>Date</span>
                                    <div class="fc-entries-date-filter-group">
                    <select
                        class="fc-entries-page__filter fc-entries-date-filter-group__field"
                        name="date_field"
                        aria-label="Date field"
                        onchange="this.form.submit()"
                    >
                        <?php foreach (($page['date_field_options'] ?? ['created_at' => 'Created At', 'updated_at' => 'Updated At']) as $fieldKey => $fieldLabel) : ?>
                        <option value="<?php echo e((string) $fieldKey); ?>"<?php echo ($page['date_field'] ?? 'updated_at') === $fieldKey ? ' selected' : ''; ?>>
                            <?php echo e((string) $fieldLabel); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <div
                        class="fc-entries-date-dropdown<?php echo ($page['date_period'] ?? '') !== '' ? ' is-active' : ''; ?><?php echo ($page['date_period'] ?? '') === 'custom' ? ' is-custom' : ''; ?>"
                        data-fc-entries-date-dropdown
                    >
                        <input type="hidden" name="date_period" value="<?php echo e((string) ($page['date_period'] ?? '')); ?>" data-fc-entries-date-period>
                        <input type="hidden" name="date_from" value="<?php echo e((string) ($page['date_from'] ?? '')); ?>" data-fc-entries-date-from>
                        <input type="hidden" name="date_to" value="<?php echo e((string) ($page['date_to'] ?? '')); ?>" data-fc-entries-date-to>
                        <button
                            type="button"
                            class="fc-entries-page__filter fc-entries-date-dropdown__toggle"
                            id="fc-entries-date-toggle"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="fc-entries-date-panel"
                        >
                            <i class="fa-regular fa-calendar-days fc-entries-date-dropdown__icon" aria-hidden="true"></i>
                            <span class="fc-entries-date-dropdown__label" data-fc-entries-date-label><?php echo e((string) ($page['date_filter_label'] ?? '')); ?></span>
                            <i class="fa-solid fa-chevron-down fc-entries-date-dropdown__caret" aria-hidden="true"></i>
                        </button>
                        <div
                            class="fc-entries-date-dropdown__panel"
                            id="fc-entries-date-panel"
                            role="listbox"
                            aria-labelledby="fc-entries-date-toggle"
                            hidden
                        >
                            <div class="fc-entries-date-dropdown__presets">
                                <?php foreach ($page['date_period_options'] as $periodKey => $periodLabel) : ?>
                                <?php if ($periodKey === 'custom') {
                                    continue;
                                } ?>
                                <button
                                    type="button"
                                    class="fc-entries-date-dropdown__option<?php echo ($page['date_period'] ?? '') === $periodKey ? ' is-selected' : ''; ?>"
                                    data-fc-entries-date-preset="<?php echo e((string) $periodKey); ?>"
                                    role="option"
                                    aria-selected="<?php echo ($page['date_period'] ?? '') === $periodKey ? 'true' : 'false'; ?>"
                                >
                                    <span><?php echo e((string) $periodLabel); ?></span>
                                    <i class="fa-solid fa-check fc-entries-date-dropdown__check" aria-hidden="true"></i>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="fc-entries-date-dropdown__custom-wrap">
                                <button
                                    type="button"
                                    class="fc-entries-date-dropdown__option fc-entries-date-dropdown__option--custom<?php echo ($page['date_period'] ?? '') === 'custom' ? ' is-selected' : ''; ?>"
                                    data-fc-entries-date-preset="custom"
                                    role="option"
                                    aria-selected="<?php echo ($page['date_period'] ?? '') === 'custom' ? 'true' : 'false'; ?>"
                                >
                                    <span><?php echo e((string) ($page['date_period_options']['custom'] ?? 'Custom')); ?></span>
                                    <i class="fa-solid fa-check fc-entries-date-dropdown__check" aria-hidden="true"></i>
                                </button>
                                <div
                                    class="fc-entries-date-dropdown__custom"
                                    data-fc-entries-date-custom
                                    <?php echo ($page['date_period'] ?? '') === 'custom' ? '' : 'hidden'; ?>
                                >
                                    <div class="fc-entries-date-dropdown__custom-fields">
                                        <label class="fc-entries-date-dropdown__field">
                                            <span class="fc-entries-date-dropdown__field-label">From</span>
                                            <input
                                                type="date"
                                                class="fc-entries-date-dropdown__input"
                                                data-fc-entries-date-custom-from
                                                value="<?php echo e((string) ($page['date_from'] ?? '')); ?>"
                                            >
                                        </label>
                                        <label class="fc-entries-date-dropdown__field">
                                            <span class="fc-entries-date-dropdown__field-label">To</span>
                                            <input
                                                type="date"
                                                class="fc-entries-date-dropdown__input"
                                                data-fc-entries-date-custom-to
                                                value="<?php echo e((string) ($page['date_to'] ?? '')); ?>"
                                            >
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-orange fw-semibold fc-entries-date-dropdown__apply-custom" data-fc-entries-date-apply-custom>
                                        Apply range
                                    </button>
                                </div>
                            </div>
                            <div class="fc-entries-date-dropdown__footer">
                                <button type="button" class="fc-entries-date-dropdown__clear" data-fc-entries-date-clear>
                                    Clear dates
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                                </div>

                                <div class="fc-entries-advanced-search__field fc-entries-advanced-search__field--wide fc-entries-advanced-search__field--fence">
                                    <span>Fence type</span>
                                    <div
                                        class="fc-entries-fence-dropdown<?php echo ($page['selected_fence_types'] ?? []) !== [] ? ' is-active' : ''; ?>"
                                        data-fc-entries-multi-dropdown
                                        data-fc-entries-multi-default-label="All fence types"
                                    >
                                        <button
                                            type="button"
                                            class="fc-entries-page__filter fc-entries-fence-dropdown__toggle"
                                            id="fc-entries-fence-toggle"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                            aria-controls="fc-entries-fence-panel"
                                        >
                                            <span class="fc-entries-fence-dropdown__label" data-fc-entries-multi-label><?php echo e((string) ($page['fence_type_filter_label'] ?? '')); ?></span>
                                            <i class="fa-solid fa-chevron-down fc-entries-fence-dropdown__caret" aria-hidden="true"></i>
                                        </button>
                                        <div
                                            class="fc-entries-fence-dropdown__panel"
                                            id="fc-entries-fence-panel"
                                            role="listbox"
                                            aria-labelledby="fc-entries-fence-toggle"
                                            aria-multiselectable="true"
                                            hidden
                                        >
                                            <div class="fc-entries-fence-dropdown__options">
                                                <?php foreach ($page['fence_options'] as $fenceOption) : ?>
                                                <label class="fc-entries-fence-dropdown__option" role="option" aria-selected="<?php echo !empty($fenceOption['is_checked']) ? 'true' : 'false'; ?>">
                                                    <input
                                                        type="checkbox"
                                                        name="fence_type[]"
                                                        value="<?php echo e((string) ($fenceOption['slug'] ?? '')); ?>"
                                                        data-fc-entries-multi-checkbox
                                                        <?php echo !empty($fenceOption['is_checked']) ? 'checked' : ''; ?>
                                                    >
                                                    <span><?php echo e((string) ($fenceOption['name'] ?? '')); ?></span>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="fc-entries-fence-dropdown__footer">
                                                <button type="button" class="fc-entries-fence-dropdown__clear" data-fc-entries-multi-clear>
                                                    Clear selection
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="fc-entries-advanced-search__field">
                                    <span>Sections</span>
                                    <div class="fc-entries-advanced-search__range">
                                        <label>
                                            <span>Minimum</span>
                                            <input
                                                type="number"
                                                name="sections_min"
                                                min="0"
                                                step="1"
                                                value="<?php echo ($req['sections_min'] ?? null) !== null ? (int) $req['sections_min'] : ''; ?>"
                                                placeholder="Min"
                                            >
                                        </label>
                                        <span class="fc-entries-advanced-search__range-separator" aria-hidden="true">–</span>
                                        <label>
                                            <span>Maximum</span>
                                            <input
                                                type="number"
                                                name="sections_max"
                                                min="0"
                                                step="1"
                                                value="<?php echo ($req['sections_max'] ?? null) !== null ? (int) $req['sections_max'] : ''; ?>"
                                                placeholder="Max"
                                            >
                                        </label>
                                    </div>
                                </div>
                                <div class="fc-entries-advanced-search__field">
                                    <span>Quote loads</span>
                                    <div class="fc-entries-advanced-search__range">
                                        <label>
                                            <span>Minimum</span>
                                            <input
                                                type="number"
                                                name="quote_loads_min"
                                                min="0"
                                                step="1"
                                                value="<?php echo ($req['quote_loads_min'] ?? null) !== null ? (int) $req['quote_loads_min'] : ''; ?>"
                                                placeholder="Min"
                                            >
                                        </label>
                                        <span class="fc-entries-advanced-search__range-separator" aria-hidden="true">–</span>
                                        <label>
                                            <span>Maximum</span>
                                            <input
                                                type="number"
                                                name="quote_loads_max"
                                                min="0"
                                                step="1"
                                                value="<?php echo ($req['quote_loads_max'] ?? null) !== null ? (int) $req['quote_loads_max'] : ''; ?>"
                                                placeholder="Max"
                                            >
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <footer class="fc-entries-advanced-search__footer">
                            <div>
                                <?php if (!empty($page['has_active_filters'])) : ?>
                                <a class="btn btn-sm btn-light fw-semibold" href="<?php echo e((string) ($page['clear_filters_url'] ?? '')); ?>">Clear all filters</a>
                                <?php endif; ?>
                            </div>
                            <div class="fc-entries-advanced-search__footer-actions">
                                <button type="button" class="btn btn-sm btn-light fw-semibold" data-fc-entries-advanced-close>Cancel</button>
                                <button type="submit" class="btn btn-sm btn-orange fw-semibold">
                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    Apply filters
                                </button>
                            </div>
                        </footer>
                    </section>
                </div>

                <?php if (!empty($page['show_per_page_hidden'])) : ?>
                <input type="hidden" name="per_page" value="<?php echo e((string) ($req['per_page'] ?? '')); ?>">
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (($page['error'] ?? '') !== '') : ?>
    <div class="fc-entries-error">
        <p class="fc-entries-error__title">Could not load entries</p>
        <p><?php echo e((string) $page['error']); ?></p>
    </div>
    <?php endif; ?>

    <div class="fc-entries-page__content">
        <div class="fc-entries-table-wrap">
            <table class="fc-entries-table">
                <?php $showBulkChecks = !empty($page['bulk_action_options']); ?>
                <thead>
                    <tr>
                        <?php if ($showBulkChecks) : ?>
                        <th scope="col" class="fc-entries-table__check-col">
                            <label class="fc-entries-check">
                                <input type="checkbox" data-fc-entries-select-all aria-label="Select all entries on this page">
                            </label>
                        </th>
                        <?php endif; ?>
                        <th scope="col">Planner ID</th>
                        <th scope="col">Status</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Mobile</th>
                        <th scope="col">Fence type</th>
                        <th scope="col">Timeframe</th>
                        <th scope="col">Sections</th>
                        <th scope="col">Loads</th>
                        <th scope="col">State</th>
                        <th scope="col">Device</th>
                        <th scope="col"><?php echo e((string) ($page['date_column_label'] ?? 'Created At')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($page['has_table_rows'])) : ?>
                    <tr>
                        <td colspan="<?php echo $showBulkChecks ? '13' : '12'; ?>" class="fc-entries-empty"><?php
                            if (!empty($page['is_trash_view'])) {
                                echo 'Trash is empty.';
                            } elseif (!empty($page['is_duplicates_view'])) {
                                echo 'No duplicate entries found.';
                            } else {
                                echo 'No planner entries found.';
                            }
                        ?></td>
                    </tr>
                    <?php else : ?>
                    <?php foreach ($page['table_rows'] as $row) : ?>
                    <?php
                    $rowCanOpen = !empty($row['can_open']);
                    $rowHref = (string) ($row['row_href'] ?? '');
                    ?>
                    <tr
                        class="fc-entries-table__row<?php echo $rowCanOpen ? '' : ' fc-entries-table__row--no-open'; ?>"
                        data-fc-entries-row
                        data-entry-id="<?php echo (int) ($row['id'] ?? 0); ?>"
                        <?php if ($rowCanOpen && $rowHref !== '') : ?>
                        data-fc-entries-row-href="<?php echo e($rowHref); ?>"
                        <?php endif; ?>
                    >
                        <?php if ($showBulkChecks) : ?>
                        <td class="fc-entries-table__check-col">
                            <label class="fc-entries-check">
                                <input
                                    type="checkbox"
                                    data-fc-entries-row-check
                                    value="<?php echo (int) ($row['id'] ?? 0); ?>"
                                    aria-label="<?php echo e('Select entry ' . ($row['planner_id'] ?? '')); ?>"
                                >
                            </label>
                        </td>
                        <?php endif; ?>
                        <td class="fc-entries-table__mono fc-entries-table__planner-id">
                            <span class="fc-entries-planner-id-wrap">
                                <button
                                    type="button"
                                    class="fc-entries-planner-id"
                                    data-fc-copy-planner-url="<?php echo e((string) ($row['planner_share_url'] ?? '')); ?>"
                                    data-fc-copy-planner-id="<?php echo e((string) ($row['planner_id'] ?? '')); ?>"
                                    title="Click to copy link · Ctrl+click to copy ID"
                                    aria-label="<?php echo e('Copy planner link for ' . ($row['planner_id'] ?? '') . '. Ctrl+click to copy ID.'); ?>"
                                ><?php echo cell($row['planner_id'] ?? ''); ?></button>
                            </span>
                        </td>
                        <td class="fc-entries-table__truncate">
                            <?php if ($rowCanOpen) : ?>
                            <a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>">
                                <span class="<?php echo e((string) ($row['status_class'] ?? '')); ?>">
                                    <?php echo cell($row['status'] ?? ''); ?>
                                </span>
                            </a>
                            <?php else : ?>
                            <span class="fc-entries-row-text">
                                <span class="<?php echo e((string) ($row['status_class'] ?? '')); ?>">
                                    <?php echo cell($row['status'] ?? ''); ?>
                                </span>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="fc-entries-table__truncate"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['name'] ?? ''); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['name'] ?? ''); ?></span><?php endif; ?></td>
                        <td class="fc-entries-table__truncate fc-entries-table__truncate--wide"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['email'] ?? ''); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['email'] ?? ''); ?></span><?php endif; ?></td>
                        <td class="fc-entries-table__truncate"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['mobile'] ?? ''); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['mobile'] ?? ''); ?></span><?php endif; ?></td>
                        <td class="fc-entries-table__fence-types" title="<?php echo e((string) ($row['fence_label_inline'] ?? $row['fence_label'] ?? '')); ?>">
                            <?php
                            $fenceLines = is_array($row['fence_label_lines'] ?? null) ? $row['fence_label_lines'] : [];
                            $fenceInner = '';
                            if ($fenceLines === []) {
                                $fenceInner = cell('');
                            } else {
                                $fenceInner = '<span class="fc-entries-fence-types">';
                                foreach ($fenceLines as $fenceLine) {
                                    $fenceInner .= '<span class="fc-entries-fence-types__item">' . e((string) $fenceLine) . '</span>';
                                }
                                $fenceInner .= '</span>';
                            }
                            ?>
                            <?php if ($rowCanOpen) : ?>
                            <a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo $fenceInner; ?></a>
                            <?php else : ?>
                            <span class="fc-entries-row-text"><?php echo $fenceInner; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="fc-entries-table__truncate"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['timeframe_label'] ?? ''); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['timeframe_label'] ?? ''); ?></span><?php endif; ?></td>
                        <td class="fc-entries-table__truncate"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['section_count'] ?? ''); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['section_count'] ?? ''); ?></span><?php endif; ?></td>
                        <td class="fc-entries-table__truncate"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['quote_load_count'] ?? '0'); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['quote_load_count'] ?? '0'); ?></span><?php endif; ?></td>
                        <td class="fc-entries-table__truncate"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['state'] ?? ''); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['state'] ?? ''); ?></span><?php endif; ?></td>
                        <td class="fc-entries-table__device-col">
                            <?php if ($rowCanOpen) : ?>
                            <a
                                class="fc-entries-row-link fc-entries-table__device-link"
                                href="<?php echo e($rowHref); ?>"
                                aria-label="<?php echo e((string) ($row['device'] ?? 'Unknown') . ', ' . (string) ($row['browser'] ?? 'Unknown')); ?>"
                            >
                            <?php else : ?>
                            <span
                                class="fc-entries-row-text fc-entries-table__device-link"
                                aria-label="<?php echo e((string) ($row['device'] ?? 'Unknown') . ', ' . (string) ($row['browser'] ?? 'Unknown')); ?>"
                            >
                            <?php endif; ?>
                                <i
                                    class="<?php echo e((string) ($row['device_icon'] ?? 'fa-solid fa-circle-question')); ?><?php echo strtolower((string) ($row['device'] ?? 'unknown')) === 'unknown' ? ' is-muted' : ''; ?>"
                                    title="<?php echo e((string) ($row['device'] ?? 'Unknown')); ?>"
                                    aria-hidden="true"
                                ></i>
                                <i
                                    class="<?php echo e((string) ($row['browser_icon'] ?? 'fa-solid fa-globe')); ?><?php echo in_array(strtolower((string) ($row['browser'] ?? 'unknown')), ['unknown', 'other'], true) ? ' is-muted' : ''; ?>"
                                    title="<?php echo e((string) ($row['browser'] ?? 'Unknown')); ?>"
                                    aria-hidden="true"
                                ></i>
                            <?php if ($rowCanOpen) : ?>
                            </a>
                            <?php else : ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="fc-entries-table__truncate"><?php if ($rowCanOpen) : ?><a class="fc-entries-row-link" href="<?php echo e($rowHref); ?>"><?php echo cell($row['date_at'] ?? ''); ?></a><?php else : ?><span class="fc-entries-row-text"><?php echo cell($row['date_at'] ?? ''); ?></span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="fc-entries-page__footer">
        <div class="fc-entries-page__footer-row">
            <div class="fc-entries-page__bulk" data-fc-entries-bulk<?php echo empty($page['bulk_action_options']) ? ' hidden' : ''; ?>>
                <label class="fc-entries-page__bulk-label" for="fc-entries-bulk-action">Bulk actions</label>
                <select id="fc-entries-bulk-action" class="fc-entries-page__bulk-select" data-fc-entries-bulk-action disabled>
                    <option value="">Bulk actions</option>
                    <?php foreach (($page['bulk_action_options'] ?? []) as $bulkOption) : ?>
                    <option value="<?php echo e((string) ($bulkOption['value'] ?? '')); ?>">
                        <?php echo e((string) ($bulkOption['label'] ?? '')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-entries-bulk-apply disabled>Apply</button>
                <span class="fc-entries-page__bulk-count" data-fc-entries-bulk-count hidden>0 selected</span>
            </div>

            <div class="fc-entries-page__count"><?php echo e((string) ($page['count_label'] ?? '')); ?></div>

            <form class="fc-entries-page__per-page" method="get" action="<?php echo e((string) $page['form_action']); ?>">
                <?php echo $page['filter_hidden_html'] ?? ''; ?>
                <span class="fc-entries-page__per-page-label">Display per page</span>
                <select class="fc-entries-page__per-page-select" name="per_page" aria-label="Display per page" onchange="this.form.submit()">
                    <?php foreach ($page['per_page_options'] as $option) : ?>
                    <option value="<?php echo (int) $option; ?>"<?php echo empty($page['is_all']) && (int) ($req['per_page'] ?? 0) === (int) $option ? ' selected' : ''; ?>><?php echo (int) $option; ?></option>
                    <?php endforeach; ?>
                    <option value="all"<?php echo !empty($page['is_all']) ? ' selected' : ''; ?>>All</option>
                </select>
            </form>

            <?php if (!empty($page['pagination']['show'])) : ?>
            <nav class="fc-entries-page__pagination" aria-label="Entries pagination">
                <?php if (($page['pagination']['prev_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo e((string) $page['pagination']['prev_url']); ?>" aria-label="Previous page">&lsaquo;</a>
                <?php endif; ?>

                <?php foreach ($page['pagination_links'] as $paginationLink) : ?>
                    <?php if (($paginationLink['type'] ?? '') === 'ellipsis') : ?>
                <span class="fc-entries-pagination__ellipsis" aria-hidden="true">…</span>
                    <?php elseif (($paginationLink['type'] ?? '') === 'current') : ?>
                <span class="fc-entries-pagination__btn fc-entries-pagination__btn--active" aria-current="page"><?php echo e((string) ($paginationLink['label'] ?? '')); ?></span>
                    <?php else : ?>
                <a class="fc-entries-pagination__btn" href="<?php echo e((string) ($paginationLink['url'] ?? '')); ?>"><?php echo e((string) ($paginationLink['label'] ?? '')); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (($page['pagination']['next_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo e((string) $page['pagination']['next_url']); ?>" aria-label="Next page">&rsaquo;</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </footer>

    <div class="fc-entries-dedupe-modal" data-fc-entries-dedupe-modal hidden>
        <div class="fc-entries-dedupe-modal__backdrop" aria-hidden="true"></div>
        <section
            class="fc-entries-dedupe-modal__dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="fc-entries-dedupe-title"
            tabindex="-1"
        >
            <header class="fc-entries-dedupe-modal__header">
                <span class="fc-entries-dedupe-modal__icon" aria-hidden="true">
                    <i class="fa-solid fa-clone"></i>
                </span>
                <div>
                    <h2 id="fc-entries-dedupe-title">Find Duplicates</h2>
                    <p data-fc-entries-dedupe-subtitle>Scanning planner entries for matching planner IDs…</p>
                </div>
                <button
                    type="button"
                    class="fencing-modal-close"
                    data-fc-entries-dedupe-close
                    aria-label="Close find duplicates"
                ></button>
            </header>
            <div class="fc-entries-dedupe-modal__body">
                <p class="fc-entries-dedupe-modal__intro" data-fc-entries-dedupe-intro>
                    Entries that share the same <strong>planner ID</strong> are scanned. Older copies are marked as
                    <strong>duplicate</strong>; the newest entry for each planner ID stays in All.
                </p>
                <div class="fc-entries-dedupe-progress" data-fc-entries-dedupe-progress>
                    <div class="fc-entries-dedupe-progress__status">
                        <span data-fc-entries-dedupe-status>Preparing…</span>
                        <strong data-fc-entries-dedupe-percent>0%</strong>
                    </div>
                    <div class="fc-entries-dedupe-progress__track" aria-hidden="true">
                        <span data-fc-entries-dedupe-bar style="width: 0%"></span>
                    </div>
                    <dl class="fc-entries-dedupe-progress__details">
                        <div>
                            <dt>Planner ID groups</dt>
                            <dd data-fc-entries-dedupe-groups>—</dd>
                        </div>
                        <div>
                            <dt>Kept (newest)</dt>
                            <dd data-fc-entries-dedupe-kept>—</dd>
                        </div>
                        <div>
                            <dt>Marked duplicate</dt>
                            <dd data-fc-entries-dedupe-marked>—</dd>
                        </div>
                        <div>
                            <dt>Processed</dt>
                            <dd data-fc-entries-dedupe-processed>—</dd>
                        </div>
                    </dl>
                    <p class="fc-entries-dedupe-progress__message" data-fc-entries-dedupe-message></p>
                    <div class="fc-entries-dedupe-modal__error" data-fc-entries-dedupe-error hidden></div>
                </div>
            </div>
            <footer class="fc-entries-dedupe-modal__footer">
                <button type="button" class="btn btn-sm btn-light fw-semibold" data-fc-entries-dedupe-close>
                    Cancel
                </button>
                <button type="button" class="btn btn-sm btn-orange fw-semibold" data-fc-entries-dedupe-start hidden>
                    Start cleanup
                </button>
                <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-entries-dedupe-done hidden>
                    View Duplicates
                </button>
            </footer>
        </section>
    </div>
</div>
