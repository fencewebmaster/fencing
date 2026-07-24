/**
 * FC Admin — sidebar navigation, page title (path routing), mobile menu.
 */
(function (global) {
    'use strict';

    var DEFAULT_ROUTE = 'dashboard';
    var DEFAULT_TITLE = 'Dashboard';
    var DESKTOP_BREAKPOINT = 1024;

    var titleEl = document.getElementById('fc-admin-page-title');
    var contentEl = document.getElementById('fc-admin-page-content');
    var mainEl = document.getElementById('fc-admin-main');
    var sidebarEl = document.getElementById('fc-admin-sidebar');
    var backdropEl = document.getElementById('fc-admin-sidebar-backdrop');
    var menuToggleEl = document.getElementById('fc-admin-menu-toggle');
    var sidebarCloseEl = document.getElementById('fc-admin-sidebar-close');
    var navLinks = document.querySelectorAll('[data-nav], [data-nav-child]');
    var productsGroupEl = document.getElementById('fc-admin-products-group');
    var productsToggleEl = document.getElementById('fc-admin-products-toggle');
    var usersGroupEl = document.getElementById('fc-admin-users-group');
    var usersToggleEl = document.getElementById('fc-admin-users-toggle');

    function getAdminBasePath() {
        var base = document.body && document.body.getAttribute('data-fc-admin-base');
        if (base) {
            return String(base).replace(/\/+$/, '');
        }
        var path = window.location.pathname.replace(/\/+$/, '');
        var idx = path.lastIndexOf('/');
        return idx >= 0 ? path.slice(0, idx) : path;
    }

    function normalizeRoute(raw) {
        var route = String(raw || '')
            .replace(/^#\/?/, '')
            .trim()
            .replace(/\/+$/, '');
        if (!route) {
            return DEFAULT_ROUTE;
        }
        return route;
    }

    function routeFromPathname() {
        var base = getAdminBasePath();
        var path = window.location.pathname.replace(/\/+$/, '') || '/';
        var baseNorm = base.replace(/\/+$/, '') || '';

        if (baseNorm && path.indexOf(baseNorm) === 0) {
            var tail = path.slice(baseNorm.length).replace(/^\/+/, '');
            return normalizeRoute(tail);
        }

        var initial = document.body && document.body.getAttribute('data-fc-admin-initial-route');
        return normalizeRoute(initial);
    }

    function routeToUrl(route) {
        var base = getAdminBasePath();
        var normalized = normalizeRoute(route);
        if (normalized === DEFAULT_ROUTE) {
            return base + '/';
        }
        return base + '/' + normalized;
    }

    function routeToTitle(route) {
        var link = document.querySelector('[data-route="' + route + '"]');
        if (link && link.getAttribute('data-title')) {
            return link.getAttribute('data-title');
        }
        if (route.indexOf('products/fence-styles/edit/') === 0) {
            var slug = route.slice('products/fence-styles/edit/'.length);
            try {
                slug = decodeURIComponent(slug);
            } catch (e) {
                /* keep raw slug */
            }
            return (
                'Edit ' +
                slug.replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                    return c.toUpperCase();
                })
            );
        }
        if (route.indexOf('planner-entries/') === 0) {
            return 'Planner Entry';
        }
        if (route === DEFAULT_ROUTE) {
            return DEFAULT_TITLE;
        }
        return route
            .split('/')
            .pop()
            .replace(/-/g, ' ')
            .replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
    }

    function isDesktopViewport() {
        return window.matchMedia('(min-width: ' + DESKTOP_BREAKPOINT + 'px)').matches;
    }

    function setSidebarOpen(open) {
        var onMobile = !isDesktopViewport();

        if (!onMobile) {
            syncSidebarForViewport();
            return;
        }

        if (sidebarEl) {
            sidebarEl.classList.toggle('-translate-x-full', !open);
            sidebarEl.classList.toggle('translate-x-0', open);
            sidebarEl.setAttribute('aria-hidden', open ? 'false' : 'true');
        }

        if (backdropEl) {
            backdropEl.classList.toggle('opacity-0', !open);
            backdropEl.classList.toggle('pointer-events-none', !open);
            backdropEl.classList.toggle('opacity-100', open);
            backdropEl.setAttribute('aria-hidden', open ? 'false' : 'true');
        }

        if (menuToggleEl) {
            menuToggleEl.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        document.body.classList.toggle('fc-admin-sidebar-open', open);
    }

    function openSidebar() {
        if (!isDesktopViewport()) {
            setSidebarOpen(true);
        }
    }

    function closeSidebar() {
        setSidebarOpen(false);
    }

    function syncSidebarForViewport() {
        if (isDesktopViewport()) {
            if (sidebarEl) {
                sidebarEl.classList.remove('-translate-x-full', 'translate-x-0');
                sidebarEl.setAttribute('aria-hidden', 'false');
            }
            if (backdropEl) {
                backdropEl.classList.add('opacity-0', 'pointer-events-none');
                backdropEl.classList.remove('opacity-100');
            }
            if (menuToggleEl) {
                menuToggleEl.setAttribute('aria-expanded', 'false');
            }
            document.body.classList.remove('fc-admin-sidebar-open');
        } else {
            setSidebarOpen(false);
        }
    }

    function isProductsRoute(route) {
        return route === 'products/system-products' ||
            route === 'products/store-products' ||
            route === 'products/fence-styles' ||
            route.indexOf('products/fence-styles/') === 0;
    }

    function setProductsGroupOpen(open) {
        if (!productsGroupEl || !productsToggleEl) {
            return;
        }

        productsGroupEl.classList.toggle('is-open', open);
        productsToggleEl.classList.toggle('is-expanded', open);
        productsToggleEl.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function syncProductsGroupForRoute(route) {
        if (!productsToggleEl) {
            return;
        }

        var childActive = isProductsRoute(route);
        productsToggleEl.classList.toggle('has-active-child', childActive);
        setProductsGroupOpen(childActive);
    }

    function bindProductsGroupToggle() {
        if (!productsToggleEl || productsToggleEl.getAttribute('data-fc-products-bound') === '1') {
            return;
        }
        productsToggleEl.setAttribute('data-fc-products-bound', '1');
        productsToggleEl.addEventListener('click', function () {
            setProductsGroupOpen(!productsGroupEl.classList.contains('is-open'));
        });
    }

    function isUsersRoute(route) {
        return route === 'users' || route.indexOf('users/') === 0;
    }

    function setUsersGroupOpen(open) {
        if (!usersGroupEl || !usersToggleEl) {
            return;
        }

        usersGroupEl.classList.toggle('is-open', open);
        usersToggleEl.classList.toggle('is-expanded', open);
        usersToggleEl.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function syncUsersGroupForRoute(route) {
        if (!usersToggleEl) {
            return;
        }

        var childActive = isUsersRoute(route);
        usersToggleEl.classList.toggle('has-active-child', childActive);
        setUsersGroupOpen(childActive);
    }

    function bindUsersGroupToggle() {
        if (!usersToggleEl || usersToggleEl.getAttribute('data-fc-users-bound') === '1') {
            return;
        }
        usersToggleEl.setAttribute('data-fc-users-bound', '1');
        usersToggleEl.addEventListener('click', function () {
            setUsersGroupOpen(!usersGroupEl.classList.contains('is-open'));
        });
    }

    function setActiveNav(route) {
        navLinks.forEach(function (el) {
            el.classList.remove('is-active');
        });

        var navRoute = route;
        if (route.indexOf('products/fence-styles/edit/') === 0) {
            navRoute = 'products/fence-styles';
        }
        if (route.indexOf('planner-entries/') === 0) {
            navRoute = 'planner-entries';
        }

        var exact = document.querySelector('[data-route="' + navRoute + '"]');
        if (exact) {
            exact.classList.add('is-active');
        }

        syncProductsGroupForRoute(route);
        syncUsersGroupForRoute(route);
    }

    function updateDocumentTitle(pageTitle) {
        document.title = pageTitle + ' — FC Admin';
    }

    var MAIN_SCROLL_CLASSES = ['overflow-y-auto', 'p-4', 'sm:p-6'];
    var MAIN_FILL_CLASSES = ['flex', 'flex-col', 'overflow-hidden', 'p-0'];
    var CONTENT_CARD_CLASSES = [
        'min-h-full',
        'rounded-lg',
        'bg-white',
        'fc-admin-content-card',
        'shadow-sm',
        'ring-1',
        'ring-slate-200/60',
        'sm:rounded-xl'
    ];
    var CONTENT_FILL_CLASSES = ['flex', 'flex-1', 'min-h-0', 'flex-col', 'overflow-hidden', 'p-0', 'bg-white', 'fc-admin-content-card'];

    function setStoreProductsViewportLayout(active) {
        if (!mainEl || !contentEl) {
            return;
        }
        MAIN_SCROLL_CLASSES.forEach(function (cls) {
            mainEl.classList.toggle(cls, !active);
        });
        MAIN_FILL_CLASSES.forEach(function (cls) {
            mainEl.classList.toggle(cls, active);
        });
        CONTENT_CARD_CLASSES.forEach(function (cls) {
            contentEl.classList.toggle(cls, !active);
        });
        CONTENT_FILL_CLASSES.forEach(function (cls) {
            contentEl.classList.toggle(cls, active);
        });
        if (active) {
            window.dispatchEvent(new Event('resize'));
        }
    }

    function renderPage(route, pageTitle) {
        if (titleEl) {
            titleEl.textContent = pageTitle;
        }
        updateDocumentTitle(pageTitle);

        if (!contentEl) {
            return;
        }

        contentEl.setAttribute('data-route', route);

        if (typeof contentEl._fcSpDestroy === 'function') {
            contentEl._fcSpDestroy();
            contentEl._fcSpDestroy = null;
        }

        if (typeof contentEl._fcSysDestroy === 'function') {
            contentEl._fcSysDestroy();
            contentEl._fcSysDestroy = null;
        }

        if (typeof contentEl._fcSettingsDestroy === 'function') {
            contentEl._fcSettingsDestroy();
            contentEl._fcSettingsDestroy = null;
        }

        if (typeof contentEl._fcGalleryDestroy === 'function') {
            contentEl._fcGalleryDestroy();
            contentEl._fcGalleryDestroy = null;
        }

        if (route === 'products/store-products') {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-system-products-server') === '1' &&
                contentEl.querySelector('[data-fc-system-products-server]')
            ) {
                contentEl.setAttribute('data-route', 'products/store-products');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                if (
                    window.FcAdminSystemProducts &&
                    typeof window.FcAdminSystemProducts.hydrateFromServer === 'function'
                ) {
                    window.FcAdminSystemProducts.hydrateFromServer(contentEl);
                }
                return;
            }
            window.location.href = routeToUrl('products/store-products');
            return;
        }

        if (route === 'products/system-products') {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-store-products-server') === '1' &&
                contentEl.querySelector('[data-fc-store-products-server]')
            ) {
                contentEl.setAttribute('data-route', 'products/system-products');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                if (
                    window.FcAdminStoreProducts &&
                    typeof window.FcAdminStoreProducts.hydrateFromServer === 'function'
                ) {
                    window.FcAdminStoreProducts.hydrateFromServer(contentEl);
                }
                return;
            }
            window.location.href = routeToUrl('products/system-products');
            return;
        }

        if (route === 'products/fence-styles') {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-fence-styles-server') === '1' &&
                contentEl.querySelector('[data-fc-fence-styles-server]')
            ) {
                contentEl.setAttribute('data-route', 'products/fence-styles');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                if (
                    window.FcAdminFenceStyles &&
                    typeof window.FcAdminFenceStyles.hydrateFromServer === 'function'
                ) {
                    window.FcAdminFenceStyles.hydrateFromServer(contentEl);
                }
                return;
            }
            window.location.href = routeToUrl('products/fence-styles');
            return;
        }

        if (route.indexOf('products/fence-styles/edit/') === 0) {
            setStoreProductsViewportLayout(true);
            contentEl.innerHTML = '';
            var fenceSlug =
                window.FcAdminFenceStyles && typeof window.FcAdminFenceStyles.parseEditSlug === 'function'
                    ? window.FcAdminFenceStyles.parseEditSlug(route)
                    : route.slice('products/fence-styles/edit/'.length);
            if (window.FcAdminFenceStyles && typeof window.FcAdminFenceStyles.loadEdit === 'function') {
                window.FcAdminFenceStyles.loadEdit(contentEl, fenceSlug);
            } else {
                contentEl.innerHTML =
                    '<p class="p-6 text-sm text-red-600">Fence style editor failed to load.</p>';
            }
            return;
        }

        if (route === 'gallery') {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-gallery-server') === '1' &&
                contentEl.querySelector('.fc-gallery-page[data-fc-gallery-server]')
            ) {
                contentEl.setAttribute('data-route', 'gallery');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                if (
                    window.FcAdminGallery &&
                    typeof window.FcAdminGallery.hydrateFromServer === 'function'
                ) {
                    window.FcAdminGallery.hydrateFromServer(contentEl);
                }
                return;
            }
            window.location.href = routeToUrl('gallery');
            return;
        }

        if (route === 'planner-entries' || route.indexOf('planner-entries/') === 0) {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-entries-server') === '1' &&
                (contentEl.querySelector('.fc-entries-page') ||
                    contentEl.querySelector('.fc-entries-detail-page'))
            ) {
                contentEl.setAttribute('data-route', route.indexOf('planner-entries/') === 0 ? route : 'planner-entries');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                return;
            }
            window.location.href = routeToUrl(route);
            return;
        }

        if (route === 'users') {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-users-server') === '1' &&
                contentEl.querySelector('[data-fc-users-list]')
            ) {
                contentEl.setAttribute('data-route', 'users');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                return;
            }
            window.location.href = routeToUrl('users');
            return;
        }

        if (route === 'users/group-permissions') {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-group-permissions-server') === '1' &&
                contentEl.querySelector('[data-fc-group-permissions]')
            ) {
                contentEl.setAttribute('data-route', 'users/group-permissions');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                if (
                    window.FcAdminGroupPermissions &&
                    typeof window.FcAdminGroupPermissions.hydrateFromServer === 'function'
                ) {
                    window.FcAdminGroupPermissions.hydrateFromServer();
                }
                return;
            }
            window.location.href = routeToUrl('users/group-permissions');
            return;
        }

        if (route === 'settings') {
            setStoreProductsViewportLayout(true);
            if (
                contentEl.getAttribute('data-fc-settings-server') === '1' &&
                contentEl.querySelector('.fc-settings-page')
            ) {
                contentEl.setAttribute('data-route', 'settings');
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                if (
                    window.FcAdminSettings &&
                    typeof window.FcAdminSettings.hydrateFromServer === 'function'
                ) {
                    window.FcAdminSettings.hydrateFromServer(
                        contentEl.querySelector('#fc-settings-root') || contentEl
                    );
                }
                return;
            }
            window.location.href = routeToUrl('settings');
            return;
        }

        if (route === DEFAULT_ROUTE) {
            setStoreProductsViewportLayout(false);
            if (
                contentEl.getAttribute('data-fc-dashboard-server') === '1' &&
                contentEl.querySelector('[data-fc-dashboard-server]')
            ) {
                contentEl.setAttribute('data-route', DEFAULT_ROUTE);
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                if (
                    window.FcAdminDashboard &&
                    typeof window.FcAdminDashboard.hydrateFromServer === 'function'
                ) {
                    window.FcAdminDashboard.hydrateFromServer(contentEl);
                }
                return;
            }
            window.location.href = routeToUrl(DEFAULT_ROUTE) + (window.location.search || '');
            return;
        }

        setStoreProductsViewportLayout(false);
        contentEl.innerHTML = '';
    }

    function syncUrl(route, replace) {
        var url = routeToUrl(route);
        var currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
        var targetPath = url.replace(/\/+$/, '') || '/';
        var preserveSearch =
            currentPath === targetPath ||
            currentPath + '/' === targetPath ||
            currentPath === targetPath + '/';
        var fullUrl = preserveSearch ? url + (window.location.search || '') : url;
        var current = window.location.pathname + window.location.search;
        if (
            current === fullUrl ||
            current + '/' === fullUrl ||
            current === fullUrl + '/'
        ) {
            return;
        }
        var state = window.history.state && typeof window.history.state === 'object'
            ? window.history.state
            : {};
        state.fcAdminRoute = route;
        if (replace) {
            window.history.replaceState(state, '', fullUrl);
        } else {
            window.history.pushState(state, '', fullUrl);
        }
    }

    function migrateLegacyHashRoute() {
        var hash = window.location.hash || '';
        if (!/^#\/?/.test(hash)) {
            return null;
        }
        var route = normalizeRoute(hash);
        var url = routeToUrl(route);
        window.history.replaceState({ fcAdminRoute: route }, '', url);
        return route;
    }

    function navigate(route, options) {
        options = options || {};
        route = normalizeRoute(route);
        var pageTitle = routeToTitle(route);
        setActiveNav(route);
        renderPage(route, pageTitle);
        syncUrl(route, !!options.replace);
        closeSidebar();
    }

    function onPopState() {
        navigate(routeFromPathname(), { replace: true });
    }

    function initSidebarUserMenu() {
        var roots = document.querySelectorAll('[data-fc-sidebar-user-menu]');
        if (!roots.length) {
            return;
        }

        function closeMenu(root) {
            var toggle = root.querySelector('.fc-sidebar-user__toggle');
            var menu = root.querySelector('.fc-sidebar-user__menu');
            root.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            if (menu) {
                menu.hidden = true;
            }
        }

        function openMenu(root) {
            roots.forEach(function (other) {
                if (other !== root) {
                    closeMenu(other);
                }
            });
            var siteRoots = document.querySelectorAll('[data-fc-sidebar-site-switcher]');
            siteRoots.forEach(closeSiteSwitcherMenu);
            var toggle = root.querySelector('.fc-sidebar-user__toggle');
            var menu = root.querySelector('.fc-sidebar-user__menu');
            root.classList.add('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
            }
            if (menu) {
                menu.hidden = false;
            }
        }

        function toggleMenu(root) {
            if (root.classList.contains('is-open')) {
                closeMenu(root);
            } else {
                openMenu(root);
            }
        }

        roots.forEach(function (root) {
            var toggle = root.querySelector('.fc-sidebar-user__toggle');
            if (!toggle) {
                return;
            }
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleMenu(root);
            });
        });

        document.addEventListener('click', function (e) {
            roots.forEach(function (root) {
                if (!root.contains(e.target)) {
                    closeMenu(root);
                }
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                roots.forEach(closeMenu);
            }
        });
    }

    function closeSiteSwitcherMenu(root) {
        var toggle = root.querySelector('.fc-sidebar-site__toggle');
        var menu = root.querySelector('.fc-sidebar-site__menu');
        root.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (menu) {
            menu.hidden = true;
        }
    }

    function initSidebarSiteSwitcher() {
        var roots = document.querySelectorAll('[data-fc-sidebar-site-switcher]');
        if (!roots.length) {
            return;
        }

        function getOptions(root) {
            return Array.prototype.slice.call(root.querySelectorAll('.fc-sidebar-site__item'));
        }

        function focusOption(options, index) {
            if (!options.length) {
                return;
            }
            var i = ((index % options.length) + options.length) % options.length;
            options[i].focus();
        }

        function openMenu(root) {
            roots.forEach(function (other) {
                if (other !== root) {
                    closeSiteSwitcherMenu(other);
                }
            });
            document.querySelectorAll('[data-fc-sidebar-user-menu]').forEach(function (userRoot) {
                userRoot.classList.remove('is-open');
                var userToggle = userRoot.querySelector('.fc-sidebar-user__toggle');
                var userMenu = userRoot.querySelector('.fc-sidebar-user__menu');
                if (userToggle) {
                    userToggle.setAttribute('aria-expanded', 'false');
                }
                if (userMenu) {
                    userMenu.hidden = true;
                }
            });
            var toggle = root.querySelector('.fc-sidebar-site__toggle');
            var menu = root.querySelector('.fc-sidebar-site__menu');
            if (menu) {
                menu.hidden = false;
                // Allow CSS open transition after [hidden] is cleared.
                requestAnimationFrame(function () {
                    root.classList.add('is-open');
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                    var active = menu.querySelector('.fc-sidebar-site__item.is-active');
                    if (active && typeof active.scrollIntoView === 'function') {
                        active.scrollIntoView({ block: 'nearest' });
                    }
                });
                return;
            }
            root.classList.add('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
            }
        }

        function toggleMenu(root) {
            if (root.classList.contains('is-open')) {
                closeSiteSwitcherMenu(root);
            } else {
                openMenu(root);
            }
        }

        roots.forEach(function (root) {
            var toggle = root.querySelector('.fc-sidebar-site__toggle');
            var menu = root.querySelector('.fc-sidebar-site__menu');
            if (!toggle) {
                return;
            }
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleMenu(root);
            });
            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (!root.classList.contains('is-open')) {
                        openMenu(root);
                    }
                    var options = getOptions(root);
                    var activeIndex = options.findIndex(function (el) {
                        return el.classList.contains('is-active');
                    });
                    focusOption(options, activeIndex >= 0 ? activeIndex : 0);
                }
            });
            if (menu) {
                menu.addEventListener('keydown', function (e) {
                    var options = getOptions(root);
                    if (!options.length) {
                        return;
                    }
                    var current = document.activeElement;
                    var index = options.indexOf(current);
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        focusOption(options, index < 0 ? 0 : index + 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        focusOption(options, index < 0 ? options.length - 1 : index - 1);
                    } else if (e.key === 'Home') {
                        e.preventDefault();
                        focusOption(options, 0);
                    } else if (e.key === 'End') {
                        e.preventDefault();
                        focusOption(options, options.length - 1);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        closeSiteSwitcherMenu(root);
                        toggle.focus();
                    }
                });
            }
        });

        document.addEventListener('click', function (e) {
            roots.forEach(function (root) {
                if (!root.contains(e.target)) {
                    closeSiteSwitcherMenu(root);
                }
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                roots.forEach(closeSiteSwitcherMenu);
            }
        });
    }

    if (menuToggleEl) {
        menuToggleEl.addEventListener('click', function () {
            var isOpen = sidebarEl && sidebarEl.classList.contains('translate-x-0');
            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarCloseEl) {
        sidebarCloseEl.addEventListener('click', closeSidebar);
    }

    if (backdropEl) {
        backdropEl.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function () {
        syncSidebarForViewport();
    });

    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            closeSidebar();
        });
    });

    bindProductsGroupToggle();
    bindUsersGroupToggle();
    initSidebarUserMenu();
    initSidebarSiteSwitcher();

    window.addEventListener('popstate', onPopState);

    syncSidebarForViewport();

    var initial = migrateLegacyHashRoute() || routeFromPathname();
    navigate(initial, { replace: true });
    window.requestAnimationFrame(function () {
        document.documentElement.classList.add('fc-admin-nav-ready');
    });

    global.fcAdminNavigate = navigate;
})(window);
