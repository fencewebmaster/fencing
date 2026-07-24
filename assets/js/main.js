//Global Variable
var FENCES = FENCES || {};

FENCES = {
    //default values
    defaultValues: {
        measurement: '',
        unit: 'mm',
    },
    activeSetting: '',

    //----------------------------------------------------------------------------------
    
    el: {
        'tabContainer': '.js-fencing-tab-container',
        'tabArea': '.js-fencing-tab-container-area',
        'fencingTab': '.fencing-tab',
        'zoomReset': '.js-fc-zoom-reset',
        'measurementBoxNumber': '.measurement-box-number',
        //Fencing container div that wraps entire application
        'container': '.fencing-container',
        'fencingContainer': '.fencing-container',
        'fencingPanelContainer': '.fencing-panel-container',
        'fencingPanelItem': '.fencing-panel-item',
        'shortPanelItem': '.short-panel-item',
        'fencingPanelControls': '.fencing-panel-controls',
        'fencingTabSelected': '.fencing-tab-selected',
        'fsiSelected': '.fsi-selected',
        'fcColorOptions': '.fc-color-options',
        'jsFcFormStep': '.js-fc-form-step',
        'fencingPanelGate': '.fencing-panel-gate',
        'fencingOffcut': '.fencing-offcut',
        'fcInputMsg': '.fc-input-msg',
        'fcFenceResetAll': '.fc-fence-reset-all',
        'fcTabTitle': '.fc-tab-title',
        'fcTabSubtitle': '.fc-tab-subtitle',
        'jsBtnDeleteFence': '.js-btn-delete-fence',
        'panelItem': '.panel-item',
        'fencingDisplayResult': '.fencing-display-result',
        'btnGate': '#btn-gate',
    },

    //----------------------------------------------------------------------------------

    init: function() {
        FENCES.hideElementsOnLoad();
        HELPER.setMeasurementDefaultValue();
        FENCES.setActiveSetting('');
    },
    
    //----------------------------------------------------------------------------------

    hideElementsOnLoad: function() {
        HELPER.hideZoomResetButton();
        HELPER.hideDeleteSectionBtn();
    },

    //----------------------------------------------------------------------------------

    setActiveSetting: function(val) {
        let fencingContainer = document.querySelector(FENCES.el.container);
        if (fencingContainer) {
            FENCES.activeSetting = val;
            fencingContainer.setAttribute('data-key', val);
        }
    }

    //----------------------------------------------------------------------------------

};

$(function() {
    FENCES.init();
});


