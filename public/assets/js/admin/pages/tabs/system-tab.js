/**
 * FC Admin — Settings → System tab.
 * Extracted from settings.js. Reads/writes state via the shared
 * FC.Settings shell (state, flash, updateHeaderActions) exposed by
 * settings.js, since this tab's dirty-tracking and save flow mutate the same
 * `state.system*` slice the shell's tab-switching/header-actions code reads.
 */
(function (global) {
    'use strict';

    var API_SYSTEM = global.fcApiUrl('settings', 'action=system');
    var TOAST_SYSTEM = 'fc-system-save';
    var API_ADDRESS_BOOK = global.fcApiUrl('settings', 'action=address-book');
    var API_ADDRESS_BOOK_EXPORT = global.fcApiUrl('settings', 'action=address-book-export');
    var API_ADDRESS_BOOK_IMPORT = global.fcApiUrl('settings', 'action=address-book-import');
    var TOAST_ADDRESS_BOOK = 'fc-address-book';

    class SystemTabController extends global.FC.Settings.TabController {
        readFieldValue(el) {
            if (!el) {
                return '';
            }
            if (el.type === 'number') {
                var n = parseInt(el.value, 10);
                return Number.isFinite(n) ? n : 0;
            }
            return el.value;
        }

        setDirty(isDirty) {
            this.state.systemDirty = !!isDirty;
            this.updateHeaderActions();
        }

        paint() {
            var state = this.state;
            document.querySelectorAll('[data-fc-system-field]').forEach(function (el) {
                var key = el.getAttribute('data-fc-system-field');
                if (!key) {
                    return;
                }
                var value = state.system[key];
                if (value == null) {
                    value = '';
                }
                el.value = String(value);
            });
        }

        bind() {
            var self = this;
            var state = this.state;
            if (state.systemFormBound) {
                return;
            }
            state.systemFormBound = true;

            document.querySelectorAll('[data-fc-system-field]').forEach(function (el) {
                var onFieldChange = function () {
                    var key = el.getAttribute('data-fc-system-field');
                    if (!key) {
                        return;
                    }
                    state.system[key] = self.readFieldValue(el);
                    self.setDirty(true);
                };
                el.addEventListener('change', onFieldChange);
                if (el.type === 'number') {
                    el.addEventListener('input', onFieldChange);
                }
            });

            var saveBtn = document.getElementById('fc-system-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }
            var resetBtn = document.getElementById('fc-system-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    state.system = Object.assign({}, state.systemDefaults);
                    self.paint();
                    self.setDirty(true);
                });
            }

            var importBtn = document.querySelector('[data-fc-address-book-import]');
            var fileInput = document.querySelector('[data-fc-address-book-file]');
            if (importBtn && fileInput) {
                importBtn.addEventListener('click', function () {
                    fileInput.value = '';
                    fileInput.click();
                });
                fileInput.addEventListener('change', function () {
                    if (fileInput.files && fileInput.files[0]) {
                        self.importAddressBook(fileInput.files[0], importBtn);
                    }
                });
            }
        }

        /** Loads the Address Book card the first time System is shown: a cold count reads the whole 70 MB file. */
        ensureAddressBook() {
            if (this.addressBookRequested) {
                return;
            }
            this.addressBookRequested = true;
            var self = this;
            fetch(API_ADDRESS_BOOK, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (res) {
                    return res.json().then(function (body) {
                        if (!res.ok || !body.ok) {
                            throw new Error((body && body.error) || 'The address book could not be read.');
                        }
                        return body.addressBook;
                    });
                })
                .then(function (book) {
                    self.renderAddressBook(book);
                })
                .catch(function (err) {
                    self.addressBookRequested = false;
                    var body = document.querySelector('[data-fc-address-book-body]');
                    if (body) {
                        body.innerHTML = '<p class="text-sm text-red-700">' + global.FC.util.escapeHtml(err.message || 'The address book could not be read.') + '</p>';
                    }
                });
        }

        renderAddressBook(book) {
            var esc = global.FC.util.escapeHtml;
            var body = document.querySelector('[data-fc-address-book-body]');
            var exportLink = document.querySelector('[data-fc-address-book-export]');
            var importBtn = document.querySelector('[data-fc-address-book-import]');
            if (!body || !book) {
                return;
            }
            this.addressBook = book;
            if (exportLink) {
                exportLink.href = API_ADDRESS_BOOK_EXPORT;
                exportLink.hidden = !book.exists;
            }
            if (importBtn) {
                importBtn.hidden = !book.canImport;
            }

            var num = function (n) {
                return Number(n || 0).toLocaleString('en-AU');
            };
            var tile = function (icon, label, value, state) {
                return '<div class="fc-seo-check" data-state="' + (state || 'info') + '">' +
                    '<span class="fc-seo-check__icon" aria-hidden="true"><i class="fa-solid ' + icon + '"></i></span>' +
                    '<span class="fc-seo-check__text"><span class="fc-seo-check__label">' + esc(label) + '</span>' +
                    '<span class="fc-seo-check__value" title="' + esc(value) + '">' + esc(value) + '</span></span></div>';
            };
            var importHint = book.canImport ? ' Import an exported .jsonl.gz to add one.' : ' The Super Admin can import one.';

            if (!book.exists) {
                body.innerHTML = '<div>' + tile('fa-triangle-exclamation', 'No address book', 'Address fields show no suggestions on this site.', 'warn') +
                    '<p class="mt-3 text-sm text-slate-500">' + esc('This site has no ' + book.path + '.' + importHint) + '</p></div>' +
                    '<div><p class="text-sm text-slate-500" data-fc-address-book-status hidden></p></div>';
                return;
            }

            var tiles = [
                tile('fa-file-lines', 'File name', book.path),
                tile('fa-weight-hanging', 'File size', book.sizeLabel),
                tile('fa-clock-rotate-left', 'Updated at', book.updatedAtLabel),
                tile('fa-database', 'Data source', book.source),
                tile('fa-hammer', 'Built', book.builtLabel || 'Unknown'),
                tile('fa-house', 'Addresses', num(book.addresses)),
                tile('fa-road', 'Streets', num(book.streets)),
                tile('fa-map-location-dot', 'Suburbs', num(book.suburbs)),
                tile('fa-envelope', 'Postcodes', num(book.postcodes))
            ];
            if (!book.valid) {
                tiles.unshift(tile('fa-triangle-exclamation', 'This file has a problem', book.error, 'bad'));
            }

            var rows = (book.states || []).map(function (s) {
                var hint = s.state === book.stateHint ? ' <span class="text-xs text-slate-500">ranked first here</span>' : '';
                return '<tr><td><strong>' + esc(s.state) + '</strong>' + hint + '</td><td class="text-end">' + num(s.suburbs) +
                    '</td><td class="text-end">' + num(s.streets) + '</td><td class="text-end">' + num(s.addresses) + '</td></tr>';
            }).join('');

            body.innerHTML = '<div><div class="fc-seo-checks">' + tiles.join('') + '</div></div>' +
                (rows ? '<div><div class="fc-address-book__states"><table class="fc-entries-table"><thead><tr><th>State</th><th class="text-end">Suburbs</th>' +
                    '<th class="text-end">Streets</th><th class="text-end">Addresses</th></tr></thead><tbody>' + rows + '</tbody></table></div></div>' : '') +
                '<div><p class="text-sm text-slate-500" data-fc-address-book-status hidden></p>' +
                (book.licence ? '<p class="text-xs text-slate-500">' + esc(book.licence) + '</p>' : '') + '</div>';
        }

        /** Sends the file in chunks under any host's upload limit; the server checks it before it replaces the live one. */
        importAddressBook(file, btn) {
            var self = this;
            var book = this.addressBook || {};
            if (!/\.(gz|jsonl)$/i.test(file.name)) {
                global.FC.util.toast('error', 'Choose an exported address book (.jsonl.gz or .jsonl).', TOAST_ADDRESS_BOOK);
                return;
            }
            var sizeMb = (file.size / 1048576).toFixed(1) + ' MB';
            var message = 'Replace this site\'s address book with ' + file.name + ' (' + sizeMb + ')? It is checked first; the current one stays if the new one has a problem.';
            var asked = global.FcAdminModal
                ? global.FcAdminModal.confirm({ title: 'Import address book?', message: message, confirmLabel: 'Import' })
                : Promise.resolve(global.confirm(message));
            asked.then(function (confirmed) {
                if (!confirmed || !global.FC.util.setSaving(btn, true)) {
                    return;
                }
                var chunk = Number(book.chunkBytes) || 1500000;
                var total = Math.max(1, Math.ceil(file.size / chunk));
                var upload = Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
                var status = function (text) {
                    var el = document.querySelector('[data-fc-address-book-status]');
                    if (el) {
                        el.hidden = !text;
                        el.textContent = text;
                    }
                };

                var send = function (index) {
                    var offset = index * chunk;
                    var form = new FormData();
                    form.append('csrf', self.state.csrf);
                    form.append('upload', upload);
                    form.append('index', String(index));
                    form.append('total', String(total));
                    form.append('offset', String(offset));
                    form.append('size', String(file.size));
                    form.append('chunk', file.slice(offset, offset + chunk), file.name);
                    status(index === total - 1 ? 'Checking every line of ' + file.name + '…' : 'Uploading ' + file.name + '… ' + Math.round(index / total * 100) + '%');
                    return fetch(API_ADDRESS_BOOK_IMPORT, { method: 'POST', body: form, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                        .then(function (res) {
                            return res.json().catch(function () {
                                return {};
                            }).then(function (data) {
                                if (!res.ok || !data.ok) {
                                    throw new Error(data.error || 'The import stopped (HTTP ' + res.status + ').');
                                }
                                return data.done ? data : send(index + 1);
                            });
                        });
                };

                send(0)
                    .then(function (data) {
                        global.FC.util.setSaving(btn, false);
                        self.renderAddressBook(data.details);
                        global.FC.util.toast('success', data.message || 'Address book imported.', TOAST_ADDRESS_BOOK);
                    })
                    .catch(function (err) {
                        global.FC.util.setSaving(btn, false);
                        status(err.message || 'The import failed.');
                        global.FC.util.toast('error', err.message || 'The import failed.', TOAST_ADDRESS_BOOK);
                    });
            });
        }

        save() {
            var self = this;
            var state = this.state;
            if (!this.startSaving('fc-system-save')) {
                return;
            }
            global.FC.util.toast('saving', 'Saving system settings…', TOAST_SYSTEM);
            fetch(API_SYSTEM, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ system: state.system, csrf: state.csrf })
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
                    state.system = Object.assign({}, body.system || state.system);
                    state.systemDefaults = Object.assign({}, body.defaults || state.systemDefaults);
                    self.paint();
                    self.setDirty(false);
                    self.flash.set(body.message || 'System settings saved.', 'success');
                    try {
                        var next = new URL(window.location.href);
                        window.location.assign(next.pathname + next.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    self.stopSaving('fc-system-save');
                    global.FC.util.toast('error', err.message || 'Could not save system settings.', TOAST_SYSTEM);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.system = new SystemTabController();
})(window);
