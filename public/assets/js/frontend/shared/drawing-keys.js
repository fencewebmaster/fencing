/**
 * Planner Step 3 keyboard shortcuts for the fence drawing: ← / → scroll it, + / − zoom it, 0 resets it,
 * F toggles full screen (by pressing the zoom bar's button, which step3-fullscreen.js owns).
 *
 * Arrow keys only scroll the scroller that has focus, and nothing on the planner ever focuses the
 * drawing strip (`.fc-project-plan-hscroll` — its panels are click targets, not focusable), so the
 * keys did nothing while a long run sat half off screen. The keys are taken at the document
 * instead, only while the strip is on screen, and left alone whenever the focused control has its
 * own use for them (text fields, where + and − get typed, selects, the style and colour sliders)
 * or a modal is open.
 *
 * ← / →: a tap glides a third of the visible drawing; holding the key keeps it moving at a steady
 * speed until the key comes up. The proxy scrollbar, the edge fades and the Overall label each
 * follow the strip's own scroll event, so they need nothing from here.
 *
 * + / −: press the Step 3 zoom buttons themselves, so the 10% step, the zoom-out floor and the
 * percentage readout all stay the buttons' own. 0 presses Reset the same way, and does nothing at
 * 100%, where HELPER keeps that button disabled.
 *
 * The keyboard button beside Summary lists these in #fc-shortcuts-modal (planner/modals.php);
 * keep the two in step.
 *
 * Shared file beside hscroll-fade.js. The project plan gets ← / → on the section drawing nearest
 * mid-screen, and + / − / 0 only while a section is full screen, on that section's own zoom buttons
 * (step3-fullscreen.js); it has no F. Any other page has neither strip and is left alone.
 */
