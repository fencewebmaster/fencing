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
 * pinned underneath it, so it doesn't cover Save Changes / Change QTY on mobile.
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

/**
 * What a section's capture needs, measured on the live section. The copy onCloneNode is handed is
 * not in the page, so everything measured on it reads 0: the width taken from it came out 0px, was
 * written onto the copy's strip, .dl-row and .fc-result, and left the drawing out of every PNG, PDF
 * and Download Plans page. The capture lays the strip out at full width, so the section grows by
 * what the strip has scrolled out of view and loses the strip's scrollbar; without that size passed
 * to modern-screenshot, the image was also cut off at the on-screen section's width.
 */
function fcMeasureProjectPlanSectionCapture(sectionEl) {
    var strip = sectionEl.querySelector('.fc-project-plan-hscroll');
    var hidden = strip ? Math.max(0, strip.scrollWidth - strip.clientWidth) : 0;
    var scrollbar = strip ? Math.max(0, strip.offsetHeight - strip.clientHeight) : 0;

    var head = sectionEl.querySelector('.fc-project-plan-section-head');

    return {
        width: Math.ceil(sectionEl.offsetWidth + hidden),
        height: Math.ceil(sectionEl.offsetHeight - scrollbar),
        stripWidth: strip ? Math.ceil(strip.scrollWidth) : 0,
        headHeight: head ? Math.ceil(head.offsetHeight) : 0
    };
}

function fcPrepareProjectPlanSectionScreenshotClone(cloned, size) {
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
        /* Copied at the on-screen section's width; its band has to span the whole run. */
        head.style.width = 'auto';
        head.classList.remove('fc-project-plan-section-head--stuck', 'fc-project-plan-section-head--dropdown-open');
    }

    var hscroll = cloned.querySelector('.fc-project-plan-hscroll');
    var planItem = cloned.querySelector('.plan-item');

    cloned.style.overflow = 'visible';
    cloned.style.maxWidth = 'none';

    if (hscroll) {
        /* The whole run rather than the stretch the strip shows, and without the edge fades that say
           there is more to scroll: on the full-width copy they would fade out the run's own ends. */
        hscroll.style.overflow = 'visible';
        hscroll.style.maxWidth = 'none';
        hscroll.style.setProperty('-webkit-mask-image', 'none');
        hscroll.style.setProperty('mask-image', 'none');
        if (size && size.stripWidth) {
            hscroll.style.width = size.stripWidth + 'px';
        }
    }
    if (planItem) {
        planItem.style.overflow = 'visible';
        planItem.style.width = 'auto';
    }

    var skeleton = cloned.querySelector('.fc-project-plan-skeleton');
    if (skeleton) {
        skeleton.style.display = 'none';
    }
}

function fcProjectPlanSectionScreenshotOptions(sectionEl, captureOpts) {
    captureOpts = captureOpts || {};
    var size = fcMeasureProjectPlanSectionCapture(sectionEl);

    return {
        /* Always 2x, not the screen's ratio: a 1x-display capture prints soft. */
        scale: 2,
        backgroundColor: '#ffffff',
        width: size.width,
        /* Headless (wrapped-PDF) captures shrink the canvas too, or the removed band would
           come back as white space under the run. */
        height: captureOpts.excludeHead ? size.height - size.headHeight : size.height,
        timeout: 60000,
        features: {
            /* The strip is captured from its start at full width, wherever it is scrolled to on screen. */
            restoreScrollPosition: false,
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
            if (captureOpts.excludeHead && el.classList.contains('fc-project-plan-section-head')) {
                return false;
            }
            return true;
        },
        onCloneNode: function(cloned) {
            fcPrepareProjectPlanSectionScreenshotClone(cloned, size);
            /* The section card's grey border would frame every wrapped row of the PDF. */
            if (captureOpts.excludeHead && cloned && cloned.classList && cloned.classList.contains('fc-project-plan-section')) {
                cloned.style.border = '0';
            }
        }
    };
}

/**
 * The section head alone, at its on-screen width: the wrapped PDF prints it as a constant-size
 * band, where the in-strip copy would shrink with the run's length. Resolves null on failure so
 * the pages still build, just headless.
 */
