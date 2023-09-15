<?php
	session_start();
	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

	include('data/settings.php');
	include('temp/fields.php');
	include('helpers.php');
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">

<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">
<script src="https://js.stripe.com/v3/" async></script>


<!-- START FENCING CONTAINER -->
<div class="fencing-container fc-project-plan" data-tab="1">
	

	<!-- START CHECKOUT FORM -->
	<form method="POST" id="paymentFrm" action="<?php echo base_url('pay.php'); ?>">


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

		                <div class="fc-card-body">
		                    <div class="fc-row-container">
		                        <div class="fc-col-half">
		                            <div class="fc-table-rounded-border fc-mb-2 fc-position-relative">
		                                
		                                <i class="fa-regular fa-pen-to-square fc-editing-icon" style="display: none;"></i>

		                                <table class="fc-table fc-table-customer">
		                                    <tbody>
		                                        <tr>
		                                            <td>Name</td>
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
		                                                    <input type="text" name="phone" value="<?php echo @$info['mobile']; ?>" class="fc-form-control fc-form-control-sm">
		                                                </div>
		                                            </td>
		                                        </tr>
		                                        <tr>
		                                            <td>Email</td>
		                                            <td>
		                                                <span><?php echo @$info['email']; ?></span>
		                                                <div class="fc-form-group">
		                                                    <input type="email" name="email" value="<?php echo @$info['email']; ?>" class="fc-form-control fc-form-control-sm no-space">
		                                                </div>
		                                            </td>
		                                        </tr>
		                                        <tr>
		                                            <td>State</td>
		                                            <td>
		                                                <span><?php echo @$info['state']; ?></span>
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
		                                                <span>Suite 756 031 Ines Riverway, Rhiannonchester</span>
		                                                <div class="fc-form-group">
		                                                    <input type="text" name="address" value="Suite 756 031 Ines Riverway, Rhiannonchester" class="fc-form-control fc-form-control-sm">
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

		                <div class="fc-card-body">
		                    
		                    <div class="step-label">Item List & <span>Cart</span></div>

		                    <div class="fc-row-container" style="justify-content: flex-start;">
		                        <div class="fc-col-half">
		                            <div class="fc-card fc-table-items">
		                                <div class="fc-card-body fc-border-bottom fc-p-0 fc-border-0 fc-position-relative">
		                                    <i class="fa-regular fa-pen-to-square fc-editing-icon" style="display: none;"></i>
		                                    <div class="fc-table-rounded-border fc-mb-2">
		                                        
		                                        <table class="fc-table fc-table-bordered fc-table-striped">
		                                            <thead class="fc-bg-dark fc-border">
		                                                <tr>
		                                                    <th>QTY</th>
		                                                    <th>Description</th>
		                                                    <th>SKU</th>
		                                                    <th>RRP</th>
		                                                    <th>Trade Price</th>
		                                                    <th>Sub Total</th>
		                                                </tr>
		                                            </thead>
		                                            <tbody>
		                                                <tr>
		                                                    <td>
		                                                        <span class="fc-item-value">10</span>
		                                                        <input name="qty[]" type="number" value="10" class="fc-form-control" min="1">
		                                                    </td>
		                                                    <td>Panels - 2400W</td>
		                                                    <td>XX-XXXX-XX</td>
		                                                    <td><s>$129</s></td>
		                                                    <td>$89.30</td>
		                                                    <td>$893</td>
		                                                </tr>
		                                                <tr>
		                                                    <td>
		                                                        <span class="fc-item-value">10</span>
		                                                        <input name="qty[]" type="number" value="10" class="fc-form-control" min="1">
		                                                    </td>
		                                                    <td>Bracket</td>
		                                                    <td>12345</td>
		                                                    <td><s>14.25</s></td>
		                                                    <td>$12.10</td>
		                                                    <td>$120</td>
		                                                </tr>
		                                                <tr>
		                                                    <td>
		                                                        <span class="fc-item-value">12</span>
		                                                        <input name="qty[]" type="number" value="12" class="fc-form-control" min="1">
		                                                    </td>
		                                                    <td>Post - 1300L</td>
		                                                    <td>678910</td>
		                                                    <td><s>$50.90</s></td>
		                                                    <td>$39.00</td>
		                                                    <td>$468</td>
		                                                </tr>
		                                                <tr>
		                                                    <td>
		                                                        <span class="fc-item-value">14</span>
		                                                        <input name="qty[]" type="number" value="14" class="fc-form-control" min="1">
		                                                    </td>
		                                                    <td>Base Plate - Covers</td>
		                                                    <td>ABCDE</td>
		                                                    <td><s>$16.95</s></td>
		                                                    <td>$9.50</td>
		                                                    <td>$133</td>
		                                                </tr>
		                                                <tr>
		                                                    <td>
		                                                        <span class="fc-item-value">15</span>
		                                                        <input name="qty[]" type="number" value="15" class="fc-form-control" min="1">
		                                                    </td>
		                                                    <td>Fixing Kits</td>
		                                                    <td>FGHIJ</td>
		                                                    <td><s>$14.95</s></td>
		                                                    <td>$12.80</td>
		                                                    <td>$192</td>
		                                                </tr>
		                                            </tbody>
		                                        </table>

		                                    </div>

		                                    <div class="fc-items-action fc-mb-2">
		                                        <a href="" class="fc-edit-item">Edit</a>
		                                        <a href="" class="fc-reset-item" style="display: none;">Reset</a>									
		                                    </div>

		                                    <div class="fc-float-r fc-mb-2">
		                                        
		                                        <table>
		                                            <tr>
		                                                <td class="fc-text-right"><b class="fc-mr-1">Sub Total:</b></td>
		                                                <td>$1,806.00</td>
		                                            </tr>
		                                            <tr>
		                                                <td class="fc-text-right"><b class="fc-mr-1">Trade Discount:</b></td>
		                                                <td>$584.50</td>
		                                            </tr>
		                                            <tr>
		                                                <td class="fc-text-right"><b class="fc-mr-1">Delivery:</b></td>
		                                                <td>$89</td>
		                                            </tr>
		                                            <tr>
		                                                <td class="fc-text-right"><b class="fc-mr-1">GST:</b></td>
		                                                <td>$180.6</td>
		                                            </tr>
		                                            <tr>
		                                                <td class="fc-text-right"><b class="fc-mr-1">Total:</b></td>
		                                                <td>$2,075.6</td>
		                                            </tr>
		                                        </table>

		                                    </div>

		                                    <div style="clear: both;"></div>

		                                </div>
		                            </div>
		                        </div>

		                        
		                        <div class="fc-col-half">

		                        	<!-- START SHIPPING OPTION -->
		                            <h4 class="fc-mb-2">Pick-Up / Delivery</h4>

		                            <div class="fc-form-group fc-form-check fc-mb-3">
		                                <label class="fc-mb-1">
			                                <input type="radio" name="shipping" class="fc-mr-1" value="shipping_1" checked required>
			                                Warehouse Pickup
		                                </label>

		                                <label class="fc-mb-1">
			                                <input type="radio" name="shipping" class="fc-mr-1" value="shipping_2" required>
			                                Deliver to Site (Metro $89)
		                                </label>

		                                <label class="fc-mb-1">
			                                <input type="radio" name="shipping" class="fc-mr-1" value="shipping_3" required>
			                                Deliver to Site (Rural - $TBA)
		                                </label>
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

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/checkout.js?v=<?php echo date('YmdHis'); ?>"></script>
