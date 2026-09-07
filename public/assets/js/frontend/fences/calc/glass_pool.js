/**
 * Glass Pool calculation module.
 *
 * The glue between calculate_fences() and the glass panel solver (below in this file),
 * split out of fences/calc/calc.js: hinge/latch gap resolution, the
 * hinge-panel default, the solver config, and mapping the solver's plan back onto the
 * variable names calculate_fences() feeds into its returned plan.
 *
 * Loaded by fence-scripts.php AFTER the main fences/*.js glob and attached onto GlassPool
 * below, so calculate_fences() calls GlassPool.calculatePanels().
 */

GlassPoolCalc = {

    /**
     * Panel layout for one Glass Pool section. Callers must skip gate-only sections — the
     * solver still subtracts the gate and looks for regular panels, which fails with a
     * bogus "no solutions" error (the guard lives at the calc.js call site, as before).
     */
    calculatePanels: function(input) {
        var fdGlass = { slug: input.slug, data: input.info, info: input.customFence };
        var glassGaps =
            typeof FENCE !== 'undefined' && typeof FENCE.resolveGlassPoolHingeGapsMm === 'function'
                ? FENCE.resolveGlassPoolHingeGapsMm(fdGlass, input.gateData)
                : { hinge: 10, latch: 9 };

        // Hinge panel is always part of the gate assembly for glass pool sizing and diagram render.
        var gate_hinge_panel_count = 0;
        if (input.gateCount) {
            gate_hinge_panel_count = 1;
        }

        var hingePanelActive = !!gate_hinge_panel_count;
        if (input.gateOnly) hingePanelActive = false;

        var gate_hinge_panel_width = input.gateHingePanelWidthMm;
        if (
            hingePanelActive &&
            (!Number.isFinite(gate_hinge_panel_width) || gate_hinge_panel_width <= 0) &&
            typeof fcGlassPoolDefaultHingePanelWidthMm === 'function'
        ) {
            gate_hinge_panel_width = fcGlassPoolDefaultHingePanelWidthMm(input.info);
        }

        var glassPoolConfig = {
            overallLength: input.overallLengthMm,
            gate: {
                active: input.gateCount ? true : false,
                gateSize: input.gateWidthMm,
                hingePanelSize: gate_hinge_panel_width,
                hingePanelActive: hingePanelActive,
                hingeType: { left: glassGaps.hinge, right: glassGaps.latch }
            },
            leftRakedPanel: { active: input.leftRakedWidthMm > 0 ? true : false, size: input.leftRakedWidthMm },
            rightRakedPanel: { active: input.rightRakedWidthMm > 0 ? true : false, size: input.rightRakedWidthMm },
            leftEndAttachment: { size: [-1, false].includes(input.leftSideWidthD) ? '' : input.leftSideWidthD }, // 0, 25, gap based
            rightEndAttachment: { size: [-1, false].includes(input.rightSideWidthD) ? '' : input.rightSideWidthD }, // 0, 25, gap based
            panelSettings: {
                maxPanelSize: 2000,
                minPanelSize: 200,
                defaultPanelSize: input.defaultPanelSizeMm, // User-adjustable initial preference.
                panelSizeIncrement: 50, // All regular panels will be rounded to this increment.
                panelGap: input.panelGapMm, // User-adjustable initial preference. Final gap may be adjusted.
                maxPanelSpacing: 80, // The maximum allowed gap between panels.
            }
        };

        // Only once the customer has accepted the gap adjustment (or a dry run asks what it
        // would give): the solver then keeps every junction gap at or above the smallest
        // clamp's minimum and may widen past the preferred spacing up to the largest clamp's
        // maximum (capped at maxPanelSpacing inside the solver). Both keys stay ABSENT
        // otherwise - the solver JSON-clones this config per
        // pass, and an Infinity sentinel would not survive that.
        if (input.clamp && input.clamp.enforce) {
            glassPoolConfig.panelSettings.minPanelGap = input.clamp.minGapMm;
            glassPoolConfig.panelSettings.clampMaxGapMm = input.clamp.maxGapMm;
        }

        var glassPoolPlan = calculateGlassFencing(glassPoolConfig);
        var out = {
            spacing_width: glassPoolPlan?.results?.panelGapsValue ?? input.panelGapMm,
            msg: glassPoolPlan?.calculationDetails?.error || '',
            closest_lengths: null,
            gate_hinge_panel_count: gate_hinge_panel_count
        };

        // On failure, hand the UI verified alternative lengths so it can auto-adjust rather
        // than surface the raw solver error. Runs only when the solver has already failed.
        if (out.msg) {
            out.closest_lengths = GlassPoolCalc.findClosestBuildableLengths(
                glassPoolConfig,
                glassPoolPlan?.calculationDetails?.closestLengths
            );
        }

        if (glassPoolPlan?.results) {
            out.long_panel_count = glassPoolPlan.results.longPanels?.count;
            out.long_panel_length = glassPoolPlan.results.longPanels?.size;
            out.short_panel_count = glassPoolPlan.results.shortPanel?.count;
            out.short_panel_length = glassPoolPlan.results.shortPanel?.size;
            out.gate_hinge_panel_width = hingePanelActive
                ? (glassPoolPlan.results.hingeSize ?? gate_hinge_panel_width)
                : 0;
        } else {
            out.long_panel_count = 0;
            out.long_panel_length = 0;
            out.short_panel_count = 0;
            out.short_panel_length = 0;
            out.gate_hinge_panel_width = 0;
        }

        // Classified from the same gap spacing_width carries, so the status line, the cart
        // and the diagram labels all describe one figure.
        out.clamp = GlassPoolCalc.clampResult(
            input.clamp || GlassPoolCalc.clampRequest([], input.info),
            out.spacing_width,
            !!out.msg
        );

        return out;
    },

    /**
     * Smallest ACCURATE overall (mm) for the configured fixed elements: raked panels + the
     * gate assembly (gate + hinge panel + hinge/latch gaps) + junction gaps between fixed
     * elements + both end spacings at the nominal panel gap. Mirrors the component reads in
     * _fcGlassDynamicEndRescue. This is the floor an under-entered Overall Length snaps to -
     * NOT the solver's absolute minimum, which squeezes the end spacings toward 0mm and
     * draws a fence nobody can build.
     *
     * @returns {number} 0 when there are no fixed elements (a plain panel run has no
     *   assembly floor).
     */
    minAssemblyLengthMm: function(config) {
        var gate = config?.gate;
        var gapMm = Number(config?.panelSettings?.panelGap);
        if (!Number.isFinite(gapMm) || gapMm < 0) {
            gapMm = 0;
        }

        var fixedSizes = [];
        if (config?.leftRakedPanel?.active) {
            fixedSizes.push(Number(config.leftRakedPanel.size) || 0);
        }
        if (config?.rightRakedPanel?.active) {
            fixedSizes.push(Number(config.rightRakedPanel.size) || 0);
        }
        if (gate?.active) {
            var hingePanelMm = gate.hingePanelActive ? (Number(gate.hingePanelSize) || 0) : 0;
            fixedSizes.push(
                (Number(gate.gateSize) || 0) +
                    hingePanelMm +
                    (Number(gate.hingeType?.left) || 0) +
                    (Number(gate.hingeType?.right) || 0)
            );
        }
        if (!fixedSizes.length) {
            return 0;
        }

        var total = fixedSizes.reduce(function(a, b) { return a + b; }, 0);
        total += Math.max(0, fixedSizes.length - 1) * gapMm;

        var leftEnd = config?.leftEndAttachment?.size;
        var rightEnd = config?.rightEndAttachment?.size;
        total += leftEnd === '' ? gapMm : (Number(leftEnd) || 0);
        total += rightEnd === '' ? gapMm : (Number(rightEnd) || 0);

        return Math.round(total);
    },

    /**
     * Nearest overall lengths (mm) above and below config.overallLength that the untouched
     * solver can actually build - each candidate is verified by re-running
     * calculateGlassFencing(), so a length returned here cannot fail when the planner
     * re-calculates with it. Called only after a failed plan; bounded to STEP*RANGE_STEPS mm
     * each way so a hopeless configuration cannot spin.
     *
     * `hints` is calculationDetails.closestLengths from the dynamic-end rescue (analytic
     * dead-zone edges, close but not guaranteed buildable) - used as scan start points.
     *
     * @returns {{shortenTo: number, extendTo: number}|null} 0 for a direction with no
     *   working length in range; null when neither direction has one.
     */
    findClosestBuildableLengths: function(baseConfig, hints) {
        var overall = Number(baseConfig?.overallLength) || 0;
        if (overall <= 0) {
            return null;
        }

        var works = function(mm) {
            if (!Number.isFinite(mm) || mm <= 0 || mm === overall) {
                return false;
            }
            var cfg = JSON.parse(JSON.stringify(baseConfig));
            cfg.overallLength = mm;
            var plan;
            try {
                plan = calculateGlassFencing(cfg);
            } catch (eProbe) {
                return false;
            }
            return !!(plan && plan.results);
        };

        var FINE_MM = 30;   // every mm for the first 30mm out - "closest" stays literally true
        var RANGE_MM = 600; // then 10mm steps to +/-600mm

        // Nearest-first, scanning outward from the entered length - NOT from the analytic
        // hints. The hints mark where the rescue's 0/1-panel model works, which can sit far
        // beyond closer solutions it does not model (e.g. a zero-panel gap window opening
        // 240mm before the hint's one-panel length).
        var scanFrom = function(centerMm, dir) {
            for (var d = 1; d <= RANGE_MM; d++) {
                if (d > FINE_MM && d % 10 !== 0) {
                    continue;
                }
                var mm = centerMm + dir * d;
                if (mm <= 0) {
                    return 0;
                }
                if (works(mm)) {
                    return mm;
                }
            }
            return 0;
        };

        var shortenTo = scanFrom(overall, -1);
        var extendTo = scanFrom(overall, 1);

        // Nothing buildable BELOW means the entry sits under the fence's minimum. The up-scan
        // then lands on the solver's absolute squeeze (end spacings forced toward 0mm), so
        // replace it with the accurate assembly floor - hinge panel + gate + the spacing -
        // whenever the solver confirms that floor builds.
        if (!shortenTo) {
            var assemblyMin = GlassPoolCalc.minAssemblyLengthMm(baseConfig);
            if (assemblyMin > overall && works(assemblyMin)) {
                extendTo = assemblyMin;
            }
        }

        // A direction with nothing in range: fall back to its hint (verified, then walking
        // away from the entered length) so a fix beyond the window is still found.
        var hintShorten = Number(hints?.shortenTo);
        if (!shortenTo && Number.isFinite(hintShorten) && hintShorten > 0 && hintShorten < overall) {
            shortenTo = scanFrom(hintShorten + 1, -1);
        }
        var hintExtend = Number(hints?.extendTo);
        if (!extendTo && Number.isFinite(hintExtend) && hintExtend > overall) {
            extendTo = scanFrom(hintExtend - 1, 1);
        }

        if (!shortenTo && !extendTo) {
            return null;
        }
        return { shortenTo: shortenTo, extendTo: extendTo };
    },

    // --------------------------------------------------
    // Panel-to-panel clamps.
    //
    // "Yes Clamps" on the Step-2 Panel Clamps tile used to be render-only: the diagram drew
    // a clamp bar on every junction whether or not a clamp existed for that gap, so a 13mm
    // gap shipped with bars that no clamp fits and nothing in the cart. These members
    // classify the solved gap against the configured clamp sizes and, once the customer has
    // accepted the gap adjustment, tell the solver the minimum it must keep. Pure - no DOM,
    // jQuery or localStorage - so tests/glass-pool-clamps/run.js exercises the same code.
    // --------------------------------------------------

    CLAMP_CONTROL_KEY: 'panel_clamps',   // the code-written control row (GlassPool.setClampGapAdjust)
    CLAMP_ADJUST_KEY: 'gap_adjust',      // settings key inside that row, val '1'
    CLAMP_OPTION_KEY: 'post_option',     // Panel Clamps field slug (inside panel_options_custom)
    CLAMP_OPTION_YES: 'opt-2',

    /**
     * The shipped sizes from writable/fences/3-GLASS-POOL.php `panel_clamps`. Used when
     * info.panel_clamps is missing or holds nothing valid, so a config file that predates the
     * key (or one emptied in the admin GUI) degrades to the catalog ranges rather than
     * classifying every gap against nothing. Fresh arrays each call - callers sort in place.
     */
    clampDefaults: function() {
        return [
            { key: 'small', title: 'Small', minGapMm: 20, maxGapMm: 50, slug: 'panel_to_panel_clamp+sm' },
            { key: 'large', title: 'Large', minGapMm: 50, maxGapMm: 95, slug: 'panel_to_panel_clamp+lg' }
        ];
    },

    /**
     * Clamp sizes from info.panel_clamps (object keyed by size key), sorted by minGapMm.
     * Drops entries whose min/max are not finite positive numbers with min < max, or whose
     * slug is blank: the admin GUI writes '' for an emptied number field, and a half-filled
     * size would otherwise match gaps with no product behind it. Falls back to
     * clampDefaults() when nothing valid remains.
     *
     * @returns {Array<{key:string,title:string,minGapMm:number,maxGapMm:number,slug:string}>}
     */
    clampSizesFromInfo: function(info) {
        var raw = info && typeof info === 'object' ? info.panel_clamps : null;
        var sizes = [];
        if (raw && typeof raw === 'object') {
            Object.keys(raw).forEach(function(key) {
                var row = raw[key];
                if (!row || typeof row !== 'object') {
                    return;
                }
                var min = Number(row.min_gap_mm);
                var max = Number(row.max_gap_mm);
                var slug = String(row.slug == null ? '' : row.slug).trim();
                if (!Number.isFinite(min) || !Number.isFinite(max) || min <= 0 || max <= 0 || min >= max || slug === '') {
                    return;
                }
                var title = row.title == null ? '' : String(row.title).trim();
                sizes.push({
                    key: String(key),
                    title: title === '' ? String(key) : title,
                    minGapMm: min,
                    maxGapMm: max,
                    slug: slug
                });
            });
        }
        if (!sizes.length) {
            return GlassPoolCalc.clampDefaults();
        }
        sizes.sort(function(a, b) { return a.minGapMm - b.minGapMm; });
        return sizes;
    },

    /**
     * Which clamp a gap takes: a gap matches size k when gap >= min_k && gap < max_k, except
     * the last (largest) size, which includes its maximum - so with the shipped ranges 50 is
     * Large, 95 is Large, 96 is above_max and 19 is below_min. Compare the ROUNDED gap: the
     * diagram prints Math.round of the mean gap, so a 19.6mm layout must read as 20 here too.
     *
     * @returns {{status:string, size:object|null, minGapMm:number, maxGapMm:number}}
     *   status 'ok' | 'below_min' (under the smallest size's minimum) | 'above_max' (over the
     *   largest size's maximum) | 'no_size' (inside that overall range but in a hole between
     *   two configured sizes - Small 20-45 / Large 50-95 leaves 45..49 with no clamp) |
     *   'invalid' (gap not finite or <= 0).
     */
    clampForGap: function(gapMm, sizes) {
        var list = Array.isArray(sizes) && sizes.length ? sizes : GlassPoolCalc.clampDefaults();
        var out = {
            status: 'invalid',
            size: null,
            minGapMm: list[0].minGapMm,
            maxGapMm: list[list.length - 1].maxGapMm
        };
        var gap = Number(gapMm);
        if (!Number.isFinite(gap) || gap <= 0) {
            return out;
        }
        if (gap < out.minGapMm) {
            out.status = 'below_min';
            return out;
        }
        if (gap > out.maxGapMm) {
            out.status = 'above_max';
            return out;
        }
        for (var k = 0; k < list.length; k++) {
            var last = k === list.length - 1;
            if (gap >= list[k].minGapMm && (last ? gap <= list[k].maxGapMm : gap < list[k].maxGapMm)) {
                out.status = 'ok';
                out.size = list[k];
                return out;
            }
        }
        // Inside the overall range but in a hole between two configured sizes (the admin GUI
        // does not force the ranges to touch: Small 20-45 / Large 50-95 leaves a 47mm gap with
        // no clamp). Its own status - reporting it as above_max told the customer their 47mm
        // gap was over the 95mm maximum, which is false and points them at the wrong fix.
        out.status = 'no_size';
        return out;
    },

    /**
     * What the customer asked for on this section, read from the stored custom_fence rows.
     * `selected` is the Panel Clamps tile ("Yes Clamps"); `enforce` additionally needs the
     * code-written panel_clamps row (the customer accepted the gap adjustment) or
     * opts.forceEnforce (a dry run asking "what would the gap be?").
     *
     * Settings are found BY KEY, never by position: mergeSettings() re-pushes DOM-backed keys
     * to the end of the panel_options_custom row on every modal save, so its settings order
     * is not stable (calc.js reads the panel width positionally for the same reason and that
     * row must never gain foreign keys).
     *
     * @returns {{selected:boolean, enforce:boolean, sizes:Array, minGapMm:number, maxGapMm:number}}
     */
    clampRequest: function(customFence, info, opts) {
        var sizes = GlassPoolCalc.clampSizesFromInfo(info);
        var req = {
            selected: false,
            enforce: false,
            sizes: sizes,
            minGapMm: sizes[0].minGapMm,
            maxGapMm: sizes[sizes.length - 1].maxGapMm
        };
        if (!Array.isArray(customFence) || !customFence.length) {
            return req;
        }
        var hasSetting = function(controlKey, key, wanted) {
            return customFence.some(function(row) {
                if (!row || String(row.control_key) !== controlKey || !Array.isArray(row.settings)) {
                    return false;
                }
                return row.settings.some(function(setting) {
                    return !!setting && setting.key === key && wanted.indexOf(String(setting.val)) !== -1;
                });
            });
        };
        req.selected = hasSetting('panel_options_custom', GlassPoolCalc.CLAMP_OPTION_KEY, [GlassPoolCalc.CLAMP_OPTION_YES]);
        req.enforce = req.selected && (
            (!!opts && opts.forceEnforce === true) ||
            hasSetting(GlassPoolCalc.CLAMP_CONTROL_KEY, GlassPoolCalc.CLAMP_ADJUST_KEY, ['1'])
        );
        return req;
    },

    /**
     * The clamp outcome for a solved section, carried on selected_values.clamp. Not selected
     * -> 'none'; solver failed (or no finite gap) -> 'invalid'; otherwise clampForGap() on the
     * rounded gap. Junction gating - a layout with nothing to clamp (single panel, zero-panel
     * run) - is applied by the consumers from the diagram count; this stays DOM-free.
     */
    clampResult: function(request, gapExactMm, solverFailed) {
        var req = request && typeof request === 'object' ? request : {};
        var sizes = Array.isArray(req.sizes) && req.sizes.length ? req.sizes : GlassPoolCalc.clampSizesFromInfo(null);
        var gap = Number(gapExactMm);
        var finite = Number.isFinite(gap);
        var out = {
            selected: !!req.selected,
            enforce: !!req.enforce,
            gapMm: finite ? Math.round(gap) : 0,
            gapExactMm: finite ? gap : 0,
            status: 'none',
            size: null,
            slug: '',
            minGapMm: sizes[0].minGapMm,
            maxGapMm: sizes[sizes.length - 1].maxGapMm
        };
        if (!out.selected) {
            return out;
        }
        if (solverFailed || !finite) {
            out.status = 'invalid';
            return out;
        }
        var match = GlassPoolCalc.clampForGap(out.gapMm, sizes);
        out.status = match.status;
        out.size = match.size;
        out.slug = match.size ? match.size.slug : '';
        return out;
    }

};