function fcCaptureProjectPlanSectionHead(sectionEl) {
    var head = sectionEl ? sectionEl.querySelector('.fc-project-plan-section-head') : null;
    if (!head || !window.modernScreenshot || typeof window.modernScreenshot.domToPng !== 'function') {
        return Promise.resolve(null);
    }

    /* A fixed capture width, as the cart pages do: the band's proportions come from the page it
       prints on, not from however narrow the customer's window happens to be. */
    var captureW = fcProjectPlanA4LandscapePageSizePx().width;

    return window.modernScreenshot
        .domToPng(head, {
            scale: 2,
            backgroundColor: '#ffffff',
            width: captureW,
            timeout: 60000,
            filter: function(node) {
                if (!node || node.nodeType !== 1) {
                    return true;
                }
                return !node.classList.contains('fc-project-plan-section-actions');
            },
            onCloneNode: function(cloned) {
                if (cloned && cloned.classList && cloned.classList.contains('fc-project-plan-section-head')) {
                    cloned.style.position = 'relative';
                    cloned.style.boxShadow = 'none';
                    cloned.style.width = captureW + 'px';
                    cloned.classList.remove('fc-project-plan-section-head--stuck', 'fc-project-plan-section-head--dropdown-open');
                    /* Pin the overall to the band's right edge. Absolutely, not text-align: the
                       clone keeps the on-screen row widths, which overflow the fixed captureW. */
                    var overall = cloned.querySelector('.fc-project-plan-section-head__overall');
                    if (overall) {
                        overall.style.position = 'absolute';
                        overall.style.top = '0';
                        overall.style.right = '16px';
                        overall.style.bottom = '0';
                        overall.style.width = 'auto';
                        /* The clone bakes the on-screen height in; clear it so top/bottom rule. */
                        overall.style.height = 'auto';
                        overall.style.blockSize = 'auto';
                        overall.style.display = 'flex';
                        overall.style.alignItems = 'center';
                        overall.style.justifyContent = 'flex-end';
                    }
                }
            }
        })
        .catch(function() {
            return null;
        });
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

function fcCaptureProjectPlanSection(sectionIndex, captureOpts) {
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
                        .domToPng(sectionEl, fcProjectPlanSectionScreenshotOptions(sectionEl, captureOpts))
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

/** Head band + headless run, captured back to back: the wrapped PDF pages draw from both. */
function fcCaptureProjectPlanSectionParts(sectionIndex) {
    return fcCaptureProjectPlanSection(sectionIndex, { excludeHead: true }).then(function(runUrl) {
        return fcCaptureProjectPlanSectionHead(fcGetProjectPlanSectionEl(sectionIndex)).then(function(headUrl) {
            return { run: runUrl, head: headUrl };
        });
    });
}

function fcDataUrlToBlob(dataUrl) {
    var comma = dataUrl.indexOf(',');
    var type = dataUrl.slice(5, comma).split(';')[0] || 'image/png';
    var binary = atob(dataUrl.slice(comma + 1));
    var bytes = new Uint8Array(binary.length);

    for (var i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return new Blob([bytes], { type: type });
}

function fcDownloadDataUrlPng(dataUrl, filename) {
    if (!dataUrl) {
        return;
    }

    /* An object URL on a link that is in the page: Firefox ignores a click on a download link that
       is not in the document, and Chrome fails a data: URL download past about 2MB, which a long
       section captured at 2x passes. */
    var url = dataUrl;
    try {
        url = URL.createObjectURL(fcDataUrlToBlob(dataUrl));
    } catch (err) {
        url = dataUrl;
    }

    var link = document.createElement('a');
    link.download = filename;
    link.href = url;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();

    setTimeout(function() {
        link.remove();
        if (url !== dataUrl) {
            URL.revokeObjectURL(url);
        }
    }, 1000);
}

/** A4 landscape on the same ~96dpi px grid the portrait cart pages use. */
function fcProjectPlanA4LandscapePageSizePx() {
    var a4 = fcProjectPlanA4PageSizePx();
    return { width: a4.height, height: a4.width };
}

/** The shared wrapped-page frame: A4 landscape with a content box Letter can also print at 100%. */
function fcWrappedPageGeometry() {
    var page = fcProjectPlanA4LandscapePageSizePx();
    var pxPerMm = page.width / 297;
    var contentW = 259.4 * pxPerMm;
    var contentH = 190 * pxPerMm;

    return {
        page: page,
        pxPerMm: pxPerMm,
        contentW: contentW,
        contentH: contentH,
        marginX: (page.width - contentW) / 2,
        marginY: (page.height - contentH) / 2,
        headerGap: 10,
        rowGap: 12,
        rowsPerPage: 4,
        overlapPaper: 5 * pxPerMm
    };
}

/* The band keeps its capture aspect; the cap stops a runaway head eating the rows. */
function fcWrappedHeaderHeight(geo, head) {
    return head ? Math.min(geo.contentW * head.height / head.width, 30 * geo.pxPerMm) : 0;
}

function fcWrappedRowsNeeded(geo, run, rowH) {
    var s = rowH / run.height;
    var rowRunW = geo.contentW / s;
    var stepRunW = rowRunW - geo.overlapPaper / s;

    return run.width <= rowRunW ? 1 : 1 + Math.ceil((run.width - rowRunW) / stepRunW);
}

/* One run row: the slice for [row] clipped out of the whole image, drawn at scale s = rowH/run.height.
   xShift centres a lone row that ends short of the content box. */
function fcWrappedDrawRow(doc, geo, run, rowH, row, y, alias, xShift) {
    var s = rowH / run.height;
    var stepRunW = geo.contentW / s - geo.overlapPaper / s;
    var startRun = row * stepRunW;
    var sliceW = Math.min(geo.contentW, (run.width - startRun) * s);
    var x = geo.marginX + (xShift || 0);

    doc.saveGraphicsState();
    doc.rect(x, y, sliceW, rowH, null);
    doc.clip();
    doc.discardPath();
    doc.addImage(run.dataUrl, 'PNG', x - startRun * s, y, run.width * s, rowH, alias, 'FAST');
    doc.restoreGraphicsState();
}

/* A one-row section sits centred in the content box; wrapped rows stay on the left margin. */
function fcWrappedRowXShift(geo, run, rowH, totalRows) {
    if (totalRows !== 1) {
        return 0;
    }
    return Math.max(0, (geo.contentW - run.width * (rowH / run.height)) / 2);
}

function fcWrappedDrawHeader(doc, geo, parts, headerH, y, alias) {
    doc.addImage(parts.head, 'PNG', geo.marginX, y, geo.contentW, headerH, alias ? alias + '-head' : undefined, 'FAST');
}

function fcWrappedSheetFooterText(label, sheet, sheets) {
    return (label ? label + ' — ' : '') +
        'sheet ' + sheet + ' of ' + sheets +
        (sheet < sheets ? ' (drawing continues on the next sheet)' : '');
}

function fcWrappedSheetFooter(doc, geo, label, sheet, sheets) {
    doc.setFontSize(9);
    doc.setTextColor(130);
    doc.text(
        fcWrappedSheetFooterText(label, sheet, sheets),
        geo.page.width - geo.marginX,
        geo.page.height - geo.marginY / 2,
        { align: 'right', baseline: 'middle' }
    );
}

/**
 * The run capture cropped to its drawn content (plus a small margin). Sections carry different
 * amounts of vertical padding — a gate taller than the fence grows the baseline reserve, raked
 * panels a top offset — and fitting those paddings into the shared row height printed one style
 * smaller than the next. Falls back to the original on any failure.
 */
function fcTrimRunCapture(dim) {
    try {
        var canvas = document.createElement('canvas');
        canvas.width = dim.width;
        canvas.height = dim.height;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(dim.image, 0, 0);
        var data = ctx.getImageData(0, 0, dim.width, dim.height).data;

        var minX = dim.width;
        var minY = dim.height;
        var maxX = -1;
        var maxY = -1;
        for (var y = 0; y < dim.height; y++) {
            for (var x = 0; x < dim.width; x++) {
                var i = (y * dim.width + x) * 4;
                /* Anything visibly darker than the white ground counts, the drawing grid included. */
                if (data[i + 3] > 16 && (data[i] < 250 || data[i + 1] < 250 || data[i + 2] < 250)) {
                    if (x < minX) { minX = x; }
                    if (x > maxX) { maxX = x; }
                    if (y < minY) { minY = y; }
                    if (y > maxY) { maxY = y; }
                }
            }
        }
        if (maxX < 0 || maxX - minX < 40 || maxY - minY < 40) {
            return dim;
        }

        var pad = 8;
        minX = Math.max(0, minX - pad);
        minY = Math.max(0, minY - pad);
        maxX = Math.min(dim.width - 1, maxX + pad);
        maxY = Math.min(dim.height - 1, maxY + pad);

        var out = document.createElement('canvas');
        out.width = maxX - minX + 1;
        out.height = maxY - minY + 1;
        out.getContext('2d').drawImage(dim.image, -minX, -minY);

        return { dataUrl: out.toDataURL('image/png'), width: out.width, height: out.height };
    } catch (e) {
        return dim;
    }
}

function fcLoadSectionPartsDimensions(parts) {
    var headLoad = parts.head
        ? fcLoadProjectPlanCaptureDimensions(parts.head).catch(function() { return null; })
        : Promise.resolve(null);

    return Promise.all([fcLoadProjectPlanCaptureDimensions(parts.run), headLoad]).then(function(loaded) {
        return { run: fcTrimRunCapture(loaded[0]), head: loaded[1] };
    });
}

/**
 * One section on standard sheets, wrapped like text: a constant-size header band, then the run
 * sliced into up to four full-width rows, reading left to right down the page and continuing on
 * the next sheet (header repeated) when four rows are not enough. A section needing fewer rows
 * grows to fill the page instead of leaving it blank, capped by the width fit. Rows overlap 5mm
 * at each wrap so a post on the break prints whole.
 * opts: useCurrentPage draws the first sheet on the doc's fresh page; label feeds the footer;
 * alias embeds each PNG once however many rows reuse it.
 */
/* One section's sheet layout, shared by the PDF pager and the PNG composer so the two
   downloads cannot drift. */
function fcWrappedSectionLayout(dim) {
    var run = dim.run;
    var geo = fcWrappedPageGeometry();
    var headerH = fcWrappedHeaderHeight(geo, dim.head);
    var headerGap = dim.head ? geo.headerGap : 0;
    var rowsArea = geo.contentH - headerH - headerGap;
    var rowHFull = (rowsArea - (geo.rowsPerPage - 1) * geo.rowGap) / geo.rowsPerPage;

    var rowH = rowHFull;
    var totalRows = fcWrappedRowsNeeded(geo, run, rowHFull);
    if (totalRows < geo.rowsPerPage) {
        // Grow-to-fill: the section's own rows share the whole area, unless the width fit
        // binds first (a lone short row keeps the run's aspect rather than overshooting).
        // The sharpness cap stops a small section (a lone gate) from being blown up past its
        // pixels: half the 2x capture is its on-screen size, about 190dpi on paper.
        var n = totalRows;
        var grownH = (rowsArea - (n - 1) * geo.rowGap) / n;
        var widthFitH = ((n * geo.contentW - (n - 1) * geo.overlapPaper) / run.width) * run.height;
        var maxSharpH = run.height * 0.5;
        rowH = Math.min(grownH, widthFitH, Math.max(maxSharpH, rowHFull));
        totalRows = fcWrappedRowsNeeded(geo, run, rowH);
        if (totalRows > n) {
            rowH = rowHFull;
            totalRows = fcWrappedRowsNeeded(geo, run, rowHFull);
        }
    }

    return {
        geo: geo,
        headerH: headerH,
        headerGap: headerGap,
        rowsArea: rowsArea,
        rowH: rowH,
        totalRows: totalRows,
        sheets: Math.ceil(totalRows / geo.rowsPerPage),
        xShift: fcWrappedRowXShift(geo, run, rowH, totalRows)
    };
}

function fcAppendSectionWrappedPages(doc, parts, opts) {
    opts = opts || {};

    return fcLoadSectionPartsDimensions(parts).then(function(dim) {
        var run = dim.run;
        var head = dim.head;
        var L = fcWrappedSectionLayout(dim);
        var geo = L.geo;

        for (var p = 0; p < L.sheets; p++) {
            if (p > 0 || !opts.useCurrentPage) {
                doc.addPage([geo.page.width, geo.page.height], 'landscape');
            }

            var y = geo.marginY;
            if (head) {
                fcWrappedDrawHeader(doc, geo, parts, L.headerH, y, opts.alias);
                y += L.headerH + L.headerGap;
            }

            var first = p * geo.rowsPerPage;
            var last = Math.min(L.totalRows, first + geo.rowsPerPage);
            if (L.sheets === 1 && L.totalRows < geo.rowsPerPage) {
                /* Grown rows sit centred in what is left; full sheets stack from the top. */
                var block = (last - first) * L.rowH + (last - first - 1) * geo.rowGap;
                y += Math.max(0, (L.rowsArea - block) / 2);
            }

            for (var r = first; r < last; r++) {
                fcWrappedDrawRow(doc, geo, run, L.rowH, r, y, opts.alias, L.xShift);
                y += L.rowH + geo.rowGap;
            }

            if (L.sheets > 1) {
                fcWrappedSheetFooter(doc, geo, opts.label, p + 1, L.sheets);
            }
        }
    });
}

/**
 * Build an A4-landscape wrapped-row PDF from a section's head + run captures.
 */
function fcDownloadSectionPartsPdf(parts, filename, label) {
    if (!parts || !parts.run) {
        return Promise.reject(new Error('No image data'));
    }

    if (!window.jspdf || !window.jspdf.jsPDF) {
        return Promise.reject(new Error('jsPDF not loaded'));
    }

    window.jsPDF = window.jspdf.jsPDF;

    var page = fcProjectPlanA4LandscapePageSizePx();
    var doc = new jsPDF({
        orientation: 'landscape',
        unit: 'px',
        format: [page.width, page.height],
        hotfixes: ['px_scaling']
    });

    return fcAppendSectionWrappedPages(doc, parts, {
        useCurrentPage: true,
        label: label || '',
        alias: 'fc-section-pdf'
    }).then(function() {
        doc.save(filename);
    });
}

/**
 * The per-section PDF sheets drawn onto one canvas, stacked top to bottom with a thin grey
 * divider: the PNG download shows exactly what the PDF prints, in a single image.
 */
function fcComposeSectionWrappedPng(parts, label) {
    return fcLoadSectionPartsDimensions(parts).then(function(dim) {
        return new Promise(function(resolve, reject) {
            var runImg = new Image();
            runImg.onload = function() {
                try {
                    resolve(draw(runImg));
                } catch (err) {
                    reject(err);
                }
            };
            runImg.onerror = function() {
                reject(new Error('Could not read captured image'));
            };
            runImg.src = dim.run.dataUrl;

            function draw(img) {
                var L = fcWrappedSectionLayout(dim);
                var geo = L.geo;
                var scale = 2;
                var divider = 8;
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(geo.page.width * scale);
                canvas.height = Math.round((geo.page.height * L.sheets + divider * (L.sheets - 1)) * scale);
                var ctx = canvas.getContext('2d');
                ctx.scale(scale, scale);
                ctx.fillStyle = '#d9d9d9';
                ctx.fillRect(0, 0, geo.page.width, canvas.height / scale);

                for (var p = 0; p < L.sheets; p++) {
                    var top = p * (geo.page.height + divider);
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, top, geo.page.width, geo.page.height);

                    var y = top + geo.marginY;
                    if (dim.head && dim.head.image) {
                        ctx.drawImage(dim.head.image, geo.marginX, y, geo.contentW, L.headerH);
                        y += L.headerH + L.headerGap;
                    }

                    var first = p * geo.rowsPerPage;
                    var last = Math.min(L.totalRows, first + geo.rowsPerPage);
                    if (L.sheets === 1 && L.totalRows < geo.rowsPerPage) {
                        var block = (last - first) * L.rowH + (last - first - 1) * geo.rowGap;
                        y += Math.max(0, (L.rowsArea - block) / 2);
                    }

                    var s = L.rowH / dim.run.height;
                    var stepRunW = geo.contentW / s - geo.overlapPaper / s;
                    for (var r = first; r < last; r++) {
                        var startRun = r * stepRunW;
                        var sliceW = Math.min(geo.contentW, (dim.run.width - startRun) * s);
                        var x = geo.marginX + L.xShift;
                        ctx.save();
                        ctx.beginPath();
                        ctx.rect(x, y, sliceW, L.rowH);
                        ctx.clip();
                        ctx.drawImage(img, x - startRun * s, y, dim.run.width * s, L.rowH);
                        ctx.restore();
                        y += L.rowH + geo.rowGap;
                    }

                    if (L.sheets > 1) {
                        /* 9pt in the PDF is 12px here. */
                        ctx.fillStyle = 'rgb(130, 130, 130)';
                        ctx.font = '12px Helvetica, Arial, sans-serif';
                        ctx.textAlign = 'right';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(
                            fcWrappedSheetFooterText(label, p + 1, L.sheets),
                            geo.page.width - geo.marginX,
                            top + geo.page.height - geo.marginY / 2
                        );
                    }
                }

                return canvas.toDataURL('image/png');
            }
        });
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

    // Same head + wrapped-rows sheets as the PDF, composed into one image.
    fcCaptureProjectPlanSectionParts(sectionIndex).then(function(parts) {
        return fcComposeSectionWrappedPng(parts, 'Section ' + (sectionIndex + 1)).then(function(dataUrl) {
            fcDownloadDataUrlPng(dataUrl, fcProjectPlanSectionFilename(sectionIndex, 'png'));
        });
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

    fcCaptureProjectPlanSectionParts(sectionIndex).then(function(parts) {
        return fcDownloadSectionPartsPdf(parts, fcProjectPlanSectionFilename(sectionIndex, 'pdf'), 'Section ' + (sectionIndex + 1));
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
                height: probe.height,
                image: probe
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

    cloned.querySelectorAll(
        '.fc-cart-toolbar, .js-fc-cart-toolbar, .fc-view-total-cost-bar, .fc-cart-edit-bar, ' +
        '.fc-cart-list-toolbar, .fc-cart-heading-actions, .fc-cart-optional-actions, ' +
        '.fc-cart-qty-gauge, .fc-cancel-item, .fc-update-item, .fc-reset-item'
    ).forEach(function(el) {
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

    /* Baked on-screen widths and heights keep the table from reflowing to the page width, so
       it printed cut off at the right: clear the table and every wrapper up to the root. */
    var tableEl = cloned.querySelector('.table-cart');
    for (var wrapEl = tableEl; wrapEl && wrapEl !== cloned; wrapEl = wrapEl.parentElement) {
        wrapEl.style.width = '100%';
        wrapEl.style.inlineSize = '100%';
        wrapEl.style.maxWidth = '100%';
        wrapEl.style.maxInlineSize = '100%';
        wrapEl.style.minWidth = '0';
        wrapEl.style.minInlineSize = '0';
        wrapEl.style.height = 'auto';
        wrapEl.style.blockSize = 'auto';
        wrapEl.style.overflow = 'visible';
    }
    cloned.querySelectorAll('.table-cart *').forEach(function(el) {
        if (el.tagName === 'IMG') {
            return;
        }
        el.style.width = 'auto';
        el.style.inlineSize = 'auto';
        el.style.height = 'auto';
        el.style.blockSize = 'auto';
        el.style.minWidth = '0';
        el.style.minInlineSize = '0';
        el.style.maxWidth = '100%';
        el.style.maxInlineSize = '100%';
        el.style.overflowWrap = 'break-word';
    });

    /* The sticky column head bakes its on-screen offset in and printed mid-row: back to flow.
       Its dark band prints as a slab of ink, so it goes white with black text on paper. */
    cloned.querySelectorAll('.table-cart thead, .table-cart thead tr, .table-cart thead th').forEach(function(el) {
        el.style.position = 'static';
        el.style.top = 'auto';
        el.style.insetBlockStart = 'auto';
        el.style.boxShadow = 'none';
        el.style.background = '#ffffff';
        el.style.color = '#212529';
        /* The clone bakes -webkit-text-fill-color, which outranks color when painting. */
        el.style.webkitTextFillColor = '#212529';
    });

    /* Keep "In-Stock" / "Low-Stock" to one line in the narrowed stock column. */
    cloned.querySelectorAll('.table-cart .fw-boldx').forEach(function(el) {
        el.style.whiteSpace = 'nowrap';
    });

    /* Fonts measure a hair wider in the capture than the baked heading box: one line, unclipped. */
    cloned.querySelectorAll('.step-label').forEach(function(el) {
        el.style.whiteSpace = 'nowrap';
        el.style.width = 'auto';
        el.style.inlineSize = 'auto';
        el.style.maxWidth = 'none';
        el.style.maxInlineSize = 'none';
    });
}

function fcProjectPlanCartScreenshotOptions() {
    return {
        /* Always 2x, not the screen's ratio: a 1x-display capture prints soft. */
        scale: 2,
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
            if (el.classList.contains('fc-cart-list-toolbar')) {
                return false;
            }
            if (el.classList.contains('fc-cart-optional-actions')) {
                return false;
            }
            if (el.classList.contains('fc-cart-qty-gauge')) {
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

                    var options = fcProjectPlanCartScreenshotOptions();
                    /* The capture canvas takes the cart's on-screen height, but the reflowed
                       table is usually taller: stage a copy at the page width and use its height.
                       The pager trims whatever blank is left at the foot. */
                    try {
                        var stage = document.createElement('div');
                        stage.style.cssText = 'position:absolute;left:-100000px;top:0;width:' + fcProjectPlanCartCaptureWidthPx() + 'px;';
                        var staged = cartEl.cloneNode(true);
                        fcPrepareProjectPlanCartScreenshotClone(staged);
                        stage.appendChild(staged);
                        document.body.appendChild(stage);
                        var stagedH = Math.ceil(staged.getBoundingClientRect().height);
                        stage.remove();
                        if (stagedH > 0) {
                            options.height = stagedH + 40;
                        }
                    } catch (stageError) {
                        /* The live height stays the fallback. */
                    }

                    window.modernScreenshot
                        .domToPng(cartEl, options)
                        .then(resolve)
                        .catch(reject);
                });
            });
        }

        function whenFontsReady() {
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(startCapture).catch(startCapture);
            } else {
                startCapture();
            }
        }

        // Thumbnails load lazily; rows never scrolled to would come out as empty frames.
        if (window.FCLazyImages) {
            window.FCLazyImages.loadAll(document.getElementById('update_cart-list')).then(whenFontsReady, whenFontsReady);
        } else {
            whenFontsReady();
        }
    });
}

/**
 * Where the item list's A4 pages end, in rows of the captured image. At a fixed height a page cut
 * through whatever row of the table landed there. Each page now ends just under the rule beneath a
 * row of the table, the nearest one found walking up through the page's last quarter, so the row
 * keeps its own bottom border and the next page opens on a whole row. Failing a rule it ends on an
 * empty line of pixels, and failing that at full height. An empty line alone was not enough: the
 * gap between a wrapped description's lines is one too, and a row broke across two pages there.
 * The blank run at the foot of the capture is left off too: the list is captured at the height it
 * has in its narrower column on screen, and that run was printing as an empty last page.
 */
function fcFindCartListPageBreaks(image, pageHeight) {
    /* Squeezed across but not down, so a 1px rule is still a whole line of its own colour. */
    var width = Math.min(image.width, 400);
    var height = image.height;
    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');

    canvas.width = width;
    canvas.height = height;
    context.drawImage(image, 0, 0, width, height);

    var pixels = context.getImageData(0, 0, width, height).data;
    /* Inside the card's own left and right borders. */
    var from = Math.floor(width * 0.06);
    var to = Math.ceil(width * 0.94);

    /* Per line of pixels: how many are not near-white, how many of those are dark enough to be
       text, and how many are the light grey of a table rule. */
    function tally(row) {
        var counts = { marked: 0, dark: 0, grey: 0 };

        for (var x = from; x < to; x++) {
            var i = (row * width + x) * 4;
            var low = Math.min(pixels[i], pixels[i + 1], pixels[i + 2]);

            if (low < 235) {
                counts.marked++;
                if (low < 150) {
                    counts.dark++;
                } else if (low > 200) {
                    counts.grey++;
                }
            }
        }
        return counts;
    }

    /* A few marked pixels are allowed on an empty line: the table's own side borders cross every
       line, text or not. */
    function plain(row) {
        return tally(row).marked <= 3;
    }

    function rule(row) {
        var counts = tally(row);
        return counts.dark <= 3 && counts.grey >= (to - from) * 0.3;
    }

    var last = height - 1;
    while (last > 0 && plain(last)) {
        last--;
    }

    var end = Math.min(height, last + 2);
    var breaks = [0];
    var top = 0;

    while (end - top > pageHeight) {
        var full = Math.floor(top + pageHeight);
        var floor = Math.ceil(top + pageHeight * 0.75);
        var cut = 0;
        var row;

        for (row = full - 1; row >= floor && !cut; row--) {
            if (rule(row)) {
                cut = row + 1;
            }
        }
        /* A rule two lines thick (a 1px border captured at 2x) stays whole on the page it ends. */
        while (cut && cut < full && rule(cut)) {
            cut++;
        }
        for (row = full; row >= floor && !cut; row--) {
            if (plain(row)) {
                cut = row;
            }
        }
        if (cut <= top) {
            cut = full;
        }

        breaks.push(cut);
        top = cut;
    }

    breaks.push(end);
    return breaks;
}

function fcAppendCartListA4Pages(doc, cartDataUrl) {
    return fcLoadProjectPlanCaptureDimensions(cartDataUrl).then(function(dim) {
        var a4 = fcProjectPlanA4PageSizePx();
        var margin = 24;
        var pageW = a4.width;
        var pageH = a4.height;
        var contentW = pageW - margin * 2;
        var contentH = pageH - margin * 2;
        var ratio = contentW / dim.width;
        var breaks = fcFindCartListPageBreaks(dim.image, contentH / ratio);

        for (var i = 1; i < breaks.length; i++) {
            var top = breaks[i - 1];

            doc.addPage([pageW, pageH], 'portrait');
            /* Each page prints its own slice only: unclipped, the image ran on into the bottom
               margin, and the next page's top margin printed the same strip again. */
            doc.saveGraphicsState();
            doc.rect(margin, margin, contentW, (breaks[i] - top) * ratio, null);
            doc.clip();
            doc.discardPath();
            doc.addImage(dim.dataUrl, 'PNG', margin, margin - top * ratio, contentW, dim.height * ratio, 'fc-project-plan-cart', 'FAST');
            doc.restoreGraphicsState();
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

    var page = fcProjectPlanA4LandscapePageSizePx();
    var doc = new jsPDF({
        orientation: 'landscape',
        unit: 'px',
        format: [page.width, page.height],
        hotfixes: ['px_scaling']
    });

    // Each section prints exactly as its own "Download PDF" does — its own sheet(s), rows
    // grown to fill the page. The first one takes the constructor's page so the doc doesn't
    // open on a blank sheet.
    return sectionCaptures.reduce(function(chain, parts, index) {
        return chain.then(function() {
            return fcAppendSectionWrappedPages(doc, parts, {
                useCurrentPage: index === 0,
                label: 'Section ' + (index + 1),
                alias: 'fc-section-' + index
            });
        });
    }, Promise.resolve()).then(function() {
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
            return fcCaptureProjectPlanSectionParts(sectionIndex).then(function(parts) {
                captures.push(parts);
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
    var $icon = $button.find('i').first();

    if (isBusy) {
        /* The icon's own classes are kept and put back, me-2 with them: rebuilding them from the
           icon name alone dropped the gap before "Download Plans" after the first download. */
        if (!$icon.data('fcPrevClass')) {
            $icon.data('fcPrevClass', $icon.attr('class'));
        }
        $icon.attr('class', 'fas fa-spinner fa-spin me-2');
        $button.attr('disabled', true).find('span').html('Preparing Plans...');
        fcProjectPlanDownloadToast(true);
        return;
    }

    $icon.attr('class', $icon.data('fcPrevClass') || 'fa-solid fa-download me-2');
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
                /* An item list that will not capture costs the plan its item list pages, not the
                   whole download. */
                return fcCaptureProjectPlanCartList().catch(function() {
                    return null;
                }).then(function(cartCapture) {
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

/**
 * Save Changes on, whatever fcSyncCartEditActions just decided.
 *
 * That function gates Save and Reset together on "does any quantity differ from the one its row
 * loaded with", which is the right question for Reset and the wrong one for Save once an action
 * has already been taken: a reset is itself something the user may want to commit, and a cart
 * toggle changes the cart without touching a single quantity field. Reset is left alone — after
 * either of those there is genuinely nothing left to undo.
 */
function fcEnableCartSaveButton() {
    if (!$('.js-fc-cart-edit-bar').hasClass('is-editing')) {
        return;
    }
    $('.js-fc-edit-item').removeClass('disabled').removeAttr('aria-disabled').removeAttr('tabindex');
}

/**
 * Put freshly rendered rows into edit dress.
 *
 * The cart fragment always comes back in its rest state — steppers hidden, values shown — because
 * the server has no idea the bar is mid-edit. The bar itself sits outside .fc-table-items and so
 * survives the swap, which is what left the two disagreeing: Cancel and Save on screen above rows
 * that had gone back to plain values.
 */
function fcApplyCartRowsEditMode() {
    $('.fc-table-items .md-qty').show();
    $('.fc-table-items .fc-item-value').addClass('d-none');
}

function fcExitCartEditMode() {
    $('.js-fc-cart-edit-bar').removeClass('is-editing');
    $('.fc-table-items .md-qty, .fc-reset-item').add('.fc-cancel-item').hide();
    $('.js-fc-copy-cart-items, .js-fc-cart-count').show();
    $('.fc-item-value').removeClass('d-none');
    $('.js-fc-edit-item span').html('Change QTY');
    // Change QTY is always available; only its Save Changes state is gated.
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
                    // Adding or removing a line is an edit like any other, so the mode holds. The
                    // new rows carry the quantities the toggle just wrote, so the actions are
                    // re-read against those before Save is put back on.
                    if ($('.js-fc-cart-edit-bar').hasClass('is-editing')) {
                        fcApplyCartRowsEditMode();
                        fcSyncCartEditActions();
                        fcEnableCartSaveButton();
                    }
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
        $('.fc-reset-item').add('.fc-cancel-item').show();
        fcApplyCartRowsEditMode();
        // The count goes with Copy: four things will not sit on one row on a phone, and the number
        // of lines in the list is not what you are editing.
        $('.js-fc-copy-cart-items, .js-fc-cart-count').hide();
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

    // Every row is back to what it loaded with, so there is nothing left to undo — but the reset
    // is still something to commit, so Save stays live where fcSyncCartEditActions would drop it.
    fcSyncCartEditActions();
    fcEnableCartSaveButton();
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
                            // to Copy and Change QTY, and Reset goes dead with it. The table was
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
