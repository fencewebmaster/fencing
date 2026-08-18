/**
 * FC Admin — root namespace bootstrap.
 * Must be the first admin script loaded on every page. Creates the shared
 * `window.FC` object that the rest of the admin JS attaches classes/utilities
 * to, replacing the old pattern of one bare global per file (FcAdminModal,
 * FcAdminToast, FcAdminGallery, ...).
 */
(function (global) {
    'use strict';

    global.FC = global.FC || {};
    global.FC.util = global.FC.util || {};
})(window);
