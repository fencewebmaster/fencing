/**
 * FC Admin — toast notifications (upper left of content, below sticky headers).
 */
(function (global) {
    'use strict';

    var CONTAINER_ID = 'fc-admin-toast-root';
    var DEFAULT_DURATION = 4500;
    var DEFAULT_OFFSET = 16;
    var HEADER_GAP = 8;
    var STICKY_HEADER_SELECTOR = '[data-fc-admin-sticky-header], .fc-admin-sticky-header';
    var SIDE_RAIL_SELECTOR = '[data-fc-admin-side-rail]';
    var FADE_OUT_MS = 200;

    var toastsById = {};
    var positionListenersBound = false;

    var escapeHtml = global.FC.util.escapeHtml;

    function getToastHost() {
        return document.getElementById('fc-admin-page-content') || document.getElementById('fc-admin-main') || document.body;
    }

    function toastPositionClass() {
        return 'pointer-events-none absolute z-[200] flex w-[min(100%,24rem)] flex-col gap-2';
    }

    function measureStickyHeaderOffset(host) {
        if (!host) {
            return DEFAULT_OFFSET;
        }

        var hostRect = host.getBoundingClientRect();
        var top = DEFAULT_OFFSET;
        var headers = host.querySelectorAll(STICKY_HEADER_SELECTOR);

        headers.forEach(function (header) {
            var rect = header.getBoundingClientRect();
            var relativeBottom = rect.bottom - hostRect.top;

            if (relativeBottom > DEFAULT_OFFSET && rect.top <= hostRect.top + 2) {
                top = Math.max(top, relativeBottom + HEADER_GAP);
            } else if (relativeBottom > DEFAULT_OFFSET && rect.top >= hostRect.top && rect.top < hostRect.top + 120) {
                top = Math.max(top, relativeBottom + HEADER_GAP);
            }
        });

        return top;
    }

    /** A page's side rail (Settings) keeps toasts off its tabs; laid out as a top strip it doesn't count. */
    function measureSideRailOffset(host) {
        if (!host) {
            return DEFAULT_OFFSET;
        }

        var hostRect = host.getBoundingClientRect();
        var left = DEFAULT_OFFSET;
        host.querySelectorAll(SIDE_RAIL_SELECTOR).forEach(function (rail) {
            var rect = rail.getBoundingClientRect();
            if (rect.width > 0 && rect.height > rect.width && rect.left <= hostRect.left + 2) {
                left = Math.max(left, rect.right - hostRect.left + DEFAULT_OFFSET);
            }
        });

        return left;
    }

    function updateContainerPosition(container) {
        if (!container) {
            return;
        }

        var host = getToastHost();
        var top = measureStickyHeaderOffset(host);

        container.style.top = top + 'px';
        container.style.left = measureSideRailOffset(host) + 'px';
        container.style.right = 'auto';
    }

    function bindPositionListeners() {
        if (positionListenersBound) {
            return;
        }
        positionListenersBound = true;

        var scheduled = false;
        function scheduleUpdate() {
            if (scheduled) {
                return;
            }
            scheduled = true;
            window.requestAnimationFrame(function () {
                scheduled = false;
                updateContainerPosition(document.getElementById(CONTAINER_ID));
            });
        }

        window.addEventListener('resize', scheduleUpdate);
        document.addEventListener('scroll', scheduleUpdate, true);

        if (typeof MutationObserver !== 'undefined') {
            var content = document.getElementById('fc-admin-page-content');
            if (content) {
                var observer = new MutationObserver(scheduleUpdate);
                observer.observe(content, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
            }
        }
    }

    function ensureContainer() {
        var el = document.getElementById(CONTAINER_ID);
        var host = getToastHost();

        if (!el) {
            el = document.createElement('div');
            el.id = CONTAINER_ID;
            el.className = toastPositionClass();
            el.setAttribute('aria-live', 'polite');
            el.setAttribute('aria-relevant', 'additions');
            host.insertBefore(el, host.firstChild);
        } else if (el.parentNode !== host) {
            el.className = toastPositionClass();
            host.insertBefore(el, host.firstChild);
        }

        bindPositionListeners();
        updateContainerPosition(el);
        return el;
    }

    function stylesForType(type) {
        switch (type) {
            case 'success':
                return 'border-emerald-200 bg-emerald-50/95 text-emerald-900';
            case 'error':
                return 'border-red-200 bg-red-50/95 text-red-900';
            case 'loading':
                return 'border-amber-200 bg-amber-50/95 text-amber-900';
            default:
                return 'border-slate-200 bg-white text-slate-800';
        }
    }

    function iconClassForType(type) {
        switch (type) {
            case 'success':
                return 'fa-solid fa-circle-check text-emerald-600';
            case 'error':
                return 'fa-solid fa-circle-xmark text-red-600';
            case 'loading':
                return 'fa-solid fa-spinner fa-spin text-amber-600';
            default:
                return 'fa-solid fa-circle-info text-indigo-600';
        }
    }

    /** Fades a toast element out and removes it from the DOM once the transition ends. */
    function fadeOutAndRemove(toast) {
        toast.setAttribute('data-toast-leaving', '1');
        window.clearTimeout(toast._fcTimer);
        toast.classList.add('opacity-0', '-translate-x-4');
        window.setTimeout(function () {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, FADE_OUT_MS);
    }

    /** A still-visible toast with the same type and message, so repeats don't stack. */
    function findVisibleToast(container, key) {
        for (var i = 0; i < container.children.length; i++) {
            var child = container.children[i];
            if (child.getAttribute('data-toast-key') === key && !child.hasAttribute('data-toast-leaving')) {
                return child;
            }
        }
        return null;
    }

    function armTimer(toast, duration) {
        window.clearTimeout(toast._fcTimer);
        if (duration > 0) {
            toast._fcTimer = window.setTimeout(toast._fcDismiss, duration);
        }
    }

    function dismiss(id) {
        if (!id || !toastsById[id]) {
            return;
        }
        fadeOutAndRemove(toastsById[id]);
        delete toastsById[id];
    }

    /**
     * @param {string|{message?:string,type?:string,duration?:number,id?:string,unique?:boolean}} opts
     *   unique (default true): re-arm an identical visible toast instead of stacking a duplicate
     * @returns {string|undefined} toast id when opts.id is set
     */
    function show(opts) {
        if (typeof opts === 'string') {
            opts = { message: opts };
        }
        opts = opts || {};

        var message = opts.message || '';
        var type = opts.type || 'info';
        var id = opts.id || '';
        var duration = opts.duration;

        if (duration === undefined) {
            duration = type === 'loading' ? 0 : DEFAULT_DURATION;
        }

        if (id && toastsById[id]) {
            dismiss(id);
        }

        var container = ensureContainer();
        updateContainerPosition(container);

        var key = type + '|' + message;
        // Id'd toasts already replace themselves above; deduping them could orphan a later dismiss(id).
        var dupe = !id && opts.unique !== false ? findVisibleToast(container, key) : null;
        if (dupe) {
            armTimer(dupe, duration);
            return id || undefined;
        }

        var toast = document.createElement('div');
        toast.setAttribute('data-toast-key', key);
        toast.className =
            'pointer-events-auto flex translate-x-0 items-start gap-3 rounded-lg border px-4 py-3 shadow-lg ring-1 ring-black/5 transition-all duration-200 ' +
            stylesForType(type);

        if (id) {
            toast.setAttribute('data-toast-id', id);
            toastsById[id] = toast;
        }

        toast.innerHTML =
            '<i class="' +
            iconClassForType(type) +
            ' mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>' +
            '<p class="min-w-0 flex-1 text-sm font-medium leading-snug">' +
            escapeHtml(message) +
            '</p>' +
            (type !== 'loading'
                ? '<button type="button" class="shrink-0 rounded p-1 text-slate-400 hover:bg-black/5 hover:text-slate-600" data-toast-close aria-label="Dismiss">' +
                  '<i class="fa-solid fa-xmark" aria-hidden="true"></i></button>'
                : '');

        function dismissToast() {
            if (id) {
                dismiss(id);
            } else {
                fadeOutAndRemove(toast);
            }
        }

        var closeBtn = toast.querySelector('[data-toast-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', dismissToast);
        }

        toast._fcDismiss = dismissToast;
        container.appendChild(toast);
        armTimer(toast, duration);

        return id || undefined;
    }

    function success(message, opts) {
        opts = opts || {};
        opts.message = message;
        opts.type = 'success';
        return show(opts);
    }

    function error(message, opts) {
        opts = opts || {};
        opts.message = message;
        opts.type = 'error';
        return show(opts);
    }

    function loading(message, id) {
        return show({
            message: message,
            type: 'loading',
            id: id || 'fc-admin-toast-loading',
            duration: 0
        });
    }

    global.FcAdminToast = {
        show: show,
        dismiss: dismiss,
        success: success,
        error: error,
        loading: loading,
        updatePosition: function () {
            updateContainerPosition(document.getElementById(CONTAINER_ID));
        }
    };
})(window);
