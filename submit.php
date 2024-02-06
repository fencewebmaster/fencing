<?php
session_start();

include('helpers.php');
include 'config/database.php'; 


if( $_POST ) {
    $_SESSION["fc_data"] = $data = $_POST;
    $_SESSION["fc_data"]['color'] = json_decode($_SESSION["fc_data"]['color'], true);
}


$cart_items_grouped = json_decode($_SESSION["fc_data"]['cart_items'], true);

$cart_items_regrouped = $cart_items_formatted = [];



// Merged the cart items
$cart_items_merged = array_merge(...$cart_items_grouped);

// Regrouped the cart items
foreach($cart_items_merged as $cart_item) {
    $cart_items_regrouped[$cart_item['slug']][] = $cart_item['qty']; 
}

// Reformat the cart items
foreach($cart_items_regrouped as $ci_k => $ci_v) {
    $cart_items_formatted[] = [
        'slug' => $ci_k,
        'qty' => array_sum($ci_v),
    ];
}


$color = $_SESSION["fc_data"]['color'];

$cart_items_data = [
    'color' => $color['value'],
    'items' => $cart_items_formatted
];

/*$custom_fence_data = [
    'color' => $color['value'],
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
        'slug' => 'panel_options+bracket', 
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

$custom_fence_data = [
    'color' => $color['value'],
    'items' => [
      ['slug' => "raked_post+opt-1", 'qty' => 2],
      ['slug' => "raked_panel+1300x1200", 'qty' => 1],
      ['slug' => "panel_options+full", 'qty' => 3],
      ['slug' => "gate_kit", 'qty' => 1],
      ['slug' => "gate", 'qty' => 1],
      ['slug' => "raked_panel+1700x1200", 'qty' => 1],
      ['slug' => "panel_options+bracket", 'qty' => 3],
  ]
];
*/

post_product_skus($cart_items_data);

// post_product_skus($custom_fence_data);

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
  'fence_type'         => ['aluminum'],
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