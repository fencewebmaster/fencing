<?php
    include('helpers.php');
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="style.css?v=<?php echo date('YmdHis'); ?>">

<link rel="stylesheet" type="text/css" href="fonts/fa/css/all.min.css">
<script src="https://js.stripe.com/v3/" async></script>


<!-- START FENCING CONTAINER -->
<div id="place_order-section" class="fencing-container fc-project-plan fc-position-relative" data-tab="1">

    <!-- START PAGE HEADER TITLE -->
    <div class="fc-mb-2">
        <div class="fc-row">
            <div class="fc-col-half">
                <h2 class="fc-header-title">Fencing Calculator</h2>
                <p>Calculate your fence cost and the materials needed.</p>
            </div>
            <div class="fc-col-half">
                <div class="fc-flex-end">
                </div>
            </div>
        </div>
    </div>
    <!-- END PAGE HEADER TITLE -->


    <!-- START FENCING CONTENT -->
    <div class="fencing-content fc-font-1">

        <div class="fc-section-step">

            <div class="fencing-section">
                <a href="<?php echo base_url(); ?>" class="btn-fc btn-fc-orange fc-w-700 fc-float-r">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back
                </a>
                <div class="step-label">Thank You <span>For Your Order</span>!</div>
            </div>

            <div class="fencing-section">

            </div>
        </div>
    </div>
    <!-- END FENCING CONTENT -->

</div>
<!-- END FENCING CONTAINER -->

