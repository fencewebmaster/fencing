/**
 * FC Admin — Media gallery (public/assets/uploads).
 */
(function (global) {
    'use strict';

    var API_LIST = fcApiUrl('gallery', 'action=list');
    var API_UPLOAD = fcApiUrl('gallery', 'action=upload');
    var API_DELETE = fcApiUrl('gallery', 'action=delete');
    var TOAST_GALLERY = 'fc-admin-gallery';
    var MODAL_ID = 'fc-gallery-attach-modal';
    var VIEW_MODE_KEY = 'fc-gallery-view-mode';
    var ACCEPT_TYPES = 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml';
    var GALLERY_DEFAULT_TAB = 'library';
    var GALLERY_URL_TAB_KEY = 'tab';

    var state = {
        container: null,
        items: [],
        modalPath: null,
        search: '',
        uploading: false,
        uploadQueue: null,
        appBase: '',
        modalEl: null,
        viewMode: 'grid',
        selected: {},
        activeTab: 'library',
        canUpload: true,
        canDelete: true,
        csrf: ''
    };

    var escapeHtml = global.FC.util.escapeHtml;

    function normalizeGalleryTab(tab) {
        var normalized = String(tab || '')
            .trim()
            .toLowerCase();
        return normalized === 'upload' ? 'upload' : GALLERY_DEFAULT_TAB;
    }

    function readGalleryTabFromUrl() {
        var page = document.querySelector('.fc-gallery-page[data-fc-gallery-server]');
        if (page) {
            var initial = page.getAttribute('data-fc-gallery-initial-tab');
            if (initial) {
                return normalizeGalleryTab(initial);
            }
        }

        var params = new URLSearchParams(window.location.search);
        return normalizeGalleryTab(params.get(GALLERY_URL_TAB_KEY));
    }

    function syncGalleryTabUrl(tabId) {
        var tab = normalizeGalleryTab(tabId);
        var params = new URLSearchParams();
        if (tab !== GALLERY_DEFAULT_TAB) {
            params.set(GALLERY_URL_TAB_KEY, tab);
        }

        var search = params.toString();
        var nextUrl = window.location.pathname + (search ? '?' + search : '');
        var currentUrl = window.location.pathname + window.location.search;
        if (nextUrl === currentUrl) {
            return;
        }

        var historyState =
            window.history.state && typeof window.history.state === 'object'
                ? window.history.state
                : {};
        window.history.replaceState(historyState, '', nextUrl);
    }

    function toast(kind, message, toastId) {
        global.FC.util.toast(kind, message, toastId || TOAST_GALLERY);
    }

    function formatBytes(bytes) {
        var n = Number(bytes) || 0;
        if (n < 1024) {
            return n + ' B';
        }
        if (n < 1024 * 1024) {
            return (n / 1024).toFixed(1) + ' KB';
        }
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function formatDate(ts) {
        if (!ts) {
            return '—';
        }
        if (typeof global.fcFormatAdminDate === 'function') {
            return global.fcFormatAdminDate(ts);
        }
        try {
            return new Date(ts * 1000).toLocaleString();
        } catch (e) {
            return '—';
        }
    }

    function assetUrl(path) {
        var base = String(state.appBase || '').replace(/\/+$/, '');
        var rel = String(path || '').replace(/^\/+/, '');
        return base ? base + '/' + rel : rel;
    }

    function fileTypeBadge(item) {
        var mime = String((item && item.mime) || '').toLowerCase();
        if (mime.indexOf('jpeg') !== -1 || mime === 'image/jpg') {
            return 'JPG';
        }
        if (mime.indexOf('png') !== -1) {
            return 'PNG';
        }
        if (mime.indexOf('gif') !== -1) {
            return 'GIF';
        }
        if (mime.indexOf('webp') !== -1) {
            return 'WEBP';
        }
        if (mime.indexOf('svg') !== -1) {
            return 'SVG';
        }
        var ext = String((item && item.name) || '')
            .split('.')
            .pop();
        return ext ? ext.toUpperCase() : 'IMG';
    }

    function filteredItems() {
        var q = state.search.trim().toLowerCase();
        if (!q) {
            return state.items;
        }
        return state.items.filter(function (item) {
            return String(item.name || '')
                .toLowerCase()
                .includes(q);
        });
    }

    function itemByPath(path) {
        return state.items.find(function (item) {
            return item.path === path;
        }) || null;
    }

    function getViewMode() {
        try {
            return localStorage.getItem(VIEW_MODE_KEY) === 'list' ? 'list' : 'grid';
        } catch (e) {
            return 'grid';
        }
    }

    function setViewMode(mode) {
        state.viewMode = mode === 'list' ? 'list' : 'grid';
        try {
            localStorage.setItem(VIEW_MODE_KEY, state.viewMode);
        } catch (e) {
            /* ignore */
        }
    }

    function isSelected(path) {
        return !!state.selected[path];
    }

    function selectedPaths() {
        return Object.keys(state.selected).filter(function (path) {
            return state.selected[path];
        });
    }

    function selectedCount() {
        return selectedPaths().length;
    }

    function toggleSelected(path, selected) {
        if (selected === undefined) {
            selected = !isSelected(path);
        }
        if (selected) {
            state.selected[path] = true;
        } else {
            delete state.selected[path];
        }
    }

    function clearSelection() {
        state.selected = {};
    }

    function selectAllFiltered() {
        filteredItems().forEach(function (item) {
            state.selected[item.path] = true;
        });
    }

    function pruneSelection() {
        var valid = {};
        state.items.forEach(function (item) {
            if (state.selected[item.path]) {
                valid[item.path] = true;
            }
        });
        state.selected = valid;
    }

    function renderItemCheckbox(path) {
        if (!state.canDelete) {
            return '';
        }
        var checked = isSelected(path);
        return (
            '<label class="fc-gallery-item__check" data-fc-gallery-check-wrap="' +
            escapeHtml(path) +
            '">' +
            '<input type="checkbox" class="fc-gallery-item__check-input" data-fc-gallery-select="' +
            escapeHtml(path) +
            '"' +
            (checked ? ' checked' : '') +
            ' aria-label="Select file">' +
            '<span class="fc-gallery-item__check-ui" aria-hidden="true">' +
            '<i class="fa-solid fa-check fc-gallery-item__check-icon" aria-hidden="true"></i>' +
            '</span></label>'
        );
    }

    function renderGridItem(item) {
        var selected = isSelected(item.path);
        return (
            '<li class="fc-gallery-grid__item">' +
            '<div class="fc-gallery-item-wrap' +
            (selected ? ' is-selected' : '') +
            '" data-fc-gallery-wrap="' +
            escapeHtml(item.path) +
            '">' +
            renderItemCheckbox(item.path) +
            '<button type="button" class="fc-gallery-item" data-fc-gallery-item="' +
            escapeHtml(item.path) +
            '" title="' +
            escapeHtml(item.name) +
            '">' +
            '<span class="fc-gallery-item__thumb">' +
            '<span class="fc-gallery-item__badge">' +
            escapeHtml(fileTypeBadge(item)) +
            '</span>' +
            '<img alt="" class="fc-lazy" data-fc-lazy data-fc-lazy-src="' +
            escapeHtml(assetUrl(item.path)) +
            '" decoding="async">' +
            '</span></button></div></li>'
        );
    }

    function renderListItem(item) {
        var selected = isSelected(item.path);
        var dimensions =
            item.width && item.height ? String(item.width) + ' × ' + String(item.height) : '—';
        return (
            '<li class="fc-gallery-list__item' +
            (selected ? ' is-selected' : '') +
            '" data-fc-gallery-wrap="' +
            escapeHtml(item.path) +
            '">' +
            renderItemCheckbox(item.path) +
            '<button type="button" class="fc-gallery-list__row" data-fc-gallery-item="' +
            escapeHtml(item.path) +
            '" title="' +
            escapeHtml(item.name) +
            '">' +
            '<span class="fc-gallery-list__thumb">' +
            '<img alt="" class="fc-lazy" data-fc-lazy data-fc-lazy-src="' +
            escapeHtml(assetUrl(item.path)) +
            '" decoding="async">' +
            '</span>' +
            '<span class="fc-gallery-list__info">' +
            '<span class="fc-gallery-list__name">' +
            escapeHtml(item.name) +
            '</span>' +
            '<span class="fc-gallery-list__meta">' +
            escapeHtml(fileTypeBadge(item)) +
            ' · ' +
            escapeHtml(formatBytes(item.size)) +
            ' · ' +
            escapeHtml(formatDate(item.modified)) +
            ' · ' +
            escapeHtml(dimensions) +
            '</span></span>' +
            '<span class="fc-gallery-list__badge">' +
            escapeHtml(fileTypeBadge(item)) +
            '</span></button></li>'
        );
    }

    function renderViewToggle() {
        return (
            '<div class="fc-gallery-view-toggle" role="group" aria-label="View mode">' +
            '<button type="button" class="fc-gallery-view-toggle__btn' +
            (state.viewMode === 'grid' ? ' is-active' : '') +
            '" data-fc-gallery-view="grid" aria-pressed="' +
            (state.viewMode === 'grid' ? 'true' : 'false') +
            '" aria-label="Grid view" title="Grid view">' +
            '<i class="fa-solid fa-grip" aria-hidden="true"></i></button>' +
            '<button type="button" class="fc-gallery-view-toggle__btn' +
            (state.viewMode === 'list' ? ' is-active' : '') +
            '" data-fc-gallery-view="list" aria-pressed="' +
            (state.viewMode === 'list' ? 'true' : 'false') +
            '" aria-label="List view" title="List view">' +
            '<i class="fa-solid fa-list" aria-hidden="true"></i></button></div>'
        );
    }

    function renderBulkBar(items) {
        var count = selectedCount();
        if (!count || !state.canDelete) {
            return '';
        }
        return (
            '<div class="fc-gallery-bulk-bar">' +
            '<span class="fc-gallery-bulk-bar__count">' +
            count +
            ' selected</span>' +
            '<div class="fc-gallery-bulk-bar__actions">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" data-fc-gallery-select-all>Select all (' +
            items.length +
            ')</button>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" data-fc-gallery-clear-selection>Clear selection</button>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary fw-semibold fc-gallery-bulk-bar__delete" data-fc-gallery-bulk-delete>Delete selected</button>' +
            '</div></div>'
        );
    }

    function renderItemsList(items) {
        if (!items.length) {
            return (
                '<div class="fc-gallery-empty">' +
                '<i class="fa-regular fa-image fc-gallery-empty__icon" aria-hidden="true"></i>' +
                '<p class="fc-gallery-empty__title">No media files yet</p>' +
                '<p class="fc-gallery-empty__hint">Switch to <strong>Add New</strong> to upload files.</p>' +
                '</div>'
            );
        }

        if (state.viewMode === 'list') {
            return (
                '<ul class="fc-gallery-list" data-fc-gallery-grid>' +
                items.map(renderListItem).join('') +
                '</ul>'
            );
        }

        return (
            '<ul class="fc-gallery-grid" data-fc-gallery-grid>' +
            items.map(renderGridItem).join('') +
            '</ul>'
        );
    }

    var lockScroll = global.FcAdminModal.lockScroll;
    var unlockScroll = global.FcAdminModal.unlockScroll;

    var copyFieldButton = new global.FC.components.CopyFieldButton({
        dataAttr: 'data-fc-gallery-copy-for',
        onCopied: function () {
            toast('ok', 'Copied to clipboard', TOAST_GALLERY);
        },
        onError: function () {
            toast('error', 'Could not copy to clipboard', TOAST_GALLERY);
        }
    });

    function buildFieldCopyButton(fieldId, label) {
        return copyFieldButton.markup(fieldId, label);
    }

    function copyFieldToClipboard(control, btn) {
        copyFieldButton.copy(control, btn);
    }

    function renderCopyField(id, label, value) {
        return (
            '<div class="fc-sp-field fc-sp-field--wide">' +
            '<label class="fc-sp-field__label" for="' +
            escapeHtml(id) +
            '"><span>' +
            escapeHtml(label) +
            '</span></label>' +
            '<div class="fc-sp-field-input-wrap">' +
            '<input type="text" id="' +
            escapeHtml(id) +
            '" class="fc-sp-field-control fc-sp-field-control--readonly" readonly value="' +
            escapeHtml(value) +
            '" tabindex="-1" aria-readonly="true">' +
            buildFieldCopyButton(id, label) +
            '</div></div>'
        );
    }

    function renderAttachmentDetails(item) {
        var fullUrl = assetUrl(item.path);
        var dimensions =
            item.width && item.height ? String(item.width) + ' × ' + String(item.height) + ' pixels' : '—';

        return (
            '<aside class="fc-gallery-modal__details">' +
            '<dl class="fc-gallery-modal__meta">' +
            '<div class="fc-gallery-modal__meta-row"><dt>Uploaded</dt><dd>' +
            escapeHtml(formatDate(item.modified)) +
            '</dd></div>' +
            '<div class="fc-gallery-modal__meta-row"><dt>Type</dt><dd>' +
            escapeHtml(item.mime || '—') +
            '</dd></div>' +
            '<div class="fc-gallery-modal__meta-row"><dt>Size</dt><dd>' +
            escapeHtml(formatBytes(item.size)) +
            '</dd></div>' +
            '<div class="fc-gallery-modal__meta-row"><dt>Dimensions</dt><dd>' +
            escapeHtml(dimensions) +
            '</dd></div>' +
            '</dl>' +
            '<div class="fc-gallery-modal__fields">' +
            renderCopyField('fc-gallery-field-path', 'File path', item.path) +
            renderCopyField('fc-gallery-field-url', 'Full URL', fullUrl) +
            '</div>' +
            '<div class="fc-gallery-modal__actions">' +
            '<a class="btn btn-sm btn-dark fw-semibold fc-gallery-modal__action-btn" href="' +
            escapeHtml(fullUrl) +
            '" target="_blank" rel="noopener noreferrer">Open file</a>' +
            (state.canDelete
                ? '<button type="button" class="btn btn-sm btn-outline-secondary fw-semibold fc-gallery-modal__delete fc-gallery-modal__action-btn" data-fc-gallery-delete>Delete</button>'
                : '') +
            '</div></aside>'
        );
    }

    function renderAttachmentModal(item) {
        if (!item) {
            return '';
        }

        return (
            '<div id="' +
            MODAL_ID +
            '" class="fc-gallery-modal is-open" role="dialog" aria-modal="true" aria-labelledby="fc-gallery-modal-title">' +
            '<div class="fc-gallery-modal__backdrop" data-fc-gallery-modal-close aria-hidden="true"></div>' +
            '<div class="fc-gallery-modal__panel">' +
            '<button type="button" class="fencing-modal-close" data-fc-gallery-modal-close aria-label="Close"></button>' +
            '<header class="fc-gallery-modal__head">' +
            '<div class="fc-gallery-modal__head-text">' +
            '<h2 id="fc-gallery-modal-title" class="fc-gallery-modal__title">Attachment details</h2>' +
            '<p class="fc-gallery-modal__filename">' +
            escapeHtml(item.name) +
            '</p></div></header>' +
            '<div class="fc-gallery-modal__body">' +
            '<div class="fc-gallery-modal__preview">' +
            '<img src="' +
            escapeHtml(assetUrl(item.path)) +
            '" alt="' +
            escapeHtml(item.name) +
            '">' +
            '</div>' +
            renderAttachmentDetails(item) +
            '</div></div></div>'
        );
    }

    function ensureModalRoot() {
        var root = document.getElementById('fc-admin-modal-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'fc-admin-modal-root';
            document.body.appendChild(root);
        }
        root.classList.add('fc-gallery-modal-root--active');
        root.setAttribute('aria-hidden', 'false');
        return root;
    }

    function clearModalRoot() {
        var root = document.getElementById('fc-admin-modal-root');
        if (!root) {
            return;
        }
        if (!root.querySelector('.fc-gallery-modal') && !root.querySelector('.fc-media-picker')) {
            root.classList.remove('fc-gallery-modal-root--active');
            root.setAttribute('aria-hidden', 'true');
        }
    }

    function closeAttachmentModal() {
        if (state.modalEl && state.modalEl.parentNode) {
            state.modalEl.parentNode.removeChild(state.modalEl);
        }
        state.modalEl = null;
        state.modalPath = null;
        unlockScroll();
        clearModalRoot();
    }

    function bindModalEvents() {
        if (!state.modalEl) {
            return;
        }

        state.modalEl.querySelectorAll('[data-fc-gallery-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', closeAttachmentModal);
        });

        state.modalEl.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAttachmentModal();
            }
        });

        state.modalEl.addEventListener('click', function (e) {
            var copyBtn = e.target.closest('[data-fc-gallery-copy-for]');
            if (!copyBtn || !state.modalEl.contains(copyBtn)) {
                return;
            }
            var fieldId = copyBtn.getAttribute('data-fc-gallery-copy-for');
            var control = fieldId ? document.getElementById(fieldId) : null;
            copyFieldToClipboard(control, copyBtn);
        });

        var deleteBtn = state.modalEl.querySelector('[data-fc-gallery-delete]');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                var item = itemByPath(state.modalPath);
                if (!item) {
                    return;
                }
                var confirmFn =
                    global.FcAdminModal && typeof global.FcAdminModal.confirm === 'function'
                        ? global.FcAdminModal.confirm.bind(global.FcAdminModal)
                        : null;
                var message = 'Delete “' + item.name + '”? This cannot be undone.';
                var runDelete = function () {
                    closeAttachmentModal();
                    deleteItemWithToast(item.path);
                };
                if (confirmFn) {
                    confirmFn({
                        title: 'Delete file',
                        message: message,
                        confirmLabel: 'Delete',
                        variant: 'warning'
                    }).then(function (ok) {
                        if (ok) {
                            runDelete();
                        }
                    });
                    return;
                }
                if (global.confirm(message)) {
                    runDelete();
                }
            });
        }

        var closeBtn = state.modalEl.querySelector('.fencing-modal-close');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function openAttachmentModal(path) {
        var item = itemByPath(path);
        if (!item) {
            return;
        }

        closeAttachmentModal();
        state.modalPath = path;

        var root = ensureModalRoot();
        root.insertAdjacentHTML('beforeend', renderAttachmentModal(item));
        state.modalEl = document.getElementById(MODAL_ID);
        lockScroll();
        bindModalEvents();
    }

    function renderTabBar() {
        var tabs = [{ id: 'library', label: 'Media Library' }];
        if (state.canUpload) {
            tabs.push({ id: 'upload', label: 'Add New' });
        }

        return (
            '<div class="fc-gallery-page__tabs" role="tablist" aria-label="Gallery sections">' +
            tabs
                .map(function (tab) {
                    var active = state.activeTab === tab.id;
                    return (
                        '<button type="button" role="tab" data-fc-gallery-tab="' +
                        escapeHtml(tab.id) +
                        '" aria-selected="' +
                        (active ? 'true' : 'false') +
                        '" class="fc-gallery-page__tab' +
                        (active ? ' is-active' : '') +
                        '">' +
                        escapeHtml(tab.label) +
                        '</button>'
                    );
                })
                .join('') +
            '</div>'
        );
    }

    function ensureUploadQueue() {
        if (state.uploadQueue) {
            // Keep the queue's token in sync — it's created once and cached, but the
            // CSRF token can be (re)hydrated later (e.g. re-login without a full page
            // reload), and a stale token here causes every upload to fail with
            // "Invalid security token" even though the page itself is fine.
            state.uploadQueue.csrf = state.csrf;
            return state.uploadQueue;
        }
        if (!global.FcGalleryUploadQueue) {
            return state.uploadQueue;
        }

        state.uploadQueue = global.FcGalleryUploadQueue.create({
            apiUrl: API_UPLOAD,
            csrf: state.csrf,
            onChange: function (event) {
                if (!state.container || state.activeTab !== 'upload') {
                    return;
                }
                if (event.type === 'structure') {
                    renderUploadPanel();
                    return;
                }
                if (event.type === 'progress' && event.item) {
                    state.uploadQueue.patchCard(event.item);
                }
            },
            onAllSettled: function (summary) {
                state.uploading = state.uploadQueue ? state.uploadQueue.hasActive() : false;

                var T = global.FcAdminToast;
                if (T && !summary.uploaded && !summary.failed) {
                    T.dismiss(TOAST_GALLERY);
                }

                if (summary.uploaded) {
                    toast(
                        'ok',
                        summary.uploaded + ' file' + (summary.uploaded === 1 ? '' : 's') + ' uploaded.',
                        TOAST_GALLERY
                    );
                    if (state.uploadQueue) {
                        state.uploadQueue.clearDone();
                    }
                    state.activeTab = 'library';
                    syncGalleryTabUrl('library');
                    loadItems().then(function () {
                        var paths = summary.paths || [];
                        if (paths.length === 1) {
                            openAttachmentModal(paths[0]);
                        }
                    });
                    return;
                }

                if (summary.failed) {
                    toast('error', 'Upload failed.', TOAST_GALLERY);
                }

                if (!summary.uploaded) {
                    renderUploadPanel();
                }
            }
        });

        return state.uploadQueue;
    }

    function uploadHasActive() {
        return state.uploadQueue ? state.uploadQueue.hasActive() : state.uploading;
    }

    function renderUploadPanel() {
        if (!state.container) {
            return;
        }
        var panel = state.container.querySelector('.fc-gallery-page__upload-panel');
        if (!panel) {
            renderPage();
            return;
        }
        panel.innerHTML = renderUploadSectionInner();
        bindUploadPanelEvents(panel);
    }

    function renderUploadSectionInner() {
        var queue = ensureUploadQueue();
        var hasQueue = queue && queue.hasItems();

        return (
            renderUploadDropzone(true, hasQueue) +
            (hasQueue ? queue.renderQueue() : '')
        );
    }

    function renderUploadDropzone(full, compact) {
        var uploadText = compact ? 'Add more files' : 'Drop files here to upload';
        var hintText = compact
            ? 'Drop files here or click to browse'
            : 'or click anywhere in this area to browse your computer';

        return (
            '<div class="fc-gallery-page__dropzone' +
            (full ? ' fc-gallery-page__dropzone--full' : '') +
            (compact ? ' fc-gallery-page__dropzone--compact' : '') +
            '" data-fc-gallery-dropzone tabindex="0" role="button" aria-label="Upload files">' +
            '<i class="fa-solid fa-cloud-arrow-up fc-gallery-page__dropzone-icon" aria-hidden="true"></i>' +
            '<p class="fc-gallery-page__dropzone-title">' +
            escapeHtml(uploadText) +
            '</p>' +
            '<p class="fc-gallery-page__dropzone-text">' +
            escapeHtml(hintText) +
            '</p>' +
            (full && !compact
                ? '<p class="fc-gallery-page__dropzone-hint">JPG, PNG, GIF, WebP, or SVG · saved to <code>public/assets/uploads</code></p>'
                : '') +
            '<input type="file" class="fc-gallery-page__file-input" accept="' +
            ACCEPT_TYPES +
            '" multiple hidden>' +
            '</div>'
        );
    }

    function renderLibraryToolbar(items) {
        var bulkBar = renderBulkBar(items);

        return (
            '<div class="fc-gallery-page__toolbar" data-fc-admin-sticky-header>' +
            '<div class="fc-gallery-page__toolbar-row">' +
            renderViewToggle() +
            '<div class="fc-gallery-page__search-wrap">' +
            '<i class="fa-solid fa-magnifying-glass fc-gallery-page__search-icon" aria-hidden="true"></i>' +
            '<input type="search" class="fc-gallery-page__search" placeholder="Search media…" value="' +
            escapeHtml(state.search) +
            '" autocomplete="off">' +
            '</div>' +
            '<span class="fc-gallery-page__count">' +
            items.length +
            ' item' +
            (items.length === 1 ? '' : 's') +
            '</span></div>' +
            bulkBar +
            '</div>'
        );
    }

    function renderLibrarySection(items) {
        return (
            renderLibraryToolbar(items) +
            '<div class="fc-gallery-page__content" data-fc-lazy-root>' +
            renderItemsList(items) +
            '</div>'
        );
    }

    function renderUploadSection() {
        return '<div class="fc-gallery-page__upload-panel">' + renderUploadSectionInner() + '</div>';
    }

    function bindUploadPanelEvents(panel) {
        if (!panel) {
            return;
        }

        var queue = ensureUploadQueue();
        if (queue) {
            queue.bind(panel);
        }

        var fileInput = panel.querySelector('.fc-gallery-page__file-input');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length) {
                    uploadFiles(fileInput.files);
                    fileInput.value = '';
                }
            });
        }

        var dropzone = panel.querySelector('[data-fc-gallery-dropzone]');
        if (dropzone) {
            dropzone.addEventListener('click', function (e) {
                if (e.target.closest('.fc-upload-card__action')) {
                    return;
                }
                if (e.target.closest('.fc-gallery-page__file-input')) {
                    return;
                }
                var input = dropzone.querySelector('.fc-gallery-page__file-input') || fileInput;
                if (input) {
                    input.click();
                }
            });
            dropzone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    var input = dropzone.querySelector('.fc-gallery-page__file-input') || fileInput;
                    if (input) {
                        input.click();
                    }
                }
            });
            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            });
            dropzone.addEventListener('dragleave', function () {
                dropzone.classList.remove('is-dragover');
            });
            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                    uploadFiles(e.dataTransfer.files);
                }
            });
        }
    }

    function switchTab(tabId) {
        if (tabId !== 'library' && tabId !== 'upload') {
            return;
        }
        if (tabId === 'upload' && !state.canUpload) {
            return;
        }
        if (uploadHasActive() && tabId === 'library') {
            return;
        }
        state.activeTab = tabId;
        syncGalleryTabUrl(tabId);
        renderPage();
    }

    function renderShell() {
        var items = filteredItems();
        var hasSelection = selectedCount() > 0;

        return (
            '<div class="fc-gallery-page fc-gallery-page--' +
            escapeHtml(state.viewMode) +
            (hasSelection ? ' fc-gallery-page--has-selection' : '') +
            (state.activeTab === 'upload' ? ' fc-gallery-page--upload' : '') +
            '" data-fc-gallery-page>' +
            renderTabBar() +
            (state.activeTab === 'library' ? renderLibrarySection(items) : renderUploadSection()) +
            '</div>'
        );
    }

    function renderPage() {
        if (!state.container) {
            return;
        }
        state.container.innerHTML = renderShell();
        bindPageEvents();
    }

    function initGalleryLazy() {
        if (!state.container || state.activeTab !== 'library' || !global.FcLazy) {
            return;
        }

        global.requestAnimationFrame(function () {
            if (!state.container) {
                return;
            }
            var root = state.container.querySelector('.fc-gallery-page__content[data-fc-lazy-root]');
            if (!root) {
                return;
            }
            global.FcLazy.refresh(root, { root: root });
        });
    }

    function bindPageEvents() {
        if (!state.container) {
            return;
        }

        if (!state.container.getAttribute('data-fc-gallery-shell-bound')) {
            state.container.setAttribute('data-fc-gallery-shell-bound', '1');
            state.container.addEventListener('click', function (e) {
                var tabBtn = e.target.closest('[data-fc-gallery-tab]');
                if (tabBtn) {
                    switchTab(tabBtn.getAttribute('data-fc-gallery-tab'));
                }
            });
        }

        var searchInput = state.container.querySelector('.fc-gallery-page__search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                state.search = searchInput.value;
                renderPage();
                var next = state.container.querySelector('.fc-gallery-page__search');
                if (next) {
                    next.focus();
                    next.setSelectionRange(next.value.length, next.value.length);
                }
            });
        }

        if (state.activeTab === 'upload') {
            bindUploadPanelEvents(state.container.querySelector('.fc-gallery-page__upload-panel'));
        }

        state.container.querySelectorAll('[data-fc-gallery-view]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setViewMode(btn.getAttribute('data-fc-gallery-view'));
                renderPage();
            });
        });

        state.container.querySelectorAll('[data-fc-gallery-select]').forEach(function (input) {
            input.addEventListener('click', function (e) {
                e.stopPropagation();
            });
            input.addEventListener('change', function () {
                toggleSelected(input.getAttribute('data-fc-gallery-select'), input.checked);
                renderPage();
            });
        });

        var selectAllBtn = state.container.querySelector('[data-fc-gallery-select-all]');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                selectAllFiltered();
                renderPage();
            });
        }

        var clearBtn = state.container.querySelector('[data-fc-gallery-clear-selection]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                clearSelection();
                renderPage();
            });
        }

        var bulkDeleteBtn = state.container.querySelector('[data-fc-gallery-bulk-delete]');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function () {
                bulkDeleteSelected();
            });
        }

        state.container.querySelectorAll('[data-fc-gallery-item]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var path = btn.getAttribute('data-fc-gallery-item');
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    toggleSelected(path);
                    renderPage();
                    return;
                }
                openAttachmentModal(path);
            });
        });

        initGalleryLazy();
    }

    function uploadFiles(fileList) {
        var files = Array.prototype.slice.call(fileList || []);
        if (!files.length) {
            return;
        }

        var queue = ensureUploadQueue();
        if (!queue) {
            return;
        }

        state.activeTab = 'upload';
        syncGalleryTabUrl('upload');
        state.uploading = true;

        if (state.container && state.container.querySelector('.fc-gallery-page__upload-panel')) {
            renderUploadPanel();
        } else {
            renderPage();
        }

        toast('saving', 'Uploading…', TOAST_GALLERY);
        queue.addFiles(files);
    }

    function deleteItem(path) {
        return fetch(API_DELETE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ path: path, csrf: state.csrf })
        }).then(function (res) {
            return res.json().then(function (body) {
                if (!res.ok || !body.ok) {
                    throw new Error((body && body.error) || 'Delete failed.');
                }
                return body;
            });
        });
    }

    function deleteItemWithToast(path) {
        toast('saving', 'Deleting…', TOAST_GALLERY);
        return deleteItem(path)
            .then(function () {
                toast('ok', 'File deleted.', TOAST_GALLERY);
                return loadItems();
            })
            .catch(function (err) {
                toast('error', err.message || 'Delete failed.', TOAST_GALLERY);
            });
    }

    function bulkDeleteSelected() {
        var paths = selectedPaths();
        if (!paths.length) {
            return;
        }

        var confirmFn =
            global.FcAdminModal && typeof global.FcAdminModal.confirm === 'function'
                ? global.FcAdminModal.confirm.bind(global.FcAdminModal)
                : null;
        var message =
            'Delete ' +
            paths.length +
            ' selected file' +
            (paths.length === 1 ? '' : 's') +
            '? This cannot be undone.';

        var runDelete = function () {
            toast('saving', 'Deleting selected files…', TOAST_GALLERY);
            var chain = Promise.resolve();
            var deleted = 0;
            var failed = 0;

            paths.forEach(function (path) {
                chain = chain.then(function () {
                    return deleteItem(path)
                        .then(function () {
                            deleted += 1;
                            delete state.selected[path];
                        })
                        .catch(function () {
                            failed += 1;
                        });
                });
            });

            chain.then(function () {
                if (deleted) {
                    toast(
                        'ok',
                        deleted + ' file' + (deleted === 1 ? '' : 's') + ' deleted.',
                        TOAST_GALLERY
                    );
                } else if (failed) {
                    toast('error', 'Could not delete selected files.', TOAST_GALLERY);
                }
                clearSelection();
                loadItems();
            });
        };

        if (confirmFn) {
            confirmFn({
                title: 'Delete selected files',
                message: message,
                confirmLabel: 'Delete',
                variant: 'warning'
            }).then(function (ok) {
                if (ok) {
                    runDelete();
                }
            });
            return;
        }

        if (global.confirm(message)) {
            runDelete();
        }
    }

    function loadItems() {
        if (!state.container) {
            return Promise.resolve();
        }

        return fetch(API_LIST, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    if (!res.ok || !body.ok) {
                        throw new Error((body && body.error) || 'Could not load gallery.');
                    }
                    state.items = Array.isArray(body.items) ? body.items : [];
                    pruneSelection();
                    renderPage();
                });
            })
            .catch(function (err) {
                if (state.container) {
                    state.container.innerHTML =
                        '<div class="fc-gallery-error">' +
                        '<p class="fc-gallery-error__title">Could not load gallery</p>' +
                        '<p class="fc-gallery-error__message">' +
                        escapeHtml(err.message || 'Unknown error') +
                        '</p></div>';
                }
            });
    }

    function readBootstrapData() {
        var el = document.getElementById('fc-gallery-bootstrap');
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
        if (!container || !container.querySelector('.fc-gallery-page[data-fc-gallery-server]')) {
            return Promise.resolve(false);
        }

        var data = readBootstrapData();
        if (!data) {
            return Promise.resolve(false);
        }

        state.container = container;
        state.items = Array.isArray(data.items) ? data.items : [];
        state.modalPath = null;
        state.search = '';
        state.uploading = false;
        state.selected = {};
        state.canUpload = data.canUpload !== false;
        state.canDelete = data.canDelete !== false;
        state.csrf = data.csrf || '';
        state.activeTab = normalizeGalleryTab(data.activeTab || readGalleryTabFromUrl());
        if (state.activeTab === 'upload' && !state.canUpload) {
            state.activeTab = 'library';
        }
        syncGalleryTabUrl(state.activeTab);
        state.viewMode = getViewMode();
        state.appBase =
            data.appBase || (document.body && document.body.getAttribute('data-fc-app-base')) || '';

        pageController.destroy = function () {
            closeAttachmentModal();
            state.container = null;
        };

        if (state.activeTab === 'library' && state.viewMode !== 'grid') {
            renderPage();
        } else {
            bindPageEvents();
            initGalleryLazy();
        }

        container.removeAttribute('aria-busy');

        return Promise.resolve(true);
    }

    function loadGallery(container) {
        if (!container) {
            return Promise.resolve();
        }

        if (container.querySelector('.fc-gallery-page[data-fc-gallery-server]')) {
            return hydrateFromServer(container);
        }

        if (typeof global.fcAdminUrl === 'function') {
            global.location.href = global.fcAdminUrl('gallery');
            return Promise.resolve();
        }

        container.innerHTML =
            '<div class="fc-gallery-page fc-gallery-page--loading"><p class="fc-gallery-page__loading">Loading gallery…</p></div>';

        return loadItems();
    }

    global.FcAdminGallery = {
        load: loadGallery,
        hydrateFromServer: hydrateFromServer
    };

    class GalleryPage extends global.FC.PageController {
        hydrate(container) {
            hydrateFromServer(container);
        }
    }
    var pageController = new GalleryPage();
    global.FC.PageRegistry.register('gallery', pageController);
})(window);
