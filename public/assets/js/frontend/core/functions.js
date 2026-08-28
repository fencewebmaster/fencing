/**
 * Function to add or update an object in the array by key property
 * @param {array} objectArray 
 * @param {obj} obj 
 * @returns 
 */
function addObjectByKey(objectArray, obj) {
    const existingIndex = objectArray.findIndex(item => item.control_key === obj.control_key);
    if (existingIndex !== -1) {
        objectArray[existingIndex] = obj;
    } else {
        objectArray.push(obj);
    }

    return objectArray;
}

//----------------------------------------------------------------------------------

/**
 * Find Object by property value
 * @param {array} array 
 * @param {string} keyToFind 
 * @returns 
 */
function findObjectByKey(array, keyToFind) {
    for (let i = 0; i < array.length; i++) {
        if (array[i].key === keyToFind) {
            return array[i];
        }
    }
    return null;
}

//----------------------------------------------------------------------------------

/**
 * Get values from setting array
 * @param {array} setting 
 * @param {string} key 
 * @param {array} props 
 */
function updateElements(setting, key, props) {
    let entry = findObjectByKey(setting, key);
    for (let i = 0; i < props.length; i++) {
        updateElement(entry.key, props[i], entry[props[i]]);
    }
}

//----------------------------------------------------------------------------------

/**
 * Get global setting from localStorage
 */
function loadGlobalSetting() {
    let getGlobalSetting = localStorage.getItem('custom_fence-global-setting');
    let globalSettingObj = getGlobalSetting ? JSON.parse(getGlobalSetting)[0] : [];
    let globalSetting = globalSettingObj['settings'];
    let globalControlKey = globalSettingObj['control_key'];
    updateElements(globalSetting, "color_options", ["title", "subtitle", "color_code"]);
}

//----------------------------------------------------------------------------------

function clearFencingData() {
    // Planner reset: all tab payloads (custom_fence-0, custom_fence-0-slat, custom_fence-section,
    // custom_fence-global-setting, …), cart lines, plan blobs, countdown.
    removeItemStorageWith("custom_fence");
    removeItemStorageWith("cart_items");
    removeItemStorageWith("project-plans");
    removeItemStorageWith("countdown-date");
}

//----------------------------------------------------------------------------------

/**
 * Full browser wipe for fencing calculator keys (used after successful project-plan checkout push).
 */
function clearPlannerLocalStorage() {
    clearFencingData();
    try {
        localStorage.removeItem("last-clicked-value");
    } catch (e) {}
    removeItemStorageWith("fc-step2-go-snap-");
}

//----------------------------------------------------------------------------------

/** Remove every localStorage key whose name starts with `prefix` (snapshot keys first; safe while mutating). */
function removeItemStorageWith(prefix) {
    const keys = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith(prefix)) keys.push(key);
    }
    keys.forEach(function (k) {
        localStorage.removeItem(k);
    });
}

//----------------------------------------------------------------------------------

/*function getCartItemStorage() {
    var values = [];
    Object.entries(localStorage).forEach(([key, value]) => {
        if (key.startsWith("cart_items")) {
            var cartData = JSON.parse(localStorage.getItem(key)),
                fence = key.split('-').pop();

            values.push({
                [fence]: cartData
            });
        }
    });
    return values;
}*/


function getCartItemStorage() {
    var values = [];
    Object.entries(localStorage).forEach(function(entry) {
        var key = entry[0];
        var value = entry[1];
        if (!key || key.indexOf('cart_items-') !== 0) {
            return;
        }
        var m = /^cart_items-(\d+)-(.+)$/.exec(key);
        if (!m) {
            return;
        }
        try {
            var cartData = JSON.parse(value);
            var id = m[2] + '-' + m[1];
            var row = {};
            row[id] = cartData;
            values.push(row);
        } catch (e) {}
    });
    return values;
}

/**
 * Restore cart_items-{section}-{slug} keys from saved planner cart_items_data (DB / session).
 */
function fcResolveSectionFenceSlugForCart(tabIdx0) {
    try {
        var raw = localStorage.getItem('custom_fence-' + tabIdx0);
        if (!raw) {
            return '';
        }
        var form = JSON.parse(raw);
        var slug = form && form[0] ? (form[0].style || form[0].fence || '') : '';
        return typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(slug) : String(slug || '');
    } catch (e) {
        return '';
    }
}

function fcNormalizePlannerCartItemsGrouped(cartItemsData) {
    var cart_items = [];

    if (typeof cartItemsData === 'string') {
        try {
            cart_items = JSON.parse(cartItemsData || '[]') || [];
        } catch (e) {
            cart_items = [];
        }
    } else if (Array.isArray(cartItemsData)) {
        cart_items = cartItemsData;
    } else if (cartItemsData && typeof cartItemsData === 'object') {
        cart_items = Object.keys(cartItemsData).map(function(key) {
            return cartItemsData[key];
        });
    }

    return Array.isArray(cart_items) ? cart_items : [];
}

function fcHydratePlannerCartItemsLocalStorage(cartItemsData, options) {
    options = options || {};
    var cart_items = fcNormalizePlannerCartItemsGrouped(cartItemsData);

    if (!cart_items.length) {
        return false;
    }

    if (options.clearFirst !== false) {
        removeItemStorageWith('cart_items-');
    }

    cart_items.forEach(function(row, rowIdx) {
        if (Array.isArray(row)) {
            var slugFromTab = fcResolveSectionFenceSlugForCart(rowIdx);
            if (!slugFromTab) {
                return;
            }
            try {
                localStorage.setItem('cart_items-' + rowIdx + '-' + slugFromTab, JSON.stringify(row));
            } catch (eRowArr) {}
            return;
        }

        if (!row || typeof row !== 'object') {
            return;
        }

        Object.keys(row).forEach(function(id) {
            var items = row[id];
            if (!Array.isArray(items)) {
                return;
            }

            var slug = '';
            var idx = String(rowIdx);
            var m = /^(.+)-(\d+)$/.exec(String(id));
            if (m) {
                slug = m[1];
                idx = m[2];
            } else {
                slug = fcResolveSectionFenceSlugForCart(rowIdx);
            }

            if (!slug) {
                return;
            }

            if (typeof normalizeFenceStyleSlug === 'function') {
                slug = normalizeFenceStyleSlug(slug);
            }

            try {
                localStorage.setItem('cart_items-' + idx + '-' + slug, JSON.stringify(items));
            } catch (eCart) {}
        });
    });

    return getCartItemStorage().length > 0;
}

function fcWhenPlannerQuoteReady(callback, options) {
    options = options || {};
    var maxMs = options.maxMs != null ? options.maxMs : 8000;
    var start = Date.now();

    function tick() {
        var ready = false;

        if ($('.fc-planner-page').length) {
            var $panel = $('.fc-planner-page .fencing-panel-container').first();
            var hasPanels =
                $panel.find('.fencing-panel-item, .fencing-panel-gate, .extra-panel-item').length > 0;
            var hasStyle = $('.fencing-style-item.fsi-selected').length > 0;
            ready = hasStyle && (hasPanels || Date.now() - start > 2500);
        }

        if (ready || Date.now() - start > maxMs) {
            if (typeof callback === 'function') {
                callback();
            }
            return;
        }

        setTimeout(tick, 200);
    }

    tick();
}

function fcRunQuoteReloadSubmit() {
    fcWhenPlannerQuoteReady(function() {
        if (
            typeof fcHydratePlannerCartItemsLocalStorage === 'function' &&
            typeof fc_fence_info !== 'undefined' &&
            fc_fence_info &&
            fc_fence_info.cart_items_data
        ) {
            fcHydratePlannerCartItemsLocalStorage(fc_fence_info.cart_items_data);
        }

        var cart = typeof getCartItemStorage === 'function' ? getCartItemStorage() : [];
        if (cart && cart.length) {
            if (typeof Planner !== 'undefined' && typeof Planner.planCart === 'function') {
                Planner.planCart(true);
            }
            return;
        }

        var items =
            typeof fcGetPersistedFenceSectionCount === 'function'
                ? fcGetPersistedFenceSectionCount()
                : parseInt(localStorage.getItem('custom_fence-section'), 10);
        if (!Number.isFinite(items) || items < 1) {
            items = 1;
        }

        if (typeof fcRebuildPlannerCartSequential === 'function') {
            fcRebuildPlannerCartSequential(items, function() {
                if (typeof Planner !== 'undefined' && typeof Planner.planCart === 'function') {
                    Planner.planCart(true);
                }
            });
            return;
        }

        if (typeof Planner !== 'undefined' && typeof Planner.planCart === 'function') {
            Planner.planCart(true);
        }
    });
}


//----------------------------------------------------------------------------------

function mergeSettings(data, settings, key, modal_key) {
    //Check first if a control_key already exists and get it
    var find_existing_data = data.find(obj => obj[key] === modal_key);

    if (typeof find_existing_data !== "undefined") {
        let merge_settings = [];

        if( modal_key == 'gate' ) {

            find_existing_data?.settings?.fields?.forEach(obj => {
                merge_settings.push(obj);
            });

        } else {

            find_existing_data?.settings?.forEach(obj => {
                merge_settings.push(obj);
            });

        }

        settings.forEach(obj => {
            const indexToRemove = merge_settings.findIndex(item => item.key === obj.key);
            // Check if the object with the given ID was found
            if (indexToRemove !== -1) {
                // Remove the object from the array using splice
                merge_settings.splice(indexToRemove, 1);
            }
            merge_settings.push(obj);
        });
        settings = merge_settings;
    }

    return settings;
}

//----------------------------------------------------------------------------------

function updateOrCreateObjectInLocalStorage(key, newData) {
    // Check if the key already exists in localStorage
    if (localStorage.getItem(key)) {
        // If it exists, parse the JSON data and update the object
        const existingData = JSON.parse(localStorage.getItem(key));
        const updatedData = { ...existingData, ...newData };
        // Save the updated object back to localStorage
        //convert array to string
        if (updatedData['extra'] && Array.isArray(updatedData['extra'])) {
            updatedData['extra'] = updatedData['extra'].join(', ');
        }
        if (updatedData.mobile != null && updatedData.mobile !== '') {
            updatedData.mobile = fcNormalizeMobileForStorage(updatedData.mobile);
        }
        localStorage.setItem(key, JSON.stringify(updatedData));
    } else {
        // If the key doesn't exist, create a new object and save it to localStorage
        if (newData.mobile != null && newData.mobile !== '') {
            newData.mobile = fcNormalizeMobileForStorage(newData.mobile);
        }
        localStorage.setItem(key, JSON.stringify(newData));
    }
}

//----------------------------------------------------------------------------------

function getActiveFencing() {
    var sectionCount = parseInt(localStorage.getItem('custom_fence-section'), 10);
    if (!Number.isFinite(sectionCount) || sectionCount < 1) {
        return [];
    }
    var fenceStyle = [];
    for (var i = 0; i < sectionCount; i++) {
        var raw = localStorage.getItem('custom_fence-' + i);
        if (!raw) {
            continue;
        }
        try {
            var cf = JSON.parse(raw);
            if (cf && cf[0] && cf[0].style) {
                fenceStyle.push(cf[0].style);
            }
        } catch (e) {}
    }
    return fenceStyle.filter(function(v, p) {
        return fenceStyle.indexOf(v) === p;
    });
}

/**
 * How many planner sections use this fence style (matches `custom_fence-{n}` row style, slug-normalized).
 * @param {string} canonicalSlug — e.g. `fc_data[v].slug`
 * @param {string} [styleKey] — optional `fc_data` array key `v` when it can differ from stored `style`
 */
function fcPlannerSectionCountForFenceStyle(canonicalSlug, styleKey) {
    var target =
        typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(canonicalSlug)
            : String(canonicalSlug || '');
    var alt =
        styleKey !== undefined && styleKey !== null && styleKey !== ''
            ? typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(styleKey)
                : String(styleKey)
            : null;
    var n = 0;
    var sectionCount = parseInt(localStorage.getItem('custom_fence-section'), 10);
    if (!Number.isFinite(sectionCount) || sectionCount < 1) {
        return 0;
    }
    for (var i = 0; i < sectionCount; i++) {
        var raw = localStorage.getItem('custom_fence-' + i);
        if (!raw) {
            continue;
        }
        try {
            var cf = JSON.parse(raw);
            var st = cf && cf[0] && cf[0].style;
            if (!st) {
                continue;
            }
            var stNorm =
                typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(st) : String(st);
            if (stNorm === target || (alt !== null && stNorm === alt)) {
                n++;
            }
        } catch (e) {}
    }
    return n;
}

//----------------------------------------------------------------------------------

function savePlanner() {
    // var form = $('form')[0]; 
    var formData = new FormData();
    formData.set("action", 'save_planner');
    if (typeof planner_id !== 'undefined' && planner_id) {
        formData.set("planner_id", String(planner_id).trim());
    }
    $.ajax({
        url: 'checkout',
        type: "POST",
        data: formData,
        headers: {},
        contentType: false,
        cache: false,
        processData: false,
        success: function(response) {
            try {
                var info = JSON.parse(response);

                if (!info.error) {
                    var $qidEl = $('.quote-id-card .qic-body [id]').first();
                    if ($qidEl.length) {
                        $qidEl.text(info.id);
                    } else {
                        $('.quote-id-card .qic-body').html(info.id);
                    }
                }

            } catch (err) {

            }
        }
    });
}

//----------------------------------------------------------------------------------

const submitModal = document.getElementById("submit-modal");
const formDownload = document.getElementById("fc-planning-form");
const projectPlanKey = "project-plans";

function fcGetOtherProductsRoot() {
    return document.querySelector('#submit-modal .fc-other-products');
}

function fcSyncOtherProductsSelectionUi(root) {
    root = root || fcGetOtherProductsRoot();
    if (!root) {
        return;
    }

    $(root).find('.fc-form-check-img').each(function() {
        var $box = $(this);
        $box.toggleClass('fc-selected', $box.find('input').is(':checked'));
    });
}

function fcParseOtherProductsExtraValues(raw) {
    if (raw === null || raw === undefined || raw === '') {
        return [];
    }

    if (Array.isArray(raw)) {
        return raw.map(function(item) {
            return String(item).trim();
        }).filter(Boolean);
    }

    if (typeof raw === 'string') {
        var trimmed = raw.trim();
        if (trimmed === '' || trimmed === 'nothing') {
            return [];
        }

        if (trimmed.charAt(0) === '[') {
            try {
                var decoded = JSON.parse(trimmed);
                if (Array.isArray(decoded)) {
                    return fcParseOtherProductsExtraValues(decoded);
                }
            } catch (eJson) {}
        }

        if (trimmed.indexOf(',') !== -1) {
            return trimmed.split(',').map(function(part) {
                return part.trim();
            }).filter(Boolean);
        }

        return [trimmed];
    }

    return [];
}

/**
 * Persist NIL vs extra[] exclusively into project-plans localStorage.
 */
function fcPersistOtherProductsToProjectPlans() {
    var root = fcGetOtherProductsRoot();
    if (!root) {
        return;
    }

    var $root = $(root);
    var nilChecked = $root.find('input[name="nothing_extra"]').is(':checked');
    var extras = [];

    $root.find('input[name="extra[]"]:checked').each(function() {
        extras.push(String($(this).val()));
    });

    var existing = {};
    try {
        existing = JSON.parse(localStorage.getItem(projectPlanKey) || '{}') || {};
    } catch (eRead) {
        existing = {};
    }

    delete existing['extra[]'];

    if (nilChecked) {
        existing.nothing_extra = 'nothing';
        delete existing.extra;
    } else if (extras.length) {
        existing.extra = extras;
        delete existing.nothing_extra;
    } else {
        delete existing.extra;
        delete existing.nothing_extra;
    }

    localStorage.setItem(projectPlanKey, JSON.stringify(existing));
}

/**
 * Restore NIL vs extra[] from project-plans localStorage into the modal.
 */
function fcRestoreOtherProductsFromProjectPlans() {
    var root = fcGetOtherProductsRoot();
    if (!root) {
        return;
    }

    var $root = $(root);
    var formData = {};

    try {
        formData = JSON.parse(localStorage.getItem(projectPlanKey) || '{}') || {};
    } catch (eRead) {
        formData = {};
    }

    var extras = fcParseOtherProductsExtraValues(
        formData['extra[]'] !== undefined ? formData['extra[]'] : formData.extra
    );
    var nilSelected = String(formData.nothing_extra || '') === 'nothing';

    if (!nilSelected && extras.length === 0 && String(formData.extra || '') === 'nothing') {
        nilSelected = true;
    }

    $root.find('input[name="extra[]"]').prop('checked', false);
    $root.find('input[name="nothing_extra"]').prop('checked', false);

    if (nilSelected) {
        $root.find('input[name="nothing_extra"]').prop('checked', true);
    } else {
        extras.forEach(function(val) {
            $root.find('input[name="extra[]"][value="' + val + '"]').prop('checked', true);
        });
    }

    fcSyncOtherProductsSelectionUi(root);
}

/**
 * Save form data to local storage whenever a field changes
 */
function saveFormData() {

    const formData = {};
    const otherFormFields = formDownload ? formDownload.querySelectorAll("[name=notes]") : '';
    const formFields = submitModal.querySelectorAll("[name]");
    let formFieldsArray = [...formFields, ...otherFormFields];

    $(formFieldsArray).each(function(i, item) {

        var name = $(item).attr('name'),
            type = $(item).attr('type'),
            val = $(item).val();

        if (type === "checkbox") {
            formData[name] = formData[name] || [];

            if ($(item).is(':checked')) {
                formData[name].push(val);
            }

        } else if (type === "radio") {

            if ($(item).is(':checked')) {
                formData[name] = val;
            }

            if ($('[name="' + name + '"]').length == 1) {
                if (!$(item).is(':checked')) {
                    formData[name] = '';
                }
            }

        } else {

            if (name === 'mobile' && val != null && val !== '') {
                val = fcNormalizeMobileForStorage(val);
            }
            formData[name] = val;
        }

    });

    updateOrCreateObjectInLocalStorage(projectPlanKey, formData);
    fcPersistOtherProductsToProjectPlans();
}

//----------------------------------------------------------------------------------

// Add event listeners TO form elements inside the submit-modal div
if (submitModal) {
    $(document).on('keyup', 'textarea, input', saveFormData);
    submitModal.addEventListener("change", saveFormData);
}

//----------------------------------------------------------------------------------

/**
 * Restore data from local storage when the page loads
 */
function restoreFormData() {
    const formData = JSON.parse(localStorage.getItem(projectPlanKey)) || {};
    for (const key in formData) {
        const input = document.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === "checkbox") {
                let selectedValues = formData[key];
                if (!Array.isArray(selectedValues)) {
                    if (typeof selectedValues === 'string') {
                        selectedValues = selectedValues
                            .split(',')
                            .map(item => item.trim())
                            .filter(Boolean);
                    } else if (selectedValues === undefined || selectedValues === null) {
                        selectedValues = [];
                    } else {
                        selectedValues = [String(selectedValues).trim()];
                    }
                } else {
                    selectedValues = selectedValues.map(item => String(item).trim());
                }

                if (Array.isArray(selectedValues)) {
                    for (let i = 0; i < selectedValues.length; i++) {
                        var checkBox = document.querySelector('input[type=checkbox][name="' + key + '"][value="' + selectedValues[i] + '"]');
                        if (checkBox) checkBox.checked = true;
                    }
                } else {
                    var checkBox = document.querySelector('input[type=checkbox][name="' + key + '"][value="' + formData[key] + '"]');
                    checkBox.checked = true;
                }
            } else if (input.type === "radio") {
                var radioBtn = document.querySelector('input[type=radio][name="' + key + '"][value="' + formData[key] + '"]');
                if (radioBtn) radioBtn.checked = true;
            } else if (input.type === "select-one") {
                input.value = formData[key];
            } else {
                input.value = key === 'mobile'
                    ? fcNormalizeMobileForStorage(formData[key])
                    : formData[key];
            }
        }
    }

    fcRestoreOtherProductsFromProjectPlans();

    // Values above were set without events; float the labels of now-filled fields.
    if (typeof fcSyncDownloadPlansFloatingLabels === 'function') {
        fcSyncDownloadPlansFloatingLabels();
    }
}

//----------------------------------------------------------------------------------

/**
 * Keys equal to basePrefix or starting with basePrefix + '-' (avoids custom_fence-10 when targeting custom_fence-1).
 */
function fcListLocalStorageKeysWithBasePrefix(basePrefix) {
    var out = [];
    for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i);
        if (!k) {
            continue;
        }
        if (k === basePrefix || k.indexOf(basePrefix + '-') === 0) {
            out.push(k);
        }
    }
    return out;
}

/**
 * Distinct numeric rungs for keys like prefix + N or prefix + N + '-' + … (N integer).
 */
function fcPlannerStorageNumericRungs(prefix) {
    var escaped = prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    var re = new RegExp('^' + escaped + '(\\d+)(?:$|-)');
    var seen = {};
    for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i);
        if (!k) {
            continue;
        }
        var m = k.match(re);
        if (m) {
            seen[parseInt(m[1], 10)] = true;
        }
    }
    return Object.keys(seen)
        .map(function(x) {
            return parseInt(x, 10);
        })
        .sort(function(a, b) {
            return a - b;
        });
}

/**
 * Cart uses two shapes (legacy session merge vs cart-items.js):
 * - Flat blob: `cart_items-{sectionIndex}` (0-based, same as custom_fence index) from session reload.
 * - Slug BOM: `cart_items-{sectionIndex + 1}-{slug}` from FENCES.cartItems.init (1-based bucket).
 * Only the deleted section must be removed — do not remove `cart_items-{d+1}` flat (that is the next section).
 */
function fcRemovePlannerStorageForDeletedSection(deletedTabIndex0) {
    var d = parseInt(deletedTabIndex0, 10);
    if (!Number.isFinite(d) || d < 0) {
        return;
    }
    fcListLocalStorageKeysWithBasePrefix('custom_fence-' + d).forEach(function(k) {
        localStorage.removeItem(k);
    });

    localStorage.removeItem('cart_items-' + d);

    var slugBucketPrefix = 'cart_items-' + (d + 1) + '-';
    var slugKeys = [];
    for (var si = 0; si < localStorage.length; si++) {
        var sk = localStorage.key(si);
        if (sk && sk.indexOf(slugBucketPrefix) === 0) {
            slugKeys.push(sk);
        }
    }
    slugKeys.forEach(function(k) {
        localStorage.removeItem(k);
    });
}

function fcListCartFlatNumericIndices() {
    var seen = {};
    for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i);
        if (!k) {
            continue;
        }
        var m = /^cart_items-(\d+)$/.exec(k);
        if (m) {
            seen[parseInt(m[1], 10)] = true;
        }
    }
    return Object.keys(seen)
        .map(function(x) {
            return parseInt(x, 10);
        })
        .sort(function(a, b) {
            return a - b;
        });
}

function fcRenameCartFlatKeysAfterSectionDelete(deletedTabIndex0) {
    var d = parseInt(deletedTabIndex0, 10);
    if (!Number.isFinite(d) || d < 0) {
        return;
    }
    fcListCartFlatNumericIndices()
        .filter(function(n) {
            return n > d;
        })
        .sort(function(a, b) {
            return a - b;
        })
        .forEach(function(n) {
            var oldK = 'cart_items-' + n;
            var newK = 'cart_items-' + (n - 1);
            var val = localStorage.getItem(oldK);
            if (val != null) {
                localStorage.removeItem(oldK);
                localStorage.setItem(newK, val);
            }
        });
}

function fcRenameCartSlugBucketsAfterSectionDelete(deletedTabIndex0) {
    var d = parseInt(deletedTabIndex0, 10);
    if (!Number.isFinite(d) || d < 0) {
        return;
    }
    var buckets = {};
    for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i);
        if (!k) {
            continue;
        }
        var m = /^cart_items-(\d+)-(.+)/.exec(k);
        if (m) {
            buckets[parseInt(m[1], 10)] = true;
        }
    }
    Object.keys(buckets)
        .map(function(x) {
            return parseInt(x, 10);
        })
        .filter(function(b) {
            return b > d + 1;
        })
        .sort(function(a, b) {
            return a - b;
        })
        .forEach(function(b) {
            var oldPrefix = 'cart_items-' + b + '-';
            var newPrefix = 'cart_items-' + (b - 1) + '-';
            var keys = [];
            for (var j = 0; j < localStorage.length; j++) {
                var ck = localStorage.key(j);
                if (ck && ck.indexOf(oldPrefix) === 0) {
                    keys.push(ck);
                }
            }
            keys.forEach(function(key) {
                var rest = key.slice(oldPrefix.length);
                var val = localStorage.getItem(key);
                localStorage.removeItem(key);
                localStorage.setItem(newPrefix + rest, val);
            });
        });
}

/**
 * Rename all keys under oldPrefix to newPrefix; optionally set custom_fence base row [0].tab.
 */
function fcRenameLocalStorageKeyPrefix(oldPrefix, newPrefix, fixCustomFenceTab0) {
    var keys = fcListLocalStorageKeysWithBasePrefix(oldPrefix);
    keys.forEach(function(k) {
        var val = localStorage.getItem(k);
        var rest = k === oldPrefix ? '' : k.slice(oldPrefix.length);
        var newKey = newPrefix + rest;
        localStorage.removeItem(k);
        if (fixCustomFenceTab0 != null && /^custom_fence-\d+$/.test(newKey) && val) {
            try {
                var parsed = JSON.parse(val);
                if (parsed && parsed[0]) {
                    parsed[0].tab = fixCustomFenceTab0;
                    val = JSON.stringify(parsed);
                }
            } catch (e) {}
        }
        localStorage.setItem(newKey, val);
    });
}

/**
 * Snapshot planner fence + cart lines in localStorage (indices only; not project-plans).
 */
function fcCapturePlannerFenceCartStorageSnapshot() {
    var snap = {
        sectionRaw: localStorage.getItem('custom_fence-section'),
        kv: {}
    };
    var reCf = /^custom_fence-\d+(?:$|-)/;
    var reCart = /^cart_items-\d+(?:$|-)/;
    for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i);
        if (!k) {
            continue;
        }
        if (reCf.test(k) || reCart.test(k)) {
            snap.kv[k] = localStorage.getItem(k);
        }
    }
    return snap;
}

function fcClearPlannerFenceCartStorageKeys() {
    var reCf = /^custom_fence-\d+(?:$|-)/;
    var reCart = /^cart_items-\d+(?:$|-)/;
    var toRemove = [];
    for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i);
        if (!k) {
            continue;
        }
        if (reCf.test(k) || reCart.test(k)) {
            toRemove.push(k);
        }
    }
    toRemove.forEach(function(k) {
        localStorage.removeItem(k);
    });
}

/** Replace all indexed custom_fence / cart_items keys with snapshot (used after stale session merge). */
function fcApplyPlannerFenceCartStorageSnapshot(snap) {
    if (!snap || !snap.kv) {
        return;
    }
    fcClearPlannerFenceCartStorageKeys();
    Object.keys(snap.kv).forEach(function(k) {
        var v = snap.kv[k];
        if (v != null) {
            localStorage.setItem(k, v);
        }
    });
    if (snap.sectionRaw != null && snap.sectionRaw !== '') {
        localStorage.setItem('custom_fence-section', snap.sectionRaw);
    }
}

function fcReindexPlannerStorageAfterSectionDelete(deletedTabIndex0) {
    var d = parseInt(deletedTabIndex0, 10);
    if (!Number.isFinite(d) || d < 0) {
        return;
    }

    fcRemovePlannerStorageForDeletedSection(d);

    // Ascending so custom_fence-(d+1) moves into freed custom_fence-d before higher indices shift.
    fcPlannerStorageNumericRungs('custom_fence-')
        .filter(function(n) {
            return n > d;
        })
        .sort(function(a, b) {
            return a - b;
        })
        .forEach(function(n) {
            var newIdx = n - 1;
            fcRenameLocalStorageKeyPrefix('custom_fence-' + n, 'custom_fence-' + newIdx, newIdx);
        });

    fcRenameCartFlatKeysAfterSectionDelete(d);
    fcRenameCartSlugBucketsAfterSectionDelete(d);
}

//----------------------------------------------------------------------------------

/**
 * Delete custom_fence-{idx} and custom_fence-{idx}-{styleIdx} instances in localStorage
 * @param {string} substring 
 */
function deleteAllEntriesBySubstring(substring) {
    // Use a while loop to delete all matching entries
    while (true) {
        // Find the index of the first matching key
        const index = Object.keys(localStorage).findIndex(key => key.indexOf(substring) !== -1);

        // If no more matching keys are found, exit the loop
        if (index === -1) {
            break;
        }
        // Get the matching key and delete the entry
        const matchingKey = Object.keys(localStorage)[index];
        localStorage.removeItem(matchingKey);
    }
}

//----------------------------------------------------------------------------------

/** Canonical planner slug (legacy `slat_fence` → `slat`). */
function normalizeFenceStyleSlug(slug) {
    return slug === 'slat_fence' ? 'slat' : slug;
}

/** Human-readable fence style title for a planner / project-plan section (localStorage + fc_data). */
function fcFenceSectionStyleTitle(tabIdx0) {
    var raw = localStorage.getItem('custom_fence-' + tabIdx0);
    if (!raw) {
        return '';
    }
    try {
        var tab = JSON.parse(raw);
        if (!tab || !tab[0]) {
            return '';
        }
        var slugRaw = tab[0].style || tab[0].fence || '';
        if (!slugRaw) {
            return '';
        }
        var slug =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(String(slugRaw))
                : String(slugRaw);
        if (typeof fc_data !== 'undefined' && fc_data[slug] && fc_data[slug].title) {
            return fc_data[slug].title;
        }
        if (typeof fc_data !== 'undefined' && fc_data[String(slugRaw)] && fc_data[String(slugRaw)].title) {
            return fc_data[String(slugRaw)].title;
        }
        return String(slugRaw).replace(/_/g, ' ');
    } catch (e) {
        return '';
    }
}

var FC_SECTION_TAB_STATUS_TOOLTIPS = {
    complete: 'This section is complete',
    incomplete: 'Missing overall length or Calculate not run'
};

var FC_STEP4_COLOR_STATUS_TOOLTIPS = {
    complete: 'Colour selected',
    incomplete: 'Please select a colour'
};

function fcPlannerSectionTabStatusHtml() {
    return (
        '<span class="fc-section-tab-status fc-section-tab-status--incomplete" aria-hidden="true">' +
        '<i class="fa-regular fa-circle fc-section-tab-status__icon"></i></span>'
    );
}

function fcApplyPlannerSectionTabStatusTooltip($status, tooltip) {
    if (!$status || !$status.length) {
        return;
    }

    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var existing = bootstrap.Tooltip.getInstance($status[0]);
        if (existing) {
            existing.dispose();
        }
    }

    $status.removeAttr('title data-bs-toggle data-bs-placement data-bs-container aria-label');

    if (!tooltip) {
        $status.attr('aria-hidden', 'true');
        return;
    }

    $status
        .attr('aria-label', tooltip)
        .removeAttr('aria-hidden')
        .attr({
            title: tooltip,
            'data-bs-toggle': 'tooltip',
            'data-bs-placement': 'top',
            'data-bs-container': 'body'
        });

    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        new bootstrap.Tooltip($status[0], {
            trigger: 'hover focus',
            container: 'body',
            boundary: 'viewport'
        });
    }
}

function fcPlannerSectionHasOverallLength(tabIdx0) {
    try {
        var raw = localStorage.getItem('custom_fence-' + tabIdx0);
        if (!raw) {
            return false;
        }
        var tabRow = JSON.parse(raw);
        if (!tabRow || !tabRow[0]) {
            return false;
        }
        var slug = tabRow[0].style || tabRow[0].fence || '';
        if (typeof normalizeFenceStyleSlug === 'function') {
            slug = normalizeFenceStyleSlug(slug);
        }
        var cv =
            typeof fcReadCalculateValueForStyle === 'function'
                ? fcReadCalculateValueForStyle(tabRow[0], slug)
                : tabRow[0].calculateValue;
        var n = parseInt(String(cv != null ? cv : '').replace(/,/g, ''), 10);
        return Number.isFinite(n) && n > 0 && n !== 9999;
    } catch (e) {
        return false;
    }
}

function fcEnsurePlannerTabSectionStatusEl($tab) {
    var $name = $tab.find('.fencing-tab-name').first();
    if (!$name.length) {
        return $();
    }
    var $status = $name.find('.fc-section-tab-status').first();
    if (!$status.length) {
        $status = $(fcPlannerSectionTabStatusHtml());
        $name.prepend($status);
    }
    return $status;
}

