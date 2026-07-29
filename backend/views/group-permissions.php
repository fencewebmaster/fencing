<?php
/**
 * FC Admin — Group Permissions editor.
 *
 * @var array<string, mixed> $fcGroupPermissionsPage
 */

declare(strict_types=1);

if (!isset($fcGroupPermissionsPage) || !is_array($fcGroupPermissionsPage)) {
    return;
}

$h = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$page = $fcGroupPermissionsPage;
$roles = is_array($page['roles'] ?? null) ? $page['roles'] : [];
$tree = is_array($page['tree'] ?? null) ? $page['tree'] : [];
$selected = (string) ($page['selected_role'] ?? '');
$isLocked = !empty($page['is_locked']);
$lockNotice = (string) ($page['lock_notice'] ?? '');
$bootstrap = [
    'roles' => $roles,
    'selectedRole' => $selected,
    'isSuperAdminRole' => !empty($page['is_super_admin_role']),
    'isAdministratorRole' => !empty($page['is_administrator_role']),
    'isLocked' => $isLocked,
    'canEdit' => !empty($page['can_edit']),
    'lockNotice' => $lockNotice,
    'tree' => $tree,
    'permissions' => $page['permissions'] ?? [],
    'csrf' => (string) ($page['csrf'] ?? ''),
    'apiUrl' => (string) ($page['api_url'] ?? 'api.php?module=groupPermissions'),
];
?>
<div
    class="fc-gp-page"
    data-fc-group-permissions
    id="fc-group-permissions-root"
>
    <script type="application/json" id="fc-gp-bootstrap"><?php echo json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?></script>

    <div class="fc-entries-page__notice" data-fc-gp-notice hidden role="status" aria-live="polite"></div>

    <header class="fc-gp-page__header">
        <div>
            <h2 class="fc-gp-page__title">Group Permissions</h2>
            <p class="fc-gp-page__subtitle">Assign backend module access for each user group role.</p>
        </div>
        <div class="fc-gp-page__header-actions">
            <span id="fc-gp-dirty" class="fc-gp-page__dirty hidden">Unsaved changes</span>
            <div class="fc-products-download-dropdown" data-fc-gp-download-dropdown>
                <button
                    type="button"
                    class="btn btn-sm btn-dark fw-semibold fc-products-download-trigger"
                    data-fc-gp-download-toggle
                    aria-haspopup="menu"
                    aria-expanded="false"
                    aria-controls="fc-gp-download-menu"
                    id="fc-gp-download-toggle"
                >
                    <span>Download</span>
                    <i class="fa-solid fa-chevron-down fc-products-download-dropdown__caret" aria-hidden="true"></i>
                </button>
                <div
                    class="fc-products-download-dropdown__panel"
                    id="fc-gp-download-menu"
                    role="menu"
                    aria-labelledby="fc-gp-download-toggle"
                    hidden
                >
                    <button
                        type="button"
                        class="fc-products-download-dropdown__option"
                        role="menuitem"
                        data-fc-gp-export-json
                    >
                        <span>Export JSON</span>
                    </button>
                    <button
                        type="button"
                        class="fc-products-download-dropdown__option"
                        role="menuitem"
                        data-fc-gp-export-all-json
                    >
                        <span>Export All JSON</span>
                    </button>
                    <button
                        type="button"
                        class="fc-products-download-dropdown__option"
                        role="menuitem"
                        data-fc-gp-import-json
                    >
                        <span>Import JSON</span>
                    </button>
                </div>
                <input
                    type="file"
                    class="sr-only"
                    accept=".json,application/json"
                    data-fc-gp-import-input
                    tabindex="-1"
                    aria-hidden="true"
                >
            </div>
            <button type="button" id="fc-gp-save" class="btn btn-sm btn-orange fw-semibold"<?php echo $isLocked ? ' disabled' : ''; ?>>
                Save Permissions
            </button>
        </div>
    </header>

    <div class="fc-gp-page__layout">
        <aside class="fc-gp-page__groups" aria-label="Roles">
            <label class="fc-entries-page__search-wrap fc-gp-page__search-wrap">
                <i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    id="fc-gp-role-search"
                    class="fc-entries-page__search"
                    placeholder="Search groups…"
                    autocomplete="off"
                    aria-label="Search groups"
                >
            </label>
            <ul class="fc-gp-page__role-list" id="fc-gp-role-list" role="listbox">
                <?php foreach ($roles as $role) : ?>
                <?php
                $key = (string) ($role['key'] ?? '');
                $label = (string) ($role['label'] ?? $key);
                $active = $key === $selected;
                $hasPermissions = !empty($role['has_permissions']);
                $isSuper = !empty($role['is_super_admin']);
                $roleLocked = !empty($role['is_locked']);
                ?>
                <li>
                    <button
                        type="button"
                        class="fc-gp-page__role<?php echo $active ? ' is-active' : ''; ?>"
                        data-fc-gp-role="<?php echo $h($key); ?>"
                        data-fc-gp-super="<?php echo $isSuper ? '1' : '0'; ?>"
                        data-fc-gp-admin="<?php echo !empty($role['is_administrator']) ? '1' : '0'; ?>"
                        data-fc-gp-locked="<?php echo $roleLocked ? '1' : '0'; ?>"
                        data-fc-gp-has-perms="<?php echo $hasPermissions ? '1' : '0'; ?>"
                        role="option"
                        aria-selected="<?php echo $active ? 'true' : 'false'; ?>"
                    >
                        <span
                            class="fc-gp-page__role-dot<?php echo $hasPermissions ? ' is-on' : ''; ?>"
                            data-fc-gp-role-dot
                            aria-hidden="true"
                        ></span>
                        <span class="fc-gp-page__role-name"><?php echo $h($label); ?></span>
                        <?php if ($isSuper) : ?>
                        <span class="fc-gp-page__role-badge">Full access</span>
                        <?php endif; ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <section class="fc-gp-page__editor" aria-label="Permission tree">
            <div id="fc-gp-admin-notice" class="fc-gp-page__notice<?php echo $isLocked && $lockNotice !== '' ? '' : ' hidden'; ?>">
                <?php echo $h($lockNotice !== '' ? $lockNotice : 'This role cannot be edited.'); ?>
            </div>
            <div id="fc-gp-tree" class="fc-gp-tree"<?php echo $isLocked ? ' data-locked="1"' : ''; ?>></div>
        </section>
    </div>
</div>
