/**
 * FC Admin — Settings → Minify CSS & JS tab: a serving switch, a build and a file grid for each
 * group (frontend and admin, CSS and JS), grouped by area. PHP renders everything from
 * SettingsPresenter::minifyStatus(); this file only copies a later status payload into the rendered
 * slots (data-fc-minify-cell names mirror the presenter's row keys and admin/partials/minify-row.php:
 * edit the three in pairs). A group key is its MinifySettings switch: css, js, adminCss, adminJs.
 */
(function (global) {
    'use strict';

    var API_MINIFY = global.fcApiUrl('settings', 'action=minify');
    var API_MINIFY_BUILD = global.fcApiUrl('settings', 'action=minify-build');
    var TOAST_SAVE = 'fc-minify-save';
    var TOAST_BUILD = 'fc-minify-build';

    // Row slots whose text and title both come from the payload (slot => title key).
    var TITLED_CELLS = { status: 'state_title', serving: 'served_title', source_main: 'source_title', copy_main: 'copy_title', saved: 'saved_title' };
    var TEXT_CELLS = ['name', 'dir', 'status', 'serving', 'source_main', 'source_sub', 'copy_main', 'copy_sub', 'saved_main', 'saved_sub', 'error'];
    var TEXT_KEYS = { status: 'state_label', serving: 'served_label' };

    function infoToast(message, id) {
        var T = global.FcAdminToast;
        if (T && typeof T.show === 'function') {
            T.dismiss(id);
            T.show({ message: message, type: 'info', id: id });
        }
    }

    class MinifyTabController extends global.FC.Settings.TabController {
        constructor() {
            super();
            this.snapshot = 0;
            this.status = null;
            this.filter = {};
            this.sort = {};
        }

        panel() {
            return document.querySelector('[data-fc-minify]');
        }

        card(key) {
            return document.querySelector('[data-fc-minify-card="' + key + '"]');
        }

        /** @returns {string[]} the rendered groups' keys, in page order */
        groupKeys() {
            return Array.prototype.map.call(document.querySelectorAll('[data-fc-minify-card]'), function (card) {
                return card.getAttribute('data-fc-minify-card');
            });
        }

        bind() {
            var self = this;
            var state = this.state;
            var panel = this.panel();
            if (state.minifyBound || !panel) {
                return;
            }
            state.minifyBound = true;
            state.minifyInFlight = {};
            state.minifyPending = {};
            this.snapshot = parseFloat(panel.getAttribute('data-fc-minify-snapshot')) || 0;

            document.querySelectorAll('[data-fc-minify-mode]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.saveToggle(btn.getAttribute('data-fc-minify-mode'), btn.getAttribute('data-fc-minify-value') === '1');
                });
            });
            document.querySelectorAll('[data-fc-minify-toggle]').forEach(function (track) {
                track.addEventListener('click', function () {
                    var key = track.getAttribute('data-fc-minify-toggle');
                    self.saveToggle(key, !(state.minify && state.minify[key]));
                });
            });
            document.querySelectorAll('[data-fc-minify-build]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var which = btn.getAttribute('data-fc-minify-build');
                    self.build(which === 'all' ? self.groupKeys() : [which], btn);
                });
            });
            var refresh = document.getElementById('fc-minify-refresh');
            if (refresh) {
                refresh.addEventListener('click', function () {
                    self.refresh(false);
                });
            }
            document.querySelectorAll('[data-fc-minify-filter]').forEach(function (pill) {
                pill.addEventListener('click', function () {
                    var card = pill.closest('[data-fc-minify-card]');
                    self.setFilter(card.getAttribute('data-fc-minify-card'), pill.getAttribute('data-fc-minify-filter'));
                });
            });
            document.querySelectorAll('[data-fc-minify-tile]').forEach(function (tile) {
                tile.addEventListener('click', function () {
                    self.jumpTo(tile.getAttribute('data-fc-minify-tile'));
                });
            });
            this.paintChecked();
            this.pinOverview(panel.querySelector('[data-fc-overview-pin]'), panel.querySelector('[data-fc-minify-overview]'));
            document.querySelectorAll('[data-fc-minify-sort-key]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var card = btn.closest('[data-fc-minify-card]');
                    self.cycleSort(card.getAttribute('data-fc-minify-card'), btn);
                });
            });
            // Server order is the fallback when a sort is cleared, and the tie-break inside one.
            document.querySelectorAll('[data-fc-minify-table]').forEach(function (table) {
                table.querySelectorAll('[data-fc-minify-file]').forEach(function (row, i) {
                    row.setAttribute('data-fc-minify-index', String(i));
                });
            });
            // The pinned File column casts an edge once the grid has scrolled sideways (see .fc-sites.is-scrolled).
            document.querySelectorAll('[data-fc-minify-table]').forEach(function (table) {
                table.addEventListener('scroll', function () {
                    table.classList.toggle('is-scrolled', table.scrollLeft > 0);
                }, { passive: true });
            });
            panel.addEventListener('click', function (e) {
                var copy = e.target.closest('[data-fc-minify-copy-error]');
                if (!copy) {
                    return;
                }
                var code = copy.parentNode.querySelector('[data-fc-minify-cell="error"]');
                global.FC.util.copyToClipboard(code ? code.textContent : '')
                    .then(function () {
                        global.FC.util.toast('success', 'Error copied.');
                    })
                    .catch(function () {
                        global.FC.util.toast('error', 'Could not copy the error.');
                    });
            });

            this.groupKeys().forEach(function (key) {
                self.paintSwitch(key);
            });
        }

        paintSwitch(key) {
            var enabled = !!(this.state.minify && this.state.minify[key]);
            document.querySelectorAll('[data-fc-minify-mode="' + key + '"]').forEach(function (btn) {
                btn.setAttribute('aria-pressed', (btn.getAttribute('data-fc-minify-value') === '1') === enabled ? 'true' : 'false');
            });
            document.querySelectorAll('[data-fc-minify-toggle="' + key + '"]').forEach(function (track) {
                track.setAttribute('aria-checked', enabled ? 'true' : 'false');
            });
        }

        /** Applies a status payload unless an older one arrives after a newer one already painted. */
        paint(status) {
            var self = this;
            if (!status || !Array.isArray(status.areas)) {
                return;
            }
            var snapshot = parseFloat(status.snapshot) || 0;
            if (snapshot < this.snapshot) {
                return;
            }
            this.snapshot = snapshot;
            this.status = status;
            var state = this.state;
            state.minify = state.minify || {};
            // A switch mid-save keeps its optimistic value: this payload may predate that save.
            Object.keys(status.minify || {}).forEach(function (key) {
                if (!(state.minifyInFlight && state.minifyInFlight[key])) {
                    state.minify[key] = !!status.minify[key];
                }
            });

            this.paintOverview(status.overview);

            var groups = [];
            status.areas.forEach(function (area) {
                var chip = document.querySelector('[data-fc-minify-area="' + area.key + '"] [data-fc-minify-area-chip]');
                if (chip) {
                    chip.setAttribute('data-state', area.chip_state);
                    chip.textContent = area.chip_text;
                }
                groups = groups.concat(area.groups || []);
            });

            groups.forEach(function (group) {
                var card = self.card(group.key);
                if (!card) {
                    return;
                }
                var empty = !group.count;
                ['[data-fc-minify-filters]', '[data-fc-minify-table]'].forEach(function (sel) {
                    var el = card.querySelector(sel);
                    if (el) {
                        el.hidden = empty;
                    }
                });
                var chip = card.querySelector('[data-fc-minify-chip]');
                if (chip) {
                    chip.setAttribute('data-state', group.chip_state);
                    chip.textContent = group.chip_text;
                }
                var meta = card.querySelector('[data-fc-minify-meta]');
                if (meta) {
                    meta.textContent = group.meta;
                }
                self.paintStatusRow(card, group.status);

                (group.filters || []).forEach(function (filter) {
                    var pill = card.querySelector('[data-fc-minify-filter="' + filter.key + '"]');
                    if (!pill) {
                        return;
                    }
                    pill.disabled = !!filter.disabled;
                    pill.title = filter.title || '';
                    var count = pill.querySelector('[data-fc-minify-count]');
                    if (count) {
                        count.textContent = String(filter.count);
                    }
                });

                var table = card.querySelector('[data-fc-minify-table]');
                if (table) {
                    var keep = {};
                    (group.files || []).forEach(function (file, i) {
                        var row = self.findRow(table, file.path) || self.cloneRow(file.path);
                        // Appending in payload order moves existing rows too, so the grid follows the server's sort.
                        table.appendChild(row);
                        self.paintRow(row, file);
                        row.setAttribute('data-fc-minify-index', String(i));
                        keep[file.path] = true;
                    });
                    Array.prototype.slice.call(table.querySelectorAll('[data-fc-minify-file]')).forEach(function (row) {
                        if (!keep[row.getAttribute('data-fc-minify-file')]) {
                            row.parentNode.removeChild(row);
                        }
                    });
                    table.removeAttribute('aria-busy');
                }
                self.applySort(group.key);
                self.applyFilter(group.key);
                self.paintSwitch(group.key);
            });
            // A refresh that landed mid-build must not undress the cards still being built.
            if (state.minifyBusy && state.minifyBuilding) {
                this.setBusy(state.minifyBuilding);
            }
            this.paintBuildControls();
        }

        /** The Site Health-style score, tiles and time; the tiles' text and icons come from the server. */
        paintOverview(overview) {
            var self = this;
            if (!overview) {
                return;
            }
            // The card and the pinned bar carry the same slots.
            document.querySelectorAll('[data-fc-minify-score]').forEach(function (score) {
                score.setAttribute('data-state', overview.state);
            });
            document.querySelectorAll('[data-fc-minify-score-text]').forEach(function (text) {
                text.textContent = overview.text;
            });
            document.querySelectorAll('[data-fc-minify-score-short]').forEach(function (text) {
                text.textContent = overview.short;
            });
            document.querySelectorAll('[data-fc-minify-score-fill]').forEach(function (fill) {
                fill.style.width = (overview.percent || 0) + '%';
            });
            (overview.tiles || []).forEach(function (tile) {
                self.paintTile(tile);
            });
            this.paintChecked();
        }

        paintTile(tile) {
            document.querySelectorAll('[data-fc-minify-tile="' + tile.key + '"]').forEach(function (el) {
                el.setAttribute('data-state', tile.state);
                var icon = el.querySelector('[data-fc-minify-tile-icon]');
                if (icon) {
                    icon.className = 'fa-solid ' + tile.icon;
                }
                var value = el.querySelector('[data-fc-minify-tile-value]');
                if (value) {
                    value.textContent = tile.value;
                }
                // The pinned bar's chips show only their label, so the status goes in the tooltip.
                if (el.classList.contains('fc-overview-pin__tile')) {
                    el.title = (tile.label || (el.firstChild && el.firstChild.nodeValue) || '') + ': ' + tile.value;
                }
            });
        }


        paintChecked() {
            var checked = document.querySelector('[data-fc-minify-checked]');
            if (checked) {
                checked.textContent = 'Checked at ' + new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            }
        }

        /** A tile's card, scrolled to the top and flashed the way Site Health's tiles do it. */
        jumpTo(key) {
            var card = this.card(key);
            if (!card) {
                return;
            }
            var reduceMotion = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;
            card.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
            card.classList.remove('fc-seo-flash');
            void card.offsetWidth;
            card.classList.add('fc-seo-flash');
            setTimeout(function () {
                card.classList.remove('fc-seo-flash');
            }, 1600);
        }

        paintStatusRow(card, status) {
            var row = card.querySelector('[data-fc-minify-status]');
            if (!row || !status) {
                return;
            }
            row.setAttribute('data-state', status.state);
            var icon = row.querySelector('[data-fc-minify-status-icon]');
            if (icon) {
                icon.className = 'fa-solid ' + status.icon;
            }
            var label = row.querySelector('[data-fc-minify-status-label]');
            if (label) {
                label.textContent = status.label;
            }
            var value = row.querySelector('[data-fc-minify-status-value]');
            if (value) {
                value.textContent = status.value;
                value.title = status.value_title || '';
            }
            var detail = row.querySelector('[data-fc-minify-status-detail]');
            if (detail) {
                detail.textContent = status.detail;
            }
        }

        findRow(table, path) {
            var escaped = global.CSS && global.CSS.escape ? global.CSS.escape(path) : path.replace(/["\\]/g, '\\$&');
            return table.querySelector('[data-fc-minify-file="' + escaped + '"]');
        }

        cloneRow(path) {
            var template = document.querySelector('[data-fc-minify-row-template]');
            var row = template.content.firstElementChild.cloneNode(true);
            row.setAttribute('data-fc-minify-file', path);
            return row;
        }

        paintRow(row, file) {
            row.setAttribute('data-state', file.state);
            row.setAttribute('data-fc-minify-sort', file.sort_json || '{}');
            row.setAttribute('data-served', file.served ? '1' : '0');
            var site = row.querySelector('[data-fc-minify-cell="full_path"]');
            if (site) {
                site.title = file.full_path;
            }
            TEXT_CELLS.forEach(function (slot) {
                var el = row.querySelector('[data-fc-minify-cell="' + slot + '"]');
                if (!el) {
                    return;
                }
                var key = TEXT_KEYS[slot] || slot;
                if (slot === 'source_main') {
                    // The date text sits beside the sr-only "newer" note; a row cloned from the blank template has no text node yet.
                    var text = el.firstChild;
                    if (!text || text.nodeType !== 3) {
                        text = document.createTextNode('');
                        el.insertBefore(text, el.firstChild);
                    }
                    text.nodeValue = file.source_main;
                } else {
                    el.textContent = file[key];
                }
                if (TITLED_CELLS[slot]) {
                    el.title = file[TITLED_CELLS[slot]] || '';
                }
            });
            var status = row.querySelector('[data-fc-minify-cell="status"]');
            if (status) {
                status.setAttribute('data-state', file.chip_state);
            }
            var serving = row.querySelector('[data-fc-minify-cell="serving"]');
            if (serving) {
                serving.setAttribute('data-served', file.served ? '1' : '0');
            }
            var sourceMain = row.querySelector('[data-fc-minify-cell="source_main"]');
            if (sourceMain) {
                sourceMain.classList.toggle('fc-minify__main--newer', !!file.source_newer);
            }
            var newer = row.querySelector('[data-fc-minify-cell="source_newer"]');
            if (newer) {
                newer.textContent = file.source_newer ? ' — newer than the copy' : '';
            }
            var saved = row.querySelector('[data-fc-minify-cell="saved"]');
            if (saved) {
                saved.title = file.saved_title || '';
            }
            var drawer = row.querySelector('.fc-minify__error');
            if (drawer) {
                drawer.hidden = !file.error;
            }
        }

        /** A header click: its first direction, then the other, then back to the server's folder order. */
        cycleSort(group, btn) {
            var key = btn.getAttribute('data-fc-minify-sort-key');
            var first = btn.getAttribute('data-fc-minify-sort-first') === 'desc' ? 'desc' : 'asc';
            var current = this.sort[group];
            if (!current || current.key !== key) {
                this.sort[group] = { key: key, dir: first };
            } else if (current.dir === first) {
                this.sort[group] = { key: key, dir: first === 'asc' ? 'desc' : 'asc' };
            } else {
                delete this.sort[group];
            }
            this.applySort(group);
        }

        /** Reorders a card's rows; missing values (no copy, nothing saved) stay last in both directions. */
        applySort(group) {
            var card = this.card(group);
            var table = card ? card.querySelector('[data-fc-minify-table]') : null;
            if (!table) {
                return;
            }
            var sort = this.sort[group] || null;
            var rows = Array.prototype.slice.call(table.querySelectorAll('[data-fc-minify-file]'));
            var value = function (row) {
                try {
                    var v = JSON.parse(row.getAttribute('data-fc-minify-sort') || '{}')[sort.key];
                    return v === undefined ? null : v;
                } catch (e) {
                    return null;
                }
            };
            var index = function (row) {
                return parseInt(row.getAttribute('data-fc-minify-index'), 10) || 0;
            };
            rows.sort(function (a, b) {
                if (sort) {
                    var va = value(a);
                    var vb = value(b);
                    if (va === null || vb === null) {
                        if (va !== vb) {
                            return va === null ? 1 : -1;
                        }
                    } else if (va !== vb) {
                        var cmp = typeof va === 'string' ? va.localeCompare(vb) : va - vb;
                        return sort.dir === 'desc' ? -cmp : cmp;
                    }
                }
                return index(a) - index(b);
            });
            rows.forEach(function (row) {
                table.appendChild(row);
            });
            table.querySelectorAll('[data-fc-minify-sort-key]').forEach(function (btn) {
                var on = !!sort && btn.getAttribute('data-fc-minify-sort-key') === sort.key;
                var cell = btn.closest('[role="columnheader"]');
                if (cell) {
                    cell.setAttribute('aria-sort', on ? (sort.dir === 'asc' ? 'ascending' : 'descending') : 'none');
                }
                btn.classList.toggle('is-active', on);
                var icon = btn.querySelector('[data-fc-minify-sort-icon]');
                if (icon) {
                    icon.className = 'fa-solid ' + (on ? (sort.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort');
                }
            });
        }

        setFilter(group, filter) {
            this.filter[group] = filter;
            this.applyFilter(group);
        }

        /** Hides rows the active pill excludes; a pill whose count dropped to 0 falls back to All. */
        applyFilter(group) {
            var card = this.card(group);
            if (!card) {
                return;
            }
            var filter = this.filter[group] || 'all';
            var active = card.querySelector('[data-fc-minify-filter="' + filter + '"]');
            if (active && active.disabled) {
                filter = 'all';
                this.filter[group] = filter;
            }
            card.querySelectorAll('[data-fc-minify-filter]').forEach(function (pill) {
                pill.setAttribute('aria-pressed', pill.getAttribute('data-fc-minify-filter') === filter ? 'true' : 'false');
            });
            card.querySelectorAll('[data-fc-minify-file]').forEach(function (row) {
                var state = row.getAttribute('data-state');
                row.hidden = !(filter === 'all' || (filter === 'fresh' && state === 'fresh') || (filter === 'rebuild' && state !== 'fresh'));
            });
        }

        paintBuildControls() {
            var busy = !!this.state.minifyBusy;
            document.querySelectorAll('[data-fc-minify-build]').forEach(function (btn) {
                if (!btn.classList.contains('is-saving')) {
                    btn.disabled = busy;
                }
            });
            var refresh = document.getElementById('fc-minify-refresh');
            if (refresh && !refresh.classList.contains('is-saving')) {
                refresh.disabled = busy;
            }
        }

        /**
         * Saves one switch. Only the flipped key is posted (the server merges onto disk), and a click
         * during a save is queued rather than dropped, so a fast Off→On→Off ends where it was left.
         */
        saveToggle(key, next) {
            var self = this;
            var state = this.state;
            next = !!next;
            state.minify = state.minify || {};
            if (!!state.minify[key] === next) {
                this.paintSwitch(key);
                return;
            }
            state.minify[key] = next;
            this.paintSwitch(key);

            if (state.minifyInFlight[key]) {
                state.minifyPending[key] = next;
                return;
            }
            state.minifyInFlight[key] = true;
            // The switch carries the server's name for the group, e.g. "Minify admin CSS".
            var track = document.querySelector('[data-fc-minify-toggle="' + key + '"]');
            var label = (track && track.getAttribute('aria-label')) || 'Minify';
            global.FC.util.toast('saving', 'Turning ' + label + (next ? ' on…' : ' off…'), TOAST_SAVE);

            var body = { minify: {}, csrf: state.csrf };
            body.minify[key] = next;
            // What the disk holds once this request settles; a queued click is measured against it, not the optimistic switch.
            var saved = !next;

            fetch(API_MINIFY, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(body)
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok || !data.ok) {
                            throw new Error((data && data.error) || 'Save failed');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    saved = data.minify && typeof data.minify[key] === 'boolean' ? data.minify[key] : next;
                    self.paint(data);
                    global.FC.util.toast('success', data.message || (label + ' saved.'), TOAST_SAVE);
                })
                .catch(function (err) {
                    state.minify[key] = saved;
                    self.paintSwitch(key);
                    if (self.status) {
                        self.paint(self.status);
                    }
                    global.FC.util.toast('error', (err && err.message) || 'Could not save ' + label + '.', TOAST_SAVE);
                })
                .then(function () {
                    state.minifyInFlight[key] = false;
                    if (Object.prototype.hasOwnProperty.call(state.minifyPending, key)) {
                        var queued = state.minifyPending[key];
                        delete state.minifyPending[key];
                        state.minify[key] = saved;
                        self.saveToggle(key, queued);
                    }
                });
        }

        /** Marks the cards being rebuilt; the response's paint() restores them. */
        setBusy(groups) {
            var self = this;
            groups.forEach(function (key) {
                var card = self.card(key);
                if (!card) {
                    return;
                }
                self.paintTile({ key: key, state: 'info', icon: 'fa-circle-notch fa-spin', value: 'Minifying…' });
                var count = card.querySelector('[data-fc-minify-count="all"]');
                self.paintStatusRow(card, {
                    state: 'info',
                    icon: 'fa-circle-notch fa-spin',
                    label: 'Minifying ' + (count ? count.textContent + ' ' : '') + 'files…',
                    value: '',
                    value_title: '',
                    detail: ''
                });
                var chip = card.querySelector('[data-fc-minify-chip]');
                if (chip) {
                    chip.setAttribute('data-state', 'info');
                    chip.textContent = 'Minifying…';
                }
                var table = card.querySelector('[data-fc-minify-table]');
                if (table) {
                    table.setAttribute('aria-busy', 'true');
                }
            });
        }

        build(groups, btn) {
            var self = this;
            var state = this.state;
            if (state.minifyBusy) {
                return;
            }
            state.minifyBusy = true;
            state.minifyBuilding = groups.slice();
            global.FC.util.setSaving(btn, true);
            this.paintBuildControls();
            this.setBusy(groups);
            var total = 0;
            groups.forEach(function (key) {
                var card = self.card(key);
                var count = card ? card.querySelector('[data-fc-minify-count="all"]') : null;
                total += count ? parseInt(count.textContent, 10) || 0 : 0;
            });
            global.FC.util.toast('saving', 'Minifying ' + total + ' files…', TOAST_BUILD);

            fetch(API_MINIFY_BUILD, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ types: groups, csrf: state.csrf })
            })
                .then(function (res) {
                    return res
                        .json()
                        .catch(function () {
                            return {};
                        })
                        .then(function (data) {
                            return { status: res.status, data: data || {} };
                        });
                })
                .then(function (result) {
                    var data = result.data;
                    // The build is over before its status paints, or paint() would dress the cards as busy again.
                    state.minifyBusy = false;
                    state.minifyBuilding = null;
                    if (Array.isArray(data.areas)) {
                        self.paint(data);
                    } else {
                        self.refresh(true);
                    }
                    if (result.status === 200 && data.ok) {
                        global.FC.util.toast('success', data.message || 'Minified.', TOAST_BUILD);
                    } else if (result.status === 409) {
                        infoToast(data.error || 'Another build is already running — try again in a moment.', TOAST_BUILD);
                    } else {
                        global.FC.util.toast('error', data.error || 'The build could not run.', TOAST_BUILD);
                    }
                })
                .catch(function () {
                    state.minifyBusy = false;
                    state.minifyBuilding = null;
                    self.refresh(true);
                    global.FC.util.toast('error', 'The build could not run.', TOAST_BUILD);
                })
                .then(function () {
                    global.FC.util.setSaving(btn, false);
                    self.paintBuildControls();
                });
        }

        /** Re-reads the disk: the dates change under a CLI build or a git pull. Quiet unless it fails. */
        refresh(quiet) {
            var self = this;
            var refreshBtn = document.getElementById('fc-minify-refresh');
            if (!this.panel() || (this.state.minifyBusy && !quiet)) {
                return;
            }
            // setSaving() answers false while the button already spins: one refresh at a time.
            if (!quiet && !global.FC.util.setSaving(refreshBtn, true)) {
                return;
            }
            document.querySelectorAll('[data-fc-minify-table]').forEach(function (table) {
                table.setAttribute('aria-busy', 'true');
            });

            fetch(API_MINIFY, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok || !data.ok) {
                            throw new Error((data && data.error) || 'Refresh failed');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    self.paint(data);
                })
                .catch(function () {
                    global.FC.util.toast('error', 'Could not refresh the minify status.');
                })
                .then(function () {
                    var building = (self.state.minifyBusy && self.state.minifyBuilding) || [];
                    document.querySelectorAll('[data-fc-minify-card]').forEach(function (card) {
                        var table = card.querySelector('[data-fc-minify-table]');
                        if (table && building.indexOf(card.getAttribute('data-fc-minify-card')) === -1) {
                            table.removeAttribute('aria-busy');
                        }
                    });
                    if (!quiet) {
                        global.FC.util.setSaving(refreshBtn, false);
                        self.paintBuildControls();
                    }
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.minify = new MinifyTabController();
})(window);
