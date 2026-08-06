/**
 * Restore planner localStorage from PHP session (`fc_fence_info`), same idea as p1 `reloadFencingData`.
 * Without this, /fc/project-plan loads with an empty #fc-fence-list when localStorage was cleared or opened in a new tab.
 */
function fcHydrateProjectPlanLocalStorageFromSession() {
    if (typeof fc_fence_info === 'undefined' || !fc_fence_info || !fc_fence_info.fence_data) {
        return;
    }
    var raw = fc_fence_info.fence_data;
    if (typeof raw !== 'string' || raw.length < 2) {
        return;
    }

    var localSectionCountBefore = parseInt(localStorage.getItem('custom_fence-section'), 10);
    if (!Number.isFinite(localSectionCountBefore) || localSectionCountBefore < 1) {
        localSectionCountBefore = 1;
    }

    var custom_fence_items = [];
    try {
        if (typeof fcParsePlannerFenceDataJson === 'function') {
            custom_fence_items = fcParsePlannerFenceDataJson(raw);
        } else {
            custom_fence_items = JSON.parse(raw) || [];
        }
    } catch (e) {
        return;
    }

    if (!Array.isArray(custom_fence_items) || custom_fence_items.length === 0) {
        return;
    }

    var serverSectionCount = fc_fence_info.section_count || custom_fence_items.length || 1;

    if (typeof fcNormalizePlannerFencePayload === 'function') {
        custom_fence_items = fcNormalizePlannerFencePayload(custom_fence_items);
    }

    custom_fence_items.forEach(function(v) {
        if (!v || !v.form || !v.form[0] || v.form[0].tab === undefined || v.form[0].tab === null) {
            return;
        }
        v.form[0].style = v.form[0].style;
        v.form[0].tab = v.form[0].tab - 1;

        if (typeof fcNormalizePlannerTabRow0 === 'function') {
            fcNormalizePlannerTabRow0(v.form[0]);
        }

        localStorage.setItem('custom_fence-' + v.form[0].tab, JSON.stringify(v.form));

        if (v.settings && v.form[0].style) {
            localStorage.setItem('custom_fence-' + v.form[0].tab + '-' + v.form[0].style, JSON.stringify(v.settings));
        }
    });

    var cart_items = [];
    try {
        cart_items = JSON.parse(fc_fence_info.cart_items_data || '[]') || [];
    } catch (e2) {
        cart_items = [];
    }

    if (Array.isArray(cart_items) && cart_items.length) {
        if (typeof fcHydratePlannerCartItemsLocalStorage === 'function') {
            fcHydratePlannerCartItemsLocalStorage(cart_items);
        }
    }

    var mergedSectionCount = Math.max(serverSectionCount, localSectionCountBefore);
    localStorage.setItem('custom_fence-section', String(mergedSectionCount));

    if (fc_fence_info.project_plans_data) {
        var pp = fc_fence_info.project_plans_data;
        if (typeof pp === 'string') {
            localStorage.setItem('project-plans', pp);
        } else {
            try {
                localStorage.setItem('project-plans', JSON.stringify(pp));
            } catch (e3) {}
        }
    }
}

fcHydrateProjectPlanLocalStorageFromSession();

