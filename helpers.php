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

//----------------------------------------------------------------

function toURL($url){
    if( isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    } else {
        $protocol = 'https';
    }
    return $protocol . "://" . $url;
}

//----------------------------------------------------------------

function get_uid($l=10) {
    return strtoupper(substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, $l));
}

//----------------------------------------------------------------

function sites($key = '', $value = 'id', $search = false) {

    $data = [
        [
            'id'       => 999999,
            'domain'   => "localhost",
            'url'      => toURL('fencingwarehouse.au'),
            'supplier' => "JG",
            'logo'     => "https://fencesperth.com/wp-content/uploads/2022/02/FENCING-SUPPLIERS-Australia-5-e1702790075927.png",
            'name'     => "Localhost Fencing Outlet",
            'restrict' => [
                'left_raked',
                'right_raked'
            ]
        ],
        [
            'id'       => 1,
            'domain'   => "fencesperth.com",
            'url'      => toURL('fencesperth.com'),
            'supplier' => "GO",
            'logo'     => "https://fencesperth.com/wp-content/uploads/2022/02/FENCING-SUPPLIERS-Australia-5-e1702790075927.png",
            'name'     => "Perth's Fencing Outlet"
        ],
        [
            'id'       => 2,
            'domain'   => "fencesbrisbane.au",
            'url'      => toURL('fencesbrisbane.au'),
            'supplier' => "JG",
            'logo'     => "https://fencesbrisbane.au/wp-content/uploads/2022/02/FENCING-SUPPLIERS-AUSTRALIA-130.png",
            'name'     => "Brisbane's Fencing Outlet"
        ],
        [
            'id'       => 3,
            'domain'   => "fencingwarehouse.au",
            'url'      => toURL('fencingwarehouse.au'),
            'supplier' => "JG",
            'logo'     => "https://new.fencesbrisbane.au/wp-content/uploads/2023/10/FencingWarehouse-W300px.png",
            'name'     => "Fencing Warehouse"
        ],
        [
            'id'       => 4,
            'domain'   => "fencinggoldcoast.au",
            'url'      => toURL('fencinggoldcoast.au'),
            'supplier' => "GO",
            'logo'     => "https://fencinggoldcoast.au/wp-content/uploads/2022/02/FENCING-SUPPLIERS-AUSTRALIA-130.png",
            'name'     => "Gold Coast's Fencing Outlet"
        ],
        [
            'id'       => 5,
            'domain'   => "fencesadelaide.au",
            'url'      => toURL('fencesadelaide.au'),
            'supplier' => "GO",
            'logo'     => "https://fencesadelaide.au/wp-content/uploads/2022/02/FENCING-SUPPLIERS-AUSTRALIA-130.png",
            'name'     => "Adelaide's Fencing Outlet"
        ],
        [
            'id'       => 6,
            'domain'   => "fencessydney.au",
            'url'      => toURL('fencessydney.au'),
            'supplier' => "GO",
            'logo'     => "https://fencessydney.au/wp-content/uploads/2022/02/FENCING-SUPPLIERS-AUSTRALIA-130.png",
            'name'     => "Sydney's Fencing Outlet"
        ],
        [
            'id'       => 7,
            'domain'   => "fencesmelbourne.au",
            'url'      => toURL('fencesmelbourne.au'),
            'supplier' => "GO",
            'logo'     => "https://fencesmelbourne.au/wp-content/uploads/2022/02/FENCING-SUPPLIERS-V2.png",
            'name'     => "Melbourne's Fencing Outlet"
        ],
        [
            'id'       => 8,
            'domain'   => "fencesnewcastle.au",
            'url'      => toURL('fencesnewcastle.au'),
            'supplier' => "GO",
            'logo'     => "",
            'name'     => ""
        ]
    ];

    if( $search ) {
        $key = array_search($key, array_column($data, $value));

        if( !empty($key) || $key === 0 ) {
            return $data[$key];
        }

        return FALSE;
    }

    return $data;

}

//----------------------------------------------------------------

function selected_fences($fences, $column = 'slug') {
    
    $info = $_SESSION['fc_data'];

    $fence_data = array();

    foreach ( convert_inputs($info['fences']) as $fence) {
        $slug = $fence['form'][0]['fence'];
        $fence_data[$slug] = $fences[$slug][$column];
    }   

    return $fence_data; 
}

//----------------------------------------------------------------

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

//----------------------------------------------------------------

function array_to_json($val='') {

  if( is_array($val) ) {
      return json_encode($val);    
  }

  return $val;
}

//----------------------------------------------------------------


