													
<?php
/**
 * Project plan — item list + cart, and the Stock & Delivery panel beside it.
 *
 * @var array $fc_pp_stock Stock & Delivery settings (Settings -> Project Plan).
 * @var string $fc_pp_phone Call button number as typed in settings; '' hides the button.
 */

use Fc\Admin\Settings\PlannerOptionSettings;

$fc_pp_stock = PlannerOptionSettings::stock();
$fc_pp_phone = trim((string) ($fc_pp_stock['phone'] ?? ''));
?>
<?php /* [START] PRODUCT LIST */ ?>
<div class="fc-cart-items">
	<div class="fc-card">

		<div id="update_cart-section" class="fencing-section fencing-section--has-border">

				<div class="row">

					<div class="col-lg-7 col-md-8 col-sm-12 fc-position-relative order-md-1 order-2" id="update_cart-list">
					    <?php /* [START] Label */ ?>
				        <div class="row align-items-center">
				        	<div class="col-sm col">
				        		<div class="step-label">Item List & <span>Cart</span></div>

				        	</div>

				        	<?php /* Copy sits with the heading; the fence-style filter is put in front of
				        	     it by fcMountCartHeadingActions(). The filter cannot be written here:
				        	     it is part of the cart fragment the server re-renders, and its options
				        	     would go stale the first time the list changed.
				        	     ms-auto, not the grid: the title column is set to shrink to its
				        	     content at 576px up, so there is nothing to push this to the edge. */ ?>
				        	<div class="col-auto ms-auto js-fc-cart-heading-actions fc-cart-heading-actions d-flex align-items-center gap-2">
				        		<a href="#" class="btn btn-sm btn-outline-dark js-fc-copy-cart-items text-uppercase" aria-label="Copy item list">
				        			<i class="fa-regular fa-copy me-sm-1" aria-hidden="true"></i>
				        			<span class="d-none d-sm-inline">Copy</span>
				        		</a>
				        	</div>
				        </div>
					    <?php /* [END] Label */ ?>

					    <div class="fc-card fc-table-items">
					        <?php include view_path('frontend.partials.sections.cart-table'); ?>
					    </div>

						<?php /* Every cart action lives here rather than beside the heading: the cart is the
						     longest table on the page, and up there they scrolled away from the rows
						     they act on. The bar carries both states — Edit and Copy at rest, Cancel,
						     Reset and Save while editing — and checkout.js only ever shows and hides
						     within it, so the one Edit/Save button keeps a single label.
						     Reset sits last in source order and is pulled in front of Save by .is-editing,
						     which is the only difference between the two arrangements. */ ?>
						<div class="js-fc-cart-edit-bar fc-cart-edit-bar">
							<div class="js-fc-cart-edit-bar__left fc-cart-edit-bar__side">
								<a href="#" class="btn btn-sm btn-secondary fc-cancel-item text-uppercase" style="display: none;" aria-label="Cancel editing cart">
									<i class="fa-solid fa-xmark me-sm-1" aria-hidden="true"></i> <span class="d-none d-sm-inline">Cancel</span>
								</a>
							</div>
							<div class="js-fc-cart-edit-bar__right fc-cart-edit-bar__side">
								<a href="#" class="btn btn-sm btn-orange fc-update-item text-uppercase js-fc-edit-item" aria-label="Change quantity">
									<i class="fa-regular fa-pen-to-square me-sm-1" aria-hidden="true"></i>
									<span class="d-none d-sm-inline">Change QTY</span>
								</a>
								<?php /* Starts dead, like Save Changes: there is nothing to undo until a
								     quantity differs from the one its row loaded with. */ ?>
								<a href="#" class="btn btn-sm btn-dark fc-reset-item text-uppercase disabled" aria-disabled="true" tabindex="-1" style="display: none;">
									<i class="fa-solid fa-rotate-left me-sm-1"></i> <span class="d-none d-sm-inline">Reset</span>
								</a>
							</div>
						</div>

						<div class="fc-view-total-cost-bar">
							<div class="fc-view-total-cost-bar__inner">
								<div class="d-grid gap-2">
									<?php /* No bounce: setAnimation() (core/events.js) re-adds the class on
									         every scroll once the button is in view, and each cart
									         re-render - now one per optional Add to cart / Remove -
									         hands it a fresh element to animate all over again. */ ?>
									<div>
										<button type="submit" class="btn btn-lg btn-submit btn-green js-fc-view-total-cost text-uppercase w-100 text fc-btn-shine">
											<i class="fa-solid fa-cart-shopping me-1"></i>
											View Total Cost!
										</button>
									</div>
								</div>
							</div>
				    	</div>

					</div>

					<div class="col fc-position-relative order-1 mb-md-0 mb-4" id="update_stock-delivery">

						<?php /* offset 0: pinned, this pane's header has to land on the same line as the
						     cart header opposite, which sticks at the top of its own column. At 20
						     it floated 20px lower and the band the two share read as two. */ ?>
						<div data-spy="scroll" data-screen="768" data-offset="0" data-target="#update_stock-delivery">
							<div class="fencing-section__cmp fencing-section__step-label">
						        <div class="step-label">Stock & <span>Delivery</span></div>
						    </div>
				
							<div class="fc-cart-stock-area">
							    <ul class="fc-stock-facts list-unstyled">
							    	<li class="fc-stock-fact">
							    		<span class="fc-stock-fact__icon fc-stock-fact__icon--info" aria-hidden="true"><i class="fa-solid fa-truck"></i></span>
							    		<span class="fc-stock-fact__text">
							    			<span class="fc-stock-fact__label">Approx Delivery Run</span>
							    			<span class="fc-stock-fact__value">2-3 Days</span>
							    		</span>
							    	</li>
							    	<li class="fc-stock-fact">
							    		<span class="fc-stock-fact__icon fc-stock-fact__icon--good" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
							    		<span class="fc-stock-fact__text">
							    			<span class="fc-stock-fact__label">Items in Stock</span>
							    			<span class="fc-stock-fact__value fc-stock-fact__value--good">Yes</span>
							    		</span>
							    	</li>
							    </ul>

							    <?php if ( ! empty($fc_pp_stock['lowStockEnabled']) && trim((string) $fc_pp_stock['lowStockHtml']) !== '' ) : ?>
							    <div class="alert alert-danger fc-step-2-alert fc-alert-gray--low-stock">
							        <h3 class="fc-mb-1"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Low Stock Warning</h3>
							        <?php /* Authored in the settings WYSIWYG and sanitized on save by
							                 PlannerOptionSettings::normalizeStock() - echoed as HTML by design. */ ?>
							        <?php echo $fc_pp_stock['lowStockHtml']; ?>
							    </div>
							    <?php endif; ?>
							</div>

							<div style="clear: both;"></div>
							
							<?php if ( ! empty($fc_pp_stock['orderWithinEnabled']) ) : ?>
							<div class="fc-cart-countdown mb-3">
							    <p>
							        <strong>ORDER WITHIN:</strong><br />
							        <span id="fc-countdown-timer" data-fc-countdown-hours="<?php echo e((string) ($fc_pp_stock['orderWithinHours'] ?? 3)); ?>"></span>
							    </p>
							</div>
							<?php endif; ?>
							
							<div class="fc-stock-delivery-actions d-grid gap-2">
								<button type="button" class="btn btn-lg fc-btn-download-fence btn-outline-dark text-uppercase w-100 fs-6 text" aria-label="Download project plans as PDF">
									<i class="fa-solid fa-download me-2" aria-hidden="true"></i>
									<span>Download Plans</span>
								</button>

								<div class="row g-2">
									<div class="col-lg col-md-12 col-sm">
								        <button type="submit" class="btn btn-submit btn-lg btn-dark text-uppercase w-100 fs-6 text fc-btn-shine">
								            <i class="fa-solid fa-cart-shopping me-2"></i>
								            Order Items Now!
								        </button>
									</div>
									<?php if ($fc_pp_phone !== '') : ?>
									<div class="col-lg col-md-12 col-sm">
								        <a href="tel:<?php echo e(PlannerOptionSettings::stockPhoneHref($fc_pp_phone)); ?>" class="btn btn-lg btn-dark text-uppercase w-100 fs-6 text">
								           	<i class="fa-solid fa-phone me-2"></i> Call <?php echo e($fc_pp_phone); ?>
								        </a>
							       </div>
									<?php endif; ?>
								</div>
							</div>

						</div>

					</div>

				</div>

			</div>


		</div>
	</div>
</div>
<?php /* [END] PRODUCT LIST */ ?>