// Attach onto GlassPool (fences/glass_pool.js, loaded earlier in the same pass).
if (typeof GlassPool !== 'undefined' && GlassPool) {
    Object.keys(GlassPoolCalc).forEach(function (key) {
        GlassPool[key] = GlassPoolCalc[key];
    });
}

// --------------------------------------------------
// Glass panel solver -- called only from calculatePanels above.
// --------------------------------------------------

/**
 * Fencing Glass Planner Calculator
 *
 * This script calculates the number and sizes of glass panels required for a fencing project.
 * It considers various components like raked panels, gates, and different types of gaps.
 * The core logic aims to use the maximum number of standard-sized panels and one smaller panel to fit the total length.
 *
 * @version 3.0.0
 */

/**
 * Finds all possible combinations of regular glass panels for a given length.
 * Only allows solutions where all panels are the same size (no short panels).
 *
 * @param {object} options - The options for panel calculation.
 * @param {number} options.lengthToFill - The total length for regular panels and their gaps.
 * @param {number} options.panelGap - The user-preferred gap size for initial estimation.
 * @param {number} options.maxPanelSize - The maximum size of a standard panel.
 * @param {number} options.minPanelSize - The minimum size of a panel.
 * @param {number} [options.preferredPanelSize] - The preferred or default size for panels.
 * @returns {Array<object>} A list of possible panel layout solutions (all panels same size).
 */
