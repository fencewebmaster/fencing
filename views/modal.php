<?php $files = glob('views/modal/*', GLOB_ONLYDIR); ?>

<?php 
foreach( $files as $file ):

	include $file.'/view.php';

endforeach; 

