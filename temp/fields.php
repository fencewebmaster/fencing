<?php $files = glob('temp/fields/*.php'); ?>

<?php foreach( $files as $file ): ?>
<script type="text/text" data-type="<?php echo basename($file, '.php'); ?>">

	<?php include $file; ?>

</script>
<?php endforeach; ?>
