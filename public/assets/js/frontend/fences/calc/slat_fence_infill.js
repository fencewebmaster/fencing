/**
 * Slat Infill calculation module.
 *
 * The panel layout for Slat Infill, split out of calculate_fences() in fences/calc/calc.js. Infill
 * has no computed geometry: the entered opening width is the panel length, the user's
 * panel_count is the count, and gates, offcuts and raked panels are always zero (the Step 3
 * controls that would set them are disabled in 5-SLAT-FENCE-INFILL.php).
 *
 * Loaded by fence-scripts.php AFTER the main fences/*.js glob and attached onto
 * SlatFenceInfill below, so calculate_fences() calls SlatFenceInfill.calculatePanels().
 */

SlatFenceInfillCalc = {

    /**
     * Panel-layout values for one Slat Infill section, keyed by the exact variable names
     * calculate_fences() destructures into its returned plan.
     */
    calculatePanels: function(input) {
        var infillCount = Math.max(1, HELPER.isNaNtoZero(parseInt(input.panelCount, 10)));
        var infillOpeningWidth = Math.max(0, HELPER.isNaNtoZero(Math.round(input.openingWidthMm)));

        return {
            full_panel_count: infillCount,
            full_panel_length: infillOpeningWidth,
            even_panel_count: infillCount,
            even_panel_length: infillOpeningWidth,
            long_panel_count: infillCount,
            long_panel_length: infillOpeningWidth,
            offcut_panel_count: 0,
            offcut_panel_length: 0,
            gate_count: 0,
            gate_length: 0,
            gate_width: 0,
            gate_hinge_panel_count: 0,
            gate_hinge_panel_width: 0,
            left_raked_panel_height: 0,
            left_raked_panel_width: 0,
            right_raked_panel_height: 0,
            right_raked_panel_width: 0
        };
    }

};

// Attach onto SlatFenceInfill (fences/slat_fence_infill.js, loaded earlier in the same pass).
if (typeof SlatFenceInfill !== 'undefined' && SlatFenceInfill) {
    Object.keys(SlatFenceInfillCalc).forEach(function (key) {
        SlatFenceInfill[key] = SlatFenceInfillCalc[key];
    });
}
