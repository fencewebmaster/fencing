function load_fencing_items() {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];

    $('.fencing-panel-container').html('');

    var calc = calculate_fences();

    for (let i = 0; i < calc.long_panel.count; i++) {

        var center_point = 50;
        
        mesurement = $('.measurement-box-number').val();

        var panel_number = i,
            panel_size = calc.long_panel.length,
            panel_unit = 'mm';

        var tpl = $('script[data-type="panel_item-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point)
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

        // $('.fencing-container').width()

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

    $('.fencing-panel-container').prepend('<div class="left_raked-panel raked-panel"></div>');
    $('.fencing-panel-container').append('<div class="right_raked-panel raked-panel"></div>');

    update_raked_panels(['left_raked', 'right_raked']);

    if( calc.offcut_panel.count && calc.offcut_panel.length ) {
        var tpl = $('script[data-type="offcut"]').text()
                                                 .replace(/{{count}}/gi, calc.offcut_panel.count)  
                                                 .replace(/{{group}}/gi, info.panel_group) 
                                                 .replace(/{{width}}/gi, calc.offcut_panel.length);     

        $('.fencing-panel-container').append(tpl);    

        $('.fencing-offcut').css({'width':calc.offcut_panel.length*0.10});
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

    console.log('filtered_settings', filtered_data);

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
                    console.log('panel_h', panel_h);
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

                $('.'+v+'-panel').html(tpl); 
            }

        }

        if( side_part == 'left' ) {
            $('.panel-post').first().addClass('panel-'+has_post);
            $('.fencing-panel-spacing-number').first().addClass(has_post);
        }

        if( side_part == 'right' ) {
            $('.panel-post').last().addClass('panel-'+has_post);
            $('.fencing-panel-spacing-number').last().addClass(has_post);
        }

    });

    // Left Panel post
    var left_panel_post = $('.left-panel-post.no-post span').text().replace('(', '').replace(')', '');
    $('.left-panel-post.no-post span').text('('+left_panel_post+')');
    
    // Right Panel Post
    var right_panel_post = $('.right-panel-post.no-post span').text().replace('(', '').replace(')', '');
    $('.right-panel-post.no-post span').text('('+right_panel_post+')');


    // Post Options
    var post_options_filtered_data = custom_fence.filter(function(item) {
        return item.control_key == 'post_options';
    });

    if( post_options_filtered_data.length ) {
        var post_options_setting = post_options_filtered_data[0]?.settings;
        $('.panel-post, .fencing-panel-spacing-number').addClass(post_options_setting[0]?.val);
    } else {
        // Get default post options
        var post_options_default = info.settings.post_options.fields[0].options.filter(function(item) {
            return item.default == true;
        });
        $('.panel-post, .fencing-panel-spacing-number').addClass(post_options_default[0].slug);           
    }

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

    $('.fencing-panel-gate').prepend('<span class="fc-gate-spacing fc-gate-left-spacing">20</span>')
                            .append('<span class="fc-gate-spacing fc-gate-right-spacing">20</span>');

                      
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

        // Reset value
        console.log( $('[name='+key+']').find('.fc-selected').length );
        if( $('[name='+key+']').find('.fc-selected').length ) {
            $('[name='+key+']').find('.fc-selected').removeClass('fc-selected'); 
        }
        console.log('val', val);
        $('[name='+key+']').attr('value', val);
        $('[name='+key+']').find('[data-slug="'+val+'"]').addClass('fc-selected');
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
    });

    localStorage.setItem('custom_fence-'+tab, JSON.stringify(filtered_data_tabs));

    mesurement = $('.measurement-box-number').val();
    mesurement = parseInt(mesurement).toLocaleString();

    $('.fencing-tab-selected').find('.ftm-measurement').html( mesurement + ' mm' );

    $('.fc-tab-title').html('SECTION ' + (tab+1) );
    $('.fc-tab-subtitle').html( mesurement + ' - ' + info['title']);

    load_fencing_items();



   //  get_stored_items();

    // console.log('tab: '+tab, '| index: '+i);

    // console.log('update_custom_fence_tab: '+tab, JSON.parse(localStorage.getItem('custom_fence-'+tab)) );

}

function update_custom_fence(modal_key, fc_form_field = false) {

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        mbn = $('.measurement-box-number').val(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i);

    let form_field = fc_form_field || $('.fc-form-field:visible');

    const data = custom_fence ? JSON.parse(custom_fence) : [];

    let itemKey = 'custom_fence-'+tab+'-'+i;
    
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

    var filtered_data = data.filter(function(item) {
        return item.control_key != modal_key;
    });

    filtered_data.push({
        id: i, 
        control_key: modal_key, 
        settings: settings
    });

    if( modal_key === "color_options" ){
        itemKey = 'custom_fence-global-setting';
        filtered_data = [];
        filtered_data.push({
            id: i, 
            control_key: modal_key, 
            settings: settings
        });
    }
    

    localStorage.setItem(itemKey, JSON.stringify(filtered_data));

    update_custom_fence_tab();

    // console.log('update_custom_fence: ', 'custom_fence-'+tab+'-'+i );

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

    $('.fencing-tab').eq(0).clone().appendTo('.fencing-tab-container');

    $('.fencing-tab').removeClass('fencing-tab-selected');
    $('.fencing-tab:last-child').addClass('fencing-tab-selected');
    $('.fencing-tab:last-child').find('.fencing-tab-number').html( $('.fencing-tab').length );

    $('.measurement-box-number').val(fc_mbn);

    update_custom_fence_style_item();

    $('.fencing-tabs-container').animate({scrollLeft: $('.fencing-tabs-container').prop('scrollWidth')}, 0);

    $('.js-btn-delete-fence').show();

    $('.fc-tab-title, .fc-tab-subtitle').html('');
    $('.js-fc-form-step').hide();
    $('.fsi-selected').removeClass('fsi-selected');
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

    var set_fc_data = [];

    $( ".fencing-tab" ).each(function() {

        var tid = $( this ).index();

        form = JSON.parse(localStorage.getItem('custom_fence-'+tid));
        settings = JSON.parse(localStorage.getItem('custom_fence-'+tid+'-'+form[0].style));

        form[0].style = form[0].style + 1;
        form[0].tab = form[0].tab + 1;

        set_fc_data.push({
            'form': form,
            'settings': settings,
        }); 

    });

/*    $.post('submit.php', {data : JSON.stringify(set_fc_data)}, 
        function(data, status) {
            console.log(data);
    });*/

    var form = $('form')[0]; 
    var formData = new FormData(form);

    formData.set("fences", JSON.stringify(set_fc_data));

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
        if( step <= 1 ) {
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
/*
    setTimeout(function(){
        $('.fencing-panel-items').css({'justify-content':''});
        if( $(".fencing-panel-items").prop('scrollWidth') <= $(".fencing-display-result").width() ) {
            $('.fencing-panel-items').css({'justify-content':'center'});
        }
    });*/

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