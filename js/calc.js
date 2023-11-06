function get_field_multi_option_value(custom_fence, info, control_key, slug){

    var custom_fence_data = custom_fence.filter(function(item) {
        return item.control_key == control_key;
    });


    var custom_fence_data = custom_fence_data[0]?.settings.filter(function(item) {
        return item.key == slug;
    });

    return custom_fence_data?.[0] ? custom_fence_data[0] : custom_fence_data;
}

function get_field_options(custom_fence, info, control_key){

    var custom_fence_data = custom_fence.filter(function(item) {
        return item.control_key == control_key;
    });

    var info = JSON.parse(JSON.stringify(info));

    field_options = info['settings'][control_key]['fields'][0]['options'];

    var options_data = field_options.filter(function(item)  {
        return item.slug == custom_fence_data[0]?.settings[0]?.val;
    });    

    return options_data;
}

function get_field_multi_options(custom_fence, info, control_key){

    var custom_fence_data = custom_fence.filter(function(item) {
        return item.control_key == control_key;
    });

    field_options = info['settings'][control_key]['fields'];


    return field_options;
}

function get_field_by_slug(custom_fence, slug){


    var custom_fence_data = custom_fence.filter(function(item) {
        return item.slug == slug;
    });

    return custom_fence_data?.[0] ? custom_fence_data[0] : custom_fence_data;
}


