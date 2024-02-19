
function reload_fence_items() {

    var items = localStorage.getItem('custom_fence-section');

    for (let i = 0; i < items; i++) {

        var section = `<div class="border p-3 rounded mb-4 mx-2">
            <div class="row align-items-center mb-3">
                <div class="col fw-bold">
                    SECTION ${i+1}  
                </div>
                <div class="col-auto text-end">
                    <a href="${base_url}?section=${i+1}" class="btn btn-sm text-uppercase btn-orange px-3">
                        <i class="fa-solid fa-pencil me-1"></i>
                        <span>Edit Details</span>
                    </a>    
                </div>
            </div>
            <div class="plan-item">
                <div id="pp-${i}" class="dl-row"><div class="fc-result"><div class="fencing-panel-container"></div></div></div>
            </div>
        </div> `;

        $('#fc-fence-list').append(section);

        if( (i+1) % 3 === 0 & i != 1 ) {
            $('#fc-fence-list').append(`<div style="dl-page-separator"></div>`);
        }

       reload_load_fencing_items(i);

       load_center_point(i);

       if( $('#pp-'+i+' .panel-post').hasClass('raked-panel-post') ) {
           $('#pp-'+i).addClass('has-raked');
       }
    }


}
reload_fence_items();

function load_center_point(tab) {

    var defaultCenterPoint = 25;
    var defaultRakedWidth = 1200;
    var gateGap = 20 + 20;

    var custom_fence_tab = localStorage.getItem('custom_fence-'+tab),
        custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [],
        i = custom_fence_tab[0]?.style,
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [];


    var overall = `<div class="fc-overall">${(custom_fence_tab[0]?.calculateValue).toLocaleString()} Overall</div>`;

    var first_point = `<span class="fc-start-c-p">${defaultCenterPoint}</span>`;

    gate_size = 970 + 50 + gateGap;

    // Check if the gate is on the first order
    if( $('#pp-'+tab+' .fencing-panel-gate').prev().prev().index() == 1 ) {
        var gate = `<div class="fc-center-point">
            <span class="fc-div-c-p"></span>
            ${gate_size}<br>
            Centers
        </div>`;
    } else {
        var gate = `<div class="fc-center-point fc-last-c-p">
            <span class="fc-div-c-p"></span>
            <span class="fc-div-c-p"></span>
            ${gate_size}<br>
            Centers
        </div>`;
    }


    var last_point = `<span class="fc-div-c-p"></span>
        <span class="fc-div-c-p"></span>
        <span class="fc-end-c-p">${defaultCenterPoint}</span><br>Centers`;


    if( !$('#pp-'+tab+' .right_raked-panel .fencing-raked-panel').length && $('#pp-'+tab+' .post-right.panel-no-post').prev().prev().attr('data-key') != 'gate' ) {

        if( !$('#pp-'+tab+' .panel-item').last().next().next().next().hasClass('fencing-panel-gate') ) {
            $('#pp-'+tab+' .panel-item').last().find('.fc-center-point').addClass('fc-last-c-p');
        }


        if( $('#pp-'+tab+' .right-panel-post.no-post').length ) {
            $('#pp-'+tab+' .fc-last-c-p').addClass('cp_no-post--right');
        }

        $('#pp-'+tab+' .fc-last-c-p').prepend(`<span class="fc-end-c-p">${defaultCenterPoint}</span><span class="fc-div-c-p"></span>`);
    

    } 

    $('#pp-'+tab+' .fc-result').append(overall);

    $('#pp-'+tab+' .fencing-panel-gate').append(gate);


    // If the gate is in the last order
    if( $('#pp-'+tab+' .panel-item').last().next().next().next().hasClass('fencing-panel-gate') ) {
       $('#pp-'+tab+' .fc-last-c-p').append(`<span class="fc-end-c-p">${defaultCenterPoint}</span>`);
    }

    if( $('#pp-'+tab+' .post-left.panel-no-post').length ) {
        
       $('#pp-'+tab+' .panel-item').first().find('.fc-center-point').addClass('fc-first-c-p');
       
       // If there is no post and the next item is gate
       if( $('#pp-'+tab+' .post-left.panel-no-post').next().attr('data-key') == 'gate' ) {
            $('#pp-'+tab+' .fencing-panel-gate').find('.fc-center-point').addClass('cp_no-post--left');
            $('#pp-'+tab+' .cp_no-post--left').append(`<span class="fc-start-c-p">${defaultCenterPoint}</span><span class="fc-div-c-p"></span>`);
       } else {
           $('#pp-'+tab+' .fc-first-c-p').addClass('cp_no-post--left');
       }

    }

    if( $('#pp-'+tab+' .post-right.panel-no-post').length ) {

       // If there is no post and the previous item is gate
       if( $('#pp-'+tab+' .post-right.panel-no-post').prev().prev().attr('data-key') == 'gate' ) {

            $('#pp-'+tab+' .fencing-panel-gate').find('.fc-center-point').addClass('fc-last-c-p cp_no-post--right');
            $('#pp-'+tab+' .cp_no-post--right').prepend(`<span class="fc-end-c-p">${defaultCenterPoint}</span><span class="fc-div-c-p"></span>`);
    
       } else {
            $('#pp-'+tab+' .fc-last-c-p').addClass('cp_no-post--right');
       }

    }
      

    if( $('#pp-'+tab+' .fencing-panel-gate').length && ! $('#pp-'+tab+' .left_raked-panel .fencing-raked-panel').length  ) {
      
        // If there is a gate and no step up & gate is not in the first order
        if( $('#pp-'+tab+' .fencing-panel-gate .fc-center-point .fc-start-c-p').length == 0 && $('#pp-'+tab+' .panel-item').first().prev().prev().prev().hasClass('fencing-panel-gate') ) {            
            $('#pp-'+tab+' .fencing-panel-gate').find('.fc-div-c-p').after(first_point);
        }
    
    } 

    if( ! $('#pp-'+tab+' .left_raked-panel .fencing-raked-panel').length ) {
        // If there is no step up
    } 
    
    // If gate is not in the first order
    if( ! $('#pp-'+tab+' .panel-item').first().prev().prev().prev().hasClass('fencing-panel-gate') ) {
        $('#pp-'+tab+' .panel-item').first().find('.fc-div-c-p').after(first_point);
    }
}



