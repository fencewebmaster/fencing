var _doc = $(document),
    _win = $(window);

/*
    ----------------------------------------------------------------
    [START] CLICK EVENT
    ----------------------------------------------------------------
*/

_doc.on('click', '.form-control-clear', formControlClear);

function formControlClear() {
    var _this = $(this);
    _this.siblings('.form-control').val('').focus().trigger('keyup');
    _this.remove();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-update', fcBtnUpdate);

function fcBtnUpdate(e) {
    e.preventDefault();
    Planner.planCart();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.js-fc-zoom-reset', jsFcZoomReset);

function jsFcZoomReset() {
    HELPER.zooming('reset');
}

//----------------------------------------------------------------------------------

_doc.on('click', '.popup-alert', popupAlert);

function popupAlert(title, message) {
    var _this = $(this),
        _pa = $('#popup-alert'),
        title = title || _this.attr('data-title'),
        body = body || _this.attr('data-message');


    _pa.modal('show');
    _pa.find('.modal-title').html(title);
    _pa.find('.modal-message').html(message);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.popup-toast', popupToast);

function popupToast(title, message) {
    const _lt = $('#liveToast');
    const toastBootstrap = bootstrap.Toast.getOrCreateInstance(_lt);

    var _this = $(this),
        title = title || _this.attr('data-title'),
        body = body || _this.attr('data-message');

    toastBootstrap.show();
    _lt.find('.toast-title').html(title);
    _lt.find('.toast-body').html(message);
}

//----------------------------------------------------------------------------------

_doc.on('click', '[data-remove]', removePanelItem);

function removePanelItem() {
    var _this = $(this);

    if(_this.attr('data-remove') == 'gate') {
        FENCE.call('move_the_gate', 'delete');
    } else if(_this.attr('data-remove') == 'step_up') {
        removeStepPanels();
        btnCalculate();
    }
    
    $('.modal').modal('hide');

}

//----------------------------------------------------------------------------------

_doc.on('click', '[name="gate_only"]', gateOnly);

function gateOnly() {
    var _this = $('[name="gate_only"]'),
        data = {};

    var fd = getSelectedFenceData(),
        slug = fd.slug,
        tab = fd.tab,
        mbn = fd.mbn;

    // Save custom fields changes
    if( _this.is(':checked') ) {

        updateGateOnly(true);
        removeStepPanels();
        updateOverAllLength();

        FENCE.call('update_gate', 'add');
        FENCE.call('update_custom_fence_gate');

        btnCalculate();
        
    } else {

        updateGateOnly(false);

        FENCE.call('move_the_gate', 'delete');

        $('[name="width"]').val('');
    }

    btnCalculate();

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-style-item', fencingStyleItem);

function fencingStyleItem() {

    var _this = $(this),
        fd = getSelectedFenceData(),
        i = fd.slug,
        tab = fd.tab;

    $('.fencing-style-item').removeClass('fsi-selected');

    _this.addClass('fsi-selected');

    extra_fields();

    FENCE.call('load_fencing_items');

    $('.js-fc-form-step[data-section="2"]').fadeIn(200);

    setTimeout(function() {
        $('.fc-btn-next-step').attr('disabled', 'disabled');
        if ($('.fencing-panel-item:visible').length > 0) {
            $('.fc-btn-next-step').removeAttr('disabled');
        }

        checkGateOnly();
    }, 100);

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-style-item', fencingStyleItem_2);

function fencingStyleItem_2() {
    FENCE.call('update_custom_fence_style_item');
    FENCE.call('set_cutom_fence_data');
}

//----------------------------------------------------------------------------------

_doc.on('click', '#btn-gate', btnGate);

function btnGate() {

    var _this = $(this),
        data = {};

    if (_this.text().toLowerCase().indexOf('edit') > -1) {
        return;
    }

    data.gate = 1;
    updateOverAllLength(data);

    FENCE.call('update_gate', 'add');
    FENCE.call('update_custom_fence_gate');

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-move-post', fcMovePost);

function fcMovePost() {

    var _this = $(this),
        move = _this.data('move'),
        gate = $('.fencing-panel-gate:visible');

    FENCE.call('move_the_gate', move);

    $('[data-section="3"]').scrollTo(100, 57);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-qty-plus', fencingQtyPlus);

function fencingQtyPlus() {

    var _this = $(this),
        input = _this.closest('.fencing-mb-input').find('input'),
        max = parseInt(input.attr('data-max')),
        val = input.val();

    if (!val) {
        input.val(max);
        measurementBoxNumber();

        _this.closest('.fc-input-container').find('[type="button"]').removeAttr('disabled')
            .removeClass('disabled')
            .removeClass('btn-light')
            .addClass('btn-dark');
        
    } else {
        if (val < max) {
            input.val(parseInt(val) + 1);
        }
    }

    $('.error-msg').removeClass('fcim-show').html('');

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-qty-minus', fencingQtyMinus);

function fencingQtyMinus() {

    var _this = $(this),
        input = _this.closest('.fencing-mb-input').find('input'),
        min = parseInt(input.attr('data-min')),
        val = input.val() || min;

    if (!val) {
        input.val(min);
    } else {
        if (val > min) {
            input.val(parseInt(val) - 1);
        }
    }
    $('.error-msg').removeClass('fcim-show').html('');

}

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-get-link', savePlanner);

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-copy-link', btnCopyLink);

function btnCopyLink() {

    var _this = $(this),
        id = _this.attr('data-id')
    text = _this.html(),
        r = document.createRange();

    $('#' + id).show();

    r?.selectNode(document.getElementById(id));

    window.getSelection().removeAllRanges();
    window.getSelection().addRange(r);
    document.execCommand('copy');
    window.getSelection().removeAllRanges();

    // $this.html('COPIED');
    $('#' + id).css({ 'background': '#ffeb3b', 'cursor': 'progress' });

    setTimeout(function() {
        $('#' + id).css({ 'background': '', 'cursor': '' });
    }, 500);

}


//----------------------------------------------------------------------------------


_doc.on('click', '.fc-select-post, .fc-select-item', fcSelectPostItem);

function fcSelectPostItem() {

    var _this = $(this),
        slug = _this.attr('data-slug'),
        getFormField = _this.closest('.fc-form-field');

    _this.closest('.fencing-form-group').find('.fc-select').removeClass('fc-selected');
    _this.addClass('fc-selected');

    getFormField.attr('value', slug);

    HELPER.getSelectedColorDetails(_this, getFormField);

    if ($('.fencing-container').attr('data-key') == 'right_side') {
        $(".fencing-display-result").scrollCenter(".panel-post:last", 300);
    }

    if ($('.fencing-container').attr('data-key') == 'left_side') {
        $(".fencing-display-result").scrollCenter(".panel-post:first", 300);
    }

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-modal-content', function(e) {
    e.stopPropagation();
});

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-tab', fencingTab);

function fencingTab() {

    var _this = $(this);

    $(FENCES.el.fencingContainer).attr('data-key', '');

    $('.fencing-tab').removeClass('fencing-tab-selected');

    _this.addClass('fencing-tab-selected');

    var tab = $('.fencing-tab.fencing-tab-selected').index(),
        modal_key = $('.fencing-container').attr('data-key'),
        custom_fence_tabs = localStorage.getItem('custom_fence-' + tab);

    const custom_fence_tab = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];

    HELPER.resetSectionsBlocks();

    //  if( custom_fence_tab.length > 0 ){

    $('.fencing-style-item[data-slug="' + custom_fence_tab[0]?.style + '"]').addClass('fsi-selected');
    var measurement = custom_fence_tab[0]?.calculateValue ? custom_fence_tab[0]?.calculateValue : FENCES.defaultValues.measurement;
    $('.measurement-box-number').val(measurement);

    FENCE.call('update_custom_fence_tab')

    $('.fsi-selected').trigger('click');

    HELPER.loadStep3(custom_fence_tab[0]);

    //  }

    if (_this.hasClass('fencing-tab')) {
        HELPER.setSectionURLParam();
    }

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-modal .fc-select', fencingModalFcSelect);

function fencingModalFcSelect() {
    FCModal.close();
    $('.fc-btn-active').removeClass('fc-btn-active');
}

//----------------------------------------------------------------------------------

_doc.on('click', '#fc-control-modal', fcControlModal);

function fcControlModal() {
    FCModal.close();
    $('.fc-btn-active').removeClass('fc-btn-active');
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-tab-add', fencingTabAdd);

function fencingTabAdd(e) {
    var _this = $(this);
    
    e?.preventDefault();
    
    FENCE.call('add_new_fence_section');

    HELPER.tabContainerScroll(_this);
    
    $('html').scrollTo(100, 0);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-fence-reset-all', fcFenceResetAll);

function fcFenceResetAll(e) {
    e?.preventDefault();

    var i = $('.fencing-style-item.fsi-selected').attr('data-slug'),
        tab = $('.fencing-tab.fencing-tab-selected').index();

    setTimeout(function() {
        $('.fsi-selected').removeClass('fsi-selected');
        $('.fencing-tab-selected').find('.ftm-measurement').html('');
        $('.fc-tab-title, .fc-tab-subtitle').html('');
        $('.measurement-box-number').val('');

        localStorage.removeItem('custom_fence-' + tab);
        localStorage.removeItem('custom_fence-' + tab + '-' + i);

        $('.js-fc-form-step').fadeOut('fast');
        $('.fc-fence-reset-all').hide();

    });

}

//----------------------------------------------------------------------------------

_doc.on('click', '[data-action="scroll"]', scrollToTarget);

function scrollToTarget() {
    var _this = $(this),
        target = _this.attr('data-target'),
        offset = _this.attr('data-offset');

    $(target).scrollTo(100, offset);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-fence-reset', fcFenceReset);

function fcFenceReset(e) {
    e?.preventDefault();

    var i = $('.fencing-style-item.fsi-selected').attr('data-slug'),
        tab = $('.fencing-tab.fencing-tab-selected').index();

    localStorage.removeItem('custom_fence-' + tab + '-' + i);

    FENCE.call('move_the_gate', 'delete');
    FENCE.call('update_custom_fence_tab')
    FENCE.call('load_fencing_items');

}

//----------------------------------------------------------------------------------

_doc.on('click', '.js-btn-delete-fence', jsBtnDeleteFence);

function jsBtnDeleteFence(e) {
    e?.preventDefault();

    var _this = $(this);

    _this.attr('disabled', 'disabled');

    let getActiveTab = $('.fencing-tab-selected');
    let getActiveTabIndex = getActiveTab.index();

    // Call delete section functions in order
    HELPER.stopSectionDeletion();
    deleteLocalStorageEntry();
    HELPER.deleteSectionTab();
    HELPER.refreshSectionTabIndex();
    refreshLocalStorage(getActiveTabIndex, 'custom_fence');
    refreshLocalStorage(getActiveTabIndex, 'cart_items');
    HELPER.hideDeleteSectionBtn();

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-btn-modal', fencingBtnModal);

function fencingBtnModal(event) {

    var _this = $(this);

    if (!_this.hasClass('fencing-btn-modal'))
        return false;

    let modal = {
        el: '',
        content: '.fencing-modal-content'
    };

    //Button Data Information
    var target = _this.data('target'),
        key = _this.data('key');

    var fd = getSelectedFenceData();

    var i = fd.slug,
        tab = fd.tab,
        info = fd.info,
        data = fd.data;

    modal.el = $(target);
    modal.content = modal.el.find(modal.content);
    FCModal.open(target);

    _this.addClass('fc-btn-active');

    if (typeof key !== "undefined") {
        modal.el.find(".fencing-modal-notes").html('');
        modal.content.html('');
    }

    FENCES.setActiveSetting(key);

    var fields = data?.settings[key]?.fields;

    if (typeof fields !== "undefined") {

        if (fields.length > 1) {
            modal.content.addClass('has-multiple-areas');
        } else {
            modal.content.removeClass('has-multiple-areas');
        }

        $.each(fields, function(k, v) {

            let marker = '';

            if (v.marker !== undefined && v.marker !== "") {
                marker = `<span class="fencing-modal-title__marker">${v.marker})</span> `;
            }

            var tpl = $('script[data-type="' + v.type + '"]').text()
                .replace(/{{field_title}}/gi, v.title)
                .replace(/{{marker}}/gi, marker)
                .replace(/{{title}}/gi, v.label)
                .replace(/{{image}}/gi, v.image)
                .replace(/{{default}}/gi, v.default)
                .replace(/{{unit}}/gi, v.unit)
                .replace(/{{field_name}}/gi, v.slug)
                .replace(/{{type}}/gi, v.type)
                .replace(/{{sub_default}}/gi, v?.weight?.default)
                .replace(/{{sub_unit}}/gi, v?.weight?.unit)
                .split(/\$\{(.+?)\}/g);

            modal.content.append(tpl);


            if (v.type == 'dropdown_option') {

                $(v.options).each(function(i, o) {
                    $('[name="' + v.slug + '"] select').append($('<option>', { value: o.slug, text: o.title }));
                    $('[name="' + v.slug + '"] select').attr('data-key', v.key);
                });

            }

            if (v.type == 'range_option') {

                const Item = ({ image, title, slug, key }) => `
                <div class="col-sm-4 col-6 mb-2 px-1 text-center">
                    <div class="fc-select-post fc-select" data-key="${key}" data-slug="${slug}">    
                        <img src="${image}">
                    </div>
                    <p>${title}</p>
                </div>`;

                modal.el.find('[data-field="range_option"] .row').html(v.options.map(Item).join(''));

            }

            if (v.type == 'text_option') {

                const Item = ({ title, slug, desc }) => `
                <div class="col-sm-6 col-12 mb-2 text-center px-1">
                    <div class="fc-select-post fc-select" data-key="${key}" data-slug="${slug}">    
                        <p>${title}<strong>${desc ?? ''}</strong></p>
                    </div>
                </div>`;

                modal.el.find('[data-field="text_option"] .row').html(v.options.map(Item).join(''));
            }

            if (v.type == 'image_option') {

                const Item = ({ image, title, extra, slug, key }) => `
                <div class="col-sm-4 col-6 mb-2 text-center px-1">
                    <div class="fc-select-post fc-select" data-key="${key}" data-slug="${slug}">
                        <img src="${image}" class="fc-fullwidth">        
                    </div>
                    <p>${title}</p>
                    <p>${extra}</p>
                </div>`;

                modal.el.find('[data-field="image_option"] .row').html(v.options.map(Item).join(''));
            }


            addNotesOrInfo(modal.el.find('[data-field="' + v.type + '"] .fencing-modal-notes'), v);

            if (v.class) {
                $('.fencing-modal-area[data-field="' + v.type + '"]').addClass(v.class)
            }

            // GET/SET DEFAULT VALUE
            var default_value = v.options?.filter(function(item) {
                return item.default == true;
            });

            if (default_value) {
                var opt = default_value[0],
                    tag = $('.fc-form-field').get(0).tagName.toLowerCase();


                // Set overall value for side posts
                if (v?.slug == 'post_option' && $.inArray(key, ['left_side', 'right_side']) !== -1) {

                    var post_options_filtered_data = info.filter(function(item) {
                        return item.control_key === "post_options";
                    });

                    var _postValue = post_options_filtered_data[0]?.settings[0]?.val || opt?.slug;

                    HELPER.get_field_value(tag, v?.slug, _postValue);

                } else {
                    HELPER.get_field_value(tag, v?.slug, opt?.slug);
                }


            }


        });



        // Disable gate controls
        if( $('.fencing-panel-item:not(.fencing-raked-panel)').length == 1 ) {
            $('.fc-move-post:not([data-move="delete"])').addClass('disabled');
        }

        // Custom gate
        if (data?.settings[key]?.custom) {

            default_panel = data.settings.panel_options.fields[0].options.filter(function(item) {
                return item.default;
            });

            selected_panel = get_field_options(info, data, 'panel_options');

            active_panel = selected_panel[0]?.slug ? selected_panel[0].slug : default_panel[0].slug;

            panel_options_data = get_field_by_slug(data.settings.panel_options.fields[0].options, active_panel);

            var split_size = panel_options_data.slug.split('+')[1],
                fence_height = $('[name="fence_height"]').val(),
                panel_opts = panel_options_data.size.width_based_height,
                maxWidth = fence_height ? parseInt(panel_opts[fence_height]) - FENCE.get(i, 'post') : split_size,
                maxLength = maxWidth.toString().length;

            var tpl = $('script[data-type="custom-gate"]').text()
                .replace(/{{maxWidth}}/gi, maxWidth)
                .replace(/{{maxLength}}/gi, maxLength);

            $('.custom-gate').html('').html(tpl);

        }

    }

    //Get data based on key
    var filtered_data = info.filter(function(item) {
        return item.control_key == key;
    });

    removeDuplicateCloseBtn();

    HELPER.set_field_value(filtered_data);

    // Default load of STD gate
    if (!$('[name="width"]').val() || $('[name="use_std"]').is(':checked')) {
        
        var gateWidth = data?.settings?.gate?.size.width;

        $('[name="use_std"]').prop('checked', true);
        $('[name="width"]').val(gateWidth);

        FENCE.call('disabledCustomGate');
        FENCE.call('calculateCustomGate');

        // calculateCustomGate();
    }

}

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-fc-calculate', btnCalculate);

function btnCalculate() {

    if( $('.fencing-panel-gate:visible').length || $('.raked-panel-container:visible').length ) {
        updateOverAllLength();
    }

    var box = $('.measurement-box-number'),
        last = box.attr('data-last'),
        length = parseInt(box.val()),
        tab = $('.fencing-tab.fencing-tab-selected').index(),
        custom_fence_tabs = localStorage.getItem('custom_fence-' + tab),
        custom_fence_tab = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];

    box.attr('data-last', box.val());

    if (!$('.measurement-box-number').val()) {

        $('.measurement-box-number').closest('.fc-input-container')
            .find('.fc-input-msg')
            .addClass('fcim-show')
            .html('Please enter the amount');
        return;
    }

    FENCE.call('update_custom_fence_tab')
    FENCE.call('load_fencing_items');

    $('.js-fc-form-step[data-section="3"]').fadeIn(200);
    $('.fencing-tabs-container').show();

    setTimeout(function() {

/*        if ($('.fencing-panel-gate:visible').length) {
            FENCE.call('update_gate', 'add');
        }*/

        $('.fc-btn-next-step').attr('disabled', 'disabled');

        //@TODO - checkback to refactor
        // if( $('.fencing-panel-item:visible').length > 0 ) {
        //     $('.fc-btn-next-step').removeAttr('disabled');
        // }
        $('.fc-btn-next-step').removeAttr('disabled');

        if ($(".fencing-panel-spacing-number:contains('undefined')").length) {
            box.val(last);
            $('.btn-fc-calculate').one();

            var alert = [];

            if ($('.fencing-panel-gate:visible').length) {
                alert.push('gate');
            }
            if ($('.fencing-raked-panel').length) {
                alert.push('step up');
            }

            if ($('.fencing-raked-panel').length || $('.fencing-panel-gate:visible').length) {
                var alert_msg = 'Invalid, remove ' + alert.join(' or ') + ' first';

                $('.measurement-box-number').closest('.fc-input-container').find('.fc-input-msg').addClass('fcim-show').html(alert_msg);
            }

            setTimeout(function() {
                $('.fc-input-msg').removeClass('fcim-show').html('');
            }, 5000);
        }

    });

    // Save custom fields changes
    custom_fence_tab[0].fields = $('[data-action="change"] .form-control').serializeArray();
    custom_fence_tab[0].isCalculate = 1;
    custom_fence_tab[0].calculateValue = length;

    localStorage.setItem('custom_fence-' + tab, JSON.stringify(custom_fence_tab));

}

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-fc-calculate', btnCalculate_v2);

function btnCalculate_v2() {
    $('[data-section="3"]:visible').scrollTo(100, 57);
}


//----------------------------------------------------------------------------------

/* Input Range */
_doc.on('click', '.fir-minus', firMinus);

function firMinus() {
    HELPER.zoom(this, "out");
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fir-plus', firPlus);

function firPlus() {
    HELPER.zoom(this, "in");
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-zoom-fence', fcZoomFence);

function fcZoomFence(e) {
    e?.preventDefault();
    var _this = $(this),
        zoom = _this.attr('data-zoom');
    HELPER.zooming(zoom);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-input-group button', fcInputGroup_button);

function fcInputGroup_button() {

    updateOverAllLength();

    FENCE.call('calculateCustomGate');
    FCModal.close();
    checkGateOnly();

    btnCalculate();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-select-post', fcSelectPost);

function fcSelectPost() {

    var _this = $(this),
        modal_key = $('.fencing-container').attr('data-key'),
        fc_form_field = _this.closest(".fc-form-field");

    if (_this.attr('data-key') && _this.attr('data-key') !== undefined) {
        modal_key = _this.attr('data-key');
    }

    FENCE.call('update_custom_fence', modal_key);
    FENCE.call('updateOverallPosts');

    $('[data-section="3"]').scrollTo(100, 57);

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-select-color', fcSelectColor);

function fcSelectColor() {
    update_color_options();
}

//----------------------------------------------------------------------------------

_doc.on('click', '#submit-modal .js-fencing-modal-close', submitModal_jsFencingModalClose);

function submitModal_jsFencingModalClose() {
    // Push param in URL tab={tab}
    if (tab = HELPER.getSearchParams('tab')) {
        history.pushState({}, '', `?tab=${tab}`);
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-form-step', fcBtnFormStep);

function fcBtnFormStep() {

    var _this = $(this),
        move = _this.attr('data-move'),
        tab = HELPER.getSearchParams('tab');

    if (!_this.hasClass('fc-btn-next')) {

        $('.fc-form-plan').hide();
        $('[data-formtab="' + move + '"]').show();
        history.pushState({}, '', `?tab=${tab}&form=${move}`);
    }

    _this.closest('form').find('[type="submit"]')
        .addClass('disabled')
        .attr('disabled', 'disabled');

    if ($('.fc-form-check-img input:checked').length) {
        _this.closest('form').find('[type="submit"]')
            .removeClass('disabled')
            .removeAttr('disabled');
    }

}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-next', fcBtnNext);

function fcBtnNext() {
    var _this = $(this);
    _this.closest('form').submit();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-step', fcBtnStep);

function fcBtnStep(e) {
    e?.preventDefault();

    var _this = $(this);
    var tab = _this.attr('tab'),
        section = _this.attr('data-section'),
        offset = _this.attr('data-offset'),
        move = _this.attr('data-move');

    $('.fc-section-step').hide();
    $('[data-tab="' + move + '"]').show();

    $('.fencing-container').removeClass('w-side-section');
    if (move < 2) {
        $('.fencing-container').addClass('w-side-section');
    }

    // Push param in URL tab={tab}
    history.pushState({}, '', `?tab=${move}`);

    HELPER.tabContainerScroll();

    if (move == 2) {
        loadColorOptions();
    }

    if (move == 1) {
        $('.fencing-tab.fencing-tab-selected:visible').trigger('click');
    }

    $('html').scrollTo(100, 0);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-form-check input', fcFormCheck_input);

function fcFormCheck_input() {

    var _this = $(this);

    var type = _this.attr('type');

    if (type == 'checkbox') {
        $('.fc-form-check-empty').removeClass('fc-selected');
        _this.closest('.row').find('input[type="radio"]').prop('checked', false);
    } else {
        $('.fc-form-check-img').removeClass('fc-selected');
        _this.closest('.row').find('input[type="checkbox"]').prop('checked', false);
    }

    var check = _this.is(':checked');

    _this.closest('.fc-form-check-img').removeClass('fc-selected');

    if (check) {
        _this.closest('.fc-form-check-img').addClass('fc-selected');
    }

    _this.closest('form').find('[type="submit"]')
        .addClass('disabled')
        .attr('disabled', 'disabled');


    if ($('.fc-form-check-img input:checked').length) {
        _this.closest('form').find('[type="submit"]')
            .removeClass('disabled')
            .removeAttr('disabled');
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '[name="color_options"]', color_options);

function color_options() {
    $('.fc-btn-create-plan').attr('disabled');
    if ($('.fc-color-options .fc-selected').length == $('.fc-color-options').length) {
        $('.fc-btn-create-plan').removeAttr('disabled');
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-create-plan', fcBtnCreatePlan);

function fcBtnCreatePlan() {
    // Push param in URL tab={tab}
    history.pushState({}, '', `?tab=2&form=1`);

    // Show form modal
    $('.fc-form-plan').hide();

    // Show the first step of the form
    $('[data-formtab="1"]').show();
}

//----------------------------------------------------------------------------------

_doc.on('click', '[name="use_std"]', use_std);

function use_std(e) {

    $('[name="width"]').removeAttr('readonly').removeClass('disabled text-muted').val('').focus();
    $('.fencing-qty-btn').removeClass('disabled');
    $('.custom-gate button').removeAttr('disabled').removeClass('disabled btn-light').addClass('btn-dark');

    // 975 default gate width

    if ($('[name="use_std"]').is(':checked')) {
    
        updateOverAllLength();

        FENCE.call('calculateCustomGate');
        FENCE.call('disabledCustomGate');

        FCModal.close();
    }

}

/* ----------------------------------------------------------------
    [END] CLICK EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] KEYPRESS EVENT
    ---------------------------------------------------------------- */

_doc.on('keypress', '.numeric', numeric);

function numeric(event) {

    var _this = $(this);

    if (event.which != 13 && event.which != 8) {

        if (event.which < 46 || event.which >= 58 || event.which == 47) {
            event.preventDefault();
        }

        if (event.which == 46 && _this.val().indexOf('.') != -1) {
            event.preventDefault();
        }

    }

}

//----------------------------------------------------------------------------------

_doc.on('keypress', '.measurement-box-number', measurementBoxNumber_kp);

function measurementBoxNumber_kp(e) {
    if (event.which == 13) {
        btnCalculate();
        e?.preventDefault();

        $('[data-section="3"]:visible').scrollTo(100, 57);
    }
}

//----------------------------------------------------------------------------------

_doc.on('keypress', '[input-type="number"]', inputType_number_2);

function inputType_number_2(e) {
    if (event.which == 13) {
        var _this = $(this);
        _this.closest('.fc-input-container').find('[type="button"]').trigger('click');
        e?.preventDefault();
    }
}

//----------------------------------------------------------------------------------

_doc.on('keypress', '.no-enter', noEnter);

function noEnter(e) {
    if (e.keyCode == 13) {
        event.preventDefault();
    }
}

//----------------------------------------------------------------------------------

_doc.on('keypress', '.no-space', noSpace);

function noSpace(e) {
    if (e.keyCode == 32) {
        event.preventDefault();
    }
}

/* ----------------------------------------------------------------
    [END] KEYPRESS EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] CHANGE EVENT
    ---------------------------------------------------------------- */

_doc.on('change', '.fc-select-option', fcSelectOption);

function fcSelectOption() {

    var _this = $(this),
        fd = getSelectedFenceData(),
        tabInfo = fd.tabInfo,
        info = fd.info,
        value = _this.val();

    _this.parent().attr('value', value);

    var modal_key = $('.fencing-container').attr('data-key');

    var leftRakedBefore = $('.left_raked-panel .fencing-panel-item-size').length;
    var rightRakedBefore = $('.right_raked-panel .fencing-panel-item-size').length;

    if (_this.attr('data-key') && _this.attr('data-key') !== undefined) {
        modal_key = _this.attr('data-key');
    }

    FENCE.call('update_custom_fence', modal_key);

    var rakedSide = _this.closest('.fc-form-field').attr('name');

    if (rakedSide == 'right_raked') {
        $(".fencing-display-result").scrollCenter(".panel-post:last", 300);
    }

    if (rakedSide == 'left_raked') {
        $(".fencing-display-result").scrollCenter(".panel-post:first", 300);
    }

    if (_this.parents('.js-fencing-modal').length) {
        FCModal.close();
    }

}

//----------------------------------------------------------------------------------

_doc.on('change', '.fc-select-option', fcSelectOption_v2);

function fcSelectOption_v2() {

    updateGateOnly(false);
    checkGateOnly();
    updateOverAllLength();

    var side = ['left_raked', 'right_raked'];

    $('.raked-panel').html('');

    FENCE.call('update_raked_panels', side);

    btnCalculate();

    $('[data-section="3"]').scrollTo(100, 57);

}

//----------------------------------------------------------------------------------


/* ----------------------------------------------------------------
    [END] CHANGE EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] KEYDOWN EVENT
    ---------------------------------------------------------------- */

_doc.on('keydown', function(e) {
    var code = e.keyCode || e.which;
    if (code == 27) {
        FCModal.close();
        $('.fc-btn-active').removeClass('fc-btn-active');
    }
});

/* ----------------------------------------------------------------
    [END] KEYDOWN EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] KEYUP EVENT
    ---------------------------------------------------------------- */

_doc.on('keyup', '.has-clear .form-control', hasClear_formControl);

function hasClear_formControl() {

    var _this = $(this),
        clear = `<i class="fa-solid fa-circle-xmark form-control-clear"></i>`;

    _this.siblings('.form-control-clear').remove();

    if (_this.val()) _this.after(clear);

}

//----------------------------------------------------------------------------------

_doc.on('keyup', '[input-type="number"]', inputType_number);

function inputType_number() {

    var _this = $(this);

    var min = parseInt(_this.attr('data-min')),
        max = parseInt(_this.attr('data-max'));

    _this.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');
    _this.closest('.fc-input-container').find('[type="button"]').removeAttr('disabled')
        .removeClass('disabled')
        .removeClass('btn-light')
        .addClass('btn-dark');

    if (_this.val() < min || _this.val() > max) {

        if (_this.val() < min) {
            var alert = ' Invalid ' + min + 'mm Min';
        }

        if (_this.val() > max) {
            var alert = ' Invalid ' + max + 'mm max';
        }

        if (_this.val() == '') {
            var alert = 'Please enter the amount';
        }

        _this.closest('.fc-input-container').find('.fc-input-msg').addClass('fcim-show').html(alert);

        _this.closest('.fc-input-container').find('[type="button"]').attr('disabled', 'disabled')
            .removeClass('btn-dark')
            .addClass('btn-light disabled');

        if (alert.length) {
            _this.closest('.fc-input-container')
                .find('.fc-input-msg')
                .addClass('fcim-show')
                .html(alert);
        }

    }

}

//----------------------------------------------------------------------------------

_doc.on('keyup', '.measurement-box-number', measurementBoxNumber);

function measurementBoxNumber() {

    var _this = $('.measurement-box-number');

    _this.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');

    var min = parseInt(_this.attr('data-min')),
        max = parseInt(_this.attr('data-max'));

    $('.btn-fc-calculate').removeAttr('disabled').removeClass('btn-light disabled').addClass('btn-dark');

    if (_this.val() < min || _this.val() > max) {

        if (_this.val() < min) {
            var alert = ' Invalid ' + min + 'mm Min';
        }

        if (_this.val() > max) {
            var alert = ' Invalid ' + max + 'mm max';
        }

        if (_this.val() == '') {
            var alert = 'Please enter the amount';
        }

        _this.closest('.fc-input-container').find('.fc-input-msg').addClass('fcim-show').html(alert);

        $('.btn-fc-calculate').attr('disabled', 'disabled').removeClass('btn-dark').addClass('btn-light disabled');
        // 2nd validation
        /*
        var alert = [];

        if ($('.fencing-panel-gate:visible').length) {
            alert.push('gate');
        }
        if ($('.fencing-raked-panel').length) {
            alert.push('step up');
        }

        var alert_msg = 'Invalid, remove ' + alert.join(' or ') + ' first';
        */

        if (alert.length) {
            $('.measurement-box-number').closest('.fc-input-container')
                .find('.fc-input-msg')
                .addClass('fcim-show')
                .html(alert);
        }

    }


}

/* ----------------------------------------------------------------
    [END] KEYUP EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] CHANGE EVENT
    ---------------------------------------------------------------- */

_doc.on('input change', '.fencing-input-range input', fencingInputRange_input);

function fencingInputRange_input() {

    var _this = $(this);

    _this.closest('.fencing-input-range').find('.fir-info span').text($(event.currentTarget).val());

    _this.closest('.fencing-input-range').find('.fir-info-sub span').text(72 + 1.8);

}

//----------------------------------------------------------------------------------

_doc.on('change', '.fc-form-field select', fcFormField_select);

function fcFormField_select() {

    var modal_key = $('.fencing-container').attr('data-key');

    FENCE.call('update_custom_fence', modal_key);

}

/* ----------------------------------------------------------------
    [END] CHANGE EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] RESIZE EVENT
    ---------------------------------------------------------------- */

// Initiate tab container scroll on window resize
_win.on('resize', function() {
    HELPER.tabContainerScroll();
});

//----------------------------------------------------------------------------------


_win.on('scroll resize', function() {

    var spy = $('[data-spy="scroll"]'),
        target = spy.attr('data-target'),
        offset = spy.attr('data-offset'),
        screen = parseInt(spy.attr('data-screen')),
        width = $(target).width();

    spy.removeClass('sticky-roll').css({ 'width': '' });

    if (target && $('body').innerWidth() >= screen) {
        if ($(window).scrollTop() > ($(target).offset().top)) {
            spy.addClass('sticky-roll').css({ 'width': width, 'top': offset });
        }
    }

});


/* ----------------------------------------------------------------
    [END] RESIZE EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] MOUSEDOWN EVENT
    ---------------------------------------------------------------- */

// https://stackoverflow.com/questions/19743228/scroll-the-page-on-drag-with-jquery
var cursordown = false;
var cursorypos = 0;
var cursorxpos = 0;
$('.fencing-display-result').mousedown(function(e) {
    cursordown = true;
    cursorxpos = $(this).scrollLeft() + e.clientX;
    cursorypos = $(this).scrollTop() + e.clientY;
}).mousemove(function(e) {
    if (!cursordown) return;
    try { $(this).scrollLeft(cursorxpos - e.clientX); } catch (e) {}
    try { $(this).scrollTop(cursorypos - e.clientY); } catch (e) {}
}).mouseup(end = function(e) {
    cursordown = false;
}).mouseleave(end);

/* ----------------------------------------------------------------
    [END] MOUSEDOWN EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] MOUSEUP / TOUCH[END] EVENT
    ---------------------------------------------------------------- */

_doc.on('mouseup touchend', '.fencing-display-result', mu_fencingDisplayResult);

function mu_fencingDisplayResult(e) {

    var _this = $(this);

    _this.removeClass('grabbing');

    setTimeout(function() {
        _this.removeClass('grabbing is-grabbing');
    }, 200);

}

//----------------------------------------------------------------------------------

_doc.on('mousedown touchstart', '.fencing-display-result', md_fencingDisplayResult);

function md_fencingDisplayResult() {

    var _this = $(this);

    _this.addClass('is-grabbing');

    setTimeout(function() {
        if (!$('.fencing-modal').is(':visible')) {
            _this.addClass('grabbing').removeClass('is-grabbing');
        }
    }, 200);

}

/* ----------------------------------------------------------------
    [END] MOUSEUP / TOUCH[END] EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] TIMEOUT
    ---------------------------------------------------------------- */

setTimeout(function() {

    loadClearForm();

}, 200);

//----------------------------------------------------------------------------------



/* ----------------------------------------------------------------
    [START] VALIDATE
    ---------------------------------------------------------------- */
// https://jqueryvalidation.org/validate/

$("#fc-planning-form").validate({
    rules: {
        email: {
            required: true,
            email: true
        },
    },
    messages: {
        timeframe: "Please select an option.",
        installer: "Please select an option.",
    },
    submitHandler: function(form) {

        var tab = $('.fc-form-plan:visible').attr('data-formtab'),
            move = $('.fc-form-plan:visible').find('.fc-btn-next').attr('data-move');

        history.pushState({}, '', `?tab=${HELPER.getSearchParams('tab')}&form=${move}`);

        if (tab == 4) {

            FCModal.close('#submit-modal');
            $('.fc-loader-overlay').show();

            res = submit_fence_planner('new');

        } else {
            $('.fc-form-plan').hide();
            $('[data-formtab="' + move + '"]').show();
        }

    }
});

/* ----------------------------------------------------------------
    [END] [START] VALIDATE
    ---------------------------------------------------------------- */