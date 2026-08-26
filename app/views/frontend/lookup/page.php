<?php
/**
 * Read-only template: LookupPageModel::shellData() supplies layout and the
 * sidebar heading; $catalog is the same normalized array build() computes.
 *
 * @var array<string, mixed> $page
 * @var array<string, mixed> $shell
 * @var callable $h
 */

$layout          = $shell['layout'];
$logoUrl         = $fcLookupLogoUrl;
$sidebarTitle    = $shell['sidebar_title'];
$sidebarSubtitle = $shell['sidebar_subtitle'];
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
                <?php require view_path('frontend.lookup.empty'); ?>
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
            <?php require view_path('frontend.lookup.filters'); ?>
        </aside>

        <div class="fc-lookup__content">
            <header class="fc-lookup__topbar">
                <?php require view_path('frontend.lookup.toolbar'); ?>
            </header>

            <main class="fc-lookup__main">
                <?php if (($page['total'] ?? 0) < 1) : ?>
                    <?php require view_path('frontend.lookup.empty'); ?>
                <?php else : ?>
                    <?php require view_path('frontend.lookup.results'); ?>
                <?php endif; ?>
            </main>

            <?php require view_path('frontend.lookup.footer'); ?>
        </div>

        <?php if (!empty($page['quick_view'])) : ?>
            <?php require view_path('frontend.lookup.quick-view'); ?>
        <?php endif; ?>
    <?php endif; ?>

    <div class="fc-lookup-toast" data-fc-lookup-toast hidden role="status" aria-live="polite"></div>
</div>
