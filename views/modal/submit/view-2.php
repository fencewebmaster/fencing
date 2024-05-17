<div id="submit-modal" class="fencing-modal fencing-modal--v2 fencing-modal--project-plans">
    
    <div class="fc-modal-frame">

        <div class="fencing-modal-content fencing-modal-md">
        
            <div class="fencing-modal-head">
                <button type="button" class="fencing-modal-close js-fencing-modal-close">&times;</button>
            </div>

            <div class="fencing-modal-body">

                <div class="fc-form-plan" data-formtab="1">
                    
                    <div class="text-uppercase mb-2 fw-bold">Color Options</div>

                    <?php $colors =  convert_inputs($info['color']); ?>
                    <?php foreach( $colors as $color_data ): ?>
                    <div class="fencing-form-group fc-mb-0">
                        
                        <h6><span class="text-uppercase"><?php echo $fences[@$color_data['fence']]['title']; ?></span> - Color Options</h6>

                        <div class="fc-row fc-form-field fc-color-options" 
                            data-slug="<?php echo $color_data['fence']; ?>" 
                            data-key="color_options" 
                            name="color_options" 
                            type="text_option" 
                            value="">

                            <?php foreach(fc_color() as $co_k => $co_v): ?>

                                <?php if( in_array($co_k, $fences[$color_data['fence']]['color']) ): ?>
                                <div class="fc-col-3">

                                    <div class="fc-select-item fc-select fc-mb-0" 
                                        data-color-title="<?php echo $co_v['title']; ?>" 
                                        data-color-code="<?php echo $co_v['background_color']; ?>" 
                                        data-color-subtitle="<?php echo $co_v['sub_title']; ?>" 
                                        data-slug="<?php echo $co_k; ?>" 
                                        style="background:<?php echo $co_v['background_color']; ?>;color:<?php echo $co_v['text_color']; ?>;">
                                        <p class="mb-sm-2 mb-0"><strong><?php echo $co_v['title']; ?></strong>
                                        <span><?php echo $co_v['sub_title']; ?></span></p>
                                    </div>

                                </div>
                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <hr class="my-2">

                <div class="fc-form-plan" data-formtab="2">
                    <div class="text-uppercase mb-2 fw-bold">When Do You Need The Materials?</div>

                    <?php include 'views/modal/submit/form/timeframe.php'; ?>

                </div>

                <hr class="my-2">

                <div class="fc-form-plan" data-formtab="3">

                    <div class="text-uppercase mb-2 fw-bold">DIY OR INSTALL??</div>
                    
                    <?php include 'views/modal/submit/form/installer.php'; ?>

                </div>

                <hr class="my-2">

                <div class="fc-form-plan" data-formtab="4">
                    
                    <div class="text-uppercase mb-2 fw-bold">Anything Else We Can Help You With?</div>

                    <?php include 'views/modal/submit/form/other-items-needed.php'; ?>                        

                    <!-- [START] FORM MODAL ACTIONS - STEP 4 | SUBMIT -->
                   <div class="d-grid gap-2">
                        <button type="submit" 
                            class="btn btn-lg project-details--update btn-orange text-uppercase">
                            <b>Update</b>
                        </button>
                    </div>
                    <!-- [END] FORM MODAL ACTIONS - STEP 4 | SUBMIT -->

                </div>
            </div>
        </div>
    </div>
</div>
