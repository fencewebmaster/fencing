<?php 
session_start();
$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

include 'helpers.php';

if( @$_GET['site'] ) {
    $site = sites($_GET['site'], 'domain', true);

    if( $site ) {
        $_SESSION["site"] = $site;  
        header("Location: ./");   
    } else {
       header("Location: ".toURL($_GET['url']));
    }
    exit;      
} 

if( ! @$_SESSION["site"] ) {
    clear_planner_sessions();
    $site = sites($_SERVER['HTTP_HOST'], 'domain', true);    
    $_SESSION["site"] = $site;  
}

include 'data/settings.php';
include 'temp/fields.php';
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
?>

<!DOCTYPE html>
<html>
    <head>
        <?php include 'temp/partials/head.php'; ?>
    </head>
    <body>

        <!-- START FORM SUBMISSION LOADER -->
        <div class="fc-loader-overlay" style="display: none;">
            <div class="fc-loader-container">
                <div class="fc-loader">
                    <div class="row align-items-center">
                        
                        <div class="col-auto">
                            <div class="fc-loader-gif"></div>
                        </div>

                        <div class="col-auto">
                            
                            <ul class="mb-0 p-0">
                                <li class="fc-text-success li-create">
                                    <div><small>Creating your plan...</small></div>
                                </li>
                            </ul>

                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- END FORM SUBMISSION LOADER -->


        <!-- Load Quote Modal -->
        <form method="get">
            <div class="modal modal-sm fade" id="load-quote" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header fw-bold">
                            <h5 class="modal-title text-uppercase fw-bold" id="exampleModalLabel">Load <span class="text-danger">Quote</span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="input-groupx mb-3">
                                <div class="form-group has-clear position-relative">
                                    <input type="text" name="qid" class="form-control border border-secondary form-control-lg no-space text-center mb-2" placeholder="Enter Quote ID" required>                        
                                </div>

                                <button type="submit" class="btn btn-lg w-100 btn-orange text-uppercase px-4">
                                    <i class="fa fa-check me-2"></i>
                                    <strong>Confirm</strong>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="fencing-container container-lg w-side-section mt-5" data-tab="1">
            <form method="POST" id="fc-planning-form" action="project-plan.php">

                <div class="fencing-container__header">
                    <div class="row align-items-center">
                        
                        <div class="col-md-6 col-sm">
                            <?php include 'temp/partials/header-left.php'; ?>                
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

                <!-- START TABS -->
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
                <!-- END TABS -->

                <div class="fc-section-details">       


                <!-- START TABS -->
                    <div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="2" style="display:none;">
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

                            <div class="fencing-section fencing-section--has-border fencing-section--no-radius-top">
                            
                                <!-- START STYLES AREA -->
                                <div class="fencing-section__cmp fencing-section-step fencing-section--step1">

                                    <div class="row align-items-center">
                                        <div class="col-sm mb-sm-0 mb-3">
                                            <div class="step-label">Step <span>01</span></div>

                                            <h4 class="fencing-content-title">Choose Your Fencing Style</h4>                                    
                                        </div>
                                        <div class="col-sm">

                                            <div class="btn-delete-fence text-end">

                                                <button type="button" 
                                                    class="btn btn-danger btn-sm js-btn-delete-fence px-2 fw-bold text-uppercase" 
                                                    style="display:none;">
                                                    <i class="fa fa-trash-can me-1"></i> Delete <span>Section</span>
                                                </button>

                                                <button type="button" 
                                                    class="btn btn-danger btn-sm fc-fence-reset-all px-2 fw-bold text-uppercase" 
                                                    style="display:none;">
                                                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                                                </button>

                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <!-- START STYLES -->
                                <div class="fencing-section__cmp fencing-styles">
                                    <div class="fencing-styles__area row">

                                        <?php foreach( $fences as $fence ): ?>
                                        <div class="fencing-style-item col-lg-2 col-md-3 col-sm-4 col-6 mb-3 px-2" data-slug="<?php echo $fence['slug']; ?>" data-title="<?php echo $fence['title']; ?>">
                                            <div>

                                                <div class="fencing-style-img">
                                                    <img src="<?php echo base_url().$fence['image']; ?>">               
                                                </div>

                                                <div class="fencing-style-title fw-bold fs-6">
                                                    <?php echo $fence['title']; ?>
                                                </div>

                                                <button class="fencing-style-btn js-fencing-style-btn fc-fence-reset-all"><i class="fa fa-times"></i></button>

                                            </div>
                                        </div>
                                        <?php endforeach; ?>    


                                        <div class="load-quote col-lg-2 col-md-3 col-sm-4 col-6 mb-3 px-2" data-bs-toggle="modal" data-bs-target="#load-quote">
                                            <div>

                                                <div class="fencing-style-img">
                                                    <img src="<?php echo base_url(); ?>img/plain-white.jpg">  
                                                    <div class="lq-mid-desc">
                                                        <div class="lq-icon">
                                                            <i class="fa-solid fa-file-circle-plus"></i>                                         
                                                        </div>
                                                        Click Here to<br> Load Quote                                                             
                                                    </div>
                                                </div>

                                                <div class="fencing-style-title fw-bold fs-6">
                                                    Load Quote
                                                </div>

                                            </div>
                                        </div>

                                    </div>          
                                </div>
                                <!-- END STYLES -->

                            </div>

                                        
                            <!-- START MEASUREMENT -->  
                            <div class="fencing-section fencing-section--has-border js-fc-form-step fc-d-none" data-section="2" style="display: none;">
                                
                                <div class="fencing-measurement fencing-section--step2">

                                    <div class="row align-items-center">
                                        
                                        <div class="col-lg-5 col-md-6 fc-input-container mb-3">
                                            
                                            <div class="step-label mb-2">Step <span>02</span></div>

                                            <h4 class="fencing-content-title mb-2">Enter the overall length (mm)</h4>

                                            <div class="step-2_field" data-action="change">
                                                                                     
                                            </div>

                                            <div class="fw-bold small mb-1">Overall Length</div>
                                            
                                            <div class="fencing-measurement-box">
                                            
                                                <span class="fencing-mb-input">
                                                    
                                                    <input type="text" class="measurement-box-number numeric py-1" data-min="300" data-max="100000" value=""> 
                                                    
                                                    <span>mm</span>             
                                                    
                                                    <div class="fencing-qty-plus fencing-qty-btn">
                                                        <img src="<?php echo base_url(); ?>img/quantity-plus.png" alt="" title="">
                                                    </div>

                                                    <div class="fencing-qty-minus fencing-qty-btn">
                                                        <img src="<?php echo base_url(); ?>img/quantity-minus.png" alt="" title="">
                                                    </div>

                                                </span>

                                                <button type="button" class="btn btn-dark py-3 text-uppercase btn-fc-calculate px-3 fw-bold"><small>Calculate</small></button>

                                            </div>

                                            <div class="fc-input-msg error-msg"></div>
                                        </div>
                                        <div class="col-lg d-lg-inline-block d-md-none"></div>
                                        <div class="col-lg-6 col-md-6">

                                            <div class="alert alert-gray float-end">

                                                <div class="text-uppercase fw-bold text-dark mb-2">
                                                    <i class="fc-icon fc-icon-capa me-1"></i> Important
                                                </div>

                                                <div class="step-2_notes" data-action="change"></div>
                                                            
                                                <div>
                                                    <strong class="mb-1">Overall  Length</strong>
                                                    <p class="text-secondary small">Ensure your overall length includes the posts each end. NOTE: "Panel & Post Options" below will deduct based on options selected.
                                                    </p>                                            
                                                </div>                    
                                                
                                            </div>

                                        </div>

                                    </div>

                                </div>
                               
                            </div>
                            <!-- END MEASUREMENT -->
                            
                            <!-- START DISPLAY RESULT -->   
                            <div class="fencing-section fencing-section--no-padding fencing-section--has-border fc-position-relative js-fc-form-step fc-d-none" data-section="3" style="display: none;">
                                   
                                <div class="fencing-section__top">
                                    <div class="fencing-calculating">
                                        <div class="fc-calculating-loader">
                                            <div class="fc-loader-gif"></div>
                                            <h4 class="fc-text-uppercase">Calculating ...   </h4>
                                        </div>
                                    </div>

                                    <div class="fencing-section__cmp fencing-section--step3">
                                        
                                        <div class="step-label">Step <span>03</span></div>

                                        <h4 class="fencing-content-title fc-mb-2">Configure this fence section</h4>

                                        <div class="fencing-section__controls">
                                            
                                            <a href="#" style="display: none;">
                                                <i class="fc-icon fc-rectangle"></i>
                                            </a>

                                            <a href="#" class="fc-zoom-fence" data-zoom="in">
                                                <i class="fc-icon fc-magnify-plus"></i>
                                                Zoom in
                                            </a>

                                            <div class="fc-zoom-progress js-fc-zoom-progress">100%</div>

                                            <a href="#" class="fc-zoom-fence" data-zoom="out">
                                                <i class="fc-icon fc-magnify-munis"></i>
                                                Zoom out
                                            </a>

                                            <a href="#" class="fc-zoom-reset js-fc-zoom-reset" data-zoom="reset">
                                                <i class="fc-icon fc-icon-arrow-cc"></i>
                                                Reset
                                            </a>
                  
                                        </div>                                        

                                    </div>

                                    <div class="fencing-section__cmp fencing-display-result">
                                        
                                        <div class="fencing-result-msg" style="display: none;">
                                            <p>No Valid Solution. Please adjust Measurements.</p>
                                        </div>

                                        <div class="fencing-panel-items">
                                            <div class="fencing-panel-rail fencing-btn-modal" 
                                                data-key="rail_options" 
                                                data-target="#fc-control-modal" style="display:none;"></div>
                                            <div id="pp-0" class="fencing-panel-container"></div>
                                        </div>

                                    </div>

                                    <!-- START PANEL CONTROLS -->   
                                    <div class="fencing-section__cmp fencing-panel-controls"></div>


                                    <!-- END PANEL CONTROLS -->
                                </div>
                                
                                <div class="fencing-section__bottom py-3">
                                    <div class="">

                                        <div class="row" data-tab="1">
                    

                                            <?php if( @$_SESSION['planner_id'] && @$_SESSION['fc_data']['name'] ): ?>
                                            <div class="col-lg-auto col-sm-6 px-1 mb-lg-0 mb-2">
                                                <button type="submit" class="btn btn-orange fc-btn-update py-3 px-4 w-100">
                                                    <i class="fa-regular fa-pen-to-square me-1"></i> 
                                                    <b>UPDATE</b>
                                                </button>
                                            </div>
                                            <?php endif; ?> 

                                            <div class="col-lg col-sm-6 px-1 mb-lg-0 mb-2">
                                                <button type="button" 
                                                    class="btn btn-orange fc-btn-next-step fc-btn-step p-3 text-uppercase w-100" 
                                                    data-tab="1" 
                                                    data-move="2"
                                                    disabled>
                                                    <b>NEXT <i class="fa-solid fa-angle-right mx-2"></i> Select PLAN OPTIONS</b>
                                                </button>
                                            </div>

                                            <div class="col-lg col-sm-6 px-1 mb-lg-0 mb-2">
                                                <button type="button" 
                                                    class="btn btn-dark fc-tab-add fencing-tab-add p-3 w-100">
                                                    <b>
                                                        <i class="fa-solid fa-plus me-1"></i> Add Another Section
                                                    </b>
                                                </button>
                                            </div>

                                            <div class="col-lg-auto col-sm-6 col-auto px-1 mb-lg-0 mb-2">
                                                <button type="button" 
                                                    class="btn btn-secondary fc-fence-reset-all fc-fence-reset text-uppercase p-3 w-100">
                                                    <b>
                                                        <i class="fa-solid fa-rotate-left me-1"></i>
                                                        Reset
                                                    </b>
                                                </button>
                                            </div>

                                            <div class="col-lg-auto col-sm-6 col px-1 mb-lg-0 mb-2">
                                                <button type="button" 
                                                    class="btn btn-danger btn-fc-sm btn-delete-fence js-btn-delete-fence fw-bold text-uppercase p-3 w-100" 
                                                    >
                                                    <span><i class="fa fa-trash-can me-1"></i> Delete <span>Section</span></span>
                                                </button>
                                            </div>

                                        </div>

                                        <div class="fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                                            
                                            <button type="button" 
                                                class="btn btn-orange fc-btn-create-plan fencing-btn-modal" 
                                                data-target="#submit-modal" 
                                                disabled>
                                                <strong>Create Project Plan</strong><br>
                                                <small>View Costing, Plan & Materials List</small>
                                            </button>

                                            <button type="button" 
                                                class="btn btn-step btn-outline-secondary text-uppercase fc-px-3" 
                                                data-tab="2" 
                                                data-move="1"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                                            </button>

                                        </div>

                                    </div>

                                </div>
                            </div>
                            <!-- END DISPLAY RESULT -->
                        </div>

                        <div class="fc-section-step fc-d-none fc-step-4 mb-5" data-tab="2" style="display: none;">
                            
                            <div class="fencing-section fencing-section--no-padding fencing-section--has-border br-tl-0">
                                
                                    <div class="fencing-section__top">
                                        <div class="step-label">Step <span>04</span></div>
                                        
                                        <h4 class="fencing-content-title fc-mb-2">Configure Your Project</h4>
                                    
                                        <div class="fc-card fc-mb-2" data-load="color-options"></div>

                                        <div class="fc-card">
                                            
                                            <div class="fc-card-header fc-bg-dark fc-border-top">
                                                Project Notes & Additional Details
                                            </div>

                                            <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
                                                <div class="p-3">
                                                    <div class="row">
                                                        <div class="col-lg-6">
                                                            <div class="form-group has-clear position-relative">
                                                                <textarea name="notes" 
                                                            placeholder="Write your notes here" 
                                                            class="form-control fc-form-control--textarea" rows="7"><?php echo @$info['notes']; ?></textarea>                                                        
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    
                                    </div>
                                    <div class="fencing-section__bottom py-3">
                                        <div class="fc-tab-control">

                                            <div class="fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                                                
                                                <button type="button" 
                                                    class="btn btn-orange fc-btn-create-plan fencing-btn-modal me-2 py-2 px-4 text-uppercase mb-2" 
                                                    data-target="#submit-modal" 
                                                    disabled>
                                                    <strong>Create Project Plan</strong><br>
                                                    <small>View Costing, Plan & Materials List</small>
                                                </button>
                                                
                                                <button type="button" 
                                                    class="btn fc-btn-step btn-secondary text-uppercase py-3 px-4 mb-2" 
                                                    data-tab="2" 
                                                    data-move="1"><b><i class="fa-solid fa-angle-left me-2"></i> Back</b>
                                                </button>

                                            </div>

                                        </div>

                                    </div>

                            </div>

                        </div>

                        
                    </div>
                </div>

                <?php include 'temp/modal.php'; ?>

            </form>

        </div>


        <script type="text/javascript">
        var fc_data       = <?php echo json_encode($fences); ?>;
        var fc_fence_info = <?php echo json_encode($res); ?>;
        var planner_id    = "<?php echo @$_SESSION['planner_id']; ?>";  
        </script>

        <?php include 'temp/partials/footer.php'; ?>

        <script type="text/javascript" src="<?php echo base_url(); ?>js/cart-items.js?v=<?php echo date('YmdHis'); ?>"></script>
        <script type="text/javascript" src="<?php echo base_url(); ?>js/p1.js?v=<?php echo date('YmdHis'); ?>"></script>

    </body>
</html>