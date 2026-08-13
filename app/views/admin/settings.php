<?php
/**
 * FC Admin — Settings page (server-rendered).
 *
 * @var array<string, mixed> $fcSettingsPage
 */

declare(strict_types=1);

if (!isset($fcSettingsPage) || !is_array($fcSettingsPage)) {
    return;
}

$h = static fn(string $v): string => \Fc\Admin\Helpers\StringHelper::escapeHtml($v);
$tab = $fcSettingsPage;
?>
<div
    class="fc-settings-page flex h-full min-h-0 flex-col"
    data-fc-settings-initial-tab="<?php echo $h((string) $tab['initial_tab']); ?>"
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
                                data-fc-settings-tab="<?php echo $h((string) $tabId); ?>"
                                aria-selected="<?php echo $tab['active_tab'] === $tabId ? 'true' : 'false'; ?>"
                                class="rounded-md px-4 py-2 text-sm font-medium transition <?php echo $tab['active_tab'] === $tabId ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'; ?>"
                            ><?php echo $h((string) $tabLabel); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <span id="fc-settings-theme-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-branding-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-fence-colors-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-catalog-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-system-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-integration-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                    <span id="fc-settings-project-plan-dirty" class="hidden text-xs font-medium text-amber-600">Unsaved changes</span>
                </div>
                <div id="fc-settings-header-actions-theme" class="<?php echo $h((string) $tab['header_actions_class']['theme']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-theme-reset" class="<?php echo $h((string) $tab['btn_secondary']); ?>">Reset Defaults</button>
                    <button type="button" id="fc-theme-save" class="<?php echo $h((string) $tab['btn_primary']); ?>">Save Theme</button>
                </div>
                <div id="fc-settings-header-actions-branding" class="<?php echo $h((string) $tab['header_actions_class']['branding']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-branding-reset" class="<?php echo $h((string) $tab['btn_secondary']); ?>">Reset Defaults</button>
                    <button type="button" id="fc-branding-save" class="<?php echo $h((string) $tab['btn_primary']); ?>">Save Branding</button>
                </div>
                <div id="fc-settings-header-actions-fence-colors" class="<?php echo $h((string) $tab['header_actions_class']['fence_colors']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-fence-colors-reset" class="<?php echo $h((string) $tab['btn_secondary']); ?>">Reset Defaults</button>
                    <button type="button" id="fc-fence-colors-save" class="<?php echo $h((string) $tab['btn_primary']); ?>">Save Fence Colors</button>
                </div>
                <div id="fc-settings-header-actions-catalog" class="<?php echo $h((string) $tab['header_actions_class']['catalog']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-catalog-reset" class="<?php echo $h((string) $tab['btn_secondary']); ?>">Reset Defaults</button>
                    <button type="button" id="fc-catalog-save" class="<?php echo $h((string) $tab['btn_primary']); ?>">Save Catalog</button>
                </div>
                <div id="fc-settings-header-actions-system" class="<?php echo $h((string) $tab['header_actions_class']['system']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-system-reset" class="<?php echo $h((string) $tab['btn_secondary']); ?>">Reset Defaults</button>
                    <button type="button" id="fc-system-save" class="<?php echo $h((string) $tab['btn_primary']); ?>">Save System</button>
                </div>
                <div id="fc-settings-header-actions-integration" class="<?php echo $h((string) $tab['header_actions_class']['integration']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-integration-reset" class="<?php echo $h((string) $tab['btn_secondary']); ?>">Discard Changes</button>
                    <button type="button" id="fc-integration-save" class="<?php echo $h((string) $tab['btn_primary']); ?>">Save Integrations</button>
                </div>
                <div id="fc-settings-header-actions-project-plan" class="<?php echo $h((string) $tab['header_actions_class']['project_plan']); ?> flex-wrap gap-2">
                    <button type="button" id="fc-project-plan-reset" class="<?php echo $h((string) $tab['btn_secondary']); ?>">Reset Defaults</button>
                    <button type="button" id="fc-project-plan-save" class="<?php echo $h((string) $tab['btn_primary']); ?>">Save Project Plan</button>
                </div>
                <div id="fc-settings-header-actions-console" class="<?php echo $h((string) $tab['header_actions_class']['console']); ?> flex-wrap gap-2"></div>
            </div>
            <div data-fc-settings-notice hidden class="fc-entries-page__notice fc-entries-page__notice--success" aria-hidden="true"></div>

            <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden">
                <div id="fc-settings-layout" class="grid w-full grid-cols-1 gap-6 p-4 sm:p-6 lg:items-start <?php echo $h((string) $tab['layout_class']); ?>">
                    <div class="min-w-0 space-y-5">
                        <div id="fc-settings-panel-theme" class="<?php echo $h((string) $tab['panel_class']['theme']); ?>space-y-5">
                            <?php if (!empty($tab['presets'])) : ?>
                            <section class="border border-slate-200 bg-white p-4 sm:p-5">
                                <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-500">Presets</h3>
                                <p class="mb-4 text-sm text-slate-500">Apply a ready-made palette. You can fine-tune individual colors below.</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <?php foreach ($tab['presets'] as $preset) : ?>
                                    <button
                                        type="button"
                                        data-fc-theme-preset="<?php echo $h((string) $preset['id']); ?>"
                                        class="fc-theme-preset group flex items-start gap-3 border-2 p-4 text-left transition <?php echo $h((string) $preset['card_class']); ?>"
                                        aria-pressed="<?php echo !empty($preset['is_selected']) ? 'true' : 'false'; ?>"
                                    >
                                        <span class="mt-0.5 flex h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 shadow-sm" aria-hidden="true">
                                            <span class="h-full w-1/2" style="background:<?php echo $h((string) $preset['accent']); ?>"></span>
                                            <span class="h-full w-1/2" style="background:<?php echo $h((string) $preset['brand_primary']); ?>"></span>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-slate-900"><?php echo $h((string) $preset['label']); ?></span>
                                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-500"><?php echo $h((string) $preset['description']); ?></span>
                                            <span
                                                data-fc-theme-active-badge
                                                class="<?php echo !empty($preset['is_active']) ? '' : 'hidden '; ?>mt-2 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold shadow-sm"
                                                style="<?php echo $h((string) $preset['badge_styles']); ?>"
                                            >
                                                <i class="fa-solid fa-circle-check text-[11px]" aria-hidden="true"></i> Active
                                            </span>
                                        </span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                            <?php endif; ?>

                            <?php foreach ($tab['theme_groups'] as $group) : ?>
                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500"><?php echo $h((string) $group['label']); ?></h3>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <?php foreach ($group['fields'] as $field) : ?>
                                    <div class="block">
                                        <span class="mb-1 block text-xs font-medium text-slate-600"><?php echo $h((string) $field['label']); ?></span>
                                        <code class="mb-2 block text-[11px] text-slate-400"><?php echo $h((string) $field['var']); ?></code>
                                        <div class="flex items-center gap-2">
                                            <input type="color" id="<?php echo $h((string) $field['field_id']); ?>_picker" data-fc-theme-var="<?php echo $h((string) $field['var']); ?>" value="<?php echo $h((string) $field['picker_value']); ?>" class="h-[33px] w-11 shrink-0 cursor-pointer rounded-[3px] border border-[#8c8f94] bg-white p-0.5" aria-label="<?php echo $h((string) $field['label']); ?> color picker" />
                                            <div class="fc-settings-field-input-wrap min-w-0 flex-1">
                                                <input type="text" id="<?php echo $h((string) $field['field_id']); ?>_hex" data-fc-theme-hex="<?php echo $h((string) $field['var']); ?>" value="<?php echo $h((string) $field['value']); ?>" maxlength="7" spellcheck="false" class="fc-settings-field font-mono uppercase" aria-label="<?php echo $h((string) $field['label']); ?> hex value" />
                                                <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo $h((string) $field['field_id']); ?>_hex" aria-label="Copy <?php echo $h((string) $field['label']); ?> hex" title="Copy to clipboard">
                                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                            <?php endforeach; ?>
                        </div>

                        <div id="fc-settings-panel-branding" class="<?php echo $h((string) $tab['panel_class']['branding']); ?>space-y-3">
                            <section class="border border-slate-200 bg-slate-50/60 p-3 sm:p-3.5">
                                <div class="grid grid-cols-1 gap-3">
                                    <?php foreach ($tab['branding_fields'] as $field) : ?>
                                    <?php if (($field['type'] ?? 'text') === 'image') : ?>
                                    <div class="fc-settings-branding-logo">
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">
                                            <span class="w-full shrink-0 text-sm font-medium text-slate-700 sm:w-28 sm:pt-2"><?php echo $h((string) $field['label']); ?></span>
                                            <span class="min-w-0 flex-1 space-y-2">
                                                <div class="fc-settings-branding-logo__inputs">
                                                    <input type="text" id="<?php echo $h((string) $field['field_id']); ?>" data-fc-branding-field="<?php echo $h((string) $field['key']); ?>" value="<?php echo $h((string) $field['value']); ?>" placeholder="<?php echo $h((string) $field['placeholder']); ?>" title="<?php echo $h((string) $field['title']); ?>" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" />
                                                    <button type="button" class="fc-settings-branding-logo__pick" data-fc-branding-pick title="Upload or choose <?php echo $h((string) $field['label']); ?>" aria-label="Upload or choose <?php echo $h((string) $field['label']); ?>"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                                    <button type="button" class="fc-settings-branding-logo__clear" data-fc-branding-clear title="Remove <?php echo $h((string) $field['label']); ?>" aria-label="Remove <?php echo $h((string) $field['label']); ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                                </div>
                                                <?php if ($field['help'] !== '') : ?>
                                                <span class="block text-xs text-slate-500"><?php echo $h((string) $field['help']); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php else : ?>
                                    <label class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3" for="<?php echo $h((string) $field['field_id']); ?>">
                                        <span class="w-full shrink-0 text-sm font-medium text-slate-700 sm:w-28"><?php echo $h((string) $field['label']); ?></span>
                                        <span class="min-w-0 flex-1">
                                            <input type="text" id="<?php echo $h((string) $field['field_id']); ?>" data-fc-branding-field="<?php echo $h((string) $field['key']); ?>" value="<?php echo $h((string) $field['value']); ?>" placeholder="<?php echo $h((string) $field['placeholder']); ?>" title="<?php echo $h((string) $field['title']); ?>" class="fc-settings-field" />
                                            <?php if ($field['help'] !== '') : ?>
                                            <span class="mt-1 block text-xs text-slate-500"><?php echo $h((string) $field['help']); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>

                        <div id="fc-settings-panel-fence-colors" class="<?php echo $h((string) $tab['panel_class']['fence_colors']); ?>fc-settings-fence-colors">
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
                                                <button type="button" class="fc-fs-kv-table__col fc-settings-fence-colors__sort-col" data-fc-fence-color-sort="<?php echo $h((string) $col['id']); ?>" aria-label="Sort by <?php echo $h((string) $col['label']); ?>">
                                                    <span><?php echo $h((string) $col['label']); ?></span>
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
                                                <div class="fc-fs-kv-row fc-fs-kv-row--table<?php echo $h((string) $row['row_class']); ?>" data-fc-fence-color-row="<?php echo (int) $row['index']; ?>">
                                                    <span class="fc-fs-kv-row__grip" data-fc-fence-color-grip role="button" tabindex="0" aria-label="Drag to reorder" title="Drag to reorder">
                                                        <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                                                    </span>
                                                    <span class="fc-settings-fence-colors__preview" data-fc-fence-color-preview="<?php echo (int) $row['index']; ?>" style="background:<?php echo $h((string) $row['bg']); ?>">
                                                        <?php if ($row['preview_url'] !== '') : ?>
                                                        <img src="<?php echo $h((string) $row['preview_url']); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </span>
                                                    <label class="fc-fs-gui-field fc-fs-kv-row__key">
                                                        <span class="fc-fs-gui-field__label">Slug</span>
                                                        <input type="text" class="fc-fs-input fc-fs-input--mono<?php echo !empty($row['is_original']) ? ' fc-fs-input--readonly' : ''; ?>" data-fc-fence-color-field="slug" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo $h((string) $row['slug']); ?>" spellcheck="false" placeholder="monument_matt" autocomplete="off"<?php echo !empty($row['is_original']) ? ' readonly aria-readonly="true" title="Original color slugs cannot be changed"' : ''; ?> />
                                                    </label>
                                                    <label class="fc-fs-gui-field">
                                                        <span class="fc-fs-gui-field__label">Label</span>
                                                        <input type="text" class="fc-fs-input" data-fc-fence-color-field="label" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo $h((string) $row['label']); ?>" placeholder="Black" autocomplete="off" />
                                                    </label>
                                                    <label class="fc-fs-gui-field">
                                                        <span class="fc-fs-gui-field__label">Sub label</span>
                                                        <input type="text" class="fc-fs-input" data-fc-fence-color-field="subLabel" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo $h((string) $row['sub_label']); ?>" placeholder="Satin" autocomplete="off" />
                                                    </label>
                                                    <div class="fc-fs-gui-field fc-settings-fence-colors__color-cell">
                                                        <span class="fc-fs-gui-field__label">Color</span>
                                                        <div class="fc-settings-fence-colors__color-inputs">
                                                            <input type="color" class="fc-settings-fence-colors__picker" data-fc-fence-color-picker="<?php echo (int) $row['index']; ?>" value="<?php echo $h((string) $row['picker_value']); ?>" aria-label="Color picker" />
                                                            <input type="text" id="fc-fence-color-hex-<?php echo (int) $row['index']; ?>" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="color" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo $h((string) $row['color']); ?>" spellcheck="false" placeholder="#6e6e6a" autocomplete="off" />
                                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-fence-color-hex-<?php echo (int) $row['index']; ?>" aria-label="Copy Color" title="Copy to clipboard">
                                                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="fc-fs-gui-field fc-settings-fence-colors__image-cell">
                                                        <span class="fc-fs-gui-field__label">Image</span>
                                                        <div class="fc-settings-fence-colors__image-inputs">
                                                            <input type="text" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="image" data-fc-fence-color-index="<?php echo (int) $row['index']; ?>" value="<?php echo $h((string) $row['image']); ?>" spellcheck="false" placeholder="public/assets/img/… or URL" autocomplete="off" />
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

                        <div id="fc-settings-panel-catalog" class="<?php echo $h((string) $tab['panel_class']['catalog']); ?>space-y-5">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-5 lg:items-stretch">
                                <section class="flex h-full flex-col border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-800">Sidebar branding</h3>
                                        <p class="mt-1 text-xs text-slate-500">Shown at the top of the Product Lookup filter sidebar.</p>
                                    </div>
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
                                </section>

                                <section class="flex h-full flex-col border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-800">Price range</h3>
                                        <p class="mt-1 text-xs text-slate-500">Default minimum and maximum price bounds for the lookup filter slider.</p>
                                    </div>
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
                                </section>

                                <section class="flex h-full flex-col border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-800">Default sorting</h3>
                                        <p class="mt-1 text-xs text-slate-500">Defaults used when a visitor opens Product Lookup without sort or page-size query params.</p>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-start">
                                        <label class="flex min-w-0 flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-700">Sort by</span>
                                            <select id="fc-catalog-defaultOrderby" data-fc-catalog-field="defaultOrderby" class="fc-settings-field">
                                                <?php foreach (($tab['catalog_orderby_choices'] ?? []) as $value => $label) : ?>
                                                <option value="<?php echo $h((string) $value); ?>"><?php echo $h((string) $label); ?></option>
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
                                </section>

                                <section class="flex h-full flex-col border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-800">Layout</h3>
                                        <p class="mt-1 text-xs text-slate-500">Grid columns by breakpoint (1–6).</p>
                                    </div>
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
                                </section>

                                <section class="flex h-full min-h-0 flex-col border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-sm font-semibold text-slate-800">Product categories</h3>
                                            <p class="mt-1 text-xs text-slate-500">Leave none selected to show all. Selecting any limits the lookup filter.</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2 shrink-0">
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-cats-all>Select all</button>
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-cats-none>Clear</button>
                                        </div>
                                    </div>
                                    <label class="fc-entries-page__search-wrap fc-catalog-filter-search">
                                        <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                                        <input type="search" id="fc-catalog-categories-search" class="fc-entries-page__search" data-fc-catalog-categories-search placeholder="Search product categories…" autocomplete="off" aria-label="Search product categories" />
                                    </label>
                                    <p id="fc-catalog-options-error" class="hidden text-sm text-amber-700"></p>
                                    <div id="fc-catalog-categories" class="fc-catalog-check-tree fc-catalog-options-list overflow-auto border border-slate-200 bg-white p-2.5"></div>
                                </section>

                                <section class="flex h-full min-h-0 flex-col border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-sm font-semibold text-slate-800">Attribute filters</h3>
                                            <p class="mt-1 text-xs text-slate-500">Leave none selected to show all. Selecting any limits which attributes appear.</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2 shrink-0">
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-attrs-all>Select all</button>
                                            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-catalog-attrs-none>Clear</button>
                                        </div>
                                    </div>
                                    <label class="fc-entries-page__search-wrap fc-catalog-filter-search">
                                        <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                                        <input type="search" id="fc-catalog-attributes-search" class="fc-entries-page__search" data-fc-catalog-attributes-search placeholder="Search attribute filters…" autocomplete="off" aria-label="Search attribute filters" />
                                    </label>
                                    <div id="fc-catalog-attributes" class="fc-catalog-check-list fc-catalog-options-list overflow-auto border border-slate-200 bg-white p-2.5"></div>
                                </section>
                            </div>

                            <p class="text-xs text-slate-500">
                                Opens on the public
                                <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../lookup" target="_blank" rel="noopener">Product Lookup</a>
                                page after save (refresh if already open).
                            </p>
                        </div>

                        <div id="fc-settings-panel-system" class="<?php echo $h((string) $tab['panel_class']['system']); ?>space-y-5">
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">Dashboard</h3>
                                    <p class="mt-1 text-xs text-slate-500">Default date range for dashboard charts when no <code>?date=</code> is in the URL.</p>
                                </div>
                                <label class="flex min-w-0 flex-col gap-1" for="fc-system-dashboardDefaultDatePeriod">
                                    <span class="text-sm font-medium text-slate-700">Default date range</span>
                                    <select id="fc-system-dashboardDefaultDatePeriod" data-fc-system-field="dashboardDefaultDatePeriod" class="fc-settings-field">
                                        <?php foreach (($tab['system_date_period_choices'] ?? []) as $value => $label) : ?>
                                        <option value="<?php echo $h((string) $value); ?>"><?php echo $h((string) $label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </section>

                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">Planner Entries</h3>
                                    <p class="mt-1 text-xs text-slate-500">Defaults used when opening Planner Entries with no date filter in the URL.</p>
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-system-entriesDefaultDatePeriod">
                                        <span class="text-sm font-medium text-slate-700">Default date range</span>
                                        <select id="fc-system-entriesDefaultDatePeriod" data-fc-system-field="entriesDefaultDatePeriod" class="fc-settings-field">
                                            <?php foreach (($tab['system_date_period_choices'] ?? []) as $value => $label) : ?>
                                            <option value="<?php echo $h((string) $value); ?>"><?php echo $h((string) $label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-system-entriesDefaultDateField">
                                        <span class="text-sm font-medium text-slate-700">Default date field</span>
                                        <select id="fc-system-entriesDefaultDateField" data-fc-system-field="entriesDefaultDateField" class="fc-settings-field">
                                            <?php foreach (($tab['system_date_field_choices'] ?? []) as $value => $label) : ?>
                                            <option value="<?php echo $h((string) $value); ?>"><?php echo $h((string) $label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                            </section>

                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">Date display</h3>
                                    <p class="mt-1 text-xs text-slate-500">How timestamps are shown across the admin (entries, dashboard, media library).</p>
                                </div>
                                <label class="flex min-w-0 flex-col gap-1" for="fc-system-dateFormat">
                                    <span class="text-sm font-medium text-slate-700">Date display format</span>
                                    <select id="fc-system-dateFormat" data-fc-system-field="dateFormat" class="fc-settings-field">
                                        <?php foreach (($tab['system_date_format_choices'] ?? []) as $value => $label) : ?>
                                        <option value="<?php echo $h((string) $value); ?>"><?php echo $h((string) $label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </section>

                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">Online presence</h3>
                                    <p class="mt-1 text-xs text-slate-500">How often activity is recorded and how online status is shown on the Users page.</p>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
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
                        <div id="fc-settings-panel-integration" class="<?php echo $h((string) $tab['panel_class']['integration']); ?>space-y-5">
                            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2 xl:items-stretch">
                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                <div class="mb-4">
                                    <h3 class="text-sm font-semibold text-slate-800">API keys</h3>
                                    <p class="mt-1 text-xs text-slate-500">Keys used by the planner for maps, chat, and Cloudflare cache purge.</p>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-googleMapsApiKey">
                                        <span class="text-sm font-medium text-slate-700">Google Maps API key</span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="password" id="fc-integration-googleMapsApiKey" data-fc-integration-field="googleMapsApiKey" value="<?php echo $h((string) ($integrations['googleMapsApiKey'] ?? '')); ?>" class="fc-settings-field font-mono" autocomplete="off" spellcheck="false" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-reveal="fc-integration-googleMapsApiKey" aria-label="Show Google Maps API key" title="Show or hide"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-googleMapsApiKey" aria-label="Copy Google Maps API key" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-chatraApiKey">
                                        <span class="text-sm font-medium text-slate-700">Chatra API key</span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="password" id="fc-integration-chatraApiKey" data-fc-integration-field="chatraApiKey" value="<?php echo $h((string) ($integrations['chatraApiKey'] ?? '')); ?>" class="fc-settings-field font-mono" autocomplete="off" spellcheck="false" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-reveal="fc-integration-chatraApiKey" aria-label="Show Chatra API key" title="Show or hide"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-chatraApiKey" aria-label="Copy Chatra API key" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1" for="fc-integration-cloudflareApiToken">
                                        <span class="text-sm font-medium text-slate-700">Cloudflare API token</span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="password" id="fc-integration-cloudflareApiToken" data-fc-integration-field="cloudflareApiToken" value="<?php echo $h((string) ($integrations['cloudflareApiToken'] ?? '')); ?>" class="fc-settings-field font-mono" autocomplete="off" spellcheck="false" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-reveal="fc-integration-cloudflareApiToken" aria-label="Show Cloudflare API token" title="Show or hide"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-cloudflareApiToken" aria-label="Copy Cloudflare API token" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </label>
                                </div>
                            </section>

                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                <div class="mb-4">
                                    <h3 class="text-sm font-semibold text-slate-800">Webhook</h3>
                                    <p class="mt-1 text-xs text-slate-500">Zapier webhook destination used by planner submissions.</p>
                                </div>
                                <label class="flex min-w-0 flex-col gap-1" for="fc-integration-webhookUrl">
                                    <span class="text-sm font-medium text-slate-700">Webhook URL</span>
                                    <span class="fc-settings-field-input-wrap">
                                        <input type="text" id="fc-integration-webhookUrl" data-fc-integration-field="webhookUrl" value="<?php echo $h((string) ($integrations['webhookUrl'] ?? '')); ?>" class="fc-settings-field font-mono text-xs" placeholder="https://hooks.zapier.com/hooks/catch/…" autocomplete="off" spellcheck="false" />
                                        <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-webhookUrl" aria-label="Copy webhook URL" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                    </span>
                                </label>
                            </section>
                            </div>

                            <div class="overflow-x-auto border border-slate-200 bg-white">
                                    <div class="grid min-w-[60rem] grid-cols-[minmax(11rem,1fr)_minmax(12rem,1.1fr)_minmax(6.5rem,0.55fr)_minmax(11rem,1fr)_minmax(11rem,1fr)_minmax(14rem,1.2fr)] border-b border-slate-200 bg-slate-100 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                        <span>Site</span><span>Logo</span><span>Supplier</span><span>Gtag ID</span><span>GTM ID</span><span>Cloudflare Zone ID</span>
                                    </div>
                                    <?php foreach (($integrations['sites'] ?? []) as $site) : ?>
                                    <?php
                                    $siteFieldId = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($site['key'] ?? 'site'));
                                    $siteSupplier = strtoupper(trim((string) ($site['supplier'] ?? '')));
                                    if ($siteSupplier !== 'GO' && $siteSupplier !== 'JG') {
                                        $siteSupplier = '';
                                    }
                                    ?>
                                    <div class="grid min-w-[60rem] grid-cols-[minmax(11rem,1fr)_minmax(12rem,1.1fr)_minmax(6.5rem,0.55fr)_minmax(11rem,1fr)_minmax(11rem,1fr)_minmax(14rem,1.2fr)] items-center gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0">
                                        <div class="min-w-0 flex items-center gap-2.5">
                                            <span class="fc-settings-site-logo shrink-0" data-fc-integration-site-logo-preview="<?php echo $h((string) ($site['key'] ?? '')); ?>">
                                                <?php if (!empty($site['logoUrl'])) : ?>
                                                <img src="<?php echo $h((string) $site['logoUrl']); ?>" alt="" loading="lazy" decoding="async" tabindex="0" role="button" data-fc-settings-image-view data-fc-settings-image-view-label="<?php echo $h((string) ($site['label'] ?? $site['key'] ?? '')); ?>" aria-label="View larger image for <?php echo $h((string) ($site['label'] ?? $site['key'] ?? '')); ?>">
                                                <?php endif; ?>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-slate-800"><?php echo $h((string) ($site['label'] ?? $site['key'] ?? '')); ?></span>
                                                <code class="block truncate text-[11px] text-slate-400"><?php echo $h((string) ($site['key'] ?? '')); ?></code>
                                            </span>
                                        </div>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo $h((string) $siteFieldId); ?>-logo" data-fc-integration-site="<?php echo $h((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="logo" value="<?php echo $h((string) ($site['logo'] ?? '')); ?>" class="fc-settings-field font-mono" placeholder="<?php echo $h((string) ($site['logoDefault'] ?? 'public/assets/img/… or URL')); ?>" autocomplete="off" spellcheck="false" aria-label="<?php echo $h((string) ($site['label'] ?? 'Site')); ?> logo" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-integration-site-logo-pick="<?php echo $h((string) ($site['key'] ?? '')); ?>" aria-label="Set <?php echo $h((string) ($site['label'] ?? 'Site')); ?> logo" title="Set logo"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                        </span>
                                        <label class="min-w-0">
                                            <span class="sr-only"><?php echo $h((string) ($site['label'] ?? 'Site')); ?> supplier</span>
                                            <select
                                                id="fc-integration-<?php echo $h((string) $siteFieldId); ?>-supplier"
                                                data-fc-integration-site="<?php echo $h((string) ($site['key'] ?? '')); ?>"
                                                data-fc-integration-site-field="supplier"
                                                class="fc-settings-field"
                                                aria-label="<?php echo $h((string) ($site['label'] ?? 'Site')); ?> supplier"
                                            >
                                                <option value="JG"<?php echo $siteSupplier === 'JG' ? ' selected' : ''; ?>>JG</option>
                                                <option value="GO"<?php echo $siteSupplier === 'GO' ? ' selected' : ''; ?>>GO</option>
                                            </select>
                                        </label>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo $h((string) $siteFieldId); ?>-gtag" data-fc-integration-site="<?php echo $h((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="gtagId" value="<?php echo $h((string) ($site['gtagId'] ?? '')); ?>" class="fc-settings-field font-mono uppercase" placeholder="AW-123456789" autocomplete="off" spellcheck="false" aria-label="<?php echo $h((string) ($site['label'] ?? 'Site')); ?> Gtag ID" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-<?php echo $h((string) $siteFieldId); ?>-gtag" aria-label="Copy <?php echo $h((string) ($site['label'] ?? 'Site')); ?> Gtag ID" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo $h((string) $siteFieldId); ?>-gtm" data-fc-integration-site="<?php echo $h((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="gtmId" value="<?php echo $h((string) ($site['gtmId'] ?? '')); ?>" class="fc-settings-field font-mono uppercase" placeholder="GTM-XXXXXXX" autocomplete="off" spellcheck="false" aria-label="<?php echo $h((string) ($site['label'] ?? 'Site')); ?> GTM ID" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-<?php echo $h((string) $siteFieldId); ?>-gtm" aria-label="Copy <?php echo $h((string) ($site['label'] ?? 'Site')); ?> GTM ID" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="fc-integration-<?php echo $h((string) $siteFieldId); ?>-cfzone" data-fc-integration-site="<?php echo $h((string) ($site['key'] ?? '')); ?>" data-fc-integration-site-field="cloudflareZoneId" value="<?php echo $h((string) ($site['cloudflareZoneId'] ?? '')); ?>" class="fc-settings-field font-mono" placeholder="32-char zone id" autocomplete="off" spellcheck="false" aria-label="<?php echo $h((string) ($site['label'] ?? 'Site')); ?> Cloudflare Zone ID" />
                                            <button type="button" class="fc-settings-field-copy fc-settings-field-verify" data-fc-cloudflare-verify data-fc-cloudflare-site="<?php echo $h((string) ($site['key'] ?? '')); ?>" data-fc-cloudflare-zone-for="fc-integration-<?php echo $h((string) $siteFieldId); ?>-cfzone" aria-label="Verify <?php echo $h((string) ($site['label'] ?? 'Site')); ?> Cloudflare Zone ID" title="Verify Cloudflare connection"><i class="fa-solid fa-plug" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="fc-integration-<?php echo $h((string) $siteFieldId); ?>-cfzone" aria-label="Copy <?php echo $h((string) ($site['label'] ?? 'Site')); ?> Cloudflare Zone ID" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                        </div>

                        <div id="fc-settings-panel-project-plan" class="<?php echo $h((string) $tab['panel_class']['project_plan']); ?>space-y-5">
                            <div class="overflow-x-auto border border-slate-200 bg-white">
                                <div class="grid min-w-[52rem] grid-cols-[1.5rem_2.5rem_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(12rem,1.4fr)_2.25rem] items-center gap-3 border-b border-slate-200 bg-slate-100 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    <span></span><span></span><span>Slug</span><span>Label</span><span>Image</span><span></span>
                                </div>
                                <div id="fc-project-plan-items">
                                    <?php foreach (($tab['project_plan_items'] ?? []) as $ppItem) : ?>
                                    <?php
                                    $ppIsOriginal = !empty($ppItem['isOriginal']);
                                    $ppKey = (string) ($ppItem['slug'] ?? '');
                                    $ppSlugId = 'fc-project-plan-item-' . $h($ppKey) . '-slug';
                                    $ppLabelId = 'fc-project-plan-item-' . $h($ppKey) . '-label';
                                    $ppImageId = 'fc-project-plan-item-' . $h($ppKey) . '-image';
                                    $ppViewLabel = (string) ($ppItem['label'] ?? '') !== '' ? (string) $ppItem['label'] : $ppKey;
                                    ?>
                                    <div class="grid min-w-[52rem] grid-cols-[1.5rem_2.5rem_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(12rem,1.4fr)_2.25rem] items-center gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0" data-fc-project-plan-row="<?php echo $h($ppKey); ?>">
                                        <span class="fc-project-plan-grip" data-fc-project-plan-grip role="button" tabindex="0" aria-label="Drag to reorder" title="Drag to reorder">
                                            <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                                        </span>
                                        <span class="fc-settings-site-logo shrink-0" data-fc-project-plan-item-preview="<?php echo $h($ppKey); ?>">
                                            <?php if (!empty($ppItem['imageUrl'])) : ?>
                                            <img src="<?php echo $h((string) $ppItem['imageUrl']); ?>" alt="" loading="lazy" decoding="async" tabindex="0" role="button" data-fc-settings-image-view data-fc-settings-image-view-label="<?php echo $h($ppViewLabel); ?>" aria-label="View larger image for <?php echo $h($ppViewLabel); ?>">
                                            <?php endif; ?>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <?php if ($ppIsOriginal) : ?>
                                            <input type="text" id="<?php echo $ppSlugId; ?>" value="<?php echo $h($ppKey); ?>" class="fc-settings-field font-mono" readonly aria-readonly="true" title="Original item slugs cannot be changed" aria-label="Slug" />
                                            <?php else : ?>
                                            <input type="text" id="<?php echo $ppSlugId; ?>" data-fc-project-plan-item="<?php echo $h($ppKey); ?>" data-fc-project-plan-item-field="slug" value="<?php echo $h($ppKey); ?>" class="fc-settings-field font-mono" spellcheck="false" autocomplete="off" placeholder="e.g. gate-opener" aria-label="Slug" />
                                            <?php endif; ?>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo $ppSlugId; ?>" aria-label="Copy Slug" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="<?php echo $ppLabelId; ?>" data-fc-project-plan-item="<?php echo $h($ppKey); ?>" data-fc-project-plan-item-field="label" value="<?php echo $h((string) ($ppItem['label'] ?? '')); ?>" class="fc-settings-field" aria-label="Label" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo $ppLabelId; ?>" aria-label="Copy Label" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <span class="fc-settings-field-input-wrap">
                                            <input type="text" id="<?php echo $ppImageId; ?>" data-fc-project-plan-item="<?php echo $h($ppKey); ?>" data-fc-project-plan-item-field="image" value="<?php echo $h((string) ($ppItem['image'] ?? '')); ?>" class="fc-settings-field font-mono" placeholder="<?php echo $h((string) ($ppItem['imageDefault'] ?? '')); ?>" autocomplete="off" spellcheck="false" aria-label="Image" />
                                            <button type="button" class="fc-settings-field-copy" data-fc-project-plan-item-pick="<?php echo $h($ppKey); ?>" title="Set image" aria-label="Set image"><i class="fa-solid fa-image" aria-hidden="true"></i></button>
                                            <button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="<?php echo $ppImageId; ?>" aria-label="Copy Image" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
                                        </span>
                                        <?php if ($ppIsOriginal) : ?>
                                        <span class="fc-project-plan-remove fc-project-plan-remove--disabled" aria-hidden="true" title="Original items cannot be removed">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </span>
                                        <?php else : ?>
                                        <button type="button" class="fc-project-plan-remove" data-fc-project-plan-item-remove="<?php echo $h($ppKey); ?>" title="Remove item" aria-label="Remove item"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="button" id="fc-project-plan-add" class="btn btn-sm btn-dark fw-semibold">
                                <i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Add item
                            </button>
                        </div>

                        <div id="fc-settings-panel-console" class="<?php echo $h((string) $tab['panel_class']['console']); ?>space-y-5">
                            <?php
                            $consoleSettings = is_array($tab['console'] ?? null) ? $tab['console'] : [];
                            $debugModeOn = !empty($consoleSettings['debugMode']);
                            $debugOffClass = $debugModeOn
                                ? 'text-slate-600 hover:text-slate-900'
                                : 'bg-white text-slate-900 shadow-sm';
                            $debugOnClass = $debugModeOn
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-600 hover:text-slate-900';
                            ?>
                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-semibold text-slate-800">Debug Mode</h3>
                                        <p class="mt-1 text-xs text-slate-500">When on, enables verbose debugging across the app. Off by default.</p>
                                    </div>
                                    <div class="flex flex-wrap rounded-lg bg-slate-200/80 p-1" role="group" aria-label="Debug Mode">
                                        <button
                                            type="button"
                                            data-fc-debug-mode="0"
                                            aria-pressed="<?php echo $debugModeOn ? 'false' : 'true'; ?>"
                                            class="rounded-md px-4 py-2 text-sm font-medium transition <?php echo $h($debugOffClass); ?>"
                                        >Off</button>
                                        <button
                                            type="button"
                                            data-fc-debug-mode="1"
                                            aria-pressed="<?php echo $debugModeOn ? 'true' : 'false'; ?>"
                                            class="rounded-md px-4 py-2 text-sm font-medium transition <?php echo $h($debugOnClass); ?>"
                                        >On</button>
                                    </div>
                                </div>
                            </section>

                            <section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">Console</h3>
                                    <p class="mt-1 text-xs text-slate-500">Type <code>git …</code> commands in the project root, or <code>help</code> / <code>clear</code> / <code>pwd</code>. Mutating git commands require confirmation.</p>
                                </div>
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
                            </section>
                        </div>
                    </div>

                    <div class="<?php echo $h((string) $tab['preview_hidden']); ?>sticky top-4 z-10 self-start" id="fc-settings-preview">
                        <?php if ($tab['preview_mode'] === 'theme') : ?>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="mb-3 text-sm font-semibold text-slate-800">Live preview</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-princeton-orange)">Primary button</span>
                                <span class="inline-flex items-center rounded-lg border-2 px-4 py-2 text-sm font-semibold" style="border-color:var(--fc-princeton-orange);color:var(--fc-princeton-orange)">Outline</span>
                                <span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-brand-primary)">Brand</span>
                                <span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-green)">Success</span>
                            </div>
                            <p class="mt-3 text-sm" style="color:var(--fc-dark-charcoal)">Body text uses dark charcoal.</p>
                            <p class="text-xs" style="color:var(--fc-dark-medium-gray)">Secondary label text.</p>
                            <div class="mt-3 rounded-lg border p-3" style="border-color:var(--fc-gray);background:var(--fc-bright-gray)">Surface panel</div>
                            <p class="mt-3 text-xs text-slate-500">Saved theme applies on the <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../planner" target="_blank" rel="noopener">planner</a> after save (refresh if already open).</p>
                        </div>
                        <?php elseif ($tab['preview_mode'] === 'branding') : ?>
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="mb-3 text-sm font-semibold text-slate-800">Live preview</p>
                            <div class="space-y-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-sm">
                                <div class="border-b border-slate-200 px-3 py-3">
                                    <div class="flex items-start gap-4">
                                        <div class="flex flex-col items-start">
                                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Logo</p>
                                            <div id="fc-branding-preview-logo" class="fc-settings-branding-logo__preview fc-settings-branding-logo__preview--sidebar<?php echo ($tab['branding_preview']['logo_url'] ?? '') !== '' ? '' : ' fc-settings-branding-logo__preview--empty'; ?>"<?php echo ($tab['branding_preview']['logo_url'] ?? '') !== '' ? ' style="--fc-branding-logo-preview:url(' . $h((string) $tab['branding_preview']['logo_url']) . ');width:48px;height:48px;"' : ' style="width:48px;height:48px;"'; ?>>
                                                <span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-border-all"></i></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-start">
                                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Favicon</p>
                                            <div id="fc-branding-preview-favicon" class="fc-settings-branding-logo__preview fc-settings-branding-logo__preview--sidebar"<?php echo ($tab['branding_preview']['favicon_url'] ?? '') !== '' ? ' style="--fc-branding-logo-preview:url(' . $h((string) $tab['branding_preview']['favicon_url']) . ')"' : ''; ?>>
                                                <span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-b border-slate-200 px-3 py-3">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">App name</p>
                                    <p id="fc-branding-preview-title" class="truncate font-bold leading-snug text-slate-900"><?php echo $h((string) $tab['branding_preview']['app_name']); ?></p>
                                </div>
                                <div class="border-b border-slate-200 px-3 py-3">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tagline</p>
                                    <p id="fc-branding-preview-tagline" class="leading-snug text-slate-600"><?php echo $h((string) $tab['branding_preview']['tagline']); ?></p>
                                </div>
                                <div class="px-3 py-3">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Footer</p>
                                    <p id="fc-branding-preview-footer" class="truncate text-xs text-slate-500">
                                        <span id="fc-branding-preview-footer-name"><?php echo $h((string) $tab['branding_preview']['app_name']); ?></span>
                                        <span id="fc-branding-preview-version"><?php echo $h((string) $tab['branding_preview']['version']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-slate-500">Saved branding applies on the <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../planner" target="_blank" rel="noopener">planner</a> after save (refresh if already open).</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