function findAllPossiblePanelLayouts({
    lengthToFill,
    panelGap,
    maxPanelSize,
    minPanelSize,
    preferredPanelSize
}) {
    if (lengthToFill <= 0) {
        return [];
    }

    const solutions = [];
    const targetPanelSize = preferredPanelSize || maxPanelSize;
    const minPossibleGap = 30;
    // n_min: minimum number of panels (largest possible panels)
    // n_max: maximum number of panels (smallest possible panels)
    const n_min = Math.max(1, Math.ceil((lengthToFill + panelGap) / (targetPanelSize + panelGap)));
    const n_max = Math.floor((lengthToFill + minPossibleGap) / (minPanelSize + minPossibleGap)) + 1;

    for (let n = n_min; n <= n_max; n++) {
        // Calculate the panel size for n panels (all the same size)
        const totalPanelLength = lengthToFill - (n - 1) * panelGap;
        const panelSize = totalPanelLength / n;
        if (panelSize >= minPanelSize && panelSize <= maxPanelSize) {
            solutions.push({
                longPanels: { count: n, size: panelSize },
                shortPanel: { count: 0, size: 0 }, // No short panels
            });
        }
    }
    return solutions;
}


/**
 * Main function to calculate the fencing plan.
 * @param {object} config - The configuration object for the fencing project.
 * @returns {object} The calculated fencing plan.
 */
