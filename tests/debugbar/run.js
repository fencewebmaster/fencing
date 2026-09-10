/**
 * FC Debugbar — client-core harness (zero-dependency, same pattern as
 * tests/glass-pool-clamps): loads debugbar-core.js into a vm sandbox with browser
 * stubs and exercises the pieces the calculator's safety depends on.
 *
 *   node tests/debugbar/run.js
 *
 * The PHP half (redaction + ConsoleSettings normalize) lives in run-php.php:
 *
 *   php tests/debugbar/run-php.php
 */
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const CORE = path.join(__dirname, '..', '..', 'public', 'assets', 'js', 'frontend', 'debug', 'debugbar-core.js');

let passed = 0;
let failed = 0;

function check(name, condition, detail) {
    if (condition) {
        passed++;
        console.log('  ok  ' + name);
    } else {
        failed++;
        console.log('FAIL  ' + name + (detail !== undefined ? ' — ' + JSON.stringify(detail) : ''));
    }
}

function makeSandbox(options) {
    options = options || {};
    const storage = new Map(Object.entries(options.localStorage || {}));
    const listeners = [];

    const documentStub = {
        readyState: 'complete',
        getElementById: (id) => (id === 'fc-debugbar-server' && options.island
            ? { textContent: JSON.stringify(options.island) }
            : null),
        addEventListener: (type, fn) => listeners.push(['document', type, fn]),
        querySelector: () => null,
        querySelectorAll: () => [],
        // pageInfo() reads the app's own body class to tell planner from project plan.
        body: { className: options.bodyClass === undefined ? 'fc-planner-page' : options.bodyClass }
    };

    // sessionStorage + document.cookie stubs so the Data panel readers can be exercised.
    const sessionStore = new Map(Object.entries(options.sessionStorage || {}));
    let cookieJar = options.cookie || '';
    Object.defineProperty(documentStub, 'cookie', {
        get: () => cookieJar,
        set: (v) => {
            const name = String(v).split('=')[0];
            if (/expires=Thu, 01 Jan 1970/.test(v)) {
                cookieJar = cookieJar
                    .split('; ')
                    .filter((c) => c && c.split('=')[0] !== decodeURIComponent(name))
                    .join('; ');
            } else {
                cookieJar = cookieJar ? cookieJar + '; ' + v : String(v);
            }
        },
        configurable: true
    });

    const sandbox = {
        document: documentStub,
        sessionStorage: {
            get length() { return sessionStore.size; },
            key: (i) => Array.from(sessionStore.keys())[i],
            getItem: (k) => (sessionStore.has(k) ? sessionStore.get(k) : null),
            setItem: (k, v) => sessionStore.set(k, String(v)),
            removeItem: (k) => sessionStore.delete(k)
        },
        location: { pathname: '/wp/fence/fc/planner' },
        TextEncoder,
        localStorage: {
            get length() { return storage.size; },
            key: (i) => Array.from(storage.keys())[i],
            getItem: (k) => (storage.has(k) ? storage.get(k) : null),
            setItem: (k, v) => storage.set(k, String(v)),
            removeItem: (k) => storage.delete(k)
        },
        performance: { now: () => Date.now() },
        setTimeout: (fn) => { fn(); return 0; },
        console: { log() {}, info() {}, warn() {}, error() {} },
        addEventListener: (type, fn) => listeners.push(['window', type, fn]),
        Date,
        JSON,
        Math,
        Object,
        Array,
        String,
        Number,
        parseInt,
        isNaN,
        __listeners: listeners
    };
    sandbox.window = sandbox;
    vm.createContext(sandbox);
    vm.runInContext(fs.readFileSync(CORE, 'utf8'), sandbox, { filename: 'debugbar-core.js' });
    return sandbox;
}

// ---------------------------------------------------------------- gate / API
{
    const sb = makeSandbox();
    check('core exposes FCDebugbar + debugbar API', !!sb.FCDebugbar && typeof sb.debugbar === 'object');
    check('api.step/log/mark/measure never throw', (() => {
        sb.debugbar.step('x', { a: 1 });
        sb.debugbar.log('warn', 'w');
        sb.debugbar.log('bogus-level', 'coerced to info');
        sb.debugbar.mark('m');
        sb.debugbar.measure('m');
        sb.debugbar.measure('never-marked');
        return true;
    })());
    check('bogus level coerces to info', sb.FCDebugbar.state.logs.items.some(l => l.level === 'info' && l.message.indexOf('coerced') !== -1));
    check('island absent -> default maxEntries 200', sb.FCDebugbar.state.trace.cap === 200);
}

