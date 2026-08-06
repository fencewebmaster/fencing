<?php
require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Core/SessionBootstrap.php';
\Fc\Admin\Core\SessionBootstrap::start();

require_once __DIR__ . '/app/src/Services/DatabaseConfigService.php';
require_once __DIR__ . '/app/src/Services/Database.php';
require_once __DIR__ . '/app/src/Services/PlannerRecordService.php';
require_once __DIR__ . '/app/src/Services/SiteRegistryService.php';
require_once __DIR__ . '/app/src/Services/PlannerSessionService.php';
require_once __DIR__ . '/app/src/Services/FenceCatalogService.php';
require_once __DIR__ . '/app/src/Services/CartBuilderService.php';
require_once __DIR__ . '/app/src/Services/WcProductCsvService.php';
require_once __DIR__ . '/app/src/Helpers/UrlHelper.php';
require_once __DIR__ . '/app/src/Helpers/AssetHelper.php';

$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
$cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];

if ( empty( $info ) && ! empty( $_GET['qid'] ) ) {
    $qid = trim( (string) $_GET['qid'] );
    $db  = new \Fc\Admin\Services\Database();
    $row = \Fc\Admin\Services\PlannerRecordService::isValidPlannerId( $qid )
        ? $db->select_where( 'planners', '`planner_id`="' . $qid . '"' )
        : null;
    if ( $row && is_object( $row ) && ! \Fc\Admin\Services\PlannerRecordService::rowIsTrashed( $row ) ) {
        $_SESSION['planner_id'] = $qid;
        $site = \Fc\Admin\Services\SiteRegistryService::all( $_SERVER['HTTP_HOST'], 'domain', true );
        if ( $site ) {
            $_SESSION['site'] = $site;
        }
        \Fc\Admin\Services\PlannerSessionService::hydrateFromRow( $row );
        \Fc\Admin\Services\PlannerRecordService::markReloaded( $qid );
        $info = isset( $_SESSION['fc_data'] ) ? $_SESSION['fc_data'] : [];
    }
}

if ( empty( $info ) ) {
	header("Location: ./");
	die();
}

date_default_timezone_set('Asia/Manila');

include 'writable/settings.php';
include 'views/fields.php';

if ( ! empty( $info['cart_items'] ) ) {
	$cart_items_grouped = json_decode( $info['cart_items'], true );
	if ( is_array( $cart_items_grouped ) && count( $cart_items_grouped ) ) {
		$colors = \Fc\Admin\Services\PlannerSessionService::colorRowsFromSession();
		$cart_items_regrouped = \Fc\Admin\Services\FenceCatalogService::regroupPlannerCartItemsForSkus( $cart_items_grouped, $colors );
		$cart_items_data = \Fc\Admin\Services\FenceCatalogService::formatRegroupedCartItemsForProductSkus(
			$cart_items_regrouped,
			isset( $info['fences'] ) ? $info['fences'] : '[]'
		);
		if ( ! empty( $cart_items_data ) ) {
			\Fc\Admin\Services\CartBuilderService::postProductSkus( $cart_items_data );
			$cart = isset( $_SESSION['fc_cart'] ) ? $_SESSION['fc_cart'] : $cart;
		}
	}
}

if ( ! empty( $cart['items'] ) && is_array( $cart['items'] ) ) {
	\Fc\Admin\Services\WcProductCsvService::ensureItemsHaveImages( $cart['items'] );
	$_SESSION['fc_cart']['items'] = $cart['items'];
}

$site_info = \Fc\Admin\Services\SiteRegistryService::all(@$_SESSION['site']['id'], 'id', true);

/** Same shape as planner `fc_fence_info` — p2.js hydrates localStorage so Project Plans render without planner tab open. */
$fc_fence_info = \Fc\Admin\Services\PlannerSessionService::rowToJsFenceInfo(
	(object) array(
		'fence_data'         => isset( $info['fences'] ) ? $info['fences'] : '',
		'cart_items_data'    => isset( $info['cart_items'] ) ? $info['cart_items'] : '[]',
		'project_plans_data' => isset( $info['project_plans'] ) ? $info['project_plans'] : '',
		'section_count'      => 0,
	)
);
if ( ! empty( $info['fences'] ) ) {
	$decoded_fences = is_array( $info['fences'] ) ? $info['fences'] : json_decode( $info['fences'], true );
	if ( is_array( $decoded_fences ) && $fc_fence_info->section_count < 1 ) {
		$fc_fence_info->section_count = count( $decoded_fences );
	}
}
?>

