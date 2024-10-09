<?php
if (!session_id()) {
    session_start();
}

include 'data/settings.php';
include 'config/helpers.php';
include 'config/database.php'; 

$action = isset($_POST['action']) ? $_POST['action'] : '';

if( $action == 'get-size' ) {
    $name  = $_POST['name'];
    $key   = $_POST['key'];
    $value = $_POST['value'];

    $rows = load_csv('data/sizes/'.$name.'.csv');
    $data = array();
    
    foreach ($rows as $row) {
        if( $row[$key] <= $value ) {
            $data = $row;
            continue;
        }
    }

    echo json_encode($data);
    exit;
}   