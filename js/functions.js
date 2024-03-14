function load_fencing_items() {

    var fd = getSelectedFenceData();

    var i            = fd.slug,
        tab          = fd.tab,
        custom_fence = fd.info,
        info         = fd.data;

    $(FENCES.el.fencingPanelContainer).html('').attr('data-type', info?.slug);

    var calc = calculate_fences();

    if( !calc ) {
        return;
    }

    var center_point = 50;

    for (let i = 0; i < calc.long_panel.count; i++) {
        
        mesurement = $(FENCES.el.measurementBoxNumber).val();

        var panel_number = i,
            panel_size   = calc.long_panel.length,
            panel_unit   = FENCES.defaultValues.unit,
            data_key     = "post_options";

        var panel_option_value = calc.selected_values.panel_option;

        if( panel_option_value.indexOf('full') !== -1 ){
            panel_option_value = panel_option_value.split('_')[0];
        } 

        // Fence height
        if( calc.fence_size.height ) {
            panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
        }

        var tpl = $('script[data-type="panel_item-'+info.panel_group+'"]').text()
                                                     .replace(/{{data_key}}/gi, center_point)
                                                     .replace(/{{center_point}}/gi, center_point)
                                                     .replace(/{{panel_value}}/gi, panel_option_value)
                                                     .replace(/{{panel_size}}/gi, panel_size+'W')
                                                     .replace(/{{panel_unit}}/gi, '<br>PANEL')
                                                     .replace(/{{panel_number}}/gi, panel_number);    

        $(FENCES.el.fencingPanelContainer).append(tpl);

        $(FENCES.el.fencingPanelItem).css({'width':panel_size*0.10});
    }  

    var tpl = $('script[data-type="panel_spacing-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point);

    $(FENCES.el.fencingPanelContainer).append(tpl);

    if( calc.short_panel.count ) {

        for (let i = 0; i < 1; i++) {

            var panel_number = i,
                panel_size = calc.short_panel.length,
                panel_unit = FENCES.defaultValues.unit;
                panel_option_value = calc.selected_values.panel_option;

            if( panel_option_value.indexOf('full') !== -1 ){
                panel_option_value = panel_option_value.split('_')[0];
            } 

            // Fence height
            if( calc.fence_size.height ) {
                panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
            }

            var tpl = $('script[data-type="short_panel_item-'+info.panel_group+'"]').text()
                                                         .replace(/{{center_point}}/gi, center_point)
                                                         .replace(/{{panel_size}}/gi, panel_size+'W')
                                                         .replace(/{{panel_value}}/gi, panel_option_value)
                                                         .replace(/{{panel_unit}}/gi, '<br>PANEL')
                                                         .replace(/{{panel_number}}/gi, panel_number);    
        
            $(FENCES.el.fencingPanelContainer).append(tpl);

            $(FENCES.el.shortPanelItem).attr('data-id', calc.long_panel.count+1)
                                  .attr('id', 'panel-item-'+(calc.long_panel.count+1))
                                  .css({'width':panel_size*0.10});

        }  

        var tpl = $('script[data-type="panel_spacing-'+info.panel_group+'"]').text()
                                                         .replace(/{{center_point}}/gi, center_point);

        $(FENCES.el.fencingPanelContainer).append(tpl);

    }

    update_gate('edit');

    // $('.fpsn-b:not(:first-child):not(:last-child)').remove();

    $(FENCES.el.fencingPanelContainer).prepend('<div data-cart-key="raked-panel" class="left_raked-panel raked-panel"></div>')
                                 .append('<div data-cart-key="raked-panel" class="right_raked-panel raked-panel"></div>');

    update_raked_panels(['left_raked', 'right_raked']);

    // Panel off-cut
    if( calc.offcut_panel.count && calc.offcut_panel.length ) {
        var tpl = $('script[data-type="offcut"]').text()
                                                 .replace(/{{slug}}/gi, 'panel-offcut') 
                                                 .replace(/{{name}}/gi, 'Panel')
                                                 .replace(/{{count}}/gi, calc.offcut_panel.count)  
                                                 .replace(/{{group}}/gi, info.panel_group) 
                                                 .replace(/{{width}}/gi, calc.offcut_panel.length);     

        $(FENCES.el.fencingPanelContainer).append(tpl);    
        $('.fencing-offcut.panel-offcut').css({'max-width':calc.offcut_panel.length*0.10});
    }

    // Custom gate off-cut
    if( calc.offcut_gate_panel.count && calc.offcut_gate_panel.length ) {
        var tpl = $('script[data-type="offcut"]').text()
                                                 .replace(/{{slug}}/gi, 'gate-offcut') 
                                                 .replace(/{{name}}/gi, 'Gate')
                                                 .replace(/{{count}}/gi, calc.offcut_gate_panel.count)  
                                                 .replace(/{{group}}/gi, info.panel_group) 
                                                 .replace(/{{width}}/gi, calc.offcut_gate_panel.length);     

        $(FENCES.el.fencingPanelContainer).append(tpl);    
        $('.fencing-offcut.gate-offcut').css({'max-width':calc.offcut_gate_panel.length*0.10});
    }

    // Clear tooltip like error massage
    $(FENCES.el.fcInputMsg).removeClass('fcim-show').html('');


    setTimeout(function(){
        $(FENCES.el.fcFenceResetAll).hide();
        if( $(FENCES.el.fsiSelected).length ) {
            $(FENCES.el.fcFenceResetAll).show();
        }        
    });

    if( calc.fence_size.height ) {
        $('.fencing-panel-item, .short-panel-item, .fencing-offcut').css({'height':calc.fence_size.height*0.10});     
        $('.panel-post.opt-1').css({'height': (calc.fence_size.height*0.10)+25 });        
        $('.panel-post.opt-2').css({'height': (calc.fence_size.height*0.10)+35 });        
    }

    $('.ftm-measurement:not(:empty)').closest(FENCES.el.fencingTab).removeClass('incomplete-section');

}

function updateLastFencingPost(){
    var elements = document.querySelectorAll('.fencing-panel-container [data-key="post_options"]');
    if (elements.length > 0) {
      var lastElement = elements[elements.length - 1];
      lastElement.setAttribute('data_key', 'left_side');
    }
}

function updateFirstFencingPost(){
    var elements = document.querySelectorAll('.fencing-panel-container [data-key="post_options"]');
    if ( elements.length > 0 ) {
      var lastElement = elements[0];
      lastElement.setAttribute('data_key', 'right_side');
    }
}

function update_raked_panels(side) {

    var fd = getSelectedFenceData();
    
    var i            = fd.slug,
        tab          = fd.tab,
        custom_fence = fd.info,
        info         = fd.data;

    var filtered_data = custom_fence.filter(function(item) {
        return item.control_key == 'add_step_up_panels';
    });

    var settings = filtered_data[0]?.settings;

    var calc = calculate_fences();

    $(side).each(function(k, v){

        // Side
        var side_part = v.replace('_raked', ''),
            has_post = 'yes-post',
            center_point = 50;

        var filtered_side_data = custom_fence.filter(function(item) {
            return item.control_key == side_part+'_side';
        });

        if( filtered_side_data ) {

            if( filtered_side_data.length ) {
                var has_post = $(filtered_side_data[0].settings).map(function(k, item) {
                    if( item.key == side_part+'_option' ) {
                        return item.val;
                    }
                }).get().join("");
            }

            if( has_post != 'yes-post' && has_post ) {
                var has_post = 'no-post '+side_part+'-panel-post '+has_post;
            }
        }



        // Raked
        var filtered_settings = settings?.filter(function(item) {
            return item.key == v;
        });

        if( filtered_settings ) {

            if( filtered_settings[0]?.val != 'none' ) {

                var dim = filtered_settings[0]?.val.split('x');

                if( side_part == 'left') {
                    panel_w = calc.left_raked.width;
                } else {
                    panel_w = calc.right_raked.width;
                } 

                panel_h      = '';
                panel_height = ''; 

                if( dim ) {
                    panel_h      = dim[0];
                    panel_height = dim[1];                    
                }

                var tpl = $('script[data-type="'+v+'-panel-'+info.panel_group+'"]').text()
                                                                .replace(/{{center_point}}/gi, center_point)
                                                                .replace(/{{panel_size}}/gi, panel_h)
                                                                .replace(/{{panel_unit}}/gi,  panel_w)                                                 
                                                                .replace(/{{panel_height}}/gi, panel_height)
                                                                .replace(/{{post}}/gi, has_post);   

                if( panel_h ){
                    $('.'+v+'-panel').html(tpl);    
                }
                
            }

        }

        if( side_part == 'left' ) {
            $('.panel-post:not(.post-left):not(.post-right)').first()
                                                             .addClass('post-left panel-'+has_post)
                                                             .attr('data-key',"left_side")
                                                             .attr('post-side',"post_left");

            $('.fencing-panel-spacing-number').first().addClass(has_post);
        }

        if( side_part == 'right' ) {

            $('.panel-post:not(.post-left):not(.post-right)').last()
                                                             .addClass('post-right panel-'+has_post)
                                                             .attr('data-key',"right_side")
                                                             .attr('post-side',"post_right");

            $('.fencing-panel-spacing-number').last().addClass(has_post);
        }

    });

    // Left Panel post
    var left_panel_post = $('.left-panel-post.no-post span').text()
                                                            .replace('-', '')
                                                            .replace('(', '')
                                                            .replace(')', '');

    $('.left-panel-post.no-post span').text('(-'+left_panel_post+')');

    // Right Panel Post
    var right_panel_post = $('.right-panel-post.no-post span').text()
                                                              .replace('-', '')
                                                              .replace('(', '')
                                                              .replace(')', '');

    $('.right-panel-post.no-post span').text('(-'+right_panel_post+')');

    $('.no-post-swivel-bracket span').after('<span class="sw sw-top">SW</span><span class="sw sw-bot">SW</span>');    


    load_post_options_all(custom_fence, info, 0, calc);

    load_post_options_first_last_values(custom_fence, info, 0);

    $('.fencing-display-result').css({'padding': ''});

    if( $('.raked-panel .fencing-raked-panel').length && $('.fencing-display-result').css('margin-top') != '70px' ) {   
        $('.fencing-display-result').css({'padding-top': ''});
    } else {
        $('.fencing-display-result').css({'margin-top': ''});        
    }

    $('.raked-panel .fencing-panel-item').css({'width':1200*0.10});


}

/**
 * This function will update either First or Last post after user selection
 * @param {array} custom_fence 
 */
function load_post_options_first_last_values(custom_fence, info, sectionId) {

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

    if( post_options_filtered_data.length ) {

        //iterate both left and right side and get the values of post_options
        for( let i = 0; i < post_options_filtered_data.length; i++ ){
            let activeSetting = post_options_filtered_data[i].control_key;
            let settings = post_options_filtered_data[i].settings;
    
            for( let idx = 0; idx < settings.length; idx++ ){
                let key   = settings[idx].key;
                let value = settings[idx].val ? settings[idx].val : post_options_default[0].slug;

                if( key === "post_option" && modal_key != 'post_options' ){
            
                    //We added data-key attribute on the first and last panel post both will have either left_side or right_side value
                    //Find the element that matches the condition below and add the class
                    $('#pp-'+sectionId+' .panel-post[data-key='+activeSetting+'], #pp-'+sectionId+' .fencing-panel-spacing-number')
                        .addClass(value)
                        .attr('data-cart-value', value);
                }
            }

        }

        var side_post = post_options_filtered_data[0].control_key;
    }


    // Set default option on left side
    if( side_post != 'left_side' ) {
        $('#pp-'+sectionId+' .panel-post.post-left').addClass(post_options_default[0].slug);   
    } 

    // Set default option on right side
    if( side_post != 'right_side' ) {
        $('#pp-'+sectionId+' .panel-post.post-right').addClass(post_options_default[0].slug);
    }

}

/**
 * This function will update all posts except for the first and last post 
 * @param {array} custom_fence 
 * @param {obj} info 
 */
function load_post_options_all(custom_fence, info, tab, calc) {


    let panel_post           = $('#pp-'+tab+' .panel-post');
    let panel_spacing_number = $('#pp-'+tab+' .fencing-panel-spacing-number');
    var modal_key            = $(FENCES.el.fencingContainer).attr('data-key');
    var exclude_panel_posts  = '';
    
/*    var fd = getSelectedFenceData();
    
    var i   = fd.slug;*/

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

    if( modal_key != 'post_options' && left_side_filtered_data.length) {
        var left_planel_class  = ".post-left";
    }

    if( modal_key != 'post_options' && right_side_filtered_data.length ) {
        var right_planel_class  = ".post-right";
    }

    if( post_options_filtered_data.length ) {
        //Get the value of Post Option
        var post_options_setting = post_options_filtered_data[0].settings.find(function(item) {
            return item.key === "post_option";
        });
    
        if( !$('#fc-planning-form').length || 
            typeof post_options_setting !== "undefined" && 
            panel_post.attr('class').includes('opt-') == false ) {

            // Fence height
            postValue = post_options_setting.val;

            if( calc.fence_size.height ) {
                postValue = postValue.concat("+", calc.fence_size.height);
            }

            panel_post.not(left_planel_class)
                      .not(right_planel_class)
                      .addClass(post_options_setting.val)
                      .attr('data-cart-value', postValue);

            panel_spacing_number.addClass(post_options_setting.val);

        } 

    } else {

        // Get default post options
        var post_options_default = info.settings.post_options.fields[0].options.find(function(item) {
            return item.default == true;
        });

        // Fence height
        postValue = post_options_default.slug;

        if( calc.fence_size.height ) {
            postValue = postValue.concat("+", calc.fence_size.height);
        }

        panel_post.not(left_planel_class)   
                  .not(right_planel_class)
                  .attr('data-cart-value', postValue)
                  .addClass(post_options_default.slug);

        panel_spacing_number
                  .addClass(post_options_default.slug);

    }

    // Overwrite side panel posts
    if( modal_key == 'post_options' ) {
        $(custom_fence).each(function(k, v){
            if( v.control_key == 'left_side' || v.control_key == 'right_side' ) {

                $(custom_fence[k].settings).each(function(lok, lov) {

                    if( lov.key == 'post_option' ) {
                       custom_fence[k].settings[lok].val = post_options_filtered_data[0]?.settings[0]?.val;
                        localStorage.setItem(`custom_fence-${tab}-${i}`, JSON.stringify(custom_fence));
                    }
                });
            }
        });
    }

}

//----------------------------------------------------------------

function update_gate(action) {

    var fd = getSelectedFenceData();
    
    var i            = fd.slug,
        tab          = fd.tab,
        custom_fence = fd.info,
        info         = fd.data;

    var find_gate = custom_fence.filter(function(item) {
        return item.control_key == 'gate';
    });

    if( find_gate.length ) {
        placement =  find_gate[0]?.settings?.placement;
    } else {

        placement = 0;

    }

    var center_point = 50,        
        mesurement = $(FENCES.el.measurementBoxNumber).val();

    var calc = calculate_fences();

    var panel_size = calc.gate.length,
        panel_unit = FENCES.defaultValues.unit,
        gate_size  = calc.gate.width;


    if( action == 'add' || action == 'edit' ) {

        if( placement == -1 ) {

           var tpl = $('script[data-type="panel_gate-'+info.panel_group+'-r"]').text()
                                                         .replace(/{{center_point}}/gi, center_point)
                                                         .replace(/{{panel_size}}/gi, gate_size)
                                                         .replace(/{{panel_unit}}/gi, panel_unit);

            $('#panel-item-0').before(tpl);

            $(FENCES.el.btnGate).html('Edit Gate');

        } 


        if( find_gate.length && placement >= 0  || action == 'add' && placement == 0 ) {

            var tpl = $('script[data-type="panel_gate-'+info.panel_group+'-l"]').text()
                                                         .replace(/{{center_point}}/gi, center_point)
                                                         .replace(/{{panel_size}}/gi, gate_size)
                                                         .replace(/{{panel_unit}}/gi, panel_unit);  

            $('#panel-item-'+placement).after(tpl);    

            $(FENCES.el.btnGate).html('Edit Gate');
        }                                             

    }

    // fence Height
    gateValue = '';
    if( calc.fence_size.height ) {
        gateValue = calc.fence_size.height;
    }



    $(FENCES.el.fencingPanelGate).prepend('<span class="fc-gate-spacing fc-gate-left-spacing">20</span>')
                                 .append('<span class="fc-gate-spacing fc-gate-right-spacing">20</span>')
                                 .attr('data-cart-value', gateValue);

    if( calc.fence_size.height ) {
        $(FENCES.el.fencingPanelGate).css({'max-width': calc.gate.width * 0.1});
    }

}

$.fn.swapWith = function(to) {
    return this.each(function() {
        var copy_to = $(to).clone(true);
        var copy_from = $(this).clone(true);
        $(to).replaceWith(copy_from);
        $(this).replaceWith(copy_to);
    });
};

//----------------------------------------------------------------

function update_custom_fence_style_item() {

    var fd = getSelectedFenceData();
    
    var i    = fd.slug,
        info = fd.data;;

    mesurement = $(FENCES.el.measurementBoxNumber).val();
    mesurement = mesurement ? mesurement + ' ' + FENCES.defaultValues.unit : '';

    $(FENCES.el.fencingTabSelected).find('.ftm-title').html( 'SECTION' ); // info['name']
    $(FENCES.el.fencingTabSelected).find('.ftm-measurement').html( mesurement );

    $(FENCES.el.fencingPanelControls).html('');

    $.each(info?.settings, function(k, v){

        /**
         * @TODO - re-check on how to disable from the settings
         */
        if( v.disabled ){
            return;
        }

        if( v.length !== 0 ) {

          var action = '';
          let label = v.label;

          if( v.action, v.action.includes('edit')  ) {
              var action = 'Edit ';
          }

          if( v.action, v.action.includes('add')  ) {
              var action = 'Add ';            
          }

          if( label ){
            label = label.split(' ');

            if(Array.isArray(label)){
                label[0] = `<span>${label[0]}</span>`;
            }

            label = label.join(" ");
          }

          $('<button>').html(action+label).attr({
            'type'        : 'button',
            'id'          : 'btn-'+k,
            'data-key'    : k,
            'data-target' : "#fc-control-modal",
            'class'       : 'btn-fc btn-fc-outline-default fencing-btn-modal fc-mb-1'
          }).appendTo(FENCES.el.fencingPanelControls);

          setTimeout(function(){
            $(FENCES.el.fencingPanelControls + " > div").remove();
          }, 100);

        }
   
    });

    $(FENCES.el.btnGate).before('<div></div>');

    update_custom_fence_tab();
}

//----------------------------------------------------------------

function update_color_options() {

    colorData = color_data = [];

    $(FENCES.el.fcColorOptions).each(function(k, v){
        var color = $(this).find('.fc-selected').attr('data-slug');

        if( color ) {
            var data = {
                fence: $(v).attr('data-slug'),
                color: color
            }

            color_data.push(data);            
        }

    });

    var colorData = { color: color_data }

    updateOrCreateObjectInLocalStorage('project-plans', colorData);

}


/**
 * 
 * Prepare to load values from local storage
 * @param {json} filtered_data 
 * 
 */
function set_field_value(filtered_data) {

    if( filtered_data ) {
        $(filtered_data).each(function(i, item){            
            $(item.settings).each(function(i, item){
                
                $(item.fields).each(function(i, item){

                    get_field_value(item.tag, item.key, item.val);
                });

                get_field_value(item.tag, item.key, item.val);
            });    
        });
    }

}

//----------------------------------------------------------------

/**
 * Load values from local storage
 * @param {string} tag 
 * @param {string} key 
 * @param {string} val 
 */
function get_field_value(tag, key, val) {

    if( ! val ) return; 

    if( tag == 'input' ) {

        $('[name='+key+']').val(val);
        $('[name='+key+']').closest('.fencing-form-group').find('.fir-info span').text(val);
        $('[name='+key+']').prop('checked', true);

    } else if( tag == 'select' ) {
        $('[name='+key+']').val(val);
        $('[name='+key+']').attr('value', val);

    } else if( tag == 'div' ) {
        let getElement = $('[name='+key+']'),
            getSelectedEl = getElement.find('.fc-selected');

        // Reset value
        if( getSelectedEl.length ) {
            getSelectedEl.removeClass('fc-selected'); 
        }

        getElement.attr('value', val);
        getElement.find('[data-slug="'+val+'"]').addClass('fc-selected');            

        // Set preselected value for right and left raked inside modal
        if( key === "left_raked" || key === "right_raked" ){
            if( typeof val !== "undefined" && val ){
                $('[name='+key+'] select').val(val);
            } else {
                $('[name='+key+'] select').val("none");
            }
        }

    }
}

function extra_fields() {

    var fd = getSelectedFenceData();
    
    var i            = fd.slug,
        tab          = fd.tab,
        custom_fence = fd.info,
        info         = fd.data
        tabInfo      = fd.tabInfo;

    var modal_key = fd.modKey,
        mbn       = fd.mbn;

    // START FORM FIELDS ON STEP 3
    $('[data-action="change"]').html('');

    $.each(info.form, function(k, v){

        var tpl = $('script[data-type="'+v.type+'"]').text()
                                                     .replace(/{{title}}/gi, v.title)
                                                     .replace(/{{slug}}/gi, v.slug)
                                                     .replace(/{{description}}/gi, v.description);


        $(v.target).append(tpl);

        // Select field
        $.each(v.option, function (i, item) {

            $('[name="'+v.slug+'"]').append($('<option>', { 
                value: i,
                text : item 
            }));
        });

        var selectValue = v.default;

        $('[name='+v.slug+']').val(selectValue);

    });

    $.each(tabInfo[0]?.fields, function(k, v){
        if( v.value ) {
            $('[name='+v.name+']').val(v.value);
        }
    });
    // END FORM FIELDS ON STEP 3

}

function set_cutom_fence_data() {

    var fd = getSelectedFenceData();
    
    var i            = fd.slug,
        tab          = fd.tab,
        custom_fence = fd.info,
        info         = fd.data,
        tabInfo    = fd.tabInfo;

    var modal_key = fd.modalKey,
        mbn       = fd.mbn;

    var filtered_data_tabs = tabInfo.filter(function(item) {
        return item.tab != tab;
    });

    if( info == undefined ) {

        $('.fc-tab-title, .fc-tab-subtitle').html('');
        $(FENCES.el.jsFcFormStep).hide();
        $(FENCES.el.fsiSelected).removeClass('fsi-selected');

        return;
    }

    filtered_data_tabs.push({
        tab            : tab,
        style          : i,
        fence          : info.slug,
        mbn            : mbn,
        fields         : $('[data-action="change"] .form-control').serializeArray(),
        isCalculate    : tabInfo[0]?.isCalculate || FENCES.defaultValues.measurement,
        calculateValue : tabInfo[0]?.calculateValue || FENCES.defaultValues.measurement
    });

    localStorage.setItem('custom_fence-'+tab, JSON.stringify(filtered_data_tabs));

}

function update_custom_fence_tab() {

    var fd = getSelectedFenceData();
    
    var i            = fd.slug,
        tab          = fd.tab,
        custom_fence = fd.info,
        info         = fd.data
        tabInfo      = fd.tabInfo;

    var modal_key = fd.modKey,
        mbn       = fd.mbn;
   
    var filtered_data_tabs = tabInfo.filter(function(item) {
        return item.tab != tab;
    });

    if( info == undefined ) {

        $(FENCES.el.fcTabTitle).html('SECTION ' + (tab+1) );
        $(FENCES.el.fcTabSubtitle).html('');

        $(FENCES.el.jsFcFormStep).hide();
        $(FENCES.el.fsiSelected).removeClass('fsi-selected');

        return;
    }

    mesurement = $(FENCES.el.measurementBoxNumber).val();
    mesurement = mesurement ? parseInt(mesurement).toLocaleString() + ' mm' : '';

    $(FENCES.el.fencingTabSelected).find('.ftm-measurement').html( mesurement );

    $(FENCES.el.fcTabTitle).html('SECTION ' + (tab+1) );

    subTitle = [mesurement, info['title']].filter(function(e){return e}).join(' <i class="fa-solid fa-caret-right ms-3"></i> ');

    $(FENCES.el.fcTabSubtitle).html(` <i class="fa-solid fa-caret-right ms-3"></i> ${subTitle}`);

    load_fencing_items();

}

function update_custom_fence(modal_key, fc_form_field = false) {

    var fd = getSelectedFenceData();
    
    var i       = fd.slug,
        tab     = fd.tab,
        data    = fd.info,
        info    = fd.data
        tabInfo = fd.tabInfo;

    let form_field = fc_form_field || $('.fc-form-field:visible');

    let itemKey = 'custom_fence-'+tab+'-'+i;

    var modalKeys = ['left_side', 'right_side', 'post_options', 'panel_options', 'gate'];

    if( $.inArray(modal_key, modalKeys) !== -1 ){
        modal_key = FENCES.activeSetting;
    }

    settings = form_field.map(function(){

        var key  = $(this).attr('name'),
            val  = $(this).val() ? $(this).val() : $(this).attr('value'),
            type = $(this).attr('type'),
            tag  = $(this).get(0).tagName.toLowerCase(),
            obj  = {key:key, val:val, tag: tag, type: type};

        if( modal_key === "color_options"  ){
            obj.title      = $(this).attr('data-title') || '';
            obj.subtitle   = $(this).attr('data-subtitle') || '';
            obj.color_code = $(this).attr('data-color-code') || '';
        }

        return obj;

    }).get();

   settings = mergeSettings(data, settings, 'control_key', modal_key);

    var filtered_data = data.filter(function(item) {
        return item.control_key != modal_key;
    });
    
    filtered_data.push({
        id          : i, 
        control_key : modal_key, 
        settings    : settings
    });

    if( modal_key === "color_options" ){
        
        itemKey = 'project-plans';
        color_data = {};
        let text_color = "#fff";

        // To make the text readable in project plans page,
        // we need to change the text to black if the selected color is white
        if( settings[0].val.indexOf('white') !== -1 ){
            text_color = '#000';
        }

        color_data.color = {
            code       : settings[0].color_code, 
            subtitle   : settings[0].subtitle, 
            title      : settings[0].title,
            value      : settings[0].val,
            text_color : text_color
        };

        updateOrCreateObjectInLocalStorage(itemKey, color_data);

    } else {
        
        localStorage.setItem(itemKey, JSON.stringify(filtered_data));
    
    }

   update_custom_fence_tab();

}

function mergeSettings(data, settings, key,  modal_key){

    //Check first if a control_key already exists and get it
    const find_existing_data = data.find(obj => obj[key] === modal_key);
    
    if( typeof find_existing_data !== "undefined" ){

        let merge_settings = [];

        find_existing_data.settings?.forEach(obj => {
            merge_settings.push(obj);
        });

        settings.forEach(obj => {

            const indexToRemove = merge_settings.findIndex(item => item.key === obj.key);

            // Check if the object with the given ID was found
            if (indexToRemove !== -1) {
                // Remove the object from the array using splice
                merge_settings.splice(indexToRemove, 1);
            }

            merge_settings.push(obj);

        });

        settings = merge_settings;

    }

    return settings;
}

//----------------------------------------------------------------

function update_custom_fence_gate() {

    var fd = getSelectedFenceData();
    
    var i       = fd.slug,
        tab     = fd.tab,
        info    = fd.info,
        data    = fd.data
        tabInfo = fd.tabInfo;

    var modal_key = 'gate';

    placement = $(FENCES.el.fencingPanelGate).prev().prev().prev().attr('data-id');
    placement = placement == undefined ? -1 : placement;

    var settings = {
        'placement' : placement,
        'index'     : $(FENCES.el.fencingPanelGate).index(),
        'size'      : $('[name="width"]').val(),
        'unit'      : FENCES.defaultValues.unit
    }
    
    settings.fields = $('.fc-form-field:visible').map(function(){

        var key  = $(this).attr('name'),
            val  = $.inArray($(this).attr('type'), ['radio','checkbox']) !== -1 ?  ($('[name="use_std"]').is(':checked')) : $(this).val(),
            type = $(this).attr('type'),
            tag  = $(this).get(0).tagName.toLowerCase(),
            obj  = {key:key, val:val, tag: tag, type: type};

        return obj;

    }).get();

    var filtered_data = info.filter(function(item) {
        return item.control_key != modal_key;
    });

    if( $(FENCES.el.fencingPanelGate).length ) {

        filtered_data.push({
            id: i, 
            control_key: modal_key, 
            settings: settings
        });

    } 

    localStorage.setItem('custom_fence-'+tab+'-'+i, JSON.stringify(filtered_data));
}

//----------------------------------------------------------------

function zoom(parent, direction) {
  var slider = $(parent).closest('.fencing-input-range').find("input");
  var step = parseInt(slider.attr('step'), 10);
  var currentSliderValue = parseInt(slider.val(), 10);
  var newStepValue = currentSliderValue + step;

  if (direction === "out") {
    newStepValue = currentSliderValue - step;
  } else {
    newStepValue = currentSliderValue + step;
  }

  slider.val(newStepValue).change();
};

//----------------------------------------------------------------

function add_new_fence_section() {

    $(FENCES.el.fencingTab).eq(0).clone().appendTo(FENCES.el.tabArea);

    $(FENCES.el.fencingTab).removeClass('fencing-tab-selected');
    $('.fencing-tab:last-child').addClass('fencing-tab-selected');

    var tabCount = $(FENCES.el.fencingTab).length;

    $('.fencing-tab:last-child').find('.fencing-tab-number').html( tabCount );

    $('.fencing-tab:last-child').toggleClass(`fc-section-1 fc-section-${tabCount}`);

    $(FENCES.el.measurementBoxNumber).val(FENCES.defaultValues.measurement);

    update_custom_fence_style_item();

    $(FENCES.el.jsBtnDeleteFence).show();

    $('.fc-tab-title, .fc-tab-subtitle').html('');
    $(FENCES.el.jsFcFormStep).hide();
    $(FENCES.el.fsiSelected).removeClass('fsi-selected');

    $(FENCES.el.fcFenceResetAll).hide();

    // Store section count
    localStorage.setItem('custom_fence-section', $(FENCES.el.fencingTab).length);

    setSectionURLParam();

}

//----------------------------------------------------------------

function move_the_gate(move) {

    var gate = $(FENCES.el.fencingPanelGate);

    if( move == 'left' ) {

        closest_id = gate.prev().prev().prev().attr('id');

        if( $(FENCES.el.fencingPanelGate).index() == 1 || closest_id == undefined ) {
            return;
        }

        $(gate).swapWith( $('#'+closest_id) );

        gate.remove();

    } else if( move == 'right' ) {

        closest_id = gate.next().next().next().attr('id');

        if( closest_id == undefined ) {
            return;
        }

        $(gate).swapWith( $('#'+closest_id) );

        gate.remove();

    } if( move == 'first' ) {

        var index =  $('#panel-item-0').index()/3;

        if( index != 2 ) {

            move_gate = gate.prop("outerHTML") + gate.prev().prop("outerHTML") + gate.prev().prev().prop("outerHTML");
            
            gate.prev().prev().remove();
            gate.prev().remove();

            $('#panel-item-0').before( move_gate ); 

            gate.remove();
        }

    } else if( move == 'last' ) {

        var closest_id = $(FENCES.el.panelItem).length-1,
            last_id = $(FENCES.el.panelItem).last().attr('data-id');

        if( $('.right_raked-panel .fencing-raked-panel').length ) {            
            last_id = $(FENCES.el.panelItem).last().attr('data-id')-1;
        }

        var index =  ($('#panel-item-'+last_id).index()/3)+1,
            gate_index = gate.index()/3;         


        if( index != gate_index ) {

            move_gate = gate.next().next().prop("outerHTML") + gate.next().prop("outerHTML") + gate.prop("outerHTML");

            gate.next().next().remove();
            gate.next().remove();

            $('#panel-item-'+last_id).after( move_gate );  

            gate.remove();

        }

    } else if( move == 'delete' ) {

        var index =  $('#panel-item-0').index()/3;

        $(FENCES.el.btnGate).html('Add Gate');
        $(FENCES.el.fencingPanelGate).removeAttr('data-cart-value');
        FCModal.close();
        $('.fc-btn-active').removeClass('fc-btn-active');

 
        if( index == 2 ) {
            gate.next().next().remove();   
            gate.next().remove();              
        } else {
            gate.prev().prev().remove();   
            gate.prev().remove();    
        }

        gate.remove();

        setTimeout(function(){
            $('.btn-fc-calculate').trigger('click');            
        });

    }

    update_custom_fence_gate();

    $(FENCES.el.fencingDisplayResult).scrollCenter(".fencing-panel-gate", 300);

}

function restore_items( remove_index ) {

    var last_tid = $('.fencing-tab:last-child').index();

    $( ".fencing-tab" ).each(function() {

        var tid = $( this ).index();


        if( remove_index <= tid ) {

            var next_index = tid+1;

            form = JSON.parse(localStorage.getItem('custom_fence-'+next_index));
            settings = localStorage.getItem('custom_fence-'+next_index+'-'+form[0].style);

            // Update items
            localStorage.setItem('custom_fence-'+tid, JSON.stringify(form));

            if( settings ) {
                localStorage.setItem('custom_fence-'+tid+'-'+form[0].style, settings);
            }

        }

    });
    console.log(last_tid);
//    localStorage.removeItem('custom_fence-'+last_tid);

}

$.fn.scrollCenter = function(elem, speed) {

    var active = $(this).find(elem); // find the active element

    if( active.length == 0 ) {
        return;
    }

    //var activeWidth = active.width(); // get active width
    var activeWidth = active.width() / 2; // get active width center

    //alert(activeWidth)

    //var pos = $('#timepicker .active').position().left; //get left position of active li
    // var pos = $(elem).position().left; //get left position of active li
    //var pos = $(this).find(elem).position().left; //get left position of active li
    var pos = active.position().left + activeWidth; //get left position of active li + center position
    var elpos = $(this).scrollLeft(); // get current scroll position
    var elW = $(this).width(); //get div width
    //var divwidth = $(elem).width(); //get div width
    pos = pos + elpos - elW / 2; // for center position if you want adjust then change this

    $(this).animate({
    scrollLeft: pos
    }, speed == undefined ? 1000 : speed);

    return this;
};

//----------------------------------------------------------------

function getCartItemStorage() {

    var values = [];

    Object.entries(localStorage).forEach(([key, value]) => {
      if (key.startsWith("cart_items")) {
        var cartData = JSON.parse(localStorage.getItem(key)),
            fence = key.split('-').pop();

        values.push({[fence]:cartData});
      }
    });

    return values;
}

function removeItemStorageWith(startsWith) {
    Object.entries(localStorage).forEach(([key, value]) => {
        if (key.startsWith(startsWith)) {
            localStorage.removeItem(key);
        }
    });
}

//----------------------------------------------------------------

function submit_fence_planner(status ='') {

    // window.onbeforeunload = function() {}

    // Removed unwanted cart
    removeItemStorageWith('cart_items-');

    //Set some delay to make sure the local storage and the html markup are loaded
    var items = localStorage.getItem('custom_fence-section') ?? 1;
    for (let i = 0; i < items; i++) {
        FENCES.cartItems.init( i );
    }    
    
    var set_fc_data   = [];
    var project_plans = JSON.parse(localStorage.getItem('project-plans'));
    var cart_items    = getCartItemStorage();

    var incompleteSection = 0; 

    $( ".fencing-tab" ).each(function() {

        var tid = $( this ).index();

        form = JSON.parse(localStorage.getItem('custom_fence-'+tid));

        if( form != null ) {

            settings = JSON.parse(localStorage.getItem('custom_fence-'+tid+'-'+form[0]?.style));

            form[0].style = form[0]?.style;
            form[0].tab = form[0]?.tab + 1;

            set_fc_data.push({
                'form': form,
                'settings': settings
            }); 

            if( ! form[0]?.calculateValue ) {
                incompleteSection += 1; 
            }

        } else {
            incompleteSection += 1; 
        }

    });

    if( incompleteSection > 0 ) {

        $('.ftm-measurement:empty').closest(FENCES.el.fencingTab).addClass('incomplete-section');
        
        $('.fc-loader-overlay').hide();
        $('.fc-section-step').hide();
        $('[data-tab="1"]').show();
        
        tabContainerScroll();
        
        return false;
    }        


/*    var sectionCount = $(".fencing-tab").find('.ftm-measurement').filter(function () {
        return !$(this).is(':empty');
    }).length;

    localStorage.setItem('custom_fence-section', sectionCount);

    return;*/

/*    $.post('submit.php', {data : JSON.stringify(set_fc_data)}, 
        function(data, status) {
            console.log(data);
    });*/

    var form = $('form')[0]; 
    var formData = new FormData(form);

    formData.set("fences", JSON.stringify(set_fc_data));

    formData.set("cart_items", JSON.stringify(cart_items));

    formData.set("project_plans", JSON.stringify(project_plans));

    Object.entries(project_plans).forEach(([key, value]) => {
        if (typeof value === 'object') {
            value = JSON.stringify(value);
        }

        // remove brackets if key contains array format
        if (key.includes("[]")) {
            key = key.replace('[]', '');
        }

        formData.set(key, value);
    });

    $.ajax({
        url: 'submit.php', 
        type: "POST",  
        data: formData,
        headers: {},
        contentType: false,  
        cache: false,         
        processData:false,    
        success: function(response) {
            try {

                var count = 0;
                   
                if( status == 'new' ) {
                     setTimeout(function(){
                        $('.fc-loader ul li').each(function(i) {
                            var $this = $(this);
                            setTimeout(function(){
                               $this.addClass('fc-text-success');
                               count++;
                               if( count == 1 ) {
                                    /*
                                    window.onbeforeunload = function () {
                                        return;
                                    }
                                    */
                                    window.location = 'project-plan.php';
                               }
                            }, 1000 * i);
                        });
                    }, 1000);

                } else {
                    setTimeout(function(){
                        $('.fc-loader ul li').each(function(i) {
                            var $this = $(this);
                            setTimeout(function(){
                               $this.addClass('fc-text-success');
                               count++;

                               if( count == 1 ) {
                                    /*
                                    window.onbeforeunload = function () {
                                        return;
                                    }
                                    */
                                    window.location = 'project-plan.php?qid='+planner_id;
                               }
                               
                            }, 1000 * i);
                        });
                    }, 1000);
                }
            } catch(err){

            } 
        }
    });

}

//----------------------------------------------------------------

function setSectionURLParam() {
    var index = $(FENCES.el.fencingTabSelected).index();
        tab   = index + 1;

    history.pushState({}, '', '?section='+tab);    
}

//----------------------------------------------------------------

function reloadFencingData() {

    if( getSearchParams('qid') && !fc_fence_info.fence_data ) {
        clearFencingData();
        location.href = location.origin+location.pathname;
    }

    if( fc_fence_info?.length == 0 ) {
       return;
    }

    var custom_fence_items = JSON.parse(fc_fence_info.fence_data, true);

    $(custom_fence_items).each(function(k, v){

        v.form[0].style = v.form[0].style;
        v.form[0].tab   = v.form[0].tab - 1;
        
        localStorage.setItem('custom_fence-'+v.form[0].tab, JSON.stringify(v.form));

        if( v.settings ) {
            localStorage.setItem('custom_fence-'+v.form[0].tab+'-'+v.form[0].style,  JSON.stringify(v.settings));            
        }
    });

    var cart_items = JSON.parse(fc_fence_info.cart_items_data);
    $(cart_items).each(function(k, v){

        localStorage.setItem('cart_items-'+k, JSON.stringify(v));
    });

    localStorage.setItem('custom_fence-section', fc_fence_info.section_count);

    localStorage.setItem('project-plans', fc_fence_info.project_plans_data);

    // Reset URL
    // history.pushState({}, '', location.origin+location.pathname);
}

//----------------------------------------------------------------

function clearFencingData() {
    // Add clear fence planner local storage here
    let keysToRemove = ["project-plans", "countdown-date", "custom_fence-", "cart_items-"];
    keysToRemove.forEach(k => removeItemStorageWith(k) );
}

//----------------------------------------------------------------

function zooming(zoom) {

    var raked_panel_mt = '20px';

    if( $('.raked-panel .fencing-raked-panel').length ) {   
        var raked_panel_mt = '30px';
    }

    if( zoom == 'reset' ) {
        step = 1;  
    }

    if( zoom == 'in' ) {
        if( step < 1 ) {
             step = step + 0.10;
        } else {
            step = step + 0.10;       
        }
    }

    if( zoom == 'out' ) {
        if( step <= 1 ) {
             step = step - 0.10;
        } else {
            step = step - 0.10;            
        }
    }
    
    document.querySelector('.js-fc-zoom-progress').textContent = Math.floor(step*100) + "%";

    if( step >= 1 ) {
        $('.fencing-panel-items').css({'padding-top': raked_panel_mt, 'zoom': step,   'max-height' : '200px'});
        $('.fencing-display-result').css({'margin-top': 'auto'});
    } else {
        $('.fencing-panel-items').css({ 'zoom': step,   'max-height' : '200px'});
    }

    if( step == 1 ) {

        if( $('.raked-panel .fencing-raked-panel').length ) {   
 
        }

        $('.fencing-display-result').css({'margin-top': raked_panel_mt});
        $('.fencing-panel-items').removeAttr('style');
    }

    toggleZoomResetButton(step);

}

// https://stackoverflow.com/questions/19743228/scroll-the-page-on-drag-with-jquery
var cursordown = false;
var cursorypos = 0;
var cursorxpos = 0;
$('.fencing-display-result').mousedown(function(e){
    cursordown = true; 
    cursorxpos = $(this).scrollLeft() + e.clientX; 
    cursorypos = $(this).scrollTop() +e.clientY;
}).mousemove(function(e){
    if(!cursordown) return;
    try { $(this).scrollLeft(cursorxpos - e.clientX); } catch(e) { }
    try { $(this).scrollTop(cursorypos - e.clientY); } catch(e) { }
}).mouseup(end = function(e){
    cursordown = false;
}).mouseleave(end);

$('.fencing-display-result').on("mousedown touchstart", function(e) {
    var $this = $(this);

    $(this).addClass('is-grabbing');  

    setTimeout(function(){
        if( ! $('.fencing-modal').is(':visible') ) {
            $this.addClass('grabbing').removeClass('is-grabbing');  
        }
    }, 200);

});



$('.fencing-display-result').on("mouseup touchend", function(e) {
    var $this = $(this);
    $(this).removeClass('grabbing'); 

    setTimeout(function(){
        $this.removeClass('grabbing is-grabbing');  
    }, 200);         
});


/**
 * Get the color title and subtitle
 * and assign it to the closest fc-form-field
 * @param {string} _color_el 
 * @param {string} form_field 
 */
function getSelectedColorDetails(_color_el, form_field ){

    var getFormFieldKey = form_field.attr('data-key');
    var title           = _color_el.attr('data-color-title');
    var subtitle        = _color_el.attr('data-color-subtitle');
    var colorCode       = _color_el.attr('data-color-code');

    if( getFormFieldKey === "color_options" ){
        form_field.attr('data-title', title).attr('data-subtitle', subtitle).attr('data-color-code', colorCode);
    }
}

/**
 * Get global setting from localStorage
 */
function loadGlobalSetting() {

    let getGlobalSetting = localStorage.getItem('custom_fence-global-setting');
    let globalSettingObj = getGlobalSetting ? JSON.parse(getGlobalSetting)[0] : [];
    let globalSetting    = globalSettingObj['settings'];
    let globalControlKey = globalSettingObj['control_key'];

    updateElements(globalSetting, "color_options", ["title", "subtitle", "color_code"]);

}

/**
 * Get values from setting array
 * @param {array} setting 
 * @param {string} key 
 * @param {array} props 
 */
function updateElements(setting, key, props){
    let entry = findObjectByKey(setting, key);

    for( let i = 0; i < props.length; i++ ){
        updateElement(entry.key, props[i], entry[props[i]]);
    }
}

/**
 * Update element
 * @param {string} control_key 
 * @param {string} property 
 * @param {string} value 
 */
function updateElement(control_key, property, value){

    if( typeof document.querySelector('.js-' + control_key + '-' + property) === undefined ) {
        return;
    }

    let getEl = document.querySelector('.js-' + control_key + '-' + property);

    if( property === "color_code" ){
        getEl.style.backgroundColor = value;

        if(getEl.querySelector('strong').textContent.toLowerCase().includes('white')){
            getEl.querySelector('strong').style.color = "#000";
        }

    } else {
        getEl.textContent = value;
    }

}

/**
 * Find Object by property value
 * @param {array} array 
 * @param {string} keyToFind 
 * @returns 
 */
function findObjectByKey(array, keyToFind) {
    for (let i = 0; i < array.length; i++) {
      if (array[i].key === keyToFind) {
        return array[i];
      }
    }
    return null;
}
  

/**
 * Load section overlay dynamically
 * @param {string} target 
 */
function loadSectionOverlay(target) {

    let tpl = `<div class="fc-section-loader-overlay">
                <div class="fc-loader-container">
                    <div class="fc-loader"><div class="fc-loader-gif"></div></div>
                </div>
            </div>`;

    target = document.getElementById(target);
    target.insertAdjacentHTML('afterbegin', tpl);

}

/**
 * Remove section overlay
 */
function removeSectionOverlay(){
    document.querySelector('.fc-section-loader-overlay').remove();
}

/**
 * Function to add or update an object in the array by key property
 * @param {array} objectArray 
 * @param {obj} obj 
 * @returns 
 */
function addObjectByKey(objectArray, obj) {
    const existingIndex = objectArray.findIndex(item => item.control_key === obj.control_key);

    if (existingIndex !== -1) {
      objectArray[existingIndex] = obj;
    } else {
      objectArray.push(obj);
    }

    return objectArray;
}

/**
 * Toggle Tab Container Scroll
 * @param {obj} _this 
 */
function tabContainerScroll() {

    let _tab_parent_class = FENCES?.el?.tabContainer;
    let _tab_content_class = '.js-fencing-tab-container-area';
    let _main_parent = $('.js-fencing-tabs-container');

    _main_parent.removeClass('enable-scroll');

    let _main_parent_width = _main_parent.width();
    let _trigger_width = $('.fencing-tabs-area').width();

    $('.fc-content-tab-title').css({'border-top-right-radius':''});

    if( _trigger_width >= _main_parent_width ) {
        _main_parent.addClass('enable-scroll');
        draggable(_tab_parent_class, _tab_content_class);
        $('.fc-content-tab-title').css({'border-top-right-radius':0});
    }

    moveScrollPosition(_tab_parent_class, $(_tab_parent_class).prop('scrollWidth'));
}

/**
 * Move Horizontal scroll position
 * @param {string} _el 
 */
function moveScrollPosition(_el, _position) {
    $(_el).animate({scrollLeft:  _position}, 0);
}

/**
 * Draggable elements
 */
function draggable(_parent, _content) {

    if( isMobileDevice() ){
        return;
    }

    // Select the draggable element and its content
    const draggableElement = document.querySelector(_parent);
    const contentElement = draggableElement.querySelector(_content);

    let isDragging = false;
    let initialX;
    let xOffset = 0;

    // Function to handle the mouse down event
    function onMouseDown(event) {
        isDragging = true;
        initialX = event.clientX;
        xOffset = draggableElement.scrollLeft;
    }

    // Function to handle the mouse move event
    function onMouseMove(event) {
        if (isDragging) {
            const currentX = event.clientX;
            const deltaX = currentX - initialX;
            draggableElement.scrollLeft = xOffset - deltaX;
        }
    }

    // Function to handle the mouse up event
    function onMouseUp() {
        isDragging = false;
    }

    //Attach events
    draggableElement.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);

}

