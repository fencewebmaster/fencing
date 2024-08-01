<title>Fencing Calculator</title>

<?php 
if( !config()->app->debug ):
	error_reporting(0);	
endif;
?>

<?php $info = $_SESSION; ?>

<?php if( $info['site']['id'] == 2 ): ?>
<!-- fencesbrisbane.au | Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-MNZ475L');</script>
<!-- fencesbrisbane.au | End Google Tag Manager -->
<?php endif; ?>

<link rel="icon" type="image/x-icon" href="<?php echo load_file('assets/img/fav.ico'); ?>">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" type="text/css" href="<?php echo load_file('assets/css/style.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo load_file('assets/css/style-v2.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo load_file('assets/fonts/fa/css/all.min.css'); ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

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


