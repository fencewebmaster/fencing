<?php
/**
 * "Download Your Project Plans" wizard (planner). The third pane is data-formtab="4",
 * not 3 — the numbering is historical and fcBtnFormStep() moves by that value, so the
 * step labels are keyed to it rather than renumbered.
 *
 * @var array<int, string> $fcDownloadSteps
 */
$fcDownloadSteps = [
    1 => 'Your details',
    2 => 'Timeframe',
    4 => 'Anything else',
];
?>
<div id="submit-modal" class="fencing-modal fencing-modal--v2 fencing-modal--download-plans" role="dialog" aria-modal="true" aria-labelledby="fcDownloadPlansTitle">
    
    <div class="fc-modal-frame">

        <div class="fencing-modal-content fencing-modal-md fencing-modal--scroll-layout">
        
            <header class="fencing-modal-section fencing-modal-head fencing-modal-head--sticky">
                <div class="fencing-modal-title fc-download-head-text">
                    <h4 id="fcDownloadPlansTitle">Download Your Project Plans</h4>
                    <p class="fc-download-subtitle">Tell us where to send them</p>
                </div>
                <button type="button" class="fencing-modal-close js-fencing-modal-close" aria-label="Close"></button>
            </header>

            <div class="fencing-modal-section fencing-modal-body fencing-modal-body--scroll">

                <div class="fc-form-plan" data-formtab="1">

                    <?php $fcDownloadStepCurrent = 1; ?>
                    <?php include view_path('frontend.partials.modal.submit.form.stepper'); ?>

                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Customer Details</h2>

                    <div class="fc-download-intro">
                        <span class="fc-download-intro__icon" aria-hidden="true">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </span>
                        <div>
                            <div class="fc-download-intro__title">We&rsquo;ll email you the plans</div>
                            <p class="fc-download-intro__text mb-0">Enter your details and we&rsquo;ll send your plans through, ready to download or print for your reference.</p>
                        </div>
                    </div>

                    <?php include view_path('frontend.partials.modal.submit.form.customer-details'); ?>

                </div>

                <div class="fc-form-plan" data-formtab="2" style="display: none;">

                    <?php $fcDownloadStepCurrent = 2; ?>
                    <?php include view_path('frontend.partials.modal.submit.form.stepper'); ?>

                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-1">When Do You Need The Materials?</h2>
                    <p class="fc-download-step-hint">Pick the option that best matches your plans.</p>

                    <?php include view_path('frontend.partials.modal.submit.form.timeframe'); ?>

                </div>

                <div class="fc-form-plan form-tab-4" data-formtab="4" style="display: none;">

                    <?php $fcDownloadStepCurrent = 4; ?>
                    <?php include view_path('frontend.partials.modal.submit.form.stepper'); ?>

                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-1">Anything Else We Can Help You With?</h2>
                    <p class="fc-download-step-hint">Choose anything you also need, or pick <strong>NIL &ndash; Just Looking</strong>. One selection is required before you can finish.</p>

                    <?php include view_path('frontend.partials.modal.submit.form.other-items-needed'); ?>                        

                </div>
            </div>

            <footer class="fencing-modal-section fencing-modal-footer fencing-modal-footer--sticky fencing-modal-footer--with-actions">
                <div class="fc-download-footer-actions" data-formtab="1">
                    <div class="fc-form-plan-action fc-form-plan-action--modal-footer">
                        <button type="button" class="btn fc-btn-form-step fc-btn-next btn-orange fc-text-uppercase fc-w-b" data-move="2"><b>Next <i class="fa-solid fa-angle-right mx-2"></i> Time Frame</b></button>
                    </div>
                </div>

                <div class="fc-download-footer-actions" data-formtab="2" style="display: none;">
                    <div class="fc-form-plan-action fc-form-plan-action--modal-footer mt-0">
                        <button type="button" 
                            class="btn fc-btn-form-step btn-dark fc-text-uppercase fc-w-b" 
                            data-move="1"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                        </button>	

                        <button type="button" 
                            class="btn fc-btn-form-step fc-btn-next btn-orange fc-text-uppercase fc-w-b" 
                            data-move="4"><b>Next <i class="fa-solid fa-angle-right mx-2"></i> Anything Else</b>
                        </button>
                    </div>
                </div>

                <div class="fc-download-footer-actions" data-formtab="4" style="display: none;">
                    <div class="fc-form-plan-action fc-form-plan-action--modal-footer mt-0">
                        <button type="button" 
                            class="btn fc-btn-form-step btn-dark fc-text-uppercase fc-w-b" 
                            data-move="2"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                        </button>

                        <button type="submit" 
                            class="btn fc-btn-next btn-orange fc-text-uppercase fc-w-b disabled">
                            <b>Done <i class="fa-solid fa-angle-right mx-2"></i> View Plans &amp; Costs</b>
                        </button>
                    </div>
                </div>

                <p class="fencing-modal-footer-note mb-0 small text-muted">Fields marked with <span class="fc-text-danger">*</span> are required.</p>
            </footer>
        </div>
    </div>
</div>