function fcSyncPlannerTabSectionStatus($tab, tabIdx0) {
    if (!$tab || !$tab.length || !$('.fc-planner-page').length) {
        return;
    }
    if (!Number.isFinite(tabIdx0) || tabIdx0 < 0) {
        tabIdx0 = $tab.index();
    }

    var hasLength = fcPlannerSectionHasOverallLength(tabIdx0);
    var isIncomplete =
        typeof fcFenceSectionIncompleteFromStorage === 'function'
            ? fcFenceSectionIncompleteFromStorage(tabIdx0)
            : !hasLength;
    var $status = fcEnsurePlannerTabSectionStatusEl($tab);
    var $icon = $status.find('.fc-section-tab-status__icon');

    $tab.removeClass('incomplete-section');
    $status.removeClass(
        'fc-section-tab-status--complete fc-section-tab-status--incomplete fc-section-tab-status--error'
    );

    if (hasLength) {
        $status.addClass('fc-section-tab-status--complete');
        $icon.attr('class', 'fc-section-tab-status__icon fa-solid fa-circle-check');
        fcApplyPlannerSectionTabStatusTooltip($status, FC_SECTION_TAB_STATUS_TOOLTIPS.complete);
        return;
    }

    if (isIncomplete) {
        $status.addClass('fc-section-tab-status--error');
        $icon.attr('class', 'fc-section-tab-status__icon fa-solid fa-circle-exclamation');
        fcApplyPlannerSectionTabStatusTooltip($status, FC_SECTION_TAB_STATUS_TOOLTIPS.incomplete);
        return;
    }

    $status.addClass('fc-section-tab-status--incomplete');
    $icon.attr('class', 'fc-section-tab-status__icon fa-regular fa-circle');
    fcApplyPlannerSectionTabStatusTooltip($status, null);
}

function fcSyncAllPlannerSectionTabStatuses() {
    if (!$('.fc-planner-page').length) {
        return;
    }
    var $tabs =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : $(FENCES.el.fencingTab);
    $tabs.each(function(i) {
        fcSyncPlannerTabSectionStatus($(this), i);
    });
}

function fcSyncPlannerTabFenceStyle($tab, tabIdx0, titleOverride) {
    if (!$tab || !$tab.length) {
        return;
    }

    var $styleEl = $tab.find('.ftm-fence-style');
    if ($styleEl.length) {
        var title =
            titleOverride !== undefined && titleOverride !== null
                ? String(titleOverride).trim()
                : fcFenceSectionStyleTitle(tabIdx0);

        if (title) {
            $styleEl.text(title).prop('hidden', false).show();
        } else {
            $styleEl.empty().prop('hidden', true).hide();
        }
    }

    fcSyncPlannerTabSectionStatus($tab, tabIdx0);
}

/**
 * First line of panel size label (Step 3 / project plan): e.g. &lt;span class="fc-panel-fence-height"&gt;1200H&lt;/span&gt;&lt;br&gt;
 * Slat: calculated panel height (max height → slat rows → panel H); Barr uses fence height from calc.
 */
function fcGetPanelLabelFenceHeightLineHtml(slug, calc, opts) {
    opts = opts || {};
    var canon = normalizeFenceStyleSlug(slug);
    if (typeof SlatFence !== 'undefined' && typeof SlatFence.getPanelLabelFenceHeightLineHtml === 'function') {
        var slatLine = SlatFence.getPanelLabelFenceHeightLineHtml(canon, calc, opts);
        if (slatLine) {
            return slatLine;
        }
    }
    if (canon !== 'barr') {
        return '';
    }
    var raw = calc && calc.fence_size ? calc.fence_size.height : '';
    var h = Math.round(Number(String(raw).replace(/,/g, '')));
    if (!Number.isFinite(h) || h <= 0) {
        return '';
    }
    return '<span class="fc-panel-fence-height">' + String(h) + 'H</span><br>';
}

/**
 * Tubular group-b Fence Height (mm): live Step 2 → calc → saved tab fields.
 * Not used for Slat / Slat Infill (those use max_fence_height + slat scaling).
 */
function fcResolveGroupBFenceHeightMm(calc, tabInfo, slug) {
    if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(slug)) {
        return 0;
    }

    var fromCalc = parseInt(String(calc?.fence_size?.height ?? '').replace(/,/g, ''), 10);
    if (Number.isFinite(fromCalc) && fromCalc > 0) {
        return fromCalc;
    }

    try {
        var el = document.querySelector('[data-section="2"] [name="fence_height"]');
        if (el && el.value) {
            var dom = parseInt(String(el.value).replace(/,/g, ''), 10);
            if (Number.isFinite(dom) && dom > 0) {
                return dom;
            }
        }
    } catch (eDom) {}

    var tab0 = tabInfo?.[0];
    if (tab0 && typeof fcReadTabRowStep2Field === 'function') {
        var slugNorm = slug || tab0.fence || tab0.style || '';
        var stored = parseInt(
            String(fcReadTabRowStep2Field(tab0, slugNorm, 'fence_height') || '').replace(/,/g, ''),
            10
        );
        if (Number.isFinite(stored) && stored > 0) {
            return stored;
        }
    }

    return 0;
}

function fcResolveGlassPoolPanelContainer($root, tab) {
    if (!$root || !$root.length) {
        var $byTab = $('#pp-' + (tab != null ? tab : 0) + ' .fencing-panel-container').first();
        if ($byTab.length) {
            return $byTab;
        }
        return typeof FENCES !== 'undefined' && FENCES.el && FENCES.el.fencingPanelContainer
            ? $(FENCES.el.fencingPanelContainer).first()
            : $();
    }

    var $el = $root.jquery ? $root : $($root);
    if ($el.hasClass('fencing-panel-container')) {
        return $el.first();
    }

    var $fc = $el.find('.fencing-panel-container').first();
    return $fc.length ? $fc : $el.first();
}

/**
 * Glass pool: move STD/SC hinge panel bundle (spacing + panel + post) next to the gate when
 * switchPanel left other panels in between (common on project-plan multi-section render).
 */
function fcEnsureGlassPoolHingeAdjacentToGate($fc) {
    if (!$fc || !$fc.length || String($fc.attr('data-type') || '') !== 'glass_pool') {
        return;
    }

    var $hinge = $fc.find('.extra-panel-item.hinge-panel, .extra-panel-item.hinge-panel-alt');
    var $gate = $fc.find('.fencing-panel-gate');
    if (!$hinge.length || !$gate.length) {
        return;
    }

    $hinge.removeClass('hinge-panel-alt').addClass('hinge-panel');

    function hingeBundleNodes() {
        var $hingeLeadSp = $hinge.prev('.fencing-panel-spacing-number');
        var $hingePost = $hinge.next('.panel-post');
        var $nodes = $();
        if ($hingeLeadSp.length) {
            $nodes = $nodes.add($hingeLeadSp);
        }
        $nodes = $nodes.add($hinge);
        if ($hingePost.length) {
            $nodes = $nodes.add($hingePost);
        }
        return $nodes;
    }

    function hasForeignPanelBetween($from, $to) {
        return $from.nextUntil($to).filter('.fencing-panel-item:not(.extra-panel-item)').length > 0;
    }

    if ($gate.hasClass('panel-gate-left') && ($hinge.index() > $gate.index() || hasForeignPanelBetween($hinge, $gate))) {
        var $insertBefore = $gate.prevAll('.fencing-panel-spacing-number').first();
        if (!$insertBefore.length) {
            $insertBefore = $gate;
        }
        hingeBundleNodes().insertBefore($insertBefore);
    } else if ($gate.hasClass('panel-gate-right') && ($hinge.index() < $gate.index() || hasForeignPanelBetween($gate, $hinge))) {
        var $insertAfter = $gate.nextAll('.panel-post').first();
        if (!$insertAfter.length) {
            $insertAfter = $gate.nextAll('.fencing-panel-spacing-number').first();
        }
        if (!$insertAfter.length) {
            $insertAfter = $gate;
        }
        hingeBundleNodes().insertAfter($insertAfter);
    }
}

/**
 * Glass pool: uniform display width for all panel gap strips (mm / 10 at base_margin 0.1).
 */
function fcApplyGlassPoolPanelSpacingWidths(tab, spacingMm, $root) {
    var gapMm = parseInt(String(spacingMm == null ? '' : spacingMm).replace(/,/g, ''), 10);
    if (!Number.isFinite(gapMm) || gapMm <= 0) {
        return;
    }

    var gapPx = gapMm / 10;
    var $fc = fcResolveGlassPoolPanelContainer($root, tab);

    if (!$fc.length || String($fc.attr('data-type') || '') !== 'glass_pool') {
        return;
    }

    $fc
        .find('.fencing-panel-spacing-number:not(.PTP90):not(.PTPA):not(.PTW)')
        .css('width', gapPx);
}

/**
 * Glass pool: minimum-length (zero regular panel) layouts render the hinge panel hard
 * against the gate with no spacing strip between them, so the hinge-side gap (e.g. 10mm)
 * vanished from the diagram while its width was still counted in the overall - reached
 * routinely now that an under-entered Overall Length auto-adjusts to the assembly minimum.
 * Recreate the strip; near_gate_spacing() then classes it and the hinge-type labelers keep
 * its value current like any other gate-adjacent strip.
 */
function fcEnsureGlassPoolHingeGateGapLabel($fc, tab) {
    if (!$fc || !$fc.length || String($fc.attr('data-type') || '') !== 'glass_pool') {
        return;
    }

    var $gate = $fc.find('.fencing-panel-gate');
    var $hinge = $fc.find('.extra-panel-item.hinge-panel, .extra-panel-item.hinge-panel-alt').first();
    if (!$gate.length || !$hinge.length) {
        return;
    }

    var gateIsLeft = $gate.hasClass('panel-gate-left');
    var $from = gateIsLeft ? $hinge : $gate;
    var $to = gateIsLeft ? $gate : $hinge;
    if ($from.index() < 0 || $to.index() < 0 || $from.index() >= $to.index()) {
        return;
    }

    var hingeMm = 10;
    try {
        var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData(undefined, tab) : null;
        var gateRows = (fd && fd.info ? fd.info : []).filter(function(r) {
            return r && r.control_key === 'gate';
        });
        var gaps =
            typeof FENCE !== 'undefined' && typeof FENCE.resolveGlassPoolHingeGapsMm === 'function'
                ? FENCE.resolveGlassPoolHingeGapsMm(fd, gateRows)
                : null;
        if (gaps && Number(gaps.hinge) > 0) {
            hingeMm = Number(gaps.hinge);
        }
    } catch (eGap) {}

    // This junction is always the hinge gap. Re-assert the value even when a strip already
    // exists: a re-render (e.g. the auto-adjust's second calculate) can rebuild it with the
    // generic panel-gap label.
    var $junction = $from.nextUntil($to).filter('.fencing-panel-spacing-number').first();
    if ($junction.length) {
        var $span = $junction.find('span:not(.fs-clamp)').first();
        if ($span.length) {
            $span.html(hingeMm);
        } else {
            $junction.append('<span>' + hingeMm + '</span>');
        }
        return;
    }

    $('<div class="fencing-panel-spacing-number fc-glass-hinge-gap-label"><span>' + hingeMm + '</span></div>')
        .insertBefore($to);
}

/**
 * Glass pool: two gap strips with no panel between them (latch gap + end gap on minimum
 * layouts) sit a few px apart, so their mm labels fused into one number ("9" + "50" read
 * as "950"). Drop every second label of an adjacent run one step down so both stay legible.
 */
function fcStaggerGlassPoolAdjacentGapLabels($fc) {
    var $strips = $fc.find('.fencing-panel-spacing-number');
    $strips.removeClass('fc-gap-label-stagger');

    var prevRect = null;
    var prevStaggered = false;
    $strips.each(function() {
        var rect = this.getBoundingClientRect();
        if (!rect.width) {
            return; // hidden container - geometry unknown, leave labels alone
        }
        if (prevRect && !prevStaggered && rect.left - prevRect.right < 14) {
            $(this).addClass('fc-gap-label-stagger');
            prevStaggered = true;
        } else {
            prevStaggered = false;
        }
        prevRect = rect;
    });
}

/**
 * Glass pool: run hinge adjacency, uniform gap widths, and gate post cleanup after render.
 */
function fcFinalizeGlassPoolPanelLayout($fc, spacingMm, tab) {
    $fc = fcResolveGlassPoolPanelContainer($fc, tab);
    if (!$fc.length || String($fc.attr('data-type') || '') !== 'glass_pool') {
        return;
    }

    fcEnsureGlassPoolHingeAdjacentToGate($fc);
    fcEnsureGlassPoolHingeGateGapLabel($fc, tab);
    fcApplyGlassPoolPanelSpacingWidths(tab, spacingMm, $fc);
    fcNormalizeGlassPoolGateAdjacentPosts($fc);
    fcStaggerGlassPoolAdjacentGapLabels($fc);
}

/**
 * Glass pool: gate templates include an extra post beside the gap strip; hide it when a hinge
 * panel is present so gate/hinge spacing matches panel-to-panel spacing.
 */
function fcNormalizeGlassPoolGateAdjacentPosts($fc) {
    if (!$fc || !$fc.length || String($fc.attr('data-type') || '') !== 'glass_pool') {
        return;
    }

    if (!$fc.find('.extra-panel-item.hinge-panel').length) {
        $fc.find('.panel-post.fc-glass-gate-dup-post').removeClass('fc-glass-gate-dup-post');
        return;
    }

    $fc.find('.panel-post.fc-glass-gate-dup-post').removeClass('fc-glass-gate-dup-post');

    var $gate = $fc.find('.fencing-panel-gate');
    if (!$gate.length) {
        return;
    }

    if ($gate.hasClass('panel-gate-left')) {
        var $leadPost = $gate.prev('.panel-post');
        if ($leadPost.length && $gate.prev().prev('.fencing-panel-spacing-number').length) {
            $leadPost.addClass('fc-glass-gate-dup-post');
        }
    } else if ($gate.hasClass('panel-gate-right')) {
        var $trailPost = $gate.next('.panel-post');
        if ($trailPost.length && $trailPost.next('.fencing-panel-spacing-number').length) {
            $trailPost.addClass('fc-glass-gate-dup-post');
        }
    }
}

/** @deprecated Use fcApplyGlassPoolPanelSpacingWidths — kept as alias for older call sites. */
function fcApplyGlassPoolGateHingeSpacingWidths(tab, ght, $root, spacingMm) {
    var gap = spacingMm;
    if (gap == null && ght && ght.gap && ght.gap.hinge) {
        gap = ght.gap.hinge;
    }
    fcApplyGlassPoolPanelSpacingWidths(tab, gap, $root);
}

/**
 * Step 3 / project plan: scale panel + post heights for tubular styles (Barr, Flat Top, etc.) from fence height.
 */
function fcApplyGroupBFenceDisplayHeights(calc, $scope, opts) {
    opts = opts || {};
    var slug = opts.slug || '';
    if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(slug)) {
        return;
    }

    var $root = $scope && $scope.length ? $scope : $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingPanelContainer : '.fencing-panel-container');

    if (typeof SlatFence !== 'undefined' && typeof SlatFence.resetSlatDisplayScaling === 'function') {
        SlatFence.resetSlatDisplayScaling($root.length ? $root : null);
    }

    var heightMm = fcResolveGroupBFenceHeightMm(calc, opts.tabInfo || null, slug);
    if (!Number.isFinite(heightMm) || heightMm <= 0 || !$root.length) {
        return;
    }

    var baseMargin = typeof FENCE !== 'undefined' ? FENCE.get('item', 'base_margin') : 0.1;
    var fenceHeightPx = heightMm * baseMargin;

    $root
        .find('.fencing-panel-item:not(.fencing-raked-panel), .short-panel-item, .fencing-offcut .offcut-body')
        .css({ height: fenceHeightPx });

    $root.find('.panel-post.opt-1, .panel-post.opt-1-1').css({
        height: fenceHeightPx + 25,
        minHeight: fenceHeightPx + 25
    });
    $root.find('.panel-post.opt-2, .panel-post.opt-2-1').css({
        height: fenceHeightPx + 35,
        minHeight: fenceHeightPx + 35
    });
}

function fcHasLeftRakedPanel() {
    return $('#panel-item-left-raked').length > 0;
}

function fcHasRightRakedPanel() {
    return $('#panel-item-right-raked').length > 0;
}

/** Configured left raked panel within a fence diagram scope (not empty placeholder). */
function fcScopeHasConfiguredLeftRaked($scope) {
    if (!$scope || !$scope.length) {
        return fcHasLeftRakedPanel();
    }
    return $scope.find('#panel-item-left-raked').length > 0;
}

/** Configured right raked panel within a fence diagram scope (not empty placeholder). */
function fcScopeHasConfiguredRightRaked($scope) {
    if (!$scope || !$scope.length) {
        return fcHasRightRakedPanel();
    }
    return $scope.find('#panel-item-right-raked').length > 0;
}

/**
 * Gate + optional hinge unit at first/last bay (regular panels only between ends).
 */
function fcComputeGateMoveBoundary($scope, $gate) {
    $scope =
        $scope && $scope.length
            ? $scope
            : typeof FENCES !== 'undefined' && FENCES.el && FENCES.el.fencingPanelContainer
              ? $(FENCES.el.fencingPanelContainer).filter(':visible').first()
              : $('.fencing-panel-container:visible').first();
    $gate =
        $gate && $gate.length
            ? $gate
            : $scope.find('.fencing-panel-gate').first().length
              ? $scope.find('.fencing-panel-gate').first()
              : $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingPanelGate : '.fencing-panel-gate').first();

    if (!$gate.length) {
        return { atFirst: false, atLast: false };
    }

    var $hinge = $scope.find('.extra-panel-item.hinge-panel, .extra-panel-item.hinge-panel-alt, .extra-panel-item').first();
    var leadIdx = $hinge.length ? Math.min($gate.index(), $hinge.index()) : $gate.index();
    var trailIdx = $hinge.length ? Math.max($gate.index(), $hinge.index()) : $gate.index();
    var $regular = $scope.find('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)');

    var gateAtFirst = !$regular.length || $regular.first().index() > leadIdx;
    var gateAtLast = !$regular.length || $regular.last().index() < trailIdx;

    var hasLeftRaked = fcScopeHasConfiguredLeftRaked($scope);
    var hasRightRaked = fcScopeHasConfiguredRightRaked($scope);

    return {
        gateAtFirst: gateAtFirst,
        gateAtLast: gateAtLast,
        hasLeftRaked: hasLeftRaked,
        hasRightRaked: hasRightRaked
    };
}

function fcGetFirstRegularPanel$() {
    var sel = typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.panelItem : '.panel-item';
    return $(sel).filter(':not(.fencing-raked-panel,.fencing-panel-gate,.extra-panel-item)').first();
}

function fcGetLastRegularPanel$() {
    var sel = typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.panelItem : '.panel-item';
    return $(sel).filter(':not(.fencing-raked-panel,.fencing-panel-gate,.extra-panel-item)').last();
}

/** Gate is at the first bay (after left raked when present). */
function fcGateIsAtFirstPosition($gate) {
    $gate = $gate && $gate.length ? $gate : $(typeof FENCES !== 'undefined' ? FENCES.el.fencingPanelGate : '.fencing-panel-gate');
    if (!$gate.length) {
        return false;
    }

    var $scope = $gate.closest('.fencing-panel-container');
    if ($scope.length && String($scope.attr('data-type') || '') === 'glass_pool') {
        return !!fcComputeGateMoveBoundary($scope, $gate).gateAtFirst;
    }

    var $firstPanel = fcGetFirstRegularPanel$();
    if (!$firstPanel.length) {
        return false;
    }

    if (fcHasLeftRakedPanel()) {
        var $leftRaked = $('#panel-item-left-raked');
        if ($leftRaked.length) {
            var $hinge = $gate.closest('.fencing-panel-container').find('.extra-panel-item').first();
            var leadIdx = $hinge.length ? Math.min($gate.index(), $hinge.index()) : $gate.index();
            return leadIdx > $leftRaked.index() && $firstPanel.index() > leadIdx;
        }
    }

    return $gate.index() < $firstPanel.index();
}

/** Gate is at the last bay (before right raked when present). */
function fcGateIsAtLastPosition($gate) {
    $gate = $gate && $gate.length ? $gate : $(typeof FENCES !== 'undefined' ? FENCES.el.fencingPanelGate : '.fencing-panel-gate');
    if (!$gate.length) {
        return false;
    }

    var $scope = $gate.closest('.fencing-panel-container');
    if ($scope.length && String($scope.attr('data-type') || '') === 'glass_pool') {
        return !!fcComputeGateMoveBoundary($scope, $gate).gateAtLast;
    }

    if (fcHasRightRakedPanel()) {
        var $rightRaked = $('#panel-item-right-raked');
        var $lastPanel = fcGetLastRegularPanel$();
        if ($rightRaked.length && $lastPanel.length) {
            var $hinge = $gate.closest('.fencing-panel-container').find('.extra-panel-item').first();
            var trailIdx = $hinge.length ? Math.max($gate.index(), $hinge.index()) : $gate.index();
            return trailIdx > $lastPanel.index() && trailIdx < $rightRaked.index();
        }
        if ($gate.hasClass('panel-gate-left')) {
            return $gate.next().is($('.right_raked-panel').first());
        }
        if ($gate.hasClass('panel-gate-right')) {
            return $gate.next().next().next().is($('.right_raked-panel').first());
        }
        return false;
    }

    return typeof HELPER !== 'undefined' && typeof HELPER.isGateOnLastPanel === 'function' && HELPER.isGateOnLastPanel();
}

/**
 * Glass pool with hinge: treat gate + hinge panel as one unit at the fence ends.
 * Returns null when not applicable.
 */
function fcGlassPoolGateHingeBoundaryState($scope, $gate) {
    $scope =
        $scope && $scope.length
            ? $scope
            : typeof fcResolveGlassPoolPanelContainer === 'function'
              ? fcResolveGlassPoolPanelContainer(null, null)
              : $('.fencing-panel-container:visible').first();
    $gate = $gate && $gate.length ? $gate : $scope.find('.fencing-panel-gate').first();

    if (
        !$scope.length ||
        String($scope.attr('data-type') || '') !== 'glass_pool' ||
        !$gate.length
    ) {
        return null;
    }

    var boundary = fcComputeGateMoveBoundary($scope, $gate);

    return {
        atFirst: boundary.gateAtFirst,
        atLast: boundary.gateAtLast,
        gateAtFirst: boundary.gateAtFirst,
        gateAtLast: boundary.gateAtLast,
        hasLeftRaked: boundary.hasLeftRaked,
        hasRightRaked: boundary.hasRightRaked
    };
}

/** Whether gate move controls should block toward the start/end of the run. */
function fcGateMoveBoundaryState($scope, $gate) {
    $scope =
        $scope && $scope.length
            ? $scope
            : typeof FENCES !== 'undefined' && FENCES.el && FENCES.el.fencingPanelContainer
              ? $(FENCES.el.fencingPanelContainer).filter(':visible').first()
              : $('.fencing-panel-container:visible').first();
    $gate =
        $gate && $gate.length
            ? $gate
            : $scope.find('.fencing-panel-gate').first().length
              ? $scope.find('.fencing-panel-gate').first()
              : $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingPanelGate : '.fencing-panel-gate').first();

    var boundary = fcComputeGateMoveBoundary($scope, $gate);

    return {
        atFirst: boundary.gateAtFirst,
        atLast: boundary.gateAtLast
    };
}

/**
 * Gate modal move buttons (First / Left / Right / Last) — enabled when the Step 3 diagram
 * has at least one regular panel bay (same count as `move_the_gate`). Gate-only runs stay disabled.
 */
function fcSyncGateMoveControlsState() {
    var $scope = $();
    if (typeof FENCES !== 'undefined' && FENCES.el && FENCES.el.fencingPanelContainer) {
        $scope = $(FENCES.el.fencingPanelContainer).filter(':visible').first();
    }
    if (!$scope.length) {
        $scope = $('.fencing-panel-container:visible').first();
    }

    var panelBayCount = $scope.find('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)').length;
    if (!panelBayCount && typeof getSelectedFenceData === 'function') {
        try {
            var fdScope = getSelectedFenceData();
            if (fdScope && fdScope.tab !== undefined && fdScope.tab !== null) {
                var $tabScope = $('#pp-' + fdScope.tab + ' .fencing-panel-container');
                panelBayCount = $tabScope.find('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)').length;
            }
        } catch (eScope) {}
    }

    var $moveBtns = $('.fc-move-post:not([data-move="delete"])');
    if (!$moveBtns.length) {
        return;
    }
    if (panelBayCount < 1) {
        $moveBtns.addClass('disabled');
        return;
    }

    $moveBtns.removeClass('disabled');

    var $gate = $scope.find('.fencing-panel-gate').first();
    if (!$gate.length) {
        $gate = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingPanelGate : '.fencing-panel-gate:visible').first();
    }

    var boundary =
        typeof fcComputeGateMoveBoundary === 'function'
            ? fcComputeGateMoveBoundary($scope, $gate)
            : { gateAtFirst: false, gateAtLast: false };

    try {
        if (typeof getSelectedFenceData === 'function') {
            var fdSync = getSelectedFenceData();
            var gdSync = (fdSync && fdSync.info || []).filter(function(item) {
                return item && item.control_key === 'gate';
            });
            if (
                fdSync &&
                fdSync.data &&
                fdSync.data.panel_group === 'a' &&
                typeof fcGlassPoolUsesPlacementDrive === 'function' &&
                fcGlassPoolUsesPlacementDrive(gdSync, $gate)
            ) {
                var plSync = fcReadGlassPoolGatePlacement(gdSync);
                var lbSync = fcGetGlassPoolGateMoveBoundary(plSync, panelBayCount);
                boundary.gateAtFirst = lbSync.atFirst;
                boundary.gateAtLast = lbSync.atLast;
            }
        }
    } catch (eSyncBound) {}

    $moveBtns.filter('[data-move="first"], [data-move="left"]').toggleClass('disabled', !!boundary.gateAtFirst);
    $moveBtns.filter('[data-move="last"], [data-move="right"]').toggleClass('disabled', !!boundary.gateAtLast);
}

/** Selectors that receive the shared button press animation. */
var FC_PRESSABLE_SELECTOR = [
    '.fc-pressable',
    '.btn:not(.btn-close)',
    '.btn-fc',
    '.fencing-qty-btn',
    '.fencing-qty-plus',
    '.fencing-qty-minus',
    '.fc-move-post',
    '.fc-zoom-reset',
    '.btn-fc-calculate',
    '.fc-planner-page .fc-step-4 .fc-select-color',
    '#submit-modal.fencing-modal--project-plans .fc-select-color',
    '#submit-modal .fc-other-products .fc-form-check-img'
].join(', ');

/** Clicks on inner labels/text/images — resolve press target via closest card. */
var FC_PRESSABLE_CHILD_SELECTOR = [
    '.fc-planner-page .fc-step-4 .fc-select-color *',
    '#submit-modal.fencing-modal--project-plans .fc-select-color *',
    '#submit-modal .fc-other-products .fc-form-check-img *'
].join(', ');

/** Brief pressed-state class for tap / click feedback on buttons. */
function fcTriggerPressEffect($el) {
    if (!$el || !$el.length) {
        return;
    }
    if ($el.is(':disabled') || $el.hasClass('disabled') || $el.prop('disabled')) {
        return;
    }
    if (
        $el.closest('.slick-dots').length ||
        $el.hasClass('slick-prev') ||
        $el.hasClass('slick-next') ||
        $el.hasClass('fencing-styles-arrow')
    ) {
        return;
    }

    $el.addClass('is-pressed');
    window.setTimeout(function() {
        $el.removeClass('is-pressed');
    }, 180);
}

function fcRemoveTrailingBayBeforeRightRaked() {
    var $rightRaked = $('.right_raked-panel').first();
    if (!$rightRaked.length) {
        return;
    }
    var $post = $rightRaked.prev();
    var $spacing = $post.prev();
    if (
        $post.length && $post.hasClass('panel-post') && !$post.hasClass('raked-panel-post') &&
        $spacing.length && $spacing.hasClass('fencing-panel-spacing-number')
    ) {
        $spacing.remove();
        $post.remove();
    }
}

/**
 * Glass pool with hinge panel: gate position is driven by stored placement (both left- and right-hand).
 */
function fcGlassPoolUsesPlacementDrive(gate_data, $gate) {
    var $scope =
        $gate && $gate.length
            ? $gate.closest('.fencing-panel-container')
            : typeof fcResolveGlassPoolPanelContainer === 'function'
              ? fcResolveGlassPoolPanelContainer(null, null)
              : $('.fencing-panel-container:visible').first();

    if (!$scope.length || String($scope.attr('data-type') || '') !== 'glass_pool') {
        return false;
    }
    return $scope.find('.extra-panel-item').length > 0;
}

/**
 * Glass pool left-hand: hinge + gate move together (subset of placement-drive styles).
 */
function fcGlassPoolShouldMoveGateHingeAsUnit(gate_data, $gate) {
    if (!fcGlassPoolUsesPlacementDrive(gate_data, $gate)) {
        return false;
    }
    if (fcGlassPoolIsLeftHandSwingFromGateData(gate_data)) {
        return true;
    }
    return !!($gate && $gate.length && $gate.hasClass('panel-gate-left'));
}

/** Read persisted glass-pool gate placement (-1 = first bay; N = after Nth regular panel). */
function fcReadGlassPoolGatePlacement(gate_data) {
    return fcReadGlassPoolLeftHandGatePlacement(gate_data);
}

/** Read persisted left-hand glass-pool gate placement (-1 = first bay). */
function fcReadGlassPoolLeftHandGatePlacement(gate_data) {
    var raw = gate_data?.[0]?.settings?.placement;
    if (raw === undefined || raw === null || String(raw).trim() === '') {
        return -1;
    }
    var p = parseInt(String(raw).replace(/,/g, ''), 10);
    return Number.isFinite(p) ? p : -1;
}

/** First/last bounds for glass pool from placement + regular panel count. */
function fcGetGlassPoolGateMoveBoundary(placement, regularCount) {
    return fcGetGlassPoolLeftHandMoveBoundary(placement, regularCount);
}

/** First/last bounds for left-hand glass pool from placement + regular panel count. */
function fcGetGlassPoolLeftHandMoveBoundary(placement, regularCount) {
    var p = Number.isFinite(parseInt(placement, 10)) ? parseInt(placement, 10) : -1;
    var n = parseInt(regularCount, 10) || 0;
    return {
        atFirst: p === -1,
        atLast: n > 0 && p >= n
    };
}

/**
 * Step glass-pool gate placement by one bay (left- or right-hand; panel-item-0 is hinge).
 */
function fcGlassPoolGatePlacementStep(current, move, regularCount) {
    return fcGlassPoolLeftHandGatePlacementStep(current, move, regularCount);
}

/**
 * Step left-hand glass-pool gate placement by one bay.
 * panel-item-0 is the hinge, so the first step right from -1 goes to 1 (after 1st regular panel).
 */
function fcGlassPoolLeftHandGatePlacementStep(current, move, regularCount) {
    var p = Number.isFinite(parseInt(current, 10)) ? parseInt(current, 10) : -1;
    var n = parseInt(regularCount, 10) || 0;
    if (n < 1) {
        return p;
    }

    if (move === 'right') {
        if (p === -1) {
            return 1;
        }
        if (p < n) {
            return p + 1;
        }
        return p;
    }

    if (move === 'left') {
        if (p === 1) {
            return -1;
        }
        if (p > 1) {
            return p - 1;
        }
        return p;
    }

    return p;
}

/** Persist glass-pool gate placement before diagram reload. */
function fcPersistGlassPoolGatePlacement(fd, placement, regularCount) {
    return fcPersistGlassPoolLeftHandGatePlacement(fd, placement, regularCount);
}

/** Persist left-hand glass-pool gate placement before diagram reload. */
function fcPersistGlassPoolLeftHandGatePlacement(fd, placement, regularCount) {
    if (!fd || fd.tab === undefined || fd.tab === null || !fd.slug) {
        return false;
    }
    var p = parseInt(placement, 10);
    if (!Number.isFinite(p)) {
        return false;
    }
    var n = parseInt(regularCount, 10) || 0;
    var pos = 'middle';
    if (p === -1) {
        pos = 'first';
    } else if (n > 0 && p >= n) {
        pos = 'last';
    }

    var cf =
        typeof readCustomFenceSegment === 'function'
            ? readCustomFenceSegment(fd.tab, fd.slug)
            : [];
    if (!Array.isArray(cf)) {
        cf = [];
    }

    var gi = cf.findIndex(function(item) {
        return item && item.control_key === 'gate';
    });
    if (gi < 0) {
        return false;
    }

    cf[gi].settings = cf[gi].settings || {};
    cf[gi].settings.placement = p;
    cf[gi].settings.position = pos;
    localStorage.setItem('custom_fence-' + fd.tab + '-' + fd.slug, JSON.stringify(cf));
    return true;
}

/** Detach glass-pool hinge bundle (spacing + hinge panel + post). */
function fcExtractGlassPoolHingeBundleHtml($scope) {
    if (!$scope || !$scope.length) {
        return '';
    }
    var $hinge = $scope.find('.extra-panel-item.hinge-panel, .extra-panel-item.hinge-panel-alt, .extra-panel-item').first();
    if (!$hinge.length) {
        return '';
    }

    var html = '';
    var $hingeLeadSp = $hinge.prev('.fencing-panel-spacing-number');
    var $hingePost = $hinge.next('.panel-post');

    if ($hingeLeadSp.length) {
        html += $hingeLeadSp.prop('outerHTML');
        $hingeLeadSp.remove();
    }
    html += $hinge.prop('outerHTML');
    $hinge.remove();
    if ($hingePost.length) {
        html += $hingePost.prop('outerHTML');
        $hingePost.remove();
    }

    return html;
}

