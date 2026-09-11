/**
 * Planner Step 3 and the project plan: dimension line under the fence drawing
 * — <—— 6,000 OVERALL ——> — as the design-1 mockup draws it.
 *
 * The planner printed "6,000 Overall" as centred text below the scroll strip, which lined up
 * with nothing once a long run scrolled or zoomed. `.js-fc-dim` lives INSIDE the strip's
 * `.fc-result`, beside the run, and this module sizes it to the drawn run — left edge of the first
 * part to right edge of the last — measured from the rendered boxes, so CSS `zoom`
 * (HELPER.zooming) and every style's own markup (posts, spigots, raked panels, gates) are covered
 * without knowing their geometry. In a project-plan section the line hangs from `.fc-result`'s
 * foot instead of sitting in it (style.css), clear of the spacing and Centers labels.
 *
 * The text is mirrored from `.fc-overall`, which stays the single source. On the planner events.js
 * writes the number into `.d-oaw` and the style hooks write `.js-overall-label`; while the line
 * shows, that `.fc-overall` is only visually hidden (the line itself is aria-hidden), so screen
 * readers keep reading it — and if anything here fails, the old text simply stays on screen. A
 * project-plan section prints its "10,000 Overall" in the section head once it has drawn, and keeps
 * it there.
 *
 * The figure sits at the centre of the part of the line that is on screen: the whole line when
 * it fits (its true centre), the visible stretch when a long run is scrolled.
 *
 * One line per drawing. The planner's is in the page; the project plan appends its sections, a
 * line each, to #fc-fence-list after this file has run, so lines arriving there are set up too.
 *
 * Shared file beside hscroll-proxy.js; it no-ops on pages without the markup.
 */
(function fcOverallDimension() {
    /* Room kept between the figure's tag and each arrowhead while the line is long enough. */
    var ARROW_CLEAR = 12;

    /* Neither is fence length: the spacers carry the centre-point numbers between parts, and the
       off-cut box is drawn beside the run. */
    var NOT_RUN = '.fencing-panel-spacing-number, .fencing-offcut';

    /* A project-plan head's "10,000 Overall" (or "6,000 Opening Width") has no spans to read the
       figure and the word from. */
    var FIGURE_WORD = /^([\d.,]+)\s*(.*)$/;

    function textOf(el) {
        return el ? (el.textContent || '').replace(/\s+/g, ' ').trim() : '';
    }

    function setText(el, value) {
        if (el.textContent !== value) {
            el.textContent = value;
        }
    }

    function setup(dim) {
        var result = dim.closest('.fc-result');
        var run = result ? result.querySelector('.fencing-panel-container') : null;
        /* Step 3's .fc-overall sits under the strip; a project-plan section's is in its head. */
        var scope = dim.closest('.js-fc-form-step, .fc-project-plan-section');
        var label = dim.querySelector('.fc-dim__label');

        if (!run || !scope || !label || dim.fcOverallDimension) {
            return;
        }
        dim.fcOverallDimension = true;

        var items = result.querySelector('.fencing-panel-items');
        var anchor = dim.querySelector('.fc-dim__anchor');
        var strip = dim.closest('.fc-project-plan-hscroll');
        var num = label.querySelector('.fc-dim__num');
        var word = label.querySelector('.fc-dim__word');
        /* The head writes its .fc-overall only once the section has drawn, so the figure is looked
           up on every sync and the head's column is what gets watched. */
        var overallHost = scope.querySelector('.fc-project-plan-section-head__overall') ||
            scope.querySelector('.fc-overall');
        var watchers = [];

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

        function sync() {
            /* The project plan empties and rebuilds #fc-fence-list when it reloads its sections; a
               line that has left the page lets go of what watches it. */
            if (!dim.isConnected) {
                watchers.forEach(function(watcher) {
                    watcher.disconnect();
                });
                window.removeEventListener('resize', sync);
                if (strip) {
                    strip.removeEventListener('scroll', place);
                }
                return;
            }

            var overall = scope.querySelector('.fc-overall');
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

            var text = textOf(overall);
            /* Before Calculate .d-oaw is empty and the text is just "Overall": no figure, no line. */
            var on = right > left && /\d/.test(text);

            dim.classList.toggle('fc-dim--off', !on);
            if (overall) {
                overall.classList.toggle('fc-overall--in-dim', on);
            }

            if (!on) {
                return;
            }

            /* The line is not zoomed itself, so the run's rendered (already zoomed) px apply to it
               unchanged. Measured from where the line sits with no offset: in .fc-result's content box
               on the planner, hung from its padding edge in a project-plan section. */
            var origin = dim.getBoundingClientRect().left - (parseFloat(dim.style.marginLeft) || 0);
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
                var figure = overall.querySelector('.d-oaw');
                var split = figure ? null : FIGURE_WORD.exec(text);

                setText(num, figure ? textOf(figure) : (split ? split[1] : text));
                setText(word, figure ? textOf(overall.querySelector('.js-overall-label')) : (split ? split[2] : ''));
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
            var drawn = new MutationObserver(queue);

            drawn.observe(run, { childList: true, subtree: true });
            watchers.push(drawn);

            if (items) {
                var zoom = new MutationObserver(queue);

                zoom.observe(items, { attributes: true, attributeFilter: ['style', 'class'] });
                watchers.push(zoom);
            }
            if (overallHost) {
                var headText = new MutationObserver(queue);

                headText.observe(overallHost, { childList: true, characterData: true, subtree: true });
                watchers.push(headText);
            }
        }

        /* Step 3 is display:none until the first Calculate, and a font swap or card resize moves
           nothing the mutation observers see. */
        if (typeof ResizeObserver === 'function') {
            var sizes = new ResizeObserver(queue);

            sizes.observe(result);
            sizes.observe(run);
            watchers.push(sizes);
        }

        if (strip) {
            /* Scrolling moves the visible stretch but changes no size, so nothing above sees it. */
            strip.addEventListener('scroll', place, { passive: true });
        }

        window.addEventListener('resize', sync);
        sync();
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-fc-dim'), setup);

    var list = document.getElementById('fc-fence-list');

    if (list && typeof MutationObserver === 'function') {
        new MutationObserver(function() {
            Array.prototype.forEach.call(list.querySelectorAll('.js-fc-dim'), setup);
        }).observe(list, { childList: true });
    }
})();
