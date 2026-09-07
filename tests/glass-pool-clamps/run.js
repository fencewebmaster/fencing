'use strict';

/**
 * Glass Pool panel-to-panel clamp tests.
 *
 * Node only, no dependencies:  node tests/glass-pool-clamps/run.js   (from the project root)
 *
 * Loads the working-tree solver (fences/calc/glass_pool.js) into one vm context and the
 * PINNED baseline revision into a second one, then proves (a) the clamp helpers, (b) that the
 * non-enforced solver output is unchanged against the baseline across a broad sweep, and
 * (c) what enforcement does and does not change. See README.md for what each block proves.
 *
 * The contexts are sloppy-mode on purpose: the solver assigns an implicit global
 * (numFixedElements), which a strict-mode load would throw on.
 */

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const { execSync } = require('child_process');

const ROOT = path.resolve(__dirname, '..', '..');
const SOLVER = 'public/assets/js/frontend/fences/calc/glass_pool.js';
// The last commit BEFORE the clamp logic landed. HEAD would become a tautology once the clamp
// commit exists, so the baseline is pinned - re-pin only after an intentional solver change
// (README.md).
const BASELINE_REV = '37dc446';

// --------------------------------------------------------------------------------------------
// Loading
// --------------------------------------------------------------------------------------------

function loadContext(src, label) {
    const ctx = vm.createContext({ GlassPool: {}, console });
    vm.runInContext(src, ctx, { filename: label });
    return ctx;
}

let live;
let base;
try {
    live = loadContext(fs.readFileSync(path.join(ROOT, SOLVER), 'utf8'), 'live:' + SOLVER);
    const baseSrc = execSync(`git show ${BASELINE_REV}:${SOLVER}`, {
        cwd: ROOT,
        encoding: 'utf8',
        maxBuffer: 1 << 24,
        stdio: ['ignore', 'pipe', 'pipe']
    });
    base = loadContext(baseSrc, 'baseline:' + SOLVER);
} catch (e) {
    console.error('Could not load the solver contexts: ' + (e && e.message ? e.message : e));
    process.exit(2);
}

const L = live.GlassPoolCalc;
const B = base.GlassPoolCalc;

// --------------------------------------------------------------------------------------------
// Harness
// --------------------------------------------------------------------------------------------

const MAX_DETAILS = 8;

class Block {
    constructor(name) {
        this.name = name;
        this.cases = 0;
        this.failures = 0;
        this.details = [];
        this.notes = [];
    }
    fail(msg) {
        this.failures++;
        if (this.details.length < MAX_DETAILS) this.details.push(msg);
    }
    check(cond, msg) {
        this.cases++;
        if (!cond) this.fail(msg);
    }
    eq(actual, expected, msg) {
        this.cases++;
        try {
            assert.deepStrictEqual(actual, expected);
        } catch (e) {
            this.fail(msg + '\n      ' + String(e.message).split('\n').slice(0, 12).join('\n      '));
        }
    }
    note(msg) {
        this.notes.push(msg);
    }
}

const blocks = [];
function block(name, fn) {
    blocks.push({ name, fn });
}

// Objects created inside a vm context carry that context's Object.prototype, and
// assert.deepStrictEqual compares prototypes with ===, so a live-vs-baseline comparison
// would fail on realm alone. Clone into this realm first; unlike a JSON round-trip this keeps
// NaN, undefined and -0 exactly as the solver produced them.
function toRealm(v) {
    if (Array.isArray(v)) {
        // Not v.map(): that allocates through the source array's realm and comes back foreign.
        const a = [];
        for (let i = 0; i < v.length; i++) a.push(toRealm(v[i]));
        return a;
    }
    if (v && typeof v === 'object') {
        const o = {};
        for (const k of Object.keys(v)) o[k] = toRealm(v[k]);
        return o;
    }
    return v;
}

function fmt(v) {
    return JSON.stringify(v);
}

// --------------------------------------------------------------------------------------------
// Sweep fixtures (blocks 4-6)
// --------------------------------------------------------------------------------------------

const SWEEP = {
    overallFrom: 900,
    overallTo: 12000,
    overallStep: 37,
    dps: [1200, 1400, 2000],
    pg: [30, 50, 80],
    ends: [-1, 0, 25], // -1 = dynamic ("gap based" = ''), 0 and 25 fixed
    gate: [false, true]
};
const GATE_WIDTH = 890;
const HINGE_PANEL = 800;

function sweepCases() {
    const out = [];
    for (let overall = SWEEP.overallFrom; overall <= SWEEP.overallTo; overall += SWEEP.overallStep) {
        for (const dps of SWEEP.dps) {
            for (const pg of SWEEP.pg) {
                for (const left of SWEEP.ends) {
                    for (const right of SWEEP.ends) {
                        for (const gate of SWEEP.gate) {
                            out.push({ overall, dps, pg, left, right, gate });
                        }
                    }
                }
            }
        }
    }
    return out;
}

function caseLabel(c) {
    return `overall=${c.overall} dps=${c.dps} pg=${c.pg} ends=${c.left === -1 ? "''" : c.left}/${c.right === -1 ? "''" : c.right} gate=${c.gate ? 'on' : 'off'}`;
}

// calculatePanels() input for one case. Every gate case passes a finite hinge panel width so
// the FENCE-less default lookup never runs.
function makeInput(c, clamp) {
    return {
        slug: 'glass_pool',
        info: {},
        customFence: [],
        gateData: null,
        gateOnly: false,
        overallLengthMm: c.overall,
        gateCount: c.gate ? 1 : 0,
        gateWidthMm: c.gate ? GATE_WIDTH : 0,
        gateHingePanelWidthMm: HINGE_PANEL,
        leftRakedWidthMm: 0,
        rightRakedWidthMm: 0,
        leftSideWidthD: c.left,
        rightSideWidthD: c.right,
        defaultPanelSizeMm: c.dps,
        panelGapMm: c.pg,
        clamp: clamp
    };
}

