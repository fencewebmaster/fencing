    <!-- START DISPLAY RESULT -->   
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
                    <span id="quote-id-1"><?php echo @$_SESSION['planner_id']; ?></span>
                </div>
                <?php endif; ?>

                <div class="step-label">Step <span>03</span></div>

                <h4 class="fencing-content-title fc-mb-2">Configure this fence section</h4>

                <div class="fencing-section__controls d-flex">
                    
                    <a href="#" style="display: none;">
                        <i class="fc-icon fc-rectangle"></i>
                    </a>

                    <a href="#" class="fc-zoom-fence btn btn-sm btn-secondary d-flex align-items-center text-uppercase px-2 fw-bold" data-zoom="in">
                        <i class="fa fa-magnifying-glass-minus"></i>
                        <span class="d-none d-sm-inline-block ms-2">Zoom in</span>
                    </a>

                    <div class="fc-zoom-progress js-fc-zoom-progress mx-2">100%</div>

                    <a href="#" class="fc-zoom-fence btn btn-sm btn-secondary d-flex align-items-center me-2 text-uppercase px-2 fw-bold" data-zoom="out">
                        <i class="fa fa-magnifying-glass-plus"></i>
                        <span class="d-none d-sm-inline-block ms-2">Zoom out</span>
                    </a>

                    <a href="#" class="fc-zoom-reset js-fc-zoom-reset btn btn-sm btn-danger align-items-center text-uppercase px-2 fw-bold" data-zoom="reset">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span class="d-none d-sm-inline-block ms-2">Reset</span>
                    </a>

                </div>                                        

            </div>

            <div class="fencing-section__cmp fencing-display-result">
                
                <div class="fencing-result-msg" style="display: none;">
                    <p>No Valid Solution. Please adjust Measurements.</p>
                </div>

                <div class="fencing-panel-items">
                    <div class="fencing-panel-rail fencing-btn-modal" 
                        data-key="rail_options" 
                        data-target="#fc-control-modal" style="display:none;"></div>
                    <div id="pp-0" class="fencing-panel-container"></div>
                </div>

            </div>

            <!-- START PANEL CONTROLS -->   
            <div class="fencing-section__cmp fencing-panel-controls"></div>


            <!-- END PANEL CONTROLS -->
        </div>
        
        <div class="fencing-section__bottom py-3">
            <div class="">

                <div class="row" data-tab="1">


                    <?php if( @$_SESSION['planner_id'] && @$_SESSION['fc_data']['name'] ): ?>
                    <div class="col-lg-auto col-sm-6 px-1 mb-lg-0 mb-2">
                        <button type="submit" class="btn btn-orange fc-btn-update py-3 px-4 w-100">
                            <i class="fa-regular fa-pen-to-square me-1"></i> 
                            <b>UPDATE</b>
                        </button>
                    </div>
                    <?php endif; ?> 

                    <div class="col-lg col-sm-6 px-1 mb-lg-0 mb-2">
                        <button type="button" 
                            class="btn btn-orange fc-btn-next-step fc-btn-step p-3 text-uppercase w-100" 
                            data-tab="1" 
                            data-move="2"
                            disabled>
                            <b>NEXT <i class="fa-solid fa-angle-right mx-2"></i> Select PLAN OPTIONS</b>
                        </button>
                    </div>

                    <div class="col-lg col-sm-6 px-1 mb-lg-0 mb-2">
                        <button type="button" 
                            class="btn btn-dark fc-tab-add fencing-tab-add p-3 w-100">
                            <b>
                                <i class="fa-solid fa-plus me-1"></i> Add Another Section
                            </b>
                        </button>
                    </div>

                    <div class="col-lg-auto col-sm-6 col-auto px-1 mb-lg-0 mb-2">
                        <button type="button" 
                            class="btn btn-danger fc-fence-reset-all fc-fence-reset text-uppercase p-3 w-100">
                            <b>
                                <i class="fa-solid fa-rotate-left me-1"></i>
                                Reset
                            </b>
                        </button>
                    </div>

                    <div class="col-lg-auto col-sm-6 col px-1 mb-lg-0 mb-2">
                        <button type="button" 
                            class="btn btn-danger btn-fc-sm btn-delete-fence js-btn-delete-fence fw-bold text-uppercase p-3 w-100" 
                            >
                            <span><i class="fa fa-trash-can me-1"></i> Delete <span>Section</span></span>
                        </button>
                    </div>

                </div>

                <div class="fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                    
                    <button type="button" 
                        class="btn btn-orange fc-btn-create-plan fencing-btn-modal" 
                        data-target="#submit-modal" 
                        disabled>
                        <strong>Create Project Plan</strong><br>
                        <small>View Costing, Plan & Materials List</small>
                    </button>

                    <button type="button" 
                        class="btn btn-step btn-outline-secondary text-uppercase fc-px-3" 
                        data-tab="2" 
                        data-move="1"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                    </button>

                </div>

            </div>

        </div>
    </div>
    <!-- END DISPLAY RESULT -->
