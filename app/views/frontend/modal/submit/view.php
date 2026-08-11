<div id="submit-modal" class="fencing-modal fencing-modal--v2 fencing-modal--download-plans">
    
    <div class="fc-modal-frame">

        <div class="fencing-modal-content fencing-modal-md fencing-modal--scroll-layout">
        
            <header class="fencing-modal-section fencing-modal-head fencing-modal-head--sticky">
                <div class="fencing-modal-title">
                    <h4>Download Your Project Plans</h4>
                </div>
                <button type="button" class="fencing-modal-close js-fencing-modal-close" aria-label="Close"></button>
            </header>

            <div class="fencing-modal-section fencing-modal-body fencing-modal-body--scroll">
                
                <div class="fc-alert-gray fc-mb-3 fc-alert-gray--large-h4">
                    <h4>&#127881; Awesome! We’ll email you the plans..</h4>
                    <p class="fc-text-gray">Simply enter your details below and we’ll send you your plans to download or print for your reference.</p>
                </div>

                <div class="fc-form-plan" data-formtab="1">


                    <!-- [START] FORM MODAL PROGRESS BAR - STEP 1 -->                            
<div class="fc-progress-container fc-float-r">
    <div class="fc-progress-tabs">
        <div class="fc-progress-value pt-complete"></div>
        <div class="fc-progress-value"></div>
        <div class="fc-progress-value"></div>
    </div>
    <span>1/3</span>
</div>
                    <!-- [END] FORM MODAL PROGRESS BAR - STEP 1 -->


                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Customer Details</h2>

                    <?php include __DIR__ . '/form/customer-details.php'; ?>

                </div>

                <div class="fc-form-plan" data-formtab="2" style="display: none;">


                    <!-- [START] FORM MODAL PROGRESS BAR - STEP 2 -->                            
              <div class="fc-progress-container fc-float-r">
    <div class="fc-progress-tabs">
        <div class="fc-progress-value pt-complete"></div>
        <div class="fc-progress-value pt-complete"></div>
        <div class="fc-progress-value"></div>
    </div>
    <span>2/3</span>
</div>
                    <!-- [END] FORM MODAL PROGRESS BAR - STEP 2 -->


                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-1">When Do You Need The Materials?</h2>
                    
                    <?php include __DIR__ . '/form/timeframe.php'; ?>

                </div>

                <div class="fc-form-plan form-tab-4" data-formtab="4" style="display: none;">
                    

                    <!-- [START] FORM MODAL PROGRESS BAR - STEP 4 -->
                  <div class="fc-progress-container fc-float-r">
    <div class="fc-progress-tabs">
        <div class="fc-progress-value pt-complete"></div>
        <div class="fc-progress-value pt-complete"></div>
        <div class="fc-progress-value pt-complete"></div>
    </div>
    <span>3/3</span>
</div>
                    <!-- [END] FORM MODAL PROGRESS BAR - STEP 4 -->
                    

                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Anything Else We Can Help You With?</h2>

                    <?php include __DIR__ . '/form/other-items-needed.php'; ?>                        

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
                            <b>Done <i class="fa-solid fa-angle-right mx-2"></i> View Plans & Costs</b>
                        </button>
                    </div>
                </div>

                <p class="fencing-modal-footer-note mb-0 small text-muted">Fields marked with <span class="fc-text-danger">*</span> are required.</p>
            </footer>
        </div>
    </div>
</div>
