<?php
/**
 * FC Admin — Changelogs (server-rendered list, plus the View dialog versions.js opens). No
 * write actions: writable/versions.csv ships with the code, and a site that edited its copy
 * could no longer git pull.
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
                                <?php if ($page['empty_state']['show_clear']) : ?>
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
                            <button
                                type="button"
                                class="btn btn-sm btn-light"
                                data-fc-versions-action="view"
                                data-id="<?php echo (int) $row['id']; ?>"
                                aria-label="<?php echo e('View ' . $row['row_label']); ?>"
                            >
                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                <span>View</span>
                            </button>
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
            </footer>
        </section>
    </div>
</div>