function reload_load_fencing_items(tab) {


    var custom_fence_tab = localStorage.getItem('custom_fence-'+tab),
        custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [],
        i = custom_fence_tab[0]?.style,
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];


    $(`#pp-${tab} .fencing-panel-container`).html('');

    var cf_data = {item:i,tab:tab}
    var calc = calculate_fences(cf_data);

    if( !calc ){
        return;
    }

    var center_point = 50;

    for (let i = 0; i < calc.long_panel.count; i++) {

 
        
        mesurement = $('.measurement-box-number').val();

        var panel_number = i,
            panel_size = calc.long_panel.length,
            panel_unit = 'mm',
            data_key = "post_options";

        var tpl = $('script[data-type="panel_item-'+info.panel_group+'"]').text()
                                                     .replace(/{{data_key}}/gi, center_point)
                                                     .replace(/{{center_point}}/gi, center_point)
                                                     .replace(/{{panel_size}}/gi, panel_size+'W')
                                                     .replace(/{{panel_size_center}}/gi, (panel_size+center_point)+'W')
                                                     .replace(/{{panel_unit}}/gi, '<br>PANEL')
                                                     .replace(/{{panel_number}}/gi, panel_number);    
    
        $(`#pp-${tab} .fencing-panel-container`).append(tpl);
  
        $('#pp-'+tab+' .fencing-panel-item').css({'width':panel_size*0.10});
    }  

    var tpl = $('script[data-type="panel_spacing-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point);


    $(`#pp-${tab} .fencing-panel-container`).append(tpl);

    if( calc.short_panel.count ) {

    for (let i = 0; i < 1; i++) {

        var panel_number = i,
            panel_size = calc.short_panel.length,
            panel_unit = 'mm';
   
        var tpl = $('script[data-type="short_panel_item-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point)
                                                     .replace(/{{panel_size}}/gi, panel_size+'W')
                                                     .replace(/{{panel_unit}}/gi, '<br>PANEL')
                                                     .replace(/{{panel_number}}/gi, panel_number)
                                                     .replace(/{{panel_size_center}}/gi, (panel_size+center_point)+'W');   
                                         
    
        $(`#pp-${tab} .fencing-panel-container`).append(tpl);

        $('#pp-'+tab+' .short-panel-item').attr('data-id', calc.long_panel.count+1)
                              .attr('id', 'panel-item-'+(calc.long_panel.count+1))
                              .css({'width':panel_size*0.10});

    }  

    var tpl = $('script[data-type="panel_spacing-'+info.panel_group+'"]').text()
                                                     .replace(/{{center_point}}/gi, center_point);

    $(`#pp-${tab} .fencing-panel-container`).append(tpl);

    }

    re_update_gate('edit', tab);

    // $('.fpsn-b:not(:first-child):not(:last-child)').remove();

    $(`#pp-${tab} .fencing-panel-container`).prepend('<div class="left_raked-panel raked-panel"></div>');
    $(`#pp-${tab} .fencing-panel-container`).append('<div class="right_raked-panel raked-panel"></div>');

    re_update_raked_panels(['left_raked', 'right_raked'], tab);

    if( calc.offcut_panel.count && calc.offcut_panel.length ) {
        var tpl = $('script[data-type="offcut"]').text()
                                                 .replace(/{{count}}/gi, calc.offcut_panel.count)  
                                                 .replace(/{{group}}/gi, info.panel_group) 
                                                 .replace(/{{width}}/gi, calc.offcut_panel.length);     

        $(`#pp-${tab} .fencing-panel-container`).append(tpl);    

        $('#pp-'+tab+' .fencing-offcut').css({'width':calc.offcut_panel.length*0.10});
    }

    //updateFirstFencingPost();
    //updateLastFencingPost();
}





function re_update_gate(action, tab) {

    var custom_fence_tab = localStorage.getItem('custom_fence-'+tab),
        custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [],
        i = custom_fence_tab[0]?.style,
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

    var cf_data = {item:i,tab:tab}
    var calc = calculate_fences(cf_data);

    var panel_size = calc.gate.length,
        panel_unit = 'mm',
        gate_size  = calc.gate.length;

    if( action == 'add' || action == 'edit' ) {

        if( placement == -1 ) {

           var tpl = $('script[data-type="panel_gate-'+info.panel_group+'-r"]').text()
                                                         .replace(/{{center_point}}/gi, center_point)
                                                         .replace(/{{panel_size}}/gi, gate_size)
                                                         .replace(/{{panel_unit}}/gi, panel_unit);  

            $('#pp-'+tab+' #panel-item-0').before(tpl);

            $('#pp-'+tab+' #btn-gate').html('Edit Gate');

        } 


        if( find_gate.length && placement >= 0  || action == 'add' && placement == 0 ) {

            var tpl = $('script[data-type="panel_gate-'+info.panel_group+'-l"]').text()
                                                         .replace(/{{center_point}}/gi, center_point)
                                                         .replace(/{{panel_size}}/gi, gate_size)
                                                         .replace(/{{panel_unit}}/gi, panel_unit);  

            $('#pp-'+tab+' #panel-item-'+placement).after(tpl);    

            $('#pp-'+tab+' #btn-gate').html('Edit Gate');
        }                                             

    }

    $('#pp-'+tab+' .fencing-panel-gate').prepend('<span class="fc-gate-spacing fc-gate-left-spacing">20</span>')
                            .append('<span class="fc-gate-spacing fc-gate-right-spacing">20</span>');

                      
}



function re_update_raked_panels(side, tab) {

    var custom_fence_tab = localStorage.getItem('custom_fence-'+tab),
        custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [],
        i = custom_fence_tab[0]?.style,
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];

    var filtered_data = custom_fence.filter(function(item) {
        return item.control_key == 'add_step_up_panels';
    });

    var settings = filtered_data[0]?.settings;

    var cf_data = {item:i,tab:tab}
    var calc = calculate_fences(cf_data);

    var defaultCenterPoint = 25;

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
                var has_post = 'no-post '+side_part+'-panel-post '+has_post;
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
                                                                .replace(/{{panel_size}}/gi, panel_h)
                                                                .replace(/{{panel_unit}}/gi,  panel_w)
                                                                .replace(/{{panel_unit_center}}/gi, panel_w+center_point)
                                                                .replace(/{{post}}/gi, has_post)
                                                                .replace(/{{center_post}}/gi, defaultCenterPoint);   
                                                                

                if( typeof panel_h !== "undefined"){
                    $('#pp-'+tab+' .'+v+'-panel').html(tpl);    
                }
                
            }

        }

        if( side_part == 'left' ) {
            $('#pp-'+tab+' .panel-post:not(.post-left):not(.post-right)').first()
                                                                         .addClass('post-left panel-'+has_post)
                                                                         .attr('data-key',"left_side")
                                                                         .attr('post-side',"post_left");

            $('#pp-'+tab+' .fencing-panel-spacing-number').first().addClass(has_post);
        }

        if( side_part == 'right' ) {
            $('#pp-'+tab+' .panel-post:not(.post-left):not(.post-right)').last()
                                                                         .addClass('post-right panel-'+has_post)
                                                                         .attr('data-key',"right_side")
                                                                         .attr('post-side',"post_right");

            $('#pp-'+tab+' .fencing-panel-spacing-number').last().addClass(has_post);
        }

    });

    // Left Panel post
    var left_panel_post = $('#pp-'+tab+' .left-panel-post.no-post span').text().replace('(', '').replace(')', '');
    $('#pp-'+tab+' .left-panel-post.no-post span').text('('+left_panel_post+')');

    // Right Panel Post
    var right_panel_post = $('#pp-'+tab+' .right-panel-post.no-post span').text().replace('(', '').replace(')', '');
    $('#pp-'+tab+' .right-panel-post.no-post span').text('('+right_panel_post+')');

    $('.no-post-swivel-bracket span').after('<span class="sw sw-top">SW</span><span class="sw sw-bot">SW</span>');    
    
    load_post_options_first_last_values(custom_fence, info, tab);

    load_post_options_all(custom_fence, info, tab);


    $('#pp-'+tab+' .fc-result').css({'padding': ''});

    if( $('#pp-'+tab+' .raked-panel .fencing-raked-panel').length && $('#pp-'+tab+' .fc-result').css('margin-top') != '70px' ) {   
        $('#pp-'+tab+' .fc-result').css({'padding-top': '40px'});
    } else {
        $('#pp-'+tab+' .fc-result').css({'margin-top': ''});        
    }

    $('#pp-'+tab+' .raked-panel .fencing-panel-item').css({'width':1200*0.10});

/*    setTimeout(function(){
        $('.fencing-panel-items').css({'justify-content':''});
        if( $(".fencing-panel-items").prop('scrollWidth') <= $(".fc-result").width() ) {
            $('.fencing-panel-items').css({'justify-content':'center'});
        }
    }, 100);*/

}


// savePlanner();

