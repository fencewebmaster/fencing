
//Global Variable
var FENCES = FENCES || {};

FENCES = {
    el:  {
        'tabContainer': '.js-fencing-tab-container',
        'tabArea': '.js-fencing-tab-container-area',
        'fencingTab': '.fencing-tab',
        'zoomReset': '.js-fc-zoom-reset'
    },
    init: function() {
        FENCES.hideElementsOnLoad();
    },
    hideElementsOnLoad: function() {
        hideZoomResetButton();
        hideDeleteSectionBtn();
    }
};

$(function(){
    FENCES.init();
});

