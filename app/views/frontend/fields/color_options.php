<div class="fc-card fc-mb-2" data-fc-planner-fence-title="{{title}}">
    
    <div class="fc-card-header fc-bg-dark fc-border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="fc-color-options-planner-heading d-flex align-items-start align-items-sm-center gap-2 min-w-0 flex-grow-1">
            <span class="fc-color-options-planner-status-icon fc-color-options-planner-status-icon--bad" aria-hidden="true">
                <i class="fa-solid fa-exclamation fc-color-options-planner-status-icon__inner"></i>
            </span>
            <span class="fc-color-options-planner-title-text"><strong>{{title}}</strong> - Colour Options</span>
        </div>
        <span class="fc-color-options-section-count text-nowrap" aria-label="Number of fence sections">{{section_count_label}}</span>
    </div>

    <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
        <div class="fencing-form-group fc-mb-0">
            <div class="fc-color-options-slick-area">
                <div class="fc-form-field fc-color-options js-fc-color-options-slick" data-key="color_options" data-slug="{{slug}}" name="color_options" type="text_option" value="">
                    <?php foreach(fc_color() as $co_k => $co_v): ?>
                    <div class="fc-color-options__slide">
                        <div class="fc-select-item fc-select fc-select-color fc-mb-0" data-color-title="<?php echo $co_v['title']; ?>" data-color-code="<?php echo $co_v['background_color']; ?>" data-color-subtitle="<?php echo $co_v['sub_title']; ?>" data-slug="<?php echo $co_k; ?>" style="background:<?php echo $co_v['background_color']; ?>;color:<?php echo $co_v['text_color']; ?>;">
                            <p><strong><?php echo $co_v['title']; ?></strong><br />
                            <span><?php echo $co_v['sub_title']; ?></span></p>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>

</div>