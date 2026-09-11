/**
 * Planner Step 3: detached scrollbar for the fence drawing strip.
 *
 * The strip (`.fc-project-plan-hscroll`) scrolls horizontally, but its native scrollbar rendered
 * between the drawing and the Overall line, nowhere near the control buttons the eye is on. CSS
 * hides the strip's own bar (planner Step 3 only) and this module mirrors the strip into
 * `.js-fc-hscroll-proxy` — an empty scroll container placed under the control buttons — whose
 * inner width is sized so both scroll ranges are IDENTICAL (scrollWidth - clientWidth), making
 * the two scrollLeft values copy across 1:1 with no proportional maths.
 *
 * Shared file beside hscroll-fade.js, which watches the same strip; it no-ops on pages without
 * the proxy markup (project plan, lookup).
 */
(function fcHScrollProxy() {
    var proxy = document.querySelector('.js-fc-hscroll-proxy');

    if (!proxy) {
        return;
    }

    var section = proxy.closest('.js-fc-form-step[data-section="3"]');
    var strip = section ? section.querySelector('.fencing-display-result .fc-project-plan-hscroll') : null;
    var inner = proxy.querySelector('.fc-hscroll-proxy__inner');

    if (!strip || !inner) {
        return;
    }

    /* Assigning an equal scrollLeft fires no scroll event, so the echo between the two sides
       dies out on its own; the sub-pixel guard stops a fractional position bouncing once more. */
    function follow(from, to) {
        if (Math.abs(to.scrollLeft - from.scrollLeft) >= 1) {
            to.scrollLeft = from.scrollLeft;
        }
    }

    /* Equal-range sizing: inner = the strip's overflow plus the proxy's own viewport, so the
       maximum scrollLeft is the same on both sides. Un-hide before measuring — a display:none
       proxy reports clientWidth 0 and would size the range to nothing. */
    function resize() {
        var range = strip.scrollWidth - strip.clientWidth;

        proxy.classList.toggle('fc-hscroll-proxy--off', range <= 1);
        inner.style.width = (range + proxy.clientWidth) + 'px';
        follow(strip, proxy);
    }

    strip.addEventListener('scroll', function() {
        follow(strip, proxy);
    }, { passive: true });

    proxy.addEventListener('scroll', function() {
        follow(proxy, strip);
    }, { passive: true });

    /* Recalculate redraws the drawing inside .fc-result and a zoom step rescales it in place —
       one mutates the child list, the other only styles. Watching the two box sizes catches
       both, where hscroll-fade.js's childList observer misses the zoom. */
    if (typeof ResizeObserver === 'function') {
        var queued = false;
        var observer = new ResizeObserver(function() {
            if (queued) {
                return;
            }
            queued = true;
            window.requestAnimationFrame(function() {
                queued = false;
                resize();
            });
        });

        observer.observe(strip);

        var result = strip.querySelector('.fc-result');

        if (result) {
            observer.observe(result);
        }
    }

    window.addEventListener('resize', resize);
    resize();
})();
