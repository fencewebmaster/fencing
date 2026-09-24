<?php
use Fc\Admin\Core\FrontendApplication;
use Fc\Admin\Debug\DebugbarServer;
use Fc\Admin\Helpers\AssetHelper;
use Fc\Admin\Services\AppConfigService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\ThemeSettings;
?>
<meta charset="UTF-8">
<?php
$fcBranding = BrandingSettings::get();
// Pages that pass no $seo (the project plan) show a visitor's own quote: bare app name, never indexed.
$fcSeo = $seo ?? ['title' => $fcBranding['appName'], 'description' => '', 'canonical' => '', 'robots' => 'noindex, nofollow', 'meta' => [], 'json_ld' => ''];
?>
<title><?php echo e($fcSeo['title']); ?></title>
<?php if ($fcSeo['description'] !== '') : ?>
<meta name="description" content="<?php echo e($fcSeo['description']); ?>">
<?php endif; ?>
<?php if ($fcSeo['canonical'] !== '') : ?>
<link rel="canonical" href="<?php echo e($fcSeo['canonical']); ?>">
<?php endif; ?>
<?php if ($fcSeo['robots'] !== '') : ?>
<meta name="robots" content="<?php echo e($fcSeo['robots']); ?>">
<?php endif; ?>
<?php /* Social cards and webmaster verification (Settings -> SEO); attr is 'name' or 'property'. */ ?>
<?php foreach ($fcSeo['meta'] as $fcSeoMeta) : ?>
<meta <?php echo e($fcSeoMeta['attr']); ?>="<?php echo e($fcSeoMeta['key']); ?>" content="<?php echo e($fcSeoMeta['content']); ?>">
<?php endforeach; ?>
<?php if ($fcSeo['json_ld'] !== '') : ?>
<?php /* Encoded with JSON_HEX_TAG, so no value can close this script element early. */ ?>
<script type="application/ld+json"><?php echo $fcSeo['json_ld']; ?></script>
<?php endif; ?>

<?php
if( !AppConfigService::all()->app->debug ):
	error_reporting(0);
endif;
?>

<?php $info = $_SESSION; ?>
<?php $fc_route = FrontendApplication::currentRoute(); ?>

<?php /* jQuery loads here, not deferred, so it's ready before GTM tags run */ ?>
<script src="<?php echo asset('public/assets/js/vendor/jquery-3.7.1.min.js'); ?>"></script>

<?php if( $gtmID = @$site_info['gtmID'] ): ?>
<?php /* Google Tag Manager */ ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo @$gtmID; ?>');</script>
<?php /* End Google Tag Manager */ ?>
<?php endif; ?>

<?php
// Dynamic favicon: prefer branding favicon when set, fallback to bundled fav.ico
$fcFavicon = BrandingSettings::faviconUrl('');

if ($fcFavicon !== '') {
    $faviconHref = $fcFavicon;
    if (!preg_match('/^https?:\\/\\//i', $faviconHref) && strpos($faviconHref, '//') !== 0 && !preg_match('/^data:/i', $faviconHref)) {
        // Stored favicon paths are app-relative; resolve them to a full URL
        $faviconHref = url(ltrim($faviconHref, '/'));
    }
    echo '<link rel="icon" href="' . e($faviconHref) . '">';
} else {
    echo '<link rel="icon" type="image/x-icon" href="' . e(asset('public/assets/img/fav.ico')) . '">';
}
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

<?php include view_path('frontend.partials.webfonts'); ?>

<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/css/frontend/style.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/css/frontend/style-v2.css'); ?>">
<?php
echo ThemeSettings::cssBlock();
?>
<?php /* Font Awesome trimmed to the icons these pages use (build/minify/icons.php). The full class list follows
     deferred, so an icon added since the last build still shows, from the full font, just after first paint. */ ?>
<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/fonts/fa/css/subset.min.css'); ?>">
<?php AssetHelper::deferStylesheet(asset('public/assets/fonts/fa/css/icons.min.css')); ?>

<link href="<?php echo asset('public/assets/css/vendor/bootstrap/bootstrap.min.css'); ?>" rel="stylesheet">

<?php
// Debugbar (Settings -> Console -> Debug Mode). One request-cached flag decides CSS here
// and the JS + JSON island in footer.php - shared scope, same as $info above. Nothing is
// emitted when off, so the disabled page is byte-identical to today.
$fcDebugbarOn = DebugbarServer::showDebugbar();
if ($fcDebugbarOn) :
?>
<link rel="stylesheet" type="text/css" href="<?php echo asset('public/assets/css/frontend/debugbar.css'); ?>">
<?php endif; ?>

<?php if ($fc_route === 'project-plan') : ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<?php endif; ?>

<link rel="stylesheet" href="<?php echo asset('public/assets/css/vendor/slick/slick.css'); ?>"/>
<?php AssetHelper::deferStylesheet(asset('public/assets/css/vendor/slick/slick-theme.css')); ?>
<link rel="stylesheet" href="<?php echo asset('public/assets/css/vendor/select2/select2.min.css'); ?>"/>
<?php AssetHelper::deferStylesheet(asset('public/assets/css/vendor/select2/select2-bootstrap-5-theme.min.css')); ?>

<?php if( $gtagID = @$site_info['gtagID'] ): ?>
<?php /* Google tag (gtag.js) */ ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo @$gtagID; ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', '<?php echo @$gtagID; ?>');
</script>
<?php endif; ?>

<?php
// Custom header code from Settings -> Integration. Emitted raw and unescaped on purpose:
// the field exists to inject third-party markup (verification tags, pixels), so escaping
// it would defeat it. Only admins holding the settings permission can write the value.
$fcHeaderCode = trim((string) (AppConfigService::all()->custom_code->header ?? ''));
if ($fcHeaderCode !== '') :
?>
<?php /* Custom header code */ ?>
<?php echo $fcHeaderCode; ?>
<?php endif; ?>
