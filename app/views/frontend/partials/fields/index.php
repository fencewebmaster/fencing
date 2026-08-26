<?php
/**
 * Field-template registry loader: emits every sibling *.php in this directory
 * as a <script type="text/text" data-type="{basename}"> block the planner JS
 * looks up by type. The glob is the registry — dropping a file in here adds a
 * template, no other wiring. This loader excludes itself from the glob.
 */
$files = array_filter(glob(__DIR__ . '/*.php') ?: [], static fn (string $f): bool => basename($f) !== 'index.php');
?>

<?php foreach( $files as $file ): ?>
<script type="text/text" data-type="<?php echo basename($file, '.php'); ?>">

	<?php include $file; ?>

</script>
<?php endforeach; ?>
