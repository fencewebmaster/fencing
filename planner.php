<?php
require_once __DIR__ . '/app/src/Core/Autoloader.php';
\Fc\Admin\Core\Autoloader::register();

require_once __DIR__ . '/app/src/Core/SessionBootstrap.php';
\Fc\Admin\Core\SessionBootstrap::start();

$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

require_once __DIR__ . '/app/src/Services/SiteRegistryService.php';
require_once __DIR__ . '/app/src/Services/PlannerSessionService.php';
require_once __DIR__ . '/app/src/Helpers/UrlHelper.php';
require_once __DIR__ . '/app/src/Helpers/AssetHelper.php';

if( @$_GET['action'] == 'clear-all' || @$_GET['site'] || @$_GET['sid'] ) {
    // Clear fence session data
    \Fc\Admin\Services\PlannerSessionService::clearPlannerSessions();
}

if( @$_GET['site'] || @$_GET['sid'] ) {

    $redirect_to    = \Fc\Admin\Helpers\UrlHelper::baseUrl('planner');
    $query_vars     = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';
    $new_query_vars = array_diff_key(\Fc\Admin\Helpers\UrlHelper::queryVars($query_vars), ['sid' => 1, 'site' => 1]);

    if( @$_GET['sid'] ) {
        $site = \Fc\Admin\Services\SiteRegistryService::all($_GET['sid'], 'id', true);
    } else {
        $site = \Fc\Admin\Services\SiteRegistryService::all($_GET['site'], 'domain', true);
    }

    if( $site ) {
        $_SESSION["site"] = $site;
        header("Location: ".$redirect_to.'?'.http_build_query($new_query_vars));
    } else {
       header("Location: ".\Fc\Admin\Helpers\UrlHelper::toUrl($_GET['url']));
    }
    exit;
}



if( ! @$_SESSION["site"] ) {
    $site = \Fc\Admin\Services\SiteRegistryService::all($_SERVER['HTTP_HOST'], 'domain', true);
    $_SESSION["site"] = $site;
}

include 'writable/settings.php';
require_once __DIR__ . '/app/src/Services/DatabaseConfigService.php';
require_once __DIR__ . '/app/src/Services/Database.php';
require_once __DIR__ . '/app/src/Services/PlannerRecordService.php';

$res = array();
$load_quote_failed = false;
$load_quote_error = '';
$load_quote_attempt = '';

if ( $qid = @$_GET['qid'] ) {
    $qid = trim( (string) $qid );
    $load_quote_attempt = $qid;

    $db = new \Fc\Admin\Services\Database();
    $res = \Fc\Admin\Services\PlannerRecordService::isValidPlannerId( $qid )
        ? $db->select_where( 'planners', '`planner_id`="' . $qid . '"' )
        : array();

    if ( $res && is_object( $res ) && ! \Fc\Admin\Services\PlannerRecordService::rowIsTrashed( $res ) ) {
        // Clear fence session data
        \Fc\Admin\Services\PlannerSessionService::clearPlannerSessions();

        $_SESSION['planner_id'] = $qid;

        $site = \Fc\Admin\Services\SiteRegistryService::all( $_SERVER['HTTP_HOST'], 'domain', true );
        $_SESSION['site'] = $site;

        \Fc\Admin\Services\PlannerSessionService::hydrateFromRow( $res );

        \Fc\Admin\Services\PlannerRecordService::markReloaded( $qid );

        $res = \Fc\Admin\Services\PlannerSessionService::rowToJsFenceInfo( $res );
    } else {
        $load_quote_failed = true;
        $load_quote_error  = ( $res && is_object( $res ) && \Fc\Admin\Services\PlannerRecordService::rowIsTrashed( $res ) )
            ? 'This quote is no longer available.'
            : 'No quote found for that Quote ID. Please check the ID and try again.';
        $res               = (object) array();
    }
}

