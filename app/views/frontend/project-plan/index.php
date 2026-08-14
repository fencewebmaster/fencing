<?php
/**
 * Project plan page (/project-plan).
 *
 * Rendered by Controllers\Frontend\ProjectPlanController.
 *
 * @var array      $fences        Fence catalog (FenceSettingsService).
 * @var array      $info          Session fc_data.
 * @var array      $cart          Session fc_cart.
 * @var array|null $site_info     Site registry row for the session's site.
 * @var object     $fc_fence_info JS `fc_fence_info` payload.
 */

// Field templates are emitted ahead of the document, exactly as the legacy entry script
// did — p2.js reads them out of the DOM by data-type, so the position is not significant
// but the presence of every template before the scripts run is.
include __DIR__ . '/../fields.php';
?>

<!DOCTYPE html>
<html data-fc-debug="<?php echo \Fc\Admin\Services\ConsoleSettings::debugMode() ? '1' : '0'; ?>">
	<head>
	<?php include __DIR__ . '/../partials/head.php'; ?>
	<script>window.FC_DEBUG = document.documentElement.getAttribute('data-fc-debug') === '1';</script>
	</head>
	<body class="fc-project-plan-page fc-project-plan-page-loading">

		<?php include __DIR__ . '/../partials/body-before.php'; ?>

		<!-- [START] FENCING CONTAINER -->
		<div id="place_order-section" class="fencing-container container-lg fc-project-plan fc-position-relative mt-5" data-tab="1">

			<!-- [START] CHECKOUT FORM -->
			<form method="POST" id="paymentFrm" action="<?php echo \Fc\Admin\Helpers\UrlHelper::baseUrl('checkout'); ?>">

				<input type="hidden" name="action" value="">

				<!-- [START] PAGE HEADER TITLE -->
		        <div class="fencing-container__header">
		            <div class="row align-items-center">

		                <div class="col-sm">
		                	<div class="mb-3 mb-sm-0">
		                		<?php include __DIR__ . '/../partials/header-left.php'; ?>
		                	</div>
		                </div>
		                <div class="col-sm-auto col-auto">
							<?php
							$quote_id_dom_id = 'quote-id-1';
							$quote_card_class = 'float-end';
							include __DIR__ . '/../partials/quote-id-card.php';
							?>
		                </div>

		            </div>
		        </div>
				<!-- [END] PAGE HEADER TITLE -->

				<?php include __DIR__ . '/header-tabs.php'; ?>

				<!-- [START] FENCING CONTENT -->
				<div class="fencing-content fc-font-1">

				    <div class="fc-section-step">

				        <div>

				        	<?php include __DIR__ . '/project-details.php'; ?>

				        	<?php include __DIR__ . '/project-plans.php'; ?>

				        	<?php include __DIR__ . '/item-list-cart.php'; ?>

				        </div>
				    </div>
				</div>
				<!-- [END] FENCING CONTENT -->

		    	<?php include __DIR__ . '/../modal/submit/view-2.php'; ?>


			</form>
			<!-- [END] CHECKOUT FORM -->

		</div>
		<!-- [END] FENCING CONTAINER -->

		<?php include __DIR__ . '/modals.php'; ?>

		<!-- Config -->
		<script type="text/javascript">
		var fc_data  = <?php echo json_encode($fences); ?>;
		var base_url = '<?php echo \Fc\Admin\Helpers\UrlHelper::baseUrl(); ?>';
		var fc_fence_info = <?php echo json_encode( $fc_fence_info, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
		var planner_id = "<?php echo @$_SESSION['planner_id']; ?>";
		var planner_share_url = "<?php echo \Fc\Admin\Services\PlannerSessionService::qidShareUrl(); ?>";
		</script>

		<?php include __DIR__ . '/../partials/footer.php'; ?>

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
