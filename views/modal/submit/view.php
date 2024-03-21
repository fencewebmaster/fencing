<div id="submit-modal" class="fencing-modal fencing-modal--v2">
    
    <div class="fc-modal-frame">

        <div class="fencing-modal-content fencing-modal-md">
        
            <div class="fencing-modal-head">
                <div class="fencing-modal-title">
                    <h4>Download Your Project Plans</h4>
                </div>
                <button type="button" class="fencing-modal-close js-fencing-modal-close">&times;</button>
            </div>

            <div class="fencing-modal-body">
                
                <div class="fc-alert-gray fc-mb-3 fc-alert-gray--large-h4">
                    <h4>&#127881; Awesome! We’ll email you the plans..</h4>
                    <p class="fc-text-gray">Simply enter your details below and we’ll send you your plans to download or print for your reference.</p>
                </div>

                <div class="fc-form-plan" data-formtab="1">



                    <!-- START FORM MODAL PROGRESS BAR - STEP 1 -->                            
                    <div class="fc-progress-container fc-float-r">
                        <div class="fc-progress-tabs">
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value"></div>
                            <div class="fc-progress-value"></div>
                            <div class="fc-progress-value"></div>
                        </div>
                        <span>1/4</span>				
                    </div>
                    <!-- END FORM MODAL PROGRESS BAR - STEP 1 -->


                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Customer Details</h2>

                    <?php include 'views/modal/submit/form/customer-details.php'; ?>

                    <p class="fc-mb-2 fc-text-gray"><span class="fc-text-danger">*</span> = Required</p>
                    

                    <!-- START FORM MODAL ACTIONS - STEP 1 -->
                    <div class="fc-form-plan-action">
                        <button type="button" class="btn fc-btn-form-step fc-btn-next btn-orange fc-text-uppercase fc-w-b" data-move="2"><b>Next <i class="fa-solid fa-angle-right mx-2"></i> Time Frame</b></button>				
                    </div>
                    <!-- END FORM MODAL ACTIONS - STEP 1 -->


                </div>

                <div class="fc-form-plan" data-formtab="2" style="display: none;">


                    <!-- START FORM MODAL PROGRESS BAR - STEP 2 -->                            
                    <div class="fc-progress-container fc-float-r">
                        <div class="fc-progress-tabs">
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value"></div>
                            <div class="fc-progress-value"></div>
                        </div>
                        <span>2/4</span>				
                    </div>
                    <!-- END FORM MODAL PROGRESS BAR - STEP 2 -->


                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">When Do You Need The Materials?</h2>
                    
                    <?php include 'views/modal/submit/form/timeframe.php'; ?>

                    <!-- START FORM MODAL ACTIONS - STEP 2 -->
                    <div class="fc-form-plan-action mt-3">
                        <button type="button" 
                            class="btn fc-btn-form-step btn-secondary fc-text-uppercase fc-w-b" 
                            data-move="1"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                        </button>	

                        <button type="button" 
                            class="btn fc-btn-form-step fc-btn-next btn-orange fc-text-uppercase fc-w-b" 
                            data-move="3"><b>Next <i class="fa-solid fa-angle-right mx-2"></i> Install Options</b>
                        </button>					
                    </div>
                    <!-- END FORM MODAL ACTIONS - STEP 2 -->


                </div>

                <div class="fc-form-plan" data-formtab="3" style="display: none;">

                    <!-- START FORM MODAL PROGRESS BAR - STEP 3 -->                            
                    <div class="fc-progress-container fc-float-r">
                        <div class="fc-progress-tabs">
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value"></div>
                        </div>
                        <span>3/4</span>				
                    </div>
                    <!-- END FORM MODAL PROGRESS BAR - STEP 3 -->


                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">DIY OR INSTALL??</h2>
                    
                    <?php include 'views/modal/submit/form/installer.php'; ?>

                    <!-- START FORM MODAL ACTIONS - STEP 3 -->
                    <div class="fc-form-plan-action mt-3">
                        <button type="button" class="btn fc-btn-form-step btn-secondary fc-text-uppercase fc-w-b" data-move="2"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b></button>
                        <button type="button" class="btn fc-btn-form-step fc-btn-next btn-orange fc-text-uppercase fc-w-b" data-move="4"><b>Next <i class="fa-solid fa-angle-right mx-2"></i> Needed Options</b></button>
                    </div>
                    <!-- END FORM MODAL ACTIONS - STEP 3 -->


                </div>

                <div class="fc-form-plan form-tab-4" data-formtab="4" style="display: none;">
                    

                    <!-- START FORM MODAL PROGRESS BAR - STEP 4 -->
                    <div class="fc-progress-container fc-float-r">
                        <div class="fc-progress-tabs">
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value pt-complete"></div>
                            <div class="fc-progress-value pt-complete"></div>
                        </div>
                        <span>4/4</span>				
                    </div>
                    <!-- END FORM MODAL PROGRESS BAR - STEP 4 -->
                    

                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Anything Else We Can Help You With?</h2>

                    <?php include 'views/modal/submit/form/other-items-needed.php'; ?>                        

                    <!-- START FORM MODAL ACTIONS - STEP 4 | SUBMIT -->
                    <div class="fc-form-plan-action mt-2">
                        <button type="button" 
                            class="btn fc-btn-form-step btn-secondary fc-text-uppercase fc-w-b" 
                            data-move="3"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                        </button>

                        <button type="submit" 
                            class="btn fc-btn-next btn-orange fc-text-uppercase fc-w-b disabled">
                            <b>Done <i class="fa-solid fa-angle-right mx-2"></i> View Plans & Costs</b>
                        </button>
                    </div>
                    <!-- END FORM MODAL ACTIONS - STEP 4 | SUBMIT -->


                </div>
            </div>
        </div>
    </div>
</div>
