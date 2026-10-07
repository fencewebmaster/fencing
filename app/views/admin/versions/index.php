<?php
/**
 * FC Admin — Releases (server-rendered list with a row Actions menu, plus the editor and
 * viewer dialogs versions.js opens).
 *
 * Read-only template: VersionPresenter::listViewData() shapes every value and the JS
 * bootstrap. Escaping via the global e()/cell() helpers; the layout's is_array() check
 * is the render gate.
 *
 * @var array<string, mixed> $fcVersionManagerPage
 */

$page = $fcVersionManagerPage;
$req  = $page['request'];
$cols = $page['columns'];
?>
<div class="fc-entries-page fc-versions-page" data-fc-version-manager>
    <script type="application/json" id="fc-versions-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <nav class="fc-entries-page__tabs fc-entries-page__tabs--group" aria-label="Versions by type">
        <div class="btn-group">
            <?php foreach ($page['tabs'] as $tab) : ?>
            <a
                class="btn btn-sm btn-light"
                href="<?php echo e((string) $tab['href']); ?>"
                <?php echo $tab['is_active'] ? 'aria-current="page"' : ''; ?>
            >
                <span><?php echo e((string) $tab['label']); ?></span>
                <span class="fc-btn-count"><?php echo number_format((int) $tab['count']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="fc-entries-page__toolbar">
        <form class="fc-entries-page__toolbar-form" method="get" action="<?php echo e((string) $page['form_action']); ?>">
            <?php foreach ($page['hidden_fields'] as $field) : ?>
            <input type="hidden" name="<?php echo e($field['name']); ?>" value="<?php echo e($field['value']); ?>">
            <?php endforeach; ?>
            <div class="fc-entries-page__toolbar-row fc-versions-toolbar">
                <div class="fc-entries-page__search-group">
                    <label class="fc-entries-page__search-wrap">
                        <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                        <span class="sr-only">Search versions</span>
                        <input
                            type="search"
                            name="q"
                            class="fc-entries-page__search"
                            placeholder="Search version or release notes…"
                            value="<?php echo e((string) $req['q']); ?>"
                            maxlength="100"
                            autocomplete="off"
                        >
                    </label>
                    <label class="fc-versions-toolbar__filter">
                        <span class="sr-only">Version level</span>
                        <select class="fc-entries-page__filter" name="level" onchange="this.form.submit()">
                            <?php foreach ($page['level_options'] as $option) : ?>
                            <option value="<?php echo e($option['value']); ?>"<?php echo $option['is_selected'] ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="fc-versions-toolbar__filter">
                        <span class="sr-only">Status</span>
                        <select class="fc-entries-page__filter" name="status" onchange="this.form.submit()">
                            <?php foreach ($page['status_options'] as $option) : ?>
                            <option value="<?php echo e($option['value']); ?>"<?php echo $option['is_selected'] ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php if ($page['has_active_filters']) : ?>
                    <a class="btn btn-sm btn-light" href="<?php echo e((string) $page['clear_filters_url']); ?>">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span>Clear Filters</span>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="fc-versions-toolbar__actions">
                    <a
                        class="btn btn-sm btn-light"
                        href="<?php echo e((string) $page['public_url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        <span>Public Page</span>
                    </a>
                    <?php if ($page['can_edit']) : ?>
                    <button type="button" class="btn btn-sm btn-orange fw-semibold" data-fc-versions-add>
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Add Release</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if ($page['error'] !== '') : ?>
    <div class="fc-entries-error">
        <p class="fc-entries-error__title">Could not read the version history</p>
        <p><?php echo e((string) $page['error']); ?></p>
    </div>
    <?php endif; ?>

    <div class="fc-entries-page__content">
        <div class="fc-entries-table-wrap">
            <table class="fc-entries-table fc-versions-table">
                <thead>
                    <tr>
                        <th scope="col" aria-sort="<?php echo e($cols['version']['aria_sort']); ?>">
                            <a class="fc-versions-sort<?php echo $cols['version']['is_active'] ? ' is-active' : ''; ?>" href="<?php echo e($cols['version']['href']); ?>">
                                <span>Version</span><i class="<?php echo e($cols['version']['icon']); ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" aria-sort="<?php echo e($cols['type']['aria_sort']); ?>">
                            <a class="fc-versions-sort<?php echo $cols['type']['is_active'] ? ' is-active' : ''; ?>" href="<?php echo e($cols['type']['href']); ?>">
                                <span>Type</span><i class="<?php echo e($cols['type']['icon']); ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" aria-sort="<?php echo e($cols['date']['aria_sort']); ?>">
                            <a class="fc-versions-sort<?php echo $cols['date']['is_active'] ? ' is-active' : ''; ?>" href="<?php echo e($cols['date']['href']); ?>">
                                <span>Release date</span><i class="<?php echo e($cols['date']['icon']); ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" aria-sort="<?php echo e($cols['level']['aria_sort']); ?>">
                            <a class="fc-versions-sort<?php echo $cols['level']['is_active'] ? ' is-active' : ''; ?>" href="<?php echo e($cols['level']['href']); ?>">
                                <span>Level</span><i class="<?php echo e($cols['level']['icon']); ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" class="fc-versions-table__desc-col">Description</th>
                        <th scope="col" aria-sort="<?php echo e($cols['status']['aria_sort']); ?>">
                            <a class="fc-versions-sort<?php echo $cols['status']['is_active'] ? ' is-active' : ''; ?>" href="<?php echo e($cols['status']['href']); ?>">
                                <span>Status</span><i class="<?php echo e($cols['status']['icon']); ?>" aria-hidden="true"></i>
                            </a>
                        </th>
                        <th scope="col" class="fc-versions-table__actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$page['has_table_rows']) : ?>
                    <tr>
                        <td colspan="7" class="fc-versions-empty">
                            <div class="fc-versions-empty__inner">
                                <span class="fc-versions-empty__icon" aria-hidden="true"><i class="fa-solid fa-code-branch"></i></span>
                                <p class="fc-versions-empty__title"><?php echo e((string) $page['empty_state']['title']); ?></p>
                                <p class="fc-versions-empty__text"><?php echo e((string) $page['empty_state']['text']); ?></p>
                                <?php if ($page['empty_state']['show_add']) : ?>
                                <button type="button" class="btn btn-sm btn-orange fw-semibold" data-fc-versions-add>
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    <span>Add Release</span>
                                </button>
                                <?php elseif ($page['empty_state']['show_clear']) : ?>
                                <a class="btn btn-sm btn-light" href="<?php echo e((string) $page['clear_filters_url']); ?>">Clear Filters</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else : ?>
                    <?php foreach ($page['table_rows'] as $row) : ?>
                    <tr class="fc-entries-table__row fc-versions-table__row" data-fc-versions-row="<?php echo (int) $row['id']; ?>">
                        <td class="fc-versions-table__version">
                            <button
                                type="button"
                                class="fc-versions-version"
                                data-fc-versions-action="view"
                                data-id="<?php echo (int) $row['id']; ?>"
                                aria-label="<?php echo e('View ' . $row['row_label']); ?>"
                            ><?php echo e((string) $row['version_label']); ?></button>
                            <span class="fc-versions-mode"><?php echo cell($row['mode_label']); ?></span>
                        </td>
                        <td>
                            <span class="fc-versions-type fc-versions-type--<?php echo e((string) $row['type']); ?>"><?php echo cell($row['type_label']); ?></span>
                        </td>
                        <td class="fc-versions-table__date">
                            <time datetime="<?php echo e((string) $row['date_title']); ?>"><?php echo cell($row['date_label']); ?></time>
                        </td>
                        <td>
                            <span class="fc-versions-level fc-versions-level--<?php echo e((string) $row['level']); ?>"><?php echo cell($row['level_label']); ?></span>
                        </td>
                        <td class="fc-versions-table__desc-col">
                            <span class="fc-versions-excerpt"><?php echo cell($row['excerpt']); ?></span>
                        </td>
                        <td>
                            <span class="fc-versions-status<?php echo $row['is_published'] ? ' fc-versions-status--published' : ' fc-versions-status--draft'; ?>"><?php echo e((string) $row['status_label']); ?></span>
                        </td>
                        <td class="fc-versions-table__actions-col">
                            <div class="fc-versions-row-menu" data-fc-versions-menu>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light fc-versions-row-menu__toggle"
                                    data-fc-versions-menu-toggle
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                    aria-label="<?php echo e($row['row_label'] . ' actions'); ?>"
                                >
                                    <span>Actions</span>
                                    <span class="btn-caret" aria-hidden="true"><i class="fa-solid fa-chevron-down fc-products-download-dropdown__caret"></i></span>
                                </button>
                                <div class="fc-products-download-dropdown__panel fc-admin-menu__panel" role="menu" aria-label="<?php echo e($row['row_label'] . ' actions'); ?>" hidden>
                                    <div class="fc-admin-menu__head">
                                        <span class="fc-admin-menu__head-title"><?php echo e((string) $row['row_label']); ?></span>
                                        <span class="fc-admin-menu__head-hint"><?php echo e((string) $row['menu_hint']); ?></span>
                                    </div>
                                    <div class="fc-admin-menu__group">
                                        <button type="button" class="fc-products-download-dropdown__option fc-admin-menu__option" role="menuitem" data-fc-versions-action="view" data-id="<?php echo (int) $row['id']; ?>">
                                            <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-regular fa-eye"></i></span>
                                            <span class="fc-admin-menu__option-text">
                                                <span class="fc-admin-menu__option-label">View</span>
                                                <span class="fc-admin-menu__option-meta">Read the release notes</span>
                                            </span>
                                        </button>
                                        <?php if ($page['can_edit']) : ?>
                                        <button type="button" class="fc-products-download-dropdown__option fc-admin-menu__option" role="menuitem" data-fc-versions-action="edit" data-id="<?php echo (int) $row['id']; ?>">
                                            <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                            <span class="fc-admin-menu__option-text">
                                                <span class="fc-admin-menu__option-label">Edit</span>
                                                <span class="fc-admin-menu__option-meta">Change the number, date or release notes</span>
                                            </span>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($page['can_publish']) : ?>
                                        <button type="button" class="fc-products-download-dropdown__option fc-admin-menu__option" role="menuitem" data-fc-versions-action="<?php echo e((string) $row['publish_action']); ?>" data-id="<?php echo (int) $row['id']; ?>">
                                            <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="<?php echo e((string) $row['publish_icon']); ?>"></i></span>
                                            <span class="fc-admin-menu__option-text">
                                                <span class="fc-admin-menu__option-label"><?php echo e((string) $row['publish_label']); ?></span>
                                                <span class="fc-admin-menu__option-meta"><?php echo e((string) $row['publish_meta']); ?></span>
                                            </span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($page['can_delete']) : ?>
                                    <div class="fc-admin-menu__divider" role="separator"></div>
                                    <div class="fc-admin-menu__group">
                                        <button type="button" class="fc-products-download-dropdown__option fc-admin-menu__option fc-admin-menu__option--danger" role="menuitem" data-fc-versions-action="delete" data-id="<?php echo (int) $row['id']; ?>">
                                            <span class="fc-admin-menu__option-icon" aria-hidden="true"><i class="fa-regular fa-trash-can"></i></span>
                                            <span class="fc-admin-menu__option-text">
                                                <span class="fc-admin-menu__option-label">Delete</span>
                                                <span class="fc-admin-menu__option-meta">Remove it from the history for good</span>
                                            </span>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="fc-entries-page__footer">
        <div class="fc-entries-page__footer-row">
            <div class="fc-entries-page__count"><?php echo e((string) $page['count_label']); ?></div>

            <form class="fc-entries-page__per-page" method="get" action="<?php echo e((string) $page['form_action']); ?>">
                <?php foreach ($page['per_page_hidden_fields'] as $field) : ?>
                <input type="hidden" name="<?php echo e($field['name']); ?>" value="<?php echo e($field['value']); ?>">
                <?php endforeach; ?>
                <span class="fc-entries-page__per-page-label">Display per page</span>
                <select class="fc-entries-page__per-page-select" name="per_page" aria-label="Display per page" onchange="this.form.submit()">
                    <?php foreach ($page['per_page_options'] as $option) : ?>
                    <option value="<?php echo (int) $option; ?>"<?php echo (string) $page['per_page_value'] === (string) $option ? ' selected' : ''; ?>><?php echo (int) $option; ?></option>
                    <?php endforeach; ?>
                    <option value="all"<?php echo (string) $page['per_page_value'] === 'all' ? ' selected' : ''; ?>>All</option>
                </select>
            </form>

            <?php view('admin.partials.pagination', ['fcPagination' => $page['pagination'], 'fcPaginationLinks' => $page['pagination_links'], 'fcPaginationLabel' => 'Versions pagination']); ?>
        </div>
    </footer>

    <div class="fc-versions-dialog" data-fc-versions-viewer hidden>
        <div class="fc-versions-dialog__backdrop" data-fc-versions-close aria-hidden="true"></div>
        <section class="fc-versions-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="fc-versions-viewer-title" tabindex="-1">
            <button type="button" class="fencing-modal-close" data-fc-versions-close aria-label="Close"></button>
            <header class="fc-versions-dialog__header">
                <div class="fc-versions-viewer__badges" data-fc-versions-viewer-badges></div>
                <h2 id="fc-versions-viewer-title" class="fc-versions-dialog__title" data-fc-versions-viewer-title></h2>
                <p class="fc-versions-dialog__subtitle" data-fc-versions-viewer-meta></p>
            </header>
            <div class="fc-versions-dialog__body">
                <div class="fc-release-notes" data-fc-versions-viewer-notes></div>
                <dl class="fc-versions-viewer__stamps">
                    <div><dt>Version number</dt><dd data-fc-versions-viewer-mode></dd></div>
                    <div><dt>Created</dt><dd data-fc-versions-viewer-created></dd></div>
                    <div><dt>Last updated</dt><dd data-fc-versions-viewer-updated></dd></div>
                </dl>
            </div>
            <footer class="fc-versions-dialog__footer">
                <button type="button" class="btn btn-sm btn-light" data-fc-versions-close>Close</button>
                <?php if ($page['can_edit']) : ?>
                <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-versions-viewer-edit>
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    <span>Edit</span>
                </button>
                <?php endif; ?>
            </footer>
        </section>
    </div>

    <?php if ($page['can_edit']) : ?>
    <div class="fc-versions-dialog" data-fc-versions-editor hidden>
        <div class="fc-versions-dialog__backdrop" data-fc-versions-close aria-hidden="true"></div>
        <section class="fc-versions-dialog__panel fc-versions-dialog__panel--wide" role="dialog" aria-modal="true" aria-labelledby="fc-versions-editor-title" tabindex="-1">
            <button type="button" class="fencing-modal-close" data-fc-versions-close aria-label="Close"></button>
            <header class="fc-versions-dialog__header">
                <h2 id="fc-versions-editor-title" class="fc-versions-dialog__title" data-fc-versions-editor-title>Add Release</h2>
                <p class="fc-versions-dialog__subtitle">Saved to the version history (writable/versions.csv).</p>
            </header>
            <form class="fc-versions-form" data-fc-versions-form novalidate>
                <div class="fc-versions-dialog__body">
                    <div class="fc-versions-form__alert" data-fc-versions-form-alert role="alert" hidden></div>

                    <div class="fc-versions-form__grid">
                        <div class="fc-versions-field">
                            <label class="fc-versions-field__label" for="fc-versions-type">Version type</label>
                            <select id="fc-versions-type" class="fc-versions-input" name="type" data-fc-versions-input="type">
                                <?php foreach ($page['type_options'] as $option) : ?>
                                <option value="<?php echo e($option['value']); ?>"<?php echo $option['is_selected'] ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="fc-versions-field__error" data-fc-versions-error="type" hidden></p>
                        </div>
                        <div class="fc-versions-field">
                            <label class="fc-versions-field__label" for="fc-versions-level">Version level</label>
                            <select id="fc-versions-level" class="fc-versions-input" name="version_level" data-fc-versions-input="version_level">
                                <?php foreach ($page['editor_level_options'] as $option) : ?>
                                <option value="<?php echo e($option['value']); ?>"<?php echo $option['is_selected'] ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="fc-versions-field__error" data-fc-versions-error="version_level" hidden></p>
                        </div>
                        <div class="fc-versions-field">
                            <label class="fc-versions-field__label" for="fc-versions-date">Release date &amp; time</label>
                            <input id="fc-versions-date" class="fc-versions-input" type="datetime-local" step="60" name="date_time" data-fc-versions-input="date_time" required>
                            <p class="fc-versions-field__error" data-fc-versions-error="date_time" hidden></p>
                        </div>
                    </div>

                    <fieldset class="fc-versions-number">
                        <legend class="fc-versions-field__label">Version number</legend>
                        <div class="fc-versions-number__row">
                            <div class="btn-group fc-versions-number__modes" role="group" aria-label="How the version number is set">
                                <button type="button" class="btn btn-sm btn-light" data-fc-versions-mode="automatic" aria-pressed="true">
                                    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                    <span>Automatic</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-light" data-fc-versions-mode="manual" aria-pressed="false">
                                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i>
                                    <span>Manual</span>
                                </button>
                            </div>
                            <div class="fc-versions-number__auto" data-fc-versions-auto>
                                <span class="fc-versions-number__value" data-fc-versions-auto-value>…</span>
                                <span class="fc-versions-number__hint" data-fc-versions-auto-hint></span>
                            </div>
                            <div class="fc-versions-number__manual" data-fc-versions-manual hidden>
                                <label class="sr-only" for="fc-versions-version">Version number (MAJOR.MINOR.PATCH)</label>
                                <input
                                    id="fc-versions-version"
                                    class="fc-versions-input fc-versions-input--mono"
                                    type="text"
                                    name="version"
                                    data-fc-versions-input="version"
                                    placeholder="1.4.0"
                                    maxlength="32"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    spellcheck="false"
                                >
                                <span class="fc-versions-number__hint" data-fc-versions-manual-hint>MAJOR.MINOR.PATCH, for example 1.4.0</span>
                            </div>
                        </div>
                        <p class="fc-versions-field__error" data-fc-versions-error="version" hidden></p>
                        <p class="fc-versions-field__error" data-fc-versions-error="version_mode" hidden></p>
                    </fieldset>

                    <div class="fc-versions-field fc-versions-field--notes">
                        <label class="fc-versions-field__label" for="fc-versions-description">Release notes</label>
                        <textarea
                            id="fc-versions-description"
                            class="fc-versions-input fc-versions-input--notes"
                            name="description"
                            rows="12"
                            data-fc-wysiwyg="release-notes"
                            data-fc-versions-input="description"
                        ></textarea>
                        <p class="fc-versions-field__error" data-fc-versions-error="description" hidden></p>
                    </div>

                    <?php if ($page['can_publish']) : ?>
                    <div class="fc-versions-publish">
                        <span class="fc-versions-field__label" id="fc-versions-published-label">Published</span>
                        <div class="fc-mode-switch" role="group" aria-labelledby="fc-versions-published-label">
                            <button type="button" class="fc-mode-switch__side" data-fc-versions-published="0" aria-pressed="true">No</button>
                            <button
                                type="button"
                                class="fc-mode-switch__track"
                                role="switch"
                                aria-checked="false"
                                aria-labelledby="fc-versions-published-label"
                                data-fc-versions-input="published"
                            ><span class="fc-mode-switch__thumb"></span></button>
                            <button type="button" class="fc-mode-switch__side" data-fc-versions-published="1" aria-pressed="false">Yes</button>
                        </div>
                        <p class="fc-versions-publish__hint">Only published versions appear on the public version history page.</p>
                    </div>
                    <?php else : ?>
                    <p class="fc-versions-publish__hint">You can save this version, but publishing it needs the Publish/Unpublish permission.</p>
                    <?php endif; ?>
                </div>
                <footer class="fc-versions-dialog__footer">
                    <button type="button" class="btn btn-sm btn-light" data-fc-versions-close>Cancel</button>
                    <button type="submit" class="btn btn-sm btn-orange fw-semibold" data-fc-versions-save>
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        <span>Save Version</span>
                    </button>
                </footer>
            </form>
        </section>
    </div>
    <?php endif; ?>
</div>
