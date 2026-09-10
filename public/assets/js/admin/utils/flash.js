/**
 * FC Admin — sessionStorage "flash message" shown once after a reload.
 * Extracted from 6 files (group-permissions.js, settings.js,
 * products/store-products.js, products/system-products.js,
 * entries/bulk.js, core/cache-purge.js) that each carried a
 * byte-identical set/consume pair, and 5 of those 6 also carried an
 * identical showHeaderNotice renderer differing only in the notice
 * element's selector and (for 3 of them) a fallback root lookup.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.util = FC.util || {};

    class FlashMessage {
        /**
         * @param {Object} options
         * @param {string} options.storageKey flash scope inside the shared
         *   'fc-admin-session' envelope. Kept as each caller's pre-consolidation
         *   sessionStorage key name: FC.store.session uses the same string as
         *   its legacy fallback, so an in-flight flash set before a deploy
         *   still gets consumed after it.
         * @param {string} [options.noticeSelector] selector for the notice mount,
         *   relative to the root passed to renderInto — omit if this instance is
         *   only used for set()/consume(), not renderInto()
         * @param {function(): (Element|null)} [options.defaultRoot] resolves a
         *   root element when renderInto() is called without one
         */
        constructor(options) {
            options = options || {};
            this.storageKey = options.storageKey;
            this.noticeSelector = options.noticeSelector;
            this.defaultRoot = typeof options.defaultRoot === 'function' ? options.defaultRoot : null;
        }

        set(message, type) {
            var store = global.FC && global.FC.store;
            if (!store) {
                return;
            }
            store.session.set(this.storageKey, {
                message: String(message || ''),
                type: type === 'error' ? 'error' : 'success'
            });
        }

        /** @returns {{message:string,type:string}|null} */
        consume() {
            var store = global.FC && global.FC.store;
            if (!store) {
                return null;
            }
            var data = store.session.consume(this.storageKey);
            if (!data || !data.message) {
                return null;
            }
            return data;
        }

        /**
         * @param {Element|null} root
         * @param {{message:string,type:string}|null} flash
         */
        renderInto(root, flash) {
            if (!root && this.defaultRoot) {
                root = this.defaultRoot();
            }
            var mount = root ? root.querySelector(this.noticeSelector) : null;
            if (!mount || !flash || !flash.message) {
                return;
            }

            var type = flash.type === 'error' ? 'error' : 'success';
            mount.hidden = false;
            mount.className =
                'fc-entries-page__notice fc-entries-page__notice--' + type + ' is-visible';
            mount.setAttribute('role', type === 'error' ? 'alert' : 'status');
            mount.innerHTML =
                '<p class="fc-entries-page__notice-text"></p>' +
                '<button type="button" class="fc-entries-page__notice-dismiss" aria-label="Dismiss notice">' +
                '<i class="fa-solid fa-xmark" aria-hidden="true"></i>' +
                '</button>';

            var textEl = mount.querySelector('.fc-entries-page__notice-text');
            if (textEl) {
                textEl.textContent = flash.message;
            }

            var dismiss = mount.querySelector('.fc-entries-page__notice-dismiss');
            if (dismiss) {
                dismiss.addEventListener('click', function () {
                    mount.hidden = true;
                    mount.classList.remove('is-visible');
                    mount.innerHTML = '';
                });
            }
        }
    }

    FC.util.FlashMessage = FlashMessage;
})(window);
