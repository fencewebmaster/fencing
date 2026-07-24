    <!-- Mobile sidebar backdrop -->
    <div
        id="fc-admin-sidebar-backdrop"
        class="fixed inset-0 z-40 bg-slate-900/60 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden"
        aria-hidden="true"
    ></div>

    <div id="fc-admin-shell" class="flex h-full w-full">
        <!-- Sidebar: fixed full height; does not scroll with main content -->
        <aside
            id="fc-admin-sidebar"
            class="fixed inset-y-0 left-0 z-50 flex h-full max-w-[85vw] -translate-x-full flex-col border-r transition-transform duration-200 ease-out lg:max-w-none lg:translate-x-0"
            aria-label="Admin navigation"
            aria-hidden="true"
        >
            <?php include __DIR__ . '/sidebar-brand.php'; ?>

            <nav class="fc-sidebar-nav" id="fc-admin-nav" aria-label="Main">
                <ul class="fc-sidebar-nav__list">
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/', ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-route="dashboard"
                            data-title="Dashboard"
                            class="fc-sidebar-nav__link"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-gauge-high"></i></span>
                            <span class="fc-sidebar-nav__label">Dashboard</span>
                        </a>
                    </li>

                    <?php
                    $fcCan = $fcCan ?? static function (string $key): bool {
                        return function_exists('fc_auth_user_can') ? fc_auth_user_can($key) : true;
                    };
                    $fcSiteSwitched = function_exists('fc_admin_is_site_switched') && fc_admin_is_site_switched();
                    $fcShowSystemProducts = !$fcSiteSwitched && $fcCan('products.system_products.view');
                    $fcShowStoreProducts = !$fcSiteSwitched && $fcCan('products.store_products.view');
                    $fcShowFenceStyles = !$fcSiteSwitched && $fcCan('products.fence_styles.view_list');
                    $fcShowProductsGroup = $fcShowSystemProducts || $fcShowStoreProducts || $fcShowFenceStyles;
                    $fcProductsRouteActive = str_starts_with((string) $fcAdminRoute, 'products/');
                    $fcShowGallery = !$fcSiteSwitched && $fcCan('media_library.view_list');
                    $fcShowEntries = $fcSiteSwitched || $fcCan('planner_entries.view_list');
                    $fcShowContentSection = $fcShowGallery || $fcShowEntries;
                    $fcShowUsersList = !$fcSiteSwitched && $fcCan('users.view_list');
                    $fcShowGroupPerms = !$fcSiteSwitched && $fcCan('users.group_permissions');
                    $fcShowUsersGroup = $fcShowUsersList || $fcShowGroupPerms;
                    $fcShowSettings = !$fcSiteSwitched && $fcCan('settings.settings');
                    $fcShowSystemSection = $fcShowUsersGroup || $fcShowSettings;
                    $fcUsersRouteActive = $fcAdminRoute === 'users' || str_starts_with((string) $fcAdminRoute, 'users/');
                    ?>
                    <?php if ($fcShowProductsGroup) : ?>
                    <li class="fc-sidebar-nav__group<?php echo $fcProductsRouteActive ? ' is-open' : ''; ?>" id="fc-admin-products-group">
                        <button
                            type="button"
                            class="fc-sidebar-nav__group-toggle<?php echo $fcProductsRouteActive ? ' is-expanded' : ''; ?>"
                            id="fc-admin-products-toggle"
                            aria-expanded="<?php echo $fcProductsRouteActive ? 'true' : 'false'; ?>"
                            aria-controls="fc-admin-products-submenu"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-cubes"></i></span>
                            <span class="fc-sidebar-nav__label">Products</span>
                            <i class="fa-solid fa-chevron-down fc-sidebar-nav__chevron" aria-hidden="true"></i>
                        </button>
                        <ul id="fc-admin-products-submenu" class="fc-sidebar-nav__submenu">
                            <?php if ($fcShowSystemProducts) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/products/system-products', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-route="products/system-products"
                                    data-title="System Products"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'products/system-products' ? ' is-active' : ''; ?>"
                                >
                                    System Products
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($fcShowStoreProducts) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/products/store-products', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-route="products/store-products"
                                    data-title="Store Products"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'products/store-products' ? ' is-active' : ''; ?>"
                                >
                                    Store Products
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($fcShowFenceStyles) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/products/fence-styles', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-route="products/fence-styles"
                                    data-title="Fence Styles"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'products/fence-styles' || str_starts_with((string) $fcAdminRoute, 'products/fence-styles/') ? ' is-active' : ''; ?>"
                                >
                                    Fence Styles
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if ($fcShowContentSection) : ?>
                    <li class="fc-sidebar-nav__section-label" aria-hidden="true">Content</li>
                    <?php endif; ?>

                    <?php if ($fcShowGallery) : ?>
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/gallery', ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-route="gallery"
                            data-title="Media Library"
                            class="fc-sidebar-nav__link<?php echo $fcAdminRoute === 'gallery' ? ' is-active' : ''; ?>"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-images"></i></span>
                            <span class="fc-sidebar-nav__label">Media Library</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($fcShowEntries) : ?>
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/' . $fcPlannerEntriesRoute, ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-nav-full="1"
                            data-route="<?php echo htmlspecialchars($fcPlannerEntriesRoute, ENT_QUOTES, 'UTF-8'); ?>"
                            data-title="<?php echo htmlspecialchars($fcPlannerEntriesTitle, ENT_QUOTES, 'UTF-8'); ?>"
                            class="fc-sidebar-nav__link<?php echo $fcAdminRoute === $fcPlannerEntriesRoute ? ' is-active' : ''; ?>"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                            <span class="fc-sidebar-nav__label"><?php echo htmlspecialchars($fcPlannerEntriesTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($fcShowSystemSection) : ?>
                    <li class="fc-sidebar-nav__section-label" aria-hidden="true">System</li>
                    <?php endif; ?>

                    <?php if ($fcShowUsersGroup) : ?>
                    <li class="fc-sidebar-nav__group<?php echo $fcUsersRouteActive ? ' is-open' : ''; ?>" id="fc-admin-users-group">
                        <button
                            type="button"
                            class="fc-sidebar-nav__group-toggle<?php echo $fcUsersRouteActive ? ' is-expanded' : ''; ?>"
                            id="fc-admin-users-toggle"
                            aria-expanded="<?php echo $fcUsersRouteActive ? 'true' : 'false'; ?>"
                            aria-controls="fc-admin-users-submenu"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
                            <span class="fc-sidebar-nav__label">Users</span>
                            <i class="fa-solid fa-chevron-down fc-sidebar-nav__chevron" aria-hidden="true"></i>
                        </button>
                        <ul id="fc-admin-users-submenu" class="fc-sidebar-nav__submenu">
                            <?php if ($fcShowUsersList) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/users', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-nav-full="1"
                                    data-route="users"
                                    data-title="All Users"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'users' ? ' is-active' : ''; ?>"
                                >
                                    All Users
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($fcShowGroupPerms) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/users/group-permissions', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-nav-full="1"
                                    data-route="users/group-permissions"
                                    data-title="Group Permissions"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'users/group-permissions' ? ' is-active' : ''; ?>"
                                >
                                    Group Permissions
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if ($fcShowSettings) : ?>
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/settings', ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-route="settings"
                            data-title="Settings"
                            class="fc-sidebar-nav__link<?php echo $fcAdminRoute === 'settings' ? ' is-active' : ''; ?>"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></span>
                            <span class="fc-sidebar-nav__label">Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="fc-sidebar-footer">
                <?php if (!empty($fcAuthUser) && is_array($fcAuthUser)) : ?>
                <?php
                $fcSidebarUserName = (string) (($fcAuthUser['display_name'] ?? '') !== ''
                    ? $fcAuthUser['display_name']
                    : ($fcAuthUser['login'] ?? 'Admin'));
                $fcSidebarUserEmail = (string) ($fcAuthUser['email'] ?? '');
                $fcSidebarUserInitials = '';
                foreach (preg_split('/\s+/', trim($fcSidebarUserName)) ?: [] as $fcSidebarNamePart) {
                    if ($fcSidebarNamePart === '') {
                        continue;
                    }
                    $fcSidebarUserInitials .= strtoupper(substr($fcSidebarNamePart, 0, 1));
                    if (strlen($fcSidebarUserInitials) >= 2) {
                        break;
                    }
                }
                if ($fcSidebarUserInitials === '') {
                    $fcSidebarUserInitials = 'A';
                }
                ?>
                <div class="fc-sidebar-user" data-fc-sidebar-user-menu>
                    <button
                        type="button"
                        class="fc-sidebar-user__toggle"
                        id="fc-sidebar-user-toggle"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="fc-sidebar-user-menu"
                    >
                        <span class="fc-sidebar-user__avatar" aria-hidden="true"><?php echo htmlspecialchars($fcSidebarUserInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="fc-sidebar-user__info">
                            <span class="fc-sidebar-user__name"><?php echo htmlspecialchars($fcSidebarUserName, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($fcSidebarUserEmail !== '') : ?>
                            <span class="fc-sidebar-user__email"><?php echo htmlspecialchars($fcSidebarUserEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </span>
                        <i class="fa-solid fa-chevron-up fc-sidebar-user__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-sidebar-user__menu"
                        id="fc-sidebar-user-menu"
                        role="menu"
                        aria-labelledby="fc-sidebar-user-toggle"
                        hidden
                    >
                        <a
                            href="<?php echo htmlspecialchars($fcAppBase . '/planner', ENT_QUOTES, 'UTF-8'); ?>"
                            class="fc-sidebar-user__menu-item"
                            role="menuitem"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                            <span>Open planner</span>
                        </a>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/logout', ENT_QUOTES, 'UTF-8'); ?>"
                            class="fc-sidebar-user__menu-item fc-sidebar-user__menu-item--danger"
                            role="menuitem"
                        >
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                            <span>Sign out</span>
                        </a>
                    </div>
                </div>
                <?php else : ?>
                <a
                    href="<?php echo htmlspecialchars($fcAppBase . '/planner', ENT_QUOTES, 'UTF-8'); ?>"
                    class="fc-sidebar-footer__planner"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="fc-sidebar-footer__planner-icon" aria-hidden="true">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </span>
                    <span class="fc-sidebar-footer__planner-text">
                        <span class="fc-sidebar-footer__planner-label">Open planner</span>
                        <span class="fc-sidebar-footer__planner-name"><?php echo htmlspecialchars($fcBranding['appName'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </a>
                <?php endif; ?>
                <div class="fc-sidebar-footer__meta">
                    <span>&copy; <?php echo date('Y'); ?></span>
                    <span class="fc-sidebar-footer__version"><?php echo htmlspecialchars($fcBranding['version'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </aside>
