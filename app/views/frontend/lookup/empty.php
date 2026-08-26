<?php
/**
 * Read-only template: LookupPageModel::emptyStateData() supplies both values.
 *
 * @var array<string, mixed> $emptyState
 * @var callable $h
 */

$isBootError = $emptyState['is_boot_error'];
$clearUrl    = $emptyState['clear_url'];
?>
<div class="fc-lookup-empty" role="status">
    <div class="fc-lookup-empty__icon" aria-hidden="true">
        <i class="fa-solid <?php echo $isBootError ? 'fa-triangle-exclamation' : 'fa-box-open'; ?>"></i>
    </div>
    <?php if ($isBootError) : ?>
        <h2 class="fc-lookup-empty__title">Lookup unavailable</h2>
        <p class="fc-lookup-empty__text"><?php echo $h($emptyState['error']); ?></p>
    <?php else : ?>
        <h2 class="fc-lookup-empty__title">No products found</h2>
        <p class="fc-lookup-empty__text">Try adjusting your search or filters to see more results.</p>
        <?php if ($emptyState['has_active']) : ?>
        <a class="btn btn-sm btn-orange fw-semibold" href="<?php echo $h($clearUrl); ?>">Reset filters</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
