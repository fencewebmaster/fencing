
<div class="raked-panel-container">

	<div class="fencing-right-panel-o-a">

		<div id="panel-item-{{panel_number}}" 
		class="fencing-panel-item fencing-raked-panel fencing-right-panel-a fencing-btn-modal panel-item" 
		data-id="{{panel_number}}" 
		data-cart-key="right_raked_panel" 
		data-cart-value="{{panel_size}}x{{panel_height}}" 
		data-key="right_side" 
		data-target="#fc-control-modal">

			<div class="fencing-panel-item-size">{{panel_size}}H <br> {{panel_unit}}W</div>

		</div>
		
	</div>

	<div class="fc-center-point fc-first-c-p">
		<span class="fc-div-c-p"></span>
	    <span class="fc-start-c-p">{{center_post}}</span>
	    {{panel_size_center}}<br>
	    Centers
	</div>

	<div class="fc-center-point fc-last-c-p">
	    <span class="fc-div-c-p"></span>
	    <span class="fc-div-c-p"></span>
	    <span class="fc-end-c-p">{{center_post}}</span>
	</div>

    <div class="fencing-panel-spigots panel-post">
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>		
	</div>

	<div class="fencing-panel-pinfixes" style="display: none;">
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>						
	</div>

</div>

<div class="raked-panel-post panel-post panel-{{post}} fencing-btn-modal"  
	data-cart-key="raked_post" 
	data-cart-value="" 
	data-key="right_side" 
	data-target="#fc-control-modal"></div>

<div class="fencing-panel-spacing-number fpsn-a {{post}}">
	<span>{{center_point}}</span>					
</div>
