

// https://jqueryvalidation.org/validate/
$("#fc-planning-form").validate({   
    rules: {
        email: {
            required: true,
            email: true
        },
    },
    messages: {
        timeframe: "Please select an option.",
        installer: "Please select an option.",
    },
    submitHandler: function(form) {

        var tab  = $('.fc-form-plan:visible').attr('data-formtab'),
            move = $('.fc-form-plan:visible').find('.fc-btn-next').attr('data-move');

            history.pushState({}, '', `?tab=${getSearchParams('tab')}&form=${move}`);

        if( tab == 4 ) {

            FCModal.close('#submit-modal');
            $('.fc-loader-overlay').show();

            res = submit_fence_planner('new');            
            
        } else {
            $('.fc-form-plan').hide();
            $('[data-formtab="'+move+'"]').show();
        }

    }
});



$(document).on('click', '.fc-btn-update', function(e) {
    e.preventDefault();
   
    FCModal.close('#submit-modal');
    $('.fc-loader-overlay').show();

    if( getSearchParams('qid') ) {
        $('.li-create small').html('Reloading your plan...');
    } else {
        $('.li-create small').html('Updating your plan...');
    }

    res = submit_fence_planner('update');            

});

// localStorage.clear();

if( location.host == 'localhostx' ) {

    // $('#submit-modal').show();
    // $('#fc-control-modal').show();

    // $('.fc-d-none').show();

    // localStorage.clear();

    function test_data() {

        var index = $('.fencing-tab').length;

        $('.fencing-style-item:not(.fsi-selected)').click();

        $('.measurement-box-number').val( 10999 + index );
        $('.btn-fc-calculate').click();

        if( index < 3 ) {

            setTimeout(function() {
                add_new_fence_section();
                test_data();
            }, index);
        }

    }

    test_data();    
} 


function reload_fence_items() {

    if( getSearchParams('action') == 'clear-all' ) {
        clearFencingData();
        location.href = location.origin+location.pathname;
    }

    reloadFencingData();

    var items   = localStorage.getItem('custom_fence-section') ?? 1;
    var section = getSearchParams('section');
    var tab     = getSearchParams('tab');
    var form    = getSearchParams('form');

    for ( let i = 1; i <= items; i++ ) {

        var index = i-1;

        var sectionTab = `<div class="fencing-tab fencing-tab-selected fc-d-none fc-section-${i}">
                <div class="fencing-tab-name">
                    <span class="ftm-title">SECTION</span> <span class="fencing-tab-number">${i}</span>
                    <div class="ftm-measurement"></div>
                </div>
            </div>`;

        $('.fencing-tab-container-area').append(sectionTab);

        $('.fencing-tab').removeClass('fencing-tab-selected');

        $('.fencing-tab:last-child').addClass('fencing-tab-selected');

        var  custom_fence_tabs = localStorage.getItem('custom_fence-'+index);
        const data_tabs = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];
        mesurement = data_tabs[0]?.calculateValue ? parseInt(data_tabs[0]?.calculateValue).toLocaleString() + ' ' + FENCES.defaultValues.unit : '';

        $('.fencing-tab-selected').find('.ftm-measurement').html( mesurement );
        $('.js-btn-delete-fence').show();
        $('.js-fc-form-step').hide();

    }

    if( tab ) {
        $('.fc-section-step').hide();
        $('[data-tab="'+tab+'"]').show();
    } 

    setTimeout(function(){
         if( section ) { 
            $('.fencing-tab-selected').removeClass('fencing-tab-selected');
            $('.fc-section-'+section).addClass('fencing-tab-selected');
        } 

        $('.fencing-tab.fencing-tab-selected:visible').click();

    }, 100);
    
    if( tab == 2 && form ) {
        $('#submit-modal').show();
        $('.fc-form-plan').hide();
        $('[data-formtab="'+form+'"]').show();
    }


    // Store default section count
    if(  localStorage.getItem('custom_fence-section') == null && $('.fencing-tab').length == 1) {
        localStorage.setItem('custom_fence-section', 1);    
    }

    // Initiate tab container scroll
    tabContainerScroll();    


    // Restore form data when the page loads
    restoreFormData();

    if( getSearchParams('qid') ) {
       $('.fc-btn-update').click();
    }

    if( $('.fc-form-check-img input:checked').length ) {
        $('.form-tab-4').closest('form').find('[type="submit"]')
                        .removeClass('disabled')
                        .removeAttr('disabled');
    }

}

reload_fence_items();

