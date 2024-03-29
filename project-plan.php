<?php
	session_start();
	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
    $cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];

	if( empty($info) ) {
		header("Location: ./");
		die();
	}

	date_default_timezone_set('Asia/Manila');

	include 'data/settings.php';
	include 'views/fields.php';
	include 'config/helpers.php';

	$site_info = sites($_SESSION['site']['id'], 'id', true);
?>

<!DOCTYPE html>
<html>
	<head>
	<?php include 'views/partials/head.php'; ?>
	</head>
	<body>

		<!-- [START] FENCING CONTAINER -->
		<div id="place_order-section" class="fencing-container container-lg fc-project-plan fc-position-relative mt-5" data-tab="1">

			<!-- [START] CHECKOUT FORM -->
			<form method="POST" id="paymentFrm" action="<?php echo base_url('checkout.php'); ?>">

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
							<div class="quote-id-card float-end">
								<div class="qic-head px-3">Your Quote ID</div>
								<div class="qic-body btn-copy-link" data-id="quote-id-1">
									<div id="quote-id-1"><?php echo @$_SESSION['planner_id']; ?></div>
								</div>
							</div>
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
		var base_url = '<?php echo base_url(); ?>';	
		</script>

		<?php include 'views/partials/footer.php'; ?>	

		<script type="text/javascript" src="<?php echo load_file('assets/js/checkout.js'); ?>"></script>
		<script type="text/javascript" src="<?php echo load_file('assets/js/p2.js'); ?>"></script>

	</body>
</html>