// ---------------------------------------------------------------- ring buffer
{
    const sb = makeSandbox();
    const rb = new sb.FCDebugbar.RingBuffer(3);
    for (let i = 0; i < 5; i++) { rb.push(i); }
    check('ring buffer caps at capacity', rb.items.length === 3 && rb.items[0] === 2);
    check('ring buffer counts drops + total', rb.dropped === 2 && rb.total === 5);
}

// ---------------------------------------------------------------- snapshot / redaction
{
    const sb = makeSandbox({ island: { config: { redactKeys: ['password', 'key'] } } });
    const snap = sb.FCDebugbar.snapshot;
    const out = snap({
        name: 'x',
        api_key: 'SECRET',
        nested: { db_password: 'p', ok: 1 },
        long: 'a'.repeat(600),
        big: Array.from({ length: 42 }, (_, i) => i)
    });
    check('sensitive keys redacted (substring match)', out.api_key === '[redacted]' && out.nested.db_password === '[redacted]');
    check('non-sensitive values survive', out.name === 'x' && out.nested.ok === 1);
    check('long strings truncate with length note', /…\(600 chars\)$/.test(out.long));
    check('big arrays cap at 10 + remainder note', out.big.length === 11 && out.big[10] === '… 32 more');
    check('depth cap stops runaway recursion', (() => {
        const deep = {}; let cur = deep;
        for (let i = 0; i < 10; i++) { cur.next = {}; cur = cur.next; }
        const s = snap(deep);
        return JSON.stringify(s).indexOf('[object]') !== -1;
    })());
}

// ---------------------------------------------------------------- call_fence_func semantics
{
    const sb = makeSandbox();
    const calls = [];
    sb.HELPER = { call_fence_func: function (t, f, a) { try { t[f](a); } catch (e) { sb.FENCE[f](a); } } };
    sb.FENCE = { hook: (a) => calls.push(['base', a]) };
    sb.FCDebugbar.installCollectors();

    const moduleWithout = {};
    sb.HELPER.call_fence_func(moduleWithout, 'hook', 1);
    check('missing module hook falls back to base (by design)', calls.length === 1 && calls[0][0] === 'base');
    check('missing hook is NOT a warning', sb.FCDebugbar.state.counters.swallowed === 0 && sb.FCDebugbar.state.warnCount === 0);

    const moduleThrowing = { hook: () => { throw new Error('boom'); } };
    sb.HELPER.call_fence_func(moduleThrowing, 'hook', 2);
    check('throwing module hook STILL falls back to base', calls.length === 2 && calls[1][0] === 'base');
    check('throwing hook surfaces one warning', sb.FCDebugbar.state.counters.swallowed === 1 &&
        sb.FCDebugbar.state.logs.items.some(l => l.level === 'warn' && l.message.indexOf('hook "hook" threw') !== -1));

    const moduleWorking = { hook: (a) => calls.push(['module', a]) };
    sb.HELPER.call_fence_func(moduleWorking, 'hook', 3);
    check('working module hook runs, base untouched', calls.length === 3 && calls[2][0] === 'module');
}

// ---------------------------------------------------------------- calculate_fences wrapper
{
    const sb = makeSandbox();
    let ran = 0;
    sb.calculate_fences = function (data) { ran++; return { fence_size: 5000, echo: data }; };
    sb.FCDebugbar.installCollectors();
    check('calculate_fences gets wrapped', sb.calculate_fences.__fcDebugWrapped === true);
    const result = sb.calculate_fences({ tab: 3, item: 'slat' });
    check('wrapped calc returns the original result untouched', ran === 1 && result.fence_size === 5000 && result.echo.item === 'slat');
    check('calc call recorded with tab/item + summary', (() => {
        const s = sb.FCDebugbar.state;
        return s.counters.calcCalls === 1 && s.lastCalc && s.lastCalc.tab === 3 && s.lastCalc.item === 'slat' &&
            s.trace.items.some(e => e.name === 'calculate_fences');
    })());
    check('double install never double-wraps', (() => {
        sb.FCDebugbar.installCollectors();
        sb.calculate_fences({ tab: 0, item: 'barr' });
        return sb.FCDebugbar.state.counters.calcCalls === 2; // one increment per call, not two
    })());
}

