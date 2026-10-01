/**
 * Premium Perforated calculation module: the per-job consumables (glue, gun, wafer screws) shared across sections.
 * Loaded by fence-scripts.php after the main fences/*.js glob and attached onto PerforatedPool below.
 */

PerforatedPoolCalc = {

    // One chemical-anchor tube per five base-plated posts, rounded up over the whole job.
    postsPerGlueTube: 5,

    /** Row 0 of a section's saved Step 2 state (`custom_fence-{i}`), or null. */
    sectionTabRow: function(sectionIndex) {
        try {
            var raw = localStorage.getItem('custom_fence-' + sectionIndex);
            var parsed = raw ? JSON.parse(raw) : null;
            return parsed && parsed[0] ? parsed[0] : null;
        } catch (e) {
            return null;
        }
    },

    isPerforatedSection: function(sectionIndex) {
        var row = this.sectionTabRow(sectionIndex);
        return !!row && (row.fence || row.style) === 'perforated_pool';
    },

    /** True when a cart orders fence (panels or a gate), not just an empty section. */
    cartBuildsFence: function(cart) {
        return (cart || []).some(function(line) {
            return (parseInt(line?.qty, 10) || 0) > 0 && /^(?:panel_options\+(?:even|full)\+\d+|gate)$/.test(String(line?.slug || ''));
        });
    },

    /** What the earlier perforated sections' saved carts order; null while one of them is missing. */
    earlierSectionTotals: function(sectionIndex) {
        var totals = { basePosts: 0, buildsFence: false };
        for (var i = 0; i < sectionIndex; i++) {
            if (!this.isPerforatedSection(i)) continue;
            var cart = null;
            try {
                cart = JSON.parse(localStorage.getItem('cart_items-' + i + '-perforated_pool'));
            } catch (e) {}
            if (!Array.isArray(cart)) return null;
            totals.basePosts += FENCES.cartItems.sumPostQtyByOpt(cart, 'opt-1');
            totals.buildsFence = totals.buildsFence || this.cartBuildsFence(cart);
        }
        return totals;
    },

    /**
     * This section's share of the per-job lines: glue is its rise in the job's running tube count, so the sections
     * sum to the job total; the gun and the screw pack ride with the first section that needs them.
     */
    pooledPerJobLinesForSection: function(sectionIndex, cart) {
        var basePosts = FENCES.cartItems.sumPostQtyByOpt(cart, 'opt-1');
        var per = this.postsPerGlueTube;
        // Carts rebuild in section order before submit; until then a missing earlier cart counts this section alone.
        var before = Number.isFinite(sectionIndex) ? this.earlierSectionTotals(sectionIndex) : null;
        if (!before) {
            before = { basePosts: 0, buildsFence: false };
        }
        return {
            glue: basePosts > 0 ? Math.ceil((before.basePosts + basePosts) / per) - Math.ceil(before.basePosts / per) : 0,
            gun: basePosts > 0 && before.basePosts === 0 ? 1 : 0,
            screws: this.cartBuildsFence(cart) && !before.buildsFence ? 1 : 0
        };
    }

};

// Attach onto PerforatedPool (fences/perforated_pool.js, loaded earlier in the same pass).
if (typeof PerforatedPool !== 'undefined' && PerforatedPool) {
    Object.keys(PerforatedPoolCalc).forEach(function (key) {
        PerforatedPool[key] = PerforatedPoolCalc[key];
    });
}
