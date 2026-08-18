/**
 * FC Admin — Copy planner share link (click) or ID (Ctrl/Cmd+click) from Planner ID column.
 */
(function () {
    'use strict';

    var copyText = window.FC.util.copyToClipboard;

    var tooltip = new window.FC.components.CopyTooltip({
        toastClassName: 'fc-entries-planner-copy-toast',
        onReset: function () {
            document.querySelectorAll('.fc-entries-planner-id-wrap.is-copied').forEach(function (wrap) {
                wrap.classList.remove('is-copied');
            });
        }
    });

    function showCopiedEffect(button, label) {
        var wrap = button.closest('.fc-entries-planner-id-wrap');
        if (!wrap) {
            return;
        }

        tooltip.reset();
        wrap.classList.add('is-copied');
        tooltip.display(button, label);
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

    document.querySelectorAll('[data-fc-copy-planner-url]').forEach(initCopyButton);
})();
