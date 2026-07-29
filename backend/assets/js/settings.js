/**
 * FC Admin — Settings page.
 */
(function (global) {
    'use strict';

    var API_THEME = fcApiUrl('settings', 'action=theme');
    var API_BRANDING = fcApiUrl('settings', 'action=branding');
    var API_FENCE_COLORS = fcApiUrl('settings', 'action=fence-colors');
    var API_CATALOG = fcApiUrl('settings', 'action=catalog');
    var API_SYSTEM = fcApiUrl('settings', 'action=system');
    var API_INTEGRATIONS = fcApiUrl('settings', 'action=integrations');
    var API_CLOUDFLARE_VERIFY = fcApiUrl('settings', 'action=cloudflare-verify');
    var API_DEV_CONSOLE = fcApiUrl('settings', 'action=dev-console');
    var TOAST_THEME = 'fc-theme-save';
    var TOAST_BRANDING = 'fc-branding-save';
    var TOAST_FENCE_COLORS = 'fc-fence-colors-save';
    var TOAST_CATALOG = 'fc-catalog-save';
    var TOAST_SYSTEM = 'fc-system-save';
    var TOAST_INTEGRATIONS = 'fc-integrations-save';
    var TOAST_CLOUDFLARE_VERIFY = 'fc-cloudflare-verify';

    var SETTINGS_TABS = [
        'theme',
        'branding',
        'fence-colors',
        'catalog',
        'system',
        'integration',
        'dev-mode'
    ];
    var SETTINGS_DEFAULT_TAB = 'theme';
    var SETTINGS_URL_TAB_KEY = 'tab';

    var BRANDING_FIELD_ORDER = ['logo', 'appName', 'tagline', 'version'];

    var FENCE_COLOR_SORT_COLUMNS = [
        { id: 'slug', label: 'Slug' },
        { id: 'label', label: 'Label' },
        { id: 'subLabel', label: 'Sub label' },
        { id: 'color', label: 'Color' },
        { id: 'image', label: 'Image' }
    ];

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
        csrf: ''
    };

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

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
        if (normalized === 'devmode' || normalized === 'dev') {
            normalized = 'dev-mode';
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

    function toast(kind, message, toastId) {
        var T = global.FcAdminToast;
        if (!T) {
            return;
        }
        toastId = toastId || TOAST_THEME;
        if (kind === 'saving') {
            T.loading(message, toastId);
            return;
        }
        T.dismiss(toastId);
        if (kind === 'ok') {
            T.success(message);
        } else if (kind === 'error') {
            T.error(message);
        }
    }

    function buildFieldCopyButton(fieldId, label) {
        return (
            '<button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="' +
            escapeHtml(fieldId) +
            '" aria-label="Copy ' +
            escapeHtml(label) +
            '" title="Copy to clipboard">' +
            '<i class="fa-regular fa-copy" aria-hidden="true"></i></button>'
        );
    }

    function showCopyFeedback(btn) {
        if (!btn) {
            return;
        }
        var icon = btn.querySelector('i');
        if (!icon) {
            return;
        }
        icon.className = 'fa-solid fa-check';
        btn.classList.add('is-copied');
        window.setTimeout(function () {
            icon.className = 'fa-regular fa-copy';
            btn.classList.remove('is-copied');
        }, 1500);
    }

    function copyFieldToClipboard(control, btn) {
        if (!control) {
            return;
        }

        var text = String(control.value != null ? control.value : '');

        function onCopied() {
            showCopyFeedback(btn);
            var T = global.FcAdminToast;
            if (T && text.trim()) {
                T.success('Copied to clipboard');
            }
        }

        function fallbackCopy() {
            try {
                control.focus();
                control.select();
                if (typeof control.setSelectionRange === 'function') {
                    control.setSelectionRange(0, text.length);
                }
                if (document.execCommand('copy')) {
                    onCopied();
                    return;
                }
            } catch (err) {
                /* fall through */
            }
            var T = global.FcAdminToast;
            if (T) {
                T.error('Could not copy to clipboard');
            }
        }

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text).then(onCopied).catch(fallbackCopy);
            return;
        }

        fallbackCopy();
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

    function applyLiveTheme() {
        if (global.FcTheme && typeof global.FcTheme.apply === 'function') {
            global.FcTheme.apply(state.colors);
        }
    }

    function setThemeDirty(isDirty) {
        state.themeDirty = !!isDirty;
        updateHeaderActions();
    }

    function setBrandingDirty(isDirty) {
        state.brandingDirty = !!isDirty;
        updateHeaderActions();
    }

    function setFenceColorsDirty(isDirty) {
        state.fenceColorsDirty = !!isDirty;
        updateHeaderActions();
    }

    function getAppBase() {
        return (document.body && document.body.getAttribute('data-fc-app-base')) || '..';
    }

    function brandingAssetUrl(path) {
        var value = String(path || '').trim();
        if (!value) {
            return '';
        }
        if (/^https?:\/\//i.test(value) || /^data:/i.test(value) || value.indexOf('//') === 0) {
            return value;
        }
        var base = getAppBase().replace(/\/+$/, '');
        return base + '/' + value.replace(/^\/+/, '');
    }

    function updateBrandingLogoPreview() {
        var preview = document.getElementById('fc-branding-logo-preview');
        var sidebarPreview = document.getElementById('fc-branding-preview-logo');
        var logoPath = String(state.branding.logo || '').trim();
        var logoUrl = brandingAssetUrl(logoPath);

        [preview, sidebarPreview].forEach(function (el) {
            if (!el) {
                return;
            }
            el.classList.toggle('fc-settings-branding-logo__preview--empty', !logoUrl);
            if (logoUrl) {
                el.style.setProperty('--fc-branding-logo-preview', 'url(' + logoUrl + ')');
            } else {
                el.style.removeProperty('--fc-branding-logo-preview');
            }
        });
    }

    function updateBrandingPreview() {
        var titleEl = document.getElementById('fc-branding-preview-title');
        var taglineEl = document.getElementById('fc-branding-preview-tagline');
        var footerNameEl = document.getElementById('fc-branding-preview-footer-name');
        var versionEl = document.getElementById('fc-branding-preview-version');

        var appName = state.branding.appName || 'Fencing Calculator';
        var tagline = state.branding.tagline || '';
        var version = state.branding.version || '';

        if (titleEl) {
            titleEl.textContent = appName;
        }
        if (footerNameEl) {
            footerNameEl.textContent = appName;
        }
        if (taglineEl) {
            taglineEl.textContent = tagline;
        }
        if (versionEl) {
            versionEl.textContent = version;
        }
        updateBrandingLogoPreview();
    }

    function renderTabBar() {
        var tabs = [
            { id: 'theme', label: 'Theme' },
            { id: 'branding', label: 'Branding' },
            { id: 'fence-colors', label: 'Fence colors' },
            { id: 'catalog', label: 'Catalog' },
            { id: 'system', label: 'System' },
            { id: 'integration', label: 'Integration' },
            { id: 'dev-mode', label: 'Dev Mode' }
        ];

        return (
            '<div class="flex flex-wrap rounded-lg bg-slate-200/80 p-1" role="tablist" aria-label="Settings sections">' +
            tabs
                .map(function (tab) {
                    var active = state.activeTab === tab.id;
                    return (
                        '<button type="button" role="tab" data-fc-settings-tab="' +
                        escapeHtml(tab.id) +
                        '" aria-selected="' +
                        (active ? 'true' : 'false') +
                        '" class="rounded-md px-4 py-2 text-sm font-medium transition ' +
                        (active
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-600 hover:text-slate-900') +
                        '">' +
                        escapeHtml(tab.label) +
                        '</button>'
                    );
                })
                .join('') +
            '</div>'
        );
    }

    function renderSettingsHeader() {
        var btn = global.FcAdminBtn || {};
        var btnSecondary = btn.secondary || 'btn btn-sm btn-dark fw-semibold';
        var btnPrimary = btn.primary || 'btn btn-sm btn-orange fw-semibold';

        return (
            '<div class="fc-admin-sticky-header sticky top-0 z-20 flex shrink-0 flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 py-4 sm:px-6">' +
            '<div class="flex min-w-0 flex-wrap items-center gap-3">' +
            renderTabBar() +
            '<span id="fc-settings-theme-dirty" class="' +
            (state.activeTab === 'theme' && state.themeDirty ? '' : 'hidden ') +
            'text-xs font-medium text-amber-600">Unsaved changes</span>' +
            '<span id="fc-settings-branding-dirty" class="' +
            (state.activeTab === 'branding' && state.brandingDirty ? '' : 'hidden ') +
            'text-xs font-medium text-amber-600">Unsaved changes</span>' +
            '<span id="fc-settings-fence-colors-dirty" class="' +
            (state.activeTab === 'fence-colors' && state.fenceColorsDirty ? '' : 'hidden ') +
            'text-xs font-medium text-amber-600">Unsaved changes</span>' +
            '<span id="fc-settings-catalog-dirty" class="' +
            (state.activeTab === 'catalog' && state.catalogDirty ? '' : 'hidden ') +
            'text-xs font-medium text-amber-600">Unsaved changes</span>' +
            '<span id="fc-settings-system-dirty" class="' +
            (state.activeTab === 'system' && state.systemDirty ? '' : 'hidden ') +
            'text-xs font-medium text-amber-600">Unsaved changes</span>' +
            '<span id="fc-settings-integration-dirty" class="' +
            (state.activeTab === 'integration' && state.integrationDirty ? '' : 'hidden ') +
            'text-xs font-medium text-amber-600">Unsaved changes</span>' +
            '</div>' +
            '<div id="fc-settings-header-actions-theme" class="' +
            (state.activeTab === 'theme' ? 'flex' : 'hidden') +
            ' flex-wrap gap-2">' +
            '<button type="button" id="fc-theme-reset" class="' +
            btnSecondary +
            '">Reset Defaults</button>' +
            '<button type="button" id="fc-theme-save" class="' +
            btnPrimary +
            '">Save Theme</button>' +
            '</div>' +
            '<div id="fc-settings-header-actions-branding" class="' +
            (state.activeTab === 'branding' ? 'flex' : 'hidden') +
            ' flex-wrap gap-2">' +
            '<button type="button" id="fc-branding-reset" class="' +
            btnSecondary +
            '">Reset Defaults</button>' +
            '<button type="button" id="fc-branding-save" class="' +
            btnPrimary +
            '">Save Branding</button>' +
            '</div>' +
            '<div id="fc-settings-header-actions-fence-colors" class="' +
            (state.activeTab === 'fence-colors' ? 'flex' : 'hidden') +
            ' flex-wrap gap-2">' +
            '<button type="button" id="fc-fence-colors-reset" class="' +
            btnSecondary +
            '">Reset Defaults</button>' +
            '<button type="button" id="fc-fence-colors-save" class="' +
            btnPrimary +
            '">Save Fence Colors</button>' +
            '</div>' +
            '<div id="fc-settings-header-actions-catalog" class="' +
            (state.activeTab === 'catalog' ? 'flex' : 'hidden') +
            ' flex-wrap gap-2">' +
            '<button type="button" id="fc-catalog-reset" class="' +
            btnSecondary +
            '">Reset Defaults</button>' +
            '<button type="button" id="fc-catalog-save" class="' +
            btnPrimary +
            '">Save Catalog</button>' +
            '</div>' +
            '<div id="fc-settings-header-actions-system" class="' +
            (state.activeTab === 'system' ? 'flex' : 'hidden') +
            ' flex-wrap gap-2">' +
            '<button type="button" id="fc-system-reset" class="' +
            btnSecondary +
            '">Reset Defaults</button>' +
            '<button type="button" id="fc-system-save" class="' +
            btnPrimary +
            '">Save System</button>' +
            '</div>' +
            '<div id="fc-settings-header-actions-integration" class="' +
            (state.activeTab === 'integration' ? 'flex' : 'hidden') +
            ' flex-wrap gap-2">' +
            '<button type="button" id="fc-integration-reset" class="' +
            btnSecondary +
            '">Discard Changes</button>' +
            '<button type="button" id="fc-integration-save" class="' +
            btnPrimary +
            '">Save Integrations</button>' +
            '</div>' +
            '<div id="fc-settings-header-actions-dev-mode" class="' +
            (state.activeTab === 'dev-mode' ? 'flex' : 'hidden') +
            ' flex-wrap gap-2"></div></div>'
        );
    }

    function updateHeaderActions() {
        var themeActions = document.getElementById('fc-settings-header-actions-theme');
        var brandingActions = document.getElementById('fc-settings-header-actions-branding');
        var fenceColorsActions = document.getElementById('fc-settings-header-actions-fence-colors');
        var catalogActions = document.getElementById('fc-settings-header-actions-catalog');
        var systemActions = document.getElementById('fc-settings-header-actions-system');
        var integrationActions = document.getElementById('fc-settings-header-actions-integration');
        var devModeActions = document.getElementById('fc-settings-header-actions-dev-mode');
        var themeDirty = document.getElementById('fc-settings-theme-dirty');
        var brandingDirty = document.getElementById('fc-settings-branding-dirty');
        var fenceColorsDirty = document.getElementById('fc-settings-fence-colors-dirty');
        var catalogDirty = document.getElementById('fc-settings-catalog-dirty');
        var systemDirty = document.getElementById('fc-settings-system-dirty');
        var integrationDirty = document.getElementById('fc-settings-integration-dirty');

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
        if (devModeActions) {
            devModeActions.classList.toggle('hidden', state.activeTab !== 'dev-mode');
            devModeActions.classList.toggle('flex', state.activeTab === 'dev-mode');
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
    }

    function colorsMatchPreset(colors, preset) {
        if (!preset || !preset.colors) {
            return false;
        }
        return Object.keys(preset.colors).every(function (varName) {
            return (colors[varName] || '').toLowerCase() === (preset.colors[varName] || '').toLowerCase();
        });
    }

    function detectMatchingPreset(colors) {
        var match = null;
        state.presets.forEach(function (preset) {
            if (!match && colorsMatchPreset(colors, preset)) {
                match = preset.id;
            }
        });
        return match;
    }

    function presetCardClasses(isSelected) {
        return (
            'fc-theme-preset group flex items-start gap-3 rounded-xl border-2 p-4 text-left transition ' +
            (isSelected
                ? 'fc-theme-preset--selected'
                : 'border-slate-200 bg-slate-50/50 hover:border-slate-300 hover:bg-white')
        );
    }

    function hexToRgb(hex) {
        var normalized = String(hex || '')
            .replace(/^#/, '')
            .toLowerCase();
        if (normalized.length === 3) {
            normalized = normalized
                .split('')
                .map(function (char) {
                    return char + char;
                })
                .join('');
        }
        if (normalized.length !== 6 || !/^[0-9a-f]{6}$/.test(normalized)) {
            return null;
        }
        return {
            r: parseInt(normalized.slice(0, 2), 16),
            g: parseInt(normalized.slice(2, 4), 16),
            b: parseInt(normalized.slice(4, 6), 16)
        };
    }

    function presetAccentColor(preset) {
        return preset.swatch || preset.colors['--fc-princeton-orange'] || '#f67925';
    }

    function presetActiveBadgeStyles(accent) {
        var rgb = hexToRgb(accent);
        if (!rgb) {
            return 'color:#f67925;border-color:rgba(246,121,37,0.35);background:rgba(246,121,37,0.12);';
        }
        return (
            'color:' +
            accent +
            ';border-color:rgba(' +
            rgb.r +
            ',' +
            rgb.g +
            ',' +
            rgb.b +
            ',0.35);background:rgba(' +
            rgb.r +
            ',' +
            rgb.g +
            ',' +
            rgb.b +
            ',0.12);'
        );
    }

    function presetActiveBadge(isActive, accent) {
        return (
            '<span data-fc-theme-active-badge class="' +
            (isActive ? '' : 'hidden ') +
            'mt-2 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold shadow-sm" style="' +
            presetActiveBadgeStyles(accent) +
            '">' +
            '<i class="fa-solid fa-circle-check text-[11px]" aria-hidden="true"></i> Active</span>'
        );
    }

    function renderPresets() {
        if (!state.presets.length) {
            return '';
        }

        return (
            '<section class="border border-slate-200 bg-white p-4 sm:p-5">' +
            '<h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-500">Presets</h3>' +
            '<p class="mb-4 text-sm text-slate-500">Apply a ready-made palette. You can fine-tune individual colors below.</p>' +
            '<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">' +
            state.presets
                .map(function (preset) {
                    var isActive = state.activePreset === preset.id;
                    var isSelected = state.selectedPreset === preset.id;
                    return (
                        '<button type="button" data-fc-theme-preset="' +
                        escapeHtml(preset.id) +
                        '" class="' +
                        presetCardClasses(isSelected) +
                        '" aria-pressed="' +
                        (isSelected ? 'true' : 'false') +
                        '">' +
                        '<span class="mt-0.5 flex h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 shadow-sm" aria-hidden="true">' +
                        '<span class="h-full w-1/2" style="background:' +
                        escapeHtml(preset.swatch || preset.colors['--fc-princeton-orange'] || '#d4112f') +
                        '"></span>' +
                        '<span class="h-full w-1/2" style="background:' +
                        escapeHtml(preset.colors['--fc-brand-primary'] || '#d4112f') +
                        '"></span></span>' +
                        '<span class="min-w-0 flex-1">' +
                        '<span class="block text-sm font-semibold text-slate-900">' +
                        escapeHtml(preset.label) +
                        '</span>' +
                        '<span class="mt-0.5 block text-xs leading-relaxed text-slate-500">' +
                        escapeHtml(preset.description) +
                        '</span>' +
                        presetActiveBadge(isActive, presetAccentColor(preset)) +
                        '</span></button>'
                    );
                })
                .join('') +
            '</div></section>'
        );
    }

    function updatePresetCards() {
        document.querySelectorAll('[data-fc-theme-preset]').forEach(function (btn) {
            var id = btn.getAttribute('data-fc-theme-preset');
            var isSelected = state.selectedPreset === id;
            var isActive = state.activePreset === id;

            btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            btn.classList.remove(
                'fc-theme-preset--selected',
                'border-slate-200',
                'bg-slate-50/50',
                'hover:border-slate-300',
                'hover:bg-white'
            );

            if (isSelected) {
                btn.classList.add('fc-theme-preset--selected');
            } else {
                btn.classList.add(
                    'border-slate-200',
                    'bg-slate-50/50',
                    'hover:border-slate-300',
                    'hover:bg-white'
                );
            }

            var badge = btn.querySelector('[data-fc-theme-active-badge]');
            if (badge) {
                badge.classList.toggle('hidden', !isActive);
            }
        });
    }

    function syncSelectedPresetFromColors() {
        if (!state.selectedPreset) {
            return;
        }
        var preset = state.presets.find(function (p) {
            return p.id === state.selectedPreset;
        });
        if (!preset || !colorsMatchPreset(state.colors, preset)) {
            state.selectedPreset = null;
        }
    }

    function applyPreset(presetId) {
        var preset = state.presets.find(function (p) {
            return p.id === presetId;
        });
        if (!preset) {
            return;
        }
        state.colors = Object.assign({}, preset.colors);
        state.selectedPreset = presetId;
        paintThemeForm();
        applyLiveTheme();
        setThemeDirty(true);
        updatePresetCards();
    }

    function renderThemePreview() {
        return (
            '<div class="rounded-xl border border-slate-200 bg-white p-4">' +
            '<p class="mb-3 text-sm font-semibold text-slate-800">Live preview</p>' +
            '<div class="flex flex-wrap gap-2">' +
            '<span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-princeton-orange)">Primary button</span>' +
            '<span class="inline-flex items-center rounded-lg border-2 px-4 py-2 text-sm font-semibold" style="border-color:var(--fc-princeton-orange);color:var(--fc-princeton-orange)">Outline</span>' +
            '<span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-brand-primary)">Brand</span>' +
            '<span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-green)">Success</span>' +
            '</div>' +
            '<p class="mt-3 text-sm" style="color:var(--fc-dark-charcoal)">Body text uses dark charcoal.</p>' +
            '<p class="text-xs" style="color:var(--fc-dark-medium-gray)">Secondary label text.</p>' +
            '<div class="mt-3 rounded-lg border p-3" style="border-color:var(--fc-gray);background:var(--fc-bright-gray)">Surface panel</div>' +
            '<p class="mt-3 text-xs text-slate-500">Saved theme applies on the <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../planner" target="_blank" rel="noopener">planner</a> after save (refresh if already open).</p>' +
            '</div>'
        );
    }

    function renderField(varName, label, value) {
        var id = 'fc-theme-' + varName.replace(/^--fc-/, '').replace(/-/g, '_');
        return (
            '<div class="block">' +
            '<span class="mb-1 block text-xs font-medium text-slate-600">' +
            escapeHtml(label) +
            '</span>' +
            '<code class="mb-2 block text-[11px] text-slate-400">' +
            escapeHtml(varName) +
            '</code>' +
            '<div class="flex items-center gap-2">' +
            '<input type="color" id="' +
            escapeHtml(id) +
            '_picker" data-fc-theme-var="' +
            escapeHtml(varName) +
            '" value="' +
            escapeHtml(value) +
            '" class="h-[33px] w-11 shrink-0 cursor-pointer rounded-[3px] border border-[#8c8f94] bg-white p-0.5" aria-label="' +
            escapeHtml(label) +
            ' color picker" />' +
            '<div class="fc-settings-field-input-wrap min-w-0 flex-1">' +
            '<input type="text" id="' +
            escapeHtml(id) +
            '_hex" data-fc-theme-hex="' +
            escapeHtml(varName) +
            '" value="' +
            escapeHtml(value) +
            '" maxlength="7" spellcheck="false" class="fc-settings-field font-mono uppercase" aria-label="' +
            escapeHtml(label) +
            ' hex value" />' +
            buildFieldCopyButton(id + '_hex', label + ' hex') +
            '</div></div></div>'
        );
    }

    function renderBrandingLogoField(key, field, value) {
        var id = 'fc-branding-' + key;
        var logoUrl = brandingAssetUrl(value);

        return (
            '<div class="fc-settings-branding-logo">' +
            '<div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-3">' +
            '<span class="w-full shrink-0 text-sm font-medium text-slate-700 sm:w-28 sm:pt-2">' +
            escapeHtml(field.label || key) +
            '</span>' +
            '<span class="min-w-0 flex-1 space-y-2">' +
            '<div id="fc-branding-logo-preview" class="fc-settings-branding-logo__preview' +
            (logoUrl ? '' : ' fc-settings-branding-logo__preview--empty') +
            '"' +
            (logoUrl ? ' style="--fc-branding-logo-preview:url(\'' + String(logoUrl).replace(/'/g, '%27') + '\')"' : '') +
            '>' +
            '<span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-image"></i></span>' +
            '</div>' +
            '<div class="fc-settings-branding-logo__inputs">' +
            '<input type="text" id="' +
            escapeHtml(id) +
            '" data-fc-branding-field="' +
            escapeHtml(key) +
            '" value="' +
            escapeHtml(value) +
            '" placeholder="' +
            escapeHtml(field.placeholder || '') +
            '" title="' +
            escapeHtml(field.help || '') +
            '" class="fc-settings-field font-mono text-xs" spellcheck="false" autocomplete="off" />' +
            '<button type="button" class="fc-settings-branding-logo__pick" data-fc-branding-pick title="Upload or choose logo" aria-label="Upload or choose logo">' +
            '<i class="fa-solid fa-image" aria-hidden="true"></i></button>' +
            '<button type="button" class="fc-settings-branding-logo__clear" data-fc-branding-clear title="Remove logo" aria-label="Remove logo">' +
            '<i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
            '</div>' +
            (field.help
                ? '<span class="block text-xs text-slate-500">' + escapeHtml(field.help) + '</span>'
                : '') +
            '</span></div></div>'
        );
    }

    function renderBrandingField(key, field, value) {
        if (field && field.type === 'image') {
            return renderBrandingLogoField(key, field, value);
        }

        var id = 'fc-branding-' + key;

        return (
            '<label class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3" for="' +
            escapeHtml(id) +
            '">' +
            '<span class="w-full shrink-0 text-sm font-medium text-slate-700 sm:w-28">' +
            escapeHtml(field.label || key) +
            '</span>' +
            '<span class="min-w-0 flex-1">' +
            '<input type="text" id="' +
            escapeHtml(id) +
            '" data-fc-branding-field="' +
            escapeHtml(key) +
            '" value="' +
            escapeHtml(value) +
            '" placeholder="' +
            escapeHtml(field.placeholder || '') +
            '" title="' +
            escapeHtml(field.help || '') +
            '" class="fc-settings-field" />' +
            (field.help
                ? '<span class="mt-1 block text-xs text-slate-500">' + escapeHtml(field.help) + '</span>'
                : '') +
            '</span></label>'
        );
    }

    function brandingFieldKeys() {
        return BRANDING_FIELD_ORDER.filter(function (key) {
            return state.brandingSchema[key];
        });
    }

    function renderThemePanel() {
        var groups = Object.keys(state.schema);
        var groupsHtml = groups
            .map(function (groupKey) {
                var group = state.schema[groupKey];
                var fields = Object.keys(group.vars || {})
                    .map(function (varName) {
                        return renderField(varName, group.vars[varName], state.colors[varName] || '#000000');
                    })
                    .join('');
                return (
                    '<section class="border border-slate-200 bg-slate-50/60 p-4 sm:p-5">' +
                    '<h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">' +
                    escapeHtml(group.label || groupKey) +
                    '</h3>' +
                    '<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">' +
                    fields +
                    '</div></section>'
                );
            })
            .join('');

        return (
            '<div id="fc-settings-panel-theme" class="' +
            (state.activeTab === 'theme' ? '' : 'hidden ') +
            'space-y-5">' +
            '<div>' +
            '<h2 class="text-lg font-semibold text-slate-900">Theme colors</h2>' +
            '<p class="mt-1 text-sm text-slate-500">Edit CSS variables used across the fencing calculator planner.</p>' +
            '</div>' +
            renderPresets() +
            groupsHtml +
            '</div>'
        );
    }

    function renderBrandingPanel() {
        var fields = brandingFieldKeys()
            .map(function (key) {
                return renderBrandingField(key, state.brandingSchema[key], state.branding[key] || '');
            })
            .join('');

        return (
            '<div id="fc-settings-panel-branding" class="' +
            (state.activeTab === 'branding' ? '' : 'hidden ') +
            'space-y-3">' +
            '<div>' +
            '<h2 class="text-lg font-semibold text-slate-900">Branding</h2>' +
            '<p class="mt-1 text-sm text-slate-500">App name, logo, tagline, and version shown on the planner and admin.</p>' +
            '</div>' +
            '<section class="border border-slate-200 bg-slate-50/60 p-3 sm:p-3.5">' +
            '<div class="grid grid-cols-1 gap-3">' +
            fields +
            '</div></section>' +
            '</div>'
        );
    }

    function cloneFenceColorsList(list) {
        return (list || []).map(function (row) {
            return {
                slug: row.slug || '',
                label: row.label || '',
                subLabel: row.subLabel || row.sub_label || '',
                color: row.color || '',
                image: row.image || ''
            };
        });
    }

    function getOriginalFenceColorSlugSet() {
        var slugs = Object.create(null);
        (state.fenceColorsDefaults || []).forEach(function (row) {
            var slug = String(row && row.slug ? row.slug : '').trim();
            if (slug) {
                slugs[slug] = true;
            }
        });
        return slugs;
    }

    function isOriginalFenceColorSlug(slug) {
        return !!getOriginalFenceColorSlugSet()[String(slug || '').trim()];
    }

    function fenceColorSortValue(row, column) {
        return String(row[column] || '').trim().toLowerCase();
    }

    function sortFenceColorsInPlace() {
        var sort = state.fenceColorsSort;
        if (!sort || !sort.column) {
            return;
        }
        var column = sort.column;
        var direction = sort.direction === 'desc' ? -1 : 1;
        state.fenceColors.sort(function (a, b) {
            var aVal = fenceColorSortValue(a, column);
            var bVal = fenceColorSortValue(b, column);
            if (aVal < bVal) {
                return -1 * direction;
            }
            if (aVal > bVal) {
                return 1 * direction;
            }
            return fenceColorSortValue(a, 'slug').localeCompare(fenceColorSortValue(b, 'slug'));
        });
    }

    function fenceColorSortIcon(column) {
        var sort = state.fenceColorsSort || {};
        if (sort.column !== column) {
            return 'fa-sort';
        }
        return sort.direction === 'desc' ? 'fa-sort-down' : 'fa-sort-up';
    }

    function renderFenceColorsSortHeaderCell(column, label) {
        var sort = state.fenceColorsSort || {};
        var active = sort.column === column;
        return (
            '<button type="button" class="fc-fs-kv-table__col fc-settings-fence-colors__sort-col' +
            (active ? ' is-active' : '') +
            '" data-fc-fence-color-sort="' +
            escapeHtml(column) +
            '" aria-label="Sort by ' +
            escapeHtml(label) +
            (active ? ', ' + (sort.direction === 'desc' ? 'descending' : 'ascending') : '') +
            '">' +
            '<span>' +
            escapeHtml(label) +
            '</span>' +
            '<i class="fa-solid ' +
            fenceColorSortIcon(column) +
            ' fc-settings-fence-colors__sort-icon" aria-hidden="true"></i></button>'
        );
    }

    function renderFenceColorsTableHead() {
        return (
            '<div class="fc-fs-kv-table__head" data-fc-fence-colors-head>' +
            '<span class="fc-fs-kv-table__grip" aria-hidden="true"></span>' +
            '<span class="fc-fs-kv-table__col fc-settings-fence-colors__head-preview" aria-hidden="true"></span>' +
            FENCE_COLOR_SORT_COLUMNS.map(function (col) {
                return renderFenceColorsSortHeaderCell(col.id, col.label);
            }).join('') +
            '<span class="fc-fs-kv-table__actions" aria-hidden="true"></span></div>'
        );
    }

    function setFenceColorsSort(column) {
        var sort = state.fenceColorsSort || { column: null, direction: 'asc' };
        if (sort.column === column) {
            sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            sort.column = column;
            sort.direction = 'asc';
        }
        state.fenceColorsSort = sort;
        sortFenceColorsInPlace();
        refreshFenceColorsTable();
        setFenceColorsDirty(true);
    }

    function fenceColorRowBackground(row) {
        var image = String(row.image || '').trim();
        var color = String(row.color || '').trim();
        if (image) {
            return /^url\(/i.test(image) ? image : 'url(' + image + ')';
        }
        return color || '#e2e8f0';
    }

    function fenceColorPickerValue(color) {
        var normalized = normalizeHexInput(color);
        return normalized || '#cccccc';
    }

    function fenceColorPreviewUrl(row, appBase) {
        var image = String(row.image || '').trim();
        if (!image) {
            return '';
        }
        if (/^https?:\/\//i.test(image) || /^data:/i.test(image) || /^\/\//.test(image)) {
            return image;
        }
        if (/^url\(/i.test(image)) {
            var inner = image.replace(/^url\(\s*/i, '').replace(/\s*\)\s*;?\s*$/, '');
            inner = inner.replace(/^['"]|['"]$/g, '');
            if (/^https?:\/\//i.test(inner) || /^data:/i.test(inner)) {
                return inner;
            }
            return appBase ? appBase + '/' + inner.replace(/^\/+/, '') : inner;
        }
        return appBase ? appBase + '/' + image.replace(/^\/+/, '') : image;
    }

    function renderFenceColorRow(row, index, appBase) {
        var bg = fenceColorRowBackground(row);
        var previewUrl = fenceColorPreviewUrl(row, appBase);
        var previewInner = previewUrl
            ? '<img src="' + escapeHtml(previewUrl) + '" alt="" />'
            : '';
        var isOriginal = isOriginalFenceColorSlug(row.slug);
        var slugInputAttrs = isOriginal
            ? ' readonly aria-readonly="true" title="Original color slugs cannot be changed"'
            : '';

        return (
            '<div class="fc-fs-kv-row fc-fs-kv-row--table' +
            (isOriginal ? ' fc-fs-kv-row--locked' : '') +
            '" data-fc-fence-color-row="' +
            index +
            '">' +
            '<span class="fc-fs-kv-row__grip" data-fc-fence-color-grip role="button" tabindex="0" aria-label="Drag to reorder" title="Drag to reorder">' +
            '<i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></span>' +
            '<span class="fc-settings-fence-colors__preview" data-fc-fence-color-preview="' +
            index +
            '" style="background:' +
            escapeHtml(bg) +
            '">' +
            previewInner +
            '</span>' +
            '<label class="fc-fs-gui-field fc-fs-kv-row__key">' +
            '<span class="fc-fs-gui-field__label">Slug</span>' +
            '<input type="text" class="fc-fs-input fc-fs-input--mono' +
            (isOriginal ? ' fc-fs-input--readonly' : '') +
            '" data-fc-fence-color-field="slug" data-fc-fence-color-index="' +
            index +
            '" value="' +
            escapeHtml(row.slug) +
            '" spellcheck="false" placeholder="monument_matt" autocomplete="off"' +
            slugInputAttrs +
            ' />' +
            '</label>' +
            '<label class="fc-fs-gui-field">' +
            '<span class="fc-fs-gui-field__label">Label</span>' +
            '<input type="text" class="fc-fs-input" data-fc-fence-color-field="label" data-fc-fence-color-index="' +
            index +
            '" value="' +
            escapeHtml(row.label) +
            '" placeholder="Black" autocomplete="off" />' +
            '</label>' +
            '<label class="fc-fs-gui-field">' +
            '<span class="fc-fs-gui-field__label">Sub label</span>' +
            '<input type="text" class="fc-fs-input" data-fc-fence-color-field="subLabel" data-fc-fence-color-index="' +
            index +
            '" value="' +
            escapeHtml(row.subLabel) +
            '" placeholder="Satin" autocomplete="off" />' +
            '</label>' +
            '<div class="fc-fs-gui-field fc-settings-fence-colors__color-cell">' +
            '<span class="fc-fs-gui-field__label">Color</span>' +
            '<div class="fc-settings-fence-colors__color-inputs">' +
            '<input type="color" class="fc-settings-fence-colors__picker" data-fc-fence-color-picker="' +
            index +
            '" value="' +
            escapeHtml(fenceColorPickerValue(row.color)) +
            '" aria-label="Color picker" />' +
            '<input type="text" id="fc-fence-color-hex-' +
            index +
            '" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="color" data-fc-fence-color-index="' +
            index +
            '" value="' +
            escapeHtml(row.color) +
            '" spellcheck="false" placeholder="#6e6e6a" autocomplete="off" />' +
            buildFieldCopyButton('fc-fence-color-hex-' + index, 'Color') +
            '</div></div>' +
            '<div class="fc-fs-gui-field fc-settings-fence-colors__image-cell">' +
            '<span class="fc-fs-gui-field__label">Image</span>' +
            '<div class="fc-settings-fence-colors__image-inputs">' +
            '<input type="text" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="image" data-fc-fence-color-index="' +
            index +
            '" value="' +
            escapeHtml(row.image) +
            '" spellcheck="false" placeholder="assets/img/… or URL" autocomplete="off" />' +
            '<button type="button" class="fc-settings-fence-colors__pick" data-fc-fence-color-pick="' +
            index +
            '" title="Set image" aria-label="Set image">' +
            '<i class="fa-solid fa-image" aria-hidden="true"></i></button>' +
            '</div></div>' +
            (isOriginal
                ? '<span class="fc-fs-kv-row__remove fc-fs-kv-row__remove--disabled" aria-hidden="true" title="Original colors cannot be removed">' +
                  '<i class="fa-solid fa-trash-can" aria-hidden="true"></i></span>'
                : '<button type="button" class="fc-fs-kv-row__remove" data-fc-fence-color-remove="' +
                  index +
                  '" aria-label="Remove">' +
                  '<i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>') +
            '</div>'
        );
    }

    function renderFenceColorsTableBody(appBase) {
        if (!state.fenceColors.length) {
            return (
                '<div class="fc-settings-fence-colors__empty">No fence colors yet. Add one below.</div>'
            );
        }
        return state.fenceColors
            .map(function (row, index) {
                return renderFenceColorRow(row, index, appBase);
            })
            .join('');
    }

    function renderFenceColorsPanel() {
        var appBase = getAppBase();

        return (
            '<div id="fc-settings-panel-fence-colors" class="' +
            (state.activeTab === 'fence-colors' ? '' : 'hidden ') +
            'fc-settings-fence-colors">' +
            '<article class="fc-fs-field-group fc-fs-field-group--outer fc-fs-field-group--full fc-fs-field-group--kv-table">' +
            '<header class="fc-fs-field-group__head">' +
            '<div class="fc-fs-field-group__head-copy">' +
            '<h4 class="fc-fs-field-group__head-title">Fence colors</h4>' +
            '<p class="fc-fs-field-group__head-sub">Colour swatches shown in the planner. Use a hex colour or CSS gradient, or an image URL. Image takes priority when both are set.</p>' +
            '</div></header>' +
            '<div class="fc-fs-field-group__body fc-fs-field-group__body--kv-table">' +
            '<div class="fc-fs-gui-field fc-fs-gui-field--span fc-fs-kv-block fc-fs-kv-block--table fc-fs-kv-block--fence-colors" data-fc-fence-colors-block>' +
            '<div class="fc-fs-kv-table fc-fs-kv-table--compact">' +
            renderFenceColorsTableHead() +
            '<div class="fc-fs-kv-table__body fc-fs-kv-table__body--compact" id="fc-fence-colors-tbody">' +
            renderFenceColorsTableBody(appBase) +
            '</div></div>' +
            '<button type="button" id="fc-fence-colors-add" class="btn btn-sm btn-dark fw-semibold fc-fs-kv-add">' +
            '<i class="fa-solid fa-plus me-1" aria-hidden="true"></i>Add color</button>' +
            '</div></div></article></div>'
        );
    }

    function updateFenceColorRowPreview(index) {
        var row = state.fenceColors[index];
        var preview = document.querySelector('[data-fc-fence-color-preview="' + index + '"]');
        if (!preview || !row) {
            return;
        }
        var appBase = getAppBase();
        var bg = fenceColorRowBackground(row);
        preview.style.background = bg;
        var previewUrl = fenceColorPreviewUrl(row, appBase);
        if (previewUrl) {
            preview.innerHTML = '<img src="' + escapeHtml(previewUrl) + '" alt="" />';
        } else {
            preview.innerHTML = '';
        }
    }

    function syncFenceColorsOrderFromDom() {
        var tbody = document.getElementById('fc-fence-colors-tbody');
        if (!tbody) {
            return;
        }
        var rows = tbody.querySelectorAll('[data-fc-fence-color-row]');
        var next = [];
        rows.forEach(function (row) {
            var index = parseInt(row.getAttribute('data-fc-fence-color-row'), 10);
            if (!isNaN(index) && state.fenceColors[index]) {
                next.push(cloneFenceColorsList([state.fenceColors[index]])[0]);
            }
        });
        if (next.length === state.fenceColors.length) {
            state.fenceColors = next;
        }
    }

    function refreshFenceColorsTable() {
        var head = document.querySelector('[data-fc-fence-colors-head]');
        var tbody = document.getElementById('fc-fence-colors-tbody');
        if (head) {
            head.outerHTML = renderFenceColorsTableHead();
        }
        if (tbody) {
            tbody.innerHTML = renderFenceColorsTableBody(getAppBase());
        }
        bindFenceColorsTableEvents();
    }

    function renderBrandingPreview() {
        return (
            '<div class="rounded-xl border border-slate-200 bg-white p-4">' +
            '<p class="mb-3 text-sm font-semibold text-slate-800">Live preview</p>' +
            '<div class="space-y-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-sm">' +
            '<div class="border-b border-slate-200 px-3 py-3">' +
            '<p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Logo</p>' +
            '<div id="fc-branding-preview-logo" class="fc-settings-branding-logo__preview fc-settings-branding-logo__preview--sidebar fc-settings-branding-logo__preview--empty">' +
            '<span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-border-all"></i></span>' +
            '</div></div>' +
            '<div class="border-b border-slate-200 px-3 py-3">' +
            '<p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">App name</p>' +
            '<p id="fc-branding-preview-title" class="truncate font-bold leading-snug text-slate-900">Fencing Calculator</p>' +
            '</div>' +
            '<div class="border-b border-slate-200 px-3 py-3">' +
            '<p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tagline</p>' +
            '<p id="fc-branding-preview-tagline" class="leading-snug text-slate-600">Calculate your fence cost and the materials needed.</p>' +
            '</div>' +
            '<div class="px-3 py-3">' +
            '<p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Footer</p>' +
            '<p id="fc-branding-preview-footer" class="truncate text-xs text-slate-500">' +
            '<span id="fc-branding-preview-footer-name">Fencing Calculator</span> ' +
            '<span id="fc-branding-preview-version">v10.0.0 beta</span>' +
            '</p></div></div>' +
            '<p class="mt-3 text-xs text-slate-500">Saved branding applies on the <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../planner" target="_blank" rel="noopener">planner</a> after save (refresh if already open).</p>' +
            '</div>'
        );
    }

    function renderSettingsForm() {
        var showPreview = state.activeTab === 'theme' || state.activeTab === 'branding';
        var sidebarPreview =
            state.activeTab === 'theme' ? renderThemePreview() : renderBrandingPreview();

        return (
            '<div class="flex h-full min-h-0 flex-col">' +
            renderSettingsHeader() +
            '<div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden">' +
            '<div id="fc-settings-layout" class="grid w-full grid-cols-1 gap-6 p-4 sm:p-6 lg:items-start ' +
            (showPreview ? 'lg:grid-cols-2' : '') +
            '">' +
            '<div class="min-w-0 space-y-5">' +
            renderThemePanel() +
            renderBrandingPanel() +
            renderFenceColorsPanel() +
            '</div>' +
            '<div class="' +
            (showPreview ? '' : 'hidden ') +
            'sticky top-4 z-10 self-start" id="fc-settings-preview">' +
            sidebarPreview +
            '</div></div></div></div>'
        );
    }

    function syncPickerToHex(varName, value) {
        var picker = document.querySelector('[data-fc-theme-var="' + varName + '"]');
        var hex = document.querySelector('[data-fc-theme-hex="' + varName + '"]');
        if (picker && /^#[0-9a-fA-F]{6}$/.test(value)) {
            picker.value = value.toLowerCase();
        }
        if (hex) {
            hex.value = value;
        }
    }

    function normalizeHexInput(value) {
        var v = String(value || '').trim();
        if (/^#[0-9a-fA-F]{6}$/.test(v)) {
            return v.toLowerCase();
        }
        if (/^#[0-9a-fA-F]{3}$/.test(v)) {
            return (
                '#' +
                v[1] +
                v[1] +
                v[2] +
                v[2] +
                v[3] +
                v[3]
            ).toLowerCase();
        }
        return null;
    }

    function switchTab(tabId) {
        state.activeTab = tabId;
        var themePanel = document.getElementById('fc-settings-panel-theme');
        var brandingPanel = document.getElementById('fc-settings-panel-branding');
        var fenceColorsPanel = document.getElementById('fc-settings-panel-fence-colors');
        var catalogPanel = document.getElementById('fc-settings-panel-catalog');
        var systemPanel = document.getElementById('fc-settings-panel-system');
        var integrationPanel = document.getElementById('fc-settings-panel-integration');
        var devModePanel = document.getElementById('fc-settings-panel-dev-mode');
        var preview = document.getElementById('fc-settings-preview');
        var layout = document.getElementById('fc-settings-layout');
        var showPreview = tabId === 'theme' || tabId === 'branding';

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
        if (devModePanel) {
            devModePanel.classList.toggle('hidden', tabId !== 'dev-mode');
        }
        if (layout) {
            layout.classList.toggle('lg:grid-cols-2', showPreview);
        }
        if (preview) {
            preview.classList.toggle('hidden', !showPreview);
            if (showPreview) {
                preview.innerHTML = tabId === 'theme' ? renderThemePreview() : renderBrandingPreview();
                if (tabId === 'branding') {
                    updateBrandingPreview();
                }
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
            ensureCatalogOptions().then(function () {
                paintCatalogForm();
            });
        }
        if (tabId === 'system') {
            paintSystemForm();
        }
    }

    function bindThemeForm() {
        document.querySelectorAll('[data-fc-theme-var]').forEach(function (picker) {
            picker.addEventListener('input', function () {
                var varName = picker.getAttribute('data-fc-theme-var');
                var value = picker.value;
                state.colors[varName] = value;
                syncPickerToHex(varName, value);
                applyLiveTheme();
                syncSelectedPresetFromColors();
                updatePresetCards();
                setThemeDirty(true);
            });
        });

        document.querySelectorAll('[data-fc-theme-hex]').forEach(function (input) {
            input.addEventListener('input', function () {
                var varName = input.getAttribute('data-fc-theme-hex');
                var normalized = normalizeHexInput(input.value);
                if (!normalized) {
                    return;
                }
                state.colors[varName] = normalized;
                syncPickerToHex(varName, normalized);
                applyLiveTheme();
                syncSelectedPresetFromColors();
                updatePresetCards();
                setThemeDirty(true);
            });
            input.addEventListener('blur', function () {
                var varName = input.getAttribute('data-fc-theme-hex');
                var normalized = normalizeHexInput(input.value);
                if (normalized) {
                    input.value = normalized;
                    state.colors[varName] = normalized;
                    syncPickerToHex(varName, normalized);
                }
            });
        });

        var saveBtn = document.getElementById('fc-theme-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveTheme);
        }

        var resetBtn = document.getElementById('fc-theme-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                var Modal = global.FcAdminModal;
                var ask =
                    Modal && typeof Modal.confirm === 'function'
                        ? Modal.confirm({
                              title: 'Reset theme',
                              message: 'Reset all theme colors to defaults?',
                              confirmLabel: 'Reset',
                              cancelLabel: 'Cancel'
                          })
                        : Promise.resolve(window.confirm('Reset all theme colors to defaults?'));

                ask.then(function (ok) {
                    if (!ok) {
                        return;
                    }
                    state.colors = Object.assign({}, state.defaults);
                    paintThemeForm();
                    applyLiveTheme();
                    state.selectedPreset = detectMatchingPreset(state.colors);
                    updatePresetCards();
                    setThemeDirty(true);
                });
            });
        }

        document.querySelectorAll('[data-fc-theme-preset]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyPreset(btn.getAttribute('data-fc-theme-preset'));
            });
        });
    }

    function bindBrandingForm() {
        document.querySelectorAll('[data-fc-branding-field]').forEach(function (input) {
            if (input.getAttribute('data-fc-branding-bound') === '1') {
                return;
            }
            input.setAttribute('data-fc-branding-bound', '1');
            input.addEventListener('input', function () {
                var key = input.getAttribute('data-fc-branding-field');
                state.branding[key] = input.value;
                updateBrandingPreview();
                setBrandingDirty(true);
            });
        });

        document.querySelectorAll('[data-fc-branding-pick]').forEach(function (btn) {
            if (btn.getAttribute('data-fc-branding-bound') === '1') {
                return;
            }
            btn.setAttribute('data-fc-branding-bound', '1');
            btn.addEventListener('click', function () {
                var input = document.querySelector('[data-fc-branding-field="logo"]');
                if (!input || !global.FcAdminMediaPicker || typeof global.FcAdminMediaPicker.open !== 'function') {
                    return;
                }
                global.FcAdminMediaPicker.open({
                    appBase: getAppBase(),
                    onSelect: function (path) {
                        input.value = path;
                        state.branding.logo = path;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            });
        });

        document.querySelectorAll('[data-fc-branding-clear]').forEach(function (btn) {
            if (btn.getAttribute('data-fc-branding-bound') === '1') {
                return;
            }
            btn.setAttribute('data-fc-branding-bound', '1');
            btn.addEventListener('click', function () {
                var input = document.querySelector('[data-fc-branding-field="logo"]');
                if (!input) {
                    return;
                }
                input.value = '';
                state.branding.logo = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        var saveBtn = document.getElementById('fc-branding-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveBranding);
        }

        var resetBtn = document.getElementById('fc-branding-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                var Modal = global.FcAdminModal;
                var ask =
                    Modal && typeof Modal.confirm === 'function'
                        ? Modal.confirm({
                              title: 'Reset branding',
                              message: 'Reset all branding fields to defaults?',
                              confirmLabel: 'Reset',
                              cancelLabel: 'Cancel'
                          })
                        : Promise.resolve(window.confirm('Reset all branding fields to defaults?'));

                ask.then(function (ok) {
                    if (!ok) {
                        return;
                    }
                    state.branding = Object.assign({}, state.brandingDefaults);
                    paintBrandingForm();
                    setBrandingDirty(true);
                });
            });
        }
    }

    var fenceColorsTableBound = false;

    function bindFenceColorsRowDragDrop(block) {
        if (!block || block.getAttribute('data-fc-fc-drag-bound') === '1') {
            return;
        }
        block.setAttribute('data-fc-fc-drag-bound', '1');

        var dragRow = null;

        function clearDragOver() {
            block.querySelectorAll('.fc-fs-kv-row--drag-over').forEach(function (row) {
                row.classList.remove('fc-fs-kv-row--drag-over');
            });
        }

        function resetDraggable() {
            block.querySelectorAll('.fc-fs-kv-row').forEach(function (row) {
                row.draggable = false;
            });
        }

        function moveRowBeforeTarget(fromRow, targetRow) {
            if (!fromRow || !targetRow || fromRow === targetRow) {
                return;
            }
            var parent = targetRow.parentNode;
            if (!parent) {
                return;
            }
            var siblings = Array.prototype.slice.call(parent.querySelectorAll(':scope > .fc-fs-kv-row'));
            var fromIndex = siblings.indexOf(fromRow);
            var toIndex = siblings.indexOf(targetRow);
            if (fromIndex < 0 || toIndex < 0) {
                return;
            }
            if (fromIndex < toIndex) {
                parent.insertBefore(fromRow, targetRow.nextSibling);
            } else {
                parent.insertBefore(fromRow, targetRow);
            }
        }

        block.addEventListener('mousedown', function (e) {
            var grip = e.target.closest('[data-fc-fence-color-grip]');
            if (!grip || !block.contains(grip)) {
                return;
            }
            var row = grip.closest('.fc-fs-kv-row');
            if (row) {
                row.draggable = true;
            }
        });

        block.addEventListener('mouseup', resetDraggable);

        block.addEventListener('dragstart', function (e) {
            var row = e.target.closest('.fc-fs-kv-row');
            if (!row || !block.contains(row) || !row.draggable) {
                e.preventDefault();
                return;
            }
            dragRow = row;
            row.classList.add('fc-fs-kv-row--dragging');
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', 'fence-color-row');
            }
        });

        block.addEventListener('dragend', function () {
            if (dragRow) {
                dragRow.classList.remove('fc-fs-kv-row--dragging');
            }
            clearDragOver();
            resetDraggable();
            dragRow = null;
        });

        block.addEventListener('dragover', function (e) {
            if (!dragRow) {
                return;
            }
            var table = dragRow.closest('.fc-fs-kv-table');
            var target = e.target.closest('.fc-fs-kv-row');
            if (!table || !target || target.closest('.fc-fs-kv-table') !== table || target === dragRow) {
                return;
            }
            e.preventDefault();
            clearDragOver();
            target.classList.add('fc-fs-kv-row--drag-over');
        });

        block.addEventListener('dragleave', function (e) {
            var row = e.target.closest('.fc-fs-kv-row');
            if (row) {
                row.classList.remove('fc-fs-kv-row--drag-over');
            }
        });

        block.addEventListener('drop', function (e) {
            if (!dragRow) {
                return;
            }
            var table = dragRow.closest('.fc-fs-kv-table');
            var target = e.target.closest('.fc-fs-kv-row');
            if (!table || !target || target.closest('.fc-fs-kv-table') !== table || target === dragRow) {
                return;
            }
            e.preventDefault();
            moveRowBeforeTarget(dragRow, target);
            clearDragOver();
            syncFenceColorsOrderFromDom();
            state.fenceColorsSort = { column: null, direction: 'asc' };
            refreshFenceColorsTable();
            setFenceColorsDirty(true);
        });
    }

    function bindFenceColorsTableEvents() {
        document.querySelectorAll('[data-fc-fence-color-field]').forEach(function (input) {
            if (input.getAttribute('data-fc-fc-bound') === '1') {
                return;
            }
            input.setAttribute('data-fc-fc-bound', '1');
            input.addEventListener('input', function () {
                var index = parseInt(input.getAttribute('data-fc-fence-color-index'), 10);
                var field = input.getAttribute('data-fc-fence-color-field');
                if (isNaN(index) || !field || !state.fenceColors[index]) {
                    return;
                }
                if (field === 'slug' && isOriginalFenceColorSlug(state.fenceColors[index].slug)) {
                    input.value = state.fenceColors[index].slug;
                    return;
                }
                state.fenceColors[index][field] = input.value;
                if (field === 'color') {
                    var picker = document.querySelector('[data-fc-fence-color-picker="' + index + '"]');
                    var normalized = normalizeHexInput(input.value);
                    if (picker && normalized) {
                        picker.value = normalized;
                    }
                }
                updateFenceColorRowPreview(index);
                setFenceColorsDirty(true);
            });
            input.addEventListener('blur', function () {
                if (input.getAttribute('data-fc-fence-color-field') !== 'color') {
                    return;
                }
                var index = parseInt(input.getAttribute('data-fc-fence-color-index'), 10);
                var normalized = normalizeHexInput(input.value);
                if (normalized && state.fenceColors[index]) {
                    input.value = normalized;
                    state.fenceColors[index].color = normalized;
                    var picker = document.querySelector('[data-fc-fence-color-picker="' + index + '"]');
                    if (picker) {
                        picker.value = normalized;
                    }
                }
            });
        });

        document.querySelectorAll('[data-fc-fence-color-picker]').forEach(function (picker) {
            if (picker.getAttribute('data-fc-fc-bound') === '1') {
                return;
            }
            picker.setAttribute('data-fc-fc-bound', '1');
            picker.addEventListener('input', function () {
                var index = parseInt(picker.getAttribute('data-fc-fence-color-picker'), 10);
                if (isNaN(index) || !state.fenceColors[index]) {
                    return;
                }
                state.fenceColors[index].color = picker.value;
                var hexInput = document.querySelector(
                    '[data-fc-fence-color-field="color"][data-fc-fence-color-index="' + index + '"]'
                );
                if (hexInput) {
                    hexInput.value = picker.value;
                }
                updateFenceColorRowPreview(index);
                setFenceColorsDirty(true);
            });
        });

        document.querySelectorAll('[data-fc-fence-color-pick]').forEach(function (btn) {
            if (btn.getAttribute('data-fc-fc-bound') === '1') {
                return;
            }
            btn.setAttribute('data-fc-fc-bound', '1');
            btn.addEventListener('click', function () {
                var index = parseInt(btn.getAttribute('data-fc-fence-color-pick'), 10);
                var input = document.querySelector(
                    '[data-fc-fence-color-field="image"][data-fc-fence-color-index="' + index + '"]'
                );
                if (!input || !global.FcAdminMediaPicker || typeof global.FcAdminMediaPicker.open !== 'function') {
                    return;
                }
                global.FcAdminMediaPicker.open({
                    appBase: getAppBase(),
                    onSelect: function (path) {
                        input.value = path;
                        if (state.fenceColors[index]) {
                            state.fenceColors[index].image = path;
                        }
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        updateFenceColorRowPreview(index);
                        setFenceColorsDirty(true);
                    }
                });
            });
        });

        document.querySelectorAll('[data-fc-fence-color-remove]').forEach(function (btn) {
            if (btn.getAttribute('data-fc-fc-bound') === '1') {
                return;
            }
            btn.setAttribute('data-fc-fc-bound', '1');
            btn.addEventListener('click', function () {
                var index = parseInt(btn.getAttribute('data-fc-fence-color-remove'), 10);
                if (isNaN(index) || !state.fenceColors[index]) {
                    return;
                }
                if (isOriginalFenceColorSlug(state.fenceColors[index].slug)) {
                    return;
                }
                state.fenceColors.splice(index, 1);
                refreshFenceColorsTable();
                setFenceColorsDirty(true);
            });
        });
    }

    function bindFenceColorsForm() {
        if (fenceColorsTableBound) {
            return;
        }
        fenceColorsTableBound = true;

        var fenceColorsBlock = document.querySelector('[data-fc-fence-colors-block]');
        if (fenceColorsBlock && !fenceColorsBlock.getAttribute('data-fc-fc-sort-bound')) {
            fenceColorsBlock.setAttribute('data-fc-fc-sort-bound', '1');
            fenceColorsBlock.addEventListener('click', function (e) {
                var sortBtn = e.target.closest('[data-fc-fence-color-sort]');
                if (!sortBtn) {
                    return;
                }
                e.preventDefault();
                setFenceColorsSort(sortBtn.getAttribute('data-fc-fence-color-sort'));
            });
        }
        bindFenceColorsRowDragDrop(fenceColorsBlock);

        bindFenceColorsTableEvents();

        var addBtn = document.getElementById('fc-fence-colors-add');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                state.fenceColors.push({ slug: '', label: '', subLabel: '', color: '', image: '' });
                refreshFenceColorsTable();
                setFenceColorsDirty(true);
            });
        }

        var saveBtn = document.getElementById('fc-fence-colors-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveFenceColors);
        }

        var resetBtn = document.getElementById('fc-fence-colors-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                var Modal = global.FcAdminModal;
                var ask =
                    Modal && typeof Modal.confirm === 'function'
                        ? Modal.confirm({
                              title: 'Reset fence colors',
                              message: 'Reset all fence colors to defaults?',
                              confirmLabel: 'Reset',
                              cancelLabel: 'Cancel'
                          })
                        : Promise.resolve(window.confirm('Reset all fence colors to defaults?'));

                ask.then(function (ok) {
                    if (!ok) {
                        return;
                    }
                    state.fenceColors = cloneFenceColorsList(state.fenceColorsDefaults);
                    state.fenceColorsSort = { column: null, direction: 'asc' };
                    refreshFenceColorsTable();
                    setFenceColorsDirty(true);
                });
            });
        }
    }

    function bindSettingsShell() {
        document.querySelectorAll('[data-fc-settings-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                switchTab(btn.getAttribute('data-fc-settings-tab'));
            });
        });
    }

    function bindDevModeForm() {
        bindDevConsole();
    }

    function bindDevConsole() {
        var form = document.getElementById('fc-dev-console-form');
        var input = document.getElementById('fc-dev-console-input');
        var output = document.getElementById('fc-dev-console-output');
        if (!form || !input || !output || form.getAttribute('data-fc-dev-console-bound') === '1') {
            return;
        }
        form.setAttribute('data-fc-dev-console-bound', '1');

        var history = [];
        var historyIndex = -1;
        var busy = false;

        function appendLine(text, kind) {
            var line = document.createElement('div');
            line.className = 'fc-dev-console__line' + (kind ? ' fc-dev-console__line--' + kind : '');
            line.textContent = text;
            output.appendChild(line);
            output.scrollTop = output.scrollHeight;
        }

        function clearOutput() {
            output.innerHTML = '';
        }

        function runLocal(command) {
            var normalized = String(command || '')
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();
            if (normalized === 'clear') {
                clearOutput();
                return true;
            }
            if (normalized === 'help' || normalized === '?') {
                appendLine('$ ' + command, 'cmd');
                appendLine(
                    [
                        'Commands:',
                        '  help                 Show this help',
                        '  clear                Clear the console',
                        '  pwd                  Show project root',
                        '  git <args>           Any git command in the project root',
                        '',
                        'Mutating git commands (pull, push, merge, reset, …) require CONFIRM.',
                        'Shell operators and -C / --git-dir overrides are blocked.'
                    ].join('\n'),
                    'out'
                );
                return true;
            }
            return false;
        }

        function tokenizeCommand(command) {
            var raw = String(command || '');
            if (/[;|&`$(){}<>\\]/.test(raw)) {
                return null;
            }
            var tokens = [];
            var current = '';
            var quote = '';
            for (var i = 0; i < raw.length; i++) {
                var ch = raw.charAt(i);
                if (quote) {
                    if (ch === quote) {
                        quote = '';
                    } else {
                        current += ch;
                    }
                    continue;
                }
                if (ch === '"' || ch === "'") {
                    quote = ch;
                    continue;
                }
                if (ch === ' ' || ch === '\t') {
                    if (current) {
                        tokens.push(current);
                        current = '';
                    }
                    continue;
                }
                current += ch;
            }
            if (quote) {
                return null;
            }
            if (current) {
                tokens.push(current);
            }
            return tokens;
        }

        function gitNeedsConfirm(command) {
            var argv = tokenizeCommand(command);
            if (!argv || !argv.length || String(argv[0]).toLowerCase() !== 'git') {
                return false;
            }
            var i = 1;
            while (i < argv.length && String(argv[i]).charAt(0) === '-') {
                if (argv[i] === '-c') {
                    i += 2;
                    continue;
                }
                i += 1;
            }
            var sub = String(argv[i] || '').toLowerCase();
            var always = {
                pull: 1,
                push: 1,
                fetch: 1,
                merge: 1,
                rebase: 1,
                reset: 1,
                clean: 1,
                commit: 1,
                checkout: 1,
                switch: 1,
                am: 1,
                'cherry-pick': 1,
                revert: 1,
                gc: 1,
                prune: 1,
                'filter-branch': 1,
                replace: 1,
                submodule: 1,
                worktree: 1
            };
            if (always[sub]) {
                return true;
            }
            var rest = argv.slice(i + 1);
            if (sub === 'stash') {
                var stashAction = String(rest[0] || 'push').toLowerCase();
                return stashAction !== 'list' && stashAction !== 'show';
            }
            if (sub === 'branch') {
                return rest.some(function (arg) {
                    return (
                        arg === '-d' ||
                        arg === '-D' ||
                        arg === '--delete' ||
                        arg === '-m' ||
                        arg === '-M' ||
                        arg === '--move' ||
                        arg === '-c' ||
                        arg === '-C' ||
                        arg === '--copy'
                    );
                });
            }
            if (sub === 'tag') {
                if (
                    rest.some(function (arg) {
                        return arg === '-d' || arg === '--delete' || arg === '-f' || arg === '--force';
                    })
                ) {
                    return true;
                }
                return rest.length > 0 && String(rest[0]).charAt(0) !== '-';
            }
            if (sub === 'remote') {
                var remoteAction = String(rest[0] || '').toLowerCase();
                return (
                    remoteAction === 'add' ||
                    remoteAction === 'remove' ||
                    remoteAction === 'rm' ||
                    remoteAction === 'rename' ||
                    remoteAction === 'set-url' ||
                    remoteAction === 'prune'
                );
            }
            return false;
        }

        function confirmGitCommand(command) {
            var Modal = global.FcAdminModal;
            var label = String(command || 'git').trim();
            if (Modal && typeof Modal.confirm === 'function') {
                return Modal.confirm({
                    title: 'Run git command?',
                    message: 'This will run "' + label + '" on the server in the project root. Continue?',
                    confirmLabel: 'Run command',
                    cancelLabel: 'Cancel',
                    variant: 'warning',
                    confirmText: 'CONFIRM',
                    confirmPrompt: 'Type {confirm} to continue.'
                });
            }
            return Promise.resolve(
                window.prompt(
                    'This will run "' +
                        label +
                        '" on the server in the project root.\n\nType CONFIRM to continue:'
                ) === 'CONFIRM'
            );
        }

        function executeCommand(command) {
            var trimmed = String(command || '').trim();
            if (!trimmed || busy) {
                return;
            }

            history.push(trimmed);
            historyIndex = history.length;

            if (runLocal(trimmed)) {
                return;
            }

            var needsConfirm = gitNeedsConfirm(trimmed);
            var confirmPromise = needsConfirm ? confirmGitCommand(trimmed) : Promise.resolve(true);

            confirmPromise.then(function (ok) {
                if (!ok) {
                    appendLine('$ ' + trimmed, 'cmd');
                    appendLine('Cancelled.', 'muted');
                    return;
                }

                busy = true;
                input.disabled = true;
                appendLine('$ ' + trimmed, 'cmd');

                var body = {
                    csrf: state.csrf || '',
                    command: trimmed
                };
                if (needsConfirm) {
                    body.confirm = 'CONFIRM';
                }

                fetch(API_DEV_CONSOLE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body)
                })
                    .then(function (res) {
                        return res.json().then(function (payload) {
                            return { res: res, body: payload || {} };
                        });
                    })
                    .then(function (result) {
                        var payload = result.body;
                        if (payload.clear) {
                            clearOutput();
                            return;
                        }
                        var text = String(payload.output || payload.error || '').trim();
                        if (!result.res.ok || !payload.ok) {
                            appendLine(text || payload.error || 'Command failed.', 'err');
                            return;
                        }
                        appendLine(text || '(no output)', 'out');
                    })
                    .catch(function (err) {
                        appendLine((err && err.message) || 'Could not run command.', 'err');
                    })
                    .finally(function () {
                        busy = false;
                        input.disabled = false;
                        input.focus();
                    });
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var value = input.value;
            input.value = '';
            executeCommand(value);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!history.length) {
                    return;
                }
                historyIndex = Math.max(0, historyIndex - 1);
                input.value = history[historyIndex] || '';
                input.setSelectionRange(input.value.length, input.value.length);
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!history.length) {
                    return;
                }
                historyIndex = Math.min(history.length, historyIndex + 1);
                input.value = historyIndex >= history.length ? '' : history[historyIndex] || '';
                input.setSelectionRange(input.value.length, input.value.length);
            }
        });

        appendLine('FC Dev Console — type "help" for commands.', 'muted');
    }

    function paintThemeForm() {
        Object.keys(state.colors).forEach(function (varName) {
            syncPickerToHex(varName, state.colors[varName]);
        });
        applyLiveTheme();
    }

    function paintBrandingForm() {
        brandingFieldKeys().forEach(function (key) {
            var input = document.querySelector('[data-fc-branding-field="' + key + '"]');
            if (input) {
                input.value = state.branding[key] || '';
            }
        });
        updateBrandingPreview();
    }

    function saveTheme() {
        toast('saving', 'Saving theme…', TOAST_THEME);
        fetch(API_THEME, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ colors: state.colors })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Save failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                state.colors = body.colors || state.colors;
                state.defaults = body.defaults || state.defaults;
                state.schema = body.schema || state.schema;
                state.presets = body.presets || state.presets;
                state.activePreset = body.activePreset || detectMatchingPreset(state.colors);
                state.selectedPreset = state.activePreset;
                paintThemeForm();
                updatePresetCards();
                setThemeDirty(false);
                toast('ok', 'Theme saved — refresh the planner to see changes.', TOAST_THEME);
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save theme.', TOAST_THEME);
            });
    }

    function saveBranding() {
        toast('saving', 'Saving branding…', TOAST_BRANDING);
        fetch(API_BRANDING, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ branding: state.branding })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Save failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                state.branding = body.branding || state.branding;
                state.brandingDefaults = body.defaults || state.brandingDefaults;
                state.brandingSchema = body.schema || state.brandingSchema;
                paintBrandingForm();
                setBrandingDirty(false);
                toast('ok', 'Branding saved — refresh the planner to see changes.', TOAST_BRANDING);
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save branding.', TOAST_BRANDING);
            });
    }

    function saveFenceColors() {
        toast('saving', 'Saving fence colors…', TOAST_FENCE_COLORS);
        fetch(API_FENCE_COLORS, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ fenceColors: state.fenceColors })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Save failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                state.fenceColors = cloneFenceColorsList(body.fenceColors || state.fenceColors);
                state.fenceColorsDefaults = cloneFenceColorsList(body.defaults || state.fenceColorsDefaults);
                refreshFenceColorsTable();
                setFenceColorsDirty(false);
                toast('ok', 'Fence colors saved — refresh the planner to see changes.', TOAST_FENCE_COLORS);
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save fence colors.', TOAST_FENCE_COLORS);
            });
    }

    function setCatalogDirty(isDirty) {
        state.catalogDirty = !!isDirty;
        updateHeaderActions();
    }

    function cloneIntegrations(value) {
        try {
            return JSON.parse(JSON.stringify(value || {}));
        } catch (e) {
            return Object.assign({}, value || {});
        }
    }

    function setIntegrationDirty(isDirty) {
        state.integrationDirty = !!isDirty;
        updateHeaderActions();
    }

    function paintIntegrationForm() {
        document.querySelectorAll('[data-fc-integration-field]').forEach(function (input) {
            var key = input.getAttribute('data-fc-integration-field');
            input.value = state.integrations[key] || '';
        });

        var sites = Array.isArray(state.integrations.sites) ? state.integrations.sites : [];
        document.querySelectorAll('[data-fc-integration-site]').forEach(function (input) {
            var siteKey = input.getAttribute('data-fc-integration-site');
            var field = input.getAttribute('data-fc-integration-site-field');
            var site = sites.find(function (row) {
                return String(row.key || '') === siteKey;
            });
            input.value = site && field ? site[field] || '' : '';
        });
    }

    function bindIntegrationForm() {
        if (state.integrationFormBound) {
            return;
        }
        state.integrationFormBound = true;

        document.querySelectorAll('[data-fc-integration-field]').forEach(function (input) {
            input.addEventListener('input', function () {
                var key = input.getAttribute('data-fc-integration-field');
                state.integrations[key] = input.value;
                setIntegrationDirty(true);
            });
        });

        document.querySelectorAll('[data-fc-integration-site]').forEach(function (input) {
            function syncSiteField() {
                var siteKey = input.getAttribute('data-fc-integration-site');
                var field = input.getAttribute('data-fc-integration-site-field');
                var sites = Array.isArray(state.integrations.sites) ? state.integrations.sites : [];
                var site = sites.find(function (row) {
                    return String(row.key || '') === siteKey;
                });
                if (site && field) {
                    site[field] = input.value;
                    setIntegrationDirty(true);
                }
            }
            input.addEventListener('input', syncSiteField);
            input.addEventListener('change', syncSiteField);
        });

        document.querySelectorAll('[data-fc-integration-reveal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-fc-integration-reveal');
                var input = id ? document.getElementById(id) : null;
                if (!input) {
                    return;
                }
                var showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                btn.setAttribute('aria-label', (showing ? 'Show ' : 'Hide ') + 'API key');
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.className = showing ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
                }
            });
        });

        document.querySelectorAll('[data-fc-cloudflare-verify]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                verifyCloudflareZone(btn);
            });
        });

        var saveBtn = document.getElementById('fc-integration-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveIntegrations);
        }
        var resetBtn = document.getElementById('fc-integration-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                state.integrations = cloneIntegrations(state.integrationsInitial);
                paintIntegrationForm();
                setIntegrationDirty(false);
            });
        }
    }

    function showVerifyFeedback(btn, ok) {
        if (!btn) {
            return;
        }
        var icon = btn.querySelector('i');
        if (!icon) {
            return;
        }
        btn.classList.remove('is-verifying', 'is-verified', 'is-verify-failed');
        if (ok) {
            icon.className = 'fa-solid fa-check';
            btn.classList.add('is-verified');
        } else {
            icon.className = 'fa-solid fa-xmark';
            btn.classList.add('is-verify-failed');
        }
        window.setTimeout(function () {
            icon.className = 'fa-solid fa-plug';
            btn.classList.remove('is-verified', 'is-verify-failed');
        }, 2000);
    }

    function verifyCloudflareZone(btn) {
        if (!btn || btn.disabled || btn.classList.contains('is-verifying')) {
            return;
        }

        var zoneInputId = btn.getAttribute('data-fc-cloudflare-zone-for') || '';
        var zoneInput = zoneInputId ? document.getElementById(zoneInputId) : null;
        var zoneId = zoneInput ? String(zoneInput.value || '').trim() : '';
        var siteKey = btn.getAttribute('data-fc-cloudflare-site') || '';
        var token = String(state.integrations.cloudflareApiToken || '').trim();
        var T = global.FcAdminToast;

        if (!zoneId) {
            if (T) {
                T.error('Enter a Cloudflare Zone ID first.');
            }
            showVerifyFeedback(btn, false);
            return;
        }

        var icon = btn.querySelector('i');
        btn.disabled = true;
        btn.classList.add('is-verifying');
        btn.classList.remove('is-verified', 'is-verify-failed');
        if (icon) {
            icon.className = 'fa-solid fa-spinner fa-spin';
        }
        if (T) {
            T.loading('Checking Cloudflare connection…', TOAST_CLOUDFLARE_VERIFY);
        }

        fetch(API_CLOUDFLARE_VERIFY, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                cloudflareApiToken: token,
                cloudflareZoneId: zoneId,
                siteKey: siteKey,
                csrf: state.csrf || ''
            })
        })
            .then(function (res) {
                return res.json().catch(function () {
                    return { ok: false, error: 'Invalid response from server.' };
                }).then(function (data) {
                    return { status: res.status, data: data || {} };
                });
            })
            .then(function (result) {
                btn.disabled = false;
                btn.classList.remove('is-verifying');
                if (T) {
                    T.dismiss(TOAST_CLOUDFLARE_VERIFY);
                }
                var data = result.data || {};
                if (data.ok) {
                    var zoneName = String(data.zoneName || '').trim();
                    var msg = zoneName ? 'Connected: ' + zoneName : 'Cloudflare zone connected.';
                    if (T) {
                        T.success(msg);
                    }
                    showVerifyFeedback(btn, true);
                    return;
                }
                var err = String(data.error || 'Cloudflare zone check failed.');
                if (T) {
                    T.error(err);
                }
                showVerifyFeedback(btn, false);
            })
            .catch(function () {
                btn.disabled = false;
                btn.classList.remove('is-verifying');
                if (T) {
                    T.dismiss(TOAST_CLOUDFLARE_VERIFY);
                    T.error('Cloudflare zone check failed.');
                }
                showVerifyFeedback(btn, false);
            });
    }

    function saveIntegrations() {
        toast('saving', 'Saving integration settings…', TOAST_INTEGRATIONS);
        fetch(API_INTEGRATIONS, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                integrations: state.integrations,
                revision: state.integrationsRevision,
                csrf: state.csrf
            })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Save failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                state.integrations = cloneIntegrations(body.integrations || state.integrations);
                state.integrationsInitial = cloneIntegrations(state.integrations);
                state.integrationsRevision = body.revision || state.integrationsRevision;
                paintIntegrationForm();
                setIntegrationDirty(false);
                toast('ok', 'Integration settings saved.', TOAST_INTEGRATIONS);
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save integration settings.', TOAST_INTEGRATIONS);
            });
    }

    function cloneCatalog(catalog) {
        try {
            return JSON.parse(JSON.stringify(catalog || {}));
        } catch (e) {
            return Object.assign({}, catalog || {});
        }
    }

    function filterCatalogCategoryNodes(nodes, query) {
        query = String(query || '').trim().toLowerCase();
        if (!query) {
            return nodes || [];
        }

        return (nodes || []).reduce(function (matches, node) {
            if (!node || typeof node !== 'object') {
                return matches;
            }
            var haystack = (String(node.name || '') + ' ' + String(node.slug || '')).toLowerCase();
            var ownMatch = haystack.indexOf(query) !== -1;
            var children = ownMatch
                ? node.children || []
                : filterCatalogCategoryNodes(node.children || [], query);
            if (ownMatch || children.length) {
                var copy = Object.assign({}, node);
                copy.children = children;
                matches.push(copy);
            }
            return matches;
        }, []);
    }

    function renderCatalogCategoryNodes(nodes, depth) {
        depth = depth || 0;
        var html = '';
        (nodes || []).forEach(function (node) {
            if (!node || typeof node !== 'object') {
                return;
            }
            var id = parseInt(node.id, 10) || 0;
            if (!id) {
                return;
            }
            var selected = (state.catalog.categoryIds || []).indexOf(id) !== -1;
            var count =
                node.count != null && node.count !== '' ? String(node.count) : '';
            html +=
                '<label class="fc-catalog-check" style="--fc-catalog-depth:' +
                depth +
                '">' +
                '<input type="checkbox" data-fc-catalog-category="' +
                id +
                '"' +
                (selected ? ' checked' : '') +
                '>' +
                '<span class="fc-catalog-check__label" title="' +
                escapeHtml(node.name || '') +
                '">' +
                escapeHtml(node.name || '') +
                '</span>' +
                (count !== ''
                    ? '<span class="fc-catalog-check__count">' + escapeHtml(count) + '</span>'
                    : '') +
                '</label>';
            if (node.children && node.children.length) {
                html += renderCatalogCategoryNodes(node.children, depth + 1);
            }
        });
        return html;
    }

    function catalogCheckMetaHtml(selected, total, emptyMeansAll) {
        var selectedLabel =
            selected > 0
                ? selected + ' selected'
                : emptyMeansAll
                  ? 'All visible'
                  : 'None selected';
        return (
            '<div class="fc-catalog-check-meta">' +
            '<span>' +
            total +
            ' total</span>' +
            '<span class="fc-catalog-check-meta__selected">' +
            selectedLabel +
            '</span>' +
            '</div>'
        );
    }

    function paintCatalogLists() {
        var catsEl = document.getElementById('fc-catalog-categories');
        var attrsEl = document.getElementById('fc-catalog-attributes');
        var errEl = document.getElementById('fc-catalog-options-error');

        if (errEl) {
            if (state.catalogOptionsError) {
                errEl.textContent = state.catalogOptionsError;
                errEl.classList.remove('hidden');
            } else {
                errEl.textContent = '';
                errEl.classList.add('hidden');
            }
        }

        if (catsEl) {
            if (!state.catalogCategories.length) {
                catsEl.innerHTML =
                    '<p class="fc-catalog-check-empty">' +
                    (state.catalogOptionsError
                        ? 'Categories unavailable until WooCommerce loads.'
                        : 'No product categories found.') +
                    '</p>';
            } else {
                var catTotal = collectCatalogCategoryIds(state.catalogCategories, []).length;
                var catSelected = (state.catalog.categoryIds || []).length;
                var filteredCategories = filterCatalogCategoryNodes(
                    state.catalogCategories,
                    state.catalogCategorySearch
                );
                catsEl.innerHTML =
                    catalogCheckMetaHtml(catSelected, catTotal, true) +
                    (filteredCategories.length
                        ? renderCatalogCategoryNodes(filteredCategories, 0)
                        : '<p class="fc-catalog-check-empty">No categories match your search.</p>');
            }
        }

        if (attrsEl) {
            if (!state.catalogAttributes.length) {
                attrsEl.innerHTML =
                    '<p class="fc-catalog-check-empty">' +
                    (state.catalogOptionsError
                        ? 'Attributes unavailable until WooCommerce loads.'
                        : 'No product attributes found.') +
                    '</p>';
            } else {
                var attrTotal = state.catalogAttributes.length;
                var attrSelected = (state.catalog.attributeSlugs || []).length;
                var attributeQuery = String(state.catalogAttributeSearch || '')
                    .trim()
                    .toLowerCase();
                var filteredAttributes = state.catalogAttributes.filter(function (attr) {
                    if (!attributeQuery) {
                        return true;
                    }
                    return (
                        (String(attr.label || '') + ' ' + String(attr.slug || ''))
                            .toLowerCase()
                            .indexOf(attributeQuery) !== -1
                    );
                });
                attrsEl.innerHTML =
                    catalogCheckMetaHtml(attrSelected, attrTotal, true) +
                    (filteredAttributes.length
                        ? filteredAttributes
                        .map(function (attr) {
                            var slug = String(attr.slug || '');
                            var selected = (state.catalog.attributeSlugs || []).indexOf(slug) !== -1;
                            var label = attr.label || slug;
                            return (
                                '<label class="fc-catalog-check">' +
                                '<input type="checkbox" data-fc-catalog-attribute="' +
                                escapeHtml(slug) +
                                '"' +
                                (selected ? ' checked' : '') +
                                '>' +
                                '<span class="fc-catalog-check__label" title="' +
                                escapeHtml(label) +
                                '">' +
                                escapeHtml(label) +
                                '</span>' +
                                '</label>'
                            );
                        })
                        .join('')
                        : '<p class="fc-catalog-check-empty">No attributes match your search.</p>');
            }
        }
    }

    function catalogResultsPerPageList(base) {
        var n = parseInt(base, 10);
        if (!n || n < 1) {
            n = 12;
        }
        if (n > 100) {
            n = 100;
        }
        return [n, n * 2, n * 3, n * 4, n * 5];
    }

    function paintCatalogResultsPerPageHint() {
        var hint = document.getElementById('fc-catalog-resultsPerPage-hint');
        if (!hint) {
            return;
        }
        var list = catalogResultsPerPageList(state.catalog.resultsPerPage);
        hint.textContent =
            'Lookup Per page list: ' + list.join(', ') + ' (default ' + list[0] + ').';
    }

    function paintCatalogForm() {
        document.querySelectorAll('[data-fc-catalog-field]').forEach(function (el) {
            var key = el.getAttribute('data-fc-catalog-field');
            if (!key) {
                return;
            }
            var value = state.catalog[key];
            if (el.tagName === 'SELECT' || el.tagName === 'INPUT') {
                el.value = value == null ? '' : String(value);
            }
        });
        paintCatalogResultsPerPageHint();
        paintCatalogLists();
    }

    function collectCatalogCategoryIds(nodes, out) {
        out = out || [];
        (nodes || []).forEach(function (node) {
            if (!node) {
                return;
            }
            var id = parseInt(node.id, 10) || 0;
            if (id) {
                out.push(id);
            }
            if (node.children && node.children.length) {
                collectCatalogCategoryIds(node.children, out);
            }
        });
        return out;
    }

    function ensureCatalogOptions() {
        if (state.catalogOptionsLoaded && (state.catalogCategories.length || state.catalogOptionsError)) {
            return Promise.resolve();
        }

        return fetch(API_CATALOG, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Catalog request failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                state.catalog = cloneCatalog(body.catalog || state.catalog);
                state.catalogDefaults = cloneCatalog(body.defaults || state.catalogDefaults);
                state.catalogOrderbyChoices = body.orderbyChoices || state.catalogOrderbyChoices;
                state.catalogCategories = body.categories || [];
                state.catalogAttributes = body.attributes || [];
                state.catalogOptionsError = body.optionsError || '';
                state.catalogOptionsLoaded = true;
            })
            .catch(function (err) {
                state.catalogOptionsError = err.message || 'Could not load catalog options.';
                state.catalogOptionsLoaded = true;
            });
    }

    function bindCatalogForm() {
        if (state.catalogFormBound) {
            return;
        }
        state.catalogFormBound = true;

        var categorySearch = document.querySelector('[data-fc-catalog-categories-search]');
        if (categorySearch) {
            categorySearch.value = state.catalogCategorySearch;
            categorySearch.addEventListener('input', function () {
                state.catalogCategorySearch = categorySearch.value;
                paintCatalogLists();
            });
        }

        var attributeSearch = document.querySelector('[data-fc-catalog-attributes-search]');
        if (attributeSearch) {
            attributeSearch.value = state.catalogAttributeSearch;
            attributeSearch.addEventListener('input', function () {
                state.catalogAttributeSearch = attributeSearch.value;
                paintCatalogLists();
            });
        }

        document.querySelectorAll('[data-fc-catalog-field]').forEach(function (el) {
            var handler = function () {
                var key = el.getAttribute('data-fc-catalog-field');
                if (!key) {
                    return;
                }
                if (el.type === 'number' || key === 'resultsPerPage') {
                    state.catalog[key] = el.value === '' ? '' : Number(el.value);
                } else {
                    state.catalog[key] = el.value;
                }
                if (key === 'resultsPerPage') {
                    paintCatalogResultsPerPageHint();
                }
                setCatalogDirty(true);
            };
            el.addEventListener('input', handler);
            el.addEventListener('change', handler);
        });

        var catsEl = document.getElementById('fc-catalog-categories');
        if (catsEl) {
            catsEl.addEventListener('change', function (e) {
                var t = e.target;
                if (!t || !t.matches('[data-fc-catalog-category]')) {
                    return;
                }
                var id = parseInt(t.getAttribute('data-fc-catalog-category'), 10) || 0;
                var selected = Array.isArray(state.catalog.categoryIds)
                    ? state.catalog.categoryIds.slice()
                    : [];
                var idx = selected.indexOf(id);
                if (t.checked && idx === -1) {
                    selected.push(id);
                } else if (!t.checked && idx !== -1) {
                    selected.splice(idx, 1);
                }
                state.catalog.categoryIds = selected;
                setCatalogDirty(true);
            });
        }

        var attrsEl = document.getElementById('fc-catalog-attributes');
        if (attrsEl) {
            attrsEl.addEventListener('change', function (e) {
                var t = e.target;
                if (!t || !t.matches('[data-fc-catalog-attribute]')) {
                    return;
                }
                var slug = String(t.getAttribute('data-fc-catalog-attribute') || '');
                var selected = Array.isArray(state.catalog.attributeSlugs)
                    ? state.catalog.attributeSlugs.slice()
                    : [];
                var idx = selected.indexOf(slug);
                if (t.checked && idx === -1) {
                    selected.push(slug);
                } else if (!t.checked && idx !== -1) {
                    selected.splice(idx, 1);
                }
                state.catalog.attributeSlugs = selected;
                setCatalogDirty(true);
            });
        }

        var catsAll = document.querySelector('[data-fc-catalog-cats-all]');
        if (catsAll) {
            catsAll.addEventListener('click', function () {
                state.catalog.categoryIds = collectCatalogCategoryIds(state.catalogCategories, []);
                paintCatalogLists();
                setCatalogDirty(true);
            });
        }
        var catsNone = document.querySelector('[data-fc-catalog-cats-none]');
        if (catsNone) {
            catsNone.addEventListener('click', function () {
                state.catalog.categoryIds = [];
                paintCatalogLists();
                setCatalogDirty(true);
            });
        }
        var attrsAll = document.querySelector('[data-fc-catalog-attrs-all]');
        if (attrsAll) {
            attrsAll.addEventListener('click', function () {
                state.catalog.attributeSlugs = (state.catalogAttributes || []).map(function (a) {
                    return String(a.slug || '');
                }).filter(Boolean);
                paintCatalogLists();
                setCatalogDirty(true);
            });
        }
        var attrsNone = document.querySelector('[data-fc-catalog-attrs-none]');
        if (attrsNone) {
            attrsNone.addEventListener('click', function () {
                state.catalog.attributeSlugs = [];
                paintCatalogLists();
                setCatalogDirty(true);
            });
        }

        var saveBtn = document.getElementById('fc-catalog-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveCatalog);
        }
        var resetBtn = document.getElementById('fc-catalog-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                state.catalog = cloneCatalog(state.catalogDefaults);
                paintCatalogForm();
                setCatalogDirty(true);
            });
        }
    }

    function saveCatalog() {
        toast('saving', 'Saving catalog settings…', TOAST_CATALOG);
        fetch(API_CATALOG, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ catalog: state.catalog })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Save failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                state.catalog = cloneCatalog(body.catalog || state.catalog);
                state.catalogDefaults = cloneCatalog(body.defaults || state.catalogDefaults);
                state.catalogOrderbyChoices = body.orderbyChoices || state.catalogOrderbyChoices;
                state.catalogCategories = body.categories || state.catalogCategories;
                state.catalogAttributes = body.attributes || state.catalogAttributes;
                state.catalogOptionsError = body.optionsError || '';
                state.catalogOptionsLoaded = true;
                paintCatalogForm();
                setCatalogDirty(false);
                toast('ok', 'Catalog settings saved — refresh Product Lookup to see changes.', TOAST_CATALOG);
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save catalog settings.', TOAST_CATALOG);
            });
    }

    function setSystemDirty(isDirty) {
        state.systemDirty = !!isDirty;
        updateHeaderActions();
    }

    function readSystemFieldValue(el) {
        if (!el) {
            return '';
        }
        if (el.type === 'number') {
            var n = parseInt(el.value, 10);
            return Number.isFinite(n) ? n : 0;
        }
        return el.value;
    }

    function paintSystemForm() {
        document.querySelectorAll('[data-fc-system-field]').forEach(function (el) {
            var key = el.getAttribute('data-fc-system-field');
            if (!key) {
                return;
            }
            var value = state.system[key];
            if (value == null) {
                value = '';
            }
            el.value = String(value);
        });
    }

    function bindSystemForm() {
        if (state.systemFormBound) {
            return;
        }
        state.systemFormBound = true;

        document.querySelectorAll('[data-fc-system-field]').forEach(function (el) {
            var onFieldChange = function () {
                var key = el.getAttribute('data-fc-system-field');
                if (!key) {
                    return;
                }
                state.system[key] = readSystemFieldValue(el);
                setSystemDirty(true);
            };
            el.addEventListener('change', onFieldChange);
            if (el.type === 'number') {
                el.addEventListener('input', onFieldChange);
            }
        });

        var saveBtn = document.getElementById('fc-system-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveSystem);
        }
        var resetBtn = document.getElementById('fc-system-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                state.system = Object.assign({}, state.systemDefaults);
                paintSystemForm();
                setSystemDirty(true);
            });
        }
    }

    function saveSystem() {
        toast('saving', 'Saving system settings…', TOAST_SYSTEM);
        fetch(API_SYSTEM, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ system: state.system })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Save failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                state.system = Object.assign({}, body.system || state.system);
                state.systemDefaults = Object.assign({}, body.defaults || state.systemDefaults);
                paintSystemForm();
                setSystemDirty(false);
                toast('ok', 'System settings saved.', TOAST_SYSTEM);
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save system settings.', TOAST_SYSTEM);
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

        state.fenceColors = cloneFenceColorsList(data.fenceColors || []);
        state.fenceColorsDefaults = cloneFenceColorsList(data.fenceColorsDefaults || []);

        state.catalog = cloneCatalog(data.catalog || {});
        state.catalogDefaults = cloneCatalog(data.catalogDefaults || {});
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

        state.integrations = cloneIntegrations(data.integrations || {});
        state.integrationsInitial = cloneIntegrations(
            data.integrationsInitial || data.integrations || {}
        );
        state.integrationsRevision = data.integrationsRevision || '';
        state.csrf = data.csrf || '';

        state.themeDirty = false;
        state.brandingDirty = false;
        state.fenceColorsDirty = false;
        state.catalogDirty = false;
        state.systemDirty = false;
        state.integrationDirty = false;
        state.fenceColorsSort = { column: null, direction: 'asc' };
        fenceColorsTableBound = false;
        state.catalogFormBound = false;
        state.systemFormBound = false;
        state.integrationFormBound = false;

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
        bindThemeForm();
        bindBrandingForm();
        bindFenceColorsForm();
        bindCatalogForm();
        bindSystemForm();
        bindIntegrationForm();
        bindDevModeForm();
        updatePresetCards();
        applyLiveTheme();
        updateBrandingPreview();
        paintCatalogForm();
        paintSystemForm();
        paintIntegrationForm();
        if (state.activeTab === 'catalog') {
            ensureCatalogOptions().then(function () {
                paintCatalogForm();
            });
        }
        container.removeAttribute('aria-busy');

        return Promise.resolve(true);
    }

    function loadSettings(container) {
        if (!container) {
            return Promise.resolve();
        }

        if (container.querySelector('#fc-settings-layout')) {
            return hydrateFromServer(container);
        }

        if (typeof global.fcAdminUrl === 'function') {
            global.location.href = global.fcAdminUrl('settings');
            return Promise.resolve();
        }

        container.innerHTML =
            '<div class="flex flex-col items-center justify-center gap-3 p-12 text-slate-500">' +
            '<i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-500" aria-hidden="true"></i>' +
            '<p class="text-sm">Loading settings…</p></div>';

        container.setAttribute('aria-busy', 'true');

        return Promise.all([
            fetch(API_THEME, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Theme request failed');
                    }
                    return body;
                });
            }),
            fetch(API_BRANDING, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Branding request failed');
                    }
                    return body;
                });
            }),
            fetch(API_FENCE_COLORS, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Fence colors request failed');
                    }
                    return body;
                });
            })
        ])
            .then(function (results) {
                var themeData = results[0];
                var brandingData = results[1];
                var fenceColorsData = results[2];

                state.colors = Object.assign({}, themeData.colors || {});
                state.defaults = Object.assign({}, themeData.defaults || {});
                state.schema = themeData.schema || {};
                state.presets = themeData.presets || [];
                state.activePreset = themeData.activePreset || null;
                state.selectedPreset = state.activePreset;
                state.themeDirty = false;

                state.branding = Object.assign({}, brandingData.branding || {});
                state.brandingDefaults = Object.assign({}, brandingData.defaults || {});
                state.brandingSchema = brandingData.schema || {};
                state.brandingDirty = false;

                state.fenceColors = cloneFenceColorsList(fenceColorsData.fenceColors || []);
                state.fenceColorsDefaults = cloneFenceColorsList(fenceColorsData.defaults || []);
                state.fenceColorsDirty = false;
                fenceColorsTableBound = false;

                state.activeTab = readSettingsTabFromUrl();
                syncSettingsTabUrl(state.activeTab);

                container.innerHTML = renderSettingsForm();
                container.removeAttribute('aria-busy');
                bindSettingsShell();
                bindSettingsCopyButtons(container);
                bindThemeForm();
                bindBrandingForm();
                bindFenceColorsForm();
                updatePresetCards();
                applyLiveTheme();
                updateBrandingPreview();
            })
            .catch(function (err) {
                container.removeAttribute('aria-busy');
                container.innerHTML =
                    '<div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">' +
                    '<p class="font-semibold">Could not load settings</p>' +
                    '<p class="mt-1 text-sm">' +
                    escapeHtml(err.message || 'Unknown error') +
                    '</p></div>';
            });
    }

    global.FcAdminSettings = {
        load: loadSettings,
        hydrateFromServer: hydrateFromServer
    };
})(window);