// When returning from project-plan, reload the saved quote from DB so session + JS match latest edits.
if (
    ! $load_quote_failed &&
    empty( $_GET['qid'] ) &&
    ! empty( $_SESSION['planner_id'] )
) {
    $db      = new \Fc\Admin\Services\Database();
    $pid     = str_replace( '"', '""', (string) $_SESSION['planner_id'] );
    $db_row  = $db->select_where( 'planners', '`planner_id`="' . $pid . '"' );

    if ( $db_row && is_object( $db_row ) && ! \Fc\Admin\Services\PlannerRecordService::rowIsTrashed( $db_row ) ) {
        \Fc\Admin\Services\PlannerSessionService::hydrateFromRow( $db_row );
        $res = \Fc\Admin\Services\PlannerSessionService::rowToJsFenceInfo( $db_row );
    } elseif ( $db_row && is_object( $db_row ) && \Fc\Admin\Services\PlannerRecordService::rowIsTrashed( $db_row ) ) {
        \Fc\Admin\Services\PlannerSessionService::clearPlannerSessions();
        unset( $_SESSION['planner_id'] );
        $load_quote_failed = true;
        $load_quote_error  = 'This quote is no longer available.';
        $load_quote_attempt = $pid;
        $res = (object) array();
    }
}

$info = isset( $_SESSION['fc_data'] ) ? $_SESSION['fc_data'] : array();

// Expose session-saved plan to JS when not loading a row by qid (e.g. user navigates back from
// project-plan). p1.js reloadFencingData() only repopulates localStorage when fc_fence_info.fence_data
// is set; without this, Overall Length / mbn in custom_fence-* can be missing.
if (
    ! $load_quote_failed &&
    ( ! is_object( $res ) || empty( $res->fence_data ) ) &&
    ! empty( $info['fences'] )
) {
    $fences_raw = $info['fences'];
    $fence_ary = is_string($fences_raw) ? json_decode($fences_raw, true) : $fences_raw;
    $section_count = is_array($fence_ary) ? count($fence_ary) : 0;
    $res = (object) [
        'fence_data'         => is_string($fences_raw) ? $fences_raw : json_encode($fence_ary ?: []),
        'cart_items_data'    => isset($info['cart_items']) ? $info['cart_items'] : '[]',
        'section_count'      => $section_count,
        'project_plans_data' => isset($info['project_plans']) ? $info['project_plans'] : '',
    ];
}

$site_info = \Fc\Admin\Services\SiteRegistryService::all($_SERVER['HTTP_HOST'], 'domain', true);

// Header logo: Settings → Integrations override for this site, else the registry asset.
$fc_site_logo_url = \Fc\Admin\Services\SiteRegistryService::logoUrl(
    is_array($site_info) ? $site_info : (string) $_SERVER['HTTP_HOST']
);

$fc_session_project_plans = \Fc\Admin\Services\PlannerSessionService::clientProjectPlansFromSession();

if ( is_object( $res ) && $fc_session_project_plans !== '' ) {
    $res->project_plans_data = $fc_session_project_plans;
}

$_SESSION['live_mode'] = \Fc\Admin\Helpers\UrlHelper::inUriSegment(\Fc\Admin\Services\SiteRegistryService::demoStages()) ? FALSE : TRUE;
?>

<!DOCTYPE html>
<html>
    <head>
        <?php include 'views/partials/head.php'; ?>
    </head>
    <body class="fc-planner-page">

        <?php include 'views/partials/body-before.php'; ?>

        <div class="fencing-container container-lg w-side-section mt-5" data-tab="1">
            <form method="POST" id="fc-planning-form" action="project-plan.php" novalidate>

                <div class="fencing-container__header">
                    <div class="row align-items-center">
                        
                        <div class="col col-sm">
                            <?php include 'views/partials/header-left.php'; ?>                
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

                            <?php include 'views/planner/step-1.php'; ?>

                            <?php include 'views/planner/step-2.php'; ?>

                            <?php include 'views/planner/step-3.php'; ?>
                          
                        </div>

                        <?php include 'views/planner/step-4.php'; ?>
                        
                    </div>
                </div>

                <?php include 'views/modal.php'; ?>

            </form>

        </div>

        <?php include 'views/planner/modals.php'; ?>

        <?php include 'views/fields.php'; ?>

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

        <?php include 'views/partials/footer.php'; ?>

        <script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/cart-items.js'); ?>"></script>
        <script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/fc-planner-summary.js'); ?>"></script>
        <script defer src="<?php echo \Fc\Admin\Helpers\AssetHelper::assetUrl('public/assets/js/p1.js'); ?>"></script>

    </body>
</html>