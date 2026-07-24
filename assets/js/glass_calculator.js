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


