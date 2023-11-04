$(document).on("keypress", ".numeric", function( event ){
    if(event.which != 13 && event.which != 8) {
        if(event.which < 46 || event.which >= 58 || event.which == 47) {
            event.preventDefault();
        }
        if(event.which == 46 && $(this).val().indexOf('.') != -1) {
            event.preventDefault();
        }
    }
});

$(document).on('click', '.fencing-style-item', function(){

    var i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index();

    $('.fencing-style-item').removeClass('fsi-selected');
    $(this).addClass('fsi-selected');

    /* Reset items */
    // localStorage.setItem('custom_fence-'+tab+'-'+i, '');

    load_fencing_items();

    $('.js-fc-form-step[data-section="2"]').fadeIn(200);


    setTimeout(function(){
        $('.fc-btn-next-step').attr('disabled', 'disabled');
        if( $('.fencing-panel-item:visible').length > 0 ) {
            $('.fc-btn-next-step').removeAttr('disabled');
        }
    }, 100);


});


$(document).on('click', '#btn-gate', function(e){
    e.preventDefault();

    if( $(this).text().toLowerCase().indexOf('edit') > -1 ) {
        return;
    }

    update_gate('add');
    
    update_custom_fence_gate();

    setTimeout(function(){

        if( $('.fencing-panel-gate').length == 0 ) {

            var calc = calculate_fences(),
                gate = calc.gate.length,
                post = 50,
                gate_spacing = 40,
                length = $('.measurement-box-number');

            length.val( parseInt(length.val()) + calc.gate.length + post + gate_spacing);

            $('.btn-fc-calculate').click();
        }

    });

    $('.btn-fc-calculate').click();

});


$(document).on('click', '.fc-move-post', function(){

    var move = $(this).data('move'),
        gate = $('.fencing-panel-gate');

    if( move == 'left' ) {

        closest_id = gate.prev().prev().prev().attr('id');

        if( $('.fencing-panel-gate').index() == 1 || closest_id == undefined ) {
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

        var closest_id = $('.panel-item').length-1,
            last_id = $('.panel-item').last().attr('data-id');

        if( $('.right_raked-panel .fencing-raked-panel').length ) {            
            last_id = $('.panel-item').last().attr('data-id')-1;
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

        $('#btn-gate').html('Add Gate');
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
            $('.btn-fc-calculate').click();            
        });

    }

    update_custom_fence_gate();

    $(".fencing-display-result").scrollCenter(".fencing-panel-gate", 300);

});



$(document).on('click', '.fencing-qty-plus', function(){
    var input = $('.measurement-box-number'),
        val = input.val();
        input.val( parseInt(val) + 1 );
});

$(document).on('click', '.fencing-qty-minus', function(){
    var input = $('.measurement-box-number'),
        val = input.val();
        if( val > 1) {
            input.val( parseInt(val) - 1 );
        }
});


$(document).on('change', '.fc-select-option', function(){
    var slug = $(this).val();
    $(this).parent().attr('value', slug);

    var modal_key = $('.fencing-container').attr('data-key');

    if( $(this).attr('data-key') && $(this).attr('data-key') !== "undefined" ){
        modal_key = $(this).attr('data-key');
    }
    
    update_custom_fence(modal_key);

    if( $(this).closest('.fc-form-field').attr('name') == 'right_raked' ) {   
        $(".fencing-display-result").scrollCenter(".panel-post:last", 300);
    }

    if( $(this).closest('.fc-form-field').attr('name') == 'left_raked' ) {   
        $(".fencing-display-result").scrollCenter(".panel-post:first", 300);
    }

    if( $(this).parents('.js-fencing-modal').length ){
        FCModal.close();
    }

});

$(document).on('click', '.fc-select-post, .fc-select-item', function(){
    var slug = $(this).attr('data-slug');
    var getFormField = $(this).closest('.fc-form-field');

    $(this).closest('.fencing-form-group').find('.fc-select').removeClass('fc-selected');
    $(this).addClass('fc-selected');
    getFormField.attr('value', slug);

    getSelectedColorDetails($(this), getFormField);

    if( $('.fencing-container').attr('data-key') == 'right_side' ) {   
        $(".fencing-display-result").scrollCenter(".panel-post:last", 300);
    }

    if( $('.fencing-container').attr('data-key') == 'left_side' ) {   
        $(".fencing-display-result").scrollCenter(".panel-post:first", 300);
    }

});

