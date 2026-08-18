/**
 * FC Admin — light / dark appearance toggle (persisted in localStorage).
 */
(function (global) {
    'use strict';

    var STORAGE_KEY = 'fc-admin-appearance';
    var html = document.documentElement;

    function normalizeTheme(value) {
        return value === 'dark' ? 'dark' : 'light';
    }

    function readStoredTheme() {
        try {
            var stored = localStorage.getItem(STORAGE_KEY);
            return normalizeTheme(stored);
        } catch (e) {
            return 'light';
        }
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
            try {
                localStorage.setItem(STORAGE_KEY, theme);
            } catch (e) {
                /* ignore quota / private mode */
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
