function get_field_multi_option_value(custom_fence, info, control_key, slug) {

    var custom_fence_data = custom_fence.filter(function(item) {
        return item.control_key == control_key;
    });

    var custom_fence_data = custom_fence_data[0]?.settings.filter(function(item) {
        return item.key == slug;
    });

    return custom_fence_data?. [0] ? custom_fence_data[0] : custom_fence_data;
}

//----------------------------------------------------------------------------------

function get_field_options(custom_fence, info, control_key) {

    var custom_fence_data = custom_fence.filter(function(item) {
        return item.control_key == control_key;
    });

    var info = JSON.parse(JSON.stringify(info));

    field_options = info['settings'][control_key]['fields'][0]['options'];

    var options_data = field_options.filter(function(item) {
        return item.slug == custom_fence_data[0]?.settings[0]?.val;
    });

    return options_data;
}

//----------------------------------------------------------------------------------

function get_field_multi_options(custom_fence, info, control_key) {

    var custom_fence_data = custom_fence.filter(function(item) {
        return item.control_key == control_key;
    });

    field_options = info['settings'][control_key]['fields'];


    return field_options;
}

//----------------------------------------------------------------------------------

function get_field_by_slug(custom_fence, slug) {


    var custom_fence_data = custom_fence.filter(function(item) {

        if(item.slug.includes('+')) {
            return item.slug.includes(slug);
        }

        return item.slug == slug;
    });

    return custom_fence_data?. [0] ? custom_fence_data[0] : custom_fence_data;
}

//----------------------------------------------------------------------------------

