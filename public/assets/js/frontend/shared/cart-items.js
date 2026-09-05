FENCES = FENCES || {};

FENCES.cartItems = {

    //----------------------------------------------------------------------------------

    item: {
        gateKit1: {
            slug: 'gate+kit',
            qty: 1
        },
        gateKit2: {
            slug: 'gate+converter',
            qty: 1
        },
        gateKit3: {
            slug: 'gate+hinges',
            qty: 1
        },
        gateKit4: {
            slug: 'gate+latch',
            qty: 1
        },
        swivelBrackets: {
            slug: 'swivel_brackets',
            qty: 2,
            className: 'no-post-swivel-bracket'
        }
    },

    //----------------------------------------------------------------------------------

    /**
     * Main Slat only: use catalog post accessory slugs (products.csv) instead of generic base_plate+*.
     * Slat Infill has no posts in the FSQ BOM — do not add slat_post+base_plate_* here.
     */
    isSlatPostStyleFence: function(context) {
        if (!context) {
            return false;
        }
        var raw = context.fenceSlug || '';
        if (!raw && context.tabInfo && context.tabInfo[0]) {
            raw = context.tabInfo[0].fence || context.tabInfo[0].style || '';
        }
        raw = String(raw || '');
        var s = typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : raw;
        if (s === 'slat_fence_infill') {
            return false;
        }
        return s === 'slat';
    },

    /** Barr only — optional base-plate dynabolts are listed but not in cart until the user adds them. */
    isBarrFence: function(context) {
        if (!context) {
            return false;
        }
        var raw = context.fenceSlug || '';
        if (!raw && context.tabInfo && context.tabInfo[0]) {
            raw = context.tabInfo[0].fence || context.tabInfo[0].style || '';
        }
        var s = typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : String(raw || '');
        return s === 'barr';
    },

    isFlatTopFence: function(context) {
        if (!context) {
            return false;
        }
        var raw = context.fenceSlug || '';
        if (!raw && context.tabInfo && context.tabInfo[0]) {
            raw = context.tabInfo[0].fence || context.tabInfo[0].style || '';
        }
        var s = typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(raw) : String(raw || '');
        return s === 'flat_top';
    },

    /**
     * Flat Top: a custom gate is a panel cut to width on the Gate Converter (the only stocked gate is
     * the 970 STD), so it needs a donor panel; the STD gate is a manufactured item and does not.
     * Read off the converter line the gate-kit rules have already produced, or the gate settings.
     */
    flatTopGateNeedsDonorPanel: function(context, array) {
        if (!FENCES.cartItems.isFlatTopFence(context)) {
            return false;
        }
        if ((array || []).some(function(item) {
            return /^gate\+converter(?:\+\d+)*$/.test(String(item?.slug || ''));
        })) {
            return true;
        }
        var gate_data = (context?.fenceInfo || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
        if (!gate_data.length) {
            return false;
        }
        return typeof FENCE !== 'undefined' && typeof FENCE.isStdGate === 'function'
            ? !FENCE.isStdGate(gate_data)
            : false;
    },

    /** Barr gate on diagram or in fence settings (gate opening consumes one panel SKU). */
    hasBarrGate: function(context, root) {
        if (!FENCES.cartItems.isBarrFence(context)) {
            return false;
        }
        root = root && root.querySelectorAll ? root : document;
        if (root.querySelector('.fencing-panel-gate')) {
            return true;
        }
        return (context.fenceInfo || []).some(function(item) {
            return item && item.control_key === 'gate';
        });
    },

    /** Barr cart slug for gate or custom gate converter at fence height. */
    isBarrGatePanelSlug: function(slug) {
        return /^gate\+(?:converter\+)?(1000|1200|1800)$/.test(String(slug || ''));
    },

    /**
     * Barr gate rows from planner context.
     */
    getBarrGateData: function(context) {
        return (context?.fenceInfo || []).filter(function(item) {
            return item && item.control_key === 'gate';
        });
    },

    /**
     * Barr fence height (mm) from calc or form fields.
     */
    getBarrFenceHeightMm: function(context) {
        var fromCalc = parseInt(context?.calc?.fence_size?.height, 10);
        if (Number.isFinite(fromCalc) && fromCalc > 0) {
            return fromCalc;
        }
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.getContextFieldValue === 'function') {
            var fromField = parseInt(String(SlatFence.getContextFieldValue(context, 'fence_height', '')), 10);
            if (Number.isFinite(fromField) && fromField > 0) {
                return fromField;
            }
        }
        return 0;
    },

    /**
     * The panel a custom gate is cut from, in the style's own catalogue form. Barr panels key on
     * fence height (panel_options+even+1200); Flat Top panels key on panel width, and the selected
     * option already carries it (even+2400, full+3000). Empty when the form cannot be completed, so
     * no half-formed slug is sold.
     */
    gateDonorPanelSlug: function(context) {
        if (FENCES.cartItems.isFlatTopFence(context)) {
            var opt = String(context?.calc?.selected_values?.panel_option || '');
            return /^(?:even|full)\+\d+$/.test(opt) ? 'panel_options+' + opt : '';
        }
        if (!FENCES.cartItems.isBarrFence(context)) {
            return '';
        }
        var height = FENCES.cartItems.getBarrFenceHeightMm(context);
        if (!height) {
            return '';
        }
        var option = String(context?.calc?.selected_values?.panel_option || 'even');
        option = option.indexOf('full') !== -1 ? 'full' : 'even';
        return 'panel_options+' + option + '+' + height;
    },

    /**
     * Barr: add +1 panel_options qty for gate opening?
     * Standard gate+1200 / gate+1800 — no extra panel.
     * Standard gate+1000 and all custom gates (gate+converter+*) — +1 panel.
     */
    shouldBarrGateAddExtraPanel: function(context, array) {
        if (!FENCES.cartItems.isBarrFence(context)) {
            return false;
        }

        array = array || [];

        if (array.some(function(item) {
            return /^gate\+converter\+(1000|1200|1800)$/.test(String(item?.slug || ''));
        })) {
            return true;
        }

        var stdGateHeight = null;
        array.forEach(function(item) {
            var m = /^gate\+(1000|1200|1800)$/.exec(String(item?.slug || ''));
            if (m) {
                stdGateHeight = parseInt(m[1], 10);
            }
        });

        var gate_data = FENCES.cartItems.getBarrGateData(context);
        var isStd =
            gate_data.length && typeof FENCE !== 'undefined' && typeof FENCE.isStdGate === 'function'
                ? FENCE.isStdGate(gate_data)
                : true;

        if (stdGateHeight !== null) {
            if (!isStd) {
                return true;
            }
            return stdGateHeight === 1000;
        }

        if (!gate_data.length) {
            return false;
        }
        if (!isStd) {
            return true;
        }

        return FENCES.cartItems.getBarrFenceHeightMm(context) === 1000;
    },

    /** Barr: custom gate or 1000mm height adds gate+converter+{height} to cart. */
    hasBarrGateConverter: function(context, array) {
        if (!FENCES.cartItems.isBarrFence(context)) {
            return false;
        }
        if ((array || []).some(function(item) {
            return /^gate\+converter\+(1000|1200|1800)$/.test(String(item?.slug || ''));
        })) {
            return true;
        }
        if (!FENCES.cartItems.hasBarrGate(context)) {
            return false;
        }
        var gate_data = FENCES.cartItems.getBarrGateData(context);
        var isStd =
            gate_data.length &&
            typeof FENCE !== 'undefined' &&
            typeof FENCE.isStdGate === 'function'
                ? FENCE.isStdGate(gate_data)
                : true;
        if (!isStd) {
            return true;
        }
        return FENCES.cartItems.getBarrFenceHeightMm(context) === 1000;
    },

    /**
     * Barr: fence panels on diagram; +1 for gate extra panel SKU unless gate+converter applies.
     */
    countBarrPanelItemsForBracket: function(root, context, array) {
        root = root && root.querySelectorAll ? root : document;
        var n = root.querySelectorAll('.panel-item:not(.fencing-raked-panel)').length;
        if (
            FENCES.cartItems.hasBarrGate(context, root) &&
            FENCES.cartItems.shouldBarrGateAddExtraPanel(context, array) &&
            !FENCES.cartItems.hasBarrGateConverter(context, array)
        ) {
            n += 1;
        }
        return n;
    },

    /**
     * Barr and Flat Top: a gate built on the converter is cut from a panel, so add one to the panel
     * line - or add that line when the section rendered no panels at all (Gate ONLY, or a run the
     * gate consumes). STD gates are stocked items and add nothing.
     */
    apply_barr_gate_panel_extra: function(array, context) {
        var isBarr = FENCES.cartItems.isBarrFence(context);
        var isFlatTop = FENCES.cartItems.isFlatTopFence(context);
        if (!isBarr && !isFlatTop) {
            return array;
        }
        var needsPanel = isBarr
            ? FENCES.cartItems.shouldBarrGateAddExtraPanel(context, array)
            : FENCES.cartItems.flatTopGateNeedsDonorPanel(context, array);
        if (!needsPanel) {
            return array;
        }
        if (
            isBarr &&
            !FENCES.cartItems.hasBarrGate(context) &&
            !(array || []).some(function(item) {
                return FENCES.cartItems.isBarrGatePanelSlug(item && item.slug);
            })
        ) {
            return array;
        }

        var bumped = false;
        array.forEach(function(item, k) {
            var slug = item && item.slug ? String(item.slug) : '';
            if (/^panel_options\+(?:even|full)\+\d+$/.test(slug)) {
                array[k].qty = (parseInt(item.qty, 10) || 0) + 1;
                bumped = true;
            }
        });

        /* Nothing to bump: Gate ONLY, or a run the gate consumes, renders no panels, so the converter
           went into the cart with nothing to convert. A custom Barr gate is a fence panel cut to width
           on the converter uprights - the donor panel has to be sold with it. */
        if (!bumped) {
            var donor = FENCES.cartItems.gateDonorPanelSlug(context);
            if (donor) {
                array.push({ slug: donor, qty: 1 });
            }
        }
        return array;
    },

    /** Barr: panel_options+bracket = panel count (no +1 when gate+converter). */
    apply_barr_bracket_rules: function(array, context, processOpts) {
        if (!FENCES.cartItems.isBarrFence(context)) {
            return array;
        }

        var root = document;
        if (processOpts && processOpts.scopeRoot && processOpts.scopeRoot.querySelectorAll) {
            root = processOpts.scopeRoot;
        }

        var qty = FENCES.cartItems.countBarrPanelItemsForBracket(root, context, array);
        if (!qty) {
            return array;
        }

        var found = array.find(function(item) {
            return item && item.slug === 'panel_options+bracket';
        });
        if (found) {
            found.qty = qty;
        } else {
            array.push({ slug: 'panel_options+bracket', qty: qty });
        }
        return array;
    },

    /**
     * Barr: corner post/cover SKUs only when gate present or multi-section junction.
     */
    needsBarrCornerSkus: function(context) {
        if (!FENCES.cartItems.isBarrFence(context)) {
            return false;
        }
        if (FENCES.cartItems.hasBarrGate(context)) {
            return true;
        }
        var tabIndex = parseInt(context.tabIndex, 10);
        return Number.isFinite(tabIndex) && tabIndex > 0;
    },

    /**
     * Barr: first/last corner posts + gate-adjacent post(s) use panel_post_corner SKUs.
     * Gate section: 2 gate-adjacent corners only (not post-left/post-right).
     * Section junction (tabIndex > 0): first post in section is a corner.
     */
    isBarrCornerPost: function(el, context, root) {
        if (!el || !el.classList) {
            return false;
        }
        if (!el.classList.contains('panel-post')) {
            return false;
        }
        if (el.classList.contains('panel-no-post')) {
            return false;
        }

        context = context || {};
        root = root && root.querySelectorAll ? root : document;

        if (!FENCES.cartItems.needsBarrCornerSkus(context)) {
            return false;
        }

        var tabIndex = parseInt(context.tabIndex, 10);
        if (!Number.isFinite(tabIndex) || tabIndex < 0) {
            tabIndex = 0;
        }

        var hasGate =
            FENCES.cartItems.isBarrFence(context) &&
            FENCES.cartItems.hasBarrGate(context, root);

        if (tabIndex > 0) {
            var firstPost = root.querySelector('.panel-post:not(.panel-no-post)');
            if (firstPost === el) {
                return true;
            }
        }

        if (hasGate) {
            var next = el.nextElementSibling;
            if (next && next.classList.contains('fencing-panel-gate')) {
                return true;
            }
            var prev = el.previousElementSibling;
            if (prev && prev.classList.contains('fencing-panel-spacing-number')) {
                var beforeSpacing = prev.previousElementSibling;
                if (beforeSpacing && beforeSpacing.classList.contains('fencing-panel-gate')) {
                    return true;
                }
            }
        }
        return false;
    },

    /** Barr post option suffix for cart slugs, e.g. opt-2+1200. */
    getBarrPostOptionSuffix: function(context, array) {
        var height = FENCES.cartItems.getBarrFenceHeightMm(context);
        if (!height) {
            return '';
        }

        var opt = '';
        /* getFenceSettingValue, not getContextFieldValue: the post option is a fence setting (fenceInfo,
           control_key post_options), not a Step 2 field. The field reader takes (context, name, default),
           so handed these four arguments it looked for a field named post_options, found none, and
           returned its "default" - the literal post_option - which then became the slug and, being
           truthy, kept every fallback below from running. */
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.getFenceSettingValue === 'function') {
            opt = String(SlatFence.getFenceSettingValue(context, 'post_options', 'post_option', '') || '');
        }
        if (!opt && Array.isArray(context?.fenceInfo)) {
            var po = context.fenceInfo.find(function(item) {
                return item && item.control_key === 'post_options';
            });
            var field = po?.settings?.find(function(s) {
                return s && s.key === 'post_option';
            });
            opt = field?.val || '';
        }
        if (!opt && Array.isArray(array)) {
            array.some(function(item) {
                var m = /^panel_post(?:_corner)?\+(opt-\d+)\+\d+$/.exec(String(item?.slug || ''));
                if (m) {
                    opt = m[1];
                    return true;
                }
                return false;
            });
        }
        if (!opt) {
            opt = 'opt-2';
        }
        return opt + '+' + height;
    },

    /**
     * Barr corner post qty for this section:
     * - Gate → 2 corners
     * - Each section after the first → +1 junction corner
     * Single section without gate → 0 (regular panel_post only)
     */
    getBarrRequiredCornerPostQty: function(context) {
        if (!FENCES.cartItems.needsBarrCornerSkus(context)) {
            return 0;
        }

        var tabIndex = parseInt(context.tabIndex, 10);
        if (!Number.isFinite(tabIndex) || tabIndex < 0) {
            tabIndex = 0;
        }

        var hasGate = FENCES.cartItems.hasBarrGate(context);
        var qty = 0;

        if (hasGate) {
            qty = 2;
        }
        if (tabIndex > 0) {
            qty += 1;
        }
        return qty;
    },

    adjustCartLineQty: function(array, slug, delta) {
        if (!slug || !delta) {
            return;
        }
        var found = array.find(function(item) {
            return item && item.slug === slug;
        });
        if (found) {
            found.qty = (parseInt(found.qty, 10) || 0) + delta;
            if (found.qty <= 0) {
                var idx = array.indexOf(found);
                if (idx >= 0) {
                    array.splice(idx, 1);
                }
            }
        } else if (delta > 0) {
            array.push({ slug: slug, qty: delta });
        }
    },

    /**
     * Barr: normalize panel_post_corner qty (gate = 2; +1 per section junction).
     * Single section without gate: no panel_post_corner lines.
     */
    apply_barr_corner_post_rules: function(array, context) {
        if (!FENCES.cartItems.isBarrFence(context)) {
            return array;
        }

        array = array || [];
        var required = FENCES.cartItems.getBarrRequiredCornerPostQty(context);

        if (!required) {
            array.forEach(function(item) {
                var slug = item && item.slug ? String(item.slug) : '';
                if (slug.indexOf('panel_post_corner+') !== 0) {
                    return;
                }
                var q = parseInt(item.qty, 10) || 0;
                if (q <= 0) {
                    return;
                }
                FENCES.cartItems.adjustCartLineQty(
                    array,
                    slug.replace('panel_post_corner', 'panel_post'),
                    q
                );
                item.qty = 0;
            });
            return array.filter(function(item) {
                return (parseInt(item.qty, 10) || 0) > 0;
            });
        }

        var suffix = FENCES.cartItems.getBarrPostOptionSuffix(context, array);
        if (!suffix) {
            return array;
        }

        var current = FENCES.cartItems.sumPanelPostCornerQty(array);
        if (current === required) {
            return array;
        }

        var delta = required - current;
        var defaultCornerSlug = 'panel_post_corner+' + suffix;
        var defaultPostSlug = 'panel_post+' + suffix;

        if (delta > 0) {
            FENCES.cartItems.adjustCartLineQty(array, defaultCornerSlug, delta);
            FENCES.cartItems.adjustCartLineQty(array, defaultPostSlug, -delta);
            return array;
        }

        var toMove = -delta;
        array.forEach(function(item) {
            if (toMove <= 0) {
                return;
            }
            var slug = item && item.slug ? String(item.slug) : '';
            if (slug.indexOf('panel_post_corner+') !== 0) {
                return;
            }
            var q = parseInt(item.qty, 10) || 0;
            if (q <= 0) {
                return;
            }
            var take = Math.min(q, toMove);
            item.qty = q - take;
            FENCES.cartItems.adjustCartLineQty(array, slug.replace('panel_post_corner', 'panel_post'), take);
            toMove -= take;
        });

        return array.filter(function(item) {
            return (parseInt(item.qty, 10) || 0) > 0;
        });
    },

    /** Sum cart line qty for post slugs matching opt-N (panel_post + panel_post_corner). */
    sumPostQtyByOpt: function(array, opt) {
        if (!Array.isArray(array) || !opt) {
            return 0;
        }
        var escaped = String(opt).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var re = new RegExp('^(?:panel_post|panel_post_corner)\\+' + escaped + '(?:\\+|\\-|$)');
        var total = 0;
        array.forEach(function(obj) {
            var slug = obj && obj.slug ? String(obj.slug) : '';
            if (re.test(slug)) {
                total += parseInt(obj.qty, 10) || 0;
            }
        });
        return total;
    },

    /** Total qty of all panel_post_corner lines in cart. */
    sumPanelPostCornerQty: function(array) {
        if (!Array.isArray(array)) {
            return 0;
        }
        var total = 0;
        array.forEach(function(obj) {
            var slug = obj && obj.slug ? String(obj.slug) : '';
            if (slug.indexOf('panel_post_corner+') === 0) {
                total += parseInt(obj.qty, 10) || 0;
            }
        });
        return total;
    },

    /** Barr post cover qty: regular panel_post vs panel_post_corner. */
    sumBarrPostCoverQuantities: function(array) {
        var regular = 0;
        var corner = 0;
        (array || []).forEach(function(item) {
            var slug = item && item.slug ? String(item.slug) : '';
            var q = parseInt(item.qty, 10) || 0;
            if (/^panel_post\+/.test(slug)) {
                regular += q;
            } else if (/^panel_post_corner\+/.test(slug)) {
                corner += q;
            }
        });
        return { regular: regular, corner: corner };
    },

    /**
     * Barr: corner posts use base_plate+post_cover_corner; regular posts use base_plate+post_cover.
     */
    apply_barr_post_cover_rules: function(array, context) {
        if (!FENCES.cartItems.isBarrFence(context)) {
            return array;
        }

        array = array || [];
        var counts = FENCES.cartItems.sumBarrPostCoverQuantities(array);

        array = array.filter(function(item) {
            var slug = item && item.slug ? String(item.slug) : '';
            return slug !== 'base_plate+post_cover' && slug !== 'base_plate+post_cover_corner';
        });

        if (FENCES.cartItems.needsBarrCornerSkus(context) && counts.corner > 0) {
            array.push({
                slug: 'base_plate+post_cover_corner',
                qty: counts.corner
            });
        }
        if (counts.regular > 0) {
            array.push({
                slug: 'base_plate+post_cover',
                qty: counts.regular
            });
        }

        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Gate Options First/Second post selections (main slat): count posts matching opt-N slug.
     */
    countSlatGatePostsByOpt: function(context, optSlug) {
        if (!context || !FENCES.cartItems.isSlatPostStyleFence(context)) {
            return 0;
        }
        var fenceInfo = context.fenceInfo || [];
        var gateItem = fenceInfo.find(function(it) {
            return it.control_key === 'gate';
        });
        var fields = gateItem?.settings?.fields || [];
        if (!Array.isArray(fields)) {
            return 0;
        }
        var want = String(optSlug || '');
        var n = 0;
        ['gate_first_post', 'gate_second_post'].forEach(function(key) {
            var f = fields.find(function(x) {
                return x.key === key;
            });
            if (f && String(f.val) === want) {
                n++;
            }
        });
        return n;
    },

    //----------------------------------------------------------------------------------

    getContext: function(tabIndex) {
        var raw = localStorage.getItem('custom_fence-' + tabIndex);
        var tabInfo = raw ? JSON.parse(raw) : [];
        if (tabInfo[0] && tabInfo[0].fence === 'slat_fence') {
            tabInfo[0].fence = 'slat';
            if (tabInfo[0].style === 'slat_fence') {
                tabInfo[0].style = 'slat';
            }
            localStorage.setItem('custom_fence-' + tabIndex, JSON.stringify(tabInfo));
        }
        var rawFence = tabInfo?.[0]?.fence;
        var fenceSlug = rawFence ? normalizeFenceStyleSlug(rawFence) : '';
        var fenceInfo = rawFence ? readCustomFenceSegment(tabIndex, rawFence) : [];

        var calc = null;
        if (typeof calculate_fences === 'function' && tabInfo[0]) {
            calc = calculate_fences({
                tab: tabIndex,
                item: tabInfo[0].fence || tabInfo[0].style || rawFence
            });
        }

        return {
            tabIndex: tabIndex,
            tabInfo: tabInfo || [],
            fenceSlug: fenceSlug || '',
            fenceInfo: fenceInfo || [],
            calc: calc
        };
    },

    //----------------------------------------------------------------------------------

    /**
     * DOM root for counting `[data-cart-key]` (planner active section vs project-plan `#pp-{n}`).
     */
    getProcessScopeRoot: function(tabIndex) {
        var planRoot = document.querySelector('#pp-' + tabIndex);
        if (planRoot) {
            return planRoot;
        }
        if (document.querySelector('.fc-planner-page')) {
            return (
                document.querySelector('.fc-planner-page .fencing-panel-container') ||
                document.querySelector('.fencing-panel-container')
            );
        }
        return null;
    },

    init: function(i, opts) {
        opts = opts || {};
        var tabIndex = i;
        var context = FENCES.cartItems.getContext(tabIndex);
        var slug = context.fenceSlug;

        var sectionNum = tabIndex + 1;
        var planRoot = document.querySelector('#pp-' + tabIndex);

        if (!opts.skipTabClick && !planRoot) {
            $(`.fc-section-${sectionNum}`).click();
        }

        if (typeof calculate_fences === 'function' && context.tabInfo && context.tabInfo[0]) {
            context.calc = calculate_fences({
                tab: tabIndex,
                item: context.tabInfo[0].fence || context.tabInfo[0].style || slug
            });
        }

        var scopeRoot = opts.scopeRoot || FENCES.cartItems.getProcessScopeRoot(tabIndex);
        const multiArray = FENCES.cartItems.process(context, {
            scopeRoot: scopeRoot,
            tabIndex: tabIndex
        });

        // Convert the array to a string using JSON.stringify
        const multiArrayString = JSON.stringify(multiArray);

        try {
            // Save the string in local storage (canonical slug, e.g. `slat` not `slat_fence`)
            slug = normalizeFenceStyleSlug(slug);
            localStorage.setItem('cart_items-' + i + '-' + slug, multiArrayString);
            if (slug === 'slat') {
                localStorage.removeItem('cart_items-' + i + '-slat_fence');
            }

            // Retrieve data from local storage
            const storedMultiArrayString = localStorage.getItem('cart_items-' + i + '-' + slug);

            // Convert the string back to an array using JSON.parse
            const storedMultiArray = JSON.parse(storedMultiArrayString);

            // Output the retrieved data
            // console.log('Retrieved cart_items:', storedMultiArray);

        } catch (error) {
            // Handle errors
            //console.error('Error occurred while getting cart_items:', error);
        }
    },

    //----------------------------------------------------------------------------------

    process: function(context, opts) {
        opts = opts || {};
        context = context || FENCES.cartItems.getContext($('.fencing-tab.fencing-tab-selected').index());

        var root = document;
        if (opts.scopeRoot && opts.scopeRoot.querySelectorAll) {
            root = opts.scopeRoot;
        } else if (Number.isFinite(opts.tabIndex)) {
            var scoped = FENCES.cartItems.getProcessScopeRoot(opts.tabIndex);
            if (scoped) {
                root = scoped;
            }
        }

        //Get all cart items
        // Added data-cart-key to identify the items that will appear in cart
        let getItemsByCartKey = root.querySelectorAll('[data-cart-key]');

        //Object that holds the color and cart items
        //This is the data that will be stored in localstorage and also sent to the server when form is submitted 
        var newCartItems = [];

        //Iterate all items found with [data-cart-key] attribute
        for (let i = 0; i < getItemsByCartKey.length; i++) {

            //Get element object
            let el = getItemsByCartKey[i];

            //A cart item slug consist of two parts, the key and the value
            //example: `panel_options+even` = `{key}+{value}`

            //Get cart key
            let cartKey = el.getAttribute('data-cart-key');

            //Get cart value
            let cartValue = el.getAttribute('data-cart-value');

            // Barr corner / gate posts use panel_post_corner+opt-N+height SKUs.
            if (
                cartKey === 'panel_post' &&
                FENCES.cartItems.isBarrFence(context) &&
                FENCES.cartItems.isBarrCornerPost(el, context, root)
            ) {
                cartKey = 'panel_post_corner';
            }

            //Merge the two strings to create an cart item slug
            //example: `panel_options+even` = `{cartKey}+{cartValue}`
            let modifiedCartKey = cartValue ? `${cartKey}+${cartValue}` : cartKey;

            //Init an empty object
            //This will contain the cart item data
            let entry = {};

            //This variable is used to add a check before adding new object to the `newCartItems` array
            //If an object is found in the array, update it. if not, push the new object to array
            let found = false;
            let qty = 1;

            // A node can stand in for more than itself. Slat Infill caps how many panels it
            // renders (SlatFenceInfill.appendHiddenPanelsTile) while the cart is a raw count of
            // [data-cart-key] nodes, so the capped panels were calculated, summarised in the
            // "N more panels" tile, and then never billed. The renderer folds their quantity onto
            // the last rendered node instead. No attribute = qty 1, so this is inert everywhere else.
            let declaredQty = parseInt(el.getAttribute('data-cart-qty'), 10);
            if (Number.isFinite(declaredQty) && declaredQty > 0) {
                qty = declaredQty;
            }

            //additional condition for some cart items
            if (cartKey === "gate") {
                //Since `gate_kit` shares the same value with `gate`
                //create the entry for `gate_kit` manually
                newCartItems.push(FENCES.cartItems.item.gateKit1);

            }

            //additional condition for some cart items
            //for raked_panel, we removed extra characters from the slug
            if (cartKey === "left_raked_panel") {
                //Remove H and W chars
                modifiedCartKey = modifiedCartKey.replace("H", '').replace("W", '');
            }
            if (cartKey === "right_raked_panel") {
                //Remove H and W chars
                modifiedCartKey = modifiedCartKey.replace("H", '').replace("W", '');
            }

            //additional condition for panel_post to exclude el with class `post-left` OR `post-right`
            if (cartKey === 'panel_post' || cartKey === 'panel_post_corner' || cartKey === 'raked_post') {
                if (el.classList.contains('panel-no-post')) {
                    qty = 0;
                }

                if (el.classList.contains(FENCES.cartItems.item.swivelBrackets.className)) {
                    modifiedCartKey = FENCES.cartItems.item.swivelBrackets.slug;
                    qty = FENCES.cartItems.item.swivelBrackets.qty;
                    cartValue = true;
                }
            }

            //Update the object `slug` and `qty` property before pushing to the array
            entry.slug = modifiedCartKey;
            entry.qty = qty;

            //Iterate though existing cart items array
            //This condition will handle the check if the cart item slug already exists in the array
            if (newCartItems.length > 0) {
                for (let i = 0; i < newCartItems.length; i++) {
                    //We are using the `slug` property to check if the cart item already exists in the array
                    if (newCartItems[i].slug === modifiedCartKey) {
                        //If it exists, increase the quantity by 1
                        newCartItems[i].qty += qty;
                        found = true;
                        break;
                    }
                }
            }

            //If the cart item slug does not exists in array, push/add it into the array
            if (!found && cartValue !== null && entry.qty != 0) {
                newCartItems.push(entry);
            }

        }

        newCartItems = FENCES.cartItems.apply_conditions(newCartItems, context, opts);


        return newCartItems;
    },

    //----------------------------------------------------------------------------------

    apply_conditions: function(newCartItems, context, processOpts) {
        processOpts = processOpts || {};
        newCartItems = FENCES.cartItems.apply_barr_corner_post_rules(newCartItems, context);

        //Apply condition for panel_options+even
        newCartItems = FENCES.cartItems.apply_panel_options_even(newCartItems, context);

        //Apply condition for panel_options+full
        newCartItems = FENCES.cartItems.apply_panel_options_full(newCartItems, context);

        //Apply condition for post_options+opt-1 
        newCartItems = FENCES.cartItems.apply_post_options_opt1(newCartItems, context);
        newCartItems = FENCES.cartItems.apply_barr_post_cover_rules(newCartItems, context);
        newCartItems = FENCES.cartItems.apply_post_options_opt2(newCartItems, context);

        //Apply condition for panel_post
        newCartItems = FENCES.cartItems.apply_panel_post(newCartItems);
        newCartItems = FENCES.cartItems.cart_conditions(newCartItems, context);

        // Slat Fence (Barr-based) optional infill materials
        newCartItems = FENCES.cartItems.slat_fence_conditions(newCartItems, context);

        newCartItems = FENCES.cartItems.glass_pool_conditions(newCartItems, context);

        newCartItems = FENCES.cartItems.apply_barr_gate_panel_extra(newCartItems, context);

        newCartItems = FENCES.cartItems.apply_barr_bracket_rules(newCartItems, context, processOpts);

        return newCartItems;
    },

    //----------------------------------------------------------------------------------

    glass_pool_conditions: function(array, context) {
        if (typeof GlassPool !== 'undefined' && typeof GlassPool.applyCartConditions === 'function') {
            return GlassPool.applyCartConditions(array, context);
        }
        return array;
    },

    cart_conditions: function(array, context) {
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.applyGateKitConditions === 'function') {
            return SlatFence.applyGateKitConditions(array, context, FENCES.cartItems.item.gateKit2);
        }
        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Slat Fence (Barr-based): add slat + spacer materials.
     *
     * Rows from fence height: floor((H+gap−3)/(slatFace+gap)).
     * Slat qty = rows × fence sections (e.g. 7 × 3 = 21 for slat+65).
     */
    slat_fence_conditions: function(array, context) {
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.applyCartConditions === 'function') {
            return SlatFence.applyCartConditions(array, context);
        }
        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * IF gate is found
     * @param {*} array 
     * @returns 
     */
    apply_panel_post: function(array) {
        const foundGate = array.find(obj => obj['slug'] === 'gate' || (obj.slug && String(obj.slug).indexOf('gate+') === 0));
        array.forEach(function(v, k) {
            if (foundGate && v.slug && (v.slug.includes('panel_post') || v.slug.includes('panel_post_corner'))) {
                v.qty = v.qty;
                array[k] = v;
            }
        });
        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * IF panel options = "Evenly Spaced Posts" = (Number of Fence Panels) + (short panel offcut panel length)
     * @param {*} array 
     * @returns 
     */
    /**
     * Node count that honours data-cart-qty. Slat Infill caps how many panels it renders and folds
     * the hidden panels' quantity onto the last rendered node, so a raw .length here under-counts
     * the bracket line by exactly the panels the preview hides.
     */
    countNodesWithCartQty: function(nodes) {
        var total = 0;
        Array.prototype.forEach.call(nodes || [], function (el) {
            var q = parseInt(el.getAttribute('data-cart-qty'), 10);
            total += (Number.isFinite(q) && q > 0) ? q : 1;
        });
        return total;
    },

    //----------------------------------------------------------------------------------

    apply_panel_options_even: function(array, context) {
        //Get offcut size
        let getOffCutValue = document.querySelector('.fencing-offcut')?.getAttribute('data-cart-value');
        let getPanelItems = FENCES.cartItems.countNodesWithCartQty(document.querySelectorAll('.panel-item:not(.fencing-raked-panel)'));
        if (context && FENCES.cartItems.isBarrFence(context)) {
            getPanelItems = FENCES.cartItems.countBarrPanelItemsForBracket(document, context, array);
        }
        //Find the existing object
        const foundObject = array.find(obj => obj['slug'] === "panel_options+even");
        let qty = getPanelItems;
        if (qty) {
            FENCES.cartItems.apply_panel_options_bracket(array, qty);
        }
        return array;
    },

    //----------------------------------------------------------------------------------

    /**  1 + 3
     * IF panel options = "Full Panels - 3000W" = (Number of full length Panels) + (Number of short length panels)
     * @param {*} array 
     * @returns 
     */
    apply_panel_options_full: function(array, context) {
        //Get all short panel item
        let noOfShortPanel = document.querySelectorAll('.short-panel-item').length;
        let getPanelItems = FENCES.cartItems.countNodesWithCartQty(document.querySelectorAll('.panel-item:not(.fencing-raked-panel)'));
        let getRakedPanelItems = document.querySelectorAll('.panel-item.fencing-raked-panel').length;
        if (context && FENCES.cartItems.isBarrFence(context)) {
            getPanelItems = FENCES.cartItems.countBarrPanelItemsForBracket(document, context, array);
        }
        //Find the existing object
        const foundObject = array.find(obj => obj['slug'] === "panel_options+full");
        var qty = getPanelItems + getRakedPanelItems;
        if (qty) {
            FENCES.cartItems.apply_panel_options_bracket(array, qty);
        }
        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Get qty of either slug selected for panel_options{even/full}
     * @param {*} array 
     * @param {*} total 
     * @returns 
     */
    apply_panel_options_bracket: function(array, total) {

        let slug = "panel_options+bracket";
        //Find if the slug already exists
        const foundObject = array.find(obj => obj['slug'] === slug);
        //Exists
        if (foundObject) {
            //then update the qty value
            foundObject['qty'] = total;
            //Doesnt exists
        } else {
            //add new object for the slug
            array.push({
                "slug": slug,
                "qty": total,
            });

        }
    },

    //----------------------------------------------------------------------------------

    /**
     * Apply Condition for Base Plated Posts | Row 16
     * Condition: Object with slug `panel_post+opt-1` AND `raked_post+opt-1` must be in array
     * 
     * @param {*} array 
     * @returns 
     */
    apply_raked_panel_post_opt1: function(array) {
        //Find the two objects with slug `panel_post+opt-1` and `raked_post+opt-1` in the array
        //If it exists means user selected it
        const foundPanelPostOpt1 = array.find(obj => obj['slug'] === "panel_post+opt-1");
        const foundRakedPostOpt1 = array.find(obj => obj['slug'] === "raked_post+opt-1");
        //If any of the slug returns undefined, do nothing
        if (typeof foundPanelPostOpt1 === "undefined" || typeof foundRakedPostOpt1 === "undefined") {
            return array;
        }
        //Remove `post_options+opt-2` from array
        array = FENCES.cartItems.remove_post_options_opt2(array);
        let total = foundPanelPostOpt1.qty + foundRakedPostOpt1.qty;
        array.push({
            "slug": "raked_panel_post+opt-1",
            "qty": total,
        });
        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Apply Condition for Cemented Post | Row 20
     * Condition: Object with slug `panel_post+opt-2` AND `raked_post+opt-2` must be in array
     * 
     * @param {*} array 
     * @returns 
     */
    apply_raked_panel_post_opt2: function(array) {
        const foundRakedPostOpt2 = array.find(obj => obj['slug'] === "raked_post+opt-2");
        //If any of the slug returns undefined, do nothing
        if (typeof foundRakedPostOpt2 === "undefined") {
            return array;
        }
        //Find the two objects with slug `panel_post+opt-2` and `raked_post+opt-2` in the array
        //If it exists means user selected it
        for (var i = 0; i < array.length; i++) {
            if (array[i].slug == 'raked_post+opt-2') {
                array[i].qty = array[i].qty + 1;
                break;
            }
        }
        return array;
    },

    //----------------------------------------------------------------------------------

    /**
     * Apply Condition for Cemented Post
     * Condition: Object with slug `panel_post+opt-1` AND `raked_post+opt-1` must be in array
     * 
     * @param {*} array 
     * @returns 
     */
    apply_post_options_opt1: function(array, context) {
        context = context || {};
        var tabInfo = context.tabInfo || [];

        var total = 0;
        total += FENCES.cartItems.sumPostQtyByOpt(array, 'opt-1');

        const foundRakedPostOpt1 = array.find(obj => obj['slug'].includes('raked_post+opt-1'));
        if (typeof foundRakedPostOpt1 !== 'undefined') {
            total += foundRakedPostOpt1.qty;
        }

        total += FENCES.cartItems.countSlatGatePostsByOpt(context, 'opt-1');

        if (total) {    

            // If the spigot is base plated
            if ($.inArray(tabInfo[0]?.fence, ['glass_pool']) !== -1) {
                var spigots = $('.fencing-panel-spigot').length,
                    gluePerBolts = 7; 

                array.push({
                    "slug": "fixings_stone",
                    "qty": spigots,
                });

                let glue = Math.floor(spigots / gluePerBolts);
                if (spigots % gluePerBolts > 0) {
                    glue += 1;
                }

                array.push({
                    "slug": "chem_achor+glue",
                    "qty": glue,
                });
                
                array.push({
                    "slug": "chem_achor+glue_gun",
                    "qty": 1,
                });
            }

            if (FENCES.cartItems.isSlatPostStyleFence(context)) {
                array.push({
                    "slug": "slat_post+base_plate_kit",
                    "qty": total,
                });
                array.push({
                    "slug": "slat_post+base_plate_cover",
                    "qty": total,
                });
                return array;
            }

            if (FENCES.cartItems.isBarrFence(context)) {
                array.push({
                    slug: 'base_plate+dynabolts',
                    qty: 0,
                    optional: true,
                    suggested_qty: total
                });
                return array;
            }

            array.push({
                "slug": "base_plate+dynabolts",
                "qty": total,
            });
            array.push({
                "slug": "base_plate+post_cover",
                "qty": total,
            });
            return array;
        }
        return array;
    },

    /**
     * Condition: Object with slug `panel_post+opt-2` AND `raked_post+opt-2` must be in array
     * 
     * @param {*} array 
     * @returns 
     */
    apply_post_options_opt2: function(array, context) {
        context = context || {};
        var tabInfo = context.tabInfo || [];

        var total = 0;

        total += FENCES.cartItems.sumPostQtyByOpt(array, 'opt-2');
        total += FENCES.cartItems.sumPostQtyByOpt(array, 'opt-2-1');

        const foundRakedPostOpt2 = array.find(obj => obj['slug'].includes('raked_post+opt-2'));
        const foundRakedPostOpt21 = array.find(obj => obj['slug'].includes('raked_post+opt-2-1'));

        if (typeof foundRakedPostOpt2 !== 'undefined') {
            total += foundRakedPostOpt2.qty;
        }
        if (typeof foundRakedPostOpt21 !== 'undefined') {
            total += foundRakedPostOpt21.qty;
        }

        if (total) {

            if ($.inArray(tabInfo[0]?.fence, ['glass_pool']) !== -1) {
                array.push({
                    "slug": "grout",
                    "qty": Math.round($('.fencing-panel-spigot').length / 8),
                });
            }

            return array;
        }
        return array;
    },

    //----------------------------------------------------------------------------------

    remove_item: function(array, slug) {
        //To removed
        const foundPostOptionsOpt2 = array.find(obj => obj['slug'] === slug);
        if (typeof foundPostOptionsOpt2 !== "undefined") {
            array = array.filter(obj => obj.slug !== slug);
        }
        return array;
    },

    //----------------------------------------------------------------------------------

    remove_post_options_opt2: function(array) {
        let slug = "post_options+opt-2";
        //To removed
        const foundPostOptionsOpt2 = array.find(obj => obj['slug'] === slug);
        if (typeof foundPostOptionsOpt2 !== "undefined") {
            array = array.filter(obj => obj.slug !== slug);
        }
        return array;
    }

    //----------------------------------------------------------------------------------

}