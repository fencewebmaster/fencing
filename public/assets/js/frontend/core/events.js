var _doc = $(document),
    _win = $(window);

/** When true, `fcSelectOption` will not auto-close `#fc-control-modal` (Step 2 Gate ONLY programmatic gate sync). */
var fcSuppressControlModalCloseOnFcSelectChange = false;

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

_doc.on('click', '#fc-incomplete-sections-modal [data-action="complete-sections"]', fcIncompleteSectionsModal_completeSections);

function fcIncompleteSectionsModal_completeSections(e) {
    e?.preventDefault();
    var el = document.getElementById('fc-incomplete-sections-modal');
    if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var m = bootstrap.Modal.getInstance(el);
        if (m) {
            m.hide();
        }
    }
    if (typeof fcGoPlannerCompleteIncompleteSections === 'function') {
        fcGoPlannerCompleteIncompleteSections();
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '#fc-incomplete-sections-modal [data-action="proceed-anyway"]', fcIncompleteSectionsModal_proceedAnyway);

function fcIncompleteSectionsModal_proceedAnyway(e) {
    e?.preventDefault();
    var el = document.getElementById('fc-incomplete-sections-modal');
    if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var m = bootstrap.Modal.getInstance(el);
        if (m) {
            m.hide();
        }
    }
    $('.fc-loader-overlay').show();
    if (typeof fcSetPlannerSubmitLoaderMessage === 'function') {
        fcSetPlannerSubmitLoaderMessage();
    }
    if (typeof submit_fence_planner === 'function') {
        submit_fence_planner(fcIncompleteSectionsPendingStatus, { forceProceed: true });
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.js-fc-zoom-reset', jsFcZoomReset);

function jsFcZoomReset() {
    HELPER.zooming('reset');
}

//----------------------------------------------------------------------------------

_doc.on('click', '.popup-alert', popupAlert);

function popupAlert(title, message) {
    var _this = $(this);
    var $pa = $('#popup-alert');
    var modalTitle = title || _this.attr('data-title') || 'Notice';
    var modalMessage = message || _this.attr('data-message') || '';

    $pa.find('.modal-title').html(modalTitle);
    $pa.find('.modal-message').html(modalMessage);
    $pa.find('.fencing-measurement-box').addClass('d-none');

    var el = document.getElementById('popup-alert');
    if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    } else {
        $pa.modal('show');
    }
}

//----------------------------------------------------------------------------------

_doc.on('input', '#load-quote [name="qid"]', function() {
    if (typeof fcClearLoadQuoteModalError === 'function') {
        fcClearLoadQuoteModalError();
    }
});

_doc.on('show.bs.modal', '#load-quote', function() {
    if (typeof fc_load_quote_failed !== 'undefined' && fc_load_quote_failed) {
        return;
    }
    if (typeof fcClearLoadQuoteModalError === 'function') {
        fcClearLoadQuoteModalError();
    }
});

//----------------------------------------------------------------------------------

// Shared behaviour for every .fc-modal dialog. Two jobs: put focus on the marked control so
// Enter cannot land on whatever the browser happened to focus (on Clear All that means the
// destructive button), and restore data-fc-role, because Bootstrap stamps role="dialog" over
// the markup's alertdialog on show and strips the attribute again on hide.
_doc.on('shown.bs.modal', '.fc-modal', fcModalShown);

function fcModalShown() {
    var $modal = $(this);
    var role = $modal.attr('data-fc-role');

    if (role) {
        $modal.attr('role', role);
    }

    $modal.find('[data-fc-autofocus]').first().trigger('focus');
}

//----------------------------------------------------------------------------------

// Name the sections that still need work. "One or more fence sections are incomplete" left
// people opening every tab to find them.
_doc.on('show.bs.modal', '#fc-incomplete-sections-modal', fcIncompleteSectionsModalShow);

function fcIncompleteSectionsModalShow() {
    var $modal = $(this);
    var $list = $modal.find('.js-fc-incomplete-sections-list');
    var sections = [];

    if (typeof fcFenceSectionIncompleteFromStorage === 'function') {
        var count = parseInt(localStorage.getItem('custom_fence-section'), 10);
        if (!Number.isFinite(count) || count < 1) {
            count = 0;
        }
        for (var i = 0; i < count; i++) {
            if (fcFenceSectionIncompleteFromStorage(i)) {
                sections.push(i + 1);
            }
        }
    }

    $list.empty();
    $.each(sections, function(_, n) {
        $list.append(
            $('<li>').append(
                $('<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>'),
                $('<span>').text('Section ' + n)
            )
        );
    });

    // The check that opened this modal (fcPlannerTabRowHasPlanData) is not the one used here,
    // so an empty list is possible — keep the generic sentence rather than show an empty box.
    var named = sections.length > 0;
    $list.toggleClass('d-none', !named);
    $modal.find('.js-fc-incomplete-sections-lead').toggleClass('d-none', !named);
    $modal.find('.js-fc-incomplete-sections-fallback').toggleClass('d-none', named);
}

//----------------------------------------------------------------------------------

// Reopening after a back-navigation restores the DOM mid-confirm; reset the button so it is
// not stuck on the spinner.
_doc.on('show.bs.modal', '#clear-all-data', fcClearAllModalShow);

function fcClearAllModalShow() {
    var $confirm = $('#clear-all-data .js-fc-clear-all-confirm');

    $confirm.removeClass('is-busy').removeAttr('aria-disabled');
    $confirm.find('.js-fc-clear-all-confirm-icon').attr('class', 'fa-solid fa-trash-can me-2 js-fc-clear-all-confirm-icon');
    $confirm.find('.js-fc-clear-all-confirm-label').text('Yes, clear all');
}

// Confirm is a link, so an impatient second click fires a second navigation before the first
// unloads. Lock it and show progress instead.
_doc.on('click', '#clear-all-data .js-fc-clear-all-confirm', fcClearAllModalConfirm);

function fcClearAllModalConfirm(e) {
    var $confirm = $(this);

    if ($confirm.hasClass('is-busy')) {
        e.preventDefault();
        return;
    }

    $confirm.addClass('is-busy').attr('aria-disabled', 'true');
    $confirm.find('.js-fc-clear-all-confirm-icon').attr('class', 'fa-solid fa-spinner fa-spin me-2 js-fc-clear-all-confirm-icon');
    $confirm.find('.js-fc-clear-all-confirm-label').text('Clearing...');
}

//----------------------------------------------------------------------------------

_doc.on('click', '.popup-toast', popupToast);

function popupToast(title, message, code) {
    const _lt = $('#liveToast');
    const toastBootstrap = bootstrap.Toast.getOrCreateInstance(_lt);

    var _this = $(this);

    title = title || _this.attr('data-title');
    message = message || _this.attr('data-message');

    // Fill before show(): the toast is aria-live, and showing the empty shell first meant
    // screen readers announced nothing.
    _lt.find('.toast-title').html(title);
    _lt.find('.toast-body').html(message);

    // `code` is the internal rule that fired (STD, R+G, SU, …) — a developer breadcrumb a
    // customer can do nothing with, so it only renders in debug mode.
    var $code = _lt.find('.toast-code');
    if (window.FC_DEBUG) {
        $code.html(code).removeClass('d-none');
    } else {
        $code.empty().addClass('d-none');
    }

    toastBootstrap.show();
}

//----------------------------------------------------------------------------------

_doc.on('click', '[data-remove]', removePanelItem);

function removePanelItem() {
    var _this = $(this);

    if(_this.attr('data-remove') == 'gate') {
        try {
            var fdRm = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
            if (fdRm && typeof fcRemoveGateSegmentFromStorage === 'function') {
                fcRemoveGateSegmentFromStorage(fdRm.tab, fdRm.slug);
            }
        } catch (eRmSeg) {}
        FENCE.call('move_the_gate', 'delete');
    } else if(_this.attr('data-remove') == 'step_up') {
        removeStepPanels();
        btnCalculate();
    }
    
    $('.modal').modal('hide');
}

//----------------------------------------------------------------------------------

/**
 * Show Step 2 "Gate ONLY" when `fc_data` defines an enabled gate (`settings.gate` present and not `disabled`).
 */
function fcSyncStep2GateOnlyVisibility() {
    var $wrap = $('.fc-step2-gate-only');
    if (!$wrap.length) {
        return;
    }
    var slugRaw = $('.fencing-style-item.fsi-selected').attr('data-slug');
    if (!slugRaw) {
        $wrap.addClass('d-none');
        return;
    }
    var slugNorm =
        typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(slugRaw)
            : String(slugRaw);
    var meta = typeof fc_data !== 'undefined' && fc_data ? fc_data[slugNorm] : null;
    var gateSettings = meta && meta.settings && meta.settings.gate;
    var gateDisabled =
        !!(
            gateSettings &&
            (gateSettings.disabled === true ||
                gateSettings.disabled === 1 ||
                String(gateSettings.disabled).toLowerCase() === 'true')
        );
    var hasGate = !!(gateSettings && !gateDisabled);
    if (hasGate) {
        $wrap.removeClass('d-none');
    } else {
        $wrap.addClass('d-none');
        if ($wrap.find('[name="gate_only_step2"]').prop('checked')) {
            $('.select-gate_only_step2').removeClass('fc-selected');
            $wrap.find('[name="gate_only_step2"]').prop('checked', false);
            try {
                updateGateOnly(false);
            } catch (eGo) {}
            if (typeof checkGateOnly === 'function') {
                checkGateOnly();
            }
        }
    }

    if (typeof SlatFence !== 'undefined' && typeof SlatFence.ensureStep2SlatHeightPairRow === 'function') {
        SlatFence.ensureStep2SlatHeightPairRow(slugNorm);
        try {
            var fdGo = typeof getSelectedFenceData === 'function' ? getSelectedFenceData(slugNorm) : null;
            SlatFence.reinitStep2SlatSelect2();
            if (fdGo && fdGo.tabInfo && fdGo.tabInfo[0]) {
                SlatFence.restoreStep2MaxFenceHeightAfterStep2Init(slugNorm, fdGo.tabInfo[0]);
            }
        } catch (eGoS2) {}
    } else if (
        typeof SlatFence !== 'undefined' &&
        typeof SlatFence.syncStep2BlocksSpacingBeforeOverall === 'function'
    ) {
        SlatFence.syncStep2BlocksSpacingBeforeOverall();
    }
}

/**
 * Open Gate Options (#fc-control-modal) from Step 2 Gate ONLY flow.
 * Retries briefly so `#btn-gate` exists after `btnCalculate` / `load_fencing_items`.
 */
function fcOpenStep2GateOptionsModalThenPickWidth() {
    var attempts = 0;
    var maxAttempts = 18;

    function pickWidthSoon() {
        setTimeout(function() {
            fcStep2AutoPickGateWidthAndCalculate();
        }, 420);
    }

    function tryClick() {
        attempts++;
        var $btn = $('#btn-gate.fencing-btn-modal').first();
        if (!$btn.length) {
            $btn = $('.fencing-panel-controls .fencing-btn-modal[data-key="gate"]').first();
        }
        if (!$btn.length) {
            $btn = $('.fencing-panel-gate .fencing-btn-modal[data-key="gate"]').first();
        }
        if ($btn.length) {
            $btn.trigger('click');
            pickWidthSoon();
            return;
        }
        if (attempts < maxAttempts) {
            setTimeout(tryClick, 100);
        } else {
            pickWidthSoon();
        }
    }

    setTimeout(tryClick, 180);
}

/** After Gate Options modal opens: pick lowest STD gate width (or first) and trigger change / calculate. */
function fcStep2AutoPickGateWidthAndCalculate() {
    var $stdSel = $('.fencing-container[data-key="gate"] [name="gate_width"] select');
    if (!$stdSel.length) {
        $stdSel = $('#fc-control-modal [name="gate_width"] select, .js-fencing-modal [name="gate_width"] select');
    }
    if ($stdSel.length) {
        var $opts = $stdSel.first().find('option:not(:disabled)');
        var $pick = $opts.filter(function() {
            return this.selected && String(this.value || '').trim() !== '';
        }).first();
        if (!$pick.length) {
            var bestEl = null;
            var bestMm = Infinity;
            $opts.each(function() {
                var mm = parseInt(String(this.value || '').replace(/,/g, ''), 10);
                if (Number.isFinite(mm) && mm > 0 && mm < bestMm) {
                    bestMm = mm;
                    bestEl = this;
                }
            });
            $pick = bestEl ? $(bestEl) : $opts.first();
        }
        if ($pick.length && String($pick.val() || '').trim() !== '') {
            fcSuppressControlModalCloseOnFcSelectChange = true;
            try {
                $stdSel.first().val($pick.val()).trigger('change');
            } finally {
                fcSuppressControlModalCloseOnFcSelectChange = false;
            }
            return;
        }
    }
    try {
        FENCE.call('update_custom_fence_gate');
    } catch (e1) {}
    try {
        btnCalculate();
    } catch (e2) {}
    try {
        var fdPick = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (fdPick && typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(fdPick.slug)) {
            updateOverAllLength();
            SlatFence.syncStep2GateOnlyOverallField(fdPick, { persist: true });
        }
    } catch (eGo) {}
}

function fcGateOnlyStep2Enable() {
    var $box = $(FENCES.el.measurementBoxNumber);
    var $row = $('.select-gate_only_step2');
    var prev = $box.val() || '';
    var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    var slatStdGo =
        fd &&
        typeof SlatFence !== 'undefined' &&
        SlatFence.isMainSlatSlug(fd.slug);

    if (!slatStdGo || !SlatFence.isGateOnlyPlaceholderMm(prev)) {
        if (prev && !(slatStdGo && SlatFence.isGateOnlyPlaceholderMm(prev))) {
            $box.attr('data-prev-gate-only-mbn', prev);
        }
    }
    if (!slatStdGo) {
        $box.val(9999).attr('data-last', '9999');
    }

    var _width = $('[name="width"]');
    var default_width = fd?.data?.settings?.gate?.size?.width;
    if (_width.length && !_width.val()) {
        _width.val(default_width);
    }

    updateGateOnly(true);
    removeStepPanels();
    if (!slatStdGo) {
        updateOverAllLength();
    }

    FENCE.call('update_gate', 'add');
    FENCE.call('update_custom_fence_gate');

    if (!validateStep2BeforeCalculate(false)) {
        updateGateOnly(false);
        $box.val(prev);
        if (prev) {
            $box.attr('data-last', prev);
        } else {
            $box.removeAttr('data-last');
        }
        $box.removeAttr('data-prev-gate-only-mbn');
        $row.removeClass('fc-selected');
        $row.find('[name="gate_only_step2"]').prop('checked', false);
        if (typeof checkGateOnly === 'function') {
            checkGateOnly();
        }
        return;
    }

    btnCalculate();

    if (slatStdGo) {
        try {
            updateOverAllLength();
        } catch (eOal) {}
        try {
            SlatFence.syncStep2GateOnlyOverallField(getSelectedFenceData(), { persist: true });
        } catch (eSync) {}
    }

    if (typeof checkGateOnly === 'function') {
        checkGateOnly();
    } else if (slatStdGo) {
        try {
            SlatFence.syncStep2GateOnlyHeightMode(getSelectedFenceData());
        } catch (eSyncH) {}
    }

    try {
        if (typeof fcPersistStep2Immediate === 'function') {
            fcPersistStep2Immediate({ force: true });
        }
    } catch (ePs) {}

    fcOpenStep2GateOptionsModalThenPickWidth();
}

function fcHidePlannerStep3Results() {
    try {
        $(FENCES.el.fencingPanelContainer).html('');
    } catch (e) {}
    $('.js-fc-form-step[data-section="3"]').hide();
    try {
        $('.fencing-result-msg').hide();
    } catch (e2) {}
    try {
        $('.err-message').html('');
    } catch (e3) {}
    $('.fc-btn-next-step').attr('disabled', 'disabled');
}

/**
 * Clear Step 2 Gate ONLY UI + storage when the gate was removed elsewhere (e.g. Delete in Gate Options).
 * Does not call `move_the_gate` — the gate is already gone.
 */
function fcUncheckStep2GateOnlyAfterGateDelete() {
    try {
        var fdRm = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (fdRm && typeof fcRemoveGateSegmentFromStorage === 'function') {
            fcRemoveGateSegmentFromStorage(fdRm.tab, fdRm.slug);
        }
    } catch (eSeg) {}

    var $row = $('.select-gate_only_step2');
    if (!$row.length) {
        return;
    }

    var wasOn =
        $row.hasClass('fc-selected') ||
        $row.find('[name="gate_only_step2"]').prop('checked');

    var gateOnlyStored = false;
    try {
        var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        var gateRow = (fd?.info || []).filter(function(it) {
            return it && it.control_key === 'gate';
        })[0];
        gateOnlyStored = !!(gateRow && gateRow.settings && gateRow.settings.gateOnly);
    } catch (eFd) {}

    if (!wasOn && !gateOnlyStored) {
        return;
    }

    var $box = $(FENCES.el.measurementBoxNumber);
    try {
        updateGateOnly(false);
    } catch (eGo) {}

    $row.removeClass('fc-selected');
    $row.find('[name="gate_only_step2"]').prop('checked', false);

    var prev = $box.attr('data-prev-gate-only-mbn');
    if (prev !== undefined && prev !== null && String(prev) !== '') {
        $box.val(prev);
        $box.attr('data-last', prev);
    } else {
        $box.val('');
        $box.removeAttr('data-last');
    }
    $box.removeAttr('data-prev-gate-only-mbn');
    $box.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');

    fcHidePlannerStep3Results();
    try {
        measurementBoxNumber();
    } catch (eMb) {}
    if (typeof checkGateOnly === 'function') {
        checkGateOnly();
    }
    try {
        updateCalculateButtonByStep2Completeness();
    } catch (eUp) {}
    try {
        FENCE.call('update_custom_fence_tab');
    } catch (eTab) {}
    try {
        if (typeof fcPersistStep2Immediate === 'function') {
            fcPersistStep2Immediate({ force: true });
        }
    } catch (ePs) {}
    try {
        if (
            typeof SlatFence !== 'undefined' &&
            typeof SlatFence.syncStep2BlocksSpacingBeforeOverall === 'function'
        ) {
            SlatFence.syncStep2BlocksSpacingBeforeOverall();
        }
    } catch (eSp) {}
}

