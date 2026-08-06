/**
 * FC Admin — media picker modal (Media Library + Add New upload).
 */
(function (global) {
    'use strict';

    var API_LIST = fcApiUrl('gallery', 'action=list');
    var API_UPLOAD = fcApiUrl('gallery', 'action=upload');
    var MODAL_ID = 'fc-media-picker-modal';
    var TOAST_PICKER = 'fc-media-picker-upload';
    var ACCEPT_TYPES = 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml';

    var pickerState = {
        open: false,
        items: [],
        search: '',
        appBase: '',
        csrf: '',
        onSelect: null,
        modalEl: null,
        uploading: false,
        uploadQueue: null,
        activeTab: 'library',
        loadError: null,
        canList: true,
        canUpload: true
    };

    function readBodyFlag(name, fallback) {
        var body = document.body;
        if (!body || !body.hasAttribute(name)) {
            return fallback !== false;
        }
        return body.getAttribute(name) === '1';
    }

    function readMediaCaps(options) {
        options = options || {};
        var caps = {
            canList: readBodyFlag('data-fc-can-media-list', true),
            canUpload: readBodyFlag('data-fc-can-media-upload', true)
        };
        if (options.canList != null) {
            caps.canList = !!options.canList;
        }
        if (options.canUpload != null) {
            caps.canUpload = !!options.canUpload;
        }
        return caps;
    }

    function applyCapsFromPayload(body) {
        if (!body || typeof body !== 'object') {
            return;
        }
        if (typeof body.canList === 'boolean') {
            pickerState.canList = body.canList;
        }
        if (typeof body.canUpload === 'boolean') {
            pickerState.canUpload = body.canUpload;
        }
    }

    function defaultTab() {
        if (pickerState.canList) {
            return 'library';
        }
        if (pickerState.canUpload) {
            return 'upload';
        }
        return 'library';
    }

    function pickerDescription() {
        if (pickerState.canList && pickerState.canUpload) {
            return 'Choose from the media library or upload a new file.';
        }
        if (pickerState.canUpload) {
            return 'Upload a new image file.';
        }
        return 'Choose an image from the media library.';
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function toast(kind, message, toastId) {
        var T = global.FcAdminToast;
        if (!T) {
            return;
        }
        toastId = toastId || TOAST_PICKER;
        if (kind === 'saving') {
            T.loading(message, toastId);
            return;
        }
        T.dismiss(toastId);
        if (kind === 'ok') {
            T.success(message);
        } else if (kind === 'error') {
            T.error(message);
        }
    }

    function assetUrl(path) {
        var base = String(pickerState.appBase || '').replace(/\/+$/, '');
        var rel = String(path || '').replace(/^\/+/, '');
        return base ? base + '/' + rel : rel;
    }

    function filteredItems() {
        var q = pickerState.search.trim().toLowerCase();
        if (!q) {
            return pickerState.items;
        }
        return pickerState.items.filter(function (item) {
            return String(item.name || '')
                .toLowerCase()
                .includes(q);
        });
    }

    function lockScroll() {
        document.documentElement.classList.add('fc-admin-scroll-lock');
        document.body.classList.add('fc-admin-scroll-lock');
        var main = document.getElementById('fc-admin-main');
        if (main) {
            main.classList.add('fc-admin-scroll-lock');
        }
    }

    function unlockScroll() {
        document.documentElement.classList.remove('fc-admin-scroll-lock');
        document.body.classList.remove('fc-admin-scroll-lock');
        var main = document.getElementById('fc-admin-main');
        if (main) {
            main.classList.remove('fc-admin-scroll-lock');
        }
    }

    function clearModalRoot() {
        var root = document.getElementById('fc-admin-modal-root');
        if (!root) {
            return;
        }
        if (!root.querySelector('.fc-media-picker') && !root.querySelector('.fc-gallery-modal')) {
            root.classList.remove('fc-gallery-modal-root--active');
            root.setAttribute('aria-hidden', 'true');
        }
    }

    function close() {
        if (pickerState.modalEl && pickerState.modalEl.parentNode) {
            pickerState.modalEl.parentNode.removeChild(pickerState.modalEl);
        }
        pickerState.modalEl = null;
        pickerState.open = false;
        pickerState.onSelect = null;
        pickerState.loadError = null;
        pickerState.uploading = false;
        pickerState.uploadQueue = null;
        unlockScroll();
        clearModalRoot();
    }

    function selectItem(path) {
        var callback = pickerState.onSelect;
        close();
        if (typeof callback === 'function') {
            callback(path);
        }
    }

    function renderGrid(items) {
        if (pickerState.loadError) {
            return (
                '<div class="fc-media-picker__empty fc-media-picker__empty--error">' +
                '<p>' +
                escapeHtml(pickerState.loadError) +
                '</p></div>'
            );
        }

        if (!items.length) {
            return (
                '<div class="fc-media-picker__empty">' +
                '<i class="fa-regular fa-image" aria-hidden="true"></i>' +
                '<p>No images found.</p>' +
                (pickerState.canUpload
                    ? '<p class="fc-media-picker__empty-hint">Switch to <strong>Add New</strong> to upload a file.</p>'
                    : '') +
                '</div>'
            );
        }

        return (
            '<ul class="fc-media-picker__grid">' +
            items
                .map(function (item) {
                    return (
                        '<li class="fc-media-picker__item">' +
                        '<button type="button" class="fc-media-picker__pick" data-fc-media-pick="' +
                        escapeHtml(item.path) +
                        '" title="' +
                        escapeHtml(item.name) +
                        '">' +
                        '<span class="fc-media-picker__thumb">' +
                        '<img alt="" class="fc-lazy" data-fc-lazy data-fc-lazy-src="' +
                        escapeHtml(assetUrl(item.path)) +
                        '" decoding="async">' +
                        '</span>' +
                        '<span class="fc-media-picker__name">' +
                        escapeHtml(item.name) +
                        '</span></button></li>'
                    );
                })
                .join('') +
            '</ul>'
        );
    }

    function renderDropzone(full, compact) {
        var uploadText = compact ? 'Add more files' : 'Drop files here to upload';
        var hintText = compact
            ? 'Drop files here or click to browse'
            : 'or click anywhere in this area to browse your computer';

        return (
            '<div class="fc-media-picker__dropzone' +
            (full ? ' fc-media-picker__dropzone--full' : '') +
            (compact ? ' fc-media-picker__dropzone--compact' : '') +
            '" data-fc-media-picker-dropzone tabindex="0" role="button" aria-label="Upload files">' +
            '<i class="fa-solid fa-cloud-arrow-up fc-media-picker__dropzone-icon" aria-hidden="true"></i>' +
            '<p class="fc-media-picker__dropzone-title">' +
            escapeHtml(uploadText) +
            '</p>' +
            '<p class="fc-media-picker__dropzone-text">' +
            escapeHtml(hintText) +
            '</p>' +
            (full && !compact
                ? '<p class="fc-media-picker__dropzone-hint">JPG, PNG, GIF, WebP, or SVG · saved to <code>public/assets/uploads</code></p>'
                : '') +
            '<input type="file" class="fc-media-picker__file-input" accept="' +
            ACCEPT_TYPES +
            '" multiple hidden>' +
            '</div>'
        );
    }

    function ensureUploadQueue() {
        if (pickerState.uploadQueue) {
            // Keep the queue's token in sync — it's created once and cached, but the
            // CSRF token can be (re)hydrated later (e.g. re-login without a full page
            // reload), and a stale token here causes every upload to fail with
            // "Invalid security token" even though the page itself is fine.
            pickerState.uploadQueue.csrf = pickerState.csrf;
            return pickerState.uploadQueue;
        }
        if (!global.FcGalleryUploadQueue) {
            return pickerState.uploadQueue;
        }

        pickerState.uploadQueue = global.FcGalleryUploadQueue.create({
            apiUrl: API_UPLOAD,
            csrf: pickerState.csrf,
            onChange: function (event) {
                if (!pickerState.modalEl || pickerState.activeTab !== 'upload') {
                    return;
                }
                if (event.type === 'structure') {
                    renderUploadPanel();
                    return;
                }
                if (event.type === 'progress' && event.item) {
                    pickerState.uploadQueue.patchCard(event.item);
                }
            },
            onAllSettled: function (summary) {
                pickerState.uploading = pickerState.uploadQueue ? pickerState.uploadQueue.hasActive() : false;

                var T = global.FcAdminToast;
                if (T && !summary.uploaded && !summary.failed) {
                    T.dismiss(TOAST_PICKER);
                }

                if (summary.uploaded) {
                    toast(
                        'ok',
                        summary.uploaded + ' file' + (summary.uploaded === 1 ? '' : 's') + ' uploaded.',
                        TOAST_PICKER
                    );
                    if (pickerState.uploadQueue) {
                        pickerState.uploadQueue.clearDone();
                    }
                    if (!pickerState.canList) {
                        renderUploadPanel();
                        return;
                    }
                    pickerState.activeTab = 'library';
                    loadItems()
                        .then(function () {
                            refreshPickerView();
                        })
                        .catch(function (err) {
                            pickerState.loadError = err.message || 'Could not refresh media library.';
                            refreshPickerView();
                            toast('error', err.message || 'Could not refresh media library.', TOAST_PICKER);
                        });
                    return;
                }

                if (summary.failed) {
                    toast('error', 'Upload failed.', TOAST_PICKER);
                }

                if (!summary.uploaded) {
                    renderUploadPanel();
                }
            }
        });

        return pickerState.uploadQueue;
    }

    function uploadHasActive() {
        return pickerState.uploadQueue ? pickerState.uploadQueue.hasActive() : pickerState.uploading;
    }

    function renderUploadPanel() {
        if (!pickerState.modalEl) {
            return;
        }
        var panel = pickerState.modalEl.querySelector('.fc-media-picker__upload-panel');
        if (!panel) {
            refreshPickerView();
            return;
        }
        panel.innerHTML = renderUploadBodyInner();
        bindUploadPanelEvents(panel);
    }

    function renderUploadBodyInner() {
        var queue = ensureUploadQueue();
        var hasQueue = queue && queue.hasItems();
        return renderDropzone(true, hasQueue) + (hasQueue ? queue.renderQueue() : '');
    }

    function renderTabBar() {
        var tabs = [];
        if (pickerState.canList) {
            tabs.push({ id: 'library', label: 'Media Library' });
        }
        if (pickerState.canUpload) {
            tabs.push({ id: 'upload', label: 'Add New' });
        }
        if (!tabs.length) {
            return '';
        }

        return (
            '<div class="fc-media-picker__tabs" role="tablist" aria-label="Media picker sections">' +
            tabs
                .map(function (tab) {
                    var active = pickerState.activeTab === tab.id;
                    return (
                        '<button type="button" role="tab" data-fc-media-picker-tab="' +
                        escapeHtml(tab.id) +
                        '" aria-selected="' +
                        (active ? 'true' : 'false') +
                        '" class="fc-media-picker__tab' +
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

    function renderLibraryToolbar(items) {
        return (
            '<div class="fc-media-picker__toolbar" data-fc-media-picker-toolbar>' +
            '<div class="fc-media-picker__search-wrap">' +
            '<i class="fa-solid fa-magnifying-glass fc-media-picker__search-icon" aria-hidden="true"></i>' +
            '<input type="search" class="fc-media-picker__search" placeholder="Search media…" value="' +
            escapeHtml(pickerState.search) +
            '" autocomplete="off">' +
            '</div>' +
            '<span class="fc-media-picker__count">' +
            items.length +
            ' image' +
            (items.length === 1 ? '' : 's') +
            '</span></div>'
        );
    }

    function renderLibraryBody(items) {
        return (
            '<div class="fc-media-picker__panel-library" role="tabpanel">' +
            '<div class="fc-media-picker__grid-wrap">' +
            renderGrid(items) +
            '</div></div>'
        );
    }

    function renderUploadBody() {
        return '<div class="fc-media-picker__upload-panel">' + renderUploadBodyInner() + '</div>';
    }

    function bindUploadPanelEvents(panel) {
        if (!panel) {
            return;
        }

        var queue = ensureUploadQueue();
        if (queue) {
            queue.bind(panel);
        }

        var fileInput = panel.querySelector('.fc-media-picker__file-input');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length) {
                    uploadFiles(fileInput.files);
                    fileInput.value = '';
                }
            });
        }

        var dropzone = panel.querySelector('[data-fc-media-picker-dropzone]');
        if (!dropzone) {
            return;
        }

        dropzone.addEventListener('click', function (e) {
            if (e.target.closest('.fc-upload-card__action')) {
                return;
            }
            if (e.target.closest('.fc-media-picker__file-input')) {
                return;
            }
            var input = dropzone.querySelector('.fc-media-picker__file-input') || fileInput;
            if (input) {
                input.click();
            }
        });

        dropzone.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var input = dropzone.querySelector('.fc-media-picker__file-input') || fileInput;
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

    function renderBody(items) {
        if (pickerState.activeTab === 'upload' && pickerState.canUpload) {
            return (
                '<div class="fc-media-picker__body fc-media-picker__body--upload">' +
                renderUploadBody() +
                '</div>'
            );
        }

        return (
            '<div class="fc-media-picker__body" data-fc-lazy-root>' +
            renderLibraryBody(items) +
            '</div>'
        );
    }

    function renderModal() {
        var items = filteredItems();

        return (
            '<div id="' +
            MODAL_ID +
            '" class="fc-media-picker" role="dialog" aria-modal="true" aria-labelledby="fc-media-picker-title">' +
            '<div class="fc-media-picker__backdrop" data-fc-media-picker-close aria-hidden="true"></div>' +
            '<div class="fc-media-picker__panel">' +
            '<button type="button" class="fencing-modal-close" data-fc-media-picker-close aria-label="Close"></button>' +
            '<header class="fc-media-picker__head">' +
            '<h2 id="fc-media-picker-title" class="fc-media-picker__title">Select image</h2>' +
            '<p class="fc-media-picker__desc">' +
            escapeHtml(pickerDescription()) +
            '</p>' +
            '</header>' +
            renderTabBar() +
            (pickerState.activeTab === 'library' && pickerState.canList
                ? renderLibraryToolbar(items)
                : '') +
            renderBody(items) +
            '</div></div>'
        );
    }

    function syncTabUi() {
        if (!pickerState.modalEl) {
            return;
        }

        pickerState.modalEl.querySelectorAll('[data-fc-media-picker-tab]').forEach(function (btn) {
            var active = btn.getAttribute('data-fc-media-picker-tab') === pickerState.activeTab;
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.classList.toggle('is-active', active);
        });
    }

    function switchTab(tabId) {
        if (tabId !== 'library' && tabId !== 'upload') {
            return;
        }
        if (tabId === 'library' && !pickerState.canList) {
            return;
        }
        if (tabId === 'upload' && !pickerState.canUpload) {
            return;
        }
        if (uploadHasActive() && tabId === 'library') {
            return;
        }
        pickerState.activeTab = tabId;
        refreshPickerView();
    }

    function bindSearchInput() {
        if (!pickerState.modalEl) {
            return;
        }
        var searchInput = pickerState.modalEl.querySelector('.fc-media-picker__search');
        if (!searchInput) {
            return;
        }
        searchInput.addEventListener('input', function () {
            pickerState.search = searchInput.value;
            var gridWrap = pickerState.modalEl.querySelector('.fc-media-picker__grid-wrap');
            var countEl = pickerState.modalEl.querySelector('.fc-media-picker__count');
            var items = filteredItems();
            if (gridWrap) {
                gridWrap.innerHTML = renderGrid(items);
                bindPickButtons();
                initPickerLazy();
            }
            if (countEl) {
                countEl.textContent = items.length + ' image' + (items.length === 1 ? '' : 's');
            }
        });
    }

    function initPickerLazy() {
        if (!pickerState.modalEl || !global.FcLazy || pickerState.activeTab !== 'library') {
            return;
        }
        var root = pickerState.modalEl.querySelector('.fc-media-picker__body[data-fc-lazy-root]');
        if (root) {
            global.FcLazy.refresh(root, { root: root });
        }
    }

    function refreshPickerView() {
        if (!pickerState.modalEl) {
            return;
        }

        var items = filteredItems();
        var panel = pickerState.modalEl.querySelector('.fc-media-picker__panel');
        if (!panel) {
            return;
        }

        var tabs = panel.querySelector('.fc-media-picker__tabs');
        var toolbar = panel.querySelector('[data-fc-media-picker-toolbar]');
        var body = panel.querySelector('.fc-media-picker__body');
        var desc = panel.querySelector('.fc-media-picker__desc');

        if (desc) {
            desc.textContent = pickerDescription();
        }

        if (tabs) {
            tabs.outerHTML = renderTabBar();
        } else {
            var head = panel.querySelector('.fc-media-picker__head');
            if (head) {
                head.insertAdjacentHTML('afterend', renderTabBar());
            }
        }

        if (pickerState.activeTab === 'library') {
            if (toolbar) {
                toolbar.outerHTML = renderLibraryToolbar(items);
            } else {
                var tabsEl = panel.querySelector('.fc-media-picker__tabs');
                if (tabsEl) {
                    tabsEl.insertAdjacentHTML('afterend', renderLibraryToolbar(items));
                }
            }
            bindSearchInput();
        } else if (toolbar) {
            toolbar.remove();
        }

        if (body) {
            if (pickerState.activeTab === 'upload') {
                body.className = 'fc-media-picker__body fc-media-picker__body--upload';
                body.removeAttribute('data-fc-lazy-root');
                body.innerHTML = renderUploadBody();
            } else {
                body.className = 'fc-media-picker__body';
                body.setAttribute('data-fc-lazy-root', '');
                body.innerHTML = renderLibraryBody(items);
            }
        }

        syncTabUi();
        if (pickerState.modalEl) {
            pickerState.modalEl.classList.toggle('fc-media-picker--upload', pickerState.activeTab === 'upload');
        }
        if (pickerState.activeTab === 'upload') {
            bindUploadPanelEvents(body ? body.querySelector('.fc-media-picker__upload-panel') : null);
        } else {
            bindPickButtons();
            initPickerLazy();
        }
    }

    function loadItems() {
        if (!pickerState.canList) {
            pickerState.items = [];
            pickerState.loadError = null;
            return Promise.resolve([]);
        }

        return fetch(API_LIST, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().then(function (body) {
                applyCapsFromPayload(body);
                if (!res.ok || !body.ok) {
                    throw new Error((body && body.error) || 'Could not load media library.');
                }
                pickerState.items = Array.isArray(body.items) ? body.items : [];
                pickerState.loadError = null;
                if (pickerState.activeTab === 'library' && !pickerState.canList) {
                    pickerState.activeTab = defaultTab();
                }
                if (pickerState.activeTab === 'upload' && !pickerState.canUpload) {
                    pickerState.activeTab = defaultTab();
                }
                return pickerState.items;
            });
        });
    }

    function uploadFiles(fileList, options) {
        options = options || {};
        var files = Array.prototype.slice.call(fileList || []);
        if (!files.length) {
            return;
        }
        if (!pickerState.canUpload) {
            toast('error', 'You do not have permission to upload media.');
            return;
        }

        var queue = ensureUploadQueue();
        if (!queue) {
            return;
        }

        pickerState.activeTab = 'upload';
        pickerState.uploading = true;

        if (pickerState.modalEl && pickerState.modalEl.querySelector('.fc-media-picker__upload-panel')) {
            if (pickerState.modalEl) {
                pickerState.modalEl.classList.add('fc-media-picker--upload');
            }
            renderUploadPanel();
        } else {
            refreshPickerView();
        }

        toast('saving', 'Uploading…', TOAST_PICKER);
        queue.addFiles(files);
    }

    function bindUploadEvents() {
        if (!pickerState.modalEl) {
            return;
        }
        if (pickerState.activeTab === 'upload') {
            bindUploadPanelEvents(pickerState.modalEl.querySelector('.fc-media-picker__upload-panel'));
        }
    }

    function bindModalEvents() {
        if (!pickerState.modalEl) {
            return;
        }

        pickerState.modalEl.querySelectorAll('[data-fc-media-picker-close]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });

        pickerState.modalEl.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                close();
            }
        });

        if (!pickerState.modalEl.getAttribute('data-fc-mp-shell-bound')) {
            pickerState.modalEl.setAttribute('data-fc-mp-shell-bound', '1');
            pickerState.modalEl.addEventListener('click', function (e) {
                var tabBtn = e.target.closest('[data-fc-media-picker-tab]');
                if (tabBtn) {
                    switchTab(tabBtn.getAttribute('data-fc-media-picker-tab'));
                }
            });
        }

        bindSearchInput();
        bindUploadEvents();
        bindPickButtons();

        var closeBtn = pickerState.modalEl.querySelector('.fencing-modal-close');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function bindPickButtons() {
        if (!pickerState.modalEl) {
            return;
        }
        pickerState.modalEl.querySelectorAll('[data-fc-media-pick]').forEach(function (btn) {
            if (btn.getAttribute('data-fc-mp-bound') === '1') {
                return;
            }
            btn.setAttribute('data-fc-mp-bound', '1');
            btn.addEventListener('click', function () {
                selectItem(btn.getAttribute('data-fc-media-pick'));
            });
        });
    }

    function ensureRoot() {
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

    function open(options) {
        options = options || {};
        close();

        var caps = readMediaCaps(options);
        if (!caps.canList && !caps.canUpload) {
            toast('error', 'You do not have permission to use the media library.');
            return;
        }

        pickerState.canList = caps.canList;
        pickerState.canUpload = caps.canUpload;
        pickerState.appBase =
            options.appBase ||
            (document.body && document.body.getAttribute('data-fc-app-base')) ||
            '';
        pickerState.csrf = typeof options.csrf === 'string' ? options.csrf : '';
        pickerState.onSelect = typeof options.onSelect === 'function' ? options.onSelect : null;
        pickerState.search = '';
        pickerState.uploading = false;
        pickerState.uploadQueue = null;
        pickerState.activeTab = defaultTab();
        pickerState.loadError = null;
        pickerState.items = [];
        pickerState.open = true;

        var root = ensureRoot();
        root.insertAdjacentHTML('beforeend', renderModal());
        pickerState.modalEl = document.getElementById(MODAL_ID);
        lockScroll();
        bindModalEvents();

        loadItems()
            .then(function () {
                refreshPickerView();
            })
            .catch(function (err) {
                pickerState.loadError = err.message || 'Could not load media library.';
                refreshPickerView();
            });
    }

    global.FcAdminMediaPicker = {
        open: open,
        close: close
    };
})(window);
