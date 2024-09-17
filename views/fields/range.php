<div class="fencing-modal-header">
	<div class="fencing-modal-title fc-font-2">{{field_title}}</div>
	<button type="button" class="fencing-modal-close js-fencing-modal-close">&nbsp;</button>
</div>

<div class="fencing-form-group fencing-input-range">	

	<button type="button" class="fi-btn fir-minus">-</button>

	<div class="fir-input-group">
		<div class="fir-info">
			<span>{{default}}</span>{{unit}}			
		</div>

		<input name="{{field_name}}" class="fc-form-field" type="range" min="0" value="1000" step="25" max="1000">		
	</div>

	<button type="button" class="fi-btn fir-plus">+</button>

</div>	