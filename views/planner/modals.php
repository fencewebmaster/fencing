<!-- START FORM SUBMISSION LOADER -->
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
<!-- END FORM SUBMISSION LOADER -->


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