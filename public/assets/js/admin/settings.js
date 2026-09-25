/**
 * FC Admin — Settings page.
 */
(function (global) {
    'use strict';

    var settingsImagePreview = new global.FC.components.ImageLightbox({
        bodyOpenClass: 'fc-entries-cart-gallery-open',
        showNav: false,
        imageAltFallback: ''
    });

    var SETTINGS_TABS = [
        'theme',
        'branding',
        'fence-colors',
        'catalog',
        'system',
        'integration',
        'project-plan',
        'seo',
        'console',
        'minify',
        'site-health'
    ];
    var SETTINGS_DEFAULT_TAB = 'theme';
    var SETTINGS_URL_TAB_KEY = 'tab';

    var state = {
        activeTab: 'theme',
        colors: {},
        defaults: {},
        schema: {},
        presets: [],
        activePreset: null,
        selectedPreset: null,
        themeDirty: false,
        branding: {},
        brandingDefaults: {},
        brandingSchema: {},
        brandingDirty: false,
        fenceColors: [],
        fenceColorsDefaults: [],
        fenceColorsDirty: false,
        fenceColorsSort: { column: null, direction: 'asc' },
        catalog: {},
        catalogDefaults: {},
        catalogOrderbyChoices: {},
        catalogCategories: [],
        catalogAttributes: [],
        catalogOptionsError: '',
        catalogDirty: false,
        catalogOptionsLoaded: false,
        catalogFormBound: false,
        catalogCategorySearch: '',
        catalogAttributeSearch: '',
        system: {},
        systemDefaults: {},
        systemDirty: false,
        systemFormBound: false,
        integrations: {},
        integrationsInitial: {},
        integrationsRevision: '',
        integrationDirty: false,
        integrationFormBound: false,
        seo: {},
        seoInitial: {},
        seoDefaults: {},
        seoContext: {},
        seoDirty: false,
        seoFormBound: false,
        console: { debugMode: false },
        consoleDefaults: { debugMode: false },
        consoleFormBound: false,
        consoleSaving: false,
        minify: { css: true, js: true, adminCss: false, adminJs: false },
        minifyDefaults: { css: true, js: true, adminCss: false, adminJs: false },
        minifyTool: { available: false, path: '', reason: '' },
        minifyBound: false,
        minifyBusy: false,
        csrf: ''
    };

    var escapeHtml = global.FC.util.escapeHtml;

    function normalizeSettingsTab(tab) {
        var normalized = String(tab || '')
            .trim()
            .toLowerCase()
            .replace(/_/g, '-');
        if (normalized === 'fence-colors' || normalized === 'fencecolors') {
            normalized = 'fence-colors';
        }
        if (normalized === 'catalog-settings' || normalized === 'catalogsettings') {
            normalized = 'catalog';
        }
        if (normalized === 'integrations') {
            normalized = 'integration';
        }
        if (normalized === 'dev-mode' || normalized === 'devmode' || normalized === 'dev') {
            normalized = 'console';
        }
        if (normalized === 'health' || normalized === 'sitehealth') {
            normalized = 'site-health';
        }
        if (normalized === 'minified' || normalized === 'minify-css-js') {
            normalized = 'minify';
        }
        return SETTINGS_TABS.indexOf(normalized) !== -1 ? normalized : SETTINGS_DEFAULT_TAB;
    }

    function readSettingsTabFromUrl() {
        var page = document.querySelector('.fc-settings-page');
        if (page) {
            var initial = page.getAttribute('data-fc-settings-initial-tab');
            if (initial) {
                return normalizeSettingsTab(initial);
            }
        }

        var params = new URLSearchParams(window.location.search);
        return normalizeSettingsTab(params.get(SETTINGS_URL_TAB_KEY));
    }

    function syncSettingsTabUrl(tabId) {
        var tab = normalizeSettingsTab(tabId);
        var params = new URLSearchParams();
        if (tab !== SETTINGS_DEFAULT_TAB) {
            params.set(SETTINGS_URL_TAB_KEY, tab);
        }

        var search = params.toString();
        var nextUrl = window.location.pathname + (search ? '?' + search : '');
        var currentUrl = window.location.pathname + window.location.search;
        if (nextUrl === currentUrl) {
            return;
        }

        var historyState =
            window.history.state && typeof window.history.state === 'object'
                ? window.history.state
                : {};
        window.history.replaceState(historyState, '', nextUrl);
    }

    var copyFieldButton = new global.FC.components.CopyFieldButton({
        buttonClass: 'fc-settings-field-copy',
        copiedButtonClass: 'is-copied',
        dataAttr: 'data-fc-settings-copy-for',
        iconIdleClass: 'fa-regular fa-copy',
        iconCopiedClass: 'fa-solid fa-check',
        onCopied: function () {
            var T = global.FcAdminToast;
            if (T) {
                T.success('Copied to clipboard');
            }
        }
    });

    function buildFieldCopyButton(fieldId, label) {
        return copyFieldButton.markup(fieldId, label);
    }

    function copyFieldToClipboard(control, btn) {
        copyFieldButton.copy(control, btn);
    }

    function bindSettingsCopyButtons(root) {
        var scope = root || document;
        if (scope.getAttribute && scope.getAttribute('data-fc-settings-copy-bound') === '1') {
            return;
        }
        if (scope.setAttribute) {
            scope.setAttribute('data-fc-settings-copy-bound', '1');
        }

        scope.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-fc-settings-copy-for]');
            if (!btn || (root && !root.contains(btn))) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            var fieldId = btn.getAttribute('data-fc-settings-copy-for');
            if (!fieldId) {
                return;
            }
            var control = document.getElementById(fieldId);
            copyFieldToClipboard(control, btn);
        });
    }

    /** Fallback markup for a settings preview thumbnail when no image is set. */
    function buildImagePlaceholderHtml() {
        return '<i class="fa-solid fa-image" aria-hidden="true"></i>';
    }

    /** <img> markup for a settings preview thumbnail, clickable to view larger (project plan items, integration logos). */
    function buildViewableImgHtml(url, label) {
        var safeLabel = escapeHtml(String(label || ''));
        return (
            '<img src="' +
            escapeHtml(url) +
            '" alt="" loading="lazy" decoding="async" tabindex="0" role="button" data-fc-settings-image-view data-fc-settings-image-view-label="' +
            safeLabel +
            '" aria-label="View larger image' +
            (safeLabel ? ' for ' + safeLabel : '') +
            '" />'
        );
    }

    function closeSettingsImagePreview() {
        settingsImagePreview.close();
    }

    function openSettingsImagePreview(url, label) {
        if (!url) {
            return;
        }
        var caption = String(label || '').trim();
        settingsImagePreview.open([{ url: url, caption: caption }], 0, {
            ariaLabel: caption || 'Image preview',
            focusClose: false
        });
    }

    function bindSettingsImagePreviewTriggers(root) {
        var scope = root || document;
        if (scope.getAttribute && scope.getAttribute('data-fc-settings-image-view-bound') === '1') {
            return;
        }
        if (scope.setAttribute) {
            scope.setAttribute('data-fc-settings-image-view-bound', '1');
        }

        function triggerFor(target) {
            var url = target.currentSrc || target.src || '';
            openSettingsImagePreview(url, target.getAttribute('data-fc-settings-image-view-label') || '');
        }

        scope.addEventListener('click', function (e) {
            var target = e.target.closest('[data-fc-settings-image-view]');
            if (!target || (root && !root.contains(target))) {
                return;
            }
            e.preventDefault();
            triggerFor(target);
        });

        scope.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            var target = e.target.closest('[data-fc-settings-image-view]');
            if (!target || (root && !root.contains(target))) {
                return;
            }
            e.preventDefault();
            triggerFor(target);
        });
    }


    var FLASH_KEY = 'fc-settings-save-flash';

    var flash = new global.FC.util.FlashMessage({
        storageKey: FLASH_KEY,
        noticeSelector: '[data-fc-settings-notice]',
        defaultRoot: function () {
            return document.getElementById('fc-settings-root');
        }
    });

    function consumeFlash() {
        return flash.consume();
    }

    function showHeaderNotice(root, flashData) {
        flash.renderInto(root, flashData);
    }

    var SETTINGS_IO_TOAST_ID = 'fc-settings-io';

    function reloadWithNotice(message, type) {
        flash.set(message, type === 'error' ? 'error' : 'success');
        try {
            var next = new URL(window.location.href);
            window.location.assign(next.pathname + next.search);
        } catch (e) {
            window.location.reload();
        }
    }

    function bindSettingsIoMenu() {
        var dropdown = document.querySelector('[data-fc-settings-io-dropdown]');
        if (!dropdown || dropdown.dataset.fcBound === '1') {
            return;
        }
        dropdown.dataset.fcBound = '1';

        var toggle = dropdown.querySelector('[data-fc-settings-io-toggle]');
        var panel = dropdown.querySelector('.fc-products-download-dropdown__panel');
        var exportTrigger = dropdown.querySelector('[data-fc-settings-export]');
        var importTrigger = dropdown.querySelector('[data-fc-settings-import]');
        var importInput = dropdown.querySelector('[data-fc-settings-import-input]');

        function closeMenu() {
            if (!panel || !toggle) {
                return;
            }
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            dropdown.classList.remove('is-open');
            panel.style.left = '';
            panel.style.top = '';
            panel.style.position = '';
            panel.style.right = '';
            panel.style.zIndex = '';
        }

        function positionMenu() {
            if (!toggle || !panel || panel.hidden) {
                return;
            }
            var rect = toggle.getBoundingClientRect();
            var gap = 6;
            panel.style.position = 'fixed';
            panel.style.zIndex = '80';
            panel.style.left = Math.round(rect.left) + 'px';
            panel.style.top = Math.round(rect.bottom + gap) + 'px';
            panel.style.right = 'auto';

            var panelRect = panel.getBoundingClientRect();
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

            if (panelRect.right > viewportWidth - 8) {
                panel.style.left = Math.max(8, viewportWidth - panelRect.width - 8) + 'px';
            }
            if (panelRect.bottom > viewportHeight - 8) {
                var aboveTop = rect.top - gap - panelRect.height;
                if (aboveTop >= 8) {
                    panel.style.top = aboveTop + 'px';
                }
            }
        }

        function openMenu() {
            if (!panel || !toggle) {
                return;
            }
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            dropdown.classList.add('is-open');
            positionMenu();
        }

        function exportSettings() {
            closeMenu();
            var link = document.createElement('a');
            link.href = fcApiUrl('settings', 'action=export');
            link.download = 'fc-settings-export.json';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }

        function importSettingsFile(file) {
            if (!file) {
                return;
            }
            var name = String(file.name || '').toLowerCase();
            if (!name.endsWith('.json')) {
                global.FC.util.toast('error', 'Only .json files can be imported.', SETTINGS_IO_TOAST_ID);
                return;
            }
            if (!state.csrf) {
                global.FC.util.toast('error', 'Missing security token. Refresh and try again.', SETTINGS_IO_TOAST_ID);
                return;
            }

            closeMenu();
            global.FC.util.toast('saving', 'Importing settings…', SETTINGS_IO_TOAST_ID);

            var formData = new FormData();
            formData.append('csrf', state.csrf);
            formData.append('file', file);

            fetch(fcApiUrl('settings', 'action=import'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: formData
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, error: 'Invalid server response.' };
                    });
                })
                .then(function (body) {
                    if ((body.applied || 0) > 0) {
                        reloadWithNotice(body.message || 'Settings imported.', body.ok ? 'success' : 'error');
                        return;
                    }
                    throw new Error(body.error || body.message || 'Could not import settings.');
                })
                .catch(function (error) {
                    global.FC.util.toast(
                        'error',
                        (error && error.message) || 'Could not import settings.',
                        SETTINGS_IO_TOAST_ID
                    );
                })
                .then(function () {
                    if (importInput) {
                        importInput.value = '';
                    }
                });
        }

        if (toggle && panel) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (panel.hidden) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });
            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });
            document.addEventListener('click', function () {
                closeMenu();
            });
            window.addEventListener('resize', function () {
                if (!panel.hidden) {
                    positionMenu();
                }
            });
            window.addEventListener(
                'scroll',
                function () {
                    if (!panel.hidden) {
                        positionMenu();
                    }
                },
                true
            );
        }

        if (exportTrigger) {
            exportTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                exportSettings();
            });
        }

        if (importTrigger && importInput) {
            importTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                closeMenu();
                importInput.click();
            });
            importInput.addEventListener('change', function () {
                var file = importInput.files && importInput.files[0] ? importInput.files[0] : null;
                importSettingsFile(file);
            });
        }
    }

    function updateHeaderActions() {
        var themeActions = document.getElementById('fc-settings-header-actions-theme');
        var brandingActions = document.getElementById('fc-settings-header-actions-branding');
        var fenceColorsActions = document.getElementById('fc-settings-header-actions-fence-colors');
        var catalogActions = document.getElementById('fc-settings-header-actions-catalog');
        var systemActions = document.getElementById('fc-settings-header-actions-system');
        var integrationActions = document.getElementById('fc-settings-header-actions-integration');
        var projectPlanActions = document.getElementById('fc-settings-header-actions-project-plan');
        var seoActions = document.getElementById('fc-settings-header-actions-seo');
        var consoleActions = document.getElementById('fc-settings-header-actions-console');
        var minifyActions = document.getElementById('fc-settings-header-actions-minify');
        var siteHealthActions = document.getElementById('fc-settings-header-actions-site-health');
        var themeDirty = document.getElementById('fc-settings-theme-dirty');
        var brandingDirty = document.getElementById('fc-settings-branding-dirty');
        var fenceColorsDirty = document.getElementById('fc-settings-fence-colors-dirty');
        var catalogDirty = document.getElementById('fc-settings-catalog-dirty');
        var systemDirty = document.getElementById('fc-settings-system-dirty');
        var integrationDirty = document.getElementById('fc-settings-integration-dirty');
        var projectPlanDirty = document.getElementById('fc-settings-project-plan-dirty');
        var seoDirty = document.getElementById('fc-settings-seo-dirty');
        var themeReset = document.getElementById('fc-theme-reset');
        var brandingReset = document.getElementById('fc-branding-reset');
        var fenceColorsReset = document.getElementById('fc-fence-colors-reset');
        var catalogReset = document.getElementById('fc-catalog-reset');
        var systemReset = document.getElementById('fc-system-reset');
        var integrationReset = document.getElementById('fc-integration-reset');
        var projectPlanReset = document.getElementById('fc-project-plan-reset');
        var seoReset = document.getElementById('fc-seo-reset');

        if (themeActions) {
            themeActions.classList.toggle('hidden', state.activeTab !== 'theme');
            themeActions.classList.toggle('flex', state.activeTab === 'theme');
        }
        if (brandingActions) {
            brandingActions.classList.toggle('hidden', state.activeTab !== 'branding');
            brandingActions.classList.toggle('flex', state.activeTab === 'branding');
        }
        if (fenceColorsActions) {
            fenceColorsActions.classList.toggle('hidden', state.activeTab !== 'fence-colors');
            fenceColorsActions.classList.toggle('flex', state.activeTab === 'fence-colors');
        }
        if (catalogActions) {
            catalogActions.classList.toggle('hidden', state.activeTab !== 'catalog');
            catalogActions.classList.toggle('flex', state.activeTab === 'catalog');
        }
        if (systemActions) {
            systemActions.classList.toggle('hidden', state.activeTab !== 'system');
            systemActions.classList.toggle('flex', state.activeTab === 'system');
        }
        if (integrationActions) {
            integrationActions.classList.toggle('hidden', state.activeTab !== 'integration');
            integrationActions.classList.toggle('flex', state.activeTab === 'integration');
        }
        if (projectPlanActions) {
            projectPlanActions.classList.toggle('hidden', state.activeTab !== 'project-plan');
            projectPlanActions.classList.toggle('flex', state.activeTab === 'project-plan');
        }
        if (seoActions) {
            seoActions.classList.toggle('hidden', state.activeTab !== 'seo');
            seoActions.classList.toggle('flex', state.activeTab === 'seo');
        }
        if (consoleActions) {
            consoleActions.classList.toggle('hidden', state.activeTab !== 'console');
            consoleActions.classList.toggle('flex', state.activeTab === 'console');
        }
        if (minifyActions) {
            minifyActions.classList.toggle('hidden', state.activeTab !== 'minify');
            minifyActions.classList.toggle('flex', state.activeTab === 'minify');
        }
        if (siteHealthActions) {
            siteHealthActions.classList.toggle('hidden', state.activeTab !== 'site-health');
            siteHealthActions.classList.toggle('flex', state.activeTab === 'site-health');
        }
        if (themeDirty) {
            themeDirty.classList.toggle('hidden', state.activeTab !== 'theme' || !state.themeDirty);
        }
        if (brandingDirty) {
            brandingDirty.classList.toggle('hidden', state.activeTab !== 'branding' || !state.brandingDirty);
        }
        if (fenceColorsDirty) {
            fenceColorsDirty.classList.toggle(
                'hidden',
                state.activeTab !== 'fence-colors' || !state.fenceColorsDirty
            );
        }
        if (catalogDirty) {
            catalogDirty.classList.toggle('hidden', state.activeTab !== 'catalog' || !state.catalogDirty);
        }
        if (systemDirty) {
            systemDirty.classList.toggle('hidden', state.activeTab !== 'system' || !state.systemDirty);
        }
        if (integrationDirty) {
            integrationDirty.classList.toggle(
                'hidden',
                state.activeTab !== 'integration' || !state.integrationDirty
            );
        }
        if (projectPlanDirty) {
            projectPlanDirty.classList.toggle(
                'hidden',
                state.activeTab !== 'project-plan' || !state.projectPlanItemsDirty
            );
        }
        if (seoDirty) {
            seoDirty.classList.toggle('hidden', state.activeTab !== 'seo' || !state.seoDirty);
        }
        if (themeReset) {
            themeReset.disabled = !state.themeDirty;
        }
        if (brandingReset) {
            brandingReset.disabled = !state.brandingDirty;
        }
        if (fenceColorsReset) {
            fenceColorsReset.disabled = !state.fenceColorsDirty;
        }
        if (catalogReset) {
            catalogReset.disabled = !state.catalogDirty;
        }
        if (systemReset) {
            systemReset.disabled = !state.systemDirty;
        }
        if (integrationReset) {
            integrationReset.disabled = !state.integrationDirty;
        }
        if (projectPlanReset) {
            projectPlanReset.disabled = !state.projectPlanItemsDirty;
        }
        if (seoReset) {
            seoReset.disabled = !state.seoDirty;
        }

        var dirtyTabs = {
            theme: state.themeDirty,
            branding: state.brandingDirty,
            'fence-colors': state.fenceColorsDirty,
            catalog: state.catalogDirty,
            system: state.systemDirty,
            integration: state.integrationDirty,
            'project-plan': state.projectPlanItemsDirty,
            seo: state.seoDirty
        };
        document.querySelectorAll('[data-fc-settings-tab-dirty]').forEach(function (dot) {
            dot.hidden = !dirtyTabs[dot.getAttribute('data-fc-settings-tab-dirty')];
        });
    }

    /** Puts the open tab's label and description in the section header. */
    function paintSectionHeader(btn) {
        var title = document.getElementById('fc-settings-section-title');
        var desc = document.getElementById('fc-settings-section-desc');
        var label = btn ? btn.querySelector('.fc-settings-nav__label') : null;
        if (title && label) {
            title.textContent = label.textContent;
        }
        if (desc && btn) {
            desc.textContent = btn.getAttribute('data-fc-settings-tab-description') || '';
        }
    }

    /** When the tabs are a sideways strip rather than the rail, centre the open one in it. */
    function revealActiveTab(btn) {
        var nav = btn ? btn.closest('.fc-settings-nav') : null;
        if (!nav || nav.scrollWidth <= nav.clientWidth) {
            return;
        }
        var navRect = nav.getBoundingClientRect();
        var btnRect = btn.getBoundingClientRect();
        nav.scrollLeft += btnRect.left + btnRect.width / 2 - (navRect.left + navRect.width / 2);
    }

    function switchTab(tabId) {
        var changed = state.activeTab !== tabId;
        state.activeTab = tabId;
        var themePanel = document.getElementById('fc-settings-panel-theme');
        var brandingPanel = document.getElementById('fc-settings-panel-branding');
        var fenceColorsPanel = document.getElementById('fc-settings-panel-fence-colors');
        var catalogPanel = document.getElementById('fc-settings-panel-catalog');
        var systemPanel = document.getElementById('fc-settings-panel-system');
        var integrationPanel = document.getElementById('fc-settings-panel-integration');
        var projectPlanPanel = document.getElementById('fc-settings-panel-project-plan');
        var seoPanel = document.getElementById('fc-settings-panel-seo');
        var consolePanel = document.getElementById('fc-settings-panel-console');
        var minifyPanel = document.getElementById('fc-settings-panel-minify');
        var siteHealthPanel = document.getElementById('fc-settings-panel-site-health');
        var preview = document.getElementById('fc-settings-preview');
        var layout = document.getElementById('fc-settings-layout');
        var showPreview = tabId === 'branding';

        if (themePanel) {
            themePanel.classList.toggle('hidden', tabId !== 'theme');
        }
        if (brandingPanel) {
            brandingPanel.classList.toggle('hidden', tabId !== 'branding');
        }
        if (fenceColorsPanel) {
            fenceColorsPanel.classList.toggle('hidden', tabId !== 'fence-colors');
        }
        if (catalogPanel) {
            catalogPanel.classList.toggle('hidden', tabId !== 'catalog');
        }
        if (systemPanel) {
            systemPanel.classList.toggle('hidden', tabId !== 'system');
        }
        if (integrationPanel) {
            integrationPanel.classList.toggle('hidden', tabId !== 'integration');
        }
        if (projectPlanPanel) {
            projectPlanPanel.classList.toggle('hidden', tabId !== 'project-plan');
        }
        if (seoPanel) {
            seoPanel.classList.toggle('hidden', tabId !== 'seo');
        }
        if (consolePanel) {
            consolePanel.classList.toggle('hidden', tabId !== 'console');
        }
        if (minifyPanel) {
            minifyPanel.classList.toggle('hidden', tabId !== 'minify');
        }
        if (siteHealthPanel) {
            siteHealthPanel.classList.toggle('hidden', tabId !== 'site-health');
        }
        if (layout) {
            layout.classList.toggle('lg:grid-cols-2', showPreview);
        }
        if (preview) {
            preview.classList.toggle('hidden', !showPreview);
            if (showPreview) {
                preview.innerHTML = global.FC.Settings.tabs.branding.renderPreview();
                global.FC.Settings.tabs.branding.updatePreview();
            }
        }

        var activeBtn = null;
        document.querySelectorAll('[data-fc-settings-tab]').forEach(function (btn) {
            var active = btn.getAttribute('data-fc-settings-tab') === tabId;
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.setAttribute('tabindex', active ? '0' : '-1');
            btn.classList.toggle('is-active', active);
            if (active) {
                activeBtn = btn;
            }
        });
        paintSectionHeader(activeBtn);
        revealActiveTab(activeBtn);

        // A new section opens at its top, not wherever the last one was scrolled to.
        var scroller = document.querySelector('[data-fc-settings-scroll]');
        if (changed && scroller) {
            scroller.scrollTop = 0;
        }

        updateHeaderActions();
        syncSettingsTabUrl(tabId);

        if (tabId === 'catalog') {
            global.FC.Settings.tabs.catalog.ensureOptions().then(function () {
                global.FC.Settings.tabs.catalog.paint();
            });
        }
        if (tabId === 'system') {
            global.FC.Settings.tabs.system.paint();
        }
        if (tabId === 'project-plan') {
            // The Low Stock editor could not size itself while this panel was hidden.
            global.FC.Settings.tabs.projectPlan.mountEditor();
        }
        if (tabId === 'site-health') {
            global.FC.Settings.tabs.siteHealth.ensureRun();
        }
        if (tabId === 'minify' && changed) {
            // The dates come from disk, and a CLI build may have run since the page loaded.
            global.FC.Settings.tabs.minify.refresh();
        }
    }

    function bindSettingsShell() {
        document.querySelectorAll('[data-fc-settings-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                switchTab(btn.getAttribute('data-fc-settings-tab'));
            });
        });

        var list = document.querySelector('[data-fc-settings-tablist]');
        if (!list || list.getAttribute('data-fc-settings-keys-bound') === '1') {
            return;
        }
        list.setAttribute('data-fc-settings-keys-bound', '1');

        // Arrows move focus only; Enter/Space opens the tab, so arrowing past Catalog or Site Health fetches nothing.
        list.addEventListener('keydown', function (e) {
            var current = e.target.closest('[data-fc-settings-tab]');
            if (!current) {
                return;
            }
            var tabs = Array.prototype.slice.call(list.querySelectorAll('[data-fc-settings-tab]'));
            var index = tabs.indexOf(current);
            var next = null;
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                next = tabs[(index + 1) % tabs.length];
            } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                next = tabs[(index - 1 + tabs.length) % tabs.length];
            } else if (e.key === 'Home') {
                next = tabs[0];
            } else if (e.key === 'End') {
                next = tabs[tabs.length - 1];
            }
            if (next) {
                e.preventDefault();
                next.focus();
            }
        });

        // The strip hides its scrollbar, so a mouse wheel scrolls it sideways; the rail keeps its own scrolling.
        var nav = list.closest('.fc-settings-nav');
        if (nav) {
            nav.addEventListener(
                'wheel',
                function (e) {
                    if (nav.scrollWidth <= nav.clientWidth || Math.abs(e.deltaX) >= Math.abs(e.deltaY)) {
                        return;
                    }
                    nav.scrollLeft += e.deltaY;
                    e.preventDefault();
                },
                { passive: false }
            );
        }

        // entries.css picks rail or strip from the page's width, which the sidebar's state changes too, so read the layout.
        var syncLayout = function () {
            var vertical = global.getComputedStyle(list).flexDirection === 'column';
            list.setAttribute('aria-orientation', vertical ? 'vertical' : 'horizontal');
            revealActiveTab(list.querySelector('[data-fc-settings-tab].is-active'));
        };
        syncLayout();
        if (global.ResizeObserver) {
            new global.ResizeObserver(syncLayout).observe(list);
        }
    }



    function readBootstrapData() {
        var el = document.getElementById('fc-settings-bootstrap');
        if (!el || !el.textContent) {
            return null;
        }
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return null;
        }
    }

    function applyBootstrapState(data) {
        state.colors = Object.assign({}, data.colors || {});
        state.defaults = Object.assign({}, data.defaults || {});
        state.schema = data.schema || {};
        state.presets = data.presets || [];
        state.activePreset = data.activePreset || null;
        state.selectedPreset = data.selectedPreset || state.activePreset;

        state.branding = Object.assign({}, data.branding || {});
        state.brandingDefaults = Object.assign({}, data.brandingDefaults || {});
        state.brandingSchema = data.brandingSchema || {};

        state.fenceColors = global.FC.Settings.tabs.fenceColors.clone(data.fenceColors || []);
        state.fenceColorsDefaults = global.FC.Settings.tabs.fenceColors.clone(data.fenceColorsDefaults || []);

        state.catalog = global.FC.Settings.tabs.catalog.clone(data.catalog || {});
        state.catalogDefaults = global.FC.Settings.tabs.catalog.clone(data.catalogDefaults || {});
        state.catalogOrderbyChoices = data.catalogOrderbyChoices || {};
        state.catalogCategories = data.catalogCategories || [];
        state.catalogAttributes = data.catalogAttributes || [];
        state.catalogOptionsError = data.catalogOptionsError || '';
        state.catalogOptionsLoaded =
            state.catalogCategories.length > 0 ||
            state.catalogAttributes.length > 0 ||
            !!state.catalogOptionsError;
        state.catalogCategorySearch = '';
        state.catalogAttributeSearch = '';

        state.system = Object.assign({}, data.system || {});
        state.systemDefaults = Object.assign({}, data.systemDefaults || {});

        state.integrations = global.FC.Settings.tabs.integration.clone(data.integrations || {});
        state.integrationsInitial = global.FC.Settings.tabs.integration.clone(
            data.integrationsInitial || data.integrations || {}
        );
        state.integrationsRevision = data.integrationsRevision || '';
        state.csrf = data.csrf || '';

        state.projectPlanItems = global.FC.Settings.tabs.projectPlan.clone(data.projectPlanItems || []);
        state.projectPlanItemsDefaults = global.FC.Settings.tabs.projectPlan.clone(data.projectPlanDefaults || []);
        state.projectPlanStock = Object.assign({}, data.projectPlanStock || {});
        state.projectPlanStockDefaults = Object.assign({}, data.projectPlanStockDefaults || {});

        state.seo = global.FC.Settings.tabs.seo.clone(data.seo || {});
        state.seoInitial = global.FC.Settings.tabs.seo.clone(data.seo || {});
        state.seoDefaults = global.FC.Settings.tabs.seo.clone(data.seoDefaults || {});
        state.seoContext = Object.assign({}, data.seoContext || {});

        state.console = Object.assign(
            { debugMode: false },
            data.console || {}
        );
        state.consoleDefaults = Object.assign(
            { debugMode: false },
            data.consoleDefaults || {}
        );
        state.minify = Object.assign({ css: true, js: true, adminCss: false, adminJs: false }, data.minify || {});
        state.minifyDefaults = Object.assign({ css: true, js: true, adminCss: false, adminJs: false }, data.minifyDefaults || {});
        state.minifyTool = Object.assign({ available: false, path: '', reason: '' }, data.minifyTool || {});

        state.themeDirty = false;
        state.brandingDirty = false;
        state.fenceColorsDirty = false;
        state.catalogDirty = false;
        state.systemDirty = false;
        state.integrationDirty = false;
        state.projectPlanItemsDirty = false;
        state.seoDirty = false;
        state.fenceColorsSort = { column: null, direction: 'asc' };
        global.FC.Settings.tabs.fenceColors.tableBound = false;
        state.catalogFormBound = false;
        state.systemFormBound = false;
        state.integrationFormBound = false;
        state.projectPlanFormBound = false;
        state.seoFormBound = false;
        state.consoleFormBound = false;
        state.consoleSaving = false;
        state.minifyBound = false;
        state.minifyBusy = false;

        state.activeTab = normalizeSettingsTab(data.activeTab || readSettingsTabFromUrl());
        syncSettingsTabUrl(state.activeTab);
    }

    function hydrateFromServer(container) {
        if (!container || !container.querySelector('#fc-settings-layout')) {
            return Promise.resolve(false);
        }

        var data = readBootstrapData();
        if (!data) {
            return Promise.resolve(false);
        }

        applyBootstrapState(data);
        bindSettingsShell();
        bindSettingsCopyButtons(container);
        bindSettingsImagePreviewTriggers(container);
        bindSettingsIoMenu();
        global.FC.Settings.tabs.theme.bind();
        global.FC.Settings.tabs.branding.bind();
        global.FC.Settings.tabs.fenceColors.bind();
        global.FC.Settings.tabs.catalog.bind();
        global.FC.Settings.tabs.system.bind();
        global.FC.Settings.tabs.integration.bind();
        global.FC.Settings.tabs.projectPlan.bind();
        global.FC.Settings.tabs.seo.bind();
        global.FC.Settings.tabs.console.bind();
        global.FC.Settings.tabs.minify.bind();
        global.FC.Settings.tabs.siteHealth.bind();
        global.FC.Settings.tabs.theme.updatePresetCards();
        global.FC.Settings.tabs.theme.applyLiveTheme();
        global.FC.Settings.tabs.branding.updatePreview();
        global.FC.Settings.tabs.catalog.paint();
        global.FC.Settings.tabs.system.paint();
        global.FC.Settings.tabs.integration.paint();
        global.FC.Settings.tabs.projectPlan.paint();
        global.FC.Settings.tabs.seo.paint();
        if (state.activeTab === 'catalog') {
            global.FC.Settings.tabs.catalog.ensureOptions().then(function () {
                global.FC.Settings.tabs.catalog.paint();
            });
        }
        if (state.activeTab === 'site-health') {
            global.FC.Settings.tabs.siteHealth.ensureRun();
        }
        container.removeAttribute('aria-busy');
        // Show flash notice if present (from previous save)
        try {
            showHeaderNotice(container, consumeFlash());
        } catch (e) {
            /* ignore */
        }

        return Promise.resolve(true);
    }

    // Shared access point for extracted tab modules (e.g. pages/tabs/system-tab.js)
    // that need the shell's mutable state, flash instance, and header-actions repaint.
    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.state = state;
    global.FC.Settings.flash = flash;
    global.FC.Settings.updateHeaderActions = updateHeaderActions;
    global.FC.Settings.buildFieldCopyButton = buildFieldCopyButton;
    global.FC.Settings.buildViewableImgHtml = buildViewableImgHtml;
    global.FC.Settings.buildImagePlaceholderHtml = buildImagePlaceholderHtml;
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};

    class SettingsPage extends global.FC.PageController {
        hydrate(contentEl) {
            hydrateFromServer(contentEl.querySelector('#fc-settings-root') || contentEl);
        }
    }
    global.FC.PageRegistry.register('settings', new SettingsPage());
})(window);
