SlatFence = {

    /**
     * Fallback when fence.pack_qty is missing in fc_data (e.g. stale cache). Prefer PHP `pack_qty` in 4-SLAT / 5-SLAT-INFILL.
     */
    settings: {
        pack_qty_defaults: {
            'slat_spacer+5': 50,
            'slat_spacer+9': 50,
            'slat_spacer+12': 50,
            'slat_spacer+15': 50,
            'slat_spacer+20': 50,
            'slat_spacer+30': 50,
            /** XPL-EP-*-2PK — pieces per retail pack (used for slat infill end-cap packs vs rail qty). */
            'sfs+end_caps': 2,
        },
        slat: {
            gate: 975 + 50 + 20 + 20,
            gate_space_left: 20,
            gate_space_right: 20,
            post: 50,
            minOnGate: 975 + 50 + 20 + 20 + 50, // 1115
            maxOnGate: 1165,
            minPanelWidthOnGate: 86,
            gate_post_gaps: 50 + 20 + 20,
            gate_posts_gaps: 50 + 20 + 20 + 50,
        }
    },

    /**
     * Slat / Slat Infill fence height ↔ row count (65mm reference table, rows 5–40).
     * Height (mm) for N rows: N × (slatMm + gapMm) − gapMm + endAllowanceMm
     * Row count from height: floor((heightMm + gapMm − endAllowanceMm) / (slatMm + gapMm))
     */
    slatHeightRowConfig: {
        minRows: 5,
        maxRows: 40,
        endAllowanceMm: 3,
        /** Step 2 Fence Height input bounds (mm). */
        minHeightMm: 199,
        maxHeightMm: 5800
    },

    //----------------------------------------------------------------------------------

    init: function(func, a, b, c, d, e, f) {
        HELPER.call_fence_func(this, func, a, b, c, d, e, f);
    },

    //----------------------------------------------------------------------------------

    test: function() {
        console.log('SLAT FENCE:', 'SlatFence.test()');
    },

    //----------------------------------------------------------------------------------

    getSetting: function(fence, key) {
        var f = fence === 'slat_fence' ? 'slat' : fence;
        return this.settings?.[f]?.[key];
    },

    //----------------------------------------------------------------------------------

    /** Main Slat style (not infill); `slat_fence` is legacy slug. */
    isMainSlatSlug: function(slug) {
        return slug === 'slat' || slug === 'slat_fence';
    },

    /**
     * Fence config `hide_post_value` (see data/fences/*.php): omit post width on spacing + Centers UI.
     */
    shouldHidePostValue: function(slugOrInfo) {
        var slug =
            slugOrInfo && typeof slugOrInfo === 'object'
                ? slugOrInfo.slug || slugOrInfo.fence || slugOrInfo.style || ''
                : String(slugOrInfo || '');
        if (!slug && typeof getSelectedFenceData === 'function') {
            try {
                slug = getSelectedFenceData().slug || '';
            } catch (e) {}
        }
        var canon =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(slug)
                : String(slug);
        if (typeof fc_data === 'undefined') {
            return false;
        }
        var meta = fc_data[canon] || fc_data[slug] || null;
        return !!(meta && meta.hide_post_value);
    },

    /** Step 3 / project plan: no-post ends show (0), not default post width (50). */
    syncSlatNoPostSpacingLabels: function(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        root.querySelectorAll('.fencing-panel-spacing-number.no-post > span:first-child').forEach(function(span) {
            span.textContent = '(0)';
        });
    },

    /**
     * Mirror no-post spacing label onto first/last panel Centers post markers (planner group b).
     */
    syncSlatNoPostEndCenterMarkers: function(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        var $root = $(root);
        var $firstSpacing = $root.find('.fencing-panel-spacing-number').first();
        if ($firstSpacing.hasClass('no-post')) {
            var leftLbl = $firstSpacing.find('> span:first-child').text();
            var $firstPanel = $root.find('.fencing-panel-item.first-item').first();
            if (!$firstPanel.length) {
                $firstPanel = $root.find('.fencing-panel-item:not(.fencing-raked-panel)').first();
            }
            if ($firstPanel.length && leftLbl) {
                $firstPanel.find('.fc-start-c-p').text(leftLbl);
            }
            if (
                $firstPanel.length &&
                typeof ProjectPlan !== 'undefined' &&
                ProjectPlan.fixCentersWidthWithoutEndPost
            ) {
                ProjectPlan.fixCentersWidthWithoutEndPost($firstPanel);
            }
        }
        var $lastSpacing = $root.find('.fencing-panel-spacing-number').last();
        if ($lastSpacing.hasClass('no-post')) {
            var rightLbl = $lastSpacing.find('> span:first-child').text();
            var $lastPanel = $root.find('.fencing-panel-item.last-item').first();
            if (!$lastPanel.length) {
                $lastPanel = $root.find('.fencing-panel-item:not(.fencing-raked-panel)').last();
            }
            if ($lastPanel.length && rightLbl) {
                $lastPanel.find('.fc-end-c-p').text(rightLbl);
            }
            if (
                $lastPanel.length &&
                typeof ProjectPlan !== 'undefined' &&
                ProjectPlan.fixCentersWidthWithoutEndPost
            ) {
                ProjectPlan.fixCentersWidthWithoutEndPost($lastPanel);
            }
        }
    },

    /** Centers label: `panelW` or `panelW + postW` depending on config. */
    formatPanelSizeCenterW: function(panelSizeMm, centerPointMm, slug) {
        var panel = parseInt(panelSizeMm, 10);
        if (!Number.isFinite(panel)) {
            panel = 0;
        }
        if (this.shouldHidePostValue(slug)) {
            return panel + 'W';
        }
        var cp = parseInt(centerPointMm, 10);
        if (!Number.isFinite(cp)) {
            cp = 0;
        }
        return panel + cp + 'W';
    },

    //----------------------------------------------------------------------------------

    isSlatLike: function(slug) {
        return slug === 'slat' || slug === 'slat_fence' || slug === 'slat_fence_infill';
    },

    /**
     * Slat main planner with Gate only (no fence panels) — BOM should follow slat-gates / products-slat-gate.csv.
     */
    isSlatGateOnly: function(context) {
        if (!this.isSlatLike(context?.tabInfo?.[0]?.fence)) {
            return false;
        }
        if (context?.tabInfo?.[0]?.fence === 'slat_fence_infill') {
            return false;
        }
        var gItem = (context?.fenceInfo || []).find(function(it) {
            return it?.control_key === 'gate';
        });
        return !!(gItem?.settings?.gateOnly);
    },

    /**
     * Main Slat planner: gate is shown without fence panels — posts/display use Gate Height, not Step 2 Fence Height.
     */
    isSlatGateOnlyPlanner: function(context, calc, info, containerEl) {
        if (!this.isMainSlatSlug(info?.slug || context?.tabInfo?.[0]?.fence || '')) {
            return false;
        }
        if (this.isSlatGateOnly(context)) {
            return true;
        }
        if (containerEl && containerEl.querySelector) {
            if (
                containerEl.querySelector('.fencing-panel-gate') &&
                !containerEl.querySelector(
                    '.fencing-panel-item:not(.fencing-panel-gate), .short-panel-item:not(.fencing-panel-gate)'
                )
            ) {
                return true;
            }
        }
        if (calc) {
            var fencePanelCount =
                (parseInt(calc?.long_panel?.count, 10) || 0) +
                (parseInt(calc?.short_panel?.count, 10) || 0) +
                (parseInt(calc?.even_panel?.count, 10) || 0) +
                (parseInt(calc?.full_panel?.count, 10) || 0);
            if ((parseInt(calc?.gate?.count, 10) || 0) > 0 && fencePanelCount < 1) {
                return true;
            }
        }
        return false;
    },

    isGateOnlyPlaceholderMm: function(val) {
        var n = parseInt(String(val != null ? val : '').replace(/,/g, ''), 10);
        return n === 9999;
    },

    /** Main Slat: Step 2 Gate ONLY is active (segment + tab storage). */
    isStep2GateOnlyActive: function(fd) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return false;
        }
        if (typeof fcIsPlannerGateOnlyActive === 'function') {
            return fcIsPlannerGateOnlyActive(fd);
        }
        return this.isSlatGateOnly(fd);
    },

    /** Main Slat: keep Add Gate + Gate Options enabled (default planner flow). */
    syncSlatGateAddAndOptionsEnabled: function(fd) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return;
        }
        var btnSel =
            typeof FENCES !== 'undefined' && FENCES.el && FENCES.el.btnGate
                ? FENCES.el.btnGate
                : '#btn-gate';
        $(btnSel)
            .add(
                $(
                    '.fencing-panel-controls .fencing-btn-modal[data-key="gate"], .fencing-panel-gate .fencing-btn-modal[data-key="gate"]'
                )
            )
            .prop('disabled', false)
            .removeAttr('aria-disabled')
            .removeClass('fc-slat-gate-requires-gate-only');
    },

    getStep2HeightFieldLabel: function(gateOnly) {
        return gateOnly ? 'Gate Height' : 'Fence Height';
    },

    /**
     * Gate ONLY on Step 2: treat max_fence_height as Gate Height (199–2240 mm) and keep modal Gate Height.
     */
    syncStep2GateOnlyHeightMode: function(fd) {
        fd =
            fd ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return;
        }

        var gateOnly = this.isStep2GateOnlyActive(fd);
        var el = this.getStep2MaxFenceHeightEl();
        var $wrap = el ? $(el).closest('.fc-step2-max-height-input, .fc-input-container') : $();
        var $label = $wrap.find('.fw-bold').first();

        if (gateOnly) {
            $wrap.addClass('fc-step2-height--gate-only');
            if ($label.length) {
                $label.text(this.getStep2HeightFieldLabel(true));
            }
            this.refreshStep2MaxFenceHeightBounds({ resetHeightIfInvalid: true });
            var committed = this.getCommittedGateMaxFenceHeightMm(fd);
            if (el && committed && !(el.value || '').toString().trim()) {
                el.value = String(committed);
            }
            if (el && (el.value || '').toString().trim()) {
                this.syncStep2HeightToGateFields(fd);
                this.mirrorStep2HeightToGateModalInput();
            }
        } else {
            $wrap.removeClass('fc-step2-height--gate-only');
            if ($label.length) {
                $label.text(this.getStep2HeightFieldLabel(false));
            }
            this.refreshStep2MaxFenceHeightBounds({ resetHeightIfInvalid: false });
        }
    },

    getCommittedGateMaxFenceHeightMm: function(fd) {
        var gateRow = (fd?.info || []).find(function(item) {
            return item && item.control_key === 'gate';
        });
        var fields = gateRow?.settings?.fields || [];
        for (var i = 0; i < fields.length; i++) {
            if (fields[i]?.key === 'gate_max_fence_height') {
                var n = parseInt(String(fields[i].val || '').replace(/,/g, ''), 10);
                if (Number.isFinite(n) && n > 0) {
                    return n;
                }
            }
        }
        return null;
    },

    syncStep2HeightToGateFields: function(fd) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return;
        }
        var el = this.getStep2MaxFenceHeightEl();
        if (!el || !this.validateMaxFenceHeightField(el).valid) {
            return;
        }
        var v = (el.value || '').toString().trim();
        if (!v) {
            return;
        }
        this.persistGateMaxFenceHeightToGateFields(fd.tab, fd.slug, v);
    },

    mirrorStep2HeightToGateModalInput: function() {
        var stepEl = this.getStep2MaxFenceHeightEl();
        var gateEl = this.getGateMaxFenceHeightEl();
        if (!stepEl || !gateEl) {
            return;
        }
        var v = (stepEl.value || '').toString().trim();
        if (!v || !this.validateMaxFenceHeightField(stepEl).valid) {
            return;
        }
        gateEl.value = v;
    },

    mirrorGateModalHeightToStep2: function(fd) {
        if (!fd || !this.isStep2GateOnlyActive(fd)) {
            return;
        }
        var gateEl = this.getGateMaxFenceHeightEl();
        var stepEl = this.getStep2MaxFenceHeightEl();
        if (!gateEl || !stepEl) {
            return;
        }
        var v = (gateEl.value || '').toString().trim();
        if (!v || !this.validateMaxFenceHeightField(gateEl).valid) {
            return;
        }
        stepEl.value = v;
        try {
            this.persistMaxFenceHeightFromStep2(fd.tab, fd.slug, v);
        } catch (ePs) {}
    },

    /**
     * Gate ONLY Step 2 Gate Height: persist + sync gate segment (run on Enter / Calculate only).
     */
    commitStep2GateHeightForGateOnly: function(fd) {
        if (!fd || !this.isStep2GateOnlyActive(fd)) {
            return false;
        }
        var el = this.getStep2MaxFenceHeightEl();
        if (!el || !this.validateMaxFenceHeightField(el).valid) {
            return false;
        }
        var v = (el.value || '').toString().trim();
        if (!v) {
            return false;
        }
        try {
            this.persistMaxFenceHeightFromStep2(fd.tab, fd.slug, v);
        } catch (ePs) {}
        this.syncStep2HeightToGateFields(fd);
        this.mirrorStep2HeightToGateModalInput();
        return true;
    },

    /** Gate Options: STD (not custom width). */
    isSlatStdGate: function(gate_data) {
        var row = gate_data?.[0]?.settings?.fields?.find(function(item) {
            return item?.key === 'use_std';
        });
        if (!row) {
            return true;
        }
        return row.val !== false && String(row.val).toLowerCase() !== 'false';
    },

    /** Step 2 Gate ONLY + custom gate (not STD). */
    isStep2GateOnlyCustomGate: function(fd) {
        if (!this.isStep2GateOnlyActive(fd)) {
            return false;
        }
        return this.isSlatCustomGateFd(fd);
    },

    /** Slat fence with a custom-width gate (not STD). */
    isSlatCustomGateFd: function(fd) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return false;
        }
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        return gate_data.length > 0 && !this.isSlatStdGate(gate_data);
    },

    getGateOnlySideAdjustGapMm: function(fd) {
        var custom_fence = fd?.info || [];
        var info = fd?.data;
        var slug = fd?.slug;
        var adjustGap = 0;
        var left_side_width = HELPER.sideOptionValue('left', custom_fence, info);
        var right_side_width = HELPER.sideOptionValue('right', custom_fence, info);
        if (left_side_width >= 0 || right_side_width >= 0) {
            var post = FENCE.get(slug, 'post');
            var lw = left_side_width >= 0 ? post - left_side_width : 0;
            var rw = right_side_width >= 0 ? post - right_side_width : 0;
            adjustGap = lw + rw;
        }
        return adjustGap;
    },

    /**
     * Gate ONLY + custom: derive gate leaf width (mm) from Step 2 Overall Length display value.
     */
    deriveCustomGateLeafWidthFromOverallDisplayMm: function(fd, displayMm) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return null;
        }
        var raw = parseInt(String(displayMm != null ? displayMm : '').replace(/,/g, ''), 10);
        if (!Number.isFinite(raw) || raw <= 0 || this.isGateOnlyPlaceholderMm(raw)) {
            return null;
        }

        var effective = this.isStep2GateOnlyCustomGate(fd)
            ? raw
            : this.applyCalcOverallOffset(
                fd.slug,
                raw,
                fd.info || [],
                fd.tabInfo ? [fd.tabInfo[0]] : []
            );
        var fence_gate_posts_gaps = parseInt(FENCE.get(fd.slug, 'gate_posts_gaps'), 10);
        if (!Number.isFinite(fence_gate_posts_gaps)) {
            fence_gate_posts_gaps = 0;
        }
        var minusPosts = FENCE.minus_posts(fd.info || []);
        var totalOpening = effective - fence_gate_posts_gaps + minusPosts;
        if (!Number.isFinite(totalOpening) || totalOpening <= 0) {
            return null;
        }

        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var isDouble = gate_data.length && this.isSlatDoubleGate(gate_data);
        var leaf = isDouble ? Math.round(totalOpening / 2) : Math.round(totalOpening);
        return leaf > 0 ? leaf : null;
    },

    /**
     * Inverse of deriveCustomGateLeafWidthFromOverallDisplayMm — display OAL for a leaf width (single/double).
     */
    computeDisplayOverallFromGateLeafMm: function(fd, leafMm, opts) {
        opts = opts || {};
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return null;
        }
        var leaf = parseInt(leafMm, 10);
        if (!Number.isFinite(leaf) || leaf <= 0) {
            return null;
        }
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var isDouble =
            opts.isDouble != null
                ? !!opts.isDouble
                : gate_data.length && this.isSlatDoubleGate(gate_data);
        var totalOpening = isDouble ? leaf * 2 : leaf;
        var fence_gate_posts_gaps = parseInt(FENCE.get(fd.slug, 'gate_posts_gaps'), 10);
        if (!Number.isFinite(fence_gate_posts_gaps)) {
            fence_gate_posts_gaps = 0;
        }
        var minusPosts = FENCE.minus_posts(fd.info || []);
        var effective = totalOpening + fence_gate_posts_gaps - minusPosts;
        if (!Number.isFinite(effective) || effective <= 0) {
            return null;
        }
        // Gate minimum overall is the outside fence length (leaf + posts/gaps), not center-line display offset.
        return effective;
    },

    clampCustomGateLeafWidthMm: function(fd, leafMm) {
        var w = parseInt(leafMm, 10);
        if (!Number.isFinite(w) || w <= 0) {
            return null;
        }

        var minW = 300;
        var maxW = 2100;
        var widthEl = document.querySelector('.custom-gate [name="width"]');
        if (widthEl) {
            var dMin = parseInt(widthEl.getAttribute('data-min') || '', 10);
            var dMax = parseInt(widthEl.getAttribute('data-max') || '', 10);
            if (Number.isFinite(dMin) && dMin > 0) {
                minW = dMin;
            }
            if (Number.isFinite(dMax) && dMax > 0) {
                maxW = dMax;
            }
        }

        try {
            if (typeof getSelectedFenceData === 'function' && typeof fc_data !== 'undefined') {
                var panelOpts = fd?.data?.settings?.panel_options;
                if (panelOpts) {
                    var limits = this.getCustomGateLimits({
                        slug: fd.slug,
                        panelOptionsData: panelOpts.fields?.[0]?.options?.[0] || {},
                        fenceHeight: '',
                        maxFenceHeight: this.getMaxFenceHeightValForStep2(fd.tabInfo?.[0], fd.slug),
                        tabInfo: fd.tabInfo,
                        fenceInfo: fd.info,
                        postWidth: FENCE.get(fd.slug, 'post')
                    });
                    if (Number.isFinite(limits.maxWidth) && limits.maxWidth > 0) {
                        maxW = limits.maxWidth;
                    }
                }
            }
        } catch (eLim) {}

        return Math.max(minW, Math.min(maxW, w));
    },

    persistCustomGateLeafWidth: function(fd, leafMm) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return;
        }
        var w = parseInt(leafMm, 10);
        if (!Number.isFinite(w) || w <= 0) {
            return;
        }

        var gateRows = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        if (gateRows[0]?.settings) {
            gateRows[0].settings.size = w;
            gateRows[0].settings.gateOnly = true;
            if (!Array.isArray(gateRows[0].settings.fields)) {
                gateRows[0].settings.fields = [];
            }
            var useStdRow = gateRows[0].settings.fields.find(function(f) {
                return f?.key === 'use_std';
            });
            if (useStdRow) {
                useStdRow.val = false;
            } else {
                gateRows[0].settings.fields.push({
                    key: 'use_std',
                    val: false,
                    tag: 'input',
                    type: 'checkbox'
                });
            }
        }

        var widthEl = document.querySelector('.custom-gate [name="width"]');
        if (widthEl) {
            widthEl.value = String(w);
            widthEl.removeAttribute('readonly');
        }

        try {
            var key = 'custom_fence-' + fd.tab + '-' + fd.slug;
            var cf = JSON.parse(localStorage.getItem(key) || 'null');
            if (Array.isArray(cf)) {
                for (var i = 0; i < cf.length; i++) {
                    if (cf[i].control_key !== 'gate' || !cf[i].settings) {
                        continue;
                    }
                    cf[i].settings.size = w;
                    cf[i].settings.gateOnly = true;
                    if (!Array.isArray(cf[i].settings.fields)) {
                        cf[i].settings.fields = [];
                    }
                    var hasUseStd = false;
                    for (var j = 0; j < cf[i].settings.fields.length; j++) {
                        if (cf[i].settings.fields[j]?.key === 'use_std') {
                            cf[i].settings.fields[j].val = false;
                            hasUseStd = true;
                        }
                    }
                    if (!hasUseStd) {
                        cf[i].settings.fields.push({
                            key: 'use_std',
                            val: false,
                            tag: 'input',
                            type: 'checkbox'
                        });
                    }
                }
                localStorage.setItem(key, JSON.stringify(cf));
            }
        } catch (eStore) {}

        try {
            updateGateOnly(true);
        } catch (eGo) {}
    },

    /**
     * Gate ONLY + custom: apply Overall Length → custom gate width on Calculate / Enter only.
     * Clamps to min/max; if clamped, Step 2 Overall Length is set to the achievable span.
     */
    commitStep2GateOnlyCustomFromOverall: function(fd) {
        fd =
            fd ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
        if (!fd || !this.isStep2GateOnlyCustomGate(fd)) {
            return false;
        }

        var $box =
            typeof FENCES !== 'undefined' && FENCES.el
                ? $(FENCES.el.measurementBoxNumber)
                : $('.measurement-box-number');
        var display = $box.val();
        var leafRaw = this.deriveCustomGateLeafWidthFromOverallDisplayMm(fd, display);
        if (!leafRaw) {
            return false;
        }
        var leaf = this.clampCustomGateLeafWidthMm(fd, leafRaw);
        if (!leaf) {
            return false;
        }

        this.persistCustomGateLeafWidth(fd, leaf);

        var dispCommit = String(display || '').replace(/,/g, '').trim();
        if (leaf !== leafRaw) {
            var dispOv = this.computeDisplayOverallFromGateLeafMm(fd, leaf, {
                isDouble: this.resolveGateTypeIsDouble(fd)
            });
            if (dispOv != null && Number.isFinite(dispOv)) {
                $box.val(dispOv);
                $box.attr('data-last', String(dispOv));
            }
        } else if (dispCommit) {
            $box.attr('data-last', dispCommit);
        }

        try {
            FENCE.call('update_custom_fence_gate');
        } catch (eUpd) {}
        try {
            updateGateOnly(true);
        } catch (eGo) {}
        try {
            if (typeof checkGateOnly === 'function') {
                checkGateOnly();
            }
        } catch (eChk) {}

        return true;
    },

    /** Leaf width (mm) from gate modal input or persisted gate segment. */
    resolveCustomGateLeafWidthMm: function(fd, opts) {
        opts = opts || {};
        var leaf = null;
        var widthEl = document.querySelector('.custom-gate [name="width"]');
        if (widthEl && opts.preferModal !== false) {
            var raw = String(widthEl.value || '').replace(/,/g, '').trim();
            if (raw) {
                var v = parseInt(raw, 10);
                if (Number.isFinite(v) && v > 0) {
                    if (opts.allowUnvalidated) {
                        leaf = v;
                    } else {
                        var vr = this.validateGateModalCustomWidthField(widthEl);
                        if (vr.valid) {
                            leaf = v;
                        }
                    }
                }
            }
        }
        if (!leaf) {
            var gate_data = (fd?.info || []).filter(function(item) {
                return item && item.control_key === 'gate';
            });
            var stored = parseInt(
                String(gate_data[0]?.settings?.size || '').replace(/,/g, ''),
                10
            );
            if (Number.isFinite(stored) && stored > 0) {
                leaf = stored;
            }
        }
        return Number.isFinite(leaf) && leaf > 0 ? leaf : null;
    },

    /** Gate type from open gate modal, else persisted gate fields. */
    resolveGateTypeIsDouble: function(fd) {
        var gtField = document.querySelector(
            '.fencing-container[data-key="gate"] .fc-form-field[name="gate_type"], #fc-control-modal .fc-form-field[name="gate_type"]'
        );
        if (gtField) {
            var slug = String(gtField.getAttribute('value') || '').trim();
            if (slug === 'double') {
                return true;
            }
            if (slug === 'single') {
                return false;
            }
        }
        var gate_data = (fd?.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        return gate_data.length > 0 && this.isSlatDoubleGate(gate_data);
    },

    /**
     * Gate ONLY + custom: set Step 2 Overall Length from custom gate leaf width (single = leaf, double = 2× leaf).
     */
    syncStep2OverallFromCustomGateWidth: function(fd, opts) {
        opts = opts || {};
        fd =
            fd ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
        if (!fd || !this.isSlatCustomGateFd(fd)) {
            return false;
        }

        var leaf = this.resolveCustomGateLeafWidthMm(fd, opts);
        if (!leaf) {
            return false;
        }

        var isDouble = this.resolveGateTypeIsDouble(fd);
        var disp = this.computeDisplayOverallFromGateLeafMm(fd, leaf, { isDouble: isDouble });
        if (disp == null || !Number.isFinite(disp) || disp <= 0) {
            return false;
        }

        var $box =
            typeof FENCES !== 'undefined' && FENCES.el
                ? $(FENCES.el.measurementBoxNumber)
                : $('.measurement-box-number');
        if (!$box.length) {
            return false;
        }

        var gateOnly = this.isStep2GateOnlyCustomGate(fd);
        var curDisplay = parseInt(String($box.val() || '').replace(/,/g, ''), 10);
        if (!gateOnly) {
            if (opts.postChange) {
                if (Number.isFinite(curDisplay) && (curDisplay === disp || curDisplay > disp)) {
                    return false;
                }
            } else if (Number.isFinite(curDisplay) && curDisplay >= disp) {
                return false;
            }
        }

        $box.val(disp);
        $box.attr('data-last', String(disp));
        $box.prop('readonly', false).removeAttr('aria-disabled');
        $box.closest('.fc-input-container').removeClass('fc-measurement-locked-gate-only');

        if (opts.persist && typeof fcPersistStep2Immediate === 'function') {
            try {
                fcPersistStep2Immediate({ force: true });
            } catch (ePs) {}
        }

        if (gateOnly) {
            try {
                updateGateOnly(true);
            } catch (eGo) {}
        }

        return true;
    },

    /** Step 2 Gate ONLY + STD gate: overall length is derived from gate width, not user-typed. */
    shouldLockStep2OverallForStdGateOnly: function(fd) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return false;
        }
        if (typeof fcIsPlannerGateOnlyActive === 'function' && !fcIsPlannerGateOnlyActive(fd)) {
            return false;
        }
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        if (!gate_data.length) {
            return false;
        }
        return this.isSlatStdGate(gate_data);
    },

    /**
     * Nominal STD gate leaf (mm) from gate modal / persisted gate — never calc layout width (C8 / center-line adjusted).
     */
    resolveStdGateLeafWidthNominalMm: function(slug, gate_data) {
        var g = gate_data?.[0]?.settings;
        if (g && g.size != null && String(g.size) !== '') {
            var n = parseInt(String(g.size).replace(/,/g, ''), 10);
            if (Number.isFinite(n) && n > 0) {
                return n;
            }
        }
        var gw = g?.fields?.find(function(item) {
            return item && item.key === 'gate_width';
        })?.val;
        if (gw != null && String(gw) !== '') {
            var n2 = parseInt(String(gw).replace(/,/g, ''), 10);
            if (Number.isFinite(n2) && n2 > 0) {
                return n2;
            }
        }
        try {
            var def = parseInt(
                String(
                    typeof fc_data !== 'undefined' && fc_data[slug]
                        ? fc_data[slug]?.settings?.gate?.size?.width
                        : ''
                ).replace(/,/g, ''),
                10
            );
            if (Number.isFinite(def) && def > 0) {
                return def;
            }
        } catch (eDef) {}
        return 975;
    },

    /**
     * STD gate minimum outside Overall Length (mm): opening + left post + right post + gate gaps.
     * E.g. 975 + 50 + 50 + 20 + 20 = 1115. Not calc C8 (925+90=1015 with center-line gate width).
     */
    getSlatStdGateMinOverallPhysicalMm: function(fd, calc) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return null;
        }
        return FENCE.getStdGateMinOverallPhysicalMm(fd);
    },

    /**
     * Effective overall (mm) for calc / min-OAL — gate opening + posts/gaps − removed end posts only.
     */
    computeSlatGateOnlyStdOverallEffectiveMm: function(fd) {
        return this.getSlatStdGateMinOverallPhysicalMm(fd, null);
    },

    /** Step 2 display value for Gate ONLY + STD — physical outside length (975 + posts + gaps = 1115). */
    computeSlatGateOnlyStdOverallDisplayMm: function(fd) {
        return this.computeSlatGateOnlyStdOverallEffectiveMm(fd);
    },

    /** Slat fence with a standard-width gate (not custom). */
    isSlatStdGateFd: function(fd) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return false;
        }
        var gate_data = (fd.info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        return gate_data.length > 0 && this.isSlatStdGate(gate_data);
    },

    /**
     * Fence + STD gate (or Gate ONLY + STD): set Step 2 Overall Length to gate minimum when below min.
     */
    syncStep2OverallFromStdGateWidth: function(fd, opts) {
        if (!fd || !this.isSlatStdGateFd(fd)) {
            return false;
        }
        return FENCE.syncOverallFromStdGateWidth(fd, opts);
    },

    /**
     * After left/right end post yes/no changes: refresh Step 2 min Overall Length (STD or custom gate).
     */
    syncSlatGateOverallOnPostChange: function(fd, opts) {
        if (!fd || !this.isMainSlatSlug(fd.slug)) {
            return false;
        }
        return FENCE.syncGateOverallOnPostChange(fd, opts);
    },

    /**
     * Gate ONLY + STD: show computed overall, lock field (readonly), strip legacy 9999 placeholder.
     */
    syncStep2GateOnlyOverallField: function(fd, opts) {
        opts = opts || {};
        fd =
            fd ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
        var $box =
            typeof FENCES !== 'undefined' && FENCES.el
                ? $(FENCES.el.measurementBoxNumber)
                : $('.measurement-box-number');
        if (!$box.length) {
            return;
        }

        var lock = fd && this.shouldLockStep2OverallForStdGateOnly(fd);
        var $ic = $box.closest('.fc-input-container');

        if (lock) {
            var disp = this.computeSlatGateOnlyStdOverallDisplayMm(fd);
            var cur = parseInt(String($box.val() || '').replace(/,/g, ''), 10);
            if (Number.isFinite(disp) && disp > 0) {
                if (!Number.isFinite(cur) || cur <= 0 || this.isGateOnlyPlaceholderMm(cur) || cur !== disp) {
                    $box.val(disp);
                    $box.attr('data-last', String(disp));
                }
            } else if (this.isGateOnlyPlaceholderMm(cur)) {
                var prev = $box.attr('data-prev-gate-only-mbn');
                var prevN = parseInt(String(prev || '').replace(/,/g, ''), 10);
                if (Number.isFinite(prevN) && prevN > 0 && !this.isGateOnlyPlaceholderMm(prevN)) {
                    $box.val(prevN);
                    $box.attr('data-last', String(prevN));
                }
            }

            $box.prop('readonly', true).attr('aria-disabled', 'true');
            $ic.addClass('fc-measurement-locked-gate-only');
        } else {
            $box.prop('readonly', false).removeAttr('aria-disabled');
            $ic.removeClass('fc-measurement-locked-gate-only');
            var cur2 = parseInt(String($box.val() || '').replace(/,/g, ''), 10);
            if (this.isGateOnlyPlaceholderMm(cur2)) {
                var prev2 = $box.attr('data-prev-gate-only-mbn');
                var prevN2 = parseInt(String(prev2 || '').replace(/,/g, ''), 10);
                if (Number.isFinite(prevN2) && prevN2 > 0) {
                    $box.val(prevN2);
                    $box.attr('data-last', String(prevN2));
                }
            }
        }

        if (opts.persist && typeof fcPersistStep2Immediate === 'function') {
            try {
                fcPersistStep2Immediate({ force: true });
            } catch (ePs) {}
        }
    },

    hasSlatPlannerGate: function(context, calc, containerEl) {
        if (containerEl && containerEl.querySelector && containerEl.querySelector('.fencing-panel-gate')) {
            return true;
        }
        if (calc && (parseInt(calc.gate?.count, 10) || 0) > 0) {
            return true;
        }
        var rows = context?.fenceInfo || [];
        for (var i = 0; i < rows.length; i++) {
            if (rows[i] && rows[i].control_key === 'gate') {
                return true;
            }
        }
        return false;
    },

    /** Post / display height (mm): gate-only uses gate height; otherwise default fence height. */
    resolveSlatPostHeightMm: function(context, calc, info, containerEl, fenceHeightMm, gateHeightMm) {
        var fenceHm =
            fenceHeightMm !== null && Number.isFinite(fenceHeightMm) && fenceHeightMm > 0
                ? fenceHeightMm
                : null;
        var gateHm =
            gateHeightMm !== null && Number.isFinite(gateHeightMm) && gateHeightMm > 0
                ? gateHeightMm
                : fenceHm;
        if (this.isSlatGateOnlyPlanner(context, calc, info, containerEl) && gateHm) {
            return gateHm;
        }
        return fenceHm;
    },

    /**
     * Planner uses `.fencing-panel-items`; project plan uses `.fc-result` (no panel-items wrapper).
     */
    resolveSlatScaleWrapper: function(rootEl, $scope) {
        if (rootEl && rootEl.closest) {
            var fromDom =
                rootEl.closest('.fencing-panel-items') || rootEl.closest('.fc-result');
            if (fromDom) {
                return fromDom;
            }
        }
        if ($scope && $scope.length) {
            var $wrap = $scope.closest('.fencing-panel-items');
            if ($wrap.length) {
                return $wrap.get(0);
            }
            $wrap = $scope.closest('.fc-result');
            if ($wrap.length) {
                return $wrap.get(0);
            }
        }
        return null;
    },

    /** jQuery collection of slat scale wrappers within scope (planner + project plan). */
    $slatScaleWrappersInScope: function($scope) {
        if (!$scope || !$scope.length) {
            return $('.fencing-panel-items');
        }
        return $scope
            .closest('.fencing-panel-items')
            .add($scope.closest('.fc-result'))
            .add($scope.filter('.fencing-panel-items'))
            .add($scope.filter('.fc-result'));
    },

    /** Posts flanking the gate (same nodes as `near_gate_spacing`). */
    isSlatGateAdjacentPost: function(postEl, containerEl) {
        if (!postEl || !containerEl) {
            return false;
        }
        if (postEl.classList && postEl.classList.contains('near-gate')) {
            return true;
        }
        var gate = containerEl.querySelector('.fencing-panel-gate');
        if (!gate) {
            return false;
        }
        var isNode = function(node) {
            return node && node === postEl;
        };
        if (gate.classList.contains('panel-gate-left')) {
            return (
                isNode(gate.nextElementSibling) ||
                isNode(gate.nextElementSibling && gate.nextElementSibling.nextElementSibling) ||
                isNode(gate.previousElementSibling) ||
                isNode(
                    gate.previousElementSibling &&
                        gate.previousElementSibling.previousElementSibling
                )
            );
        }
        if (gate.classList.contains('panel-gate-right')) {
            return (
                isNode(gate.nextElementSibling) ||
                isNode(gate.nextElementSibling && gate.nextElementSibling.nextElementSibling) ||
                isNode(gate.previousElementSibling) ||
                isNode(
                    gate.previousElementSibling &&
                        gate.previousElementSibling.previousElementSibling
                )
            );
        }
        return false;
    },

    //----------------------------------------------------------------------------------

    /**
     * Number of packs to order: ceil(requiredPieces / itemsPerPack). Minimum 1 when requiredPieces > 0.
     * Use for any pack-sized material (spacers, future stock with pack_qty in fc_data).
     */
    ceilPackQuantity: function(requiredPieces, itemsPerPack) {
        var r = typeof requiredPieces === 'number' ? requiredPieces : parseFloat(requiredPieces);
        if (!Number.isFinite(r) || r <= 0) return 0;
        var n = parseInt(itemsPerPack, 10);
        if (!Number.isFinite(n) || n <= 0) {
            n = 50;
        }
        return Math.max(1, Math.ceil(r / n));
    },

    /**
     * Items per pack for a catalog slug (e.g. slat_spacer+20) from fc_data[fence].pack_qty, then settings.pack_qty_defaults.
     */
    getPackItemsForSlug: function(fenceSlug, materialSlug) {
        var raw = String(fenceSlug || '');
        var canon =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : raw;
        if (canon === 'slat_fence') {
            canon = 'slat';
        }
        var map =
            typeof fc_data !== 'undefined' && fc_data[canon] && fc_data[canon].pack_qty
                ? fc_data[canon].pack_qty
                : null;
        if (map && materialSlug != null && map[materialSlug] != null && map[materialSlug] !== '') {
            var fromData = parseInt(map[materialSlug], 10);
            if (Number.isFinite(fromData) && fromData > 0) {
                return fromData;
            }
        }
        var defMap = this.settings.pack_qty_defaults || {};
        if (materialSlug != null && defMap[materialSlug] != null) {
            var d = parseInt(defMap[materialSlug], 10);
            if (Number.isFinite(d) && d > 0) {
                return d;
            }
        }
        return 50;
    },

    //----------------------------------------------------------------------------------

    getStep2MeasurementCopy: function(slug) {
        if (typeof SlatFenceInfill !== 'undefined' && SlatFenceInfill.isActive(slug)) {
            return SlatFenceInfill.getStep2MeasurementCopy();
        }

        return {
            title: 'Enter your measurements',
            measurementLabel: 'Overall Length',
            noteTitle: 'Overall Length',
            noteCopy: 'Ensure your overall length includes the posts each end. NOTE: "Panel & Post Options" below will deduct based on options selected.',
            overallLabel: 'Overall'
        };
    },

    //----------------------------------------------------------------------------------

    applyStep2MeasurementCopy: function(slug) {
        var copy = this.getStep2MeasurementCopy(slug);
        $('.js-step-2-title').text(copy.title);
        $('.js-step-2-measurement-label').text(copy.measurementLabel);
        $('.js-step-2-note-title').text(copy.noteTitle);
        $('.js-step-2-note-copy').html(copy.noteCopy);
        $('.js-overall-label').text(copy.overallLabel);
    },

    //----------------------------------------------------------------------------------

    /** Convert a slat_gap option slug to gap mm (Step 2 select + segment storage). */
    gapSlugToMm: function(slug, info) {
        if (slug === undefined || slug === null || String(slug).trim() === '') {
            return null;
        }

        var options = info?.settings?.edit_spacing?.fields?.[0]?.options || [];
        var selectedOpt = options.find(function(item) {
            return String(item?.slug) === String(slug);
        });

        var slugGap = parseFloat(selectedOpt?.slug);
        if (Number.isFinite(slugGap) && slugGap >= 0) {
            return slugGap;
        }

        var title = selectedOpt?.title;
        if (typeof title === 'string') {
            var match = title.match(/(\d+)/);
            if (match) {
                return parseInt(match[1], 10);
            }
        }

        var n = parseFloat(slug);
        if (Number.isFinite(n) && n >= 0) {
            return n;
        }

        return null;
    },

    /** Catalog slug suffix for slat_spacer+{n} (matches products.csv). */
    gapMmToSpacerCatalogKey: function(gapMm) {
        var g = Math.round(Number(gapMm));
        if (!Number.isFinite(g) || g < 0) {
            return null;
        }
        return g;
    },

    /** Live Step 2 `[name="slat_gap"]` → gap mm (used when refreshing fence height options). */
    getStep2SlatGapMm: function(info) {
        var el = document.querySelector('[data-section="2"] [name="slat_gap"]');
        if (!el || !this.validateSlatGapField(el).valid) {
            return null;
        }
        return this.gapSlugToMm(el.value, info);
    },

    /** Live Step 2 `[name="slat_size"]` → 65 or 90 mm face size. */
    getStep2SlatSizeMm: function() {
        var el = document.querySelector('[data-section="2"] [name="slat_size"]');
        if (!el || !this.validateSlatSizeField(el).valid) {
            return null;
        }
        var n = parseFloat(el.value);
        if (!Number.isFinite(n)) {
            return null;
        }
        return n >= 80 ? 90 : 65;
    },

    getGapMm: function(custom_fence, info) {
        var step2Mm = this.getStep2SlatGapMm(info);
        if (Number.isFinite(step2Mm)) {
            return step2Mm;
        }

        var editSpacing = custom_fence?.find?.(function(item) {
            return item?.control_key === 'edit_spacing';
        });
        var selected = editSpacing?.settings?.find?.(function(item) {
            return item?.key === 'slat_gap';
        })?.val;

        var options = info?.settings?.edit_spacing?.fields?.[0]?.options || [];
        var selectedOpt = selected
            ? options.find(function(item) { return String(item?.slug) === String(selected); })
            : options.find(function(item) { return item?.default; });

        var fromSlug = this.gapSlugToMm(selectedOpt?.slug, info);
        if (Number.isFinite(fromSlug)) {
            return fromSlug;
        }

        var n = parseFloat(selected || '');
        if (Number.isFinite(n) && n >= 0) {
            return n;
        }

        return 9;
    },

    getSlatHeightRowLimits: function() {
        var cfg = this.slatHeightRowConfig || {};
        var minRows = parseInt(cfg.minRows, 10);
        var maxRows = parseInt(cfg.maxRows, 10);
        if (!Number.isFinite(minRows) || minRows < 1) {
            minRows = 5;
        }
        if (!Number.isFinite(maxRows) || maxRows < minRows) {
            maxRows = 40;
        }
        return { minRows: minRows, maxRows: maxRows };
    },

    getSlatHeightEndAllowanceMm: function() {
        var cfg = this.slatHeightRowConfig || {};
        var n = parseInt(cfg.endAllowanceMm, 10);
        return Number.isFinite(n) ? n : 3;
    },

    /** Panel / bottom-gap math (Slat Planner V6): 3 mm top + 3 mm bottom in panel height. */
    getSlatPanelEndAllowanceMm: function() {
        return 6;
    },

    /**
     * Gap pitch mm for panel row/height formulas (Slat Planner V6 M6 in F80/F81).
     * Planner slugs 9 / 20 map to legacy option nums 9.3 / 21.1 used in pitch math.
     */
    gapSlugToPitchMm: function(slug) {
        if (slug === undefined || slug === null || String(slug).trim() === '') {
            return null;
        }
        var raw = String(slug).trim();
        if (raw === '9') {
            return 9.3;
        }
        if (raw === '20') {
            return 21.1;
        }
        var n = parseFloat(raw);
        return Number.isFinite(n) && n >= 0 ? n : null;
    },

    /** Raw slat gap slug from Step 2 or segment storage (for pitch math). */
    getSlatGapSlugRaw: function(custom_fence, info) {
        var el = document.querySelector('[data-section="2"] [name="slat_gap"]');
        if (el && el.value !== undefined && el.value !== null && String(el.value).trim() !== '') {
            return String(el.value).trim();
        }
        var editSpacing = (custom_fence || []).find(function(item) {
            return item && item.control_key === 'edit_spacing';
        });
        var selected = editSpacing?.settings?.find(function(item) {
            return item && item.key === 'slat_gap';
        })?.val;
        if (selected !== undefined && selected !== null && String(selected).trim() !== '') {
            return String(selected).trim();
        }
        var options = info?.settings?.edit_spacing?.fields?.[0]?.options || [];
        var def = options.find(function(item) {
            return item && item.default;
        });
        if (def && def.slug !== undefined && def.slug !== null && String(def.slug).trim() !== '') {
            return String(def.slug).trim();
        }
        return '';
    },

    /** Slat size pitch mm from option slug (65.3 / 90.3) for panel row/height formulas (M7). */
    getSlatSizePitchMm: function(custom_fence, tabRow0, slugNorm) {
        var slugVal = '';
        try {
            slugVal = this.getSlatSizeValForStep2(custom_fence, tabRow0, slugNorm) || '';
        } catch (e) {}
        var n = parseFloat(String(slugVal).trim());
        if (Number.isFinite(n) && n > 0) {
            return n;
        }
        return 65.3;
    },

    /**
     * Slat Planner V6 pitch inputs for panel height / row count (F80/F81).
     * BOM/cart still uses integer getSizeMm / getGapMm separately.
     */
    resolveSlatPanelPitchInputs: function(context, slug) {
        context = context || {};
        var slugNorm =
            slug && typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(slug)
                : String(
                      slug ||
                          context.tabInfo?.[0]?.fence ||
                          context.tabInfo?.[0]?.style ||
                          ''
                  );
        var fenceInfo = context.fenceInfo || [];
        var tabRow0 = context.tabInfo && context.tabInfo[0] ? context.tabInfo[0] : null;
        var infoMeta = null;
        try {
            if (typeof fc_data !== 'undefined' && slugNorm && fc_data[slugNorm]) {
                infoMeta = fc_data[slugNorm];
            }
        } catch (eInfo) {}
        var sizePitch = this.getSlatSizePitchMm(fenceInfo, tabRow0, slugNorm);
        var gapSlugRaw = this.getSlatGapSlugRaw(fenceInfo, infoMeta);
        var gapPitch = this.gapSlugToPitchMm(gapSlugRaw);
        if (!Number.isFinite(gapPitch)) {
            gapPitch = this.gapSlugToPitchMm(String(this.getGapMm(fenceInfo, infoMeta)));
        }
        if (!Number.isFinite(gapPitch)) {
            gapPitch = 9.3;
        }
        return {
            sizePitch: sizePitch,
            gapPitch: gapPitch
        };
    },

    /**
     * Slat row count for panel height (F81).
     * floor((MaxHeight − (6 + sizePitch)) / (sizePitch + gapPitch)) + 1
     */
    countSlatPanelRowsFromMaxHeightMm: function(maxHMm, sizePitch, gapPitch) {
        var h = Number(maxHMm);
        var s = Number(sizePitch);
        var g = Number(gapPitch);
        var pitch = s + g;
        var clearance = this.getSlatPanelEndAllowanceMm() + s;
        if (
            !Number.isFinite(h) ||
            h <= 0 ||
            !Number.isFinite(s) ||
            !Number.isFinite(g) ||
            g < 0 ||
            !Number.isFinite(pitch) ||
            pitch <= 0
        ) {
            return 0;
        }
        var rows = Math.floor((h - clearance) / pitch) + 1;
        return rows < 1 ? 0 : rows;
    },

    /**
     * Panel height (mm) from max fence height (F80).
     * (rows × (sizePitch + gapPitch)) − gapPitch + 6
     */
    computeSlatPanelHeightMmFromMaxHeight: function(maxHMm, sizePitch, gapPitch) {
        var rows = this.countSlatPanelRowsFromMaxHeightMm(maxHMm, sizePitch, gapPitch);
        if (rows < 1) {
            return 0;
        }
        var s = Number(sizePitch);
        var g = Number(gapPitch);
        var pitch = s + g;
        if (!Number.isFinite(pitch) || pitch <= 0) {
            return 0;
        }
        return Math.round(rows * pitch - g + this.getSlatPanelEndAllowanceMm());
    },

    /** Bottom gap (mm) = Max Height − Panel Height (F77 − F80). */
    computeSlatBottomGapMm: function(maxHMm, sizePitch, gapPitch) {
        var maxH = Math.round(Number(maxHMm));
        if (!Number.isFinite(maxH) || maxH <= 0) {
            return 0;
        }
        var panel = this.computeSlatPanelHeightMmFromMaxHeight(maxH, sizePitch, gapPitch);
        if (!Number.isFinite(panel) || panel <= 0) {
            return 0;
        }
        return Math.max(0, maxH - panel);
    },

    /**
     * @deprecated Use resolveSlatPanelPitchInputs — kept for callers expecting gap/size ints.
     */
    resolveSlatPanelHeightInputs: function(context, slug) {
        var pitch = this.resolveSlatPanelPitchInputs(context, slug);
        return {
            slatGap: pitch.gapPitch,
            slatSize: pitch.sizePitch,
            sizePitch: pitch.sizePitch,
            gapPitch: pitch.gapPitch
        };
    },

    /**
     * Calculated panel height (mm) for Step 3 labels / visuals — not the Step 2 max height.
     */
    resolveSlatPanelHeightMm: function(context, calc, opts) {
        opts = opts || {};
        context = context || {};
        var slug =
            context.tabInfo?.[0]?.fence ||
            context.tabInfo?.[0]?.style ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData().slug : '');
        var maxH =
            opts.forGate && this.isMainSlatSlug(slug)
                ? this.resolveGateSlatHeightMm(context, calc)
                : this.resolveSlatFenceHeightMm(context, calc);
        if (!Number.isFinite(maxH) || maxH <= 0) {
            return 0;
        }
        var pitch = this.resolveSlatPanelPitchInputs(context, slug);
        return this.computeSlatPanelHeightMmFromMaxHeight(maxH, pitch.sizePitch, pitch.gapPitch);
    },

    /** Gate Options modal — Gate Height min/max (mm); fixed range, not Step 2 matrix. */
    getGateModalFenceHeightBoundsMm: function() {
        return { min: 199, max: 2240 };
    },

    enableGateModalHeightQtyButtons: function() {
        $('.custom-gate .fc-gate-modal-max-height .fencing-qty-btn').removeClass('disabled');
    },

    validateGateModalCustomWidthField: function(el) {
        if (!el || el.name !== 'width') {
            return { valid: true, message: '' };
        }
        if (el.disabled || el.readOnly) {
            return { valid: true, message: '' };
        }
        var raw = String(el.value || '').trim();
        if (!raw) {
            return { valid: false, message: 'Please enter the amount' };
        }
        var val = parseInt(raw.replace(/,/g, ''), 10);
        if (!Number.isFinite(val) || val <= 0) {
            return { valid: false, message: 'Invalid value' };
        }
        var min = parseInt(el.getAttribute('data-min') || '', 10);
        var max = parseInt(el.getAttribute('data-max') || '', 10);
        if (Number.isFinite(min) && val < min) {
            return {
                valid: false,
                message: ' Invalid ' + HELPER.number_format(min) + 'mm min'
            };
        }
        if (Number.isFinite(max) && val > max) {
            return {
                valid: false,
                message: ' Invalid ' + HELPER.number_format(max) + 'mm max'
            };
        }
        return { valid: true, message: '' };
    },

    /**
     * Slat Gate Options: Calculate enabled when Gate Height is valid and (STD or valid Custom width).
     */
    canSubmitGateModalCalculate: function() {
        if (typeof getSelectedFenceData !== 'function') {
            return false;
        }
        var fd = getSelectedFenceData();
        if (!this.isMainSlatSlug(fd.slug)) {
            return true;
        }

        var ghEl = this.getGateMaxFenceHeightEl();
        if (ghEl && !ghEl.disabled) {
            if (!this.validateMaxFenceHeightField(ghEl).valid) {
                return false;
            }
        }

        var useStdEl = document.querySelector('.custom-gate [name="use_std"]');
        if (useStdEl && useStdEl.checked) {
            return true;
        }

        var widthEl = document.querySelector('.custom-gate [name="width"]');
        if (!widthEl) {
            return false;
        }
        return this.validateGateModalCustomWidthField(widthEl).valid;
    },

    syncGateModalCalculateButtonState: function() {
        if (typeof getSelectedFenceData !== 'function') {
            return;
        }
        var fd = getSelectedFenceData();
        if (!this.isMainSlatSlug(fd.slug)) {
            return;
        }

        var $btn = $('.custom-gate .fc-gate-modal-calculate-btn');
        if (!$btn.length) {
            return;
        }

        var can = this.canSubmitGateModalCalculate();
        if (can) {
            $btn.removeAttr('disabled')
                .removeClass('disabled btn-light')
                .addClass('btn-dark');
        } else {
            $btn.attr('disabled', 'disabled')
                .removeClass('btn-dark')
                .addClass('btn-light disabled');
        }
    },

    //----------------------------------------------------------------------------------

    /** Step 2 numeric Fence Height min/max (mm). */
    getStep2FenceHeightBoundsMm: function() {
        var cfg = this.slatHeightRowConfig || {};
        var min = parseInt(cfg.minHeightMm, 10);
        var max = parseInt(cfg.maxHeightMm, 10);
        if (!Number.isFinite(min) || min < 1) {
            min = 199;
        }
        if (!Number.isFinite(max) || max < min) {
            max = 5800;
        }
        return { min: min, max: max };
    },

    /**
     * Fence height (mm) for a given slat row count, face size, and gap (65mm table: 5 rows @ 5mm gap = 348mm).
     */
    computeFenceHeightMmFromRowCount: function(rowCount, slatSizeMm, gapMm) {
        var n = Math.round(Number(rowCount));
        var s = Number(slatSizeMm);
        var g = Number(gapMm);
        var pitch = s + g;
        if (!Number.isFinite(n) || n < 1 || !Number.isFinite(pitch) || pitch <= 0 || !Number.isFinite(g) || g < 0) {
            return null;
        }
        return Math.round(n * pitch - g + this.getSlatHeightEndAllowanceMm());
    },

    /**
     * Slat row count from fence height (inverse of computeFenceHeightMmFromRowCount).
     */
    countSlatRowsFromHeightMm: function(maxHMm, slatSizeMm, gapMm) {
        var h = Number(maxHMm);
        var s = Number(slatSizeMm);
        var g = Number(gapMm);
        var pitch = s + g;
        var allowance = this.getSlatHeightEndAllowanceMm();
        if (!Number.isFinite(h) || h <= 0 || !Number.isFinite(pitch) || pitch <= 0 || !Number.isFinite(g) || g < 0) {
            return 0;
        }
        var rows = Math.floor((h + g - allowance) / pitch);
        return rows < 1 ? 0 : rows;
    },

    /**
     * Planner label only: whole mm from titles like "12mm" or numeric slugs (incl. legacy 9.3).
     */
    getGapDisplayLabelMm: function(custom_fence, info) {
        var editSpacing = custom_fence?.find?.(function(item) {
            return item?.control_key === 'edit_spacing';
        });
        var selected = editSpacing?.settings?.find?.(function(item) {
            return item?.key === 'slat_gap';
        })?.val;

        var options = info?.settings?.edit_spacing?.fields?.[0]?.options || [];
        var selectedOpt = selected
            ? options.find(function(item) { return String(item?.slug) === String(selected); })
            : options.find(function(item) { return item?.default; });

        var title = selectedOpt?.title;
        if (typeof title === 'string') {
            var tm = title.match(/(\d+)\s*mm/i);
            if (tm) return tm[1];
        }

        var slugGap = parseFloat(selectedOpt?.slug);
        if (Number.isFinite(slugGap) && slugGap >= 0) {
            return String(Math.round(slugGap));
        }

        return '9';
    },

    //----------------------------------------------------------------------------------

    /** Step 2 `[name="slat_gap"]` value: saved fields, then segment `edit_spacing` (only if style never saved). */
    getSlatGapValForStep2: function(custom_fence, tabRow0, slugNorm) {
        var restored = [];
        if (typeof fcStep2RestoreFieldsForStyle === 'function' && tabRow0) {
            restored = fcStep2RestoreFieldsForStyle(tabRow0, slugNorm) || [];
        }
        var hasStyleSnapshot =
            tabRow0 &&
            tabRow0.fieldsByStyle &&
            Object.prototype.hasOwnProperty.call(tabRow0.fieldsByStyle, slugNorm);
        for (var i = 0; i < restored.length; i++) {
            if (restored[i] && restored[i].name === 'slat_gap') {
                var rv = restored[i].value;
                if (rv !== undefined && rv !== null && String(rv) !== '') {
                    return String(rv);
                }
                if (hasStyleSnapshot) {
                    return '';
                }
            }
        }
        if (hasStyleSnapshot) {
            return '';
        }

        var editSpacing = (custom_fence || []).find(function(item) {
            return item && item.control_key === 'edit_spacing';
        });
        if (!editSpacing || !editSpacing.settings) {
            return '';
        }

        var byKey = editSpacing.settings.find(function(item) {
            return item && item.key === 'slat_gap';
        });
        if (byKey && byKey.val !== undefined && byKey.val !== null && String(byKey.val) !== '') {
            return String(byKey.val);
        }

        var legacy = editSpacing.settings[0];
        if (legacy && legacy.val !== undefined && legacy.val !== null && String(legacy.val) !== '') {
            return String(legacy.val);
        }

        return '';
    },

    //----------------------------------------------------------------------------------

    /** Write Step 2 slat gap into `custom_fence-{tab}-{slug}` (`edit_spacing` / `slat_gap`). */
    persistSlatGapFromStep2: function(tab, slug, val) {
        if (!this.isSlatLike(slug)) {
            return;
        }
        if (tab === undefined || tab === null) {
            return;
        }

        var slugNorm =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(slug) : String(slug || '');
        var rawVal = val !== undefined && val !== null ? String(val).trim() : '';
        var itemKey = 'custom_fence-' + tab + '-' + slugNorm;
        var cf = [];
        try {
            cf = typeof readCustomFenceSegment === 'function'
                ? readCustomFenceSegment(tab, slugNorm)
                : JSON.parse(localStorage.getItem(itemKey) || '[]');
        } catch (e) {
            cf = [];
        }
        if (!Array.isArray(cf)) {
            cf = [];
        }

        var settings = rawVal ? [{ key: 'slat_gap', val: rawVal }] : [];
        var filtered = cf.filter(function(item) {
            return item && item.control_key !== 'edit_spacing';
        });

        if (rawVal) {
            filtered.push({
                id: slugNorm,
                control_key: 'edit_spacing',
                settings: settings
            });
        }

        try {
            localStorage.setItem(itemKey, JSON.stringify(filtered));
        } catch (e2) {}
    },

    //----------------------------------------------------------------------------------

    /**
     * Ordered gap rows for Step 2 (placeholder first). Avoids object-key iteration putting "" last after "0","5",…
     */
    buildSlatGapSelectOptions: function(info) {
        var rows = [{ value: '', label: 'Select Gap' }];
        var opts = info?.settings?.edit_spacing?.fields?.[0]?.options || [];
        for (var oi = 0; oi < opts.length; oi++) {
            var opt = opts[oi];
            if (!opt || opt.slug === undefined || opt.slug === null) {
                continue;
            }
            rows.push({
                value: String(opt.slug),
                label: opt.title || String(opt.slug) + 'mm'
            });
        }
        return rows;
    },

    populateSlatGapStep2Select: function(rows) {
        var $sel = $('[data-section="2"] [name="slat_gap"]');
        if (!$sel.length || !rows || !rows.length) {
            return;
        }
        $sel.empty();
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            if (!row) {
                continue;
            }
            $sel.append(
                $('<option>', {
                    value: row.value != null ? String(row.value) : '',
                    text: row.label || ''
                })
            );
        }
    },

    ensureStep2SlatSelectRow: function() {
        var $field = $('[data-section="2"] .step-2_field');
        var $gap = $('[data-section="2"] [name="slat_gap"]').closest('.fc-input-container');
        var $size = $('[data-section="2"] [name="slat_size"]').closest('.fc-input-container');
        if (!$field.length || (!$gap.length && !$size.length)) {
            return;
        }
        var $row = $field.children('.fc-step2-slat-select-row');
        if (!$row.length) {
            $row = $('<div class="row fc-step2-slat-select-row g-2 align-items-start mb-2"></div>');
            $field.prepend($row);
        }
        if ($size.length) {
            $size.removeClass('mb-2').addClass('col-md-6 col-12');
            $row.append($size);
        }
        if ($gap.length) {
            $gap.removeClass('mb-2').addClass('col-md-6 col-12');
            $row.append($gap);
        }
    },

    /** Restore Step 2 height/pair slots before fence fields are rebuilt. */
    resetStep2SlatFieldLayout: function() {
        var $section = $('[data-section="2"]');
        var $field = $section.find('.step-2_field');
        var $row = $section.find('.fc-step2-slat-height-row');
        if (!$row.length) {
            return;
        }

        var $heightSlot = $row.find('.fc-step2-height-slot');
        var $pairSlot = $row.find('.fc-step2-pair-slot');

        $heightSlot.children('.fc-input-container').appendTo($field);
        $pairSlot.children('.fc-input-container').appendTo($field);

        var $gateInPair = $pairSlot.children('.fc-step2-gate-only');
        if ($gateInPair.length) {
            $gateInPair.insertAfter($row);
        }

        $heightSlot.empty();
        $pairSlot.empty();
        $row.addClass('d-none');
        this.syncStep2BlocksSpacingBeforeOverall();
    },

    /**
     * Step 2 left column: margin below the last field block before Overall Length (e.g. Gate ONLY alone).
     */
    syncStep2BlocksSpacingBeforeOverall: function() {
        var $col = $('[data-section="2"] .fencing-section--step2 .col-lg-5').first();
        if (!$col.length) {
            return;
        }

        $col.children('.step-2_field, .fc-step2-slat-height-row, .fc-step2-gate-only').removeClass(
            'fc-step2-spacer-before-overall'
        );

        var $visible = $col.children().filter(function() {
            var $el = $(this);
            if ($el.hasClass('fc-input-container')) {
                return false;
            }
            if ($el.hasClass('step-label') || $el.hasClass('fencing-content-title')) {
                return false;
            }
            if ($el.hasClass('step-2_field')) {
                return $el.children().length > 0;
            }
            if ($el.hasClass('fc-step2-slat-height-row')) {
                return !$el.hasClass('d-none');
            }
            if ($el.hasClass('fc-step2-gate-only')) {
                return !$el.hasClass('d-none');
            }
            return false;
        });

        if ($visible.length) {
            $visible.last().addClass('fc-step2-spacer-before-overall');
        }
    },

    /**
     * Mark height row when only one column is filled (extra space before Overall Length).
     */
    syncStep2HeightRowSlotCount: function($row, $heightSlot, $pairSlot) {
        if (!$row || !$row.length) {
            return;
        }
        var count = 0;
        if ($heightSlot && $heightSlot.children().length) {
            count += 1;
        }
        if ($pairSlot && $pairSlot.children().length) {
            count += 1;
        }
        $row.toggleClass('fc-step2-row--single', count === 1);
        $row.toggleClass('fc-step2-row--double', count >= 2);
    },

    /**
     * Barr: Fence Height + Gate ONLY in one row (two columns).
     */
    ensureStep2BarrHeightGateRow: function() {
        var $section = $('[data-section="2"]');
        var $field = $section.find('.step-2_field');
        var $row = $section.find('.fc-step2-slat-height-row');
        if (!$row.length || !$field.length) {
            return;
        }

        var $heightSlot = $row.find('.fc-step2-height-slot');
        var $pairSlot = $row.find('.fc-step2-pair-slot');
        var $height = $section.find('[name="fence_height"]').closest('.fc-input-container');
        var $pair = $section.find('.fc-step2-gate-only');

        if (!$height.length || !$pair.length || $pair.hasClass('d-none')) {
            this.resetStep2SlatFieldLayout();
            return;
        }

        this.moveStep2HeightPairIntoRow($height, $pair, $heightSlot, $pairSlot, {
            pairIsInfill: false
        });
        $row.removeClass('d-none');

        if ($section.is(':visible')) {
            this.reinitStep2SlatSelect2($section.find('[name="fence_height"]'));
        }

        this.syncStep2HeightRowSlotCount($row, $heightSlot, $pairSlot);
        this.syncStep2BlocksSpacingBeforeOverall();
    },

    /**
     * Step 2 two-column row: height field (left) + gate only or panel count (right).
     * Slat Infill: Fence Height | Number of Panels.
     * Slat: Fence Height | Gate ONLY.
     * Barr: Fence Height | Gate ONLY.
     */
    ensureStep2SlatHeightPairRow: function(slug) {
        var slugNorm =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(slug || '')
                : String(slug || '');

        if (slugNorm === 'barr') {
            this.ensureStep2BarrHeightGateRow();
            return;
        }

        var $section = $('[data-section="2"]');
        var $field = $section.find('.step-2_field');
        var $row = $section.find('.fc-step2-slat-height-row');
        if (!$row.length || !$field.length) {
            return;
        }

        var $heightSlot = $row.find('.fc-step2-height-slot');
        var $pairSlot = $row.find('.fc-step2-pair-slot');
        var $height = $section.find('[name="max_fence_height"]').closest('.fc-input-container');
        var isInfill = slugNorm === 'slat_fence_infill';
        var isMainSlat = slugNorm === 'slat' || slugNorm === 'slat_fence';

        if (!this.isSlatLike(slugNorm) || !$height.length) {
            this.resetStep2SlatFieldLayout();
            return;
        }

        var $pair = null;
        if (isInfill) {
            $pair = $section.find('[name="panel_count"]').closest('.fc-input-container');
            if (!$pair.length) {
                this.resetStep2SlatFieldLayout();
                return;
            }
        } else if (isMainSlat) {
            $pair = $section.find('.fc-step2-gate-only');
            if (!$pair.length || $pair.hasClass('d-none')) {
                this.resetStep2SlatFieldLayout();
                return;
            }
        } else {
            this.resetStep2SlatFieldLayout();
            return;
        }

        this.moveStep2HeightPairIntoRow($height, $pair, $heightSlot, $pairSlot, {
            pairIsInfill: isInfill
        });
        $row.removeClass('d-none');
        this.syncStep2HeightRowSlotCount($row, $heightSlot, $pairSlot);
        this.syncStep2BlocksSpacingBeforeOverall();
    },

    /** Move height + right column (gate only or panel count) into the Step 2 pair row slots. */
    moveStep2HeightPairIntoRow: function($height, $pair, $heightSlot, $pairSlot, opts) {
        opts = opts || {};
        var pairIsInfill = !!opts.pairIsInfill;

        var heightNeedsMove = !$height.parent().is($heightSlot);
        var pairNeedsMove = !$pair.parent().is($pairSlot);

        if (heightNeedsMove || pairNeedsMove) {
            var $selects = $height.find('select');
            if ($pair && $pair.find) {
                $selects = $selects.add($pair.find('select'));
            }
            if (typeof fcDestroyStep2Select2 === 'function') {
                fcDestroyStep2Select2($selects);
            }
        }

        if (heightNeedsMove) {
            $height.removeClass('mb-2').addClass('mb-0 w-100');
            $heightSlot.empty().append($height);
        }

        if (pairNeedsMove) {
            $pairSlot.empty();
            if (pairIsInfill) {
                $pair.removeClass('mb-2').addClass('mb-0 w-100');
                $pairSlot.append($pair);
            } else {
                $pairSlot.append($pair);
            }
        }
    },

    /** Destroy/rebuild Step 2 Select2 widgets (safe after DOM layout moves). */
    reinitStep2SlatSelect2: function($selects) {
        var $section = $('[data-section="2"]');
        if (!$section.length) {
            return;
        }

        var $targets =
            $selects && $selects.length
                ? $selects
                : $section.find('[name="slat_gap"], [name="slat_size"]');

        if (typeof fcDestroyStep2Select2 === 'function') {
            fcDestroyStep2Select2($targets);
        }

        if (!$section.is(':visible')) {
            return;
        }

        if (typeof fcInitStep2Select2 === 'function') {
            fcInitStep2Select2($targets);
        }
    },

    /**
     * Step 2 is often hidden during `extra_fields()`; init Select2 after it is shown.
     */
    scheduleStep2SlatSelect2AfterVisible: function(slug, tabRow0, opts) {
        opts = opts || {};
        var self = this;

        function run() {
            self.reinitStep2SlatSelect2();
            if (!opts.skipRestore) {
                self.restoreStep2MaxFenceHeightAfterStep2Init(slug, tabRow0);
            }
        }

        var $section = $('[data-section="2"]');
        if ($section.length && $section.is(':visible')) {
            run();
            return;
        }

        setTimeout(run, 280);
    },

    buildSlatSizeSelectOptions: function(info) {
        var formEntry = (info?.form || []).find(function(f) {
            return f && f.slug === 'slat_size';
        });
        if (formEntry && Array.isArray(formEntry.slat_size_rows) && formEntry.slat_size_rows.length) {
            return formEntry.slat_size_rows;
        }

        var rows = [{ value: '', label: 'Select Slat Size' }];
        var fields = info?.settings?.panel_options?.fields || [];
        var sizeField = null;
        for (var fi = 0; fi < fields.length; fi++) {
            if (fields[fi] && fields[fi].slug === 'slat_size') {
                sizeField = fields[fi];
                break;
            }
        }
        var opts = sizeField?.options || [];
        for (var oi = 0; oi < opts.length; oi++) {
            var opt = opts[oi];
            if (!opt || opt.slug === undefined || opt.slug === null) {
                continue;
            }
            rows.push({
                value: String(opt.slug),
                label: opt.title || String(opt.slug) + 'mm'
            });
        }
        return rows;
    },

    populateSlatSizeStep2Select: function(rows) {
        var $sel = $('[data-section="2"] [name="slat_size"]');
        if (!$sel.length || !rows || !rows.length) {
            return;
        }
        $sel.empty();
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            if (!row) {
                continue;
            }
            $sel.append(
                $('<option>', {
                    value: row.value != null ? String(row.value) : '',
                    text: row.label || ''
                })
            );
        }
    },

    getSlatSizeValForStep2: function(custom_fence, tabRow0, slugNorm) {
        var restored = [];
        if (typeof fcStep2RestoreFieldsForStyle === 'function' && tabRow0) {
            restored = fcStep2RestoreFieldsForStyle(tabRow0, slugNorm) || [];
        }
        var hasStyleSnapshot =
            tabRow0 &&
            tabRow0.fieldsByStyle &&
            Object.prototype.hasOwnProperty.call(tabRow0.fieldsByStyle, slugNorm);
        for (var i = 0; i < restored.length; i++) {
            if (restored[i] && restored[i].name === 'slat_size') {
                var rv = restored[i].value;
                if (rv !== undefined && rv !== null && String(rv) !== '') {
                    return String(rv);
                }
                if (hasStyleSnapshot) {
                    return '';
                }
            }
        }
        if (hasStyleSnapshot) {
            return '';
        }

        var panelOpts = (custom_fence || []).find(function(item) {
            return item && item.control_key === 'panel_options';
        });
        if (panelOpts && panelOpts.settings) {
            var byKey = panelOpts.settings.find(function(item) {
                return item && item.key === 'slat_size';
            });
            if (byKey && byKey.val !== undefined && byKey.val !== null && String(byKey.val) !== '') {
                return String(byKey.val);
            }
        }

        return '';
    },

    persistSlatSizeFromStep2: function(tab, slug, val) {
        if (!this.isSlatLike(slug)) {
            return;
        }
        if (tab === undefined || tab === null) {
            return;
        }

        var slugNorm =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(slug) : String(slug || '');
        var rawVal = val !== undefined && val !== null ? String(val).trim() : '';
        var itemKey = 'custom_fence-' + tab + '-' + slugNorm;
        var cf = [];
        try {
            cf =
                typeof readCustomFenceSegment === 'function'
                    ? readCustomFenceSegment(tab, slugNorm)
                    : JSON.parse(localStorage.getItem(itemKey) || '[]');
        } catch (e) {
            cf = [];
        }
        if (!Array.isArray(cf)) {
            cf = [];
        }

        var panelRow = cf.find(function(item) {
            return item && item.control_key === 'panel_options';
        });
        var settings = panelRow && Array.isArray(panelRow.settings) ? panelRow.settings.slice() : [];
        settings = settings.filter(function(item) {
            return item && item.key !== 'slat_size';
        });

        if (rawVal) {
            settings.push({ key: 'slat_size', val: rawVal });
        }

        var filtered = cf.filter(function(item) {
            return item && item.control_key !== 'panel_options';
        });

        if (settings.length) {
            filtered.push({
                id: slugNorm,
                control_key: 'panel_options',
                settings: settings
            });
        }

        try {
            localStorage.setItem(itemKey, JSON.stringify(filtered));
        } catch (e2) {}
    },

    /** Saved Step 2 max_fence_height from `fieldsByStyle` (survives page reload). */
    getMaxFenceHeightValForStep2: function(tabRow0, slug) {
        if (!tabRow0) {
            return '';
        }
        var restored = [];
        if (typeof fcStep2RestoreFieldsForStyle === 'function') {
            restored = fcStep2RestoreFieldsForStyle(tabRow0, slug) || [];
        } else if (tabRow0.fields && tabRow0.fields.length) {
            restored = tabRow0.fields;
        }
        for (var i = 0; i < restored.length; i++) {
            if (restored[i] && restored[i].name === 'max_fence_height') {
                var rv = restored[i].value;
                if (rv !== undefined && rv !== null && String(rv) !== '') {
                    return String(rv);
                }
            }
        }
        return '';
    },

    getStep2MaxFenceHeightEl: function() {
        return document.querySelector('[data-section="2"] [name="max_fence_height"]');
    },

    /** Apply saved height (mm) to Step 2 Fence Height control (input or legacy select). */
    applyMaxFenceHeightValue: function(el, preservedMm) {
        if (!el) {
            return false;
        }
        if (el.tagName === 'INPUT') {
            var n = parseInt(preservedMm, 10);
            if (!Number.isFinite(n) || n <= 0) {
                return false;
            }
            el.value = String(n);
            return true;
        }
        if (el.tagName === 'SELECT') {
            return this.applyMaxFenceHeightSelectValue(el, preservedMm);
        }
        return false;
    },

    /** Apply a saved height (mm) to the Step 2 select, adding an option when needed. */
    applyMaxFenceHeightSelectValue: function(el, preservedMm) {
        if (!el || el.tagName !== 'SELECT') {
            return false;
        }
        var preserved = parseInt(preservedMm, 10);
        if (!Number.isFinite(preserved) || preserved <= 0) {
            return false;
        }

        var found = false;
        for (var i = 0; i < el.options.length; i++) {
            if (el.options[i].value === String(preserved)) {
                found = true;
                break;
            }
        }
        if (!found) {
            $(el).append(
                $('<option>', {
                    value: String(preserved),
                    text: preserved + ' mm'
                })
            );
        }
        el.value = String(preserved);
        return true;
    },

    /**
     * After Step 2 markup + Select2 init (and height/panel row layout), re-apply saved Fence Height.
     */
    restoreStep2MaxFenceHeightAfterStep2Init: function(slug, tabRow0) {
        if (!this.isSlatLike(slug)) {
            return;
        }

        var el = this.getStep2MaxFenceHeightEl();
        if (!el) {
            return;
        }

        var saved = this.getMaxFenceHeightValForStep2(tabRow0, slug);
        if (!saved) {
            return;
        }

        if (el.tagName === 'INPUT') {
            this.refreshStep2MaxFenceHeightBounds({});
            el = this.getStep2MaxFenceHeightEl();
            if (!el) {
                return;
            }
            this.applyMaxFenceHeightValue(el, saved);
            return;
        }

        if (el.disabled || el.tagName !== 'SELECT') {
            return;
        }

        if (!this.isStep2SlatGapAndSizeReady()) {
            return;
        }

        if (el.options.length <= 1) {
            this.refreshMaxFenceHeightSelect();
            el = this.getStep2MaxFenceHeightEl();
            if (!el || el.disabled) {
                return;
            }
        }

        var preserved = parseInt(saved, 10);
        if (!this.applyMaxFenceHeightSelectValue(el, preserved)) {
            return;
        }

        this.reinitStep2SlatSelect2($(el));
    },

    /** Persist Fence Height (mm) to tab `fieldsByStyle` (Slat / Slat Infill). */
    persistMaxFenceHeightFromStep2: function(tab, slug, val) {
        if (!this.isSlatLike(slug)) {
            return;
        }
        if (tab === undefined || tab === null) {
            return;
        }

        var slugNorm =
            typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(slug) : String(slug || '');
        var rawVal = val !== undefined && val !== null ? String(val).trim() : '';

        var tabInfo = [];
        try {
            var raw = localStorage.getItem('custom_fence-' + tab);
            tabInfo = raw ? JSON.parse(raw) : [];
        } catch (e) {
            tabInfo = [];
        }
        if (!tabInfo[0]) {
            return;
        }

        tabInfo[0].fieldsByStyle = tabInfo[0].fieldsByStyle || {};
        var fields = tabInfo[0].fieldsByStyle[slugNorm];
        fields = Array.isArray(fields) ? fields.slice() : [];

        fields = fields.filter(function(item) {
            return item && item.name !== 'max_fence_height';
        });
        if (rawVal) {
            fields.push({ name: 'max_fence_height', value: rawVal });
        }

        tabInfo[0].fieldsByStyle[slugNorm] = fields;

        var activeSlug =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(tabInfo[0].style || '')
                : String(tabInfo[0].style || '');
        if (activeSlug === slugNorm) {
            tabInfo[0].fields = fields;
        }

        try {
            localStorage.setItem('custom_fence-' + tab, JSON.stringify(tabInfo));
        } catch (e2) {}
    },

    hydrateStep2SlatSizeSelect: function(slug, tabRow0, custom_fence, info, opts) {
        opts = opts || {};
        if (!this.isSlatLike(slug)) {
            return;
        }

        var rows = this.buildSlatSizeSelectOptions(info);
        this.populateSlatSizeStep2Select(rows);

        var val = opts.skipValues ? '' : this.getSlatSizeValForStep2(custom_fence, tabRow0, slug);
        $('[data-section="2"] [name="slat_size"]').val(val !== undefined && val !== null ? String(val) : '');
    },

    hydrateStep2SlatSelects: function(slug, tabRow0, custom_fence, info, opts) {
        opts = opts || {};
        if (!this.isSlatLike(slug)) {
            return;
        }
        this.hydrateStep2SlatGapSelect(slug, tabRow0, custom_fence, info, opts);
        this.hydrateStep2SlatSizeSelect(slug, tabRow0, custom_fence, info, opts);
        this.ensureStep2SlatSelectRow();
        // Layout row before enabling height + Select2 (moving a Select2 node breaks clicks).
        this.ensureStep2SlatHeightPairRow(slug);
        this.syncStep2MaxFenceHeightDisabledState(slug);
        this.reinitStep2SlatSelect2();
    },

    /** True when Step 2 slat gap and slat size both have a selection (not placeholder). */
    isStep2SlatGapAndSizeReady: function() {
        var gapEl = document.querySelector('[data-section="2"] [name="slat_gap"]');
        var sizeEl = document.querySelector('[data-section="2"] [name="slat_size"]');
        if (!gapEl && !sizeEl) {
            return true;
        }
        if (gapEl && !this.validateSlatGapField(gapEl).valid) {
            return false;
        }
        if (sizeEl && !this.validateSlatSizeField(sizeEl).valid) {
            return false;
        }
        return true;
    },

    /**
     * Ensure the Fence Height select has a "Select height" placeholder and it is visibly selected.
     */
    seedMaxFenceHeightPlaceholder: function(el, opts) {
        opts = opts || {};
        el = el || document.querySelector('[data-section="2"] [name="max_fence_height"]');
        if (!el || el.tagName !== 'SELECT') {
            return;
        }

        var hasPlaceholder = false;
        for (var i = 0; i < el.options.length; i++) {
            if (el.options[i].value === '') {
                hasPlaceholder = true;
                break;
            }
        }
        if (!hasPlaceholder) {
            var $ph = $('<option>', { value: '', text: 'Select height' });
            if (el.options.length) {
                $(el).prepend($ph);
            } else {
                $(el).append($ph);
            }
        }

        var current = (el.value || '').toString().trim();
        if (opts.force || current === '') {
            for (var j = 0; j < el.options.length; j++) {
                if (el.options[j].value === '') {
                    el.selectedIndex = j;
                    el.value = '';
                    break;
                }
            }
        }
    },

    /**
     * Step 2 Fence Height: always enabled (numeric input). Updates min/max from slat gap + size matrix.
     */
    syncStep2MaxFenceHeightDisabledState: function(slug, opts) {
        opts = opts || {};
        if (!this.isSlatLike(slug)) {
            return;
        }

        var el = this.getStep2MaxFenceHeightEl();
        if (!el) {
            return;
        }

        var $wrap = $(el).closest('.fc-input-container');

        if (el.tagName === 'INPUT') {
            el.disabled = false;
            el.removeAttribute('aria-disabled');
            $wrap.removeClass('fc-step2-field--disabled');
            if (!opts.skipRefresh) {
                this.refreshStep2MaxFenceHeightBounds({
                    gapSlug: opts.gapSlug,
                    sizeSlug: opts.sizeSlug,
                    resetHeightIfInvalid: opts.resetHeightIfInvalid
                });
            }
            this.notifyStep2MaxFenceHeightChanged(el);
        } else if (el.tagName === 'SELECT') {
            var ready = this.isStep2SlatGapAndSizeReady();
            if (!ready) {
                el.disabled = true;
                el.setAttribute('aria-disabled', 'true');
                $wrap.addClass('fc-step2-field--disabled');
                if (typeof fcDestroyStep2Select2 === 'function') {
                    fcDestroyStep2Select2($(el));
                }
                $(el).empty();
                this.seedMaxFenceHeightPlaceholder(el, { force: true });
                $wrap.find('.fc-input-msg').removeClass('fcim-show').html('');
            } else {
                el.disabled = false;
                el.removeAttribute('aria-disabled');
                $wrap.removeClass('fc-step2-field--disabled');
                if (!opts.skipRefresh) {
                    this.refreshMaxFenceHeightSelect({ resetHeightIfInvalid: false });
                    this.ensureMaxFenceHeightPlaceholderOrValidOption(el);
                }
                this.notifyStep2MaxFenceHeightChanged(el);
            }
        }

        try {
            if (typeof updateCalculateButtonByStep2Completeness === 'function') {
                updateCalculateButtonByStep2Completeness();
            }
        } catch (eBtn) {}
    },

    /** After gap/size change: refresh height options + validation (original modal-save behaviour). */
    notifyStep2MaxFenceHeightChanged: function(el) {
        el = el || document.querySelector('[data-section="2"] [name="max_fence_height"]');
        if (!el || el.disabled) {
            return;
        }
        try {
            if (typeof maxFenceHeightValidation === 'function') {
                maxFenceHeightValidation.call(el, { target: el });
            }
        } catch (eVal) {}
    },

    validateSlatSizeField: function(el) {
        if (!el) {
            return { valid: false, message: 'Please select a slat size' };
        }
        var raw = String(el.value || '').trim();
        if (!raw) {
            return { valid: false, message: 'Please select a slat size' };
        }
        return { valid: true, message: '' };
    },

    //----------------------------------------------------------------------------------

    hydrateStep2SlatGapSelect: function(slug, tabRow0, custom_fence, info, opts) {
        opts = opts || {};
        if (!this.isSlatLike(slug)) {
            return;
        }

        var rows = this.buildSlatGapSelectOptions(info);
        this.populateSlatGapStep2Select(rows);

        var val = opts.skipValues ? '' : this.getSlatGapValForStep2(custom_fence, tabRow0, slug);
        $('[data-section="2"] [name="slat_gap"]').val(val !== undefined && val !== null ? String(val) : '');
    },

    //----------------------------------------------------------------------------------

    validateSlatGapField: function(el) {
        if (!el) {
            return { valid: false, message: 'Please select a slat gap' };
        }
        var raw = String(el.value || '').trim();
        if (!raw) {
            return { valid: false, message: 'Please select a slat gap' };
        }
        return { valid: true, message: '' };
    },

    //----------------------------------------------------------------------------------

    mmToPx: function(mm) {
        var baseMargin = FENCE.get('item', 'base_margin') || 0.1;
        return Math.max(1, Math.round(mm * baseMargin));
    },

    /**
     * First line of panel size label on Step 3 / project plan: calculated slat panel height (e.g. 2458H).
     */
    getPanelLabelFenceHeightLineHtml: function(slug, calc, opts) {
        opts = opts || {};
        if (!this.isSlatLike(slug)) return '';
        var context = opts.context || {};
        var h = Math.round(
            Number(
                this.resolveSlatPanelHeightMm(context, calc, {
                    forGate: !!(opts.forGate && this.isMainSlatSlug(slug))
                })
            )
        );
        if (!Number.isFinite(h) || h <= 0) return '';
        return '<span class="fc-panel-fence-height">' + String(h) + 'H</span><br>';
    },

    //----------------------------------------------------------------------------------

    getGateMaxFenceHeightEl: function() {
        return document.querySelector(
            '.fc-gate-modal-max-height [name="gate_max_fence_height"], .custom-gate [name="gate_max_fence_height"]'
        );
    },

    /**
     * Gate panel height (mm) from committed gate segment fields only (not live modal typing).
     * Falls back to Step 2 fence height when unset.
     */
    resolveGateSlatHeightMm: function(context, calc) {
        context = context || {};

        var fd = {
            slug: context.tabInfo?.[0]?.fence || context.tabInfo?.[0]?.style || '',
            info: context.fenceInfo || [],
            tabInfo: context.tabInfo
        };
        if (this.isMainSlatSlug(fd.slug) && this.isStep2GateOnlyActive(fd)) {
            var gateRowGo = (context.fenceInfo || []).find(function(item) {
                return item && item.control_key === 'gate';
            });
            var fieldsGo = gateRowGo?.settings?.fields || [];
            for (var ggi = 0; ggi < fieldsGo.length; ggi++) {
                if (fieldsGo[ggi]?.key === 'gate_max_fence_height') {
                    var ggv = parseInt(String(fieldsGo[ggi].val || '').replace(/,/g, ''), 10);
                    if (Number.isFinite(ggv) && ggv > 0) {
                        return ggv;
                    }
                }
            }
            var savedStep2 = parseInt(
                String(this.getMaxFenceHeightValForStep2(context.tabInfo?.[0], fd.slug) || '').replace(/,/g, ''),
                10
            );
            if (Number.isFinite(savedStep2) && savedStep2 > 0) {
                return savedStep2;
            }
        }

        var gateRow = (context.fenceInfo || []).find(function(item) {
            return item && item.control_key === 'gate';
        });
        var fields = gateRow?.settings?.fields || [];
        for (var gi = 0; gi < fields.length; gi++) {
            if (fields[gi]?.key === 'gate_max_fence_height') {
                var gv = parseInt(String(fields[gi].val || '').replace(/,/g, ''), 10);
                if (Number.isFinite(gv) && gv > 0) {
                    return gv;
                }
            }
        }

        return this.resolveSlatFenceHeightMm(context, calc);
    },

    persistGateMaxFenceHeightToGateFields: function(tab, slug, rawVal) {
        if (tab === undefined || tab === null || !slug) {
            return;
        }
        var val = rawVal === undefined || rawVal === null ? '' : String(rawVal).trim();
        try {
            var key = 'custom_fence-' + tab + '-' + slug;
            var cf = JSON.parse(localStorage.getItem(key) || 'null');
            if (!Array.isArray(cf)) {
                return;
            }
            for (var i = 0; i < cf.length; i++) {
                if (cf[i].control_key !== 'gate' || !cf[i].settings) {
                    continue;
                }
                if (!Array.isArray(cf[i].settings.fields)) {
                    cf[i].settings.fields = [];
                }
                var found = false;
                for (var j = 0; j < cf[i].settings.fields.length; j++) {
                    if (cf[i].settings.fields[j]?.key === 'gate_max_fence_height') {
                        cf[i].settings.fields[j].val = val;
                        cf[i].settings.fields[j].tag = 'input';
                        found = true;
                        break;
                    }
                }
                if (!found && val !== '') {
                    cf[i].settings.fields.push({
                        key: 'gate_max_fence_height',
                        tag: 'input',
                        val: val
                    });
                }
            }
            localStorage.setItem(key, JSON.stringify(cf));
        } catch (eStore) {}
    },

    refreshGateModalMaxFenceHeightBounds: function(opts) {
        opts = opts || {};
        if (typeof getSelectedFenceData !== 'function') {
            return;
        }
        var fd = getSelectedFenceData();
        if (!this.isMainSlatSlug(fd.slug)) {
            return;
        }
        var el = this.getGateMaxFenceHeightEl();
        if (!el || el.tagName !== 'INPUT') {
            return;
        }
        var bounds = this.getGateModalFenceHeightBoundsMm();
        el.setAttribute('data-min', String(bounds.min));
        el.setAttribute('data-max', String(bounds.max));
    },

    syncGateModalMaxFenceHeightVisibility: function() {
        var row = document.querySelector('.fc-gate-modal-gate-only-height-row');
        var col = document.querySelector('.fc-gate-modal-max-height');
        if (!row && !col) {
            return;
        }
        // Main Slat always shows Gate Height in the modal (including Step 2 Gate ONLY).
        var show = false;
        if (typeof getSelectedFenceData === 'function') {
            var fd = getSelectedFenceData();
            show = this.isMainSlatSlug(fd.slug);
        }
        if (col) {
            col.style.display = show ? '' : 'none';
        }
        var goCol = row ? row.querySelector('.select-gate_only')?.closest('.col-sm-6') : null;
        if (goCol) {
            goCol.classList.toggle('col-sm-12', !show);
            goCol.classList.toggle('col-sm-6', show);
        }
        if (!show) {
            var el = this.getGateMaxFenceHeightEl();
            if (el) {
                el.value = '';
            }
        }
    },

    hydrateGateModalMaxFenceHeight: function() {
        if (typeof getSelectedFenceData !== 'function') {
            return;
        }
        var fd = getSelectedFenceData();
        if (!this.isMainSlatSlug(fd.slug)) {
            return;
        }
        var el = this.getGateMaxFenceHeightEl();
        if (!el) {
            return;
        }
        this.refreshGateModalMaxFenceHeightBounds({});

        var saved = '';
        var gateRow = (fd.info || []).find(function(item) {
            return item && item.control_key === 'gate';
        });
        var fields = gateRow?.settings?.fields || [];
        for (var i = 0; i < fields.length; i++) {
            if (fields[i]?.key === 'gate_max_fence_height' && fields[i].val !== undefined && fields[i].val !== '') {
                saved = String(fields[i].val);
                break;
            }
        }
        if (!saved && this.isStep2GateOnlyActive(fd)) {
            var step2Mm = this.getMaxFenceHeightMm();
            if (Number.isFinite(step2Mm) && step2Mm > 0) {
                saved = String(step2Mm);
            }
        }
        if (!saved) {
            saved = this.getMaxFenceHeightValForStep2(fd.tabInfo?.[0], fd.slug) || '';
        }
        if (saved) {
            var n = parseInt(saved, 10);
            var bounds = this.getGateModalFenceHeightBoundsMm();
            if (Number.isFinite(n) && n > 0) {
                if (n < bounds.min) {
                    n = bounds.min;
                } else if (n > bounds.max) {
                    n = bounds.max;
                }
                el.value = String(n);
            }
        }

        this.enableGateModalHeightQtyButtons();
        this.syncGateModalCalculateButtonState();
    },

    /**
     * Read Step 2 Fence Height (mm). `lenient` skips disabled/validation gates (Step 3 display + cart).
     */
    readStep2MaxFenceHeightMm: function(opts) {
        opts = opts || {};
        var el = this.getStep2MaxFenceHeightEl();
        if (!el) {
            return null;
        }
        if (!opts.lenient && el.disabled) {
            return null;
        }
        if (!opts.lenient) {
            if (el.tagName === 'INPUT' && !this.validateMaxFenceHeightField(el).valid) {
                return null;
            }
            if (el.tagName === 'SELECT' && !this.validateMaxFenceHeightField(el).valid) {
                return null;
            }
        }

        var n = parseInt(String(el.value || '').replace(/,/g, ''), 10);
        return Number.isFinite(n) && n > 0 ? n : null;
    },

    getMaxFenceHeightMm: function() {
        return this.readStep2MaxFenceHeightMm({ lenient: false });
    },

    /**
     * Fence height (mm) for slat row / BOM math — one source for Step 3 labels and cart.
     * Live Step 2 input → calculate_fences height → saved tab fields.
     */
    resolveSlatFenceHeightMm: function(context, calc) {
        context = context || {};

        try {
            var dom = this.readStep2MaxFenceHeightMm({ lenient: true });
            if (Number.isFinite(dom) && dom > 0) {
                return dom;
            }
        } catch (eDom) {}

        var fromCalc = parseInt(String(calc?.fence_size?.height ?? '').replace(/,/g, ''), 10);
        if (Number.isFinite(fromCalc) && fromCalc > 0) {
            return fromCalc;
        }

        try {
            var tab0 = context.tabInfo?.[0];
            if (tab0 && typeof fcReadTabRowStep2Field === 'function') {
                var slugNorm =
                    tab0.fence ||
                    tab0.style ||
                    (typeof getSelectedFenceData === 'function' ? getSelectedFenceData().slug : '');
                var fromStore = parseInt(
                    String(fcReadTabRowStep2Field(tab0, slugNorm, 'max_fence_height') || '').replace(
                        /,/g,
                        ''
                    ),
                    10
                );
                if (Number.isFinite(fromStore) && fromStore > 0) {
                    return fromStore;
                }
            }
        } catch (eStore) {}

        var fromCtx = parseInt(String(this.getContextFieldValue(context, 'max_fence_height', '')), 10);
        if (Number.isFinite(fromCtx) && fromCtx > 0) {
            return fromCtx;
        }

        try {
            var tabRow = context.tabInfo?.[0];
            if (tabRow) {
                var resolved = this.resolveCalcFenceHeights([tabRow]);
                var h = parseInt(String(resolved?.maxFenceHeight || '').replace(/,/g, ''), 10);
                if (Number.isFinite(h) && h > 0) {
                    return h;
                }
            }
        } catch (eTab) {}

        return 0;
    },

    /**
     * Allowed fence heights (mm) for slat size + gap: one height per row (minRows…maxRows).
     */
    getMaxFenceHeightOptions: function(slatMm, gapMm) {
        var s = Math.round(Number(slatMm)) >= 80 ? 90 : 65;
        var g = Number(gapMm);
        if (!Number.isFinite(g) || g < 0) {
            return [];
        }
        var limits = this.getSlatHeightRowLimits();
        var heights = [];
        for (var r = limits.minRows; r <= limits.maxRows; r++) {
            var h = this.computeFenceHeightMmFromRowCount(r, s, g);
            if (Number.isFinite(h) && h > 0) {
                heights.push(h);
            }
        }
        return heights;
    },

    /**
     * Set min/max (mm) on Fence Height numeric input from slat gap + slat size matrix (no preset option list).
     */
    refreshStep2MaxFenceHeightBounds: function(opts) {
        opts = opts || {};
        var fd = getSelectedFenceData();
        if (!this.isSlatLike(fd.slug)) {
            return;
        }

        var el = this.getStep2MaxFenceHeightEl();
        if (!el || el.tagName !== 'INPUT') {
            return;
        }

        var bounds;
        if (this.isMainSlatSlug(fd.slug) && this.isStep2GateOnlyActive(fd)) {
            bounds = this.getGateModalFenceHeightBoundsMm();
        } else {
            var custom_fence = fd.info || [];
            var info = fd.data;

            var gapMm = this.resolveStep2GapMmForHeight(opts, custom_fence, info);
            var slatMm = this.resolveStep2SlatMmForHeight(opts, custom_fence, info, fd.slug);

            bounds = this.getStep2FenceHeightBoundsMm();
        }
        el.setAttribute('data-min', String(bounds.min));
        el.setAttribute('data-max', String(bounds.max));

        if (opts.resetHeightIfInvalid !== false) {
            var cur = parseInt(el.value, 10);
            var minB = parseInt(el.getAttribute('data-min') || '', 10);
            var maxB = parseInt(el.getAttribute('data-max') || '', 10);
            if (
                Number.isFinite(cur) &&
                ((Number.isFinite(minB) && cur < minB) || (Number.isFinite(maxB) && cur > maxB))
            ) {
                el.value = '';
                try {
                    this.persistMaxFenceHeightFromStep2(fd.tab, fd.slug, '');
                } catch (eClr) {}
            }
        }

        try {
            var toPersist = (el.value || '').toString().trim();
            if (toPersist) {
                this.persistMaxFenceHeightFromStep2(fd.tab, fd.slug, toPersist);
            }
        } catch (ePersist) {}
    },

    resolveStep2GapMmForHeight: function(opts, custom_fence, info) {
        var gapMm = null;
        if (opts.gapSlug !== undefined && opts.gapSlug !== null && String(opts.gapSlug).trim() !== '') {
            gapMm = this.gapSlugToMm(opts.gapSlug, info);
        }
        if (!Number.isFinite(gapMm)) {
            gapMm = this.getStep2SlatGapMm(info);
        }
        if (!Number.isFinite(gapMm)) {
            gapMm = this.getGapMm(custom_fence, info);
        }
        return gapMm;
    },

    resolveStep2SlatMmForHeight: function(opts, custom_fence, info, slug) {
        var slatMm = null;
        if (opts.sizeSlug !== undefined && opts.sizeSlug !== null && String(opts.sizeSlug).trim() !== '') {
            var sizeN = parseFloat(opts.sizeSlug);
            if (Number.isFinite(sizeN)) {
                slatMm = sizeN >= 80 ? 90 : 65;
            }
        }
        if (!Number.isFinite(slatMm)) {
            slatMm = this.getStep2SlatSizeMm();
        }
        if (!Number.isFinite(slatMm)) {
            slatMm = this.getSizeMm(custom_fence, 65, slug);
        }
        return slatMm;
    },

    /**
     * Legacy: fill Max Fence Height select from Step 2 slat gap + slat size.
     * @param {object} [opts] — optional `gapSlug`, `sizeSlug` (from change handler); `resetHeightIfInvalid` clears height when no longer in matrix.
     */
    refreshMaxFenceHeightSelect: function(opts) {
        opts = opts || {};
        var fd = getSelectedFenceData();
        if (!this.isSlatLike(fd.slug)) return;

        var el = this.getStep2MaxFenceHeightEl();
        if (!el) {
            return;
        }

        if (el.tagName === 'INPUT') {
            this.refreshStep2MaxFenceHeightBounds(opts);
            return;
        }

        if (el.tagName !== 'SELECT' || el.disabled) return;

        if (!this.isStep2SlatGapAndSizeReady()) {
            return;
        }

        var custom_fence = fd.info || [];
        var info = fd.data;

        var gapMm = this.resolveStep2GapMmForHeight(opts, custom_fence, info);
        var slatMm = this.resolveStep2SlatMmForHeight(opts, custom_fence, info, fd.slug);

        var heights = this.getMaxFenceHeightOptions(slatMm, gapMm);

        var preserved = parseInt(el.value, 10);
        if (!Number.isFinite(preserved)) {
            var savedRaw = this.getMaxFenceHeightValForStep2(fd.tabInfo && fd.tabInfo[0], fd.slug);
            preserved = parseInt(savedRaw, 10);
        }

        $(el).empty();
        $(el).append($('<option>', { value: '', text: 'Select height' }));
        heights.forEach(function(h) {
            $(el).append($('<option>', { value: String(h), text: h + ' mm' }));
        });

        var resetInvalid = opts.resetHeightIfInvalid !== false;
        if (Number.isFinite(preserved) && preserved > 0 && heights.length) {
            if (heights.indexOf(preserved) !== -1) {
            el.value = String(preserved);
            } else if (resetInvalid) {
                this.seedMaxFenceHeightPlaceholder(el, { force: true });
                try {
                    this.persistMaxFenceHeightFromStep2(fd.tab, fd.slug, '');
                } catch (eClr) {}
        } else {
                this.applyMaxFenceHeightSelectValue(el, preserved);
            }
        } else {
            this.seedMaxFenceHeightPlaceholder(el, { force: true });
            if (resetInvalid && Number.isFinite(preserved) && preserved > 0 && heights.indexOf(preserved) === -1) {
                try {
                    this.persistMaxFenceHeightFromStep2(fd.tab, fd.slug, '');
                } catch (eClr2) {}
            }
        }

        try {
            var toPersist = (el.value || '').toString().trim();
            if (toPersist) {
                this.persistMaxFenceHeightFromStep2(fd.tab, fd.slug, toPersist);
            }
        } catch (ePersistRefresh) {}

        this.reinitStep2SlatSelect2($(el));
    },

    /** Step 2: Slat Gap changed — refresh Fence Height option list for the new gap. */
    onStep2SlatGapChanged: function(fd, gapSlug) {
        if (!fd || !this.isSlatLike(fd.slug)) {
            return;
        }
        this.syncStep2MaxFenceHeightDisabledState(fd.slug, {
            skipRefresh: true,
            gapSlug: gapSlug,
            resetHeightIfInvalid: true
        });
        this.refreshMaxFenceHeightSelect({
            gapSlug: gapSlug,
            resetHeightIfInvalid: true
        });
        this.notifyStep2MaxFenceHeightChanged();
    },

    /** Step 2: Slat Size changed — refresh Fence Height min/max for the new size. */
    onStep2SlatSizeChanged: function(fd, sizeSlug) {
        if (!fd || !this.isSlatLike(fd.slug)) {
            return;
        }
        this.syncStep2MaxFenceHeightDisabledState(fd.slug, {
            skipRefresh: true,
            sizeSlug: sizeSlug,
            resetHeightIfInvalid: true
        });
        this.refreshMaxFenceHeightSelect({
            sizeSlug: sizeSlug,
            resetHeightIfInvalid: true
        });
        this.notifyStep2MaxFenceHeightChanged();
    },

    /**
     * After reload / restore, keep "Select height" selected unless the current value matches an option.
     */
    ensureMaxFenceHeightPlaceholderOrValidOption: function(el) {
        el = el || document.querySelector('[data-section="2"] [name="max_fence_height"]');
        if (!el || el.tagName !== 'SELECT') return;

        var current = (el.value || '').toString().trim();
        if (current === '') {
            this.seedMaxFenceHeightPlaceholder(el, { force: true });
            return;
        }

        var ok = false;
        for (var i = 0; i < el.options.length; i++) {
            var opt = el.options[i];
            if (opt.value === current && opt.value !== '') {
                ok = true;
                break;
            }
        }

        if (!ok) {
            this.seedMaxFenceHeightPlaceholder(el, { force: true });
        }
    },

    //----------------------------------------------------------------------------------

    getSizeMm: function(custom_fence, fallback, slug) {
        var defaultVal = Number.isFinite(parseFloat(fallback)) ? parseFloat(fallback) : 65;

        var domSize = this.getStep2SlatSizeMm();
        if (Number.isFinite(domSize)) {
            return domSize;
        }

        var slugNorm =
            slug && typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(slug)
                : String(slug || 'slat');

        try {
            var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData(slugNorm) : null;
            var tabRow0 = fd?.tabInfo?.[0];
            var step2Val = this.getSlatSizeValForStep2(custom_fence || fd?.info || [], tabRow0, slugNorm);
            if (step2Val) {
                var fromStep2 = parseFloat(step2Val);
                if (Number.isFinite(fromStep2)) {
                    return fromStep2 >= 80 ? 90 : 65;
                }
            }
        } catch (eStep2) {}

        var panelOpt = custom_fence?.find?.(function(item) {
            return item?.control_key === 'panel_options';
        });
        var raw = panelOpt?.settings?.find?.(function(item) {
            return item?.key === 'slat_size';
        })?.val;

        var n = parseFloat(raw || '');
        if (Number.isFinite(n)) return n >= 80 ? 90 : 65;

        return defaultVal;
    },

    //----------------------------------------------------------------------------------

    getWidthDimensionOffset: function(fd) {
        var slug = fd?.slug;
        if (!this.isSlatLike(slug)) return 0;

        var wdf = '';
        try {
            var panelOpts = fd?.info?.find(function(item) {
                return item.control_key === 'panel_options';
            });
            var stored = panelOpts?.settings?.find(function(item) {
                return item?.key === 'width_dimension_from';
            })?.val;
            if (stored !== undefined && stored !== null && stored !== '') {
                wdf = String(stored);
            }
        } catch (err) {}

        if (!wdf) {
            wdf = $('[name="width_dimension_from"]:checked').val() || $('[name="width_dimension_from"]').val() || '';
        }

        if (!wdf && fd?.tabInfo?.[0]?.fields) {
            var found = fd.tabInfo[0].fields.find(function(item) {
                return item?.name === 'width_dimension_from';
            });
            wdf = found?.value;
        }

        var offset = parseInt(wdf, 10);
        if (!Number.isFinite(offset)) {
            offset = -1;
        }

        var postW = parseInt(FENCE.get(slug, 'post'), 10);
        if (Number.isFinite(postW) && Math.abs(offset) <= 5) {
            offset = offset * postW;
        }

        return offset;
    },

    //----------------------------------------------------------------------------------

    getDisplayOverallLength: function(slug, calculatedOverall, widthDimensionOffset) {
        if (this.isSlatLike(slug)) {
            return parseInt(calculatedOverall, 10) - parseInt(widthDimensionOffset || 0, 10);
        }
        return calculatedOverall;
    },

    //----------------------------------------------------------------------------------

    getHeightKeyFromMax: function(maxHeight) {
        var h = parseInt(maxHeight, 10);
        if (!Number.isFinite(h) || h <= 0) return 1200;
        if (h <= 1100) return 1000;
        if (h <= 1500) return 1200;
        return 1800;
    },

    /**
     * Main Slat only: stock post length tier (mm) from Step 2 Fence Height.
     * Tiers: <=1800, <=2400, <=2700, else 6000 (products.csv slat_post+50x50_* rows).
     */
    getSlatPostHeightTierMm: function(fenceHeightMm) {
        var h = parseInt(fenceHeightMm, 10);
        if (!Number.isFinite(h) || h <= 0) {
            return 2400;
        }
        if (h <= 1800) {
            return 1800;
        }
        if (h <= 2400) {
            return 2400;
        }
        if (h <= 2700) {
            return 2700;
        }
        return 6000;
    },

    /** Main Slat only: catalog slug for 50×50 post from Fence Height (mm). */
    getSlatPostCatalogSlugFromFenceHeightMm: function(fenceHeightMm) {
        var tier = this.getSlatPostHeightTierMm(fenceHeightMm);
        return 'slat_post+50x50_' + tier;
    },

    /** Resolve Fence Height (mm) then post catalog slug (main Slat planner sections only). */
    resolveSlatPostCatalogSlug: function(context, calc) {
        if (!this.isMainSlatSlug(context?.tabInfo?.[0]?.fence || context?.tabInfo?.[0]?.style || '')) {
            return '';
        }
        var fenceHm = this.resolveSlatFenceHeightMm(context, calc);
        var gateHm = this.resolveGateSlatHeightMm(context, calc);
        var heightMm = this.resolveSlatPostHeightMm(context, calc, { slug: context?.tabInfo?.[0]?.fence }, null, fenceHm, gateHm);
        return this.getSlatPostCatalogSlugFromFenceHeightMm(heightMm);
    },

    //----------------------------------------------------------------------------------

    resolveCalcFenceHeights: function(custom_fence_tab) {
        var domMaxH = this.readStep2MaxFenceHeightMm({ lenient: true });
        var maxHeight = Number.isFinite(domMaxH) && domMaxH > 0 ? domMaxH : '';

        var tab0 = custom_fence_tab?.[0];
        if (!maxHeight && tab0 && typeof fcReadTabRowStep2Field === 'function') {
            var slugNorm = tab0.fence || tab0.style || '';
            var stored = fcReadTabRowStep2Field(tab0, slugNorm, 'max_fence_height');
            if (stored) {
                maxHeight = stored;
            }
        }

        if (!maxHeight) {
            var filteredMaxHeight = tab0?.fields?.filter(function(item) {
            return item.name == 'max_fence_height';
        });
        var savedMaxHeight = filteredMaxHeight?.[0]?.value || '';
            maxHeight = savedMaxHeight || '';
        }

        var fenceHeightKey = this.getHeightKeyFromMax(maxHeight);
        var panelWidthHeightKey = fenceHeightKey === 1000 ? 1200 : fenceHeightKey;

        return {
            maxFenceHeight: maxHeight,
            fenceHeight: fenceHeightKey,
            panelWidthHeightKey: panelWidthHeightKey
        };
    },

    //----------------------------------------------------------------------------------

    getCustomGateLimits: function(args = {}) {
        var slug = args.slug;
        var panelOptionsData = args.panelOptionsData || {};
        var fenceHeight = args.fenceHeight;
        var maxFenceHeight = args.maxFenceHeight;
        var tabInfo = args.tabInfo || [];
        var postWidth = parseInt(args.postWidth, 10);

        var panelOpts = panelOptionsData?.size?.width_based_height;
        var fallbackWidth = panelOptionsData?.size?.width ?? panelOptionsData?.size?.default ?? 0;
        var maxWidth = 0;

        if (panelOpts && fenceHeight && panelOpts[fenceHeight] !== undefined) {
            maxWidth = parseInt(panelOpts[fenceHeight], 10) - postWidth;
        } else {
            maxWidth = parseInt(fallbackWidth, 10) - postWidth;
        }

        if (!Number.isFinite(maxWidth) || maxWidth <= 0) {
            maxWidth = 0;
        }

        if (this.isSlatLike(slug)) {
            var maxHEl = this.isMainSlatSlug(slug)
                ? this.getGateMaxFenceHeightEl() || this.getStep2MaxFenceHeightEl()
                : this.getStep2MaxFenceHeightEl();
            var maxInvalid = maxHEl && !this.validateMaxFenceHeightField(maxHEl).valid;
            if (maxInvalid) {
                var gateFields = tabInfo?.[0]?.fields || [];
                var gateRow = (args.fenceInfo || []).find(function(item) {
                    return item?.control_key === 'gate';
                });
                var savedGateH = gateRow?.settings?.fields?.find(function(item) {
                    return item?.key === 'gate_max_fence_height';
                })?.val;
                var prev =
                    savedGateH ||
                    gateFields.find(function(item) {
                        return item?.name === 'gate_max_fence_height';
                    })?.value ||
                    gateFields.find(function(item) {
                    return item?.name === 'max_fence_height';
                })?.value;
                maxFenceHeight = prev || '';
            } else if (maxHEl && maxHEl.value) {
                maxFenceHeight = maxHEl.value;
            }
        }

        if ((!fenceHeight || fenceHeight === 'undefined') && maxFenceHeight && panelOpts) {
            var h = parseInt(maxFenceHeight, 10);
            var key = 1200;
            if (Number.isFinite(h)) {
                key = this.isSlatLike(slug) ? (h <= 1500 ? 1200 : 1800) : (h <= 1100 ? 1000 : (h <= 1500 ? 1200 : 1800));
            }

            if (panelOpts[key] !== undefined) {
                maxWidth = parseInt(panelOpts[key], 10) - postWidth;
            }
        }

        if (this.isSlatLike(slug)) {
            maxWidth = 2100;
        }

        maxWidth = Number.isFinite(maxWidth) && maxWidth > 0 ? maxWidth : 0;
        return {
            maxWidth: maxWidth,
            maxLength: String(maxWidth).length
        };
    },

    //----------------------------------------------------------------------------------

    validateMaxFenceHeightField: function(el) {
        var isGateHeight = el && el.name === 'gate_max_fence_height';
        var isFenceHeight = el && el.name === 'max_fence_height';
        if (!el || (!isFenceHeight && !isGateHeight)) {
            return { valid: true, message: '' };
        }

        if (el.disabled) {
            return { valid: false, message: '' };
        }

        if (el.tagName === 'SELECT') {
            var v = (el.value || '').toString().trim();
            if (!v) {
                return {
                    valid: false,
                    message: isGateHeight ? 'Please select gate height' : 'Please select a fence height'
                };
            }
            return { valid: true, message: '' };
        }

        var raw = (el.value || '').toString().trim();
        if (!raw) {
            return {
                valid: false,
                message: isGateHeight ? 'Please enter gate height' : 'Please enter fence height'
            };
        }

        var val = parseInt(raw, 10);
        if (!Number.isFinite(val) || val <= 0) {
            return { valid: false, message: 'Invalid value' };
        }

        var min = parseInt(el.getAttribute('data-min') || '', 10);
        var max = parseInt(el.getAttribute('data-max') || '', 10);
        if (Number.isFinite(min) && val < min) {
            return {
                valid: false,
                message: ' Invalid ' + (typeof HELPER !== 'undefined' ? HELPER.number_format(min) : min) + 'mm min'
            };
        }
        if (Number.isFinite(max) && val > max) {
            return {
                valid: false,
                message: ' Invalid ' + (typeof HELPER !== 'undefined' ? HELPER.number_format(max) : max) + 'mm max'
            };
        }

        return { valid: true, message: '' };
    },

    //----------------------------------------------------------------------------------

    getMeasurementValidation: function(measurementEl) {
        if (!measurementEl) return false;

        var raw = (measurementEl.value || '').toString().trim();
        var min = parseInt(measurementEl.getAttribute('data-min') || '', 10);
        var max = parseInt(measurementEl.getAttribute('data-max') || '', 10);
        var val = parseInt(raw || '', 10);

        if (!raw || !Number.isFinite(val)) return false;
        if (Number.isFinite(min) && val < min) return false;
        if (Number.isFinite(max) && val > max) return false;
        return true;
    },

    //----------------------------------------------------------------------------------

    canAutoCalculateFromStep2: function(args = {}) {
        var slug = args.slug;
        var measurementEl = args.measurementEl;
        var panelCountEl = args.panelCountEl;
        var maxHeightEl = args.maxHeightEl;
        var validatePanelCountFn = args.validatePanelCountFn;

        if (!this.getMeasurementValidation(measurementEl)) return false;

        if (this.isSlatLike(slug) && maxHeightEl) {
            var maxHeightResult = this.validateMaxFenceHeightField(maxHeightEl);
            if (!maxHeightResult.valid) return false;
        }

        var slatGapEl = document.querySelector('[data-section="2"] [name="slat_gap"]');
        if (this.isSlatLike(slug) && slatGapEl) {
            if (!this.validateSlatGapField(slatGapEl).valid) return false;
        }

        var slatSizeEl = document.querySelector('[data-section="2"] [name="slat_size"]');
        if (this.isSlatLike(slug) && slatSizeEl) {
            if (!this.validateSlatSizeField(slatSizeEl).valid) return false;
        }

        if (typeof SlatFenceInfill !== 'undefined' && SlatFenceInfill.isPanelCountRequired(slug)) {
            var panelOk = SlatFenceInfill.getStep2AutoCalculateGuard({
                slug: slug,
                panelCountEl: panelCountEl,
                validatePanelCountFn: validatePanelCountFn
            });
            if (!panelOk) return false;
        }

        return true;
    },

    //----------------------------------------------------------------------------------

    syncCalculateButtonState: function(args = {}) {
        var canCalculate = !!args.canCalculate;
        if (canCalculate) {
            $('.btn-fc-calculate')
                .removeAttr('disabled')
                .removeClass('btn-light disabled')
                .addClass('btn-dark');
        } else {
            $('.btn-fc-calculate')
                .attr('disabled', 'disabled')
                .removeClass('btn-dark')
                .addClass('btn-light disabled');
        }

        return canCalculate;
    },

    //----------------------------------------------------------------------------------

    normalizeStep2FieldsBeforeSave: function(args = {}) {
        var slug = args.slug;
        var prevFields = args.prevFields || [];
        var nextFields = args.nextFields || [];
        var maxHeightEl = args.maxHeightEl;

        if (!this.isSlatLike(slug)) {
            return nextFields;
        }

        var maxBad = maxHeightEl && !this.validateMaxFenceHeightField(maxHeightEl).valid;
        if (maxBad) {
            // Do not default to 1200 or carry Barr `fence_height` — drop invalid / empty height.
            nextFields = nextFields.filter(function(item) {
                return !item || item.name !== 'max_fence_height';
            });
        }

        return nextFields;
    },

    //----------------------------------------------------------------------------------

    getCalcWidthDimensionOffset: function(slug, custom_fence, custom_fence_tab) {
        if (!this.isMainSlatSlug(slug)) return 0;

        var wdfRaw = '';
        var activeKey = $('.fencing-container').attr('data-key');
        if (activeKey === 'panel_options') {
            wdfRaw = $('.fc-form-field[name="width_dimension_from"]:visible').attr('value') || wdfRaw;
        }

        if (!wdfRaw) {
            var panelOpts = custom_fence.find(function(item) {
                return item.control_key === 'panel_options';
            });
            var stored = panelOpts?.settings?.find(function(item) {
                return item?.key === 'width_dimension_from';
            })?.val;
            wdfRaw = (stored !== undefined && stored !== null) ? String(stored) : wdfRaw;
        }

        if (!wdfRaw) {
            var legacy = $('[name="width_dimension_from"]:checked').val();
            if (legacy === undefined || legacy === null || legacy === '') {
                legacy = $('[name="width_dimension_from"]').val();
            }
            wdfRaw = legacy || wdfRaw;
        }

        var widthDimensionFrom = parseInt(wdfRaw, 10);
        var filteredWdf = custom_fence_tab?.[0]?.fields?.filter(function(item) {
            return item.name == 'width_dimension_from';
        });
        var savedWdf = filteredWdf?.[0]?.value || '';
        widthDimensionFrom = Number.isFinite(widthDimensionFrom) ? widthDimensionFrom : parseInt(savedWdf, 10);

        if (!Number.isFinite(widthDimensionFrom)) {
            widthDimensionFrom = -1;
        }

        var postW = parseInt(FENCE.get(slug, 'post'), 10);
        var offsetMm = widthDimensionFrom;
        if (Number.isFinite(postW) && Math.abs(widthDimensionFrom) <= 5) {
            offsetMm = widthDimensionFrom * postW;
        }

        return offsetMm;
    },

    //----------------------------------------------------------------------------------

    applyCalcOverallOffset: function(slug, overallWidth, custom_fence, custom_fence_tab) {
        if (!this.isMainSlatSlug(slug)) return parseInt(overallWidth, 10);
        var offset = this.getCalcWidthDimensionOffset(slug, custom_fence, custom_fence_tab);
        return parseInt(overallWidth, 10) + parseInt(offset, 10);
    },

    //----------------------------------------------------------------------------------

    adjustCalcGateSize: function(slug, gateSize, gate_data, post_panel) {
        if (!this.isMainSlatSlug(slug)) return gateSize;

        var gateWdfRaw = gate_data?.[0]?.settings?.fields?.find(function(item) {
            return item?.key === 'width_dimension_from';
        })?.val;
        var gateWdf = parseInt(gateWdfRaw, 10);

        if (Number.isFinite(gateSize) && Number.isFinite(gateWdf) && (gateWdf === -1 || gateWdf === -2)) {
            return Math.max(0, gateSize + (gateWdf * post_panel));
        }

        return gateSize;
    },

    isSlatDoubleGate: function(gate_data) {
        return (
            gate_data?.[0]?.settings?.fields?.find(function(item) {
                return item?.key === 'gate_type';
            })?.val === 'double'
        );
    },

    /**
     * One gate leaf width from Gate modal (`settings.size` / `gate_width`), not calc layout width.
     */
    getGateLeafWidthMm: function(slug, gate_data, calc) {
        var g = gate_data?.[0]?.settings;
        if (g && g.size != null && String(g.size) !== '') {
            var n = parseInt(String(g.size).replace(/,/g, ''), 10);
            if (Number.isFinite(n) && n > 0) {
                return n;
            }
        }
        var gw = g?.fields?.find(function(item) {
            return item.key === 'gate_width';
        })?.val;
        if (gw != null && String(gw) !== '') {
            var n2 = parseInt(String(gw).replace(/,/g, ''), 10);
            if (Number.isFinite(n2) && n2 > 0) {
                return n2;
            }
        }
        var fb = parseInt(calc?.gate?.width, 10);
        if (!Number.isFinite(fb) || fb <= 0) {
            return 0;
        }
        if (this.isMainSlatSlug(slug) && this.isSlatDoubleGate(gate_data)) {
            return Math.round(fb / 2);
        }
        return fb;
    },

    /**
     * Total gate opening width: single = leaf width; double = 2× leaf (e.g. 2000 + 2000 = 4000 mm).
     */
    getGateOpeningWidthMm: function(slug, gate_data, calc) {
        var leaf = this.getGateLeafWidthMm(slug, gate_data, calc);
        if (!Number.isFinite(leaf) || leaf <= 0) {
            return 0;
        }
        if (this.isMainSlatSlug(slug) && this.isSlatDoubleGate(gate_data)) {
            return leaf * 2;
        }
        return leaf;
    },

    /**
     * Width shown on gate labels and used for min overall-length checks (total opening).
     */
    getGateDisplayWidthMm: function(slug, gate_data, calc) {
        return this.getGateOpeningWidthMm(slug, gate_data, calc);
    },

    /**
     * Gate span used in calc.js (after width-dimension-from); doubles for Slat double gates.
     */
    getCalcGateSpanMm: function(slug, leafWidthMm, gate_data, post_panel) {
        var span = this.adjustCalcGateSize(slug, leafWidthMm, gate_data, post_panel);
        if (this.isMainSlatSlug(slug) && this.isSlatDoubleGate(gate_data)) {
            span = span * 2;
        }
        return span;
    },

    /** calc.js C8 — gate opening span plus hinge/latch gaps (not end posts). */
    getSlatGateCalcC8Mm: function(slug, gate_data, post_panel) {
        if (!this.isMainSlatSlug(slug) || !gate_data || !gate_data.length) {
            return 0;
        }
        var gateSize = parseInt(String(gate_data[0]?.settings?.size || '').replace(/,/g, ''), 10);
        if (!Number.isFinite(gateSize) || gateSize <= 0) {
            return 0;
        }
        var gatePostGaps = parseInt(FENCE.get(slug, 'gate_post_gaps'), 10) || 0;
        return this.getCalcGateSpanMm(slug, gateSize, gate_data, post_panel) + gatePostGaps;
    },

    /** Remaining infill span (mm) after gate + raked — uses effective overall (display + width-dimension offset). */
    getSlatInfillSpanMm: function(slug, rawDisplayMm, gateSpanC8, rakedLeft, rakedRight, custom_fence, custom_fence_tab) {
        if (!this.isMainSlatSlug(slug)) {
            return parseInt(rawDisplayMm, 10);
        }
        var raw = parseInt(String(rawDisplayMm != null ? rawDisplayMm : '').replace(/,/g, ''), 10);
        if (!Number.isFinite(raw)) {
            return NaN;
        }
        var offset = this.getCalcWidthDimensionOffset(slug, custom_fence, custom_fence_tab);
        var c8 = parseInt(gateSpanC8, 10) || 0;
        var c9 = parseInt(rakedLeft, 10) || 0;
        var c10 = parseInt(rakedRight, 10) || 0;
        return raw + offset - c8 - c9 - c10;
    },

    getSlatGateMinOverallDisplayMm: function(fd, gate_data, calc) {
        if (!fd || !this.isMainSlatSlug(fd.slug) || !gate_data || !gate_data.length) {
            return null;
        }
        if (this.isSlatStdGate(gate_data)) {
            return this.computeSlatGateOnlyStdOverallEffectiveMm(fd);
        }
        var leaf = this.getGateLeafWidthMm(fd.slug, gate_data, calc);
        if (!Number.isFinite(leaf) || leaf <= 0) {
            return null;
        }
        return this.computeDisplayOverallFromGateLeafMm(fd, leaf, {
            isDouble: this.isSlatDoubleGate(gate_data)
        });
    },

    /**
     * True when Step 2 overall is within the gate footprint (single/double, with/without end posts) —
     * no infill panels or gate off-cut tiles should be added.
     */
    isWithinGateOnlyOverallRange: function(
        slug,
        gate_data,
        custom_fence,
        custom_fence_tab,
        gateSpanC8,
        rakedLeft,
        rakedRight,
        calc
    ) {
        if (!this.isMainSlatSlug(slug) || !gate_data || !gate_data.length) {
            return false;
        }

        var rawSlatOv = this.readSlatRawOverallMm(custom_fence_tab, slug);
        if (!Number.isFinite(rawSlatOv)) {
            return false;
        }

        var infillSpan = this.getSlatInfillSpanMm(
            slug,
            rawSlatOv,
            gateSpanC8,
            rakedLeft,
            rakedRight,
            custom_fence,
            custom_fence_tab
        );
        var minPanelOnGate = parseInt(FENCE.get(slug, 'minPanelWidthOnGate'), 10) || 86;
        if (!Number.isFinite(infillSpan) || infillSpan < minPanelOnGate) {
            return true;
        }

        var fd = {
            slug: slug,
            info: custom_fence,
            tabInfo: custom_fence_tab,
            data: typeof fc_data !== 'undefined' ? fc_data[slug] : null
        };
        var minDisp = this.getSlatGateMinOverallDisplayMm(fd, gate_data, calc);
        return Number.isFinite(minDisp) && rawSlatOv <= minDisp;
    },

    //----------------------------------------------------------------------------------

    applyGapPattern: function(custom_fence, info, container, calc, extraCtx) {
        if (!this.isSlatLike(info?.slug)) return;

        extraCtx = extraCtx || {};

        var mm = this.getGapMm(custom_fence, info);
        var gapDisp = this.getGapDisplayLabelMm(custom_fence, info);
        // 0mm gap: force a 1px visual divider so individual slats stay visible in the planner.
        // Cart / BOM math reads the real "0" slug separately, so quantities are unaffected.
        var gapPx = mm <= 0 ? 1 : this.mmToPx(mm);
        var centerPoint = FENCE.get(info?.slug, 'post') ?? 25;

        var postOpt = custom_fence?.find?.(function(item) {
            return item?.control_key === 'post_options';
        })?.settings?.find?.(function(item) {
            return item?.key === 'post_option';
        })?.val;

        if (!postOpt) {
            postOpt = info?.settings?.post_options?.fields?.[0]?.options?.find?.(function(item) {
                return item?.default;
            })?.slug;
        }

        var slatNoBuryPost = (postOpt === 'opt-3' || postOpt === 'opt-5');
        var slatReducePostHeightPx = (postOpt === 'opt-1') ? 20 : ((postOpt === 'opt-2' || postOpt === 'opt-4') ? 10 : 0);
        var ctx = { fenceInfo: custom_fence };
        if (extraCtx.tabInfo) {
            ctx.tabInfo = extraCtx.tabInfo;
        }
        var fenceHeightMm = this.resolveSlatFenceHeightMm(ctx, calc);
        var gateHeightMm = this.isMainSlatSlug(info?.slug)
            ? this.resolveGateSlatHeightMm(ctx, calc)
            : fenceHeightMm;
        if (!Number.isFinite(fenceHeightMm) || fenceHeightMm <= 0) {
            fenceHeightMm = null;
        }
        if (!Number.isFinite(gateHeightMm) || gateHeightMm <= 0) {
            gateHeightMm = fenceHeightMm;
        }
        var slatSizeMm = this.getSizeMm(custom_fence, 65, info?.slug);
        var slatPx = Math.max(2, this.mmToPx(slatSizeMm));
        var panelPitch = this.resolveSlatPanelPitchInputs(ctx, info?.slug);
        var fencePanelHeightMm =
            fenceHeightMm !== null
                ? this.computeSlatPanelHeightMmFromMaxHeight(
                      fenceHeightMm,
                      panelPitch.sizePitch,
                      panelPitch.gapPitch
                  )
                : fenceHeightMm;
        var gatePanelHeightMm =
            gateHeightMm !== null
                ? this.computeSlatPanelHeightMmFromMaxHeight(
                      gateHeightMm,
                      panelPitch.sizePitch,
                      panelPitch.gapPitch
                  )
                : fencePanelHeightMm;
        var postBuryMm = slatNoBuryPost ? 0 : 300;
        var postBuryPx = this.mmToPx(postBuryMm);

        var self = this;

        var applyGapSizeVars = function(targetEl) {
            if (!targetEl || !targetEl.style) {
                return;
            }
            targetEl.style.setProperty('--fc-slat-gap-px', gapPx + 'px');
            targetEl.style.setProperty('--fc-slat-size-px', slatPx + 'px');
        };

        var clearHeightVars = function(targetEl) {
            if (!targetEl || !targetEl.style) {
                return;
            }
            targetEl.style.removeProperty('--fc-slat-post-bury-px');
            targetEl.style.removeProperty('--fc-slat-post-height-px');
            targetEl.style.removeProperty('--fc-slat-post-above-px');
        };

        var applyHeightVars = function(targetEl, maxHeightMm) {
            if (!targetEl || !targetEl.style) {
                return null;
            }

            var heightPx =
                maxHeightMm !== null && Number.isFinite(maxHeightMm) && maxHeightMm > 0
                    ? self.mmToPx(maxHeightMm)
                    : null;

            if (heightPx === null) {
                clearHeightVars(targetEl);
                return null;
            }

            targetEl.style.setProperty('--fc-slat-post-bury-px', postBuryPx + 'px');
            var totalPostPx = Math.max(0, (heightPx + postBuryPx) - slatReducePostHeightPx);
            targetEl.style.setProperty('--fc-slat-post-height-px', totalPostPx + 'px');
            targetEl.style.setProperty('--fc-slat-post-above-px', heightPx + 'px');
            return heightPx;
        };

        var syncDisplayWrapper = function(rootEl, fenceMm, gateMm) {
            var panelItemsEl = self.resolveSlatScaleWrapper(rootEl, null);
            var displayEl =
                rootEl?.closest?.('.fencing-display-result') ||
                rootEl?.closest?.('.fc-project-plan-hscroll') ||
                (rootEl?.querySelector ? rootEl.querySelector('.fencing-display-result') : null);

            var fenceDisplayMm =
                fenceMm !== null && Number.isFinite(fenceMm) ? fenceMm : 0;
            var gateDisplayMm = gateMm !== null && Number.isFinite(gateMm) ? gateMm : 0;
            var displayMm = Math.max(fenceDisplayMm, gateDisplayMm);
            var gateTallerThanFence = gateDisplayMm > fenceDisplayMm;

            var clearProjectPlanSlatScrollHeight = function(rootEl) {
                if (!rootEl || !rootEl.closest) {
                    return;
                }
                var hscrollEl = rootEl.closest('.fc-project-plan-hscroll');
                if (hscrollEl) {
                    hscrollEl.classList.remove('fc-slat-scale');
                    hscrollEl.style.removeProperty('min-height');
                    hscrollEl.style.removeProperty('--fc-slat-hscroll-min-height-px');
                }
            };

            if (!panelItemsEl?.style || displayMm <= 0) {
                if (panelItemsEl?.style) {
                    panelItemsEl.classList.remove('fc-slat-scale');
                    panelItemsEl.classList.remove('fc-slat-align-top');
                    panelItemsEl.classList.remove('fc-slat-align-bottom');
                    panelItemsEl.style.removeProperty('--fc-slat-display-height-px');
                    panelItemsEl.style.removeProperty('--fc-slat-display-pad-top-px');
                    panelItemsEl.style.removeProperty('--fc-slat-display-pad-bottom-px');
                }
                if (displayEl?.classList) {
                    displayEl.classList.remove('fc-slat-scale');
                }
                clearProjectPlanSlatScrollHeight(rootEl);
                return;
            }

            var isProjectPlanPage = !!rootEl?.closest?.('.fc-project-plan-page');
            var isPlannerStep3 = !!rootEl?.closest?.('.fc-planner-page .fencing-display-result');
            var fenceBodyPx = self.mmToPx(displayMm) + postBuryPx;
            var postFootMarginPx = 30;
            var centersPadPx = 88;
            var plannerCentersPadPx = 40;
            // Planner Step 3 `.fencing-panel-container` uses padding-top: 15px inside the scroll strip.
            var plannerContainerPadTopPx = 15;
            var displayHeightPx;
            var padBottomPx;
            var extraTopPx = 0;

            if (isProjectPlanPage) {
                displayHeightPx = fenceBodyPx + postFootMarginPx + centersPadPx;
                padBottomPx = centersPadPx;
            } else if (isPlannerStep3) {
                // Match non-slat Step 3: label room on `.fencing-panel-container` (CSS), not panel-items padding.
                var postHeightPx = Math.max(
                    0,
                    self.mmToPx(displayMm) + postBuryPx - slatReducePostHeightPx
                );
                displayHeightPx =
                    postHeightPx +
                    postFootMarginPx +
                    plannerContainerPadTopPx +
                    plannerCentersPadPx;
                padBottomPx = 0;
            } else {
                displayHeightPx = fenceBodyPx + postFootMarginPx + (slatNoBuryPost ? 40 : 120);
                padBottomPx = postBuryPx;
            }

            panelItemsEl.classList.add('fc-slat-scale');
            panelItemsEl.classList.toggle('fc-slat-align-top', gateTallerThanFence);
            panelItemsEl.classList.toggle('fc-slat-align-bottom', !gateTallerThanFence);
            if (isPlannerStep3) {
                panelItemsEl.style.removeProperty('--fc-slat-display-height-px');
            } else {
                panelItemsEl.style.setProperty('--fc-slat-display-height-px', displayHeightPx + 'px');
            }
            panelItemsEl.style.setProperty('--fc-slat-display-pad-top-px', extraTopPx + 'px');
            panelItemsEl.style.setProperty('--fc-slat-display-pad-bottom-px', padBottomPx + 'px');
            if (displayEl?.classList) {
                displayEl.classList.add('fc-slat-scale');
            }

            // hscroll overflow-x:auto clips vertically — min-height matches scaled run (planner Step 3 + project plan).
            var hscrollEl = rootEl?.closest?.('.fc-project-plan-hscroll');
            if ((isProjectPlanPage || isPlannerStep3) && hscrollEl?.style) {
                hscrollEl.classList.add('fc-slat-scale');
                hscrollEl.style.setProperty('--fc-slat-hscroll-min-height-px', displayHeightPx + 'px');
                hscrollEl.style.minHeight = displayHeightPx + 'px';
            }
        };

        var applyLabelToEl = function(el) {
            if (!el || !el.querySelectorAll) return;

            el.querySelectorAll('.fencing-panel-item').forEach(function(panelEl) {
                var label = panelEl.querySelector(':scope > .fc-slat-gap-label');
                if (!label) {
                    label = document.createElement('div');
                    label.className = 'fc-slat-gap-label';
                    panelEl.appendChild(label);
                }

                var labelHeightMm = fenceHeightMm;
                if (
                    gateHeightMm !== null &&
                    panelEl.closest &&
                    panelEl.closest('.fencing-panel-gate')
                ) {
                    labelHeightMm = gateHeightMm;
                }

                var rows =
                    labelHeightMm !== null &&
                    Number.isFinite(labelHeightMm) &&
                    Number.isFinite(panelPitch.sizePitch) &&
                    Number.isFinite(panelPitch.gapPitch)
                        ? SlatFence.countSlatPanelRowsFromMaxHeightMm(
                              labelHeightMm,
                              panelPitch.sizePitch,
                              panelPitch.gapPitch
                          )
                        : null;

                label.innerHTML = rows !== null
                    ? 'Gap: ' + gapDisp + 'mm<br>Size: ' + slatSizeMm + 'mm<br>Rows: ' + rows
                    : 'Gap: ' + gapDisp + 'mm<br>Size: ' + slatSizeMm + 'mm';
            });

            var hidePostValue = SlatFence.shouldHidePostValue(info);
            if (hidePostValue) {
                el.classList.add('fc-hide-post-value');
            } else {
                el.classList.remove('fc-hide-post-value');
            }
            el.querySelectorAll('.fencing-panel-spacing-number > span:first-child').forEach(function(span) {
                var spacingEl = span.closest('.fencing-panel-spacing-number');
                if (hidePostValue) {
                    span.textContent = '';
                } else if (spacingEl && spacingEl.classList.contains('no-post')) {
                    span.textContent = '(0)';
                } else {
                span.textContent = String(centerPoint);
                }
            });
            if (hidePostValue) {
                el.querySelectorAll('.fencing-panel-item').forEach(function(panelEl) {
                    var $fcp = $(panelEl).find('.fc-first-c-p');
                    if ($fcp.length && typeof ProjectPlan !== 'undefined' && ProjectPlan.fixCentersWidthWithoutEndPost) {
                        ProjectPlan.fixCentersWidthWithoutEndPost($(panelEl));
                    }
                });
            } else if (self.isSlatLike(info)) {
                SlatFence.syncSlatNoPostSpacingLabels(el);
                SlatFence.syncSlatNoPostEndCenterMarkers(el);
            }
        };

        var applyFenceAndGateHeights = function(el) {
            if (!el) {
                return;
            }

            applyGapSizeVars(el);

            var gateOnlyPlanner = self.isSlatGateOnlyPlanner(ctx, calc, info, el);
            var defaultPostHeightMm = self.resolveSlatPostHeightMm(
                ctx,
                calc,
                info,
                el,
                fenceHeightMm,
                gateHeightMm
            );
            var gateTallerThanFence =
                !gateOnlyPlanner &&
                self.isMainSlatSlug(info?.slug) &&
                gateHeightMm !== null &&
                fenceHeightMm !== null &&
                Number.isFinite(gateHeightMm) &&
                Number.isFinite(fenceHeightMm) &&
                gateHeightMm > fenceHeightMm;
            var syncFenceMm = gateOnlyPlanner ? defaultPostHeightMm : fenceHeightMm;
            var syncGateMm = gateHeightMm;

            if (el.querySelectorAll) {
                el.querySelectorAll('.fencing-panel-item:not(.fencing-panel-gate)').forEach(function(panelEl) {
                    applyHeightVars(panelEl, fencePanelHeightMm);
                });
                el.querySelectorAll('.panel-post').forEach(function(postEl) {
                    var postMm = defaultPostHeightMm;
                    if (gateTallerThanFence) {
                        postMm = self.isSlatGateAdjacentPost(postEl, el)
                            ? gatePanelHeightMm
                            : fencePanelHeightMm;
                    }
                    applyHeightVars(postEl, postMm);
                });
                el.querySelectorAll('.fencing-panel-item.fencing-panel-gate').forEach(function(gateEl) {
                    applyHeightVars(gateEl, gatePanelHeightMm);
                });
            }

            syncDisplayWrapper(el, syncFenceMm, syncGateMm);
        };

        if (!container) {
            document.querySelectorAll('.fencing-panel-container[data-type="slat"], .fencing-panel-container[data-type="slat_fence_infill"]')
                .forEach(function(el) {
                    applyFenceAndGateHeights(el);
                    applyLabelToEl(el);
                });
            return;
        }

        if (typeof container === 'string') {
            document.querySelectorAll(container).forEach(function(el) {
                applyFenceAndGateHeights(el);
                applyLabelToEl(el);
            });
            return;
        }

        applyFenceAndGateHeights(container);
        applyLabelToEl(container);
    },

    //----------------------------------------------------------------------------------

    resolveDisplayFenceHeightMm: function(slug, calc, fd) {
        if (!this.isSlatLike(slug)) {
            return parseInt(calc?.fence_size?.height, 10);
        }
        var h = this.resolveSlatFenceHeightMm(fd || {}, calc);
        if (Number.isFinite(h) && h > 0) {
            return h;
        }
        return parseInt(calc?.fence_size?.height, 10);
    },

    /**
     * Step 3 / project plan inline panel heights: fence panels use calculated panel height;
     * gate panels use calculated gate panel height on main Slat only.
     */
    applySlatPanelInlineHeights: function(slug, calc, context, opts) {
        opts = opts || {};
        if (!this.isSlatLike(slug) || !calc) {
            return;
        }

        var baseMargin = FENCE.get('item', 'base_margin') || 0.1;
        context = context || {};

        var maxFenceMm = this.resolveSlatFenceHeightMm(context, calc);
        if (!Number.isFinite(maxFenceMm) || maxFenceMm <= 0) {
            maxFenceMm = parseInt(String(calc?.fence_size?.height || '').replace(/,/g, ''), 10);
        }
        if (!Number.isFinite(maxFenceMm) || maxFenceMm <= 0) {
            return;
        }

        var fencePanelMm = this.resolveSlatPanelHeightMm(context, calc);
        if (!Number.isFinite(fencePanelMm) || fencePanelMm <= 0) {
            fencePanelMm = maxFenceMm;
        }

        var fencePx =
            opts.fenceHeightPx != null && Number.isFinite(opts.fenceHeightPx)
                ? opts.fenceHeightPx
                : fencePanelMm * baseMargin;

        var $scope = opts.$root ? $(opts.$root) : $(document);
        var $fencePanels = $scope.find(
            '.fencing-panel-item:not(.fencing-panel-gate), .short-panel-item'
        );
        var $fenceOffcuts = $scope.find('.fencing-offcut:not(.gate-offcut) .offcut-body');

        $fencePanels.css({ height: fencePx });
        $fenceOffcuts.css({ height: fencePx });

        var gateMaxMm = this.isMainSlatSlug(slug) ? this.resolveGateSlatHeightMm(context, calc) : maxFenceMm;
        if (!Number.isFinite(gateMaxMm) || gateMaxMm <= 0) {
            gateMaxMm = maxFenceMm;
        }

        var gatePanelMm = this.resolveSlatPanelHeightMm(context, calc, { forGate: true });
        if (!Number.isFinite(gatePanelMm) || gatePanelMm <= 0) {
            gatePanelMm = gateMaxMm;
        }

        var gatePx =
            opts.gateHeightPx != null && Number.isFinite(opts.gateHeightPx)
                ? opts.gateHeightPx
                : gatePanelMm * baseMargin;

        var gateOnlyPlanner = this.isSlatGateOnlyPlanner(
            context,
            calc,
            { slug: slug },
            $scope.find('.fencing-panel-container[data-type="slat"]').get(0) || null
        );
        var gateTallerThanFence = gateMaxMm > maxFenceMm;
        var $panelItems = this.$slatScaleWrappersInScope($scope);
        if ($panelItems.length) {
            $panelItems.toggleClass('fc-slat-align-top', gateTallerThanFence);
            $panelItems.toggleClass('fc-slat-align-bottom', !gateTallerThanFence);
        }
        var gateMarginTop =
            gateOnlyPlanner || gateTallerThanFence ? 0 : Math.max(0, Math.round(fencePx - gatePx));
        $scope.find('.fencing-panel-item.fencing-panel-gate').css({
            height: gatePx,
            marginTop: gateMarginTop > 0 ? gateMarginTop + 'px' : ''
        });
        if (gateTallerThanFence) {
            $scope.find('.fencing-panel-item:not(.fencing-panel-gate), .short-panel-item').css({
                marginTop: ''
            });
        }
        $scope.find('.fencing-offcut.gate-offcut .offcut-body').css({ height: gatePx });
    },

    //----------------------------------------------------------------------------------

    applyLegacyPostHeightAdjustments: function(slug, fenceHeightPx) {
        if (this.isSlatLike(slug)) return;

        $('.panel-post.opt-1').css({ 'height': fenceHeightPx + 25 });
        $('.panel-post.opt-2').css({ 'height': fenceHeightPx + 35 });
    },

    //----------------------------------------------------------------------------------

    resetSlatDisplayScaling: function(scope) {
        var $scope = scope && scope.length ? $(scope) : null;
        var clearSlatScrollStrip = function($el) {
            if (!$el || !$el.length) {
                return;
            }
            $el.removeClass('fc-slat-scale').each(function() {
                this.style?.removeProperty?.('min-height');
                this.style?.removeProperty?.('--fc-slat-hscroll-min-height-px');
            });
        };
        var clearSlatWrappers = function($el) {
            if (!$el || !$el.length) {
                return;
            }
            $el
                .removeClass('fc-slat-scale fc-slat-align-top fc-slat-align-bottom')
            .each(function() {
                this.style?.removeProperty?.('--fc-slat-display-height-px');
                this.style?.removeProperty?.('--fc-slat-display-pad-top-px');
                this.style?.removeProperty?.('--fc-slat-display-pad-bottom-px');
            });
        };

        // Planner Step 3 reuses one diagram strip across section tabs — always clear slat sizing.
        clearSlatScrollStrip($('.fc-planner-page .fencing-display-result .fc-project-plan-hscroll'));
        $('.fc-planner-page .fencing-display-result').removeClass('fc-slat-scale');
        clearSlatWrappers($('.fc-planner-page .fencing-display-result .fencing-panel-items'));

        var $display = $scope
            ? $scope
                  .closest('.fencing-display-result')
                  .add($scope.closest('.fc-project-plan-hscroll'))
                  .add($scope.filter('.fencing-display-result'))
            : $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingDisplayResult : '.fencing-display-result');
        $display.removeClass('fc-slat-scale');
        clearSlatScrollStrip($scope ? $scope.closest('.fc-project-plan-hscroll') : $());

        var $items = $scope ? this.$slatScaleWrappersInScope($scope) : $('.fencing-panel-items');
        clearSlatWrappers($items);
    },

    //----------------------------------------------------------------------------------

    applyGapPatternIfNeeded: function(slug, custom_fence, info, calc, extraCtx) {
        if (!this.isSlatLike(slug)) return;
        this.applyGapPattern(custom_fence, info, FENCES.el.fencingPanelContainer, calc, extraCtx);
    },

    /**
     * Planner / project plan: slat panel + post heights from Fence Height (gap pattern + inline panels).
     */
    applySlatFenceDisplayHeights: function(slug, calc, context, $scope) {
        if (!this.isSlatLike(slug) || !calc) {
            return;
        }
        context = context || {};
        var $root = $scope && $scope.length ? $($scope) : $(FENCES.el.fencingPanelContainer);
        if (!$root.length) {
            $root = $('.fencing-panel-container').first();
        }

        this.resetSlatDisplayScaling($root);

        var $container = $root.filter('.fencing-panel-container');
        if (!$container.length) {
            $container = $root.find('.fencing-panel-container').first();
        }
        if (!$container.length) {
            $container = $root.closest('.fencing-panel-container');
        }
        if (!$container.length) {
            $container = $(FENCES.el.fencingPanelContainer).first();
        }

        var containerEl = $container.get(0);
        if (!containerEl) {
            containerEl =
                typeof FENCES !== 'undefined' && FENCES.el
                    ? FENCES.el.fencingPanelContainer
                    : '.fencing-panel-container';
        }

        var info =
            (typeof fc_data !== 'undefined' && fc_data[slug]) ||
            (typeof getSelectedFenceData === 'function' ? getSelectedFenceData().data : null);
        if (info && !info.slug) {
            info = Object.assign({}, info, { slug: slug });
        }

        var custom_fence = context.fenceInfo || context.info || [];

        // Clear tubular inline post heights when reusing group-b markup (Slat Infill).
        if ($container.length) {
            $container.find('.panel-post').css({ height: '', minHeight: '' });
        }

        this.applyGapPattern(custom_fence, info, containerEl, calc, context);
        this.applySlatPanelInlineHeights(slug, calc, context, { $root: $container.length ? $container : $root });
    },

    //----------------------------------------------------------------------------------

    getGatePanelSizeCenter: function(slug, gate_size, center_point, gate_data) {
        var panelSizeCenter = (gate_size + 20 + 20 + center_point) + 'W';
        if (!this.isMainSlatSlug(slug)) return panelSizeCenter;

        var gateWdf = parseInt(gate_data?.[0]?.settings?.fields?.find(function(item) {
            return item.key === 'width_dimension_from';
        })?.val, 10);

        var mult = (Number.isFinite(gateWdf) && (gateWdf === -1 || gateWdf === -2)) ? Math.abs(gateWdf) : 1;
        return (gate_size + 20 + 20 + (mult * center_point)) + 'W';
    },

    //----------------------------------------------------------------------------------

    applyGateLabel: function(slug, gate_data, calc, panel_unit, panel_name) {
        if (!this.isMainSlatSlug(slug)) return;

        var gateTypeSlug = gate_data?.[0]?.settings?.fields?.find(function(item) {
            return item.key === 'gate_type';
        })?.val || 'single';

        var gateTypeLabel = (gateTypeSlug === 'double') ? 'Double' : 'Single';
        var displayGateWidthMm = this.getGateOpeningWidthMm(slug, gate_data, calc);
        if (!Number.isFinite(displayGateWidthMm) || displayGateWidthMm <= 0) {
            displayGateWidthMm = 0;
        }

        var gateHeightMm = this.resolveGateSlatHeightMm({ fenceInfo: gate_data ? [gate_data[0]] : [] }, calc);
        var gateHeightLine =
            Number.isFinite(gateHeightMm) && gateHeightMm > 0
                ? String(Math.round(gateHeightMm)) + 'H<br>'
                : '';

        $(FENCES.el.fencingPanelGate)
            .find('.fencing-panel-item-size')
            .html(gateTypeLabel + '<br>' + gateHeightLine + displayGateWidthMm + panel_unit + '<br> ' + panel_name);

        $(FENCES.el.fencingPanelGate).find('.double-gate').remove();
        if (gateTypeSlug === 'double') {
            $(FENCES.el.fencingPanelGate).append('<div class="double-gate"></div>');
        }
    },

    //----------------------------------------------------------------------------------

    applyRefreshGateLabel: function(fd, calc) {
        var slug = fd?.slug;
        if (!this.isMainSlatSlug(slug)) return;
        if (!$(FENCES.el.fencingPanelGate).length) return;

        var panel_unit = FENCES.defaultValues.unit;
        var custom_fence = fd?.info || [];

        var gate_data = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        var gate_hinge_type = gate_data[0]?.settings?.fields?.find(function(item) {
            return item.key == 'gate_hinge_type';
        });

        var panel_name = 'GATE';
        if (gate_hinge_type) {
            panel_name = gate_hinge_type?.val == 'opt-1' ? 'STD GATE' : 'SC GATE';
        } else {
            panel_name = 'SC GATE';
        }

        this.applyGateLabel(slug, gate_data, calc, panel_unit, panel_name);

        var center_point = FENCE.get(slug, 'post');
        var physW = parseInt(calc?.gate?.width, 10);
        var panel_size_center = this.getGatePanelSizeCenter(
            slug,
            Number.isFinite(physW) ? physW : 0,
            center_point,
            gate_data
        );
        $(FENCES.el.fencingPanelGate).find('.fc-panel-size-center').text(panel_size_center);
    },

    //----------------------------------------------------------------------------------

    shouldApplyPostSpacingOverride: function(slug) {
        return !this.isSlatLike(slug);
    },

    //----------------------------------------------------------------------------------

    getContextFieldValue: function(context, name, defVal = '') {
        var fields = context?.tabInfo?.[0]?.fields || [];
        for (let i = 0; i < fields.length; i++) {
            if (fields[i]?.name === name) {
                var v = fields[i]?.value;
                if (v !== undefined && v !== null && v !== '') return v;
            }
        }
        return defVal;
    },

    //----------------------------------------------------------------------------------

    getFenceSettingValue: function(context, controlKey, key, defVal = '') {
        var arr = context?.fenceInfo || [];
        var found = arr.find(function(item) {
            return item?.control_key === controlKey;
        });
        var settings = found?.settings;
        if (!Array.isArray(settings)) return defVal;
        var s = settings.find(function(item) {
            return item?.key === key;
        });
        var v = s?.val;
        return (v !== undefined && v !== null && v !== '') ? v : defVal;
    },

    //----------------------------------------------------------------------------------

    isGateAccessorySlug: function(slug) {
        var s = String(slug || '');
        return s === 'gate+kit' || s === 'gate+hinges' || s === 'gate+latch';
    },

    hasConfiguredGate: function(array, gate_data) {
        if (Array.isArray(gate_data) && gate_data.length) {
            return true;
        }
        return (array || []).some(function(item) {
            var s = String(item?.slug || '');
            if (this.isGateAccessorySlug(s)) {
                return false;
            }
            return /^(gate\+(?:converter\+)?(1000|1200|1800)|gate(\+1)?)$/.test(s);
        }, this);
    },

    /**
     * Barr / flat_top / slat: gate+kit with every gate; normalize gate+1 → gate+{height} on project-plan.
     */
    ensureGateKitLine: function(array, gate_data, fenceHeight, isSTDGate) {
        array = array || [];
        if (!Array.isArray(gate_data) || !gate_data.length) {
            return array;
        }

        if (!array.some(function(item) { return item?.slug === 'gate+kit'; })) {
            array.push({ slug: 'gate+kit', qty: 1 });
        }

        if (isSTDGate && fenceHeight) {
            var stdSlug = 'gate+' + fenceHeight;
            var hasStd = array.some(function(item) { return item?.slug === stdSlug; });
            if (!hasStd) {
                array = array.filter(function(item) {
                    var s = item?.slug;
                    return s !== 'gate' && s !== 'gate+1';
                });
                array.push({ slug: stdSlug, qty: 1 });
            }
        }

        return array;
    },

    /**
     * Barr @ 1000mm fence height: gate opening requires gate+converter+1000 (standard or custom).
     */
    ensureBarrGateConverter1000: function(array, gateKit2Template) {
        array = array || [];
        if (!gateKit2Template?.slug) {
            return array;
        }
        var converterSlug = gateKit2Template.slug + '+1000';
        if (!array.some(function(item) { return item?.slug === converterSlug; })) {
            array.push({
                slug: converterSlug,
                qty: parseInt(gateKit2Template.qty, 10) || 1
            });
        }
        return array;
    },

    applyGateKitConditions: function(array, context, gateKit2Template) {
        var fence = context?.tabInfo?.[0]?.fence;
        if ($.inArray(fence, ['barr', 'flat_top', 'slat', 'slat_fence', 'slat_fence_infill']) === -1) {
            return array;
        }

        var fenceInfo = context?.fenceInfo || [];
        var gate_data = fenceInfo.filter(function(item) {
            return item.control_key == 'gate';
        });

        if (!this.hasConfiguredGate(array, gate_data)) {
            return array;
        }

        // STD unless use_std is explicitly false (missing field = standard gate).
        var isSTDGate =
            typeof FENCE !== 'undefined' && typeof FENCE.isStdGate === 'function'
                ? FENCE.isStdGate(gate_data)
                : this.isSlatStdGate(gate_data);

        var fenceHeight = '';
        if (this.isSlatLike(fence)) {
            var maxH = this.getContextFieldValue(context, 'max_fence_height', '');
            fenceHeight = this.getHeightKeyFromMax(maxH);
        } else {
            fenceHeight = parseInt(this.getContextFieldValue(context, 'fence_height', ''), 10);
            if (isNaN(fenceHeight) || !fenceHeight) {
                fenceHeight = parseInt(context?.calc?.fence_size?.height, 10);
            }
            fenceHeight = !isNaN(fenceHeight) && fenceHeight ? fenceHeight : '';
        }

        if (isSTDGate) {
            array = this.ensureGateKitLine(array, gate_data, fenceHeight, true);
        } else if (!gateKit2Template?.slug) {
            array = this.ensureGateKitLine(array, gate_data, fenceHeight, false);
        } else {
            var converterSlug = [gateKit2Template.slug, fenceHeight].filter(Boolean).join('+');
            var hasConverter = array.some(function(item) { return item?.slug === converterSlug; });
            if (!hasConverter) {
                array.push({ ...gateKit2Template, slug: converterSlug });
            }

            var stdGateSlug = fenceHeight ? 'gate+' + fenceHeight : '';
            array = array.filter(function(item) {
                var slug = String(item?.slug || '');
                if (this.isGateAccessorySlug(slug)) {
                    return true;
                }
                return slug !== 'gate' && slug !== stdGateSlug;
            }, this);

            array = this.ensureGateKitLine(array, gate_data, fenceHeight, false);
        }

        if (fence === 'barr' && fenceHeight === 1000) {
            array = this.ensureBarrGateConverter1000(array, gateKit2Template);
        }

        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Sums planner cart lines panel_post+raked_post by opt-1..5 (matches 4-SLAT.php post option slugs).
     */
    aggregatePostOptQtyFromCart: function(array, context) {
        var byOpt = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
        var total = 0;
        var re = /^(?:panel_post|raked_post)\+opt-(\d+)/;
        (array || []).forEach(function(item) {
            var slug = item?.slug || '';
            var m = re.exec(slug);
            if (!m) return;
            var n = parseInt(m[1], 10);
            if (!n || n < 1 || n > 5) return;
            var q = parseInt(item.qty, 10) || 0;
            if (q <= 0) return;
            byOpt[n] += q;
            total += q;
        });

        // Gate Options modal: First/Second post (opt-1…opt-5) — not on data-cart-key lines.
        try {
            var rawFence = context?.tabInfo?.[0]?.fence || '';
            var canon =
                typeof normalizeFenceStyleSlug === 'function'
                    ? normalizeFenceStyleSlug(rawFence)
                    : rawFence;
            if (canon === 'slat_fence') {
                canon = 'slat';
            }
            if (context && canon === 'slat') {
                var fenceInfo = context.fenceInfo || [];
                var gateItem = fenceInfo.find(function(it) {
                    return it?.control_key === 'gate';
                });
                var fields = gateItem?.settings?.fields || [];
                ['gate_first_post', 'gate_second_post'].forEach(function(key) {
                    var f = fields.find(function(x) {
                        return x?.key === key;
                    });
                    var val = f?.val;
                    if (!val) return;
                    var mm = /^opt-(\d)$/.exec(String(val));
                    if (!mm) return;
                    var num = parseInt(mm[1], 10);
                    if (num < 1 || num > 5) return;
                    byOpt[num] += 1;
                    total += 1;
                });
            }
        } catch (eGateOpt) {}

        return { byOpt: byOpt, total: total };
    },

    /**
     * Panel count from calculate_fences() — matches form F326-style "panel QTY" for SFS (F239 / F238).
     */
    slatPanelCountFromCalc: function(calc) {
        if (!calc) return 0;
        var lp = calc.long_panel?.count || 0;
        var sp = calc.short_panel?.count || 0;
        return lp + sp;
    },

    /**
     * Slat fence catalog row from `4-SLAT.php` (via `fc_data` in planner.php).
     */
    resolveSlatCatalogInfo: function(info) {
        var src = info || {};
        if (
            (parseInt(src.max_panel_span_mm, 10) > 0) ||
            (parseInt(src.max_panel_width_mm, 10) > 0)
        ) {
            return src;
        }
        if (typeof fc_data !== 'undefined') {
            return fc_data.slat || fc_data.slat_fence || src;
        }
        return src;
    },

    /**
     * Slat Planner V6 F141: span divisor for panel qty — floor(overall / span) + 1 (F82 + 1).
     * Value from `4-SLAT.php` → `max_panel_span_mm`.
     */
    getMaxPanelSpanMmFromInfo: function(info) {
        var cfg = this.resolveSlatCatalogInfo(info);
        var v = parseInt(cfg.max_panel_span_mm, 10);
        return (Number.isFinite(v) && v > 0) ? v : NaN;
    },

    /**
     * Max physical panel width (mm) from `4-SLAT.php` → `max_panel_width_mm`.
     */
    getMaxPanelWidthMmFromInfo: function(info, panelOptionsData, calcC5) {
        var cfg = this.resolveSlatCatalogInfo(info);
        var fromInfo = parseInt(cfg.max_panel_width_mm, 10);
        if (Number.isFinite(fromInfo) && fromInfo > 0) {
            return fromInfo;
        }
        var def = parseInt(panelOptionsData?.size?.default, 10);
        if (Number.isFinite(def) && def > 0) {
            return def;
        }
        var w = parseInt(calcC5, 10);
        if (Number.isFinite(w) && w > 0) {
            return Math.max(1, w - 25);
        }
        return NaN;
    },

    /**
     * Panel qty from overall span: midPosts = floor(span / maxSpan), panels = midPosts + 1 (Slat Planner V6 F82 + 1).
     * `maxPanelSpanMm` from `getMaxPanelSpanMmFromInfo()` / `4-SLAT.php` `max_panel_span_mm`.
     */
    countEvenPanelsFromOverallSpan: function(spanMm, maxPanelSpanMm, postWidthMm, info, removedPostsMm) {
        var span = parseInt(spanMm, 10);
        var maxSpan = parseInt(maxPanelSpanMm, 10);
        if (!Number.isFinite(maxSpan) || maxSpan <= 0) {
            maxSpan = this.getMaxPanelSpanMmFromInfo(info);
        }
        if (!Number.isFinite(span) || span <= 0) {
            return 1;
        }
        if (!Number.isFinite(maxSpan) || maxSpan <= 0) {
            return 1;
        }
        var midPosts = Math.floor(span / maxSpan);
        var count = Math.max(1, midPosts + 1);

        // A bay can still land over `max_panel_width_mm` once interior posts are removed
        // from the span — add posts until every even panel fits the widest stock panel.
        var maxWidth = this.getMaxPanelWidthMmFromInfo(info);
        var postW = parseInt(postWidthMm, 10);
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        if (Number.isFinite(maxWidth) && maxWidth > 0) {
            while (
                count < 500 &&
                this.computeSlatEvenPanelWidthMm(span, count, postW, removedPostsMm) > maxWidth
            ) {
                count += 1;
            }
        }

        return count;
    },

    /**
     * End posts removed via left/right "no-post" (mm). Same as `FENCE.minus_posts()`.
     */
    getRemovedEndPostsMm: function(custom_fence) {
        if (typeof FENCE !== 'undefined' && typeof FENCE.minus_posts === 'function') {
            var removed = parseInt(FENCE.minus_posts(custom_fence || []), 10);
            return (Number.isFinite(removed) && removed > 0) ? removed : 0;
        }
        return 0;
    },

    /**
     * Total post width (mm) for N panels: (N+1)×post minus removed end posts.
     */
    computeSlatTotalPostsMm: function(panelCount, postWidthMm, removedPostsMm) {
        var n = parseInt(panelCount, 10);
        var postW = parseInt(postWidthMm, 10);
        var removed = parseInt(removedPostsMm, 10);
        if (!Number.isFinite(n) || n <= 0) {
            return 0;
        }
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        if (!Number.isFinite(removed) || removed < 0) {
            removed = 0;
        }
        return Math.max(0, (n + 1) * postW - removed);
    },

    /**
     * Total panel material (mm) inside a clear span: only the N−1 interior posts.
     *
     * `spanMm` comes from `getSlatInfillSpanMm()`, which has already applied the
     * "Width Dimension From" offset (−1×post center-line, −2×post outside) and so
     * excludes both end posts. Deducting `(N+1)×post` here as well would remove the
     * end posts twice. `removedPostsMm` is added back because that offset assumed
     * both end posts were present.
     */
    computeSlatPanelSpanMm: function(spanMm, panelCount, postWidthMm, removedPostsMm) {
        var span = parseInt(spanMm, 10);
        var n = parseInt(panelCount, 10);
        var postW = parseInt(postWidthMm, 10);
        var removed = parseInt(removedPostsMm, 10);
        if (!Number.isFinite(span) || !Number.isFinite(n) || n <= 0) {
            return 0;
        }
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        if (!Number.isFinite(removed) || removed < 0) {
            removed = 0;
        }
        return Math.max(0, span + removed - (n - 1) * postW);
    },

    /**
     * Even panel width (mm): ceil(panelSpan / N) where panelSpan excludes interior posts only.
     * `removedPostsMm` from `getRemovedEndPostsMm()` when left/right end is no-post.
     */
    computeSlatEvenPanelWidthMm: function(overallMm, panelCount, postWidthMm, removedPostsMm) {
        var ov = parseInt(overallMm, 10);
        var n = parseInt(panelCount, 10);
        var postW = parseInt(postWidthMm, 10);
        if (!Number.isFinite(ov) || ov <= 0 || !Number.isFinite(n) || n <= 0) {
            return 0;
        }
        if (!Number.isFinite(postW) || postW <= 0) {
            postW = 50;
        }
        var avail = this.computeSlatPanelSpanMm(ov, n, postW, removedPostsMm);
        if (avail <= 0) {
            return 0;
        }
        return Math.ceil(avail / n);
    },

    /**
     * Raw Step 2 overall (measurement box) before width-dimension offset — used for Slat panel layout.
     */
    readSlatRawOverallMm: function(custom_fence_tab, slug) {
        var rawOv = parseInt(String($('.measurement-box-number').val() || '').replace(/,/g, ''), 10);
        if (!Number.isFinite(rawOv) && typeof fcReadCalculateValueForStyle === 'function' && custom_fence_tab && custom_fence_tab[0]) {
            rawOv = parseInt(fcReadCalculateValueForStyle(custom_fence_tab[0], slug), 10);
        }
        return Number.isFinite(rawOv) ? rawOv : NaN;
    },

    /**
     * Slat infill: SFS / slat BOM must use the real panel tally (No# Of Panels + cart), not only
     * `calculate_fences()` when it can diverge, and not only visible `.panel-item` rows (preview hides
     * extras behind an "X more panels" tile).
     */
    slatInfillEffectivePanelCount: function(context, calc) {
        var fromCtx = parseInt(String(this.getContextFieldValue(context, 'panel_count', '')), 10);
        if (Number.isFinite(fromCtx) && fromCtx > 0) return fromCtx;

        try {
            if (typeof $ !== 'undefined') {
                var jq = $('[name="panel_count"]');
                if (jq.length) {
                    var live = parseInt(String(jq.val()), 10);
                    if (Number.isFinite(live) && live > 0) return live;
                }
            }
        } catch (e) {}

        try {
            var rendered = document.querySelectorAll('.panel-item:not(.fencing-raked-panel)');
            var nRendered = rendered ? rendered.length : 0;
            var hiddenExtra = 0;
            var banner = document.querySelector('.infill-hidden-panels .text-uppercase');
            if (banner && banner.textContent) {
                var txt = banner.textContent.replace(/\u00a0/g, ' ').trim();
                var mm = /^([\d,\s]+)\s+more\s+panels/i.exec(txt);
                if (mm) {
                    hiddenExtra = parseInt(String(mm[1]).replace(/[, ]+/g, ''), 10) || 0;
                }
            }
            var domTotal = nRendered + hiddenExtra;
            if (domTotal > 0) return domTotal;
        } catch (e2) {}

        return this.slatPanelCountFromCalc(calc);
    },

    /**
     * Gate posts that are "Base Plated" for glue F421 (gate side). Uses Gate Options First/Second post
     * when set; otherwise assumes two base-plated hinge posts (legacy).
     */
    countGateBasePlatedPostsForGlue: function(calc, context) {
        var n = calc?.gate?.count;
        if (!(n > 0)) return 0;
        try {
            var fenceInfo = context?.fenceInfo || [];
            var gateItem = fenceInfo.find(function(it) {
                return it?.control_key === 'gate';
            });
            var fields = gateItem?.settings?.fields || [];
            var gf = fields.find(function(x) {
                return x.key === 'gate_first_post';
            })?.val;
            var gs = fields.find(function(x) {
                return x.key === 'gate_second_post';
            })?.val;
            if (gf || gs) {
                var c = 0;
                if (gf === 'opt-1') c++;
                if (gs === 'opt-1') c++;
                return c;
            }
        } catch (e) {}
        return 2;
    },

    /**
     * Extra BOM lines from slat-fence-app.html / FSQ_FORM_140003302026 sku_lines:
     * - S-120ROD: F422+F240+F342 (base-plated + wall-fix fence posts; F422 = gate wall-fix in Gates mode)
     * - SOUD-CA1400: F421+F241 with F421=F420/4, F241=ceil(F240/4); F240 = base-plated fence posts only
     * - GROUT: F423+F344 (cement-in fence + gate cement)
     * - XPL-EP: F239+F523 → F239 = (panel counts)*2 per segment (here: 2 × panel count); slat infill: packs = ceil(F238 / pack_qty[sfs+end_caps])
     * - XPL-6000-SF: F238+F524 → F238 ≈ 2×panelCount×SFS length(mm)/1000 (single-segment proxy)
     */
    applySlatFormulaFixings: function(array, context, calc, addOrInc) {
        var fenceKind = context?.tabInfo?.[0]?.fence;
        // FSQ infill tab: matrix forces "No Post" — no S-120ROD, cement glue, or slat_post lines (slat-fence-app.html).
        var isSlatInfill = fenceKind === 'slat_fence_infill';

        var postAgg = this.aggregatePostOptQtyFromCart(array, context);
        var byOpt = postAgg.byOpt;
        // FSQ M63 num 1 = Base Plated, 2 = Cement In, 3 = Wall Fix (see form MCQ 63 export).
        var baseFencePosts = byOpt[1] || 0;
        var wallFixFencePosts = byOpt[3] || 0;
        var cementPosts = byOpt[2] || 0;
        var threadRodPosts = baseFencePosts + wallFixFencePosts;

        if (!isSlatInfill) {
            if (threadRodPosts > 0) {
                addOrInc('slat_fixings+thread_rods', threadRodPosts);
            }

            var gateBasePosts = this.countGateBasePlatedPostsForGlue(calc, context);
            // FSQ: F241 = ceil(F240/4), F421 = F420/4, SOUD qty = round(F421+F241) (integerQty uses Math.round).
            var f240 = baseFencePosts;
            var f241 = f240 === 0 ? 0 : Math.ceil(f240 / 4);
            var f420 = gateBasePosts;
            var f421 = f420 === 0 ? 0 : f420 / 4;
            var glueTubes = Math.round(f421 + f241);
            if (glueTubes > 0) {
                addOrInc('slat_fixings+glue_tube', glueTubes);
                addOrInc('slat_fixings+glue_gun', 1);
            }

            if (cementPosts > 0) {
                addOrInc('slat_fixings+cement', cementPosts);
            }
        }

        var fenceHm = this.resolveSlatFenceHeightMm(context, calc);
        if (!Number.isFinite(fenceHm) || fenceHm <= 0) {
            fenceHm = parseInt(String(calc?.fence_size?.height || ''), 10) || 1800;
        }
        var gateHm = this.isSlatLike(fenceKind) && this.isMainSlatSlug(fenceKind)
            ? this.resolveGateSlatHeightMm(context, calc)
            : fenceHm;
        if (!Number.isFinite(gateHm) || gateHm <= 0) {
            gateHm = fenceHm;
        }

        var panelCountFence = this.slatPanelCountFromCalc(calc);
        if (isSlatInfill) {
            var infillPanels = this.slatInfillEffectivePanelCount(context, calc);
            if (infillPanels > 0) panelCountFence = infillPanels;
        }
        var panelCountGate = 0;
        if (!isSlatInfill && calc?.gate?.count && calc?.gate?.width) {
            panelCountGate = Math.max(1, parseInt(calc.gate.count, 10) || 1);
        }
        if (!isSlatInfill && panelCountFence < 1 && this.isSlatGateOnly(context) && panelCountGate > 0) {
            panelCountFence = 0;
        }
        var panelCount = panelCountFence + panelCountGate;
        if (panelCount > 0) {
            var railM = 0;
            if (panelCountFence > 0) {
                railM += (2 * panelCountFence * fenceHm) / 1000;
            }
            if (panelCountGate > 0) {
                railM += (2 * panelCountGate * gateHm) / 1000;
            }
            var railQty = railM > 0 ? Math.max(1, Math.ceil(railM)) : 0;

            if (isSlatInfill) {
                // Slat infill: order end-cap packs from rail line qty ÷ pieces per pack (e.g. rail 6, 2PK → 3 packs).
                if (railQty > 0) {
                    addOrInc('sfs+rail', railQty);
                    var epk = this.getPackItemsForSlug(fenceKind, 'sfs+end_caps');
                    var endCapPacks = Math.ceil(railQty / epk);
                    if (endCapPacks > 0) {
                        addOrInc('sfs+end_caps', endCapPacks);
                    }
                }
            } else {
                var endCapPairs = 2 * panelCount;
                addOrInc('sfs+end_caps', endCapPairs);
                if (railQty > 0) {
                    addOrInc('sfs+rail', railQty);
                }
            }
        }

        var hasFenceBody =
            panelCount > 0 ||
            threadRodPosts > 0 ||
            cementPosts > 0 ||
            (calc?.gate?.count > 0);
        if (hasFenceBody) {
            addOrInc('slat_gate+screws_flat', 1);
        }

        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Centre support rail (products.csv `centre_support_rail`): one per infill/opening ≥ 2000 mm,
     * and one per gate leaf whose panel width is ≥ 1500 mm (Slat + Slat Infill only).
     */
    getCentreSupportRailCartQty: function(context, calc) {
        var fenceSlug = context?.tabInfo?.[0]?.fence || '';
        if (!this.isSlatLike(fenceSlug) || !calc) {
            return 0;
        }

        var panelMinMm = 2000;
        var gateMinMm = 1500;
        var qty = 0;

        var addWidePanels = function(widthMm, count) {
            var w = parseInt(widthMm, 10);
            var n = parseInt(count, 10) || 0;
            if (Number.isFinite(w) && w >= panelMinMm && n > 0) {
                qty += n;
            }
        };

        if (fenceSlug === 'slat_fence_infill') {
            var openingW = parseInt(calc?.long_panel?.length || calc?.even_panel?.length, 10);
            var panelN = this.slatInfillEffectivePanelCount(context, calc);
            if (!panelN) {
                panelN = parseInt(calc?.long_panel?.count, 10) || 0;
            }
            addWidePanels(openingW, panelN);
        } else {
            if ((parseInt(calc?.even_panel?.count, 10) || 0) > 0) {
                addWidePanels(calc?.even_panel?.length, calc?.even_panel?.count);
            } else {
                addWidePanels(calc?.long_panel?.length, calc?.long_panel?.count);
                addWidePanels(calc?.short_panel?.length, calc?.short_panel?.count);
                addWidePanels(calc?.full_panel?.length, calc?.full_panel?.count);
            }
            if ((parseInt(calc?.left_raked?.width, 10) || 0) >= panelMinMm) {
                qty += 1;
            }
            if ((parseInt(calc?.right_raked?.width, 10) || 0) >= panelMinMm) {
                qty += 1;
            }
        }

        if (calc?.gate?.count > 0) {
            var gItem = (context?.fenceInfo || []).find(function(it) {
                return it?.control_key === 'gate';
            });
            var gateData = gItem ? [gItem] : [];
            var gatePanelW = this.getGateLeafWidthMm(fenceSlug, gateData, calc);
            var fields = gItem?.settings?.fields || [];
            var isDouble = fields.find(function(x) {
                return x.key === 'gate_type';
            })?.val === 'double';
            if (Number.isFinite(gatePanelW) && gatePanelW >= gateMinMm) {
                var gc = Math.max(1, parseInt(calc.gate.count, 10) || 1);
                var leaves = isDouble ? 2 : 1;
                qty += gc * leaves;
            }
        }

        return qty;
    },

    applyCartConditions: function(array, context) {
        if (!this.isSlatLike(context?.tabInfo?.[0]?.fence)) {
            return array;
        }

        var fenceSlug = context?.tabInfo?.[0]?.fence || '';
        var slatSize = this.getSizeMm(context?.fenceInfo || [], 65, fenceSlug);

        var infoMeta =
            (typeof fc_data !== 'undefined' && context?.tabInfo?.[0]?.fence && fc_data[context.tabInfo[0].fence])
                ? fc_data[context.tabInfo[0].fence]
                : null;
        var slatGap = this.getGapMm(context?.fenceInfo || [], infoMeta);
        var panelPitch = this.resolveSlatPanelPitchInputs(context, fenceSlug);

        if (!Number.isFinite(slatSize) || !Number.isFinite(slatGap)) {
            return array;
        }

        var calc = context?.calc || calculate_fences();
        if (!calc) return array;

        var isSlatInfillOpenings = fenceSlug === 'slat_fence_infill';

        // Slat rows from height: fence panels use Step 2 Fence Height; gate panels use Gate Height (modal).
        var fenceHm = this.resolveSlatFenceHeightMm(context, calc);
        var gateHm = this.isMainSlatSlug(fenceSlug) ? this.resolveGateSlatHeightMm(context, calc) : fenceHm;
        var rowCountFence = this.countSlatPanelRowsFromMaxHeightMm(
            fenceHm,
            panelPitch.sizePitch,
            panelPitch.gapPitch
        );
        var rowCountGate =
            gateHm !== fenceHm
                ? this.countSlatPanelRowsFromMaxHeightMm(
                      gateHm,
                      panelPitch.sizePitch,
                      panelPitch.gapPitch
                  )
                : rowCountFence;
        if (rowCountFence < 1 && rowCountGate < 1) {
            return array;
        }

        var panelCountFence = 0;
        var panelCountGate = 0;

        if (isSlatInfillOpenings) {
            panelCountFence = this.slatInfillEffectivePanelCount(context, calc);
        } else {
            panelCountFence = this.slatPanelCountFromCalc(calc);
            if (calc.left_raked?.width) {
                panelCountFence += 1;
            }
            if (calc.right_raked?.width) {
                panelCountFence += 1;
            }
            if (calc.gate?.count && calc.gate?.width) {
                panelCountGate = Math.max(1, parseInt(calc.gate.count, 10) || 1);
            }
        }

        var panelCount = panelCountFence + panelCountGate;

        // FSQ spacer piece tally (F235 core): (F81*F326)*2 → 2 × slat rows × panel openings.
        // This stays on the same scale as slat row×panel counts so one 50-pc pack covers typical runs
        // (e.g. 6×3 panels → 36 pieces → ceil(36/50)=1 pack). The prior k-expanded sum often exceeded 50
        // and incorrectly ordered 2+ packs when one pack was enough.
        var totalSpacerPieces = 0;
        if (rowCountFence > 0 && panelCountFence > 0) {
            totalSpacerPieces += 2 * rowCountFence * panelCountFence;
        }
        if (rowCountGate > 0 && panelCountGate > 0) {
            totalSpacerPieces += 2 * rowCountGate * panelCountGate;
        }

        var sizeKey = slatSize >= 80 ? 90 : 65;
        var gapKey = this.gapMmToSpacerCatalogKey(slatGap);
        if (gapKey === null) {
            return array;
        }

        /** Row × panel lines — replace on each recalc (never accumulate). */
        var isRowPanelDerivedSlug = function(slug) {
            var s = String(slug || '');
            return /^slat\+(65|90)$/.test(s) || s === 'slat_gate+blade_65' || /^slat_spacer\+/.test(s);
        };
        array = (array || []).filter(function(item) {
            return !isRowPanelDerivedSlug(item?.slug);
        });

        var setQty = function(slug, qty) {
            qty = parseInt(qty, 10);
            if (!slug || !qty || qty <= 0) {
                return;
            }
            var found = array.find(function(item) {
                return item?.slug === slug;
            });
            if (found) {
                found.qty = qty;
            } else {
                array.push({ slug: slug, qty: qty });
            }
        };

        var addOrInc = function(slug, qty) {
            qty = parseInt(qty, 10);
            if (!qty || qty <= 0) return;
            var found = array.find(function(item) {
                return item?.slug === slug;
            });
            if (found) {
                found.qty += qty;
            } else {
                array.push({ slug: slug, qty: qty });
            }
        };

        // Fence infill slats vs gate leaf: only 65mm has a distinct gate-blade SKU in catalog (slat_gate+blade_65).
        var slatQtyFence =
            rowCountFence > 0 && panelCountFence > 0 ? rowCountFence * panelCountFence : 0;
        var slatQtyGateLeaf =
            rowCountGate > 0 && panelCountGate > 0 ? rowCountGate * panelCountGate : 0;
        if (slatQtyFence > 0) {
            setQty('slat+' + sizeKey, slatQtyFence);
        }
        if (slatQtyGateLeaf > 0) {
            if (sizeKey === 65) {
                setQty('slat_gate+blade_65', slatQtyGateLeaf);
            } else {
                setQty('slat+' + sizeKey, slatQtyGateLeaf);
            }
        }

        var spacerSlug = 'slat_spacer+' + gapKey;
        var fenceSlugForPack = context?.tabInfo?.[0]?.fence || 'slat';
        var itemsPerPack = this.getPackItemsForSlug(fenceSlugForPack, spacerSlug);
        var spacerPacks =
            gapKey > 0 ? this.ceilPackQuantity(totalSpacerPieces, itemsPerPack) : 0;
        if (spacerPacks > 0) {
            setQty(spacerSlug, spacerPacks);
        }

        // XPS gate frame kits: required for any slat gate opening (fence+gate or gate-only; was gate-only only).
        if (calc.gate && calc.gate.count > 0) {
            this.applySlatGateFrameHardware(array, context, calc, addOrInc, gapKey);
        }

        this.ensureSlatGateTrucloseHinge(array, context, calc, addOrInc);

        var hdRailQty = this.getSlatHeavyDutyRailCartQty(context, calc);
        if (hdRailQty > 0) {
            addOrInc('slat_gate+hd_rail', hdRailQty);
        }

        this.applySlatFormulaFixings(array, context, calc, addOrInc);

        array = (array || []).filter(function(item) {
            return item?.slug !== 'centre_support_rail';
        });
        var centreRailQty = this.getCentreSupportRailCartQty(context, calc);
        if (centreRailQty > 0) {
            setQty('centre_support_rail', centreRailQty);
        }

        return array;
    },

    /** Rebuild cart BOM for the active section after height/panel layout changes. */
    syncSlatCartForTab: function(tabIdx) {
        if (typeof fcRefreshPlannerCartForTab === 'function') {
            fcRefreshPlannerCartForTab(tabIdx);
        }
    },

    /**
     * Any slat configuration with a gate: XPS side frames, covers, caps, frame screws (products-slat-gate.csv).
     * Previously only ran for "Gate ONLY"; fence+gate was missing these lines.
     */
    applySlatGateFrameHardware: function(array, context, calc, addOrInc, gapKey) {
        if (!calc || !(calc.gate?.count > 0)) {
            return;
        }

        var gc = Math.max(1, parseInt(calc.gate.count, 10) || 1);
        var gItem = (context?.fenceInfo || []).find(function(it) {
            return it?.control_key === 'gate';
        });
        var fields = gItem?.settings?.fields || [];
        var field = function(key) {
            var f = fields.find(function(x) {
                return x.key === key;
            });
            return f?.val;
        };

        var doubleGate = field('gate_type') === 'double';
        var leafMult = doubleGate ? 2 : 1;

        var sideSlug = gapKey >= 15 ? 'slat_gate+side_frame_20mm' : 'slat_gate+side_frame_9mm';
        addOrInc(sideSlug, 2 * leafMult * gc);
        addOrInc('slat_gate+frame_cover', 2 * leafMult * gc);
        addOrInc('slat_gate+frame_cap', 2 * leafMult * gc);
        addOrInc('slat_gate+screws_frame', gc);
    },

    /**
     * Heavy-duty gate rails: single gate = 2, double = 4 (per gate leaf pair), scaled by gate count.
     */
    getSlatHeavyDutyRailCartQty: function(context, calc) {
        if (!this.isSlatLike(context?.tabInfo?.[0]?.fence)) {
            return 0;
        }
        if (!calc || !(calc.gate?.count > 0)) {
            return 0;
        }
        var gItem = (context?.fenceInfo || []).find(function(it) {
            return it?.control_key === 'gate';
        });
        var fields = gItem?.settings?.fields || [];
        var heavy = fields.find(function(x) {
            return x.key === 'heavy_duty_rails';
        });
        if (heavy?.val !== 'yes') {
            return 0;
        }
        var gt = fields.find(function(x) {
            return x.key === 'gate_type';
        });
        var leafMult = gt?.val === 'double' ? 2 : 1;
        var gc = Math.max(1, parseInt(calc.gate.count, 10) || 1);
        return 2 * leafMult * gc;
    },

    /**
     * Slat / Slat Infill: default gate hardware whenever a gate is present (hinge type removed from modal).
     * Tru-Close hinge set plus Lokk latch + handle — same scale as double/single leaves.
     */
    ensureSlatGateTrucloseHinge: function(array, context, calc, addOrInc) {
        if (!this.isSlatLike(context?.tabInfo?.[0]?.fence)) {
            return;
        }
        if (!calc || !(calc.gate?.count > 0)) {
            return;
        }
        var gc = Math.max(1, parseInt(calc.gate.count, 10) || 1);
        var gItem = (context?.fenceInfo || []).find(function(it) {
            return it?.control_key === 'gate';
        });
        var fields = gItem?.settings?.fields || [];
        var gt = fields.find(function(x) {
            return x.key === 'gate_type';
        });
        var leafMult = gt?.val === 'double' ? 2 : 1;
        var kitQty = gc * leafMult;
        addOrInc('slat_gate+hinge_truclose', kitQty);
        addOrInc('slat_gate+latch_deluxe', kitQty);
        addOrInc('slat_gate+handle', kitQty);
    },

    //----------------------------------------------------------------------------------

}
