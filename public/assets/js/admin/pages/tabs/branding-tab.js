/**
 * FC Admin — Settings → Branding tab.
 * Extracted from settings.js. See pages/tabs/system-tab.js for the
 * shared-shell access pattern this and every other extracted tab uses.
 */
(function (global) {
    'use strict';

    var API_BRANDING = global.fcApiUrl('settings', 'action=branding');
    var TOAST_BRANDING = 'fc-branding-save';
    var BRANDING_FIELD_ORDER = ['logo', 'appName', 'tagline', 'version'];

    class BrandingTabController extends global.FC.Settings.TabController {
        setDirty(isDirty) {
            this.state.brandingDirty = !!isDirty;
            this.updateHeaderActions();
        }

        assetUrl(path) {
            var value = String(path || '').trim();
            if (!value) {
                return '';
            }
            if (/^https?:\/\//i.test(value) || /^data:/i.test(value) || value.indexOf('//') === 0) {
                return value;
            }
            var base = this.getAppBase().replace(/\/+$/, '');
            return base + '/' + value.replace(/^\/+/, '');
        }

        fieldKeys() {
            var state = this.state;
            return BRANDING_FIELD_ORDER.filter(function (key) {
                return state.brandingSchema[key];
            });
        }

        updateLogoPreview() {
            var state = this.state;
            var self = this;

            // Logo preview (large)
            var logoPreview = document.getElementById('fc-branding-logo-preview');
            var logoSidebar = document.getElementById('fc-branding-preview-logo');
            var logoPath = String(state.branding.logo || '').trim();
            var logoUrl = self.assetUrl(logoPath);

            [logoPreview, logoSidebar].forEach(function (el) {
                if (!el) {
                    return;
                }
                el.classList.toggle('fc-settings-branding-logo__preview--empty', !logoUrl);
                if (logoUrl) {
                    el.style.setProperty('--fc-branding-logo-preview', 'url(' + logoUrl + ')');
                } else {
                    el.style.removeProperty('--fc-branding-logo-preview');
                }
            });

            // Favicon preview (small)
            var faviconPreview = document.getElementById('fc-branding-favicon-preview');
            var faviconSidebar = document.getElementById('fc-branding-preview-favicon');
            var faviconPath = String(state.branding.favicon || '').trim();
            var faviconUrl = self.assetUrl(faviconPath);

            [faviconPreview, faviconSidebar].forEach(function (el) {
                if (!el) {
                    return;
                }
                el.classList.toggle('fc-settings-branding-logo__preview--empty', !faviconUrl);
                if (faviconUrl) {
                    el.style.setProperty('--fc-branding-logo-preview', 'url(' + faviconUrl + ')');
                } else {
                    el.style.removeProperty('--fc-branding-logo-preview');
                }
            });
        }

        updatePreview() {
            var state = this.state;
            var titleEl = document.getElementById('fc-branding-preview-title');
            var taglineEl = document.getElementById('fc-branding-preview-tagline');
            var footerNameEl = document.getElementById('fc-branding-preview-footer-name');
            var versionEl = document.getElementById('fc-branding-preview-version');

            var appName = state.branding.appName || 'Fencing Calculator';
            var tagline = state.branding.tagline || '';
            var version = state.branding.version || '';

            if (titleEl) {
                titleEl.textContent = appName;
            }
            if (footerNameEl) {
                footerNameEl.textContent = appName;
            }
            if (taglineEl) {
                taglineEl.textContent = tagline;
            }
            if (versionEl) {
                versionEl.textContent = version;
            }
            this.updateLogoPreview();
        }

        /** Static sidebar preview markup — built once when the Branding tab becomes active. */
        renderPreview() {
            return (
                '<div class="rounded-xl border border-slate-200 bg-white p-4">' +
                '<p class="mb-3 text-sm font-semibold text-slate-800">Live preview</p>' +
                '<div class="space-y-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-sm">' +
                '<div class="border-b border-slate-200 px-3 py-3">' +
                '<div class="flex items-start gap-4">' +
                '<div class="flex flex-col items-start">' +
                '<p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Logo</p>' +
                '<div id="fc-branding-preview-logo" class="fc-settings-branding-logo__preview fc-settings-branding-logo__preview--sidebar fc-settings-branding-logo__preview--empty" style="width:48px;height:48px;">' +
                '<span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-border-all"></i></span>' +
                '</div></div>' +
                '<div class="flex flex-col items-start">' +
                '<p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Favicon</p>' +
                '<div id="fc-branding-preview-favicon" class="fc-settings-branding-logo__preview fc-settings-branding-logo__preview--sidebar fc-settings-branding-logo__preview--empty">' +
                '<span class="fc-settings-branding-logo__preview-fallback" aria-hidden="true"><i class="fa-solid fa-image"></i></span>' +
                '</div></div>' +
                '</div></div>' +
                '<div class="border-b border-slate-200 px-3 py-3">' +
                '<p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">App name</p>' +
                '<p id="fc-branding-preview-title" class="truncate font-bold leading-snug text-slate-900">Fencing Calculator</p>' +
                '</div>' +
                '<div class="border-b border-slate-200 px-3 py-3">' +
                '<p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tagline</p>' +
                '<p id="fc-branding-preview-tagline" class="leading-snug text-slate-600">Calculate your fence cost and the materials needed.</p>' +
                '</div>' +
                '<div class="px-3 py-3">' +
                '<p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Footer</p>' +
                '<p id="fc-branding-preview-footer" class="truncate text-xs text-slate-500">' +
                '<span id="fc-branding-preview-footer-name">Fencing Calculator</span> ' +
                '<span id="fc-branding-preview-version">v10.0.0 beta</span>' +
                '</p></div></div>' +
                '<p class="mt-3 text-xs text-slate-500">Saved branding applies on the <a class="font-medium text-indigo-600 hover:text-indigo-700" href="../planner" target="_blank" rel="noopener">planner</a> after save (refresh if already open).</p>' +
                '</div>'
            );
        }

        paint() {
            var state = this.state;
            this.fieldKeys().forEach(function (key) {
                var input = document.querySelector('[data-fc-branding-field="' + key + '"]');
                if (input) {
                    input.value = state.branding[key] || '';
                }
            });
            this.updatePreview();
        }

        bind() {
            var self = this;
            var state = this.state;

            document.querySelectorAll('[data-fc-branding-field]').forEach(function (input) {
                if (input.getAttribute('data-fc-branding-bound') === '1') {
                    return;
                }
                input.setAttribute('data-fc-branding-bound', '1');
                input.addEventListener('input', function () {
                    var key = input.getAttribute('data-fc-branding-field');
                    state.branding[key] = input.value;
                    self.updatePreview();
                    self.setDirty(true);
                });
            });

            document.querySelectorAll('[data-fc-branding-pick]').forEach(function (btn) {
                if (btn.getAttribute('data-fc-branding-bound') === '1') {
                    return;
                }
                btn.setAttribute('data-fc-branding-bound', '1');
                btn.addEventListener('click', function () {
                    var container = btn.closest('.fc-settings-branding-logo');
                    if (!container) {
                        return;
                    }
                    var input = container.querySelector('input[data-fc-branding-field]');
                    if (!input || !global.FcAdminMediaPicker || typeof global.FcAdminMediaPicker.open !== 'function') {
                        return;
                    }
                    var key = input.getAttribute('data-fc-branding-field');
                    global.FcAdminMediaPicker.open({
                        appBase: self.getAppBase(),
                        csrf: state.csrf,
                        onSelect: function (path) {
                            input.value = path;
                            if (key) {
                                state.branding[key] = path;
                            }
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            self.updatePreview();
                        }
                    });
                });
            });

            document.querySelectorAll('[data-fc-branding-clear]').forEach(function (btn) {
                if (btn.getAttribute('data-fc-branding-bound') === '1') {
                    return;
                }
                btn.setAttribute('data-fc-branding-bound', '1');
                btn.addEventListener('click', function () {
                    var container = btn.closest('.fc-settings-branding-logo');
                    if (!container) {
                        return;
                    }
                    var input = container.querySelector('input[data-fc-branding-field]');
                    if (!input) {
                        return;
                    }
                    var key = input.getAttribute('data-fc-branding-field');
                    input.value = '';
                    if (key) {
                        state.branding[key] = '';
                    }
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    self.updatePreview();
                });
            });

            var saveBtn = document.getElementById('fc-branding-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }

            var resetBtn = document.getElementById('fc-branding-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    var Modal = global.FcAdminModal;
                    var ask =
                        Modal && typeof Modal.confirm === 'function'
                            ? Modal.confirm({
                                  title: 'Reset branding',
                                  message: 'Reset all branding fields to defaults?',
                                  confirmLabel: 'Reset',
                                  cancelLabel: 'Cancel'
                              })
                            : Promise.resolve(window.confirm('Reset all branding fields to defaults?'));

                    ask.then(function (ok) {
                        if (!ok) {
                            return;
                        }
                        state.branding = Object.assign({}, state.brandingDefaults);
                        self.paint();
                        self.setDirty(true);
                    });
                });
            }
        }

        save() {
            var self = this;
            var state = this.state;
            global.FC.util.toast('saving', 'Saving branding…', TOAST_BRANDING);
            fetch(API_BRANDING, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ branding: state.branding, csrf: state.csrf })
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
                    state.branding = body.branding || state.branding;
                    state.brandingDefaults = body.defaults || state.brandingDefaults;
                    state.brandingSchema = body.schema || state.brandingSchema;
                    self.paint();
                    self.setDirty(false);
                    self.flash.set(body.message || 'Branding saved — refresh the planner to see changes.', 'success');
                    try {
                        var next = new URL(window.location.href);
                        window.location.assign(next.pathname + next.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    global.FC.util.toast('error', err.message || 'Could not save branding.', TOAST_BRANDING);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.branding = new BrandingTabController();
})(window);
