<?php
require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Helpers/UrlHelper.php';

$redirect_to = \Fc\Admin\Helpers\UrlHelper::baseUrl('planner');

$query_vars = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';

if( $query_vars ) {
	header("Location: ".$redirect_to.$query_vars);
	exit;
}

header("Location: ".$redirect_to.'?site='.$_SERVER['SERVER_NAME']);