/**
 * 
 * @returns Check if device is tablet/mobile
 */
function isMobileDevice() {
    // Check the user agent string for common mobile keywords
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}


function deleteSectionTab() {
    
    let getActiveTab      = $(FENCES.el.fencingTabSelected);
    let getActiveTabIndex = getActiveTab.index();
    let getPrevBtn        = getActiveTab.prev();
    let getNextBtn        = getActiveTab.next();

    getActiveTab.addClass('is-deleting');

    if( getActiveTab.is(':last-child') ){
        getPrevBtn.trigger('click');
    } else {
        getNextBtn.trigger('click');
    }

    $('.is-deleting').remove();

    tabContainerScroll();

    // Store section count
    localStorage.setItem('custom_fence-section', $(FENCES.el.fencingTab).length);

}

//----------------------------------------------------------------

function refreshSectionTabIndex() {
    $('.fencing-tab-container .fencing-tab').each(function(index) {
        $(this).find('.fencing-tab-number').html( index+1 );
    });

    setSectionURLParam();
}

//----------------------------------------------------------------

function resetSectionsBlocks() {

    $('.fencing-style-item').removeClass('fsi-selected');
    $(FENCES.el.jsFcFormStep).removeAttr('style');

}

/**
 * Remove deleted section entry in local storage
 */
