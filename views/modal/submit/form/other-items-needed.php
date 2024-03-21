<div class="fc-other-products fc-form-group">
    <div class="row">
        <input type="hidden" name="extra" value="">
        <?php foreach( fc_extra_needed() as $extra_k => $extra_v ): ?>
        <div class="col-md-3 col-sm-4 col-6">
            <div class="fc-form-check-img fc-rounded mb-3">
                <label class="fc-form-check">
                <img class="fc-rounded" src="<?php echo base_url(); ?>img/plans/webp/<?php echo $extra_k; ?>.webp">								
                <input type="checkbox" name="extra[]" value="<?php echo $extra_k; ?>">
                </label>
                <div class="text-center fw-bold py-2 small"><?php echo $extra_v; ?></div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col-md-3 col-sm-4 col-6">
            <div class="fc-form-check-img fc-form-check-empty fc-rounded mb-3">
                <label class="fc-form-check">
                    <div class="fc-empty-img">
                        <span>Nothing Extra<br>
                        Just Fencing</span>
                    </div>
                    <input type="radio" name="nothing_extra" value="nothing">
                </label>
                <div class="text-center fw-bold py-2 small">NIL - Just Looking</div>
            </div>
        </div>

    </div>
</div>