function calculateGlassFencing(config) {
    // Helper to resolve end gap value (number or '' for dynamic)
    function resolveEndGap(val, fallback) {
        if (val === '' || val === undefined || val === null) return fallback;
        return Number(val);
    }

    // 1. Prepare config for first pass (treat '' as 0 for initial calculation)
    let leftEndGap = resolveEndGap(config.leftEndAttachment?.size, 0);
    let rightEndGap = resolveEndGap(config.rightEndAttachment?.size, 0);
    let firstPassConfig = JSON.parse(JSON.stringify(config));
    firstPassConfig.leftEndAttachment.size = leftEndGap;
    firstPassConfig.rightEndAttachment.size = rightEndGap;

    // 2. Run first calculation
    let firstPass = _calculateGlassFencingInternal(firstPassConfig, leftEndGap, rightEndGap, false, false);
    let adjustedPanelGap = firstPass.results ? firstPass.results.panelGapsValue : 0;

    // 3. If either end was '', set it to the adjusted panel gap and rerun
    let needsSecondPass = (config.leftEndAttachment?.size === '' || config.rightEndAttachment?.size === '');
    if (needsSecondPass) {
        // Tight gate-only-infill runs (e.g. overall = gate + nominal gaps, no field glass) succeed on
        // the first pass with ends treated as 0. A second pass with leftDynamic/rightDynamic true skips
        // the slack / zero-infill fallback inside _calculateGlassFencingInternal and incorrectly errors.
        if (firstPass.results && firstPass.calculationDetails?.zeroRegularPanels) {
            return firstPass;
        }
        // Mark which ends are dynamic
        const leftDynamic = config.leftEndAttachment?.size === '';
        const rightDynamic = config.rightEndAttachment?.size === '';
        leftEndGap = leftDynamic ? adjustedPanelGap : leftEndGap;
        rightEndGap = rightDynamic ? adjustedPanelGap : rightEndGap;
        let secondPassConfig = JSON.parse(JSON.stringify(config));
        secondPassConfig.leftEndAttachment.size = leftEndGap;
        secondPassConfig.rightEndAttachment.size = rightEndGap;
        let secondPass = _calculateGlassFencingInternal(secondPassConfig, leftEndGap, rightEndGap, leftDynamic, rightDynamic);
        // Dynamic "gap based" ends are the planner's default, and the solver above has two blind
        // spots for them: the zero-panel fallback only runs in the first pass (ends pinned to 0,
        // capping usable slack at one nominal gap), and findAllPossiblePanelLayouts reserves no
        // room for the end gaps, so short runs generate one full-width panel whose gap is 0 and
        // die in the filter. Both surface as "No initial panel solutions" on perfectly buildable
        // lengths (e.g. gate + 1200 hinge panel errored for every overall in 2021-2218mm). The
        // rescue below fills the slack with the dynamic end gaps themselves - zero or one panel -
        // and only ever runs where the solver has already failed, so working outputs are untouched.
        if (!secondPass.results) {
            const rescue = _fcGlassDynamicEndRescue(config, leftDynamic, rightDynamic);
            if (rescue && rescue.ok) return rescue.result;
            if (rescue && rescue.shortenTo > 0 && rescue.extendTo > rescue.shortenTo) {
                secondPass = JSON.parse(JSON.stringify(secondPass));
                secondPass.calculationDetails = secondPass.calculationDetails || {};
                secondPass.calculationDetails.error =
                    'No panel layout fits this exact length - the space left over is too wide for gaps'
                    + ' alone but too narrow for the smallest ' + config.panelSettings.minPanelSize
                    + 'mm panel. Closest working lengths: ' + rescue.shortenTo + 'mm or '
                    + rescue.extendTo + 'mm overall.';
                // Same numbers as the prose, structured, so the planner UI can auto-adjust the
                // Overall Length instead of asking the customer to retype it.
                secondPass.calculationDetails.closestLengths = {
                    shortenTo: rescue.shortenTo,
                    extendTo: rescue.extendTo
                };
            }
        }
        // In the results, set leftSideGap/rightSideGap to panelGapsValue if they were dynamic
        if (secondPass.results) {
            if (leftDynamic) secondPass.results.leftSideGap = secondPass.results.panelGapsValue;
            if (rightDynamic) secondPass.results.rightSideGap = secondPass.results.panelGapsValue;
        }
        return secondPass;
    } else {
        return firstPass;
    }
}

