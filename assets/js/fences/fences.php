<?php $files = glob('assets/js/fences/*.js'); ?>
<?php foreach( $files as $file ): ?>
<script defer src="<?php echo load_file($file); ?>"></script>
<?php endforeach; ?>
