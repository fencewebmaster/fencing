<?php
/**
 * Wizard progress for the "Download Your Project Plans" modal.
 *
 * @var array<int, string> $fcDownloadSteps       formtab value => step label
 * @var int                $fcDownloadStepCurrent formtab value of the pane being rendered
 */
$fcDownloadStepIndex = (int) array_search($fcDownloadStepCurrent, array_keys($fcDownloadSteps), true);
?>
<ol class="fc-download-steps" aria-label="Form progress">
    <?php foreach (array_values($fcDownloadSteps) as $fcDownloadStepI => $fcDownloadStepLabel): ?>
    <?php
    $fcDownloadStepState = $fcDownloadStepI < $fcDownloadStepIndex
        ? 'is-complete'
        : ($fcDownloadStepI === $fcDownloadStepIndex ? 'is-current' : 'is-upcoming');
    ?>
    <li class="fc-download-steps__item <?php echo $fcDownloadStepState; ?>"<?php echo $fcDownloadStepState === 'is-current' ? ' aria-current="step"' : ''; ?>>
        <span class="fc-download-steps__marker" aria-hidden="true">
            <?php if ($fcDownloadStepState === 'is-complete'): ?>
            <i class="fa-solid fa-check"></i>
            <?php else: ?>
            <?php echo $fcDownloadStepI + 1; ?>
            <?php endif; ?>
        </span>
        <span class="fc-download-steps__label"><?php echo e($fcDownloadStepLabel); ?></span>
        <span class="fc-download-steps__sr">
            <?php echo $fcDownloadStepState === 'is-complete' ? '(completed)' : ($fcDownloadStepState === 'is-current' ? '(current step)' : ''); ?>
        </span>
    </li>
    <?php endforeach; ?>
</ol>
