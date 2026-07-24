/**
 * FC Admin — Copy planner detail fields with tooltip feedback.
 */
(function () {
    'use strict';

    var COPIED_MS = 1800;
    var TOAST_GAP = 10;
    var toastEl = null;
    var hideTimer = null;
    var activeButton = null;

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

        if (activeButton) {
            activeButton.classList.remove('is-copied');
            var icon = activeButton.querySelector('i');
            if (icon && activeButton._fcCopyIconClass) {
                icon.className = activeButton._fcCopyIconClass;
                delete activeButton._fcCopyIconClass;
            }
            activeButton = null;
        }
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

    function showCopiedEffect(button, message) {
        clearCopiedState();
        activeButton = button;
        button.classList.add('is-copied');

        var icon = button.querySelector('i');
        if (icon) {
            button._fcCopyIconClass = icon.className;
            icon.className = 'fa-solid fa-check';
        }

        var toast = getToast();
        toast.textContent = message || 'Copied!';
        toast.classList.add('is-visible');
        positionToast(button);

        hideTimer = window.setTimeout(clearCopiedState, COPIED_MS);
    }

    function initCopyButton(button) {
        if (!button || button.getAttribute('data-fc-detail-copy-bound') === '1') {
            return;
        }
        button.setAttribute('data-fc-detail-copy-bound', '1');

        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var text = button.getAttribute('data-fc-copy-text');
            if (text == null) {
                return;
            }

            copyText(text)
                .then(function () {
                    showCopiedEffect(button, 'Copied!');
                })
                .catch(function () {
                    showCopiedEffect(button, 'Copy failed');
                });
        });
    }

    function initDetailPanel(panel) {
        if (!panel) {
            return;
        }

        panel.querySelectorAll('[data-fc-copy-text]').forEach(initCopyButton);
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

    document.querySelectorAll('[data-fc-entries-detail-panel="planner"]').forEach(initDetailPanel);
})();
