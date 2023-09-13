<!DOCTYPE html>
<html>
<head>
	<title></title>

	<?php 
		include('data.php');
		include('temp/fields.php');
		include('helpers.php');
	?>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">

	<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">
	  <script src="https://js.stripe.com/v3/" async></script>
</head>
<body>


	
	<div class="fencing-container fc-project-plan" data-tab="1">
		
		<form method="POST" id="paymentFrm" action="<?php echo base_url('pay.php'); ?>">

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

		<div class="fc-header-tab fc-section-step fc-font-2">
			<a href=".?step=3">SECTION DETAILS</a>
			<a href=".?step=4">Project Options</a>	
			<a href="#" class="fc-tab-active tab-selected">Project Plan & Cart</a>
		</div>

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

					<div class="fc-card fc-project-details">
						<div class="fc-card-header">

							<div class="fc-row-flex fc-row-f-s-b">
								<div class="fc-col">
									Your Project Details
								</div>
								<div class="fc-col">
									<div class="fc-text-right">
										<buton type="button" class="btn-fc fc-btn-edit btn-fc-outline-light fc-text-uppercase">
											<i class="fa-solid fa-pencil"></i>
											<span>Edit</span>
										</buton>
										<buton type="button" class="btn-fc fc-btn-reset btn-fc-outline-light fc-text-uppercase" style="display:none;">
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
														<span>James Evans</span>
														<div class="fc-form-group">
															<input type="text" name="name" value="James Evans" class="fc-form-control fc-form-control-sm">
														</div>	
													</td>
												</tr>
												<tr>
													<td>Contact</td>
													<td>
														<span>+61 2345 6789</span>
														<div class="fc-form-group">
															<input type="text" name="phone" value="+61 2345 6789" class="fc-form-control fc-form-control-sm">
														</div>	
													</td>
												</tr>
												<tr>
													<td>Email</td>
													<td>
														<span>james@gmail.com</span>
														<div class="fc-form-group">
															<input type="text" name="email" value="james@gmail.com" class="fc-form-control fc-form-control-sm">
														</div>														
													</td>
												</tr>
												<tr>
													<td>State</td>
													<td>
														<span>WA</span>
		
														<div class="fc-form-group">
															<select name="state" class="fc-form-control fc-form-control-sm" required>
															    <option value="">Select an option…</option>
															    <option value="ACT">Australian Capital Territory</option>
															    <option value="NSW">New South Wales</option>
															    <option value="NT">Northern Territory</option>
															    <option value="QLD">Queensland</option>
															    <option value="SA">South Australia</option>
															    <option value="TAS">Tasmania</option>
															    <option value="VIC">Victoria</option>
															    <option value="WA">Western Australia</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td>Post Code</td>
													<td>
														<span>6026</span>
														<div class="fc-form-group">
															<input type="text" name="postcode" value="6026" class="fc-form-control fc-form-control-sm">
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
												<td>Within the next 8 weeks, Waiting on pool to be installed</td>
											</tr>
											<tr>
												<td>Install Required</td>
												<td>No</td>
											</tr>
											<tr>
												<td>Other Items Needed</td>
												<td>Pool cover, Pump Enclosure</td>
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
												<textarea name="notes" placeholder="Write your notes here" class="fc-form-control" rows="7"></textarea>
									
											</div>

										</div>
									</div>
												
								</div>
							</div>

						</div>
					</div>

					<div class="fc-card">
						<div class="fc-card-header">

							<div class="fc-row-flex fc-row-f-s-b">
								<div class="fc-col">
									Product List & Cart
								</div>
								<div class="fc-col">
									<div class="fc-text-right" style="display:none;">
										<buton type="button" class="btn-fc fc-btn-download-fence btn-fc-outline-default fc-text-uppercase"><i class="fa fa-download"></i> Download Plans</buton>
									</div>
								</div>
							</div>

						</div>
						<div class="fc-card-body">
							
							<div id="fc-fence-list">


						<?php foreach( range(1, 1) as $row ): ?>
								<h3 class="fc-mb-2">Section <?php echo $row; ?></h3>

	<div class="fencing-section fencing-display-result">


	    <div class="fencing-panel-items" style="">
			<div class="fc-overall">
				11,0000 Overall
			</div>
		
	        <div class="fencing-panel-container">
	            <div class="left_raked-panel raked-panel"></div>
	            <div class="fencing-panel-spacing-number fpsn-b yes-post opt-1">
	                <span>50</span>                 
	            </div>
	            <div class="panel-post fencing-btn-modal panel-yes-post opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
	            <div id="panel-item-0" data-id="0" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	                <div class="fencing-panel-item-size">2140W <br>PANEL</div>

				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	<span class="fc-start-c-p">25</span>
				    	1060<br>
				    	Centers
				    </div>

	            </div>
	            <div class="fencing-panel-spacing-number fpsn-b opt-1">
	                <span>50</span>                 
	            </div>

	            <div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
	            <div id="panel-item-1" data-id="1" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	                <div class="fencing-panel-item-size">2140W <br>PANEL</div>

				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>

	            </div>
	            <div class="fencing-panel-spacing-number fpsn-b opt-1">
	                <span>50</span>                 
	            </div>
	            <div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
	            <div id="panel-item-2" data-id="2" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	                <div class="fencing-panel-item-size">2140W <br>PANEL</div>

				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>

	            </div>
	            <div class="fencing-panel-spacing-number fpsn-b opt-1">
	                <span>50</span>                 
	            </div>
	            <div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
	            <div id="panel-item-3" data-id="3" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	                <div class="fencing-panel-item-size">2140W <br>PANEL</div>

				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>

	            </div>
	            <div class="fencing-panel-spacing-number fpsn-b opt-1">
	                <span>50</span>                 
	            </div>
	            <div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
	            <div id="panel-item-4" data-id="4" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	                <div class="fencing-panel-item-size">2140W <br>PANEL</div>

				    <div class="fc-center-point fc-last-c-p">
				    	<span class="fc-div-c-p"></span>
				    	<span class="fc-div-c-p"></span>
				    	<span class="fc-end-c-p">25</span>
				    	2460<br>
				    	Centers
				    </div>

	            </div>
	            <div class="fencing-panel-spacing-number fpsn-b yes-post opt-1">
	                <span>50</span>
	            </div>
	            <div class="panel-post fencing-btn-modal panel-yes-post opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
	            <div class="right_raked-panel raked-panel"></div>
	            <div class="fencing-offcut offcut-b">
	                <div>
	                    OFF-CUT
	                    <div>5 x 1300W</div>
	                </div>
	            </div>
	        </div>

	    </div>

	</div>

								<?php endforeach; ?>