$(document).on('click', '.fencing-modal-content', function(e) {
  e.stopPropagation();
});

$(document).on('click', '.fencing-tab', function() {

    $('.fencing-tab').removeClass('fencing-tab-selected');

    $(this).addClass('fencing-tab-selected');

    var tab = $('.fencing-tab.fencing-tab-selected').index(),
        modal_key = $('.fencing-container').attr('data-key'),
        custom_fence_tabs = localStorage.getItem('custom_fence-'+tab);

    const custom_fence_tab = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];

    resetSectionsBlocks();

    if( custom_fence_tab.length > 0 ){

        $('.fencing-style-item').eq(custom_fence_tab[0]?.style).addClass('fsi-selected');

        var measurement = custom_fence_tab[0]?.mbn ? custom_fence_tab[0].mbn : FENCES.defaultValues.measurement;
        $('.measurement-box-number').val(measurement);

        update_custom_fence_tab();

        $('.fsi-selected').click();

        loadStep3(custom_fence_tab[0]);

    }

});

$(document).on('click', '.fencing-modal .fc-select', function() {
    FCModal.close();
    $('.fc-btn-active').removeClass('fc-btn-active');
});

$(document).on('click', '#fc-control-modal', function() {
    FCModal.close();
    $('.fc-btn-active').removeClass('fc-btn-active');
});

$(document).keydown(function(e){
   var code = e.keyCode || e.which;
   
   if( code == 27 ) {
        FCModal.close();
        $('.fc-btn-active').removeClass('fc-btn-active');        
   }

});

// $("#edit-gate-modal").fadeIn('fast');

$(document).on('click', '.fencing-tab-add', function(e) {
    e.preventDefault();

    add_new_fence_section();

/*    if( $(this).hasClass('fc-tab-add') ) {
        $('html, body').animate({
            scrollTop: $(".fencing-container").offset().top
        }, 1);        
    }*/

    tabContainerScroll($(this));
    
});


$(document).on('click', '.fencing-style-item', function() {
    
    update_custom_fence_style_item();

});

$(document).on('click', '.js-btn-delete-fence', function(){

    let getActiveTab = $('.fencing-tab-selected');
    let getActiveTabIndex = getActiveTab.index();

    hideDeleteSectionBtn();

    stopSectionDeletion();

    deleteSectionTab();

    refreshSectionTabIndex();
    
    deleteLocalStorageEntry();
    
    refreshLocalStorage(getActiveTabIndex);

});


$(document).on('change', '.fc-select-option', function(event){

    var side = ['left_raked', 'right_raked'];

    setTimeout(function(){
        $('.raked-panel').html('');
        update_raked_panels( side ); 

        // raked panel
        if( $('.fencing-raked-panel').length && $(".fencing-panel-spacing-number:contains('undefined')").length ) {

            var calc   = calculate_fences(),
                raked  = 1200,
                post   = 50,
                length = $('.measurement-box-number');

            length.val( parseInt(length.val()) + raked + post);

            $('.btn-fc-calculate').click();
        }

    });    
     
    

});

