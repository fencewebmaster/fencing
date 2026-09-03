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

/**
 * Edit mode hides the Colour Options arrows, and the CSS takes the 40px they were housed in off
 * each side — so the row is 80px wider than the width Slick measured its slides against. Nothing
 * else tells Slick the box changed, so the slides would keep the narrower size and leave a gap at
 * the end of the row.
 *
 * rAF for the normal case (the class flip has to land before Slick re-measures), plus a short
 * timeout: rAF does not run at all while the tab is hidden, and the same 120ms is what the Step 4
 * colour carousel already waits before its own refresh in events.js.
 */
function fcRefreshColourSlickAfterEditToggle() {
    fcAfterProjectDetailsSectionReloaded();

    setTimeout(function() {
        if (typeof window.fcRefreshProjectPlanColorSlick === 'function') {
            window.fcRefreshProjectPlanColorSlick();
        }
    }, 120);
}

/**
 * Pushes the chat launcher up when a sticky bar (project details footer or cart action bar) is
 * pinned underneath it, so it doesn't cover Save Changes / Edit Quantity on mobile.
 */
function fcSyncChatLauncherOffset() {
    var launcher = document.querySelector('.fc-chat-launcher');
    if (!launcher) {
        return;
    }

    var corner = launcher.getBoundingClientRect();
    var lift = 0;

    $('.js-project-details-footer, .js-fc-cart-edit-bar').each(function() {
        var bar = this.getBoundingClientRect();
        if (!bar.height || !bar.width) {
            return;
        }
        // Bar is pinned when its bottom edge sits on the viewport's bottom edge.
        if (Math.abs(bar.bottom - window.innerHeight) > 1) {
            return;
        }
        // Skip if it doesn't overlap the launcher horizontally (desktop case).
        if (bar.right < corner.left || bar.left > corner.right) {
            return;
        }
        lift = Math.max(lift, bar.height);
    });

    launcher.style.setProperty('--fc-chat-launcher-lift', lift + 'px');
}

var fcChatLauncherFrame = null;

function fcQueueChatLauncherOffset() {
    // One measurement per animation frame. Cancel-and-replace, not skip-if-pending: a frame
    // requested on a hidden tab never fires, which would otherwise wedge this permanently.
    if (fcChatLauncherFrame !== null) {
        cancelAnimationFrame(fcChatLauncherFrame);
    }
    fcChatLauncherFrame = requestAnimationFrame(function() {
        fcChatLauncherFrame = null;
        fcSyncChatLauncherOffset();
    });
}

