var _doc = $(document);

/** After AJAX replaces `.your-project-details`, Slick must bind to the new DOM (slick/project-plan-color.js). */
function fcAfterProjectDetailsSectionReloaded() {
    if (typeof window.fcRefreshProjectPlanColorSlick !== 'function') {
        return;
    }
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            window.fcRefreshProjectPlanColorSlick();
        });
    });
}

/*
    ----------------------------------------------------------------
    [START] CLICK EVENT
    ----------------------------------------------------------------
*/

_doc.on('click', '.fc-btn-download-fence', fcBtnDownloadFence);
_doc.on('click', '.fc-project-plan-download-png', fcProjectPlanDownloadPng);
_doc.on('click', '.fc-project-plan-download-pdf', fcProjectPlanDownloadPdf);
_doc.on('click', '.js-fc-copy-cart-items', fcCopyCartItems);

/**
 * Build plain-text lines from the project-plan Item List & Cart table.
 * Format: "{qty} {name}, {sku}" (one line per row).
 *
 * @param {jQuery} [$root] Optional tbody scope.
 * @return {string}
 */
function fcBuildCartItemsCopyText($root) {
    $root = $root && $root.length ? $root : $('#update_cart-list .table-cart tbody');
    if (!$root.length) {
        return '';
    }

    var lines = [];
    $root.find('tr').each(function() {
        var $tr = $(this);
        var $desc = $tr.find('td.align-top').first();
        if (!$desc.length) {
            return;
        }

        var name = $.trim($desc.children('.fw-bold.text-dark.mb-2').first().text());
        var sku = $.trim($desc.children('.text-muted.mb-1').first().text());

        var qty = '';
        var $visibleEditQty = $tr.find('.md-qty:visible input, .fencing-mb-input.md-qty:visible input').first();
        if ($visibleEditQty.length) {
            qty = $.trim(String($visibleEditQty.val() || ''));
        }
        if (!qty) {
            var $hiddenQty = $tr.find('input.input-qty, input[name^="cart[qty]"]').first();
            if ($hiddenQty.length) {
                qty = $.trim(String($hiddenQty.val() || ''));
            }
        }
        if (!qty) {
            qty = $.trim($tr.find('.fc-item-value.fw-bold').first().text());
        }

        if (!name && !sku) {
            return;
        }

        lines.push((qty || '0') + ' ' + name + (sku ? ', ' + sku : ''));
    });

    return lines.join('\n');
}

function fcCopyCartItems(e) {
    e.preventDefault();

    var $btn = $(this);
    var text = fcBuildCartItemsCopyText();
    if (!text) {
        return;
    }

    function showCopiedFeedback() {
        var $label = $btn.find('span.d-none.d-sm-inline');
        var original = $btn.data('fc-copy-label');
        if (original === undefined || original === '') {
            original = $label.length ? $label.text() : 'Copy';
            $btn.data('fc-copy-label', original);
        }
        if ($label.length) {
            $label.text('Copied!');
        }
        setTimeout(function() {
            if ($label.length) {
                $label.text(original);
            }
        }, 2000);
    }

    if (typeof fcCopyTextToClipboard === 'function') {
        fcCopyTextToClipboard(text, showCopiedFeedback);
        return;
    }

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).then(showCopiedFeedback).catch(function() {});
    }
}

function fcProjectPlanDownloadDateSuffix() {
    var date = new Date();
    return date.getDate() + '-' + (date.getMonth() + 1) + '-' + date.getFullYear();
}

function fcGetProjectPlanSectionEl(sectionIndex) {
    var pp = document.getElementById('pp-' + sectionIndex);
    return pp ? pp.closest('.fc-project-plan-section') : null;
}

function fcMeasureProjectPlanSectionContentWidth(sectionEl) {
    var container = sectionEl.querySelector('.fencing-panel-container');
    if (container) {
        return Math.ceil(Math.max(container.scrollWidth, container.getBoundingClientRect().width));
    }

    var result = sectionEl.querySelector('.fc-result');
    if (result) {
        return Math.ceil(Math.max(result.scrollWidth, result.getBoundingClientRect().width));
    }

    return Math.ceil(sectionEl.scrollWidth);
}

