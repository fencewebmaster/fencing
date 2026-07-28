/**
 * FC Admin — Store Products page (wc-products-GO.csv / wc-products-JG.csv).
 */
(function (global) {
    'use strict';

    var TABS = ['GO', 'JG'];
    var PRIMARY_COLUMNS = ['Images', 'SKU', 'Name'];
    var SYSTEM_COLUMN_ORDER = ['Images', 'SKU', 'Name'];
    var GALLERY_BODY_CLASS = 'fc-entries-cart-gallery-open';
    var galleryEl = null;
    var galleryKeydownHandler = null;
    var galleryState = {
        slides: [],
        index: 0
    };

    function orderSystemProductColumns(cols) {
        var ordered = [];
        cols = cols.filter(function (col) {
            return col !== 'ID';
        });
        SYSTEM_COLUMN_ORDER.forEach(function (col) {
            if (cols.indexOf(col) !== -1) {
                ordered.push(col);
            }
        });
        cols.forEach(function (col) {
            if (ordered.indexOf(col) === -1) {
                ordered.push(col);
            }
        });
        return ordered;
    }

    function parseImageUrls(val) {
        return String(val || '')
            .split(',')
            .map(function (part) {
                return part.trim();
            })
            .filter(Boolean);
    }

    function encodeDataAttr(value) {
        return escapeHtml(JSON.stringify(value));
    }

    var FILTER_URL_KEYS = {
        source: 'source',
        q: 'q'
    };

    function scrollGalleryThumbIntoView() {
        if (!galleryEl) {
            return;
        }

        var thumbsEl = galleryEl.querySelector('[data-fc-cart-gallery-thumbs]');
        if (!thumbsEl || thumbsEl.hidden) {
            return;
        }

        var activeThumb = thumbsEl.querySelector('[data-fc-cart-gallery-thumb].is-active');
        if (!activeThumb) {
            return;
        }

        activeThumb.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest'
        });
    }

    function renderGalleryThumbs() {
        if (!galleryEl) {
            return;
        }

        var thumbsEl = galleryEl.querySelector('[data-fc-cart-gallery-thumbs]');
        if (!thumbsEl) {
            return;
        }

        if (galleryState.slides.length <= 1) {
            thumbsEl.hidden = true;
            thumbsEl.innerHTML = '';
            return;
        }

        thumbsEl.hidden = false;
        thumbsEl.innerHTML = galleryState.slides
            .map(function (slide, index) {
                var active = index === galleryState.index ? ' is-active' : '';
                return (
                    '<button type="button" class="fc-entries-cart-gallery__thumb' +
                    active +
                    '" data-fc-cart-gallery-thumb="' +
                    index +
                    '" aria-label="View image ' +
                    (index + 1) +
                    ' of ' +
                    galleryState.slides.length +
                    '">' +
                    '<img src="' +
                    escapeHtml(slide.url) +
                    '" alt="" loading="lazy" decoding="async">' +
                    '</button>'
                );
            })
            .join('');

        requestAnimationFrame(scrollGalleryThumbIntoView);
    }

    function renderGallerySlide() {
        if (!galleryEl || !galleryState.slides.length) {
            return;
        }

        var slide = galleryState.slides[galleryState.index];
        var imageEl = galleryEl.querySelector('[data-fc-cart-gallery-image]');
        var captionEl = galleryEl.querySelector('[data-fc-cart-gallery-caption]');
        var counterEl = galleryEl.querySelector('[data-fc-cart-gallery-counter]');
        var prevBtn = galleryEl.querySelector('[data-fc-cart-gallery-prev]');
        var nextBtn = galleryEl.querySelector('[data-fc-cart-gallery-next]');

        if (imageEl) {
            imageEl.src = slide.url;
            imageEl.alt = slide.caption || 'Product image';
        }

        if (captionEl) {
            captionEl.textContent = slide.caption || '';
            captionEl.hidden = !slide.caption;
        }

        if (counterEl) {
            counterEl.textContent = galleryState.index + 1 + ' / ' + galleryState.slides.length;
            counterEl.hidden = galleryState.slides.length <= 1;
        }

        if (prevBtn) {
            prevBtn.disabled = galleryState.slides.length <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = galleryState.slides.length <= 1;
        }

        galleryEl.querySelectorAll('[data-fc-cart-gallery-thumb]').forEach(function (btn) {
            var thumbIndex = parseInt(btn.getAttribute('data-fc-cart-gallery-thumb') || '-1', 10);
            btn.classList.toggle('is-active', thumbIndex === galleryState.index);
        });

        scrollGalleryThumbIntoView();
    }

    function closeGalleryModal() {
        if (!galleryEl) {
            return;
        }

        if (galleryKeydownHandler) {
            document.removeEventListener('keydown', galleryKeydownHandler);
            galleryKeydownHandler = null;
        }

        galleryEl.remove();
        galleryEl = null;
        galleryState.slides = [];
        galleryState.index = 0;
        document.body.classList.remove(GALLERY_BODY_CLASS);
    }

    function showGallerySlide(index) {
        if (!galleryState.slides.length) {
            return;
        }

        if (index < 0) {
            index = galleryState.slides.length - 1;
        } else if (index >= galleryState.slides.length) {
            index = 0;
        }

        galleryState.index = index;
        renderGallerySlide();
    }

    function openGalleryModal(urls, title, startIndex) {
        if (!urls || !urls.length) {
            return;
        }

        closeGalleryModal();

        var caption = title || 'Product images';
        galleryState.slides = urls.map(function (url) {
            return {
                url: url,
                caption: caption
            };
        });
        galleryState.index = Math.max(0, Math.min(startIndex || 0, galleryState.slides.length - 1));

        galleryEl = document.createElement('div');
        galleryEl.className = 'fc-entries-cart-gallery';
        galleryEl.setAttribute('role', 'dialog');
        galleryEl.setAttribute('aria-modal', 'true');
        galleryEl.setAttribute('aria-label', caption);
        galleryEl.innerHTML =
            '<div class="fc-entries-cart-gallery__backdrop" data-fc-cart-gallery-close aria-hidden="true"></div>' +
            '<button type="button" class="fencing-modal-close" data-fc-cart-gallery-close aria-label="Close"></button>' +
            '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--prev" data-fc-cart-gallery-prev aria-label="Previous image">' +
            '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>' +
            '<div class="fc-entries-cart-gallery__stage">' +
            '<img class="fc-entries-cart-gallery__image" data-fc-cart-gallery-image src="" alt="">' +
            '<p class="fc-entries-cart-gallery__caption" data-fc-cart-gallery-caption hidden></p>' +
            '<span class="fc-entries-cart-gallery__counter" data-fc-cart-gallery-counter hidden></span>' +
            '</div>' +
            '<button type="button" class="fc-entries-cart-gallery__nav fc-entries-cart-gallery__nav--next" data-fc-cart-gallery-next aria-label="Next image">' +
            '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>' +
            '<div class="fc-entries-cart-gallery__thumbs" data-fc-cart-gallery-thumbs hidden></div>';

        document.body.appendChild(galleryEl);
        document.body.classList.add(GALLERY_BODY_CLASS);

        galleryEl.querySelectorAll('[data-fc-cart-gallery-close]').forEach(function (btn) {
            btn.addEventListener('click', closeGalleryModal);
        });

        var prevBtn = galleryEl.querySelector('[data-fc-cart-gallery-prev]');
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                showGallerySlide(galleryState.index - 1);
            });
        }

        var nextBtn = galleryEl.querySelector('[data-fc-cart-gallery-next]');
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                showGallerySlide(galleryState.index + 1);
            });
        }

        galleryEl.addEventListener('click', function (e) {
            var thumbBtn = e.target.closest('[data-fc-cart-gallery-thumb]');
            if (!thumbBtn) {
                return;
            }
            e.preventDefault();
            showGallerySlide(parseInt(thumbBtn.getAttribute('data-fc-cart-gallery-thumb') || '0', 10));
        });

        galleryKeydownHandler = function (e) {
            if (!galleryEl) {
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                closeGalleryModal();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                showGallerySlide(galleryState.index - 1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                showGallerySlide(galleryState.index + 1);
            }
        };

        document.addEventListener('keydown', galleryKeydownHandler);
        renderGalleryThumbs();
        renderGallerySlide();
    }

    function destroyGalleryModal() {
        closeGalleryModal();
    }

    function apiUrl(source) {
        return fcApiUrl('products', 'action=system-products&source=' + encodeURIComponent(source));
    }

    function readFiltersFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var source = String(params.get(FILTER_URL_KEYS.source) || 'GO')
            .trim()
            .toUpperCase();
        if (TABS.indexOf(source) === -1) {
            source = 'GO';
        }

        return {
            source: source,
            q: params.get(FILTER_URL_KEYS.q) || ''
        };
    }

    function syncFiltersToUrl(source, q) {
        var params = new URLSearchParams();
        if (source && source !== 'GO') {
            params.set(FILTER_URL_KEYS.source, source);
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

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatHeader(label) {
        return String(label || '')
            .replace(/_/g, ' ')
            .toLowerCase()
            .replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
    }

    function applySearchFilter(rows, query) {
        if (!query) {
            return rows;
        }
        var q = query.toLowerCase();
        return rows.filter(function (row) {
            return Object.keys(row).some(function (key) {
                if (key === '_rowIndex') {
                    return false;
                }
                return String(row[key] || '')
                    .toLowerCase()
                    .indexOf(q) !== -1;
            });
        });
    }

    function renderLoading() {
        return (
            '<div class="flex flex-col items-center justify-center gap-3 p-12 text-slate-500">' +
            '<i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-500" aria-hidden="true"></i>' +
            '<p class="text-sm">Loading store products…</p>' +
            '</div>'
        );
    }

    function renderError(message) {
        return (
            '<div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">' +
            '<p class="font-semibold">Could not load store products</p>' +
            '<p class="mt-1 text-sm">' +
            escapeHtml(message) +
            '</p>' +
            '</div>'
        );
    }

    function formatCellValue(col, val, row) {
        if (val === '') {
            return { html: '—', empty: true };
        }

        if (col === 'Images') {
            var urls = parseImageUrls(val);
            var productName =
                row && (row.Name || row.SKU) ? String(row.Name || row.SKU) : 'Product images';

            if (!urls.length) {
                return {
                    html:
                        '<div class="fc-sys-images-fill">' +
                        '<span class="fc-sys-images-placeholder" title="No images">' +
                        '<i class="fa-solid fa-image" aria-hidden="true"></i>' +
                        '<span class="sr-only">No images</span></span></div>',
                    empty: true
                };
            }

            var thumbUrl = urls[0];
            var extraCount = urls.length > 1 ? urls.length - 1 : 0;
            return {
                html:
                    '<div class="fc-sys-images-fill">' +
                    '<button type="button" class="fc-sys-images-trigger" data-fc-sys-images="' +
                    encodeDataAttr(urls) +
                    '" data-fc-sys-images-title="' +
                    escapeHtml(productName) +
                    '" aria-label="View ' +
                    String(urls.length) +
                    (urls.length === 1 ? ' image' : ' images') +
                    ' for ' +
                    escapeHtml(productName) +
                    '">' +
                    '<img src="' +
                    escapeHtml(thumbUrl) +
                    '" alt="" class="fc-sys-images-thumb" loading="lazy" decoding="async" />' +
                    '<span class="fc-sys-images-overlay" aria-hidden="true">' +
                    '<i class="fa-solid fa-magnifying-glass-plus"></i></span>' +
                    (extraCount
                        ? '<span class="fc-sys-images-badge">+' + String(extraCount) + '</span>'
                        : '') +
                    '</button></div>',
                empty: false
            };
        }

        if (col === 'Name') {
            return {
                html:
                    '<span class="block max-w-md truncate" title="' +
                    escapeHtml(val) +
                    '">' +
                    escapeHtml(val) +
                    '</span>',
                empty: false
            };
        }

        if (col === 'SKU') {
            return {
                html:
                    '<span class="fc-sys-sku-value block text-sm text-slate-800">' +
                    escapeHtml(val) +
                    '</span>',
                empty: false
            };
        }

        return { html: escapeHtml(val), empty: false };
    }

    function buildTable(columns, rows) {
        var colgroup = columns
            .map(function (col) {
                var isPrimary = PRIMARY_COLUMNS.indexOf(col) !== -1;
                var widthClass =
                    col === 'ID'
                        ? 'fc-sys-id-col'
                        : col === 'Images'
                        ? 'fc-sys-images-col'
                        : col === 'SKU'
                          ? 'fc-sys-sku-col'
                          : col === 'Name'
                            ? 'fc-sys-name-col'
                          : isPrimary
                            ? 'min-w-[8rem]'
                            : 'min-w-[6rem]';
                return '<col class="' + widthClass + '" />';
            })
            .join('');

        var thead =
            '<thead class="fc-sp-table-head text-left">' +
            '<tr>' +
            columns
                .map(function (col, colIndex) {
                    var sticky =
                        colIndex === 0
                            ? ' fc-sp-sticky fc-sp-sticky-col relative'
                            : '';
                    return (
                        '<th scope="col" class="' +
                        (col === 'Images'
                            ? 'fc-sys-images-cell '
                            : col === 'SKU'
                              ? 'fc-sys-sku-cell '
                              : col === 'Name'
                                ? 'fc-sys-name-cell '
                                : 'whitespace-nowrap ') +
                        'px-3 py-2' +
                        (col === 'Images' ? ' fc-sys-images-cell--head' : '') +
                        sticky +
                        '">' +
                        escapeHtml(formatHeader(col)) +
                        '</th>'
                    );
                })
                .join('') +
            '</tr></thead>';

        var tbody =
            '<tbody class="divide-y divide-slate-100 text-sm text-slate-700">' +
            rows
                .map(function (row, rowIdx) {
                    var stripeBg = rowIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50';
                    return (
                        '<tr class="' +
                        stripeBg +
                        '/50">' +
                        columns
                            .map(function (col, colIndex) {
                                var val = row[col] != null ? row[col] : '';
                                var formatted = formatCellValue(col, val, row);
                                var sticky =
                                    colIndex === 0
                                        ? ' fc-sp-sticky fc-sp-sticky-col relative ' + stripeBg
                                        : '';
                                return (
                                    '<td class="border-b border-slate-100' +
                                    sticky +
                                    (formatted.empty && col !== 'Images' ? ' text-slate-300' : '') +
                                    (col === 'Images' ? ' fc-sys-images-cell' : ' px-3 py-2') +
                                    (col === 'SKU' ? ' fc-sys-sku-cell' : '') +
                                    (col === 'Name' ? ' fc-sys-name-cell' : '') +
                                    (col === 'Name' || col === 'Images' || col === 'SKU'
                                        ? ''
                                        : ' whitespace-nowrap') +
                                    '">' +
                                    formatted.html +
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
            '<div class="fc-system-products-scroll fc-sp-table-body fc-sp-hide-x-scrollbar min-h-0 flex-1 overflow-x-hidden overflow-y-auto">' +
            '<table class="fc-system-products-table fc-system-products-table--fixed w-full border-collapse text-left">' +
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

    function renderPage(initialData) {
        var urlFilters = readFiltersFromUrl();
        var activeTab = initialData.source || urlFilters.source || 'GO';
        var tabCache = {};
        tabCache[activeTab] = initialData;
        var columns = orderSystemProductColumns(initialData.columns || []);
        var allRows = (initialData.rows || []).slice();
        var searchQuery = urlFilters.q;
        var bottomScrollSync = null;
        var filterUrlSyncTimer = null;

        var shell =
            '<div class="flex h-full min-h-0 flex-col">' +
            '<div class="fc-gallery-page__tabs fc-system-products-tabs" role="tablist" aria-label="Store product source">' +
            TABS.map(function (tab) {
                var isActive = tab === activeTab;
                var cached = tabCache[tab];
                var countText = '…';
                var countHidden = false;
                if (cached) {
                    var total = Number(cached.total || 0);
                    countText = String(total);
                    countHidden = !(total > 0);
                }
                return (
                    '<button type="button" role="tab" data-fc-sys-tab="' +
                    tab +
                    '" aria-selected="' +
                    (isActive ? 'true' : 'false') +
                    '" class="fc-gallery-page__tab fc-system-products-tab' +
                    (isActive ? ' is-active' : '') +
                    '">' +
                    '<span>' +
                    tab +
                    '</span>' +
                    '<span class="fc-system-products-tab__count" data-fc-sys-tab-count="' +
                    tab +
                    '"' +
                    (countHidden ? ' hidden' : '') +
                    '>' +
                    countText +
                    '</span></button>'
                );
            }).join('') +
            '</div>' +
            '<div class="fc-entries-page__toolbar fc-sp-toolbar fc-admin-sticky-header sticky top-0 z-20">' +
            '<div class="fc-entries-page__toolbar-row">' +
            '<label class="fc-entries-page__search-wrap">' +
            '<i class="fa-solid fa-magnifying-glass fc-entries-page__search-icon" aria-hidden="true"></i>' +
            '<input type="search" id="fc-system-products-search" class="fc-entries-page__search" placeholder="Search products…" aria-label="Search products" autocomplete="off">' +
            '</label>' +
            '<button type="button" id="fc-system-products-clear-search" class="fc-sp-toolbar__clear hidden">Clear search</button>' +
            '</div>' +
            '<div class="fc-entries-page__count fc-sys-toolbar-meta">' +
            '<span><span id="fc-system-products-count">' +
            allRows.length +
            '</span> Items</span>' +
            '<span id="fc-system-products-file" class="fc-sys-toolbar-meta__file">' +
            escapeHtml(initialData.file || '') +
            '</span>' +
            '</div>' +
            '</div>' +
            '<div id="fc-system-products-table-wrap" class="flex min-h-0 flex-1 flex-col overflow-hidden"></div>' +
            '</div>';

        function getVisibleRows() {
            return applySearchFilter(allRows, searchQuery);
        }

        function hasActiveSearch() {
            return searchQuery.length > 0;
        }

        function refreshClearSearchButton() {
            var clearBtn = document.getElementById('fc-system-products-clear-search');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', !hasActiveSearch());
            }
        }

        function clearSearch() {
            searchQuery = '';
            var searchInput = document.getElementById('fc-system-products-search');
            if (searchInput) {
                searchInput.value = '';
            }
            refreshClearSearchButton();
        }

        function scheduleFilterUrlSync(immediate) {
            if (filterUrlSyncTimer) {
                window.clearTimeout(filterUrlSyncTimer);
                filterUrlSyncTimer = null;
            }
            if (immediate) {
                syncFiltersToUrl(activeTab, searchQuery);
                return;
            }
            filterUrlSyncTimer = window.setTimeout(function () {
                filterUrlSyncTimer = null;
                syncFiltersToUrl(activeTab, searchQuery);
            }, 300);
        }

        function refreshResults(options) {
            options = options || {};
            refreshClearSearchButton();
            paintTable(getVisibleRows());
            if (options.syncUrl !== false) {
                scheduleFilterUrlSync(options.immediateUrlSync);
            }
        }

        function setTabCountBadge(el, total) {
            if (!el) {
                return;
            }
            var count = Number(total || 0);
            if (!isFinite(count) || count < 0) {
                count = 0;
            }
            el.textContent = String(count);
            if (count > 0) {
                el.hidden = false;
                el.removeAttribute('hidden');
            } else {
                el.hidden = true;
                el.setAttribute('hidden', '');
            }
        }

        function updateTabCounts() {
            TABS.forEach(function (tab) {
                var el = document.querySelector('[data-fc-sys-tab-count="' + tab + '"]');
                if (el && tabCache[tab]) {
                    setTabCountBadge(el, tabCache[tab].total || 0);
                }
            });
        }

        function setActiveTabUi(tab) {
            document.querySelectorAll('[data-fc-sys-tab]').forEach(function (btn) {
                var isActive = btn.getAttribute('data-fc-sys-tab') === tab;
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                btn.classList.toggle('is-active', isActive);
                btn.classList.toggle('bg-white', isActive);
                btn.classList.toggle('text-slate-900', isActive);
                btn.classList.toggle('shadow-sm', isActive);
                btn.classList.toggle('text-slate-600', !isActive);
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

            var scrollEl = wrap.querySelector('.fc-system-products-scroll');
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

        function paintTable(rows) {
            var wrap = document.getElementById('fc-system-products-table-wrap');
            var countEl = document.getElementById('fc-system-products-count');
            if (!wrap) {
                return;
            }

            destroyBottomHorizontalScroll();

            if (!rows.length) {
                wrap.innerHTML =
                    '<p class="p-8 text-center text-sm text-slate-500">No store products match your search.</p>';
            } else {
                wrap.innerHTML = buildTable(columns, rows);
                initBottomHorizontalScroll(wrap);
            }

            if (countEl) {
                countEl.textContent =
                    rows.length === allRows.length
                        ? String(allRows.length)
                        : rows.length + ' of ' + allRows.length;
            }
        }

        function applyTabData(data) {
            activeTab = data.source || activeTab;
            columns = orderSystemProductColumns(data.columns || []);
            allRows = (data.rows || []).slice();
            tabCache[activeTab] = data;

            var fileEl = document.getElementById('fc-system-products-file');
            if (fileEl) {
                fileEl.textContent = data.file || '';
            }

            var searchInput = document.getElementById('fc-system-products-search');
            if (searchInput) {
                searchQuery = searchInput.value.trim();
            }

            setActiveTabUi(activeTab);
            updateTabCounts();
            refreshResults({ syncUrl: false });
        }

        function loadTab(tab) {
            if (tabCache[tab]) {
                applyTabData(tabCache[tab]);
                scheduleFilterUrlSync(true);
                return Promise.resolve();
            }

            var wrap = document.getElementById('fc-system-products-table-wrap');
            if (wrap) {
                wrap.innerHTML = renderLoading();
            }

            return fetch(apiUrl(tab), {
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
                .then(function (data) {
                    if (!data.ok) {
                        throw new Error(data.error || 'Failed to load store products');
                    }
                    applyTabData(data);
                    scheduleFilterUrlSync(true);
                })
                .catch(function (err) {
                    if (wrap) {
                        wrap.innerHTML = renderError(err.message || 'Unknown error');
                    }
                });
        }

        function bindControls() {
            var tableWrap = document.getElementById('fc-system-products-table-wrap');
            if (tableWrap && !tableWrap.dataset.fcSysGalleryBound) {
                tableWrap.dataset.fcSysGalleryBound = '1';
                tableWrap.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-fc-sys-images]');
                    if (!btn) {
                        return;
                    }
                    e.preventDefault();
                    var raw = btn.getAttribute('data-fc-sys-images');
                    var urls = [];
                    try {
                        urls = JSON.parse(raw || '[]');
                    } catch (err) {
                        urls = [];
                    }
                    if (!Array.isArray(urls) || !urls.length) {
                        return;
                    }
                    openGalleryModal(
                        urls,
                        btn.getAttribute('data-fc-sys-images-title') || 'Product images',
                        0
                    );
                });
            }

            var tabList = document.querySelector('[role="tablist"][aria-label="Store product source"]');
            if (tabList) {
                tabList.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-fc-sys-tab]');
                    if (!btn) {
                        return;
                    }
                    var tab = btn.getAttribute('data-fc-sys-tab');
                    if (!tab || tab === activeTab) {
                        return;
                    }
                    loadTab(tab);
                });

                tabList.addEventListener('keydown', function (e) {
                    if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
                        return;
                    }
                    e.preventDefault();
                    var tabs = Array.prototype.slice.call(tabList.querySelectorAll('[data-fc-sys-tab]'));
                    var current = tabs.findIndex(function (btn) {
                        return btn.getAttribute('aria-selected') === 'true';
                    });
                    var next =
                        e.key === 'ArrowRight'
                            ? tabs[(current + 1) % tabs.length]
                            : tabs[(current - 1 + tabs.length) % tabs.length];
                    if (next) {
                        loadTab(next.getAttribute('data-fc-sys-tab'));
                        next.focus();
                    }
                });
            }

            var searchInput = document.getElementById('fc-system-products-search');
            if (searchInput) {
                searchInput.value = searchQuery;
                searchInput.addEventListener('input', function () {
                    searchQuery = searchInput.value.trim();
                    refreshClearSearchButton();
                    refreshResults({ immediateUrlSync: false });
                });
            }

            var clearBtn = document.getElementById('fc-system-products-clear-search');
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    clearSearch();
                    refreshResults({ immediateUrlSync: true });
                });
            }
        }

        function prefetchOtherTabs() {
            TABS.forEach(function (tab) {
                if (tabCache[tab]) {
                    return;
                }
                fetch(apiUrl(tab), {
                    method: 'GET',
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (data) {
                        if (data && data.ok) {
                            tabCache[tab] = data;
                            updateTabCounts();
                        }
                    })
                    .catch(function () {
                        /* ignore prefetch errors */
                    });
            });
        }

        return {
            html: shell,
            init: function () {
                bindControls();
                refreshResults({ syncUrl: false });
                updateTabCounts();
                prefetchOtherTabs();
            },
            destroy: function () {
                if (filterUrlSyncTimer) {
                    window.clearTimeout(filterUrlSyncTimer);
                    filterUrlSyncTimer = null;
                }
                destroyBottomHorizontalScroll();
                destroyGalleryModal();
            }
        };
    }

    function loadSystemProducts(container) {
        if (!container) {
            return Promise.resolve();
        }

        if (container.querySelector('[data-fc-system-products-server]')) {
            return hydrateFromServer(container);
        }

        if (typeof global.fcAdminUrl === 'function') {
            global.location.href = global.fcAdminUrl('products/system-products');
            return Promise.resolve();
        }

        var urlFilters = readFiltersFromUrl();
        container.innerHTML = renderLoading();

        return fetch(apiUrl(urlFilters.source), {
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
                    throw new Error(data.error || 'Failed to load store products');
                }
                var page = renderPage(data);
                container.innerHTML = page.html;
                container._fcSysDestroy = page.destroy;
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
        var el = document.getElementById('fc-system-products-bootstrap');
        if (!el) {
            return null;
        }

        try {
            return JSON.parse(el.textContent || '');
        } catch (e) {
            return null;
        }
    }

    function bindProductDownload() {
        var openTrigger = document.querySelector('[data-fc-products-download-open]');
        var modal = document.querySelector('[data-fc-products-download-modal]');
        if (!openTrigger || !modal || modal.dataset.fcDownloadBound === '1') {
            return;
        }
        modal.dataset.fcDownloadBound = '1';

        var data = readBootstrapData() || {};
        var source = String(data.source || 'GO').toUpperCase();
        var csrf = String(data.csrf || '');
        var dropdown = document.querySelector('[data-fc-products-download-dropdown]');
        var toggle = dropdown ? dropdown.querySelector('[data-fc-products-download-toggle]') : null;
        var panel = dropdown ? dropdown.querySelector('.fc-products-download-dropdown__panel') : null;
        var csvTrigger = dropdown ? dropdown.querySelector('[data-fc-products-download-csv]') : null;
        var importTrigger = dropdown ? dropdown.querySelector('[data-fc-products-import-csv]') : null;
        var importInput = dropdown ? dropdown.querySelector('[data-fc-products-import-input]') : null;
        var dialog = modal.querySelector('.fc-products-download-modal__dialog');
        var startButton = modal.querySelector('[data-fc-products-download-start]');
        var startLabel = startButton ? startButton.querySelector('span') : null;
        var cancelButton = modal.querySelector('[data-fc-products-download-cancel]');
        var cancelLabel = cancelButton ? cancelButton.querySelector('span') : null;
        var closeButtons = modal.querySelectorAll('[data-fc-products-download-close]');
        var progress = modal.querySelector('[data-fc-products-download-progress]');
        var intro = modal.querySelector('[data-fc-products-download-intro]');
        var errorEl = modal.querySelector('[data-fc-products-download-error]');
        var statusEl = modal.querySelector('[data-fc-products-download-status]');
        var percentEl = modal.querySelector('[data-fc-products-download-percent]');
        var trackEl = modal.querySelector('[data-fc-products-download-track]');
        var barEl = modal.querySelector('[data-fc-products-download-bar]');
        var sourceEl = modal.querySelector('[data-fc-products-download-source]');
        var countEl = modal.querySelector('[data-fc-products-download-count]');
        var pageEl = modal.querySelector('[data-fc-products-download-page]');
        var workingEl = modal.querySelector('[data-fc-products-download-working]');
        var finalEl = modal.querySelector('[data-fc-products-download-final]');
        var elapsedEl = modal.querySelector('[data-fc-products-download-elapsed]');
        var messageEl = modal.querySelector('[data-fc-products-download-message]');
        var running = false;
        var complete = false;
        var completedThisRun = false;
        var currentJob = null;
        var previousFocus = null;

        function closeDownloadMenu() {
            if (!dropdown || !toggle || !panel) {
                return;
            }
            panel.hidden = true;
            panel.style.top = '';
            panel.style.left = '';
            panel.style.right = '';
            panel.style.minWidth = '';
            toggle.setAttribute('aria-expanded', 'false');
            dropdown.classList.remove('is-open');
        }

        function positionDownloadMenu() {
            if (!toggle || !panel || panel.hidden) {
                return;
            }
            var rect = toggle.getBoundingClientRect();
            var gap = 6;
            var minWidth = Math.max(rect.width, 200);
            panel.style.minWidth = minWidth + 'px';
            panel.style.right = 'auto';
            // Measure after showing so we can flip if needed.
            panel.style.top = (rect.bottom + gap) + 'px';
            panel.style.left = Math.max(8, rect.right - minWidth) + 'px';

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

        function openDownloadMenu() {
            if (!dropdown || !toggle || !panel) {
                return;
            }
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            dropdown.classList.add('is-open');
            positionDownloadMenu();
        }

        function csvDownloadUrl() {
            return fcApiUrl(
                'products',
                'action=download-products-csv&source=' + encodeURIComponent(source)
            );
        }

        function downloadCsvFile() {
            if (csvTrigger && (csvTrigger.disabled || csvTrigger.getAttribute('aria-disabled') === 'true')) {
                return;
            }
            closeDownloadMenu();
            var link = document.createElement('a');
            link.href = csvDownloadUrl();
            link.download = csvTrigger
                ? String(csvTrigger.getAttribute('data-fc-products-csv-name') || ('wc-products-' + source + '.csv'))
                : ('wc-products-' + source + '.csv');
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }

        function toast(kind, message) {
            var T = global.FcAdminToast;
            if (!T) {
                return;
            }
            if (kind === 'saving' && typeof T.loading === 'function') {
                T.loading(message, 'fc-products-catalogue');
                return;
            }
            if (kind === 'success' && typeof T.success === 'function') {
                T.success(message, { id: 'fc-products-catalogue', duration: 4500 });
                return;
            }
            if (kind === 'error' && typeof T.error === 'function') {
                T.error(message, { id: 'fc-products-catalogue', duration: 5000 });
            }
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

            closeDownloadMenu();
            toast('saving', 'Importing CSV…');

            var formData = new FormData();
            formData.append('source', source);
            formData.append('csrf', csrf);
            formData.append('file', file);

            fetch(fcApiUrl('products', 'action=import-products-csv'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: formData
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, error: 'Invalid server response.' };
                    }).then(function (body) {
                        if (!response.ok || !body.ok) {
                            throw new Error((body && body.error) || 'Could not import CSV.');
                        }
                        return body;
                    });
                })
                .then(function (body) {
                    toast('success', body.message || 'CSV imported successfully.');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 600);
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
                    openDownloadMenu();
                } else {
                    closeDownloadMenu();
                }
            });
            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });
            document.addEventListener('click', function () {
                closeDownloadMenu();
            });
            window.addEventListener('resize', function () {
                if (!panel.hidden) {
                    positionDownloadMenu();
                }
            });
            window.addEventListener('scroll', function () {
                if (!panel.hidden) {
                    positionDownloadMenu();
                }
            }, true);
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
                closeDownloadMenu();
                importInput.click();
            });
            importInput.addEventListener('change', function () {
                var file = importInput.files && importInput.files[0] ? importInput.files[0] : null;
                importCsvFile(file);
            });
        }

        function endpoint(action, query) {
            var suffix = 'action=' + encodeURIComponent(action);
            if (query) {
                suffix += '&' + query;
            }
            return fcApiUrl('products', suffix);
        }

        function request(action, payload) {
            return fetch(endpoint(action), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            }).then(function (response) {
                return response.json().catch(function () {
                    return { ok: false, error: 'Invalid server response.' };
                }).then(function (body) {
                    if (!response.ok || !body.ok) {
                        var error = new Error(body.error || 'Product download failed.');
                        error.job = body.job || null;
                        throw error;
                    }
                    return body;
                });
            });
        }

        function number(value) {
            return Number(value || 0).toLocaleString();
        }

        function paint(job) {
            if (!job) {
                return;
            }
            currentJob = job;
            var percent = Math.max(0, Math.min(100, Number(job.percent || 0)));
            var processed = Number(job.processed || 0);
            var total = job.total == null ? null : Number(job.total);
            var page = Number(job.page || 0);
            var totalPages = job.totalPages == null ? null : Number(job.totalPages);
            var status = String(job.status || 'idle');

            if (progress) {
                progress.hidden = status === 'idle';
            }
            if (intro) {
                intro.hidden = status !== 'idle';
            }
            if (statusEl) {
                statusEl.textContent = status === 'complete'
                    ? 'Download complete'
                    : (status === 'failed'
                        ? 'Download failed'
                        : (status === 'cancelled'
                            ? 'Download cancelled'
                            : 'Downloading ' + source + ' products'));
            }
            if (percentEl) {
                percentEl.textContent = percent + '%';
            }
            if (trackEl) {
                trackEl.setAttribute('aria-valuenow', String(percent));
            }
            if (barEl) {
                barEl.style.width = percent + '%';
            }
            if (sourceEl) {
                sourceEl.textContent = job.store || source;
            }
            if (countEl) {
                countEl.textContent = number(processed)
                    + (total === null ? ' products' : ' of ' + number(total) + ' products');
            }
            if (pageEl) {
                pageEl.textContent = page > 0
                    ? String(page) + (totalPages === null ? '' : ' of ' + totalPages)
                    : 'Waiting';
            }
            if (workingEl) {
                workingEl.textContent = job.workingFile || 'wc-products-' + source + '-downloading.csv';
            }
            if (finalEl) {
                finalEl.textContent = job.finalFile || 'wc-products-' + source + '.csv';
            }
            if (elapsedEl) {
                elapsedEl.textContent = number(job.elapsedSeconds || 0) + 's';
            }
            if (messageEl) {
                messageEl.textContent = job.message || '';
            }

            complete = status === 'complete';
            if (startLabel && !running) {
                startLabel.textContent = completedThisRun ? 'Reload products' : 'Start download';
            }
            if (startButton && !running) {
                startButton.disabled = false;
                startButton.hidden = false;
            }
            if (cancelButton && !running) {
                cancelButton.hidden = status !== 'running';
                cancelButton.disabled = false;
            }
        }

        function resetStartButton() {
            if (startButton) {
                startButton.disabled = false;
                startButton.hidden = false;
            }
            if (startLabel) {
                startLabel.textContent = completedThisRun ? 'Reload products' : 'Start download';
            }
            if (cancelButton) {
                cancelButton.hidden = true;
                cancelButton.disabled = false;
            }
            closeButtons.forEach(function (button) {
                button.disabled = false;
            });
        }

        function showError(message, job) {
            running = false;
            completedThisRun = false;
            if (job) {
                paint(job);
            }
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.textContent = message;
            }
            resetStartButton();
        }

        function runNextStep() {
            if (!running || !currentJob || !currentJob.id) {
                return;
            }
            request('download-products-step', {
                source: source,
                jobId: currentJob.id,
                csrf: csrf
            }).then(function (body) {
                if (!running) {
                    return;
                }
                paint(body.job);
                if (body.job && body.job.status === 'complete') {
                    running = false;
                    complete = true;
                    completedThisRun = true;
                    resetStartButton();
                    return;
                }
                window.setTimeout(runNextStep, 150);
            }).catch(function (error) {
                showError(error.message || 'Product download failed.', error.job);
            });
        }

        function begin(job) {
            currentJob = job;
            running = true;
            complete = false;
            completedThisRun = false;
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = '';
            }
            if (startButton) {
                startButton.disabled = true;
                startButton.hidden = false;
            }
            if (startLabel) {
                startLabel.textContent = 'Downloading…';
            }
            if (cancelButton) {
                cancelButton.hidden = false;
                cancelButton.disabled = false;
            }
            closeButtons.forEach(function (button) {
                button.disabled = true;
            });
            paint(job);
            runNextStep();
        }

        function loadStatus() {
            return fetch(endpoint(
                'download-products-status',
                'source=' + encodeURIComponent(source)
            ), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (body) {
                if (body && body.ok && body.job) {
                    paint(body.job);
                }
            }).catch(function () {
                // The modal remains usable even when no previous job status exists.
            });
        }

        function openModal() {
            closeDownloadMenu();
            previousFocus = document.activeElement;
            modal.hidden = false;
            resetStartButton();
            document.documentElement.classList.add('fc-admin-scroll-lock');
            var adminMain = document.getElementById('fc-admin-main');
            if (adminMain) {
                adminMain.classList.add('fc-admin-scroll-lock');
            }
            window.requestAnimationFrame(function () {
                if (dialog) {
                    dialog.focus();
                }
            });
            loadStatus();
        }

        function closeModal() {
            if (running) {
                return;
            }
            modal.hidden = true;
            document.documentElement.classList.remove('fc-admin-scroll-lock');
            var adminMain = document.getElementById('fc-admin-main');
            if (adminMain) {
                adminMain.classList.remove('fc-admin-scroll-lock');
            }
            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus();
            }
        }

        openTrigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openModal();
        });
        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                if (!currentJob || !currentJob.id) {
                    return;
                }
                running = false;
                completedThisRun = false;
                cancelButton.disabled = true;
                if (cancelLabel) {
                    cancelLabel.textContent = 'Cancelling…';
                }
                request('download-products-cancel', {
                    source: source,
                    jobId: currentJob.id,
                    csrf: csrf
                }).then(function (body) {
                    paint(body.job);
                    resetStartButton();
                    if (cancelLabel) {
                        cancelLabel.textContent = 'Cancel download';
                    }
                }).catch(function (error) {
                    showError(error.message || 'Unable to cancel the product download.', error.job);
                    if (cancelLabel) {
                        cancelLabel.textContent = 'Cancel download';
                    }
                });
            });
        }
        if (startButton) {
            resetStartButton();
            startButton.addEventListener('click', function () {
                if (running) {
                    return;
                }
                if (completedThisRun) {
                    window.location.reload();
                    return;
                }
                // Start a fresh export unless this run has just completed.
                request('download-products-start', {
                    source: source,
                    csrf: csrf
                }).then(function (body) {
                    begin(body.job);
                }).catch(function (error) {
                    showError(error.message || 'Unable to start the product download.', error.job);
                });
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (!modal.hidden) {
                    closeModal();
                    return;
                }
                closeDownloadMenu();
            }
        });
        window.addEventListener('beforeunload', function (event) {
            if (!running) {
                return;
            }
            event.preventDefault();
            event.returnValue = '';
        });
    }

    function bindPhpRenderedPage(container) {
        var wrap = document.getElementById('fc-system-products-table-wrap');
        var bottomScrollSync = null;

        function destroyBottomHorizontalScroll() {
            if (!bottomScrollSync) {
                return;
            }
            bottomScrollSync.scrollEl.removeEventListener('scroll', bottomScrollSync.onScrollMain);
            bottomScrollSync.barEl.removeEventListener('scroll', bottomScrollSync.onScrollBar);
            window.removeEventListener('resize', bottomScrollSync.onResize);
            bottomScrollSync = null;
        }

        function initBottomHorizontalScroll(root) {
            var scrollEl = root.querySelector('.fc-system-products-scroll');
            var barEl = root.querySelector('.fc-sp-bottom-scrollbar');
            var spacer = root.querySelector('.fc-sp-bottom-scrollbar-spacer');
            if (!scrollEl || !barEl || !spacer) {
                return;
            }

            var syncing = false;

            function updateSpacer() {
                spacer.style.width = Math.max(scrollEl.scrollWidth, 1) + 'px';
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

        if (wrap && !wrap.dataset.fcSysGalleryBound) {
            wrap.dataset.fcSysGalleryBound = '1';
            wrap.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-fc-sys-images]');
                if (!btn) {
                    return;
                }
                e.preventDefault();
                var raw = btn.getAttribute('data-fc-sys-images');
                var urls = [];
                try {
                    urls = JSON.parse(raw || '[]');
                } catch (err) {
                    urls = [];
                }
                if (!Array.isArray(urls) || !urls.length) {
                    return;
                }
                openGalleryModal(
                    urls,
                    btn.getAttribute('data-fc-sys-images-title') || 'Product images',
                    0
                );
            });
        }

        if (wrap && wrap.querySelector('.fc-sp-table-layout')) {
            initBottomHorizontalScroll(wrap);
        }
        bindProductDownload();

        container._fcSysDestroy = function () {
            destroyBottomHorizontalScroll();
            destroyGalleryModal();
        };

        container.removeAttribute('aria-busy');
        window.requestAnimationFrame(function () {
            window.dispatchEvent(new Event('resize'));
        });

        return Promise.resolve(true);
    }

    function hydrateFromServer(container) {
        if (!container || !container.querySelector('[data-fc-system-products-server]')) {
            return Promise.resolve(false);
        }

        var data = readBootstrapData() || {};
        if (data.phpRendered || data.deferLoad === false) {
            return bindPhpRenderedPage(container);
        }

        var urlFilters = readFiltersFromUrl();
        var source = data.source || urlFilters.source || 'GO';
        var wrap = document.getElementById('fc-system-products-table-wrap');

        if (wrap) {
            wrap.innerHTML = renderLoading();
        }

        return fetch(apiUrl(source), {
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
                    throw new Error(body.error || 'Failed to load store products');
                }

                if (data.tabTotals && typeof data.tabTotals === 'object') {
                    TABS.forEach(function (tab) {
                        if (typeof data.tabTotals[tab] === 'number') {
                            body['__seedTotal_' + tab] = data.tabTotals[tab];
                        }
                    });
                }

                var page = renderPage(body);
                container.innerHTML = page.html;
                container._fcSysDestroy = page.destroy;
                page.init();

                if (data.tabTotals && typeof data.tabTotals === 'object') {
                    TABS.forEach(function (tab) {
                        var el = document.querySelector('[data-fc-sys-tab-count="' + tab + '"]');
                        if (el && typeof data.tabTotals[tab] === 'number') {
                            var count = Number(data.tabTotals[tab] || 0);
                            el.textContent = String(count);
                            if (count > 0) {
                                el.hidden = false;
                                el.removeAttribute('hidden');
                            } else {
                                el.hidden = true;
                                el.setAttribute('hidden', '');
                            }
                        }
                    });
                }

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

    global.FcAdminSystemProducts = {
        load: loadSystemProducts,
        hydrateFromServer: hydrateFromServer
    };
})(window);
