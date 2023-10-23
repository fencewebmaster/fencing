<?php $files = glob('temp/modal/*', GLOB_ONLYDIR); ?>

<?php 
foreach( $files as $file ):

	include $file.'/view.php';

endforeach; 

