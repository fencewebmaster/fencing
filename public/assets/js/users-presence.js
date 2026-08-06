/**
 * FC Admin — Users list presence poller (online dots + activity + device).
 */
(function (global) {
    'use strict';

    var POLL_MS = 30000;

    function apiUrl(root) {
        var url = root.getAttribute('data-fc-users-presence-api') || 'api.php?module=users&action=presence';
        var ids = [];
        root.querySelectorAll('tr[data-user-id]').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-user-id') || '0', 10);
            if (id > 0) {
                ids.push(String(id));
            }
        });
        if (ids.length) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'ids=' + encodeURIComponent(ids.join(','));
        }
        return url;
    }

    function setDot(dot, online, activityLabel) {
        if (!dot) {
            return;
        }
        var label = online ? 'Online' : 'Offline';
        var title = online && activityLabel ? label + ' · Last activity ' + activityLabel : label;
        dot.classList.toggle('is-online', !!online);
        dot.classList.toggle('is-offline', !online);
        dot.setAttribute('title', title);
        dot.setAttribute('aria-label', label);
    }

    function setIcon(el, className, title, muted) {
        if (!el) {
            return;
        }
        el.className = className + (muted ? ' is-muted' : '');
        el.setAttribute('title', title || '');
        el.setAttribute('aria-hidden', 'true');
    }

    function applyDevice(row, deviceInfo) {
        if (!deviceInfo) {
            return;
        }
        var device = String(deviceInfo.device || 'Unknown');
        var browser = String(deviceInfo.browser || 'Unknown');
        var deviceIcon = String(deviceInfo.device_icon || 'fa-solid fa-circle-question');
        var browserIcon = String(deviceInfo.browser_icon || 'fa-solid fa-globe');
        var deviceMuted = device.toLowerCase() === 'unknown';
        var browserMuted = ['unknown', 'other'].indexOf(browser.toLowerCase()) !== -1;

        var link = row.querySelector('[data-fc-users-device-link]');
        if (link) {
            link.setAttribute('aria-label', device + ', ' + browser);
        }
        setIcon(row.querySelector('[data-fc-users-device-icon]'), deviceIcon, device, deviceMuted);
        setIcon(row.querySelector('[data-fc-users-browser-icon]'), browserIcon, browser, browserMuted);
    }

    function applyPresence(root, payload) {
        if (!payload || !payload.ok) {
            return;
        }
        var online = payload.online || {};
        var activityFmt = payload.last_activity_formatted || {};
        var lastLoginFmt = payload.last_login_formatted || {};
        var devicesFmt = payload.devices_formatted || {};

        root.querySelectorAll('tr[data-user-id]').forEach(function (row) {
            var id = String(row.getAttribute('data-user-id') || '');
            if (!id) {
                return;
            }
            var isOnline = !!online[id];
            setDot(
                row.querySelector('[data-fc-users-online-dot]'),
                isOnline,
                activityFmt[id] || ''
            );

            var loginValue = row.querySelector('[data-fc-users-last-login-value]');
            if (loginValue && Object.prototype.hasOwnProperty.call(lastLoginFmt, id)) {
                loginValue.textContent = lastLoginFmt[id] || '—';
            }

            var activityValue = row.querySelector('[data-fc-users-last-activity-value]');
            if (activityValue && Object.prototype.hasOwnProperty.call(activityFmt, id)) {
                activityValue.textContent = activityFmt[id] || '—';
            }

            if (Object.prototype.hasOwnProperty.call(devicesFmt, id)) {
                applyDevice(row, devicesFmt[id]);
            }
        });
    }

    function poll(root) {
        var url = apiUrl(root);
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                applyPresence(root, data);
            })
            .catch(function () {
                /* ignore transient poll errors */
            });
    }

    function initList(root) {
        if (!root || root.getAttribute('data-fc-users-presence-bound') === '1') {
            return;
        }
        root.setAttribute('data-fc-users-presence-bound', '1');

        var timer = global.setInterval(function () {
            if (!document.body.contains(root)) {
                global.clearInterval(timer);
                return;
            }
            if (document.hidden) {
                return;
            }
            poll(root);
        }, POLL_MS);

        // First refresh shortly after load so dots stay fresh after idle tabs.
        global.setTimeout(function () {
            if (document.body.contains(root)) {
                poll(root);
            }
        }, 5000);
    }

    function boot() {
        document.querySelectorAll('[data-fc-users-list]').forEach(initList);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window);
