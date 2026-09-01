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
        </div>
    </div>
</div>
<!-- [END] FORM SUBMISSION LOADER -->

<!-- Download progress toast. Downloads used to raise the full-screen submission loader, which
     blacked out the page for something the user can happily keep scrolling past. -->
<div class="fc-download-toast" role="status" aria-live="polite">
    <span class="fc-loader-gif fc-download-toast__spinner" aria-hidden="true"></span>
    <div class="fc-download-toast__text">
        <div class="fc-download-toast__title">Preparing your download</div>
        <p class="fc-download-toast__message js-fc-download-toast-message">Building the full project plan PDF.</p>
    </div>
</div>

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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down fc-planner-summary-modal__dialog">
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