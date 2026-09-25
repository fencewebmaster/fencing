/**
 * FC Admin — Settings → Integration tab.
 * The panel markup itself is server-rendered PHP (no render* function here,
 * matching the System tab); this only paints/binds/saves it.
 */
(function (global) {
    'use strict';

    var API_INTEGRATIONS = global.fcApiUrl('settings', 'action=integrations');
    var API_CLOUDFLARE_VERIFY = global.fcApiUrl('settings', 'action=cloudflare-verify');
    var API_CLOUDFLARE_PURGE = global.fcApiUrl('settings', 'action=cloudflare-purge');
    var TOAST_INTEGRATIONS = 'fc-integrations-save';
    var TOAST_CLOUDFLARE_VERIFY = 'fc-cloudflare-verify';
    var TOAST_CLOUDFLARE_PURGE = 'fc-cloudflare-purge';

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

            var self = this;
            sites.forEach(function (site) {
                self.paintSiteLogo(String(site.key || ''));
            });

            this.syncPrePlannerDependents();
            this.syncCloudflareButtons();
        }

        /** The Sites table thumbnail: the site's own logo, else its default, else an image icon. */
        paintSiteLogo(siteKey) {
            var preview = document.querySelector('[data-fc-integration-site-logo-preview="' + siteKey + '"]');
            var sites = Array.isArray(this.state.integrations.sites) ? this.state.integrations.sites : [];
            var site = sites.find(function (row) {
                return String(row.key || '') === siteKey;
            });
            if (!preview || !site) {
                return;
            }
            var path = String(site.logo || '').trim() || String(site.logoDefault || '').trim();
            var url = path ? global.FC.Settings.tabs.fenceColors.previewUrl({ image: path }, this.getAppBase()) : '';
            preview.innerHTML = url
                ? '<img src="' + global.FC.util.escapeHtml(url) + '" alt="" decoding="async">'
                : '<i class="fa-solid fa-image" aria-hidden="true"></i>';
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

        // Verify and Purge need the API token and this row's 32-character Zone ID, as typed; the tooltip says what is missing.
        syncCloudflareButtons() {
            var token = String(this.state.integrations.cloudflareApiToken || '').trim();
            document.querySelectorAll('[data-fc-cloudflare-verify], [data-fc-cloudflare-purge]').forEach(function (btn) {
                if (btn.classList.contains('is-verifying')) {
                    return;
                }
                if (btn.getAttribute('data-fc-cloudflare-title') === null) {
                    btn.setAttribute('data-fc-cloudflare-title', btn.title);
                }
                var zoneInput = document.getElementById(btn.getAttribute('data-fc-cloudflare-zone-for') || '');
                var zone = zoneInput ? String(zoneInput.value || '').trim() : '';
                var reason = '';
                if (!token) {
                    reason = 'Add the Cloudflare API token under API Keys first';
                } else if (!zone) {
                    reason = 'Enter a Cloudflare Zone ID for this site first';
                } else if (!/^[a-f0-9]{32}$/i.test(zone)) {
                    reason = 'A Cloudflare Zone ID is 32 characters (0-9, a-f)';
                }
                btn.disabled = reason !== '';
                btn.title = reason || btn.getAttribute('data-fc-cloudflare-title');
            });
        }

        showVerifyFeedback(btn, ok, idleIcon) {
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
                icon.className = idleIcon || 'fa-solid fa-plug';
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
                    btn.classList.remove('is-verifying');
                    self.syncCloudflareButtons();
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
                    btn.classList.remove('is-verifying');
                    self.syncCloudflareButtons();
                    if (T) {
                        T.dismiss(TOAST_CLOUDFLARE_VERIFY);
                        T.error('Cloudflare zone check failed.');
                    }
                    self.showVerifyFeedback(btn, false);
                });
        }

        // Same inputs as verifyCloudflareZone(): the Zone ID as typed in this row and the token as typed.
        purgeCloudflareZone(btn) {
            var self = this;
            var state = this.state;
            if (!btn || btn.disabled || btn.classList.contains('is-verifying')) {
                return;
            }

            var zoneInputId = btn.getAttribute('data-fc-cloudflare-zone-for') || '';
            var zoneInput = zoneInputId ? document.getElementById(zoneInputId) : null;
            var zoneId = zoneInput ? String(zoneInput.value || '').trim() : '';
            var siteKey = btn.getAttribute('data-fc-cloudflare-site') || '';
            var siteLabel = btn.getAttribute('data-fc-cloudflare-site-label') || siteKey || 'this site';
            var token = String(state.integrations.cloudflareApiToken || '').trim();
            var T = global.FcAdminToast;
            var idleIcon = 'fa-solid fa-broom';

            if (!zoneId) {
                if (T) {
                    T.error('Enter a Cloudflare Zone ID first.');
                }
                self.showVerifyFeedback(btn, false, idleIcon);
                return;
            }

            // One click purges, like the top-bar Cloudflare option; purgeZone() still refuses another site's zone.
            var icon = btn.querySelector('i');
            btn.disabled = true;
            btn.classList.add('is-verifying');
            btn.classList.remove('is-verified', 'is-verify-failed');
            if (icon) {
                icon.className = 'fa-solid fa-spinner fa-spin';
            }
            if (T) {
                T.loading('Purging the Cloudflare cache for ' + siteLabel + '…', TOAST_CLOUDFLARE_PURGE);
            }

            function finish(ok, message) {
                btn.classList.remove('is-verifying');
                self.syncCloudflareButtons();
                if (T) {
                    T.dismiss(TOAST_CLOUDFLARE_PURGE);
                    if (ok) {
                        T.success(message);
                    } else {
                        T.error(message);
                    }
                }
                self.showVerifyFeedback(btn, ok, idleIcon);
            }

            fetch(API_CLOUDFLARE_PURGE, {
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
                    });
                })
                .then(function (data) {
                    if (data && data.ok) {
                        finish(true, data.message || 'Cloudflare cache purged for ' + siteLabel + '.');
                        return;
                    }
                    finish(false, String((data && data.error) || 'Cloudflare purge failed.'));
                })
                .catch(function () {
                    finish(false, 'Cloudflare purge failed.');
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

            var tokenInput = document.getElementById('fc-integration-cloudflareApiToken');
            if (tokenInput) {
                tokenInput.addEventListener('input', function () {
                    self.syncCloudflareButtons();
                });
            }

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
                if (input.getAttribute('data-fc-integration-site-field') === 'cloudflareZoneId') {
                    input.addEventListener('input', function () {
                        self.syncCloudflareButtons();
                    });
                }
                if (input.getAttribute('data-fc-integration-site-field') === 'logo') {
                    input.addEventListener('change', function () {
                        self.paintSiteLogo(input.getAttribute('data-fc-integration-site'));
                    });
                }
            });

            // The thumbnail opens its row's logo drawer; the row scrolls back so the drawer is in view.
            document.querySelectorAll('[data-fc-integration-site-logo-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var panel = document.getElementById(btn.getAttribute('aria-controls') || '');
                    if (!panel) {
                        return;
                    }
                    var open = panel.hidden;
                    panel.hidden = !open;
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    var row = btn.closest('[data-fc-integration-site-row]');
                    if (row) {
                        row.classList.toggle('is-open', open);
                    }
                    if (open) {
                        var table = btn.closest('[data-fc-integration-sites]');
                        if (table) {
                            table.scrollLeft = 0;
                        }
                        var field = panel.querySelector('input');
                        if (field) {
                            field.focus();
                        }
                    }
                });
            });

            var sitesTable = document.querySelector('[data-fc-integration-sites]');
            if (sitesTable) {
                sitesTable.addEventListener('scroll', function () {
                    sitesTable.classList.toggle('is-scrolled', sitesTable.scrollLeft > 0);
                }, { passive: true });
            }

            document.querySelectorAll('[data-fc-integration-site-logo-default]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var siteKey = btn.getAttribute('data-fc-integration-site-logo-default');
                    var input = document.querySelector(
                        '[data-fc-integration-site="' + siteKey + '"][data-fc-integration-site-field="logo"]'
                    );
                    if (!input || input.value === '') {
                        return;
                    }
                    input.value = '';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    self.paintSiteLogo(siteKey);
                });
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
                            self.paintSiteLogo(siteKey);
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

            document.querySelectorAll('[data-fc-cloudflare-purge]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.purgeCloudflareZone(btn);
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
            if (!this.startSaving('fc-integration-save')) {
                return;
            }
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
                    self.stopSaving('fc-integration-save');
                    global.FC.util.toast('error', err.message || 'Could not save integration settings.', TOAST_INTEGRATIONS);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.integration = new IntegrationTabController();
})(window);
