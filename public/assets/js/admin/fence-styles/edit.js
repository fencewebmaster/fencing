/**
 * FC Admin — Fence style edit page (full config / settings manager).
 */
(function (global) {
    'use strict';

    var GENERAL_SCALAR_KEYS = [
        'title',
        'name',
        'slug',
        'image',
        'panel_group',
        'panel_count',
        'max_panel_width_mm',
        'max_panel_span_mm'
    ];

    var GENERAL_KNOWN_KEYS = {
        title: true,
        name: true,
        slug: true,
        image: true,
        live: true,
        panel_group: true,
        panel_count: true,
        color: true,
        offcut: true,
        hide_post_value: true,
        form: true,
        settings: true,
        max_panel_width_mm: true,
        max_panel_span_mm: true
    };

    var FS_EDIT_URL_KEYS = {
        mode: 'mode',
        tab: 'tab',
        section: 'section'
    };
    var FS_GUI_TABS = ['overview', 'form', 'modals', 'extra', 'advanced'];
    var FS_GUI_DEFAULT_TAB = 'overview';

    function normalizeGuiTab(tab) {
        tab = String(tab || '').trim();
        if (FS_GUI_TABS.indexOf(tab) !== -1) {
            return tab;
        }
        if (tab === 'general') {
            return 'overview';
        }
        if (tab === 'settings') {
            return 'modals';
        }
        return FS_GUI_DEFAULT_TAB;
    }

    function readFenceStyleEditUrlState() {
        var params = new URLSearchParams(window.location.search);
        var mode = String(params.get(FS_EDIT_URL_KEYS.mode) || '').trim().toLowerCase();
        var tab = String(params.get(FS_EDIT_URL_KEYS.tab) || '').trim();
        if (mode === 'dev' && !tab) {
            tab = 'advanced';
        }

        return {
            tab: normalizeGuiTab(tab),
            section: String(params.get(FS_EDIT_URL_KEYS.section) || '').trim()
        };
    }

    function syncFenceStyleEditUrl(state) {
        var tab = normalizeGuiTab(state.guiTab || FS_GUI_DEFAULT_TAB);
        state.guiTab = tab;

        var params = new URLSearchParams();
        if (tab !== FS_GUI_DEFAULT_TAB) {
            params.set(FS_EDIT_URL_KEYS.tab, tab);
        }
        if (state.activeSection && tab === 'modals') {
            params.set(FS_EDIT_URL_KEYS.section, state.activeSection);
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

    function activateGuiTab(container, tabName) {
        var guiRoot = container.querySelector('.fc-fs-gui-mode');
        if (!guiRoot) {
            return;
        }

        if (
            global.FcFenceStyleGui &&
            typeof global.FcFenceStyleGui.syncGuiNavTabUi === 'function'
        ) {
            global.FcFenceStyleGui.syncGuiNavTabUi(container, { guiTab: tabName });
            return;
        }

        if (tabName === 'advanced') {
            guiRoot.querySelectorAll('[data-gui-panel]').forEach(function (panel) {
                panel.classList.add('hidden');
            });
            return;
        }

        guiRoot.querySelectorAll('[data-gui-panel]').forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-gui-panel') !== tabName);
        });
    }

    function activateGuiSection(container, sectionKey) {
        var guiRoot = container.querySelector('.fc-fs-gui-mode');
        if (!guiRoot || !sectionKey) {
            return;
        }

        guiRoot.querySelectorAll('[data-gui-section-nav]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-gui-section-nav') === sectionKey);
        });
        guiRoot.querySelectorAll('[data-gui-section]').forEach(function (section) {
            section.classList.toggle('hidden', section.getAttribute('data-gui-section') !== sectionKey);
        });
    }

    function ensureEditorVisibility(state) {
        if (!state || !state.container) {
            return;
        }
        var guiTab = normalizeGuiTab(state.guiTab || FS_GUI_DEFAULT_TAB);
        var showFullConfig = guiTab === 'advanced';
        var guiRoot = state.container.querySelector('.fc-fs-gui-mode');
        var devRoot = state.container.querySelector('.fc-fs-dev-mode');

        if (guiRoot) {
            guiRoot.classList.toggle('hidden', showFullConfig);
        }
        if (devRoot) {
            devRoot.classList.toggle('hidden', !showFullConfig);
        }
    }

    function ensureFirstModalsSectionVisible(state) {
        if (!state || !state.container || state.activeSection) {
            return;
        }

        var guiRoot = state.container.querySelector('.fc-fs-gui-mode');
        if (!guiRoot) {
            return;
        }
        var firstNav = guiRoot.querySelector('[data-gui-section-nav]');
        var sectionKey = firstNav ? firstNav.getAttribute('data-gui-section-nav') : '';
        if (sectionKey) {
            activateGuiSection(state.container, sectionKey);
        }
    }

    function applyFenceStyleEditTabState(state, tab, section) {
        var nextTab = normalizeGuiTab(tab || state.guiTab || FS_GUI_DEFAULT_TAB);
        var nextSection = section || '';

        if (nextSection && nextTab !== 'modals') {
                nextTab = 'modals';
        }

        state.guiTab = nextTab;
        state.activeSection = nextSection;

        ensureEditorVisibility(state);

        if (
            global.FcFenceStyleGui &&
            typeof global.FcFenceStyleGui.syncGuiNavTabUi === 'function'
        ) {
            global.FcFenceStyleGui.syncGuiNavTabUi(state.container, { guiTab: nextTab });
        } else {
        activateGuiTab(state.container, nextTab);
        }

        if (nextTab === 'modals' && nextSection) {
            activateGuiSection(state.container, nextSection);
        } else if (nextTab === 'modals') {
            ensureFirstModalsSectionVisible(state);
        }

        if (nextTab === 'advanced') {
            if (global.FcFenceStyleCodeEditor) {
                global.FcFenceStyleCodeEditor.preload();
            }
            initFullJsonEditor(state).then(function () {
                refreshFullJsonEditor(state.container);
            });
        }
    }

    var escapeHtml = global.FC.util.escapeHtml;

    function deepClone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    function isPlainObject(val) {
        return val !== null && typeof val === 'object' && !Array.isArray(val);
    }

    function labelize(key) {
        return String(key)
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
    }

    function sectionSummary(section) {
        if (!isPlainObject(section)) {
            return '';
        }
        var parts = [];
        if (section.title) {
            parts.push(section.title);
        }
        if (Array.isArray(section.fields)) {
            parts.push(section.fields.length + ' field' + (section.fields.length === 1 ? '' : 's'));
        }
        return parts.join(' · ');
    }

    function renderInput(label, id, value, opts) {
        opts = opts || {};
        var readonly = opts.readonly ? ' readonly' : '';
        var type = opts.type || 'text';
        var extraClass = opts.mono ? ' font-mono text-xs' : ' text-sm';
        var placeholder = opts.placeholder ? ' placeholder="' + escapeHtml(opts.placeholder) + '"' : '';

        return (
            '<label class="fc-fs-field block">' +
            '<span class="fc-fs-field__label">' +
            escapeHtml(label) +
            '</span>' +
            '<input type="' +
            type +
            '" id="' +
            escapeHtml(id) +
            '" data-config-path="' +
            escapeHtml(opts.path || '') +
            '" value="' +
            escapeHtml(value == null ? '' : String(value)) +
            '"' +
            readonly +
            placeholder +
            ' class="fc-fs-input' +
            extraClass +
            (opts.readonly ? ' fc-fs-input--readonly' : '') +
            '">' +
            '</label>'
        );
    }

    function renderCheckbox(label, id, checked, path) {
        return (
            '<label class="fc-fs-check flex items-center gap-2">' +
            '<input type="checkbox" id="' +
            escapeHtml(id) +
            '" data-config-path="' +
            escapeHtml(path) +
            '"' +
            (checked ? ' checked' : '') +
            ' class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20">' +
            '<span class="text-sm font-medium text-slate-700">' +
            escapeHtml(label) +
            '</span></label>'
        );
    }

    function renderFullConfigPanel(config, hidden) {
        return (
            '<div class="fc-fs-dev-mode' +
            (hidden ? ' hidden' : '') +
            '" data-fc-fs-advanced="1">' +
            '<div class="fc-fs-panel-head fc-fs-panel-head--row">' +
            '<div><h3 class="fc-fs-panel-title">Advanced</h3>' +
            '<p class="fc-fs-panel-desc">Full configuration — edit the entire fence style as JSON.</p></div>' +
            '<div class="flex flex-wrap gap-2">' +
            '<button type="button" id="fc-fs-json-format" class="btn btn-sm btn-dark fw-semibold">Format</button>' +
            '<button type="button" id="fc-fs-json-validate" class="btn btn-sm btn-dark fw-semibold">Validate</button>' +
            '</div></div>' +
            '<div class="fc-fs-code-editor-wrap fc-fs-code-editor-wrap--full">' +
            '<textarea id="fc-fs-full-json" class="fc-fs-json-editor fc-fs-json-editor--full" spellcheck="false">' +
            escapeHtml(JSON.stringify(config, null, 2)) +
            '</textarea></div>' +
            '<p id="fc-fs-json-status" class="mt-2 text-xs text-slate-500" aria-live="polite"></p>' +
            '</div>'
        );
    }

    function getFullJsonEditor(container) {
        return global.FcFenceStyleCodeEditor || null;
    }

    function getFullJsonValue(container) {
        var Editor = getFullJsonEditor(container);
        if (Editor && typeof Editor.getValue === 'function') {
            return Editor.getValue(container);
        }
        var fullJson = container.querySelector('#fc-fs-full-json');
        return fullJson ? fullJson.value : '';
    }

    function setFullJsonValue(container, value) {
        var Editor = getFullJsonEditor(container);
        if (Editor && typeof Editor.setValue === 'function') {
            Editor.setValue(container, value);
            return;
        }
        var fullJson = container.querySelector('#fc-fs-full-json');
        if (fullJson) {
            fullJson.value = value;
        }
    }

    function setFullJsonError(container, hasError) {
        var Editor = getFullJsonEditor(container);
        if (Editor && typeof Editor.setError === 'function') {
            Editor.setError(container, hasError);
            return;
        }
        var fullJson = container.querySelector('#fc-fs-full-json');
        if (fullJson) {
            fullJson.classList.toggle('fc-fs-json-editor--error', !!hasError);
        }
    }

    function refreshFullJsonEditor(container) {
        var Editor = getFullJsonEditor(container);
        if (Editor && typeof Editor.refresh === 'function') {
            Editor.refresh(container);
        }
    }

    function initFullJsonEditor(state, options) {
        var Editor = getFullJsonEditor(state.container);
        if (!Editor || typeof Editor.initFullJson !== 'function') {
            return Promise.resolve(null);
        }
        return Editor.initFullJson(state.container, options || {});
    }

    function renderAdvancedPanel(config) {
        var urlState = readFenceStyleEditUrlState();
        var initialTab = normalizeGuiTab(urlState.tab || FS_GUI_DEFAULT_TAB);
        return renderFullConfigPanel(config, initialTab !== 'advanced');
    }

    function refreshDevJsonEditors(container, config) {
        syncDevEditorsFromConfig({ container: container, config: config }, {});
    }

    function syncDevGeneralFromConfig(container, config) {
        container.querySelectorAll('[data-config-path]').forEach(function (el) {
            var path = el.getAttribute('data-config-path');
            if (!path) {
                return;
            }
            var value = getByPath(config, path);
            if (el.type === 'checkbox') {
                el.checked = !!value;
            } else if (el.type === 'radio') {
                el.checked = String(value != null ? value : '') === String(el.value);
            } else if (path === 'color' && Array.isArray(value)) {
                el.value = value.join('\n');
            } else if (value != null) {
                el.value = String(value);
            }
        });
    }

    function configsEqual(a, b) {
        try {
            return JSON.stringify(a) === JSON.stringify(b);
        } catch (e) {
            return false;
        }
    }

    function syncDevEditorsFromConfig(state, options) {
        options = options || {};
        if (!state || !state.container) {
            return;
        }
        var container = state.container;
        var config = state.config;

        if (options.skipGeneral !== true) {
            syncDevGeneralFromConfig(container, config);
        }

        if (options.skipPathEditors !== true) {
        container.querySelectorAll('.fc-fs-json-editor[data-json-path]').forEach(function (textarea) {
            var path = textarea.getAttribute('data-json-path');
            if (!path) {
                return;
            }
            var value = getByPath(config, path);
            if (value !== undefined) {
                textarea.value = JSON.stringify(value, null, 2);
                textarea.classList.remove('fc-fs-json-editor--error');
            }
        });
        }

        if (options.skipFullJson !== true) {
            setFullJsonValue(container, JSON.stringify(config, null, 2));
            setFullJsonError(container, false);
        }
    }

    function syncAllEditorsFromConfig(state, options) {
        options = options || {};
        if (!state || !state.container) {
            return Promise.resolve();
        }

        if (options.preview !== false) {
            syncPreview(state);
        }

        syncDevEditorsFromConfig(state, options.dev || {});

        return syncGuiEditorsFromConfig(state, state._guiHelpers, {
            activeTab: options.activeTab || resolveActiveGuiTab(state),
            full: !!options.full
        });
    }

    function resolveActiveGuiTab(state) {
        return normalizeGuiTab(state.guiTab || FS_GUI_DEFAULT_TAB);
    }

    function syncGuiEditorsFromConfig(state, helpers, options) {
        if (!global.FcFenceStyleGui || typeof global.FcFenceStyleGui.syncGuiFromConfig !== 'function') {
            return Promise.resolve();
        }
        options = options || {};
        if (!options.activeTab) {
            options.activeTab = resolveActiveGuiTab(state);
        }
        return global.FcFenceStyleGui.syncGuiFromConfig(state, helpers, options);
    }

    function initEditorPage(state, options) {
        options = options || {};
        ensureEditorVisibility(state);
        syncHeaderNavTabs(state);

        return initGuiWysiwyg(state).then(function () {
            applyFenceStyleEditTabState(state, state.guiTab, state.activeSection);
            if (!options.skipUrlSync) {
                syncFenceStyleEditUrl(state);
            }
        });
    }

    function initGuiWysiwyg(state) {
        var guiRoot = state.container.querySelector('.fc-fs-gui-mode');
        if (!guiRoot || guiRoot.classList.contains('hidden') || !global.FcFenceStyleWysiwyg) {
            return Promise.resolve();
        }
        return global.FcFenceStyleWysiwyg.initInRoot(guiRoot, function (textarea, content) {
            var path = textarea.getAttribute('data-gui-path');
            if (!path) {
                return;
            }
            setByPath(state.config, path, content);
            markDirty(state, true);
            syncDevEditorsFromConfig(state, { skipPathEditors: true });
        });
    }

    function renderStylePreviewPlaceholder() {
        return (
            '<span class="fc-fs-style-preview__placeholder" aria-hidden="true">' +
            '<i class="fa-solid fa-image"></i>' +
            '<span>No image</span></span>'
        );
    }

    function renderPreviewLiveToggle(checked) {
        return (
            '<label class="fc-fs-gui-toggle fc-fs-style-preview__live-toggle">' +
            '<input type="checkbox" id="fc-fence-style-preview-live" data-config-path="live"' +
            (checked ? ' checked' : '') +
            ' class="fc-fs-gui-toggle__input">' +
            '<span class="fc-fs-gui-toggle__track" aria-hidden="true"></span>' +
            '<span class="fc-fs-gui-toggle__label">LIVE</span></label>'
        );
    }

    function renderStylePreviewMedia(imageSrc, title) {
        if (!imageSrc) {
            return (
                '<div class="fc-fs-style-preview__media" id="fc-fence-style-preview-img">' +
                renderStylePreviewPlaceholder() +
                '</div>'
            );
        }

        return (
            '<button type="button" class="fc-fs-style-preview__media fc-fs-style-preview__media--clickable" id="fc-fence-style-preview-img" data-fc-fs-preview-trigger aria-label="View featured image fullscreen">' +
            '<img src="' +
            escapeHtml(imageSrc) +
            '" alt="' +
            escapeHtml(title) +
            '">' +
            '<span class="fc-fs-style-preview__zoom" aria-hidden="true"><i class="fa-solid fa-magnifying-glass-plus"></i></span>' +
            '</button>'
        );
    }

    function openStylePreviewLightbox(src, title) {
        if (!src) {
            return;
        }

        closeStylePreviewLightbox();

        var lightbox = document.createElement('div');
        lightbox.className = 'fc-fs-preview-lightbox';
        lightbox.id = 'fc-fs-preview-lightbox';
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');
        lightbox.setAttribute('aria-label', 'Featured image preview');
        lightbox.innerHTML =
            '<div class="fc-fs-preview-lightbox__backdrop" data-fc-fs-preview-lightbox-close aria-hidden="true"></div>' +
            '<button type="button" class="fc-fs-preview-lightbox__close" data-fc-fs-preview-lightbox-close aria-label="Close">' +
            '<i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
            '<img class="fc-fs-preview-lightbox__image" src="' +
            escapeHtml(src) +
            '" alt="' +
            escapeHtml(title || 'Featured image') +
            '">' +
            (title
                ? '<p class="fc-fs-preview-lightbox__caption">' + escapeHtml(title) + '</p>'
                : '');

        function onKeydown(e) {
            if (e.key === 'Escape') {
                closeStylePreviewLightbox();
            }
        }

        lightbox._fcPreviewKeydown = onKeydown;
        document.addEventListener('keydown', onKeydown);
        lightbox.querySelectorAll('[data-fc-fs-preview-lightbox-close]').forEach(function (el) {
            el.addEventListener('click', closeStylePreviewLightbox);
        });
        document.body.appendChild(lightbox);
        document.body.classList.add('fc-fs-preview-lightbox-open');
    }

    function closeStylePreviewLightbox() {
        var lightbox = document.getElementById('fc-fs-preview-lightbox');
        if (!lightbox) {
            return;
        }
        if (lightbox._fcPreviewKeydown) {
            document.removeEventListener('keydown', lightbox._fcPreviewKeydown);
        }
        lightbox.remove();
        document.body.classList.remove('fc-fs-preview-lightbox-open');
    }

    function bindStylePreviewLightbox(container) {
        if (!container || container.getAttribute('data-fc-fs-preview-bound') === '1') {
            return;
        }
        container.setAttribute('data-fc-fs-preview-bound', '1');
        container.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-fc-fs-preview-trigger]');
            if (!trigger || !container.contains(trigger)) {
                return;
            }
            var img = trigger.querySelector('img');
            if (!img || !img.getAttribute('src')) {
                return;
            }
            var titleEl = container.querySelector('#fc-fence-style-preview-title');
            var title = titleEl ? titleEl.textContent : img.getAttribute('alt') || '';
            openStylePreviewLightbox(img.getAttribute('src'), title);
        });
    }

    function renderStylePreviewImageField(config, appBase) {
        var Gui = global.FcFenceStyleGui;
        if (!Gui || typeof Gui.renderImageField !== 'function') {
            return '';
        }

        return (
            '<div class="fc-fs-style-preview__controls">' +
            Gui.renderImageField('Featured image URL', 'image', config.image || '', {
                appBase: appBase,
                compact: true,
                hidePreview: true,
                editIconOnly: true,
                pathAttr: 'data-config-path',
                id: 'fc-fence-style-image-input'
            }) +
            '</div>'
        );
    }

    function renderStylePreviewCard(style, config, appBase) {
        config = config || {};
        var title = String(config.title || style.title || '').trim() || 'Untitled style';
        var image = config.image || style.image || '';
        var live = !!config.live;
        var imagePath = String(image).trim().replace(/^\/+/, '');
        var imageSrc = imagePath && appBase ? appBase + '/' + imagePath : '';

        return (
            '<div class="fc-admin-fence-style-edit-preview">' +
            '<div class="fc-fs-style-preview">' +
            '<div class="fc-fs-style-preview__head">' +
            '<span class="fc-fs-style-preview__eyebrow">Featured preview</span>' +
            renderPreviewLiveToggle(live) +
            '</div>' +
            '<div class="fc-fs-style-preview__card">' +
            renderStylePreviewMedia(imageSrc, title) +
            '<p class="fc-fs-style-preview__name" id="fc-fence-style-preview-title">' +
            escapeHtml(title) +
            '</p></div>' +
            renderStylePreviewImageField(config, appBase) +
            '</div></div>'
        );
    }

    function renderEditPage(style, config, fileMeta, options) {
        options = options || {};
        var canEdit = options.canEdit !== false;
        var Gui = global.FcFenceStyleGui;
        var btn = global.FcAdminBtn || {};
        var btnPrimary = btn.primary || 'btn btn-sm btn-orange fw-semibold';
        var appBase = (document.body && document.body.getAttribute('data-fc-app-base')) || '';
        appBase = appBase.replace(/\/+$/, '');
        var urlState = readFenceStyleEditUrlState();
        var initialTab = normalizeGuiTab(urlState.tab || FS_GUI_DEFAULT_TAB);
        var guiHtml =
            Gui && typeof Gui.render === 'function'
                ? Gui.render(config, appBase, { fenceColorCatalog: options.fenceColorCatalog || [] })
                : '<p class="text-sm text-red-600">GUI editor failed to load.</p>';
        if (initialTab === 'advanced') {
            guiHtml = guiHtml.replace('class="fc-fs-gui-mode"', 'class="fc-fs-gui-mode hidden"');
        }

        var guiNavHtml =
            Gui && typeof Gui.renderGuiNavTabs === 'function'
                ? Gui.renderGuiNavTabs(initialTab, { includeBack: true })
                : '';

        var saveHtml = canEdit
            ? '<button type="submit" form="fc-fence-style-edit-form" id="fc-fence-style-save" class="' +
              btnPrimary +
              '">' +
              '<i class="fa-solid fa-check" aria-hidden="true"></i>' +
              '<span>Save</span></button>'
            : '<span class="text-xs text-slate-500">View only</span>';

        return (
            '<div class="fc-admin-fence-style-edit fc-fs-edit flex h-full min-h-0 flex-col overflow-hidden' +
            (canEdit ? '' : ' fc-fs-edit--readonly') +
            '">' +
            '<div class="fc-fs-edit-toolbar fc-admin-sticky-header shrink-0" data-fc-admin-sticky-header>' +
            '<div class="fc-fs-edit-toolbar__start">' +
            '<div id="fc-fs-gui-nav" class="fc-fs-edit-toolbar__tabs">' +
            guiNavHtml +
            '</div>' +
            '<span id="fc-fs-dirty-badge" class="fc-fs-dirty-badge hidden" role="status" aria-live="polite" aria-hidden="true">' +
            '<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>' +
            '<span>Unsaved changes</span></span></div>' +
            '<div class="fc-fs-edit-toolbar__actions">' +
            saveHtml +
            '</div></div>' +
            '<form id="fc-fence-style-edit-form" class="fc-fs-edit-body min-h-0 flex-1 overflow-y-auto overflow-x-hidden" novalidate' +
            (canEdit ? '' : ' data-fc-fs-readonly="1"') +
            '>' +
            '<div class="fc-fs-main-col">' +
            guiHtml +
            renderAdvancedPanel(config) +
            '</div>' +
            '<aside class="fc-fs-preview-col">' +
            renderStylePreviewCard(style, config, appBase) +
            '</aside></form></div>'
        );
    }

    function setByPath(obj, path, value) {
        var parts = path.split('.');
        var cur = obj;
        for (var i = 0; i < parts.length - 1; i++) {
            var part = parts[i];
            if (!isPlainObject(cur) && !Array.isArray(cur)) {
                cur = {};
            }
            if (!(part in cur) || cur[part] === null || typeof cur[part] !== 'object') {
                cur[part] = /^\d+$/.test(parts[i + 1]) ? [] : {};
            }
            cur = cur[part];
        }
        cur[parts[parts.length - 1]] = value;
    }

    function getByPath(obj, path) {
        var parts = path.split('.');
        var cur = obj;
        for (var i = 0; i < parts.length; i++) {
            if (cur == null) {
                return undefined;
            }
            cur = cur[parts[i]];
        }
        return cur;
    }

    function snapshotConfig(config) {
        return JSON.stringify(config);
    }

    function refreshDirtyBadge(state) {
        var dirty =
            !!state.baselineConfig && snapshotConfig(state.config) !== state.baselineConfig;
        state.dirty = dirty;
        var badge = state.container.querySelector('#fc-fs-dirty-badge');
        if (badge) {
            badge.classList.toggle('hidden', !dirty);
            badge.setAttribute('aria-hidden', dirty ? 'false' : 'true');
        }
    }

    function resetDirtyBaseline(state) {
        if (global.FcFenceStyleWysiwyg) {
            global.FcFenceStyleWysiwyg.syncAll(state.container);
        }
        state.baselineConfig = snapshotConfig(state.config);
        refreshDirtyBadge(state);
    }

    function markDirty(state, dirty) {
        if (dirty === false) {
            state.baselineConfig = snapshotConfig(state.config);
        }
        refreshDirtyBadge(state);
    }

    function syncPreview(state) {
        var title = String(state.config.title || '').trim() || 'Untitled style';
        var image = state.config.image || '';
        var previewTitle = state.container.querySelector('#fc-fence-style-preview-title');
        var previewImgWrap = state.container.querySelector('#fc-fence-style-preview-img');
        var previewImageInput = state.container.querySelector('#fc-fence-style-image-input');

        if (previewTitle) {
            previewTitle.textContent = title;
        }

        if (previewImageInput && previewImageInput.value !== String(image || '')) {
            previewImageInput.value = image || '';
            if (global.FcFenceStyleGui && typeof global.FcFenceStyleGui.disableImageUrlInput === 'function') {
                global.FcFenceStyleGui.disableImageUrlInput(previewImageInput);
            }
        }

        if (previewImgWrap) {
            var path = String(image).trim().replace(/^\/+/, '');
            if (!path) {
                previewImgWrap.outerHTML = renderStylePreviewMedia('', title);
                return;
            }
            var src = state.appBase + '/' + path;
            previewImgWrap.outerHTML = renderStylePreviewMedia(src, title);
        }
    }

    function applyJsonPath(state, path, raw) {
        var parsed;
        try {
            parsed = JSON.parse(raw);
        } catch (e) {
            return false;
        }
        setByPath(state.config, path, parsed);
        markDirty(state, true);
        syncAllEditorsFromConfig(state, { full: true });
        return true;
    }

    function flushDevEditorsToConfig(state) {
        if (typeof state.flushFullJsonEditor === 'function') {
            state.flushFullJsonEditor();
        }
    }

    function bindGeneralInputs(state) {
        function applyConfigPathEl(el) {
            var path = el.getAttribute('data-config-path');
            if (!path) {
                return;
            }
            var value;
            if (el.type === 'checkbox') {
                value = el.checked;
            } else if (el.type === 'radio') {
                if (!el.checked) {
                    return;
                }
                value = el.value;
            } else if (path === 'color') {
                value = el.value
                    .split(/\r?\n/)
                    .map(function (line) {
                        return line.trim();
                    })
                    .filter(Boolean);
            } else if (path === 'panel_count' || path === 'max_panel_width_mm' || path === 'max_panel_span_mm') {
                value = el.value === '' ? '' : Number(el.value);
                if (Number.isNaN(value)) {
                    value = el.value;
                }
            } else {
                value = el.value;
            }
            setByPath(state.config, path, value);
            markDirty(state, true);
            if (path === 'image' && global.FcFenceStyleGui) {
                global.FcFenceStyleGui.updateImageFieldPreview(el, state.appBase);
            }
            syncAllEditorsFromConfig(state, {
                dev: { skipPathEditors: true }
            });
        }

        state.container.querySelectorAll('[data-config-path]').forEach(function (el) {
            if (el.type === 'radio' || el.type === 'checkbox') {
                el.addEventListener('change', function () {
                    applyConfigPathEl(el);
                });
                return;
            }
            el.addEventListener('input', function () {
                applyConfigPathEl(el);
            });
        });
    }

    function bindImagePickers(state) {
        if (state._fcImagePickerBound) {
            return;
        }
        state._fcImagePickerBound = true;

        state.container.addEventListener('click', function (e) {
            var Gui = global.FcFenceStyleGui;
            if (!Gui) {
                return;
            }

            var editBtn = e.target.closest('[data-fc-edit-image-url]');
            if (editBtn && state.container.contains(editBtn)) {
                var editInput =
                    typeof Gui.resolveImageFieldInput === 'function'
                        ? Gui.resolveImageFieldInput(editBtn)
                        : null;
                if (editInput && typeof Gui.enableImageUrlInput === 'function') {
                    Gui.enableImageUrlInput(editInput);
                }
                return;
            }

            var setBtn = e.target.closest('[data-fc-set-image]');
            if (!setBtn || !state.container.contains(setBtn)) {
                return;
            }
            if (typeof Gui.openMediaPickerForInput !== 'function') {
                return;
            }
            var input =
                typeof Gui.resolveImageFieldInput === 'function'
                    ? Gui.resolveImageFieldInput(setBtn)
                    : null;
            if (input) {
                Gui.openMediaPickerForInput(input, state.appBase, state.csrf);
            }
        });

        state.container.addEventListener(
            'focusout',
            function (e) {
                var Gui = global.FcFenceStyleGui;
                var input = e.target.closest('[data-fc-image-url-input]');
                if (!input || !state.container.contains(input) || input.disabled) {
                    return;
                }
                window.setTimeout(function () {
                    if (!state.container.contains(input)) {
                        return;
                    }
                    if (input === document.activeElement) {
                        return;
                    }
                    if (typeof Gui.disableImageUrlInput === 'function') {
                        Gui.disableImageUrlInput(input);
                    }
                }, 0);
            },
            true
        );
    }

    function bindJsonEditors(state) {
        var jsonChangeTimer;
        state.container.querySelectorAll('.fc-fs-json-editor[data-json-path]').forEach(function (textarea) {
            function applyFromTextarea() {
                var path = textarea.getAttribute('data-json-path');
                if (!path) {
                    return;
                }
                if (!applyJsonPath(state, path, textarea.value)) {
                    textarea.classList.add('fc-fs-json-editor--error');
                } else {
                    textarea.classList.remove('fc-fs-json-editor--error');
                }
            }

            textarea.addEventListener('input', function () {
                clearTimeout(jsonChangeTimer);
                jsonChangeTimer = setTimeout(applyFromTextarea, 450);
            });
            textarea.addEventListener('change', function () {
                clearTimeout(jsonChangeTimer);
                applyFromTextarea();
            });
        });
    }

    function bindSettingsNav(state) {
        var nav = state.container.querySelector('#fc-fs-settings-nav');
        if (!nav) {
            return;
        }
        nav.querySelectorAll('[data-settings-key]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-settings-key');
                applyFenceStyleEditTabState(state, 'modals', key);
                syncFenceStyleEditUrl(state);
            });
        });

        var search = state.container.querySelector('#fc-fs-settings-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                nav.querySelectorAll('[data-settings-key]').forEach(function (btn) {
                    var key = btn.getAttribute('data-settings-key') || '';
                    var text = (key + ' ' + btn.textContent).toLowerCase();
                    btn.classList.toggle('hidden', q !== '' && text.indexOf(q) === -1);
                });
            });
        }
    }

    function bindAdvancedJson(state, handlers) {
        handlers = handlers || {};
        var status = state.container.querySelector('#fc-fs-json-status');
        var formatBtn = state.container.querySelector('#fc-fs-json-format');
        var validateBtn = state.container.querySelector('#fc-fs-json-validate');
        var changeTimer;
        var applyingFromFullJson = false;

        function notifyToast(kind, message) {
            if (typeof handlers.onToast === 'function') {
                handlers.onToast(kind, message);
                return;
            }
            var T = global.FcAdminToast;
            if (!T) {
                return;
            }
            if (kind === 'error') {
                T.error(message);
            } else {
                T.success(message);
            }
        }

        function setStatus(msg, isError) {
            if (status) {
                status.textContent = msg;
                status.classList.toggle('text-red-600', !!isError);
                status.classList.toggle('text-slate-500', !isError);
            }
        }

        function applyFullJsonFromEditor() {
            if (applyingFromFullJson) {
                return;
            }
            try {
                var parsed = JSON.parse(getFullJsonValue(state.container));
                if (configsEqual(parsed, state.config)) {
                    setFullJsonError(state.container, false);
                    return;
                }
                applyingFromFullJson = true;
                state.config = parsed;
                markDirty(state, true);
                syncAllEditorsFromConfig(state, {
                    full: true,
                    dev: { skipFullJson: true }
                }).finally(function () {
                    applyingFromFullJson = false;
                });
                setFullJsonError(state.container, false);
                setStatus('Configuration updated from JSON.', false);
                } catch (e) {
                setFullJsonError(state.container, true);
                    setStatus('Invalid JSON: ' + e.message, true);
                }
        }

        state.flushFullJsonEditor = function () {
            clearTimeout(changeTimer);
            changeTimer = null;
            applyFullJsonFromEditor();
        };

        initFullJsonEditor(state, {
            onChange: function () {
                if (applyingFromFullJson) {
                    return;
                }
                clearTimeout(changeTimer);
                changeTimer = setTimeout(applyFullJsonFromEditor, 450);
            }
        }).then(function () {
            if (normalizeGuiTab(state.guiTab) === 'advanced') {
                refreshFullJsonEditor(state.container);
            }
        });

        if (formatBtn) {
            formatBtn.addEventListener('click', function () {
                try {
                    var parsed = JSON.parse(getFullJsonValue(state.container));
                    setFullJsonValue(state.container, JSON.stringify(parsed, null, 2));
                    setFullJsonError(state.container, false);
                    setStatus('', false);
                    notifyToast('ok', 'JSON formatted.');
                    refreshFullJsonEditor(state.container);
                    clearTimeout(changeTimer);
                    applyFullJsonFromEditor();
                } catch (e) {
                    setFullJsonError(state.container, true);
                    setStatus('', false);
                    notifyToast('error', 'Invalid JSON: ' + e.message);
                }
            });
        }

        if (validateBtn) {
            validateBtn.addEventListener('click', function () {
                try {
                    JSON.parse(getFullJsonValue(state.container));
                    setFullJsonError(state.container, false);
                    setStatus('', false);
                    notifyToast('ok', 'JSON is valid.');
                } catch (e) {
                    setFullJsonError(state.container, true);
                    setStatus('', false);
                    notifyToast('error', 'Invalid JSON: ' + e.message);
                }
            });
        }
    }

    function syncHeaderNavTabs(state) {
        if (!state || !state.container) {
            return;
        }
        ensureEditorVisibility(state);

        if (
            global.FcFenceStyleGui &&
            typeof global.FcFenceStyleGui.syncGuiNavTabUi === 'function'
        ) {
            global.FcFenceStyleGui.syncGuiNavTabUi(state.container, {
                guiTab: state.guiTab || FS_GUI_DEFAULT_TAB
            });
        }
    }

    function bindEditForm(container, slug, style, config, fileMeta, handlers) {
        var state = {
            slug: slug,
            style: style,
            config: deepClone(config),
            fileMeta: fileMeta || {},
            container: container,
            dirty: false,
            appBase: (document.body && document.body.getAttribute('data-fc-app-base')) || ''
        };
        state.appBase = state.appBase.replace(/\/+$/, '');
        state.csrf = (handlers && typeof handlers.csrf === 'string') ? handlers.csrf : '';
        state.fenceColorCatalog =
            handlers && Array.isArray(handlers.fenceColorCatalog) ? handlers.fenceColorCatalog : [];
        var urlState = readFenceStyleEditUrlState();
        state.guiTab = normalizeGuiTab(urlState.tab || FS_GUI_DEFAULT_TAB);
        state.activeSection = urlState.section || '';

        var guiHelpers = {
            setByPath: setByPath,
            getByPath: getByPath,
            markDirty: markDirty,
            syncPreview: syncPreview,
            initWysiwyg: function () {
                return initGuiWysiwyg(state);
            },
            syncDevFromConfig: function () {
                syncDevEditorsFromConfig(state);
            },
            syncGuiFromConfig: function (options) {
                return syncGuiEditorsFromConfig(state, guiHelpers, options);
            },
            getActiveGuiTab: function () {
                return resolveActiveGuiTab(state);
            },
            getGuiTab: function () {
                return state.guiTab || FS_GUI_DEFAULT_TAB;
            },
            onGuiTabChange: function (tab) {
                var previousTab = state.guiTab;
                if (previousTab === 'advanced' && tab !== 'advanced') {
                    if (typeof state.flushFullJsonEditor === 'function') {
                        state.flushFullJsonEditor();
                    }
                }
                applyFenceStyleEditTabState(state, tab, '');
                syncFenceStyleEditUrl(state);
            },
            onGuiSectionChange: function (sectionKey) {
                applyFenceStyleEditTabState(state, 'modals', sectionKey);
                syncFenceStyleEditUrl(state);
            }
        };
        state._guiHelpers = guiHelpers;

        if (global.FcFenceStyleGui && typeof global.FcFenceStyleGui.bind === 'function') {
            global.FcFenceStyleGui.bind(state, guiHelpers);
        }

        initEditorPage(state).then(function () {
            resetDirtyBaseline(state);
        });

        bindGeneralInputs(state);
        bindImagePickers(state);
        bindJsonEditors(state);
        bindSettingsNav(state);
        bindAdvancedJson(state, handlers);
        bindStylePreviewLightbox(container);

        var backBtn = container.querySelector('#fc-fence-style-back');
        var form = container.querySelector('#fc-fence-style-edit-form');
        var saveBtn = container.querySelector('#fc-fence-style-save');

        function goBack() {
            if (state.dirty && !global.confirm('You have unsaved changes. Leave anyway?')) {
                return;
            }
            if (global.FcFenceStyleWysiwyg) {
                global.FcFenceStyleWysiwyg.destroyInRoot(state.container);
            }
            if (global.FcFenceStyleCodeEditor) {
                global.FcFenceStyleCodeEditor.destroyInRoot(state.container);
            }
            closeStylePreviewLightbox();
            handlers.onBack();
        }

        if (backBtn) {
            backBtn.addEventListener('click', goBack);
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!saveBtn || (handlers && handlers.canEdit === false)) {
                    return;
                }

                var jsonErrors = [];
                flushDevEditorsToConfig(state);
                if (normalizeGuiTab(state.guiTab) === 'advanced') {
                    try {
                        state.config = JSON.parse(getFullJsonValue(container));
                        setFullJsonError(container, false);
                        } catch (err) {
                            jsonErrors.push('full config');
                        setFullJsonError(container, true);
                    }
                }

                if (jsonErrors.length) {
                    handlers.onToast('error', 'Fix invalid JSON in: ' + jsonErrors.join(', '));
                    return;
                }

                if (global.FcFenceStyleWysiwyg) {
                    global.FcFenceStyleWysiwyg.syncAll(state.container);
                }

                if (!String(state.config.title || '').trim()) {
                    handlers.onToast('error', 'Title is required.');
                    return;
                }

                saveBtn.disabled = true;
                handlers.onSave(state.config, function () {
                    saveBtn.disabled = false;
                    markDirty(state, false);
                }, function () {
                    saveBtn.disabled = false;
                });
            });
        }

        return state;
    }

    global.FcAdminFenceStyleEdit = {
        renderEditPage: renderEditPage,
        bindEditForm: bindEditForm,
        getByPath: getByPath,
        setByPath: setByPath
    };
})(window);
