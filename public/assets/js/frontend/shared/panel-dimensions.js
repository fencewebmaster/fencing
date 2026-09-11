/**
 * Planner Step 3 and the project plan: a width dimension over every panel and gate of the drawing
 * — |—— 1,934W ——| — as the design-1 mockup draws it.
 *
 * The widths were only printed inside the parts ("1934W PANEL"), where a slat panel's height
 * shares the label and nothing lines up with the run. `.js-fc-pdim` lives INSIDE the strip's
 * `.fc-result`, above the run — the zoomed `.fencing-panel-items` on the planner, a project-plan
 * section's bare `.fencing-panel-container` — and each segment is placed from the part's rendered
 * box, the same measuring overall-dimension.js does for the Overall line, so CSS `zoom`
 * (HELPER.zooming) and every style's own markup are covered without knowing their geometry.
 *
 * The figure is read back from the part's own label, which stays the single source: a panel's W
 * figure (".fc-panel-size" "1934W", next to a slat panel's "1769H"), a gate's mm figure
 * ("970mm GATE", under the slat gate's "1800H"). A part whose label has neither gets no segment
 * rather than a guessed number.
 *
 * One row per drawing. The planner's is in the page; the project plan appends its sections, a row
 * each, to #fc-fence-list after this file has run, so rows arriving there are set up too.
 *
 * Shared file beside overall-dimension.js; it no-ops on pages without the markup.
 */