function fcPrepareProjectPlanSectionScreenshotClone(cloned) {
    if (!cloned || cloned.nodeType !== 1 || !cloned.classList) {
        return;
    }
    if (!cloned.classList.contains('fc-project-plan-section')) {
        return;
    }

    cloned.classList.add('fc-project-plan-capturing');

    var head = cloned.querySelector('.fc-project-plan-section-head');
    if (head) {
        head.style.position = 'relative';
        head.style.top = 'auto';
        head.style.zIndex = 'auto';
        head.style.boxShadow = 'none';
        head.classList.remove('fc-project-plan-section-head--stuck', 'fc-project-plan-section-head--dropdown-open');
    }

    var contentWidth = fcMeasureProjectPlanSectionContentWidth(cloned);
    var hscroll = cloned.querySelector('.fc-project-plan-hscroll');
    var planItem = cloned.querySelector('.plan-item');
    var dlRow = cloned.querySelector('.dl-row');
    var fcResult = cloned.querySelector('.fc-result');

    cloned.style.overflow = 'visible';
    cloned.style.maxWidth = 'none';

    if (hscroll) {
        hscroll.style.overflow = 'visible';
        hscroll.style.width = contentWidth + 'px';
        hscroll.scrollLeft = 0;
    }
    if (planItem) {
        planItem.style.overflow = 'visible';
    }
    if (dlRow) {
        dlRow.style.width = contentWidth + 'px';
    }
    if (fcResult) {
        fcResult.style.width = contentWidth + 'px';
        fcResult.style.maxWidth = 'none';
    }

    var skeleton = cloned.querySelector('.fc-project-plan-skeleton');
    if (skeleton) {
        skeleton.style.display = 'none';
    }
}

function fcProjectPlanSectionScreenshotOptions() {
    return {
        scale: Math.min(window.devicePixelRatio || 1, 2),
        backgroundColor: '#ffffff',
        timeout: 60000,
        features: {
            restoreScrollPosition: true,
            copyScrollbar: false
        },
        filter: function(node) {
            if (!node || node.nodeType !== 1) {
                return true;
            }
            var el = node;
            if (el.classList.contains('fc-project-plan-section-actions')) {
                return false;
            }
            if (el.classList.contains('fc-project-plan-skeleton')) {
                return false;
            }
            return true;
        },
        onCloneNode: function(cloned) {
            fcPrepareProjectPlanSectionScreenshotClone(cloned);
        }
    };
}

function fcCloseProjectPlanSectionDropdown(sectionEl) {
    if (!sectionEl || typeof bootstrap === 'undefined' || !bootstrap.Dropdown) {
        return;
    }

    var toggle = sectionEl.querySelector('.fc-project-plan-download-toggle');
    if (!toggle) {
        return;
    }

    var instance = bootstrap.Dropdown.getInstance(toggle);
    if (instance) {
        instance.hide();
    }
}

function fcCaptureProjectPlanSection(sectionIndex) {
    return new Promise(function(resolve, reject) {
        function startCapture() {
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    var sectionEl = fcGetProjectPlanSectionEl(sectionIndex);
                    if (!sectionEl) {
                        reject(new Error('Section not ready'));
                        return;
                    }

                    if (sectionEl.classList.contains('fc-project-plan-section--pending')) {
                        reject(new Error('Section is still loading'));
                        return;
                    }

                    if (!window.modernScreenshot || typeof window.modernScreenshot.domToPng !== 'function') {
                        reject(new Error('Capture library not loaded'));
                        return;
                    }

                    fcCloseProjectPlanSectionDropdown(sectionEl);

                    window.modernScreenshot
                        .domToPng(sectionEl, fcProjectPlanSectionScreenshotOptions())
                        .then(resolve)
                        .catch(reject);
                });
            });
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(startCapture).catch(startCapture);
        } else {
            startCapture();
        }
    });
}

function fcDownloadDataUrlPng(dataUrl, filename) {
    if (!dataUrl) {
        return;
    }

    var link = document.createElement('a');
    link.download = filename;
    link.href = dataUrl;
    link.click();
}

/**
 * Build a PDF from the same PNG data URL used for Download PNG (1:1 page size = image size).
 */
function fcDownloadDataUrlPdf(dataUrl, filename) {
    return new Promise(function(resolve, reject) {
        if (!dataUrl) {
            reject(new Error('No image data'));
            return;
        }

        if (!window.jspdf || !window.jspdf.jsPDF) {
            reject(new Error('jsPDF not loaded'));
            return;
        }

    window.jsPDF = window.jspdf.jsPDF;

        var probe = new Image();
        probe.onload = function() {
            try {
                var width = probe.width;
                var height = probe.height;
                var maxDim = 14400;

                if (width > maxDim || height > maxDim) {
                    var scale = Math.min(maxDim / width, maxDim / height);
                    width = Math.floor(width * scale);
                    height = Math.floor(height * scale);
                }

                var doc = new jsPDF({
                    orientation: width >= height ? 'landscape' : 'portrait',
                    unit: 'px',
                    format: [width, height],
                    hotfixes: ['px_scaling']
                });

                doc.addImage(dataUrl, 'PNG', 0, 0, width, height, undefined, 'FAST');
                doc.save(filename);
                resolve();
            } catch (err) {
                reject(err);
            }
        };
        probe.onerror = function() {
            reject(new Error('Could not read captured image'));
        };
        probe.src = dataUrl;
    });
}