// The solver config calculatePanels() builds for the same case - mirrored here so a block
// can re-run calculateGlassFencing() directly (geometry check, strict-only classification).
// Block 5 cross-checks it against calculatePanels' own output so a drift shows up as a failure.
function buildConfig(c, extraPanelSettings) {
    const cfg = {
        overallLength: c.overall,
        gate: {
            active: !!c.gate,
            gateSize: c.gate ? GATE_WIDTH : 0,
            hingePanelSize: HINGE_PANEL,
            hingePanelActive: !!c.gate,
            hingeType: { left: 10, right: 9 }
        },
        leftRakedPanel: { active: false, size: 0 },
        rightRakedPanel: { active: false, size: 0 },
        leftEndAttachment: { size: c.left === -1 ? '' : c.left },
        rightEndAttachment: { size: c.right === -1 ? '' : c.right },
        panelSettings: {
            maxPanelSize: 2000,
            minPanelSize: 200,
            defaultPanelSize: c.dps,
            panelSizeIncrement: 50,
            panelGap: c.pg,
            maxPanelSpacing: 80
        }
    };
    Object.assign(cfg.panelSettings, extraPanelSettings || {});
    return cfg;
}

const NOT_SELECTED = { selected: false };
function enforcedRequest() {
    return { selected: true, enforce: true, sizes: L.clampDefaults(), minGapMm: 20, maxGapMm: 95 };
}

// A panel-to-panel junction exists when two glass panels meet: regular panels plus the hinge
// panel counted as one (equivalently: at least one regular panel next to a gate).
function hasJunction(out) {
    const regular = (out.long_panel_count || 0) + (out.short_panel_count || 0);
    return regular + (out.gate_hinge_panel_count || 0) > 1;
}

function stripClamp(out) {
    const o = toRealm(out);
    delete o.clamp;
    return o;
}

// panels + gaps + fixed glass + fixed ends == overall, mirroring the solver's own accounting:
// a dynamic end is one of numberOfPanelGaps, a fixed end is its own width.
function geometry(cfg, results) {
    const leftDyn = cfg.leftEndAttachment.size === '';
    const rightDyn = cfg.rightEndAttachment.size === '';
    const panels = results.longPanels.count * results.longPanels.size + results.shortPanel.count * results.shortPanel.size;
    const gaps = results.numberOfPanelGaps * results.panelGapsValue;
    const fixed = results.leftRakedSize + results.rightRakedSize
        + (cfg.gate.active ? results.gateSize + results.hingeSize + results.gateGaps.left + results.gateGaps.right : 0);
    const ends = (leftDyn ? 0 : results.leftSideGap) + (rightDyn ? 0 : results.rightSideGap);
    return panels + gaps + fixed + ends;
}

// Shared sweep results, computed once and read by blocks 4-6.
let sweep = null;
function runSweep() {
    if (sweep) return sweep;
    const cases = sweepCases();
    const rows = [];
    const t0 = Date.now();
    for (const c of cases) {
        rows.push({
            c,
            base: B.calculatePanels(makeInput(c, NOT_SELECTED)),
            live: L.calculatePanels(makeInput(c, NOT_SELECTED)),
            enforced: L.calculatePanels(makeInput(c, enforcedRequest()))
        });
    }
    sweep = { cases, rows, ms: Date.now() - t0 };
    return sweep;
}

// --------------------------------------------------------------------------------------------
// Block 1 - clampForGap / clampResult classification
// --------------------------------------------------------------------------------------------

