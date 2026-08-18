/**
 * FC Admin — cross-feature "close every other open dropdown" registry.
 *
 * The audit found the multi-select/filter dropdowns in entries/filters.js,
 * dashboard/entries-date-filter.js, core/cache-purge.js, and
 * products/store-products-color-filter.js each hardcoded knowledge of the
 * OTHER features' specific CSS selectors to close them when one dropdown
 * opens (e.g. entries/filters.js reaching into
 * `.fc-entries-date-dropdown__panel`, a selector owned by a different file).
 * That's a real coupling bug, not just duplication: any of those features
 * changing its markup silently breaks the others' "close sibling" behavior.
 *
 * This registry replaces the hardcoded cross-references with a neutral
 * open-dropdown tracker. Each widget keeps its own open/close rendering
 * logic (label formatting, checkbox state, positioning all genuinely
 * differ per widget) — it only needs to call openExclusive() right before
 * showing its panel, and notifyClosed() whenever it hides its panel by any
 * other means (Escape key, outside click, toggle click while open).
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.components = FC.components || {};

    var openEntries = [];

    /**
     * Closes every other registered dropdown, then registers `root` as open.
     * @param {Element} root identity key for this dropdown instance
     * @param {function(): void} close hides this dropdown's panel
     */
    function openExclusive(root, close) {
        var toClose = openEntries.filter(function (entry) {
            return entry.root !== root;
        });
        openEntries = openEntries.filter(function (entry) {
            return entry.root === root;
        });
        toClose.forEach(function (entry) {
            entry.close();
        });
        if (!openEntries.length) {
            openEntries.push({ root: root, close: close });
        }
    }

    /** Removes `root` from the open registry without invoking its close callback. */
    function notifyClosed(root) {
        openEntries = openEntries.filter(function (entry) {
            return entry.root !== root;
        });
    }

    FC.components.DropdownRegistry = {
        openExclusive: openExclusive,
        notifyClosed: notifyClosed
    };
})(window);
