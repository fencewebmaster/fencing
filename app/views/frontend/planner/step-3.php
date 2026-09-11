    <!-- [START] DISPLAY RESULT -->   
    <div class="fencing-section fencing-section--no-padding fencing-section--has-border fc-position-relative js-fc-form-step fc-d-none" data-section="3" style="display: none;">

        <div class="fencing-section__top">

            <div class="fencing-calculating">
                <div class="fc-calculating-loader">
                    <div class="fc-loader-gif"></div>
                    <h4 class="fc-text-uppercase">Calculating ...   </h4>
                </div>
            </div>

            <div class="fencing-section__cmp fencing-section--step3">

                <!-- Step number and title are one block, with the zoom cluster beside the pair
                     rather than beside the title alone — the way Step 01 seats its Delete/Reset.
                     Sharing a row with the controls centred the 19px title inside their 37px box,
                     which pushed it 9px off "Step 03" and 9px off the rule below. On phones the
                     pair leaves no room beside it, so .fc-step3-head seats a smaller bar opposite
                     "Step 03" instead, the way Step 02 seats its ? button (see style.css). -->
                <div class="fc-step3-head d-flex justify-content-between align-items-center flex-wrap gap-2 fc-mb-2">
                    <div class="fc-step3-heading">
                        <div class="step-label" data-action="scroll" data-target="[data-section=3]" data-offset="54">Step <span>03</span></div>
                        <h4 class="fencing-content-title mb-0">Configure this fence section</h4>
                    </div>

                    <!-- The design-1 mockup's zoom bar (tests/mockup/design-1.html .toolbar): minus,
                         readout, plus, Reset. The controls keep the hooks events.js and HELPER.zooming
                         drive — .fc-zoom-fence[data-zoom], the one .js-fc-zoom-progress readout, and
                         .js-fc-zoom-reset, which HELPER keeps disabled at 100%. -->
                    <div class="fc-zoom-bar" role="group" aria-label="Zoom the fence editor">
                        <button type="button" class="fc-zoom-bar__btn fc-zoom-fence" data-zoom="out" aria-label="Zoom out" title="Zoom out (&minus;)">
                            <svg class="fc-zoom-bar__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14"/></svg>
                        </button>
                        <span class="fc-zoom-bar__value js-fc-zoom-progress" aria-live="polite">100%</span>
                        <button type="button" class="fc-zoom-bar__btn fc-zoom-fence" data-zoom="in" aria-label="Zoom in" title="Zoom in (+)">
                            <svg class="fc-zoom-bar__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <button type="button" class="fc-zoom-bar__btn js-fc-zoom-reset" title="Back to 100% (0)" disabled>Reset</button>
                    </div>
                </div>

            </div>

            <div class="fencing-section__cmp fencing-display-result fc-position-relative">

                <div class="js-fc-planner-step3-skeleton fc-planner-step3-skeleton fc-d-none" aria-hidden="true">
                    <div class="fc-planner-step3-skeleton__run">
                        <div class="fc-planner-step3-skeleton__post"></div>
                        <div class="fc-planner-step3-skeleton__panel"><div class="fc-planner-step3-skeleton__panel-fill"></div></div>
                        <div class="fc-planner-step3-skeleton__post"></div>
                        <div class="fc-planner-step3-skeleton__panel"><div class="fc-planner-step3-skeleton__panel-fill"></div></div>
                        <div class="fc-planner-step3-skeleton__post"></div>
                    </div>
                    <div class="fc-planner-step3-skeleton__meta">
                        <span class="fc-planner-step3-skeleton__line"></span>
                        <span class="fc-planner-step3-skeleton__line fc-planner-step3-skeleton__line--narrow"></span>
                    </div>
                </div>
                
                <div class="fencing-result-msg" style="display: none;">
                    <p>No Valid Solution. Please adjust Measurements.</p>
                </div>

                <div class="fc-project-plan-hscroll">
                    <div class="fc-result">
                        <!-- Width dimension over each panel and gate — |—— 1,934W ——| — as the design-1
                             mockup draws it, above the run the way the Overall line sits under it.
                             shared/panel-dimensions.js places one segment per drawn part from its
                             rendered box and reads the figure from the part's own label. Ships hidden
                             until a part is drawn; aria-hidden — the parts' labels already carry it. -->
                        <div class="fc-pdim js-fc-pdim fc-pdim--off" aria-hidden="true"></div>

                        <div class="fencing-panel-items">
                            <div class="fencing-panel-rail fencing-btn-modal" 
                                data-key="rail_options" 
                                data-target="#fc-control-modal" style="display:none;"></div>

                            <div id="pp-0" class="fencing-panel-container"></div>
                        </div>

                        <!-- Dimension line under the drawing — <—— 6,000 OVERALL ——> — as the design-1
                             mockup draws it. Inside the scroll strip so it scrolls and zooms with the
                             run; shared/overall-dimension.js sizes it to the drawn parts and mirrors the
                             .fc-overall text into it. Ships hidden until there is a figure to show. The
                             figure sits on a zero-width anchor at the line's centre; on a run wider than
                             the screen the script slides it to the middle of the visible stretch. -->
                        <div class="fc-dim js-fc-dim fc-dim--off" aria-hidden="true"><span class="fc-dim__arm fc-dim__arm--start"></span><span class="fc-dim__anchor"><span class="fc-dim__label"><span class="fc-dim__num"></span> <span class="fc-dim__word"></span></span></span><span class="fc-dim__arm fc-dim__arm--end"></span></div>
                    </div>
                </div>

            </div>
            <div class="err-message fencing-panel-solution-msg text-center text-danger px-2 py-2" role="alert"></div>
            <!-- Glass pool panel-to-panel clamp status (size × qty, or why none are added); written by
                 GlassPool.applyClampMessage, hidden while empty. -->
            <div class="fc-clamp-message fencing-panel-clamp-msg text-center px-2 py-1" role="status" aria-live="polite"></div>
            <div class="fc-overall pt-1 pb-2"><span class="d-oaw"></span> <span class="js-overall-label">Overall</span></div>

            <!-- [START] PANEL CONTROLS -->   
            <span class="fencing-section__cmp fencing-panel-controls"></span>

            <!-- Detached scrollbar for the drawing strip above. The strip's own bar is hidden
                 (planner Step 3 only) and shared/hscroll-proxy.js mirrors it here, so the
                 scrollbar sits under the control buttons instead of splitting the drawing from
                 the Overall line. Ships hidden: the module un-hides it only while the drawing
                 actually overflows. aria-hidden — it duplicates scrolling the strip itself
                 still offers by drag and keyboard. -->
            <div class="fc-hscroll-proxy js-fc-hscroll-proxy fc-hscroll-proxy--off" aria-hidden="true">
                <div class="fc-hscroll-proxy__inner"></div>
            </div>


            <!-- [END] PANEL CONTROLS -->
        </div>
        
        <div class="fencing-section__bottom py-3 fc-step3-bottom">
            <div class="">

                <div class="row flex-nowrap flex-md-wrap flex-xl-nowrap align-items-stretch align-items-xl-center g-2" data-tab="1">

                    <div class="d-none d-md-block col-auto col-md-4 col-lg-4 col-xl-auto order-md-1 order-xl-1">
                        <button type="button" 
                            aria-label="Add section"
                            class="btn btn-dark fc-tab-add fencing-tab-add fc-step3-icon-btn p-3 w-100 w-xl-auto">
                            <span class="d-md-none"><i class="fa-solid fa-plus" aria-hidden="true"></i></span>
                            <span class="d-none d-md-inline"><i class="fa-solid fa-plus me-1"></i> Add Another Section</span>
                        </button>
                    </div>

                    <div class="d-none d-md-block col-auto col-md-4 col-lg-4 col-xl-auto order-md-2 order-xl-2">
                        <button type="button" 
                            aria-label="Reset section"
                            class="btn btn-danger fc-fence-reset-all fc-fence-reset fc-step3-icon-btn text-uppercase p-3 w-100 w-xl-auto">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                            <span class="d-none d-md-inline ms-1">Reset</span>
                        </button>
                    </div>

                    <div class="col col-md-4 col-lg-4 col-xl-auto order-md-3 order-xl-4 ms-xl-auto">
                        <button type="button" 
                            class="btn btn-orange fc-btn-next-step fc-btn-step p-3 text-uppercase w-100 w-xl-auto" 
                            data-tab="1" 
                            data-move="2"
                            data-section="4"
                            data-offset="0">
                            <span class="d-xl-none"><b>NEXT</b> <i class="fa-solid fa-angle-right mx-1" aria-hidden="true"></i> PLAN OPTIONS</span>
                            <span class="d-none d-xl-inline"><b>NEXT</b> <i class="fa-solid fa-angle-right mx-2"></i> Select PLAN OPTIONS</span>
                        </button>
                    </div>

                    <div class="d-none d-md-block col-auto col-md-12 col-lg-12 col-xl-auto order-md-4 order-xl-3">
                        <button type="button" 
                            aria-label="Delete section"
                            class="btn btn-danger btn-fc-sm btn-delete-fence js-btn-delete-fence fc-step3-icon-btn text-uppercase p-3 w-100 w-xl-auto" 
                            >
                            <span><i class="fa fa-trash-can" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Delete <span>Section</span></span></span>
                        </button>
                    </div>

                </div>

                <div class="fc-section-step fencing-calculate-price fc-d-none" data-tab="2" style="display: none;">
                    <div class="fc-step3-options-actions d-flex flex-column flex-sm-row flex-wrap align-items-stretch gap-2">
                        <button type="button" 
                            class="btn btn-orange fc-btn-create-plan fencing-btn-modal order-2 order-sm-1 w-100 w-sm-auto" 
                            data-target="#submit-modal">
                            <strong>Create Project Plan</strong><br>
                            <small>View Costing, Plan & Materials List</small>
                        </button>

                        <button type="button" 
                            class="btn btn-step btn-outline-secondary text-uppercase fc-px-3 order-1 order-sm-2 w-100 w-sm-auto" 
                            data-tab="2" 
                            data-move="1"><i class="fa-solid fa-angle-left me-2"></i> Back
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- [END] DISPLAY RESULT -->
