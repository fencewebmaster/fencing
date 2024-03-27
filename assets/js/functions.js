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

function clearFencingData() {
    // Add clear fence planner local storage here
    let keysToRemove = ["project-plans", "countdown-date", "custom_fence-", "cart_items-"];
    keysToRemove.forEach(k => removeItemStorageWith(k));
}

//----------------------------------------------------------------------------------

function removeItemStorageWith(startsWith) {
    Object.entries(localStorage).forEach(([key, value]) => {
        if (key.startsWith(startsWith)) {
            localStorage.removeItem(key);
        }
    });
}

//----------------------------------------------------------------------------------

function getCartItemStorage() {

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
}

//----------------------------------------------------------------------------------

function mergeSettings(data, settings, key, modal_key) {

    //Check first if a control_key already exists and get it
    const find_existing_data = data.find(obj => obj[key] === modal_key);

    if (typeof find_existing_data !== "undefined") {

        let merge_settings = [];

        find_existing_data.settings?.forEach(obj => {
            merge_settings.push(obj);
        });

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


        localStorage.setItem(key, JSON.stringify(updatedData));
    } else {
        // If the key doesn't exist, create a new object and save it to localStorage
        localStorage.setItem(key, JSON.stringify(newData));
    }
}

//----------------------------------------------------------------------------------

function getActiveFencing() {
    let sectionCount = localStorage.getItem('custom_fence-section'),
        fenceStyle = [];

    for (let i = 0; i < sectionCount; i++) {
        var cf = JSON.parse(localStorage.getItem('custom_fence-' + i));

        if (cf) {
            style = cf[0].style;
            fenceStyle.push(style);
        }
    }

    return fenceStyle.filter((v, p) => fenceStyle.indexOf(v) == p);
}

//----------------------------------------------------------------------------------

function savePlanner() {

    // var form = $('form')[0]; 
    var formData = new FormData();

    formData.set("action", 'save_planner');

    $.ajax({
        url: 'checkout.php',
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
                    $('.quote-id-card .qic-body').html(info.id);
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

            formData[name] = val;
        }

    });

    updateOrCreateObjectInLocalStorage(projectPlanKey, formData);
}


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

                selectedValues = selectedValues.map(item => item.trim());

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
                input.value = formData[key];
            }
        }
    }
}

/**
 * Remove deleted section entry in local storage
 */
function deleteLocalStorageEntry() {

    //Get selected tab
    let getActiveTab = $(FENCES.el.fencingTabSelected);

    //Get selected tab index
    let getActiveTabIndex = getActiveTab.index();


    //Find and delete all instance of it in local storage
    deleteAllEntriesBySubstring("custom_fence-" + getActiveTabIndex);
    localStorage.removeItem("cart_items-" + getActiveTabIndex + 1);

}

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

