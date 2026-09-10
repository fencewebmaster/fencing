/**
 * FC Admin — light / dark appearance toggle (persisted in localStorage).
 */
(function (global) {
    'use strict';

    var html = document.documentElement;

    // core/store.js loads right before this file on both admin pages; the guard
    // only matters if that script failed to load, where defaults beat a throw.
    function store() {
        return (global.FC && global.FC.store) || null;
    }

    function normalizeTheme(value) {
        return value === 'dark' ? 'dark' : 'light';
    }

    function readStoredTheme() {
        var s = store();
        return s ? s.get('ui.appearance') : 'light';
    }

    function updateSwitcher(theme) {
        document.querySelectorAll('[data-fc-admin-theme-set]').forEach(function (btn) {
            var mode = btn.getAttribute('data-fc-admin-theme-set');
            var active = mode === theme;
            btn.classList.toggle('fc-admin-theme-switcher__btn--active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function applyTheme(theme, options) {
        options = options || {};
        theme = normalizeTheme(theme);
        html.setAttribute('data-fc-admin-theme', theme);

        if (!options.skipStore) {
            var s = store();
            if (s) {
                s.set('ui.appearance', theme);
            }
        }

        updateSwitcher(theme);

        if (!options.silent) {
            global.dispatchEvent(
                new CustomEvent('fc-admin-theme-change', {
                    detail: { theme: theme }
                })
            );
        }
    }

    function bindSwitcher() {
        document.querySelectorAll('[data-fc-admin-theme-set]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyTheme(btn.getAttribute('data-fc-admin-theme-set'));
            });
        });
    }

    function init() {
        applyTheme(readStoredTheme(), { skipStore: true, silent: true });
        bindSwitcher();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window);
