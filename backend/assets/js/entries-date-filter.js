/**
 * FC Admin — Planner entries date filter dropdown.
 */
(function () {
    'use strict';

    var DEFAULT_LABEL = 'All dates';
    var PRESET_LABELS = {
        today: 'Today',
        yesterday: 'Yesterday',
        this_week: 'This Week',
        last_7_days: 'Last 7 Days',
        last_2_weeks: 'Last 2 Weeks',
        last_3_weeks: 'Last 3 Weeks',
        last_4_weeks: 'Last 4 Weeks',
        this_month: 'This Month',
        last_month: 'Last Month',
        last_3_months: 'Last 3 Months',
        last_6_months: 'Last 6 Months',
        last_9_months: 'Last 9 Months',
        last_12_months: 'Last 12 Months',
        this_year: 'This Year',
        custom: 'Custom Range',
    };

    function getForm(root) {
        return root.closest('form');
    }

    function periodInput(root) {
        return root.querySelector('[data-fc-entries-date-period]');
    }

    function fromInput(root) {
        return root.querySelector('[data-fc-entries-date-from]');
    }

    function toInput(root) {
        return root.querySelector('[data-fc-entries-date-to]');
    }

    function customFromInput(root) {
        return root.querySelector('[data-fc-entries-date-custom-from]');
    }

    function customToInput(root) {
        return root.querySelector('[data-fc-entries-date-custom-to]');
    }

    function formatCustomLabel(from, to) {
        if (!from || !to) {
            return PRESET_LABELS.custom;
        }

        var fromDate = new Date(from + 'T00:00:00');
        var toDate = new Date(to + 'T00:00:00');
        if (isNaN(fromDate.getTime()) || isNaN(toDate.getTime())) {
            return PRESET_LABELS.custom;
        }

        var opts = { month: 'short', day: 'numeric', year: 'numeric' };
        return fromDate.toLocaleDateString(undefined, opts) + ' – ' + toDate.toLocaleDateString(undefined, opts);
    }

    function updateLabel(root) {
        var labelEl = root.querySelector('[data-fc-entries-date-label]');
        var period = periodInput(root);
        if (!labelEl || !period) {
            return;
        }

        var value = period.value || '';
        var label = DEFAULT_LABEL;

        if (value && value !== 'custom') {
            label = PRESET_LABELS[value] || value;
        } else if (value === 'custom') {
            label = formatCustomLabel(
                fromInput(root) ? fromInput(root).value : '',
                toInput(root) ? toInput(root).value : ''
            );
        }

        labelEl.textContent = label;
        root.classList.toggle('is-active', value !== '');
        root.classList.toggle('is-custom', value === 'custom');
    }

    function syncSelectedState(root) {
        var current = periodInput(root) ? periodInput(root).value : '';
        root.querySelectorAll('[data-fc-entries-date-preset]').forEach(function (btn) {
            var key = btn.getAttribute('data-fc-entries-date-preset') || '';
            var selected = key === current;
            btn.classList.toggle('is-selected', selected);
            btn.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
    }

    function toggleCustomPanel(root, show) {
        var panel = root.querySelector('[data-fc-entries-date-custom]');
        if (!panel) {
            return;
        }
        panel.hidden = !show;
    }

    function closeDropdown(root) {
        var toggle = root.querySelector('.fc-entries-date-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-date-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
    }

    function openDropdown(root) {
        var toggle = root.querySelector('.fc-entries-date-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-date-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }

        document.querySelectorAll('[data-fc-entries-date-dropdown].is-open').forEach(function (other) {
            if (other !== root) {
                closeDropdown(other);
            }
        });

        document.querySelectorAll(
            '[data-fc-entries-multi-dropdown].is-open, [data-fc-entries-fence-dropdown].is-open'
        ).forEach(function (fence) {
            var fencePanel = fence.querySelector('.fc-entries-fence-dropdown__panel');
            var fenceToggle = fence.querySelector('.fc-entries-fence-dropdown__toggle');
            if (fencePanel) {
                fencePanel.hidden = true;
            }
            if (fenceToggle) {
                fenceToggle.setAttribute('aria-expanded', 'false');
            }
            fence.classList.remove('is-open');
        });

        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');

        if (periodInput(root) && periodInput(root).value === 'custom') {
            toggleCustomPanel(root, true);
        }
    }

    function submitForm(root) {
        var form = getForm(root);
        if (form) {
            form.submit();
            return;
        }

        root.dispatchEvent(new CustomEvent('fc-entries-date-change', {
            bubbles: true,
            detail: {
                period: periodInput(root) ? periodInput(root).value : '',
                from: fromInput(root) ? fromInput(root).value : '',
                to: toInput(root) ? toInput(root).value : '',
            },
        }));
    }

    function syncUi(root) {
        syncSelectedState(root);
        updateLabel(root);
        toggleCustomPanel(root, periodInput(root) && periodInput(root).value === 'custom');
    }

    function setPreset(root, key, submit) {
        var period = periodInput(root);
        var from = fromInput(root);
        var to = toInput(root);
        if (!period) {
            return;
        }

        period.value = key;

        if (key === 'custom') {
            toggleCustomPanel(root, true);
            syncSelectedState(root);
            updateLabel(root);
            var customFrom = customFromInput(root);
            if (customFrom) {
                customFrom.focus();
            }
            return;
        }

        if (from) {
            from.value = '';
        }
        if (to) {
            to.value = '';
        }

        toggleCustomPanel(root, false);
        syncSelectedState(root);
        updateLabel(root);

        if (submit) {
            closeDropdown(root);
            submitForm(root);
        }
    }

    function applyCustomRange(root) {
        var customFrom = customFromInput(root);
        var customTo = customToInput(root);
        var from = fromInput(root);
        var to = toInput(root);
        var period = periodInput(root);

        if (!customFrom || !customTo || !from || !to || !period) {
            return;
        }

        if (!customFrom.value || !customTo.value) {
            customFrom.reportValidity && customFrom.reportValidity();
            return;
        }

        var start = customFrom.value;
        var end = customTo.value;
        if (start > end) {
            start = customTo.value;
            end = customFrom.value;
            customFrom.value = start;
            customTo.value = end;
        }

        period.value = 'custom';
        from.value = start;
        to.value = end;
        syncSelectedState(root);
        updateLabel(root);
        closeDropdown(root);
        submitForm(root);
    }

    function clearDates(root) {
        var period = periodInput(root);
        var from = fromInput(root);
        var to = toInput(root);
        var customFrom = customFromInput(root);
        var customTo = customToInput(root);

        if (period) {
            period.value = '';
        }
        if (from) {
            from.value = '';
        }
        if (to) {
            to.value = '';
        }
        if (customFrom) {
            customFrom.value = '';
        }
        if (customTo) {
            customTo.value = '';
        }

        toggleCustomPanel(root, false);
        syncSelectedState(root);
        updateLabel(root);
        closeDropdown(root);
        submitForm(root);
    }

    function initDateDropdown(root) {
        if (root.getAttribute('data-fc-entries-date-initialized') === '1') {
            return;
        }
        root.setAttribute('data-fc-entries-date-initialized', '1');

        var toggle = root.querySelector('.fc-entries-date-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-date-dropdown__panel');
        var clearBtn = root.querySelector('[data-fc-entries-date-clear]');
        var applyCustomBtn = root.querySelector('[data-fc-entries-date-apply-custom]');

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

        root.querySelectorAll('[data-fc-entries-date-preset]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var key = btn.getAttribute('data-fc-entries-date-preset') || '';
                setPreset(root, key, key !== 'custom');
            });
        });

        if (applyCustomBtn) {
            applyCustomBtn.addEventListener('click', function (e) {
                e.preventDefault();
                applyCustomRange(root);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearDates(root);
            });
        }

        [customFromInput(root), customToInput(root)].forEach(function (input) {
            if (!input) {
                return;
            }
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyCustomRange(root);
                }
            });
        });

        document.addEventListener('click', function () {
            closeDropdown(root);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDropdown(root);
            }
        });

        syncSelectedState(root);
        updateLabel(root);
        toggleCustomPanel(root, periodInput(root) && periodInput(root).value === 'custom');
    }

    window.FcEntriesDateFilter = {
        syncUi: syncUi,
        init: initDateDropdown,
    };

    document.querySelectorAll('[data-fc-entries-date-dropdown]').forEach(initDateDropdown);
})();