function convert_inputs($val='') {

    if( is_array($val) ) {
        return json_encode($val);
    }

    if ( $data = json_decode($val, true) ) {
        return $data;
    }

    if (preg_match("/\d{4}\-\d{2}-\d{2}/", $val) || preg_match("/\d{2}\-\d{2}-\d{4}/", $val) ) {
        
        if( strlen($val) > 10 ) {
            return $val;            
        } else {
            return date_formatted_b($val);
        }

    }

    return $val;
}

//----------------------------------------------------------------

function dd($data ='') {
    echo '<pre>';
    print_r( $data );
    exit;
}

//----------------------------------------------------------------

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

//----------------------------------------------------------------

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

//----------------------------------------------------------------

function get_product_skus($data = array()) {

	$products = $skus = array();

 	$the_products = load_csv('data/products.csv');


    foreach ($data as $d) {

     	$items = $d['items'];

     	$column = 'slug';
     	$color  = $d['color'];

        $supplier = $_SESSION['site']['supplier'];


    	foreach ($items as $item) {

            $filtered_product = array_filter($the_products, function($val) use($item, $supplier){
                return ( $val['slug'] == $item['slug'] && $val['supplier'] == $supplier );
            });

            if( $filtered_product ) {
                $key = array_keys($filtered_product)[0];

                $sku = $the_products[$key][$color];

                if( $key !== false && strtolower($sku) != 'off' ){
                  $products[] = [
                    'sku'   => $sku,
                    'qty'   => $item['qty'],
                    'slug'  => $item['slug'],
                    'fence' => $d['slug'],
                    'color' => $d['color'],
                  ]; 
                }
                

                $skus[] = $the_products[$key][$color];
            }

    	}

    }

	$_SESSION['custom_fence_products'] = $products;

	return $products;
}

//----------------------------------------------------------------

function post_product_skus($cart_items = array()) {

    $supplier = strtoupper($_SESSION['site']['supplier']);

	$items = $cart = array();

    $skus = get_product_skus($cart_items);

    foreach ($skus as $sku) {
        $post_query[] = $sku;
    }

 	$the_products = load_csv('data/wc-products-'.$supplier.'.csv');

    foreach ($post_query as $query) {

		$key = array_search($query['sku'], array_column($the_products, 'sku'));

        $image = '';
        if( isset($the_products[$key]['images']) ) {
            $image = explode(', ', $the_products[$key]['images'])[0];
        }

        $items[]  = [
            'sku'   => $query['sku'],
            'name'  => $the_products[$key]['name'],
            'slug'  => $query['slug'],
            'color' => $query['color'],
            'fence' => $query['fence'],
            'image' => $image,
        ];

    }

    // START - GET PRODUCTS FROM THE STORE
	/*    
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

    curl_close($curl);*/


    $count = count($items);
    $rand  = rand(2, $count);

    $custom_fence_products = $_SESSION['custom_fence_products'];

    $i=1;
    foreach ($items as $item) {

        $key = array_search($item['sku'], array_column($custom_fence_products, 'sku'));

        if( $custom_fence_products[$key]['qty'] ) {

            $cart['items'][] = [
                'name'  => $item['name'],
                'image' => $item['image'],
                'sku'   => $item['sku'],
                'slug'  => $item['slug'],
                'color' => $item['color'],
                'fence' => $item['fence'],
                'stock' => $i == 1 || $i == $rand ? 'low' : 'yes',
                'qty'   => $custom_fence_products[$key]['qty'],
                'original_qty' => $custom_fence_products[$key]['qty'],
            ];
            $i++;
        }
    }

    // Sort by SKU
    array_multisort(array_map(function($element) {
        return $element['sku'];
    }, $cart['items']), SORT_ASC, $cart['items']);


    $_SESSION['fc_cart'] = $cart;

}

//----------------------------------------------------------------

function is_localhost($whitelist = ['127.0.0.1', '::1']) {
    return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
}

//----------------------------------------------------------------

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key => $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
}

//----------------------------------------------------------------

function add_filepath_last($filename, $add ='') {

    $arr = pathinfo($filename);

    if( !$arr['filename'] ) return;

    $file = [
        $arr['dirname'].'/', 
        $arr['filename'], 
        $add,
        '.'.$arr['extension']
    ];

    $url = implode('', array_filter($file));

    return $url;

}

//----------------------------------------------------------------

/*
    [major].[minor].[patch]-[build/beta/rc]
    X.Y.Z
*/
function get_version() {
    return '3.0.0';
}

//----------------------------------------------------------------
