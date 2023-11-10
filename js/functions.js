function load_fencing_items() {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];

    $('.fencing-panel-container').html('');

    var calc = calculate_fences();

    if( !calc ){
        return;
    }

    for (let i = 0; i < calc.long_panel.count; i++) {

        var center_point = 50;
        
        mesurement = $('.measurement-box-number').val();

        var panel_number = i,
            panel_size = calc.long_panel.length,
            panel_unit = 'mm',
            data_key = "post_options",
            panel_option_value = calc.selected_values.panel_option;

        if( panel_option_value.indexOf('full') !== -1 ){
            panel_option_value = panel_option_value.split('_')[0];
        } 

        var tpl = $('script[data-type="panel_item-'+info.panel_group+'"]').text()
                                                     .replace(/{{data_key}}/gi, center_point)
                                                     .replace(/{{center_point}}/gi, center_point)
                                                     .replace(/{{panel_value}}/gi, panel_option_value)
                                                     .replace(/{{panel_size}}/gi, panel_size+'W')
                                                     .replace(/{{panel_unit}}/gi, '<br>PANEL')
                                                     .replace(/{{panel_number}}/gi, panel_number);    
    
        $('.fencing-panel-container').append(tpl);

        $('.fencing-panel-item').css({'width':panel_size*0.10});
    }  

    var tpl = $('script[data-type="panel_spacing-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point);

    $('.fencing-panel-container').append(tpl);

    if( calc.short_panel.count ) {

    for (let i = 0; i < 1; i++) {

        var panel_number = i,
            panel_size = calc.short_panel.length,
            panel_unit = 'mm';

        var tpl = $('script[data-type="short_panel_item-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point)
                                                     .replace(/{{panel_size}}/gi, panel_size+'W')
                                                     .replace(/{{panel_unit}}/gi, '<br>PANEL')
                                                     .replace(/{{panel_number}}/gi, panel_number);    
    
        $('.fencing-panel-container').append(tpl);

        $('.short-panel-item').attr('data-id', calc.long_panel.count+1)
                              .attr('id', 'panel-item-'+(calc.long_panel.count+1))
                              .css({'width':panel_size*0.10});

    }  

    var tpl = $('script[data-type="panel_spacing-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point);

    $('.fencing-panel-container').append(tpl);

    }

    update_gate('edit');

    // $('.fpsn-b:not(:first-child):not(:last-child)').remove();

    $('.fencing-panel-container').prepend('<div data-cart-key="raked-panel" class="left_raked-panel raked-panel"></div>');
    $('.fencing-panel-container').append('<div data-cart-key="raked-panel" class="right_raked-panel raked-panel"></div>');

    update_raked_panels(['left_raked', 'right_raked']);

    if( calc.offcut_panel.count && calc.offcut_panel.length ) {
        var tpl = $('script[data-type="offcut"]').text()
                                                 .replace(/{{count}}/gi, calc.offcut_panel.count)  
                                                 .replace(/{{group}}/gi, info.panel_group) 
                                                 .replace(/{{width}}/gi, calc.offcut_panel.length);     

        $('.fencing-panel-container').append(tpl);    

        $('.fencing-offcut').css({'width':calc.offcut_panel.length*0.10});
    }

    //updateFirstFencingPost();
    //updateLastFencingPost();
}

function updateLastFencingPost(){
    var elements = document.querySelectorAll('.fencing-panel-container [data-key="post_options"]');
    console.log('elements', elements);
    if (elements.length > 0) {
      var lastElement = elements[elements.length - 1];
      lastElement.setAttribute('data_key', 'left_side');
    }
}

function updateFirstFencingPost(){
    var elements = document.querySelectorAll('.fencing-panel-container [data-key="post_options"]');
    if (elements.length > 0) {
      var lastElement = elements[0];
      lastElement.setAttribute('data_key', 'right_side');
    }
}

function update_raked_panels(side) {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];

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


            if( has_post != 'yes-post' ) {
                var has_post = 'no-post '+side_part+'-panel-post';
            }
        }


        // Raked
        var filtered_settings = settings?.filter(function(item) {
            return item.key == v;
        });

        if( filtered_settings ) {

            if( filtered_settings[0]?.val != 'none' ) {

                if( side_part == 'left') {
                    panel_w = calc.left_raked.width;
                    panel_h = calc.left_raked.height;
                } else {
                    panel_w = calc.right_raked.width;
                    panel_h = calc.right_raked.height;
                } 

                
                var tpl = $('script[data-type="'+v+'-panel-'+info.panel_group+'"]').text()
                                                                .replace(/{{center_point}}/gi, center_point)
                                                                .replace(/{{panel_size}}/gi, panel_h+'H')
                                                                .replace(/{{panel_unit}}/gi,  panel_w+'W')
                                                                .replace(/{{panel_number}}/gi, 4)                                                     
                                                                .replace(/{{panel_size}}/gi, 5)
                                                                .replace(/{{panel_unit}}/gi, 6)
                                                                .replace(/{{panel_number}}/gi, 7)
                                                                .replace(/{{post}}/gi, has_post);   

                if( typeof panel_h !== "undefined"){
                    $('.'+v+'-panel').html(tpl);    
                }
                
            }

        }

        if( side_part == 'left' ) {
            $('.panel-post:not(.post-left):not(.post-right)').first().addClass('post-left panel-'+has_post).attr('data-key',"left_side").attr('post-side',"post_left");
            $('.fencing-panel-spacing-number').first().addClass(has_post);
        }

        if( side_part == 'right' ) {
            $('.panel-post:not(.post-left):not(.post-right)').last().addClass('post-right panel-'+has_post).attr('data-key',"right_side").attr('post-side',"post_right");
            $('.fencing-panel-spacing-number').last().addClass(has_post);
        }

    });

    // Left Panel post
    var left_panel_post = $('.left-panel-post.no-post span').text().replace('(', '').replace(')', '');
    $('.left-panel-post.no-post span').text('('+left_panel_post+')');

    // Right Panel Post
    var right_panel_post = $('.right-panel-post.no-post span').text().replace('(', '').replace(')', '');
    $('.right-panel-post.no-post span').text('('+right_panel_post+')');

    
    load_post_options_first_last_values(custom_fence, info);

    load_post_options_all(custom_fence, info);


    $('.fencing-display-result').css({'padding': ''});
    if( $('.raked-panel .fencing-raked-panel').length && $('.fencing-display-result').css('margin-top') != '70px' ) {   
        $('.fencing-display-result').css({'padding-top': '30px'});
    } else {
        $('.fencing-display-result').css({'margin-top': ''});        
    }

    $('.raked-panel .fencing-panel-item').css({'width':1200*0.10});

