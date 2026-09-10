/**
 * FC Admin — single-key client storage.
 *
 * Every admin preference lives under ONE localStorage key ('fc-admin', a
 * versioned JSON envelope) and every one-shot flash message under ONE
 * sessionStorage key ('fc-admin-session'), replacing the scattered
 * fc-admin-appearance / fc-admin-sidebar-collapsed / fc-gallery-view-mode /
 * fc-*-flash keys.
 *
 * SCHEMA and FLASH_SCOPES are closed allowlists: set() refuses any path, value
 * or scope outside them, so no call site can stash arbitrary data (tokens, PII)
 * in the browser, and a hand-edited envelope can never push an unexpected value
 * into a DOM sink — get() and session.consume() re-validate on every read and
 * serve the schema default. Membership checks are own-property only: without
 * that, SCHEMA['constructor'] resolves through the prototype chain and a bare
 * truthiness test would accept paths the schema never declared.
 *
 * There is deliberately NO long-lived envelope cache. Reads parse the stored
 * envelope fresh and writes merge into a fresh read inside the flush — a page
 * that cached the envelope at load and wrote it back whole would revert every
 * preference another tab persisted in between (the sidebar toggle in a stale
 * dashboard tab silently undoing a dark-mode choice made elsewhere), which the
 * old per-key setItem calls could never do. The only in-memory state is the
 * `pending` overlay of this tab's own not-yet-flushed writes; conflicts shrink
 * to same-field last-writer-wins within one microtask — the per-key semantics
 * this module replaced.
 *
 * Nothing here sweeps by prefix: the public planner shares this origin and owns
 * fc-step2-go-snap-* keys, so admin-side clearing must stay on the explicit
 * lists below or it destroys a customer's in-progress Step 2 snapshots.
 *
 * Load order: immediately after core/namespace.js and before
 * core/admin-appearance.js (the first consumer) on BOTH the layout and the
 * login page — the eager migrate() at the bottom must fold the legacy keys into
 * the envelope before anything reads a preference.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};

    var LS_KEY = 'fc-admin';
    var SS_KEY = 'fc-admin-session';
    var VERSION = 1;

    // A full preference envelope is ~100 bytes; anything approaching this cap is
    // a bug (or someone bypassing the schema), not data worth keeping.
    var MAX_BYTES = 8192;

    // Pre-consolidation localStorage keys. Folded in by migrate(), removed there
    // and in clearAll(). Transitional (deployed 2026-09-10): remove this list,
    // the raw-key fallback in session.consume(), and storage-boot.php's
    // else-branch together, one release after every admin browser has loaded
    // this file once.
    var LEGACY_LOCAL = [
        'fc-admin-appearance',
        'fc-admin-sidebar-collapsed',
        'fc-gallery-view-mode',
        // Dead key: the dashboard date filter moved to the URL; nothing writes
        // this any more, it was only ever cleaned up.
        'fc-dashboard-date-filter'
    ];

    // Standalone localStorage keys cleared at sign-out alongside the envelope,
    // named explicitly by the product owner. Deliberately a separate list from
    // LEGACY_LOCAL: those are migration leftovers with a removal date, these are
    // permanent.
    //
    // The first four do not exist yet. They are listed anyway because
    // removeItem on an absent key is free, and because it closes the gap where a
    // feature ships writing a stray top-level key instead of a SCHEMA path and
    // nothing clears it. When those features are built the RIGHT home is a
    // SCHEMA entry inside the envelope — which clearAll() already wipes — and
    // the line here becomes redundant belt-and-braces rather than the mechanism.
    //
    // fc-lookup-layout is the exception worth knowing about: it is NOT admin
    // state. It belongs to the public, session-less product lookup page
    // (public/assets/js/frontend/lookup.js, LAYOUT_KEY) and holds a grid/list
    // preference. Clearing it from here is a deliberate cross-module reach,
    // requested knowingly; the cost is one extra click for whoever next opens
    // /lookup in this browser. It is called out rather than quietly folded in
    // because if lookup.js ever changes what that key holds, this line is a
    // hidden coupling that will not announce itself.
    //
    // Never extend this list with a PREFIX or with the planner's keys
    // (fc-step2-go-snap-*, custom_fence-*, cart_items-*) or the frontend
    // Debugbar's (fc-debugbar) — those hold a visitor's in-progress work.
    var EXTRA_CLEAR = [
        'fc-admin-products-nav-open',
        'fc-dashboard-range',
        'fc-dashboard-widgets',
        'fc-entries-page-size',
        'fc-lookup-layout'
    ];

    // Flash scopes double as each caller's pre-consolidation sessionStorage key
    // name — session.consume() reads that raw key as a fallback so an in-flight
    // flash set just before this deploy still shows after it (the guarantee the
    // per-caller keys originally existed for). A new flash caller adds its scope
    // here, same as a new preference adds a SCHEMA line; an unregistered scope
    // is silently refused (warned under FC_DEBUG).
    var FLASH_SCOPES = [
        'fc-cache-purge-flash',
        'fc-entries-bulk-flash',
        'fc-gp-save-flash',
        'fc-store-products-save-flash',
        'fc-system-products-download-flash',
        'fc-settings-save-flash'
    ];

    // The closed allowlist. kind 'enum' validates against values; 'bool' against
    // typeof. get()/set() resolve dotted paths ('ui.appearance') inside the
    // envelope.
    var SCHEMA = {
        'ui.appearance':       { kind: 'enum', values: ['light', 'dark'], def: 'light' },
        'ui.sidebarCollapsed': { kind: 'bool', def: false },
        'ui.galleryViewMode':  { kind: 'enum', values: ['grid', 'list'], def: 'grid' }
    };

    // This tab's validated-but-not-yet-flushed writes (path -> value). Null
    // prototype so an allowlisted-looking path like 'hasOwnProperty' could
    // never collide with Object.prototype even if SCHEMA ever declared one.
    var pending = Object.create(null);
    var flushQueued = false;

    function hasOwn(obj, key) {
        return Object.prototype.hasOwnProperty.call(obj, key);
    }

    // FC_DEBUG is stamped by layouts/main.php only; the login page leaves it
    // undefined, so warnings there need a manual `window.FC_DEBUG = true` from
    // the console — acceptable for a dev-only channel.
    function warn(message) {
        if (global.FC_DEBUG === true && global.console && console.warn) {
            console.warn('[FC.store] ' + message);
        }
    }

    // Accessing window.localStorage itself can throw (privacy modes, storage
    // disabled), not just getItem/setItem — so even the handle lookup is guarded.
    function storageFor(kind) {
        try {
            return kind === 'session' ? global.sessionStorage : global.localStorage;
        } catch (e) {
            return null;
        }
    }

    function readRaw(kind, key) {
        var store = storageFor(kind);
        if (!store) {
            return null;
        }
        try {
            var raw = store.getItem(key);
            if (!raw) {
                return null;
            }
            var data = JSON.parse(raw);
            return data && typeof data === 'object' && !Array.isArray(data) ? data : null;
        } catch (e) {
            return null;
        }
    }

    function writeRaw(kind, key, envelope) {
        var store = storageFor(kind);
        if (!store) {
            return false;
        }
        try {
            var json = JSON.stringify(envelope);
            if (json.length > MAX_BYTES) {
                warn('refusing oversized envelope for ' + key + ' (' + json.length + ' bytes)');
                return false;
            }
            store.setItem(key, json);
            return true;
        } catch (e) {
            warn('write to ' + key + ' failed: ' + e);
            return false;
        }
    }

    function removeKeys(kind, keys) {
        var store = storageFor(kind);
        if (!store) {
            return;
        }
        for (var i = 0; i < keys.length; i++) {
            try {
                store.removeItem(keys[i]);
            } catch (e) {
                /* ignore */
            }
        }
    }

    function removeLegacyLocal() {
        removeKeys('local', LEGACY_LOCAL);
    }

    /**
     * Fresh, shape-guaranteed envelope from disk — parsed on every call, never
     * cached (see the header). Unknown top-level keys and a higher v are kept
     * and carried through every write — the same carry-all contract as
     * ThemeSettings::writeSection(), so a rollback after a future format bump
     * cannot clobber data this build does not understand.
     */
    function loadEnvelope() {
        var data = readRaw('local', LS_KEY);
        if (!data) {
            data = { v: VERSION, ui: {} };
        }
        if (typeof data.v !== 'number' || data.v < VERSION) {
            data.v = VERSION;
        }
        if (!data.ui || typeof data.ui !== 'object' || Array.isArray(data.ui)) {
            data.ui = {};
        }
        return data;
    }

    /**
     * One-time fold of the pre-consolidation keys into the envelope. The legacy
     * keys are only deleted once the folded envelope has actually persisted —
     * deleting them after a failed write (quota, disabled storage) would destroy
     * the only surviving copy of the preferences. When the envelope already
     * exists the legacy keys are cleaned unconditionally: a still-open
     * pre-deploy tab can re-write one after this browser has migrated.
     */
    function migrate() {
        if (readRaw('local', LS_KEY)) {
            removeLegacyLocal();
            return;
        }

        var data = { v: VERSION, ui: {} };
        var store = storageFor('local');
        if (store) {
            try {
                if (store.getItem('fc-admin-appearance') === 'dark') {
                    data.ui.appearance = 'dark';
                }
                if (store.getItem('fc-admin-sidebar-collapsed') === '1') {
                    data.ui.sidebarCollapsed = true;
                }
                if (store.getItem('fc-gallery-view-mode') === 'list') {
                    data.ui.galleryViewMode = 'list';
                }
            } catch (e) {
                /* defaults win */
            }
        }

        if (writeRaw('local', LS_KEY, data)) {
            removeLegacyLocal();
        }
    }

    /** @returns {{value:*}|null} normalized value, or null when out of schema */
    function validate(path, value) {
        if (!hasOwn(SCHEMA, path)) {
            return null;
        }
        var spec = SCHEMA[path];
        if (spec.kind === 'enum') {
            return spec.values.indexOf(value) !== -1 ? { value: value } : null;
        }
        return typeof value === 'boolean' ? { value: value } : null;
    }

    /**
     * Merge this tab's pending writes into a FRESH read of the envelope and
     * persist. Merging (rather than writing a cached whole) is what keeps a
     * concurrent tab's writes to other fields alive. On a failed write the
     * overlay is kept: get() keeps serving this tab's own choices in memory —
     * the same session-lifetime continuity the old code's live-DOM fallback
     * gave when storage was unavailable — and the next set() retries.
     */
    function flush() {
        var env = loadEnvelope();
        var paths = Object.keys(pending);
        for (var p = 0; p < paths.length; p++) {
            var parts = paths[p].split('.');
            var node = env;
            for (var i = 0; i < parts.length - 1; i++) {
                if (!node[parts[i]] || typeof node[parts[i]] !== 'object' || Array.isArray(node[parts[i]])) {
                    node[parts[i]] = {};
                }
                node = node[parts[i]];
            }
            node[parts[parts.length - 1]] = pending[paths[p]];
        }
        if (writeRaw('local', LS_KEY, env)) {
            pending = Object.create(null);
        }
    }

    // Microtask, not a timer: it still runs before any navigation started by the
    // same click, and it collapses the burst of set() calls a window resize
    // produces into a single read + merge + setItem.
    function scheduleFlush() {
        if (flushQueued) {
            return;
        }
        flushQueued = true;
        Promise.resolve().then(function () {
            flushQueued = false;
            flush();
        });
    }

    /**
     * Schema-validated read. Never throws; a known path always resolves to a
     * valid value (this tab's pending write, then the stored envelope, then the
     * schema default). An unknown path is a programming error: it warns under
     * FC_DEBUG and returns undefined.
     */
    function get(path) {
        if (!hasOwn(SCHEMA, path)) {
            warn('get: unknown path "' + path + '"');
            return undefined;
        }
        if (hasOwn(pending, path)) {
            return pending[path];
        }
        var node = loadEnvelope();
        var parts = path.split('.');
        for (var i = 0; i < parts.length && node !== null && node !== undefined; i++) {
            node = node[parts[i]];
        }
        var ok = validate(path, node);
        return ok ? ok.value : SCHEMA[path].def;
    }

    /** Schema-validated write. Returns false (and warns under FC_DEBUG) when refused. */
    function set(path, value) {
        var ok = validate(path, value);
        if (!ok) {
            warn('set: rejected "' + String(value) + '" for path "' + path + '"');
            return false;
        }
        pending[path] = ok.value;
        scheduleFlush();
        return true;
    }

    /**
     * Wipe admin-owned client storage. Two strengths, because the two triggers
     * mean different things:
     *
     *   clearAll()                      login page rendered while signed out —
     *                                   which includes a session that merely
     *                                   expired on a shared machine. Keeps
     *                                   ui.appearance, a device preference the
     *                                   login page's own theme toggle writes:
     *                                   dropping it here would make that toggle
     *                                   un-persistable, since every reload of
     *                                   this page runs this path.
     *   clearAll({everything: true})    deliberate logout (?logged_out=1). The
     *                                   whole envelope goes, theme included.
     *
     * Both strengths clear the envelope, the legacy names, every pending flash
     * and every EXTRA_CLEAR key — ui.appearance is the only thing that ever
     * survives, and only on the weaker path.
     *
     * Always an explicit list, never a prefix sweep: the public planner shares
     * this origin and owns fc-step2-go-snap-*, custom_fence-*, cart_items-*,
     * and the frontend Debugbar owns fc-debugbar — clearing any of those from
     * here would destroy a visitor's in-progress work.
     *
     * The write is synchronous (no scheduleFlush) so the wipe is on disk even
     * if the visitor navigates away immediately.
     */
    function clearAll(options) {
        var everything = !!(options && options.everything);
        var appearance = everything ? null : get('ui.appearance');
        pending = Object.create(null);
        var env = { v: VERSION, ui: {} };
        if (appearance === 'dark') {
            env.ui.appearance = 'dark';
        }
        writeRaw('local', LS_KEY, env);
        removeLegacyLocal();
        removeKeys('local', EXTRA_CLEAR);

        removeKeys('session', [SS_KEY].concat(FLASH_SCOPES));
    }

    // Same carry-all posture as loadEnvelope(): only the flash section is
    // shape-repaired; unknown sibling keys ride along untouched.
    function readSessionEnvelope() {
        var data = readRaw('session', SS_KEY);
        if (!data) {
            return { v: VERSION, flash: {} };
        }
        if (!data.flash || typeof data.flash !== 'object' || Array.isArray(data.flash)) {
            data.flash = {};
        }
        return data;
    }

    /** @returns {{message:string,type:string}|null} the closed flash shape, nothing extra */
    function normalizeFlash(value) {
        if (!value || typeof value !== 'object' || typeof value.message !== 'string') {
            return null;
        }
        return {
            message: value.message,
            type: value.type === 'error' ? 'error' : 'success'
        };
    }

    var session = {
        /**
         * Store a one-shot flash under a known scope. The value must be the
         * FlashMessage shape ({message, type}); anything else is refused so the
         * session envelope stays as closed as the preference schema.
         */
        set: function (scope, value) {
            if (FLASH_SCOPES.indexOf(scope) === -1) {
                warn('session.set: unknown flash scope "' + scope + '"');
                return false;
            }
            var flash = normalizeFlash(value);
            if (!flash) {
                warn('session.set: rejected non-flash value for "' + scope + '"');
                return false;
            }
            var env = readSessionEnvelope();
            env.flash[scope] = flash;
            return writeRaw('session', SS_KEY, env);
        },

        /**
         * Read-and-remove a flash, always returned re-normalized — the envelope
         * is user-editable, so it is closed on the way out too, never just on
         * the way in. Falls back to the scope's pre-consolidation raw
         * sessionStorage key so an in-flight flash set before this deploy is
         * still consumed after it.
         */
        consume: function (scope) {
            var env = readSessionEnvelope();
            var flash = hasOwn(env.flash, scope) ? normalizeFlash(env.flash[scope]) : null;
            if (flash) {
                delete env.flash[scope];
                writeRaw('session', SS_KEY, env);
                return flash;
            }

            var store = storageFor('session');
            if (!store) {
                return null;
            }
            try {
                var raw = store.getItem(scope);
                if (!raw) {
                    return null;
                }
                store.removeItem(scope);
                return normalizeFlash(JSON.parse(raw));
            } catch (e) {
                return null;
            }
        }
    };

    FC.store = {
        get: get,
        set: set,
        clearAll: clearAll,
        session: session
    };

    // Eager, not lazy: login.js calls clearAll() and admin-appearance.js reads
    // the theme in this same document — the legacy keys must be folded in before
    // either runs.
    try {
        migrate();
    } catch (e) {
        /* storage unavailable — every read serves schema defaults */
    }
})(window);
