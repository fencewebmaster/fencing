<div class="fencing-section fencing-section--has-border fencing-section--no-radius-top" data-section="1">

    <!-- [START] STYLES AREA -->
    <div class="fencing-section__cmp fencing-section-step fencing-section--step1">

        <div class="row align-items-center">
            <div class="col-sm mb-sm-0 mb-3">
                <div class="step-label" data-action="scroll" data-target="[data-section=1]" data-offset="46">Step <span>01</span></div>

                <h4 class="fencing-content-title">Choose Your Fencing Style</h4>                                    
            </div>
            <div class="col-sm">

                <div class="btn-delete-fence text-end">

                    <button type="button" 
                        class="btn btn-danger btn-sm js-btn-delete-fence px-2 fw-bold text-uppercase" 
                        style="display:none;">
                        <i class="fa fa-trash-can me-1"></i> Delete <span>Section</span>
                    </button>

                    <button type="button" 
                        class="btn btn-danger btn-sm fc-fence-reset-all px-2 fw-bold text-uppercase" 
                        style="display:none;">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </button>

                </div>

            </div>
        </div>

    </div>

    <!-- [START] STYLES -->
    <div class="fencing-section__cmp fencing-styles">
        <div class="fencing-styles__area row">

            <?php foreach( $fences as $fence ): ?>
            <div class="fencing-style-item col-lg-2 col-md-3 col-sm-4 col-6 mb-3 px-2" data-slug="<?php echo $fence['slug']; ?>" data-title="<?php echo $fence['title']; ?>">
                <div>

                    <div class="fencing-style-img">
                        <img src="<?php echo base_url().$fence['image']; ?>">               
                    </div>

                    <div class="fencing-style-title fw-bold fs-6">
                        <?php echo $fence['title']; ?>
                    </div>

                    <button class="fencing-style-btn js-fencing-style-btn fc-fence-reset-all"><i class="fa fa-times"></i></button>

                </div>
            </div>
            <?php endforeach; ?>    


            <div class="load-quote col-lg-2 col-md-3 col-sm-4 col-6 mb-3 px-2" data-bs-toggle="modal" data-bs-target="#load-quote">
                <div>

                    <div class="fencing-style-img">
                        <img src="<?php echo base_url(); ?>assets/img/webp/plain-white.webp">  
                        <div class="lq-mid-desc">
                            <div class="lq-icon">
                                <i class="fa-solid fa-file-circle-plus"></i>                                         
                            </div>
                            Click Here to<br> Load Quote                                                             
                        </div>
                    </div>

                    <div class="fencing-style-title fw-bold fs-6">
                        Load Quote
                    </div>

                </div>
            </div>

        </div>          
    </div>
    <!-- [END] STYLES -->

</div>