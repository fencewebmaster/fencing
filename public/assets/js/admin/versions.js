/**
 * FC Admin — Releases: the row Actions menus, the Add/Edit and View dialogs, Publish/Unpublish and Delete.
 * The list is server-rendered (VersionPresenter); every successful write reloads it.
 */
(function (global) {
    'use strict';

    var FC = global.FC;
    var FLASH = new FC.util.FlashMessage({ storageKey: 'fc-versions-save-flash' });
    var SEMVER = /^v?(0|[1-9]\d{0,8})\.(0|[1-9]\d{0,8})\.(0|[1-9]\d{0,8})$/i;
    var DATE_INPUT = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/;
    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])';

    var root = null;
    var boot = {};
    var viewer = null;
    var editor = null;
    var openEl = null;
    var lastFocus = null;
    var viewing = null;
    var editing = null;
    var mode = 'automatic';
    var autoRequest = 0;
    var editorReady = null;
    var baseline = null;
    var busy = false;
    var leaving = false;

    class VersionManagerPage extends FC.PageController {
        hydrate(container) {
            init(container);
        }

        destroy() {
            closeMenus();
            closeDialog();
        }
    }

    function init(container) {
        var scope = container || document;
        root = scope.matches && scope.matches('[data-fc-version-manager]')
            ? scope
            : scope.querySelector('[data-fc-version-manager]');
        if (!root) {
            return;
        }
        boot = readBootstrap();

        if (root.getAttribute('data-fc-versions-bound') !== '1') {
            root.setAttribute('data-fc-versions-bound', '1');
            // Out of #fc-admin-main, whose z-index:1 would leave the sidebar and topbar above the backdrop.
            viewer = adoptDialog(root.querySelector('[data-fc-versions-viewer]'));
            editor = adoptDialog(root.querySelector('[data-fc-versions-editor]'));
            root.addEventListener('click', onListClick);
            bindEditor();
            document.addEventListener('keydown', onKeydown, true);
            document.addEventListener('keydown', onMenuKeydown);
            document.addEventListener('click', onDocumentClick);
            global.addEventListener('resize', repositionMenu);
            global.addEventListener('scroll', repositionMenu, true);
            global.addEventListener('beforeunload', onBeforeUnload);
        }

        var flash = FLASH.consume();
        if (flash && global.FcAdminToast) {
            if (flash.type === 'error') {
                global.FcAdminToast.error(flash.message);
            } else {
                global.FcAdminToast.success(flash.message);
            }
        }
    }

    function readBootstrap() {
        var el = document.getElementById('fc-versions-bootstrap');
        if (!el) {
            return {};
        }
        try {
            return JSON.parse(el.textContent || '{}') || {};
        } catch (err) {
            return {};
        }
    }

    function adoptDialog(el) {
        if (!el) {
            return null;
        }
        document.body.appendChild(el);
        el.addEventListener('click', onDialogClick);
        return el;
    }

    function recordFor(id) {
        var records = boot.records || {};
        return Object.prototype.hasOwnProperty.call(records, String(id)) ? records[String(id)] : null;
    }

    function labelOf(record) {
        return record.type_label + ' ' + record.version_label;
    }

    /* ---------- List actions ---------- */

    function onListClick(e) {
        if (e.target.closest('[data-fc-versions-add]')) {
            e.preventDefault();
            openEditor(null);
            return;
        }

        var toggle = e.target.closest('[data-fc-versions-menu-toggle]');
        if (toggle && root.contains(toggle)) {
            e.preventDefault();
            var toggled = toggle.closest('[data-fc-versions-menu]');
            if (toggled.classList.contains('is-open')) {
                closeMenu(toggled, false);
            } else {
                // A keyboard click (detail 0) moves focus into the menu; a pointer click leaves it on the toggle.
                openMenu(toggled, e.detail === 0);
            }
            return;
        }

        var btn = e.target.closest('[data-fc-versions-action]');
        if (!btn || !root.contains(btn)) {
            return;
        }
        var record = recordFor(btn.getAttribute('data-id'));
        if (!record) {
            return;
        }
        var action = btn.getAttribute('data-fc-versions-action');

        // From a row menu: close it and give focus and the saving state to its toggle, which stays on screen.
        var rowMenu = btn.closest('[data-fc-versions-menu]');
        if (rowMenu) {
            closeMenu(rowMenu, true);
            btn = rowMenu.querySelector('[data-fc-versions-menu-toggle]');
        }

        switch (action) {
            case 'view':
                openViewer(record);
                break;
            case 'edit':
                openEditor(record);
                break;
            case 'publish':
                confirmPublish(record, true, btn);
                break;
            case 'unpublish':
                confirmPublish(record, false, btn);
                break;
            case 'delete':
                confirmDelete(record, btn);
                break;
        }
    }

    function confirmPublish(record, publish, btn) {
        ask({
            title: publish ? 'Publish version?' : 'Unpublish version?',
            message: publish
                ? '{version} will appear on the public version history page.'
                : '{version} will be hidden from the public version history page.',
            emphasis: { version: labelOf(record) },
            confirmLabel: publish ? 'Publish' : 'Unpublish',
            variant: publish ? 'info' : 'warning'
        }).then(function (ok) {
            if (ok) {
                runRowAction(publish ? 'publish' : 'unpublish', record, btn);
            }
        });
    }

    function confirmDelete(record, btn) {
        ask({
            title: 'Delete version?',
            message: '{version} will be removed from the version history. This cannot be undone.',
            emphasis: { version: labelOf(record) },
            confirmLabel: 'Delete',
            variant: 'error'
        }).then(function (ok) {
            if (ok) {
                runRowAction('delete', record, btn);
            }
        });
    }

    function ask(opts) {
        if (global.FcAdminModal) {
            return global.FcAdminModal.confirm(opts);
        }
        return Promise.resolve(global.confirm(opts.message.replace('{version}', opts.emphasis.version)));
    }

    function runRowAction(action, record, btn) {
        btn.disabled = true;
        btn.classList.add('is-saving');
        post(action, { id: record.id })
            .then(function (body) {
                reloadWith(body.message);
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.classList.remove('is-saving');
                if (global.FcAdminToast) {
                    global.FcAdminToast.error(err.message);
                }
            });
    }

    function reloadWith(message) {
        FLASH.set(message, 'success');
        leaving = true;
        global.location.reload();
    }

    /* ---------- Row Actions menus ---------- */

    function openMenu(menu, focusFirst) {
        closeMenus();
        var panel = menu.querySelector('[role="menu"]');
        panel.hidden = false;
        menu.querySelector('[data-fc-versions-menu-toggle]').setAttribute('aria-expanded', 'true');
        menu.classList.add('is-open');
        positionMenu(menu);
        var items = menuItems(menu);
        if (focusFirst && items.length) {
            items[0].focus({ preventScroll: true });
        }
    }

    function closeMenu(menu, refocus) {
        var panel = menu.querySelector('[role="menu"]');
        var toggle = menu.querySelector('[data-fc-versions-menu-toggle]');
        panel.hidden = true;
        panel.style.left = '';
        panel.style.top = '';
        toggle.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
        if (refocus) {
            toggle.focus({ preventScroll: true });
        }
    }

    function closeMenus() {
        if (root) {
            root.querySelectorAll('[data-fc-versions-menu].is-open').forEach(function (menu) {
                closeMenu(menu, false);
            });
        }
    }

    function openMenuEl() {
        return root ? root.querySelector('[data-fc-versions-menu].is-open') : null;
    }

    function menuItems(menu) {
        return Array.prototype.slice.call(menu.querySelectorAll('[role="menuitem"]:not(:disabled)'));
    }

    // Fixed to the viewport so the table's scroll box cannot clip it: right-aligned under the toggle, flipped above near the bottom.
    function positionMenu(menu) {
        var panel = menu.querySelector('[role="menu"]');
        if (panel.hidden) {
            return;
        }
        var rect = menu.querySelector('[data-fc-versions-menu-toggle]').getBoundingClientRect();
        var gap = 6;
        var viewportWidth = global.innerWidth || document.documentElement.clientWidth || 0;
        var viewportHeight = global.innerHeight || document.documentElement.clientHeight || 0;
        var width = panel.offsetWidth;
        var height = panel.offsetHeight;
        var left = Math.min(Math.max(8, rect.right - width), viewportWidth - width - 8);
        var top = rect.bottom + gap;
        if (top + height > viewportHeight - 8 && rect.top - gap - height >= 8) {
            top = rect.top - gap - height;
        }
        panel.style.left = Math.round(left) + 'px';
        panel.style.top = Math.round(top) + 'px';
    }

    function repositionMenu() {
        var menu = openMenuEl();
        if (menu) {
            positionMenu(menu);
        }
    }

    function onDocumentClick(e) {
        if (openMenuEl() && !(e.target.closest && e.target.closest('[data-fc-versions-menu].is-open'))) {
            closeMenus();
        }
    }

    function onMenuKeydown(e) {
        if (openEl) {
            return; // an open dialog owns the keyboard
        }
        var menu = openMenuEl();
        if (!menu) {
            // ArrowDown on a closed toggle opens its menu, as a menu button should.
            var toggle = e.key === 'ArrowDown' && e.target.closest ? e.target.closest('[data-fc-versions-menu-toggle]') : null;
            if (toggle && root && root.contains(toggle)) {
                e.preventDefault();
                openMenu(toggle.closest('[data-fc-versions-menu]'), true);
            }
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            closeMenu(menu, true);
            return;
        }
        if (e.key === 'Tab') {
            closeMenu(menu, false);
            return;
        }
        var items = menuItems(menu);
        var index = items.indexOf(document.activeElement);
        var next = null;
        if (e.key === 'ArrowDown') {
            next = items[index < 0 ? 0 : (index + 1) % items.length];
        } else if (e.key === 'ArrowUp') {
            next = items[index <= 0 ? items.length - 1 : index - 1];
        } else if (e.key === 'Home') {
            next = items[0];
        } else if (e.key === 'End') {
            next = items[items.length - 1];
        }
        if (next) {
            e.preventDefault();
            next.focus({ preventScroll: true });
        }
    }

    /* ---------- API ---------- */

    function post(action, body) {
        return request(global.fcApiUrl('versions', 'action=' + encodeURIComponent(action)), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(Object.assign({ csrf: boot.csrf || '' }, body))
        });
    }

    function request(url, options) {
        return fetch(url, options || { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (res) {
                return res.json()
                    .catch(function () {
                        return { ok: false, error: 'The server sent an unexpected response (' + res.status + ').' };
                    })
                    .then(function (body) {
                        if (!res.ok || !body || !body.ok) {
                            var err = new Error((body && body.error) || 'The request failed.');
                            err.errors = (body && body.errors) || {};
                            err.status = res.status;
                            throw err;
                        }
                        return body;
                    });
            }, function () {
                throw new Error('Could not reach the server. Check your connection and try again.');
            });
    }

    /* ---------- Dialog shell ---------- */

    function showDialog(el, focusTarget) {
        if (!el) {
            return;
        }
        if (openEl && openEl !== el) {
            closeDialog();
        }
        if (!openEl) {
            lastFocus = document.activeElement;
            if (global.FcAdminModal) {
                global.FcAdminModal.lockScroll();
            }
        }
        openEl = el;
        el.hidden = false;
        global.requestAnimationFrame(function () {
            el.classList.add('is-open');
        });
        var target = focusTarget || el.querySelector('[role="dialog"]');
        if (target) {
            target.focus({ preventScroll: true });
        }
    }

    function closeDialog() {
        if (!openEl) {
            return;
        }
        var el = openEl;
        openEl = null;
        el.classList.remove('is-open');
        el.hidden = true;
        if (global.FcAdminModal) {
            global.FcAdminModal.unlockScroll();
        }
        if (el === editor) {
            baseline = null;
            autoRequest++;
        }
        if (lastFocus && typeof lastFocus.focus === 'function' && document.contains(lastFocus)) {
            lastFocus.focus({ preventScroll: true });
        }
        lastFocus = null;
    }

    function requestClose() {
        if (openEl !== editor || !isDirty()) {
            closeDialog();
            return;
        }
        ask({
            title: 'Discard changes?',
            message: 'Your changes to this version have not been saved.',
            confirmLabel: 'Discard',
            cancelLabel: 'Keep editing',
            variant: 'warning'
        }).then(function (ok) {
            if (ok) {
                closeDialog();
            }
        });
    }

    function onDialogClick(e) {
        if (e.target.closest('[data-fc-versions-close]')) {
            e.preventDefault();
            requestClose();
            return;
        }
        if (e.target.closest('[data-fc-versions-viewer-edit]')) {
            var record = viewing;
            closeDialog();
            if (record) {
                openEditor(record);
            }
            return;
        }
        var modeBtn = e.target.closest('[data-fc-versions-mode]');
        if (modeBtn) {
            setMode(modeBtn.getAttribute('data-fc-versions-mode'), true);
            return;
        }
        if (e.target.closest('[data-fc-versions-input="published"]')) {
            setPublished(!isPublishedOn());
            return;
        }
        var side = e.target.closest('[data-fc-versions-published]');
        if (side) {
            setPublished(side.getAttribute('data-fc-versions-published') === '1');
        }
    }

    /* The Published control is the shared .fc-mode-switch (No | track | Yes), not a checkbox. */
    function isPublishedOn() {
        var track = field('published');
        return !!track && track.getAttribute('aria-checked') === 'true';
    }

    function setPublished(on) {
        var track = field('published');
        if (!track) {
            return;
        }
        track.setAttribute('aria-checked', on ? 'true' : 'false');
        editor.querySelectorAll('[data-fc-versions-published]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', (btn.getAttribute('data-fc-versions-published') === '1') === on ? 'true' : 'false');
        });
    }

    function onKeydown(e) {
        if (!openEl) {
            return;
        }
        // A TinyMCE dialog or a confirm popup on top handles its own keys.
        var modalRoot = document.getElementById('fc-admin-modal-root');
        if (document.querySelector('.tox-dialog-wrap') || (modalRoot && modalRoot.children.length)) {
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            requestClose();
        } else if (e.key === 'Tab') {
            trapTab(e);
        }
    }

    function trapTab(e) {
        var items = Array.prototype.filter.call(openEl.querySelectorAll(FOCUSABLE), function (el) {
            return el.offsetParent !== null;
        });
        if (!items.length) {
            return;
        }
        var first = items[0];
        var last = items[items.length - 1];
        if (e.shiftKey && (document.activeElement === first || !openEl.contains(document.activeElement))) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && (document.activeElement === last || !openEl.contains(document.activeElement))) {
            e.preventDefault();
            first.focus();
        }
    }

    function onBeforeUnload(e) {
        if (!leaving && openEl === editor && isDirty()) {
            e.preventDefault();
            e.returnValue = '';
        }
    }

    /* ---------- View ---------- */

    function openViewer(record) {
        if (!viewer) {
            return;
        }
        viewing = record;
        text(viewer, '[data-fc-versions-viewer-title]', record.version_label);
        text(viewer, '[data-fc-versions-viewer-meta]', record.type_label + ' · Released ' + record.date_label);
        text(viewer, '[data-fc-versions-viewer-mode]', record.mode_label || '—');
        text(viewer, '[data-fc-versions-viewer-created]', record.created_label || '—');
        text(viewer, '[data-fc-versions-viewer-updated]', record.updated_label || '—');

        var badges = viewer.querySelector('[data-fc-versions-viewer-badges]');
        badges.textContent = '';
        badges.appendChild(badge('fc-versions-type fc-versions-type--' + record.type, record.type_label));
        badges.appendChild(badge('fc-versions-level fc-versions-level--' + record.version_level, record.level_label));
        badges.appendChild(badge(
            'fc-versions-status ' + (record.published ? 'fc-versions-status--published' : 'fc-versions-status--draft'),
            record.published ? 'Published' : 'Unpublished'
        ));

        var notes = viewer.querySelector('[data-fc-versions-viewer-notes]');
        // record.description is HtmlSanitizer output (cleaned on save and again by the presenter).
        notes.innerHTML = record.description || '<p class="fc-release-notes__empty">No release notes.</p>';

        showDialog(viewer);
    }

    function badge(className, label) {
        var el = document.createElement('span');
        el.className = className;
        el.textContent = label;
        return el;
    }

    function text(scope, selector, value) {
        var el = scope.querySelector(selector);
        if (el) {
            el.textContent = value;
        }
    }

    /* ---------- Add / Edit ---------- */

    function field(name) {
        return editor ? editor.querySelector('[data-fc-versions-input="' + name + '"]') : null;
    }

    function bindEditor() {
        if (!editor) {
            return;
        }
        var form = editor.querySelector('[data-fc-versions-form]');
        form.addEventListener('submit', save);
        // Any edit retires the summary alert; field errors clear one by one and return on the next save.
        ['input', 'change'].forEach(function (type) {
            form.addEventListener(type, function () {
                editor.querySelector('[data-fc-versions-form-alert]').hidden = true;
            });
        });
        field('type').addEventListener('change', refreshNumber);
        field('version_level').addEventListener('change', refreshNumber);
        field('version').addEventListener('input', function () {
            refreshManualHint();
            clearFieldError('version');
        });
        field('date_time').addEventListener('input', function () {
            clearFieldError('date_time');
        });
    }

    function openEditor(record) {
        if (!editor) {
            return;
        }
        editing = record;
        text(editor, '[data-fc-versions-editor-title]', record ? 'Edit ' + labelOf(record) : 'Add Release');
        field('type').value = record ? record.type : 'full';
        field('version_level').value = record ? record.version_level : 'patch';
        field('date_time').value = record && record.date_input ? record.date_input : nowForInput();
        field('version').value = record ? record.version : '';
        setPublished(record ? !!record.published : false);
        clearErrors();
        setBusy(false);
        setMode(record ? record.version_mode : 'automatic', false);

        var html = record ? record.description : '';
        showDialog(editor, field('type'));
        // Provisional baseline from the raw notes: syncing now would copy the previous record's editor content back.
        baseline = snapshot(html);
        setDescription(html).then(function () {
            if (openEl === editor && editing === record) {
                baseline = snapshot();
            }
        });
    }

    /** Mounts TinyMCE on first open (it measures its container, so never while hidden), then loads the notes. */
    function setDescription(html) {
        var textarea = field('description');
        textarea.value = html;
        var W = global.FcFenceStyleWysiwyg;
        if (!W) {
            return Promise.resolve();
        }
        if (!editorReady) {
            editorReady = W.initInRoot(editor);
        }
        return editorReady.then(function () {
            var instance = global.tinymce && textarea.id ? global.tinymce.get(textarea.id) : null;
            if (instance) {
                instance.setContent(html);
                instance.undoManager.clear();
            }
        });
    }

    function readDescription() {
        if (global.FcFenceStyleWysiwyg) {
            global.FcFenceStyleWysiwyg.syncAll(editor);
        }
        return field('description').value;
    }

    function setMode(next, fromClick) {
        mode = next === 'manual' ? 'manual' : 'automatic';
        editor.querySelectorAll('[data-fc-versions-mode]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.getAttribute('data-fc-versions-mode') === mode ? 'true' : 'false');
        });
        editor.querySelector('[data-fc-versions-auto]').hidden = mode !== 'automatic';
        editor.querySelector('[data-fc-versions-manual]').hidden = mode !== 'manual';
        clearFieldError('version');

        if (mode === 'manual' && fromClick) {
            var input = field('version');
            // Start from the number Automatic would have used, so a small tweak is all it takes.
            var auto = editor.querySelector('[data-fc-versions-auto-value]').getAttribute('data-version') || '';
            if (input.value.trim() === '' && auto !== '') {
                input.value = auto;
            }
            input.focus();
        }
        refreshNumber();
    }

    function refreshNumber() {
        if (mode === 'manual') {
            refreshManualHint();
            return;
        }

        var type = field('type').value;
        var level = field('version_level').value;
        var valueEl = editor.querySelector('[data-fc-versions-auto-value]');
        var hintEl = editor.querySelector('[data-fc-versions-auto-hint]');
        var levelLabel = (boot.levels || {})[level] || level;

        // The server keeps an automatic number while its level is unchanged; every type shares one number line.
        if (editing && editing.version_mode === 'automatic' && editing.version_level === level) {
            showAuto(valueEl, hintEl, editing.version, 'Keeps its number: the level is unchanged.');
            return;
        }

        var ticket = ++autoRequest;
        showAuto(valueEl, hintEl, '', 'Working out the next ' + levelLabel + ' version…');
        var query = 'action=next-version&type=' + encodeURIComponent(type) + '&level=' + encodeURIComponent(level) +
            '&exclude=' + encodeURIComponent(editing ? editing.id : 0);
        request(global.fcApiUrl('versions', query))
            .then(function (body) {
                if (ticket !== autoRequest) {
                    return;
                }
                var baseLabel = (boot.types || {})[body.base_type] || '';
                showAuto(
                    valueEl,
                    hintEl,
                    body.version,
                    body.base
                        ? 'Next ' + levelLabel + ' after ' + (baseLabel ? baseLabel + ' ' : '') + 'v' + body.base + '.'
                        : 'The first version.'
                );
            })
            .catch(function (err) {
                if (ticket === autoRequest) {
                    showAuto(valueEl, hintEl, '', err.message);
                }
            });
    }

    function showAuto(valueEl, hintEl, version, hint) {
        valueEl.textContent = version ? 'v' + version : '—';
        valueEl.setAttribute('data-version', version || '');
        hintEl.textContent = hint;
    }

    function refreshManualHint() {
        var hintEl = editor.querySelector('[data-fc-versions-manual-hint]');
        var raw = field('version').value.trim();
        var m = SEMVER.exec(raw);
        if (!m) {
            hintEl.textContent = 'MAJOR.MINOR.PATCH, for example 1.4.0';
            return;
        }
        var level = field('version_level').value;
        var note = '';
        if (level === 'major' && (m[2] !== '0' || m[3] !== '0')) {
            note = ' (a Major release usually ends in .0.0)';
        } else if (level === 'minor' && m[3] !== '0') {
            note = ' (a Minor release usually ends in .0)';
        }
        hintEl.textContent = 'Saved as v' + m[1] + '.' + m[2] + '.' + m[3] + note;
    }

    function collect(description) {
        var canPublish = !!field('published');
        return {
            id: editing ? editing.id : 0,
            type: field('type').value,
            version_level: field('version_level').value,
            version_mode: mode,
            version: mode === 'manual' ? field('version').value.trim() : '',
            date_time: field('date_time').value,
            // Without the publish permission the flag is not on the form: keep what the version had.
            published: canPublish ? isPublishedOn() : !!(editing && editing.published),
            description: description === undefined ? readDescription() : description,
            expected_updated_at: editing ? editing.updated_at : ''
        };
    }

    function snapshot(description) {
        var data = collect(description);
        delete data.expected_updated_at;
        return JSON.stringify(data);
    }

    function isDirty() {
        return baseline !== null && snapshot() !== baseline;
    }

    function save(e) {
        e.preventDefault();
        if (busy) {
            return;
        }
        clearErrors();

        var data = collect();
        var errors = {};
        if (!DATE_INPUT.test(data.date_time)) {
            errors.date_time = 'Enter the release date and time.';
        }
        if (data.version_mode === 'manual' && !SEMVER.test(data.version)) {
            errors.version = 'Use the MAJOR.MINOR.PATCH format, for example 1.4.0.';
        }
        if (Object.keys(errors).length) {
            showErrors(errors, 'Please check the highlighted fields.');
            return;
        }

        setBusy(true);
        post(editing ? 'update' : 'create', data)
            .then(function (body) {
                baseline = null;
                reloadWith(body.message);
            })
            .catch(function (err) {
                setBusy(false);
                showErrors(err.errors || {}, err.message);
            });
    }

    function setBusy(state) {
        busy = state;
        var btn = editor.querySelector('[data-fc-versions-save]');
        btn.disabled = state;
        btn.classList.toggle('is-saving', state);
    }

    // Toasts sit under dialogs (#fc-admin-main traps them), so form errors are shown on the form itself.
    function showErrors(errors, message) {
        var alert = editor.querySelector('[data-fc-versions-form-alert]');
        alert.textContent = message || 'The version could not be saved.';
        alert.hidden = false;
        var firstInvalid = null;
        Object.keys(errors).forEach(function (name) {
            var el = editor.querySelector('[data-fc-versions-error="' + name + '"]');
            if (el) {
                el.textContent = errors[name];
                el.hidden = false;
            }
            var input = field(name);
            if (input) {
                input.setAttribute('aria-invalid', 'true');
                firstInvalid = firstInvalid || input;
            }
        });
        if (firstInvalid && firstInvalid.offsetParent !== null && firstInvalid.tagName !== 'TEXTAREA') {
            firstInvalid.focus();
        } else {
            alert.scrollIntoView({ block: 'nearest' });
        }
    }

    function clearFieldError(name) {
        var el = editor.querySelector('[data-fc-versions-error="' + name + '"]');
        if (el) {
            el.hidden = true;
            el.textContent = '';
        }
        var input = field(name);
        if (input) {
            input.removeAttribute('aria-invalid');
        }
    }

    function clearErrors() {
        var alert = editor.querySelector('[data-fc-versions-form-alert]');
        alert.hidden = true;
        alert.textContent = '';
        editor.querySelectorAll('[data-fc-versions-error]').forEach(function (el) {
            clearFieldError(el.getAttribute('data-fc-versions-error'));
        });
    }

    function nowForInput() {
        var d = new Date();
        var pad = function (n) {
            return (n < 10 ? '0' : '') + n;
        };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
            'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    FC.PageRegistry.register('releases', new VersionManagerPage());
})(window);