/*    setTimeout(function(){
        $('.fencing-panel-items').css({'justify-content':''});
        if( $(".fencing-panel-items").prop('scrollWidth') <= $(".fencing-display-result").width() ) {
            $('.fencing-panel-items').css({'justify-content':'center'});
        }
    }, 100);*/

}

/**
 * This function will update either First or Last post after user selection
 * @param {array} custom_fence 
 */
function load_post_options_first_last_values(custom_fence, info) {

    var side_post = '';

    //Get the settings of post_option from left_side and right_side
    var post_options_filtered_data = custom_fence.filter(function(item) {
        return item.control_key === "left_side" || item.control_key === "right_side";
    });

    if( post_options_filtered_data.length ) {

        //iterate both left and right side and get the values of post_options
        for( let i = 0; i < post_options_filtered_data.length; i++ ){
            let activeSetting = post_options_filtered_data[i].control_key;
            let settings = post_options_filtered_data[i].settings;

            for( let idx = 0; idx < settings.length; idx++ ){
                let key = settings[idx].key;
                let value = settings[idx].val;

                if(key === "post_option" ){
                    //We added data-key attribute on the first and last panel post both will have either left_side or right_side value
                    //Find the element that matches the condition below and add the class
                    $('.panel-post[data-key='+activeSetting+'], .fencing-panel-spacing-number').addClass(value).attr('data-cart-value', value);
                }
            }

        }

        var side_post = post_options_filtered_data[0].control_key;
    }


    // Get default post options
    var post_options_default = info.settings.post_options.fields[0].options.filter(function(item) {
        return item.default == true;
    });

    // Set default option on left side
    if( side_post != 'left_side' ) {
        $('.panel-post.post-left').addClass(post_options_default[0].slug);   
    } 

    // Set default option on right side
    if( side_post != 'right_side' ) {
        $('.panel-post.post-right').addClass(post_options_default[0].slug);
    }

}