(function fcPanelDimensions() {
    /* W first: a slat panel's label carries its height too, and "1769H" must not win. */
    var WIDTH_W = /(\d[\d,]*(?:\.\d+)?)\s*W\b/i;
    var WIDTH_MM = /(\d[\d,]*(?:\.\d+)?)\s*mm\b/i;

    /* Every drawn part; a raked panel's container and its end post are the ones that stand taller. */
    var PARTS = '.fencing-panel-item, .panel-post, .raked-panel-container';

    function widthOf(part) {
        var size = part.querySelector('.fc-panel-size');
        var label = part.querySelector('.fencing-panel-item-size');
        var texts = [size ? size.textContent : '', label ? label.textContent : ''];

        for (var i = 0; i < texts.length; i++) {
            var m = WIDTH_W.exec(texts[i]) || WIDTH_MM.exec(texts[i]);
            if (m) {
                var mm = Math.round(parseFloat(m[1].replace(/,/g, '')));
                if (mm > 0) {
                    return mm;
                }
            }
        }
        return 0;
    }

    /* What stands between two parts: a post, or on a glass run the gap strip, drawn as wide as its
       gap in mm ("42" is 4.2px at 100%). Both are zero-width spacers elsewhere, so they separate
       nothing there. */
    var SEPARATORS = '.panel-post, .fencing-panel-spacing-number';

    /* Barr draws each panel from post centre to post centre, half under each post, and glass pool
       draws each panel 1px over the gap strip either side, so their segments met, or nearly, where
       the other styles leave the post's gap between them. A segment gives up whatever a separator
       covers at either end: one whose middle is in the segment's left half trims its left end, one
       in the right half its right end, and the segment keeps which separator cut it so both of its
       neighbours can be placed from the same one. A box wider than half the part is not a separator
       (a glass panel's spigot holder is a .panel-post too), and a trim that would take more than
       half the part is not trusted. */
    function clearOfSeparators(box, separators) {
        var span = { left: box.left, right: box.right, leftSep: null, rightSep: null };
        var middle = (box.left + box.right) / 2;

        separators.forEach(function(sep) {
            if (sep.right <= span.left || sep.left >= span.right || sep.width > box.width / 2) {
                return;
            }
            if ((sep.left + sep.right) / 2 <= middle) {
                span.left = sep.right;
                span.leftSep = sep;
            } else {
                span.right = sep.left;
                span.rightSep = sep;
            }
        });

        if (span.right - span.left < box.width / 2) {
            return { left: box.left, right: box.right, leftSep: null, rightSep: null };
        }
        return span;
    }

    function setup(row) {
        var result = row.closest('.fc-result');
        var run = result ? result.querySelector('.fencing-panel-container') : null;

        if (!run || row.fcPanelDimensions) {
            return;
        }
        row.fcPanelDimensions = true;

        /* The run's top is measured from the zoomed .fencing-panel-items that wraps it on the
           planner; a project-plan section has no wrapper. */
        var items = result.querySelector('.fencing-panel-items');
        var base = items || run;
        var watchers = [];

        /* How far the tallest part stands above the top of the run, in rendered px. */
        function riseAboveRun() {
            var top = base.getBoundingClientRect().top;
            var highest = top;

            Array.prototype.forEach.call(run.querySelectorAll(PARTS), function(el) {
                var box = el.getBoundingClientRect();
                if (box.width && box.height && box.top < highest) {
                    highest = box.top;
                }
            });
            return Math.max(0, Math.ceil(top - highest));
        }

        /* Writes only on change, like the segments below. */
        function setPx(name, px) {
            var value = px > 0 ? px + 'px' : '';

            if (row.style.getPropertyValue(name) === value) {
                return;
            }
            if (value) {
                row.style.setProperty(name, value);
            } else {
                row.style.removeProperty(name);
            }
        }

        function segment(i) {
            var seg = row.children[i];

            if (!seg) {
                seg = document.createElement('span');
                seg.className = 'fc-pdim__seg';
                var label = document.createElement('span');
                label.className = 'fc-pdim__label';
                seg.appendChild(label);
                row.appendChild(seg);
            }
            return seg;
        }

        function sync() {
            /* The project plan empties and rebuilds #fc-fence-list when it reloads its sections; a
               row that has left the page lets go of what watches it. */
            if (!row.isConnected) {
                watchers.forEach(function(watcher) {
                    watcher.disconnect();
                });
                window.removeEventListener('resize', sync);
                return;
            }

            var marks = [];
            var separators = [];

            Array.prototype.forEach.call(run.querySelectorAll(SEPARATORS), function(el) {
                var box = el.getBoundingClientRect();
                if (box.width && box.height) {
                    separators.push(box);
                }
            });

            Array.prototype.forEach.call(run.querySelectorAll('.fencing-panel-item'), function(part) {
                var box = part.getBoundingClientRect();
                if (!box.width || !box.height) {
                    return;
                }
                var mm = widthOf(part);
                if (!mm) {
                    return;
                }

                /* "1,934W": the W the parts' own labels used, now that the line carries the figure. */
                marks.push({ span: clearOfSeparators(box, separators), text: mm.toLocaleString('en-AU') + 'W' });
            });

            row.classList.toggle('fc-pdim--off', !marks.length);

            /* A raked panel's high end and its end post stand above the run the other parts share
               (style.css pulls them up with top: -41px and margin-top: -50px), right through this row,
               so a raked panel's figure sat under its own post. The row rises by that much into the room
               .fc-result's raked padding keeps above the run (4px short of its clipped top edge) and
               holds the run as far below it, so the line clears the tallest part with the drawing left
               where it was; only a rise the padding cannot take pushes the run down. Zoom changes the
               rise, hence measuring it on every sync. */
            var rise = marks.length ? riseAboveRun() : 0;
            var room = Math.max(0, (parseFloat(window.getComputedStyle(result).paddingTop) || 0) - 4);

            setPx('--fc-pdim-rise', rise);
            setPx('--fc-pdim-lift', Math.min(rise, room));

            while (row.children.length > marks.length) {
                row.removeChild(row.lastChild);
            }

            if (!marks.length) {
                return;
            }

            /* The row is a block in .fc-result's content box and is not zoomed itself, so the parts'
               rendered (already zoomed) px apply to it unchanged — as for the Overall line. */
            var origin = result.getBoundingClientRect().left + result.clientLeft +
                (parseFloat(window.getComputedStyle(result).paddingLeft) || 0);

            /* Edges land on half pixels, which keeps the 1px ticks crisp. Rounding each segment's own
               left and width drifted with the drawing's fractions: a glass run's equal 42mm gaps came
               out 2px and 2.5px by turns. An edge a separator cut is placed instead at half the
               separator's whole-pixel width off its half-pixel centre, the same numbers for the
               segment on its other side, so equal gaps come out equal: every 42mm glass gap 4px at
               100%, every post 8px. */
            function edge(x, sep, side) {
                if (!sep) {
                    return Math.round((x - origin) * 2) / 2;
                }
                var centre = Math.round(((sep.left + sep.right) / 2 - origin) * 2) / 2;

                return centre + side * Math.max(1, Math.round(sep.width)) / 2;
            }

            marks.forEach(function(mark, i) {
                var seg = segment(i);
                var label = seg.firstChild;
                var from = edge(mark.span.left, mark.span.leftSep, 1);
                var to = edge(mark.span.right, mark.span.rightSep, -1);
                var left = from + 'px';
                var width = Math.max(0, to - from) + 'px';

                /* Writes only on change: the ResizeObserver below watches .fc-result, which this row sits in. */
                if (seg.style.left !== left) {
                    seg.style.left = left;
                }
                if (seg.style.width !== width) {
                    seg.style.width = width;
                }
                if (label.textContent !== mark.text) {
                    label.textContent = mark.text;
                }
                seg.classList.toggle('fc-pdim__seg--tight', label.offsetWidth > to - from - 4);
            });
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
            /* Recalculate and section switches rebuild the run, and the gate label is rewritten in
               place (slat_fence.js applyGateLabel); zoom and the slat-scale classes restyle its wrapper. */
            var drawn = new MutationObserver(queue);

            drawn.observe(run, { childList: true, subtree: true, characterData: true });
            watchers.push(drawn);

            if (items) {
                var zoom = new MutationObserver(queue);

                zoom.observe(items, { attributes: true, attributeFilter: ['style', 'class'] });
                watchers.push(zoom);
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

        window.addEventListener('resize', sync);
        sync();
    }

    Array.prototype.forEach.call(document.querySelectorAll('.js-fc-pdim'), setup);

    var list = document.getElementById('fc-fence-list');

    if (list && typeof MutationObserver === 'function') {
        new MutationObserver(function() {
            Array.prototype.forEach.call(list.querySelectorAll('.js-fc-pdim'), setup);
        }).observe(list, { childList: true });
    }
})();
