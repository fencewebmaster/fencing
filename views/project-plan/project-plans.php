<!-- [START] PROJECT PLAN -->
<div id="project-plans-section" class="fencing-section fencing-section--has-border p-3 pb-0">
	<div class="fc-card">

		<div class="fc-row-flex">
			<div class="fc-col-6">
				
				<!-- [START] Label -->
				<div class="fencing-section__cmp fencing-section__step-label">
					<div class="step-label">Project <span>Plans</span></div>
				</div>
				<!-- [END] Label -->

			</div>
			<div class="fc-col-6 fc-flex-end">
				
				<div class="quote-id-card">
					<div class="qic-head">Your Quote ID</div>
					<div class="qic-body btn-copy-link" data-id="quote-id">
						<div id="quote-id"><?php echo @$_SESSION['planner_id']; ?></div>
					</div>
				</div>

			</div>
		</div>

		<div>
			<div id="fc-fence-list" class="pb-1"></div>
		</div>

	</div>
	<!-- [END] PROJECT PLAN -->
</div>