(function fcDrawingKeys() {
    var section = document.querySelector('.js-fc-form-step[data-section="3"]');
    var plannerStrip = section ? section.querySelector('.fencing-display-result .fc-project-plan-hscroll') : null;
    var planList = document.getElementById('fc-fence-list');
    var strip = plannerStrip;

    if (!plannerStrip && !planList) {
        return;
    }

    /* Controls that already use these keys. Buttons and links are not among them, which matters:
       focus is still on the Calculate button when the drawing first appears. */
    var OWNS_KEYS = 'input, textarea, select, [contenteditable]:not([contenteditable="false"]), ' +
        '[role="combobox"], [role="listbox"], [role="menu"], [role="slider"], [role="spinbutton"], ' +
        '[role="tablist"], [role="radiogroup"], .slick-slider, .select2-container';

    /* A tap that lands while the previous one is still gliding carries on from where that one was
       heading, so quick taps add up instead of each restarting from mid-flight. */
    var CHAIN_MS = 450;

    /* Zoom-in has no ceiling and a held key repeats about 30 times a second, which would fly past
       any useful size: a held + or − takes one step per this many ms. */
    var ZOOM_REPEAT_MS = 150;

    var tapTarget = 0;
    var tapAt = 0;
    var zoomAt = 0;
    var held = 0;
    var frame = 0;
    var pos = 0;
    var last = 0;

    /* The project plan's image gallery takes ← / → for its own slides. */
    function modalOpen() {
        return Array.prototype.some.call(document.querySelectorAll('.fencing-modal, .modal.show, .fc-cart-image-modal.is-open'), function(el) {
            return el.getClientRects().length > 0;
        });
    }

    /* The project plan draws its sections' strips after load, so the one to scroll is found per key press. */
    function nearestPlanStrip() {
        var best = planList.querySelector('.fc-project-plan-section--fullscreen .fc-project-plan-hscroll');
        var bestGap = Infinity;

        /* A full-screen section covers the rest, which keep their page positions behind it. */
        if (best) {
            return best;
        }

        planList.querySelectorAll('.fc-project-plan-hscroll').forEach(function(el) {
            var box = el.getBoundingClientRect();
            var gap = Math.abs((box.top + box.bottom) / 2 - window.innerHeight / 2);

            if (box.width > 0 && gap < bestGap) {
                best = el;
                bestGap = gap;
            }
        });

        return best;
    }

    /* Half the strip in view or more: the keys act on the drawing being looked at, not one scrolled
       nearly out of sight. Also false while Step 3 is hidden before the first Calculate. */
    function onScreen(el) {
        var box = el.getBoundingClientRect();
        var middle = (box.top + box.bottom) / 2;

        return box.width > 0 && middle > 0 && middle < window.innerHeight;
    }

    function range() {
        return strip.scrollWidth - strip.clientWidth;
    }

    function stop() {
        held = 0;
        if (frame) {
            window.cancelAnimationFrame(frame);
            frame = 0;
        }
    }

    /* One visible width a second while held. Frame gaps are capped so a stalled frame does not
       jump the drawing. */
    function glide(now) {
        var max = range();
        var dt = last ? Math.min(now - last, 64) : 16;

        last = now;
        pos = Math.min(Math.max(pos + held * Math.max(600, strip.clientWidth) * dt / 1000, 0), max);
        strip.scrollLeft = pos;

        if (!held || pos <= 0 || pos >= max) {
            stop();
            return;
        }
        frame = window.requestAnimationFrame(glide);
    }

    function scrollStep(dir, repeat) {
        /* Auto-repeat means the key is being held: hand over from the tap's glide to a steady scroll. */
        if (repeat) {
            held = dir;
            if (!frame) {
                pos = strip.scrollLeft;
                last = 0;
                frame = window.requestAnimationFrame(glide);
            }
            return;
        }

        stop();

        var now = Date.now();
        var from = now - tapAt < CHAIN_MS ? tapTarget : strip.scrollLeft;
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        tapTarget = Math.min(Math.max(from + dir * Math.max(80, Math.round(strip.clientWidth / 3)), 0), range());
        tapAt = now;
        strip.scrollTo({ left: tapTarget, behavior: reduce ? 'auto' : 'smooth' });
    }

    /* way is 'in', 'out' or 'reset'. */
    function zoomButton(way) {
        if (!section) {
            return planList.querySelector('.fc-project-plan-section--fullscreen .js-fc-plan-zoom[data-zoom="' + way + '"]');
        }
        return section.querySelector(way === 'reset' ? '.js-fc-zoom-reset' : '.fc-zoom-fence[data-zoom="' + way + '"]');
    }

    function zoomStep(way, repeat) {
        var now = Date.now();
        var button = zoomButton(way);

        if (repeat && now - zoomAt < ZOOM_REPEAT_MS) {
            return;
        }
        zoomAt = now;

        /* click() does nothing on a disabled button, so zoom-out stops at its floor exactly as it
           does for the mouse. */
        if (button) {
            button.click();
        }
    }

    document.addEventListener('keydown', function(e) {
        var dir = e.key === 'ArrowRight' ? 1 : (e.key === 'ArrowLeft' ? -1 : 0);
        /* "=" and "_" share a key with + and − on most layouts, where + itself needs Shift. */
        var way = e.key === '+' || e.key === '=' ? 'in' : (e.key === '-' || e.key === '_' ? 'out' : '');
        var reset = e.key === '0';
        var full = e.key === 'f' || e.key === 'F';

        if (!section) {
            full = false;
            if (!planList.querySelector('.fc-project-plan-section--fullscreen')) {
                way = '';
                reset = false;
            }
        }

        /* Ctrl/⌘ with +, − or 0 is the browser's page zoom and Alt+← is Back. Shift rules out only the
           arrows, since + and _ are typed with it. */
        if ((!dir && !way && !reset && !full) || (dir && e.shiftKey) || e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || e.isComposing) {
            return;
        }

        /* A held key stays on the strip it started on; a tap on another strip does not chain on the last one's. */
        if (!plannerStrip && (!e.repeat || !strip)) {
            var next = nearestPlanStrip();

            if (next !== strip) {
                stop();
                tapAt = 0;
            }
            strip = next;
        }

        if ((e.target && e.target.closest && e.target.closest(OWNS_KEYS)) || modalOpen() || !strip || !onScreen(strip)) {
            return;
        }

        /* A held F would flicker in and out of full screen. */
        if (full) {
            var fullButton = section.querySelector('.js-fc-step3-fullscreen');

            e.preventDefault();
            if (fullButton && !e.repeat) {
                fullButton.click();
            }
            return;
        }

        if (reset) {
            var resetButton = zoomButton('reset');

            e.preventDefault();
            if (resetButton) {
                resetButton.click();
            }
            return;
        }

        if (way) {
            e.preventDefault();
            zoomStep(way, e.repeat);
            return;
        }

        if (range() <= 1) {
            return;
        }

        e.preventDefault();
        scrollStep(dir, e.repeat);
    });

    document.addEventListener('keyup', function(e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            stop();
        }
    });

    /* The key-up never arrives if the window loses focus mid-hold. */
    window.addEventListener('blur', stop);

    /* Project plan: style.css shows the arrow-key note only under the strip the keys would move now. */
    if (!plannerStrip) {
        var marking = 0;

        var markTarget = function() {
            var target = nearestPlanStrip();

            marking = 0;
            if (target && !onScreen(target)) {
                target = null;
            }
            planList.querySelectorAll('.fc-project-plan-hscroll').forEach(function(el) {
                el.classList.toggle('fc-drawing-keys-target', el === target);
            });
        };

        var queueMark = function() {
            if (!marking) {
                marking = window.requestAnimationFrame(markTarget);
            }
        };

        /* Capture, so a scroll inside any container counts; sections and their drawings arrive after load. */
        document.addEventListener('scroll', queueMark, { capture: true, passive: true });
        window.addEventListener('resize', queueMark);
        if (typeof MutationObserver === 'function') {
            new MutationObserver(queueMark).observe(planList, { childList: true, subtree: true });
        }
        queueMark();
    }
})();
