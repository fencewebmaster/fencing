<meta charset="UTF-8">
<?php
require_once dirname(__DIR__, 4) . '/app/src/Services/BrandingSettings.php';
require_once dirname(__DIR__, 4) . '/app/src/Services/AppConfigService.php';
require_once dirname(__DIR__, 4) . '/app/src/Helpers/UrlHelper.php';
require_once dirname(__DIR__, 4) . '/app/src/Helpers/AssetHelper.php';
$fcBranding = \Fc\Admin\Services\BrandingSettings::get();
?>
<title><?php echo htmlspecialchars($fcBranding['appName'], ENT_QUOTES, 'UTF-8'); ?></title>

<?php
if( !\Fc\Admin\Services\AppConfigService::all()->app->debug ):
	error_reporting(0);
endif;
?>

<?php $info = $_SESSION; ?>
<?php $fc_route = \Fc\Admin\Core\FrontendApplication::currentRoute(); ?>

<?php if( $gtmID = @$site_info['gtmID'] ): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo @$gtmID; ?>');</script>
<!-- End Google Tag Manager -->
<?php endif; ?>

<?php
// Dynamic favicon: prefer branding favicon when set, fallback to bundled fav.ico
$fcFavicon = \Fc\Admin\Services\BrandingSettings::faviconUrl('');

if ($fcFavicon !== '') {
    $faviconHref = $fcFavicon;
    if (!preg_match('/^https?:\\/\\//i', $faviconHref) && strpos($faviconHref, '//') !== 0 && !preg_match('/^data:/i', $faviconHref)) {
        // make absolute URL relative to current host/path
        $faviconHref = \Fc\Admin\Helpers\UrlHelper::baseUrl(ltrim($faviconHref, '/'));
    }
    echo '<link rel="icon" href="' . htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') . '">';
} else {
    echo '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars(\Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/img/fav.ico'), ENT_QUOTES, 'UTF-8') . '">';
}
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

<link rel="stylesheet" type="text/css" href="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/css/fonts.css'); ?>">

<link rel="stylesheet" type="text/css" href="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/css/frontend/style.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/css/frontend/style-v2.css'); ?>">
<?php
require_once dirname(__DIR__, 4) . '/app/src/Services/ThemeSettings.php';
echo \Fc\Admin\Services\ThemeSettings::cssBlock();
?>
<link rel="stylesheet" type="text/css" href="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/fonts/fa/css/all.min.css'); ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<?php if ($fc_route === 'project-plan') : ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" crossorigin="anonymous"/>
<?php \Fc\Admin\Helpers\AssetHelper::deferStylesheet('https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', true); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" crossorigin="anonymous"/>
<?php \Fc\Admin\Helpers\AssetHelper::deferStylesheet('https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css', true); ?>

<?php if( $gtagID = @$site_info['gtagID'] ): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo @$gtagID; ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', '<?php echo @$gtagID; ?>');
</script>
<?php endif; ?>

