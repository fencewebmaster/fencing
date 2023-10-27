/*// Initialize Stripe with your publishable API key
var stripe = Stripe('pk_test_fY3GMPqaZTKE94kLMB5BnOdf');


// Create an instance of elements
var elements = stripe.elements();

var style = {
    base: {
        fontWeight: 400,
        fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif',
        fontSize: '16px',
        lineHeight: '1.4',
        color: '#555',
        backgroundColor: '#fff',
        '::placeholder': {
            color: '#888',
        },
    },
    invalid: {
        color: '#eb1c26',
    }
};

var cardElement = elements.create('cardNumber', {
    style: style
});
cardElement.mount('#card_number');

var exp = elements.create('cardExpiry', {
    'style': style
});
exp.mount('#card_expiry');

var cvc = elements.create('cardCvc', {
    'style': style
});
cvc.mount('#card_cvc');

// Validate input of the card elements
var resultContainer = document.getElementById('paymentResponse');
cardElement.addEventListener('change', function(event) {
    if (event.error) {
        resultContainer.innerHTML = '<p>'+event.error.message+'</p>';
    } else {
        resultContainer.innerHTML = '';
    }
});

// Get payment form element
var form = document.getElementById('paymentFrm');

// Create a token when the form is submitted.
form.addEventListener('submit', function(e) {
    e.preventDefault();
    createToken();
});

// Create single-use token to charge the user
function createToken() {
    stripe.createToken(cardElement).then(function(result) {
        if (result.error) {
            // Inform the user if there was an error
            resultContainer.innerHTML = '<p>'+result.error.message+'</p>';
        } else {
            // Send the token to your server
            stripeTokenHandler(result.token);
        }
    });
}

// Callback to handle the response from stripe
function stripeTokenHandler(token) {

    // Insert the token ID into the form so it gets submitted to the server
    var hiddenInput = document.createElement('input');
    hiddenInput.setAttribute('type', 'hidden');
    hiddenInput.setAttribute('name', 'stripeToken');
    hiddenInput.setAttribute('value', token.id);
    form.appendChild(hiddenInput);
    
    // Submit the form
    form.submit();

}
*/


$(document).on('click', '.fc-btn-download-fence', function (e) {    

    window.jsPDF = window.jspdf.jsPDF;

    var element = document.getElementById('fencing-display-result');

    html2canvas(element,
        { 
            scale: 3,
            dpi: 300,
            width: 1350,
            height: 1350,
        }
    ).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF({
          orientation: 'landscape',
        });
        const imgProps= pdf.getImageProperties(imgData);
        const pdfWidth = 300;
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'PNG', 13, 0, pdfWidth, pdfHeight);
        pdf.save('project-plan.pdf');
    });
    

});

$("#paymentFrm").validate({   
    rules: {
        name: { required: true },
        mobile: { required: true },
        postcode: { required: true },
        address: { required: true },
        email: {
            required: true,
            //email: true
        },
    },
    messages: {},
    submitHandler: function(form) {

        window.onbeforeunload = function() { return false; }

        var action   = $('[name="action"]').val(),
            form     = $('form')[0],
            formData = new FormData(form);

        formData.set("action", action);

        $('#paymentResponse').html('');           

        $('#'+action+'-section').find('.fc-section-loader-overlay').show();

        if( action == 'update_cart' ) {

            $('.fc-table-items td').each(function(){
                if( $(this).find('.fc-form-control').length ) {
                    var val = $(this).find('.fc-form-control').val();
                    $(this).find('.fc-item-value').html( val );
                }
            });

            $(".fc-table-items .fc-form-control").css({'color': '#4caf50'}); 

            $.ajax({
                url: 'checkout.php', 
                type: "POST",  
                data: formData,
                headers: {},
                beforeSend: function(){
                    loadSectionOverlay('update_cart-list');
                },
                contentType: false,  
                cache: false,         
                processData:false,    
                success: function(response) {
                    try {
                        $('.fc-table-items').html(response);

                        setTimeout(function() { 
                            $(".fc-table-items .fc-form-control").css({'color': ''}); 
                            removeSectionOverlay();
                            $('.fc-item-value').show();
                            $('.fc-table-items input, .fc-reset-item').hide();

                            window.onbeforeunload = function() {}

                        } , 500);

                        $('.js-fc-edit-item span').html('Edit');

                    } catch(err){
                        console.log('err: ', response);
                    } 
                }
            });

        } else if( action == 'update_details' ) {

            $('.fc-table-customer td').each(function(){
                if( $(this).find('.fc-form-control').length ) {

                    if( $(this).find('.fc-form-control').prop('tagName').toLowerCase() == 'select' ) {
                        var val = $(this).find('.fc-form-control option:selected').text();
                    } else {
                        var val = $(this).find('.fc-form-control').val();
                    }

                    $(this).find('span').html( val );

                }
            });

            $(".fc-table-customer .fc-form-control").css({'color': '#4caf50'}); 

            $.ajax({
                url: 'checkout.php', 
                type: "POST",  
                data: formData,
                beforeSend: function(){
                    loadSectionOverlay('update_details-section');
                },
                headers: {},
                contentType: false,  
                cache: false,         
                processData:false,    
                success: function(response) {
                    try {

                        setTimeout(function() { 
                            $(".fc-table-customer .fc-form-control").css({'color': ''}); 
                            removeSectionOverlay();
                            $('.fc-table-customer span').show();
                            $('.fc-project-details .fc-form-group, .fc-btn-reset').hide();
                            $('.js-project-details-controls').addClass('fc-d-none');


                            $.get( "temp/sections/your-project-details.php", function(res){
                                $(".your-project-details").html(res);
                            });

                            window.onbeforeunload = function() {}

                        } , 500);

                    } catch(err){

                    } 
                }
            });
                
        } else if( action == 'push_order' ) {

            $('.fc-loader-overlay').show();

            setTimeout(function(){
                $('.fc-loader ul li:first-child').addClass('fc-text-success');
            }, 500);

            $.ajax({
                url: 'checkout.php', 
                type: "POST",  
                data: formData,
                headers: {},
                contentType: false,  
                cache: false,         
                processData:false,    
                success: function(response) {
                    
                    var info = JSON.parse(response);

                    if( ! info.error ) {

                        window.onbeforeunload = function() {}

                        var count = 0;
            
                        $('.fc-loader ul li:not(.fc-text-success)').each(function(i) {
                            var $this = $(this);

                            setTimeout(function(){
                               $this.addClass('fc-text-success');
                               count++;
       
                            }, 2000 * i);

                        });
                        
                        // Add clear fence planner local storage here

                        location.href = info.url;

                    }
                    
                    $('#paymentResponse').html(info.message);


                }
            });

        }

    }
}); 