function fcGateOnlyStep2Disable() {
    var $row = $('.select-gate_only_step2');
    var $box = $(FENCES.el.measurementBoxNumber);
    $row.removeClass('fc-selected');
    $row.find('[name="gate_only_step2"]').prop('checked', false);
    updateGateOnly(false);
    $box.val('');
    $box.removeAttr('data-last');
    $box.removeAttr('data-prev-gate-only-mbn');
    $box.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');
    try {
        FCModal.close('#fc-control-modal');
    } catch (eMc) {}
    try {
        FENCE.call('move_the_gate', 'delete', { skipGateOnlySync: true });
    } catch (eRmGate) {}
    fcHidePlannerStep3Results();
    try {
        measurementBoxNumber();
    } catch (eMb) {}
    if (typeof checkGateOnly === 'function') {
        checkGateOnly();
    }
    try {
        updateCalculateButtonByStep2Completeness();
    } catch (eUp) {}
    try {
        FENCE.call('update_custom_fence_tab');
    } catch (eTab) {}
    try {
        if (typeof fcPersistStep2Immediate === 'function') {
            fcPersistStep2Immediate({ force: true });
        }
    } catch (ePs) {}
}

_doc.on('click', '.select-gate_only_step2', function(e) {
    e.preventDefault();
    var $row = $(this);
    var $cb = $row.find('[name="gate_only_step2"]');
    var turningOn = !$cb.prop('checked');
    $cb.prop('checked', turningOn);
    $row.toggleClass('fc-selected', turningOn);
    if (turningOn) {
        fcGateOnlyStep2Enable();
    } else {
        fcGateOnlyStep2Disable();
    }
});

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-style-item', fencingStyleItem);

function fencingStyleItem(e) {
    var _this = $(this);

    if (_this.hasClass('fencing-style-item--unavailable') || _this.attr('data-live') === '0') {
        return;
    }

    var plannerPage = $('.fc-planner-page').length;
    var tabIdx = $('.fencing-tab.fencing-tab-selected').index();
    var prevSlugRaw = $('.fencing-style-item.fsi-selected').attr('data-slug');
    var newSlugRaw = _this.attr('data-slug');
    var prevSlugNorm =
        prevSlugRaw && typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(String(prevSlugRaw))
            : String(prevSlugRaw || '');
    var newSlugNorm =
        newSlugRaw && typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(String(newSlugRaw))
            : String(newSlugRaw || '');

    var styleChanged =
        !!plannerPage &&
        prevSlugRaw &&
        newSlugRaw &&
        prevSlugNorm !== newSlugNorm;

    var gateOnlyPrev = false;
    if (styleChanged && typeof getSelectedFenceData === 'function') {
        var prevFd = getSelectedFenceData(prevSlugRaw, tabIdx);
        var g0 = prevFd.info.filter(function(item) {
            return item.control_key === 'gate';
        })[0];
        gateOnlyPrev = !!(
            (g0 && g0.settings && g0.settings.gateOnly) ||
            (prevFd.tabInfo[0] && prevFd.tabInfo[0].gateOnly)
        );
        if (gateOnlyPrev && typeof fcSaveStep2GateOnlySnapshot === 'function') {
            fcSaveStep2GateOnlySnapshot(tabIdx, prevSlugNorm, prevFd);
        }
    }

    if (styleChanged && plannerPage && typeof fcPersistStep2ForOutgoingFenceStyle === 'function') {
        fcPersistStep2ForOutgoingFenceStyle(tabIdx, prevSlugNorm);
    }
    if (styleChanged && plannerPage && typeof fcMarkStep2Committed === 'function') {
        fcMarkStep2Committed();
    }

    if (
        styleChanged &&
        plannerPage &&
        typeof fcStripSlatMaxHeightCopiedFromBarr === 'function'
    ) {
        fcStripSlatMaxHeightCopiedFromBarr(tabIdx, prevSlugNorm, newSlugNorm);
    }

    var alreadyActive = _this.hasClass('fsi-selected');
    var userTrustedClick =
        !!(e && e.originalEvent && e.originalEvent.isTrusted === true);

    $('.fencing-style-item').removeClass('fsi-selected');

    _this.addClass('fsi-selected');

    // Only rewrite ?fence= when it already exists (e.g. /planner?fence=slat → flat_top).
    // Do not add fence to a clean /planner URL.
    if (plannerPage && typeof HELPER.syncFenceURLParam === 'function') {
        HELPER.syncFenceURLParam(newSlugNorm || newSlugRaw);
    }

    extra_fields();

    if (plannerPage) {
        try {
            var snapRaw = localStorage.getItem(fcStep2GateOnlySnapKey(tabIdx, newSlugNorm));
            if (snapRaw && typeof fcApplyStep2GateOnlySnapshot === 'function') {
                fcApplyStep2GateOnlySnapshot(JSON.parse(snapRaw));
            } else if (gateOnlyPrev && typeof fcClearStep2AfterGateOnlyStyleSwitch === 'function') {
                fcClearStep2AfterGateOnlyStyleSwitch();
            }
        } catch (errSnap) {}
    }

    var canRenderStep3 = false;
    try {
        canRenderStep3 = !!updateCalculateButtonByStep2Completeness();
    } catch (err) {}
    if (canRenderStep3) {
        var didAutoCalc = false;
        try {
            didAutoCalc = !!plannerTryAutoCalculateIfStep2Complete({
                scrollToStep3: false,
                showNativeTooltip: false
            });
        } catch (err2) {}
        if (!didAutoCalc) {
            try {
                if (
                    typeof fcValidateOverallLengthMm !== 'function' ||
                    fcValidateOverallLengthMm().valid
                ) {
                    FENCE.call('load_fencing_items');
                } else if (typeof fcApplyOverallLengthValidationUi === 'function') {
                    fcApplyOverallLengthValidationUi({ hideStep3: true });
                }
            } catch (e2) {}
        }
    } else {
        fcHidePlannerStep3Results();
    }

    if (styleChanged && plannerPage && typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(newSlugNorm)) {
        try {
            var fdStyle = typeof getSelectedFenceData === 'function' ? getSelectedFenceData(newSlugNorm) : null;
            SlatFence.syncStep2MaxFenceHeightDisabledState(newSlugNorm);
            SlatFence.scheduleStep2SlatSelect2AfterVisible(
                newSlugNorm,
                fdStyle && fdStyle.tabInfo ? fdStyle.tabInfo[0] : null
            );
        } catch (errH2) {}
    }

    $('.js-fc-form-step[data-section="2"]').fadeIn(200, function() {
        if (alreadyActive && userTrustedClick) {
            $(this).scrollTo(100, 54);
        }
    });

    setTimeout(function() {
        $('.fc-btn-next-step').attr('disabled', 'disabled');
        if ($('.fencing-panel-item:visible').length > 0) {
            $('.fc-btn-next-step').removeAttr('disabled');
        }
        checkGateOnly();
        if (typeof fcSyncStep2GateOnlyVisibility === 'function') {
            fcSyncStep2GateOnlyVisibility();
        }
        try { updateCalculateButtonByStep2Completeness(); } catch (err) {}
        try {
            if (typeof SlatFence !== 'undefined' && typeof SlatFence.scheduleStep2SlatSelect2AfterVisible === 'function') {
                var fdVis = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
                SlatFence.scheduleStep2SlatSelect2AfterVisible(
                    fdVis ? fdVis.slug : '',
                    fdVis && fdVis.tabInfo ? fdVis.tabInfo[0] : null
                );
            } else if (typeof fcInitStep2Select2 === 'function') {
                fcInitStep2Select2();
            }
        } catch (errS2) {}

    }, 100);    
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-style-item', fencingStyleItem_2);

function fencingStyleItem_2() {
    var _this = $(this);
    if (_this.hasClass('fencing-style-item--unavailable') || _this.attr('data-live') === '0') {
        return;
    }

    var fd = getSelectedFenceData();

    var gate_data = fd.info.filter(function(item) {
        return item.control_key == 'gate';
    });

    gateOnly = gate_data[0]?.settings?.gateOnly;

    if(gateOnly) {
        updateOverAllLength();
    }

    FENCE.call('update_custom_fence_style_item');
    // New sections start with no `custom_fence-{tab}` row; Step 2 may still be "dirty" from
    // programmatic resets — always seed storage when a style is picked on the planner.
    FENCE.call('set_cutom_fence_data', $('.fc-planner-page').length ? { force: true } : {});

    setTimeout(function() {
        try {
            if (typeof fcSyncPlannerUpdateButtonVisibility === 'function') {
                fcSyncPlannerUpdateButtonVisibility();
            }
        } catch (err) {}
    }, 0);
}

//----------------------------------------------------------------------------------

_doc.on('click', '#btn-gate', btnGate);

_doc.on('click', '#btn-planner-summary', function() {
    if (typeof fcOpenPlannerSummaryModal === 'function') {
        fcOpenPlannerSummaryModal();
    }
});

_doc.on('click', '.fc-project-plan-summary-btn', function() {
    var section = parseInt($(this).data('section'), 10);
    if (typeof fcOpenPlannerSummaryModal === 'function') {
        fcOpenPlannerSummaryModal(Number.isFinite(section) ? section : undefined);
    }
});

/** True when the active planner diagram already has a gate panel. */
function fcPlannerGateIsOnPlan() {
    return $('.fencing-panel-gate:visible').length > 0;
}

/** Keep #btn-gate in Gate Options mode when a gate is already on the plan. */
function fcSyncPlannerGateButtonMode($btn) {
    $btn = $btn && $btn.length ? $btn : $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.btnGate : '#btn-gate');
    if (!$btn.length || !fcPlannerGateIsOnPlan()) {
        return;
    }
    $btn.addClass('edit-gate').removeClass('add-gate');
    if (!/gate\s*options/i.test($btn.text())) {
        $btn.html('<span>Gate</span> Options');
    }
}

function btnGate() {
    var _this = $(this),
        data = {};

    if (_this.prop('disabled')) {
        return;
    }

    if (_this.hasClass('edit-gate') || fcPlannerGateIsOnPlan()) {
        fcSyncPlannerGateButtonMode(_this);
        return;
    }

    data.gate = 1;
    updateOverAllLength(data);

    FENCE.call('update_gate', 'add');
    FENCE.call('update_custom_fence_gate');

    try {
        var fdGate = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (fdGate && typeof fcSyncGateMinOverallLength === 'function') {
            fcSyncGateMinOverallLength(fdGate);
            updateOverAllLength();
        }
    } catch (eGateOal) {}
}

//----------------------------------------------------------------------------------

_doc.on('click', FC_PRESSABLE_SELECTOR + ', ' + FC_PRESSABLE_CHILD_SELECTOR, fcPressableClickEffect);

function fcPressableClickEffect() {
    var $el = $(this);
    var $target = $el.closest('.fc-select-color').first();
    if (!$target.length) {
        $target = $el.closest('#submit-modal .fc-other-products .fc-form-check-img').first();
    }
    if (!$target.length) {
        $target = $el;
    }
    fcTriggerPressEffect($target);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-move-post', fcMovePost);

function fcMovePost() {
    var _this = $(this),
        move = _this.data('move'),
        gate = $('.fencing-panel-gate:visible');

    if (_this.hasClass('disabled')) {
        return;
    }

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

        var $qtyBtn = _this.closest('.fc-input-container').find('[type="button"]');
        if (!$qtyBtn.hasClass('fc-gate-modal-calculate-btn')) {
            $qtyBtn.removeAttr('disabled')
                .removeClass('disabled')
                .removeClass('btn-light')
                .addClass('btn-dark');
        }
    } else {
        if (val < max) {
            input.val(parseInt(val, 10) + 1);
        }
    }

    if (input.hasClass('measurement-box-number')) {
        try {
            measurementBoxNumber();
        } catch (eMbQty) {}
    }

    if (input.attr('name') === 'max_fence_height') {
        input.trigger('input');
    } else if (input.attr('name') === 'gate_max_fence_height') {
        try {
            gateMaxFenceHeightValidation.call(input[0], { target: input[0], type: 'input' });
        } catch (eGhQty) {}
    } else if (
        input.attr('name') === 'width' &&
        input.closest('.custom-gate').length &&
        typeof SlatFence !== 'undefined'
    ) {
        input.trigger('keyup');
    }

    if (input.closest('.custom-gate').length && typeof SlatFence !== 'undefined' && SlatFence.syncGateModalCalculateButtonState) {
        SlatFence.syncGateModalCalculateButtonState();
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
            input.val(parseInt(val, 10) - 1);
        }
    }

    if (input.hasClass('measurement-box-number')) {
        try {
            measurementBoxNumber();
        } catch (eMbQty) {}
    }

    if (input.attr('name') === 'max_fence_height') {
        input.trigger('input');
    } else if (input.attr('name') === 'gate_max_fence_height') {
        try {
            gateMaxFenceHeightValidation.call(input[0], { target: input[0], type: 'input' });
        } catch (eGhQty) {}
    } else if (
        input.attr('name') === 'width' &&
        input.closest('.custom-gate').length &&
        typeof SlatFence !== 'undefined'
    ) {
        input.trigger('keyup');
    }

    if (input.closest('.custom-gate').length && typeof SlatFence !== 'undefined' && SlatFence.syncGateModalCalculateButtonState) {
        SlatFence.syncGateModalCalculateButtonState();
    }

    $('.error-msg').removeClass('fcim-show').html('');
}

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-get-link', savePlanner);

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-copy-link', btnCopyLink);

function btnCopyLink() {
    var _this = $(this),
        id = _this.attr('data-id'),
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

_doc.on('click', '.fc-copy-quote-link', fcCopyQuoteLink);

function fcCopyTextToClipboard(text, onDone) {
    if (!text) {
        return;
    }
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).then(onDone).catch(function() {
            fcCopyTextToClipboardFallback(text, onDone);
        });
        return;
    }
    fcCopyTextToClipboardFallback(text, onDone);
}

function fcCopyTextToClipboardFallback(text, onDone) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand('copy');
    } catch (e) {}
    document.body.removeChild(ta);
    if (typeof onDone === 'function') {
        onDone();
    }
}

