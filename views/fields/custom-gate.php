<div class="row fc-form-field px-2">
    <div class="col-sm-6 col-12 mb-2 px-1">
        <div class="fc-select-2 fc-select-left select-gate_only">    
			<input type="checkbox" name="gate_only" style="width: 0;position: absolute;">
            <p>Gate <strong>ONLY</strong></p>
        </div>
    </div>
</div>

<div class="fc-form-field">
	<input type="checkbox" name="use_std" class="fc-form-field" style="width: 0;position: absolute;">
	<div class="row px-2">
	    <div class="col-sm-6 col-12 mb-2 px-1">
	        <div class="fc-select-2 fc-select-left select-use_std" data-val="std">    
	            <p><strong>STD</strong> Gate Width</p>
	        </div>
	    </div>
	    <div class="col-sm-6 col-12 mb-2 px-1">
	        <div class="fc-select-2 fc-select-left select-use_std" data-val="custom">    
	            <p><strong>CUSTOM</strong> Gate (300-{{maxWidth}}mm)</p>
	        </div>
	    </div>
	</div>
	
</div>

<div class="fc-input-container">

	<div class="fencing-measurement-box mb-3 fc-input-group">

	    <div class="fencing-mb-input">

	        <div class="d-flex align-items-center">

	            <div class="fencing-qty-minus fencing-qty-btn px-3">
	                <i class="fa fa-minus"></i>
	            </div>
	            
	   	        <input name="width" type="number" class="numeric fc-form-field text-center py-1" input-type="number" data-min="<?php echo config()->overall->min; ?>" maxlength="<?php echo config()->overall->length; ?>" data-max="{{maxWidth}}"> 
	        
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