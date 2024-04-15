var _doc = $(document);

/*
    ----------------------------------------------------------------
    [START] CLICK EVENT
    ----------------------------------------------------------------
*/

_doc.on('click', '.fc-btn-download-fence', fcBtnDownloadFence);

function fcBtnDownloadFence(e) {

    var $this = $(this);

    const date = new Date();

    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();

    // This arrangement can be altered based on how we want the date's format to appear.
    let currentDate = `${day}-${month}-${year}`;

    window.jsPDF = window.jspdf.jsPDF;

    $('#project-plans-section').addClass('downloading-pdf').append('<div id="fc-follow"></div>');
    $('#update_cart-section').addClass('downloading-pdf')

    var element = document.getElementById('project-plans-section');

    // Add loading animation
    $this.find('i').removeAttr('class').addClass('fas fa-spinner fa-spin');
    $this.attr('disabled', true).find('span').html('Preparing Plans...');


    var count = 0;

    $('.fc-loader-overlay').show();
    $('.fc-loader ul li').remove();

    var items = [
        'Converting to PDF...',
    ];

    $.each(items, function(k, v) {
        $('.fc-loader ul').append(`<li><i class="fa fa-check fc-mr-1"></i> ${v}</li>`);
    });

    $('.fc-loader ul li:not(.fc-text-success)').each(function(i) {
        var $this = $(this);

        setTimeout(function() {
            $this.addClass('fc-text-success');
            count++;

        }, 500 * i);

    });

    html2canvas(element, {
        width: 1600,
    }).then(canvas => {

        var sectionCount = localStorage.getItem('custom_fence-section') ?? 1;
        var sectionGroupCount = Math.ceil((sectionCount / 3));

        const imgData = canvas.toDataURL('image/png');

        const doc = new jsPDF('l', 'mm', 'letter');

        const pageHeight = doc.internal.pageSize.getHeight();

        const imgProps = doc.getImageProperties(imgData);
        const imgWidth = doc.internal.pageSize.getWidth();
        const imgHeight = (imgProps.height * imgWidth) / imgProps.width;

        let heightLeft = imgHeight;

        doc.addImage(imgData, 'PNG', 10, 0, imgWidth, imgHeight - 10);
        heightLeft -= pageHeight;

        while (heightLeft >= 0) {

            position = heightLeft - imgHeight;

            doc.addPage();
            doc.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight + 25);
            heightLeft -= pageHeight;
        }

        element = document.getElementById("update_cart-section");

        // https://stackoverflow.com/questions/36472094/how-to-set-image-to-fit-width-of-the-page-using-jspdf
        doc.html(element, {
            callback: function(doc) {
                doc.save(`project-plan-${currentDate}.pdf`);

                // Return to default estate
                $this.find('i').removeAttr('class').addClass('fa-solid fa-download');
                $this.removeAttr('disabled').find('span').html('Download Plans');
                $('.downloading-pdf').removeClass('downloading-pdf');
                $("#fc-follow").remove();
                $('.fc-loader-overlay').hide();
                $('.fc-loader ul li').remove();

            },
            margin: [10, 10, 10, 10],
            x: 0,
            y: 196 * sectionGroupCount,
            html2canvas: {
                autoPaging: 'text',
                scale: 0.16, //this was my solution, you have to adjust to your size
                width: 1000 //for some reason width does nothing
            },
        });


        // pdf.addImage(imgData, 'PNG', 13, 0, pdfWidth, pdfHeight);



    });


}

//----------------------------------------------------------------------------------

// PROJECT DETAILS SECTION

_doc.on('click', '.fc-btn-edit', fcBtnEdit);

function fcBtnEdit(e) {
    e?.preventDefault();

    $('[name="action"]').val('update_details');

    let _this = $(this);
    let _action = _this.attr('data-action');

    if (_action == 'edit') {
        $('.project-details--edit').toggleClass('project-details--edit project-details--editable');
        $('.fc-project-details .fc-form-group, .fc-btn-reset').show();
        $('.fc-project-details table span:not([class^="js-"])').hide();

        _this.hide();

        loadClearForm();

    } else {

        $('form').submit();

    }

    $('.js-project-details-controls').removeClass('fc-d-none');

}

//----------------------------------------------------------------------------------

_doc.on('click', '.project-details--editable', projectDetailsEditable);

