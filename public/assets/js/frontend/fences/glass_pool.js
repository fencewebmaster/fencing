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

                array.forEach(function(item) {
                    if (item.slug == 'gate') {
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