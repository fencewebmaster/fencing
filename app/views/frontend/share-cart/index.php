<?php
/**
 * Share-cart loader (/share-cart-url/{plannerId}).
 *
 * Rendered by Controllers\Frontend\ShareCartUrlController. Standalone <head>: this is a
 * nested public route, so asset URLs come from the controller's basePath()-built closure,
 * never the one-segment-deep asset()/baseUrl() helpers (same reason /lookup/view/{slug}
 * carries its own head). style.css owns every .fc-loader-* rule; partials/webfonts.php supplies
 * the League Gothic the loader lines are set in.
 *
 * @var string   $fcShareCartError Error message; '' renders the loader + auto-continue.
 * @var string   $fcShareCartGoUrl Same-path URL that performs the push and redirect.
 * @var callable $fcShareCartAsset Cache-busted, basePath-relative asset URL builder.
 */

declare(strict_types=1);

use Fc\Admin\Settings\SeoSettings;
use Fc\Admin\Settings\ThemeSettings;

$asset = $fcShareCartAsset;
$goUrl = $fcShareCartGoUrl;
$error = $fcShareCartError;
?><!DOCTYPE html>
<html lang="<?php echo e(SeoSettings::language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Loading Your Quote</title>
    <?php include view_path('frontend.partials.webfonts'); ?>
    <link rel="stylesheet" href="<?php echo e($asset('public/assets/css/frontend/style.css')); ?>">
    <?php echo ThemeSettings::cssBlock(); ?>
    <?php if ($error === '') : ?>
    <noscript><meta http-equiv="refresh" content="2;url=<?php echo e($goUrl); ?>"></noscript>
    <?php endif; ?>
    <style>
        /* Standalone page: no Bootstrap here, so the loader list resets itself. */
        body { margin: 0; background: var(--fc-a-black-98, #0a0a0a); }
        .fc-loader ul { margin: 0; padding: 0; list-style: none; }
        .fc-share-cart-retry { color: var(--fc-white, #fff); }
    </style>
</head>
<body>

    <div class="fc-loader-overlay" style="display: block;">
        <div class="fc-loader-container">
            <div class="fc-loader">
                <div class="fc-loader-panel fc-loader-panel--default">
                    <div class="fc-row">
                        <div class="fc-col-auto">
                            <div class="fc-loader-gif"></div>
                        </div>
                        <div class="fc-col-auto">
                            <ul>
                                <?php if ($error === '') : ?>
                                <li class="fc-text-success li-create"><div><small>Please wait while we are loading your quote.</small></div></li>
                                <li class="fc-text-success li-create"><div><small>Thank you!</small></div></li>
                                <?php else : ?>
                                <li class="fc-text-success li-create"><div><small><?php echo e($error); ?></small></div></li>
                                <li><div><small><a class="fc-share-cart-retry" href="<?php echo e($goUrl); ?>">Try again</a></small></div></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error === '') : ?>
    <script>window.location.replace(<?php echo json_encode($goUrl); ?>);</script>
    <?php endif; ?>

</body>
</html>
