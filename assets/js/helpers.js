//Global Variable
var HELPER = HELPER || {};
var step = 1;
var fcStep3ResultBaseHeight = null;
var fcZoomMinStep = 0.1;

HELPER = {

    //----------------------------------------------------------------------------------

    countLocalStorageFenceKeys: function(target) {
        let count = 0;
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            // Check if the key contains the substring "custom_fence"
            if (key.includes(target)) {
                count++;
            }
        }
        return count;
    },

    //----------------------------------------------------------------------------------

    /**
     * GET Segment URI value | key=value
     * @param {string} k 
     * @returns string
     */
    getSearchParams: function(k) {
        var p = {};
        location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(s, k, v) { p[k] = v })
        return k ? p[k] : p;
    },

    //----------------------------------------------------------------------------------

    /**
     * 
     * @returns Check if device is tablet/mobile
     */
    isMobileDevice: function() {
        // Check the user agent string for common mobile keywords
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },

    //----------------------------------------------------------------------------------

    /**
     * Move Horizontal scroll position
     * @param {string} _el 
     */
    moveScrollPosition: function(_el, _position) {
        $(_el).animate({ scrollLeft: _position }, 0);
    },

    //----------------------------------------------------------------------------------

    /**
     * Horizontally scroll the section tab strip so the given tab is centered in
     * `.js-fencing-tab-container` (when tabs overflow). No-op if there is nothing to scroll.
     *
     * @param {JQuery} $tab The `.fencing-tab` element to bring into view.
     */
    scrollFencingTabIntoCenter: function($tab) {
        if (!$tab || !$tab.length) {
            return;
        }
        var containerSel = FENCES?.el?.tabContainer || '.js-fencing-tab-container';
        var $scroll = $(containerSel);
        if (!$scroll.length) {
            return;
        }
        var scrollEl = $scroll[0];
        var tabEl = $tab[0];
        var maxScroll = Math.max(0, scrollEl.scrollWidth - scrollEl.clientWidth);
        if (maxScroll <= 0) {
            return;
        }
        var scrollRect = scrollEl.getBoundingClientRect();
        var tabRect = tabEl.getBoundingClientRect();
        var delta = (tabRect.left + tabRect.width / 2) - (scrollRect.left + scrollRect.width / 2);
        var newScrollLeft = scrollEl.scrollLeft + delta;
        newScrollLeft = Math.max(0, Math.min(newScrollLeft, maxScroll));
        $scroll.stop(true).animate({ scrollLeft: newScrollLeft }, 280);
    },

    //----------------------------------------------------------------------------------

    /**
     * Horizontal scroll container for fence diagrams (planner Step 3 hscroll or legacy strip).
     * @param {JQuery} [$context]
     * @returns {JQuery}
     */
    getFenceDiagramHScroll$: function($context) {
        if ($context && $context.length) {
            if ($context.hasClass('fc-project-plan-hscroll')) {
                return $context;
            }
            if ($context.hasClass('fencing-display-result')) {
                var $nestedFrom = $context.children('.fc-project-plan-hscroll').first();
                return $nestedFrom.length ? $nestedFrom : $context;
            }
            var $inCtx = $context.find('.fc-project-plan-hscroll').first();
            if ($inCtx.length) {
                return $inCtx;
            }
            var $resultInCtx = $context.find('.fencing-display-result').first();
            if ($resultInCtx.length) {
                return HELPER.getFenceDiagramHScroll$($resultInCtx);
            }
        }

        var $planner = $('.fc-planner-page .fencing-display-result:visible .fc-project-plan-hscroll').first();
        if ($planner.length) {
            return $planner;
        }

        var $visible = $('.fencing-display-result:visible').first();
        if (!$visible.length) {
            return $();
        }
        return HELPER.getFenceDiagramHScroll$($visible);
    },

    //----------------------------------------------------------------------------------

    /**
     * Horizontally scroll each fence diagram strip so the panel preview is centered (zoom reset).
     */
    centerFencingDisplayHorizontally: function() {
        $('.fencing-display-result').each(function() {
            var $result = $(this);
            var $scroll = HELPER.getFenceDiagramHScroll$($result);
            if (!$scroll.length) {
                return;
            }
            var $block = $scroll.find('.fencing-panel-items').first();
            if (!$block.length) {
                $block = $result.find('.fencing-panel-items').first();
            }
            if (!$block.length) {
                return;
            }
            var el = $scroll[0];
            var viewW = $scroll.width();
            var posLeft = $block.position().left;
            var blockW = $block.outerWidth();
            var pos = posLeft + blockW / 2 - viewW / 2;
            var maxScroll = Math.max(0, el.scrollWidth - viewW);
            if (pos < 0) {
                pos = 0;
            } else if (pos > maxScroll) {
                pos = maxScroll;
            }
            el.scrollLeft = pos;
        });
    },

    //----------------------------------------------------------------------------------

    /**
     * Draggable elements
     */
    draggable: function(_parent, _content) {
        if (this.isMobileDevice()) {
            return;
        }

        // Select the draggable element and its content
        const draggableElement = document.querySelector(_parent);
        const contentElement = draggableElement.querySelector(_content);

        let isDragging = false;
        let initialX;
        let xOffset = 0;

        // Function to handle the mouse down event
        function onMouseDown(event) {
            isDragging = true;
            initialX = event.clientX;
            xOffset = draggableElement.scrollLeft;
        }

        // Function to handle the mouse move event
        function onMouseMove(event) {
            if (isDragging) {
                const currentX = event.clientX;
                const deltaX = currentX - initialX;
                draggableElement.scrollLeft = xOffset - deltaX;
            }
        }

        // Function to handle the mouse up event
        function onMouseUp() {
            isDragging = false;
        }

        //Attach events
        draggableElement.addEventListener('mousedown', onMouseDown);
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);
    },

    //----------------------------------------------------------------------------------

    zoom: function(parent, direction) {
        var slider = $(parent).closest('.fencing-input-range').find("input");
        var step = parseInt(slider.attr('step'), 10);
        var currentSliderValue = parseInt(slider.val(), 10);
        var newStepValue = currentSliderValue + step;
        if (direction === "out") {
            newStepValue = currentSliderValue - step;
        } else {
            newStepValue = currentSliderValue + step;
        }
        slider.val(newStepValue).change();
    },

    //----------------------------------------------------------------------------------


    /**
     * 
     * Prepare to load values from local storage
     * @param {json} filtered_data 
     * 
     */
    set_field_value: function(filtered_data) {
        if (!filtered_data) {
            return;
        }
        $(filtered_data).each(function(i, item) {
            // Gate rows store `settings` as an object (`placement`, `size`, `fields`, …), not an array of rows.
            if (item.control_key === 'gate' && item.settings) {
                if (Array.isArray(item.settings.fields)) {
                    $(item.settings.fields).each(function(j, f) {
                        HELPER.get_field_value(f.tag, f.key, f.val);
                    });
                }
                if (item.settings.gateOnly !== undefined && item.settings.gateOnly !== null) {
                    HELPER.get_field_value('input', 'gate_only', item.settings.gateOnly);
                }
                return;
            }
            if (!item.settings || !$.isArray(item.settings)) {
                return;
            }
            $(item.settings).each(function(j, row) {
                if (row.fields) {
                    $(row.fields).each(function(k, f) {
                        HELPER.get_field_value(f.tag, f.key, f.val);
                    });
                } else {
                    HELPER.get_field_value(row.tag, row.key, row.val);
                }
            });
        });
    },

    //----------------------------------------------------------------------------------

    /**
     * Load values from local storage
     * @param {string} tag 
     * @param {string} key 
     * @param {string} val 
     */
    get_field_value: function(tag, key, val) {
        if (tag == 'input') {
            var $inp = $('[name=' + key + ']');
            if (!$inp.length) {
                return;
            }
            var typ = ($inp.attr('type') || '').toLowerCase();
            if (typ === 'checkbox') {
                if (val === undefined || val === null) {
                    return;
                }
                var checked = val === true || val === 'true' || val === 1 || val === '1';
                $inp.prop('checked', checked);
                $inp.closest('.fc-select-2').toggleClass('fc-selected', checked);
                return;
            }
            if (!val && val !== 0) {
                return;
            }
            $inp.val(val, function() {
                $(this).trigger('change');
            });

            $inp.closest('.fencing-form-group').find('.fir-info span').text(val);
            $inp.prop('checked', true);
            return;
        }
        if (!val && val !== 0) {
            return;
        }
        if (tag == 'select') {
            $('[name=' + key + ']').val(val);
            $('[name=' + key + ']').attr('value', val);
        } else if (tag == 'div') {
            let getElement = $('[name=' + key + ']'),
                getSelectedEl = getElement.find('.fc-selected');
            // Reset value
            if (getSelectedEl.length) {
                getSelectedEl.removeClass('fc-selected');
            }
            getElement.attr('value', val);
            getElement.find('[data-slug="' + val + '"]').addClass('fc-selected');
           
            // Set preselected value for right and left raked inside modal
           // if (key === "left_raked" || key === "right_raked") {
                if (typeof val !== "undefined" && val) {
                    $('[name=' + key + '] select').val(val);
                } else {
                    $('[name=' + key + '] select').val("none");
                }
           // }
                     
        }
    },

    //----------------------------------------------------------------------------------

    /** Planner Step 3 — diagram result strip used for zoom min-height locking. */
    getStep3DisplayResult$: function() {
        return $('.fc-planner-page .js-fc-form-step[data-section="3"] .fencing-display-result').first();
    },

    /** Capture natural height at 100% zoom (before zoom in/out). */
    captureStep3ResultBaseHeight: function() {
        if (!$('.fc-planner-page').length) {
            return;
        }
        if (step !== 1) {
            return;
        }
        var $el = HELPER.getStep3DisplayResult$();
        if (!$el.length || $el.hasClass('fc-planner-step3-result--loading')) {
            return;
        }
        var height = Math.ceil($el.outerHeight());
        if (height > 0) {
            fcStep3ResultBaseHeight = height;
        }
    },

    applyStep3ResultMinHeight: function() {
        if (!$('.fc-planner-page').length) {
            return;
        }
        if (!fcStep3ResultBaseHeight) {
            HELPER.captureStep3ResultBaseHeight();
        }
        if (!fcStep3ResultBaseHeight) {
            return;
        }
        HELPER.getStep3DisplayResult$().css('min-height', fcStep3ResultBaseHeight + 'px');
    },

    clearStep3ResultMinHeight: function() {
        fcStep3ResultBaseHeight = null;
        HELPER.getStep3DisplayResult$().css('min-height', '');
    },

    /**
     * Hide / Show Zoom Reset Button
     */
    toggleZoomResetButton: function(zoomValue) {
        if (zoomValue == 1) {
            HELPER.hideZoomResetButton();
        } else {
            HELPER.showZoomResetButton();
        }
        HELPER.toggleZoomOutButton(zoomValue);
    },

    toggleZoomOutButton: function(zoomValue) {
        var $out = $('.fc-zoom-fence[data-zoom="out"]');
        if (!$out.length) {
            return;
        }
        if (zoomValue <= fcZoomMinStep) {
            $out.attr('disabled', 'disabled').addClass('disabled');
        } else {
            $out.removeAttr('disabled').removeClass('disabled');
        }
    },

    //----------------------------------------------------------------------------------

    /**
     * Hide Zoom Reset Button
     */
    hideZoomResetButton: function() {
        $(FENCES.el.zoomReset)
            .attr('disabled', 'disabled')
            .addClass('disabled');
    },

    //----------------------------------------------------------------------------------

    /**
     * Show Zoom Reset Button
     */
    showZoomResetButton: function() {
        $(FENCES.el.zoomReset)
            .removeAttr('disabled')
            .removeClass('disabled');
    },

    //----------------------------------------------------------------------------------

    /**
     * Set defaul value for measurement box
     */
    setMeasurementDefaultValue: function() {
        $(FENCES.el.measurementBoxNumber).val(FENCES.defaultValues.measurement);
    },

    //----------------------------------------------------------------------------------

    /**
     * Section tabs appended as direct children of `.js-fencing-tab-container-area`.
     * Scoped to `.fc-planner-page` so theme/duplicate markup cannot skew the count.
     */
    getFenceSectionTabCount: function() {
        var $area = $('.fc-planner-page').find(FENCES.el.tabArea);
        if (!$area.length) {
            $area = $(FENCES.el.tabArea);
        }
        // Exclude tab mid-removal so nested fencingTab/btnCalculate during delete don't see an extra section.
        return $area.first().children('.fencing-tab').not('.is-deleting').length;
    },

    //----------------------------------------------------------------------------------

    /**
     * Show Delete Section when there are 2+ tabs; hide when only one (planner step 1 + step 3 buttons).
     */
    hideDeleteSectionBtn: function() {
        var count = HELPER.getFenceSectionTabCount();
        var _delete_btn = $('.fc-planner-page').length
            ? $('.fc-planner-page').find(FENCES.el.jsBtnDeleteFence)
            : $(FENCES.el.jsBtnDeleteFence);
        if (count > 1) {
            _delete_btn.show().prop('disabled', false).removeAttr('disabled').removeClass('disabled');
        } else {
            _delete_btn.hide().prop('disabled', false).removeAttr('disabled').removeClass('disabled');
        }
    },

    //----------------------------------------------------------------------------------

    /**
     * @deprecated Kept for compatibility; use tab count check + hideDeleteSectionBtn instead.
     */
    stopSectionDeletion: function() {
        let _remaining_tabs = HELPER.getFenceSectionTabCount();
        if (_remaining_tabs == 1) {
            return false;
        }
    },

    //----------------------------------------------------------------------------------

    /**
     * If a user revisit a section tab
     * Check if the calculate button was clicked before
     * If yes, then set the mm input field value 
     * and click it again to load the step 3 section
     * @param {obj} custom_fence_tab 
     */
    loadStep3: function(custom_fence_tab) {
        if (!custom_fence_tab?.isCalculate) {
            return;
        }
        var length = '';
        if (typeof fcReadCalculateValueForStyle === 'function' && custom_fence_tab.style) {
            length = fcReadCalculateValueForStyle(custom_fence_tab, custom_fence_tab.style);
        } else if (custom_fence_tab.calculateValue != null && custom_fence_tab.calculateValue !== '') {
            length = custom_fence_tab.calculateValue;
        }
        if (length === '' || length === undefined || length === null) {
            return;
        }
        if (
            typeof fcIsOverallLengthValueOverMaxMm === 'function' &&
            fcIsOverallLengthValueOverMaxMm(length)
        ) {
            $('.btn-fc-calculate').prev().find('input').val('');
            return;
        }
        $('.btn-fc-calculate').prev().find('input').val(length);
        btnCalculate();
    },

    //----------------------------------------------------------------------------------

    deleteSectionTab: function() {
        var $strip =
            typeof fcGetPlannerSectionTabs$ === 'function'
                ? fcGetPlannerSectionTabs$()
                : $(FENCES.el.tabArea).first().children('.fencing-tab');
        var getActiveTab = $strip.filter('.fencing-tab-selected');
        if (!getActiveTab.length) {
            getActiveTab = $(FENCES.el.fencingTabSelected).filter(function() {
                return $(this).closest(FENCES.el.tabArea).length > 0;
            });
        }
        var getPrevBtn = getActiveTab.prev('.fencing-tab');
        var getNextBtn = getActiveTab.next('.fencing-tab');
        var $tabToSelect = getActiveTab.is(':last-child') ? getPrevBtn : getNextBtn;

        getActiveTab.addClass('is-deleting');
        // Do not trigger `.fencing-tab` click here: while `is-deleting` is still in the strip,
        // sibling `.index()` is one higher than after removal, so `fencingTab` would read the wrong
        // `custom_fence-{n}` after storage was already reindexed (see jsBtnDeleteFence).
        $strip.removeClass('fencing-tab-selected');
        if ($tabToSelect.length) {
            $tabToSelect.addClass('fencing-tab-selected');
        }

        $('.is-deleting').remove();

        HELPER.tabContainerScroll();

        var remaining = HELPER.getFenceSectionTabCount();
        localStorage.setItem('custom_fence-section', String(Math.max(remaining, 1)));
    },

    //----------------------------------------------------------------------------------

    refreshSectionTabIndex: function() {
        $('.fencing-tab-container .fencing-tab').each(function(index) {
            var _this = $(this);
            _this.find('.fencing-tab-number').html(index + 1);
        });
        HELPER.setSectionURLParam();
    },

    //----------------------------------------------------------------------------------

    resetSectionsBlocks: function() {
        $('.fencing-style-item').removeClass('fsi-selected');
        $(FENCES.el.jsFcFormStep).removeAttr('style');
    },
    
    //----------------------------------------------------------------------------------

    /**
     * Load section overlay dynamically
     * @param {string} target 
     */
    loadSectionOverlay: function(target) {
        let tpl = `<div class="fc-section-loader-overlay">
            <div class="fc-loader-container">
                <div class="fc-loader"><div class="fc-loader-gif"></div></div>
            </div>
        </div>`;
        target = document.getElementById(target);
        target.insertAdjacentHTML('afterbegin', tpl);
    },

    //----------------------------------------------------------------------------------

    /**
     * Remove section overlay
     */
    removeSectionOverlay: function() {
        document.querySelector('.fc-section-loader-overlay').remove();
    },

    //----------------------------------------------------------------------------------

    /**
     * Toggle Tab Container Scroll
     * @param {obj} _this 
     */
    tabContainerScroll: function() {
        let _tab_parent_class = FENCES?.el?.tabContainer;
        let _tab_content_class = '.js-fencing-tab-container-area';
        let _main_parent = $('.js-fencing-tabs-container');

        _main_parent.removeClass('enable-scroll');

        let _main_parent_width = _main_parent.width();
        let _trigger_width = $('.fencing-tabs-area').width();

        $('.fc-content-tab-title').css({ 'border-top-right-radius': '' });

        if (_trigger_width >= _main_parent_width) {
            _main_parent.addClass('enable-scroll');
            HELPER.draggable(_tab_parent_class, _tab_content_class);
            $('.fc-content-tab-title').css({ 'border-top-right-radius': 0 });
        }

        HELPER.moveScrollPosition(_tab_parent_class, $(_tab_parent_class).prop('scrollWidth'));
    },

    //----------------------------------------------------------------------------------

    /**
     * Get the color title and subtitle
     * and assign it to the closest fc-form-field
     * @param {string} _color_el 
     * @param {string} form_field 
     */
    getSelectedColorDetails: function(_color_el, form_field) {

        var getFormFieldKey = form_field.attr('data-key');
        var title = _color_el.attr('data-color-title');
        var subtitle = _color_el.attr('data-color-subtitle');
        var colorCode = _color_el.attr('data-color-code');

        if (getFormFieldKey === "color_options") {
            form_field.attr('data-title', title).attr('data-subtitle', subtitle).attr('data-color-code', colorCode);
        }
    },

    //----------------------------------------------------------------------------------

    zooming: function(zoom) {
        var raked_panel_mt = '20px';
        var $result = $('.fencing-display-result');
        var $items = $('.fencing-panel-items');

        if ($('.raked-panel .fencing-raked-panel').length) {
            var raked_panel_mt = '30px';
        }

        if (zoom == 'reset') {
            step = 1;
            // Ensure drag state does not keep stale cursor/scroll behavior.
            try { cursordown = false; } catch (e) {}
        } else if (step === 1 && (zoom === 'in' || zoom === 'out')) {
            HELPER.captureStep3ResultBaseHeight();
        }

        if (zoom == 'in') {
            if (step < 1) {
                step = step + 0.10;
            } else {
                step = step + 0.10;
            }
        }

        if (zoom == 'out') {
            if (step > fcZoomMinStep) {
                step = step - 0.10;
                if (step < fcZoomMinStep) {
                    step = fcZoomMinStep;
                }
            }
        }

        step = Math.round(step * 100) / 100;

        document.querySelector('.js-fc-zoom-progress').textContent = Math.floor(step * 100) + "%";

        if (step >= 1) {
            $items.css({ 'padding-top': raked_panel_mt, 'zoom': step });
            $result.css({ 'margin-top': 'auto', 'overflow-y': 'auto' });
        } else {
            $items.css({ 'zoom': step });
            $result.css({ 'overflow-y': 'auto' });
        }

        if (step == 1) {
            $result.css({ 'margin-top': raked_panel_mt });
            $items.removeAttr('style');
            $result.css({ 'overflow-y': '' });
            HELPER.clearStep3ResultMinHeight();
        } else {
            HELPER.applyStep3ResultMinHeight();
        }

        if (zoom == 'reset') {
            $('.fencing-display-result, .fc-project-plan-hscroll')
                .removeClass('grabbing is-grabbing')
                .each(function() {
                    this.scrollTop = 0;
                });
            HELPER.clearStep3ResultMinHeight();
        }

        HELPER.toggleZoomResetButton(step);

        window.setTimeout(function() {
            HELPER.centerFencingDisplayHorizontally();
        }, 0);
    },

    //----------------------------------------------------------------------------------

    setSectionURLParam: function() {
        var index = $(FENCES.el.fencingTabSelected).index(),
            sectionNum = index + 1;
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('section', String(sectionNum));
            history.pushState({}, '', url.pathname + url.search + url.hash);
        } catch (e) {
            history.pushState({}, '', '?section=' + sectionNum);
        }
    },

    /**
     * Keep ?fence= in sync with the active fence style — only when fence already
     * exists in the address bar. Never introduces fence if it was absent.
     *
     * @param {string} slug Raw or normalized fence style slug
     */
    syncFenceURLParam: function(slug) {
        if (slug == null || slug === '') {
            return;
        }
        try {
            var url = new URL(window.location.href);
            if (!url.searchParams.has('fence')) {
                return;
            }
            var next =
                typeof normalizeFenceStyleSlug === 'function'
                    ? normalizeFenceStyleSlug(String(slug))
                    : String(slug);
            next = String(next || '').trim();
            if (!next) {
                return;
            }
            if (url.searchParams.get('fence') === next) {
                return;
            }
            url.searchParams.set('fence', next);
            var qs = url.searchParams.toString();
            history.replaceState({}, '', url.pathname + (qs ? '?' + qs : '') + url.hash);
        } catch (e) {
            /* ignore */
        }
    },

    //----------------------------------------------------------------------------------

    isNumber: function(value) {
      return typeof value === 'number' &&  value != Infinity;
    },

    //----------------------------------------------------------------------------------

    isNaNtoZero: function(number) {
        number = Math.round(number);
        number = isNaN(number) || number == Infinity || number <= 0 ? 0 : number;
        return number;
    },

    //----------------------------------------------------------------------------------

    number_format: function(nStr) {
        nStr += '';
        x = nStr.split('.');
        x1 = x[0];
        x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    },

    //----------------------------------------------------------------------------------

    call_fence_func: function(_this, func, a, b, c, d, e, f) {
        try {
            _this[func](a, b, c, d, e, f);
        } catch {
            FENCE[func](a, b, c, d, e, f);
        }
    },

    //----------------------------------------------------------------------------------

    sideOptionValue: function(side, custom_fence, info, has_center_point = false) {

        var center_point = FENCE.get(info.slug, 'post');

        // Left Panel post
        if( side == 'left' ) {
            var edit_left_side = custom_fence.filter(function(item) {
                return item.control_key == 'left_side';
            });

            if( edit_left_side.length ) {
                var left_option = edit_left_side[0]?.settings.filter(function(item) {
                    return item.key == 'left_option';
                });

                var info_left_option = info.settings.left_side.fields.filter(function(item) {
                    return item.slug == 'left_option';
                });

                var info_left_option_val = info_left_option[0]?.options.filter(function(item) {
                    return item.slug == left_option[0]?.val;
                });

                var width = info_left_option_val[0]?.size.width;

                if( has_center_point ) {
                    width = width < 0 ? center_point : width;
                }

                return width;   
            }  
        }

        // Right Panel post
        if( side == 'right' ) {
            var edit_right_side = custom_fence.filter(function(item) {
                return item.control_key == 'right_side';
            });

            if( edit_right_side.length ) {
                var right_option = edit_right_side[0]?.settings.filter(function(item) {
                    return item.key == 'right_option';
                });

                var info_right_option = info.settings.right_side.fields.filter(function(item) {
                    return item.slug == 'right_option';
                });

                var info_right_option_val = info_right_option[0]?.options.filter(function(item) {
                    return item.slug == right_option[0]?.val;
                });

                var width = info_right_option_val[0]?.size.width;

                if( has_center_point ) {
                    width = width < 0 ? center_point : width;
                }
  
                return width;   
 
            } 
        }

        return false;
    },

    //----------------------------------------------------------------------------------
    
    // Function to validate Australian phone numbers
    isValidAustralianNumber: function(phoneNumber) {
        // Remove non-numeric characters
        let cleaned = phoneNumber.replace(/\D/g, '');

        // Ensure the number starts with 0 or +61 (Australia)
        if (!/^(\+61|0)/.test(phoneNumber)) return false;

        // Convert +61 to 0 (standardize)
        if (cleaned.startsWith('61')) cleaned = '0' + cleaned.slice(2);

        // Check length (Mobile: 10 digits, Landline: 8–10 digits)
        if (cleaned.length < 8 || cleaned.length > 10) return false;

        // Block repetitive numbers (1111111111, 0000000000)
        if (/^(.)\1+$/.test(cleaned)) return false;

        // Block sequential numbers (1234567890, 9876543210)
        if (/123456789|987654321/.test(cleaned)) return false;

        return true; // Valid number
    },

    // Function to format Australian numbers correctly
    formatAustralianNumber: function(phoneNumber) {
        // Remove non-numeric characters
        let cleaned = phoneNumber.replace(/\D/g, '');

        // Convert +61 to 0
        if (cleaned.startsWith('61')) cleaned = '0' + cleaned.slice(2);

        // Mobile numbers (0412 345 678 format)
        if (/^04\d{8}$/.test(cleaned)) {
            return cleaned.replace(/^(\d{4})(\d{3})(\d{3})$/, '$1 $2 $3');
        }

        // Landline numbers (02 1234 5678 format)
        if (/^0[2-9]\d{8}$/.test(cleaned)) {
            return cleaned.replace(/^(\d{2})(\d{4})(\d{4})$/, '$1 $2 $3');
        }

        return "Invalid number";
    },

    isGateOnLastPanel: function() {

        // Find the gate element
        var gate = $('.fencing-panel-gate');
        if (gate.length === 0) return false;

        // Find all panel items (excluding gates and raked panels)
        var panelItems = $('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel)');
        if (panelItems.length === 0) return false;

        // Get the last panel-item
        var lastPanel = panelItems.last();

        // The gate is on the last panel if it comes after the last panel-item in the DOM
        // or if the gate is the last .panel-item (if it has both classes)
        return gate.index() > lastPanel.index();

    }

}

/**
 * Keep mobile as a string so JSON/localStorage never drops a leading 0.
 */
function fcNormalizeMobileForStorage(value) {
    if (value == null || value === '') {
        return '';
    }
    return String(value).trim();
}