function fcProjectPlanEscapeHtml(str) {
    if (str == null || str === '') {
        return '';
    }
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/** Human-readable fence style title for project-plan section headers (from localStorage + fc_data catalog). */
function fcProjectPlanSectionFenceTitle(tabIdx0) {
    return typeof fcFenceSectionStyleTitle === 'function' ? fcFenceSectionStyleTitle(tabIdx0) : '';
}

let ProjectPlan = {

    init: function() {
        if (typeof applyOffcutDisplayPreference === 'function') {
            applyOffcutDisplayPreference();
        }
        this.reload_fence_items();
        this.countDownTimer();
    },

    //----------------------------------------------------------------------------------

    reload_fence_items: function() {
        var sectionCount = typeof fcGetPersistedFenceSectionCount === 'function'
            ? fcGetPersistedFenceSectionCount()
            : parseInt(localStorage.getItem('custom_fence-section'), 10);
        if (!sectionCount || sectionCount < 1) {
            return;
        }

        $('#fc-fence-list').empty();

        var htmlChunks = [];
        for (let i = 0; i < sectionCount; i++) {

            var fenceStyleTitle = fcProjectPlanSectionFenceTitle(i);
            var fenceStyleHtml = fenceStyleTitle
                ? `<div class="fc-project-plan-section-style">${fcProjectPlanEscapeHtml(fenceStyleTitle)}</div>`
                : '';

            var section = `<div class="border p-3 mb-4 mx-2 fc-project-plan-section fc-project-plan-section--pending" data-section-index="${i}" aria-busy="true">
                <div class="fc-project-plan-section-sticky-sentinel" aria-hidden="true"></div>
                <div class="fc-project-plan-section-head">
                    <div class="row align-items-center gx-2 mb-0">
                        <div class="col-auto fw-bold">
                            <div class="fc-project-plan-section-label"><span class="fc-project-plan-section-label__word">SECTION</span> <span class="fc-project-plan-section-label__num">${i + 1}</span></div>
                            ${fenceStyleHtml}
                        </div>
                        <div class="col fc-project-plan-section-head__overall text-center min-w-0" id="fc-section-overall-${i}"></div>
                        <div class="col-auto text-end fc-project-plan-section-actions">
                            <div class="d-inline-flex align-items-center gap-2 flex-nowrap">
                                <button type="button" class="btn btn-sm text-uppercase btn-outline-dark fw-bold fc-project-plan-summary-btn" data-section="${i}" aria-label="Summary section ${i + 1}">
                                    <i class="fa-solid fa-list-ul me-sm-1" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Summary</span>
                                </button>
                                <div class="dropdown fc-project-plan-download">
                                    <button type="button" class="btn btn-sm text-uppercase btn-outline-dark fw-bold dropdown-toggle fc-project-plan-download-toggle" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" data-section="${i}" aria-label="Download section" disabled>
                                        <i class="fa-solid fa-download me-sm-1" aria-hidden="true"></i>
                                        <span class="d-none d-sm-inline">Download</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <button type="button" class="dropdown-item fc-project-plan-download-png" data-section="${i}">Download PNG</button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item fc-project-plan-download-pdf" data-section="${i}">Download PDF</button>
                                        </li>
                                    </ul>
                                </div>
                                <a href="${base_url}?section=${i+1}" class="btn btn-sm text-uppercase btn-orange fw-bold fc-project-plan-edit-section" aria-label="Edit details">
                                    <i class="fa-regular fa-pen-to-square me-sm-1" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Edit Details</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="plan-item">
                    <div class="fc-project-plan-hscroll">
                        <div id="pp-${i}" class="dl-row">
                            <div class="fc-result">
                                <div class="fencing-panel-container">
                                    <div class="fc-project-plan-skeleton" aria-hidden="true">
                                        <div class="fc-project-plan-skeleton__run">
                                            <div class="fc-project-plan-skeleton__post"></div>
                                            <div class="fc-project-plan-skeleton__panel"><div class="fc-project-plan-skeleton__panel-fill"></div></div>
                                            <div class="fc-project-plan-skeleton__post"></div>
                                            <div class="fc-project-plan-skeleton__panel"><div class="fc-project-plan-skeleton__panel-fill"></div></div>
                                            <div class="fc-project-plan-skeleton__post"></div>
                                        </div>
                                        <div class="fc-project-plan-skeleton__meta">
                                            <span class="fc-project-plan-skeleton__line"></span>
                                            <span class="fc-project-plan-skeleton__line fc-project-plan-skeleton__line--narrow"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> `;

            htmlChunks.push(section);
            if ((i + 1) % 3 === 0 & i != 1) {
                htmlChunks.push(`<div style="dl-page-separator"></div>`);
            }
        }

        $('#fc-fence-list').append(htmlChunks.join(''));

        ProjectPlan.initSectionDownloadDropdowns();
        ProjectPlan.setupStickySectionHeadSentinels();

        var self = this;
        // Double rAF: first frame commits appended DOM, second runs after paint so skeletons are visible
        // before reload_load_fencing_items clears each .fencing-panel-container.
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                var idx = 0;
                function loadSectionStep() {
                    if (idx >= sectionCount) {
                        try {
                            if (typeof fcRebuildAllPlannerCarts === 'function') {
                                fcRebuildAllPlannerCarts(sectionCount);
                            }
                            if (typeof fcSyncProjectPlanSessionCart === 'function') {
                                fcSyncProjectPlanSessionCart(function() {
                                    $('body').removeClass('fc-project-plan-page-loading');
                                });
                            } else {
                                $('body').removeClass('fc-project-plan-page-loading');
                            }
                        } catch (eCartSync) {
                            $('body').removeClass('fc-project-plan-page-loading');
                        }
                        return;
                    }
                    self.reload_load_fencing_items(idx);
                    self.load_center_point(idx);
                    idx++;
                    if (idx < sectionCount) {
                        requestAnimationFrame(loadSectionStep);
                    }
                }
                loadSectionStep();
            });
        });
    },

    _downloadDropdownEventsBound: false,

    initSectionDownloadDropdowns: function() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) {
            return;
        }

        if (!ProjectPlan._downloadDropdownEventsBound) {
            ProjectPlan._downloadDropdownEventsBound = true;

            document.addEventListener('show.bs.dropdown', function(e) {
                var toggle = e.relatedTarget;
                if (!toggle || !toggle.classList.contains('fc-project-plan-download-toggle')) {
                    return;
                }
                var head = toggle.closest('.fc-project-plan-section-head');
                if (head) {
                    head.classList.add('fc-project-plan-section-head--dropdown-open');
                }
            });

            document.addEventListener('hide.bs.dropdown', function(e) {
                var toggle = e.relatedTarget;
                if (!toggle || !toggle.classList.contains('fc-project-plan-download-toggle')) {
                    return;
                }
                var head = toggle.closest('.fc-project-plan-section-head');
                if (head) {
                    head.classList.remove('fc-project-plan-section-head--dropdown-open');
                }
            });
        }

        document.querySelectorAll('#fc-fence-list .fc-project-plan-download-toggle').forEach(function(btn) {
            bootstrap.Dropdown.getOrCreateInstance(btn, {
                popperConfig: function(defaultBsPopperConfig) {
                    var config = Object.assign({}, defaultBsPopperConfig);
                    config.strategy = 'fixed';
                    config.placement = 'bottom-end';
                    config.modifiers = (config.modifiers || []).concat([
                        { name: 'offset', options: { offset: [0, 6] } }
                    ]);
                    return config;
                }
            });
        });
    },

    /**
     * Toggle fc-project-plan-section-head--stuck when the section toolbar pins (IntersectionObserver).
     */
    _sectionHeadStickyObservers: [],

    setupStickySectionHeadSentinels: function() {
        var prev = ProjectPlan._sectionHeadStickyObservers;
        if (prev && prev.length) {
            prev.forEach(function(o) {
                try {
                    o.disconnect();
                } catch (e) {
                    /* noop */
                }
            });
        }
        ProjectPlan._sectionHeadStickyObservers = [];

        if (typeof IntersectionObserver !== 'function') {
            return;
        }

        var sentinels = document.querySelectorAll('#fc-fence-list .fc-project-plan-section-sticky-sentinel');
        sentinels.forEach(function(sentinel) {
            var head = sentinel.nextElementSibling;
            if (!head || !head.classList.contains('fc-project-plan-section-head')) {
                return;
            }

            var io = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    head.classList.toggle('fc-project-plan-section-head--stuck', !entry.isIntersecting);
                });
            }, {
                root: null,
                threshold: 0
            });

            io.observe(sentinel);
            ProjectPlan._sectionHeadStickyObservers.push(io);
        });
    },

    /**
     * Project-plan / planner diagram: panel templates set Centers line to panel + post width.
     * When this panel sits on an end with no post (left/right), show panel width only.
     */
    fixCentersWidthWithoutEndPost: function($panelItem) {
        var panelW = $panelItem.find('.fc-panel-size').first().text().replace(/\s+/g, '').trim();
        if (!panelW) {
            return;
        }
        var $fcp = $panelItem.find('.fc-first-c-p');
        var $start = $fcp.find('.fc-start-c-p');
        var $br = $fcp.find('br').first();
        if (!$fcp.length || !$start.length || !$br.length) {
            return;
        }
        var node = $start[0].nextSibling;
        while (node && node !== $br[0]) {
            var next = node.nextSibling;
            if (node.parentNode) {
                node.parentNode.removeChild(node);
            }
            node = next;
        }
        $br.before(document.createTextNode(panelW));
    },

    /**
     * Project plan: align Centers dimension lines to one baseline when panel/gate bottoms
     * differ (e.g. gate taller than fence). Only .fc-center-point is shifted — not panels or posts.
     */
    syncProjectPlanCenterPointBaseline: function(tab) {
        if (!$('body').hasClass('fc-project-plan-page')) {
            return;
        }

        var $fc = $('#pp-' + tab).find('.fencing-panel-container');
        if (!$fc.length) {
            return;
        }

        var $centerPoints = $fc.find('.fc-center-point:visible');
        if (!$centerPoints.length) {
            return;
        }

        var entries = [];
        $centerPoints.each(function() {
            var rect = this.getBoundingClientRect();
            entries.push({ el: this, y: rect.top });
        });

        if (!entries.length) {
            return;
        }

        var maxY = entries[0].y;
        for (var i = 1; i < entries.length; i++) {
            if (entries[i].y > maxY) {
                maxY = entries[i].y;
            }
        }

        entries.forEach(function(entry) {
            var delta = maxY - entry.y;
            entry.el.style.transform = delta > 0.5 ? 'translateY(' + Math.round(delta) + 'px)' : '';
        });
    },

    //----------------------------------------------------------------------------------

    load_center_point: function(tab) {
        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var $pp = $('#pp-' + tab),
            $fc = $pp.find('.fencing-panel-container');

        var panel_count = $fc.find('.fencing-panel-item').length,
            left_raked_count = $fc.find('.left_raked-panel .raked-panel-container').length,
            right_raked_count = $fc.find('.right_raked-panel .raked-panel-container').length;

        var overallLabel = custom_fence_tab[0]?.style === 'slat_fence_infill' ? 'Opening Width' : 'Overall';
        var overallVal = custom_fence_tab[0]?.calculateValue;
        if (typeof fcReadCalculateValueForStyle === 'function' && custom_fence_tab[0]) {
            var cvOv = fcReadCalculateValueForStyle(custom_fence_tab[0], custom_fence_tab[0].style);
            if (cvOv !== undefined && cvOv !== null && String(cvOv) !== '') {
                overallVal = cvOv;
            }
        }
        var overallNum = parseInt(String(overallVal != null ? overallVal : '').replace(/,/g, ''), 10);
        var overallHtml = '';
        if (Number.isFinite(overallNum) && overallNum > 0) {
            overallHtml = `<div class="fc-overall">${overallNum.toLocaleString()} ${overallLabel}</div>`;
        }
        $('#fc-section-overall-' + tab).html(overallHtml);

        var $fencingItems = $fc.find('.fencing-panel-item');
        $fencingItems.first().addClass('first-item');
        $fencingItems.last().addClass('last-item');

        if ($fc.find('.raked-panel .raked-panel-container').length == 1) {
            $fc.find('.raked-panel').addClass('first-item last-item');
        } else {
            $fc.find('.left_raked-panel').first().addClass('first-item');
            $fc.find('.right_raked-panel').first().addClass('last-item');
        }

        if ($fc.find('.left-panel-post.no-post').length) {
            $fc.find('.fc-center-point').first().addClass('cp_no-post--left');
        }

        if ($fc.find('.right-panel-post.no-post').length) {
            $fc.find('.fc-center-point').last().addClass('cp_no-post--right');
        }

        $fencingItems.not(':last').find('.fc-last-c-p').remove();

        if (left_raked_count && panel_count > 1) {
            $fc.find('.left_raked-panel .fc-last-c-p').remove();
        }

        var $spacingNums = $pp.find('.fencing-panel-spacing-number');
        var sectionSlug = custom_fence_tab[0]?.style || custom_fence_tab[0]?.fence || '';
        var hidePostValue =
            typeof SlatFence !== 'undefined' && SlatFence.shouldHidePostValue(sectionSlug);

        if (hidePostValue) {
            $spacingNums.find('> span:first-child').text('');
            $fc.addClass('fc-hide-post-value');
            $fencingItems.each(function() {
                var $panel = $(this);
                if ($panel.find('.fc-panel-size').length) {
                    ProjectPlan.fixCentersWidthWithoutEndPost($panel);
                }
            });
            $pp.find('.fc-start-c-p').empty();
            $pp.find('.fc-end-c-p').empty();
        } else {
            $fc.removeClass('fc-hide-post-value');
            $pp.find('.fc-start-c-p').html($spacingNums.first().find('span').html());
            $pp.find('.fc-end-c-p').html($spacingNums.last().find('span').html());
        }

        var group = $fc.attr('data-group');

        if (group == 'a') {
            $pp.find('.first-item .fc-start-c-p').html($pp.find('.left-panel-post span:not(.cg-top)').text());
            $pp.find('.last-item .fc-end-c-p').html($pp.find('.right-panel-post span:not(.cg-top)').text());
        }

        // Tubular styles (panel group b): no post on an end → do not add post width into Centers line.
        if (group !== 'a') {
            if ($fc.find('.left-panel-post.no-post').length) {
                var $firstPanel = $fc.find('.fencing-panel-item.first-item').first();
                if ($firstPanel.find('.fc-panel-size').length) {
                    ProjectPlan.fixCentersWidthWithoutEndPost($firstPanel);
                }
            }
            if ($fc.find('.right-panel-post.no-post').length) {
                var $lastPanel = $fc.find('.fencing-panel-item.last-item').first();
                if ($lastPanel.find('.fc-panel-size').length) {
                    ProjectPlan.fixCentersWidthWithoutEndPost($lastPanel);
                }
            }
        }

        var syncTab = tab;
        requestAnimationFrame(function() {
            ProjectPlan.syncProjectPlanCenterPointBaseline(syncTab);
        });

    },

    //----------------------------------------------------------------------------------

    reload_load_fencing_items: function(tab) {
        var center_post = FENCE.settings.item.center_point;

        var $planSection = $('#pp-' + tab).closest('.fc-project-plan-section');

        function clearPlanSectionPending() {
            if ($planSection && $planSection.length) {
                $planSection.removeClass('fc-project-plan-section--pending').removeAttr('aria-busy');
                $planSection.find('.fc-project-plan-download-toggle').prop('disabled', false);
            }
        }

        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var rawStyle = custom_fence_tab[0]?.style;
        var i = normalizeFenceStyleSlug(rawStyle);

        var custom_fence = readCustomFenceSegment(tab, rawStyle),
            info = fc_data[i];

        if (!info) {
            clearPlanSectionPending();
            return;
        }

        var fencingPanelContainer = '#pp-' + tab + ' .fencing-panel-container';
        var $ppFc = $(fencingPanelContainer);
        var $ppRoot = $('#pp-' + tab);

        $ppFc
            .html('')
            .attr('data-group', info?.panel_group)
            .attr('data-type', info?.slug);

        var fence_height_filtered_data = info?.form?.filter(function(item) {
            return item.slug == 'fence_height';
        });

        if (fence_height_filtered_data) {
            $ppFc.addClass('custom-height');
        }

        var cf_data = { item: i, tab: tab };

        if (
            info.panel_group === 'a' &&
            typeof fcGlassPoolPersistGateFieldsIfNeeded === 'function'
        ) {
            fcGlassPoolPersistGateFieldsIfNeeded(tab, i, info);
            custom_fence = readCustomFenceSegment(tab, rawStyle);
        }

        var calc = calculate_fences(cf_data);

        if (!calc) {
            clearPlanSectionPending();
            return;
        }

        var panel_fence_height =
            typeof fcGetPanelLabelFenceHeightLineHtml === 'function'
                ? fcGetPanelLabelFenceHeightLineHtml(i, calc, {
                      context: { fenceInfo: custom_fence, tabInfo: custom_fence_tab }
                  })
                : '';

        var pg = info.panel_group;
        var tplPanelItem = $('script[data-type="panel_item-' + pg + '"]').text();
        var tplPanelSpacing = $('script[data-type="panel_spacing-' + pg + '"]').text();
        var tplExtraPanel = $('script[data-type="extra_panel_item-' + pg + '"]').text();
        var tplShortPanel = $('script[data-type="short_panel_item-' + pg + '"]').text();
        var tplOffcut = $('script[data-type="offcut"]').text();

        var center_point = FENCE.get(i, 'post');

        // Update spacing for glass fence
        if( info.panel_group == 'a' ) {
            var center_point = calc.selected_values.spacing;
        }

        var tplCenterPoint =
            typeof SlatFence !== 'undefined' && SlatFence.shouldHidePostValue(i) ? '' : center_point;
        var panelSizeCenterW = function(panelMm) {
            if (typeof SlatFence !== 'undefined' && SlatFence.formatPanelSizeCenterW) {
                return SlatFence.formatPanelSizeCenterW(panelMm, center_point, i);
            }
            return panelMm + center_point + 'W';
        };

        gate_hinge_panel_number = panel_number = 0;

        var gateRowsPp = (custom_fence || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var hingePanelWidthPp =
            calc.gate_hinge_panel.width > 0
                ? calc.gate_hinge_panel.width
                : typeof fcResolveGlassPoolHingePanelWidthMm === 'function'
                  ? fcResolveGlassPoolHingePanelWidthMm(gateRowsPp, calc, info)
                  : calc.gate_hinge_panel.width;

        if (calc.gate_hinge_panel.count && hingePanelWidthPp > 0) {

            for (let hi = 0; hi < calc.gate_hinge_panel.count; hi++) {

                var panel_size = hingePanelWidthPp,
                    panel_unit = FENCES.defaultValues.unit;
                panel_option_value = calc.selected_values.panel_option;

                var gate_hinge_panel_number = hi;


                if(panel_option_value.indexOf('full') !== -1) {
                    panel_option_value = panel_option_value.split('_')[0];
                }

                // Fence height
                if(calc.fence_size.height) {
                    panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
                }

                var tpl = tplExtraPanel
                    .replace(/{{center_point}}/gi, tplCenterPoint)
                    .replace(/{{panel_size}}/gi, panel_size + 'W')
                    .replace(/{{panel_value}}/gi, panel_option_value)
                    .replace(/{{panel_unit}}/gi, 'HINGE<br>PANEL')
                    .replace(/{{panel_size_center}}/gi, panelSizeCenterW(panel_size))
                    .replace(/{{panel_number}}/gi, gate_hinge_panel_number);

                $ppFc.append(tpl);

            }

            var hingePw = hingePanelWidthPp * FENCE.get('item', 'base_margin');
            $ppFc.find('.extra-panel-item').css({ 'width': hingePw }).attr('data-width', hingePw);

            var tpl = tplPanelSpacing
                .replace(/{{center_point}}/gi, tplCenterPoint);

        }

        mesurement = $(FENCES.el.measurementBoxNumber).val();

        var totalLongPanels = parseInt(calc.long_panel.count || 0, 10);
        var infillRenderPlan =
            typeof SlatFenceInfill !== 'undefined' && SlatFenceInfill.getLongPanelRenderPlan
                ? SlatFenceInfill.getLongPanelRenderPlan(i, totalLongPanels)
                : { renderLongPanels: totalLongPanels, hiddenLongPanels: 0 };
        var renderLongPanels = infillRenderPlan.renderLongPanels;
        var hiddenLongPanels = infillRenderPlan.hiddenLongPanels;

        for (let pi = 0; pi < renderLongPanels; pi++) {

            var panel_number = pi + gate_hinge_panel_number;
            if(gate_hinge_panel_number) {
                panel_number = pi + gate_hinge_panel_number + 1;
            }

                panel_size = calc.long_panel.length,
                panel_unit = FENCES.defaultValues.unit,
                data_key = "post_options";

            var panel_option_value = calc.selected_values.panel_option;

            if(panel_option_value.indexOf('full') !== -1) {
                panel_option_value = panel_option_value.split('_')[0];
            }

            // Fence height
            if(calc.fence_size.height) {
                panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
            }

            var tpl = tplPanelItem
                .replace(/{{data_key}}/gi, tplCenterPoint)
                .replace(/{{center_point}}/gi, tplCenterPoint)
                .replace(/{{panel_value}}/gi, panel_option_value)
                .replace(/{{panel_fence_height}}/gi, panel_fence_height)
                .replace(/{{panel_size}}/gi, panel_size + 'W')
                .replace(/{{panel_unit}}/gi, 'PANEL')
                .replace(/{{panel_size_center}}/gi, panelSizeCenterW(panel_size))
                .replace(/{{panel_number}}/gi, panel_number);
        
            // if( panel_size > FENCE.get(slug, 'minPanelWidthOnGate') ) { } 
            if(panel_size > 0) {
                $ppFc.append(tpl);
            }

        }

        if (renderLongPanels > 0 && calc.long_panel.length > 0) {
            $ppFc.find('.fencing-panel-item').css({ 'width': calc.long_panel.length * 0.10 });
        }

        var tpl = tplPanelSpacing
            .replace(/{{center_point}}/gi, tplCenterPoint);


        if(calc.short_panel.count) {

            for (let si = 0; si < calc.short_panel.count; si++) {

                var panel_size = calc.short_panel.length,
                    panel_unit = FENCES.defaultValues.unit;
                panel_option_value = calc.selected_values.panel_option;

                var short_panel_number = panel_number + gate_hinge_panel_number + si;
                if( panel_number ) {
                    short_panel_number = panel_number + gate_hinge_panel_number + si + 1;
                }

                if(panel_option_value.indexOf('full') !== -1) {
                    panel_option_value = panel_option_value.split('_')[0];
                }

                // Fence height
                if(calc.fence_size.height) {
                    panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
                }

                var tpl = tplShortPanel
                    .replace(/{{center_point}}/gi, tplCenterPoint)
                    .replace(/{{panel_fence_height}}/gi, panel_fence_height)
                    .replace(/{{panel_size}}/gi, panel_size + 'W')
                    .replace(/{{panel_value}}/gi, panel_option_value)
                    .replace(/{{panel_unit}}/gi, 'PANEL')
                    .replace(/{{panel_size_center}}/gi, panelSizeCenterW(panel_size))
                    .replace(/{{panel_number}}/gi, short_panel_number);

                $ppFc.append(tpl);

            }

            var shortPw = calc.short_panel.length * 0.10;
            $ppFc.find('.short-panel-item').css({ 'width': shortPw }).attr('data-width', shortPw);

            tpl = tplPanelSpacing
                .replace(/{{center_point}}/gi, tplCenterPoint);

        }


        $ppFc.append(tpl);

        if (typeof SlatFenceInfill !== 'undefined' && SlatFenceInfill.isActive(i)) {
            SlatFenceInfill.applyPostUiAdjustments(fencingPanelContainer);
            SlatFenceInfill.appendHiddenPanelsTile(fencingPanelContainer, hiddenLongPanels);
        }

        // Set the ID for each panel item
        setPanelItemsID(tab);

        // No panel item 
        if($ppRoot.find('.single-panel, #panel-item-0').length == 0 && $ppRoot.find('.panel-item:not(.fencing-raked-panel)').length == 0 ) {
            if( $ppFc.find('.panel-post').length ) {
                $ppFc.find('.panel-post').first().after('<div id="panel-item-x" class="single-panel"></div>'); 
            } else {
                $ppFc.find('.fencing-panel-spacing-number').first().after('<div id="panel-item-x" class="single-panel"></div>'); 
            }
        }

        this.re_update_gate('edit', tab);

        $ppFc.prepend('<div data-cart-key="raked-panel" class="left_raked-panel raked-panel"></div>')
            .append('<div data-cart-key="raked-panel" class="right_raked-panel raked-panel"></div>');

        this.re_update_raked_panels(['left_raked', 'right_raked'], tab);

        // Panel off-cut
        if(calc.offcut_panel.count && calc.offcut_panel.length) {
            var tplOcc = tplOffcut
                .replace(/{{slug}}/gi, 'panel-offcut')
                .replace(/{{name}}/gi, 'Panel')
                .replace(/{{count}}/gi, calc.offcut_panel.count)
                .replace(/{{group}}/gi, info.panel_group)
                .replace(/{{width}}/gi, calc.offcut_panel.length);

            $ppFc.append(tplOcc);

            $ppRoot.find('.fencing-offcut.panel-offcut .offcut-body').css({ 'width': calc.offcut_panel.length * 0.10 });
        }

        // Custom gate off-cut
        if(calc.offcut_gate_panel.count && calc.offcut_gate_panel.length) {
            var tplGateOcc = tplOffcut
                .replace(/{{slug}}/gi, 'gate-offcut')
                .replace(/{{name}}/gi, 'Gate')
                .replace(/{{count}}/gi, calc.offcut_gate_panel.count)
                .replace(/{{group}}/gi, info.panel_group)
                .replace(/{{width}}/gi, calc.offcut_gate_panel.length);

            $ppFc.append(tplGateOcc);

            $ppRoot.find('.fencing-offcut.gate-offcut .offcut-body').css({ 'max-width': calc.offcut_gate_panel.length * 0.10 });
        }

        // Remove offcut - On Gate
        if( parseInt(custom_fence_tab[0].mbn) <= FENCE.get(i, 'maxOnGate') ) {
            // $('.panel-offcut').remove();
        } else if( parseInt(custom_fence_tab[0].mbn) <= FENCE.get(i, 'minOnGate') ) {
            $ppRoot.find('.fencing-offcut').remove();
        }
            
        /* 
            REMOVED: Clear tooltip like error massage
        */

        var $spacings = $ppFc.find('.fencing-panel-spacing-number');
        $spacings.first().find('.fs-clamp').remove();
        $spacings.last().find('.fs-clamp').remove();

        FENCE.call('near_gate_spacing');

        var slatCtx = { fenceInfo: custom_fence, tabInfo: custom_fence_tab };
        if (SlatFence.isSlatLike(info?.slug)) {
            if (typeof SlatFence.applySlatFenceDisplayHeights === 'function') {
                SlatFence.applySlatFenceDisplayHeights(i, calc, slatCtx, $ppRoot);
            } else {
                SlatFence.applyGapPattern(custom_fence, info, fencingPanelContainer, calc, slatCtx);
                SlatFence.applySlatPanelInlineHeights(i, calc, slatCtx, { $root: $ppRoot });
            }
        } else if (typeof fcApplyGroupBFenceDisplayHeights === 'function') {
            fcApplyGroupBFenceDisplayHeights(calc, $ppRoot, {
                slug: i,
                tabInfo: custom_fence_tab
            });
        }

        clearPlanSectionPending();

    },

    //----------------------------------------------------------------------------------

    near_gate_spacing: function(tab) {
        var $fc = $('#pp-' + tab + ' .fencing-panel-container'),
            $gl = $fc.find('.panel-gate-left'),
            $gr = $fc.find('.panel-gate-right');
        $fc.find('.near-gate').removeClass('near-gate');
        $gl.next().next().addClass('near-gate');
        $gl.next().addClass('near-gate');
        $gl.prev().prev().addClass('near-gate');
        $gl.prev().addClass('near-gate');

        $gr.next().addClass('near-gate');
        $gr.next().next().addClass('near-gate');
        $gr.prev().addClass('near-gate');
    },

    //----------------------------------------------------------------------------------

    re_update_gate: function(action, tab) {
        var center_post = FENCE.settings.item.center_point;

        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var rawStyle = custom_fence_tab[0]?.style;
        var i = normalizeFenceStyleSlug(rawStyle);

        var custom_fence = readCustomFenceSegment(tab, rawStyle),
            info = fc_data[i];

        var gate_data = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        var find_gate = gate_data;

        if (find_gate.length) {
            placement = find_gate[0]?.settings?.placement;
        } else {

            placement = 0;

        }

        var center_point = FENCE.get(i, 'post'),
            mesurement = $('.measurement-box-number').val();

        var cf_data = { item: i, tab: tab },
            calc = calculate_fences(cf_data);

        var panel_size = calc.gate.length,
            panel_unit = 'mm',
            gate_size = calc.gate.width;

        var gate_label_width = gate_size;
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.getGateDisplayWidthMm === 'function') {
            var gwLblPp = SlatFence.getGateDisplayWidthMm(i, gate_data, calc);
            if (Number.isFinite(gwLblPp) && gwLblPp > 0) {
                gate_label_width = gwLblPp;
            }
        }

        var panel_size_center = (gate_size + 20 + 20 + center_point)  + 'W';


        // Slat Fence: Gate Options "Width Dimension From" affects the gate panel centers display.
        if (i === 'slat' || i === 'slat_fence') {
            const gateWdf = parseInt(gate_data?.[0]?.settings?.fields?.find(it => it.key === 'width_dimension_from')?.val, 10);
            const mult = (Number.isFinite(gateWdf) && (gateWdf === -1 || gateWdf === -2)) ? Math.abs(gateWdf) : 1;
            panel_size_center = (gate_size + 20 + 20 + (mult * center_point)) + 'W';
        }

        // Update spacing for glass fence
        if (info.panel_group == 'a') {
            var center_point = calc.selected_values.spacing;
            var panel_size_center = gate_size + center_point + 'W';
        }


        /* Set Gate panel Name */
        var gate_hinge_type = gate_data[0]?.settings?.fields?.find(function(item) {
            return item.key == 'gate_hinge_type';
        });

        panel_name = 'GATE';
        gate_class = '';

        if(gate_hinge_type) {
            panel_name = gate_hinge_type?.val == 'opt-1' ? 'STD GATE' : 'SC GATE';
            gate_class = 'hinge-type-'+gate_hinge_type?.val;
        } else if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(i)) {
            panel_name = 'SC GATE';
            gate_class = 'hinge-type-opt-2';
        }


        if (action == 'add' || action == 'edit') {

            if (placement == -1 ) {

                var isLeftSwing =
                    typeof fcGlassPoolIsLeftHandSwingFromGateData === 'function' &&
                    fcGlassPoolIsLeftHandSwingFromGateData(gate_data);
                var hasHingePanel = $('#pp-' + tab + ' .extra-panel-item').length > 0;
                var leftFirstLayout = info.panel_group == 'a' && isLeftSwing && hasHingePanel;

                if (leftFirstLayout) {
                    var tplLeftFirst = $('script[data-type="panel_gate-' + info.panel_group + '-l"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_label_width)
                        .replace(/{{panel_size_center}}/gi, panel_size_center)
                        .replace(/{{center_post}}/gi, center_post)
                        .replace(/{{panel_name}}/gi, panel_name)
                        .replace(/{{panel_unit}}/gi, panel_unit);

                    $('#pp-' + tab + ' #panel-item-0').after(tplLeftFirst);
                } else {
                    var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-r"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, gate_label_width)
                        .replace(/{{panel_size_center}}/gi, panel_size_center)
                        .replace(/{{center_post}}/gi, center_post)
                        .replace(/{{panel_name}}/gi, panel_name)
                        .replace(/{{panel_unit}}/gi, panel_unit);

                    $('#pp-' + tab + ' #panel-item-0, #pp-' + tab + ' #panel-item-x').before(tpl);
                }

            } else if (find_gate.length && placement >= 0) {

                var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-l"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_label_width)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{center_post}}/gi, center_post)
                    .replace(/{{panel_name}}/gi, panel_name)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                $('#pp-' + tab + ' #panel-item-' + placement).after(tpl);


            } else if (action == 'add' && placement == 0) {

                temp = $('script[data-type="panel_gate-' + info.panel_group + '-l"]');

                if($('.fencing-panel-items [data-key="panel_options"]').length) {
                    temp = $('script[data-type="panel_gate-' + info.panel_group + '-r"]')
                }

                var tpl = temp.text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_label_width)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{center_post}}/gi, center_post)
                    .replace(/{{panel_name}}/gi, panel_name)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                var panelID = $('[data-cart-key="panel_options"].fencing-panel-item').attr('id');

                $('#pp-' + tab + ' #'+panelID+', #pp-' + tab + ' .fencing-panel-items .raked-panel-container').after(tpl);

            }

        }



        var gateCartValue = 1;
        if (calc.fence_size && calc.fence_size.height) {
            gateCartValue = calc.fence_size.height;
        }

        $('#pp-' + tab + ' .fencing-panel-gate')
            .attr('data-cart-value', gateCartValue)
            .css({ 'max-width': calc.gate.length * 0.1 });

        if (info.panel_group == 'b') {
            $('#pp-' + tab + ' .fencing-panel-gate')
                .prepend('<span class="fc-gate-spacing fc-gate-left-spacing">20</span>')
                .append('<span class="fc-gate-spacing fc-gate-right-spacing">20</span>') ;       
        }

        // Slat Fence: gate label shows type + total opening width (double = 2× leaf width).
        if (i === 'slat' || i === 'slat_fence') {
            const gateTypeSlug = gate_data?.[0]?.settings?.fields?.find(item => item.key === 'gate_type')?.val || 'single';
            const gateTypeLabel = (gateTypeSlug === 'double') ? 'Double' : 'Single';
            const displayGateWidthMm =
                typeof SlatFence !== 'undefined' && typeof SlatFence.getGateOpeningWidthMm === 'function'
                    ? SlatFence.getGateOpeningWidthMm(i, gate_data, calc)
                    : parseInt(gate_size, 10);
            const gateWidthLabel = Number.isFinite(displayGateWidthMm) && displayGateWidthMm > 0 ? displayGateWidthMm : 0;
            $('#pp-' + tab + ' .fencing-panel-gate')
                .find('.fencing-panel-item-size')
                .html(`${gateTypeLabel}<br>${gateWidthLabel}${panel_unit}<br> ${panel_name}`);

            // Double gate: add a center divider line.
            $('#pp-' + tab + ' .fencing-panel-gate').find('.double-gate').remove();
            if (gateTypeSlug === 'double') {
                $('#pp-' + tab + ' .fencing-panel-gate').append('<div class="double-gate"></div>');
            }
        }

        // Remove hinge type class
        $('#pp-' + tab + ' .fencing-panel-container').removeClass('hinge-type-opt-1 hinge-type-opt-2').addClass(gate_class);     

        this.re_update_hinge_panel(info.panel_group, placement, tab);

    },

 //----------------------------------------------------------------------------------

    re_update_hinge_panel: function(group, placement, tab) {

        if( group != 'a' ) return;

        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var rawStyle = custom_fence_tab[0]?.style;
        var i = normalizeFenceStyleSlug(rawStyle);

        var custom_fence = readCustomFenceSegment(tab, rawStyle),
            info = fc_data[i];

        var gate_data = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

    
        var cf_data = { item: i, tab: tab },
            calc = calculate_fences(cf_data);



        var gate_data = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        var gate_hinge_panel = gate_data[0]?.settings?.fields.find(function(item) {
            return item.key == 'gate_hinge_panel_width';
        });

        var gate_width = gate_data[0]?.settings?.fields.find(function(item) {
            return item.key == 'gate_width';
        });


        // Update gate width
        var gate_panel_width = 0;
        if(gate_width) {
            gate_panel_width = gate_width.val;
            updateGateSettings('size', gate_panel_width);
        }

        var gate_hinge_panel_width = 0;
        if(gate_hinge_panel) {
            gate_hinge_panel_width = gate_hinge_panel.val;
        }

        var resolvedHingeWidthPp =
            typeof fcResolveGlassPoolHingePanelWidthMm === 'function'
                ? fcResolveGlassPoolHingePanelWidthMm(gate_data, calc, info)
                : parseInt(gate_hinge_panel_width, 10) || 0;

        var gate_swing = gate_data[0]?.settings?.fields.find(function(item) {
            return item.key == 'gate_hinge_position';
        });

        // Get default gate swing
        var gate_options_default = info.settings.gate.fields[4].options.filter(function(item) {
            return item.default == true;
        });

        var default_swing_slug = gate_options_default[0].slug;

        last_id = $('#pp-' + tab + ' ' + FENCES.el.panelItem + ':not(.fencing-raked-panel,.fencing-panel-gate,.extra-panel-item)')
            .last()
            .attr('data-id');

        var useGlassPoolPlacementDrivePp =
            typeof fcGlassPoolUsesPlacementDrive === 'function' &&
            fcGlassPoolUsesPlacementDrive(gate_data, $('#pp-' + tab + ' .fencing-panel-gate'));

        setTimeout(function() {
            $('#pp-' + tab + ' .fc-hinges-set').remove();

            swing_slug = gate_swing?.val ? gate_swing?.val : default_swing_slug;

            default_swing = true;

            op = placement;

            if( placement < 0 ) {
                default_swing = false;
                placement = 0;

                if( swing_slug.includes('left') ) {
                    default_swing = true;
                }
            }

         //   $('[data-slug="left-swing"], [data-slug="right-swing"]').parent().removeClass('disabled');

            if (!useGlassPoolPlacementDrivePp) {
                var fdPp = { tab: tab, slug: i, info: custom_fence, data: info };

                if( op < 0 && swing_slug.includes('left') && $('#pp-' + tab + ' #panel-item-left-raked').length ) {
                    setGateFieldSettings(fdPp, 'gate_hinge_position', 'right-swing');
                    op = 1;
                    default_swing = false;

                    $('#pp-'+tab+' [data-slug="left-swing"]').removeClass('fc-selected');
                    $('#pp-'+tab+' [data-slug="right-swing"]').addClass('fc-selected');
                    $('#pp-'+tab+' [data-slug="left-swing"]').parent().addClass('disabled');
                }

                if( swing_slug.includes('right') ) {

                    if( op < 0 ) {
                        placement = 0;
                    } else if( placement == 0 ) {
                        placement = 1;
                    } else {
                        placement = parseInt(placement) + 1;
                    }

                    if( !$('#pp-'+tab+' #panel-item-'+placement).length ) {
                        placement = parseInt(placement) + 1;
                    }
                    default_swing = false;

                    if( op == last_id && $('#pp-'+tab+' #panel-item-right-raked').length ) {

                        setGateFieldSettings(fdPp, 'gate_hinge_position', 'left-swing');
                        default_swing = true;
                        placement = last_id;

                        $('#pp-'+tab+' [data-slug="right-swing"]').removeClass('fc-selected');
                        $('#pp-'+tab+' [data-slug="left-swing"]').addClass('fc-selected');

                        $('#pp-'+tab+' [data-slug="right-swing"]').parent().addClass('disabled');
                    }

                }
            }

            if( group == 'a' && $('#pp-'+tab+' .fencing-panel-gate').length ) {

                var gate_hinge_type = gate_data[0]?.settings?.fields.find(function(item) {
                    return item.key == 'gate_hinge_type';
                });
                var gate_position = gate_data[0]?.settings?.position;

                if (!useGlassPoolPlacementDrivePp) {
                    var panel_size = $('#pp-'+tab+' .panel-item.hinge-panel').attr('data-panel-size'),
                        panel_width = $('#pp-'+tab+' .panel-item.hinge-panel').data('width');

                    $('#pp-'+tab+' .hinge-panel .fc-panel-size').html(panel_size);
                    $('#pp-'+tab+' .hinge-panel .fc-panel-unit').html('PANEL');
                    $('#pp-'+tab+' .hinge-panel').removeClass('hinge-panel').css({'width':panel_width});

                    $('#pp-'+tab+' .hinge-panel .fc-panel-unit').html('PANEL');
                    $('#pp-'+tab+' .hinge-panel').removeClass('hinge-panel');
                }

                if ($('#pp-' + tab + ' .extra-panel-item').length) {
                    $('#pp-' + tab + ' .extra-panel-item')
                        .removeClass('hinge-panel-alt')
                        .addClass('hinge-panel')
                        .css({ 'width': resolvedHingeWidthPp * FENCE.get('item', 'base_margin') });
                    $('#pp-' + tab + ' .fencing-panel-gate').css({ 'width': gate_panel_width * FENCE.get('item', 'base_margin') });

                    var panel_name = 'PANEL';
                    if (gate_hinge_type) {
                        panel_name = gate_hinge_type?.val == 'opt-1' ? 'STD HINGE' : 'SC HINGE';
                    }

                    $('#pp-' + tab + ' .hinge-panel .fc-panel-unit').html(panel_name);
                    $('#pp-' + tab + ' .hinge-panel .fc-panel-size').html(resolvedHingeWidthPp + 'W');
                }

                var hinges_panel = `<div class="fc-hinges-set">
                    <div class="fc-hinges fc-hinges-top">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>
                    </div> 
                    <div class="fc-hinges fc-hinges-bot">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>
                    </div>      
                </div>`;

                $('.hinge-panel').append(hinges_panel);

                var hinges_gate = `<div class="fc-hinges-set">
                    <div class="fc-hinges fc-hinges-top">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>    
                    </div> 
                    <div class="fc-hinges fc-hinges-center">
                        <i class="fa-latch"></i>
                    </div> 
                    <div class="fc-hinges fc-hinges-bot">
                        <i class="fa-hinge-type"></i>
                        <i class="fa-hinge-type"></i>                        
                    </div>      
                </div>`;

                $('#pp-'+tab+' .fencing-panel-gate').append(hinges_gate);

                $('#pp-'+tab+' .fencing-panel-gate').removeClass('panel-gate-left panel-gate-right');       

                if( default_swing ) {
                    $('#pp-'+tab+' .hinge-panel .fc-hinges-set').addClass('fc-hinges-right');
                    $('#pp-'+tab+' .fencing-panel-gate .fc-hinges-set').addClass('fc-hinges-left');       
                    $('#pp-'+tab+' .fencing-panel-gate').addClass('panel-gate-left');       
                } else {
                    $('#pp-'+tab+' .hinge-panel .fc-hinges-set').addClass('fc-hinges-left');
                    $('#pp-'+tab+' .fencing-panel-gate .fc-hinges-set').addClass('fc-hinges-right');    
                    $('#pp-'+tab+' .fencing-panel-gate').addClass('panel-gate-right');                                          
                }

                // Switch the hinge panel next to the gate (start/end corners: finalize handles order).
                left_swing_gate = $('#pp-' + tab + ' .panel-gate-left').prev().prev().prev().attr('id');
                right_swing_gate = $('#pp-' + tab + ' .panel-gate-right').next().next().next().attr('id');
                switch_panel_id = right_swing_gate ? right_swing_gate : left_swing_gate;
                var skipSwitchForEndHinge = String(op) === String(last_id) && swing_slug.includes('right');
                var skipSwitchForStartHinge =
                    (op < 0 && swing_slug.includes('right')) ||
                    (swing_slug.includes('left') && (op < 0 || gate_position === 'first'));
                if (useGlassPoolPlacementDrivePp) {
                    skipSwitchForEndHinge = true;
                    skipSwitchForStartHinge = true;
                }
                if (switch_panel_id && !skipSwitchForEndHinge && !skipSwitchForStartHinge) {
                    switchPanel('#pp-' + tab + ' .extra-panel-item', '#pp-' + tab + ' #' + switch_panel_id);
                }

                var $ppFc = $('#pp-' + tab + ' .fencing-panel-container');
                if (typeof fcEnsureGlassPoolHingeAdjacentToGate === 'function') {
                    fcEnsureGlassPoolHingeAdjacentToGate($ppFc);
                }
                if (typeof setPanelItemsID === 'function') {
                    setPanelItemsID(tab);
                }
                ProjectPlan.near_gate_spacing(tab);


                var gate_hinge_types = info.settings.gate.fields.find(function(item) {
                    return item.slug == 'gate_hinge_type';
                });
                var ght = gate_hinge_types.options.find(function(item) {
                    return item.slug == gate_hinge_type?.val;
                });

                gaps = calc.selected_values.spacing;   

                $('#pp-'+tab+' .fencing-panel-spacing-number:not(.PTP90, .PTPA, .PTW)').find('span:not(.fs-clamp)').html(gaps);

                // Set gate spacing
                if( $('#pp-'+tab+' .fencing-panel-gate').hasClass('panel-gate-left') ) {
                    $('#pp-'+tab+' .fencing-panel-spacing-number.near-gate').first().find('span:not(.fs-clamp)').html( ght?.gap?.hinge );
                    $('#pp-'+tab+' .fencing-panel-spacing-number.near-gate').last().find('span:not(.fs-clamp)').html( ght?.gap?.latch );
                }
                if( $('#pp-'+tab+' .fencing-panel-gate').hasClass('panel-gate-right') ) {
                    $('#pp-'+tab+' .fencing-panel-spacing-number.near-gate').first().find('span:not(.fs-clamp)').html(ght?.gap?.latch);
                    $('#pp-'+tab+' .fencing-panel-spacing-number.near-gate').last().find('span:not(.fs-clamp)').html(ght?.gap?.hinge);            
                }

                if (typeof fcFinalizeGlassPoolPanelLayout === 'function') {
                    fcFinalizeGlassPoolPanelLayout($ppFc, gaps, tab);
                } else {
                    if (typeof fcApplyGlassPoolPanelSpacingWidths === 'function') {
                        fcApplyGlassPoolPanelSpacingWidths(tab, gaps, $ppFc);
                    }
                    if (typeof fcNormalizeGlassPoolGateAdjacentPosts === 'function') {
                        fcNormalizeGlassPoolGateAdjacentPosts($ppFc);
                    }
                }

            }
        });    

    },

    //----------------------------------------------------------------------------------

    re_update_raked_panels: function(side, tab) {
        var center_post = FENCE.settings.item.center_point;

        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var rawStyle = custom_fence_tab[0]?.style;
        var i = normalizeFenceStyleSlug(rawStyle);

        var custom_fence = readCustomFenceSegment(tab, rawStyle),
            info = fc_data[i];

        var $ppFc = $('#pp-' + tab + ' .fencing-panel-container');

        var filtered_data = custom_fence.filter(function(item) {
            return item.control_key == 'add_step_up_panels';
        });

        var settings = filtered_data[0]?.settings;

        var cf_data = { item: i, tab: tab },
            calc = calculate_fences(cf_data);

        var center_post = FENCE.settings.item.center_point;

        $(side).each(function(k, v) {

            // Side
            var side_part = v.replace('_raked', ''),
                has_post = 'yes-post',
                center_point = FENCE.get(i, 'post');

            // Update spacing for glass fence
            if( info.panel_group == 'a' ) {
                var center_point = calc.selected_values.spacing;
            }

            var filtered_side_data = custom_fence.filter(function(item) {
                return item.control_key == side_part + '_side';
            });

            if (filtered_side_data) {

                if (filtered_side_data.length) {
                    var has_post = $(filtered_side_data[0].settings).map(function(k, item) {
                        if (item.key == side_part + '_option') {
                            return item.val;
                        }
                    }).get().join("");
                }

                if (has_post != 'yes-post' && has_post) {
                    var has_post = 'no-post ' + side_part + '-panel-post ' + has_post;
                }
            }


            // Raked
            var rakedSetting =
                typeof fcResolveStepUpRakedSetting === 'function'
                    ? fcResolveStepUpRakedSetting(custom_fence, v)
                    : (settings || []).find(function(item) {
                          return item && item.key === v;
                      });

            if (rakedSetting) {

                if (rakedSetting.val != 'none') {

                    if (side_part == 'left') {
                        panel_w = calc.left_raked.width;
                        panel_h = calc.left_raked.height;
                    } else {
                        panel_w = calc.right_raked.width;
                        panel_h = calc.right_raked.height;
                    }

                    var tpl = $('script[data-type="' + v + '-panel-' + info.panel_group + '"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, panel_h)
                        .replace(/{{panel_unit}}/gi, panel_w)
                        .replace(/{{panel_size_center}}/gi, panel_w + center_point + 'W')
                        .replace(/{{post}}/gi, has_post)
                        .replace(/{{center_post}}/gi, center_post);

                    if(panel_h) {
                        $('#pp-' + tab + ' .' + v + '-panel').html(tpl);
                    }

                }

            }

            if (side_part == 'left') {
                $('#pp-' + tab + ' .panel-post:not(.post-left):not(.post-right)').first()
                    .addClass('post-left panel-' + has_post)
                    .attr('data-key', "left_side")
                    .attr('post-side', "post_left");

                $('#pp-' + tab + ' .fencing-panel-spacing-number').first().addClass(has_post);
            }

            if (side_part == 'right') {
                $('#pp-' + tab + ' .panel-post:not(.post-left):not(.post-right)').last()
                    .addClass('post-right panel-' + has_post)
                    .attr('data-key', "right_side")
                    .attr('post-side', "post_right");

                $('#pp-' + tab + ' .fencing-panel-spacing-number').last().addClass(has_post);
            }

        });


        // Left Panel post
        var left_panel_post = $('#pp-' + tab + ' .left-panel-post.no-post span').text()
            .replace('(', '')
            .replace(')', '');

        $('#pp-' + tab + ' .left-panel-post.no-post span').text('(0)');

        // Right Panel Post
        var right_panel_post = $('#pp-' + tab + ' .right-panel-post.no-post span').text()
            .replace('(', '')
            .replace(')', '');

        $('#pp-' + tab + ' .right-panel-post.no-post span').text('(0)');

        $('#pp-' + tab + ' .no-post-swivel-bracket span')
            .after('<span class="sw sw-top">SW</span><span class="sw sw-bot">SW</span>');

        FENCE.call('load_post_options_all', custom_fence, info, tab, calc);

        FENCE.call('load_post_options_first_last_values', custom_fence, info, tab, calc);

        if (info.slug === 'glass_pool' && typeof fcFinalizeGlassPoolPanelLayout === 'function') {
            fcFinalizeGlassPoolPanelLayout($ppFc, calc.selected_values.spacing, tab);
        }

        if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(getSelectedFenceData()?.slug)) {
            SlatFence.syncSlatNoPostSpacingLabels($('#pp-' + tab).get(0));
            SlatFence.syncSlatNoPostEndCenterMarkers($('#pp-' + tab).get(0));
        }

        // Adjust side spacing value
        if( info.panel_group == 'a' ) {

        // Update spacing for glass fence
            var center_point = calc.selected_values.spacing;

            var left_side_width = HELPER.sideOptionValue('left', custom_fence, info);
            if( left_side_width < 0 ) {
                left_side_width = center_point;
            }
            $('#pp-' + tab + ' .left-panel-post.no-post span:first-child').text('('+left_side_width+')');

            var right_side_width = HELPER.sideOptionValue('right', custom_fence, info);
            if( right_side_width < 0 ) {
                right_side_width = center_point;
            }
            $('#pp-' + tab + ' .right-panel-post.no-post span:first-child').text('('+right_side_width+')');
        }

        $('#pp-' + tab + ' .fc-result').css({ 'padding': '' });

        if ($('#pp-' + tab + ' .raked-panel .fencing-raked-panel').length && $('#pp-' + tab + ' .fc-result').css('margin-top') != '70px') {
            $('#pp-' + tab + ' .fc-result').css({ 'padding-top': '40px' });
        } else {
            $('#pp-' + tab + ' .fc-result').css({ 'margin-top': '' });
        }

        $('#pp-' + tab + ' .raked-panel .fencing-panel-item').css({ 'width': 1200 * 0.10 });
    },

    //----------------------------------------------------------------------------------

    countDownTimer: function() {
        var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        // 10800000 ms = 3 hours
        setcountDownDate = new Date(Date.now() + 10800000).toLocaleString('en-US', {
            timeZone: timezone,
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        var getcountDownDate = localStorage.getItem('countdown-date');

        if (!getcountDownDate) {
            // Set the date we're counting down to
            localStorage.setItem('countdown-date', setcountDownDate);
            var getcountDownDate = localStorage.getItem('countdown-date');
        }

        var countDownDateFormat = new Date(getcountDownDate).getTime(),
            cont = 'fc-countdown-timer';

        // Update the count down every 1 second
        var x = setInterval(function() {

            // Get today's date and time
            var now = new Date().getTime();

            // Find the distance between now and the count down date
            var distance = countDownDateFormat - now;

            // Time calculations for days, hours, minutes and seconds
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Display the result in the element with id="demo"
            document.getElementById(cont).innerHTML = hours + "hrs " + minutes + "mins " + seconds + "secs ";

            // If the count down is finished, write some text
            if (distance <= 1) {
                clearInterval(x);
                localStorage.removeItem('countdown-date');
                document.getElementById(cont).innerHTML = '<div class="fc-loader-gif"></div>';
                ProjectPlan.countDownTimer();
            }

        }, 1000);
    }

    //----------------------------------------------------------------------------------

}

var FcCartImageGallery = {

    $modal: null,
    items: [],
    index: 0,

    init: function() {
        this.$modal = $('#fc-cart-image-modal');
        if (!this.$modal.length) {
            return;
        }

        var self = this;

        $(document).on('click', '.fc-cart-gallery-trigger', function(e) {
            e.preventDefault();
            var src = $(this).attr('data-fc-gallery-src');
            if (!src) {
                return;
            }
            self.openAtSrc(src);
        });

        $(document).on('keydown', '.fc-cart-gallery-trigger', function(e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            e.preventDefault();
            $(this).trigger('click');
        });

        this.$modal.on('click', '[data-fc-cart-gallery-close]', function() {
            self.close();
        });

        this.$modal.find('.fc-cart-image-modal__prev').on('click', function(e) {
            e.stopPropagation();
            self.showSlide(self.index - 1);
        });

        this.$modal.find('.fc-cart-image-modal__next').on('click', function(e) {
            e.stopPropagation();
            self.showSlide(self.index + 1);
        });

        $(document).on('keydown.fcCartGallery', function(e) {
            if (!self.$modal.hasClass('is-open')) {
                return;
            }
            if (e.key === 'Escape') {
                self.close();
            } else if (e.key === 'ArrowLeft') {
                self.showSlide(self.index - 1);
            } else if (e.key === 'ArrowRight') {
                self.showSlide(self.index + 1);
            }
        });
    },

    collectItems: function() {
        this.items = [];
        var self = this;
        $('.fc-cart-gallery-trigger').each(function() {
            var src = $(this).attr('data-fc-gallery-src');
            if (!src) {
                return;
            }
            self.items.push({
                src: src,
                title: $(this).attr('data-fc-gallery-title') || ''
            });
        });
    },

    showSlide: function(i) {
        if (!this.items.length) {
            return;
        }
        this.index = (i + this.items.length) % this.items.length;
        var item = this.items[this.index];
        this.$modal.find('.fc-cart-image-modal__img').attr('src', item.src).attr('alt', item.title);
        this.$modal.find('.fc-cart-image-modal__caption').text(item.title);
        this.$modal.find('.fc-cart-image-modal__counter').text((this.index + 1) + ' / ' + this.items.length);
        var multi = this.items.length > 1;
        this.$modal.find('.fc-cart-image-modal__prev, .fc-cart-image-modal__next').toggle(multi);
    },

    openAtSrc: function(src) {
        this.collectItems();
        var start = -1;
        for (var j = 0; j < this.items.length; j++) {
            if (this.items[j].src === src) {
                start = j;
                break;
            }
        }
        this.showSlide(start >= 0 ? start : 0);
        this.$modal.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('fc-cart-image-modal-open');
    },

    close: function() {
        this.$modal.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('fc-cart-image-modal-open');
        this.$modal.find('.fc-cart-image-modal__img').attr('src', '').attr('alt', '');
        this.$modal.find('.fc-cart-image-modal__caption').text('');
    }

};

ProjectPlan.init();
$(function() {
    FcCartImageGallery.init();
});