<!DOCTYPE html>
<html data-fc-debug="<?php
	if (!class_exists('\Fc\Admin\Services\ConsoleSettings')) {
		require_once __DIR__ . '/app/src/Services/ConsoleSettings.php';
	}
	echo \Fc\Admin\Services\ConsoleSettings::debugMode() ? '1' : '0';
?>">
	<head>
	<?php include 'views/partials/head.php'; ?>
	<script>window.FC_DEBUG = document.documentElement.getAttribute('data-fc-debug') === '1';</script>
	</head>
	<body class="fc-project-plan-page fc-project-plan-page-loading">

		<?php include 'views/partials/body-before.php'; ?>

		<!-- [START] FENCING CONTAINER -->
		<div id="place_order-section" class="fencing-container container-lg fc-project-plan fc-position-relative mt-5" data-tab="1">

			<!-- [START] CHECKOUT FORM -->
			<form method="POST" id="paymentFrm" action="<?php echo \Fc\Admin\Helpers\UrlHelper::baseUrl('checkout.php'); ?>">

				<input type="hidden" name="action" value="">

				<!-- [START] PAGE HEADER TITLE -->
		        <div class="fencing-container__header">
		            <div class="row align-items-center">

		                <div class="col-sm">
		                	<div class="mb-3 mb-sm-0">
		                		<?php include 'views/partials/header-left.php'; ?>     
		                	</div>
		                </div>
		                <div class="col-sm-auto col-auto">
							<?php
							$quote_id_dom_id = 'quote-id-1';
							$quote_card_class = 'float-end';
							include 'views/partials/quote-id-card.php';
							?>
		                </div>

		            </div>
		        </div>
				<!-- [END] PAGE HEADER TITLE -->

				<?php include 'views/project-plan/header-tabs.php'; ?>

				<!-- [START] FENCING CONTENT -->
				<div class="fencing-content fc-font-1">

				    <div class="fc-section-step">

				        <div>

				        	<?php include 'views/project-plan/project-details.php'; ?>

				        	<?php include 'views/project-plan/project-plans.php'; ?>

				        	<?php include 'views/project-plan/item-list-cart.php'; ?>

				        </div>
				    </div>
				</div>
				<!-- [END] FENCING CONTENT -->

		    	<?php include 'views/modal/submit/view-2.php'; ?>


			</form>
			<!-- [END] CHECKOUT FORM -->
		    
		</div>
		<!-- [END] FENCING CONTAINER -->

		<?php include 'views/project-plan/modals.php'; ?>

		<!-- Config -->
		<script type="text/javascript">
		var fc_data  = <?php echo json_encode($fences); ?>;
		var base_url = '<?php echo \Fc\Admin\Helpers\UrlHelper::baseUrl(); ?>';
		var fc_fence_info = <?php echo json_encode( $fc_fence_info, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
		var planner_id = "<?php echo @$_SESSION['planner_id']; ?>";
		var planner_share_url = "<?php echo \Fc\Admin\Services\PlannerSessionService::qidShareUrl(); ?>";
		</script>

		<?php include 'views/partials/footer.php'; ?>	

		<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/modern-screenshot.js'); ?>"></script>
		<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/html2canvas.min.js'); ?>"></script>
		<script defer src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>
		<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/checkout.js'); ?>"></script>
		<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/cart-items.js'); ?>"></script>
		<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/fc-planner-summary.js'); ?>"></script>
		<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/p2.js'); ?>"></script>
		<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/fc-project-plan-color-slick.js'); ?>"></script>

		<script type="text/javascript">
		(function() {
			function fcProjectPlanClearEditDetailsLoadingCursor() {
				document.body.classList.remove('fc-project-plan-page-loading');
			}
			if (document.readyState === 'complete') {
				fcProjectPlanClearEditDetailsLoadingCursor();
			} else {
				window.addEventListener('load', fcProjectPlanClearEditDetailsLoadingCursor);
			}
		})();
		</script>

	</body>
</html>