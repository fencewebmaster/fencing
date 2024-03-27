<?php
include 'config/helpers.php';

$redirect_to = base_url('planner');

$action = $_GET['action'];

if( $action == 'git-pull' ) {
	echo exec('git pull');
}

if( $action == 'minify-css' ) {
	
	foreach ( glob('assets/css/*[!{.min}].css') as $file) {
		minifiy_css( realpath($file) );
	}

}

header("Location: ".$redirect_to);
