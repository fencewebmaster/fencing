<?php
/**
 * Planner page (/planner).
 *
 * Rendered by Controllers\Frontend\PlannerController.
 *
 * @var array        $fences                   Fence catalog (writable/settings.php).
 * @var array        $info                     Session fc_data.
 * @var object|array $res                      JS `fc_fence_info` payload.
 * @var array|null   $site_info                Site registry row for the current host.
 * @var string       $fc_site_logo_url         Header logo URL ('' when unset).
 * @var string       $fc_session_project_plans Project plans JSON from the session.
 * @var bool         $load_quote_failed
 * @var string       $load_quote_error
 * @var string       $load_quote_attempt
 */
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include __DIR__ . '/../partials/head.php'; ?>
    </head>
    <body class="fc-planner-page">

        <?php include __DIR__ . '/../partials/body-before.php'; ?>

        <div class="fencing-container container-lg w-side-section mt-5" data-tab="1">
            <form method="POST" id="fc-planning-form" action="<?php echo \Fc\Admin\Helpers\UrlHelper::baseUrl('project-plan'); ?>" novalidate>

                <div class="fencing-container__header">
                    <div class="row align-items-center">

                        <div class="col col-sm">
                            <?php include __DIR__ . '/../partials/header-left.php'; ?>
                        </div>

                        <div class="col-md-6 col-sm-auto">
                            <div class="d-sm-block d-none">
                                <div class="fc-flex-end">
                                    <?php if ( $fc_site_logo_url !== '' ) : ?>
                                    <img src="<?php echo htmlspecialchars($fc_site_logo_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) @$site_info['name'], ENT_QUOTES, 'UTF-8'); ?>" style="max-width: 200px;" decoding="async">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-sm-block-x d-none">
                                <div class="fc-flex-end">
                                    <div class="fc-video-bg">
                                        <i class="fa-solid fa-circle-play fc-mb-1"></i>
                                        <h4 class="fc-mb-0">How to Use This<br>
                                            Fence Planner
                                        </h4>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- [START] TABS -->
                <div class="fencing-tabs-container js-fencing-tabs-container fc-section-step fc-d-none fc-font-2 mt-3" data-tab="1">
                    <div class="fencing-tabs-area bg-white" data-spy="scroll" data-screen="0" data-target=".js-fencing-tabs-container">
                        <div class="fencing-tabs fc-row-flex">

                            <div class="fencing-tab-container js-fencing-tab-container fc-row-flex">
                                <div class="fencing-tab-container-area js-fencing-tab-container-area fc-row-flex"></div>
                            </div>

                            <a href="#" class="fencing-tab-add fc-d-none" aria-label="Add section">
                            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i><span>Add Section</span>
                            </a>

                        </div>
                    </div>
                </div>
                <!-- [END] TABS -->

                <div class="fc-section-details">

                    <!-- [START] TABS -->
                    <div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="2" data-section="4" style="display:none;">
                        <div class="fc-header-tab__area">
                            <a href="#" class="fc-btn-step" data-tab="2" data-move="1">
                                <div class="fencing-tab-name">Section Details</div>
                            </a>

                            <a class="fc-tab-active tab-selected">
                                <div class="fencing-tab-name">Project Options</div>
                            </a>
                        </div>
                    </div>

                    <div class="fencing-content fc-font-1">
                        <div class="fc-section-step" data-tab="1">

                            <div class="fc-content-tab-title">
                                <span class="fc-tab-title me-2"></span>
                                <span class="fc-tab-subtitle"></span>
                            </div>

                            <?php include __DIR__ . '/step-1.php'; ?>

                            <?php include __DIR__ . '/step-2.php'; ?>

                            <?php include __DIR__ . '/step-3.php'; ?>

                        </div>

                        <?php include __DIR__ . '/step-4.php'; ?>

                    </div>
                </div>

                <?php include __DIR__ . '/../modal.php'; ?>

            </form>

        </div>

        <?php include __DIR__ . '/modals.php'; ?>

        <?php include __DIR__ . '/../fields.php'; ?>

        <script type="text/javascript">
        var fc_data       = <?php echo json_encode($fences); ?>;
        var fc_fence_info = <?php echo json_encode($res); ?>;
        var planner_id    = "<?php echo @$_SESSION['planner_id']; ?>";
        var fc_session_project_plans = <?php echo json_encode( $fc_session_project_plans ); ?>;
        var planner_share_url = "<?php echo \Fc\Admin\Services\PlannerSessionService::qidShareUrl(); ?>";
        var fc_load_quote_failed = <?php echo $load_quote_failed ? 'true' : 'false'; ?>;
        var fc_load_quote_error = <?php echo json_encode( $load_quote_error ); ?>;
        var fc_load_quote_attempt = <?php echo json_encode( $load_quote_attempt ); ?>;
        </script>

        <?php include __DIR__ . '/../partials/footer.php'; ?>

        <script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/cart-items.js'); ?>"></script>
        <script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/fc-planner-summary.js'); ?>"></script>
        <script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/p1.js'); ?>"></script>

    </body>
</html>
