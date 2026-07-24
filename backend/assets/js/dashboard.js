/**
 * FC Admin — Dashboard charts and lazy loading.
 * Requires assets/js/vendor/chart.umd.min.js (loaded before this script).
 */
(function () {
    'use strict';

    var charts = {};
    var chartDataCache = null;
    var currentDateFilter = { period: '', from: '', to: '' };
    var chartRoot = null;
    /** @type {Object.<string, boolean>} */
    var excludedStates = {};
    /** @type {Object.<string, string>} */
    var stateColorMap = {};

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getRoot(el) {
        if (el) {
            var fromEl = el.closest('.fc-dashboard-page[data-fc-dashboard-server]')
                || el.closest('[data-fc-dashboard-api]');
            if (fromEl) {
                return fromEl;
            }
        }
        return document.querySelector('.fc-dashboard-page[data-fc-dashboard-server]')
            || document.querySelector('[data-fc-dashboard-api]')
            || document.querySelector('[data-fc-dashboard-server]');
    }

    function apiBase(root) {
        return root.getAttribute('data-fc-dashboard-api') || 'api.php?module=dashboard';
    }

    function chartUrl(base, action, params) {
        var url = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'action=' + encodeURIComponent(action);
        if (params) {
            Object.keys(params).forEach(function (key) {
                url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            });
        }
        return url;
    }

    function ensureChartJs() {
        if (!window.Chart) {
            return Promise.reject(new Error('Chart.js is not loaded. Include assets/js/vendor/chart.umd.min.js before dashboard.js.'));
        }
        return Promise.resolve(window.Chart);
    }

    function isDarkTheme() {
        return document.documentElement.getAttribute('data-fc-admin-theme') === 'dark';
    }

    function chartTheme() {
        var dark = isDarkTheme();
        return {
            text: dark ? '#94a3b8' : '#64748b',
            heading: dark ? '#e2e8f0' : '#334155',
            grid: dark ? 'rgba(71, 85, 105, 0.35)' : 'rgba(226, 232, 240, 0.9)',
            gridLight: dark ? 'rgba(71, 85, 105, 0.18)' : 'rgba(241, 245, 249, 0.95)',
            tooltipBg: dark ? 'rgba(15, 23, 42, 0.96)' : 'rgba(255, 255, 255, 0.98)',
            tooltipBorder: dark ? 'rgba(51, 65, 85, 0.9)' : 'rgba(226, 232, 240, 0.95)',
            tooltipText: dark ? '#e2e8f0' : '#0f172a',
            font: {
                family: "'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif",
                size: 11,
            },
            palette: ['#6366f1', '#06b6d4', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#14b8a6', '#f97316'],
            paletteSoft: [
                'rgba(99, 102, 241, 0.88)',
                'rgba(6, 182, 212, 0.88)',
                'rgba(16, 185, 129, 0.88)',
                'rgba(139, 92, 246, 0.88)',
                'rgba(245, 158, 11, 0.88)',
                'rgba(236, 72, 153, 0.88)',
                'rgba(20, 184, 166, 0.88)',
                'rgba(249, 115, 22, 0.88)',
            ],
        };
    }

    function formatNumber(value) {
        var n = Number(value);
        if (!isFinite(n)) {
            n = 0;
        }
        n = Math.round(n);
        return n.toLocaleString('en-US');
    }

    function sumValues(rows, key) {
        return (rows || []).reduce(function (total, row) {
            return total + (Number(row[key]) || 0);
        }, 0);
    }

    function sortByCount(rows) {
        return (rows || []).slice().sort(function (a, b) {
            return (b.count || 0) - (a.count || 0);
        });
    }

    function paletteColor(theme, index, alpha) {
        var base = theme.palette[index % theme.palette.length];
        if (!alpha || alpha >= 1) {
            return base;
        }
        if (base.indexOf('rgba') === 0) {
            return base;
        }
        var hex = base.replace('#', '');
        var r = parseInt(hex.slice(0, 2), 16);
        var g = parseInt(hex.slice(2, 4), 16);
        var b = parseInt(hex.slice(4, 6), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    function intensityColors(values, theme, baseIndex) {
        var max = Math.max.apply(null, values.concat([1]));
        return values.map(function (value, index) {
            var ratio = 0.35 + ((Number(value) || 0) / max) * 0.65;
            return paletteColor(theme, (baseIndex || 0) + index, ratio);
        });
    }

    function baseChartOptions(theme) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 200,
            animation: {
                duration: 650,
                easing: 'easeOutQuart',
            },
            interaction: { mode: 'nearest', intersect: false, axis: 'x' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: theme.tooltipBg,
                    titleColor: theme.tooltipText,
                    bodyColor: theme.tooltipText,
                    footerColor: theme.text,
                    borderColor: theme.tooltipBorder,
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: true,
                    boxPadding: 5,
                    titleFont: { size: 12, weight: '600', family: theme.font.family },
                    bodyFont: { size: 11, family: theme.font.family },
                    callbacks: {
                        label: function (ctx) {
                            var label = ctx.dataset.label ? ctx.dataset.label + ': ' : '';
                            var raw = ctx.parsed.y != null && ctx.chart.config.type === 'line'
                                ? ctx.parsed.y
                                : (ctx.parsed.x != null ? ctx.parsed.x : ctx.parsed.y);
                            return label + formatNumber(raw);
                        },
                    },
                },
            },
        };
    }

    function cartesianScales(theme, horizontal) {
        var category = {
            ticks: {
                color: theme.text,
                font: theme.font,
                maxRotation: horizontal ? 0 : 0,
                autoSkip: true,
                maxTicksLimit: horizontal ? 12 : 8,
            },
            grid: { display: false },
            border: { display: false },
        };
        var value = {
            ticks: {
                color: theme.text,
                precision: 0,
                font: theme.font,
                callback: function (value) { return formatNumber(value); },
            },
            grid: { color: theme.gridLight, drawTicks: false },
            border: { display: false },
            beginAtZero: true,
        };
        return horizontal
            ? { x: value, y: category }
            : { x: category, y: value };
    }

    function lineGradient(ctx, chartArea, color) {
        if (!chartArea) {
            return 'rgba(99, 102, 241, 0.12)';
        }
        var gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        var rgb = color || '99, 102, 241';
        gradient.addColorStop(0, 'rgba(' + rgb + ', 0.28)');
        gradient.addColorStop(1, 'rgba(' + rgb + ', 0.02)');
        return gradient;
    }

    function renderVizSummary(container, items) {
        if (!container) {
            return;
        }
        if (!items || !items.length) {
            container.hidden = true;
            container.innerHTML = '';
            return;
        }
        container.hidden = false;
        container.innerHTML = items.map(function (item) {
            return (
                '<div class="fc-dashboard-viz-summary__item">' +
                '<span class="fc-dashboard-viz-summary__label">' + escapeHtml(item.label) + '</span>' +
                '<strong class="fc-dashboard-viz-summary__value">' + escapeHtml(item.value) + '</strong>' +
                (item.hint ? '<span class="fc-dashboard-viz-summary__hint">' + escapeHtml(item.hint) + '</span>' : '') +
                '</div>'
            );
        }).join('');
    }

    function setChartEmpty(name, show) {
        document.querySelectorAll('[data-fc-dashboard-empty="' + name + '"]').forEach(function (el) {
            el.hidden = !show;
        });
    }

    function setRankedChartHeight(host, count) {
        if (!host) {
            return;
        }
        var rows = Math.max(3, Math.min(8, count || 3));
        host.style.height = (rows * 2.15 + 1.5) + 'rem';
    }

    function hideSkeleton(name) {
        document.querySelectorAll('[data-fc-dashboard-skeleton="' + name + '"]').forEach(function (el) {
            el.hidden = true;
        });
    }

    function destroyAllCharts() {
        Object.keys(charts).forEach(function (id) {
            if (charts[id]) {
                charts[id].destroy();
            }
        });
        charts = {};
    }

    function makeChart(canvasId, config) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) {
            return null;
        }
        if (charts[canvasId]) {
            charts[canvasId].destroy();
            delete charts[canvasId];
        }
        charts[canvasId] = new window.Chart(canvas, config);
        return charts[canvasId];
    }

    function renderTopList(container, items, options) {
        if (!container) {
            return;
        }
        renderRankedBars(container, items, options || { limit: 6, accent: 'violet' });
    }

    function formatPersonName(value) {
        var raw = String(value || '').trim().replace(/\s+/g, ' ');
        if (!raw || raw.indexOf('@') >= 0) {
            return raw;
        }
        return raw.replace(/\S+/g, function (word) {
            if (!/[a-zA-Z]/.test(word)) {
                return word;
            }
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        });
    }

    function dateFilterQueryParams(filter) {
        var active = filter || currentDateFilter || { period: '', from: '', to: '' };
        var params = {};
        var period = String(active.period || '').trim();
        if (!period) {
            return params;
        }
        params.date_period = period;
        if (period === 'custom') {
            if (active.from) {
                params.date_from = String(active.from);
            }
            if (active.to) {
                params.date_to = String(active.to);
            }
        }
        return params;
    }

    function buildPlannerEntriesUrl(extraParams) {
        var params = Object.assign({}, dateFilterQueryParams(), extraParams || {});
        var keys = Object.keys(params).filter(function (key) {
            return params[key] !== '' && params[key] != null;
        });
        var query = keys.map(function (key) {
            return encodeURIComponent(key) + '=' + encodeURIComponent(String(params[key]));
        }).join('&');
        var path = 'planner-entries' + (query ? '?' + query : '');

        if (typeof window.fcAdminUrl === 'function') {
            return window.fcAdminUrl(path);
        }

        var root = document.querySelector('[data-fc-dashboard-entries]');
        var base = root ? root.getAttribute('data-fc-dashboard-entries') : '';
        if (!base) {
            return path;
        }
        return String(base).replace(/\/+$/, '') + (query ? '?' + query : '');
    }

    function adminPlannerEntryUrl(entryId) {
        if (!entryId) {
            return '#';
        }
        if (typeof window.fcAdminUrl === 'function') {
            return window.fcAdminUrl('planner-entries/' + encodeURIComponent(String(entryId)));
        }
        var root = document.querySelector('[data-fc-dashboard-entries]');
        var base = root ? root.getAttribute('data-fc-dashboard-entries') : '';
        if (!base) {
            return '#';
        }
        return String(base).replace(/\/+$/, '') + '/' + encodeURIComponent(String(entryId));
    }

    function adminPlannerEntriesSearchUrl(query) {
        var q = String(query || '').trim();
        return buildPlannerEntriesUrl(q ? { q: q } : {});
    }

    function syncEntriesAllLinks(root) {
        var scope = root || document;
        var href = buildPlannerEntriesUrl();
        var queryIndex = href.indexOf('?');
        var route = 'planner-entries' + (queryIndex >= 0 ? href.slice(queryIndex) : '');
        scope.querySelectorAll('[data-fc-dashboard-entries-all-link]').forEach(function (link) {
            link.setAttribute('href', href);
            link.setAttribute('data-route', route);
        });
    }

    function bindEntryLinks(root) {
        if (!root || root.getAttribute('data-fc-dashboard-links-bound') === '1') {
            return;
        }
        root.setAttribute('data-fc-dashboard-links-bound', '1');
        root.addEventListener('click', function (e) {
            var link = e.target.closest('[data-fc-dashboard-entry-link]');
            if (!link) {
                return;
            }
            var route = link.getAttribute('data-route');
            if (!route) {
                return;
            }
            e.preventDefault();
            if (typeof window.fcAdminNavigate === 'function') {
                window.fcAdminNavigate(route);
                return;
            }
            var href = link.getAttribute('href');
            if (href && href !== '#') {
                window.location.href = href;
            }
        });
    }

    function chunkArray(items, size) {
        var chunks = [];
        var chunkSize = Math.max(1, size || 1);
        for (var i = 0; i < items.length; i += chunkSize) {
            chunks.push(items.slice(i, i + chunkSize));
        }
        return chunks;
    }

    function sliderItemsPerSlide() {
        if (window.innerWidth >= 1100) {
            return 4;
        }
        if (window.innerWidth >= 720) {
            return 2;
        }
        return 1;
    }

    function initInfiniteSlider(root) {
        if (!root || root.getAttribute('data-fc-slider-bound') === '1') {
            return;
        }
        root.setAttribute('data-fc-slider-bound', '1');

        var viewport = root.querySelector('.fc-dashboard-card-slider__viewport');
        var track = root.querySelector('.fc-dashboard-card-slider__track');
        var prevBtn = root.querySelector('[data-fc-slider-prev]');
        var nextBtn = root.querySelector('[data-fc-slider-next]');
        if (!viewport || !track || !prevBtn || !nextBtn) {
            return;
        }

        var slides = Array.from(track.querySelectorAll('.fc-dashboard-card-slider__slide'));
        if (!slides.length) {
            return;
        }

        var firstClone = slides[0].cloneNode(true);
        var lastClone = slides[slides.length - 1].cloneNode(true);
        firstClone.setAttribute('data-fc-slider-clone', '1');
        lastClone.setAttribute('data-fc-slider-clone', '1');
        track.insertBefore(lastClone, slides[0]);
        track.appendChild(firstClone);

        var index = 1;
        var animating = false;

        function slideCount() {
            return track.querySelectorAll('.fc-dashboard-card-slider__slide').length;
        }

        function setPosition(animate) {
            track.style.transition = animate ? 'transform 0.42s cubic-bezier(0.4, 0, 0.2, 1)' : 'none';
            track.style.transform = 'translate3d(' + (-index * 100) + '%, 0, 0)';
        }

        function go(delta) {
            if (animating) {
                return;
            }
            animating = true;
            index += delta;
            setPosition(true);
        }

        track.addEventListener('transitionend', function (event) {
            if (event.propertyName !== 'transform') {
                return;
            }
            var total = slideCount();
            if (index <= 0) {
                index = total - 2;
                setPosition(false);
            } else if (index >= total - 1) {
                index = 1;
                setPosition(false);
            }
            animating = false;
        });

        prevBtn.addEventListener('click', function () {
            go(-1);
        });
        nextBtn.addEventListener('click', function () {
            go(1);
        });

        setPosition(false);
    }

    function mountCardSlider(container, cardHtmlList) {
        if (!container) {
            return;
        }
        if (!cardHtmlList || !cardHtmlList.length) {
            return false;
        }

        var perSlide = sliderItemsPerSlide();
        var slides = chunkArray(cardHtmlList, perSlide);
        var slidesHtml = slides.map(function (chunk) {
            return '<div class="fc-dashboard-card-slider__slide">' + chunk.join('') + '</div>';
        }).join('');

        container.innerHTML =
            '<div class="fc-dashboard-card-slider" data-fc-dashboard-card-slider>' +
            '<button type="button" class="fc-dashboard-card-slider__arrow fc-dashboard-card-slider__arrow--prev" data-fc-slider-prev aria-label="Previous slide">' +
            '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>' +
            '<div class="fc-dashboard-card-slider__viewport">' +
            '<div class="fc-dashboard-card-slider__track">' + slidesHtml + '</div>' +
            '</div>' +
            '<button type="button" class="fc-dashboard-card-slider__arrow fc-dashboard-card-slider__arrow--next" data-fc-slider-next aria-label="Next slide">' +
            '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>' +
            '</div>';

        initInfiniteSlider(container.querySelector('[data-fc-dashboard-card-slider]'));
        return true;
    }

    function renderCustomerList(container, items, options) {
        if (!container) {
            return;
        }
        var limit = (options && options.limit) || 16;
        var rows = (items || []).slice(0, limit);
        if (!rows.length) {
            container.innerHTML =
                '<div class="fc-dashboard-customers-empty">' +
                '<span class="fc-dashboard-customers-empty__icon" aria-hidden="true"><i class="fa-solid fa-users-slash"></i></span>' +
                '<p class="fc-dashboard-customers-empty__title">No customers yet</p>' +
                '<p class="fc-dashboard-customers-empty__text">Planner submissions with an email address will appear here.</p>' +
                '</div>';
            return;
        }

        var cardsHtml = rows.map(function (customer) {
            var name = formatPersonName(String(customer.name || customer.email || '—').trim()) || '—';
            var email = String(customer.email || '').trim();
            var mobile = String(customer.mobile || '').trim();
            var state = String(customer.state || '').trim().toUpperCase();
            var count = customer.count || 0;
            var lastSeen = formatEntryDate(customer.last_seen);

            var contactBits = [];
            if (mobile) {
                contactBits.push(
                    '<a class="fc-dashboard-entry-card__contact" href="tel:' + escapeHtml(mobile.replace(/\s+/g, '')) + '">' +
                    '<i class="fa-solid fa-phone" aria-hidden="true"></i><span>' + escapeHtml(mobile) + '</span></a>'
                );
            }
            if (state) {
                contactBits.push(
                    '<span class="fc-dashboard-entry-card__contact">' +
                    '<i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>' + escapeHtml(state) + '</span></span>'
                );
            }

            return (
                '<article class="fc-dashboard-entry-card">' +
                '<header class="fc-dashboard-entry-card__header">' +
                '<div class="fc-dashboard-entry-card__identity">' +
                '<h4 class="fc-dashboard-entry-card__name" title="' + escapeHtml(name) + '">' + escapeHtml(name) + '</h4>' +
                (email
                    ? '<a class="fc-dashboard-entry-card__email" href="mailto:' + escapeHtml(email) + '" title="' + escapeHtml(email) + '">' + escapeHtml(email) + '</a>'
                    : '') +
                '</div>' +
                '</header>' +
                '<div class="fc-dashboard-entry-card__body">' +
                (contactBits.length
                    ? '<div class="fc-dashboard-entry-card__contacts">' + contactBits.join('') + '</div>'
                    : '<p class="fc-dashboard-entry-card__empty-contact">No contact details</p>') +
                '<p class="fc-dashboard-entry-card__metric" title="Planner submissions">' +
                '<strong>' + escapeHtml(formatNumber(count)) + '</strong>' +
                '<span>' + (count === 1 ? 'planner' : 'planners') + '</span>' +
                '</p>' +
                (lastSeen && lastSeen !== '—'
                    ? '<p class="fc-dashboard-entry-card__time"><i class="fa-regular fa-clock" aria-hidden="true"></i>' + escapeHtml(lastSeen) + '</p>'
                    : '') +
                '</div>' +
                (email
                    ? '<a class="fc-dashboard-entry-card__footer" href="' + escapeHtml(adminPlannerEntriesSearchUrl(email)) + '" data-nav-full="1">' +
                      '<span class="fc-dashboard-entry-card__footer-label">View entries</span>' +
                      '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>' +
                      '</a>'
                    : '<div class="fc-dashboard-entry-card__footer fc-dashboard-entry-card__footer--disabled" aria-hidden="true">' +
                      '<span class="fc-dashboard-entry-card__footer-label">View entries</span>' +
                      '</div>') +
                '</article>'
            );
        });

        mountCardSlider(container, cardsHtml);
    }

    function entryDetailUrl(id) {
        return adminPlannerEntryUrl(id);
    }

    function entryStatusClass(status) {
        var key = String(status || '').toLowerCase().trim();
        if (['completed', 'submitted', 'quoted', 'done', 'approved', 'ordered'].indexOf(key) >= 0) {
            return 'fc-dashboard-badge--success';
        }
        if (['planning', 'draft', 'pending'].indexOf(key) >= 0) {
            return 'fc-dashboard-badge--muted';
        }
        if (['cancelled', 'failed', 'rejected'].indexOf(key) >= 0) {
            return 'fc-dashboard-badge--danger';
        }
        return 'fc-dashboard-badge--info';
    }

    function formatEntryDate(value) {
        if (!value) {
            return '—';
        }
        if (typeof window.fcFormatAdminDate === 'function') {
            return window.fcFormatAdminDate(value);
        }
        var normalized = String(value).replace(' ', 'T');
        var date = new Date(normalized);
        if (isNaN(date.getTime())) {
            return String(value);
        }
        return date.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    function renderRecentList(container, items, options) {
        if (!container) {
            return;
        }
        var limit = (options && options.limit) || 16;
        var rows = (items || []).slice(0, limit);
        if (!rows.length) {
            container.innerHTML =
                '<div class="fc-dashboard-customers-empty">' +
                '<span class="fc-dashboard-customers-empty__icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>' +
                '<p class="fc-dashboard-customers-empty__title">No entries yet</p>' +
                '<p class="fc-dashboard-customers-empty__text">Planner submissions will appear here as they are created.</p>' +
                '</div>';
            return;
        }

        var cardsHtml = rows.map(function (entry) {
            var name = (entry.name || '').trim();
            var plannerId = (entry.planner_id || '').trim();
            var displayName = formatPersonName(name) || plannerId || 'Unknown';
            var email = (entry.email || '').trim();
            var mobile = (entry.mobile || '').trim();
            var address = (entry.address || '').trim();
            var state = (entry.state || '').trim();
            var sections = entry.section_count || 0;
            var fenceLabel = (entry.fence_label || '').trim();
            var updatedAt = formatEntryDate(entry.updated_at);
            var detailUrl = entryDetailUrl(entry.id);
            var searchUrl = email ? adminPlannerEntriesSearchUrl(email) : '';
            var footerHref = searchUrl || detailUrl;
            var footerIsSearch = !!searchUrl;
            var route = entry.id ? 'planner-entries/' + entry.id : 'planner-entries';

            var chips = [];
            if (state) {
                chips.push('<span class="fc-dashboard-entry-card__chip"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>' + escapeHtml(state) + '</span>');
            }
            if (fenceLabel) {
                chips.push('<span class="fc-dashboard-entry-card__chip"><i class="fa-solid fa-border-all" aria-hidden="true"></i>' + escapeHtml(fenceLabel) + '</span>');
            }
            chips.push(
                '<span class="fc-dashboard-entry-card__chip">' +
                '<i class="fa-solid fa-layer-group" aria-hidden="true"></i>' +
                formatNumber(sections) + ' ' + (sections === 1 ? 'section' : 'sections') +
                '</span>'
            );

            var contactBits = [];
            if (email) {
                contactBits.push(
                    '<a class="fc-dashboard-entry-card__contact" href="mailto:' + escapeHtml(email) + '" title="' + escapeHtml(email) + '">' +
                    '<i class="fa-regular fa-envelope" aria-hidden="true"></i><span>' + escapeHtml(email) + '</span></a>'
                );
            }
            if (mobile) {
                contactBits.push(
                    '<a class="fc-dashboard-entry-card__contact" href="tel:' + escapeHtml(mobile.replace(/\s+/g, '')) + '">' +
                    '<i class="fa-solid fa-phone" aria-hidden="true"></i><span>' + escapeHtml(mobile) + '</span></a>'
                );
            }
            if (address) {
                contactBits.push(
                    '<span class="fc-dashboard-entry-card__contact fc-dashboard-entry-card__contact--address">' +
                    '<i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>' + escapeHtml(address) + '</span></span>'
                );
            }

            return (
                '<article class="fc-dashboard-entry-card">' +
                '<header class="fc-dashboard-entry-card__header">' +
                '<div class="fc-dashboard-entry-card__identity">' +
                '<h4 class="fc-dashboard-entry-card__name" title="' + escapeHtml(displayName) + '">' + escapeHtml(displayName) + '</h4>' +
                '<p class="fc-dashboard-entry-card__time"><i class="fa-regular fa-clock" aria-hidden="true"></i>' + escapeHtml(updatedAt) + '</p>' +
                '</div>' +
                '</header>' +
                '<div class="fc-dashboard-entry-card__body">' +
                (chips.length
                    ? '<div class="fc-dashboard-entry-card__chips">' + chips.join('') + '</div>'
                    : '') +
                (contactBits.length
                    ? '<div class="fc-dashboard-entry-card__contacts">' + contactBits.join('') + '</div>'
                    : '<p class="fc-dashboard-entry-card__empty-contact">No contact details</p>') +
                '</div>' +
                (footerHref
                    ? '<a class="fc-dashboard-entry-card__footer" href="' + escapeHtml(footerHref) + '"' +
                      (footerIsSearch
                          ? ' data-nav-full="1"'
                          : ' data-fc-dashboard-entry-link data-route="' + escapeHtml(route) + '"') +
                      '>' +
                      '<span class="fc-dashboard-entry-card__footer-label">' + (footerIsSearch ? 'View entries' : 'View entry') + '</span>' +
                      '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>' +
                      '</a>'
                    : '<div class="fc-dashboard-entry-card__footer fc-dashboard-entry-card__footer--disabled" aria-hidden="true">' +
                      '<span class="fc-dashboard-entry-card__footer-label">View entry</span>' +
                      '</div>') +
                '</article>'
            );
        });

        mountCardSlider(container, cardsHtml);
    }

    function renderPopularRankedList(container, items, options) {
        if (!container) {
            return;
        }
        var opts = options || {};
        var limit = opts.limit || 10;
        var rows = sortByCount(items).slice(0, limit);
        var summaryEl = opts.summarySelector
            ? document.querySelector(opts.summarySelector)
            : null;
        var unitSingular = opts.unitSingular || 'item';
        var unitPlural = opts.unitPlural || 'items';
        var topLabel = opts.topLabel || 'Top';
        var shownLabel = opts.shownLabel || 'Shown';
        var totalLabel = opts.totalLabel || 'Total';
        var accent = opts.accent || 'pink';
        var showSwatch = !!opts.showSwatch;
        var listClass = 'fc-dashboard-fence-list'
            + (accent !== 'pink' ? ' fc-dashboard-fence-list--' + accent : '')
            + (showSwatch ? ' has-swatches' : '');

        if (!rows.length) {
            container.innerHTML = '';
            renderVizSummary(summaryEl, []);
            return;
        }

        var total = sumValues(rows, 'count');
        var max = rows[0].count || 1;
        var top = rows[0];

        renderVizSummary(summaryEl, [
            {
                label: topLabel,
                value: top.label || '—',
                hint: formatNumber(top.count || 0) + ' ' + ((top.count || 0) === 1 ? unitSingular : unitPlural),
            },
            { label: shownLabel, value: String(rows.length) },
            { label: totalLabel, value: formatNumber(total) },
        ]);

        container.innerHTML =
            '<ol class="' + listClass + '">' +
            rows.map(function (item, index) {
                var count = item.count || 0;
                var pct = total > 0 ? Math.round((count / total) * 100) : 0;
                var width = Math.max(8, Math.round((count / max) * 100));
                var label = item.label || '—';
                var rankClass = index === 0
                    ? ' is-gold'
                    : (index === 1 ? ' is-silver' : (index === 2 ? ' is-bronze' : ''));
                var imageUrl = String(item.image_url || item.image || '').trim();
                var swatch = String(item.swatch || item.color || '').trim();
                var swatchAsImage = !!opts.swatchAsImage;
                var swatchHtml = '';
                if (showSwatch) {
                    if (swatchAsImage && imageUrl !== '' && imageUrl.indexOf('url(') !== 0 && imageUrl.charAt(0) !== '#') {
                        swatchHtml =
                            '<span class="fc-dashboard-fence-row__swatch fc-dashboard-fence-row__swatch--image" title="' + escapeHtml(label) + '">' +
                            '<img src="' + escapeHtml(imageUrl) + '" alt="" loading="lazy" decoding="async" width="40" height="40">' +
                            '</span>';
                    } else {
                        swatchHtml =
                            '<span class="fc-dashboard-fence-row__swatch" style="background:' + escapeHtml(swatch || '#94a3b8') + ';background-size:cover;background-position:center;" title="' + escapeHtml(label) + '" aria-hidden="true"></span>';
                    }
                }
                var barStyle = 'width:' + width + '%';
                return (
                    '<li class="fc-dashboard-fence-row' + rankClass + '">' +
                    '<span class="fc-dashboard-fence-row__rank" aria-label="Rank ' + (index + 1) + '">' + (index + 1) + '</span>' +
                    swatchHtml +
                    '<div class="fc-dashboard-fence-row__main">' +
                    '<div class="fc-dashboard-fence-row__head">' +
                    '<span class="fc-dashboard-fence-row__name" title="' + escapeHtml(label) + '">' + escapeHtml(label) + '</span>' +
                    '<span class="fc-dashboard-fence-row__count">' +
                    '<strong>' + formatNumber(count) + '</strong>' +
                    '<small>' + (count === 1 ? unitSingular : unitPlural) + '</small>' +
                    '</span>' +
                    '</div>' +
                    '<div class="fc-dashboard-fence-row__track" aria-hidden="true">' +
                    '<span class="fc-dashboard-fence-row__bar" style="' + barStyle + '"></span>' +
                    '</div>' +
                    '</div>' +
                    '<span class="fc-dashboard-fence-row__share" title="Share of listed items">' + pct + '%</span>' +
                    '</li>'
                );
            }).join('') +
            '</ol>';
    }

    function renderFenceStylesList(container, items, options) {
        var opts = options || {};
        renderPopularRankedList(container, items, {
            limit: opts.limit || 10,
            summarySelector: '[data-fc-dashboard-fences-summary]',
            topLabel: 'Top style',
            shownLabel: 'Styles shown',
            totalLabel: 'Sections',
            unitSingular: 'section',
            unitPlural: 'sections',
            accent: 'pink',
            showSwatch: true,
            swatchAsImage: true,
        });
    }

    function renderRankedBars(container, items, options) {
        if (!container) {
            return;
        }
        var opts = options || {};
        var limit = opts.limit || 6;
        var accent = opts.accent || 'indigo';
        var rows = sortByCount(items).slice(0, limit);
        if (!rows.length) {
            container.innerHTML = '<p class="fc-dashboard-empty">No data yet.</p>';
            return;
        }
        var total = sumValues(rows, 'count');
        var max = rows[0].count || 1;
        container.innerHTML =
            '<ul class="fc-dashboard-ranked" data-accent="' + escapeHtml(accent) + '">' +
            rows.map(function (item, index) {
                var count = item.count || 0;
                var pct = total > 0 ? Math.round((count / total) * 100) : 0;
                var width = Math.max(6, Math.round((count / max) * 100));
                var label = item.label || item.email || '—';
                var color = /^#[0-9a-f]{6}$/i.test(String(item.color || ''))
                    ? String(item.color)
                    : '';
                var colorName = String(item.color_name || '').trim();
                var swatch = color
                    ? '<span class="fc-dashboard-ranked__swatch" style="background-color:' + color + '"' +
                        (colorName ? ' title="' + escapeHtml(colorName) + '"' : '') +
                        ' aria-hidden="true"></span>'
                    : '';
                var barStyle = 'width:' + width + '%;' + (color ? 'background:' + color + ';' : '');
                return (
                    '<li class="fc-dashboard-ranked__item">' +
                    '<div class="fc-dashboard-ranked__meta">' +
                    '<span class="fc-dashboard-ranked__rank">' + (index + 1) + '</span>' +
                    swatch +
                    '<span class="fc-dashboard-ranked__label" title="' + escapeHtml(label) + '">' + escapeHtml(label) + '</span>' +
                    '<span class="fc-dashboard-ranked__value">' + formatNumber(count) + '</span>' +
                    '</div>' +
                    '<div class="fc-dashboard-ranked__track" aria-hidden="true">' +
                    '<span class="fc-dashboard-ranked__bar" style="' + barStyle + '"></span>' +
                    '</div>' +
                    '<span class="fc-dashboard-ranked__pct">' + pct + '%</span>' +
                    '</li>'
                );
            }).join('') +
            '</ul>';
    }

    function renderInsights(container, insights) {
        if (!container) {
            return;
        }
        var colours = (insights && insights.colours) || [];
        renderPopularRankedList(container, colours, {
            limit: 10,
            summarySelector: '[data-fc-dashboard-insights-summary]',
            topLabel: 'Top colour',
            shownLabel: 'Colours shown',
            totalLabel: 'Selections',
            unitSingular: 'selection',
            unitPlural: 'selections',
            accent: 'rose',
            showSwatch: true,
        });
    }

    function buildStateColorMap(stateRows, theme) {
        var map = {};
        (stateRows || []).forEach(function (row, index) {
            var code = String(row.label || '').toUpperCase();
            if (!code) {
                return;
            }
            map[code] = paletteColor(theme, index);
        });
        return map;
    }

    function updateAuMap(byState) {
        var map = document.querySelector('[data-fc-dashboard-au-map]');
        if (!map) {
            return;
        }
        var counts = {};
        (byState || []).forEach(function (row) {
            counts[String(row.label || '').toUpperCase()] = row.count || 0;
        });
        var chips = Array.from(map.querySelectorAll('[data-state]'));
        chips.forEach(function (el) {
            var code = String(el.getAttribute('data-state') || '').toUpperCase();
            var count = counts[code] || 0;
            var countEl = el.querySelector('.fc-dashboard-au-map__count') || el.querySelector('strong');
            if (countEl) {
                countEl.textContent = formatNumber(count);
            }
            var disabled = !!excludedStates[code];
            var accent;
            var soft;
            if (stateColorMap[code]) {
                accent = count > 0
                    ? stateColorMap[code]
                    : paletteColorFromHex(stateColorMap[code], 0.45);
                soft = paletteColorFromHex(stateColorMap[code], count > 0 ? 0.14 : 0.06);
            } else {
                accent = isDarkTheme() ? '#64748b' : '#94a3b8';
                soft = isDarkTheme() ? 'rgba(100, 116, 139, 0.12)' : 'rgba(148, 163, 184, 0.12)';
            }
            el.style.setProperty('--fc-au-color', accent);
            el.style.setProperty('--fc-au-color-soft', soft);
            el.classList.toggle('has-data', count > 0);
            el.classList.toggle('is-disabled', disabled);
            el.setAttribute('aria-pressed', disabled ? 'false' : 'true');
            el.setAttribute('aria-label', disabled
                ? 'Show ' + code + ' on chart (' + formatNumber(count) + ' entries)'
                : 'Hide ' + code + ' from chart (' + formatNumber(count) + ' entries)');
            el.setAttribute('title', disabled
                ? 'Show ' + code + ' on chart'
                : (count > 0 ? 'Hide ' + code + ' from chart' : code + ' — no entries'));
            el.setAttribute('data-fc-state-count', String(count));
        });

        chips.sort(function (a, b) {
            var countA = Number(a.getAttribute('data-fc-state-count')) || 0;
            var countB = Number(b.getAttribute('data-fc-state-count')) || 0;
            if (countB !== countA) {
                return countB - countA;
            }
            var codeA = String(a.getAttribute('data-state') || '');
            var codeB = String(b.getAttribute('data-state') || '');
            return codeA.localeCompare(codeB);
        }).forEach(function (el) {
            map.appendChild(el);
        });

        map.removeAttribute('aria-hidden');
    }

    function paletteColorFromHex(hexOrRgba, alpha) {
        if (!hexOrRgba) {
            return paletteColor(chartTheme(), 0, alpha);
        }
        if (String(hexOrRgba).indexOf('rgba') === 0) {
            return hexOrRgba;
        }
        var hex = String(hexOrRgba).replace('#', '');
        if (hex.length !== 6) {
            return hexOrRgba;
        }
        var r = parseInt(hex.slice(0, 2), 16);
        var g = parseInt(hex.slice(2, 4), 16);
        var b = parseInt(hex.slice(4, 6), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + (alpha == null ? 1 : alpha) + ')';
    }

    function renderStatesChart(byState) {
        var theme = chartTheme();
        var base = baseChartOptions(theme);
        var allRows = sortByCount(byState || []).slice(0, 8);
        stateColorMap = buildStateColorMap(allRows, theme);

        var visibleRows = allRows.filter(function (row) {
            var code = String(row.label || '').toUpperCase();
            return !excludedStates[code];
        });
        var stateLabels = visibleRows.map(function (r) { return r.label; });
        var stateValues = visibleRows.map(function (r) { return r.count; });
        var stateColors = visibleRows.map(function (r) {
            var code = String(r.label || '').toUpperCase();
            return stateColorMap[code] || paletteColor(theme, 0);
        });
        var stateTotal = sumValues(visibleRows, 'count');
        var topState = visibleRows[0];

        renderVizSummary(document.querySelector('[data-fc-dashboard-states-summary]'), visibleRows.length ? [
            { label: 'Top state', value: topState ? topState.label : '—', hint: topState ? formatNumber(topState.count) + ' entries' : '' },
            { label: 'States shown', value: String(visibleRows.length) },
            { label: 'Combined', value: formatNumber(stateTotal) },
        ] : []);

        setRankedChartHeight(document.querySelector('[data-fc-dashboard-chart-host="states"]'), visibleRows.length);
        setChartEmpty('states', !visibleRows.length);
        makeChart('fc-dashboard-chart-states', {
            type: 'bar',
            data: {
                labels: stateLabels,
                datasets: [{
                    label: 'Entries',
                    data: stateValues,
                    backgroundColor: stateColors,
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 14,
                }],
            },
            options: Object.assign({}, base, {
                indexAxis: 'y',
                onClick: function (_evt, elements) {
                    if (!elements.length) {
                        return;
                    }
                    var label = stateLabels[elements[0].index];
                    if (!label) {
                        return;
                    }
                    var adminBase = document.body && document.body.getAttribute('data-fc-admin-base');
                    if (adminBase) {
                        window.location.href = buildPlannerEntriesUrl({ state: label });
                    }
                },
                scales: Object.assign({}, cartesianScales(theme, true), {
                    y: Object.assign({}, cartesianScales(theme, true).y, {
                        ticks: Object.assign({}, cartesianScales(theme, true).y.ticks, { autoSkip: false }),
                    }),
                }),
            }),
        });
        hideSkeleton('states');
        updateAuMap(byState);
    }

    function bindStateMapToggles(root) {
        var map = (root || document).querySelector('[data-fc-dashboard-au-map]');
        if (!map || map.getAttribute('data-fc-state-map-bound') === '1') {
            return;
        }
        map.setAttribute('data-fc-state-map-bound', '1');

        function toggleStateChip(el) {
            if (!el) {
                return;
            }
            var code = String(el.getAttribute('data-state') || '').toUpperCase();
            if (!code) {
                return;
            }
            if (excludedStates[code]) {
                delete excludedStates[code];
            } else {
                excludedStates[code] = true;
            }
            if (chartDataCache) {
                renderStatesChart(chartDataCache.by_state || []);
            }
        }

        map.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-state]');
            if (!chip || !map.contains(chip)) {
                return;
            }
            e.preventDefault();
            toggleStateChip(chip);
        });

        map.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            var chip = e.target.closest('[data-state]');
            if (!chip || !map.contains(chip)) {
                return;
            }
            e.preventDefault();
            toggleStateChip(chip);
        });
    }

    function formatTrendLabel(dateStr) {
        if (!dateStr) {
            return '';
        }
        var parts = String(dateStr).split('-');
        if (parts.length !== 3) {
            return dateStr;
        }
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return parseInt(parts[2], 10) + ' ' + (months[parseInt(parts[1], 10) - 1] || parts[1]);
    }

    function syncTrendPeriodLabel() {
        var target = document.querySelector('[data-fc-dashboard-trend-period-label]');
        if (!target) {
            return;
        }
        var source = document.querySelector('[data-fc-dashboard-date-dropdown] [data-fc-entries-date-label]');
        var label = source ? String(source.textContent || '').trim() : '';
        target.textContent = label || 'All dates';
    }

    function renderCharts(data) {
        chartDataCache = data;
        var theme = chartTheme();
        var base = baseChartOptions(theme);

        /* Trend — area line for time series */
        var trendRows = data.trend || [];
        var trendLabels = trendRows.map(function (row) { return formatTrendLabel(row.date); });
        var trendValues = trendRows.map(function (row) { return row.count; });
        var trendTotal = sumValues(trendRows, 'count');
        var trendPeak = sortByCount(trendRows.map(function (row) {
            return { label: formatTrendLabel(row.date), count: row.count };
        }))[0];
        var trendAvg = trendRows.length ? Math.round(trendTotal / trendRows.length) : 0;

        renderVizSummary(document.querySelector('[data-fc-dashboard-trend-summary]'), trendRows.length ? [
            { label: 'Total submissions', value: formatNumber(trendTotal) },
            { label: 'Daily average', value: formatNumber(trendAvg) },
            { label: 'Peak day', value: trendPeak ? formatNumber(trendPeak.count) : '0', hint: trendPeak ? trendPeak.label : '' },
        ] : []);
        syncTrendPeriodLabel();

        setChartEmpty('trend', !trendRows.length);
        makeChart('fc-dashboard-chart-trend', {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Submissions',
                    data: trendValues,
                    borderColor: '#6366f1',
                    backgroundColor: function (context) {
                        return lineGradient(context.chart.ctx, context.chart.chartArea, '99, 102, 241');
                    },
                    fill: true,
                    tension: 0.35,
                    pointRadius: trendRows.length > 28 ? 0 : 2,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: isDarkTheme() ? '#0f172a' : '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverBorderWidth: 2,
                    borderWidth: 2.5,
                }],
            },
            options: Object.assign({}, base, {
                interaction: { mode: 'index', intersect: false },
                scales: cartesianScales(theme, false),
            }),
        });
        hideSkeleton('trend');

        /* States — horizontal bars; chips toggle visibility */
        renderStatesChart(data.by_state);

        /* Fence styles — ranked list (style name + section count) */
        var fenceRows = sortByCount(data.fence_styles || []);
        var fenceContainer = document.querySelector('[data-fc-dashboard-fence-bars]');
        if (fenceContainer) {
            renderFenceStylesList(fenceContainer, fenceRows, { limit: 10 });
            hideSkeleton('fences');
        }
        setChartEmpty('fences', !fenceRows.length);

        /* Peak hours — vertical bars with intensity for 24h distribution */
        var hourValues = new Array(24).fill(0);
        (data.by_hour || []).forEach(function (row) {
            hourValues[row.hour] = row.count;
        });
        var hourLabels = [];
        for (var h = 0; h < 24; h++) {
            hourLabels.push((h < 10 ? '0' : '') + h + ':00');
        }
        var peakHourIndex = 0;
        var peakHourValue = 0;
        hourValues.forEach(function (value, index) {
            if (value >= peakHourValue) {
                peakHourValue = value;
                peakHourIndex = index;
            }
        });
        var sessionsTotal = hourValues.reduce(function (total, count) {
            return total + (Number(count) || 0);
        }, 0);

        renderVizSummary(document.querySelector('[data-fc-dashboard-hours-summary]'), sessionsTotal ? [
            { label: 'Peak hour', value: hourLabels[peakHourIndex] || '—', hint: formatNumber(peakHourValue) + ' sessions' },
            { label: 'Sessions tracked', value: formatNumber(sessionsTotal) },
        ] : []);

        setChartEmpty('hours', !sessionsTotal);
        makeChart('fc-dashboard-chart-hours', {
            type: 'bar',
            data: {
                labels: hourLabels,
                datasets: [{
                    label: 'Sessions',
                    data: hourValues,
                    backgroundColor: intensityColors(hourValues, theme, 2),
                    hoverBackgroundColor: intensityColors(hourValues, theme, 2).map(function (color) {
                        return color.replace(/0\.\d+\)$/, '1)');
                    }),
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 18,
                }],
            },
            options: Object.assign({}, base, {
                scales: Object.assign({}, cartesianScales(theme, false), {
                    x: Object.assign({}, cartesianScales(theme, false).x, {
                        ticks: Object.assign({}, cartesianScales(theme, false).x.ticks, {
                            maxTicksLimit: 6,
                            callback: function (value, index) {
                                return index % 4 === 0 ? this.getLabelForValue(value) : '';
                            },
                        }),
                    }),
                }),
            }),
        });
        hideSkeleton('hours');

        /* Devices & browsers — ranked bars (better than small donuts) */
        var deviceContainer = document.querySelector('[data-fc-dashboard-device-bars]');
        if (deviceContainer) {
            renderRankedBars(deviceContainer, data.by_device || [], { limit: 5, accent: 'amber' });
            hideSkeleton('devices');
        }

        var browserContainer = document.querySelector('[data-fc-dashboard-browser-bars]');
        if (browserContainer) {
            renderRankedBars(browserContainer, data.browsers || [], { limit: 5, accent: 'cyan' });
            hideSkeleton('browsers');
        }

        var combinationContainer = document.querySelector('[data-fc-dashboard-combination-bars]');
        if (combinationContainer) {
            renderRankedBars(
                combinationContainer,
                data.device_browser_combinations || [],
                { limit: 17, accent: 'amber' }
            );
            hideSkeleton('device-browser-combinations');
        }

        var customersEl = document.querySelector('[data-fc-dashboard-top-customers]');
        if (customersEl) {
            renderCustomerList(customersEl, data.top_customers || [], { limit: 16 });
            hideSkeleton('customers');
        }

        var recentEl = document.querySelector('[data-fc-dashboard-recent-entries]');
        if (recentEl) {
            renderRecentList(recentEl, data.recent_entries || [], { limit: 16 });
            hideSkeleton('recent');
        }

        var insightsEl = document.querySelector('[data-fc-dashboard-insights]');
        if (insightsEl) {
            var colourRows = ((data.product_insights || {}).colours) || [];
            renderInsights(insightsEl, data.product_insights || {});
            hideSkeleton('insights');
            setChartEmpty('insights', !colourRows.length);
        }
    }

    function fetchCharts(root, dateFilter) {
        chartRoot = root;
        var filter = dateFilter || currentDateFilter || { period: '', from: '', to: '' };
        currentDateFilter = {
            period: filter.period || '',
            from: filter.from || '',
            to: filter.to || '',
        };

        var skeletons = root.querySelectorAll('.fc-dashboard-skeleton');
        skeletons.forEach(function (el) {
            el.hidden = false;
        });

        return fetch(chartUrl(apiBase(root), 'charts', {
            date_period: currentDateFilter.period,
            date_from: currentDateFilter.from,
            date_to: currentDateFilter.to,
        }), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.ok) {
                    throw new Error((data && data.error) || 'Could not load charts.');
                }
                return ensureChartJs().then(function () {
                    excludedStates = {};
                    destroyAllCharts();
                    renderCharts(data);
                });
            })
            .catch(function () {
                skeletons.forEach(function (el) {
                    el.textContent = 'Could not load chart data.';
                    el.hidden = false;
                });
            });
    }

    function readDateFilterFromDom(dropdown) {
        if (!dropdown) {
            return { period: '', from: '', to: '' };
        }
        var periodInput = dropdown.querySelector('[data-fc-entries-date-period]');
        var fromInput = dropdown.querySelector('[data-fc-entries-date-from]');
        var toInput = dropdown.querySelector('[data-fc-entries-date-to]');
        return {
            period: periodInput ? String(periodInput.value || '') : '',
            from: fromInput ? String(fromInput.value || '') : '',
            to: toInput ? String(toInput.value || '') : '',
        };
    }

    function writeDateFilterToDom(dropdown, filter) {
        if (!dropdown || !filter) {
            return;
        }
        var periodInput = dropdown.querySelector('[data-fc-entries-date-period]');
        var fromInput = dropdown.querySelector('[data-fc-entries-date-from]');
        var toInput = dropdown.querySelector('[data-fc-entries-date-to]');
        var customFrom = dropdown.querySelector('[data-fc-entries-date-custom-from]');
        var customTo = dropdown.querySelector('[data-fc-entries-date-custom-to]');

        if (periodInput) {
            periodInput.value = filter.period || '';
        }
        if (fromInput) {
            fromInput.value = filter.from || '';
        }
        if (toInput) {
            toInput.value = filter.to || '';
        }
        if (customFrom) {
            customFrom.value = filter.from || '';
        }
        if (customTo) {
            customTo.value = filter.to || '';
        }
    }

    /**
     * @return {{period:string,from:string,to:string}|null} null when ?date= is absent
     */
    function readDateFilterFromUrl() {
        var params = new URLSearchParams(window.location.search || '');
        if (!params.has('date')) {
            return null;
        }
        var date = String(params.get('date') || '').trim();
        if (date === '' || date === 'all') {
            return { period: '', from: '', to: '' };
        }
        if (date === 'custom') {
            return {
                period: 'custom',
                from: String(params.get('from') || '').trim(),
                to: String(params.get('to') || '').trim(),
            };
        }
        return { period: date, from: '', to: '' };
    }

    function writeDateFilterToUrl(filter) {
        var url = new URL(window.location.href);
        var period = filter && filter.period ? String(filter.period) : '';

        if (!period) {
            url.searchParams.set('date', 'all');
            url.searchParams.delete('from');
            url.searchParams.delete('to');
        } else if (period === 'custom') {
            url.searchParams.set('date', 'custom');
            if (filter.from) {
                url.searchParams.set('from', filter.from);
            } else {
                url.searchParams.delete('from');
            }
            if (filter.to) {
                url.searchParams.set('to', filter.to);
            } else {
                url.searchParams.delete('to');
            }
        } else {
            url.searchParams.set('date', period);
            url.searchParams.delete('from');
            url.searchParams.delete('to');
        }

        var next = url.pathname + url.search + url.hash;
        var current = window.location.pathname + window.location.search + window.location.hash;
        if (next === current) {
            return;
        }

        var state = window.history.state && typeof window.history.state === 'object'
            ? Object.assign({}, window.history.state)
            : {};
        state.fcAdminRoute = 'dashboard';
        window.history.replaceState(state, '', next);
    }

    function filtersEqual(a, b) {
        a = a || {};
        b = b || {};
        return String(a.period || '') === String(b.period || '')
            && String(a.from || '') === String(b.from || '')
            && String(a.to || '') === String(b.to || '');
    }

    function syncDateFilterUi(root, filter) {
        var dropdown = root.querySelector('[data-fc-dashboard-date-dropdown]');
        if (dropdown) {
            writeDateFilterToDom(dropdown, filter);
            if (window.FcEntriesDateFilter && typeof window.FcEntriesDateFilter.syncUi === 'function') {
                window.FcEntriesDateFilter.syncUi(dropdown);
            }
        }
        syncTrendPeriodLabel();
    }

    function applyDateFilter(root, dateFilter, options) {
        options = options || {};
        currentDateFilter = {
            period: dateFilter.period || '',
            from: dateFilter.from || '',
            to: dateFilter.to || '',
        };
        if (options.syncUrl !== false) {
            writeDateFilterToUrl(currentDateFilter);
        }
        syncDateFilterUi(root, currentDateFilter);
        syncEntriesAllLinks(root);
        if (options.fetch === false) {
            return Promise.resolve();
        }
        return fetchCharts(root, currentDateFilter);
    }

    function ensureDateDropdown(root) {
        var dropdown = root.querySelector('[data-fc-dashboard-date-dropdown]');
        if (!dropdown) {
            return null;
        }
        if (window.FcEntriesDateFilter && typeof window.FcEntriesDateFilter.init === 'function') {
            window.FcEntriesDateFilter.init(dropdown);
        }
        return dropdown;
    }

    function resolveDateFilter(root) {
        var fromUrl = readDateFilterFromUrl();
        if (fromUrl) {
            return fromUrl;
        }
        var dropdown = root.querySelector('[data-fc-dashboard-date-dropdown]');
        return readDateFilterFromDom(dropdown);
    }

    function restoreDateFilter(root) {
        var filter = resolveDateFilter(root);
        currentDateFilter = {
            period: filter.period || '',
            from: filter.from || '',
            to: filter.to || '',
        };
        writeDateFilterToUrl(currentDateFilter);
        syncDateFilterUi(root, currentDateFilter);
        syncEntriesAllLinks(root);
    }

    function bindDateDropdown(root) {
        if (root.getAttribute('data-fc-dashboard-date-bound') === '1') {
            return;
        }
        root.setAttribute('data-fc-dashboard-date-bound', '1');

        root.addEventListener('fc-entries-date-change', function (e) {
            var dropdown = e.target && e.target.closest
                ? e.target.closest('[data-fc-dashboard-date-dropdown]')
                : null;
            if (!dropdown || !root.contains(dropdown)) {
                return;
            }
            var detail = e.detail || {};
            applyDateFilter(root, {
                period: detail.period || '',
                from: detail.from || '',
                to: detail.to || '',
            });
        });
    }

    function bindDatePopState(root) {
        if (root.getAttribute('data-fc-dashboard-popstate-bound') === '1') {
            return;
        }
        root.setAttribute('data-fc-dashboard-popstate-bound', '1');
        window.addEventListener('popstate', function () {
            if (!document.body.contains(root)) {
                return;
            }
            var next = resolveDateFilter(root);
            if (filtersEqual(next, currentDateFilter)) {
                return;
            }
            applyDateFilter(root, next, { syncUrl: false });
        });
    }

    function refreshCardSliders() {
        if (!chartDataCache) {
            return;
        }
        var customersEl = document.querySelector('[data-fc-dashboard-top-customers]');
        var recentEl = document.querySelector('[data-fc-dashboard-recent-entries]');
        if (customersEl) {
            renderCustomerList(customersEl, chartDataCache.top_customers || [], { limit: 16 });
        }
        if (recentEl) {
            renderRecentList(recentEl, chartDataCache.recent_entries || [], { limit: 16 });
        }
    }

    function bindSliderResize() {
        if (window.__fcDashboardSliderResizeBound) {
            return;
        }
        window.__fcDashboardSliderResizeBound = true;
        var lastPerSlide = sliderItemsPerSlide();
        var resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                var nextPerSlide = sliderItemsPerSlide();
                if (nextPerSlide !== lastPerSlide) {
                    lastPerSlide = nextPerSlide;
                    refreshCardSliders();
                }
            }, 180);
        });
    }

    function bindThemeRefresh() {
        if (window.__fcDashboardThemeBound) {
            return;
        }
        window.__fcDashboardThemeBound = true;
        window.addEventListener('fc-admin-theme-change', function () {
            if (!chartDataCache || !window.Chart) {
                return;
            }
            destroyAllCharts();
            renderCharts(chartDataCache);
        });
    }

    function initDashboard(root) {
        if (!root) {
            return;
        }

        if (root.getAttribute('data-fc-dashboard-bound') === '1') {
            var next = resolveDateFilter(root);
            if (!filtersEqual(next, currentDateFilter)) {
                applyDateFilter(root, next);
            } else {
                writeDateFilterToUrl(currentDateFilter);
                syncDateFilterUi(root, currentDateFilter);
            }
            return;
        }
        root.setAttribute('data-fc-dashboard-bound', '1');

        bindDateDropdown(root);
        bindDatePopState(root);
        ensureDateDropdown(root);
        restoreDateFilter(root);

        try {
            localStorage.removeItem('fc-dashboard-date-filter');
        } catch (err) {
            /* ignore */
        }

        bindThemeRefresh();
        bindSliderResize();
        bindEntryLinks(root);
        bindStateMapToggles(root);

        window.requestAnimationFrame(function () {
            fetchCharts(root, currentDateFilter);
        });
    }

    function hydrateFromServer(container) {
        var root = getRoot(container);
        if (root) {
            initDashboard(root);
        }
    }

    window.FcAdminDashboard = {
        init: initDashboard,
        hydrateFromServer: hydrateFromServer,
    };

    document.addEventListener('DOMContentLoaded', function () {
        var root = getRoot();
        if (root) {
            initDashboard(root);
        }
    });
})();
