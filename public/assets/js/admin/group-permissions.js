/**
 * FC Admin — Group Permissions editor.
 */
(function () {
    'use strict';

    var TOAST_ID = 'fc-gp-save';
    var FLASH_KEY = 'fc-gp-save-flash';
    var state = {
        roles: [],
        selectedRole: '',
        isAdministratorRole: false,
        isSuperAdminRole: false,
        isLocked: false,
        canEdit: true,
        lockNotice: '',
        tree: [],
        permissions: {},
        csrf: '',
        apiUrl: 'api.php?module=groupPermissions',
        dirty: false,
        bound: false,
    };

    function toast(kind, message) {
        if (!window.FcAdminToast) {
            return;
        }
        var text = typeof message === 'string' ? message : String(message == null ? '' : message);
        if (kind === 'loading') {
            window.FcAdminToast.loading(text, TOAST_ID);
            return;
        }
        window.FcAdminToast.dismiss(TOAST_ID);
        if (kind === 'success') {
            window.FcAdminToast.success(text, { id: TOAST_ID });
        } else if (kind === 'error') {
            window.FcAdminToast.error(text, { id: TOAST_ID });
        }
    }

    function readBootstrap() {
        var el = document.getElementById('fc-gp-bootstrap');
        if (!el) {
            return null;
        }
        try {
            return JSON.parse(el.textContent || '{}');
        } catch (e) {
            return null;
        }
    }

    var flash = new window.FC.util.FlashMessage({
        storageKey: FLASH_KEY,
        noticeSelector: '[data-fc-gp-notice]'
    });

    function setFlash(message, type) {
        flash.set(message, type);
    }

    function consumeFlash() {
        return flash.consume();
    }

    function showHeaderNotice(root, flashData) {
        flash.renderInto(root, flashData);
    }

    function getPath(obj, path) {
        var parts = String(path || '').split('.');
        var cur = obj;
        for (var i = 0; i < parts.length; i++) {
            if (!cur || typeof cur !== 'object' || !(parts[i] in cur)) {
                return false;
            }
            cur = cur[parts[i]];
        }
        return cur === true;
    }

    function setPath(obj, path, value) {
        var parts = String(path || '').split('.');
        var cur = obj;
        for (var i = 0; i < parts.length - 1; i++) {
            if (!cur[parts[i]] || typeof cur[parts[i]] !== 'object') {
                cur[parts[i]] = {};
            }
            cur = cur[parts[i]];
        }
        cur[parts[parts.length - 1]] = !!value;
    }

    function leafPaths(nodes, prefix) {
        var keys = [];
        (nodes || []).forEach(function (node) {
            var path = prefix ? prefix + '.' + node.key : node.key;
            if (node.children && node.children.length) {
                keys = keys.concat(leafPaths(node.children, path));
            } else {
                keys.push(path);
            }
        });
        return keys;
    }

    function setDirty(isDirty) {
        state.dirty = !!isDirty;
        var el = document.getElementById('fc-gp-dirty');
        if (el) {
            el.classList.toggle('hidden', !state.dirty);
        }
        var saveBtn = document.getElementById('fc-gp-save');
        if (saveBtn && !state.isLocked) {
            saveBtn.disabled = !state.dirty;
        }
    }

    function renderCheckControl(path, label, allChecked, locked) {
        return (
            '<span class="fc-gp-tree__check">' +
            '<input type="checkbox" class="fc-gp-tree__check-input" data-fc-gp-check="' +
            escapeAttr(path) +
            '"' +
            (allChecked ? ' checked' : '') +
            (locked ? ' disabled' : '') +
            ' aria-label="' +
            escapeAttr(label) +
            '">' +
            '<span class="fc-gp-tree__check-ui" aria-hidden="true">' +
            '<i class="fa-solid fa-check fc-gp-tree__check-icon fc-gp-tree__check-icon--on" aria-hidden="true"></i>' +
            '<i class="fa-solid fa-minus fc-gp-tree__check-icon fc-gp-tree__check-icon--partial" aria-hidden="true"></i>' +
            '</span></span>'
        );
    }

    function nodeLeafStats(node, path) {
        var hasChildren = !!(node.children && node.children.length);
        var leaves = hasChildren ? leafPaths(node.children, path) : [path];
        var checkedCount = 0;
        leaves.forEach(function (leaf) {
            if (getPath(state.permissions, leaf)) {
                checkedCount += 1;
            }
        });
        return {
            leaves: leaves,
            checkedCount: checkedCount,
            allChecked: checkedCount === leaves.length && leaves.length > 0,
            someChecked: checkedCount > 0 && checkedCount < leaves.length,
        };
    }

    function renderTreeNode(node, prefix) {
        var path = prefix ? prefix + '.' + node.key : node.key;
        var stats = nodeLeafStats(node, path);
        var locked = state.isLocked;
        var label = String(node.label || node.key || '');
        var hasChildren = !!(node.children && node.children.length);

        var html = '<div class="fc-gp-tree__node" data-fc-gp-path="' + escapeAttr(path) + '">';
        html += '<div class="fc-gp-tree__row">';
        html += '<label class="fc-gp-tree__label">';
        html += renderCheckControl(path, label, stats.allChecked, locked);
        html += '<span class="fc-gp-tree__text">' + escapeHtml(label) + '</span>';
        html += '</label></div>';

        if (hasChildren) {
            html += '<div class="fc-gp-tree__children">';
            node.children.forEach(function (child) {
                html += renderTreeNode(child, path).html;
            });
            html += '</div>';
        }
        html += '</div>';

        return { html: html, path: path, someChecked: stats.someChecked };
    }

    function renderSection(node) {
        var path = String(node.key || '');
        var stats = nodeLeafStats(node, path);
        var locked = state.isLocked;
        var label = String(node.label || node.key || '');
        var hasChildren = !!(node.children && node.children.length);
        var countLabel = stats.checkedCount + ' / ' + stats.leaves.length;

        var html = '<section class="fc-gp-section" data-fc-gp-path="' + escapeAttr(path) + '">';
        html += '<header class="fc-gp-section__head">';
        html += '<label class="fc-gp-tree__label fc-gp-section__title-label">';
        html += renderCheckControl(path, label, stats.allChecked, locked);
        html += '<span class="fc-gp-section__title">' + escapeHtml(label) + '</span>';
        html += '</label>';
        html += '<span class="fc-gp-section__count">' + escapeHtml(countLabel) + '</span>';
        html += '</header>';

        if (hasChildren) {
            html += '<div class="fc-gp-section__body">';
            node.children.forEach(function (child) {
                html += renderTreeNode(child, path).html;
            });
            html += '</div>';
        }
        html += '</section>';

        return html;
    }

    var escapeHtml = window.FC.util.escapeHtml;

    function escapeAttr(text) {
        return escapeHtml(text).replace(/'/g, '&#39;');
    }

    function permissionsHaveGrant(permissions) {
        var leaves = leafPaths(state.tree || [], '');
        for (var i = 0; i < leaves.length; i++) {
            if (getPath(permissions, leaves[i])) {
                return true;
            }
        }
        return false;
    }

    function syncSelectedRoleDot() {
        var role = state.selectedRole;
        if (!role) {
            return;
        }
        var btn = document.querySelector('[data-fc-gp-role="' + cssEscape(role) + '"]');
        if (!btn) {
            return;
        }
        var hasPerms = state.isSuperAdminRole || permissionsHaveGrant(state.permissions);
        btn.setAttribute('data-fc-gp-has-perms', hasPerms ? '1' : '0');
        var dot = btn.querySelector('[data-fc-gp-role-dot]');
        if (dot) {
            dot.classList.toggle('is-on', hasPerms);
        }
        (state.roles || []).forEach(function (item) {
            if (item && item.key === role) {
                item.has_permissions = hasPerms;
            }
        });
    }

    function renderTree() {
        var root = document.getElementById('fc-gp-tree');
        if (!root) {
            return;
        }
        var parts = [];
        var indeterminate = [];
        (state.tree || []).forEach(function (node) {
            parts.push(renderSection(node));
            collectIndeterminate(node, '', indeterminate);
        });
        root.innerHTML = parts.join('');
        root.setAttribute('data-locked', state.isLocked ? '1' : '0');

        indeterminate.forEach(function (path) {
            var input = root.querySelector('[data-fc-gp-check="' + cssEscape(path) + '"]');
            if (input) {
                input.indeterminate = true;
                input.checked = false;
            }
        });
        syncSelectedRoleDot();
    }

    function collectIndeterminate(node, prefix, out) {
        var path = prefix ? prefix + '.' + node.key : node.key;
        if (!(node.children && node.children.length)) {
            return;
        }
        var leaves = leafPaths(node.children, path);
        var checkedCount = 0;
        leaves.forEach(function (leaf) {
            if (getPath(state.permissions, leaf)) {
                checkedCount += 1;
            }
        });
        if (checkedCount > 0 && checkedCount < leaves.length) {
            out.push(path);
        }
        node.children.forEach(function (child) {
            collectIndeterminate(child, path, out);
        });
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/"/g, '\\"');
    }

    function applyCheck(path, checked) {
        var node = findNodeByPath(state.tree, path);
        if (!node) {
            setPath(state.permissions, path, checked);
            return;
        }
        var leaves = node.node.children && node.node.children.length
            ? leafPaths(node.node.children, path)
            : [path];
        leaves.forEach(function (leaf) {
            setPath(state.permissions, leaf, checked);
        });
    }

    function findNodeByPath(nodes, target, prefix) {
        prefix = prefix || '';
        for (var i = 0; i < (nodes || []).length; i++) {
            var node = nodes[i];
            var path = prefix ? prefix + '.' + node.key : node.key;
            if (path === target) {
                return { node: node, path: path };
            }
            if (node.children && node.children.length) {
                var found = findNodeByPath(node.children, target, path);
                if (found) {
                    return found;
                }
            }
        }
        return null;
    }

    function syncExportButtonLabel() {
        var label = document.querySelector('[data-fc-gp-export-json] span');
        if (!label) {
            return;
        }
        var role = (state.roles || []).filter(function (item) {
            return item && item.key === state.selectedRole;
        })[0];
        label.textContent = 'Export ' + (role && role.label ? role.label : 'Permission');
    }

    function syncRoleListActive() {
        document.querySelectorAll('[data-fc-gp-role]').forEach(function (btn) {
            var key = btn.getAttribute('data-fc-gp-role') || '';
            var active = key === state.selectedRole;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function syncAdminNotice() {
        var notice = document.getElementById('fc-gp-admin-notice');
        if (notice) {
            if (state.lockNotice) {
                notice.textContent = state.lockNotice;
            }
            notice.classList.toggle('hidden', !state.isLocked || !state.lockNotice);
        }
        var saveBtn = document.getElementById('fc-gp-save');
        if (saveBtn) {
            saveBtn.disabled = state.isLocked || !state.dirty;
        }
    }

    function loadRole(role, options) {
        options = options || {};
        if (state.dirty && !options.force) {
            if (!window.confirm('You have unsaved changes. Switch groups anyway?')) {
                return;
            }
        }

        var url = state.apiUrl + (state.apiUrl.indexOf('?') >= 0 ? '&' : '?') + 'action=get&role=' + encodeURIComponent(role);
        fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.ok) {
                    throw new Error((data && data.error) || 'Could not load permissions.');
                }
                state.selectedRole = data.role || role;
                state.isSuperAdminRole = !!data.isSuperAdminRole;
                state.isAdministratorRole = !!data.isAdministratorRole;
                state.isLocked = data.isLocked != null ? !!data.isLocked : !!data.isSuperAdminRole;
                state.canEdit = data.canEdit != null ? !!data.canEdit : !state.isLocked;
                state.lockNotice = data.lockNotice || '';
                state.permissions = data.permissions || {};
                state.csrf = data.csrf || state.csrf;
                if (Array.isArray(data.tree) && data.tree.length) {
                    state.tree = data.tree;
                }
                setDirty(false);
                syncRoleListActive();
                syncAdminNotice();
                syncExportButtonLabel();
                renderTree();
                try {
                    var next = new URL(window.location.href);
                    next.searchParams.set('role', state.selectedRole);
                    window.history.replaceState({}, '', next.pathname + next.search);
                } catch (e) { /* ignore */ }
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not load permissions.');
            });
    }

    function savePermissions() {
        if (state.isLocked) {
            toast('error', state.lockNotice || 'This role cannot be edited.');
            return;
        }
        toast('loading', 'Saving permissions…');
        fetch(state.apiUrl + (state.apiUrl.indexOf('?') >= 0 ? '&' : '?') + 'action=save', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                role: state.selectedRole,
                permissions: state.permissions,
                csrf: state.csrf,
            }),
        })
            .then(function (res) { return res.json().then(function (body) { return { res: res, body: body }; }); })
            .then(function (pack) {
                var body = pack.body || {};
                if (!pack.res.ok || !body.ok) {
                    throw new Error(body.error || 'Could not save permissions.');
                }
                setDirty(false);
                setFlash(body.message || 'Permissions saved.', 'success');
                try {
                    var next = new URL(window.location.href);
                    if (state.selectedRole) {
                        next.searchParams.set('role', state.selectedRole);
                    }
                    window.location.assign(next.pathname + next.search);
                } catch (e) {
                    window.location.reload();
                }
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save permissions.');
            });
    }

    function bindUi(root) {
        if (state.bound) {
            return;
        }
        state.bound = true;

        root.addEventListener('click', function (e) {
            var roleBtn = e.target.closest('[data-fc-gp-role]');
            if (roleBtn && root.contains(roleBtn)) {
                e.preventDefault();
                var role = roleBtn.getAttribute('data-fc-gp-role') || '';
                if (role && role !== state.selectedRole) {
                    loadRole(role);
                }
                return;
            }
        });

        root.addEventListener('change', function (e) {
            var input = e.target.closest('[data-fc-gp-check]');
            if (!input || !root.contains(input) || state.isLocked) {
                return;
            }
            var path = input.getAttribute('data-fc-gp-check') || '';
            applyCheck(path, !!input.checked);
            setDirty(true);
            renderTree();
        });

        var search = document.getElementById('fc-gp-role-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = String(search.value || '').toLowerCase().trim();
                document.querySelectorAll('#fc-gp-role-list li').forEach(function (li) {
                    var text = (li.textContent || '').toLowerCase();
                    li.hidden = q !== '' && text.indexOf(q) === -1;
                });
            });
        }

        var saveBtn = document.getElementById('fc-gp-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                savePermissions();
            });
        }

        bindImportExport();

        window.addEventListener('beforeunload', function (e) {
            if (!state.dirty) {
                return;
            }
            e.preventDefault();
            e.returnValue = '';
        });
    }

    function bindImportExport() {
        var dropdown = document.querySelector('[data-fc-gp-download-dropdown]');
        if (!dropdown || dropdown.dataset.fcBound === '1') {
            return;
        }
        dropdown.dataset.fcBound = '1';

        var toggle = dropdown.querySelector('[data-fc-gp-download-toggle]');
        var panel = dropdown.querySelector('.fc-products-download-dropdown__panel');
        var exportBtn = dropdown.querySelector('[data-fc-gp-export-json]');
        var exportAllBtn = dropdown.querySelector('[data-fc-gp-export-all-json]');
        var importBtn = dropdown.querySelector('[data-fc-gp-import-json]');
        var importInput = dropdown.querySelector('[data-fc-gp-import-input]');

        function closeMenu() {
            if (!panel || !toggle) {
                return;
            }
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            dropdown.classList.remove('is-open');
            panel.style.left = '';
            panel.style.top = '';
            panel.style.position = '';
            panel.style.right = '';
            panel.style.zIndex = '';
        }

        function positionMenu() {
            if (!toggle || !panel || panel.hidden) {
                return;
            }
            var rect = toggle.getBoundingClientRect();
            var gap = 6;
            panel.style.position = 'fixed';
            panel.style.zIndex = '80';
            panel.style.left = Math.round(rect.left) + 'px';
            panel.style.top = Math.round(rect.bottom + gap) + 'px';
            panel.style.right = 'auto';

            var panelRect = panel.getBoundingClientRect();
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

            if (panelRect.right > viewportWidth - 8) {
                panel.style.left = Math.max(8, viewportWidth - panelRect.width - 8) + 'px';
            }
            if (panelRect.bottom > viewportHeight - 8) {
                var aboveTop = rect.top - gap - panelRect.height;
                if (aboveTop >= 8) {
                    panel.style.top = aboveTop + 'px';
                }
            }
        }

        function openMenu() {
            if (!panel || !toggle) {
                return;
            }
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            dropdown.classList.add('is-open');
            positionMenu();
        }

        function exportUrl(all) {
            var base = state.apiUrl || 'api.php?module=groupPermissions';
            var sep = base.indexOf('?') >= 0 ? '&' : '?';
            if (all) {
                return base + sep + 'action=export-all';
            }
            return base + sep + 'action=export&role=' + encodeURIComponent(state.selectedRole || '');
        }

        function downloadJson(all) {
            closeMenu();
            var link = document.createElement('a');
            link.href = exportUrl(!!all);
            link.download = all
                ? 'group-permissions.json'
                : 'group-permissions-' + (state.selectedRole || 'role') + '.json';
            link.rel = 'noopener';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }

        function importJsonFile(file) {
            if (!file) {
                return;
            }
            var name = String(file.name || '').toLowerCase();
            if (!name.endsWith('.json')) {
                toast('error', 'Only .json files can be imported.');
                return;
            }
            if (!state.csrf) {
                toast('error', 'Missing security token. Refresh and try again.');
                return;
            }
            if (state.dirty && !window.confirm('You have unsaved changes. Import and discard them?')) {
                if (importInput) {
                    importInput.value = '';
                }
                return;
            }

            closeMenu();
            toast('loading', 'Importing permissions…');

            var formData = new FormData();
            formData.append('csrf', state.csrf);
            formData.append('file', file);

            var url = (state.apiUrl || 'api.php?module=groupPermissions');
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'action=import';

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: formData,
            })
                .then(function (response) {
                    return response
                        .json()
                        .catch(function () {
                            return { ok: false, error: 'Invalid server response.' };
                        })
                        .then(function (body) {
                            if (!response.ok || !body.ok) {
                                throw new Error((body && body.error) || 'Could not import permissions.');
                            }
                            return body;
                        });
                })
                .then(function (body) {
                    if (body.csrf) {
                        state.csrf = body.csrf;
                    }
                    setFlash(body.message || 'Permissions imported.', 'success');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 500);
                })
                .catch(function (err) {
                    toast('error', (err && err.message) || 'Could not import permissions.');
                })
                .then(function () {
                    if (importInput) {
                        importInput.value = '';
                    }
                });
        }

        if (toggle && panel) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (panel.hidden) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });
            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', function (event) {
                event.preventDefault();
                downloadJson(false);
            });
        }
        if (exportAllBtn) {
            exportAllBtn.addEventListener('click', function (event) {
                event.preventDefault();
                downloadJson(true);
            });
        }
        if (importBtn && importInput) {
            importBtn.addEventListener('click', function (event) {
                event.preventDefault();
                closeMenu();
                importInput.click();
            });
            importInput.addEventListener('change', function () {
                var file = importInput.files && importInput.files[0] ? importInput.files[0] : null;
                importJsonFile(file);
            });
        }

        document.addEventListener('click', function () {
            closeMenu();
        });
        window.addEventListener('resize', function () {
            if (dropdown.classList.contains('is-open')) {
                positionMenu();
            }
        });
        window.addEventListener('scroll', function () {
            if (dropdown.classList.contains('is-open')) {
                positionMenu();
            }
        }, true);
    }

    function init() {
        var root = document.querySelector('[data-fc-group-permissions]');
        if (!root) {
            return;
        }
        var boot = readBootstrap() || {};
        state.roles = boot.roles || [];
        state.selectedRole = boot.selectedRole || '';
        state.isSuperAdminRole = !!boot.isSuperAdminRole;
        state.isAdministratorRole = !!boot.isAdministratorRole;
        state.isLocked = boot.isLocked != null ? !!boot.isLocked : !!boot.isSuperAdminRole;
        state.canEdit = boot.canEdit != null ? !!boot.canEdit : !state.isLocked;
        state.lockNotice = boot.lockNotice || '';
        state.tree = boot.tree || [];
        state.permissions = boot.permissions || {};
        state.csrf = boot.csrf || '';
        state.apiUrl = boot.apiUrl || state.apiUrl;
        setDirty(false);
        syncAdminNotice();
        syncExportButtonLabel();
        renderTree();
        bindUi(root);
        showHeaderNotice(root, consumeFlash());
    }

    class GroupPermissionsPage extends window.FC.PageController {
        hydrate(container) {
            init(container);
        }
    }
    window.FC.PageRegistry.register('users/group-permissions', new GroupPermissionsPage());

    document.addEventListener('DOMContentLoaded', init);
})();
