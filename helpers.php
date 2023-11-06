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

function post_product_skus($cart_items = array()) {

    $skus = get_product_skus($cart_items);

    foreach ($skus as $sku) {
        $post_query[]['sku'] = $sku;
    }

    // START - GET PRODUCTS FROM THE STORE
    $curl = curl_init();

    // An array of cURL options
    $options = array(
      CURLOPT_URL => 'https://fencesperth.com?fc_action=get_products',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => json_encode($post_query),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    );

    // Check if running on localhost
    if ($_SERVER['HTTP_HOST'] === 'localhost') {
      // Disable SSL certificate verification for localhost
      // Error happens when request is coming from a non https source
      $options[CURLOPT_SSL_VERIFYPEER] = false;
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);

    curl_close($curl);

    $items = json_decode($response);
    $cart  = array();

    $count = count($items);
    $rand  = rand(2, $count);

    $custom_fence_products = $_SESSION['custom_fence_products'];


    $i=1;
    foreach ($items as $item) {

        $key = array_search($item->sku, array_column($custom_fence_products, 'sku'));

        $cart['items'][] = [
            'name'  => $item->name,
            'sku'   => $item->sku,
            'stock' => $i == 1 || $i == $rand ? 'low' : $item->stock,
            'qty'   => $custom_fence_products[$key]['qty'],
        ];
      $i++;
    }

    $_SESSION['fc_cart'] = $cart;

}