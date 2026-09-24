<?php
/**
 * FC Admin — Settings page (server-rendered).
 *
 * Read-only template: SettingsPresenter::viewData() guarantees every shape here.
 * Escaping via the global e() helper; the layout's is_array() check is the gate.
 *
 * @var array<string, mixed> $fcSettingsPage
 */

$tab = $fcSettingsPage;
?>
<div
    class="fc-settings-page flex h-full min-h-0 flex-col"
    data-fc-settings-initial-tab="<?php echo e((string) $tab['initial_tab']); ?>"
    data-fc-settings-server="1"
>
    <script type="application/json" id="fc-settings-bootstrap"><?php echo $tab['bootstrap_json']; ?></script>

    <div id="fc-settings-root" class="flex h-full min-h-0 flex-col">
        <div class="flex h-full min-h-0 flex-col">
            <div class="fc-admin-sticky-header sticky top-0 z-20 flex shrink-0 flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 py-4 sm:px-6">
                <div class="flex min-w-0 flex-wrap items-center gap-3">
                    <div class="flex flex-wrap rounded-lg bg-slate-200/80 p-1" role="tablist" aria-label="Settings sections">
                        <?php foreach ($tab['tabs'] as $tabId => $tabLabel) : ?>
                            <button
                                type="button"
                                role="tab"
                                data-fc-settings-tab="<?php echo e((string) $tabId); ?>"
                                aria-selected="<?php echo $tab['active_tab'] === $tabId ? 'true' : 'false'; ?>"
                                class="rounded-md px-4 py-2 text-sm font-medium transition <?php echo $tab['active_tab'] === $tabId ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>"
                            ><?php echo e((string) $tabLabel); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <span id="fc-settings-theme-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-branding-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-fence-colors-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-catalog-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-system-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-integration-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-project-plan-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-seo-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                <div id="fc-settings-header-actions-theme" class="<?php echo e((string) $tab['header_actions_class']['theme']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-theme-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-theme-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-branding" class="<?php echo e((string) $tab['header_actions_class']['branding']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-branding-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-branding-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-fence-colors" class="<?php echo e((string) $tab['header_actions_class']['fence_colors']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-fence-colors-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-fence-colors-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-catalog" class="<?php echo e((string) $tab['header_actions_class']['catalog']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-catalog-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-catalog-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-system" class="<?php echo e((string) $tab['header_actions_class']['system']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-system-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-system-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-integration" class="<?php echo e((string) $tab['header_actions_class']['integration']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-integration-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-integration-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-project-plan" class="<?php echo e((string) $tab['header_actions_class']['project_plan']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-project-plan-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-project-plan-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-seo" class="<?php echo e((string) $tab['header_actions_class']['seo']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-seo-reset" class="<?php echo e((string) $tab['btn_secondary']); ?> fc-entries-clear-filters" disabled>Discard Changes</button>
                    <button type="button" id="fc-seo-save" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Save Changes</span></button>
                </div>
                <div id="fc-settings-header-actions-console" class="<?php echo e((string) $tab['header_actions_class']['console']); ?> flex-wrap gap-2"></div>
                <?php if ($tab['site_health_enabled']) : ?>
                <div id="fc-settings-header-actions-site-health" class="<?php echo e((string) $tab['header_actions_class']['site_health']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-health-copy" class="<?php echo e((string) $tab['btn_secondary']); ?>" disabled>Copy Report</button>
                    <button type="button" id="fc-health-run" class="<?php echo e((string) $tab['btn_primary']); ?>"><span>Run Checks Again</span></button>
                </div>
                <?php endif; ?>
                <div class="fc-products-download-dropdown" data-fc-settings-io-dropdown>
                    <button
                        type="button"
                        class="btn btn-sm btn-dark fw-semibold fc-entries-toolbar-menu__toggle"
                        data-fc-settings-io-toggle
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="fc-settings-io-menu"
                        aria-label="Actions"
                        title="Actions"
                        id="fc-settings-io-toggle"
                    >
                        <span>Actions</span>
                        <i class="fa-solid fa-chevron-down fc-products-download-dropdown__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-products-download-dropdown__panel fc-admin-menu__panel"
                        id="fc-settings-io-menu"
                        role="menu"
                        aria-labelledby="fc-settings-io-toggle"
                        hidden
                    >
                        <div class="fc-admin-menu__head">
                            <span class="fc-admin-menu__head-title">Settings actions</span>
                            <span class="fc-admin-menu__head-hint">Importing overwrites every group and reloads the page</span>
                        </div>
                        <div class="fc-admin-menu__group">
                            <button type="button" class="fc-products-download-dropdown__option fc-admin-menu__option" role="menuitem" data-fc-settings-export>
                                <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-solid fa-download"></i></span>
                                <span class="fc-admin-menu__option-text">
                                    <span class="fc-admin-menu__option-label">Export Settings</span>
                                    <span class="fc-admin-menu__option-meta">Save every group as one .json file</span>
                                </span>
                            </button>
                            <button type="button" class="fc-products-download-dropdown__option fc-admin-menu__option" role="menuitem" data-fc-settings-import>
                                <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-solid fa-file-import"></i></span>
                                <span class="fc-admin-menu__option-text">
                                    <span class="fc-admin-menu__option-label">Import Settings</span>
                                    <span class="fc-admin-menu__option-meta">Replace every group from a .json file</span>
                                </span>
                            </button>
                        </div>
                    </div>
                    <input
                        type="file"
                        class="sr-only"
                        accept="application/json,.json"
                        data-fc-settings-import-input
                        tabindex="-1"
                        aria-hidden="true"
                    >
                </div>
                </div>
            </div>
            <div data-fc-settings-notice hidden class="fc-entries-page__notice fc-entries-page__notice--success" aria-hidden="true"></div>

            <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden">
                <div id="fc-settings-layout" class="grid w-full grid-cols-1 gap-6 p-4 sm:p-6 lg:items-start <?php echo e((string) $tab['layout_class']); ?>">
                    <div class="min-w-0 space-y-5">
                        <div id="fc-settings-panel-theme" class="<?php echo e((string) $tab['panel_class']['theme']); ?>space-y-5 mt-[1.25rem]">
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:items-stretch">
                            <?php if (!empty($tab['presets'])) : ?>
                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Presets</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                <div class="grid grid-cols-1 content-start gap-2 sm:grid-cols-2">
                                    <?php foreach ($tab['presets'] as $preset) : ?>
                                    <button
                                        type="button"
                                        data-fc-theme-preset="<?php echo e((string) $preset['id']); ?>"
                                        class="fc-theme-preset group flex items-start gap-2 border-2 p-2.5 text-left transition <?php echo e((string) $preset['card_class']); ?>"
                                        aria-pressed="<?php echo !empty($preset['is_selected']) ? 'true' : 'false'; ?>"
                                    >
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-200 shadow-sm" aria-hidden="true">
                                            <span class="h-full w-1/2" style="background:<?php echo e((string) $preset['accent']); ?>"></span>
                                            <span class="h-full w-1/2" style="background:<?php echo e((string) $preset['brand_primary']); ?>"></span>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-slate-900"><?php echo e((string) $preset['label']); ?></span>
                                            <span class="mt-0.5 block text-xs leading-snug text-slate-500"><?php echo e((string) $preset['description']); ?></span>
                                            <span
                                                data-fc-theme-active-badge
                                                class="<?php echo !empty($preset['is_active']) ? '' : 'hidden '; ?>mt-1.5 inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-semibold shadow-sm"
                                                style="<?php echo e((string) $preset['badge_styles']); ?>"
                                            >
                                                <i class="fa-solid fa-circle-check text-[11px]" aria-hidden="true"></i> Active
                                            </span>
                                        </span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                                </div>
                            </section>
                            <?php endif; ?>

                            <?php foreach ($tab['theme_groups'] as $group) : ?>
                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title"><?php echo e((string) $group['label']); ?></h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <?php foreach ($group['rows'] as $row) : ?>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <?php foreach ($row as $field) : ?>
                                    <div class="block">
                                        <span class="mb-0.5 block text-xs font-medium text-slate-600"><?php echo e((string) $field['label']); ?></span>
                                        <code class="mb-1 block text-[11px] text-slate-400"><?php echo e((string) $field['var']); ?></code>
                                        <div class="flex items-center gap-2">
                                            <input type="color" id="<?php echo e((string) $field['field_id']); ?>_picker" data-fc-theme-var="<?php echo e((string) $field['var']); ?>" value="<?php echo e((string) $field['picker_value']); ?>" class="h-[33px] w-11 shrink-0 cursor-pointer rounded-[3px] border border-[#8c8f94] bg-white p-0.5" aria-label="<?php echo e((string) $field['label']); ?> color picker" />
                                            <div class="fc-settings-field-input-wrap min-w-0 flex-1">
                                                <input type="text" id="<?php echo e((string) $field['field_id']); ?>_hex" data-fc-theme-hex="<?php echo e((string) $field['var']); ?>" value="<?php echo e((string) $field['value']); ?>" maxlength="7" spellcheck="false" class="fc-settings-field font-mono uppercase" aria-label="<?php echo e((string) $field['label']); ?> hex value" />
                                                <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo e((string) $field['field_id']); ?>_hex" aria-label="Copy <?php echo e((string) $field['label']); ?> hex" title="Copy to clipboard">
                                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                            <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="fc-settings-panel-branding" class="<?php echo e((string) $tab['panel_class']['branding']); ?>space-y-5">
                            <section class="fc-settings-card flex flex-col border border-slate-200 bg-white" aria-labelledby="fc-branding-heading">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title" id="fc-branding-heading">Branding</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <?php foreach ($tab['branding_fields'] as $field) : ?>
                                    <?php if (($field['type'] ?? 'text') === 'image') : ?>
                                    <div class="fc-settings-branding-logo">
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                            <span class="w-full shrink-0 text-sm font-medium text-slate-700 sm:w-28 sm:pt-2"><?php echo e((string) $field['label']); ?></span>
                                            <span class="min-w-0 flex-1 space-y-2">
                                                <div class="fc-settings-branding-logo__inputs">
                                                    <input type="text" id="<?php echo e((string) $field['field_id']); ?>" data-fc-branding-field="<?php echo e((string) $field['key']); ?>" value="<?php echo e((string) $field['value']); ?>" placeholder="<?php echo e((string) $field['placeholder']); ?>" title="<?php echo e((string) $field['title']); ?>" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" />
                                                    <button type="button" class="fc-settings-branding-logo__pick" data-fc-branding-pick title="Upload or choose <?php echo e((string) $field['label']); ?>" aria-label="Upload or choose <?php echo e((string) $field['label']); ?>"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                                    <button type="button" class="fc-settings-branding-logo__clear" data-fc-branding-clear title="Remove <?php echo e((string) $field['label']); ?>" aria-label="Remove <?php echo e((string) $field['label']); ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                                </div>
                                                <?php if ($field['help'] !== '') : ?>
                                                <span class="block text-xs text-slate-500"><?php echo e((string) $field['help']); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php else : ?>
                                    <label class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3" for="<?php echo e((string) $field['field_id']); ?>">
                                        <span class="w-full shrink-0 text-sm font-medium text-slate-700 sm:w-28"><?php echo e((string) $field['label']); ?></span>
                                        <span class="min-w-0 flex-1">
                                            <input type="text" id="<?php echo e((string) $field['field_id']); ?>" data-fc-branding-field="<?php echo e((string) $field['key']); ?>" value="<?php echo e((string) $field['value']); ?>" placeholder="<?php echo e((string) $field['placeholder']); ?>" title="<?php echo e((string) $field['title']); ?>" class="fc-settings-field" />
                                            <?php if ($field['help'] !== '') : ?>
                                            <span class="mt-1 block text-xs text-slate-500"><?php echo e((string) $field['help']); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>

                        <div id="fc-settings-panel-fence-colors" class="<?php echo e((string) $tab['panel_class']['fence_colors']); ?>fc-settings-fence-colors">
                            <article class="fc-fs-field-group fc-fs-field-group--outer fc-fs-field-group--full fc-fs-field-group--kv-table">
                                <header class="fc-fs-field-group__head">
                                    <div class="fc-fs-field-group__head-copy">
                                        <p class="fc-fs-field-group__head-sub">Colour swatches shown in the planner. Use a hex colour or CSS gradient, or an image URL. Image takes priority when both are set.</p>
                                    </div>
                                </header>
                                <div class="fc-fs-field-group__body fc-fs-field-group__body--kv-table">
                                    <div class="fc-fs-gui-field fc-fs-gui-field--span fc-fs-kv-block fc-fs-kv-block--table fc-fs-kv-block--fence-colors" data-fc-fence-colors-block>
                                        <div class="fc-fs-kv-table fc-fs-kv-table--compact">
                                            <div class="fc-fs-kv-table__head" data-fc-fence-colors-head>
                                                <span class="fc-fs-kv-table__grip" aria-hidden="true"></span>
                                                <span class="fc-fs-kv-table__col fc-settings-fence-colors__head-preview" aria-hidden="true"></span>
                                                <?php foreach ($tab['fence_sort_columns'] as $col) : ?>
                                                <button type="button" class="fc-fs-kv-table__col fc-settings-fence-colors__sort-col" data-fc-fence-color-sort="<?php echo e((string) $col['id']); ?>" aria-label="Sort by <?php echo e((string) $col['label']); ?>">
                                                    <span><?php echo e((string) $col['label']); ?></span>
                                                    <i class="fa-solid fa-sort fc-settings-fence-colors__sort-icon" aria-hidden="true"></i>
                                                </button>
                                                <?php endforeach; ?>
                                                <span class="fc-fs-kv-table__actions" aria-hidden="true"></span>
                                            </div>
                                            <div class="fc-fs-kv-table__body fc-fs-kv-table__body--compact" id="fc-fence-colors-tbody">
                                                <?php if (empty($tab['has_fence_rows'])) : ?>
                                                <div class="fc-settings-fence-colors__empty">No fence colors yet. Add one below.</div>
                                                <?php else : ?>
                                                    <?php foreach ($tab['fence_rows'] as $row) : ?>
                                                <div class="fc-fs-kv-row fc-fs-kv-row--table<?php echo e((string) $row['row_class']); ?>" data-fc-fence-color-row="<?php echo (int) $row['index']; ?>">
                                                    <span class="fc-fs-kv-row__grip" data-fc-fence-color-grip role="button" tabindex="0" aria-label="Drag to reorder" title="Drag to reorder">
                                                        <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                                                    </span>
                                                    <span class="fc-settings-fence-colors__preview" data-fc-fence-color-preview="<?php echo (int) $row['index']; ?>" style="background:<?php echo e((string) $row['bg']); ?>">
                                                        <?php if ($row['preview_url'] !== '') : ?>
                                                        <img src="<?php echo e((string) $row['preview_url']); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </span>
                                                    <label class="fc-fs-gui-field fc-fs-kv-row__key">
                                                        <span class="fc-fs-gui-field__label">Slug</span>
                                                        <input type="text" class="fc-fs-input fc-fs-input--mono<?php echo !empty($row['is_original']) ? ' fc-fs-input--readonly' : ''; ?>" data-fc-fence-color-field="slug" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo e((string) $row['slug']); ?>" spellcheck="false" placeholder="monument_matt" autocomplete="off"<?php echo !empty($row['is_original']) ? ' readonly aria-readonly="true" title="Original color slugs cannot be changed"' : ''; ?> />
                                                    </label>
                                                    <label class="fc-fs-gui-field fc-settings-fence-colors__initial-cell">
                                                        <span class="fc-fs-gui-field__label">Initial</span>
                                                        <input type="text" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="initial" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo e((string) $row['initial']); ?>" spellcheck="false" placeholder="BS" maxlength="6" autocomplete="off" title="The colour's code inside a product SKU, e.g. XP-6100-S65-BS-CTS" />
                                                    </label>
                                                    <label class="fc-fs-gui-field">
                                                        <span class="fc-fs-gui-field__label">Label</span>
                                                        <input type="text" class="fc-fs-input" data-fc-fence-color-field="label" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo e((string) $row['label']); ?>" placeholder="Black" autocomplete="off" />
                                                    </label>
                                                    <label class="fc-fs-gui-field">
                                                        <span class="fc-fs-gui-field__label">Sub label</span>
                                                        <input type="text" class="fc-fs-input" data-fc-fence-color-field="subLabel" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo e((string) $row['sub_label']); ?>" placeholder="Satin" autocomplete="off" />
                                                    </label>
                                                    <div class="fc-fs-gui-field fc-settings-fence-colors__color-cell">
                                                        <span class="fc-fs-gui-field__label">Color</span>
                                                        <div class="fc-settings-fence-colors__color-inputs">
                                                            <input type="color" class="fc-settings-fence-colors__picker" data-fc-fence-color-picker="<?php echo (int) $row['index']; ?>" value="<?php echo e((string) $row['picker_value']); ?>" aria-label="Color picker" />
                                                            <input type="text" id="fc-fence-color-hex-<?php echo (int) $row['index']; ?>" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="color" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo e((string) $row['color']); ?>" spellcheck="false" placeholder="#6e6e6a" autocomplete="off" />
                                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-fence-color-hex-<?php echo (int) $row['index']; ?>" aria-label="Copy Color" title="Copy to clipboard">
                                                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="fc-fs-gui-field fc-settings-fence-colors__image-cell">
                                                        <span class="fc-fs-gui-field__label">Image</span>
                                                        <div class="fc-settings-fence-colors__image-inputs">
                                                            <input type="text" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="image" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo e((string) $row['image']); ?>" spellcheck="false" placeholder="public/assets/img/… or URL" autocomplete="off" />
                                                            <button type="button" class="fc-settings-fence-colors__pick" data-fc-fence-color-pick="<?php echo (int) $row['index']; ?>" title="Set image" aria-label="Set image">
                                                                <i class="fa-solid fa-image" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($row['is_original'])) : ?>
                                                    <span class="fc-fs-kv-row__remove fc-fs-kv-row__remove--disabled" aria-hidden="true" title="Original colors cannot be removed">
                                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                    </span>
                                                    <?php else : ?>
                                                    <button type="button" class="fc-fs-kv-row__remove" data-fc-fence-color-remove="<?php echo (int) $row['index']; ?>" aria-label="Remove">
                                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button type="button" id="fc-fence-colors-add" class="btn btn-sm btn-dark fw-semibold fc-fs-kv-add">
                                            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Add color
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div id="fc-settings-panel-catalog" class="<?php echo e((string) $tab['panel_class']['catalog']); ?>space-y-5">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title">Sidebar Branding</h3>
                                        </div>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Title</span>
                                            <input type="text" id="fc-catalog-sidebarTitle" data-fc-catalog-field="sidebarTitle" class="fc-settings-field" maxlength="80" autocomplete="off" />
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Subtitle</span>
                                            <input type="text" id="fc-catalog-sidebarSubtitle" data-fc-catalog-field="sidebarSubtitle" class="fc-settings-field" maxlength="160" autocomplete="off" />
                                        </label>
                                    </div>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title">Price Range</h3>
                                        </div>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Minimum price</span>
                                            <input type="number" id="fc-catalog-priceMin" data-fc-catalog-field="priceMin" class="fc-settings-field" min="0" step="1" />
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Maximum price</span>
                                            <input type="number" id="fc-catalog-priceMax" data-fc-catalog-field="priceMax" class="fc-settings-field" min="1" step="1" />
                                        </label>
                                    </div>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title">Default Sorting</h3>
                                        </div>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-start">
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Sort by</span>
                                            <select id="fc-catalog-defaultOrderby" data-fc-catalog-field="defaultOrderby" class="fc-settings-field">
                                                <?php foreach (($tab['catalog_orderby_choices'] ?? []) as $value => $label) : ?>
                                                <option value="<?php echo e((string) $value); ?>"><?php echo e((string) $label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Results per page</span>
                                            <input
                                                type="number"
                                                id="fc-catalog-resultsPerPage"
                                                data-fc-catalog-field="resultsPerPage"
                                                class="fc-settings-field"
                                                min="1"
                                                max="100"
                                                step="1"
                                            />
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Per page list size</span>
                                            <input
                                                type="number"
                                                id="fc-catalog-resultsPerPageListSize"
                                                data-fc-catalog-field="resultsPerPageListSize"
                                                class="fc-settings-field"
                                                min="1"
                                                max="10"
                                                step="1"
                                            />
                                        </label>
                                    </div>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title">Layout</h3>
                                        </div>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div class="grid grid-cols-4 gap-3">
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Desktop</span>
                                            <input type="number" data-fc-catalog-field="columnsDesktop" class="fc-settings-field" min="1" max="6" step="1" />
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Laptop</span>
                                            <input type="number" data-fc-catalog-field="columnsLaptop" class="fc-settings-field" min="1" max="6" step="1" />
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Tablet</span>
                                            <input type="number" data-fc-catalog-field="columnsTablet" class="fc-settings-field" min="1" max="6" step="1" />
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Mobile</span>
                                            <input type="number" data-fc-catalog-field="columnsMobile" class="fc-settings-field" min="1" max="6" step="1" />
                                        </label>
                                    </div>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full min-h-0 flex-col border border-slate-200 bg-white">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title">Product Categories</h3>
                                        </div>
                                        <div class="fc-settings-card__aside flex flex-wrap gap-2 shrink-0">
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-cats-all>Select all</button>
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-cats-none>Clear</button>
                                        </div>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div>
                                    <label class="fc-entries-page__search-wrap fc-catalog-filter-search">
                                        <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                                        <input type="search" id="fc-catalog-categories-search" class="fc-entries-page__search" data-fc-catalog-categories-search placeholder="Search product categories…" autocomplete="off" aria-label="Search product categories" />
                                    </label>
                                    </div>
                                    <p id="fc-catalog-options-error" class="hidden text-sm text-amber-700"></p>
                                    <div>
                                    <div id="fc-catalog-categories" class="fc-catalog-check-tree fc-catalog-options-list overflow-auto border border-slate-200 bg-white p-2.5"></div>
                                    </div>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full min-h-0 flex-col border border-slate-200 bg-white">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title">Attribute Filters</h3>
                                        </div>
                                        <div class="fc-settings-card__aside flex flex-wrap gap-2 shrink-0">
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-attrs-all>Select all</button>
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-attrs-none>Clear</button>
                                        </div>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div>
                                    <label class="fc-entries-page__search-wrap fc-catalog-filter-search">
                                        <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                                        <input type="search" id="fc-catalog-attributes-search" class="fc-entries-page__search" data-fc-catalog-attributes-search placeholder="Search attribute filters…" autocomplete="off" aria-label="Search attribute filters" />
                                    </label>
                                    </div>
                                    <div>
                                    <div id="fc-catalog-attributes" class="fc-catalog-check-list fc-catalog-options-list overflow-auto border border-slate-200 bg-white p-2.5"></div>
                                    </div>
                                    </div>
                                </section>
                            </div>

                            <p class="text-xs text-slate-500">
                                Opens on the public
                                <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../lookup" target="_blank" rel="noopener">Product Lookup</a>
                                page after save (refresh if already open).
                            </p>
                        </div>

                        <div id="fc-settings-panel-system" class="<?php echo e((string) $tab['panel_class']['system']); ?>space-y-5">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Dashboard</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                <label class="flex min-w-0 flex-col gap-1" for="fc-system-dashboardDefaultDatePeriod">
                                    <span class="text-sm font-medium text-slate-700">Default date range</span>
                                    <select id="fc-system-dashboardDefaultDatePeriod" data-fc-system-field="dashboardDefaultDatePeriod" class="fc-settings-field">
                                        <?php foreach (($tab['system_date_period_choices'] ?? []) as $value => $label) : ?>
                                        <option value="<?php echo e((string) $value); ?>"><?php echo e((string) $label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                </div>
                            </section>

                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Planner Entries</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-system-entriesDefaultDatePeriod">
                                        <span class="text-sm font-medium text-slate-700">Default date range</span>
                                        <select id="fc-system-entriesDefaultDatePeriod" data-fc-system-field="entriesDefaultDatePeriod" class="fc-settings-field">
                                            <?php foreach (($tab['system_entries_date_period_choices'] ?? []) as $value => $label) : ?>
                                            <option value="<?php echo e((string) $value); ?>"><?php echo e((string) $label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-system-entriesDefaultDateField">
                                        <span class="text-sm font-medium text-slate-700">Default date field</span>
                                        <select id="fc-system-entriesDefaultDateField" data-fc-system-field="entriesDefaultDateField" class="fc-settings-field">
                                            <?php foreach (($tab['system_date_field_choices'] ?? []) as $value => $label) : ?>
                                            <option value="<?php echo e((string) $value); ?>"><?php echo e((string) $label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                                </div>
                            </section>

                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Date Display</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                <label class="flex min-w-0 flex-col gap-1" for="fc-system-dateFormat">
                                    <span class="text-sm font-medium text-slate-700">Date display format</span>
                                    <select id="fc-system-dateFormat" data-fc-system-field="dateFormat" class="fc-settings-field">
                                        <?php foreach (($tab['system_date_format_choices'] ?? []) as $value => $label) : ?>
                                        <option value="<?php echo e((string) $value); ?>"><?php echo e((string) $label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                </div>
                            </section>

                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Online Presence</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-system-presenceUpdateIntervalSeconds">
                                        <span class="text-sm font-medium text-slate-700">Activity update interval (seconds)</span>
                                        <input
                                            type="number"
                                            id="fc-system-presenceUpdateIntervalSeconds"
                                            data-fc-system-field="presenceUpdateIntervalSeconds"
                                            class="fc-settings-field"
                                            min="5"
                                            max="300"
                                            step="1"
                                        >
                                        <span class="text-xs text-slate-500">How often the system refreshes a user’s online activity while they use the admin. Default: 20.</span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-system-presenceOnlineWindowMinutes">
                                        <span class="text-sm font-medium text-slate-700">Stay online for (minutes)</span>
                                        <input
                                            type="number"
                                            id="fc-system-presenceOnlineWindowMinutes"
                                            data-fc-system-field="presenceOnlineWindowMinutes"
                                            class="fc-settings-field"
                                            min="1"
                                            max="60"
                                            step="1"
                                        >
                                        <span class="text-xs text-slate-500">Mark a user offline after this much inactivity. Default: 3.</span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-system-activityRelativeHours">
                                        <span class="text-sm font-medium text-slate-700">Relative time for (hours)</span>
                                        <input
                                            type="number"
                                            id="fc-system-activityRelativeHours"
                                            data-fc-system-field="activityRelativeHours"
                                            class="fc-settings-field"
                                            min="1"
                                            max="168"
                                            step="1"
                                        >
                                        <span class="text-xs text-slate-500">Show “just now” / “X ago” on Last Activity for this long, then show a timestamp. Default: 24.</span>
                                    </label>
                                </div>
                            </section>
                            </div>
                        </div>

                        <?php $integrations = is_array($tab['integrations'] ?? null) ? $tab['integrations'] : []; ?>
                        <div id="fc-settings-panel-integration" class="<?php echo e((string) $tab['panel_class']['integration']); ?>space-y-5">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">API Keys</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-googleMapsApiKey">
                                        <span class="text-sm font-medium text-slate-700">Google Maps API key</span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="password" id="fc-integration-googleMapsApiKey" data-fc-integration-field="googleMapsApiKey" value="<?php echo e((string) ($integrations['googleMapsApiKey'] ?? '')); ?>" class="fc-settings-field font-mono" autocomplete="off" spellcheck="false" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-reveal="fc-integration-googleMapsApiKey" aria-label="Show Google Maps API key" title="Show or hide"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-googleMapsApiKey" aria-label="Copy Google Maps API key" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-chatraId">
                                        <span class="text-sm font-medium text-slate-700">Chatra ID</span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="password" id="fc-integration-chatraId" data-fc-integration-field="chatraId" value="<?php echo e((string) ($integrations['chatraId'] ?? '')); ?>" class="fc-settings-field font-mono" autocomplete="off" spellcheck="false" placeholder="e.g. zyiAwfgBp6aaDnXK2" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-reveal="fc-integration-chatraId" aria-label="Show Chatra ID" title="Show or hide"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-chatraId" aria-label="Copy Chatra ID" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="text-xs text-slate-500">Public widget ID from Chatra &rarr; Setup. Leave blank to disable the chat widget.</span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-cloudflareApiToken">
                                        <span class="text-sm font-medium text-slate-700">Cloudflare API token</span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="password" id="fc-integration-cloudflareApiToken" data-fc-integration-field="cloudflareApiToken" value="<?php echo e((string) ($integrations['cloudflareApiToken'] ?? '')); ?>" class="fc-settings-field font-mono" autocomplete="off" spellcheck="false" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-reveal="fc-integration-cloudflareApiToken" aria-label="Show Cloudflare API token" title="Show or hide"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-cloudflareApiToken" aria-label="Copy Cloudflare API token" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </label>
                                </div>
                            </section>

                            <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Webhook</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">

                                <div class="fc-webhook-options" role="radiogroup" aria-label="Active webhook">
                                    <?php foreach ($tab['integration_webhook_options'] as $option) : ?>
                                    <div class="fc-webhook-option fc-webhook-option--<?php echo e((string) $option['mode']); ?>">
                                        <label class="fc-webhook-option__head">
                                            <input
                                                type="radio"
                                                name="fc-integration-webhookMode"
                                                data-fc-integration-field="webhookMode"
                                                value="<?php echo e((string) $option['mode']); ?>"
                                                class="fc-webhook-option__radio"
                                                <?php echo !empty($option['checked']) ? 'checked' : ''; ?>
                                            >
                                            <span class="fc-webhook-option__text">
                                                <span class="fc-webhook-option__title"><?php echo e((string) $option['title']); ?></span>
                                                <span class="fc-webhook-option__hint"><?php echo e((string) $option['hint']); ?></span>
                                            </span>
                                        </label>
                                        <span class="fc-settings-field-input-wrap fc-webhook-option__field">
                                            <input
                                                type="text"
                                                id="<?php echo e((string) $option['input_id']); ?>"
                                                data-fc-integration-field="<?php echo e((string) $option['field']); ?>"
                                                value="<?php echo e((string) $option['url']); ?>"
                                                class="fc-settings-field font-mono text-xs"
                                                placeholder="<?php echo e((string) $option['placeholder']); ?>"
                                                aria-label="<?php echo e((string) $option['title']); ?> URL"
                                                autocomplete="off"
                                                spellcheck="false"
                                            />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo e((string) $option['input_id']); ?>" aria-label="<?php echo e((string) $option['copy_label']); ?>" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                    <label class="flex items-center justify-between gap-3" for="fc-integration-webhookPrePlannerEnabled">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700">Enable Pre-Planner</span>
                                            <span class="block text-xs text-slate-500">Fires when the customer submits the planner form.</span>
                                        </span>
                                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                                            <input
                                                type="checkbox"
                                                id="fc-integration-webhookPrePlannerEnabled"
                                                data-fc-integration-field="webhookPrePlannerEnabled"
                                                class="peer sr-only"
                                                <?php echo !empty($integrations['webhookPrePlannerEnabled']) ? 'checked' : ''; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>

                                    <?php $fc_pre_planner_enabled = !empty($integrations['webhookPrePlannerEnabled']); ?>
                                    <label
                                        id="fc-integration-webhookSameDayDedup-row"
                                        class="flex items-center justify-between gap-3<?php echo $fc_pre_planner_enabled ? '' : ' opacity-50 cursor-not-allowed'; ?>"
                                        for="fc-integration-webhookSameDayDedup"
                                    >
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700">Don't re-fire within the same day</span>
                                            <span class="block text-xs text-slate-500">Prevents duplicate webhook fires for the same quote on the same calendar day.</span>
                                        </span>
                                        <span class="relative inline-flex shrink-0 items-center<?php echo $fc_pre_planner_enabled ? ' cursor-pointer' : ' cursor-not-allowed'; ?>">
                                            <input
                                                type="checkbox"
                                                id="fc-integration-webhookSameDayDedup"
                                                data-fc-integration-field="webhookSameDayDedup"
                                                class="peer sr-only"
                                                <?php echo !empty($integrations['webhookSameDayDedup']) ? 'checked' : ''; ?>
                                                <?php echo $fc_pre_planner_enabled ? '' : 'disabled'; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>
                                </div>
                            </section>
                            </div>

                            <section class="fc-settings-card border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Custom Code</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5">
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-headerCode">
                                        <span class="text-sm font-medium text-slate-700">Header code</span>
                                        <span class="block text-xs text-slate-500">Added at the end of &lt;head&gt;, after the Google tag block.</span>
                                        <textarea id="fc-integration-headerCode" data-fc-integration-field="headerCode" rows="8" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" placeholder="&lt;!-- e.g. verification meta tags, pixel scripts --&gt;"><?php echo e((string) ($integrations['headerCode'] ?? '')); ?></textarea>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-footerCode">
                                        <span class="text-sm font-medium text-slate-700">Footer code</span>
                                        <span class="block text-xs text-slate-500">Added at the end of the page, after every script has loaded.</span>
                                        <textarea id="fc-integration-footerCode" data-fc-integration-field="footerCode" rows="8" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" placeholder="&lt;!-- e.g. chat widgets, conversion snippets --&gt;"><?php echo e((string) ($integrations['footerCode'] ?? '')); ?></textarea>
                                    </label>
                                </div>
                                </div>
                            </section>

                            <div class="overflow-x-auto border border-slate-200 bg-white">
                                    <div class="grid min-w-[68rem] grid-cols-[minmax(11rem,1fr)_minmax(12rem,1.1fr)_minmax(6.5rem,0.55fr)_minmax(11rem,1fr)_minmax(11rem,1fr)_minmax(14rem,1.2fr)_minmax(8rem,0.6fr)] border-b border-slate-200 bg-slate-100 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                        <span>Site</span><span>Logo</span><span>Supplier</span><span>Gtag ID</span><span>GTM ID</span><span>Cloudflare Zone ID</span><span>PID Prefix</span>
                                    </div>
                                    <?php foreach (($integrations['sites'] ?? []) as $site) : ?>
                                    <?php
                                    $siteFieldId = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($site['key'] ?? 'site'));
                                    $siteSupplier = strtoupper(trim((string) ($site['supplier'] ?? '')));
                                    if ($siteSupplier !== 'GO' && $siteSupplier !== 'JG') {
                                        $siteSupplier = '';
                                    }
                                    ?>
                                    <div class="grid min-w-[68rem] grid-cols-[minmax(11rem,1fr)_minmax(12rem,1.1fr)_minmax(6.5rem,0.55fr)_minmax(11rem,1fr)_minmax(11rem,1fr)_minmax(14rem,1.2fr)_minmax(8rem,0.6fr)] items-center gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0">
                                        <div class="min-w-0 flex items-center gap-2.5">
                                            <span class="fc-settings-site-logo shrink-0" data-fc-integration-site-logo-preview="<?php echo e((string) ($site['key'] ?? '')); ?>">
                                                <?php if (!empty($site['logoUrl'])) : ?>
                                                <img src="<?php echo e((string) $site['logoUrl']); ?>" alt="" loading="lazy" decoding="async" tabindex="0" role="button" data-fc-settings-image-view data-fc-settings-image-view-label="<?php echo e((string) ($site['label'] ?? $site['key'] ?? '')); ?>" aria-label="View larger image for <?php echo e((string) ($site['label'] ?? $site['key'] ?? '')); ?>">
                                                <?php endif; ?>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-slate-800"><?php echo e((string) ($site['label'] ?? $site['key'] ?? '')); ?></span>
                                                <code class="block truncate text-[11px] text-slate-400"><?php echo e((string) ($site['key'] ?? '')); ?></code>
                                            </span>
                                        </div>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo e((string) $siteFieldId); ?>-logo" data-fc-integration-site="<?php echo e((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="logo" value="<?php echo e((string) ($site['logo'] ?? '')); ?>" class="fc-settings-field font-mono" placeholder="<?php echo e((string) ($site['logoDefault'] ?? 'public/assets/img/… or URL')); ?>" autocomplete="off" spellcheck="false" aria-label="<?php echo e((string) ($site['label'] ?? 'Site')); ?> logo" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-site-logo-pick="<?php echo e((string) ($site['key'] ?? '')); ?>" aria-label="Set <?php echo e((string) ($site['label'] ?? 'Site')); ?> logo" title="Set logo"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                        </span>
                                        <label class="min-w-0">
                                            <span class="sr-only"><?php echo e((string) ($site['label'] ?? 'Site')); ?> supplier</span>
                                            <select
                                                id="fc-integration-<?php echo e((string) $siteFieldId); ?>-supplier"
                                                data-fc-integration-site="<?php echo e((string) ($site['key'] ?? '')); ?>"
                                                data-fc-integration-site-field="supplier"
                                                class="fc-settings-field"
                                                aria-label="<?php echo e((string) ($site['label'] ?? 'Site')); ?> supplier"
                                            >
                                                <option value="JG"<?php echo $siteSupplier === 'JG' ? ' selected' : ''; ?>>JG</option>
                                                <option value="GO"<?php echo $siteSupplier === 'GO' ? ' selected' : ''; ?>>GO</option>
                                            </select>
                                        </label>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo e((string) $siteFieldId); ?>-gtag" data-fc-integration-site="<?php echo e((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="gtagId" value="<?php echo e((string) ($site['gtagId'] ?? '')); ?>" class="fc-settings-field font-mono uppercase" placeholder="AW-123456789" autocomplete="off" spellcheck="false" aria-label="<?php echo e((string) ($site['label'] ?? 'Site')); ?> Gtag ID" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-<?php echo e((string) $siteFieldId); ?>-gtag" aria-label="Copy <?php echo e((string) ($site['label'] ?? 'Site')); ?> Gtag ID" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo e((string) $siteFieldId); ?>-gtm" data-fc-integration-site="<?php echo e((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="gtmId" value="<?php echo e((string) ($site['gtmId'] ?? '')); ?>" class="fc-settings-field font-mono uppercase" placeholder="GTM-XXXXXXX" autocomplete="off" spellcheck="false" aria-label="<?php echo e((string) ($site['label'] ?? 'Site')); ?> GTM ID" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-<?php echo e((string) $siteFieldId); ?>-gtm" aria-label="Copy <?php echo e((string) ($site['label'] ?? 'Site')); ?> GTM ID" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo e((string) $siteFieldId); ?>-cfzone" data-fc-integration-site="<?php echo e((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="cloudflareZoneId" value="<?php echo e((string) ($site['cloudflareZoneId'] ?? '')); ?>" class="fc-settings-field font-mono" placeholder="32-char zone id" autocomplete="off" spellcheck="false" aria-label="<?php echo e((string) ($site['label'] ?? 'Site')); ?> Cloudflare Zone ID" />
                                            <button type="button" class="fc-settings-field-copy fc-settings-field-verify" data-fc-cloudflare-verify data-fc-cloudflare-site="<?php echo e((string) ($site['key'] ?? '')); ?>" data-fc-cloudflare-zone-for="fc-integration-<?php echo e((string) $siteFieldId); ?>-cfzone" aria-label="Verify <?php echo e((string) ($site['label'] ?? 'Site')); ?> Cloudflare Zone ID" title="Verify Cloudflare connection"><i class="fa-solid fa-plug" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-<?php echo e((string) $siteFieldId); ?>-cfzone" aria-label="Copy <?php echo e((string) ($site['label'] ?? 'Site')); ?> Cloudflare Zone ID" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo e((string) $siteFieldId); ?>-pidprefix" data-fc-integration-site="<?php echo e((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="pidPrefix" value="<?php echo e((string) ($site['pidPrefix'] ?? '')); ?>" class="fc-settings-field font-mono uppercase" placeholder="e.g. PER" maxlength="10" autocomplete="off" spellcheck="false" aria-label="<?php echo e((string) ($site['label'] ?? 'Site')); ?> PID Prefix" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-<?php echo e((string) $siteFieldId); ?>-pidprefix" aria-label="Copy <?php echo e((string) ($site['label'] ?? 'Site')); ?> PID Prefix" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                        </div>

                        <div id="fc-settings-panel-project-plan" class="<?php echo e((string) $tab['panel_class']['project_plan']); ?>space-y-5">
                            <div class="overflow-x-auto border border-slate-200 bg-white">
                                <div class="grid min-w-[52rem] grid-cols-[1.5rem_2.5rem_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(12rem,1.4fr)_2.25rem] items-center gap-3 border-b border-slate-200 bg-slate-100 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    <span></span><span></span><span>Slug</span><span>Label</span><span>Image</span><span></span>
                                </div>
                                <div id="fc-project-plan-items">
                                    <?php foreach (($tab['project_plan_items'] ?? []) as $ppItem) : ?>
                                    <?php
                                    $ppIsOriginal = !empty($ppItem['isOriginal']);
                                    $ppKey = (string) ($ppItem['slug'] ?? '');
                                    $ppSlugId = 'fc-project-plan-item-' . e($ppKey) . '-slug';
                                    $ppLabelId = 'fc-project-plan-item-' . e($ppKey) . '-label';
                                    $ppImageId = 'fc-project-plan-item-' . e($ppKey) . '-image';
                                    $ppViewLabel = (string) ($ppItem['label'] ?? '') !== '' ? (string) $ppItem['label'] : $ppKey;
                                    ?>
                                    <div class="grid min-w-[52rem] grid-cols-[1.5rem_2.5rem_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(12rem,1.4fr)_2.25rem] items-center gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0" data-fc-project-plan-row="<?php echo e($ppKey); ?>">
                                        <span class="fc-project-plan-grip" data-fc-project-plan-grip role="button" tabindex="0" aria-label="Drag to reorder" title="Drag to reorder">
                                            <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                                        </span>
                                        <span class="fc-settings-site-logo shrink-0" data-fc-project-plan-item-preview="<?php echo e($ppKey); ?>">
                                            <?php if (!empty($ppItem['imageUrl'])) : ?>
                                            <img src="<?php echo e((string) $ppItem['imageUrl']); ?>" alt="" loading="lazy" decoding="async" tabindex="0" role="button" data-fc-settings-image-view data-fc-settings-image-view-label="<?php echo e($ppViewLabel); ?>" aria-label="View larger image for <?php echo e($ppViewLabel); ?>">
                                            <?php else : ?>
                                            <i class="fa-solid fa-image" aria-hidden="true"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <?php if ($ppIsOriginal) : ?>
                                            <input type="text" id="<?php echo $ppSlugId; ?>" value="<?php echo e($ppKey); ?>" class="fc-settings-field font-mono" readonly aria-readonly="true" title="Original item slugs cannot be changed" aria-label="Slug" />
                                            <?php else : ?>
                                            <input type="text" id="<?php echo $ppSlugId; ?>" data-fc-project-plan-item="<?php echo e($ppKey); ?>" data-fc-project-plan-item-field="slug" value="<?php echo e($ppKey); ?>" class="fc-settings-field font-mono" spellcheck="false" autocomplete="off" placeholder="e.g. gate-opener" aria-label="Slug" />
                                            <?php endif; ?>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo $ppSlugId; ?>" aria-label="Copy Slug" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="<?php echo $ppLabelId; ?>" data-fc-project-plan-item="<?php echo e($ppKey); ?>" data-fc-project-plan-item-field="label" value="<?php echo e((string) ($ppItem['label'] ?? '')); ?>" class="fc-settings-field" aria-label="Label" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo $ppLabelId; ?>" aria-label="Copy Label" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="<?php echo $ppImageId; ?>" data-fc-project-plan-item="<?php echo e($ppKey); ?>" data-fc-project-plan-item-field="image" value="<?php echo e((string) ($ppItem['image'] ?? '')); ?>" class="fc-settings-field font-mono" placeholder="<?php echo e((string) ($ppItem['imageDefault'] ?? '')); ?>" autocomplete="off" spellcheck="false" aria-label="Image" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-project-plan-item-pick="<?php echo e($ppKey); ?>" title="Set image" aria-label="Set image"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo $ppImageId; ?>" aria-label="Copy Image" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <?php if ($ppIsOriginal) : ?>
                                        <span class="fc-project-plan-remove fc-project-plan-remove--disabled" aria-hidden="true" title="Original items cannot be removed">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </span>
                                        <?php else : ?>
                                        <button type="button" class="fc-project-plan-remove" data-fc-project-plan-item-remove="<?php echo e($ppKey); ?>" title="Remove item" aria-label="Remove item"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="button" id="fc-project-plan-add" class="btn btn-sm btn-dark fw-semibold">
                                <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Add item
                            </button>

                            <?php $ppStock = is_array($tab['project_plan_stock'] ?? null) ? $tab['project_plan_stock'] : []; ?>
                            <section class="fc-settings-card border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Stock &amp; Delivery</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">

                                    <label class="flex items-center justify-between gap-3" for="fc-project-plan-stock-lowStockEnabled">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700">Show Low Stock Warning</span>
                                            <span class="block text-xs text-slate-500">The red panel under the delivery facts.</span>
                                        </span>
                                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                                            <input
                                                type="checkbox"
                                                id="fc-project-plan-stock-lowStockEnabled"
                                                data-fc-project-plan-stock-field="lowStockEnabled"
                                                class="peer sr-only"
                                                <?php echo !empty($ppStock['lowStockEnabled']) ? 'checked' : ''; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>

                                    <label class="flex items-center justify-between gap-3" for="fc-project-plan-stock-orderWithinEnabled">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700">Show Order Within countdown</span>
                                            <span class="block text-xs text-slate-500">The ORDER WITHIN timer below the stock panel.</span>
                                        </span>
                                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                                            <input
                                                type="checkbox"
                                                id="fc-project-plan-stock-orderWithinEnabled"
                                                data-fc-project-plan-stock-field="orderWithinEnabled"
                                                class="peer sr-only"
                                                <?php echo !empty($ppStock['orderWithinEnabled']) ? 'checked' : ''; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>

                                    <label class="flex items-center justify-between gap-3" for="fc-project-plan-stock-phone">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700">Call button phone number</span>
                                            <span class="block text-xs text-slate-500">Printed on the Call button exactly as typed. Leave blank to hide the button.</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-2">
                                            <input
                                                type="tel"
                                                id="fc-project-plan-stock-phone"
                                                data-fc-project-plan-stock-field="phone"
                                                class="fc-settings-field w-48 text-right"
                                                autocomplete="off"
                                                spellcheck="false"
                                                placeholder="<?php echo e((string) ($tab['project_plan_stock_defaults']['phone'] ?? '')); ?>"
                                                value="<?php echo e((string) ($ppStock['phone'] ?? '')); ?>"
                                            >
                                        </span>
                                    </label>

                                    <label class="flex items-center justify-between gap-3" for="fc-project-plan-stock-orderWithinHours">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700">Countdown length</span>
                                            <span class="block text-xs text-slate-500">Hours the cart is held for. A countdown already running keeps its deadline &mdash; the new length applies when it next resets.</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-2">
                                            <input
                                                type="number"
                                                id="fc-project-plan-stock-orderWithinHours"
                                                data-fc-project-plan-stock-field="orderWithinHours"
                                                class="fc-settings-field w-24 text-right"
                                                min="<?php echo e((string) ($tab['project_plan_stock_hours_min'] ?? 1)); ?>"
                                                max="<?php echo e((string) ($tab['project_plan_stock_hours_max'] ?? 168)); ?>"
                                                step="1"
                                                inputmode="numeric"
                                                value="<?php echo e((string) ($ppStock['orderWithinHours'] ?? 3)); ?>"
                                            >
                                            <span class="text-xs text-slate-500">hours</span>
                                        </span>
                                    </label>

                                <?php /* Same field shape the fence-style editor uses for its HTML fields
                                         (fence-styles/gui.js, type 'html'): a div rather than a label,
                                         because a <label> wrapper hands every click in the editor back to
                                         the hidden textarea. .fc-fs-* styling lives in layouts/main.php. */ ?>
                                <div>
                                    <div class="fc-fs-gui-field fc-fs-gui-field--wysiwyg">
                                        <span class="fc-fs-gui-field__label">Low Stock Warning description</span>
                                        <textarea
                                            id="fc-project-plan-stock-lowStockHtml"
                                            data-fc-project-plan-stock-field="lowStockHtml"
                                            data-fc-wysiwyg="1"
                                            rows="6"
                                            class="fc-fs-input fc-fs-wysiwyg"
                                        ><?php echo e((string) ($ppStock['lowStockHtml'] ?? '')); ?></textarea>
                                    </div>
                                </div>
                                </div>
                            </section>
                        </div>

                        <div id="fc-settings-panel-seo" class="<?php echo e((string) $tab['panel_class']['seo']); ?>space-y-5">
                            <section class="fc-settings-card flex flex-col border border-slate-200 bg-white" aria-labelledby="fc-seo-hero-title">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h2 class="fc-settings-card__title" id="fc-seo-hero-title">SEO Overview</h2>
                                    </div>
                                    <div class="fc-settings-card__aside fc-seo-hero__visibility">
                                        <div class="fc-seo-hero__visibility-row">
                                            <span class="fc-seo-hero__visibility-label" id="fc-seo-visibility-label">Search engine visibility</span>
                                            <div class="fc-mode-switch" role="group" aria-labelledby="fc-seo-visibility-label">
                                                <button
                                                    type="button"
                                                    data-fc-seo-visibility="0"
                                                    aria-pressed="<?php echo $tab['seo_visible'] ? 'false' : 'true'; ?>"
                                                    class="fc-mode-switch__side"
                                                >Disabled</button>
                                                <button
                                                    type="button"
                                                    data-fc-seo-visibility-toggle
                                                    role="switch"
                                                    aria-checked="<?php echo $tab['seo_visible'] ? 'true' : 'false'; ?>"
                                                    aria-labelledby="fc-seo-visibility-label"
                                                    class="fc-mode-switch__track"
                                                ><span class="fc-mode-switch__thumb"></span></button>
                                                <button
                                                    type="button"
                                                    data-fc-seo-visibility="1"
                                                    aria-pressed="<?php echo $tab['seo_visible'] ? 'true' : 'false'; ?>"
                                                    class="fc-mode-switch__side"
                                                >Enabled</button>
                                            </div>
                                        </div>
                                    </div>
                                </header>

                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                <div class="fc-seo-health">
                                    <div class="fc-seo-health__head">
                                        <h3 class="fc-seo-health__title">Health check</h3>
                                        <div class="fc-seo-score" data-fc-seo-score data-state="good">
                                            <span class="fc-seo-score__text" data-fc-seo-score-text aria-live="polite"></span>
                                            <span class="fc-seo-score__bar" aria-hidden="true"><span class="fc-seo-score__fill" data-fc-seo-score-fill></span></span>
                                        </div>
                                    </div>
                                    <div class="fc-seo-checks">
                                        <?php foreach ($tab['seo_checks'] as $check) : ?>
                                        <button type="button" class="fc-seo-check" data-fc-seo-check="<?php echo e($check['key']); ?>" data-fc-seo-jump="<?php echo e($check['target']); ?>" data-state="info">
                                            <span class="fc-seo-check__icon" aria-hidden="true"><i class="fa-solid fa-circle-minus" data-fc-seo-check-icon></i></span>
                                            <span class="fc-seo-check__text">
                                                <span class="fc-seo-check__label"><?php echo e($check['label']); ?></span>
                                                <span class="fc-seo-check__value" data-fc-seo-check-value></span>
                                            </span>
                                            <i class="fa-solid fa-chevron-right fc-seo-check__go" aria-hidden="true"></i>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="fc-seo-hero__links">
                                    <a class="fc-seo-link" href="<?php echo e((string) $tab['seo_context']['plannerUrl']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Open the planner</a>
                                    <?php if ($tab['seo_tests_enabled']) : ?>
                                    <a class="fc-seo-link" data-fc-seo-test="rich-results" href="https://search.google.com/test/rich-results" target="_blank" rel="noopener"><i class="fa-brands fa-google" aria-hidden="true"></i>Rich Results Test</a>
                                    <a class="fc-seo-link" data-fc-seo-test="share-debugger" href="https://developers.facebook.com/tools/debug/" target="_blank" rel="noopener"><i class="fa-brands fa-facebook" aria-hidden="true"></i>Sharing Debugger</a>
                                    <?php else : ?>
                                    <span class="fc-seo-link is-disabled" aria-disabled="true" title="Google's and Facebook's testers fetch the page from the internet, so they work on the live site, not on localhost."><i class="fa-brands fa-google" aria-hidden="true"></i>Rich Results Test</span>
                                    <span class="fc-seo-link is-disabled" aria-disabled="true" title="Google's and Facebook's testers fetch the page from the internet, so they work on the live site, not on localhost."><i class="fa-brands fa-facebook" aria-hidden="true"></i>Sharing Debugger</span>
                                    <?php endif; ?>
                                </div>
                                </div>
                            </section>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white" aria-labelledby="fc-seo-section-appearance">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title" id="fc-seo-section-appearance">Search Appearance</h3>
                                        </div>
                                        <span class="fc-seo-chip fc-settings-card__aside" data-fc-seo-chip="appearance" data-state="info"></span>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <label class="text-sm font-medium text-slate-700" for="fc-seo-siteName">Site name</label>
                                        <input type="text" id="fc-seo-siteName" data-fc-seo-field="siteName" value="<?php echo e((string) $tab['seo']['siteName']); ?>" placeholder="<?php echo e((string) $tab['seo_context']['autoSiteName']); ?>" class="fc-settings-field" maxlength="120" autocomplete="off" />
                                        <div class="fc-seo-tokens">
                                            <span class="fc-seo-tokens__label">Insert</span>
                                            <?php foreach ($tab['seo_placeholders'] as $placeholder) : ?>
                                            <button type="button" class="fc-seo-token" data-fc-seo-token="<?php echo e($placeholder['token']); ?>" data-fc-seo-token-for="fc-seo-siteName" title="<?php echo e($placeholder['label']); ?>" aria-label="Insert <?php echo e($placeholder['label']); ?> into the site name"><?php echo e($placeholder['token']); ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="text-xs text-slate-500">Fills {site_name}, the social card and the structured data. Here {site_name} is this site&rsquo;s own name, &ldquo;<?php echo e((string) $tab['seo_context']['autoSiteName']); ?>&rdquo;, which is also used when this is blank.</span>
                                    </div>
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <label class="text-sm font-medium text-slate-700" for="fc-seo-siteLogo">Site logo</label>
                                        <div class="flex items-center gap-2">
                                            <span class="fc-settings-site-logo shrink-0" data-fc-seo-logo-preview></span>
                                            <div class="fc-settings-branding-logo__inputs min-w-0 flex-1">
                                                <input type="text" id="fc-seo-siteLogo" data-fc-seo-field="siteLogo" value="<?php echo e((string) $tab['seo']['siteLogo']); ?>" placeholder="<?php echo e((string) $tab['seo_context']['logoPath']); ?>" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" />
                                                <button type="button" class="fc-settings-branding-logo__pick" data-fc-seo-pick="fc-seo-siteLogo" title="Upload or choose the site logo" aria-label="Upload or choose the site logo"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                                <button type="button" class="fc-settings-branding-logo__clear" data-fc-seo-clear="fc-seo-siteLogo" title="Back to the default logo" aria-label="Back to the default logo"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                            </div>
                                        </div>
                                        <span class="text-xs text-slate-500">Stands in for the share image and the structured-data logo when they are blank. Leave blank to use this site&rsquo;s own logo (Settings &rarr; Integration).</span>
                                    </div>
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <div class="flex items-baseline justify-between gap-2">
                                            <label class="text-sm font-medium text-slate-700" for="fc-seo-titleTemplate">SEO title</label>
                                            <span class="fc-seo-count" data-fc-seo-count="title" data-fc-seo-limit="<?php echo (int) $tab['seo_title_limit']; ?>"></span>
                                        </div>
                                        <input type="text" id="fc-seo-titleTemplate" data-fc-seo-field="titleTemplate" value="<?php echo e((string) $tab['seo']['titleTemplate']); ?>" placeholder="<?php echo e((string) $tab['seo_defaults']['titleTemplate']); ?>" class="fc-settings-field" maxlength="200" autocomplete="off" />
                                        <div class="fc-seo-tokens">
                                            <span class="fc-seo-tokens__label">Insert</span>
                                            <?php foreach ($tab['seo_placeholders'] as $placeholder) : ?>
                                            <button type="button" class="fc-seo-token" data-fc-seo-token="<?php echo e($placeholder['token']); ?>" data-fc-seo-token-for="fc-seo-titleTemplate" title="<?php echo e($placeholder['label']); ?>" aria-label="Insert <?php echo e($placeholder['label']); ?> into the SEO title"><?php echo e($placeholder['token']); ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <div class="flex items-baseline justify-between gap-2">
                                            <label class="text-sm font-medium text-slate-700" for="fc-seo-descriptionTemplate">Meta description</label>
                                            <span class="fc-seo-count" data-fc-seo-count="description" data-fc-seo-limit="<?php echo (int) $tab['seo_description_limit']; ?>"></span>
                                        </div>
                                        <textarea id="fc-seo-descriptionTemplate" data-fc-seo-field="descriptionTemplate" rows="3" placeholder="<?php echo e((string) $tab['seo_defaults']['descriptionTemplate']); ?>" class="fc-settings-field" maxlength="500"><?php echo e((string) $tab['seo']['descriptionTemplate']); ?></textarea>
                                        <div class="fc-seo-tokens">
                                            <span class="fc-seo-tokens__label">Insert</span>
                                            <?php foreach ($tab['seo_placeholders'] as $placeholder) : ?>
                                            <button type="button" class="fc-seo-token" data-fc-seo-token="<?php echo e($placeholder['token']); ?>" data-fc-seo-token-for="fc-seo-descriptionTemplate" title="<?php echo e($placeholder['label']); ?>" aria-label="Insert <?php echo e($placeholder['label']); ?> into the meta description"><?php echo e($placeholder['token']); ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="text-xs text-slate-500">Leave either one blank to go back to the default wording.</span>
                                    </div>
                                    <div class="fc-seo-preview">
                                        <p class="fc-seo-preview__label">Search result preview</p>
                                        <div class="fc-seo-serp" data-fc-seo-serp>
                                            <div class="fc-seo-serp__site">
                                                <span class="fc-seo-serp__favicon" data-fc-seo-preview-logo>
                                                    <?php if ($tab['seo_context']['faviconUrl'] !== '') : ?>
                                                    <img src="<?php echo e((string) $tab['seo_context']['faviconUrl']); ?>" alt="" decoding="async">
                                                    <?php else : ?>
                                                    <i class="fa-solid fa-globe" aria-hidden="true"></i>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="fc-seo-serp__site-text">
                                                    <span class="fc-seo-serp__site-name" data-fc-seo-preview="siteName"></span>
                                                    <span class="fc-seo-serp__url" data-fc-seo-preview="displayUrl"></span>
                                                </span>
                                            </div>
                                            <p class="fc-seo-serp__title" data-fc-seo-preview="title"></p>
                                            <p class="fc-seo-serp__desc" data-fc-seo-preview="description"></p>
                                        </div>
                                        <p class="fc-seo-preview__hidden" data-fc-seo-preview-hidden hidden><i class="fa-solid fa-eye-slash" aria-hidden="true"></i>The planner is hidden from search engines, so it won&rsquo;t be listed at all.</p>
                                    </div>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white" aria-labelledby="fc-seo-section-indexing">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title" id="fc-seo-section-indexing">Indexing</h3>
                                        </div>
                                        <span class="fc-seo-chip fc-settings-card__aside" data-fc-seo-chip="indexing" data-state="info"></span>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <?php foreach ($tab['seo_pages'] as $page) : ?>
                                    <?php if ($page['locked']) : ?>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700"><?php echo e($page['label']); ?> <code class="fc-seo-path"><?php echo e($page['path']); ?></code></span>
                                            <span class="block text-xs text-slate-500"><?php echo e($page['hint']); ?></span>
                                        </span>
                                        <span class="fc-seo-lock"><i class="fa-solid fa-lock" aria-hidden="true"></i>Always hidden</span>
                                    </div>
                                    <?php else : ?>
                                    <label class="flex items-center justify-between gap-3" for="fc-seo-<?php echo e($page['field']); ?>">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700"><?php echo e($page['label']); ?> <code class="fc-seo-path"><?php echo e($page['path']); ?></code></span>
                                            <span class="block text-xs text-slate-500"><?php echo e($page['hint']); ?></span>
                                        </span>
                                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                                            <input
                                                type="checkbox"
                                                id="fc-seo-<?php echo e($page['field']); ?>"
                                                data-fc-seo-field="<?php echo e($page['field']); ?>"
                                                class="peer sr-only"
                                                <?php echo $page['checked'] ? 'checked' : ''; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                    <div data-fc-seo-hidden-note<?php echo $tab['seo_visible'] ? ' hidden' : ''; ?>>
                                        <p class="fc-seo-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>Search engine visibility is disabled, so every page is hidden until it is enabled again.</span></p>
                                    </div>
                                    <label class="flex items-center justify-between gap-3" for="fc-seo-canonicalEnabled">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-slate-700">Canonical URL</span>
                                            <span class="block text-xs text-slate-500">Points every variant of the planner address (?fence=, ?section=&hellip;) at one URL, so search engines don&rsquo;t count them as duplicates.</span>
                                        </span>
                                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                                            <input
                                                type="checkbox"
                                                id="fc-seo-canonicalEnabled"
                                                data-fc-seo-field="canonicalEnabled"
                                                class="peer sr-only"
                                                <?php echo !empty($tab['seo']['canonicalEnabled']) ? 'checked' : ''; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-seo-canonicalUrl">
                                        <span class="text-sm font-medium text-slate-700">Custom canonical URL</span>
                                        <input type="url" id="fc-seo-canonicalUrl" data-fc-seo-field="canonicalUrl" value="<?php echo e((string) $tab['seo']['canonicalUrl']); ?>" placeholder="<?php echo e((string) $tab['seo_context']['plannerUrl']); ?>" class="fc-settings-field font-mono text-xs" autocomplete="off" spellcheck="false" />
                                        <span class="text-xs text-slate-500">Leave blank to use the planner&rsquo;s own address. Also used as the shared link and in the structured data.</span>
                                    </label>
                                    </div>
                                </section>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white" aria-labelledby="fc-seo-section-social">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title" id="fc-seo-section-social">Social Sharing</h3>
                                        </div>
                                        <label class="fc-settings-card__aside relative inline-flex shrink-0 cursor-pointer items-center" for="fc-seo-socialEnabled">
                                            <span class="sr-only">Add social sharing tags</span>
                                            <input
                                                type="checkbox"
                                                id="fc-seo-socialEnabled"
                                                data-fc-seo-field="socialEnabled"
                                                class="peer sr-only"
                                                <?php echo !empty($tab['seo']['socialEnabled']) ? 'checked' : ''; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </label>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections fc-seo-group" data-fc-seo-group="socialEnabled">
                                        <label class="flex min-w-0 flex-col gap-1" for="fc-seo-socialTitle">
                                            <span class="text-sm font-medium text-slate-700">Share title</span>
                                            <input type="text" id="fc-seo-socialTitle" data-fc-seo-field="socialTitle" value="<?php echo e((string) $tab['seo']['socialTitle']); ?>" placeholder="Same as the SEO title" class="fc-settings-field" maxlength="200" autocomplete="off" />
                                        </label>
                                        <label class="flex min-w-0 flex-col gap-1" for="fc-seo-socialDescription">
                                            <span class="text-sm font-medium text-slate-700">Share description</span>
                                            <textarea id="fc-seo-socialDescription" data-fc-seo-field="socialDescription" rows="2" placeholder="Same as the meta description" class="fc-settings-field" maxlength="500"><?php echo e((string) $tab['seo']['socialDescription']); ?></textarea>
                                        </label>
                                        <div class="flex min-w-0 flex-col gap-1">
                                            <label class="text-sm font-medium text-slate-700" for="fc-seo-socialImage">Share image</label>
                                            <div class="fc-settings-branding-logo__inputs">
                                                <input type="text" id="fc-seo-socialImage" data-fc-seo-field="socialImage" value="<?php echo e((string) $tab['seo']['socialImage']); ?>" placeholder="public/assets/uploads/share.jpg" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" />
                                                <button type="button" class="fc-settings-branding-logo__pick" data-fc-seo-pick="fc-seo-socialImage" title="Upload or choose the share image" aria-label="Upload or choose the share image"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                                <button type="button" class="fc-settings-branding-logo__clear" data-fc-seo-clear="fc-seo-socialImage" title="Remove the share image" aria-label="Remove the share image"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                            </div>
                                            <span class="text-xs text-slate-500">1200 &times; 630 px suits every network. Leave blank to use the site logo.</span>
                                        </div>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <label class="flex min-w-0 flex-col gap-1" for="fc-seo-twitterCard">
                                                <span class="text-sm font-medium text-slate-700">X card</span>
                                                <select id="fc-seo-twitterCard" data-fc-seo-field="twitterCard" class="fc-settings-field">
                                                    <?php foreach ($tab['seo_twitter_card_choices'] as $value => $label) : ?>
                                                    <option value="<?php echo e((string) $value); ?>"<?php echo (string) $tab['seo']['twitterCard'] === (string) $value ? ' selected' : ''; ?>><?php echo e((string) $label); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label class="flex min-w-0 flex-col gap-1" for="fc-seo-twitterSite">
                                                <span class="text-sm font-medium text-slate-700">X username</span>
                                                <input type="text" id="fc-seo-twitterSite" data-fc-seo-field="twitterSite" value="<?php echo e((string) $tab['seo']['twitterSite']); ?>" placeholder="@yourstore" class="fc-settings-field" maxlength="60" autocomplete="off" spellcheck="false" />
                                            </label>
                                        </div>
                                        <div class="fc-seo-preview">
                                            <p class="fc-seo-preview__label">Share preview</p>
                                            <div class="fc-seo-card" data-fc-seo-card data-card="<?php echo e((string) $tab['seo']['twitterCard']); ?>">
                                                <div class="fc-seo-card__image" data-fc-seo-preview-image></div>
                                                <div class="fc-seo-card__body">
                                                    <p class="fc-seo-card__domain" data-fc-seo-preview="domain"></p>
                                                    <p class="fc-seo-card__title" data-fc-seo-preview="socialTitle"></p>
                                                    <p class="fc-seo-card__desc" data-fc-seo-preview="socialDescription"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white" aria-labelledby="fc-seo-section-schema">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title" id="fc-seo-section-schema">Structured Data</h3>
                                        </div>
                                        <label class="fc-settings-card__aside relative inline-flex shrink-0 cursor-pointer items-center" for="fc-seo-schemaEnabled">
                                            <span class="sr-only">Add structured data</span>
                                            <input
                                                type="checkbox"
                                                id="fc-seo-schemaEnabled"
                                                data-fc-seo-field="schemaEnabled"
                                                class="peer sr-only"
                                                <?php echo !empty($tab['seo']['schemaEnabled']) ? 'checked' : ''; ?>
                                            >
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-[var(--fc-princeton-orange)] peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-[var(--fc-princeton-orange)]"></span>
                                            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                                        </label>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections fc-seo-group" data-fc-seo-group="schemaEnabled">
                                        <label class="flex min-w-0 flex-col gap-1" for="fc-seo-schemaUrl">
                                            <span class="text-sm font-medium text-slate-700">Business website</span>
                                            <input type="url" id="fc-seo-schemaUrl" data-fc-seo-field="schemaUrl" value="<?php echo e((string) $tab['seo']['schemaUrl']); ?>" placeholder="<?php echo e((string) $tab['seo_context']['storeUrl']); ?>" class="fc-settings-field font-mono text-xs" autocomplete="off" spellcheck="false" />
                                            <span class="text-xs text-slate-500">Leave blank to use the store&rsquo;s address.</span>
                                        </label>
                                        <div class="flex min-w-0 flex-col gap-1">
                                            <label class="text-sm font-medium text-slate-700" for="fc-seo-schemaLogo">Logo</label>
                                            <div class="fc-settings-branding-logo__inputs">
                                                <input type="text" id="fc-seo-schemaLogo" data-fc-seo-field="schemaLogo" value="<?php echo e((string) $tab['seo']['schemaLogo']); ?>" placeholder="public/assets/uploads/logo.png" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" />
                                                <button type="button" class="fc-settings-branding-logo__pick" data-fc-seo-pick="fc-seo-schemaLogo" title="Upload or choose the logo" aria-label="Upload or choose the logo"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                                <button type="button" class="fc-settings-branding-logo__clear" data-fc-seo-clear="fc-seo-schemaLogo" title="Remove the logo" aria-label="Remove the logo"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                            </div>
                                            <span class="text-xs text-slate-500">Leave blank to use the site logo.</span>
                                        </div>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <label class="flex min-w-0 flex-col gap-1" for="fc-seo-schemaPhone">
                                                <span class="text-sm font-medium text-slate-700">Phone</span>
                                                <input type="tel" id="fc-seo-schemaPhone" data-fc-seo-field="schemaPhone" value="<?php echo e((string) $tab['seo']['schemaPhone']); ?>" placeholder="Optional" class="fc-settings-field" maxlength="30" autocomplete="off" />
                                            </label>
                                            <label class="flex min-w-0 flex-col gap-1" for="fc-seo-schemaEmail">
                                                <span class="text-sm font-medium text-slate-700">Email</span>
                                                <input type="email" id="fc-seo-schemaEmail" data-fc-seo-field="schemaEmail" value="<?php echo e((string) $tab['seo']['schemaEmail']); ?>" placeholder="Optional" class="fc-settings-field" maxlength="254" autocomplete="off" spellcheck="false" />
                                            </label>
                                        </div>
                                        <label class="flex min-w-0 flex-col gap-1" for="fc-seo-schemaSameAs">
                                            <span class="text-sm font-medium text-slate-700">Social profiles</span>
                                            <textarea id="fc-seo-schemaSameAs" data-fc-seo-field="schemaSameAs" data-fc-seo-list rows="4" placeholder="https://www.facebook.com/yourstore&#10;https://www.instagram.com/yourstore" class="fc-settings-field font-mono text-xs" spellcheck="false"><?php echo e((string) $tab['seo_same_as']); ?></textarea>
                                            <span class="text-xs text-slate-500">One address per line: Facebook, Instagram, YouTube, LinkedIn&hellip;</span>
                                        </label>
                                    </div>
                                </section>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white" aria-labelledby="fc-seo-section-verification">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title" id="fc-seo-section-verification">Webmaster Verification</h3>
                                        </div>
                                        <span class="fc-seo-chip fc-settings-card__aside" data-fc-seo-chip="verification" data-state="info"></span>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                        <?php foreach ($tab['seo_verification'] as $field) : ?>
                                        <label class="flex min-w-0 flex-col gap-1" for="<?php echo e($field['field_id']); ?>">
                                            <span class="text-sm font-medium text-slate-700"><?php echo e($field['label']); ?></span>
                                            <input type="text" id="<?php echo e($field['field_id']); ?>" data-fc-seo-field="<?php echo e($field['key']); ?>" value="<?php echo e($field['value']); ?>" placeholder="<?php echo e($field['meta']); ?> code" class="fc-settings-field font-mono text-xs" autocomplete="off" spellcheck="false" />
                                            <span class="text-xs text-slate-500"><?php echo e($field['hint']); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </section>

                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white" aria-labelledby="fc-seo-section-advanced">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title" id="fc-seo-section-advanced">Advanced</h3>
                                        </div>
                                        <span class="fc-seo-chip fc-settings-card__aside" data-fc-seo-chip="advanced" data-state="info"></span>
                                    </header>
                                    <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-seo-language">
                                        <span class="text-sm font-medium text-slate-700">Language</span>
                                        <select id="fc-seo-language" data-fc-seo-field="language" class="fc-settings-field">
                                            <?php foreach ($tab['seo_language_choices'] as $value => $label) : ?>
                                            <option value="<?php echo e((string) $value); ?>"<?php echo (string) $tab['seo']['language'] === (string) $value ? ' selected' : ''; ?>><?php echo e((string) $label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="text-xs text-slate-500">The language public pages declare (lang), and the social card&rsquo;s locale.</span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-seo-maxImagePreview">
                                        <span class="text-sm font-medium text-slate-700">Image previews</span>
                                        <select id="fc-seo-maxImagePreview" data-fc-seo-field="maxImagePreview" class="fc-settings-field">
                                            <?php foreach ($tab['seo_image_preview_choices'] as $value => $label) : ?>
                                            <option value="<?php echo e((string) $value); ?>"<?php echo (string) $tab['seo']['maxImagePreview'] === (string) $value ? ' selected' : ''; ?>><?php echo e((string) $label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="text-xs text-slate-500">How big an image search results and Google Discover may show (max-image-preview).</span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-seo-maxSnippet">
                                        <span class="text-sm font-medium text-slate-700">Snippet length</span>
                                        <input type="number" id="fc-seo-maxSnippet" data-fc-seo-field="maxSnippet" value="<?php echo (int) $tab['seo']['maxSnippet']; ?>" class="fc-settings-field" min="-1" max="5000" step="1" />
                                        <span class="text-xs text-slate-500">Characters of text a result may show: -1 for no limit, 0 for none (max-snippet).</span>
                                    </label>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <div id="fc-settings-panel-console" class="<?php echo e((string) $tab['panel_class']['console']); ?>space-y-5">
                            <?php
                            $consoleSettings = is_array($tab['console'] ?? null) ? $tab['console'] : [];
                            $debugModeOn = !empty($consoleSettings['debugMode']);
                            ?>
                            <?php /* The switch is the whole card, so its header stands alone. */ ?>
                            <section class="fc-settings-card border border-slate-200 bg-white">
                                <header class="fc-settings-card__head border-b-0">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Debug Mode</h3>
                                    </div>
                                    <div class="fc-settings-card__aside fc-mode-switch" role="group" aria-label="Debug Mode">
                                        <button
                                            type="button"
                                            data-fc-debug-mode="0"
                                            aria-pressed="<?php echo $debugModeOn ? 'false' : 'true'; ?>"
                                            class="fc-mode-switch__side"
                                        >Off</button>
                                        <button
                                            type="button"
                                            data-fc-debug-mode-toggle
                                            role="switch"
                                            aria-checked="<?php echo $debugModeOn ? 'true' : 'false'; ?>"
                                            aria-label="Debug Mode"
                                            class="fc-mode-switch__track"
                                        ><span class="fc-mode-switch__thumb"></span></button>
                                        <button
                                            type="button"
                                            data-fc-debug-mode="1"
                                            aria-pressed="<?php echo $debugModeOn ? 'true' : 'false'; ?>"
                                            class="fc-mode-switch__side"
                                        >On</button>
                                    </div>
                                </header>
                            </section>

                            <section class="fc-settings-card border border-slate-200 bg-white">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h3 class="fc-settings-card__title">Console</h3>
                                    </div>
                                </header>
                                <div class="fc-settings-card__body">
                                <div class="fc-dev-console" id="fc-dev-console" data-fc-dev-console>
                                    <div class="fc-dev-console__output" id="fc-dev-console-output" role="log" aria-live="polite" aria-relevant="additions"></div>
                                    <form class="fc-dev-console__prompt" id="fc-dev-console-form" autocomplete="off">
                                        <label class="sr-only" for="fc-dev-console-input">Console command</label>
                                        <span class="fc-dev-console__prefix" aria-hidden="true">$</span>
                                        <input
                                            type="text"
                                            id="fc-dev-console-input"
                                            class="fc-dev-console__input"
                                            placeholder="Type a command…"
                                            spellcheck="false"
                                            autocomplete="off"
                                            autocapitalize="off"
                                            enterkeyhint="enter"
                                        />
                                    </form>
                                </div>
                                </div>
                            </section>
                        </div>

                        <?php if ($tab['site_health_enabled']) : ?>
                        <div id="fc-settings-panel-site-health" class="<?php echo e((string) $tab['panel_class']['site_health']); ?>space-y-5">
                            <section class="fc-settings-card flex flex-col border border-slate-200 bg-white" aria-labelledby="fc-health-overview-title">
                                <header class="fc-settings-card__head">
                                    <div class="fc-settings-card__heading">
                                        <h2 class="fc-settings-card__title" id="fc-health-overview-title">Site Health Overview</h2>
                                    </div>
                                    <span class="fc-settings-card__aside fc-health-checked" data-fc-health-checked></span>
                                </header>
                                <div class="fc-settings-card__body fc-settings-card__body--sections">
                                    <div class="fc-seo-health">
                                        <div class="fc-seo-health__head">
                                            <h3 class="fc-seo-health__title">Health check</h3>
                                            <div class="fc-seo-score" data-fc-health-score data-state="info">
                                                <span class="fc-seo-score__text" data-fc-health-score-text aria-live="polite">Running checks…</span>
                                                <span class="fc-seo-score__bar" aria-hidden="true"><span class="fc-seo-score__fill" data-fc-health-score-fill></span></span>
                                            </div>
                                        </div>
                                        <div class="fc-seo-checks">
                                            <?php foreach ($tab['site_health_groups'] as $group) : ?>
                                            <button type="button" class="fc-seo-check fc-health-tile" data-fc-health-tile="<?php echo e($group['key']); ?>" data-state="info">
                                                <span class="fc-seo-check__icon" aria-hidden="true"><i class="fa-solid fa-circle-notch fa-spin" data-fc-health-tile-icon></i></span>
                                                <span class="fc-seo-check__text">
                                                    <span class="fc-seo-check__label"><?php echo e($group['label']); ?></span>
                                                    <span class="fc-seo-check__value" data-fc-health-tile-value>Checking…</span>
                                                </span>
                                                <i class="fa-solid fa-chevron-right fc-seo-check__go" aria-hidden="true"></i>
                                            </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                                <?php foreach ($tab['site_health_groups'] as $group) : ?>
                                <section class="fc-settings-card flex h-full flex-col border border-slate-200 bg-white <?php echo e($group['card_class']); ?>" aria-labelledby="<?php echo e($group['title_id']); ?>" data-fc-health-group="<?php echo e($group['key']); ?>">
                                    <header class="fc-settings-card__head">
                                        <div class="fc-settings-card__heading">
                                            <h3 class="fc-settings-card__title" id="<?php echo e($group['title_id']); ?>"><?php echo e($group['label']); ?></h3>
                                        </div>
                                        <span class="fc-seo-chip fc-settings-card__aside" data-fc-health-chip data-state="info"></span>
                                    </header>
                                    <div class="fc-settings-card__body fc-health-body">
                                        <ul class="fc-health-list" data-fc-health-list aria-busy="true"></ul>
                                    </div>
                                </section>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php /* Beside the panels (lg) it takes the 1.25rem each panel gets from the column's space-y-5 (class-hidden siblings still count). */ ?>
                    <div class="<?php echo e((string) $tab['preview_hidden']); ?>sticky top-4 z-10 self-start lg:mt-[1.25rem]" id="fc-settings-preview">
                        <?php if ($tab['preview_mode'] === 'branding') : ?>
                        <?php /* Same markup as BrandingTabController::renderPreview(), which rebuilds it on a tab switch: edit in pairs. */ ?>
                        <section class="fc-settings-card flex flex-col border border-slate-200 bg-white" aria-labelledby="fc-branding-preview-heading">
                            <header class="fc-settings-card__head">
                                <div class="fc-settings-card__heading">
                                    <h3 class="fc-settings-card__title" id="fc-branding-preview-heading">Live Preview</h3>
                                </div>
                            </header>
                            <div class="fc-settings-card__body fc-settings-card__body--sections text-sm">
                                <div class="flex items-start gap-4">
                                    <div class="flex flex-col items-start">
                                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Logo</p>
                                        <div id="fc-branding-preview-logo" class="fc-settings-branding-logo__preview fc-settings-branding-logo__preview--sidebar<?php echo ($tab['branding_preview']['logo_url'] ?? '') !== '' ? '' : ' fc-settings-branding-logo__preview--empty'; ?>"<?php echo ($tab['branding_preview']['logo_url'] ?? '') !== '' ? ' style="--fc-branding-logo-preview:url(' . e((string) $tab['branding_preview']['logo_url']) . ');width:48px;height:48px;"' : ' style="width:48px;height:48px;"'; ?>>
                                            <span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-border-all"></i></span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-start">
                                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Favicon</p>
                                        <div id="fc-branding-preview-favicon" class="fc-settings-branding-logo__preview fc-settings-branding-logo__preview--sidebar"<?php echo ($tab['branding_preview']['favicon_url'] ?? '') !== '' ? ' style="--fc-branding-logo-preview:url(' . e((string) $tab['branding_preview']['favicon_url']) . ')"' : ''; ?>>
                                            <span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">App name</p>
                                    <p id="fc-branding-preview-title" class="truncate font-bold leading-snug text-slate-900"><?php echo e((string) $tab['branding_preview']['app_name']); ?></p>
                                </div>
                                <div>
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tagline</p>
                                    <p id="fc-branding-preview-tagline" class="leading-snug text-slate-600"><?php echo e((string) $tab['branding_preview']['tagline']); ?></p>
                                </div>
                                <div>
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Footer</p>
                                    <p id="fc-branding-preview-footer" class="truncate text-xs text-slate-500">
                                        <span id="fc-branding-preview-footer-name"><?php echo e((string) $tab['branding_preview']['app_name']); ?></span>
                                        <span id="fc-branding-preview-version"><?php echo e((string) $tab['branding_preview']['version']); ?></span>
                                    </p>
                                </div>
                                <p class="text-xs text-slate-500">Saved branding applies on the <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../planner" target="_blank" rel="noopener">planner</a> after save (refresh if already open).</p>
                            </div>
                        </section>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
