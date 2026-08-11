<!-- [START] FORM SUBMISSION LOADER -->
<div class="fc-loader-overlay" style="display: none;">
    <div class="fc-loader-container">
        <div class="fc-loader">
            <div class="fc-loader-panel fc-loader-panel--default">
                <div class="fc-row">
                    <div class="fc-col-auto">
                        <div class="fc-loader-gif"></div>
                    </div>
                    <div class="fc-col-auto">
                        <ul></ul>
                    </div>
                </div>
            </div>
            <div class="fc-loader-panel fc-loader-panel--download-plans" hidden>
                <div class="fc-loader-gif" aria-hidden="true"></div>
                <p class="fc-loader-download-message">Please wait while we prepare your plans for download.</p>
            </div>
        </div>
    </div>
</div>
<!-- [END] FORM SUBMISSION LOADER -->

<!-- [START] Cart item image gallery (project plan) -->
<div id="fc-cart-image-modal" class="fc-cart-image-modal" aria-hidden="true">
	<div class="fc-cart-image-modal__backdrop" data-fc-cart-gallery-close></div>
	<div class="fc-cart-image-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="fc-cart-image-modal-title">
		<button type="button" class="fc-cart-image-modal__close" data-fc-cart-gallery-close aria-label="Close">
			<i class="fa-solid fa-xmark"></i>
		</button>
		<button type="button" class="fc-cart-image-modal__nav fc-cart-image-modal__prev" aria-label="Previous image">
			<i class="fa-solid fa-chevron-left"></i>
		</button>
		<div class="fc-cart-image-modal__body">
			<img class="fc-cart-image-modal__img" src="" alt="">
			<div id="fc-cart-image-modal-title" class="fc-cart-image-modal__caption"></div>
		</div>
		<button type="button" class="fc-cart-image-modal__nav fc-cart-image-modal__next" aria-label="Next image">
			<i class="fa-solid fa-chevron-right"></i>
		</button>
		<div class="fc-cart-image-modal__counter" aria-live="polite"></div>
	</div>
</div>
<!-- [END] Cart item image gallery -->

<!-- Project plan — fence configuration summary -->
<div class="modal fade" id="fc-planner-summary-modal" tabindex="-1" aria-labelledby="fcPlannerSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg fc-planner-summary-modal__dialog">
        <div class="modal-content fc-planner-summary-modal__content">
            <div class="modal-header fc-planner-summary-modal__header">
                <div class="fc-planner-summary-modal__header-text">
                    <h5 class="modal-title fc-planner-summary-modal__title" id="fcPlannerSummaryModalLabel">Fence Summary</h5>
                    <p class="fc-planner-summary-modal__subtitle mb-0">Configuration overview for your project</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body js-fc-planner-summary-body fc-planner-summary-modal__body"></div>
        </div>
    </div>
</div>