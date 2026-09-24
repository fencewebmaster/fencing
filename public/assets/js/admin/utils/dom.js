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

    /**
     * Spins a Save button in place while its request runs (`.btn.is-saving` in buttons.css).
     * @param {HTMLElement|null} btn
     * @param {boolean} on
     * @returns {boolean} false when turning on a save that is already in flight, so double clicks don't re-post
     */
    function setSaving(btn, on) {
        if (!btn) {
            return true;
        }
        if (on && btn.classList.contains('is-saving')) {
            return false;
        }
        btn.classList.toggle('is-saving', !!on);
        if (on) {
            btn.setAttribute('aria-busy', 'true');
        } else {
            btn.removeAttribute('aria-busy');
        }
        return true;
    }

    /* Tags the description may keep when it renders. Everything else becomes its own text. */
    var DESCRIPTION_TAGS = {
        P: 1, BR: 1, DIV: 1, SPAN: 1, UL: 1, OL: 1, LI: 1, STRONG: 1, B: 1,
        EM: 1, I: 1, U: 1, SMALL: 1, H3: 1, H4: 1, H5: 1, A: 1
    };

    /* Dropped whole rather than unwrapped: their text is code, not copy, and unwrapping a
       <script> would print its body into the description as if it were words. */
    var DESCRIPTION_DROP_TAGS = {
        SCRIPT: 1, STYLE: 1, IFRAME: 1, OBJECT: 1, EMBED: 1, NOSCRIPT: 1, TEMPLATE: 1
    };

    /* products.csv holds whatever the store wrote, so the modal renders it through an
       allowlist rather than trusting it: unknown elements are unwrapped to their words, and
       every attribute goes except a plain http(s) href. Parsed in a detached document, which
       runs no scripts and fetches nothing. */
    function sanitizeDescriptionHtml(raw) {
        var doc = document.implementation.createHTMLDocument('');
        doc.body.innerHTML = String(raw == null ? '' : raw);

        (function walk(parent) {
            var child = parent.firstChild;
            while (child) {
                var next = child.nextSibling;
                if (child.nodeType === 1) {
                    if (DESCRIPTION_DROP_TAGS[child.tagName]) {
                        parent.removeChild(child);
                        child = next;
                        continue;
                    }
                    if (!DESCRIPTION_TAGS[child.tagName]) {
                        while (child.firstChild) {
                            parent.insertBefore(child.firstChild, child);
                        }
                        parent.removeChild(child);
                        child = next;
                        continue;
                    }
                    Array.prototype.slice.call(child.attributes).forEach(function (attr) {
                        var isSafeHref =
                            child.tagName === 'A' &&
                            attr.name.toLowerCase() === 'href' &&
                            /^https?:\/\//i.test(String(attr.value).trim());
                        if (!isSafeHref) {
                            child.removeAttribute(attr.name);
                        }
                    });
                    if (child.tagName === 'A' && child.getAttribute('href')) {
                        child.setAttribute('target', '_blank');
                        child.setAttribute('rel', 'noopener noreferrer');
                    }
                    walk(child);
                } else if (child.nodeType !== 3) {
                    parent.removeChild(child);
                }
                child = next;
            }
        })(doc.body);

        return doc.body.innerHTML;
    }

    FC.util.escapeHtml = escapeHtml;
    FC.util.formatHeader = formatHeader;
    FC.util.setSaving = setSaving;
    FC.util.sanitizeDescriptionHtml = sanitizeDescriptionHtml;
})(window);