/** Left-hand glass pool: hinge bundle + gate bundle HTML (removed from DOM). */
function fcExtractGlassPoolLeftHandUnitHtml($gate) {
    if (!$gate || !$gate.length) {
        return '';
    }
    var $scope = $gate.closest('.fencing-panel-container');
    var hingeHtml = fcExtractGlassPoolHingeBundleHtml($scope);
    var gateHtml = typeof fcExtractGateMoveBundle === 'function' ? fcExtractGateMoveBundle($gate) : '';
    return hingeHtml + gateHtml;
}

/** Resolve gate placement index from DOM for glass pool left-hand (hinge | gate unit). */
function fcResolveGlassPoolGatePlacementFromDom($gate, gate_data) {
    if (!$gate || !$gate.length || !fcGlassPoolUsesPlacementDrive(gate_data, $gate)) {
        return null;
    }

    var $scope = $gate.closest('.fencing-panel-container');
    var boundary =
        typeof fcGlassPoolGateHingeBoundaryState === 'function'
            ? fcGlassPoolGateHingeBoundaryState($scope, $gate)
            : null;

    if (boundary && boundary.atFirst) {
        return -1;
    }

    var regularCount = $scope.find('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)').length;

    if (boundary && boundary.atLast) {
        return regularCount > 0 ? regularCount : -1;
    }

    var $hinge = $scope.find('.extra-panel-item').first();
    if (!$hinge.length) {
        return null;
    }

    var regularBefore = $hinge.prevAll('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)').length;
    if (regularBefore > 0) {
        return regularBefore;
    }

    return -1;
}

/** Step glass pool left-hand gate+hinge unit one bay left or right. */
function fcMoveGlassPoolLeftHandUnitStep($gate, move) {
    if (!$gate || !$gate.length || (move !== 'left' && move !== 'right')) {
        return false;
    }

    var $scope = $gate.closest('.fencing-panel-container');
    if (!$scope.length || String($scope.attr('data-type') || '') !== 'glass_pool') {
        return false;
    }

    var $hinge = $scope.find('.extra-panel-item').first();
    if (!$hinge.length) {
        return false;
    }

    if (move === 'right') {
        var $nextRegular = $gate
            .nextAll('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)')
            .first();
        if (!$nextRegular.length) {
            return false;
        }

        var unitRightHtml = fcExtractGlassPoolLeftHandUnitHtml($gate);
        if (!unitRightHtml) {
            return false;
        }

        var $insertAfter = $nextRegular.next('.panel-post');
        if (!$insertAfter.length) {
            $insertAfter = $nextRegular;
        }
        $insertAfter.after(unitRightHtml);
        return true;
    }

    if (move === 'left') {
        var $prevRegular = $hinge
            .prevAll('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)')
            .first();
        if (!$prevRegular.length) {
            return false;
        }

        var unitLeftHtml = fcExtractGlassPoolLeftHandUnitHtml($gate);
        if (!unitLeftHtml) {
            return false;
        }

        var $insertBefore = $prevRegular.prev('.fencing-panel-spacing-number');
        if (!$insertBefore.length) {
            $insertBefore = $prevRegular;
        }
        $insertBefore.before(unitLeftHtml);
        return true;
    }

    return false;
}

/** panel_gate-a-r order: gate | spacing | post (panel-gate-right). */
function fcNormalizeGateBundleHtmlToGateRight(html) {
    if (!html) {
        return '';
    }
    var $wrap = $('<div>').html(html);
    var $gate = $wrap.find('.fencing-panel-gate').first();
    if (!$gate.length) {
        return html;
    }
    $gate.removeClass('panel-gate-left').addClass('panel-gate-right');

    var $children = $wrap.children();
    var $spacing = $children.filter('.fencing-panel-spacing-number').first();
    var $post = $children.filter('.panel-post').first();

    if (!$spacing.length || !$post.length) {
        $post = $gate.prev('.panel-post');
        $spacing = $post.prev('.fencing-panel-spacing-number');
    }

    if ($spacing.length && $post.length) {
        return $gate.prop('outerHTML') + $spacing.prop('outerHTML') + $post.prop('outerHTML');
    }

    return html;
}

/** panel_gate-b-l order: spacing | post | gate (panel-gate-left). */
function fcNormalizeGateBundleHtmlToGateLeft(html) {
    if (!html) {
        return '';
    }
    var $wrap = $('<div>').html(html);
    var $gate = $wrap.find('.fencing-panel-gate').first();
    if (!$gate.length) {
        return html;
    }
    $gate.removeClass('panel-gate-right').addClass('panel-gate-left');

    var $children = $wrap.children();
    var $spacing = $children.filter('.fencing-panel-spacing-number').first();
    var $post = $children.filter('.panel-post').first();

    if (!$spacing.length || !$post.length) {
        $spacing = $gate.next('.fencing-panel-spacing-number');
        $post = $spacing.next('.panel-post');
    }

    if ($spacing.length && $post.length) {
        return $spacing.prop('outerHTML') + $post.prop('outerHTML') + $('<div>').append($gate.clone()).html();
    }

    return html;
}

/**
 * Move gate to last bay: after last regular panel, immediately before right raked when present.
 */
function fcMoveGateToLastPosition($gate) {
    $gate = $gate && $gate.length ? $gate : $(typeof FENCES !== 'undefined' ? FENCES.el.fencingPanelGate : '.fencing-panel-gate');
    if (!$gate.length) {
        return false;
    }

    if (typeof fcGateIsAtLastPosition === 'function' && fcGateIsAtLastPosition($gate)) {
        return false;
    }

    var gate_data = [];
    try {
        if (typeof getSelectedFenceData === 'function') {
            var fdLast = getSelectedFenceData();
            gate_data = (fdLast && fdLast.info || []).filter(function(item) {
                return item && item.control_key === 'gate';
            });
        }
    } catch (eGateLast) {}

    var useLeftHandUnit =
        typeof fcGlassPoolShouldMoveGateHingeAsUnit === 'function' &&
        fcGlassPoolShouldMoveGateHingeAsUnit(gate_data, $gate);

    var bundleHtml = useLeftHandUnit
        ? fcExtractGlassPoolLeftHandUnitHtml($gate)
        : typeof fcExtractGateMoveBundle === 'function'
          ? fcExtractGateMoveBundle($gate)
          : '';
    if (!bundleHtml) {
        return false;
    }

    if (!useLeftHandUnit) {
        bundleHtml = fcNormalizeGateBundleHtmlToGateLeft(bundleHtml);
    }
    $gate.remove();

    var $scopeLast = $gate.closest('.fencing-panel-container');
    if (useLeftHandUnit && typeof fcPersistGlassPoolLeftHandGatePlacement === 'function') {
        var regularCountLast = $scopeLast.find('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)').length;
        try {
            if (typeof getSelectedFenceData === 'function') {
                var fdLastPl = getSelectedFenceData();
                fcPersistGlassPoolLeftHandGatePlacement(fdLastPl, regularCountLast, regularCountLast);
            }
        } catch (ePlLast) {}
    }

    if (fcHasRightRakedPanel()) {
        fcRemoveTrailingBayBeforeRightRaked();
        $('.right_raked-panel').first().before(bundleHtml);
        return true;
    }

    var lastId = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.panelItem : '.panel-item')
        .filter(':not(.fencing-raked-panel)')
        .last()
        .attr('data-id');
    if (lastId === undefined) {
        return false;
    }
    $('#panel-item-' + lastId).after(bundleHtml);
    return true;
}

/** Default option slug from a glass-pool gate field definition. */
function fcGlassPoolDefaultOptionSlug(fieldDef) {
    if (!fieldDef || !Array.isArray(fieldDef.options)) {
        return '';
    }
    var def = fieldDef.options.find(function(o) {
        return o && o.default;
    });
    return String((def || fieldDef.options[0] || {}).slug || '');
}

/**
 * Ensure persisted glass-pool gate fields exist before the modal opens (Add Gate flow).
 * Without defaults, hinge gaps are undefined and the glass solver fails on long runs.
 */
function fcGlassPoolEnsureDefaultGateFields(info, fields) {
    fields = Array.isArray(fields) ? fields.slice() : [];
    if (!info || info.panel_group !== 'a') {
        return fields;
    }

    var gateFields = info.settings?.gate?.fields || [];
    var required = [
        { slug: 'gate_hinge_type', key: 'gate_hinge_type' },
        { slug: 'gate_hinge_panel_width', key: 'gate_hinge_panel_width' },
        { slug: 'gate_width', key: 'gate_width' },
        { slug: 'gate_hinge_position', key: 'gate_hinge_position' }
    ];

    required.forEach(function(req) {
        var existing = fields.find(function(f) {
            return f && f.key === req.key;
        });
        if (existing && existing.val !== undefined && String(existing.val).trim() !== '') {
            return;
        }
        var fieldDef = gateFields.find(function(f) {
            return f && f.slug === req.slug;
        });
        var val = fcGlassPoolDefaultOptionSlug(fieldDef);
        if (!val) {
            return;
        }
        if (existing) {
            existing.val = val;
        } else {
            fields.push({ key: req.key, val: val, tag: 'input', type: 'hidden' });
        }
    });

    return fields;
}

/** Resolve glass-pool hinge panel width (mm) from gate fields, calc, or fence defaults. */
function fcResolveGlassPoolHingePanelWidthMm(gate_data, calc, info) {
    var fromField = gate_data?.[0]?.settings?.fields?.find(function(item) {
        return item && item.key === 'gate_hinge_panel_width';
    });
    var w = parseInt(String(fromField?.val ?? '').replace(/,/g, ''), 10);
    if (Number.isFinite(w) && w > 0) {
        return w;
    }
    w = parseInt(String(calc?.gate_hinge_panel?.width ?? '').replace(/,/g, ''), 10);
    if (Number.isFinite(w) && w > 0) {
        return w;
    }
    if (typeof fcGlassPoolDefaultHingePanelWidthMm === 'function') {
        return fcGlassPoolDefaultHingePanelWidthMm(info);
    }
    return 1200;
}

/** Persist missing glass-pool gate field defaults before calc / diagram reload. */
function fcGlassPoolPersistGateFieldsIfNeeded(tab, slug, info) {
    if (!info || info.panel_group !== 'a' || typeof readCustomFenceSegment !== 'function') {
        return;
    }
    var cf = readCustomFenceSegment(tab, slug);
    if (!Array.isArray(cf)) {
        return;
    }
    var gi = cf.findIndex(function(item) {
        return item && item.control_key === 'gate';
    });
    if (gi < 0 || typeof fcGlassPoolEnsureDefaultGateFields !== 'function') {
        return;
    }
    var prevFields = cf[gi].settings?.fields || [];
    var fields = fcGlassPoolEnsureDefaultGateFields(info, prevFields);
    if (JSON.stringify(fields) === JSON.stringify(prevFields)) {
        return;
    }
    cf[gi].settings = cf[gi].settings || {};
    cf[gi].settings.fields = fields;
    localStorage.setItem('custom_fence-' + tab + '-' + slug, JSON.stringify(cf));
}

/** Gate hinge type option from glass-pool fence config. */
function fcGlassPoolGetGateHingeTypeOption(info, hingeTypeSlug) {
    var fieldDef = (info?.settings?.gate?.fields || []).find(function(f) {
        return f && f.slug === 'gate_hinge_type';
    });
    if (!fieldDef || !Array.isArray(fieldDef.options)) {
        return null;
    }
    if (hingeTypeSlug !== undefined && hingeTypeSlug !== null && String(hingeTypeSlug).trim() !== '') {
        var match = fieldDef.options.find(function(o) {
            return o && String(o.slug) === String(hingeTypeSlug);
        });
        if (match) {
            return match;
        }
    }
    return (
        fieldDef.options.find(function(o) {
            return o && o.default;
        }) || fieldDef.options[0] ||
        null
    );
}

/**
 * Filter gate-width dropdown to widths allowed for the selected hinge type.
 * Returns the selected width (mm) after sync, or null when not applicable.
 */
function fcGlassPoolSyncGateWidthDropdown(fd, hingeTypeSlug) {
    if (!fd || !fd.data || fd.data.panel_group !== 'a') {
        return null;
    }

    var htOpt = fcGlassPoolGetGateHingeTypeOption(fd.data, hingeTypeSlug);
    if (!htOpt || !Array.isArray(htOpt.gate_width) || !htOpt.gate_width.length) {
        return null;
    }

    var allowed = htOpt.gate_width
        .map(function(w) {
            return parseInt(String(w).replace(/,/g, ''), 10);
        })
        .filter(function(w) {
            return Number.isFinite(w) && w > 0;
        });
    if (!allowed.length) {
        return null;
    }

    var gwFieldDef = (fd.data.settings?.gate?.fields || []).find(function(f) {
        return f && f.slug === 'gate_width';
    });
    if (!gwFieldDef || !Array.isArray(gwFieldDef.options)) {
        return null;
    }

    var $select = $('.fencing-container[data-key="gate"] [name="gate_width"] select');
    if (!$select.length) {
        $select = $('#fc-control-modal [name="gate_width"] select, .js-fencing-modal [name="gate_width"] select');
    }
    if (!$select.length) {
        return null;
    }

    var prevVal = parseInt(String($select.val() || '').replace(/,/g, ''), 10);
    var gateRow = (fd.info || []).filter(function(r) {
        return r && r.control_key === 'gate';
    })[0];
    if (!Number.isFinite(prevVal) || prevVal <= 0) {
        var gwStored = (gateRow?.settings?.fields || []).find(function(f) {
            return f && f.key === 'gate_width';
        });
        prevVal = parseInt(
            String(gwStored?.val || gateRow?.settings?.size || '').replace(/,/g, ''),
            10
        );
    }

    $select.empty();
    gwFieldDef.options.forEach(function(o) {
        var w = parseInt(String(o.slug || o.size?.width || '').replace(/,/g, ''), 10);
        if (!allowed.includes(w)) {
            return;
        }
        $select.append($('<option>', { value: o.slug, text: o.title }));
    });

    var nextVal = allowed.includes(prevVal) ? prevVal : allowed[0];
    if (Number.isFinite(nextVal)) {
        $select.val(String(nextVal));
    }
    return Number.isFinite(nextVal) ? nextVal : null;
}

/** Persist hinge type, sync gate width options, and reload Step 3 results for glass pool. */
function fcGlassPoolApplyHingeTypeChange(hingeTypeSlug, fd) {
    fd =
        fd ||
        (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
    if (!fd || !fd.data || fd.data.panel_group !== 'a') {
        return;
    }

    if (typeof fcGlassPoolSyncGateWidthDropdown === 'function') {
        fcGlassPoolSyncGateWidthDropdown(fd, hingeTypeSlug);
    }

    try {
        if (typeof FENCE !== 'undefined') {
            FENCE.call('update_custom_fence_gate');
        }
    } catch (ePersist) {}

    try {
        if (typeof fcSyncGateMinOverallLength === 'function') {
            fd =
                typeof getSelectedFenceData === 'function'
                    ? getSelectedFenceData()
                    : fd;
            fcSyncGateMinOverallLength(fd, { persist: true });
        }
    } catch (eOal) {}

    try {
        if (typeof updateOverAllLength === 'function') {
            updateOverAllLength();
        }
    } catch (eOal2) {}

    try {
        if (typeof btnCalculate === 'function') {
            btnCalculate();
        }
    } catch (eCalc) {}
}

/** Default hinge panel width (mm) from glass-pool gate config. */
function fcGlassPoolDefaultHingePanelWidthMm(info) {
    var fieldDef = (info?.settings?.gate?.fields || []).find(function(f) {
        return f && f.slug === 'gate_hinge_panel_width';
    });
    var opt = (fieldDef?.options || []).find(function(o) {
        return o && o.default;
    }) || (fieldDef?.options || [])[0];
    var w = parseInt(String(opt?.size?.width ?? opt?.slug ?? '').replace(/,/g, ''), 10);
    return Number.isFinite(w) && w > 0 ? w : 1200;
}

/**
 * Glass pool: true when gate hinge position is left-hand.
 */
function fcGlassPoolIsLeftHandSwingFromGateData(gate_data) {
    if (!gate_data || !gate_data.length) {
        return false;
    }
    var field = gate_data[0].settings?.fields?.find(function(item) {
        return item.key === 'gate_hinge_position';
    });
    return !!(field?.val && String(field.val).includes('left'));
}

/** Glass pool: true when gate hinge position is right-hand. */
function fcGlassPoolIsRightHandSwingFromGateData(gate_data) {
    if (!gate_data || !gate_data.length) {
        return false;
    }
    var field = gate_data[0].settings?.fields?.find(function(item) {
        return item.key === 'gate_hinge_position';
    });
    return !!(field?.val && String(field.val).includes('right'));
}

/**
 * Glass pool / panel-group a: insert point for gate-at-first (before hinge bundle when present).
 */
function fcGetGlassPoolGateFirstInsert$($scope) {
    $scope =
        $scope && $scope.length
            ? $scope
            : typeof fcResolveGlassPoolPanelContainer === 'function'
              ? fcResolveGlassPoolPanelContainer(null, null)
              : $('.fencing-panel-container:visible').first();

    if (!$scope.length || String($scope.attr('data-type') || '') !== 'glass_pool') {
        return $();
    }

    var $hinge = $scope.find('.extra-panel-item.hinge-panel, .extra-panel-item').first();
    if ($hinge.length) {
        var $hingeLead = $hinge.prev('.fencing-panel-spacing-number');
        return $hingeLead.length ? $hingeLead : $hinge;
    }

    return $();
}

/**
 * Glass pool left-hand at first: insert gate after the hinge bundle (HINGE | GATE | panels).
 */
function fcGetGlassPoolGateFirstInsertAfterHinge$($scope) {
    $scope =
        $scope && $scope.length
            ? $scope
            : typeof fcResolveGlassPoolPanelContainer === 'function'
              ? fcResolveGlassPoolPanelContainer(null, null)
              : $('.fencing-panel-container:visible').first();

    if (!$scope.length || String($scope.attr('data-type') || '') !== 'glass_pool') {
        return $();
    }

    var $hinge = $scope.find('.extra-panel-item.hinge-panel, .extra-panel-item.hinge-panel-alt, .extra-panel-item').first();
    if (!$hinge.length) {
        return $();
    }

    var $hingePost = $hinge.next('.panel-post');
    return $hingePost.length ? $hingePost : $hinge;
}

/**
 * Move gate to first bay: before hinge panel (glass pool) or first regular panel.
 */
function fcMoveGateToFirstPosition($gate) {
    $gate = $gate && $gate.length ? $gate : $(typeof FENCES !== 'undefined' ? FENCES.el.fencingPanelGate : '.fencing-panel-gate');
    if (!$gate.length) {
        return false;
    }

    if (typeof fcGateMoveBoundaryState === 'function') {
        var boundary = fcGateMoveBoundaryState(null, $gate);
        if (boundary && boundary.atFirst) {
            return false;
        }
    } else if (typeof fcGateIsAtFirstPosition === 'function' && fcGateIsAtFirstPosition($gate)) {
        return false;
    }

    var $scope =
        typeof fcResolveGlassPoolPanelContainer === 'function'
            ? fcResolveGlassPoolPanelContainer(null, null)
            : $('.fencing-panel-container:visible').first();
    var isGlassPool = $scope.length && String($scope.attr('data-type') || '') === 'glass_pool';

    var gate_data = [];
    try {
        if (typeof getSelectedFenceData === 'function') {
            var fdMove = getSelectedFenceData();
            gate_data = (fdMove && fdMove.info || []).filter(function(item) {
                return item && item.control_key === 'gate';
            });
        }
    } catch (eGateData) {}

    var isLeftFirstLayout =
        isGlassPool &&
        fcGlassPoolIsLeftHandSwingFromGateData(gate_data) &&
        $scope.find('.extra-panel-item').length;

    if (isLeftFirstLayout) {
        var unitHtml =
            typeof fcExtractGlassPoolLeftHandUnitHtml === 'function'
                ? fcExtractGlassPoolLeftHandUnitHtml($gate)
                : '';
        if (!unitHtml) {
            return false;
        }

        var $insertBefore = fcGetFirstRegularPanel$();
        if (!$insertBefore.length) {
            $insertBefore = $('#panel-item-0, #panel-item-x').first();
        }
        if ($insertBefore.length) {
            $insertBefore.before(unitHtml);
        } else if (fcHasLeftRakedPanel()) {
            $('.left_raked-panel').first().after(unitHtml);
        } else if ($scope.length) {
            $scope.prepend(unitHtml);
        } else {
            return false;
        }

        if (typeof fcPersistGlassPoolLeftHandGatePlacement === 'function') {
            var regularCount = $scope.find('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel,.extra-panel-item)').length;
            try {
                if (typeof getSelectedFenceData === 'function') {
                    var fdFirst = getSelectedFenceData();
                    fcPersistGlassPoolLeftHandGatePlacement(fdFirst, -1, regularCount);
                }
            } catch (ePlFirst) {}
        }
        return true;
    }

    var bundleHtml = typeof fcExtractGateMoveBundle === 'function' ? fcExtractGateMoveBundle($gate) : '';
    if (!bundleHtml) {
        return false;
    }

    if (isGlassPool && typeof fcNormalizeGateBundleHtmlToGateRight === 'function') {
        bundleHtml = fcNormalizeGateBundleHtmlToGateRight(bundleHtml);
    }

    var $insertBefore = isGlassPool ? fcGetGlassPoolGateFirstInsert$($scope) : $();
    if (!$insertBefore.length) {
        $insertBefore = fcGetFirstRegularPanel$();
    }
    if (!$insertBefore.length) {
        $insertBefore = $('#panel-item-0, #panel-item-x').first();
    }

    if ($insertBefore.length) {
        $insertBefore.before(bundleHtml);
    } else if (fcHasLeftRakedPanel()) {
        $('.left_raked-panel').first().after(bundleHtml);
    } else {
        return false;
    }

    $gate.remove();
    return true;
}

/** Detach gate + spacing + post bundle from the DOM (panel-group b templates). */
function fcExtractGateMoveBundle($gate) {
    if (!$gate || !$gate.length) {
        return '';
    }

    if ($gate.hasClass('panel-gate-left')) {
        var $spacing = $gate.prev().prev();
        var $post = $gate.prev();
        if ($spacing.length && $post.length) {
            var leftHtml = $spacing.prop('outerHTML') + $post.prop('outerHTML') + $gate.prop('outerHTML');
            $spacing.remove();
            $post.remove();
            return leftHtml;
        }
    }

    if ($gate.hasClass('panel-gate-right')) {
        // Prefer spacing/post before gate (panel-gate-a-l / glass pool at last position).
        var $postBefore = $gate.prev('.panel-post');
        var $spacingBefore = $postBefore.length ? $postBefore.prev('.fencing-panel-spacing-number') : $();
        if ($spacingBefore.length && $postBefore.length) {
            var beforeHtml = $spacingBefore.prop('outerHTML') + $postBefore.prop('outerHTML') + $gate.prop('outerHTML');
            $spacingBefore.remove();
            $postBefore.remove();
            return beforeHtml;
        }

        var $spacingR = $gate.next('.fencing-panel-spacing-number');
        var $postR = $spacingR.length ? $spacingR.next('.panel-post') : $();
        if ($spacingR.length && $postR.length) {
            var rightHtml = $gate.prop('outerHTML') + $spacingR.prop('outerHTML') + $postR.prop('outerHTML');
            $spacingR.remove();
            $postR.remove();
            return rightHtml;
        }
    }

    var $spacingLegacy = $gate.prev().prev();
    var $postLegacy = $gate.prev();
    if ($spacingLegacy.length && $postLegacy.length) {
        var legacyHtml = $spacingLegacy.prop('outerHTML') + $postLegacy.prop('outerHTML') + $gate.prop('outerHTML');
        $spacingLegacy.remove();
        $postLegacy.remove();
        return legacyHtml;
    }

    return '';
}

/** True only during the first Step 3 restore after a full planner page load. */
var _fcPlannerStep3SkeletonLoadPass = false;

function fcBeginPlannerStep3SkeletonLoadPass() {
    _fcPlannerStep3SkeletonLoadPass = true;
}

function fcEndPlannerStep3SkeletonLoadPass() {
    _fcPlannerStep3SkeletonLoadPass = false;
}

function fcShouldShowPlannerStep3Skeleton() {
    return !!_fcPlannerStep3SkeletonLoadPass;
}

/** Planner Step 3 — show fence diagram skeleton overlay. */
function fcShowPlannerStep3Skeleton() {
    if (!fcShouldShowPlannerStep3Skeleton()) {
        return;
    }
    if (!$('.fc-planner-page').length) {
        return;
    }
    var $section = $('.fc-planner-page .js-fc-form-step[data-section="3"]');
    if (!$section.length) {
        return;
    }
    $section.find('.js-fc-planner-step3-skeleton')
        .removeClass('fc-d-none')
        .attr('aria-hidden', 'false');
    $section.find('.fencing-display-result').addClass('fc-planner-step3-result--loading');
}

/** Planner Step 3 — hide fence diagram skeleton overlay. */
function fcHidePlannerStep3Skeleton() {
    if (!$('.fc-planner-page').length) {
        return;
    }
    $('.fc-planner-page .js-fc-planner-step3-skeleton')
        .addClass('fc-d-none')
        .attr('aria-hidden', 'true');
    $('.fc-planner-page .fencing-display-result').removeClass('fc-planner-step3-result--loading');
}

/** Re-apply Step 3 panel/post heights after the planner skeleton hides (slat vs tubular paths). */
function fcReapplyPlannerStep3DisplayHeights() {
    if (!$('.fc-planner-page').length) {
        return;
    }
    try {
        var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (!fd || !fd.slug) {
            return;
        }
        var calc = typeof calculate_fences === 'function' ? calculate_fences() : null;
        if (!calc) {
            return;
        }
        var $scope = $('#pp-' + fd.tab + ' .fencing-panel-container');
        if (!$scope.length) {
            $scope = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingPanelContainer : '.fencing-panel-container');
        }
        if (!$scope.length) {
            return;
        }

        requestAnimationFrame(function() {
            try {
                if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(fd.slug)) {
                    if (typeof SlatFence.applySlatFenceDisplayHeights === 'function') {
                        SlatFence.applySlatFenceDisplayHeights(
                            fd.slug,
                            calc,
                            { fenceInfo: fd.info, tabInfo: fd.tabInfo },
                            $scope
                        );
                    }
                    return;
                }
                if (typeof fcApplyGroupBFenceDisplayHeights === 'function') {
                    fcApplyGroupBFenceDisplayHeights(calc, $scope, {
                        slug: fd.slug,
                        tabInfo: fd.tabInfo
                    });
                }
            } catch (eApply) {}
            if (typeof step !== 'undefined' && step === 1 && typeof HELPER.captureStep3ResultBaseHeight === 'function') {
                requestAnimationFrame(function() {
                    HELPER.captureStep3ResultBaseHeight();
                });
            }
        });
    } catch (e) {}
}

/** Defer Step 3 render so skeleton paints before sync diagram build (double rAF). */
function fcRunWithPlannerStep3Skeleton(renderFn) {
    if (typeof renderFn !== 'function') {
        return;
    }
    if (!fcShouldShowPlannerStep3Skeleton()) {
        try {
            renderFn();
        } finally {
            fcReapplyPlannerStep3DisplayHeights();
        }
        return;
    }
    fcShowPlannerStep3Skeleton();
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            try {
                renderFn();
            } finally {
                fcHidePlannerStep3Skeleton();
                fcReapplyPlannerStep3DisplayHeights();
            }
        });
    });
}

/** Baseline for Step 3 UPDATE visibility — captured after planner finishes loading saved fence styles. */
var fcPlannerUpdateFenceBaseline = null;

function fcNormalizeStyleSlugForPlannerSnapshot(s) {
    if (s === undefined || s === null || s === '') {
        return '';
    }
    return normalizeFenceStyleSlug(String(s));
}

/**
 * Stable snapshot: section count + chosen style per tab (matches `custom_fence-{i}`).
 */
function fcGetFenceStyleSnapshot() {
    var nRaw = localStorage.getItem('custom_fence-section');
    var n = nRaw === null || nRaw === '' ? NaN : parseInt(nRaw, 10);
    if (!Number.isFinite(n) || n < 1) {
        n = 1;
    }
    try {
        var tabLen = document.querySelectorAll('.fencing-tab').length;
        if (tabLen > 0) {
            n = Math.max(n, tabLen);
        }
    } catch (e) {}

    var styles = [];
    for (var i = 0; i < n; i++) {
        var raw = localStorage.getItem('custom_fence-' + i);
        var tab = raw ? JSON.parse(raw) : [];
        var styleRaw = tab[0] && tab[0].style !== undefined && tab[0].style !== null ? tab[0].style : '';
        styles.push(fcNormalizeStyleSlugForPlannerSnapshot(styleRaw));
    }
    return JSON.stringify({ n: n, styles: styles });
}

function fcCapturePlannerUpdateFenceBaseline() {
    fcPlannerUpdateFenceBaseline = fcGetFenceStyleSnapshot();
}

/**
 * Keep UPDATE column visible; enable/disable is driven only by colour completeness (see fcApplyPlannerUpdateDisabledFromColors).
 * Previously hid the column when fence snapshot diverged from baseline, which blocked colour-based enable on tab=2.
 */
function fcSyncPlannerUpdateButtonVisibility() {
    var $btn = $('.fc-btn-update');
    if (!$btn.length) {
        return;
    }
    $btn.closest('.col-lg-auto').removeClass('fc-d-none');
    fcApplyPlannerUpdateDisabledFromColors();
}

/**
 * Each `.fc-color-options` row has a selection in real DOM only (not Slick `infinite` clones).
 * Cloned slides copy `.fc-selected`, so a naive count of `.fc-selected` stays > row count and keeps buttons disabled.
 */
function fcPlannerColorOptionGroupsComplete($scope) {
    var $groups = $scope.find('.fc-color-options');
    if (!$groups.length) {
        return false;
    }
    var complete = true;
    $groups.each(function() {
        var n = $(this)
            .find('.fc-select-item.fc-selected')
            .filter(function() {
                return $(this).closest('.slick-cloned').length === 0;
            }).length;
        if (n < 1) {
            complete = false;
            return false;
        }
    });
    return complete;
}

/**
 * Planner Step 4: mark each colour carousel row so scripts/CSS can find rows without a real selection
 * (ignores Slick infinite clones). Query e.g. `.fc-step-4 [data-fc-planner-has-selection="0"]` or
 * `.fc-planner-color-options-row--unselected`.
 */
function fcSyncPlannerStep4ColorRowMarkers($scope) {
    if (!$scope || !$scope.length) {
        return;
    }
    $scope.find('.fc-color-options').each(function() {
        var $g = $(this);
        var $sel = $g
            .find('.fc-select-item.fc-selected')
            .filter(function() {
                return $(this).closest('.slick-cloned').length === 0;
            })
            .first();
        var n = $sel.length;
        var has = n >= 1;
        var fenceSlug = $g.attr('data-slug') || '';
        var colorTitle = has ? ($sel.attr('data-color-title') || '').trim() : '';
        $g.attr('data-fc-planner-has-selection', has ? '1' : '0');
        if (fenceSlug) {
            $g.attr('data-fc-planner-color-fence', fenceSlug);
        } else {
            $g.removeAttr('data-fc-planner-color-fence');
        }
        var $card = $g.closest('.fc-card');
        if ($card.length) {
            $card.attr('data-fc-planner-has-selection', has ? '1' : '0');
            if (fenceSlug) {
                $card.attr('data-fc-planner-color-fence', fenceSlug);
            } else {
                $card.removeAttr('data-fc-planner-color-fence');
            }
            $card.toggleClass('fc-planner-color-options-row--unselected', !has);

            var $iconSlot = $card.find('.fc-color-options-planner-status-icon');
            var $titleText = $card.find('.fc-color-options-planner-title-text');
            if ($iconSlot.length && $titleText.length) {
                var fenceLabel = ($card.attr('data-fc-planner-fence-title') || '').trim();
                if (!fenceLabel && $titleText.find('strong').length) {
                    fenceLabel = $titleText.find('strong').first().text().trim();
                }

                $iconSlot
                    .removeClass(
                        'fc-color-options-planner-status-icon--good fc-color-options-planner-status-icon--bad'
                    )
                    .empty();
                if (has) {
                    $iconSlot
                        .addClass('fc-color-options-planner-status-icon--good')
                        .append(
                            $('<i>', {
                                class: 'fa-solid fa-check fc-color-options-planner-status-icon__inner',
                                'aria-hidden': 'true'
                            })
                        );
                    fcApplyPlannerSectionTabStatusTooltip(
                        $iconSlot,
                        FC_STEP4_COLOR_STATUS_TOOLTIPS.complete
                    );
                } else {
                    $iconSlot
                        .addClass('fc-color-options-planner-status-icon--bad')
                        .append(
                            $('<i>', {
                                class: 'fa-solid fa-exclamation fc-color-options-planner-status-icon__inner',
                                'aria-hidden': 'true'
                            })
                        );
                    fcApplyPlannerSectionTabStatusTooltip(
                        $iconSlot,
                        FC_STEP4_COLOR_STATUS_TOOLTIPS.incomplete
                    );
                }

                $titleText.empty();
                if (fenceLabel) {
                    $titleText.append($('<strong>').text(fenceLabel));
                }
                if (has && colorTitle) {
                    $titleText.append(document.createTextNode(' - ')).append(document.createTextNode(colorTitle));
                } else {
                    $titleText.append(document.createTextNode(' - Colour Options'));
                }
            }
        }
    });
}

