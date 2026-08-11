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

$adminCssBase = 'public/assets/css/';
?><!DOCTYPE html>
<html lang="en" data-fc-admin-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $h($fcLookupPageTitle); ?></title>
    <?php echo \Fc\Admin\Services\ThemeSettings::cssBlock(); ?>
    <link rel="stylesheet" href="<?php echo $h($asset('public/assets/css/fonts.css')); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?php echo $h($asset($adminCssBase . 'fc-admin-buttons.css')); ?>">
    <link rel="stylesheet" href="<?php echo $h($asset($adminCssBase . 'fc-admin-theme.css')); ?>">
    <link rel="stylesheet" href="<?php echo $h($asset('public/assets/css/lookup.css')); ?>">
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
<script src="<?php echo $h($asset('public/assets/js/lookup.js')); ?>" defer></script>
</body>
</html>
