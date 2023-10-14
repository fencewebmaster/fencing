<?php
session_start();

$_SESSION["fc_data"] = $data = $_POST;
$_SESSION["fc_data"]['color'] = json_decode($_SESSION["fc_data"]['color']);

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
  CURLOPT_POSTFIELDS =>'[
    {
        "sku": "XP-4200-GSF09-B-CTS"
    },
    {
        "sku": "XP-1800-FP-BS"
    },
    {
        "sku": "XP-SCREWSGF-10pk-CTS"
    },
    {
        "sku": "XP-6100-S65-SM-CTS"
    },
    {
        "sku": "PP-PANEL-TRANS-B | PP-TRANSKIT-B"
    }
]',
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
$cart = [];

$count = count($items);
$rand = rand(1, $count);

$i=1;
foreach ($items as $item) {
	$cart['items'][] = [
		'name'  => $item->name,
		'sku'   => $item->sku,
		'stock' => $i == $rand ? 'low' : $item->stock,
		'qty'   => 1,
	];
  $i++;
}

$_SESSION['fc_cart'] = $cart;

// END - GET PRODUCTS FROM THE STORE

echo json_encode($data);