function fcProjectPlanSectionFilename(sectionIndex, ext) {
    return 'project-plan-section-' + (sectionIndex + 1) + '-' + fcProjectPlanDownloadDateSuffix() + '.' + ext;
}

function fcProjectPlanDownloadBusy($dropdown, isBusy, format) {
    var $toggle = $dropdown.find('.fc-project-plan-download-toggle');
    var $icon = $toggle.find('i').first();

    if (isBusy) {
        $toggle.prop('disabled', true);
        $icon.data('fcPrevClass', $icon.attr('class'));
        $icon.attr('class', 'fas fa-spinner fa-spin me-sm-1');
        fcProjectPlanDownloadToast(
            true,
            format === 'pdf'
                ? 'Building the PDF for this section.'
                : 'Building the image for this section.'
        );
        return;
    }

    $toggle.prop('disabled', false);
    var prevClass = $icon.data('fcPrevClass');
    $icon.attr('class', prevClass || 'fa-solid fa-download me-sm-1');
    fcProjectPlanDownloadToast(false);
}

function fcProjectPlanDownloadPng(e) {
    e.preventDefault();

    var sectionIndex = parseInt($(this).attr('data-section'), 10);
    if (!Number.isFinite(sectionIndex)) {
        return;
    }

    var $dropdown = $(this).closest('.fc-project-plan-download');
    fcProjectPlanDownloadBusy($dropdown, true, 'png');

    fcCaptureProjectPlanSection(sectionIndex).then(function(dataUrl) {
        fcDownloadDataUrlPng(dataUrl, fcProjectPlanSectionFilename(sectionIndex, 'png'));
    }).catch(function() {
        window.alert('Could not capture this section. Wait for the diagram to finish loading, then try again.');
    }).finally(function() {
        fcProjectPlanDownloadBusy($dropdown, false);
    });
}

function fcProjectPlanDownloadPdf(e) {
    e.preventDefault();

    var sectionIndex = parseInt($(this).attr('data-section'), 10);
    if (!Number.isFinite(sectionIndex)) {
        return;
    }

    var $dropdown = $(this).closest('.fc-project-plan-download');
    fcProjectPlanDownloadBusy($dropdown, true, 'pdf');

    fcCaptureProjectPlanSection(sectionIndex).then(function(dataUrl) {
        return fcDownloadDataUrlPdf(dataUrl, fcProjectPlanSectionFilename(sectionIndex, 'pdf'));
    }).catch(function() {
        window.alert('Could not capture this section. Wait for the diagram to finish loading, then try again.');
    }).finally(function() {
        fcProjectPlanDownloadBusy($dropdown, false);
    });
}

function fcGetProjectPlanSectionIndices() {
    var indices = [];
    document.querySelectorAll('#fc-fence-list .fc-project-plan-section').forEach(function(section) {
        var idx = parseInt(section.getAttribute('data-section-index'), 10);
        if (Number.isFinite(idx)) {
            indices.push(idx);
        }
    });
    indices.sort(function(a, b) {
        return a - b;
    });
    return indices;
}

function fcWaitForProjectPlanSectionsReady(timeoutMs) {
    var deadline = Date.now() + (timeoutMs || 120000);

    return new Promise(function(resolve, reject) {
        function check() {
            var indices = fcGetProjectPlanSectionIndices();
            if (!indices.length) {
                if (Date.now() >= deadline) {
                    reject(new Error('No project plan sections found'));
                    return;
                }
                requestAnimationFrame(check);
                return;
            }

            if (document.querySelector('#fc-fence-list .fc-project-plan-section--pending')) {
                if (Date.now() >= deadline) {
                    reject(new Error('Project plan sections still loading'));
                    return;
                }
                requestAnimationFrame(check);
                return;
            }

            resolve(indices);
        }

        check();
    });
}

function fcLoadProjectPlanCaptureDimensions(dataUrl) {
    return new Promise(function(resolve, reject) {
        var probe = new Image();
        probe.onload = function() {
            resolve({
                dataUrl: dataUrl,
                width: probe.width,
                height: probe.height
            });
        };
        probe.onerror = function() {
            reject(new Error('Could not read captured image'));
        };
        probe.src = dataUrl;
    });
}

/** A4 portrait page size in px (~96dpi) for cart pages in the combined project-plan PDF. */
function fcProjectPlanA4PageSizePx() {
    return { width: 794, height: 1123 };
}

function fcProjectPlanCartCaptureWidthPx() {
    return fcProjectPlanA4PageSizePx().width;
}

