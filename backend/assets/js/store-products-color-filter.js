/**
 * FC Admin — System products color filter dropdown.
 */
(function () {
    'use strict';

    var DEFAULT_LABEL = 'All colors';

    function defaultLabel(root) {
        return root.getAttribute('data-fc-store-products-color-default-label') || DEFAULT_LABEL;
    }

    function selectedLabels(root) {
        var labels = [];
        root.querySelectorAll('[data-fc-store-products-color-checkbox]:checked').forEach(function (input) {
            var option = input.closest('.fc-store-products-color-option');
            var textEl = option ? option.querySelector('.fc-store-products-color-option__label > span:last-child') : null;
            labels.push(textEl ? textEl.textContent.trim() : input.value);
        });
        return labels;
    }

    function formatLabel(labels, root) {
        if (!labels.length) {
            return defaultLabel(root);
        }
        if (labels.length === 1) {
            return labels[0];
        }
        if (labels.length === 2) {
            return labels[0] + ', ' + labels[1];
        }
        return labels[0] + ', ' + labels[1] + ' (+' + (labels.length - 2) + ')';
    }

    function updateLabel(root) {
        var labelEl = root.querySelector('[data-fc-store-products-color-label]');
        if (!labelEl) {
            return;
        }
        var labels = selectedLabels(root);
        labelEl.textContent = formatLabel(labels, root);
        root.classList.toggle('is-active', labels.length > 0);
    }

    function syncOptionState(root) {
        root.querySelectorAll('.fc-store-products-color-option').forEach(function (option) {
            var input = option.querySelector('[data-fc-store-products-color-checkbox]');
            if (!input) {
                return;
            }
            option.setAttribute('aria-selected', input.checked ? 'true' : 'false');
        });
    }

    function closeDropdown(root) {
        var toggle = root.querySelector('.fc-entries-fence-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-fence-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
    }

    function openDropdown(root) {
        var toggle = root.querySelector('.fc-entries-fence-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-fence-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }

        document.querySelectorAll('[data-fc-entries-fence-dropdown].is-open').forEach(function (otherRoot) {
            var otherPanel = otherRoot.querySelector('.fc-entries-fence-dropdown__panel');
            var otherToggle = otherRoot.querySelector('.fc-entries-fence-dropdown__toggle');
            if (otherPanel) {
                otherPanel.hidden = true;
            }
            if (otherToggle) {
                otherToggle.setAttribute('aria-expanded', 'false');
            }
            otherRoot.classList.remove('is-open');
        });

        document.querySelectorAll('[data-fc-store-products-color-dropdown].is-open').forEach(function (otherRoot) {
            if (otherRoot === root) {
                return;
            }
            closeDropdown(otherRoot);
        });

        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
    }

    function initDropdown(root) {
        var toggle = root.querySelector('.fc-entries-fence-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-fence-dropdown__panel');
        var clearBtn = root.querySelector('[data-fc-store-products-color-clear]');
        var form = root.closest('form');

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (panel.hidden) {
                openDropdown(root);
            } else {
                closeDropdown(root);
            }
        });

        panel.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        root.querySelectorAll('[data-fc-store-products-color-checkbox]').forEach(function (input) {
            input.addEventListener('change', function () {
                syncOptionState(root);
                updateLabel(root);
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                root.querySelectorAll('[data-fc-store-products-color-checkbox]:checked').forEach(function (input) {
                    input.checked = false;
                });
                syncOptionState(root);
                updateLabel(root);
                if (form) {
                    form.submit();
                }
            });
        }

        document.addEventListener('click', function () {
            closeDropdown(root);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDropdown(root);
            }
        });

        syncOptionState(root);
        updateLabel(root);
    }

    document.querySelectorAll('[data-fc-store-products-color-dropdown]').forEach(initDropdown);
})();
