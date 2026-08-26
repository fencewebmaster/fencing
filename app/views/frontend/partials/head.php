<?php
use Fc\Admin\Core\FrontendApplication;
use Fc\Admin\Helpers\AssetHelper;
use Fc\Admin\Services\AppConfigService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\ThemeSettings;
?>
<meta charset="UTF-8">
<?php
$fcBranding = BrandingSettings::get();
?>
<title><?php echo e($fcBranding['appName']); ?></title>

<?php
if( !AppConfigService::all()->app->debug ):
	error_reporting(0);
endif;
?>

<?php $info = $_SESSION; ?>
<?php $fc_route = FrontendApplication::currentRoute(); ?>

<!-- jQuery loads here, not deferred, so it's ready before GTM tags run -->
<script src="<?php echo asset('public/assets/js/vendor/jquery-3.3.1.min.js'); ?>"></script>

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
$fcFavicon = BrandingSettings::faviconUrl('');

if ($fcFavicon !== '') {
    $faviconHref = $fcFavicon;
    if (!preg_match('/^https?:\\/\\//i', $faviconHref) && strpos($faviconHref, '//') !== 0 && !preg_match('/^data:/i', $faviconHref)) {
        // make absolute URL relative to current host/path
        $faviconHref = url(ltrim($faviconHref, '/'));
    }
    echo '<link rel="icon" href="' . e($faviconHref) . '">';
} else {
    echo '<link rel="icon" type="image/x-icon" href="' . e(asset('public/assets/img/fav.ico')) . '">';
}
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/css/fonts.css'); ?>">

<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/css/frontend/style.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/css/frontend/style-v2.css'); ?>">
<?php
echo ThemeSettings::cssBlock();
?>
<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/fonts/fa/css/all.min.css'); ?>">

<link href="<?php echo asset('public/assets/css/vendor/bootstrap/bootstrap.min.css'); ?>" rel="stylesheet">

<?php if ($fc_route === 'project-plan') : ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<?php endif; ?>

<link rel="stylesheet" href="<?php echo asset('public/assets/css/vendor/slick/slick.css'); ?>"/>
<?php AssetHelper::deferStylesheet(asset('public/assets/css/vendor/slick/slick-theme.css')); ?>
<link rel="stylesheet" href="<?php echo asset('public/assets/css/vendor/select2/select2.min.css'); ?>"/>
<?php AssetHelper::deferStylesheet(asset('public/assets/css/vendor/select2/select2-bootstrap-5-theme.min.css')); ?>

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