<div class="fencing-section fencing-display-result">

						<div class="fencing-result-msg" style="display: none;">
							<p>No Valid Solution. Please adjust Measurements.</p>			
						</div>

						<div class="fencing-panel-items" style="">
									<div class="fc-overall">
				11,0000 Overall
			</div>
							<div class="fencing-panel-rail fencing-btn-modal" data-key="rail_options" data-target="#fc-control-modal" style="display:none;"></div>

							<div class="fencing-panel-container"><div class="left_raked-panel raked-panel"></div>
<div class="fencing-panel-spacing-number fpsn-b no-post left-panel-post opt-1">
    <span>(50)</span>                 
</div>

<div class="panel-post fencing-btn-modal panel-no-post left-panel-post opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-0" data-id="0" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	<div class="fencing-panel-item-size">2140W <br>PANEL</div>
			    <div class="fc-center-point fc-first-c-p">
			    	<span class="fc-div-c-p"></span>
			    	<span class="fc-start-c-p">25</span>
			    	2460<br>
			    	Centers
			    </div>
</div>	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-1" data-id="1" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	<div class="fencing-panel-item-size">2140W <br>PANEL</div>
			    <div class="fc-center-point">
			    	<span class="fc-div-c-p"></span>
			    	2460<br>
			    	Centers
			    </div>
