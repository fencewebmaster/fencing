/**
 * FC Admin — Store Products page (products.csv via api.php).
 * Drag rows to reorder; order is saved back to products.csv.
 */
(function (global) {
    'use strict';

    var API_LOAD = fcApiUrl('products', 'action=store-products');
    var API_REORDER = fcApiUrl('products', 'action=reorder-store-products');
    var API_UPDATE = fcApiUrl('products', 'action=update-store-product');
    var API_WC_SKU_INDEX = fcApiUrl('products', 'action=wc-sku-index');

    var DETAILS_COLUMNS = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE'];
    var LIST_DISPLAY_COLUMNS = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'SKUs', 'STYLE', 'Colors'];

    var editModalEl = null;
    var editFormEl = null;
    var editRowIndex = null;
    var editFormDirty = false;
    var dragJustEnded = false;
    var editModalCloseTimer = null;
    var MODAL_TRANSITION_MS = 280;
    var wcSkuSet = null;
    var wcSkuList = null;
    var wcSkuMetaBySku = null;
    var wcSkuSetPromise = null;
    var skuStatusTimer = null;
    var activeSkuSuggestWrap = null;
    var activeSkuSuggestMenu = null;
    var activeSkuSuggestMatches = null;
    var skuSuggestRepositionBound = null;
    var SKU_SUGGEST_LIMIT = 24;
    var skuGalleryEl = null;
    var skuGalleryKeydownHandler = null;
    var skuGalleryState = { slides: [], index: 0 };
    var GALLERY_BODY_CLASS = 'fc-entries-cart-gallery-open';

    var TOAST_CSV_REORDER = 'fc-csv-reorder';
    var TOAST_CSV_UPDATE = 'fc-csv-update';
    var FLASH_KEY = 'fc-store-products-save-flash';

    // NOTE: intentionally registered under the *system-products* route —
    // see the matching note in products/system-products.js. Route/controller
    // naming is cross-wired throughout this app; pre-existing and
    // self-consistent, do not "fix" it here.
    class StoreProductsPage extends global.FC.PageController {
        hydrate(container) {
            hydrateFromServer(container);
        }
    }
    var pageController = new StoreProductsPage();

    var flashMessage = new global.FC.util.FlashMessage({
        storageKey: FLASH_KEY,
        noticeSelector: '[data-fc-store-products-notice]',
        defaultRoot: function () {
            return document.querySelector('[data-fc-store-products-server]');
        }
    });

    function setFlash(message, type) {
        flashMessage.set(message, type);
    }

    function consumeFlash() {
        return flashMessage.consume();
    }

    function showHeaderNotice(root, flashData) {
        flashMessage.renderInto(root, flashData);
    }

    function reloadWithNotice(message, type) {
        setFlash(message, type === 'error' ? 'error' : 'success');
        try {
            var next = new URL(window.location.href);
            window.location.assign(next.pathname + next.search);
        } catch (e) {
            window.location.reload();
        }
    }

    var FILTER_URL_KEYS = {
        supplier: 'supplier',
        style: 'style',
        q: 'q'
    };

    function readFiltersFromUrl() {
        var params = new URLSearchParams(window.location.search);

        return {
            supplier: params.get(FILTER_URL_KEYS.supplier) || '',
            style: params.get(FILTER_URL_KEYS.style) || '',
            q: params.get(FILTER_URL_KEYS.q) || ''
        };
    }

    function syncFiltersToUrl(supplier, style, q) {
        var params = new URLSearchParams();
        if (supplier) {
            params.set(FILTER_URL_KEYS.supplier, supplier);
        }
        if (style) {
            params.set(FILTER_URL_KEYS.style, style);
        }
        if (q) {
            params.set(FILTER_URL_KEYS.q, q);
        }

        var search = params.toString();
        var nextUrl = window.location.pathname + (search ? '?' + search : '');
        var currentUrl = window.location.pathname + window.location.search;
        if (nextUrl === currentUrl) {
            return;
        }

        var state =
            window.history.state && typeof window.history.state === 'object'
                ? window.history.state
                : {};
        window.history.replaceState(state, '', nextUrl);
    }

    var SORT_URL_KEYS = { column: 'sort', dir: 'dir' };

    function readSortFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var dir = params.get(SORT_URL_KEYS.dir);

        return {
            column: params.get(SORT_URL_KEYS.column) || '',
            dir: dir === 'desc' ? 'desc' : 'asc'
        };
    }

    // Sorting is server-driven (see ProductsController::queryStoreProducts) so it's
    // correct across pagination — clicking a header navigates instead of re-sorting
    // whatever subset of rows happens to be loaded in the browser.
    function navigateWithSort(column) {
        var current = readSortFromUrl();
        var params = new URLSearchParams(window.location.search);
        if (current.column === column) {
            if (current.dir === 'asc') {
                params.set(SORT_URL_KEYS.dir, 'desc');
            } else {
                params.delete(SORT_URL_KEYS.column);
                params.delete(SORT_URL_KEYS.dir);
            }
        } else {
            params.set(SORT_URL_KEYS.column, column);
            params.set(SORT_URL_KEYS.dir, 'asc');
        }
        params.delete('page');
        var search = params.toString();
        window.location.assign(window.location.pathname + (search ? '?' + search : ''));
    }

    function getDetailsColumns(allColumns) {
        return DETAILS_COLUMNS.filter(function (col) {
            return allColumns.indexOf(col) !== -1;
        });
    }

    function getSkuColumns(allColumns) {
        return allColumns
            .filter(function (col) {
                return DETAILS_COLUMNS.indexOf(col) === -1;
            })
            .sort(function (a, b) {
                return formatHeader(a).localeCompare(formatHeader(b), undefined, { sensitivity: 'base' });
            });
    }

    function filterSkuColumnsByStyle(skuColumns, styleKey, styleColorsMap) {
        if (!styleColorsMap || !styleKey) {
            return skuColumns;
        }

        var allowed = styleColorsMap[String(styleKey).trim()];
        if (!Array.isArray(allowed) || !allowed.length) {
            return skuColumns;
        }

        var allowedSet = {};
        allowed.forEach(function (col) {
            allowedSet[String(col).toUpperCase()] = true;
        });

        var filtered = skuColumns.filter(function (col) {
            return !!allowedSet[String(col).toUpperCase()];
        });

        return filtered.length ? filtered : skuColumns;
    }

    function getListDisplayColumns(allColumns) {
        return LIST_DISPLAY_COLUMNS.filter(function (col) {
            if (col === 'SKUs' || col === 'Colors') {
                return true;
            }
            return allColumns.indexOf(col) !== -1;
        });
    }

    function insertSkusColumn(columns) {
        var out = [];
        var inserted = false;
        (columns || []).forEach(function (col) {
            if (col === 'SKUs') {
                return;
            }
            out.push(col);
            if (col === 'SUPPLIER') {
                out.push('SKUs');
                inserted = true;
            }
        });
        if (!inserted) {
            var styleIdx = out.indexOf('STYLE');
            if (styleIdx >= 0) {
                out.splice(styleIdx, 0, 'SKUs');
            } else {
                out.push('SKUs');
            }
        }
        return out;
    }

    function normalizeSkuValue(value) {
        return String(value || '').trim();
    }

    /** "OFF" marks a colour this product is deliberately not sold in — not a missing SKU. */
    function isSkuOffValue(value) {
        return normalizeSkuValue(value).toUpperCase() === 'OFF';
    }

    function isSkuMissingValue(value) {
        var sku = normalizeSkuValue(value);
        return sku === '' || sku.toUpperCase() === 'OFF';
    }

    /** Counts toward the x/y tally: either a real catalogue SKU, or a deliberate OFF. */
    function skuCountsAsComplete(value) {
        return isSkuOffValue(value) || skuExistsInCatalogue(value);
    }

    function skuExistsInCatalogue(value) {
        if (!wcSkuSet || isSkuMissingValue(value)) {
            return false;
        }
        return wcSkuSet.has(normalizeSkuValue(value));
    }

    function rememberWcSkuMeta(sku, name, image) {
        if (!wcSkuMetaBySku) {
            wcSkuMetaBySku = Object.create(null);
        }
        wcSkuMetaBySku[sku] = {
            sku: sku,
            name: String(name || '').trim(),
            image: String(image || '').trim()
        };
    }

    function metaForSku(sku) {
        var key = normalizeSkuValue(sku);
        if (wcSkuMetaBySku && wcSkuMetaBySku[key]) {
            return wcSkuMetaBySku[key];
        }
        return { sku: key, name: '', image: '' };
    }

    function ensureWcSkuIndex() {
        if (wcSkuSet && Array.isArray(wcSkuList)) {
            return Promise.resolve(wcSkuSet);
        }
        if (wcSkuSetPromise) {
            return wcSkuSetPromise;
        }
        wcSkuSetPromise = fetch(API_WC_SKU_INDEX, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return { ok: false, skus: [], products: [] };
                });
            })
            .then(function (body) {
                var set = new Set();
                var list = [];
                wcSkuMetaBySku = Object.create(null);
                if (body && body.ok && Array.isArray(body.products) && body.products.length) {
                    body.products.forEach(function (row) {
                        if (!row || typeof row !== 'object') {
                            return;
                        }
                        var normalized = normalizeSkuValue(row.sku);
                        if (normalized === '' || set.has(normalized)) {
                            return;
                        }
                        set.add(normalized);
                        list.push(normalized);
                        rememberWcSkuMeta(normalized, formatProductName(row.name), row.image);
                    });
                } else if (body && body.ok && Array.isArray(body.skus)) {
                    body.skus.forEach(function (sku) {
                        var normalized = normalizeSkuValue(sku);
                        if (normalized !== '' && !set.has(normalized)) {
                            set.add(normalized);
                            list.push(normalized);
                            rememberWcSkuMeta(normalized, '', '');
                        }
                    });
                }
                wcSkuSet = set;
                wcSkuList = list;
                return wcSkuSet;
            })
            .catch(function () {
                wcSkuSet = new Set();
                wcSkuList = [];
                wcSkuMetaBySku = Object.create(null);
                return wcSkuSet;
            })
            .then(function (set) {
                wcSkuSetPromise = null;
                return set;
            });

        return wcSkuSetPromise;
    }

    function commonPrefixLength(a, b) {
        var n = Math.min(a.length, b.length);
        var i = 0;
        while (i < n && a.charAt(i) === b.charAt(i)) {
            i += 1;
        }
        return i;
    }

    function suggestSkusFor(query, limit) {
        limit = limit || SKU_SUGGEST_LIMIT;
        var q = normalizeSkuValue(query);
        if (!q || q.toUpperCase() === 'OFF' || !Array.isArray(wcSkuList) || !wcSkuList.length) {
            return [];
        }

        var qLower = q.toLowerCase();
        var minPrefix = qLower.length >= 3 ? 3 : 1;
        var scored = [];

        for (var i = 0; i < wcSkuList.length; i++) {
            var sku = normalizeSkuValue(wcSkuList[i]);
            if (!sku) {
                continue;
            }
            var sLower = sku.toLowerCase();
            if (sLower === qLower) {
                continue;
            }
            var prefix = commonPrefixLength(qLower, sLower);
            var contains = sLower.indexOf(qLower) !== -1 || qLower.indexOf(sLower) !== -1;
            if (prefix < minPrefix && !contains) {
                continue;
            }
            scored.push({
                sku: sku,
                prefix: prefix,
                contains: contains ? 1 : 0,
                lenDiff: Math.abs(sku.length - q.length)
            });
        }

        scored.sort(function (a, b) {
            if (b.prefix !== a.prefix) {
                return b.prefix - a.prefix;
            }
            if (b.contains !== a.contains) {
                return b.contains - a.contains;
            }
            if (a.lenDiff !== b.lenDiff) {
                return a.lenDiff - b.lenDiff;
            }
            return a.sku.localeCompare(b.sku, undefined, { sensitivity: 'base' });
        });

        return scored.slice(0, limit).map(function (row) {
            return metaForSku(row.sku);
        });
    }

    function getAllowedColorColumns(row, allColumns, styleColorsMap) {
        var styleKey = String((row && row.STYLE) || '').trim();
        if (styleColorsMap && Object.prototype.hasOwnProperty.call(styleColorsMap, styleKey)) {
            var allowed = styleColorsMap[styleKey];
            return Array.isArray(allowed) ? allowed.slice() : [];
        }
        return getSkuColumns(allColumns || []);
    }

    function skusSummaryForRow(row, allColumns, styleColorsMap) {
        var allowed = getAllowedColorColumns(row, allColumns, styleColorsMap);
        var total = allowed.length;
        var found = 0;
        var off = 0;
        allowed.forEach(function (column) {
            var value = row && row[column] != null ? row[column] : '';
            if (isSkuOffValue(value)) {
                off += 1;
            }
            if (skuCountsAsComplete(value)) {
                found += 1;
            }
        });
        return {
            found: found,
            total: total,
            off: off,
            complete: total > 0 && found === total
        };
    }

    function skusCellHtml(row, allColumns, styleColorsMap) {
        var summary = skusSummaryForRow(row, allColumns, styleColorsMap);
        if (summary.total === 0) {
            return '<span class="text-slate-300">—</span>';
        }
        var complete = summary.complete;
        var hasOff = summary.off > 0;
        // A real gap outranks everything: grey first, then red for a colour set to OFF, else green.
        var statusClass = !complete
            ? 'fc-sp-sku-status--missing'
            : hasOff
              ? 'fc-sp-sku-status--off'
              : 'fc-sp-sku-status--found';
        var wrapClass = complete
            ? 'fc-sp-skus-summary--complete'
            : 'fc-sp-skus-summary--incomplete';
        var label = hasOff
            ? complete
                ? 'All style SKUs accounted for — includes a colour set to OFF'
                : 'Some style SKUs missing — includes a colour set to OFF'
            : complete
              ? 'All style SKUs found in store catalogue'
              : 'Some style SKUs missing from store catalogue';
        return (
            '<span class="fc-sp-skus-summary ' +
            wrapClass +
            '" title="' +
            escapeHtml(label) +
            '">' +
            '<span class="fc-sp-sku-status ' +
            statusClass +
            '" aria-hidden="true"></span>' +
            '<span>' +
            escapeHtml(String(summary.found) + '/' + String(summary.total)) +
            '</span></span>'
        );
    }

    function colorsCellHtml(row, allColumns, styleColorsMap) {
        var allowed = getAllowedColorColumns(row, allColumns, styleColorsMap);
        var items = [];
        allowed.forEach(function (column) {
            var sku = normalizeSkuValue(row && row[column] != null ? row[column] : '');
            if (isSkuMissingValue(sku)) {
                return;
            }
            var label = String(column).toUpperCase().replace(/[-_]/g, ' ');
            items.push(
                '<span class="fc-sys-product-color">' +
                    '<span class="fc-sys-product-color__swatch" style="background:#cbd5e1;" title="' +
                    escapeHtml(label) +
                    '" aria-hidden="true"></span>' +
                    '<span class="fc-sys-product-color__label">' +
                    escapeHtml(label) +
                    '</span></span>'
            );
        });
        if (!items.length) {
            return '<span class="text-slate-300">—</span>';
        }
        return '<div class="fc-sys-product-colors">' + items.join('') + '</div>';
    }

    function setEditFormDirty(isDirty) {
        editFormDirty = !!isDirty;
        var statusEl = document.getElementById('fc-sp-edit-status');
        if (!statusEl) {
            return;
        }
        if (editFormDirty) {
            statusEl.innerHTML =
                '<span class="inline-flex items-center gap-1.5 text-amber-600">' +
                '<i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i>' +
                '<span>Unsaved changes</span></span>';
            return;
        }
        statusEl.textContent = 'Press Esc to cancel';
    }

    function setSubmitLoading(isLoading) {
        var submitBtn = document.getElementById('fc-sp-edit-submit');
        if (!submitBtn) {
            return;
        }
        submitBtn.disabled = !!isLoading;
        if (isLoading) {
            submitBtn.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Saving…</span>';
            return;
        }
        submitBtn.innerHTML =
            '<i class="fa-solid fa-check" aria-hidden="true"></i><span>Save Changes</span>';
    }

    function csvToast(kind, message, toastId) {
        var T = global.FcAdminToast;
        if (!T) {
            return;
        }
        if (kind === 'saving') {
            T.loading(message, toastId);
            return;
        }
        if (toastId) {
            T.dismiss(toastId);
        }
        if (kind === 'ok') {
            T.success(message);
        } else if (kind === 'error') {
            T.error(message);
        }
    }

    var escapeHtml = global.FC.util.escapeHtml;

    function decodeHtmlEntities(text) {
        var raw = String(text == null ? '' : text);
        if (raw.indexOf('&') === -1) {
            return raw;
        }
        var textarea = document.createElement('textarea');
        textarea.innerHTML = raw;
        return textarea.value;
    }

    function formatProductName(name) {
        return decodeHtmlEntities(String(name || '').trim());
    }

    var copyFieldButton = new global.FC.components.CopyFieldButton({
        dataAttr: 'data-fc-sp-copy-for',
        onCopied: function () {
            var T = global.FcAdminToast;
            if (T) {
                T.success('Copied to clipboard');
            }
        }
    });

    function buildFieldCopyButton(fieldId, label, options) {
        return copyFieldButton.markup(fieldId, label, options);
    }

    function copyFieldToClipboard(control, btn) {
        copyFieldButton.copy(control, btn);
    }

    var formatHeader = global.FC.util.formatHeader;

    function renderLoading() {
        return (
            '<div class="flex flex-col items-center justify-center gap-3 p-12 text-slate-500">' +
            '<i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-500" aria-hidden="true"></i>' +
            '<p class="text-sm">Loading system products…</p>' +
            '</div>'
        );
    }

    function renderError(message) {
        return (
            '<div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">' +
            '<p class="font-semibold">Could not load system products</p>' +
            '<p class="mt-1 text-sm">' +
            escapeHtml(message) +
            '</p>' +
            '</div>'
        );
    }

    function applyRowFilters(rows, options) {
        options = options || {};
        var query = options.query || '';
        var supplier = options.supplier || '';
        var style = options.style || '';
        var result = rows;

        if (supplier) {
            result = result.filter(function (row) {
                return String(row.SUPPLIER != null ? row.SUPPLIER : '').trim() === supplier;
            });
        }

        if (style) {
            result = result.filter(function (row) {
                return String(row.STYLE != null ? row.STYLE : '').trim() === style;
            });
        }

        if (query) {
            var q = query.toLowerCase();
            result = result.filter(function (row) {
                return Object.keys(row).some(function (key) {
                    return String(row[key] || '')
                        .toLowerCase()
                        .indexOf(q) !== -1;
                });
            });
        }

        return result;
    }

    function uniqueColumnValues(rows, column) {
        var seen = {};
        var values = [];
        var columnKey = String(column || '').trim().toUpperCase();
        rows.forEach(function (row) {
            var val = String(row[column] != null ? row[column] : '').trim();
            if (!val || seen[val] || val.toUpperCase() === columnKey) {
                return;
            }
            seen[val] = true;
            values.push(val);
        });
        return values.sort(function (a, b) {
            return a.localeCompare(b, undefined, { sensitivity: 'base' });
        });
    }

    function buildFilterSelectOptions(values, allLabel, selected) {
        var html =
            '<option value="">' +
            escapeHtml(allLabel) +
            '</option>' +
            values
                .map(function (val) {
                    return (
                        '<option value="' +
                        escapeHtml(val) +
                        '"' +
                        (val === selected ? ' selected' : '') +
                        '>' +
                        escapeHtml(val) +
                        '</option>'
                    );
                })
                .join('');
        return html;
    }

    function getRowIndex(row) {
        var idx = row._rowIndex;
        return typeof idx === 'number' ? idx : parseInt(idx, 10);
    }

    function moveItem(array, fromIndex, toIndex) {
        if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0) {
            return array;
        }
        var next = array.slice();
        var item = next.splice(fromIndex, 1)[0];
        next.splice(toIndex, 0, item);
        return next;
    }

    function buildTable(columns, rows, options) {
        options = options || {};
        var draggable = !!options.draggable;
        var allColumns = options.allColumns || columns;
        var styleColorsMap = options.styleColorsMap || {};
        var displayColumns = options.displayColumns || insertSkusColumn(columns);

        var COLUMN_WIDTH_CLASS = {
            SLUG: 'w-[14rem]',
            PRODUCT: 'w-[18rem]',
            SUPPLIER: 'w-[6rem]',
            STYLE: 'w-[7rem]'
        };

        var colgroup =
            (draggable ? '<col class="w-10" />' : '') +
            displayColumns
                .map(function (col) {
                    if (col === 'Colors') {
                        return '<col class="fc-sys-product-colors-col" />';
                    }
                    if (col === 'SKUs') {
                        return '<col class="fc-sys-product-skus-col" />';
                    }
                    if (col === 'DESCRIPTION') {
                        return '<col class="fc-sys-product-desc-col" />';
                    }
                    return '<col class="' + (COLUMN_WIDTH_CLASS[col] || 'w-[8rem]') + '" />';
                })
                .join('');

        var thead =
            '<thead class="fc-sp-table-head text-left">' +
            '<tr>' +
            (draggable
                ? '<th scope="col" class="fc-sp-sticky fc-sp-sticky-grip px-2 py-2 w-10" aria-label="Reorder"></th>'
                : '') +
            displayColumns
                .map(function (col, colIndex) {
                    var sticky =
                        colIndex === 0
                            ? ' fc-sp-sticky fc-sp-sticky-col relative'
                            : '';
                    var header =
                        col === 'Colors' || col === 'SKUs' ? col : formatHeader(col);
                    var sortable = col !== 'Colors';
                    var extra =
                        (col === 'DESCRIPTION' ? ' fc-sys-product-desc-cell' : '') +
                        (col === 'Colors' ? ' fc-sys-product-colors-cell' : '') +
                        (col === 'SKUs' ? ' fc-sys-product-skus-cell' : '') +
                        (sortable ? ' cursor-pointer select-none hover:bg-slate-100' : '');
                    return (
                        '<th scope="col" class="whitespace-nowrap px-3 py-2' +
                        sticky +
                        extra +
                        '"' +
                        (sortable
                            ? ' data-sort-col="' + escapeHtml(col) + '" role="button" tabindex="0" aria-sort="none"'
                            : '') +
                        '>' +
                        (sortable
                            ? '<div class="flex items-center justify-between gap-1"><span>' + escapeHtml(header) + '</span>' +
                              '<i class="fa-solid fa-sort fc-sp-sort-icon text-slate-300" aria-hidden="true"></i></div>'
                            : '<span>' + escapeHtml(header) + '</span>') +
                        '</th>'
                    );
                })
                .join('') +
            '</tr></thead>';

        var tbody =
            '<tbody id="fc-store-products-tbody" class="divide-y divide-slate-100 text-sm text-slate-700">' +
            rows
                .map(function (row, rowIdx) {
                    var dataRowIndex = getRowIndex(row);
                    var stripeBg = rowIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
                    return (
                        '<tr' +
                        ' data-row-index="' +
                        String(dataRowIndex) +
                        '"' +
                        ' class="fc-store-products-row fc-store-products-row--clickable ' +
                        stripeBg +
                        '/50"' +
                        '>' +
                        (draggable
                            ? '<td class="fc-sp-sticky fc-sp-sticky-grip cursor-grab border-b border-slate-100 px-2 py-2 text-center text-slate-400 active:cursor-grabbing ' +
                              stripeBg +
                              '" title="Drag to reorder">' +
                              '<i class="fa-solid fa-grip-vertical pointer-events-none" aria-hidden="true"></i>' +
                              '</td>'
                            : '') +
                        displayColumns
                            .map(function (col, colIndex) {
                                var sticky =
                                    colIndex === 0
                                        ? ' fc-sp-sticky fc-sp-sticky-col relative ' + stripeBg
                                        : '';
                                if (col === 'SKUs') {
                                    return (
                                        '<td class="border-b border-slate-100 px-3 py-2 fc-sys-product-skus-cell' +
                                        sticky +
                                        '">' +
                                        skusCellHtml(row, allColumns, styleColorsMap) +
                                        '</td>'
                                    );
                                }
                                if (col === 'Colors') {
                                    return (
                                        '<td class="border-b border-slate-100 px-3 py-2 fc-sys-product-colors-cell' +
                                        sticky +
                                        '">' +
                                        colorsCellHtml(row, allColumns, styleColorsMap) +
                                        '</td>'
                                    );
                                }
                                var val = row[col] != null ? row[col] : '';
                                var empty = val === '';
                                return (
                                    '<td class="border-b border-slate-100 px-3 py-2' +
                                    (col === 'DESCRIPTION' ? ' fc-sys-product-desc-cell' : '') +
                                    sticky +
                                    (empty ? ' text-slate-300' : '') +
                                    '">' +
                                    (empty ? '—' : escapeHtml(val)) +
                                    '</td>'
                                );
                            })
                            .join('') +
                        '</tr>'
                    );
                })
                .join('') +
            '</tbody>';

        return (
            '<div class="fc-sp-table-layout flex min-h-0 flex-1 flex-col">' +
            '<div class="fc-store-products-scroll fc-sp-table-body fc-sp-hide-x-scrollbar min-h-0 flex-1 overflow-x-hidden overflow-y-auto' +
            (draggable ? ' fc-sp-has-grip' : '') +
            '">' +
            '<table class="fc-store-products-table fc-sp-table-fixed border-collapse text-left">' +
            '<colgroup>' +
            colgroup +
            '</colgroup>' +
            thead +
            tbody +
            '</table></div>' +
            '<div class="fc-sp-bottom-scrollbar" aria-label="Scroll table horizontally">' +
            '<div class="fc-sp-bottom-scrollbar-spacer h-px"></div>' +
            '</div></div>'
        );
    }

    function renderPage(data) {
        var columns = data.columns || [];
        var styleColorsMap = data.styleColors || {};
        var allRows = (data.rows || []).slice();
        var phpMode = !!data.phpMode;
        var csrf = String(data.csrf || '');
        var onPersistReload =
            typeof data.onPersistReload === 'function' ? data.onPersistReload : null;
        var urlFilters = readFiltersFromUrl();
        var searchQuery = phpMode
            ? String((data.filters && data.filters.q) || urlFilters.q || '')
            : urlFilters.q;
        var supplierFilter = phpMode
            ? String((data.filters && data.filters.supplier) || urlFilters.supplier || '')
            : urlFilters.supplier;
        var styleFilter = phpMode
            ? String((data.filters && data.filters.style) || urlFilters.style || '')
            : urlFilters.style;
        var saveTimer = null;
        var filterUrlSyncTimer = null;
        var isSaving = false;
        var bottomScrollSync = null;
        // Column-header sort — server-driven (see navigateWithSort); this just mirrors
        // the sort/dir URL params PHP already used to decide how allRows is ordered.
        var sortState = readSortFromUrl();

        var shell =
            '<div class="flex h-full min-h-0 flex-col">' +
            '<div class="fc-entries-page__toolbar fc-sp-toolbar fc-admin-sticky-header sticky top-0 z-20">' +
            '<div class="fc-entries-page__toolbar-row">' +
            '<label class="fc-entries-page__search-wrap">' +
            '<i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>' +
            '<input type="search" id="fc-store-products-search" class="fc-entries-page__search" placeholder="Search products…" aria-label="Search products" autocomplete="off">' +
            '</label>' +
            '<select id="fc-store-products-filter-supplier" aria-label="Supplier" class="fc-entries-page__filter">' +
            buildFilterSelectOptions(uniqueColumnValues(allRows, 'SUPPLIER'), 'All suppliers', '') +
            '</select>' +
            '<select id="fc-store-products-filter-style" aria-label="Style" class="fc-entries-page__filter">' +
            buildFilterSelectOptions(uniqueColumnValues(allRows, 'STYLE'), 'All styles', '') +
            '</select>' +
            '<button type="button" id="fc-store-products-clear-filters" class="btn btn-sm btn-light fw-semibold fc-entries-clear-filters" disabled><span>Clear Filters</span></button>' +
            '</div>' +
            '<div class="fc-entries-page__count">' +
            '<span id="fc-store-products-count">' +
            allRows.length +
            '</span> Items' +
            '</div>' +
            '</div>' +
            '<div id="fc-store-products-table-wrap" class="flex min-h-0 flex-1 flex-col overflow-hidden"></div>' +
            '</div>';

        function getVisibleRows() {
            // allRows is already in the order the server returned (sorted server-side
            // when a sort is active) — filtering here preserves that relative order.
            return applyRowFilters(allRows, {
                query: searchQuery,
                supplier: supplierFilter,
                style: styleFilter
            });
        }

        function hasActiveFilters() {
            return searchQuery.length > 0 || supplierFilter.length > 0 || styleFilter.length > 0;
        }

        function isSortActive() {
            return sortState.column !== '';
        }

        function scheduleFilterUrlSync(immediate) {
            if (filterUrlSyncTimer) {
                window.clearTimeout(filterUrlSyncTimer);
                filterUrlSyncTimer = null;
            }
            if (immediate) {
                syncFiltersToUrl(supplierFilter, styleFilter, searchQuery);
                return;
            }
            filterUrlSyncTimer = window.setTimeout(function () {
                filterUrlSyncTimer = null;
                syncFiltersToUrl(supplierFilter, styleFilter, searchQuery);
            }, 300);
        }

        function refreshFilterControls() {
            var supplierSelect = document.getElementById('fc-store-products-filter-supplier');
            var styleSelect = document.getElementById('fc-store-products-filter-style');
            var clearBtn = document.getElementById('fc-store-products-clear-filters');

            if (supplierSelect) {
                supplierSelect.innerHTML = buildFilterSelectOptions(
                    uniqueColumnValues(allRows, 'SUPPLIER'),
                    'All suppliers',
                    supplierFilter
                );
            }
            if (styleSelect) {
                styleSelect.innerHTML = buildFilterSelectOptions(
                    uniqueColumnValues(allRows, 'STYLE'),
                    'All styles',
                    styleFilter
                );
            }
            if (clearBtn) {
                var filtersOn = hasActiveFilters();
                clearBtn.disabled = !filtersOn;
                clearBtn.classList.toggle('btn-dark', filtersOn);
                clearBtn.classList.toggle('btn-light', !filtersOn);
            }
        }

        function resetFilters() {
            searchQuery = '';
            supplierFilter = '';
            styleFilter = '';
            var input = document.getElementById('fc-store-products-search');
            if (input) {
                input.value = '';
            }
            refreshFilterControls();
            scheduleFilterUrlSync(true);
        }

        function refreshTable(options) {
            options = options || {};
            refreshFilterControls();
            paintTable(getVisibleRows());
            if (options.syncUrl !== false) {
                scheduleFilterUrlSync(options.immediateUrlSync);
            }
        }

        function orderFromRows(rows) {
            return rows.map(getRowIndex);
        }

        function saveOrder(rows) {
            if (isSaving) {
                return Promise.resolve();
            }
            isSaving = true;
            csvToast('saving', 'Saving row order to products.csv…', TOAST_CSV_REORDER);

            return fetch(API_REORDER, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ order: orderFromRows(rows), csrf: csrf })
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        if (!res.ok || !body.ok) {
                            throw new Error((body && body.error) || 'Save failed');
                        }
                        return body;
                    });
                })
                .then(function () {
                    csvToast('ok', 'products.csv updated — row order saved.', TOAST_CSV_REORDER);
                    if (phpMode && onPersistReload) {
                        reloadWithNotice('products.csv updated — row order saved.', 'success');
                        return null;
                    }
                    return fetch(API_LOAD, {
                        method: 'GET',
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin'
                    }).then(function (res) {
                        return res.json();
                    });
                })
                .then(function (body) {
                    if (!body) {
                        return;
                    }
                    if (body && body.ok && body.rows) {
                        allRows = body.rows.slice();
                        refreshTable();
                    }
                })
                .catch(function (err) {
                    var failMsg =
                        (err.message || 'Could not save row order.') + ' Table reloaded.';
                    csvToast('error', failMsg, TOAST_CSV_REORDER);
                    if (phpMode && onPersistReload) {
                        reloadWithNotice(failMsg, 'error');
                        return null;
                    }
                    return fetch(API_LOAD, {
                        method: 'GET',
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin'
                    })
                        .then(function (res) {
                            return res.json();
                        })
                        .then(function (body) {
                            if (body.ok && body.rows) {
                                allRows = body.rows.slice();
                                resetFilters();
                                refreshTable();
                            }
                        });
                })
                .finally(function () {
                    isSaving = false;
                });
        }

        function findRowByIndex(rowIndex) {
            for (var i = 0; i < allRows.length; i++) {
                if (getRowIndex(allRows[i]) === rowIndex) {
                    return allRows[i];
                }
            }
            return null;
        }

        function ensureEditModal() {
            if (editModalEl) {
                return;
            }

            var btn = global.FcAdminBtn || {};
            var btnSecondary = btn.secondary || 'btn btn-sm btn-dark fw-semibold';
            var btnPrimary = btn.primary || 'btn btn-sm btn-orange fw-semibold';

            var modalHtml =
                '<div id="fc-sp-edit-modal" class="fixed inset-0 z-[100] items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="fc-sp-edit-modal-title" aria-hidden="true">' +
                '<div class="fc-sp-edit-backdrop absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]" aria-hidden="true"></div>' +
                '<div class="fc-sp-edit-panel relative flex max-h-[calc(100vh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xl">' +
                '<button type="button" class="fencing-modal-close" data-fc-sp-modal-close aria-label="Close"></button>' +
                '<div class="shrink-0 border-b border-slate-200 px-6 py-4 pr-12">' +
                '<div class="min-w-0">' +
                '<h2 id="fc-sp-edit-modal-title" class="text-base font-semibold leading-tight text-slate-900">Edit product</h2>' +
                '</div></div>' +
                '<form id="fc-sp-edit-form" class="flex min-h-0 flex-1 flex-col">' +
                '<div class="fc-sp-edit-panels min-h-0 flex-1 overflow-y-auto bg-white">' +
                '<div id="fc-sp-edit-panel-details" class="fc-sp-field-grid px-6 py-4"></div>' +
                '<div id="fc-sp-edit-panel-sku" class="px-6 pb-4">' +
                '<div class="fc-sp-edit-section-title">SKU</div>' +
                '<p class="fc-sp-field__intro">Color variant SKUs used by the planner. Leave blank when a finish is not available for this product.</p>' +
                '<div class="fc-sp-field-grid fc-sp-field-grid--sku"></div></div>' +
                '</div>' +
                '<div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">' +
                '<p id="fc-sp-edit-status" class="text-xs text-slate-400">Press Esc to cancel</p>' +
                '<div class="flex flex-wrap justify-end gap-2">' +
                '<button type="button" class="' +
                btnSecondary +
                '" data-fc-sp-modal-close>' +
                'Cancel</button>' +
                '<button type="submit" id="fc-sp-edit-submit" class="' +
                btnPrimary +
                '">' +
                '<i class="fa-solid fa-check" aria-hidden="true"></i><span>Save Changes</span></button>' +
                '</div></div>' +
                '</form>' +
                '</div>' +
                '</div>';

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            editModalEl = document.getElementById('fc-sp-edit-modal');
            editFormEl = document.getElementById('fc-sp-edit-form');

            editModalEl.querySelectorAll('[data-fc-sp-modal-close]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeEditModal(false);
                });
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') {
                    return;
                }
                if (closeSkuSuggestPreview()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                if (closeSkuSuggest()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                if (
                    editModalEl &&
                    editModalEl.classList.contains('fc-sp-edit-modal--visible')
                ) {
                    closeEditModal(false);
                }
            });

            editFormEl.addEventListener('submit', function (e) {
                e.preventDefault();
                saveEditProduct();
            });

            editFormEl.addEventListener('input', function (e) {
                setEditFormDirty(true);
                syncFieldFilledState(e.target);
                if (e.target && e.target.classList && e.target.classList.contains('fc-sp-field-control--sku')) {
                    scheduleSkuStatusRefresh(e.target);
                    if (activeSkuSuggestWrap && activeSkuSuggestWrap.contains(e.target)) {
                        refreshOpenSkuSuggest(e.target);
                    }
                }
            });

            editFormEl.addEventListener('click', function (e) {
                var checkBtn = e.target.closest('[data-fc-sp-sku-check]');
                if (checkBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    var wrap = checkBtn.closest('.fc-sp-field-input-wrap--sku');
                    if (!wrap) {
                        return;
                    }
                    if (activeSkuSuggestWrap === wrap) {
                        closeSkuSuggest();
                        return;
                    }
                    openSkuSuggest(wrap);
                    return;
                }

                var copyBtn = e.target.closest('[data-fc-sp-copy-for]');
                if (!copyBtn) {
                    return;
                }
                e.preventDefault();
                var fieldId = copyBtn.getAttribute('data-fc-sp-copy-for');
                if (!fieldId) {
                    return;
                }
                var control = document.getElementById(fieldId);
                copyFieldToClipboard(control, copyBtn);
            });

            editModalEl.addEventListener('click', function (e) {
                var thumbViewEl = e.target.closest('[data-fc-sp-sku-thumb-view]');
                if (thumbViewEl) {
                    e.preventDefault();
                    e.stopPropagation();
                    var thumbGallery = collectSkuGallerySlides(thumbViewEl);
                    openSkuImageGallery(thumbGallery.slides, thumbGallery.startIndex);
                    return;
                }

                var previewOpenBtn = e.target.closest('[data-fc-sp-sku-preview-open]');
                if (previewOpenBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    openSkuSuggestPreview(
                        previewOpenBtn.getAttribute('data-fc-sp-sku-preview-open') || ''
                    );
                    return;
                }

                var previewCloseBtn = e.target.closest('[data-fc-sp-sku-preview-close]');
                if (previewCloseBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSkuSuggestPreview();
                    return;
                }

                var useBtn = e.target.closest('[data-fc-sp-sku-use]');
                if (!useBtn) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                applySuggestedSku(useBtn.getAttribute('data-fc-sp-sku-use') || '', useBtn);
            });

            editModalEl.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') {
                    return;
                }
                var thumbViewEl = e.target.closest && e.target.closest('[data-fc-sp-sku-thumb-view]');
                if (!thumbViewEl) {
                    return;
                }
                e.preventDefault();
                var thumbGallery = collectSkuGallerySlides(thumbViewEl);
                openSkuImageGallery(thumbGallery.slides, thumbGallery.startIndex);
            });

            document.addEventListener('click', function (e) {
                if (!activeSkuSuggestWrap) {
                    return;
                }
                if (activeSkuSuggestWrap.contains(e.target)) {
                    return;
                }
                if (activeSkuSuggestMenu && activeSkuSuggestMenu.contains(e.target)) {
                    return;
                }
                closeSkuSuggest();
            });
        }

        function syncFieldFilledState(el) {
            if (!el || !el.classList || !el.classList.contains('fc-sp-field-control')) {
                return;
            }
            var hasValue = String(el.value || '').trim() !== '';
            if (el.classList.contains('fc-sp-field-control--sku')) {
                el.classList.toggle('fc-sp-field-control--empty', !hasValue);
            }
        }

        /** Thumbnail slot shown left of a SKU field. Always rendered, so the grid columns stay aligned. */
        function skuThumbHtml(value) {
            var image = String(metaForSku(value).image || '').trim();
            if (!image) {
                return '<span class="fc-sp-sku-thumb fc-sp-sku-thumb--empty" data-fc-sp-sku-thumb aria-hidden="true"></span>';
            }
            return (
                '<img class="fc-sp-sku-thumb fc-sp-sku-thumb--viewable" data-fc-sp-sku-thumb data-fc-sp-sku-thumb-view="' +
                escapeHtml(image) +
                '" src="' +
                escapeHtml(image) +
                '" alt="" loading="lazy" decoding="async" tabindex="0" role="button" aria-label="View larger image">'
            );
        }

        /**
         * Bigger-image gallery for a SKU thumbnail, styled like the system products image gallery.
         * Collects every color variant with a resolved image from the currently open edit modal so
         * the other SKUs' images can be browsed from the same popup, each captioned with its color + SKU.
         */
        function collectSkuGallerySlides(fromEl) {
            var result = { slides: [], startIndex: 0 };
            var grid = editModalEl && editModalEl.querySelector('.fc-sp-field-grid--sku');
            if (!grid) {
                return result;
            }

            grid.querySelectorAll('.fc-sp-field--sku').forEach(function (field) {
                var input = field.querySelector('.fc-sp-field-control--sku');
                if (!input) {
                    return;
                }
                var image = String(metaForSku(input.value).image || '').trim();
                if (!image) {
                    return;
                }
                if (fromEl && field.contains(fromEl)) {
                    result.startIndex = result.slides.length;
                }
                result.slides.push({
                    url: image,
                    color: formatHeader(input.name || ''),
                    sku: normalizeSkuValue(input.value) || String(input.value || '').trim()
                });
            });

            return result;
        }

        function scrollSkuGalleryThumbIntoView() {
            if (!skuGalleryEl) {
                return;
            }
            var thumbsEl = skuGalleryEl.querySelector('[data-fc-sku-gallery-thumbs]');
            if (!thumbsEl || thumbsEl.hidden) {
                return;
            }
            var activeThumb = thumbsEl.querySelector('[data-fc-sku-gallery-thumb].is-active');
            if (!activeThumb) {
                return;
            }
            activeThumb.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        }

        function renderSkuGalleryThumbs() {
            if (!skuGalleryEl) {
                return;
            }
            var thumbsEl = skuGalleryEl.querySelector('[data-fc-sku-gallery-thumbs]');
            if (!thumbsEl) {
                return;
            }
            if (skuGalleryState.slides.length <= 1) {
                thumbsEl.hidden = true;
                thumbsEl.innerHTML = '';
                return;
            }
            thumbsEl.hidden = false;
            thumbsEl.innerHTML = skuGalleryState.slides
                .map(function (slide, index) {
                    return (
                        '<button type="button" class="fc-entries-cart-gallery__thumb' +
                        (index === skuGalleryState.index ? ' is-active' : '') +
                        '" data-fc-sku-gallery-thumb="' +
                        index +
                        '" aria-label="View ' +
                        escapeHtml(slide.color || 'image ' + (index + 1)) +
                        '"><img src="' +
                        escapeHtml(slide.url) +
                        '" alt="" loading="lazy" decoding="async"></button>'
                    );
                })
                .join('');
            requestAnimationFrame(scrollSkuGalleryThumbIntoView);
        }

        function renderSkuGallerySlide() {
            if (!skuGalleryEl || !skuGalleryState.slides.length) {
                return;
            }

            var slide = skuGalleryState.slides[skuGalleryState.index];
            var imageEl = skuGalleryEl.querySelector('[data-fc-sku-gallery-image]');
            var colorEl = skuGalleryEl.querySelector('[data-fc-sku-gallery-color]');
            var skuEl = skuGalleryEl.querySelector('[data-fc-sku-gallery-sku]');
            var counterEl = skuGalleryEl.querySelector('[data-fc-sku-gallery-counter]');
            var prevBtn = skuGalleryEl.querySelector('[data-fc-sku-gallery-prev]');
            var nextBtn = skuGalleryEl.querySelector('[data-fc-sku-gallery-next]');

            if (imageEl) {
                imageEl.src = slide.url;
                imageEl.alt = slide.color || 'Product image';
            }
            if (colorEl) {
                colorEl.textContent = slide.color ? 'Color: ' + slide.color : '';
                colorEl.hidden = !slide.color;
            }
            if (skuEl) {
                skuEl.textContent = slide.sku ? 'SKU: ' + slide.sku : '';
                skuEl.hidden = !slide.sku;
            }
            if (counterEl) {
                counterEl.textContent = skuGalleryState.index + 1 + ' / ' + skuGalleryState.slides.length;
                counterEl.hidden = skuGalleryState.slides.length <= 1;
            }
            if (prevBtn) {
                prevBtn.disabled = skuGalleryState.slides.length <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = skuGalleryState.slides.length <= 1;
            }

            skuGalleryEl.querySelectorAll('[data-fc-sku-gallery-thumb]').forEach(function (btn) {
                var thumbIndex = parseInt(btn.getAttribute('data-fc-sku-gallery-thumb') || '-1', 10);
                btn.classList.toggle('is-active', thumbIndex === skuGalleryState.index);
            });

            scrollSkuGalleryThumbIntoView();
        }

        function showSkuGallerySlide(index) {
            if (!skuGalleryState.slides.length) {
                return;
            }
            if (index < 0) {
                index = skuGalleryState.slides.length - 1;
            } else if (index >= skuGalleryState.slides.length) {
                index = 0;
            }
            skuGalleryState.index = index;
            renderSkuGallerySlide();
        }

        function closeSkuImageGallery() {
            if (!skuGalleryEl) {
                return;
            }
            if (skuGalleryKeydownHandler) {
                document.removeEventListener('keydown', skuGalleryKeydownHandler);
                skuGalleryKeydownHandler = null;
            }
            skuGalleryEl.remove();
            skuGalleryEl = null;
            skuGalleryState.slides = [];
            skuGalleryState.index = 0;
            document.body.classList.remove(GALLERY_BODY_CLASS);
        }

        function openSkuImageGallery(slides, startIndex) {
            if (!slides || !slides.length) {
                return;
            }
            closeSkuImageGallery();

            skuGalleryState.slides = slides;
            skuGalleryState.index = Math.max(0, Math.min(startIndex || 0, slides.length - 1));

            skuGalleryEl = document.createElement('div');
            skuGalleryEl.className = 'fc-entries-cart-gallery';
            skuGalleryEl.setAttribute('role', 'dialog');
            skuGalleryEl.setAttribute('aria-modal', 'true');
            skuGalleryEl.setAttribute('aria-label', 'Product images');
            skuGalleryEl.innerHTML =
                '<div class="fc-entries-cart-gallery__backdrop" data-fc-sku-gallery-close aria-hidden="true"></div>' +
                '<button type="button" class="fencing-modal-close" data-fc-sku-gallery-close aria-label="Close"></button>' +
                '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--prev" data-fc-sku-gallery-prev aria-label="Previous image">' +
                '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>' +
                '<div class="fc-entries-cart-gallery__stage">' +
                '<img class="fc-entries-cart-gallery__image" data-fc-sku-gallery-image src="" alt="">' +
                '<p class="fc-entries-cart-gallery__caption" data-fc-sku-gallery-caption>' +
                '<span class="fc-sp-gallery-caption__color" data-fc-sku-gallery-color></span>' +
                '<span class="fc-sp-gallery-caption__sku" data-fc-sku-gallery-sku></span>' +
                '</p>' +
                '<span class="fc-entries-cart-gallery__counter" data-fc-sku-gallery-counter hidden></span>' +
                '</div>' +
                '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--next" data-fc-sku-gallery-next aria-label="Next image">' +
                '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>' +
                '<div class="fc-entries-cart-gallery__thumbs" data-fc-sku-gallery-thumbs hidden></div>';

            document.body.appendChild(skuGalleryEl);
            document.body.classList.add(GALLERY_BODY_CLASS);

            skuGalleryEl.querySelectorAll('[data-fc-sku-gallery-close]').forEach(function (btn) {
                btn.addEventListener('click', closeSkuImageGallery);
            });

            var prevBtn = skuGalleryEl.querySelector('[data-fc-sku-gallery-prev]');
            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    showSkuGallerySlide(skuGalleryState.index - 1);
                });
            }

            var nextBtn = skuGalleryEl.querySelector('[data-fc-sku-gallery-next]');
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    showSkuGallerySlide(skuGalleryState.index + 1);
                });
            }

            skuGalleryEl.addEventListener('click', function (e) {
                var thumbBtn = e.target.closest('[data-fc-sku-gallery-thumb]');
                if (!thumbBtn) {
                    return;
                }
                e.preventDefault();
                showSkuGallerySlide(parseInt(thumbBtn.getAttribute('data-fc-sku-gallery-thumb') || '0', 10));
            });

            skuGalleryKeydownHandler = function (e) {
                if (!skuGalleryEl) {
                    return;
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeSkuImageGallery();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    showSkuGallerySlide(skuGalleryState.index - 1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    showSkuGallerySlide(skuGalleryState.index + 1);
                }
            };
            document.addEventListener('keydown', skuGalleryKeydownHandler);

            renderSkuGalleryThumbs();
            renderSkuGallerySlide();
        }

        /**
         * Swap a SKU field's thumbnail in place (img <-> placeholder). Fields are built before the WC
         * SKU index resolves, so every slot starts as a placeholder and is filled in from here.
         */
        function paintSkuThumb(field, value) {
            if (!field) {
                return;
            }
            var slot = field.querySelector('[data-fc-sp-sku-thumb]');
            if (!slot) {
                return;
            }
            var image = String(metaForSku(value).image || '').trim();
            var current = slot.tagName === 'IMG' ? slot.getAttribute('src') || '' : '';
            if (current === image) {
                return;
            }
            slot.outerHTML = skuThumbHtml(value);
        }

        function paintSkuStatus(checkBtn, value) {
            if (!checkBtn) {
                return;
            }
            // OFF is deliberate, so it counts as complete, but stays visually distinct (red dot).
            var off = isSkuOffValue(value);
            var empty = !off && normalizeSkuValue(value) === '';
            var found = !off && !empty && skuExistsInCatalogue(value);
            var missing = !off && !empty && !found;
            checkBtn.classList.toggle('fc-sp-sku-check--off', off);
            checkBtn.classList.toggle('fc-sp-sku-check--empty', empty);
            checkBtn.classList.toggle('fc-sp-sku-check--found', found);
            checkBtn.classList.toggle('fc-sp-sku-check--missing', missing);

            var icon = checkBtn.querySelector('i');
            if (icon) {
                if (off) {
                    icon.className = 'fa-solid fa-circle';
                } else if (found) {
                    icon.className = 'fa-solid fa-check';
                } else if (empty) {
                    icon.className = 'fa-solid fa-exclamation';
                } else {
                    icon.className = 'fa-solid fa-xmark';
                }
            }

            if (off) {
                checkBtn.setAttribute(
                    'aria-label',
                    'SKU is set to OFF and counts as complete. Open similar SKUs.'
                );
                checkBtn.title = 'Set to OFF — counted as complete';
            } else if (found) {
                checkBtn.setAttribute(
                    'aria-label',
                    'SKU found in store catalogue. Open similar SKUs.'
                );
                checkBtn.title = 'Found in store catalogue — click for similar SKUs';
            } else if (empty) {
                checkBtn.setAttribute(
                    'aria-label',
                    'SKU is empty. Open similar catalogue SKUs.'
                );
                checkBtn.title = 'No SKU — click for similar catalogue SKUs';
            } else {
                checkBtn.setAttribute(
                    'aria-label',
                    'SKU not in store catalogue. Open similar SKUs.'
                );
                checkBtn.title = 'Not in store catalogue — click for similar SKUs';
            }
        }

        function refreshSkuStatusForInput(input) {
            if (!input) {
                return;
            }
            var wrap = input.closest('.fc-sp-field-input-wrap--sku');
            var checkBtn = wrap ? wrap.querySelector('[data-fc-sp-sku-check]') : null;
            paintSkuStatus(checkBtn, input.value);
            paintSkuThumb(input.closest('.fc-sp-field--sku'), input.value);
        }

        function refreshAllSkuStatuses() {
            if (!editFormEl) {
                return;
            }
            editFormEl.querySelectorAll('.fc-sp-field-control--sku').forEach(function (input) {
                refreshSkuStatusForInput(input);
            });
        }

        function scheduleSkuStatusRefresh(input) {
            if (skuStatusTimer) {
                window.clearTimeout(skuStatusTimer);
            }
            skuStatusTimer = window.setTimeout(function () {
                skuStatusTimer = null;
                refreshSkuStatusForInput(input);
            }, 150);
        }

        function clearSkuSuggestInlinePosition(menu) {
            if (!menu || !menu.style) {
                return;
            }
            menu.style.position = '';
            menu.style.left = '';
            menu.style.top = '';
            menu.style.width = '';
            menu.style.right = '';
            menu.style.zIndex = '';
            menu.style.maxHeight = '';
            menu.style.height = '';
        }

        function positionSkuSuggestMenu(wrap, menu) {
            if (!wrap || !menu) {
                return;
            }
            var rect = wrap.getBoundingClientRect();
            var gap = 4;
            var maxH = Math.min(520, Math.max(280, window.innerHeight - 24));
            var spaceBelow = window.innerHeight - rect.bottom - 8;
            var spaceAbove = rect.top - 8;
            // Prefer opening above the field; only drop below if there is not enough room.
            var openUp = spaceAbove >= 180 || spaceAbove >= spaceBelow;
            var height = Math.min(maxH, openUp ? spaceAbove - gap : spaceBelow - gap);
            if (height < 200) {
                height = Math.min(maxH, Math.max(spaceBelow, spaceAbove) - gap);
                openUp = spaceAbove >= spaceBelow;
            }
            height = Math.max(220, height);
            // Keep a locked height while open so filtering does not resize the panel.
            var lockedHeight = parseInt(menu.style.height, 10);
            if (lockedHeight > 0) {
                height = lockedHeight;
            }
            var width = Math.min(
                Math.max(rect.width, 420),
                Math.max(280, window.innerWidth - 16)
            );
            var left = Math.min(
                Math.max(8, rect.left),
                Math.max(8, window.innerWidth - width - 8)
            );
            var top = openUp
                ? Math.max(8, rect.top - gap - height)
                : Math.min(window.innerHeight - height - 8, rect.bottom + gap);
            menu.style.position = 'fixed';
            menu.style.left = Math.round(left) + 'px';
            menu.style.top = Math.round(top) + 'px';
            menu.style.width = Math.round(width) + 'px';
            menu.style.right = 'auto';
            menu.style.zIndex = '130';
            menu.style.height = Math.round(height) + 'px';
            menu.style.maxHeight = Math.round(height) + 'px';
        }

        function bindSkuSuggestReposition() {
            unbindSkuSuggestReposition();
            skuSuggestRepositionBound = function () {
                if (!activeSkuSuggestWrap || !activeSkuSuggestMenu) {
                    return;
                }
                positionSkuSuggestMenu(activeSkuSuggestWrap, activeSkuSuggestMenu);
            };
            window.addEventListener('resize', skuSuggestRepositionBound);
            if (editModalEl) {
                var panels = editModalEl.querySelector('.fc-sp-edit-panels');
                if (panels) {
                    panels.addEventListener('scroll', skuSuggestRepositionBound, { passive: true });
                }
            }
        }

        function unbindSkuSuggestReposition() {
            if (!skuSuggestRepositionBound) {
                return;
            }
            window.removeEventListener('resize', skuSuggestRepositionBound);
            if (editModalEl) {
                var panels = editModalEl.querySelector('.fc-sp-edit-panels');
                if (panels) {
                    panels.removeEventListener('scroll', skuSuggestRepositionBound);
                }
            }
            skuSuggestRepositionBound = null;
        }

        function mountSkuSuggestMenu(wrap, menu) {
            if (!wrap || !menu || !editModalEl) {
                return;
            }
            if (menu.parentElement !== editModalEl) {
                editModalEl.appendChild(menu);
            }
            menu.classList.add('fc-sp-sku-suggest--floating');
            positionSkuSuggestMenu(wrap, menu);
            bindSkuSuggestReposition();
        }

        function unmountSkuSuggestMenu(wrap, menu) {
            unbindSkuSuggestReposition();
            if (!menu) {
                return;
            }
            menu.classList.remove('fc-sp-sku-suggest--floating');
            clearSkuSuggestInlinePosition(menu);
            if (wrap && menu.parentElement !== wrap) {
                wrap.appendChild(menu);
            }
        }

        function closeSkuSuggest() {
            if (!activeSkuSuggestWrap) {
                return false;
            }
            var wrap = activeSkuSuggestWrap;
            var menu = activeSkuSuggestMenu || wrap.querySelector('[data-fc-sp-sku-suggest]');
            var checkBtn = wrap.querySelector('[data-fc-sp-sku-check]');
            if (menu) {
                menu.hidden = true;
                menu.classList.remove('is-preview-open');
                menu.innerHTML = '';
                unmountSkuSuggestMenu(wrap, menu);
            }
            if (checkBtn) {
                checkBtn.setAttribute('aria-expanded', 'false');
            }
            wrap.classList.remove('is-suggest-open');
            activeSkuSuggestWrap = null;
            activeSkuSuggestMenu = null;
            activeSkuSuggestMatches = null;
            return true;
        }

        function filterSkuSuggestMatches(matches, filterQuery) {
            var filter = String(filterQuery || '')
                .trim()
                .toLowerCase();
            if (!filter || !Array.isArray(matches) || !matches.length) {
                return Array.isArray(matches) ? matches.slice() : [];
            }
            return matches.filter(function (item) {
                var sku =
                    typeof item === 'string'
                        ? normalizeSkuValue(item)
                        : normalizeSkuValue(item && item.sku);
                var meta = typeof item === 'object' && item ? item : metaForSku(sku);
                var name = formatProductName(meta.name).toLowerCase();
                return (
                    sku.toLowerCase().indexOf(filter) !== -1 || name.indexOf(filter) !== -1
                );
            });
        }

        function renderSkuSuggestRows(matches) {
            return matches
                .map(function (item) {
                    var sku =
                        typeof item === 'string'
                            ? normalizeSkuValue(item)
                            : normalizeSkuValue(item && item.sku);
                    var meta = typeof item === 'object' && item ? item : metaForSku(sku);
                    var name = formatProductName(meta.name) || 'Untitled product';
                    var image = String(meta.image || '').trim();
                    var thumbInner = image
                        ? '<img class="fc-sp-sku-suggest__thumb" src="' +
                          escapeHtml(image) +
                          '" alt="" loading="lazy" decoding="async">'
                        : '<span class="fc-sp-sku-suggest__thumb fc-sp-sku-suggest__thumb--empty" aria-hidden="true"></span>';
                    return (
                        '<div class="fc-sp-sku-suggest__row" role="option">' +
                        '<button type="button" class="fc-sp-sku-suggest__thumb-btn" data-fc-sp-sku-preview-open="' +
                        escapeHtml(sku) +
                        '" aria-label="View larger image for ' +
                        escapeHtml(name) +
                        '" title="View larger image">' +
                        thumbInner +
                        '</button>' +
                        '<div class="fc-sp-sku-suggest__meta">' +
                        '<div class="fc-sp-sku-suggest__name">' +
                        escapeHtml(name) +
                        '</div>' +
                        '<code class="fc-sp-sku-suggest__sku">' +
                        escapeHtml(sku) +
                        '</code>' +
                        '</div>' +
                        '<button type="button" class="btn btn-sm btn-orange fw-semibold fc-sp-sku-suggest__use" data-fc-sp-sku-use="' +
                        escapeHtml(sku) +
                        '">Use</button>' +
                        '</div>'
                    );
                })
                .join('');
        }

        function renderSkuSuggestPreview(sku) {
            var meta = metaForSku(sku);
            var name = formatProductName(meta.name) || 'Untitled product';
            var image = String(meta.image || '').trim();
            var normalized = normalizeSkuValue(sku);
            var btn = global.FcAdminBtn || {};
            var btnSecondary = btn.secondary || 'btn btn-sm btn-dark fw-semibold';
            var btnPrimary = btn.primary || 'btn btn-sm btn-orange fw-semibold';
            var media = image
                ? '<img class="fc-sp-sku-suggest__preview-image" src="' +
                  escapeHtml(image) +
                  '" alt="' +
                  escapeHtml(name) +
                  '" loading="lazy" decoding="async">'
                : '<div class="fc-sp-sku-suggest__preview-image fc-sp-sku-suggest__preview-image--empty" aria-hidden="true"></div>';

            return (
                '<div class="fc-sp-sku-suggest__preview" data-fc-sp-sku-preview>' +
                '<div class="fc-sp-sku-suggest__preview-body">' +
                '<div class="fc-sp-sku-suggest__preview-media">' +
                media +
                '</div>' +
                '<div class="fc-sp-sku-suggest__preview-name">' +
                escapeHtml(name) +
                '</div>' +
                '<code class="fc-sp-sku-suggest__preview-sku">' +
                escapeHtml(normalized) +
                '</code>' +
                '</div>' +
                '<div class="fc-sp-sku-suggest__preview-footer">' +
                '<button type="button" class="' +
                btnSecondary +
                '" data-fc-sp-sku-preview-close>Close</button>' +
                '<button type="button" class="' +
                btnPrimary +
                '" data-fc-sp-sku-use="' +
                escapeHtml(normalized) +
                '">Use</button>' +
                '</div>' +
                '</div>'
            );
        }

        function openSkuSuggestPreview(sku) {
            if (!activeSkuSuggestMenu || !normalizeSkuValue(sku)) {
                return;
            }
            closeSkuSuggestPreview();
            var menu = activeSkuSuggestMenu;
            var list = menu.querySelector('[data-fc-sp-sku-suggest-list]');
            var toolbar = menu.querySelector('.fc-sp-sku-suggest__toolbar');
            menu.insertAdjacentHTML('beforeend', renderSkuSuggestPreview(sku));
            menu.classList.add('is-preview-open');
            if (list) {
                list.hidden = true;
            }
            if (toolbar) {
                toolbar.hidden = true;
            }
        }

        function closeSkuSuggestPreview() {
            if (!activeSkuSuggestMenu || !activeSkuSuggestMenu.classList.contains('is-preview-open')) {
                return false;
            }
            var menu = activeSkuSuggestMenu;
            var preview = menu.querySelector('[data-fc-sp-sku-preview]');
            var list = menu.querySelector('[data-fc-sp-sku-suggest-list]');
            var toolbar = menu.querySelector('.fc-sp-sku-suggest__toolbar');
            if (preview) {
                preview.remove();
            }
            menu.classList.remove('is-preview-open');
            if (list) {
                list.hidden = false;
            }
            if (toolbar) {
                toolbar.hidden = false;
            }
            return true;
        }

        function renderSkuSuggestListBody(matches, query, filterQuery) {
            var q = normalizeSkuValue(query);
            if (!q || q.toUpperCase() === 'OFF') {
                return (
                    '<div class="fc-sp-sku-suggest__empty">Enter a SKU to find similar catalogue matches.</div>'
                );
            }
            if (!Array.isArray(matches) || !matches.length) {
                return '<div class="fc-sp-sku-suggest__empty">No similar SKUs found.</div>';
            }
            var filtered = filterSkuSuggestMatches(matches, filterQuery);
            if (!filtered.length) {
                return '<div class="fc-sp-sku-suggest__empty">No products match this filter.</div>';
            }
            return renderSkuSuggestRows(filtered);
        }

        function renderSkuSuggestList(matches, query, filterQuery) {
            filterQuery = filterQuery == null ? '' : String(filterQuery);
            return (
                '<div class="fc-sp-sku-suggest__list" data-fc-sp-sku-suggest-list role="listbox" aria-label="Similar catalogue SKUs">' +
                renderSkuSuggestListBody(matches, query, filterQuery) +
                '</div>' +
                '<div class="fc-sp-sku-suggest__toolbar">' +
                '<label class="fc-sp-sku-suggest__filter-label" for="fc-sp-sku-suggest-filter">Filter</label>' +
                '<input type="search" id="fc-sp-sku-suggest-filter" class="fc-sp-sku-suggest__filter" data-fc-sp-sku-suggest-filter placeholder="Filter by name or SKU…" value="' +
                escapeHtml(filterQuery) +
                '" autocomplete="off" spellcheck="false">' +
                '</div>'
            );
        }

        function getSkuSuggestFilterValue(menu) {
            var filterInput = menu
                ? menu.querySelector('[data-fc-sp-sku-suggest-filter]')
                : null;
            return filterInput ? String(filterInput.value || '') : '';
        }

        function applySkuSuggestFilter() {
            if (!activeSkuSuggestWrap || !activeSkuSuggestMenu) {
                return;
            }
            closeSkuSuggestPreview();
            var input = activeSkuSuggestWrap.querySelector('.fc-sp-field-control--sku');
            var list = activeSkuSuggestMenu.querySelector('[data-fc-sp-sku-suggest-list]');
            if (!input || !list) {
                return;
            }
            list.innerHTML = renderSkuSuggestListBody(
                activeSkuSuggestMatches || [],
                input.value,
                getSkuSuggestFilterValue(activeSkuSuggestMenu)
            );
        }

        function bindSkuSuggestFilter(menu) {
            if (!menu) {
                return;
            }
            var filterInput = menu.querySelector('[data-fc-sp-sku-suggest-filter]');
            if (!filterInput || filterInput.getAttribute('data-fc-bound') === '1') {
                return;
            }
            filterInput.setAttribute('data-fc-bound', '1');
            filterInput.addEventListener('click', function (e) {
                e.stopPropagation();
            });
            filterInput.addEventListener('keydown', function (e) {
                e.stopPropagation();
                if (e.key === 'Escape') {
                    e.preventDefault();
                    if (closeSkuSuggestPreview()) {
                        return;
                    }
                    if (String(filterInput.value || '') !== '') {
                        filterInput.value = '';
                        applySkuSuggestFilter();
                        return;
                    }
                    closeSkuSuggest();
                }
            });
            filterInput.addEventListener('input', function (e) {
                e.stopPropagation();
                applySkuSuggestFilter();
            });
        }

        function openSkuSuggest(wrap) {
            if (!wrap) {
                return;
            }
            closeSkuSuggest();
            var input = wrap.querySelector('.fc-sp-field-control--sku');
            var menu = wrap.querySelector('[data-fc-sp-sku-suggest]');
            var checkBtn = wrap.querySelector('[data-fc-sp-sku-check]');
            if (!input || !menu || !checkBtn) {
                return;
            }

            function showMenu() {
                var matches = suggestSkusFor(input.value, SKU_SUGGEST_LIMIT);
                activeSkuSuggestMatches = matches;
                menu.innerHTML = renderSkuSuggestList(matches, input.value, '');
                menu.hidden = false;
                menu.style.height = '';
                menu.style.maxHeight = '';
                checkBtn.setAttribute('aria-expanded', 'true');
                wrap.classList.add('is-suggest-open');
                activeSkuSuggestWrap = wrap;
                activeSkuSuggestMenu = menu;
                mountSkuSuggestMenu(wrap, menu);
                bindSkuSuggestFilter(menu);
                var filterInput = menu.querySelector('[data-fc-sp-sku-suggest-filter]');
                if (filterInput) {
                    filterInput.focus();
                }
            }

            if (!Array.isArray(wcSkuList)) {
                menu.innerHTML =
                    '<div class="fc-sp-sku-suggest__empty">Loading catalogue…</div>';
                menu.hidden = false;
                checkBtn.setAttribute('aria-expanded', 'true');
                wrap.classList.add('is-suggest-open');
                activeSkuSuggestWrap = wrap;
                activeSkuSuggestMenu = menu;
                activeSkuSuggestMatches = [];
                mountSkuSuggestMenu(wrap, menu);
                ensureWcSkuIndex().then(function () {
                    if (activeSkuSuggestWrap === wrap) {
                        showMenu();
                    }
                });
                return;
            }

            showMenu();
        }

        function refreshOpenSkuSuggest(input) {
            if (!activeSkuSuggestWrap || !input || !activeSkuSuggestMenu) {
                return;
            }
            var menu = activeSkuSuggestMenu;
            if (menu.hidden) {
                return;
            }
            var filterValue = getSkuSuggestFilterValue(menu);
            var matches = suggestSkusFor(input.value, SKU_SUGGEST_LIMIT);
            activeSkuSuggestMatches = matches;
            menu.classList.remove('is-preview-open');
            menu.innerHTML = renderSkuSuggestList(matches, input.value, filterValue);
            bindSkuSuggestFilter(menu);
            positionSkuSuggestMenu(activeSkuSuggestWrap, menu);
        }

        function applySuggestedSku(sku, fromEl) {
            var wrap =
                (fromEl && fromEl.closest('.fc-sp-field-input-wrap--sku')) || activeSkuSuggestWrap;
            if (!wrap) {
                return;
            }
            var input = wrap.querySelector('.fc-sp-field-control--sku');
            if (!input) {
                return;
            }
            input.value = normalizeSkuValue(sku);
            syncFieldFilledState(input);
            setEditFormDirty(true);
            refreshSkuStatusForInput(input);
            closeSkuSuggest();
            input.focus();
        }

        function buildFieldControl(col, val) {
            var id = 'fc-sp-field-' + col.replace(/[^a-zA-Z0-9_-]/g, '_');
            var isSlug = col === 'SLUG';
            var isDescription = col === 'DESCRIPTION';
            var isSku = DETAILS_COLUMNS.indexOf(col) === -1;
            var isEmpty = String(val).trim() === '';
            var fieldClass = 'fc-sp-field-control';

            if (isSlug) {
                fieldClass += ' fc-sp-field-control--readonly';
            } else if (isSku) {
                fieldClass += ' fc-sp-field-control--sku';
                if (isEmpty) {
                    fieldClass += ' fc-sp-field-control--empty';
                }
            }

            if (isDescription) {
                fieldClass += ' fc-sp-field-control--textarea';
                return (
                    '<div class="fc-sp-field-input-wrap fc-sp-field-input-wrap--textarea">' +
                    '<textarea id="' +
                    escapeHtml(id) +
                    '" name="' +
                    escapeHtml(col) +
                    '" rows="4" class="' +
                    fieldClass +
                    '" autocomplete="off" placeholder="Product description shown in quotes and plans">' +
                    escapeHtml(val) +
                    '</textarea>' +
                    buildFieldCopyButton(id, formatHeader(col), { compact: true }) +
                    '</div>'
                );
            }

            var placeholder = '';
            if (isSlug) {
                placeholder = '';
            } else if (isSku) {
                placeholder = 'No SKU';
            } else if (col === 'PRODUCT') {
                placeholder = 'e.g. Panel - STD';
            } else if (col === 'SUPPLIER') {
                placeholder = 'e.g. JG';
            } else if (col === 'STYLE') {
                placeholder = 'e.g. flat_top';
            }

            if (isSku) {
                return (
                    '<div class="fc-sp-field-input-wrap fc-sp-field-input-wrap--sku">' +
                    '<button type="button" class="fc-sp-sku-check fc-sp-sku-check--empty" data-fc-sp-sku-check aria-expanded="false" aria-haspopup="listbox" aria-label="SKU is empty. Open similar catalogue SKUs." title="No SKU — click for similar catalogue SKUs">' +
                    '<i class="fa-solid fa-exclamation" aria-hidden="true"></i>' +
                    '</button>' +
                    '<input type="text" id="' +
                    escapeHtml(id) +
                    '" name="' +
                    escapeHtml(col) +
                    '" value="' +
                    escapeHtml(val) +
                    '" class="' +
                    fieldClass +
                    '" autocomplete="off" placeholder="' +
                    escapeHtml(placeholder) +
                    '">' +
                    buildFieldCopyButton(id, formatHeader(col)) +
                    '<div class="fc-sp-sku-suggest" data-fc-sp-sku-suggest hidden></div>' +
                    '</div>'
                );
            }

            return (
                '<div class="fc-sp-field-input-wrap">' +
                '<input type="text" id="' +
                escapeHtml(id) +
                '" name="' +
                escapeHtml(col) +
                '" value="' +
                escapeHtml(val) +
                '" class="' +
                fieldClass +
                '" autocomplete="off"' +
                (placeholder ? ' placeholder="' + escapeHtml(placeholder) + '"' : '') +
                (isSlug ? ' readonly tabindex="-1" aria-readonly="true"' : '') +
                '>' +
                buildFieldCopyButton(id, formatHeader(col)) +
                '</div>'
            );
        }

        function buildEditFormFields(row, fieldColumns) {
            return fieldColumns
                .map(function (col) {
                    var val = row[col] != null ? row[col] : '';
                    var id = 'fc-sp-field-' + col.replace(/[^a-zA-Z0-9_-]/g, '_');
                    var isSlug = col === 'SLUG';
                    var isDescription = col === 'DESCRIPTION';
                    var isSku = DETAILS_COLUMNS.indexOf(col) === -1;
                    var fieldHtml = buildFieldControl(col, val);
                    var labelPrefix = isSlug
                        ? '<i class="fa-solid fa-lock fc-sp-field__lock" aria-hidden="true"></i>'
                        : '';

                    var labelAndControl =
                        '<label class="fc-sp-field__label" for="' +
                        escapeHtml(id) +
                        '">' +
                        labelPrefix +
                        '<span>' +
                        escapeHtml(formatHeader(col)) +
                        '</span></label>' +
                        fieldHtml +
                        (isSlug
                            ? '<p class="fc-sp-field__help">Unique identifier — cannot be changed after creation.</p>'
                            : '');

                    // SKU columns carry the product thumbnail to the left of the label + input stack.
                    if (isSku) {
                        return (
                            '<div class="fc-sp-field fc-sp-field--sku">' +
                            skuThumbHtml(val) +
                            '<div class="fc-sp-field__main">' +
                            labelAndControl +
                            '</div></div>'
                        );
                    }

                    return (
                        '<div class="fc-sp-field' +
                        (isDescription ? ' fc-sp-field--wide' : '') +
                        '">' +
                        labelAndControl +
                        '</div>'
                    );
                })
                .join('');
        }

        function openEditModal(rowIndex) {
            ensureEditModal();
            var row = findRowByIndex(rowIndex);
            if (!row) {
                return;
            }

            editRowIndex = rowIndex;
            var detailsPanel = document.getElementById('fc-sp-edit-panel-details');
            var skuPanel = document.getElementById('fc-sp-edit-panel-sku');
            var titleEl = document.getElementById('fc-sp-edit-modal-title');
            var allSkuColumns = getSkuColumns(columns);
            var visibleSkuColumns = filterSkuColumnsByStyle(allSkuColumns, row.STYLE, styleColorsMap);
            if (detailsPanel) {
                detailsPanel.innerHTML = buildEditFormFields(row, getDetailsColumns(columns));
            }
            if (skuPanel) {
                var skuGrid = skuPanel.querySelector('.fc-sp-field-grid--sku');
                if (skuGrid) {
                    skuGrid.innerHTML = buildEditFormFields(row, visibleSkuColumns);
                }
            }
            closeSkuSuggest();
            setEditFormDirty(false);
            setSubmitLoading(false);
            if (titleEl) {
                titleEl.textContent = 'Edit product';
            }

            var submitBtn = document.getElementById('fc-sp-edit-submit');
            if (submitBtn) {
                submitBtn.disabled = false;
            }

            ensureWcSkuIndex().then(function () {
                refreshAllSkuStatuses();
            });

            if (editModalCloseTimer) {
                window.clearTimeout(editModalCloseTimer);
                editModalCloseTimer = null;
            }

            editModalEl.classList.remove('fc-sp-edit-modal--closing');
            editModalEl.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');

            requestAnimationFrame(function () {
                editModalEl.classList.add('fc-sp-edit-modal--visible');
            });

            var firstInput =
                editFormEl &&
                editFormEl.querySelector('#fc-sp-edit-panel-details input:not([readonly])');
            if (firstInput) {
                window.setTimeout(function () {
                    firstInput.focus();
                }, MODAL_TRANSITION_MS);
            }
        }

        function closeEditModal(force) {
            if (!editModalEl) {
                return;
            }
            if (
                !editModalEl.classList.contains('fc-sp-edit-modal--visible') ||
                editModalEl.classList.contains('fc-sp-edit-modal--closing')
            ) {
                return;
            }
            if (!force && editFormDirty) {
                var Modal = global.FcAdminModal;
                if (Modal && typeof Modal.confirm === 'function') {
                    Modal.confirm({
                        title: 'Discard changes?',
                        message: 'You have unsaved changes. Discard them?',
                        confirmLabel: 'Discard',
                        cancelLabel: 'Keep Editing',
                        variant: 'warning'
                    }).then(function (ok) {
                        if (ok) {
                            closeEditModal(true);
                        }
                    });
                    return;
                }
                if (!window.confirm('Discard unsaved changes?')) {
                    return;
                }
            }

            closeSkuSuggest();
            closeSkuImageGallery();
            editModalEl.classList.remove('fc-sp-edit-modal--visible');
            editModalEl.classList.add('fc-sp-edit-modal--closing');
            editModalEl.setAttribute('aria-hidden', 'true');

            if (editModalCloseTimer) {
                window.clearTimeout(editModalCloseTimer);
            }
            editModalCloseTimer = window.setTimeout(function () {
                editModalEl.classList.remove('fc-sp-edit-modal--closing');
                document.body.classList.remove('overflow-hidden');
                editRowIndex = null;
                editFormDirty = false;
                editModalCloseTimer = null;
            }, MODAL_TRANSITION_MS);
        }

        function saveEditProduct() {
            if (editRowIndex == null || !editFormEl || isSaving) {
                return;
            }

            var fields = {};
            var rowForSave = findRowByIndex(editRowIndex);
            columns.forEach(function (col) {
                if (col === 'SLUG' && rowForSave) {
                    fields[col] = rowForSave.SLUG != null ? String(rowForSave.SLUG) : '';
                    return;
                }
                var fieldEl = editFormEl.querySelector(
                    '[name="' + col.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]'
                );
                fields[col] = fieldEl ? fieldEl.value : rowForSave && rowForSave[col] != null ? rowForSave[col] : '';
            });

            var submitBtn = document.getElementById('fc-sp-edit-submit');
            setSubmitLoading(true);
            csvToast('saving', 'Saving product to products.csv…', TOAST_CSV_UPDATE);

            fetch(API_UPDATE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    rowIndex: editRowIndex,
                    fields: fields,
                    csrf: csrf
                })
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        if (!res.ok || !body.ok) {
                            throw new Error((body && body.error) || 'Update failed');
                        }
                        return body;
                    });
                })
                .then(function () {
                    setEditFormDirty(false);
                    closeEditModal(true);
                    var slug =
                        fields.SLUG != null && String(fields.SLUG).trim() !== ''
                            ? String(fields.SLUG).trim()
                            : 'Product';
                    var savedMsg = slug + ' updated.';
                    if (phpMode && onPersistReload) {
                        reloadWithNotice(savedMsg, 'success');
                        return null;
                    }
                    csvToast('ok', savedMsg, TOAST_CSV_UPDATE);
                    return fetch(API_LOAD, {
                        method: 'GET',
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin'
                    }).then(function (res) {
                        return res.json();
                    });
                })
                .then(function (body) {
                    if (!body) {
                        return;
                    }
                    if (body && body.ok && body.rows) {
                        allRows = body.rows.slice();
                        refreshTable();
                    }
                })
                .catch(function (err) {
                    csvToast('error', err.message || 'Could not save product.', TOAST_CSV_UPDATE);
                    setSubmitLoading(false);
                });
        }

        function initRowClick(tbody) {
            tbody.addEventListener('click', function (e) {
                if (dragJustEnded) {
                    dragJustEnded = false;
                    return;
                }
                if (e.target.closest('.fc-sp-sticky-grip')) {
                    return;
                }
                var tr = e.target.closest('tr[data-row-index]');
                if (!tr || tr.getAttribute('data-fc-sp-editable') !== '1') {
                    return;
                }
                var rowIndex = parseInt(tr.getAttribute('data-row-index'), 10);
                if (!Number.isFinite(rowIndex)) {
                    return;
                }
                openEditModal(rowIndex);
            });
        }

        function initRowDragDrop(tbody) {
            var dragRow = null;

            tbody.addEventListener('mousedown', function (e) {
                if (hasActiveFilters() || isSortActive()) {
                    return;
                }
                var grip = e.target.closest('.fc-sp-sticky-grip');
                if (!grip) {
                    return;
                }
                var tr = grip.closest('tr.fc-store-products-row');
                if (tr) {
                    tr.draggable = true;
                }
            });

            tbody.addEventListener('mouseup', function () {
                tbody.querySelectorAll('tr.fc-store-products-row').forEach(function (tr) {
                    tr.draggable = false;
                });
            });

            tbody.addEventListener('dragstart', function (e) {
                var tr = e.target.closest('tr.fc-store-products-row');
                if (!tr || hasActiveFilters() || isSortActive() || !tr.draggable) {
                    e.preventDefault();
                    return;
                }
                dragRow = tr;
                tr.classList.add('opacity-50', 'ring-2', 'ring-indigo-400');
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', tr.getAttribute('data-row-index') || '');
                }
            });

            tbody.addEventListener('dragend', function () {
                dragJustEnded = true;
                window.setTimeout(function () {
                    dragJustEnded = false;
                }, 100);
                if (dragRow) {
                    dragRow.classList.remove('opacity-50', 'ring-2', 'ring-indigo-400');
                    dragRow.draggable = false;
                }
                dragRow = null;
                tbody.querySelectorAll('.fc-store-products-row--drag-over').forEach(function (row) {
                    row.classList.remove('fc-store-products-row--drag-over');
                });
            });

            tbody.addEventListener('dragover', function (e) {
                if (!dragRow || hasActiveFilters() || isSortActive()) {
                    return;
                }
                e.preventDefault();
                var tr = e.target.closest('tr.fc-store-products-row');
                if (!tr || tr === dragRow) {
                    return;
                }
                if (e.dataTransfer) {
                    e.dataTransfer.dropEffect = 'move';
                }
                tbody.querySelectorAll('.fc-store-products-row--drag-over').forEach(function (row) {
                    row.classList.remove('fc-store-products-row--drag-over');
                });
                tr.classList.add('fc-store-products-row--drag-over', 'bg-indigo-100/60');
            });

            tbody.addEventListener('dragleave', function (e) {
                var tr = e.target.closest('tr.fc-store-products-row');
                if (tr) {
                    tr.classList.remove('fc-store-products-row--drag-over', 'bg-indigo-100/60');
                }
            });

            tbody.addEventListener('drop', function (e) {
                e.preventDefault();
                if (!dragRow || hasActiveFilters() || isSortActive()) {
                    return;
                }
                var target = e.target.closest('tr.fc-store-products-row');
                if (!target || target === dragRow) {
                    return;
                }

                var domRows = Array.prototype.slice.call(
                    tbody.querySelectorAll('tr.fc-store-products-row')
                );
                var fromIndex = domRows.indexOf(dragRow);
                var toIndex = domRows.indexOf(target);

                if (fromIndex < 0 || toIndex < 0) {
                    return;
                }

                allRows = moveItem(allRows, fromIndex, toIndex);
                refreshTable();

                if (saveTimer) {
                    window.clearTimeout(saveTimer);
                }
                saveTimer = window.setTimeout(function () {
                    saveOrder(allRows);
                }, 400);
            });
        }

        function destroyBottomHorizontalScroll() {
            if (!bottomScrollSync) {
                return;
            }
            window.removeEventListener('resize', bottomScrollSync.onResize);
            if (bottomScrollSync.scrollEl) {
                bottomScrollSync.scrollEl.removeEventListener('scroll', bottomScrollSync.onScrollMain);
            }
            if (bottomScrollSync.barEl) {
                bottomScrollSync.barEl.removeEventListener('scroll', bottomScrollSync.onScrollBar);
            }
            bottomScrollSync = null;
        }

        function initBottomHorizontalScroll(wrap) {
            destroyBottomHorizontalScroll();

            var scrollEl = wrap.querySelector('.fc-store-products-scroll');
            var barEl = wrap.querySelector('.fc-sp-bottom-scrollbar');
            var spacer = wrap.querySelector('.fc-sp-bottom-scrollbar-spacer');
            if (!scrollEl || !barEl || !spacer) {
                return;
            }

            var syncing = false;

            function updateSpacer() {
                spacer.style.width = scrollEl.scrollWidth + 'px';
                if (!syncing) {
                    syncing = true;
                    barEl.scrollLeft = scrollEl.scrollLeft;
                    syncing = false;
                }
            }

            function onScrollMain() {
                if (syncing) {
                    return;
                }
                syncing = true;
                barEl.scrollLeft = scrollEl.scrollLeft;
                syncing = false;
            }

            function onScrollBar() {
                if (syncing) {
                    return;
                }
                syncing = true;
                scrollEl.scrollLeft = barEl.scrollLeft;
                syncing = false;
            }

            function onResize() {
                updateSpacer();
            }

            scrollEl.addEventListener('scroll', onScrollMain, { passive: true });
            barEl.addEventListener('scroll', onScrollBar, { passive: true });
            window.addEventListener('resize', onResize);

            updateSpacer();
            window.requestAnimationFrame(updateSpacer);
            window.setTimeout(updateSpacer, 150);

            bottomScrollSync = {
                scrollEl: scrollEl,
                barEl: barEl,
                onScrollMain: onScrollMain,
                onScrollBar: onScrollBar,
                onResize: onResize
            };
        }

        function updateSortIndicators(root) {
            var ths = root.querySelectorAll('th[data-sort-col]');
            ths.forEach(function (th) {
                var icon = th.querySelector('.fc-sp-sort-icon');
                var isActive = th.getAttribute('data-sort-col') === sortState.column;
                th.setAttribute('aria-sort', isActive ? (sortState.dir === 'asc' ? 'ascending' : 'descending') : 'none');
                if (!icon) {
                    return;
                }
                icon.className = 'fa-solid fc-sp-sort-icon ' +
                    (isActive
                        ? (sortState.dir === 'asc' ? 'fa-sort-up text-slate-600' : 'fa-sort-down text-slate-600')
                        : 'fa-sort text-slate-300');
            });
        }

        function initHeaderSort(root) {
            var ths = root.querySelectorAll('th[data-sort-col]');
            ths.forEach(function (th) {
                if (th.dataset.fcSortBound === '1') {
                    return;
                }
                th.dataset.fcSortBound = '1';
                var column = th.getAttribute('data-sort-col');
                th.addEventListener('click', function () {
                    navigateWithSort(column);
                });
                th.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        navigateWithSort(column);
                    }
                });
            });
            updateSortIndicators(root);
        }

        function paintTable(rows) {
            var wrap = document.getElementById('fc-store-products-table-wrap');
            var countEl = document.getElementById('fc-store-products-count');
            if (!wrap) {
                return;
            }

            destroyBottomHorizontalScroll();

            var filtered = hasActiveFilters() || isSortActive();
            var displayColumns = getListDisplayColumns(columns);

            function renderRows(visibleRows) {
                if (!visibleRows.length) {
                    wrap.innerHTML =
                        '<p class="p-8 text-center text-sm text-slate-500">No system products match your filters.</p>';
                } else {
                    wrap.innerHTML = buildTable(columns, visibleRows, {
                        draggable: !filtered,
                        allColumns: columns,
                        styleColorsMap: styleColorsMap,
                        displayColumns: displayColumns
                    });
                    var tbody = document.getElementById('fc-store-products-tbody');
                    if (tbody) {
                        initRowClick(tbody);
                        if (!filtered) {
                            initRowDragDrop(tbody);
                        }
                    }
                    initHeaderSort(wrap);
                    initBottomHorizontalScroll(wrap);
                }

                if (countEl) {
                    countEl.textContent =
                        visibleRows.length === allRows.length
                            ? String(allRows.length)
                            : visibleRows.length + ' of ' + allRows.length;
                }
            }

            renderRows(rows);

            if (rows.length && !wcSkuSet) {
                ensureWcSkuIndex().then(function () {
                    var stillWrap = document.getElementById('fc-store-products-table-wrap');
                    if (!stillWrap || stillWrap !== wrap) {
                        return;
                    }
                    renderRows(getVisibleRows());
                });
            }
        }

        function bindFilters() {
            var supplierSelect = document.getElementById('fc-store-products-filter-supplier');
            var styleSelect = document.getElementById('fc-store-products-filter-style');
            var clearBtn = document.getElementById('fc-store-products-clear-filters');
            var searchInput = document.getElementById('fc-store-products-search');

            if (searchInput) {
                searchInput.value = searchQuery;
            }

            if (supplierSelect) {
                supplierSelect.addEventListener('change', function () {
                    supplierFilter = supplierSelect.value;
                    refreshTable({ immediateUrlSync: true });
                });
            }

            if (styleSelect) {
                styleSelect.addEventListener('change', function () {
                    styleFilter = styleSelect.value;
                    refreshTable({ immediateUrlSync: true });
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    searchQuery = searchInput.value.trim();
                    refreshTable({ immediateUrlSync: false });
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    resetFilters();
                    refreshTable({ syncUrl: false });
                });
            }
        }

        return {
            html: shell,
            init: function () {
                ensureEditModal();
                refreshTable({ syncUrl: false });
                bindFilters();
            },
            bindPhpShell: function (options) {
                options = options || {};
                if (options.canEdit !== false) {
                    ensureEditModal();
                }
                var wrap = document.getElementById('fc-store-products-table-wrap');
                var tbody = options.tbody || document.getElementById('fc-store-products-tbody');
                if (wrap) {
                    initHeaderSort(wrap);
                    initBottomHorizontalScroll(wrap);
                }
                if (tbody && options.canEdit !== false) {
                    initRowClick(tbody);
                    if (options.canReorder) {
                        initRowDragDrop(tbody);
                    }
                }
            },
            destroy: function () {
                if (filterUrlSyncTimer) {
                    window.clearTimeout(filterUrlSyncTimer);
                    filterUrlSyncTimer = null;
                }
                destroyBottomHorizontalScroll();
                closeEditModal(true);
            }
        };
    }

    function loadStoreProducts(container) {
        if (!container) {
            return Promise.resolve();
        }

        if (container.querySelector('[data-fc-store-products-server]')) {
            return hydrateFromServer(container);
        }

        if (typeof global.fcAdminUrl === 'function') {
            global.location.href = global.fcAdminUrl('products/store-products');
            return Promise.resolve();
        }

        container.innerHTML = renderLoading();

        return fetch(API_LOAD, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.error) || 'Request failed');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Failed to load system products');
                }
                var page = renderPage(data);
                container.innerHTML = page.html;
                pageController.destroy = page.destroy;
                page.init();
                window.requestAnimationFrame(function () {
                    window.dispatchEvent(new Event('resize'));
                });
            })
            .catch(function (err) {
                container.innerHTML = renderError(err.message || 'Unknown error');
            });
    }

    function readBootstrapData() {
        var el = document.getElementById('fc-store-products-bootstrap');
        if (!el) {
            return null;
        }

        try {
            return JSON.parse(el.textContent || '');
        } catch (e) {
            return null;
        }
    }

    function bindStoreProductsCsvActions(bootstrap) {
        var dropdown = document.querySelector('[data-fc-store-products-download-dropdown]');
        if (!dropdown || dropdown.dataset.fcBound === '1') {
            return;
        }
        dropdown.dataset.fcBound = '1';

        var toggle = dropdown.querySelector('[data-fc-store-products-download-toggle]');
        var panel = dropdown.querySelector('.fc-products-download-dropdown__panel');
        var csvTrigger = dropdown.querySelector('[data-fc-store-products-download-csv]');
        var importTrigger = dropdown.querySelector('[data-fc-store-products-import-csv]');
        var importInput = dropdown.querySelector('[data-fc-store-products-import-input]');
        var csrf = String((bootstrap && bootstrap.csrf) || '');

        var toast = global.FC.util.toastProductsCatalogue;

        function closeMenu() {
            if (!panel || !toggle) {
                return;
            }
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            dropdown.classList.remove('is-open');
            panel.style.left = '';
            panel.style.top = '';
            panel.style.position = '';
            panel.style.right = '';
            panel.style.zIndex = '';
        }

        function positionMenu() {
            if (!toggle || !panel || panel.hidden) {
                return;
            }
            var rect = toggle.getBoundingClientRect();
            var gap = 6;
            panel.style.position = 'fixed';
            panel.style.zIndex = '80';
            panel.style.left = Math.round(rect.left) + 'px';
            panel.style.top = Math.round(rect.bottom + gap) + 'px';
            panel.style.right = 'auto';

            var panelRect = panel.getBoundingClientRect();
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

            if (panelRect.right > viewportWidth - 8) {
                panel.style.left = Math.max(8, viewportWidth - panelRect.width - 8) + 'px';
            }
            if (panelRect.bottom > viewportHeight - 8) {
                var aboveTop = rect.top - gap - panelRect.height;
                if (aboveTop >= 8) {
                    panel.style.top = aboveTop + 'px';
                }
            }
        }

        function openMenu() {
            if (!panel || !toggle) {
                return;
            }
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            dropdown.classList.add('is-open');
            positionMenu();
        }

        function downloadCsvFile() {
            if (
                csvTrigger &&
                (csvTrigger.disabled || csvTrigger.getAttribute('aria-disabled') === 'true')
            ) {
                return;
            }
            closeMenu();
            var link = document.createElement('a');
            link.href = fcApiUrl('products', 'action=download-store-products-csv');
            link.download =
                (csvTrigger && csvTrigger.getAttribute('data-fc-store-products-csv-name')) ||
                'products.csv';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }

        function importCsvFile(file) {
            if (!file) {
                return;
            }
            var name = String(file.name || '').toLowerCase();
            if (!name.endsWith('.csv')) {
                toast('error', 'Only .csv files can be imported.');
                return;
            }
            if (!csrf) {
                toast('error', 'Missing security token. Refresh and try again.');
                return;
            }

            closeMenu();
            toast('saving', 'Importing CSV…');

            var formData = new FormData();
            formData.append('csrf', csrf);
            formData.append('file', file);

            fetch(fcApiUrl('products', 'action=import-store-products-csv'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: formData
            })
                .then(function (response) {
                    return response
                        .json()
                        .catch(function () {
                            return { ok: false, error: 'Invalid server response.' };
                        })
                        .then(function (body) {
                            if (!response.ok || !body.ok) {
                                throw new Error((body && body.error) || 'Could not import CSV.');
                            }
                            return body;
                        });
                })
                .then(function (body) {
                    reloadWithNotice(body.message || 'CSV imported successfully.', 'success');
                })
                .catch(function (error) {
                    toast('error', (error && error.message) || 'Could not import CSV.');
                })
                .then(function () {
                    if (importInput) {
                        importInput.value = '';
                    }
                });
        }

        if (toggle && panel) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (panel.hidden) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });
            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });
            document.addEventListener('click', function () {
                closeMenu();
            });
            window.addEventListener('resize', function () {
                if (!panel.hidden) {
                    positionMenu();
                }
            });
            window.addEventListener(
                'scroll',
                function () {
                    if (!panel.hidden) {
                        positionMenu();
                    }
                },
                true
            );
        }

        if (csvTrigger) {
            csvTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                downloadCsvFile();
            });
        }

        if (importTrigger && importInput) {
            importTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                closeMenu();
                importInput.click();
            });
            importInput.addEventListener('change', function () {
                var file = importInput.files && importInput.files[0] ? importInput.files[0] : null;
                importCsvFile(file);
            });
        }
    }

    function hydrateFromServer(container) {
        if (!container || !container.querySelector('[data-fc-store-products-server]')) {
            return Promise.resolve(false);
        }

        var bootstrap = readBootstrapData() || {};
        if (bootstrap.phpRendered || bootstrap.deferLoad === false) {
            var page = renderPage({
                columns: bootstrap.columns || [],
                rows: bootstrap.rows || [],
                styleColors: bootstrap.styleColors || {},
                filters: bootstrap.filters || {},
                total: bootstrap.total || 0,
                file: bootstrap.file || 'products.csv',
                phpMode: true,
                csrf: bootstrap.csrf || '',
                onPersistReload: function () {
                    window.location.reload();
                }
            });
            pageController.destroy = page.destroy;
            if (typeof page.bindPhpShell === 'function') {
                page.bindPhpShell({
                    tbody: document.getElementById('fc-store-products-tbody'),
                    canReorder: !!bootstrap.canReorder,
                    canEdit: bootstrap.canEdit !== false
                });
            }
            if (bootstrap.canEdit !== false) {
                bindStoreProductsCsvActions(bootstrap);
            }
            container.removeAttribute('aria-busy');
            showHeaderNotice(container, consumeFlash());
            window.requestAnimationFrame(function () {
                window.dispatchEvent(new Event('resize'));
            });
            return Promise.resolve(true);
        }

        var wrap = document.getElementById('fc-store-products-table-wrap');
        if (wrap) {
            wrap.innerHTML = renderLoading();
        }

        return fetch(API_LOAD, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok) {
                        throw new Error((body && body.error) || 'Request failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                if (!body.ok) {
                    throw new Error(body.error || 'Failed to load system products');
                }

                if (bootstrap.filters && typeof bootstrap.filters === 'object') {
                    body.filters = bootstrap.filters;
                }

                var page = renderPage(body);
                container.innerHTML = page.html;
                pageController.destroy = page.destroy;
                page.init();
                container.removeAttribute('aria-busy');
                window.requestAnimationFrame(function () {
                    window.dispatchEvent(new Event('resize'));
                });
                return true;
            })
            .catch(function (err) {
                if (wrap) {
                    wrap.innerHTML = renderError(err.message || 'Unknown error');
                } else if (container) {
                    container.innerHTML = renderError(err.message || 'Unknown error');
                }
                return false;
            });
    }

    global.FC.PageRegistry.register('products/system-products', pageController);
})(window);
