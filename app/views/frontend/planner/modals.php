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


<!-- Overall Length exceeds planner / fence style maximum.
     NOTE: nothing shows or fills this modal today — no JS references
     #fc-overall-length-max-modal, the js-fc-overall-max-* spans or
     data-action="reset-overall-length". Restyled with the rest of the family so it is
     right the day it gets wired up. -->
<div class="modal fade fc-modal" id="fc-overall-length-max-modal" tabindex="-1" role="alertdialog" data-fc-role="alertdialog" aria-labelledby="fcOverallLengthMaxModalLabel" aria-describedby="fcOverallLengthMaxModalDesc" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <span class="fc-modal__icon fc-modal__icon--warning" aria-hidden="true">
                    <i class="fa-solid fa-ruler-horizontal"></i>
                </span>
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcOverallLengthMaxModalLabel">Overall Length too large</h5>
                    <p class="fc-modal__subtitle mb-0">Over the limit for <span class="js-fc-overall-max-style">this fence style</span></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body">
                <p class="fc-modal__lead" id="fcOverallLengthMaxModalDesc">The Overall Length entered for this section is longer than the style allows.</p>
                <div class="fc-modal__stats">
                    <div class="fc-modal__stat fc-modal__stat--over">
                        <span class="fc-modal__stat-label">You entered</span>
                        <span class="fc-modal__stat-value"><span class="js-fc-overall-max-value"></span> mm</span>
                    </div>
                    <div class="fc-modal__stat">
                        <span class="fc-modal__stat-label">Maximum allowed</span>
                        <span class="fc-modal__stat-value"><span class="js-fc-overall-max-limit"></span> mm</span>
                    </div>
                </div>
                <p class="fc-modal__note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>Lengths above the limit can overload the planner. Reset Overall Length and enter a valid measurement.</span>
                </p>
            </div>
            <div class="modal-footer fc-modal__footer">
                <button type="button" class="btn btn-orange text-uppercase px-3" data-action="reset-overall-length" data-fc-autofocus>
                    <i class="fa-solid fa-rotate-left me-2" aria-hidden="true"></i>
                    <strong>Reset Overall Length</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Planner Step 3 — fence configuration summary -->
<div class="modal fade" id="fc-planner-summary-modal" tabindex="-1" aria-labelledby="fcPlannerSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down fc-planner-summary-modal__dialog">
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

<!-- Incomplete fence sections — choose to fix or continue to project plan. The section
     list is filled on show (events.js); the fallback copy stays visible when the two
     completeness checks disagree and it comes back empty. -->
<div class="modal fade fc-modal" id="fc-incomplete-sections-modal" tabindex="-1" role="alertdialog" data-fc-role="alertdialog" aria-labelledby="fcIncompleteSectionsModalLabel" aria-describedby="fcIncompleteSectionsModalDesc" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <span class="fc-modal__icon fc-modal__icon--warning" aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcIncompleteSectionsModalLabel">Sections need attention</h5>
                    <p class="fc-modal__subtitle mb-0">Some measurements are still missing</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body" id="fcIncompleteSectionsModalDesc">
                <p class="fc-modal__lead js-fc-incomplete-sections-lead d-none">These sections have no Overall Length, or <strong>Calculate</strong> was never run on them:</p>
                <ul class="fc-modal__list js-fc-incomplete-sections-list d-none"></ul>
                <p class="fc-modal__lead js-fc-incomplete-sections-fallback">One or more fence sections are incomplete — an Overall Length is missing, or <strong>Calculate</strong> was never run.</p>
                <p class="fc-modal__note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>Finish them first for an accurate materials list, or continue with what you have entered so far.</span>
                </p>
            </div>
            <div class="modal-footer fc-modal__footer fc-modal__footer--split">
                <button type="button" class="btn btn-fc-blue text-uppercase px-3" data-action="complete-sections" data-fc-autofocus>
                    <i class="fa-solid fa-angle-left me-2" aria-hidden="true"></i>
                    <strong>Complete the Section</strong>
                </button>
                <button type="button" class="btn btn-orange text-uppercase px-3" data-action="proceed-anyway">
                    <strong>Proceed to project plan</strong>
                    <i class="fa-solid fa-angle-right ms-2" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Load Quote Modal. The oversized League Gothic field stays; its placeholder does not —
     "Enter Quote ID" at 3em overflowed the dialog, so the prompt moved to a real label. -->
