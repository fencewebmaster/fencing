<?php
session_start();

include('helpers.php');


if( $_POST ) {
    $_SESSION["fc_data"] = $data = $_POST;
    $_SESSION["fc_data"]['color'] = json_decode($_SESSION["fc_data"]['color']);
}

$color = $_SESSION["fc_data"]['color'];

$custom_fence_data = [
    'color' => $color->value,
    'items' => [
      [
        'slug' => 'panel_options+even', 
        'qty' => 1
      ],
      [
        'slug' => 'panel_options+full', 
        'qty' => 2
      ],
      [
        'slug' => 'raked_panel+1300x300', 
        'qty' => 3
      ],
      [
        'slug' => 'raked_panel+1400x400', 
        'qty' => 4
      ],
      [
        'slug' => 'raked_panel+1500x500', 
        'qty' => 5
      ],
      [
        'slug' => 'raked_panel+1600x600', 
        'qty' => 6
      ],
      [
        'slug' => 'raked_panel+1700x700', 
        'qty' => 7
      ],
      [
        'slug' => 'raked_panel+1800x600', 
        'qty' => 8
      ],
      [
        'slug' => 'gate', 
        'qty' => 9
      ],
      [
        'slug' => 'panel_post+opt-1', 
        'qty' => 10
      ],
      [
        'slug' => 'panel_post+opt-2', 
        'qty' => 11
      ],
      [
        'slug' => 'raked_post+opt-1', 
        'qty' => 12
      ],
      [
        'slug' => 'raked_post+opt-2', 
        'qty' => 13
      ],
      [
        'slug' => 'raked_panel_post+opt-1', 
        'qty' => 14
      ],
      [
        'slug' => 'panel options+bracket', 
        'qty' => 15
      ],
      [
        'slug' => 'gate_kit', 
        'qty' => 16
      ],
      [
        'slug' => 'post_options+opt-2', 
        'qty' => 17
      ] 
    ]
];

$post_query = array();

$skus = get_product_skus($custom_fence_data);

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

//Postman
//curl  https://fencesperth.com?fc_action=get_products -X post --header 'Content-Type:application/json' -d '[{"sku": "XP-4200-GSF09-B-CTS"},{"sku": "XP-1800-FP-BS"},{"sku": "XP-SCREWSGF-10pk-CTS"},{"sku": "XP-6100-S65-SM-CTS"},{"sku": "PP-PANEL-TRANS-B | PP-TRANSKIT-B"}]'

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

echo json_encode($items);
// END - GET PRODUCTS FROM THE STORE