function calculate_fences( data ) {
    // https://docs.google.com/spreadsheets/d/1Xxh9XsDy96cSQrPgR98rWZ1nwVORVxFL06OKQ8DhMEc/edit?pli=1#gid=0

    var i = data?.item != null ? data?.item : $('.fencing-style-item.fsi-selected').index(),
        tab = data?.tab != null ? data?.tab : $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        custom_fence_tab = localStorage.getItem('custom_fence-'+tab),
        custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [],

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
        left_raked_panel_height  = 0,
        left_raked_panel_width   = 0,
        right_raked_panel_height = 0,
        right_raked_panel_width  = 0,
        short_panel_count        = 0,
        short_panel_length       = 0;


    post_panel = 50;
    no_post = -50;


    C3 = parseInt($('.measurement-box-number').val());  // overall width
    C3 = C3 ? C3 :  custom_fence_tab[0].calculateValue;

    C4  = $('.left-panel-post.no-post').length ? no_post : 0;    // edit left side


    panel_options_data = get_field_options(custom_fence, info, 'panel_options');
    C5  = panel_options_data[0]?.size.width;   // panel options

    if( C5 == undefined ) {
        panel_options_data = get_field_by_slug(info.settings.panel_options.fields[0].options, 'even');
        C5 = panel_options_data.size?.width;        
    }

    C6  = 0;      // post options
    
    C7  = $('.right-panel-post.no-post').length ? no_post : 0;      // edit right side


    var gate_data = custom_fence.filter(function(item) {
        return item.control_key == 'gate';
    });

    // add gate
    if( gate_data.length ) {
        C8  = info.settings.gate.size.width; 
    }

    // raked panel left
    step_up_panels = get_field_multi_options(custom_fence, info, 'left_side');
    step_up_panels = get_field_by_slug(step_up_panels, 'left_raked');
    step_up_panels_data = get_field_multi_option_value(custom_fence, info, 'left_side', 'left_raked');
    step_up_panels = get_field_by_slug(step_up_panels.options, step_up_panels_data?.val);

    if( step_up_panels.length != 0 ) {
        C9  = step_up_panels?.size?.width;
    }


    left_raked_panel_height  = step_up_panels?.size?.height;
    left_raked_panel_width   = isNaN(C9-post_panel) ? 0 : C9-post_panel;      


    // raked panel right 
    step_up_panels = get_field_multi_options(custom_fence, info, 'right_side');
    step_up_panels = get_field_by_slug(step_up_panels, 'right_raked');
    step_up_panels_data = get_field_multi_option_value(custom_fence, info, 'right_side', 'right_raked');
    step_up_panels = get_field_by_slug(step_up_panels.options, step_up_panels_data?.val);

    if( step_up_panels.length != 0 ) {
        C10 = step_up_panels?.size?.width;   
    }

    right_raked_panel_height = step_up_panels?.size?.height;
    right_raked_panel_width  = isNaN(C10-post_panel) ? 0 : C10-post_panel; 


    // C13 = 50*(C24+C22+C23);
    C14 = C3-C8-C9-C10-post_panel;



    C15 = C3+C7+C4;
    C16 = Math.ceil(C14/C5);
    C17 = C16;
    C18 = Math.floor( C14/C5 );

    C21 = C8 > 0 ? 1 : 0;
   /// C22 = D22-E22;
   /// C23 = D23-E23;
   /// C24 = (C19=0 ? C18+1 : C16+1 )+C21+D24+E24;

    D16 = C14/C5;
    D17 = Math.round(C14/C17);
    D18 = C5;



    D19 = (D16-C18)*D18; // E19

    D21 = C8;
    D22 = C9 > 0 ? 1 : 0;
    D23 = C10 > 0 ? 1 : 0;
    D24 =  D22<1 ? (C4<0 ? -1 : 0) : 0;
    
    E17 = D17-post_panel;
    E18 = D18-post_panel;
    E19 = D19<post_panel ? 0 : D19-post_panel; // E19
    E21 = D21-post_panel;
    E22 = C9>0 ? (C4<0 ? 1 : 0) : 0;
    E23 = C10>0 ? (C7<0 ? 1 : 0) : 0;
    E24 = D23<1 ? (C7<0 ? -1 : 0) : 0;

    C19 =  E19 < 1 ? 0 : 1;

    // C20 = C19;
    C20 = (panel_options_data[0]?.slug == 'even' || panel_options_data[0]?.slug == undefined) ? C17 : C19;

    D20 = D19<post_panel ? 0 : E18-E19;

    D20 = panel_options_data[0]?.slug == 'even' ? E18-E17 : (D19<50 ? 0 : E18-E19);

    // Outputs
    full_panel_count  = isNaN(C18) ? 0 : C18;
    full_panel_length = isNaN(E18) ? 0 : E18;

    even_panel_count  = isNaN(C17) ? 0 : C17;
    even_panel_length = isNaN(E17) ? 0 : E17;

    if( panel_options_data[0]?.slug == 'even' || panel_options_data[0]?.slug == undefined ) {

        long_panel_count  = even_panel_count;
        long_panel_length = Math.round(even_panel_length);  

    } else {

        long_panel_count  = full_panel_count;
        long_panel_length = Math.round(full_panel_length);          

        short_panel_count  = isNaN(C19) ? 0 : C19;
        short_panel_length = isNaN(E19) ? 0 : Math.round(E19);
    }


    offcut_panel_count = C20;
    offcut_panel_length = isNaN(D20) ? 0 : Math.round(D20);

    gate_count = isNaN(C21) ? 0 : C21;
    gate_width = isNaN(D21) ? 0 : (D21 ? D21-50-20-20 : 0);

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
    
    if( Array.isArray(panel_options_data) ){
        panel_options_data = panel_options_data[0];
    }

    data = {
        'full_panel' : {
            'count' : full_panel_count, 
            'length' : full_panel_length            
        },
        'even_panel' : {
            'count' : even_panel_count, 
            'length' : even_panel_length            
        },
        'long_panel' : {
            'count' : long_panel_count, 
            'length' : long_panel_length            
        },
        'short_panel' : {
            'count' : short_panel_count, 
            'length' : short_panel_length            
        },
        'offcut_panel' : {
            'count' : offcut_panel_count, 
            'length' : offcut_panel_length
        },
        'gate' : {
            'count' : gate_count,
            'length' : gate_width
        },
        'left_raked' : {
            'count' : gate_count,
            'height' : left_raked_panel_height,
            'width' : left_raked_panel_width,
        },
        'right_raked' : {
            'count' : gate_count,
            'height' : right_raked_panel_height,
            'width' : right_raked_panel_width,
        },
        'selected_values': {
            'panel_option': panel_options_data.slug
        }
    }

    return data;
}
