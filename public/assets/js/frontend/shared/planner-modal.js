let FCModal = {

    el: '.js-fencing-modal',
    closeBtnEl: '.js-fencing-modal-close',
    /* Every modal carries .fencing-modal, but only #fc-control-modal also carries
       .js-fencing-modal — keying the lock off FCModal.el would leave the page scrolling behind
       #submit-modal. */
    modalEl: '.fencing-modal',

    init: function() {
        FCModal.closeBtn();
    },

    //----------------------------------------------------------------------------------

    /**
     * Hold the page still while a modal is up. The modal is position: fixed with its own
     * overflow: auto, so it keeps scrolling when the body cannot.
     * Read off what is actually visible rather than counted on open/close: close() with no target
     * closes every .js-fencing-modal at once, and a counter would drift the first time one was
     * hidden by anything other than these two paths.
     */
    syncScrollLock: function() {
        var open = $(FCModal.modalEl).filter(':visible').length > 0;

        if (open) {
            /* Measured before the class goes on, while the scrollbar is still there. Hiding it
               widens the viewport, and the overlay is translucent, so without the reserved width
               the page visibly jumps sideways behind the modal as it opens (7px at 1100px).
               Phones report 0 here — overlay scrollbars take no width — so nothing is added. */
            var gap = window.innerWidth - document.documentElement.clientWidth;
            if (gap > 0) {
                $('body').css('padding-right', gap + 'px');
            }
            $('body').addClass('fc-modal-open');
            return;
        }

        $('body').removeClass('fc-modal-open');
        /* Cleared rather than zeroed, so whatever the stylesheet sets comes back. */
        $('body').css('padding-right', '');
    },

    //----------------------------------------------------------------------------------
    
    open: function(target = false) {

        let el = target || FCModal.el;
        $(el).fadeIn('fast');
        FCModal.syncScrollLock();

    },

    //----------------------------------------------------------------------------------

    close: function(target = false) {
        let el = target || FCModal.el;
        /* Synced after the fade, not before it: the element stays :visible while it animates out. */
        $(el).fadeOut('fast', FCModal.syncScrollLock);
        $(".fencing-btn-modal.fc-btn-active").removeClass('fc-btn-active');
    },
    
    //----------------------------------------------------------------------------------

    closeBtn: function() {
        $(document).on('click', FCModal.closeBtnEl, function() {
            $(this).closest('.fencing-modal').fadeOut('fast', FCModal.syncScrollLock);
            $('.fc-btn-active').removeClass('fc-btn-active');
        });
    }

    //----------------------------------------------------------------------------------
};

FCModal.init();