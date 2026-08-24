/**
 * FC Admin — Settings → Integration tab.
 * The panel markup itself is server-rendered PHP (no render* function here,
 * matching the System tab); this only paints/binds/saves it.
 */
(function (global) {
    'use strict';

    var API_INTEGRATIONS = global.fcApiUrl('settings', 'action=integrations');
    var API_CLOUDFLARE_VERIFY = global.fcApiUrl('settings', 'action=cloudflare-verify');
    var TOAST_INTEGRATIONS = 'fc-integrations-save';
    var TOAST_CLOUDFLARE_VERIFY = 'fc-cloudflare-verify';

    class IntegrationTabController extends global.FC.Settings.TabController {
        clone(value) {
            try {
                return JSON.parse(JSON.stringify(value || {}));
            } catch (e) {
                return Object.assign({}, value || {});
            }
        }

        setDirty(isDirty) {
            this.state.integrationDirty = !!isDirty;
            this.updateHeaderActions();
        }

        paint() {
            var state = this.state;
            document.querySelectorAll('[data-fc-integration-field]').forEach(function (input) {
                var key = input.getAttribute('data-fc-integration-field');
                var value = state.integrations[key];
                if (input.type === 'radio') {
                    input.checked = (input.value === String(value == null ? '' : value));
                    return;
                }
                if (input.type === 'checkbox') {
                    input.checked = !!value;
                    return;
                }
                input.value = value || '';
            });

            var sites = Array.isArray(state.integrations.sites) ? state.integrations.sites : [];
            document.querySelectorAll('[data-fc-integration-site]').forEach(function (input) {
                var siteKey = input.getAttribute('data-fc-integration-site');
                var field = input.getAttribute('data-fc-integration-site-field');
                var site = sites.find(function (row) {
                    return String(row.key || '') === siteKey;
                });
                input.value = site && field ? site[field] || '' : '';
            });

            this.syncPrePlannerDependents();
        }

        syncPrePlannerDependents() {
            var enabled = !!this.state.integrations.webhookPrePlannerEnabled;
            var input = document.getElementById('fc-integration-webhookSameDayDedup');
            var row = document.getElementById('fc-integration-webhookSameDayDedup-row');
            if (input) {
                input.disabled = !enabled;
            }
            if (row) {
                row.classList.toggle('opacity-50', !enabled);
                row.classList.toggle('cursor-not-allowed', !enabled);
                var toggleWrap = row.querySelector('.relative.inline-flex');
                if (toggleWrap) {
                    toggleWrap.classList.toggle('cursor-pointer', enabled);
                    toggleWrap.classList.toggle('cursor-not-allowed', !enabled);
                }
            }
        }

        showVerifyFeedback(btn, ok) {
            if (!btn) {
                return;
            }
            var icon = btn.querySelector('i');
            if (!icon) {
                return;
            }
            btn.classList.remove('is-verifying', 'is-verified', 'is-verify-failed');
            if (ok) {
                icon.className = 'fa-solid fa-check';
                btn.classList.add('is-verified');
            } else {
                icon.className = 'fa-solid fa-xmark';
                btn.classList.add('is-verify-failed');
            }
            window.setTimeout(function () {
                icon.className = 'fa-solid fa-plug';
                btn.classList.remove('is-verified', 'is-verify-failed');
            }, 2000);
        }

        verifyCloudflareZone(btn) {
            var self = this;
            var state = this.state;
            if (!btn || btn.disabled || btn.classList.contains('is-verifying')) {
                return;
            }

            var zoneInputId = btn.getAttribute('data-fc-cloudflare-zone-for') || '';
            var zoneInput = zoneInputId ? document.getElementById(zoneInputId) : null;
            var zoneId = zoneInput ? String(zoneInput.value || '').trim() : '';
            var siteKey = btn.getAttribute('data-fc-cloudflare-site') || '';
            var token = String(state.integrations.cloudflareApiToken || '').trim();
            var T = global.FcAdminToast;

            if (!zoneId) {
                if (T) {
                    T.error('Enter a Cloudflare Zone ID first.');
                }
                self.showVerifyFeedback(btn, false);
                return;
            }

            var icon = btn.querySelector('i');
            btn.disabled = true;
            btn.classList.add('is-verifying');
            btn.classList.remove('is-verified', 'is-verify-failed');
            if (icon) {
                icon.className = 'fa-solid fa-spinner fa-spin';
            }
            if (T) {
                T.loading('Checking Cloudflare connection…', TOAST_CLOUDFLARE_VERIFY);
            }

            fetch(API_CLOUDFLARE_VERIFY, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    cloudflareApiToken: token,
                    cloudflareZoneId: zoneId,
                    siteKey: siteKey,
                    csrf: state.csrf || ''
                })
            })
                .then(function (res) {
                    return res.json().catch(function () {
                        return { ok: false, error: 'Invalid response from server.' };
                    }).then(function (data) {
                        return { status: res.status, data: data || {} };
                    });
                })
                .then(function (result) {
                    btn.disabled = false;
                    btn.classList.remove('is-verifying');
                    if (T) {
                        T.dismiss(TOAST_CLOUDFLARE_VERIFY);
                    }
                    var data = result.data || {};
                    if (data.ok) {
                        var zoneName = String(data.zoneName || '').trim();
                        var msg = zoneName ? 'Connected: ' + zoneName : 'Cloudflare zone connected.';
                        if (T) {
                            T.success(msg);
                        }
                        self.showVerifyFeedback(btn, true);
                        return;
                    }
                    var err = String(data.error || 'Cloudflare zone check failed.');
                    if (T) {
                        T.error(err);
                    }
                    self.showVerifyFeedback(btn, false);
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.classList.remove('is-verifying');
                    if (T) {
                        T.dismiss(TOAST_CLOUDFLARE_VERIFY);
                        T.error('Cloudflare zone check failed.');
                    }
                    self.showVerifyFeedback(btn, false);
                });
        }

        bind() {
            var self = this;
            var state = this.state;
            if (state.integrationFormBound) {
                return;
            }
            state.integrationFormBound = true;

            document.querySelectorAll('[data-fc-integration-field]').forEach(function (input) {
                var onFieldChange = function () {
                    if (input.type === 'radio' && !input.checked) {
                        return;
                    }
                    var key = input.getAttribute('data-fc-integration-field');
                    state.integrations[key] = input.type === 'checkbox' ? input.checked : input.value;
                    self.setDirty(true);
                };
                input.addEventListener('input', onFieldChange);
                if (input.type === 'radio' || input.type === 'checkbox') {
                    input.addEventListener('change', onFieldChange);
                }
            });

            var prePlannerInput = document.getElementById('fc-integration-webhookPrePlannerEnabled');
            if (prePlannerInput) {
                prePlannerInput.addEventListener('change', function () {
                    self.syncPrePlannerDependents();
                });
            }
            this.syncPrePlannerDependents();

            document.querySelectorAll('[data-fc-integration-site]').forEach(function (input) {
                function syncSiteField() {
                    var siteKey = input.getAttribute('data-fc-integration-site');
                    var field = input.getAttribute('data-fc-integration-site-field');
                    var sites = Array.isArray(state.integrations.sites) ? state.integrations.sites : [];
                    var site = sites.find(function (row) {
                        return String(row.key || '') === siteKey;
                    });
                    if (site && field) {
                        site[field] = input.value;
                        self.setDirty(true);
                    }
                }
                input.addEventListener('input', syncSiteField);
                input.addEventListener('change', syncSiteField);
            });

            document.querySelectorAll('[data-fc-integration-site-logo-pick]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var siteKey = btn.getAttribute('data-fc-integration-site-logo-pick');
                    var input = document.querySelector(
                        '[data-fc-integration-site="' + siteKey + '"][data-fc-integration-site-field="logo"]'
                    );
                    if (!input || !global.FcAdminMediaPicker || typeof global.FcAdminMediaPicker.open !== 'function') {
                        return;
                    }
                    global.FcAdminMediaPicker.open({
                        appBase: self.getAppBase(),
                        csrf: state.csrf,
                        onSelect: function (path) {
                            input.value = path;
                            var sites = Array.isArray(state.integrations.sites) ? state.integrations.sites : [];
                            var site = sites.find(function (row) {
                                return String(row.key || '') === siteKey;
                            });
                            if (site) {
                                site.logo = path;
                            }
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            var preview = document.querySelector(
                                '[data-fc-integration-site-logo-preview="' + siteKey + '"]'
                            );
                            if (preview) {
                                var url = global.FC.Settings.tabs.fenceColors.previewUrl({ image: path }, self.getAppBase());
                                preview.innerHTML = url
                                    ? global.FC.Settings.buildViewableImgHtml(url, site ? site.label || siteKey : siteKey)
                                    : '';
                            }
                            self.setDirty(true);
                        }
                    });
                });
            });

            document.querySelectorAll('[data-fc-integration-reveal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-fc-integration-reveal');
                    var input = id ? document.getElementById(id) : null;
                    if (!input) {
                        return;
                    }
                    var showing = input.type === 'text';
                    input.type = showing ? 'password' : 'text';
                    btn.setAttribute('aria-label', (showing ? 'Show ' : 'Hide ') + 'API key');
                    var icon = btn.querySelector('i');
                    if (icon) {
                        icon.className = showing ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
                    }
                });
            });

            document.querySelectorAll('[data-fc-cloudflare-verify]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.verifyCloudflareZone(btn);
                });
            });

            var saveBtn = document.getElementById('fc-integration-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }
            var resetBtn = document.getElementById('fc-integration-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    state.integrations = self.clone(state.integrationsInitial);
                    self.paint();
                    self.setDirty(false);
                });
            }
        }

        save() {
            var self = this;
            var state = this.state;
            global.FC.util.toast('saving', 'Saving integration settings…', TOAST_INTEGRATIONS);
            fetch(API_INTEGRATIONS, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    integrations: state.integrations,
                    revision: state.integrationsRevision,
                    csrf: state.csrf
                })
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
                    state.integrations = self.clone(body.integrations || state.integrations);
                    state.integrationsInitial = self.clone(state.integrations);
                    state.integrationsRevision = body.revision || state.integrationsRevision;
                    self.paint();
                    self.setDirty(false);
                    self.flash.set(body.message || 'Integration settings saved.', 'success');
                    try {
                        var next = new URL(window.location.href);
                        window.location.assign(next.pathname + next.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    global.FC.util.toast('error', err.message || 'Could not save integration settings.', TOAST_INTEGRATIONS);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.integration = new IntegrationTabController();
})(window);
