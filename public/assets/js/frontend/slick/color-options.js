/**
 * Colour options carousel (Slick) — planner Step 4 + project-plan submit modal (#submit-modal).
 * Planner: mobileFirst 2 / >767 → 4 / >990 → 6.
 * Project-plan modal: 2 mobile / 4 tablet & desktop (matches project-plan colour carousel).
 */
(function($) {
    'use strict';

    var SELECTOR =
        '.fc-planner-page .js-fc-color-options-slick, #submit-modal.fencing-modal--project-plans .js-fc-color-options-slick';

    var resizeTimer;

    function sliderWrapFromSlider($slider) {
        var $wrap = $slider.closest('.fc-color-options-slick-area');
        return $wrap.length ? $wrap : $slider.parent();
    }

    function isProjectPlansSubmitModalSlider($slider) {
        return $slider.closest('#submit-modal.fencing-modal--project-plans').length > 0;
    }

    /** Initialise / refresh only when the slider’s container is visible (planner tab vs modal open). */
    function sliderAllowedForInit($slider) {
        if (isProjectPlansSubmitModalSlider($slider)) {
            return $('#submit-modal').is(':visible');
        }
        var $step = $slider.closest('.fc-section-step[data-tab="2"]');
        return $step.length ? $step.is(':visible') : true;
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

    function syncDotsVisibility($slider, $wrap) {
        if (!$slider || !$slider.length || !$wrap || !$wrap.length) {
            return;
        }
        if (!$slider.hasClass('slick-initialized')) {
            return;
        }
        var dotCount = $slider.find('.slick-dots li').length;
        if (dotCount <= 1) {
            $wrap.addClass('fc-color-options-slick-single-page');
        } else {
            $wrap.removeClass('fc-color-options-slick-single-page');
        }
    }

    /**
     * Project-plan submit modal (#submit-modal): when more colour tiles than fit in one view,
     * move the carousel so the `.fc-selected` tile sits near the horizontal centre.
     * Uses non-cloned `.fc-color-options__slide` indices (Slick infinite clones break `.slick-slide` indexing).
     */
    function fcSubmitModalColorSlickCenterSelectedIfPaged($slider) {
        if (!isProjectPlansSubmitModalSlider($slider) || !$slider.hasClass('slick-initialized')) {
            return;
        }

        var slick = $slider.data('slick');
        if (!slick || typeof slick.slideCount !== 'number' || slick.slideCount < 1) {
            return;
        }

        var count = slick.slideCount;
        var k = slick.options.slidesToShow || 2;
        if (count <= k) {
            return;
        }

        var $selected = $slider
            .find('.fc-select-color.fc-selected')
            .filter(function() {
                return $(this).closest('.slick-cloned').length === 0;
            })
            .first();
        if (!$selected.length) {
            return;
        }

        var $slide = $selected.closest('.slick-slide');
        if (!$slide.length || $slide.hasClass('slick-cloned')) {
            return;
        }

        var i = -1;
        var slideIdxAttr = $slide.attr('data-slick-index');
        if (slideIdxAttr !== undefined && slideIdxAttr !== null && slideIdxAttr !== '') {
            var parsed = parseInt(slideIdxAttr, 10);
            if (!isNaN(parsed) && parsed >= 0 && parsed < count) {
                i = parsed;
            }
        }
        if (i < 0) {
            var $colorSlide = $selected.closest('.fc-color-options__slide');
            var $origColorSlides = $slider.find('.fc-color-options__slide').filter(function() {
                return $(this).closest('.slick-cloned').length === 0;
            });
            i = $origColorSlides.index($colorSlide);
        }
        if (i < 0 || i >= count) {
            return;
        }

        /* Centre selected in the visible window: e.g. k=4 → offset 1 so slide sits in middle pair. */
        var half = Math.floor((k - 1) / 2);
        var goTo = i - half;
        if (goTo < 0) {
            goTo = 0;
        }
        var maxGo = Math.max(0, count - k);
        if (goTo > maxGo) {
            goTo = maxGo;
        }

        try {
            $slider.slick('slickGoTo', goTo, true);
        } catch (e1) {
            try {
                $slider.slick('slickGoTo', goTo);
            } catch (e2) {
                /* ignore */
            }
        }

        requestAnimationFrame(function() {
            refreshSlickPosition($slider);
        });
    }

    /** Modal layout/dots can settle after init — re-run centre a few times so the selected tile ends up mid-track. */
    function scheduleSubmitModalCenterSelected($slider) {
        if (!isProjectPlansSubmitModalSlider($slider)) {
            return;
        }
        [0, 80, 220, 400].forEach(function(ms) {
            window.setTimeout(function() {
                fcSubmitModalColorSlickCenterSelectedIfPaged($slider);
            }, ms);
        });
    }

    function initOne($el, $wrap) {
        if ($el.hasClass('slick-initialized')) {
            return;
        }

        $wrap.addClass('fc-color-options-slick-pending');

        var isModal = $wrap.closest('#submit-modal.fencing-modal--project-plans').length > 0;

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

        var revealFallback = setTimeout(function() {
            $wrap.addClass('fc-color-options-slick-ready');
            $wrap.find('.js-fc-modal-color-options-skeleton').attr({ 'aria-busy': 'false' });
        }, 2500);

        function revealAndSync() {
            clearTimeout(revealFallback);
            $wrap.addClass('fc-color-options-slick-ready');
            $wrap.find('.js-fc-modal-color-options-skeleton').attr({ 'aria-busy': 'false' });
            refreshSlickPosition($el);
            syncDotsVisibility($el, $wrap);
            requestAnimationFrame(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
                if (isModal) {
                    requestAnimationFrame(function() {
                        scheduleSubmitModalCenterSelected($el);
                    });
                }
            });
        }

        $el.on('init.fcColorOptionsSlick', function() {
            requestAnimationFrame(function() {
                revealAndSync();
            });
        });

        $el.on('breakpoint.fcColorOptionsSlick', function() {
            refreshSlickPosition($el);
            requestAnimationFrame(function() {
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
                if (isModal) {
                    requestAnimationFrame(function() {
                        scheduleSubmitModalCenterSelected($el);
                    });
                }
            });
        });

        var responsivePlanner = [
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
        ];

        var responsiveModal = [
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    infinite: true
                }
            }
        ];

        $el.slick({
            mobileFirst: true,
            infinite: true,
            slidesToShow: 2,
            slidesToScroll: 2,
            dots: isModal,
            arrows: true,
            appendArrows: $wrap,
            prevArrow: arrowPrev,
            nextArrow: arrowNext,
            adaptiveHeight: false,
            rows: 1,
            slidesPerRow: 1,
            respondTo: 'window',
            responsive: isModal ? responsiveModal : responsivePlanner
        });
    }

    window.fcRefreshColorOptionsSlick = function() {
        $(SELECTOR).each(function() {
            var $el = $(this);
            if (!$el.length || typeof $.fn.slick !== 'function') {
                return;
            }

            var $wrap = sliderWrapFromSlider($el);

            if ($el.hasClass('slick-initialized')) {
                if (!sliderAllowedForInit($el)) {
                    return;
                }
                $wrap.addClass('fc-color-options-slick-ready');
                refreshSlickPosition($el);
                syncDotsVisibility($el, $wrap);
                requestAnimationFrame(function() {
                    refreshSlickPosition($el);
                    syncDotsVisibility($el, $wrap);
                    if (isProjectPlansSubmitModalSlider($el)) {
                        requestAnimationFrame(function() {
                            scheduleSubmitModalCenterSelected($el);
                        });
                    }
                });
                return;
            }

            if (!sliderAllowedForInit($el)) {
                return;
            }

            initOne($el, $wrap);
        });
    };

    $(function() {
        $(window).on('load.fcColorOptionsSlick', function() {
            $(SELECTOR).each(function() {
                var $el = $(this);
                if ($el.hasClass('slick-initialized')) {
                    refreshSlickPosition($el);
                    syncDotsVisibility($el, sliderWrapFromSlider($el));
                }
            });
        });

        $(window).on('resize.fcColorOptionsSlick orientationchange.fcColorOptionsSlick', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                $(SELECTOR).each(function() {
                    var $el = $(this);
                    if ($el.hasClass('slick-initialized')) {
                        refreshSlickPosition($el);
                        var $w = sliderWrapFromSlider($el);
                        syncDotsVisibility($el, $w);
                        if (isProjectPlansSubmitModalSlider($el)) {
                            fcSubmitModalColorSlickCenterSelectedIfPaged($el);
                        }
                    }
                });
            }, 150);
        });

        $(document).on(
            'click',
            '#submit-modal.fencing-modal--project-plans .fc-select-color',
            function() {
                var $slider = $(this).closest('.js-fc-color-options-slick');
                if (!$slider.length) {
                    return;
                }
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        scheduleSubmitModalCenterSelected($slider);
                    });
                });
            }
        );

        if (typeof window.fcRefreshColorOptionsSlick === 'function') {
            window.fcRefreshColorOptionsSlick();
        }
    });
})(jQuery);
