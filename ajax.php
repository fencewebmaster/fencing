<?php
require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Core/SessionBootstrap.php';
\Fc\Admin\Core\SessionBootstrap::start();

include 'writable/settings.php';
require_once __DIR__ . '/app/src/Services/DatabaseConfigService.php';
require_once __DIR__ . '/app/src/Services/Database.php';
require_once __DIR__ . '/app/src/Helpers/FileHelper.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if( $action == 'get-size' ) {
    $name  = $_POST['name'];
    $key   = $_POST['key'];
    $value = $_POST['value'];

    $rows = \Fc\Admin\Helpers\FileHelper::loadCsv('writable/sizes/'.$name.'.csv');
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