function deleteLocalStorageEntry(){

    //Get selected tab
    let getActiveTab = $(FENCES.el.fencingTabSelected);

    //Get selected tab index
    let getActiveTabIndex = getActiveTab.index();


    //Find and delete all instance of it in local storage
    deleteAllEntriesBySubstring("custom_fence-" + getActiveTabIndex);
    localStorage.removeItem("cart_items-" + getActiveTabIndex+1);

}

/**
 * If a user revisit a section tab
 * Check if the calculate button was clicked before
 * If yes, then set the mm input field value 
 * and click it again to load the step 3 section
 * @param {obj} custom_fence_tab 
 */
function loadStep3(custom_fence_tab) {
    //Check if user clicks the calculate button for fence section
    if( custom_fence_tab?.isCalculate ){
        //Set the mm field value
        $('.btn-fc-calculate').prev().find('input').val(custom_fence_tab.calculateValue);
        //Then trigger click into the calculate button to load section 3
        $('.btn-fc-calculate').trigger('click');
    }
}

/**
 * Delete custom_fence-{idx} and custom_fence-{idx}-{styleIdx} instances in localStorage
 * @param {string} substring 
 */
function deleteAllEntriesBySubstring(substring) {
  
    // Use a while loop to delete all matching entries
    while (true) {
      // Find the index of the first matching key
      const index = Object.keys(localStorage).findIndex(key => key.indexOf(substring) !== -1);
    
      // If no more matching keys are found, exit the loop
      if (index === -1) {
        break;
      }
  
      // Get the matching key and delete the entry
      const matchingKey = Object.keys(localStorage)[index];
      localStorage.removeItem(matchingKey);
  
    }
  
}

