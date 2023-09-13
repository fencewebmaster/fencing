// Initialize Stripe with your publishable API key
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


$(document).on('click', ".fc-btn-edit", function (e) {
    e.preventDefault();

    if( $(this).find('span').text() == 'Edit' ) {
        $('.fc-project-details .fc-form-group, .fc-btn-reset').show();
        $('.fc-project-details table span').hide();
        $(this).find('span').html('Update');

        $('.fc-project-details .fc-editing-icon').fadeIn();

    } else {

        $('.fc-table-customer td').each(function(){
            if( $(this).find('.fc-form-control').length ) {
                var val = $(this).find('.fc-form-control').val();
                $(this).find('span').html( val );
            }
        });

        $(".fc-table-customer .fc-form-control").css({'color': '#4caf50'}); 

        setTimeout(function() { 
            $(".fc-table-customer .fc-form-control").css({'color': ''}); 

            $('.fc-table-customer span').show();
            $('.fc-project-details .fc-form-group, .fc-btn-reset').hide();

            $('.fc-project-details .fc-editing-icon').fadeOut();

        } , 500);

        $(this).find('span').html('Edit');

    }

});


$(document).on('click', ".fc-btn-reset", function (e) {
    e.preventDefault();

    $('.fc-table-customer td').each(function(){
        if( $(this).find('.fc-form-control').length ) {
            var val = $(this).find('span').text();
            $(this).find('.fc-form-control').val(val)
        }
    });

    $(".fc-table-customer .fc-form-control").css({'color': '#f67925'}); 

    setTimeout(function() { 
        $(".fc-table-customer .fc-form-control").css({'color': ''}); 
    } , 500);

});


$(document).on('click', ".fc-edit-item", function (e) {
    e.preventDefault();

    if( $(this).text() == 'Edit' ) {
        $('.fc-table-items input, .fc-reset-item, .fc-link-div').show();
        $('.fc-item-value').hide();
        $(this).html('Update');
        $('.fc-table-items .fc-editing-icon').fadeIn();
    } else {

        $('.fc-table-items td').each(function(){
            if( $(this).find('.fc-form-control').length ) {
                var val = $(this).find('.fc-form-control').val();
                $(this).find('.fc-item-value').html( val );
            }
        });

        $(".fc-table-items .fc-form-control").css({'color': '#4caf50'}); 

        setTimeout(function() { 
            $(".fc-table-items .fc-form-control").css({'color': ''}); 

            $('.fc-item-value').show();
            $('.fc-table-items input, .fc-reset-item, .fc-link-div').hide();
            $('.fc-table-items .fc-editing-icon').fadeOut();
        } , 500);

        $(this).html('Edit');

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