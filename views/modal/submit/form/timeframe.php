<div class="fc-form-group fc-form-check">
    
    <?php foreach( fc_timeframe() as $timeframe_k => $timeframe_v ): ?>
    <label class="mb-1">
    <input type="radio" name="timeframe" value="<?php echo $timeframe_k; ?>" class="fc-mr-1" required>
    <?php echo $timeframe_v; ?>
    </label>
    <?php endforeach; ?>

</div>