/**
 * GET Segment URI value | key=value
 * @param {string} k 
 * @returns string
 */
function getSearchParams(k){
    var p={};
    location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(s,k,v){p[k]=v})
    return k?p[k]:p;
}

  
/**
 * Refresh local storage
 * Run this after deleting a section tab
 * This function will only update the index of storage entries  
 * that appear after the deleted entry based on the index position
 * `custom_fence-{idx}` and `custom_fence-{idx}-{styleIdx}`
 */
function refreshLocalStorage(activeSectionIndex, target) {

    //Only get storage entries related to custom fence
    const totalEntries = countLocalStorageFenceKeys(target);

    //Iterate each entries
    for (let i = activeSectionIndex; i <= totalEntries; i++) {

        let newIndex = i - 1;

        //If -1, set value to 0
        if( newIndex == -1 ){
            newIndex = 0;
        }

        //Retrieve to the old key
        const oldKey = `${target}-${i}`;

        //Prepare the new key string format
        const newKey = `${target}-${newIndex}`;
        
        // Check if the oldKey exists in localStorage and update it
        if ( localStorage.getItem(oldKey)) {

            //Grab the old key value
            const value = JSON.parse(localStorage.getItem(oldKey));

            //Remove old key entry from local storage
            localStorage.removeItem(oldKey);

            //Update the tab value with new Index
            value[0].tab = newIndex;
            
            //Set the new key entry
            localStorage.setItem(newKey, JSON.stringify(value));

            //For Styles
            //Grab the old style key value
            const oldStyleKey = `${target}-${i}-${value[0].style}`;

            //Prepare new key string format
            const newStyleKey = `${target}-${newIndex}-${value[0].style}`;

            // Check if the old style Key exists in localStorage
            if ( localStorage.getItem(oldStyleKey)) {

                //Get the value
                const value = JSON.parse(localStorage.getItem(oldStyleKey));

                //Remove the old style key
                localStorage.removeItem(oldStyleKey);

                //Set the new style key entry
                localStorage.setItem(newStyleKey, JSON.stringify(value));
            }
        }
    
    }

}

