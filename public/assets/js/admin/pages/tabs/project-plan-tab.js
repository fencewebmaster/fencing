/**
 * FC Admin — Settings → Project Plan tab.
 * The panel markup is server-rendered PHP; new rows added via "Add item"
 * are rendered client-side with renderItemRowHtml().
 */
(function (global) {
    'use strict';

    var API_PROJECT_PLAN = global.fcApiUrl('settings', 'action=project-plan');
    var TOAST_PROJECT_PLAN = 'fc-project-plan-save';

    class ProjectPlanTabController extends global.FC.Settings.TabController {
        clone(list) {
            return (list || []).map(function (row) {
                return {
                    key: row.slug || '',
                    slug: row.slug || '',
                    label: row.label || '',
                    image: row.image || '',
                    imageDefault: row.imageDefault || '',
                    isOriginal: !!row.isOriginal
                };
            });
        }

        itemKey(item) {
            return (item && (item.key || item.slug)) || '';
        }

        findItemByKey(key) {
            return this.state.projectPlanItems.find((row) => this.itemKey(row) === key);
        }

        paint() {
            var self = this;
            document.querySelectorAll('[data-fc-project-plan-item-field]').forEach(function (input) {
                var key = input.getAttribute('data-fc-project-plan-item');
                var field = input.getAttribute('data-fc-project-plan-item-field');
                var item = self.findItemByKey(key);
                input.value = item && field ? item[field] || '' : '';
            });
        }

        setDirty(isDirty) {
            this.state.projectPlanItemsDirty = !!isDirty;
            this.updateHeaderActions();
        }

        /** Field sync + image-pick + remove wiring for one item row. Reused for both
         *  server-rendered rows (at initial bind) and rows added via "Add item". */
        bindItemRow(rowEl) {
            var self = this;
            var state = this.state;
            if (!rowEl || rowEl.getAttribute('data-fc-pp-row-bound') === '1') {
                return;
            }
            rowEl.setAttribute('data-fc-pp-row-bound', '1');

            rowEl.querySelectorAll('[data-fc-project-plan-item-field]').forEach(function (input) {
                function sync() {
                    var key = input.getAttribute('data-fc-project-plan-item');
                    var field = input.getAttribute('data-fc-project-plan-item-field');
                    var item = self.findItemByKey(key);
                    if (item && field) {
                        item[field] = input.value;
                        self.setDirty(true);
                    }
                }
                input.addEventListener('input', sync);
                input.addEventListener('change', sync);
            });

            var pickBtn = rowEl.querySelector('[data-fc-project-plan-item-pick]');
            if (pickBtn) {
                pickBtn.addEventListener('click', function () {
                    var key = pickBtn.getAttribute('data-fc-project-plan-item-pick');
                    var input = rowEl.querySelector(
                        '[data-fc-project-plan-item="' + key + '"][data-fc-project-plan-item-field="image"]'
                    );
                    if (!input || !global.FcAdminMediaPicker || typeof global.FcAdminMediaPicker.open !== 'function') {
                        return;
                    }
                    global.FcAdminMediaPicker.open({
                        appBase: self.getAppBase(),
                        csrf: state.csrf,
                        onSelect: function (path) {
                            input.value = path;
                            var item = self.findItemByKey(key);
                            if (item) {
                                item.image = path;
                            }
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            var preview = rowEl.querySelector('[data-fc-project-plan-item-preview="' + key + '"]');
                            if (preview) {
                                var url = global.FC.Settings.tabs.fenceColors.previewUrl({ image: path }, self.getAppBase());
                                preview.innerHTML = url
                                    ? global.FC.Settings.buildViewableImgHtml(url, (item && item.label) || key)
                                    : '';
                            }
                            self.setDirty(true);
                        }
                    });
                });
            }

            var removeBtn = rowEl.querySelector('[data-fc-project-plan-item-remove]');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    var key = removeBtn.getAttribute('data-fc-project-plan-item-remove');
                    state.projectPlanItems = state.projectPlanItems.filter(function (row) {
                        return self.itemKey(row) !== key;
                    });
                    rowEl.remove();
                    self.setDirty(true);
                });
            }
        }

        /** Blank row markup for a newly added (not yet saved) extra item. */
        renderItemRowHtml(item) {
            var escapeHtml = global.FC.util.escapeHtml;
            var key = escapeHtml(this.itemKey(item));
            var slugId = 'fc-project-plan-item-' + key + '-slug';
            var labelId = 'fc-project-plan-item-' + key + '-label';
            var imageId = 'fc-project-plan-item-' + key + '-image';

            return (
                '<div class="grid min-w-[52rem] grid-cols-[1.5rem_2.5rem_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(12rem,1.4fr)_2.25rem] items-center gap-3 border-b border-slate-200 px-3 py-2.5 last:border-b-0" data-fc-project-plan-row="' +
                key +
                '">' +
                '<span class="fc-project-plan-grip" data-fc-project-plan-grip role="button" tabindex="0" aria-label="Drag to reorder" title="Drag to reorder">' +
                '<i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>' +
                '</span>' +
                '<span class="fc-settings-site-logo shrink-0" data-fc-project-plan-item-preview="' +
                key +
                '"></span>' +
                '<span class="fc-settings-field-input-wrap">' +
                '<input type="text" id="' +
                slugId +
                '" data-fc-project-plan-item="' +
                key +
                '" data-fc-project-plan-item-field="slug" value="" class="fc-settings-field font-mono" spellcheck="false" autocomplete="off" placeholder="e.g. gate-opener" aria-label="Slug">' +
                '<button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="' +
                slugId +
                '" aria-label="Copy Slug" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>' +
                '</span>' +
                '<span class="fc-settings-field-input-wrap">' +
                '<input type="text" id="' +
                labelId +
                '" data-fc-project-plan-item="' +
                key +
                '" data-fc-project-plan-item-field="label" value="" class="fc-settings-field" aria-label="Label">' +
                '<button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="' +
                labelId +
                '" aria-label="Copy Label" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>' +
                '</span>' +
                '<span class="fc-settings-field-input-wrap">' +
                '<input type="text" id="' +
                imageId +
                '" data-fc-project-plan-item="' +
                key +
                '" data-fc-project-plan-item-field="image" value="" class="fc-settings-field font-mono" placeholder="public/assets/img/plans/webp/…" autocomplete="off" spellcheck="false" aria-label="Image">' +
                '<button type="button" class="fc-settings-field-copy" data-fc-project-plan-item-pick="' +
                key +
                '" title="Set image" aria-label="Set image"><i class="fa-solid fa-image" aria-hidden="true"></i></button>' +
                '<button type="button" class="fc-settings-field-copy" data-fc-settings-copy-for="' +
                imageId +
                '" aria-label="Copy Image" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>' +
                '</span>' +
                '<button type="button" class="fc-project-plan-remove" data-fc-project-plan-item-remove="' +
                key +
                '" title="Remove item" aria-label="Remove item"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>' +
                '</div>'
            );
        }

        syncOrderFromDom() {
            var self = this;
            var state = this.state;
            var container = document.getElementById('fc-project-plan-items');
            if (!container) {
                return;
            }
            var rows = container.querySelectorAll('[data-fc-project-plan-row]');
            var next = [];
            rows.forEach(function (rowEl) {
                var key = rowEl.getAttribute('data-fc-project-plan-row');
                var item = self.findItemByKey(key);
                if (item) {
                    next.push(item);
                }
            });
            if (next.length === state.projectPlanItems.length) {
                state.projectPlanItems = next;
            }
        }

        moveRowBeforeTarget(fromRow, targetRow) {
            if (!fromRow || !targetRow || fromRow === targetRow) {
                return;
            }
            var parent = targetRow.parentNode;
            if (!parent) {
                return;
            }
            var siblings = Array.prototype.slice.call(parent.querySelectorAll(':scope > [data-fc-project-plan-row]'));
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

        bindDragDrop(container) {
            var self = this;
            if (!container || container.getAttribute('data-fc-pp-drag-bound') === '1') {
                return;
            }
            container.setAttribute('data-fc-pp-drag-bound', '1');

            var dragRow = null;

            function clearDragOver() {
                container.querySelectorAll('.fc-project-plan-row--drag-over').forEach(function (row) {
                    row.classList.remove('fc-project-plan-row--drag-over');
                });
            }

            function resetDraggable() {
                container.querySelectorAll('[data-fc-project-plan-row]').forEach(function (row) {
                    row.draggable = false;
                });
            }

            container.addEventListener('mousedown', function (e) {
                var grip = e.target.closest('[data-fc-project-plan-grip]');
                if (!grip || !container.contains(grip)) {
                    return;
                }
                var row = grip.closest('[data-fc-project-plan-row]');
                if (row) {
                    row.draggable = true;
                }
            });

            container.addEventListener('mouseup', resetDraggable);

            container.addEventListener('dragstart', function (e) {
                var row = e.target.closest('[data-fc-project-plan-row]');
                if (!row || !container.contains(row) || !row.draggable) {
                    e.preventDefault();
                    return;
                }
                dragRow = row;
                row.classList.add('fc-project-plan-row--dragging');
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', 'project-plan-row');
                }
            });

            container.addEventListener('dragend', function () {
                if (dragRow) {
                    dragRow.classList.remove('fc-project-plan-row--dragging');
                }
                clearDragOver();
                resetDraggable();
                dragRow = null;
            });

            container.addEventListener('dragover', function (e) {
                if (!dragRow) {
                    return;
                }
                var target = e.target.closest('[data-fc-project-plan-row]');
                if (!target || !container.contains(target) || target === dragRow) {
                    return;
                }
                e.preventDefault();
                clearDragOver();
                target.classList.add('fc-project-plan-row--drag-over');
            });

            container.addEventListener('dragleave', function (e) {
                var row = e.target.closest('[data-fc-project-plan-row]');
                if (row) {
                    row.classList.remove('fc-project-plan-row--drag-over');
                }
            });

            container.addEventListener('drop', function (e) {
                if (!dragRow) {
                    return;
                }
                var target = e.target.closest('[data-fc-project-plan-row]');
                if (!target || !container.contains(target) || target === dragRow) {
                    return;
                }
                e.preventDefault();
                self.moveRowBeforeTarget(dragRow, target);
                clearDragOver();
                self.syncOrderFromDom();
                self.setDirty(true);
            });
        }

        bind() {
            var self = this;
            var state = this.state;
            if (state.projectPlanFormBound) {
                return;
            }
            state.projectPlanFormBound = true;

            document.querySelectorAll('[data-fc-project-plan-row]').forEach(function (rowEl) {
                self.bindItemRow(rowEl);
            });

            var addBtn = document.getElementById('fc-project-plan-add');
            var itemsContainer = document.getElementById('fc-project-plan-items');
            if (itemsContainer) {
                self.bindDragDrop(itemsContainer);
            }
            if (addBtn && itemsContainer) {
                addBtn.addEventListener('click', function () {
                    state.projectPlanItemCounter = (state.projectPlanItemCounter || 0) + 1;
                    var item = {
                        key: 'new-' + state.projectPlanItemCounter,
                        slug: '',
                        label: '',
                        image: '',
                        imageDefault: '',
                        isOriginal: false
                    };
                    state.projectPlanItems.push(item);
                    itemsContainer.insertAdjacentHTML('beforeend', self.renderItemRowHtml(item));
                    var rowEl = itemsContainer.lastElementChild;
                    self.bindItemRow(rowEl);
                    self.setDirty(true);
                    var slugInput = rowEl.querySelector('[data-fc-project-plan-item-field="slug"]');
                    if (slugInput) {
                        slugInput.focus();
                    }
                });
            }

            var saveBtn = document.getElementById('fc-project-plan-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }
            var resetBtn = document.getElementById('fc-project-plan-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    var escapeHtml = global.FC.util.escapeHtml;
                    state.projectPlanItems = state.projectPlanItems.map(function (row) {
                        var def = state.projectPlanItemsDefaults.find(function (d) {
                            return d.slug === row.slug;
                        });
                        return {
                            key: self.itemKey(row),
                            slug: row.slug,
                            label: def ? def.label : row.label,
                            image: '',
                            imageDefault: row.imageDefault,
                            isOriginal: row.isOriginal
                        };
                    });
                    self.paint();
                    document.querySelectorAll('[data-fc-project-plan-item-preview]').forEach(function (preview) {
                        var key = preview.getAttribute('data-fc-project-plan-item-preview');
                        var item = self.findItemByKey(key);
                        var url = item
                            ? global.FC.Settings.tabs.fenceColors.previewUrl({ image: item.imageDefault }, self.getAppBase())
                            : '';
                        preview.innerHTML = url
                            ? '<img src="' + escapeHtml(url) + '" alt="" loading="lazy" decoding="async" />'
                            : '';
                    });
                    self.setDirty(true);
                });
            }
        }

        save() {
            var self = this;
            var state = this.state;
            global.FC.util.toast('saving', 'Saving Project Plan settings…', TOAST_PROJECT_PLAN);
            fetch(API_PROJECT_PLAN, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ items: state.projectPlanItems, csrf: state.csrf })
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
                    state.projectPlanItems = self.clone(body.extraItems || state.projectPlanItems);
                    self.paint();
                    self.setDirty(false);
                    self.flash.set(body.message || 'Project Plan settings saved.', 'success');
                    window.location.reload();
                })
                .catch(function (err) {
                    global.FC.util.toast('error', err.message || 'Could not save Project Plan settings.', TOAST_PROJECT_PLAN);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.projectPlan = new ProjectPlanTabController();
})(window);
