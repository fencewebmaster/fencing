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

		<input name="{{field_name}}" class="fc-form-field" type="range" min="{{min}}" value="{{default}}" step="{{step}}" max="{{max}}">		

		<div class="fir-info-sub">
			<span>{{sub_default}}</span>{{sub_unit}}			
		</div>
	</div>

	<button type="button" class="fi-btn fir-plus">+</button>

</div>	