function calculate_fences(data) {
    // https://docs.google.com/spreadsheets/d/1Xxh9XsDy96cSQrPgR98rWZ1nwVORVxFL06OKQ8DhMEc/edit?pli=1#gid=0

    var i = data?.item != null ? data?.item : $('.fencing-style-item.fsi-selected').attr('data-slug'),
        tab = data?.tab != null ? data?.tab : $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-' + tab + '-' + i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
        custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

    if (!i) return;

    info = fc_data[i];

    // Inputs
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

    var C3 = C4 = C5 = C6 = C7 = C8 = C9 = C10 = 0,
        left_raked_panel_height = 0,
        left_raked_panel_width = 0,
        right_raked_panel_height = 0,
        right_raked_panel_width = 0,
        short_panel_count = 0,
        short_panel_length = 0,
        _short_panel_length = 0,
        offcut_gate_panel_length = 0,
        offcut_gate_panel_count = 0;

    gate_post_gaps = FENCE.get(i, 'gate_post_gaps');
    post_panel = FENCE.get(i, 'post');

    no_post = -post_panel;
    
    fence_height = '';
    if (info?.form) {
        fence_height = parseInt($('[name="fence_height"]').val());

        var filtered_fence_height = custom_fence_tab[0]?.fields?.filter(function(item) {
            return item.name == 'fence_height';
        });
        filtered_fence_height_value = '';
        if (filtered_fence_height) {
            filtered_fence_height_value = filtered_fence_height[0]?.value;
        }

        fence_height = fence_height ? fence_height : filtered_fence_height_value;
    }

    C3 = parseInt($('.measurement-box-number').val()); // overall width
    C3 = C3 ? C3 : custom_fence_tab[0]?.calculateValue;


    panel_options_data = get_field_options(custom_fence, info, 'panel_options');


    if (Array.isArray(panel_options_data)) {
        panel_options_data = panel_options_data[0];
    }

    C5 = panel_options_data?.size.width; // panel options

    if (C5 == undefined) {

        panel_options_data = info.settings.panel_options.fields[0].options.filter(function(item) {
            return item.default;
        });

        if (Array.isArray(panel_options_data)) {
            panel_options_data = panel_options_data[0];
        }

        C5 = panel_options_data.size?.width;
    }

    var default_panel_width = panel_options_data?.size?.default;

    if (panel_options_data.size?.width_based_height) {
        /*
            1000H Panels = 1733W
            1200H Panels = 2205W
            1800H Panels = 1969W
        */
        panel_opts = panel_options_data.size.width_based_height;
        C5 = panel_opts[fence_height];
    }

    C6 = 0; // post options

    var gate_data = custom_fence.filter(function(item) {
        return item.control_key == 'gate';
    });

    // add gate
    if (gate_data.length) {
        if (gate_data[0]?.settings.size) {
            C8 = parseInt(gate_data[0]?.settings.size) + gate_post_gaps;
            offcut_gate_panel_length = (C5 - post_panel) - C8 + gate_post_gaps;

            offcut_gate_panel_count = 0;

            var isCustomGate = gate_data[0]?.settings?.fields?.filter(function(item) {
                return item.key == 'use_std' && item.val == false;
            });

            if (isCustomGate[0]) {
                offcut_gate_panel_count = 1;
            }

        } else {
            C8 = parseInt(info.settings.gate.size.width) + post_panel + 20 + 20;
        }
    }



    // raked panel left
    step_up_panels = get_field_multi_options(custom_fence, info, 'left_side');
    step_up_panels = get_field_by_slug(step_up_panels, 'left_raked');
    step_up_panels_data = get_field_multi_option_value(custom_fence, info, 'left_side', 'left_raked');
    step_up_panels = get_field_by_slug(step_up_panels.options, step_up_panels_data?.val);

    if (step_up_panels.length != 0) {
        C9 = step_up_panels?.size?.width;
    }


    left_raked_panel_height = step_up_panels?.size?.height;
    left_raked_panel_width = isNaN(C9 - post_panel) ? 0 : C9 - post_panel;


    // raked panel right 
    step_up_panels = get_field_multi_options(custom_fence, info, 'right_side');
    step_up_panels = get_field_by_slug(step_up_panels, 'right_raked');
    step_up_panels_data = get_field_multi_option_value(custom_fence, info, 'right_side', 'right_raked');
    step_up_panels = get_field_by_slug(step_up_panels.options, step_up_panels_data?.val);

    if (step_up_panels.length != 0) {
        C10 = step_up_panels?.size?.width;
    }

    right_raked_panel_height = step_up_panels?.size?.height;
    right_raked_panel_width = isNaN(C10 - post_panel) ? 0 : C10 - post_panel;

    // C13 = 50*(C24+C22+C23);
    C14 = C3 - C8 - C9 - C10 - post_panel;


    C15 = C3 + C7 + C4;
    C16 = Math.ceil(C14 / C5);
    C17 = C16;
    C18 = Math.floor(C14 / C5);

    C21 = C8 > 0 ? 1 : 0;
    /// C22 = D22-E22;
    /// C23 = D23-E23;
    /// C24 = (C19=0 ? C18+1 : C16+1 )+C21+D24+E24;

    D16 = C14 / C5;
    D17 = Math.round(C14 / C17);
    D18 = C5;



    D19 = (D16 - C18) * D18; // E19

    D21 = C8;
    D22 = C9 > 0 ? 1 : 0;
    D23 = C10 > 0 ? 1 : 0;
    D24 = D22 < 1 ? (C4 < 0 ? -1 : 0) : 0;

    E17 = D17 - post_panel;
    E18 = D18 - post_panel;
    E19 = D19 < post_panel ? 0 : D19 - post_panel; // E19
    E21 = D21 - post_panel;
    E22 = C9 > 0 ? (C4 < 0 ? 1 : 0) : 0;
    E23 = C10 > 0 ? (C7 < 0 ? 1 : 0) : 0;
    E24 = D23 < 1 ? (C7 < 0 ? -1 : 0) : 0;

    C19 = E19 < 1 ? 0 : 1;

    // C20 = C19;
    C20 = (panel_options_data?.slug.includes('even') || panel_options_data?.slug == undefined) ? C17 : C19;

    D20 = D19 < post_panel ? 0 : E18 - E19;

    D20 = panel_options_data?.slug.includes('even') ? E18 - E17 : (D19 < post_panel ? 0 : E18 - E19);



    // Outputs
    full_panel_count = isNaN(C18) ? 0 : C18;
    full_panel_length = isNaN(E18) ? 0 : E18;

    even_panel_count = isNaN(C17) ? 0 : C17;
    even_panel_length = isNaN(E17) ? 0 : E17;

    if (panel_options_data?.slug.includes('even') || panel_options_data?.slug == undefined) {

        long_panel_count = even_panel_count;
        long_panel_length = Math.round(even_panel_length);
  
    } else {

        long_panel_count = full_panel_count;
        long_panel_length = Math.round(full_panel_length);

        short_panel_count = isNaN(C19) ? 0 : C19;
        short_panel_length = isNaN(E19) ? 0 : Math.round(E19);
        _short_panel_length = short_panel_length;
    }


    offcut_panel_count = C20;
    offcut_panel_length = isNaN(D20) ? 0 : Math.round(D20);
    _offcut_panel_length = offcut_panel_length;

    gate_count = isNaN(C21) ? 0 : C21;
    gate_length = isNaN(D21) ? 0 : parseInt(D21) - gate_post_gaps;
    gate_width = parseInt(gate_length);


    /*    
        console.log('C', C3, C14, C5, C15, C16, C17, C19, C18, C21);
        console.log('D', D16, D17, D18, Math.round(D19), D21, D22, D23, D24);
        console.log('E', E17, E18, Math.round(E19), E21, E22, E23, E24);

        console.log('========================================================');

        console.log('full_panel', full_panel_count, full_panel_length);
        console.log('even_panel', even_panel_count, even_panel_length);
        console.log('short_panel', short_panel_count, short_panel_length);
        console.log('offcut_panel', offcut_panel_count, offcut_panel_length);
        console.log('gate', gate_count, gate_width);
    */

    var _post = FENCE.minus_posts(custom_fence);

    if( _post ) {
        divided_post = _post/(long_panel_count + short_panel_count);


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
            short_panel_length = Math.round(default_panel_width);                  
        } else {
            if(short_panel_length > 0) {
                divided_post = _post/short_panel_count;
                short_panel_length = Math.round(_short_panel_length + divided_post);                             
                offcut_panel_length = HELPER.isNaNtoZero(Math.round(_offcut_panel_length - _post));
            }
        }

        if(short_panel_length == 0) {
            short_panel_count = 0;
        }


    }

    data = {
        'fence_size': {
            'width': '',
            'height': HELPER.isNaNtoZero(fence_height),
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
            'length': long_panel_length
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
        'left_raked': {
            'height': HELPER.isNaNtoZero(left_raked_panel_height),
            'width': HELPER.isNaNtoZero(left_raked_panel_width),
        },
        'right_raked': {
            'height': HELPER.isNaNtoZero(right_raked_panel_height),
            'width': HELPER.isNaNtoZero(right_raked_panel_width),
        },
        'selected_values': {
            'panel_option': panel_options_data?.slug
        }
    }

    return data;
}

//----------------------------------------------------------------------------------