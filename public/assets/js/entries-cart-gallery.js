/**
 * FC Admin — Planner entry cart item image gallery.
 */
(function () {
    'use strict';

    var BODY_CLASS = 'fc-entries-cart-gallery-open';
    var galleryEl = null;
    var keydownHandler = null;
    var state = {
        slides: [],
        index: 0,
    };

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

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

    function scrollActiveThumbIntoView() {
        if (!galleryEl) {
            return;
        }

        var thumbsEl = galleryEl.querySelector('[data-fc-cart-gallery-thumbs]');
        if (!thumbsEl || thumbsEl.hidden) {
            return;
        }

        var activeThumb = thumbsEl.querySelector('[data-fc-cart-gallery-thumb].is-active');
        if (!activeThumb) {
            return;
        }

        activeThumb.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest',
        });
    }

    function renderThumbs() {
        if (!galleryEl) {
            return;
        }

        var thumbsEl = galleryEl.querySelector('[data-fc-cart-gallery-thumbs]');
        if (!thumbsEl) {
            return;
        }

        if (state.slides.length <= 1) {
            thumbsEl.hidden = true;
            thumbsEl.innerHTML = '';
            return;
        }

        thumbsEl.hidden = false;
        thumbsEl.innerHTML = state.slides.map(function (slide, index) {
            var active = index === state.index ? ' is-active' : '';
            return (
                '<button type="button" class="fc-entries-cart-gallery__thumb' + active + '" data-fc-cart-gallery-thumb="' + index + '" aria-label="View image ' + (index + 1) + ' of ' + state.slides.length + '">' +
                '<img src="' + escapeHtml(slide.url) + '" alt="" loading="lazy" decoding="async">' +
                '</button>'
            );
        }).join('');

        requestAnimationFrame(scrollActiveThumbIntoView);
    }

    function renderSlide() {
        if (!galleryEl || state.slides.length === 0) {
            return;
        }

        var slide = state.slides[state.index];
        var imageEl = galleryEl.querySelector('[data-fc-cart-gallery-image]');
        var captionEl = galleryEl.querySelector('[data-fc-cart-gallery-caption]');
        var counterEl = galleryEl.querySelector('[data-fc-cart-gallery-counter]');
        var prevBtn = galleryEl.querySelector('[data-fc-cart-gallery-prev]');
        var nextBtn = galleryEl.querySelector('[data-fc-cart-gallery-next]');

        if (imageEl) {
            imageEl.src = slide.url;
            imageEl.alt = slide.caption || 'Product image';
        }

        if (captionEl) {
            captionEl.textContent = slide.caption || '';
            captionEl.hidden = !slide.caption;
        }

        if (counterEl) {
            counterEl.textContent = (state.index + 1) + ' / ' + state.slides.length;
            counterEl.hidden = state.slides.length <= 1;
        }

        if (prevBtn) {
            prevBtn.disabled = state.slides.length <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = state.slides.length <= 1;
        }

        galleryEl.querySelectorAll('[data-fc-cart-gallery-thumb]').forEach(function (btn) {
            var thumbIndex = parseInt(btn.getAttribute('data-fc-cart-gallery-thumb') || '-1', 10);
            btn.classList.toggle('is-active', thumbIndex === state.index);
        });

        scrollActiveThumbIntoView();
    }

    function closeGallery() {
        if (!galleryEl) {
            return;
        }

        if (keydownHandler) {
            document.removeEventListener('keydown', keydownHandler);
            keydownHandler = null;
        }

        galleryEl.remove();
        galleryEl = null;
        state.slides = [];
        state.index = 0;
        document.body.classList.remove(BODY_CLASS);
    }

    function showSlide(index) {
        if (!state.slides.length) {
            return;
        }

        if (index < 0) {
            index = state.slides.length - 1;
        } else if (index >= state.slides.length) {
            index = 0;
        }

        state.index = index;
        renderSlide();
    }

    function openGallery(slides, startIndex) {
        if (!slides.length) {
            return;
        }

        closeGallery();

        state.slides = slides;
        state.index = Math.max(0, Math.min(startIndex, slides.length - 1));

        galleryEl = document.createElement('div');
        galleryEl.className = 'fc-entries-cart-gallery';
        galleryEl.setAttribute('role', 'dialog');
        galleryEl.setAttribute('aria-modal', 'true');
        galleryEl.setAttribute('aria-label', 'Cart item images');
        galleryEl.innerHTML =
            '<div class="fc-entries-cart-gallery__backdrop" data-fc-cart-gallery-close aria-hidden="true"></div>' +
            '<button type="button" class="fencing-modal-close" data-fc-cart-gallery-close aria-label="Close"></button>' +
            '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--prev" data-fc-cart-gallery-prev aria-label="Previous image">' +
            '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>' +
            '</button>' +
            '<div class="fc-entries-cart-gallery__stage">' +
            '<img class="fc-entries-cart-gallery__image" data-fc-cart-gallery-image src="" alt="">' +
            '<p class="fc-entries-cart-gallery__caption" data-fc-cart-gallery-caption hidden></p>' +
            '<span class="fc-entries-cart-gallery__counter" data-fc-cart-gallery-counter hidden></span>' +
            '</div>' +
            '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--next" data-fc-cart-gallery-next aria-label="Next image">' +
            '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>' +
            '</button>' +
            '<div class="fc-entries-cart-gallery__thumbs" data-fc-cart-gallery-thumbs hidden></div>';

        document.body.appendChild(galleryEl);
        document.body.classList.add(BODY_CLASS);

        galleryEl.querySelectorAll('[data-fc-cart-gallery-close]').forEach(function (btn) {
            btn.addEventListener('click', closeGallery);
        });

        var prevBtn = galleryEl.querySelector('[data-fc-cart-gallery-prev]');
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                showSlide(state.index - 1);
            });
        }

        var nextBtn = galleryEl.querySelector('[data-fc-cart-gallery-next]');
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                showSlide(state.index + 1);
            });
        }

        galleryEl.addEventListener('click', function (e) {
            var thumbBtn = e.target.closest('[data-fc-cart-gallery-thumb]');
            if (!thumbBtn) {
                return;
            }
            e.preventDefault();
            showSlide(parseInt(thumbBtn.getAttribute('data-fc-cart-gallery-thumb') || '0', 10));
        });

        keydownHandler = function (e) {
            if (!galleryEl) {
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                closeGallery();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                showSlide(state.index - 1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                showSlide(state.index + 1);
            }
        };

        document.addEventListener('keydown', keydownHandler);
        renderThumbs();
        renderSlide();

        var closeBtn = galleryEl.querySelector('.fencing-modal-close');
        if (closeBtn) {
            closeBtn.focus();
        }
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

            openGallery(slides, startIndex < 0 ? 0 : startIndex);
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
