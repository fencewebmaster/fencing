/**
 * FC Admin — Settings → Fence Colors tab.
 * Extracted from settings.js. previewUrl() is also called from the
 * Integrations and Project Plan tabs (still in settings.js) for their own
 * image-preview thumbnails.
 */
(function (global) {
    'use strict';

    var API_FENCE_COLORS = global.fcApiUrl('settings', 'action=fence-colors');
    var TOAST_FENCE_COLORS = 'fc-fence-colors-save';

    var FENCE_COLOR_SORT_COLUMNS = [
        { id: 'slug', label: 'Slug' },
        { id: 'label', label: 'Label' },
        { id: 'subLabel', label: 'Sub label' },
        { id: 'color', label: 'Color' },
        { id: 'image', label: 'Image' }
    ];

    class FenceColorsTabController extends global.FC.Settings.TabController {
        constructor() {
            super();
            this.tableBound = false;
        }

        setDirty(isDirty) {
            this.state.fenceColorsDirty = !!isDirty;
            this.updateHeaderActions();
        }

        clone(list) {
            return (list || []).map(function (row) {
                return {
                    slug: row.slug || '',
                    label: row.label || '',
                    subLabel: row.subLabel || row.sub_label || '',
                    color: row.color || '',
                    image: row.image || ''
                };
            });
        }

        getOriginalSlugSet() {
            var slugs = Object.create(null);
            (this.state.fenceColorsDefaults || []).forEach(function (row) {
                var slug = String(row && row.slug ? row.slug : '').trim();
                if (slug) {
                    slugs[slug] = true;
                }
            });
            return slugs;
        }

        isOriginalSlug(slug) {
            return !!this.getOriginalSlugSet()[String(slug || '').trim()];
        }

        sortValue(row, column) {
            return String(row[column] || '').trim().toLowerCase();
        }

        sortInPlace() {
            var self = this;
            var sort = this.state.fenceColorsSort;
            if (!sort || !sort.column) {
                return;
            }
            var column = sort.column;
            var direction = sort.direction === 'desc' ? -1 : 1;
            this.state.fenceColors.sort(function (a, b) {
                var aVal = self.sortValue(a, column);
                var bVal = self.sortValue(b, column);
                if (aVal < bVal) {
                    return -1 * direction;
                }
                if (aVal > bVal) {
                    return 1 * direction;
                }
                return self.sortValue(a, 'slug').localeCompare(self.sortValue(b, 'slug'));
            });
        }

        sortIcon(column) {
            var sort = this.state.fenceColorsSort || {};
            if (sort.column !== column) {
                return 'fa-sort';
            }
            return sort.direction === 'desc' ? 'fa-sort-down' : 'fa-sort-up';
        }

        renderSortHeaderCell(column, label) {
            var escapeHtml = global.FC.util.escapeHtml;
            var sort = this.state.fenceColorsSort || {};
            var active = sort.column === column;
            return (
                '<button type="button" class="fc-fs-kv-table__col fc-settings-fence-colors__sort-col' +
                (active ? ' is-active' : '') +
                '" data-fc-fence-color-sort="' +
                escapeHtml(column) +
                '" aria-label="Sort by ' +
                escapeHtml(label) +
                (active ? ', ' + (sort.direction === 'desc' ? 'descending' : 'ascending') : '') +
                '">' +
                '<span>' +
                escapeHtml(label) +
                '</span>' +
                '<i class="fa-solid ' +
                this.sortIcon(column) +
                ' fc-settings-fence-colors__sort-icon" aria-hidden="true"></i></button>'
            );
        }

        renderTableHead() {
            var self = this;
            return (
                '<div class="fc-fs-kv-table__head" data-fc-fence-colors-head>' +
                '<span class="fc-fs-kv-table__grip" aria-hidden="true"></span>' +
                '<span class="fc-fs-kv-table__col fc-settings-fence-colors__head-preview" aria-hidden="true"></span>' +
                FENCE_COLOR_SORT_COLUMNS.map(function (col) {
                    return self.renderSortHeaderCell(col.id, col.label);
                }).join('') +
                '<span class="fc-fs-kv-table__actions" aria-hidden="true"></span></div>'
            );
        }

        setSort(column) {
            var sort = this.state.fenceColorsSort || { column: null, direction: 'asc' };
            if (sort.column === column) {
                sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                sort.column = column;
                sort.direction = 'asc';
            }
            this.state.fenceColorsSort = sort;
            this.sortInPlace();
            this.refreshTable();
            this.setDirty(true);
        }

        rowBackground(row) {
            var image = String(row.image || '').trim();
            var color = String(row.color || '').trim();
            if (image) {
                return /^url\(/i.test(image) ? image : 'url(' + image + ')';
            }
            return color || '#e2e8f0';
        }

        pickerValue(color) {
            var normalized = this.normalizeHexInput(color);
            return normalized || '#cccccc';
        }

        previewUrl(row, appBase) {
            var image = String(row.image || '').trim();
            if (!image) {
                return '';
            }
            if (/^https?:\/\//i.test(image) || /^data:/i.test(image) || /^\/\//.test(image)) {
                return image;
            }
            if (/^url\(/i.test(image)) {
                var inner = image.replace(/^url\(\s*/i, '').replace(/\s*\)\s*;?\s*$/, '');
                inner = inner.replace(/^['"]|['"]$/g, '');
                if (/^https?:\/\//i.test(inner) || /^data:/i.test(inner)) {
                    return inner;
                }
                return appBase ? appBase + '/' + inner.replace(/^\/+/, '') : inner;
            }
            return appBase ? appBase + '/' + image.replace(/^\/+/, '') : image;
        }

        renderRow(row, index, appBase) {
            var escapeHtml = global.FC.util.escapeHtml;
            var bg = this.rowBackground(row);
            var previewUrl = this.previewUrl(row, appBase);
            var previewInner = previewUrl
                ? '<img src="' + escapeHtml(previewUrl) + '" alt="" />'
                : '';
            var isOriginal = this.isOriginalSlug(row.slug);
            var slugInputAttrs = isOriginal
                ? ' readonly aria-readonly="true" title="Original color slugs cannot be changed"'
                : '';

            return (
                '<div class="fc-fs-kv-row fc-fs-kv-row--table' +
                (isOriginal ? ' fc-fs-kv-row--locked' : '') +
                '" data-fc-fence-color-row="' +
                index +
                '">' +
                '<span class="fc-fs-kv-row__grip" data-fc-fence-color-grip role="button" tabindex="0" aria-label="Drag to reorder" title="Drag to reorder">' +
                '<i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></span>' +
                '<span class="fc-settings-fence-colors__preview" data-fc-fence-color-preview="' +
                index +
                '" style="background:' +
                escapeHtml(bg) +
                '">' +
                previewInner +
                '</span>' +
                '<label class="fc-fs-gui-field fc-fs-kv-row__key">' +
                '<span class="fc-fs-gui-field__label">Slug</span>' +
                '<input type="text" class="fc-fs-input fc-fs-input--mono' +
                (isOriginal ? ' fc-fs-input--readonly' : '') +
                '" data-fc-fence-color-field="slug" data-fc-fence-color-index="' +
                index +
                '" value="' +
                escapeHtml(row.slug) +
                '" spellcheck="false" placeholder="monument_matt" autocomplete="off"' +
                slugInputAttrs +
                ' />' +
                '</label>' +
                '<label class="fc-fs-gui-field">' +
                '<span class="fc-fs-gui-field__label">Label</span>' +
                '<input type="text" class="fc-fs-input" data-fc-fence-color-field="label" data-fc-fence-color-index="' +
                index +
                '" value="' +
                escapeHtml(row.label) +
                '" placeholder="Black" autocomplete="off" />' +
                '</label>' +
                '<label class="fc-fs-gui-field">' +
                '<span class="fc-fs-gui-field__label">Sub label</span>' +
                '<input type="text" class="fc-fs-input" data-fc-fence-color-field="subLabel" data-fc-fence-color-index="' +
                index +
                '" value="' +
                escapeHtml(row.subLabel) +
                '" placeholder="Satin" autocomplete="off" />' +
                '</label>' +
                '<div class="fc-fs-gui-field fc-settings-fence-colors__color-cell">' +
                '<span class="fc-fs-gui-field__label">Color</span>' +
                '<div class="fc-settings-fence-colors__color-inputs">' +
                '<input type="color" class="fc-settings-fence-colors__picker" data-fc-fence-color-picker="' +
                index +
                '" value="' +
                escapeHtml(this.pickerValue(row.color)) +
                '" aria-label="Color picker" />' +
                '<input type="text" id="fc-fence-color-hex-' +
                index +
                '" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="color" data-fc-fence-color-index="' +
                index +
                '" value="' +
                escapeHtml(row.color) +
                '" spellcheck="false" placeholder="#6e6e6a" autocomplete="off" />' +
                global.FC.Settings.buildFieldCopyButton('fc-fence-color-hex-' + index, 'Color') +
                '</div></div>' +
                '<div class="fc-fs-gui-field fc-settings-fence-colors__image-cell">' +
                '<span class="fc-fs-gui-field__label">Image</span>' +
                '<div class="fc-settings-fence-colors__image-inputs">' +
                '<input type="text" class="fc-fs-input fc-fs-input--mono" data-fc-fence-color-field="image" data-fc-fence-color-index="' +
                index +
                '" value="' +
                escapeHtml(row.image) +
                '" spellcheck="false" placeholder="public/assets/img/… or URL" autocomplete="off" />' +
                '<button type="button" class="fc-settings-fence-colors__pick" data-fc-fence-color-pick="' +
                index +
                '" title="Set image" aria-label="Set image">' +
                '<i class="fa-solid fa-image" aria-hidden="true"></i></button>' +
                '</div></div>' +
                (isOriginal
                    ? '<span class="fc-fs-kv-row__remove fc-fs-kv-row__remove--disabled" aria-hidden="true" title="Original colors cannot be removed">' +
                      '<i class="fa-solid fa-trash-can" aria-hidden="true"></i></span>'
                    : '<button type="button" class="fc-fs-kv-row__remove" data-fc-fence-color-remove="' +
                      index +
                      '" aria-label="Remove">' +
                      '<i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>') +
                '</div>'
            );
        }

        renderTableBody(appBase) {
            var self = this;
            if (!this.state.fenceColors.length) {
                return (
                    '<div class="fc-settings-fence-colors__empty">No fence colors yet. Add one below.</div>'
                );
            }
            return this.state.fenceColors
                .map(function (row, index) {
                    return self.renderRow(row, index, appBase);
                })
                .join('');
        }

        updateRowPreview(index) {
            var row = this.state.fenceColors[index];
            var preview = document.querySelector('[data-fc-fence-color-preview="' + index + '"]');
            if (!preview || !row) {
                return;
            }
            var escapeHtml = global.FC.util.escapeHtml;
            var appBase = this.getAppBase();
            var bg = this.rowBackground(row);
            preview.style.background = bg;
            var previewUrl = this.previewUrl(row, appBase);
            if (previewUrl) {
                preview.innerHTML = '<img src="' + escapeHtml(previewUrl) + '" alt="" />';
            } else {
                preview.innerHTML = '';
            }
        }

        syncOrderFromDom() {
            var self = this;
            var tbody = document.getElementById('fc-fence-colors-tbody');
            if (!tbody) {
                return;
            }
            var rows = tbody.querySelectorAll('[data-fc-fence-color-row]');
            var next = [];
            rows.forEach(function (row) {
                var index = parseInt(row.getAttribute('data-fc-fence-color-row'), 10);
                if (!isNaN(index) && self.state.fenceColors[index]) {
                    next.push(self.clone([self.state.fenceColors[index]])[0]);
                }
            });
            if (next.length === this.state.fenceColors.length) {
                this.state.fenceColors = next;
            }
        }

        refreshTable() {
            var head = document.querySelector('[data-fc-fence-colors-head]');
            var tbody = document.getElementById('fc-fence-colors-tbody');
            if (head) {
                head.outerHTML = this.renderTableHead();
            }
            if (tbody) {
                tbody.innerHTML = this.renderTableBody(this.getAppBase());
            }
            this.bindTableEvents();
        }

        bindRowDragDrop(block) {
            var self = this;
            if (!block || block.getAttribute('data-fc-fc-drag-bound') === '1') {
                return;
            }
            block.setAttribute('data-fc-fc-drag-bound', '1');

            var dragRow = null;

            function clearDragOver() {
                block.querySelectorAll('.fc-fs-kv-row--drag-over').forEach(function (row) {
                    row.classList.remove('fc-fs-kv-row--drag-over');
                });
            }

            function resetDraggable() {
                block.querySelectorAll('.fc-fs-kv-row').forEach(function (row) {
                    row.draggable = false;
                });
            }

            function moveRowBeforeTarget(fromRow, targetRow) {
                if (!fromRow || !targetRow || fromRow === targetRow) {
                    return;
                }
                var parent = targetRow.parentNode;
                if (!parent) {
                    return;
                }
                var siblings = Array.prototype.slice.call(parent.querySelectorAll(':scope > .fc-fs-kv-row'));
                var fromIndex = siblings.indexOf(fromRow);
                var toIndex = siblings.indexOf(targetRow);
                if (fromIndex < 0 || toIndex < 0) {
                    return;
                }
                if (fromIndex < toIndex) {
                    parent.insertBefore(fromRow, targetRow.nextSibling);
                } else {
                    parent.insertBefore(fromRow, targetRow);
                }
            }

            block.addEventListener('mousedown', function (e) {
                var grip = e.target.closest('[data-fc-fence-color-grip]');
                if (!grip || !block.contains(grip)) {
                    return;
                }
                var row = grip.closest('.fc-fs-kv-row');
                if (row) {
                    row.draggable = true;
                }
            });

            block.addEventListener('mouseup', resetDraggable);

            block.addEventListener('dragstart', function (e) {
                var row = e.target.closest('.fc-fs-kv-row');
                if (!row || !block.contains(row) || !row.draggable) {
                    e.preventDefault();
                    return;
                }
                dragRow = row;
                row.classList.add('fc-fs-kv-row--dragging');
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', 'fence-color-row');
                }
            });

            block.addEventListener('dragend', function () {
                if (dragRow) {
                    dragRow.classList.remove('fc-fs-kv-row--dragging');
                }
                clearDragOver();
                resetDraggable();
                dragRow = null;
            });

            block.addEventListener('dragover', function (e) {
                if (!dragRow) {
                    return;
                }
                var table = dragRow.closest('.fc-fs-kv-table');
                var target = e.target.closest('.fc-fs-kv-row');
                if (!table || !target || target.closest('.fc-fs-kv-table') !== table || target === dragRow) {
                    return;
                }
                e.preventDefault();
                clearDragOver();
                target.classList.add('fc-fs-kv-row--drag-over');
            });

            block.addEventListener('dragleave', function (e) {
                var row = e.target.closest('.fc-fs-kv-row');
                if (row) {
                    row.classList.remove('fc-fs-kv-row--drag-over');
                }
            });

            block.addEventListener('drop', function (e) {
                if (!dragRow) {
                    return;
                }
                var table = dragRow.closest('.fc-fs-kv-table');
                var target = e.target.closest('.fc-fs-kv-row');
                if (!table || !target || target.closest('.fc-fs-kv-table') !== table || target === dragRow) {
                    return;
                }
                e.preventDefault();
                moveRowBeforeTarget(dragRow, target);
                clearDragOver();
                self.syncOrderFromDom();
                self.state.fenceColorsSort = { column: null, direction: 'asc' };
                self.refreshTable();
                self.setDirty(true);
            });
        }

        bindTableEvents() {
            var self = this;

            document.querySelectorAll('[data-fc-fence-color-field]').forEach(function (input) {
                if (input.getAttribute('data-fc-fc-bound') === '1') {
                    return;
                }
                input.setAttribute('data-fc-fc-bound', '1');
                input.addEventListener('input', function () {
                    var index = parseInt(input.getAttribute('data-fc-fence-color-index'), 10);
                    var field = input.getAttribute('data-fc-fence-color-field');
                    if (isNaN(index) || !field || !self.state.fenceColors[index]) {
                        return;
                    }
                    if (field === 'slug' && self.isOriginalSlug(self.state.fenceColors[index].slug)) {
                        input.value = self.state.fenceColors[index].slug;
                        return;
                    }
                    self.state.fenceColors[index][field] = input.value;
                    if (field === 'color') {
                        var picker = document.querySelector('[data-fc-fence-color-picker="' + index + '"]');
                        var normalized = self.normalizeHexInput(input.value);
                        if (picker && normalized) {
                            picker.value = normalized;
                        }
                    }
                    self.updateRowPreview(index);
                    self.setDirty(true);
                });
                input.addEventListener('blur', function () {
                    if (input.getAttribute('data-fc-fence-color-field') !== 'color') {
                        return;
                    }
                    var index = parseInt(input.getAttribute('data-fc-fence-color-index'), 10);
                    var normalized = self.normalizeHexInput(input.value);
                    if (normalized && self.state.fenceColors[index]) {
                        input.value = normalized;
                        self.state.fenceColors[index].color = normalized;
                        var picker = document.querySelector('[data-fc-fence-color-picker="' + index + '"]');
                        if (picker) {
                            picker.value = normalized;
                        }
                    }
                });
            });

            document.querySelectorAll('[data-fc-fence-color-picker]').forEach(function (picker) {
                if (picker.getAttribute('data-fc-fc-bound') === '1') {
                    return;
                }
                picker.setAttribute('data-fc-fc-bound', '1');
                picker.addEventListener('input', function () {
                    var index = parseInt(picker.getAttribute('data-fc-fence-color-picker'), 10);
                    if (isNaN(index) || !self.state.fenceColors[index]) {
                        return;
                    }
                    self.state.fenceColors[index].color = picker.value;
                    var hexInput = document.querySelector(
                        '[data-fc-fence-color-field="color"][data-fc-fence-color-index="' + index + '"]'
                    );
                    if (hexInput) {
                        hexInput.value = picker.value;
                    }
                    self.updateRowPreview(index);
                    self.setDirty(true);
                });
            });

            document.querySelectorAll('[data-fc-fence-color-pick]').forEach(function (btn) {
                if (btn.getAttribute('data-fc-fc-bound') === '1') {
                    return;
                }
                btn.setAttribute('data-fc-fc-bound', '1');
                btn.addEventListener('click', function () {
                    var index = parseInt(btn.getAttribute('data-fc-fence-color-pick'), 10);
                    var input = document.querySelector(
                        '[data-fc-fence-color-field="image"][data-fc-fence-color-index="' + index + '"]'
                    );
                    if (!input || !global.FcAdminMediaPicker || typeof global.FcAdminMediaPicker.open !== 'function') {
                        return;
                    }
                    global.FcAdminMediaPicker.open({
                        appBase: self.getAppBase(),
                        csrf: self.state.csrf,
                        onSelect: function (path) {
                            input.value = path;
                            if (self.state.fenceColors[index]) {
                                self.state.fenceColors[index].image = path;
                            }
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            self.updateRowPreview(index);
                            self.setDirty(true);
                        }
                    });
                });
            });

            document.querySelectorAll('[data-fc-fence-color-remove]').forEach(function (btn) {
                if (btn.getAttribute('data-fc-fc-bound') === '1') {
                    return;
                }
                btn.setAttribute('data-fc-fc-bound', '1');
                btn.addEventListener('click', function () {
                    var index = parseInt(btn.getAttribute('data-fc-fence-color-remove'), 10);
                    if (isNaN(index) || !self.state.fenceColors[index]) {
                        return;
                    }
                    if (self.isOriginalSlug(self.state.fenceColors[index].slug)) {
                        return;
                    }
                    self.state.fenceColors.splice(index, 1);
                    self.refreshTable();
                    self.setDirty(true);
                });
            });
        }

        bind() {
            var self = this;
            if (this.tableBound) {
                return;
            }
            this.tableBound = true;

            var fenceColorsBlock = document.querySelector('[data-fc-fence-colors-block]');
            if (fenceColorsBlock && !fenceColorsBlock.getAttribute('data-fc-fc-sort-bound')) {
                fenceColorsBlock.setAttribute('data-fc-fc-sort-bound', '1');
                fenceColorsBlock.addEventListener('click', function (e) {
                    var sortBtn = e.target.closest('[data-fc-fence-color-sort]');
                    if (!sortBtn) {
                        return;
                    }
                    e.preventDefault();
                    self.setSort(sortBtn.getAttribute('data-fc-fence-color-sort'));
                });
            }
            this.bindRowDragDrop(fenceColorsBlock);

            this.bindTableEvents();

            var addBtn = document.getElementById('fc-fence-colors-add');
            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    self.state.fenceColors.push({ slug: '', label: '', subLabel: '', color: '', image: '' });
                    self.refreshTable();
                    self.setDirty(true);
                });
            }

            var saveBtn = document.getElementById('fc-fence-colors-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }

            var resetBtn = document.getElementById('fc-fence-colors-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    var Modal = global.FcAdminModal;
                    var ask =
                        Modal && typeof Modal.confirm === 'function'
                            ? Modal.confirm({
                                  title: 'Reset fence colors',
                                  message: 'Reset all fence colors to defaults?',
                                  confirmLabel: 'Reset',
                                  cancelLabel: 'Cancel'
                              })
                            : Promise.resolve(window.confirm('Reset all fence colors to defaults?'));

                    ask.then(function (ok) {
                        if (!ok) {
                            return;
                        }
                        self.state.fenceColors = self.clone(self.state.fenceColorsDefaults);
                        self.state.fenceColorsSort = { column: null, direction: 'asc' };
                        self.refreshTable();
                        self.setDirty(true);
                    });
                });
            }
        }

        save() {
            var self = this;
            var state = this.state;
            global.FC.util.toast('saving', 'Saving fence colors…', TOAST_FENCE_COLORS);
            fetch(API_FENCE_COLORS, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ fenceColors: state.fenceColors, csrf: state.csrf })
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        if (!res.ok || !body.ok) {
                            throw new Error((body && body.error) || 'Save failed');
                        }
                        return body;
                    });
                })
                .then(function (body) {
                    state.fenceColors = self.clone(body.fenceColors || state.fenceColors);
                    state.fenceColorsDefaults = self.clone(body.defaults || state.fenceColorsDefaults);
                    self.refreshTable();
                    self.setDirty(false);
                    self.flash.set(body.message || 'Fence colors saved — refresh the planner to see changes.', 'success');
                    try {
                        var next = new URL(window.location.href);
                        window.location.assign(next.pathname + next.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    global.FC.util.toast('error', err.message || 'Could not save fence colors.', TOAST_FENCE_COLORS);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.fenceColors = new FenceColorsTabController();
})(window);
