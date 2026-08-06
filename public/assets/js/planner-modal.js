let FCModal = {

    el: '.js-fencing-modal',
    closeBtnEl: '.js-fencing-modal-close',

    init: function() {
        FCModal.closeBtn();
    },

    //----------------------------------------------------------------------------------
    
    open: function(target = false) {

        let el = target || FCModal.el;
        $(el).fadeIn('fast');

    },

    //----------------------------------------------------------------------------------

    close: function(target = false) {
        let el = target || FCModal.el;
        $(el).fadeOut('fast');
        $(".fencing-btn-modal.fc-btn-active").removeClass('fc-btn-active');
    },
    
    //----------------------------------------------------------------------------------

    closeBtn: function() {
        $(document).on('click', FCModal.closeBtnEl, function() {
            $(this).closest('.fencing-modal').fadeOut('fast');
            $('.fc-btn-active').removeClass('fc-btn-active');
        });
    }

    //----------------------------------------------------------------------------------
};

FCModal.init();