// ---------------------------------------------------------------- run grouping + pause + clear
{
    const sb = makeSandbox();
    sb.calculate_fences = () => ({});
    sb.FCDebugbar.installCollectors();
    sb.calculate_fences({});
    check('entries land in currentRun', sb.FCDebugbar.state.currentRun.length === 1);
    sb.FCDebugbar.rotateRunIfIdle(true);
    check('forced rotation moves current -> previous and counts a run', sb.FCDebugbar.state.prevRun.length === 1 &&
        sb.FCDebugbar.state.currentRun.length === 0 && sb.FCDebugbar.state.counters.calcRuns === 1);

    sb.FCDebugbar.pause(true);
    sb.calculate_fences({});
    check('paused capture records nothing (call still runs)', sb.FCDebugbar.state.trace.items.length === 1);
    sb.FCDebugbar.pause(false);

    sb.FCDebugbar.clear();
    check('clear empties buffers and counters', sb.FCDebugbar.state.trace.items.length === 0 &&
        sb.FCDebugbar.state.counters.calcCalls === 0 && sb.FCDebugbar.state.warnCount === 0);
}

// ---------------------------------------------------------------- BOM reader
{
    const sb = makeSandbox({
        localStorage: {
            'cart_items-3-slat': JSON.stringify([{ slug: 'panel_post+opt-1+1500', qty: 22 }, { slug: 'chem_achor+glue', qty: 0, optional: true, suggested_qty: 2 }]),
            'cart_items-0-flat_top': JSON.stringify([{ slug: 'panel_options+full+1200', qty: 3 }]),
            'cart_items-2': JSON.stringify([{ slug: 'legacy-flat-blob', qty: 1 }]),
            'custom_fence-3': JSON.stringify([{ style: 'slat', isCalculate: true, calculateValue: 50000, gateOnly: false }]),
            'custom_fence-section': '4'
        }
    });
    const rows = sb.FCDebugbar.readClientBom();
    check('BOM reader parses slugged buckets only (legacy flat blob ignored)', rows.length === 3 &&
        !rows.some(r => r.slug === 'legacy-flat-blob'));
    check('BOM rows keep optional + suggested qty', rows.some(r => r.slug === 'chem_achor+glue' && r.optional && r.suggested_qty === 2));
    check('BOM rows sorted by bucket', rows[0].fence === 'flat_top' && rows[rows.length - 1].fence === 'slat');

    const ps = sb.FCDebugbar.readPlannerState();
    check('planner-state reader maps 0-based tab to 1-based section', ps.sections.length === 1 &&
        ps.sections[0].tab === 3 && ps.sections[0].section === 4 && ps.sections[0].style === 'slat' && ps.sections[0].calculated === true);
}

// ---------------------------------------------------------------- island config respected
{
    const sb = makeSandbox({ island: { config: { verbose: true, maxEntries: 75, redactKeys: ['zzz'] } } });
    check('island maxEntries clamps buffers', sb.FCDebugbar.state.trace.cap === 75);
    check('island verbose applies', sb.FCDebugbar.state.verbose === true);
    check('island redact keys apply', sb.FCDebugbar.keyIsSensitive('my_zzz_field') === true &&
        sb.FCDebugbar.keyIsSensitive('password') === false);
}

