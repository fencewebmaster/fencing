/**
 * FC Admin — topbar cache purge dropdown.
 */
(function (global) {
    'use strict';

    var TOAST_ID = 'fc-cache-purge';
    var TARGET_LABELS = {
        all: 'all caches',
        lookup: 'Lookup cache',
        products: 'Products cache',
        cloudflare: 'Cloudflare cache',
    };

    function toast(kind, message) {
        var T = global.FcAdminToast;
        if (!T) {
            return;
        }
        if (kind === 'saving' && typeof T.loading === 'function') {
            T.loading(message, TOAST_ID);
            return;
        }
        if (kind === 'success' && typeof T.success === 'function') {
            T.success(message, { id: TOAST_ID, duration: 4500 });
            return;
        }
        if (kind === 'error' && typeof T.error === 'function') {
            T.error(message, { id: TOAST_ID, duration: 4500 });
        }
    }

    function closeDropdown(root) {
        var toggle = root.querySelector('.fc-entries-date-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-date-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
        clearActiveOption(root);
    }

    function clearActiveOption(root) {
        root.querySelectorAll('.fc-admin-cache-dropdown__option.is-active').forEach(function (btn) {
            btn.classList.remove('is-active');
        });
    }

    function closeOtherMenus(root) {
        document.querySelectorAll('[data-fc-cache-purge-dropdown].is-open').forEach(function (other) {
            if (other !== root) {
                closeDropdown(other);
            }
        });

        document.querySelectorAll('[data-fc-entries-date-dropdown].is-open').forEach(function (dateRoot) {
            var panel = dateRoot.querySelector('.fc-entries-date-dropdown__panel');
            var toggle = dateRoot.querySelector('.fc-entries-date-dropdown__toggle');
            if (panel) {
                panel.hidden = true;
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            dateRoot.classList.remove('is-open');
        });

        document.querySelectorAll(
            '[data-fc-entries-multi-dropdown].is-open, [data-fc-entries-fence-dropdown].is-open'
        ).forEach(function (fence) {
            var fencePanel = fence.querySelector('.fc-entries-fence-dropdown__panel');
            var fenceToggle = fence.querySelector('.fc-entries-fence-dropdown__toggle');
            if (fencePanel) {
                fencePanel.hidden = true;
            }
            if (fenceToggle) {
                fenceToggle.setAttribute('aria-expanded', 'false');
            }
            fence.classList.remove('is-open');
        });
    }

    function setOptionEmpty(btn, files) {
        var empty = !(files > 0);
        btn.classList.toggle('is-empty', empty);
        btn.disabled = empty;
        btn.setAttribute('aria-disabled', empty ? 'true' : 'false');
        if (empty) {
            btn.classList.remove('is-active');
        }
    }

    function applyStats(root, stats) {
        if (!stats || typeof stats !== 'object') {
            return;
        }

        root.querySelectorAll('[data-fc-cache-purge]').forEach(function (btn) {
            var target = btn.getAttribute('data-fc-cache-purge') || '';
            var meta = btn.querySelector('[data-fc-cache-meta]');
            var entry = null;

            if (target === 'all') {
                entry = stats.all || null;
            } else if (stats.buckets && stats.buckets[target]) {
                entry = stats.buckets[target];
            }

            if (entry && entry.label && meta) {
                meta.textContent = String(entry.label);
            }

            if (target === 'cloudflare') {
                // Always allow click; API returns a clear error when credentials are missing.
                setOptionEmpty(btn, 1);
                return;
            }

            setOptionEmpty(btn, entry && typeof entry.files === 'number' ? entry.files : 0);
        });
    }

    function apiBase(root) {
        return root.getAttribute('data-fc-cache-api') || 'api.php?module=cache';
    }

    function actionUrl(root, action) {
        var base = apiBase(root);
        return base + (base.indexOf('?') >= 0 ? '&' : '?') + 'action=' + encodeURIComponent(action);
    }

    function refreshStats(root) {
        return fetch(actionUrl(root, 'stats'), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { okHttp: res.ok, data: data || {} };
                }).catch(function () {
                    return { okHttp: res.ok, data: {} };
                });
            })
            .then(function (result) {
                if (!result.okHttp || !result.data.ok || !result.data.stats) {
                    return;
                }
                applyStats(root, result.data.stats);
            })
            .catch(function () {
                /* ignore stats refresh errors */
            });
    }

    function openDropdown(root) {
        var toggle = root.querySelector('.fc-entries-date-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-date-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }

        closeOtherMenus(root);
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
        refreshStats(root);
    }

    function confirmPurge(target, metaText) {
        if (target !== 'all') {
            return true;
        }

        var detail = metaText ? '\n\n' + metaText : '';
        return global.confirm(
            'Purge all caches?' + detail + '\n\nThis clears Lookup and Products cache files. They will rebuild on next use.'
        );
    }

    function purge(root, target) {
        var csrf = root.getAttribute('data-fc-cache-csrf') || '';
        var label = TARGET_LABELS[target] || target;

        toast('saving', 'Purging ' + label + '…');

        return fetch(actionUrl(root, 'purge'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                target: target,
                csrf: csrf,
            }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { okHttp: res.ok, data: data || {} };
                }).catch(function () {
                    return { okHttp: res.ok, data: {} };
                });
            })
            .then(function (result) {
                var data = result.data;
                if (!result.okHttp || !data.ok) {
                    throw new Error((data && data.error) || 'Could not purge cache.');
                }

                if (data.stats) {
                    applyStats(root, data.stats);
                } else {
                    refreshStats(root);
                }

                var count = typeof data.deleted === 'number' ? data.deleted : 0;
                var msg;
                if (target === 'cloudflare') {
                    msg = data.message || 'Purged Cloudflare CDN cache.';
                } else if (count === 1) {
                    msg = 'Purged 1 cache file (' + label + ').';
                } else {
                    msg = 'Purged ' + count + ' cache files (' + label + ').';
                }
                toast('success', msg);
            })
            .catch(function (err) {
                toast('error', (err && err.message) || 'Could not purge cache.');
            })
            .then(function () {
                clearActiveOption(root);
            });
    }

    function init(root) {
        if (!root || root.getAttribute('data-fc-cache-initialized') === '1') {
            return;
        }
        root.setAttribute('data-fc-cache-initialized', '1');

        var toggle = root.querySelector('.fc-entries-date-dropdown__toggle');
        var panel = root.querySelector('.fc-entries-date-dropdown__panel');
        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (panel.hidden) {
                openDropdown(root);
            } else {
                closeDropdown(root);
            }
        });

        panel.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        root.querySelectorAll('[data-fc-cache-purge]').forEach(function (btn) {
            btn.addEventListener('mouseenter', function () {
                if (btn.disabled) {
                    return;
                }
                clearActiveOption(root);
                btn.classList.add('is-active');
            });
            btn.addEventListener('mouseleave', function () {
                btn.classList.remove('is-active');
            });
            btn.addEventListener('focus', function () {
                if (btn.disabled) {
                    return;
                }
                clearActiveOption(root);
                btn.classList.add('is-active');
            });
            btn.addEventListener('blur', function () {
                btn.classList.remove('is-active');
            });
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (btn.disabled) {
                    return;
                }

                var target = btn.getAttribute('data-fc-cache-purge') || 'all';
                var meta = btn.querySelector('[data-fc-cache-meta]');
                var metaText = meta ? String(meta.textContent || '').trim() : '';

                clearActiveOption(root);
                btn.classList.add('is-active');

                if (!confirmPurge(target, metaText)) {
                    btn.classList.remove('is-active');
                    return;
                }

                closeDropdown(root);
                purge(root, target);
            });
        });

        // Apply initial empty states from server-rendered meta when possible.
        root.querySelectorAll('[data-fc-cache-purge]').forEach(function (btn) {
            var target = btn.getAttribute('data-fc-cache-purge') || '';
            if (target === 'cloudflare') {
                setOptionEmpty(btn, 1);
                return;
            }
            var meta = btn.querySelector('[data-fc-cache-meta]');
            var text = meta ? String(meta.textContent || '') : '';
            var match = text.match(/^([\d,]+)\s+items?/i);
            var files = match ? parseInt(match[1].replace(/,/g, ''), 10) : 0;
            if (!isNaN(files)) {
                setOptionEmpty(btn, files);
            }
        });
    }

    function initAll() {
        document.querySelectorAll('[data-fc-cache-purge-dropdown]').forEach(init);
    }

    document.addEventListener('click', function () {
        document.querySelectorAll('[data-fc-cache-purge-dropdown].is-open').forEach(closeDropdown);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-fc-cache-purge-dropdown].is-open').forEach(closeDropdown);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    global.FcCachePurge = {
        init: init,
        initAll: initAll,
        refreshStats: refreshStats,
    };
})(window);
