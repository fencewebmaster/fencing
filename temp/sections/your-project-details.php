<?php 
	$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
?>
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
									<span><?php echo @$info['state'] ? fc_state(@$info['state']) : ''; ?></span>
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


				<div class="fc-table-rounded-border fc-mb-2 project-details--edit"> 

					<table class="fc-table">
						<tr>
							<td width="180">Fence Type</td>
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
							<td>
								<?php  
								if( is_array(@$info['extra']) ): ?>
									<?php echo get_items('fc_extra_needed', $info['extra']); ?>
								<?php else: ?>
									Nothing Extra, Just Fencing															
								<?php endif; ?>
							</td>
						</tr>
					</table>

				</div>


				<div class="js-project-details-controls project-details-controls fc-d-none">
					<button type="button" data-action="update" class="btn-fc fc-btn-edit btn-fc-outline-light fc-text-uppercase btn-fc-orange fc-w-700 fc-float-r" >
						<i class="fa-solid fa-pencil"></i>
						<span>Save</span>
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
					
						<table class="fc-table fc-table--colour project-details--edit">
							<thead>
								<tr>
									<td width="100" class="valign-top">Colour</td>
									<td>
										<?php 
											$color_value = @$info['color']['value'];
											$color = fc_color($color_value);
										?>
										<input type="hidden" name="color[value]" value="<?php echo $color_value; ?>">

										<div style="background:<?php echo @$color['background_color']; ?>;color:<?php echo @$color['text_color']; ?>;border:	2px solid var(--fc-gray);max-width:250px;" class="fc-colour-item fc-border fc-p-1 js-color_options-color_code">
											<br>   
											<p style="color: <?php echo @$color['text_color']; ?>"><strong class="js-color_options-title"><?php echo @$color['title']; ?></strong><br />
											<span class="js-color_options-subtitle"><?php echo @$color['sub_title']; ?></span></p>
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

							<table class="fc-table fc-table-customer">
								<tr>
									<td>
										
										<span><?php echo @$info['notes']; ?></span>
										<div class="fc-form-group">
												<textarea name="notes" placeholder="Write your notes here" class="fc-form-control" rows="5"><?php echo @$info['notes']; ?></textarea>
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