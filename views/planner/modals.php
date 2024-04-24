<!-- [START] FORM SUBMISSION LOADER -->
<div class="fc-loader-overlay" style="display: none;">
    <div class="fc-loader-container">
        <div class="fc-loader">
            <div class="row align-items-center">
                
                <div class="col-auto">
                    <div class="fc-loader-gif"></div>
                </div>

                <div class="col-auto">
                    
                    <ul class="mb-0 p-0">
                        <li class="fc-text-success li-create">
                            <div><small>Creating your plan...</small></div>
                        </li>
                    </ul>

                </div>

            </div>
        </div>
    </div>
</div>
<!-- [END] FORM SUBMISSION LOADER -->


<!-- Load Quote Modal -->
<form method="get">
    <div class="modal modal-sm fade" id="load-quote" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header fw-bold">
                    <h5 class="modal-title text-uppercase fw-bold" id="exampleModalLabel">Load <span class="text-danger">Quote</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-groupx mb-3">
                        <div class="form-group has-clear position-relative">
                            <input type="text" name="qid" class="form-control border border-secondary form-control-lg no-space text-center mb-2" placeholder="Enter Quote ID" required>                        
                        </div>

                        <button type="submit" class="btn btn-lg w-100 btn-orange text-uppercase px-4">
                            <i class="fa fa-check me-2"></i>
                            <strong>Confirm</strong>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>


<div class="modal fade" id="popup-alert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header fw-bold">
                <h5 class="modal-title text-uppercase fw-bold" id="exampleModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="modal-message mb-2"></div>

                <div class="fencing-measurement-box">

                    <div class="fencing-mb-input">

                        <div class="d-flex align-items-center">

                            <div class="fencing-qty-minus fencing-qty-btn px-3">
                                <i class="fa fa-minus"></i>
                            </div>
                            
                            <input type="text" class="measurement-box-number numeric text-center py-1 valid" data-min="300" data-max="10000" maxlength="5" value="" data-last="" aria-invalid="false"> 
                            
                            <span>mm</span>   

                            <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
                                <i class="fa fa-plus"></i>
                            </div>

                        </div>

                    </div>

                    <button type="button" class="btn btn-dark text-uppercase btn-fc-calculate px-3 fw-bold">
                        <small>Update</small>
                    </button>

                </div>

                <div class="d-none">
                    <div class="or-divider">
                        <strong>OR</strong>  
                        <div></div>                    
                    </div>  

                    <div class="row">
                        <div class="col pe-1">
                            <button class="btn btn-danger w-100 text-uppercase" data-remove="gate">Remove Gate</button>                        
                        </div>
                        <div class="col ps-1">
                            <button class="btn btn-danger w-100 text-uppercase" data-remove="step_up">Remove Step-Up Panels</button>                
                        </div>
                    </div>                    
                </div>

            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 11">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-bs-config='{"delay":7000}'>
        <div class="toast-header text-bg-dark">
            <div class="me-auto toast-title"></div>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>