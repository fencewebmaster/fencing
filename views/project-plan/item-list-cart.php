													
<!-- [START] PRODUCT LIST -->
<div class="fc-cart-items">
	<div class="fc-card">

		<div id="update_cart-section" class="fencing-section fencing-section--has-border">

				<div class="row">

					<div class="col-lg-7 col-md-8 col-sm-12 fc-position-relative order-md-1 order-2" id="update_cart-list">
					    <!-- [START] Label -->
				        <div class="row align-items-center mb-2">
				        	<div class="col-sm col">
				        		<div class="step-label">Item List & <span>Cart</span></div>

				        	</div>
				        	<div class="col-sm col-auto text-end">
					        		
					        	<a href="" class="btn btn-orange fc-update-item text-uppercase js-fc-edit-item fw-bold">
					            	<i class="fa-regular fa-pen-to-square me-1"></i> <span>Edit</span>
					            </a>

					            <a href="" class="btn btn-secondary fc-reset-item fw-bold text-uppercase" style="display: none;">
					            	<i class="fa-solid fa-rotate-left me-1"></i> Reset</a>

				        	</div>
				        </div>
					    <!-- [END] Label -->

					    <div class="fc-card fc-table-items">
					        <?php include('views/sections/cart-table.php'); ?>
					    </div>

						<div class="d-grid gap-2">
							<div class="animate__animated" animation-type="animate__bounce">
						        <button type="submit" class="btn btn-lg btn-submit btn-green text-uppercase w-100 text fw-bold animate__animated  animate__delay-1s" animation-type="animate__shakeX">
						            <i class="fa-solid fa-cart-shopping me-1"></i>
						            View Total Cost!
						        </button>								
							</div>
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

							    <p>Items in Stock: <span class="fc-stock-status fc-stock-status--inline">
							    	<i class="fa-solid fa-circle-check text-success me-2 fs-4"></i> Yes</span>
							    </p>

							    <div class="alert alert-danger fc-step-2-alert fc-alert-gray--low-stock">
							        <h3 class="fc-mb-1"><i class="fa fa-exclamation-circle"></i> Low Stock Warning</h3>
							        <p class="fc-text-red">
							            Some items have limited stock available. <br />
							            Your cart can only be Reserved for a Limited Time <br />
							            Then its released for other customers.
							        </p>
							    </div>
							</div>

							<div style="clear: both;"></div>
							
							<div class="fc-cart-countdown mb-3">
							    <p>
							        <strong>ORDER WITHIN:</strong><br />
							        <span id="fc-countdown-timer"></span>
							    </p>
							</div>
							
							<div class="row">
								<div class="col-lg col-md-12 col-sm mb-2">
							        <button type="submit" class="btn btn-submit btn-lg btn-dark text-uppercase w-100 fs-6 text fw-bold">
							            <i class="fa-solid fa-cart-shopping me-2"></i>
							            Order Items Now!
							        </button>		
								</div>
								<div class="col-lg col-md-12 col-sm">
							        <a href="tel:0480016687" class="btn btn-lg btn-secondary text-uppercase w-100 fs-6 text">
							           	<b><i class="fa-solid fa-phone me-2"></i> Call 04800 166 87</b>
							        </a>	
						       </div>									    											

							</div>

						</div>

					</div>

				</div>

			</div>


		</div>
	</div>
</div>
<!-- [END] PRODUCT LIST -->