// The original calculation logic, now as a helper function
function _calculateGlassFencingInternal(config, leftEndGap, rightEndGap, leftDynamic, rightDynamic) {
    const {
        overallLength,
        leftRakedPanel,
        rightRakedPanel,
        gate,
        leftEndAttachment,
        rightEndAttachment,
        panelSettings,
    } = config;

    const { maxPanelSize, minPanelSize, panelGap, panelSizeIncrement, defaultPanelSize, maxPanelSpacing } = panelSettings;
    // Panel-to-panel clamp enforcement (GlassPoolCalc.calculatePanels sets both only once the
    // customer accepted the gap adjustment; absent -> 0 -> every test below is a no-op).
    const minPanelGap = Number(panelSettings.minPanelGap) || 0;
    const clampMaxGap = Number(panelSettings.clampMaxGapMm) || 0;

    // 1. Calculate end gaps and gate gaps
    // leftEndGap and rightEndGap are passed in
    const gateGaps = gate?.active ? { left: gate?.hingeType?.left, right: gate?.hingeType?.right } : { left: 0, right: 0 };
    const totalGateGaps = gateGaps.left + gateGaps.right;

    // 2. Account for all fixed-size elements
    const fixedElements = [];
    if (leftRakedPanel?.active) {
        fixedElements.push({ size: leftRakedPanel.size, type: 'raked' });
    }
    if (rightRakedPanel?.active) {
        fixedElements.push({ size: rightRakedPanel.size, type: 'raked' });
    }

    if (gate?.active) {
        // Only include hingePanelSize if hingePanelActive is true
        const hingePanelSize = gate.hingePanelActive ? (gate.hingePanelSize || 0) : 0;
        const gateAssemblySize = gate.gateSize + hingePanelSize + totalGateGaps;
        fixedElements.push({ size: gateAssemblySize, type: 'gate' });
    }
    const fixedElementsTotalLength = fixedElements.reduce((sum, el) => sum + el.size, 0);
    numFixedElements = fixedElements.length;

    // 3. Calculate length available for regular panels, using the user-defined panelGap for an initial estimate.
    // If left or right end is dynamic, do NOT subtract it from the available length (it will be distributed as a gap)
    let lengthToFill = overallLength
        - (leftDynamic ? 0 : leftEndGap)
        - (rightDynamic ? 0 : rightEndGap)
        - fixedElementsTotalLength
        - (numFixedElements * panelGap);
    if (numFixedElements === 0) {
        lengthToFill = overallLength - (leftDynamic ? 0 : leftEndGap) - (rightDynamic ? 0 : rightEndGap);
    }

    // Millimetres left after ends + gate/raked glass (no pessimistic panel-gap reserve).
    // lengthToFill subtracts numFixedElements*panelGap as a worst-case layout estimate, which can go
    // strongly negative on a valid tight run (e.g. end 9 + gap 10 + gate 890 = overall 909) even
    // though slack is only ~10 mm — all gap, no infill panels.
    const slack = overallLength
        - (leftDynamic ? 0 : leftEndGap)
        - (rightDynamic ? 0 : rightEndGap)
        - fixedElementsTotalLength;

    const lengthTolMm = 0.5;
    const zeroRegularGlassResult = () => ({
        inputs: config,
        results: {
            overallLength: overallLength,
            longPanels: { count: 0, size: 0 },
            shortPanel: { count: 0, size: 0 },
            leftRakedSize: leftRakedPanel?.active ? leftRakedPanel.size : 0,
            rightRakedSize: rightRakedPanel?.active ? rightRakedPanel.size : 0,
            gateSize: gate?.active ? gate.gateSize : 0,
            hingeSize: gate?.active && gate.hingePanelActive ? (gate.hingePanelSize || 0) : 0,
            gateGaps: { left: gateGaps.left, right: gateGaps.right },
            leftSideGap: leftEndGap,
            rightSideGap: rightEndGap,
            panelGapsValue: panelGap,
            panelGapsCount: Math.max(0, numFixedElements),
            numberOfPanelGaps: 0,
            panelsCount: 0,
        },
        calculationDetails: {
            lengthAvailableForRegularPanels: 0,
            finalAdjustedGap: panelGap,
            fixedElementsTotalLength: fixedElementsTotalLength,
            zeroRegularPanels: true,
        },
    });

    if (!leftDynamic && !rightDynamic) {
        if (slack < -lengthTolMm) {
            return {
                inputs: config,
                results: null,
                calculationDetails: { error: "No initial panel solutions were found. Please update accordingly." },
            };
        }
        const reservedNominalGaps = numFixedElements * panelGap + lengthTolMm + 1;
        const fitsZeroRegularPanels =
            slack <= lengthTolMm
            || (numFixedElements > 0 && slack < minPanelSize && slack <= reservedNominalGaps);
        if (fitsZeroRegularPanels) {
            return zeroRegularGlassResult();
        }
    }

    // 4. Get ALL possible ideal panel configurations
    let possibleLayouts = findAllPossiblePanelLayouts({
        lengthToFill,
        panelGap,
        maxPanelSize,
        minPanelSize,
        preferredPanelSize: defaultPanelSize,
    });
    // Filter layouts to only those where the calculated gap is > 0 and <= panelGap and panel size <= defaultPanelSize
    let filteredLayouts = possibleLayouts.filter(layout => {
        const n = layout.longPanels.count;
        const totalPanelLength = n * layout.longPanels.size;
        let totalGapsCount = n - 1;
        if (leftDynamic) totalGapsCount++;
        if (rightDynamic) totalGapsCount++;
        const totalGapsLength = overallLength - fixedElementsTotalLength - totalPanelLength;
        const gapValue = totalGapsCount > 0 ? totalGapsLength / totalGapsCount : 0;
        // Panel size must not exceed defaultPanelSize
        return gapValue > 0 && gapValue <= panelGap && layout.longPanels.size <= defaultPanelSize;
    });

    // If no valid layouts, relax the gap constraint incrementally (up to +50mm)
    let relaxedPanelGap = panelGap;
    let relaxStep = 10;
    let relaxLimit = 50;
    while (filteredLayouts.length === 0 && relaxedPanelGap <= panelGap + relaxLimit) {
        relaxedPanelGap += relaxStep;
        filteredLayouts = possibleLayouts.filter(layout => {
            const n = layout.longPanels.count;
            const totalPanelLength = n * layout.longPanels.size;
            let totalGapsCount = n - 1;
            if (leftDynamic) totalGapsCount++;
            if (rightDynamic) totalGapsCount++;
            const totalGapsLength = overallLength - fixedElementsTotalLength - totalPanelLength;
            const gapValue = totalGapsCount > 0 ? totalGapsLength / totalGapsCount : 0;
            // Panel size must not exceed defaultPanelSize
            return gapValue > 0 && gapValue <= relaxedPanelGap && layout.longPanels.size <= defaultPanelSize;
        });
    }
    possibleLayouts = filteredLayouts;

    if (!possibleLayouts || possibleLayouts.length === 0) {
        if (!leftDynamic && !rightDynamic) {
            const reservedNominalGaps = numFixedElements * panelGap + lengthTolMm + 1;
            const fitsZeroRegularPanels =
                slack <= lengthTolMm
                || (numFixedElements > 0 && slack < minPanelSize && slack <= reservedNominalGaps);
            if (fitsZeroRegularPanels && slack >= -lengthTolMm) {
                return zeroRegularGlassResult();
            }
        }
        return { inputs: config, results: null, calculationDetails: { error: "No initial panel solutions were found. Please update accordingly." } };
    }

    // 5. Iterate through layouts to find one that satisfies the maxPanelSpacing constraint after rounding.
    // The loop body is the original, lifted into pickLayout() so the clamp-widened pass below can
    // re-run it over a wider candidate list with its own ceilings; the strict call keeps today's
    // arguments and today's output.
    let finalResult = null;
    let finalCalculationDetails = {};
    // Set when the clamp minimum is what discards a layout - the widened pass below runs only
    // then, so a run the strict pass would have failed anyway stays on the baseline's path.
    let minRejected = false;

    const pickLayout = (layouts, gapCeiling, spacingCeiling) => {
        let finalResult = null;
        let finalCalculationDetails = {};

        for (const layout of layouts) {
            const longPanelCount = layout.longPanels.count;
            const shortPanelCount = layout.shortPanel.count;
            const numRegularPanels = longPanelCount + shortPanelCount;

            const roundedLongPanelSize = Math.round(layout.longPanels.size / panelSizeIncrement) * panelSizeIncrement;
            const roundedShortPanelSize = shortPanelCount > 0 ? Math.round(layout.shortPanel.size / panelSizeIncrement) * panelSizeIncrement : 0;

            const finalRegularPanelsLength = (longPanelCount * roundedLongPanelSize) + (shortPanelCount * roundedShortPanelSize);
            const finalTotalPanelLength = fixedElementsTotalLength + finalRegularPanelsLength;

            // --- FIX: Calculate total number of elements (panels + fixed elements) ---
            // Each panel and each fixed element is separated by a gap, except at the ends unless end attachments are specified.
            // Treat hinge panel as a regular panel for gap calculation
            let hingePanelAsPanel = 0;
            if (gate?.active && gate.hingePanelActive && gate.hingePanelSize > 0) {
                hingePanelAsPanel = 1;
            }
            const totalElements = numRegularPanels + numFixedElements + hingePanelAsPanel;
            let totalGapsCount = totalElements - 1;
            // Only a DYNAMIC end takes a share of the leftover below. Its width is the solver's to
            // choose, so it divides the remainder alongside the internal gaps. A fixed end (90deg,
            // angled or wall clamp) is a known width: it has already been taken out of
            // totalLengthForGaps and is drawn at its own size, so counting it here as well would hand
            // it a second share and starve every real gap - the run then lands short of the ordered
            // length by roughly one gap per fixed end. This matches findAllPossiblePanelLayouts'
            // filter above, which has always counted dynamic ends only.
            if (leftDynamic) totalGapsCount++;
            if (rightDynamic) totalGapsCount++;
            // If gate and hinge are active, reduce 2 gaps (gate has its own left/right gaps)
            if (gate?.active && gate.hingePanelActive) totalGapsCount -= 2;
            else if (gate?.active) totalGapsCount -= 2;

            // Calculate the total length available for gaps
            let totalLengthForGaps = overallLength - finalTotalPanelLength;
            if (!leftDynamic) totalLengthForGaps -= leftEndGap;
            if (!rightDynamic) totalLengthForGaps -= rightEndGap;

            const finalPanelGap = totalGapsCount > 0 ? totalLengthForGaps / totalGapsCount : 0;

            // Count left and right end gaps if their size is greater than 0 (or if dynamic)
            let numberOfPanelGaps = totalGapsCount;
            // (No need to add again for end gaps, already included above)

            // Enforce: panelGapsValue must be > 0 and <= panelGap
            if (finalPanelGap > gapCeiling || finalPanelGap <= 0) {
                continue; // Skip this layout, as it does not satisfy the gap constraint
            }
            // Panel-to-panel clamps need a gap the smallest clamp can close: a 13mm layout used to
            // be accepted and drawn with clamp bars no clamp fits. Rounded, because the diagram
            // prints Math.round of this gap and the clamp classifier reads that same figure - a
            // 19.6mm layout must be one thing everywhere (label 20, Small clamp, accepted).
            // Only where two glass elements meet: a layout with no panel-to-panel junction has
            // nothing to clamp, so the minimum has no say in it (the dynamic-end rescue gates
            // the same way) - 1237mm, dynamic left end / 25 right, no gate, spacing 30: the
            // baseline's single 1200 panel with a 12mm end gap was thrown out for five 200s.
            const glassElements = numRegularPanels + hingePanelAsPanel
                + (leftRakedPanel?.active ? 1 : 0) + (rightRakedPanel?.active ? 1 : 0);
            if (minPanelGap > 0 && glassElements >= 2 && Math.round(finalPanelGap) < minPanelGap) {
                minRejected = true;
                continue;
            }

            // Check the constraint
            if (finalPanelGap <= spacingCeiling) {
                // Found a valid solution that meets the spacing criteria.
                finalResult = {
                    overallLength: overallLength,
                    longPanels: { count: longPanelCount, size: roundedLongPanelSize },
                    shortPanel: { count: shortPanelCount, size: roundedShortPanelSize },
                    leftRakedSize: leftRakedPanel?.active ? leftRakedPanel.size : 0,
                    rightRakedSize: rightRakedPanel?.active ? rightRakedPanel.size : 0,
                    gateSize: gate?.active ? gate.gateSize : 0,
                    // Only include hingeSize if hingePanelActive is true
                    hingeSize: gate?.active && gate.hingePanelActive ? (gate.hingePanelSize || 0) : 0,
                    gateGaps: { left: gateGaps.left, right: gateGaps.right },
                    leftSideGap: leftDynamic ? finalPanelGap : leftEndGap,
                    rightSideGap: rightDynamic ? finalPanelGap : rightEndGap,
                    panelGapsValue: finalPanelGap,
                    panelGapsCount: totalGapsCount,
                    numberOfPanelGaps: numberOfPanelGaps,
                    panelsCount: shortPanelCount + longPanelCount,
                };

                finalCalculationDetails = {
                    lengthAvailableForRegularPanels: lengthToFill,
                    finalAdjustedGap: finalPanelGap,
                    fixedElementsTotalLength: fixedElementsTotalLength,
                };

                // Use the first valid solution found (which will have the fewest panels).
                break;
            }
        }

        return finalResult ? { finalResult, finalCalculationDetails } : null;
    };

    // Enforced runs (the customer accepted Auto Adjustment) choose the FEWEST-panel layout in the
    // clamp's own window, ahead of the preferred-spacing pass below. Every panel is two more
    // spigots and another clamp while the glass is priced per m2 either way, so panel count is
    // what the adjustment costs: OA 2000 at spacing 30 was six 300s @ 28.6mm when 2 x 950 @
    // 33.3mm builds the same 2.16m2 of glass on four spigots instead of twelve. That does mean
    // an enforced run may land just outside Max Panel Spacing - accepting the adjustment is the
    // customer trading their spacing preference for clamps, and the dialog shows the resulting
    // gap, panel count and clamp size before they commit.
    //
    // Candidates are enumerated here rather than taken from findAllPossiblePanelLayouts, which
    // offers exactly ONE width per panel count - the width the preferred spacing implies. That
    // width often rounds to a dead end while a neighbouring increment is perfectly buildable:
    // two panels at spacing 30 compute to 985mm, which rounds to 1000 and leaves no gap at all,
    // so two panels looked impossible. Every 50mm width is walked instead; within one panel
    // count the gap nearest the customer's spacing wins, and the outer loop stops at the first
    // count that yields anything - no larger count can beat it.
    //
    // Ceiling: the clamp's maximum, but never past maxPanelSpacing - 80 is the planner's
    // documented hard gap ceiling (the dynamic-end rescue caps there too), and the Large clamp's
    // 95 must not draw a gap the planner refuses everywhere else.
    const clampWindowCeiling = Math.min(clampMaxGap, maxPanelSpacing);

    /**
     * Fewest-panel layout within a gap ceiling. Candidates are enumerated here rather than taken
     * from findAllPossiblePanelLayouts, which offers exactly ONE width per panel count - the
     * width the preferred spacing implies. That width often rounds to a dead end while a
     * neighbouring increment is perfectly buildable: two panels over 2000mm at spacing 30 compute
     * to 985mm, which rounds to 1000 and leaves no gap at all, so two panels looked impossible
     * when 2 x 950 @ 33.3mm was there all along. Every 50mm width is walked instead; within one
     * panel count the gap nearest the customer's spacing wins, and the loop stops at the first
     * count that yields anything - no larger count can beat it.
     */
    const fewestPanelLayout = (gapCeiling, spacingCeiling, fromCount) => {
        const widestPanel = Math.floor(Math.min(maxPanelSize, defaultPanelSize) / panelSizeIncrement) * panelSizeIncrement;
        const mostPanels = Math.min(60, Math.floor(overallLength / minPanelSize) + 1);
        for (let count = fromCount; count <= mostPanels; count++) {
            let bestForCount = null;
            for (let size = widestPanel; size >= minPanelSize; size -= panelSizeIncrement) {
                const candidate = pickLayout(
                    [{ longPanels: { count: count, size: size }, shortPanel: { count: 0, size: 0 } }],
                    gapCeiling,
                    spacingCeiling
                );
                if (!candidate) continue;
                if (
                    !bestForCount ||
                    Math.abs(candidate.finalResult.panelGapsValue - panelGap)
                        < Math.abs(bestForCount.finalResult.panelGapsValue - panelGap)
                ) {
                    bestForCount = candidate;
                }
            }
            if (bestForCount) {
                return bestForCount;
            }
        }
        return null;
    };

    // Preferred-spacing pass first - it is what sets `minRejected`, which decides whether the
    // clamp window below may open.
    let picked = pickLayout(possibleLayouts, panelGap, maxPanelSpacing);

    // Fewest panels, on every glass solve, clamps or not: each panel is two more spigots (and,
    // with clamps, another clamp) while the glass is priced per m2 either way, so panel count is
    // what the customer pays for. Max Panel Spacing stays a HARD cap here - this only finds the
    // layouts the one-width-per-count generator could not see. Strictly fewer panels only, so an
    // equal-count layout is never swapped for a different width (owner's call, Sept 2026).
    // Only ever improves a pick the loop already made: when the loop finds nothing the run
    // belongs to the dynamic-end rescue, and pre-empting that changed edge lengths the rescue
    // has always owned (900mm with one fixed end) for no gain in panel count.
    if (picked) {
        const fewestWithinSpacing = fewestPanelLayout(panelGap, maxPanelSpacing, 1);
        if (fewestWithinSpacing) {
            const fewer = fewestWithinSpacing.finalResult.panelsCount < picked.finalResult.panelsCount;
            // On a tie, the gap closest to what the customer actually set wins. The generator's
            // one width per panel count often lands far under the setting when a nearer width
            // exists at the same count: 3200mm at spacing 50 was three 1050s with a 12.5mm gap
            // when three 1000s give exactly the 50 asked for - same panel count, less glass.
            const sameCountButNearer =
                fewestWithinSpacing.finalResult.panelsCount === picked.finalResult.panelsCount &&
                Math.abs(fewestWithinSpacing.finalResult.panelGapsValue - panelGap)
                    < Math.abs(picked.finalResult.panelGapsValue - panelGap);
            if (fewer || sameCountButNearer) {
                picked = fewestWithinSpacing;
            }
        }
    }

    // Clamps enforced, and only when the minimum actually discarded a layout: the fence had to
    // change anyway, so the window opens to the clamp's own range - which may sit just outside
    // Max Panel Spacing. Accepting Auto Adjustment is the customer trading that preference for
    // clamps, and the dialog shows the gap, panel count and clamp size before they commit.
    // Without the `minRejected` gate this also re-arranged runs that were already clamp-legal,
    // and split a single-panel run (1237mm, one fixed end) into two to manufacture a junction.
    const otherGlass =
        (gate?.active && gate.hingePanelActive && gate.hingePanelSize > 0 ? 1 : 0) +
        (leftRakedPanel?.active ? 1 : 0) +
        (rightRakedPanel?.active ? 1 : 0);
    // ...and only when the fence being shipped actually HAS a glass-to-glass junction. A layout
    // that is one panel between two end gaps has nothing to clamp, and `minRejected` alone is
    // not that test - it goes true as soon as any multi-panel candidate was discarded, so a
    // single-panel run (937mm, both ends dynamic: one 900 panel) was being split into two just
    // to manufacture somewhere to put a clamp. When the loop picked nothing at all the run is
    // headed for the rescue, and the clamp window is its chance to find a real layout first.
    const pickedHasJunction = picked ? (picked.finalResult.panelsCount + otherGlass) >= 2 : true;
    if (minPanelGap > 0 && minRejected && pickedHasJunction) {
        // Keep the preferred-spacing pick when nothing clampable exists at all.
        const fewest = fewestPanelLayout(clampWindowCeiling, clampWindowCeiling, Math.max(1, 2 - otherGlass));
        if (fewest) {
            picked = fewest;
        }
    }

    if (picked) {
        finalResult = picked.finalResult;
        finalCalculationDetails = picked.finalCalculationDetails;
    }

    if (!finalResult) {
        return {
            inputs: config,
            results: null,
            calculationDetails: {
                // Name the minimum only when it is what rejected something: a run with no
                // candidate inside the preferred spacing to begin with fails for the spacing alone.
                error: (minPanelGap > 0 && minRejected)
                    ? `No solution found that satisfies the max panel spacing of ${panelGap}mm and the ${minPanelGap}mm minimum gap for panel-to-panel clamps.`
                    : `No solution found that satisfies the max panel spacing of ${panelGap}mm.`
            }
        };
    }

    // 6. Return the validated result
    return {
        inputs: config,
        results: finalResult,
        calculationDetails: finalCalculationDetails,
    };
}

