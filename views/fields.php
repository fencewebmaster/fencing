<?php $files = glob('views/fields/*.php'); ?>

<?php foreach( $files as $file ): ?>
<script type="text/text" data-type="<?php echo basename($file, '.php'); ?>" async>

	<?php include $file; ?>

</script>
<?php endforeach; ?>
