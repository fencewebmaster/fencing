<?php
use Fc\Admin\Services\AppConfigService;
?>
<!-- [START] FORM SUBMISSION LOADER -->
<div class="fc-loader-overlay" style="display: none;">
    <div class="fc-loader-container">
        <div class="fc-loader">
            <div class="row align-items-center">
                
                <div class="col-auto">
                    <div class="fc-loader-gif"></div>
                </div>

                <div class="col-auto">
                    
                    <ul class="mb-0 p-0">
                        <li class="fc-text-success li-create">
                            <div><small>Creating your plan...</small></div>
                        </li>
                    </ul>

                </div>

            </div>
        </div>
    </div>
</div>
<!-- [END] FORM SUBMISSION LOADER -->


<!-- Overall Length exceeds planner / fence style maximum -->
<div class="modal fade" id="fc-overall-length-max-modal" tabindex="-1" aria-labelledby="fcOverallLengthMaxModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-uppercase fw-bold" id="fcOverallLengthMaxModalLabel">Overall Length too large</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    Overall Length is <strong><span class="js-fc-overall-max-value"></span> mm</strong> for
                    <strong><span class="js-fc-overall-max-style"></span></strong>.
                </p>
                <p class="mb-0 text-secondary small">
                    The maximum allowed is <strong><span class="js-fc-overall-max-limit"></span> mm</strong>.
                    Values above this limit can overload the planner. Reset Overall Length and enter a valid measurement.
                </p>
            </div>
            <div class="modal-footer flex-wrap gap-2 justify-content-end">
                <button type="button" class="btn btn-orange text-uppercase" data-action="reset-overall-length">
                    <strong>Reset</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Planner Step 3 — fence configuration summary -->
<div class="modal fade" id="fc-planner-summary-modal" tabindex="-1" aria-labelledby="fcPlannerSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg fc-planner-summary-modal__dialog">
        <div class="modal-content fc-planner-summary-modal__content">
            <div class="modal-header fc-planner-summary-modal__header">
                <div class="fc-planner-summary-modal__header-text">
                    <h5 class="modal-title fc-planner-summary-modal__title" id="fcPlannerSummaryModalLabel">Fence Summary</h5>
                    <p class="fc-planner-summary-modal__subtitle mb-0">Configuration overview for your project</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body js-fc-planner-summary-body fc-planner-summary-modal__body"></div>
        </div>
    </div>
</div>

<!-- Incomplete fence sections — choose to fix or continue to project plan -->
<div class="modal fade" id="fc-incomplete-sections-modal" tabindex="-1" aria-labelledby="fcIncompleteSectionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-uppercase fw-bold" id="fcIncompleteSectionsModalLabel">Sections need attention</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">One or more fence sections are incomplete (for example, overall length is missing or <strong>Calculate</strong> was not run). You can finish those sections first, or continue to the project plan with the information entered so far.</p>
            </div>
            <div class="modal-footer flex-wrap gap-2 justify-content-between">
                <button type="button" class="btn btn-fc-blue text-uppercase" data-action="complete-sections">
                    <i class="fa-solid fa-angle-left me-2" aria-hidden="true"></i>
                    <strong>Complete the Section</strong>
                </button>
                <button type="button" class="btn btn-orange text-uppercase" data-action="proceed-anyway">
                    <strong>Proceed to project plan</strong>
                    <i class="fa-solid fa-angle-right ms-2" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Load Quote Modal -->
<form method="get">
    <div class="modal modal-sm fade" id="load-quote" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header fw-bold">
                    <h5 class="modal-title text-uppercase fw-bold" id="exampleModalLabel">Load <span class="text-danger">Quote</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fc-load-quote-error d-none" role="alert" aria-live="polite"></div>
                    <div class="input-groupx mb-3">
                        <div class="form-group has-clear position-relative">
                            <input type="text" name="qid" class="form-control border border-secondary form-control-lg no-space text-center mb-2" placeholder="Enter Quote ID" required autocomplete="off">
                        </div>

                        <button type="submit" class="btn btn-lg w-100 btn-orange text-uppercase px-4">
                            <i class="fa fa-check me-2"></i>
                            <strong>Confirm</strong>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>


<div class="modal fade fc-popup-alert-modal" id="popup-alert" tabindex="-1" aria-labelledby="fcPopupAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-popup-alert-modal__dialog">
        <div class="modal-content fc-popup-alert-modal__content">
            <div class="modal-header fc-popup-alert-modal__header">
                <h5 class="modal-title fc-popup-alert-modal__title" id="fcPopupAlertModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-popup-alert-modal__body">
                <div class="modal-message fc-popup-alert-modal__message"></div>

                <div class="fencing-measurement-box">

                    <div class="fencing-mb-input">

                        <div class="d-flex align-items-center">

                            <div class="fencing-qty-minus fencing-qty-btn px-3">
                                <i class="fa fa-minus"></i>
                            </div>
                            
                            <input type="text" class="measurement-box-number numeric text-center py-1 valid" data-min="<?php echo AppConfigService::all()->overall->min; ?>" data-max="<?php echo AppConfigService::all()->overall->max; ?>" maxlength="<?php echo AppConfigService::all()->overall->length; ?>" value="" data-last="" aria-invalid="false">
                            
                            <span>mm</span>   

                            <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
                                <i class="fa fa-plus"></i>
                            </div>

                        </div>

                    </div>

                    <button type="button" class="btn btn-dark text-uppercase btn-fc-calculate px-3 fw-bold">
                        <small>Update</small>
                    </button>

                </div>

                <div class="d-none">
                    <div class="or-divider">
                        <strong>OR</strong>  
                        <div></div>                    
                    </div>  

                    <div class="row">
                        <div class="col pe-1">
                            <button class="btn btn-danger w-100 text-uppercase" data-remove="gate">Remove Gate</button>                        
                        </div>
                        <div class="col ps-1">
                            <button class="btn btn-danger w-100 text-uppercase" data-remove="step_up">Remove Step-Up Panels</button>                
                        </div>
                    </div>                    
                </div>

            </div>
            <div class="modal-footer fc-popup-alert-modal__footer d-none">
                <button type="button" class="btn btn-orange px-4 fw-semibold" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 11">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-bs-config='{"delay":7000}'>
        <div class="toast-header text-bg-dark">
            <div class="me-auto toast-title"></div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body py-1"></div>
        <div class="px-2 pb-2">
            <code class="toast-code small text-muted border px-1 rounded"></code>        
        </div>
    </div>
</div>