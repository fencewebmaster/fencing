let FCModal = {

    el: '.js-fencing-modal',
    closeBtnEl: '.js-fencing-modal-close',
    /* Every modal carries .fencing-modal, but only #fc-control-modal also carries
       .js-fencing-modal — keying the lock off FCModal.el would leave the page scrolling behind
       #submit-modal. */
    modalEl: '.fencing-modal',
    /* Drives the drawer slide (style.css "Option drawer"); every other modal ignores it. */
    slidClass: 'fc-modal-slid',

    //----------------------------------------------------------------------------------

    /* A locked page keeps the scrollbar's gutter in the containing block, so a 100%-wide fixed
       overlay stops short of the right edge; CSS cannot measure it, so the drawers read this. */
    setScrollbarGap: function(px) {
        document.documentElement.style.setProperty('--fc-scrollbar-gap', Math.max(0, px) + 'px');
    },

    //----------------------------------------------------------------------------------

    /* A pick leaves the drawer open, since the drawing is beside it; the phone popup still
       closes, and only the pick is held back - X, Escape, backdrop and Calculate all close. */
    keepsOpenOnPick: function(el) {
        if (!el || typeof el.closest !== 'function' || !el.closest('#fc-control-modal')) {
            return false;
        }
        return window.matchMedia('(min-width: 768px)').matches;
    },

    //----------------------------------------------------------------------------------

    /* Pad by what the body actually gained, not by the scrollbar's width: it does not widen
       under the lock, and padding it anyway dragged the centred page 7.5px left. */
    afterScrollLock: function(bodyWidthBeforeLock) {
        var grew = document.body.getBoundingClientRect().width - bodyWidthBeforeLock;
        /* Cleared rather than zeroed, so whatever the stylesheet sets comes back. */
        $('body').css('padding-right', grew > 0 ? grew + 'px' : '');
        FCModal.setScrollbarGap(window.innerWidth - document.documentElement.getBoundingClientRect().width);
    },

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
            if (!$('body').hasClass('fc-modal-open')) {
                /* Measured across the class going on, not read off the scrollbar.
                   See FCModal.afterScrollLock for why those are different numbers. */
                var wasWide = document.body.getBoundingClientRect().width;
                $('body').addClass('fc-modal-open');
                FCModal.afterScrollLock(wasWide);
            }
            return;
        }

        $('body').removeClass('fc-modal-open');
        /* Cleared rather than zeroed, so whatever the stylesheet sets comes back. */
        $('body').css('padding-right', '');
    },

    //----------------------------------------------------------------------------------
    
    open: function(target = false) {

        let el = target || FCModal.el;
        let $el = $(el);
        /* Reopening an already-open drawer swaps its contents; it must not slide in again. */
        let wasOpen = $el.is(':visible');

        if (!wasOpen) {
            $el.removeClass(FCModal.slidClass);
        }
        $el.fadeIn('fast');
        if (!wasOpen) {
            /* Flush layout so the off-screen transform resolves before the class changes it;
               rAF would do too, but is throttled in a background tab. */
            $el.each(function() { void this.offsetWidth; });
        }
        $el.addClass(FCModal.slidClass);
        FCModal.syncScrollLock();

    },

    //----------------------------------------------------------------------------------

    close: function(target = false) {
        let el = target || FCModal.el;
        /* Class off first, so the drawer slides out while jQuery fades the overlay over it. */
        $(el).removeClass(FCModal.slidClass);
        /* Synced after the fade, not before it: the element stays :visible while it animates out. */
        $(el).fadeOut('fast', FCModal.syncScrollLock);
        $(".fencing-btn-modal.fc-btn-active, .fc-fence-color-btn.fc-btn-active, .fc-post-finish-btn.fc-btn-active").removeClass('fc-btn-active');
    },
    
    //----------------------------------------------------------------------------------

    closeBtn: function() {
        $(document).on('click', FCModal.closeBtnEl, function() {
            $(this).closest('.fencing-modal')
                .removeClass(FCModal.slidClass)
                .fadeOut('fast', FCModal.syncScrollLock);
            $('.fc-btn-active').removeClass('fc-btn-active');
        });
    }

    //----------------------------------------------------------------------------------
};

FCModal.init();