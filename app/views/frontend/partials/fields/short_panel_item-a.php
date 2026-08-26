<div class="fencing-panel-spacing-number fpsn-a">
    <span>{{center_point}}</span>                 
</div>

<div id="panel-item-{{panel_number}}" 
	data-id="{{panel_number}}"
	data-cart-key="panel_options" 
	data-cart-value="{{panel_value}}"
	data-key="panel_options"
	data-panel-size="{{panel_size}}"
	class="fencing-panel-item short-panel-item panel-item">

	<div class="fencing-btn-modal" 
		data-key="panel_options_custom" 
		data-target="#fc-control-modal">

		<div class="fencing-panel-item-size">
			<span class="fc-panel-size">{{panel_size}}</span>
			<span class="fc-panel-unit">{{panel_unit}}</span>
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

<div data-cart-key="panel_post" 
	class="panel-post fencing-btn-modal" 
	data-key="post_options" 
	data-target="#fc-control-modal"></div>
