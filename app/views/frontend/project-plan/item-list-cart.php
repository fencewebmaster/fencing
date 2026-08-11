													
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
					        	<div class="col min-w-0">
					        	<div class="js-fc-cart-toolbar fc-cart-toolbar d-flex align-items-center justify-content-end gap-2 flex-wrap w-100">
					        		<div class="js-fc-cart-toolbar-actions d-flex align-items-center gap-2 flex-wrap ms-sm-auto">
											<a href="#" class="btn btn-outline-dark js-fc-copy-cart-items fw-bold text-uppercase" aria-label="Copy item list">
							            		<i class="fa-regular fa-copy me-sm-1" aria-hidden="true"></i>
							            		<span class="d-none d-sm-inline">Copy</span>
							            	</a>
											<a href="#" class="btn btn-orange fc-update-item text-uppercase js-fc-edit-item fw-bold" aria-label="Edit item">
							            		<i class="fa-regular fa-pen-to-square me-sm-1" aria-hidden="true"></i>
							            		<span class="d-none d-sm-inline">Edit</span>
							            	</a>
									        <a href="#" class="btn btn-secondary fc-cancel-item fw-bold text-uppercase" style="display: none;" aria-label="Cancel editing cart">
							            		<i class="fa-solid fa-xmark me-sm-1" aria-hidden="true"></i> <span class="d-none d-sm-inline">Cancel</span>
							            	</a>
							            	<a href="#" class="btn btn-dark fc-reset-item fw-bold text-uppercase" style="display: none;">
							            		<i class="fa-solid fa-rotate-left me-sm-1"></i> <span class="d-none d-sm-inline">Reset</span>
							            	</a>
					        		</div>
					        	</div>

				        	</div>
				        </div>
					    <!-- [END] Label -->

					    <div class="fc-card fc-table-items">
					        <?php include __DIR__ . '/../sections/cart-table.php'; ?>
					    </div>

						<div class="fc-view-total-cost-bar">
							<div class="fc-view-total-cost-bar__inner">
								<div class="d-grid gap-2">
									<div class="animate__animated" animation-type="animate__bounce">
										<button type="submit" class="btn btn-lg btn-submit btn-green js-fc-view-total-cost text-uppercase w-100 text fw-bold animate__animated  animate__delay-1s" animation-type="animate__shakeX">
											<i class="fa-solid fa-cart-shopping me-1"></i>
											View Total Cost!
										</button>
									</div>
								</div>
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
							
							<div class="fc-stock-delivery-actions d-grid gap-2">
								<button type="button" class="btn btn-lg fc-btn-download-fence btn-outline-dark text-uppercase w-100 fs-6 text fw-bold" aria-label="Download project plans as PDF">
									<i class="fa-solid fa-download me-2" aria-hidden="true"></i>
									<span>Download Plans</span>
								</button>

								<div class="row g-2">
									<div class="col-lg col-md-12 col-sm">
								        <button type="submit" class="btn btn-submit btn-lg btn-dark text-uppercase w-100 fs-6 text fw-bold">
								            <i class="fa-solid fa-cart-shopping me-2"></i>
								            Order Items Now!
								        </button>
									</div>
									<div class="col-lg col-md-12 col-sm">
								        <a href="tel:0480016687" class="btn btn-lg btn-dark text-uppercase w-100 fs-6 text">
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
</div>
<!-- [END] PRODUCT LIST -->