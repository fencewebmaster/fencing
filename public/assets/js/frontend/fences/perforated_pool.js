PerforatedPool = {

    //----------------------------------------------------------------------------------

    init: function(func, a, b, c, d, e, f) {
        HELPER.call_fence_func(this, func, a, b, c, d, e, f);
    },

    //----------------------------------------------------------------------------------

    test: function() {
        console.log('PREMIUM PERFORATED:', 'PerforatedPool.test()');
    },

    //----------------------------------------------------------------------------------

    /** On top of the shared Flat Top lines: no bracket (the kit's U-channels hold the panel), the suggested consumables and the screw pack. */
    applyCartConditions: function(array, context) {
        if ($.inArray(context?.tabInfo?.[0]?.fence, ['perforated_pool']) === -1) {
            return array;
        }

        array = array.filter(function(item) {
            return item?.slug !== 'panel_options+bracket';
        });

        // Suggested, not billed, like Barr's base_plate+dynabolts: listed with an "Add to cart" button.
        var addOptional = function(slug, qty) {
            qty = parseInt(qty, 10);
            if (!qty || qty <= 0) return;
            var found = array.find(function(item) {
                return item?.slug === slug;
            });
            if (found) {
                found.optional = true;
                found.qty = found.qty || 0;
                found.suggested_qty = (found.suggested_qty || 0) + qty;
            } else {
                array.push({ slug: slug, qty: 0, optional: true, suggested_qty: qty });
            }
        };

        // Glue, gun and screws are per job, so each section takes its share (fences/calc/perforated_pool.js).
        var perJob = this.pooledPerJobLinesForSection(parseInt(context?.tabIndex, 10), array);

        addOptional('cement_in+cement', FENCES.cartItems.sumPostQtyByOpt(array, 'opt-2'));
        addOptional('chem_achor+glue', perJob.glue);
        addOptional('chem_achor+glue_gun', perJob.gun);

        // Billed, as the old planner did; products.csv has it OFF for Black, whose panel kits already include black screws.
        if (perJob.screws > 0) {
            array.push({ slug: 'panel_options+wafer_screws', qty: perJob.screws });
        }

        return array;
    },

    //----------------------------------------------------------------------------------

    /** The shared Step 3 buttons, less Panel Options while there is only one: nothing to choose, and the calc takes it as default. */
    update_custom_fence_style_item: function() {
        FENCE.update_custom_fence_style_item();
        if (this.panelOptionCount() === 1) {
            $(FENCES.el.fencingPanelControls).find('#btn-panel_options').remove();
        }
    },

    /** How many panel options the style offers, across its Panel Options fields. */
    panelOptionCount: function() {
        var fields = getSelectedFenceData()?.data?.settings?.panel_options?.fields || [];
        return fields.reduce(function(count, field) {
            return count + (Array.isArray(field?.options) ? field.options.length : 0);
        }, 0);
    },

    //----------------------------------------------------------------------------------

    /** Sections that follow a Perforated section share its end post, as the old planner did, so they start with No Post. */
    set_cutom_fence_data: function(opts) {
        var healed = this.healSharedCornerPost();
        // Read before the shared seed runs: it stamps this style's calculate value, which marks the section as not new.
        var tab = this.sharedCornerPostTab();
        FENCE.set_cutom_fence_data(opts);
        if (tab !== null) {
            this.seedSharedCornerPost(tab);
        } else if (healed) {
            this.afterSharedCornerHealed();
        }
    },

    /** Deleting the section before it left a seeded shared corner with nothing to stand on: drop it, so the post is back. */
    healSharedCornerPost: function() {
        try {
            var fd = getSelectedFenceData();
            var tab = parseInt(fd.tab, 10);
            if (fd.slug !== 'perforated_pool' || !Number.isFinite(tab)) {
                return false;
            }
            var segment = readCustomFenceSegment(tab, 'perforated_pool');
            var seeded = segment.some(function(row) {
                return row?.control_key === 'left_side' && row.sharedCorner === true;
            });
            if (!seeded || (tab > 0 && this.isPerforatedSection(tab - 1))) {
                return false;
            }
            localStorage.setItem('custom_fence-' + tab + '-perforated_pool', JSON.stringify(segment.filter(function(row) {
                return row?.control_key !== 'left_side';
            })));
            return true;
        } catch (e) {
            return false;
        }
    },

    /** The steps fcSelectPost runs when a customer changes a post end, then say why the post came back. */
    afterSharedCornerHealed: function() {
        try {
            var tab = parseInt(getSelectedFenceData().tab, 10);
            FENCE.call('updateOverallPosts');
            updateOverAllLength({ removePost: 1 });
            var fd = getSelectedFenceData();
            if (FENCE.isGateMinOalStyle(fd.slug)) {
                FENCE.syncGateOverallOnPostChange(fd, { persist: true });
            }
            btnCalculate();

            if (typeof popupToast === 'function') {
                popupToast(
                    'Corner post',
                    '<b>Section ' + (tab + 1) + '</b> starts with a post again: there is no Perforated section before it to share a corner post with.',
                    'CORNER'
                );
            }
        } catch (e) {}
    },

    /** The selected section if it is new to Perforated, follows a Perforated section and has no left side picked; else null. */
    sharedCornerPostTab: function() {
        try {
            var fd = getSelectedFenceData();
            var tab = parseInt(fd.tab, 10);
            if (fd.slug !== 'perforated_pool' || !(tab > 0)) {
                return null;
            }
            // Calculated as Perforated already: a saved or reloaded section keeps the ends it was quoted with.
            var calculated = fd.tabInfo?.[0]?.calculateValueByStyle?.perforated_pool;
            if (calculated !== undefined && calculated !== null && calculated !== '') {
                return null;
            }
            var picked = (fd.info || []).some(function(row) {
                return row?.control_key === 'left_side';
            });
            return !picked && this.isPerforatedSection(tab - 1) ? tab : null;
        } catch (e) {
            return null;
        }
    },

    /** Store the row the Edit Left Side drawer writes for No Post, redraw, and tell the customer why. */
    seedSharedCornerPost: function(tab) {
        try {
            var segment = readCustomFenceSegment(tab, 'perforated_pool');
            var postRow = segment.find(function(row) {
                return row?.control_key === 'post_options';
            });
            var postOpt = postRow?.settings?.find(function(item) {
                return item?.key === 'post_option';
            })?.val || 'opt-1';

            // sharedCorner marks it as ours: the drawer rewrites the row without it, so a customer's own No Post is never undone.
            segment.push({
                id: 'perforated_pool',
                control_key: 'left_side',
                sharedCorner: true,
                settings: [
                    { key: 'left_option', val: 'no-post', tag: 'div', type: 'range_option' },
                    { key: 'post_option', val: postOpt, tag: 'div', type: 'image_option' }
                ]
            });
            localStorage.setItem('custom_fence-' + tab + '-perforated_pool', JSON.stringify(segment));

            FENCE.call('update_custom_fence_tab');

            if (typeof popupToast === 'function') {
                popupToast(
                    'Corner post',
                    '<b>Section ' + (tab + 1) + '</b> starts with <b>No Post</b>: it shares the corner post at the end of Section ' +
                        tab + '. If this run stands on its own, choose <b>Yes Post</b> under <b>Edit Left Side</b>.',
                    'CORNER'
                );
            }
        } catch (e) {}
    }

    //----------------------------------------------------------------------------------

}
