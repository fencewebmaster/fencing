SlatFenceInfill = {

    settings: {
        slat_fence_infill: {
            gate: 975 + 50 + 20 + 20,
            gate_space_left: 20,
            gate_space_right: 20,
            post: 50,
            minOnGate: 975 + 50 + 20 + 20 + 50,
            maxOnGate: 1165,
            minPanelWidthOnGate: 86,
            gate_post_gaps: 50 + 20 + 20,
            gate_posts_gaps: 50 + 20 + 20 + 50,
            panel_preview_limit: 7,
        }
    },

    //----------------------------------------------------------------------------------

    init: function(func, a, b, c, d, e, f) {
        HELPER.call_fence_func(this, func, a, b, c, d, e, f);
    },

    //----------------------------------------------------------------------------------

    test: function() {
        console.log('SLAT INFILL:', 'SlatFenceInfill.test()');
    },

    //----------------------------------------------------------------------------------

    getSetting: function(fence, key) {
        return this.settings?.[fence]?.[key];
    },

    //----------------------------------------------------------------------------------

    getPreviewLimit: function() {
        var previewLimit = parseInt(this.settings?.slat_fence_infill?.panel_preview_limit || 7, 10);
        return Number.isFinite(previewLimit) && previewLimit > 1 ? previewLimit : 7;
    },

    //----------------------------------------------------------------------------------

    isActive: function(slug) {
        return slug === 'slat_fence_infill';
    },

    /**
     * Match `5-SLAT-INFILL.php`: when `settings.gate.disabled === false`, gate modal is used but
     * the Gate ONLY row must not appear (Step 3 / gate options modal).
     */
    shouldHideGateOnlyInGateModal: function(gateSettings) {
        return !!(gateSettings && gateSettings.disabled === false);
    },

    //----------------------------------------------------------------------------------

    getStep2MeasurementCopy: function() {
        return {
            title: 'Enter your measurements',
            measurementLabel: 'Opening Width',
            noteTitle: 'Opening Width',
            noteCopy: 'Enter the width for each panel opening. The planner will build the section using the selected number of equal openings with a post at each end and between every panel.',
            overallLabel: 'Opening Width'
        };
    },

    //----------------------------------------------------------------------------------

    isPanelCountRequired: function(slug) {
        return this.isActive(slug);
    },

    //----------------------------------------------------------------------------------

    validatePanelCountField: function(el) {
        if (!el || el.name !== 'panel_count') {
            return { valid: true, message: '' };
        }

        var raw = (el.value || '').toString().trim();
        var min = parseInt(el.getAttribute('min') || '1', 10);
        var max = parseInt(el.getAttribute('max') || '100', 10);
        var num = Number(raw);

        if (!raw) return { valid: false, message: 'Please enter the amount' };
        if (!Number.isFinite(num) || !Number.isInteger(num)) return { valid: false, message: 'Please enter a whole number' };
        if (Number.isFinite(min) && num < min) return { valid: false, message: 'Minimum panel count is ' + min };
        if (Number.isFinite(max) && num > max) return { valid: false, message: 'Maximum panel count is ' + max };

        return { valid: true, message: '' };
    },

    //----------------------------------------------------------------------------------

    getStep2AutoCalculateGuard: function(args = {}) {
        var slug = args.slug;
        if (!this.isPanelCountRequired(slug)) return true;

        var panelCountEl = args.panelCountEl;
        var validatePanelCountFn = args.validatePanelCountFn;
        if (typeof validatePanelCountFn !== 'function') {
            validatePanelCountFn = (el) => this.validatePanelCountField(el).valid;
        }

        return validatePanelCountFn(panelCountEl);
    },

    //----------------------------------------------------------------------------------

    getLongPanelRenderPlan: function(slug, totalLongPanels) {
        var totalPanels = parseInt(totalLongPanels || 0, 10);
        if (!Number.isFinite(totalPanels) || totalPanels < 0) {
            totalPanels = 0;
        }

        if (!this.isActive(slug)) {
            return {
                renderLongPanels: totalPanels,
                hiddenLongPanels: 0
            };
        }

        var previewLimit = this.getPreviewLimit();
        var hasHiddenPanels = totalPanels > previewLimit;
        var renderLongPanels = hasHiddenPanels ? previewLimit - 1 : Math.min(totalPanels, previewLimit);
        var hiddenLongPanels = hasHiddenPanels ? totalPanels - renderLongPanels : 0;

        return {
            renderLongPanels: renderLongPanels,
            hiddenLongPanels: hiddenLongPanels
        };
    },

    //----------------------------------------------------------------------------------

    applyPostUiAdjustments: function(containerSelector) {
        if (!containerSelector) return;

        var $root = $(containerSelector);
        $root
            .find('.panel-post')
            .removeClass('fencing-btn-modal')
            .removeAttr('data-target');

        if (typeof SlatFence !== 'undefined' && SlatFence.shouldHidePostValue('slat_fence_infill')) {
            $root.addClass('fc-hide-post-value');
            $root.find('.fencing-panel-spacing-number > span:first-child').text('');
        }
    },

    //----------------------------------------------------------------------------------

    appendHiddenPanelsTile: function(containerSelector, hiddenLongPanels) {
        if (!containerSelector) return;

        var hiddenCount = parseInt(hiddenLongPanels || 0, 10);
        if (!Number.isFinite(hiddenCount) || hiddenCount <= 0) return;

        var hiddenLabel = (typeof HELPER?.number_format === 'function')
            ? HELPER.number_format(hiddenCount)
            : hiddenCount;

        var hiddenTpl = `
            <div class="ms-3 fencing-offcut infill-hidden-panels">
                <div class="offcut-body">
                    <div>
                        <div class="text-uppercase">${hiddenLabel} more panels</div>
                    </div>
                </div>
            </div>
        `;

        // The cart counts [data-cart-key] nodes, so the panels this tile stands in for have to be
        // billed by something. Fold their quantity onto the last rendered panel and its paired post
        // rather than emitting hidden nodes: extra .fencing-panel-item / .panel-post elements would
        // capture `.panel-post:last` (diagram scroll-centering, events.js) and the .last-item /
        // .last() display logic. The paired post is the one preceding the panel in panel_item-b.
        var $container = $(containerSelector);
        var $lastPanel = $container.find('.fencing-panel-item.long-panel-item').last();
        if ($lastPanel.length) {
            $lastPanel.attr('data-cart-qty', hiddenCount + 1);
            var $pairedPost = $lastPanel.prevAll('.panel-post').first();
            if ($pairedPost.length) {
                $pairedPost.attr('data-cart-qty', hiddenCount + 1);
            }
        }

        $(containerSelector).append(hiddenTpl);
    },

    //----------------------------------------------------------------------------------

}
