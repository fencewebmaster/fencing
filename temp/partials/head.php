<title>Fencing Calculator</title>
<link rel="icon" type="image/x-icon" href="<?php echo base_url(); ?>img/fav.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>style.css?v=<?php echo date('YmdHis'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>style-v2.css?v=<?php echo date('YmdHis'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>fonts/fa/css/all.min.css">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<?php if( $gtagID = @$site_info['gtagID'] ): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11027308949"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?php echo @$gtagID; ?>');
</script>
<?php endif; ?>