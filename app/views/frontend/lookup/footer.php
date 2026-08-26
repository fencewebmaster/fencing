<?php
/**
 * Sticky footer: result summary + pagination.
 *
 * Read-only template: LookupPageModel::pagerData() supplies the summary numbers
 * and the fully resolved pagination link set.
 *
 * @var array<string, mixed> $pager
 * @var callable $h
 */

$from  = $pager['from'];
$to    = $pager['to'];
$total = $pager['total'];
$pages = $pager['pages'];
?>
<footer class="fc-lookup__footer">
    <div class="fc-lookup__footer-summary" aria-live="polite">
        <?php if ($total > 0) : ?>
            Showing <?php echo number_format($from); ?>–<?php echo number_format($to); ?> of <?php echo number_format($total); ?> products
        <?php else : ?>
            Showing 0 products
        <?php endif; ?>
    </div>

    <?php if ($pages > 1) : ?>
    <nav class="fc-lookup-pagination" aria-label="Product pagination">
        <?php
        if ($pager['prev_url'] !== '') {
            echo '<a class="fc-lookup-pagination__btn" href="' . $h($pager['prev_url']) . '">Previous</a>';
        }
        if ($pager['show_first']) {
            echo '<a class="fc-lookup-pagination__page" href="' . $h($pager['first_url']) . '">1</a>';
            if ($pager['first_ellipsis']) {
                echo '<span class="fc-lookup-pagination__ellipsis">…</span>';
            }
        }
        foreach ($pager['window'] as $link) {
            if ($link['current']) {
                echo '<span class="fc-lookup-pagination__page is-current" aria-current="page">' . $link['num'] . '</span>';
            } else {
                echo '<a class="fc-lookup-pagination__page" href="' . $h($link['url']) . '">' . $link['num'] . '</a>';
            }
        }
        if ($pager['show_last']) {
            if ($pager['last_ellipsis']) {
                echo '<span class="fc-lookup-pagination__ellipsis">…</span>';
            }
            echo '<a class="fc-lookup-pagination__page" href="' . $h($pager['last_url']) . '">' . $pager['pages'] . '</a>';
        }
        if ($pager['next_url'] !== '') {
            echo '<a class="fc-lookup-pagination__btn" href="' . $h($pager['next_url']) . '">Next</a>';
        }
        ?>
    </nav>
    <?php endif; ?>
</footer>
