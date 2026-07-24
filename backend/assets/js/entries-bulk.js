/**
 * FC Admin — Planner entries list selection + bulk trash/restore/export/import.
 */
(function (global) {
    'use strict';

    var TOAST_ID = 'fc-entries-bulk';
    var FLASH_KEY = 'fc-entries-bulk-flash';

    function toast(kind, message, duration) {
        var T = global.FcAdminToast;
        if (!T) {
            return;
        }
        if (kind === 'saving' && typeof T.loading === 'function') {
            T.loading(message, TOAST_ID);
            return;
        }
        if (kind === 'success' && typeof T.success === 'function') {
            T.success(message, {
                id: TOAST_ID,
                duration: duration || 4500,
            });
            return;
        }
        if (kind === 'error' && typeof T.error === 'function') {
            T.error(message, {
                id: TOAST_ID,
                duration: duration || 4500,
            });
        }
    }

    function toastExporting() {
        var T = global.FcAdminToast;
        if (!T || typeof T.show !== 'function') {
            return;
        }
        T.show({
            message: 'Exporting entries',
            type: 'info',
            id: TOAST_ID,
            duration: 3000,
        });
    }

    function setFlash(message, type) {
        try {
            sessionStorage.setItem(
                FLASH_KEY,
                JSON.stringify({
                    message: String(message || ''),
                    type: type === 'error' ? 'error' : 'success',
                })
            );
        } catch (e) {
            /* ignore */
        }
    }

    function consumeFlash() {
        try {
            var raw = sessionStorage.getItem(FLASH_KEY);
            if (!raw) {
                return null;
            }
            sessionStorage.removeItem(FLASH_KEY);
            var data = JSON.parse(raw);
            if (!data || !data.message) {
                return null;
            }
            return data;
        } catch (e) {
            return null;
        }
    }

    function showHeaderNotice(root, flash) {
        var mount = root.querySelector('[data-fc-entries-notice]');
        if (!mount || !flash || !flash.message) {
            return;
        }

        var type = flash.type === 'error' ? 'error' : 'success';
        mount.hidden = false;
        mount.className =
            'fc-entries-page__notice fc-entries-page__notice--' + type + ' is-visible';
        mount.setAttribute('role', type === 'error' ? 'alert' : 'status');
        mount.innerHTML =
            '<p class="fc-entries-page__notice-text"></p>' +
            '<button type="button" class="fc-entries-page__notice-dismiss" aria-label="Dismiss notice">' +
            '<i class="fa-solid fa-xmark" aria-hidden="true"></i>' +
            '</button>';

        var textEl = mount.querySelector('.fc-entries-page__notice-text');
        if (textEl) {
            textEl.textContent = flash.message;
        }

        var dismiss = mount.querySelector('.fc-entries-page__notice-dismiss');
        if (dismiss) {
            dismiss.addEventListener('click', function () {
                mount.hidden = true;
                mount.classList.remove('is-visible');
                mount.innerHTML = '';
            });
        }
    }

    function apiUrl(root, action) {
        var base = root.getAttribute('data-fc-entries-api') || 'api.php?module=entries';
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        return base + sep + 'action=' + encodeURIComponent(action);
    }

    function selectedIds(root) {
        var ids = [];
        root.querySelectorAll('[data-fc-entries-row-check]:checked').forEach(function (input) {
            var id = parseInt(input.value, 10);
            if (id > 0) {
                ids.push(id);
            }
        });
        return ids;
    }

    function syncSelectionUi(root) {
        var checks = root.querySelectorAll('[data-fc-entries-row-check]');
        var selected = selectedIds(root);
        var selectAll = root.querySelector('[data-fc-entries-select-all]');
        var action = root.querySelector('[data-fc-entries-bulk-action]');
        var apply = root.querySelector('[data-fc-entries-bulk-apply]');
        var countEl = root.querySelector('[data-fc-entries-bulk-count]');
        var hasRows = checks.length > 0;
        var allChecked = hasRows && selected.length === checks.length;
        var someChecked = selected.length > 0 && selected.length < checks.length;

        if (selectAll) {
            selectAll.disabled = !hasRows;
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked;
        }

        checks.forEach(function (input) {
            var row = input.closest('[data-fc-entries-row]');
            if (row) {
                row.classList.toggle('is-selected', !!input.checked);
            }
        });

        if (action) {
            action.disabled = selected.length === 0;
            if (selected.length === 0) {
                action.value = '';
            }
        }
        if (apply) {
            apply.disabled = selected.length === 0 || !(action && action.value);
        }
        if (countEl) {
            if (selected.length > 0) {
                countEl.hidden = false;
                countEl.textContent = selected.length + ' selected';
            } else {
                countEl.hidden = true;
                countEl.textContent = '0 selected';
            }
        }
    }

    function confirmBulk(action, count) {
        var confirmFn =
            global.FcAdminModal && typeof global.FcAdminModal.confirm === 'function'
                ? global.FcAdminModal.confirm.bind(global.FcAdminModal)
                : null;
        var noun = count === 1 ? 'entry' : 'entries';
        var title;
        var message;
        var confirmLabel;
        var variant;

        if (action === 'export') {
            title = 'Export selected entries';
            message =
                'Export ' +
                count +
                ' selected ' +
                noun +
                ' as JSON? The file includes full planner details for import on another site.';
            confirmLabel = 'Export JSON';
            variant = 'info';
        } else if (action === 'restore') {
            title = 'Restore selected entries';
            message = 'Restore ' + count + ' selected ' + noun + '?';
            confirmLabel = 'Restore';
            variant = 'info';
        } else if (action === 'delete') {
            title = 'Delete permanently';
            message =
                'Permanently delete ' +
                count +
                ' selected ' +
                noun +
                '? This cannot be undone.';
            confirmLabel = 'Delete permanently';
            variant = 'error';
        } else {
            title = 'Move to trash';
            message = 'Move ' + count + ' selected ' + noun + ' to trash?';
            confirmLabel = 'Move to trash';
            variant = 'warning';
        }

        if (confirmFn) {
            return confirmFn({
                title: title,
                message: message,
                confirmLabel: confirmLabel,
                variant: variant,
            });
        }

        return Promise.resolve(global.confirm(message));
    }

    function downloadJson(filename, payload) {
        var blob = new Blob([JSON.stringify(payload, null, 2)], {
            type: 'application/json;charset=utf-8',
        });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename || 'fc-planner-entries.json';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () {
            URL.revokeObjectURL(url);
        }, 1000);
    }

    function runExport(root, ids) {
        toastExporting();

        return fetch(apiUrl(root, 'export'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: ids }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || result.data.ok === false) {
                    throw new Error(
                        (result.data && (result.data.error || result.data.message)) ||
                            'Export failed.'
                    );
                }
                downloadJson(result.data.filename, result.data.payload || {});
                toast('success', result.data.message || 'Entries exported.', 3000);
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not export entries.', 3000);
            });
    }

    function runBulk(root, action, ids) {
        var endpoint = apiUrl(root, action);
        var loadingMsg =
            action === 'restore'
                ? 'Restoring entries…'
                : action === 'delete'
                  ? 'Deleting entries…'
                  : 'Moving entries to trash…';
        toast('saving', loadingMsg);

        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: ids }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || result.data.ok === false) {
                    throw new Error((result.data && (result.data.error || result.data.message)) || 'Request failed.');
                }
                setFlash(result.data.message || 'Done.', 'success');
                global.location.reload();
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not update entries.');
            });
    }

    function applyBulk(root) {
        var actionEl = root.querySelector('[data-fc-entries-bulk-action]');
        var action = actionEl ? String(actionEl.value || '').trim() : '';
        var ids = selectedIds(root);
        if (!action || !ids.length) {
            return;
        }

        confirmBulk(action, ids.length).then(function (ok) {
            if (!ok) {
                return;
            }
            if (action === 'export') {
                runExport(root, ids);
                return;
            }
            runBulk(root, action, ids);
        });
    }

    function confirmImport(count) {
        var confirmFn =
            global.FcAdminModal && typeof global.FcAdminModal.confirm === 'function'
                ? global.FcAdminModal.confirm.bind(global.FcAdminModal)
                : null;
        var noun = count === 1 ? 'entry' : 'entries';
        var message =
            'Import ' +
            count +
            ' planner ' +
            noun +
            '? Matching planner IDs will be overwritten with the full imported details.';

        if (confirmFn) {
            return confirmFn({
                title: 'Import planner entries',
                message: message,
                confirmLabel: 'Import JSON',
                variant: 'warning',
            });
        }

        return Promise.resolve(global.confirm(message));
    }

    function runImport(root, documentPayload) {
        var csrf = root.getAttribute('data-fc-entries-csrf') || '';
        toast('saving', 'Importing entries…');

        return fetch(apiUrl(root, 'import'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf: csrf,
                mode: 'overwrite',
                document: documentPayload,
            }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || result.data.ok === false) {
                    throw new Error(
                        (result.data && (result.data.error || result.data.message)) ||
                            'Import failed.'
                    );
                }
                setFlash(result.data.message || 'Entries imported.', 'success');
                global.location.reload();
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not import entries.');
            });
    }

    function bindImport(root) {
        var openBtn = root.querySelector('[data-fc-entries-import-open]');
        var fileInput = root.querySelector('[data-fc-entries-import-file]');
        if (!openBtn || !fileInput || openBtn.dataset.fcImportBound === '1') {
            return;
        }
        openBtn.dataset.fcImportBound = '1';

        openBtn.addEventListener('click', function () {
            fileInput.value = '';
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function () {
                var parsed;
                try {
                    parsed = JSON.parse(String(reader.result || ''));
                } catch (e) {
                    toast('error', 'Invalid JSON file.');
                    return;
                }

                if (
                    !parsed ||
                    parsed.format !== 'fc-planner-entries' ||
                    !Array.isArray(parsed.entries)
                ) {
                    toast('error', 'Invalid planner export file.');
                    return;
                }

                confirmImport(parsed.entries.length).then(function (ok) {
                    if (ok) {
                        runImport(root, parsed);
                    }
                });
            };
            reader.onerror = function () {
                toast('error', 'Could not read the selected file.');
            };
            reader.readAsText(file);
        });
    }

    function initList(root) {
        if (!root || root.getAttribute('data-fc-entries-bulk-bound') === '1') {
            return;
        }
        root.setAttribute('data-fc-entries-bulk-bound', '1');

        var selectAll = root.querySelector('[data-fc-entries-select-all]');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var checked = !!selectAll.checked;
                root.querySelectorAll('[data-fc-entries-row-check]').forEach(function (input) {
                    input.checked = checked;
                });
                syncSelectionUi(root);
            });
        }

        root.addEventListener('change', function (e) {
            var target = e.target;
            if (!target) {
                return;
            }
            if (target.matches('[data-fc-entries-row-check]')) {
                syncSelectionUi(root);
                return;
            }
            if (target.matches('[data-fc-entries-bulk-action]')) {
                syncSelectionUi(root);
            }
        });

        root.addEventListener('click', function (e) {
            var applyBtn = e.target.closest('[data-fc-entries-bulk-apply]');
            if (applyBtn) {
                e.preventDefault();
                applyBulk(root);
                return;
            }
            var checkLabel = e.target.closest('.fc-entries-check');
            if (checkLabel) {
                e.stopPropagation();
            }
        });

        bindImport(root);
        syncSelectionUi(root);
        showHeaderNotice(root, consumeFlash());
    }

    function boot() {
        document.querySelectorAll('[data-fc-entries-list]').forEach(initList);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window);
