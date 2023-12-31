<?php 
	include('data.php');
	include('temp/fields.php');
	include('helpers.php');
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">

<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">


<div class="fc-loader-overlay" style="display: none;">
	<div class="fc-loader-container">
		<div class="fc-loader">
			<div class="fc-row">
				<div class="fc-col-auto">
					<img src="<?php echo base_url('img/loaders/1.gif'); ?>" width="120">
				</div>
				<div class="fc-col-auto">
					<ul>	
						<li><div class="fc-mb-1"><small style="font-size:30px;">Preparing:</small></div></li>
						<li><i class="fa fa-check fc-mr-1"></i> Creating your plan... </li>
						<li><i class="fa fa-check fc-mr-1"></i> Calculating your materials...</li>
						<li><i class="fa fa-check fc-mr-1"></i> Confirming details...</li>	
						<li></li>		
					</ul>	
				</div>
			</div>
		</div>			
	</div>
</div>


<div class="fencing-container w-side-section" data-tab="1">

	<form method="POST" id="fc-download-form" action="project-plan.php">

	<div class="fc-mb-1">

		<div class="fc-row">
			<div class="fc-col-half">
				<h2 class="fc-header-title">Fencing Calculator</h2>
				<p>Calculate your fence cost and the materials needed.</p>			
			</div>
			<div class="fc-col-half">
				<div class="fc-flex-end">

					<div class="fc-video-bg">
						<i class="fa-solid fa-circle-play fc-mb-1"></i>
						<h4 class="fc-mb-0">How to Use This<br>
						Fence Planner</h4>					
					</div>

				</div>
			</div>
		</div>

	</div>

	<!-- START TABS -->
	<div class="fencing-tabs-container fc-section-step fc-d-none fc-font-2" data-tab="1">	

		<div class="fencing-tabs">

			<div class="fencing-tab-container">

				<div class="fencing-tab fencing-tab-selected fc-d-none">
					<div class="fencing-tab-name">
						<span class="ftm-title">SECTION</span> <span class="fencing-tab-number">1</span>
						<div class="ftm-measurement">10000 mm</div>
					</div>							
				</div>

			</div>

			<a href="#" class="fencing-tab-add fc-d-none">
			&#43; Add Section			
			</a>

		</div>

	</div>
	<!-- END TABS -->

	<div class="fc-section-details">

	
		<div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="1">
			<a href="#" data-tab="1" data-move="2" class="tab-selected">Section Details</a>
		</div>

		<div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="2" style="display:none;">
			<a href="#" data-tab="1" data-move="2" class="">Section Details</a>
			<a href="#" data-tab="2" data-move="1" class="fc-tab-active tab-selected">Project Options</a>	
		</div>

		<div class="fencing-content fc-font-1">


			<div class="fc-section-step" data-tab="1">

				<div class="fc-content-tab-title">
					<span class="fc-tab-title"></span> | <span class="fc-tab-subtitle"></span>
				</div>

				<div class="fencing-section"> 
					
					<buton type="button" class="btn-fc btn-fc-outline-danger btn-fc-sm fc-float-r btn-delete-fence" style="display:none;">
						<i class="fa fa-trash-can"></i>Delete Section
					</buton>

					<div class="step-label">Step <span>01</span></div>
					<h4 class="fencing-content-title">Choose Your Fencing Style</h4>			
				</div>

				<!-- START STYLES -->

				<div class="fencing-section fencing-styles">

					<?php foreach( $fences as $fence ): ?>
					<div class="fencing-style-item" data-title="<?php echo $fence['title']; ?>">
						<div>
							<div class="fencing-style-img">
								<img src="<?php echo $fence['image']; ?>">				
							</div>
							<div class="fencing-style-title">
								<?php echo $fence['title']; ?>
							</div>					
						</div>
					</div>
					<?php endforeach; ?>				
			
				</div>
				<!-- END STYLES -->

				<!-- START MEASUREMENT -->	
				<div class="fc-form-step fc-d-none" data-section="2" style="display: none;">

					<div class="fencing-divider"></div>

					<div class="fencing-section fencing-measurement">

						<div class="fc-row">
							<div class="fc-col-half fc-input-container">

								<div class="step-label">Step <span>02</span></div>
								<h4 class="fencing-content-title fc-mb-2">Enter the overall length (mm)</h4>	

								<div class="fencing-measurement-box"> 
									<span class="fencing-mb-input">
										<input type="text" class="measurement-box-number numeric" value=""> 
										<span>mm</span>				
										<div class="fencing-qty-plus fencing-qty-btn">
											<img src="img/quantity-plus.png" alt="" title="">
										</div>
										<div class="fencing-qty-minus fencing-qty-btn">
											<img src="img/quantity-minus.png" alt="" title="">
										</div>						
									</span>
									<button type="button" class="btn-fc btn-fc-calculate">Calculate</button>
								</div>
								<div class="fc-input-msg error-msg"></div>

							</div>
							<div class="fc-col-half">

								<div class="fc-alert-gray fc-step-2-alert">
									<h3 class="fc-mb-1"><i class="fc-icon fc-icon-capa"></i> Measuring Your Fencing Section</h3>
									<p class="fc-text-gray">Ensure your overall dimension includes the posts each end.
									NOTE: "Panel & Post Options" below will deduct based on options selected.</p>									
								</div>

							</div>
						</div>

					</div>				
				</div>
				<!-- END MEASUREMENT -->

				<div class="fc-form-step fc-d-none fc-position-relative" data-section="3" style="display: none;">
					<div class="fencing-divider"></div>

					<div class="fencing-calculating">
						<div class="fc-calculating-loader">
							<img src="//<?php echo $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; ?>img/loaders/1.gif" width="60">
							<h4 class="fc-text-uppercase">Calculating ...	</h4>					
						</div>
					</div>


					<!-- START DISPLAY RESULT -->	
					<div class="fencing-section"> 
						<div class="step-label">Step <span>03</span></div>
						<h4 class="fencing-content-title fc-mb-2">Configure this fence section</h4>			

						<div>
						
							<a href="#" class="fc-mr-1 fc-zoom-reset" data-zoom="reset">
								<i class="fc-icon fc-icon-arrow-cc"></i>
							</a>

							<a href="#" class="fc-mr-1" style="display: none;">
								<i class="fc-icon fc-rectangle"></i>
							</a>

							<a href="#" class="fc-mr-1 fc-zoom-fence" data-zoom="in">
								<i class="fc-icon fc-magnify-plus"></i>
							</a>

							<a href="#" class="fc-zoom-fence" data-zoom="out">
								<i class="fc-icon fc-magnify-munis"></i>
							</a>

						</div>

					</div>

					<div style="clear:both;"></div>

					<div class="fencing-section fencing-display-result">

						<div class="fencing-result-msg" style="display: none;">
							<p>No Valid Solution. Please adjust Measurements.</p>			
						</div>

						<div class="fencing-panel-items">
							
							<div class="fencing-panel-rail fencing-btn-modal" data-key="rail_options" data-target="#fc-control-modal" style="display:none;"></div>

							<div class="fencing-panel-container"></div>

						</div>

					</div>

					<!-- START PANEL CONTROLS -->	
					<div class="fencing-section fencing-panel-controls"></div>
					<!-- END PANEL CONTROLS -->

				</div>
				<!-- END DISPLAY RESULT -->


				<div class="fencing-divider"></div>
					
			</div>

			<div class="fc-section-step fc-d-none fc-step-4" data-tab="2" style="display: none;">
				
				<div class="fencing-section"> 
					<div class="step-label">Step <span>04</span></div>
					<h4 class="fencing-content-title fc-mb-2">Configure Your Project</h4>

					<div class="fc-card fc-mb-2">
						<div class="fc-card-header fc-bg-dark fc-border-top">
							Colour Options
						</div>
						<div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
						
							<div class="fencing-form-group fc-mb-0">
								<div class="fc-row fc-form-field fc-color-options" data-key="color_options" name="color_options" type="text_option" value="">
								<?php foreach($color_options as $co): ?>
									<div class="fc-col-3">
									    <div class="fc-select-item fc-select fc-mb-0" data-slug="" style="background:<?php echo $co['background_color']; ?>;color:<?php echo $co['text_color']; ?>;">    
									        <p><?php echo $co['title']; ?></p>
									        <span><?php echo $co['sub_title']; ?></span>
									    </div>
									</div>
								<?php endforeach; ?>						
								</div>
							</div>

						</div>
					</div>

					<div class="fc-card">
						<div class="fc-card-header fc-bg-dark fc-border-top">
							Project Notes & Additional Details
						</div>
						<div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
							
							<div class="fc-p-1">
								<div class="fc-row">
									<div class="fc-col-half">
										<textarea name="notes" placeholder="Write your notes here" class="fc-form-control" rows="7"></textarea>
									</div>
								</div>
					
							</div>

						</div>
					</div>


				</div>

			</div>


			<div class="fc-tab-control">

				<div class="fencing-section fc-section-step" data-tab="1">
					<button type="button" class="btn-fc btn-fc-orange fc-btn-next-step fc-btn-step fc-mr-1" disabled data-tab="1" data-move="2"
					>
						<b>NEXT - Select PLAN OPTIONS &#8594;</b>
					</button>

					<button type="button" class="btn-fc btn-fc-outline-default fc-tab-add fencing-tab-add fc-px-2"><b><i class="fa-solid fa-plus"></i> Add Another Section</b></button>
				</div>

				<div class="fencing-section fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
					<button type="button" 
						class="btn-fc btn-fc-orange fc-btn-create-plan fencing-btn-modal fc-mr-1" 
						data-target="#submit-modal" disabled>
							<strong>Create Project Plan</strong><br>
							<small>View Costing, Plan & Materials List</small>
					</button>

					<button type="button" class="btn-fc fc-btn-step btn-fc-outline-default fc-text-uppercase fc-px-3" data-tab="2" data-move="1"><b>Back</b></button>

				</div>

			</div>

			
		</div>

		
	</div>




	<!-- The Modal -->
	<div id="fc-control-modal" class="fencing-modal">

		<div class="fc-modal-frame">
			  <!-- Modal content -->
			  <div class="fencing-modal-content">
			    <span class="fencing-modal-close">&times;</span>
			    <div class="fencing-modal-title fc-font-2">
			    	Options
			    </div>	   
			    <div class="fc-modal-divider"></div>
			    <div class="fencing-modal-body fc-font-1">

			    </div>
			    <div class="fencing-modal-notes"></div>
			  </div>			
		</div>

	</div>



	<div id="submit-modal" class="fencing-modal">
		<div class="fc-modal-frame">
	  <!-- Modal content -->
	  <div class="fencing-modal-content fencing-modal-md">
	    <span class="fencing-modal-close">&times;</span>
	    <div class="fencing-modal-head">
		    <div class="fencing-modal-title">
				<h4>Download Your Project Plans</h4>
		    </div>    	
	    </div>
	    <div class="fencing-modal-body fc-mb-3">

			<div class="fc-alert-gray fc-mb-3">
				<h4>&#127881; Awesome! We’ll email you the plans..</h4>
				<p class="fc-text-gray">Simply enter your details below and we’ll send you your plans to download or print for your reference.</p>
			</div>


				<div class="fc-form-plan" data-formtab="1" style="display: nonex;">

					<div class="fc-progress-container fc-float-r">
						<div class="fc-progress-tabs">
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value"></div>
							<div class="fc-progress-value"></div>
							<div class="fc-progress-value"></div>
						</div>
						<span>1/4</span>				
					</div>

					<h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Project Details</h2>	

					<div class="fc-form-group fc-mb-1">
						<div class="fc-row-container">
							<div class="fc-col-half">
								<div class="fc-label-group">
									<label class="fc-form-label">Name <span class="fc-text-danger">*</span></label>
									<input type="text" name="name" id="name" class="fc-form-control" placeholder="Your Name" required>					
								</div>
							</div>
							<div class="fc-col-half">
								<div class="fc-label-group">
									<label class="fc-form-label">Mobile <span class="fc-text-danger">*</span></label>
									<input type="text" name="mobile" id="mobile" class="fc-form-control" placeholder="Your phone number" required>							
								</div>		
							</div>
						</div>						
					</div>

					<div class="fc-form-group fc-mb-1">
						<div class="fc-label-group">
							<label class="fc-form-label">Email <span class="fc-text-danger">*</span></label>
							<input type="text" name="email" id="email" class="fc-form-control no-space" placeholder="Your Email" required>	
						</div>
					</div>

					<div class="fc-form-group fc-mb-1">
						<div class="fc-row-container">
							<div class="fc-col-half">
								<div class="fc-label-group">
									<label class="fc-form-label">Post Code <span class="fc-text-danger">*</span></label>
									<input type="text" name="postcode" id="postcode" class="fc-form-control" placeholder="Post Code" required>					
								</div>		
							</div>
							<div class="fc-col-half">
								<div class="fc-label-group">
									<label class="fc-form-label">State <span class="fc-text-danger">*</span></label>
									<select name="state" class="fc-form-control" required>
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
							</div>
						</div>
					</div>

					<p class="fc-mb-2 fc-text-gray"><span class="fc-text-danger">*</span> = Required</p>

			    	<div class="fc-form-plan-action">
				    	<button type="button" class="btn-fc fc-btn-form-step fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b" data-move="2"><b>Next > Time Frame</b></button>				
				    </div>
				</div>

				<div class="fc-form-plan" data-formtab="2" style="display: none;">

					<div class="fc-progress-container fc-float-r">
						<div class="fc-progress-tabs">
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value"></div>
							<div class="fc-progress-value"></div>
						</div>
						<span>2/4</span>				
					</div>

					<h2 class="fc-text-uppercase fc-font-2 fc-mb-2">When Do You Need The Materials?</h2>

					<div class="fc-form-group fc-form-check fc-mb-3">
						<label class="fc-mb-1">
							<input type="radio" name="timeframe" value="asap" class="fc-mr-1" required>
							ASAP - Within 24hrs
						</label>

						<label class="fc-mb-1">
							<input type="radio" name="timeframe" value="soon" class="fc-mr-1" required>
							SOON - This Week
						</label>
						<label class="fc-mb-1">
							<input type="radio" name="timeframe" value="later" class="fc-mr-1" required>
							LATER - This Month
						</label>
						<label class="fc-mb-1">
							<input type="radio" name="timeframe" value="looking" class="fc-mr-1" required>
							NIL - Just Looking
						</label>
					</div>

					<div class="fc-form-plan-action">
						<button type="button" class="btn-fc fc-btn-form-step btn-fc-outline-default fc-text-uppercase fc-w-b" data-move="1"><b>Back</b></button>	
						<button type="button" class="btn-fc fc-btn-form-step fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b" data-move="3"><b>Next > Install Options</b></button>					
					</div>
				</div>

				<div class="fc-form-plan" data-formtab="3" style="display: none;">

					<div class="fc-progress-container fc-float-r">
						<div class="fc-progress-tabs">
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value"></div>
						</div>
						<span>3/4</span>				
					</div>

					<h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Do You Need An Installer?</h2>

					<div class="fc-form-group fc-form-check fc-mb-3">
						<label class="fc-mb-1">
							<input type="radio" name="installer" value="yes" class="fc-mr-1" required>
							YES - I need an installer
						</label>
						<label class="fc-mb-1">
							<input type="radio" name="installer" value="no" class="fc-mr-1" required>
							NO - I can install it myself <span class="fc-label-badge fc-ml-1">Cheaper</span>
						</label>
					</div>

					<div class="fc-form-plan-action">
						<button type="button" class="btn-fc fc-btn-form-step btn-fc-outline-default fc-text-uppercase fc-w-b" data-move="2"><b>Back</b></button>
						<button type="button" class="btn-fc fc-btn-form-step fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b" data-move="4"><b>Next > Needed Options</b></button>
					</div>
				</div>

				<div class="fc-form-plan form-tab-4" data-formtab="4" style="display: none;">

					<div class="fc-progress-container fc-float-r">
						<div class="fc-progress-tabs">
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value pt-complete"></div>
							<div class="fc-progress-value pt-complete"></div>
						</div>
						<span>4/4</span>				
					</div>

					<h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Anything Else We Can Help You With?</h2>
					
					<div class="fc-form-group fc-mb-2">
						<div class="fc-row-container">

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<img class="fc-rounded" src="img/plans/1.png">								
										<input type="checkbox" name="extra[]" value="Pool Covers">
									</label>
									<p class="fc-text-center">Pool Covers</p>
								</div>
							</div>

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<img class="fc-rounded" src="img/plans/2.png">								
										<input type="checkbox" name="extra[]" value="Pump Enclosure">
									</label>
									<p class="fc-text-center">Pump Enclosure</p>
								</div>
							</div>

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<img class="fc-rounded" src="img/plans/3.jpg">								
										<input type="checkbox" name="extra[]" value="Decking">
									</label>
									<p class="fc-text-center">Decking</p>
								</div>
							</div>

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<img class="fc-rounded" src="img/plans/4.png">								
										<input type="checkbox" name="extra[]" value="Pergola">
									</label>
									<p class="fc-text-center">Pergola</p>
								</div>
							</div>

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<img class="fc-rounded" src="img/plans/5.png">								
										<input type="checkbox" name="extra[]" value="Shed">
									</label>
									<p class="fc-text-center">Shed</p>
								</div>
							</div>

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<img class="fc-rounded" src="img/plans/6.png">								
										<input type="checkbox" name="extra[]" value="Outdoor Furniture">
									</label>
									<p class="fc-text-center">Outdoor Furniture</p>
								</div>
							</div>

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<img class="fc-rounded" src="img/plans/7.png">								
										<input type="checkbox" name="extra[]" value="Outdoor Kitchen">
									</label>
									<p class="fc-text-center">Outdoor Kitchen</p>
								</div>
							</div>

							<div class="fc-col-3">
								<div class="fc-form-check-img fc-form-check-empty fc-rounded fc-mb-1">
									<label class="fc-form-check fc-mb-1">
										<div class="fc-empty-img">
											Nothing Extra<br>
											Just Fencing
										</div>								
										<input type="radio" name="nothing_extra" value="nothing">
									</label>
									<p class="fc-text-center">NIL - Just Looking</p>
								</div>
							</div>
																					
						</div>						
					</div>

					<div class="fc-form-plan-action">
						<button type="button" class="btn-fc fc-btn-form-step btn-fc-outline-default fc-text-uppercase fc-w-b" data-move="3"><b>Back</b></button>
						<button type="submit" class="btn-fc fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b"><b>Done > View Plans & Costs</b></button>
					</div>
				</div>
				


	    </div>

	  </div>
	</div>
	
	</div>
	</form>

