/**
 * FC Admin — shared toast(kind, message, ...) wrappers around FcAdminToast.
 *
 * Two genuinely duplicated call shapes were found across the admin JS:
 *   - "dismiss-first": settings.js, gallery.js, core/media-picker.js,
 *     fence-styles/fence-styles.js all defaulted/took a toast id, dismissed
 *     any existing toast with that id, then showed a bare success/error toast.
 *     Reproduced here when the 3rd argument is a string (or omitted).
 *   - "id+duration passthrough": products/store-products.js and
 *     products/system-products.js passed {id, duration} straight through to
 *     FcAdminToast.success/.error without an explicit dismiss (FcAdminToast's
 *     own show() already replaces a toast with the same id).
 *     Reproduced here when the 3rd argument is an options object.
 *
 * Other local toast() wrappers (entries/bulk.js, core/cache-purge.js,
 * group-permissions.js) each have a genuinely distinct shape used in only
 * one file and are intentionally left alone — see the audit.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.util = FC.util || {};

    var PRODUCTS_TOAST_ID = 'fc-products-catalogue';

    /**
     * @param {'saving'|'loading'|'ok'|'success'|'error'} kind
     * @param {string} message
     * @param {string|{id?:string,duration?:number}} [opts] toast id string
     *   (dismiss-first shape) or an {id, duration} object (passthrough shape)
     */
    function toast(kind, message, opts) {
        var T = global.FcAdminToast;
        if (!T) {
            return;
        }

        var dismissFirst = opts === undefined || typeof opts === 'string';
        var id = dismissFirst ? opts : (opts && opts.id);
        var duration = dismissFirst ? undefined : (opts && opts.duration);
        var isLoading = kind === 'saving' || kind === 'loading';
        var isSuccess = kind === 'ok' || kind === 'success';

        if (isLoading) {
            if (typeof T.loading === 'function') {
                T.loading(message, id);
            }
            return;
        }

        if (dismissFirst) {
            if (id) {
                T.dismiss(id);
            }
            if (isSuccess) {
                T.success(message);
            } else if (kind === 'error') {
                T.error(message);
            }
            return;
        }

        if (isSuccess && typeof T.success === 'function') {
            T.success(message, { id: id, duration: duration });
        } else if (kind === 'error' && typeof T.error === 'function') {
            T.error(message, { id: id, duration: duration });
        }
    }

    /**
     * Pre-configured bridge for the store-products/system-products pair,
     * which both hardcoded the same toast id and duration-per-kind.
     * @param {'saving'|'success'|'error'} kind
     * @param {string} message
     */
    function toastProductsCatalogue(kind, message) {
        toast(kind, message, {
            id: PRODUCTS_TOAST_ID,
            duration: kind === 'error' ? 5000 : 4500
        });
    }

    FC.util.toast = toast;
    FC.util.toastProductsCatalogue = toastProductsCatalogue;
})(window);
