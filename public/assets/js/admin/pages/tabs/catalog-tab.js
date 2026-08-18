/**
 * FC Admin — Settings → Catalog tab.
 * Extracted from settings.js. The only settings tab with a genuine lazy
 * fetch: category/attribute option lists load on demand via
 * ensureOptions(), gated by the tab becoming active (see the shell's
 * switchTab()/hydrateFromServer() call sites) rather than on every hydrate.
 */
(function (global) {
    'use strict';

    var API_CATALOG = global.fcApiUrl('settings', 'action=catalog');
    var TOAST_CATALOG = 'fc-catalog-save';

    class CatalogTabController extends global.FC.Settings.TabController {
        clone(catalog) {
            try {
                return JSON.parse(JSON.stringify(catalog || {}));
            } catch (e) {
                return Object.assign({}, catalog || {});
            }
        }

        setDirty(isDirty) {
            this.state.catalogDirty = !!isDirty;
            this.updateHeaderActions();
        }

        filterCategoryNodes(nodes, query) {
            var self = this;
            query = String(query || '').trim().toLowerCase();
            if (!query) {
                return nodes || [];
            }

            return (nodes || []).reduce(function (matches, node) {
                if (!node || typeof node !== 'object') {
                    return matches;
                }
                var haystack = (String(node.name || '') + ' ' + String(node.slug || '')).toLowerCase();
                var ownMatch = haystack.indexOf(query) !== -1;
                var children = ownMatch
                    ? node.children || []
                    : self.filterCategoryNodes(node.children || [], query);
                if (ownMatch || children.length) {
                    var copy = Object.assign({}, node);
                    copy.children = children;
                    matches.push(copy);
                }
                return matches;
            }, []);
        }

        renderCategoryNodes(nodes, depth) {
            var self = this;
            var escapeHtml = global.FC.util.escapeHtml;
            depth = depth || 0;
            var html = '';
            (nodes || []).forEach(function (node) {
                if (!node || typeof node !== 'object') {
                    return;
                }
                var id = parseInt(node.id, 10) || 0;
                if (!id) {
                    return;
                }
                var selected = (self.state.catalog.categoryIds || []).indexOf(id) !== -1;
                var count =
                    node.count != null && node.count !== '' ? String(node.count) : '';
                html +=
                    '<label class="fc-catalog-check" style="--fc-catalog-depth:' +
                    depth +
                    '">' +
                    '<input type="checkbox" data-fc-catalog-category="' +
                    id +
                    '"' +
                    (selected ? ' checked' : '') +
                    '>' +
                    '<span class="fc-catalog-check__label" title="' +
                    escapeHtml(node.name || '') +
                    '">' +
                    escapeHtml(node.name || '') +
                    '</span>' +
                    (count !== ''
                        ? '<span class="fc-catalog-check__count">' + escapeHtml(count) + '</span>'
                        : '') +
                    '</label>';
                if (node.children && node.children.length) {
                    html += self.renderCategoryNodes(node.children, depth + 1);
                }
            });
            return html;
        }

        checkMetaHtml(selected, total, emptyMeansAll) {
            var selectedLabel =
                selected > 0
                    ? selected + ' selected'
                    : emptyMeansAll
                      ? 'All visible'
                      : 'None selected';
            return (
                '<div class="fc-catalog-check-meta">' +
                '<span>' +
                total +
                ' total</span>' +
                '<span class="fc-catalog-check-meta__selected">' +
                selectedLabel +
                '</span>' +
                '</div>'
            );
        }

        collectCategoryIds(nodes, out) {
            var self = this;
            out = out || [];
            (nodes || []).forEach(function (node) {
                if (!node) {
                    return;
                }
                var id = parseInt(node.id, 10) || 0;
                if (id) {
                    out.push(id);
                }
                if (node.children && node.children.length) {
                    self.collectCategoryIds(node.children, out);
                }
            });
            return out;
        }

        paintLists() {
            var escapeHtml = global.FC.util.escapeHtml;
            var state = this.state;
            var self = this;
            var catsEl = document.getElementById('fc-catalog-categories');
            var attrsEl = document.getElementById('fc-catalog-attributes');
            var errEl = document.getElementById('fc-catalog-options-error');

            if (errEl) {
                if (state.catalogOptionsError) {
                    errEl.textContent = state.catalogOptionsError;
                    errEl.classList.remove('hidden');
                } else {
                    errEl.textContent = '';
                    errEl.classList.add('hidden');
                }
            }

            if (catsEl) {
                if (!state.catalogCategories.length) {
                    catsEl.innerHTML =
                        '<p class="fc-catalog-check-empty">' +
                        (state.catalogOptionsError
                            ? 'Categories unavailable until WooCommerce loads.'
                            : 'No product categories found.') +
                        '</p>';
                } else {
                    var catTotal = self.collectCategoryIds(state.catalogCategories, []).length;
                    var catSelected = (state.catalog.categoryIds || []).length;
                    var filteredCategories = self.filterCategoryNodes(
                        state.catalogCategories,
                        state.catalogCategorySearch
                    );
                    catsEl.innerHTML =
                        self.checkMetaHtml(catSelected, catTotal, true) +
                        (filteredCategories.length
                            ? self.renderCategoryNodes(filteredCategories, 0)
                            : '<p class="fc-catalog-check-empty">No categories match your search.</p>');
                }
            }

            if (attrsEl) {
                if (!state.catalogAttributes.length) {
                    attrsEl.innerHTML =
                        '<p class="fc-catalog-check-empty">' +
                        (state.catalogOptionsError
                            ? 'Attributes unavailable until WooCommerce loads.'
                            : 'No product attributes found.') +
                        '</p>';
                } else {
                    var attrTotal = state.catalogAttributes.length;
                    var attrSelected = (state.catalog.attributeSlugs || []).length;
                    var attributeQuery = String(state.catalogAttributeSearch || '')
                        .trim()
                        .toLowerCase();
                    var filteredAttributes = state.catalogAttributes.filter(function (attr) {
                        if (!attributeQuery) {
                            return true;
                        }
                        return (
                            (String(attr.label || '') + ' ' + String(attr.slug || ''))
                                .toLowerCase()
                                .indexOf(attributeQuery) !== -1
                        );
                    });
                    attrsEl.innerHTML =
                        self.checkMetaHtml(attrSelected, attrTotal, true) +
                        (filteredAttributes.length
                            ? filteredAttributes
                            .map(function (attr) {
                                var slug = String(attr.slug || '');
                                var selected = (state.catalog.attributeSlugs || []).indexOf(slug) !== -1;
                                var label = attr.label || slug;
                                return (
                                    '<label class="fc-catalog-check">' +
                                    '<input type="checkbox" data-fc-catalog-attribute="' +
                                    escapeHtml(slug) +
                                    '"' +
                                    (selected ? ' checked' : '') +
                                    '>' +
                                    '<span class="fc-catalog-check__label" title="' +
                                    escapeHtml(label) +
                                    '">' +
                                    escapeHtml(label) +
                                    '</span>' +
                                    '</label>'
                                );
                            })
                            .join('')
                            : '<p class="fc-catalog-check-empty">No attributes match your search.</p>');
                }
            }
        }

        paint() {
            var state = this.state;
            document.querySelectorAll('[data-fc-catalog-field]').forEach(function (el) {
                var key = el.getAttribute('data-fc-catalog-field');
                if (!key) {
                    return;
                }
                var value = state.catalog[key];
                if (el.type === 'checkbox') {
                    el.checked = !!value;
                } else if (el.tagName === 'SELECT' || el.tagName === 'INPUT') {
                    el.value = value == null ? '' : String(value);
                }
            });
            this.paintLists();
        }

        /** Fetches category/attribute option lists on demand; safe to call repeatedly. */
        ensureOptions() {
            var state = this.state;
            var self = this;
            if (state.catalogOptionsLoaded && (state.catalogCategories.length || state.catalogOptionsError)) {
                return Promise.resolve();
            }

            return fetch(API_CATALOG, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        if (!res.ok || !body.ok) {
                            throw new Error((body && body.error) || 'Catalog request failed');
                        }
                        return body;
                    });
                })
                .then(function (body) {
                    state.catalog = self.clone(body.catalog || state.catalog);
                    state.catalogDefaults = self.clone(body.defaults || state.catalogDefaults);
                    state.catalogOrderbyChoices = body.orderbyChoices || state.catalogOrderbyChoices;
                    state.catalogCategories = body.categories || [];
                    state.catalogAttributes = body.attributes || [];
                    state.catalogOptionsError = body.optionsError || '';
                    state.catalogOptionsLoaded = true;
                })
                .catch(function (err) {
                    state.catalogOptionsError = err.message || 'Could not load catalog options.';
                    state.catalogOptionsLoaded = true;
                });
        }

        bind() {
            var self = this;
            var state = this.state;
            if (state.catalogFormBound) {
                return;
            }
            state.catalogFormBound = true;

            var categorySearch = document.querySelector('[data-fc-catalog-categories-search]');
            if (categorySearch) {
                categorySearch.value = state.catalogCategorySearch;
                categorySearch.addEventListener('input', function () {
                    state.catalogCategorySearch = categorySearch.value;
                    self.paintLists();
                });
            }

            var attributeSearch = document.querySelector('[data-fc-catalog-attributes-search]');
            if (attributeSearch) {
                attributeSearch.value = state.catalogAttributeSearch;
                attributeSearch.addEventListener('input', function () {
                    state.catalogAttributeSearch = attributeSearch.value;
                    self.paintLists();
                });
            }

            document.querySelectorAll('[data-fc-catalog-field]').forEach(function (el) {
                var handler = function () {
                    var key = el.getAttribute('data-fc-catalog-field');
                    if (!key) {
                        return;
                    }
                    if (el.type === 'checkbox') {
                        state.catalog[key] = !!el.checked;
                    } else if (el.type === 'number' || key === 'resultsPerPage') {
                        state.catalog[key] = el.value === '' ? '' : Number(el.value);
                    } else {
                        state.catalog[key] = el.value;
                    }
                    self.setDirty(true);
                };
                el.addEventListener('input', handler);
                el.addEventListener('change', handler);
            });

            var catsEl = document.getElementById('fc-catalog-categories');
            if (catsEl) {
                catsEl.addEventListener('change', function (e) {
                    var t = e.target;
                    if (!t || !t.matches('[data-fc-catalog-category]')) {
                        return;
                    }
                    var id = parseInt(t.getAttribute('data-fc-catalog-category'), 10) || 0;
                    var selected = Array.isArray(state.catalog.categoryIds)
                        ? state.catalog.categoryIds.slice()
                        : [];
                    var idx = selected.indexOf(id);
                    if (t.checked && idx === -1) {
                        selected.push(id);
                    } else if (!t.checked && idx !== -1) {
                        selected.splice(idx, 1);
                    }
                    state.catalog.categoryIds = selected;
                    self.setDirty(true);
                });
            }

            var attrsEl = document.getElementById('fc-catalog-attributes');
            if (attrsEl) {
                attrsEl.addEventListener('change', function (e) {
                    var t = e.target;
                    if (!t || !t.matches('[data-fc-catalog-attribute]')) {
                        return;
                    }
                    var slug = String(t.getAttribute('data-fc-catalog-attribute') || '');
                    var selected = Array.isArray(state.catalog.attributeSlugs)
                        ? state.catalog.attributeSlugs.slice()
                        : [];
                    var idx = selected.indexOf(slug);
                    if (t.checked && idx === -1) {
                        selected.push(slug);
                    } else if (!t.checked && idx !== -1) {
                        selected.splice(idx, 1);
                    }
                    state.catalog.attributeSlugs = selected;
                    self.setDirty(true);
                });
            }

            var catsAll = document.querySelector('[data-fc-catalog-cats-all]');
            if (catsAll) {
                catsAll.addEventListener('click', function () {
                    state.catalog.categoryIds = self.collectCategoryIds(state.catalogCategories, []);
                    self.paintLists();
                    self.setDirty(true);
                });
            }
            var catsNone = document.querySelector('[data-fc-catalog-cats-none]');
            if (catsNone) {
                catsNone.addEventListener('click', function () {
                    state.catalog.categoryIds = [];
                    self.paintLists();
                    self.setDirty(true);
                });
            }
            var attrsAll = document.querySelector('[data-fc-catalog-attrs-all]');
            if (attrsAll) {
                attrsAll.addEventListener('click', function () {
                    state.catalog.attributeSlugs = (state.catalogAttributes || []).map(function (a) {
                        return String(a.slug || '');
                    }).filter(Boolean);
                    self.paintLists();
                    self.setDirty(true);
                });
            }
            var attrsNone = document.querySelector('[data-fc-catalog-attrs-none]');
            if (attrsNone) {
                attrsNone.addEventListener('click', function () {
                    state.catalog.attributeSlugs = [];
                    self.paintLists();
                    self.setDirty(true);
                });
            }

            var saveBtn = document.getElementById('fc-catalog-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    self.save();
                });
            }
            var resetBtn = document.getElementById('fc-catalog-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    state.catalog = self.clone(state.catalogDefaults);
                    self.paint();
                    self.setDirty(true);
                });
            }
        }

        save() {
            var self = this;
            var state = this.state;
            global.FC.util.toast('saving', 'Saving catalog settings…', TOAST_CATALOG);
            fetch(API_CATALOG, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ catalog: state.catalog, csrf: state.csrf })
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
                    state.catalog = self.clone(body.catalog || state.catalog);
                    state.catalogDefaults = self.clone(body.defaults || state.catalogDefaults);
                    state.catalogOrderbyChoices = body.orderbyChoices || state.catalogOrderbyChoices;
                    state.catalogCategories = body.categories || state.catalogCategories;
                    state.catalogAttributes = body.attributes || state.catalogAttributes;
                    state.catalogOptionsError = body.optionsError || '';
                    state.catalogOptionsLoaded = true;
                    self.paint();
                    self.setDirty(false);
                    self.flash.set(body.message || 'Catalog settings saved — refresh Product Lookup to see changes.', 'success');
                    try {
                        var next = new URL(window.location.href);
                        window.location.assign(next.pathname + next.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    global.FC.util.toast('error', err.message || 'Could not save catalog settings.', TOAST_CATALOG);
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.catalog = new CatalogTabController();
})(window);
