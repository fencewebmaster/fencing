<div class="fc-section-step fc-step-4 mb-5" data-tab="2" style="display: none;">
    <div class="fencing-section fencing-section--no-padding fencing-section--has-border br-tl-0">
        
        <div class="fencing-section__top">

            <div class="step-label">Step <span>04</span></div>
            
            <h4 class="fencing-content-title fc-mb-2">Configure Your Project</h4>
        
            <div class="fc-planner-color-options-wrap">
                <div class="js-fc-color-options-skeleton-host"
                    aria-busy="true"
                    aria-label="Loading colour options"></div>
                <div class="js-fc-color-options-mount fc-d-none">
                    <div class="fc-card fc-mb-2" data-load="color-options"></div>
                </div>
            </div>

            <template id="fc-planner-color-options-skeleton-card">
                <div class="fc-card fc-mb-2 fc-color-options-skeleton" aria-hidden="true">
                    <div class="fc-card-header fc-bg-dark fc-border-top fc-color-options-skeleton__header">
                        <span class="fc-color-options-skeleton__header-bar" aria-hidden="true"></span>
                    </div>
                    <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
                        <div class="fc-color-options-skeleton__track">
                            <?php for ($i = 0; $i < 6; $i++) : ?>
                            <div class="fc-color-options-skeleton__cell">
                                <div class="fc-color-options-skeleton__tile">
                                    <span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--title"></span>
                                    <span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--sub"></span>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </template>

            <div class="fc-card">
                
                <div class="fc-card-header fc-bg-dark fc-border-top">
                    Project Notes & Additional Details
                </div>

                <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
                    <div class="p-3">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group has-clear position-relative">
                                    <textarea name="notes" placeholder="Write your notes here" class="form-control fc-form-control--textarea w-100" rows="7"><?php echo htmlspecialchars((string) @$info['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        
        </div>

        <div class="fencing-section__bottom py-3">
            <div class="fc-tab-control">
                <div class="fc-section-step fencing-calculate-price fc-step4-calculate-bottom fc-d-none" data-tab="2" style="display: none;">

                    <div class="d-flex flex-wrap align-items-stretch gap-2 fc-step4-bottom-btns">
                        <?php if( @$_SESSION['planner_id'] && @$_SESSION['fc_data']['name'] ): ?>
                        <div class="flex-shrink-0 d-flex order-1">
                            <button type="submit" class="btn btn-orange fc-btn-update py-3 px-3 px-sm-4 text-uppercase align-self-stretch fc-step4-btn-update" disabled>
                                <i class="fa-regular fa-pen-to-square me-1"></i>
                                <b>UPDATE</b>
                                <br>
                                <small>Go to Project Plan & Cart</small>
                            </button>
                        </div>
                        <?php endif; ?>
                        <div class="flex-grow-1 d-flex order-2" style="min-width: 0;">
                            <?php $fc_step4_plan_btn_outline = @$_SESSION['planner_id'] && @$_SESSION['fc_data']['name']; ?>
                            <button type="button"
                                class="btn <?php echo $fc_step4_plan_btn_outline ? 'btn-orange-outline' : 'btn-orange'; ?> fc-btn-create-plan fencing-btn-modal h-100 py-3 px-3 px-sm-4 text-uppercase align-self-stretch"
                                data-target="#submit-modal"
                                disabled>
                                <?php if( @$_SESSION['planner_id'] && @$_SESSION['fc_data']['name'] ): ?>
                                <strong>Update Details</strong><br>
                                <?php else: ?>
                                <strong>Create Project Plan</strong><br>
                                <?php endif; ?>

                                <small>View Costing, Plan & Materials List</small>
                            </button>
                        </div>
                        <div class="flex-shrink-0 d-flex order-3 ms-auto">
                            <button type="button"
                                class="btn fc-btn-step btn-dark text-uppercase py-3 px-3 px-sm-4 align-self-stretch fc-step4-btn-back"
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

    </div>
</div>