<form method="get">
    <div class="modal fade fc-modal" id="load-quote" tabindex="-1" aria-labelledby="fcLoadQuoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered fc-modal__dialog fc-modal__dialog--sm">
            <div class="modal-content fc-modal__content">
                <div class="modal-header fc-modal__header">
                    <div class="fc-modal__header-text">
                        <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcLoadQuoteModalLabel">Load <span class="text-danger">Quote</span></h5>
                        <p class="fc-modal__subtitle mb-0">Pick up a saved plan where you left off</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body fc-modal__body">
                    <div class="fc-load-quote-error d-none" role="alert" aria-live="polite"></div>
                    <label class="fc-modal__field-label" for="fc-load-quote-id">Quote ID</label>
                    <input type="text" id="fc-load-quote-id" name="qid" class="form-control form-control-lg no-space text-center" maxlength="64" spellcheck="false" autocomplete="off" autocorrect="off" aria-describedby="fc-load-quote-hint" data-fc-autofocus required>
                    <p class="fc-modal__note" id="fc-load-quote-hint">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <span>Letters and numbers only. You will find it on the Fence Summary of the plan you saved.</span>
                    </p>
                </div>
                <div class="modal-footer fc-modal__footer">
                    <button type="button" class="btn btn-outline-secondary text-uppercase px-3" data-bs-dismiss="modal">
                        <strong>Cancel</strong>
                    </button>
                    <button type="submit" class="btn btn-orange text-uppercase px-3">
                        <i class="fa fa-check me-2" aria-hidden="true"></i>
                        <strong>Load quote</strong>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>


<!-- Generic notice/error dialog. fcShowPopupAlertModal() writes .modal-title and
     .modal-message and hides the measurement box; the footer used to be d-none too, which
     left an error dialog with no way to acknowledge it but the X. -->
<div class="modal fade fc-modal fc-popup-alert-modal" id="popup-alert" tabindex="-1" role="alertdialog" data-fc-role="alertdialog" aria-labelledby="fcPopupAlertModalLabel" aria-describedby="fcPopupAlertModalDesc" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <span class="fc-modal__icon" aria-hidden="true">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </span>
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcPopupAlertModalLabel"></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body" id="fcPopupAlertModalDesc">
                <div class="modal-message fc-modal__lead"></div>

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
            <div class="modal-footer fc-modal__footer">
                <button type="button" class="btn btn-orange text-uppercase px-3" data-bs-dismiss="modal" data-fc-autofocus>
                    <strong>OK</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset / Delete Section — destructive confirms, same shape and copy discipline as the Clear All
     dialog in partials/footer.php: name what disappears rather than asking "are you sure?".
     Gated in core/events.js, which re-fires the original click once confirmed. -->
