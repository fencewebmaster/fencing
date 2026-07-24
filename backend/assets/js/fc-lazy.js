/**
 * FC Admin — Reusable lazy image loader.
 *
 * Standard markup:
 *   <img class="fc-lazy" data-fc-lazy data-fc-lazy-src="/path/image.jpg" alt="" decoding="async">
 *
 * Optional container hook (auto-inits on load):
 *   <div data-fc-lazy-root>…images…</div>
 *
 * API:
 *   FcLazy.refresh(container, options?) — observe pending images inside container
 *   FcLazy.reveal(img)                 — force-load a single image
 */
(function (global) {
    'use strict';

    var PENDING_SELECTOR = 'img[data-fc-lazy]:not([data-fc-lazy-done])';
    var SRC_ATTR = 'data-fc-lazy-src';
    var DONE_ATTR = 'data-fc-lazy-done';
    var ROOT_ATTR = 'data-fc-lazy-root';
    var LAZY_CLASS = 'fc-lazy';
    var LOADED_CLASS = 'is-loaded';
    var INSTANT_CLASS = 'fc-lazy--instant';
    var FRAME_CLASS = 'fc-lazy-frame';
    var FRAME_LOADING_CLASS = 'is-loading';
    var DEFAULT_ROOT_MARGIN = '250px 0px';
    var DEFAULT_THRESHOLD = 0.01;

    var observers = new WeakMap();

    function frameOf(img) {
        return img ? img.parentElement : null;
    }

    function startFrame(img) {
        var frame = frameOf(img);
        if (!frame) {
            return;
        }
        frame.classList.add(FRAME_CLASS, FRAME_LOADING_CLASS);
        // Ensure the skeleton overlay has a positioning context without
        // disturbing frames that are already positioned.
        var position = global.getComputedStyle(frame).position;
        if (position === 'static') {
            frame.style.position = 'relative';
            frame.setAttribute('data-fc-lazy-pos', '1');
        }
    }

    function stopFrame(img) {
        var frame = frameOf(img);
        if (!frame) {
            return;
        }
        frame.classList.remove(FRAME_LOADING_CLASS);
        if (frame.getAttribute('data-fc-lazy-pos') === '1') {
            frame.style.position = '';
            frame.removeAttribute('data-fc-lazy-pos');
        }
    }

    function markLoaded(img, instant) {
        if (instant) {
            img.classList.add(INSTANT_CLASS);
        }
        img.classList.add(LOADED_CLASS);
        stopFrame(img);
    }

    function reveal(img) {
        if (!img || img.getAttribute(DONE_ATTR) === '1') {
            return;
        }

        img.setAttribute(DONE_ATTR, '1');

        var src = img.getAttribute(SRC_ATTR);
        if (!src) {
            markLoaded(img, true);
            return;
        }

        img.addEventListener(
            'load',
            function () {
                markLoaded(img, false);
            },
            { once: true }
        );
        img.addEventListener(
            'error',
            function () {
                markLoaded(img, true);
            },
            { once: true }
        );

        img.src = src;
        img.removeAttribute(SRC_ATTR);

        // Browser cache hit — show immediately without fade.
        if (img.complete && img.naturalWidth > 0) {
            markLoaded(img, true);
        }
    }

    function resolveRoot(container, options) {
        options = options || {};

        if (options.root) {
            return options.root;
        }

        if (container && container.hasAttribute && container.hasAttribute(ROOT_ATTR)) {
            return container;
        }

        if (options.rootSelector) {
            if (container.matches && container.matches(options.rootSelector)) {
                return container;
            }
            var nested = container.querySelector(options.rootSelector);
            if (nested) {
                return nested;
            }
        }

        var marked = container.querySelector('[' + ROOT_ATTR + ']');
        return marked || null;
    }

    function disconnectContainer(container) {
        var observer = observers.get(container);
        if (!observer) {
            return;
        }
        observer.disconnect();
        observers.delete(container);
    }

    function refresh(container, options) {
        if (!container) {
            return;
        }

        options = options || {};
        var selector = options.selector || PENDING_SELECTOR;
        var rootMargin = options.rootMargin || DEFAULT_ROOT_MARGIN;
        var threshold =
            options.threshold !== undefined && options.threshold !== null
                ? options.threshold
                : DEFAULT_THRESHOLD;
        var root = resolveRoot(container, options);

        disconnectContainer(container);

        var imgs = container.querySelectorAll(selector);
        if (!imgs.length) {
            return;
        }

        if (!('IntersectionObserver' in global)) {
            Array.prototype.forEach.call(imgs, reveal);
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    reveal(entry.target);
                    observer.unobserve(entry.target);
                });
            },
            {
                root: root,
                rootMargin: rootMargin,
                threshold: threshold
            }
        );

        Array.prototype.forEach.call(imgs, function (img) {
            if (!img.classList.contains(LAZY_CLASS)) {
                img.classList.add(LAZY_CLASS);
            }
            startFrame(img);
            observer.observe(img);
        });

        observers.set(container, observer);
    }

    function initMarkedRoots() {
        document.querySelectorAll('[' + ROOT_ATTR + ']').forEach(function (root) {
            refresh(root, { root: root });
        });
    }

    global.FcLazy = {
        refresh: refresh,
        reveal: reveal
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMarkedRoots);
    } else {
        initMarkedRoots();
    }
})(window);