</div>	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-2" data-id="2" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	<div class="fencing-panel-item-size">2140W <br>PANEL</div>
			    <div class="fc-center-point">
			    	<span class="fc-div-c-p"></span>
			    	2460<br>
			    	Centers
			    </div>
</div>	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-3" data-id="3" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	<div class="fencing-panel-item-size">2140W <br>PANEL</div>
			    <div class="fc-center-point">
			    	<span class="fc-div-c-p"></span>
			    	2460<br>
			    	Centers
			    </div>
</div>	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-4" data-id="4" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 214px;">
	<div class="fencing-panel-item-size">2140W <br>PANEL</div>
				    <div class="fc-center-point fc-last-c-p">
				    	<span class="fc-div-c-p"></span>
				    	<span class="fc-div-c-p"></span>
				    	<span class="fc-end-c-p">25</span>
				    	2460<br>
				    	Centers
				    </div>
</div>	

<div class="fencing-panel-spacing-number fpsn-b yes-post opt-1">
    <span>50</span>
</div>

<div class="panel-post fencing-btn-modal panel-yes-post opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
<div class="right_raked-panel raked-panel"></div>
<div class="fencing-offcut offcut-b">
	<div>
		OFF-CUT
		<div>5 x 1300W</div>
	</div>
</div>
</div>

						</div>

					</div>










<div class="fencing-section fencing-display-result" style="padding: 4% 10px;">


						<div class="fencing-panel-items" style="">

		<div class="fc-overall">
				11,0000 Overall
			</div>

							<div class="fencing-panel-container"><div class="left_raked-panel raked-panel">
<div class="fencing-panel-spacing-number fpsn-b yes-post opt-1">
	<span>50</span>					
</div>

<div class="raked-panel-post panel-post panel-yes-post fencing-btn-modal opt-1" data-key="left_side" data-target="#fc-control-modal"></div>

<div class="raked-panel-container">

	<div class="fencing-left-panel-o-b">
		<div id="panel-item-4" data-id="4" class="fencing-panel-item fencing-raked-panel fencing-left-panel-b fencing-btn-modal panel-item ally" data-key="add_step_up_panels" data-target="#fc-control-modal" style="width: 120px;">
			<div class="fencing-panel-item-size">1500H <br> 1200W</div>
		</div>

	</div>
		
			    <div class="fc-center-point fc-first-c-p">
			    	<span class="fc-div-c-p"></span>
			    	<span class="fc-start-c-p">25</span>
			    	2460<br>
			    	Centers
			    </div>
</div>
</div>
<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-0" data-id="0" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 179.8px;">
	<div class="fencing-panel-item-size">1798W <br>PANEL</div>
				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>
</div>
<div class="fencing-panel-spacing-number fpsn-b opt-1">
	<span>50</span>					
</div>	
<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
<div class="fencing-panel-item fencing-panel-gate fencing-btn-modal ally" data-key="gate" data-target="#fc-control-modal"><span class="fc-gate-spacing fc-gate-left-spacing">20</span>
	<div class="fencing-panel-item-size">970mm<br> GATE</div>
<span class="fc-gate-spacing fc-gate-right-spacing">20</span>

				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>

</div>	
	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-1" data-id="1" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 179.8px;">
	<div class="fencing-panel-item-size">1798W <br>PANEL</div>


				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>

</div>	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-2" data-id="2" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 179.8px;">
	<div class="fencing-panel-item-size">1798W <br>PANEL</div>

				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>
</div>	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>                 
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-3" data-id="3" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal" style="width: 179.8px;">
	<div class="fencing-panel-item-size">1798W <br>PANEL</div>
				    <div class="fc-center-point">
				    	<span class="fc-div-c-p"></span>
				    	2460<br>
				    	Centers
				    </div>
</div>	

<div class="fencing-panel-spacing-number fpsn-b opt-1">
    <span>50</span>
</div>

