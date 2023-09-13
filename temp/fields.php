<script type="text/text" data-type="move">
<div class="fc-control-move fencing-form-group">
	<h2 class="body-title">{{title}}</h2>
	<div class="fc-row">

		<div class="fc-row-flex">
			<div class="fc-col-flex fc-text-center">
				<div class="fc-move-post" data-move="first">	
					<p><span>&#8676;</span><br>First</p>
				</div>
			</div>
			<div class="fc-col-flex fc-text-center">
				<div class="fc-move-post" data-move="left">	
					<p><span>&#8592;</span><br>Left</p>
				</div>
			</div>
			<div class="fc-col-flex fc-text-center">
				<div class="fc-move-post" data-move="delete">	
					<p><span>&#10005;</span><br>Delete</p>
				</div>
			</div>
			<div class="fc-col-flex fc-text-center">
				<div class="fc-move-post" data-move="right">	
					<p><span>&#8594;</span><br>Right</p>
				</div>
			</div>
			<div class="fc-col-flex fc-text-center">
				<div class="fc-move-post" data-move="last">	
					<p><span>&#8677;</span><br>Last</p>
				</div>
			</div>
		</div>	
										
	</div>    		
</div>
</script>

<script type="text/text" data-type="range">
<div class="fencing-form-group fencing-input-range">
	<button type="button" class="fi-btn fir-minus">-</button>

	<div class="fir-input-group">
		<div class="fir-info">
			<span>{{default}}</span>{{unit}}			
		</div>
		<input name="{{field_name}}" class="fc-form-control fc-form-field" type="range" min="700" value="800" step="25" max="1000">		
		<div class="fir-info-sub">
			<span>{{sub_default}}</span>{{sub_unit}}			
		</div>
	</div>

	<button type="button" class="fi-btn fir-plus">+</button>
</div>	
</script>

<script type="text/text" data-type="range_option">
<div class="fencing-form-group">
	<h2 class="body-title">{{title}}</h2>
	<div class="fc-row-container">
		<div class="fc-row fc-form-field" name="{{field_name}}" type="{{type}}" value=""></div>	
	</div>			
</div>
</script>

<script type="text/text" data-type="dropdown_option">
<div class="fc-col-half fc-dropdown_option">
	<h2 class="body-title">{{title}}</h2>
	<div class="fc-row">
		<select class="fc-form-field fc-select-option" name="{{field_name}}" type="{{type}}" value="">
	</div>			
</div>
</script>

<script type="text/text" data-type="text_option">
<div class="fencing-form-group">
	<h2 class="body-title">{{title}}</h2>
	<div class="fc-row-container">
		<div class="fc-row fc-form-field" name="{{field_name}}" type="{{type}}" value=""></div>	
	</div>    		
</div>
</script>

<script type="text/text" data-type="range_icon">
<div class="fencing-form-group">
	<img src="{{image}}">
</div>

<div class="fencing-form-group fencing-input-range">
	<button type="button" class="fi-btn fir-minus">-</button>

	<div class="fir-input-group">
		<div class="fir-info">
			<span>{{default}}</span>{{unit}}			
		</div>
		<input name="{{field_name}}" class="fc-form-control fc-form-field" type="range" min="700" value="800" step="25" max="1000">				
	</div>

	<button type="button" class="fi-btn fir-plus">+</button>
</div>	
</script>

<script type="text/text" data-type="image_option">
<div class="fc-row-container fencing-form-group">
	<div class="fc-row fc-form-field" name="{{field_name}}" type="{{type}}" value=""></div>	
</div>
</script>

<script type="text/text" data-type="panel_item-a">
<div class="fencing-panel-spacing-number">
	<span>{{center_point}}</span>					
</div>

<div class="fc-empty-spacing"></div>

<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item" data-key="panel_options" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}} {{panel_unit}}</div>
	
	<div class="fencing-panel-spigots">
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>						
	</div>

	<div class="fencing-panel-pinfixes">
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>						
	</div>

</div>	
</script>

<script type="text/text" data-type="panel_item-b">
<div class="fencing-panel-spacing-number fpsn-b">
    <span>{{center_point}}</span>                 
</div>

<div class="panel-post fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></div>

<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item long-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}} {{panel_unit}}</div>
</div>	
</script>

<script type="text/text" data-type="short_panel_item-a">
<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item short-panel-item fencing-btn-modal panel-item" data-key="panel_options" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}} {{panel_unit}}</div>
	
	<div class="fencing-panel-spigots">
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>						
	</div>

	<div class="fencing-panel-pinfixes">
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>						
	</div>

</div>	
</script>

<script type="text/text" data-type="short_panel_item-b">
<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item short-panel-item fencing-btn-modal panel-item ally" data-key="panel_options" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}} {{panel_unit}}</div>
</div>	
</script>

<script type="text/text" data-type="panel_gate-a-l">
<div class="fencing-panel-spacing-number">
	<span>{{center_point}}</span>					
</div>

<div class="fc-empty-spacing"></div>

<div class="fencing-panel-item fencing-panel-gate fencing-btn-modal" data-key="gate" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}}{{panel_unit}}<br> GATE</div>
</div>	
</script>

