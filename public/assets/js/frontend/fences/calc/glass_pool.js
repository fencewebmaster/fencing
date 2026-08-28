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
    let finalResult = null;
    let finalCalculationDetails = {};

    for (const layout of possibleLayouts) {
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
        // Add end gaps if left or right end attachments are present (size > 0 or dynamic)
        if (leftDynamic || leftEndGap > 0) totalGapsCount++;
        if (rightDynamic || rightEndGap > 0) totalGapsCount++;
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
        if (finalPanelGap > panelGap || finalPanelGap <= 0) {
            continue; // Skip this layout, as it does not satisfy the gap constraint
        }

        // Check the constraint
        if (finalPanelGap <= maxPanelSpacing) {
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

    if (!finalResult) {
        return {
            inputs: config,
            results: null,
            calculationDetails: { error: `No solution found that satisfies the max panel spacing of ${panelGap}mm.` }
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
        if (g0 > 0 && g0 <= cap) return buildResult(0, 0, g0, gapsZero);
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
            if (g1 > 0 && g1 <= cap) return buildResult(1, sizeMm, g1, gapsOne);
        }
    }

    // Dead zone: report the nearest lengths that DO work so the customer can adjust.
    const shortenTo = (numFixed > 0 && gapsZero > 0) ? Math.floor(overall - (slack - gapsZero * cap)) : 0;
    const extendTo = gapsOne > 0 ? Math.ceil(overall + (ps.minPanelSize + gapsOne - slack)) : 0;
    return { ok: false, shortenTo: shortenTo, extendTo: extendTo };
}
