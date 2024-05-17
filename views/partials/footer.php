<div class="container-lg">
	<div class="row row align-items-middle">
		<div class="col">
			<div class="mb-5 pb-5 text-secondary small">
				<?php echo config()->app->name; ?> <span class="app-version"><?php echo config()->app->version; ?></span>
			</div>			
		</div>
		<div class="col">
			<a href="" data-bs-toggle="modal" data-bs-target="#clear-all-data" class="btn btn-sm border text-muted float-end"> Clear All</a>
			
		</div>
	</div>
</div>


<?php if( in_uri_segment(demo_stages()) ): ?>
<span class="badge bg-danger text-white text-uppercase p-1 is-demo">Test<br> Version</span>
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
<script type="text/javascript" src="<?php echo load_file('assets/js/jquery-3.3.1.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo load_file('assets/js/jquery.validate.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo load_file('assets/js/jquery-scrollspy.min.js'); ?>"></script>

<!-- Plugins -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo config()->apikey->google_map; ?>&libraries=places&loading=async&callback=initAutocompleteAddress"
    async defer></script>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>

<!-- Scripts -->
<script type="text/javascript" src="<?php echo load_file('assets/js/main.js'); ?>"></script>
<script type="text/javascript" src="<?php echo load_file('assets/js/modal.js'); ?>"></script>
<script type="text/javascript" src="<?php echo load_file('assets/js/helpers.js'); ?>"></script>
<script type="text/javascript" src="<?php echo load_file('assets/js/functions.js'); ?>"></script>
<script type="text/javascript" src="<?php echo load_file('assets/js/events.js'); ?>"></script>
<script type="text/javascript" src="<?php echo load_file('assets/js/calc.js'); ?>"></script>

<?php include 'assets/js/fences/fences.php'; ?>

<?php if( !in_uri_segment(demo_stages()) ): ?>
<!-- Chatra {literal} -->
<script>
(function(d, w, c) {
    w.ChatraID = '<?php echo config()->apikey->chatra; ?>';
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