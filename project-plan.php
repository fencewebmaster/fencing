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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<!-- START FORM SUBMISSION LOADER -->
<div class="fc-loader-overlay" style="display: none;">
    <div class="fc-loader-container">
        <div class="fc-loader">
            <div class="fc-row">
                
                <div class="fc-col-auto">
				<div class="fc-loader-gif"></div>
                </div>

                <div class="fc-col-auto">
                    
                    <ul></ul>

                </div>

            </div>
        </div>
    </div>
</div>
<!-- END FORM SUBMISSION LOADER -->


<!-- START FENCING CONTAINER -->
<div id="place_order-section" class="fencing-container container-lg fc-project-plan fc-position-relative mt-5" data-tab="1">

	<!-- START CHECKOUT FORM -->
	<form method="POST" id="paymentFrm" action="<?php echo base_url('checkout.php'); ?>">

		<input type="hidden" name="action" value="">

		<!-- START PAGE HEADER TITLE -->
        <div class="fencing-container__header">
            <div class="row align-items-center">

                <div class="col-sm">
                	<div class="mb-3 mb-sm-0">
                		<?php include 'temp/header-left.php'; ?>     
                	</div>
                </div>
                <div class="col-sm-auto col-auto">
					<div class="quote-id-card float-end">
						<div class="qic-head px-3">Your Quote ID</div>
						<div class="qic-body btn-copy-link px-3" data-id="quote-id-1">
							<div id="quote-id-1"><?php echo @$_SESSION['planner_id']; ?></div>
						</div>
					</div>
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
        <div class="fencing-tabs-container fencing-tabs-container--project-plan fc-section-step fc-d-none fc-font-2 mt-3" data-tab="1">
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
								
								<button type="button" data-action="edit" class="btn btn-sm fc-btn-edit btn-orange text-uppercase">
									<i class="fa-regular fa-pen-to-square me-1"></i>
									<span>Edit Details</span>
								</button>

								<div class="js-project-details-controls project-details-controls fc-d-none">
									
									<button type="button" data-action="update" class="btn btn-sm fc-btn-edit btn-orange text-uppercase btn-orange fc-w-700">
										<i class="fa-regular fa-pen-to-square me-1"></i>
										<span>Save</span>
									</button>

									<button type="button" 
										class="btn btn-sm fc-btn-reset btn-secondary text-uppercase" 
										style="display:none;">
											<i class="fa-solid fa-rotate-left"></i> <span>Reset</span>
									</button>

								</div>

							</div>
						</div>
						<!-- [END] Step 5 | Edit Controls -->

						<div class="your-project-details">
							<?php include 'temp/sections/your-project-details.php'; ?>						
						</div>

						<!-- END PROJECT DETAILS -->
					</div>


		            <!-- START PROJECT PLAN -->
					<div id="project-plans-section" class="fencing-section fencing-section--has-border p-3 pb-0">
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
								<div id="fc-fence-list" class="pb-1"></div>
							</div>

						</div>
						<!-- END PROJECT PLAN -->
					</div>

																		
		            <!-- START PRODUCT LIST -->
					<div class="fc-cart-items">
						<div class="fc-card fc-mb-3">

							<div id="update_cart-section" class="fencing-section fencing-section--has-border">

									<div class="row">

										<div class="col-lg-7 col-md-8 col-sm-12 fc-position-relative order-md-1 order-2" id="update_cart-list">
										    <!-- [START] Label -->
									        <div class="row align-items-center mb-2">
									        	<div class="col-sm col">
									        		<div class="step-label">Item List & <span>Cart</span></div>

									        	</div>
									        	<div class="col-sm col-auto text-end">
										        		
										        	<a href="" class="btn btn-orange fc-edit-item text-uppercase js-fc-edit-item fw-bold">
										            	<i class="fa-regular fa-pen-to-square me-1"></i> <span>Edit</span>
										            </a>

										            <a href="" class="btn btn-secondary fc-reset-item fw-bold text-uppercase" style="display: none;">
										            	<i class="fa-solid fa-rotate-left me-1"></i> Reset</a>

									        	</div>
									        </div>
										    <!-- [END] Label -->

										    <div class="fc-card fc-table-items">
										        <?php include('temp/sections/cart-table.php'); ?>
										    </div>

											<div class="d-grid gap-2">
										        <button type="submit" class="btn btn-lg btn-submit btn-orange text-uppercase w-100 text fw-bold">
										            <i class="fa-solid fa-cart-shopping me-1"></i>
										            Order Items Now!
										        </button>
									    	</div>

										    <div class="row d-none">
										    	<div class="col-sm ps-1">
											        <button type="button" class="btn btn-lg fc-btn-download-fence btn-outline-dark text-uppercase w-100 fs-6 text">
											            <i class="fa-solid fa-download me-1"></i>
											            <span>Download Plans</span>
											        </button>										    		
										    	</div>

										    </div>

										</div>

										<div class="col fc-position-relative order-1 mb-md-0 mb-4" id="update_stock-delivery">

											<div data-spy="scroll" data-screen="768" data-offset="20" data-target="#update_stock-delivery">
												<div class="fencing-section__cmp fencing-section__step-label">
											        <div class="step-label">Stock & <span>Delivery</span></div>
											    </div>
									
												<div class="fc-cart-stock-area">
												    <p>Approx Delivery Run: <span>2-3 Days</span></p>

												    <p>Items in Stock: <span class="fc-stock-status fc-stock-status--inline fc-stock-status--yes">Yes</span></p>

												    <div class="alert alert-danger fc-step-2-alert fc-alert-gray--low-stock">
												        <h3 class="fc-mb-1"><i class="fc-icon fc-icon-info"></i> Low Stock Warning</h3>
												        <p class="fc-text-red">
												            Some items have limited stock available. <br />
												            Your cart can only be Reserved for a Limited Time <br />
												            Then its released for other customers.
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
												
												<div class="d-grid gap-2">

											        <button type="submit" class="btn btn-submit btn-lg btn-dark text-uppercase w-100 fs-6 text fw-bold">
											            <i class="fa-solid fa-cart-shopping me-2"></i>
											            Order Items Now!
											        </button>
										
											        <button type="button" class="btn btn-lg btn-secondary text-uppercase w-100 fs-6 text">
											            <i class="fa-regular fa-comments me-2"></i>
											            Speak To Trade Support
											        </button>										    		
								
												</div>

											</div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>

<!-- Scripts -->
<script type="text/javascript" src="js/main.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/modal.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/checkout.js?v=<?php echo date('YmdHis'); ?>"></script>

<script type="text/javascript" src="js/calc.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/p2.js?v=<?php echo date('YmdHis'); ?>"></script>