$(document).on('click', '.fencing-btn-modal', function(event){

    if( ! $(this).hasClass('fencing-btn-modal') ) 
        return false;

    let modal = {
        el : '',
        content : '.fencing-modal-content',
        body: '.fencing-modal-body'
    };

    //Button Data Information
    var target = $(this).data('target'),
        key = $(this).data('key'),
        i = $('.fencing-style-item.fsi-selected').index(),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence = localStorage.getItem('custom_fence-'+tab+'-'+i),
        custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
        info = fc_data[i];

    modal.el = $(target);
    modal.content = modal.el.find(modal.content);
    modal.body = modal.el.find(modal.body);
    FCModal.open(target);

    $(this).addClass('fc-btn-active');
   
    if( typeof key !== "undefined" ){
        modal.el.find(".fencing-modal-notes").html('');
        modal.el.find(".fencing-modal-content").html('');
    }

    FENCES.setActiveSetting(key);

    var fields = info?.settings[key]?.fields;

    $.each(fields, function(k, v){

        var tpl = $('script[data-type="'+v.type+'"]').text()
                                                     .replace(/{{field_title}}/gi, v.title)
                                                     .replace(/{{title}}/gi, v.label)
                                                     .replace(/{{image}}/gi, v.image)
                                                     .replace(/{{default}}/gi, v.default)
                                                     .replace(/{{unit}}/gi, v.unit)
                                                     .replace(/{{field_name}}/gi, v.slug)
                                                     .replace(/{{type}}/gi, v.type)
                                                     .replace(/{{sub_default}}/gi, v?.weight?.default)
                                                     .replace(/{{sub_unit}}/gi, v?.weight?.unit)
                                                     .split(/\$\{(.+?)\}/g);

        modal.content.append(tpl);

        if( v.type == 'dropdown_option') {

            $(v.options).each(function(i, o) {
                $('[name="'+v.slug+'"] select').append($('<option>',{ value: o.slug, text: o.title }));
                $('[name="'+v.slug+'"] select').attr('data-key', v.key);
            });

        }

        if( v.type == 'range_option') {

            const Item = ({ image, title, slug, key }) => `
            <div class="fc-col-4 fc-text-center">
                <div class="fc-select-post fc-select" data-key="${key}" data-slug="${slug}">    
                    <img src="${image}">
                </div>
                <p>${title}</p>
            </div>`;

            modal.el.find('[data-field="range_option"] .fc-row').html(v.options.map(Item).join(''));
        }

        if( v.type == 'text_option') {

            const Item = ({ title, slug }) => `
            <div class="fc-col-4 fc-text-center">
                <div class="fc-select-post fc-select" data-key="${key}" data-slug="${slug}">    
                    <p>${title}</p>
                </div>
            </div>`;

            modal.el.find('[data-field="text_option"] .fc-row').html(v.options.map(Item).join(''));
        }

        if( v.type == 'image_option') {

            const Item = ({ image, title, extra, slug, key }) => `
            <div class="fc-col-4 fc-text-center">
                <div class="fc-select-post fc-select" data-key="${key}" data-slug="${slug}">
                    <img src="${image}" class="fc-fullwidth">        
                </div>
                <p>${title}</p>
                <p>${extra}</p>
            </div>`;

            modal.el.find('[data-field="image_option"] .fc-row').html(v.options.map(Item).join(''));
        }

        addNotesOrInfo(modal, v);

        // GET/SET DEFAULT VALUE
        var default_value = v.options?.filter(function(item) {
            return item.default == true;
        });

        if( default_value ) {
            var opt = default_value[0],
                tag = $('.fc-form-field').get(0).tagName.toLowerCase();

            get_field_value(tag, v?.slug, opt?.slug);                        
        }
        

    });

    //Get data based on key
    var filtered_data = custom_fence.filter(function(item) {
        return item.control_key == key;
    });

    removeDuplicateCloseBtn();
    set_field_value( filtered_data );

    if( target === "#submit-modal" ) {
        // Restore form data when the page loads
        restoreFormData();
    }

});


/**
 * Add Notes or Info if value exists in array
 */
function addNotesOrInfo(modal, v) {

    var details = v.info;
    var notes = v.notes;

    
    
    if( details || notes ){

   
        if( details ) {
            const Item = ({ title, description }) => `<div class="fc-selection-details">
                    <label>${title}</label>
                    <p>${description}</p>
                </div>`;
    
            modal.el.find('.fencing-modal-notes').append( details.map(Item).join('') );
        }
       
        if( notes ) {
            var notes_html =  `<div class="fc-selection-details fc-alert-gray">
                    <label><i class="fc-icon fc-icon-capa"></i> ${notes.title}</label>
                    <p class="fc-text-gray">${notes.description}</p>
                </div>`;
    
            modal.el.find('.fencing-modal-notes').append( notes_html );
        }
    }

}

/**
 * @TODO - This is a temporary solution
 */
