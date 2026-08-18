/**
 * FC Admin — Settings tab controller base class.
 * Tab controllers (SystemTabController, ...) extend this to reach the
 * settings shell's shared mutable state, flash instance, and header-actions
 * repaint without each tab wiring up its own reference plumbing into
 * settings.js — see the FC.Settings exposure block at the bottom of that file.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.Settings = FC.Settings || {};

    class SettingsTabController {
        /** @returns {Object} the settings shell's shared mutable state */
        get state() {
            return FC.Settings.state;
        }

        /** @returns {Object} the shared FlashMessage instance (see utils/flash.js) */
        get flash() {
            return FC.Settings.flash;
        }

        /** Repaints the shell's tab header/save-button dirty indicators. */
        updateHeaderActions() {
            FC.Settings.updateHeaderActions();
        }

        /** @returns {string} the app's base path, e.g. for building asset URLs */
        getAppBase() {
            return (document.body && document.body.getAttribute('data-fc-app-base')) || '..';
        }

        /**
         * Normalizes a hex color input (3 or 6 digit, with #) to lowercase
         * 6-digit form. Shared by Theme and Fence Colors, both of which let
         * an admin type a hex value into a text field alongside a color picker.
         * @param {string} value
         * @returns {string|null} null when the input isn't a valid hex color
         */
        normalizeHexInput(value) {
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

        /** Populates this tab's DOM fields from state. Override in subclasses. */
        paint() {}

        /** Binds this tab's event listeners; must be idempotent. Override in subclasses. */
        bind() {}

        /** Persists this tab's state to the server. Override in subclasses. */
        save() {}
    }

    FC.Settings.TabController = SettingsTabController;
})(window);
