
//Global Variable
var FENCES = FENCES || {};

FENCES = {
    //default values
    defaultValues: {
        measurement: 11000
    },
    el:  {
        'tabContainer': '.js-fencing-tab-container',
        'tabArea': '.js-fencing-tab-container-area',
        'fencingTab': '.fencing-tab',
        'zoomReset': '.js-fc-zoom-reset',
        'measurementBox': '.measurement-box-number'
    },
    init: function() {
        FENCES.hideElementsOnLoad();
        setMeasurementDefaultValue();
    },
    hideElementsOnLoad: function() {
        hideZoomResetButton();
        hideDeleteSectionBtn();
    }
};

$(function(){
    FENCES.init();
});