function removeDuplicateCloseBtn() {
    $('.fencing-modal-area ~ .fencing-modal-area .fencing-modal-close').remove();
}

$(document).on('keypress', '.measurement-box-number', function(e){
    if(event.which == 13) {
        $('.btn-fc-calculate').click();
    }
});


$(document).on('keyup', '.measurement-box-number', function(){

    var min = 300,
        max = 100000;

    $(this).closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');
    $('.btn-fc-calculate').removeAttr('disabled');

    if( $(this).val() < min || $(this).val() > max ) {

        if( $(this).val() < min  ) { 
            var alert = ' Invalid '+min+'mm Min';
        } 

        if( $(this).val() > max  ) { 
            var alert = ' Invalid '+max+'mm max';
        } 

        $(this).closest('.fc-input-container').find('.fc-input-msg').addClass('fcim-show').html(alert);

        $('.btn-fc-calculate').attr('disabled', 'disabled');

        // 2nd validation
        var alert = [];

        if( $('.fencing-panel-gate').length ) {
            alert.push('gate');
        }
        if( $('.fencing-raked-panel').length ) {
            alert.push('step up');
        }

        var alert_msg = 'Invalid, remove '+alert.join(' or ')+' first';

        if( alert.length ) {
            $('.measurement-box-number').closest('.fc-input-container').find('.fc-input-msg').addClass('fcim-show').html(alert_msg);
        }

    }


});



$(document).on('click', '.btn-fc-calculate', function(){

    var box = $('.measurement-box-number'),
        last = box.attr('data-last'),
        length = parseInt(box.val()),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence_tabs = localStorage.getItem('custom_fence-'+tab),
        custom_fence_tab = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];

    box.attr('data-last', box.val());


    /*
    // Use only on database query
    $('.fencing-calculating').show();
    $('.fencing-panel-items').hide();

    setTimeout(function(){
        $.get('/', function(){
            $('.fencing-panel-items').show();
            $('.fencing-calculating').hide();
        });
    }, 1000);
    */



    update_custom_fence_tab();

    load_fencing_items();

    $('.js-fc-form-step[data-section="3"]').fadeIn(200);
    $('.fencing-tabs-container').show();

    setTimeout(function(){
        

        $('.fc-btn-next-step').attr('disabled', 'disabled');

        //@TODO - checkback to refactor
        // if( $('.fencing-panel-item:visible').length > 0 ) {
        //     $('.fc-btn-next-step').removeAttr('disabled');
        // }
        $('.fc-btn-next-step').removeAttr('disabled');

        
        if( $(".fencing-panel-spacing-number:contains('undefined')").length ) {
            box.val( last );
            $('.btn-fc-calculate').one();

            var alert = [];

            if( $('.fencing-panel-gate').length ) {
                alert.push('gate');
            }
            if( $('.fencing-raked-panel').length ) {
                alert.push('step up');
            }

            if( $('.fencing-raked-panel').length || $('.fencing-panel-gate').length ) {
                var alert_msg = 'Invalid, remove '+alert.join(' or ')+' first';

                $('.measurement-box-number').closest('.fc-input-container').find('.fc-input-msg').addClass('fcim-show').html( alert_msg );
            }

            setTimeout(function(){
                $('.fc-input-msg').removeClass('fcim-show').html('');
            }, 5000);    
        }

    }, 200);

    
    custom_fence_tab[0].isCalculate = 1;
    custom_fence_tab[0].calculateValue = length;
    localStorage.setItem('custom_fence-'+tab, JSON.stringify(custom_fence_tab));

    window.onbeforeunload = function() {
        return false;
    }
});


/* Input Range */
$(document).on('click', '.fir-minus', function() {
    zoom(this, "out");
});

$(document).on('click', '.fir-plus', function() {
    zoom(this, "in");
});

$(document).on('input change', '.fencing-input-range input', function(event) {

    $(this).closest('.fencing-input-range').find('.fir-info span').text( $(event.currentTarget).val() );

     $(this).closest('.fencing-input-range').find('.fir-info-sub span').text( 72 + 1.8 );
});

