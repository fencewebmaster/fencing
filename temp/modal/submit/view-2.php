<div id="submit-modal" class="fencing-modal fencing-modal--v2 fencing-modal--project-plans">
    
    <div class="fc-modal-frame">

        <div class="fencing-modal-content fencing-modal-md">
        
            <div class="fencing-modal-head">
                <button type="button" class="fencing-modal-close js-fencing-modal-close">&times;</button>
            </div>

            <div class="fencing-modal-body">

                <div class="fc-form-plan" data-formtab="1">
                    
                    <div class="text-uppercase mb-2 fw-bold">Color Options</div>

                    <div class="fencing-form-group fc-mb-0">
                        <div class="fc-row fc-form-field fc-color-options" data-key="color_options" name="color_options" type="text_option" value="">
                            <?php foreach(fc_color() as $co_k => $co_v): ?>
                            <div class="fc-col-3">
                                <div class="fc-select-item fc-select fc-mb-0" data-color-title="<?php echo $co_v['title']; ?>" data-color-code="<?php echo $co_v['background_color']; ?>" data-color-subtitle="<?php echo $co_v['sub_title']; ?>" data-slug="<?php echo $co_k; ?>" style="background:<?php echo $co_v['background_color']; ?>;color:<?php echo $co_v['text_color']; ?>;">
                                    <p><strong><?php echo $co_v['title']; ?></strong>
                                    <span><?php echo $co_v['sub_title']; ?></span></p>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                </div>

                <hr>

                <div class="fc-form-plan" data-formtab="2">
                    <div class="text-uppercase mb-2 fw-bold">When Do You Need The Materials?</div>

                    <?php include 'temp/modal/submit/form/timeframe.php'; ?>

                </div>

                <hr>

                <div class="fc-form-plan" data-formtab="3">

                    <div class="text-uppercase mb-2 fw-bold">Do You Need An Installer?</div>
                    
                    <?php include 'temp/modal/submit/form/installer.php'; ?>

                </div>

                <hr>

                <div class="fc-form-plan" data-formtab="4">
                    
                  
                    <div class="text-uppercase mb-2 fw-bold">Anything Else We Can Help You With?</div>

                    <?php include 'temp/modal/submit/form/other-items-needed.php'; ?>                        

                    <!-- START FORM MODAL ACTIONS - STEP 4 | SUBMIT -->
                   <div class="d-grid gap-2">
                        <button type="submit" 
                            class="btn-fc project-details--update btn-fc-orange fc-text-uppercase fc-w-b">
                            <b>Update</b>
                        </button>
                    </div>
                    <!-- END FORM MODAL ACTIONS - STEP 4 | SUBMIT -->


                </div>
            </div>
        </div>
    </div>
</div>
