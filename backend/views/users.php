<?php
/**
 * FC Admin — Users list (server-rendered).
 *
 * @var array<string, mixed> $fcUsersPage
 */

declare(strict_types=1);

if (!isset($fcUsersPage) || !is_array($fcUsersPage)) {
    return;
}

$h = 'fc_users_admin_h';
$cell = 'fc_users_admin_cell';
$page = $fcUsersPage;
$req = is_array($page['request'] ?? null) ? $page['request'] : [];
$activeRole = trim((string) ($req['role'] ?? ''));
?>
<div class="fc-entries-page" data-fc-users-list>
    <nav class="fc-entries-page__tabs" aria-label="Users by role">
        <?php foreach (($page['tabs'] ?? []) as $tab) : ?>
        <a
            class="fc-entries-page__tab<?php echo !empty($tab['is_active']) ? ' is-active' : ''; ?>"
            href="<?php echo $h((string) ($tab['href'] ?? '#')); ?>"
            <?php echo !empty($tab['is_active']) ? 'aria-current="page"' : ''; ?>
        >
            <span><?php echo $h((string) ($tab['label'] ?? '')); ?></span>
            <span class="fc-entries-page__tab-count"><?php echo number_format((int) ($tab['count'] ?? 0)); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="fc-entries-page__toolbar">
        <form class="fc-entries-page__toolbar-form" method="get" action="<?php echo $h((string) $page['form_action']); ?>">
            <div class="fc-entries-page__toolbar-row">
                <div class="fc-entries-page__search-group">
                    <label class="fc-entries-page__search-wrap">
                        <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                        <input
                            type="search"
                            name="q"
                            class="fc-entries-page__search"
                            placeholder="Search username, email, display name…"
                            value="<?php echo $h((string) ($req['q'] ?? '')); ?>"
                            autocomplete="off"
                        >
                    </label>
                    <?php if (!empty($page['has_active_filters'])) : ?>
                    <a
                        class="btn btn-sm btn-dark fw-semibold fc-entries-clear-filters"
                        href="<?php echo $h((string) ($page['clear_filters_url'] ?? '')); ?>"
                    >
                        <span>Clear search</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if ($activeRole !== '') : ?>
                <input type="hidden" name="role" value="<?php echo $h($activeRole); ?>">
                <?php endif; ?>
                <?php if (!empty($page['show_per_page_hidden'])) : ?>
                <input type="hidden" name="per_page" value="<?php echo $h((string) ($req['per_page'] ?? '')); ?>">
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (!empty($page['active_role_needs_permissions']) && ($page['active_role_set_permissions_url'] ?? '') !== '') : ?>
    <div class="fc-users-permissions-notice" role="status">
        <p>
            <strong><?php echo $h((string) ($page['active_role_label'] ?? 'This role')); ?></strong>
            has no FC admin access yet.
        </p>
        <a
            class="btn btn-sm btn-orange fw-semibold"
            href="<?php echo $h((string) $page['active_role_set_permissions_url']); ?>"
            data-nav-full="1"
            data-route="users/group-permissions"
            data-title="Group Permissions"
        >Set Permission</a>
    </div>
    <?php endif; ?>

    <?php if (($page['error'] ?? '') !== '') : ?>
    <div class="fc-entries-error">
        <p class="fc-entries-error__title">Could not load users</p>
        <p><?php echo $h((string) $page['error']); ?></p>
    </div>
    <?php endif; ?>

    <div class="fc-entries-page__content">
        <div class="fc-entries-table-wrap">
            <table class="fc-entries-table fc-users-table">
                <thead>
                    <tr>
                        <th scope="col" class="fc-users-table__avatar-col"><span class="sr-only">Avatar</span></th>
                        <th scope="col">Username</th>
                        <th scope="col">Display name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($page['has_table_rows'])) : ?>
                    <tr>
                        <td colspan="6" class="fc-entries-empty">No users found.</td>
                    </tr>
                    <?php else : ?>
                    <?php foreach ($page['table_rows'] as $row) : ?>
                    <tr class="fc-entries-table__row fc-users-table__row">
                        <td class="fc-users-table__avatar-cell">
                            <img
                                class="fc-users-table__avatar"
                                src="<?php echo $h((string) ($row['avatar_url'] ?? '')); ?>"
                                alt=""
                                width="32"
                                height="32"
                                loading="lazy"
                                decoding="async"
                            >
                        </td>
                        <td class="fc-users-table__username">
                            <div class="fc-users-table__username-main fc-entries-table__truncate"><?php echo $cell($row['user_login'] ?? ''); ?></div>
                            <div class="fc-users-table__row-actions">
                                <?php if (!empty($row['needs_permissions']) && ($row['set_permissions_url'] ?? '') !== '') : ?>
                                <a
                                    class="fc-users-table__action"
                                    href="<?php echo $h((string) $row['set_permissions_url']); ?>"
                                    data-nav-full="1"
                                    data-route="users/group-permissions"
                                    data-title="Group Permissions"
                                >Set Group Permissions</a>
                                <?php elseif (!empty($row['can_login_as']) && ($row['login_as_url'] ?? '') !== '') : ?>
                                <a class="fc-users-table__action" href="<?php echo $h((string) $row['login_as_url']); ?>">Login As</a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="fc-entries-table__truncate"><?php echo $cell($row['display_name'] ?? ''); ?></td>
                        <td class="fc-entries-table__truncate fc-entries-table__truncate--wide">
                            <?php
                            $email = trim((string) ($row['user_email'] ?? ''));
                            if ($email !== '') :
                            ?>
                            <a class="fc-entries-row-link" href="mailto:<?php echo $h($email); ?>"><?php echo $cell($email); ?></a>
                            <?php else : ?>
                            <?php echo $cell(''); ?>
                            <?php endif; ?>
                        </td>
                        <td class="fc-entries-table__truncate"><?php echo $cell($row['role_label'] ?? ''); ?></td>
                        <td class="fc-entries-table__truncate"><?php echo $cell($row['registered_at'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="fc-entries-page__footer">
        <div class="fc-entries-page__footer-row">
            <div class="fc-entries-page__count"><?php echo $h((string) ($page['count_label'] ?? '')); ?></div>

            <form class="fc-entries-page__per-page" method="get" action="<?php echo $h((string) $page['form_action']); ?>">
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
            <nav class="fc-entries-page__pagination" aria-label="Users pagination">
                <?php if (($page['pagination']['prev_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo $h((string) $page['pagination']['prev_url']); ?>" aria-label="Previous page">&lsaquo;</a>
                <?php endif; ?>

                <?php foreach ($page['pagination_links'] as $paginationLink) : ?>
                    <?php if (($paginationLink['type'] ?? '') === 'ellipsis') : ?>
                <span class="fc-entries-pagination__ellipsis" aria-hidden="true">…</span>
                    <?php elseif (($paginationLink['type'] ?? '') === 'current') : ?>
                <span class="fc-entries-pagination__btn fc-entries-pagination__btn--active" aria-current="page"><?php echo $h((string) ($paginationLink['label'] ?? '')); ?></span>
                    <?php else : ?>
                <a class="fc-entries-pagination__btn" href="<?php echo $h((string) ($paginationLink['url'] ?? '')); ?>"><?php echo $h((string) ($paginationLink['label'] ?? '')); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (($page['pagination']['next_url'] ?? '') !== '') : ?>
                <a class="fc-entries-pagination__btn fc-entries-pagination__btn--nav" href="<?php echo $h((string) $page['pagination']['next_url']); ?>" aria-label="Next page">&rsaquo;</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </footer>
</div>
