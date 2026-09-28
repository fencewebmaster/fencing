<?php
/**
 * FC Admin — list pagination: one .btn-group of page links (buttons.css).
 *
 * Read-only partial shared by Planner Entries, Users and both product pages. The presenter's pagination
 * array gates it ('show'); ViewHelper::paginationLinks() shapes the links.
 *
 * @var array<string, mixed>       $fcPagination      show, prev_url, next_url
 * @var list<array<string, mixed>> $fcPaginationLinks type (link|current|ellipsis), label, url
 * @var string                     $fcPaginationLabel the nav's aria-label
 */
?>
<?php if (!empty($fcPagination['show'])) : ?>
<nav class="fc-entries-page__pagination" aria-label="<?php echo e($fcPaginationLabel); ?>">
    <div class="btn-group">
        <?php if (($fcPagination['prev_url'] ?? '') !== '') : ?>
        <a class="btn btn-sm btn-light" href="<?php echo e((string) $fcPagination['prev_url']); ?>" aria-label="Previous page" title="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
        <?php endif; ?>
        <?php foreach ($fcPaginationLinks as $fcPaginationLink) : ?>
            <?php if (($fcPaginationLink['type'] ?? '') === 'ellipsis') : ?>
        <span class="btn btn-sm btn-light disabled" aria-hidden="true">…</span>
            <?php elseif (($fcPaginationLink['type'] ?? '') === 'current') : ?>
        <span class="btn btn-sm btn-light" aria-current="page"><?php echo e((string) ($fcPaginationLink['label'] ?? '')); ?></span>
            <?php else : ?>
        <a class="btn btn-sm btn-light" href="<?php echo e((string) ($fcPaginationLink['url'] ?? '#')); ?>"><?php echo e((string) ($fcPaginationLink['label'] ?? '')); ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (($fcPagination['next_url'] ?? '') !== '') : ?>
        <a class="btn btn-sm btn-light" href="<?php echo e((string) $fcPagination['next_url']); ?>" aria-label="Next page" title="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>
