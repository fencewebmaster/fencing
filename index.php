<?php
include 'config/helpers.php';

$redirect_to = base_url('planner');

$query_vars = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';

if( $query_vars ) {
	header("Location: ".$redirect_to.$query_vars);
	exit;
}

header("Location: ".$redirect_to.'?site='.$_SERVER['SERVER_NAME']);