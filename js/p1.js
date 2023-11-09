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




/*var fc_selected_item = 1,
    fc_selected_tab = 0;

function reload_fence_items() {

    var a = {};
    Object.entries(localStorage).forEach(([key, value]) => {
      if (key.startsWith("custom_fence-")) {
        a[key] = value;
      }
    });

    var items = Object.keys(a).length / 2;

    for (let i = 1; i < items; i++) {
        add_new_fence_section();
    }


}
reload_fence_items();


*/