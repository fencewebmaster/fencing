<?php 
	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
?>
<div class="fc-card fc-project-details">

	<div id="update_details-section">

		<div class="row">
			<div class="col-md">
				<div class="fc-table-rounded-border fc-mb-2 fc-position-relative">
				
					<table class="fc-table fc-table-customer">
						<tbody>
							<tr>
								<td width="100">Name</td>
								<td>
									<span><?php echo @$info['name']; ?></span>
									<div class="fc-form-group has-clear">
										<input type="text" name="name" value="<?php echo @$info['name']; ?>" class="form-control">
									</div>
								</td>
							</tr>
							<tr>
								<td>Contact</td>
								<td>
									<span><?php echo @$info['mobile']; ?></span>
									<div class="fc-form-group has-clear">
										<input type="number" name="mobile" value="<?php echo @$info['mobile']; ?>" class="form-control">
									</div>
								</td>
							</tr>
							<tr>
								<td>Email</td>
								<td>
									<span><?php echo @$info['email']; ?></span>
									<div class="fc-form-group has-clear">
										<input type="email" name="email" value="<?php echo @$info['email']; ?>" class="form-control no-space" required>
									</div>
								</td>
							</tr>
							<tr>
								<td>State</td>
								<td>
									<span><?php echo @$info['state'] ? fc_state(@$info['state']) : ''; ?></span>
									<div class="fc-form-group has-clear">
										<select name="state" class="form-control" required>
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
									<div class="fc-form-group has-clear">
										<input type="text" name="postcode" value="<?php echo @$info['postcode']; ?>" class="form-control">
									</div>
								</td>
							</tr>
							<tr>
								<td>Address</td>
								<td>
									<span><?php echo @$info['address']; ?></span>
									<div class="fc-form-group has-clear">
										<input type="text" name="address" value="<?php echo @$info['address']; ?>" class="form-control">
									</div>
								</td>
							</tr>
						</tbody>
					</table>

				</div>


				<div class="fc-table-rounded-border fc-mb-2 project-details--edit"> 

					<table class="fc-table">
						<tr>
							<td width="180">Fence Type</td>
							<td><?php echo implode(', ', selected_fences($fences, 'name')); ?></td>
						</tr>
						<tr>
							<td>When Needed</td>
							<td><?php echo @$info['timeframe'] ? fc_timeframe(@$info['timeframe']) : ''; ?></td>
						</tr>
						<tr>
							<td>DIY OR INSTALL</td>
							<td><?php echo @$info['installer'] ? fc_installer(@$info['installer']) : ''; ?></td>
						</tr>
						<tr>
							<td>Other Items Needed</td>
							<td>
								<?php  
								$extra = is_array(@$info['extra']) ? $info['extra'] : convert_inputs(@$info['extra']);

								if( $extra ): ?>
									<?php 
									echo get_items('fc_extra_needed', $extra); ?>
								<?php else: ?>
									Nothing Extra, Just Fencing															
								<?php endif; ?>
							</td>
						</tr>
					</table>

				</div>



			</div>

			<div class="col-md">
				<div class="fc-card fc-mb-2">

					<div class="fc-card-header fc-bg-dark fc-border-top">
						Color Options
					</div>


					<div class="fc-table-rounded-border fc-rounded-top-none fc-mb-2">
					
						<table class="fc-table fc-table--colour project-details--edit">
							<thead>
								<tr>
									<td width="100" class="valign-top">Colour</td>
									<td>

									<?php $colors = is_array(@$info['color']) ? $info['color'] : convert_inputs($info['color']); ?>

									<div class="row">
									<?php foreach( $colors as $cd_k => $color_data ):
	
										$color_fence = $color_data['fence'];
										$color_value = $color_data['color'];

										$color = fc_color($color_value);
									?>

										<div class="col fc-color-options" data-slug="<?php echo @$color_data['fence']; ?>">						
										<input type="hidden" class="input-fence" name="color[<?php echo $cd_k; ?>][fence]" value="<?php echo @$color_data['fence']; ?>">
										<input type="hidden" class="input-color" name="color[<?php echo $cd_k; ?>][color]" value="<?php echo $color_value; ?>">

										<div style="background:<?php echo @$color['background_color']; ?>;color:<?php echo @$color['text_color']; ?>;border:	2px solid var(--fc-gray);max-width:250px;" class="fc-colour-item fc-border fc-p-1 js-color_options-color_code">
											<div style="color: <?php echo @$color['text_color']; ?>">
												<div><?php echo $fences[@$color_data['fence']]['title']; ?></div>
												<hr class="my-2">
												<strong class="js-color_options-title"><?php echo @$color['title']; ?></strong><br />
												<span class="js-color_options-subtitle"><?php echo @$color['sub_title']; ?></span>
											</div>
										</div>
									
									</div>

									<?php endforeach; ?>
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

						<div class="m-3">

							<table class="fc-table fc-table-customer">
								<tr>
									<td>
										
										<span><?php echo @$info['notes']; ?></span>
										<div class="fc-form-group has-clear">
												<textarea name="notes" placeholder="Write your notes here" class="form-control" rows="5"><?php echo @$info['notes']; ?></textarea>
										</div>
									</td>
								</tr>
							</table>

						</div>
					</div>

				</div>

			</div>
		</div>
	</div>
</div>