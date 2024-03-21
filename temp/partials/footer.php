<div class="container-lg">
	<div class="row row align-items-middle">
		<div class="col">
			<div class="mb-5 pb-5 text-secondary small">
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



<!-- Required Libraries -->
<script type="text/javascript" src="<?php echo base_url(); ?>js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/jquery.validate.min.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/jquery-scrollspy.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>

<!-- Scripts -->
<script type="text/javascript" src="<?php echo base_url(); ?>js/main.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/modal.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>js/calc.js?v=<?php echo date('YmdHis'); ?>"></script>

<?php if( !in_uri_segment(demo_stages()) ): ?>
<!-- Chatra {literal} -->
<script>
(function(d, w, c) {
    w.ChatraID = 'zyiAwfgBp6aaDnXK2';
    var s = d.createElement('script');
    w[c] = w[c] || function() {
        (w[c].q = w[c].q || []).push(arguments);
    };
    s.async = true;
    s.src = 'https://call.chatra.io/chatra.js';
    if (d.head) d.head.appendChild(s);
})(document, window, 'Chatra');
</script>
<!-- /Chatra {/literal} -->
<?php endif; ?>