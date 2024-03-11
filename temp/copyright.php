 <div class="my-3 text-center text-secondary small">Fencing Calculator v<?php echo get_version(); ?></div>


<?php if( in_uri_segment(demo_stages()) ): ?>
<span class="badge bg-danger text-white text-uppercase is-demo">Demo<br> Version</span>

<style type="text/css">
.is-demo {
	position: fixed;
	bottom: 10px;
	z-index: 10;
	left: 10px;	
} 	
</style>
<?php endif; ?>