block('1 clampForGap table', (t) => {
    t.check(typeof L.clampForGap === 'function', 'clampForGap exists');
    t.check(live.GlassPool.CLAMP_CONTROL_KEY === 'panel_clamps', 'CLAMP_CONTROL_KEY copied onto GlassPool');
    t.check(live.GlassPool.CLAMP_ADJUST_KEY === 'gap_adjust', 'CLAMP_ADJUST_KEY');
    t.check(live.GlassPool.CLAMP_OPTION_KEY === 'post_option', 'CLAMP_OPTION_KEY');
    t.check(live.GlassPool.CLAMP_OPTION_YES === 'opt-2', 'CLAMP_OPTION_YES');
    t.check(live.GlassPool.clampForGap === L.clampForGap, 'clampForGap attached onto GlassPool');

    const defaults = L.clampDefaults();
    const table = [
        [13, 'below_min', null], [19, 'below_min', null],
        [20, 'ok', 'small'], [35, 'ok', 'small'], [49, 'ok', 'small'],
        [50, 'ok', 'large'], [75, 'ok', 'large'], [95, 'ok', 'large'],
        [96, 'above_max', null], [100, 'above_max', null],
        [0, 'invalid', null], [NaN, 'invalid', null], [-5, 'invalid', null], [Infinity, 'invalid', null]
    ];
    for (const [gap, status, sizeKey] of table) {
        const r = L.clampForGap(gap, defaults);
        t.check(r.status === status, `clampForGap(${gap}) status ${r.status}, expected ${status}`);
        t.check((r.size ? r.size.key : null) === sizeKey, `clampForGap(${gap}) size ${r.size ? r.size.key : null}, expected ${sizeKey}`);
        t.check(r.minGapMm === 20 && r.maxGapMm === 95, `clampForGap(${gap}) carries the overall 20..95 range`);
    }

    // clampResult rounds first: 19.6 -> 20 Small, 49.6 -> 50 Large, 49.4 -> 49 Small.
    const req = { selected: true, enforce: false, sizes: defaults, minGapMm: 20, maxGapMm: 95 };
    const r196 = L.clampResult(req, 19.6, false);
    t.check(r196.status === 'ok' && r196.size.key === 'small' && r196.gapMm === 20 && r196.gapExactMm === 19.6,
        'clampResult 19.6 -> ' + fmt(r196));
    t.check(r196.slug === 'panel_to_panel_clamp+sm', 'clampResult 19.6 slug');
    const r496 = L.clampResult(req, 49.6, false);
    t.check(r496.status === 'ok' && r496.size.key === 'large' && r496.gapMm === 50 && r496.slug === 'panel_to_panel_clamp+lg',
        'clampResult 49.6 -> ' + fmt(r496));
    const r494 = L.clampResult(req, 49.4, false);
    t.check(r494.status === 'ok' && r494.size.key === 'small', 'clampResult 49.4 -> ' + fmt(r494));
    const r194 = L.clampResult(req, 19.4, false);
    t.check(r194.status === 'below_min' && r194.size === null && r194.slug === '', 'clampResult 19.4 -> ' + fmt(r194));
    const rFail = L.clampResult(req, 35, true);
    t.check(rFail.status === 'invalid' && rFail.selected === true, 'clampResult solverFailed -> invalid: ' + fmt(rFail));
    const rNaN = L.clampResult(req, NaN, false);
    t.check(rNaN.status === 'invalid' && rNaN.gapMm === 0 && rNaN.gapExactMm === 0, 'clampResult NaN -> invalid: ' + fmt(rNaN));
    const rNone = L.clampResult({ selected: false }, 13, false);
    t.check(rNone.status === 'none' && rNone.selected === false && rNone.enforce === false && rNone.gapMm === 13
        && rNone.minGapMm === 20 && rNone.maxGapMm === 95, 'clampResult not selected -> none: ' + fmt(rNone));
    const rEnf = L.clampResult({ selected: true, enforce: true, sizes: defaults }, 60, false);
    t.check(rEnf.enforce === true && rEnf.status === 'ok' && rEnf.size.key === 'large', 'clampResult carries enforce');

    // Config-derived ranges drive the classification, not the defaults.
    const cfgSizes = L.clampSizesFromInfo({ panel_clamps: {
        small: { title: 'Small', min_gap_mm: 25, max_gap_mm: 60, slug: 'clamp+s' },
        large: { title: 'Large', min_gap_mm: 60, max_gap_mm: 90, slug: 'clamp+l' }
    } });
    const cfgTable = [[24, 'below_min', null], [25, 'ok', 'small'], [59, 'ok', 'small'], [60, 'ok', 'large'],
        [90, 'ok', 'large'], [91, 'above_max', null]];
    for (const [gap, status, sizeKey] of cfgTable) {
        const r = L.clampForGap(gap, cfgSizes);
        t.check(r.status === status && (r.size ? r.size.key : null) === sizeKey && r.minGapMm === 25 && r.maxGapMm === 90,
            `config 25-60/60-90: clampForGap(${gap}) -> ${fmt(r)}`);
    }
    const rCfg = L.clampResult({ selected: true, sizes: cfgSizes }, 59.6, false);
    t.check(rCfg.status === 'ok' && rCfg.slug === 'clamp+l' && rCfg.minGapMm === 25 && rCfg.maxGapMm === 90,
        'clampResult with config sizes 59.6 -> ' + fmt(rCfg));

    // A hole between two configured sizes (the admin GUI does not force the ranges to touch):
    // inside the overall range but no clamp fits -> no_size, never above_max. 45 is no_size
    // because Small is half-open at its maximum; 50 is Large; the outer bounds keep their status.
    const holed = L.clampSizesFromInfo({ panel_clamps: {
        small: { title: 'Small', min_gap_mm: 20, max_gap_mm: 45, slug: 'clamp+s' },
        large: { title: 'Large', min_gap_mm: 50, max_gap_mm: 95, slug: 'clamp+l' }
    } });
    const holedTable = [[19, 'below_min', null], [20, 'ok', 'small'], [44, 'ok', 'small'], [45, 'no_size', null],
        [47, 'no_size', null], [49, 'no_size', null], [50, 'ok', 'large'], [95, 'ok', 'large'], [96, 'above_max', null]];
    for (const [gap, status, sizeKey] of holedTable) {
        const r = L.clampForGap(gap, holed);
        t.check(r.status === status && (r.size ? r.size.key : null) === sizeKey && r.minGapMm === 20 && r.maxGapMm === 95,
            `config 20-45/50-95: clampForGap(${gap}) -> ${fmt(r)}`);
    }
    const rHole = L.clampResult({ selected: true, sizes: holed }, 46.6, false);
    t.check(rHole.status === 'no_size' && rHole.size === null && rHole.slug === '' && rHole.gapMm === 47,
        'clampResult in the hole 46.6 -> ' + fmt(rHole));

    // Three sizes, given unsorted: middle band exclusive at its max, last inclusive.
    const three = L.clampSizesFromInfo({ panel_clamps: {
        xl: { title: 'XL', min_gap_mm: 70, max_gap_mm: 120, slug: 'c+xl' },
        s: { title: 'S', min_gap_mm: 10, max_gap_mm: 30, slug: 'c+s' },
        m: { title: 'M', min_gap_mm: 30, max_gap_mm: 70, slug: 'c+m' }
    } });
    t.eq(toRealm(three.map((s) => s.key)), ['s', 'm', 'xl'], 'three-size list sorted by minGapMm');
    const threeTable = [[9, 'below_min', null], [10, 'ok', 's'], [29, 'ok', 's'], [30, 'ok', 'm'], [69, 'ok', 'm'],
        [70, 'ok', 'xl'], [120, 'ok', 'xl'], [121, 'above_max', null]];
    for (const [gap, status, sizeKey] of threeTable) {
        const r = L.clampForGap(gap, three);
        t.check(r.status === status && (r.size ? r.size.key : null) === sizeKey && r.minGapMm === 10 && r.maxGapMm === 120,
            `three sizes: clampForGap(${gap}) -> ${fmt(r)}`);
    }

    // Empty / missing size list falls back to the defaults.
    t.check(L.clampForGap(35, []).size.key === 'small', 'clampForGap([]) uses defaults');
    t.check(L.clampForGap(35, undefined).size.key === 'small', 'clampForGap(undefined) uses defaults');
});

// --------------------------------------------------------------------------------------------
// Block 2 - clampSizesFromInfo
// --------------------------------------------------------------------------------------------

