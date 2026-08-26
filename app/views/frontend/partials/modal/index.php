<?php
/**
 * Modal panel loader: includes each subdirectory's view.php (control/, submit/)
 * into the including page's scope. The directory glob is the registry — a new
 * modal panel is a new subdirectory with a view.php, no other wiring.
 */
$files = glob(__DIR__ . '/*', GLOB_ONLYDIR) ?: [];
?>

<?php
foreach( $files as $file ):

	include $file.'/view.php';

endforeach;
