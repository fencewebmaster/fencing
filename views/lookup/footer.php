<?php
/**
 * Sticky footer: result summary + pagination.
 *
 * @var array<string, mixed> $page
 * @var callable $h
 */

declare(strict_types=1);

$req = is_array($page['request'] ?? null) ? $page['request'] : [];
$from = (int) ($page['from'] ?? 0);
$to = (int) ($page['to'] ?? 0);
$total = (int) ($page['total'] ?? 0);
$pages = (int) ($page['pages'] ?? 0);
$current = (int) ($req['page'] ?? 1);
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
        $window = 2;
        $start = max(1, $current - $window);
        $end = min($pages, $current + $window);
        if ($current > 1) {
            $prev = fc_lookup_url($req, ['page' => $current - 1, 'view' => null]);
            echo '<a class="fc-lookup-pagination__btn" href="' . $h($prev) . '">Previous</a>';
        }
        if ($start > 1) {
            echo '<a class="fc-lookup-pagination__page" href="' . $h(fc_lookup_url($req, ['page' => 1, 'view' => null])) . '">1</a>';
            if ($start > 2) {
                echo '<span class="fc-lookup-pagination__ellipsis">…</span>';
            }
        }
        for ($p = $start; $p <= $end; $p++) {
            if ($p === $current) {
                echo '<span class="fc-lookup-pagination__page is-current" aria-current="page">' . $p . '</span>';
            } else {
                echo '<a class="fc-lookup-pagination__page" href="' . $h(fc_lookup_url($req, ['page' => $p, 'view' => null])) . '">' . $p . '</a>';
            }
        }
        if ($end < $pages) {
            if ($end < $pages - 1) {
                echo '<span class="fc-lookup-pagination__ellipsis">…</span>';
            }
            echo '<a class="fc-lookup-pagination__page" href="' . $h(fc_lookup_url($req, ['page' => $pages, 'view' => null])) . '">' . $pages . '</a>';
        }
        if ($current < $pages) {
            $next = fc_lookup_url($req, ['page' => $current + 1, 'view' => null]);
            echo '<a class="fc-lookup-pagination__btn" href="' . $h($next) . '">Next</a>';
        }
        ?>
    </nav>
    <?php endif; ?>
</footer>
