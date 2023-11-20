
//Global Variable
var FENCES = FENCES || {};

FENCES = {
    //default values
    defaultValues: {
        measurement: '',
        unit: 'mm',
    },
    activeSetting: '',
    el:  {
        'tabContainer': '.js-fencing-tab-container',
        'tabArea': '.js-fencing-tab-container-area',
        'fencingTab': '.fencing-tab',
        'zoomReset': '.js-fc-zoom-reset',
        'measurementBox': '.measurement-box-number',
        //Fencing container div that wraps entire application
        'container': '.fencing-container'
    },
    init: function() {
        FENCES.hideElementsOnLoad();
        setMeasurementDefaultValue();

        FENCES.setActiveSetting('');

    },
    hideElementsOnLoad: function() {
        hideZoomResetButton();
        hideDeleteSectionBtn();
    },
    setActiveSetting: function(val) {
        let fencingContainer = document.querySelector(FENCES.el.container);
        if( fencingContainer ){
            FENCES.activeSetting = val;
            fencingContainer.setAttribute('data-key', val);
        }
    }
};

$(function(){
    FENCES.init();
});

