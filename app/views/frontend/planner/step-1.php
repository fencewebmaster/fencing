<div class="fencing-section fencing-section--has-border fencing-section--no-radius-top" data-section="1">

    <div class="fencing-section__cmp fencing-section-step fencing-section--step1">

        <div class="row align-items-center">
            <div class="col-sm mb-sm-0">
                <div class="step-label" data-action="scroll" data-target="[data-section=1]" data-offset="46">Step <span>01</span></div>
                <h4 class="fencing-content-title mb-3">Choose Your Fencing Style</h4>                                    
            </div>
            <div class="col-sm">

                <div class="btn-delete-fence text-end mb-2">

                    <button type="button" 
                        class="btn btn-danger btn-sm js-btn-delete-fence px-2 text-uppercase" 
                        aria-label="Delete section"
                        style="display:none;">
                        <i class="fa fa-trash-can" aria-hidden="true"></i><span class="fc-btn-label"> Delete <span>Section</span></span>
                    </button>

                    <button type="button" 
                        class="btn btn-danger btn-sm fc-fence-reset-all px-2 text-uppercase" 
                        aria-label="Reset section"
                        style="display:none;">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span class="fc-btn-label"> Reset</span>
                    </button>

                </div>

            </div>
        </div>

    </div>

    <!-- [START] STYLES -->
    <div class="fencing-section__cmp fencing-styles">
        <div class="fencing-styles__area fencing-styles-slick-pending">

            <div class="fc-fencing-styles-skeleton" aria-hidden="true">
                <div class="fc-fencing-styles-skeleton__track">
                    <?php for ($sk = 0; $sk < 6; $sk++) : ?>
                    <div class="fc-fencing-styles-skeleton__card">
                        <div>
                            <div class="fc-fencing-styles-skeleton__img-shell">
                                <div class="fc-fencing-styles-skeleton__img-ph"></div>
                            </div>
                            <div class="fc-fencing-styles-skeleton__title-shell">
                                <span class="fc-fencing-styles-skeleton__title-ph"></span>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="js-fencing-styles-slick">

            <?php foreach( $fences as $fence ): ?>
            <?php
            $fence_is_live = ! empty( $fence['live'] );
            $fence_draft = isset( $fence['draft'] ) && is_array( $fence['draft'] ) ? $fence['draft'] : [];
            $fence_draft_description = trim( (string) ( $fence_draft['description'] ?? '' ) );
            if ( $fence_draft_description === '' ) {
                $fence_draft_description = 'Temporarily Unavailable';
            }
            $fence_draft_link = trim( (string) ( $fence_draft['link'] ?? '' ) );
            $fence_draft_link_text = trim( (string) ( $fence_draft['link_text'] ?? '' ) );
            if ( $fence_draft_link_text === '' ) {
                $fence_draft_link_text = 'Learn more';
            }
            $fence_draft_new_tab = ! empty( $fence_draft['new_tab'] );
            ?>
            <div class="fencing-style-item fencing-styles-slide<?php echo $fence_is_live ? '' : ' fencing-style-item--unavailable'; ?>"
                data-slug="<?php echo e((string) $fence['slug']); ?>"
                data-title="<?php echo e((string) $fence['title']); ?>"
                <?php if ( ! $fence_is_live ) : ?>data-live="0" aria-disabled="true"<?php endif; ?>>
                <div>

                    <div class="fencing-style-img">
                        <img src="<?php echo url() . $fence['image']; ?>" alt="<?php echo e((string) $fence['title']); ?>">
                        <?php if ( ! $fence_is_live ) : ?>
                        <div class="fencing-style-unavailable">
                            <span class="fencing-style-unavailable__text"><?php echo e($fence_draft_description); ?></span>
                            <?php if ( $fence_draft_link !== '' ) : ?>
                            <a
                                class="fencing-style-unavailable__link"
                                href="<?php echo e($fence_draft_link); ?>"
                                <?php if ( $fence_draft_new_tab ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                            ><?php echo e($fence_draft_link_text); ?></a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="fencing-style-title fw-bold">
                        <?php echo $fence['title']; ?>
                    </div>

                    <?php
                    // Names the consequence, not the glyph: this resets the whole section, and
                    // below a calculated section it does so without a confirm. strip_tags because
                    // the title is markup where it is printed above, but this is an attribute.
                    $fence_reset_label = 'Remove ' . strip_tags($fence['title']) . ' and reset this section';
                    ?>
                    <button type="button" class="fencing-style-btn js-fencing-style-btn fc-fence-reset-all fc-btn-shine" aria-label="<?php echo e($fence_reset_label); ?>" title="<?php echo e($fence_reset_label); ?>"<?php echo $fence_is_live ? '' : ' tabindex="-1" aria-hidden="true" hidden'; ?>><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>

                </div>
            </div>
            <?php endforeach; ?>    


            <div class="load-quote fencing-styles-slide" data-bs-toggle="modal" data-bs-target="#load-quote">
                <div>

                    <div class="fencing-style-img">
                        <img src="<?php echo url(); ?>public/assets/img/webp/plain-white.webp">  
                        <div class="lq-mid-desc">
                            <div class="lq-icon">
                                <i class="fa-solid fa-folder-open"></i>                                         
                            </div>
                            <!-- Two sentences, two elements: as one run of text it wrapped mid-phrase
                                 ("Already saved a / quote? Enter your / ID to continue"), so neither
                                 idea read as a unit. Separate blocks break between them instead. -->
                            <span class="lq-lead">Already saved a quote?</span>
                            <span class="lq-hint">Enter your ID to continue</span>
                        </div>
                    </div>

                    <div class="fencing-style-title fw-bold">
                        Load Quote
                    </div>

                </div>
            </div>

            </div>

            <div class="fencing-styles-load-quote-mobile fc-load-quote-mobile-only text-center">
                <button type="button"
                    class="btn btn-dark text-uppercase px-4 py-2 fencing-styles-load-quote-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#load-quote">
                    <i class="fa-solid fa-folder-open me-2"></i>Load Quote
                </button>
            </div>

        </div>          
    </div>
    <!-- [END] STYLES -->

</div>