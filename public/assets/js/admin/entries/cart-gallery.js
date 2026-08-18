/**
 * FC Admin — Planner entry cart item image gallery.
 */
(function () {
    'use strict';

    var lightbox = new window.FC.components.ImageLightbox({
        bodyOpenClass: 'fc-entries-cart-gallery-open',
        closeOnBackdrop: true
    });

    function collectSlides(panel) {
        var slides = [];

        panel.querySelectorAll('[data-fc-cart-gallery-open]').forEach(function (btn) {
            var row = btn.closest('[data-fc-cart-row]');
            if (row && row.hidden) {
                return;
            }

            var url = btn.getAttribute('data-fc-cart-gallery-url') || '';
            if (url === '') {
                return;
            }

            slides.push({
                url: url,
                caption: btn.getAttribute('data-fc-cart-gallery-caption') || '',
                btn: btn,
            });
        });

        return slides;
    }

    function initCartGallery(panel) {
        if (panel.getAttribute('data-fc-cart-gallery-bound') === '1') {
            return;
        }
        panel.setAttribute('data-fc-cart-gallery-bound', '1');

        panel.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-fc-cart-gallery-open]');
            if (!btn) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            var slides = collectSlides(panel);
            var startIndex = slides.findIndex(function (slide) {
                return slide.btn === btn;
            });

            lightbox.open(slides, startIndex < 0 ? 0 : startIndex, { ariaLabel: 'Cart item images' });
        });
    }

    function initAllCartGalleries(root) {
        (root || document).querySelectorAll('.fc-entries-detail-panel--cart').forEach(initCartGallery);
    }

    window.FcEntriesCartGallery = {
        init: initAllCartGalleries,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAllCartGalleries();
        });
    } else {
        initAllCartGalleries();
    }
})();
