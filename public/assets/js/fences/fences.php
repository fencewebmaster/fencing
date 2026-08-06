<?php require_once dirname(__DIR__, 4) . '/app/src/Helpers/AssetHelper.php'; ?>
<?php $files = glob('public/assets/js/fences/*.js'); ?>
<?php foreach( $files as $file ): ?>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl($file); ?>"></script>
<?php endforeach; ?>
