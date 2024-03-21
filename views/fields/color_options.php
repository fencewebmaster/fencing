<div class="fc-card fc-mb-2">
    
    <div class="fc-card-header fc-bg-dark fc-border-top">
        <strong>{{title}}</strong> - Colour Options
    </div>

    <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
        <div class="fencing-form-group fc-mb-0">
            <div class="row fc-form-field fc-color-options" data-key="color_options" data-slug="{{slug}}" name="color_options" type="text_option" value="">
                <?php foreach(fc_color() as $co_k => $co_v): ?>
                <div class="col-md-3 col-sm-4 col-12 mb-2">
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