/**
 * Step 4 (tab=2): disable UPDATE until every planner colour row has a selection (same rule as Create / Update Project Plan).
 * Scope to .fc-step-4 only: #submit-modal also lives inside #fc-planning-form and duplicates .fc-color-options,
 * which inflated `total` and kept UPDATE disabled when only Step 4 rows were complete.
 */
function fcApplyPlannerUpdateDisabledFromColors() {
    var $scope = $('#fc-planning-form .fc-step-4');
    fcSyncPlannerStep4ColorRowMarkers($scope);

    var $btn = $('#fc-planning-form .fc-btn-update');
    if (!$btn.length) {
        return;
    }
    var $groups = $scope.find('.fc-color-options');
    if ($groups.length === 0) {
        $btn.prop('disabled', true);
        return;
    }
    $btn.prop('disabled', !fcPlannerColorOptionGroupsComplete($scope));
}

/** `custom_fence-{tab}-{style}` payload; tries canonical key then legacy `slat_fence`. */
function readCustomFenceSegment(tab, styleSlug) {
    if (styleSlug === undefined || styleSlug === null || styleSlug === '') {
        return [];
    }
    var canon = normalizeFenceStyleSlug(styleSlug);
    var tryKeys = [canon];
    if (canon === 'slat') {
        tryKeys.push('slat_fence');
    }
    if (canon !== String(styleSlug)) {
        tryKeys.push(String(styleSlug));
    }
    var seen = {};
    for (var k = 0; k < tryKeys.length; k++) {
        var key = tryKeys[k];
        if (seen[key]) continue;
        seen[key] = true;
        var raw = localStorage.getItem('custom_fence-' + tab + '-' + key);
        if (raw) return JSON.parse(raw);
    }
    return [];
}

/**
 * Step 2 controls for `fieldsByStyle` — includes fields moved into `.fc-step2-height-slot` /
 * `.fc-step2-pair-slot` (e.g. Barr `fence_height`, Slat `max_fence_height`) which are outside
 * `.step-2_field[data-action="change"]`.
 */
function fcCollectStep2FieldsFromDom() {
    var byName = {};
    var $section = $('[data-section="2"]');
    if (!$section.length) {
        return [];
    }

    function upsert(name, value) {
        if (!name) {
            return;
        }
        byName[name] = value !== undefined && value !== null ? String(value) : '';
    }

    $section
        .find(
            '[data-action="change"] .form-control, ' +
                '.fc-step2-height-slot .form-control, ' +
                '.fc-step2-height-slot .fc-max-fence-height-input, ' +
                '.fc-step2-pair-slot .form-control, ' +
                '.fc-step2-slat-select-row .form-control'
        )
        .each(function() {
            if (this.name) {
                upsert(this.name, $(this).val());
            }
        });

    $section
        .find(
            '[data-action="change"] input[type="radio"]:checked, ' +
                '.fc-step2-height-slot input[type="radio"]:checked, ' +
                '.fc-step2-pair-slot input[type="radio"]:checked'
        )
        .each(function() {
            upsert(this.name, this.value);
        });

    var fields = [];
    Object.keys(byName).forEach(function(name) {
        fields.push({ name: name, value: byName[name] });
    });
    return fields;
}

/** Re-apply saved Step 2 values after DOM layout moves / Select2 re-init. */
function fcReapplyStep2SavedFieldValues(tabRow0, slug) {
    if (!tabRow0 || slug === undefined || slug === null || slug === '') {
        return;
    }
    var restored = fcStep2RestoreFieldsForStyle(tabRow0, slug);
    for (var ri = 0; ri < restored.length; ri++) {
        var f = restored[ri];
        if (!f || !f.name) {
            continue;
        }
        if (f.value === undefined || f.value === null || String(f.value) === '') {
            continue;
        }
        if (
            f.name === 'max_fence_height' &&
            typeof SlatFence !== 'undefined' &&
            SlatFence.isSlatLike(slug)
        ) {
            continue;
        }
        var $els = $('[data-section="2"] [name="' + f.name + '"]');
        if (!$els.length) {
            continue;
        }
        if (($els.first().attr('type') || '').toLowerCase() === 'radio') {
            $els.filter('[value="' + f.value + '"]').prop('checked', true);
        } else {
            $els.val(f.value);
            if ($els.is('select') && $els.data('select2')) {
                $els.trigger('change.select2');
            }
        }
    }
}

/**
 * Before switching fence style on the same section: snapshot Step 2 DOM + tab row metrics into
 * `fieldsByStyle`, `measurementByStyle`, `calculateValueByStyle`, etc. for the outgoing slug so
 * `custom_fence-{tab}-{slug}` / tab payloads for the previous style remain intact.
 */
function fcPersistStep2ForOutgoingFenceStyle(tabIdx, slugNorm) {
    if (tabIdx === undefined || tabIdx === null || slugNorm === undefined || slugNorm === null || slugNorm === '') {
        return;
    }
    if (typeof fcIsStep2DomDirty === 'function' && fcIsStep2DomDirty()) {
        return;
    }
    try {
        var raw = localStorage.getItem('custom_fence-' + tabIdx);
        if (!raw) {
            return;
        }
        var tabInfo = JSON.parse(raw);
        if (!tabInfo[0]) {
            return;
        }
        var fields =
            typeof fcCollectStep2FieldsFromDom === 'function'
                ? fcCollectStep2FieldsFromDom()
                : $('[data-section="2"] [data-action="change"] .form-control').serializeArray();
        try {
            if (typeof SlatFence !== 'undefined' && typeof SlatFence.normalizeStep2FieldsBeforeSave === 'function') {
                fields = SlatFence.normalizeStep2FieldsBeforeSave({
                    slug: slugNorm,
                    prevFields:
                        tabInfo[0].fieldsByStyle && tabInfo[0].fieldsByStyle[slugNorm]
                            ? tabInfo[0].fieldsByStyle[slugNorm]
                            : tabInfo[0].fields || [],
                    nextFields: fields,
                    maxHeightEl: document.querySelector('[name="max_fence_height"]')
                });
            }
        } catch (eN) {}
        tabInfo[0].fieldsByStyle = tabInfo[0].fieldsByStyle || {};
        tabInfo[0].fieldsByStyle[slugNorm] = fields;
        tabInfo[0].measurementByStyle = tabInfo[0].measurementByStyle || {};
        var $box = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.measurementBoxNumber : '.measurement-box-number');
        var mbnPersist = $box.val() != null ? String($box.val()) : '';
        if (
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(slugNorm) &&
            SlatFence.isGateOnlyPlaceholderMm(mbnPersist)
        ) {
            var fdPersist = {
                slug: slugNorm,
                tab: tabIdx,
                info: readCustomFenceSegment(tabIdx, slugNorm),
                data: typeof fc_data !== 'undefined' ? fc_data[slugNorm] : null,
                tabInfo: tabInfo
            };
            if (SlatFence.shouldLockStep2OverallForStdGateOnly(fdPersist)) {
                var dispPersist = SlatFence.computeSlatGateOnlyStdOverallDisplayMm(fdPersist);
                if (Number.isFinite(dispPersist) && dispPersist > 0) {
                    mbnPersist = String(dispPersist);
                }
            }
        }
        tabInfo[0].measurementByStyle[slugNorm] = {
            val: mbnPersist,
            dataLast: $box.attr('data-last') || '',
            dataPrevGateOnlyMbn: $box.attr('data-prev-gate-only-mbn') || ''
        };
        tabInfo[0].calculateValueByStyle = tabInfo[0].calculateValueByStyle || {};
        if (tabInfo[0].calculateValue !== undefined && tabInfo[0].calculateValue !== null) {
            tabInfo[0].calculateValueByStyle[slugNorm] = tabInfo[0].calculateValue;
        }
        tabInfo[0].isCalculateByStyle = tabInfo[0].isCalculateByStyle || {};
        if (tabInfo[0].isCalculate !== undefined && tabInfo[0].isCalculate !== null) {
            tabInfo[0].isCalculateByStyle[slugNorm] = tabInfo[0].isCalculate;
        }
        tabInfo[0].gateOnlyByStyle = tabInfo[0].gateOnlyByStyle || {};
        tabInfo[0].gateOnlyByStyle[slugNorm] = !!tabInfo[0].gateOnly;
        localStorage.setItem('custom_fence-' + tabIdx, JSON.stringify(tabInfo));

        if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(slugNorm)) {
            var gapPersist = '';
            var sizePersist = '';
            for (var gi = 0; gi < fields.length; gi++) {
                if (fields[gi] && fields[gi].name === 'slat_gap') {
                    gapPersist = fields[gi].value;
                }
                if (fields[gi] && fields[gi].name === 'slat_size') {
                    sizePersist = fields[gi].value;
                }
            }
            var heightPersist = '';
            var mhEl = document.querySelector('[data-section="2"] [name="max_fence_height"]');
            if (mhEl && !mhEl.disabled) {
                heightPersist = (mhEl.value || '').toString().trim();
            }
            if (!heightPersist) {
                for (var hi = 0; hi < fields.length; hi++) {
                    if (fields[hi] && fields[hi].name === 'max_fence_height') {
                        heightPersist = fields[hi].value;
                        break;
                    }
                }
            }
            if (
                !heightPersist &&
                typeof SlatFence.getMaxFenceHeightValForStep2 === 'function'
            ) {
                heightPersist = SlatFence.getMaxFenceHeightValForStep2(tabInfo[0], slugNorm);
            }
            try {
                SlatFence.persistSlatGapFromStep2(tabIdx, slugNorm, gapPersist);
                SlatFence.persistSlatSizeFromStep2(tabIdx, slugNorm, sizePersist);
                SlatFence.persistMaxFenceHeightFromStep2(tabIdx, slugNorm, heightPersist);
            } catch (eGap) {}
        }
    } catch (e) {}
}

/** Slug keys to try when reading/writing per-style Step 2 `fieldsByStyle` (legacy `slat_fence` → `slat`). */
function fcStep2StyleFieldKeys(slugNorm) {
    var canon =
        typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(slugNorm)
            : String(slugNorm || '');
    var keys = [];
    var seen = {};
    function push(k) {
        if (!k || seen[k]) {
            return;
        }
        seen[k] = true;
        keys.push(k);
    }
    push(canon);
    if (canon === 'slat') {
        push('slat_fence');
    }
    if (String(slugNorm) !== canon) {
        push(String(slugNorm));
    }
    return keys;
}

/**
 * When switching Barr → Slat/Infill, remove `max_fence_height` saved for the slat slug if it
 * matches Barr `fence_height` (legacy normalize default or accidental bleed).
 */
function fcStripSlatMaxHeightCopiedFromBarr(tabIdx, prevSlugNorm, newSlugNorm) {
    if (tabIdx === undefined || tabIdx === null) {
        return;
    }
    if (typeof SlatFence === 'undefined' || !SlatFence.isSlatLike(newSlugNorm)) {
        return;
    }
    var prevCanon =
        typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(prevSlugNorm || '')
            : String(prevSlugNorm || '');
    if (prevCanon !== 'barr') {
        return;
    }
    try {
        var raw = localStorage.getItem('custom_fence-' + tabIdx);
        if (!raw) {
            return;
        }
        var tabInfo = JSON.parse(raw);
        var row0 = tabInfo[0];
        if (!row0) {
            return;
        }
        var barrH = fcReadTabRowStep2Field(row0, 'barr', 'fence_height');
        if (!barrH) {
            return;
        }
        var tryKeys = fcStep2StyleFieldKeys(newSlugNorm);
        var changed = false;
        row0.fieldsByStyle = row0.fieldsByStyle || {};
        for (var ki = 0; ki < tryKeys.length; ki++) {
            var key = tryKeys[ki];
            var arr = row0.fieldsByStyle[key];
            if (!Array.isArray(arr) || !arr.length) {
                continue;
            }
            var filtered = arr.filter(function(f) {
                if (f && f.name === 'max_fence_height' && String(f.value) === String(barrH)) {
                    changed = true;
                    return false;
                }
                return true;
            });
            if (filtered.length !== arr.length) {
                row0.fieldsByStyle[key] = filtered;
            }
        }
        if (!changed) {
            return;
        }
        var newCanon =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(newSlugNorm)
                : String(newSlugNorm);
        var activeCanon =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(row0.style || '')
                : String(row0.style || '');
        if (activeCanon === newCanon) {
            row0.fields = fcStep2RestoreFieldsForStyle(row0, newSlugNorm);
        }
        localStorage.setItem('custom_fence-' + tabIdx, JSON.stringify(tabInfo));
        try {
            SlatFence.persistMaxFenceHeightFromStep2(tabIdx, newSlugNorm, '');
        } catch (eP) {}
    } catch (e) {}
}

/** Fields to restore on Step 2 for `slugNorm` (per-style), without bleeding another style's `fields`. */
function fcStep2RestoreFieldsForStyle(tabRow0, slugNorm) {
    if (!tabRow0 || slugNorm === undefined || slugNorm === null || slugNorm === '') {
        return [];
    }
    var tryKeys = fcStep2StyleFieldKeys(slugNorm);
    var fbs = tabRow0.fieldsByStyle;
    if (fbs) {
        for (var ki = 0; ki < tryKeys.length; ki++) {
            var key = tryKeys[ki];
            if (Object.prototype.hasOwnProperty.call(fbs, key)) {
                return Array.isArray(fbs[key]) ? fbs[key] : [];
            }
        }
    }
    if (!fbs && tabRow0.fields && tabRow0.fields.length) {
        var styleCanon =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(tabRow0.style || '')
                : String(tabRow0.style || '');
        for (var sj = 0; sj < tryKeys.length; sj++) {
            if (styleCanon === tryKeys[sj] || tabRow0.style === tryKeys[sj]) {
                if (
                    typeof SlatFence !== 'undefined' &&
                    SlatFence.isSlatLike(slugNorm) &&
                    !tabRow0.fields.some(function(f) {
                        return f && f.name === 'max_fence_height';
                    })
                ) {
                    return [];
                }
                return tabRow0.fields;
            }
        }
    }
    return [];
}

/** Read per-style Step 2 measurement snapshot (`measurementByStyle`) with slug key variants. */
function fcGetStep2MeasurementForStyle(tabRow0, slugNorm) {
    if (!tabRow0 || slugNorm === undefined || slugNorm === null || slugNorm === '') {
        return null;
    }
    var mbs = tabRow0.measurementByStyle;
    if (!mbs || typeof mbs !== 'object') {
        return null;
    }
    var tryKeys = fcStep2StyleFieldKeys(slugNorm);
    for (var ki = 0; ki < tryKeys.length; ki++) {
        var key = tryKeys[ki];
        if (Object.prototype.hasOwnProperty.call(mbs, key)) {
            return mbs[key];
        }
    }
    return null;
}

/** Per-style calculate value fallback when `measurementByStyle` is empty. */
function fcGetStep2CalculateValueForStyle(tabRow0, slugNorm) {
    if (!tabRow0 || slugNorm === undefined || slugNorm === null || slugNorm === '') {
        return null;
    }
    var cvBy = tabRow0.calculateValueByStyle;
    if (cvBy && typeof cvBy === 'object') {
        var tryKeys = fcStep2StyleFieldKeys(slugNorm);
        for (var ki = 0; ki < tryKeys.length; ki++) {
            var key = tryKeys[ki];
            if (Object.prototype.hasOwnProperty.call(cvBy, key)) {
                var v = cvBy[key];
                if (v !== undefined && v !== null && String(v) !== '') {
                    return v;
                }
            }
        }
    }
    var styleCanon =
        typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(tabRow0.style || '')
            : String(tabRow0.style || '');
    for (var sj = 0; sj < fcStep2StyleFieldKeys(slugNorm).length; sj++) {
        if (styleCanon === fcStep2StyleFieldKeys(slugNorm)[sj] && tabRow0.calculateValue != null && tabRow0.calculateValue !== '') {
            return tabRow0.calculateValue;
        }
    }
    return null;
}

/** Overall length (mm) for a section/style from tab row storage (legacy + current shapes). */
function fcReadCalculateValueForStyle(tabRow0, slugNorm) {
    if (!tabRow0) {
        return null;
    }
    var slug =
        slugNorm !== undefined && slugNorm !== null && slugNorm !== ''
            ? typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(slugNorm)
                : String(slugNorm)
            : typeof normalizeFenceStyleSlug === 'function'
              ? normalizeFenceStyleSlug(tabRow0.style || '')
              : String(tabRow0.style || '');

    var m = fcGetStep2MeasurementForStyle(tabRow0, slug);
    if (m && m.val !== undefined && m.val !== null && String(m.val) !== '') {
        var mNum = parseInt(String(m.val).replace(/,/g, ''), 10);
        if (mNum === 9999) {
            var resolved9999 = fcResolveSlatGateOnlyStdOverallForTabRow(tabRow0, slug);
            if (Number.isFinite(resolved9999) && resolved9999 > 0) {
                return resolved9999;
            }
        } else if (Number.isFinite(mNum) && mNum > 0) {
            return mNum;
        }
    }

    var cv = fcGetStep2CalculateValueForStyle(tabRow0, slug);
    if (cv !== undefined && cv !== null && String(cv) !== '') {
        return cv;
    }

    if (tabRow0.mbn !== undefined && tabRow0.mbn !== null && String(tabRow0.mbn).trim() !== '') {
        return tabRow0.mbn;
    }

    if (tabRow0.calculateValue !== undefined && tabRow0.calculateValue !== null && tabRow0.calculateValue !== '') {
        return tabRow0.calculateValue;
    }

    return null;
}

/** Step 2 field from tab row `fields` / `fieldsByStyle` (for calc on project-plan). */
function fcReadTabRowStep2Field(tabRow0, slugNorm, fieldName) {
    if (!tabRow0 || !fieldName) {
        return '';
    }
    var slug =
        slugNorm !== undefined && slugNorm !== null && slugNorm !== ''
            ? slugNorm
            : tabRow0.style || '';
    var restored = fcStep2RestoreFieldsForStyle(tabRow0, slug);
    for (var ri = 0; ri < restored.length; ri++) {
        var f = restored[ri];
        if (f && f.name === fieldName && f.value !== undefined && f.value !== null && String(f.value) !== '') {
            return String(f.value);
        }
    }
    var styleCanon =
        typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(slug)
            : String(slug || '');
    if (
        Array.isArray(tabRow0.fields) &&
        (!styleCanon ||
            styleCanon ===
                (typeof normalizeFenceStyleSlug === 'function'
                    ? normalizeFenceStyleSlug(tabRow0.style || '')
                    : String(tabRow0.style || '')))
    ) {
        for (var fi = 0; fi < tabRow0.fields.length; fi++) {
            var row = tabRow0.fields[fi];
            if (row && row.name === fieldName && row.value !== undefined && row.value !== null && String(row.value) !== '') {
                return String(row.value);
            }
        }
    }
    return '';
}

/** True when a section has enough data to render / submit a plan (legacy quotes used `mbn` only). */
function fcPlannerTabRowHasPlanData(tabRow0, slugNorm) {
    if (!tabRow0) {
        return false;
    }
    var cv = fcReadCalculateValueForStyle(tabRow0, slugNorm);
    var n = parseInt(String(cv != null ? cv : '').replace(/,/g, ''), 10);
    if (Number.isFinite(n) && n > 0 && n !== 9999) {
        return true;
    }
    if (tabRow0.isCalculate === 1 || tabRow0.isCalculate === true) {
        return true;
    }
    var icBy = tabRow0.isCalculateByStyle;
    if (icBy && typeof icBy === 'object' && slugNorm) {
        var keys = fcStep2StyleFieldKeys(slugNorm);
        for (var ki = 0; ki < keys.length; ki++) {
            if (icBy[keys[ki]]) {
                return true;
            }
        }
    }
    return false;
}

function fcHasMeaningfulStep2TabRow(tabRow0) {
    return fcPlannerTabRowHasPlanData(tabRow0, tabRow0 && tabRow0.style);
}

/** Normalize legacy tab row shape after load-quote (`mbn`, `slat_fence`, missing `calculateValue`). */
function fcNormalizePlannerTabRow0(tabRow0) {
    if (!tabRow0 || typeof tabRow0 !== 'object') {
        return tabRow0;
    }
    if (tabRow0.style === 'slat_fence') {
        tabRow0.style = 'slat';
    }
    if (typeof normalizeFenceStyleSlug === 'function' && tabRow0.style) {
        tabRow0.style = normalizeFenceStyleSlug(String(tabRow0.style));
    }
    var slug = tabRow0.style;

    if (
        (tabRow0.calculateValue === undefined || tabRow0.calculateValue === null || tabRow0.calculateValue === '') &&
        tabRow0.mbn !== undefined &&
        tabRow0.mbn !== null &&
        String(tabRow0.mbn).trim() !== ''
    ) {
        var mbnNum = parseInt(String(tabRow0.mbn).replace(/,/g, ''), 10);
        if (mbnNum === 9999 && typeof fcResolveSlatGateOnlyStdOverallForTabRow === 'function') {
            var resolvedNorm = fcResolveSlatGateOnlyStdOverallForTabRow(tabRow0, slug);
            if (Number.isFinite(resolvedNorm) && resolvedNorm > 0) {
                mbnNum = resolvedNorm;
                tabRow0.mbn = resolvedNorm;
            }
        }
        tabRow0.calculateValue = Number.isFinite(mbnNum) ? mbnNum : tabRow0.mbn;
    }

    if (
        (tabRow0.calculateValue === undefined || tabRow0.calculateValue === null || tabRow0.calculateValue === '') &&
        typeof fcReadCalculateValueForStyle === 'function'
    ) {
        var cv = fcReadCalculateValueForStyle(tabRow0, slug);
        if (cv !== undefined && cv !== null && String(cv) !== '') {
            var cvNum = parseInt(String(cv).replace(/,/g, ''), 10);
            tabRow0.calculateValue = Number.isFinite(cvNum) ? cvNum : cv;
        }
    }

    if (tabRow0.calculateValue && !tabRow0.isCalculate) {
        tabRow0.isCalculate = 1;
    }

    if ((!tabRow0.fields || !tabRow0.fields.length) && tabRow0.fieldsByStyle && slug) {
        tabRow0.fields = fcStep2RestoreFieldsForStyle(tabRow0, slug);
    }

    return tabRow0;
}

function fcParsePlannerFenceDataJson(raw) {
    if (raw === undefined || raw === null || raw === '') {
        return [];
    }
    if (Array.isArray(raw)) {
        return raw;
    }
    if (typeof raw !== 'string') {
        return [];
    }
    try {
        var parsed = JSON.parse(raw);
        if (typeof parsed === 'string') {
            parsed = JSON.parse(parsed);
        }
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
}

function fcNormalizePlannerFencePayload(items) {
    if (!Array.isArray(items)) {
        return [];
    }
    items.forEach(function(v) {
        if (!v || !v.form || !v.form[0]) {
            return;
        }
        fcNormalizePlannerTabRow0(v.form[0]);
    });
    return items;
}

function fcPlannerHasPersistedCartItems() {
    try {
        for (var i = 0; i < localStorage.length; i++) {
            var k = localStorage.key(i);
            if (k && k.indexOf('cart_items-') === 0) {
                return true;
            }
        }
    } catch (e) {}
    return false;
}

/** Slat Gate ONLY + STD: resolve stored 9999 / missing OAL to computed display overall (mm). */
function fcResolveSlatGateOnlyStdOverallForTabRow(tabRow0, slugNorm, tabIdx) {
    if (typeof SlatFence === 'undefined' || !SlatFence.isMainSlatSlug(slugNorm)) {
        return null;
    }
    if (tabIdx === undefined || tabIdx === null) {
        tabIdx = $('.fencing-tab.fencing-tab-selected').index();
    }
    if (!fcGetStep2GateOnlyForStyle(tabIdx, slugNorm, tabRow0)) {
        return null;
    }
    var slug = typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(slugNorm) : slugNorm;
    var info = typeof fc_data !== 'undefined' ? fc_data[slug] : null;
    if (!info) {
        return null;
    }
    var cf =
        typeof readCustomFenceSegment === 'function' ? readCustomFenceSegment(tabIdx, slug) : [];
    if (!Array.isArray(cf)) {
        cf = [];
    }
    var fd = { slug: slug, tab: tabIdx, info: cf, data: info, tabInfo: tabRow0 ? [tabRow0] : [] };
    if (!SlatFence.shouldLockStep2OverallForStdGateOnly(fd)) {
        return null;
    }
    return SlatFence.computeSlatGateOnlyStdOverallDisplayMm(fd);
}

/** Gate ONLY flag for a style (`gateOnlyByStyle` with slug key variants, then segment). */
function fcGetStep2GateOnlyForStyle(tabIdx, slugNorm, tabRow0) {
    var gbs = (tabRow0 && tabRow0.gateOnlyByStyle) || {};
    var tryKeys = fcStep2StyleFieldKeys(slugNorm);
    for (var ki = 0; ki < tryKeys.length; ki++) {
        var key = tryKeys[ki];
        if (Object.prototype.hasOwnProperty.call(gbs, key)) {
            return !!gbs[key];
        }
    }
    var seg = readCustomFenceSegment(tabIdx, slugNorm);
    var segGate = (seg || []).filter(function(r) {
        return r.control_key === 'gate';
    })[0];
    return !!(segGate && segGate.settings && segGate.settings.gateOnly);
}

/** True when `gateOnlyByStyle` has an explicit entry for this style (incl. false). */
function fcStyleHasExplicitGateOnlyFlag(tabRow0, slugNorm) {
    var gbs = (tabRow0 && tabRow0.gateOnlyByStyle) || {};
    var tryKeys =
        typeof fcStep2StyleFieldKeys === 'function'
            ? fcStep2StyleFieldKeys(slugNorm)
            : [slugNorm];
    for (var ki = 0; ki < tryKeys.length; ki++) {
        if (Object.prototype.hasOwnProperty.call(gbs, tryKeys[ki])) {
            return true;
        }
    }
    return false;
}

/** Whether the active planner section is Gate ONLY (segment + tab / Step 2 snapshot). */
function fcIsPlannerGateOnlyActive(fd) {
    if (!fd) {
        return false;
    }
    var gateRows = (fd.info || []).filter(function(item) {
        return item.control_key === 'gate';
    });
    var segGo = !!(gateRows[0] && gateRows[0].settings && gateRows[0].settings.gateOnly);
    var tabGo = false;
    if (fd.tabInfo && fd.tabInfo[0]) {
        tabGo = !!fcGetStep2GateOnlyForStyle(fd.tab, fd.slug, fd.tabInfo[0]);
        // Do not let a stale tab-level flag override an explicit per-style false
        // (e.g. after leaving Slat Gate ONLY for another fence style).
        if (
            !tabGo &&
            fd.tabInfo[0].gateOnly &&
            !fcStyleHasExplicitGateOnlyFlag(fd.tabInfo[0], fd.slug)
        ) {
            tabGo = true;
        }
    }
    return !!(segGo || tabGo);
}

/** Unlock Step 2 Overall Length after leaving Slat Gate ONLY (or any non-locked style). */
function fcUnlockStep2OverallLengthField() {
    var $box = $(
        typeof FENCES !== 'undefined' && FENCES.el
            ? FENCES.el.measurementBoxNumber
            : '.measurement-box-number'
    );
    if (!$box.length) {
        return;
    }
    $box.prop('readonly', false).removeAttr('aria-disabled');
    $box.closest('.fc-input-container').removeClass('fc-measurement-locked-gate-only');
}

/** Read left/right step-up selection from segment storage (side row or legacy add_step_up_panels row). */
function fcResolveStepUpRakedSetting(custom_fence, rakedKey) {
    if (!Array.isArray(custom_fence) || (rakedKey !== 'left_raked' && rakedKey !== 'right_raked')) {
        return null;
    }
    var sideKey = rakedKey === 'left_raked' ? 'left_side' : 'right_side';
    var sideRow = custom_fence.find(function(item) {
        return item && item.control_key === sideKey;
    });
    var fromSide = (sideRow?.settings || []).find(function(row) {
        return row && row.key === rakedKey;
    });
    if (fromSide && fromSide.val !== undefined && fromSide.val !== null && String(fromSide.val).trim() !== '') {
        return fromSide;
    }
    var stepRow = custom_fence.find(function(item) {
        return item && item.control_key === 'add_step_up_panels';
    });
    return (stepRow?.settings || []).find(function(row) {
        return row && row.key === rakedKey;
    }) || null;
}

/** Mirror left/right step-up values into the add_step_up_panels segment for diagram render. */
function fcSyncStepUpPanelsStorageFromSides(fd) {
    fd = fd || (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
    if (!fd || fd.tab === undefined || fd.tab === null || !fd.slug) {
        return;
    }
    var cf = Array.isArray(fd.info) ? fd.info.slice() : [];
    var leftRaked = fcResolveStepUpRakedSetting(cf, 'left_raked');
    var rightRaked = fcResolveStepUpRakedSetting(cf, 'right_raked');
    var settings = [];
    if (leftRaked) {
        settings.push(leftRaked);
    }
    if (rightRaked) {
        settings.push(rightRaked);
    }
    cf = cf.filter(function(item) {
        return item && item.control_key !== 'add_step_up_panels';
    });
    if (settings.length) {
        cf.push({
            id: fd.slug,
            control_key: 'add_step_up_panels',
            settings: settings
        });
    }
    localStorage.setItem('custom_fence-' + fd.tab + '-' + fd.slug, JSON.stringify(cf));
}

/** Persist + redraw after a step-up card is selected in the left/right modal. */
function fcApplyStepUpPanelSelection(rakedKey) {
    if (rakedKey !== 'left_raked' && rakedKey !== 'right_raked') {
        return;
    }
    var fd =
        typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    if (typeof fcSyncStepUpPanelsStorageFromSides === 'function') {
        fcSyncStepUpPanelsStorageFromSides(fd);
    }
    try {
        $('.raked-panel').html('');
        FENCE.call('update_raked_panels', ['left_raked', 'right_raked']);
    } catch (eRaked) {}
    try {
        if (typeof updateOverAllLength === 'function') {
            updateOverAllLength();
        }
    } catch (eOal) {}
    try {
        if (typeof btnCalculate === 'function') {
            btnCalculate();
        }
    } catch (eCalc) {}
    if ($('.js-fencing-modal').length && !fcSuppressControlModalCloseOnFcSelectChange) {
        try {
            FCModal.close();
        } catch (eClose) {}
    }
}

/** Whether a modal field config has notes/info content below the options. */
function fcModalFieldHasNotesContent(field) {
    if (!field) {
        return false;
    }
    if (Array.isArray(field.info) && field.info.length) {
        return true;
    }
    var notes = field.notes;
    if (!notes) {
        return false;
    }
    return (
        !!notes.image ||
        !!(notes.title && String(notes.title).trim()) ||
        !!(notes.description && String(notes.description).trim())
    );
}

/** Drop body bottom gap when there is no notes/info section below the options. */
function fcSyncModalAreaBodyMargin($area, field) {
    if (!$area || !$area.length) {
        return;
    }
    var $body = $area.find('.fencing-modal-body').first();
    var $notes = $area.find('.fencing-modal-notes').first();
    var notesHasContent =
        $notes.children().length > 0 &&
        (!!$notes.text().trim() || $notes.find('img, .fc-alert-gray, .fc-selection-details').length > 0);

    if (notesHasContent || fcModalFieldHasNotesContent(field)) {
        if (!$body.hasClass('mb-4') && !$body.hasClass('mb-3')) {
            $body.addClass('mb-4');
        }
        return;
    }

    $body.removeClass('mb-4 mb-3');
}

/** Modal field: left/right "Add Step-Up Panel" block (Glass Pool / Flat Top). */
function fcIsStepUpPanelModalField(field) {
    if (!field) {
        return false;
    }
    if (field.key === 'add_step_up_panels') {
        return true;
    }
    var slug = String(field.slug || '');
    if (slug === 'left_raked' || slug === 'right_raked') {
        return true;
    }
    return String(field.title || '').trim() === 'Add Step-Up Panel';
}

/**
 * Gate ONLY on Glass Pool / Flat Top: omit step-up panel sections from left/right modals.
 * @param {string} fenceSlug
 * @param {Array} fields
 * @param {boolean} [gateOnlyOverride]
 */
function fcFilterModalFieldsForGateOnly(fenceSlug, fields, gateOnlyOverride) {
    if (!Array.isArray(fields)) {
        return fields;
    }
    if ($.inArray(String(fenceSlug || ''), ['glass_pool', 'flat_top']) === -1) {
        return fields;
    }
    var gateOnly =
        typeof gateOnlyOverride === 'boolean'
            ? gateOnlyOverride
            : fcIsPlannerGateOnlyActive(
                  typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null
              );
    if (!gateOnly) {
        return fields;
    }
    return fields.filter(function(field) {
        return !fcIsStepUpPanelModalField(field);
    });
}

/** After switching style (or rebuilding Step 2): apply saved overall length + Gate ONLY for this slug. */
function fcApplyStep2ForIncomingFenceStyle(tabIdx, slugNorm) {
    if (tabIdx === undefined || tabIdx === null || slugNorm === undefined || slugNorm === null || slugNorm === '') {
        return;
    }
    fcRunWithoutStep2DirtyTracking(function() {
    try {
        var raw = localStorage.getItem('custom_fence-' + tabIdx);
        if (!raw) {
            return;
        }
        var tabInfo = JSON.parse(raw);
        if (!tabInfo[0]) {
            return;
        }
        var row0 = tabInfo[0];
        if (typeof fcStripOverMaxOverallLengthFromTabStorage === 'function') {
            fcStripOverMaxOverallLengthFromTabStorage(tabIdx, slugNorm);
            try {
                var rawFresh = localStorage.getItem('custom_fence-' + tabIdx);
                tabInfo = rawFresh ? JSON.parse(rawFresh) : tabInfo;
                row0 = tabInfo[0] || row0;
            } catch (eFresh) {}
        }
        var $box = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.measurementBoxNumber : '.measurement-box-number');
        var m = fcGetStep2MeasurementForStyle(row0, slugNorm);
        var valToShow =
            m && m.val !== undefined && m.val !== null && String(m.val) !== ''
                ? String(m.val)
                : null;
        if (valToShow === null) {
            var cv = fcGetStep2CalculateValueForStyle(row0, slugNorm);
            if (cv !== undefined && cv !== null && String(cv) !== '') {
                valToShow = String(cv);
            }
        }
        if (typeof fcSanitizeOverallLengthRestoreVal === 'function') {
            valToShow = fcSanitizeOverallLengthRestoreVal(valToShow);
            if (!valToShow) {
                valToShow = null;
            }
        }
        if (
            valToShow !== null &&
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(slugNorm) &&
            SlatFence.isGateOnlyPlaceholderMm(valToShow)
        ) {
            var resolvedGo = fcResolveSlatGateOnlyStdOverallForTabRow(row0, slugNorm, tabIdx);
            if (Number.isFinite(resolvedGo) && resolvedGo > 0) {
                valToShow = String(resolvedGo);
            }
        }
        if (valToShow !== null) {
            $box.val(valToShow);
            if (m && m.dataLast && !SlatFence.isGateOnlyPlaceholderMm(m.dataLast)) {
                $box.attr('data-last', m.dataLast);
            } else if (Number.isFinite(parseInt(valToShow, 10))) {
                $box.attr('data-last', valToShow);
            } else {
                $box.removeAttr('data-last');
            }
            if (m && m.dataPrevGateOnlyMbn) {
                $box.attr('data-prev-gate-only-mbn', m.dataPrevGateOnlyMbn);
            } else {
                $box.removeAttr('data-prev-gate-only-mbn');
            }
        } else {
            $box.val('');
            $box.removeAttr('data-last');
            $box.removeAttr('data-prev-gate-only-mbn');
        }
        $box.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');
        var wantGo = fcGetStep2GateOnlyForStyle(tabIdx, slugNorm, row0);
        if (typeof updateGateOnly === 'function') {
            updateGateOnly(!!wantGo);
        }
        if (typeof checkGateOnly === 'function') {
            checkGateOnly();
        }
        if (wantGo && typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slugNorm)) {
            try {
                SlatFence.syncStep2GateOnlyOverallField({
                    slug: slugNorm,
                    tab: tabIdx,
                    info: readCustomFenceSegment(tabIdx, slugNorm),
                    data: typeof fc_data !== 'undefined' ? fc_data[slugNorm] : null,
                    tabInfo: [row0]
                });
            } catch (eSyncGo) {}
        }
    } catch (e) {}
    if (typeof fcMarkStep2Committed === 'function') {
        fcMarkStep2Committed();
    }
    });
}

