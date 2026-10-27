/**
 * FC Admin — Changelogs: the View dialog. The list is server-rendered (VersionPresenter) and has no
 * write actions: writable/versions.csv ships with the code.
 */
(function (global) {
    'use strict';

    var FC = global.FC;
    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])';

    var root = null;
    var boot = {};
    var viewer = null;
    var openEl = null;
    var lastFocus = null;

    class VersionManagerPage extends FC.PageController {
        hydrate(container) {
            init(container);
        }

        destroy() {
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
            root.addEventListener('click', onListClick);
            document.addEventListener('keydown', onKeydown, true);
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

    /* ---------- List ---------- */

    function onListClick(e) {
        var btn = e.target.closest('[data-fc-versions-action="view"]');
        if (!btn || !root.contains(btn)) {
            return;
        }
        var record = recordFor(btn.getAttribute('data-id'));
        if (record) {
            openViewer(record);
        }
    }

    /* ---------- Dialog shell ---------- */

    function showDialog(el) {
        if (!el) {
            return;
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
        var target = el.querySelector('[role="dialog"]');
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
        if (lastFocus && typeof lastFocus.focus === 'function' && document.contains(lastFocus)) {
            lastFocus.focus({ preventScroll: true });
        }
        lastFocus = null;
    }

    function onDialogClick(e) {
        if (e.target.closest('[data-fc-versions-close]')) {
            e.preventDefault();
            closeDialog();
        }
    }

    function onKeydown(e) {
        if (!openEl) {
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            closeDialog();
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

    /* ---------- View ---------- */

    function openViewer(record) {
        if (!viewer) {
            return;
        }
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
        // record.description is HtmlSanitizer output (the presenter cleans it on every render).
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

    FC.PageRegistry.register('changelogs', new VersionManagerPage());
})(window);
