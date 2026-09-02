<?php
use Fc\Admin\Services\AppConfigService;
?>
<!-- [START] MEASUREMENT -->
<div class="fencing-section fencing-section--has-border js-fc-form-step fc-d-none" data-section="2" style="display: none;">
    
    <div class="fencing-measurement fencing-section--step2">

        <!-- Top aligned, not centred: both columns open with the same header now, and centring
             sizes them against each other so the two header rules can never meet. -->
        <div class="row align-items-start">
            
            <div class="col-lg-5 col-md-6">
                
                <!-- Wrapped so the header is one box: it is what carries the ground and the rule out
                     to the card edges, which loose siblings could not do between them. Mirrors
                     .fc-step2-notes__header across the seam. -->
                <div class="fc-step2-measure__header">

                    <!-- Step number and its mobile Important trigger share a row, so the chip sits
                         opposite "Step 02" rather than beside the subtitle. Below md the IMPORTANT
                         panel opposite is hidden and its content moves behind this button; the dialog
                         body is filled from that panel on show, since the copy changes with the fence
                         style. -->
                    <div class="fc-step2-title-row d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="step-label" data-action="scroll" data-target="[data-section=2]" data-offset="54">Step <span>02</span></div>

                        <button type="button"
                            class="btn btn-sm fc-step2-important-btn d-md-none js-fc-step2-important-open"
                            data-bs-toggle="modal"
                            data-bs-target="#fc-step2-important-modal"
                            aria-label="Important notice for this step">
                            <i class="fa-solid fa-question" aria-hidden="true"></i>
                        </button>
                    </div>

                    <h4 class="fencing-content-title js-step-2-title">Enter your measurements</h4>
                </div>

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
                                
                                <input type="number" class="measurement-box-number numeric text-center py-1" data-min="<?php echo AppConfigService::all()->overall->min; ?>" data-max="<?php echo AppConfigService::all()->overall->max; ?>" value="">
                                
                                <span>mm</span>   

                                <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
                                    <i class="fa fa-plus"></i>
                                </div>

                            </div>

                        </div>

                        <button type="button" class="btn btn-dark text-uppercase btn-fc-calculate px-3 fw-bold">
                            Calculate
                        </button>

                    </div>

                    <div class="fc-input-msg error-msg"></div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6">

                <!-- Header outside the panel, so this column reads header-then-body the way Step 02
                     does across the seam, rather than the header being the panel's first line. Same
                     two elements as that header - .step-label over .fencing-content-title - so the
                     pair matches; the span is what colours it, the way "02" is coloured over there.
                     The subtitle repeats the one #fc-step2-important-modal carries, so the panel and
                     the dialog read alike. Nothing is stripped from the dialog's copy any more: it
                     clones .alert-gray, which is notes only now. -->
                <div class="fc-step2-notes__header">
                    <div class="step-label"><span>Important</span></div>
                    <h4 class="fencing-content-title">Measurement Guide</h4>
                </div>

                <!-- No longer an .alert: it carried the grey ground and the padding that went with
                     it, and with the header lifted out this is body copy sitting on the card like
                     the fields opposite. fcStep2ImportantModalShow reads this element by name. -->
                <div class="fc-step2-notes__body">

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