/** True while Step 2 DOM differs from last Calculate / Enter commit. */
var _fcStep2DomDirty = false;
var _fcStep2SuppressDirty = 0;

function fcIsStep2DomDirty() {
    return !!_fcStep2DomDirty;
}

function fcMarkStep2DomDirty() {
    if (_fcStep2SuppressDirty > 0) {
        return;
    }
    _fcStep2DomDirty = true;
}

function fcMarkStep2Committed() {
    _fcStep2DomDirty = false;
}

/** Run restore / programmatic Step 2 updates without marking the form dirty. */
function fcRunWithoutStep2DirtyTracking(fn) {
    _fcStep2SuppressDirty++;
    try {
        if (typeof fn === 'function') {
            fn();
        }
    } finally {
        _fcStep2SuppressDirty--;
    }
}

/**
 * Persist current Step 2 DOM into `custom_fence-{tab}` (fieldsByStyle, measurement, gate ONLY, slat extras).
 * Skipped while Step 2 has uncommitted edits — use Calculate / Enter to commit first.
 */
function fcPersistStep2Immediate(opts) {
    opts = opts || {};
    if (!$('.fc-planner-page').length) {
        return;
    }
    if (!opts.force && typeof fcIsStep2DomDirty === 'function' && fcIsStep2DomDirty()) {
        return;
    }
    try {
        var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (!fd || !fd.data || fd.tab === undefined || fd.tab === null || !fd.slug) {
            return;
        }
        if (typeof FENCE !== 'undefined' && typeof FENCE.call === 'function') {
            FENCE.call('set_cutom_fence_data', opts);
        }
    } catch (e) {}
}

var _fcStep2PersistTimer = null;

/** Debounced Step 2 save (default 250ms). */
function fcPersistStep2Debounced(delayMs) {
    var delay = delayMs === undefined || delayMs === null ? 250 : delayMs;
    if (_fcStep2PersistTimer) {
        clearTimeout(_fcStep2PersistTimer);
    }
    _fcStep2PersistTimer = setTimeout(function() {
        _fcStep2PersistTimer = null;
        fcPersistStep2Immediate();
    }, delay);
}

/** Full `custom_fence-{tab}-{style}` JSON blob for submit (tries slat / slat_fence key variants). */
function readPlannerSectionSettingsBlob(tabIdx0, styleSlug) {
    if (styleSlug === undefined || styleSlug === null || styleSlug === '') {
        return null;
    }
    var canon = normalizeFenceStyleSlug(styleSlug);
    var tryKeys = [canon];
    if (canon === 'slat') {
        tryKeys.push('slat_fence');
    }
    if (canon !== String(styleSlug)) {
        tryKeys.push(String(styleSlug));
    }
    var seen = {};
    for (var k = 0; k < tryKeys.length; k++) {
        var key = tryKeys[k];
        if (seen[key]) continue;
        seen[key] = true;
        var raw = localStorage.getItem('custom_fence-' + tabIdx0 + '-' + key);
        if (raw) {
            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }
    }
    return null;
}

/**
 * Lists localStorage keys like `custom_fence-0-slat` (per-section style blobs).
 * Used by planner `reloadFencingData` to snapshot segment JSON before PHP session merge overwrites it
 * (post options, sides, gate, etc. all live in these blobs — not only in `custom_fence-{tab}`).
 */
function fcPlannerListCustomFenceSegmentBlobKeys() {
    var keys = [];
    try {
        for (var idx = 0; idx < localStorage.length; idx++) {
            var k = localStorage.key(idx);
            if (!k || !/^custom_fence-\d+-/.test(k)) {
                continue;
            }
            if (/^custom_fence-\d+$/.test(k)) {
                continue;
            }
            keys.push(k);
        }
    } catch (e) {}
    return keys;
}

/**
 * After session `fence_data` is merged into localStorage, restore `control_key: gate` entries from the
 * pre-merge snapshot when the merged blob lost them (gate often exists only locally until submit).
 */
function fcRestorePlannerGateSegmentBlobsAfterSessionMerge(snap, qidFromUrl) {
    if (qidFromUrl || !snap || typeof snap !== 'object') {
        return;
    }
    Object.keys(snap).forEach(function(key) {
        var rawBefore = snap[key];
        if (!rawBefore || typeof rawBefore !== 'string') {
            return;
        }
        var rawAfter = localStorage.getItem(key);
        if (!rawAfter) {
            return;
        }
        var arrBefore;
        var arrAfter;
        try {
            arrBefore = JSON.parse(rawBefore);
            arrAfter = JSON.parse(rawAfter);
        } catch (e) {
            return;
        }
        if (!Array.isArray(arrBefore) || !Array.isArray(arrAfter)) {
            return;
        }
        var gateBefore = arrBefore.filter(function(item) {
            return item && item.control_key === 'gate';
        });
        var gateAfter = arrAfter.filter(function(item) {
            return item && item.control_key === 'gate';
        });

        function gateIsCustom(gateRow) {
            if (!gateRow || !gateRow.settings || !Array.isArray(gateRow.settings.fields)) {
                return false;
            }
            var f = gateRow.settings.fields.find(function(item) {
                return item && item.key === 'use_std';
            });
            return !!(f && (f.val === false || f.val === 'false'));
        }

        // Session merge removed gate entirely — restore from snapshot.
        if (gateBefore.length && !gateAfter.length) {
            var mergedMissing = arrAfter
                .filter(function(item) {
                    return !item || item.control_key !== 'gate';
                })
                .concat(gateBefore);
            try {
                localStorage.setItem(key, JSON.stringify(mergedMissing));
            } catch (e2) {}
            return;
        }

        // Both have gate: PHP session often ships a stale STD gate; prefer local custom gate + width.
        if (gateBefore.length && gateAfter.length && gateIsCustom(gateBefore[0])) {
            var ix = arrAfter.findIndex(function(item) {
                return item && item.control_key === 'gate';
            });
            if (ix !== -1) {
                arrAfter[ix] = gateBefore[0];
                try {
                    localStorage.setItem(key, JSON.stringify(arrAfter));
                } catch (e3) {}
            }
        }
    });
}

/**
 * After session `fence_data` is merged into localStorage, restore each `custom_fence-{tab}-{slug}`
 * value from a pre-merge snapshot. `$_SESSION['fc_data']['fences']` can lag behind local edits
 * (e.g. post options changed in the planner but not yet POSTed via the /submit route). Previously only
 * `custom_fence-{tab}` was restored for tabs with `calculateValue`, so segment blobs were still
 * overwritten and changes disappeared on reload — especially after load-quote + session hydrate.
 */
function fcRestorePlannerSegmentBlobsAfterSessionMerge(snap, qidFromUrl, didFullLocalRestore) {
    if (qidFromUrl || didFullLocalRestore || !snap || typeof snap !== 'object') {
        return;
    }
    Object.keys(snap).forEach(function(key) {
        var raw = snap[key];
        if (raw == null || raw === '') {
            return;
        }
        try {
            localStorage.setItem(key, raw);
        } catch (e) {}
    });
}

//----------------------------------------------------------------------------------

function getSelectedFenceData(slug, itab) {
    var slugRaw = slug ? slug : $('.fencing-style-item.fsi-selected').attr('data-slug'),
        itab = itab ? itab : $('.fencing-tab.fencing-tab-selected').index(),
        slugNorm = normalizeFenceStyleSlug(slugRaw),
        info = readCustomFenceSegment(itab, slugRaw),
        data = fc_data[slugNorm];

    var tabInfo = localStorage.getItem('custom_fence-' + itab),
        tabInfo = tabInfo ? JSON.parse(tabInfo) : [];
    if (tabInfo[0] && tabInfo[0].style === 'slat_fence') {
        tabInfo[0].style = 'slat';
    }

    var modalKey = $(FENCES.el.fencingContainer).attr('data-key'),
        mbn = $(FENCES.el.measurementBoxNumber).val();

    return {
        slug: slugNorm,
        tab: itab,
        info: info,
        data: data,
        mbn: mbn,
        modalKey: modalKey,
        tabInfo: tabInfo
    }
}

//----------------------------------------------------------------------------------

/**
 * Slat Fence: set CSS slat gap based on selected "slat_gap".
 *
 * We render slats using `repeating-linear-gradient` in CSS (see `public/assets/css/frontend/style.css`).
 * This function updates `--fc-slat-gap-px` on the slat fence container so the visible
 * gap matches the selected mm option (scaled to the planner UI).
 */
function getSlatGapMm(custom_fence, info) {
    if (typeof SlatFence === 'undefined') {
        return null;
    }
    return SlatFence.getGapMm(custom_fence, info);
}

function slatGapMmToPx(mm) {
    if (typeof SlatFence === 'undefined') {
        return 1;
    }
    return SlatFence.mmToPx(mm);
}

function getSlatMaxFenceHeightMm() {
    if (typeof SlatFence === 'undefined') {
        return null;
    }
    return SlatFence.getMaxFenceHeightMm();
}

function slatFenceHeightMmToPx(mm) {
    if (typeof SlatFence === 'undefined') {
        return 1;
    }
    return SlatFence.mmToPx(mm);
}

function getSlatSizeMm(custom_fence, fallback = 65) {
    if (typeof SlatFence === 'undefined') {
        return fallback;
    }
    return SlatFence.getSizeMm(custom_fence, fallback);
}

function isSlatFenceLike(target) {
    if (typeof SlatFence === 'undefined') {
        return false;
    }
    const slug = (typeof target === 'string') ? target : target?.slug;
    return SlatFence.isSlatLike(slug);
}

/** Validate planner Step 2 Overall Length against data-min / data-max (and Gate ONLY rules). */
function fcValidateOverallLengthMm(opts) {
    opts = opts || {};
    var box =
        opts.el ||
        (typeof document !== 'undefined' ? document.querySelector('.measurement-box-number') : null);
    if (!box) {
        return { valid: true, val: null, min: null, max: null, message: '' };
    }

    var raw = String(box.value || '')
        .replace(/,/g, '')
        .trim();
    var min = parseInt(box.getAttribute('data-min') || '', 10);
    var max = parseInt(box.getAttribute('data-max') || '', 10);
    var val = parseInt(raw, 10);
    var fcGateOnlyPlaceholderMm = 9999;
    var fdVal = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
    var slatStdLocked =
        fdVal &&
        typeof SlatFence !== 'undefined' &&
        SlatFence.shouldLockStep2OverallForStdGateOnly(fdVal);
    var skipMinMaxForGateOnlyPlaceholder =
        val === fcGateOnlyPlaceholderMm ||
        (slatStdLocked &&
            typeof SlatFence.isGateOnlyPlaceholderMm === 'function' &&
            SlatFence.isGateOnlyPlaceholderMm(val));

    if (slatStdLocked) {
        var disp =
            typeof SlatFence.computeSlatGateOnlyStdOverallDisplayMm === 'function'
                ? SlatFence.computeSlatGateOnlyStdOverallDisplayMm(fdVal)
                : null;
        if (Number.isFinite(disp) && disp > 0) {
            val = disp;
            raw = String(disp);
        }
    }

    if (!raw) {
        return { valid: false, val: val, min: min, max: max, message: 'Please enter the amount' };
    }
    if (!Number.isFinite(val)) {
        return { valid: false, val: val, min: min, max: max, message: 'Invalid value' };
    }
    if (!skipMinMaxForGateOnlyPlaceholder && Number.isFinite(min) && val < min) {
        return {
            valid: false,
            val: val,
            min: min,
            max: max,
            message: ' Invalid ' + HELPER.number_format(min) + 'mm min'
        };
    }
    if (!skipMinMaxForGateOnlyPlaceholder && Number.isFinite(max) && val > max) {
        return {
            valid: false,
            val: val,
            min: min,
            max: max,
            message: ' Invalid ' + HELPER.number_format(max) + 'mm max'
        };
    }

    return { valid: true, val: val, min: min, max: max, message: '' };
}

/** Read Overall Length min/max from the Step 2 input (data-min / data-max). */
function fcGetOverallLengthMinMaxMm() {
    var box =
        typeof document !== 'undefined' ? document.querySelector('.measurement-box-number') : null;
    if (!box) {
        return { min: null, max: null };
    }
    return {
        min: parseInt(box.getAttribute('data-min') || '', 10),
        max: parseInt(box.getAttribute('data-max') || '', 10)
    };
}

/** True when a stored/display mm value exceeds the configured Overall Length maximum. */
function fcIsOverallLengthValueOverMaxMm(val) {
    var num = parseInt(String(val != null ? val : '').replace(/,/g, ''), 10);
    if (!Number.isFinite(num)) {
        return false;
    }
    var max = fcGetOverallLengthMinMaxMm().max;
    return Number.isFinite(max) && num > max;
}

/** Value safe to restore into Overall Length on reload; over-max becomes empty. */
function fcSanitizeOverallLengthRestoreVal(val) {
    if (val === undefined || val === null || String(val).trim() === '') {
        return '';
    }
    if (fcIsOverallLengthValueOverMaxMm(val)) {
        return '';
    }
    return String(val).trim();
}

/**
 * Remove over-max Overall Length from tab localStorage so reload does not restore it.
 * @returns {boolean} true when storage was updated
 */
function fcStripOverMaxOverallLengthFromTabStorage(tabIdx, slugNorm) {
    if (tabIdx === undefined || tabIdx === null || slugNorm === undefined || slugNorm === null || slugNorm === '') {
        return false;
    }
    try {
        var raw = localStorage.getItem('custom_fence-' + tabIdx);
        if (!raw) {
            return false;
        }
        var tabInfo = JSON.parse(raw);
        var row0 = tabInfo[0];
        if (!row0) {
            return false;
        }

        var changed = false;
        var keys = fcStep2StyleFieldKeys(slugNorm);
        var styleCanon =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(String(slugNorm))
                : String(slugNorm);

        function stripVal(v) {
            if (v === undefined || v === null || String(v).trim() === '') {
                return false;
            }
            if (!fcIsOverallLengthValueOverMaxMm(v)) {
                return false;
            }
            return true;
        }

        if (row0.measurementByStyle && typeof row0.measurementByStyle === 'object') {
            for (var mi = 0; mi < keys.length; mi++) {
                var mk = keys[mi];
                if (!Object.prototype.hasOwnProperty.call(row0.measurementByStyle, mk)) {
                    continue;
                }
                var mRow = row0.measurementByStyle[mk];
                if (mRow && stripVal(mRow.val)) {
                    mRow.val = '';
                    mRow.dataLast = '';
                    mRow.dataPrevGateOnlyMbn = '';
                    changed = true;
                }
            }
        }

        if (row0.calculateValueByStyle && typeof row0.calculateValueByStyle === 'object') {
            for (var ci = 0; ci < keys.length; ci++) {
                var ck = keys[ci];
                if (Object.prototype.hasOwnProperty.call(row0.calculateValueByStyle, ck) && stripVal(row0.calculateValueByStyle[ck])) {
                    delete row0.calculateValueByStyle[ck];
                    changed = true;
                }
            }
        }

        if (row0.isCalculateByStyle && typeof row0.isCalculateByStyle === 'object') {
            for (var ii = 0; ii < keys.length; ii++) {
                var ik = keys[ii];
                if (Object.prototype.hasOwnProperty.call(row0.isCalculateByStyle, ik) && row0.isCalculateByStyle[ik]) {
                    var cv =
                        row0.calculateValueByStyle && row0.calculateValueByStyle[ik] != null
                            ? row0.calculateValueByStyle[ik]
                            : row0.calculateValue;
                    if (stripVal(cv)) {
                        row0.isCalculateByStyle[ik] = false;
                        changed = true;
                    }
                }
            }
        }

        var rowMatchesStyle =
            keys.indexOf(styleCanon) !== -1 ||
            (row0.style && keys.indexOf(String(row0.style)) !== -1);
        if (rowMatchesStyle) {
            if (stripVal(row0.calculateValue)) {
                row0.calculateValue = '';
                row0.isCalculate = false;
                changed = true;
            }
            if (stripVal(row0.mbn)) {
                row0.mbn = '';
                changed = true;
            }
        }

        if (changed) {
            localStorage.setItem('custom_fence-' + tabIdx, JSON.stringify(tabInfo));
        }
        return changed;
    } catch (e) {
        return false;
    }
}

/**
 * Fence diagram horizontal scroll strip (planner Step 3 `.fc-project-plan-hscroll` or legacy).
 * @param {JQuery} [$context]
 * @returns {JQuery}
 */
function fcGetFenceDiagramHScroll$($context) {
    if (typeof HELPER !== 'undefined' && typeof HELPER.getFenceDiagramHScroll$ === 'function') {
        return HELPER.getFenceDiagramHScroll$($context);
    }
    var $visible = $('.fencing-display-result:visible').first();
    if (!$visible.length) {
        return $();
    }
    var $nested = $visible.children('.fc-project-plan-hscroll').first();
    return $nested.length ? $nested : $visible;
}

/** Center a selector inside the fence diagram scroll strip (planner / legacy). */
function fcFenceDiagramScrollCenter(elem, speed) {
    var $scroll = fcGetFenceDiagramHScroll$();
    if ($scroll.length && typeof $scroll.scrollCenter === 'function') {
        $scroll.scrollCenter(elem, speed);
    }
}

/**
 * Center an element in the diagram hscroll after planner Step 3 finishes re-rendering
 * (e.g. gate move triggers `btnCalculate` → deferred `load_fencing_items`).
 */
function fcFenceDiagramScrollCenterAfterRender(elem, speed) {
    var attempts = 0;
    var maxAttempts = 6;

    function tryScroll(isFollowUp) {
        if (!isFollowUp) {
            attempts++;
        }
        var $scroll = fcGetFenceDiagramHScroll$();
        var hasTarget =
            $scroll.length &&
            $scroll.find(elem).filter(':visible').length > 0;
        if (!hasTarget) {
            if (attempts < maxAttempts) {
                requestAnimationFrame(function() {
                    tryScroll(false);
                });
            }
            return;
        }
        fcFenceDiagramScrollCenter(elem, speed);
        if (!isFollowUp) {
            requestAnimationFrame(function() {
                tryScroll(true);
            });
        }
    }

    // Planner diagram build is deferred by two animation frames (`fcRunWithPlannerStep3Skeleton`).
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            tryScroll(false);
        });
    });
}

/**
 * Planner Step 3: keep the left end post in view (wide runs are horizontally scrollable).
 * Skipped for Slat Infill — post values are intentionally hidden there.
 */
function fcScrollPlannerStep3ToLeftPost(slug) {
    if (!$('.fc-planner-page').length) {
        return;
    }
    if (typeof SlatFenceInfill !== 'undefined' && SlatFenceInfill.isActive(slug)) {
        return;
    }
    var $scroll = fcGetFenceDiagramHScroll$();
    if (!$scroll.length) {
        return;
    }
    requestAnimationFrame(function() {
        var scrollEl = $scroll[0];
        if (!scrollEl) {
            return;
        }
        scrollEl.scrollLeft = 0;
        var $anchor = $scroll
            .find('.fencing-panel-container .post-left, .fencing-panel-container .panel-post')
            .first();
        if ($anchor.length) {
            var pad = 12;
            var anchorLeft = $anchor[0].offsetLeft;
            if (Number.isFinite(anchorLeft)) {
                scrollEl.scrollLeft = Math.max(0, anchorLeft - pad);
            }
        }
    });
}

/**
 * Planner Step 3: first/last panel markers + end post labels (mirrors project-plan `load_center_point`).
 * Not used for Slat Infill.
 */
function fcSyncPlannerStep3PanelEnds(slug) {
    if (!$('.fc-planner-page').length) {
        return;
    }
    if (typeof SlatFenceInfill !== 'undefined' && SlatFenceInfill.isActive(slug)) {
        return;
    }

    var $fc = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingPanelContainer : '.fencing-panel-container');
    if (!$fc.length) {
        return;
    }

    var $items = $fc.find('.fencing-panel-item:not(.fencing-raked-panel)');
    $items.removeClass('first-item last-item');
    if ($items.length) {
        $items.first().addClass('first-item');
        $items.last().addClass('last-item');
    }

    if ($fc.find('.raked-panel .raked-panel-container').length === 1) {
        $fc.find('.raked-panel').addClass('first-item last-item');
    } else {
        $fc.find('.left_raked-panel').first().addClass('first-item');
        $fc.find('.right_raked-panel').first().addClass('last-item');
    }

    $fc.find('.cp_no-post--left').removeClass('cp_no-post--left');
    $fc.find('.cp_no-post--right').removeClass('cp_no-post--right');

    if ($fc.find('.left-panel-post.no-post').length) {
        $fc.find('.fc-center-point').first().addClass('cp_no-post--left');
    }
    if ($fc.find('.right-panel-post.no-post').length) {
        $fc.find('.fc-center-point').last().addClass('cp_no-post--right');
    }

    $items.not(':last').find('.fc-last-c-p').remove();

    var $spacingNums = $fc.find('.fencing-panel-spacing-number');
    var hidePostValue =
        typeof SlatFence !== 'undefined' && SlatFence.shouldHidePostValue(slug);

    if (hidePostValue) {
        $spacingNums.find('> span:first-child').text('');
        $fc.addClass('fc-hide-post-value');
        $items.each(function() {
            var $panel = $(this);
            if (
                $panel.find('.fc-panel-size').length &&
                typeof ProjectPlan !== 'undefined' &&
                ProjectPlan.fixCentersWidthWithoutEndPost
            ) {
                ProjectPlan.fixCentersWidthWithoutEndPost($panel);
            }
        });
        $fc.find('.fc-start-c-p').empty();
        $fc.find('.fc-end-c-p').empty();
    } else {
        $fc.removeClass('fc-hide-post-value');
        $fc.find('.fc-start-c-p').html($spacingNums.first().find('> span:first-child').html());
        $fc.find('.fc-end-c-p').html($spacingNums.last().find('> span:first-child').html());
    }

    var group = $fc.attr('data-group');
    if (group === 'a') {
        $fc.find('.first-item .fc-start-c-p').html($fc.find('.left-panel-post span:not(.cg-top)').text());
        $fc.find('.last-item .fc-end-c-p').html($fc.find('.right-panel-post span:not(.cg-top)').text());
    } else if (group === 'b') {
        if ($fc.find('.left-panel-post.no-post').length) {
            var $firstPanel = $fc.find('.fencing-panel-item.first-item').first();
            if (
                $firstPanel.length &&
                $firstPanel.find('.fc-panel-size').length &&
                typeof ProjectPlan !== 'undefined' &&
                ProjectPlan.fixCentersWidthWithoutEndPost
            ) {
                ProjectPlan.fixCentersWidthWithoutEndPost($firstPanel);
            }
        }
        if ($fc.find('.right-panel-post.no-post').length) {
            var $lastPanel = $fc.find('.fencing-panel-item.last-item').first();
            if (
                $lastPanel.length &&
                $lastPanel.find('.fc-panel-size').length &&
                typeof ProjectPlan !== 'undefined' &&
                ProjectPlan.fixCentersWidthWithoutEndPost
            ) {
                ProjectPlan.fixCentersWidthWithoutEndPost($lastPanel);
            }
        }
    }

    if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug)) {
        try {
            SlatFence.syncSlatNoPostEndCenterMarkers($fc[0]);
        } catch (eSlat) {}
    }

    fcScrollPlannerStep3ToLeftPost(slug);
}

/**
 * Show / clear Overall Length validation under the field; hide Step 3 when invalid.
 * @returns {{ valid: boolean, message: string }}
 */
function fcApplyOverallLengthValidationUi(opts) {
    opts = opts || {};
    var result = fcValidateOverallLengthMm(opts);
    var box =
        opts.el ||
        (typeof document !== 'undefined' ? document.querySelector('.measurement-box-number') : null);
    if (!box) {
        return result;
    }

    var $msg = $(box).closest('.fc-input-container').find('.fc-input-msg').first();
    if (!result.valid) {
        $msg.addClass('fcim-show').html(result.message || 'Invalid value');
        $('.btn-fc-calculate')
            .attr('disabled', 'disabled')
            .removeClass('btn-dark')
            .addClass('btn-light disabled');
        if (opts.hideStep3 !== false && typeof fcHidePlannerStep3Results === 'function') {
            fcHidePlannerStep3Results();
        }
    } else if (opts.clearMessage !== false) {
        $msg.removeClass('fcim-show').html('');
    }

    return result;
}

function updateStep2MeasurementCopy(target) {
    if (typeof SlatFence === 'undefined') {
        return;
    }
    const slug = (typeof target === 'string') ? target : target?.slug;
    if (!slug || !SlatFence.isSlatLike(slug)) {
        return;
    }
    SlatFence.applyStep2MeasurementCopy(slug);
}

function applySlatGapPattern(custom_fence, info, container, calc) {
    if (typeof SlatFence === 'undefined') {
        return;
    }
    SlatFence.applyGapPattern(custom_fence, info, container, calc);
}

//----------------------------------------------------------------------------------

/**
 * @deprecated Use fcReindexPlannerStorageAfterSectionDelete.
 */
function refreshLocalStorage(activeSectionIndex, target) {
    if (typeof fcReindexPlannerStorageAfterSectionDelete === 'function') {
        fcReindexPlannerStorageAfterSectionDelete(activeSectionIndex);
    }
}

//----------------------------------------------------------------------------------

var fcIncompleteSectionsPendingStatus = '';

function fcPlannerHasQuoteId() {
    return typeof planner_id !== 'undefined' && planner_id && String(planner_id).trim() !== '';
}

/**
 * Planner page: apply server session / DB snapshot to localStorage (after project-plan edits).
 */
function fcSyncPlannerClientStateFromServer() {
    if (!$('.fc-planner-page').length || !fcPlannerHasQuoteId()) {
        return;
    }

    var projectPlansJson = null;

    if (typeof fc_session_project_plans !== 'undefined' && fc_session_project_plans) {
        projectPlansJson =
            typeof fc_session_project_plans === 'string'
                ? fc_session_project_plans
                : JSON.stringify(fc_session_project_plans);
    } else if (typeof fc_fence_info !== 'undefined' && fc_fence_info && fc_fence_info.project_plans_data) {
        var pp = fc_fence_info.project_plans_data;
        projectPlansJson = typeof pp === 'string' ? pp : JSON.stringify(pp);
    }

    if (projectPlansJson) {
        try {
            localStorage.setItem('project-plans', projectPlansJson);
        } catch (ePp) {}
    }

    if (typeof fc_fence_info !== 'undefined' && fc_fence_info && fc_fence_info.cart_items_data) {
        try {
            var cartItems = JSON.parse(fc_fence_info.cart_items_data || '[]') || [];
            if (cartItems.length && typeof fcHydratePlannerCartItemsLocalStorage === 'function') {
                fcHydratePlannerCartItemsLocalStorage(cartItems, { clearFirst: true });
            }
        } catch (eCart) {}
    }
}

/** Loader line under planner submit (Download plans / proceed with incomplete sections). */
function fcSetPlannerSubmitLoaderMessage() {
    var $cap = $('.li-create small');
    if (!$cap.length) {
        return;
    }
    if (fcPlannerHasQuoteId()) {
        $cap.html('Updating your plan...');
    } else {
        $cap.html('Creating your plan...');
    }
}

function fcFenceSectionIncompleteFromStorage(tid) {
    var form = null;
    try {
        var raw = localStorage.getItem('custom_fence-' + tid);
        form = raw ? JSON.parse(raw) : null;
    } catch (e) {
        form = null;
    }
    return form == null || !form[0]?.calculateValue;
}

function fcGetFirstIncompleteFenceSectionIndex() {
    var first = -1;
    var $tabs =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : $(FENCES.el.fencingTab);
    $tabs.each(function(i) {
        if (fcFenceSectionIncompleteFromStorage(i)) {
            first = i;
            return false;
        }
    });
    return first;
}

function fcApplyIncompleteSectionTabHighlight() {
    var $tabs =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : $(FENCES.el.fencingTab);
    $tabs.removeClass('incomplete-section');
    if (typeof fcSyncAllPlannerSectionTabStatuses === 'function') {
        fcSyncAllPlannerSectionTabStatuses();
    }
}

function fcShowIncompleteSectionsModal(status) {
    fcIncompleteSectionsPendingStatus = status || '';
    $('.fc-loader-overlay').hide();
    var el = document.getElementById('fc-incomplete-sections-modal');
    if (!el) {
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    } else {
        $(el).modal('show');
    }
}

function fcShowPopupAlertModal(title, message) {
    var $pa = $('#popup-alert');
    if (!$pa.length) {
        window.alert(message || title || '');
        return;
    }

    $pa.find('.modal-title').html(title || 'Notice');
    $pa.find('.modal-message').html(message || '');
    $pa.find('.fencing-measurement-box').addClass('d-none');

    var el = document.getElementById('popup-alert');
    if (!el) {
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    } else {
        $pa.modal('show');
    }
}

function fcBuildLoadQuoteErrorHtml(message) {
    var safeMessage = $('<div>').text(message || '').html();
    return (
        '<div class="fc-load-quote-error__inner">' +
        '<span class="fc-load-quote-error__icon" aria-hidden="true">' +
        '<i class="fa-solid fa-circle-exclamation"></i>' +
        '</span>' +
        '<span class="fc-load-quote-error__text">' +
        safeMessage +
        '</span>' +
        '</div>'
    );
}