function fcPrepareProjectPlanCartScreenshotClone(cloned) {
    if (!cloned || cloned.nodeType !== 1 || !cloned.classList) {
        return;
    }

    var captureWidth = fcProjectPlanCartCaptureWidthPx();
    cloned.classList.add('fc-project-plan-cart-capturing');
    cloned.style.width = captureWidth + 'px';
    cloned.style.maxWidth = captureWidth + 'px';
    cloned.style.overflow = 'visible';
    cloned.style.background = '#ffffff';
    cloned.style.boxSizing = 'border-box';

    cloned.querySelectorAll('.fc-cart-toolbar, .js-fc-cart-toolbar, .fc-view-total-cost-bar').forEach(function(el) {
        el.style.display = 'none';
    });

    cloned.querySelectorAll('.md-qty').forEach(function(el) {
        el.style.display = 'none';
    });
    cloned.querySelectorAll('.fc-item-value').forEach(function(el) {
        el.style.display = 'block';
    });

    cloned.querySelectorAll('.table-cart th, .table-cart td').forEach(function(el) {
        if (el.classList.contains('d-none') && el.classList.contains('d-md-table-cell')) {
            el.style.display = 'table-cell';
        }
    });

    cloned.querySelectorAll('.table-cart .d-block.d-md-none').forEach(function(el) {
        el.style.display = 'none';
    });
}

function fcProjectPlanCartScreenshotOptions() {
    return {
        scale: Math.min(window.devicePixelRatio || 1, 2),
        backgroundColor: '#ffffff',
        width: fcProjectPlanCartCaptureWidthPx(),
        timeout: 60000,
        features: {
            restoreScrollPosition: true,
            copyScrollbar: false
        },
        filter: function(node) {
            if (!node || node.nodeType !== 1) {
                return true;
            }
            var el = node;
            if (el.classList.contains('fc-cart-toolbar')) {
                return false;
            }
            if (el.classList.contains('js-fc-cart-toolbar')) {
                return false;
            }
            if (el.classList.contains('fc-view-total-cost-bar')) {
                return false;
            }
            if (el.classList.contains('fc-cancel-item')) {
                return false;
            }
            if (el.classList.contains('fc-update-item')) {
                return false;
            }
            if (el.classList.contains('fc-reset-item')) {
                return false;
            }
            return true;
        },
        onCloneNode: function(cloned) {
            fcPrepareProjectPlanCartScreenshotClone(cloned);
        }
    };
}

function fcCaptureProjectPlanCartList() {
    return new Promise(function(resolve, reject) {
        function startCapture() {
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    var cartEl = document.getElementById('update_cart-list');
                    if (!cartEl) {
                        reject(new Error('Cart not found'));
                        return;
                    }

                    if (!window.modernScreenshot || typeof window.modernScreenshot.domToPng !== 'function') {
                        reject(new Error('Capture library not loaded'));
                        return;
                    }

                    window.modernScreenshot
                        .domToPng(cartEl, fcProjectPlanCartScreenshotOptions())
                        .then(resolve)
                        .catch(reject);
                });
            });
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(startCapture).catch(startCapture);
        } else {
            startCapture();
        }
    });
}

function fcAppendCartListA4Pages(doc, cartDataUrl) {
    return fcLoadProjectPlanCaptureDimensions(cartDataUrl).then(function(dim) {
        var a4 = fcProjectPlanA4PageSizePx();
        var margin = 24;
        var pageW = a4.width;
        var pageH = a4.height;
        var contentW = pageW - margin * 2;
        var contentH = pageH - margin * 2;
        var imgW = contentW;
        var imgH = (dim.height * imgW) / dim.width;
        var y = margin;

        doc.addPage([pageW, pageH], 'portrait');
        doc.addImage(dim.dataUrl, 'PNG', margin, y, imgW, imgH, undefined, 'FAST');

        var heightLeft = imgH - contentH;
        while (heightLeft > 0) {
            y = margin - (imgH - heightLeft);
            doc.addPage([pageW, pageH], 'portrait');
            doc.addImage(dim.dataUrl, 'PNG', margin, y, imgW, imgH, undefined, 'FAST');
            heightLeft -= contentH;
        }
    });
}

function fcBuildProjectPlanPdfFromCaptures(sectionCaptures, cartCapture) {
    if (!sectionCaptures || !sectionCaptures.length) {
        return Promise.reject(new Error('No captures'));
    }

    if (!window.jspdf || !window.jspdf.jsPDF) {
        return Promise.reject(new Error('jsPDF not loaded'));
    }

    window.jsPDF = window.jspdf.jsPDF;

    return Promise.all(sectionCaptures.map(fcLoadProjectPlanCaptureDimensions)).then(function(dimensions) {
        var doc = null;
        var maxDim = 14400;

        dimensions.forEach(function(dim, index) {
            var width = dim.width;
            var height = dim.height;

            if (width > maxDim || height > maxDim) {
                var scale = Math.min(maxDim / width, maxDim / height);
                width = Math.floor(width * scale);
                height = Math.floor(height * scale);
            }

            var orientation = width >= height ? 'landscape' : 'portrait';

            if (index === 0) {
                doc = new jsPDF({
                    orientation: orientation,
                    unit: 'px',
                    format: [width, height],
                    hotfixes: ['px_scaling']
                });
            } else {
                doc.addPage([width, height], orientation);
            }

            doc.addImage(dim.dataUrl, 'PNG', 0, 0, width, height, undefined, 'FAST');
        });

        var savePdf = function() {
            doc.save('project-plan-' + fcProjectPlanDownloadDateSuffix() + '.pdf');
        };

        if (cartCapture) {
            return fcAppendCartListA4Pages(doc, cartCapture).then(savePdf);
        }

        savePdf();
    });
}

