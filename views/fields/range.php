<div class="fencing-modal-area">

	<div class="fencing-modal-header">
		<div class="fencing-modal-title fc-font-2">{{marker}}{{field_title}}</div>
	</div>

	<div class="fencing-form-group fencing-input-range">	

		<button type="button" class="fi-btn fir-minus">-</button>

		<div class="fir-input-group">
			<div class="fir-info">
				<span>{{default}}</span>{{unit}}			
			</div>
	
			<input name="{{field_name}}" class="fc-form-field" type="range" min="{{min}}" value="{{default}}" step="{{step}}" max="{{max}}">
		</div>

		<button type="button" class="fi-btn fir-plus">+</button>

	</div>	

</div>