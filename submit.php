<?php
require_once __DIR__ . '/config/session.php';
fc_session_start();
 
include 'config/helpers.php';
include 'data/settings.php';
include 'config/database.php';
require_once __DIR__ . '/config/planners.php';

if( $_POST ) {
    $_SESSION["fc_data"] = $data = $_POST;
    if ( isset( $_SESSION['fc_data']['mobile'] ) ) {
        $_SESSION['fc_data']['mobile'] = fc_normalize_mobile_for_storage( $_SESSION['fc_data']['mobile'] );
    }
}

$colors = fc_planner_color_rows_from_session();
$cart_items_grouped = json_decode($_SESSION["fc_data"]['cart_items'], true);
$cart_items_regrouped = fc_regroup_planner_cart_items_for_skus($cart_items_grouped, $colors);
$cart_items_data = fc_format_regrouped_cart_items_for_product_skus(
    $cart_items_regrouped,
    $_SESSION['fc_data']['fences'] ?? '[]'
);

post_product_skus($cart_items_data);

// [END] - GET PRODUCTS FROM THE STORE

// Save or update planner
$info = $_SESSION;

$planner_ref = fc_planner_resolve_submission_planner_id($_POST['planner_id'] ?? null);
$_SESSION['planner_id'] = $planner_id = $planner_ref['planner_id'];

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
if (! $planner_ref['exists']) {
    $data_inputs['created_at'] = date('Y-m-d H:i:s');
}

$where = ['planner_id' => $planner_id];

$db = new Database();
$res = $db->updateOrCreate('planners', $data_inputs, $where);

if (empty($res['success'])) {
    error_log('FC submit.php: could not save planner ' . $planner_id . ' — ' . (string) ($res['message'] ?? 'unknown error'));
    http_response_code(500);
    echo 'ERROR';
    exit;
}

echo 'SUCCESS:' . $planner_id;
exit;
