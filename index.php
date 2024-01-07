<?php 
session_start();
$info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

if( $sid = @$_GET['sid'] && $url = @$_GET['url'] ) {
    $_SESSION["site"] = [
        'id'  => $sid,
        'url' => $url
    ];     
    header("Location: ./");
    die();
} 

include 'data/settings.php';
include 'temp/fields.php';
include 'helpers.php';
include 'config/database.php'; 

if( ! isset($_SESSION['url']) ) {
    $_SESSION["site"] = [
        'id'  => 1,
        'url' => is_localhost() ? 'fencesperth.com' : $_SERVER['HTTP_HOST'] 
    ];         
}

$res = array();
if( isset($_GET['planner_id']) ) {
    $db = new Database();
    $res = $db->select_where('planners', 'WHERE `planner_id` LIKE "'.$_GET['planner_id'].'"');    
}
?>

<title>Fencing Calculator</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">
<link rel="stylesheet" type="text/css" href="style-v2.css?v=<?php echo date('YmdHis'); ?>">
<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">

<style type="text/css">
/* Temporary solution to hide frameless pool from the selection */
.fencing-style-item:first-child {
    display: none;
}    
.fc-center-point {
    display: none;
}
.fencing-style-item .fencing-style-btn {
    border: 2px solid #ffffff;
    box-shadow: 1px 1px 4px 1px #939393;
    color: #f67925;
}
</style>

<!-- START FORM SUBMISSION LOADER -->
<div class="fc-loader-overlay" style="display: none;">
    <div class="fc-loader-container">
        <div class="fc-loader">
            <div class="fc-row">
                
                <div class="fc-col-auto">
                    <div class="fc-loader-gif"></div>
                </div>

                <div class="fc-col-auto">
                    
                    <ul>
                        <li class="fc-text-success li-create">
                            <div class="fc-mb-1"><small style="font-size:30px;">Creating your plan...</small></div>
                        </li>
                    </ul>

                </div>

            </div>
        </div>
    </div>
</div>
<!-- END FORM SUBMISSION LOADER -->

