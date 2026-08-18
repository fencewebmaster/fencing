/**
 * FC Admin — Planner entries filter UI (fence type dropdown).
 */
(function () {
    'use strict';

    var DEFAULT_LABEL = 'All options';
    var CHECKBOX_SELECTOR = '[data-fc-entries-multi-checkbox], [data-fc-entries-fence-checkbox]';
    var CHECKED_SELECTOR = '[data-fc-entries-multi-checkbox]:checked, [data-fc-entries-fence-checkbox]:checked';

    function defaultLabel(root) {
        return root.getAttribute('data-fc-entries-multi-default-label')
            || root.getAttribute('data-fc-entries-fence-default-label')
            || DEFAULT_LABEL;
    }

    function selectedValues(root) {
        var values = [];
        root.querySelectorAll(CHECKED_SELECTOR).forEach(function (input) {
            values.push(input.value);
        });
        return values;
    }
    function selectedLabels(root) {
        var labels = [];
        root.querySelectorAll(CHECKED_SELECTOR).forEach(function (input) {
            var option = input.closest('.fc-entries-fence-dropdown__option');
            var textEl = option ? option.querySelector('span') : null;
            labels.push(textEl ? textEl.textContent.trim() : input.value);
        });
        return labels;
    }

    function notifyChange(root) {
        root.dispatchEvent(new CustomEvent('fc-entries-multi-change', {
            bubbles: true,
            detail: { values: selectedValues(root) },
        }));
        root.dispatchEvent(new CustomEvent('fc-entries-fence-change', {
            bubbles: true,
            detail: { values: selectedValues(root) },
        }));
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
        var labelEl = root.querySelector(
            '[data-fc-entries-multi-label], [data-fc-entries-fence-label]'
        );
        if (!labelEl) {
            return;
        }
        labelEl.textContent = formatLabel(selectedLabels(root), root);
        root.classList.toggle('is-active', selectedLabels(root).length > 0);
    }

    function syncOptionState(root) {
        root.querySelectorAll('.fc-entries-fence-dropdown__option').forEach(function (option) {
            var input = option.querySelector(CHECKBOX_SELECTOR);
            if (!input) {
                return;
            }
            option.setAttribute('aria-selected', input.checked ? 'true' : 'false');
        });
    }

    function closeDropdown(root) {
        window.FC.components.DropdownRegistry.notifyClosed(root);
        var toggle = root.querySelector('.fc-entries-fence-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-fence-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }
        panel.hidden = true;
        panel.style.removeProperty('position');
        panel.style.removeProperty('top');
        panel.style.removeProperty('right');
        panel.style.removeProperty('bottom');
        panel.style.removeProperty('left');
        panel.style.removeProperty('width');
        panel.style.removeProperty('max-width');
        panel.style.removeProperty('min-width');
        toggle.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
    }

    function positionDropdown(root, panel, toggle) {
        if (!root.closest('[data-fc-entries-advanced-modal]')) {
            return;
        }

        var rect = toggle.getBoundingClientRect();
        var viewportGap = 16;
        var panelGap = 6;
        var width = Math.max(rect.width, 1);
        var left = rect.left;
        var roomBelow = window.innerHeight - rect.bottom - viewportGap;
        var roomAbove = rect.top - viewportGap;

        panel.style.position = 'fixed';
        panel.style.left = left + 'px';
        panel.style.width = width + 'px';
        panel.style.maxWidth = width + 'px';
        panel.style.minWidth = width + 'px';
        if (roomBelow >= 280 || roomBelow >= roomAbove) {
            panel.style.top = (rect.bottom + panelGap) + 'px';
            panel.style.removeProperty('bottom');
        } else {
            panel.style.bottom = (window.innerHeight - rect.top + panelGap) + 'px';
            panel.style.removeProperty('top');
        }
    }

    function openDropdown(root) {
        var toggle = root.querySelector('.fc-entries-fence-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-fence-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }

        window.FC.components.DropdownRegistry.openExclusive(root, function () {
            closeDropdown(root);
        });

        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
        positionDropdown(root, panel, toggle);
    }

    function initDropdown(root) {
        var toggle = root.querySelector('.fc-entries-fence-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-fence-dropdown__panel');
        var clearBtn = root.querySelector(
            '[data-fc-entries-multi-clear], [data-fc-entries-fence-clear]'
        );

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

        root.querySelectorAll(CHECKBOX_SELECTOR).forEach(function (input) {
            input.addEventListener('change', function () {
                syncOptionState(root);
                updateLabel(root);
                notifyChange(root);
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                root.querySelectorAll(CHECKED_SELECTOR).forEach(function (input) {
                    input.checked = false;
                });
                syncOptionState(root);
                updateLabel(root);
                notifyChange(root);
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

        window.addEventListener('resize', function () {
            if (!panel.hidden) {
                positionDropdown(root, panel, toggle);
            }
        });

        var modalBody = root.closest('.fc-entries-advanced-search__body');
        if (modalBody) {
            modalBody.addEventListener('scroll', function () {
                if (!panel.hidden) {
                    positionDropdown(root, panel, toggle);
                }
            });
        }

        syncOptionState(root);
        updateLabel(root);
    }

    function initAdvancedSearchModal() {
        var modal = document.querySelector('[data-fc-entries-advanced-modal]');
        var trigger = document.querySelector('[data-fc-entries-advanced-open]');
        if (!modal || !trigger) {
            return;
        }

        var searchGroup = document.querySelector('.fc-entries-page__search-group');
        var dateField = modal.querySelector('.fc-entries-advanced-search__field--date');
        var dateFilter = dateField
            ? dateField.querySelector('.fc-entries-date-filter-group')
            : null;
        var clearFilters = searchGroup
            ? searchGroup.querySelector('[data-fc-entries-clear-filters]')
            : null;
        if (searchGroup && dateField && dateFilter) {
            searchGroup.insertBefore(dateFilter, clearFilters || trigger);
            dateField.remove();
        }

        var dialog = modal.querySelector('.fc-entries-advanced-search__dialog');
        var closeButtons = modal.querySelectorAll('[data-fc-entries-advanced-close]');
        var previousFocus = null;

        function focusableElements() {
            if (!dialog) {
                return [];
            }
            return Array.from(dialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter(function (element) {
                return !element.hidden && element.offsetParent !== null;
            });
        }

        function closeNestedDropdowns() {
            modal.querySelectorAll(
                '[data-fc-entries-multi-dropdown].is-open, [data-fc-entries-fence-dropdown].is-open'
            ).forEach(closeDropdown);
            modal.querySelectorAll('[data-fc-entries-date-dropdown].is-open').forEach(function (root) {
                var panel = root.querySelector('.fc-entries-date-dropdown__panel');
                var toggle = root.querySelector('.fc-entries-date-dropdown__toggle');
                if (panel) {
                    panel.hidden = true;
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
                root.classList.remove('is-open');
            });
        }

        function openModal() {
            previousFocus = document.activeElement;
            modal.hidden = false;
            modal.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            document.body.classList.add('fc-entries-advanced-search-open');
            window.FcAdminModal.lockScroll();
            window.requestAnimationFrame(function () {
                var firstField = dialog.querySelector('select, input:not([type="hidden"])');
                (firstField || dialog).focus();
            });
        }

        function closeModal() {
            if (modal.hidden) {
                return;
            }
            closeNestedDropdowns();
            modal.classList.remove('is-open');
            modal.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('fc-entries-advanced-search-open');
            window.FcAdminModal.unlockScroll();
            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus();
            } else {
                trigger.focus();
            }
        }

        trigger.addEventListener('click', openModal);
        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (modal.hidden) {
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal();
                return;
            }
            if (event.key !== 'Tab') {
                return;
            }

            var focusable = focusableElements();
            if (!focusable.length) {
                event.preventDefault();
                dialog.focus();
                return;
            }
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    }

    initAdvancedSearchModal();
    document.querySelectorAll(
        '[data-fc-entries-multi-dropdown], [data-fc-entries-fence-dropdown]'
    ).forEach(initDropdown);
})();
