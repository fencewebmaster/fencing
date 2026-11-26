<div class="mb-2 fc-input-container fc-step2-max-height-input">
    <div class="fw-bold small mb-1">{{title}}</div>
    <div class="fencing-measurement-box fencing-measurement-box--height-only">
        <div class="fencing-mb-input">
            <div class="d-flex align-items-center">
                <div class="fencing-qty-minus fencing-qty-btn px-3">
                    <i class="fa fa-minus"></i>
                </div>
                <input
                    type="number"
                    name="max_fence_height"
                    class="fc-max-fence-height-input numeric text-center py-1"
                    value=""
                    data-min="199"
                    data-max="5800"
                    autocomplete="off"
                >
                <span>mm</span>
                <div class="fencing-qty-plus fencing-qty-btn px-3 ms-2">
                    <i class="fa fa-plus"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="fc-input-msg error-msg"></div>
</div>
