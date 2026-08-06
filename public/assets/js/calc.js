// Returns the value of a multi-option field for a given control_key and slug from custom_fence data
const get_field_multi_option_value = (custom_fence, info, control_key, slug) => {
    const custom_fence_data = custom_fence.filter(item => item.control_key == control_key);
    const settings = custom_fence_data[0]?.settings.filter(item => item.key == slug);
    return settings?.[0] ? settings[0] : settings;
};

//----------------------------------------------------------------------------------

// Returns the options data for a given control_key from custom_fence and info
const get_field_options = (custom_fence, info, control_key, field_slug = null) => {
    const custom_fence_data = custom_fence.filter(item => item.control_key == control_key);
    const infoCopy = JSON.parse(JSON.stringify(info));
    const fields = infoCopy?.settings?.[control_key]?.fields || [];
    let field = field_slug ? fields.find(item => item?.slug == field_slug) : null;
    if (!field) {
        field = fields.find(item => Array.isArray(item?.options));
    }
    if (!field) {
        return [];
    }

    const field_options = field.options || [];
    const selected_val = custom_fence_data[0]?.settings?.find(item => item?.key == (field_slug || field.slug))?.val
        ?? custom_fence_data[0]?.settings?.[0]?.val;
    let options_data = field_options.filter(item => item.slug == selected_val);

    if (!options_data.length) {
        options_data = field_options.filter(item => item.default);
    }

    return options_data;
};

//----------------------------------------------------------------------------------

// Returns all field options for a given control_key from custom_fence and info
const get_field_multi_options = (custom_fence, info, control_key) => {
    const custom_fence_data = custom_fence.filter(item => item.control_key == control_key);
    const field_options = info['settings'][control_key]['fields'];
    return field_options;
};

//----------------------------------------------------------------------------------

// Returns the field data from custom_fence by slug (supports "+" in slug)
const get_field_by_slug = (custom_fence, slug) => {
    const custom_fence_data = custom_fence.filter(item => {
        if(item.slug.includes('+')) {
            return item.slug.includes(slug);
        }
        return item.slug == slug;
    });
    return custom_fence_data?.[0] ? custom_fence_data[0] : custom_fence_data;
};

//----------------------------------------------------------------------------------

/*
 * Main calculation function for fence configuration.
 * - Reads user selections and configuration from localStorage and DOM
 * - Calculates panel counts, lengths, gate and raked panel sizes, spacing, and posts
 * - Handles different panel groups (e.g., glass, offcut logic)
 * - Returns a structured data object with all calculated values
 */