$(window).on('scroll resize', fcQueueChatLauncherOffset);
// Edit mode and cart renders can change bar height without any scroll happening.
_doc.on('click', '.js-fc-edit-item, .fc-cancel-item, .fc-btn-edit, .fc-btn-cancel-project-details', fcQueueChatLauncherOffset);
$(fcSyncChatLauncherOffset);
$(window).on('load', fcSyncChatLauncherOffset);

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

    // Swap both label and icon: only one is visible at a time depending on viewport width.
    function showCopiedFeedback() {
        var $label = $btn.find('span.d-none.d-sm-inline');
        var $icon = $btn.find('i').first();

        var original = $btn.data('fc-copy-label');
        if (original === undefined || original === '') {
            original = $label.length ? $label.text() : 'Copy';
            $btn.data('fc-copy-label', original);
        }

        var originalIcon = $btn.data('fc-copy-icon');
        if (originalIcon === undefined && $icon.length) {
            originalIcon = $icon.attr('class');
            $btn.data('fc-copy-icon', originalIcon);
        }

        if ($label.length) {
            $label.text('Copied!');
        }
        if ($icon.length) {
            $icon.attr('class', 'fa-solid fa-check me-sm-1');
        }
        $btn.addClass('is-copied');

        // Restart the timer on repeat copies instead of stacking.
        clearTimeout($btn.data('fc-copy-timer'));
        $btn.data('fc-copy-timer', setTimeout(function() {
            if ($label.length) {
                $label.text(original);
            }
            if ($icon.length && originalIcon) {
                $icon.attr('class', originalIcon);
            }
            $btn.removeClass('is-copied');
        }, 2000));
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
    $icon.attr('class', prevClass || 'fa-solid fa-ellipsis-vertical me-sm-1');
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

    cloned.querySelectorAll('.fc-cart-toolbar, .js-fc-cart-toolbar, .fc-view-total-cost-bar, .fc-cart-edit-bar').forEach(function(el) {
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
            if (el.classList.contains('fc-cart-edit-bar')) {
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
 * Save Changes and Reset are dead until a field differs from what it held when the editor opened.
 *
 * Measured against a snapshot rather than against the spans fcBtnReset restores from, which cannot
 * be read back reliably: the notes cell nests one span inside another, so .find('span').text()
 * returns "No notes added." twice over for a textarea that is empty, and every row would count as
 * changed the moment you pressed Edit Details. A snapshot also says the plainer thing — changed
 * since you started — and needs no special case for the selects.
 */
var fcProjectDetailsSnapshot = null;

function fcProjectDetailsFields$() {
    return $('.fc-table-customer').find('.form-control');
}

function fcProjectDetailsValues() {
    return fcProjectDetailsFields$()
        .map(function() {
            return String($(this).val());
        })
        .get();
}

function fcCaptureProjectDetailsSnapshot() {
    fcProjectDetailsSnapshot = fcProjectDetailsValues();
}

function fcProjectDetailsHasChanges() {
    if (!fcProjectDetailsSnapshot) {
        return false;
    }

    var now = fcProjectDetailsValues();
    if (now.length !== fcProjectDetailsSnapshot.length) {
        return true;
    }

    for (var i = 0; i < now.length; i++) {
        if (now[i] !== fcProjectDetailsSnapshot[i]) {
            return true;
        }
    }

    return false;
}

function fcSyncProjectDetailsActions() {
    var editing = !$('.js-project-details-controls').hasClass('fc-d-none');
    var enable = editing && fcProjectDetailsHasChanges();

    // Only the Save half of .fc-btn-edit — Edit Details shares the class and is always available.
    $('.fc-btn-reset, .fc-btn-edit[data-action="update"]')
        .toggleClass('disabled', !enable)
        .attr('aria-disabled', enable ? null : 'true')
        .attr('tabindex', enable ? null : '-1');
}

// keyup as well as input: the field clear buttons loadClearForm() adds empty a field with .val('')
// and announce it with a keyup, which neither of the other two events would carry.
_doc.on('input change keyup', '.fc-table-customer .form-control', fcSyncProjectDetailsActions);

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
    // Out of edit mode there is no baseline, so both actions go back to dead for the next round.
    fcProjectDetailsSnapshot = null;
    fcSyncProjectDetailsActions();
    fcRefreshColourSlickAfterEditToggle();
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
        $('.js-project-details-controls').removeClass('fc-d-none');
        // What the fields held on the way in, and so nothing to save or undo yet.
        fcCaptureProjectDetailsSnapshot();
        fcSyncProjectDetailsActions();
        fcRefreshColourSlickAfterEditToggle();
        return;
    }

    // originalEvent tells a real click from projectDetailsUpdate()'s .trigger('click'), which
    // arrives from the modal and has to go through whatever this button looks like.
    if (e && e.originalEvent && _this.hasClass('disabled')) {
        return;
    }

    $('form').submit();
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

    // As in fcBtnEdit: a real click respects the disabled state, fcProjectDetailsCancelEdit's
    // .trigger('click') has to restore whatever the button looks like.
    if (e && e.originalEvent && $(this).hasClass('disabled')) {
        return;
    }

    $('.fc-table-customer td').each(function() {
        var _this = $(this);
        if (_this.find('.form-control').length) {
            // Read the outermost span only, and drop the muted placeholder inside it. The cell
            // shows either a value or a stand-in for an empty one ("No notes added.", "—"), and
            // the empty notes cell nests one span in another — .find('span').text() across the
            // pair returned that placeholder twice over and wrote it into the textarea.
            var $shown = _this.find('span').first().clone();
            $shown.find('.text-muted').remove();
            var val = $shown.hasClass('text-muted') ? '' : $shown.text();

            if (_this.find('.form-control').prop('tagName').toLowerCase() == 'select') {
                // :contains('') matches every option, and the last one would win. An empty cell
                // belongs on the select's own empty option instead.
                if (val === '') {
                    _this.find('.form-control').val('');
                } else {
                    _this.find('option:contains(' + val + ')').prop('selected', true);
                }
            } else {
                _this.find('.form-control').val(val)
            }
        }
    });
    $(".fc-table-customer .fc-form-control").css({ 'color': '#f67925' });
    setTimeout(function() {
        $(".fc-table-customer .fc-form-control").css({ 'color': '' });
    }, 500);

    // Every field is back to what its row displays, so there is nothing left to save or undo.
    fcSyncProjectDetailsActions();
}

//----------------------------------------------------------------------------------

/**
 * The item count goes to the action bar at the foot of the list and the fence-style filter to the
 * heading, but both are rendered as part of the cart fragment — the server rebuilds that whole
 * block whenever the list changes, and the count has to come with it. So they are re-homed after
 * every render rather than written into either place, and the toolbar they arrive in goes with
 * them. Copy is static markup in the heading and stays where it is.
 */
function fcMountCartHeadingActions() {
    var $mount = $('.js-fc-cart-heading-actions').first();
    var $bar = $('.js-fc-cart-edit-bar__left').first();
    var $toolbar = $('#update_cart-list .fc-cart-list-toolbar').first();

    if (!$mount.length || !$bar.length || !$toolbar.length) {
        return;
    }

    // Clear what the last render left in each home before the new pair arrives.
    $('.js-fc-cart-count').not($toolbar.find('.js-fc-cart-count')).remove();
    $mount.children('.fc-cart-style-filter').remove();

    // Count ahead of Cancel on the left; filter after Copy on the right.
    $toolbar.children('.js-fc-cart-count').prependTo($bar);
    $toolbar.children('.fc-cart-style-filter').appendTo($mount);
    $toolbar.remove();

    // A render can land mid-edit (optional-item toggle), so re-hide the count if needed.
    $('.js-fc-cart-count').toggle(!$('.js-fc-cart-edit-bar').hasClass('is-editing'));
}

$(fcMountCartHeadingActions);

/**
 * Every cart action lives in the sticky bar at the foot of the column; the two states differ only
 * in which of them show. Copy goes with them: four buttons will not sit on one row on a phone, and
 * copying the list mid-edit would copy half-typed values.
 */
/**
 * Reset and Save Changes are dead until a quantity actually differs from the one the row loaded
 * with — there is nothing to undo or commit before that. They are anchors, so `disabled` does not
 * apply: the .disabled class is what Bootstrap paints and what the two click handlers check.
 */
function fcCartHasChanges() {
    var changed = false;

    $('.fc-table-items td').each(function() {
        var $field = $(this).find('.fc-form-field');
        if (!$field.length) {
            return;
        }
        // Same source fcResetItem restores from, so the two always agree on what "unchanged" means.
        if (String($field.val()) !== String($(this).closest('tr').data('original'))) {
            changed = true;
            return false;
        }
    });

    return changed;
}

function fcSyncCartEditActions() {
    var enable = $('.js-fc-cart-edit-bar').hasClass('is-editing') && fcCartHasChanges();

    // tabindex with it: the button still takes pointer events so the not-allowed cursor shows, and
    // a control nothing will act on should not be a tab stop either.
    $('.fc-reset-item, .js-fc-edit-item')
        .toggleClass('disabled', !enable)
        .attr('aria-disabled', enable ? null : 'true')
        .attr('tabindex', enable ? null : '-1');
}

function fcExitCartEditMode() {
    $('.js-fc-cart-edit-bar').removeClass('is-editing');
    $('.fc-table-items .md-qty, .fc-reset-item').add('.fc-cancel-item').hide();
    $('.js-fc-copy-cart-items, .js-fc-cart-count').show();
    $('.fc-item-value').removeClass('d-none');
    $('.js-fc-edit-item span').html('Edit Quantity');
    // Edit Quantity is always available; only its Save Changes state is gated.
    $('.js-fc-edit-item').removeClass('disabled').removeAttr('aria-disabled').removeAttr('tabindex');
    $('.fc-reset-item').addClass('disabled').attr({ 'aria-disabled': 'true', tabindex: '-1' });
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

    // Read before the fetch: the response replaces the select this value lives on.
    var keepStyle = $('.js-fc-cart-style-filter').first().val() || '';

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
                    // The fragment brought a fresh count back with it.
                    fcMountCartHeadingActions();
                    fcRestoreCartStyleFilter(keepStyle);
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

    // The bar's state decides the mode, not the button's label — the label is copy and has been
    // renamed once already; reading it back is how a rename silently turns Save into Edit.
    if (!$('.js-fc-cart-edit-bar').hasClass('is-editing')) {
        $('.js-fc-cart-edit-bar').addClass('is-editing');
        $('.fc-table-items .md-qty, .fc-reset-item').add('.fc-cancel-item').show();
        // The count goes with Copy: four things will not sit on one row on a phone, and the number
        // of lines in the list is not what you are editing.
        $('.js-fc-copy-cart-items, .js-fc-cart-count').hide();
        $('.fc-item-value').addClass('d-none');
        _this.find('span').html('Save Changes');
        // Nothing has been touched yet, so there is nothing to save or undo.
        fcSyncCartEditActions();
        return;
    }

    if (_this.hasClass('disabled')) {
        return;
    }

    $('form').submit();
}

//----------------------------------------------------------------------------------

_doc.on('click', '.fc-reset-item', fcResetItem);

function fcResetItem(e) {
    e?.preventDefault();

    // Cancel calls this directly and must always restore, however the button looks.
    if (this && $(this).hasClass('fc-reset-item') && $(this).hasClass('disabled')) {
        return;
    }

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

    // Every row is back to what it loaded with, so there is nothing left to save or undo.
    fcSyncCartEditActions();
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
    // .val() fires nothing, so the stepper has to report the change itself.
    fcSyncCartEditActions();
}

// The typed path: the stepper's own input is covered by inputQty above, but a row whose quantity
// is edited directly writes to .fc-form-field and would otherwise leave the two buttons dead.
_doc.on('input change', '.fc-table-items .fc-form-field', fcSyncCartEditActions);

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
                        // The fragment brought a fresh count back with it.
                        fcMountCartHeadingActions();

                        setTimeout(function() {
                            $(".fc-table-items .fc-form-control").css({ 'color': '' });
                            HELPER.removeSectionOverlay();
                            // Saving leaves edit mode the same way Cancel does: the bar drops back
                            // to Copy and Edit Quantity, and Reset goes dead with it. The table was
                            // just replaced, so this has to run after that HTML is in.
                            fcExitCartEditMode();

                            window.onbeforeunload = function() {}

                        }, 500);

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

/* "View Total Cost" needed no signal from here in the end: the bar is position: sticky at the
   foot of the cart pane, the same as the Edit Details bar, so CSS floats and settles it on its
   own. The body class and the --fc-view-total-bar-h measurement that drove the fixed version
   went with it. */
/**
 * Project plan diagrams: fade the edge a plan can still be scrolled towards, so a section that
 * runs wider than the panel says so instead of just ending at the border. The classes drive a
 * mask in CSS; both can be on at once when the plan is scrolled to neither end.
 * The diagrams are drawn after load and re-drawn on edit, so the list is watched rather than
 * bound once — bind() is idempotent, and the observer is coalesced to one pass per frame.
 */
/* The fence diagram scroll fade moved to shared/hscroll-fade.js: the planner's Step 3
   drawing uses the same .fc-project-plan-hscroll strip, and the copy here was gated to
   this page so Step 3 never faded. Both pages load the shared file from footer.php. */
//----------------------------------------------------------------------------------

/**
 * Project plan: mark each section header band .is-stuck while it is pinned, so the mobile rule
 * can run it edge to edge. Same sentinel trick p2.js uses for the SECTION n toolbars — CSS has no
 * way to ask whether a position: sticky element is currently stuck.
 */
(function initProjectPlanSectionBandStuck() {
    if (!document.body.classList.contains('fc-project-plan-page') || typeof IntersectionObserver === 'undefined') {
        return;
    }

    var bands = [
        '#project-details-section > .fencing-section__step-label',
        '#project-plans-section .fc-card > .fc-row-flex',
        '#update_cart-list > .row:first-of-type',
        '#update_stock-delivery .fencing-section__step-label',
        /* Second level: these pin under their section band. */
        '.fc-project-details .fc-card-header'
    ];

    var elements = [];
    bands.forEach(function(selector) {
        Array.prototype.push.apply(elements, document.querySelectorAll(selector));
    });

    elements.forEach(function(band) {
        if (!band || !band.parentNode) {
            return;
        }

        /* A span, not a div: several rules address these bands as .row:first-of-type, and a div
           inserted before one takes that position away from it — the Item List & Cart band lost
           its gutter the moment the sentinel went in. */
        var sentinel = document.createElement('span');
        sentinel.className = 'fc-section-band-sentinel';
        sentinel.setAttribute('aria-hidden', 'true');
        band.parentNode.insertBefore(sentinel, band);

        /* The sentinel sits at the band's own resting line, but the band pins at its sticky top —
           45 under a section band, 59 under the taller ones. Pulling the root's top edge down by
           that much makes the sentinel leave exactly when the band pins; with a plain 0 the class
           arrived that many pixels of scroll late and the band snapped wide after it had already
           stuck. */
        var stickTop = parseFloat(window.getComputedStyle(band).top);
        if (!isFinite(stickTop) || stickTop < 0) {
            stickTop = 0;
        }

        var io = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                /* Above the viewport, not merely out of it: without the top test the band reads
                   as stuck while its section is still below the fold, and you would see it full
                   width on the way down to it. */
                var stuck = !entry.isIntersecting && entry.boundingClientRect.top < stickTop;
                band.classList.toggle('is-stuck', stuck);
            });
        }, {
            root: null,
            rootMargin: (-stickTop) + 'px 0px 0px 0px',
            threshold: 0
        });

        io.observe(sentinel);
    });
})();
//----------------------------------------------------------------------------------