//----------------------------------------------------------------

function countLocalStorageFenceKeys(target) {
    
    let count = 0;

    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);

        // Check if the key contains the substring "custom_fence"
        if (key.includes(target)) {
            count++;
        }
    }

    return count;
}

/**
 * 
 * Hide Delete Button
 * 
 */
function hideDeleteSectionBtn() {

    let _remaining_tabs = $(FENCES.el.tabArea).children().length;
    let _delete_btn = $(FENCES.el.jsBtnDeleteFence);


    if ( _remaining_tabs == 1 ){
        _delete_btn.hide();
    }

    _delete_btn.removeAttr('disabled');

}

/**
 * Dont delete the first section
 * @returns boolean
 */
function stopSectionDeletion() {

    let _remaining_tabs = $(FENCES.el.tabArea).children().length;

    if( _remaining_tabs == 1 ) {
        return false;
    }
}

/**
 * Hide / Show Zoom Reset Button
 */
function toggleZoomResetButton(zoomValue) {
    if( zoomValue == 1 ){
        hideZoomResetButton();
    } else{
        showZoomResetButton();
    }
}

/**
 * Hide Zoom Reset Button
 */
function hideZoomResetButton() {
    $(FENCES.el.zoomReset).hide();
}

/**
 * Show Zoom Reset Button
 */
