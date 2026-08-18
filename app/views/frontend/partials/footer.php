<div class="container-lg">
    <div class="row row align-items-middle">
        <div class="col">
            <?php
            require_once dirname(__DIR__, 4) . '/app/src/Services/BrandingSettings.php';
            require_once dirname(__DIR__, 4) . '/app/src/Services/AppConfigService.php';
            require_once dirname(__DIR__, 4) . '/app/src/Services/SiteRegistryService.php';
            require_once dirname(__DIR__, 4) . '/app/src/Helpers/UrlHelper.php';
            require_once dirname(__DIR__, 4) . '/app/src/Helpers/AssetHelper.php';
            $fcBranding = \Fc\Admin\Services\BrandingSettings::get();
            ?>
            <div class="mb-5 pb-5 text-secondary small">
                <?php echo htmlspecialchars($fcBranding['appName'], ENT_QUOTES, 'UTF-8'); ?>
                <span class="app-version"><?php echo htmlspecialchars($fcBranding['version'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>          
        </div>
        <div class="col">
            <a href="" data-bs-toggle="modal" data-bs-target="#clear-all-data" class="btn btn-sm border text-muted float-end"> Clear All</a>
            
        </div>
    </div>
</div>


<?php if( \Fc\Admin\Helpers\UrlHelper::inUriSegment(\Fc\Admin\Services\SiteRegistryService::demoStages()) ): ?>
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
				<a href="<?php echo \Fc\Admin\Helpers\UrlHelper::baseUrl('?action=clear-all'); ?>" class="btn btn-orange text-uppercase px-3">
				    <i class="fa fa-check me-1"></i>
				    <strong>Confirm</strong>
				</a>
			</div>
        </div>
    </div>
</div>

<!-- Required Libraries -->
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/vendor/jquery-3.3.1.min.js'); ?>"></script>
<script defer src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js" crossorigin="anonymous"></script>
<script defer src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js" crossorigin="anonymous"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/vendor/jquery.validate.min.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/vendor/jquery-scrollspy.min.js'); ?>"></script>

<!-- Plugins -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo \Fc\Admin\Services\AppConfigService::all()->apikey->google_map; ?>&libraries=places&loading=async&callback=initAutocompleteAddress"
    async defer></script>

<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>



<!-- Scripts -->
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/helpers.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/main.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/vendor/jquery.inputmask.min.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/shared/planner-modal.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/functions.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/events.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/fencing-styles-slick.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/fc-color-options-slick.js'); ?>"></script>
<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/calc.js'); ?>"></script>

<script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/frontend/core/glass_calculator.js'); ?>"></script>

<?php include __DIR__ . '/fence-scripts.php'; ?>

<?php if( !\Fc\Admin\Helpers\UrlHelper::inUriSegment(\Fc\Admin\Services\SiteRegistryService::demoStages()) ): ?>
<!-- Chatra {literal} -->
<script>
window.addEventListener('load', function() {
    window.setTimeout(function() {
        (function(d, w, c) {
            w.ChatraID = '<?php echo \Fc\Admin\Services\AppConfigService::all()->apikey->chatra; ?>';
            var s = d.createElement('script');
            w[c] = w[c] || function() {
                (w[c].q = w[c].q || []).push(arguments);
            };
            s.async = true;
            s.src = 'https://call.chatra.io/chatra.js';
            if (d.head) d.head.appendChild(s);
        })(document, window, 'Chatra');
    }, 2500);
});
</script>
<!-- /Chatra {/literal} -->
<?php endif; ?>
