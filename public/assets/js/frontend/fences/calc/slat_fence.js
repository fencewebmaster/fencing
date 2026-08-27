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
     * fence+infill jobs. Sections only share a pool when they also order the same pack size,
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

        var spacerSlug = 'slat_spacer+' + gapKey;
        var indexes = [];
        var weights = [];
        for (var i = 0; i < count; i++) {
            var s = this.slatSectionSlatCountFromStorage(i);
            if (!this.isSlatLike(s.style) || s.gapKey !== gapKey || s.slats <= 0) continue;
            if (this.getPackItemsForSlug(s.style, spacerSlug) !== itemsPerPack) continue;
            indexes.push(i);
            // Weigh by spacers, not slats, so a style with a different spacers_per_slat still
            // takes its true share. Identical allocation while both styles consume 2.
            weights.push(s.slats * this.getSpacersPerSlat(s.style));
        }

        var pos = indexes.indexOf(sectionIndex);
        if (pos < 0) return null;

        var totalSpacers = 0;
        for (var w = 0; w < weights.length; w++) totalSpacers += weights[w];

        var totalPacks = this.ceilPackQuantity(totalSpacers, itemsPerPack);
        return this.allocatePooledPacks(weights, totalPacks)[pos];
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

        var total = Math.round(
            dp === undefined ? totalM : this.applySlatMetreRoundingDp(totalM, dp)
        );
        return this.allocatePooledPacks(weights, total)[pos];
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
     * Panel qty from overall span: midPosts = floor(span / maxSpan), panels = midPosts + 1 (Slat Planner V6 F82 + 1).
     * `maxPanelSpanMm` from `getMaxPanelSpanMmFromInfo()` / `4-SLAT.php` `max_panel_span_mm`.
     */
    countEvenPanelsFromOverallSpan: function(spanMm, maxPanelSpanMm, postWidthMm, info, removedPostsMm) {
        var span = parseInt(spanMm, 10);
        var maxSpan = parseInt(maxPanelSpanMm, 10);
        if (!Number.isFinite(maxSpan) || maxSpan <= 0) {
            maxSpan = this.getMaxPanelSpanMmFromInfo(info);
        }
        if (!Number.isFinite(span) || span <= 0) {
            return 1;
        }
        if (!Number.isFinite(maxSpan) || maxSpan <= 0) {
            return 1;
        }
        var midPosts = Math.floor(span / maxSpan);
        var count = Math.max(1, midPosts + 1);

        // A bay can still land over `max_panel_width_mm` once interior posts are removed
        // from the span — add posts until every even panel fits the widest stock panel.
        var maxWidth = this.getMaxPanelWidthMmFromInfo(info);
        var postW = parseInt(postWidthMm, 10);
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        if (Number.isFinite(maxWidth) && maxWidth > 0) {
            while (
                count < 500 &&
                this.computeSlatEvenPanelWidthMm(span, count, postW, removedPostsMm) > maxWidth
            ) {
                count += 1;
            }
        }

        return count;
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