<div class="fencing-container w-side-section" data-tab="1">
    <form method="POST" id="fc-download-form" action="project-plan.php">

        <div class="fencing-container__header">
            <div class="fc-row">
                
                <div class="fc-col-half">
                    <h2 class="fc-header-title">Fencing Calculator</h2>
                    <p>Calculate your fence cost and the materials needed.</p>
                </div>

                <div class="fc-col-half">
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

        <!-- START TABS -->
        <div class="fencing-tabs-container js-fencing-tabs-container fc-section-step fc-d-none fc-font-2 " data-tab="1">
            <div class="fencing-tabs-area">
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
            
            <!-- @TODO: Are we hiding this header tab? It no longer exists in figma -->
            <!-- <div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="1">
                <div class="fc-header-tab__area">
                    <a href="#" data-tab="1" data-move="2" class="tab-selected">Section Details</a>
                </div>
            </div> -->
            
            <div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="2" style="display:none;">
                <div class="fc-header-tab__area">
                    <a href="#" data-tab="2" data-move="1" class="fc-btn-step">Section Details</a>
                    <a href="#" data-tab="2" data-move="1" class="fc-tab-active tab-selected">Project Options</a>
                </div>	
            </div>


            <div class="fencing-content fc-font-1">
                <div class="fc-section-step" data-tab="1">
                    
                    <div class="fc-content-tab-title">
                        <span class="fc-tab-title"></span><span class="fc-tab-subtitle"></span>
                    </div>

                    <div class="fencing-section fencing-section--has-border fencing-section--no-radius-top">
                    
                        <!-- START STYLES AREA -->
                        <div class="fencing-section__cmp fencing-section-step fencing-section--step1">

                            <div class="btn-delete-fence">

                                <button type="button" 
                                    class="btn-fc btn-fc-outline-danger btn-fc-sm js-btn-delete-fence" 
                                    style="display:none;">
                                    <span><i class="fa fa-trash-can"></i>Delete <span>Section</span></span>
                                </button>

                                <button type="button" 
                                    class="btn-fc btn-fc-outline-danger btn-fc-sm fc-fence-reset-all fc-ml-1" 
                                    style="display:none;">
                                    <span><i class="fa-solid fa-circle-minus"></i> Reset</span>
                                </button>

                            </div>

                            <div class="step-label">Step <span>01</span></div>

                            <h4 class="fencing-content-title">Choose Your Fencing Style</h4>
                        </div>

                        <!-- START STYLES -->
                        <div class="fencing-section__cmp fencing-styles">
                            <div class="fencing-styles__area">
                            <?php foreach( $fences as $fence ): ?>
                            <div class="fencing-style-item" data-title="<?php echo $fence['title']; ?>">
                                <div>

                                    <div class="fencing-style-img">
                                        <img src="<?php echo $fence['image']; ?>">				
                                    </div>

                                    <div class="fencing-style-title">
                                        <?php echo $fence['title']; ?>
                                    </div>

                                    <button class="fencing-style-btn js-fencing-style-btn fc-fence-reset-all"><i class="fa fa-times"></i></button>

                                </div>
                            </div>
                            <?php endforeach; ?>	
                            </div>			
                        </div>
                        <!-- END STYLES -->

                    </div>

                                
                    <!-- START MEASUREMENT -->	
                    <div class="fencing-section fencing-section--has-border js-fc-form-step fc-d-none" data-section="2" style="display: none;">
                        
                        <div class="fencing-measurement fencing-section--step2">

                            <div class="fc-row">
                                
                                <div class="fc-col-half fc-input-container">
                                    
                                    <div class="step-label">Step <span>02</span></div>

                                    <h4 class="fencing-content-title fc-mb-2">Enter the overall length (mm)</h4>
                                    
                                    <div class="fencing-measurement-box">
                                    
                                        <span class="fencing-mb-input">
                                            
                                            <input type="text" class="measurement-box-number numeric" value=""> 
                                            
                                            <span>mm</span>				
                                            
                                            <div class="fencing-qty-plus fencing-qty-btn">
                                                <img src="img/quantity-plus.png" alt="" title="">
                                            </div>

                                            <div class="fencing-qty-minus fencing-qty-btn">
                                                <img src="img/quantity-minus.png" alt="" title="">
                                            </div>

                                        </span>

                                        <button type="button" class="btn-fc btn-fc-calculate">Calculate</button>

                                    </div>

                                    <div class="fc-input-msg error-msg"></div>
                                </div>

                                <div class="fc-col-half">
                                    <div class="fc-alert-gray fc-step-2-alert">
                                        <h3 class="fc-mb-1"><i class="fc-icon fc-icon-capa"></i> Measuring Your Fencing Section</h3>
                                        <p class="fc-text-gray">Ensure your overall dimension includes the posts each end.
                                            NOTE: "Panel & Post Options" below will deduct based on options selected.
                                        </p>
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
                                    <h4 class="fc-text-uppercase">Calculating ...	</h4>
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
                        
                        <div class="fencing-section__bottom">
                            <div class="fc-tab-control">

                                <div class="fc-section-step" data-tab="1">

                                    <?php if( isset($_SESSION['fc_data']) ): ?>
                                    <button type="submit" 
                                        class="btn-fc btn-fc-orange fc-btn-update">
                                        <b>UPDATE <i class="fa-regular fa-pen-to-square"></i></b>
                                    </button>
                                    <?php endif; ?>

                                    <button type="button" 
                                        class="btn-fc btn-fc-orange fc-btn-next-step fc-btn-step" 
                                        data-tab="1" 
                                        data-move="2"
                                        disabled>
                                        <b>NEXT - Select PLAN OPTIONS &#8594;</b>
                                    </button>

                                    <button type="button" 
                                        class="btn-fc btn-fc-outline-default fc-tab-add fencing-tab-add fc-px-2">
                                        <b>
                                            <i class="fa-solid fa-plus"></i> Add Another Section
                                        </b>
                                    </button>

                                    <button type="button" 
                                        class="btn-fc btn-fc-outline-default fc-fence-reset fc-text-uppercase">
                                        <b>
                                            <i class="fa-solid fa-rotate"></i> Reset
                                        </b>
                                    </button>

                                    <button type="button" 
                                        class="btn-fc btn-fc-outline-danger btn-fc-sm btn-delete-fence js-btn-delete-fence" 
                                        >
                                        <span><i class="fa fa-trash-can"></i>Delete <span>Section</span></span>
                                    </button>
                                </div>

                                <div class=" fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                                    
                                    <button type="button" 
                                        class="btn-fc btn-fc-orange fc-btn-create-plan fencing-btn-modal" 
                                        data-target="#submit-modal" 
                                        disabled>
                                        <strong>Create Project Plan</strong><br>
                                        <small>View Costing, Plan & Materials List</small>
                                    </button>

                                    <button type="button" 
                                        class="btn-fc fc-btn-step btn-fc-outline-default fc-text-uppercase fc-px-3" 
                                        data-tab="2" 
                                        data-move="1"><b>Back</b>
                                    </button>

                                </div>

                            </div>

                        </div>
                    </div>
                    <!-- END DISPLAY RESULT -->
                </div>

                <div class="fc-section-step  fc-d-none fc-step-4" data-tab="2" style="display: none;">
                    
                    <div class="fencing-section fencing-section--no-padding fencing-section--has-border br-tl-0">
                        
                            <div class="fencing-section__top">
                                <div class="step-label">Step <span>04</span></div>
                                
                                <h4 class="fencing-content-title fc-mb-2">Configure Your Project</h4>
                            
                                <div class="fc-card fc-mb-2">
                                    
                                    <div class="fc-card-header fc-bg-dark fc-border-top">
                                        Colour Options
                                    </div>

                                    <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
                                        <div class="fencing-form-group fc-mb-0">
                                            <div class="fc-row fc-form-field fc-color-options" data-key="color_options" name="color_options" type="text_option" value="">
                                                <?php foreach(fc_color() as $co_k => $co_v): ?>
                                                <div class="fc-col-3">
                                                    <div class="fc-select-item fc-select fc-mb-0" data-color-title="<?php echo $co_v['title']; ?>" data-color-code="<?php echo $co_v['background_color']; ?>" data-color-subtitle="<?php echo $co_v['sub_title']; ?>" data-slug="<?php echo $co_k; ?>" style="background:<?php echo $co_v['background_color']; ?>;color:<?php echo $co_v['text_color']; ?>;">
                                                        <p><strong><?php echo $co_v['title']; ?></strong><br />
                                                        <span><?php echo $co_v['sub_title']; ?></span></p>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>

                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="fc-card">
                                    
                                    <div class="fc-card-header fc-bg-dark fc-border-top">
                                        Project Notes & Additional Details
                                    </div>

                                    <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
                                        <div class="fc-p-1">
                                            <div class="fc-row">
                                                <div class="fc-col-half fc-lg-col-full">
                                                    <textarea name="notes" 
                                                    placeholder="Write your notes here" 
                                                    class="fc-form-control fc-form-control--textarea" rows="7"><?php echo @$info['notes']; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            
                            </div>
                            <div class="fencing-section__bottom">
                                <div class="fc-tab-control">

                                    <div class=" fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                                        
                                        <button type="button" 
                                            class="btn-fc btn-fc-orange fc-btn-create-plan fencing-btn-modal" 
                                            data-target="#submit-modal" 
                                            disabled>
                                            <strong>Create Project Plan</strong><br>
                                            <small>View Costing, Plan & Materials List</small>
                                        </button>
                                        
                                        <button type="button" 
                                            class="btn-fc fc-btn-step btn-fc-outline-default fc-text-uppercase fc-px-3" 
                                            data-tab="2" 
                                            data-move="1"><b>Back</b>
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

<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="js/jquery.validate.min.js"></script>
<script type="text/javascript" src="js/jquery-scrollspy.min.js"></script>

<script type="text/javascript">
var fc_data = <?php echo json_encode($fences); ?>;
var fc_fence_info = <?php echo json_encode($res); ?>;
console.log( fc_fence_info );
</script>

<script type="text/javascript" src="js/main.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/modal.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/calc.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/cart-items.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/p1.js?v=<?php echo date('YmdHis'); ?>"></script>



