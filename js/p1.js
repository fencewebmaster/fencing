$(window).on('scroll', function(){

    var ha = $('.fc-content-tab-title').height(),
        hb = $('.fc-header-tab').height();

    if( $(window).scrollTop() > ($('.fc-content-tab-title').offset().top) ) {
        $('.fencing-tabs-container').addClass('fc-sticky');
    } else {
        $('.fencing-tabs-container').removeClass('fc-sticky');
    }

});

// https://jqueryvalidation.org/validate/
$("#fc-download-form").validate({   
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

        if( tab == 4 ) {

            var count = 0;

            FCModal.close('#submit-modal');
            $('.fc-loader-overlay').show();

            submit_fence_planner();            

            setTimeout(function(){
                $('.fc-loader ul li').each(function(i) {
                    var $this = $(this);
                    setTimeout(function(){
                       $this.addClass('fc-text-success');
                       count++;

                       if( count == 1 ) {
                            window.onbeforeunload = function () {
                                return;
                            }
                            window.location = 'project-plan.php';
                       }
                       
                    }, 1000 * i);


                });

            }, 1000);

        } else {
            $('.fc-form-plan').hide();
            $('[data-formtab="'+move+'"]').show();
        }

    }
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

    var items = localStorage.getItem('custom_fence-section') ?? 1;
    var section = getSearchParams('section');
    var tab = getSearchParams('tab');
    var form = getSearchParams('form');

    for (let i = 1; i <= items; i++) {

        var index = i-1;

        var sectionTab = `<div class="fencing-tab fencing-tab-selected fc-d-none fc-section-${i}">
                <div class="fencing-tab-name">
                    <span class="ftm-title">SECTION</span> <span class="fencing-tab-number">${i}</span>
                    <div class="ftm-measurement"></div>
                </div>
            </div>`;

        $('.fencing-tab-container-area').append(sectionTab);

        $('.fencing-tab').removeClass('fencing-tab-selected');

        if(section) { 
            $('.fc-section-'+section).addClass('fencing-tab-selected');
        } else {
            $('.fencing-tab:last-child').addClass('fencing-tab-selected');
        }

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
    } else {
        setTimeout(function(){
            $('.fencing-tab.fencing-tab-selected').click();
        }, 100);
    }
    
    if( tab == 2 && form ) {
        $('#submit-modal').show();
        $('.fc-form-plan').hide();
        $('[data-formtab="'+form+'"]').show();
    }
}
reload_fence_items();
