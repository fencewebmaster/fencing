<?php
	session_start();
	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

	include('data/settings.php');
	include('temp/fields.php');
	include('helpers.php');
	
	// unset($_SESSION['fc_cart']);
    
    /*
 	$products = load_csv('data/products.csv');
	$key = array_search('slug', array_column($products, 'gate'));
	dd( $products[$key] );
	*/

	date_default_timezone_set('Asia/Manila');
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">

<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">
<script src="https://js.stripe.com/v3/" async></script>

<style type="text/css">
.fc-form-group {
	position: relative;
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

					<a href=".?step=4" class="fencing-tab fc-d-none">
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

						<div class="fc-card fc-project-details">

							<div id="update_details-section">

								<div class="fc-row-container">
									<div class="fc-col-half">
										<div class="fc-table-rounded-border fc-mb-2 fc-position-relative">
										
											<table class="fc-table fc-table-customer">
												<tbody>
													<tr>
														<td width="100">Name</td>
														<td>
															<span><?php echo @$info['name']; ?></span>
															<div class="fc-form-group">
																<input type="text" name="name" value="<?php echo @$info['name']; ?>" class="fc-form-control fc-form-control-sm">
															</div>
														</td>
													</tr>
													<tr>
														<td>Contact</td>
														<td>
															<span><?php echo @$info['mobile']; ?></span>
															<div class="fc-form-group">
																<input type="text" name="mobile" value="<?php echo @$info['mobile']; ?>" class="fc-form-control fc-form-control-sm">
															</div>
														</td>
													</tr>
													<tr>
														<td>Email</td>
														<td>
															<span><?php echo @$info['email']; ?></span>
															<div class="fc-form-group">
																<input type="email" name="email" value="<?php echo @$info['email']; ?>" class="fc-form-control fc-form-control-sm no-space" required>
															</div>
														</td>
													</tr>
													<tr>
														<td>State</td>
														<td>
															<span><?php echo @$info['state'] ? fc_state($info['state']) : ''; ?></span>
															<div class="fc-form-group">
																<select name="state" class="fc-form-control fc-form-control-sm" required>
																	<option value="">Select an option…</option>
																	<?php foreach( fc_state() as $state_k => $state_v ): ?>
																	<option value="<?php echo $state_k; ?>" <?php echo @$info['state']==$state_k ? 'selected': ''; ?>><?php echo $state_v; ?></option>
																	<?php endforeach; ?>
																</select>
															</div>
														</td>
													</tr>
													<tr>
														<td>Post Code</td>
														<td>
															<span><?php echo @$info['postcode']; ?></span>
															<div class="fc-form-group">
																<input type="text" name="postcode" value="<?php echo @$info['postcode']; ?>" class="fc-form-control fc-form-control-sm">
															</div>
														</td>
													</tr>
													<tr>
														<td>Address</td>
														<td>
															<span><?php echo @$info['address']; ?></span>
															<div class="fc-form-group">
																<input type="text" name="address" value="<?php echo @$info['address']; ?>" class="fc-form-control fc-form-control-sm">
															</div>
														</td>
													</tr>
												</tbody>
											</table>

										</div>

										<div class="fc-table-rounded-border fc-mb-2">
										
											<table class="fc-table">
												<tr>
													<td>Fence Type</td>
													<td>Flat Top Pool Fencing, Glass Pool Fencing</td>
												</tr>
												<tr>
													<td>When Needed</td>
													<td><?php echo @$info['timeframe'] ? fc_timeframe(@$info['timeframe']) : ''; ?></td>
												</tr>
												<tr>
													<td>Install Required</td>
													<td><?php echo @$info['installer'] ? fc_installer(@$info['installer']) : ''; ?></td>
												</tr>
												<tr>
													<td>Other Items Needed</td>
													<td><?php echo @$info['extra'] ? get_items('fc_extra_needed', $info['extra']) : ''; ?></td>
												</tr>
											</table>

										</div>

										<div class="js-project-details-controls fc-d-none">
											<button type="button" data-action="update" class="btn-fc fc-btn-edit btn-fc-outline-light fc-text-uppercase btn-fc-orange fc-w-700 fc-float-r" style=" margin-left: 16px;">
												<i class="fa-solid fa-pencil"></i>
												<span>Update</span>
											</button>
											<button type="button" 
												class="btn-fc fc-btn-reset btn-fc-outline-light fc-text-uppercase btn-fc-orange fc-w-700 fc-float-r" 
												style="display:none;">
													<i class="fa-solid fa-rotate-left"></i> <span>Reset</span>
											</button>
										</div>

									</div>

									<div class="fc-col-half">
										<div class="fc-card fc-mb-2">
											
											<div class="fc-card-header fc-bg-dark fc-border-top">
												Flat Top Pool Fencing - Options
											</div>

											<div class="fc-table-rounded-border fc-rounded-top-none fc-mb-2">
											
												<table class="fc-table fc-table--colour">
													<thead>
														<tr>
															<td width="100" class="valign-top">Colour</td>
															<td>
																<div style="background:<?php echo @$info['color']->code; ?>;color:<?php echo @$info['color']->text_color; ?>;border:	2px solid var(--fc-gray);max-width:250px;" class="fc-colour-item fc-border fc-p-1 js-color_options-color_code">
																	<br>   
																	<p style="color: <?php echo @$info['color']->text_color; ?>"><strong class="js-color_options-title"><?php echo @$info['color']->title; ?></strong><br />
																	<span class="js-color_options-subtitle"><?php echo @$info['color']->subtitle; ?></span></p>
																</div>
															</td>
														</tr>
													</thead>
												</table>

											</div>
										</div>

										<div class="fc-card">
										
											<div class="fc-card-header fc-bg-dark fc-border-top">
												Project Notes & Additional Details
											</div>
											
											<div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
												<div class="fc-p-1">
													<textarea name="notes" 
														placeholder="Write your notes here" 
														class="fc-form-control" 
														rows="5"><?php echo @$info['notes']; ?></textarea>
												</div>
											</div>

										</div>

									</div>
								</div>
							</div>
						</div>
						<!-- END PROJECT DETAILS -->
					</div>


		            <!-- START PROJECT PLAN -->
					<div class="fencing-section fencing-section--has-border">
						<div class="fc-card">

						<!-- [START] Label -->
						<div class="fencing-section__cmp fencing-section__step-label">
							<div class="step-label">Project <span>Plans</span></div>
							<div>
								
							</div>
						</div>
						<!-- [END] Label -->
							
							<div>
								<div id="fc-fence-list" >
									<?php include 'data/plan-item.php'; ?>
								</div>
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
											<?php include('temp/cart-table.php'); ?>
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

											<?php $alert = array_search('yes', array_column($cart['items'], 'stock')); ?>

											<p>Items in Stock: <span class="fc-stock-status fc-stock-status--inline fc-stock-status--yes">Yes</span></p>

											<?php $alert = array_search('low', array_column($cart['items'], 'stock')); ?>

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


	</form>
	<!-- END CHECKOUT FORM -->


</div>
<!-- END FENCING CONTAINER -->


<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/checkout.js?v=<?php echo date('YmdHis'); ?>"></script>

<script type="text/javascript">
var setcountDownDate = "<?php echo date('M d, Y H:i:s', strtotime('+3 hours')); ?>";	
var getcountDownDate = localStorage.getItem('countdown-date');

if( ! getcountDownDate ) {
	// Set the date we're counting down to
	localStorage.setItem('countdown-date', setcountDownDate);
	
	var getcountDownDate = localStorage.getItem('countdown-date');
}

</script>

<script type="text/javascript">
var countDownDateFormat = new Date(getcountDownDate).getTime(),
	cont = 'fc-countdown-timer';

	// Update the count down every 1 second
	var x = setInterval(function() {

  // Get today's date and time
  var now = new Date().getTime();

  // Find the distance between now and the count down date
  var distance = countDownDateFormat - now;

  // Time calculations for days, hours, minutes and seconds
  var days 	  = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours   = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Display the result in the element with id="demo"
  document.getElementById(cont).innerHTML = hours + "hrs " + minutes + "mins " + seconds + "secs ";

  // If the count down is finished, write some text
  if (distance < 0) {
	clearInterval(x);
	document.getElementById(cont).innerHTML = "Limited time offer...<br> HURRY UP!";
	localStorage.removeItem('countdown-date');
  }

}, 1000);	
</script>