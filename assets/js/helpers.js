//Global Variable
var HELPER = HELPER || {};
var step = 1;

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
        if (filtered_data) {
            $(filtered_data).each(function(i, item) {
                $(item.settings).each(function(i, item) {
                    $(item.fields).each(function(i, item) {
                        HELPER.get_field_value(item.tag, item.key, item.val);
                    });
                    HELPER.get_field_value(item.tag, item.key, item.val);
                });
            });
        }
    },

    //----------------------------------------------------------------------------------

    /**
     * Load values from local storage
     * @param {string} tag 
     * @param {string} key 
     * @param {string} val 
     */
    get_field_value: function(tag, key, val) {
        if (!val) return;
        if (tag == 'input') {
            $('[name=' + key + ']').val(val);
            $('[name=' + key + ']').closest('.fencing-form-group').find('.fir-info span').text(val);
            $('[name=' + key + ']').prop('checked', true);
        } else if (tag == 'select') {
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
            if (key === "left_raked" || key === "right_raked") {
                if (typeof val !== "undefined" && val) {
                    $('[name=' + key + '] select').val(val);
                } else {
                    $('[name=' + key + '] select').val("none");
                }
            }
        }
    },

    //----------------------------------------------------------------------------------

    /**
     * Hide / Show Zoom Reset Button
     */
    toggleZoomResetButton: function(zoomValue) {
        if (zoomValue == 1) {
            HELPER.hideZoomResetButton();
        } else {
            HELPER.showZoomResetButton();
        }
    },

    //----------------------------------------------------------------------------------

    /**
     * Hide Zoom Reset Button
     */
    hideZoomResetButton: function() {
        $(FENCES.el.zoomReset).hide();
    },

    //----------------------------------------------------------------------------------

    /**
     * Show Zoom Reset Button
     */
    showZoomResetButton: function() {
        $(FENCES.el.zoomReset).removeAttr('style');
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
     * 
     * Hide Delete Button
     * 
     */
    hideDeleteSectionBtn: function() {
        let _remaining_tabs = $(FENCES.el.tabArea).children().length;
        let _delete_btn = $(FENCES.el.jsBtnDeleteFence);
        if (_remaining_tabs == 1) {
            _delete_btn.hide();
        }
        _delete_btn.removeAttr('disabled');

    },

    //----------------------------------------------------------------------------------

    /**
     * Dont delete the first section
     * @returns boolean
     */
    stopSectionDeletion: function() {
        let _remaining_tabs = $(FENCES.el.tabArea).children().length;
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
        //Check if user clicks the calculate button for fence section
        if (custom_fence_tab?.isCalculate) {
            //Set the mm field value
            $('.btn-fc-calculate').prev().find('input').val(custom_fence_tab.calculateValue);
            //Then trigger click into the calculate button to load section 3
            // $('.btn-fc-calculate').trigger('click');
            btnCalculate();

        }
    },

    //----------------------------------------------------------------------------------

    deleteSectionTab: function() {
        let getActiveTab = $(FENCES.el.fencingTabSelected);
        let getActiveTabIndex = getActiveTab.index();
        let getPrevBtn = getActiveTab.prev();
        let getNextBtn = getActiveTab.next();

        getActiveTab.addClass('is-deleting');
        if (getActiveTab.is(':last-child')) {
            getPrevBtn.trigger('click');
        } else {
            getNextBtn.trigger('click');
        }

        $('.is-deleting').remove();

        HELPER.tabContainerScroll();

        // Store section count
        localStorage.setItem('custom_fence-section', $(FENCES.el.fencingTab).length);
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

        if ($('.raked-panel .fencing-raked-panel').length) {
            var raked_panel_mt = '30px';
        }

        if (zoom == 'reset') {
            step = 1;
        }

        if (zoom == 'in') {
            if (step < 1) {
                step = step + 0.10;
            } else {
                step = step + 0.10;
            }
        }

        if (zoom == 'out') {
            if (step <= 1) {
                step = step - 0.10;
            } else {
                step = step - 0.10;
            }
        }

        document.querySelector('.js-fc-zoom-progress').textContent = Math.floor(step * 100) + "%";

        if (step >= 1) {
            $('.fencing-panel-items').css({ 'padding-top': raked_panel_mt, 'zoom': step, 'max-height': '200px' });
            $('.fencing-display-result').css({ 'margin-top': 'auto' });
        } else {
            $('.fencing-panel-items').css({ 'zoom': step, 'max-height': '200px' });
        }

        if (step == 1) {
            $('.fencing-display-result').css({ 'margin-top': raked_panel_mt });
            $('.fencing-panel-items').removeAttr('style');
        }

        HELPER.toggleZoomResetButton(step);
    },

    //----------------------------------------------------------------------------------

    setSectionURLParam: function() {
        var index = $(FENCES.el.fencingTabSelected).index(),
            tab = index + 1;
        history.pushState({}, '', '?section=' + tab);
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
    }

    //----------------------------------------------------------------------------------

}


