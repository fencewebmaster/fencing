<?php
require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Helpers/UrlHelper.php';
require_once __DIR__ . '/app/src/Helpers/FileHelper.php';

$redirect_to = \Fc\Admin\Helpers\UrlHelper::baseUrl('planner');

$action = $_GET['action'];

if( $action == 'git-pull' ) {
	echo exec('git pull');
}

if( $action == 'minify-css' ) {

	foreach ( glob('public/assets/css/*[!{.min}].css') as $file) {
		\Fc\Admin\Helpers\FileHelper::minifyCss( realpath($file) );
	}

}

header("Location: ".$redirect_to);
