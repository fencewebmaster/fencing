<?php
/**
 * FC Admin — Fence styles list (server-rendered).
 *
 * @var array<string, mixed> $fcFenceStylesPage
 */

declare(strict_types=1);

if (!isset($fcFenceStylesPage) || !is_array($fcFenceStylesPage)) {
    return;
}

$h = 'fc_products_admin_h';
$page = $fcFenceStylesPage;
?>
<div class="fc-fs-styles-page" data-fc-fence-styles-server="1">
    <script type="application/json" id="fc-fence-styles-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <?php if (($page['error'] ?? '') !== '') : ?>
    <div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
        <p class="font-semibold">Could not load fence styles</p>
        <p class="mt-1 text-sm"><?php echo $h((string) $page['error']); ?></p>
    </div>
    <?php elseif (empty($page['has_styles'])) : ?>
    <div class="p-8 text-center text-sm text-slate-500">No fence styles found in data/fences.</div>
    <?php else : ?>
    <div class="fc-admin-fence-styles">
        <div class="fc-admin-fence-styles__grid">
            <?php foreach ($page['cards'] as $card) : ?>
            <?php if (!empty($card['can_view'])) : ?>
            <a
                href="<?php echo $h((string) ($card['edit_href'] ?? '#')); ?>"
                class="fencing-style-item fc-admin-fence-style-item fc-admin-fence-style-link"
                data-route="<?php echo $h((string) ($card['edit_route'] ?? '')); ?>"
                data-title="<?php echo $h((!empty($card['can_edit']) ? 'Edit ' : 'View ') . ($card['title'] ?? '')); ?>"
                aria-label="<?php echo $h((!empty($card['can_edit']) ? 'Edit ' : 'View ') . ($card['title'] ?? '')); ?>"
            >
            <?php else : ?>
            <div
                class="fencing-style-item fc-admin-fence-style-item"
                aria-label="<?php echo $h((string) ($card['title'] ?? '')); ?>"
            >
            <?php endif; ?>
                <div>
                    <div class="fencing-style-img">
                        <?php if (!empty($card['has_image'])) : ?>
                        <img src="<?php echo $h((string) ($card['image_url'] ?? '')); ?>" alt="" loading="lazy" decoding="async">
                        <?php else : ?>
                        <span class="fc-admin-fence-style-img-placeholder" aria-hidden="true">
                            <i class="fa-solid fa-image text-2xl text-slate-300"></i>
                        </span>
                        <?php endif; ?>
                        <span class="fc-admin-fence-style-badge <?php echo $h((string) ($card['badge_class'] ?? '')); ?>">
                            <?php echo $h((string) ($card['badge_label'] ?? '')); ?>
                        </span>
                    </div>
                    <div class="fencing-style-title fw-bold"><?php echo $h((string) ($card['title'] ?? '')); ?></div>
                </div>
            <?php if (!empty($card['can_view'])) : ?>
            </a>
            <?php else : ?>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