function showZoomResetButton() {
    $(FENCES.el.zoomReset).removeAttr('style');
}

/**
 * Set defaul value for measurement box
 */
function setMeasurementDefaultValue() {
    $(FENCES.el.measurementBoxNumber).val(FENCES.defaultValues.measurement);
}

function getSelectedRadioValue(name) {
    // Get a NodeList of all radio buttons with the name "gender"
    var radios = document.getElementsByName(name);
  
    // Initialize a variable to store the selected value
    var selectedValue = '';
  
    // Loop through the radio buttons to find the selected one
    for (var i = 0; i < radios.length; i++) {
      if (radios[i].checked) {
        selectedValue = radios[i].value;
        break; // Exit the loop since we found the selected radio button
      }
    }
  
    return selectedValue
}

//----------------------------------------------------------------

function getSelectedCheckboxes(name) {
    // Get a NodeList of all checkboxes with the name "fruits"
    var checkboxes = document.getElementsByName(name);
  
    // Initialize an array to store the selected values
    var selectedValues = [];
  
    // Loop through the checkboxes to find the selected ones
    for (var i = 0; i < checkboxes.length; i++) {
      if (checkboxes[i].checked) {
        selectedValues.push(checkboxes[i].value);
      }
    }
  
    if (selectedValues.length == 0) {
      return false;
    }

    return selectedValues.join(',');
}
  
