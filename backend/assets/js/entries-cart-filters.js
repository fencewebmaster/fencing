/**
 * FC Admin — Planner entry detail cart search & fence style filters.
 */
(function () {
    'use strict';

    function selectedFenceValues(root) {
        var values = [];
        root.querySelectorAll('[data-fc-entries-fence-checkbox]:checked').forEach(function (input) {
            values.push(input.value);
        });
        return values;
    }

    function formatLines(visible, total) {
        var suffix = visible === 1 ? ' line' : ' lines';
        if (visible === total) {
            return visible + suffix;
        }
        return visible + ' of ' + total + suffix;
    }

    function formatUnits(visible, total) {
        if (visible === total) {
            return total + ' total units';
        }
        return visible + ' of ' + total + ' total units';
    }

    function applyCartFilters(container) {
        var panel = container.closest('.fc-entries-detail-panel--cart');
        if (!panel) {
            return;
        }

        var searchInput = container.querySelector('[data-fc-cart-search]');
        var fenceDropdown = container.querySelector('[data-fc-entries-fence-dropdown]');
        var rows = panel.querySelectorAll('[data-fc-cart-row]');
        var noResults = panel.querySelector('[data-fc-cart-no-results]');
        var footerLines = panel.querySelector('[data-fc-cart-footer-lines]');
        var footerUnits = panel.querySelector('[data-fc-cart-footer-units]');

        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var selectedFences = fenceDropdown ? selectedFenceValues(fenceDropdown) : [];
        var totalLines = parseInt(container.getAttribute('data-fc-cart-total-lines') || '0', 10);
        var totalUnits = parseInt(container.getAttribute('data-fc-cart-total-units') || '0', 10);
        var visibleLines = 0;
        var visibleUnits = 0;

        rows.forEach(function (row) {
            var haystack = row.getAttribute('data-fc-cart-search') || '';
            var fence = row.getAttribute('data-fc-cart-fence') || '';
            var qty = parseInt(row.getAttribute('data-fc-cart-qty') || '0', 10);
            var matchesSearch = query === '' || haystack.indexOf(query) !== -1;
            var matchesFence = selectedFences.length === 0
                || (fence !== '' && selectedFences.indexOf(fence) !== -1);
            var show = matchesSearch && matchesFence;

            row.hidden = !show;

            if (show) {
                visibleLines += 1;
                visibleUnits += qty;
            }
        });

        if (noResults) {
            noResults.hidden = visibleLines > 0 || rows.length === 0;
        }

        var tableWrap = panel.querySelector('.fc-entries-cart-table-wrap');
        if (tableWrap) {
            tableWrap.hidden = visibleLines === 0 && rows.length > 0;
        }

        if (footerLines) {
            footerLines.textContent = formatLines(visibleLines, totalLines);
        }

        if (footerUnits) {
            footerUnits.textContent = formatUnits(visibleUnits, totalUnits);
        }

        var clearBtn = container.querySelector('[data-fc-cart-clear]');
        if (clearBtn) {
            clearBtn.disabled = query === '' && selectedFences.length === 0;
        }
    }

    function clearCartFilters(container) {
        var searchInput = container.querySelector('[data-fc-cart-search]');
        var fenceDropdown = container.querySelector('[data-fc-entries-fence-dropdown]');

        if (searchInput) {
            searchInput.value = '';
        }

        if (fenceDropdown) {
            var fenceClear = fenceDropdown.querySelector('[data-fc-entries-fence-clear]');
            if (fenceClear) {
                fenceClear.click();
            }
        }

        applyCartFilters(container);
    }

    function initCartFilters(container) {
        var searchInput = container.querySelector('[data-fc-cart-search]');
        var fenceDropdown = container.querySelector('[data-fc-entries-fence-dropdown]');
        var clearBtn = container.querySelector('[data-fc-cart-clear]');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                applyCartFilters(container);
            });
        }

        if (fenceDropdown) {
            fenceDropdown.addEventListener('fc-entries-fence-change', function () {
                applyCartFilters(container);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (clearBtn.disabled) {
                    return;
                }
                clearCartFilters(container);
            });
        }

        applyCartFilters(container);
    }

    document.querySelectorAll('[data-fc-entries-cart-filters]').forEach(initCartFilters);
})();