block('2 clampSizesFromInfo', (t) => {
    const defaults = toRealm(L.clampDefaults());
    t.eq(defaults, [
        { key: 'small', title: 'Small', minGapMm: 20, maxGapMm: 50, slug: 'panel_to_panel_clamp+sm' },
        { key: 'large', title: 'Large', minGapMm: 50, maxGapMm: 95, slug: 'panel_to_panel_clamp+lg' }
    ], 'clampDefaults shape');
    t.check(L.clampDefaults() !== L.clampDefaults(), 'clampDefaults returns a fresh array each call');

    t.eq(toRealm(L.clampSizesFromInfo({})), defaults, 'missing panel_clamps -> defaults');
    t.eq(toRealm(L.clampSizesFromInfo(null)), defaults, 'null info -> defaults');
    t.eq(toRealm(L.clampSizesFromInfo(undefined)), defaults, 'undefined info -> defaults');
    t.eq(toRealm(L.clampSizesFromInfo({ panel_clamps: 'nope' })), defaults, 'non-object panel_clamps -> defaults');
    t.eq(toRealm(L.clampSizesFromInfo({ panel_clamps: {} })), defaults, 'empty panel_clamps -> defaults');

    // The real config file's shape (ints, sorted) round-trips as-is.
    const real = L.clampSizesFromInfo({ panel_clamps: {
        small: { title: 'Small', min_gap_mm: 20, max_gap_mm: 50, slug: 'panel_to_panel_clamp+sm' },
        large: { title: 'Large', min_gap_mm: 50, max_gap_mm: 95, slug: 'panel_to_panel_clamp+lg' }
    } });
    t.eq(toRealm(real), defaults, 'real config -> same as defaults');

    // Malformed entries are dropped, the valid ones survive.
    const mixed = L.clampSizesFromInfo({ panel_clamps: {
        blankMin: { title: 'A', min_gap_mm: '', max_gap_mm: 50, slug: 'a' },        // admin GUI emptied field
        nanMax: { title: 'B', min_gap_mm: 20, max_gap_mm: 'abc', slug: 'b' },
        inverted: { title: 'C', min_gap_mm: 60, max_gap_mm: 40, slug: 'c' },
        equal: { title: 'D', min_gap_mm: 40, max_gap_mm: 40, slug: 'd' },
        zeroMin: { title: 'E', min_gap_mm: 0, max_gap_mm: 40, slug: 'e' },
        blankSlug: { title: 'F', min_gap_mm: 20, max_gap_mm: 40, slug: '   ' },
        notObject: 'x',
        nullRow: null,
        ok2: { title: 'Two', min_gap_mm: '55', max_gap_mm: '95.5', slug: ' two ' }, // strings coerce, slug trimmed
        ok1: { title: '', min_gap_mm: 20, max_gap_mm: 55, slug: 'one' }             // blank title -> key
    } });
    t.eq(toRealm(mixed), [
        { key: 'ok1', title: 'ok1', minGapMm: 20, maxGapMm: 55, slug: 'one' },
        { key: 'ok2', title: 'Two', minGapMm: 55, maxGapMm: 95.5, slug: 'two' }
    ], 'malformed entries dropped, valid ones coerced and sorted');

    t.eq(toRealm(L.clampSizesFromInfo({ panel_clamps: {
        a: { min_gap_mm: '', max_gap_mm: 50, slug: 'a' },
        b: { min_gap_mm: 20, max_gap_mm: 50, slug: '' }
    } })), defaults, 'all-invalid -> defaults');

    const unsorted = L.clampSizesFromInfo({ panel_clamps: {
        large: { title: 'Large', min_gap_mm: 50, max_gap_mm: 95, slug: 'l' },
        small: { title: 'Small', min_gap_mm: 20, max_gap_mm: 50, slug: 's' }
    } });
    t.eq(toRealm(unsorted.map((s) => s.key)), ['small', 'large'], 'sorted by minGapMm regardless of object order');
});

// --------------------------------------------------------------------------------------------
// Block 3 - clampRequest
// --------------------------------------------------------------------------------------------

