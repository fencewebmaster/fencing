<?php
	session_start();
	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

	include('data/settings.php');
	include('temp/fields.php');
	include('helpers.php');

	$cart = [
		'items' => [
			[
				'qty' 			=> 10,
				'description' 	=> 'Panels - 2400W',
				'sku' 			=> 'sku1',
				'rrp' 			=> 129,
				'trade_price' 	=> 89.30,
				'subtotal' 	    => 893,
			],
			[
				'qty' 			=> 10,
				'description' 	=> 'Bracket',
				'sku' 			=> 'sku2',
				'rrp' 			=> 14.25,
				'trade_price' 	=> 12.10,
				'subtotal' 	    => 120,
			],
			[
				'qty' 			=> 12,
				'description' 	=> 'Post - 1300L',
				'sku' 			=> 'sku3',
				'rrp' 			=> 50.90,
				'trade_price' 	=> 39.00,
				'subtotal' 	    => 468,
			],
			[
				'qty' 			=> 14,
				'description' 	=> 'Base Plate - Covers',
				'sku' 			=> 'sku4',
				'rrp' 			=> 16.95,
				'trade_price' 	=> 9.50,
				'subtotal' 	    => 133,
			],
			[
				'qty' 			=> 15,
				'description' 	=> 'Fixing Kits',
				'sku' 			=> 'sku5',
				'rrp' 			=> 14.95,
				'trade_price' 	=> 12.80,
				'subtotal' 	    => 192,
			],
		],
		'subtotal' 		 => 1806,
		'trade_discount' => 584.50,
		'shipping_type'  => 'shipping_2',
		'delivery_fee'   => 89,
		'gst' 			 => 180.6,
		'total' 		 => 2075.6,
	];

	// $_SESSION['fc_cart'] = $cart;

	$cart = $_SESSION['fc_cart'];

	// dd($cart );
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