function fcClearLoadQuoteModalError() {
    var $modal = $('#load-quote');
    if (!$modal.length) {
        return;
    }
    $modal.find('.fc-load-quote-error').addClass('d-none').empty();
    $modal.find('[name="qid"]').removeClass('is-invalid');
}

function fcShowLoadQuoteModalError(message, attemptedQid) {
    var $modal = $('#load-quote');
    if (!$modal.length) {
        window.alert(message || 'Quote ID could not be loaded.');
        return;
    }

    var $error = $modal.find('.fc-load-quote-error');
    var $input = $modal.find('[name="qid"]');

    if (message) {
        $error.html(fcBuildLoadQuoteErrorHtml(message)).removeClass('d-none');
    } else {
        $error.addClass('d-none').empty();
    }

    if (attemptedQid) {
        $input.val(String(attemptedQid));
    }
    $input.addClass('is-invalid');

    var el = document.getElementById('load-quote');
    if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    } else {
        $modal.modal('show');
    }
}

function fcInitLoadQuoteModalFromServer() {
    if (typeof fc_load_quote_failed === 'undefined' || !fc_load_quote_failed) {
        return;
    }

    var message =
        typeof fc_load_quote_error === 'string' && fc_load_quote_error
            ? fc_load_quote_error
            : 'No quote found for that Quote ID. Please check the ID and try again.';
    var attempt =
        typeof fc_load_quote_attempt === 'string' ? fc_load_quote_attempt : '';

    try {
        if (typeof history.replaceState === 'function') {
            var u = new URL(window.location.href);
            if (u.searchParams.has('qid')) {
                u.searchParams.delete('qid');
                var qs = u.searchParams.toString();
                history.replaceState({}, '', u.pathname + (qs ? '?' + qs : '') + u.hash);
            }
        }
    } catch (e) {}

    fcShowLoadQuoteModalError(message, attempt);
}

function fcGoPlannerCompleteIncompleteSections() {
    fcApplyIncompleteSectionTabHighlight();
    $('.fc-section-step').hide();
    $('[data-tab="1"]').show();
    HELPER.tabContainerScroll();
    $('html').scrollTo(100, 0);

    var firstIncomplete = fcGetFirstIncompleteFenceSectionIndex();
    if (firstIncomplete < 0) {
        return;
    }
    var $targetTab =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$().eq(firstIncomplete)
            : $(FENCES.el.fencingTab).eq(firstIncomplete);
    if (!$targetTab.length) {
        return;
    }
    // Match Planner.reload_fence_items: defer so layout/step 1 is visible, then run fencingTab()
    // (load style from localStorage, .fsi-selected + click, step 3 / fence UI).
    setTimeout(function() {
        $targetTab.trigger('click');
        if (typeof window.fcRefreshFencingStylesSlick === 'function') {
            requestAnimationFrame(function() {
                window.fcRefreshFencingStylesSlick();
            });
        }
    }, 100);
}

/**
 * Reliable fence section count from localStorage. Heals custom_fence-section when it
 * drifts from actual custom_fence-0..n-1 keys (e.g. after incomplete-section compaction + cart init).
 *
 * Only ever grows the persisted count to match storage, never shrinks it: a freshly
 * added trailing section is a tab with no saved fields yet (no custom_fence-{n-1} row),
 * which is normal mid-entry state, not drift — shrinking here would silently orphan that
 * section (it previously did, when this was called mid-calculation from the Slat BOM's
 * cross-section pooling before the last-added section had saved anything).
 */
function fcGetPersistedFenceSectionCount() {
    var raw = localStorage.getItem('custom_fence-section');
    var n = raw === null || raw === '' ? NaN : parseInt(raw, 10);

    var count = 0;
    for (var i = 0; i < 64; i++) {
        if (localStorage.getItem('custom_fence-' + i) != null) {
            count = i + 1;
        } else {
            break;
        }
    }

    if (Number.isFinite(n) && n >= 1) {
        if (count > n) {
            localStorage.setItem('custom_fence-section', String(count));
            return count;
        }
        return n;
    }

    if (count >= 1) {
        localStorage.setItem('custom_fence-section', String(count));
        return count;
    }
    return 0;
}

//----------------------------------------------------------------------------------

function fcGatherFenceSectionStorageSnapshot(oldIdx0) {
    var raw = {};
    var cfPrefix = 'custom_fence-' + oldIdx0;
    var cartPrefix = 'cart_items-' + (oldIdx0 + 1);
    var keys = [];
    for (var ki = 0; ki < localStorage.length; ki++) {
        keys.push(localStorage.key(ki));
    }
    keys.forEach(function(key) {
        if (!key) {
            return;
        }
        if (key === cfPrefix || key.indexOf(cfPrefix + '-') === 0) {
            raw[key] = localStorage.getItem(key);
        } else if (key === cartPrefix || key.indexOf(cartPrefix + '-') === 0) {
            raw[key] = localStorage.getItem(key);
        }
    });
    return raw;
}

function fcRemapFenceStorageKey(key, oldIdx0, newIdx0) {
    var cfOld = 'custom_fence-' + oldIdx0;
    var cfNew = 'custom_fence-' + newIdx0;
    if (key === cfOld) {
        return cfNew;
    }
    if (key.indexOf(cfOld + '-') === 0) {
        return cfNew + key.slice(cfOld.length);
    }
    var cartOld = 'cart_items-' + (oldIdx0 + 1);
    var cartNew = 'cart_items-' + (newIdx0 + 1);
    if (key === cartOld) {
        return cartNew;
    }
    if (key.indexOf(cartOld + '-') === 0) {
        return cartNew + key.slice(cartOld.length);
    }
    return key;
}

function fcApplyFenceSectionSnapshotAtNewIndex(snapshotKeyVals, oldIdx0, newIdx0) {
    Object.keys(snapshotKeyVals).forEach(function(oldKey) {
        var newKey = fcRemapFenceStorageKey(oldKey, oldIdx0, newIdx0);
        var val = snapshotKeyVals[oldKey];
        if (newKey === 'custom_fence-' + newIdx0 && val) {
            try {
                var parsed = JSON.parse(val);
                if (parsed && parsed[0]) {
                    parsed[0].tab = newIdx0;
                    val = JSON.stringify(parsed);
                }
            } catch (e) {}
        }
        localStorage.setItem(newKey, val);
    });
}

function fcStripNumericCustomFenceAndCartLocalStorage() {
    var keys = [];
    for (var ri = 0; ri < localStorage.length; ri++) {
        keys.push(localStorage.key(ri));
    }
    keys.forEach(function(key) {
        if (!key) {
            return;
        }
        if (/^custom_fence-\d+/.test(key) || /^cart_items-\d+/.test(key)) {
            localStorage.removeItem(key);
        }
    });
}

function fcReorderProjectPlansColorForSectionCompaction(keepOldIndices) {
    try {
        var raw = localStorage.getItem('project-plans');
        if (!raw) {
            return;
        }
        var project_plans = JSON.parse(raw);
        if (!project_plans || !Array.isArray(project_plans.color)) {
            return;
        }
        var oldColors = project_plans.color;
        project_plans.color = keepOldIndices
            .map(function(oldI) {
                var row = oldColors[oldI];
                if (
                    !row ||
                    row.fence == null ||
                    row.color == null ||
                    String(row.fence) === '' ||
                    String(row.color) === ''
                ) {
                    return null;
                }
                var fenceSlug = String(row.fence);
                if (typeof normalizeFenceStyleSlug === 'function') {
                    fenceSlug = normalizeFenceStyleSlug(fenceSlug);
                }
                return { fence: fenceSlug, color: row.color };
            })
            .filter(Boolean);
        localStorage.setItem('project-plans', JSON.stringify(project_plans));
    } catch (e) {}
}

/**
 * Fence section tabs in the planner strip only (matches `HELPER.getFenceSectionTabCount` scope).
 */
function fcGetPlannerSectionTabs$() {
    var $area = $('.fc-planner-page').find(FENCES.el.tabArea).first();
    if (!$area.length) {
        $area = $(FENCES.el.tabArea).first();
    }
    return $area.children('.fencing-tab');
}

/**
 * Switch to Section 1 before leaving Section Details (e.g. NEXT → Plan Options).
 * Runs `onReady` immediately when Section 1 is already active.
 *
 * @param {Function} onReady
 */
function fcPlannerActivateSectionOneThen(onReady) {
    if (typeof onReady !== 'function') {
        return;
    }
    if (!$('.fc-planner-page').length) {
        onReady();
        return;
    }
    var $tabs =
        typeof fcGetPlannerSectionTabs$ === 'function'
            ? fcGetPlannerSectionTabs$()
            : $('.fc-planner-page .fencing-tab');
    if (!$tabs.length) {
        onReady();
        return;
    }
    var $section1 = $tabs.filter('.fc-section-1').first();
    if (!$section1.length) {
        $section1 = $tabs.first();
    }
    if ($section1.hasClass('fencing-tab-selected')) {
        onReady();
        return;
    }
    $section1.trigger('click');
    setTimeout(onReady, 100);
}

/**
 * How many section indices (0..n-1) to evaluate when dropping incomplete rows.
 * Uses counter, persisted count, DOM tabs, and highest custom_fence-{n} base key so stale
 * `custom_fence-section` cannot skip trailing sections.
 */
function fcGetFenceSectionCompactionSlotCount() {
    var domCount = 0;
    if (typeof HELPER !== 'undefined' && typeof HELPER.getFenceSectionTabCount === 'function') {
        domCount = HELPER.getFenceSectionTabCount();
    } else if (typeof fcGetPlannerSectionTabs$ === 'function') {
        domCount = fcGetPlannerSectionTabs$().length;
    } else if (typeof $ !== 'undefined' && FENCES && FENCES.el && FENCES.el.fencingTab) {
        domCount = $(FENCES.el.fencingTab).length;
    }
    var storedRaw = localStorage.getItem('custom_fence-section');
    var storedCount = parseInt(storedRaw, 10);
    var fromCounter = Number.isFinite(storedCount) && storedCount > 0 ? storedCount : 0;
    var fromPersist =
        typeof fcGetPersistedFenceSectionCount === 'function' ? fcGetPersistedFenceSectionCount() : 0;
    var maxBaseIdx = -1;
    try {
        for (var ki = 0; ki < localStorage.length; ki++) {
            var key = localStorage.key(ki);
            if (!key) {
                continue;
            }
            var m = /^custom_fence-(\d+)$/.exec(key);
            if (m) {
                var ix = parseInt(m[1], 10);
                if (ix > maxBaseIdx) {
                    maxBaseIdx = ix;
                }
            }
        }
    } catch (e) {}
    var fromKeys = maxBaseIdx >= 0 ? maxBaseIdx + 1 : 0;
    return Math.max(fromCounter, fromPersist, domCount, fromKeys, 1);
}

function fcRebuildFencingTabsAfterCompaction(newCount, onReady) {
    var $area = $('.fc-planner-page').find(FENCES.el.tabArea).first();
    if (!$area.length) {
        $area = $(FENCES.el.tabArea).first();
    }
    $area.empty();
    for (var i = 1; i <= newCount; i++) {
        var index = i - 1;
        var sectionTab =
            '<div class="fencing-tab fencing-tab-selected fc-section-' +
            i +
            '">' +
            '<div class="fencing-tab-name">' +
            fcPlannerSectionTabStatusHtml() +
            '<span class="ftm-title">SECTION</span> <span class="fencing-tab-number">' +
            i +
            '</span>' +
            '<div class="ftm-fence-style" hidden></div>' +
            '<div class="ftm-measurement"></div>' +
            '</div>' +
            '</div>';
        $area.append(sectionTab);
        $area.children('.fencing-tab').removeClass('fencing-tab-selected');
        $area.children('.fencing-tab').last().addClass('fencing-tab-selected');
        var custom_fence_tabs = localStorage.getItem('custom_fence-' + index);
        var data_tabs = [];
        if (custom_fence_tabs) {
            try {
                data_tabs = JSON.parse(custom_fence_tabs);
            } catch (e) {
                data_tabs = [];
            }
        }
        var mesurement = data_tabs[0]?.calculateValue
            ? parseInt(data_tabs[0].calculateValue, 10).toLocaleString() + ' ' + FENCES.defaultValues.unit
            : '';
        $area.find('.fencing-tab.fencing-tab-selected').first().find('.ftm-measurement').html(mesurement);
        if (typeof fcSyncPlannerTabFenceStyle === 'function') {
            fcSyncPlannerTabFenceStyle($area.find('.fencing-tab.fencing-tab-selected').first(), index);
        }
        $(FENCES.el.jsFcFormStep).hide();
    }
    HELPER.hideDeleteSectionBtn();
    HELPER.tabContainerScroll();
    HELPER.refreshSectionTabIndex();
    setTimeout(function() {
        var $first = $area.children('.fencing-tab').first();
        if ($first.length) {
            $first.trigger('click');
        }
        if (typeof window.fcRefreshFencingStylesSlick === 'function') {
            requestAnimationFrame(function() {
                window.fcRefreshFencingStylesSlick();
            });
        }
        if (typeof window.fcRefreshColorOptionsSlick === 'function') {
            requestAnimationFrame(function() {
                window.fcRefreshColorOptionsSlick();
            });
        }
        try {
            if (typeof fcCapturePlannerUpdateFenceBaseline === 'function') {
                fcCapturePlannerUpdateFenceBaseline();
            }
            if (typeof fcSyncPlannerUpdateButtonVisibility === 'function') {
                fcSyncPlannerUpdateButtonVisibility();
            }
        } catch (err) {}
        setTimeout(function() {
            if (typeof onReady === 'function') {
                onReady();
            }
        }, 200);
    }, 100);
    if (typeof fcSyncAllPlannerSectionTabStatuses === 'function') {
        fcSyncAllPlannerSectionTabStatuses();
    }
}

/**
 * Remove incomplete (empty / not calculated) sections, reindex storage and tabs.
 * Used when user chooses "Proceed to project plan" on the incomplete-sections modal.
 * @param {function(boolean):void} [done] — called with true when ready to continue submit, false if aborted
 */
function fcCompactFencePlannerRemoveIncompleteSections(done) {
    var finishOk = function() {
        if (typeof done === 'function') {
            done(true);
        }
    };
    var finishAbort = function() {
        if (typeof done === 'function') {
            done(false);
        }
    };

    var sectionCount = fcGetFenceSectionCompactionSlotCount();

    var keepOldIndices = [];
    for (var i = 0; i < sectionCount; i++) {
        if (!fcFenceSectionIncompleteFromStorage(i)) {
            keepOldIndices.push(i);
        }
    }

    if (keepOldIndices.length === sectionCount) {
        finishOk();
        return;
    }

    if (keepOldIndices.length === 0) {
        $('.fc-loader-overlay').hide();
        window.alert(
            'There are no completed sections to save. Run Calculate on at least one fence section, then try again.'
        );
        finishAbort();
        return;
    }

    var snapshots = [];
    for (var s = 0; s < keepOldIndices.length; s++) {
        snapshots.push(fcGatherFenceSectionStorageSnapshot(keepOldIndices[s]));
    }

    fcStripNumericCustomFenceAndCartLocalStorage();

    for (var n = 0; n < keepOldIndices.length; n++) {
        fcApplyFenceSectionSnapshotAtNewIndex(snapshots[n], keepOldIndices[n], n);
    }

    localStorage.setItem('custom_fence-section', String(keepOldIndices.length));

    fcReorderProjectPlansColorForSectionCompaction(keepOldIndices);

    fcRebuildFencingTabsAfterCompaction(keepOldIndices.length, finishOk);
}

/**
 * Rebuild cart_items-* from the live fence DOM one section at a time.
 * A tight synchronous loop fails: tab switches + fence renders are async, so cart rows were empty on project-plan.
 */
/**
 * Rebuild cart BOM for one planner / project-plan section (rows × panels for slat).
 */
function fcRefreshPlannerCartForTab(tabIdx) {
    if (typeof FENCES === 'undefined' || !FENCES.cartItems || typeof FENCES.cartItems.init !== 'function') {
        return;
    }
    tabIdx = parseInt(tabIdx, 10);
    if (!Number.isFinite(tabIdx) || tabIdx < 0) {
        return;
    }
    try {
        FENCES.cartItems.init(tabIdx, { skipTabClick: true });
    } catch (e) {}
}

/**
 * Rebuild every section cart in localStorage (sync, no tab-click delay).
 */
function fcRebuildAllPlannerCarts(sectionCount) {
    var n = parseInt(sectionCount, 10);
    if (!Number.isFinite(n) || n < 1) {
        return;
    }
    for (var i = 0; i < n; i++) {
        fcRefreshPlannerCartForTab(i);
    }
}

function fcRebuildPlannerCartSequential(sectionCount, onDone) {
    removeItemStorageWith('cart_items-');
    if (!sectionCount || sectionCount < 1) {
        if (typeof onDone === 'function') {
            onDone();
        }
        return;
    }
    var idx = 0;
    var stepDelayMs = 120;
    function step() {
        if (idx >= sectionCount) {
            if (typeof onDone === 'function') {
                onDone();
            }
            return;
        }
        try {
            var planRoot = document.querySelector('#pp-' + idx);
            FENCES.cartItems.init(idx, {
                skipTabClick: !!planRoot,
                scopeRoot: planRoot || FENCES.cartItems.getProcessScopeRoot(idx)
            });
        } catch (e) {}
        idx++;
        setTimeout(step, stepDelayMs);
    }
    step();
}

/**
 * Project plan: refresh $_SESSION['fc_cart'] from recomputed localStorage cart_items.
 */
function fcSyncProjectPlanSessionCart(onDone) {
    if (typeof getCartItemStorage !== 'function') {
        if (typeof onDone === 'function') {
            onDone(false);
        }
        return;
    }
    var cart_items = getCartItemStorage();
    if (!cart_items || !cart_items.length) {
        if (typeof onDone === 'function') {
            onDone(false);
        }
        return;
    }

    var formData = new FormData();
    formData.set('action', 'rebuild_cart_from_plans');
    formData.set('cart_items', JSON.stringify(cart_items));

    try {
        var colors = null;
        if (typeof fcCollectPlannerColorRowsFromDom === 'function') {
            colors = fcCollectPlannerColorRowsFromDom();
        }
        if (!Array.isArray(colors) || !colors.length) {
            var project_plans = JSON.parse(localStorage.getItem('project-plans') || '{}') || {};
            if (Array.isArray(project_plans.color)) {
                colors = project_plans.color;
            }
        }
        if (Array.isArray(colors) && colors.length) {
            formData.set('color', JSON.stringify(colors));
        }
        var projectPlansRaw = localStorage.getItem('project-plans');
        if (projectPlansRaw) {
            formData.set('project_plans', projectPlansRaw);
        }
    } catch (eColor) {}

    var checkoutUrl =
        typeof base_url === 'function'
            ? base_url('checkout')
            : 'checkout';

    fetch(checkoutUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(function(res) {
            return res.text();
        })
        .then(function(html) {
            if (html && typeof $ !== 'undefined') {
                var $table = $('.fc-table-items');
                if ($table.length) {
                    $table.html(html);
                }
            }
            if (typeof onDone === 'function') {
                onDone(true);
            }
        })
        .catch(function() {
            if (typeof onDone === 'function') {
                onDone(false);
            }
        });
}

//----------------------------------------------------------------------------------

var fcPlannerSubmitInFlight = false;

/**
 * Read the saved quote id back from /submit ("SUCCESS:<planner_id>").
 */
function fcParseSavedPlannerId(response) {
    if (typeof response !== 'string') {
        return '';
    }
    var parts = response.trim().split(':');
    return parts.length > 1 ? parts[1].trim() : '';
}

function submit_fence_planner(status = '', options) {
    options = options || {};
    var forceProceed = options.forceProceed === true;

    function runSubmitBody() {
        var set_fc_data = [];
        var incompleteSection = 0;
        var formStyle = '';

        var $submitTabs =
            typeof fcGetPlannerSectionTabs$ === 'function'
                ? fcGetPlannerSectionTabs$()
                : $('.fc-planner-page').find(FENCES.el.tabArea).first().children('.fencing-tab');
        if (!$submitTabs.length) {
            $submitTabs = $('.fencing-tab');
        }

        $submitTabs.each(function(tid) {
            var raw = localStorage.getItem('custom_fence-' + tid);
            var form = null;
            if (raw) {
                try {
                    form = JSON.parse(raw);
                } catch (e) {
                    form = null;
                }
            }
            var settings = null;

            if (form != null) {

                settings = readPlannerSectionSettingsBlob(tid, form[0]?.style);
                if (!formStyle && form[0]?.style) {
                    formStyle = form[0].style;
                }

                form[0].style = form[0]?.style;
                var tabIdx =
                    typeof form[0]?.tab === 'number' && Number.isFinite(form[0].tab) ? form[0].tab : tid;
                form[0].tab = tabIdx + 1;

                set_fc_data.push({
                    'form': form,
                    'settings': settings
                });

                var rowComplete =
                    typeof fcPlannerTabRowHasPlanData === 'function'
                        ? fcPlannerTabRowHasPlanData(form[0], form[0]?.style)
                        : !!form[0]?.calculateValue;
                if (!rowComplete) {
                    incompleteSection += 1;
                }

            } else {
                incompleteSection += 1;
            }

        });

        if (incompleteSection > 0) {
            $('.fc-loader-overlay').hide();
            if (forceProceed) {
                window.alert(
                    'Could not prepare your plan: some sections are still incomplete after cleanup. Please run Calculate on each section you want to keep.'
                );
            } else {
                fcShowIncompleteSectionsModal(status);
            }
            return false;
        }

        var items = typeof fcGetPersistedFenceSectionCount === 'function'
            ? fcGetPersistedFenceSectionCount()
            : parseInt(localStorage.getItem('custom_fence-section'), 10);
        if (!Number.isFinite(items) || items < 1) {
            items = 1;
        }

        function afterCartReady() {
            var domColors = [];
            if ($('.fc-planner-page').length && $('#fc-planning-form .fc-step-4 .fc-color-options').length) {
                domColors = fcCollectPlannerColorRowsFromDom();
                updateOrCreateObjectInLocalStorage('project-plans', { color: domColors });
            } else if (typeof fcCollectPlannerColorRowsFromDom === 'function') {
                domColors = fcCollectPlannerColorRowsFromDom();
            }

            var project_plans = JSON.parse(localStorage.getItem('project-plans')) || {};
            if (!Array.isArray(project_plans.color) || !project_plans.color.length) {
                project_plans.color = domColors;
            }
            var cart_items = getCartItemStorage();
            if (!cart_items || cart_items.length === 0) {
                if (
                    typeof fcHydratePlannerCartItemsLocalStorage === 'function' &&
                    typeof fc_fence_info !== 'undefined' &&
                    fc_fence_info &&
                    fc_fence_info.cart_items_data
                ) {
                    fcHydratePlannerCartItemsLocalStorage(fc_fence_info.cart_items_data, { clearFirst: false });
                    cart_items = getCartItemStorage();
                }
            }
            if (!cart_items || cart_items.length === 0) {
                $('.fc-loader-overlay').hide();
                if (typeof fcShowPopupAlertModal === 'function') {
                    fcShowPopupAlertModal(
                        'Materials list',
                        'Could not build the materials list for your fence. Open each section tab, run Calculate, then try again.'
                    );
                } else {
                    window.alert(
                        'Could not build the materials list for your fence. Open each section tab, run Calculate, then try again.'
                    );
                }
                return;
            }

            var form = $('form')[0];
            var formData = new FormData(form);

            formData.set("fences", JSON.stringify(set_fc_data));

            formData.set("cart_items", JSON.stringify(cart_items));

            formData.set("project_plans", JSON.stringify(project_plans));

            if (Array.isArray(project_plans.color)) {
                project_plans.color.forEach(function(row, idx) {
                    if (!row || !row.fence || !row.color) {
                        return;
                    }
                    formData.set('color[' + idx + '][fence]', row.fence);
                    formData.set('color[' + idx + '][color]', row.color);
                });
            }

            Object.entries(project_plans || {}).forEach(([key, value]) => {
                if (typeof value === 'object') {
                    value = JSON.stringify(value);
                }

                if (key === 'mobile' && value != null && value !== '') {
                    value = fcNormalizeMobileForStorage(value);
                }

                if (key.includes("[]")) {
                    key = key.replace('[]', '');
                }

                formData.set(key, value);
            });

            // Send the quote id the page was rendered with: /submit can then update the existing
            // row even when the PHP session no longer holds it (expired session, dropped cookie).
            var existingPlannerId = fcPlannerHasQuoteId() ? String(planner_id).trim() : '';
            if (existingPlannerId) {
                formData.set('planner_id', existingPlannerId);
            }

            // Tells /submit not to overwrite the 'reloaded' status this same page load
            // just set server-side (PlannerController -> PlannerRecordService::markReloaded()).
            formData.set('is_quote_reload', options.isQuoteReload ? '1' : '0');

            // Only the Download-Plans modal's final step sets this — lets SubmitController
            // tell this apart from the ordinary Update button, which also POSTs to /submit.
            if (options.triggerEarlyWebhook) {
                formData.set('trigger_early_webhook', '1');
            }

            var uri_success = '';
            if (formStyle == 'barr') {
                uri_success = '&barr-success';
            } else if (formStyle == 'flat_top') {
                uri_success = '&ftpf-success';
            } else if (formStyle == 'glass_pool') {
                uri_success = '&glass-success';
            }

            // A second POST while the first is still open would save the plan twice.
            if (fcPlannerSubmitInFlight) {
                return;
            }
            fcPlannerSubmitInFlight = true;

            $.ajax({
                url: 'submit',
                type: "POST",
                data: formData,
                headers: {},
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    try {

                        var savedPlannerId = fcParseSavedPlannerId(response);
                        if (savedPlannerId && typeof planner_id !== 'undefined') {
                            planner_id = savedPlannerId;
                        }

                        var quoteId = savedPlannerId || existingPlannerId;
                        var count = 0;
                        var target = quoteId
                            ? 'project-plan?project-success&qid=' + encodeURIComponent(quoteId) + uri_success
                            : 'project-plan?project-success' + uri_success;

                        setTimeout(function() {
                            $('.fc-loader ul li').each(function(i) {
                                var _this = $(this);
                                setTimeout(function() {
                                    _this.addClass('fc-text-success');
                                    count++;
                                    if (count == 1) {
                                        window.location = target;
                                    }
                                }, 1000 * i);
                            });
                        }, 1000);
                    } catch (err) {

                    }
                },
                error: function() {
                    fcPlannerSubmitInFlight = false;
                    $('.fc-loader-overlay').hide();
                    if (typeof fcShowPopupAlertModal === 'function') {
                        fcShowPopupAlertModal(
                            'Save failed',
                            'We could not save your plan. Please check your connection and try again.'
                        );
                    } else {
                        window.alert('We could not save your plan. Please try again.');
                    }
                }
            });
        }

        if (options.skipCartRebuild) {
            var hydratedCart = getCartItemStorage();
            if (!hydratedCart || !hydratedCart.length) {
                if (
                    typeof fcHydratePlannerCartItemsLocalStorage === 'function' &&
                    typeof fc_fence_info !== 'undefined' &&
                    fc_fence_info &&
                    fc_fence_info.cart_items_data
                ) {
                    fcHydratePlannerCartItemsLocalStorage(fc_fence_info.cart_items_data);
                    hydratedCart = getCartItemStorage();
                }
            }

            if (hydratedCart && hydratedCart.length) {
                afterCartReady();
                return;
            }

            fcRebuildPlannerCartSequential(items, afterCartReady);
            return;
        }

        fcRebuildPlannerCartSequential(items, afterCartReady);
    }

    if (forceProceed) {
        fcCompactFencePlannerRemoveIncompleteSections(function(ok) {
            if (ok !== true) {
                return;
            }
            runSubmitBody();
        });
    } else {
        runSubmitBody();
    }
}

//----------------------------------------------------------------------------------

/**
 * Planner Step 4 — one skeleton card per active fence type (matches loaded colour sections).
 */
function renderPlannerColorOptionsSkeletonCards(count, tplEl, $host) {
    $host.empty();
    if (!tplEl || count < 1) {
        return;
    }
    var frag = document.createDocumentFragment();
    var i;
    for (i = 0; i < count; i++) {
        frag.appendChild(tplEl.content.cloneNode(true));
    }
    $host.append(frag);
}

/**
 * Show real colour cards (opacity fade-in), init Slick after mount is displayed, then remove skeletons.
 */
function transitionPlannerColorOptionsReveal($host, $mount) {
    $mount.removeClass('fc-d-none').addClass('fc-color-options-mount--entering');
    requestAnimationFrame(function() {
        if (typeof window.fcRefreshColorOptionsSlick === 'function') {
            window.fcRefreshColorOptionsSlick();
        }
        requestAnimationFrame(function() {
            setTimeout(function() {
                $host.addClass('fc-planner-skeleton-host--exiting');
                requestAnimationFrame(function() {
                    $mount.addClass('fc-color-options-mount--visible');
                    $host.addClass('fc-planner-skeleton-host--hidden');
                });
                setTimeout(function() {
                    $host.addClass('fc-d-none')
                        .empty()
                        .removeClass('fc-planner-skeleton-host--exiting fc-planner-skeleton-host--hidden');
                    $mount.removeClass('fc-color-options-mount--entering fc-color-options-mount--visible');
                    if (typeof window.fcRefreshColorOptionsSlick === 'function') {
                        window.fcRefreshColorOptionsSlick();
                    }
                }, 430);
            }, 55);
        });
    });
}

function loadColorOptions() {
    var $host = $('.js-fc-color-options-skeleton-host');
    var $mount = $('.js-fc-color-options-mount');
    var tplEl = document.getElementById('fc-planner-color-options-skeleton-card');
    var hasSkeleton = $host.length && $mount.length && tplEl;

    function applyColorOptionsFill() {
        var project = null;
        try {
            project = JSON.parse(localStorage.getItem('project-plans'));
        } catch (e) {
            project = null;
        }

        var colorOption = $('[data-load="color-options"]');
        var items = getActiveFencing();

        colorOption.html('');

        $('.fc-btn-create-plan').prop('disabled', true);
        if (typeof fcApplyPlannerUpdateDisabledFromColors === 'function') {
            fcApplyPlannerUpdateDisabledFromColors();
        }

        $.each(items, function(k, v) {
            if (v) {
                var slug = fc_data[v].slug,
                    title = fc_data[v].title,
                    colors = fc_data[v].color;

                var numSections =
                    typeof fcPlannerSectionCountForFenceStyle === 'function'
                        ? fcPlannerSectionCountForFenceStyle(slug, v)
                        : 0;
                var sectionCountLabel = numSections > 0 ? 'x ' + numSections : '';

                var tpl = $('script[data-type="color_options"]').text()
                    .replace(/{{slug}}/gi, slug)
                    .replace(/{{title}}/gi, title)
                    .replace(/{{section_count_label}}/gi, sectionCountLabel);

                colorOption.append(tpl);

                var $group = colorOption.find('.fc-color-options[data-slug="' + slug + '"]').last();
                var allowedColors = Array.isArray(colors) ? colors : [];

                // Keep only style colors, in the order from fence style settings
                // e.g. 'color' => ['black', 'white', 'monument']
                $.each(allowedColors, function(ci, colorSlug) {
                    var $item = $group.find('.fc-select-color[data-slug="' + colorSlug + '"]');
                    if (!$item.length) {
                        return;
                    }
                    $item.addClass('on');
                    var $slide = $item.closest('.fc-color-options__slide');
                    if ($slide.length) {
                        $group.append($slide);
                    }
                });
                $group.find('.fc-select-color:not(.on)').closest('.fc-color-options__slide').remove();
                $group.find('.fc-select-color:not(.on)').remove();
            }
        });

        if (project && project.color) {
            $.each(project.color, function(k, v) {
                if (!v || v.fence == null || v.color == null) {
                    return;
                }
                $('#fc-planning-form .fc-color-options[data-slug="' + v.fence + '"] .fc-select-item[data-slug="' + v.color + '"]').addClass('fc-selected');
            });

            var $plannerStep4 = $('#fc-planning-form .fc-step-4');
            if ($plannerStep4.find('.fc-color-options').length && fcPlannerColorOptionGroupsComplete($plannerStep4)) {
                $('.fc-btn-create-plan').prop('disabled', false);
            }

        }

        if (typeof fcApplyPlannerUpdateDisabledFromColors === 'function') {
            fcApplyPlannerUpdateDisabledFromColors();
        }

        if (hasSkeleton) {
            $host.attr('aria-busy', 'false');
            transitionPlannerColorOptionsReveal($host, $mount);
        } else if (typeof window.fcRefreshColorOptionsSlick === 'function') {
            requestAnimationFrame(function() {
                window.fcRefreshColorOptionsSlick();
            });
        }

        //  setActiveColor();
    }

    if (hasSkeleton) {
        var count = getActiveFencing().filter(Boolean).length;

        $mount.addClass('fc-d-none').removeClass('fc-color-options-mount--visible fc-color-options-mount--entering');

        $host.removeClass('fc-d-none fc-planner-skeleton-host--exiting fc-planner-skeleton-host--hidden').empty();

        if (count > 0) {
            renderPlannerColorOptionsSkeletonCards(count, tplEl, $host);
            $host.attr('aria-busy', 'true');
        } else {
            $host.attr('aria-busy', 'false').addClass('fc-d-none');
        }

        requestAnimationFrame(function() {
            requestAnimationFrame(applyColorOptionsFill);
        });
    } else {
        applyColorOptionsFill();
    }
}

