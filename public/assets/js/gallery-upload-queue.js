/**
 * FC Admin — media upload queue (progress cards + cancel).
 */
(function (global) {
    'use strict';

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
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

    function createId() {
        return 'fc-up-' + Date.now() + '-' + Math.random().toString(36).slice(2, 9);
    }

    function fileTypeBadge(file) {
        var mime = String((file && file.type) || '').toLowerCase();
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
        var ext = String((file && file.name) || '')
            .split('.')
            .pop();
        return ext ? ext.toUpperCase() : 'IMG';
    }

    function UploadQueue(options) {
        this.apiUrl = options.apiUrl;
        this.csrf = typeof options.csrf === 'string' ? options.csrf : '';
        this.items = [];
        this.root = null;
        this.onChange = typeof options.onChange === 'function' ? options.onChange : function () {};
        this.onAllSettled = typeof options.onAllSettled === 'function' ? options.onAllSettled : function () {};
        this._batchId = 0;
        this._activeBatch = 0;
        this._settledBatches = {};
    }

    UploadQueue.prototype.hasActive = function () {
        return this.items.some(function (item) {
            return item.status === 'queued' || item.status === 'uploading';
        });
    };

    UploadQueue.prototype.hasItems = function () {
        return this.items.length > 0;
    };

    UploadQueue.prototype.find = function (id) {
        return this.items.find(function (item) {
            return item.id === id;
        }) || null;
    };

    UploadQueue.prototype.revokePreview = function (item) {
        if (item && item.previewUrl) {
            try {
                URL.revokeObjectURL(item.previewUrl);
            } catch (e) {
                /* ignore */
            }
            item.previewUrl = null;
        }
    };

    UploadQueue.prototype.remove = function (id) {
        var item = this.find(id);
        if (!item) {
            return;
        }
        var batchId = item.batchId;
        if (item.status === 'uploading' && item.xhr) {
            item._cancelRequested = true;
            item.xhr.abort();
        }
        this.revokePreview(item);
        this.items = this.items.filter(function (entry) {
            return entry.id !== id;
        });
        this.onChange({ type: 'structure', queue: this });
        this._checkBatchSettled(batchId);
    };

    UploadQueue.prototype.cancel = function (id) {
        this.remove(id);
    };

    UploadQueue.prototype.clearDone = function () {
        this.items.forEach(
            function (item) {
                if (item.status === 'done' || item.status === 'error' || item.status === 'cancelled') {
                    this.revokePreview(item);
                }
            }.bind(this)
        );
        this.items = this.items.filter(function (item) {
            return item.status === 'queued' || item.status === 'uploading';
        });
        this.onChange({ type: 'structure', queue: this });
    };

    UploadQueue.prototype.getSuccessfulPaths = function () {
        return this.items
            .filter(function (item) {
                return item.status === 'done' && item.itemPath;
            })
            .map(function (item) {
                return item.itemPath;
            });
    };

    UploadQueue.prototype.statusLabel = function (item) {
        if (item.status === 'queued') {
            return 'Waiting…';
        }
        if (item.status === 'uploading') {
            return 'Uploading… ' + (item.progress || 0) + '%';
        }
        if (item.status === 'done') {
            return 'Uploaded';
        }
        if (item.status === 'error') {
            return item.error || 'Upload failed';
        }
        if (item.status === 'cancelled') {
            return 'Cancelled';
        }
        return '';
    };

    UploadQueue.prototype.actionLabel = function (item) {
        if (item.status === 'uploading' || item.status === 'queued') {
            return 'Cancel upload';
        }
        return 'Remove';
    };

    UploadQueue.prototype.renderCard = function (item) {
        var canAct = item.status !== 'done' || true;
        var showProgress = item.status === 'uploading' || item.status === 'queued';
        var pct = item.status === 'done' ? 100 : item.progress || 0;

        return (
            '<li class="fc-upload-card' +
            (item.status === 'uploading' ? ' is-uploading' : '') +
            (item.status === 'done' ? ' is-done' : '') +
            (item.status === 'error' ? ' is-error' : '') +
            (item.status === 'cancelled' ? ' is-cancelled' : '') +
            '" data-fc-upload-id="' +
            escapeHtml(item.id) +
            '">' +
            '<div class="fc-upload-card__thumb">' +
            '<span class="fc-upload-card__badge">' +
            escapeHtml(fileTypeBadge(item.file)) +
            '</span>' +
            (item.previewUrl
                ? '<img src="' + escapeHtml(item.previewUrl) + '" alt="" decoding="async">'
                : '<span class="fc-upload-card__placeholder" aria-hidden="true"><i class="fa-regular fa-image"></i></span>') +
            (item.status === 'done'
                ? '<span class="fc-upload-card__done-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>'
                : '') +
            '</div>' +
            '<div class="fc-upload-card__body">' +
            '<p class="fc-upload-card__name" title="' +
            escapeHtml(item.name) +
            '">' +
            escapeHtml(item.name) +
            '</p>' +
            '<p class="fc-upload-card__meta">' +
            escapeHtml(formatBytes(item.size)) +
            '</p>' +
            (showProgress
                ? '<div class="fc-upload-card__progress" data-fc-upload-progress role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' +
                  pct +
                  '" aria-label="Upload progress for ' +
                  escapeHtml(item.name) +
                  '">' +
                  '<span class="fc-upload-card__progress-bar" data-fc-upload-progress-bar style="width:' +
                  pct +
                  '%"></span></div>'
                : '') +
            '<p class="fc-upload-card__status" data-fc-upload-status>' +
            escapeHtml(this.statusLabel(item)) +
            '</p></div>' +
            (canAct
                ? '<button type="button" class="fc-upload-card__action" data-fc-upload-cancel="' +
                  escapeHtml(item.id) +
                  '" aria-label="' +
                  escapeHtml(this.actionLabel(item)) +
                  '" title="' +
                  escapeHtml(this.actionLabel(item)) +
                  '">' +
                  '<i class="fa-solid fa-xmark" aria-hidden="true"></i></button>'
                : '') +
            '</li>'
        );
    };

    UploadQueue.prototype.renderQueue = function () {
        if (!this.items.length) {
            return '';
        }

        return (
            '<ul class="fc-upload-queue" data-fc-upload-queue aria-live="polite">' +
            this.items.map(this.renderCard.bind(this)).join('') +
            '</ul>'
        );
    };

    UploadQueue.prototype.patchCard = function (item) {
        if (!this.root) {
            return;
        }
        var card = this.root.querySelector('[data-fc-upload-id="' + item.id + '"]');
        if (!card) {
            return;
        }

        var pct = item.status === 'done' ? 100 : item.progress || 0;
        var progress = card.querySelector('[data-fc-upload-progress]');
        var bar = card.querySelector('[data-fc-upload-progress-bar]');
        var status = card.querySelector('[data-fc-upload-status]');
        var action = card.querySelector('[data-fc-upload-cancel]');

        if (progress) {
            progress.setAttribute('aria-valuenow', String(pct));
        }
        if (bar) {
            bar.style.width = pct + '%';
        }
        if (status) {
            status.textContent = this.statusLabel(item);
        }
        if (action) {
            action.setAttribute('aria-label', this.actionLabel(item));
            action.setAttribute('title', this.actionLabel(item));
        }

        card.classList.toggle('is-uploading', item.status === 'uploading');
        card.classList.toggle('is-done', item.status === 'done');
        card.classList.toggle('is-error', item.status === 'error');
        card.classList.toggle('is-cancelled', item.status === 'cancelled');
    };

    UploadQueue.prototype.setRoot = function (root) {
        this.root = root || null;
    };

    UploadQueue.prototype.bind = function (root) {
        this.setRoot(root);
        if (!root) {
            return;
        }

        if (!root.getAttribute('data-fc-upload-bound')) {
            root.setAttribute('data-fc-upload-bound', '1');
            root.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-fc-upload-cancel]');
                if (!btn || !root.contains(btn)) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                this.cancel(btn.getAttribute('data-fc-upload-cancel'));
            }.bind(this));
        }
    };

    UploadQueue.prototype._notifyStructure = function () {
        this.onChange({ type: 'structure', queue: this });
    };

    UploadQueue.prototype._notifyProgress = function (item) {
        this.patchCard(item);
        this.onChange({ type: 'progress', item: item, queue: this });
    };

    UploadQueue.prototype._finishBatch = function (batchId, summary) {
        if (this._settledBatches[batchId]) {
            return;
        }
        this._settledBatches[batchId] = true;
        this.onAllSettled(summary);
    };

    UploadQueue.prototype._checkBatchSettled = function (batchId) {
        if (batchId !== this._activeBatch || this._settledBatches[batchId]) {
            return;
        }

        var batchItems = this.items.filter(function (item) {
            return item.batchId === batchId;
        });
        if (!batchItems.length) {
            this._finishBatch(batchId, {
                batchId: batchId,
                uploaded: 0,
                failed: 0,
                cancelled: 0,
                paths: []
            });
            return;
        }

        var pending = batchItems.some(function (item) {
            return item.status === 'queued' || item.status === 'uploading';
        });
        if (pending) {
            return;
        }

        var uploaded = batchItems.filter(function (item) {
            return item.status === 'done';
        }).length;
        var failed = batchItems.filter(function (item) {
            return item.status === 'error';
        }).length;
        var cancelled = batchItems.filter(function (item) {
            return item.status === 'cancelled';
        }).length;
        var paths = batchItems
            .filter(function (item) {
                return item.status === 'done' && item.itemPath;
            })
            .map(function (item) {
                return item.itemPath;
            });

        this._finishBatch(batchId, {
            batchId: batchId,
            uploaded: uploaded,
            failed: failed,
            cancelled: cancelled,
            paths: paths
        });
    };

    UploadQueue.prototype._startUpload = function (item) {
        if (!item || item.status !== 'queued') {
            return;
        }

        item.status = 'uploading';
        item.progress = 0;
        this._notifyProgress(item);

        var xhr = new XMLHttpRequest();
        item.xhr = xhr;
        var formData = new FormData();
        formData.append('file', item.file);
        if (this.csrf) {
            formData.append('csrf', this.csrf);
        }

        xhr.upload.addEventListener(
            'progress',
            function (e) {
                if (!e.lengthComputable) {
                    return;
                }
                item.progress = Math.min(100, Math.round((e.loaded / e.total) * 100));
                this._notifyProgress(item);
            }.bind(this)
        );

        xhr.addEventListener(
            'load',
            function () {
                var current = this.find(item.id);
                item.xhr = null;
                if (!current) {
                    this._checkBatchSettled(item.batchId);
                    return;
                }

                var body = null;
                try {
                    body = JSON.parse(xhr.responseText || '{}');
                } catch (parseErr) {
                    body = null;
                }

                if (current._cancelRequested) {
                    current.status = 'cancelled';
                    this._notifyStructure();
                    this._checkBatchSettled(current.batchId);
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300 && body && body.ok) {
                    current.status = 'done';
                    current.progress = 100;
                    current.itemPath = body.item && body.item.path ? body.item.path : null;
                    this._notifyProgress(current);
                    this._checkBatchSettled(current.batchId);
                    return;
                }

                current.status = 'error';
                current.error = (body && body.error) || 'Upload failed.';
                this._notifyProgress(current);
                this._checkBatchSettled(current.batchId);
            }.bind(this)
        );

        xhr.addEventListener(
            'error',
            function () {
                var current = this.find(item.id);
                item.xhr = null;
                if (!current) {
                    this._checkBatchSettled(item.batchId);
                    return;
                }
                if (current._cancelRequested) {
                    current.status = 'cancelled';
                } else {
                    current.status = 'error';
                    current.error = 'Network error.';
                }
                this._notifyProgress(current);
                this._checkBatchSettled(current.batchId);
            }.bind(this)
        );

        xhr.addEventListener(
            'abort',
            function () {
                var current = this.find(item.id);
                item.xhr = null;
                if (!current) {
                    this._checkBatchSettled(item.batchId);
                    return;
                }
                current.status = 'cancelled';
                this._notifyProgress(current);
                this._checkBatchSettled(current.batchId);
            }.bind(this)
        );

        xhr.open('POST', this.apiUrl, true);
        xhr.withCredentials = true;
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    };

    UploadQueue.prototype.addFiles = function (fileList) {
        var files = Array.prototype.slice.call(fileList || []);
        if (!files.length) {
            return;
        }

        this._batchId += 1;
        var batchId = this._batchId;
        this._activeBatch = batchId;
        this._settledBatches[batchId] = false;

        files.forEach(
            function (file) {
                var previewUrl = null;
                try {
                    if (file && String(file.type || '').indexOf('image/') === 0) {
                        previewUrl = URL.createObjectURL(file);
                    }
                } catch (previewErr) {
                    previewUrl = null;
                }

                this.items.push({
                    id: createId(),
                    batchId: batchId,
                    file: file,
                    name: file.name || 'file',
                    size: file.size || 0,
                    status: 'queued',
                    progress: 0,
                    xhr: null,
                    previewUrl: previewUrl,
                    itemPath: null,
                    error: null,
                    _cancelRequested: false
                });
            }.bind(this)
        );

        this._notifyStructure();

        this.items
            .filter(function (item) {
                return item.batchId === batchId && item.status === 'queued';
            })
            .forEach(
                function (item) {
                    this._startUpload(item);
                }.bind(this)
            );
    };

    global.FcGalleryUploadQueue = {
        create: function (options) {
            return new UploadQueue(options || {});
        },
        escapeHtml: escapeHtml,
        formatBytes: formatBytes
    };
})(window);
