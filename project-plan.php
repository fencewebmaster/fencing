<?php
	session_start();
	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
    $cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];

	if( empty($info) ) {
		header("Location: ./");
		die();
	}

	date_default_timezone_set('Asia/Manila');

	include('data/settings.php');
	include('temp/fields.php');
	include('helpers.php');

	// unset($_SESSION['fc_cart']);

?>

<title>Fencing Calculator</title>
<link rel="icon" type="image/x-icon" href="img/fav.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">
<link rel="stylesheet" type="text/css" href="style-v2.css?v=<?php echo date('YmdHis'); ?>">

<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">
<script src="https://js.stripe.com/v3/" async></script>

<style type="text/css">
.fc-form-group {
	position: relative;
}	
@media print { 
     .btn-fc { display: none !important; } 
    }
</style>

<!-- START FORM SUBMISSION LOADER -->
<div class="fc-loader-overlay" style="display: none;">
    <div class="fc-loader-container">
        <div class="fc-loader">
            <div class="fc-row">
                
                <div class="fc-col-auto">
				<div class="fc-loader-gif"></div>
                </div>

                <div class="fc-col-auto">
                    
                    <ul>
                        <li>
                            <div class="fc-mb-1"><small style="font-size:30px;">Preparing:</small></div>
                        </li>
                        <li><i class="fa fa-check fc-mr-1"></i> Checking customer details... </li>
                        <li><i class="fa fa-check fc-mr-1"></i> Pushing order into cart...</li>
                        <li><i class="fa fa-check fc-mr-1"></i> Redirecting to fencing website...</li>
                        <li></li>
                    </ul>

                </div>

            </div>
        </div>
    </div>
</div>
<!-- END FORM SUBMISSION LOADER -->



