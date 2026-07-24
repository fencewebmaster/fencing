<?php
/**
 * @var array<string, mixed> $page
 * @var callable $h
 */

declare(strict_types=1);

$isBootError = empty($page['ok']);
$clearUrl = (string) ($page['clear_url'] ?? fc_lookup_base_path());
?>
<div class="fc-lookup-empty" role="status">
    <div class="fc-lookup-empty__icon" aria-hidden="true">
        <i class="fa-solid <?php echo $isBootError ? 'fa-triangle-exclamation' : 'fa-box-open'; ?>"></i>
    </div>
    <?php if ($isBootError) : ?>
        <h2 class="fc-lookup-empty__title">Lookup unavailable</h2>
        <p class="fc-lookup-empty__text"><?php echo $h((string) ($page['error'] ?? 'Could not load WordPress / WooCommerce.')); ?></p>
    <?php else : ?>
        <h2 class="fc-lookup-empty__title">No products found</h2>
        <p class="fc-lookup-empty__text">Try adjusting your search or filters to see more results.</p>
        <?php if (!empty($page['has_active_filters'])) : ?>
        <a class="btn btn-sm btn-orange fw-semibold" href="<?php echo $h($clearUrl); ?>">Reset filters</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
