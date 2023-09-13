<?php

require 'vendor/autoload.php'; // Include the Stripe PHP library

\Stripe\Stripe::setApiKey('sk_test_RXv2cjYIBVyIWk8wEdLnIkf2'); // Replace with your actual secret key

header('Content-Type: application/json');

/*
echo '<pre>';
print_r($_POST );
exit;*/

// Create a Customer:
try {
    $customer = \Stripe\Customer::create([
        'name'    => ucwords($_POST['firstname'].' '.$_POST['lastname']), 
        'email'   => $_POST['email'],
        'source'  => $_POST['stripeToken']
    ]);

    // $customer->id contains the customer ID, which you can save in your database
} catch (\Stripe\Exception\CardException $e) {
    // Handle card errors
    echo 'Error: ' . $e->getError()->message;
} catch (Exception $e) {
    // Handle other errors
    echo 'Error: An error occurred while creating the customer.';
}

// Charge the Customer:
try {
    $charge = \Stripe\Charge::create([
        'amount' => 2000, // Amount in cents
        'currency' => 'usd',
        'customer' => $customer->id, // Customer ID obtained from the previous step
        'description' => 'Example Charge',
    ]);

    // $charge->id contains the charge ID, which you can save or use for reference
} catch (\Stripe\Exception\CardException $e) {
    // Handle card errors
    echo 'Error: ' . $e->getError()->message;
} catch (Exception $e) {
    // Handle other errors
    echo 'Error: An error occurred while processing the payment.';
}

echo '<pre>';
print_r($charge );
