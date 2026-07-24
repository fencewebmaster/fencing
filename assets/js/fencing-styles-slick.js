/**
 * Step 1 — fence style picker carousel (Slick).
 * mobileFirst: phones 2/row; width > 767 → 4 (tablet/iPad); width > 990 → 6 (desktop).
 */
(function($) {
    'use strict';

    var SELECTOR = '.fc-planner-page .js-fencing-styles-slick';

    var resizeTimer;

    function sliderWrapFromSlider($slider) {
        var $section = $slider.closest('.fencing-styles');
        var $wrap = $slider.closest('.fencing-styles__area');
        if (!$wrap.length) {
            $wrap = $section;
        }
        return { $section: $section, $wrap: $wrap };
    }

    /** True when Step 1 (fence styles) panel is visible — Slick must not init when hidden (wrong slide widths). */
    function isFenceStylesStepVisible() {
        var $slider = $(SELECTOR);
        if (!$slider.length) {
            return false;
        }
        var $step = $slider.closest('.fc-section-step[data-tab="1"]');
        return $step.length ? $step.is(':visible') : true;
    }

    /**
     * Call after switching to Section Details (tab 1) when Slick was deferred on load (e.g. opened planner with ?tab=2).
     * Exposed for planner tab handlers (events.js).
     */
    window.fcRefreshFencingStylesSlick = function() {
        var $el = $(SELECTOR);
        if (!$el.length || typeof $.fn.slick !== 'function') {
            return;
        }

        function reposition($slider, $wrap) {
            refreshSlickPosition($slider);
            syncDotsVisibility($slider, $wrap);
            requestAnimationFrame(function() {
                refreshSlickPosition($slider);
                syncDotsVisibility($slider, $wrap);
            });
        }

        if ($el.hasClass('slick-initialized')) {
            var meta = sliderWrapFromSlider($el);
            meta.$wrap.addClass('fencing-styles-slick-ready');
            reposition($el, meta.$wrap);
            return;
        }

        if (!isFenceStylesStepVisible()) {
            return;
        }

        initFencingStylesSlick();
    };

    /**
     * Slick may fire `init` before `data('slick')` is set, or class slick-initialized can linger after unslick.
     * Calling setPosition without a live instance throws (reading 'setPosition' of undefined inside slick.min.js).
     */
    function refreshSlickPosition($slider) {
        if (!$slider || !$slider.length || typeof $.fn.slick !== 'function') {
            return;
        }
        $slider.each(function() {
            var $node = $(this);
            if (!$node.hasClass('slick-initialized')) {
                return;
            }
            if (!$node.data('slick')) {
                return;
            }
            try {
                $node.slick('setPosition');
            } catch (e) {
                /* Slider destroyed or mid-reinit */
            }
        });
    }

    /** Hide dot nav when there is only one page (single bullet is redundant). */
    function syncDotsVisibility($slider, $wrap) {
        if (!$slider || !$slider.length || !$wrap || !$wrap.length) {
            return;
        }
        if (!$slider.hasClass('slick-initialized')) {
            return;
        }
        var dotCount = $slider.find('.slick-dots li').length;
        if (dotCount <= 1) {
            $wrap.addClass('fencing-styles-slick-single-page');
        } else {
            $wrap.removeClass('fencing-styles-slick-single-page');
        }
    }

    function initFencingStylesSlick() {
        var $el = $(SELECTOR);
        if (!$el.length || typeof $.fn.slick !== 'function') {
            return;
        }
        if ($el.hasClass('slick-initialized')) {
            return;
        }

        if (!isFenceStylesStepVisible()) {
            return;
        }

        var $section = $el.closest('.fencing-styles');
        var $wrap = $el.closest('.fencing-styles__area');
        if (!$wrap.length) {
            $wrap = $section;
        }

        $section.addClass('fencing-styles--slick');
        if (!$wrap.hasClass('fencing-styles-slick-pending')) {
            $wrap.addClass('fencing-styles-slick-pending');
        }

        var arrowPrev =
            '<button type="button" class="slick-prev fencing-styles-arrow" aria-label="Previous fencing styles">' +
            '<span class="fencing-styles-arrow__inner">' +
            '<i class="fa-solid fa-chevron-left fencing-styles-arrow__icon" aria-hidden="true"></i>' +
            '</span></button>';
        var arrowNext =
            '<button type="button" class="slick-next fencing-styles-arrow" aria-label="Next fencing styles">' +
            '<span class="fencing-styles-arrow__inner">' +
            '<i class="fa-solid fa-chevron-right fencing-styles-arrow__icon" aria-hidden="true"></i>' +
            '</span></button>';

        var revealFallback = setTimeout(function() {
            $wrap.addClass('fencing-styles-slick-ready');
        }, 2500);

        function revealAndSync() {
            clearTimeout(revealFallback);
            $wrap.addClass('fencing-styles-slick-ready');
            refreshSlickPosition($el);
            syncDotsVisibility($el, $wrap);
            requestAnimationFrame(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
            });
        }

        $el.on('init.fencingStylesSlick', function() {
            // Defer until Slick has attached $node.data('slick'); avoids setPosition during half-built instance.
            requestAnimationFrame(function() {
                revealAndSync();
            });
        });

        $el.on('breakpoint.fencingStylesSlick', function() {
            refreshSlickPosition($el);
            requestAnimationFrame(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
            });
        });

        $el.slick({
            mobileFirst: true,
            infinite: true,
            slidesToShow: 2,
            slidesToScroll: 2,
            dots: false,
            arrows: true,
            prevArrow: arrowPrev,
            nextArrow: arrowNext,
            adaptiveHeight: false,
            rows: 1,
            slidesPerRow: 1,
            respondTo: 'window',
            responsive: [
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 4,
                        dots: true
                    }
                },
                {
                    breakpoint: 990,
                    settings: {
                        slidesToShow: 6,
                        slidesToScroll: 6,
                        dots: true
                    }
                }
            ]
        });

        $(window).on('load.fencingStylesSlick', function() {
            refreshSlickPosition($el);
            requestAnimationFrame(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
            });
        });

        $(window).on('resize.fencingStylesSlick orientationchange.fencingStylesSlick', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
            }, 150);
        });
    }

    $(function() {
        initFencingStylesSlick();
    });
})(jQuery);
