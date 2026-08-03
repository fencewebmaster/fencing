<?php
require_once __DIR__ . '/config/session.php';
fc_session_start();

include 'data/settings.php';
include 'config/helpers.php';
include 'config/database.php';
require_once __DIR__ . '/config/planners.php';

if( @$_POST['action'] == 'push_order' ) {

    $info = $_SESSION;

    // Never mint an id here: an order push must attach to the quote that was already saved.
    $planner_ref = fc_planner_resolve_submission_planner_id( @$_POST['planner_id'], false );
    $planner_id  = $planner_ref['planner_id'] !== '' ? $planner_ref['planner_id'] : null;

    if ( ! $planner_id ) {
        echo json_encode( array(
            'error'   => true,
            'message' => 'Missing planner ID. Please save your project first.',
        ) );
        exit;
    }

    $fc_site = isset( $info['site'] ) ? $info['site'] : null;

    if ( ! $fc_site || empty( $fc_site['url'] ) ) {
        echo json_encode( array(
            'error'   => true,
            'message' => 'Site configuration is missing.',
        ) );
        exit;
    }

    $wp_site_url = fc_wp_site_url();
    if ( $wp_site_url ) {
        $fc_site['url'] = $wp_site_url;
    }

    $fc_data     = $info['fc_data'];
    $fc_products = $info['custom_fence_products'];
    $fc_cart     = $info['fc_cart'];

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
      'mobile'             => fc_normalize_mobile_for_storage( @$fc_data['mobile'] ),
      'email'              => @$fc_data['email'],
      'address'            => @$fc_data['address'],
      'postcode'           => @$fc_data['postcode'],
      'state'              => @$fc_data['state'],
      'fence_type'         => selected_fences($fences, 'slug'),
      'timeframe'          => @$fc_data['timeframe'],
      'extra'              => fc_planners_extra_for_db(
          @$fc_data['extra'],
          isset($fc_data['nothing_extra']) ? (string) $fc_data['nothing_extra'] : null
      ),
      'color_data'         => @$fc_data['color'],
      'products_data'      => $fc_products,
      'fence_data'         => @$fc_data['fences'],
      'cart_data'          => @$fc_cart['items'],
      'cart_items_data'    => @$fc_data['cart_items'],
      'project_plans_data' => @$fc_data['project_plans'],
      'updated_at'         => date('Y-m-d H:i:s'),
    ];

    $data_inputs = array_merge($data_inputs, fc_planner_submission_meta());

    // Keep the original creation time when updating an existing quote.
    if ( ! $planner_ref['exists'] ) {
        $data_inputs['created_at'] = date('Y-m-d H:i:s');
    }

    $where = [ 'planner_id' => $planner_id ];

    $db = new Database();
    $res = $db->updateOrCreate( 'planners', $data_inputs, $where );

    if( ! $res['success'] ) {
        echo json_encode( array(
            'error'   => true,
            'message' => 'Could not save planner: ' . ( $res['message'] ?? 'Unknown error' ),
        ) );
        exit;
    }

    $data = json_encode( $info );

    // push to WP
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => rtrim( $fc_site['url'], '/' ) . '/?fc_action=push&date=' . date( 'mdYHis' ),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);
    $curl_error = curl_error( $curl );
    $http_code  = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
    curl_close($curl);

    if ( $response === false || $http_code >= 400 ) {
        echo json_encode( array(
            'error'   => true,
            'message' => 'Could not reach the store (' . ( $curl_error ?: 'HTTP ' . $http_code ) . ').',
        ) );
        exit;
    }

    $wp_res = json_decode( $response, true );
    if ( ! is_array( $wp_res ) || empty( $wp_res['token'] ) || empty( $wp_res['url'] ) ) {
        echo json_encode( array(
            'error'   => true,
            'message' => 'The store returned an invalid push response.',
        ) );
        exit;
    }

    // Push succeeded — the quote has actually been handed off to the store, so mark it submitted.
    $db->update( 'planners', [
        'status'            => 'submitted',
        'status_updated_at' => date('Y-m-d H:i:s'),
    ], [ 'planner_id' => $planner_id ] );

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

    $planner_ref = fc_planner_resolve_submission_planner_id(@$_POST['planner_id']);
    $planner_id  = $planner_ref['planner_id'];

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
      'mobile'             => fc_normalize_mobile_for_storage( @$fc_data['mobile'] ),
      'email'              => @$fc_data['email'],
      'address'            => @$fc_data['address'],
      'fence_type'         => selected_fences($fences, 'slug'),
      'timeframe'          => @$fc_data['timeframe'],
      'extra'              => fc_planners_extra_for_db(
          @$fc_data['extra'],
          isset($fc_data['nothing_extra']) ? (string) $fc_data['nothing_extra'] : null
      ),
      'color_data'         => @$fc_data['color'],
      'products_data'      => $fc_products,
      'fence_data'         => @$fc_data['fences'],
      'cart_data'          => @$fc_cart['items'],
      'cart_items_data'    => @$fc_data['cart_items'],
      'project_plans_data' => @$fc_data['project_plans'],
      'updated_at'         => date('Y-m-d H:i:s'),
    ];

    $data_inputs = array_merge($data_inputs, fc_planner_submission_meta());

    // Keep the original creation time when updating an existing quote.
    if ( ! $planner_ref['exists'] ) {
        $data_inputs['created_at'] = date('Y-m-d H:i:s');
    }

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

    $posted_colors = isset( $_POST['color'] ) ? $_POST['color'] : null;

    unset($_POST['cart'], $_POST['action']);

    if ( ! empty( $_POST['nothing_extra'] ) ) {
        $_SESSION['fc_data']['nothing_extra'] = (string) $_POST['nothing_extra'];
        $_POST['extra'] = '[]';
    } else {
        unset( $_SESSION['fc_data']['nothing_extra'], $_POST['nothing_extra'] );
        $_POST['extra'] = fc_convert_inputs( $_POST['extra'] ?? '[]' );
        if ( $_POST['extra'] === '' || $_POST['extra'] === null ) {
            $_POST['extra'] = '[]';
        }
    }

    $_POST['color'] = fc_convert_inputs($_POST['color']);

    if ( isset( $_POST['project_plans'] ) && (string) $_POST['project_plans'] !== '' ) {
        $_SESSION['fc_data']['project_plans'] = (string) $_POST['project_plans'];
    }

    $_SESSION["fc_data"] = array_merge($_SESSION["fc_data"], $_POST);

    if ( isset( $_SESSION['fc_data']['mobile'] ) ) {
        $_SESSION['fc_data']['mobile'] = fc_normalize_mobile_for_storage( $_SESSION['fc_data']['mobile'] );
    }

    /* Rebuild item list & cart SKUs from stored BOM + updated colour rows */
    if ( $posted_colors ) {
        $colors = fc_planner_color_rows_from_session( $posted_colors );
        $cart_items_raw = isset( $_SESSION['fc_data']['cart_items'] ) ? $_SESSION['fc_data']['cart_items'] : '[]';
        $cart_items_grouped = is_array( $cart_items_raw )
            ? $cart_items_raw
            : json_decode( (string) $cart_items_raw, true );

        if ( is_array( $cart_items_grouped ) && count( $cart_items_grouped ) && is_array( $colors ) && count( $colors ) ) {
            $cart_items_regrouped = fc_regroup_planner_cart_items_for_skus( $cart_items_grouped, $colors );
            $cart_items_data      = fc_format_regrouped_cart_items_for_product_skus(
                $cart_items_regrouped,
                isset( $_SESSION['fc_data']['fences'] ) ? $_SESSION['fc_data']['fences'] : '[]'
            );
            if ( ! empty( $cart_items_data ) ) {
                post_product_skus( $cart_items_data );
            }
        }
    }

    ob_start();
    include('views/sections/your-project-details.php');
    $include = ob_get_clean();

    fc_planner_persist_session($fences);

    echo $include;

} elseif ( @$_POST['action'] === 'rebuild_cart_from_plans' ) {

    $color_override = null;
    if ( isset( $_POST['color'] ) && $_POST['color'] !== '' ) {
        $color_override = is_array( $_POST['color'] )
            ? $_POST['color']
            : json_decode( (string) $_POST['color'], true );
    }

    $colors = fc_planner_color_rows_from_session(
        is_array( $color_override ) ? $color_override : null
    );

    $cart_items_raw = isset( $_POST['cart_items'] ) ? (string) $_POST['cart_items'] : '[]';
    $cart_items_grouped = json_decode( $cart_items_raw, true );
    $cart_items_regrouped = fc_regroup_planner_cart_items_for_skus( $cart_items_grouped, $colors );
    $cart_items_data = fc_format_regrouped_cart_items_for_product_skus(
        $cart_items_regrouped,
        isset( $_SESSION['fc_data']['fences'] ) ? $_SESSION['fc_data']['fences'] : '[]'
    );

    if ( ! isset( $_SESSION['fc_data'] ) || ! is_array( $_SESSION['fc_data'] ) ) {
        $_SESSION['fc_data'] = array();
    }
    $_SESSION['fc_data']['cart_items'] = $cart_items_raw;

    if ( isset( $_POST['project_plans'] ) && (string) $_POST['project_plans'] !== '' ) {
        $_SESSION['fc_data']['project_plans'] = (string) $_POST['project_plans'];
    }

    if ( is_array( $color_override ) && $color_override !== array() ) {
        $_SESSION['fc_data']['color'] = fc_convert_inputs( $color_override );
    }

    post_product_skus( $cart_items_data );

    fc_planner_persist_session( $fences );

    ob_start();
    include 'views/sections/cart-table.php';
    $html = ob_get_clean();
    echo $html;
    exit;

} elseif ( @$_POST['action'] === 'toggle_optional_cart' ) {

    $opt_key = isset( $_POST['optional_key'] ) ? trim( (string) $_POST['optional_key'] ) : '';
    $include = isset( $_POST['include'] ) && (string) $_POST['include'] === '1';

    if ( $opt_key !== '' && ! empty( $_SESSION['fc_cart']['items'] ) && is_array( $_SESSION['fc_cart']['items'] ) ) {
        foreach ( $_SESSION['fc_cart']['items'] as $idx => $row ) {
            if ( ! is_array( $row ) || empty( $row['optional'] ) ) {
                continue;
            }
            $row_key = ! empty( $row['optional_key'] )
                ? (string) $row['optional_key']
                : fc_optional_cart_item_key( $row );
            if ( $row_key !== $opt_key ) {
                continue;
            }
            $suggested = (int) ( $row['suggested_qty'] ?? 0 );
            $_SESSION['fc_cart']['items'][ $idx ]['optional_included'] = $include;
            $_SESSION['fc_cart']['items'][ $idx ]['qty']              = $include ? $suggested : 0;
            $_SESSION['fc_cart']['items'][ $idx ]['original_qty']      = $_SESSION['fc_cart']['items'][ $idx ]['qty'];
        }
    }

    if ( ! empty( $_SESSION['custom_fence_products'] ) && is_array( $_SESSION['custom_fence_products'] ) ) {
        foreach ( $_SESSION['custom_fence_products'] as $pk => $prod ) {
            if ( ! is_array( $prod ) || empty( $prod['optional'] ) ) {
                continue;
            }
            if ( fc_optional_cart_item_key( $prod ) !== $opt_key ) {
                continue;
            }
            $suggested = (int) ( $prod['suggested_qty'] ?? 0 );
            $_SESSION['custom_fence_products'][ $pk ]['qty'] = $include ? $suggested : 0;
        }
    }

    ob_start();
    include 'views/sections/cart-table.php';
    echo ob_get_clean();

    fc_planner_persist_session( $fences );
    exit;

} elseif( @$_POST['action'] == 'update_cart' ) {
 
    $cart_items_data = array();

    $post_data = $_POST['cart'];

    $cart = $_SESSION['fc_cart'];

    $color = $_SESSION["fc_data"]['color'];


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

    fc_planner_persist_session( $fences );

    include('views/sections/cart-table.php');
    $include = ob_get_contents();
    ob_end_clean();

    echo $include;

    // dd( $_SESSION['fc_cart'] );

    // echo json_encode($_POST);

}

exit;


