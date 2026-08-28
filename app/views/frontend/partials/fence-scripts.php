<?php
/**
 * Per-fence-type calculator scripts.
 *
 * One <script> per JS module in public/assets/js/frontend/fences/, so adding a fence type is a
 * matter of dropping its file in there. The files themselves stay under public/ (they
 * are browser assets); only this markup lives with the other views.
 */
?>
<?php $files = glob(dirname(__DIR__, 4) . '/public/assets/js/frontend/fences/*.js') ?: []; ?>
<?php foreach ( $files as $file ) : ?>
<script defer src="<?php echo asset('public/assets/js/frontend/fences/' . basename($file)); ?>"></script>
<?php endforeach; ?>
<?php
// Per-fence calculation modules (fences/calc/*.js) attach onto the hook-bag objects above
// (calc/slat_fence.js extends SlatFence), so they must load AFTER the whole main glob —
// appending a second pass keeps them behind z_fence.js too.
// calc.js (the shared engine) also lives in fences/calc/ but is excluded here: footer.php
// loads it early in the core chain, and a second tag would throw on its const redeclarations.
$calcFiles = array_filter(glob(dirname(__DIR__, 4) . '/public/assets/js/frontend/fences/calc/*.js') ?: [], static fn (string $f): bool => basename($f) !== 'calc.js');
?>
<?php foreach ( $calcFiles as $file ) : ?>
<script defer src="<?php echo asset('public/assets/js/frontend/fences/calc/' . basename($file)); ?>"></script>
<?php endforeach; ?>