/**
 * Item List & Cart — show one fence style at a time. Rows are hidden with a class, not detached,
 * so their hidden qty inputs still post. Matches on data-fc-fence-style, not the label text.
 */
function fcApplyCartStyleFilter(style) {
    $('.fc-table-items .table-cart tbody tr[data-fc-fence-style]').each(function() {
        var row = $(this);
        row.toggleClass('fc-cart-row--filtered', style !== '' && row.attr('data-fc-fence-style') !== style);
    });

    fcSyncCartCountLabel();
}

_doc.on('change', '.js-fc-cart-style-filter', function() {
    fcApplyCartStyleFilter($(this).val() || '');
});

/**
 * A cart render replaces the filter along with its options, so a chosen style has to be restored
 * by hand afterward rather than surviving on its own.
 */
function fcRestoreCartStyleFilter(style) {
    if (!style) {
        return;
    }

    var $filter = $('.js-fc-cart-style-filter').first();
    if (!$filter.length) {
        return;
    }

    // Fall back to "All" if the rebuilt list no longer offers this style.
    var stillOffered = $filter.find('option').filter(function() {
        return this.value === style;
    }).length > 0;

    if (!stillOffered) {
        return;
    }

    $filter.val(style);
    fcApplyCartStyleFilter(style);
}

/**
 * Mirrors CartBuilderService::cartIncludedItemCount(): counts rows with qty > 0, so an unadded
 * optional item doesn't count. Reads the hidden cart[qty] input, not the displayed value.
 */
function fcCountedCartRows($rows) {
    return $rows.filter(function() {
        return parseInt($(this).find('input.input-qty').first().val() || '0', 10) > 0;
    }).length;
}

/**
 * "24 Items", or "6 of 24 Items" once a fence style is filtered. Recounted from the rows each
 * time, not parsed from the previous label.
 */
function fcSyncCartCountLabel() {
    var $count = $('.js-fc-cart-count').first();
    if (!$count.length) {
        return;
    }

    var $rows = $('.fc-table-items .table-cart tbody tr[data-fc-fence-style]');
    var total = fcCountedCartRows($rows);
    var style = $('.js-fc-cart-style-filter').first().val() || '';

    if (style === '') {
        $count.text(total + ' Items');
        return;
    }

    // Filter function, not an attribute selector: style is free text and would need escaping.
    var shown = fcCountedCartRows($rows.filter(function() {
        return $(this).attr('data-fc-fence-style') === style;
    }));

    $count.text(shown + ' of ' + total + ' Items');
}
