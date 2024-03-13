<div class="container-lg">
	<div class="row row align-items-middle">
		<div class="col">
			<div class="mb-5 text-secondary small">
				Fencing Calculator v<?php echo get_version(); ?>
			</div>			
		</div>
		<div class="col">
			<a href="" data-bs-toggle="modal" data-bs-target="#clear-all-data" class="btn btn-sm border text-muted float-end"> Clear All</a>
			
		</div>
	</div>
</div>

<?php if( in_uri_segment(demo_stages()) ): ?>
<span class="badge bg-danger text-white text-uppercase p-1 is-demo">Demo<br> Version</span>

<style type="text/css">
.is-demo {
	position: fixed;
	bottom: 10px;
	z-index: 10;
	left: 10px;	
	font-size: 10px;
} 	
</style>
<?php endif; ?>



<div class="modal fade" id="clear-all-data" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header fw-bold">
                <h5 class="modal-title text-uppercase fw-bold" id="exampleModalLabel">Clear all</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to clear all data and reset the form?
            </div>
			<div class="modal-footer">
				<a href="<?php echo base_url('?action=clear-all'); ?>" class="btn btn-orange text-uppercase px-3">
				    <i class="fa fa-check me-1"></i>
				    <strong>Confirm</strong>
				</a>
			</div>
        </div>
    </div>
</div>