function fcCopyQuoteLink(e) {
    e.preventDefault();
    e.stopPropagation();

    var $btn = $(this);
    var url = ($btn.attr('data-copy-url') || '').trim();
    if (!url) {
        return;
    }

    var $label = $btn.find('.fc-copy-quote-link__label');
    var label = $btn.data('fc-copy-label');
    if (label === undefined || label === null || label === '') {
        label = $label.length ? $label.text().trim() : $btn.text().trim();
        $btn.data('fc-copy-label', label);
    }

    fcCopyTextToClipboard(url, function() {
        if ($label.length) {
            $label.text('Copied!');
        } else {
            $btn.text('Copied!');
        }
        /* The label is the only confirmation, and it is hidden where the button is icon-only —
           the class lets CSS show the result some other way there (see .is-copied). */
        $btn.addClass('is-copied');
        setTimeout(function() {
            if ($label.length) {
                $label.text(label);
            } else {
                $btn.text(label);
            }
            $btn.removeClass('is-copied');
        }, 2000);
    });
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

    // Gate modal: persist selections immediately so they reload on open.
    // (Gate selections were previously saved after closing the modal, which can miss values.)
    if ($('.fencing-container').attr('data-key') === 'gate') {
        try { FENCE.call('update_custom_fence_gate'); } catch (err) {}

        if (getFormField.attr('name') === 'gate_hinge_type') {
            var fdHt = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
            if (
                fdHt &&
                fdHt.data &&
                fdHt.data.panel_group === 'a' &&
                typeof fcGlassPoolApplyHingeTypeChange === 'function'
            ) {
                try {
                    fcGlassPoolApplyHingeTypeChange(slug, fdHt);
                } catch (errHt) {}
            }
        }

        // Slat Fence: reflect "Type Of Gate" changes on the Step 3 gate label immediately.
        // (Do NOT call update_gate('edit') here; that can re-insert the gate and duplicate it.)
        if (getFormField.attr('name') === 'gate_type') {
            try { FENCE.call('refresh_gate_label'); } catch (err) {}
            var fdGt = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
            if (fdGt && typeof fcSyncGateMinOverallLength === 'function') {
                try {
                    fcSyncGateMinOverallLength(fdGt, { persist: true });
                } catch (eSyncGt) {}
                try { updateOverAllLength(); } catch (errOal) {}
                try { btnCalculate(); } catch (errCalc) {}
            }
        }

        // Width Dimension From (in Gate Options): should ONLY affect gate panel display,
        // not the overall fence (overall-length offset) calculation.
        // It DOES affect gate width, so we need to recalculate layout.
        if (getFormField.attr('name') === 'width_dimension_from') {
            try { widthDimensionFrom_change?.(); } catch (err) {}
        }
    }

    // Slat Fence: Width Dimension From now lives in Panel Options modal (text_option).
    // Auto-calculate on selection if Overall Length is valid.
    if ($('.fencing-container').attr('data-key') === 'panel_options' && getFormField.attr('name') === 'width_dimension_from') {
        try { widthDimensionFrom_change?.(); } catch (err) {}
    }

    if ($('.fencing-container').attr('data-key') == 'right_side') {
        if (typeof fcFenceDiagramScrollCenter === 'function') {
            fcFenceDiagramScrollCenter('.panel-post:last', 300);
        } else {
            $('.fencing-display-result').scrollCenter('.panel-post:last', 300);
        }
    }

    if ($('.fencing-container').attr('data-key') == 'left_side') {
        if (typeof fcFenceDiagramScrollCenter === 'function') {
            fcFenceDiagramScrollCenter('.panel-post:first', 300);
        } else {
            $('.fencing-display-result').scrollCenter('.panel-post:first', 300);
        }
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-select-post', lastClickedElement);
_doc.on('change', '.fc-select-option', fcSelectOption);

function lastClickedElement() {
    var _this = $(this),
        slug = _this.attr('data-slug'),
        getFormField = _this.closest('.fc-form-field');

    // Store last clicked element
    data = {
        'name' : getFormField.attr('name'),
        'value' : slug
    }
    localStorage.setItem('last-clicked-value', JSON.stringify(data));
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-modal-content', function(e) {
    e.stopPropagation();
});

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-tab', fencingTab);

function fencingTab() {

    var _this = $(this);
    var $plannerStrip =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : null;
    var prevTabIdx = -1;
    if ($plannerStrip && $plannerStrip.length) {
        var $prevSel = $plannerStrip.filter('.fencing-tab-selected');
        prevTabIdx = $plannerStrip.index($prevSel);
    } else {
        prevTabIdx = $('.fencing-tab.fencing-tab-selected').index();
    }

    // Persist Step 2 + section row for the tab we are leaving (otherwise fields only
    // saved on Calculate or fence-style click are lost when switching sections).
    var nextTabIdx =
        $plannerStrip && $plannerStrip.length ? $plannerStrip.index(_this) : _this.index();
    if (prevTabIdx >= 0 && prevTabIdx !== nextTabIdx) {
        var skipLeavingPersist = false;
        if ($plannerStrip && $plannerStrip.length) {
            var $leavingTab = $plannerStrip.eq(prevTabIdx);
            if ($leavingTab.length && $leavingTab.hasClass('is-deleting')) {
                skipLeavingPersist = true;
            }
        } else if ($('.fencing-tab.fencing-tab-selected').hasClass('is-deleting')) {
            skipLeavingPersist = true;
        }
        // Do not write localStorage for a tab being removed — delete flow already reindexed
        // custom_fence-*; persisting here overwrote e.g. custom_fence-3 with the deleted section's DOM.
        if (!skipLeavingPersist) {
            try {
                if (typeof fcPersistStep2Immediate === 'function') {
                    fcPersistStep2Immediate();
                } else {
                    FENCE.call('set_cutom_fence_data');
                }
            } catch (e) {}
        }
    }

    $(FENCES.el.fencingContainer).attr('data-key', '');

    if (
        typeof SlatFence !== 'undefined' &&
        typeof SlatFence.resetSlatDisplayScaling === 'function'
    ) {
        SlatFence.resetSlatDisplayScaling();
    }

    $('.fencing-tab').removeClass('fencing-tab-selected');

    _this.addClass('fencing-tab-selected');

    var tab =
            $plannerStrip && $plannerStrip.length
                ? $plannerStrip.index(_this)
                : $('.fencing-tab.fencing-tab-selected').index(),
        modal_key = $('.fencing-container').attr('data-key'),
        custom_fence_tabs = localStorage.getItem('custom_fence-' + tab);

    const custom_fence_tab = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];

    HELPER.resetSectionsBlocks();

    // .not('.slick-cloned'): the slug matches Slick's clones too, and the re-click below then
    // left the class on the last clone — the real slide came back from a tab switch unmarked.
    $('.fencing-style-item[data-slug="' + custom_fence_tab[0]?.style + '"]')
        .not('.slick-cloned')
        .addClass('fsi-selected');
    var tabRow0 = custom_fence_tab[0];
    var styleSlug = tabRow0?.style;
    if (styleSlug && typeof fcStripOverMaxOverallLengthFromTabStorage === 'function') {
        fcStripOverMaxOverallLengthFromTabStorage(tab, styleSlug);
        try {
            var rawTabFresh = localStorage.getItem('custom_fence-' + tab);
            custom_fence_tab = rawTabFresh ? JSON.parse(rawTabFresh) : custom_fence_tab;
            tabRow0 = custom_fence_tab[0] || tabRow0;
        } catch (eTabFresh) {}
    }
    var measurement = FENCES.defaultValues.measurement;
    if (styleSlug && typeof fcGetStep2MeasurementForStyle === 'function') {
        var mSnap = fcGetStep2MeasurementForStyle(tabRow0, styleSlug);
        if (mSnap && mSnap.val !== undefined && mSnap.val !== null && String(mSnap.val) !== '') {
            measurement = mSnap.val;
        } else if (typeof fcGetStep2CalculateValueForStyle === 'function') {
            var cvSnap = fcGetStep2CalculateValueForStyle(tabRow0, styleSlug);
            if (cvSnap !== undefined && cvSnap !== null && String(cvSnap) !== '') {
                measurement = cvSnap;
            }
        }
    }
    if (typeof fcSanitizeOverallLengthRestoreVal === 'function') {
        measurement = fcSanitizeOverallLengthRestoreVal(measurement);
    }
    if (typeof fcRunWithoutStep2DirtyTracking === 'function') {
        fcRunWithoutStep2DirtyTracking(function() {
            $('.measurement-box-number').val(measurement);
        });
    } else {
        $('.measurement-box-number').val(measurement);
    }
    if (!measurement) {
        $('.measurement-box-number').removeAttr('data-last');
        $('.measurement-box-number').removeAttr('data-prev-gate-only-mbn');
    }
    if (measurement && styleSlug && tabRow0 && typeof fcGetStep2MeasurementForStyle === 'function') {
        var mTab = fcGetStep2MeasurementForStyle(tabRow0, styleSlug);
        if (mTab && mTab.dataLast) {
            $('.measurement-box-number').attr('data-last', mTab.dataLast);
        }
        if (mTab && mTab.dataPrevGateOnlyMbn) {
            $('.measurement-box-number').attr('data-prev-gate-only-mbn', mTab.dataPrevGateOnlyMbn);
        }
    }

    FENCE.call('update_custom_fence_tab');

    $('.fsi-selected').not('.slick-cloned').first().trigger('click');

    if (typeof fcMarkStep2Committed === 'function') {
        fcMarkStep2Committed();
    }

    HELPER.loadStep3(custom_fence_tab[0]);

    if (_this.hasClass('fencing-tab')) {
        // Do not replace ?tab=2 (Project Options) with ?section=… — e.g. reload with ?tab=2 triggers a
        // programmatic fencing-tab click that must not wipe the planner tab query param.
        var $projectOptions = $('.fc-planner-page .fc-section-step[data-tab="2"]');
        if (!$projectOptions.length || !$projectOptions.is(':visible')) {
            HELPER.setSectionURLParam();
        }
    }

    // Project Options / Colour Options: re-measure Slick when switching section (?section=N).
    if ($('.fc-planner-page').length && $('.fc-planner-page .fc-step-4').is(':visible')) {
        if (typeof window.fcRefreshColorOptionsSlick === 'function') {
            requestAnimationFrame(function() {
                window.fcRefreshColorOptionsSlick();
            });
        }
    }

    // Center the active section tab in the horizontal strip when tabs overflow.
    setTimeout(function() {
        HELPER.scrollFencingTabIntoCenter(_this);
        // …and bring this section's fence style into the picker's visible window.
        HELPER.scrollFenceStyleIntoCenter();
    }, 0);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-modal .fc-select', fencingModalFcSelect);

function fencingModalFcSelect() {
    // Ensure gate selections are persisted before closing (modal fields become non-visible after close).
    try { FENCE.call('update_custom_fence_gate'); } catch (err) {}
    FCModal.close();
    $('.fc-btn-active').removeClass('fc-btn-active');
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-select-2', fcSelect2);

function fcSelect2() {
    var _this = $(this),
        _checkbox = _this.find('[type="checkbox"]'),
        _radio = _this.find('[type="radio"]');

    if (_this.hasClass('select-gate_only_step2') || _this.hasClass('select-gate_only')) {
        return;
    }

    if( _checkbox.length ) {
        var val = _checkbox.is(':checked') ? false : true;

        _this.toggleClass('fc-selected');
        _checkbox.prop('checked', val);

    } else if( _radio.length ) {

        var val = _radio.is(':checked') ? false : true;

        _this.closest('.fc-form-field').find('.fc-selected').removeClass('fc-selected');    
        _this.addClass('fc-selected');
        _radio.prop('checked', val);
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.select-gate_only', gateOnly);

function gateOnly(e) {
    e?.preventDefault?.();
    var $row = $(e?.currentTarget).closest('.select-gate_only');
    if (!$row.length) {
        $row = $('.fencing-container[data-key="gate"] .select-gate_only').first();
    }
    var $cb = $row.find('[name="gate_only"]');
    var turningOn = !$cb.prop('checked');
    $cb.prop('checked', turningOn);
    $row.toggleClass('fc-selected', turningOn);

    var _width = $('[name="width"]');
    var fd = getSelectedFenceData(),
        default_width = fd?.data?.settings?.gate?.size?.width;

    if (turningOn) {
        if (!_width.val()) {
            _width.val(default_width);
        }

        updateGateOnly(true);
        removeStepPanels();
        updateOverAllLength();

        FENCE.call('update_gate', 'add');
        FENCE.call('update_custom_fence_gate');

        btnCalculate();
    } else {
        updateGateOnly(false);
        try {
            FENCE.call('move_the_gate', 'delete', { skipGateOnlySync: true });
        } catch (eRmGate) {}
        btnCalculate();
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.select-use_std', use_std);

function use_std(e) {
    var _this = $(this),
        val = _this.attr('data-val');

    $('[name="use_std"]').prop('checked', val=='std'?true:false);    

    _this.closest('.fc-form-field').find('.fc-selected').removeClass('fc-selected');    
    _this.addClass('fc-selected');

    if (typeof SlatFence !== 'undefined' && SlatFence.enableGateModalHeightQtyButtons) {
        SlatFence.enableGateModalHeightQtyButtons();
    }

    if($('[name="use_std"]').is(':checked')) {
        // STD: lock Custom Gate Width, apply STD size, close modal.
        FENCE.call('disabledCustomGate');

        updateOverAllLength();

        FENCE.call('calculateCustomGate');

        FCModal.close();

        btnCalculate();
        return;
    }

    // CUSTOM: unlock Custom Gate Width for editing.
    $('[name="width"]').removeAttr('readonly')
        .removeClass('disabled text-muted')
        .val('')
        .focus();

    $('.custom-gate .fc-gate-modal-custom-width-section .fencing-qty-btn').removeClass('disabled');

    if (typeof SlatFence !== 'undefined' && SlatFence.syncGateModalCalculateButtonState) {
        SlatFence.syncGateModalCalculateButtonState();
    } else {
        $('.custom-gate .fc-gate-modal-custom-width-section button')
            .removeAttr('disabled')
            .removeClass('btn-light disabled')
            .addClass('btn-dark');
    }
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

    setTimeout(function() {
        try {
            if (typeof fcSyncPlannerUpdateButtonVisibility === 'function') {
                fcSyncPlannerUpdateButtonVisibility();
            }
        } catch (err) {}
    }, 0);
}

//----------------------------------------------------------------------------------

/* Reset and Delete Section discard work the same way Clear All does, so they get the same
   confirm dialog (planner/modals.php).

   Gated in the capture phase rather than inside each handler: the Step 3 Reset button carries
   both .fc-fence-reset-all and .fc-fence-reset, so one click runs two separate delegated
   handlers, and a guard inside either one would let the other through. Capturing on document
   stops the event before it ever reaches them. */
var FC_CONFIRM_ACTIONS = [
    { selector: '.js-btn-delete-fence', modal: '#fc-delete-section-confirm', needsConfirm: fcSectionHasFenceStyle },
    { selector: '.fc-fence-reset-all, .fc-fence-reset', modal: '#fc-reset-section-confirm', needsConfirm: fcSectionIsCalculated }
];

var fcConfirmPendingEl = null;

/** The active section's stored record, or null. Same shape the tab strip reads for its own labels. */
function fcActiveSectionData() {
    var tab = $('.fencing-tab.fencing-tab-selected').index(),
        raw = tab > -1 ? localStorage.getItem('custom_fence-' + tab) : null,
        data = [];

    if (raw) {
        try {
            data = JSON.parse(raw);
        } catch (e) {
            data = [];
        }
    }

    return (data && data[0]) ? data[0] : null;
}

// An empty section has nothing to lose, so deleting it needs no confirming. The DOM check covers
// a style picked but not yet written to storage.
function fcSectionHasFenceStyle() {
    var data = fcActiveSectionData();

    return !!(data && data.style) || $('.fencing-style-item.fsi-selected').length > 0;
}

// Until Step 2 is calculated the section holds no measurements, so Reset discards nothing worth
// warning about. calculateValue is the same field the tab strip uses to show "9,000 mm".
function fcSectionIsCalculated() {
    var data = fcActiveSectionData();

    return !!(data && data.calculateValue);
}

document.addEventListener('click', function(e) {
    if (!e.target || typeof e.target.closest !== 'function') {
        return;
    }

    for (var i = 0; i < FC_CONFIRM_ACTIONS.length; i++) {
        var el = e.target.closest(FC_CONFIRM_ACTIONS[i].selector);

        if (!el) {
            continue;
        }

        // The re-fire from the confirm button. Clear the pass and let it through untouched.
        if (el.getAttribute('data-fc-confirmed') === '1') {
            el.removeAttribute('data-fc-confirmed');
            return;
        }

        // Nothing destructive to warn about yet — let the action run straight through.
        if (typeof FC_CONFIRM_ACTIONS[i].needsConfirm === 'function' && !FC_CONFIRM_ACTIONS[i].needsConfirm()) {
            return;
        }

        var modal = document.querySelector(FC_CONFIRM_ACTIONS[i].modal);

        // No dialog on the page (project-plan includes these controls without modals.php) —
        // fall through to the original behaviour rather than blocking the button outright.
        if (!modal || typeof bootstrap === 'undefined') {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        fcConfirmPendingEl = el;
        bootstrap.Modal.getOrCreateInstance(modal).show();
        return;
    }
}, true);

_doc.on('click', '.js-fc-confirm-proceed', fcConfirmProceed);

function fcConfirmProceed() {
    var el = fcConfirmPendingEl,
        modal = this.closest ? this.closest('.modal') : null;

    fcConfirmPendingEl = null;

    if (!el) {
        return;
    }

    // Closing is left to data-bs-dismiss on the button so it never depends on this handler.
    // Re-fire only once the dialog has finished closing: the reset and delete handlers tear down
    // the DOM the modal is measuring against, and Bootstrap leaves a stuck backdrop if it is
    // mid-transition when that happens.
    //
    // Native listener rather than $.one(): jQuery reads the dots in "hidden.bs.modal" as
    // namespaces, so it binds type "hidden" and the callback is at the mercy of Bootstrap's
    // jQuery bridge. The timeout is the backstop for a modal that never emits the event.
    if (!modal) {
        fcConfirmRefire(el);
        return;
    }

    var done = false,
        finish = function() {
            if (done) {
                return;
            }
            done = true;
            modal.removeEventListener('hidden.bs.modal', finish);

            // Yield a turn before acting. Bootstrap is still inside its own hidden handling here
            // — removing the backdrop and the body class — and Delete Section rebuilds the tab
            // strip, which interrupts that and leaves the dialog and backdrop stuck on screen.
            setTimeout(function() {
                fcConfirmRefire(el);
            }, 0);
        };

    modal.addEventListener('hidden.bs.modal', finish);

    // Backstop for a dialog that never emits the event. Comfortably past Bootstrap's 300ms fade
    // so it does not pre-empt the real one.
    setTimeout(finish, 800);
}

function fcConfirmRefire(el) {
    el.setAttribute('data-fc-confirmed', '1');
    el.click();
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
        $('.fencing-tab-selected').find('.ftm-fence-style').empty().prop('hidden', true).hide();
        $('.fc-tab-title, .fc-tab-subtitle').html('');
        $('.measurement-box-number').val('');

        localStorage.removeItem('custom_fence-' + tab);
        localStorage.removeItem('custom_fence-' + tab + '-' + i);

        $('.js-fc-form-step').fadeOut('fast');
        $('.fc-fence-reset-all').hide();

        try {
            if (typeof fcSyncPlannerUpdateButtonVisibility === 'function') {
                fcSyncPlannerUpdateButtonVisibility();
            }
            if (typeof fcSyncAllPlannerSectionTabStatuses === 'function') {
                fcSyncAllPlannerSectionTabStatuses();
            }
        } catch (err) {}
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

    setTimeout(function() {
        try {
            if (typeof fcSyncPlannerUpdateButtonVisibility === 'function') {
                fcSyncPlannerUpdateButtonVisibility();
            }
        } catch (err) {}
    }, 0);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.js-btn-delete-fence', jsBtnDeleteFence);

function jsBtnDeleteFence(e) {
    e?.preventDefault();

    if (HELPER.getFenceSectionTabCount() <= 1) {
        return;
    }

    var _this = $(this);

    _this.attr('disabled', 'disabled');

    var $stripDel =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : $('.fc-planner-page').find(FENCES.el.tabArea).first().children('.fencing-tab');
    var $selDel = $stripDel.filter('.fencing-tab-selected');
    var getActiveTabIndex = $stripDel.length ? $stripDel.index($selDel) : $('.fencing-tab.fencing-tab-selected').index();

    // Remove + reindex custom_fence-* and cart_items-* (1-based cart bucket) before DOM updates.
    if (typeof fcReindexPlannerStorageAfterSectionDelete === 'function') {
        fcReindexPlannerStorageAfterSectionDelete(getActiveTabIndex);
    }
    HELPER.deleteSectionTab();
    HELPER.refreshSectionTabIndex();
    HELPER.hideDeleteSectionBtn();

    // deleteSectionTab() no longer fires a fencing-tab click mid-strip (stale index vs reindexed
    // localStorage). Load the neighbour tab once the row is compact so `custom_fence-{index}` matches.
    var $stripSync =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : $('.fc-planner-page').find(FENCES.el.tabArea).first().children('.fencing-tab');
    var $selSync = $stripSync.filter('.fencing-tab-selected');
    if ($selSync.length) {
        $selSync.trigger('click');
    }

    setTimeout(function() {
        HELPER.hideDeleteSectionBtn();
        try {
            if (typeof fcSyncPlannerUpdateButtonVisibility === 'function') {
                fcSyncPlannerUpdateButtonVisibility();
            }
        } catch (err) {}
    }, 0);
}

//----------------------------------------------------------------------------------

/** Modal card grid: 4 columns on desktop when option count is 4 or 8. */
function fcModalOptionColClass(optionCount, fieldType, isStepUpRaked) {
    if (optionCount === 4 || optionCount === 8) {
        return 'col-lg-3 col-6';
    }
    if (fieldType === 'text_option') {
        return 'col-sm-6 col-12';
    }
    return 'col-sm-4 col-6';
}

function fcModalOptionsUseQuadColumns(optionCount) {
    return optionCount === 4 || optionCount === 8;
}

_doc.on('click', '.fencing-btn-modal', fencingBtnModal);

function fencingBtnModal(event) {
    var _this = $(this);


    if (!_this.hasClass('fencing-btn-modal'))
        return false;

    if (
        _this.prop('disabled') ||
        _this.hasClass('fc-panel-control--gate-only-disabled')
    ) {
        event?.preventDefault?.();
        return false;
    }

    var fd = getSelectedFenceData();

    if (fd?.slug === 'slat_fence_infill' && (_this.hasClass('panel-post') || _this.attr('data-key') === 'post_options')) {
        return false;
    }

    if (String(_this.attr('data-key') || '') === 'gate') {
        var gSt = fd?.data?.settings?.gate;
        if (
            gSt &&
            (gSt.disabled === true ||
                gSt.disabled === 1 ||
                String(gSt.disabled).toLowerCase() === 'true')
        ) {
            event?.preventDefault?.();
            return false;
        }
    }

    let modal = {
        el: '',
        content: '.fc-modal-content'
    };

    //Button Data Information
    var target = _this.attr('data-target'),
        key = _this.attr('data-key');

    var i = fd.slug,
        tab = fd.tab,
        info = fd.info,
        data = fd.data;

    modal.el = $(target);
    modal.content = modal.el.find(modal.content);
    FCModal.open(target);

    if (target === '#submit-modal' && typeof window.fcRefreshColorOptionsSlick === 'function') {
        setTimeout(function() {
            window.fcRefreshColorOptionsSlick();
        }, 220);
    }

    _this.addClass('fc-btn-active');

    if (typeof key !== "undefined") {
        modal.el.find(".fencing-modal-notes").html('');
        modal.content.html('');
    }

    FENCES.setActiveSetting(key);

    var fields = data?.settings[key]?.fields;
    if (key === 'gate' && typeof SlatFence !== 'undefined' && SlatFence.isSlatLike && SlatFence.isSlatLike(i)) {
        if (Array.isArray(fields)) {
            fields = fields.filter(function(v) {
                return v && v.slug !== 'gate_hinge_type';
            });
        }
    }

    if (typeof fcFilterModalFieldsForGateOnly === 'function') {
        fields = fcFilterModalFieldsForGateOnly(i, fields);
    }

    if (typeof fields !== "undefined") {

        modal.content.addClass('has-multiple-areas');
        if (fields.length > 1) {
            // modal.content.addClass('has-multiple-areas');
        } else {
            // modal.content.removeClass('has-multiple-areas');
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
                .replace(/{{min}}/gi, v?.min)
                .replace(/{{max}}/gi, v?.max)
                .replace(/{{step}}/gi, v?.step)
                .replace(/{{check_name}}/gi, v?.check_settings?.name)
                .replace(/{{check_class}}/gi, v?.check_settings?.class)
                .replace(/{{check_label}}/gi, v?.check_settings?.label)
                .split(/\$\{(.+?)\}/g);


            modal.content.append(tpl);

            // Current area for this field (supports multiple fields of same type).
            const $area = $(target+' .fencing-modal-area').eq(k);

            if (v.type == 'dropdown_option') {

                $(v.options).each(function(i, o) {
                    $('[name="' + v.slug + '"] select').append($('<option>', { value: o.slug, text: o.title }));
                    $('[name="' + v.slug + '"] select').attr('data-key', v.key);
                });

            }

            if (v.type == 'dropdown_option_check') {

                $(v.options).each(function(i, o) {
                    $('[name="' + v.slug + '"] select').append($('<option>', { value: o.slug, text: o.title }));
                    $('[name="' + v.slug + '"] select').attr('data-key', v.key);
                });

            }

            if (v.type == 'range_option') {

                var rangeOptCount = (v.options || []).length;
                var rangeColClass = fcModalOptionColClass(rangeOptCount, 'range_option', false);
                var rangeItem = function(opt) {
                    return (
                        '<div class="' +
                        rangeColClass +
                        ' mb-2 px-1 text-center">' +
                        '<div class="fc-select-post fc-select" data-key="' +
                        key +
                        '" data-slug="' +
                        opt.slug +
                        '">' +
                        '<img src="' +
                        opt.image +
                        '">' +
                        '</div>' +
                        '<p>' +
                        (opt.title || '') +
                        '</p>' +
                        '</div>'
                    );
                };
                var $rangeRow = $area.find('.row');
                if (fcModalOptionsUseQuadColumns(rangeOptCount)) {
                    $rangeRow.addClass('fc-modal-options--cols-4');
                }
                $rangeRow.html(v.options.map(rangeItem).join(''));

            }

            if (v.type == 'text_option') {

                var isStepUpRaked = v.slug === 'left_raked' || v.slug === 'right_raked';
                var textOptCount = (v.options || []).length;
                var textColClass = fcModalOptionColClass(textOptCount, 'text_option', isStepUpRaked);
                var Item = function(opt) {
                    return (
                        '<div class="' +
                        textColClass +
                        ' mb-2 px-1' +
                        (isStepUpRaked ? '' : ' text-center') +
                        '">' +
                        '<div class="fc-select-post fc-select" data-key="' +
                        key +
                        '" data-slug="' +
                        opt.slug +
                        '">' +
                        '<p>' +
                        opt.title +
                        (opt.desc ? '<strong>' + opt.desc + '</strong>' : '') +
                        '</p>' +
                        '</div>' +
                        '</div>'
                    );
                };

                var $row = $area.find('.row');
                if (isStepUpRaked) {
                    $area.addClass('fencing-modal-area--step-up');
                    if (typeof fcModalFieldHasNotesContent === 'function' && fcModalFieldHasNotesContent(v)) {
                        $area.find('.fencing-modal-body').addClass('mb-4');
                    }
                }
                if (fcModalOptionsUseQuadColumns(textOptCount)) {
                    $row.addClass('fc-modal-options--cols-4');
                }
                $row.html(v.options.map(Item).join(''));
            }

            if (v.type == 'image_option') {

                var imageOptCount = (v.options || []).length;
                var imageColClass = fcModalOptionColClass(imageOptCount, 'image_option', false);
                var imageItem = function(opt) {
                    return (
                        '<div class="' +
                        imageColClass +
                        ' mb-2 text-center px-1">' +
                        '<div class="fc-select-post fc-select" data-key="' +
                        key +
                        '" data-slug="' +
                        opt.slug +
                        '">' +
                        '<img src="' +
                        opt.image +
                        '" class="fc-fullwidth">' +
                        '</div>' +
                        '<p>' +
                        (opt.title || '') +
                        '</p>' +
                        '<p>' +
                        (opt.extra || '') +
                        '</p>' +
                        '</div>'
                    );
                };
                var $imageRow = $area.find('.row');
                if (fcModalOptionsUseQuadColumns(imageOptCount)) {
                    $imageRow.addClass('fc-modal-options--cols-4');
                }
                $imageRow.html(v.options.map(imageItem).join(''));
            }

            addNotesOrInfo($area.find('.fencing-modal-notes'), v);

            if (typeof fcSyncModalAreaBodyMargin === 'function') {
                fcSyncModalAreaBodyMargin($area, v);
            }
         
            if (v.class) {
                $area.addClass(v.class);
            }


            // GET/SET DEFAULT VALUE
            var default_value = v.options?.filter(function(item) {
                return item.default == true;
            });

            if (default_value) {

                var opt = default_value[0],
                    tag = $('.fc-form-field[name="'+v.slug+'"]').get(0).tagName.toLowerCase();

                // Set overall value for side posts
                if (v?.slug == 'post_option' && $.inArray(key, ['left_side', 'right_side']) !== -1) {

                    var post_options_filtered_data = info.filter(function(item) {
                        return item.control_key === "post_options";
                    });

                    var _postValue = post_options_filtered_data[0]?.settings[0]?.val || opt?.slug;

                    // HELPER.get_field_value(tag, v?.slug, _postValue);
                    var optValue = _postValue;

                } else {
                
                    // HELPER.get_field_value(tag, v?.slug, opt?.slug);
                    var optValue = opt?.slug;
                }

                HELPER.get_field_value(tag, v?.slug, optValue);
          
            }
        });

        // Custom gate
        if (data?.settings[key]?.custom) {

            default_panel = data.settings.panel_options.fields[0].options.filter(function(item) {
                return item.default;
            });

            selected_panel = get_field_options(info, data, 'panel_options');

            active_panel = selected_panel[0]?.slug ? selected_panel[0].slug : default_panel[0].slug;

            panel_options_data = get_field_by_slug(data.settings.panel_options.fields[0].options, active_panel);

            var gateMaxH =
                typeof SlatFence !== 'undefined' && SlatFence.getGateMaxFenceHeightEl
                    ? SlatFence.getGateMaxFenceHeightEl()
                    : null;
            var step2MaxH = document.querySelector('[data-section="2"] [name="max_fence_height"]');
            var gateLimits = SlatFence.getCustomGateLimits({
                slug: i,
                panelOptionsData: panel_options_data,
                fenceHeight: $('[name="fence_height"]').val(),
                maxFenceHeight:
                    gateMaxH && gateMaxH.value
                        ? gateMaxH.value
                        : step2MaxH && step2MaxH.value
                          ? step2MaxH.value
                          : $('[name="max_fence_height"]').val(),
                tabInfo: getSelectedFenceData(i)?.tabInfo,
                fenceInfo: info,
                postWidth: FENCE.get(i, 'post')
            });

            var maxWidth = gateLimits.maxWidth;
            var maxLength = gateLimits.maxLength;

            // Gate details copy can be configured per fence (e.g. Slat in 4-SLAT.php).
            var gateDetailsTitle = data?.settings?.gate?.custom_gate_details?.title || '';
            var gateDetailsDescription = data?.settings?.gate?.custom_gate_details?.description || '';

            var tpl = $('script[data-type="custom-gate"]').text()
                .replace(/{{maxWidth}}/gi, maxWidth)
                .replace(/{{maxLength}}/gi, maxLength)
                .replace(/{{gateDetailsTitle}}/gi, gateDetailsTitle)
                .replace(/{{gateDetailsDescription}}/gi, gateDetailsDescription);

            $('.custom-gate').html('').html(tpl);

            if (typeof SlatFence !== 'undefined') {
                SlatFence.syncGateModalMaxFenceHeightVisibility();
            }

        }
    }

    //Get data based on key
    var filtered_data = info.filter(function(item) {
        return item.control_key == key;
    });

    removeDuplicateCloseBtn();

    HELPER.set_field_value(filtered_data);

    if (
        key === 'gate' &&
        data &&
        data.panel_group === 'a' &&
        typeof fcGlassPoolSyncGateWidthDropdown === 'function'
    ) {
        var gateSegGp = (info || []).filter(function(item) {
            return item && item.control_key === 'gate';
        })[0];
        var htStored = (gateSegGp?.settings?.fields || []).find(function(f) {
            return f && f.key === 'gate_hinge_type';
        });
        var htSlug =
            htStored?.val ||
            $('.fencing-container[data-key="gate"] [name="gate_hinge_type"]').attr('value') ||
            '';
        if (htSlug) {
            try {
                fcGlassPoolSyncGateWidthDropdown(getSelectedFenceData(i), htSlug);
            } catch (eGwSync) {}
        }
    }

    // Gate modal: mirror Step 2 Gate ONLY from segment/tab storage into modal checkbox.
    if (key === 'gate' && typeof checkGateOnly === 'function') {
        checkGateOnly();
    }

    // Gate modal: sync STD/custom + width from segment storage (not only #btn-gate first add flow).
    if (key === 'gate' && data?.settings?.gate?.custom) {
        checkGateWidthType();
        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(getSelectedFenceData()?.slug)) {
            SlatFence.syncGateModalCalculateButtonState();
        }
    }

    if (key === 'gate' && typeof SlatFence !== 'undefined') {
        SlatFence.syncGateModalMaxFenceHeightVisibility();
        if (SlatFence.isMainSlatSlug(getSelectedFenceData()?.slug)) {
            SlatFence.hydrateGateModalMaxFenceHeight();
            SlatFence.syncGateModalCalculateButtonState();
        } else {
            SlatFence.enableGateModalHeightQtyButtons();
        }
    }

    // Default load spacing step
/*    if( $(this).attr('id') == 'btn-edit_spacing' ) {
        spacing_width = $('.fencing-panel-spacing-number:not(:first)').find('span').html();
        spacing_step = (50 - Math.round(spacing_width)) * 2;

        console.log(spacing_step);
        $('[name="panel_option"]').attr('step', spacing_step).val(spacing_width).trigger('change');
    }
*/

    // First-time Add Gate only — skip auto-calculate when opening Gate Options for an existing gate.
    if ($(this).attr('id') == 'btn-gate' && $(this).hasClass('add-gate')) {

        checkGateWidthType();

        if (!$('[name="width"]').val() || $('[name="use_std"]').is(':checked')) {
            FENCE.call('disabledCustomGate');
            FENCE.call('calculateCustomGate');
        
            btnCalculate();
        }       

    }

    if (key === 'gate' && typeof fcSyncGateMoveControlsState === 'function') {
        fcSyncGateMoveControlsState();
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-fc-calculate', btnCalculate);

function updateCalculateButtonByStep2Completeness() {
    var ok = true;

    var box = document.querySelector('.measurement-box-number');
    if (box) {
        var raw = (box.value || '').toString().trim();
        var min = parseInt(box.getAttribute('data-min') || '', 10);
        var max = parseInt(box.getAttribute('data-max') || '', 10);
        var val = parseInt(raw || '', 10);
        var fdOk = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        var slatStdLocked =
            fdOk &&
            typeof SlatFence !== 'undefined' &&
            SlatFence.shouldLockStep2OverallForStdGateOnly(fdOk);
        if (slatStdLocked) {
            var dispOk = SlatFence.computeSlatGateOnlyStdOverallDisplayMm(fdOk);
            if (Number.isFinite(dispOk) && dispOk > 0) {
                val = dispOk;
                raw = String(dispOk);
            }
        }
        if (!raw || !Number.isFinite(val)) ok = false;
        if (
            !(slatStdLocked && Number.isFinite(val) && val > 0) &&
            Number.isFinite(min) &&
            val < min
        ) {
            ok = false;
        }
        if (
            !(slatStdLocked && Number.isFinite(val) && val > 0) &&
            Number.isFinite(max) &&
            val > max
        ) {
            ok = false;
        }
    }

    $('[data-section="2"] [data-action="change"] .form-control:visible').each(function() {
        if (!ok) return false;
        var el = this;
        var name = el.name || '';
        var raw = (el.value || '').toString().trim();
        if (!raw) {
            ok = false;
            return false;
        }

        if (name === 'panel_count') {
            var num = Number(raw);
            var min = parseInt(el.getAttribute('min') || '1', 10);
            var max = parseInt(el.getAttribute('max') || '100', 10);
            if (!Number.isFinite(num) || !Number.isInteger(num)) ok = false;
            if (Number.isFinite(min) && num < min) ok = false;
            if (Number.isFinite(max) && num > max) ok = false;
            if (!ok) return false;
        }

        if (el.validity && !el.validity.valid) {
            ok = false;
            return false;
        }
    });

    if (ok) {
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

    return ok;
}

/** After Step 2 keydown runs Calculate, skip keypress handlers that would submit again. */
var _fcStep2EnterHandledSubmit = false;

/**
 * Focusable Step 2 fields in document order (for Enter → next field).
 */
function getStep2FocusableList() {
    var $section = $('.js-fc-form-step[data-section="2"]').filter(':visible');
    if (!$section.length) {
        return $();
    }
    return $section.first().find('input, select, textarea').filter(function() {
        var el = this;
        if (!$(el).is(':visible') || el.disabled) {
            return false;
        }
        if ($(el).closest('.select2-container').length) {
            return false;
        }
        if ($(el).hasClass('fc-step2-gate-only-input')) {
            return false;
        }
        var t = (el.type || '').toLowerCase();
        if (t === 'hidden' || t === 'button' || t === 'submit' || t === 'reset') {
            return false;
        }
        if (t === 'checkbox' || t === 'radio') {
            return false;
        }
        return true;
    });
}

/** True for Step 2 text/number inputs (Enter advances to next empty field). */
function step2IsTextOrNumberInput(el) {
    if (!el || el.tagName !== 'INPUT') {
        return false;
    }
    var t = (el.type || 'text').toLowerCase();
    return t === 'text' || t === 'number' || t === 'tel' || t === '' || t === 'search';
}

/** Focus a Step 2 control (native input or Select2-backed select). */
function step2FocusField(el) {
    if (!el) {
        return;
    }
    el.focus();
    if (typeof el.select === 'function' && (el.type || '').toLowerCase() !== 'number') {
        try {
            el.select();
        } catch (err) {}
    }
    if (el.tagName === 'SELECT' && $(el).data('select2')) {
        try {
            $(el).select2('open');
        } catch (err2) {}
    }
}

/**
 * Next empty Step 2 field after `activeEl` (skips filled controls — e.g. jump to Overall Length).
 */
function step2FindNextEmptyField(activeEl) {
    var $list = getStep2FocusableList();
    var idx = $list.index(activeEl);
    if (idx === -1) {
        return null;
    }
    for (var i = idx + 1; i < $list.length; i++) {
        var candidate = $list.get(i);
        if (!candidate) {
            continue;
        }
        if (!step2FieldTrimmedValue(candidate)) {
            return candidate;
        }
    }
    return null;
}

/** Move focus to the next empty Step 2 field; returns true when focus moved. */
function step2FocusNextEmptyFrom(activeEl) {
    var nextEl = step2FindNextEmptyField(activeEl);
    if (!nextEl) {
        return false;
    }
    step2FocusField(nextEl);
    return true;
}

function step2FieldTrimmedValue(el) {
    if (!el) {
        return '';
    }
    return String(el.value != null ? el.value : '').trim();
}

/** Clear HTML5 constraint state so only `.fc-input-msg` is shown on Step 2. */
function fcClearStep2NativeValidity(el) {
    if (!el) {
        return;
    }
    try {
        el.setCustomValidity('');
    } catch (err) {}
}

_doc.on(
    'invalid',
    '[data-section="2"] input, [data-section="2"] select, [data-section="2"] textarea, .measurement-box-number',
    function(e) {
        e.preventDefault();
        fcClearStep2NativeValidity(this);
    }
);

_doc.on(
    'input change',
    '[data-section="2"] input, [data-section="2"] select, [data-section="2"] textarea, .measurement-box-number',
    function() {
        fcClearStep2NativeValidity(this);
    }
);

/** Visible inline error for this control's fc-input-container (Step 2 UI). */
function step2FieldHasVisibleInputError(el) {
    if (!el) {
        return false;
    }
    var $wrap = $(el).closest('.fc-input-container');
    var $msg = $wrap.find('.fc-input-msg').first();
    if ($msg.hasClass('fcim-show')) {
        var html = ($msg.html() || '').toString().replace(/&nbsp;/g, ' ').trim();
        if (html.length) {
            return true;
        }
    }
    return false;
}

/**
 * Slat infill uses the same measurement input as overall length but labels it "Opening Width".
 * Enter must never move focus to the next field from this control (only Calculate when step is complete).
 */
function step2IsOpeningWidthMeasurementField(el) {
    if (!el || !$(el).hasClass('measurement-box-number')) {
        return false;
    }
    var label = ($('.js-step-2-measurement-label').first().text() || '').trim();
    return label === 'Opening Width';
}

/** True when Enter should move to the next Step 2 field instead of calculating. */
function step2ShouldAdvanceToNextField(activeEl) {
    if (!activeEl || activeEl.tagName === 'TEXTAREA') {
        return false;
    }
    // When every Step 2 field is valid, Enter submits (Calculate), not tab-to-next.
    try {
        if (updateCalculateButtonByStep2Completeness()) {
            return false;
        }
    } catch (err) {}
    var raw = step2FieldTrimmedValue(activeEl);
    if (!raw) {
        return false;
    }
    if (step2FieldHasVisibleInputError(activeEl)) {
        return false;
    }
    if (step2IsOpeningWidthMeasurementField(activeEl)) {
        return false;
    }
    return !!step2FindNextEmptyField(activeEl);
}

function step2EnterNavigate(e) {
    var code = e.keyCode || e.which;
    if (code !== 13) {
        return;
    }

    var el = e.target;
    if (!el || !el.closest || el.closest('[data-section="2"]') === null) {
        return;
    }

    var section = el.closest('[data-section="2"]');
    if (!$(section).is(':visible')) {
        return;
    }

    if (el.tagName === 'TEXTAREA') {
        return;
    }

    var type = (el.type || '').toLowerCase();
    if (type === 'hidden' || type === 'button' || type === 'submit' || el.disabled) {
        return;
    }

    if (!step2FieldTrimmedValue(el)) {
        return;
    }

    if (step2FieldHasVisibleInputError(el)) {
        return;
    }

    // All Step 2 inputs valid: Enter acts like Calculate from any field.
    try {
        if (updateCalculateButtonByStep2Completeness()) {
            e.preventDefault();
            e.stopImmediatePropagation();
            _fcStep2EnterHandledSubmit = true;
            setTimeout(function() {
                _fcStep2EnterHandledSubmit = false;
            }, 0);
            step2TrySubmitCalculateFromEnter();
            return;
        }
    } catch (err) {}

    if (step2IsOpeningWidthMeasurementField(el)) {
        return;
    }

    // Text/number inputs: Enter → next empty field (e.g. Fence Height → Overall Length).
    if (!step2IsTextOrNumberInput(el)) {
        return;
    }

    var nextEmpty = step2FindNextEmptyField(el);
    if (!nextEmpty) {
        return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();
    step2FocusField(nextEmpty);
}

function validateStep2BeforeCalculate(showNativeTooltip = false) {
    var hasError = false;
    var firstInvalidEl = null;

    function markInvalid(el, message) {
        if (!el) return;
        var $wrap = $(el).closest('.fc-input-container');
        var $msg = $wrap.find('.fc-input-msg').first();
        $msg.addClass('fcim-show').html(message || 'Invalid value');
        hasError = true;
        if (!firstInvalidEl) firstInvalidEl = el;
    }

    // Overall measurement is always required in Step 2.
    var box = document.querySelector('.measurement-box-number');
    if (box && typeof fcValidateOverallLengthMm === 'function') {
        var oalResult = fcValidateOverallLengthMm({ el: box });
        if (!oalResult.valid) {
            markInvalid(box, oalResult.message);
        }
    }

    // Validate each visible Step 2 form-control.
    $('[data-section="2"] [data-action="change"] .form-control:visible').each(function() {
        var el = this;
        var name = el.name || '';
        var raw = (el.value || '').toString().trim();
        var $wrap = $(el).closest('.fc-input-container');
        var $msg = $wrap.find('.fc-input-msg').first();
        $msg.removeClass('fcim-show').html('');

        if (name === 'slat_gap') {
            try {
                var gapResult =
                    typeof SlatFence !== 'undefined' && SlatFence.validateSlatGapField
                        ? SlatFence.validateSlatGapField(el)
                        : { valid: !!raw, message: 'Please select a slat gap' };
                if (!gapResult.valid) {
                    markInvalid(el, gapResult.message || 'Please select a slat gap');
                }
            } catch (errGap) {
                if (!raw) {
                    markInvalid(el, 'Please select a slat gap');
                }
            }
            return;
        }

        if (name === 'slat_size') {
            try {
                var sizeResult =
                    typeof SlatFence !== 'undefined' && SlatFence.validateSlatSizeField
                        ? SlatFence.validateSlatSizeField(el)
                        : { valid: !!raw, message: 'Please select a slat size' };
                if (!sizeResult.valid) {
                    markInvalid(el, sizeResult.message || 'Please select a slat size');
                }
            } catch (errSize) {
                if (!raw) {
                    markInvalid(el, 'Please select a slat size');
                }
            }
            return;
        }

        if (!raw) {
            markInvalid(el, 'Please enter the amount');
            return;
        }

        if (name === 'panel_count') {
            if (!panelCountValidation({ target: el })) {
                hasError = true;
                if (!firstInvalidEl) firstInvalidEl = el;
            }
            return;
        }

        if (name === 'max_fence_height') {
            try { maxFenceHeightValidation?.call(el, { target: el }); } catch (err) {}
            try {
                var maxResult =
                    typeof SlatFence !== 'undefined' && SlatFence.validateMaxFenceHeightField
                        ? SlatFence.validateMaxFenceHeightField(el)
                        : { valid: !!raw, message: 'Please enter fence height' };
                if (!maxResult.valid) {
                    markInvalid(el, maxResult.message || 'Please enter fence height');
                }
            } catch (errMaxH) {
                if (!raw) {
                    markInvalid(el, 'Please enter fence height');
                }
            }
            fcClearStep2NativeValidity(el);
            return;
        }
    });

    if (hasError) {
        $('.btn-fc-calculate')
            .attr('disabled', 'disabled')
            .removeClass('btn-dark')
            .addClass('btn-light disabled');
        if (typeof fcHidePlannerStep3Results === 'function') {
            fcHidePlannerStep3Results();
        }
    }

    return !hasError;
}

/**
 * Run full Step 2 validation + `btnCalculate()` when every required field is ready (same as the
 * Calculate button). Used from Enter, Barr height/length changes, and fence-style pick on Step 1.
 *
 * @param {{ scrollToStep3?: boolean, showNativeTooltip?: boolean }} opts
 * @returns {boolean} true if Calculate ran
 */
function plannerTryAutoCalculateIfStep2Complete(opts) {
    opts = opts || {};
    var scrollToStep3 = opts.scrollToStep3 !== false;
    var showNativeTooltip = opts.showNativeTooltip === true;

    var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;

    try {
        var maxHEl = document.querySelector('[name="max_fence_height"]');
        if (maxHEl) {
            maxFenceHeightValidation?.call(maxHEl, { target: maxHEl });
        }
    } catch (err) {}
    try {
        var panelEl = document.querySelector('[name="panel_count"]');
        if (panelEl) {
            panelCountValidation?.call(panelEl, { target: panelEl });
        }
    } catch (err2) {}
    try {
        measurementBoxNumber();
    } catch (err3) {}

    var canCalculate = true;
    if (typeof SlatFence !== 'undefined' && SlatFence.canAutoCalculateFromStep2) {
        canCalculate = SlatFence.canAutoCalculateFromStep2({
            slug: fd?.slug,
            measurementEl: document.querySelector('.measurement-box-number'),
            panelCountEl: document.querySelector('[name="panel_count"]'),
            maxHeightEl: document.querySelector('[name="max_fence_height"]'),
            validatePanelCountFn: function(panelEl) {
                if (typeof SlatFenceInfill === 'undefined' || !SlatFenceInfill.validatePanelCountField) {
                    return true;
                }
                return SlatFenceInfill.validatePanelCountField(panelEl).valid;
            }
        });
    }

    if (!validateStep2BeforeCalculate(showNativeTooltip) || !canCalculate) {
        return false;
    }

    btnCalculate();
    if (scrollToStep3) {
        try {
            $('[data-section="3"]:visible').scrollTo(100, 57);
        } catch (e4) {}
    }
    return true;
}

/**
 * After a Step 2 dropdown change, run Calculate when all required fields are valid.
 * @param {{ scrollToStep3?: boolean }} opts
 * @returns {boolean}
 */
function step2TryAutoCalculateAfterDropdownChange(opts) {
    opts = opts || {};
    var section = document.querySelector('.js-fc-form-step[data-section="2"]');
    if (!section || !$(section).is(':visible')) {
        return false;
    }
    if (typeof plannerTryAutoCalculateIfStep2Complete !== 'function') {
        return false;
    }
    return plannerTryAutoCalculateIfStep2Complete({
        scrollToStep3: opts.scrollToStep3 === true,
        showNativeTooltip: opts.showNativeTooltip === true
    });
}

/**
 * Run validation + Calculate from Step 2 when Enter is pressed (same rules as the Calculate button).
 */
function step2TrySubmitCalculateFromEnter() {
    plannerTryAutoCalculateIfStep2Complete({
        scrollToStep3: true,
        showNativeTooltip: false
    });
}

function btnCalculate() {
    if (!validateStep2BeforeCalculate(false)) {
        return;
    }

    var fdPreCalc = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    var isGlassPoolFence = fdPreCalc && fdPreCalc.data && fdPreCalc.data.panel_group === 'a';
    var skipEarlyOalForSlatGoCustom =
        fdPreCalc &&
        typeof SlatFence !== 'undefined' &&
        SlatFence.isStep2GateOnlyCustomGate(fdPreCalc);
    if (
        !skipEarlyOalForSlatGoCustom &&
        ($('.fencing-panel-gate:visible').length ||
            $('.raked-panel-container:visible').length ||
            isGlassPoolFence)
    ) {
        updateOverAllLength();
        if (fdPreCalc && typeof fcSyncGateMinOverallLength === 'function') {
            try {
                fcSyncGateMinOverallLength(fdPreCalc);
            } catch (eGateMinOal) {}
        }
    }

    var $plannerStrip =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : $('.fc-planner-page').find(FENCES.el.tabArea).first().children('.fencing-tab');
    var $selPlannerTab = $plannerStrip.filter('.fencing-tab-selected');
    var tab = $plannerStrip.length ? $plannerStrip.index($selPlannerTab) : $('.fencing-tab.fencing-tab-selected').index();

    var box = $('.measurement-box-number'),
        last = box.attr('data-last'),
        length = parseInt(box.val()),
        custom_fence_tabs = localStorage.getItem('custom_fence-' + tab),
        custom_fence_tab;
    try {
        custom_fence_tab = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];
    } catch (e) {
        custom_fence_tab = [];
    }

    box.attr('data-last', box.val());

    if (
        fdPreCalc &&
        typeof SlatFence !== 'undefined' &&
        SlatFence.isStep2GateOnlyCustomGate(fdPreCalc)
    ) {
        try {
            SlatFence.commitStep2GateOnlyCustomFromOverall(fdPreCalc);
        } catch (eCommitGoCg) {}
        if (
            $('.fencing-panel-gate:visible').length ||
            $('.raked-panel-container:visible').length ||
            isGlassPoolFence
        ) {
            try {
                updateOverAllLength();
            } catch (eOalPostCommit) {}
        }
    }

    if (!custom_fence_tab || !custom_fence_tab[0]) {
        var fdFillEarly = getSelectedFenceData(undefined, tab);
        if (fdFillEarly.tabInfo && fdFillEarly.tabInfo[0]) {
            custom_fence_tab = fdFillEarly.tabInfo;
        }
    }
    if (!custom_fence_tab || !custom_fence_tab[0]) {
        try {
            FENCE.call('set_cutom_fence_data', { force: true });
            custom_fence_tabs = localStorage.getItem('custom_fence-' + tab);
            custom_fence_tab = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];
        } catch (eInitTab) {
            custom_fence_tab = [];
        }
    }
    if (!custom_fence_tab || !custom_fence_tab[0]) {
        return;
    }

    // Persist Step 2 + calculateValue BEFORE layout render (calculate_fences reads localStorage).
    const fd = getSelectedFenceData(undefined, tab);
    const prevFields =
        typeof fcStep2RestoreFieldsForStyle === 'function'
            ? fcStep2RestoreFieldsForStyle(custom_fence_tab[0], fd.slug)
            : custom_fence_tab?.[0]?.fields || [];

    if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(fd.slug)) {
        try {
            SlatFence.persistSlatGapFromStep2(
                tab,
                fd.slug,
                $('[data-section="2"] [name="slat_gap"]').val()
            );
            SlatFence.persistSlatSizeFromStep2(
                tab,
                fd.slug,
                $('[data-section="2"] [name="slat_size"]').val()
            );
            var mhCalc = document.querySelector('[data-section="2"] [name="max_fence_height"]');
            if (mhCalc && !mhCalc.disabled && mhCalc.value) {
                if (SlatFence.isStep2GateOnlyActive(fd)) {
                    SlatFence.commitStep2GateHeightForGateOnly(fd);
                } else {
                    SlatFence.persistMaxFenceHeightFromStep2(tab, fd.slug, mhCalc.value);
                }
            }
        } catch (eGap) {}
    }

    let nextFields =
        typeof fcCollectStep2FieldsFromDom === 'function'
            ? fcCollectStep2FieldsFromDom()
            : $('[data-section="2"] [data-action="change"] .form-control').serializeArray();

    if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(fd.slug)) {
        var mhStep2 = document.querySelector('[data-section="2"] [name="max_fence_height"]');
        if (mhStep2 && !mhStep2.disabled && mhStep2.value) {
            var mhIdx = nextFields.findIndex(function(f) {
                return f && f.name === 'max_fence_height';
            });
            if (mhIdx >= 0) {
                nextFields[mhIdx].value = mhStep2.value;
            } else {
                nextFields.push({ name: 'max_fence_height', value: mhStep2.value });
            }
        }
    }

    $('[data-section="2"] [data-action="change"] input[type="radio"]:checked').each(function() {
        const name = this.name;
        const value = this.value;
        if (!name) return;
        const idx = nextFields.findIndex(f => f?.name === name);
        if (idx >= 0) {
            nextFields[idx].value = value;
        } else {
            nextFields.push({ name, value });
        }
    });

    if (typeof SlatFence !== 'undefined' && typeof SlatFence.normalizeStep2FieldsBeforeSave === 'function') {
        nextFields = SlatFence.normalizeStep2FieldsBeforeSave({
            slug: fd?.slug,
            prevFields: prevFields,
            nextFields: nextFields,
            maxHeightEl: document.querySelector('[name="max_fence_height"]')
        });
    }

    custom_fence_tab[0].fields = nextFields;
    custom_fence_tab[0].fieldsByStyle = {
        ...(custom_fence_tab[0].fieldsByStyle || {}),
        [fd.slug]: nextFields
    };
    custom_fence_tab[0].isCalculate = 1;
    custom_fence_tab[0].calculateValue = length;

    var slugKey = fd.slug;
    custom_fence_tab[0].measurementByStyle = custom_fence_tab[0].measurementByStyle || {};
    custom_fence_tab[0].measurementByStyle[slugKey] = {
        val: box.val() != null ? String(box.val()) : '',
        dataLast: box.attr('data-last') || '',
        dataPrevGateOnlyMbn: box.attr('data-prev-gate-only-mbn') || ''
    };
    custom_fence_tab[0].calculateValueByStyle = custom_fence_tab[0].calculateValueByStyle || {};
    custom_fence_tab[0].calculateValueByStyle[slugKey] = length;
    custom_fence_tab[0].isCalculateByStyle = custom_fence_tab[0].isCalculateByStyle || {};
    custom_fence_tab[0].isCalculateByStyle[slugKey] = custom_fence_tab[0].isCalculate;
    custom_fence_tab[0].gateOnlyByStyle = custom_fence_tab[0].gateOnlyByStyle || {};
    var gdTab = (fd.info || []).filter(function(item) {
        return item.control_key === 'gate';
    })[0];
    custom_fence_tab[0].gateOnlyByStyle[slugKey] = !!(
        (gdTab && gdTab.settings && gdTab.settings.gateOnly) ||
        custom_fence_tab[0].gateOnly
    );

    localStorage.setItem('custom_fence-' + tab, JSON.stringify(custom_fence_tab));
    $('.d-oaw').html(HELPER.number_format(length));

    if (typeof fcMarkStep2Committed === 'function') {
        fcMarkStep2Committed();
    }

    if ($('.fc-planner-page').length) {
        var $step3 = $('.js-fc-form-step[data-section="3"]');
        $step3.removeClass('fc-d-none').css('display', '');
        if (
            typeof fcShouldShowPlannerStep3Skeleton === 'function' &&
            fcShouldShowPlannerStep3Skeleton() &&
            typeof fcShowPlannerStep3Skeleton === 'function'
        ) {
            fcShowPlannerStep3Skeleton();
        }
    }

    // update_custom_fence_tab ends with load_fencing_items (single render pass).
    FENCE.call('update_custom_fence_tab');

    setTimeout(function() {
        try {
            if (typeof fcSyncPanelControlsGateOnlyDisabled === 'function') {
                fcSyncPanelControlsGateOnlyDisabled();
            }
        } catch (eGoPanel) {}
    }, 120);

    $('.js-fc-form-step[data-section="3"]').fadeIn(200);
    // Never show section tabs while Project Options (step 4) is active — btnCalculate runs from
    // many code paths and an unconditional .show() overrides fc-section-step .hide() (flicker).
    var $plannerTabs = $('.fc-planner-page .fencing-tabs-container');
    if ($plannerTabs.length) {
        var onProjectOptions =
            HELPER.getSearchParams('tab') === '2' || $('.fc-planner-page .fc-step-4').is(':visible');
        if (onProjectOptions) {
            $plannerTabs.hide();
        } else {
            $plannerTabs.show();
        }
    }

    if ($('.fc-planner-page').length) {
        HELPER.hideDeleteSectionBtn();
    }

    setTimeout(function() {

        $('.fc-btn-next-step').attr('disabled', 'disabled');
        $('.fc-btn-next-step').removeAttr('disabled');

        if (typeof fcSyncGateMoveControlsState === 'function') {
            fcSyncGateMoveControlsState();
        }

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
}

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-fc-calculate', btnCalculate_v2);

function btnCalculate_v2() {
    var $step3 = $('.fc-planner-page .js-fc-form-step[data-section="3"]:visible');
    if (!$step3.length) {
        return;
    }
    $step3.scrollTo(100, 57);
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

/* Step 2 "Important" dialog (mobile). The notice copy is per fence style and is rewritten in
   place as the style changes, so the dialog reads it from the live panel on every show rather
   than holding its own copy that would drift. The panel is notes only - its IMPORTANT heading and
   subtitle sit outside .alert-gray, and the dialog header carries that pair itself. */
_doc.on('show.bs.modal', '#fc-step2-important-modal', fcStep2ImportantModalShow);

function fcStep2ImportantModalShow() {
    var $body = $(this).find('.js-fc-step2-important-body'),
        $source = $('[data-section="2"]').find('.fc-step2-notes__body').first();

    if (!$body.length) {
        return;
    }

    if (!$source.length) {
        $body.html('<p class="fc-modal__lead mb-0">No notes for this fence style.</p>');
        return;
    }

    $body.html($source.html());
}

//----------------------------------------------------------------------------------

/* Download wizard "We'll email you the plans" dialog (mobile). Same trick as the Step 2 notice:
   the panel is hidden below md but still in the DOM, so the dialog reads its copy on show rather
   than holding a second copy of the same sentence that would drift. */
_doc.on('show.bs.modal', '#fc-download-intro-modal', fcDownloadIntroModalShow);

function fcDownloadIntroModalShow() {
    var $body = $(this).find('.js-fc-download-intro-body'),
        $source = $('#submit-modal .fc-download-intro__text').first();

    if (!$body.length) {
        return;
    }

    if (!$source.length) {
        $body.html('<p class="fc-modal__lead mb-0">Enter your details and we&rsquo;ll send your plans through.</p>');
        return;
    }

    $body.html($source.clone().removeClass('fc-download-intro__text').addClass('fc-modal__lead'));
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-zoom-fence', fcZoomFence);

function fcZoomFence(e) {
    e?.preventDefault();
    var _this = $(this),
        zoom = _this.attr('data-zoom');
    if (zoom === 'out' && step <= fcZoomMinStep) {
        return;
    }
    HELPER.zooming(zoom);
}

//----------------------------------------------------------------------------------

/**
 * Gate Options modal: Custom width Calculate (Slat + Barr-style custom gate).
 * Validates gate height (Slat) + custom width, persists, closes modal, runs planner Calculate.
 */
function fcGateModalCalculateSubmit(e) {
    e?.preventDefault?.();

    if (!$('.custom-gate').length) {
        return false;
    }

    var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;

    if (fd && typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(fd.slug)) {
        var ghEl = SlatFence.getGateMaxFenceHeightEl();
        if (ghEl && !ghEl.disabled) {
            var ghResult = SlatFence.validateMaxFenceHeightField(ghEl);
            var $ghMsg = $(ghEl).closest('.fc-input-container').find('.fc-input-msg').first();
            $ghMsg.removeClass('fcim-show').html('');
            if (!ghResult.valid) {
                $ghMsg.addClass('fcim-show').html(ghResult.message || 'Please enter gate height');
                try {
                    ghEl.focus();
                } catch (eFocusGh) {}
                return false;
            }
            try {
                SlatFence.persistGateMaxFenceHeightToGateFields(fd.tab, fd.slug, ghEl.value);
            } catch (ePersistGh) {}
            try {
                SlatFence.mirrorGateModalHeightToStep2(fd);
            } catch (eMirGh) {}
        }
    }

    var isStd = $('[name="use_std"]').is(':checked');
    if (!isStd) {
        var widthEl = document.querySelector('.custom-gate [name="width"]');
        if (widthEl) {
            var $wWrap = $(widthEl).closest('.fc-input-container');
            var $wMsg = $wWrap.find('.fc-input-msg').first();
            var wResult = SlatFence.validateGateModalCustomWidthField(widthEl);

            $wMsg.removeClass('fcim-show').html('');
            if (!wResult.valid) {
                $wMsg.addClass('fcim-show').html(wResult.message);
                try {
                    widthEl.focus();
                } catch (eFocusW) {}
                try {
                    SlatFence.syncGateModalCalculateButtonState();
                } catch (eBtnW) {}
                return false;
            }
        }

        if (!SlatFence.canSubmitGateModalCalculate()) {
            try {
                SlatFence.syncGateModalCalculateButtonState();
            } catch (eBtnSync) {}
            return false;
        }
    }

    try {
        FENCE.call('update_custom_fence_gate');
    } catch (eUpdG) {}

    if (fd && typeof fcSyncGateMinOverallLength === 'function') {
        try {
            fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : fd;
        } catch (eFd) {}
        try {
            fcSyncGateMinOverallLength(fd, { persist: true });
        } catch (eSyncOal) {}
    }

    try {
        updateOverAllLength();
    } catch (eOal) {}
    try {
        FENCE.call('calculateCustomGate');
    } catch (eCcg) {}
    try {
        FCModal.close('#fc-control-modal');
    } catch (eClose) {}
    $('.fc-btn-active').removeClass('fc-btn-active');
    try {
        if (typeof checkGateOnly === 'function') {
            checkGateOnly();
        }
    } catch (eGo) {}
    try {
        btnCalculate();
    } catch (eCalc) {}

    return true;
}

_doc.on(
    'click',
    '.custom-gate .fc-gate-modal-calculate-btn, .custom-gate .fc-gate-modal-custom-width-section button[type="button"]',
    fcGateModalCalculateSubmit
);

_doc.on('click', '.fc-input-group button', fcInputGroup_button);

function fcInputGroup_button(e) {
    if ($(e?.target).closest('.custom-gate').length) {
        fcGateModalCalculateSubmit(e);
        return;
    }
    updateOverAllLength();
    FENCE.call('calculateCustomGate');
    FCModal.close();
    checkGateOnly();
    btnCalculate();
}

//----------------------------------------------------------------------------------

_doc.on('click', '[name="gate_hinge_position"]', fcGateHingeCalculationFix);
function fcGateHingeCalculationFix() {
        btnCalculate();
        btnCalculate();
}

//----------------------------------------------------------------------------------

_doc.on('change', '[type="dropdown_option"]', dropdownOption);

function dropdownOption() {
    updateOverAllLength();
    btnCalculate();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-select-post', fcSelectPost);

function fcSelectPost() {
    var _this = $(this),
        modal_key = $('.fencing-container').attr('data-key'),
        fc_form_field = _this.closest(".fc-form-field");

    if (_this.attr('data-key') && _this.attr('data-key') !== "undefined") {
        modal_key = _this.attr('data-key');
    }

    // Gate modal selections are persisted via `update_custom_fence_gate()`.
    // Calling the generic `update_custom_fence('gate')` here can corrupt gate fields
    // (notably `use_std` checkbox) and reset custom gate width back to STD.
    if (modal_key === 'gate') {
        return;
    }

    FENCE.call('update_custom_fence', modal_key);
    FENCE.call('updateOverallPosts');

    var slug = String(_this.attr('data-slug') || '');
    var fieldName = String(fc_form_field.attr('name') || '');

    if (fieldName === 'left_raked' || fieldName === 'right_raked') {
        if (typeof fcApplyStepUpPanelSelection === 'function') {
            try {
                fcApplyStepUpPanelSelection(fieldName);
            } catch (eStepUp) {}
        }
        return;
    }

    // Recalculate AOW on post change.
    // - Range options use slugs like yes-post / no-post (substring "post").
    // - Post *type* image options (Starting / End / Mid) use slugs like opt-1 / opt-2 — they must still refresh the BOM.
    if (
        slug.includes('post')
        || fieldName === 'post_option'
        || _this.closest('.fencing-modal-area').hasClass('btn-recalculate')
    ) {
        var data = {};
        data.removePost = 1;
        updateOverAllLength(data);
        try {
            var fdPost = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
            if (fdPost && FENCE.isGateMinOalStyle(fdPost.slug)) {
                FENCE.syncGateOverallOnPostChange(fdPost, { persist: true });
            }
        } catch (ePostSync) {}
        btnCalculate();
    }

    $('[data-section="3"]').scrollTo(100, 57);
}

//----------------------------------------------------------------------------------

_doc.on('change', '.fencing-input-range [type="range"]', fcSelectPanelRange);

function fcSelectPanelRange() {
    var _this = $(this),
        modal_key = $('.fencing-container').attr('data-key');

    FENCE.call('update_custom_fence', modal_key);
    FENCE.call('updateOverallPosts');

    var dataRange = { removePost: 1 };
    updateOverAllLength(dataRange);
    try {
        var fdRange = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (fdRange && FENCE.isGateMinOalStyle(fdRange.slug)) {
            FENCE.syncGateOverallOnPostChange(fdRange, { persist: true });
        }
    } catch (eSlatRangeOal) {}
    btnCalculate();

    $('[data-section="3"]').scrollTo(100, 57);
}

//----------------------------------------------------------------------------------


_doc.on('click', '.fc-select-color', fcSelectColor);

function fcSelectColor() {
    update_color_options();
    try {
        if (typeof fcApplyPlannerUpdateDisabledFromColors === 'function') {
            fcApplyPlannerUpdateDisabledFromColors();
        }
    } catch (err) {}
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

// FCModal is a hand-rolled fadeIn/fadeOut, not a Bootstrap modal, so Escape does not close
// #submit-modal for free — neither the planner download wizard nor the project-plan editor.
// Routed through the close button so the history push in submitModal_jsFencingModalClose
// still runs.
_doc.on('keydown', fcSubmitModalKeydown);

function fcSubmitModalKeydown(e) {
    if (e.key !== 'Escape' && e.key !== 'Esc') {
        return;
    }

    var $modal = $('#submit-modal:visible');
    if (!$modal.length) {
        return;
    }

    $modal.find('.js-fencing-modal-close').first().trigger('click');
}

//----------------------------------------------------------------------------------

/**
 * Download-plans wizard floating labels: `.is-filled` on the field group keeps the label
 * floated for fields that hold a value. Runs on load, on modal open, and after programmatic
 * fills (restoreFormData, Google address autocomplete) - none of which fire input events.
 */
function fcSyncDownloadPlansFloatingLabels() {
    $('.fencing-modal--download-plans .fc-label-group').each(function() {
        var $group = $(this);
        var value = String($group.find('.form-control').first().val() || '').trim();
        $group.toggleClass('is-filled', value !== '');
    });
}

_doc.on(
    'input change keyup',
    '.fencing-modal--download-plans .fc-label-group .form-control',
    fcDownloadPlansFloatingLabelInput
);

function fcDownloadPlansFloatingLabelInput() {
    var $group = $(this).closest('.fc-label-group');
    $group.toggleClass('is-filled', String($(this).val() || '').trim() !== '');
}

$(fcSyncDownloadPlansFloatingLabels);

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-form-step', fcBtnFormStep);

function fcBtnFormStep() {
    var _this = $(this),
        move = _this.attr('data-move'),
        tab = HELPER.getSearchParams('tab');

    if (!_this.hasClass('fc-btn-next')) {

        $('#submit-modal .fc-form-plan').hide();
        $('#submit-modal .fc-download-footer-actions').hide();
        $('#submit-modal [data-formtab="' + move + '"]').show();
        history.pushState({}, '', `?tab=${tab}&form=${move}`);
    }

    /* Done is left enabled: it is gated by validation now, which names what is missing, where
       disabling it only stopped the click and explained nothing. The selector reached every
       [type=submit] in the form - the planner's own UPDATE among them - so this stops that too. */
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

    function applyPlannerTabMove() {
        if (
            (move === '2' || move === 2) &&
            $('.js-fc-form-step[data-section="3"]').is(':visible') &&
            $('.fencing-panel-gate').length
        ) {
            try {
                FENCE.call('update_custom_fence_gate');
            } catch (ePersistGate) {}
        }

        $('.fc-section-step').hide();
        $('[data-tab="' + move + '"]').show();

        $('.fencing-container').removeClass('w-side-section');
        if (move < 2) {
            $('.fencing-container').addClass('w-side-section');
        }

        history.pushState({}, '', `?tab=${move}`);

        HELPER.tabContainerScroll();

        if (move == 2) {
            loadColorOptions();
            setTimeout(function() {
                if (typeof window.fcRefreshColorOptionsSlick === 'function') {
                    window.fcRefreshColorOptionsSlick();
                }
                try {
                    if (typeof fcApplyPlannerUpdateDisabledFromColors === 'function') {
                        fcApplyPlannerUpdateDisabledFromColors();
                    }
                } catch (err) {}
            }, 120);
        }

        if (move == 1) {
            $('.fencing-tab.fencing-tab-selected:visible').trigger('click');
            if (typeof window.fcRefreshFencingStylesSlick === 'function') {
                requestAnimationFrame(function() {
                    window.fcRefreshFencingStylesSlick();
                });
            }
        }

        $('html').scrollTo(100, 0);
    }

    // Leaving Section Details: persist Step 2, then always land on Section 1 before Plan Options.
    if (move === '2' || move === 2) {
        try {
            if (typeof fcPersistStep2Immediate === 'function') {
                fcPersistStep2Immediate();
            } else {
                FENCE.call('set_cutom_fence_data');
            }
        } catch (errPersist) {}

        if (typeof fcPlannerActivateSectionOneThen === 'function') {
            fcPlannerActivateSectionOneThen(applyPlannerTabMove);
        } else {
            applyPlannerTabMove();
        }
        return;
    }

    applyPlannerTabMove();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-form-check input', fcFormCheck_input);

function fcFormCheck_input() {
    var _this = $(this);
    var type = _this.attr('type');
    var $otherProducts = _this.closest('#submit-modal .fc-other-products');

    if (!$otherProducts.length) {
        return;
    }

    if (type === 'checkbox') {
        $otherProducts.find('.fc-form-check-empty').removeClass('fc-selected');
        $otherProducts.find('input[name="nothing_extra"]').prop('checked', false);
    } else if (type === 'radio' && _this.attr('name') === 'nothing_extra') {
        $otherProducts.find('.fc-form-check-img').not('.fc-form-check-empty').removeClass('fc-selected');
        $otherProducts.find('input[name="extra[]"]').prop('checked', false);
    }

    var check = _this.is(':checked');

    _this.closest('.fc-form-check-img').removeClass('fc-selected');

    if (check) {
        _this.closest('.fc-form-check-img').addClass('fc-selected');
    }

    /* The rule for this group hangs off NIL, so ticking a tile never re-checks it and the pill would
       sit there on a question that is now answered. Only once a message exists - so a first tick
       never raises one on a customer who has not asked to move on yet. */
    try {
        var groupValidator = _this.closest('form').data('validator');
        var nilEl = $otherProducts.find('input[name="nothing_extra"]')[0];
        if (groupValidator && nilEl && $otherProducts.find('label.error').length) {
            groupValidator.element(nilEl);
        }
    } catch (eGv) {}

    if (typeof fcPersistOtherProductsToProjectPlans === 'function') {
        fcPersistOtherProductsToProjectPlans();
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '[name="color_options"]', color_options);

function color_options() {
    var $scope = $('#fc-planning-form .fc-step-4');
    var $groups = $scope.find('.fc-color-options');
    var complete = $groups.length > 0 && fcPlannerColorOptionGroupsComplete($scope);
    $('.fc-btn-create-plan').prop('disabled', !complete);
    try {
        if (typeof fcApplyPlannerUpdateDisabledFromColors === 'function') {
            fcApplyPlannerUpdateDisabledFromColors();
        }
    } catch (err) {}
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-create-plan', fcBtnCreatePlan);

function fcBtnCreatePlan() {
    // Push param in URL tab={tab}
    history.pushState({}, '', `?tab=2&form=1`);

    // Show form modal
    $('#submit-modal .fc-form-plan').hide();
    $('#submit-modal .fc-download-footer-actions').hide();

    // Show the first step of the form
    $('#submit-modal [data-formtab="1"]').show();

    if (typeof fcSyncDownloadPlansFloatingLabels === 'function') {
        fcSyncDownloadPlansFloatingLabels();
    }

    // Land the caret in the first field instead of leaving focus on the page behind.
    // Pointer-coarse layouts are skipped: focusing there springs the keyboard open over
    // a modal that is already full height.
    if (window.matchMedia && window.matchMedia('(min-width: 992px)').matches) {
        setTimeout(function() {
            $('#submit-modal.fencing-modal--download-plans [data-formtab="1"] [name="name"]').trigger('focus');
        }, 260);
    }
}

//----------------------------------------------------------------------------------

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
    if (e.which === 13) {
        if (_fcStep2EnterHandledSubmit) {
            e?.preventDefault();
            return;
        }
        e?.preventDefault();
        if (step2ShouldAdvanceToNextField(this)) {
            step2FocusNextEmptyFrom(this);
            return;
        }
        if (validateStep2BeforeCalculate(false) && !$('.btn-fc-calculate').attr('disabled')) {
            btnCalculate();
            $('[data-section="3"]:visible').scrollTo(100, 57);
        }
    }
}

//----------------------------------------------------------------------------------

// Slat Fence: allow Enter on Max Fence Height to trigger Calculate
// (only when Overall Length is filled/valid).
_doc.on('keypress', '[name="max_fence_height"]', maxFenceHeight_kp);

function maxFenceHeight_kp(e) {
    if (e.which != 13) return;

    if (_fcStep2EnterHandledSubmit) {
        e?.preventDefault();
        return;
    }

    var fd = getSelectedFenceData();
    if (!SlatFence.isSlatLike(fd?.slug)) return;

    if (step2ShouldAdvanceToNextField(this)) {
        e?.preventDefault();
        step2FocusNextEmptyFrom(this);
        return;
    }

    e?.preventDefault();

    // Refresh tooltips (show Max Fence Height + Overall Length state).
    try { maxFenceHeightValidation?.call(this, { target: this }); } catch (err) {}
    try { measurementBoxNumber?.(); } catch (err) {}

    var canCalculate = SlatFence.canAutoCalculateFromStep2({
        slug: fd?.slug,
        measurementEl: document.querySelector('.measurement-box-number'),
        panelCountEl: document.querySelector('[name="panel_count"]'),
        maxHeightEl: document.querySelector('[name="max_fence_height"]'),
        validatePanelCountFn: function(panelEl) {
            return SlatFenceInfill.validatePanelCountField(panelEl).valid;
        }
    });

    if (SlatFence.isMainSlatSlug(fd.slug) && SlatFence.isStep2GateOnlyActive(fd)) {
        try {
            SlatFence.commitStep2GateHeightForGateOnly(fd);
        } catch (eCommitGh) {}
    }

    if (validateStep2BeforeCalculate(false) && canCalculate) {
        btnCalculate();
        $('[data-section="3"]:visible').scrollTo(100, 57);
    }
}

// Slat Infill: allow Enter on panel count to trigger Calculate
_doc.on('keypress', '[name="panel_count"]', panelCount_kp);

function panelCount_kp(e) {
    if (e.which != 13) return;

    if (_fcStep2EnterHandledSubmit) {
        e?.preventDefault();
        return;
    }

    var fd = getSelectedFenceData();
    if (fd?.slug !== 'slat_fence_infill') return;

    if (step2ShouldAdvanceToNextField(this)) {
        e?.preventDefault();
        step2FocusNextEmptyFrom(this);
        return;
    }

    e?.preventDefault();

    try { panelCountValidation?.call(this, { target: this }); } catch (err) {}
    try { measurementBoxNumber?.(); } catch (err) {}
    try {
        const maxHEl = document.querySelector('[name="max_fence_height"]');
        if (maxHEl) maxFenceHeightValidation?.call(maxHEl, { target: maxHEl });
    } catch (err) {}

    var canCalculate = SlatFence.canAutoCalculateFromStep2({
        slug: fd?.slug,
        measurementEl: document.querySelector('.measurement-box-number'),
        panelCountEl: document.querySelector('[name="panel_count"]'),
        maxHeightEl: document.querySelector('[name="max_fence_height"]'),
        validatePanelCountFn: function(panelEl) {
            return SlatFenceInfill.validatePanelCountField(panelEl).valid;
        }
    });

    if (validateStep2BeforeCalculate(false) && canCalculate) {
        btnCalculate();
        $('[data-section="3"]:visible').scrollTo(100, 57);
    }
}

//----------------------------------------------------------------------------------

_doc.on('keypress', '[input-type="number"]', inputType_number_2);

function inputType_number_2(e) {
    if (e.which == 13) {
        if (_fcStep2EnterHandledSubmit) {
            e?.preventDefault();
            return;
        }
        if (step2ShouldAdvanceToNextField(this)) {
            e?.preventDefault();
            step2FocusNextEmptyFrom(this);
            return;
        }
        var _this = $(this);
        if (_this.closest('.custom-gate').length) {
            if (_this.attr('name') === 'width' && $('[name="use_std"]').is(':checked')) {
                e?.preventDefault();
                return;
            }
            fcGateModalCalculateSubmit(e);
            e?.preventDefault();
            return;
        }
        _this.closest('.fc-input-container').find('[type="button"]').trigger('click');
        e?.preventDefault();
    }
}

_doc.on('keydown', '.custom-gate [name="gate_max_fence_height"]', function(e) {
    if (e.key !== 'Enter' && e.which !== 13) {
        return;
    }
    e.preventDefault();
    fcGateModalCalculateSubmit(e);
});

//----------------------------------------------------------------------------------

_doc.on('keypress', '.no-enter', noEnter);

function noEnter(e) {
    if (e.keyCode == 13) {
        event.preventDefault();
    }
}


//----------------------------------------------------------------------------------

_doc.on('keypress', '.text-only', textOnly);

function textOnly(e) {
    var chr = String.fromCharCode(e.which);
    if ("qwertyuioplkjhgfdsazxcvbnmQWERTYUIOPLKJHGFDSAZXCVBNM- ".indexOf(chr) < 0)
    return false;
}

//----------------------------------------------------------------------------------

_doc.on('keypress', '.numeric-only', numericOnly);

function numericOnly(e) {
    var chr = String.fromCharCode(e.which);
    if ("1234567890".indexOf(chr) < 0)
    return false;
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

// Step 2: track uncommitted edits — values persist only on Calculate or Enter-submit.
_doc.on(
    'input change',
    '[data-section="2"] [data-action="change"] .form-control, ' +
        '[data-section="2"] .fc-step2-height-slot .form-control, ' +
        '[data-section="2"] .fc-step2-height-slot .fc-max-fence-height-input, ' +
        '[data-section="2"] .fc-step2-pair-slot .form-control, ' +
        '.measurement-box-number',
    function() {
        if (typeof fcMarkStep2DomDirty === 'function') {
            fcMarkStep2DomDirty();
        }
    }
);
_doc.on(
    'change select2:select',
    '[data-section="2"] [data-action="change"] select, [data-section="2"] .fc-step2-height-slot select',
    function() {
        if (typeof fcMarkStep2DomDirty === 'function') {
            fcMarkStep2DomDirty();
        }
        var name = (this.name || '').toString();
        if (name === 'slat_gap' || name === 'slat_size') {
            return;
        }
        if (name === 'fence_height') {
            step2BarrTryAutoCalculateIfReady();
            return;
        }
        step2TryAutoCalculateAfterDropdownChange({
            scrollToStep3: false,
            showNativeTooltip: false
        });
    }
);
_doc.on('change', '[data-section="2"] [data-action="change"] input[type="radio"]', function() {
    if (typeof fcMarkStep2DomDirty === 'function') {
        fcMarkStep2DomDirty();
    }
});
_doc.on('input change', '.measurement-box-number', function() {
    try {
        measurementBoxNumber();
    } catch (eMb) {}
});

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

    if (_this.attr('data-key') && _this.attr('data-key') !== "undefined") {
        modal_key = _this.attr('data-key');
    }

    FENCE.call('update_custom_fence', modal_key);

    // Gate modal: width / hinge etc. only hit `update_custom_fence` (fields merge). Also persist
    // top-level gate settings (incl. gateOnly) so Gate ONLY survives STD width changes + reload.
    if (modal_key === 'gate') {
        try { FENCE.call('update_custom_fence_gate'); } catch (eGate) {}
    }

    var rakedSide = _this.closest('.fc-form-field').attr('name');

    if (rakedSide == 'right_raked') {
        if (typeof fcFenceDiagramScrollCenter === 'function') {
            fcFenceDiagramScrollCenter('.panel-post:last', 300);
        } else {
            $('.fencing-display-result').scrollCenter('.panel-post:last', 300);
        }
    }

    if (rakedSide == 'left_raked') {
        if (typeof fcFenceDiagramScrollCenter === 'function') {
            fcFenceDiagramScrollCenter('.panel-post:first', 300);
        } else {
            $('.fencing-display-result').scrollCenter('.panel-post:first', 300);
        }
    }

    if (_this.parents('.js-fencing-modal').length && !fcSuppressControlModalCloseOnFcSelectChange) {
        FCModal.close();
    }
}

//----------------------------------------------------------------------------------

_doc.on('change', '.fc-select-option', fcSelectOption_v2);

function fcSelectOption_v2() {

    // RC BUG - 061725
    // updateGateOnly(false);
    checkGateOnly();
    updateOverAllLength();

    var side = ['left_raked', 'right_raked'];

    $('.raked-panel').html('');

    FENCE.call('update_raked_panels', side);

    if (typeof fcSyncStepUpPanelsStorageFromSides === 'function') {
        try {
            fcSyncStepUpPanelsStorageFromSides();
        } catch (eSyncStep) {}
    }

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

_doc.on('keydown', '[data-section="2"] input, [data-section="2"] select', step2EnterNavigate);

/* Enter walks the Download Your Project Plans wizard the way Step 2's own Enter already does: a
   field that passes hands focus on, one that fails states why and keeps it. The check is the form's
   own jQuery Validate instance, so Enter reports exactly what Next would - same rules, same wording,
   same label in the same place - rather than a second opinion written here. preventDefault runs
   either way: the wizard sits inside #fc-planning-form, whose Done button would otherwise take an
   Enter from any field as an implicit submit and post the plan half-filled. */
function fcDownloadPlansEnterNavigate(e) {
    var code = e.keyCode || e.which;
    if (code !== 13) {
        return;
    }

    var el = e.target;
    if (!el || el.tagName === 'TEXTAREA' || el.disabled || el.readOnly) {
        return;
    }

    // Text-like fields and the State select only; the radio panes keep Enter as their own submit.
    var type = (el.type || 'text').toLowerCase();
    var isTextLike =
        el.tagName === 'INPUT' &&
        (type === 'text' || type === 'tel' || type === 'email' || type === 'number' || type === 'search' || type === '');
    if (!isTextLike && el.tagName !== 'SELECT') {
        return;
    }

    var pane = el.closest('.fc-form-plan');
    if (!pane || !$(pane).is(':visible')) {
        return;
    }

    // Google's address suggestions own the Enter key while their list is up.
    if ($('.pac-container:visible').length) {
        return;
    }

    e.preventDefault();

    var validator = null;
    try {
        validator = $(el).closest('form').data('validator');
    } catch (errVd) {}

    if (validator) {
        // element() renders the message itself, so a failure only has to stop here.
        if (validator.element(el) === false) {
            return;
        }
    } else if (!String(el.value != null ? el.value : '').trim()) {
        return;
    }

    var $fields = $(pane).find('input, select').filter(function() {
        var t = (this.type || 'text').toLowerCase();
        if (t === 'hidden' || t === 'button' || t === 'submit' || t === 'reset') {
            return false;
        }
        return !this.disabled && !this.readOnly && $(this).is(':visible');
    });

    var next = $fields.get($fields.index(el) + 1);
    if (!next) {
        return;
    }

    next.focus();
    if (typeof next.select === 'function' && (next.type || '').toLowerCase() !== 'number') {
        try {
            next.select();
        } catch (errSel) {}
    }
}

_doc.on('keydown', '#submit-modal input, #submit-modal select', fcDownloadPlansEnterNavigate);

/* Enter anywhere in the wizard that is not a field presses the step's own button, so a step whose
   answer is a radio still reports what is missing instead of the key doing nothing. Fields are
   handled above; a focused radio keeps the browser's own implicit submit, which runs the same
   validation. A button already marked disabled is left alone. */
function fcDownloadPlansEnterStep(e) {
    var code = e.keyCode || e.which;
    if (code !== 13) {
        return;
    }

    var $modal = $('#submit-modal:visible');
    if (!$modal.length) {
        return;
    }

    if (!e.target || $(e.target).is('input, select, textarea, button, a, [contenteditable]')) {
        return;
    }

    var $btn = $modal.find('.fc-download-footer-actions:visible .fc-btn-next').first();
    if (!$btn.length) {
        $btn = $modal.find('.fc-form-plan:visible .fc-btn-next').first();
    }
    if (!$btn.length || $btn.hasClass('disabled') || $btn.is('[disabled]')) {
        return;
    }

    e.preventDefault();
    $btn.trigger('click');
}

_doc.on('keydown', fcDownloadPlansEnterStep);

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

_doc.on('keyup input change', '.has-clear .form-control', hasClear_formControl);

function hasClear_formControl() {
    var _this = $(this),
        clear = `<i class="fa-solid fa-circle-xmark form-control-clear"></i>`,
        shown = _this.siblings('.form-control-clear').length > 0,
        wanted = !!_this.val();

    if (shown === wanted) return;

    if (wanted) {
        _this.after(clear);
    } else {
        _this.siblings('.form-control-clear').remove();
    }
}

//----------------------------------------------------------------------------------

_doc.on('keyup input blur', '[input-type="number"]', inputType_number);

_doc.on('input blur change', '.custom-gate [name="width"]', function() {
    var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    if (!fd || typeof SlatFence === 'undefined' || !SlatFence.isMainSlatSlug(fd.slug)) {
        return;
    }
    var el = this;
    var $msg = $(el).closest('.fc-input-container').find('.fc-input-msg').first();
    $msg.removeClass('fcim-show').html('');
    var wResult = SlatFence.validateGateModalCustomWidthField(el);
    if (!wResult.valid) {
        $msg.addClass('fcim-show').html(wResult.message);
    }
    SlatFence.syncGateModalCalculateButtonState();
});

function inputType_number() {
    var _this = $(this);

    if (_this.closest('.custom-gate').length && typeof SlatFence !== 'undefined') {
        var fdGate = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (fdGate && SlatFence.isMainSlatSlug(fdGate.slug) && _this.attr('name') === 'width') {
            var $wrapGate = _this.closest('.fc-input-container');
            var $msgGate = $wrapGate.find('.fc-input-msg').first();
            $msgGate.removeClass('fcim-show').html('');
            var wResult = SlatFence.validateGateModalCustomWidthField(_this[0]);
            if (!wResult.valid) {
                $msgGate.addClass('fcim-show').html(wResult.message);
            }
            SlatFence.syncGateModalCalculateButtonState();
            return;
        }
    }

    var min = parseInt(_this.attr('data-min')),
        max = parseInt(_this.attr('data-max'));

    _this.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');
    _this.closest('.fc-input-container').find('[type="button"]').removeAttr('disabled')
        .removeClass('disabled')
        .removeClass('btn-light')
        .addClass('btn-dark');

    if (_this.val() < min || _this.val() > max) {

        if (_this.val() < min) {
            var alert = ' Invalid ' + HELPER.number_format(min) + 'mm min';
        }

        if (_this.val() > max) {
            var alert = ' Invalid ' + HELPER.number_format(max) + 'mm max';
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

// Slat / Slat Infill: Step 2 slat gap → segment storage + max height options (native + Select2).
_doc.on('change select2:select', '[data-section="2"] [name="slat_gap"]', slatGapStep2_change);

function slatGapStep2_change() {
    var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    if (!fd || typeof SlatFence === 'undefined' || !SlatFence.isSlatLike(fd.slug)) {
        return;
    }

    var el = this;
    var val = $(el).val();

    var $msg = $(el).closest('.fc-input-container').find('.fc-input-msg').first();
    $msg.removeClass('fcim-show').html('');
    var gapResult = SlatFence.validateSlatGapField(el);
    if (!gapResult.valid) {
        $msg.addClass('fcim-show').html(gapResult.message);
        try {
            SlatFence.syncStep2MaxFenceHeightDisabledState(fd.slug);
        } catch (eOff) {}
        try {
            updateCalculateButtonByStep2Completeness();
        } catch (e3) {}
        return;
    }

    try {
        SlatFence.onStep2SlatGapChanged(fd, val);
    } catch (e2) {}

    try {
        updateCalculateButtonByStep2Completeness();
    } catch (e3) {}

    setTimeout(function() {
        try {
            step2TryAutoCalculateAfterDropdownChange({
                scrollToStep3: false,
                showNativeTooltip: false
            });
        } catch (eAc) {}
    }, 0);
}

_doc.on('change select2:select', '[data-section="2"] [name="slat_size"]', slatSizeStep2_change);

function slatSizeStep2_change() {
    var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    if (!fd || typeof SlatFence === 'undefined' || !SlatFence.isSlatLike(fd.slug)) {
        return;
    }

    var el = this;
    var val = $(el).val();

    var $msg = $(el).closest('.fc-input-container').find('.fc-input-msg').first();
    $msg.removeClass('fcim-show').html('');
    var sizeResult = SlatFence.validateSlatSizeField(el);
    if (!sizeResult.valid) {
        $msg.addClass('fcim-show').html(sizeResult.message);
        try {
            SlatFence.syncStep2MaxFenceHeightDisabledState(fd.slug);
        } catch (eOff) {}
        try {
            updateCalculateButtonByStep2Completeness();
        } catch (e3) {}
        return;
    }

    try {
        SlatFence.onStep2SlatSizeChanged(fd, val);
    } catch (e2) {}

    try {
        updateCalculateButtonByStep2Completeness();
    } catch (e3) {}

    setTimeout(function() {
        try {
            step2TryAutoCalculateAfterDropdownChange({
                scrollToStep3: false,
                showNativeTooltip: false
            });
        } catch (eAc) {}
    }, 0);
}

//----------------------------------------------------------------------------------

// Slat Fence: Max Fence Height — validate on input/blur; auto-calc on change/blur except Gate ONLY Step 2 height.
_doc.on('input blur change', '[name="max_fence_height"]', maxFenceHeightValidation);
_doc.on('input blur', '[name="gate_max_fence_height"]', gateMaxFenceHeightValidation);

function maxFenceHeightValidation(e) {
    var fd = getSelectedFenceData();
    if (!SlatFence.isSlatLike(fd?.slug)) return;

    // Use the actual field that fired the event (supports multiple sections/tabs).
    var el = e?.target || this;
    if (!el || el.name !== 'max_fence_height') return;

    if (el.disabled) {
        return;
    }

    var $wrap = $(el).closest('.fc-input-container');
    // Guard against duplicate tooltip nodes (pick first).
    var $msg = $wrap.find('.fc-input-msg').first();

    $msg.removeClass('fcim-show').html('');
    var maxHeightResult = SlatFence.validateMaxFenceHeightField(el);
    if (!maxHeightResult.valid) {
        $msg.addClass('fcim-show').html(maxHeightResult.message);
    }

    var panelCountEl = document.querySelector('[name="panel_count"]');
    var canCalculate = SlatFence.canAutoCalculateFromStep2({
        slug: fd?.slug,
        measurementEl: document.querySelector('.measurement-box-number'),
        panelCountEl: panelCountEl,
        maxHeightEl: el,
        validatePanelCountFn: function(panelEl) {
            return SlatFenceInfill.validatePanelCountField(panelEl).valid;
        }
    });

    SlatFence.syncCalculateButtonState({ canCalculate: canCalculate });

    var slatGateOnlyHeight =
        SlatFence.isMainSlatSlug(fd.slug) && SlatFence.isStep2GateOnlyActive(fd);

    // Fence Height: auto-calculate on change/blur — not while editing Gate ONLY Step 2 Gate Height.
    if (e && (e.type === 'change' || e.type === 'blur') && canCalculate && !slatGateOnlyHeight) {
        try {
            step2TryAutoCalculateAfterDropdownChange({
                scrollToStep3: false,
                showNativeTooltip: false
            });
        } catch (eAc) {}
    }
}

function gateMaxFenceHeightValidation(e) {
    var fd = getSelectedFenceData();
    if (!SlatFence.isMainSlatSlug(fd?.slug)) return;

    var el = e?.target || this;
    if (!el || el.name !== 'gate_max_fence_height') return;
    if (el.disabled) return;

    var $wrap = $(el).closest('.fc-input-container');
    var $msg = $wrap.find('.fc-input-msg').first();
    $msg.removeClass('fcim-show').html('');

    var maxHeightResult = SlatFence.validateMaxFenceHeightField(el);
    if (!maxHeightResult.valid) {
        $msg.addClass('fcim-show').html(maxHeightResult.message);
        try {
            SlatFence.syncGateModalCalculateButtonState();
        } catch (eBtnGh) {}
        return;
    }

    try {
        SlatFence.syncGateModalCalculateButtonState();
    } catch (eBtnGhOk) {}

    if (
        maxHeightResult.valid &&
        e &&
        (e.type === 'change' || e.type === 'blur') &&
        SlatFence.isStep2GateOnlyActive(fd)
    ) {
        try {
            SlatFence.mirrorGateModalHeightToStep2(fd);
        } catch (eMirModalH) {}
    }
}

//----------------------------------------------------------------------------------

_doc.on('input blur', '[name="panel_count"]', panelCountValidation);

function panelCountValidation(e) {
    var fd = getSelectedFenceData();
    if (!SlatFenceInfill.isPanelCountRequired(fd?.slug)) return true;

    var el = e?.target || this;
    if (!el || el.name !== 'panel_count') return true;

    var $wrap = $(el).closest('.fc-input-container');
    var $msg = $wrap.find('.fc-input-msg').first();
    $msg.removeClass('fcim-show').html('');

    var panelResult = SlatFenceInfill.validatePanelCountField(el);
    if (!panelResult.valid) {
        $msg.addClass('fcim-show').html(panelResult.message);
    }
    fcClearStep2NativeValidity(el);

    var canCalculate = SlatFence.canAutoCalculateFromStep2({
        slug: fd?.slug,
        measurementEl: document.querySelector('.measurement-box-number'),
        panelCountEl: el,
        maxHeightEl: document.querySelector('[name="max_fence_height"]'),
        validatePanelCountFn: function(panelEl) {
            return SlatFenceInfill.validatePanelCountField(panelEl).valid;
        }
    });

    SlatFence.syncCalculateButtonState({ canCalculate: canCalculate });

    if (e && e.type === 'change' && panelResult.valid && canCalculate) {
        setTimeout(function() {
            try {
                step2TryAutoCalculateAfterDropdownChange({
                    scrollToStep3: false,
                    showNativeTooltip: false
                });
            } catch (eAc) {}
        }, 0);
    }

    return panelResult.valid;
}

//----------------------------------------------------------------------------------

_doc.on('keyup', '.measurement-box-number', measurementBoxNumber);

function measurementBoxNumber() {
    var _this = $('.measurement-box-number');
    if (
        _this.prop('readonly') &&
        _this.closest('.fc-measurement-locked-gate-only').length
    ) {
        return;
    }

    if (typeof fcValidateOverallLengthMm === 'function') {
        var oalResult = fcValidateOverallLengthMm({ el: _this[0] });
        if (!oalResult.valid) {
            if (typeof fcApplyOverallLengthValidationUi === 'function') {
                fcApplyOverallLengthValidationUi({ el: _this[0], hideStep3: false });
            }
        } else {
            _this.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');
            try {
                updateCalculateButtonByStep2Completeness();
            } catch (eUp) {}
        }
    }

    try {
        var fd = getSelectedFenceData();
        if (fd?.slug === 'slat_fence_infill') {
            var panelCountEl = document.querySelector('[name="panel_count"]');
            if (panelCountEl) panelCountValidation({ target: panelCountEl });
        }

        var canCalculate = SlatFence.canAutoCalculateFromStep2({
            slug: fd?.slug,
            measurementEl: document.querySelector('.measurement-box-number'),
            panelCountEl: document.querySelector('[name="panel_count"]'),
            maxHeightEl: document.querySelector('[name="max_fence_height"]'),
            validatePanelCountFn: function(panelEl) {
                return SlatFenceInfill.validatePanelCountField(panelEl).valid;
            }
        });
        SlatFence.syncCalculateButtonState({ canCalculate: canCalculate });
    } catch (err) {}
}

//----------------------------------------------------------------------------------

// Slat Fence: auto-calculate when Width Dimension From changes,
// but only if Overall Length is filled and there are no validation errors.
_doc.on('change', '[name="width_dimension_from"]', widthDimensionFrom_change);

function widthDimensionFrom_change(e) {
    var fd = getSelectedFenceData();
    if (!SlatFence.isSlatLike(fd?.slug)) return;

    // Update validation state/tooltips first.
    try { measurementBoxNumber?.(); } catch (err) {}
    try {
        const maxHEl = document.querySelector('[name="max_fence_height"]');
        if (maxHEl) maxFenceHeightValidation?.call(maxHEl, { target: maxHEl });
    } catch (err) {}

    var canCalculate = SlatFence.canAutoCalculateFromStep2({
        slug: fd?.slug,
        measurementEl: document.querySelector('.measurement-box-number'),
        panelCountEl: document.querySelector('[name="panel_count"]'),
        maxHeightEl: document.querySelector('[name="max_fence_height"]'),
        validatePanelCountFn: function(panelEl) {
            return SlatFenceInfill.validatePanelCountField(panelEl).valid;
        }
    });

    if (canCalculate) {
        btnCalculate();
    }
}

/**
 * Barr Step 2: run Calculate when fence height and overall length are both set (no button click).
 */
function step2BarrTryAutoCalculateIfReady() {
    var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    if (!fd || typeof normalizeFenceStyleSlug !== 'function') {
        return;
    }
    if (normalizeFenceStyleSlug(fd.slug) !== 'barr') {
        return;
    }

    var section = document.querySelector('.js-fc-form-step[data-section="2"]');
    if (!section || !$(section).is(':visible')) {
        return;
    }

    var fh = section.querySelector('[name="fence_height"]');
    if (!fh || !String(fh.value || '').trim()) {
        return;
    }

    plannerTryAutoCalculateIfStep2Complete({
        scrollToStep3: false,
        showNativeTooltip: false
    });
}

_doc.on('change select2:select', '[data-section="2"] [name="fence_height"]', function() {
    if (typeof fcMarkStep2DomDirty === 'function') {
        fcMarkStep2DomDirty();
    }
    step2BarrTryAutoCalculateIfReady();
});

_doc.on('change', '.js-fc-form-step[data-section="2"] .measurement-box-number', function() {
    var fdChg = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    var skipOalGoCustom =
        fdChg &&
        typeof SlatFence !== 'undefined' &&
        SlatFence.isStep2GateOnlyCustomGate(fdChg);
    if (!skipOalGoCustom) {
        try {
            updateOverAllLength();
        } catch (eOal) {}
    }
    step2BarrTryAutoCalculateIfReady();
});

/* ----------------------------------------------------------------
    [END] KEYUP EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] CHANGE EVENT
    ---------------------------------------------------------------- */

_doc.on('change', '.fencing-input-range input', fencingInputRange_input);

function fencingInputRange_input() {
    var _this = $(this),
        val = _this.val(),
        modal_key = $('.fencing-container').attr('data-key');

    var fd = getSelectedFenceData();

    _this.closest('.fencing-input-range').find('.fir-info span').text( val );

    if( modal_key == 'edit_spacing' ) {
        var width = val/10;
        $('.fencing-panel-spacing-number').css({'width': width});
    }

    if( modal_key == 'panel_options_custom' ) {

        var data = {
            'action': 'get-size',
            'name': fd.slug,
            'key': 'width',
            'value': val
        }

        $.ajax({
            url: 'ajax',
            type: "POST",
            data: data,
            headers: {},
            success: function(response) {
                try {
                    var info = JSON.parse(response);
                     _this.closest('.fencing-input-range').find('.fir-info-sub span').text(info.weight);
                } catch (err) {

                }
            }
        });

    }

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


// Tracks whether the spy is currently stuck. .sticky-roll used to be removed and re-added on
// every scroll tick, which restarts the CSS transitions the strip changes form with — they never
// got past their first frame, so sticking read as a cut. The class is toggled on state change
// only now; width and top still refresh every tick, since neither is transitioned.
var _fcStickyRollOn = false;
var _fcStickyReleaseTimer = null;

_win.on('scroll resize', function() {
    var spy = $('[data-spy="scroll"]'),
        target = spy.attr('data-target'),
        offset = spy.attr('data-offset'),
        screen = parseInt(spy.attr('data-screen')),
        $target = $(target);

    if (!spy.length || !target || !$target.length) {
        return;
    }

    var stick = $('body').innerWidth() >= screen && $(window).scrollTop() > $target.offset().top;

    if (stick) {
        // A release still pending means the strip is mid-transition back to its full height, so the
        // placeholder it already holds is the measurement to keep — taking a fresh one here reads
        // the strip part-grown and reserves a few px short, which is the jump this exists to stop.
        var releasing = _fcStickyReleaseTimer !== null;

        if (releasing) {
            clearTimeout(_fcStickyReleaseTimer);
            _fcStickyReleaseTimer = null;
        }

        // Measured while the strip is still in flow: position:fixed empties its container, and
        // without a placeholder everything below jumped up by the strip's own height the moment
        // it stuck. Only a spy that asks for it gets one — the plan page's Stock & Delivery panel
        // is a flex column beside the cart, where reserving would stretch the column instead.
        if (!_fcStickyRollOn && !releasing && spy.is('[data-sticky-reserve]')) {
            $target.css('min-height', spy.outerHeight());
        }
        spy.addClass('sticky-roll').css({ 'width': $target.width(), 'top': offset });
    } else if (_fcStickyRollOn) {
        spy.removeClass('sticky-roll').css({ 'width': '', 'top': '' });
        // The placeholder outlives the class by the length of the strip's transition. Back in flow
        // the strip is still at its compact height and eases up to its full one, so releasing on
        // the first frame would let the page below ride that 17px down with it — the jump the
        // placeholder is there to prevent, just slower.
        _fcStickyReleaseTimer = setTimeout(function() {
            _fcStickyReleaseTimer = null;
            $target.css('min-height', '');
        }, 260);
    }

    _fcStickyRollOn = stick;

});

_win.on('scroll', setAnimation);
function setAnimation() {

    $('[animation-type]').each(function(i, el) {

        var h = $(window).outerHeight(),
            target = $(el).offset().top,
            st = $(window).scrollTop() + h,
            type = $(el).attr('animation-type');

            if( target <= st ) {
                $(el).addClass(type);
            }

    });

}

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
var _fcFenceDiagramScroll$ = null;

function fcFenceDiagramDragStart($scroll, e) {
    cursordown = true;
    _fcFenceDiagramScroll$ = $scroll;
    cursorxpos = $scroll.scrollLeft() + e.clientX;
    cursorypos = $scroll.scrollTop() + e.clientY;
}

function fcFenceDiagramDragEnd() {
    cursordown = false;
    _fcFenceDiagramScroll$ = null;
}

$('.fc-project-plan-hscroll, .fencing-display-result').mousedown(function(e) {
    var $scroll = $(this);
    if ($scroll.hasClass('fencing-display-result') && $scroll.children('.fc-project-plan-hscroll').length) {
        return;
    }
    fcFenceDiagramDragStart($scroll, e);
}).mouseup(fcFenceDiagramDragEnd).mouseleave(fcFenceDiagramDragEnd);

$(document).on('mousemove', function(e) {
    if (!cursordown || !_fcFenceDiagramScroll$ || !_fcFenceDiagramScroll$.length) {
        return;
    }
    try {
        _fcFenceDiagramScroll$.scrollLeft(cursorxpos - e.clientX);
    } catch (err) {}
    try {
        _fcFenceDiagramScroll$.scrollTop(cursorypos - e.clientY);
    } catch (err2) {}
});

/* ----------------------------------------------------------------
    [END] MOUSEDOWN EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] MOUSEUP / TOUCH[END] EVENT
    ---------------------------------------------------------------- */

_doc.on('mouseup touchend', '.fc-project-plan-hscroll, .fencing-display-result', mu_fencingDisplayResult);

function mu_fencingDisplayResult(e) {
    var _this = $(this);
    _this.removeClass('grabbing');
    setTimeout(function() {
        _this.removeClass('grabbing is-grabbing');
    }, 200);

}

//----------------------------------------------------------------------------------

_doc.on('mousedown touchstart', '.fc-project-plan-hscroll, .fencing-display-result', md_fencingDisplayResult);

function md_fencingDisplayResult() {
    var _this = $(this);
    if (_this.hasClass('fencing-display-result') && _this.children('.fc-project-plan-hscroll').length) {
        return;
    }
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
    // Step 2 + planner measurements use `.fc-input-msg` — not jQuery Validate labels.
    ignore:
        ':hidden, .measurement-box-number, [name="max_fence_height"], [name="panel_count"], [data-section="2"] input, [data-section="2"] select, [data-section="2"] textarea',
    rules: {
        email: {
            required: true,
            email: true
        },
        /* Anything Else is one question answered by either a tile or NIL, so the rule hangs off NIL
           and asks whether anything at all is chosen. */
        nothing_extra: {
            required: function() {
                return $('#submit-modal .fc-other-products input[name="extra[]"]:checked').length === 0;
            }
        },
    },
    messages: {
        timeframe: "Please select an option.",
        nothing_extra: "Please choose at least one option.",
    },
    /* A radio group has no single field to hang a message off, and jQuery Validate's default drops
       the label inside the first option - where the row's own flex rules stretch it into a red slab
       across the hint above. On the group box instead, where it straddles the top-right corner the
       way it does on a text field. Everything else keeps the default placement. */
    errorPlacement: function(error, element) {
        var $extras = element.closest('.fc-other-products');
        if ($extras.length) {
            error.appendTo($extras);
            return;
        }

        var type = (element.attr('type') || '').toLowerCase();
        if (type === 'radio' || type === 'checkbox') {
            // div only: pane 4 wraps each checkbox in a <label class="fc-form-check">, which is an
            // item and not a group, and appending there would bury the message inside one option.
            var $group = element.closest('div.fc-form-check');
            if ($group.length) {
                error.appendTo($group);
                return;
            }
        }
        error.insertAfter(element);
    },
    submitHandler: function(form) {
        var tab = $('#submit-modal .fc-form-plan:visible').attr('data-formtab'),
            move = $('#submit-modal .fc-download-footer-actions:visible').find('.fc-btn-next').attr('data-move');
        if (!move) {
            move = $('#submit-modal .fc-form-plan:visible').find('.fc-btn-next').attr('data-move');
        }
        history.pushState({}, '', `?tab=${HELPER.getSearchParams('tab')}&form=${move}`);

        if (tab == 4) {
            FCModal.close('#submit-modal');
            $('.fc-loader-overlay').show();
            if (typeof fcSetPlannerSubmitLoaderMessage === 'function') {
                fcSetPlannerSubmitLoaderMessage();
            }
            var submitStatus =
                typeof fcPlannerHasQuoteId === 'function' && fcPlannerHasQuoteId() ? 'update' : 'new';
            res = submit_fence_planner(submitStatus, { triggerEarlyWebhook: true });
        } else {
            $('#submit-modal .fc-form-plan').hide();
            $('#submit-modal .fc-download-footer-actions').hide();
            $('#submit-modal [data-formtab="' + move + '"]').show();
        }
    }
});

$.validator.addMethod("phone-format", function(value, element, params) {
    return HELPER.isValidAustralianNumber(value);
}, 'Please enter a valid mobile number.');


// $('.input-mobile').inputmask('9999 999 999');

/* ----------------------------------------------------------------
    [END] [START] VALIDATE
    ---------------------------------------------------------------- */
