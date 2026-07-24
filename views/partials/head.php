<meta charset="UTF-8">
<?php
if (!function_exists('fc_branding_get')) {
    require_once dirname(__DIR__, 2) . '/config/branding.php';
}
$fcBranding = fc_branding_get();
?>
<title><?php echo htmlspecialchars($fcBranding['appName'], ENT_QUOTES, 'UTF-8'); ?></title>

<?php 
if( !config()->app->debug ):
	error_reporting(0);	
endif;
?>

<?php $info = $_SESSION; ?>
<?php $fc_page_script = function_exists('fc_page_script') ? fc_page_script() : basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>

<?php if( $gtmID = @$site_info['gtmID'] ): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo @$gtmID; ?>');</script>
<!-- End Google Tag Manager -->
<?php endif; ?>

<link rel="icon" type="image/x-icon" href="<?php echo load_file('assets/img/fav.ico'); ?>">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

<link rel="stylesheet" type="text/css" href="<?php echo load_file('assets/css/fonts.css'); ?>">

<link rel="stylesheet" type="text/css" href="<?php echo load_file('assets/css/style.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo load_file('assets/css/style-v2.css'); ?>">
<?php
if (!function_exists('fc_theme_css_block')) {
    require_once dirname(__DIR__, 2) . '/config/theme.php';
}
echo fc_theme_css_block();
?>
<link rel="stylesheet" type="text/css" href="<?php echo load_file('assets/fonts/fa/css/all.min.css'); ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<?php if ($fc_page_script === 'project-plan.php') : ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" crossorigin="anonymous"/>
<?php fc_defer_stylesheet('https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', true); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" crossorigin="anonymous"/>
<?php fc_defer_stylesheet('https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css', true); ?>

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

