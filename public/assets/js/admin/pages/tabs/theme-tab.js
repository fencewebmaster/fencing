/**
 * FC Admin — Settings → Theme tab.
 * Extracted from settings.js. Uses the shared normalizeHexInput() from
 * SettingsTabController (also used by Fence Colors).
 */
(function (global) {
    'use strict';

    var API_THEME = global.fcApiUrl('settings', 'action=theme');
    var TOAST_THEME = 'fc-theme-save';

    class ThemeTabController extends global.FC.Settings.TabController {
        setDirty(isDirty) {
            this.state.themeDirty = !!isDirty;
            this.updateHeaderActions();
        }

        applyLiveTheme() {
            if (global.FcTheme && typeof global.FcTheme.apply === 'function') {
                global.FcTheme.apply(this.state.colors);
            }
        }

        colorsMatchPreset(colors, preset) {
            if (!preset || !preset.colors) {
                return false;
            }
            return Object.keys(preset.colors).every(function (varName) {
                return (colors[varName] || '').toLowerCase() === (preset.colors[varName] || '').toLowerCase();
            });
        }

        detectMatchingPreset(colors) {
            var self = this;
            var match = null;
            this.state.presets.forEach(function (preset) {
                if (!match && self.colorsMatchPreset(colors, preset)) {
                    match = preset.id;
                }
            });
            return match;
        }

        updatePresetCards() {
            var state = this.state;
            document.querySelectorAll('[data-fc-theme-preset]').forEach(function (btn) {
                var id = btn.getAttribute('data-fc-theme-preset');
                var isSelected = state.selectedPreset === id;
                var isActive = state.activePreset === id;

                btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                btn.classList.remove(
                    'fc-theme-preset--selected',
                    'border-slate-200',
                    'bg-slate-50/50',
                    'hover:border-slate-300',
                    'hover:bg-white'
                );

                if (isSelected) {
                    btn.classList.add('fc-theme-preset--selected');
                } else {
                    btn.classList.add(
                        'border-slate-200',
                        'bg-slate-50/50',
                        'hover:border-slate-300',
                        'hover:bg-white'
                    );
                }

                var badge = btn.querySelector('[data-fc-theme-active-badge]');
                if (badge) {
                    badge.classList.toggle('hidden', !isActive);
                }
            });
        }

        syncSelectedPresetFromColors() {
            var state = this.state;
            if (!state.selectedPreset) {
                return;
            }
            var preset = state.presets.find(function (p) {
                return p.id === state.selectedPreset;
            });
            if (!preset || !this.colorsMatchPreset(state.colors, preset)) {
                state.selectedPreset = null;
            }
        }

        applyPreset(presetId) {
            var state = this.state;
            var preset = state.presets.find(function (p) {
                return p.id === presetId;
            });
            if (!preset) {
                return;
            }
            state.colors = Object.assign({}, preset.colors);
            state.selectedPreset = presetId;
            this.paint();
            this.applyLiveTheme();
            this.setDirty(true);
            this.updatePresetCards();
        }

        syncPickerToHex(varName, value) {
            var picker = document.querySelector('[data-fc-theme-var="' + varName + '"]');
            var hex = document.querySelector('[data-fc-theme-hex="' + varName + '"]');
            if (picker && /^#[0-9a-fA-F]{6}$/.test(value)) {
                picker.value = value.toLowerCase();
            }
            if (hex) {
                hex.value = value;
            }
        }

        paint() {
            var self = this;
            Object.keys(this.state.colors).forEach(function (varName) {
                self.syncPickerToHex(varName, self.state.colors[varName]);
            });
            this.applyLiveTheme();
        }

        bind() {
            var self = this;
            var state = this.state;

            document.querySelectorAll('[data-fc-theme-var]').forEach(function (picker) {
                picker.addEventListener('input', function () {
                    var varName = picker.getAttribute('data-fc-theme-var');
                    var value = picker.value;
                    state.colors[varName] = value;
                    self.syncPickerToHex(varName, value);
                    self.applyLiveTheme();
                    self.syncSelectedPresetFromColors();
                    self.updatePresetCards();
                    self.setDirty(true);
                });
            });

            document.querySelectorAll('[data-fc-theme-hex]').forEach(function (input) {
                input.addEventListener('input', function () {
                    var varName = input.getAttribute('data-fc-theme-hex');
                    var normalized = self.normalizeHexInput(input.value);
                    if (!normalized) {
                        return;
                    }
                    state.colors[varName] = normalized;
                    self.syncPickerToHex(varName, normalized);
                    self.applyLiveTheme();
                    self.syncSelectedPresetFromColors();
                    self.updatePresetCards();
                    self.setDirty(true);
                });
                input.addEventListener('blur', function () {
                    var varName = input.getAttribute('data-fc-theme-hex');
                    var normalized = self.normalizeHexInput(input.value);
                    if (normalized) {
                        input.value = normalized;
                        state.colors[varName] = normalized;
                        self.syncPickerToHex(varName, normalized);
                    }
                });
            });

            var saveBtn = document.getElementById('fc-theme-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }

            var resetBtn = document.getElementById('fc-theme-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    var Modal = global.FcAdminModal;
                    var ask =
                        Modal && typeof Modal.confirm === 'function'
                            ? Modal.confirm({
                                  title: 'Reset theme',
                                  message: 'Reset all theme colors to defaults?',
                                  confirmLabel: 'Reset',
                                  cancelLabel: 'Cancel'
                              })
                            : Promise.resolve(window.confirm('Reset all theme colors to defaults?'));

                    ask.then(function (ok) {
                        if (!ok) {
                            return;
                        }
                        state.colors = Object.assign({}, state.defaults);
                        self.paint();
                        self.applyLiveTheme();
                        state.selectedPreset = self.detectMatchingPreset(state.colors);
                        self.updatePresetCards();
                        self.setDirty(true);
                    });
                });
            }

            document.querySelectorAll('[data-fc-theme-preset]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.applyPreset(btn.getAttribute('data-fc-theme-preset'));
                });
            });
        }

        save() {
            var self = this;
            var state = this.state;
            global.FC.util.toast('saving', 'Saving theme…', TOAST_THEME);
            fetch(API_THEME, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ colors: state.colors, csrf: state.csrf })
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
                    state.colors = body.colors || state.colors;
                    state.defaults = body.defaults || state.defaults;
                    state.schema = body.schema || state.schema;
                    state.presets = body.presets || state.presets;
                    state.activePreset = body.activePreset || self.detectMatchingPreset(state.colors);
                    state.selectedPreset = state.activePreset;
                    self.paint();
                    self.updatePresetCards();
                    self.setDirty(false);
                    self.flash.set(body.message || 'Theme saved — refresh the planner to see changes.', 'success');
                    try {
                        var next = new URL(window.location.href);
                        window.location.assign(next.pathname + next.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    global.FC.util.toast('error', err.message || 'Could not save theme.', TOAST_THEME);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.theme = new ThemeTabController();
})(window);