/**
 * Zero/one-panel layouts for runs whose slack the main solver cannot place, using the dynamic
 * end gaps as the flexible space they really are. Gap accounting mirrors the main loop for one
 * panel (element junctions minus the gate's built-in hinge/latch gaps) and the physical count
 * for zero panels (each dynamic end plus the junctions between fixed elements). Gaps are capped
 * at maxPanelSpacing - the documented ceiling - not the preferred panelGap, because this path
 * only runs when the preferred spacing has already failed to produce any layout.
 * Returns {ok:true, result} on success, {ok:false, shortenTo, extendTo} when the length falls in
 * the genuine dead zone between "gaps can stretch no further" and "smallest panel fits", or
 * null when no dynamic end exists (fixed-end behaviour is unchanged).
 */
function _fcGlassDynamicEndRescue(config, leftDynamic, rightDynamic) {
    const dyn = (leftDynamic ? 1 : 0) + (rightDynamic ? 1 : 0);
    if (!dyn) return null;
    const ps = config.panelSettings;
    const gate = config.gate;
    const gateGaps = gate?.active ? { left: gate?.hingeType?.left || 0, right: gate?.hingeType?.right || 0 } : { left: 0, right: 0 };

    const fixedSizes = [];
    if (config.leftRakedPanel?.active) fixedSizes.push(config.leftRakedPanel.size);
    if (config.rightRakedPanel?.active) fixedSizes.push(config.rightRakedPanel.size);
    let hingeAsPanel = 0;
    if (gate?.active) {
        const hingePanelSize = gate.hingePanelActive ? (gate.hingePanelSize || 0) : 0;
        fixedSizes.push(gate.gateSize + hingePanelSize + gateGaps.left + gateGaps.right);
        if (gate.hingePanelActive && gate.hingePanelSize > 0) hingeAsPanel = 1;
    }
    const numFixed = fixedSizes.length;
    const fixedTotal = fixedSizes.reduce(function (a, b) { return a + b; }, 0);
    const leftFixedGap = leftDynamic ? 0 : (Number(config.leftEndAttachment?.size) || 0);
    const rightFixedGap = rightDynamic ? 0 : (Number(config.rightEndAttachment?.size) || 0);
    const overall = config.overallLength;
    const slack = overall - fixedTotal - leftFixedGap - rightFixedGap;
    const cap = ps.maxPanelSpacing;
    // Clamp minimum, but only where a panel-to-panel junction exists: zero panels only meet
    // glass-to-glass when two fixed elements sit side by side, one panel when at least one
    // fixed element does. Enforcing it on a run with nothing to clamp would push the
    // customer's measured length through the auto-fit to add nothing.
    const minPanelGap = Number(ps.minPanelGap) || 0;

    const buildResult = function (count, size, gapVal, gapsCount) {
        return {
            ok: true,
            result: {
                inputs: config,
                results: {
                    overallLength: overall,
                    longPanels: { count: count, size: size },
                    shortPanel: { count: 0, size: 0 },
                    leftRakedSize: config.leftRakedPanel?.active ? config.leftRakedPanel.size : 0,
                    rightRakedSize: config.rightRakedPanel?.active ? config.rightRakedPanel.size : 0,
                    gateSize: gate?.active ? gate.gateSize : 0,
                    hingeSize: gate?.active && gate.hingePanelActive ? (gate.hingePanelSize || 0) : 0,
                    gateGaps: { left: gateGaps.left, right: gateGaps.right },
                    leftSideGap: leftDynamic ? gapVal : leftFixedGap,
                    rightSideGap: rightDynamic ? gapVal : rightFixedGap,
                    panelGapsValue: gapVal,
                    panelGapsCount: gapsCount,
                    numberOfPanelGaps: gapsCount,
                    panelsCount: count,
                },
                calculationDetails: {
                    lengthAvailableForRegularPanels: count > 0 ? slack : 0,
                    finalAdjustedGap: gapVal,
                    fixedElementsTotalLength: fixedTotal,
                    dynamicEndRescue: true,
                },
            },
        };
    };

    // Zero regular panels: slack becomes the dynamic end gaps (plus junctions between fixed
    // elements). Needs at least one fixed element - a run of nothing but gaps is not a fence.
    const gapsZero = dyn + Math.max(0, numFixed - 1);
    if (numFixed > 0 && gapsZero > 0) {
        const g0 = slack / gapsZero;
        if (g0 > 0 && g0 <= cap && (numFixed < 2 || minPanelGap === 0 || Math.round(g0) >= minPanelGap)) return buildResult(0, 0, g0, gapsZero);
    }

    // One panel, largest increment-multiple first so the panel takes the space and gaps stay small.
    const totalElementsOne = 1 + numFixed + hingeAsPanel;
    let gapsOne = totalElementsOne - 1;
    if (leftDynamic || leftFixedGap > 0) gapsOne++;
    if (rightDynamic || rightFixedGap > 0) gapsOne++;
    if (gate?.active) gapsOne -= 2;
    if (gapsOne > 0) {
        const sizeCap = Math.min(ps.maxPanelSize, ps.defaultPanelSize || ps.maxPanelSize);
        let sMax = Math.floor(slack / ps.panelSizeIncrement) * ps.panelSizeIncrement;
        if (sMax > sizeCap) sMax = sizeCap;
        for (let sizeMm = sMax; sizeMm >= ps.minPanelSize; sizeMm -= ps.panelSizeIncrement) {
            const g1 = (slack - sizeMm) / gapsOne;
            if (g1 > 0 && g1 <= cap && (numFixed < 1 || minPanelGap === 0 || Math.round(g1) >= minPanelGap)) return buildResult(1, sizeMm, g1, gapsOne);
        }
    }

    // Dead zone: report the nearest lengths that DO work so the customer can adjust.
    const shortenTo = (numFixed > 0 && gapsZero > 0) ? Math.floor(overall - (slack - gapsZero * cap)) : 0;
    const extendTo = gapsOne > 0 ? Math.ceil(overall + (ps.minPanelSize + gapsOne - slack)) : 0;
    return { ok: false, shortenTo: shortenTo, extendTo: extendTo };
}
