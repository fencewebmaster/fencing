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
            min_raked: "Minimum <b>Overall Length</b> for <b>{{hasRaked}} RAKED</b> is <b>{{overall}}</b>mm"
        },
        item: {
            raked: 50 + 1200 + 50,
            raked_post: 1200 + 50,
            center_point: 25,
        },
        flat_top: {
            gate: 970 + 50 + 20 + 20,                             
            post: 50,
            minOnGate: 970 + 50 + 20 + 20 + 50, // 1110
            maxOnGate: 1160,
            minPanelWidthOnGate: 86,
            gate_post_gaps: 50 + 20 + 20,
            gate_posts_gaps: 50 + 20 + 20 + 50,
        },
        glass_pool: {
            gate: 970 + 80 + 20 + 20,                             
            post: 80,
            minOnGate: 970 + 80 + 20 + 20 + 80, // 1110
            maxOnGate: 1160,
            minPanelWidthOnGate: 86,
            gate_post_gaps: 80 + 20 + 20,
            gate_posts_gaps: 80 + 20 + 20 + 80,
        },
        barr: {
            gate: 975 + 25 + 20 + 20,                              
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
        return FENCE.settings[fence][key];
    },

    //----------------------------------------------------------------------------------

    load_fencing_items: function() {
        var fd = getSelectedFenceData();

        var slug = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data;

        $(FENCES.el.fencingPanelContainer)
            .html('')
            .attr('data-type', info?.slug)
            .attr('data-group', info?.panel_group)
            .removeClass('custom-height');

        var fence_height_filtered_data = info?.form?.filter(function(item) {
            return item.slug == 'fence_height';
        });

        if(fence_height_filtered_data) {
            $(FENCES.el.fencingPanelContainer).addClass('custom-height');
        }


        var calc = calculate_fences();

        if(!calc) {
            return;
        }

        var center_point = FENCE.get(slug, 'post');

        for (let i = 0; i < calc.long_panel.count; i++) {

            mesurement = $(FENCES.el.measurementBoxNumber).val();

            var panel_number = i,
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
                .replace(/{{data_key}}/gi, center_point)
                .replace(/{{center_point}}/gi, center_point)
                .replace(/{{panel_value}}/gi, panel_option_value)
                .replace(/{{panel_size}}/gi, panel_size + 'W')
                .replace(/{{panel_unit}}/gi, '<br>PANEL')
                .replace(/{{panel_number}}/gi, panel_number);

            // if( panel_size > FENCE.get(slug, 'minPanelWidthOnGate') ) { } 
            if(panel_size > 0) {
                $(FENCES.el.fencingPanelContainer).append(tpl);
            }

            $(FENCES.el.fencingPanelItem).css({ 'width': panel_size * 0.10 });
        }

        var tpl = $('script[data-type="panel_spacing-' + info.panel_group + '"]').text()
            .replace(/{{center_point}}/gi, center_point);


        $(FENCES.el.fencingPanelContainer).append(tpl);     

        // No panel item 
        if($('.single-panel, #panel-item-0').length == 0 && $('.panel-item:not(.fencing-raked-panel)').length == 0 ) {
            $(FENCES.el.fencingPanelContainer+' .panel-post').after('<div id="panel-item-x" class="single-panel"></div>'); 
        }


        if(calc.short_panel.count) {

            for (let i = 0; i < 1; i++) {

                var panel_number = i,
                    panel_size = calc.short_panel.length,
                    panel_unit = FENCES.defaultValues.unit;
                panel_option_value = calc.selected_values.panel_option;

                if(panel_option_value.indexOf('full') !== -1) {
                    panel_option_value = panel_option_value.split('_')[0];
                }

                // Fence height
                if(calc.fence_size.height) {
                    panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
                }

                var tpl = $('script[data-type="short_panel_item-' + info.panel_group + '"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, panel_size + 'W')
                    .replace(/{{panel_value}}/gi, panel_option_value)
                    .replace(/{{panel_unit}}/gi, '<br>PANEL')
                    .replace(/{{panel_number}}/gi, panel_number);

                $(FENCES.el.fencingPanelContainer).append(tpl);

                $(FENCES.el.shortPanelItem).attr('data-id', calc.long_panel.count + 1)
                    .attr('id', 'panel-item-' + (calc.long_panel.count + 1))
                    .css({ 'width': panel_size * 0.10 });

            }

            var tpl = $('script[data-type="panel_spacing-' + info.panel_group + '"]').text()
                .replace(/{{center_point}}/gi, center_point);

            $(FENCES.el.fencingPanelContainer).append(tpl);

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
            $('.fencing-offcut.panel-offcut .offcut-body').css({ 'width': calc.offcut_panel.length * 0.10 });
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
            $('.fencing-offcut.gate-offcut .offcut-body').css({ 'width': calc.offcut_gate_panel.length * 0.10 });
        }

        // Remove offcut - On Gate
        if( parseInt(fd.mbn) <= this.get(slug, 'maxOnGate') ) {
            // $('.panel-offcut').remove();
        } else if( parseInt(fd.mbn) <= this.get(slug, 'minOnGate') ) {
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

        if(calc.fence_size.height) {
            $('.fencing-panel-item, .short-panel-item, .fencing-offcut .offcut-body').css({ 'height': calc.fence_size.height * 0.10 });
            $('.panel-post.opt-1').css({ 'height': (calc.fence_size.height * 0.10) + 25 });
            $('.panel-post.opt-2').css({ 'height': (calc.fence_size.height * 0.10) + 35 });
        }

        $('.ftm-measurement:not(:empty)').closest(FENCES.el.fencingTab).removeClass('incomplete-section');
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

            return;
        }

        mesurement = $(FENCES.el.measurementBoxNumber).val();
        mesurement = mesurement ? parseInt(mesurement).toLocaleString() + ' mm' : '';

        $(FENCES.el.fencingTabSelected).find('.ftm-measurement').html(mesurement);

        $(FENCES.el.fcTabTitle).html('SECTION ' + (tab + 1));

        subTitle = [mesurement, info['title']].filter(function(e) { return e }).join(' <i class="fa-solid fa-caret-right ms-3"></i> ');

        $(FENCES.el.fcTabSubtitle).html(` <i class="fa-solid fa-caret-right ms-3"></i> ${subTitle}`);

        FENCE.call('load_fencing_items');
    },

    //----------------------------------------------------------------------------------

    update_custom_fence_style_item: function() {
        var fd = getSelectedFenceData();

        var i = fd.slug,
            info = fd.data;;

        mesurement = $(FENCES.el.measurementBoxNumber).val();
        mesurement = mesurement ? mesurement + ' ' + FENCES.defaultValues.unit : '';

        $(FENCES.el.fencingTabSelected).find('.ftm-title').html('SECTION'); // info['name']
        $(FENCES.el.fencingTabSelected).find('.ftm-measurement').html(mesurement);

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

        FENCE.call('update_custom_fence_tab');
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

        $(FENCES.el.measurementBoxNumber).val(FENCES.defaultValues.measurement);

        FENCE.call('update_custom_fence_style_item');

        $(FENCES.el.jsBtnDeleteFence).show();

        $('.fc-tab-title, .fc-tab-subtitle').html('');
        $(FENCES.el.jsFcFormStep).hide();
        $(FENCES.el.fsiSelected).removeClass('fsi-selected');

        $(FENCES.el.fcFenceResetAll).hide();

        // Store section count
        localStorage.setItem('custom_fence-section', $(FENCES.el.fencingTab).length);

        HELPER.setSectionURLParam();
    },

    //----------------------------------------------------------------------------------

    update_custom_fence_gate: function() {
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

        var size = $('[name="width"]').val(),
            default_width = fd?.data?.settings?.gate?.size?.width,
            gateOnly = fd?.tabInfo[0]?.gateOnly;

        var settings = {
            'placement': placement,
            'gateOnly': $('[name="gate_only"]:visible').is(':checked'),
            'index': $(FENCES.el.fencingPanelGate).index(),
            'size': size || default_width,
            'unit': FENCES.defaultValues.unit
        }

        if( $('.fc-form-field:visible').length ) {

            settings.fields = $('.fc-form-field:visible').map(function() {

                var _this = $(this),
                    key = _this.attr('name'),
                    val = $.inArray(_this.attr('type'), ['radio', 'checkbox']) !== -1 ? ($('[name="use_std"]').is(':checked')) : _this.val(),
                    type = _this.attr('type'),
                    tag = _this.get(0).tagName.toLowerCase(),
                    obj = { key: key, val: val, tag: tag, type: type };

                return obj;

            }).get();
            
        } else {

            var gate_data = info.filter(function(item) {
                return item.control_key == 'gate';
            });

            settings.fields = gate_data[0]?.settings?.fields || [];            
        }
        
        var filtered_data = info.filter(function(item) {
            return item.control_key != modal_key;
        });

        if($(FENCES.el.fencingPanelGate).length) {

            filtered_data.push({
                id: i,
                control_key: modal_key,
                settings: settings
            });

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

        var panel_size = calc.gate.length,
            panel_unit = FENCES.defaultValues.unit,
            gate_size = calc.gate.width;

        if(action == 'add' || action == 'edit') {
            
            if(placement == -1 ) {

                var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-r"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_size)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                $('#panel-item-0, #panel-item-x').before(tpl);

            } else if(find_gate.length && placement >= 0) {

                // if panel placement doesn't exist
                if($('#panel-item-' + placement).length == 0) {

                    panel_gate_side = (panel_count <= 1) ? 'r' : 'l';
            
                    var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-' + panel_gate_side +'"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_size)
                        .replace(/{{panel_unit}}/gi, panel_unit);

                    $('#panel-item-0, #panel-item-x').after(tpl);

                } else {

                    var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-l"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_size)
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
                    .replace(/{{panel_size}}/gi, gate_size)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                var panelID = $('[data-cart-key="panel_options"].fencing-panel-item').attr('id');

                $('#'+panelID+', .fencing-panel-items .raked-panel-container').after(tpl);

                $(FENCES.el.btnGate).addClass('edit-gate').removeClass('add-gate').html('<span>Add</span> Gate');

            }

        }

        if( find_gate.length ) {                
           $(FENCES.el.btnGate).addClass('edit-gate').removeClass('add-gate').html('<span>Gate</span> Options');
        }

        // fence Height
        gateValue = '';

        if(calc.fence_size.height) {
            gateValue = calc.fence_size.height;
        }

        $(FENCES.el.fencingPanelGate).prepend('<span class="fc-gate-spacing fc-gate-left-spacing">20</span>')
            .append('<span class="fc-gate-spacing fc-gate-right-spacing">20</span>')
            .attr('data-cart-value', gateValue);


        // Is custom gate
        var gate_data = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        isCustomGate = gate_data[0]?.settings?.fields?.find(obj => obj['key'] === "use_std" && obj['val'] === false );

        if(isCustomGate) {
            $(FENCES.el.fencingPanelGate).css({ 'max-width': calc.gate.width * 0.1 });
        }

        if(action == 'add') {
            FENCE.call('calculateCustomGate');
        }
    },

    //----------------------------------------------------------------------------------

    move_the_gate: function(move) {
        var gate = $(FENCES.el.fencingPanelGate),
            panel_count = $('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel)').length;

        if(move == 'left') {

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

            var index = $('#panel-item-0, #panel-item-x').index() / 3;

            if(index != 2) {

                move_gate = gate.prop("outerHTML") + gate.prev().prop("outerHTML") + gate.prev().prev().prop("outerHTML");

                gate.prev().prev().remove();
                gate.prev().remove();

                $('#panel-item-0, #panel-item-x').before(move_gate);

                gate.remove();
            }

        } else if(move == 'last') {

            var closest_id = $(FENCES.el.panelItem).length - 1,
                last_id = $(FENCES.el.panelItem+':not(.fencing-raked-panel)').last().attr('data-id');

            var index = ($('#panel-item-' + last_id).index() / 3) + 1,
                gate_index = gate.index() / 3;

            if(index != gate_index) {

                move_gate = gate.next().next().prop("outerHTML") + gate.next().prop("outerHTML") + gate.prop("outerHTML");

                gate.next().next().remove();
                gate.next().remove();

                $('#panel-item-' + last_id).after(move_gate);

                 gate.remove();

            }

        } else if(move == 'delete') {

            var index = $('#panel-item-0, #panel-item-x').index() / 3;

            $(FENCES.el.btnGate).addClass('add-gate').removeClass('edit-gate').html('<span>Add</span> Gate');

            $(FENCES.el.fencingPanelGate).removeAttr('data-cart-value');
            FCModal.close();
            $('.fc-btn-active').removeClass('fc-btn-active');


            if(index == 2) {
                gate.next().next().remove();
                gate.next().remove();
            } else {
                gate.prev().prev().remove();
                gate.prev().remove();
            }

            gate.remove();

            $('.select-gate_only.fc-selected').trigger('click');

            setTimeout(function() {
                btnCalculate();
            });

        }

        FENCE.call('update_custom_fence_gate');

        $(FENCES.el.fencingDisplayResult).scrollCenter(".fencing-panel-gate", 100);
    },

    //----------------------------------------------------------------------------------

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
            var filtered_settings = settings?.filter(function(item) {
                return item.key == v;
            });

            if(filtered_settings) {

                if(filtered_settings[0]?.val != 'none') {

                    var dim = filtered_settings[0]?.val.split('x');

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

        $('.no-post-swivel-bracket span').after('<span class="sw sw-top">SW</span><span class="sw sw-bot">SW</span>');


        FENCE.call('load_post_options_all', custom_fence, info, 0, calc);

        FENCE.call('load_post_options_first_last_values', custom_fence, info, 0, calc);

        $('.fencing-display-result').css({ 'padding': '' });

        if($('.raked-panel .fencing-raked-panel').length && $('.fencing-display-result').css('margin-top') != '70px') {
            $('.fencing-display-result').css({ 'padding-top': '' });
        } else {
            $('.fencing-display-result').css({ 'margin-top': '' });
        }

        $('.raked-panel .fencing-panel-item').css({ 'width': 1200 * 0.10 });
    },

    //----------------------------------------------------------------------------------

    /**
     * This function will update either First or Last post after user selection
     * @param {array} custom_fence 
     */
    load_post_options_first_last_values: function(custom_fence, info, sectionId, calc) {
        var modal_key = $(FENCES.el.fencingContainer).attr('data-key');
        var side_post = '';

        //Get the settings of post_option from left_side and right_side
        var post_options_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === "left_side" || item.control_key === "right_side";
        });

        // Get default post options
        var post_options_default = info.settings.post_options.fields[0].options.filter(function(item) {
            return item.default == true;
        });


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
                        $('#pp-' + sectionId + ' .panel-post[data-key=' + activeSetting + '], #pp-' + sectionId + ' .fencing-panel-spacing-number')
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
        let panel_post = $('#pp-' + tab + ' .panel-post');
        let panel_spacing_number = $('#pp-' + tab + ' .fencing-panel-spacing-number');
        var modal_key = $(FENCES.el.fencingContainer).attr('data-key');
        var exclude_panel_posts = '';

        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab;

        var post_options_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === 'post_options';
        });

        var left_side_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === 'left_side';
        });

        var right_side_filtered_data = custom_fence.filter(function(item) {
            return item.control_key === 'right_side';
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

            if(!$('#fc-planning-form').length ||
                typeof post_options_setting !== "undefined" &&
                panel_post.attr('class').includes('opt-') == false) {

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

            panel_spacing_number.addClass(post_options_default?.slug);

        }
    },

    //----------------------------------------------------------------------------------

    set_cutom_fence_data: function() {
        var fd = getSelectedFenceData();

        var i = fd.slug,
            tab = fd.tab,
            custom_fence = fd.info,
            info = fd.data,
            tabInfo = fd.tabInfo;

        var modal_key = fd.modalKey,
            mbn = fd.mbn;

        var filtered_data_tabs = tabInfo.filter(function(item) {
            return item.tab != tab;
        });

        if(info == undefined) {

            $('.fc-tab-title, .fc-tab-subtitle').html('');
            $(FENCES.el.jsFcFormStep).hide();
            $(FENCES.el.fsiSelected).removeClass('fsi-selected');

            return;
        }

        filtered_data_tabs.push({
            tab: tab,
            style: i,
            fence: info.slug,
            mbn: mbn,
            gateOnly: tabInfo[0]?.gateOnly || false,
            fields: $('[data-action="change"] .form-control').serializeArray(),
            isCalculate: tabInfo[0]?.isCalculate || FENCES.defaultValues.measurement,
            calculateValue: tabInfo[0]?.calculateValue || FENCES.defaultValues.measurement
        });

        localStorage.setItem('custom_fence-' + tab, JSON.stringify(filtered_data_tabs));
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

        $('.custom-gate .fencing-qty-btn').addClass('disabled');

        $('.custom-gate button').attr('disabled', 'disabled')
            .removeClass('btn-dark')
            .addClass('btn-light disabled');
    },

    //----------------------------------------------------------------------------------

    calculateCustomGate: function() {
        FENCE.call('update_custom_fence_gate');
        FENCE.call('load_fencing_items');

        $(".fencing-display-result").scrollCenter(".fencing-panel-gate", 300);
    },

    //----------------------------------------------------------------------------------

    minus_posts: function(custom_fence) {
        _post = 0;

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
