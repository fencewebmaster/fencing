<div class="fc-other-products fc-form-group fc-mb-1">
    <div class="fc-row-container">
        <input type="hidden" name="extra" value="">
        <?php foreach( fc_extra_needed() as $extra_k => $extra_v ): ?>
        <div class="fc-col-3">
            <div class="fc-form-check-img fc-rounded">
                <label class="fc-form-check">
                <img class="fc-rounded" src="img/plans/<?php echo $extra_k; ?>.png">								
                <input type="checkbox" name="extra[]" value="<?php echo $extra_k; ?>">
                </label>
                <p class="fc-text-center"><?php echo $extra_v; ?></p>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="fc-col-3">
            <div class="fc-form-check-img fc-form-check-empty fc-rounded">
                <label class="fc-form-check">
                    <div class="fc-empty-img">
                        <span>Nothing Extra<br>
                        Just Fencing</span>
                    </div>
                    <input type="radio" name="nothing_extra" value="nothing">
                </label>
                <p class="fc-text-center">NIL - Just Looking</p>
            </div>
        </div>

    </div>
</div>