<!-- START FENCING CONTAINER -->
<div class="fencing-container fc-project-plan" data-tab="1">
	

	<!-- START CHECKOUT FORM -->
	<form method="POST" id="paymentFrm" action="<?php echo base_url('checkout.php'); ?>">

		<input type="text" name="action" value="update_cart">

		<!-- START PAGE HEADER TITLE -->
		<div class="fc-mb-2">
		    <div class="fc-row">
		        <div class="fc-col-half">
		            <h2 class="fc-header-title">Fencing Calculator</h2>
		            <p>Calculate your fence cost and the materials needed.</p>
		        </div>
		        <div class="fc-col-half">
		            <div class="fc-flex-end">
		            </div>
		        </div>
		    </div>
		</div>
		<!-- END PAGE HEADER TITLE -->


		<!-- START SECTION TABS -->
		<div class="fc-header-tab fc-section-step fc-font-2">
			<a href=".?step=3">SECTION DETAILS</a>
			<a href=".?step=4">Project Options</a>	
			<a href="#" class="fc-tab-active tab-selected">Project Plan & Cart</a>
		</div>
		<!-- END SECTION TABS -->


		<!-- START FENCING CONTENT -->
		<div class="fencing-content fc-font-1">
		    <div class="fc-section-step">
		        
		        <div class="fencing-section">
		            <buton type="button" class="btn-fc btn-fc-orange fc-w-700 fc-float-r">
		                <i class="fa-solid fa-cart-shopping"></i>
		                Add items to cart
		            </buton>
		            <div class="step-label">Step <span>05</span></div>
		        </div>

		        <div class="fencing-section">

		        	<!-- START PROJECT DETAILS -->
		            <div class="fc-card fc-project-details">
		             
		                <div class="fc-card-header">
		                    <div class="fc-row-flex fc-row-f-s-b">
		                        
		                        <div class="fc-col">Your Project Details</div>

		                        <div class="fc-col">
		                            <div class="fc-text-right">

		                                <buton type="button" 
		                                	class="btn-fc fc-btn-edit btn-fc-outline-light fc-text-uppercase">
		                                    <i class="fa-solid fa-pencil"></i>
		                                    <span>Edit</span>
		                                </buton>

		                                <buton type="button" 
			                                class="btn-fc fc-btn-reset btn-fc-outline-light fc-text-uppercase" 
			                                style="display:none;">
			                                    <i class="fa-solid fa-rotate-left"></i> <span>Reset</span>
		                                </buton>

		                            </div>
		                        </div>
		                    </div>
		                </div>

		                <div id="update_details-section" class="fc-card-body">

							<!-- START CART CALCULATION LOADER -->
							<div class="fc-section-loader-overlay" style="display: none;">
							    <div class="fc-loader-container">
							        <div class="fc-loader">
							           	<img src="<?php echo base_url('img/loaders/1.gif'); ?>" width="80">
							        </div>
							    </div>
							</div>
							<!-- END CART CALCULATION LOADER -->

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

		                        </div>

		                        <div class="fc-col-half">
		                            <div class="fc-card fc-mb-2">
		                                
		                                <div class="fc-card-header fc-bg-dark fc-border-top">
		                                    Flat Top Pool Fencing - Options
		                                </div>

		                                <div class="fc-table-rounded-border fc-rounded-top-none fc-mb-2">
		                                   
		                                    <table class="fc-table">
		                                        <thead>
		                                            <tr>
		                                                <td width="100">Colour</td>
		                                                <td>
		                                                    <div style="background:#000;color:#fff;border:	2px solid var(--fc-gray);max-width:250px;" class="fc-colour-item fc-border fc-p-1">
		                                                        <br>   
		                                                        <p>Black</p>
		                                                        Satin
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
			                                        rows="7"><?php echo @$info['notes']; ?></textarea>
		                                    </div>
		                                </div>

		                            </div>

		                        </div>
		                    </div>
		                </div>
		            </div>
		            <!-- END PROJECT DETAILS -->


		            <!-- START PROJECT PLAN -->
		            <div class="fc-card">
		                
		                <div class="fc-card-header">
		                    <div class="fc-row-flex fc-row-f-s-b">
		                       
		                        <div class="fc-col">Project Plan</div>

		                        <div class="fc-col">
		                            
		                            <div class="fc-text-right" style="display:none;">
		                                <buton type="button" 
		                                	class="btn-fc fc-btn-download-fence btn-fc-outline-default fc-text-uppercase">
		                                	<i class="fa fa-download"></i> Download Plans
		                                </buton>
		                            </div>

		                        </div>
		                    </div>
		                </div>

		                <div class="fc-card-body">
		                    <div id="fc-fence-list">
		                        <?php include 'data/plan-item.php'; ?>
		                    </div>
		                </div>

		            </div>
		            <!-- END PROJECT PLAN -->


		            <!-- START PRODUCT LIST -->
		            <div class="fc-card fc-mb-3">
		                
		                <div class="fc-card-header">Product List & Cart</div>

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

		                <div id="update_cart-section" class="fc-card-body">

							<!-- START CART CALCULATION LOADER -->
							<div class="fc-section-loader-overlay" style="display: none;">
							    <div class="fc-loader-container">
							        <div class="fc-loader">
							           	<img src="<?php echo base_url('img/loaders/1.gif'); ?>" width="80">
							        </div>
							    </div>
							</div>
							<!-- END CART CALCULATION LOADER -->
		                    
		                    <div class="step-label">Item List & <span>Cart</span></div>

		                    <div class="fc-row-container" style="justify-content: flex-start;">
		                        <div class="fc-col-half">

						           <div class="fc-card fc-table-items">          		
			                        	<?php include('temp/cart-table.php'); ?>
		                        	</div>

		                        </div>
		                        
		                        <div class="fc-col-half">

		                        	<!-- START SHIPPING OPTION -->
		                            <h4 class="fc-mb-2">Pick-Up / Delivery</h4>

		                            <div class="fc-form-group fc-form-check fc-mb-3">

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
		                            <h4 class="fc-mb-3">Pay with creditcard</h4>

		                            <div class="select-cards">
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


		                            <button type="submit" 
			                            class="btn-fc fc-btn-md btn-fc-orange fc-text-uppercase fc-mb-1 fc-w-700 w-100-sm">
			                            <i class="fa-solid fa-cart-shopping"></i>
			                            Order Items Now!
		                            </button>

		                        </div>

		                    </div>


		                    <!-- START CONTACT SECTION -->
		                    <h4>Need Help With Your Project?</h4>

		                    <div class="fc-mb-3">
		                       
		                        <buton type="button" class="btn-fc btn-fc-black fc-mb-1 w-100-sm">
		                            <i class="fc-icon fc-icon-phone"></i>
		                            Call Us Now!
		                        </buton>

		                        <buton type="button" class="btn-fc btn-fc-black fc-mb-1 w-100-sm">
		                            <i class="fc-icon fc-icon-chat-dots"></i>
		                            Chat With Live Support
		                        </buton>
		                        
		                    </div>
		                    <!-- END CONTACT SECTION -->


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
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/checkout.js?v=<?php echo date('YmdHis'); ?>"></script>