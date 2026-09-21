/**
 * FC Admin — Missing SKUs (route products/system-products/missing-sku).
 *
 * The server renders every row and its colour SKU fields; this file only enhances them:
 * catalogue status ticks, thumbnails, a suggestions popover and a per-row save. SKU semantics
 * (missing / OFF / found, and the suggestion matcher) come from FcAdminSkuFields, exported by
 * products/store-products.js, so the two pages cannot drift apart.
 */
(function (global) {
    'use strict';

    var API_UPDATE = fcApiUrl('products', 'action=update-store-product');
    var API_SCAN = fcApiUrl('products', 'action=scan-missing-skus');
    var API_DEEP_SCAN = fcApiUrl('products', 'action=deep-scan-missing-skus');
    var API_CLEAR = fcApiUrl('products', 'action=clear-missing-skus');
    var SKU = global.FcAdminSkuFields || null;
    var escapeHtml = global.FC.util.escapeHtml;

    var csrf = '';
    // Deep Scan leads, keyed "slug|COLUMN". Advisory, so they live here and never touch an input.
    var deepSuggestions = {};
    var canEdit = false;
    var pageRoot = null;
    var openSuggestWrap = null;
    var openSuggestMatches = [];
    var openSuggestSeed = '';
    var docClickBound = false;

    var copyFieldButton = new global.FC.components.CopyFieldButton({
        dataAttr: 'data-fc-sp-copy-for',
        onCopied: function () {
            var T = global.FcAdminToast;
            if (T) {
                T.success('Copied to clipboard');
            }
        }
    });

    class MissingSkuPage extends global.FC.PageController {
        hydrate(container) {
            hydrateFromServer(container);
        }

        destroy() {
            closeSuggest();
            closeActionsMenu();
            if (SKU && typeof SKU.closeGallery === 'function') {
                SKU.closeGallery();
            }
        }
    }

    function readBootstrap() {
        var el = document.getElementById('fc-missing-sku-bootstrap');
        if (!el) {
            return {};
        }
        try {
            return JSON.parse(el.textContent || '{}') || {};
        } catch (err) {
            return {};
        }
    }

    function rowsIn(root) {
        return Array.prototype.slice.call(root.querySelectorAll('[data-fc-ms-row]'));
    }

    function fieldsIn(row) {
        return Array.prototype.slice.call(row.querySelectorAll('[data-fc-ms-field]'));
    }

    function inputIn(field) {
        return field.querySelector('.fc-sp-field-control--sku');
    }

    /* The menu options wrap their label beside an icon, so it has to be asked for by name. */
    function labelOf(btn) {
        return btn ? btn.querySelector('[data-fc-ms-label]') : null;
    }

    /* ---------------------------------------------------------------- status + thumbnails */

    function paintStatus(field) {
        var input = inputIn(field);
        var btn = field.querySelector('[data-fc-sp-sku-check]');
        if (!input || !btn || !SKU) {
            return;
        }
        var value = input.value;
        var off = SKU.isOff(value);
        var empty = !off && SKU.normalize(value) === '';
        var found = !off && !empty && SKU.existsInCatalogue(value);
        var missing = !off && !empty && !found;

        btn.classList.toggle('fc-sp-sku-check--off', off);
        btn.classList.toggle('fc-sp-sku-check--empty', empty);
        btn.classList.toggle('fc-sp-sku-check--found', found);
        btn.classList.toggle('fc-sp-sku-check--missing', missing);
        input.classList.toggle('fc-sp-field-control--empty', empty);
        field.classList.toggle('fc-ms-field--gap', empty || missing);

        var icon = btn.querySelector('i');
        if (icon) {
            icon.className = off
                ? 'fa-solid fa-circle'
                : found
                  ? 'fa-solid fa-check'
                  : empty
                    ? 'fa-solid fa-exclamation'
                    : 'fa-solid fa-xmark';
        }
        btn.title = off
            ? 'Set to OFF — counted as complete'
            : found
              ? 'Found in store catalogue — click for similar SKUs'
              : empty
                ? 'No SKU — click for similar catalogue SKUs'
                : 'Not in store catalogue — click for similar SKUs';
        btn.setAttribute('aria-label', btn.title);

        paintThumb(field, value);
    }

    function paintThumb(field, value) {
        var slot = field.querySelector('[data-fc-sp-sku-thumb]');
        if (!slot || !SKU) {
            return;
        }
        var image = String(SKU.meta(value).image || '').trim();
        var current = slot.tagName === 'IMG' ? slot.getAttribute('src') || '' : '';
        if (current === image) {
            return;
        }
        slot.outerHTML = image
            ? '<img class="fc-sp-sku-thumb fc-sp-sku-thumb--viewable" data-fc-sp-sku-thumb data-fc-ms-thumb-view src="' +
              escapeHtml(image) +
              '" alt="" loading="lazy" decoding="async" tabindex="0" role="button" aria-label="View larger image">'
            : '<span class="fc-sp-sku-thumb fc-sp-sku-thumb--empty" data-fc-sp-sku-thumb aria-hidden="true"></span>';
    }

    function paintRow(row) {
        var gaps = 0;
        fieldsIn(row).forEach(function (field) {
            paintStatus(field);
            if (field.classList.contains('fc-ms-field--gap')) {
                gaps++;
            }
        });
        var label = row.querySelector('[data-fc-ms-missing-label]');
        if (label) {
            var total = fieldsIn(row).length;
            label.textContent = gaps === 0 ? 'All filled' : gaps + ' of ' + total + ' missing';
            label.classList.toggle('fc-ms-chip--gap', gaps > 0);
            label.classList.toggle('fc-ms-chip--done', gaps === 0);
        }
    }

    /* ------------------------------------------------------------------------ suggestions */

    /* The toolbar's "Filled Rows n/total". A row counts once none of its SKU fields is a gap,
       which is the same test the row's own "All filled" chip uses. */
    function refreshFilledCount() {
        var el = document.querySelector('[data-fc-ms-filled-count]');
        if (!el) {
            return;
        }
        var rows = rowsIn(document);
        var filled = 0;
        var eligible = 0;
        rows.forEach(function (row) {
            var isFilled = fieldsIn(row).every(function (field) {
                return !field.classList.contains('fc-ms-field--gap');
            });
            // The "Filled Rows" toggle hides on this class, so it follows the tally automatically.
            row.classList.toggle('is-filled', isFilled);
            if (isFilled) {
                filled++;
            }
            if (isFilled || row.classList.contains('has-scanned')) {
                eligible++;
            }
        });

        el.textContent = filled + '/' + rows.length;
        var wrap = el.closest('.fc-ms-filled');
        if (wrap) {
            wrap.classList.toggle('is-complete', rows.length > 0 && filled === rows.length);
        }
        refreshFilledToggle(eligible);
    }

    /* The toggle means nothing until something is filled, so it stays out of the toolbar until then.
       Losing the last filled row also clears the filter, or it would hide every row with no way back. */
    function refreshFilledToggle(eligible) {
        var toggle = document.querySelector('[data-fc-ms-only-filled]');
        if (!toggle) {
            return;
        }
        if (eligible === 0 && toggle.checked) {
            toggle.checked = false;
            if (pageRoot) {
                pageRoot.classList.remove('is-only-filled');
            }
        }
        var control = toggle.closest('.fc-ms-only-filled');
        if (control) {
            control.hidden = eligible === 0;
        }
    }

    /** A blank field has nothing to match on, so it borrows a filled sibling's SKU as the seed. */
    function seedFor(field) {
        var input = inputIn(field);
        var own = input ? SKU.normalize(input.value) : '';
        if (own && !SKU.isOff(own)) {
            return own;
        }
        var row = field.closest('[data-fc-ms-row]');
        var seed = '';
        if (row) {
            fieldsIn(row).some(function (sibling) {
                var value = SKU.normalize((inputIn(sibling) || {}).value || '');
                if (value && !SKU.isOff(value)) {
                    seed = value;
                    return true;
                }
                return false;
            });
        }
        return seed;
    }

    /**
     * Marks the part of a candidate SKU the matcher scored on: the seed as a substring when it is
     * one, otherwise the shared opening characters it ranked by.
     */
    function highlightSku(sku, seed) {
        var text = String(sku || '');
        var q = SKU.normalize(seed || '');
        if (!q) {
            return escapeHtml(text);
        }
        var lower = text.toLowerCase();
        var qLower = q.toLowerCase();
        var start = lower.indexOf(qLower);
        var end;
        if (start !== -1) {
            end = start + qLower.length;
        } else if (qLower.indexOf(lower) !== -1) {
            start = 0;
            end = text.length;
        } else {
            start = 0;
            end = 0;
            while (end < text.length && end < qLower.length && lower.charAt(end) === qLower.charAt(end)) {
                end += 1;
            }
        }
        if (end <= start) {
            return escapeHtml(text);
        }
        return (
            escapeHtml(text.slice(0, start)) +
            '<mark class="fc-ms-hl">' +
            escapeHtml(text.slice(start, end)) +
            '</mark>' +
            escapeHtml(text.slice(end))
        );
    }

    function suggestRowsHtml(matches, seed) {
        return matches
            .map(function (item) {
                var sku = SKU.normalize(item && item.sku ? item.sku : item);
                var meta = item && item.name != null ? item : SKU.meta(sku);
                var name = SKU.productName(meta.name) || 'Untitled product';
                var image = String(meta.image || '').trim();
                var thumbInner = image
                    ? '<img class="fc-sp-sku-suggest__thumb" src="' +
                      escapeHtml(image) +
                      '" alt="" loading="lazy" decoding="async">'
                    : '<span class="fc-sp-sku-suggest__thumb fc-sp-sku-suggest__thumb--empty" aria-hidden="true"></span>';
                return (
                    '<div class="fc-sp-sku-suggest__row" role="option">' +
                    '<button type="button" class="fc-sp-sku-suggest__thumb-btn" data-fc-ms-sku-preview-open="' +
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
                    highlightSku(sku, seed) +
                    '</code>' +
                    // Deep Scan leads carry how much of the title they cover; SKU matches do not.
                    (item && item.percent
                        ? '<span class="fc-sp-sku-suggest__score" title="Deep Scan — covers ' +
                          escapeHtml(String(item.percent)) +
                          '% of this product’s title">' +
                          escapeHtml(String(item.percent)) +
                          '% title match</span>'
                        : '') +
                    '</div>' +
                    '<button type="button" class="btn btn-sm btn-orange fw-semibold fc-sp-sku-suggest__use" data-fc-ms-sku-use="' +
                    escapeHtml(sku) +
                    '">Use</button>' +
                    '</div>'
                );
            })
            .join('');
    }

    /* Same shape as the edit modal's dropdown: a scrolling list with a filter box pinned below. */
    function filterMatches(matches, query) {
        var q = String(query || '').trim().toLowerCase();
        if (!q) {
            return matches;
        }
        return matches.filter(function (item) {
            var sku = SKU.normalize(item && item.sku ? item.sku : item);
            var meta = item && item.name != null ? item : SKU.meta(sku);
            return (
                String(sku).toLowerCase().indexOf(q) !== -1 ||
                String(meta.name || '').toLowerCase().indexOf(q) !== -1
            );
        });
    }

    function suggestListBodyHtml(matches, seed, filterQuery) {
        if (!matches.length) {
            return (
                '<div class="fc-sp-sku-suggest__empty">' +
                (seed
                    ? 'No similar SKUs found.'
                    : 'Type part of a SKU, or fill one colour first, to see catalogue matches.') +
                '</div>'
            );
        }
        var filtered = filterMatches(matches, filterQuery);
        if (!filtered.length) {
            return '<div class="fc-sp-sku-suggest__empty">No products match this filter.</div>';
        }
        return suggestRowsHtml(filtered, seed);
    }

    function suggestMenuHtml(matches, seed, filterQuery) {
        filterQuery = filterQuery == null ? '' : String(filterQuery);
        return (
            '<div class="fc-sp-sku-suggest__list" data-fc-ms-sku-suggest-list role="listbox" aria-label="Similar catalogue SKUs">' +
            suggestListBodyHtml(matches, seed, filterQuery) +
            '</div>' +
            '<div class="fc-sp-sku-suggest__toolbar">' +
            '<label class="fc-sp-sku-suggest__filter-label" for="fc-ms-sku-suggest-filter">Filter</label>' +
            '<input type="search" id="fc-ms-sku-suggest-filter" class="fc-sp-sku-suggest__filter" data-fc-ms-sku-suggest-filter placeholder="Filter by name or SKU\u2026" value="' +
            escapeHtml(filterQuery) +
            '" autocomplete="off" spellcheck="false">' +
            '</div>'
        );
    }

    function previewHtml(sku) {
        var meta = SKU.meta(sku);
        var name = SKU.productName(meta.name) || 'Untitled product';
        var image = String(meta.image || '').trim();
        return (
            '<div class="fc-sp-sku-suggest__preview" data-fc-ms-sku-preview>' +
            '<div class="fc-sp-sku-suggest__preview-body">' +
            '<div class="fc-sp-sku-suggest__preview-media">' +
            (image
                ? '<img class="fc-sp-sku-suggest__preview-image" src="' + escapeHtml(image) + '" alt="">'
                : '<div class="fc-sp-sku-suggest__preview-image fc-sp-sku-suggest__preview-image--empty" aria-hidden="true"></div>') +
            '</div>' +
            '<div class="fc-sp-sku-suggest__preview-name">' + escapeHtml(name) + '</div>' +
            '<code class="fc-sp-sku-suggest__preview-sku">' + escapeHtml(SKU.normalize(sku)) + '</code>' +
            '</div>' +
            '<div class="fc-sp-sku-suggest__preview-footer">' +
            '<button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-ms-sku-preview-close>Close</button>' +
            '</div>' +
            '</div>'
        );
    }

    /* The preview takes the menu over while it is up, so list and filter step aside for it. */
    function togglePreviewChrome(menu, previewing) {
        var list = menu.querySelector('[data-fc-ms-sku-suggest-list]');
        var toolbar = menu.querySelector('.fc-sp-sku-suggest__toolbar');
        menu.classList.toggle('is-preview-open', previewing);
        if (list) {
            list.hidden = previewing;
        }
        if (toolbar) {
            toolbar.hidden = previewing;
        }
    }

    function closePreview() {
        if (!openSuggestWrap) {
            return false;
        }
        var menu = openSuggestWrap.querySelector('[data-fc-sp-sku-suggest]');
        if (!menu || !menu.classList.contains('is-preview-open')) {
            return false;
        }
        var preview = menu.querySelector('[data-fc-ms-sku-preview]');
        if (preview) {
            preview.parentNode.removeChild(preview);
        }
        togglePreviewChrome(menu, false);
        return true;
    }

    function openPreview(sku) {
        if (!openSuggestWrap || !sku) {
            return;
        }
        var menu = openSuggestWrap.querySelector('[data-fc-sp-sku-suggest]');
        if (!menu) {
            return;
        }
        closePreview();
        menu.insertAdjacentHTML('beforeend', previewHtml(sku));
        togglePreviewChrome(menu, true);
    }

    /* Breathing room between the menu and whichever edge it is measured against. */
    var SUGGEST_GUTTER = 8;

    /* Enough menu to be worth opening when neither side of the field has real room. */
    var SUGGEST_MIN_HEIGHT = 160;

    /**
     * The menu is wider than its field and taller than the room under a field near the foot of the
     * list, so it anchors right when it would run off-screen and opens upward when that is where
     * the room is — capped to the room it actually has, or the list is clipped instead of scrolled.
     */
    function positionSuggest(menu) {
        menu.classList.remove('fc-sp-sku-suggest--flip', 'fc-sp-sku-suggest--above');
        menu.style.removeProperty('max-height');

        if (menu.getBoundingClientRect().right > window.innerWidth - SUGGEST_GUTTER) {
            menu.classList.add('fc-sp-sku-suggest--flip');
        }

        var anchor = (menu.closest('.fc-sp-field-input-wrap--sku') || menu).getBoundingClientRect();
        // The rows scroll inside .fc-ms-scroll, which clips the menu long before the viewport does.
        var scroller = menu.closest('.fc-ms-scroll');
        var box = scroller ? scroller.getBoundingClientRect() : null;
        var below = Math.min(box ? box.bottom : window.innerHeight, window.innerHeight)
            - anchor.bottom - SUGGEST_GUTTER;
        var above = anchor.top - Math.max(box ? box.top : 0, 0) - SUGGEST_GUTTER;

        var needed = menu.offsetHeight;
        if (needed <= below) {
            return;
        }
        var room = below;
        if (above > below) {
            menu.classList.add('fc-sp-sku-suggest--above');
            room = above;
        }
        // Only ever reduce it: an inline max-height above the stylesheet's would let the list
        // exceed the height the popover is designed to be.
        if (needed > room) {
            menu.style.maxHeight = Math.max(room, SUGGEST_MIN_HEIGHT) + 'px';
        }
    }

    function closeSuggest() {
        if (!openSuggestWrap) {
            return false;
        }
        var menu = openSuggestWrap.querySelector('[data-fc-sp-sku-suggest]');
        var btn = openSuggestWrap.querySelector('[data-fc-sp-sku-check]');
        if (menu) {
            menu.hidden = true;
            menu.innerHTML = '';
        }
        if (btn) {
            btn.setAttribute('aria-expanded', 'false');
        }
        openSuggestWrap = null;
        openSuggestMatches = [];
        openSuggestSeed = '';
        return true;
    }

    function openSuggest(wrap) {
        if (!SKU || !wrap) {
            return;
        }
        var field = wrap.closest('[data-fc-ms-field]');
        var menu = wrap.querySelector('[data-fc-sp-sku-suggest]');
        var btn = wrap.querySelector('[data-fc-sp-sku-check]');
        if (!field || !menu) {
            return;
        }
        closeSuggest();
        openSuggestWrap = wrap;
        menu.hidden = false;
        menu.innerHTML = '<div class="fc-sp-sku-suggest__empty">Loading catalogue…</div>';
        if (btn) {
            btn.setAttribute('aria-expanded', 'true');
        }

        SKU.ensureIndex().then(function () {
            if (openSuggestWrap !== wrap) {
                return;
            }
            var seed = seedFor(field);
            openSuggestSeed = seed;
            openSuggestMatches = mergeSuggestions(deepFor(field), seed ? SKU.suggest(seed, 24) : []);
            menu.innerHTML = suggestMenuHtml(openSuggestMatches, seed, '');
            positionSuggest(menu);
        });
    }

    /* ------------------------------------------------------------------------------ saving */

    function setRowStatus(row, message, state) {
        var el = row.querySelector('[data-fc-ms-status]');
        if (!el) {
            return;
        }
        el.textContent = message || '';
        el.classList.toggle('is-error', state === 'error');
        el.classList.toggle('is-saved', state === 'saved');
    }

    /* Every image in this row, so the gallery opens on the one clicked and pages the rest. */
    function gallerySlidesFor(row, fromField) {
        var slides = [];
        var startIndex = 0;
        fieldsIn(row).forEach(function (field) {
            var input = inputIn(field);
            if (!input) {
                return;
            }
            var image = String(SKU.meta(input.value).image || '').trim();
            if (!image) {
                return;
            }
            if (field === fromField) {
                startIndex = slides.length;
            }
            slides.push({
                url: image,
                color: global.FC.util.formatHeader(input.name || ''),
                sku: SKU.normalize(input.value) || String(input.value || '').trim()
            });
        });

        return { slides: slides, startIndex: startIndex };
    }

    function openThumbGallery(thumb) {
        if (!SKU || typeof SKU.openGallery !== 'function') {
            return;
        }
        var row = thumb.closest('[data-fc-ms-row]');
        if (!row) {
            return;
        }
        var picked = gallerySlidesFor(row, thumb.closest('[data-fc-ms-field]'));
        SKU.openGallery(picked.slides, picked.startIndex);
    }

    /* ------------------------------------------------------------------------------- scan */

    /* Fills the researched SKUs from writable/missing-products-*.csv. Nothing is written to
       products.csv here — each row still has to be saved, and the tint is kept on every field
       the scan touched so it stays obvious which ones changed. */
    function runScan(root, btn) {
        if (!canEdit) {
            return;
        }

        var filled = 0;
        rowsIn(root).forEach(function (row) {
            var touched = 0;
            fieldsIn(row).forEach(function (field) {
                var sku = field.getAttribute('data-fc-ms-scan-sku');
                var input = inputIn(field);
                // Only fill what is still a gap, so an edit made since page load is never clobbered.
                if (!sku || !input || !field.classList.contains('fc-ms-field--gap')) {
                    return;
                }
                input.value = sku;
                field.classList.add('fc-ms-field--scanned');
                touched++;
            });

            if (touched > 0) {
                filled += touched;
                row.classList.add('is-dirty', 'has-scanned');
                row.classList.remove('is-saved');
                paintRow(row);
                setRowStatus(row, touched + (touched === 1 ? ' SKU filled' : ' SKUs filled') + ' — review, then Save', '');
            }
        });

        refreshFilledCount();

        if (btn) {
            btn.disabled = true;
            var label = labelOf(btn);
            if (label) {
                label.textContent = filled === 0 ? 'Nothing to fill' : 'Filled ' + filled;
            }
        }

        var T = global.FcAdminToast;
        if (T) {
            if (filled === 0) {
                T.show('No SKUs to fill — every proposal is already applied.');
            } else {
                T.success(filled + (filled === 1 ? ' SKU filled' : ' SKUs filled') + ' — nothing saved yet, press Save on each row.');
            }
        }
    }

    function runRescan(btn) {
        if (!canEdit || btn.disabled) {
            return;
        }
        var label = labelOf(btn);
        var was = label ? label.textContent : '';
        btn.disabled = true;
        if (label) {
            label.textContent = 'Scanning…';
        }

        fetch(API_SCAN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ csrf: csrf })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Scan failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                applyProposals(body.proposals || {});
                var T = global.FcAdminToast;
                if (T) {
                    T.success(
                        'Scanned the catalogue — ' + body.written + ' matched, ' +
                        body.kept + ' kept as researched.'
                    );
                }
            })
            .catch(function (err) {
                var T = global.FcAdminToast;
                if (T) {
                    T.error(err.message || 'Scan failed');
                }
            })
            .finally(function () {
                btn.disabled = false;
                if (label) {
                    label.textContent = was;
                }
            });
    }

    /* Restamps every field with the freshly scanned proposal, and re-counts what Fill would do. */
    function applyProposals(proposals) {
        var fillable = 0;
        rowsIn(document).forEach(function (row) {
            var slug = row.getAttribute('data-slug') || '';
            fieldsIn(row).forEach(function (field) {
                var proposal = proposals[slug + '|' + field.getAttribute('data-column')];
                var sku = proposal ? String(proposal.sku || '') : '';
                var note = proposal ? String(proposal.note || '') : '';

                if (sku && field.classList.contains('fc-ms-field--gap')) {
                    field.setAttribute('data-fc-ms-scan-sku', sku);
                    fillable++;
                } else {
                    field.removeAttribute('data-fc-ms-scan-sku');
                }
                if (note) {
                    field.setAttribute('data-fc-ms-scan-note', note);
                } else {
                    field.removeAttribute('data-fc-ms-scan-note');
                }
            });
        });

        var fillBtn = document.querySelector('[data-fc-ms-scan]');
        if (fillBtn) {
            fillBtn.disabled = fillable === 0;
            var label = labelOf(fillBtn);
            if (label) {
                label.textContent = fillable === 0
                    ? 'Nothing to fill'
                    : 'Fill ' + fillable + (fillable === 1 ? ' SKU' : ' SKUs');
            }
            var meta = fillBtn.querySelector('[data-fc-ms-fill-meta]');
            if (meta) {
                meta.textContent = fillable === 0
                    ? 'Run Scan to find proposals'
                    : 'Fills the fields for review — nothing is saved';
            }
        }
    }

    /* -------------------------------------------------------------------------- deep scan */

    /* Ranks the catalogue by product title for the gaps the catalogue Scan could not place.
       Nothing is written and nothing is filled: the leads are stamped on the fields so the
       existing suggestions popover can offer them, and a person still picks one. */
    function runDeepScan(btn) {
        if (btn.disabled) {
            return;
        }
        var label = labelOf(btn);
        var was = label ? label.textContent : '';
        btn.disabled = true;
        if (label) {
            label.textContent = 'Thinking…';
        }

        fetch(API_DEEP_SCAN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ csrf: csrf })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Deep scan failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                var shown = applyDeepSuggestions(body.suggestions || {});
                var T = global.FcAdminToast;
                if (!T) {
                    return;
                }
                // The scan covers the whole file, so the tally is the file's; `shown` only says
                // whether the filters currently in force are hiding all of it.
                var tally = 'Deep scan matched ' + body.matched + ' of ' + body.gaps + ' open gaps';
                if (body.matched === 0) {
                    T.show('Deep scan found no title matches for the ' + body.gaps + ' open gaps.');
                } else if (shown === 0) {
                    T.show(tally + ', none on this page — clear the filters to see them.');
                } else {
                    T.success(tally + ' — open a marked field to review. Nothing was filled in.');
                }
            })
            .catch(function (err) {
                var T = global.FcAdminToast;
                if (T) {
                    T.error(err.message || 'Deep scan failed');
                }
            })
            .finally(function () {
                btn.disabled = false;
                if (label) {
                    label.textContent = was;
                }
            });
    }

    /* Marks the fields that came back with leads. Returns how many gaps are visible here, counted
       by key: products.csv holds several rows per slug, so one gap can mark more than one field. */
    function applyDeepSuggestions(suggestions) {
        deepSuggestions = suggestions || {};

        var shown = {};
        rowsIn(document).forEach(function (row) {
            var slug = row.getAttribute('data-slug') || '';
            fieldsIn(row).forEach(function (field) {
                var key = slug + '|' + field.getAttribute('data-column');
                var hits = deepSuggestions[key];
                var count = hits && hits.length ? hits.length : 0;
                field.classList.toggle('fc-ms-field--deep', count > 0);
                if (count > 0) {
                    field.setAttribute('data-fc-ms-deep-count', String(count));
                    shown[key] = true;
                } else {
                    field.removeAttribute('data-fc-ms-deep-count');
                }
            });
        });

        return Object.keys(shown).length;
    }

    function deepFor(field) {
        var row = field.closest('[data-fc-ms-row]');
        if (!row) {
            return [];
        }
        var key = (row.getAttribute('data-slug') || '') + '|' + field.getAttribute('data-column');
        var hits = deepSuggestions[key];
        return hits && hits.length ? hits : [];
    }

    /* Deep leads head the popover; the SKU matcher's own list follows, minus anything already up. */
    function mergeSuggestions(deep, matches) {
        var seen = {};
        var out = deep.map(function (hit) {
            var sku = SKU.normalize(hit.sku);
            seen[sku] = true;
            return {
                sku: hit.sku,
                name: hit.name,
                image: hit.image,
                percent: hit.percent
            };
        });

        matches.forEach(function (item) {
            var sku = SKU.normalize(item && item.sku ? item.sku : item);
            if (!seen[sku]) {
                out.push(item);
            }
        });

        return out;
    }

    /* ----------------------------------------------------------------------- actions menu */

    function actionsMenu() {
        return document.querySelector('[data-fc-ms-actions]');
    }

    function closeActionsMenu() {
        var menu = actionsMenu();
        var panel = menu ? menu.querySelector('.fc-ms-actions__panel') : null;
        if (!panel || panel.hidden) {
            return false;
        }
        global.FC.components.DropdownRegistry.notifyClosed(menu);
        panel.hidden = true;
        menu.classList.remove('is-open');
        var toggle = menu.querySelector('[data-fc-ms-actions-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        return true;
    }

    function openActionsMenu(menu) {
        var panel = menu.querySelector('.fc-ms-actions__panel');
        if (!panel) {
            return;
        }
        // The admin allows one dropdown at a time, so the topbar's menus close as this one opens.
        global.FC.components.DropdownRegistry.openExclusive(menu, closeActionsMenu);
        panel.hidden = false;
        menu.classList.add('is-open');
        var toggle = menu.querySelector('[data-fc-ms-actions-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    }

    /* Throws away writable/missing-products.csv. Rows a person decided by hand go with it, which
       is why this asks first; the SKUs already saved into products.csv are untouched. */
    function runClear(btn) {
        if (!canEdit || btn.disabled) {
            return;
        }
        if (!global.confirm(
            'Clear the saved scan?\n\nThis deletes missing-products.csv, including any rows decided '
            + 'by hand. SKUs already saved on a row are not affected.'
        )) {
            return;
        }

        var label = labelOf(btn);
        var was = label ? label.textContent : '';
        var cleared = false;
        btn.disabled = true;
        if (label) {
            label.textContent = 'Clearing…';
        }

        fetch(API_CLEAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ csrf: csrf })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Clear failed');
                    }
                    return body;
                });
            })
            .then(function (body) {
                cleared = true;
                // The proposals stamped into the markup came from the file that just went.
                applyProposals({});
                var meta = btn.querySelector('[data-fc-ms-clear-meta]');
                if (meta) {
                    meta.textContent = 'No saved scan to clear';
                }
                var T = global.FcAdminToast;
                if (T) {
                    T.success(body.removed
                        ? 'Scan cleared — missing-products.csv deleted.'
                        : 'There was no saved scan to clear.');
                }
            })
            .catch(function (err) {
                var T = global.FcAdminToast;
                if (T) {
                    T.error(err.message || 'Clear failed');
                }
            })
            .finally(function () {
                if (label) {
                    label.textContent = was;
                }
                // Nothing left to clear once the file is gone.
                btn.disabled = cleared;
                if (cleared) {
                    btn.setAttribute('aria-disabled', 'true');
                }
            });
    }

    function saveRow(row) {
        if (!canEdit || row.getAttribute('data-saving') === '1') {
            return;
        }
        var rowIndex = parseInt(row.getAttribute('data-row-index'), 10);
        if (!Number.isFinite(rowIndex)) {
            return;
        }

        var fields = {};
        fieldsIn(row).forEach(function (field) {
            var input = inputIn(field);
            if (input) {
                fields[input.name] = input.value;
            }
        });

        var btn = row.querySelector('[data-fc-ms-save]');
        row.setAttribute('data-saving', '1');
        row.classList.add('is-saving');
        if (btn) {
            btn.disabled = true;
        }
        setRowStatus(row, 'Saving…', '');

        fetch(API_UPDATE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ rowIndex: rowIndex, fields: fields, csrf: csrf })
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
                row.classList.remove('is-dirty');
                // The row keeps its place until the next load, so the list cannot jump under the cursor.
                row.classList.add('is-saved');
                setRowStatus(row, 'Saved', 'saved');
                // The scan marks have done their job once the row is on disk.
                row.classList.remove('has-scanned');
                fieldsIn(row).forEach(function (field) {
                    field.classList.remove('fc-ms-field--scanned');
                    // The lead has been acted on; the mark would only point at a settled field.
                    field.classList.remove('fc-ms-field--deep');
                    field.removeAttribute('data-fc-ms-deep-count');
                });
                paintRow(row);
                refreshFilledCount();
                var T = global.FcAdminToast;
                if (T) {
                    T.success('Saved to products.csv');
                }
            })
            .catch(function (err) {
                setRowStatus(row, err.message || 'Save failed', 'error');
                var T = global.FcAdminToast;
                if (T) {
                    T.error(err.message || 'Save failed');
                }
            })
            .finally(function () {
                row.removeAttribute('data-saving');
                row.classList.remove('is-saving');
                if (btn) {
                    btn.disabled = false;
                }
            });
    }

    /* ---------------------------------------------------------------------------- hydrate */

    function bindRoot(root) {
        if (root.getAttribute('data-fc-ms-bound') === '1') {
            return;
        }
        root.setAttribute('data-fc-ms-bound', '1');
        pageRoot = root;

        root.addEventListener('input', function (e) {
            var filterInput = e.target.closest
                ? e.target.closest('[data-fc-ms-sku-suggest-filter]')
                : null;
            if (filterInput) {
                var list = openSuggestWrap
                    ? openSuggestWrap.querySelector('[data-fc-ms-sku-suggest-list]')
                    : null;
                if (list) {
                    list.innerHTML = suggestListBodyHtml(
                        openSuggestMatches,
                        openSuggestSeed,
                        filterInput.value
                    );
                }
                return;
            }

            var input = e.target.closest ? e.target.closest('.fc-sp-field-control--sku') : null;
            if (!input) {
                return;
            }
            var field = input.closest('[data-fc-ms-field]');
            var row = input.closest('[data-fc-ms-row]');
            if (row) {
                row.classList.add('is-dirty');
                row.classList.remove('is-saved');
                setRowStatus(row, '', '');
            }
            if (field) {
                paintStatus(field);
            }
            // The row chip and the toolbar tally both read gap counts, so keep them live as you type.
            if (row) {
                paintRow(row);
            }
            refreshFilledCount();
        });

        root.addEventListener('change', function (e) {
            var toggle = e.target.closest
                ? e.target.closest('[data-fc-ms-only-filled]')
                : null;
            if (!toggle) {
                return;
            }
            root.classList.toggle('is-only-filled', toggle.checked);
            refreshFilledCount();
        });

        root.addEventListener('click', function (e) {
            var target = e.target;

            var thumbView = target.closest('[data-fc-ms-thumb-view]');
            if (thumbView) {
                e.preventDefault();
                openThumbGallery(thumbView);
                return;
            }

            var actionsToggle = target.closest('[data-fc-ms-actions-toggle]');
            if (actionsToggle) {
                e.preventDefault();
                // Or the document handler below would close it again in the same click.
                e.stopPropagation();
                var menu = actionsMenu();
                var panel = menu ? menu.querySelector('.fc-ms-actions__panel') : null;
                if (panel && panel.hidden) {
                    openActionsMenu(menu);
                } else {
                    closeActionsMenu();
                }
                return;
            }

            var rescanBtn = target.closest('[data-fc-ms-rescan]');
            if (rescanBtn) {
                e.preventDefault();
                closeActionsMenu();
                runRescan(rescanBtn);
                return;
            }

            var deepBtn = target.closest('[data-fc-ms-deep-scan]');
            if (deepBtn) {
                e.preventDefault();
                closeActionsMenu();
                runDeepScan(deepBtn);
                return;
            }

            var clearBtn = target.closest('[data-fc-ms-clear]');
            if (clearBtn) {
                e.preventDefault();
                closeActionsMenu();
                runClear(clearBtn);
                return;
            }

            var scanBtn = target.closest('[data-fc-ms-scan]');
            if (scanBtn) {
                e.preventDefault();
                closeActionsMenu();
                // The index decides found vs missing, so wait for it or the tint would be guesswork.
                if (SKU) {
                    SKU.ensureIndex().then(function () {
                        runScan(root, scanBtn);
                    });
                }
                return;
            }

            var copyBtn = target.closest('[data-fc-sp-copy-for]');
            if (copyBtn) {
                e.preventDefault();
                var control = document.getElementById(copyBtn.getAttribute('data-fc-sp-copy-for'));
                copyFieldButton.copy(control, copyBtn);
                return;
            }

            var previewOpen = target.closest('[data-fc-ms-sku-preview-open]');
            if (previewOpen) {
                e.preventDefault();
                e.stopPropagation();
                openPreview(previewOpen.getAttribute('data-fc-ms-sku-preview-open') || '');
                return;
            }

            var previewClose = target.closest('[data-fc-ms-sku-preview-close]');
            if (previewClose) {
                e.preventDefault();
                e.stopPropagation();
                closePreview();
                return;
            }

            var useBtn = target.closest('[data-fc-ms-sku-use]');
            if (useBtn) {
                e.preventDefault();
                var wrap = useBtn.closest('.fc-sp-field-input-wrap--sku');
                var useInput = wrap ? wrap.querySelector('.fc-sp-field-control--sku') : null;
                if (useInput) {
                    useInput.value = useBtn.getAttribute('data-fc-ms-sku-use') || '';
                    useInput.dispatchEvent(new Event('input', { bubbles: true }));
                    useInput.focus();
                }
                closeSuggest();
                return;
            }

            var checkBtn = target.closest('[data-fc-sp-sku-check]');
            if (checkBtn) {
                e.preventDefault();
                var checkWrap = checkBtn.closest('.fc-sp-field-input-wrap--sku');
                if (openSuggestWrap === checkWrap) {
                    closeSuggest();
                } else {
                    openSuggest(checkWrap);
                }
                return;
            }

            var saveBtn = target.closest('[data-fc-ms-save]');
            if (saveBtn) {
                e.preventDefault();
                var saveRowEl = saveBtn.closest('[data-fc-ms-row]');
                if (saveRowEl) {
                    saveRow(saveRowEl);
                }
            }
        });

        // Enter saves the row it was pressed in, the way the edit modal's form submit does.
        root.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && (closePreview() || closeSuggest() || closeActionsMenu())) {
                e.preventDefault();
                return;
            }
            var thumb = e.target.closest ? e.target.closest('[data-fc-ms-thumb-view]') : null;
            if (thumb && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                openThumbGallery(thumb);
                return;
            }

            if (e.key !== 'Enter') {
                return;
            }
            var input = e.target.closest ? e.target.closest('.fc-sp-field-control--sku') : null;
            if (!input) {
                return;
            }
            e.preventDefault();
            var row = input.closest('[data-fc-ms-row]');
            if (row) {
                saveRow(row);
            }
        });

        if (!docClickBound) {
            docClickBound = true;
            document.addEventListener('click', function (e) {
                var menu = actionsMenu();
                if (menu && !menu.contains(e.target)) {
                    closeActionsMenu();
                }
                if (!openSuggestWrap) {
                    return;
                }
                if (!openSuggestWrap.contains(e.target)) {
                    closeSuggest();
                }
            });
        }
    }

    function hydrateFromServer(container) {
        var root = (container || document).querySelector('[data-fc-missing-sku-server]');
        if (!root) {
            return;
        }

        var boot = readBootstrap();
        csrf = String(boot.csrf || '');
        canEdit = !!boot.canEdit;
        // Leads belong to the page that asked for them; a re-render has none until Deep Scan runs.
        deepSuggestions = {};

        bindRoot(root);

        if (!SKU) {
            return;
        }
        // Fields render before the catalogue index resolves; ticks and thumbnails land when it does.
        SKU.ensureIndex().then(function () {
            rowsIn(root).forEach(paintRow);
            refreshFilledCount();
        });
    }

    global.FC.PageRegistry.register('products/system-products/missing-sku', new MissingSkuPage());
})(window);
