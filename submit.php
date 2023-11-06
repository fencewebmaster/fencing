<?php
session_start();

include('helpers.php');

if( $_POST ) {
    $_SESSION["fc_data"] = $data = $_POST;
    $_SESSION["fc_data"]['color'] = json_decode($_SESSION["fc_data"]['color'], true);
}

$color = $_SESSION["fc_data"]['color'];

$custom_fence_data = [
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

$cart_items = json_decode($_SESSION["fc_data"]['cart_items'], true);

post_product_skus($cart_items);

// END - GET PRODUCTS FROM THE STORE
