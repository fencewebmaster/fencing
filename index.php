<?php 
	include('data/settings.php');
	include('temp/fields.php');
	include('helpers.php');
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">
<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">


<!-- START FORM SUBMISSION LOADER -->
<div class="fc-loader-overlay" style="display: none;">
    <div class="fc-loader-container">
        <div class="fc-loader">
            <div class="fc-row">
                
                <div class="fc-col-auto">
                    <img src="<?php echo base_url('img/loaders/1.gif'); ?>" width="120">
                </div>

                <div class="fc-col-auto">
                    
                    <ul>
                        <li>
                            <div class="fc-mb-1"><small style="font-size:30px;">Preparing:</small></div>
                        </li>
                        <li><i class="fa fa-check fc-mr-1"></i> Creating your plan... </li>
                        <li><i class="fa fa-check fc-mr-1"></i> Calculating your materials...</li>
                        <li><i class="fa fa-check fc-mr-1"></i> Confirming details...</li>
                        <li></li>
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
        <div class="fencing-tabs-container fc-section-step fc-d-none fc-font-2" data-tab="1">
            <div class="fencing-tabs fc-row-flex">
               
                <div class="fencing-tab-container fc-row-flex">
                   
                    <div class="fencing-tab fencing-tab-selected fc-d-none">
                        <div class="fencing-tab-name">
                            <span class="ftm-title">SECTION</span> <span class="fencing-tab-number">1</span>
                            <div class="ftm-measurement">10000 mm</div>
                        </div>
                    </div>

                </div>

                <a href="#" class="fencing-tab-add fc-d-none">
                &#43; Add Section			
                </a>

            </div>
        </div>
        <!-- END TABS -->

        <div class="fc-section-details">
            
            <!-- @TODO: Are we hiding this header tab? It no longer exists in figma -->
            <div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="1">
                <a href="#" data-tab="1" data-move="2" class="tab-selected">Section Details</a>
            </div>
            
            <div class="fc-header-tab fc-section-step fc-d-none fc-font-2" data-tab="2" style="display:none;">
                <a href="#" data-tab="1" data-move="2" class="">Section Details</a>
                <a href="#" data-tab="2" data-move="1" class="fc-tab-active tab-selected">Project Options</a>	
            </div>

            <div class="fencing-content fc-font-1">
                <div class="fc-section-step" data-tab="1">
                    
                    <div class="fc-content-tab-title">
                        <span class="fc-tab-title"></span><span class="fc-tab-subtitle"></span>
                    </div>

                    <div class="fencing-section fencing-section--step1">
                        
                        <buton type="button" 
                            class="btn-fc btn-fc-outline-danger btn-fc-sm fc-float-r btn-delete-fence" 
                            style="display:none;">
                            <i class="fa fa-trash-can"></i>Delete <span>Section</span>
                        </buton>
                        
                        <div class="step-label">Step <span>01</span></div>

                        <h4 class="fencing-content-title">Choose Your Fencing Style</h4>
                    </div>


                    <!-- START STYLES -->
                    <div class="fencing-section fencing-styles">
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

                                <button class="fencing-style-btn js-fencing-style-btn"></button>

                            </div>
                        </div>
                        <?php endforeach; ?>	
                        </div>			
                    </div>
                    <!-- END STYLES -->


                    <!-- START MEASUREMENT -->	
                    <div class="fc-form-step fc-d-none" data-section="2" style="display: none;">
                        
                        <div class="fencing-divider"></div>

                        <div class="fencing-section fencing-measurement fencing-section--step2">

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


                    <div class="fc-form-step fc-d-none fc-position-relative" data-section="3" style="display: none;">
                        
                        <div class="fencing-divider"></div>

                        <div class="fencing-calculating">
                            <div class="fc-calculating-loader">
                                <img src="//<?php echo $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; ?>img/loaders/1.gif" width="60">
                                <h4 class="fc-text-uppercase">Calculating ...	</h4>
                            </div>
                        </div>


                        <!-- START DISPLAY RESULT -->	
                        <div class="fencing-section fencing-section--step3">
                            
                            <div class="step-label">Step <span>03</span></div>

                            <h4 class="fencing-content-title fc-mb-2">Configure this fence section</h4>

                            <div class="fencing-section__controls">
                              
                                <a href="#" class="fc-zoom-reset" data-zoom="reset">
                                    <i class="fc-icon fc-icon-arrow-cc"></i>
                                </a>

                                <a href="#" style="display: none;">
                                    <i class="fc-icon fc-rectangle"></i>
                                </a>

                                <a href="#" class="fc-zoom-fence" data-zoom="in">
                                    <i class="fc-icon fc-magnify-plus"></i>
                                </a>

                                <a href="#" class="fc-zoom-fence" data-zoom="out">
                                    <i class="fc-icon fc-magnify-munis"></i>
                                </a>

                            </div>

                        </div>

                        <div style="clear:both;"></div>

                        <div class="fencing-section fencing-display-result">
                            
                            <div class="fencing-result-msg" style="display: none;">
                                <p>No Valid Solution. Please adjust Measurements.</p>
                            </div>

                            <div class="fencing-panel-items">
                                <div class="fencing-panel-rail fencing-btn-modal" 
                                    data-key="rail_options" 
                                    data-target="#fc-control-modal" style="display:none;"></div>
                                <div class="fencing-panel-container"></div>
                            </div>

                        </div>


                        <!-- START PANEL CONTROLS -->	
                        <div class="fencing-section fencing-panel-controls"></div>
                        <!-- END PANEL CONTROLS -->

                        <!-- START PANEL CONTROLS | FOR TESTING ONLY -->	
                        <div class="fencing-section">
                            <button type="button" id="btn-left_side" data-key="left_side" data-target="#fc-control-modal-1" class="btn-fc btn-fc-outline-default fencing-btn-modal fc-mb-1">Edit Left Side 1</button>
                            <button type="button" id="btn-panel_options" data-key="panel_options" data-target="#fc-control-modal-4" class="btn-fc btn-fc-outline-default fencing-btn-modal fc-mb-1">Panel Options</button>
                            <button type="button" id="btn-post_options" data-key="post_options" data-target="#fc-control-modal-2" class="btn-fc btn-fc-outline-default fencing-btn-modal fc-mb-1">Post Options</button>
                            <button type="button" id="btn-gate" data-key="gate" data-target="#fc-control-modal-3" class="btn-fc btn-fc-outline-default fencing-btn-modal fc-mb-1">Add Gate</button>
                        </div>
                        <!-- END PANEL CONTROLS | FOR TESTING ONLY -->


                    </div>
                    <!-- END DISPLAY RESULT -->


                    <div class="fencing-divider"></div>

                </div>

                <div class="fc-section-step fc-d-none fc-step-4" data-tab="2" style="display: none;">
                    
                    <div class="fencing-section">
                        
                        <div class="step-label">Step <span>04</span></div>
                        
                        <h4 class="fencing-content-title fc-mb-2">Configure Your Project</h4>
                      
                        <div class="fc-card fc-mb-2">
                            
                            <div class="fc-card-header fc-bg-dark fc-border-top">
                                Colour Options
                            </div>

                            <div class="fc-card-body fc-border-bottom fc-p-0 fc-border">
                                <div class="fencing-form-group fc-mb-0">
                                    <div class="fc-row fc-form-field fc-color-options" data-key="color_options" name="color_options" type="text_option" value="">
                                        <div class="fc-scrollable">
                                            <div class="fc-scrollable-area">
                                        <?php foreach($color_options as $co): ?>
                                        <div class="fc-col-3">
                                            <div class="fc-select-item fc-select fc-mb-0" data-slug="" style="background:<?php echo $co['background_color']; ?>;color:<?php echo $co['text_color']; ?>;">
                                                <p><?php echo $co['title']; ?></p>
                                                <span><?php echo $co['sub_title']; ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                            </div>
                                        </div>

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
                                            class="fc-form-control" rows="7"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="fc-tab-control">

                    <div class="fencing-section fc-section-step" data-tab="1">
                        
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
                    </div>

                    <div class="fencing-section fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                        
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


        <!-- START POPUP MODAL CONTROL PANEL -->
        <div id="fc-control-modal" class="fencing-modal">
            <div class="fc-modal-frame">
                
                <div class="fencing-modal-content">
                   
                    <span class="fencing-modal-close">&times;</span>
                   
                    <div class="fencing-modal-title fc-font-2">
                        Options
                    </div>
                    
                    <div class="fc-modal-divider"></div>
                    
                    <div class="fencing-modal-body fc-font-1"></div>
                    
                    <div class="fencing-modal-notes"></div>

                </div>
            </div>
        </div>
        <!-- END POPUP MODAL CONTROL PANEL -->

        <!-- START POPUP MODAL CONTROL PANEL | TESTING-->
        <div id="fc-control-modal-1" class="fencing-modal fencing-modal--v2">
            <div class="fc-modal-frame">
                <div class="fencing-modal-content">
                    
                    <div class="fencing-modal-area">
                        <div class="fencing-modal-header">
                            <div class="fencing-modal-title js-fc-ignore fc-font-2">Edit Left Side</div>
                            <span class="fencing-modal-close">×</span>
                        </div>
                        <div class="fencing-modal-body js-fc-ignore fc-font-1 fc-p-0">
                            <div class="fencing-form-group">
                                <h2 class="body-title"></h2>
                                <div class="fc-row-container">
                                    <div class="fc-row fc-form-field" name="left_option" type="range_option" value="yes-post">
                                        <div class="fc-col-4 fc-text-center">
                                            <div class="fc-select-post fc-select fc-selected" data-slug="yes-post">
                                                <img src="img/yes-post.png">
                                            </div>
                                            <p></p>
                                        </div>
                                        <div class="fc-col-4 fc-text-center">
                                            <div class="fc-select-post fc-select" data-slug="no-post">
                                                <img src="img/no-post-1.png">
                                            </div>
                                            <p></p>
                                        </div>
                                        <div class="fc-col-4 fc-text-center">
                                            <div class="fc-select-post fc-select" data-slug="no-post-swivel-bracket">
                                                <img src="img/no-post-2.png">
                                            </div>
                                            <p></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="fencing-modal-notes js-fc-ignore">
                            <div class="fc-selection-details fc-alert-gray">
                                <label><i class="fc-icon fc-icon-capa"></i> When To Use Swivel Brackets</label>
                                <p class="fc-text-gray">Swivel brackets are used instead of the standards straight brackets. This allow you
                                    to connect this fence section at an angle. e.g. 45degs to the connecting fence section</p>
                            </div>
                        </div>
                    </div>
                    <div class="fencing-modal-area">
                        <div class="fencing-modal-header">
                            <div class="fencing-modal-title fc-font-2 js-fc-ignore">Post Options</div>
                        </div>
                        <div class="fencing-modal-body fc-font-1 fc-p-0 js-fc-ignore">
                            <div class="fc-row-container fencing-form-group">
                                <div class="fc-row fc-form-field js-fc-ignore" name="post_option" type="image_option" value="opt-2">
                                    <div class="fc-col-4 fc-text-center">
                                        <div class="fc-select-post fc-select" data-slug="opt-1">
                                            <img src="img/base-plate-posts.png" class="fc-fullwidth">
                                        </div>
                                        <p></p>
                                        <p></p>
                                    </div>
                                    <div class="fc-col-4 fc-text-center">
                                        <div class="fc-select-post fc-select fc-selected" data-slug="opt-2">
                                            <img src="img/cement-in-posts.png" class="fc-fullwidth">
                                        </div>
                                        <p></p>
                                        <p></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="fencing-modal-area">
                        <div class="fencing-modal-header">
                            <div class="fencing-modal-title js-fc-ignore fc-font-2">Add Step-Up Panel</div>
                        </div>
                        <div class="fencing-modal-body js-fc-ignore fc-font-1 fc-p-0">
                            <div class="fc-dropdown_option">
                                <h2 class="body-title">Right Hand Step-Up Panel</h2>
                                <div class="fc-row js-fc-ignore">
                                    <select class="fc-form-field fc-select-option js-fc-ignore" name="right_raked" type="dropdown_option" value="">
                                        <option value="none">Nil</option>
                                        <option value="1300x300">1300H - 300 Step-Up</option>
                                        <option value="1400x400">1400H - 400 Step-Up</option>
                                        <option value="1500x500">1500H - 500 Step-Up</option>
                                        <option value="1600x600">1600H - 600 Step-Up</option>
                                        <option value="1700x700">1700H - 700 Step-Up</option>
                                        <option value="1800x600">1800H - 600 Step-Up</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div id="fc-control-modal-2" class="fencing-modal fencing-modal--v2">
            <div class="fc-modal-frame">
                <div class="fencing-modal-content">
                <div class="fencing-modal-area">
                        <div class="fencing-modal-header">
                            <div class="fencing-modal-title fc-font-2 js-fc-ignore">Post Options</div>
                            <span class="fencing-modal-close">×</span>
                        </div>
                        <div class="fencing-modal-body fc-font-1 fc-p-0 js-fc-ignore">
                            <div class="fc-row-container fencing-form-group">
                                <div class="fc-row fc-form-field js-fc-ignore" name="post_option" type="image_option" value="opt-2">
                                    <div class="fc-col-4 fc-text-center">
                                        <div class="fc-select-post fc-select" data-slug="opt-1">
                                            <img src="img/base-plate-posts.png" class="fc-fullwidth">
                                        </div>
                                        <p></p>
                                        <p></p>
                                    </div>
                                    <div class="fc-col-4 fc-text-center">
                                        <div class="fc-select-post fc-select fc-selected" data-slug="opt-2">
                                            <img src="img/cement-in-posts.png" class="fc-fullwidth">
                                        </div>
                                        <p></p>
                                        <p></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <div id="fc-control-modal-3" class="fencing-modal fencing-modal--v2">
            <div class="fc-modal-frame">
                <div class="fencing-modal-content">
                    <div class="fencing-modal-area fencing-modal-area--gate fc-p-0">

                        <div class="fencing-modal-header">
                            <div class="fencing-modal-title js-fc-ignore fc-font-2">Add / Remove Gate</div>
                            <span class="fencing-modal-close">×</span>
                        </div>

                        <div class="fencing-modal-body js-fc-ignore fc-font-1">
                            <div class="fc-control-move fencing-form-group">
                                <h2 class="body-title"></h2>
                                <div class="fc-row">

                                    <div class="fc-row-flex">
                                        <div class="fc-col-flex fc-text-center">
                                            <div class="fc-move-post" data-move="first">
                                                <p><span>⇤</span><br>First</p>
                                            </div>
                                        </div>
                                        <div class="fc-col-flex fc-text-center">
                                            <div class="fc-move-post" data-move="left">
                                                <p><span>←</span><br>Left</p>
                                            </div>
                                        </div>
                                        <div class="fc-col-flex fc-text-center">
                                            <div class="fc-move-post" data-move="delete">
                                                <p><span>✕</span><br>Delete</p>
                                            </div>
                                        </div>
                                        <div class="fc-col-flex fc-text-center">
                                            <div class="fc-move-post" data-move="right">
                                                <p><span>→</span><br>Right</p>
                                            </div>
                                        </div>
                                        <div class="fc-col-flex fc-text-center">
                                            <div class="fc-move-post" data-move="last">
                                                <p><span>⇥</span><br>Last</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="fencing-modal-notes js-fc-ignore"></div>

                    </div>
                </div>
            </div>
        </div>

        <div id="fc-control-modal-4" class="fencing-modal fencing-modal--v2">
            <div class="fc-modal-frame">
                <div class="fencing-modal-content">
                    <div class="fencing-modal-area fencing-modal-area--panel-options">
                        <div class="fencing-modal-header">
                            <div class="fencing-modal-title js-fc-ignore fc-font-2">Panel Options</div>
                            <span class="fencing-modal-close">×</span>
                        </div>
                        <div class="fencing-modal-body js-fc-ignore fc-font-1 fc-p-0">
                            <div class="fencing-form-group">
                                <h2 class="body-title"></h2>
                                <div class="fc-row-container">
                                    <div class="fc-row fc-form-field" name="panel_option" type="text_option" value="even">
                                        <div class="fc-col-4 fc-text-center">
                                            <div class="fc-select-post fc-select fc-selected" data-slug="even">
                                                <p>Even Size Panels</p>
                                            </div>
                                        </div>
                                        <div class="fc-col-4 fc-text-center">
                                            <div class="fc-select-post fc-select" data-slug="full_2400">
                                                <p>Use 2400W Panels</p>
                                            </div>
                                        </div>
                                        <div class="fc-col-4 fc-text-center">
                                            <div class="fc-select-post fc-select" data-slug="full_3000">
                                                <p>Use 3000W Panels</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="fencing-modal-notes js-fc-ignore">
                            <div class="fc-selection-details">
                                <label>Even Size Panels</label>
                                <p>This option evenly spaces out the posts, which also means you will need to cut down every individual
                                    panel.</p>
                            </div>
                            <div class="fc-selection-details">
                                <label>Use 2400W / 3000W Panels</label>
                                <p>This option uses full length panels, which means you will ONLY need to cut down 1x panel. </p>
                            </div>
                            <div class="fc-selection-details fc-alert-gray">
                                <label><i class="fc-icon fc-icon-capa"></i> Panel Off-Cuts</label>
                                <p class="fc-text-gray">The off-cut can be used for another fence section (where applicable). If the off-cut
                                    is used ensure you manually update the panel quantities to account for this as this planner does NOT use
                                    Off-Cuts.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- END POPUP MODAL CONTROL PANEL | TESTING -->


        <!-- START SUBMIT POPUP MODAL -->
        <div id="submit-modal" class="fencing-modal">
            <div class="fc-modal-frame">

                <div class="fencing-modal-content fencing-modal-md">
                   
                    <div class="fencing-modal-head">
                        <div class="fencing-modal-title">
                            <h4>Download Your Project Plans</h4>
                        </div>
                        <span class="fencing-modal-close">&times;</span>
                    </div>

                    <div class="fencing-modal-body fc-mb-4">
                        
                        <div class="fc-alert-gray fc-mb-3">
                            <h4>&#127881; Awesome! We’ll email you the plans..</h4>
                            <p class="fc-text-gray">Simply enter your details below and we’ll send you your plans to download or print for your reference.</p>
                        </div>

                        <div class="fc-form-plan" data-formtab="1">


                            <!-- START FORM MODAL PROGRESS BAR - STEP 1 -->                            
                            <div class="fc-progress-container fc-float-r">
                                <div class="fc-progress-tabs">
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value"></div>
                                    <div class="fc-progress-value"></div>
                                    <div class="fc-progress-value"></div>
                                </div>
                                <span>1/4</span>				
                            </div>
                            <!-- END FORM MODAL PROGRESS BAR - STEP 1 -->


                            <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Project Details</h2>

                            <div class="fc-form-group fc-mb-1">
                                <div class="fc-row-container">
                                    <div class="fc-col-half">
                                        <div class="fc-label-group">
                                            <label class="fc-form-label">Name <span class="fc-text-danger">*</span></label>
                                            <input type="text" name="name" id="name" class="fc-form-control" placeholder="Your Name" required>					
                                        </div>
                                    </div>
                                    <div class="fc-col-half">
                                        <div class="fc-label-group">
                                            <label class="fc-form-label">Mobile <span class="fc-text-danger">*</span></label>
                                            <input type="text" name="mobile" id="mobile" class="fc-form-control" placeholder="Your phone number" required>							
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fc-form-group fc-mb-2">
                                <div class="fc-label-group">
                                    <label class="fc-form-label">Email <span class="fc-text-danger">*</span></label>
                                    <input type="text" name="email" id="email" class="fc-form-control no-space" placeholder="Your Email" required>	
                                </div>
                            </div>

                            <div class="fc-form-group fc-mb-1">
                                <div class="fc-label-group">
                                    <label class="fc-form-label">Address <span class="fc-text-danger">*</span></label>
                                    <input type="text" name="address" id="address" class="fc-form-control" placeholder="Your Address" required>  
                                </div>
                            </div>

                            <div class="fc-form-group fc-mb-1">
                                <div class="fc-row-container">
                                   
                                    <div class="fc-col-half">
                                        <div class="fc-label-group">
                                            <label class="fc-form-label">Post Code <span class="fc-text-danger">*</span></label>
                                            <input type="text" name="postcode" id="postcode" class="fc-form-control" placeholder="Post Code" required>					
                                        </div>
                                    </div>

                                    <div class="fc-col-half">
                                        <div class="fc-label-group">
                                            <label class="fc-form-label">State <span class="fc-text-danger">*</span></label>
                                            <select name="state" class="fc-form-control" required>
                                                <option value="">Select an option…</option>
                                                <?php foreach( fc_state() as $state_k => $state_v ): ?>
                                                <option value="<?php echo $state_k; ?>"><?php echo $state_v; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>


                            <p class="fc-mb-2 fc-text-gray"><span class="fc-text-danger">*</span> = Required</p>
                            

                            <!-- START FORM MODAL ACTIONS - STEP 1 -->
                            <div class="fc-form-plan-action">
                                <button type="button" class="btn-fc fc-btn-form-step fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b" data-move="2"><b>Next > Time Frame</b></button>				
                            </div>
                            <!-- END FORM MODAL ACTIONS - STEP 1 -->


                        </div>

                        <div class="fc-form-plan" data-formtab="2" style="display: none;">


                            <!-- START FORM MODAL PROGRESS BAR - STEP 2 -->                            
                            <div class="fc-progress-container fc-float-r">
                                <div class="fc-progress-tabs">
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value"></div>
                                    <div class="fc-progress-value"></div>
                                </div>
                                <span>2/4</span>				
                            </div>
                            <!-- END FORM MODAL PROGRESS BAR - STEP 2 -->


                            <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">When Do You Need The Materials?</h2>
                            
                            <div class="fc-form-group fc-form-check fc-mb-3">
                                
                                <?php foreach( fc_timeframe() as $timeframe_k => $timeframe_v ): ?>
                                <label class="fc-mb-1">
                                <input type="radio" name="timeframe" value="<?php echo $timeframe_k; ?>" class="fc-mr-1" required>
                                <?php echo $timeframe_v; ?>
                                </label>
                                <?php endforeach; ?>

                            </div>


                            <!-- START FORM MODAL ACTIONS - STEP 2 -->
                            <div class="fc-form-plan-action">
                                <button type="button" 
                                    class="btn-fc fc-btn-form-step btn-fc-outline-default fc-text-uppercase fc-w-b" 
                                    data-move="1"><b>Back</b>
                                </button>	
                                <button type="button" 
                                    class="btn-fc fc-btn-form-step fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b" 
                                    data-move="3"><b>Next > Install Options</b>
                                </button>					
                            </div>
                            <!-- END FORM MODAL ACTIONS - STEP 2 -->


                        </div>

                        <div class="fc-form-plan" data-formtab="3" style="display: none;">

                            <!-- START FORM MODAL PROGRESS BAR - STEP 3 -->                            
                            <div class="fc-progress-container fc-float-r">
                                <div class="fc-progress-tabs">
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value"></div>
                                </div>
                                <span>3/4</span>				
                            </div>
                            <!-- END FORM MODAL PROGRESS BAR - STEP 3 -->


                            <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Do You Need An Installer?</h2>
                            
                            <div class="fc-form-group fc-form-check fc-mb-3">
                                
                                <?php foreach( fc_installer() as $installer_k => $installer_v ): ?>
                                <label class="fc-mb-1">
                                <input type="radio" name="installer" value="<?php echo $installer_k; ?>" class="fc-mr-1" required>
                                <?php echo $installer_v; ?>

                                <?php if( $installer_k == 'no'): ?>
                                <span class="fc-label-badge fc-ml-1">Cheaper</span>
                                <?php endif; ?>

                                </label>    
                                <?php endforeach; ?>

                            </div>


                            <!-- START FORM MODAL ACTIONS - STEP 3 -->
                            <div class="fc-form-plan-action">
                                <button type="button" class="btn-fc fc-btn-form-step btn-fc-outline-default fc-text-uppercase fc-w-b" data-move="2"><b>Back</b></button>
                                <button type="button" class="btn-fc fc-btn-form-step fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b" data-move="4"><b>Next > Needed Options</b></button>
                            </div>
                            <!-- END FORM MODAL ACTIONS - STEP 3 -->


                        </div>

                        <div class="fc-form-plan form-tab-4" data-formtab="4" style="display: none;">
                            

                            <!-- START FORM MODAL PROGRESS BAR - STEP 4 -->
                            <div class="fc-progress-container fc-float-r">
                                <div class="fc-progress-tabs">
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value pt-complete"></div>
                                    <div class="fc-progress-value pt-complete"></div>
                                </div>
                                <span>4/4</span>				
                            </div>
                            <!-- END FORM MODAL PROGRESS BAR - STEP 4 -->
                            

                            <h2 class="fc-text-uppercase fc-font-2 fc-mb-2">Anything Else We Can Help You With?</h2>

                            <div class="fc-other-products fc-form-group fc-mb-2">
                                <div class="fc-row-container">

                                    <?php foreach( fc_extra_needed() as $extra_k => $extra_v ): ?>
                                    <div class="fc-col-3">
                                        <div class="fc-form-check-img fc-rounded">
                                            <label class="fc-form-check">
                                            <img class="fc-rounded" src="img/plans/<?php echo $extra_k; ?>.png">								
                                            <input type="checkbox" name="extra[]" value="<?php echo $extra_k; ?>">
                                            </label>
                                            <p class="fc-text-center"><?php echo $extra_v; ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>

                                    <div class="fc-col-3">
                                        <div class="fc-form-check-img fc-form-check-empty fc-rounded">
                                            <label class="fc-form-check">
                                                <div class="fc-empty-img">
                                                    Nothing Extra<br>
                                                    Just Fencing
                                                </div>
                                                <input type="radio" name="nothing_extra" value="nothing">
                                            </label>
                                            <p class="fc-text-center">NIL - Just Looking</p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            

                            <!-- START FORM MODAL ACTIONS - STEP 4 | SUBMIT -->
                            <div class="fc-form-plan-action">
                                <button type="button" 
                                    class="btn-fc fc-btn-form-step btn-fc-outline-default fc-text-uppercase fc-w-b" 
                                    data-move="3"><b>Back</b>
                                </button>

                                <button type="submit" 
                                    class="btn-fc fc-btn-next btn-fc-orange fc-text-uppercase fc-w-b">
                                    <b>Done > View Plans & Costs</b>
                                </button>
                            </div>
                            <!-- END FORM MODAL ACTIONS - STEP 4 | SUBMIT -->


                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END SUBMIT POPUP MODAL -->


    </form>
</div>

<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>
<script type="text/javascript" src="js/jquery-scrollspy.min.js"></script>

<script type="text/javascript">
var fc_data = <?php echo json_encode($fences); ?>;
var fc_mbn  = 11000;

$('.measurement-box-number').val(fc_mbn);
</script>

<script type="text/javascript" src="js/functions.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/events.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/calc.js?v=<?php echo date('YmdHis'); ?>"></script>
<script type="text/javascript" src="js/p1.js?v=<?php echo date('YmdHis'); ?>"></script>




