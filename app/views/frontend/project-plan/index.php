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

use Fc\Admin\Services\ConsoleSettings;
use Fc\Admin\Services\PlannerSessionService;

// Field templates must be in the DOM before p2.js runs
include view_path('frontend.partials.fields.index');
?>

<!DOCTYPE html>
<html data-fc-debug="<?php echo ConsoleSettings::debugMode() ? '1' : '0'; ?>">
	<head>
	<?php include view_path('frontend.partials.head'); ?>
	<script>window.FC_DEBUG = document.documentElement.getAttribute('data-fc-debug') === '1';</script>
	</head>
	<body class="fc-project-plan-page fc-project-plan-page-loading">

		<?php include view_path('frontend.partials.body-before'); ?>

		<!-- [START] FENCING CONTAINER -->
		<div id="place_order-section" class="fencing-container container-lg fc-project-plan fc-position-relative mt-5" data-tab="1">

			<!-- [START] CHECKOUT FORM -->
			<form method="POST" id="paymentFrm" action="<?php echo url('checkout'); ?>">

				<input type="hidden" name="action" value="">

				<!-- [START] PAGE HEADER TITLE -->
		        <div class="fencing-container__header">
		            <div class="row align-items-center">

		                <div class="col-sm">
		                	<div class="mb-3 mb-sm-0">
		                		<?php include view_path('frontend.partials.header-left'); ?>
		                	</div>
		                </div>
		                <div class="col-sm-auto col-auto">
							<?php
							$quote_id_dom_id = 'quote-id-1';
							$quote_card_class = 'float-end';
							include view_path('frontend.partials.quote-id-card');
							?>
		                </div>

		            </div>
		        </div>
				<!-- [END] PAGE HEADER TITLE -->

				<?php include view_path('frontend.project-plan.header-tabs'); ?>

				<!-- [START] FENCING CONTENT -->
				<div class="fencing-content fc-font-1">

				    <div class="fc-section-step">

				        <div>

				        	<?php include view_path('frontend.project-plan.project-details'); ?>

				        	<?php include view_path('frontend.project-plan.project-plans'); ?>

				        	<?php include view_path('frontend.project-plan.item-list-cart'); ?>

				        </div>
				    </div>
				</div>
				<!-- [END] FENCING CONTENT -->

		    	<?php include view_path('frontend.partials.modal.submit.view-2'); ?>


			</form>
			<!-- [END] CHECKOUT FORM -->

		</div>
		<!-- [END] FENCING CONTAINER -->

		<?php include view_path('frontend.project-plan.modals'); ?>

		<!-- Config -->
		<script type="text/javascript">
		var fc_data  = <?php echo json_encode($fences); ?>;
		var base_url = '<?php echo url(); ?>';
		var fc_fence_info = <?php echo json_encode( $fc_fence_info, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
		var planner_id = <?php echo json_encode((string) (@$_SESSION['planner_id'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP); ?>;
		var planner_share_url = "<?php echo PlannerSessionService::qidShareUrl(); ?>";
		</script>

		<?php include view_path('frontend.partials.footer'); ?>

		<script defer src="<?php echo asset('public/assets/js/vendor/modern-screenshot.js'); ?>"></script>
		<script defer src="<?php echo asset('public/assets/js/vendor/html2canvas.min.js'); ?>"></script>
		<script defer src="<?php echo asset('public/assets/js/vendor/jspdf.umd.min.js'); ?>"></script>
		<script defer src="<?php echo asset('public/assets/js/frontend/project-plan/checkout.js'); ?>"></script>
		<script defer src="<?php echo asset('public/assets/js/frontend/shared/cart-items.js'); ?>"></script>
		<script defer src="<?php echo asset('public/assets/js/frontend/shared/fc-planner-summary.js'); ?>"></script>
		<script defer src="<?php echo asset('public/assets/js/frontend/project-plan/p2.js'); ?>"></script>
		<script defer src="<?php echo asset('public/assets/js/frontend/project-plan/fc-project-plan-color-slick.js'); ?>"></script>

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
