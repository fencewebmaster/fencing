<div id="submit-modal" class="fencing-modal fencing-modal--v2 fencing-modal--project-plans">
    
    <div class="fc-modal-frame">

        <div class="fencing-modal-content fencing-modal-md">
        
            <div class="fencing-modal-head">
                <button type="button" class="fencing-modal-close js-fencing-modal-close">&times;</button>
            </div>

            <div class="fencing-modal-body">

                <div class="fc-form-plan" data-formtab="1">
                    
                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-1">Color Options</h2>

                    <div class="fencing-form-group fc-mb-0">
                        <div class="fc-row fc-form-field fc-color-options" data-key="color_options" name="color_options" type="text_option" value="">
                            <?php foreach(fc_color() as $co_k => $co_v): ?>
                            <div class="fc-col-3">
                                <div class="fc-select-item fc-select fc-mb-0" data-color-title="<?php echo $co_v['title']; ?>" data-color-code="<?php echo $co_v['background_color']; ?>" data-color-subtitle="<?php echo $co_v['sub_title']; ?>" data-slug="<?php echo $co_k; ?>" style="background:<?php echo $co_v['background_color']; ?>;color:<?php echo $co_v['text_color']; ?>;">
                                    <p><strong><?php echo $co_v['title']; ?></strong><br />
                                    <span><?php echo $co_v['sub_title']; ?></span></p>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                </div>

                <div class="fc-form-plan" data-formtab="2">

                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-1">When Do You Need The Materials?</h2>
                    
                    <?php include 'temp/modal/submit/form/timeframe.php'; ?>

                </div>

                <div class="fc-form-plan" data-formtab="3">

                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-1">Do You Need An Installer?</h2>
                    
                    <?php include 'temp/modal/submit/form/installer.php'; ?>

                </div>

                <div class="fc-form-plan form-tab-4" data-formtab="4">
                    
                  
                    <h2 class="fc-text-uppercase fc-font-2 fc-mb-1">Anything Else We Can Help You With?</h2>

                    <?php include 'temp/modal/submit/form/other-items-needed.php'; ?>                        

                    <!-- START FORM MODAL ACTIONS - STEP 4 | SUBMIT -->
                    <div class="fc-form-plan-action">
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
