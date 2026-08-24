/**
 * FC Admin — Fence Styles list + edit (writable/fences/*.php).
 */
(function (global) {
    'use strict';

    var API_LIST = fcApiUrl('fenceStyles', 'action=list');
    var API_GET = fcApiUrl('fenceStyles', 'action=get');
    var API_SAVE = fcApiUrl('fenceStyles', 'action=save');
    var API_BULK_EXPORT = fcApiUrl('fenceStyles', 'action=bulk-export');
    var API_BULK_STATUS = fcApiUrl('fenceStyles', 'action=bulk-status');
    var API_BULK_IMPORT = fcApiUrl('fenceStyles', 'action=bulk-import');
    var API_FENCE_COLORS = fcApiUrl('settings', 'action=fence-colors');
    var TOAST_SAVE = 'fc-fence-style-save';

    var escapeHtml = global.FC.util.escapeHtml;

    function editRoute(slug) {
        return 'products/fence-styles/edit/' + encodeURIComponent(slug);
    }

    function navigate(route) {
        if (typeof global.fcAdminNavigate === 'function') {
            global.fcAdminNavigate(route);
            return;
        }
        global.location.href = fcAdminUrl(route);
    }

    function toast(kind, message, toastId) {
        global.FC.util.toast(kind, message, toastId);
    }

    function renderLoading(message) {
        return (
            '<div class="flex flex-col items-center justify-center gap-3 p-12 text-slate-500">' +
            '<i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-500" aria-hidden="true"></i>' +
            '<p class="text-sm">' +
            escapeHtml(message || 'Loading…') +
            '</p>' +
            '</div>'
        );
    }

    function renderError(message) {
        return (
            '<div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">' +
            '<p class="font-semibold">Could not load fence styles</p>' +
            '<p class="mt-1 text-sm">' +
            escapeHtml(message) +
            '</p>' +
            '</div>'
        );
    }

    function renderCardControls(style, canEdit) {
        if (!canEdit) {
            return '';
        }

        return (
            '<div class="fc-fs-card-controls">' +
            '<label class="fc-fs-card-check" title="Select ' + escapeHtml(style.title) + ' (Ctrl+Click a style to select it)">' +
            '<input type="checkbox" class="fc-fs-card-check-input" data-fc-fs-card-select data-slug="' +
            escapeHtml(style.slug) +
            '" aria-label="Select ' + escapeHtml(style.title) + '">' +
            '<span class="fc-fs-card-check-ui" aria-hidden="true">' +
            '<i class="fa-solid fa-check fc-fs-card-check-icon" aria-hidden="true"></i>' +
            '</span>' +
            '</label>' +
            '</div>'
        );
    }

    function renderStyleCard(style, canEdit) {
        var liveBadge = style.live
            ? '<span class="fc-admin-fence-style-badge fc-admin-fence-style-badge--live">Live</span>'
            : '<span class="fc-admin-fence-style-badge fc-admin-fence-style-badge--draft">Draft</span>';

        var imageSrc = style.imageUrl || style.image || '';
        var imageHtml = imageSrc
            ? '<img src="' + escapeHtml(imageSrc) + '" alt="" loading="lazy" decoding="async">'
            : '<span class="fc-admin-fence-style-img-placeholder" aria-hidden="true">' +
              '<i class="fa-solid fa-image text-2xl text-slate-300"></i></span>';

        var inner =
            '<div>' +
            '<div class="fencing-style-img">' +
            imageHtml +
            liveBadge +
            '</div>' +
            '<div class="fencing-style-title fw-bold">' +
            escapeHtml(style.title) +
            '</div>' +
            '</div>';

        var controls = renderCardControls(style, canEdit);

        if (!canEdit) {
            return (
                '<div class="fc-admin-fence-style-card">' +
                '<div class="fencing-style-item fc-admin-fence-style-item" aria-label="' +
                escapeHtml(style.title) +
                '">' +
                inner +
                '</div>' +
                controls +
                '</div>'
            );
        }

        var route = editRoute(style.slug);

        return (
            '<div class="fc-admin-fence-style-card">' +
            '<a href="' +
            escapeHtml(fcAdminUrl(route)) +
            '" class="fencing-style-item fc-admin-fence-style-item fc-admin-fence-style-link" data-route="' +
            escapeHtml(route) +
            '" data-title="Edit ' +
            escapeHtml(style.title) +
            '" aria-label="Edit ' +
            escapeHtml(style.title) +
            '">' +
            inner +
            '</a>' +
            controls +
            '</div>'
        );
    }

    function toggleCardSelection(link) {
        var card = link.closest('.fc-admin-fence-style-card');
        var input = card ? card.querySelector('[data-fc-fs-card-select]') : null;
        if (!input) {
            return;
        }
        input.checked = !input.checked;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function bindListLinks(container) {
        container.querySelectorAll('.fc-admin-fence-style-link[data-route]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    toggleCardSelection(link);
                    return;
                }
                e.preventDefault();
                navigate(link.getAttribute('data-route'));
            });
        });
    }

    function closeAllCardGearMenus(container) {
        container.querySelectorAll('[data-fc-fs-card-gear].is-open').forEach(function (gear) {
            var panel = gear.querySelector('.fc-fs-card-gear__panel');
            var toggle = gear.querySelector('[data-fc-fs-card-gear-toggle]');
            if (panel) {
                panel.hidden = true;
                panel.style.position = '';
                panel.style.left = '';
                panel.style.top = '';
                panel.style.right = '';
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            gear.classList.remove('is-open');
        });
    }

    function positionCardGearMenu(gear) {
        var toggle = gear.querySelector('[data-fc-fs-card-gear-toggle]');
        var panel = gear.querySelector('.fc-fs-card-gear__panel');
        if (!toggle || !panel || panel.hidden) {
            return;
        }
        var rect = toggle.getBoundingClientRect();
        var gap = 6;
        panel.style.position = 'fixed';
        panel.style.left = Math.round(rect.left) + 'px';
        panel.style.top = Math.round(rect.bottom + gap) + 'px';
        panel.style.right = 'auto';

        var panelRect = panel.getBoundingClientRect();
        var viewportWidth = global.innerWidth || document.documentElement.clientWidth || 0;
        var viewportHeight = global.innerHeight || document.documentElement.clientHeight || 0;

        if (panelRect.right > viewportWidth - 8) {
            panel.style.left = Math.max(8, viewportWidth - panelRect.width - 8) + 'px';
        }
        if (panelRect.bottom > viewportHeight - 8) {
            var aboveTop = rect.top - gap - panelRect.height;
            if (aboveTop >= 8) {
                panel.style.top = aboveTop + 'px';
            }
        }
    }

    function bindCardGearMenus(container) {
        if (!container || container.dataset.fcFsGearBound === '1') {
            return;
        }
        container.dataset.fcFsGearBound = '1';

        container.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-fc-fs-card-gear-toggle]');
            var gear = event.target.closest('[data-fc-fs-card-gear]');

            if (toggle && gear) {
                event.preventDefault();
                event.stopPropagation();
                var panel = gear.querySelector('.fc-fs-card-gear__panel');
                var isOpen = panel && !panel.hidden;
                closeAllCardGearMenus(container);
                if (panel && !isOpen) {
                    panel.hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                    gear.classList.add('is-open');
                    positionCardGearMenu(gear);
                }
                return;
            }

            if (gear && event.target.closest('.fc-fs-card-gear__panel')) {
                event.stopPropagation();
            }
        });

        document.addEventListener('click', function () {
            closeAllCardGearMenus(container);
        });
        global.addEventListener('resize', function () {
            container.querySelectorAll('[data-fc-fs-card-gear].is-open').forEach(positionCardGearMenu);
        });
        global.addEventListener(
            'scroll',
            function () {
                container.querySelectorAll('[data-fc-fs-card-gear].is-open').forEach(positionCardGearMenu);
            },
            true
        );
    }

    var BULK_TOAST_ID = 'fc-fence-style-bulk';
    var bulkSelection = new Set();

    function updateBulkBar(container) {
        var count = bulkSelection.size;
        var actionEl = container.querySelector('[data-fc-fs-bulk-action]');
        var applyBtn = container.querySelector('[data-fc-fs-bulk-apply]');
        var countEl = container.querySelector('[data-fc-fs-bulk-count]');

        if (actionEl) {
            actionEl.disabled = count === 0;
            if (count === 0) {
                actionEl.value = '';
            }
        }
        if (applyBtn) {
            applyBtn.disabled = count === 0 || !(actionEl && actionEl.value);
        }
        if (countEl) {
            countEl.hidden = count === 0;
            countEl.textContent = count + ' selected';
        }
    }

    function confirmBulkAction(action, count) {
        var confirmFn =
            global.FcAdminModal && typeof global.FcAdminModal.confirm === 'function'
                ? global.FcAdminModal.confirm.bind(global.FcAdminModal)
                : null;
        var noun = count === 1 ? 'fence style' : 'fence styles';
        var title;
        var message;
        var confirmLabel;

        if (action === 'export') {
            title = 'Export selected fence styles';
            message = 'Export ' + count + ' selected ' + noun + ' as JSON?';
            confirmLabel = 'Export JSON';
        } else if (action === 'mark-live') {
            title = 'Mark as Live';
            message = 'Mark ' + count + ' selected ' + noun + ' as Live?';
            confirmLabel = 'Mark as Live';
        } else {
            title = 'Mark as Draft';
            message = 'Mark ' + count + ' selected ' + noun + ' as Draft?';
            confirmLabel = 'Mark as Draft';
        }

        if (confirmFn) {
            return confirmFn({ title: title, message: message, confirmLabel: confirmLabel, variant: 'info' });
        }
        return Promise.resolve(global.confirm(message));
    }

    function downloadStylesExport(slugs) {
        if (!slugs.length) {
            return;
        }
        var link = document.createElement('a');
        link.href = API_BULK_EXPORT + '&slugs=' + encodeURIComponent(slugs.join(','));
        link.download = 'fence-styles-export.json';
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    function bulkExportSelected() {
        downloadStylesExport(Array.from(bulkSelection));
    }

    function bulkExportAllStyles(container) {
        var slugs = Array.from(container.querySelectorAll('[data-fc-fs-card-select]'))
            .map(function (input) {
                return input.getAttribute('data-slug') || '';
            })
            .filter(Boolean);

        if (!slugs.length) {
            toast('error', 'No fence styles to export.', BULK_TOAST_ID);
            return;
        }

        downloadStylesExport(slugs);
    }

    function bulkSetStatus(live, csrf) {
        if (!bulkSelection.size) {
            return;
        }
        if (!csrf) {
            toast('error', 'Missing security token. Refresh and try again.', BULK_TOAST_ID);
            return;
        }

        var slugs = Array.from(bulkSelection);
        toast(
            'saving',
            'Updating ' + slugs.length + ' fence style' + (slugs.length === 1 ? '' : 's') + '…',
            BULK_TOAST_ID
        );

        fetch(API_BULK_STATUS, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ slugs: slugs, live: live, csrf: csrf })
        })
            .then(function (res) {
                return res.json().catch(function () {
                    return { ok: false, error: 'Invalid server response.' };
                });
            })
            .then(function (data) {
                if (!data || (!data.ok && !data.updated)) {
                    throw new Error((data && (data.error || data.message)) || 'Could not update fence styles.');
                }
                toast(data.ok ? 'ok' : 'error', data.message || 'Fence styles updated.', BULK_TOAST_ID);
                global.location.reload();
            })
            .catch(function (err) {
                toast('error', (err && err.message) || 'Could not update fence styles.', BULK_TOAST_ID);
            });
    }

    function bulkImportFile(file, csrf) {
        if (!file) {
            return;
        }
        var name = String(file.name || '').toLowerCase();
        if (!name.endsWith('.json')) {
            toast('error', 'Only .json files can be imported.', BULK_TOAST_ID);
            return;
        }
        if (!csrf) {
            toast('error', 'Missing security token. Refresh and try again.', BULK_TOAST_ID);
            return;
        }

        var reader = new FileReader();
        reader.onload = function () {
            var parsed;
            try {
                parsed = JSON.parse(String(reader.result || ''));
            } catch (e) {
                toast('error', 'That file is not valid JSON.', BULK_TOAST_ID);
                return;
            }

            var styles = [];
            if (parsed && Array.isArray(parsed.styles)) {
                styles = parsed.styles;
            } else if (parsed && typeof parsed === 'object' && parsed.slug) {
                styles = [{ slug: parsed.slug, config: parsed.config || parsed }];
            }

            styles = styles
                .filter(function (s) {
                    return s && typeof s === 'object' && s.slug && s.config && typeof s.config === 'object';
                })
                .map(function (s) {
                    return { slug: s.slug, config: s.config };
                });

            if (!styles.length) {
                toast('error', 'That file does not contain any fence style data.', BULK_TOAST_ID);
                return;
            }

            toast(
                'saving',
                'Importing ' + styles.length + ' fence style' + (styles.length === 1 ? '' : 's') + '…',
                BULK_TOAST_ID
            );

            fetch(API_BULK_IMPORT, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ styles: styles, csrf: csrf })
            })
                .then(function (res) {
                    return res.json().catch(function () {
                        return { ok: false, error: 'Invalid server response.' };
                    });
                })
                .then(function (data) {
                    if (!data || (!data.ok && !data.updated)) {
                        throw new Error((data && (data.error || data.message)) || 'Could not import fence styles.');
                    }
                    toast(data.ok ? 'ok' : 'error', data.message || 'Fence styles imported.', BULK_TOAST_ID);
                    global.location.reload();
                })
                .catch(function (err) {
                    toast('error', (err && err.message) || 'Could not import fence styles.', BULK_TOAST_ID);
                });
        };
        reader.onerror = function () {
            toast('error', 'Could not read that file.', BULK_TOAST_ID);
        };
        reader.readAsText(file);
    }

    function applyBulkAction(container, getCsrf) {
        var actionEl = container.querySelector('[data-fc-fs-bulk-action]');
        var action = actionEl ? String(actionEl.value || '').trim() : '';
        var count = bulkSelection.size;
        if (!action || !count) {
            return;
        }

        confirmBulkAction(action, count).then(function (ok) {
            if (!ok) {
                return;
            }
            if (action === 'export') {
                bulkExportSelected();
                return;
            }
            bulkSetStatus(action === 'mark-live', getCsrf());
        });
    }

    function bindBulkBar(container, getCsrf) {
        if (!container || container.dataset.fcFsBulkBound === '1') {
            return;
        }
        container.dataset.fcFsBulkBound = '1';

        container.addEventListener('change', function (event) {
            var input = event.target.closest('[data-fc-fs-card-select]');
            if (input) {
                var slug = input.getAttribute('data-slug') || '';
                if (input.checked) {
                    bulkSelection.add(slug);
                } else {
                    bulkSelection.delete(slug);
                }
                updateBulkBar(container);
                return;
            }

            if (event.target.closest('[data-fc-fs-bulk-action]')) {
                updateBulkBar(container);
                return;
            }

            var importInput = event.target.closest('[data-fc-fs-bulk-import-input]');
            if (importInput) {
                var file = importInput.files && importInput.files[0] ? importInput.files[0] : null;
                bulkImportFile(file, getCsrf());
                importInput.value = '';
            }
        });

        container.addEventListener('click', function (event) {
            var applyBtn = event.target.closest('[data-fc-fs-bulk-apply]');
            if (applyBtn && !applyBtn.disabled) {
                event.preventDefault();
                applyBulkAction(container, getCsrf);
                return;
            }

            var importTrigger = event.target.closest('[data-fc-fs-bulk-import-trigger]');
            if (importTrigger) {
                event.preventDefault();
                closeAllCardGearMenus(container);
                var input = container.querySelector('[data-fc-fs-bulk-import-input]');
                if (input) {
                    input.click();
                }
                return;
            }

            var exportAllTrigger = event.target.closest('[data-fc-fs-bulk-export-all]');
            if (exportAllTrigger) {
                event.preventDefault();
                closeAllCardGearMenus(container);
                bulkExportAllStyles(container);
            }
        });
    }

    function renderBulkBar(canEdit) {
        if (!canEdit) {
            return '';
        }

        return (
            '<div class="fc-fs-bulk-bar fc-entries-page__footer" data-fc-fs-bulk-bar>' +
            '<div class="fc-entries-page__footer-row">' +
            '<div class="fc-entries-page__bulk" data-fc-fs-bulk>' +
            '<label class="fc-entries-page__bulk-label" for="fc-fs-bulk-action">Bulk actions</label>' +
            '<select id="fc-fs-bulk-action" class="fc-entries-page__bulk-select" data-fc-fs-bulk-action disabled>' +
            '<option value="">Bulk actions</option>' +
            '<option value="mark-live">Mark as Live</option>' +
            '<option value="mark-draft">Mark as Draft</option>' +
            '<option value="export">Export as JSON</option>' +
            '</select>' +
            '<button type="button" class="btn btn-sm btn-dark fw-semibold fc-entries-toolbar-menu__toggle" data-fc-fs-bulk-apply disabled>Apply</button>' +
            '<div class="fc-fs-card-gear fc-fs-bulk-bar__gear" data-fc-fs-card-gear data-slug="">' +
            '<button type="button" class="btn btn-sm btn-dark fw-semibold fc-products-download-trigger fc-entries-toolbar-menu__toggle" ' +
            'data-fc-fs-card-gear-toggle aria-haspopup="menu" aria-expanded="false" ' +
            'aria-label="Import or export fence styles" title="Import or export fence styles">' +
            '<i class="fa-solid fa-gear" aria-hidden="true"></i>' +
            '</button>' +
            '<div class="fc-products-download-dropdown__panel fc-fs-card-gear__panel" role="menu" hidden>' +
            '<button type="button" class="fc-products-download-dropdown__option" role="menuitem" data-fc-fs-bulk-import-trigger>' +
            '<span>Import Fence Styles</span></button>' +
            '<button type="button" class="fc-products-download-dropdown__option" role="menuitem" data-fc-fs-bulk-export-all>' +
            '<span>Export Fence Styles</span></button>' +
            '</div>' +
            '<input type="file" class="sr-only" accept="application/json,.json" data-fc-fs-bulk-import-input ' +
            'tabindex="-1" aria-hidden="true">' +
            '</div>' +
            '<span class="fc-entries-page__bulk-count" data-fc-fs-bulk-count hidden>0 selected</span>' +
            '</div>' +
            '</div>'
        );
    }

    function renderListPage(data) {
        var styles = data.styles || [];
        var canEdit = data.canEdit !== false;

        var body = !styles.length
            ? '<div class="p-8 text-center text-sm text-slate-500">No fence styles found in writable/fences.</div>'
            : '<div class="fc-admin-fence-styles">' +
              '<div class="fc-admin-fence-styles__grid">' +
              styles
                  .map(function (style) {
                      return renderStyleCard(style, canEdit);
                  })
                  .join('') +
              '</div></div>';

        return '<div class="fc-fs-styles-page">' + body + renderBulkBar(canEdit) + '</div>';
    }

    function loadFenceStyles(container) {
        if (!container) {
            return Promise.resolve();
        }

        if (container.querySelector('[data-fc-fence-styles-server]')) {
            return hydrateFromServer(container);
        }

        if (typeof global.fcAdminUrl === 'function') {
            global.location.href = global.fcAdminUrl('products/fence-styles');
            return Promise.resolve();
        }

        container.innerHTML = renderLoading('Loading fence styles…');

        return fetch(API_LIST, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.error) || 'Request failed');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Failed to load fence styles');
                }
                container.innerHTML = renderListPage(data);
                bulkSelection.clear();
                bindListLinks(container);
                bindCardGearMenus(container);
                bindBulkBar(container, function () {
                    return data.csrf || '';
                });
            })
            .catch(function (err) {
                container.innerHTML = renderError(err.message || 'Unknown error');
            });
    }

    function readBootstrapData() {
        var el = document.getElementById('fc-fence-styles-bootstrap');
        if (!el) {
            return null;
        }

        try {
            return JSON.parse(el.textContent || '');
        } catch (e) {
            return null;
        }
    }

    function hydrateFromServer(container) {
        if (!container || !container.querySelector('[data-fc-fence-styles-server]')) {
            return Promise.resolve(false);
        }

        bulkSelection.clear();
        bindListLinks(container);
        bindCardGearMenus(container);
        bindBulkBar(container, function () {
            var data = readBootstrapData();
            return data && data.csrf ? data.csrf : '';
        });
        container.removeAttribute('aria-busy');

        return Promise.resolve(true);
    }

    function saveFenceConfig(slug, config, csrf, onOk, onFail) {
        toast('saving', 'Saving fence style…', TOAST_SAVE);

        fetch(API_SAVE, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ slug: slug, config: config, csrf: csrf })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.error) || 'Save failed');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Save failed');
                }
                toast('ok', data.message || 'Fence style saved.', TOAST_SAVE);
                if (typeof onOk === 'function') {
                    onOk(data);
                }
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save fence style.', TOAST_SAVE);
                if (typeof onFail === 'function') {
                    onFail(err);
                }
            });
    }

    function loadFenceColorCatalog() {
        return fetch(API_FENCE_COLORS, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok || !data || !data.ok) {
                        return [];
                    }
                    return Array.isArray(data.fenceColors) ? data.fenceColors : [];
                });
            })
            .catch(function () {
                return [];
            });
    }

    function loadFenceStyleEdit(container, slug) {
        if (!container || !slug) {
            return Promise.resolve();
        }

        if (
            global.FcFenceStyleCodeEditor &&
            typeof global.FcFenceStyleCodeEditor.preload === 'function'
        ) {
            global.FcFenceStyleCodeEditor.preload();
        }

        container.innerHTML = renderLoading('Loading fence style…');

        return Promise.all([
            fetch(API_GET + '&slug=' + encodeURIComponent(slug), {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.error) || 'Request failed');
                    }
                    return data;
                });
            }),
            loadFenceColorCatalog()
        ])
            .then(function (results) {
                var data = results[0];
                var fenceColorCatalog = results[1];
                if (!data.ok || !data.style) {
                    throw new Error(data.error || 'Failed to load fence style');
                }
                var Edit = global.FcAdminFenceStyleEdit;
                if (!Edit || typeof Edit.renderEditPage !== 'function') {
                    throw new Error('Fence style editor module failed to load.');
                }
                var config = data.config && typeof data.config === 'object' ? data.config : {};
                var canEdit = data.canEdit !== false;
                container.innerHTML = Edit.renderEditPage(data.style, config, data.fileMeta || {}, {
                    fenceColorCatalog: fenceColorCatalog,
                    canEdit: canEdit
                });
                Edit.bindEditForm(container, data.style.slug, data.style, config, data.fileMeta || {}, {
                    fenceColorCatalog: fenceColorCatalog,
                    canEdit: canEdit,
                    csrf: data.csrf,
                    onBack: function () {
                        navigate('products/fence-styles');
                    },
                    onToast: toast,
                    onSave: function (nextConfig, onOk, onFail) {
                        if (!canEdit) {
                            if (typeof onFail === 'function') {
                                onFail(new Error('You do not have permission to edit fence styles.'));
                            }
                            return;
                        }
                        saveFenceConfig(data.style.slug, nextConfig, data.csrf, onOk, onFail);
                    }
                });
            })
            .catch(function (err) {
                container.innerHTML =
                    renderError(err.message || 'Unknown error') +
                    '<div class="px-4 pb-6"><button type="button" id="fc-fence-style-back-fail" class="btn btn-sm btn-dark fw-semibold">Back to Fence Styles</button></div>';
                var failBack = container.querySelector('#fc-fence-style-back-fail');
                if (failBack) {
                    failBack.addEventListener('click', function () {
                        navigate('products/fence-styles');
                    });
                }
            });
    }

    function parseEditSlug(route) {
        var prefix = 'products/fence-styles/edit/';
        if (route.indexOf(prefix) !== 0) {
            return '';
        }
        try {
            return decodeURIComponent(route.slice(prefix.length));
        } catch (e) {
            return route.slice(prefix.length);
        }
    }

    global.FcAdminFenceStyles = {
        load: loadFenceStyles,
        hydrateFromServer: hydrateFromServer,
        loadEdit: loadFenceStyleEdit,
        parseEditSlug: parseEditSlug
    };

    class FenceStylesPage extends global.FC.PageController {
        hydrate(container) {
            hydrateFromServer(container);
        }
    }
    global.FC.PageRegistry.register('products/fence-styles', new FenceStylesPage());
})(window);