</div>


<script type="text/javascript" src="jquery-3.3.1.min.js"></script>

<script type="text/javascript">
var fc_data = <?php echo json_encode($fences); ?>;
var fc_mbn  = 11000;

$('.measurement-box-number').val(fc_mbn);

</script>


<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/calc.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/jquery-scrollspy.min.js"></script>

<script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>

<script type="text/javascript">


$(window).on('scroll', function(){

	var ha = $('.fc-content-tab-title').height(),
		hb = $('.fc-header-tab').height();

	if( $(window).scrollTop() > ($('.fc-content-tab-title').offset().top) ) {
		$('.fencing-tabs-container').addClass('fc-sticky');
	} else {
		$('.fencing-tabs-container').removeClass('fc-sticky');
	}

});

// https://jqueryvalidation.org/validate/
$("#fc-download-form").validate({	
	rules: {
		email: {
			required: true,
			//email: true
		},
	},
	messages: {
		timeframe: "Please select an option.",
		installer: "Please select an option.",
	},
	submitHandler: function(form) {

		var tab = $('.fc-form-plan:visible').attr('data-formtab'),
				move = $('.fc-form-plan:visible').find('.fc-btn-next').attr('data-move');

		if( tab == 4 ) {

			var count = 0;

			$('.fc-loader-overlay').show();

			submit_fence_planner();

			setTimeout(function(){
			    $('.fc-loader ul li').each(function(i) {
			        var $this = $(this);
			        setTimeout(function(){
			           $this.addClass('fc-text-success');
			           count++;

			           if( count == 5 ) {
				    		window.onbeforeunload = function() {}
			           }
			           
			        }, 1000 * i);


			    });

			}, 1000);


		} else {
			$('.fc-form-plan').hide();
			$('[data-formtab="'+move+'"]').show();
		}

	}
});
	


// localStorage.clear();

if( location.host == 'localhost' ) {

	// $('#submit-modal').show();
	// $('#fc-control-modal').show();

	// $('.fc-d-none').show();

	// localStorage.clear();

	function test_data() {

	    var index = $('.fencing-tab').length;

		$('.fencing-style-item:not(.fsi-selected)').click();

	    $('.measurement-box-number').val( 10999 + index );
	    $('.btn-fc-calculate').click();

	    if( index < 1 ) {

	        setTimeout(function() {
	            add_new_fence_section();
	            test_data();
	        }, index);
	    }

	}

	test_data();	
} 
</script>

