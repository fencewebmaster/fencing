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
        'console'
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
        console: { debugMode: false },
        consoleDefaults: { debugMode: false },
        consoleFormBound: false,
        consoleSaving: false,
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
        var consoleActions = document.getElementById('fc-settings-header-actions-console');
        var themeDirty = document.getElementById('fc-settings-theme-dirty');
        var brandingDirty = document.getElementById('fc-settings-branding-dirty');
        var fenceColorsDirty = document.getElementById('fc-settings-fence-colors-dirty');
        var catalogDirty = document.getElementById('fc-settings-catalog-dirty');
        var systemDirty = document.getElementById('fc-settings-system-dirty');
        var integrationDirty = document.getElementById('fc-settings-integration-dirty');
        var projectPlanDirty = document.getElementById('fc-settings-project-plan-dirty');
        var themeReset = document.getElementById('fc-theme-reset');
        var brandingReset = document.getElementById('fc-branding-reset');
        var fenceColorsReset = document.getElementById('fc-fence-colors-reset');
        var catalogReset = document.getElementById('fc-catalog-reset');
        var systemReset = document.getElementById('fc-system-reset');
        var integrationReset = document.getElementById('fc-integration-reset');
        var projectPlanReset = document.getElementById('fc-project-plan-reset');

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
        if (consoleActions) {
            consoleActions.classList.toggle('hidden', state.activeTab !== 'console');
            consoleActions.classList.toggle('flex', state.activeTab === 'console');
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
    }

    function switchTab(tabId) {
        state.activeTab = tabId;
        var themePanel = document.getElementById('fc-settings-panel-theme');
        var brandingPanel = document.getElementById('fc-settings-panel-branding');
        var fenceColorsPanel = document.getElementById('fc-settings-panel-fence-colors');
        var catalogPanel = document.getElementById('fc-settings-panel-catalog');
        var systemPanel = document.getElementById('fc-settings-panel-system');
        var integrationPanel = document.getElementById('fc-settings-panel-integration');
        var projectPlanPanel = document.getElementById('fc-settings-panel-project-plan');
        var consolePanel = document.getElementById('fc-settings-panel-console');
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
        if (consolePanel) {
            consolePanel.classList.toggle('hidden', tabId !== 'console');
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

        document.querySelectorAll('[data-fc-settings-tab]').forEach(function (btn) {
            var active = btn.getAttribute('data-fc-settings-tab') === tabId;
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.classList.toggle('bg-white', active);
            btn.classList.toggle('text-slate-900', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('text-slate-600', !active);
        });

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
    }

    function bindSettingsShell() {
        document.querySelectorAll('[data-fc-settings-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                switchTab(btn.getAttribute('data-fc-settings-tab'));
            });
        });
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

        state.console = Object.assign(
            { debugMode: false },
            data.console || {}
        );
        state.consoleDefaults = Object.assign(
            { debugMode: false },
            data.consoleDefaults || {}
        );

        state.themeDirty = false;
        state.brandingDirty = false;
        state.fenceColorsDirty = false;
        state.catalogDirty = false;
        state.systemDirty = false;
        state.integrationDirty = false;
        state.projectPlanItemsDirty = false;
        state.fenceColorsSort = { column: null, direction: 'asc' };
        global.FC.Settings.tabs.fenceColors.tableBound = false;
        state.catalogFormBound = false;
        state.systemFormBound = false;
        state.integrationFormBound = false;
        state.projectPlanFormBound = false;
        state.consoleFormBound = false;
        state.consoleSaving = false;

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
        global.FC.Settings.tabs.console.bind();
        global.FC.Settings.tabs.theme.updatePresetCards();
        global.FC.Settings.tabs.theme.applyLiveTheme();
        global.FC.Settings.tabs.branding.updatePreview();
        global.FC.Settings.tabs.catalog.paint();
        global.FC.Settings.tabs.system.paint();
        global.FC.Settings.tabs.integration.paint();
        global.FC.Settings.tabs.projectPlan.paint();
        if (state.activeTab === 'catalog') {
            global.FC.Settings.tabs.catalog.ensureOptions().then(function () {
                global.FC.Settings.tabs.catalog.paint();
            });
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
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};

    class SettingsPage extends global.FC.PageController {
        hydrate(contentEl) {
            hydrateFromServer(contentEl.querySelector('#fc-settings-root') || contentEl);
        }
    }
    global.FC.PageRegistry.register('settings', new SettingsPage());
})(window);