function calculate_fences(data) {
    // --- Initialization and input extraction ---
    // i: selected fence item slug
    // tab: selected tab index
    // custom_fence: user customizations for this tab/item
    // custom_fence_tab: user customizations for this tab
    let rawItem = data?.item != null ? data?.item : $('.fencing-style-item.fsi-selected').attr('data-slug');
    let tab = data?.tab != null ? data?.tab : $('.fencing-tab.fencing-tab-selected').index();
    let i = normalizeFenceStyleSlug(rawItem);
    let custom_fence = readCustomFenceSegment(tab, rawItem);
    let custom_fence_tab = localStorage.getItem('custom_fence-' + tab);
    custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

    if (!i) return;

    let info = fc_data[i];

    // --- Inputs (C3-C10) ---
    /*
        C3  = 11000;  // overall width
        C4  = -50;    // edit left side
        C5  = 2450;   // panel options
        C6  = 0;      // post options
        C7  = 0;      // edit right side
        C8  = 1060;   // add gate
        C9  = 1250;   // add raked panel left
        C10 = 1250;   // add raked panel right 
    */

    // --- Variable declarations for calculation ---
    let C3 = 0, C4 = 0, C5 = 0, C6 = 0, C7 = 0, C8 = 0, C9 = 0, C10 = 0;
    let left_raked_panel_height = 0;
    let left_raked_panel_width = 0;
    let right_raked_panel_height = 0;
    let right_raked_panel_width = 0;
    let short_panel_count = 0;
    let short_panel_length = 0;
    let _short_panel_length = 0;
    let offcut_gate_panel_length = 0;
    let offcut_gate_panel_count = 0;
    let left_raked_count = 0;
    let right_raked_count = 0;
    let gate_hinge_panel_count = 0;
    let gate_hinge_panel_width = 0;

    // --- Get configuration values from helpers and info ---
    const gate_post_gaps = FENCE.get(i, 'gate_post_gaps');
    const post_panel = FENCE.get(i, 'post');
    const no_post = -post_panel;
    let fence_height = '';
    let max_fence_height = '';
    let panel_width_height_key = '';

    // --- Fence height selection logic ---
    if (info?.form) {
        if (SlatFence.isSlatLike(i)) {
            const slatHeights = SlatFence.resolveCalcFenceHeights(custom_fence_tab);
            max_fence_height = slatHeights.maxFenceHeight;
            fence_height = slatHeights.fenceHeight;
            panel_width_height_key = slatHeights.panelWidthHeightKey;
        } else {
            var fhStored =
                typeof fcReadTabRowStep2Field === 'function' && custom_fence_tab[0]
                    ? fcReadTabRowStep2Field(custom_fence_tab[0], i, 'fence_height')
                    : '';
            fence_height = parseInt(fhStored, 10);
            if (!Number.isFinite(fence_height)) {
                fence_height = parseInt($('[data-section="2"] [name="fence_height"]').val(), 10);
            }
            if (!Number.isFinite(fence_height)) {
                fence_height = parseInt($('[name="fence_height"]').val(), 10);
            }
            const filtered_fence_height = custom_fence_tab[0]?.fields?.filter(item => item.name == 'fence_height');
            let filtered_fence_height_value = '';
            if (filtered_fence_height && filtered_fence_height[0]) {
                filtered_fence_height_value = filtered_fence_height[0]?.value;
            }
            if (!Number.isFinite(fence_height) && filtered_fence_height_value) {
                fence_height = parseInt(filtered_fence_height_value, 10);
            }
            panel_width_height_key = fence_height;
        }
    }

    // --- Main input: overall width ---
    if (typeof fcReadCalculateValueForStyle === 'function' && custom_fence_tab[0]) {
        var cvStyle = fcReadCalculateValueForStyle(custom_fence_tab[0], i);
        if (cvStyle !== '' && cvStyle !== null && cvStyle !== undefined) {
            C3 = parseInt(String(cvStyle).replace(/,/g, ''), 10);
        }
    }
    if (!Number.isFinite(C3) || C3 <= 0) {
        C3 = parseInt($('.measurement-box-number').val(), 10);
    }

    if (
        typeof SlatFence !== 'undefined' &&
        SlatFence.isMainSlatSlug(i) &&
        (!Number.isFinite(C3) || C3 <= 0 || C3 === 9999)
    ) {
        var fdGoCalc = {
            slug: i,
            info: custom_fence,
            data: info,
            tabInfo: custom_fence_tab
        };
        if (SlatFence.shouldLockStep2OverallForStdGateOnly(fdGoCalc)) {
            var effGo = SlatFence.computeSlatGateOnlyStdOverallEffectiveMm(fdGoCalc);
            if (Number.isFinite(effGo) && effGo > 0) {
                C3 = effGo;
            }
        }
    }

    let panel_count = parseInt($('[name="panel_count"]').val(), 10);
    const filtered_panel_count = custom_fence_tab[0]?.fields?.filter(item => item.name == 'panel_count');
    const filtered_panel_count_value = filtered_panel_count?.[0]?.value;
    panel_count = Number.isFinite(panel_count) ? panel_count : parseInt(filtered_panel_count_value, 10);
    if (!Number.isFinite(panel_count)) {
        panel_count = parseInt(info?.panel_count, 10);
    }

    C3 = SlatFence.applyCalcOverallOffset(i, C3, custom_fence, custom_fence_tab);

    // --- Panel options (width, etc.) ---
    let panel_options_data = get_field_options(custom_fence, info, 'panel_options', 'panel_option');

    const edit_spacing = custom_fence.filter(item => item.control_key == 'edit_spacing');
    // `edit_spacing` is a glass-only spacing input in the existing planner. Slat Fence uses an
    // Edit Spacing button for slat gap, so keep panel spacing default for non-glass fences.
    const max_panel_spacing = (i === 'glass_pool' && edit_spacing.length) ? parseInt(edit_spacing[0].settings[0].val) : 50;

    // --- Panel width selection logic ---
    if (Array.isArray(panel_options_data)) {
        panel_options_data = panel_options_data[0];
    }

    if (!panel_options_data && i === 'slat_fence_infill') {
        panel_options_data = {
            slug: 'even',
            size: {
                default: C3,
                width: C3,
            }
        };
    }

    C5 = panel_options_data?.size?.width; // panel options
    if (C5 == undefined) {

        panel_options_data = info.settings.panel_options.fields[0].options.filter(function(item) {
            return item.default;
        });

        if (Array.isArray(panel_options_data)) {
            panel_options_data = panel_options_data[0];
        }

        C5 = panel_options_data.size?.width;
    }
    
    if( info.panel_group == 'a' ) {
	    C5 = panel_options_data?.size?.default;
	    if (C5 === undefined) {
	        panel_options_data = info.settings.panel_options.fields[0].options.filter(item => item.default);
	        if (Array.isArray(panel_options_data)) {
	            panel_options_data = panel_options_data[0];
	        }
	        C5 = panel_options_data.size?.default;
	    }
	}

    const default_panel_width = panel_options_data?.size?.default;

    // --- Handle width-based height for panels ---
    if (panel_options_data?.size?.width_based_height) {
        const panel_opts = panel_options_data?.size?.width_based_height;
        C5 = panel_opts?.[panel_width_height_key || fence_height];
    }

    // --- Custom panel width override ---
    const panel_options_custom = custom_fence.filter(item => item.control_key == 'panel_options_custom');
    if(panel_options_custom.length) {
        C5 = panel_options_custom[0]?.settings?.[0].val;
    }

    C6 = 0; // post options

    // --- Gate calculation ---
    const gate_data = custom_fence.filter(item => item.control_key == 'gate');
    if (gate_data.length) {
        if (gate_data[0]?.settings.size) {
            let gateSize = parseInt(gate_data[0]?.settings.size, 10);

            // Slat Fence: Gate Options "Width Dimension From" applies to gate width input.
            // - Center-line (-1): subtract 1x post width from entered measurement to get gate panel width.
            // - Outside (-2): subtract 2x post width.
            gateSize = SlatFence.getCalcGateSpanMm(i, gateSize, gate_data, post_panel);

            C8 = gateSize + gate_post_gaps;
            offcut_gate_panel_length = (C5 - post_panel) - C8 + gate_post_gaps;
            offcut_gate_panel_count = 0;
            const isCustomGate = gate_data[0]?.settings?.fields?.filter(item => item.key == 'use_std' && item.val == false);
            if (isCustomGate[0]) {
                offcut_gate_panel_count = 1;
            }

        } else {
            C8 = parseInt(info.settings.gate.size.width) + post_panel + 20 + 20;
        }
    }

    let full_panel_count = 0;
    let full_panel_length = 0;
    let even_panel_count = 0;
    let even_panel_length = 0;
    let long_panel_count = 0;
    let long_panel_length = 0;
    let offcut_panel_count = 0;
    let offcut_panel_length = 0;
    let _offcut_panel_length = 0;

    let gate_hinge_panel = gate_data[0]?.settings?.fields.find(item => item.key == 'gate_hinge_panel_width');
    let gate_placement = gate_data[0]?.settings?.placement;
    let gate_position = gate_data[0]?.settings?.position;
    let gate_swing = gate_data[0]?.settings?.fields.find(item => item.key == 'gate_hinge_position');
    let gateOnly = gate_data[0]?.settings?.gateOnly;
    let gate_count = 0;
    let gate_length = 0;
    let gate_width = 0;
    let extra_panel_count = 0;


    // --- Raked panel left calculation ---
    let step_up_panels = get_field_multi_options(custom_fence, info, 'left_side');
    step_up_panels = get_field_by_slug(step_up_panels, 'left_raked');
    let step_up_panels_data = get_field_multi_option_value(custom_fence, info, 'left_side', 'left_raked');
    step_up_panels = get_field_by_slug(step_up_panels.options, step_up_panels_data?.val);
    if (step_up_panels.length != 0) {
        C9 = step_up_panels?.size?.width;
    }
    left_raked_panel_height = step_up_panels?.size?.height;
    left_raked_panel_width = isNaN(C9 - 50) ? 0 : C9 - 50;

    // --- Raked panel right calculation ---
    step_up_panels = get_field_multi_options(custom_fence, info, 'right_side');
    step_up_panels = get_field_by_slug(step_up_panels, 'right_raked');
    step_up_panels_data = get_field_multi_option_value(custom_fence, info, 'right_side', 'right_raked');
    step_up_panels = get_field_by_slug(step_up_panels.options, step_up_panels_data?.val);
    if (step_up_panels.length != 0) {
        C10 = step_up_panels?.size?.width;
    }
    right_raked_panel_height = step_up_panels?.size?.height;
    right_raked_panel_width = isNaN(C10 - 50) ? 0 : C10 - 50;

    // --- Main calculation for panel counts, lengths, and spacing ---
    if (i === 'slat_fence_infill') {
        const infillCount = Math.max(1, HELPER.isNaNtoZero(parseInt(panel_count, 10)));
        const infillOpeningWidth = Math.max(0, HELPER.isNaNtoZero(Math.round(C3)));

        full_panel_count = infillCount;
        full_panel_length = infillOpeningWidth;
        even_panel_count = infillCount;
        even_panel_length = infillOpeningWidth;
        long_panel_count = infillCount;
        long_panel_length = infillOpeningWidth;
        offcut_panel_count = 0;
        offcut_panel_length = 0;
        gate_count = 0;
        gate_length = 0;
        gate_width = 0;
        gate_hinge_panel_count = 0;
        gate_hinge_panel_width = 0;
        left_raked_panel_height = 0;
        left_raked_panel_width = 0;
        right_raked_panel_height = 0;
        right_raked_panel_width = 0;
    } else {
        const spanOverall = C3 - C8 - C9 - C10;
        let C14 = spanOverall - post_panel;
        let C15 = C3 + C7 + C4;
        let C16 = Math.ceil(C14 / C5);
        let C17 = C16;
        let C18 = Math.floor(C14 / C5);

        // Main Slat: panel count from effective infill span; skip panels inside gate footprint.
        if (
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(i) &&
            !gateOnly
        ) {
            const rawSlatOv = SlatFence.readSlatRawOverallMm(custom_fence_tab, i);
            const withinGateOnly =
                gate_data.length &&
                SlatFence.isWithinGateOnlyOverallRange(
                    i,
                    gate_data,
                    custom_fence,
                    custom_fence_tab,
                    C8,
                    C9,
                    C10,
                    null
                );
            const slatSpan = Number.isFinite(rawSlatOv)
                ? SlatFence.getSlatInfillSpanMm(i, rawSlatOv, C8, C9, C10, custom_fence, custom_fence_tab)
                : spanOverall;
            const slatRemovedPosts = SlatFence.getRemovedEndPostsMm(custom_fence);

            if (withinGateOnly) {
                C16 = 0;
                C17 = 0;
                C18 = 0;
                C14 = 0;
            } else if (Number.isFinite(slatSpan) && slatSpan > 0) {
                const slatMaxSpan = SlatFence.getMaxPanelSpanMmFromInfo(info);
                const slatPanelQty = SlatFence.countEvenPanelsFromOverallSpan(
                    slatSpan,
                    slatMaxSpan,
                    post_panel,
                    info,
                    slatRemovedPosts
                );
                C16 = slatPanelQty;
                C17 = slatPanelQty;
                C18 = slatPanelQty;
                // slatSpan already excludes both end posts (Width Dimension From offset).
                C14 = SlatFence.computeSlatPanelSpanMm(
                    slatSpan,
                    slatPanelQty,
                    post_panel,
                    slatRemovedPosts
                );
            }
        }

        let C21 = C8 > 0 ? 1 : 0;
        let D16 = C14 / C5;
        let D17 = Math.round(C14 / C17);
        let D18 = C5;
        let D19 = (D16 - C18) * D18; // E19
        let D21 = C8;
        let D22 = C9 > 0 ? 1 : 0;
        let D23 = C10 > 0 ? 1 : 0;
        let D24 = D22 < 1 ? (C4 < 0 ? -1 : 0) : 0;
        let E17 = D17 - post_panel;
        let E18 = D18 - post_panel;
        let E19 = D19 < post_panel ? 0 : D19 - post_panel; // E19
        let E21 = D21 - post_panel;
        let E22 = C9 > 0 ? (C4 < 0 ? 1 : 0) : 0;
        let E23 = C10 > 0 ? (C7 < 0 ? 1 : 0) : 0;
        let E24 = D23 < 1 ? (C7 < 0 ? -1 : 0) : 0;
        let C19 = E19 < 1 ? 0 : 1;

        // C20 = C19;
        let C20 = (panel_options_data?.slug?.includes('even') || panel_options_data?.slug === undefined) ? C17 : C19;

        // D20 = D19 < post_panel ? 0 : E18 - E19;
        let D20 = panel_options_data?.slug?.includes('even') ? (E17 ? E18 - E17 : 0) : (D19 < post_panel ? 0 : E18 - E19);


        // Outputs
        full_panel_count = isNaN(C18) ? 0 : C18;
        full_panel_length = isNaN(E18) ? 0 : E18;

        even_panel_count = isNaN(C17) ? 0 : C17;
        even_panel_length = isNaN(E17) ? 0 : E17;

        // Slat Fence (main style): ceil((overall − totalPosts) / N) — includes no-post end adjustments.
        if (
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(i) &&
            (panel_options_data?.slug?.includes('even') || panel_options_data?.slug === undefined) &&
            !gateOnly &&
            even_panel_count > 0 &&
            !(
                gate_data.length &&
                SlatFence.isWithinGateOnlyOverallRange(
                    i,
                    gate_data,
                    custom_fence,
                    custom_fence_tab,
                    C8,
                    C9,
                    C10,
                    null
                )
            )
        ) {
            const rawSlatOv = SlatFence.readSlatRawOverallMm(custom_fence_tab, i);
            const slatRemovedPosts = SlatFence.getRemovedEndPostsMm(custom_fence);
            if (Number.isFinite(rawSlatOv)) {
                const slatSpan = SlatFence.getSlatInfillSpanMm(
                    i,
                    rawSlatOv,
                    C8,
                    C9,
                    C10,
                    custom_fence,
                    custom_fence_tab
                );
                even_panel_length = SlatFence.computeSlatEvenPanelWidthMm(
                    slatSpan,
                    even_panel_count,
                    post_panel,
                    slatRemovedPosts
                );
            }
        }

        if( !gateOnly && gate_hinge_panel ) {
            gate_hinge_panel_count = 1;
            gate_hinge_panel_width = parseInt(gate_hinge_panel.val);
        }

        gate_count = isNaN(C21) ? 0 : C21;
        gate_length = isNaN(D21) ? 0 : parseInt(D21) - gate_post_gaps;
        gate_width = parseInt(gate_length);

        // Update even panel if there's a hinge panel
        if( gate_swing?.val?.includes('right')  || gate_swing?.val?.includes('left') ) {
            const even_panel_length_orig = even_panel_length;
            even_panel_length = even_panel_length + ((even_panel_length - gate_hinge_panel_width) / (even_panel_count-1) );
            if (even_panel_length > C5) {
                even_panel_length = ((even_panel_length_orig * even_panel_count) - gate_hinge_panel_width) / even_panel_count;
                extra_panel_count = 1;
            }
            if( isNaN(even_panel_length) ) {
                even_panel_length = gate_hinge_panel_width;
            }
        }

        if (panel_options_data?.slug?.includes('even') || panel_options_data?.slug === undefined) {
            long_panel_count = even_panel_count + extra_panel_count;
            long_panel_length = Math.round(even_panel_length);
        } else {
            long_panel_count = full_panel_count + extra_panel_count;
            long_panel_length = Math.round(full_panel_length);
            short_panel_count = isNaN(C19) ? 0 : C19;
            short_panel_length = isNaN(E19) ? 0 : Math.round(E19);
            _short_panel_length = short_panel_length;
        }

        offcut_panel_count = C20;
        offcut_panel_length = isNaN(D20) ? 0 : Math.round(D20);
        _offcut_panel_length = offcut_panel_length;

        if (
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(i) &&
            gate_data.length &&
            SlatFence.isWithinGateOnlyOverallRange(
                i,
                gate_data,
                custom_fence,
                custom_fence_tab,
                C8,
                C9,
                C10,
                null
            )
        ) {
            offcut_gate_panel_count = 0;
            offcut_gate_panel_length = 0;
            offcut_panel_count = 0;
            offcut_panel_length = 0;
            _offcut_panel_length = 0;
        }
    }


    // --- Post adjustment logic (Barr-style; Slat main uses fixed post layout from overall width) ---
    const _post = FENCE.minus_posts(custom_fence);
    const skipSlatMainPostAdjust =
        typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(i);
    if( _post && !skipSlatMainPostAdjust ) {
        const divided_post = _post/(long_panel_count + short_panel_count);
        full_panel_length = HELPER.isNaNtoZero(Math.round(full_panel_length + divided_post));
        even_panel_length = HELPER.isNaNtoZero(Math.round(even_panel_length + divided_post));
        long_panel_length = HELPER.isNaNtoZero(Math.round(long_panel_length + divided_post));    
        short_panel_length = HELPER.isNaNtoZero(Math.round(short_panel_length + divided_post));                  
        offcut_panel_length = HELPER.isNaNtoZero(Math.round(offcut_panel_length - divided_post));
        // Recalculate if any post is removed
        if(long_panel_length > default_panel_width) {
            long_panel_length = Math.round(default_panel_width);    
        } 
        if(short_panel_length > default_panel_width) {
            short_panel_length = HELPER.isNaNtoZero(Math.round(default_panel_width));                  
        } else {
            if(short_panel_length > 0) {
                const divided_post = _post/short_panel_count;
                short_panel_length = HELPER.isNaNtoZero(Math.round(_short_panel_length + divided_post));                             
                offcut_panel_length = HELPER.isNaNtoZero(Math.round(_offcut_panel_length - _post));
            }
        }
        if(short_panel_length == 0) {
            short_panel_count = 0;
        }
    }


    // --- Offcut logic for panel group 'b' ---
    // Fence config `offcut.panel` / `offcut.gate` (e.g. 4-SLAT.php) toggle **display** of offcut tiles only.
    // Do not zero gate_count / panels — that would remove the real gate or bays from the diagram.
    if( info.panel_group == 'b' ) {
        if( info.offcut && info.offcut.panel === false ) {
            offcut_panel_count = 0;
        }
        if( info.offcut && info.offcut.gate === false ) {
            offcut_gate_panel_count = 0;
            offcut_gate_panel_length = 0;
        }
    }

    /* RC BUG - 061725
    if( long_panel_length && long_panel_count == 0 ) {
        long_panel_count = 1;
    }
    */

    // --- Side width calculations ---
    const left_side_width = HELPER.sideOptionValue('left', custom_fence, info, true);
    const right_side_width = HELPER.sideOptionValue('right', custom_fence, info, true);
    let left_side_width_d = HELPER.sideOptionValue('left', custom_fence, info);
    let right_side_width_d = HELPER.sideOptionValue('right', custom_fence, info);

    // Default spacing/message used in UI.
    // - For panel_group 'b' (Flat Top, Barr, Slat, etc) this is the panel spacing input (or default 50).
    // - For panel_group 'a' (Glass Pool) this will be overwritten by the solver result below.
    let spacing_width = max_panel_spacing;
    let msg = '';

    // Glass Pool: use the glass solver ONLY for glass pool fences with actual panel runs.
    // Gate-only mode has no infill panels to solve; running the solver still subtracts the gate
    // from overall and looks for regular panels, which fails and surfaces a bogus "no solutions" error.
    // Running this for other fence types can override counts/hinge widths and cause "no solutions" errors to leak.
    if (info.panel_group == 'a' && !gateOnly) {
        const fdGlass = { slug: i, data: info, info: custom_fence };
        const glassGaps =
            typeof FENCE !== 'undefined' && typeof FENCE.resolveGlassPoolHingeGapsMm === 'function'
                ? FENCE.resolveGlassPoolHingeGapsMm(fdGlass, gate_data)
                : { hinge: 10, latch: 9 };

        // Hinge panel is always part of the gate assembly for glass pool sizing and diagram render.
        gate_hinge_panel_count = 0;
        if (gate_count) {
            gate_hinge_panel_count = 1;
        }

        let hingePanelActive = !!gate_hinge_panel_count;
        if (gateOnly) hingePanelActive = false;

        if (
            hingePanelActive &&
            (!Number.isFinite(gate_hinge_panel_width) || gate_hinge_panel_width <= 0) &&
            typeof fcGlassPoolDefaultHingePanelWidthMm === 'function'
        ) {
            gate_hinge_panel_width = fcGlassPoolDefaultHingePanelWidthMm(info);
        }

        const glassPoolConfig = {
            overallLength: C3,
            gate: {
                active: gate_count ? true : false,
                gateSize: gate_width,
                hingePanelSize: gate_hinge_panel_width,
                hingePanelActive: hingePanelActive,
                hingeType: { left: glassGaps.hinge, right: glassGaps.latch }
            },
            leftRakedPanel: { active: left_raked_panel_width > 0 ? true : false, size: left_raked_panel_width },
            rightRakedPanel: { active: right_raked_panel_width > 0 ? true : false, size: right_raked_panel_width },
            leftEndAttachment: { size: [-1, false].includes(left_side_width_d) ? '' : left_side_width_d }, // 0, 25, gap based
            rightEndAttachment: { size: [-1, false].includes(right_side_width_d) ? '' : right_side_width_d }, // 0, 25, gap based
            panelSettings: {
                maxPanelSize: 2000,
                minPanelSize: 200,
                defaultPanelSize: C5, // User-adjustable initial preference.
                panelSizeIncrement: 50, // All regular panels will be rounded to this increment.
                panelGap: max_panel_spacing, // User-adjustable initial preference. Final gap may be adjusted.
                maxPanelSpacing: 80, // The maximum allowed gap between panels.
            }
        };

        const glassPoolPlan = calculateGlassFencing(glassPoolConfig);
        spacing_width = glassPoolPlan?.results?.panelGapsValue ?? spacing_width;
        msg = glassPoolPlan?.calculationDetails?.error || '';

        if (glassPoolPlan?.results) {
            long_panel_count = glassPoolPlan.results.longPanels?.count;
            long_panel_length = glassPoolPlan.results.longPanels?.size;
            short_panel_count = glassPoolPlan.results.shortPanel?.count;
            short_panel_length = glassPoolPlan.results.shortPanel?.size;
            gate_hinge_panel_width = hingePanelActive ? (glassPoolPlan.results.hingeSize ?? gate_hinge_panel_width) : 0;
        } else {
            long_panel_count = 0;
            long_panel_length = 0;
            short_panel_count = 0;
            short_panel_length = 0;
            gate_hinge_panel_width = 0;
        }
    }

/*console.log(gate_hinge_panel_width);
const plan = calculateGlassFencing(glassPoolConfig);

console.log("--- Fencing Glass Plan Calculation (Refactored) ---");
console.log("Inputs:", plan.inputs);
console.log("--- Results ---");

if (plan.results) {
    console.log(`Overall Length: ${plan?.results?.overallLength}mm`);
    console.log(`Panels Count: ${plan?.results?.panelsCount}`);


    console.log('---');
    if (plan?.results?.longPanels.count > 0) {
        console.log(`Long Panels: ${plan?.results?.longPanels.count} x ${plan?.results?.longPanels.size}mm`);
    }
    if (plan?.results?.shortPanel.count > 0) {
        console.log(`Short Panel(s): ${plan?.results?.shortPanel.count} x ${plan?.results?.shortPanel.size}mm`);
    }
    console.log(`Number of Panel Gaps: ${plan?.results?.numberOfPanelGaps}`);
    console.log(`Adjusted Panel Gaps: ${plan?.results?.panelGapsCount} x ${Math.round(plan?.results?.panelGapsValue)}mm`);
    console.log('---');
    if (plan?.results?.leftRakedSize > 0) {
        console.log(`Left Raked Panel: ${plan?.results?.leftRakedSize}mm`);
    }
    if (plan?.results?.rightRakedSize > 0) {
        console.log(`Right Raked Panel: ${plan?.results?.rightRakedSize}mm`);
    }
    if (plan?.results?.gateSize > 0) {
        console.log(`Gate: ${plan?.results?.gateSize}mm`);
        if (glassPoolConfig.gate.hingePanelActive) {
            console.log(`Hinge Panel: ${plan?.results?.hingeSize}mm`);
        }
        console.log(`Gate Gaps: ${plan?.results?.gateGaps.left}mm (left), ${plan?.results?.gateGaps.right}mm (right)`);
    }
    console.log('---');
    console.log(`Left Side Gap (End): ${Math.round(plan?.results?.leftSideGap)}mm`);
    console.log(`Right Side Gap (End): ${Math.round(plan?.results?.rightSideGap)}mm`);
} else {
    console.log("Could not find a valid panel configuration.");
    if (plan.calculationDetails.error) {
        console.log(`Reason: ${plan.calculationDetails.error}`);
    }
}
console.log("--- Calculation Details ---");
console.log(plan.calculationDetails);
console.log("--------------------------------------"); 
*/

    // --- Gate only logic ---
    if( gateOnly ) {
        short_panel_count = long_panel_count = even_panel_count = 0;
    }
    if( info.panel_group == 'a' ) {
         offcut_panel_count = 0;
    }

    // --- Output data structure ---
    const fence_height_visual = SlatFence.isSlatLike(i) ? max_fence_height : fence_height;
    const dataObj = {
        'fence_size': {
            'width': '',
            'height': HELPER.isNaNtoZero(fence_height_visual),
        },
        'full_panel': {
            'count': HELPER.isNaNtoZero(full_panel_count),
            'length': full_panel_length
        },
        'even_panel': {
            'count': HELPER.isNaNtoZero(even_panel_count),
            'length': even_panel_length
        },
        'long_panel': {
            'count': HELPER.isNaNtoZero(long_panel_count),
            'length': HELPER.isNaNtoZero(long_panel_length)
        },
        'short_panel': {
            'count': HELPER.isNaNtoZero(short_panel_count),
            'length': short_panel_length
        },
        'offcut_panel': {
            'count': HELPER.isNaNtoZero(offcut_panel_count),
            'length': offcut_panel_length
        },
        'offcut_gate_panel': {
            'count': HELPER.isNaNtoZero(offcut_gate_panel_count),
            'length': HELPER.isNaNtoZero(offcut_gate_panel_length)
        },
        'gate': {
            'count': HELPER.isNaNtoZero(gate_count),
            'width': HELPER.isNaNtoZero(gate_width),
            'length': HELPER.isNaNtoZero(gate_length)
        },
        'gate_hinge_panel': {
            'count': HELPER.isNaNtoZero(gate_hinge_panel_count),
            'width': HELPER.isNaNtoZero(gate_hinge_panel_width)
        },
        'left_raked': {
            'height': HELPER.isNaNtoZero(left_raked_panel_height),
            'width': HELPER.isNaNtoZero(left_raked_panel_width),
        },
        'right_raked': {
            'height': HELPER.isNaNtoZero(right_raked_panel_height),
            'width': HELPER.isNaNtoZero(right_raked_panel_width),
        },
        'selected_values': {
            'panel_option': panel_options_data?.slug,
            'spacing': HELPER.isNaNtoZero(Math.round(spacing_width)), // .toFixed(2).replace(".00", "")
            'message' : msg,
            ...(SlatFence.isSlatLike(i) ? { fence_height_key: fence_height } : {}),
        }
    };

    // console.log(dataObj);

    return dataObj;
}

//----------------------------------------------------------------------------------