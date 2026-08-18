/**
 * FC Admin — Copy planner detail fields with tooltip feedback.
 */
(function () {
    'use strict';

    var activeButton = null;

    var copyText = window.FC.util.copyToClipboard;

    var tooltip = new window.FC.components.CopyTooltip({
        toastClassName: 'fc-entries-planner-copy-toast',
        onReset: function () {
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
    });

    function showCopiedEffect(button, message) {
        tooltip.reset();
        activeButton = button;
        button.classList.add('is-copied');

        var icon = button.querySelector('i');
        if (icon) {
            button._fcCopyIconClass = icon.className;
            icon.className = 'fa-solid fa-check';
        }

        tooltip.display(button, message);
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

    document.querySelectorAll('[data-fc-entries-detail-panel="planner"]').forEach(initDetailPanel);
})();
