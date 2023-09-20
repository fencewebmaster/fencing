<?php
function base_url($param ='') {
	return sprintf(
		"%s://%s%s/%s",
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
		$_SERVER['SERVER_NAME'],
		dirname($_SERVER["REQUEST_URI"].'?'),
		$param
	);
}

function get_items($key, $items, $list = false) {  

    if( !is_array($items) ) {
      return call_user_func_array($key, [$items]);
    }

    if( $list ) {
      $data = '';
      foreach( $items as $row ) {
        $data .= '<li>'.call_user_func_array($key, [$row]).'</li>';
      }      
      return $data;
    }

    foreach( $items as $row ) {
      $data[] = call_user_func_array($key, [$row]);
    }

    return implode(', ', $data);    

}

function dd($data ='') {
    echo '<pre>';
    print_r( $data );
    exit;
}

function fc_deliver_options() {
    $data = [
      [
        'value'   => 'shipping_1',
        'label'   => 'Warehouse Pickup',
        'price'   => 0,
        'default' => TRUE,
      ],
      [
        'value'   => 'shipping_2',
        'label'   => 'Deliver to Site (Metro $89)',
        'price'   => 89,
        'default' => FALSE,
        ],
      [
        'value'   => 'shipping_3',
        'label'   => 'Deliver to Site (Rural - $TBA)',
        'price'   => 0,
        'default' => FALSE,
      ]
    ];

    return $data;
}