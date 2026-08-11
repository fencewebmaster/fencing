<?php $files = glob(__DIR__ . '/modal/*', GLOB_ONLYDIR) ?: []; ?>

<?php 
foreach( $files as $file ):

	include $file.'/view.php';

endforeach; 

