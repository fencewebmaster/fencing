/**
 * FC Admin — alert / confirm popup modals.
 */
(function (global) {
    'use strict';

    var ROOT_ID = 'fc-admin-modal-root';
    var modalsById = {};
    var activePromiseModal = null;
    var scrollLockCount = 0;
    var escapeHtml = global.FC.util.escapeHtml;

    function normalizeOpts(opts) {
        if (typeof opts === 'string') {
            return { message: opts };
        }
        return opts || {};
    }

    function iconForVariant(variant) {
        switch (variant) {
            case 'success':
                return {
                    wrap: 'bg-emerald-100 text-emerald-600',
                    icon: 'fa-solid fa-circle-check'
                };
            case 'error':
                return {
                    wrap: 'bg-red-100 text-red-600',
                    icon: 'fa-solid fa-circle-xmark'
                };
            case 'warning':
                return {
                    wrap: 'bg-amber-100 text-amber-600',
                    icon: 'fa-solid fa-triangle-exclamation'
                };
            case 'loading':
                return {
                    wrap: 'bg-amber-100 text-amber-600',
                    icon: 'fa-solid fa-spinner fa-spin'
                };
            default:
                return {
                    wrap: 'bg-indigo-100 text-indigo-600',
                    icon: 'fa-solid fa-circle-info'
                };
        }
    }

    function lockPageScroll() {
        scrollLockCount += 1;
        if (scrollLockCount !== 1) {
            return;
        }
        document.documentElement.classList.add('fc-admin-scroll-lock');
        document.body.classList.add('fc-admin-scroll-lock');
        var main = document.getElementById('fc-admin-main');
        if (main) {
            main.classList.add('fc-admin-scroll-lock');
        }
    }

    function unlockPageScroll() {
        scrollLockCount = Math.max(0, scrollLockCount - 1);
        if (scrollLockCount !== 0) {
            return;
        }
        document.documentElement.classList.remove('fc-admin-scroll-lock');
        document.body.classList.remove('fc-admin-scroll-lock');
        var main = document.getElementById('fc-admin-main');
        if (main) {
            main.classList.remove('fc-admin-scroll-lock');
        }
    }

    function ensureRoot() {
        var root = document.getElementById(ROOT_ID);
        if (!root) {
            root = document.createElement('div');
            root.id = ROOT_ID;
            root.className = 'pointer-events-none fixed inset-0 z-[9999]';
            root.setAttribute('aria-hidden', 'true');
            document.body.appendChild(root);
        } else if (root.parentNode !== document.body) {
            document.body.appendChild(root);
            root.className = 'pointer-events-none fixed inset-0 z-[9999]';
        }
        return root;
    }

    function removeModalEl(el, id) {
        if (!el) {
            return;
        }
        el.classList.add('opacity-0');
        var panel = el.querySelector('[data-fc-admin-modal-panel]');
        if (panel) {
            panel.classList.add('scale-95');
        }
        window.setTimeout(function () {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
            var root = document.getElementById(ROOT_ID);
            if (root && !root.children.length) {
                root.setAttribute('aria-hidden', 'true');
            }
            if (!rootHasModals()) {
                unlockPageScroll();
            }
        }, 180);
        if (id && modalsById[id] === el) {
            delete modalsById[id];
        }
        if (activePromiseModal === el) {
            activePromiseModal = null;
        }
    }

    function rootHasModals() {
        var root = document.getElementById(ROOT_ID);
        return !!(root && root.children.length);
    }

    function dismiss(id) {
        if (!id || !modalsById[id]) {
            return;
        }
        removeModalEl(modalsById[id], id);
    }

    function bindDismiss(el, id, resolve, value) {
        function finish(result) {
            removeModalEl(el, id);
            if (typeof resolve === 'function') {
                resolve(result);
            }
        }

        el.querySelectorAll('[data-fc-admin-modal-cancel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                finish(false);
            });
        });

        el.querySelectorAll('[data-fc-admin-modal-confirm]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.disabled) {
                    return;
                }
                var required = el.getAttribute('data-fc-admin-modal-confirm-text') || '';
                if (required) {
                    var input = el.querySelector('[data-fc-admin-modal-confirm-input]');
                    if (!input || String(input.value || '') !== required) {
                        return;
                    }
                }
                finish(true);
            });
        });

        el.querySelectorAll('[data-fc-admin-modal-ok]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                finish(value === undefined ? true : value);
            });
        });

        var backdrop = el.querySelector('[data-fc-admin-modal-backdrop]');
        if (backdrop && el.getAttribute('data-fc-admin-modal-dismissible') === 'true') {
            backdrop.addEventListener('click', function () {
                finish(false);
            });
        }

        el.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && el.getAttribute('data-fc-admin-modal-dismissible') === 'true') {
                finish(el.getAttribute('data-fc-admin-modal-mode') === 'confirm' ? false : true);
            }
        });
    }

    function bindConfirmTextGate(el, requiredText) {
        var input = el.querySelector('[data-fc-admin-modal-confirm-input]');
        var confirmBtn = el.querySelector('[data-fc-admin-modal-confirm]');
        if (!input || !confirmBtn || !requiredText) {
            return;
        }

        function sync() {
            var matched = String(input.value || '') === requiredText;
            confirmBtn.disabled = !matched;
            confirmBtn.setAttribute('aria-disabled', matched ? 'false' : 'true');
            confirmBtn.classList.toggle('is-disabled', !matched);
        }

        input.addEventListener('input', sync);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!confirmBtn.disabled) {
                    confirmBtn.click();
                }
            }
        });
        sync();
    }

    function btnClasses() {
        var B = global.FcAdminBtn || {};
        return {
            primary: B.primary || 'btn btn-sm btn-orange fw-semibold',
            secondary: B.secondary || 'btn btn-sm btn-dark fw-semibold'
        };
    }

    function renderModal(opts) {
        var variant = opts.variant || 'info';
        var icon = iconForVariant(variant);
        var title = opts.title || '';
        var message = opts.message || '';
        var mode = opts.mode || 'alert';
        var confirmLabel = opts.confirmLabel || 'Confirm';
        var cancelLabel = opts.cancelLabel || 'Cancel';
        var okLabel = opts.okLabel || 'OK';
        var confirmText = String(opts.confirmText || '');
        var confirmPrompt =
            opts.confirmPrompt ||
            (confirmText ? 'Type {confirm} to confirm.' : '');
        var dismissible = opts.dismissible !== false && mode !== 'loading';
        var buttonsHtml = '';
        var btn = btnClasses();

        if (mode === 'confirm') {
            buttonsHtml =
                '<button type="button" data-fc-admin-modal-cancel class="' +
                btn.secondary +
                '">' +
                escapeHtml(cancelLabel) +
                '</button>' +
                '<button type="button" data-fc-admin-modal-confirm class="' +
                btn.primary +
                (confirmText ? ' is-disabled' : '') +
                '"' +
                (confirmText ? ' disabled aria-disabled="true"' : '') +
                '>' +
                escapeHtml(confirmLabel) +
                '</button>';
        } else if (mode !== 'loading') {
            buttonsHtml =
                '<button type="button" data-fc-admin-modal-ok class="' +
                btn.primary +
                '">' +
                escapeHtml(okLabel) +
                '</button>';
        }

        if (!title) {
            if (variant === 'success') {
                title = 'Success';
            } else if (variant === 'error') {
                title = 'Error';
            } else if (variant === 'warning') {
                title = 'Warning';
            } else if (mode === 'confirm') {
                title = 'Confirm';
            } else if (mode === 'loading') {
                title = 'Please wait';
            } else {
                title = 'Notice';
            }
        }

        var closeAction = mode === 'confirm' ? 'cancel' : 'ok';
        var closeBtnHtml = dismissible
            ? '<button type="button" class="fencing-modal-close" data-fc-admin-modal-' +
              closeAction +
              ' aria-label="Close"></button>'
            : '';

        var bodyHtml = message
            ? '<p class="text-base leading-relaxed text-slate-600">' + escapeHtml(message) + '</p>'
            : '<p class="text-base leading-relaxed text-slate-500">&nbsp;</p>';

        if (mode === 'confirm' && confirmText) {
            var promptHtml = escapeHtml(confirmPrompt).replace(
                /\{confirm\}/g,
                '<span class="fc-admin-modal-confirm-token">' + escapeHtml(confirmText) + '</span>'
            );
            if (promptHtml.indexOf('fc-admin-modal-confirm-token') === -1) {
                promptHtml = escapeHtml(confirmPrompt).replace(
                    new RegExp(escapeHtml(confirmText).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'),
                    '<span class="fc-admin-modal-confirm-token">' + escapeHtml(confirmText) + '</span>'
                );
            }
            bodyHtml +=
                '<div class="fc-admin-modal-confirm-field mt-4">' +
                '<label class="mb-1.5 block text-sm font-medium text-slate-700" for="fc-admin-modal-confirm-input">' +
                promptHtml +
                '</label>' +
                '<input type="text" id="fc-admin-modal-confirm-input" data-fc-admin-modal-confirm-input class="fc-settings-field" autocomplete="off" spellcheck="false" autocapitalize="characters" placeholder="' +
                escapeHtml(confirmText) +
                '" aria-required="true">' +
                '</div>';
        }

        var footerHtml = buttonsHtml
            ? '<footer data-fc-admin-modal-footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50/80 px-6 py-4">' +
              buttonsHtml +
              '</footer>'
            : '';

        return (
            '<div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 opacity-0 transition-opacity duration-200 pointer-events-auto" role="dialog" aria-modal="true" aria-labelledby="fc-admin-modal-title" data-fc-admin-modal-mode="' +
            escapeHtml(mode) +
            '" data-fc-admin-modal-dismissible="' +
            (dismissible ? 'true' : 'false') +
            '"' +
            (confirmText
                ? ' data-fc-admin-modal-confirm-text="' + escapeHtml(confirmText) + '"'
                : '') +
            '>' +
            '<div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]" data-fc-admin-modal-backdrop aria-hidden="true"></div>' +
            '<div class="relative w-full max-w-md scale-95 overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200 transition-transform duration-200" data-fc-admin-modal-panel>' +
            closeBtnHtml +
            '<header data-fc-admin-modal-header class="flex items-center gap-3 border-b border-slate-200 px-6 py-4 pr-14">' +
            '<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full ' +
            icon.wrap +
            '">' +
            '<i class="' +
            icon.icon +
            ' text-base" aria-hidden="true"></i></span>' +
            '<h3 id="fc-admin-modal-title" class="min-w-0 flex-1 text-lg font-semibold text-slate-900 sm:text-xl">' +
            escapeHtml(title) +
            '</h3>' +
            '</header>' +
            '<div data-fc-admin-modal-body class="px-6 py-3">' +
            bodyHtml +
            '</div>' +
            footerHtml +
            '</div></div>'
        );
    }

    function openModal(opts) {
        opts = normalizeOpts(opts);
        var id = opts.id || '';
        var mode = opts.mode || 'alert';
        var confirmText = String(opts.confirmText || '');

        if (id && modalsById[id]) {
            dismiss(id);
        }

        var root = ensureRoot();
        var wrap = document.createElement('div');
        wrap.innerHTML = renderModal(opts);
        var el = wrap.firstElementChild;
        if (!el) {
            return Promise.resolve(mode === 'confirm' ? false : true);
        }

        root.appendChild(el);
        root.setAttribute('aria-hidden', 'false');
        lockPageScroll();
        if (id) {
            modalsById[id] = el;
        }
        activePromiseModal = el;

        return new Promise(function (resolve) {
            bindDismiss(el, id, resolve, mode === 'confirm' ? true : undefined);
            if (mode === 'confirm' && confirmText) {
                bindConfirmTextGate(el, confirmText);
            }

            requestAnimationFrame(function () {
                el.classList.remove('opacity-0');
                var panel = el.querySelector('[data-fc-admin-modal-panel]');
                if (panel) {
                    panel.classList.remove('scale-95');
                }
                var confirmInput = el.querySelector('[data-fc-admin-modal-confirm-input]');
                if (confirmInput) {
                    confirmInput.focus();
                    return;
                }
                var primary =
                    el.querySelector('[data-fc-admin-modal-confirm]') ||
                    el.querySelector('[data-fc-admin-modal-ok]');
                if (primary) {
                    primary.focus();
                }
            });
        });
    }

    function confirm(opts) {
        opts = normalizeOpts(opts);
        opts.mode = 'confirm';
        opts.variant = opts.variant || 'warning';
        opts.title = opts.title || 'Confirm';
        return openModal(opts);
    }

    function alert(opts) {
        opts = normalizeOpts(opts);
        opts.mode = 'alert';
        return openModal(opts);
    }

    function loading(message, id) {
        return openModal({
            message: message,
            mode: 'loading',
            variant: 'loading',
            id: id || 'fc-admin-modal-loading',
            dismissible: false
        });
    }

    global.FcAdminModal = {
        confirm: confirm,
        alert: alert,
        loading: loading,
        dismiss: dismiss,
        open: openModal,
        lockScroll: lockPageScroll,
        unlockScroll: unlockPageScroll
    };
})(window);
