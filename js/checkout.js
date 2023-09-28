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

$(document).on('click', '.fc-btn-download-fence', function () {    

    /*      
        var element = document.getElementById('fc-fence-list');
        var opt = {
          margin:       1,
          filename:     'myfile.pdf',
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2 },
          jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        // New Promise-based usage:
        html2pdf().set(opt).from(element).save();
    */

    var element = document.getElementById('fc-fence-list');
    html2pdf(element);

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
                contentType: false,  
                cache: false,         
                processData:false,    
                success: function(response) {
                    try {
                        $('.fc-table-items').html(response);

                        setTimeout(function() { 
                            $(".fc-table-items .fc-form-control").css({'color': ''}); 
                            $('.fc-section-loader-overlay').hide();
                            $('.fc-item-value').show();
                            $('.fc-table-items input, .fc-reset-item').hide();

                            window.onbeforeunload = function() {}

                        } , 500);

                        $('.fc-edit-item').html('Edit');

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
                headers: {},
                contentType: false,  
                cache: false,         
                processData:false,    
                success: function(response) {
                    try {

                        setTimeout(function() { 
                            $(".fc-table-customer .fc-form-control").css({'color': ''}); 
                            $('.fc-section-loader-overlay').hide();
                            $('.fc-table-customer span').show();
                            $('.fc-project-details .fc-form-group, .fc-btn-reset').hide();

                            window.onbeforeunload = function() {}

                        } , 500);

                        $('.fc-btn-edit').find('span').html('Edit');

                    } catch(err){

                    } 
                }
            });
                
        } else if( action == 'place_order' ) {

            $('.fc-loader-overlay').show();

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

                          window.onbeforeunload = function() {}
          location.href = info.url;

                    if( ! info.error ) {

                    var count = 0;


                    setTimeout(function(){
                        $('.fc-loader ul li').each(function(i) {
                            var $this = $(this);
                            setTimeout(function(){
                               $this.addClass('fc-text-success');
                               count++;

                               if( count == 5 ) {
                                    window.onbeforeunload = function() {}
                          
                               }

                            }, 1000 * i);


                        });

                    }, 1000);


                    }
                    
                    $('#paymentResponse').html(info.message);

                    $('.fc-section-loader-overlay').hide();

                }
            });

        }

    }
}); 

/*
$(document).on('change', '[name="cart[shipping_type]"]', function() {
    $('form').submit();
});
*/ 

// PROJECT DETAILS SECTION
$(document).on('click', ".fc-btn-edit", function (e) {
    e.preventDefault();

    $('[name="action"]').val('update_details');

    if( $(this).find('span').text() == 'Edit' ) {

        $('.fc-project-details .fc-form-group, .fc-btn-reset').show();
        $('.fc-project-details table span').hide();
        $(this).find('span').html('Update');

    } else {
       
        $('form').submit();

    }

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
$(document).on('click', ".fc-edit-item", function (e) {
    e.preventDefault();

    $('[name="action"]').val('update_cart');

    if( $(this).text() == 'Edit' ) {
        $('.fc-table-items input, .fc-reset-item').show();
        $('.fc-item-value').hide();
        $(this).html('Update');
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
    } , 500);

});

$(document).on('click', ".btn-submit", function (e) {
    e.preventDefault();
    $('[name="action"]').val('place_order');
    $('form').submit();
});
