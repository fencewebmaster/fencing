<div class="mb-2">
	<input type="checkbox" name="use_std" id="use_std" class="fc-form-field" value="yes">
	<label for="use_std">Use STD Gate Width</label>				
</div>

<div class="fc-input-container">

	<div class="fencing-measurement-box mb-3 fc-input-group">

	    <div class="fencing-mb-input">

	        <div class="d-flex align-items-center">

	            <div class="fencing-qty-minus fencing-qty-btn px-3">
	                <i class="fa fa-minus"></i>
	            </div>
	            
	   	        <input name="width" type="text" class="numeric fc-form-field text-center py-1" input-type="number" data-min="300" maxlength="{{maxLength}}" data-max="{{maxWidth}}"> 
	        
	            <span class="me-2">mm</span>   

	            <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
	                <i class="fa fa-plus"></i>
	            </div>

				<div class="fc-input-msg error-msg"></div>

	        </div>

	    </div>

	    <button type="button" class="btn btn-dark px-4 text-uppercase py-2 fw-bold"><small>Calculate</small></button>

	</div>
		
</div>

<div class="alert alert-gray">

    <div class="text-uppercase fw-bold text-dark mb-2">
        <i class="fa-solid fa-circle-exclamation me-1"></i> Custom Gates
    </div>

    <p class="text-secondary mb-2 small">
    	Custom Gates require:<br>
    	1x Gate Converter Kit<br>
    	1x STD Barr Panel
    </p>
    <p class="text-secondary small">
    	Simply cut the panel to the width you need, then screw the Gate Converter onto each end.                              
	</p>

</div>
