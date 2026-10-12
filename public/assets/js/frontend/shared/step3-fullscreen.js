/* Full screen: planner Step 3's zoom-bar button, or a project plan section's corner button (p2.js), lays it over the window (style.css) and asks
   for browser full screen on <html>, where drawers, modals and toasts still show. Plan sections add zoom and moves (plan-filmstrip.js), announced as fc-plan-fullscreen. */
(function fcStep3Fullscreen() {
    var section = document.querySelector('.js-fc-form-step[data-section="3"]');
    var stepButton = section ? section.querySelector('.js-fc-step3-fullscreen') : null;
    var planList = stepButton ? null : document.getElementById('fc-fence-list');

    if (!stepButton && !planList) {
        return;
    }

    var root = document.documentElement;
    var ON = stepButton ? 'fc-step3-fullscreen' : 'fc-plan-fullscreen';
    var PLAN_ON = 'fc-project-plan-section--fullscreen';
    var button = stepButton;
    var target = section;
    var watcher = null;
    var restoreY = 0;
    var zoom = 1;
    var moved = false;

    function active() {
        return root.classList.contains(ON);
    }

    function browserFullscreen() {
        return document.fullscreenElement || document.webkitFullscreenElement || null;
    }

    /* The same test drawing-keys.js uses: an open drawer or Bootstrap modal takes Esc first. */
    function dialogOpen() {
        return Array.prototype.some.call(document.querySelectorAll('.fencing-modal, .modal.show, .fc-cart-image-modal.is-open'), function(el) {
            return el.getClientRects().length > 0;
        });
    }

    /* Hidden by .hide() (which the full-screen display:flex !important would mask) or with its step container (Next). */
    function stepShown() {
        return target.style.display !== 'none' && !!target.parentElement && target.parentElement.getClientRects().length > 0;
    }

    /* A section left behind by a move is synced off with its own button. */
    function sync(btn, off) {
        var on = !off && active();

        btn = btn || button;
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.setAttribute('aria-label', on ? 'Exit full screen' : 'Full screen');
        if (stepButton) {
            btn.setAttribute('title', on ? 'Exit full screen (F or Esc)' : 'Full screen (F)');
        } else {
            btn.setAttribute('title', on ? 'Exit full screen (Esc)' : 'Full screen');
        }
    }

    function announce() {
        if (!stepButton) {
            document.dispatchEvent(new CustomEvent('fc-plan-fullscreen', { detail: { section: active() ? target : null } }));
        }
    }

    /* The dimension lines, proxy scrollbar and edge fades re-measure on resize; a layout-only switch fires none.
       The zoom's min-height lock is re-taken now: one measured in the other layout padded Step 3's bottom on the way out. */
    function relayout() {
        if (stepButton && typeof HELPER !== 'undefined' && typeof HELPER.refreshStep3ResultMinHeight === 'function') {
            HELPER.refreshStep3ResultMinHeight();
        }
        window.requestAnimationFrame(function() {
            window.dispatchEvent(new Event('resize'));
        });
    }

    /* Project plan zoom, full screen only: CSS zoom on the section's run in planner Step 3's 10% steps, down to HELPER's floor.
       The middle of the visible stretch stays put; the width labels, fades and note re-measure on relayout()'s resize. */
    function setZoom(value) {
        var run = target.querySelector('.fencing-panel-container');
        var strip = target.querySelector('.fc-project-plan-hscroll');
        var readout = target.querySelector('.js-fc-plan-zoom-value');
        var reset = target.querySelector('.js-fc-plan-zoom[data-zoom="reset"]');
        var out = target.querySelector('.js-fc-plan-zoom[data-zoom="out"]');
        var floor = typeof fcZoomMinStep === 'number' ? fcZoomMinStep : 0.1;
        var middle = strip && strip.scrollWidth ? (strip.scrollLeft + strip.clientWidth / 2) / strip.scrollWidth : 0;

        var result = target.querySelector('.fc-result');

        zoom = Math.max(floor, Math.round(value * 100) / 100);
        if (run) {
            if (zoom === 1) {
                run.style.removeProperty('zoom');
            } else {
                run.style.zoom = String(zoom);
            }
        }
        /* The Centers line hangs in .fc-result's bottom padding, which the zoom does not reach (p2.js may have grown it
           inline at 100%): scaled with the run, or the strip clips the line. */
        if (result) {
            if (!result.fcZoomPad) {
                result.fcZoomPad = { inline: result.style.paddingBottom, px: parseFloat(window.getComputedStyle(result).paddingBottom) || 0 };
            }
            if (zoom === 1) {
                result.style.paddingBottom = result.fcZoomPad.inline;
                result.fcZoomPad = null;
            } else {
                result.style.paddingBottom = Math.ceil(result.fcZoomPad.px * zoom) + 'px';
            }
        }
        if (readout) {
            readout.textContent = Math.round(zoom * 100) + '%';
        }
        if (reset) {
            reset.disabled = zoom === 1;
        }
        if (out) {
            out.disabled = zoom <= floor;
        }
        if (strip) {
            strip.scrollLeft = middle * strip.scrollWidth - strip.clientWidth / 2;
        }
        relayout();
    }

    function settle(result) {
        if (result && typeof result.catch === 'function') {
            result.catch(function() {});
        }
    }

    function enter() {
        if (active() || !stepShown()) {
            return;
        }

        if (!stepButton) {
            restoreY = window.scrollY;
            moved = false;
            target.classList.add(PLAN_ON);
        }
        root.classList.add(ON);
        sync();

        /* Refused or missing (iPhone Safari) leaves the layout alone filling the window, which is enough. */
        var request = root.requestFullscreen || root.webkitRequestFullscreen;
        if (request && !browserFullscreen()) {
            try {
                settle(request.call(root));
            } catch (e) {}
        }

        if (typeof MutationObserver === 'function') {
            watcher = new MutationObserver(function() {
                if (!stepShown()) {
                    exit();
                }
            });
            watcher.observe(document.body, { attributes: true, attributeFilter: ['style', 'class'], subtree: true });
        }

        relayout();
        announce();
    }

    /* Browser full screen stays on: only the layout changes hands, the section left behind going back at 100%. */
    function moveTo(next, nextButton) {
        if (zoom !== 1) {
            setZoom(1);
        }
        target.classList.remove(PLAN_ON);
        sync(button, true);

        target = next;
        button = nextButton;
        moved = true;
        target.classList.add(PLAN_ON);
        sync();
        relayout();
        announce();
    }

    function exit() {
        if (!active()) {
            return;
        }

        /* The page and its PNG/PDF captures show the plan at 100%. */
        if (!stepButton && zoom !== 1) {
            setZoom(1);
        }

        root.classList.remove(ON);
        if (!stepButton) {
            target.classList.remove(PLAN_ON);
            /* Lifted out of the page, the section shortened it and the scroll was clamped: put it back, without the smooth glide.
               After a move, the page opens on the section last shown instead, centred like the arrow keys' target. */
            root.style.scrollBehavior = 'auto';
            if (moved) {
                var box = target.getBoundingClientRect();
                window.scrollTo(0, Math.max(0, window.scrollY + box.top - Math.max(0, (window.innerHeight - box.height) / 2)));
            } else {
                window.scrollTo(0, restoreY);
            }
            root.style.scrollBehavior = '';
        }
        sync();

        if (watcher) {
            watcher.disconnect();
            watcher = null;
        }

        var leave = document.exitFullscreen || document.webkitExitFullscreen;
        if (browserFullscreen() && leave) {
            try {
                settle(leave.call(document));
            } catch (e) {}
        }

        relayout();
        announce();
    }

    if (stepButton) {
        stepButton.addEventListener('click', function() {
            if (active()) {
                exit();
            } else {
                enter();
            }
        });
    } else {
        planList.addEventListener('click', function(e) {
            var zoomButton = e.target.closest ? e.target.closest('.js-fc-plan-zoom') : null;
            var clicked = e.target.closest ? e.target.closest('.js-fc-plan-fullscreen') : null;

            if (zoomButton) {
                if (active() && target.contains(zoomButton)) {
                    var way = zoomButton.getAttribute('data-zoom');

                    setZoom(way === 'reset' ? 1 : zoom + (way === 'in' ? 0.1 : -0.1));
                }
                return;
            }
            if (!clicked) {
                return;
            }
            if (active()) {
                var next = clicked.closest('.fc-project-plan-section');

                if (next && next !== target) {
                    moveTo(next, clicked);
                } else {
                    exit();
                }
                return;
            }
            button = clicked;
            target = clicked.closest('.fc-project-plan-section');
            if (target) {
                enter();
            }
        });
    }

    /* Esc or the browser's own control ended the browser's full screen: end the layout with it. */
    function onBrowserChange() {
        if (!browserFullscreen() && active()) {
            exit();
        }
    }

    document.addEventListener('fullscreenchange', onBrowserChange);
    document.addEventListener('webkitfullscreenchange', onBrowserChange);

    /* Layout-only full screen sees Esc itself. Capture phase, so an open dialog is still seen as open and keeps the key. */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && active() && !e.defaultPrevented && !dialogOpen()) {
            exit();
        }
    }, true);
})();
