<?php
session_start();

include('data/settings.php');
include('helpers.php');

if( @$_POST['action'] == 'place_order' ) {

    $data = [
        'error' => FALSE,
        'url' => 'thank-you.php',
    ];
    
    echo json_encode($data);
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

    $_SESSION["fc_data"] = array_merge($_SESSION["fc_data"], $_POST);

} elseif( @$_POST['action'] == 'update_cart' ) {
 
    $cart_items_data = array();

    $cart_subtotal = 0;

    $post_data = $_POST['cart'];

    $cart = $_SESSION['fc_cart'];

    foreach ( $_SESSION['fc_cart']['items'] as $cart_item_k => $cart_item) {

        $quantity = $post_data['qty'][$cart_item_k];
        $cart_item_subtotal = $quantity * $cart_item['trade_price'];

        $cart_items_data[$cart_item_k] = $cart_item;

        // UPDATE CART ITEM QTY
        $cart_items_data[$cart_item_k]['qty']      = $quantity;

        // UPDATE CART ITEM SUBTOTAL
        $cart_items_data[$cart_item_k]['subtotal'] = $cart_item_subtotal;

        // SUM SUBTOTAL
        $cart_subtotal += $cart_item_subtotal; 
    } 

    $gst = $trade_discount = 0;

    // GET DELIVERY FEE
    $deliver_options = fc_deliver_options();
    $do_key          = array_search($post_data['shipping_type'], array_column($deliver_options, 'value'));
    $delivery_fee    = $deliver_options[$do_key]['price'];

    $total = $cart_subtotal + $delivery_fee + $gst;



    $cart_data = [
        'items'          => $cart_items_data, //
        'shipping_type'  => $post_data['shipping_type'], //
        'subtotal'       => $cart_subtotal, //
        'trade_discount' => $trade_discount, 
        'delivery_fee'   => $delivery_fee, //
        'gst'            => $gst,
        'total'          => $total,
    ];

    $_SESSION['fc_cart'] = $cart_data;

    include('temp/cart-table.php');
    $include = ob_get_contents();
    ob_end_clean();

    echo $include;

    // dd( $_SESSION['fc_cart'] );

    // echo json_encode($_POST);

}

exit;


