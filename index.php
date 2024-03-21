<?php
include 'config/helpers.php';

$redirect_to = base_url('planner');

$query_vars = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';
/*
$queries = ['sid', 'site', 'qid'];

$not_found = TRUE;

if( $_GET ) {
	foreach ($queries as $q) {
		if( !in_array($q, $_GET) ) {
			$not_found = FALSE;
		}
	}	
}
*/
if( $query_vars ) {
	header("Location: ".$redirect_to.$query_vars);
	exit;
}

header("Location: ".$redirect_to.'?site='.$_SERVER['SERVER_NAME']);
