<?php
use Fc\Admin\Services\CartBuilderService;
?>
<div id="submit-modal" class="fencing-modal fencing-modal--v2 fencing-modal--project-plans">
    
    <div class="fc-modal-frame">

        <div class="fencing-modal-content fencing-modal-md fencing-modal--scroll-layout fencing-modal-content--project-plans">
            <button type="button" class="fencing-modal-close js-fencing-modal-close fencing-modal-close--project-plans" aria-label="Close"></button>

            <div class="fencing-modal-section fencing-modal-body fencing-modal-body--scroll fencing-modal-body--project-plans">

                <div class="fc-form-plan" data-formtab="1">
                    
                    <div class="text-uppercase mb-2 fw-bold">Color Options</div>

                    <?php
                    $colors = CartBuilderService::convertInputs($info['color']);
                    ?>
                    <?php foreach( $colors as $color_data ): ?>
                    <div class="fencing-form-group fc-mb-0">
                        <?php
                        $_fence_key_modal = isset( $color_data['fence'] ) ? $color_data['fence'] : '';
                        $_fence_title_modal = ( $_fence_key_modal !== '' && isset( $fences[ $_fence_key_modal ]['title'] ) )
                            ? $fences[ $_fence_key_modal ]['title']
                            : $_fence_key_modal;
                        $_color_pick_modal = isset( $color_data['color'] ) ? $color_data['color'] : '';
                        $_color_row_modal    = fc_color( $_color_pick_modal );
                        $_color_title_modal  = ( is_array( $_color_row_modal ) && ! empty( $_color_row_modal['title'] ) )
                            ? $_color_row_modal['title']
                            : '';
                        ?>
                        <h6 class="fc-modal-color-options-heading mb-2 fw-normal">
                            <strong><?php echo e((string) $_fence_title_modal); ?></strong>
                            <?php if ( $_color_title_modal !== '' ) : ?>
                            - <?php echo e((string) $_color_title_modal); ?>
                            <?php else : ?>
                            - Color Options
                            <?php endif; ?>
                        </h6>

                        <div class="fc-color-options-slick-area fc-color-options-slick-pending">
                            <div class="fc-modal-color-options-skeleton js-fc-modal-color-options-skeleton" aria-busy="true" aria-hidden="true">
                                <div class="fc-color-options-skeleton__track">
                                    <?php for ( $sk_i = 0; $sk_i < 6; $sk_i++ ) : ?>
                                    <div class="fc-color-options-skeleton__cell">
                                        <div class="fc-color-options-skeleton__tile">
                                            <span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--row1"></span>
                                            <span class="fc-color-options-skeleton__divider" aria-hidden="true"></span>
                                            <span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--row2"></span>
                                            <span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--row3"></span>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="fc-form-field fc-color-options js-fc-color-options-slick"
                                data-slug="<?php echo $color_data['fence']; ?>"
                                data-key="color_options"
                                name="color_options"
                                type="text_option"
                                value="">

                                <?php
                                $_fence_colors_modal = (isset($fences[$color_data['fence']]['color']) && is_array($fences[$color_data['fence']]['color']))
                                    ? $fences[$color_data['fence']]['color']
                                    : [];
                                foreach ($_fence_colors_modal as $co_k):
                                    $co_v = fc_color($co_k);
                                    if (!is_array($co_v)) {
                                        continue;
                                    }
                                ?>
                                    <div class="fc-color-options__slide">

                                        <div class="fc-select-item fc-select fc-select-color fc-mb-0 <?php echo $color_data['color']==$co_k?'fc-selected':''; ?>"
                                            data-color-title="<?php echo e((string) $co_v['title']); ?>"
                                            data-color-code="<?php echo e((string) $co_v['background_color']); ?>"
                                            data-color-subtitle="<?php echo e((string) $co_v['sub_title']); ?>"
                                            data-slug="<?php echo e((string) $co_k); ?>"
                                            style="background:<?php echo e((string) $co_v['background_color']); ?>;color:<?php echo e((string) $co_v['text_color']); ?>;">
                                            <p class="mb-sm-2 mb-0"><strong><?php echo e((string) $co_v['title']); ?></strong>
                                            <span><?php echo e((string) $co_v['sub_title']); ?></span></p>
                                        </div>

                                    </div>
                                <?php endforeach; ?>

                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <hr class="my-2">

                <div class="fc-form-plan" data-formtab="2">
                    <div class="text-uppercase mb-2 fw-bold">When Do You Need The Materials?</div>

                    <?php include view_path('frontend.partials.modal.submit.form.timeframe'); ?>

                </div>

                <hr class="my-2">

                <div class="fc-form-plan" data-formtab="3">
                    
                    <div class="text-uppercase mb-2 fw-bold">Anything Else We Can Help You With?</div>

                    <?php include view_path('frontend.partials.modal.submit.form.other-items-needed'); ?>                        

                </div>
            </div>

            <footer class="fencing-modal-section fencing-modal-footer fencing-modal-footer--sticky fencing-modal-footer--project-plans">
                <div class="d-grid gap-2 fc-project-plans-modal-footer-actions">
                    <button type="submit" 
                        class="btn btn-lg project-details--update btn-orange text-uppercase">
                        <b>Update</b>
                    </button>
                </div>
            </footer>
        </div>
    </div>
</div>
