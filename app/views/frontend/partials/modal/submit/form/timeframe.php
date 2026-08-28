<?php
// Pre-select from session, same reason as other-items-needed.php: the project-plan editor
// reopens this partial on an existing quote, and an unchecked required radio blocked Update
// on a choice the customer had already made.
$fc_timeframe_session = isset($_SESSION['fc_data']['timeframe'])
    ? (string) $_SESSION['fc_data']['timeframe']
    : '';
?>
<div class="fc-form-group fc-form-check fc-mb-1">
    
    <?php foreach( fc_timeframe() as $timeframe_k => $timeframe_v ): ?>
    <label class="mb-1">
    <input type="radio" name="timeframe" value="<?php echo e((string) $timeframe_k); ?>" class="fc-mr-1"<?php echo $fc_timeframe_session === (string) $timeframe_k ? ' checked' : ''; ?> required>
    <?php echo e((string) $timeframe_v); ?>
    </label>
    <?php endforeach; ?>

</div>