function fcCaptureAllProjectPlanSections(indices) {
    return indices.reduce(function(chain, sectionIndex) {
        return chain.then(function(captures) {
            return fcCaptureProjectPlanSection(sectionIndex).then(function(dataUrl) {
                captures.push(dataUrl);
                return captures;
            });
        });
    }, Promise.resolve([]));
}

/** In-flight downloads. Two can overlap — a section PNG started while the full plan builds. */
var fcProjectPlanDownloadCount = 0;

/**
 * Show/hide the bottom-left download progress toast.
 *
 * Downloads used to raise `.fc-loader-overlay`, the full-screen submission loader. Capturing
 * a plan takes seconds and blocks nothing, so it gets a status toast instead of a blackout.
 */
function fcProjectPlanDownloadToast(show, message) {
    var toast = document.querySelector('.fc-download-toast');
    if (!toast) {
        return;
    }

    var messageEl = toast.querySelector('.js-fc-download-toast-message');
    var defaultMessage = messageEl ? messageEl.getAttribute('data-default-message') : '';

    if (messageEl && !defaultMessage) {
        defaultMessage = messageEl.textContent.trim();
        messageEl.setAttribute('data-default-message', defaultMessage);
    }

    if (show) {
        fcProjectPlanDownloadCount += 1;
        if (messageEl) {
            messageEl.textContent = message || defaultMessage;
        }
        toast.classList.add('is-visible');
        return;
    }

    // Only the last download finishing takes the toast away.
    fcProjectPlanDownloadCount = Math.max(0, fcProjectPlanDownloadCount - 1);
    if (fcProjectPlanDownloadCount > 0) {
        return;
    }

    toast.classList.remove('is-visible');
    if (messageEl && defaultMessage) {
        messageEl.textContent = defaultMessage;
    }
}

function fcBtnDownloadFenceBusy($button, isBusy) {
    if (isBusy) {
        $button.find('i').removeAttr('class').addClass('fas fa-spinner fa-spin');
        $button.attr('disabled', true).find('span').html('Preparing Plans...');
        fcProjectPlanDownloadToast(true);
        return;
    }

    $button.find('i').removeAttr('class').addClass('fa-solid fa-download');
    $button.removeAttr('disabled').find('span').html('Download Plans');
    fcProjectPlanDownloadToast(false);
}

function fcBtnDownloadFence(e) {
    e.preventDefault();

    var $button = $(this);
    if ($button.attr('disabled')) {
        return;
    }

    fcBtnDownloadFenceBusy($button, true);

    fcWaitForProjectPlanSectionsReady()
        .then(function(indices) {
            return fcCaptureAllProjectPlanSections(indices).then(function(sectionCaptures) {
                return fcCaptureProjectPlanCartList().then(function(cartCapture) {
                    return {
                        sectionCaptures: sectionCaptures,
                        cartCapture: cartCapture
                    };
                });
            });
        })
        .then(function(payload) {
            return fcBuildProjectPlanPdfFromCaptures(payload.sectionCaptures, payload.cartCapture);
        })
        .catch(function() {
            window.alert('Could not download plans. Wait for all project plan sections to finish loading, then try again.');
        })
        .finally(function() {
            fcBtnDownloadFenceBusy($button, false);
        });
}

//----------------------------------------------------------------------------------

// PROJECT DETAILS SECTION

/**
 * Leave project-details edit mode without saving (restore fields from displayed spans).
 */
function fcProjectDetailsCancelEdit() {
    $('.fc-btn-reset').trigger('click');
    $('.project-details--editable').toggleClass('project-details--edit project-details--editable');
    $('.fc-project-details .fc-form-group, .fc-btn-reset').hide();
    $('.fc-project-details table span:not([class^="js-"])').show();
    $('.js-project-details-controls').addClass('fc-d-none');
    $(".fc-btn-edit[data-action='edit']").show();
    $('.form-control-clear').remove();
}

_doc.on('click', '.fc-btn-cancel-project-details', function(e) {
    e.preventDefault();
    fcProjectDetailsCancelEdit();
});

_doc.on('click', '.fc-btn-edit', fcBtnEdit);

