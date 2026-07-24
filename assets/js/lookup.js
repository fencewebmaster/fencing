/**
 * FC Product Lookup — light UX only (no AJAX / fetch).
 */
(function () {
    'use strict';

    var LAYOUT_KEY = 'fc-lookup-layout';

    function $(sel, root) {
        return (root || document).querySelector(sel);
    }

    function $all(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function showToast(message) {
        var el = $('[data-fc-lookup-toast]');
        if (!el) {
            return;
        }
        el.hidden = false;
        el.classList.add('is-visible');
        el.textContent = message;
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () {
            el.hidden = true;
            el.classList.remove('is-visible');
            el.textContent = '';
        }, 1800);
    }

    function copyText(text, label) {
        var done = function () {
            showToast(label || 'Copied');
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(function () {
                window.prompt('Copy:', text);
            });
            return;
        }
        window.prompt('Copy:', text);
    }

    function setFiltersOpen(root, open) {
        root.classList.toggle('is-filters-open', open);
        $all('[data-fc-lookup-filters-open]', root).forEach(function (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        var backdrop = $('[data-fc-lookup-filters-close].fc-lookup__sidebar-backdrop', root);
        if (backdrop) {
            backdrop.hidden = !open;
        }
        document.documentElement.classList.toggle('fc-lookup-scroll-lock', open);
    }

    function bindGroups(root) {
        $all('[data-fc-lookup-group-toggle]', root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('[data-fc-lookup-filter-group]');
                if (!group) {
                    return;
                }
                var body = group.querySelector('.fc-lookup-filter-group__body');
                var open = !group.classList.contains('is-open');
                group.classList.toggle('is-open', open);
                $all('[data-fc-lookup-group-toggle]', group).forEach(function (toggle) {
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
                if (body) {
                    body.hidden = !open;
                }
            });
        });
    }

    function bindCats(root) {
        $all('[data-fc-lookup-cat-toggle]', root).forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var cat = btn.closest('.fc-lookup-cat');
                if (!cat) {
                    return;
                }
                var kids = cat.querySelector(':scope > .fc-lookup-cat__children');
                var open = !cat.classList.contains('is-expanded');
                cat.classList.toggle('is-expanded', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (kids) {
                    kids.hidden = !open;
                }
            });
        });

        // Expand parents that contain checked children
        $all('.fc-lookup-cat input:checked', root).forEach(function (input) {
            var node = input.closest('.fc-lookup-cat');
            while (node) {
                var parent = node.parentElement && node.parentElement.closest
                    ? node.parentElement.closest('.fc-lookup-cat')
                    : null;
                if (!parent) {
                    break;
                }
                parent.classList.add('is-expanded');
                var kids = parent.querySelector(':scope > .fc-lookup-cat__children');
                var toggle = parent.querySelector(':scope > .fc-lookup-cat__row [data-fc-lookup-cat-toggle]');
                if (kids) {
                    kids.hidden = false;
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
                node = parent;
            }
        });

        var catSearch = $('[data-fc-lookup-cat-search]', root);
        if (catSearch) {
            catSearch.addEventListener('input', function () {
                var q = String(catSearch.value || '').toLowerCase().trim();
                $all('[data-fc-lookup-cat-list] .fc-lookup-cat', root).forEach(function (row) {
                    var label = row.querySelector('.fc-lookup-check__label');
                    var text = label ? String(label.textContent || '').toLowerCase() : '';
                    var match = !q || text.indexOf(q) !== -1;
                    row.style.display = match ? '' : 'none';
                    if (match && q) {
                        var parent = row.parentElement && row.parentElement.closest
                            ? row.parentElement.closest('.fc-lookup-cat')
                            : null;
                        while (parent) {
                            parent.classList.add('is-expanded');
                            var kids = parent.querySelector(':scope > .fc-lookup-cat__children');
                            if (kids) {
                                kids.hidden = false;
                            }
                            parent.style.display = '';
                            parent = parent.parentElement && parent.parentElement.closest
                                ? parent.parentElement.closest('.fc-lookup-cat')
                                : null;
                        }
                    }
                });
            });
        }
    }

    function bindTagSearch(root) {
        var input = $('[data-fc-lookup-tag-search]', root);
        if (!input) {
            return;
        }
        input.addEventListener('input', function () {
            var q = String(input.value || '').toLowerCase().trim();
            $all('[data-fc-lookup-tag-item]', root).forEach(function (row) {
                var name = String(row.getAttribute('data-name') || '');
                row.style.display = !q || name.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }

    function bindCopy(root) {
        root.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-fc-lookup-copy]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            copyText(btn.getAttribute('data-fc-lookup-copy') || '', btn.getAttribute('data-fc-lookup-copy-label') || 'Copied');
        });
    }

    function bindGallery(root) {
        $all('[data-fc-lookup-gallery]', root).forEach(function (media) {
            var imgs = $all('.fc-lookup-card__img', media);
            if (imgs.length < 2) {
                return;
            }
            var index = 0;
            var timer = null;

            function show(i) {
                imgs.forEach(function (img, n) {
                    var active = n === i;
                    img.classList.toggle('is-active', active);
                    img.hidden = !active;
                });
                index = i;
            }

            media.addEventListener('mouseenter', function () {
                clearInterval(timer);
                timer = setInterval(function () {
                    show((index + 1) % imgs.length);
                }, 900);
            });
            media.addEventListener('mouseleave', function () {
                clearInterval(timer);
                timer = null;
                show(0);
            });
        });
    }

    function bindQuickViewMagnify(qv) {
        var stage = $('[data-fc-lookup-qv-magnify]', qv);
        var hero = $('[data-fc-lookup-qv-hero]', qv);
        if (!stage || !hero) {
            return;
        }

        // Touch / coarse pointers: keep plain contain display.
        if (window.matchMedia && window.matchMedia('(hover: none)').matches) {
            return;
        }

        var zoom = 2.4;
        var active = false;
        var raf = 0;
        var pending = null;

        function resetMagnify() {
            active = false;
            pending = null;
            if (raf) {
                cancelAnimationFrame(raf);
                raf = 0;
            }
            stage.classList.remove('is-magnifying');
            stage.classList.remove('is-over-image');
            hero.style.transform = '';
            hero.style.transformOrigin = '';
        }

        /**
         * Layout box of the image inside the stage (unaffected by CSS transform).
         * Using getBoundingClientRect while scaled caused edge flicker: the hit
         * box grew/shrank and toggled magnify on/off every frame.
         */
        function heroLayoutBox() {
            return {
                left: hero.offsetLeft,
                top: hero.offsetTop,
                width: hero.offsetWidth,
                height: hero.offsetHeight
            };
        }

        function applyMagnify(clientX, clientY) {
            var box = heroLayoutBox();
            if (box.width < 12 || box.height < 12) {
                resetMagnify();
                return;
            }

            var stageRect = stage.getBoundingClientRect();
            var x = clientX - stageRect.left - stage.clientLeft - box.left;
            var y = clientY - stageRect.top - stage.clientTop - box.top;

            if (x < 0 || y < 0 || x > box.width || y > box.height) {
                resetMagnify();
                return;
            }

            var originX = (x / box.width) * 100;
            var originY = (y / box.height) * 100;

            active = true;
            stage.classList.add('is-over-image');
            stage.classList.add('is-magnifying');
            hero.style.transformOrigin = originX + '% ' + originY + '%';
            hero.style.transform = 'scale(' + zoom + ')';
        }

        function onMove(e) {
            pending = { x: e.clientX, y: e.clientY };
            if (raf) {
                return;
            }
            raf = requestAnimationFrame(function () {
                raf = 0;
                if (!pending) {
                    return;
                }
                applyMagnify(pending.x, pending.y);
            });
        }

        stage.addEventListener('mousemove', onMove);
        stage.addEventListener('mouseleave', resetMagnify);
        hero.addEventListener('load', function () {
            if (!active) {
                return;
            }
            resetMagnify();
        });
    }

    function bindQuickView(root) {
        var qv = $('[data-fc-lookup-qv]', root);
        if (!qv) {
            return;
        }
        var hero = $('[data-fc-lookup-qv-hero]', qv);
        $all('[data-fc-lookup-qv-thumb]', qv).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var src = btn.getAttribute('data-fc-lookup-qv-thumb') || '';
                if (hero && src) {
                    hero.src = src;
                    hero.style.transform = '';
                    hero.style.transformOrigin = '';
                    var stage = $('[data-fc-lookup-qv-magnify]', qv);
                    if (stage) {
                        stage.classList.remove('is-magnifying');
                    }
                }
                $all('[data-fc-lookup-qv-thumb]', qv).forEach(function (b) {
                    b.classList.toggle('is-active', b === btn);
                });
            });
        });

        bindQuickViewMagnify(qv);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var close = $('.fc-lookup-qv__close', qv);
                if (close && close.href) {
                    window.location.href = close.href;
                }
            }
        });

        var focusable = qv.querySelector('a, button, [tabindex]:not([tabindex="-1"])');
        if (focusable) {
            focusable.focus();
        }
    }

    function bindLayoutMemory(root) {
        var current = root.getAttribute('data-fc-lookup-layout') || 'grid';
        try {
            localStorage.setItem(LAYOUT_KEY, current);
        } catch (e) {
            /* ignore */
        }

        $all('[data-fc-lookup-layout]', root).forEach(function (link) {
            link.addEventListener('click', function () {
                var layout = link.getAttribute('data-fc-lookup-layout') || 'grid';
                try {
                    localStorage.setItem(LAYOUT_KEY, layout);
                } catch (err) {
                    /* ignore */
                }
            });
        });

        // If URL has no layout, rewrite layout toggle hrefs to include preferred mode
        // for the opposite switch; landing without ?layout keeps server default (grid).
        try {
            var preferred = localStorage.getItem(LAYOUT_KEY);
            var params = new URLSearchParams(window.location.search);
            if (!params.has('layout') && (preferred === 'list' || preferred === 'grid') && preferred !== current) {
                params.set('layout', preferred);
                var next = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', next);
                root.setAttribute('data-fc-lookup-layout', preferred);
                var results = $('[data-fc-lookup-results]', root);
                if (results) {
                    results.classList.remove('fc-lookup-results--grid', 'fc-lookup-results--list');
                    results.classList.add('fc-lookup-results--' + preferred);
                }
                $all('[data-fc-lookup-layout]', root).forEach(function (link) {
                    var isActive = link.getAttribute('data-fc-lookup-layout') === preferred;
                    link.classList.toggle('is-active', isActive);
                    link.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
                var layoutInput = $('[data-fc-lookup-layout-input]', root);
                if (layoutInput) {
                    layoutInput.value = preferred;
                }
            }
        } catch (e2) {
            /* ignore */
        }
    }

    function bindMobileFilters(root) {
        $all('[data-fc-lookup-filters-open]', root).forEach(function (btn) {
            btn.addEventListener('click', function () {
                setFiltersOpen(root, true);
            });
        });
        $all('[data-fc-lookup-filters-close]', root).forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (btn.tagName === 'A') {
                    return;
                }
                e.preventDefault();
                setFiltersOpen(root, false);
            });
        });
    }

    function bindSwatchSelection(root) {
        $all('.fc-lookup-swatch input', root).forEach(function (input) {
            input.addEventListener('change', function () {
                var label = input.closest('.fc-lookup-swatch');
                if (label) {
                    label.classList.toggle('is-selected', input.checked);
                }
            });
        });
    }

    function bindPriceRange(root) {
        var wrap = $('[data-fc-lookup-price]', root);
        if (!wrap) {
            return;
        }
        var minRange = $('[data-fc-lookup-price-min-range]', wrap);
        var maxRange = $('[data-fc-lookup-price-max-range]', wrap);
        var minInput = $('[data-fc-lookup-price-min-input]', wrap);
        var maxInput = $('[data-fc-lookup-price-max-input]', wrap);
        var selMin = $('[data-fc-lookup-price-selected-min]', wrap);
        var selMax = $('[data-fc-lookup-price-selected-max]', wrap);
        if (!minRange || !maxRange || !minInput || !maxInput) {
            return;
        }

        var boundMin = parseFloat(wrap.getAttribute('data-min') || '0') || 0;
        var boundMax = parseFloat(wrap.getAttribute('data-max') || '0') || 0;
        if (boundMax <= boundMin) {
            boundMax = boundMin + 1;
        }
        var currency = wrap.getAttribute('data-currency') || '$';
        var span = boundMax - boundMin;

        function fmt(n) {
            var v = Math.round(n);
            return currency + String(v).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function clamp(n) {
            return Math.max(boundMin, Math.min(boundMax, n));
        }

        function readPair() {
            var a = clamp(parseFloat(minRange.value) || boundMin);
            var b = clamp(parseFloat(maxRange.value) || boundMax);
            if (a > b) {
                var t = a;
                a = b;
                b = t;
            }
            return { min: a, max: b };
        }

        function paint(pair, source) {
            var a = pair.min;
            var b = pair.max;
            minRange.value = String(a);
            maxRange.value = String(b);

            // Keep empty inputs empty until the user moves the slider or types
            // (unless values came from the ranges or typed fields).
            if (source === 'range' || source === 'input') {
                minInput.value = String(a);
                maxInput.value = String(b);
            }

            if (selMin) {
                selMin.textContent = fmt(a);
            }
            if (selMax) {
                selMax.textContent = fmt(b);
            }

            var pctMin = ((a - boundMin) / span) * 100;
            var pctMax = ((b - boundMin) / span) * 100;
            wrap.style.setProperty('--price-min', pctMin.toFixed(2) + '%');
            wrap.style.setProperty('--price-max', pctMax.toFixed(2) + '%');
        }

        function fromMinRange() {
            var a = clamp(parseFloat(minRange.value) || boundMin);
            var b = clamp(parseFloat(maxRange.value) || boundMax);
            if (a > b) {
                a = b;
                minRange.value = String(a);
            }
            paint({ min: a, max: b }, 'range');
        }

        function fromMaxRange() {
            var a = clamp(parseFloat(minRange.value) || boundMin);
            var b = clamp(parseFloat(maxRange.value) || boundMax);
            if (b < a) {
                b = a;
                maxRange.value = String(b);
            }
            paint({ min: a, max: b }, 'range');
        }

        function fromInputs() {
            var aRaw = minInput.value.trim();
            var bRaw = maxInput.value.trim();
            var pair = readPair();
            if (aRaw !== '') {
                pair.min = clamp(parseFloat(aRaw) || boundMin);
            }
            if (bRaw !== '') {
                pair.max = clamp(parseFloat(bRaw) || boundMax);
            }
            if (pair.min > pair.max) {
                if (document.activeElement === minInput) {
                    pair.max = pair.min;
                } else {
                    pair.min = pair.max;
                }
            }
            paint(pair, 'input');
        }

        function markActive(el) {
            minRange.classList.remove('is-active');
            maxRange.classList.remove('is-active');
            if (el) {
                el.classList.add('is-active');
            }
        }

        minRange.addEventListener('pointerdown', function () {
            markActive(minRange);
        });
        maxRange.addEventListener('pointerdown', function () {
            markActive(maxRange);
        });
        minRange.addEventListener('input', fromMinRange);
        maxRange.addEventListener('input', fromMaxRange);
        minInput.addEventListener('input', fromInputs);
        maxInput.addEventListener('input', fromInputs);
        minInput.addEventListener('change', fromInputs);
        maxInput.addEventListener('change', fromInputs);

        // Initial paint keeps current selection without forcing empty form values.
        paint(readPair(), 'init');
    }

    function boot() {
        var root = $('[data-fc-lookup]');
        if (!root) {
            return;
        }

        bindGroups(root);
        bindCats(root);
        bindTagSearch(root);
        bindCopy(root);
        bindGallery(root);
        bindQuickView(root);
        bindMobileFilters(root);
        bindSwatchSelection(root);
        bindPriceRange(root);
        bindLayoutMemory(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
