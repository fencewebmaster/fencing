/**
 * Fence diagram horizontal scroll fade.
 *
 * The planner's Step 3 drawing and every project-plan section use the same
 * `.fc-project-plan-hscroll` strip, so this lives in shared/ rather than in checkout.js, which
 * only the project-plan page loads. It was page-gated there, which is why Step 3 never faded
 * despite carrying the class.
 *
 * CSS cannot read a scroll position, so the state is published as classes: `fc-hscroll-fade-start`
 * while there is content off the left edge, `fc-hscroll-fade-end` while there is content off the
 * right. Both are set while the strip sits between its ends.
 */
(function fcHScrollFade() {
    var SELECTOR = '.fc-project-plan-hscroll';

    function sync(el) {
        var max = el.scrollWidth - el.clientWidth;
        var scrollable = max > 1;

        el.classList.toggle('fc-hscroll-fade-start', scrollable && el.scrollLeft > 1);
        el.classList.toggle('fc-hscroll-fade-end', scrollable && el.scrollLeft < max - 1);
    }

    function syncAll() {
        document.querySelectorAll(SELECTOR).forEach(sync);
    }

    function bind(el) {
        if (el.dataset.fcHscrollFade) {
            sync(el);
            return;
        }

        el.dataset.fcHscrollFade = '1';

        el.addEventListener('scroll', function() {
            sync(el);
        }, { passive: true });

        /* The planner redraws this strip on recalculate, on a zoom step and on every section
           switch, and its scrollWidth changes with it. Without watching for that the fade would
           keep whatever state it had when it was first bound. childList only: the fade classes
           live on `el` itself, so observing its own attributes would re-enter this callback. */
        if (typeof MutationObserver === 'function') {
            var queued = false;

            new MutationObserver(function() {
                if (queued) {
                    return;
                }
                queued = true;
                window.requestAnimationFrame(function() {
                    queued = false;
                    sync(el);
                });
            }).observe(el, { childList: true, subtree: true });
        }

        sync(el);
    }

    function bindAll() {
        document.querySelectorAll(SELECTOR).forEach(bind);
    }

    bindAll();
    window.addEventListener('resize', syncAll);

    /* The project plan builds its section list after load, so strips appear that did not exist at
       bind time. Absent on the planner, where Step 3 is server-rendered. */
    var list = document.getElementById('fc-fence-list');

    if (list && typeof MutationObserver === 'function') {
        var queued = false;

        new MutationObserver(function() {
            if (queued) {
                return;
            }
            queued = true;
            window.requestAnimationFrame(function() {
                queued = false;
                bindAll();
            });
        }).observe(list, { childList: true, subtree: true });
    }
})();
