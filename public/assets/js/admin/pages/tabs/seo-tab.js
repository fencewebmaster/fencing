/**
 * FC Admin — Settings → SEO tab.
 * The panel markup is server-rendered PHP (like the System and Integration tabs); this paints,
 * binds, previews and saves it.
 */
(function (global) {
    'use strict';

    var API_SEO = global.fcApiUrl('settings', 'action=seo');
    var TOAST_SEO = 'fc-seo-save';
    var PLACEHOLDER_RE = /\{(app_name|site_name|tagline)\}/g;
    // The separators SeoSettings::renderTemplate() trims off either end.
    var STRANDED_RE = /^[\s|:·•,–—-]+|[\s|:·•,–—-]+$/g;
    var CHECK_ICONS = {
        good: 'fa-circle-check',
        warn: 'fa-triangle-exclamation',
        bad: 'fa-circle-xmark',
        info: 'fa-circle-minus'
    };
    // SiteRegistryService::searchBlockReason() codes: those copies are never listed, whatever the switch says.
    var BLOCKED_ON = {
        localhost: 'Hidden on localhost',
        staging: 'Hidden on staging',
        demo: 'Hidden on a test path'
    };
    // Descriptions shorter than this rarely say enough for Google to use them as the snippet.
    var DESCRIPTION_MIN = 70;

    class SeoTabController extends global.FC.Settings.TabController {
        clone(value) {
            try {
                return JSON.parse(JSON.stringify(value || {}));
            } catch (e) {
                return Object.assign({}, value || {});
            }
        }

        // Dirty means "differs from what is saved", so typing a change back out clears it.
        syncDirty() {
            this.state.seoDirty = JSON.stringify(this.state.seo) !== JSON.stringify(this.state.seoInitial);
            this.updateHeaderActions();
        }

        // Mirror of SeoSettings::renderTemplate() — edit in pairs.
        renderTemplate(template, vars) {
            return String(template || '')
                .replace(PLACEHOLDER_RE, function (match, key) {
                    return vars[key] || '';
                })
                .replace(/\s+/g, ' ')
                .replace(STRANDED_RE, '')
                .trim();
        }

        // Mirror of SeoSettings::siteName(): in the site name, {site_name} is the automatic name.
        vars() {
            var ctx = this.state.seoContext || {};
            var base = { app_name: ctx.appName || '', site_name: ctx.autoSiteName || '', tagline: ctx.tagline || '' };
            var typed = String(this.state.seo.siteName || '').trim();
            return {
                app_name: base.app_name,
                site_name: (typed && this.renderTemplate(typed, base)) || base.site_name,
                tagline: base.tagline
            };
        }

        // A blank template saves as the default (SeoSettings::normalize()), so preview it that way.
        rendered() {
            var seo = this.state.seo;
            var defaults = this.state.seoDefaults || {};
            var vars = this.vars();
            return {
                title: this.renderTemplate(String(seo.titleTemplate || '').trim() || defaults.titleTemplate, vars),
                description: this.renderTemplate(
                    String(seo.descriptionTemplate || '').trim() || defaults.descriptionTemplate,
                    vars
                )
            };
        }

        pageUrl() {
            return String(this.state.seo.canonicalUrl || '').trim() || (this.state.seoContext || {}).plannerUrl || '';
        }

        // Google's breadcrumb form: https://example.com › fc › planner
        displayUrl(url) {
            try {
                var parsed = new URL(url);
                return [parsed.protocol + '//' + parsed.host]
                    .concat(parsed.pathname.split('/').filter(Boolean))
                    .join(' › ');
            } catch (e) {
                return url;
            }
        }

        assetUrl(path) {
            var value = String(path || '').trim();
            if (!value) {
                return '';
            }
            if (/^https?:\/\//i.test(value) || value.indexOf('//') === 0) {
                return value;
            }
            return this.getAppBase().replace(/\/+$/, '') + '/' + value.replace(/^\/+/, '');
        }

        readField(el) {
            if (el.type === 'checkbox') {
                return el.checked;
            }
            if (el.hasAttribute('data-fc-seo-list')) {
                return el.value
                    .split(/\r?\n/)
                    .map(function (line) {
                        return line.trim();
                    })
                    .filter(Boolean);
            }
            if (el.type === 'number') {
                // Blank goes up as '' so the server restores the default; 0 would switch snippets off.
                var n = parseInt(el.value, 10);
                return Number.isFinite(n) ? n : '';
            }
            return el.value;
        }

        paint() {
            var seo = this.state.seo;
            document.querySelectorAll('[data-fc-seo-field]').forEach(function (el) {
                var value = seo[el.getAttribute('data-fc-seo-field')];
                if (el.type === 'checkbox') {
                    el.checked = !!value;
                    return;
                }
                if (el.hasAttribute('data-fc-seo-list')) {
                    el.value = Array.isArray(value) ? value.join('\n') : String(value || '');
                    return;
                }
                el.value = value == null ? '' : String(value);
            });
            this.paintVisibility();
            this.paintGroups();
            this.paintPreview();
            this.paintSummary();
        }

        paintVisibility() {
            var on = !!this.state.seo.searchEngineVisible;
            document.querySelectorAll('[data-fc-seo-visibility]').forEach(function (btn) {
                var value = btn.getAttribute('data-fc-seo-visibility') === '1';
                btn.setAttribute('aria-pressed', value === on ? 'true' : 'false');
            });
            // The switch between the two labels; its position and colour come from aria-checked.
            document.querySelectorAll('[data-fc-seo-visibility-toggle]').forEach(function (toggle) {
                toggle.setAttribute('aria-checked', on ? 'true' : 'false');
            });
            var note = document.querySelector('[data-fc-seo-hidden-note]');
            if (note) {
                note.hidden = on;
            }
        }

        // Fields under a switched-off group stay editable, just dimmed, so they can be filled in first.
        paintGroups() {
            var seo = this.state.seo;
            document.querySelectorAll('[data-fc-seo-group]').forEach(function (group) {
                group.classList.toggle('is-off', !seo[group.getAttribute('data-fc-seo-group')]);
            });
        }

        setPreviewText(key, text) {
            document.querySelectorAll('[data-fc-seo-preview="' + key + '"]').forEach(function (el) {
                el.textContent = text;
            });
        }

        paintCount(key, length) {
            var el = document.querySelector('[data-fc-seo-count="' + key + '"]');
            if (!el) {
                return;
            }
            var limit = parseInt(el.getAttribute('data-fc-seo-limit'), 10) || 0;
            el.textContent = length + ' / ' + limit;
            el.classList.toggle('is-over', limit > 0 && length > limit);
            el.title = limit > 0 && length > limit ? 'Search engines usually cut this off after about ' + limit + ' characters.' : '';
        }

        paintPreview() {
            var seo = this.state.seo;
            var ctx = this.state.seoContext || {};
            var text = this.rendered();
            var url = this.pageUrl();
            var hidden = !seo.searchEngineVisible || !seo.indexPlanner;

            this.setPreviewText('siteName', this.vars().site_name);
            this.setPreviewText('displayUrl', this.displayUrl(url));
            this.setPreviewText('title', text.title);
            this.setPreviewText('description', text.description);
            this.paintCount('title', text.title.length);
            this.paintCount('description', text.description.length);

            var serp = document.querySelector('[data-fc-seo-serp]');
            if (serp) {
                serp.classList.toggle('is-hidden', hidden);
            }
            var hiddenNote = document.querySelector('[data-fc-seo-preview-hidden]');
            if (hiddenNote) {
                hiddenNote.hidden = !hidden;
            }

            var host = '';
            try {
                host = new URL(url).host;
            } catch (e) {
                host = '';
            }
            this.setPreviewText('domain', host);
            this.setPreviewText('socialTitle', String(seo.socialTitle || '').trim() || text.title);
            this.setPreviewText('socialDescription', String(seo.socialDescription || '').trim() || text.description);

            var card = document.querySelector('[data-fc-seo-card]');
            if (card) {
                card.setAttribute('data-card', seo.twitterCard === 'summary' ? 'summary' : 'summary_large_image');
            }
            // Blank site logo means this site's own; the share image falls back to whichever is in use.
            var logoUrl = seo.siteLogo ? this.assetUrl(seo.siteLogo) : ctx.logoUrl || '';
            var logoPreview = document.querySelector('[data-fc-seo-logo-preview]');
            if (logoPreview) {
                logoPreview.innerHTML = logoUrl
                    ? global.FC.Settings.buildViewableImgHtml(logoUrl, 'Site logo')
                    : global.FC.Settings.buildImagePlaceholderHtml();
            }
            var serpLogo = document.querySelector('[data-fc-seo-preview-logo]');
            if (serpLogo) {
                var serpLogoUrl = logoUrl || ctx.faviconUrl || '';
                serpLogo.innerHTML = serpLogoUrl
                    ? '<img src="' + global.FC.util.escapeHtml(serpLogoUrl) + '" alt="" decoding="async">'
                    : '<i class="fa-solid fa-globe" aria-hidden="true"></i>';
            }

            var image = document.querySelector('[data-fc-seo-preview-image]');
            if (image) {
                var imageUrl = seo.socialImage ? this.assetUrl(seo.socialImage) : logoUrl;
                image.innerHTML = imageUrl
                    ? '<img src="' + global.FC.util.escapeHtml(imageUrl) + '" alt="" decoding="async">'
                    : '<i class="fa-solid fa-image" aria-hidden="true"></i>';
            }
        }

        limit(key) {
            var el = document.querySelector('[data-fc-seo-count="' + key + '"]');
            return el ? parseInt(el.getAttribute('data-fc-seo-limit'), 10) || 0 : 0;
        }

        // The header's health check, worked out from the live form so it follows unsaved edits.
        checks() {
            var seo = this.state.seo;
            var blocked = BLOCKED_ON[(this.state.seoContext || {}).blockReason];
            var text = this.rendered();
            var titleLength = text.title.length;
            var descriptionLength = text.description.length;
            var listing = ['good', 'Planner can be listed'];

            if (blocked) {
                listing = ['info', blocked];
            } else if (!seo.searchEngineVisible) {
                listing = ['bad', 'Visibility is disabled'];
            } else if (!seo.indexPlanner) {
                listing = ['bad', 'Planner indexing is off'];
            }

            var description = ['good', descriptionLength + ' characters'];
            if (descriptionLength > this.limit('description')) {
                description = ['warn', descriptionLength + ' characters, may be cut off'];
            } else if (descriptionLength < DESCRIPTION_MIN) {
                description = ['warn', descriptionLength + ' characters, a bit short'];
            }

            var shareImage = ['warn', 'Using the site logo'];
            if (!seo.socialEnabled) {
                shareImage = ['info', 'Social tags are off'];
            } else if (String(seo.socialImage || '').trim()) {
                shareImage = ['good', 'Custom image'];
            }

            return {
                listing: listing,
                title: titleLength > this.limit('title')
                    ? ['warn', titleLength + ' characters, may be cut off']
                    : ['good', titleLength + ' characters'],
                description: description,
                canonical: seo.canonicalEnabled
                    ? ['good', String(seo.canonicalUrl || '').trim() ? 'Custom URL' : "Planner's own address"]
                    : ['warn', 'Off'],
                shareImage: shareImage,
                schema: seo.schemaEnabled ? ['good', 'On'] : ['info', 'Off']
            };
        }

        // The card-header chips: the same checks, gathered per card.
        sectionChips(results) {
            var seo = this.state.seo;
            var defaults = this.state.seoDefaults || {};

            var toCheck = ['title', 'description'].filter(function (key) {
                return results[key][0] !== 'good';
            });
            var appearance = ['good', 'Looks good'];
            if (toCheck.length === 2) {
                appearance = ['warn', 'Check the title and description'];
            } else if (toCheck.length === 1) {
                appearance = ['warn', 'Check the ' + toCheck[0]];
            }

            var indexing = results.listing;
            if (indexing[0] === 'good' && results.canonical[0] !== 'good') {
                indexing = ['warn', 'Canonical URL is off'];
            }

            var verifyFields = document.querySelectorAll('[data-fc-seo-field^="verify"]');
            var added = Array.prototype.filter.call(verifyFields, function (el) {
                return String(seo[el.getAttribute('data-fc-seo-field')] || '').trim() !== '';
            }).length;

            // A blank snippet length saves as the default, so it isn't a customisation.
            var customised = ['language', 'maxImagePreview', 'maxSnippet'].some(function (key) {
                var value = seo[key] === '' ? defaults[key] : seo[key];
                return String(value) !== String(defaults[key]);
            });

            return {
                appearance: appearance,
                indexing: indexing,
                verification: added ? ['good', added + ' of ' + verifyFields.length + ' added'] : ['info', 'None added'],
                advanced: ['info', customised ? 'Customised' : 'Defaults']
            };
        }

        paintSummary() {
            var results = this.checks();
            var chips = this.sectionChips(results);
            var scored = 0;
            var good = 0;
            var worst = 'good';

            document.querySelectorAll('[data-fc-seo-chip]').forEach(function (chip) {
                var result = chips[chip.getAttribute('data-fc-seo-chip')];
                if (result) {
                    chip.setAttribute('data-state', result[0]);
                    chip.textContent = result[1];
                }
            });

            document.querySelectorAll('[data-fc-seo-check]').forEach(function (tile) {
                var result = results[tile.getAttribute('data-fc-seo-check')];
                if (!result) {
                    return;
                }
                tile.setAttribute('data-state', result[0]);
                var icon = tile.querySelector('[data-fc-seo-check-icon]');
                if (icon) {
                    icon.className = 'fa-solid ' + CHECK_ICONS[result[0]];
                }
                var value = tile.querySelector('[data-fc-seo-check-value]');
                if (value) {
                    value.textContent = result[1];
                }
                if (result[0] === 'info') {
                    return;
                }
                scored++;
                if (result[0] === 'good') {
                    good++;
                } else if (result[0] === 'bad' || worst === 'good') {
                    worst = result[0];
                }
            });

            var score = document.querySelector('[data-fc-seo-score]');
            if (score) {
                score.setAttribute('data-state', worst);
                var scoreText = score.querySelector('[data-fc-seo-score-text]');
                if (scoreText) {
                    scoreText.textContent = good === scored
                        ? 'All ' + scored + ' checks look good'
                        : good + ' of ' + scored + ' checks look good';
                }
                var fill = score.querySelector('[data-fc-seo-score-fill]');
                if (fill) {
                    fill.style.width = (scored ? Math.round((good / scored) * 100) : 0) + '%';
                }
            }

            // The testers follow the canonical URL, so they check the address search engines are given.
            var pageUrl = encodeURIComponent(this.pageUrl());
            var richResults = document.querySelector('a[data-fc-seo-test="rich-results"]');
            if (richResults) {
                richResults.href = 'https://search.google.com/test/rich-results?url=' + pageUrl;
            }
            var shareDebugger = document.querySelector('a[data-fc-seo-test="share-debugger"]');
            if (shareDebugger) {
                shareDebugger.href = 'https://developers.facebook.com/tools/debug/?q=' + pageUrl;
            }
        }

        // Bring a tile's field into view and flash it, so the eye lands on it.
        jumpTo(id) {
            var field = document.getElementById(id);
            if (!field) {
                return;
            }
            var target = field.type === 'checkbox' ? field.closest('label') || field : field;
            var reduceMotion = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;
            target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
            try {
                field.focus({ preventScroll: true });
            } catch (e) {
                field.focus();
            }
            target.classList.remove('fc-seo-flash');
            void target.offsetWidth;
            target.classList.add('fc-seo-flash');
            setTimeout(function () {
                target.classList.remove('fc-seo-flash');
            }, 1600);
        }

        setVisibility(on) {
            if (!!this.state.seo.searchEngineVisible === on) {
                return;
            }
            this.state.seo.searchEngineVisible = on;
            this.paintVisibility();
            this.paintPreview();
            this.paintSummary();
            this.syncDirty();
        }

        insertToken(input, token) {
            var start = typeof input.selectionStart === 'number' ? input.selectionStart : input.value.length;
            var end = typeof input.selectionEnd === 'number' ? input.selectionEnd : start;
            input.value = input.value.slice(0, start) + token + input.value.slice(end);
            input.focus();
            try {
                input.setSelectionRange(start + token.length, start + token.length);
            } catch (e) {
                /* not a text control */
            }
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }

        bind() {
            var self = this;
            var state = this.state;
            if (state.seoFormBound) {
                return;
            }
            state.seoFormBound = true;

            document.querySelectorAll('[data-fc-seo-field]').forEach(function (el) {
                var onFieldChange = function () {
                    state.seo[el.getAttribute('data-fc-seo-field')] = self.readField(el);
                    self.paintGroups();
                    self.paintPreview();
                    self.paintSummary();
                    self.syncDirty();
                };
                var instant = el.type === 'checkbox' || el.tagName === 'SELECT';
                el.addEventListener(instant ? 'change' : 'input', onFieldChange);
            });

            document.querySelectorAll('[data-fc-seo-visibility]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.setVisibility(btn.getAttribute('data-fc-seo-visibility') === '1');
                });
            });
            document.querySelectorAll('[data-fc-seo-visibility-toggle]').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    self.setVisibility(!state.seo.searchEngineVisible);
                });
            });

            document.querySelectorAll('[data-fc-seo-jump]').forEach(function (tile) {
                tile.addEventListener('click', function () {
                    self.jumpTo(tile.getAttribute('data-fc-seo-jump'));
                });
            });

            document.querySelectorAll('[data-fc-seo-token]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(btn.getAttribute('data-fc-seo-token-for'));
                    if (input) {
                        self.insertToken(input, btn.getAttribute('data-fc-seo-token'));
                    }
                });
            });

            document.querySelectorAll('[data-fc-seo-pick]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(btn.getAttribute('data-fc-seo-pick'));
                    if (!input || !global.FcAdminMediaPicker || typeof global.FcAdminMediaPicker.open !== 'function') {
                        return;
                    }
                    global.FcAdminMediaPicker.open({
                        appBase: self.getAppBase(),
                        csrf: state.csrf,
                        onSelect: function (path) {
                            input.value = path;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                });
            });
            document.querySelectorAll('[data-fc-seo-clear]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(btn.getAttribute('data-fc-seo-clear'));
                    if (input) {
                        input.value = '';
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            });

            var saveBtn = document.getElementById('fc-seo-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }
            var resetBtn = document.getElementById('fc-seo-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    state.seo = self.clone(state.seoInitial);
                    self.paint();
                    self.syncDirty();
                });
            }
        }

        save() {
            var self = this;
            var state = this.state;
            if (!this.startSaving('fc-seo-save')) {
                return;
            }
            global.FC.util.toast('saving', 'Saving SEO settings…', TOAST_SEO);
            fetch(API_SEO, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ seo: state.seo, csrf: state.csrf })
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
                    state.seo = self.clone(body.seo || state.seo);
                    state.seoInitial = self.clone(state.seo);
                    self.paint();
                    self.syncDirty();
                    self.flash.set(body.message || 'SEO settings saved.', 'success');
                    try {
                        var next = new URL(window.location.href);
                        window.location.assign(next.pathname + next.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    self.stopSaving('fc-seo-save');
                    global.FC.util.toast('error', err.message || 'Could not save SEO settings.', TOAST_SEO);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.seo = new SeoTabController();
})(window);
