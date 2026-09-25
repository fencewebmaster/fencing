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

        /** @returns {boolean} false when that save is already in flight (see FC.util.setSaving) */
        startSaving(buttonId) {
            return FC.util.setSaving(document.getElementById(buttonId), true);
        }

        stopSaving(buttonId) {
            FC.util.setSaving(document.getElementById(buttonId), false);
        }

        /**
         * Shows a panel's pinned overview bar ([data-fc-overview-pin]) once its overview card has scrolled up
         * out of the settings scroll area, and hides it again when the card comes back.
         * @param {Element|null} slot
         * @param {Element|null} card
         */
        pinOverview(slot, card) {
            var scroller = document.querySelector('[data-fc-settings-scroll]');
            if (!slot || !card || !scroller || typeof global.IntersectionObserver !== 'function') {
                return;
            }
            var bar = slot.querySelector('.fc-overview-pin__bar');
            new global.IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    // Only above the top: a card that is below the fold, or on a hidden tab, keeps the bar away.
                    var above = entry.boundingClientRect.height > 0 && entry.boundingClientRect.bottom <= (entry.rootBounds ? entry.rootBounds.top : 0) + 1;
                    var show = !entry.isIntersecting && above;
                    slot.classList.toggle('is-visible', show);
                    bar.inert = !show;
                });
            }, { root: scroller, rootMargin: '-56px 0px 0px 0px' }).observe(card);
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
