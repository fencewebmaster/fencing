/**
 * FC Debugbar — core: buffers, redaction, public API and the collectors.
 *
 * Loaded by partials/footer.php ONLY when Settings -> Console -> Debug Mode (and Show
 * Debugbar) are on — when off this file is never emitted, so the production page carries
 * zero Debugbar bytes, DOM or globals.
 *
 * Everything here observes from OUTSIDE: calculator files are not modified. Collectors
 * install at DOMContentLoaded (after every deferred script has executed, before jQuery
 * ready handlers run — jQuery 3 fires ready asynchronously) by wrapping the app's own
 * globals (calculate_fences, FENCE, FENCES.cartItems, HELPER.call_fence_func, the
 * ProjectPlan forks). Every wrapper calls the original exactly and swallows its own
 * failures, because HELPER.call_fence_func silently re-runs base methods on ANY throw —
 * a throwing observer would invisibly change dispatch.
 *
 * App code never needs this file: `window.debugbar` only exists in debug mode, so any
 * future caller must typeof-guard, same as every other optional global in this codebase.
 */
(function (window) {
    'use strict';

    var doc = window.document;

    // ------------------------------------------------------------------
    // Server island + config
    // ------------------------------------------------------------------

    function readServerIsland() {
        try {
            var el = doc.getElementById('fc-debugbar-server');
            if (!el) { return {}; }
            var parsed = JSON.parse(el.textContent || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    var server = readServerIsland();
    var cfg = server.config || {};

    var MAX_ENTRIES = (function () {
        var n = parseInt(cfg.maxEntries, 10);
        if (isNaN(n)) { n = 200; }
        return Math.max(50, Math.min(2000, n));
    })();

    var REDACT_KEYS = (function () {
        var keys = cfg.redactKeys;
        if (!keys || !keys.length) {
            keys = ['password', 'secret', 'token', 'nonce', 'key', 'auth', 'cookie', 'credential', 'db_password', 'database_password'];
        }
        var out = [];
        for (var i = 0; i < keys.length; i++) {
            var k = String(keys[i] || '').toLowerCase().trim();
            if (k) { out.push(k); }
        }
        return out;
    })();

    // ------------------------------------------------------------------
    // Small utilities
    // ------------------------------------------------------------------

    function now() {
        return window.performance && performance.now ? performance.now() : Date.now();
    }

    function keyIsSensitive(key) {
        var lower = String(key).toLowerCase();
        for (var i = 0; i < REDACT_KEYS.length; i++) {
            if (lower.indexOf(REDACT_KEYS[i]) !== -1) { return true; }
        }
        return false;
    }

    /**
     * Safe snapshot: redacts by key, truncates long strings, caps arrays ("Array(42),
     * showing 10") and depth — never deep-clones large app objects wholesale.
     */
    function snapshot(value, depth) {
        depth = depth || 0;
        try {
            if (value === null || value === undefined) { return value; }
            var type = typeof value;
            if (type === 'string') {
                return value.length > 500 ? value.slice(0, 500) + '…(' + value.length + ' chars)' : value;
            }
            if (type === 'number' || type === 'boolean') { return value; }
            if (type === 'function') { return '[function ' + (value.name || 'anonymous') + ']'; }
            if (depth >= 4) { return Array.isArray(value) ? 'Array(' + value.length + ')' : '[object]'; }
            if (Array.isArray(value)) {
                var cap = 10;
                var arr = [];
                for (var i = 0; i < Math.min(value.length, cap); i++) {
                    arr.push(snapshot(value[i], depth + 1));
                }
                if (value.length > cap) {
                    arr.push('… ' + (value.length - cap) + ' more');
                }
                return arr;
            }
            if (type === 'object') {
                if (value.jquery) { return '[jQuery(' + value.length + ')]'; }
                if (value.nodeType) { return '[' + (value.nodeName || 'node') + ']'; }
                var out = {};
                var count = 0;
                for (var k in value) {
                    if (!Object.prototype.hasOwnProperty.call(value, k)) { continue; }
                    if (count >= 40) { out['…'] = 'more keys'; break; }
                    out[k] = keyIsSensitive(k) ? '[redacted]' : snapshot(value[k], depth + 1);
                    count++;
                }
                return out;
            }
            return String(value);
        } catch (e) {
            return '[unserializable]';
        }
    }

    function RingBuffer(cap) {
        this.cap = cap;
        this.items = [];
        this.dropped = 0;
        this.total = 0;
    }
    RingBuffer.prototype.push = function (item) {
        this.total++;
        this.items.push(item);
        if (this.items.length > this.cap) {
            this.items.shift();
            this.dropped++;
        }
    };
    RingBuffer.prototype.clear = function () {
        this.items = [];
        this.dropped = 0;
        this.total = 0;
    };

    // ------------------------------------------------------------------
    // State
    // ------------------------------------------------------------------

    var prefs = (function () {
        try {
            var raw = window.localStorage.getItem('fc-debugbar');
            var p = raw ? JSON.parse(raw) : {};
            return p && typeof p === 'object' ? p : {};
        } catch (e) {
            return {};
        }
    })();

    function savePrefs() {
        try { window.localStorage.setItem('fc-debugbar', JSON.stringify(prefs)); } catch (e) {}
    }

    var state = {
        paused: false,
        verbose: typeof prefs.verbose === 'boolean' ? prefs.verbose : !!cfg.verbose,
        server: server,
        trace: new RingBuffer(MAX_ENTRIES),
        logs: new RingBuffer(MAX_ENTRIES),
        requests: new RingBuffer(MAX_ENTRIES),
        prevRun: [],
        currentRun: [],
        lastTraceAt: 0,
        marks: {},
        lastCalc: null,        // { ms, at, tab, item, summary }
        lastValidation: null,  // Inputs panel snapshot
        lastProcess: null,     // { tabIndex, lines, ms }
        counters: { calcCalls: 0, calcRuns: 0, renders: 0, swallowed: 0 },
        warnCount: 0,
        errorCount: 0
    };

    // Monotonic id stamped on every captured entry. Timestamps are not unique enough to
    // identify a row: a calculation burst records many entries inside the same millisecond,
    // and the UI needs a stable per-entry key to reopen what the reader had expanded.
    var entrySeq = 0;

    var listeners = [];
    var emitQueued = false;

    function emitChange() {
        if (emitQueued) { return; }
        emitQueued = true;
        window.setTimeout(function () {
            emitQueued = false;
            for (var i = 0; i < listeners.length; i++) {
                try { listeners[i](); } catch (e) {}
            }
        }, 250);
    }

    // A "run" groups the burst of calc/render work one user action triggers. A fresh
    // btnCalculate always starts one; otherwise a quiet gap does (modal edits, auto-calc).
    function rotateRunIfIdle(force) {
        var t = now();
        if (force || (state.currentRun.length && t - state.lastTraceAt > 400)) {
            if (state.currentRun.length) {
                state.prevRun = state.currentRun;
                state.currentRun = [];
            }
            if (force) { state.counters.calcRuns++; }
        }
        state.lastTraceAt = t;
    }

    function traceStep(entry) {
        if (state.paused) { return; }
        rotateRunIfIdle(false);
        entry.at = Date.now();
        entry.seq = ++entrySeq;
        state.trace.push(entry);
        state.currentRun.push(entry);
        emitChange();
    }

    function addLog(level, message, context) {
        if (state.paused) { return; }
        if (level === 'warn') { state.warnCount++; }
        if (level === 'error') { state.errorCount++; }
        state.logs.push({
            at: Date.now(),
            seq: ++entrySeq,
            level: level,
            message: String(message).slice(0, 2000),
            context: context === undefined ? null : snapshot(context)
        });
        emitChange();
    }

    // ------------------------------------------------------------------
    // Wrapping helper — never installs over a non-function, never double-wraps.
    // ------------------------------------------------------------------

    function wrapMethod(owner, key, makeWrapper) {
        try {
            if (!owner || typeof owner[key] !== 'function' || owner[key].__fcDebugWrapped) { return false; }
            var original = owner[key];
            var wrapped = makeWrapper(original);
            wrapped.__fcDebugWrapped = true;
            owner[key] = wrapped;
            return true;
        } catch (e) {
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Collectors
    // ------------------------------------------------------------------

    var calcDepth = 0;

    /**
     * Summarizing every result as it was captured cost ~20% of a calculate_fences() call,
     * which blew the overhead budget: calc runs many times per user action and almost none
     * of those entries are ever looked at. The raw result is held by reference instead
     * (calc returns a fresh object per call, so nothing is cloned) and condensed on demand
     * when a panel or the export actually reads it — then memoized.
     */
    function summarizeCalcEntry(holder) {
        if (!holder) { return null; }
        if (holder._summary !== undefined) { return holder._summary; }
        holder._summary = summarizeCalcResult(holder.result);
        return holder._summary;
    }

    function summarizeCalcResult(calc) {
        if (!calc || typeof calc !== 'object') { return null; }
        try {
            var sel = calc.selected_values || {};
            return {
                fence_size: calc.fence_size,
                full_panel: snapshot(calc.full_panel, 3),
                even_panel: snapshot(calc.even_panel, 3),
                long_panel: snapshot(calc.long_panel, 3),
                short_panel: snapshot(calc.short_panel, 3),
                offcut_panel: snapshot(calc.offcut_panel, 3),
                offcut_gate_panel: snapshot(calc.offcut_gate_panel, 3),
                gate: snapshot(calc.gate, 3),
                gate_hinge_panel: snapshot(calc.gate_hinge_panel, 3),
                left_raked: snapshot(calc.left_raked, 3),
                right_raked: snapshot(calc.right_raked, 3),
                panel_option: sel.panel_option,
                spacing: sel.spacing,
                spacing_exact: sel.spacing_exact,
                message: sel.message,
                clamp: snapshot(sel.clamp, 3)
            };
        } catch (e) {
            return null;
        }
    }

    function installCalcCollector() {
        wrapMethod(window, 'calculate_fences', function (original) {
            return function (data) {
                var t0 = now();
                calcDepth++;
                try {
                    var result = original.apply(this, arguments);
                    var ms = now() - t0;
                    try {
                        state.counters.calcCalls++;
                        if (calcDepth === 1) {
                            state.lastCalc = {
                                ms: ms,
                                at: Date.now(),
                                tab: data && data.tab !== undefined ? data.tab : '(selected)',
                                item: data && data.item !== undefined ? data.item : '(selected)',
                                result: result
                            };
                        }
                        if (calcDepth === 1 || state.verbose) {
                            traceStep({
                                name: 'calculate_fences',
                                depth: calcDepth - 1,
                                ms: Math.round(ms * 100) / 100,
                                input: data ? { tab: data.tab, item: data.item, clampEnforce: data.clampEnforce } : null,
                                // Condensed lazily by summarizeCalcEntry() when read.
                                result: calcDepth === 1 ? result : null,
                                output: calcDepth === 1 ? undefined : '(nested)'
                            });
                        }
                    } catch (e) {}
                    return result;
                } finally {
                    calcDepth--;
                }
            };
        });

        // Per-stage taps (verbose only): each FenceCalculator stage is a plain boundary.
        // _resolveRakedSide is positional (custom_fence, info, sideKey, ...), the rest take
        // one input object — captured accordingly.
        try {
            if (typeof FenceCalculator === 'function' && FenceCalculator.prototype) {
                var stages = ['_detectStyle', '_resolveFenceHeights', '_resolveOverallWidth', '_resolvePanelWidth',
                    '_resolveGateInputs', '_applyPostRemovalAdjustment', '_repairFullPanelPlan', '_calculatePanelLayout'];
                stages.forEach(function (name) {
                    wrapMethod(FenceCalculator.prototype, name, function (original) {
                        return function () {
                            if (!state.verbose) { return original.apply(this, arguments); }
                            var t0 = now();
                            var out = original.apply(this, arguments);
                            try {
                                traceStep({
                                    name: name.replace(/^_/, ''),
                                    stage: true,
                                    ms: Math.round((now() - t0) * 100) / 100,
                                    input: snapshot(arguments[0], 2),
                                    output: snapshot(out, 2)
                                });
                            } catch (e) {}
                            return out;
                        };
                    });
                });
                wrapMethod(FenceCalculator.prototype, '_resolveRakedSide', function (original) {
                    return function (custom_fence, info, sideKey, rakedSlug, postPanelMm) {
                        if (!state.verbose) { return original.apply(this, arguments); }
                        var t0 = now();
                        var out = original.apply(this, arguments);
                        try {
                            traceStep({
                                name: 'resolveRakedSide',
                                stage: true,
                                ms: Math.round((now() - t0) * 100) / 100,
                                input: { sideKey: sideKey, rakedSlug: snapshot(rakedSlug, 2), postPanelMm: postPanelMm },
                                output: snapshot(out, 2)
                            });
                        } catch (e) {}
                        return out;
                    };
                });
            }
        } catch (e) {}
    }

    // HELPER.call_fence_func swallows every fence-module exception and silently re-runs
    // the base method — the documented "my override isn't applying" trap. The replacement
    // preserves those semantics exactly (try module, catch -> base) and only ADDS a warn
    // record on the swallow path. Keep in sync with core/helpers.js if that ever changes.
    function installSwallowCollector() {
        try {
            if (typeof HELPER === 'undefined' || typeof HELPER.call_fence_func !== 'function' || HELPER.call_fence_func.__fcDebugWrapped) { return; }
            var replacement = function (_this, func, a, b, c, d, e, f) {
                // A module that simply doesn't define the hook is the DESIGNED fallback
                // (the original's try/catch reaches the base via a TypeError) — that path
                // is by-design and only traced in verbose. A hook that EXISTS and throws
                // is the documented "my override isn't applying" trap, and gets a warning.
                var hookMissing = !_this || typeof _this[func] !== 'function';
                try {
                    _this[func](a, b, c, d, e, f);
                } catch (err) {
                    try {
                        if (hookMissing) {
                            if (state.verbose) {
                                traceStep({ name: 'FENCE base: ' + func, dispatch: 'no module hook', ms: null });
                            }
                        } else {
                            state.counters.swallowed++;
                            addLog('warn', 'Fence hook "' + func + '" threw and fell back to the base FENCE method', {
                                func: func,
                                error: err && err.message ? err.message : String(err),
                                stack: err && err.stack ? String(err.stack).split('\n').slice(0, 4).join('\n') : ''
                            });
                        }
                    } catch (ignored) {}
                    FENCE[func](a, b, c, d, e, f);
                }
            };
            replacement.__fcDebugWrapped = true;
            HELPER.call_fence_func = replacement;
        } catch (e) {}
    }

    function installDispatchCollector() {
        try {
            if (typeof FENCE === 'undefined') { return; }
            wrapMethod(FENCE, 'call', function (original) {
                return function (func) {
                    if (state.verbose && !state.paused) {
                        try { traceStep({ name: 'FENCE.call', dispatch: func, ms: null }); } catch (e) {}
                    }
                    return original.apply(this, arguments);
                };
            });
        } catch (e) {}
    }

    function timedRender(label) {
        return function (original) {
            return function () {
                var t0 = now();
                var out = original.apply(this, arguments);
                try {
                    state.counters.renders++;
                    traceStep({
                        name: label,
                        render: true,
                        section: arguments.length ? snapshot(arguments[0], 1) : undefined,
                        ms: Math.round((now() - t0) * 100) / 100
                    });
                } catch (e) {}
                return out;
            };
        };
    }

    function installRenderCollectors() {
        try { if (typeof FENCE !== 'undefined') { wrapMethod(FENCE, 'load_fencing_items', timedRender('render: load_fencing_items')); } } catch (e) {}
        // The project-plan methods are hand-maintained forks of the z_fence originals —
        // they must be hooked separately or the bar reports only half the app.
        try {
            if (typeof ProjectPlan !== 'undefined') {
                wrapMethod(ProjectPlan, 'reload_load_fencing_items', timedRender('render: pp.reload_load_fencing_items'));
                wrapMethod(ProjectPlan, 're_update_gate', timedRender('render: pp.re_update_gate'));
                wrapMethod(ProjectPlan, 're_update_hinge_panel', timedRender('render: pp.re_update_hinge_panel'));
                wrapMethod(ProjectPlan, 're_update_raked_panels', timedRender('render: pp.re_update_raked_panels'));
            }
        } catch (e) {}
    }

    var CART_RULES = ['apply_barr_corner_post_rules', 'apply_panel_options_bracket_qty', 'apply_post_options_opt1',
        'apply_barr_post_cover_rules', 'apply_post_options_opt2', 'apply_panel_post', 'cart_conditions',
        'slat_fence_conditions', 'glass_pool_conditions', 'apply_barr_gate_panel_extra', 'apply_barr_bracket_rules',
        'pair_chem_anchor_glue'];

    function installCartCollectors() {
        try {
            if (typeof FENCES === 'undefined' || !FENCES.cartItems) { return; }

            wrapMethod(FENCES.cartItems, 'process', function (original) {
                return function (context, opts) {
                    var t0 = now();
                    var out = original.apply(this, arguments);
                    try {
                        var ms = Math.round((now() - t0) * 100) / 100;
                        state.lastProcess = {
                            at: Date.now(),
                            tabIndex: opts && opts.tabIndex !== undefined ? opts.tabIndex : null,
                            lines: Array.isArray(out) ? out.length : 0,
                            ms: ms
                        };
                        traceStep({
                            name: 'cartItems.process (BOM scrape)',
                            tabIndex: state.lastProcess.tabIndex,
                            lines: state.lastProcess.lines,
                            ms: ms
                        });
                    } catch (e) {}
                    return out;
                };
            });

            // Verbose: per-rule line provenance — each rule's add/remove delta on the BOM.
            CART_RULES.forEach(function (rule) {
                wrapMethod(FENCES.cartItems, rule, function (original) {
                    return function (items) {
                        if (!state.verbose) { return original.apply(this, arguments); }
                        var before = Array.isArray(items) ? items.length : 0;
                        var out = original.apply(this, arguments);
                        try {
                            var after = Array.isArray(out) ? out.length : 0;
                            if (after !== before) {
                                traceStep({ name: 'cart rule: ' + rule, lines: before + ' → ' + after, ms: null });
                            }
                        } catch (e) {}
                        return out;
                    };
                });
            });
        } catch (e) {}
    }

    function installInputCollectors() {
        wrapMethod(window, 'validateStep2BeforeCalculate', function (original) {
            return function () {
                var ok = original.apply(this, arguments);
                try {
                    var fields = [];
                    if (typeof fcCollectStep2FieldsFromDom === 'function') {
                        var raw = fcCollectStep2FieldsFromDom() || [];
                        for (var i = 0; i < raw.length; i++) {
                            fields.push({ name: raw[i].name, value: raw[i].value });
                        }
                    }
                    var overall = doc.querySelector('.measurement-box-number');
                    if (overall) { fields.push({ name: 'overall_length', value: overall.value }); }
                    var messages = [];
                    var shown = doc.querySelectorAll('.fc-input-msg.fcim-show');
                    for (var m = 0; m < shown.length; m++) {
                        var txt = (shown[m].textContent || '').trim();
                        if (txt) { messages.push(txt); }
                    }
                    state.lastValidation = { at: Date.now(), ok: !!ok, fields: fields, messages: messages };
                    traceStep({ name: 'validateStep2BeforeCalculate', ok: !!ok, messages: messages.length, ms: null });
                } catch (e) {}
                return ok;
            };
        });

        // jQuery bound the original btnCalculate by reference before this file ran, so
        // wrapping the global would never see button clicks. A capture-phase listener
        // (same pattern the app itself uses at events.js:25) starts a new trace run
        // without touching dispatch. Once-only: installCollectors re-runs on load.
        if (installInputCollectors.__clickBound) { return; }
        installInputCollectors.__clickBound = true;
        doc.addEventListener('click', function (e) {
            try {
                var target = e.target;
                if (target && target.closest && target.closest('.btn-fc-calculate')) {
                    rotateRunIfIdle(true);
                }
            } catch (err) {}
        }, true);
    }

    function installConsoleCollectors() {
        var levels = { log: 'info', info: 'info', warn: 'warn', error: 'error' };
        var recording = false;
        Object.keys(levels).forEach(function (method) {
            try {
                var original = window.console && window.console[method];
                if (typeof original !== 'function' || original.__fcDebugWrapped) { return; }
                var wrapped = function () {
                    // The browser console always gets the real call, first and untouched.
                    original.apply(window.console, arguments);
                    if (recording) { return; }
                    recording = true;
                    try {
                        var parts = [];
                        for (var i = 0; i < arguments.length; i++) {
                            var a = arguments[i];
                            parts.push(typeof a === 'string' ? a : JSON.stringify(snapshot(a, 2)));
                        }
                        addLog(levels[method], parts.join(' '), null);
                    } catch (e) {} finally {
                        recording = false;
                    }
                };
                wrapped.__fcDebugWrapped = true;
                window.console[method] = wrapped;
            } catch (e) {}
        });

        try {
            window.addEventListener('error', function (event) {
                addLog('error', 'Uncaught: ' + (event.message || 'script error'), {
                    source: (event.filename || '').split('/').pop() + ':' + (event.lineno || 0)
                });
            });
            window.addEventListener('unhandledrejection', function (event) {
                var reason = event.reason;
                addLog('error', 'Unhandled rejection: ' + (reason && reason.message ? reason.message : String(reason)), null);
            });
        } catch (e) {}
    }

    // ------------------------------------------------------------------
    // Requests (jQuery ajax + fetch — the census showed both in use, neither hooked)
    // ------------------------------------------------------------------

    function previewBody(body) {
        try {
            if (body === null || body === undefined) { return null; }
            if (typeof FormData !== 'undefined' && body instanceof FormData) {
                var obj = {};
                body.forEach(function (value, key) {
                    obj[key] = keyIsSensitive(key)
                        ? '[redacted]'
                        : (typeof value === 'string'
                            ? (value.length > 300 ? value.slice(0, 300) + '…(' + value.length + ')' : value)
                            : '[file]');
                });
                return obj;
            }
            if (typeof body === 'string') {
                var trimmed = body.trim();
                if (trimmed.charAt(0) === '{' || trimmed.charAt(0) === '[') {
                    try { return snapshot(JSON.parse(trimmed), 3); } catch (e2) {}
                }
                return trimmed.length > 800 ? trimmed.slice(0, 800) + '…(' + trimmed.length + ' chars)' : trimmed;
            }
            return snapshot(body, 3);
        } catch (e) {
            return '[unreadable]';
        }
    }

    function recordRequest(entry) {
        if (state.paused) { return; }
        entry.at = Date.now();
        entry.seq = ++entrySeq;
        state.requests.push(entry);
        emitChange();
    }

    function installRequestCollectors() {
        try {
            if (window.jQuery) {
                window.jQuery(doc).on('ajaxSend', function (event, xhr) {
                    try { xhr.__fcDebugT0 = now(); } catch (e) {}
                });
                window.jQuery(doc).on('ajaxComplete', function (event, xhr, settings) {
                    try {
                        var body = settings ? settings.data : null;
                        recordRequest({
                            via: 'jquery',
                            method: (settings && settings.type ? settings.type : 'GET').toUpperCase(),
                            url: settings ? String(settings.url) : '',
                            status: xhr.status,
                            ms: xhr.__fcDebugT0 ? Math.round((now() - xhr.__fcDebugT0) * 100) / 100 : null,
                            requestPreview: previewBody(body),
                            responseSize: xhr.responseText ? xhr.responseText.length : 0,
                            responsePreview: previewBody(xhr.responseText ? xhr.responseText.slice(0, 800) : '')
                        });
                    } catch (e) {}
                });
            }
        } catch (e) {}

        wrapMethod(window, 'fetch', function (original) {
            return function (input, init) {
                var t0 = now();
                var url = '';
                var method = 'GET';
                var reqPreview = null;
                try {
                    url = typeof input === 'string' ? input : (input && input.url) || '';
                    method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
                    reqPreview = previewBody(init ? init.body : null);
                } catch (e) {}
                var promise = original.apply(this, arguments);
                try {
                    promise.then(function (response) {
                        try {
                            var entry = {
                                via: 'fetch',
                                method: method,
                                url: url,
                                status: response.status,
                                ms: Math.round((now() - t0) * 100) / 100,
                                requestPreview: reqPreview,
                                responseSize: null,
                                responsePreview: null
                            };
                            response.clone().text().then(function (text) {
                                entry.responseSize = text.length;
                                entry.responsePreview = previewBody(text.slice(0, 800));
                                recordRequest(entry);
                            }, function () { recordRequest(entry); });
                        } catch (e) {}
                    }, function (err) {
                        recordRequest({
                            via: 'fetch', method: method, url: url, status: 0,
                            ms: Math.round((now() - t0) * 100) / 100,
                            requestPreview: reqPreview,
                            responseSize: 0,
                            responsePreview: 'network error: ' + (err && err.message ? err.message : String(err))
                        });
                    });
                } catch (e) {}
                return promise;
            };
        });
    }

    // ------------------------------------------------------------------
    // Readers the UI uses (read-only — never call cartItems.process()/init() here:
    // those "read" paths run calculate_fences and even write back to localStorage)
    // ------------------------------------------------------------------

    function readClientBom() {
        var rows = [];
        try {
            for (var i = 0; i < window.localStorage.length; i++) {
                var key = window.localStorage.key(i);
                var match = key && key.match(/^cart_items-(\d+)-(.+)$/);
                if (!match) { continue; }
                var items;
                try { items = JSON.parse(window.localStorage.getItem(key)); } catch (e) { continue; }
                if (!Array.isArray(items)) { continue; }
                for (var j = 0; j < items.length; j++) {
                    var item = items[j] || {};
                    rows.push({
                        bucket: parseInt(match[1], 10),
                        fence: match[2],
                        slug: item.slug,
                        qty: item.qty,
                        optional: !!item.optional,
                        suggested_qty: item.suggested_qty
                    });
                }
            }
            rows.sort(function (a, b) {
                return a.bucket - b.bucket || String(a.slug).localeCompare(String(b.slug));
            });
        } catch (e) {}
        return rows;
    }

    // ------------------------------------------------------------------
    // Client data stores (Data panel). Readers return plain rows; the mutators are the
    // only place in the Debugbar that writes anything, and they only ever touch the
    // visitor's own browser storage — never app state on the server.
    // ------------------------------------------------------------------

    function byteLength(value) {
        try {
            return typeof TextEncoder !== 'undefined' ? new TextEncoder().encode(value).length : String(value).length;
        } catch (e) {
            return String(value).length;
        }
    }

    function classifyValue(value) {
        var trimmed = String(value).trim();
        if (trimmed === '') { return 'empty'; }
        if (trimmed.charAt(0) === '{') { return 'json object'; }
        if (trimmed.charAt(0) === '[') { return 'json array'; }
        if (/^-?\d+(\.\d+)?$/.test(trimmed)) { return 'number'; }
        if (trimmed === 'true' || trimmed === 'false') { return 'boolean'; }
        return 'string';
    }

    // Which keys are FC's own. Taken from the literals and key builders in the app itself, not
    // guessed: cart_items-<section>-<slug> and custom_fence-<section>[-<slug>] (functions.js),
    // fc-step2-go-snap-<tab>-<slug> (functions.js), fc-debugbar, the fc-admin-*/fc-gallery-*/
    // fc-dashboard-* admin keys, plus the three bare names below. Everything else on the origin
    // belongs to a third party (Chatra, Google, Clarity), which is worth hiding by default but
    // never worth hiding silently - the count of what is out of scope is always on screen.
    var APP_KEY_PREFIXES = ['fc-', 'fc_', 'cart_items-', 'custom_fence-'];
    var APP_KEY_NAMES = ['project-plans', 'countdown-date', 'last-clicked-value'];
    // PHPSESSID is FC's own frontend session. It is readable here only because the frontend
    // session takes php.ini's cookie flags, where AuthService forces HttpOnly for the admin one.
    var APP_COOKIE_NAMES = ['PHPSESSID'];

    /** @param {'local'|'session'|'cookies'} kind */
    function isAppKey(name, kind) {
        var n = name === null || name === undefined ? '' : String(name);
        for (var i = 0; i < APP_KEY_PREFIXES.length; i++) {
            if (n.indexOf(APP_KEY_PREFIXES[i]) === 0) { return true; }
        }
        if (APP_KEY_NAMES.indexOf(n) !== -1) { return true; }
        return kind === 'cookies' && APP_COOKIE_NAMES.indexOf(n) !== -1;
    }

    // Which screen the bar is sitting on. The planner and the project plan load identical
    // assets but are not the same page: the project plan has no Step 2 and no Calculate
    // button, so an action or an empty-state line that names them is wrong there. Keyed off
    // the app's own body classes rather than the URL, which varies by mount depth and query.
    var PAGES = [
        { cls: 'fc-planner-page', key: 'planner', label: 'planner', calc: true },
        { cls: 'fc-project-plan-page', key: 'project-plan', label: 'project plan', calc: false }
    ];
    var UNKNOWN_PAGE = { cls: null, key: 'other', label: 'page', calc: false };

    function pageInfo() {
        var body = doc.body;
        var cls = body && typeof body.className === 'string' ? body.className : '';
        for (var i = 0; i < PAGES.length; i++) {
            // Word-boundary match: "fc-planner-page" must not be found inside another class.
            if ((' ' + cls + ' ').indexOf(' ' + PAGES[i].cls + ' ') !== -1) { return PAGES[i]; }
        }
        return UNKNOWN_PAGE;
    }

    function storageFor(kind) {
        return kind === 'session' ? window.sessionStorage : window.localStorage;
    }

    /** @param {'local'|'session'} kind */
    function readStorage(kind) {
        var rows = [];
        try {
            var store = storageFor(kind);
            for (var i = 0; i < store.length; i++) {
                var key = store.key(i);
                var raw = store.getItem(key);
                if (raw === null) { continue; }
                rows.push({
                    key: key,
                    // Redaction applies to the key name, same rule as everywhere else, so a
                    // token parked in storage is not put on screen for a shoulder-surfer.
                    value: keyIsSensitive(key) ? '[redacted]' : raw,
                    redacted: keyIsSensitive(key),
                    bytes: byteLength(raw),
                    type: classifyValue(raw),
                    app: isAppKey(key, kind)
                });
            }
            rows.sort(function (a, b) { return a.key.localeCompare(b.key); });
        } catch (e) {}
        return rows;
    }

    function readCookies() {
        var rows = [];
        try {
            var raw = doc.cookie ? doc.cookie.split('; ') : [];
            for (var i = 0; i < raw.length; i++) {
                var eq = raw[i].indexOf('=');
                var name = eq === -1 ? raw[i] : raw[i].slice(0, eq);
                var value = eq === -1 ? '' : raw[i].slice(eq + 1);
                if (!name) { continue; }
                rows.push({
                    key: name,
                    value: keyIsSensitive(name) ? '[redacted]' : decodeURIComponent(value),
                    redacted: keyIsSensitive(name),
                    bytes: byteLength(raw[i]),
                    type: classifyValue(value),
                    app: isAppKey(name, 'cookies')
                });
            }
            rows.sort(function (a, b) { return a.key.localeCompare(b.key); });
        } catch (e) {}
        return rows;
    }

    function removeStorageKey(kind, key) {
        try {
            storageFor(kind).removeItem(key);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * document.cookie can only clear what it can see (never HttpOnly ones, which is why
     * PHPSESSID and fc_admin_sess do not appear in the table at all). Expire against the
     * current path and root so a cookie set at either scope actually goes.
     */
    function removeCookie(name) {
        try {
            var paths = ['/', window.location.pathname, window.location.pathname.replace(/\/[^/]*$/, '/')];
            var expiry = 'Thu, 01 Jan 1970 00:00:00 GMT';
            for (var i = 0; i < paths.length; i++) {
                doc.cookie = encodeURIComponent(name) + '=; expires=' + expiry + '; path=' + paths[i];
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    function readPlannerState() {
        var out = { sections: [], sectionCount: null, plannerId: '', projectPlans: null };
        try {
            out.sectionCount = window.localStorage.getItem('custom_fence-section');
            out.plannerId = typeof window.planner_id !== 'undefined' ? String(window.planner_id || '') : '';
            var count = parseInt(out.sectionCount, 10) || 0;
            for (var i = 0; i < Math.max(count, 1) && i < 50; i++) {
                var raw = window.localStorage.getItem('custom_fence-' + i);
                if (!raw) { continue; }
                var row;
                try { row = (JSON.parse(raw) || [])[0] || {}; } catch (e) { continue; }
                out.sections.push({
                    tab: i,
                    section: i + 1,
                    style: row.style || row.fence || '',
                    length_mm: row.calculateValue !== undefined ? row.calculateValue : row.mbn,
                    calculated: !!row.isCalculate,
                    gateOnly: !!row.gateOnly
                });
            }
            var plans = window.localStorage.getItem('project-plans');
            if (plans) {
                try { out.projectPlans = snapshot(JSON.parse(plans), 3); } catch (e) {}
            }
        } catch (e) {}
        return out;
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    var api = {
        step: function (name, data) {
            try { traceStep({ name: 'app: ' + String(name), input: snapshot(data, 3), ms: null }); } catch (e) {}
        },
        log: function (level, message, context) {
            try {
                level = level === 'warn' || level === 'error' ? level : 'info';
                addLog(level, message, context);
            } catch (e) {}
        },
        mark: function (label) {
            try { state.marks[String(label)] = now(); } catch (e) {}
        },
        measure: function (label) {
            try {
                var start = state.marks[String(label)];
                if (start === undefined) { return null; }
                var ms = Math.round((now() - start) * 100) / 100;
                traceStep({ name: 'measure: ' + String(label), ms: ms });
                delete state.marks[String(label)];
                return ms;
            } catch (e) {
                return null;
            }
        }
    };

    window.FCDebugbar = {
        state: state,
        prefs: prefs,
        savePrefs: savePrefs,
        snapshot: snapshot,
        summarizeCalcEntry: summarizeCalcEntry,
        keyIsSensitive: keyIsSensitive,
        RingBuffer: RingBuffer,
        readClientBom: readClientBom,
        readPlannerState: readPlannerState,
        pageInfo: pageInfo,
        isAppKey: isAppKey,
        readStorage: readStorage,
        readCookies: readCookies,
        removeStorageKey: removeStorageKey,
        removeCookie: removeCookie,
        byteLength: byteLength,
        rotateRunIfIdle: rotateRunIfIdle,
        onChange: function (fn) { listeners.push(fn); },
        pause: function (value) { state.paused = value === undefined ? !state.paused : !!value; emitChange(); },
        setVerbose: function (value) {
            state.verbose = !!value;
            prefs.verbose = state.verbose;
            savePrefs();
            emitChange();
        },
        clear: function () {
            state.trace.clear();
            state.logs.clear();
            state.requests.clear();
            state.prevRun = [];
            state.currentRun = [];
            state.warnCount = 0;
            state.errorCount = 0;
            state.counters = { calcCalls: 0, calcRuns: 0, renders: 0, swallowed: 0 };
            emitChange();
        },
        installCollectors: function () {
            // Wrap-targets carry __fcDebugWrapped so re-running only wraps what appeared
            // since; the console/error listeners are once-only via this flag.
            installCalcCollector();
            installSwallowCollector();
            installDispatchCollector();
            installRenderCollectors();
            installCartCollectors();
            installInputCollectors();
            if (!window.FCDebugbar.__listenersInstalled) {
                window.FCDebugbar.__listenersInstalled = true;
                installConsoleCollectors();
                installRequestCollectors();
            }
        },
        api: api
    };

    window.debugbar = api;

    // Install once every deferred script has executed. Deferred scripts (this one included)
    // run while readyState is 'interactive', BEFORE the page-tail scripts that define
    // FENCES.cartItems / ProjectPlan — so installing "immediately when not loading" would
    // miss them. DOMContentLoaded fires only after the whole deferred chain, and jQuery 3
    // fires $(function) ready handlers asynchronously after that dispatch, so wrappers
    // registered in this synchronous listener are in place before Planner/ProjectPlan init.
    // First pass right now: the core chain and fences glob precede this tag, so
    // calculate_fences / FENCE / HELPER are already wrappable — and the project-plan
    // tail invokes its initial render at parse time, before DOMContentLoaded.
    window.FCDebugbar.installCollectors();
    if (doc.readyState !== 'complete') {
        doc.addEventListener('DOMContentLoaded', function () {
            window.FCDebugbar.installCollectors();
        });
        // Belt-and-braces for anything that appears between DOMContentLoaded and load.
        window.addEventListener('load', function () {
            window.FCDebugbar.installCollectors();
        });
    }
})(window);