const submitModal = document.getElementById("submit-modal");
const formDownload = document.getElementById("fc-planning-form");
const projectPlanKey = "project-plans";

/**
 * Restore data from local storage when the page loads
 */
function restoreFormData() {
    const formData = JSON.parse(localStorage.getItem(projectPlanKey)) || {};
    for (const key in formData) {
      const input = document.querySelector(`[name="${key}"]`);

      if ( input ) {
        if ( input.type === "checkbox" ) {
          let selectedValues = formData[key];

            selectedValues = selectedValues.map(item => item.trim());

          if (Array.isArray(selectedValues)) {
            for( let i = 0; i < selectedValues.length; i++ ){
                var checkBox = document.querySelector('input[type=checkbox][name="'+key+'"][value="' + selectedValues[i] + '"]');
                if(checkBox) checkBox.checked = true;
            }
          } else {
            var checkBox = document.querySelector('input[type=checkbox][name="'+key+'"][value="' + formData[key] + '"]');
            checkBox.checked = true;
          }

        } else if (input.type === "radio") {
          var radioBtn = document.querySelector('input[type=radio][name="'+key+'"][value="' + formData[key] + '"]');
          if(radioBtn) radioBtn.checked = true;
        } else if (input.type === "select-one") {
          input.value = formData[key];
        } else {
          input.value = formData[key];
        }
      }
    }
  }


