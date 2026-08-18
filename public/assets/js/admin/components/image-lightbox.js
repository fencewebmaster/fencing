/**
 * FC Admin — image lightbox/carousel modal.
 * Extracted from the near-100% duplicate implementation shared between
 * entries/cart-gallery.js and the gallery block inside
 * products/system-products.js (same markup, CSS classes, and
 * data-fc-cart-gallery-* attribute names in both). Gathering slides and
 * binding open-triggers stays with each caller since those genuinely
 * differ (DOM-scanned buttons with per-slide captions vs. a JSON array of
 * URLs sharing one caption) — this class owns only the modal's lifecycle.
 */
(function (global) {
    'use strict';

    var FC = global.FC = global.FC || {};
    FC.components = FC.components || {};

    class ImageLightbox {
        /**
         * @param {Object} [options]
         * @param {string} [options.bodyOpenClass] class added to <body> while open
         * @param {boolean} [options.closeOnBackdrop] whether clicking the backdrop
         *   closes the lightbox (default true)
         * @param {boolean} [options.showNav] whether to render prev/next buttons
         *   (default true) — set false for single-image-only usage, since a
         *   disabled nav button still renders faded rather than being removed
         * @param {string} [options.imageAltFallback] alt text used when a slide
         *   has no caption (default 'Product image')
         */
        constructor(options) {
            options = options || {};
            this.bodyOpenClass = options.bodyOpenClass || 'fc-entries-cart-gallery-open';
            this.closeOnBackdrop = options.closeOnBackdrop !== false;
            this.showNav = options.showNav !== false;
            this.imageAltFallback = options.imageAltFallback != null ? options.imageAltFallback : 'Product image';
            this.el = null;
            this.keydownHandler = null;
            this.slides = [];
            this.index = 0;
        }

        _scrollActiveThumbIntoView() {
            if (!this.el) {
                return;
            }
            var thumbsEl = this.el.querySelector('[data-fc-cart-gallery-thumbs]');
            if (!thumbsEl || thumbsEl.hidden) {
                return;
            }
            var activeThumb = thumbsEl.querySelector('[data-fc-cart-gallery-thumb].is-active');
            if (!activeThumb) {
                return;
            }
            activeThumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }

        _renderThumbs() {
            if (!this.el) {
                return;
            }
            var thumbsEl = this.el.querySelector('[data-fc-cart-gallery-thumbs]');
            if (!thumbsEl) {
                return;
            }
            var slides = this.slides;
            var activeIndex = this.index;
            var escapeHtml = FC.util.escapeHtml;

            if (slides.length <= 1) {
                thumbsEl.hidden = true;
                thumbsEl.innerHTML = '';
                return;
            }

            thumbsEl.hidden = false;
            thumbsEl.innerHTML = slides.map(function (slide, index) {
                var active = index === activeIndex ? ' is-active' : '';
                return (
                    '<button type="button" class="fc-entries-cart-gallery__thumb' + active + '" data-fc-cart-gallery-thumb="' + index + '" aria-label="View image ' + (index + 1) + ' of ' + slides.length + '">' +
                    '<img src="' + escapeHtml(slide.url) + '" alt="" loading="lazy" decoding="async">' +
                    '</button>'
                );
            }).join('');

            var self = this;
            requestAnimationFrame(function () {
                self._scrollActiveThumbIntoView();
            });
        }

        _renderSlide() {
            if (!this.el || !this.slides.length) {
                return;
            }
            var slide = this.slides[this.index];
            var imageEl = this.el.querySelector('[data-fc-cart-gallery-image]');
            var captionEl = this.el.querySelector('[data-fc-cart-gallery-caption]');
            var counterEl = this.el.querySelector('[data-fc-cart-gallery-counter]');
            var prevBtn = this.el.querySelector('[data-fc-cart-gallery-prev]');
            var nextBtn = this.el.querySelector('[data-fc-cart-gallery-next]');

            if (imageEl) {
                imageEl.src = slide.url;
                imageEl.alt = slide.caption || this.imageAltFallback;
            }
            if (captionEl) {
                captionEl.textContent = slide.caption || '';
                captionEl.hidden = !slide.caption;
            }
            if (counterEl) {
                counterEl.textContent = (this.index + 1) + ' / ' + this.slides.length;
                counterEl.hidden = this.slides.length <= 1;
            }
            if (prevBtn) {
                prevBtn.disabled = this.slides.length <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = this.slides.length <= 1;
            }

            var activeIndex = this.index;
            this.el.querySelectorAll('[data-fc-cart-gallery-thumb]').forEach(function (btn) {
                var thumbIndex = parseInt(btn.getAttribute('data-fc-cart-gallery-thumb') || '-1', 10);
                btn.classList.toggle('is-active', thumbIndex === activeIndex);
            });

            this._scrollActiveThumbIntoView();
        }

        /** Closes the lightbox if open; safe to call when already closed. */
        close() {
            if (!this.el) {
                return;
            }
            if (this.keydownHandler) {
                document.removeEventListener('keydown', this.keydownHandler);
                this.keydownHandler = null;
            }
            this.el.remove();
            this.el = null;
            this.slides = [];
            this.index = 0;
            document.body.classList.remove(this.bodyOpenClass);
        }

        _showSlide(index) {
            if (!this.slides.length) {
                return;
            }
            if (index < 0) {
                index = this.slides.length - 1;
            } else if (index >= this.slides.length) {
                index = 0;
            }
            this.index = index;
            this._renderSlide();
        }

        /**
         * @param {Array<{url:string,caption?:string}>} slides
         * @param {number} startIndex
         * @param {Object} [options]
         * @param {string} [options.ariaLabel] dialog aria-label (defaults to the
         *   first slide's caption, or 'Images' if none)
         */
        open(slides, startIndex, options) {
            if (!slides || !slides.length) {
                return;
            }
            options = options || {};

            this.close();

            this.slides = slides;
            this.index = Math.max(0, Math.min(startIndex || 0, slides.length - 1));

            var ariaLabel = options.ariaLabel || slides[this.index].caption || 'Images';

            var el = document.createElement('div');
            el.className = 'fc-entries-cart-gallery';
            el.setAttribute('role', 'dialog');
            el.setAttribute('aria-modal', 'true');
            el.setAttribute('aria-label', ariaLabel);
            el.innerHTML =
                '<div class="fc-entries-cart-gallery__backdrop"' +
                (this.closeOnBackdrop ? ' data-fc-cart-gallery-close' : '') +
                ' aria-hidden="true"></div>' +
                '<button type="button" class="fencing-modal-close" data-fc-cart-gallery-close aria-label="Close"></button>' +
                (this.showNav
                    ? '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--prev" data-fc-cart-gallery-prev aria-label="Previous image">' +
                      '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>' +
                      '</button>'
                    : '') +
                '<div class="fc-entries-cart-gallery__stage">' +
                '<img class="fc-entries-cart-gallery__image" data-fc-cart-gallery-image src="" alt="">' +
                '<p class="fc-entries-cart-gallery__caption" data-fc-cart-gallery-caption hidden></p>' +
                '<span class="fc-entries-cart-gallery__counter" data-fc-cart-gallery-counter hidden></span>' +
                '</div>' +
                (this.showNav
                    ? '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--next" data-fc-cart-gallery-next aria-label="Next image">' +
                      '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>' +
                      '</button>'
                    : '') +
                '<div class="fc-entries-cart-gallery__thumbs" data-fc-cart-gallery-thumbs hidden></div>';

            document.body.appendChild(el);
            document.body.classList.add(this.bodyOpenClass);
            this.el = el;

            var self = this;

            el.querySelectorAll('[data-fc-cart-gallery-close]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.close();
                });
            });

            var prevBtn = el.querySelector('[data-fc-cart-gallery-prev]');
            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    self._showSlide(self.index - 1);
                });
            }

            var nextBtn = el.querySelector('[data-fc-cart-gallery-next]');
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    self._showSlide(self.index + 1);
                });
            }

            el.addEventListener('click', function (e) {
                var thumbBtn = e.target.closest('[data-fc-cart-gallery-thumb]');
                if (!thumbBtn) {
                    return;
                }
                e.preventDefault();
                self._showSlide(parseInt(thumbBtn.getAttribute('data-fc-cart-gallery-thumb') || '0', 10));
            });

            this.keydownHandler = function (e) {
                if (!self.el) {
                    return;
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    self.close();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    self._showSlide(self.index - 1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    self._showSlide(self.index + 1);
                }
            };
            document.addEventListener('keydown', this.keydownHandler);

            this._renderThumbs();
            this._renderSlide();

            var closeBtn = el.querySelector('.fencing-modal-close');
            if (closeBtn && options.focusClose !== false) {
                closeBtn.focus();
            }
        }
    }

    FC.components.ImageLightbox = ImageLightbox;
})(window);
