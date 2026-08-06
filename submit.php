<?php
require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Core/SessionBootstrap.php';
\Fc\Admin\Core\SessionBootstrap::start();
 
include 'writable/settings.php';
require_once __DIR__ . '/app/src/Services/DatabaseConfigService.php';
require_once __DIR__ . '/app/src/Services/Database.php';
require_once __DIR__ . '/app/src/Services/PlannerRecordService.php';
require_once __DIR__ . '/app/src/Services/PlannerSessionService.php';
require_once __DIR__ . '/app/src/Services/FenceCatalogService.php';
require_once __DIR__ . '/app/src/Services/CartBuilderService.php';

if( $_POST ) {
    $_SESSION["fc_data"] = $data = $_POST;
    if ( isset( $_SESSION['fc_data']['mobile'] ) ) {
        $_SESSION['fc_data']['mobile'] = \Fc\Admin\Services\CartBuilderService::normalizeMobileForStorage( $_SESSION['fc_data']['mobile'] );
    }
}

$colors = \Fc\Admin\Services\PlannerSessionService::colorRowsFromSession();
$cart_items_grouped = json_decode($_SESSION["fc_data"]['cart_items'], true);
$cart_items_regrouped = \Fc\Admin\Services\FenceCatalogService::regroupPlannerCartItemsForSkus($cart_items_grouped, $colors);
$cart_items_data = \Fc\Admin\Services\FenceCatalogService::formatRegroupedCartItemsForProductSkus(
    $cart_items_regrouped,
    $_SESSION['fc_data']['fences'] ?? '[]'
);

\Fc\Admin\Services\CartBuilderService::postProductSkus($cart_items_data);

// [END] - GET PRODUCTS FROM THE STORE

// Save or update planner
$info = $_SESSION;

$planner_ref = \Fc\Admin\Services\PlannerRecordService::resolveSubmissionPlannerId($_POST['planner_id'] ?? null);
$_SESSION['planner_id'] = $planner_id = $planner_ref['planner_id'];

// This save fired automatically right after a `?qid=` reload (see p1.js: fcRunQuoteReloadSubmit).
// planner.php already set status='reloaded' for this same request cycle — don't clobber it back to 'planning'.
$is_quote_reload = !empty($_POST['is_quote_reload']);

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
  'mobile'             => \Fc\Admin\Services\CartBuilderService::normalizeMobileForStorage( @$fc_data['mobile'] ),
  'email'              => @$fc_data['email'],
  'address'            => @$fc_data['address'],
  'postcode'           => @$fc_data['postcode'],
  'state'              => @$fc_data['state'],
  'fence_type'         => \Fc\Admin\Services\PlannerSessionService::selectedFences($fences, 'slug'),
  'timeframe'          => @$fc_data['timeframe'],
  'extra'              => \Fc\Admin\Services\PlannerRecordService::extraForDb(
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

if ($is_quote_reload) {
    unset($data_inputs['status'], $data_inputs['status_updated_at']);
}

$data_inputs = array_merge($data_inputs, \Fc\Admin\Services\PlannerRecordService::submissionMeta());

// Keep the original creation time when updating an existing quote.
if (! $planner_ref['exists']) {
    $data_inputs['created_at'] = date('Y-m-d H:i:s');
}

$where = ['planner_id' => $planner_id];

$db = new \Fc\Admin\Services\Database();
$res = $db->updateOrCreate('planners', $data_inputs, $where);

if (empty($res['success'])) {
    error_log('FC submit.php: could not save planner ' . $planner_id . ' — ' . (string) ($res['message'] ?? 'unknown error'));
    http_response_code(500);
    echo 'ERROR';
    exit;
}

echo 'SUCCESS:' . $planner_id;
exit;
