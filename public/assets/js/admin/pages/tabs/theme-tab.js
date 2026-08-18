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

        presetCardClasses(isSelected) {
            return (
                'fc-theme-preset group flex items-start gap-3 border-2 p-4 text-left transition ' +
                (isSelected
                    ? 'fc-theme-preset--selected'
                    : 'border-slate-200 bg-slate-50/50 hover:border-slate-300 hover:bg-white')
            );
        }

        hexToRgb(hex) {
            var normalized = String(hex || '')
                .replace(/^#/, '')
                .toLowerCase();
            if (normalized.length === 3) {
                normalized = normalized
                    .split('')
                    .map(function (char) {
                        return char + char;
                    })
                    .join('');
            }
            if (normalized.length !== 6 || !/^[0-9a-f]{6}$/.test(normalized)) {
                return null;
            }
            return {
                r: parseInt(normalized.slice(0, 2), 16),
                g: parseInt(normalized.slice(2, 4), 16),
                b: parseInt(normalized.slice(4, 6), 16)
            };
        }

        presetAccentColor(preset) {
            return preset.swatch || preset.colors['--fc-princeton-orange'] || '#f67925';
        }

        presetActiveBadgeStyles(accent) {
            var rgb = this.hexToRgb(accent);
            if (!rgb) {
                return 'color:#f67925;border-color:rgba(246,121,37,0.35);background:rgba(246,121,37,0.12);';
            }
            return (
                'color:' +
                accent +
                ';border-color:rgba(' +
                rgb.r +
                ',' +
                rgb.g +
                ',' +
                rgb.b +
                ',0.35);background:rgba(' +
                rgb.r +
                ',' +
                rgb.g +
                ',' +
                rgb.b +
                ',0.12);'
            );
        }

        presetActiveBadge(isActive, accent) {
            return (
                '<span data-fc-theme-active-badge class="' +
                (isActive ? '' : 'hidden ') +
                'mt-2 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold shadow-sm" style="' +
                this.presetActiveBadgeStyles(accent) +
                '">' +
                '<i class="fa-solid fa-circle-check text-[11px]" aria-hidden="true"></i> Active</span>'
            );
        }

        renderPresets() {
            var self = this;
            var escapeHtml = global.FC.util.escapeHtml;
            var state = this.state;
            if (!state.presets.length) {
                return '';
            }

            return (
                '<section class="border border-slate-200 bg-white p-4 sm:p-5">' +
                '<h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-500">Presets</h3>' +
                '<p class="mb-4 text-sm text-slate-500">Apply a ready-made palette. You can fine-tune individual colors below.</p>' +
                '<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">' +
                state.presets
                    .map(function (preset) {
                        var isActive = state.activePreset === preset.id;
                        var isSelected = state.selectedPreset === preset.id;
                        return (
                            '<button type="button" data-fc-theme-preset="' +
                            escapeHtml(preset.id) +
                            '" class="' +
                            self.presetCardClasses(isSelected) +
                            '" aria-pressed="' +
                            (isSelected ? 'true' : 'false') +
                            '">' +
                            '<span class="mt-0.5 flex h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 shadow-sm" aria-hidden="true">' +
                            '<span class="h-full w-1/2" style="background:' +
                            escapeHtml(preset.swatch || preset.colors['--fc-princeton-orange'] || '#d4112f') +
                            '"></span>' +
                            '<span class="h-full w-1/2" style="background:' +
                            escapeHtml(preset.colors['--fc-brand-primary'] || '#d4112f') +
                            '"></span></span>' +
                            '<span class="min-w-0 flex-1">' +
                            '<span class="block text-sm font-semibold text-slate-900">' +
                            escapeHtml(preset.label) +
                            '</span>' +
                            '<span class="mt-0.5 block text-xs leading-relaxed text-slate-500">' +
                            escapeHtml(preset.description) +
                            '</span>' +
                            self.presetActiveBadge(isActive, self.presetAccentColor(preset)) +
                            '</span></button>'
                        );
                    })
                    .join('') +
                '</div></section>'
            );
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

        renderPreview() {
            return (
                '<div class="rounded-xl border border-slate-200 bg-white p-4">' +
                '<p class="mb-3 text-sm font-semibold text-slate-800">Live preview</p>' +
                '<div class="flex flex-wrap gap-2">' +
                '<span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-princeton-orange)">Primary button</span>' +
                '<span class="inline-flex items-center rounded-lg border-2 px-4 py-2 text-sm font-semibold" style="border-color:var(--fc-princeton-orange);color:var(--fc-princeton-orange)">Outline</span>' +
                '<span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-brand-primary)">Brand</span>' +
                '<span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:var(--fc-green)">Success</span>' +
                '</div>' +
                '<p class="mt-3 text-sm" style="color:var(--fc-dark-charcoal)">Body text uses dark charcoal.</p>' +
                '<p class="text-xs" style="color:var(--fc-dark-medium-gray)">Secondary label text.</p>' +
                '<div class="mt-3 rounded-lg border p-3" style="border-color:var(--fc-gray);background:var(--fc-bright-gray)">Surface panel</div>' +
                '<p class="mt-3 text-xs text-slate-500">Saved theme applies on the <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../planner" target="_blank" rel="noopener">planner</a> after save (refresh if already open).</p>' +
                '</div>'
            );
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