<div class="panel-post fencing-btn-modal opt-1" data-key="post_options" data-target="#fc-control-modal"></div>
<div class="right_raked-panel raked-panel">
<div class="raked-panel-container">

	<div class="fencing-right-panel-o-b">
		<div id="panel-item-4" data-id="4" class="fencing-panel-item fencing-raked-panel fencing-right-panel-b fencing-btn-modal panel-item ally" data-key="add_step_up_panels" data-target="#fc-control-modal" style="width: 120px;">
			<div class="fencing-panel-item-size">1700H <br> 1200W</div>

		</div>	
	</div>
				    <div class="fc-center-point fc-last-c-p">
				    	<span class="fc-div-c-p"></span>
				    	<span class="fc-div-c-p"></span>
				    	<span class="fc-end-c-p">25</span>
				    	2460<br>
				    	Centers
				    </div>
</div>

<div class="fencing-panel-spacing-number fpsn-b yes-post opt-1">
	<span>50</span>					
</div>

<div class="panel-post raked-panel-post panel-yes-post fencing-btn-modal opt-1" data-key="right_side" data-target="#fc-control-modal"></div>
</div>
<div class="fencing-offcut offcut-b">
	<div>
		OFF-CUT
		<div>4 x 0W</div>
	</div>
</div>
</div>

						</div>

					</div>


							</div>

						</div>
					</div>

					<div class="fc-card fc-mb-3">
						<div class="fc-card-header">
							Product List & Cart
						</div>
						<div class="fc-card-body">

							<div class="step-label">Item List & <span>Cart</span></div>

							<div class="fc-row-container" style="justify-content: flex-start;">
								<div class="fc-col-half">

									<div class="fc-card fc-table-items">
										<div class="fc-card-body fc-border-bottom fc-p-0 fc-border-0 fc-position-relative">

											<i class="fa-regular fa-pen-to-square fc-text-success fc-editing-icon" style="display: none;"></i>

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

						
									<h4 class="fc-mb-3">Pay with creditcard</h4>


									<!-- START SRTIPE PAYMENT -->
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


									<button type="submit" class="btn-fc fc-btn-md btn-fc-orange fc-text-uppercase fc-mb-1 fc-w-700 w-100-sm">
										<i class="fa-solid fa-cart-shopping"></i>
										Order Items Now!
									</button>

    
								</div>

							</div>

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



						</div>
					</div>

				</div>
			</div>


		  </div>

		</div>

</form>

	</div>




	<script type="text/javascript" src="jquery-3.3.1.min.js"></script>

	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

	<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
	<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>


	  <script type="text/javascript"> 

// Initialize Stripe with your publishable API key
var stripe = Stripe('pk_test_fY3GMPqaZTKE94kLMB5BnOdf');


// Create an instance of elements
var elements = stripe.elements();

var style = {
    base: {
        fontWeight: 400,
        fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif',
        fontSize: '16px',
        lineHeight: '1.4',
        color: '#555',
        backgroundColor: '#fff',
        '::placeholder': {
            color: '#888',
        },
    },
    invalid: {
        color: '#eb1c26',
    }
};

var cardElement = elements.create('cardNumber', {
    style: style
});
cardElement.mount('#card_number');

var exp = elements.create('cardExpiry', {
    'style': style
});
exp.mount('#card_expiry');

var cvc = elements.create('cardCvc', {
    'style': style
});
cvc.mount('#card_cvc');

// Validate input of the card elements
var resultContainer = document.getElementById('paymentResponse');
cardElement.addEventListener('change', function(event) {
    if (event.error) {
        resultContainer.innerHTML = '<p>'+event.error.message+'</p>';
    } else {
        resultContainer.innerHTML = '';
    }
});

// Get payment form element
var form = document.getElementById('paymentFrm');

// Create a token when the form is submitted.
form.addEventListener('submit', function(e) {
    e.preventDefault();
    createToken();
});

// Create single-use token to charge the user
function createToken() {
    stripe.createToken(cardElement).then(function(result) {
        if (result.error) {
            // Inform the user if there was an error
            resultContainer.innerHTML = '<p>'+result.error.message+'</p>';
        } else {
            // Send the token to your server
            stripeTokenHandler(result.token);
        }
    });
}

