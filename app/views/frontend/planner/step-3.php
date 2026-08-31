    <!-- [START] DISPLAY RESULT -->   
    <div class="fencing-section fencing-section--no-padding fencing-section--has-border fc-position-relative js-fc-form-step fc-d-none" data-section="3" style="display: none;">

        <div class="fencing-section__top">

            <div class="fencing-calculating">
                <div class="fc-calculating-loader">
                    <div class="fc-loader-gif"></div>
                    <h4 class="fc-text-uppercase">Calculating ...   </h4>
                </div>
            </div>

            <div class="fencing-section__cmp fencing-section--step3">
                
                <?php if( @$_SESSION['planner_id'] ): ?>
                <div class="btn-copy-link badge border text-muted float-end" data-id="quote-id-1">
                    <span id="quote-id-1"><?php echo e((string) @$_SESSION['planner_id']); ?></span>
                </div>
                <?php endif; ?>

                <div class="step-label" data-action="scroll" data-target="[data-section=3]" data-offset="54">Step <span>03</span></div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 fc-mb-2">
                    <h4 class="fencing-content-title mb-0">Configure this fence section</h4>

                    <div class="fencing-section__controls d-flex align-items-center flex-wrap ms-auto gap-1">

                        <button type="button" style="display: none;">
                            <i class="fc-icon fc-rectangle"></i>
                        </button>

                        <!-- Stepper: minus / value / plus reads small-to-large. The buttons lost
                             their labels, so each carries an aria-label instead. -->
                        <div class="fc-zoom-stepper" role="group" aria-label="Zoom the fence drawing">
                            <button type="button" class="fc-zoom-fence btn btn-sm btn-dark" data-zoom="out" aria-label="Zoom out">
                                <i class="fa fa-magnifying-glass-minus" aria-hidden="true"></i>
                            </button>

                            <div class="fc-zoom-progress js-fc-zoom-progress" aria-live="polite">100%</div>

                            <button type="button" class="fc-zoom-fence btn btn-sm btn-dark" data-zoom="in" aria-label="Zoom in">
                                <i class="fa fa-magnifying-glass-plus" aria-hidden="true"></i>
                            </button>
                        </div>

                        <button type="button" href="#" class="fc-zoom-reset js-fc-zoom-reset btn btn-sm btn-danger align-items-center text-uppercase px-2" data-zoom="reset">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span class="d-none d-sm-inline-block ms-2">Reset</span>
                        </button>

                    </div>
                </div>

            </div>

            <div class="fencing-section__cmp fencing-display-result fc-position-relative">

                <div class="js-fc-planner-step3-skeleton fc-planner-step3-skeleton fc-d-none" aria-hidden="true">
                    <div class="fc-planner-step3-skeleton__run">
                        <div class="fc-planner-step3-skeleton__post"></div>
                        <div class="fc-planner-step3-skeleton__panel"><div class="fc-planner-step3-skeleton__panel-fill"></div></div>
                        <div class="fc-planner-step3-skeleton__post"></div>
                        <div class="fc-planner-step3-skeleton__panel"><div class="fc-planner-step3-skeleton__panel-fill"></div></div>
                        <div class="fc-planner-step3-skeleton__post"></div>
                    </div>
                    <div class="fc-planner-step3-skeleton__meta">
                        <span class="fc-planner-step3-skeleton__line"></span>
                        <span class="fc-planner-step3-skeleton__line fc-planner-step3-skeleton__line--narrow"></span>
                    </div>
                </div>
                
                <div class="fencing-result-msg" style="display: none;">
                    <p>No Valid Solution. Please adjust Measurements.</p>
                </div>

                <div class="fc-project-plan-hscroll">
                    <div class="fc-result">
                        <div class="fencing-panel-items">
                            <div class="fencing-panel-rail fencing-btn-modal" 
                                data-key="rail_options" 
                                data-target="#fc-control-modal" style="display:none;"></div>

                            <div id="pp-0" class="fencing-panel-container"></div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="err-message fencing-panel-solution-msg text-center text-danger px-2 py-2" role="alert"></div>
            <div class="fc-overall pt-1 pb-2"><span class="d-oaw"></span> <span class="js-overall-label">Overall</span></div>

            <!-- [START] PANEL CONTROLS -->   
            <span class="fencing-section__cmp fencing-panel-controls"></span>


            <!-- [END] PANEL CONTROLS -->
        </div>
        
        <div class="fencing-section__bottom py-3 fc-step3-bottom">
            <div class="">

                <div class="row flex-nowrap flex-md-wrap flex-xl-nowrap align-items-stretch align-items-xl-center g-2" data-tab="1">

                    <div class="d-none d-md-block col-auto col-md-4 col-lg-4 col-xl-auto order-md-1 order-xl-1">
                        <button type="button" 
                            aria-label="Add section"
                            class="btn btn-dark fc-tab-add fencing-tab-add fc-step3-icon-btn p-3 w-100 w-xl-auto">
                            <b class="d-md-none"><i class="fa-solid fa-plus" aria-hidden="true"></i></b>
                            <b class="d-none d-md-inline"><i class="fa-solid fa-plus me-1"></i> Add Another Section</b>
                        </button>
                    </div>

                    <div class="d-none d-md-block col-auto col-md-4 col-lg-4 col-xl-auto order-md-2 order-xl-2">
                        <button type="button" 
                            aria-label="Reset section"
                            class="btn btn-danger fc-fence-reset-all fc-fence-reset fc-step3-icon-btn text-uppercase p-3 w-100 w-xl-auto">
                            <b>
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span class="d-none d-md-inline ms-1">Reset</span>
                            </b>
                        </button>
                    </div>

                    <div class="col col-md-4 col-lg-4 col-xl-auto order-md-3 order-xl-4 ms-xl-auto">
                        <button type="button" 
                            class="btn btn-orange fc-btn-next-step fc-btn-step p-3 text-uppercase w-100 w-xl-auto" 
                            data-tab="1" 
                            data-move="2"
                            data-section="4"
                            data-offset="0"
                            disabled>
                            <b class="d-xl-none">NEXT <i class="fa-solid fa-angle-right mx-1" aria-hidden="true"></i> PLAN OPTIONS</b>
                            <b class="d-none d-xl-inline">NEXT <i class="fa-solid fa-angle-right mx-2"></i> Select PLAN OPTIONS</b>
                        </button>
                    </div>

                    <div class="d-none d-md-block col-auto col-md-12 col-lg-12 col-xl-auto order-md-4 order-xl-3">
                        <button type="button" 
                            aria-label="Delete section"
                            class="btn btn-danger btn-fc-sm btn-delete-fence js-btn-delete-fence fc-step3-icon-btn fw-bold text-uppercase p-3 w-100 w-xl-auto" 
                            >
                            <span><i class="fa fa-trash-can" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Delete <span>Section</span></span></span>
                        </button>
                    </div>

                </div>

                <div class="fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                    <div class="fc-step3-options-actions d-flex flex-column flex-sm-row flex-wrap align-items-stretch gap-2">
                        <button type="button" 
                            class="btn btn-orange fc-btn-create-plan fencing-btn-modal order-2 order-sm-1 w-100 w-sm-auto" 
                            data-target="#submit-modal" 
                            disabled>
                            <strong>Create Project Plan</strong><br>
                            <small>View Costing, Plan & Materials List</small>
                        </button>

                        <button type="button" 
                            class="btn btn-step btn-outline-secondary text-uppercase fc-px-3 order-1 order-sm-2 w-100 w-sm-auto" 
                            data-tab="2" 
                            data-move="1"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- [END] DISPLAY RESULT -->
