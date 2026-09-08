GlassPool = {

    //----------------------------------------------------------------------------------

    init: function(func, a, b, c, d, e, f) {
        HELPER.call_fence_func(this, func, a, b, c, d, e, f);
    },

    //----------------------------------------------------------------------------------

    test: function() {
        console.log('GLASS POOL:', 'Glass.pool()');
    },

    //----------------------------------------------------------------------------------

    get: function(fence, key) {

        FENCE.call('test');
        return FENCE.settings[fence][key];
    },

    //----------------------------------------------------------------------------------

    applyCartConditions: function(array, context) {
        var fence = context?.tabInfo?.[0]?.fence;
        if ($.inArray(fence, ['glass_pool']) === -1) {
            return array;
        }

        var calc = context?.calc || calculate_fences();
        var fenceInfo = context?.fenceInfo || [];

        array = array.filter(function(item) {
            return $.inArray(item.slug, ['base_plate+dynabolts', 'base_plate+post_cover', 'panel_options+bracket']) === -1;
        });

        array.forEach(function(item) {
            if (item.slug.includes('panel_post+')) {
                item.qty = $('.fencing-panel-spigot').length;
            }
        });

        if (fenceInfo.length) {
            var gate_data = fenceInfo.filter(function(item) {
                return item.control_key == 'gate';
            });

            /* Read the gate through the same defaults the planner applies on render. The plan page and
               a quote reload build this cart straight from stored rows that a Step 2 Gate ONLY leaves
               without hinge type or width, and the slugs below concatenated those blanks into
               gate+undefined+undefined - a line no product matches, so the glass leaf itself was dropped. */
            if (gate_data[0] && typeof fcGlassPoolEnsureDefaultGateFields === 'function') {
                var glassInfo = typeof fc_data !== 'undefined' && fc_data ? fc_data[fence] : null;
                if (glassInfo) {
                    var resolvedGate = JSON.parse(JSON.stringify(gate_data[0]));
                    resolvedGate.settings = resolvedGate.settings || {};
                    resolvedGate.settings.fields = fcGlassPoolEnsureDefaultGateFields(
                        glassInfo,
                        resolvedGate.settings.fields,
                        resolvedGate.settings
                    );
                    gate_data = [resolvedGate];
                }
            }

            var gate_hinge_panel = gate_data[0]?.settings?.fields?.find(function(item) {
                return item.key == 'gate_hinge_panel_width';
            });

            var gate_hinge_type = gate_data[0]?.settings?.fields?.find(function(item) {
                return item.key == 'gate_hinge_type';
            });

            if (calc?.gate?.count) {
                array.forEach(function(item) {
                    if (item.slug.includes('panel_options+')) {
                        item.qty = item.qty - 1;
                    }
                });

                /* Gate ONLY counts as well, though it has no hinge panel to hang off: the gate still
                   swings on the same hinges, so the hardware is still on the job. The slug is built
                   from the hinge type alone - the panel width only feeds the gate_hinge_panel line
                   below - and the gate row is read through fcGlassPoolEnsureDefaultGateFields above,
                   so a Step 2 Gate ONLY that never set a type still resolves to the default rather
                   than to gate_hinge+undefined. Read off the stored row, not the live planner, so a
                   quote reload and the project plan bill it the same way. */
                if (calc?.gate_hinge_panel?.count || gate_data[0]?.settings?.gateOnly === true) {
                    array.push({
                        slug: 'gate_hinge+' + gate_hinge_type?.val,
                        qty: 1
                    });
                }

                $('.fencing-panel-item').each(function(i) {
                    if (!$(this).hasClass('fencing-panel-gate')) return;

                    var idx = i + 1;
                    if ((idx == $('.fencing-panel-item').length && $('.panel-gate-left').length) ||
                        (idx == 1 && $('.panel-gate-right').length)) {
                        array.push({ slug: 'latch_glass_to_wall_post', qty: 1 });
                    } else {
                        array.push({ slug: 'latch_glass_to_glass', qty: 1 });
                    }
                });

                var gate_panel = gate_data[0]?.settings?.fields?.find(function(item) {
                    return item.key == 'gate_width';
                });

                /* The planner names this node `gate`; the project plan appends the fence height, and
                   glass has no height form so it lands as `gate+1`. Matching only the bare slug left
                   the plan cart holding a line no product matches, and the leaf vanished there even
                   though the planner cart was right. */
                array.forEach(function(item) {
                    if (item.slug == 'gate' || /^gate\+\d+$/.test(item.slug)) {
                        item.slug = 'gate+' + gate_hinge_type?.val + '+' + gate_panel?.val;
                    }
                });
            }

            array.forEach(function(item) {
                if (item.slug.includes('gate+kit') && calc?.gate_hinge_panel?.count) {
                    item.slug = 'gate_hinge_panel+' + gate_hinge_type?.val + '+' + gate_hinge_panel?.val;
                }
            });
        }

        var panels = document.querySelectorAll('.fencing-panel-container [data-key="panel_options"]');
        var panelGroups = {};

        panels.forEach(function(panel) {
            var size = panel.getAttribute('data-panel-size');
            if (!panelGroups[size]) {
                panelGroups[size] = { slug: 'panel_options+1200x' + size.replace('W', ''), qty: 0 };
            }
            panelGroups[size].qty++;
        });

        var result = Object.values(panelGroups);
        var panel_options = FENCES.cartItems.remove_item(array, 'panel_options+even');
        array = result.concat(panel_options);

        if (calc?.gate_hinge_panel?.count) {
            array = array.filter(function(item) {
                return item.slug != 'panel_options+1200x' + gate_hinge_panel?.val;
            });
        }

        /* Panel-to-panel clamps: one per glass-to-glass junction, and only once the solved gap
           sits inside a clamp's range - the diagram used to draw clamp bars over 13mm gaps that
           no clamp fits. Own try/catch: this hook is called directly and the per-tab cart refresh
           wraps the whole build in one, so a throw here would drop the entire section cart rather
           than just this line. */
        try {
            var clamp = GlassPool.clampStatus(calc, context);
            if (clamp && clamp.selected && clamp.status === 'ok' && clamp.slug && clamp.junctions > 0) {
                array.push({ slug: clamp.slug, qty: clamp.junctions });
            }
        } catch (eClamp) {}

        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Junctions a panel-to-panel clamp sits on, counted from the diagram the cart is scoped to.
     * The two run ends carry side_panel_spacing hardware, the gate's own hinge/latch strips
     * (near-gate) and the PTP90/PTPA/PTW strips are fixed spacings, so only the inner solver
     * strips count. Set semantics over nodes[1..n-2] rather than "total - 2 - nearGateCount":
     * with the gate at an end one near-gate strip IS the end strip and was subtracted twice.
     * Mirrors the exclusion set fcApplyGlassPoolUniformGapLabels labels.
     */
    clampJunctionCount: function(context) {
        var tabIndex = context && context.tabIndex != null ? context.tabIndex : 0;
        var root = null;
        if (typeof FENCES !== 'undefined' && FENCES.cartItems && typeof FENCES.cartItems.getProcessScopeRoot === 'function') {
            root = FENCES.cartItems.getProcessScopeRoot(tabIndex);
        }
        root = root || document;

        var nodes = root.querySelectorAll('.fencing-panel-spacing-number');
        if (nodes.length < 3) {
            return 0;
        }

        var junctions = 0;
        for (var n = 1; n < nodes.length - 1; n++) {
            var classes = nodes[n].classList;
            if (classes.contains('near-gate') || classes.contains('PTP90') || classes.contains('PTPA') || classes.contains('PTW')) {
                continue;
            }
            junctions++;
        }
        return junctions;
    },

    //----------------------------------------------------------------------------------

    /**
     * calc.selected_values.clamp (pure, from the solver) plus the DOM junction count. A layout
     * with nothing to clamp - single panel, gate-only, zero-panel runs - reads as 'none' so no
     * status line, prompt or cart line is produced for it.
     */
    clampStatus: function(calc, context) {
        var base = calc && calc.selected_values && calc.selected_values.clamp
            ? calc.selected_values.clamp
            : { selected: false, enforce: false, status: 'none', size: null, slug: '', gapMm: 0 };

        var st = Object.assign({}, base, { junctions: GlassPool.clampJunctionCount(context) });
        if (st.selected && st.junctions === 0) {
            st.status = 'none';
        }
        return st;
    },

    //----------------------------------------------------------------------------------

    clampMessages: {
        below_min:     'Panel gap <b>{{gap}}mm</b> is below the <b>{{min}}mm</b> minimum for a panel-to-panel clamp &mdash; no clamps added. <a href="#" class="js-fc-clamp-gap-prompt">Adjust gap</a>',
        below_min_fixed:'No panel layout at this Overall Length gives the <b>{{min}}mm</b> minimum gap for a panel-to-panel clamp &mdash; no clamps added. Change the Overall Length or Max Panel Size.',
        above_max:     'Panel gap <b>{{gap}}mm</b> is above the <b>{{max}}mm</b> maximum for a panel-to-panel clamp &mdash; no clamps added. Reduce <b>Max Panel Spacing</b> (Edit Spacing) or adjust the Overall Length.',
        no_size:       'No panel-to-panel clamp is configured for a <b>{{gap}}mm</b> gap &mdash; no clamps added.',
        adjusted_title:'Panel gap adjusted',
        adjusted:      'Panel widths were recalculated so the panel gap is <b>{{gap}}mm</b> (was <b>{{from}}mm</b>) &mdash; <b>{{title}}</b> panel-to-panel clamps will be included.'
    },

    /* "{tab}|{overall}|{gapMm}" of the last prompt shown/declined; `fixedKey` is the state whose
       enforced dry-run found no layout, `from` the gap the toast quotes, `timer` the pending
       show so two renders per tile click schedule one prompt. `armed` is set by a user action
       (tile click, Calculate, the status-line link) and consumed by the next render: every
       post-persist render reaches maybePromptClampGap, including tab switches, a quote reload
       and the sequential cart rebuild that clicks through every section before submit - and
       that one raised the dialog for a section no longer on screen. `tab`/`slug` pin the section
       the open dialog belongs to. */
    clampPromptState: { key: '', fixedKey: '', from: 0, timer: null, armed: false, armTimer: null, tab: null, slug: '' },

    /** A user action happened that may warrant the gap prompt on the render it triggers. */
    armClampPrompt: function() {
        GlassPool.clampPromptState.armed = true;
        // Renders are synchronous inside the gesture's own task, so an arm still standing on the
        // next task belongs to a gesture that rendered nothing (a quantity stepper, a field the
        // solver ignores). Left standing it would be spent by the next scripted render instead.
        if (GlassPool.clampPromptState.armTimer) {
            clearTimeout(GlassPool.clampPromptState.armTimer);
        }
        GlassPool.clampPromptState.armTimer = setTimeout(function() {
            GlassPool.clampPromptState.armTimer = null;
            GlassPool.clampPromptState.armed = false;
        }, 0);
    },

    //----------------------------------------------------------------------------------

    /** {{token}} fill, the way FENCE.settings.message consumers do it. */
    clampMessage: function(key, vars) {
        var tpl = GlassPool.clampMessages[key] || '';
        Object.keys(vars || {}).forEach(function(name) {
            tpl = tpl.replace(new RegExp('\\{\\{' + name + '\\}\\}', 'gi'), String(vars[name]));
        });
        return tpl;
    },

    /** The state a prompt was asked for - a fresh length or gap asks again, the same one does not. */
    clampPromptKey: function(fd, st) {
        var overall = parseInt(String(fd && fd.mbn != null ? fd.mbn : '').replace(/,/g, ''), 10);
        if (!Number.isFinite(overall) && fd && fd.tabInfo && fd.tabInfo[0]) {
            overall = parseInt(String(fd.tabInfo[0].calculateValue != null ? fd.tabInfo[0].calculateValue : ''), 10);
        }
        return (fd ? fd.tab : '') + '|' + (Number.isFinite(overall) ? overall : '') + '|' + (st ? st.gapMm : '');
    },

    /** Planner: #pp-0 is the container itself; project plan: #pp-{tab} wraps one. */
    clampSectionRoot: function(tab) {
        var $pp = $('#pp-' + (tab != null && tab !== '' ? tab : 0));
        if ($pp.length) {
            return $pp.hasClass('fencing-panel-container') ? $pp : $pp.find('.fencing-panel-container').first();
        }
        return $('.fencing-panel-container').first();
    },

    //----------------------------------------------------------------------------------

    /**
     * The standing "adjust the gap for clamps" flag, as its own control row (control_key
     * panel_clamps). calc.js reads the panel width out of panel_options_custom by position and
     * mergeSettings() re-orders that row on every modal save, so a flag kept inside it floated
     * to index 0 and was read as the width. update_custom_fence only replaces the row of the
     * modal being saved, so this row survives every other save (add_step_up_panels precedent).
     * Returns true when the stored blob changed.
     */
    setClampGapAdjust: function(on, fd) {
        if (!fd || fd.tab === undefined || fd.tab === null || !fd.slug) {
            return false;
        }

        var controlKey = GlassPool.CLAMP_CONTROL_KEY || 'panel_clamps';
        var adjustKey = GlassPool.CLAMP_ADJUST_KEY || 'gap_adjust';
        var storageKey = 'custom_fence-' + fd.tab + '-' + fd.slug;

        var cf = [];
        try {
            var raw = localStorage.getItem(storageKey);
            cf = raw ? JSON.parse(raw) : [];
        } catch (eRead) {
            cf = [];
        }
        if (!Array.isArray(cf)) {
            cf = [];
        }

        var existing = cf.filter(function(item) {
            return item && item.control_key === controlKey;
        });
        var isOn = existing.some(function(row) {
            return (row.settings || []).some(function(setting) {
                return setting && setting.key === adjustKey && (setting.val === '1' || setting.val === 1);
            });
        });

        if (!on && !existing.length) {
            return false;
        }
        if (on && existing.length === 1 && isOn) {
            return false;
        }

        var next = cf.filter(function(item) {
            return !(item && item.control_key === controlKey);
        });
        if (on) {
            next.push({
                id: fd.slug,
                control_key: controlKey,
                settings: [{ key: adjustKey, val: '1' }]
            });
        }

        try {
            localStorage.setItem(storageKey, JSON.stringify(next));
        } catch (eWrite) {
            return false;
        }
        return true;
    },

    //----------------------------------------------------------------------------------

    /** Enforced solve without persisting the flag: what Adjust Gap would produce (whole calc). */
    clampDryRun: function(fd) {
        return calculate_fences({ tab: fd.tab, item: fd.slug, clampEnforce: true }) || null;
    },

    /**
     * "3 x 800mm" for the dialog: the panel count is what the adjustment actually costs the
     * customer (two spigots and a clamp apiece), so it is shown beside the gap rather than left
     * for them to discover on the diagram. Mixed widths fall back to the count alone.
     */
    clampPanelSummary: function(calc) {
        var long = (calc && calc.long_panel && calc.long_panel.count) | 0;
        var short = (calc && calc.short_panel && calc.short_panel.count) | 0;
        var total = long + short;
        if (total <= 0) {
            return '—';
        }
        if (short > 0 && calc.short_panel.length !== calc.long_panel.length) {
            return total + ' panels';
        }
        return total + ' × ' + (calc.long_panel.length | 0) + 'mm';
    },

    /**
     * Glass elements in a calc - regular panels, the hinge panel, step-up panels. Two or more
     * means at least one panel-to-panel junction exists. Read from the calc, not the DOM: the
     * dry-run is never rendered.
     */
    clampGlassElementCount: function(calc) {
        if (!calc) {
            return 0;
        }
        // Step-up (raked) panels are deliberately not counted: their junctions carry the
        // PTP90/PTPA/PTW side hardware that clampJunctionCount excludes, so counting them
        // offered an adjustment for a run whose only junction is never billed.
        var n = 0;
        n += (calc.long_panel && calc.long_panel.count) | 0;
        n += (calc.short_panel && calc.short_panel.count) | 0;
        n += (calc.gate_hinge_panel && calc.gate_hinge_panel.count) | 0;
        return n;
    },

    //----------------------------------------------------------------------------------

    /**
     * Planner only. Offers the gap adjustment when Yes Clamps is selected, no adjustment is
     * standing and the solved gap is under the smallest clamp's minimum - but only after the
     * enforced solve has been dry-run and succeeded at this Overall Length: offering Adjust Gap
     * for a run the enforced solver cannot lay out just sent the customer through the auto-fit
     * to a different length. Deduped on clampPromptState.key so the two renders per tile click
     * (update_custom_fence, then btnCalculate) and a declined prompt do not ask again.
     */
    maybePromptClampGap: function(calc, fd) {
        if (!fd || !document.querySelector('.fc-planner-page')) {
            return;
        }
        var modalEl = document.getElementById('fc-clamp-gap-confirm');
        if (!modalEl || typeof window.bootstrap === 'undefined' || !window.bootstrap.Modal) {
            return;
        }

        // One chance per user action; a render nobody asked for (tab switch, reload, the
        // pre-submit cart rebuild) only refreshes the status line, whose link re-offers this.
        if (!GlassPool.clampPromptState.armed) {
            return;
        }
        GlassPool.clampPromptState.armed = false;

        var st = GlassPool.clampStatus(calc, { tabIndex: fd.tab });
        if (!st.selected || st.enforce || st.status !== 'below_min' || !(st.junctions > 0)) {
            return;
        }

        var key = GlassPool.clampPromptKey(fd, st);
        if (key === GlassPool.clampPromptState.key) {
            return;
        }
        GlassPool.clampPromptState.key = key;
        GlassPool.clampPromptState.fixedKey = '';

        var dry = null;
        try {
            dry = GlassPool.clampDryRun(fd);
        } catch (eDry) {
            dry = null;
        }
        var dryValues = dry && dry.selected_values ? dry.selected_values : null;
        var dryClamp = dryValues && dryValues.clamp ? dryValues.clamp : null;

        // The enforced solve can also "succeed" by collapsing the run to a single panel (the
        // rescue path), which the gap-only classifier reads as ok: 1039mm at 30mm spacing went
        // 2x500 @ 13 -> 1x1000 @ 19.5. Nothing to clamp there, so it is not an adjustment to offer.
        if (
            !dryValues || dryValues.message || !dryClamp || dryClamp.status !== 'ok' || !dryClamp.size ||
            GlassPool.clampGlassElementCount(dry) < 2
        ) {
            GlassPool.clampPromptState.fixedKey = key;
            GlassPool.renderClampLine($('.fc-clamp-message'), st, 'below_min', fd);
            return;
        }

        var $modal = $(modalEl);
        $modal.find('.js-fc-clamp-gap-current').text(st.gapMm);
        $modal.find('.js-fc-clamp-gap-min').text(st.minGapMm);
        $modal.find('.js-fc-clamp-gap-after').text(dryClamp.gapMm);
        $modal.find('.js-fc-clamp-panels-after').text(GlassPool.clampPanelSummary(dry));
        $modal.find('.js-fc-clamp-size-after').text(
            dryClamp.size.title + ' (' + dryClamp.size.minGapMm + 'mm - ' + dryClamp.size.maxGapMm + 'mm Gap)'
        );
        GlassPool.clampPromptState.from = st.gapMm;
        GlassPool.clampPromptState.tab = fd.tab;
        GlassPool.clampPromptState.slug = fd.slug;

        // FCModal.close() fades the tile modal out over 200ms; opening on top of it stacked two
        // backdrops. Re-checked when the timer fires: a render in between may have moved the gap,
        // or the selected section may have changed under a programmatic tab click.
        if (GlassPool.clampPromptState.timer) {
            clearTimeout(GlassPool.clampPromptState.timer);
        }
        GlassPool.clampPromptState.timer = setTimeout(function() {
            GlassPool.clampPromptState.timer = null;
            if ($('.fencing-tab.fencing-tab-selected').index() !== fd.tab) {
                return;
            }
            var again = null;
            try {
                again = GlassPool.clampStatus(calculate_fences({ tab: fd.tab, item: fd.slug }), { tabIndex: fd.tab });
            } catch (eAgain) {
                return;
            }
            if (!again.selected || again.enforce || again.status !== 'below_min' || again.gapMm !== st.gapMm) {
                return;
            }
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }, 350);
    },

    //----------------------------------------------------------------------------------

    /**
     * Adjust Gap: persist the flag, then re-run Calculate once the dialog has finished closing
     * (native hidden.bs.modal listener + 800ms backstop + a yielded turn, the fcConfirmProceed
     * pattern - Bootstrap is still clearing its backdrop when the event fires). The toast never
     * says "added to your cart": btnCalculate does not rebuild the glass cart, the next cart
     * rebuild does.
     */
    applyClampGapAdjust: function() {
        var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (!fd || !fd.data || fd.data.panel_group !== 'a') {
            return;
        }
        // The dialog was raised for one section; if the selection has moved since (a scripted
        // tab click), writing the flag onto whatever is selected now would adjust the wrong run.
        if (fd.tab !== GlassPool.clampPromptState.tab || fd.slug !== GlassPool.clampPromptState.slug) {
            return;
        }

        GlassPool.setClampGapAdjust(true, fd);
        GlassPool.clampPromptState.key = '';
        GlassPool.clampPromptState.fixedKey = '';

        var from = GlassPool.clampPromptState.from;
        var modalEl = document.getElementById('fc-clamp-gap-confirm');

        var announce = function() {
            var st;
            try {
                st = GlassPool.clampStatus(calculate_fences({ tab: fd.tab, item: fd.slug }), { tabIndex: fd.tab });
            } catch (eStatus) {
                return;
            }
            if (st.status === 'ok' && st.size && typeof popupToast === 'function') {
                popupToast(
                    GlassPool.clampMessages.adjusted_title,
                    GlassPool.clampMessage('adjusted', { gap: st.gapMm, from: from, title: st.size.title }),
                    'GP-CLAMP'
                );
            }
        };

        var run = function() {
            if (typeof btnCalculate === 'function') {
                btnCalculate();
            }
            // The planner renders behind a double requestAnimationFrame; the junction count the
            // status reads comes from that render.
            if (typeof fcWhenPlannerSectionRendered === 'function') {
                fcWhenPlannerSectionRendered(fd.tab, announce);
            } else {
                setTimeout(announce, 0);
            }
        };

        GlassPool.whenClampDialogClosed(run);
    },

    //----------------------------------------------------------------------------------

    /**
     * Manual Adjustment: hand the customer the Max Panel Spacing control rather than solving
     * for them. Nothing is written - changing the spacing re-runs the normal Calculate flow,
     * and if the gap is still under the minimum the line (and a fresh prompt) says so.
     */
    openMaxPanelSpacing: function() {
        GlassPool.whenClampDialogClosed(function() {
            var btn = document.getElementById('btn-edit_spacing');
            if (btn) {
                btn.click();
            }
        });
    },

    /**
     * Run `after` once the gap dialog has finished closing. Bootstrap is still tearing down its
     * backdrop when hidden.bs.modal fires, and both footer actions open or re-render something
     * on top of it, so the work is yielded a turn. The timeout is the backstop for a dialog
     * that never emits the event (the fcConfirmProceed pattern in core/events.js).
     */
    whenClampDialogClosed: function(after) {
        var modalEl = document.getElementById('fc-clamp-gap-confirm');
        if (!modalEl) {
            after();
            return;
        }

        var done = false,
            finish = function() {
                if (done) {
                    return;
                }
                done = true;
                modalEl.removeEventListener('hidden.bs.modal', finish);
                setTimeout(after, 0);
            };

        modalEl.addEventListener('hidden.bs.modal', finish);
        setTimeout(finish, 800);
    },

    //----------------------------------------------------------------------------------

    /**
     * From fcSelectPost BEFORE update_custom_fence persists the tile, so the render that save
     * triggers already sees the final state. A fresh selection always asks again; No Clamps
     * clears the standing adjustment so the solver is back to the customer's spacing.
     */
    onPanelClampOptionChanging: function(slug, fd) {
        GlassPool.clampPromptState.key = '';
        GlassPool.clampPromptState.fixedKey = '';
        GlassPool.armClampPrompt();
        if (slug !== (GlassPool.CLAMP_OPTION_YES || 'opt-2')) {
            GlassPool.setClampGapAdjust(false, fd);
        }
    },

    //----------------------------------------------------------------------------------

    /** The .fc-clamp-message line for one status; empty for nothing-to-report states. */
    renderClampLine: function($line, st, status, fd) {
        if (!$line || !$line.length) {
            return;
        }
        $line.removeClass('fencing-panel-clamp-msg--warn');

        // 'ok' says nothing the customer cannot already see: the clamps are drawn on the
        // diagram and listed in the materials list. Only the states that add NO clamp get a
        // line, because those need explaining.
        if (!st || !st.selected || status === 'none' || status === 'invalid' || status === 'ok') {
            $line.html('');
            return;
        }

        var vars = {
            gap: st.gapMm,
            min: st.minGapMm,
            max: st.maxGapMm,
            qty: st.junctions,
            title: st.size ? st.size.title : ''
        };

        var copyKey = status;
        if (status === 'below_min' && GlassPool.clampPromptState.fixedKey && GlassPool.clampPromptState.fixedKey === GlassPool.clampPromptKey(fd, st)) {
            copyKey = 'below_min_fixed';
        }
        $line.addClass('fencing-panel-clamp-msg--warn').html(GlassPool.clampMessage(copyKey, vars));
    },

    /**
     * Dims the drawn clamp bars while the gap is outside every clamp's range (reused by p2.js),
     * after putting the bars where the billed junctions are. load_post_options_all appends a
     * bar to every strip and renderLoad strips the first/last, but the gate flow then moves the
     * hinge panel next to the gate (fcEnsureGlassPoolHingeAdjacentToGate) and its strip travels
     * with it - the stripped end strip landed second and a bar sat on the run's end. Harmless
     * while the bars were decoration; now they mark a clamp the cart bills, so they follow the
     * final order: none on the ends, one on every inner strip (near-gate ones stay CSS-hidden).
     */
    applyClampDiagramState: function($root, status) {
        if (!$root || !$root.length) {
            return;
        }
        GlassPool.syncClampBars($root);
        $root.toggleClass('fc-clamps-unsupported', status === 'below_min' || status === 'above_max' || status === 'no_size');
    },

    /**
     * Bars follow the strips' FINAL order: none on the two ends, one on every inner strip.
     * Idempotent; also run from fcFinalizeGlassPoolPanelLayout, the pass that moves the hinge
     * panel (and its strip) next to the gate after the bars were placed and the ends stripped.
     */
    syncClampBars: function($root) {
        var rootEl = $root && $root.length ? $root[0] : $root;
        if (!rootEl || !rootEl.querySelectorAll) {
            return;
        }
        var strips = rootEl.querySelectorAll('.fencing-panel-spacing-number');
        for (var i = 0; i < strips.length; i++) {
            var strip = strips[i];
            var bar = strip.querySelector('.fs-clamp');
            // Ends carry side_panel_spacing hardware; PTP90/PTPA/PTW strips are fixed spacings.
            // Both are excluded from the billed junction count, so neither may draw a bar.
            var billable = i > 0 && i < strips.length - 1 &&
                !strip.classList.contains('PTP90') &&
                !strip.classList.contains('PTPA') &&
                !strip.classList.contains('PTW');
            if (!billable) {
                if (bar) {
                    bar.remove();
                }
            } else if (!bar && /(^|\s)panel-opt-/.test(strip.className)) {
                strip.insertAdjacentHTML('beforeend', '<span class="fs-clamp"></span>');
            }
        }
    },

    /**
     * Post-persist render site: the status line, the diagram modifier and (planner) the prompt.
     * `calc === null` or a non-glass fence clears everything, so a style switch away from glass
     * does not leave the last glass line under a Flat Top diagram.
     */
    applyClampMessage: function(calc, fd) {
        var tab = fd && fd.tab != null ? fd.tab : 0;
        var $root = GlassPool.clampSectionRoot(tab);
        var $line = $('.fc-clamp-message');

        var st = null;
        if (calc && fd && fd.data && fd.data.panel_group === 'a') {
            st = GlassPool.clampStatus(calc, { tabIndex: tab });
        }

        var status = st && st.selected ? st.status : 'none';
        if (status === 'ok' && !st.size) {
            status = 'none';
        }

        // Only a glass section owns clamp bars; a non-glass render clears the line and leaves
        // that section's diagram alone rather than walking strips it does not own.
        if (st) {
            GlassPool.applyClampDiagramState($root, status);
        }
        GlassPool.renderClampLine($line, st, status, fd);

        if (status === 'below_min' && st && !st.enforce) {
            GlassPool.maybePromptClampGap(calc, fd);
            return;
        }
        // One render per gesture: a Calculate that lands in range must not leave the prompt
        // armed for whichever section the next scripted tab switch happens to render.
        GlassPool.clampPromptState.armed = false;
    },

    //----------------------------------------------------------------------------------

}