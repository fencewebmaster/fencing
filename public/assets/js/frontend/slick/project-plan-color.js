/**
 * Project plan (/project-plan) — Color Options carousel (Slick).
 * mobileFirst: 1 full-width slide, dots only; width > 767 → 4 slides with arrows.
 * Phones get no arrows: they sit outside the track (left/right: -30px) so they overhang a
 * narrow viewport, and a swipe plus dots is the expected affordance on touch. Dropping them
 * also frees the 96px the dot row reserved for them.
 * Infinite loop for continuous prev/next.
 */
(function($) {
    'use strict';

    var SELECTOR = '.fc-project-plan-page .js-fc-project-plan-color-slick';

    var resizeTimer;

    function sliderWrapFromSlider($slider) {
        var $wrap = $slider.closest('.fc-project-plan-color-slick-area');
        return $wrap.length ? $wrap : $slider.parent();
    }

    function refreshSlickPosition($slider) {
        if (!$slider || !$slider.length || typeof $.fn.slick !== 'function') {
            return;
        }
        $slider.each(function() {
            var $node = $(this);
            if (!$node.hasClass('slick-initialized')) {
                return;
            }
            try {
                // Slick keeps its instance on the DOM element (el.slick), never in jQuery's data
                // store — the old `$node.data('slick')` guard was always false, so setPosition
                // never ran and a breakpoint change left the track on the old slide width.
                if (!$node.slick('getSlick')) {
                    return;
                }
                $node.slick('setPosition');
            } catch (e) {
                /* noop */
            }
        });
    }

    function syncDotsVisibility($slider, $wrap) {
        if (!$slider || !$slider.length || !$wrap || !$wrap.length) {
            return;
        }
        if (!$slider.hasClass('slick-initialized')) {
            return;
        }
        var dotCount = $slider.find('.slick-dots li').length;
        if (dotCount <= 1) {
            $wrap.addClass('fc-project-plan-color-slick-single-page');
        } else {
            $wrap.removeClass('fc-project-plan-color-slick-single-page');
        }
    }

    function initOne($el, $wrap) {
        if ($el.hasClass('slick-initialized')) {
            return;
        }

        if (!$el.children().length) {
            $wrap.addClass('fc-project-plan-color-slick-ready');
            $wrap.find('.js-fc-project-plan-color-skeleton').attr({ 'aria-busy': 'false' });
            return;
        }

        var arrowPrev =
            '<button type="button" class="slick-prev fencing-styles-arrow" aria-label="Previous colours">' +
            '<span class="fencing-styles-arrow__inner">' +
            '<i class="fa-solid fa-chevron-left fencing-styles-arrow__icon" aria-hidden="true"></i>' +
            '</span></button>';
        var arrowNext =
            '<button type="button" class="slick-next fencing-styles-arrow" aria-label="Next colours">' +
            '<span class="fencing-styles-arrow__inner">' +
            '<i class="fa-solid fa-chevron-right fencing-styles-arrow__icon" aria-hidden="true"></i>' +
            '</span></button>';

        $wrap.addClass('fc-project-plan-color-slick-pending');

        var revealFallback = setTimeout(function() {
            $wrap.addClass('fc-project-plan-color-slick-ready');
            $wrap.find('.js-fc-project-plan-color-skeleton').attr({ 'aria-busy': 'false' });
        }, 2500);

        function revealAndSync() {
            clearTimeout(revealFallback);
            $wrap.addClass('fc-project-plan-color-slick-ready');
            $wrap.find('.js-fc-project-plan-color-skeleton').attr({ 'aria-busy': 'false' });
            refreshSlickPosition($el);
            syncDotsVisibility($el, $wrap);
            requestAnimationFrame(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
            });
        }

        $el.on('init.fcProjectPlanColorSlick', function() {
            requestAnimationFrame(revealAndSync);
        });

        $el.on('breakpoint.fcProjectPlanColorSlick', function() {
            refreshSlickPosition($el);
            requestAnimationFrame(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
            });
        });

        $el.slick({
            mobileFirst: true,
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            dots: true,
            arrows: false,
            appendArrows: $wrap,
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
                        infinite: true,
                        dots: true,
                        arrows: true
                    }
                }
            ]
        });
    }

    window.fcRefreshProjectPlanColorSlick = function() {
        $(SELECTOR).each(function() {
            var $el = $(this);
            if (!$el.length || typeof $.fn.slick !== 'function') {
                return;
            }
            var $wrap = sliderWrapFromSlider($el);
            if ($el.hasClass('slick-initialized')) {
                $wrap.addClass('fc-project-plan-color-slick-ready');
                $wrap.find('.js-fc-project-plan-color-skeleton').attr({ 'aria-busy': 'false' });
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
                requestAnimationFrame(function() {
                    refreshSlickPosition($el);
                    syncDotsVisibility($el, $wrap);
                });
                return;
            }
            initOne($el, $wrap);
        });
    };

    $(function() {
        window.fcRefreshProjectPlanColorSlick();

        $(window).on('load.fcProjectPlanColorSlick', function() {
            refreshSlickPosition($(SELECTOR));
            $(SELECTOR).each(function() {
                syncDotsVisibility($(this), sliderWrapFromSlider($(this)));
            });
        });

        $(window).on('resize.fcProjectPlanColorSlick orientationchange.fcProjectPlanColorSlick', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                $(SELECTOR).each(function() {
                    var $el = $(this);
                    if ($el.hasClass('slick-initialized')) {
                        refreshSlickPosition($el);
                        syncDotsVisibility($el, sliderWrapFromSlider($el));
                    }
                });
            }, 150);
        });
    });
})(jQuery);