<script type="text/text" data-type="panel_gate-a-r">
<div class="fencing-panel-item fencing-panel-gate fencing-btn-modal" data-key="gate" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}}{{panel_unit}}<br> GATE</div>
</div>	

<div class="fc-empty-spacing"></div>

<div class="fencing-panel-spacing-number">
	<span>{{center_point}}</span>					
</div>
</script>

<script type="text/text" data-type="panel_gate-b-l">
<div class="fencing-panel-spacing-number fpsn-b">
	<span>{{center_point}}</span>					
</div>	
<div class="panel-post fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></div>
<div class="fencing-panel-item fencing-panel-gate fencing-btn-modal ally" data-key="gate" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}}{{panel_unit}}<br> GATE</div>
</div>	
</script>

<script type="text/text" data-type="panel_gate-b-r">
<div class="fencing-panel-item fencing-panel-gate fencing-btn-modal ally" data-key="gate" data-target="#fc-control-modal">
	<div class="fencing-panel-item-size">{{panel_size}}{{panel_unit}}<br> GATE</div>
</div>	
<div class="panel-post fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></div>
<div class="fencing-panel-spacing-number">
	<span>{{center_point}}</span>					
</div>
</script>

<script type="text/text" data-type="panel_spacing-a">
<div class="fencing-panel-spacing-number">
    <span>{{center_point}}</span>                 
</div>
</script>

<script type="text/text" data-type="panel_spacing-b">
<div class="fencing-panel-spacing-number fpsn-b">
    <span>{{center_point}}</span>
</div>

<div class="panel-post fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></div>
</script>

<script type="text/text" data-type="offcut">
<div class="fencing-offcut offcut-{{group}}">
	<div>
		OFF-CUT
		<div>{{count}}x{{width}}W</div>
	</div>
</div>
</script>

<script type="text/text" data-type="left_raked-panel-a">
<div class="fencing-panel-spacing-number">
	<span>{{center_point}}</span>					
</div>

<div class="raked-panel-container">

	<div class="fencing-left-panel-o-a">
		<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item fencing-raked-panel fencing-left-panel-a fencing-btn-modal panel-item" data-key="add_step_up_panels" data-target="#fc-control-modal">
			<div class="fencing-panel-item-size">{{panel_size}} {{panel_unit}}</div>
		</div>
	</div>	

	<div class="fencing-panel-spigots">
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>						
	</div>

	<div class="fencing-panel-pinfixes">
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>						
	</div>
</div>
</script>

<script type="text/text" data-type="right_raked-panel-a">
<div class="raked-panel-container">

	<div class="fencing-right-panel-o-a">
		<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item fencing-raked-panel fencing-right-panel-a fencing-btn-modal panel-item" data-key="add_step_up_panels" data-target="#fc-control-modal">
			<div class="fencing-panel-item-size">{{panel_size}} {{panel_unit}}</div>
		</div>	
	</div>

	<div class="fencing-panel-spigots">
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>
		<span class="fencing-panel-spigot fencing-btn-modal" data-key="post_options" data-target="#fc-control-modal"></span>						
	</div>

	<div class="fencing-panel-pinfixes">
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>
		<span class="fencing-panel-pinfix"></span>						
	</div>
</div>

<div class="fencing-panel-spacing-number">
	<span>{{center_point}}</span>					
</div>
</script>

<script type="text/text" data-type="left_raked-panel-b">
<div class="fencing-panel-spacing-number fpsn-b {{post}}">
	<span>{{center_point}}</span>					
</div>

<div class="raked-panel-post panel-post panel-{{post}} fencing-btn-modal" data-key="left_side" data-target="#fc-control-modal"></div>

<div class="raked-panel-container">

	<div class="fencing-left-panel-o-b">
		<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item fencing-raked-panel fencing-left-panel-b fencing-btn-modal panel-item ally" data-key="add_step_up_panels" data-target="#fc-control-modal">
			<div class="fencing-panel-item-size">{{panel_size}} <br> {{panel_unit}}</div>
		</div>
	</div>

</div>
</script>

<script type="text/text" data-type="right_raked-panel-b">
<div class="raked-panel-container">

	<div class="fencing-right-panel-o-b">
		<div id="panel-item-{{panel_number}}" data-id="{{panel_number}}" class="fencing-panel-item fencing-raked-panel fencing-right-panel-b fencing-btn-modal panel-item ally" data-key="add_step_up_panels" data-target="#fc-control-modal">
			<div class="fencing-panel-item-size">{{panel_size}} <br> {{panel_unit}}</div>
		</div>	
	</div>

</div>

<div class="fencing-panel-spacing-number fpsn-b {{post}}">
	<span>{{center_point}}</span>					
</div>

<div class="panel-post raked-panel-post panel-{{post}} fencing-btn-modal" data-key="right_side" data-target="#fc-control-modal"></div>
</script>

<script type="text/text" data-type="color_option">
<div class="fc-col-3">
    <div class="fc-select-item fc-select" data-slug="{{slug}}" style="background:{{background_color}};color:{{text_color}};">    
        <p><b>{{title}}</b></p>
        {{sub_title}}
    </div>
</div>
</script>