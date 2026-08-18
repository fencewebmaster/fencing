/**
 * FC Admin — shared DOM/string formatting helpers.
 * Extracted from 14 files that each carried a byte-identical copy of
 * escapeHtml, and the formatHeader helper duplicated between the
 * store-products/system-products pages.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.util = FC.util || {};

    /**
     * @param {*} text
     * @returns {string} HTML-escaped string safe for innerHTML interpolation
     */
    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Turn a snake_case column key into a human-readable header label.
     * Shared verbatim body from products/store-products.js and products/system-products.js.
     * @param {string} label
     * @returns {string}
     */
    function formatHeader(label) {
        return String(label || '')
            .replace(/_/g, ' ')
            .toLowerCase()
            .replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
    }

    FC.util.escapeHtml = escapeHtml;
    FC.util.formatHeader = formatHeader;
})(window);