//----------------------------------------------------------------------------------

/**
 * Update element
 * @param {string} control_key 
 * @param {string} property 
 * @param {string} value 
 */
function updateElement(control_key, property, value) {
    if (typeof document.querySelector('.js-' + control_key + '-' + property) === undefined) {
        return;
    }
    let getEl = document.querySelector('.js-' + control_key + '-' + property);
    if (property === "color_code") {
        getEl.style.backgroundColor = value;

        if (getEl.querySelector('strong').textContent.toLowerCase().includes('white')) {
            getEl.querySelector('strong').style.color = "#000";
        }
    } else {
        getEl.textContent = value;
    }
}

//----------------------------------------------------------------------------------

function restore_items(remove_index) {
    var last_tid = $('.fencing-tab:last-child').index();
    $(".fencing-tab").each(function() {
        var tid = $(this).index();
        if (remove_index <= tid) {
            var next_index = tid + 1;
            form = JSON.parse(localStorage.getItem('custom_fence-' + next_index));
            settings = localStorage.getItem('custom_fence-' + next_index + '-' + form[0].style);
            // Update items
            localStorage.setItem('custom_fence-' + tid, JSON.stringify(form));
            if (settings) {
                localStorage.setItem('custom_fence-' + tid + '-' + form[0].style, settings);
            }
        }
    });
}

//----------------------------------------------------------------------------------

/** Saved Step 2 field value from `fieldsByStyle` (null = field not stored for this style). */
function fcStep2SavedFieldValue(tabRow0, slugNorm, fieldName) {
    if (!tabRow0 || !fieldName) {
        return null;
    }
    var restored = fcStep2RestoreFieldsForStyle(tabRow0, slugNorm);
    var found = false;
    for (var i = 0; i < restored.length; i++) {
        if (restored[i] && restored[i].name === fieldName) {
            found = true;
            var v = restored[i].value;
            if (v !== undefined && v !== null && String(v) !== '') {
                return String(v);
            }
            return '';
        }
    }
    return found ? '' : null;
}

/** Apply Barr Step 2 catalog default for Fence Height (1200mm) when no saved value. */
function fcApplyBarrFenceHeightDefault(info) {
    if (!info || !Array.isArray(info.form)) {
        return;
    }
    var formField = null;
    for (var fi = 0; fi < info.form.length; fi++) {
        if (info.form[fi] && info.form[fi].slug === 'fence_height') {
            formField = info.form[fi];
            break;
        }
    }
    if (!formField || formField.default === undefined || formField.default === null || formField.default === '') {
        return;
    }
    var $fh = $('[data-section="2"] [name="fence_height"]');
    if (!$fh.length) {
        return;
    }
    $fh.val(String(formField.default));
    if ($fh.data('select2')) {
        $fh.trigger('change.select2');
    }
}

/** Barr Fence Height: catalog default only when this style has no saved height yet. */
function fcApplyBarrFenceHeightIfUnsaved(tabRow0, slugNorm, info) {
    var slug =
        typeof normalizeFenceStyleSlug === 'function'
            ? normalizeFenceStyleSlug(slugNorm || '')
            : String(slugNorm || '');
    if (slug !== 'barr') {
        return;
    }
    var saved = fcStep2SavedFieldValue(tabRow0, slugNorm, 'fence_height');
    if (saved !== null && saved !== '') {
        return;
    }
    fcApplyBarrFenceHeightDefault(info);
}

function fcDestroyStep2Select2($targets) {
    if (typeof $.fn.select2 !== 'function') {
        return;
    }
    var $els = $targets && $targets.length ? $targets : $('[data-section="2"] select.form-control');
    $els.each(function() {
        var $s = $(this);
        if ($s.data('select2')) {
            $s.select2('destroy');
        }
    });
}

/** Searchable Select2 for Step 2 fence/slat dropdowns. */
function fcInitStep2Select2($targets) {
    if (typeof $.fn.select2 !== 'function') {
        return;
    }
    var $els = $targets && $targets.length ? $targets : $('[data-section="2"] select.form-control');
    $els.each(function() {
        var $s = $(this);
        if (!$s.length || $s.data('select2')) {
            return;
        }
        if (!$s.is(':visible')) {
            return;
        }
        var $parent = $s.closest('.js-fc-form-step[data-section="2"]');
        if (!$parent.length) {
            $parent = $('[data-section="2"]').first();
        }
        $s.select2({
            theme: 'bootstrap-5',
            width: '100%',
            minimumResultsForSearch: 0,
            placeholder: $s.find('option[value=""]').first().text() || 'Select',
            allowClear: $s.find('option[value=""]').length > 0,
            dropdownParent: $parent.length ? $parent : $(document.body)
        });
        if ($s.prop('disabled')) {
            $s.prop('disabled', true);
        }
    });
}

/** Re-init Select2 after options or value change. */
function fcRefreshStep2Select2($targets) {
    fcDestroyStep2Select2($targets);
    fcInitStep2Select2($targets);
}

//----------------------------------------------------------------------------------

function extra_fields() {
    var fd = getSelectedFenceData();

    var i = fd.slug,
        tab = fd.tab,
        custom_fence = fd.info,
        info = fd.data,
        tabInfo = fd.tabInfo;

    var modal_key = fd.modKey,
        mbn = fd.mbn;

    // [START] FORM FIELDS ON STEP 3
    fcDestroyStep2Select2();
    try {
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.resetStep2SlatFieldLayout === 'function') {
            SlatFence.resetStep2SlatFieldLayout();
        }
    } catch (eLayout) {}
    $('[data-action="change"]').html('');

    $.each(info.form, function(k, v) {

        var tpl = $('script[data-type="' + v.type + '"]').text()
            .replace(/{{title}}/gi, v.title)
            .replace(/{{slug}}/gi, v.slug)
            .replace(/{{description}}/gi, v.description)
            .replace(/{{default}}/gi, v.default ?? '')
            .replace(/{{min}}/gi, v.min ?? '')
            .replace(/{{max}}/gi, v.max ?? '');


        $(v.target).append(tpl);

        if (
            (v.type === 'slat-max-height-input' || v.type === 'slat-max-height-select') &&
            typeof SlatFence !== 'undefined'
        ) {
            var mhInit = document.querySelector('[data-section="2"] [name="max_fence_height"]');
            if (v.type === 'slat-max-height-input' && typeof SlatFence.refreshStep2MaxFenceHeightBounds === 'function') {
                SlatFence.refreshStep2MaxFenceHeightBounds({});
            } else if (mhInit && typeof SlatFence.seedMaxFenceHeightPlaceholder === 'function') {
                SlatFence.seedMaxFenceHeightPlaceholder(mhInit, { force: true });
            }
        }

        // Select field / Step 2 slat gap dropdown
        if (v.type === 'select-field' || v.type === 'slat-gap-select' || v.type === 'slat-size-select') {
            var selectName = 'slat_gap';
            if (v.type === 'slat-size-select') {
                selectName = 'slat_size';
            } else if (v.type !== 'slat-gap-select') {
                selectName = v.slug;
            }
            if (v.type === 'slat-gap-select' && typeof SlatFence !== 'undefined') {
                SlatFence.populateSlatGapStep2Select(SlatFence.buildSlatGapSelectOptions(info));
            } else if (v.type === 'slat-size-select' && typeof SlatFence !== 'undefined') {
                var sizeRows =
                    Array.isArray(v.slat_size_rows) && v.slat_size_rows.length
                        ? v.slat_size_rows
                        : SlatFence.buildSlatSizeSelectOptions(info);
                SlatFence.populateSlatSizeStep2Select(sizeRows);
            } else {
                $.each(v.option, function(optVal, optLabel) {
                    $('[name="' + selectName + '"]').append(
                        $('<option>', {
                            value: optVal,
                            text: optLabel
                        })
                    );
                });
            }

            $('[name="' + selectName + '"]').val(v.default != null ? v.default : '');
        }

        if (v.slug === 'panel_count') {
            $('[name="panel_count"]').attr('step', '1');
        }

        // Radio field
        if (v.type === 'radio-field') {
            var $radioWrap = $('.js-radio-field[data-slug="' + v.slug + '"]');
            $radioWrap.html('');

            $.each(v.option, function(i, item) {
                var rawId = (v.slug + '_' + String(i));
                var safeId = rawId.replace(/[^a-zA-Z0-9\-_]/g, '_');

                var $row = $('<div>', { class: 'form-check' });
                var $input = $('<input>', {
                    class: 'form-check-input',
                    type: 'radio',
                    name: v.slug,
                    id: safeId,
                    value: i
                });
                var $label = $('<label>', {
                    class: 'form-check-label',
                    for: safeId,
                    text: item
                });

                $row.append($input).append($label);
                $radioWrap.append($row);
            });

            var defaultRadioValue = v.default ?? '';
            if (defaultRadioValue !== '' && defaultRadioValue !== null && defaultRadioValue !== undefined) {
                $('input[type="radio"][name="' + v.slug + '"][value="' + defaultRadioValue + '"]').prop('checked', true);
            }
        }

    });

    const restoreFields = fcStep2RestoreFieldsForStyle(tabInfo[0], i);
    $.each(restoreFields, function(k, v) {
        if (v.value === undefined || v.value === null || String(v.value) === '') {
            return;
        }
        // Defer until after height row layout + Select2 (field may sit in `.fc-step2-height-slot`).
        if (v.name === 'fence_height') {
            return;
        }
        // Fence Height is rebuilt after slat gap/size hydrate (avoids blank select on style switch).
        if (v.name === 'max_fence_height' && typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(i)) {
            return;
        }
        var $els = $('[name="' + v.name + '"]');
        if (!$els.length) return;

        if ($els.first().attr('type') === 'radio') {
            $els.filter('[value="' + v.value + '"]').prop('checked', true);
        } else {
            $els.val(v.value);
            if ($els.is('select') && $els.data('select2')) {
                $els.trigger('change.select2');
            }
        }
    });
    // Ensure panel_count is restored from tab storage (fallback)
    try {
        const customFenceTab = JSON.parse(localStorage.getItem('custom_fence-' + tab)) || [];
        var restoredPanels = fcStep2RestoreFieldsForStyle(customFenceTab[0], i);
        var savedPanel;
        for (var pxi = 0; pxi < restoredPanels.length; pxi++) {
            if (restoredPanels[pxi] && restoredPanels[pxi].name === 'panel_count') {
                savedPanel = restoredPanels[pxi].value;
                break;
            }
        }
        if (savedPanel !== undefined && savedPanel !== null && $('[name="panel_count"]').length) {
            $('[name="panel_count"]').val(savedPanel);
        }
    } catch (e) {
        // ignore
    }

    try {
        if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(i)) {
            SlatFence.hydrateStep2SlatSelects(i, tabInfo[0], custom_fence, info, {});
            SlatFence.scheduleStep2SlatSelect2AfterVisible(i, tabInfo[0]);
        }
    } catch (e2) {}

    try {
        if (
            typeof SlatFence !== 'undefined' &&
            typeof SlatFence.ensureStep2SlatHeightPairRow === 'function' &&
            (i === 'barr' || SlatFence.isSlatLike(i))
        ) {
            SlatFence.ensureStep2SlatHeightPairRow(i);
        } else if (
            typeof SlatFence !== 'undefined' &&
            typeof SlatFence.syncStep2BlocksSpacingBeforeOverall === 'function'
        ) {
            SlatFence.syncStep2BlocksSpacingBeforeOverall();
        }
    } catch (ePair) {}

    try {
        if (typeof SlatFence === 'undefined' || !SlatFence.isSlatLike(i)) {
            fcInitStep2Select2();
        }
    } catch (eS2) {}

    try {
        if (typeof fcReapplyStep2SavedFieldValues === 'function') {
            fcReapplyStep2SavedFieldValues(tabInfo[0], i);
        }
    } catch (eRe) {}

    try {
        if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(i)) {
            SlatFence.restoreStep2MaxFenceHeightAfterStep2Init(i, tabInfo[0]);
            var savedMm = SlatFence.getMaxFenceHeightValForStep2(tabInfo[0], i);
            if (!savedMm) {
                var mhClear = SlatFence.getStep2MaxFenceHeightEl();
                if (mhClear) {
                    mhClear.value = '';
                }
            }
        }
    } catch (eMh) {}

    try {
        if (typeof fcApplyStep2ForIncomingFenceStyle === 'function') {
            fcApplyStep2ForIncomingFenceStyle(tab, i);
        }
    } catch (eApply) {}

    try {
        fcApplyBarrFenceHeightIfUnsaved(tabInfo[0], i, info);
    } catch (eBarr) {}

    updateStep2MeasurementCopy(i);
    // [END] FORM FIELDS ON STEP 3
}


//----------------------------------------------------------------------------------

/**
 * Colour rows currently shown in the UI (planner Step 4 vs project-plan modal), one entry per fence group.
 * Avoids global `.fc-color-options` (Step 4 + #submit-modal duplicates) and Slick clone `.fc-selected` noise.
 */
function fcCollectPlannerColorRowsFromDom() {
    var color_data = [];
    var $groups;
    if (
        $('.fc-planner-page').length &&
        $('#fc-planning-form .fc-step-4 .fc-color-options').length
    ) {
        $groups = $('#fc-planning-form .fc-step-4 .fc-color-options');
    } else if ($('#submit-modal.fencing-modal--project-plans .fc-color-options').length) {
        $groups = $('#submit-modal.fencing-modal--project-plans .fc-color-options');
    } else {
        $groups = $(FENCES.el.fcColorOptions);
    }

    $groups.each(function() {
        var $g = $(this);
        var fenceRaw = $g.attr('data-slug');
        if (!fenceRaw) {
            return;
        }
        var $sel = $g
            .find('.fc-select-item.fc-selected')
            .filter(function() {
                return $(this).closest('.slick-cloned').length === 0;
            })
            .first();
        var color = $sel.attr('data-slug');
        if (!color) {
            return;
        }
        var fenceSlug =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(String(fenceRaw))
                : String(fenceRaw);
        color_data.push({ fence: fenceSlug, color: color });
    });

    return color_data;
}

/**
 * Project plan modal → sync selected colours into hidden form inputs (your-project-details).
 * Only reads from the submit modal carousel, not the read-only colour display cards.
 */
function fcSyncProjectPlanColorHiddenInputsFromModal() {
    if (typeof fcCollectPlannerColorRowsFromDom !== 'function') {
        return [];
    }

    var colors = fcCollectPlannerColorRowsFromDom();
    if (!Array.isArray(colors) || !colors.length) {
        return colors;
    }

    colors.forEach(function(row) {
        if (!row || !row.fence || !row.color) {
            return;
        }

        var normFence =
            typeof normalizeFenceStyleSlug === 'function'
                ? normalizeFenceStyleSlug(String(row.fence))
                : String(row.fence);

        $('.your-project-details .fc-color-options').each(function() {
            var $group = $(this);
            var slugRaw = String($group.attr('data-slug') || '');
            var slugNorm =
                typeof normalizeFenceStyleSlug === 'function'
                    ? normalizeFenceStyleSlug(slugRaw)
                    : slugRaw;

            if (slugNorm !== normFence && slugRaw !== String(row.fence)) {
                return;
            }

            $group.find('.input-fence').val(slugRaw || normFence);
            $group.find('.input-color').val(row.color);
        });
    });

    try {
        updateOrCreateObjectInLocalStorage('project-plans', { color: colors });
    } catch (e) {}

    return colors;
}

function update_color_options() {
    colorData = color_data = [];
    color_data = fcCollectPlannerColorRowsFromDom();
    colorData = { color: color_data };
    updateOrCreateObjectInLocalStorage('project-plans', colorData);
}

//----------------------------------------------------------------------------------

// raked panel
function computeOverallraked(value, side, leftRakedBefore, rightRakedBefore) {
    var rakedCount = $('.' + side + '-panel .fencing-panel-item-size').length,
        rakedBefore = (side == 'left_raked') ? leftRakedBefore : rightRakedBefore;

    var fd = getSelectedFenceData();

    var tabInfo = fd.tabInfo,
        slug = fd.info,
        custom_fence = fd.info,
        data = fd.data;

    var mbn = parseInt($('.measurement-box-number').val()),
        raked = 1200 + FENCE.get(slug, 'post');

    if (value != 'none' && rakedCount && rakedBefore == 0) {
        $('.measurement-box-number').val(mbn + raked);
    }

    if (value == 'none' && rakedCount == 0) {
        $('.measurement-box-number').val(mbn - raked);
    }

    // $('.btn-fc-calculate').trigger('click');
    btnCalculate();
}

//----------------------------------------------------------------------------------

/**
 * @TODO - This is a temporary solution
 */
function removeDuplicateCloseBtn() {
    $('.fencing-modal-area ~ .fencing-modal-area .fencing-modal-close').remove();
}

//----------------------------------------------------------------------------------

/**
 * Add Notes or Info if value exists in array
 */
function addNotesOrInfo(el, v) {
    var details = v.info,
        notes = v.notes;

    if (details || notes) {
        if (details) {
            const Item = ({ title, description }) => `<div class="fc-selection-details">
                <label>${title}</label>
                <p>${description}</p>
            </div>`;
            el.append(details.map(Item).join(''));
        }
        if (notes) {
            var hasNoteContent =
                !!notes.image ||
                !!(notes.title && String(notes.title).trim()) ||
                !!(notes.description && String(notes.description).trim());
            if (!hasNoteContent) {
                return;
            }
            notes_html = `<div class="row align-items-center">`;
            if (notes.image) {
                notes_html += `<div class="col-sm-3 note-img"><img src="${notes.image}" class="border rounded p-2 mb-3"></div>`;
            }

            notes_html += `<div class="col-sm">
                <div class="fc-alert-gray field-note">`;
            
            if( notes.title ) {
                notes_html += `<label class="mb-2 fw-bold">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                        ${notes.title}
                    </label>`;                
            }

             notes_html += `<div class="fc-text-gray fc-modal-note-body">${notes.description}</div>
                </div>
            </div>`;

            notes_html += `</div>`;
            el.append(notes_html);
        }
    }
}

//----------------------------------------------------------------------------------

function loadClearForm() {
    $('.has-clear .form-control').each(function() {
        var _this = $(this);
        if (_this.val()) {
            var clear = `<i class="fa-solid fa-circle-xmark form-control-clear"></i>`;
            _this.siblings('.form-control-clear').remove();
            if (_this.val()) _this.after(clear);
        }
    });
}

/**
 * Glass-solver auto-fit state. `pending` dedupes the two render sites that both report the
 * same failed calc in one pass; `setTo`/`attempts` stop a loop if a solver-suggested length
 * ever fails on the retry (it should not - candidates are solver-verified).
 */
var fcGlassOalAutoFit = { pending: false, setTo: null, attempts: 0 };

/**
 * Render the calc solver message ('.err-message', Step 3).
 *
 * When the glass solver fails but reports a verified buildable length (selected_values
 * .closest_lengths), the planner no longer shows the red error: it snaps Overall Length to
 * the nearest working value, re-runs the normal Calculate flow, and announces the change in
 * the top-right toast. The red error remains the fallback for failures with no known fix.
 * Calculation logic is untouched - this is the same correction the customer used to type in
 * by hand.
 */
function fcApplyCalcSolutionMessage(calc) {
    var msg = (calc && calc.selected_values && calc.selected_values.message) || '';

    if (!msg) {
        $('.err-message').html('');
        fcGlassOalAutoFit.setTo = null;
        fcGlassOalAutoFit.attempts = 0;
        return;
    }

    // An auto-fit is already scheduled for this pass - keep the error hidden, the retry decides.
    if (fcGlassOalAutoFit.pending) {
        $('.err-message').html('');
        return;
    }

    var $box = $('.measurement-box-number').first();
    var entered = parseInt(String($box.val() || '').replace(/,/g, ''), 10);

    // Manual edit since our last fix - the guard belongs to the old value.
    if (fcGlassOalAutoFit.setTo !== null && entered !== fcGlassOalAutoFit.setTo) {
        fcGlassOalAutoFit.setTo = null;
        fcGlassOalAutoFit.attempts = 0;
    }

    var target = fcPickClosestBuildableLength(calc.selected_values.closest_lengths, entered, $box);

    if (!target || fcGlassOalAutoFit.attempts >= 1) {
        $('.err-message').html(msg);
        return;
    }

    fcGlassOalAutoFit.pending = true;
    fcGlassOalAutoFit.setTo = target;
    fcGlassOalAutoFit.attempts += 1;
    $('.err-message').html('');

    // Deferred so the failing calculate pass finishes rendering before the corrected one starts.
    setTimeout(function() {
        fcGlassOalAutoFit.pending = false;

        $('.measurement-box-number').val(target).attr('data-last', String(target));

        if (typeof popupToast === 'function') {
            popupToast(
                'Overall Length adjusted',
                'Glass panels can\'t be cut to fit <b>' + fcFormatMm(entered) + ' mm</b> exactly, so ' +
                    'Overall Length was set to <b>' + fcFormatMm(target) + ' mm</b> — the closest ' +
                    'length that works.',
                'GP-FIT'
            );
        }

        if (typeof btnCalculate === 'function') {
            btnCalculate();
        } else {
            $('.btn-fc-calculate').first().trigger('click');
        }
    }, 0);
}

/**
 * Nearest of the solver's shortenTo/extendTo to what the customer entered, respecting the
 * input's data-min/data-max. Tie prefers shortening - never silently plans past the space
 * the customer measured.
 */
function fcPickClosestBuildableLength(closest, entered, $box) {
    if (!closest || !Number.isFinite(entered)) {
        return 0;
    }

    var min = parseInt($box.attr('data-min') || '', 10);
    var max = parseInt($box.attr('data-max') || '', 10);

    var candidates = [closest.shortenTo, closest.extendTo].filter(function(mm) {
        if (!Number.isFinite(mm) || mm <= 0 || mm === entered) {
            return false;
        }
        if (Number.isFinite(min) && mm < min) {
            return false;
        }
        if (Number.isFinite(max) && mm > max) {
            return false;
        }
        return true;
    });

    if (!candidates.length) {
        return 0;
    }

    candidates.sort(function(a, b) {
        var d = Math.abs(entered - a) - Math.abs(entered - b);
        return d !== 0 ? d : a - b; // tie: smaller (shorten) first
    });

    return candidates[0];
}

/** 1234567 -> "1,234,567" for toast copy. */
function fcFormatMm(mm) {
    return String(mm).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

//----------------------------------------------------------------------------------

function updateOverAllLength(data) {
    var _mbn = $('.measurement-box-number'),
        mbn = parseInt(_mbn.val()),
        lastMbn = parseInt(_mbn.attr('data-last')),
        lastMbn = HELPER.isNaNtoZero(lastMbn) == 0 ? mbn : lastMbn;

    /** Step 2 Gate ONLY uses this sentinel in the overall field; do not treat it as "user length > gate". */
    var mbnRawDisplay = parseInt(String(_mbn.val() || '').replace(/,/g, ''), 10);
    var fcGateOnlyPlaceholderMm = 9999;

    var fd = getSelectedFenceData();

    var slug = fd.slug,
        tab = fd.tab,
        custom_fence = fd.info,
        info = fd.data;

    // Slat Fence: overall length is entered from a reference line, so calculations
    // use an effective overall (entered + offset), but we keep showing entered.
    var width_dimension_offset =
        typeof SlatFence !== 'undefined' ? SlatFence.getWidthDimensionOffset(fd) : 0;
    if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(slug)) {
        mbn = parseInt(mbn, 10) + width_dimension_offset;
        lastMbn = parseInt(lastMbn, 10) + width_dimension_offset;
    }

    var calc = calculate_fences();


    var hasGate = data?.gate ? data.gate : $('.fencing-panel-gate').length,
        hasRaked = data?.raked ? data.raked : $('.raked-panel-container').length,
        panel_item = $('.panel-item:not(.fencing-panel-gate,.fencing-raked-panel)').length;

    var raked = gate = overall = adjustGap = left_side_width_post = right_side_width_post = 0;

    var minusPosts = FENCE.minus_posts(fd.info),
        post_panel = FENCE.get(slug, 'post');

    var left_side_width = HELPER.sideOptionValue('left', custom_fence, info),
        right_side_width = HELPER.sideOptionValue('right', custom_fence, info);

    if( left_side_width >= 0 || right_side_width >= 0) {
       post = FENCE.get(slug, 'post');
       left_side_width = (left_side_width >= 0 ? post-left_side_width : 0);
       right_side_width = (right_side_width >= 0 ? post-right_side_width : 0);

       left_side_width_post = (left_side_width >= 0 ? post-left_side_width : post_panel);
       right_side_width_post = (right_side_width >= 0 ? post-right_side_width : post_panel);

       adjustGap = left_side_width + right_side_width;        
    }

    // With raked and gate
    if( hasRaked == 1 ) {
        raked = hasRaked * FENCE.get('item', 'raked');       
    } else {
        raked = (hasRaked * FENCE.get('item', 'raked_post')) + post_panel;                  
    }

    if( hasGate ) {
        gate = FENCE.get(slug, 'gate');
    }

    // Is custom gate
    var gate_data = fd.info.filter(function(item) {
        return item.control_key == 'gate';
    });

    isCustomGate = gate_data[0]?.settings?.fields?.find(obj => obj['key'] === "use_std" && obj['val'] === false );

    gateOnly = gate_data[0]?.settings?.gateOnly;


    if (
        FENCE.isGateMinOalStyle(slug) ||
        gateOnly ||
        (panel_item == 0 && !hasRaked) ||
        (hasGate && !hasRaked)
    ) {
        fence_gate_posts_gaps = parseInt(FENCE.get(slug, 'gate_posts_gaps'), 10);
    } else {
        fence_gate_posts_gaps = parseInt(FENCE.get(slug, 'gate_post_gaps'), 10);
    }


    var slatGateOpeningMm = 0;
    if (typeof SlatFence !== 'undefined' && SlatFence.isMainSlatSlug(slug) && gate_data.length) {
        slatGateOpeningMm = SlatFence.getGateOpeningWidthMm(slug, gate_data, calc);
    }

    if( isCustomGate ) {
        gate =
            slatGateOpeningMm > 0
                ? slatGateOpeningMm + fence_gate_posts_gaps
                : parseInt(gate_data[0]?.settings.size, 10) + fence_gate_posts_gaps;
    }

    gate_posts_gaps =
        slatGateOpeningMm > 0
            ? slatGateOpeningMm + fence_gate_posts_gaps
            : parseInt(gate_data[0]?.settings.size, 10) + fence_gate_posts_gaps;


    // is glass fence
    
	if( info.panel_group == 'a' ) {
        var gate_hinge_type = gate_data[0]?.settings?.fields.find(function(item) {
            return item.key == 'gate_hinge_type';
        });

        var gate_hinge_types = info.settings.gate.fields.find(function(item) {
            return item.slug == 'gate_hinge_type';
        });
        var ght = gate_hinge_types.options.find(function(item) {
            return item.slug == gate_hinge_type?.val;
        });

        var gate = parseInt(gate_data[0]?.settings?.size),
       		gate_posts_gaps = gate + ght?.gap?.hinge + ght?.gap?.latch,
       		adjustGap = 0;
	}



        
    var stdGateMinOverallMm = null;
    var customGateMinOverallMm = null;
    if (FENCE.isGateMinOalStyle(slug) && gate_data.length) {
        if (!isCustomGate) {
            stdGateMinOverallMm = FENCE.getStdGateMinOverallPhysicalMm(fd);
        } else {
            var customLeaf = FENCE.resolveCustomGateLeafWidthMm(fd);
            if (
                customLeaf &&
                typeof SlatFence !== 'undefined' &&
                SlatFence.isMainSlatSlug(slug) &&
                typeof SlatFence.computeDisplayOverallFromGateLeafMm === 'function'
            ) {
                customGateMinOverallMm = SlatFence.computeDisplayOverallFromGateLeafMm(fd, customLeaf, {
                    isDouble: FENCE.isDoubleGate(gate_data)
                });
            } else if (customLeaf) {
                customGateMinOverallMm = FENCE.getCustomGateMinOverallPhysicalMm(
                    fd,
                    customLeaf,
                    FENCE.isDoubleGate(gate_data)
                );
            }
        }
    }

    var slatStdGoLock =
        typeof SlatFence !== 'undefined' &&
        SlatFence.isMainSlatSlug(slug) &&
        gateOnly &&
        !isCustomGate &&
        SlatFence.shouldLockStep2OverallForStdGateOnly(fd);

    if (slatStdGoLock) {
        overall =
            stdGateMinOverallMm != null
                ? stdGateMinOverallMm
                : gate_posts_gaps - minusPosts - adjustGap;
    }

    // GATE ONLY + STD | ON POST CHANGED
    if(gateOnly && !isCustomGate ||
        gateOnly && !isCustomGate && data?.removePost) {

        // Compare to full gate-only min overall (posts/spacing), not gate opening alone — otherwise
        // after the 9999 placeholder is replaced with computed min OAL (e.g. Glass Pool), the next
        // calculate pass sees mbn > gateOpening and wrongly clears Gate ONLY.
        var gateOnlyMinOverallStd =
            stdGateMinOverallMm != null
                ? stdGateMinOverallMm
                : gate_posts_gaps - minusPosts - adjustGap;
        var shouldClearGateOnlyOal =
            !slatStdGoLock &&
            !data?.removePost &&
            !Number.isNaN(mbnRawDisplay) &&
            mbnRawDisplay !== fcGateOnlyPlaceholderMm &&
            Number.isFinite(gateOnlyMinOverallStd) &&
            mbn > gateOnlyMinOverallStd &&
            mbn > lastMbn;

        if (shouldClearGateOnlyOal) {
            updateGateOnly(false);
            if (typeof checkGateOnly === 'function') {
                checkGateOnly();
            }
        } else if (mbn <= lastMbn || data?.removePost) {
            var overall =
                stdGateMinOverallMm != null
                    ? stdGateMinOverallMm
                    : gate_posts_gaps - minusPosts - adjustGap;

            var msg = FENCE.settings.message.min_gate_only
                .replace(/{{overall}}/gi, overall);

            if(overall != mbn && !isNaN(overall))
                popupToast("Important", msg, 'STD');
        }
    }      

    // GATE ONLY + CUSTOM GATE | ON POST CHANGED
    if(gateOnly && isCustomGate ||
        gateOnly && isCustomGate && data?.removePost) {

        var gateOnlyMinOverallCg =
            customGateMinOverallMm != null
                ? customGateMinOverallMm
                : gate_posts_gaps - minusPosts;
        var slatGoCustom =
            typeof SlatFence !== 'undefined' &&
            SlatFence.isMainSlatSlug(slug) &&
            gateOnly &&
            isCustomGate;

        if (slatGoCustom) {
            try {
                updateGateOnly(true);
            } catch (eGoKeepCg) {}
            if (typeof checkGateOnly === 'function') {
                checkGateOnly();
            }
        } else {
            var shouldClearGateOnlyOalCg =
                !data?.removePost &&
                !Number.isNaN(mbnRawDisplay) &&
                mbnRawDisplay !== fcGateOnlyPlaceholderMm &&
                Number.isFinite(gateOnlyMinOverallCg) &&
                mbn > gateOnlyMinOverallCg &&
                mbn > lastMbn;

            if (shouldClearGateOnlyOalCg) {
                updateGateOnly(false);
                if (typeof checkGateOnly === 'function') {
                    checkGateOnly();
                }
            } else if (mbn <= lastMbn || data?.removePost) {
                var overall =
                    customGateMinOverallMm != null
                        ? customGateMinOverallMm
                        : gate_posts_gaps - minusPosts;

                var msg = FENCE.settings.message.min_gate_only
                    .replace(/{{overall}}/gi, overall);

                if(overall != mbn && !isNaN(overall))
                    popupToast("Important", msg, 'CG');
            }
        }

    } 



    // STD GATE + PANEL ITEM | ON POST CHANGED
    var stdGateMinCompare =
        stdGateMinOverallMm != null
            ? stdGateMinOverallMm
            : gate_posts_gaps - minusPosts;
    if (
        !isCustomGate &&
        hasGate &&
        ((panel_item > 0 && mbn < stdGateMinCompare) ||
            (panel_item == 0 && mbn < stdGateMinCompare) ||
            (panel_item == 0 && data?.removePost))
    ) {

        var overall =
            stdGateMinOverallMm != null
                ? stdGateMinOverallMm
                : gate_posts_gaps - minusPosts - adjustGap;

        var msg = FENCE.settings.message.min_gate_custom
            .replace(/{{overall}}/gi, overall);
            
        if(overall != mbn && !isNaN(overall))
            popupToast("Important", msg, 'STD+P');

    } 

    // CUSTOM GATE + PANEL ITEM | ON POST CHANGED
    var customGateMinCompare =
        customGateMinOverallMm != null
            ? customGateMinOverallMm
            : gate_posts_gaps - minusPosts;
    if (
        isCustomGate &&
        hasGate &&
        ((panel_item > 0 && mbn < customGateMinCompare) ||
            (panel_item == 0 && mbn < customGateMinCompare) ||
            (panel_item == 0 && data?.removePost))
    ) {

        var overall =
            customGateMinOverallMm != null
                ? customGateMinOverallMm
                : gate_posts_gaps - minusPosts;

        var msg = FENCE.settings.message.min_gate_custom
            .replace(/{{overall}}/gi, overall);

        if(overall != mbn && !isNaN(overall))
            popupToast("Important", msg, 'CG+P');

    } 


    // RAKED + PANEL ITEM
    if(hasRaked && panel_item > 0 && mbn < (raked - minusPosts) || 
        hasRaked && panel_item == 0 && mbn < (raked - minusPosts) || 
        hasRaked && panel_item == 0 && data?.removePost) {
        
        var overall = raked - minusPosts + left_side_width_post + right_side_width_post;

        var msg = FENCE.settings.message.min_raked
            .replace(/{{overall}}/gi, overall)
            .replace(/{{hasRaked}}/gi, hasRaked);

        if(overall != mbn && !isNaN(overall)) 
            popupToast(FENCE.settings.message.oal_changed, msg, 'SU');

    } 

    // RAKED + GATE
    if(hasGate && hasRaked && panel_item > 0 && mbn < (raked + gate - minusPosts) ||
        hasGate && hasRaked && panel_item == 0 && mbn < (raked + gate - minusPosts) || 
        data?.removePost && hasRaked && gateOnly ) {
        
        var overall = (raked + gate) - minusPosts;

        var msg = FENCE.settings.message.min_gate_raked
            .replace(/{{overall}}/gi, overall)
            .replace(/{{hasRaked}}/gi, hasRaked);

        if(overall != mbn && !isNaN(overall)) 
            popupToast(FENCE.settings.message.oal_changed, msg, 'R+G');

    } 

/* 
    var gate_hinge_panel_width = gate_data[0]?.settings?.fields.find(function(item) {
        return item.key == 'gate_hinge_panel_width';
    });

    var gate_panel_width = gate_data[0]?.settings?.fields.find(function(item) {
        return item.key == 'gate_width';
    });

    gate_hinge_panel = parseInt(gate_hinge_panel_width?.val) + parseInt(gate_panel_width?.val);


    var edit_spacing_data = fd.info.filter(function(item) {
        return item.control_key === 'edit_spacing';
    });

    var gate_swing = gate_data[0]?.settings?.fields.find(function(item) {
        return item.key == 'gate_hinge_position';
    });


   var glass_overall = gate_hinge_panel;

    console

    var gate_placement = gate_data[0]?.settings?.placement;


    if(!gateOnly && mbn < glass_overall && gate_swing.val.includes('left') && gate_placement == 0 || 
       !gateOnly && mbn < glass_overall && gate_swing.val.includes('right') && gate_placement == -1 ) {

        var overall = glass_overall;

        var msg = FENCE.settings.message.min_gate_hinge
            .replace(/{{overall}}/gi, overall);

        if(overall != mbn && !isNaN(overall)) 
            popupToast(FENCE.settings.message.oal_changed, msg, 'G+H');

    }
*/
	
	if( !gateOnly ) {
		fcApplyCalcSolutionMessage(calc);
	} else {
		$('.err-message').html('');
	}

    var slatGoCustomSkipOalRewrite =
        typeof SlatFence !== 'undefined' &&
        SlatFence.isMainSlatSlug(slug) &&
        gateOnly &&
        isCustomGate;

    if (overall && !isNaN(overall) && overall != mbn && !slatGoCustomSkipOalRewrite) {
       if (typeof SlatFence !== 'undefined' && SlatFence.isSlatLike(slug)) {
            var dispOv = overall;
            if (!hasGate) {
                dispOv = SlatFence.getDisplayOverallLength(slug, overall, width_dimension_offset);
            }
            _mbn.val(dispOv);
            _mbn.attr('data-last', String(dispOv));
       } else if (FENCE.isGateMinOalStyle(slug) && hasGate) {
            _mbn.val(overall);
            _mbn.attr('data-last', String(overall));
       } else {
            _mbn.val(overall);
            _mbn.attr('data-last', String(overall));
       }
    }

    if (slatStdGoLock) {
        try {
            SlatFence.syncStep2GateOnlyOverallField(fd);
        } catch (eLockGo) {}
    }

    if (data?.removePost && FENCE.isGateMinOalStyle(slug) && hasGate) {
        try {
            FENCE.syncGateOverallOnPostChange(fd);
        } catch (eGatePostOal) {}
    }

}