block('3 clampRequest', (t) => {
    const panelRow = (optVal, order) => {
        const settings = [
            { key: 'panel_option', val: '1400', tag: 'select', type: 'select_option' },
            { key: 'post_option', val: optVal, tag: 'div', type: 'image_option' }
        ];
        if (order === 'reversed') settings.reverse();
        return { id: 'glass_pool', control_key: 'panel_options_custom', settings };
    };
    const adjustRow = (val) => ({ id: 'glass_pool', control_key: 'panel_clamps', settings: [{ key: 'gap_adjust', val }] });
    const gateRow = { id: 'glass_pool', control_key: 'gate', settings: [{ key: 'gate_width', val: '890' }] };

    const shape = (r) => ({ selected: r.selected, enforce: r.enforce, minGapMm: r.minGapMm, maxGapMm: r.maxGapMm, n: r.sizes.length });

    t.eq(shape(L.clampRequest([panelRow('opt-1')], {})), { selected: false, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'opt-1 -> not selected');
    t.eq(shape(L.clampRequest([panelRow('opt-2')], {})), { selected: true, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'opt-2 without row -> selected, not enforced');
    t.eq(shape(L.clampRequest([panelRow('opt-2'), adjustRow('1')], {})), { selected: true, enforce: true, minGapMm: 20, maxGapMm: 95, n: 2 }, "opt-2 + row val '1' -> enforced");
    t.eq(shape(L.clampRequest([adjustRow(1), gateRow, panelRow('opt-2')], {})), { selected: true, enforce: true, minGapMm: 20, maxGapMm: 95, n: 2 }, 'opt-2 + row val 1 (number), any row order -> enforced');
    t.eq(shape(L.clampRequest([panelRow('opt-2'), adjustRow('0')], {})), { selected: true, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, "row val '0' -> not enforced");
    t.eq(shape(L.clampRequest([panelRow('opt-2')], {}, { forceEnforce: true })), { selected: true, enforce: true, minGapMm: 20, maxGapMm: 95, n: 2 }, 'opt-2 + forceEnforce -> enforced');
    t.eq(shape(L.clampRequest([panelRow('opt-2')], {}, { forceEnforce: 'yes' })), { selected: true, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'forceEnforce must be === true');
    t.eq(shape(L.clampRequest([panelRow('opt-1'), adjustRow('1')], {}, { forceEnforce: true })), { selected: false, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'opt-1 never enforces, even with row + forceEnforce');
    t.eq(shape(L.clampRequest([gateRow, adjustRow('1')], {})), { selected: false, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'missing panel_options_custom control -> not selected');
    t.eq(shape(L.clampRequest([panelRow('opt-2', 'reversed')], {})), { selected: true, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'settings order [post_option, panel_option] read by key');
    t.eq(shape(L.clampRequest([panelRow('opt-2', 'reversed'), adjustRow('1')], {})), { selected: true, enforce: true, minGapMm: 20, maxGapMm: 95, n: 2 }, 'reversed order + row -> enforced');
    t.eq(shape(L.clampRequest(undefined, {})), { selected: false, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'undefined customFence');
    t.eq(shape(L.clampRequest([], {})), { selected: false, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'empty customFence');
    t.eq(shape(L.clampRequest('junk', {})), { selected: false, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'non-array customFence');
    t.eq(shape(L.clampRequest([panelRow('opt-2'), { control_key: 'panel_clamps' }, null], {})), { selected: true, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'rows without settings / null rows are ignored');
    t.eq(shape(L.clampRequest([panelRow('opt-2')], null)), { selected: true, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'null info -> default sizes');

    // A settings entry with the wrong key but the right value must not count.
    const wrongKey = { id: 'glass_pool', control_key: 'panel_options_custom', settings: [{ key: 'panel_option', val: 'opt-2' }] };
    t.eq(shape(L.clampRequest([wrongKey], {})), { selected: false, enforce: false, minGapMm: 20, maxGapMm: 95, n: 2 }, 'opt-2 under the wrong key -> not selected');

    // Config ranges flow through to the request.
    const req = L.clampRequest([panelRow('opt-2'), adjustRow('1')], { panel_clamps: {
        large: { title: 'Large', min_gap_mm: 60, max_gap_mm: 90, slug: 'l' },
        small: { title: 'Small', min_gap_mm: 25, max_gap_mm: 60, slug: 's' }
    } });
    t.eq(shape(req), { selected: true, enforce: true, minGapMm: 25, maxGapMm: 90, n: 2 }, 'config ranges -> request min/max');
    t.eq(toRealm(req.sizes.map((s) => s.slug)), ['s', 'l'], 'request sizes sorted');
});

// --------------------------------------------------------------------------------------------
// Block 4 - non-enforced output is byte-identical to the pinned baseline
// --------------------------------------------------------------------------------------------

block('4 non-enforced regression vs ' + BASELINE_REV, (t) => {
    const s = runSweep();
    t.note(`${s.cases.length} cases (sweep incl. enforced run: ${s.ms}ms)`);
    let solved = 0;
    let identical = 0;
    let fewer = 0;
    let panelsSaved = 0;
    for (const row of s.rows) {
        const label = caseLabel(row.c);
        const liveOut = toRealm(row.live);
        const baseOut = toRealm(row.base);
        t.check(liveOut.clamp && liveOut.clamp.status === 'none' && liveOut.clamp.selected === false,
            `live result carries an inert clamp: ${label} -> ${fmt(liveOut.clamp)}`);
        delete liveOut.clamp;
        if (row.base.msg === '') solved++;

        // The fewest-panel search applies to every glass solve now (owner's call, Sept 2026), so
        // this is no longer byte-identity. What must hold: a length the baseline could build is
        // still buildable, the layout never gains panels, and any layout that DID change is a
        // valid fence - inside the customer's Max Panel Spacing (still a hard cap without
        // clamps), inside the 80mm ceiling, and closing to the ordered length.
        if (row.base.msg === '') {
            t.check(row.live.msg === '', `baseline solved but live failed: ${label} -> ${row.live.msg}`);
        }
        const livePanels = row.live.long_panel_count + row.live.short_panel_count;
        const basePanels = row.base.long_panel_count + row.base.short_panel_count;

        let same = true;
        try {
            assert.deepStrictEqual(liveOut, baseOut);
        } catch (e) {
            same = false;
        }
        if (same) {
            identical++;
            continue;
        }
        if (row.base.msg !== '' || row.live.msg !== '') continue;

        fewer++;
        panelsSaved += basePanels - livePanels;
        // A layout may change for exactly two reasons: it dropped a panel, or it kept the same
        // count and moved CLOSER to the Max Panel Spacing the customer set (3200mm at 50 was
        // three 1050s @ 12.5 when three 1000s hit the 50 exactly).
        const nearer = Math.abs(row.live.spacing_width - row.c.pg) < Math.abs(row.base.spacing_width - row.c.pg);
        t.check(livePanels < basePanels || (livePanels === basePanels && nearer),
            `changed layout must use FEWER panels, or the same count nearer the ${row.c.pg}mm setting: ${label} -> base ${fmt(stripClamp(row.base))} vs live ${fmt(stripClamp(row.live))}`);
        t.check(row.live.spacing_width > 0 && row.live.spacing_width <= row.c.pg + 1e-9,
            `changed layout must stay inside Max Panel Spacing ${row.c.pg}: ${label} -> gap ${row.live.spacing_width}`);
        t.check(row.live.spacing_width <= ENFORCED_MAX_GAP + 1e-9,
            `changed layout must stay inside the ${ENFORCED_MAX_GAP}mm ceiling: ${label} -> gap ${row.live.spacing_width}`);
        t.check(row.live.long_panel_length >= 200 && row.live.long_panel_length <= row.c.dps,
            `changed layout keeps a buildable panel width: ${label} -> ${row.live.long_panel_length}mm`);
    }
    t.note(`${solved} of ${s.cases.length} cases solve at baseline`);
    t.note(`${identical} identical to baseline, ${fewer} improved (${panelsSaved} panels removed in total)`);
});

// --------------------------------------------------------------------------------------------
// Block 5 - enforcement keeps every junction gap inside the clamp range
// --------------------------------------------------------------------------------------------

// The request carries the Large clamp's 95 maximum, but the widened pass stops at the planner's
// 80 mm spacing ceiling (maxPanelSpacing), so the enforced upper bound is min(95, 80) = 80.
const ENFORCED_MAX_GAP = 80;

block('5 enforcement (min 20 / max 95, ceiling 80)', (t) => {
    const s = runSweep();
    let solved = 0;
    let junction = 0;
    let small = 0;
    let large = 0;
    let failed = 0;
    for (const row of s.rows) {
        const e = row.enforced;
        const label = caseLabel(row.c);
        if (e.msg !== '') {
            failed++;
            t.check(e.clamp && e.clamp.status === 'invalid', `failed solve must classify invalid: ${label} -> ${fmt(toRealm(e.clamp))}`);
            t.check(/minimum gap for panel-to-panel clamps|No solution found that satisfies|Closest working lengths|No initial panel solutions/.test(e.msg),
                `unexpected failure text: ${label} -> ${e.msg}`);
            continue;
        }
        solved++;
        if (!hasJunction(e)) continue;
        junction++;

        const rounded = Math.round(e.spacing_width);
        t.check(rounded >= 20 && rounded <= ENFORCED_MAX_GAP, `junction gap out of range: ${label} -> gap ${e.spacing_width} (${fmt(stripClamp(e))})`);
        t.check(e.clamp && e.clamp.status === 'ok', `clamp status not ok: ${label} -> ${fmt(toRealm(e.clamp))}`);
        t.check(e.clamp && e.clamp.enforce === true && e.clamp.selected === true, `clamp flags: ${label} -> ${fmt(toRealm(e.clamp))}`);
        const wantSlug = rounded < 50 ? 'panel_to_panel_clamp+sm' : 'panel_to_panel_clamp+lg';
        t.check(e.clamp && e.clamp.slug === wantSlug, `clamp slug for gap ${rounded}: ${label} -> ${e.clamp && e.clamp.slug}`);
        t.check(e.clamp && e.clamp.gapMm === rounded, `clamp.gapMm mirrors Math.round(spacing_width): ${label}`);
        if (rounded < 50) small++; else large++;

        // Geometry closes: re-run the solver on the mirrored config and add it up.
        const cfg = buildConfig(row.c, { minPanelGap: 20, clampMaxGapMm: 95 });
        const plan = live.calculateGlassFencing(cfg);
        t.check(!!(plan && plan.results), `re-run has results: ${label}`);
        if (!plan || !plan.results) continue;
        const r = plan.results;
        t.check(Math.abs(r.panelGapsValue - e.spacing_width) < 1e-9
            && r.longPanels.count === e.long_panel_count && r.longPanels.size === e.long_panel_length,
            `mirrored config disagrees with calculatePanels: ${label} -> ${fmt(toRealm(r))} vs ${fmt(stripClamp(e))}`);
        const sum = geometry(cfg, r);
        t.check(Math.abs(sum - row.c.overall) <= 0.5, `geometry does not close: ${label} -> sum ${sum} (${fmt(toRealm(r))})`);
    }
    t.note(`${solved} solved, ${failed} failed, ${junction} junction layouts checked (${small} Small / ${large} Large)`);
});

// --------------------------------------------------------------------------------------------
// Block 6 - enforcement never disturbs a layout that was already in range
// --------------------------------------------------------------------------------------------

block('6 enforcement leaves good layouts alone', (t) => {
    const s = runSweep();
    let good = 0;
    let low = 0;
    let lowNoJunction = 0;
    let a = 0;
    let aPreempted = 0;
    let b = 0;
    let c = 0;
    for (const row of s.rows) {
        if (row.base.msg !== '') continue;
        const label = caseLabel(row.c);
        const rounded = Math.round(row.base.spacing_width);
        if (rounded >= 20) {
            good++;
            // The baseline was already clamp-legal, so enforcement must not make it worse. It
            // may still improve it: the fewest-panel rule applies to every solve now, so an
            // enforced run can drop a panel here exactly as an unenforced one would. What is
            // pinned is that it never GAINS panels and never leaves the clamp range.
            const basePanels = row.base.long_panel_count + row.base.short_panel_count;
            const enfPanels = row.enforced.long_panel_count + row.enforced.short_panel_count;
            t.check(row.enforced.msg === '', `enforced must still solve: ${label} -> ${row.enforced.msg}`);
            t.check(enfPanels <= basePanels,
                `enforced gained panels over a clamp-legal baseline: ${label} -> base ${fmt(stripClamp(row.base))} vs enforced ${fmt(stripClamp(row.enforced))}`);
            if (row.enforced.msg === '' && hasJunction(row.enforced)) {
                const enfGap = Math.round(row.enforced.spacing_width);
                t.check(enfGap >= 20 && enfGap <= ENFORCED_MAX_GAP,
                    `enforced stays in the clamp range: ${label} -> gap ${row.enforced.spacing_width}`);
            }
            continue;
        }
        low++;
        if (!hasJunction(row.base)) lowNoJunction++;
        if (row.enforced.msg !== '') {
            c++;
            t.check(/minimum gap for panel-to-panel clamps|No solution found that satisfies|Closest working lengths|No initial panel solutions/.test(row.enforced.msg),
                `enforced failure text: ${label} -> ${row.enforced.msg}`);
            continue;
        }
        // Strict-only re-run: the minimum with no fewest-panel search. Solves -> (a), else the
        // enforced solve came from that search -> (b). Within (a) the two may legitimately
        // differ: once the minimum has rejected the natural layout the fence is changing anyway,
        // and the enforced run then minimises panel count (owner's rule, Sept 2026). So a
        // difference is only allowed to go one way - the enforced layout must never use MORE
        // panels than the strict-only one.
        const strict = live.calculateGlassFencing(buildConfig(row.c, { minPanelGap: 20 }));
        if (strict && strict.results) {
            a++;
            const same = Math.abs(strict.results.panelGapsValue - row.enforced.spacing_width) < 1e-9
                && strict.results.longPanels.count === row.enforced.long_panel_count;
            if (!same) {
                aPreempted++;
                // Two legitimate ways to differ: strict-only fell to the dynamic-end rescue (a
                // single panel - a fallback, not a fence anyone chose), or the fewest-panel
                // search improved on it. What must never happen is the search making it worse.
                t.check(
                    !!strict.calculationDetails.dynamicEndRescue
                        || row.enforced.long_panel_count + row.enforced.short_panel_count
                            <= strict.results.longPanels.count + strict.results.shortPanel.count,
                    `enforced uses MORE panels than strict-only: ${label} -> strict ${fmt(toRealm(strict.results))} vs enforced ${fmt(stripClamp(row.enforced))}`);
            }
        } else {
            b++;
        }
    }
    t.note(`${good} baseline solves with rounded gap >= 20 unchanged under enforcement`);
    t.note(`${low} baseline solves with rounded gap < 20 (${lowNoJunction} of them with no junction): (a) ${a} re-solve strict, (b) ${b} re-solve via the fewest-panel search, (c) ${c} fail`);
    t.note(`    of (a), ${aPreempted} were improved by the fewest-panel search (never more panels than strict-only)`);
});

// --------------------------------------------------------------------------------------------
// Block 7 - runs with nothing to clamp are untouched under enforcement
// --------------------------------------------------------------------------------------------

block('7 no-junction runs untouched', (t) => {
    for (const overall of [1745, 1747]) {
        for (const dps of SWEEP.dps) {
            const c = { overall, dps, pg: 30, left: -1, right: -1, gate: true };
            const baseOut = B.calculatePanels(makeInput(c, NOT_SELECTED));
            const enf = L.calculatePanels(makeInput(c, enforcedRequest()));
            const label = caseLabel(c);
            t.check(baseOut.msg === '', `baseline solves: ${label} -> ${baseOut.msg}`);
            t.check(baseOut.long_panel_count === 0 && baseOut.short_panel_count === 0, `zero regular panels: ${label}`);
            const expectGap = (overall - (GATE_WIDTH + HINGE_PANEL + 19)) / 2;
            t.check(Math.abs(baseOut.spacing_width - expectGap) < 1e-9, `end gaps ${expectGap}: ${label} -> ${baseOut.spacing_width}`);
            t.check(Math.round(baseOut.spacing_width) < 20, `below the clamp minimum: ${label}`);
            t.eq(stripClamp(enf), toRealm(baseOut), `enforced output must equal baseline: ${label}`);
            t.check(enf.clamp.status === 'below_min' && enf.clamp.enforce === true, `clamp classifies below_min (junction gating is the consumers' job): ${label} -> ${fmt(toRealm(enf.clamp))}`);
        }
    }
});

// --------------------------------------------------------------------------------------------
// Block 8 - the widened pass keeps the Overall Length
// --------------------------------------------------------------------------------------------

block('8 widened pass example (937mm)', (t) => {
    const c = { overall: 937, dps: 1400, pg: 30, left: 0, right: 0, gate: false };
    const baseOut = B.calculatePanels(makeInput(c, NOT_SELECTED));
    t.check(baseOut.msg === '' && baseOut.long_panel_count === 3 && baseOut.long_panel_length === 300 && Math.abs(baseOut.spacing_width - 18.5) < 1e-9,
        `baseline 3x300 @ 18.5: ${fmt(toRealm(baseOut))}`);

    const enf = L.calculatePanels(makeInput(c, enforcedRequest()));
    const rounded = Math.round(enf.spacing_width);
    t.check(enf.msg === '', `enforced must solve at 937: ${enf.msg}`);
    t.check(rounded >= 20 && rounded <= 95, `enforced gap in [20,95]: ${enf.spacing_width}`);
    t.check(enf.clamp.status === 'ok' && enf.clamp.slug !== '', `clamp ok: ${fmt(toRealm(enf.clamp))}`);
    t.check(enf.closest_lengths === null, 'no auto-fit lengths on a solve');
    const total = enf.long_panel_count * enf.long_panel_length + Math.max(0, enf.long_panel_count - 1) * enf.spacing_width;
    t.check(Math.abs(total - 937) <= 0.5, `layout fills 937: ${total}`);
    t.note(`enforced: ${enf.long_panel_count} x ${enf.long_panel_length} @ ${enf.spacing_width}mm -> ${enf.clamp.size ? enf.clamp.size.title : '?'}`);

    // Strict-only (no widened pass) has nothing at 937 - that is what the widened pass is for.
    const strict = live.calculateGlassFencing(buildConfig(c, { minPanelGap: 20 }));
    t.check(!(strict && strict.results), 'strict-only pass finds nothing at 937');
    t.check(/20mm minimum gap for panel-to-panel clamps/.test(strict.calculationDetails.error),
        `strict failure names the minimum: ${strict.calculationDetails.error}`);

    // And a non-enforced request with the clamp SELECTED still returns the baseline layout,
    // classified below_min (the prompt path), not a re-solve.
    const sel = L.calculatePanels(makeInput(c, { selected: true, enforce: false, sizes: L.clampDefaults(), minGapMm: 20, maxGapMm: 95 }));
    t.eq(stripClamp(sel), toRealm(baseOut), 'selected-but-not-enforced equals baseline');
    t.check(sel.clamp.status === 'below_min' && sel.clamp.gapMm === 19 && sel.clamp.minGapMm === 20,
        `selected-but-not-enforced classifies below_min: ${fmt(toRealm(sel.clamp))}`);
});

// --------------------------------------------------------------------------------------------
// Block 9 - the widened pass picks the FEWEST panels, under the 80 ceiling. Every panel the
// adjustment adds costs two spigots and a clamp while the glass is priced per m2 either way, so
// panel count is what the customer pays for (owner's call, Sept 2026).
// --------------------------------------------------------------------------------------------

block('9 widened pass picks the fewest panels (3527mm)', (t) => {
    const c = { overall: 3527, dps: 1400, pg: 30, left: 0, right: 25, gate: true };
    const baseOut = B.calculatePanels(makeInput(c, NOT_SELECTED));
    t.check(baseOut.msg === '' && hasJunction(baseOut) && Math.round(baseOut.spacing_width) < 20,
        `baseline is a junction layout below the minimum: ${fmt(toRealm(baseOut))}`);

    // Every widened candidate, brute-forced the way the solver builds them for a both-ends-fixed
    // run with a gate + hinge panel: n equal panels of (lengthToFill - (n-1)*pg)/n, each within
    // [200, 2000] and <= dps, kept while the unrounded gap is <= the ceiling, then rounded to 50
    // and the real gap re-derived over the n-1 panel-to-panel junctions (hinge|gate and
    // gate|panel carry the 10/9 gate gaps, both ends are fixed widths).
    const fixedTotal = GATE_WIDTH + HINGE_PANEL + 19;
    const lengthToFill = c.overall - c.left - c.right - fixedTotal - c.pg;
    const candidates = [];
    for (let n = 2; n <= 40; n++) {
        const size = (lengthToFill - (n - 1) * c.pg) / n;
        if (size < 200 || size > 2000 || size > c.dps) continue;
        const gapValue = (c.overall - fixedTotal - n * size) / (n - 1);
        if (!(gapValue > 0 && gapValue <= ENFORCED_MAX_GAP)) continue;
        const rounded = Math.round(size / 50) * 50;
        const gap = (c.overall - fixedTotal - c.left - c.right - n * rounded) / (n - 1);
        if (gap > 0 && gap <= ENFORCED_MAX_GAP && Math.round(gap) >= 20) candidates.push({ n, size: rounded, gap });
    }
    t.check(candidates.length >= 3, `several widened candidates qualify: ${fmt(candidates)}`);
    t.check(!candidates.some((k) => k.n === 2 && k.size === 850), `2x850 @ 93 is over the ${ENFORCED_MAX_GAP} ceiling: ${fmt(candidates)}`);
    const fewestPanels = Math.min(...candidates.map((k) => k.n));

    const enf = L.calculatePanels(makeInput(c, enforcedRequest()));
    t.check(enf.msg === '', `enforced must solve at 3527: ${enf.msg}`);
    const rounded = Math.round(enf.spacing_width);
    t.check(rounded >= 20 && rounded <= ENFORCED_MAX_GAP, `enforced gap in [20,${ENFORCED_MAX_GAP}]: ${enf.spacing_width}`);
    t.check(!(enf.long_panel_count === 2 && enf.long_panel_length === 850),
        `must not exceed the ${ENFORCED_MAX_GAP}mm ceiling (2x850 @ 93): ${fmt(stripClamp(enf))}`);
    t.check(enf.long_panel_count === fewestPanels,
        `enforced ${enf.long_panel_count} panels is not the fewest (${fewestPanels}) among ${fmt(candidates)}`);
    t.check(candidates.some((k) => k.n === enf.long_panel_count && k.size === enf.long_panel_length && Math.abs(k.gap - enf.spacing_width) < 1e-9),
        `enforced layout is one of the brute-forced candidates: ${fmt(stripClamp(enf))}`);
    t.check(enf.clamp.status === 'ok' && enf.clamp.slug !== '', `clamp ok: ${fmt(toRealm(enf.clamp))}`);
    const total = c.left + c.right + fixedTotal + enf.long_panel_count * enf.long_panel_length + (enf.long_panel_count - 1) * enf.spacing_width;
    t.check(Math.abs(total - c.overall) <= 0.5, `layout fills 3527: ${total}`);
    t.note(`enforced: ${enf.long_panel_count} x ${enf.long_panel_length} @ ${enf.spacing_width}mm -> ${enf.clamp.size ? enf.clamp.size.title : '?'}`
        + `; candidates ${candidates.map((k) => `${k.n}x${k.size}@${Math.round(k.gap * 10) / 10}`).join(', ')}`);

    // The mirrored config agrees, and a 95 clamp maximum still stops at the 80 spacing ceiling.
    const plan = live.calculateGlassFencing(buildConfig(c, { minPanelGap: 20, clampMaxGapMm: 95 }));
    t.check(!!(plan && plan.results) && Math.abs(plan.results.panelGapsValue - enf.spacing_width) < 1e-9
        && plan.results.longPanels.count === enf.long_panel_count,
        `mirrored config agrees: ${fmt(toRealm(plan && plan.results))}`);
});

// --------------------------------------------------------------------------------------------
// Block 10 - a layout with no panel-to-panel junction is not subject to the minimum
// --------------------------------------------------------------------------------------------

block('10 no-junction ignores the minimum (1237mm)', (t) => {
    // One 1200 panel between a 12 mm dynamic end gap and a 25 fixed end: nothing meets
    // glass-to-glass, so there is nothing to clamp and the minimum must not throw the layout
    // out (it used to become five 200 panels @ 42.4 through the widened pass). The single
    // panel only exists while the default panel size is above 1200 - at 1200 the solver never
    // generates n=1 for this length, see the tail of this block.
    for (const dps of [1400, 2000]) {
        const c = { overall: 1237, dps, pg: 30, left: -1, right: 25, gate: false };
        const label = caseLabel(c);
        const baseOut = B.calculatePanels(makeInput(c, NOT_SELECTED));
        t.check(baseOut.msg === '' && baseOut.long_panel_count === 1 && baseOut.long_panel_length === 1200 && Math.abs(baseOut.spacing_width - 12) < 1e-9,
            `baseline 1x1200 @ 12: ${label} -> ${fmt(toRealm(baseOut))}`);
        t.check(!hasJunction(baseOut), `no junction: ${label}`);
        const enf = L.calculatePanels(makeInput(c, enforcedRequest()));
        t.eq(stripClamp(enf), toRealm(baseOut), `enforced must equal baseline: ${label}`);
        t.check(enf.clamp.status === 'below_min' && enf.clamp.enforce === true && enf.clamp.gapMm === 12,
            `clamp classifies below_min (junction gating is the consumers' job): ${label} -> ${fmt(toRealm(enf.clamp))}`);
        // Strict-only agrees, and from the main loop - not the rescue - so the minimum never fired.
        const strict = live.calculateGlassFencing(buildConfig(c, { minPanelGap: 20 }));
        t.check(!!(strict && strict.results) && strict.results.longPanels.count === 1 && strict.results.longPanels.size === 1200
            && !strict.calculationDetails.dynamicEndRescue,
            `strict-only keeps the single panel from the main loop: ${label} -> ${fmt(toRealm(strict && strict.results))}`);
    }

    // With the default panel size at 1200 the same length is 2 x 600 @ 6 - a junction - and
    // enforcement legitimately re-solves it inside the clamp range.
    const c1200 = { overall: 1237, dps: 1200, pg: 30, left: -1, right: 25, gate: false };
    const b1200 = B.calculatePanels(makeInput(c1200, NOT_SELECTED));
    t.check(b1200.long_panel_count === 2 && b1200.long_panel_length === 600 && Math.abs(b1200.spacing_width - 6) < 1e-9,
        `dps 1200 baseline 2x600 @ 6: ${fmt(toRealm(b1200))}`);
    const e1200 = L.calculatePanels(makeInput(c1200, enforcedRequest()));
    t.check(e1200.msg === '' && hasJunction(e1200) && Math.round(e1200.spacing_width) >= 20 && Math.round(e1200.spacing_width) <= ENFORCED_MAX_GAP,
        `dps 1200 enforced re-solves inside the clamp range: ${fmt(stripClamp(e1200))}`);
    t.note(`dps 1200: baseline 2 x 600 @ 6 -> enforced ${e1200.long_panel_count} x ${e1200.long_panel_length} @ ${e1200.spacing_width}mm`);
});

// --------------------------------------------------------------------------------------------
// Run
// --------------------------------------------------------------------------------------------

const results = [];
for (const { name, fn } of blocks) {
    const t = new Block(name);
    const t0 = Date.now();
    try {
        fn(t);
    } catch (e) {
        t.fail('threw: ' + (e && e.stack ? e.stack : e));
    }
    t.ms = Date.now() - t0;
    results.push(t);
}

const pad = (s, n) => String(s).padEnd(n);
console.log('');
console.log(pad('Block', 48) + pad('Cases', 9) + pad('Failed', 9) + pad('Time', 9) + 'Status');
console.log('-'.repeat(84));
let totalFailures = 0;
for (const r of results) {
    totalFailures += r.failures;
    console.log(pad(r.name, 48) + pad(r.cases, 9) + pad(r.failures, 9) + pad(r.ms + 'ms', 9) + (r.failures ? 'FAIL' : 'ok'));
    for (const n of r.notes) console.log('    ' + n);
    for (const d of r.details) console.log('    x ' + d);
    if (r.failures > r.details.length) console.log(`    ... ${r.failures - r.details.length} more`);
}
console.log('-'.repeat(84));
console.log(totalFailures ? `${totalFailures} failure(s)` : 'all blocks passed');
process.exit(totalFailures ? 1 : 0);