function projectDetailsEditable() {
    $('#submit-modal').show();
    restoreFormData();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.project-details--update', projectDetailsUpdate);

function projectDetailsUpdate(e) {
    e?.preventDefault();

    $('.fc-color-options').each(function(k, v) {
        var _this = $(this),
            slug = _this.attr('data-slug'),
            val = _this.find('.fc-selected').attr('data-slug');

        $('.fc-color-options[data-slug="' + slug + '"] .input-fence').val(slug);
        $('.fc-color-options[data-slug="' + slug + '"] .input-color').val(val);
    });


    $('[name="action"]').val('update_project_details');
    $('.fc-btn-edit[data-action="update"]').trigger('click');
    $('#submit-modal').hide();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-reset', fcBtnReset);

function fcBtnReset(e) {
    e?.preventDefault();

    $('.fc-table-customer td').each(function() {

        var _this = $(this);

        if (_this.find('.form-control').length) {

            var val = _this.find('span').text();

            if (_this.find('.form-control').prop('tagName').toLowerCase() == 'select') {
                _this.find('option:contains(' + val + ')').prop('selected', true);
            } else {
                _this.find('.form-control').val(val)
            }

        }
    });

    $(".fc-table-customer .fc-form-control").css({ 'color': '#f67925' });

    setTimeout(function() {
        $(".fc-table-customer .fc-form-control").css({ 'color': '' });
    }, 500);

}

//----------------------------------------------------------------------------------

_doc.on('click', '.js-fc-edit-item', jsFcEditItem);

function jsFcEditItem(e) {
    e?.preventDefault();

    var _this = $(this);

    $('[name="action"]').val('update_cart');

    if (_this.find('span').text() === 'Edit') {
        $('.fc-table-items input, .fc-reset-item').show();
        $('.fc-item-value').hide();
        _this.find('span').html('Save');
    } else {
        $('form').submit();
    }

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-reset-item', fcResetItem);

function fcResetItem(e) {
    e?.preventDefault();

    $('.fc-table-items td').each(function() {

        var _this = $(this);

        if (_this.find('.fc-form-field').length) {
            var val = _this.closest('tr').data('original');
            _this.find('.fc-form-field').val(val)
        }
    });

    $(".fc-table-items .fc-form-field").css({ 'color': '#f67925' });

    setTimeout(function() {
        $(".fc-table-items .fc-form-field").css({ 'color': '' });
    }, 500);

}

//----------------------------------------------------------------------------------


_doc.on('click', '.btn-submit', btnSubmit);

function btnSubmit(e) {
    e?.preventDefault();
    $('[name="action"]').val('push_order');
    $('form').submit();
}



_doc.on('click', '.fencing-qty-btn', inputQty);
_doc.on('keyup', '.table-cart [input-type="number"]', inputQty);

function inputQty(e) {
    var _this = $(this);
    var val = _this.closest('.fencing-mb-input').find('input').val();
    _this.closest('tr').find('.fc-form-field').val(val);
}

/* ----------------------------------------------------------------
    [END] CLICK EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] CHANGE EVENT
    ---------------------------------------------------------------- */

_doc.on('change', '[name="cart[shipping_type]"]', cart_shippingType);

function cart_shippingType() {
    $('form').find('[type="submit"]').trigger('click');
}

/* ----------------------------------------------------------------
    [END] CHANGE EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] VALIDATE
    ---------------------------------------------------------------- */
// https://jqueryvalidation.org/validate/

$("#paymentFrm").validate({
    rules: {
        name: { required: true },
        mobile: { required: true },
        postcode: { required: true },
        address: { required: true },
        email: {
            required: true,
            email: true
        },
    },
    messages: {},
    submitHandler: function(form) {

        window.onbeforeunload = function() { return false; }

        var action = $('[name="action"]').val(),
            form = $('form')[0],
            formData = new FormData(form);

        formData.set("action", action);

        $('#paymentResponse').html('');

        $('#' + action + '-section').find('.fc-section-loader-overlay').show();

        if (action == 'update_cart') {

            $('.fc-table-items td').each(function() {
                var _this = $(this);

                if (_this.find('.fc-form-control').length) {
                    var val = _this.find('.fc-form-control').val();
                    _this.find('.fc-item-value').html(val);
                }
            });

            $(".fc-table-items .fc-form-control").css({ 'color': '#4caf50' });

            $.ajax({
                url: 'checkout.php',
                type: "POST",
                data: formData,
                headers: {},
                beforeSend: function() {
                    HELPER.loadSectionOverlay('update_cart-list');
                },
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    try {
                        $('.fc-table-items').html(response);

                        setTimeout(function() {
                            $(".fc-table-items .fc-form-control").css({ 'color': '' });
                            HELPER.removeSectionOverlay();
                            $('.fc-item-value').show();
                            // $('.fc-table-items input, .fc-reset-item').hide();

                            window.onbeforeunload = function() {}

                        }, 500);

                        // $('.js-fc-edit-item span').html('Edit');

                    } catch (err) {
                        console.log('err: ', response);
                    }
                }
            });

        } else if (action == 'update_project_details') {

            var pdBtnVisible = $('.project-details-controls button').is(':visible');

            $(".fc-table-customer .fc-form-control").css({ 'color': '#4caf50' });

            $.ajax({
                url: 'checkout.php',
                type: "POST",
                data: formData,
                beforeSend: function() {
                    HELPER.loadSectionOverlay('update_details-section');
                },
                headers: {},
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    try {

                        $('[name="action"]').val('update_cart');
                        $('form').submit();

                        setTimeout(function() {
                            $(".fc-table-customer .fc-form-control").css({ 'color': '' });
                            HELPER.removeSectionOverlay();

                            $(".your-project-details").html(response);

                            if (pdBtnVisible) {

                                $('.fc-btn-edit[data-action="edit"]').trigger('click');

                            }

                            window.onbeforeunload = function() {}

                        }, 500);

                    } catch (err) {

                    }
                }
            });

        } else if (action == 'update_details') {

            $('.fc-table-customer td').each(function() {

                var _this = $(this);

                if (_this.find('.fc-form-control').length) {

                    if (_this.find('.fc-form-control').prop('tagName').toLowerCase() == 'select') {
                        var val = _this.find('.fc-form-control option:selected').text();
                    } else {
                        var val = _this.find('.fc-form-control').val();
                    }

                    _this.find('span').html(val);

                }
            });

            $(".fc-table-customer .fc-form-control").css({ 'color': '#4caf50' });

            $.ajax({
                url: 'checkout.php',
                type: "POST",
                data: formData,
                beforeSend: function() {
                    HELPER.loadSectionOverlay('update_details-section');
                },
                headers: {},
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    try {

                        $('[name="action"]').val('update_cart');
                        $('form').submit();

                        setTimeout(function() {
                            $(".fc-table-customer .fc-form-control").css({ 'color': '' });

                            HELPER.removeSectionOverlay();

                            $('.fc-table-customer span').show();
                            $('.fc-project-details .fc-form-group, .fc-btn-reset').hide();
                            $('.js-project-details-controls').addClass('fc-d-none');
                            $(".fc-btn-edit[data-action='edit']").show();
                            $(".your-project-details").html(response);

                            window.onbeforeunload = function() {}

                        }, 500);

                    } catch (err) {

                    }
                }
            });

        } else if (action == 'push_order') {

            $('.fc-loader-overlay').show();
            $('.fc-loader ul li').remove();

            var items = [
                'Preparing:',
                'Checking customer details...',
                'Pushing order into cart...',
                'Redirecting to fencing website...',
            ];

            $.each(items, function(k, v) {
                $('.fc-loader ul').append(`<li><i class="fa fa-check fc-mr-1"></i> ${v}</li>`);
            });

            setTimeout(function() {
                $('.fc-loader ul li:first-child').addClass('fc-text-success');
            }, 500);

            $.ajax({
                url: 'checkout.php',
                type: "POST",
                data: formData,
                headers: {},
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    console.log('response', response);
                    if (!response) {
                        return;
                    }

                    var info = JSON.parse(response);

                    if (!info.error) {

                        window.onbeforeunload = function() {}

                        var count = 0;

                        $('.fc-loader ul li:not(.fc-text-success)').each(function(i) {
                            var $this = $(this);

                            setTimeout(function() {
                                $this.addClass('fc-text-success');
                                count++;

                            }, 2000 * i);

                        });

                        clearFencingData();

                        location.href = info.url;

                    }

                    $('#paymentResponse').html(info.message);


                }
            });

        }

    }
});

/* ----------------------------------------------------------------
    [END] VALIDATE
    ---------------------------------------------------------------- */