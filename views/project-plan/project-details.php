<!-- START PROJECT DETAILS -->
<div class="fencing-section fencing-section--has-border br-tl-0 br-tr-md-0">

	<!-- [START] Step 5 | Edit Controls -->
	<div class="fencing-section__cmp fencing-section__step-label">
		<div class="step-label">Your Project <span> Details</span></div>

		<div>
			
			<button type="button" data-action="edit" class="btn btn-sm fc-btn-edit btn-orange text-uppercase">
				<i class="fa-regular fa-pen-to-square me-1"></i>
				<b>Edit Details</b>
			</button>

			<div class="js-project-details-controls project-details-controls fc-d-none">
				
				<button type="button" data-action="update" class="btn btn-sm fc-btn-edit btn-orange text-uppercase btn-orange fc-w-700">
					<i class="fa-regular fa-pen-to-square me-1"></i>
					<b>Save</b>
				</button>

				<button type="button" 
					class="btn btn-sm fc-btn-reset btn-secondary text-uppercase" 
					style="display:none;">
						<i class="fa-solid fa-rotate-left me-1"></i> <b>Reset</b>
				</button>

			</div>

		</div>
	</div>
	<!-- [END] Step 5 | Edit Controls -->

	<div class="your-project-details">
		<?php include 'views/sections/your-project-details.php'; ?>						
	</div>

	<!-- END PROJECT DETAILS -->
</div>
