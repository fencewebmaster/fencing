<?php
/**
 * @var array<string, mixed> $page
 * @var callable $h
 */

declare(strict_types=1);

$req = is_array($page['request'] ?? null) ? $page['request'] : [];
$layout = (($req['layout'] ?? 'grid') === 'list') ? 'list' : 'grid';
$logoUrl = isset($fcLookupLogoUrl) ? (string) $fcLookupLogoUrl : '';
$catalog = is_array($page['catalog'] ?? null) ? $page['catalog'] : (function_exists('fc_catalog_get') ? fc_catalog_get() : []);
$sidebarTitle = trim((string) ($catalog['sidebarTitle'] ?? 'Product Lookup'));
if ($sidebarTitle === '') {
    $sidebarTitle = 'Product Lookup';
}
$sidebarSubtitle = trim((string) ($catalog['sidebarSubtitle'] ?? 'Search the live catalog'));
?>
<div class="fc-lookup" data-fc-lookup data-fc-lookup-layout="<?php echo $h($layout); ?>">
    <?php if (empty($page['ok'])) : ?>
        <div class="fc-lookup__content fc-lookup__content--solo">
            <header class="fc-lookup__topbar">
                <div class="fc-lookup__brand">
                    <?php if ($logoUrl !== '') : ?>
                    <span class="fc-lookup__brand-logo">
                        <img src="<?php echo $h($logoUrl); ?>" alt="" decoding="async">
                    </span>
                    <?php else : ?>
                    <span class="fc-lookup__brand-mark" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <?php endif; ?>
                    <div>
                        <h1 class="fc-lookup__title"><?php echo $h($sidebarTitle); ?></h1>
                        <?php if ($sidebarSubtitle !== '') : ?>
                        <p class="fc-lookup__subtitle"><?php echo $h($sidebarSubtitle); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <main class="fc-lookup__main">
                <?php require __DIR__ . '/empty.php'; ?>
            </main>
        </div>
    <?php else : ?>
        <aside class="fc-lookup__sidebar" id="fc-lookup-sidebar" data-fc-lookup-sidebar>
            <div class="fc-lookup__sidebar-brand">
                <div class="fc-lookup__brand">
                    <?php if ($logoUrl !== '') : ?>
                    <span class="fc-lookup__brand-logo">
                        <img src="<?php echo $h($logoUrl); ?>" alt="" decoding="async">
                    </span>
                    <?php else : ?>
                    <span class="fc-lookup__brand-mark" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <?php endif; ?>
                    <div class="fc-lookup__brand-copy">
                        <h1 class="fc-lookup__title"><?php echo $h($sidebarTitle); ?></h1>
                        <?php if ($sidebarSubtitle !== '') : ?>
                        <p class="fc-lookup__subtitle"><?php echo $h($sidebarSubtitle); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php require __DIR__ . '/filters.php'; ?>
        </aside>

        <div class="fc-lookup__content">
            <header class="fc-lookup__topbar">
                <?php require __DIR__ . '/toolbar.php'; ?>
            </header>

            <main class="fc-lookup__main">
                <?php if (($page['total'] ?? 0) < 1) : ?>
                    <?php require __DIR__ . '/empty.php'; ?>
                <?php else : ?>
                    <?php require __DIR__ . '/results.php'; ?>
                <?php endif; ?>
            </main>

            <?php require __DIR__ . '/footer.php'; ?>
        </div>

        <?php if (!empty($page['quick_view'])) : ?>
            <?php require __DIR__ . '/quick-view.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

    <div class="fc-lookup-toast" data-fc-lookup-toast hidden role="status" aria-live="polite"></div>
</div>
