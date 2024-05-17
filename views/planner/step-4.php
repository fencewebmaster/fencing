<div class="fc-section-step fc-step-4 mb-5" data-tab="2" style="display: none;">
    <div class="fencing-section fencing-section--no-padding fencing-section--has-border br-tl-0">
        
        <div class="fencing-section__top">

            <div class="step-label">Step <span>04</span></div>
            
            <h4 class="fencing-content-title fc-mb-2">Configure Your Project</h4>
        
            <div class="fc-card fc-mb-2" data-load="color-options"></div>

            <div class="fc-card">
                
                <div class="fc-card-header fc-bg-dark fc-border-top">
                    Project Notes & Additional Details
                </div>

                <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
                    <div class="p-3">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group has-clear position-relative">
                                    <textarea name="notes" placeholder="Write your notes here" class="form-control fc-form-control--textarea" rows="7"><?php echo @$info['notes']; ?></textarea>                                                        
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        
        </div>

        <div class="fencing-section__bottom py-3">
            <div class="fc-tab-control">
                <div class="fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                    
                    <button type="button" 
                        class="btn btn-orange fc-btn-create-plan fencing-btn-modal me-2 py-2 px-4 text-uppercase mb-2" 
                        data-target="#submit-modal" 
                        disabled>
                        <strong>Create Project Plan</strong><br>
                        <small>View Costing, Plan & Materials List</small>
                    </button>
                    
                    <button type="button" 
                        class="btn fc-btn-step btn-secondary text-uppercase py-3 px-4 mb-2" 
                        data-tab="2"
                        data-section="1" 
                        data-offset="110"
                        data-move="1"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                    </button>

                </div>
            </div>
        </div>

    </div>
</div>