function getSelectedFenceData() {

    var slug = $('.fencing-style-item.fsi-selected').attr('data-slug'),
        itab = $('.fencing-tab.fencing-tab-selected').index(),
        info = localStorage.getItem('custom_fence-' + itab + '-' + slug),
        info = info ? JSON.parse(info) : [],
        data = fc_data[slug];

    var tabInfo = localStorage.getItem('custom_fence-' + itab),
        tabInfo = tabInfo ? JSON.parse(tabInfo) : [];

    var modalKey = $(FENCES.el.fencingContainer).attr('data-key'),
        mbn = $(FENCES.el.measurementBoxNumber).val();

    return {
        slug: slug,
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
 * Refresh local storage
 * Run this after deleting a section tab
 * This function will only update the index of storage entries  
 * that appear after the deleted entry based on the index position
 * `custom_fence-{idx}` and `custom_fence-{idx}-{styleIdx}`
 */
function refreshLocalStorage(activeSectionIndex, target) {

    //Only get storage entries related to custom fence
    const totalEntries = HELPER.countLocalStorageFenceKeys(target);

    //Iterate each entries
    for (let i = activeSectionIndex; i <= totalEntries; i++) {

        let newIndex = i - 1;

        //If -1, set value to 0
        if (newIndex == -1) {
            newIndex = 0;
        }

        //Retrieve to the old key
        const oldKey = `${target}-${i}`;

        //Prepare the new key string format
        const newKey = `${target}-${newIndex}`;

        // Check if the oldKey exists in localStorage and update it
        if (localStorage.getItem(oldKey)) {

            //Grab the old key value
            const value = JSON.parse(localStorage.getItem(oldKey));

            //Remove old key entry from local storage
            localStorage.removeItem(oldKey);

            //Update the tab value with new Index
            value[0].tab = newIndex;

            //Set the new key entry
            localStorage.setItem(newKey, JSON.stringify(value));

            //For Styles
            //Grab the old style key value
            const oldStyleKey = `${target}-${i}-${value[0].style}`;

            //Prepare new key string format
            const newStyleKey = `${target}-${newIndex}-${value[0].style}`;

            // Check if the old style Key exists in localStorage
            if (localStorage.getItem(oldStyleKey)) {

                //Get the value
                const value = JSON.parse(localStorage.getItem(oldStyleKey));

                //Remove the old style key
                localStorage.removeItem(oldStyleKey);

                //Set the new style key entry
                localStorage.setItem(newStyleKey, JSON.stringify(value));
            }
        }

    }

}

//----------------------------------------------------------------------------------

function submit_fence_planner(status = '') {

    // window.onbeforeunload = function() {}

    // Removed unwanted cart
    removeItemStorageWith('cart_items-');

    //Set some delay to make sure the local storage and the html markup are loaded
    var items = localStorage.getItem('custom_fence-section') ?? 1;
    for (let i = 0; i < items; i++) {
        FENCES.cartItems.init(i);
    }

    var set_fc_data = [];
    var project_plans = JSON.parse(localStorage.getItem('project-plans'));
    var cart_items = getCartItemStorage();

    var incompleteSection = 0;

    $(".fencing-tab").each(function() {

        var tid = $(this).index();

        form = JSON.parse(localStorage.getItem('custom_fence-' + tid));

        if (form != null) {

            settings = JSON.parse(localStorage.getItem('custom_fence-' + tid + '-' + form[0]?.style));

            form[0].style = form[0]?.style;
            form[0].tab = form[0]?.tab + 1;

            set_fc_data.push({
                'form': form,
                'settings': settings
            });

            if (!form[0]?.calculateValue) {
                incompleteSection += 1;
            }

        } else {
            incompleteSection += 1;
        }

    });

    if (incompleteSection > 0) {

        $('.ftm-measurement:empty').closest(FENCES.el.fencingTab).addClass('incomplete-section');

        $('.fc-loader-overlay').hide();
        $('.fc-section-step').hide();
        $('[data-tab="1"]').show();

        HELPER.tabContainerScroll();

        return false;
    }

    var form = $('form')[0];
    var formData = new FormData(form);

    formData.set("fences", JSON.stringify(set_fc_data));

    formData.set("cart_items", JSON.stringify(cart_items));

    formData.set("project_plans", JSON.stringify(project_plans));

    Object.entries(project_plans).forEach(([key, value]) => {
        if (typeof value === 'object') {
            value = JSON.stringify(value);
        }

        // remove brackets if key contains array format
        if (key.includes("[]")) {
            key = key.replace('[]', '');
        }

        formData.set(key, value);
    });

    $.ajax({
        url: 'submit.php',
        type: "POST",
        data: formData,
        headers: {},
        contentType: false,
        cache: false,
        processData: false,
        success: function(response) {
            try {

                var count = 0;

                if (status == 'new') {
                    setTimeout(function() {
                        $('.fc-loader ul li').each(function(i) {
                            var _this = $(this);
                            setTimeout(function() {
                                _this.addClass('fc-text-success');
                                count++;
                                if (count == 1) {
                                    /*
                                    window.onbeforeunload = function () {
                                        return;
                                    }
                                    */
                                    window.location = 'project-plan';
                                }
                            }, 1000 * i);
                        });
                    }, 1000);

                } else {
                    setTimeout(function() {
                        $('.fc-loader ul li').each(function(i) {
                            var _this = $(this);
                            setTimeout(function() {
                                _this.addClass('fc-text-success');
                                count++;

                                if (count == 1) {
                                    /*
                                    window.onbeforeunload = function () {
                                        return;
                                    }
                                    */
                                    window.location = 'project-plan?qid=' + planner_id;
                                }

                            }, 1000 * i);
                        });
                    }, 1000);
                }
            } catch (err) {

            }
        }
    });

}

//----------------------------------------------------------------------------------

function loadColorOptions() {

    const project = JSON.parse(localStorage.getItem('project-plans'));

    var colorOption = $('[data-load="color-options"]');

    var items = getActiveFencing();

    colorOption.html('');

    $('.fc-btn-create-plan').attr('disabled');

    $.each(items, function(k, v) {
        if (v) {
            var slug = fc_data[v].slug,
                title = fc_data[v].title,
                colors = fc_data[v].color;

            var tpl = $('script[data-type="color_options"]').text()
                .replace(/{{slug}}/gi, slug)
                .replace(/{{title}}/gi, title);

            colorOption.append(tpl);

            $.each(colors, function(k, v) {
                $('[data-load="color-options"] [data-slug="' + slug + '"] [data-slug="' + v + '"]').addClass('on');
            });

            $('.fc-select-color:not(.on)').remove();
        }
    });

    if (project?.color) {
        $.each(project.color, function(k, v) {
            $('.fc-color-options[data-slug="' + v.fence + '"] .fc-select-item[data-slug="' + v.color + '"]').addClass('fc-selected');
        });

        if ($('.fc-color-options .fc-selected').length == items.length) {
            $('.fc-btn-create-plan').removeAttr('disabled');
        }

    }

    //  setActiveColor();
}
loadColorOptions();

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

function extra_fields() {

    var fd = getSelectedFenceData();

    var i = fd.slug,
        tab = fd.tab,
        custom_fence = fd.info,
        info = fd.data
    tabInfo = fd.tabInfo;

    var modal_key = fd.modKey,
        mbn = fd.mbn;

    // START FORM FIELDS ON STEP 3
    $('[data-action="change"]').html('');

    $.each(info.form, function(k, v) {

        var tpl = $('script[data-type="' + v.type + '"]').text()
            .replace(/{{title}}/gi, v.title)
            .replace(/{{slug}}/gi, v.slug)
            .replace(/{{description}}/gi, v.description);


        $(v.target).append(tpl);

        // Select field
        $.each(v.option, function(i, item) {

            $('[name="' + v.slug + '"]').append($('<option>', {
                value: i,
                text: item
            }));
        });

        var selectValue = v.default;

        $('[name=' + v.slug + ']').val(selectValue);

    });

    $.each(tabInfo[0]?.fields, function(k, v) {
        if (v.value) {
            $('[name=' + v.name + ']').val(v.value);
        }
    });
    // END FORM FIELDS ON STEP 3

}


//----------------------------------------------------------------------------------

function update_color_options() {

    colorData = color_data = [];

    $(FENCES.el.fcColorOptions).each(function(k, v) {
        var color = $(this).find('.fc-selected').attr('data-slug');

        if (color) {
            var data = {
                fence: $(v).attr('data-slug'),
                color: color
            }

            color_data.push(data);
        }

    });

    var colorData = { color: color_data }

    updateOrCreateObjectInLocalStorage('project-plans', colorData);

}

//----------------------------------------------------------------------------------

// raked panel
function computeOverallraked(value, side, leftRakedBefore, rightRakedBefore) {

    var rakedCount = $('.' + side + '-panel .fencing-panel-item-size').length;

    rakedBefore = (side == 'left_raked') ? leftRakedBefore : rightRakedBefore;

    var fd = getSelectedFenceData();

    var tabInfo = fd.tabInfo,
        custom_fence = fd.info,
        data = fd.data;

    var mbn = parseInt($('.measurement-box-number').val()),
        raked = 1200 + 50;

    if (value != 'none' && rakedCount && rakedBefore == 0) {
        $('.measurement-box-number').val(mbn + raked);
    }

    if (value == 'none' && rakedCount == 0) {
        $('.measurement-box-number').val(mbn - raked);
    }

    $('.btn-fc-calculate').trigger('click');

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

    var details = v.info;
    var notes = v.notes;

    if (details || notes) {

        if (details) {
            const Item = ({ title, description }) => `<div class="fc-selection-details">
                    <label>${title}</label>
                    <p>${description}</p>
                </div>`;

            el.append(details.map(Item).join(''));
        }

        if (notes) {

            notes_html = `<div class="row align-items-center">`;

            if (notes.image) {
                notes_html += `<div class="col-sm-3 note-img"><img src="${notes.image}" class="border rounded p-2 mb-3"></div>`;
            }

            notes_html += `<div class="col-sm"><div class="fc-alert-gray field-note">
                <label class="mb-2 fw-bold"><i class="fa-solid fa-circle-exclamation me-1"></i> ${notes.title}</label>
                <p class="fc-text-gray">${notes.description}</p>
            </div></div>`;

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

//----------------------------------------------------------------------------------