//----------------------------------------------------------------------------------

/** Sync Step 2 Overall Length to gate minimum (STD or custom) for planner gate styles. */
function fcSyncGateMinOverallLength(fd, opts) {
    opts = opts || {};
    fd =
        fd ||
        (typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null);
    if (!fd || !FENCE.isGateMinOalStyle(fd.slug)) {
        return false;
    }
    if (FENCE.isCustomGateFd(fd)) {
        return FENCE.syncOverallFromCustomGateWidth(fd, opts);
    }
    if (FENCE.isStdGateFd(fd)) {
        return FENCE.syncOverallFromStdGateWidth(fd, opts);
    }
    return false;
}

//----------------------------------------------------------------------------------

function checkGateOnly() {
    var fd = getSelectedFenceData(),
        slug = fd.slug,
        tab = fd.tab;

    var gate_data = fd.info.filter(function(item) {
        return item.control_key == 'gate';
    });

    var segGo = !!(gate_data[0]?.settings?.gateOnly);
    var tabGo = false;
    if (fd.tabInfo && fd.tabInfo[0]) {
        tabGo = !!fcGetStep2GateOnlyForStyle(tab, slug, fd.tabInfo[0]);
        if (
            !tabGo &&
            fd.tabInfo[0].gateOnly &&
            !fcStyleHasExplicitGateOnlyFlag(fd.tabInfo[0], slug)
        ) {
            tabGo = true;
        }
    }
    if (tabGo && !segGo && gate_data.length) {
        try {
            var key = 'custom_fence-' + tab + '-' + slug;
            var cf = JSON.parse(localStorage.getItem(key));
            if (cf) {
                for (var gi = 0; gi < cf.length; gi++) {
                    if (cf[gi].control_key === 'gate') {
                        cf[gi].settings = cf[gi].settings || {};
                        cf[gi].settings.gateOnly = true;
                    }
                }
                localStorage.setItem(key, JSON.stringify(cf));
            }
        } catch (eSync) {}
    }

    var value = !!(segGo || tabGo);

    $('[name="gate_only"]').prop('checked', value);

    $('[name="gate_only"]').each(function() {
        var wrap = $(this).closest('.fc-select-2');
        if (value) {
            wrap.addClass('fc-selected');
        } else {
            wrap.removeClass('fc-selected');
        }
    });

    $('[name="gate_only_step2"]').prop('checked', value);
    $('.select-gate_only_step2').each(function() {
        var wrap = $(this);
        if (value) {
            wrap.addClass('fc-selected');
        } else {
            wrap.removeClass('fc-selected');
        }
    });

    fcSyncPanelControlsGateOnlyDisabled(value);

    if (typeof SlatFence !== 'undefined' && fd && SlatFence.isMainSlatSlug(slug)) {
        try {
            SlatFence.syncSlatGateAddAndOptionsEnabled(fd);
        } catch (eSyncGateBtn) {}
        try {
            SlatFence.syncStep2GateOnlyHeightMode(fd);
        } catch (eSyncH) {}
    }

    // Always sync Overall Length lock: unlock when leaving Slat Gate ONLY / non-slat styles.
    if (typeof SlatFence !== 'undefined' && typeof SlatFence.syncStep2GateOnlyOverallField === 'function') {
        try {
            SlatFence.syncStep2GateOnlyOverallField(fd);
        } catch (eSyncMbn) {}
    } else if (!value) {
        fcUnlockStep2OverallLengthField();
    }
}

/**
 * Gate ONLY: gate + left/right side modals stay enabled; other Step 3 panel controls stay disabled.
 * @param {boolean} [forcedGateOnly] If set, use this instead of reading segment/tab (avoids stale fd right after storage write).
 */
function fcSyncPanelControlsGateOnlyDisabled(forcedGateOnly) {
    var gateOnlyAllowedKeys = {
        gate: true,
        post_options: true,
        left_side: true,
        right_side: true
    };
    var $controls = $(typeof FENCES !== 'undefined' && FENCES.el && FENCES.el.fencingPanelControls
        ? FENCES.el.fencingPanelControls
        : '.fencing-panel-controls');
    if (!$controls.length) {
        return;
    }

    var gateOnly;
    if (typeof forcedGateOnly === 'boolean') {
        gateOnly = forcedGateOnly;
    } else {
        var fd = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
        if (!fd || !fd.info) {
            return;
        }
        gateOnly = fcIsPlannerGateOnlyActive(fd);
    }

    $controls.find('.fencing-btn-modal').each(function() {
        var $btn = $(this);
        var key = $btn.attr('data-key');
        if (gateOnly && !gateOnlyAllowedKeys[key]) {
            $btn
                .prop('disabled', true)
                .attr('aria-disabled', 'true')
                .addClass('fc-panel-control--gate-only-disabled');
        } else {
            $btn
                .prop('disabled', false)
                .removeAttr('aria-disabled')
                .removeClass('fc-panel-control--gate-only-disabled');
        }
    });

    if (typeof fcEnsurePlannerSummaryButton === 'function') {
        fcEnsurePlannerSummaryButton();
    }
}


function fcStep2GateOnlySnapKey(tab, slugNorm) {
    return 'fc-step2-go-snap-' + tab + '-' + slugNorm;
}

/**
 * When Gate ONLY is toggled off (or on), update any Step 1 style-switch snapshot for this tab+slug
 * so returning to this fence style does not re-apply Gate ONLY after the user unchecked it.
 */
function fcPatchStep2GateOnlySnapshotGateOnlyFlag(tab, slugNorm, gateOnlyVal) {
    if (tab === undefined || tab === null || slugNorm === undefined || slugNorm === null || slugNorm === '') {
        return;
    }
    var raw = localStorage.getItem(fcStep2GateOnlySnapKey(tab, slugNorm));
    if (!raw) {
        return;
    }
    try {
        var o = JSON.parse(raw);
        if (!o || o.v !== 1) {
            return;
        }
        o.gateOnly = !!gateOnlyVal;
        localStorage.setItem(fcStep2GateOnlySnapKey(tab, slugNorm), JSON.stringify(o));
    } catch (e) {}
}

/**
 * Persist Step 2 + tab row slice while Gate ONLY is on; used when switching fence style on Step 1.
 */
function fcSaveStep2GateOnlySnapshot(tab, slugNorm, prevFd) {
    if (tab === undefined || tab === null || !slugNorm || !prevFd) {
        return;
    }
    var $box = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.measurementBoxNumber : '.measurement-box-number');
    var changeFields =
        typeof fcCollectStep2FieldsFromDom === 'function'
            ? fcCollectStep2FieldsFromDom()
            : $('[data-section="2"] [data-action="change"] .form-control').serializeArray();
    try {
        changeFields =
            typeof SlatFence !== 'undefined' &&
            typeof SlatFence.normalizeStep2FieldsBeforeSave === 'function'
                ? SlatFence.normalizeStep2FieldsBeforeSave({
                      slug: slugNorm,
                      prevFields: prevFd.tabInfo[0]?.fieldsByStyle?.[slugNorm] || prevFd.tabInfo[0]?.fields || [],
                      nextFields: changeFields,
                      maxHeightEl: document.querySelector('[name="max_fence_height"]')
                  })
                : changeFields;
    } catch (eNorm) {}

    var t0 = prevFd.tabInfo && prevFd.tabInfo[0];
    var snapMbn = $box.val() != null ? String($box.val()) : '';
    if (
        typeof SlatFence !== 'undefined' &&
        SlatFence.isMainSlatSlug(slugNorm) &&
        SlatFence.isGateOnlyPlaceholderMm(snapMbn)
    ) {
        var fdSnap = {
            slug: slugNorm,
            tab: tab,
            info: readCustomFenceSegment(tab, slugNorm),
            data: typeof fc_data !== 'undefined' ? fc_data[slugNorm] : null,
            tabInfo: t0 ? [t0] : []
        };
        if (SlatFence.shouldLockStep2OverallForStdGateOnly(fdSnap)) {
            var dispSnap = SlatFence.computeSlatGateOnlyStdOverallDisplayMm(fdSnap);
            if (Number.isFinite(dispSnap) && dispSnap > 0) {
                snapMbn = String(dispSnap);
            }
        }
    }
    var snap = {
        v: 1,
        tab: tab,
        slugNorm: slugNorm,
        mbn: snapMbn,
        dataLast: $box.attr('data-last') || '',
        dataPrevGateOnlyMbn: $box.attr('data-prev-gate-only-mbn') || '',
        gateOnly: true,
        calculateValue: t0 && t0.calculateValue !== undefined ? t0.calculateValue : null,
        isCalculate: t0 && t0.isCalculate !== undefined ? t0.isCalculate : null,
        fields: changeFields,
        fieldsByStyle: t0 && t0.fieldsByStyle ? JSON.parse(JSON.stringify(t0.fieldsByStyle)) : {}
    };
    try {
        localStorage.setItem(fcStep2GateOnlySnapKey(tab, slugNorm), JSON.stringify(snap));
    } catch (eLS) {}
}

/**
 * Restore Step 2 + tab row from snapshot when returning to a fence style after Gate ONLY style switch.
 */
function fcApplyStep2GateOnlySnapshot(snap) {
    if (!snap || snap.v !== 1 || snap.tab === undefined) {
        return;
    }
    var $box = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.measurementBoxNumber : '.measurement-box-number');
    var snapMbnShow = snap.mbn != null ? String(snap.mbn) : '';
    if (
        typeof SlatFence !== 'undefined' &&
        SlatFence.isMainSlatSlug(snap.slugNorm) &&
        SlatFence.isGateOnlyPlaceholderMm(snapMbnShow)
    ) {
        try {
            var rawTab = localStorage.getItem('custom_fence-' + snap.tab);
            var tabRowSnap = rawTab ? JSON.parse(rawTab) : [];
            var resolvedSnap = fcResolveSlatGateOnlyStdOverallForTabRow(
                tabRowSnap[0],
                snap.slugNorm,
                snap.tab
            );
            if (Number.isFinite(resolvedSnap) && resolvedSnap > 0) {
                snapMbnShow = String(resolvedSnap);
            }
        } catch (eResSnap) {}
    }
    $box.val(snapMbnShow);
    if (
        snap.dataLast &&
        !(typeof SlatFence !== 'undefined' && SlatFence.isGateOnlyPlaceholderMm(snap.dataLast))
    ) {
        $box.attr('data-last', snap.dataLast);
    } else if (snapMbnShow) {
        $box.attr('data-last', snapMbnShow);
    } else {
        $box.removeAttr('data-last');
    }
    if (snap.dataPrevGateOnlyMbn) {
        $box.attr('data-prev-gate-only-mbn', snap.dataPrevGateOnlyMbn);
    } else {
        $box.removeAttr('data-prev-gate-only-mbn');
    }
    $box.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');

    $.each(snap.fields || [], function(_, f) {
        if (!f || !f.name) {
            return;
        }
        var els = document.getElementsByName(f.name);
        if (!els || !els.length) {
            return;
        }
        var $els = $(els);
        if (($els.first().attr('type') || '').toLowerCase() === 'radio') {
            $els.filter(function() {
                return String(this.value) === String(f.value);
            }).prop('checked', true);
        } else {
            $els.val(f.value);
        }
    });

    try {
        var raw = localStorage.getItem('custom_fence-' + snap.tab);
        var tabInfo = raw ? JSON.parse(raw) : [];
        if (tabInfo[0]) {
            tabInfo[0].calculateValue =
                snap.calculateValue !== undefined && snap.calculateValue !== null
                    ? snap.calculateValue
                    : parseInt(String(snap.mbn || '').replace(/,/g, ''), 10) || tabInfo[0].calculateValue;
            tabInfo[0].gateOnly = !!snap.gateOnly;
            tabInfo[0].fields = snap.fields || tabInfo[0].fields;
            if (snap.fieldsByStyle && typeof snap.fieldsByStyle === 'object') {
                tabInfo[0].fieldsByStyle = snap.fieldsByStyle;
            }
            localStorage.setItem('custom_fence-' + snap.tab, JSON.stringify(tabInfo));
        }
    } catch (eTab) {}

    if (typeof updateGateOnly === 'function') {
        updateGateOnly(!!snap.gateOnly);
    }
    if (typeof checkGateOnly === 'function') {
        checkGateOnly();
    }
    if (typeof measurementBoxNumber === 'function') {
        try {
            measurementBoxNumber();
        } catch (eM) {}
    }
}

/**
 * After switching to another style while Gate ONLY was on: empty Step 2 and turn off Gate ONLY for the new style.
 */
function fcClearStep2AfterGateOnlyStyleSwitch() {
    var $box = $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.measurementBoxNumber : '.measurement-box-number');
    $box.val('');
    $box.removeAttr('data-last');
    $box.removeAttr('data-prev-gate-only-mbn');
    $box.closest('.fc-input-container').find('.fc-input-msg').removeClass('fcim-show').html('');
    if (typeof fcUnlockStep2OverallLengthField === 'function') {
        fcUnlockStep2OverallLengthField();
    } else {
        $box.prop('readonly', false).removeAttr('aria-disabled');
        $box.closest('.fc-input-container').removeClass('fc-measurement-locked-gate-only');
    }
    $('.select-gate_only_step2').removeClass('fc-selected');
    $('[name="gate_only_step2"]').prop('checked', false);
    if (typeof updateGateOnly === 'function') {
        updateGateOnly(false);
    }
    if (typeof fcHidePlannerStep3Results === 'function') {
        fcHidePlannerStep3Results();
    } else {
        try {
            $(typeof FENCES !== 'undefined' && FENCES.el ? FENCES.el.fencingPanelContainer : '.fencing-panel-container').html('');
        } catch (e) {}
        $('.js-fc-form-step[data-section="3"]').hide();
    }
    if (typeof checkGateOnly === 'function') {
        checkGateOnly();
    }
    try {
        var tab = $('.fencing-tab.fencing-tab-selected').index();
        var raw = localStorage.getItem('custom_fence-' + tab);
        var tabInfo = raw ? JSON.parse(raw) : [];
        if (tabInfo[0]) {
            tabInfo[0].gateOnly = false;
            var fdClear = typeof getSelectedFenceData === 'function' ? getSelectedFenceData() : null;
            var snClear = fdClear && fdClear.slug;
            tabInfo[0].gateOnlyByStyle = tabInfo[0].gateOnlyByStyle || {};
            if (snClear) {
                tabInfo[0].gateOnlyByStyle[snClear] = false;
                if (tabInfo[0].fieldsByStyle && typeof tabInfo[0].fieldsByStyle === 'object') {
                    if (tabInfo[0].fieldsByStyle[snClear]) {
                        delete tabInfo[0].fieldsByStyle[snClear];
                    }
                }
            }
            localStorage.setItem('custom_fence-' + tab, JSON.stringify(tabInfo));
        }
    } catch (e2) {}
    if (typeof measurementBoxNumber === 'function') {
        try {
            measurementBoxNumber();
        } catch (e3) {}
    }
}

//----------------------------------------------------------------------------------

function switchPanel(pa, pb) {

    var panel_a = $(pa),
        panel_b = $(pb);

    if( panel_a.length ) {
        // Create temporary marker
        const temp = $('<span id="temp-marker"></span>');
        panel_a.before(temp);
        panel_b.before(panel_a);
        temp.replaceWith(panel_b);

        setPanelItemsID();
    }
}

//----------------------------------------------------------------------------------

function setPanelItemsID(tab = 0) {
    $('#pp-'+tab+' .panel-item:not(.fencing-panel-gate, #pp-'+tab+' .fencing-raked-panel)').each(function(k, v) {
        $(this).attr('id', 'panel-item-'+k).attr('data-id', k);
    });
}

//----------------------------------------------------------------------------------

function checkGateWidthType() {
    var fd = getSelectedFenceData(),
        slug = fd.slug,
        tab = fd.tab,
        info = fd.info,
        data = fd.data;

    var gateWidth = data?.settings?.gate?.size.width,
        use_std = true;

    // Is custom gate
    var gate_data = info.filter(function(item) {
        return item.control_key == 'gate';
    });

    var useStdField = gate_data[0]?.settings?.fields?.find(function(obj) {
        return obj.key === 'use_std';
    });
    isCustomGate = !!(useStdField && (useStdField.val === false || useStdField.val === 'false'));

    if (isCustomGate) {
        gateWidth = gate_data[0]?.settings?.size;
        var widthField = gate_data[0]?.settings?.fields?.find(function(f) {
            return f.key === 'width';
        });
        if (
            (gateWidth === undefined || gateWidth === null || gateWidth === '') &&
            widthField &&
            widthField.val !== undefined &&
            widthField.val !== null &&
            String(widthField.val).trim() !== ''
        ) {
            gateWidth = widthField.val;
        }
        use_std = false;
    }

    if (gateWidth !== undefined && gateWidth !== null && gateWidth !== '') {
        gateWidth = parseInt(String(gateWidth).replace(/,/g, ''), 10);
        if (!Number.isFinite(gateWidth)) {
            gateWidth = data?.settings?.gate?.size.width;
        }
    }

    $('[name="use_std"]').prop('checked', use_std);

    $('.select-use_std').removeClass('fc-selected');
    if (use_std) {
        $('.select-use_std[data-val="std"]').addClass('fc-selected');
    } else {
        $('.select-use_std[data-val="custom"]').addClass('fc-selected');
    }

    $('[name="width"]').val(gateWidth);

    // STD Gate Width: Custom Gate Width is display-only. CUSTOM: editable.
    if (use_std) {
        if (typeof FENCE !== 'undefined' && typeof FENCE.call === 'function') {
            FENCE.call('disabledCustomGate');
        } else {
            $('[name="width"]').attr('readonly', 'readonly').addClass('disabled text-muted');
            $('.custom-gate .fc-gate-modal-custom-width-section .fencing-qty-btn').addClass('disabled');
        }
        if (gateWidth !== undefined && gateWidth !== null && gateWidth !== '') {
            $('[name="width"]').val(gateWidth);
        }
    } else {
        $('[name="width"]').removeAttr('readonly').removeClass('disabled text-muted');
        $('.custom-gate .fc-gate-modal-custom-width-section .fencing-qty-btn').removeClass('disabled');
        if (typeof SlatFence !== 'undefined' && typeof SlatFence.syncGateModalCalculateButtonState === 'function') {
            SlatFence.syncGateModalCalculateButtonState();
        } else {
            $('.custom-gate .fc-gate-modal-custom-width-section button')
                .removeAttr('disabled')
                .removeClass('btn-light disabled')
                .addClass('btn-dark');
        }
    }
}

//----------------------------------------------------------------------------------

function removeStepPanels(cf) {
    var fd = getSelectedFenceData(),
        slug = fd.slug,
        tab = fd.tab,
        key = `custom_fence-${tab}-${slug}`,
        cf = JSON.parse(localStorage.getItem(key));

    if( cf ) {
        for(let i = 0; i < cf.length; i++) {
            if($.inArray(cf[i].control_key, ['left_side', 'right_side', 'add_step_up_panels']) !== -1) {
                var left = cf[i].settings.filter(function(item) {
                    return $.inArray(item.key, ['left_raked', 'right_raked']) == -1;
                });
                cf[i].settings = left;
            }
        }            
        // Remove step up panel
        localStorage.setItem(key, JSON.stringify(cf));
    }
}

//----------------------------------------------------------------------------------

/**
 * Remove persisted gate segment so calc / load_fencing_items / cart do not keep rendering a gate.
 */
function fcRemoveGateSegmentFromStorage(tab, slug) {
    if (tab == null || tab === '' || !slug) {
        return;
    }
    try {
        var key = 'custom_fence-' + tab + '-' + slug;
        var cf = JSON.parse(localStorage.getItem(key) || 'null');
        if (!Array.isArray(cf)) {
            return;
        }
        var next = cf.filter(function(item) {
            return item && item.control_key !== 'gate';
        });
        if (next.length !== cf.length) {
            localStorage.setItem(key, JSON.stringify(next));
        }
    } catch (eRm) {}
}

//----------------------------------------------------------------------------------

/** Keep `settings.gateOnly` and modal `gate_only` field row in sync for set_field_value / reload. */
function fcSyncGateOnlyInSegmentStorage(cf, val) {
    if (!cf || !Array.isArray(cf)) {
        return;
    }
    var on = !!val;
    for (var i = 0; i < cf.length; i++) {
        if (cf[i].control_key !== 'gate' || !cf[i].settings) {
            continue;
        }
        cf[i].settings.gateOnly = on;
        var fields = cf[i].settings.fields;
        if (!Array.isArray(fields)) {
            fields = cf[i].settings.fields = [];
        }
        var found = false;
        for (var j = 0; j < fields.length; j++) {
            if (fields[j] && fields[j].key === 'gate_only') {
                fields[j].val = on;
                fields[j].tag = fields[j].tag || 'input';
                found = true;
                break;
            }
        }
        if (!found) {
            fields.push({ key: 'gate_only', tag: 'input', val: on });
        }
    }
}

function updateGateOnly(val) {
    var fd = getSelectedFenceData(),
        tab = fd.tab,
        slug = fd.slug,
        key = `custom_fence-${tab}-${slug}`,
        width = fd?.data?.settings?.gate?.size?.width,
        cf = JSON.parse(localStorage.getItem(key));

    if( cf ) {
        fcSyncGateOnlyInSegmentStorage(cf, val);
        localStorage.setItem(key, JSON.stringify(cf));
    }

    // Tab row (`custom_fence-{tab}`) is used on reload / tab switch / set_cutom_fence_data; keep it in sync
    // so Gate ONLY survives gate width changes and page refresh (segment alone was not enough).
    try {
        var tabRowKey = 'custom_fence-' + tab;
        var tabRow = JSON.parse(localStorage.getItem(tabRowKey) || 'null');
        if (Array.isArray(tabRow) && tabRow[0] && typeof tabRow[0] === 'object') {
            tabRow[0].gateOnly = !!val;
            tabRow[0].gateOnlyByStyle = tabRow[0].gateOnlyByStyle || {};
            tabRow[0].gateOnlyByStyle[slug] = !!val;
            localStorage.setItem(tabRowKey, JSON.stringify(tabRow));
        }
    } catch (eTab) {}

    try {
        if (typeof fcPatchStep2GateOnlySnapshotGateOnlyFlag === 'function') {
            fcPatchStep2GateOnlySnapshotGateOnlyFlag(tab, slug, !!val);
        }
    } catch (eSnap) {}

    try {
        if (typeof fcSyncPanelControlsGateOnlyDisabled === 'function') {
            fcSyncPanelControlsGateOnlyDisabled(!!val);
        }
    } catch (eSync) {}

    if (typeof checkGateOnly === 'function') {
        checkGateOnly();
    }
}

//----------------------------------------------------------------------------------

function updateGateSettings(skey, sval) {
    var fd = getSelectedFenceData(),
        tab = fd.tab,
        slug = fd.slug,
        key = `custom_fence-${tab}-${slug}`,
        width = fd?.data?.settings?.gate?.size?.width,
        cf = JSON.parse(localStorage.getItem(key));

    if( cf ) {
        for(let i = 0; i < cf.length; i++) {
            if($.inArray(cf[i].control_key, ['gate']) !== -1) {
                cf[i].settings[skey] = sval;
            }
        }    
        
        localStorage.setItem(key, JSON.stringify(cf));
    }
}

//----------------------------------------------------------------------------------

function updateSettingsValue(storage_key, data, control_key, key, value) {

    var settings_data = data.filter(function(item) {
        return item.control_key == control_key;
    });

    if( settings_data ) {
        settings = updateFieldSettings(settings_data[0]?.settings, key, value);

        if( settings) {
            settings_data[0]?.settings
            newData = updateControlSettings(data, control_key, settings_data[0] );
            localStorage.setItem(storage_key, JSON.stringify(newData));
        }
    }
}

//----------------------------------------------------------------------------------

function updateFieldSettings(data, key, value) {
    for(let i = 0; i < data?.length; i++) {
        if($.inArray(data[i].key, [key]) !== -1) {
            data[i].val = value;
        }
    }    
    return data;
}

//----------------------------------------------------------------------------------

function updateControlSettings(data, control_key, settings) {
    for(let i = 0; i < data.length; i++) {
        if($.inArray(data[i].control_key, [control_key]) !== -1) {
            data[i] = settings;
        }
    }    
    return data;
}

//----------------------------------------------------------------------------------

function setGateFieldSettings(fd, key, value) {

    var gate_data = fd.info.filter(function(item) {
        return item.control_key == 'gate';
    });

    if( gate_data.length ) {
        gate_data[0].settings.fields = updateFieldSettings(gate_data[0]?.settings?.fields, key, value);
        newData = updateControlSettings(fd.info, 'gate', gate_data[0]);

        localStorage.setItem('custom_fence-' + fd.tab + '-' + fd.slug, JSON.stringify(newData));        
    }
}

//----------------------------------------------------------------------------------

/*
    Extended custom functions
*/

//----------------------------------------------------------------------------------

$.fn.scrollTo = function(speed, offset) {
    offset = offset || 0;
    if (!this.length) {
        return this;
    }
    var pos = this.first().offset();
    if (!pos || !Number.isFinite(pos.top)) {
        return this;
    }
    $('html, body').animate(
        {
            scrollTop: pos.top - offset
        },
        speed === undefined ? 100 : speed
    );
    return this;
};

//----------------------------------------------------------------------------------

$.fn.swapWith = function(to) {
    return this.each(function() {
        var _this = $(this),
            copy_to = $(to).clone(true),
            copy_from = _this.clone(true);
        $(to).replaceWith(copy_from);
        _this.replaceWith(copy_to);
    });
}

//----------------------------------------------------------------------------------

$.fn.scrollCenter = function(elem, speed) {
    var $scroll = $(this);
    var scrollEl = $scroll[0];
    if (!scrollEl) {
        return this;
    }

    var $target = $scroll.find(elem).filter(':visible').first();
    if (!$target.length) {
        $target = $(elem).filter(':visible').first();
    }
    if (!$target.length) {
        return this;
    }

    var targetEl = $target[0];
    var targetRect = targetEl.getBoundingClientRect();
    var scrollRect = scrollEl.getBoundingClientRect();
    var delta =
        targetRect.left + targetRect.width / 2 - (scrollRect.left + scrollRect.width / 2);
    var newScrollLeft = scrollEl.scrollLeft + delta;
    var maxScroll = Math.max(0, scrollEl.scrollWidth - scrollEl.clientWidth);
    newScrollLeft = Math.max(0, Math.min(newScrollLeft, maxScroll));

    var animSpeed = speed === undefined ? 1000 : speed;
    if (!animSpeed || animSpeed <= 0) {
        scrollEl.scrollLeft = newScrollLeft;
    } else {
        $scroll.stop(true).animate({ scrollLeft: newScrollLeft }, animSpeed);
    }
    return this;
};

//----------------------------------------------------------------------------------

// Google map integration
let autocomplete;
let address1Field;

function initAutocompleteAddress() {
    autocomplete = document.querySelector("#address");
    // Create the autocomplete object, restricting the search predictions to
    // addresses in the US and Canada.
    autocomplete = new google.maps.places.Autocomplete(autocomplete, {
        componentRestrictions: {
            country: ["au"]
        },
        fields: ["address_components", "geometry"],
        types: ["address"],
    });
    // When the user selects an address from the drop-down, populate the
    // address fields in the form.
    autocomplete.addListener("place_changed", fillInAddress);
}

//----------------------------------------------------------------------------------

function fillInAddress() {
    // Get the place details from the autocomplete object.
    const place = autocomplete.getPlace();
    let address1 = [];
    for (const component of place.address_components) {
        // @ts-ignore remove once typings fixed
        const componentType = component.types[0];
        switch (componentType) {
            case "street_number": 
                address1.push(component.long_name);
                break;
            case "route":
                address1.push(component.long_name);
                break;
            case "postal_code":
                document.querySelector("#postcode").value = component.long_name;
                break;
            case "postal_code_suffix":
                postcode = component.long_name;
                break;
            case "locality":
                 address1.push(component.long_name);
                break;
            case "administrative_area_level_1":
                document.querySelector("#state").value = component.short_name;
                break;
            case "country":
                component.long_name;
                break;
        }
    }
    document.querySelector("#address").value = address1.join(', ');

    if (typeof fcSyncDownloadPlansFloatingLabels === 'function') {
        fcSyncDownloadPlansFloatingLabels();
    }
}

//----------------------------------------------------------------------------------

// $('#mobile').inputmask('9999 999 999');
