/* Planner Step 3 full screen: the zoom bar's last button lays the step over the window (style.css) and asks the browser to go full screen.
   The browser request targets <html>, not the step: the option drawer, modals and toasts live outside Step 3 and would vanish. */
(function fcStep3Fullscreen() {
    var section = document.querySelector('.js-fc-form-step[data-section="3"]');
    var button = section ? section.querySelector('.js-fc-step3-fullscreen') : null;

    if (!button) {
        return;
    }

    var root = document.documentElement;
    var ON = 'fc-step3-fullscreen';
    var watcher = null;

    function active() {
        return root.classList.contains(ON);
    }

    function browserFullscreen() {
        return document.fullscreenElement || document.webkitFullscreenElement || null;
    }

    /* The same test drawing-keys.js uses: an open drawer or Bootstrap modal takes Esc first. */
    function dialogOpen() {
        return Array.prototype.some.call(document.querySelectorAll('.fencing-modal, .modal.show'), function(el) {
            return el.getClientRects().length > 0;
        });
    }

    /* Hidden by .hide() (which the full-screen display:flex !important would mask) or with its step container (Next). */
    function stepShown() {
        return section.style.display !== 'none' && !!section.parentElement && section.parentElement.getClientRects().length > 0;
    }

    function sync() {
        var on = active();

        button.setAttribute('aria-pressed', on ? 'true' : 'false');
        button.setAttribute('aria-label', on ? 'Exit full screen' : 'Full screen');
        button.setAttribute('title', on ? 'Exit full screen (F or Esc)' : 'Full screen (F)');
    }

    /* The dimension lines, proxy scrollbar and edge fades re-measure on resize; a layout-only switch fires none.
       The zoom's min-height lock is re-taken now: one measured in the other layout padded Step 3's bottom on the way out. */
    function relayout() {
        if (typeof HELPER !== 'undefined' && typeof HELPER.refreshStep3ResultMinHeight === 'function') {
            HELPER.refreshStep3ResultMinHeight();
        }
        window.requestAnimationFrame(function() {
            window.dispatchEvent(new Event('resize'));
        });
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
    }

    function exit() {
        if (!active()) {
            return;
        }

        root.classList.remove(ON);
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
    }

    button.addEventListener('click', function() {
        if (active()) {
            exit();
        } else {
            enter();
        }
    });

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
