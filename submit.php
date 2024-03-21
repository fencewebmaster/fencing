<?php
session_start();

include 'config/helpers.php';
include 'data/settings.php';
include 'config/database.php'; 


if( $_POST ) {
    $_SESSION["fc_data"] = $data = $_POST;
}


$colors = convert_inputs($_SESSION["fc_data"]['color']);

$cart_items_grouped = json_decode($_SESSION["fc_data"]['cart_items'], true);

$cart_items_regrouped = $cart_items_formatted = $cart_items_data = [];

// Regrouped the cart items
foreach($cart_items_grouped as $cart_item) {

    foreach ($cart_item as $cart_item_key => $ci_items) {

      foreach ($ci_items as $ci_v) {

      $color_key = array_search($cart_item_key, array_column($colors, 'fence'));
      $cart_item_id =  $cart_item_key.'+'.$colors[$color_key]['color'];

      $cart_items_regrouped[$cart_item_id][$ci_v['slug']][] = $ci_v['qty']; 

      }

    }
}

// Reformat the cart items
foreach($cart_items_regrouped as $cir_k => $cir_items) {

    $cart_items_formatted = [];

    foreach ($cir_items as $ciri_k => $ciri_v) {

      $cart_items_formatted[] = [
          'slug' => $ciri_k,
          'qty' => array_sum($ciri_v),
      ];


    }

    $c = explode('+', $cir_k);

    $cart_items_data[$cir_k] = [
      'slug'  => $c[0],
      'color' => $c[1],
      'items' => $cart_items_formatted,
    ];

}

post_product_skus($cart_items_data);

// END - GET PRODUCTS FROM THE STORE

// Save or update planner
$info = $_SESSION;

$planner_id  = isset($info['planner_id']) ? $info['planner_id'] : get_uid(6);

$_SESSION['planner_id'] = $planner_id;

$data = json_encode($info);

$fc_data     = @$info['fc_data'];
$fc_products = @$info['custom_fence_products'];
$fc_cart     = @$info['fc_cart'];
$fc_site     = @$info['site'];

$data_inputs = [
  'planner_id'         => $planner_id,
  'site_id'            => $fc_site['id'],
  'site_url'           => $fc_site['url'],
  'order_id'           => 0,
  'status'             => 'planning',
  'status_updated_at'  => date('Y-m-d H:i:s'),
  'section_count'      => @$fc_data['fences'] ? count(json_decode($fc_data['fences'])) : 0,
  'notes'              => @$fc_data['notes'],
  'name'               => @$fc_data['name'],
  'mobile'             => @$fc_data['mobile'],
  'email'              => @$fc_data['email'],
  'address'            => @$fc_data['address'],
  'fence_type'         => selected_fences($fences, 'slug'),
  'timeframe'          => @$fc_data['timeframe'],
  'installer'          => @$fc_data['installer'],
  'extra'              => @$fc_data['extra'] ? @$fc_data['extra'] : @$fc_data['nothing_extra'],
  'color_data'         => @$fc_data['color'],
  'products_data'      => $fc_products,
  'fence_data'         => @$fc_data['fences'],
  'cart_data'          => @$fc_cart['items'],
  'cart_items_data'    => @$fc_data['cart_items'],
  'project_plans_data' => @$fc_data['project_plans'],
  'created_at'         => date('Y-m-d H:i:s'),
  'updated_at'         => date('Y-m-d H:i:s'),
];

$where = ['planner_id' => $planner_id];

$db = new Database();
$res = $db->updateOrCreate('planners', $data_inputs, $where);

echo 'SUCCESS';
exit;