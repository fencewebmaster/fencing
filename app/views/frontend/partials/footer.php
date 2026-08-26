<?php
use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Services\AppConfigService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\IntegrationsSettings;
use Fc\Admin\Services\SiteRegistryService;
?>
<div class="container-lg">
    <div class="row row align-items-middle">
        <div class="col">
            <?php
            $fcBranding = BrandingSettings::get();
            $fcWebhookMode = (string) (IntegrationsSettings::get()['webhookMode'] ?? 'live');
            $fcWebhookIsLive = $fcWebhookMode !== 'test';
            ?>
            <div class="mb-5 pb-5 text-secondary small">
                <i class="fa-solid fa-circle fc-footer-webhook-dot <?php echo $fcWebhookIsLive ? 'fc-footer-webhook-dot--live' : 'fc-footer-webhook-dot--test'; ?>" aria-hidden="true" title="<?php echo $fcWebhookIsLive ? 'Live webhook URL' : 'Test webhook URL'; ?>"></i>
                <?php echo e($fcBranding['appName']); ?>
                <span class="app-version"><?php echo e($fcBranding['version']); ?></span>
            </div>
        </div>
        <div class="col">
            <a href="" data-bs-toggle="modal" data-bs-target="#clear-all-data" class="btn btn-danger btn-sm px-2 fw-bold text-uppercase float-end">Clear All</a>
            
        </div>
    </div>
</div>


<?php if( UrlHelper::inUriSegment(SiteRegistryService::demoStages()) ): ?>
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
				<a href="<?php echo url('?action=clear-all'); ?>" class="btn btn-orange text-uppercase px-3">
				    <i class="fa fa-check me-1"></i>
				    <strong>Confirm</strong>
				</a>
			</div>
        </div>
    </div>
</div>

<!-- Libraries -->
<script defer src="<?php echo asset('public/assets/js/vendor/select2.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/slick.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/jquery.validate.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/jquery-scrollspy.min.js'); ?>"></script>

<!-- Plugins -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo e((string) AppConfigService::all()->apikey->google_map); ?>&libraries=places&loading=async&callback=initAutocompleteAddress"
    async defer></script>

<script defer src="<?php echo asset('public/assets/js/vendor/bootstrap.bundle.min.js'); ?>"></script>



<!-- Scripts -->
<script defer src="<?php echo asset('public/assets/js/frontend/core/helpers.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/main.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/jquery.inputmask.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/planner-modal.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/functions.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/events.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/fencing-styles-slick.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/fc-color-options-slick.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/calc.js'); ?>"></script>

<script defer src="<?php echo asset('public/assets/js/frontend/core/glass_calculator.js'); ?>"></script>

<?php include view_path('frontend.partials.fence-scripts'); ?>

<?php if( !UrlHelper::inUriSegment(SiteRegistryService::demoStages()) ): ?>
<!-- Chatra {literal} -->
<script>
window.addEventListener('load', function() {
    window.setTimeout(function() {
        (function(d, w, c) {
            w.ChatraID = <?php echo json_encode((string) AppConfigService::all()->apikey->chatra, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
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
