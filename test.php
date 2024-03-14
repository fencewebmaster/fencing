<?php
$domain = $_SERVER['SERVER_NAME'];

$redirect_to = 'https://fencesperth.com/fc/';

$query_vars = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';

if( $query_vars ) {
    header("Location: ".$redirect_to.$query_vars); 
    exit;
}

header("Location: ".$redirect_to."?site=".$domain);   
