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
        }

        save() {
            var self = this;
            var state = this.state;
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
                    global.FC.util.toast('error', err.message || 'Could not save system settings.', TOAST_SYSTEM);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.system = new SystemTabController();
})(window);
