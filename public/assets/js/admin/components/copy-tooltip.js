/**
 * FC Admin — floating "Copied!" tooltip shown near a clicked control.
 * Extracted from the near-identical toast-creation/positioning/auto-dismiss
 * logic duplicated between entries/detail-copy.js and
 * entries/planner-copy.js. The two files differ in which DOM element
 * gets an "is-copied" class and whether an icon gets swapped, so that part
 * stays with each caller via the optional onReset hook — this class only
 * owns the floating bubble's lifecycle.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.components = FC.components || {};

    var TOAST_GAP_DEFAULT = 10;
    var COPIED_MS_DEFAULT = 1800;

    class CopyTooltip {
        /**
         * @param {Object} [options]
         * @param {string} [options.toastClassName] CSS class for the tooltip element
         * @param {number} [options.copiedMs] auto-reset delay in ms
         * @param {number} [options.toastGap] gap in px between trigger and tooltip
         * @param {function(): void} [options.onReset] called whenever the tooltip
         *   resets (auto-timeout, scroll, resize, or an explicit reset() call) —
         *   use this to clear a caller-specific "is-copied" class
         */
        constructor(options) {
            options = options || {};
            this.toastClassName = options.toastClassName || 'fc-entries-planner-copy-toast';
            this.copiedMs = options.copiedMs || COPIED_MS_DEFAULT;
            this.toastGap = options.toastGap || TOAST_GAP_DEFAULT;
            this.onReset = typeof options.onReset === 'function' ? options.onReset : null;
            this.toastEl = null;
            this.hideTimer = null;

            var self = this;
            window.addEventListener(
                'scroll',
                function () {
                    if (self.toastEl && self.toastEl.classList.contains('is-visible')) {
                        self.reset();
                    }
                },
                true
            );
            window.addEventListener('resize', function () {
                if (self.toastEl && self.toastEl.classList.contains('is-visible')) {
                    self.reset();
                }
            });
        }

        _ensureToast() {
            if (this.toastEl) {
                return this.toastEl;
            }
            var el = document.createElement('div');
            el.className = this.toastClassName;
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            el.textContent = 'Copied!';
            document.body.appendChild(el);
            this.toastEl = el;
            return el;
        }

        _position(button) {
            var toast = this._ensureToast();
            var rect = button.getBoundingClientRect();
            var toastRect = toast.getBoundingClientRect();
            var toastHeight = toastRect.height || 28;
            var placeBelow = rect.top < toastHeight + this.toastGap + 8;
            var centerX = rect.left + rect.width / 2;

            toast.style.left = centerX + 'px';
            toast.classList.toggle('is-below', placeBelow);

            if (placeBelow) {
                toast.style.top = rect.bottom + this.toastGap + 'px';
            } else {
                toast.style.top = rect.top - this.toastGap + 'px';
            }
        }

        /** Clears the timer, hides the toast, and invokes onReset. */
        reset() {
            if (this.hideTimer) {
                window.clearTimeout(this.hideTimer);
                this.hideTimer = null;
            }
            if (this.toastEl) {
                this.toastEl.classList.remove('is-visible', 'is-below');
            }
            if (this.onReset) {
                this.onReset();
            }
        }

        /**
         * Positions and shows the tooltip near `button`, then arms an
         * auto-reset timer. Callers apply their own "is-copied" state to the
         * DOM before calling this (after calling reset() to clear prior state).
         * @param {Element} button
         * @param {string} [message]
         */
        display(button, message) {
            var toast = this._ensureToast();
            toast.textContent = message || 'Copied!';
            toast.classList.add('is-visible');
            this._position(button);

            var self = this;
            this.hideTimer = window.setTimeout(function () {
                self.reset();
            }, this.copiedMs);
        }
    }

    FC.components.CopyTooltip = CopyTooltip;
})(window);