function fcBtnEdit(e) {
    e?.preventDefault();
    $('[name="action"]').val('update_details');
    let _this = $(this);
    let _action = _this.attr('data-action');
    if (_action == 'edit') {
        $('.project-details--edit').toggleClass('project-details--edit project-details--editable');
        $('.fc-project-details .fc-form-group, .fc-btn-reset').show();
        $('.fc-project-details table span:not([class^="js-"])').hide();

        _this.hide();
        loadClearForm();
    } else {
        $('form').submit();
    }
    $('.js-project-details-controls').removeClass('fc-d-none');

}

//----------------------------------------------------------------------------------

_doc.on('click', '.project-details--editable', projectDetailsEditable);

function projectDetailsEditable() {
    $('#submit-modal').show();
    restoreFormData();
    if (typeof window.fcRefreshColorOptionsSlick === 'function') {
        setTimeout(function() {
            window.fcRefreshColorOptionsSlick();
        }, 220);
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.project-details--update', projectDetailsUpdate);

function projectDetailsUpdate(e) {
    e?.preventDefault();
    if (typeof fcSyncProjectPlanColorHiddenInputsFromModal === 'function') {
        fcSyncProjectPlanColorHiddenInputsFromModal();
    }
    if (typeof fcPersistOtherProductsToProjectPlans === 'function') {
        fcPersistOtherProductsToProjectPlans();
    }
    $('[name="action"]').val('update_project_details');
    $('.fc-btn-edit[data-action="update"]').trigger('click');
    $('#submit-modal').hide();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-btn-reset', fcBtnReset);

function fcBtnReset(e) {
    e?.preventDefault();
    $('.fc-table-customer td').each(function() {
        var _this = $(this);
        if (_this.find('.form-control').length) {
            var val = _this.find('span').text();
            if (_this.find('.form-control').prop('tagName').toLowerCase() == 'select') {
                _this.find('option:contains(' + val + ')').prop('selected', true);
            } else {
                _this.find('.form-control').val(val)
            }
        }
    });
    $(".fc-table-customer .fc-form-control").css({ 'color': '#f67925' });
    setTimeout(function() {
        $(".fc-table-customer .fc-form-control").css({ 'color': '' });
    }, 500);
}

//----------------------------------------------------------------------------------

function fcExitCartEditMode() {
    $('.fc-table-items .md-qty, .fc-reset-item').add('.fc-cancel-item').hide();
    $('.fc-item-value').removeClass('d-none');
    $('.js-fc-edit-item span').html('Edit');
}

_doc.on('click', '.fc-cancel-item', function(e) {
    e.preventDefault();
    fcResetItem(e);
    fcExitCartEditMode();
});

_doc.on('click', '.js-fc-optional-cart-toggle', function(e) {
    e.preventDefault();
    var btn = e.currentTarget;
    if (!btn || btn.disabled) {
        return;
    }
    var optKey = btn.getAttribute('data-optional-key') || '';
    var include = btn.getAttribute('data-include') || '0';
    if (!optKey) {
        return;
    }

    btn.disabled = true;

    var formData = new FormData();
    formData.set('action', 'toggle_optional_cart');
    formData.set('optional_key', optKey);
    formData.set('include', include);

    var checkoutUrl =
        typeof base_url === 'function' ? base_url('checkout') : 'checkout';

    fetch(checkoutUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
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
        })
        .catch(function() {})
        .finally(function() {
            btn.disabled = false;
        });
});

_doc.on('click', '.js-fc-edit-item', jsFcEditItem);

function jsFcEditItem(e) {
    e?.preventDefault();
    var _this = $(this);
    $('[name="action"]').val('update_cart');
    if (_this.find('span').text() === 'Edit') {
        $('.fc-table-items .md-qty, .fc-reset-item').add('.fc-cancel-item').show();
        $('.fc-item-value').addClass('d-none');
        _this.find('span').html('Save');
    } else {
        $('form').submit();
    }
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-reset-item', fcResetItem);

function fcResetItem(e) {
    e?.preventDefault();
    $('.fc-table-items td').each(function() {
        var _this = $(this);
        if (_this.find('.fc-form-field').length) {
            var val = _this.closest('tr').data('original');
            _this.find('.fc-form-field').val(val)
        }
    });
    $(".fc-table-items .fc-form-field").css({ 'color': '#f67925' });
    setTimeout(function() {
        $(".fc-table-items .fc-form-field").css({ 'color': '' });
    }, 500);
}

//----------------------------------------------------------------------------------

_doc.on('click', '.btn-submit', btnSubmit);

function btnSubmit(e) {
    e?.preventDefault();
    $('[name="action"]').val('push_order');
    $('form').submit();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fencing-qty-btn', inputQty);
_doc.on('keyup', '.table-cart [input-type="number"]', inputQty);

function inputQty(e) {
    var _this = $(this);
    var val = _this.closest('.fencing-mb-input').find('input').val();
    _this.closest('tr').find('.fc-form-field').val(val);
}

/* ----------------------------------------------------------------
    [END] CLICK EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] CHANGE EVENT
    ---------------------------------------------------------------- */

_doc.on('change', '[name="cart[shipping_type]"]', cart_shippingType);

function cart_shippingType() {
    $('form').find('[type="submit"]').trigger('click');
}

/* ----------------------------------------------------------------
    [END] CHANGE EVENT
    ---------------------------------------------------------------- */


/* ----------------------------------------------------------------
    [START] VALIDATE
    ---------------------------------------------------------------- */
// https://jqueryvalidation.org/validate/

$("#paymentFrm").validate({
    rules: {
        name: { required: true },
        mobile: { required: true },
        postcode: { required: true },
        address: { required: true },
        email: {
            required: true,
            email: true
        },
    },
    messages: {},
    submitHandler: function(form) {
        window.onbeforeunload = function() { return false; }
        var action = $('[name="action"]').val(),
            form = $('form')[0],
            formData = new FormData(form);
        formData.set("action", action);

        $('#paymentResponse').html('');
        $('#' + action + '-section').find('.fc-section-loader-overlay').show();
        if (action == 'update_cart') {
            $('.fc-table-items td').each(function() {
                var _this = $(this);

                if (_this.find('.fc-form-control').length) {
                    var val = _this.find('.fc-form-control').val();
                    _this.find('.fc-item-value').html(val);
                }
            });
            $(".fc-table-items .fc-form-control").css({ 'color': '#4caf50' });
            $.ajax({
                url: 'checkout',
                type: "POST",
                data: formData,
                headers: {},
                beforeSend: function() {
                    HELPER.loadSectionOverlay('update_cart-list');
                },
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    try {
                        $('.fc-table-items').html(response);

                        setTimeout(function() {
                            $(".fc-table-items .fc-form-control").css({ 'color': '' });
                            HELPER.removeSectionOverlay();
                            $('.fc-item-value').removeClass('d-none');
                            $('.fc-table-items .md-qty, .fc-reset-item').add('.fc-cancel-item').hide();

                            window.onbeforeunload = function() {}

                        }, 500);

                        $('.js-fc-edit-item span').html('Edit');

                    } catch (err) {
                        console.log('err: ', response);
                    }
                }
            });

        } else if (action == 'update_project_details') {
            var pdBtnVisible = $('.project-details-controls button').is(':visible');
            $(".fc-table-customer .fc-form-control").css({ 'color': '#4caf50' });
            try {
                var projectPlansRaw = localStorage.getItem('project-plans');
                if (projectPlansRaw) {
                    formData.set('project_plans', projectPlansRaw);
                }
            } catch (ePp) {}
            $.ajax({
                url: 'checkout',
                type: "POST",
                data: formData,
                beforeSend: function() {
                    HELPER.loadSectionOverlay('update_details-section');
                },
                headers: {},
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    try {
                        $(".your-project-details").html(response);
                        fcAfterProjectDetailsSectionReloaded();

                        var finishProjectDetailsUpdate = function() {
                            $(".fc-table-customer .fc-form-control").css({ 'color': '' });
                            HELPER.removeSectionOverlay();
                            if (pdBtnVisible) {
                                $('.fc-btn-edit[data-action="edit"]').trigger('click');
                            }
                            window.onbeforeunload = function() {}
                        };

                        if (typeof fcSyncProjectPlanSessionCart === 'function') {
                            HELPER.loadSectionOverlay('update_cart-list');
                            fcSyncProjectPlanSessionCart(function() {
                                HELPER.removeSectionOverlay();
                                finishProjectDetailsUpdate();
                            });
                        } else {
                            $('[name="action"]').val('update_cart');
                            $('form').submit();
                            setTimeout(finishProjectDetailsUpdate, 500);
                        }
                    } catch (err) {
                    }
                }
            });
        } else if (action == 'update_details') {
            $('.fc-table-customer td').each(function() {
                var _this = $(this);
                if (_this.find('.fc-form-control').length) {
                    if (_this.find('.fc-form-control').prop('tagName').toLowerCase() == 'select') {
                        var val = _this.find('.fc-form-control option:selected').text();
                    } else {
                        var val = _this.find('.fc-form-control').val();
                    }
                    _this.find('span').html(val);
                }
            });
            $(".fc-table-customer .fc-form-control").css({ 'color': '#4caf50' });
            $.ajax({
                url: 'checkout',
                type: "POST",
                data: formData,
                beforeSend: function() {
                    HELPER.loadSectionOverlay('update_details-section');
                },
                headers: {},
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    try {
                        $('[name="action"]').val('update_cart');
                        $('form').submit();
                        setTimeout(function() {
                            $(".fc-table-customer .fc-form-control").css({ 'color': '' });
                            HELPER.removeSectionOverlay();
                            $('.fc-table-customer span').show();
                            $('.fc-project-details .fc-form-group, .fc-btn-reset').hide();
                            $('.js-project-details-controls').addClass('fc-d-none');
                            $(".fc-btn-edit[data-action='edit']").show();
                            $(".your-project-details").html(response);
                            fcAfterProjectDetailsSectionReloaded();
                            window.onbeforeunload = function() {}
                        }, 500);
                    } catch (err) {
                    }
                }
            });

        } else if (action == 'push_order') {
            $('.fc-loader-overlay').show();
            $('.fc-loader ul li').remove();
            var items = [
                'Preparing:',
                'Checking customer details...',
                'Pushing order into cart...',
                'Redirecting to fencing website...',
            ];
            $.each(items, function(k, v) {
                $('.fc-loader ul').append(`<li><i class="fa fa-check fc-mr-1"></i> ${v}</li>`);
            });
            setTimeout(function() {
                $('.fc-loader ul li:first-child').addClass('fc-text-success');
            }, 500);
   
            $.ajax({
                url: 'checkout',
                type: "POST",
                data: formData,
                headers: {},
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    if (!response) {
                        $('.fc-loader-overlay').hide();
                        $('#' + action + '-section').find('.fc-section-loader-overlay').hide();
                        $('#paymentResponse').html('No response from server. Please try again.');
                        return;
                    }

                    var info;
                    try {
                        info = JSON.parse(response);
                    } catch (err) {
                        $('.fc-loader-overlay').hide();
                        $('#' + action + '-section').find('.fc-section-loader-overlay').hide();
                        $('#paymentResponse').html('Unexpected server response. Please try again.');
                        return;
                    }

                    if (info.error) {
                        $('.fc-loader-overlay').hide();
                        $('#' + action + '-section').find('.fc-section-loader-overlay').hide();
                        $('#paymentResponse').html(info.message || 'Could not push order. Please try again.');
                        return;
                    }

                    if (!info.url) {
                        $('.fc-loader-overlay').hide();
                        $('#' + action + '-section').find('.fc-section-loader-overlay').hide();
                        $('#paymentResponse').html('Invalid store response. Please try again.');
                        return;
                    }

                    window.onbeforeunload = function() {}
                    if (typeof clearPlannerLocalStorage === 'function') {
                        clearPlannerLocalStorage();
                    }
                    var $remaining = $('.fc-loader ul li:not(.fc-text-success)');
                    $remaining.each(function(i) {
                        var _this = $(this);
                        setTimeout(function() {
                            _this.addClass('fc-text-success');
                        }, 2000 * i);
                    });
                    // Wait for the last step to actually light up before navigating away —
                    // firing location.href immediately (as before) meant the browser started
                    // leaving the page before any of these highlights could ever be seen.
                    setTimeout(function() {
                        location.href = info.url;
                    }, 2000 * Math.max(0, $remaining.length - 1) + 600);
                },
                error: function() {
                    $('.fc-loader-overlay').hide();
                    $('#' + action + '-section').find('.fc-section-loader-overlay').hide();
                    $('#paymentResponse').html('Could not reach checkout. Please try again.');
                }
            });
        }
    }
});