$(document).on('change', '[name="cart[shipping_type]"]', function() {
    $('form').find('[type="submit"]').click();
}); 

// PROJECT DETAILS SECTION
$(document).on('click', ".fc-btn-edit", function (e) {
    e.preventDefault();

    $('[name="action"]').val('update_details');

    let _this = $(this);
    let _edit_btn = $(".fc-btn-edit[data-action='edit']");
    let _action = _this.attr('data-action');

    if( _action == 'edit' ) {

        $('.fc-project-details .fc-form-group, .fc-btn-reset').show();
        $('.fc-project-details table span:not([class^="js-"])').hide();

        _this.hide();

    } else {
       
        $('form').submit();
        _edit_btn.show();

    }

    $('.js-project-details-controls').removeClass('fc-d-none');

});

$(document).on('click', '.project-details--edit', function(){
    $('#submit-modal').show();
    restoreFormData();
});

// PROJECT DETAILS SECTION
$(document).on('click', ".project-details--update", function (e) {
    e.preventDefault();

    $('[name="action"]').val('update_details');
    $('form').submit();
    $('#submit-modal').hide();
});



$(document).on('click', ".fc-btn-reset", function (e) {
    e.preventDefault();

    $('.fc-table-customer td').each(function(){
        if( $(this).find('.fc-form-control').length ) {

            var val = $(this).find('span').text();

            if( $(this).find('.fc-form-control').prop('tagName').toLowerCase() == 'select' ) {
                $(this).find('option:contains('+val+')').prop('selected', true);
            } else {
                $(this).find('.fc-form-control').val(val)
            }

        }
    });

    $(".fc-table-customer .fc-form-control").css({'color': '#f67925'}); 

    setTimeout(function() { 
        $(".fc-table-customer .fc-form-control").css({'color': ''}); 
    } , 500);

});

// ITEM LIST & CART SECTION
$(document).on('click', ".js-fc-edit-item", function (e) {
    e.preventDefault();

    $('[name="action"]').val('update_cart');

    if( $(this).find('span').text() === 'Edit' ) {
        $('.fc-table-items input, .fc-reset-item').show();
        $('.fc-item-value').hide();
        $(this).find('span').html('Update');
    } else {

        $('form').submit();

    }

});


$(document).on('click', ".fc-reset-item", function (e) {
    e.preventDefault();

    $('.fc-table-items td').each(function(){
        if( $(this).find('.fc-form-control').length ) {
            var val = $(this).find('.fc-item-value').text();
            $(this).find('.fc-form-control').val(val)
        }
    });

    $(".fc-table-items .fc-form-control").css({'color': '#f67925'}); 

    setTimeout(function() { 
        $(".fc-table-items .fc-form-control").css({'color': ''}); 
    }, 500);

});

$(document).on('click', ".btn-submit", function (e) {
    e.preventDefault();
    $('[name="action"]').val('push_order');
    $('form').submit();
});


var getcountDownDate = localStorage.getItem('countdown-date');

if( ! getcountDownDate ) {
    // Set the date we're counting down to
    localStorage.setItem('countdown-date', setcountDownDate);
    
    var getcountDownDate = localStorage.getItem('countdown-date');
}


var countDownDateFormat = new Date(getcountDownDate).getTime(),
    cont = 'fc-countdown-timer';

    // Update the count down every 1 second
    var x = setInterval(function() {

  // Get today's date and time
  var now = new Date().getTime();

  // Find the distance between now and the count down date
  var distance = countDownDateFormat - now;

  // Time calculations for days, hours, minutes and seconds
  var days    = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours   = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Display the result in the element with id="demo"
  document.getElementById(cont).innerHTML = hours + "hrs " + minutes + "mins " + seconds + "secs ";

  // If the count down is finished, write some text
  if (distance < 0) {
    clearInterval(x);
    document.getElementById(cont).innerHTML = "Limited time offer...<br> HURRY UP!";
    localStorage.removeItem('countdown-date');
  }

}, 1000);