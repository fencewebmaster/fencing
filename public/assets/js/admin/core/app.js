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
    var sidebarCollapseEl = document.getElementById('fc-admin-sidebar-collapse');
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

    function isSidebarCollapsed() {
        return document.documentElement.classList.contains('fc-admin-sidebar-collapsed');
    }

    function syncCollapsedTitles(collapsed) {
        if (!sidebarEl) {
            return;
        }

        sidebarEl.querySelectorAll('.fc-sidebar-nav__link, .fc-sidebar-nav__group-toggle').forEach(function (item) {
            if (collapsed) {
                if (!item.getAttribute('title')) {
                    var label = item.querySelector('.fc-sidebar-nav__label');
                    if (label) {
                        item.setAttribute('title', label.textContent.trim());
                        item.setAttribute('data-fc-collapsed-title', '1');
                    }
                }
            } else if (item.getAttribute('data-fc-collapsed-title') === '1') {
                item.removeAttribute('title');
                item.removeAttribute('data-fc-collapsed-title');
            }
        });
    }

    function setSidebarCollapsed(collapsed, persist) {
        collapsed = !!collapsed && isDesktopViewport();
        document.documentElement.classList.toggle('fc-admin-sidebar-collapsed', collapsed);

        if (sidebarCollapseEl) {
            sidebarCollapseEl.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarCollapseEl.setAttribute(
                'aria-label',
                collapsed ? 'Expand sidebar' : 'Collapse sidebar'
            );
            sidebarCollapseEl.setAttribute(
                'title',
                collapsed ? 'Expand sidebar' : 'Collapse sidebar'
            );
        }

        if (collapsed) {
            document.querySelectorAll(
                '[data-fc-sidebar-site-switcher].is-open, [data-fc-sidebar-user-menu].is-open'
            ).forEach(function (root) {
                root.classList.remove('is-open');
                var menu = root.querySelector('[role="listbox"], [role="menu"]');
                var toggle = root.querySelector('[aria-expanded]');
                if (menu) {
                    menu.hidden = true;
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        syncCollapsedTitles(collapsed);

        if (persist !== false) {
            try {
                global.localStorage.setItem('fc-admin-sidebar-collapsed', collapsed ? '1' : '0');
            } catch (e) {
                /* storage may be unavailable */
            }
        }
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
            var collapsed = false;
            try {
                collapsed = global.localStorage.getItem('fc-admin-sidebar-collapsed') === '1';
            } catch (e) {
                collapsed = isSidebarCollapsed();
            }
            setSidebarCollapsed(collapsed, false);
        } else {
            setSidebarCollapsed(false, false);
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
            if (isSidebarCollapsed()) {
                setSidebarCollapsed(false);
                setProductsGroupOpen(true);
                return;
            }
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
            if (isSidebarCollapsed()) {
                setSidebarCollapsed(false);
                setUsersGroupOpen(true);
                return;
            }
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

    /**
     * Route → server-hydration config for every route whose content is
     * swapped in place (server already rendered the target markup). Routes
     * that need special handling (the fence-styles edit sub-route, which is
     * genuinely client-rendered; planner-entries, which has no JS controller
     * to hydrate) are handled as explicit branches below instead.
     *
     * NOTE: 'products/store-products' and 'products/system-products' map to
     * FC.PageRegistry entries registered under the SAME (cross-wired) keys
     * by system-products.js/store-products.js respectively — this mirrors a
     * pre-existing, self-consistent route/controller-naming swap elsewhere
     * in this app (see the NOTE comments in those two files). Do not "fix"
     * it here without also updating the two files and the PHP script tags.
     */
    var ROUTE_CONFIG = {
        'products/store-products': {
            serverAttr: 'data-fc-system-products-server',
            marker: '[data-fc-system-products-server]',
            viewport: 'fill'
        },
        'products/system-products': {
            serverAttr: 'data-fc-store-products-server',
            marker: '[data-fc-store-products-server]',
            viewport: 'fill'
        },
        'products/fence-styles': {
            serverAttr: 'data-fc-fence-styles-server',
            marker: '[data-fc-fence-styles-server]',
            viewport: 'fill'
        },
        gallery: {
            serverAttr: 'data-fc-gallery-server',
            marker: '.fc-gallery-page[data-fc-gallery-server]',
            viewport: 'fill'
        },
        users: {
            serverAttr: 'data-fc-users-server',
            marker: '[data-fc-users-list]',
            viewport: 'fill'
        },
        'users/group-permissions': {
            serverAttr: 'data-fc-group-permissions-server',
            marker: '[data-fc-group-permissions]',
            viewport: 'fill'
        },
        settings: {
            serverAttr: 'data-fc-settings-server',
            marker: '.fc-settings-page',
            viewport: 'fill'
        }
    };
    ROUTE_CONFIG[DEFAULT_ROUTE] = {
        serverAttr: 'data-fc-dashboard-server',
        marker: '[data-fc-dashboard-server]',
        viewport: 'scroll'
    };

    var currentController = null;

    function renderPage(route, pageTitle) {
        if (titleEl) {
            titleEl.textContent = pageTitle;
        }
        updateDocumentTitle(pageTitle);

        if (!contentEl) {
            return;
        }

        contentEl.setAttribute('data-route', route);

        if (currentController && typeof currentController.destroy === 'function') {
            currentController.destroy();
        }
        currentController = null;

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

        var cfg = ROUTE_CONFIG[route];
        if (cfg) {
            setStoreProductsViewportLayout(cfg.viewport === 'fill');
            if (
                contentEl.getAttribute(cfg.serverAttr) === '1' &&
                contentEl.querySelector(cfg.marker)
            ) {
                contentEl.setAttribute('data-route', route);
                if (titleEl) {
                    titleEl.textContent = pageTitle;
                }
                updateDocumentTitle(pageTitle);
                var controller = window.FC.PageRegistry.get(route);
                if (controller) {
                    currentController = controller;
                    if (typeof controller.hydrate === 'function') {
                        controller.hydrate(contentEl);
                    }
                }
                return;
            }
            window.location.href = routeToUrl(route) + (route === DEFAULT_ROUTE ? (window.location.search || '') : '');
            return;
        }

        // Unknown client route → server 404 page.
        window.location.href = routeToUrl(route);
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
        var search = root.querySelector('.fc-sidebar-site__search');
        root.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (menu) {
            menu.hidden = true;
        }
        if (search && search.value) {
            search.value = '';
            filterSiteSwitcher(root, '');
        }
    }

    function filterSiteSwitcher(root, query) {
        var q = String(query || '').trim().toLowerCase();
        var otherSection = root.querySelector('[data-fc-site-section="other"]');
        var items = otherSection
            ? otherSection.querySelectorAll('.fc-sidebar-site__item')
            : [];
        var visibleCount = 0;

        items.forEach(function (item) {
            var haystack = item.getAttribute('data-fc-site-search') || item.textContent || '';
            var match = !q || haystack.indexOf(q) !== -1;
            item.classList.toggle('is-filtered-out', !match);
            if (match) {
                visibleCount += 1;
            }
        });

        if (otherSection) {
            otherSection.classList.toggle('is-filtered-out', q !== '' && visibleCount === 0);
        }

        var empty = root.querySelector('.fc-sidebar-site__empty');
        if (empty) {
            empty.hidden = !q || visibleCount > 0;
        }
    }

    function initSidebarSiteSwitcher() {
        var roots = document.querySelectorAll('[data-fc-sidebar-site-switcher]');
        if (!roots.length) {
            return;
        }

        function getOptions(root) {
            return Array.prototype.slice.call(
                root.querySelectorAll('.fc-sidebar-site__item:not(.is-filtered-out)')
            );
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
            var search = root.querySelector('.fc-sidebar-site__search');
            if (menu) {
                menu.hidden = false;
                // Allow CSS open transition after [hidden] is cleared.
                requestAnimationFrame(function () {
                    root.classList.add('is-open');
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                    if (search) {
                        search.focus();
                        search.select();
                    } else {
                        var active = menu.querySelector('.fc-sidebar-site__item.is-active');
                        if (active && typeof active.scrollIntoView === 'function') {
                            active.scrollIntoView({ block: 'nearest' });
                        }
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
            var search = root.querySelector('.fc-sidebar-site__search');
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
                    if (search) {
                        search.focus();
                        return;
                    }
                    var options = getOptions(root);
                    var activeIndex = options.findIndex(function (el) {
                        return el.classList.contains('is-active');
                    });
                    focusOption(options, activeIndex >= 0 ? activeIndex : 0);
                }
            });
            if (search) {
                search.addEventListener('input', function () {
                    filterSiteSwitcher(root, search.value);
                });
                search.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        focusOption(getOptions(root), 0);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        if (search.value) {
                            search.value = '';
                            filterSiteSwitcher(root, '');
                            return;
                        }
                        closeSiteSwitcherMenu(root);
                        toggle.focus();
                    } else if (e.key === 'Enter') {
                        var options = getOptions(root);
                        if (options.length === 1) {
                            e.preventDefault();
                            options[0].click();
                        }
                    }
                });
                search.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
            if (menu) {
                menu.addEventListener('keydown', function (e) {
                    if (e.target === search) {
                        return;
                    }
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
                        if (index <= 0 && search) {
                            search.focus();
                            return;
                        }
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

    if (sidebarCollapseEl) {
        sidebarCollapseEl.addEventListener('click', function () {
            setSidebarCollapsed(!isSidebarCollapsed());
        });
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
