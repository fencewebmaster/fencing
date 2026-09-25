/**
 * Lazy images for the public pages. Markup:
 *   <span class="fc-lazy-thumb is-loading"><img src="(1px gif)" data-fc-lazy-src="/real.jpg" alt=""></span>
 * The image loads once it is within 200px of the viewport; the frame shimmers until it arrives, then the
 * image fades in. Rows added later (the project plan's cart table is re-rendered by AJAX) are picked up too.
 *
 * FCLazyImages.loadAll(root) loads every pending image under root now and resolves once they have all
 * settled, for screenshots and PDFs that must not capture an empty frame.
 */
(function (global) {
    'use strict';

    var SELECTOR = 'img[data-fc-lazy-src]';
    var SETTLE_TIMEOUT_MS = 8000;

    function frameOf(img) {
        return img.closest ? img.closest('.fc-lazy-thumb') : null;
    }

    function settle(img, ok) {
        var frame = frameOf(img);
        if (frame) {
            frame.classList.remove('is-loading');
            frame.classList.toggle('is-broken', !ok);
        }
    }

    /** Starts one image (once) and returns a promise that settles when it has loaded or failed. */
    function load(img) {
        if (img.__fcLazyPromise) {
            return img.__fcLazyPromise;
        }
        var src = img.getAttribute('data-fc-lazy-src');
        img.__fcLazyPromise = new Promise(function (resolve) {
            var done = function (ok) {
                settle(img, ok);
                resolve();
            };
            img.addEventListener('load', function () {
                done(true);
            }, { once: true });
            img.addEventListener('error', function () {
                done(false);
            }, { once: true });
        });
        img.removeAttribute('data-fc-lazy-src');
        if (src) {
            img.src = src;
        }
        return img.__fcLazyPromise;
    }

    var observer = typeof global.IntersectionObserver === 'function'
        ? new global.IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    observer.unobserve(entry.target);
                    load(entry.target);
                }
            });
        }, { rootMargin: '200px 0px' })
        : null;

    function watch(img) {
        if (img.__fcLazyWatched) {
            return;
        }
        img.__fcLazyWatched = true;
        if (observer) {
            observer.observe(img);
        } else {
            load(img);
        }
    }

    function scan(root) {
        var scope = root && root.querySelectorAll ? root : document;
        if (scope.matches && scope.matches(SELECTOR)) {
            watch(scope);
        }
        Array.prototype.forEach.call(scope.querySelectorAll(SELECTOR), watch);
    }

    function loadAll(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var pending = Array.prototype.map.call(scope.querySelectorAll('img'), function (img) {
            if (img.hasAttribute('data-fc-lazy-src')) {
                if (observer) {
                    observer.unobserve(img);
                }
                return load(img);
            }
            return img.__fcLazyPromise || Promise.resolve();
        });
        // A stalled image must not hold an export forever.
        return Promise.race([
            Promise.all(pending),
            new Promise(function (resolve) {
                setTimeout(resolve, SETTLE_TIMEOUT_MS);
            })
        ]);
    }

    function start() {
        scan(document);
        if (typeof global.MutationObserver === 'function') {
            new global.MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                        if (node.nodeType === 1) {
                            scan(node);
                        }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    global.FCLazyImages = { scan: scan, loadAll: loadAll };
})(window);