/**
 * This function will update all posts except for the first and last post 
 * @param {array} custom_fence 
 * @param {obj} info 
 */
function load_post_options_all(custom_fence, info) {

    let panel_post = $('.panel-post');
    let panel_spacing_number = $('.fencing-panel-spacing-number');
    let exclude_panel_posts = ".post-left, .post-right";

    var post_options_filtered_data = custom_fence.filter(function(item) {
        return item.control_key === 'post_options';
    });

    if( post_options_filtered_data.length ) {

        //Get the value of Post Option
        var post_options_setting = post_options_filtered_data[0].settings.find(function(item) {
            return item.key === "post_option";
        });

        if( typeof post_options_setting !== "undefined" ){
            panel_post.not(exclude_panel_posts).addClass(post_options_setting.val).attr('data-cart-value', post_options_setting.val);
            panel_spacing_number.addClass(post_options_setting.val);
        }

    } else {

        // Get default post options
        var post_options_default = info.settings.post_options.fields[0].options.find(function(item) {

            return item.default == true;
        });
      
        panel_post.not(exclude_panel_posts).addClass(post_options_default.slug);
        panel_spacing_number.addClass(post_options_default.slug);

    }
}

function update_gate(action) {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];

    var find_gate = custom_fence.filter(function(item) {
        return item.control_key == 'gate';
    });

    if( find_gate.length ) {
        placement =  find_gate[0]?.settings?.placement;
    } else {

        placement = 0;

    }

    var center_point = 50,        
        mesurement = $('.measurement-box-number').val();

    var calc = calculate_fences();

    var panel_size = calc.gate.length,
        panel_unit = 'mm',
        gate_size  = calc.gate.length;

    if( action == 'add' || action == 'edit' ) {

        if( placement == -1 ) {

           var tpl = $('script[data-type="panel_gate-'+info.panel_group+'-r"]').text()
                                                         .replace(/{{center_point}}/gi, center_point)
                                                         .replace(/{{panel_size}}/gi, gate_size)
                                                         .replace(/{{panel_unit}}/gi, panel_unit);  

            $('#panel-item-0').before(tpl);

            $('#btn-gate').html('Edit Gate');

        } 


        if( find_gate.length && placement >= 0  || action == 'add' && placement == 0 ) {

            var tpl = $('script[data-type="panel_gate-'+info.panel_group+'-l"]').text()
                                                         .replace(/{{center_point}}/gi, center_point)
                                                         .replace(/{{panel_size}}/gi, gate_size)
                                                         .replace(/{{panel_unit}}/gi, panel_unit);  

            $('#panel-item-'+placement).after(tpl);    

            $('#btn-gate').html('Edit Gate');
        }                                             

    }

    $('.fencing-panel-gate')
        .prepend('<span class="fc-gate-spacing fc-gate-left-spacing">20</span>')
        .append('<span class="fc-gate-spacing fc-gate-right-spacing">20</span>')
        .attr('data-cart-value', 1);

                      
}

$.fn.swapWith = function(to) {
    return this.each(function() {
        var copy_to = $(to).clone(true);
        var copy_from = $(this).clone(true);
        $(to).replaceWith(copy_from);
        $(this).replaceWith(copy_to);
    });
};