<!-- START FENCING CONTAINER -->
<div id="place_order-section" class="fencing-container fc-project-plan fc-position-relative" data-tab="1">

	<!-- START CHECKOUT FORM -->
	<form method="POST" id="paymentFrm" action="<?php echo base_url('checkout.php'); ?>">

		<input type="hidden" name="action" value="">

		<!-- START PAGE HEADER TITLE -->
		<div class="fc-mb-2">
		    <div class="fc-row">
		        <div class="fc-col-half fc-lg-col-full">
		            <h2 class="fc-header-title">Fencing Calculator</h2>
		            <p>Calculate your fence cost and the materials needed.</p>
		        </div>
		    </div>
		</div>
		<!-- END PAGE HEADER TITLE -->


		<!-- START SECTION TABS -->
		<!-- <div class="fc-header-tab fc-section-step fc-font-2">
			<div class="fc-header-tab__area">
				<a href=".?step=3">SECTION DETAILS</a>
				<a href=".?step=4">Project Options</a>	
				<a href="#" class="fc-tab-active tab-selected">Project Plan & Cart</a>
			</div>
		</div> -->
		<!-- END SECTION TABS -->

		<!-- START TABS -->
        <div class="fencing-tabs-container fencing-tabs-container--project-plan fc-section-step fc-d-none fc-font-2" data-tab="1">
            <div class="fencing-tabs-area">
                <div class="fencing-tabs fc-row-flex">
                
                <div class="fencing-tab-container fc-row-flex">
                    
                    <a href=".?step=3" class="fencing-tab fc-d-none">
                        <div class="fencing-tab-name">
                            <span class="ftm-title">Section Details</span> 
                        </div>
					</a>

					<a href=".?tab=2" class="fencing-tab fc-d-none">
                        <div class="fencing-tab-name">
                            <span class="ftm-title">Project Options</span> 
                        </div>
					</a>

					<a href="#" class="fencing-tab  fencing-tab-selected fc-d-none">
                        <div class="fencing-tab-name">
                            <span class="ftm-title">Project Plan & Cart</span> 
                        </div>
					</a>

                </div>

            </div>
            </div>
        </div>
        <!-- END TABS -->


		<!-- START FENCING CONTENT -->
		<div class="fencing-content fc-font-1">

		    <div class="fc-section-step">

		        <div class="">

		        	<!-- START PROJECT DETAILS -->
					<div class="fencing-section fencing-section--has-border br-tl-0 br-tr-md-0">

						<!-- [START] Step 5 | Edit Controls -->
						<div class="fencing-section__cmp fencing-section__step-label">
							<div class="step-label">Your Project <span> Details</span></div>

							<div>
								<button type="button" data-action="edit" class="btn-fc fc-btn-edit btn-fc-outline-light fc-text-uppercase btn-fc-orange fc-w-700 fc-float-r" style=" margin-left: 16px;">
									<i class="fa-solid fa-pencil"></i>
									<span>Edit Details</span>
								</button>
							</div>
						</div>
						<!-- [END] Step 5 | Edit Controls -->

						<div class="your-project-details">
							<?php include 'temp/sections/your-project-details.php'; ?>						
						</div>

						<!-- END PROJECT DETAILS -->
					</div>


		            <!-- START PROJECT PLAN -->
					<div class="fencing-section fencing-section--has-border">
						<div class="fc-card">

							<div class="fc-row-flex">
								<div class="fc-col-6">
									
																<!-- [START] Label -->
							<div class="fencing-section__cmp fencing-section__step-label">
								<div class="step-label">Project <span>Plans</span></div>
							</div>
							<!-- [END] Label -->

								</div>
								<div class="fc-col-6 fc-flex-end">
									
									<div class="quote-id-card">
										<div class="qic-head">Your Quote ID</div>
										<div class="qic-body btn-copy-link" data-id="quote-id">
											<div id="quote-id"><?php echo @$_SESSION['planner_id']; ?></div>
										</div>
									</div>

								</div>
							</div>


							
							<?php // include 'data/plan-item.php'; ?>

							<div>
								<div id="fc-fence-list"></div>
							</div>

						</div>
						<!-- END PROJECT PLAN -->
					</div>

																		
		            <!-- START PRODUCT LIST -->
					<div class="fc-cart-items">
						<div class="fc-card fc-mb-3">

							<style type="text/css">
							.fc-card-body {
								position: relative;	
							}
							.fc-card-body .fc-loader-container {
								height: 100%;
							}
							.fc-section-loader-overlay {
								position: absolute;
								background: #000000e0;
								top: 0;
								left: 0;
								right: 0;
								bottom: 0;
								z-index: 2;
							}	
							.project-details--editable:hover {
								cursor: pointer;
								background: #f6f6f6;
							}
							.fc-result {
								position: relative;
							}
							.fc-overall {
								margin-left: auto;
								position: initial;
								text-align: center;
							}

							.cp_no-post--left:before,
							.cp_no-post--left .fc-div-c-p,
							.cp_no-post--left .fc-start-c-p, 
							.cp_no-post--right:after,
							.cp_no-post--right .fc-div-c-p:nth-child(2),
							.cp_no-post--right .fc-end-c-p
							{
								color: #f70000;
							}
							.panel-post {

							}
							.fencing-panel-spacing-number.opt-2 span {
								    bottom: -5px;
							}
							.panel-post:not(.panel-no-post).opt-2 {
								margin-bottom: 15px;
							}
							.panel-post:not(.panel-no-post).opt-2:after {
								bottom: 15px;
							}
							.qty-edited {
								position: absolute;
								left: -10px;
								top: calc(50% - 12px);
								font-size: 12px;
								background: #fff;
								padding: 5px;
								border-radius: 100%;
								border: 1px solid #dddddd;
								color: #8e8e8e;
							}
							</style>

							<div id="update_cart-section">

								<div class="fc-row-container fc-row-container--cart-stock">
									<div class="fc-col-half fc-position-relative" id="update_cart-list">
										<!-- [START] Label -->
										<div class="fencing-section__cmp fencing-section__step-label">
											<div class="step-label">Item List & <span>Cart</span></div>
											<div>
												<a href="" class="fc-edit-item js-fc-edit-item"><i class="fa-solid fa-pencil"></i> <span>Edit</span></a>
        										<a href="" class="fc-reset-item" style="display: none;">Reset</a>
											</div>
										</div>
										<!-- [END] Label -->
										<div class="fc-card fc-table-items">          		
											<?php include('temp/sections/cart-table.php'); ?>
										</div>

										<div class="fc-cart-items-btns">
											<button type="submit" 
												class="btn-fc btn-submit fc-btn-md btn-fc-orange fc-text-uppercase fc-mb-1 fc-w-700 w-100-sm">
												<i class="fa-solid fa-cart-shopping"></i>
												Order Items Now!
											</button>
											<button type="button" 
												class="btn-fc fc-btn-download-fence btn-fc-outline-light fc-text-uppercase fc-w-700 fc-float-r">
												<i class="fa-solid fa-download"></i>
												<span>Download Plans</span>
											</button>
										</div>

									</div>
									
									<div class="fc-col-half fc-position-relative" id="update_stock-delivery">
										<!-- [START] Label -->
										<div class="fencing-section__cmp fencing-section__step-label">
											<div class="step-label">Stock & <span>Delivery</span></div>
											<div>
											</div>
										</div>
										<!-- [END] Label -->
										<!-- START SHIPPING OPTION -->
										<h4 class="fc-mb-2  fc-d-none">Pick-Up / Delivery</h4>

										<div class="fc-form-group fc-form-check fc-mb-3 " style="display: none !important;">

											<?php foreach( fc_deliver_options() as $delivery_option ): ?>
											<?php 						
												$default_shipping_type = '';					
												if( isset($cart['shipping_type']) ) {
													if( $cart['shipping_type'] == $delivery_option['value'] ) {
														$default_shipping_type = 'checked';
													}
												} else {
													$default_shipping_type = $delivery_option['default'] ? 'checked' : '';
												}
											?>
											<label class="fc-mb-1">
												<input type="radio" name="cart[shipping_type]" class="fc-mr-1" value="<?php echo $delivery_option['value']; ?>" <?php echo $default_shipping_type; ?> required>
												<?php echo $delivery_option['label']; ?>
											</label>
											<?php endforeach; ?>

										</div>
										<!-- END SHIPPING OPTION -->


										<!-- START SRTIPE PAYMENT -->
										<h4 class="fc-mb-3 fc-d-none">Pay with creditcard</h4>

										<div class="select-cards  fc-d-none">
											<div class="fc-label-group fc-mb-1">
												<label class="fc-form-label">Card Number <span class="fc-text-danger">*</span></label>
												<div id="card_number" class="fc-form-control form-control-lg rounded-0"></div>
											</div>
											<div class="fc-row-container">
												<div class="fc-col-half">
													<div class="fc-label-group">
														<label class="fc-form-label">Expiry Date <span class="fc-text-danger">*</span></label>
														<div id="card_expiry" class="fc-form-control form-control-lg rounded-0"></div>
													</div>
												</div>
												<div class="fc-col-half">
													<div class="fc-label-group">
														<label class="fc-form-label">CVC Code <span class="fc-text-danger">*</span></label>
														<div id="card_cvc" class="fc-form-control form-control-lg rounded-0"></div>
													</div>
												</div>
											</div>
											<!-- Display errors returned by createToken -->
											<div id="paymentResponse" class="fc-text-danger fc-mb-1"></div>
										</div>
										<!-- END STRIPE PAYMENT -->

										<div class="fc-cart-stock-area">
												
											<p>Approx Delivery Run: <span>2-3 Days</span></p>

											<p>Items in Stock: <span class="fc-stock-status fc-stock-status--inline fc-stock-status--yes">Yes</span></p>

											<div class="fc-alert-gray fc-step-2-alert fc-alert-gray--low-stock">
												<h3 class="fc-mb-1"><i class="fc-icon fc-icon-info"></i> Low Stock Warning</h3>
												<p class="fc-text-red">Some items have limited stock available. <br />Your cart can only be Reserved for a Limited Time <br />Then its released for other customers.
												</p>
											</div>
											

										</div>

										<div style="clear: both;"></div>
										
										<div class="fc-cart-countdown">
											<p>
												<strong>ORDER WITHIN:</strong><br />
												<span id="fc-countdown-timer"></span>
											</p>
										</div>

										<div class="fc-cart-items-btns">
											<button type="submit" 
												class="btn-fc btn-fc--large-text btn-submit fc-btn-md btn-fc-black fc-text-uppercase fc-mb-1 fc-w-700 w-100-sm">
												Order Items Now!
											</button>
											<button type="button" 
												class="btn-fc btn-fc-outline-light fc-text-uppercase fc-w-700 fc-float-r">
												<span>Speak To Trade Support</span>
											</button>
										</div>

									</div>

								</div>


							</div>
						</div>
					</div>
		            <!-- END PRODUCT LIST -->


		        </div>
		    </div>
		</div>
		<!-- END FENCING CONTENT -->

    	<?php include 'temp/modal/submit/view-2.php'; ?>


	</form>
	<!-- END CHECKOUT FORM -->


</div>
<!-- END FENCING CONTAINER -->

<!-- Config -->
<script type="text/javascript">
var fc_data = <?php echo json_encode($fences); ?>;
var base_url = '<?php echo base_url(); ?>';	
</script>

<!-- Required Libraries -->
<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

<!-- Scripts -->
<script type="text/javascript" src="js/main.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/modal.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/checkout.js?v=<?php echo date('YmdHis'); ?>"></script>

<script type="text/javascript" src="js/calc.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/p2.js?v=<?php echo date('YmdHis'); ?>"></script>

