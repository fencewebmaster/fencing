<?php
session_start();

$_SESSION["fc_data"] = $data = $_POST;

// START - GET PRODUCTS FROM THE STORE
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://fencesperth.com?fc_action=get_products',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'[
    {"sku": "XP-4200-GSF09-B-CTS"},
    {"sku": "XP-1800-FP-BS"},
    {"sku": "XP-SCREWSGF-10pk-CTS"},
    {"sku": "XP-6100-S65-SM-CTS"},
    {"sku": "PP-PANEL-TRANS-B | PP-TRANSKIT-B"}
]',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);

$items = json_decode($response);

foreach ($items as $item) {
	$cart['items'][] = [
		'name' => $item->name,
		'sku' => $item->sku,
		'stock' => $item->stock,
		'qty' => 1,
	];
}

$_SESSION['fc_cart'] = $cart;

// END - GET PRODUCTS FROM THE STORE

echo json_encode($data);