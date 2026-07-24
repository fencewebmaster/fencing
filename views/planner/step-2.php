<!-- [START] MEASUREMENT -->  
<div class="fencing-section fencing-section--has-border js-fc-form-step fc-d-none" data-section="2" style="display: none;">
    
    <div class="fencing-measurement fencing-section--step2">

        <div class="row align-items-center">
            
            <div class="col-lg-5 col-md-6 mb-3">
                
                <div class="step-label mb-2" data-action="scroll" data-target="[data-section=2]" data-offset="54">Step <span>02</span></div>

                <h4 class="fencing-content-title mb-2 js-step-2-title">Enter your measurements</h4>

                <div class="step-2_field" data-action="change">
                                                         
                </div>

                <div class="row fc-step2-slat-height-row g-2 align-items-end mb-2 d-none">
                    <div class="col-md-6 col-12 fc-step2-height-slot"></div>
                    <div class="col-md-6 col-12 fc-step2-pair-slot"></div>
                </div>

                <div class="fc-step2-gate-only d-none">
                    <div class="fc-select-2 fc-select-left select-gate_only_step2">
                        <input type="checkbox" name="gate_only_step2" class="fc-step2-gate-only-input" style="width:0;position:absolute;opacity:0;" autocomplete="off">
                        <p class="mb-0">Gate <strong>ONLY</strong></p>
                    </div>
                </div>

                <div class="fc-input-container">
                    <div class="fw-bold small mb-1 js-step-2-measurement-label">Overall Length</div>
                    
                    <div class="fencing-measurement-box">
                    
                        <div class="fencing-mb-input">

                            <div class="d-flex align-items-center">

                                <div class="fencing-qty-minus fencing-qty-btn px-3">
                                    <i class="fa fa-minus"></i>
                                </div>
                                
                                <input type="number" class="measurement-box-number numeric text-center py-1" data-min="<?php echo config()->overall->min; ?>" data-max="<?php echo config()->overall->max; ?>" value=""> 
                                
                                <span>mm</span>   

                                <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
                                    <i class="fa fa-plus"></i>
                                </div>

                            </div>

                        </div>

                        <button type="button" class="btn btn-dark text-uppercase btn-fc-calculate px-3 fw-bold">
                            <small>Calculate</small>
                        </button>

                    </div>

                    <div class="fc-input-msg error-msg"></div>
                </div>
            </div>
            <div class="col-lg d-lg-inline-block d-md-none"></div>
            <div class="col-lg-6 col-md-6">

                <div class="alert alert-gray float-end">

                    <div class="text-uppercase fw-bold text-orange mb-2">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Important
                    </div>

                    <div class="step-2_notes" data-action="change"></div>
                                
                    <div>
                        <strong class="mb-1 js-step-2-note-title">Overall Length</strong>
                        <p class="text-secondary small js-step-2-note-copy">Ensure your overall length includes the posts each end. NOTE: "Panel & Post Options" below will deduct based on options selected.
                        </p>                                            
                    </div>                    
                    
                </div>

            </div>

        </div>

    </div>
   
</div>
<!-- [END] MEASUREMENT -->