$(document).on('change', '.fencing-modal', function(e){

    //Only need to run this if modal content is for fences
    if( e.currentTarget.id !== "submit-modal"){
        var modal_key = $('.fencing-container').attr('data-key');
        update_custom_fence(modal_key);
    }
    
});

$(document).on('click', '.fc-select-post', function(){
    var modal_key = $('.fencing-container').attr('data-key');
    var fc_form_field = $(this).closest(".fc-form-field");

    if( $(this).attr('data-key') && $(this).attr('data-key') !== "undefined" ){
        modal_key = $(this).attr('data-key');
    }
    
    update_custom_fence(modal_key);
});

$(document).on('click', '.fc-select-item', function(){
    var modal_key = $(this).closest('.fc-row').attr('data-key');
    update_custom_fence(modal_key);
});


$(document).on('click', '.fc-btn-form-step', function(){

    if( ! $(this).hasClass('fc-btn-next') ) {
        var move = $(this).attr('data-move');

        $('.fc-form-plan').hide();
        $('[data-formtab="'+move+'"]').show();
    } 

    /*
    $(this).closest('form').find('[type="submit"]')
                           .addClass('fc-btn-disabled')
                           .attr('disabled', 'disabled');

    if( $('.fc-form-check-img input:checked').length ) {
        $(this).closest('form').find('[type="submit"]')
                               .removeClass('fc-btn-disabled')
                               .removeAttr('disabled');
    }
    */

});


$(document).on('click', '.fc-btn-next', function(){
    $(this).closest('form').submit();
});


$(document).on('click', '.fc-btn-step', function(){
    var tab = $(this).attr('tab'),
        move = $(this).attr('data-move');

    $('.fc-section-step').hide();
    $('[data-tab="'+move+'"]').show();

    $('.fencing-container').removeClass('w-side-section');
    if( move < 2 ) {
        $('.fencing-container').addClass('w-side-section');
    }

});

$(document).on('click', '.form-tab-4 input[type="checkbox"], .form-tab-4 input[type="radio"]', function(){

    var type = $(this).attr('type');

    if( type == 'checkbox' ) {
        $('.fc-form-check-empty').removeClass('fc-selected');
        $(this).closest('.fc-row-container').find('input[type="radio"]').prop('checked', false);
    } else {
        $('.fc-form-check-img').removeClass('fc-selected');
        $(this).closest('.fc-row-container').find('input[type="checkbox"]').prop('checked', false);
    }

    var check = $(this).is(':checked');

    $(this).closest('.fc-form-check-img').removeClass('fc-selected');

    if( check ) {
        $(this).closest('.fc-form-check-img').addClass('fc-selected');
    }

    /*
    $(this).closest('form').find('[type="submit"]')
                           .addClass('fc-btn-disabled')
                           .attr('disabled', 'disabled');

    if( $('.fc-form-check-img input:checked').length ) {
        $(this).closest('form').find('[type="submit"]')
                               .removeClass('fc-btn-disabled')
                               .removeAttr('disabled');
    }
    */

});
/*
$(document).on('keydown', '.fencing-modal', function (e) {
    if(e.which == 13) {

        var move = $('.fc-form-plan:visible').find('.fc-btn-next').attr('data-move');

        if( $('.fc-form-control.error').length == 0 ) {
            $('.fc-form-plan').hide();
            $('[data-formtab="'+move+'"]').show();    
        }

    }
});*/


$(document).on('click', '[name="color_options"]', function(e) {
    $('[name="color[value]"]').val( $(this).attr('value') );
    $('.fc-btn-create-plan').removeAttr('disabled');
});

$(document).on('keypress', '.no-enter', function(e) {
    if (e.keyCode == 13) {
        event.preventDefault();
    }
});

$(document).on('keypress', '.no-space', function(e) {
    if (e.keyCode == 32) {
        event.preventDefault();
    }
});


$(document).on('click', '.js-fc-zoom-reset', function(e){
    e.preventDefault();

    zooming('reset');

});

var step = 1;

$(document).on('click', '.fc-zoom-fence', function(e){
    e.preventDefault();

    var zoom = $(this).attr('data-zoom');

    zooming(zoom);
});


