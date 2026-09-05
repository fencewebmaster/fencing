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

                if (calc?.gate_hinge_panel?.count) {
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

        return array;
    },

    //----------------------------------------------------------------------------------

}