// ---------------------------------------------------------------- Data panel readers
{
    const sb = makeSandbox({
        island: { config: { redactKeys: ['token', 'password'] } },
        localStorage: {
            'custom_fence-section': '5',
            'project-plans': '{"name":"Test","mobile":"0412345678"}',
            'api_token': 'super-secret',
            'plain': 'hello'
        },
        sessionStorage: { 'only-here': '42' },
        cookie: 'PHPSESSID_visible=abc123; _ga=GA1.1.99; auth_token=leaky'
    });

    const local = sb.FCDebugbar.readStorage('local');
    check('storage reader lists every key, sorted', local.length === 4 && local[0].key === 'api_token');
    check('storage reader redacts by key name', (() => {
        const secret = local.find((r) => r.key === 'api_token');
        return secret.value === '[redacted]' && secret.redacted === true;
    })());
    check('storage reader leaves ordinary values intact', local.find((r) => r.key === 'plain').value === 'hello');
    check('storage reader classifies types', (() => {
        const byKey = Object.fromEntries(local.map((r) => [r.key, r.type]));
        return byKey['project-plans'] === 'json object' && byKey['custom_fence-section'] === 'number' && byKey.plain === 'string';
    })());
    check('storage reader reports byte sizes', local.find((r) => r.key === 'plain').bytes === 5);

    check('session storage is read separately from local', (() => {
        const sess = sb.FCDebugbar.readStorage('session');
        return sess.length === 1 && sess[0].key === 'only-here' && sess[0].value === '42';
    })());

    const cookies = sb.FCDebugbar.readCookies();
    check('cookie reader splits name/value pairs', cookies.length === 3 && cookies.some((c) => c.key === '_ga' && c.value === 'GA1.1.99'));
    check('cookie reader redacts sensitive names', cookies.find((c) => c.key === 'auth_token').value === '[redacted]');

    check('removeStorageKey deletes only its own key', (() => {
        sb.FCDebugbar.removeStorageKey('local', 'plain');
        const after = sb.FCDebugbar.readStorage('local');
        return after.length === 3 && !after.some((r) => r.key === 'plain');
    })());
    check('removeStorageKey does not touch the other store', sb.FCDebugbar.readStorage('session').length === 1);

    check('removeCookie expires the named cookie only', (() => {
        sb.FCDebugbar.removeCookie('_ga');
        const after = sb.FCDebugbar.readCookies();
        return after.length === 2 && !after.some((c) => c.key === '_ga');
    })());

    // App-scope classification. The prefixes below are the ones the app's own key builders
    // produce, so a change to those builders should break these tests, not slip through.
    const isApp = sb.FCDebugbar.isAppKey;
    check('app scope: FC key builders are recognised', (() => {
        return ['cart_items-0-flat_top', 'custom_fence-3-slat', 'custom_fence-section',
            'fc-step2-go-snap-0-barr', 'fc-debugbar', 'fc-admin-appearance',
            'fc-gallery-view-mode', 'project-plans', 'countdown-date', 'last-clicked-value']
            .every((k) => isApp(k, 'local'));
    })());
    check('app scope: third-party keys are excluded', (() => {
        return ['_gcl_ls', 'Chatra.clientId', '_cltk', '_ga', '_clck',
            'AYCvCOPyvZsbEKjx8c4o,0480016687'].every((k) => isApp(k, 'local') === false);
    })());
    check('app scope: prefix must match at position 0, not anywhere', isApp('evil-fc-debugbar', 'local') === false);
    check('app scope: PHPSESSID counts only as a cookie', isApp('PHPSESSID', 'cookies') === true && isApp('PHPSESSID', 'local') === false);
    check('app scope: admin fc_ cookies are FC keys', isApp('fc_admin_sess', 'cookies') === true);
    check('app scope: empty and nullish names are not FC keys', isApp('', 'local') === false && isApp(null, 'local') === false && isApp(undefined, 'local') === false);
    check('storage rows carry their own app flag', (() => {
        const rows = sb.FCDebugbar.readStorage('local');
        const fence = rows.find((r) => r.key === 'custom_fence-section');
        const token = rows.find((r) => r.key === 'api_token');
        return fence && fence.app === true && token && token.app === false;
    })());
    check('cookie rows carry their own app flag', (() => {
        const rows = sb.FCDebugbar.readCookies();
        return rows.every((r) => typeof r.app === 'boolean') &&
            rows.filter((r) => r.app).length === 0;
    })());

    // Page classification. The planner and the project plan share every asset, so the bar
    // has to tell them apart to know whether Re-run and the Step 2 copy make any sense.
    check('page: planner recognised from its body class', (() => {
        const p = makeSandbox({ bodyClass: 'fc-planner-page' }).FCDebugbar.pageInfo();
        return p.key === 'planner' && p.calc === true && p.label === 'planner';
    })());
    check('page: project plan recognised, and has no calculate step', (() => {
        const p = makeSandbox({ bodyClass: 'fc-project-plan-page fc-project-plan-page-loading' }).FCDebugbar.pageInfo();
        return p.key === 'project-plan' && p.calc === false && p.label === 'project plan';
    })());
    check('page: extra classes do not defeat the match', (() => {
        const p = makeSandbox({ bodyClass: 'modal-open fc-planner-page fc-debugbar-padded' }).FCDebugbar.pageInfo();
        return p.key === 'planner';
    })());
    check('page: a class merely containing the name does not match', (() => {
        const p = makeSandbox({ bodyClass: 'not-fc-planner-pageX' }).FCDebugbar.pageInfo();
        return p.key === 'other' && p.calc === false;
    })());
    check('page: unknown page falls back without claiming a calculate step', (() => {
        const p = makeSandbox({ bodyClass: '' }).FCDebugbar.pageInfo();
        return p.key === 'other' && p.calc === false && p.cls === null;
    })());

    check('byteLength counts UTF-8 bytes, not characters', sb.FCDebugbar.byteLength('é') === 2);
}

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
