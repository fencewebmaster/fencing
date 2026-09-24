<?php
use Fc\Admin\Debug\DebugbarServer;
use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Services\AppConfigService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\IntegrationsSettings;
use Fc\Admin\Services\SiteRegistryService;
?>
<div class="container-lg">
    <div class="row align-items-center mb-5 pb-5">
        <div class="col">
            <?php
            $fcBranding = BrandingSettings::get();
            $fcWebhookMode = (string) (IntegrationsSettings::get()['webhookMode'] ?? 'live');
            $fcWebhookIsLive = $fcWebhookMode !== 'test';
            ?>
            <div class="text-secondary small fc-footer-app">
                <i class="fa-solid fa-circle fc-footer-webhook-dot <?php echo $fcWebhookIsLive ? 'fc-footer-webhook-dot--live' : 'fc-footer-webhook-dot--test'; ?>" aria-hidden="true" title="<?php echo $fcWebhookIsLive ? 'Live webhook URL' : 'Test webhook URL'; ?>"></i>
                <?php echo e($fcBranding['appName']); ?>
                <span class="app-version"><?php echo e($fcBranding['version']); ?></span>
            </div>
        </div>
        <div class="col-auto">
            <button type="button" data-bs-toggle="modal" data-bs-target="#clear-all-data" class="btn btn-sm text-uppercase fc-clear-all-btn" aria-label="Clear all sections and start a new quote">
                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                <span>Clear All</span>
            </button>
        </div>
    </div>
</div>


<?php if( UrlHelper::inUriSegment(SiteRegistryService::demoStages()) ): ?>
<span class="badge bg-danger text-white text-uppercase p-1 is-demo">Test<br> Version</span>
<?php endif; ?>

<?php /* Clear All — destructive confirm. It spells out what disappears because the old copy,
     "clear all data and reset the form", never told anyone which of their sections they lose.
     data-fc-role survives Bootstrap stamping role="dialog" over the markup on every show. */ ?>
<div class="modal fade fc-modal" id="clear-all-data" tabindex="-1" role="alertdialog" data-fc-role="alertdialog" aria-labelledby="fcClearAllTitle" aria-describedby="fcClearAllDesc" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fc-modal__dialog">
        <div class="modal-content fc-modal__content">
            <div class="modal-header fc-modal__header">
                <div class="fc-modal__header-text">
                    <h5 class="modal-title fc-modal__title text-uppercase fw-bold" id="fcClearAllTitle">Clear all</h5>
                    <p class="fc-modal__subtitle mb-0">Reset the planner and start a new quote</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fc-modal__body">
                <p class="fc-modal__lead" id="fcClearAllDesc">Everything you have entered so far will be removed:</p>
                <ul class="fc-modal__list">
                    <li><i class="fa-solid fa-layer-group" aria-hidden="true"></i>All fence sections and their measurements</li>
                    <li><i class="fa-solid fa-swatchbook" aria-hidden="true"></i>Styles, colours, gates and panel options</li>
                    <li><i class="fa-solid fa-file-invoice" aria-hidden="true"></i>The current quote and your project details</li>
                </ul>
                <p class="fc-modal__note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>This cannot be undone. A quote you have already saved can be reloaded with its Quote ID.</span>
                </p>
            </div>
            <div class="modal-footer fc-modal__footer">
                <button type="button" class="btn btn-outline-secondary text-uppercase px-3 js-fc-clear-all-cancel" data-bs-dismiss="modal" data-fc-autofocus>
                    <strong>Cancel</strong>
                </button>
                <a href="<?php echo url('?action=clear-all'); ?>" class="btn btn-danger text-uppercase px-3 fc-modal__busy-btn js-fc-clear-all-confirm">
                    <i class="fa-solid fa-trash-can me-2 js-fc-clear-all-confirm-icon" aria-hidden="true"></i>
                    <strong class="js-fc-clear-all-confirm-label">Yes, clear all</strong>
                </a>
            </div>
        </div>
    </div>
</div>

<?php /* Libraries */ ?>
<script defer src="<?php echo asset('public/assets/js/vendor/select2.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/slick.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/jquery.validate.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/jquery-scrollspy.min.js'); ?>"></script>

<?php /* Plugins */ ?>
<?php /* Google Maps only feeds the Places autocomplete on #address, so fcLoadGoogleMaps() in functions.js
     fetches it once that field is in play instead of every visitor downloading ~300 KB up front. */ ?>
