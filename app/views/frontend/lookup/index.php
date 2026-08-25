<?php
/**
 * Product Lookup page (/lookup).
 *
 * Rendered by Controllers\Frontend\LookupController.
 *
 * @var array    $page              ProductLookupService::buildPage() result.
 * @var array    $catalog           Catalog display settings.
 * @var string   $fcLookupPageTitle
 * @var string   $fcLookupAppBase   Web path the page is mounted at.
 * @var string   $fcLookupLogoUrl
 * @var callable $h                 HTML escaper.
 * @var callable $asset             Cache-busted asset URL builder.
 */

declare(strict_types=1);

$adminCssBase = 'public/assets/css/admin/';
?><!DOCTYPE html>
<html lang="en" data-fc-admin-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $h($fcLookupPageTitle); ?></title>
    <?php echo \Fc\Admin\Services\ThemeSettings::cssBlock(); ?>
    <link rel="stylesheet" href="<?php echo $h($asset('public/assets/css/fonts.css')); ?>">
    <link href="<?php echo $h($asset('public/assets/css/vendor/bootstrap/bootstrap.min.css')); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $h($asset('public/assets/css/vendor/fontawesome/css/all.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo $h($asset($adminCssBase . 'buttons.css')); ?>">
    <link rel="stylesheet" href="<?php echo $h($asset($adminCssBase . 'theme.css')); ?>">
    <link rel="stylesheet" href="<?php echo $h($asset('public/assets/css/frontend/lookup.css')); ?>">
    <style>
        .fc-lookup-page {
            --fc-lookup-cols-desktop: <?php echo (int) ($catalog['columnsDesktop'] ?? 4); ?>;
            --fc-lookup-cols-laptop: <?php echo (int) ($catalog['columnsLaptop'] ?? 3); ?>;
            --fc-lookup-cols-tablet: <?php echo (int) ($catalog['columnsTablet'] ?? 2); ?>;
            --fc-lookup-cols-mobile: <?php echo (int) ($catalog['columnsMobile'] ?? 1); ?>;
        }
    </style>
</head>
<body class="fc-lookup-page fc-admin-page">
<?php require __DIR__ . '/page.php'; ?>
<script src="<?php echo $h($asset('public/assets/js/frontend/lookup.js')); ?>" defer></script>
</body>
</html>
