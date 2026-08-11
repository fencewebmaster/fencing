<?php require_once dirname(__DIR__, 4) . '/app/src/Services/AppConfigService.php'; ?>
<div class="row fc-form-field fc-gate-modal-gate-only-height-row g-2 align-items-end mb-2">
    <div class="col-md-6 col-12 px-1 fc-gate-modal-gate-only-col mb-0">
        <div class="fw-bold small mb-1">Gate</div>
        <div class="fc-select-2 fc-select-left select-gate_only fc-gate-modal-field w-100">
            <input type="checkbox" name="gate_only" style="width: 0;position: absolute;">
            <p class="mb-0">Gate <strong>ONLY</strong></p>
        </div>
    </div>
    <div class="col-md-6 col-12 px-1 fc-input-container fc-gate-modal-max-height mb-0">
        <div class="fw-bold small mb-1">Gate Height</div>
        <div class="fencing-measurement-box fencing-measurement-box--height-only">
            <div class="fencing-mb-input">
                <div class="d-flex align-items-center">
                    <div class="fencing-qty-minus fencing-qty-btn px-3">
                        <i class="fa fa-minus"></i>
                    </div>
                    <input
                        type="number"
                        name="gate_max_fence_height"
                        class="fc-gate-max-fence-height-input numeric text-center py-1 fc-form-field"
                        value=""
                        data-min="199"
                        data-max="2240"
                        autocomplete="off"
                    >
                    <span>mm</span>
                    <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
                        <i class="fa fa-plus"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="fc-input-msg error-msg"></div>
    </div>
</div>

<div class="fc-form-field fc-gate-modal-std-custom-section mb-2">
    <input type="checkbox" name="use_std" class="fc-form-field" style="visibility: hidden;position: absolute;">
    <div class="fw-bold small mb-1">Gate Width</div>
    <div class="row g-2">
        <div class="col-md-6 col-12 px-1">
            <div class="fc-select-2 fc-select-left select-use_std fc-gate-modal-field w-100" data-val="std">
                <p class="mb-0"><strong>STD</strong> Gate Width</p>
            </div>
        </div>
        <div class="col-md-6 col-12 px-1">
            <div class="fc-select-2 fc-select-left select-use_std fc-gate-modal-field w-100" data-val="custom">
                <p class="mb-0"><strong>CUSTOM</strong> Gate (300-{{maxWidth}}mm)</p>
            </div>
        </div>
    </div>
</div>

<div class="fc-form-field fc-gate-modal-custom-width-section mb-2">
    <div class="fc-input-container mb-0">
        <div class="fw-bold small mb-1">Custom Gate Width</div>
        <div class="fencing-measurement-box fencing-measurement-box--height-only fc-gate-modal-calculate-row">
            <div class="fencing-mb-input">
                <div class="d-flex align-items-center">
                    <div class="fencing-qty-minus fencing-qty-btn px-3">
                        <i class="fa fa-minus"></i>
                    </div>
                    <input
                        name="width"
                        type="number"
                        class="numeric fc-form-field text-center py-1"
                        input-type="number"
                        data-min="<?php echo \Fc\Admin\Services\AppConfigService::all()->overall->min; ?>"
                        maxlength="<?php echo \Fc\Admin\Services\AppConfigService::all()->overall->length; ?>"
                        data-max="{{maxWidth}}"
                    >
                    <span>mm</span>
                    <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
                        <i class="fa fa-plus"></i>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-dark px-4 text-uppercase py-2 fw-bold fc-gate-modal-calculate-btn"><small>Calculate</small></button>
        </div>
        <div class="fc-input-msg error-msg"></div>
    </div>
</div>

<div class="alert alert-gray mb-2 mb-0">
    <div class="text-uppercase fw-bold text-dark mb-2">
        <i class="fa-solid fa-circle-exclamation me-1"></i> {{gateDetailsTitle}}
    </div>
    <p class="text-secondary small mb-0">{{gateDetailsDescription}}</p>
</div>