function update_custom_fence_style_item() {

    var i = $('.fencing-style-item.fsi-selected').index(),
        info = fc_data[i];

    mesurement = $('.measurement-box-number').val() + ' mm';

    $('.fencing-tab-selected').find('.ftm-title').html( 'SECTION' ); // info['name']
    $('.fencing-tab-selected').find('.ftm-measurement').html( mesurement );

    $('.fencing-panel-controls').html('');


    $.each(info?.settings, function(k, v){

        /**
         * @TODO - re-check on how to disable from the settings
         */
        if( v.disabled ){
            return;
        }

        if( v.length !== 0 ) {

          var action = '';

          if( v.action, v.action.includes('edit')  ) {
              var action = 'Edit ';
          }
          if( v.action, v.action.includes('add')  ) {
              var action = 'Add ';            
          }

          $('<button>').html(action+v.label).attr({
            'type' : 'button',
            'id' : 'btn-'+k,
            'data-key' : k,
            'data-target' : "#fc-control-modal",
            'class' : 'btn-fc btn-fc-outline-default fencing-btn-modal fc-mb-1'
          }).appendTo(".fencing-panel-controls");

        }
   
    });

    $('#btn-gate').before('<div></div>');

    update_custom_fence_tab();
}

function update_color_options() {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];

    key = 'color_options';

    $('[data-key="'+key+'"]').html('');

    var filtered_data = custom_fence.filter(function(item) {
        return item.control_key == key;
    });

    $.each(info.color_options, function(k, v){

        var tpl = $('script[data-type="color_option"]').text()
                                                     .replace(/{{title}}/gi, v.title)
                                                     .replace(/{{slug}}/gi, v.slug)
                                                     .replace(/{{sub_title}}/gi, v.sub_title)
                                                     .replace(/{{background_color}}/gi, v.background_color)
                                                     .replace(/{{text_color}}/gi, v.text_color)
                                                     .split(/\$\{(.+?)\}/g);

        $('[data-key="'+key+'"]').append(tpl);

    });

    set_field_value( filtered_data );
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
                get_field_value(item.tag, item.key, item.val);
            });    
        });
    }

}

/**
 * Load values from local storage
 * @param {string} tag 
 * @param {string} key 
 * @param {string} val 
 */
function get_field_value(tag, key, val) {

    if( tag == 'input' ) {

        $('[name='+key+']').val(val);
        $('[name='+key+']').closest('.fencing-form-group').find('.fir-info span').text(val);
        
    } if( tag == 'select' ) {
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


function update_custom_fence_tab() {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),      
        modal_key = $('.fencing-container').attr('data-key'),
        mbn = $('.measurement-box-number').val(),
        custom_fence_tabs = localStorage.getItem('custom_fence-'+tab),
        info = fc_data[i];

    const data_tabs = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];

   
    var filtered_data_tabs = data_tabs.filter(function(item) {
        return item.tab != tab;
    });

    if( info == undefined ) {

        $('.fc-tab-title, .fc-tab-subtitle').html('');
        $('.js-fc-form-step').hide();
        $('.fsi-selected').removeClass('fsi-selected');

        return;
    }

    filtered_data_tabs.push({
        tab: tab,
        style: i,
        fence: info.slug,
        mbn: mbn,
        isCalculate: data_tabs[0]?.isCalculate || FENCES.defaultValues.measurement,
        calculateValue: data_tabs[0]?.calculateValue || FENCES.defaultValues.measurement
    });

    localStorage.setItem('custom_fence-'+tab, JSON.stringify(filtered_data_tabs));

    mesurement = $('.measurement-box-number').val();
    mesurement = parseInt(mesurement).toLocaleString();

    $('.fencing-tab-selected').find('.ftm-measurement').html( mesurement + ' mm' );

    $('.fc-tab-title').html('SECTION ' + (tab+1) );
    $('.fc-tab-subtitle').html( mesurement + ' - ' + info['title']);

    load_fencing_items();

}

