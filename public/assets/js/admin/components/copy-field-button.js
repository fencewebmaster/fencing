/**
 * FC Admin — icon button that copies a readonly field's value to the
 * clipboard, with a brief checkmark feedback state. Extracted from
 * near-identical buildFieldCopyButton/showCopyFeedback/copyFieldToClipboard
 * trios duplicated in gallery.js, settings.js, and products/store-products.js.
 * Button markup/icon are identical across all three; what genuinely differs
 * per caller (button CSS class, data attribute name, copied-state styling,
 * and how the "copied" notification is surfaced) is configuration here.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.components = FC.components || {};

    class CopyFieldButton {
        /**
         * @param {Object} [options]
         * @param {string} [options.buttonClass] base CSS class for the button
         * @param {string} [options.copiedButtonClass] class added while showing feedback
         * @param {string} [options.dataAttr] data-* attribute name pointing at the field id
         * @param {string} [options.iconIdleClass] icon className while idle
         * @param {string} [options.iconCopiedClass] icon className while showing feedback
         * @param {number} [options.feedbackMs] how long the copied state is shown
         * @param {function(string): void} [options.onCopied] called with the copied
         *   text after a successful copy (non-blank only) — use for toast notifications
         * @param {function(): void} [options.onError] called when the copy attempt
         *   fails entirely (clipboard API rejects and execCommand fallback fails
         *   too) — defaults to a bare FcAdminToast.error call
         */
        constructor(options) {
            options = options || {};
            this.buttonClass = options.buttonClass || 'fc-sp-field-copy';
            this.copiedButtonClass = options.copiedButtonClass || (this.buttonClass + '--copied');
            this.dataAttr = options.dataAttr || 'data-fc-copy-for';
            this.iconIdleClass = options.iconIdleClass || 'fa-regular fa-copy text-sm';
            this.iconCopiedClass = options.iconCopiedClass || 'fa-solid fa-check text-sm text-emerald-600';
            this.feedbackMs = options.feedbackMs || 1500;
            this.onCopied = typeof options.onCopied === 'function' ? options.onCopied : null;
            this.onError = typeof options.onError === 'function' ? options.onError : function () {
                var T = window.FcAdminToast;
                if (T) {
                    T.error('Could not copy to clipboard');
                }
            };
        }

        /**
         * @param {string} fieldId
         * @param {string} label used for the aria-label
         * @param {Object} [markupOptions]
         * @param {boolean} [markupOptions.compact] adds a `<buttonClass>--compact` modifier
         * @returns {string} button HTML
         */
        markup(fieldId, label, markupOptions) {
            markupOptions = markupOptions || {};
            var escapeHtml = FC.util.escapeHtml;
            var extraClass = markupOptions.compact ? ' ' + this.buttonClass + '--compact' : '';
            return (
                '<button type="button" class="' + this.buttonClass + extraClass + '" ' + this.dataAttr + '="' +
                escapeHtml(fieldId) +
                '" aria-label="Copy ' +
                escapeHtml(label) +
                '" title="Copy to clipboard">' +
                '<i class="fa-regular fa-copy" aria-hidden="true"></i></button>'
            );
        }

        _showFeedback(btn) {
            if (!btn) {
                return;
            }
            var icon = btn.querySelector('i');
            if (!icon) {
                return;
            }
            var self = this;
            icon.className = this.iconCopiedClass;
            btn.classList.add(this.copiedButtonClass);
            window.setTimeout(function () {
                icon.className = self.iconIdleClass;
                btn.classList.remove(self.copiedButtonClass);
            }, this.feedbackMs);
        }

        /**
         * Copies `control`'s value to the clipboard and shows feedback on `btn`.
         * @param {HTMLInputElement|HTMLTextAreaElement} control
         * @param {Element} btn
         */
        copy(control, btn) {
            if (!control) {
                return;
            }
            var self = this;
            var text = String(control.value != null ? control.value : '');

            function onCopied() {
                self._showFeedback(btn);
                if (text.trim() && self.onCopied) {
                    self.onCopied(text);
                }
            }

            function fallbackCopy() {
                try {
                    control.focus();
                    control.select();
                    if (typeof control.setSelectionRange === 'function') {
                        control.setSelectionRange(0, text.length);
                    }
                    if (document.execCommand('copy')) {
                        onCopied();
                        return;
                    }
                } catch (err) {
                    /* fall through */
                }
                self.onError();
            }

            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(text).then(onCopied).catch(fallbackCopy);
                return;
            }

            fallbackCopy();
        }
    }

    FC.components.CopyFieldButton = CopyFieldButton;
})(window);