<div class="modal fade fc-modal" id="fc-reset-section-confirm" tabindex="-1" role="alertdialog" data-fc-role="alertdialog" aria-labelledby="fcResetSectionTitle" aria-describedby="fcResetSectionDesc" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <span class="fc-modal__icon" aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcResetSectionTitle">Reset section</h5>
                    <p class="fc-modal__subtitle mb-0">Start this section again from the fencing style</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body">
                <p class="fc-modal__lead" id="fcResetSectionDesc">This section will be cleared:</p>
                <ul class="fc-modal__list">
                    <li><i class="fa-solid fa-swatchbook" aria-hidden="true"></i>The fencing style you picked</li>
                    <li><i class="fa-solid fa-ruler-horizontal" aria-hidden="true"></i>Its measurements</li>
                    <li><i class="fa-solid fa-sliders" aria-hidden="true"></i>Gates, colours and panel options</li>
                </ul>
                <p class="fc-modal__note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>Your other sections are not affected. This cannot be undone.</span>
                </p>
            </div>
            <div class="modal-footer fc-modal__footer">
                <button type="button" class="btn btn-outline-secondary text-uppercase px-3" data-bs-dismiss="modal" data-fc-autofocus>
                    <strong>Cancel</strong>
                </button>
                <button type="button" class="btn btn-danger text-uppercase px-3 js-fc-confirm-proceed" data-bs-dismiss="modal">
                    <i class="fa-solid fa-rotate-left me-2" aria-hidden="true"></i>
                    <strong>Yes, reset</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade fc-modal" id="fc-delete-section-confirm" tabindex="-1" role="alertdialog" data-fc-role="alertdialog" aria-labelledby="fcDeleteSectionTitle" aria-describedby="fcDeleteSectionDesc" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <span class="fc-modal__icon" aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcDeleteSectionTitle">Delete section</h5>
                    <p class="fc-modal__subtitle mb-0">Remove this section from your quote</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body">
                <p class="fc-modal__lead" id="fcDeleteSectionDesc">This section and everything in it will be removed:</p>
                <ul class="fc-modal__list">
                    <li><i class="fa-solid fa-swatchbook" aria-hidden="true"></i>Its fencing style and measurements</li>
                    <li><i class="fa-solid fa-sliders" aria-hidden="true"></i>Its gates, colours and panel options</li>
                    <li><i class="fa-solid fa-list-check" aria-hidden="true"></i>Its lines in the materials list</li>
                </ul>
                <p class="fc-modal__note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>Your remaining sections are renumbered. This cannot be undone.</span>
                </p>
            </div>
            <div class="modal-footer fc-modal__footer">
                <button type="button" class="btn btn-outline-secondary text-uppercase px-3" data-bs-dismiss="modal" data-fc-autofocus>
                    <strong>Cancel</strong>
                </button>
                <button type="button" class="btn btn-danger text-uppercase px-3 js-fc-confirm-proceed" data-bs-dismiss="modal">
                    <i class="fa-solid fa-trash-can me-2" aria-hidden="true"></i>
                    <strong>Yes, delete</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Step 2 "Important" notice, mobile only. The body is empty in the markup on purpose: the
     notice copy changes with the fence style, so it is copied from the live panel in Step 2 each
     time the dialog opens (see fcStep2ImportantModalShow in core/events.js). -->
<div class="modal fade fc-modal" id="fc-step2-important-modal" tabindex="-1" aria-labelledby="fcStep2ImportantTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog fc-modal__dialog--sm">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <span class="fc-modal__icon fc-modal__icon--info" aria-hidden="true">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </span>
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcStep2ImportantTitle">Important</h5>
                    <p class="fc-modal__subtitle mb-0">Measurement Guide</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body js-fc-step2-important-body"></div>
            <div class="modal-footer fc-modal__footer">
                <button type="button" class="btn btn-orange text-uppercase px-3" data-bs-dismiss="modal" data-fc-autofocus>
                    <strong>Got it</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Download wizard "We'll email you the plans" note, mobile only. The panel it mirrors sits in
     Step 1 of #submit-modal and is hidden below md; the body is filled from that panel on show so
     the sentence has one source (see fcDownloadIntroModalShow in core/events.js). -->
<div class="modal fade fc-modal" id="fc-download-intro-modal" tabindex="-1" aria-labelledby="fcDownloadIntroTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog fc-modal__dialog--sm">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <span class="fc-modal__icon fc-modal__icon--info" aria-hidden="true">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </span>
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcDownloadIntroTitle">We&rsquo;ll email you the plans</h5>
                    <p class="fc-modal__subtitle mb-0">Before you enter your details</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body js-fc-download-intro-body"></div>
            <div class="modal-footer fc-modal__footer">
                <button type="button" class="btn btn-orange text-uppercase px-3" data-bs-dismiss="modal" data-fc-autofocus>
                    <strong>Got it</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Planner notice toast ("Overall Length changed", …). The rule code is only rendered in
     debug mode — see popupToast() in core/events.js. -->
<div class="toast-container fc-toast-container position-fixed">
    <div id="liveToast" class="toast fc-toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-bs-config='{"delay":8000}'>
        <div class="toast-header fc-toast__header">
            <span class="fc-toast__icon" aria-hidden="true">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </span>
            <div class="me-auto toast-title fc-toast__title"></div>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body fc-toast__body"></div>
        <code class="toast-code fc-toast__code d-none"></code>
    </div>
</div>