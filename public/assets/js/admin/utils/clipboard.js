/**
 * FC Admin — clipboard write with an execCommand fallback for non-secure contexts.
 * Extracted from the byte-identical `copyText()` in entries/detail-copy.js
 * and entries/planner-copy.js.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.util = FC.util || {};

    /**
     * @param {string} text
     * @returns {Promise<void>}
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                if (document.execCommand('copy')) {
                    resolve();
                } else {
                    reject(new Error('Copy failed'));
                }
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(textarea);
            }
        });
    }

    FC.util.copyToClipboard = copyToClipboard;
})(window);