<script>window.fcGoogleMapsSrc = <?php echo json_encode('https://maps.googleapis.com/maps/api/js?key=' . rawurlencode((string) AppConfigService::all()->apikey->google_map) . '&libraries=places&loading=async&callback=initAutocompleteAddress', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>;</script>

<script defer src="<?php echo asset('public/assets/js/vendor/bootstrap.bundle.min.js'); ?>"></script>



<?php /* Scripts */ ?>
<script defer src="<?php echo asset('public/assets/js/frontend/core/helpers.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/main.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/vendor/jquery.inputmask.min.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/planner-modal.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/hscroll-fade.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/hscroll-proxy.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/overall-dimension.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/panel-dimensions.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/drawing-keys.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/planner-shortcuts.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/data/post-finishes.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/shared/post-finish-swatches.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/functions.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/core/events.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/slick/styles.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/slick/color-options.js'); ?>"></script>
<?php // calc.js lives with the per-fence calc modules but loads here in the core chain — script
      // order is load-bearing, and fence-scripts.php excludes it from the fences/calc glob. ?>
<script defer src="<?php echo asset('public/assets/js/frontend/fences/calc/calc.js'); ?>"></script>

<?php include view_path('frontend.partials.fence-scripts'); ?>

<?php if (!empty($fcDebugbarOn)) : ?>
<?php
// Debugbar: flag comes from head.php's shared-scope $fcDebugbarOn (Settings -> Console).
// The island carries the server capture (timing/queries/errors); the two scripts execute
// after the whole deferred chain above and wrap globals from OUTSIDE at DOMContentLoaded,
// so calculator files stay untouched. Kept ahead of the custom-footer block so that block
// stays the last thing on the page.
echo DebugbarServer::inlineJsonIsland();
?>
<script defer src="<?php echo asset('public/assets/js/frontend/debug/debugbar-core.js'); ?>"></script>
<script defer src="<?php echo asset('public/assets/js/frontend/debug/debugbar-ui.js'); ?>"></script>
<?php endif; ?>

<?php
// Custom footer code from Settings -> Integration. Raw and unescaped, same rationale as
// the header block in head.php. Last thing on the page, so it runs after every script.
$fcFooterCode = trim((string) (AppConfigService::all()->custom_code->footer ?? ''));
if ($fcFooterCode !== '') :
?>
<?php /* Custom footer code */ ?>
<?php echo $fcFooterCode; ?>
<?php endif; ?>

<?php
// Chatra live chat, from Settings -> Integration -> Chatra ID.
//
// Skipped when the Custom code above already loads Chatra: that field is the user's and is never
// rewritten, so a pasted widget stays authoritative and the page cannot end up with two loaders
// racing for the same window.Chatra global.
$fcChatraId = trim((string) (AppConfigService::all()->apikey->chatra ?? ''));
$fcChatraInCustomCode = stripos($fcFooterCode, 'chatra') !== false
    || stripos((string) (AppConfigService::all()->custom_code->header ?? ''), 'chatra') !== false;

if ($fcChatraId !== '' && !$fcChatraInCustomCode) :
?>
<?php /* Chatra */ ?>
<script>
window.addEventListener('load', function() {
    window.setTimeout(function() {
        (function(d, w, c) {
            w.ChatraID = <?php echo json_encode($fcChatraId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
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
<?php /* /Chatra */ ?>
<?php endif; ?>

<?php
// Custom Chatra launcher. Emitted whenever Chatra is active by either route - the ID field
// above or a pasted Custom code block - so it works without the user having to migrate.
//
// ChatraSetup.customWidgetButton is Chatra's own hook: it suppresses the default launcher and
// treats this element as the opener, so there is no hide/open dance to keep in sync and the
// unread badge still lands on it. It only has to be set before chatra.js runs, which is 2.5s
// after window load in both loaders.
if ($fcChatraId !== '' || $fcChatraInCustomCode) :
?>
<button type="button" id="fc-chat-launcher" class="fc-chat-launcher fc-btn-shine" aria-label="Open live chat">
    <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
</button>
<script>
    window.ChatraSetup = window.ChatraSetup || {};
    window.ChatraSetup.customWidgetButton = '#fc-chat-launcher';

    <?php /* Hidden until Chatra's iframe appears: its loader waits 2.5s after window load, so a
             button shown early would be un-bound. No timeout fallback, on purpose. */ ?>
    (function () {
        var btn = document.getElementById('fc-chat-launcher');

        if (!btn) {
            return;
        }

        var reveal = function () {
            btn.classList.add('is-ready');
            // Click-to-open only: collapse whatever Chatra restored or auto-expanded at
            // boot. Dashboard-side targeted messages have no client-side off switch.
            if (typeof window.Chatra === 'function') {
                window.Chatra('minimizeWidget');
            }
        };

        if (document.getElementById('chatra__iframe')) {
            reveal();
            return;
        }

        if (typeof MutationObserver !== 'function') {
            <?php /* No observer to lean on; fall back to the loader's own delay plus a margin. */ ?>
            window.setTimeout(reveal, 4000);
            return;
        }

        var observer = new MutationObserver(function () {
            if (!document.getElementById('chatra__iframe')) {
                return;
            }
            observer.disconnect();
            reveal();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    })();
</script>
<?php endif; ?>