function update_custom_fence(modal_key, fc_form_field = false) {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        mbn = $('.measurement-box-number').val(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i);

    let form_field = fc_form_field || $('.fc-form-field:visible');

    const data = custom_fence ? JSON.parse(custom_fence) : [];

    let itemKey = 'custom_fence-'+tab+'-'+i;
    
    if( modal_key === "left_side" || modal_key === "right_side" || modal_key === "post_options" || modal_key === "panel_options" || modal_key === "gate" ){
        modal_key = FENCES.activeSetting;
    }
    
    settings = form_field.map(function(){

        var key  = $(this).attr('name'),
            val  = $(this).val() ? $(this).val() : $(this).attr('value'),
            type = $(this).attr('type'),
            tag  = $(this).get(0).tagName.toLowerCase(),
            obj = {key:key, val:val, tag: tag, type: type};

        if( modal_key === "color_options"  ){
            obj.title = $(this).attr('data-title') || '';
            obj.subtitle = $(this).attr('data-subtitle') || '';
            obj.color_code = $(this).attr('data-color-code') || '';
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

    update_custom_fence_tab();

}

function mergeSettings(data, settings, key,  modal_key){

    //Check first if a control_key already exists and get it
    const find_existing_data = data.find(obj => obj[key] === modal_key);
    
    if( typeof find_existing_data !== "undefined" ){

        let merge_settings = [];

        find_existing_data.settings.forEach(obj => {
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


function update_custom_fence_gate() {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        modal_key = 'gate',
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i);

    const data = custom_fence ? JSON.parse(custom_fence) : [];

    placement = $('.fencing-panel-gate').prev().prev().prev().attr('data-id');
    placement = placement == undefined ? -1 : placement;


    var settings = {
        'placement' :  placement,
        'index' : $('.fencing-panel-gate').index(),
        'size' : 800,
        'unit' : 'mm'
    }

    var filtered_data = data.filter(function(item) {
        return item.control_key != modal_key;
    });

    if( $('.fencing-panel-gate').length ) {

        filtered_data.push({
            id: i, 
            control_key: modal_key, 
            settings: settings
        });

    } 

    localStorage.setItem('custom_fence-'+tab+'-'+i, JSON.stringify(filtered_data));


}


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

function add_new_fence_section() {

    $('.fencing-tab').eq(0).clone().appendTo(FENCES.el.tabArea);

    $('.fencing-tab').removeClass('fencing-tab-selected');
    $('.fencing-tab:last-child').addClass('fencing-tab-selected');

    $('.fencing-tab:last-child').find('.fencing-tab-number').html( $('.fencing-tab').length );

    $('.measurement-box-number').val(FENCES.defaultValues.measurement);

    update_custom_fence_style_item();

    $('.js-btn-delete-fence').show();

    $('.fc-tab-title, .fc-tab-subtitle').html('');
    $('.js-fc-form-step').hide();
    $('.fsi-selected').removeClass('fsi-selected');

    // Store section count
    localStorage.setItem('custom_fence-section', $('.fencing-tab').length);
}

function restore_items( remove_index ) {

    var last_tid = $('.fencing-tab:last-child').index();

    $( ".fencing-tab" ).each(function() {

        var tid = $( this ).index();


        if( remove_index <= tid ) {

            /*
            console.log('tid', tid);
            console.log('remove_index', remove_index);
            */

            var next_index = tid+1;

            form = JSON.parse(localStorage.getItem('custom_fence-'+next_index));
            settings = localStorage.getItem('custom_fence-'+next_index+'-'+form[0].style);

            /*
            console.log('form', form);
            console.log('settings', settings);
            */

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

function submit_fence_planner() {

    window.onbeforeunload = function() {}

    //Set some delay to make sure the local storage and the html markup are loaded
    setTimeout(function(){
        FENCES.cartItems.init();
    });

    var set_fc_data = [];
    var project_plans = JSON.parse(localStorage.getItem('project-plans'));
    var cart_items = JSON.parse(localStorage.getItem('cart_items'));

    $( ".fencing-tab" ).each(function() {

        var tid = $( this ).index();

        form = JSON.parse(localStorage.getItem('custom_fence-'+tid));
        settings = JSON.parse(localStorage.getItem('custom_fence-'+tid+'-'+form[0].style));

        form[0].style = form[0].style + 1;
        form[0].tab = form[0].tab + 1;

        set_fc_data.push({
            'form': form,
            'settings': settings
        }); 

    });

/*    $.post('submit.php', {data : JSON.stringify(set_fc_data)}, 
        function(data, status) {
            console.log(data);
    });*/

    var form = $('form')[0]; 
    var formData = new FormData(form);

    formData.set("fences", JSON.stringify(set_fc_data));

    formData.set("cart_items", JSON.stringify(cart_items));

    Object.entries(project_plans).forEach(([key, value]) => {
        if (typeof value === 'object') {
            value = JSON.stringify(value);
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
                console.log(response);
            } catch(err){

            } 
        }
    });

}



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
             step = step + 0.02;
        } else {
            step = step + 0.2;       
        }
    }

   

    if( zoom == 'out' ) {
        if( step <= 1 ) {
             step = step - 0.02;
        } else {
            step = step - 0.2;            
        }
    }
    
    document.querySelector('.js-fc-zoom-progress').textContent = Math.floor(step*100) + "%";

    if( step >= 1 ) {
        $('.fencing-panel-items').css({ 
    'padding-top': raked_panel_mt, 'zoom': step,   'max-height' : '200px'});
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

// $('.panel-post:not(.panel-no-post)').length
// $('.fencing-panel-gate').length
// $('.right_raked-panel .fencing-raked-panel').length
// $('.left_raked-panel .fencing-raked-panel').length

/*Panel - 1200H x 2400W
Panel Raked - 1300H x 1200W
Gate - 1200H x 970W
Post - 1300L - Base Plated
Post Covers
Brackets Set x4
Flexi Bracket x1
Hinge & Latch Kit
Fixing Kit - Dynabolts*/


/**
 * Get the color title and subtitle
 * and assign it to the closest fc-form-field
 * @param {string} _color_el 
 * @param {string} form_field 
 */
function getSelectedColorDetails(_color_el, form_field ){

    var getFormFieldKey = form_field.attr('data-key');
    var title = _color_el.attr('data-color-title');
    var subtitle = _color_el.attr('data-color-subtitle');
    var colorCode = _color_el.attr('data-color-code');

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
    let globalSetting = globalSettingObj['settings'];
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
    console.log(document.querySelector('.fc-section-loader-overlay').length);
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
  console.log(existingIndex);
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
function tabContainerScroll(_this) {
    let _tab_parent_class = FENCES.el.tabContainer;
    let _tab_content_class = '.js-fencing-tab-container-area';
    let _main_parent = $('.js-fencing-tabs-container');
    let _main_parent_width = _main_parent.width();
    let _width = _this.outerWidth(true);
    let _trigger_width = (_this.position().left + _width);

    if( _trigger_width >= _main_parent_width ) {
        _main_parent.addClass('enable-scroll');
        draggable(_tab_parent_class, _tab_content_class);
    }

    moveScrollPosition(_tab_parent_class, $(_tab_parent_class).prop('scrollWidth'));
}

/**
 * Move Horizontal scroll position
 * @param {string} _el 
 */
function moveScrollPosition(_el, _position) {
    console.log('s');
    $(_el).animate({scrollLeft:  _position}, 500);
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
    
    let getActiveTab = $('.fencing-tab-selected');
    let getActiveTabIndex = getActiveTab.index();
    let getPrevBtn = getActiveTab.prev();
    let getNextBtn = getActiveTab.next();

    getActiveTab.addClass('is-deleting');

    if( getActiveTab.is(':last-child') ){
        getPrevBtn.click();
    } else {
        getNextBtn.click();
    }

    $('.is-deleting').remove();

    // Store section count
    localStorage.setItem('custom_fence-section', $('.fencing-tab').length);
}

function refreshSectionTabIndex() {
    $('.fencing-tab-container .fencing-tab').each(function(index) {
        $(this).find('.fencing-tab-number').html( index+1 );
    });
}

function resetSectionsBlocks() {

    $('.fencing-style-item').removeClass('fsi-selected');
    $('.js-fc-form-step').removeAttr('style');

}

/**
 * Remove deleted section entry in local storage
 */
function deleteLocalStorageEntry(){

    //Get selected tab
    let getActiveTab = $('.fencing-tab-selected');

    //Get selected tab index
    let getActiveTabIndex = getActiveTab.index();

    //Find and delete all instance of it in local storage
    deleteAllEntriesBySubstring("custom_fence-" + getActiveTabIndex);

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
    if( custom_fence_tab.isCalculate ){
        //Set the mm field value
        $('.btn-fc-calculate').prev().find('input').val(custom_fence_tab.calculateValue);
        //Then trigger click into the calculate button to load section 3
        $('.btn-fc-calculate').click();
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
        
      console.log('index', index);

      // If no more matching keys are found, exit the loop
      if (index === -1) {
        break;
      }
  
      // Get the matching key and delete the entry
      const matchingKey = Object.keys(localStorage)[index];
      localStorage.removeItem(matchingKey);
  
    }

    console.log(localStorage);
  
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
function refreshLocalStorage(activeSectionIndex) {

    //Only get storage entries related to custom fence
    const totalEntries = countLocalStorageFenceKeys();

    //Iterate each entries
    for (let i = activeSectionIndex; i <= totalEntries; i++) {

        let newIndex = i - 1;

        //If -1, set value to 0
        if( newIndex == -1 ){
            newIndex = 0;
        }

        //Retrieve to the old key
        const oldKey = `custom_fence-${i}`;

        //Prepare the new key string format
        const newKey = `custom_fence-${newIndex}`;
        
        // Check if the oldKey exists in localStorage and update it
        if (localStorage.getItem(oldKey)) {

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
            const oldStyleKey = `custom_fence-${i}-${value[0].style}`;

            //Prepare new key string format
            const newStyleKey = `custom_fence-${newIndex}-${value[0].style}`;

            // Check if the old style Key exists in localStorage
            if (localStorage.getItem(oldStyleKey)) {

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


function countLocalStorageFenceKeys() {
    
    let count = 0;

    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);

        // Check if the key contains the substring "custom_fence"
        if (key.includes("custom_fence")) {
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
    let _delete_btn = $('.js-btn-delete-fence');

    if ( _remaining_tabs <= 2 ){
        _delete_btn.hide();
    }

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
    $(FENCES.el.measurementBox).val(FENCES.defaultValues.measurement);
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
const formDownload = document.getElementById("fc-download-form");
const projectPlanKey = "project-plans";

/**
 * Restore data from local storage when the page loads
 */
function restoreFormData() {
    const formData = JSON.parse(localStorage.getItem(projectPlanKey)) || {};
    for (const key in formData) {
      const input = submitModal.querySelector(`[name="${key}"]`);
      if (input) {
        if (input.type === "checkbox") {
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

    formFields.forEach(input => {
        if (input.type === "checkbox") {
            formData[input.name] = formData[input.name] || [];

            if (input.checked) {
                formData[input.name].push(input.value);
            }
             
        } else if (input.type === "radio") {

            if (input.checked) {
                formData[input.name] = input.value;
            }  

            if( $('[name="'+input.name+'"]').length == 1 ) {
                if (input.checked == false) {
                    formData[input.name] = '';
                }                
            } 

        } else {
            formData[input.name] = input.value;
        }
    });

    updateOrCreateObjectInLocalStorage(projectPlanKey, formData);
}

// Add event listeners TO form elements inside the submit-modal div
if( submitModal ){
    submitModal.addEventListener("input", saveFormData); 
    submitModal.addEventListener("change", saveFormData);
}


function updateOrCreateObjectInLocalStorage(key, newData) {
    // Check if the key already exists in localStorage
    if (localStorage.getItem(key)) {
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
  
  
function setActiveColor() {

    setTimeout(function(){
        if( document.querySelector('.fc-color-options') !== null ){
            let getColor = JSON.parse(localStorage.getItem('project-plans'));
            if( getColor){
                let colorParentEl = document.querySelector('.fc-color-options');
                let createPlanBtn = document.querySelector('.fc-step-4 .fc-btn-create-plan');
                colorParentEl.querySelector('[data-slug="'+getColor.color.value+'"]').classList.add('fc-selected');

                if( createPlanBtn ) {
                    createPlanBtn.removeAttribute('disabled');
                }
            }
        }
    }, 100);

}

setActiveColor();
