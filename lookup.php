<?php
/**
 * FC Product Lookup — public, server-rendered WooCommerce product search.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Services/ThemeSettings.php';
require_once __DIR__ . '/app/src/Services/BrandingSettings.php';
require_once __DIR__ . '/app/src/Services/CatalogSettings.php';
require_once __DIR__ . '/app/src/Services/ProductLookupService.php';

use Fc\Admin\Helpers\StringHelper;
use Fc\Admin\Services\BrandingSettings;
use Fc\Admin\Services\CatalogSettings;
use Fc\Admin\Services\ProductLookupService;

$page = ProductLookupService::buildPage($_GET);
$h = static fn (string $value): string => StringHelper::escapeHtml(StringHelper::decodeHtmlEntities($value));
$catalog = is_array($page['catalog'] ?? null) ? $page['catalog'] : CatalogSettings::get();
$fcLookupPageTitle = trim((string) ($catalog['sidebarTitle'] ?? 'Product Lookup'));
if ($fcLookupPageTitle === '') {
    $fcLookupPageTitle = 'Product Lookup';
}

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/lookup.php'));
$fcLookupAppBase = dirname($scriptName);
if ($fcLookupAppBase === '/' || $fcLookupAppBase === '.' || $fcLookupAppBase === '\\') {
    $fcLookupAppBase = '';
}
$fcLookupAppBase = rtrim($fcLookupAppBase, '/');
$fcLookupLogoUrl = BrandingSettings::logoUrl($fcLookupAppBase);

$asset = static function (string $rel) use ($fcLookupAppBase): string {
    $rel = ltrim($rel, '/');
    $path = __DIR__ . '/' . $rel;
    $url = ($fcLookupAppBase !== '' ? $fcLookupAppBase . '/' : '/') . $rel;
    if ($fcLookupAppBase === '') {
        $url = '/' . $rel;
    }
    if (is_file($path)) {
        return $url . '?v=' . filemtime($path);
    }

    return $url;
};

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
<?php require __DIR__ . '/views/lookup/page.php'; ?>
<script src="<?php echo $h($asset('public/assets/js/lookup.js')); ?>" defer></script>
</body>
</html>
