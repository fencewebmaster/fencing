/**
 * Planner Step 3 — project / section summary modal.
 */
(function(global) {
    'use strict';

    var FC_POST_OPT_LABELS = {
        'opt-1': 'Base Plated',
        'opt-2': 'Cement In',
        'opt-3': 'Wall Fix',
        'opt-4': 'Core Drilled',
        'opt-5': '135 Degree Angle'
    };

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildQuoteShareUrl(quoteId) {
        var id = String(quoteId == null ? '' : quoteId).trim();
        if (!id) {
            return '';
        }
        if (typeof planner_share_url !== 'undefined' && planner_share_url) {
            return String(planner_share_url).trim();
        }
        try {
            var origin = window.location.origin;
            var path = window.location.pathname.replace(/\/[^/]*$/, '');
            return origin + path + '?qid=' + encodeURIComponent(id);
        } catch (e) {
            return '';
        }
    }

    function formatMm(val, fallback) {
        var n = parseInt(String(val == null ? '' : val).replace(/,/g, ''), 10);
        if (!Number.isFinite(n) || n <= 0) {
            return fallback != null ? fallback : '—';
        }
        return n.toLocaleString() + 'mm';
    }

    function hasDisplayValue(val) {
        if (val == null) {
            return false;
        }
        var s = String(val).trim();
        return s.length > 0 && s !== '—';
    }

    function pushRowIf(rows, label, value, opts) {
        if (!hasDisplayValue(value)) {
            return;
        }
        var row = { label: label, value: String(value) };
        if (opts) {
            Object.keys(opts).forEach(function(key) {
                row[key] = opts[key];
            });
        }
        rows.push(row);
    }

    function isProjectPlanPage() {
        try {
            return typeof $ !== 'undefined' && $('.fc-project-plan-page').length > 0;
        } catch (e) {
            return false;
        }
    }

    function getSectionPanelContainerRoot(tabIdx) {
        try {
            if (Number.isFinite(tabIdx) && tabIdx >= 0) {
                var pp = document.getElementById('pp-' + tabIdx);
                if (pp) {
                    var scoped = pp.querySelector('.fencing-panel-container');
                    if (scoped) {
                        return scoped;
                    }
                }
            }
            if (isProjectPlanPage()) {
                return null;
            }
            return (
                document.querySelector('.fc-planner-page .fencing-panel-container') ||
                document.querySelector('.fencing-panel-container')
            );
        } catch (e) {
            return null;
        }
    }

    function getSummarySectionCount() {
        if (isProjectPlanPage()) {
            var sections = document.querySelectorAll('#fc-fence-list .fc-project-plan-section');
            if (sections.length > 0) {
                return sections.length;
            }
        }
        if (typeof HELPER !== 'undefined' && typeof HELPER.getFenceSectionTabCount === 'function') {
            return HELPER.getFenceSectionTabCount();
        }
        if (typeof fcGetPlannerSectionTabs$ === 'function') {
            return fcGetPlannerSectionTabs$().length;
        }
        return 1;
    }

    function getActivePlannerTabIndex() {
        try {
            if (isProjectPlanPage() && typeof $ !== 'undefined') {
                var $stuck = $('#fc-fence-list .fc-project-plan-section-head--stuck').first().closest(
                    '.fc-project-plan-section'
                );
                if ($stuck.length) {
                    var stuckIdx = parseInt($stuck.attr('data-section-index'), 10);
                    if (Number.isFinite(stuckIdx) && stuckIdx >= 0) {
                        return stuckIdx;
                    }
                }
            }
            if (typeof $ !== 'undefined') {
                var idx = $('.fencing-tab.fencing-tab-selected').index();
                if (Number.isFinite(idx) && idx >= 0) {
                    return idx;
                }
            }
        } catch (e) {}
        return -1;
    }

    function resolveSummaryActiveSection(override) {
        if (Number.isFinite(override) && override >= 0) {
            return override;
        }
        return getActivePlannerTabIndex();
    }

    function readSectionCart(tabIdx, slug) {
        var canon = slug;
        if (typeof normalizeFenceStyleSlug === 'function') {
            canon = normalizeFenceStyleSlug(canon);
        }
        if (canon === 'slat_fence') {
            canon = 'slat';
        }
        var key = 'cart_items-' + (tabIdx + 1) + '-' + canon;
        try {
            var raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function barrGateExtraPanelCount(slug, custom_fence, tabRow, calc, tabIdx) {
        if (slug !== 'barr') {
            return 0;
        }
        if (
            typeof FENCES === 'undefined' ||
            !FENCES.cartItems ||
            typeof FENCES.cartItems.shouldBarrGateAddExtraPanel !== 'function'
        ) {
            return 0;
        }
        var root = getSectionPanelContainerRoot(tabIdx);
        var ctx = {
            fenceSlug: 'barr',
            fenceInfo: custom_fence || [],
            tabInfo: tabRow || [],
            calc: calc || null
        };
        if (!FENCES.cartItems.hasBarrGate(ctx, root || document)) {
            return 0;
        }
        return FENCES.cartItems.shouldBarrGateAddExtraPanel(ctx, []) ? 1 : 0;
    }

    function countPanelsFromLiveDom(slug, tabIdx, custom_fence, tabRow, calc) {
        try {
            var root = getSectionPanelContainerRoot(tabIdx);
            if (!root) {
                return null;
            }
            if (slug === 'slat_fence_infill') {
                return null;
            }
            var n = root.querySelectorAll(
                '.panel-item:not(.fencing-panel-gate,.fencing-raked-panel)'
            ).length;
            n += barrGateExtraPanelCount(slug, custom_fence, tabRow, calc, tabIdx);
            return n > 0 ? n : null;
        } catch (e) {
            return null;
        }
    }

    function countInfillPanelsFromStorage(custom_fence, tabRow, calc) {
        var ctx = { fenceInfo: custom_fence, tabInfo: tabRow };
        if (typeof SlatFence !== 'undefined') {
            var fromField = parseInt(String(SlatFence.getContextFieldValue(ctx, 'panel_count', '')), 10);
            if (Number.isFinite(fromField) && fromField > 0) {
                return fromField;
            }
        }
        var fromCalc = parseInt(calc?.long_panel?.count, 10);
        return Number.isFinite(fromCalc) && fromCalc > 0 ? fromCalc : 0;
    }

    function countPostsFromLiveDom(tabIdx) {
        try {
            var root = getSectionPanelContainerRoot(tabIdx);
            if (!root) {
                return null;
            }
            var total = 0;
            root.querySelectorAll('[data-cart-key="panel_post"], [data-cart-key="panel_post_corner"], [data-cart-key="raked_post"]').forEach(function(el) {
                if (el.classList.contains('panel-no-post')) {
                    return;
                }
                total += 1;
            });
            return total > 0 ? total : null;
        } catch (e) {
            return null;
        }
    }

    function countPostsFromCart(cart, context) {
        if (!Array.isArray(cart) || !cart.length) {
            return null;
        }
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.aggregatePostOptQtyFromCart === 'function') {
            var agg = SlatFence.aggregatePostOptQtyFromCart(cart, context);
            if (agg && agg.total > 0) {
                return agg.total;
            }
        }
        var total = 0;
        cart.forEach(function(item) {
            var itemSlug = item && item.slug ? String(item.slug) : '';
            if (/^(panel_post|panel_post_corner|raked_post)/.test(itemSlug)) {
                total += parseInt(item.qty, 10) || 0;
            }
        });
        return total > 0 ? total : null;
    }

    function postTypeLabelFromSlug(slug, optionMeta) {
        if (optionMeta && optionMeta.title) {
            return String(optionMeta.title);
        }
        return FC_POST_OPT_LABELS[String(slug || '')] || (slug ? String(slug).replace(/-/g, ' ') : '—');
    }

    function formatSummaryMultilineHtml(value) {
        return String(value == null ? '' : value)
            .split('\n')
            .map(function(line) {
                var trimmed = line.trim();
                if (trimmed === 'Gate ONLY') {
                    return (
                        '<span class="fc-planner-summary-gate-only">' + escapeHtml(line) + '</span>'
                    );
                }
                if (/^No Post/i.test(trimmed) || trimmed === 'None') {
                    return (
                        '<span class="fc-planner-summary-no-post">' + escapeHtml(line) + '</span>'
                    );
                }
                return escapeHtml(line);
            })
            .join('<br>');
    }

    function readTabRow(tabIdx) {
        try {
            var raw = localStorage.getItem('custom_fence-' + tabIdx);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function sectionStyleSlug(tabRow) {
        if (!tabRow || !tabRow[0]) {
            return '';
        }
        var raw = tabRow[0].style || tabRow[0].fence || '';
        return typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : raw;
    }

    function sectionIsCalculated(tabRow, slug) {
        if (!tabRow || !tabRow[0]) {
            return false;
        }
        var row = tabRow[0];
        if (row.isCalculateByStyle && slug && row.isCalculateByStyle[slug]) {
            return true;
        }
        return !!row.isCalculate;
    }

    function resolveRakedOption(custom_fence, info, sideKey, rakedSlug) {
        var selected = get_field_multi_option_value(custom_fence, info, sideKey, rakedSlug);
        if (!selected || !selected.val || selected.val === 'none') {
            return null;
        }
        var fields = get_field_multi_options(custom_fence, info, sideKey);
        var field = get_field_by_slug(fields, rakedSlug);
        if (!field || !field.options) {
            return null;
        }
        var opt = get_field_by_slug(field.options, selected.val);
        if (Array.isArray(opt)) {
            return opt[0] || null;
        }
        return opt || null;
    }

    function sideSummaryLines(custom_fence, info, side) {
        var sideKey = side + '_side';
        var optionSlug = side === 'left' ? 'left_option' : 'right_option';
        var lines = [];
        var sideOpt = get_field_options(custom_fence, info, sideKey, optionSlug)[0];

        if (sideOpt && sideOpt.slug === 'no-post') {
            lines.push('No Post');
        } else if (sideOpt && sideOpt.slug === 'no-post-swivel-bracket') {
            lines.push('No Post (Swivel Bracket)');
        } else if (sideOpt && sideOpt.slug === 'yes-post') {
            lines.push('Standard Post');
        } else if (sideOpt && sideOpt.title) {
            lines.push(sideOpt.title);
        } else {
            lines.push('Standard Post');
        }

        var sidePostSetting = get_field_multi_option_value(custom_fence, info, sideKey, 'post_option');
        if (sidePostSetting && sidePostSetting.val && sideOpt && sideOpt.slug === 'yes-post') {
            var sidePostOpt = get_field_options(custom_fence, info, sideKey, 'post_option')[0];
            lines.push('Post Type: ' + postTypeLabelFromSlug(sidePostSetting.val, sidePostOpt));
        }

        var rakedSlug = side === 'left' ? 'left_raked' : 'right_raked';
        var rakedOpt = resolveRakedOption(custom_fence, info, sideKey, rakedSlug);
        if (rakedOpt && rakedOpt.size && parseInt(rakedOpt.size.height, 10) > 0) {
            var label = rakedOpt.title && rakedOpt.title !== 'Nil' ? rakedOpt.title : '';
            if (!label) {
                var step =
                    parseInt(rakedOpt.size.height, 10) - parseInt(rakedOpt.size.width, 10);
                if (Number.isFinite(step) && step > 0) {
                    label = rakedOpt.size.height + 'H - ' + step + ' Step-Up';
                } else {
                    label = rakedOpt.size.height + 'H';
                }
            }
            lines.push('Raked Panel: ' + label);
        }

        return lines;
    }

    function countPanels(calc, slug, custom_fence, info, tabRow, tabIdx) {
        if (!calc) {
            return 0;
        }

        if (isProjectPlanPage() || tabIdx === getActivePlannerTabIndex()) {
            var fromDom = countPanelsFromLiveDom(slug, tabIdx, custom_fence, tabRow, calc);
            if (fromDom != null) {
                return fromDom;
            }
        }

        if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(slug)) {
            if (slug === 'slat_fence_infill') {
                return countInfillPanelsFromStorage(custom_fence, tabRow, calc);
            }
            return SlatFence.slatPanelCountFromCalc(calc);
        }

        var longCount = parseInt(calc.long_panel?.count, 10) || 0;
        var shortCount = parseInt(calc.short_panel?.count, 10) || 0;
        if (longCount > 0 || shortCount > 0) {
            return longCount + shortCount + barrGateExtraPanelCount(slug, custom_fence, tabRow, calc, tabIdx);
        }

        var evenCount = parseInt(calc.even_panel?.count, 10) || 0;
        var fullCount = parseInt(calc.full_panel?.count, 10) || 0;
        return Math.max(evenCount, fullCount);
    }

    function countSlatGatePosts(context) {
        if (typeof SlatFence === 'undefined') {
            return 0;
        }
        var gatePosts = 0;
        ['gate_first_post', 'gate_second_post'].forEach(function(key) {
            var val = SlatFence.getFenceSettingValue(context, 'gate', key, '');
            if (val) {
                gatePosts += 1;
            }
        });
        return gatePosts;
    }

    function countPostsForSection(calc, custom_fence, info, slug, panelCount, tabIdx, tabRow) {
        var context = { fenceInfo: custom_fence, tabInfo: tabRow, calc: calc };
        var cart = readSectionCart(tabIdx, slug);

        if (isProjectPlanPage() || tabIdx === getActivePlannerTabIndex()) {
            var fromDom = countPostsFromLiveDom(tabIdx);
            if (fromDom != null) {
                return fromDom;
            }
        }

        var fromCart = countPostsFromCart(cart, context);
        if (fromCart != null) {
            return fromCart;
        }

        var n = parseInt(panelCount, 10);
        var gateCount = parseInt(calc?.gate?.count, 10) || 0;

        if (!Number.isFinite(n) || n <= 0) {
            if (gateCount > 0 && typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug)) {
                var gateOnlyPosts = countSlatGatePosts(context);
                return gateOnlyPosts > 0 ? gateOnlyPosts : 2;
            }
            return 0;
        }

        if (slug === 'slat_fence_infill') {
            return n + 1;
        }

        var postW =
            typeof FENCE !== 'undefined' && typeof FENCE.get === 'function'
                ? parseInt(FENCE.get(slug, 'post'), 10)
                : 50;
        var removedMm =
            typeof FENCE !== 'undefined' && typeof FENCE.minus_posts === 'function'
                ? parseInt(FENCE.minus_posts(custom_fence), 10) || 0
                : 0;
        var removedCount =
            postW > 0 && removedMm > 0 ? Math.round(removedMm / postW) : 0;

        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug)) {
            var totalPostsMm = SlatFence.computeSlatTotalPostsMm(n, postW, removedMm);
            var posts = postW > 0 ? Math.round(totalPostsMm / postW) : Math.max(0, n + 1 - removedCount);
            if (gateCount > 0) {
                var slatGatePosts = countSlatGatePosts(context);
                posts += slatGatePosts > 0 ? slatGatePosts : 2;
            }
            return Math.max(0, posts);
        }

        return Math.max(0, n + 1 - removedCount);
    }

    var GLASS_POOL_PANEL_HEIGHT_MM = 1200;

    function resolveGlassPoolPanelHeightMm(calc) {
        var h = parseInt(String(calc?.fence_size?.height || '').replace(/,/g, ''), 10);
        if (Number.isFinite(h) && h > 0) {
            return h;
        }
        return GLASS_POOL_PANEL_HEIGHT_MM;
    }

    function hasFencePanelRun(calc) {
        if (!calc) {
            return false;
        }
        return (
            (parseInt(calc.long_panel?.count, 10) || 0) +
                (parseInt(calc.even_panel?.count, 10) || 0) +
                (parseInt(calc.short_panel?.count, 10) || 0) +
                (parseInt(calc.full_panel?.count, 10) || 0) >
            0
        );
    }

    /** Gate leaf/opening size for Fence Summary Panel Size — e.g. "1560H x 975W". */
    function gatePanelSizeLabel(calc, slug, custom_fence, tabRow) {
        var gateRow = (custom_fence || []).filter(function(item) {
            return item && item.control_key === 'gate';
        })[0];
        if (!gateRow || !gateRow.settings) {
            return '';
        }

        var gateCount = parseInt(calc?.gate?.count, 10) || 0;
        var gateOnly = !!gateRow.settings.gateOnly;
        if (!gateOnly && gateCount <= 0) {
            return '';
        }

        var context = { fenceInfo: custom_fence, tabInfo: tabRow, calc: calc };
        var w = 0;
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.getGateDisplayWidthMm === 'function') {
            w = SlatFence.getGateDisplayWidthMm(slug, [gateRow], calc);
        }
        if (!Number.isFinite(w) || w <= 0) {
            w =
                parseInt(String(calc?.gate?.width || gateRow.settings.size || '').replace(/,/g, ''), 10) ||
                0;
        }

        var h = 0;
        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug)) {
            h = SlatFence.resolveGateSlatHeightMm
                ? SlatFence.resolveGateSlatHeightMm(context, calc)
                : 0;
        } else if (slug === 'glass_pool') {
            h = resolveGlassPoolPanelHeightMm(calc);
        } else {
            h = parseInt(String(calc?.fence_size?.height || '').replace(/,/g, ''), 10);
        }

        if (Number.isFinite(h) && h > 0 && Number.isFinite(w) && w > 0) {
            return Math.round(h) + 'H x ' + Math.round(w) + 'W';
        }
        if (Number.isFinite(w) && w > 0) {
            return Math.round(w) + 'W';
        }
        return '';
    }

    function primaryPanelSizeLabel(calc, slug, custom_fence, tabRow) {
        if (!calc) {
            return '';
        }

        var gateRow = (custom_fence || []).filter(function(item) {
            return item && item.control_key === 'gate';
        })[0];
        var gateOnly = !!(gateRow && gateRow.settings && gateRow.settings.gateOnly);

        // Gate ONLY (or gate with no fence panel run): Panel Size is the gate size.
        if (gateOnly || !hasFencePanelRun(calc)) {
            var gateSize = gatePanelSizeLabel(calc, slug, custom_fence, tabRow);
            if (gateSize) {
                return gateSize;
            }
        }

        var h =
            slug === 'glass_pool'
                ? resolveGlassPoolPanelHeightMm(calc)
                : parseInt(String(calc.fence_size?.height || '').replace(/,/g, ''), 10);
        var w =
            parseInt(calc.long_panel?.length, 10) ||
            parseInt(calc.even_panel?.length, 10) ||
            parseInt(calc.short_panel?.length, 10) ||
            parseInt(calc.full_panel?.length, 10) ||
            0;

        var parts = [];
        if (Number.isFinite(h) && h > 0 && Number.isFinite(w) && w > 0) {
            parts.push(Math.round(h) + 'H x ' + Math.round(w) + 'W');
        } else if (Number.isFinite(w) && w > 0) {
            parts.push(Math.round(w) + 'W');
        }

        if (parseInt(calc.short_panel?.count, 10) > 0 && parseInt(calc.short_panel?.length, 10) > 0) {
            var sw = Math.round(parseInt(calc.short_panel.length, 10));
            if (!parts.length || sw !== w) {
                parts.push('Short: ' + (Number.isFinite(h) && h > 0 ? Math.round(h) + 'H x ' : '') + sw + 'W');
            }
        }

        if (!parts.length && slug === 'slat_fence_infill') {
            var opening = parseInt(calc.long_panel?.length, 10) || parseInt(calc.full_panel?.length, 10);
            if (opening > 0) {
                parts.push('Opening: ' + Math.round(opening) + 'W');
            }
        }

        if (!parts.length) {
            var gateFallback = gatePanelSizeLabel(calc, slug, custom_fence, tabRow);
            if (gateFallback) {
                return gateFallback;
            }
        }

        return parts.length ? parts.join('; ') : '';
    }

    function offcutPanelsSummaryValue(calc) {
        if (!calc) {
            return '';
        }
        var count = parseInt(calc.offcut_panel?.count, 10);
        if (!Number.isFinite(count) || count <= 0) {
            return '';
        }
        var length = parseInt(calc.offcut_panel?.length, 10);
        if (!Number.isFinite(length) || length <= 0) {
            return String(count);
        }
        var h = parseInt(String(calc.fence_size?.height || '').replace(/,/g, ''), 10);
        if (Number.isFinite(h) && h > 0) {
            return count + ' x ' + Math.round(h) + 'H x ' + Math.round(length) + 'W';
        }
        return count + ' x ' + Math.round(length) + 'W';
    }

    function fenceHeightSummaryValue(calc, info, slug) {
        if (slug === 'glass_pool') {
            return formatMm(resolveGlassPoolPanelHeightMm(calc), '');
        }
        if (info && info.panel_group === 'a') {
            return '';
        }
        if (slug === 'slat_fence_infill') {
            return '';
        }
        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug)) {
            return '';
        }
        var h = parseInt(String(calc?.fence_size?.height || '').replace(/,/g, ''), 10);
        if (!Number.isFinite(h) || h <= 0) {
            return '';
        }
        return formatMm(h, '');
    }

    function appendSlatGateHeightAndRows(lines, custom_fence, calc, slug, tabRow) {
        if (typeof SlatFence === 'undefined' || !SlatFence.isMainSlatSlug(slug)) {
            return;
        }

        var context = { fenceInfo: custom_fence, tabInfo: tabRow, calc: calc };
        var gateHm = SlatFence.resolveGateSlatHeightMm
            ? SlatFence.resolveGateSlatHeightMm(context, calc)
            : 0;

        if (Number.isFinite(gateHm) && gateHm > 0) {
            lines.push('Height: ' + formatMm(gateHm, ''));
        }

        var pitch = SlatFence.resolveSlatPanelPitchInputs
            ? SlatFence.resolveSlatPanelPitchInputs(context, slug)
            : null;
        if (
            pitch &&
            Number.isFinite(gateHm) &&
            gateHm > 0 &&
            typeof SlatFence.countSlatPanelRowsFromMaxHeightMm === 'function'
        ) {
            var gateSlatRows = SlatFence.countSlatPanelRowsFromMaxHeightMm(
                gateHm,
                pitch.sizePitch,
                pitch.gapPitch
            );
            if (gateSlatRows > 0) {
                lines.push('Slat Rows: ' + gateSlatRows);
            }
        }
    }

    function gateSummaryLines(custom_fence, calc, slug, tabRow) {
        var gateRow = (custom_fence || []).filter(function(item) {
            return item && item.control_key === 'gate';
        })[0];
        if (!gateRow || !gateRow.settings) {
            return ['None'];
        }

        if (gateRow.settings.gateOnly) {
            var goLines = ['Gate ONLY'];
            var goWidth = calc?.gate?.width || gateRow.settings.size;
            if (goWidth) {
                goLines.push('Width: ' + formatMm(goWidth));
            }
            appendSlatGateHeightAndRows(goLines, custom_fence, calc, slug, tabRow);
            return goLines;
        }

        var gateCount = parseInt(calc?.gate?.count, 10) || 0;
        if (gateCount <= 0) {
            return ['None'];
        }

        var fields = gateRow.settings.fields || [];
        var typeField = fields.find(function(f) {
            return f && f.key === 'gate_type';
        });
        var useStdField = fields.find(function(f) {
            return f && f.key === 'use_std';
        });
        var isCustom = useStdField && useStdField.val === false;
        var typeLabel =
            typeField && typeField.val === 'double'
                ? 'Double'
                : isCustom
                  ? 'Custom'
                  : 'Standard';

        var lines = ['Type: ' + typeLabel];
        var width = calc?.gate?.width || gateRow.settings.size;
        if (width) {
            lines.push('Width: ' + formatMm(width));
        }

        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug)) {
            appendSlatGateHeightAndRows(lines, custom_fence, calc, slug, tabRow);
        } else if (calc?.fence_size?.height) {
            lines.push('Height: ' + formatMm(calc.fence_size.height));
        }

        var placement = fields.find(function(f) {
            return f && f.key === 'gate_position';
        });
        if (placement && placement.val) {
            lines.push('Position: ' + String(placement.val).replace(/_/g, ' '));
        }

        var swing = fields.find(function(f) {
            return f && f.key === 'gate_swing';
        });
        if (swing && swing.val) {
            lines.push('Swing: ' + String(swing.val).replace(/_/g, ' '));
        }

        if (parseInt(calc?.gate_hinge_panel?.count, 10) > 0) {
            lines.push(
                'Hinge panel: ' + formatMm(calc.gate_hinge_panel.width)
            );
        }

        return lines;
    }

    function slatSummaryRows(custom_fence, info, calc, slug, tabRow) {
        if (typeof SlatFence === 'undefined' || !SlatFence.isSlatLike(slug)) {
            return [];
        }

        var rows = [];
        var context = { fenceInfo: custom_fence, tabInfo: tabRow, calc: calc };

        var gap = SlatFence.getGapDisplayLabelMm
            ? SlatFence.getGapDisplayLabelMm(custom_fence, info)
            : null;
        if (gap != null) {
            rows.push({ label: 'Slat Gap', value: gap + 'mm' });
        }

        var size = SlatFence.getSizeMm ? SlatFence.getSizeMm(custom_fence, null, slug) : null;
        if (size != null) {
            rows.push({ label: 'Slat Size', value: size + 'mm' });
        }

        var pitch = SlatFence.resolveSlatPanelPitchInputs
            ? SlatFence.resolveSlatPanelPitchInputs(context, slug)
            : null;
        var fenceHm = SlatFence.resolveSlatFenceHeightMm
            ? SlatFence.resolveSlatFenceHeightMm(context, calc)
            : 0;

        if (
            pitch &&
            Number.isFinite(fenceHm) &&
            fenceHm > 0 &&
            typeof SlatFence.countSlatPanelRowsFromMaxHeightMm === 'function'
        ) {
            var slatRows = SlatFence.countSlatPanelRowsFromMaxHeightMm(
                fenceHm,
                pitch.sizePitch,
                pitch.gapPitch
            );
            if (slatRows > 0) {
                rows.push({ label: 'Slat Rows', value: String(slatRows) });
            }
        }

        if (Number.isFinite(fenceHm) && fenceHm > 0) {
            rows.push({ label: 'Fence Height', value: formatMm(fenceHm, '') });
        }

        return rows;
    }

    function panelOptionLabel(custom_fence, info) {
        var opt = get_field_options(custom_fence, info, 'panel_options', 'panel_option')[0];
        if (!opt) {
            return '';
        }
        if (opt.title) {
            return opt.title;
        }
        if (opt.slug) {
            return String(opt.slug).replace(/_/g, ' ');
        }
        return '';
    }

    function sectionHeaderMeta(tabIdx, fenceName) {
        return {
            sectionLabel: 'Section ' + (tabIdx + 1),
            fenceName: fenceName ? String(fenceName) : ''
        };
    }

    function renderSectionTitleHtml(section) {
        var label = escapeHtml(section.sectionLabel || '');
        var name = section.fenceName ? escapeHtml(section.fenceName) : '';
        var html = '<span class="fc-planner-summary-section__title">';
        html += '<span class="fc-planner-summary-section__badge">' + label + '</span>';
        if (name) {
            html += '<span class="fc-planner-summary-section__name">' + name + '</span>';
        }
        html += '</span>';
        return html;
    }

    function buildSectionSummary(tabIdx) {
        var tabRow = readTabRow(tabIdx);
        var slug = sectionStyleSlug(tabRow);
        if (!slug) {
            return Object.assign(sectionHeaderMeta(tabIdx, ''), {
                incomplete: true,
                rows: [{ label: 'Status', value: 'No fence style selected' }]
            });
        }

        var info = typeof fc_data !== 'undefined' ? fc_data[slug] : null;
        var custom_fence =
            typeof readCustomFenceSegment === 'function'
                ? readCustomFenceSegment(tabIdx, slug)
                : [];
        var styleTitle =
            typeof fcFenceSectionStyleTitle === 'function'
                ? fcFenceSectionStyleTitle(tabIdx)
                : slug.replace(/_/g, ' ');

        if (!sectionIsCalculated(tabRow, slug)) {
            return Object.assign(sectionHeaderMeta(tabIdx, styleTitle), {
                incomplete: true,
                rows: [
                    { label: 'Fence Style', value: styleTitle },
                    { label: 'Status', value: 'Not calculated — run Calculate on Step 2' }
                ]
            });
        }

        var calc =
            typeof calculate_fences === 'function'
                ? calculate_fences({ tab: tabIdx, item: slug })
                : null;
        var overall =
            typeof fcReadCalculateValueForStyle === 'function'
                ? fcReadCalculateValueForStyle(tabRow[0], slug)
                : tabRow[0]?.calculateValue;
        var panelCount = countPanels(calc, slug, custom_fence, info, tabRow, tabIdx);
        var isSlatInfill = slug === 'slat_fence_infill';
        var postCount = countPostsForSection(
            calc,
            custom_fence,
            info,
            slug,
            panelCount,
            tabIdx,
            tabRow
        );
        var postOpt = get_field_options(custom_fence, info, 'post_options', 'post_option')[0];
        var postType = postTypeLabelFromSlug(postOpt?.slug, postOpt).toUpperCase();

        var rows = [];
        pushRowIf(rows, 'Fence Style', styleTitle);
        pushRowIf(rows, 'Overall Length', formatMm(overall, ''));
        pushRowIf(
            rows,
            'Fence Height',
            fenceHeightSummaryValue(calc, info, slug)
        );
        pushRowIf(rows, 'Panel Size', primaryPanelSizeLabel(calc, slug, custom_fence, tabRow));
        pushRowIf(rows, 'Offcut Panels', offcutPanelsSummaryValue(calc));
        if (panelCount > 0) {
            pushRowIf(rows, 'Number of Panels', String(panelCount));
        }
        if (!isSlatInfill && postCount > 0) {
            pushRowIf(rows, 'Number of Posts', String(postCount));
        }
        if (!isSlatInfill) {
            pushRowIf(rows, 'Post Type', postType !== '—' ? postType : '');
        }

        var panelOpt = panelOptionLabel(custom_fence, info);
        pushRowIf(rows, 'Panel Option', panelOpt);

        if (calc?.selected_values?.spacing) {
            pushRowIf(
                rows,
                info?.panel_group === 'a' ? 'Panel Gap' : 'Post Size',
                formatMm(calc.selected_values.spacing, '')
            );
        }

        slatSummaryRows(custom_fence, info, calc, slug, tabRow).forEach(function(row) {
            pushRowIf(rows, row.label, row.value);
        });

        if (
            parseInt(calc?.left_raked?.height, 10) > 0 &&
            !sideSummaryLines(custom_fence, info, 'left').some(function(l) {
                return l.indexOf('Raked') === 0;
            })
        ) {
            pushRowIf(
                rows,
                'Left Raked Panel',
                Math.round(calc.left_raked.height) +
                    'H x ' +
                    Math.round(calc.left_raked.width) +
                    'W'
            );
        }
        if (
            parseInt(calc?.right_raked?.height, 10) > 0 &&
            !sideSummaryLines(custom_fence, info, 'right').some(function(l) {
                return l.indexOf('Raked') === 0;
            })
        ) {
            pushRowIf(
                rows,
                'Right Raked Panel',
                Math.round(calc.right_raked.height) +
                    'H x ' +
                    Math.round(calc.right_raked.width) +
                    'W'
            );
        }

        if (!isSlatInfill) {
            pushRowIf(rows, 'Left Side', sideSummaryLines(custom_fence, info, 'left').join('\n'), {
                multiline: true,
                spacedTop: true
            });
            pushRowIf(rows, 'Right Side', sideSummaryLines(custom_fence, info, 'right').join('\n'), {
                multiline: true,
                spacedTop: true
            });
            pushRowIf(rows, 'Gate', gateSummaryLines(custom_fence, calc, slug, tabRow).join('\n'), {
                multiline: true,
                spacedTop: true
            });
        }

        if (calc?.selected_values?.message) {
            pushRowIf(rows, 'Notes', String(calc.selected_values.message), { warn: true });
        }

        return Object.assign(sectionHeaderMeta(tabIdx, styleTitle), {
            incomplete: false,
            rows: rows
        });
    }

    function renderSummaryHtml(activeSectionOverride) {
        var tabCount = getSummarySectionCount();
        var activeTab = resolveSummaryActiveSection(activeSectionOverride);

        var html = '';
        html += '<div class="fc-planner-summary-overview">';
        html += '<div class="fc-planner-summary-overview__grid">';
        html +=
            '<div class="fc-planner-summary-stat"><span class="fc-planner-summary-stat__label">Sections</span><span class="fc-planner-summary-stat__value">' +
            escapeHtml(String(tabCount)) +
            '</span></div>';
        if (typeof planner_id !== 'undefined' && planner_id) {
            var quoteShareUrl = buildQuoteShareUrl(planner_id);
            html += '<div class="fc-planner-summary-stat fc-planner-summary-stat--quote">';
            html += '<span class="fc-planner-summary-stat__label">Quote ID</span>';
            html += '<div class="fc-planner-summary-stat__quote-row">';
            html += '<span class="fc-planner-summary-stat__value fc-planner-summary-quote-id__value">' + escapeHtml(planner_id) + '</span>';
            if (quoteShareUrl) {
                html +=
                    '<button type="button" class="fc-planner-summary-quote-copy-link fc-copy-quote-link" data-copy-url="' +
                    escapeHtml(quoteShareUrl) +
                    '" title="Copy quote link"><i class="fa-solid fa-link" aria-hidden="true"></i> Copy Link</button>';
            }
            html += '</div></div>';
        }
        html += '</div></div>';

        html += '<div class="fc-planner-summary-sections">';

        for (var tab = 0; tab < tabCount; tab++) {
            var section = buildSectionSummary(tab);
            var isOpen = tab === activeTab || (activeTab < 0 && tab === 0);
            var sectionClasses = ['fc-planner-summary-section'];
            if (section.incomplete) {
                sectionClasses.push('fc-planner-summary-section--incomplete');
            }
            if (isOpen) {
                sectionClasses.push('fc-planner-summary-section--open');
            }

            html +=
                '<section class="' +
                sectionClasses.join(' ') +
                '" data-section-tab="' +
                tab +
                '">';
            html +=
                '<button type="button" class="fc-planner-summary-section__toggle" aria-expanded="' +
                (isOpen ? 'true' : 'false') +
                '" aria-controls="fc-planner-summary-panel-' +
                tab +
                '">';
            html += renderSectionTitleHtml(section);
            html += '<i class="fa-solid fa-chevron-down fc-planner-summary-section__chevron" aria-hidden="true"></i>';
            html += '</button>';
            html +=
                '<div class="fc-planner-summary-section__body" id="fc-planner-summary-panel-' +
                tab +
                '">';
            html += '<div class="fc-planner-summary-section__body-inner">';
            html +=
                '<button type="button" class="fc-planner-summary-section__copy"><i class="fa-solid fa-copy" aria-hidden="true"></i> Copy</button>';
            html += '<dl class="fc-planner-summary-dl fc-planner-summary-dl--rows">';
            section.rows.forEach(function(row) {
                var dtClass = [];
                if (row.spacedTop) {
                    dtClass.push('fc-planner-summary-row--spaced-top');
                }
                html +=
                    '<dt' +
                    (dtClass.length ? ' class="' + dtClass.join(' ') + '"' : '') +
                    '>' +
                    escapeHtml(row.label) +
                    '</dt>';
                var val = row.multiline
                    ? formatSummaryMultilineHtml(row.value)
                    : escapeHtml(row.value);
                var ddClass = [];
                if (row.warn) {
                    ddClass.push('text-danger');
                }
                if (row.multiline) {
                    ddClass.push('fc-planner-summary-multiline');
                }
                if (row.spacedTop) {
                    ddClass.push('fc-planner-summary-row--spaced-top');
                }
                html +=
                    '<dd' +
                    (ddClass.length ? ' class="' + ddClass.join(' ') + '"' : '') +
                    '>' +
                    val +
                    '</dd>';
            });
            html += '</dl></div></div></section>';
        }

        html += '</div>';

        return html;
    }

    function readSummaryDdCopyText($dd) {
        if (!$dd || !$dd.length) {
            return '';
        }

        if (!$dd.hasClass('fc-planner-summary-multiline')) {
            return $dd.text().replace(/\s+/g, ' ').trim();
        }

        var html = $dd.html() || '';
        var parts = html
            .split(/<br\s*\/?>/i)
            .map(function(part) {
                return $('<div>').html(part).text().replace(/\s+/g, ' ').trim();
            })
            .filter(function(part) {
                return part.length > 0;
            });

        return parts.join(', ');
    }

    function buildSectionCopyTextFromDom($section) {
        var lines = [];
        var $badge = $section.find('.fc-planner-summary-section__badge').first();
        var title = ($badge.length ? $badge.text() : $section.find('.fc-planner-summary-section__title').text())
            .replace(/\s+/g, ' ')
            .trim();
        if (title) {
            lines.push(title);
        }
        $section.find('.fc-planner-summary-section__body .fc-planner-summary-dl dt').each(function() {
            var $dt = $(this);
            var label = $dt.text().trim();
            var $dd = $dt.next('dd');
            if (!label || !$dd.length) {
                return;
            }
            var val = readSummaryDdCopyText($dd);
            if (!val) {
                return;
            }
            lines.push(label + ': ' + val);
        });
        return lines.join('\n');
    }

    function copySummaryTextToClipboard(text, onDone) {
        if (typeof fcCopyTextToClipboard === 'function') {
            fcCopyTextToClipboard(text, onDone);
            return;
        }
        try {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(text).then(onDone).catch(onDone);
                return;
            }
        } catch (e) {}
        try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        } catch (e2) {}
        if (typeof onDone === 'function') {
            onDone();
        }
    }

    function scrollSummarySectionIntoView($section) {
        var sectionEl = $section && $section[0];
        if (!sectionEl) {
            return;
        }

        var scrollRoot = document.querySelector('#fc-planner-summary-modal .modal-body');
        var prefersReducedMotion =
            typeof window.matchMedia === 'function' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var behavior = prefersReducedMotion ? 'auto' : 'smooth';

        if (!scrollRoot) {
            sectionEl.scrollIntoView({ behavior: behavior, block: 'start' });
            return;
        }

        var rootRect = scrollRoot.getBoundingClientRect();
        var sectionRect = sectionEl.getBoundingClientRect();
        var top = sectionRect.top - rootRect.top + scrollRoot.scrollTop - 8;

        scrollRoot.scrollTo({
            top: Math.max(0, top),
            behavior: behavior
        });
    }

    function bindSummarySectionToggles($body) {
        $body.off('click.fcPlannerSummaryToggle').on('click.fcPlannerSummaryToggle', '.fc-planner-summary-section__toggle', function() {
            var $btn = $(this);
            var $section = $btn.closest('.fc-planner-summary-section');
            var isOpen = $section.hasClass('fc-planner-summary-section--open');
            var willOpen = !isOpen;

            $section.toggleClass('fc-planner-summary-section--open', willOpen);
            $btn.attr('aria-expanded', willOpen ? 'true' : 'false');

            if (willOpen) {
                window.setTimeout(function() {
                    scrollSummarySectionIntoView($section);
                }, 340);
            }
        });
    }

    function bindSummarySectionCopy($body) {
        $body.off('click.fcPlannerSummaryCopy').on('click.fcPlannerSummaryCopy', '.fc-planner-summary-section__copy', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var $section = $btn.closest('.fc-planner-summary-section');
            var text = buildSectionCopyTextFromDom($section);
            if (!text) {
                return;
            }
            var original = $btn.data('fc-copy-label');
            if (!original) {
                original = $btn.text();
                $btn.data('fc-copy-label', original);
            }
            copySummaryTextToClipboard(text, function() {
                $btn.text('Copied!');
                window.setTimeout(function() {
                    $btn.text(original);
                }, 1500);
            });
        });
    }

    function ensureSummaryButton() {
        if (!$('.fc-planner-page').length) {
            return;
        }
        var sel =
            typeof FENCES !== 'undefined' && FENCES.el && FENCES.el.fencingPanelControls
                ? FENCES.el.fencingPanelControls
                : '.fencing-panel-controls';
        var $controls = $(sel);
        if (!$controls.length) {
            return;
        }
        if (!$controls.find('#btn-planner-summary').length) {
            $('<button>', {
                type: 'button',
                id: 'btn-planner-summary',
                class: 'btn-fc btn-fc-outline-default fc-planner-summary-btn fc-mb-1',
                text: 'Summary'
            }).appendTo($controls);
        }
    }

    function openModal(activeSectionOverride) {
        var $body = $('#fc-planner-summary-modal .js-fc-planner-summary-body');
        if (!$body.length) {
            return;
        }
        var activeTab = resolveSummaryActiveSection(activeSectionOverride);
        if (typeof fcRefreshPlannerCartForTab === 'function' && activeTab >= 0) {
            fcRefreshPlannerCartForTab(activeTab);
        }
        $body.html(renderSummaryHtml(activeTab));
        bindSummarySectionToggles($body);
        bindSummarySectionCopy($body);
        var el = document.getElementById('fc-planner-summary-modal');
        if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(el);
            el.addEventListener('shown.bs.modal', function onShown() {
                el.removeEventListener('shown.bs.modal', onShown);
                var $active = $body.find('.fc-planner-summary-section--open').first();
                if ($active.length) {
                    scrollSummarySectionIntoView($active);
                }
            });
            modal.show();
        }
    }

    global.fcEnsurePlannerSummaryButton = ensureSummaryButton;
    global.fcOpenPlannerSummaryModal = openModal;
})(typeof window !== 'undefined' ? window : this);