// Callback to handle the response from stripe
function stripeTokenHandler(token) {

    // Insert the token ID into the form so it gets submitted to the server
    var hiddenInput = document.createElement('input');
    hiddenInput.setAttribute('type', 'hidden');
    hiddenInput.setAttribute('name', 'stripeToken');
    hiddenInput.setAttribute('value', token.id);
    form.appendChild(hiddenInput);
    
    // Submit the form
    form.submit();

    
}
</script>




	<script type="text/javascript">

	$(document).on('click', '.fc-btn-download-fence', function () {    

/*		var element = document.getElementById('fc-fence-list');
		var opt = {
		  margin:       1,
		  filename:     'myfile.pdf',
		  image:        { type: 'jpeg', quality: 0.98 },
		  html2canvas:  { scale: 2 },
		  jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
		};

		// New Promise-based usage:
		html2pdf().set(opt).from(element).save();
*/


var element = document.getElementById('fc-fence-list');
html2pdf(element);

	});


	$(document).on('click', ".fc-btn-edit", function (e) {
		e.preventDefault();

		if( $(this).find('span').text() == 'Edit' ) {
			$('.fc-project-details .fc-form-group, .fc-btn-reset').show();
			$('.fc-project-details table span').hide();
			$(this).find('span').html('Update');

			$('.fc-project-details .fc-editing-icon').fadeIn();

		} else {

			$('.fc-table-customer td').each(function(){
				if( $(this).find('.fc-form-control').length ) {
					var val = $(this).find('.fc-form-control').val();
					$(this).find('span').html( val );
				}
			});

		    $(".fc-table-customer .fc-form-control").css({'color': '#4caf50'}); 

		    setTimeout(function() { 
		    	$(".fc-table-customer .fc-form-control").css({'color': ''}); 

				$('.fc-table-customer span').show();
				$('.fc-project-details .fc-form-group, .fc-btn-reset').hide();

				$('.fc-project-details .fc-editing-icon').fadeOut();

		    } , 500);

			$(this).find('span').html('Edit');

		}

	});

	$(document).on('click', ".fc-btn-reset", function (e) {
		e.preventDefault();

		$('.fc-table-customer td').each(function(){
			if( $(this).find('.fc-form-control').length ) {
				var val = $(this).find('span').text();
				$(this).find('.fc-form-control').val(val)
			}
		});

	    $(".fc-table-customer .fc-form-control").css({'color': '#f67925'}); 

	    setTimeout(function() { 
	    	$(".fc-table-customer .fc-form-control").css({'color': ''}); 
	    } , 500);

	});

	$(document).on('click', ".fc-edit-item", function (e) {
		e.preventDefault();

		if( $(this).text() == 'Edit' ) {
			$('.fc-table-items input, .fc-reset-item, .fc-link-div').show();
			$('.fc-item-value').hide();
			$(this).html('Update');
			$('.fc-table-items .fc-editing-icon').fadeIn();
		} else {

			$('.fc-table-items td').each(function(){
				if( $(this).find('.fc-form-control').length ) {
					var val = $(this).find('.fc-form-control').val();
					$(this).find('.fc-item-value').html( val );
				}
			});

		    $(".fc-table-items .fc-form-control").css({'color': '#4caf50'}); 

		    setTimeout(function() { 
		    	$(".fc-table-items .fc-form-control").css({'color': ''}); 

				$('.fc-item-value').show();
				$('.fc-table-items input, .fc-reset-item, .fc-link-div').hide();
				$('.fc-table-items .fc-editing-icon').fadeOut();
		    } , 500);

			$(this).html('Edit');

		}

	});

	$(document).on('click', ".fc-reset-item", function (e) {
		e.preventDefault();

		$('.fc-table-items td').each(function(){
			if( $(this).find('.fc-form-control').length ) {
				var val = $(this).find('.fc-item-value').text();
				$(this).find('.fc-form-control').val(val)
			}
		});

	    $(".fc-table-items .fc-form-control").css({'color': '#f67925'}); 

	    setTimeout(function() { 
	    	$(".fc-table-items .fc-form-control").css({'color': ''}); 
	    } , 500);

	});

	</script>


</body>
</html>
