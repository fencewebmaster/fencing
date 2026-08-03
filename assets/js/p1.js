let Planner = {
    init: function() {
        this.reload_fence_items();
    },

    //----------------------------------------------------------------------------------

    reload_fence_items: function() {
        if ($('.fc-planner-page').length && typeof fcBeginPlannerStep3SkeletonLoadPass === 'function') {
            fcBeginPlannerStep3SkeletonLoadPass();
        }

        var section = HELPER.getSearchParams('section'),
            tab = HELPER.getSearchParams('tab'),
            form = HELPER.getSearchParams('form'),
            fence = HELPER.getSearchParams('fence'),
            qid = HELPER.getSearchParams('qid'),
            action = HELPER.getSearchParams('action');

        // Remove bogus form=undefined / form=null from the address bar (e.g. leave ?tab=2 only).
        try {
            var spClean = new URLSearchParams(location.search);
            var rawFormParam = spClean.get('form');
            if (rawFormParam === 'undefined' || rawFormParam === 'null') {
                spClean.delete('form');
                var qsClean = spClean.toString();
                history.replaceState({}, '', location.pathname + (qsClean ? '?' + qsClean : '') + location.hash);
                form = HELPER.getSearchParams('form');
            }
        } catch (e) {}

        var qidLoadFailed =
            typeof fc_load_quote_failed !== 'undefined' && fc_load_quote_failed === true;

        if (action || fence || (qid && !qidLoadFailed)) {
            clearFencingData();
        }

        if (action == 'clear-all') {
            location.href = location.origin + location.pathname;
            return;
        }

        // Merge session/server fence data into localStorage before reading section count so extra
        // sections (added locally, not yet submitted) are preserved — must run before tab loop.
        this.reloadFencingData();

        if (typeof fcSyncPlannerClientStateFromServer === 'function') {
            fcSyncPlannerClientStateFromServer();
        }

        var itemsRaw = localStorage.getItem('custom_fence-section');
        var items = itemsRaw === null || itemsRaw === undefined || itemsRaw === ''
            ? 1
            : parseInt(itemsRaw, 10);
        if (!Number.isFinite(items) || items < 1) items = 1;

        for (let i = 1; i <= items; i++) {

            var index = i - 1;
            var sectionTab = `<div class="fencing-tab fencing-tab-selected fc-section-${i}">
                    <div class="fencing-tab-name">
                        ${typeof fcPlannerSectionTabStatusHtml === 'function' ? fcPlannerSectionTabStatusHtml() : ''}
                        <span class="ftm-title">SECTION</span> <span class="fencing-tab-number">${i}</span>
                        <div class="ftm-fence-style" hidden></div>
                        <div class="ftm-measurement"></div>
                    </div>
                </div>`;

            $('.fencing-tab-container-area').append(sectionTab);
            $('.fencing-tab').removeClass('fencing-tab-selected');
            $('.fencing-tab:last-child').addClass('fencing-tab-selected');

            var custom_fence_tabs = localStorage.getItem('custom_fence-' + index);
            const data_tabs = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];
            mesurement = data_tabs[0]?.calculateValue ? parseInt(data_tabs[0]?.calculateValue).toLocaleString() + ' ' + FENCES.defaultValues.unit : '';

            $('.fencing-tab-selected').find('.ftm-measurement').html(mesurement);
            if (typeof fcSyncPlannerTabFenceStyle === 'function') {
                fcSyncPlannerTabFenceStyle($('.fencing-tab-selected'), index);
            }
            $('.js-fc-form-step').hide();

        }

        HELPER.hideDeleteSectionBtn();

        if (typeof fcSyncAllPlannerSectionTabStatuses === 'function') {
            fcSyncAllPlannerSectionTabStatuses();
        }

        if (tab) {
            $('.fc-section-step').hide();
            $('[data-tab="' + tab + '"]').show();
        }

        setTimeout(function() {
            if (section) {
                $('.fencing-tab-selected').removeClass('fencing-tab-selected');
                $('.fc-section-' + section).addClass('fencing-tab-selected');
            }
            var $selectedTab = $('.fencing-tab.fencing-tab-selected:visible');
            if (!$selectedTab.length) {
                $selectedTab = $('.fencing-tab.fencing-tab-selected').first();
            }
            if ($selectedTab.length) {
                $selectedTab.trigger('click');
            }
            if (typeof window.fcRefreshFencingStylesSlick === 'function') {
                window.fcRefreshFencingStylesSlick();
            }
            if (typeof window.fcRefreshColorOptionsSlick === 'function') {
                window.fcRefreshColorOptionsSlick();
            }
            if (typeof fcEndPlannerStep3SkeletonLoadPass === 'function') {
                setTimeout(fcEndPlannerStep3SkeletonLoadPass, 600);
            }

            if (qid && !qidLoadFailed && typeof fcRunQuoteReloadSubmit === 'function') {
                fcRunQuoteReloadSubmit();
            }
        }, 100);

        // URL may contain form=undefined (string); that must not open the modal — only real tab ids (e.g. 1–4).
        var formTabOk =
            form &&
            form !== 'undefined' &&
            form !== 'null' &&
            $('#submit-modal [data-formtab="' + form + '"]').length;

        if (tab == 2 && formTabOk) {
            $('#submit-modal').show();
            $('#submit-modal .fc-form-plan').hide();
            $('#submit-modal .fc-download-footer-actions').hide();
            $('#submit-modal [data-formtab="' + form + '"]').show();
        }


        // Store default section count
        if (localStorage.getItem('custom_fence-section') == null && $('.fencing-tab').length == 1) {
            localStorage.setItem('custom_fence-section', 1);
        }

        // Initiate tab container scroll
        HELPER.tabContainerScroll();

        // Restore form data when the page loads
        restoreFormData();

        if (tab == 2 && typeof loadColorOptions === 'function') {
            loadColorOptions();
        }

        if (typeof applyOffcutDisplayPreference === 'function') {
            applyOffcutDisplayPreference();
        }

        // After tab init + programmatic fence-style clicks (see events.js fencingTab), capture baseline for UPDATE button.
        setTimeout(function() {
            if (typeof fcCapturePlannerUpdateFenceBaseline === 'function') {
                fcCapturePlannerUpdateFenceBaseline();
            }
            if (typeof fcSyncPlannerUpdateButtonVisibility === 'function') {
                fcSyncPlannerUpdateButtonVisibility();
            }
            if (typeof fcSyncStep2GateOnlyVisibility === 'function') {
                fcSyncStep2GateOnlyVisibility();
            }
        }, 320);

        if (qidLoadFailed && typeof fcInitLoadQuoteModalFromServer === 'function') {
            fcInitLoadQuoteModalFromServer();
        }

        if ($('.fc-form-check-img input:checked').length) {
            $('.form-tab-4').closest('form').find('[type="submit"]')
                .removeClass('disabled')
                .removeAttr('disabled');
        }

        if (fence) {
            $('.fencing-style-item[data-slug="' + fence + '"]').trigger('click');
        }

        // Flush Step 2 edits before full page reload so localStorage matches the UI.
        $(window).on('pagehide beforeunload', function() {
            try {
                if (
                    typeof fcIsStep2DomDirty === 'function' &&
                    fcIsStep2DomDirty()
                ) {
                    return;
                }
                if (typeof fcPersistStep2Immediate === 'function') {
                    fcPersistStep2Immediate();
                }
            } catch (eUnload) {}
        });
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                try {
                    if (
                        typeof fcIsStep2DomDirty === 'function' &&
                        fcIsStep2DomDirty()
                    ) {
                        return;
                    }
                    if (typeof fcPersistStep2Immediate === 'function') {
                        fcPersistStep2Immediate();
                    }
                } catch (eVis) {}
            }
        });
    },

    //----------------------------------------------------------------------------------

    planCart: function(fromQuoteReload) {
        FCModal.close('#submit-modal');
        $('.fc-loader-overlay').show();
        var isQuoteReload = fromQuoteReload === true || !!HELPER.getSearchParams('qid');
        if (isQuoteReload) {
            $('.li-create small').html('Reloading your plan...');
        } else if (typeof fcSetPlannerSubmitLoaderMessage === 'function') {
            fcSetPlannerSubmitLoaderMessage();
        } else {
            $('.li-create small').html('Updating your plan...');
        }
        submit_fence_planner('update', { skipCartRebuild: isQuoteReload, isQuoteReload: isQuoteReload });
    },

    //----------------------------------------------------------------------------------

    reloadFencingData: function() {
        var qidLoadFailed =
            typeof fc_load_quote_failed !== 'undefined' && fc_load_quote_failed === true;

        if (HELPER.getSearchParams('qid') && !fc_fence_info.fence_data) {
            if (qidLoadFailed) {
                return;
            }
            clearFencingData();
            location.href = location.origin + location.pathname;
        }

        if (fc_fence_info?.length == 0 || !fc_fence_info?.fence_data) {
            return;
        }

        /** Opening a saved quote by qid — session fence rows are authoritative; do not overlay local Calculate state. */
        var qidFromUrl = HELPER.getSearchParams('qid');

        /** Per-section style blobs (`custom_fence-{n}-{slug}`) before session merge — post/side/gate settings live here. */
        var segmentSnapBeforeMerge = {};
        if (!qidFromUrl && typeof fcPlannerListCustomFenceSegmentBlobKeys === 'function') {
            fcPlannerListCustomFenceSegmentBlobKeys().forEach(function(k) {
                segmentSnapBeforeMerge[k] = localStorage.getItem(k);
            });
        }

        var localSectionCountBefore = parseInt(localStorage.getItem('custom_fence-section'), 10);
        if (!Number.isFinite(localSectionCountBefore) || localSectionCountBefore < 1) {
            localSectionCountBefore = 1;
        }

        /**
         * Session `fence_data` can lag behind localStorage after the user runs Calculate but before submit.
         * Without this, merge below overwrites `custom_fence-*` and reload looks like a reset.
         */
        var localCalculatedByTab = {};
        if (!qidFromUrl) {
            try {
                for (var li = 0; li < 64; li++) {
                    var lr = localStorage.getItem('custom_fence-' + li);
                    if (!lr) {
                        continue;
                    }
                    var lp = JSON.parse(lr);
                    if (!lp || !lp[0]) {
                        continue;
                    }
                    var keepLocal =
                        (lp[0].calculateValue != null && lp[0].calculateValue !== '') ||
                        (typeof fcHasMeaningfulStep2TabRow === 'function' && fcHasMeaningfulStep2TabRow(lp[0]));
                    if (keepLocal) {
                        localCalculatedByTab[li] = lr;
                    }
                }
            } catch (e) {}
        }

        var custom_fence_items = [];
        try {
            if (typeof fcParsePlannerFenceDataJson === 'function') {
                custom_fence_items = fcParsePlannerFenceDataJson(fc_fence_info.fence_data);
            } else {
                custom_fence_items = JSON.parse(fc_fence_info.fence_data) || [];
            }
        } catch (error) {
            custom_fence_items = [];
        }

        if (typeof fcNormalizePlannerFencePayload === 'function') {
            custom_fence_items = fcNormalizePlannerFencePayload(custom_fence_items);
        }

        var serverSectionCount = fc_fence_info.section_count || custom_fence_items.length || 1;
        var serverItemsLen = Array.isArray(custom_fence_items) ? custom_fence_items.length : 0;
        if (!serverItemsLen) {
            serverItemsLen = serverSectionCount;
        }

        /**
         * After section delete (or any local reindex), session fence_data / section_count can still
         * describe the old shape. Merging would restore removed rows and clobber cart lines.
         */
        var structureDrift =
            localSectionCountBefore !== serverSectionCount ||
            localSectionCountBefore !== serverItemsLen;

        var fullLocalSnap = null;
        if (
            !qidFromUrl &&
            structureDrift &&
            typeof fcCapturePlannerFenceCartStorageSnapshot === 'function'
        ) {
            fullLocalSnap = fcCapturePlannerFenceCartStorageSnapshot();
        }

        $(custom_fence_items).each(function(k, v) {

            v.form[0].style = v.form[0].style;
            v.form[0].tab = v.form[0].tab - 1;

            if (!qidFromUrl && typeof fcMergePlannerTabFormPreferLocalStep2 === 'function') {
                try {
                    var tabIdxMerge = v.form[0].tab;
                    var localTabRaw = localStorage.getItem('custom_fence-' + tabIdxMerge);
                    if (localTabRaw) {
                        var localTabForm = JSON.parse(localTabRaw);
                        v.form = fcMergePlannerTabFormPreferLocalStep2(localTabForm, v.form);
                    }
                } catch (eMerge) {}
            }

            localStorage.setItem('custom_fence-' + v.form[0].tab, JSON.stringify(v.form));

            if (v.settings) {
                localStorage.setItem('custom_fence-' + v.form[0].tab + '-' + v.form[0].style, JSON.stringify(v.settings));
            }
        });

        var didFullLocalRestore = false;
        if (
            !qidFromUrl &&
            structureDrift &&
            fullLocalSnap &&
            Object.keys(fullLocalSnap.kv).length &&
            typeof fcApplyPlannerFenceCartStorageSnapshot === 'function'
        ) {
            fcApplyPlannerFenceCartStorageSnapshot(fullLocalSnap);
            didFullLocalRestore = true;
        }

        if (
            !qidFromUrl &&
            !didFullLocalRestore &&
            typeof fcRestorePlannerSegmentBlobsAfterSessionMerge === 'function'
        ) {
            fcRestorePlannerSegmentBlobsAfterSessionMerge(segmentSnapBeforeMerge, qidFromUrl, didFullLocalRestore);
        }

        if (!qidFromUrl && !didFullLocalRestore && Object.keys(localCalculatedByTab).length) {
            Object.keys(localCalculatedByTab).forEach(function(si) {
                localStorage.setItem('custom_fence-' + si, localCalculatedByTab[si]);
            });
        }

        var cart_items = [];
        try {
            cart_items = JSON.parse(fc_fence_info.cart_items_data || '[]') || [];
        } catch (error) {
            cart_items = [];
        }

        var syncCartFromSession = fcPlannerHasQuoteId() || !didFullLocalRestore;
        if (
            syncCartFromSession &&
            cart_items.length &&
            typeof fcHydratePlannerCartItemsLocalStorage === 'function'
        ) {
            fcHydratePlannerCartItemsLocalStorage(
                cart_items,
                fcPlannerHasQuoteId() ? { clearFirst: true } : undefined
            );
        }

        if (!didFullLocalRestore) {
            var mergedSectionCount = Math.max(serverSectionCount, localSectionCountBefore);
            localStorage.setItem('custom_fence-section', String(mergedSectionCount));
        }

        if (fc_fence_info.project_plans_data && (fcPlannerHasQuoteId() || !didFullLocalRestore)) {
            var ppRaw = fc_fence_info.project_plans_data;
            if (typeof ppRaw === 'string') {
                localStorage.setItem('project-plans', ppRaw);
            } else {
                try {
                    localStorage.setItem('project-plans', JSON.stringify(ppRaw));
                } catch (ePp) {}
            }
        }

        /**
         * Load-quote uses `?qid=` so the first paint can hydrate from the DB snapshot.
         * While `qid` stays in the URL, every reload re-runs this merge and overwrites
         * localStorage with that same snapshot — edits made after load never survive refresh.
         * Drop `qid` from the address bar once hydration succeeded; `planner_id` remains in JS
         * for submit/update. Subsequent reloads then behave like a normal planner session.
         */
        if (
            qidFromUrl &&
            serverItemsLen > 0 &&
            typeof history.replaceState === 'function'
        ) {
            try {
                var u = new URL(window.location.href);
                if (u.searchParams.has('qid')) {
                    u.searchParams.delete('qid');
                    var qs = u.searchParams.toString();
                    history.replaceState({}, '', u.pathname + (qs ? '?' + qs : '') + u.hash);
                }
            } catch (e) {}
        }
    }

    //----------------------------------------------------------------------------------

}

Planner.init();
