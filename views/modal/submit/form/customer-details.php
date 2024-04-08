<div class="form-group fc-mb-2">
    <div class="row">
        <div class="col">
            <div class="fc-label-group has-clear position-relative">
                <label class="fc-form-label">Name <span class="fc-text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control p-3 p-3" placeholder="Your Name" required>                  
            </div>
        </div>
        <div class="col">
            <div class="fc-label-group has-clear position-relative">
                <label class="fc-form-label">Mobile <span class="fc-text-danger">*</span></label>
                <input type="text" name="mobile" id="mobile" class="form-control p-3" placeholder="Your phone number" required>                          
            </div>
        </div>
    </div>
</div>

<div class="form-group fc-mb-2">
    <div class="fc-label-group has-clear position-relative">
        <label class="fc-form-label">Email <span class="fc-text-danger">*</span></label>
        <input type="text" name="email" id="email" class="form-control p-3 no-space" placeholder="Your Email" required>  
    </div>
</div>

<div class="form-group fc-mb-1">
    <div class="fc-label-group has-clear position-relative">
        <label class="fc-form-label">Address <span class="fc-text-danger">*</span></label>
        <input type="text" name="address" id="address" class="form-control p-3" placeholder="Your Address" required>  
    </div>
</div>

<div class="form-group fc-mb-1">
    <div class="fc-row-container">
    
        <div class="fc-col-half">
            <div class="fc-label-group has-clear position-relative">
                <label class="fc-form-label">Post Code <span class="fc-text-danger">*</span></label>
                <input type="text" name="postcode" id="postcode" class="form-control p-3" placeholder="Post Code" required>                  
            </div>
        </div>

        <div class="fc-col-half">
            <div class="fc-label-group has-clear position-relative">
                <label class="fc-form-label">State <span class="fc-text-danger">*</span></label>
                <select name="state" class="form-control p-3" required>
                    <option value="">Select an option…</option>
                    <?php foreach( fc_state() as $state_k => $state_v ): ?>
                    <option value="<?php echo $state_k; ?>"><?php echo $state_v; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

    </div>
</div>