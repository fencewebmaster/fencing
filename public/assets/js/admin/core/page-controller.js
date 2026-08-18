/**
 * FC Admin — page lifecycle base class + registry.
 *
 * Replaces core/app.js's hardcoded `window.FcAdminXxx && typeof
 * window.FcAdminXxx.hydrateFromServer === 'function'` checks (one near-
 * identical block per route) and the four ad hoc `contentEl._fcSpDestroy` /
 * `_fcSysDestroy` / `_fcSettingsDestroy` / `_fcGalleryDestroy` properties
 * used for page teardown on navigation. Each page module registers a
 * PageController instance under its route key; core/app.js looks the
 * controller up generically.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};

    class PageController {
        /**
         * Called by the router when this route becomes active and the
         * server has already rendered the target markup in place. Base
         * implementation is a no-op.
         * @param {Element} contentEl
         */
        hydrate(contentEl) {}

        /**
         * Called by the router right before it tears down this route's
         * content (e.g. navigating to a different route). Base
         * implementation is a no-op.
         */
        destroy() {}
    }

    var registry = {};

    var PageRegistry = {
        /**
         * @param {string} routeKey
         * @param {PageController} controller
         */
        register: function (routeKey, controller) {
            registry[routeKey] = controller || new PageController();
        },
        /** @returns {PageController|null} */
        get: function (routeKey) {
            return registry[routeKey] || null;
        }
    };

    FC.PageController = PageController;
    FC.PageRegistry = PageRegistry;
})(window);
