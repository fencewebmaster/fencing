/**
 * Planner Step 3: dimension line under the fence drawing — <—— 6,000 OVERALL ——> — as the
 * design-1 mockup draws it.
 *
 * The planner printed "6,000 Overall" as centred text below the scroll strip, which lined up
 * with nothing once a long run scrolled or zoomed. `.js-fc-dim` lives INSIDE the strip's
 * `.fc-result`, beside the zoomed `.fencing-panel-items`, and this module sizes it to the drawn
 * run — left edge of the first part to right edge of the last — measured from the rendered
 * boxes, so CSS `zoom` (HELPER.zooming) and every style's own markup (posts, spigots, raked
 * panels, gates) are covered without knowing their geometry.
 *
 * The text is mirrored from `.fc-overall`, which stays the single source: events.js writes the
 * number into `.d-oaw` and the style hooks write `.js-overall-label`. While the line shows,
 * `.fc-overall` is only visually hidden (the line itself is aria-hidden), so screen readers keep
 * reading it — and if anything here fails, the old text simply stays on screen.
 *
 * The figure sits at the centre of the part of the line that is on screen: the whole line when
 * it fits (its true centre), the visible stretch when a long run is scrolled.
 *
 * Shared file beside hscroll-proxy.js; it no-ops on pages without the markup.
 */
(function fcOverallDimension() {
    var dim = document.querySelector('.js-fc-dim');

    if (!dim) {
        return;
    }

    var result = dim.closest('.fc-result');
    var section = dim.closest('.js-fc-form-step');
    var items = result ? result.querySelector('.fencing-panel-items') : null;
    var run = items ? items.querySelector('.fencing-panel-container') : null;
    var overall = section ? section.querySelector('.fc-overall') : null;
    var label = dim.querySelector('.fc-dim__label');
    var anchor = dim.querySelector('.fc-dim__anchor');
    var strip = dim.closest('.fc-project-plan-hscroll');

    if (!run || !overall || !label) {
        return;
    }

    var num = label.querySelector('.fc-dim__num');
    var word = label.querySelector('.fc-dim__word');

    /* Room kept between the figure's tag and each arrowhead while the line is long enough. */
    var ARROW_CLEAR = 12;

    function textOf(el) {
        return el ? (el.textContent || '').replace(/\s+/g, ' ').trim() : '';
    }

    function setText(el, value) {
        if (el.textContent !== value) {
            el.textContent = value;
        }
    }

    function place() {
        if (!anchor || dim.classList.contains('fc-dim--off')) {
            return;
        }

        var line = dim.getBoundingClientRect();
        var from = line.left;
        var to = line.right;

        if (strip) {
            var viewLeft = strip.getBoundingClientRect().left + strip.clientLeft;

            from = Math.max(from, viewLeft);
            to = Math.min(to, viewLeft + strip.clientWidth);
        }

        var centre = (from + to) / 2;
        var half = label.offsetWidth / 2;
        var min = line.left + half + ARROW_CLEAR;
        var max = line.right - half - ARROW_CLEAR;

        if (min <= max) {
            centre = Math.min(Math.max(centre, min), max);
        }

        /* A relative offset from the anchor's natural spot, the line's centre. */
        var left = Math.round((centre - (line.left + line.right) / 2) * 2) / 2 + 'px';

        if (anchor.style.left !== left) {
            anchor.style.left = left;
        }
    }

    /* Neither is fence length: the spacers carry the centre-point numbers between parts, and the
       off-cut box is drawn beside the run. */
    var NOT_RUN = '.fencing-panel-spacing-number, .fencing-offcut';

    function sync() {
        var left = Infinity;
        var right = -Infinity;

        Array.prototype.forEach.call(run.children, function(el) {
            if (el.matches(NOT_RUN)) {
                return;
            }
            var box = el.getBoundingClientRect();
            if (box.width && box.height) {
                left = Math.min(left, box.left);
                right = Math.max(right, box.right);
            }
        });

        var text = (overall.textContent || '').replace(/\s+/g, ' ').trim();
        /* Before Calculate .d-oaw is empty and the text is just "Overall": no figure, no line. */
        var on = right > left && /\d/.test(text);

        dim.classList.toggle('fc-dim--off', !on);
        overall.classList.toggle('fc-overall--in-dim', on);

        if (!on) {
            return;
        }

        /* The line is a block in .fc-result's content box and is not zoomed itself, so the run's
           rendered (already zoomed) px apply to it unchanged. */
        var origin = result.getBoundingClientRect().left + result.clientLeft +
            (parseFloat(window.getComputedStyle(result).paddingLeft) || 0);
        var marginLeft = (left - origin) + 'px';
        var width = (right - left) + 'px';

        /* Writes only on change: the ResizeObserver below watches .fc-result, which this line sits in. */
        if (dim.style.marginLeft !== marginLeft) {
            dim.style.marginLeft = marginLeft;
        }
        if (dim.style.width !== width) {
            dim.style.width = width;
        }
        /* Figure and word go into their own spans so the number can carry the weight; a label
           without them just takes the whole string. */
        if (num && word) {
            setText(num, textOf(overall.querySelector('.d-oaw')));
            setText(word, textOf(overall.querySelector('.js-overall-label')));
        } else {
            setText(label, text);
        }

        place();
    }

    var queued = false;

    function queue() {
        if (queued) {
            return;
        }
        queued = true;
        window.requestAnimationFrame(function() {
            queued = false;
            sync();
        });
    }

    if (typeof MutationObserver === 'function') {
        /* Recalculate and section switches rebuild the run; zoom and the slat-scale classes restyle
           its wrapper; Calculate and style changes rewrite the Overall text. */
        new MutationObserver(queue).observe(run, { childList: true, subtree: true });
        new MutationObserver(queue).observe(items, { attributes: true, attributeFilter: ['style', 'class'] });
        new MutationObserver(queue).observe(overall, { childList: true, characterData: true, subtree: true });
    }

    /* Step 3 is display:none until the first Calculate, and a font swap or card resize moves
       nothing the mutation observers see. */
    if (typeof ResizeObserver === 'function') {
        var observer = new ResizeObserver(queue);

        observer.observe(result);
        observer.observe(run);
    }

    if (strip) {
        /* Scrolling moves the visible stretch but changes no size, so nothing above sees it. */
        strip.addEventListener('scroll', place, { passive: true });
    }

    window.addEventListener('resize', sync);
    sync();
})();
