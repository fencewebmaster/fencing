<?php 
use Fc\Admin\Helpers\ArrayHelper;
use Fc\Admin\Services\CartBuilderService;
use Fc\Admin\Services\FenceCatalogService;

	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
?>
<div class="fc-card fc-project-details">

	<div id="update_details-section">

		<div class="row">
			<div class="col-md">
				<div class="fc-card">
				<div class="fc-card-header fc-bg-dark fc-border-top">
					Customer Details
				</div>
				<div class="fc-table-rounded-border fc-mb-2 fc-position-relative">
				
					<table class="fc-table fc-table-customer">
						<tbody>
							<tr>
								<td width="100">Name</td>
								<td>
									<span><?php echo e((string) @$info['name']); ?></span>
									<div class="fc-form-group has-clear">
										<input type="text" name="name" value="<?php echo e((string) @$info['name']); ?>" class="form-control">
									</div>
								</td>
							</tr>
							<tr>
								<td width="100">Contact</td>
								<td>
									<span><?php echo e((string) @$info['mobile']); ?></span>
									<div class="fc-form-group has-clear fc-mobile-group position-relative">
										<input type="tel" name="mobile" value="<?php echo e((string) @$info['mobile']); ?>" class="form-control no-space numeric-only input-mobile" phone-format="9999 999 999" autocomplete="off" minlength="7" required>   
						                <i class="au-flag-icon"></i>
									</div>
								</td>
							</tr>
							<tr>
								<td width="100">Email</td>
								<td>
									<span><?php echo e((string) @$info['email']); ?></span>
									<div class="fc-form-group has-clear">
										<input type="email" name="email" value="<?php echo e((string) @$info['email']); ?>" class="form-control no-space" required>
									</div>
								</td>
							</tr>
							<tr>
								<td width="100">Address</td>
								<td>
									<span><?php echo e((string) @$info['address']); ?></span>
									<div class="fc-form-group has-clear">
										<input type="text" id="address" name="address" value="<?php echo e((string) @$info['address']); ?>" class="form-control">
									</div>
								</td>
							</tr>
							<!-- State and Post Code pair up into one row on mobile; the class is the
							     hook, the 50% split is in the stylesheet. -->
							<tr class="fc-cd-row--half">
								<td width="100">State</td>
								<td>
									<span><?php echo @$info['state'] ? fc_state(@$info['state']) : ''; ?></span>
									<div class="fc-form-group has-clear">
										<select id="state" name="state" class="form-control" required>
											<option value="">Select an option…</option>
											<?php foreach( fc_state() as $state_k => $state_v ): ?>
											<option value="<?php echo $state_k; ?>" <?php echo @$info['state']==$state_k ? 'selected': ''; ?>><?php echo $state_v; ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</td>
							</tr>
							<tr class="fc-cd-row--half">
								<td width="100">Post Code</td>
								<td>
									<span><?php echo e((string) @$info['postcode']); ?></span>
									<div class="fc-form-group has-clear">
										<input type="text" id="postcode" name="postcode" value="<?php echo e((string) @$info['postcode']); ?>" class="form-control">
									</div>
								</td>
							</tr>
						</tbody>
					</table>

				</div>
				</div>

				<div class="fc-card fc-mb-2">
				
					<div class="fc-card-header fc-bg-dark fc-border-top">
						Project Notes & Additional Details
					</div>
					
					<div class="fc-card-body fc-border-bottom fc-p-0 fc-border">

						<div class="m-3">

							<table class="fc-table fc-table-customer">
								<tr>
									<td>
										
										<span><?php echo trim((string) @$info['notes']) !== '' ? e((string) $info['notes']) : '<span class="text-muted">No notes added.</span>'; ?></span>
										<div class="fc-form-group has-clear">
												<textarea name="notes" placeholder="Write your notes here" class="form-control" rows="5"><?php echo e((string) @$info['notes']); ?></textarea>
										</div>
									</td>
								</tr>
							</table>

						</div>
					</div>

				</div>


			</div>

			<div class="col-md">
				<div class="fc-card fc-mb-2">

					<div class="fc-card-header fc-bg-dark fc-border-top">
						Color Options
					</div>

					<div class="fc-edit-zone">

					<div class="fc-table-rounded-border fc-rounded-top-none fc-mb-2">
					
						<table class="fc-table fc-table--colour project-details--edit">
							<tbody>
								<tr>
									<td class="fc-table--colour__slick-cell">

									<p class="fc-project-details-edit-hint small mb-2 fc-d-none" role="status">Click items below to edit</p>

									<?php
									$colors = [];
									if ( isset( $info['color'] ) && $info['color'] !== '' && $info['color'] !== null ) {
										$colors = is_array( $info['color'] ) ? $info['color'] : CartBuilderService::convertInputs( $info['color'] );
									}
									if ( ! is_array( $colors ) ) {
										$colors = [];
									}
									if ( empty( $colors ) && ! empty( $info['project_plans'] ) ) {
										$pp_raw = $info['project_plans'];
										$pp     = is_array( $pp_raw ) ? $pp_raw : json_decode( $pp_raw, true );
										if ( is_array( $pp ) && ! empty( $pp['color'] ) && is_array( $pp['color'] ) ) {
											$colors = $pp['color'];
										}
									}
									?>

									<div class="fc-project-plan-color-slick-area fc-project-plan-color-slick-pending">
										<div class="fc-project-plan-color-skeleton js-fc-project-plan-color-skeleton" aria-busy="true" aria-hidden="true">
											<div class="fc-color-options-skeleton__track">
												<?php for ( $sk_i = 0; $sk_i < 6; $sk_i++ ) : ?>
												<div class="fc-color-options-skeleton__cell">
													<div class="fc-color-options-skeleton__tile">
														<span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--row1"></span>
														<span class="fc-color-options-skeleton__divider" aria-hidden="true"></span>
														<span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--row2"></span>
														<span class="fc-color-options-skeleton__line fc-color-options-skeleton__line--row3"></span>
													</div>
												</div>
												<?php endfor; ?>
											</div>
										</div>
										<div class="fc-project-plan-color-slick js-fc-project-plan-color-slick">
									<?php foreach( $colors as $cd_k => $color_data ):
	
										$color_fence = $color_data['fence'];
										$color_value = $color_data['color'];

										$color = fc_color($color_value);
										$_fence_slug = isset( $color_data['fence'] ) ? $color_data['fence'] : '';
										$_fence_title = ( $_fence_slug !== '' && isset( $fences[ $_fence_slug ]['title'] ) )
											? $fences[ $_fence_slug ]['title']
											: $_fence_slug;
										$_fence_section_count = FenceCatalogService::plannerSectionCountForFenceSlug( $_fence_slug );
									?>

											<div class="fc-project-plan-color__slide">
												<div class="fc-color-options" data-slug="<?php echo e($_fence_slug); ?>">
													<input type="hidden" class="input-fence" name="color[<?php echo e((string) $cd_k); ?>][fence]" value="<?php echo e($_fence_slug); ?>">
													<input type="hidden" class="input-color" name="color[<?php echo e((string) $cd_k); ?>][color]" value="<?php echo e((string) $color_value); ?>">

													<div style="background:<?php echo e((string) @$color['background_color']); ?>;color:<?php echo e((string) @$color['text_color']); ?>;border:	2px solid var(--fc-gray);max-width:250px;" class="fc-colour-item fc-border fc-p-1 js-color_options-color_code">
														<div style="color: <?php echo e((string) @$color['text_color']); ?>">
															<div><?php echo e((string) $_fence_title); ?></div>
															<hr class="my-2">
															<strong class="js-color_options-title"><?php echo e((string) @$color['title']); ?></strong><br />
															<span class="js-color_options-subtitle"><?php echo e((string) @$color['sub_title']); ?></span>
															<?php if ( $_fence_section_count > 0 ) : ?>
															<div class="fc-project-plan-color-sections"><?php echo (int) $_fence_section_count; ?> section<?php echo $_fence_section_count === 1 ? '' : 's'; ?></div>
															<?php endif; ?>
														</div>
													</div>
												</div>
											</div>

									<?php endforeach; ?>
										</div>
									</div>

									<?php if ( empty( $colors ) ) : ?>
									<p class="fc-project-details-empty">No colour selections yet.</p>
									<?php endif; ?>

									</td>
								</tr>
							</tbody>
						</table>

					</div>
					</div>

				</div>

				<div class="fc-edit-zone">
					<div class="fc-card-header fc-bg-dark fc-border-top">
						Fence Details
					</div>

				<div class="fc-table-rounded-border fc-mb-2 project-details--edit"> 

					<p class="fc-project-details-edit-hint small fc-d-none" role="status">Click items below to edit</p>

					<table class="fc-table">
						<tr>
							<td width="180">Fence Type</td>
							<td>
								<?php
								$fence_types_rows = FenceCatalogService::fenceSectionTypesWithCounts( $fences );
								if ( ! empty( $fence_types_rows ) ) :
								?>
								<ul class="fc-project-details-value-list mb-0 ps-3">
									<?php foreach ( $fence_types_rows as $ft_row ) : ?>
									<li><?php echo e((string) $ft_row['name']); ?> <b>x <?php echo (int) $ft_row['count']; ?></b></li>
									<?php endforeach; ?>
								</ul>
								<?php else : ?>
								<span class="text-muted">—</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td>When Needed</td>
							<td><?php echo @$info['timeframe'] ? fc_timeframe(@$info['timeframe']) : '<span class="text-muted">&mdash;</span>'; ?></td>
						</tr>
						<tr>
							<td>Other Items Needed</td>
							<td>
								<?php
								$nothing_extra = isset( $info['nothing_extra'] ) ? (string) $info['nothing_extra'] : '';
								$extra = is_array( @$info['extra'] ) ? $info['extra'] : CartBuilderService::convertInputs( @$info['extra'] );
								if ( ! is_array( $extra ) ) {
									if ( is_string( $extra ) && trim( $extra ) !== '' && trim( $extra ) !== 'nothing' ) {
										$extra = array_filter( array_map( 'trim', explode( ',', $extra ) ) );
									} else {
										$extra = array();
									}
								}
								if ( $nothing_extra === 'nothing' || ( empty( $extra ) && in_array( (string) @$info['extra'], array( 'nothing', '[]', '' ), true ) ) ) :
								?>
								Nothing Extra, Just Fencing
								<?php elseif ( ! empty( $extra ) ) : ?>
								<ul class="fc-project-details-value-list mb-0 ps-3">
									<?php echo ArrayHelper::mapCallable( 'fc_extra_needed', $extra, true ); ?>
								</ul>
								<?php else : ?>
								Nothing Extra, Just Fencing
								<?php endif; ?>
							</td>
						</tr>
					</table>

				</div>
				</div>

			</div>
		</div>

			<div class="text-end js-project-details-footer">
				<button type="button" data-action="edit" class="btn btn-sm fc-btn-edit btn-orange text-uppercase" aria-label="Edit details">
					<i class="fa-regular fa-pen-to-square me-1" aria-hidden="true"></i>
					<b class="">Edit Details</b>
				</button>

				<div class="js-project-details-controls project-details-controls project-details-controls--edit-bar fc-d-none mt-2">
					<button type="button" class="btn btn-sm btn-secondary text-uppercase fc-btn-cancel-project-details">
						<i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>
						Cancel
					</button>
					<div class="project-details-controls__right">
						<button type="button" data-action="update" class="btn btn-sm fc-btn-edit btn-orange text-uppercase btn-orange fc-w-700">
							<i class="fa-regular fa-pen-to-square me-1"></i>
							<b>Save</b>
						</button>

						<button type="button" class="btn btn-sm fc-btn-reset btn-dark text-uppercase" style="display:none;">
							<i class="fa-solid fa-rotate-left me-1"></i> <b>Reset</b>
						</button>
					</div>
				</div>
			</div>
	</div>
</div>