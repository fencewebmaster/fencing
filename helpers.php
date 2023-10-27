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

function load_csv($file = '') {

    if( ! file_exists($file) ) {
        return FALSE;
    }

    $handle = fopen($file, "r");

    $i = $h = 0;
    while (($data = fgetcsv($handle)) !== FALSE) {
        if( $i == 0) {
            $header = $data;                               
        } else {
            $e=0;
            foreach ($data as $d) {                     
                if( @$header[$e] ) {  
                    $col = str_replace([' ', '-'], ['_', ''], strtolower( rtrim( $header[$e] ) ) );
                    $order_info[$col] = $d;
                    $e++;
                }
            }

            $rows[$i-1] = $order_info;    
        }

        $i++;
    } 

    return $rows;
}


function get_product_skus($data = array()) {

	$products = $skus = array();

 	$the_products = load_csv('data/products.csv');

 	$items = $data['items'];

 	$column = 'slug';
 	$color  = $data['color'];

	foreach ($items as $item) {

		$key = array_search($item['slug'], array_column($the_products, $column));
		$products[] = [
			'sku' => $the_products[$key][$color],
			'qty' => $item['qty']
		]; 

		$skus[] = $the_products[$key][$color];
	}

	$_SESSION['custom_fence_products'] = $products;

	return $skus;
}