/**
 * Save form data to local storage whenever a field changes
 */
function saveFormData() {

    const formData = {};
    const otherFormFields = formDownload ? formDownload.querySelectorAll("[name=notes]") : '';
    const formFields = submitModal.querySelectorAll("[name]");
    let formFieldsArray = [...formFields, ...otherFormFields];

    $(formFieldsArray).each(function(i, item){

        var name = $(item).attr('name'),
            type = $(item).attr('type'),
            val  = $(item).val();

        if (type === "checkbox") {
            formData[name] = formData[name] || [];

            if ( $(item).is(':checked') ) {
                formData[name].push(val);
            }
             
        } else if (type === "radio") {

            if ( $(item).is(':checked') ) {
                formData[name] = val;
            }  

            if( $('[name="'+name+'"]').length == 1 ) {
                if ( ! $(item).is(':checked') ) {
                    formData[name] = '';
                }                
            } 

        } else {

            formData[name] = val;
        }

    });

    updateOrCreateObjectInLocalStorage(projectPlanKey, formData);
}


// Add event listeners TO form elements inside the submit-modal div
if( submitModal ){
   $(document).on('keyup', 'textarea, input', saveFormData);
    submitModal.addEventListener("change", saveFormData);
}

//----------------------------------------------------------------

function savePlanner() {

   // var form = $('form')[0]; 
    var formData = new FormData();

    formData.set("action", 'save_planner');

    $.ajax({
        url: 'checkout.php', 
        type: "POST",  
        data: formData,
        headers: {},
        contentType: false,  
        cache: false,         
        processData:false,    
        success: function(response) {
            try {
                var info = JSON.parse(response);

                if( ! info.error ) {
                    $('.quote-id-card .qic-body').html(info.id);
                }

            } catch(err){

            } 
        }
    });

}

//----------------------------------------------------------------

function getActiveFencing() {
    let sectionCount = localStorage.getItem('custom_fence-section'),
        fenceStyle   = [];

    for (let i = 0; i < sectionCount; i++) {
        var cf = JSON.parse(localStorage.getItem('custom_fence-'+i));

        if( cf ) {
            style = cf[0].style;
            fenceStyle.push(style);            
        }
    }    
    
    return fenceStyle.filter((v, p) => fenceStyle.indexOf(v) == p);    
}

//----------------------------------------------------------------

function loadColorOptions() {

    const project = JSON.parse(localStorage.getItem('project-plans'));

    var colorOption  = $('[data-load="color-options"]');

    var items = getActiveFencing();

    colorOption.html('');

    $('.fc-btn-create-plan').attr('disabled');

    $.each(items, function(k, v){
        if( v ) {
            var slug   = fc_data[v].slug,
                title  = fc_data[v].title,
                colors = fc_data[v].color;

            var tpl = $('script[data-type="color_options"]').text()
                                                            .replace(/{{slug}}/gi, slug)
                                                            .replace(/{{title}}/gi, title);       

            colorOption.append(tpl);    

            $.each(colors, function(k, v){
                $('[data-load="color-options"] [data-slug="'+slug+'"] [data-slug="'+v+'"]').addClass('on');
            });

            $('.fc-select-color:not(.on)').remove();
        }
    });

    if( project?.color ) {
        $.each(project.color, function(k, v) {
            $('.fc-color-options[data-slug="'+v.fence+'"] .fc-select-item[data-slug="'+v.color+'"]').addClass('fc-selected');
        });        

        if( $('.fc-color-options .fc-selected').length == items.length ) {
            $('.fc-btn-create-plan').removeAttr('disabled');
        }

    }

  //  setActiveColor();
}
loadColorOptions();

//----------------------------------------------------------------

function updateOrCreateObjectInLocalStorage(key, newData) {
    // Check if the key already exists in localStorage
    if ( localStorage.getItem(key) ) {
        // If it exists, parse the JSON data and update the object
        const existingData = JSON.parse(localStorage.getItem(key));
        const updatedData = { ...existingData, ...newData };
        // Save the updated object back to localStorage

        //convert array to string
        if( updatedData['extra'] && Array.isArray(updatedData['extra']) ){
            updatedData['extra'] = updatedData['extra'].join(', ');
        }


        localStorage.setItem(key, JSON.stringify(updatedData));
    } else {
        // If the key doesn't exist, create a new object and save it to localStorage
        localStorage.setItem(key, JSON.stringify(newData));
    }
 }
  
//----------------------------------------------------------------

function disabledCustomGate() {
    var fd = getSelectedFenceData();

    var width = fd.data.settings.gate.size.width;

    $('[name="width"]').attr('readonly', 'readonly').addClass('disabled text-muted').val(width);
    $('.fencing-qty-btn').addClass('d-none');
    $('.custom-gate button').click().attr('disabled', 'disabled').removeClass('btn-dark').addClass('btn-light');
}

//----------------------------------------------------------------

function getSelectedFenceData() {

    var slug = $('.fencing-style-item.fsi-selected').attr('data-slug'),
        itab = $('.fencing-tab.fencing-tab-selected').index(),
        info = localStorage.getItem('custom_fence-'+itab+'-'+slug),
        info = info ? JSON.parse(info) : [],
        data = fc_data[slug];   

    var tabInfo = localStorage.getItem('custom_fence-'+itab),
        tabInfo = tabInfo ? JSON.parse(tabInfo) : [];
   
    var modalKey = $(FENCES.el.fencingContainer).attr('data-key'),
        mbn      = $(FENCES.el.measurementBoxNumber).val();

    return {
        slug     : slug, 
        tab      : itab, 
        info     : info, 
        data     : data,
        mbn      : mbn,
        modalKey : modalKey,
        tabInfo  : tabInfo
    } 

}

