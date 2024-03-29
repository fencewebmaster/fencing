<?php 
session_start();
$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

include 'config/helpers.php';

if( @$_GET['action'] == 'clear-all' || @$_GET['site'] || @$_GET['sid'] ) {
    // Clear fence session data
    clear_planner_sessions();
}

if( @$_GET['site'] || @$_GET['sid'] ) {

    $redirect_to    = base_url('planner');
    $query_vars     = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';
    $new_query_vars = array_diff_key(query_vars($query_vars), ['sid' => 1, 'site' => 1]);

    if( @$_GET['sid'] ) {
        $site = sites($_GET['sid'], 'id', true);
    } else {
        $site = sites($_GET['site'], 'domain', true);        
    }

    if( $site ) {
        $_SESSION["site"] = $site;          
        header("Location: ".$redirect_to.'?'.http_build_query($new_query_vars));
    } else {
       header("Location: ".toURL($_GET['url']));
    }
    exit;      
} 

if( ! @$_SESSION["site"] ) {
    $site = sites($_SERVER['HTTP_HOST'], 'domain', true);    
    $_SESSION["site"] = $site;  
}

include 'data/settings.php';
include 'config/database.php'; 

$res = array();
if( $qid = @$_GET['qid'] ) {
    $db = new Database();    
    $res = $db->select_where('planners', '`planner_id`="'.$qid.'"');   

    if( $res ) {

        // Clear fence session data
        clear_planner_sessions();

        $parse_url = parse_url($res->site_url);

        $_SESSION['planner_id'] = $qid;

        $site = sites($parse_url['host'], 'domain', true);    
        $_SESSION["site"] = $site;
    }
}

$site_info = sites($_SESSION['site']['id'], 'id', true);

$_SESSION['live_mode'] = in_uri_segment(demo_stages()) ? FALSE : TRUE;
?>

<!DOCTYPE html>
<html>
    <head>
        <?php include 'views/partials/head.php'; ?>
    </head>
    <body>

        <div class="fencing-container container-lg w-side-section mt-5" data-tab="1">
            <form method="POST" id="fc-planning-form" action="project-plan.php">

                <div class="fencing-container__header">
                    <div class="row align-items-center">
                        
                        <div class="col col-sm">
                            <?php include 'views/partials/header-left.php'; ?>                
                        </div>

                        <div class="col-md-6 col-sm-auto">

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

                            <a href="#" class="fencing-tab-add fc-d-none">
                            &#43; <span>Add Section</span>      
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
        </script>

        <?php include 'views/partials/footer.php'; ?>

        <script type="text/javascript" src="<?php echo load_file('assets/js/cart-items.js'); ?>"></script>
        <script type="text/javascript" src="<?php echo load_file('assets/js/p1.js'); ?>"></script>

    </body>
</html>