<?php
/**
 * Per-fence-type calculator scripts.
 *
 * One <script> per JS module in public/assets/js/fences/, so adding a fence type is a
 * matter of dropping its file in there. The files themselves stay under public/ (they
 * are browser assets); only this markup lives with the other views.
 */
?>
<?php $files = glob(dirname(__DIR__, 4) . '/public/assets/js/fences/*.js') ?: []; ?>
<?php foreach ( $files as $file ) : ?>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/fences/' . basename($file)); ?>"></script>
<?php endforeach; ?>
