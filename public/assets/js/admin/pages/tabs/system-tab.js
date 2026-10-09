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

            this.bindAddressBook();
        }

        /** One set of listeners on the card: its body is re-rendered at every step of an import. */
        bindAddressBook() {
            var self = this;
            var card = document.querySelector('[data-fc-address-book]');
            if (!card || this.addressBookBound) {
                return;
            }
            this.addressBookBound = true;

            card.addEventListener('click', function (e) {
                // The file input sits inside the drop zone: its own click must reach the browser, not open it again.
                if (e.target.matches('[data-fc-ab-file]')) {
                    return;
                }
                var act = e.target.closest('[data-fc-ab-action]');
                if (!act) {
                    return;
                }
                e.preventDefault();
                var action = act.getAttribute('data-fc-ab-action');
                if (action === 'choose') {
                    var input = card.querySelector('[data-fc-ab-file]');
                    if (input) {
                        input.value = '';
                        input.click();
                    }
                } else if (action === 'start') {
                    self.startAddressBookImport();
                } else if (action === 'cancel') {
                    self.cancelAddressBookImport();
                } else if (action === 'reset') {
                    self.setAddressBookUpload({ stage: 'idle' });
                }
            });
            card.addEventListener('keydown', function (e) {
                var drop = e.target.closest('[data-fc-ab-drop]');
                if (drop && (e.key === 'Enter' || e.key === ' ')) {
                    e.preventDefault();
                    drop.click();
                }
            });
            card.addEventListener('change', function (e) {
                if (e.target.matches('[data-fc-ab-file]') && e.target.files && e.target.files[0]) {
                    self.pickAddressBookFile(e.target.files[0]);
                }
            });
            // A file dragged anywhere over the card lights the drop zone; a drop elsewhere on the card must not open the file.
            ['dragenter', 'dragover'].forEach(function (type) {
                card.addEventListener(type, function (e) {
                    var drop = card.querySelector('[data-fc-ab-drop]');
                    if (!drop || !e.dataTransfer || Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') < 0) {
                        return;
                    }
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';
                    drop.classList.add('is-dragover');
                });
            });
            card.addEventListener('dragleave', function (e) {
                var drop = card.querySelector('[data-fc-ab-drop]');
                if (drop && !card.contains(e.relatedTarget)) {
                    drop.classList.remove('is-dragover');
                }
            });
            card.addEventListener('drop', function (e) {
                var drop = card.querySelector('[data-fc-ab-drop]');
                if (!drop || !e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) {
                    return;
                }
                e.preventDefault();
                drop.classList.remove('is-dragover');
                self.pickAddressBookFile(e.dataTransfer.files[0]);
            });
            global.addEventListener('beforeunload', function (e) {
                var stage = self.abUpload && self.abUpload.stage;
                if (stage === 'uploading' || stage === 'checking') {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
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
                        body.removeAttribute('aria-busy');
                        body.innerHTML = '<div><div class="fc-address-book__note" data-tone="bad"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>' +
                            global.FC.util.escapeHtml(err.message || 'The address book could not be read.') + '</span></div></div>';
                    }
                });
        }

        renderAddressBook(book) {
            var esc = global.FC.util.escapeHtml;
            var body = document.querySelector('[data-fc-address-book-body]');
            var exportLink = document.querySelector('[data-fc-address-book-export]');
            var chip = document.querySelector('[data-fc-address-book-chip]');
            if (!body || !book) {
                return;
            }
            this.addressBook = book;
            this.abUpload = this.abUpload || { stage: 'idle' };
            body.removeAttribute('aria-busy');
            if (exportLink) {
                exportLink.href = API_ADDRESS_BOOK_EXPORT;
                exportLink.hidden = !book.exists;
            }
            if (chip) {
                var tone = !book.exists ? 'warn' : (book.valid ? 'good' : 'bad');
                chip.hidden = false;
                chip.setAttribute('data-tone', tone);
                chip.textContent = !book.exists ? 'Not installed' : (book.valid ? 'In use' : 'Needs attention');
            }

            var num = function (n) {
                return Number(n || 0).toLocaleString('en-AU');
            };
            var html = '';

            if (!book.exists) {
                html += '<div><div class="fc-address-book__empty"><span class="fc-address-book__empty-icon" aria-hidden="true"><i class="fa-solid fa-map-location-dot"></i></span>' +
                    '<div><p class="fc-address-book__empty-title">No address book on this site yet</p>' +
                    '<p class="fc-address-book__empty-text">The planner\'s Address fields work, but suggest no streets until one is added. ' +
                    'Export it from a site that has one, then import it here.</p></div></div></div>';
            } else {
                var figure = function (icon, value, label) {
                    return '<div class="fc-address-book__figure"><span class="fc-address-book__figure-icon" aria-hidden="true"><i class="fa-solid ' + icon + '"></i></span>' +
                        '<span class="fc-address-book__figure-value">' + esc(value) + '</span><span class="fc-address-book__figure-label">' + esc(label) + '</span></div>';
                };
                var fact = function (label, value) {
                    return '<div><dt>' + esc(label) + '</dt><dd title="' + esc(value) + '">' + esc(value) + '</dd></div>';
                };
                html += '<div><div class="fc-address-book__figures">' +
                    figure('fa-house', num(book.addresses), 'Addresses') +
                    figure('fa-road', num(book.streets), 'Streets') +
                    figure('fa-map-location-dot', num(book.suburbs), 'Suburbs') +
                    figure('fa-envelope', num(book.postcodes), 'Postcodes') +
                    '</div><dl class="fc-address-book__facts">' +
                    fact('File', book.path) +
                    fact('Size', (Number(book.size || 0) / 1048576).toFixed(1) + ' MB') +
                    fact('Updated', book.updatedAtLabel) +
                    fact('Source', book.source || 'Unknown') +
                    fact('Built', book.builtLabel || 'Unknown') +
                    '</dl>' +
                    (book.valid ? '' : '<div class="fc-address-book__note" data-tone="bad"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>' +
                        esc('This file has a problem, so suggestions may be missing: ' + book.error) + '</span></div>') +
                    '</div>';

                var states = (book.states || []).slice().sort(function (a, b) {
                    return b.addresses - a.addresses;
                });
                var most = states.length ? states[0].addresses : 0;
                var rows = states.map(function (s) {
                    var share = book.addresses ? s.addresses / book.addresses * 100 : 0;
                    var here = s.state === book.stateHint;
                    return '<tr' + (here ? ' class="is-here"' : '') + '><td><strong>' + esc(s.state) + '</strong>' +
                        (here ? ' <span class="fc-address-book__chip" data-tone="info">This site</span>' : '') + '</td>' +
                        '<td class="text-end">' + num(s.suburbs) + '</td><td class="text-end">' + num(s.streets) + '</td><td class="text-end">' + num(s.addresses) + '</td>' +
                        '<td><span class="fc-address-book__share"><span class="fc-address-book__share-bar"><span style="width:' + (most ? (s.addresses / most * 100).toFixed(1) : 0) + '%"></span></span>' +
                        '<span class="fc-address-book__share-pct">' + share.toFixed(share < 1 ? 1 : 0) + '%</span></span></td></tr>';
                }).join('');
                if (rows) {
                    html += '<div><div class="fc-address-book__section-head"><h4>Coverage by state</h4><span>Busiest first' +
                        (book.stateHint ? ' · ' + esc(book.stateHint) + ' streets are suggested first on this site' : '') + '</span></div>' +
                        '<div class="fc-address-book__states"><table class="fc-entries-table"><thead><tr><th>State</th><th class="text-end">Suburbs</th>' +
                        '<th class="text-end">Streets</th><th class="text-end">Addresses</th><th>Share of addresses</th></tr></thead><tbody>' + rows + '</tbody></table></div></div>';
                }
            }

            html += '<div data-fc-ab-upload>' + this.addressBookUploadHtml() + '</div>';
            if (book.licence) {
                html += '<div class="fc-address-book__licence">' + esc(book.licence) + '</div>';
            }
            body.innerHTML = html;
        }

        /** The import area for the current stage: idle drop zone, chosen file, upload, check, done or failed. */
        addressBookUploadHtml() {
            var esc = global.FC.util.escapeHtml;
            var book = this.addressBook || {};
            var up = this.abUpload || { stage: 'idle' };
            var mb = function (bytes) {
                return (bytes / 1048576).toFixed(1) + ' MB';
            };
            var head = '<div class="fc-address-book__section-head"><h4>' + (book.exists ? 'Replace the address book' : 'Add an address book') + '</h4>' +
                '<span>Checked line by line first. If anything is wrong, nothing changes.</span></div>';

            if (!book.canImport) {
                return '<p class="fc-address-book__muted"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Only the Super Admin can import an address book.</p>';
            }

            var fileRow = function (extra) {
                return '<div class="fc-address-book__file"><span class="fc-address-book__file-icon" aria-hidden="true"><i class="fa-solid fa-file-zipper"></i></span>' +
                    '<span class="fc-address-book__file-text"><span class="fc-address-book__file-name" title="' + esc(up.file.name) + '">' + esc(up.file.name) + '</span>' +
                    '<span class="fc-address-book__file-meta">' + esc(mb(up.file.size)) + (extra ? ' · ' + extra : '') + '</span></span></div>';
            };
            var steps = function (current) {
                var names = ['Upload', 'Check', 'Replace'];
                return '<ol class="fc-address-book__steps">' + names.map(function (name, i) {
                    var state = i < current ? 'done' : (i === current ? 'active' : 'todo');
                    return '<li data-state="' + state + '"><span>' + (state === 'done' ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : (i + 1)) + '</span>' + name + '</li>';
                }).join('') + '</ol>';
            };

            if (up.stage === 'ready') {
                return head + '<div class="fc-address-book__panel">' + fileRow('ready to import') +
                    '<p class="fc-address-book__muted">' + (book.exists
                        ? esc('It replaces the ' + Number(book.streets || 0).toLocaleString('en-AU') + ' streets in use now, once it passes its checks.')
                        : 'Address suggestions start using it as soon as it passes its checks.') + '</p>' +
                    '<div class="fc-address-book__actions"><button type="button" class="btn btn-sm btn-dark" data-fc-ab-action="start"><i class="fa-solid fa-upload me-1" aria-hidden="true"></i>Import</button>' +
                    '<button type="button" class="btn btn-sm btn-light" data-fc-ab-action="choose">Choose another file</button>' +
                    '<button type="button" class="btn btn-sm btn-light" data-fc-ab-action="reset">Cancel</button></div>' +
                    '<input type="file" accept=".gz,.jsonl,application/gzip" class="hidden" data-fc-ab-file></div>';
            }

            if (up.stage === 'uploading' || up.stage === 'checking') {
                var pct = up.stage === 'checking' ? 100 : Math.min(100, Math.round(up.sent / up.file.size * 100));
                var detail = up.stage === 'checking'
                    ? 'Checking every line, then replacing the list in use…'
                    : mb(up.sent) + ' of ' + mb(up.file.size) + (up.eta ? ' · ' + up.eta : '');
                return head + '<div class="fc-address-book__panel" aria-live="polite">' + fileRow(up.stage === 'checking' ? 'uploaded' : 'uploading') +
                    steps(up.stage === 'checking' ? 1 : 0) +
                    '<div class="fc-address-book__progress' + (up.stage === 'checking' ? ' is-indeterminate' : '') + '" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' + pct + '">' +
                    '<span style="width:' + pct + '%"></span></div>' +
                    '<div class="fc-address-book__progress-meta"><span>' + esc(detail) + '</span><strong>' + (up.stage === 'checking' ? '' : pct + '%') + '</strong></div>' +
                    (up.stage === 'uploading' ? '<div class="fc-address-book__actions"><button type="button" class="btn btn-sm btn-light" data-fc-ab-action="cancel">Cancel import</button></div>' : '') +
                    '</div>';
            }

            if (up.stage === 'done') {
                return head + '<div class="fc-address-book__note" data-tone="good"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>' +
                    esc('Imported ' + up.file.name + ': ' + Number(book.streets || 0).toLocaleString('en-AU') + ' streets in ' +
                        Number(book.suburbs || 0).toLocaleString('en-AU') + ' suburbs. Address suggestions use it now.') + '</span>' +
                    '<button type="button" class="btn btn-sm btn-light ms-auto" data-fc-ab-action="reset">Done</button></div>';
            }

            if (up.stage === 'error') {
                return head + '<div class="fc-address-book__note" data-tone="bad"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>' +
                    '<span><strong>' + esc(up.file.name) + ' wasn\'t imported.</strong> ' + esc(up.error) + (book.exists ? ' The current address book is unchanged.' : '') + '</span></div>' +
                    '<div class="fc-address-book__actions"><button type="button" class="btn btn-sm btn-dark" data-fc-ab-action="start">Try again</button>' +
                    '<button type="button" class="btn btn-sm btn-light" data-fc-ab-action="choose">Choose another file</button>' +
                    '<button type="button" class="btn btn-sm btn-light" data-fc-ab-action="reset">Cancel</button></div>' +
                    '<input type="file" accept=".gz,.jsonl,application/gzip" class="hidden" data-fc-ab-file>';
            }

            return head + '<div class="fc-address-book__drop" data-fc-ab-drop data-fc-ab-action="choose" role="button" tabindex="0" aria-label="Choose an address book file to import">' +
                '<span class="fc-address-book__drop-icon" aria-hidden="true"><i class="fa-solid fa-cloud-arrow-up"></i></span>' +
                '<span class="fc-address-book__drop-title">Drop an exported <code>.jsonl.gz</code> here, or <u>choose a file</u></span>' +
                '<input type="file" accept=".gz,.jsonl,application/gzip" class="hidden" data-fc-ab-file></div>' +
                (up.notice ? '<p class="fc-address-book__muted">' + esc(up.notice) + '</p>' : '');
        }

        setAddressBookUpload(next) {
            this.abUpload = next;
            var area = document.querySelector('[data-fc-ab-upload]');
            if (area) {
                area.innerHTML = this.addressBookUploadHtml();
            }
        }

        pickAddressBookFile(file) {
            var stage = this.abUpload && this.abUpload.stage;
            if (stage === 'uploading' || stage === 'checking') {
                return;
            }
            if (!/\.(gz|jsonl)$/i.test(file.name)) {
                this.setAddressBookUpload({ stage: 'idle', notice: file.name + ' isn\'t an address book. Choose the .jsonl.gz from Export, or a .jsonl.' });
                return;
            }
            this.setAddressBookUpload({ stage: 'ready', file: file });
        }

        cancelAddressBookImport() {
            var up = this.abUpload;
            if (!up || up.stage !== 'uploading') {
                return;
            }
            up.cancelled = true;
            if (up.xhr) {
                up.xhr.abort();
            }
            this.setAddressBookUpload({ stage: 'idle', notice: 'Import cancelled. The current address book is unchanged.' });
        }

        /** Sends the file in pieces under any host's upload limit, with progress from each request's upload events. */
        startAddressBookImport() {
            var self = this;
            var book = this.addressBook || {};
            var up = this.abUpload;
            if (!up || !up.file) {
                return;
            }
            var file = up.file;
            var chunk = Number(book.chunkBytes) || 1500000;
            var total = Math.max(1, Math.ceil(file.size / chunk));
            var run = { stage: 'uploading', file: file, sent: 0, started: Date.now(), eta: '' };
            run.id = Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
            this.setAddressBookUpload(run);

            var paint = function () {
                if (self.abUpload !== run) {
                    return;
                }
                var elapsed = (Date.now() - run.started) / 1000;
                if (run.sent > 0 && elapsed > 1.5) {
                    var left = Math.max(0, (file.size - run.sent) / (run.sent / elapsed));
                    run.eta = left < 60 ? Math.ceil(left) + ' s left' : Math.ceil(left / 60) + ' min left';
                }
                self.setAddressBookUpload(run);
            };

            var send = function (index) {
                return new Promise(function (resolve, reject) {
                    var offset = index * chunk;
                    var form = new FormData();
                    form.append('csrf', self.state.csrf);
                    form.append('upload', run.id);
                    form.append('index', String(index));
                    form.append('total', String(total));
                    form.append('offset', String(offset));
                    form.append('size', String(file.size));
                    form.append('chunk', file.slice(offset, offset + chunk), file.name);

                    var xhr = new XMLHttpRequest();
                    run.xhr = xhr;
                    xhr.open('POST', API_ADDRESS_BOOK_IMPORT);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.upload.onprogress = function (e) {
                        if (e.lengthComputable && run.stage === 'uploading') {
                            run.sent = Math.min(file.size, offset + Math.min(e.loaded, chunk));
                            paint();
                        }
                    };
                    // Every byte of the last piece is out: the server is now unpacking and checking it.
                    xhr.upload.onload = function () {
                        if (index === total - 1 && !run.cancelled) {
                            run.stage = 'checking';
                            run.sent = file.size;
                            paint();
                        }
                    };
                    xhr.onload = function () {
                        var data = {};
                        try {
                            data = JSON.parse(xhr.responseText || '{}');
                        } catch (e) {
                            data = {};
                        }
                        if (xhr.status < 200 || xhr.status >= 300 || !data.ok) {
                            reject(new Error(data.error || 'The server stopped the import (HTTP ' + xhr.status + ').'));
                            return;
                        }
                        resolve(data);
                    };
                    xhr.onerror = function () {
                        reject(new Error('The connection dropped part way through. Check the connection and try again.'));
                    };
                    xhr.onabort = function () {
                        reject(new Error('cancelled'));
                    };
                    xhr.send(form);
                }).then(function (data) {
                    return data.done ? data : send(index + 1);
                });
            };

            send(0)
                .then(function (data) {
                    self.renderAddressBook(data.details);
                    self.setAddressBookUpload({ stage: 'done', file: file });
                    global.FC.util.toast('success', data.message || 'Address book imported.', TOAST_ADDRESS_BOOK);
                })
                .catch(function (err) {
                    if (run.cancelled) {
                        return;
                    }
                    self.setAddressBookUpload({ stage: 'error', file: file, error: (err && err.message) || 'The import failed.' });
                    global.FC.util.toast('error', 'The address book wasn\'t imported.', TOAST_ADDRESS_BOOK);
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