/* ----------------------------------------------------------------
    [END] VALIDATE
    ---------------------------------------------------------------- */

/**
 * Project plan: pin "View Total Cost" to the viewport bottom while
 * #update_cart-section is at least ~30% visible (see CSS .fc-cart-sticky-visible).
 */
(function initProjectPlanCartStickyBar() {
    if (!document.body.classList.contains('fc-project-plan-page')) {
        return;
    }
    var section = document.getElementById('update_cart-section');
    var bar = document.querySelector('.fc-view-total-cost-bar');
    if (!section || typeof IntersectionObserver === 'undefined') {
        return;
    }

    function updateBarHeightVar() {
        if (!document.body.classList.contains('fc-cart-sticky-visible') || !bar) {
            document.documentElement.style.removeProperty('--fc-view-total-bar-h');
            return;
        }
        document.documentElement.style.setProperty('--fc-view-total-bar-h', bar.offsetHeight + 'px');
    }

    function setSticky(on) {
        document.body.classList.toggle('fc-cart-sticky-visible', on);
        window.requestAnimationFrame(updateBarHeightVar);
    }

    var thresholds = Array.from({ length: 21 }, function(_, i) {
        return i * 0.05;
    });

    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var visibleH = entry.intersectionRect.height;
            /* ≥30% of viewport shows the cart section, or ≥30% of shorter sections (ratio). */
            var on = entry.isIntersecting && (
                visibleH >= vh * 0.3 ||
                entry.intersectionRatio >= 0.3
            );
            setSticky(on);
        });
    }, {
        root: null,
        rootMargin: '0px',
        threshold: thresholds
    });

    io.observe(section);

    window.addEventListener('resize', updateBarHeightVar);
})();