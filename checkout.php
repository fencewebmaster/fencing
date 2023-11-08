<?php
session_start();

include('data/settings.php');
include('helpers.php');

if( @$_POST['action'] == 'push_order' ) {

    $data = json_encode($_SESSION);

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://fencesperth.com?fc_action=push&date'.date('mdYHis'),
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
    unset($_SESSION['fc_data'], $_SESSION['custom_fence_products'], $_SESSION['fc_cart']);

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

} elseif( @$_POST['action'] == 'update_details' ) {

    /* START UPDATE item list & cart */
    $color = $_SESSION["fc_data"]['color'];

    if( $_POST['color']['value'] != $color['value'] ) {
        
        $custom_fence_data['color'] = $_POST['color']['value'];

        foreach ( $_SESSION['fc_cart']['items'] as $cart_item_k => $cart_item) {

            $custom_fence_data['items'][] = [
                'sku'  => $cart_item['sku'], 
                'qty'  => $cart_item['qty'],
                'slug' => $cart_item['slug']
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


