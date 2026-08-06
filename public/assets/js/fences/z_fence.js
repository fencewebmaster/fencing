//Global Variable
var FENCE = FENCE || {};


FENCE = {
    settings: {
        message: {
            oal_changed: "Overall Length has changed!",
            min_gate: "Minimum <b>Overall Length</b> for a <b>GATE</b> is <b class='text-underline'>{{overall}}</b>mm",
            min_gate_custom: "Minimum <b>Overall Length</b> for a <b>GATE</b> is <b class='text-underline'>{{overall}}</b>mm <b>or</b> change to custom gate in <b>Gate Options</b>",
            min_gate_only: "<b>GATE ONLY:</b> Minimum <b>Overall Length</b> for a <b>GATE</b> is <b class='text-underline'>{{overall}}</b>mm <b>or</b> change to custom gate in <b>Gate Options</b>",
            min_gate_raked: "Minimum <b>Overall Length</b> for a <b>GATE & {{hasRaked}} RAKED</b> is <b>{{overall}}</b>mm",
            min_raked: "Minimum <b>Overall Length</b> for <b>{{hasRaked}} RAKED</b> is <b>{{overall}}</b>mm",
            min_gate_hinge: "Minimum <b>Overall Length</b> for a <b>GATE & HINGE PANEL</b> is <b>{{overall}}</b>mm",
        },
        item: {
            raked: 50 + 1200 + 50,
            raked_post: 1200 + 50,
            center_point: 25,
            base_margin: 0.1,
        },
        flat_top: {
            gate: 970 + 50 + 20 + 20,       
            gate_space_left: 20,                      
            gate_space_right: 20,                      
            post: 50,
            minOnGate: 970 + 50 + 20 + 20 + 50, // 1110
            maxOnGate: 1160,
            minPanelWidthOnGate: 86,
            gate_post_gaps: 50 + 20 + 20,
            gate_posts_gaps: 50 + 20 + 20 + 50,
        },
        glass_pool: {
            gate: 970 + 0,                             
            gate_space_left: '',                      
            gate_space_right: '',                      
            post: 0,
            minOnGate: 970 + 0 + 80, // 1110
            maxOnGate: 1250,
            minPanelWidthOnGate: 86,
            gate_post_gaps: 0,
            gate_posts_gaps: 0 + 0,
        },
        barr: {
            gate: 975 + 25 + 20 + 20,                              
            gate_space_left: 20,                      
            gate_space_right: 20,                      
            post: 25,
            minOnGate: 975 + 25 + 20 + 20 + 25, // 1065
            maxOnGate: 1165,
            minPanelWidthOnGate: 86,
            gate_post_gaps: 25 + 20 + 20,
            gate_posts_gaps: 25 + 20 + 20 + 25,
        },
    },
    
    //----------------------------------------------------------------------------------

    call: function(func, a, b, c, d, e, f) {

        var fd = getSelectedFenceData();

        switch (fd.slug) {

            case 'barr':
                return Barr.init(func, a, b, c, d, e, f)
                break;

            case 'flat_top':
                return FlatTop.init(func, a, b, c, d, e, f)
                break;

            case 'glass_pool':
                return GlassPool.init(func, a, b, c, d, e, f)
                break;

            case 'slat':
                return SlatFence.init(func, a, b, c, d, e, f)
                break;

            case 'slat_fence_infill':
                return SlatFenceInfill.init(func, a, b, c, d, e, f)
                break;

            default:
                return this[func](a, b, c, d, e, f)
                break;
        }

    },

    //----------------------------------------------------------------------------------

    test: function() {
        console.log('FENCE:', 'FENCE.test()');
    },

    //----------------------------------------------------------------------------------

    get: function(fence, key) {

        if( fence == 'glass_pool' ) {

            var fd = getSelectedFenceData(fence);

            var slug = fd.slug,
                tab = fd.tab,
                custom_fence = fd.info,
                info = fd.data;

            var spacing = fd.info.filter(function(item) {
                return item.control_key === 'edit_spacing';
            });

            spacing = spacing[0]?.settings[0]?.val ? parseInt(spacing[0]?.settings[0]?.val) : fd.data?.settings?.edit_spacing?.fields[0].default;

            var keys = {
                'gate': spacing,                           
                'post': spacing,
                'minOnGate' : spacing + spacing,
                'gate_post_gaps': spacing,
                'gate_posts_gaps': spacing + spacing,            
            }

            if( $.inArray(key, Object.keys(keys)) !== -1 ) {
                return FENCE.settings[fence][key] + keys[key];               
            }
        }

        if( fence == undefined ) {
            return;
        }

        if (typeof SlatFence !== 'undefined' && typeof SlatFence.getSetting === 'function') {
            var slatFenceSetting = SlatFence.getSetting(fence, key);
            if (typeof slatFenceSetting !== 'undefined') {
                return slatFenceSetting;
            }
        }

        if (typeof SlatFenceInfill !== 'undefined' && typeof SlatFenceInfill.getSetting === 'function') {
            var slatInfillSetting = SlatFenceInfill.getSetting(fence, key);
            if (typeof slatInfillSetting !== 'undefined') {
                return slatInfillSetting;
            }
        }

        return FENCE.settings[fence][key];
    },

    //----------------------------------------------------------------------------------

    load_fencing_items: function() {
        if (
            typeof fcIsStep2DomDirty === 'function' &&
            fcIsStep2DomDirty() &&
            $('.js-fc-form-step[data-section="3"]').is(':visible')
        ) {
            return;
        }

        if (typeof fcValidateOverallLengthMm === 'function') {
            var oalCheck = fcValidateOverallLengthMm();
            if (!oalCheck.valid) {
                if (typeof fcApplyOverallLengthValidationUi === 'function') {
                    fcApplyOverallLengthValidationUi({ hideStep3: true });
                }
                return;
            }
        }

        var renderLoad = function() {

        var fd = getSelectedFenceData();

        var slug = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data;

        if (
            typeof SlatFence !== 'undefined' &&
            typeof SlatFence.resetSlatDisplayScaling === 'function' &&
            !SlatFence.isSlatLike(slug)
        ) {
            SlatFence.resetSlatDisplayScaling($(FENCES.el.fencingPanelContainer));
        }

        $(FENCES.el.fencingPanelContainer)
            .html('')
            .attr('data-type', info?.slug)
            .attr('data-group', info?.panel_group)
            .removeClass('custom-height');

        var fence_height_filtered_data = info?.form?.filter(function(item) {
            return item && item.slug === 'fence_height';
        });

        if (fence_height_filtered_data && fence_height_filtered_data.length) {
            $(FENCES.el.fencingPanelContainer).addClass('custom-height');
        }


        if (
            info.panel_group === 'a' &&
            typeof fcGlassPoolPersistGateFieldsIfNeeded === 'function'
        ) {
            fcGlassPoolPersistGateFieldsIfNeeded(tab, slug, info);
            custom_fence = fd.info;
            try {
                fd = getSelectedFenceData();
                custom_fence = fd.info;
            } catch (eReloadCf) {}
        }

        var calc = calculate_fences();

        if(!calc) {
            return;
        }

        var panel_fence_height =
            typeof fcGetPanelLabelFenceHeightLineHtml === 'function'
                ? fcGetPanelLabelFenceHeightLineHtml(slug, calc, {
                      context: { fenceInfo: custom_fence, tabInfo: fd.tabInfo }
                  })
                : '';

        var center_point = FENCE.get(slug, 'post');

        // Update spacing for glass fence
        if( info.panel_group == 'a' ) {
            var center_point = calc.selected_values.spacing;
        }

        var tplCenterPoint =
            typeof SlatFence !== 'undefined' && SlatFence.shouldHidePostValue(slug) ? '' : center_point;
        var panelSizeCenterW = function(panelMm) {
            if (typeof SlatFence !== 'undefined' && SlatFence.formatPanelSizeCenterW) {
                return SlatFence.formatPanelSizeCenterW(panelMm, center_point, slug);
            }
            return panelMm + center_point + 'W';
        };

        gate_hinge_panel_number = panel_number = 0;

        var gateRowsForHinge = (custom_fence || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var hingePanelWidthMm =
            calc.gate_hinge_panel.width > 0
                ? calc.gate_hinge_panel.width
                : typeof fcResolveGlassPoolHingePanelWidthMm === 'function'
                  ? fcResolveGlassPoolHingePanelWidthMm(gateRowsForHinge, calc, info)
                  : calc.gate_hinge_panel.width;

        if (calc.gate_hinge_panel.count && hingePanelWidthMm > 0) {

            for (let i = 0; i < calc.gate_hinge_panel.count; i++) {

                var panel_size = hingePanelWidthMm,
                    panel_unit = FENCES.defaultValues.unit;
                panel_option_value = calc.selected_values.panel_option;

                var gate_hinge_panel_number = i;


                if(panel_option_value.indexOf('full') !== -1) {
                    panel_option_value = panel_option_value.split('_')[0];
                }

                // Fence height
                if(calc.fence_size.height) {
                    panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
                }

                var tpl = $('script[data-type="extra_panel_item-' + info.panel_group + '"]').text()
                    .replace(/{{center_point}}/gi, tplCenterPoint)
                    .replace(/{{panel_size}}/gi, panel_size + 'W')
                    .replace(/{{panel_value}}/gi, panel_option_value)
                    .replace(/{{panel_unit}}/gi, 'HINGE<br>PANEL')
                    .replace(/{{panel_size_center}}/gi, panelSizeCenterW(panel_size))
                    .replace(/{{panel_number}}/gi, gate_hinge_panel_number);

                $(FENCES.el.fencingPanelContainer).append(tpl);

                var panel_width = panel_size * FENCE.get('item', 'base_margin');

                $('.extra-panel-item').css({ 'width':  panel_width}).attr('data-width', panel_width);

            }

            var tpl = $('script[data-type="panel_spacing-' + info.panel_group + '"]').text()
                .replace(/{{center_point}}/gi, tplCenterPoint);

        }

        const totalLongPanels = parseInt(calc.long_panel.count || 0, 10);
        const infillRenderPlan = SlatFenceInfill.getLongPanelRenderPlan(slug, totalLongPanels);
        const renderLongPanels = infillRenderPlan.renderLongPanels;
        const hiddenLongPanels = infillRenderPlan.hiddenLongPanels;

        for (let i = 0; i < renderLongPanels; i++) {

            mesurement = $(FENCES.el.measurementBoxNumber).val();

            var panel_number = i + gate_hinge_panel_number;
            if(gate_hinge_panel_number) {
                var panel_number = i + gate_hinge_panel_number + 1;
            }

                panel_size = calc.long_panel.length,
                panel_unit = FENCES.defaultValues.unit,
                data_key = "post_options";

            var panel_option_value = calc.selected_values.panel_option;

            if(panel_option_value.indexOf('full') !== -1) {
                panel_option_value = panel_option_value.split('_')[0];
            }

            // Fence height
            if(calc.fence_size.height) {
                panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
            }

            var tpl = $('script[data-type="panel_item-' + info.panel_group + '"]').text()
                .replace(/{{data_key}}/gi, tplCenterPoint)
                .replace(/{{center_point}}/gi, tplCenterPoint)
                .replace(/{{panel_value}}/gi, panel_option_value)
                .replace(/{{panel_fence_height}}/gi, panel_fence_height)
                .replace(/{{panel_size}}/gi, panel_size + 'W')
                .replace(/{{panel_unit}}/gi, 'PANEL')
                .replace(/{{panel_size_center}}/gi, panelSizeCenterW(panel_size))
                .replace(/{{center_post}}/gi, tplCenterPoint)
                .replace(/{{panel_number}}/gi, panel_number);
        
            // if( panel_size > FENCE.get(slug, 'minPanelWidthOnGate') ) { } 
            if(panel_size > 0) {
                $(FENCES.el.fencingPanelContainer).append(tpl);
            }

            var panel_width = panel_size * FENCE.get('item', 'base_margin');
            $(FENCES.el.fencingPanelItem).css({ 'width':  panel_width}).attr('data-width', panel_width);
        }

        var tpl = $('script[data-type="panel_spacing-' + info.panel_group + '"]').text()
            .replace(/{{center_point}}/gi, tplCenterPoint);

        if(calc.short_panel.count) {

            for (let i = 0; i < calc.short_panel.count; i++) {

                var panel_size = calc.short_panel.length,
                    panel_unit = FENCES.defaultValues.unit;
                panel_option_value = calc.selected_values.panel_option;

                var short_panel_number = panel_number + gate_hinge_panel_number + i;
                if( panel_number ) {
                    var short_panel_number = panel_number + gate_hinge_panel_number + i + 1;
                } 

                if(panel_option_value.indexOf('full') !== -1) {
                    panel_option_value = panel_option_value.split('_')[0];
                }

                // Fence height
                if(calc.fence_size.height) {
                    panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
                }

                var tpl = $('script[data-type="short_panel_item-' + info.panel_group + '"]').text()
                    .replace(/{{center_point}}/gi, tplCenterPoint)
                    .replace(/{{panel_fence_height}}/gi, panel_fence_height)
                    .replace(/{{panel_size}}/gi, panel_size + 'W')
                    .replace(/{{panel_value}}/gi, panel_option_value)
                    .replace(/{{panel_unit}}/gi, 'PANEL')
                    .replace(/{{panel_size_center}}/gi, panelSizeCenterW(panel_size))
                    .replace(/{{center_post}}/gi, tplCenterPoint)
                    .replace(/{{panel_number}}/gi, short_panel_number);

                $(FENCES.el.fencingPanelContainer).append(tpl);

                var panel_width = panel_size * FENCE.get('item', 'base_margin');
                $(FENCES.el.shortPanelItem).css({ 'width':  panel_width}).attr('data-width', panel_width);

            }

            var tpl = $('script[data-type="panel_spacing-' + info.panel_group + '"]').text()
                .replace(/{{center_point}}/gi, center_point);

        }


        $(FENCES.el.fencingPanelContainer).append(tpl);     

        if (SlatFenceInfill.isActive(slug)) {
            SlatFenceInfill.applyPostUiAdjustments(FENCES.el.fencingPanelContainer);
            SlatFenceInfill.appendHiddenPanelsTile(FENCES.el.fencingPanelContainer, hiddenLongPanels);
        }

        // Set the ID for each panel item
        setPanelItemsID();

        // No panel item 
        if($('.single-panel, #panel-item-0').length == 0 && $('.panel-item:not(.fencing-raked-panel)').length == 0 ) {
            if( $(FENCES.el.fencingPanelContainer+' .panel-post').length ) {
                $(FENCES.el.fencingPanelContainer+' .panel-post').after('<div id="panel-item-x" class="single-panel"></div>'); 
            } else {
                $(FENCES.el.fencingPanelContainer+' .fencing-panel-spacing-number').after('<div id="panel-item-x" class="single-panel"></div>'); 
            }
        }

        FENCE.call('update_gate', 'edit');

        $(FENCES.el.fencingPanelContainer).prepend('<div data-cart-key="raked-panel" class="left_raked-panel raked-panel"></div>')
            .append('<div data-cart-key="raked-panel" class="right_raked-panel raked-panel"></div>');

        FENCE.call('update_raked_panels', ['left_raked', 'right_raked']);

        // Panel off-cut
        if(calc.offcut_panel.count && calc.offcut_panel.length) {
            var tpl = $('script[data-type="offcut"]').text()
                .replace(/{{slug}}/gi, 'panel-offcut')
                .replace(/{{name}}/gi, 'Panel')
                .replace(/{{count}}/gi, calc.offcut_panel.count)
                .replace(/{{group}}/gi, info.panel_group)
                .replace(/{{width}}/gi, calc.offcut_panel.length);

            $(FENCES.el.fencingPanelContainer).append(tpl);
            $('.fencing-offcut.panel-offcut .offcut-body').css({ 'width': calc.offcut_panel.length * FENCE.get('item', 'base_margin') });
        }

        // Custom gate off-cut
        if(calc.offcut_gate_panel.count && calc.offcut_gate_panel.length) {
            var tpl = $('script[data-type="offcut"]').text()
                .replace(/{{slug}}/gi, 'gate-offcut')
                .replace(/{{name}}/gi, 'Gate')
                .replace(/{{count}}/gi, calc.offcut_gate_panel.count)
                .replace(/{{group}}/gi, info.panel_group)
                .replace(/{{width}}/gi, calc.offcut_gate_panel.length);

            $(FENCES.el.fencingPanelContainer).append(tpl);
            $('.fencing-offcut.gate-offcut .offcut-body').css({ 'width': calc.offcut_gate_panel.length * FENCE.get('item', 'base_margin') });
        }

        // Remove offcut when overall is within gate footprint (custom single/double or STD sizes).
        var gateRowsForOffcut = custom_fence.filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var slatWithinGateOnly =
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(slug) &&
            gateRowsForOffcut.length &&
            SlatFence.isWithinGateOnlyOverallRange(
                slug,
                gateRowsForOffcut,
                custom_fence,
                fd.tabInfo,
                SlatFence.getSlatGateCalcC8Mm(slug, gateRowsForOffcut, FENCE.get(slug, 'post')),
                0,
                0,
                calc
            );

        if (slatWithinGateOnly) {
            $('.fencing-offcut').remove();
        } else if (parseInt(fd.mbn) <= FENCE.get(slug, 'maxOnGate')) {
            // $('.panel-offcut').remove();
        } else if (parseInt(fd.mbn) <= FENCE.get(slug, 'minOnGate')) {
            $('.fencing-offcut').remove();
        }
        
        // Clear tooltip like error massage
        $(FENCES.el.fcInputMsg).removeClass('fcim-show').html('');


        setTimeout(function() {
            $(FENCES.el.fcFenceResetAll).hide();
            if($(FENCES.el.fsiSelected).length) {
                $(FENCES.el.fcFenceResetAll).show();
            }
        });


        $('.fencing-panel-container').each(function () {
            const $spacings = $(this).find('.fencing-panel-spacing-number');
            $spacings.first().find('.fs-clamp').remove();
            $spacings.last().find('.fs-clamp').remove();
        });

        FENCE.call('near_gate_spacing');

        var slatCtx = { fenceInfo: custom_fence, tabInfo: fd.tabInfo };
        var $panelScope = $('#pp-' + tab + ' .fencing-panel-container');
        if (!$panelScope.length) {
            $panelScope = $(FENCES.el.fencingPanelContainer);
        }

        if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(slug)) {
            if (typeof SlatFence.applySlatFenceDisplayHeights === 'function') {
                SlatFence.applySlatFenceDisplayHeights(slug, calc, slatCtx, $panelScope);
            } else {
                SlatFence.applyGapPatternIfNeeded(slug, custom_fence, info, calc, slatCtx);
                SlatFence.applySlatPanelInlineHeights(slug, calc, slatCtx, { $root: $panelScope });
            }
        } else if (typeof fcApplyGroupBFenceDisplayHeights === 'function') {
            fcApplyGroupBFenceDisplayHeights(calc, $panelScope, {
                slug: slug,
                tabInfo: fd.tabInfo
            });
        }

        var _gateRowForMsg = fd.info.filter(function(item) {
            return item.control_key == 'gate';
        })[0];
        if (!_gateRowForMsg || !_gateRowForMsg.settings || !_gateRowForMsg.settings.gateOnly) {
            var _calcUiMsg = (calc.selected_values && calc.selected_values.message) ? calc.selected_values.message : '';
            $('.err-message').html(_calcUiMsg);
        } else {
            $('.err-message').html('');
        }

        $('.ftm-measurement:not(:empty)').closest(FENCES.el.fencingTab).removeClass('incomplete-section');
        if (typeof fcSyncPlannerTabSectionStatus === 'function') {
            fcSyncPlannerTabSectionStatus($(FENCES.el.fencingTabSelected), tab);
        }

        if (SlatFence.isSlatLike(slug) && typeof fcRefreshPlannerCartForTab === 'function') {
            setTimeout(function() {
                try {
                    fcRefreshPlannerCartForTab(tab);
                } catch (eCart) {}
            }, 0);
        }

        if (typeof fcSyncPlannerStep3PanelEnds === 'function') {
            setTimeout(function() {
                try {
                    fcSyncPlannerStep3PanelEnds(slug);
                } catch (eEnds) {}
            }, 0);
        }

        };

        if ($('.fc-planner-page').length && typeof fcRunWithPlannerStep3Skeleton === 'function') {
            fcRunWithPlannerStep3Skeleton(renderLoad);
            return;
        }
        renderLoad();
    },

    //----------------------------------------------------------------------------------

    update_custom_fence_tab: function() {
        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data,
            tabInfo = fd.tabInfo;

        var modal_key = fd.modKey,
            mbn = fd.mbn;

        var filtered_data_tabs = tabInfo.filter(function(item) {
            return item.tab != tab;
        });

        if(info == undefined) {

            $(FENCES.el.fcTabTitle).html('SECTION ' + (tab + 1));
            $(FENCES.el.fcTabSubtitle).html('');

            $(FENCES.el.jsFcFormStep).hide();
            $(FENCES.el.fsiSelected).removeClass('fsi-selected');
            if (typeof fcSyncPlannerTabFenceStyle === 'function') {
                fcSyncPlannerTabFenceStyle($(FENCES.el.fencingTabSelected), tab, '');
            }

            return;
        }

        mesurement = $(FENCES.el.measurementBoxNumber).val();
        mesurement = mesurement ? parseInt(mesurement).toLocaleString() + ' mm' : '';

        $(FENCES.el.fencingTabSelected).find('.ftm-measurement').html(mesurement);
        if (typeof fcSyncPlannerTabFenceStyle === 'function') {
            fcSyncPlannerTabFenceStyle($(FENCES.el.fencingTabSelected), tab);
        }

        $(FENCES.el.fcTabTitle).html('SECTION ' + (tab + 1));

        var sectionStyleTitle =
            info && info.title
                ? info.title
                : typeof fcFenceSectionStyleTitle === 'function'
                  ? fcFenceSectionStyleTitle(tab)
                  : '';
        subTitle = [sectionStyleTitle, mesurement].filter(function(e) { return e }).join(' <i class="fa-solid fa-caret-right ms-3"></i> ');

        $(FENCES.el.fcTabSubtitle).html(` <i class="fa-solid fa-caret-right ms-3"></i> ${subTitle}`);

        FENCE.call('load_fencing_items');
    },

    //----------------------------------------------------------------------------------

    update_custom_fence_style_item: function() {
        var fd = getSelectedFenceData();

        var i = fd.slug,
            info = fd.data,
            tab = fd.tab;

        mesurement = $(FENCES.el.measurementBoxNumber).val();
        mesurement = mesurement ? mesurement + ' ' + FENCES.defaultValues.unit : '';

        $(FENCES.el.fencingTabSelected).find('.ftm-title').html('SECTION'); // info['name']
        $(FENCES.el.fencingTabSelected).find('.ftm-measurement').html(mesurement);
        if (typeof fcSyncPlannerTabFenceStyle === 'function') {
            fcSyncPlannerTabFenceStyle($(FENCES.el.fencingTabSelected), tab);
        }

        $(FENCES.el.fencingPanelControls).html('');

        $.each(info?.settings, function(k, v) {

            /**
             * @TODO - re-check on how to disable from the settings
             */
            if(v.disabled) {
                return;
            }

            if(v.length !== 0) {

                var action = '';
                let label = v.label;

                if(v.action, v.action.includes('edit')) {
                    var action = 'Edit ';
                }

                if(v.action, v.action.includes('add')) {
                    var action = 'Add ';
                }

                if(label) {
                    label = label.split(' ');

                    if(Array.isArray(label)) {
                        label[0] = `<span>${label[0]}</span>`;
                    }

                    label = label.join(" ");
                }

                $('<button>').html(action + label).attr({
                    'type': 'button',
                    'id': 'btn-' + k,
                    'data-key': k,
                    'data-target': "#fc-control-modal",
                    'class': 'btn-fc btn-fc-outline-default fencing-btn-modal fc-mb-1 '+v.class
                }).appendTo(FENCES.el.fencingPanelControls);

                setTimeout(function() {
                    $(FENCES.el.fencingPanelControls + " > div").remove();
                }, 100);

            }

        });

        $(FENCES.el.btnGate).before('<div></div>');

        if (typeof fcEnsurePlannerSummaryButton === 'function') {
            fcEnsurePlannerSummaryButton();
        }

        FENCE.call('update_custom_fence_tab');

        setTimeout(function() {
        if (typeof fcSyncPanelControlsGateOnlyDisabled === 'function') {
            fcSyncPanelControlsGateOnlyDisabled();
        }
        if (typeof fcEnsurePlannerSummaryButton === 'function') {
            fcEnsurePlannerSummaryButton();
        }
        }, 110);
    },

    //----------------------------------------------------------------------------------

    update_custom_fence: function(modal_key, fc_form_field = false) {
        var fd = getSelectedFenceData();

        var _this = $(this),
            i = fd.slug,
            tab = fd.tab,
            data = fd.info,
            info = fd.data,
            tabInfo = fd.tabInfo;

        let form_field = fc_form_field || $('.fc-form-field:visible');

        let itemKey = 'custom_fence-' + tab + '-' + i;

        var modalKeys = ['left_side', 'right_side', 'post_options', 'panel_options', 'gate'];

        if($.inArray(modal_key, modalKeys) !== -1) {
            modal_key = FENCES.activeSetting;
        }

        // Gate ONLY: keep flag when saving gate, posts, or sides; clear only for fence-body controls.
        var resolvedModalKey = modal_key;
        var keysPreservingGateOnly = {
            gate: true,
            color_options: true,
            post_options: true,
            left_side: true,
            right_side: true
        };
        if (resolvedModalKey && !keysPreservingGateOnly[resolvedModalKey]) {
            var tabGoClear = !!(fd.tabInfo && fd.tabInfo[0] && fd.tabInfo[0].gateOnly);
            var gdClear = (data || []).filter(function(item) {
                return item.control_key === 'gate';
            })[0];
            var segGoClear = !!(gdClear && gdClear.settings && gdClear.settings.gateOnly);
            if (tabGoClear || segGoClear) {
                updateGateOnly(false);
                fd = getSelectedFenceData();
                data = fd.info;
                if (typeof checkGateOnly === 'function') {
                    checkGateOnly();
                }
            }
        }

        settings = form_field.map(function() {

            var _this = $(this),
                key = _this.attr('name'),
                val = _this.val() ? _this.val() : _this.attr('value'),
                type = _this.attr('type'),
                tag = _this.get(0).tagName.toLowerCase(),
                obj = { key: key, val: val, tag: tag, type: type };


            if(modal_key === "color_options") {
                obj.title = _this.attr('data-title') || '';
                obj.subtitle = _this.attr('data-subtitle') || '';
                obj.color_code = _this.attr('data-color-code') || '';
            }

            return obj;

        }).get();


        settings = mergeSettings(data, settings, 'control_key', modal_key);

        if( modal_key == 'gate' && settings) {
            var gate_filtered_data = data.filter(function(item) {
                return item.control_key == modal_key; 
            });     
            gate_settings = gate_filtered_data[0]?.settings;  
            if( gate_settings ) {
                gate_settings.fields = settings;
                settings = gate_settings;                
            }
        }   

        var filtered_data = data.filter(function(item) {
            return item.control_key != modal_key; 
        });   


        filtered_data.push({
            id: i,
            control_key: modal_key,
            settings: settings
        });


        if(modal_key === "color_options") {

            itemKey = 'project-plans';
            color_data = {};
            let text_color = "#fff";

            // To make the text readable in project plans page,
            // we need to change the text to black if the selected color is white
            if(settings[0].val.indexOf('white') !== -1) {
                text_color = '#000';
            }

            color_data.color = {
                code: settings[0].color_code,
                subtitle: settings[0].subtitle,
                title: settings[0].title,
                value: settings[0].val,
                text_color: text_color
            };

            updateOrCreateObjectInLocalStorage(itemKey, color_data);

        } else {

            localStorage.setItem(itemKey, JSON.stringify(filtered_data));

        }

        if (
            (modal_key === 'panel_options' || modal_key === 'edit_spacing') &&
            typeof SlatFence !== 'undefined'
        ) {
            try {
                if (SlatFence.isSlatLike(i)) {
                    SlatFence.syncStep2MaxFenceHeightDisabledState(i);
                    var mhSel = document.querySelector('[data-section="2"] [name="max_fence_height"]');
                    if (mhSel && mhSel.tagName === 'SELECT' && !mhSel.disabled) {
                        try {
                            maxFenceHeightValidation?.call(mhSel, { target: mhSel });
                        } catch (errMF) {}
                    }
                }
            } catch (errSlat) {}
        }

        if (keysPreservingGateOnly[resolvedModalKey] && typeof checkGateOnly === 'function') {
            checkGateOnly();
        }

        FENCE.call('update_custom_fence_tab');
    },

    //----------------------------------------------------------------------------------

    add_new_fence_section: function() {
        $(FENCES.el.fencingTab).eq(0).clone().appendTo(FENCES.el.tabArea);

        $(FENCES.el.fencingTab).removeClass('fencing-tab-selected');
        $('.fencing-tab:last-child').addClass('fencing-tab-selected');

        var tabCount = $(FENCES.el.fencingTab).length;

        $('.fencing-tab:last-child').find('.fencing-tab-number').html(tabCount);

        $('.fencing-tab:last-child').toggleClass(`fc-section-1 fc-section-${tabCount}`);
        $('.fencing-tab:last-child').find('.ftm-measurement').html('');
        $('.fencing-tab:last-child').find('.ftm-fence-style').empty().prop('hidden', true).hide();
        if (typeof fcSyncPlannerTabSectionStatus === 'function') {
            fcSyncPlannerTabSectionStatus($('.fencing-tab:last-child'), tabCount - 1);
        }

        // Clearing OAL must not mark Step 2 dirty — otherwise `set_cutom_fence_data` is skipped
        // on the first fence-style pick and Calculate cannot persist the new section row.
        if (typeof fcRunWithoutStep2DirtyTracking === 'function') {
            fcRunWithoutStep2DirtyTracking(function() {
                $(FENCES.el.measurementBoxNumber).val(FENCES.defaultValues.measurement);
            });
        } else {
            $(FENCES.el.measurementBoxNumber).val(FENCES.defaultValues.measurement);
        }
        $(FENCES.el.measurementBoxNumber).removeAttr('data-last');
        $(FENCES.el.measurementBoxNumber).removeAttr('data-prev-gate-only-mbn');
        if (typeof fcMarkStep2Committed === 'function') {
            fcMarkStep2Committed();
        }

        $(FENCES.el.fsiSelected).removeClass('fsi-selected');
        $('.fc-tab-title, .fc-tab-subtitle').html('');
        $(FENCES.el.jsFcFormStep).hide();
        $(FENCES.el.fencingPanelControls).html('');

        FENCE.call('update_custom_fence_tab');

        HELPER.hideDeleteSectionBtn();

        $(FENCES.el.fcFenceResetAll).hide();

        // Store section count
        localStorage.setItem('custom_fence-section', $(FENCES.el.fencingTab).length);

        HELPER.setSectionURLParam();
    },

    //----------------------------------------------------------------------------------

    update_custom_fence_gate: function(action ='edit') {

        var fd = getSelectedFenceData();

        var _this = $(this),
            i = fd.slug,
            tab = fd.tab,
            info = fd.info,
            data = fd.data,
            tabInfo = fd.tabInfo;

        var modal_key = 'gate';

        placement = $(FENCES.el.fencingPanelGate).prev().prev().prev().attr('data-id');
        placement = placement == undefined ? -1 : placement;

        var gateRowPrev = (info || []).filter(function(item) {
            return item.control_key === 'gate';
        })[0];

        var gateRowForPlacement = (info || []).filter(function(item) {
            return item.control_key === 'gate';
        });
        var glassPoolPlacementDrive =
            data.panel_group === 'a' &&
            typeof fcGlassPoolUsesPlacementDrive === 'function' &&
            fcGlassPoolUsesPlacementDrive(gateRowForPlacement, $(FENCES.el.fencingPanelGate));

        if (
            glassPoolPlacementDrive &&
            gateRowPrev &&
            gateRowPrev.settings &&
            gateRowPrev.settings.placement !== undefined &&
            gateRowPrev.settings.placement !== null &&
            String(gateRowPrev.settings.placement).trim() !== ''
        ) {
            var storedPl = parseInt(String(gateRowPrev.settings.placement).replace(/,/g, ''), 10);
            if (Number.isFinite(storedPl)) {
                placement = storedPl;
            }
        } else if (
            data.panel_group === 'a' &&
            typeof fcResolveGlassPoolGatePlacementFromDom === 'function'
        ) {
            var resolvedGlassPlacement = fcResolveGlassPoolGatePlacementFromDom(
                $(FENCES.el.fencingPanelGate),
                gateRowForPlacement
            );
            if (resolvedGlassPlacement !== null && resolvedGlassPlacement !== undefined) {
                placement = resolvedGlassPlacement;
            }
        }

        var persistedGateOnly = !!(gateRowPrev && gateRowPrev.settings && gateRowPrev.settings.gateOnly);
        var $goField = $('.fencing-container[data-key="gate"] [name="gate_only"]');
        var gateOnlyOut = $goField.length ? !!$goField.prop('checked') : persistedGateOnly;

        var fieldsPrev = (gateRowPrev && gateRowPrev.settings && gateRowPrev.settings.fields) || [];
        var isCustomGate = !!(fieldsPrev.find(function(obj) {
            return obj.key === 'use_std' && obj.val === false;
        }));

        // STD gate opening is chosen from gate_width dropdown; [name="width"] is for CUSTOM only.
        // Without this, settings.size stays stale (e.g. 750) when user picks 1000 — Overall Length / gate-only math breaks.
        var $stdGateSelect = $('.fencing-container[data-key="gate"] [name="gate_width"] select');
        if (!$stdGateSelect.length) {
            $stdGateSelect = $('#fc-control-modal [name="gate_width"] select, .js-fencing-modal [name="gate_width"] select');
        }
        var stdGateMm = parseInt(String($stdGateSelect.val() || '').replace(/,/g, ''), 10);

        var sizeRaw = $('[name="width"]').val(),
            default_width = fd?.data?.settings?.gate?.size?.width;

        var sizeParsed = parseInt(String(sizeRaw || '').replace(/,/g, ''), 10);
        var defaultParsed = parseInt(String(default_width || '').replace(/,/g, ''), 10);
        var prevStoredSize = parseInt(String(gateRowPrev?.settings?.size ?? '').replace(/,/g, ''), 10);

        var size;
        if (isCustomGate) {
            size =
                Number.isFinite(sizeParsed) && sizeParsed > 0
                    ? sizeParsed
                    : Number.isFinite(defaultParsed) && defaultParsed > 0
                      ? defaultParsed
                      : sizeRaw || default_width;
        } else {
            if (Number.isFinite(stdGateMm) && stdGateMm > 0) {
                size = stdGateMm;
            } else if (Number.isFinite(sizeParsed) && sizeParsed > 0) {
                size = sizeParsed;
            } else if (Number.isFinite(prevStoredSize) && prevStoredSize > 0) {
                size = prevStoredSize;
            } else if (Number.isFinite(defaultParsed) && defaultParsed > 0) {
                size = defaultParsed;
            } else {
                size = sizeRaw || default_width;
            }
        }

        position = 'middle';
    
        if( placement == -1 ) {
            position = 'first';
        }

        var regularPanelCountPos = $(FENCES.el.fencingPanelContainer).find(
            '.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)'
        ).length;

        if (
            glassPoolPlacementDrive &&
            regularPanelCountPos > 0 &&
            parseInt(placement, 10) >= regularPanelCountPos
        ) {
            position = 'last';
        } else if (
            placement ==
            $(FENCES.el.panelItem + ':not(.fencing-raked-panel,.fencing-panel-gate,.extra-panel-item)')
                .last()
                .attr('data-id')
        ) {
            position = 'last';
        }

        var gate_data_for_pos = info.filter(function(item) {
            return item.control_key == 'gate';
        });
        var swing_left =
            fcGlassPoolIsLeftHandSwingFromGateData(gate_data_for_pos) ||
            (function() {
                try {
                    var lc = JSON.parse(localStorage.getItem('last-clicked-value'));
                    return !!(lc?.value && String(lc.value).includes('left'));
                } catch (eLc) {
                    return false;
                }
            })();

        if (
            info.panel_group == 'a' &&
            swing_left &&
            typeof fcGlassPoolGateHingeBoundaryState === 'function'
        ) {
            var glassFirstBoundary = fcGlassPoolGateHingeBoundaryState(
                null,
                $(FENCES.el.fencingPanelGate)
            );
            if (glassFirstBoundary && glassFirstBoundary.atFirst) {
                position = 'first';
            }
        }

        var last_clicked = JSON.parse(localStorage.getItem('last-clicked-value'));

        var swing_right = last_clicked?.value?.includes('right') ? true : $('.fencing-panel-gate').hasClass('panel-gate-right');

        if( HELPER.isGateOnLastPanel() && swing_right ) {
            position = 'last';

            if (!glassPoolPlacementDrive) {
                placement = parseInt(placement);
                if( $('.hinge-panel').length && info.panel_group == 'a' ) {
                    placement = parseInt(placement) - 1;
                }
            }

        }


        var settings = {
            'placement': placement,
            'position': position,
            'gateOnly': gateOnlyOut,
            'index': $(FENCES.el.fencingPanelGate).index(),
            'size': size,
            'unit': FENCES.defaultValues.unit
        }

        if( $('[data-key="gate"] .fc-form-field:visible').length ) {

            settings.fields = $('.fc-form-field:visible').map(function() {
                var el = $(this),
                    key = el.attr('name'),
                    type = (el.attr('type') || '').toLowerCase(),
                    tag = el.get(0).tagName.toLowerCase(),
                    val;

                if (!key) {
                    return null;
                }

                if (type === 'radio') {
                    if (!el.is(':checked')) {
                        return null;
                    }
                    val = el.val();
                } else if (type === 'checkbox') {
                    val = el.prop('checked');
                } else if (tag === 'select') {
                    val = el.val();
                } else {
                    val = el.val();
                    if (val === undefined || val === null || String(val).trim() === '') {
                        val = el.attr('value');
                    }
                }

                return {
                    key: key,
                    val: val,
                    tag: tag,
                    type: type || tag
                };

            }).get().filter(function(row) {
                return row != null;
            });

            // Slat: gate height commits on Calculate / Enter only — not while typing in the modal.
            if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(i)) {
                var prevGateHeight = fieldsPrev.find(function(row) {
                    return row && row.key === 'gate_max_fence_height';
                });
                settings.fields = settings.fields.filter(function(row) {
                    return row && row.key !== 'gate_max_fence_height';
                });
                if (prevGateHeight && prevGateHeight.val !== undefined && String(prevGateHeight.val).trim() !== '') {
                    settings.fields.push({
                        key: 'gate_max_fence_height',
                        val: prevGateHeight.val,
                        tag: prevGateHeight.tag || 'input',
                        type: prevGateHeight.type || 'input'
                    });
                }
            }

        } else {

            var gate_data = info.filter(function(item) {
                return item.control_key == 'gate';
            });

            settings.fields = gate_data[0]?.settings?.fields || [];

            if (
                data.panel_group === 'a' &&
                typeof fcGlassPoolEnsureDefaultGateFields === 'function'
            ) {
                settings.fields = fcGlassPoolEnsureDefaultGateFields(data, settings.fields);
            }
        }

        if (!isCustomGate && data.panel_group === 'a') {
            var gwRow = (settings.fields || []).find(function(f) {
                return f && f.key === 'gate_width';
            });
            var gwMm = parseInt(String(gwRow?.val || '').replace(/,/g, ''), 10);
            if (Number.isFinite(gwMm) && gwMm > 0) {
                settings.size = gwMm;
            }
        }


        var filtered_data = info.filter(function(item) {
            return item.control_key != modal_key;
        });

        var existingGateRow = (info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        })[0];

        if ($(FENCES.el.fencingPanelGate).length) {
            FENCE.call('update_hinge_panel', data.panel_group, placement);

            filtered_data.push({
                id: i,
                control_key: modal_key,
                settings: settings
            });
        } else if (existingGateRow) {
            filtered_data.push(existingGateRow);
        }

        localStorage.setItem('custom_fence-' + tab + '-' + i, JSON.stringify(filtered_data));
    },

    //----------------------------------------------------------------------------------

    update_gate: function(action) {
        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data;

        var panel_count = $('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel)').length;

        var find_gate = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        if(find_gate.length) {
            placement = find_gate[0]?.settings?.placement;
        } else {
            placement = 0;
        }

        var center_point = FENCE.get(i, 'post'),
            mesurement = $(FENCES.el.measurementBoxNumber).val();

        var calc = calculate_fences();

        // Update spacing for glass fence
        if (info.panel_group == 'a') {
            var center_point = calc.selected_values.spacing;
        }

        var gate_data = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        var panel_size = calc.gate.length,
            panel_unit = FENCES.defaultValues.unit,
            gate_size = calc.gate.width;

        var gate_label_width = gate_size;
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.getGateDisplayWidthMm === 'function') {
            var gwLbl = SlatFence.getGateDisplayWidthMm(i, gate_data, calc);
            if (Number.isFinite(gwLbl) && gwLbl > 0) {
                gate_label_width = gwLbl;
            }
        }

        var center_post = FENCE.settings.item.center_point;
        var panel_size_center = SlatFence.getGatePanelSizeCenter(i, gate_size, center_point, gate_data);


        var gate_hinge_type = gate_data[0]?.settings?.fields?.find(function(item) {
            return item.key == 'gate_hinge_type';
        });

        panel_name = 'GATE';
        gate_class = '';

        if(gate_hinge_type) {
            panel_name = gate_hinge_type?.val == 'opt-1' ? 'STD GATE' : 'SC GATE';
            gate_class = 'hinge-type-'+gate_hinge_type?.val;
        } else if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(i)) {
            panel_name = 'SC GATE';
            gate_class = 'hinge-type-opt-2';
        }

        // Get default hinge to update gate width
        var gate_hinge_type_gate_width = info?.settings?.gate?.fields?.[0]?.options?.filter(function(item) {
            return item.slug == gate_hinge_type?.val;
        });
        if( gate_hinge_type_gate_width ) {
            // Update gate width based on Hinge Type
            const allowed = gate_hinge_type_gate_width[0]?.gate_width;
            if( allowed ) {
                $('[name="gate_width"] select option').each(function () {
                    if (!allowed.includes( parseInt($(this).val()) )) {
                        $(this).remove();
                    }
                });                            
            }
        }

        if(action == 'add' || action == 'edit') {

            if ($(FENCES.el.fencingPanelGate).length && action === 'edit') {
                // Gate already on diagram (partial reload) — skip duplicate insert.
            } else {

            var gate_swing_field = gate_data[0]?.settings?.fields?.find(function(item) {
                return item.key == 'gate_hinge_position';
            });
            var isLeftSwing =
                typeof fcGlassPoolIsLeftHandSwingFromGateData === 'function' &&
                fcGlassPoolIsLeftHandSwingFromGateData(gate_data);
            var hasHingePanel = $(FENCES.el.fencingPanelContainer).find('.extra-panel-item').length > 0;
            var leftFirstLayout = info.panel_group == 'a' && isLeftSwing && hasHingePanel;

            if(placement == -1 ) {

                if (leftFirstLayout) {
                    var tplLeftFirst = $('script[data-type="panel_gate-' + info.panel_group + '-l"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_label_width)
                        .replace(/{{panel_size_center}}/gi, panel_size_center)
                        .replace(/{{center_post}}/gi, center_post)
                        .replace(/{{panel_name}}/gi, panel_name)
                        .replace(/{{panel_unit}}/gi, panel_unit);

                    $('#panel-item-0').after(tplLeftFirst);
                } else {
                    var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-r"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_label_width)
                        .replace(/{{panel_size_center}}/gi, panel_size_center)
                        .replace(/{{center_post}}/gi, center_post)
                        .replace(/{{panel_name}}/gi, panel_name)
                        .replace(/{{panel_unit}}/gi, panel_unit);

                    $('#panel-item-0, #panel-item-x').before(tpl);
                }

            } else if(find_gate.length && placement >= 0) {

                // if panel placement doesn't exist
                if($('#panel-item-' + placement).length == 0) {

                    panel_gate_side = (panel_count <= 1) ? 'r' : 'l';
            
                    var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-' + panel_gate_side +'"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_label_width)
                        .replace(/{{panel_size_center}}/gi, panel_size_center)
                        .replace(/{{center_post}}/gi, center_post)
                        .replace(/{{panel_name}}/gi, panel_name)
                        .replace(/{{panel_unit}}/gi, panel_unit);

                    $('#panel-item-0, #panel-item-x').after(tpl);

                } else {

                    var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-l"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_label_width)
                        .replace(/{{panel_size_center}}/gi, panel_size_center)
                        .replace(/{{center_post}}/gi, center_post)
                        .replace(/{{panel_name}}/gi, panel_name)
                        .replace(/{{panel_unit}}/gi, panel_unit);

                    $('#panel-item-' + placement).after(tpl);

                }

                $(FENCES.el.btnGate).addClass('edit-gate').removeClass('add-gate').html('<span>Add</span> Gate');

            } else if(action == 'add' && placement == 0) {

                temp = $('script[data-type="panel_gate-' + info.panel_group + '-l"]');

                if($('.fencing-panel-items [data-key="panel_options"]').length) {
                    temp = $('script[data-type="panel_gate-' + info.panel_group + '-r"]')
                }

                var tpl = temp.text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_label_width)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{center_post}}/gi, center_post)
                    .replace(/{{panel_name}}/gi, panel_name)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                var panelID = $('[data-cart-key="panel_options"].fencing-panel-item').attr('id');

                $('#'+panelID+', .fencing-panel-items .raked-panel-container').after(tpl);

                $(FENCES.el.btnGate).addClass('edit-gate').removeClass('add-gate').html('<span>Add</span> Gate');

            }

            }


        }

        if( find_gate.length ) {               
           $(FENCES.el.btnGate).addClass('edit-gate').removeClass('add-gate').html('<span>Gate</span> Options');
        } else {
            $(FENCES.el.btnGate).addClass('add-gate').removeClass('edit-gate').html('<span>Add</span> Gate');
        }

        // fence Height
        gateValue = '';

        if(calc.fence_size.height) {
            gateValue = calc.fence_size.height;
        }

        // Prevent duplicate spacing spans when gate is re-rendered (e.g. gate_type click).
        var $panelGate = $(FENCES.el.fencingPanelGate);
        if ($panelGate.length) {
            $panelGate.find('.fc-gate-spacing').remove();

            $panelGate.prepend('<span class="fc-gate-spacing fc-gate-left-spacing">'+FENCE.get(i, 'gate_space_left')+'</span>')
                .append('<span class="fc-gate-spacing fc-gate-right-spacing">'+FENCE.get(i, 'gate_space_right')+'</span>')
                .attr('data-cart-value', gateValue);    

            SlatFence.applyGateLabel(i, gate_data, calc, panel_unit, panel_name);
        }

        // Remove hinge type class
        $('.fencing-panel-container').removeClass('hinge-type-opt-1 hinge-type-opt-2').addClass(gate_class);     

        // Is custom gate (reuse gate_data from above)
        isCustomGate = gate_data[0]?.settings?.fields?.find(obj => obj['key'] === "use_std" && obj['val'] === false );

        if(isCustomGate && $panelGate.length) {
            $panelGate.css({ 'max-width': calc.gate.width * 0.1 });
        }

        if(action == 'add') {
            FENCE.call('calculateCustomGate');
        }

        FENCE.call('update_hinge_panel', info.panel_group, placement);

        // display gate next to the 1st panel
        if( info.panel_group == 'a' ) {
            if(action == 'add' || action != 'edit') {
                setTimeout(function(){
                    $('[data-move="right"]').trigger('click');    
                    FENCE.call('load_fencing_items');
                });
            }            
        }

    },
    
    //----------------------------------------------------------------------------------

    update_hinge_panel: function(group, placement) {

        if( group != 'a' ) return;

        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data;

        var gate_data = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        var gate_hinge_panel = gate_data[0]?.settings?.fields?.find(function(item) {
            return item.key == 'gate_hinge_panel_width';
        });

        var gate_width = gate_data[0]?.settings?.fields?.find(function(item) {
            return item.key == 'gate_width';
        });


        // Update gate width
        var gate_panel_width = 0;
        if(gate_width) {
            gate_panel_width = gate_width.val;
            updateGateSettings('size', gate_panel_width);
        }

        var gate_hinge_panel_width = 0;
        if(gate_hinge_panel) {
            gate_hinge_panel_width = gate_hinge_panel.val;
        }

        var cfHinge = { item: i, tab: tab },
            calcHinge = calculate_fences(cfHinge);
        var resolvedHingeWidthMm =
            typeof fcResolveGlassPoolHingePanelWidthMm === 'function'
                ? fcResolveGlassPoolHingePanelWidthMm(gate_data, calcHinge, info)
                : parseInt(gate_hinge_panel_width, 10) || 0;

        var gate_swing = gate_data[0]?.settings?.fields?.find(function(item) {
            return item.key == 'gate_hinge_position';
        });

        // Get default gate swing
        var gate_options_default = info.settings.gate.fields[4].options.filter(function(item) {
            return item.default == true;
        });

        var default_swing_slug = gate_options_default[0].slug;

        last_id = $(FENCES.el.panelItem + ':not(.fencing-raked-panel,.fencing-panel-gate,.extra-panel-item)')
            .last()
            .attr('data-id');

        var useGlassPoolPlacementDrive =
            typeof fcGlassPoolUsesPlacementDrive === 'function' &&
            fcGlassPoolUsesPlacementDrive(gate_data, $(FENCES.el.fencingPanelGate));

        setTimeout(function() {
            $('.fc-hinges-set').remove();

            swing_slug = gate_swing?.val ? gate_swing?.val : default_swing_slug;

            default_swing = true;

            op = placement;

            if( placement < 0 ) {
                default_swing = false;
                placement = 0;

                if( swing_slug.includes('left') ) {
                    default_swing = true;
                }
            }

         //   $('[data-slug="left-swing"], [data-slug="right-swing"]').parent().removeClass('disabled');

            if (!useGlassPoolPlacementDrive) {
                // if gate is on the left on the first placemnet and it has raked panel
                if( op < 0 && swing_slug.includes('left') && $('#panel-item-left-raked').length ) {
                    setGateFieldSettings(fd, 'gate_hinge_position', 'right-swing');
                    op = 1;
                    default_swing = false;

                    $('[data-slug="left-swing"]').removeClass('fc-selected');
                    $('[data-slug="right-swing"]').addClass('fc-selected');
                    $('[data-slug="left-swing"]').parent().addClass('disabled');
                }

                if( swing_slug.includes('right') ) {

                    if( op < 0 ) {
                        placement = 0;
                    } else if( placement == 0 ) {
                        placement = 1;
                    } else {
                        placement = parseInt(placement) + 1;
                    }

                    if( !$('#panel-item-'+placement).length ) {
                        placement = parseInt(placement) + 1;
                    }
                    default_swing = false;

                    // if gate is on the right on the first placemnet and it has raked panel
                    if( op == last_id && $('#panel-item-right-raked').length ) {

                        setGateFieldSettings(fd, 'gate_hinge_position', 'left-swing');
                        default_swing = true;
                        placement = last_id;

                        $('[data-slug="right-swing"]').removeClass('fc-selected');
                        $('[data-slug="left-swing"]').addClass('fc-selected');

                        $('[data-slug="right-swing"]').parent().addClass('disabled');
                    }

                }
            }

            if( group == 'a' && $('.fencing-panel-gate').length ) {

                var gate_hinge_type = gate_data[0]?.settings?.fields?.find(function(item) {
                    return item.key == 'gate_hinge_type';
                });
                var gate_position = gate_data[0]?.settings?.position;

                if (!useGlassPoolPlacementDrive) {
                    // Reset hinge panel (legacy path — placement drive uses finalize instead).
                    var panel_size = $('.panel-item.hinge-panel').attr('data-panel-size'),
                        panel_width = $('.panel-item.hinge-panel').data('width');

                    $('.hinge-panel .fc-panel-size').html(panel_size);
                    $('.hinge-panel .fc-panel-unit').html('PANEL');
                    $('.hinge-panel').removeClass('hinge-panel').css({'width':panel_width});

                    $('.hinge-panel .fc-panel-unit').html('PANEL');
                    $('.hinge-panel').removeClass('hinge-panel');
                }

                if ($('.extra-panel-item').length) {
                    $('.extra-panel-item')
                        .removeClass('hinge-panel-alt')
                        .addClass('hinge-panel')
                        .css({ 'width': resolvedHingeWidthMm * FENCE.get('item', 'base_margin') });
                    $('.fencing-panel-gate').css({ 'width': gate_panel_width * FENCE.get('item', 'base_margin') });

                    var panel_name = 'PANEL';
                    if (gate_hinge_type) {
                        panel_name = gate_hinge_type?.val == 'opt-1' ? 'STD HINGE' : 'SC HINGE';
                    }

                    $('.hinge-panel .fc-panel-unit').html(panel_name);
                    $('.hinge-panel .fc-panel-size').html(resolvedHingeWidthMm + 'W');
                }

                var hinges_panel = `<div class="fc-hinges-set">
                    <div class="fc-hinges fc-hinges-top">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>
                    </div> 
                    <div class="fc-hinges fc-hinges-bot">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>
                    </div>      
                </div>`;

                $('.hinge-panel').append(hinges_panel);

                var hinges_gate = `<div class="fc-hinges-set">
                    <div class="fc-hinges fc-hinges-top">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>    
                    </div> 
                    <div class="fc-hinges fc-hinges-center">
                        <i class="fa-latch"></i>
                    </div> 
                    <div class="fc-hinges fc-hinges-bot">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>                        
                    </div>      
                </div>`;

                $('.fencing-panel-gate').append(hinges_gate);

                $('.fencing-panel-gate').removeClass('panel-gate-left panel-gate-right');       

                if( default_swing ) {
                    $('.hinge-panel .fc-hinges-set').addClass('fc-hinges-right');
                    $('.fencing-panel-gate .fc-hinges-set').addClass('fc-hinges-left');       
                    $('.fencing-panel-gate').addClass('panel-gate-left');       
                } else {
                    $('.hinge-panel .fc-hinges-set').addClass('fc-hinges-left');
                    $('.fencing-panel-gate .fc-hinges-set').addClass('fc-hinges-right');    
                    $('.fencing-panel-gate').addClass('panel-gate-right');                                          
                }

                // Switch the hinge panel next to the gate (start/end corners: finalize handles order).
                left_swing_gate = $('.panel-gate-left').prev().prev().prev().attr('id');
                right_swing_gate = $('.panel-gate-right').next().next().next().attr('id');
                switch_panel_id = right_swing_gate ? right_swing_gate : left_swing_gate;
                var skipSwitchForEndHinge = String(op) === String(last_id) && swing_slug.includes('right');
                var skipSwitchForStartHinge =
                    (op < 0 && swing_slug.includes('right')) ||
                    (swing_slug.includes('left') && (op < 0 || gate_position === 'first'));
                if (useGlassPoolPlacementDrive) {
                    skipSwitchForEndHinge = true;
                    skipSwitchForStartHinge = true;
                }
                if (switch_panel_id && !skipSwitchForEndHinge && !skipSwitchForStartHinge) {
                    switchPanel('.extra-panel-item', '#'+switch_panel_id);
                }


                var gate_hinge_types = info.settings.gate.fields.find(function(item) {
                    return item.slug == 'gate_hinge_type';
                });
                var ght = gate_hinge_types.options.find(function(item) {
                    return item.slug == gate_hinge_type?.val;
                });


                var calc = calculate_fences();
                gaps = calc.selected_values.spacing;   

                $('.fencing-panel-spacing-number:not(.PTP90, .PTPA, .PTW)').find('span:not(.fs-clamp)').html(gaps);

                // Set gate spacing
                if( $('.fencing-panel-gate').hasClass('panel-gate-left') ) {
                    $('.fencing-panel-spacing-number.near-gate').first().find('span:not(.fs-clamp)').html( ght?.gap?.hinge );
                    $('.fencing-panel-spacing-number.near-gate').last().find('span:not(.fs-clamp)').html( ght?.gap?.latch );
                }
                if( $('.fencing-panel-gate').hasClass('panel-gate-right') ) {
                    $('.fencing-panel-spacing-number.near-gate').first().find('span:not(.fs-clamp)').html(ght?.gap?.latch);
                    $('.fencing-panel-spacing-number.near-gate').last().find('span:not(.fs-clamp)').html(ght?.gap?.hinge);            
                }

                var $glassFc =
                    typeof FENCE !== 'undefined' && typeof FENCE.resolveFenceSectionRoot === 'function'
                        ? FENCE.resolveFenceSectionRoot().find('.fencing-panel-container').first()
                        : $(FENCES.el.fencingPanelContainer).first();

                if (typeof fcFinalizeGlassPoolPanelLayout === 'function') {
                    fcFinalizeGlassPoolPanelLayout($glassFc, gaps, null);
                } else {
                    if (typeof fcEnsureGlassPoolHingeAdjacentToGate === 'function') {
                        fcEnsureGlassPoolHingeAdjacentToGate($glassFc);
                    }
                    if (typeof fcApplyGlassPoolPanelSpacingWidths === 'function') {
                        fcApplyGlassPoolPanelSpacingWidths(null, gaps, $glassFc);
                    }
                    if (typeof fcNormalizeGlassPoolGateAdjacentPosts === 'function') {
                        fcNormalizeGlassPoolGateAdjacentPosts($glassFc);
                    }
                }

                if (typeof fcSyncGateMoveControlsState === 'function') {
                    fcSyncGateMoveControlsState();
                }

            }
        });    
     
    },

    //----------------------------------------------------------------------------------

    refresh_gate_label: function() {
        var fd = getSelectedFenceData();
        var calc = calculate_fences();

        SlatFence.applyRefreshGateLabel(fd, calc);
    },

    //----------------------------------------------------------------------------------

    move_the_gate: function(move, opts) {
        var gate = $(FENCES.el.fencingPanelGate),
            panel_count = $('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)').length,
            skipGateOnlySync = opts && opts.skipGateOnlySync;

        var gate_data = [];
        var fdMoveGate = null;
        try {
            if (typeof getSelectedFenceData === 'function') {
                fdMoveGate = getSelectedFenceData();
                gate_data = (fdMoveGate && fdMoveGate.info || []).filter(function(item) {
                    return item && item.control_key === 'gate';
                });
            }
        } catch (eMoveGateData) {}

        var glassPoolPlacementDrive =
            typeof fcGlassPoolUsesPlacementDrive === 'function' &&
            fcGlassPoolUsesPlacementDrive(gate_data, gate);

        var curGlassPl =
            typeof fcReadGlassPoolGatePlacement === 'function'
                ? fcReadGlassPoolGatePlacement(gate_data)
                : -1;
        var glassPlBounds =
            glassPoolPlacementDrive && typeof fcGetGlassPoolGateMoveBoundary === 'function'
                ? fcGetGlassPoolGateMoveBoundary(curGlassPl, panel_count)
                : null;

        var boundary =
            glassPlBounds ||
            (typeof fcGateMoveBoundaryState === 'function'
                ? fcGateMoveBoundaryState(null, gate)
                : { atFirst: false, atLast: false });

        if ((move === 'left' || move === 'first') && boundary.atFirst) {
            return;
        }

        if ((move === 'right' || move === 'last') && boundary.atLast) {
            return;
        }

        if (glassPoolPlacementDrive && (move === 'left' || move === 'right')) {
            var nextGlassPl =
                typeof fcGlassPoolGatePlacementStep === 'function'
                    ? fcGlassPoolGatePlacementStep(curGlassPl, move, panel_count)
                    : curGlassPl;
            if (nextGlassPl === curGlassPl) {
                return;
            }
            if (
                typeof fcPersistGlassPoolGatePlacement !== 'function' ||
                !fdMoveGate ||
                !fcPersistGlassPoolGatePlacement(fdMoveGate, nextGlassPl, panel_count)
            ) {
                return;
            }
        } else if (glassPoolPlacementDrive && move === 'first') {
            if (
                typeof fcPersistGlassPoolGatePlacement !== 'function' ||
                !fdMoveGate ||
                !fcPersistGlassPoolGatePlacement(fdMoveGate, -1, panel_count)
            ) {
                return;
            }
        } else if (glassPoolPlacementDrive && move === 'last') {
            if (
                typeof fcPersistGlassPoolGatePlacement !== 'function' ||
                !fdMoveGate ||
                !fcPersistGlassPoolGatePlacement(fdMoveGate, panel_count, panel_count)
            ) {
                return;
            }
        } else if(move == 'left') {

            if(panel_count == 1) {

                closest_id = gate.prev().prev().prev().attr('id');
      
                if(closest_id == undefined) {
                    return;
                }

                move_gate = gate.prop("outerHTML") + gate.prev().prev().prop("outerHTML") + gate.prev().prop("outerHTML");

                gate.prev().prev().remove();
                gate.prev().remove();

                $('#panel-item-0, #panel-item-x').before(move_gate);

                gate.remove();

            } else {
                closest_id = gate.prev().prev().prev().attr('id');

                if($(FENCES.el.fencingPanelGate).index() == 1 || closest_id == undefined) {
                    return;
                }

                $(gate).swapWith($('#' + closest_id));

                gate.remove();
            }

        } else if(move == 'right') {
        
            if(panel_count == 1) {

                closest_id = gate.next().next().next().attr('id');
                
                if(closest_id == undefined) {
                    return;
                }

                move_gate = gate.next().prop("outerHTML") + gate.next().next().prop("outerHTML") + gate.prop("outerHTML");

                gate.next().next().remove();
                gate.next().remove();

                if( $('#panel-item-0').length == 0 ) {
                    $('#panel-item-1').after(move_gate);
                } else {
                    $('#panel-item-0, #panel-item-x').after(move_gate);
                }

                gate.remove();

            } else {
                closest_id = gate.next().next().next().attr('id');
  
                if(closest_id == undefined) {
                    return;
                }

                $(gate).swapWith($('#' + closest_id));

                gate.remove();
            }
        } else if(move == 'first') {

            if (typeof fcMoveGateToFirstPosition === 'function') {
                if (!fcMoveGateToFirstPosition(gate)) {
                    return;
                }
            } else {
                if (typeof fcGateIsAtFirstPosition === 'function' && fcGateIsAtFirstPosition(gate)) {
                    return;
                }

                var move_gate_first = typeof fcExtractGateMoveBundle === 'function'
                    ? fcExtractGateMoveBundle(gate)
                    : '';

                if (!move_gate_first) {
                    return;
                }

                var $insertBefore = $('#panel-item-0, #panel-item-x').first();
                if (!$insertBefore.length) {
                    $insertBefore = fcGetFirstRegularPanel$();
                }
                if ($insertBefore.length) {
                    $insertBefore.before(move_gate_first);
                } else if ($('.left_raked-panel').length) {
                    $('.left_raked-panel').first().after(move_gate_first);
                }

                gate.remove();
            }

            var fdFirst = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
            var skipFirstNudge = fdFirst && fdFirst.data && fdFirst.data.panel_group === 'a';

            if (!skipFirstNudge) {
                setTimeout(function () {
                    if ($('.fencing-panel-gate').hasClass('panel-gate-left')) {
                        FENCE.call('move_the_gate', 'right');
                    }
                }, 350);
            }

        } else if(move == 'last') {

            if (typeof fcMoveGateToLastPosition === 'function') {
                if (!fcMoveGateToLastPosition(gate)) {
                    return;
                }
            } else {
                var last_id = $(FENCES.el.panelItem+':not(.fencing-raked-panel)').last().attr('data-id');
                if (last_id === undefined) {
                    return;
                }
                var move_gate_last = typeof fcExtractGateMoveBundle === 'function'
                    ? fcExtractGateMoveBundle(gate)
                    : '';
                if (!move_gate_last) {
                    return;
                }
                $('#panel-item-' + last_id).after(move_gate_last);
                gate.remove();



            }

        } else if(move == 'delete') {

            var fdDel = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
            var slatMain =
                fdDel &&
                typeof SlatFence !== 'undefined' &&
                SlatFence.isMainSlatSlug(fdDel.slug);

            if (fdDel && typeof fcRemoveGateSegmentFromStorage === 'function') {
                fcRemoveGateSegmentFromStorage(fdDel.tab, fdDel.slug);
            }

            var index = $('#panel-item-0, #panel-item-x').index() / 3;

            $(FENCES.el.btnGate).addClass('add-gate').removeClass('edit-gate').html('<span>Add</span> Gate');

            $(FENCES.el.fencingPanelGate).removeAttr('data-cart-value');
            FCModal.close();
            $('.fc-btn-active').removeClass('fc-btn-active');

            if (gate.length) {
                if(index == 2) {
                    gate.next().next().remove();
                    gate.next().remove();
                } else {
                    gate.prev().prev().remove();
                    gate.prev().remove();
                }

                gate.remove();
            }

            $('.fencing-offcut.gate-offcut').remove();
            $('.fencing-panel-container .near-gate').removeClass('near-gate');

            if (!skipGateOnlySync) {
                if (typeof fcUncheckStep2GateOnlyAfterGateDelete === 'function') {
                    fcUncheckStep2GateOnlyAfterGateDelete();
                } else if (fdDel && typeof fcRemoveGateSegmentFromStorage === 'function') {
                    fcRemoveGateSegmentFromStorage(fdDel.tab, fdDel.slug);
                }
                if (
                    !slatMain &&
                    $('.select-gate_only.fc-selected').length
                ) {
                    $('.select-gate_only.fc-selected').trigger('click');
                }
            }

            try {
                FENCE.call('update_custom_fence_gate');
            } catch (eUpdG) {}
            try {
                FENCE.call('near_gate_spacing');
            } catch (eNear) {}
            try {
                btnCalculate();
            } catch (eCalc) {}
            try {
                if (typeof checkGateOnly === 'function') {
                    checkGateOnly();
                }
            } catch (eChk) {}
            if (slatMain && fdDel && typeof SlatFence.syncSlatGateAddAndOptionsEnabled === 'function') {
                try {
                    SlatFence.syncSlatGateAddAndOptionsEnabled(fdDel);
                } catch (eSlatBtn) {}
            }
            try {
                if (fdDel && typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(fdDel.slug)) {
                    setTimeout(function() {
                        try {
                            if (typeof fcRefreshPlannerCartForTab === 'function') {
                                fcRefreshPlannerCartForTab(fdDel.tab);
                            }
                        } catch (eCart) {}
                    }, 0);
                }
            } catch (eCartWrap) {}

            return;
        }

        FENCE.call('update_custom_fence_gate');

        FENCE.call('near_gate_spacing');

        btnCalculate();
        btnCalculate();

        if (typeof fcFenceDiagramScrollCenterAfterRender === 'function') {
            fcFenceDiagramScrollCenterAfterRender('.fencing-panel-gate', 280);
        } else if (typeof fcFenceDiagramScrollCenter === 'function') {
            fcFenceDiagramScrollCenter('.fencing-panel-gate', 280);
        } else if (typeof HELPER !== 'undefined' && typeof HELPER.getFenceDiagramHScroll$ === 'function') {
            HELPER.getFenceDiagramHScroll$().scrollCenter('.fencing-panel-gate', 280);
        } else {
            $('.fc-project-plan-hscroll').scrollCenter('.fencing-panel-gate', 280);
        }

        setTimeout(function() {
            if (typeof fcSyncGateMoveControlsState === 'function') {
                fcSyncGateMoveControlsState();
            }
        }, 350);
    },

    //----------------------------------------------------------------------------------

    near_gate_spacing: function() {
        $('.fencing-panel-container .near-gate').removeClass('near-gate');
        $('.fencing-panel-container .panel-gate-left').next().next().addClass('near-gate');
        $('.fencing-panel-container .panel-gate-left').next().addClass('near-gate');
        $('.fencing-panel-container .panel-gate-left').prev().prev().addClass('near-gate');
        $('.fencing-panel-container .panel-gate-left').prev().addClass('near-gate');

        $('.fencing-panel-container .panel-gate-right').next().addClass('near-gate');
        $('.fencing-panel-container .panel-gate-right').next().next().addClass('near-gate');
        $('.fencing-panel-container .panel-gate-right').prev().addClass('near-gate');
    },

    update_raked_panels: function(side) {
        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data;

        var filtered_data = custom_fence.filter(function(item) {
            return item.control_key == 'add_step_up_panels';
        });

        var settings = filtered_data[0]?.settings;

        var calc = calculate_fences();

        $(side).each(function(k, v) {

            // Side
            var side_part = v.replace('_raked', ''),
                has_post = 'yes-post',
                center_point = FENCE.get(i, 'post');

            var filtered_side_data = custom_fence.filter(function(item) {
                return item.control_key == side_part + '_side';
            });

            if(filtered_side_data) {

                if(filtered_side_data.length) {
                    var has_post = $(filtered_side_data[0].settings).map(function(k, item) {
                        if(item.key == side_part + '_option') {
                            return item.val;
                        }
                    }).get().join("");
                }

                if(has_post != 'yes-post' && has_post) {
                    var has_post = 'no-post ' + side_part + '-panel-post ' + has_post;
                }
            }



            // Raked
            var rakedSetting =
                typeof fcResolveStepUpRakedSetting === 'function'
                    ? fcResolveStepUpRakedSetting(custom_fence, v)
                    : (settings || []).find(function(item) {
                          return item && item.key === v;
                      });

            if (rakedSetting) {

                if(rakedSetting.val != 'none') {

                    var dim = rakedSetting.val.split('x');

                    if(side_part == 'left') {
                        panel_w = calc.left_raked.width;
                    } else {
                        panel_w = calc.right_raked.width;
                    }

                    panel_h = '';
                    panel_height = '';

                    if(dim) {
                        panel_h = dim[0];
                        panel_height = dim[1];
                    }

                    var tpl = $('script[data-type="' + v + '-panel-' + info.panel_group + '"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, panel_h)
                        .replace(/{{panel_unit}}/gi, panel_w)
                        .replace(/{{panel_height}}/gi, panel_height)
                        .replace(/{{panel_number}}/gi, side_part+'-raked')
                        .replace(/{{post}}/gi, has_post);

                    if(panel_h) {
                        $('.' + v + '-panel').html(tpl);
                    }

                }

            }

            if(side_part == 'left') {
                $('.panel-post:not(.post-left):not(.post-right)').first()
                    .addClass('post-left panel-' + has_post)
                    .attr('data-key', "left_side")
                    .attr('post-side', "post_left");

                $('.fencing-panel-spacing-number').first().addClass(has_post);

            }

            

            if(side_part == 'right') {

                $('.panel-post:not(.post-left):not(.post-right)').last()
                    .addClass('post-right panel-' + has_post)
                    .attr('data-key', "right_side")
                    .attr('post-side', "post_right");

                $('.fencing-panel-spacing-number').last().addClass(has_post);
            }

        });
        //

        if( info.panel_group == 'b' ) {
            // Left Panel post
            var left_panel_post = $('.left-panel-post.no-post span').text()
                .replace('-', '')
                .replace('(', '')
                .replace(')', '');

            $('.left-panel-post.no-post span').text('(0)');

            // Right Panel Post
            var right_panel_post = $('.right-panel-post.no-post span').text()
                .replace('-', '')
                .replace('(', '')
                .replace(')', '');

            $('.right-panel-post.no-post span').text('(0)');
        }

        $('.no-post-swivel-bracket span').after('<span class="sw sw-top">SW</span><span class="sw sw-bot">SW</span>');

        FENCE.call('load_post_options_all', custom_fence, info, tab, calc);

        FENCE.call('load_post_options_first_last_values', custom_fence, info, tab, calc);

        if (i === 'glass_pool' && typeof fcFinalizeGlassPoolPanelLayout === 'function') {
            var $glassFcPlanner = FENCE.resolveFenceSectionRoot(tab).find('.fencing-panel-container').first();
            if (!$glassFcPlanner.length) {
                $glassFcPlanner = $(FENCES.el.fencingPanelContainer).first();
            }
            fcFinalizeGlassPoolPanelLayout($glassFcPlanner, calc.selected_values.spacing, tab);
        }

        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(i)) {
            SlatFence.syncSlatNoPostSpacingLabels(FENCES.el.fencingPanelContainer);
            SlatFence.syncSlatNoPostEndCenterMarkers(FENCES.el.fencingPanelContainer);
        }

        // Adjust side spacing value
        if( info.panel_group == 'a' ) {

        // Update spacing for glass fence
            var center_point = calc.selected_values.spacing;

            var left_side_width = HELPER.sideOptionValue('left', custom_fence, info);
            if( left_side_width < 0 ) {
                left_side_width = center_point;
            }
            $('.left-panel-post.no-post span:first-child').text('('+left_side_width+')');

            var right_side_width = HELPER.sideOptionValue('right', custom_fence, info);
            if( right_side_width < 0 ) {
                right_side_width = center_point;
            }
            $('.right-panel-post.no-post span:first-child').text('('+right_side_width+')');
        }


        var $fcResult = $('.fencing-display-result .fc-project-plan-hscroll .fc-result').first();
        if (!$fcResult.length) {
            $fcResult = $('.fencing-display-result .fc-result').first();
        }
        if ($fcResult.length) {
            $fcResult.css({ 'padding': '', 'margin-top': '' });
            if ($('.raked-panel .fencing-raked-panel').length && $fcResult.css('margin-top') !== '70px') {
                $fcResult.css({ 'padding-top': '40px' });
            } else {
                $fcResult.css({ 'padding-top': '' });
            }
        }
        $('.fencing-display-result').css({ 'padding': '', 'margin-top': '' });

        $('.raked-panel .fencing-panel-item').css({ 'width': 1200 * FENCE.get('item', 'base_margin') });
    },

    //----------------------------------------------------------------------------------

    /**
     * Planner uses a single diagram root (#pp-0); project plan uses #pp-{tab} per section.
     */
    resolveFenceSectionRoot: function(sectionId) {
        var id = sectionId != null && sectionId !== '' ? sectionId : 0;
        var $root = $('#pp-' + id);
        if ($root.length) {
            return $root;
        }
        if (typeof FENCES !== 'undefined') {
            var $fc = $(FENCES.el.fencingPanelContainer).first();
            if ($fc.length) {
                return $fc;
            }
        }
        return $();
    },

    /**
     * This function will update either First or Last post after user selection
     * @param {array} custom_fence 
     */
    load_post_options_first_last_values: function(custom_fence, info, sectionId, calc) {
        var modal_key = $(FENCES.el.fencingContainer).attr('data-key');
        var side_post = '';

        var $sectionRoot = FENCE.resolveFenceSectionRoot(sectionId);

        function applySidePanelSpacingLabel(sideClass, val) {
            if (!val || val === 'side-gap' || !$sectionRoot.length) {
                return;
            }
            var $spacing = $sectionRoot.find('.fencing-panel-spacing-number.' + sideClass).first();
            if (!$spacing.length) {
                return;
            }
            $spacing.find('span.cg-top').remove();
            var $anchor = $spacing.find('span').not('.cg-top').last();
            if (!$anchor.length) {
                $anchor = $spacing;
            }
            $anchor.after('<span class="sw cg-top">' + val + '</span>');
            $spacing.find('span.cg-top')
                .attr('data-cart-key', 'side_panel_spacing')
                .attr('data-cart-value', val);
        }

        //Get the settings of post_option from left_side and right_side
        var post_options_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === "left_side" || item.control_key === "right_side";
        });

        // Get default post options
        var post_options_default = info.settings.post_options.fields[0].options.filter(function(item) {
            return item.default == true;
        });


        // LEFT SIDE OPTION
        var left_side = custom_fence.filter(function(item) {
            return item.control_key == 'left_side';
        });

        if(left_side.length && info.panel_group == 'a') {
            var left_side = left_side[0]?.settings?.filter(function(item) {
                return item.key == 'left_option';
            });
            val = left_side[0]?.val;
            applySidePanelSpacingLabel('left-panel-post', val);
        }

         // RIGHT SIDE OPTION
        var right_side = custom_fence.filter(function(item) {
            return item.control_key == 'right_side';
        });

        if(right_side.length && info.panel_group == 'a') {
            var right_side = right_side[0]?.settings?.filter(function(item) {
                return item.key == 'right_option';
            });
            val = right_side[0]?.val;
            applySidePanelSpacingLabel('right-panel-post', val);
        }


        if(post_options_filtered_data.length) {

            //iterate both left and right side and get the values of post_options
            for (let i = 0; i < post_options_filtered_data.length; i++) {
                let activeSetting = post_options_filtered_data[i].control_key;
                let settings = post_options_filtered_data[i].settings;

                for (let idx = 0; idx < settings.length; idx++) {
                    let key = settings[idx].key;
                    let value = settings[idx].val ? settings[idx].val : post_options_default[0].slug;

                    if(key === "post_option" && modal_key != 'post_options') {

                        postValue = value;

                        if(calc.fence_size.height) {
                            postValue = value.concat("+", calc.fence_size.height);
                        }

                        //We added data-key attribute on the first and last panel post both will have either left_side or right_side value
                        //Find the element that matches the condition below and add the class
                        $sectionRoot.find('.panel-post[data-key=' + activeSetting + '], .fencing-panel-spacing-number')
                            .addClass(value)
                            .attr('data-cart-value', postValue);

                    }
                }

            }

        }
    },

    //----------------------------------------------------------------------------------

    /**
     * This function will update all posts except for the first and last post 
     * @param {array} custom_fence 
     * @param {obj} info 
     */
    load_post_options_all: function(custom_fence, info, tab, calc) {
        var $sectionRoot = FENCE.resolveFenceSectionRoot(tab);
        let panel_post = $sectionRoot.find('.panel-post');
        let panel_spacing_number = $sectionRoot.find('.fencing-panel-spacing-number');

        var modal_key = $(FENCES.el.fencingContainer).attr('data-key');
        var exclude_panel_posts = '';

        var fd = getSelectedFenceData();

        var i = fd.slug;

        var post_options_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === 'post_options';
        });

        var left_side_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === 'left_side';
        });

        var right_side_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === 'right_side';
        });

        var edit_spacing_data = custom_fence.filter(function(item) {
            return item.control_key === 'edit_spacing';
        });

        var left_planel_class = right_planel_class = "";

        if(modal_key != 'post_options' && left_side_filtered_data.length) {
            var left_panel_class = ".post-left";
        }

        if(modal_key != 'post_options' && right_side_filtered_data.length) {
            var right_panel_class = ".post-right";
        }


        if(post_options_filtered_data.length) {
            //Get the value of Post Option
            var post_options_setting = post_options_filtered_data[0].settings.find(function(item) {
                return item.key === "post_option";
            });
    


            var firstPostClass = panel_post.length ? String(panel_post.first().attr('class') || '') : '';

            if(!$('#fc-planning-form').length ||
                typeof post_options_setting !== "undefined" &&
                firstPostClass.indexOf('opt-') === -1) {

                // Fence height
                postValue = post_options_setting.val;

                if(calc.fence_size.height) {
                    postValue = postValue.concat("+", calc.fence_size.height);
                }

                panel_post.not(left_panel_class)
                    .not(right_panel_class)
                    .addClass(post_options_setting?.val)
                    .attr('data-cart-value', postValue);

                panel_spacing_number.addClass(post_options_setting?.val);

                $sectionRoot.find('.fencing-panel-spigots.panel-post')
                    .addClass(post_options_setting?.val)
                    .attr('data-cart-value', postValue);
            }
    
        } else {

            // Get default post options
            var post_options_default = info.settings.post_options.fields[0].options.find(function(item) {
                return item.default == true;
            });

            // Fence height
            postValue = post_options_default.slug;

            if(calc.fence_size.height) {
                postValue = postValue.concat("+", calc.fence_size.height);
            }

            panel_post.not(left_panel_class)
                .not(right_panel_class)
                .attr('data-cart-value', postValue)
                .addClass(post_options_default?.slug);

            $sectionRoot.find('.fencing-panel-spigots.panel-post')
                .addClass(post_options_default?.slug)
                .attr('data-cart-value', postValue);

            // Update post spacing
            panel_spacing_number_width = 10;
            spacing_width = calc.selected_values.spacing;
            if( spacing_width ) {
                panel_spacing_number_width = spacing_width/10;
            } 

            // Glass pool (panel group `a`): strip shows the solver’s panel gap. Tubular styles (`b`, etc.): keep
            // post width from `{{center_point}}` in the template (e.g. Flat Top 50mm) — never reuse glass gap here.
            if(info.panel_group == 'a') {
                panel_spacing_number.addClass(post_options_default?.slug).css({'width': panel_spacing_number_width});
                if (SlatFence.shouldApplyPostSpacingOverride(info?.slug)) {
                    panel_spacing_number.find('span').html(String(calc.selected_values.spacing));
                }
            } else {
                panel_spacing_number.addClass(post_options_default?.slug).css({'width': panel_spacing_number_width});
            }

        }


        /* Load Clamps */
        var panel_option_data = custom_fence.filter(function(item) {
            return item.control_key == 'panel_options_custom';
        });

        var post_option = panel_option_data[0]?.settings?.find(function(item) {
            return item.key == 'post_option';
        });

        var post_options_info = info?.settings?.panel_options_custom?.fields?.filter(function(item) {
            return item.slug == 'post_option';
        });

        if( post_options_info ) {
            var post_options_default = post_options_info[0]?.options.find(function(item) {
                return item.default == true;
            });            
        }

        defaultVal = post_option?.val ? post_option?.val : post_options_default?.slug;

        // Set Clamps
        if( defaultVal && info.panel_group == 'a'  ) {
            $sectionRoot.find('.fencing-panel-spacing-number').addClass('panel-'+defaultVal).append('<span class="fs-clamp"></span>');
        }

        if (info.slug === 'barr' && typeof FENCES !== 'undefined' && FENCES.cartItems && typeof FENCES.cartItems.isBarrCornerPost === 'function') {
            var barrCornerCtx = {
                tabIndex: tab,
                fenceSlug: 'barr',
                fenceInfo: custom_fence,
                tabInfo: []
            };
            var $barrRoot = $sectionRoot[0];
            $sectionRoot.find('.panel-post').each(function() {
                if (FENCES.cartItems.isBarrCornerPost(this, barrCornerCtx, $barrRoot)) {
                    $(this).attr('data-cart-key', 'panel_post_corner');
                } else {
                    $(this).attr('data-cart-key', 'panel_post');
                }
            });
        }

    },

    //----------------------------------------------------------------------------------

    set_cutom_fence_data: function(opts) {
        opts = opts || {};
        if (
            !opts.force &&
            $('.fc-planner-page').length &&
            typeof fcIsStep2DomDirty === 'function' &&
            fcIsStep2DomDirty()
        ) {
            return;
        }

        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data,
            tabInfo = fd.tabInfo;

        var modal_key = fd.modalKey,
            mbn = fd.mbn;

        // Replace the row for this section; legacy rows without `tab` would never match and
        // caused duplicate `[0]` entries and lost Step 2 restores.
        var filtered_data_tabs = tabInfo.filter(function(item) {
            if (item.tab === undefined || item.tab === null) {
                return false;
            }
            return item.tab != tab;
        });

        if(info == undefined) {

            $('.fc-tab-title, .fc-tab-subtitle').html('');
            $(FENCES.el.jsFcFormStep).hide();
            $(FENCES.el.fsiSelected).removeClass('fsi-selected');

            return;
        }

        var mbnTrim = String(mbn != null ? mbn : '').trim();
        var mbnNum = parseInt(mbnTrim, 10);
        var cvBy = tabInfo[0]?.calculateValueByStyle || {};
        var icBy = tabInfo[0]?.isCalculateByStyle || {};
        var prevCalcThisStyle = Object.prototype.hasOwnProperty.call(cvBy, i) ? cvBy[i] : undefined;
        var nextCalculateValue =
            mbnTrim !== '' && Number.isFinite(mbnNum)
                ? mbnNum
                : prevCalcThisStyle !== undefined && prevCalcThisStyle !== null && prevCalcThisStyle !== ''
                  ? prevCalcThisStyle
                  : tabInfo[0]?.style === i && tabInfo[0]?.calculateValue != null && tabInfo[0]?.calculateValue !== ''
                    ? tabInfo[0].calculateValue
                    : FENCES.defaultValues.measurement;

        var prevIsCalcThisStyle = Object.prototype.hasOwnProperty.call(icBy, i) ? icBy[i] : undefined;
        var nextIsCalculate =
            prevIsCalcThisStyle !== undefined && prevIsCalcThisStyle !== null && prevIsCalcThisStyle !== ''
                ? prevIsCalcThisStyle
                : tabInfo[0]?.style === i && tabInfo[0]?.isCalculate != null
                  ? tabInfo[0].isCalculate
                  : FENCES.defaultValues.measurement;

        // Save both form-control fields and radio selections (e.g. width_dimension_from).
        // Radio fields in Step 2 are not `.form-control`, so include them explicitly.
        // Scope to Step 2 so modal / other `[data-action="change"]` regions cannot pollute storage.
        var changeFields =
            typeof fcCollectStep2FieldsFromDom === 'function'
                ? fcCollectStep2FieldsFromDom()
                : $('[data-section="2"] [data-action="change"] .form-control').serializeArray();
        if (typeof fcCollectStep2FieldsFromDom !== 'function') {
            $('[data-section="2"] [data-action="change"] input[type="radio"]:checked').each(function() {
                const name = this.name;
                const value = this.value;
                if (!name) return;
                const idx = changeFields.findIndex(f => f?.name === name);
                if (idx >= 0) {
                    changeFields[idx].value = value;
                } else {
                    changeFields.push({ name, value });
                }
            });
        }

        try {
            if (SlatFence.isSlatLike(i)) {
                var gapDomVal = $('[data-section="2"] [name="slat_gap"]').val();
                var gapRow = changeFields.find(function(f) {
                    return f && f.name === 'slat_gap';
                });
                SlatFence.persistSlatGapFromStep2(tab, i, gapRow ? gapRow.value : gapDomVal);

                var sizeDomVal = $('[data-section="2"] [name="slat_size"]').val();
                var sizeRow = changeFields.find(function(f) {
                    return f && f.name === 'slat_size';
                });
                SlatFence.persistSlatSizeFromStep2(tab, i, sizeRow ? sizeRow.value : sizeDomVal);

                var mhTab = document.querySelector('[data-section="2"] [name="max_fence_height"]');
                var mhPersist = '';
                if (mhTab && mhTab.value && !mhTab.disabled) {
                    mhPersist = mhTab.value;
                } else if (typeof fcStep2RestoreFieldsForStyle === 'function') {
                    var restoredH = fcStep2RestoreFieldsForStyle(tabInfo[0], i);
                    for (var hxi = 0; hxi < restoredH.length; hxi++) {
                        if (restoredH[hxi] && restoredH[hxi].name === 'max_fence_height' && restoredH[hxi].value) {
                            mhPersist = restoredH[hxi].value;
                            break;
                        }
                    }
                }
                if (!mhPersist && typeof SlatFence.getMaxFenceHeightValForStep2 === 'function') {
                    mhPersist = SlatFence.getMaxFenceHeightValForStep2(tabInfo[0], i);
                }
                if (mhPersist) {
                    SlatFence.persistMaxFenceHeightFromStep2(tab, i, mhPersist);
                    var mhRow = changeFields.find(function(f) {
                        return f && f.name === 'max_fence_height';
                    });
                    if (mhRow) {
                        mhRow.value = mhPersist;
                    } else {
                        changeFields.push({ name: 'max_fence_height', value: mhPersist });
                    }
                }
            }
        } catch (eGap) {}

        try {
            var mhNormEl = document.querySelector('[data-section="2"] [name="max_fence_height"]');
            var mhNormRaw =
                mhNormEl && !mhNormEl.disabled ? String(mhNormEl.value || '').trim() : '';
            var mhNormValid =
                !!mhNormRaw &&
                SlatFence.validateMaxFenceHeightField(mhNormEl).valid;
            if (mhNormValid) {
                changeFields = SlatFence.normalizeStep2FieldsBeforeSave({
                    slug: i,
                    prevFields:
                        typeof fcStep2RestoreFieldsForStyle === 'function'
                            ? fcStep2RestoreFieldsForStyle(tabInfo[0], i)
                            : tabInfo[0]?.fieldsByStyle?.[i] || tabInfo[0]?.fields || [],
                    nextFields: changeFields,
                    maxHeightEl: mhNormEl
                });
            } else {
                changeFields = changeFields.filter(function(f) {
                    return !f || f.name !== 'max_fence_height';
                });
            }
        } catch (e) {}

        const fieldsByStyle = {
            ...(tabInfo[0]?.fieldsByStyle || {}),
            [i]: changeFields
        };

        var gateSegRow = (custom_fence || []).filter(function(row) {
            return row.control_key === 'gate';
        })[0];
        var gateOnlyFromSegment = !!(gateSegRow && gateSegRow.settings && gateSegRow.settings.gateOnly);
        var gbsGo = tabInfo[0]?.gateOnlyByStyle || {};
        var gateOnlyVal;
        if (
            typeof fcStyleHasExplicitGateOnlyFlag === 'function' &&
            fcStyleHasExplicitGateOnlyFlag(tabInfo[0], i)
        ) {
            gateOnlyVal =
                typeof fcGetStep2GateOnlyForStyle === 'function'
                    ? !!fcGetStep2GateOnlyForStyle(tab, i, tabInfo[0])
                    : !!gbsGo[i];
        } else if (Object.prototype.hasOwnProperty.call(gbsGo, i)) {
            gateOnlyVal = !!gbsGo[i];
        } else {
            gateOnlyVal = !!gateOnlyFromSegment;
            // Only inherit tab-level Gate ONLY when this row is still the same style
            // (never when switching away from Slat Gate ONLY to another style).
            if (!gateOnlyVal && tabInfo[0]?.style === i && tabInfo[0]?.gateOnly) {
                gateOnlyVal = true;
            }
        }

        var $mbnEl = $(FENCES.el.measurementBoxNumber);
        var cvByM = cvBy;
        var icByM = icBy;

        filtered_data_tabs.push({
            tab: tab,
            style: i,
            fence: info.slug,
            mbn: mbn,
            gateOnly: gateOnlyVal,
            fields: changeFields,
            fieldsByStyle: fieldsByStyle,
            measurementByStyle: {
                ...(tabInfo[0]?.measurementByStyle || {}),
                [i]: {
                    val: mbnTrim,
                    dataLast: $mbnEl.attr('data-last') || '',
                    dataPrevGateOnlyMbn: $mbnEl.attr('data-prev-gate-only-mbn') || ''
                }
            },
            calculateValueByStyle: {
                ...(cvByM || {}),
                [i]: nextCalculateValue
            },
            isCalculateByStyle: {
                ...(icByM || {}),
                [i]: nextIsCalculate
            },
            gateOnlyByStyle: {
                ...(tabInfo[0]?.gateOnlyByStyle || {}),
                [i]: gateOnlyVal
            },
            isCalculate: nextIsCalculate,
            calculateValue: nextCalculateValue
        });

        localStorage.setItem('custom_fence-' + tab, JSON.stringify(filtered_data_tabs));

        if (typeof fcSyncPlannerTabFenceStyle === 'function') {
            fcSyncPlannerTabFenceStyle($(FENCES.el.fencingTabSelected), tab);
        }
    },

    //----------------------------------------------------------------------------------

    updateOverallPosts: function() {
        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data,
            modalKey = fd.modalKey;

        // Overwrite side panel posts
        if(modalKey == 'post_options') {

            //Get the settings of post_option from left_side and right_side
            var post_options_filtered_data = custom_fence.filter(function(item) {
                return item.control_key === 'post_options';
            });

            $(custom_fence).each(function(k, v) {
                if(v.control_key == 'left_side' || v.control_key == 'right_side') {
                    $(custom_fence[k].settings).each(function(lok, lov) {
                        if(lov.key == 'post_option') {
                            custom_fence[k].settings[lok].val = post_options_filtered_data[0].settings[0].val;
                            localStorage.setItem(`custom_fence-${tab}-${i}`, JSON.stringify(custom_fence));
                        }
                    });
                }
            });
        }
    },

    //----------------------------------------------------------------------------------

    disabledCustomGate: function() {
        var fd = getSelectedFenceData(),
            tab = fd.tab,
            slug = fd.slug,
            width = fd?.data?.settings?.gate?.size?.width;

        $('[name="width"]').attr('readonly', 'readonly').addClass('disabled text-muted').val(width);

        $('.custom-gate .fc-gate-modal-custom-width-section .fencing-qty-btn').addClass('disabled');

        if (typeof SlatFence !== 'undefined' && SlatFence.enableGateModalHeightQtyButtons) {
            SlatFence.enableGateModalHeightQtyButtons();
        }

        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug)) {
            SlatFence.syncGateModalCalculateButtonState();
        } else {
            $('.custom-gate .fc-gate-modal-custom-width-section button').attr('disabled', 'disabled')
                .removeClass('btn-dark')
                .addClass('btn-light disabled');
        }
    },

    //----------------------------------------------------------------------------------

    calculateCustomGate: function() {
        FENCE.call('update_custom_fence_gate');
        FENCE.call('load_fencing_items');

        if (typeof fcFenceDiagramScrollCenterAfterRender === 'function') {
            fcFenceDiagramScrollCenterAfterRender('.fencing-panel-gate', 300);
        } else if (typeof fcFenceDiagramScrollCenter === 'function') {
            fcFenceDiagramScrollCenter('.fencing-panel-gate', 300);
        } else if (typeof HELPER !== 'undefined' && typeof HELPER.getFenceDiagramHScroll$ === 'function') {
            HELPER.getFenceDiagramHScroll$().scrollCenter('.fencing-panel-gate', 300);
        } else {
            $('.fc-project-plan-hscroll').scrollCenter('.fencing-panel-gate', 300);
        }

        setTimeout(function() {
            if (typeof fcSyncGateMoveControlsState === 'function') {
                fcSyncGateMoveControlsState();
            }
        }, 350);
    },

    //----------------------------------------------------------------------------------

    /** Planner styles that use gate minimum Overall Length (excludes slat infill). */
    isGateMinOalStyle: function(slug) {
        var s =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(String(slug || ''))
                : String(slug || '');
        if (!s || s === 'slat_fence_infill') {
            return false;
        }
        if (s === 'slat' || s === 'slat_fence') {
            return true;
        }
        return !!FENCE.settings[s];
    },

    isStdGate: function(gate_data) {
        var row = gate_data?.[0]?.settings?.fields?.find(function(item) {
            return item?.key === 'use_std';
        });
        if (!row) {
            return true;
        }
        return row.val !== false && String(row.val).toLowerCase() !== 'false';
    },

    isStdGateFd: function(fd) {
        if (!fd || !this.isGateMinOalStyle(fd.slug)) {
            return false;
        }
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        return gate_data.length > 0 && this.isStdGate(gate_data);
    },

    isCustomGateFd: function(fd) {
        if (!fd || !this.isGateMinOalStyle(fd.slug)) {
            return false;
        }
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        return gate_data.length > 0 && !this.isStdGate(gate_data);
    },

    isDoubleGate: function(gate_data) {
        return (
            gate_data?.[0]?.settings?.fields?.find(function(item) {
                return item?.key === 'gate_type';
            })?.val === 'double'
        );
    },

    resolveStdGateLeafWidthNominalMm: function(slug, gate_data) {
        var s =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(String(slug || ''))
                : String(slug || '');
        var g = gate_data?.[0]?.settings;
        if (g && g.size != null && String(g.size) !== '') {
            var n = parseInt(String(g.size).replace(/,/g, ''), 10);
            if (Number.isFinite(n) && n > 0) {
                return n;
            }
        }
        var gw = g?.fields?.find(function(item) {
            return item && item.key === 'gate_width';
        })?.val;
        if (gw != null && String(gw) !== '') {
            var n2 = parseInt(String(gw).replace(/,/g, ''), 10);
            if (Number.isFinite(n2) && n2 > 0) {
                return n2;
            }
        }
        try {
            var def = parseInt(
                String(
                    typeof fc_data !== 'undefined' && fc_data[s]
                        ? fc_data[s]?.settings?.gate?.size?.width
                        : ''
                ).replace(/,/g, ''),
                10
            );
            if (Number.isFinite(def) && def > 0) {
                return def;
            }
        } catch (eDef) {}
        return s === 'flat_top' ? 970 : 975;
    },

    resolveGlassPoolHingeGapsMm: function(fd, gate_data) {
        var info = fd?.data;
        var gate_hinge_type = gate_data?.[0]?.settings?.fields?.find(function(item) {
            return item.key === 'gate_hinge_type';
        });
        var gate_hinge_types = info?.settings?.gate?.fields?.find(function(item) {
            return item.slug === 'gate_hinge_type';
        });
        var ght = gate_hinge_types?.options?.find(function(item) {
            return item.slug === gate_hinge_type?.val;
        });
        if (!ght && gate_hinge_types?.options?.length) {
            ght =
                gate_hinge_types.options.find(function(item) {
                    return item && item.default;
                }) || gate_hinge_types.options[0];
        }
        return {
            hinge: parseInt(ght?.gap?.hinge, 10) || 0,
            latch: parseInt(ght?.gap?.latch, 10) || 0
        };
    },

    /**
     * STD gate minimum outside Overall Length — opening + posts + gaps − removed end posts.
     * Glass Pool: gate width + hinge/latch gaps.
     */
    getStdGateMinOverallPhysicalMm: function(fd) {
        if (!fd || !this.isGateMinOalStyle(fd.slug)) {
            return null;
        }
        var slug =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(fd.slug)
                : fd.slug;
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        if (!gate_data.length || !gate_data[0].settings) {
            return null;
        }

        var minusPosts = this.minus_posts(fd.info || []);

        if (fd.data?.panel_group === 'a') {
            var gateSize = parseInt(String(gate_data[0].settings.size || '').replace(/,/g, ''), 10);
            if (!Number.isFinite(gateSize) || gateSize <= 0) {
                gateSize = this.resolveStdGateLeafWidthNominalMm(slug, gate_data);
            }
            var gaps = this.resolveGlassPoolHingeGapsMm(fd, gate_data);
            var glassOverall = gateSize + gaps.hinge + gaps.latch - minusPosts;
            return Number.isFinite(glassOverall) && glassOverall > 0 ? glassOverall : null;
        }

        var leaf = this.resolveStdGateLeafWidthNominalMm(slug, gate_data);
        var totalOpening = this.isDoubleGate(gate_data) && Number.isFinite(leaf) ? leaf * 2 : leaf;

        var post = parseInt(this.get(slug, 'post'), 10);
        if (!Number.isFinite(post) || post <= 0) {
            post = 50;
        }
        var gapL = parseInt(this.get(slug, 'gate_space_left'), 10);
        if (!Number.isFinite(gapL)) {
            gapL = 20;
        }
        var gapR = parseInt(this.get(slug, 'gate_space_right'), 10);
        if (!Number.isFinite(gapR)) {
            gapR = 20;
        }

        var overall = totalOpening + post + post + gapL + gapR - minusPosts;
        return Number.isFinite(overall) && overall > 0 ? overall : null;
    },

    getCustomGateMinOverallPhysicalMm: function(fd, leafMm, isDouble) {
        if (!fd || !this.isGateMinOalStyle(fd.slug)) {
            return null;
        }
        var slug =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(fd.slug)
                : fd.slug;
        var leaf = parseInt(leafMm, 10);
        if (!Number.isFinite(leaf) || leaf <= 0) {
            return null;
        }
        var totalOpening = isDouble ? leaf * 2 : leaf;
        var fence_gate_posts_gaps = parseInt(this.get(slug, 'gate_posts_gaps'), 10);
        if (!Number.isFinite(fence_gate_posts_gaps)) {
            fence_gate_posts_gaps = 0;
        }
        var minusPosts = this.minus_posts(fd.info || []);
        var overall = totalOpening + fence_gate_posts_gaps - minusPosts;
        return Number.isFinite(overall) && overall > 0 ? overall : null;
    },

    resolveCustomGateLeafWidthMm: function(fd) {
        if (!fd) {
            return null;
        }
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var g = gate_data[0]?.settings;
        if (g && g.size != null && String(g.size) !== '') {
            var n = parseInt(String(g.size).replace(/,/g, ''), 10);
            if (Number.isFinite(n) && n > 0) {
                return n;
            }
        }
        var widthEl = document.querySelector('.custom-gate [name="width"]');
        if (widthEl && widthEl.value) {
            var w = parseInt(String(widthEl.value).replace(/,/g, ''), 10);
            if (Number.isFinite(w) && w > 0) {
                return w;
            }
        }
        return null;
    },

    _writeMeasurementOverall: function(disp, opts) {
        opts = opts || {};
        var $box =
            typeof FENCES !== 'undefined' && FENCES.el
                ? $(FENCES.el.measurementBoxNumber)
                : $('.measurement-box-number');
        if (!$box.length || disp == null || !Number.isFinite(disp) || disp <= 0) {
            return false;
        }

        var curDisplay = parseInt(String($box.val() || '').replace(/,/g, ''), 10);
        if (opts.postChange) {
            if (Number.isFinite(curDisplay) && (curDisplay === disp || curDisplay > disp)) {
                return false;
            }
        } else {
            if (Number.isFinite(curDisplay) && curDisplay === disp) {
                return false;
            }
            if (Number.isFinite(curDisplay) && curDisplay > disp) {
                return false;
            }
        }

        $box.val(disp);
        $box.attr('data-last', String(disp));

        if (opts.persist && typeof fcPersistStep2Immediate === 'function') {
            try {
                fcPersistStep2Immediate({ force: true });
            } catch (ePs) {}
        }
        return true;
    },

    syncOverallFromStdGateWidth: function(fd, opts) {
        opts = opts || {};
        fd =
            fd ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
        if (!fd || !this.isStdGateFd(fd)) {
            return false;
        }

        if (
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(fd.slug) &&
            typeof SlatFence.shouldLockStep2OverallForStdGateOnly === 'function' &&
            SlatFence.shouldLockStep2OverallForStdGateOnly(fd)
        ) {
            SlatFence.syncStep2GateOnlyOverallField(fd, opts);
            return true;
        }

        var disp = this.getStdGateMinOverallPhysicalMm(fd);
        return this._writeMeasurementOverall(disp, opts);
    },

    syncOverallFromCustomGateWidth: function(fd, opts) {
        opts = opts || {};
        fd =
            fd ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
        if (!fd || !this.isCustomGateFd(fd)) {
            return false;
        }

        if (
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(fd.slug) &&
            typeof SlatFence.syncStep2OverallFromCustomGateWidth === 'function'
        ) {
            return SlatFence.syncStep2OverallFromCustomGateWidth(fd, opts);
        }

        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var leaf = this.resolveCustomGateLeafWidthMm(fd);
        if (!leaf) {
            return false;
        }
        var disp = this.getCustomGateMinOverallPhysicalMm(
            fd,
            leaf,
            this.isDoubleGate(gate_data)
        );
        return this._writeMeasurementOverall(disp, opts);
    },

    syncGateOverallOnPostChange: function(fd, opts) {
        opts = opts || {};
        fd =
            fd ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
        if (!fd || !this.isGateMinOalStyle(fd.slug)) {
            return false;
        }

        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        if (!gate_data.length) {
            return false;
        }

        var syncOpts = { postChange: true, persist: !!opts.persist };
        if (this.isStdGate(gate_data)) {
            return this.syncOverallFromStdGateWidth(fd, syncOpts);
        }
        if (this.isCustomGateFd(fd)) {
            return this.syncOverallFromCustomGateWidth(fd, syncOpts);
        }
        return false;
    },

    //----------------------------------------------------------------------------------

    minus_posts: function(custom_fence) {
        _post = 0;

        var fd = getSelectedFenceData();
        
        var id = custom_fence[0]?.id ? custom_fence[0]?.id : fd.slug;
    
        post_panel = FENCE.get(id, 'post');

        var left_side = custom_fence.filter(function(item) {
            return item.control_key == 'left_side';
        });

        if(left_side.length) {
            var left_side = left_side[0]?.settings?.filter(function(item) {
                return item.key == 'left_option';
            });

            if(left_side[0]?.val.includes('no-post')) {
                _post += post_panel;
            }        
        }

        var right_side = custom_fence.filter(function(item) {
            return item.control_key == 'right_side';
        });

        if(right_side.length) {
            var right_side = right_side[0]?.settings?.filter(function(item) {
                return item.key == 'right_option';
            });


            if(right_side[0]?.val.includes('no-post')) {
                _post += post_panel;
            }        
        }
        
        return _post;
    }

    //----------------------------------------------------------------------------------

}
