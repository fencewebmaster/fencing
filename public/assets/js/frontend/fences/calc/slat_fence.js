/**
 * Slat / Slat Infill calculation module.
 *
 * The calculation half of SlatFence — config readers, pitch/row/panel-height math, panel
 * geometry and cross-section pooling — split out of fences/slat_fence.js so quantity and
 * geometry logic can change without touching the DOM/cart wiring (and vice versa).
 *
 * Loaded by fence-scripts.php AFTER the main fences/*.js glob and attached onto SlatFence
 * below, so every existing `SlatFence.x()` / `this.x()` call site keeps working unchanged
 * and `this` still resolves to SlatFence inside these methods.
 *
 * Nothing here may render or write to the DOM. Allowed inputs: fc_data, the fence config,
 * FENCE.* readers, localStorage via the SlatFence storage readers, and the other methods
 * attached alongside.
 */

SlatFenceCalc = {

    /**
     * Number of packs to order: ceil(requiredPieces / itemsPerPack). Minimum 1 when requiredPieces > 0.
     * Use for any pack-sized material (spacers, future stock with pack_qty in fc_data).
     */
    ceilPackQuantity: function(requiredPieces, itemsPerPack) {
        var r = typeof requiredPieces === 'number' ? requiredPieces : parseFloat(requiredPieces);
        if (!Number.isFinite(r) || r <= 0) return 0;
        var n = parseInt(itemsPerPack, 10);
        if (!Number.isFinite(n) || n <= 0) {
            n = 50;
        }
        return Math.max(1, Math.ceil(r / n));
    },

    /**
     * Items per pack for a catalog slug (e.g. slat_spacer+20) from fc_data[fence].pack_qty, then settings.pack_qty_defaults.
     */
    getPackItemsForSlug: function(fenceSlug, materialSlug) {
        var raw = String(fenceSlug || '');
        var canon =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : raw;
        if (canon === 'slat_fence') {
            canon = 'slat';
        }
        var map =
            typeof fc_data !== 'undefined' && fc_data[canon] && fc_data[canon].pack_qty
                ? fc_data[canon].pack_qty
                : null;
        if (map && materialSlug != null && map[materialSlug] != null && map[materialSlug] !== '') {
            var fromData = parseInt(map[materialSlug], 10);
            if (Number.isFinite(fromData) && fromData > 0) {
                return fromData;
            }
        }
        var defMap = this.settings.pack_qty_defaults || {};
        if (materialSlug != null && defMap[materialSlug] != null) {
            var d = parseInt(defMap[materialSlug], 10);
            if (Number.isFinite(d) && d > 0) {
                return d;
            }
        }
        return 50;
    },

    /**
     * Numeric FSQ-parity value from the fence's own writable/fences config (via fc_data):
     * panel_end_allowance_mm, slat_length_deduction_mm, sfs_length_deduction_mm,
     * spacers_per_slat. Slat and Slat Infill each carry their own copy; keys shared by the
     * geometry helpers must be kept equal in both files, so lookup falls through
     * slat → slat_fence_infill for callers with no section context. parseFloat, never
     * parseInt — gap pitches are floats (9.3 / 21.1) and would silently truncate.
     */
    getSlatConfigNumber: function(fenceSlug, key, fallback) {
        var raw = String(fenceSlug || '');
        var canon =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : raw;
        if (canon === 'slat_fence') {
            canon = 'slat';
        }
        var candidates = [canon, 'slat', 'slat_fence_infill'];
        for (var i = 0; i < candidates.length; i++) {
            var c = candidates[i];
            if (!c || typeof fc_data === 'undefined' || !fc_data[c]) continue;
            var v = parseFloat(fc_data[c][key]);
            if (Number.isFinite(v)) {
                return v;
            }
        }
        return fallback;
    },

    /** slat_gap_pitch_mm[gapSlug] from config: the F80/F81 pitch (9 → 9.3, 20 → 21.1). */
    getSlatGapPitchConfigMm: function(fenceSlug, gapSlug) {
        if (gapSlug === undefined || gapSlug === null) return null;
        var keyRaw = String(gapSlug).trim();
        if (keyRaw === '') return null;
        var raw = String(fenceSlug || '');
        var canon =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : raw;
        if (canon === 'slat_fence') {
            canon = 'slat';
        }
        var candidates = [canon, 'slat', 'slat_fence_infill'];
        for (var i = 0; i < candidates.length; i++) {
            var c = candidates[i];
            var map =
                c && typeof fc_data !== 'undefined' && fc_data[c] && fc_data[c].slat_gap_pitch_mm
                    ? fc_data[c].slat_gap_pitch_mm
                    : null;
            if (!map || map[keyRaw] === undefined || map[keyRaw] === null || map[keyRaw] === '') {
                continue;
            }
            var v = parseFloat(map[keyRaw]);
            if (Number.isFinite(v) && v >= 0) {
                return v;
            }
        }
        return null;
    },

    /** Cut deduction per slat: panel width − this = slat length (FSQ F83/F215 = 16 mm). */
    getSlatLengthDeductionMm: function(fenceSlug) {
        return this.getSlatConfigNumber(fenceSlug, 'slat_length_deduction_mm', 16);
    },

    /** SFS side-frame cut length = panel height − this (FSQ F86/F238 = 6 mm). */
    getSfsLengthDeductionMm: function(fenceSlug) {
        return this.getSlatConfigNumber(fenceSlug, 'sfs_length_deduction_mm', 6);
    },

    /** Spacers consumed per slat (FSQ F235/F522 multiply rows × panels × 2). */
    getSpacersPerSlat: function(fenceSlug) {
        var n = this.getSlatConfigNumber(fenceSlug, 'spacers_per_slat', 2);
        return Number.isFinite(n) && n > 0 ? n : 2;
    },

    /**
     * Decimal places the cut-slat metre subtotal is rounded to before the cart line rounds it
     * to a whole number — see slat_metre_rounding_dp in the fence config. Read from this
     * style's own config only: the two styles legitimately differ (fence 1 dp, infill 0 dp),
     * so the usual slat → infill fallback would import the wrong precision.
     */
    getSlatMetreRoundingDp: function(fenceSlug) {
        var raw = String(fenceSlug || '');
        var canon =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : raw;
        if (canon === 'slat_fence') {
            canon = 'slat';
        }
        if (canon && typeof fc_data !== 'undefined' && fc_data[canon]) {
            var v = parseInt(fc_data[canon].slat_metre_rounding_dp, 10);
            if (Number.isFinite(v)) {
                return v;
            }
        }
        return canon === 'slat_fence_infill' ? 0 : 1;
    },

    /**
     * Round metres the way the old planner's form does: every one of its mathematical fields
     * renders at a set precision and the formulas downstream re-read that DISPLAYED text, so a
     * subtotal is already rounded once before the cart line rounds it again. toFixed is what
     * the legacy form itself uses, so its float edge cases are reproduced rather than avoided.
     * A negative dp skips the intermediate step and rounds once from the exact value.
     */
    applySlatMetreRoundingDp: function(metres, dp) {
        var m = Number(metres);
        if (!Number.isFinite(m)) return 0;
        if (!Number.isFinite(dp) || dp < 0) return m;
        return parseFloat(m.toFixed(dp));
    },

    // Whole metres billed for a style's cut line. Slat Infill rounds up (Nov 2026): to the nearest, a low single opening
    // billed no side frame at all. Main Slat keeps the old form's rounding, `formMetres` being its figure.
    slatBilledMetres: function(styleNorm, exactMm, formMetres) {
        if (styleNorm === 'slat_fence_infill') {
            return exactMm > 0 ? Math.ceil(exactMm / 1000 - 1e-9) : 0;
        }
        return Math.round(formMetres);
    },

    /**
     * Split `total` packs across sections in proportion to their slat counts (largest remainder), so
     * the per-section lines still add up to exactly `total`. Ties go to the lower section index, so a
     * job needing 1 pack puts it on the first section.
     */
    allocatePooledPacks: function(weights, total) {
        var out = [];
        var i;
        for (i = 0; i < weights.length; i++) out.push(0);

        var sum = 0;
        for (i = 0; i < weights.length; i++) sum += weights[i];
        if (!(total > 0) || !(sum > 0)) return out;

        var remainders = [];
        var used = 0;
        for (i = 0; i < weights.length; i++) {
            var exact = (total * weights[i]) / sum;
            var base = Math.floor(exact);
            out[i] = base;
            used += base;
            remainders.push({ i: i, frac: exact - base });
        }
        remainders.sort(function(a, b) {
            return b.frac - a.frac || a.i - b.i;
        });
        for (var k = 0; used < total && k < remainders.length; k++, used++) {
            out[remainders[k].i] += 1;
        }
        return out;
    },

    /**
     * Spacers are a job-level consumable, so the CEILING is taken once over the spacer count
     * (slats × spacers_per_slat — FSQ F235/F522 consume two per slat) pooled across every
     * section sharing this spacer size — not per section, which never ordered fewer than one
     * pack per section (4 sections × 8 slats ordered 4 packs, not 1).
     *
     * The pool spans Slat AND Slat Infill sections, because the spacer line is one job-wide
     * ceiling in FSQ too: F580/F581 = ceil(F417 + F235 + F522), where F235 is the fence term
     * and F522 the infill term. Pooling per style instead ceils twice and orders a pack too
     * many whenever both parts have a fractional remainder — measured on 46% of mixed
     * fence+infill jobs. Since Nov 2026 the line is the QuickScreen spacer and takes the gates'
     * spacers too. Sections only share a pool when they also order the same pack size,
     * since one ceiling across different pack sizes would be meaningless.
     *
     * Returns this section's share of the pooled packs, or null when the pool cannot be resolved
     * (single-section planner, storage not written yet) so the caller can fall back to per-section.
     */
    pooledSlatSpacerPacksForSection: function(sectionIndex, styleNorm, gapKey, itemsPerPack) {
        if (!Number.isFinite(sectionIndex) || typeof fcGetPersistedFenceSectionCount !== 'function') {
            return null;
        }
        var count = fcGetPersistedFenceSectionCount();
        if (!Number.isFinite(count) || count < 1) return null;

        var spacerSlug = 'slat_gate+qsg_spacer_' + gapKey;
        var indexes = [];
        var weights = [];
        var totals = { fence: 0, infill: 0, gateSpacers: 0 };
        for (var i = 0; i < count; i++) {
            var s = this.slatSectionSlatCountFromStorage(i);
            if (!this.isSlatLike(s.style) || s.gapKey !== gapKey) continue;
            if (this.getPackItemsForSlug(s.style, spacerSlug) !== itemsPerPack) continue;
            // Weigh by spacers, not slats, so a style with a different spacers_per_slat still
            // takes its true share. Identical allocation while both styles consume 2.
            var perSlat = this.getSpacersPerSlat(s.style);
            var spacers = s.spacerSlats * perSlat;
            var gateSpacers = s.gate && s.gate.spacers > 0 ? s.gate.spacers : 0;
            totals[s.style === 'slat_fence_infill' ? 'infill' : 'fence'] += spacers;
            totals.gateSpacers += gateSpacers;
            if (spacers + gateSpacers <= 0) continue;
            indexes.push(i);
            weights.push(spacers + gateSpacers);
        }

        var pos = indexes.indexOf(sectionIndex);
        if (pos < 0) return null;

        return this.allocatePooledPacks(weights, this.slatSpacerPacksFromTotals(totals, itemsPerPack))[pos];
    },

    // Packs for the job's spacers at one gap: fence, Infill and gate spacers on one line, rounded up once. The old form's
    // 90mm rule took 0.6 of a pack off any job with no gate, so one needing 30 spacers or fewer got none (dropped Nov 2026).
    slatSpacerPacksFromTotals: function(totals, itemsPerPack) {
        var pack = itemsPerPack > 0 ? itemsPerPack : 50;
        var spacers = totals.fence + totals.infill + (totals.gateSpacers > 0 ? totals.gateSpacers : 0);
        return spacers > 0 ? Math.ceil(spacers / pack) : 0;
    },

    /**
     * One gate as the QuickScreen kit is built (supplier catalogue, Nov 2026): two 50 x 50 side frames the gate's height, a 65
     * or 90 rail top and bottom, the slats between them, infill and screw cover by the metre, one joiner pack and two caps per
     * leaf, snap-in spacers and a wafer screw at each slat end. Rows are what fits between the rails, rounded down - a slat
     * that can't go in is never ordered, and what's left over becomes the two end gaps. Lengths are mm; the job-wide metre
     * and pack ceilings are taken in slatQsgGateLinesForSection, the spacers and screws with the fence's on one line each.
     */
    computeSlatQsgGateBom: function(g) {
        var is90 = g.sizePitch >= 80;
        var leaves = g.isDouble ? 2 : 1;
        var railMm = is90 ? 90 : 65;
        var heightMm = g.heightMm > 0 ? g.heightMm : 0;
        var leafMm = g.leafMm > 0 ? g.leafMm : 0;
        var pitch = g.sizePitch + g.gapPitch;
        var space = heightMm - 2 * railMm;
        var rows = space > 0 && pitch > 0 ? Math.floor(space / pitch + 1e-9) : 0;
        var perSlat = g.spacersPerSlat > 0 ? g.spacersPerSlat : 2;
        var live = heightMm > 0 && leafMm > 0;
        var sideFrameMm = live ? 2 * heightMm * leaves : 0;
        var railsMm = live ? 2 * leafMm * leaves : 0;
        return {
            is90: is90,
            gapMm: g.gapMm,
            gapKey: g.gapKey,
            rows: rows,
            leaves: leaves,
            slatMm: rows * leafMm * leaves,
            sideFrameMm: sideFrameMm,
            railMm: railsMm,
            infillMm: railsMm,
            coverMm: sideFrameMm + railsMm,
            joiners: live ? leaves : 0,
            spacers: g.gapKey > 0 ? rows * perSlat * leaves : 0,
            screws: rows * 2 * leaves,
            caps: live ? leaves * 2 : 0,
            latches: 1,
            hinges: leaves,
            handles: leaves,
            dropBolts: g.isDouble ? 1 : 0
        };
    },

    // Option of each post a gate hangs off. A section end set to No Post leaves the gate a post short.
    slatGatePostOptions: function(controls, gateRow, tabRow) {
        var setting = function(controlKey, key) {
            var row = (controls || []).find(function(item) {
                return item && item.control_key === controlKey;
            });
            return (Array.isArray(row?.settings) ? row.settings : []).find(function(item) {
                return item && item.key === key;
            })?.val;
        };
        var midOption = setting('post_options', 'post_option') || 'opt-1';
        var gateOnly = !!gateRow?.settings?.gateOnly || !!tabRow?.gateOnly;
        var position = gateRow?.settings?.position;
        var flank = function(side, isEnd) {
            if (!isEnd) return midOption;
            if (setting(side + '_side', side + '_option') === 'no-post') return null;
            return setting(side + '_side', 'post_option') || midOption;
        };
        return [flank('left', gateOnly || position === 'first'), flank('right', gateOnly || position === 'last')].filter(Boolean);
    },

    // The old form counts a gate run's panels on the fence's width alone (F78), which stops short of the gate's first post.
    // Typed minus C8 still held half the offset per post past the first, so a run just past a step got one panel too many.
    slatGateRunCountAdjustMm: function(slug, gate_data, custom_fence, custom_fence_tab) {
        if (!gate_data || !gate_data.length || this.isGateCentred(slug, gate_data)) return 0;
        var posts = this.slatGatePostOptions(custom_fence, gate_data[0], custom_fence_tab?.[0]).length;
        return (this.getCalcWidthDimensionOffset(slug, custom_fence, custom_fence_tab) / 2) * Math.max(0, posts - 1);
    },

    // The gate saved in a section's controls, worked out from storage only; null when there is none.
    slatGateBomFromControls: function(style, controls, tabRow, calc) {
        if (!this.isMainSlatSlug(style) || !tabRow || typeof fcReadTabRowStep2Field !== 'function') return null;
        if (!(parseInt(calc?.gate?.count, 10) > 0)) return null;
        var gateRow = (controls || []).find(function(item) {
            return item && item.control_key === 'gate';
        });
        if (!gateRow) return null;

        var field = function(key) {
            return (gateRow.settings?.fields || []).find(function(item) {
                return item && item.key === key;
            })?.val;
        };
        var read = function(name) {
            return fcReadTabRowStep2Field(tabRow, style, name);
        };
        var toMm = function(val) {
            return parseInt(String(val === undefined || val === null ? '' : val).replace(/,/g, ''), 10);
        };
        var heightMm = toMm(field('gate_max_fence_height'));
        if (!(heightMm > 0)) heightMm = toMm(read('max_fence_height'));
        var gapMm = parseFloat(read('slat_gap'));
        var leafMm = this.getGateLeafWidthMm(style, [gateRow], calc);
        if (!(heightMm > 0) || !Number.isFinite(gapMm) || !(leafMm > 0)) return null;

        var sizePitch = parseFloat(read('slat_size'));
        return this.slatGateBomFromValues(style, controls, gateRow, tabRow, calc, {
            heightMm: Math.min(heightMm, this.getGateModalFenceHeightBoundsMm().max),
            sizePitch: Number.isFinite(sizePitch) && sizePitch > 0 ? sizePitch : 65.3,
            gapMm: gapMm
        });
    },

    slatGateBomFromValues: function(style, controls, gateRow, tabRow, calc, values) {
        // The row pitch is the fence's (slat_gap_pitch_mm: a 20mm spacer sits at 21.1), so a gate beside a panel spaces alike.
        var gapPitch = this.gapSlugToPitchMm(String(values.gapMm), style);
        return this.computeSlatQsgGateBom({
            heightMm: values.heightMm,
            leafMm: this.getGateLeafWidthMm(style, [gateRow], calc),
            isDouble: this.isSlatDoubleGate([gateRow]),
            sizePitch: values.sizePitch,
            gapMm: values.gapMm,
            gapKey: this.gapMmToSpacerCatalogKey(values.gapMm),
            gapPitch: Number.isFinite(gapPitch) ? gapPitch : values.gapMm,
            spacersPerSlat: this.getSpacersPerSlat(style)
        });
    },

    // Slat rows a gate's label shows (F246), from the values that drawing already resolved.
    slatGateRowsForDisplay: function(style, controls, tabRow, calc, heightMm, sizePitch, gapMm) {
        var gateRow = (controls || []).find(function(item) {
            return item && item.control_key === 'gate';
        });
        if (!gateRow || !this.isMainSlatSlug(style)) return null;
        var bom = this.slatGateBomFromValues(style, controls, gateRow, tabRow, calc, {
            heightMm: heightMm,
            sizePitch: sizePitch,
            gapMm: gapMm
        });
        return Number.isFinite(bom.rows) ? Math.max(0, bom.rows) : null;
    },

    // This section's share of each QuickScreen gate line: metres total once over the job's gates, as the fence's own lines
    // do, and each section gets its largest-remainder share so the lines still add up to the job total.
    slatQsgGateLinesForSection: function(sectionIndex, gate) {
        var gates = [];
        if (Number.isFinite(sectionIndex) && typeof fcGetPersistedFenceSectionCount === 'function') {
            var count = fcGetPersistedFenceSectionCount();
            for (var i = 0; i < count; i++) {
                var saved = this.slatSectionSlatCountFromStorage(i).gate;
                if (saved) gates.push({ index: i, bom: saved });
            }
        }
        if (!gates.some(function(item) { return item.index === sectionIndex; })) {
            gates = [{ index: sectionIndex, bom: gate }];
        }

        var self = this;
        var line = function(field, finish, pool) {
            var list = pool || gates;
            var weights = list.map(function(item) { return item.bom[field]; });
            var pos = list.findIndex(function(item) { return item.index === sectionIndex; });
            var sum = weights.reduce(function(a, b) { return a + b; }, 0);
            return pos < 0 ? 0 : self.allocatePooledPacks(weights, finish(sum))[pos];
        };
        // Whole metres, rounded up once: the store's CTS products sell by the metre.
        var metres = function(mm) {
            return Math.ceil(mm / 1000 - 1e-9);
        };
        var each = function(n) {
            return n;
        };
        var where = function(test) {
            return gates.filter(function(item) { return test(item.bom); });
        };
        var size65 = where(function(b) { return !b.is90; });
        var size90 = where(function(b) { return b.is90; });
        return {
            sideFrames: line('sideFrameMm', metres),
            rails65: line('railMm', metres, size65),
            rails90: line('railMm', metres, size90),
            infill: line('infillMm', metres),
            covers: line('coverMm', metres),
            joiners65: line('joiners', each, size65),
            joiners90: line('joiners', each, size90),
            caps: line('caps', each),
            latches: line('latches', each),
            hinges: line('hinges', each),
            handles: line('handles', each),
            dropBolts: line('dropBolts', each)
        };
    },

    /**
     * FSQ prices cut slats and SFS rails in METRES and rounds ONCE on the job total (F214/F5
     * and F238/F524 display precision 0), so the metre totals pool the same way spacer packs
     * do: sum the per-section metres across sections sharing this style (and slat size, for
     * the size-keyed slat SKUs), round the pooled total, and hand each section its
     * largest-remainder share so the per-section lines still sum exactly to the job total.
     *
     * @param {string} field  'slatM' or 'sfsM' on slatSectionSlatCountFromStorage() output.
     * @param {?number} sizeKey  65/90 to pool one slat SKU only, or null to pool the style.
     * @param {number} [dp]  decimal places to round the pooled total to before rounding it to a
     *        whole number, reproducing the old form's already-rounded subtotal. Omit for none.
     * @return {?number} this section's integer share, or null when the pool cannot be resolved
     *         so the caller falls back to rounding this section alone.
     */
    pooledSlatMetreQtyForSection: function(sectionIndex, styleNorm, field, sizeKey, dp) {
        if (!Number.isFinite(sectionIndex) || typeof fcGetPersistedFenceSectionCount !== 'function') {
            return null;
        }
        var count = fcGetPersistedFenceSectionCount();
        if (!Number.isFinite(count) || count < 1) return null;

        var indexes = [];
        var weights = [];
        for (var i = 0; i < count; i++) {
            var s = this.slatSectionSlatCountFromStorage(i);
            if (s.style !== styleNorm) continue;
            if (sizeKey != null && s.sizeKey !== sizeKey) continue;
            var m = s[field];
            if (!Number.isFinite(m) || m <= 0) continue;
            indexes.push(i);
            weights.push(m);
        }

        var pos = indexes.indexOf(sectionIndex);
        if (pos < 0) return null;

        var totalM = 0;
        for (var w = 0; w < weights.length; w++) totalM += weights[w];

        var total = this.slatBilledMetres(
            styleNorm,
            Math.round(totalM * 1000),
            dp === undefined ? totalM : this.applySlatMetreRoundingDp(totalM, dp)
        );
        return this.allocatePooledPacks(weights, total)[pos];
    },

    // Centre support rail by the metre (CTS): every rail is cut to length, so the job's metres for this style are rounded up
    // once and each section gets its largest-remainder share. null = the pool can't place this section yet.
    pooledSlatCentreRailMetresForSection: function(sectionIndex, styleNorm) {
        if (!Number.isFinite(sectionIndex) || typeof fcGetPersistedFenceSectionCount !== 'function') {
            return null;
        }
        var count = fcGetPersistedFenceSectionCount();
        if (!Number.isFinite(count) || count < 1) return null;

        var indexes = [];
        var weights = [];
        for (var i = 0; i < count; i++) {
            var s = this.slatSectionSlatCountFromStorage(i);
            if (s.style !== styleNorm || !(s.csrMm > 0)) continue;
            indexes.push(i);
            weights.push(s.csrMm);
        }

        var pos = indexes.indexOf(sectionIndex);
        if (pos < 0) return null;

        var totalMm = 0;
        for (var w = 0; w < weights.length; w++) totalMm += weights[w];
        return this.allocatePooledPacks(weights, Math.ceil(totalMm / 1000 - 1e-9))[pos];
    },

    // Cut slat line for one slat size: gate metres (F404, 1 dp) + fence metres (F214), rounded once per job.
    pooledSlatCutQtyForSection: function(sectionIndex, styleNorm, sizeKey, dp) {
        if (!Number.isFinite(sectionIndex) || typeof fcGetPersistedFenceSectionCount !== 'function') {
            return null;
        }
        var count = fcGetPersistedFenceSectionCount();
        if (!Number.isFinite(count) || count < 1) return null;

        var indexes = [];
        var weights = [];
        var fenceMm = 0;
        var gateMm = 0;
        for (var i = 0; i < count; i++) {
            var s = this.slatSectionSlatCountFromStorage(i);
            if (s.style !== styleNorm || s.sizeKey !== sizeKey) continue;
            // Whole mm, so the total rounds as the form's does. A very low gate's rows go negative and take off the total there too.
            var sectionMm = Math.round((Number.isFinite(s.slatM) ? s.slatM : 0) * 1000);
            var sectionGateMm = s.gate ? s.gate.slatMm : 0;
            fenceMm += sectionMm;
            gateMm += sectionGateMm;
            if (sectionMm + sectionGateMm <= 0) continue;
            indexes.push(i);
            weights.push(sectionMm + sectionGateMm);
        }

        var pos = indexes.indexOf(sectionIndex);
        if (pos < 0) return null;

        var total = this.slatBilledMetres(
            styleNorm,
            gateMm + fenceMm,
            this.applySlatMetreRoundingDp(gateMm / 1000, 1) + this.applySlatMetreRoundingDp(fenceMm / 1000, dp)
        );
        return this.allocatePooledPacks(weights, Math.max(0, total))[pos];
    },

    // QuickScreen 16mm screws, Nov 2026: the old tek bag's 100 once per job, as the old eForm adds, on the first Slat or Slat
    // Infill section with panels, plus each gate's (2 per slat row a leaf), in packs rounded up once. null = can't place yet.
    pooledSlatQsScrewPacksForSection: function(sectionIndex, fenceSlug) {
        if (!Number.isFinite(sectionIndex) || typeof fcGetPersistedFenceSectionCount !== 'function') {
            return null;
        }
        var count = fcGetPersistedFenceSectionCount();
        if (!Number.isFinite(count) || count < 1) return null;

        var bag = this.getPackItemsForSlug(fenceSlug, 'slat_gate+screws_flat');
        var indexes = [];
        var weights = [];
        var seen = false;
        var bagPlaced = false;
        for (var i = 0; i < count; i++) {
            var s = this.slatSectionSlatCountFromStorage(i);
            if (!this.isSlatLike(s.style)) continue;
            if (i === sectionIndex) seen = true;
            var screws = s.gate && s.gate.screws > 0 ? s.gate.screws : 0;
            if (!bagPlaced && s.spacerSlats > 0) {
                screws += bag;
                bagPlaced = true;
            }
            if (screws <= 0) continue;
            indexes.push(i);
            weights.push(screws);
        }

        var pos = indexes.indexOf(sectionIndex);
        if (pos < 0) return seen ? 0 : null;

        var total = 0;
        for (var w = 0; w < weights.length; w++) total += weights[w];
        return this.allocatePooledPacks(
            weights,
            this.ceilPackQuantity(total, this.getPackItemsForSlug(fenceSlug, 'slat_gate+qsg_screws'))
        )[pos];
    },

    // One 20kg bag of grout fills about 11 core-drilled holes (100mm cores, 15% waste).
    postsPerGroutBag: 11,

    // 135° adapters are sold in 6000mm lengths and cut into one side-frame-height piece per 135° turn.
    adapterStockLengthMm: 6000,

    // Corner value of a section end that meets another section or a wall at 135° (4-SLAT.php post_angle).
    slatTurnSlug: 'turn-135',

    /** Section ends ('left' / 'right') whose Corner is the 135° turn. */
    slatEndTurnSides: function(controls) {
        var turnSlug = this.slatTurnSlug;
        return ['left', 'right'].filter(function(side) {
            var row = (controls || []).find(function(item) {
                return item && item.control_key === side + '_side';
            });
            var angle = (Array.isArray(row?.settings) ? row.settings : []).find(function(item) {
                return item && item.key === 'post_angle';
            });
            return !!angle && angle.val === turnSlug;
        });
    },

    /** 135° turns in a section: ends set to the turn, plus "135 Degree Angle" posts from older fence files and quotes. */
    slatTurnCount: function(controls, cart) {
        return this.slatEndTurnSides(controls).length + (this.aggregatePostOptQtyFromCart(cart, null).byOpt[5] || 0);
    },

    /** Adapter pieces one stock length gives at a fence height (mm). */
    slat135AdapterPiecesPerLength: function(heightMm) {
        var h = parseInt(heightMm, 10);
        if (!Number.isFinite(h) || h <= 0) {
            h = 1800;
        }
        return Math.max(1, Math.floor(this.adapterStockLengthMm / h));
    },

    /** A section's saved style and Fence Height from `custom_fence-{i}`, never the DOM. */
    slatSectionStyleAndHeightFromStorage: function(sectionIndex) {
        var out = { style: '', heightMm: NaN };
        var row = null;
        try {
            var raw = localStorage.getItem('custom_fence-' + sectionIndex);
            var parsed = raw ? JSON.parse(raw) : null;
            row = parsed && parsed[0] ? parsed[0] : null;
        } catch (e) {}
        if (!row) return out;
        var rawStyle = row.fence || row.style || '';
        out.style =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(rawStyle) : String(rawStyle);
        if (typeof fcReadTabRowStep2Field === 'function') {
            out.heightMm = parseInt(
                String(fcReadTabRowStep2Field(row, out.style, 'max_fence_height') || '').replace(/,/g, ''),
                10
            );
        }
        return out;
    },

    /**
     * Core-drilled posts and 135° turns (by adapter pieces per length) in the earlier Slat sections' saved carts and
     * Corner settings, or null while one of those carts is missing.
     */
    earlierSlatPostOptionTotals: function(sectionIndex) {
        var totals = { coreDrilled: 0, turnsByPieces: {} };
        for (var i = 0; i < sectionIndex; i++) {
            var s = this.slatSectionStyleAndHeightFromStorage(i);
            if (s.style !== 'slat') continue;
            var cart = null;
            try {
                cart = JSON.parse(localStorage.getItem('cart_items-' + i + '-slat'));
            } catch (e) {}
            if (!Array.isArray(cart)) return null;
            var controls = typeof readCustomFenceSegment === 'function' ? readCustomFenceSegment(i, 'slat') : [];
            totals.coreDrilled += this.aggregatePostOptQtyFromCart(cart, null).byOpt[4];
            var turns = this.slatTurnCount(controls, cart);
            if (turns > 0) {
                var pieces = this.slat135AdapterPiecesPerLength(s.heightMm);
                totals.turnsByPieces[pieces] = (totals.turnsByPieces[pieces] || 0) + turns;
            }
        }
        return totals;
    },

    /**
     * This section's grout bags and 135° adapter lengths: its rise in the job's running totals, so the sections sum to
     * one rounding per job. Adapters pool only with sections whose height cuts the same number of pieces per length.
     */
    pooledSlatPostOptionLinesForSection: function(sectionIndex, coreDrilledPosts, turns, fallbackHeightMm) {
        // Carts rebuild in section order before submit; until then a missing earlier cart counts this section alone.
        var before = Number.isFinite(sectionIndex) ? this.earlierSlatPostOptionTotals(sectionIndex) : null;
        if (!before) {
            before = { coreDrilled: 0, turnsByPieces: {} };
        }
        var heightMm = Number.isFinite(sectionIndex)
            ? this.slatSectionStyleAndHeightFromStorage(sectionIndex).heightMm
            : NaN;
        if (!Number.isFinite(heightMm) || heightMm <= 0) {
            heightMm = fallbackHeightMm;
        }
        var perBag = this.postsPerGroutBag;
        var pieces = this.slat135AdapterPiecesPerLength(heightMm);
        var beforeTurns = before.turnsByPieces[pieces] || 0;
        return {
            grout: coreDrilledPosts > 0
                ? Math.ceil((before.coreDrilled + coreDrilledPosts) / perBag) - Math.ceil(before.coreDrilled / perBag)
                : 0,
            adapters: turns > 0
                ? Math.ceil((beforeTurns + turns) / pieces) - Math.ceil(beforeTurns / pieces)
                : 0
        };
    },

    /** Panel / bottom-gap math (Slat Planner V6): 3 mm top + 3 mm bottom in panel height. */
    getSlatPanelEndAllowanceMm: function(fenceSlug) {
        return this.getSlatConfigNumber(fenceSlug, 'panel_end_allowance_mm', 6);
    },

    /**
     * Gap pitch mm for panel row/height formulas (Slat Planner V6 M6 in F80/F81).
     * Reads slat_gap_pitch_mm from the fence config; the literal 9 → 9.3 / 20 → 21.1
     * legacy mapping stays only as a stale-cache fallback for pages that rendered
     * before the config keys existed.
     */
    gapSlugToPitchMm: function(slug, fenceSlug) {
        if (slug === undefined || slug === null || String(slug).trim() === '') {
            return null;
        }
        var raw = String(slug).trim();
        var fromConfig = this.getSlatGapPitchConfigMm(fenceSlug, raw);
        if (Number.isFinite(fromConfig)) {
            return fromConfig;
        }
        if (raw === '9') {
            return 9.3;
        }
        if (raw === '20') {
            return 21.1;
        }
        var n = parseFloat(raw);
        return Number.isFinite(n) && n >= 0 ? n : null;
    },

    /**
     * Slat row count for panel height (F81).
     * floor((MaxHeight − (6 + sizePitch)) / (sizePitch + gapPitch)) + 1
     */
    countSlatPanelRowsFromMaxHeightMm: function(maxHMm, sizePitch, gapPitch) {
        var h = Number(maxHMm);
        var s = Number(sizePitch);
        var g = Number(gapPitch);
        var pitch = s + g;
        var clearance = this.getSlatPanelEndAllowanceMm() + s;
        if (
            !Number.isFinite(h) ||
            h <= 0 ||
            !Number.isFinite(s) ||
            !Number.isFinite(g) ||
            g < 0 ||
            !Number.isFinite(pitch) ||
            pitch <= 0
        ) {
            return 0;
        }
        var rows = Math.floor((h - clearance) / pitch) + 1;
        return rows < 1 ? 0 : rows;
    },

    /**
     * Panel height (mm) from max fence height (F80).
     * (rows × (sizePitch + gapPitch)) − gapPitch + 6
     */
    computeSlatPanelHeightMmFromMaxHeight: function(maxHMm, sizePitch, gapPitch) {
        var rows = this.countSlatPanelRowsFromMaxHeightMm(maxHMm, sizePitch, gapPitch);
        if (rows < 1) {
            return 0;
        }
        var s = Number(sizePitch);
        var g = Number(gapPitch);
        var pitch = s + g;
        if (!Number.isFinite(pitch) || pitch <= 0) {
            return 0;
        }
        return Math.round(rows * pitch - g + this.getSlatPanelEndAllowanceMm());
    },

    /** Bottom gap (mm) = Max Height − Panel Height (F77 − F80). */
    computeSlatBottomGapMm: function(maxHMm, sizePitch, gapPitch) {
        var maxH = Math.round(Number(maxHMm));
        if (!Number.isFinite(maxH) || maxH <= 0) {
            return 0;
        }
        var panel = this.computeSlatPanelHeightMmFromMaxHeight(maxH, sizePitch, gapPitch);
        if (!Number.isFinite(panel) || panel <= 0) {
            return 0;
        }
        return Math.max(0, maxH - panel);
    },

    /**
     * Panel qty from the run length as typed: midPosts = floor(length / maxSpan), panels = midPosts + 1 (Slat Planner V6 F82 + 1).
     * No width cap, as in the old planner: the `max_panel_width_mm` loop split 29,600 into 13 panels where it lays 12.
     */
    countEvenPanelsFromOverallSpan: function(lengthMm, maxPanelSpanMm, info) {
        var length = parseInt(lengthMm, 10);
        var maxSpan = parseInt(maxPanelSpanMm, 10);
        if (!Number.isFinite(maxSpan) || maxSpan <= 0) {
            maxSpan = this.getMaxPanelSpanMmFromInfo(info);
        }
        if (!Number.isFinite(length) || length <= 0) {
            return 1;
        }
        if (!Number.isFinite(maxSpan) || maxSpan <= 0) {
            return 1;
        }
        var midPosts = Math.floor(length / maxSpan);
        return Math.max(1, midPosts + 1);
    },

    /** Centre gate: an even count, half each side. One panel stays one when two would be under the narrowest gate-side panel. */
    centredGatePanelQty: function(slug, panelQty, spanMm, postWidthMm, removedPostsMm) {
        if (panelQty % 2 === 0) {
            return panelQty;
        }
        var next = panelQty + 1;
        var minPanelMm = parseInt(FENCE.get(slug, 'minPanelWidthOnGate'), 10) || 86;
        return this.computeSlatPanelSpanMm(spanMm, next, postWidthMm, removedPostsMm) >= next * minPanelMm ? next : panelQty;
    },

    /**
     * End posts removed via left/right "no-post", in SPAN terms (mm).
     *
     * `FENCE.minus_posts()` reports a full post width per removed end — right for post
     * MATERIAL (the summary feeds computeSlatTotalPostsMm from it directly) but not for
     * reversing the "Width Dimension From" span offset this value exists to undo: that
     * offset removed post × |wdf| / 2 per end (Outside −2 → 50, Centre-line −1 → 25), so a
     * no-post end must add back the same amount. Unscaled, centre-line + no-post laid every
     * panel up to 25 mm too wide (the old planner halves its deduction in centre-line mode,
     * F334/2: confirmed old 2375 vs new 2400 at W2400). Unknown/zero offset falls back to
     * factor 2 — the pre-fix behaviour, which is exact for the Outside default.
     */
    getRemovedEndPostsMm: function(custom_fence) {
        if (typeof FENCE === 'undefined' || typeof FENCE.minus_posts !== 'function') {
            return 0;
        }
        var removed = parseInt(FENCE.minus_posts(custom_fence || []), 10);
        if (!Number.isFinite(removed) || removed <= 0) {
            return 0;
        }
        var slug = custom_fence?.[0]?.id;
        if (!slug && typeof getSelectedFenceData === 'function') {
            slug = getSelectedFenceData()?.slug;
        }
        var postW = parseInt(typeof FENCE.get === 'function' ? FENCE.get(slug, 'post') : 50, 10);
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        var offsetMm = parseInt(this.getCalcWidthDimensionOffset(slug, custom_fence, undefined), 10);
        var factor =
            Number.isFinite(offsetMm) && offsetMm !== 0
                ? Math.min(2, Math.abs(offsetMm) / postW)
                : 2;
        return Math.round((removed * factor) / 2);
    },

    /**
     * Total post width (mm) for N panels: (N+1)×post minus removed end posts.
     */
    computeSlatTotalPostsMm: function(panelCount, postWidthMm, removedPostsMm) {
        var n = parseInt(panelCount, 10);
        var postW = parseInt(postWidthMm, 10);
        var removed = parseInt(removedPostsMm, 10);
        if (!Number.isFinite(n) || n <= 0) {
            return 0;
        }
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        if (!Number.isFinite(removed) || removed < 0) {
            removed = 0;
        }
        return Math.max(0, (n + 1) * postW - removed);
    },

    /**
     * Total panel material (mm) inside a clear span: only the N−1 interior posts.
     *
     * `spanMm` comes from `getSlatInfillSpanMm()`, which has already applied the
     * "Width Dimension From" offset (−1×post center-line, −2×post outside) and so
     * excludes both end posts. Deducting `(N+1)×post` here as well would remove the
     * end posts twice. `removedPostsMm` is added back because that offset assumed
     * both end posts were present.
     */
    computeSlatPanelSpanMm: function(spanMm, panelCount, postWidthMm, removedPostsMm) {
        var span = parseInt(spanMm, 10);
        var n = parseInt(panelCount, 10);
        var postW = parseInt(postWidthMm, 10);
        var removed = parseInt(removedPostsMm, 10);
        if (!Number.isFinite(span) || !Number.isFinite(n) || n <= 0) {
            return 0;
        }
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        if (!Number.isFinite(removed) || removed < 0) {
            removed = 0;
        }
        return Math.max(0, span + removed - (n - 1) * postW);
    },

    /**
     * Even panel width (mm): ceil(panelSpan / N) where panelSpan excludes interior posts only.
     * `removedPostsMm` from `getRemovedEndPostsMm()` when left/right end is no-post.
     */
    computeSlatEvenPanelWidthMm: function(overallMm, panelCount, postWidthMm, removedPostsMm) {
        var ov = parseInt(overallMm, 10);
        var n = parseInt(panelCount, 10);
        var postW = parseInt(postWidthMm, 10);
        if (!Number.isFinite(ov) || ov <= 0 || !Number.isFinite(n) || n <= 0) {
            return 0;
        }
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        var avail = this.computeSlatPanelSpanMm(ov, n, postW, removedPostsMm);
        if (avail <= 0) {
            return 0;
        }
        return Math.ceil(avail / n);
    },
};

// Attach onto SlatFence (fences/slat_fence.js, loaded earlier in the same glob pass).
// slat_fence.js carries a matching startup check that fails loudly if this file is missing,
// because HELPER.call_fence_func would otherwise swallow the resulting errors silently.
if (typeof SlatFence !== 'undefined' && SlatFence) {
    Object.keys(SlatFenceCalc).forEach(function (key) {
        SlatFence[key] = SlatFenceCalc[key];
    });
}
