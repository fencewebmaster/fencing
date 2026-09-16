<?php /* [START] TABS */ ?>
<?php /* Same folder strip as the planner's step 4 header tabs (.fc-header-tab), so the two
     pages read as one flow. No fc-d-none here: this strip is always on, and the class only
     looked inert on the old markup because a later .fencing-tabs-container rule outranked it. */ ?>
<div class="fc-header-tab fc-section-step fc-font-2" data-tab="1">
    <div class="fc-header-tab__area">

        <a href=".?step=3">
            <div class="fencing-tab-name">Section Details</div>
        </a>

        <a href=".?tab=2">
            <div class="fencing-tab-name">Project Options</div>
        </a>

        <a class="fc-tab-active tab-selected">
            <div class="fencing-tab-name">Project Plan &amp; Cart</div>
        </a>

    </div>
</div>
<?php /* [END] TABS */ ?>
