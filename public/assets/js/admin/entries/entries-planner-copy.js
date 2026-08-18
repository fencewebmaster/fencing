/**
 * FC Admin — Copy planner share link (click) or ID (Ctrl/Cmd+click) from Planner ID column.
 */
(function () {
    'use strict';

    var COPIED_MS = 1800;
    var TOAST_GAP = 10;
    var toastEl = null;
    var hideTimer = null;

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                if (document.execCommand('copy')) {
                    resolve();
                } else {
                    reject(new Error('Copy failed'));
                }
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(textarea);
            }
        });
    }

    function getToast() {
        if (toastEl) {
            return toastEl;
        }

        toastEl = document.createElement('div');
        toastEl.className = 'fc-entries-planner-copy-toast';
        toastEl.setAttribute('role', 'status');
        toastEl.setAttribute('aria-live', 'polite');
        toastEl.textContent = 'Copied!';
        document.body.appendChild(toastEl);

        return toastEl;
    }

    function clearCopiedState() {
        if (hideTimer) {
            window.clearTimeout(hideTimer);
            hideTimer = null;
        }

        if (toastEl) {
            toastEl.classList.remove('is-visible', 'is-below');
        }

        document.querySelectorAll('.fc-entries-planner-id-wrap.is-copied').forEach(function (wrap) {
            wrap.classList.remove('is-copied');
        });
    }

    function positionToast(button) {
        var toast = getToast();
        var rect = button.getBoundingClientRect();
        var toastRect = toast.getBoundingClientRect();
        var toastHeight = toastRect.height || 28;
        var placeBelow = rect.top < toastHeight + TOAST_GAP + 8;
        var centerX = rect.left + rect.width / 2;

        toast.style.left = centerX + 'px';
        toast.classList.toggle('is-below', placeBelow);

        if (placeBelow) {
            toast.style.top = rect.bottom + TOAST_GAP + 'px';
        } else {
            toast.style.top = rect.top - TOAST_GAP + 'px';
        }
    }

    function showCopiedEffect(button, label) {
        var wrap = button.closest('.fc-entries-planner-id-wrap');
        if (!wrap) {
            return;
        }

        clearCopiedState();
        wrap.classList.add('is-copied');

        var toast = getToast();
        toast.textContent = label || 'Copied!';
        toast.classList.add('is-visible');
        positionToast(button);

        hideTimer = window.setTimeout(clearCopiedState, COPIED_MS);
    }

    function initCopyButton(button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var copyId = e.ctrlKey || e.metaKey;
            var text = copyId
                ? button.getAttribute('data-fc-copy-planner-id')
                : button.getAttribute('data-fc-copy-planner-url');
            if (!text) {
                return;
            }

            var toastLabel = copyId ? 'ID copied!' : 'Link copied!';
            var promptLabel = copyId ? 'Copy planner ID:' : 'Copy planner link:';

            copyText(text)
                .then(function () {
                    showCopiedEffect(button, toastLabel);
                })
                .catch(function () {
                    window.prompt(promptLabel, text);
                });
        });
    }

    window.addEventListener(
        'scroll',
        function () {
            if (toastEl && toastEl.classList.contains('is-visible')) {
                clearCopiedState();
            }
        },
        true
    );

    window.addEventListener('resize', function () {
        if (toastEl && toastEl.classList.contains('is-visible')) {
            clearCopiedState();
        }
    });

    document.querySelectorAll('[data-fc-copy-planner-url]').forEach(initCopyButton);
})();
