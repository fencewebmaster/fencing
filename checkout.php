<?php
session_start();

include 'data/settings.php';
include 'helpers.php';
include 'config/database.php'; 

if( @$_POST['action'] == 'push_order' ) {

    $info = $_SESSION;

    $info['planner_id'] = $planner_id = @$info['planner_id'];

    $data = json_encode($info);

    $fc_data     = $info['fc_data'];
    $fc_products = $info['custom_fence_products'];
    $fc_cart     = $info['fc_cart'];
    $fc_site     = $info['site'];

    $data_inputs = [
      'planner_id'         => $planner_id,
      'site_id'            => $fc_site['id'],
      'site_url'           => $fc_site['url'],
      'order_id'           => 0,
      'status'             => 'planning',
      'status_updated_at'  => date('Y-m-d H:i:s'),
      'section_count'      => count(json_decode($fc_data['fences'])),
      'notes'              => @$fc_data['notes'],
      'name'               => @$fc_data['name'],
      'mobile'             => @$fc_data['mobile'],
      'email'              => @$fc_data['email'],
      'address'            => @$fc_data['address'],
      'postcode'           => @$fc_data['postcode'],
      'state'              => @$fc_data['state'],
      'fence_type'         => ['aluminum'],
      'timeframe'          => @$fc_data['timeframe'],
      'installer'          => @$fc_data['installer'],
      'extra'              => @$fc_data['extra'] ? $fc_data['extra'] : $fc_data['nothing_extra'],
      'color_data'         => @$fc_data['color'],
      'products_data'      => $fc_products,
      'fence_data'         => @$fc_data['fences'],
      'cart_data'          => @$fc_cart['items'],
      'cart_items_data'    => @$fc_data['cart_items'],
      'project_plans_data' => @$fc_data['project_plans'],
      'created_at'         => date('Y-m-d H:i:s'),
      'updated_at'         => date('Y-m-d H:i:s'),
    ];

    $db = new Database();
    $res = $db->insert('planners', $data_inputs);

    if( ! $res['success'] ) {
        return;
    }

    // Push to ZAP
    $post_data = [
        "contacts" => [
            [
                "name"   => @$fc_data['name'],
                "email"  => @$fc_data['email'],
                "phones" => @$fc_data['mobile'],
            ],
        ],
        "addresses" => [
            [
                "city"      => '',
                "state"     => @$fc_data['state'],
                "country"   => "US",
            ],
        ],
        "opportunities" => [
            [
                "value"          => 0,
                "date_won"       => date('Y-m-d'),
                "form_name"      => 'Fencing Calculator',
                "note"           => @$fc_data['notes'],
                "summary"        => "Fencing Calculator - ". date('Y-m-d') ." - ".PHP_EOL." FORM NOTES/DETAILS: " .PHP_EOL. fc_timeframe(@$fc_data['timeframe']) .PHP_EOL. fc_installer(@$fc_data['installer']) . PHP_EOL . @$fc_data['notes'],
                "installer"      => fc_installer(@$fc_data['installer']),
                "quote_id"       => $planner_id,
                "planner_url"    => base_url('?qid='.$planner_id),
                "fencing_type"   => 'aluminum',
                "timeframe"      => fc_timeframe(@$fc_data['timeframe']),
            ],
        ],
    ];

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://hooks.zapier.com/hooks/catch/13215869/b0451hl/',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => json_encode($post_data),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);



    // push to WP
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $fc_site['url'].'?fc_action=push&date='.date('mdYHis'),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    echo $response;

    // Clear fence session data
    unset($_SESSION['fc_data'], $_SESSION['custom_fence_products'], $_SESSION['fc_cart'], $_SESSION['planner_id'], $_SESSION['site']);

    exit;


    require 'vendor/autoload.php'; // Include the Stripe PHP library

    \Stripe\Stripe::setApiKey('sk_test_RXv2cjYIBVyIWk8wEdLnIkf2'); // Replace with your actual secret key

   // header('Content-Type: application/json');

    // Create a Customer:
    try {

        $customer = \Stripe\Customer::create([
            'name'    => ucwords($_POST['name']), 
            'email'   => @$_POST['email'],
            'source'  => @$_POST['stripeToken']
        ]);

        // $customer->id contains the customer ID, which you can save in your database
    } catch (\Stripe\Exception\CardException $e) {
        // Handle card errors

        $data = [
            'error' => TRUE,
            'message' => 'Error: ' . $e->getError()->message
        ];

        echo json_encode($data);
        exit;

    } catch (Exception $e) {
        // Handle other errors

        $data = [
            'error' => TRUE,
            'message' => 'Error: An error occurred while creating the customer.'
        ];

        echo json_encode($data);
        exit;

    }

    // Charge the Customer:
    try {
        $charge = \Stripe\Charge::create([
            'amount' => 2000, // Amount in cents
            'currency' => 'usd',
            'customer' => $customer->id, // Customer ID obtained from the previous step
            'description' => 'Example Charge',
        ]);

        $data = [
            'error' => FALSE,
            'message' => 'Error: ' . $e->getError()->message,
            'response' => $charge,
        ];

        // $charge->id contains the charge ID, which you can save or use for reference
    } catch (\Stripe\Exception\CardException $e) {
        // Handle card errors

        $data = [
            'error' => TRUE,
            'message' => 'Error: ' . $e->getError()->message
        ];

    } catch (Exception $e) {

        // Handle other errors
        $data = [
            'error' => TRUE,
            'message' => 'Error: An error occurred while processing the payment.'
        ];

    }

    echo json_encode($data);

} elseif( in_array(@$_POST['action'], ['save_planner']) ) {

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

    if( ! @$res['success'] ) {

        $data = [
            'error' => TRUE,
            'message' => 'Error: An error occurred while saving planner.',
            'url' => ''
        ];

    } else {

        $data = [
            'error' => FALSE,
            'message' => 'Planner has been successfully saved!',
            'id' => $planner_id
        ];

    }

    echo json_encode($data);

} elseif( in_array(@$_POST['action'], ['update_details', 'update_project_details']) ) {

    /* START UPDATE item list & cart */
    $color = $_SESSION["fc_data"]['color'];

    if( $_POST['color']['value'] != $color['value'] ) {
        
        $custom_fence_data['color'] = $_POST['color']['value'];

        foreach ( $_SESSION['fc_cart']['items'] as $cart_item_k => $cart_item) {

            $custom_fence_data['items'][] = [
                'sku'         => $cart_item['sku'], 
                'qty'         => $cart_item['qty'],
                'orignal_qty' => $cart_item['orignal_qty'],
                'slug'        => $cart_item['slug']
            ];

        } 

        post_product_skus($custom_fence_data);
    }
    /* END UPDATE item list & cart */

    $_SESSION["fc_data"] = array_merge($_SESSION["fc_data"], $_POST);
    
    include('temp/sections/your-project-details.php');
    $include = ob_get_contents();
    ob_end_clean();

    echo $include;

} elseif( @$_POST['action'] == 'update_cart' ) {
 
    $cart_items_data = array();

    $post_data = $_POST['cart'];

    $cart = $_SESSION['fc_cart'];

    $color = $_SESSION["fc_data"]['color'];
    $custom_fence_data['color'] = $color['value'];

    foreach ( $_SESSION['fc_cart']['items'] as $cart_item_k => $cart_item) {

        $quantity = $post_data['qty'][$cart_item_k];

        $cart_items_data[$cart_item_k] = $cart_item;

        // UPDATE CART ITEM QTY
        $cart_items_data[$cart_item_k]['qty']      = $quantity;
    } 


    $cart_data = [
        'items' => $cart_items_data, 
    ];


    $_SESSION['fc_cart'] = $cart_data;

    include('temp/sections/cart-table.php');
    $include = ob_get_contents();
    ob_end_clean();

    echo $include;

    // dd( $_SESSION['fc_cart'] );

    // echo json_encode($_POST);

}

exit;


