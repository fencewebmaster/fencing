<?php
$fcBodyCan = isset($fcCan) && is_callable($fcCan)
    ? $fcCan
    : static function (string $key): bool {
        return function_exists('fc_auth_user_can') ? fc_auth_user_can($key) : true;
    };
?>
<body class="fc-admin-page bg-slate-100 text-slate-800 antialiased h-full" data-fc-admin-base="<?php echo htmlspecialchars($fcAdminBase, ENT_QUOTES, 'UTF-8'); ?>" data-fc-app-base="<?php echo htmlspecialchars($fcAppBase, ENT_QUOTES, 'UTF-8'); ?>" data-fc-admin-initial-route="<?php echo htmlspecialchars($fcAdminRoute, ENT_QUOTES, 'UTF-8'); ?>" data-fc-date-format="<?php echo htmlspecialchars((string) ($fcDateFormat ?? 'M. j, Y h:i A'), ENT_QUOTES, 'UTF-8'); ?>" data-fc-can-media-list="<?php echo $fcBodyCan('media_library.view_list') ? '1' : '0'; ?>" data-fc-can-media-upload="<?php echo $fcBodyCan('media_library.upload') ? '1' : '0'; ?>" data-fc-can-media-delete="<?php echo $fcBodyCan('media_library.delete') ? '1' : '0'; ?>">
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
            <div class="fc-sidebar-brand">
                <?php $fcSidebarLogoUrl = function_exists('fc_branding_logo_url') ? fc_branding_logo_url($fcAppBase, $fcBranding) : ''; ?>
                <span class="fc-sidebar-brand__mark<?php echo $fcSidebarLogoUrl !== '' ? ' fc-sidebar-brand__mark--image' : ''; ?>" aria-hidden="true">
                    <?php echo function_exists('fc_branding_logo_markup') ? fc_branding_logo_markup($fcAppBase, $fcBranding, ['img_class' => 'fc-sidebar-brand__logo-img']) : '<i class="fa-solid fa-border-all"></i>'; ?>
                </span>
                <div class="fc-sidebar-brand__text">
                    <p class="fc-sidebar-brand__title">FC Admin</p>
                    <p class="fc-sidebar-brand__subtitle"><?php echo htmlspecialchars($fcBranding['appName'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <button
                    type="button"
                    id="fc-admin-sidebar-close"
                    class="fc-sidebar-brand__close lg:hidden"
                    aria-label="Close menu"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

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

                    <li class="fc-sidebar-nav__group is-open" id="fc-admin-products-group">
                        <button
                            type="button"
                            class="fc-sidebar-nav__group-toggle is-expanded"
                            id="fc-admin-products-toggle"
                            aria-expanded="true"
                            aria-controls="fc-admin-products-submenu"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-cubes"></i></span>
                            <span class="fc-sidebar-nav__label">Products</span>
                            <i class="fa-solid fa-chevron-down fc-sidebar-nav__chevron" aria-hidden="true"></i>
                        </button>
                        <ul id="fc-admin-products-submenu" class="fc-sidebar-nav__submenu">
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
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/products/fence-styles', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-route="products/fence-styles"
                                    data-title="Fence Styles"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'products/fence-styles' ? ' is-active' : ''; ?>"
                                >
                                    Fence Styles
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="fc-sidebar-nav__section-label" aria-hidden="true">Content</li>

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

                    <li class="fc-sidebar-nav__section-label" aria-hidden="true">System</li>

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

        <!-- Main: offset for fixed sidebar; only this column scrolls vertically -->
        <div class="fc-admin-main-column flex h-full min-h-0 min-w-0 flex-1 flex-col">
            <header class="fc-admin-topbar z-40 flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:gap-4 sm:px-6">
                <button
                    type="button"
                    id="fc-admin-menu-toggle"
                    class="fc-admin-topbar__menu-btn flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 lg:hidden"
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="fc-admin-sidebar"
                >
                    <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
                </button>
                <h1 id="fc-admin-page-title" class="fc-admin-topbar__title min-w-0 flex-1 truncate text-lg font-semibold text-slate-900 sm:text-xl">
                    <?php echo htmlspecialchars($pageTitle); ?>
                </h1>
                <div class="fc-admin-topbar__actions shrink-0">
                    <div class="fc-admin-theme-switcher shrink-0" role="group" aria-label="Appearance">
                        <button
                            type="button"
                            class="fc-admin-theme-switcher__btn fc-admin-theme-switcher__btn--active"
                            data-fc-admin-theme-set="light"
                            aria-label="Light mode"
                            aria-pressed="true"
                            title="Light mode"
                        >
                            <i class="fa-solid fa-sun text-sm" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            class="fc-admin-theme-switcher__btn"
                            data-fc-admin-theme-set="dark"
                            aria-label="Dark mode"
                            aria-pressed="false"
                            title="Dark mode"
                        >
                            <i class="fa-solid fa-moon text-sm" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php
                    if (!function_exists('fc_storage_cache_stats')) {
                        require_once FC_ROOT . '/config/storage.php';
                    }
                    $fcCacheStats = fc_storage_cache_stats();
                    $fcCacheAllLabel = (string) ($fcCacheStats['all']['label'] ?? '0 items (0B)');
                    $fcCacheLookupLabel = (string) (($fcCacheStats['buckets']['lookup']['label'] ?? null) ?: '0 items (0B)');
                    $fcCacheProductsLabel = (string) (($fcCacheStats['buckets']['products']['label'] ?? null) ?: '0 items (0B)');
                    ?>
                    <div
                        class="fc-entries-date-dropdown fc-admin-cache-dropdown shrink-0"
                        data-fc-cache-purge-dropdown
                        data-fc-cache-api="api.php?module=cache"
                        data-fc-cache-csrf="<?php echo htmlspecialchars(function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <button
                            type="button"
                            class="fc-admin-topbar__menu-btn fc-entries-date-dropdown__toggle fc-admin-cache-dropdown__toggle flex shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                            id="fc-admin-cache-toggle"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            aria-controls="fc-admin-cache-panel"
                            aria-label="Purge caches"
                            title="Purge caches"
                        >
                            <i class="fa-solid fa-database fc-entries-date-dropdown__icon" aria-hidden="true"></i>
                            <i class="fa-solid fa-chevron-down fc-entries-date-dropdown__caret fc-admin-cache-dropdown__caret" aria-hidden="true"></i>
                        </button>
                        <div
                            class="fc-entries-date-dropdown__panel fc-admin-cache-dropdown__panel"
                            id="fc-admin-cache-panel"
                            role="menu"
                            aria-labelledby="fc-admin-cache-toggle"
                            hidden
                        >
                            <div class="fc-admin-cache-dropdown__head">
                                <span class="fc-admin-cache-dropdown__head-title">Clear cache</span>
                                <span class="fc-admin-cache-dropdown__head-hint">Choose a cache to purge</span>
                            </div>
                            <div class="fc-entries-date-dropdown__presets fc-admin-cache-dropdown__presets">
                                <button type="button" class="fc-entries-date-dropdown__option fc-admin-cache-dropdown__option" role="menuitem" data-fc-cache-purge="lookup">
                                    <span class="fc-admin-cache-dropdown__option-icon" aria-hidden="true">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <span class="fc-admin-cache-dropdown__option-text">
                                        <span class="fc-admin-cache-dropdown__option-label">Lookup</span>
                                        <span class="fc-admin-cache-dropdown__option-meta" data-fc-cache-meta><?php echo htmlspecialchars($fcCacheLookupLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </button>
                                <button type="button" class="fc-entries-date-dropdown__option fc-admin-cache-dropdown__option" role="menuitem" data-fc-cache-purge="products">
                                    <span class="fc-admin-cache-dropdown__option-icon" aria-hidden="true">
                                        <i class="fa-solid fa-box"></i>
                                    </span>
                                    <span class="fc-admin-cache-dropdown__option-text">
                                        <span class="fc-admin-cache-dropdown__option-label">Products</span>
                                        <span class="fc-admin-cache-dropdown__option-meta" data-fc-cache-meta><?php echo htmlspecialchars($fcCacheProductsLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </button>
                            </div>
                            <div class="fc-admin-cache-dropdown__divider" role="separator"></div>
                            <div class="fc-admin-cache-dropdown__footer">
                                <button type="button" class="fc-entries-date-dropdown__option fc-admin-cache-dropdown__option fc-admin-cache-dropdown__option--all" role="menuitem" data-fc-cache-purge="all">
                                    <span class="fc-admin-cache-dropdown__option-icon" aria-hidden="true">
                                        <i class="fa-solid fa-broom"></i>
                                    </span>
                                    <span class="fc-admin-cache-dropdown__option-text">
                                        <span class="fc-admin-cache-dropdown__option-label">Purge All</span>
                                        <span class="fc-admin-cache-dropdown__option-meta" data-fc-cache-meta><?php echo htmlspecialchars($fcCacheAllLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main id="fc-admin-main" class="fc-admin-main relative min-h-0 flex-1 overflow-x-hidden bg-slate-50<?php echo ($fcAdminIsEntries || $fcAdminIsSettings || $fcAdminIsGallery || $fcAdminIsProductsPage) ? ' flex flex-col overflow-hidden p-0' : ' overflow-y-auto p-4 sm:p-6'; ?>">
                <div
                    id="fc-admin-page-content"
                    class="fc-admin-content-card relative<?php echo ($fcAdminIsEntries || $fcAdminIsSettings || $fcAdminIsGallery || $fcAdminIsProductsPage) ? ' fc-admin-content-card--fill fc-admin-content-card--no-pad flex flex-1 min-h-0 flex-col overflow-hidden p-0 bg-white' : ' min-h-full rounded-lg bg-white shadow-sm ring-1 ring-slate-200/60 sm:rounded-xl'; ?>"
                    <?php if ($fcAdminRoute === $fcPlannerEntriesRoute) : ?>
                    data-route="<?php echo htmlspecialchars($fcPlannerEntriesRoute, ENT_QUOTES, 'UTF-8'); ?>"
                    data-fc-entries-server="1"
                    <?php elseif ($fcAdminIsSettings) : ?>
                    data-route="settings"
                    data-fc-settings-server="1"
                    <?php elseif ($fcAdminIsGallery) : ?>
                    data-route="gallery"
                    data-fc-gallery-server="1"
                    <?php elseif ($fcAdminRoute === 'products/store-products' && is_array($fcSystemProductsPage)) : ?>
                    data-route="products/store-products"
                    data-fc-system-products-server="1"
                    <?php elseif ($fcAdminRoute === 'products/system-products' && is_array($fcStoreProductsPage)) : ?>
                    data-route="products/system-products"
                    data-fc-store-products-server="1"
                    <?php elseif ($fcAdminRoute === 'products/fence-styles') : ?>
                    data-route="products/fence-styles"
                    data-fc-fence-styles-server="1"
                    <?php elseif (preg_match('#^products/fence-styles/edit/#', (string) $fcAdminRoute)) : ?>
                    data-route="<?php echo htmlspecialchars($fcAdminRoute, ENT_QUOTES, 'UTF-8'); ?>"
                    <?php endif; ?>
                    aria-live="polite"
                >
                <?php if ($fcAdminRoute === $fcPlannerEntriesRoute && is_array($fcEntriesDetailPage)) : ?>
                    <?php include __DIR__ . '/views/entries-detail.php'; ?>
                <?php elseif ($fcAdminRoute === $fcPlannerEntriesRoute && is_array($fcEntriesPage)) : ?>
                    <?php include __DIR__ . '/views/entries.php'; ?>
                <?php elseif ($fcAdminIsSettings && is_array($fcSettingsPage)) : ?>
                    <?php include __DIR__ . '/views/settings.php'; ?>
                <?php elseif ($fcAdminIsGallery && is_array($fcGalleryPage)) : ?>
                    <?php include __DIR__ . '/views/gallery.php'; ?>
                <?php elseif ($fcAdminRoute === 'products/fence-styles' && is_array($fcFenceStylesPage)) : ?>
                    <?php include __DIR__ . '/views/products-fence-styles.php'; ?>
                <?php elseif ($fcAdminRoute === 'products/store-products' && is_array($fcSystemProductsPage)) : ?>
                    <?php include __DIR__ . '/views/products-system-products.php'; ?>
                <?php elseif ($fcAdminRoute === 'products/system-products' && is_array($fcStoreProductsPage)) : ?>
                    <?php include __DIR__ . '/views/products-store-products.php'; ?>
                <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="fc-admin-modal-root" class="pointer-events-none fixed inset-0 z-[9999]" aria-hidden="true"></div>

    <script src="assets/js/fc-admin-ui.js"></script>
    <script src="assets/js/fc-theme.js"></script>
    <script src="assets/js/fc-admin-appearance.js"></script>
    <script src="assets/js/fc-lazy.js"></script>
    <?php if ($fcAdminIsEntries) : ?>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/entries-filters.js"></script>
    <script src="assets/js/entries-date-filter.js"></script>
    <script src="assets/js/entries-planner-copy.js"></script>
    <script src="assets/js/entries-bulk.js"></script>
    <script src="assets/js/entries-detail-copy.js"></script>
    <?php if (is_array($fcEntriesDetailPage)) : ?>
    <script src="assets/js/entries-cart-filters.js"></script>
    <script src="assets/js/entries-cart-gallery.js"></script>
    <?php endif; ?>
    <?php elseif ($fcAdminIsSettings) : ?>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/gallery-upload-queue.js"></script>
    <script src="assets/js/gallery.js"></script>
    <script src="assets/js/media-picker.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/app.js"></script>
    <?php elseif ($fcAdminIsGallery) : ?>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/gallery-upload-queue.js"></script>
    <script src="assets/js/gallery.js"></script>
    <script src="assets/js/app.js"></script>
    <?php elseif ($fcAdminRoute === 'products/store-products') : ?>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/system-products.js"></script>
    <script src="assets/js/app.js"></script>
    <?php elseif ($fcAdminRoute === 'products/system-products') : ?>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/store-products-color-filter.js"></script>
    <script src="assets/js/store-products.js"></script>
    <script src="assets/js/app.js"></script>
    <?php elseif ($fcAdminRoute === 'products/fence-styles' || preg_match('#^products/fence-styles/edit/#', (string) $fcAdminRoute)) : ?>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/gallery-upload-queue.js"></script>
    <script src="assets/js/gallery.js"></script>
    <script src="assets/js/media-picker.js"></script>
    <script src="assets/js/fence-styles-wysiwyg.js"></script>
    <script src="assets/js/fence-styles-code-editor.js"></script>
    <script src="assets/js/fence-styles-gui.js"></script>
    <script src="assets/js/fence-styles-edit.js"></script>
    <script src="assets/js/fence-styles.js"></script>
    <script src="assets/js/app.js"></script>
    <?php else : ?>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/store-products.js"></script>
    <script src="assets/js/system-products.js"></script>
    <script src="assets/js/gallery-upload-queue.js"></script>
    <script src="assets/js/gallery.js"></script>
    <script src="assets/js/media-picker.js"></script>
    <script src="assets/js/fence-styles-wysiwyg.js"></script>
    <script src="assets/js/fence-styles-code-editor.js"></script>
    <script src="assets/js/fence-styles-gui.js"></script>
    <script src="assets/js/fence-styles-edit.js"></script>
    <script src="assets/js/fence-styles.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/app.js"></script>
    <?php endif; ?>
</body>
</html>
