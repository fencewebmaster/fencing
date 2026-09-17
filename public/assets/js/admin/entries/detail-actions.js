/**
 * FC Admin — Planner entry detail gear menu (Send Pre-Planner Submission).
 */
(function (global) {
    'use strict';

    var TOAST_ID = 'fc-entries-pre-planner';

    function toast(kind, message) {
        global.FC.util.toast(kind, message, {
            id: TOAST_ID,
            duration: kind === 'error' ? 6000 : 4500,
        });
    }

    function apiUrl(menu, action) {
        var base = menu.getAttribute('data-fc-entries-api') || 'api.php?module=entries';
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        return base + sep + 'action=' + encodeURIComponent(action);
    }

    function confirmSend(item) {
        var sentAt = item.getAttribute('data-fc-webhook-sent-at') || '';
        var mode = item.getAttribute('data-fc-webhook-mode') || 'Live';
        var emphasis = {
            planner: item.getAttribute('data-fc-planner-id') || '',
            target: {
                text: mode + ' webhook',
                className: mode === 'Test'
                    ? 'fc-entries-pre-planner-mode fc-entries-pre-planner-mode--test'
                    : 'fc-entries-pre-planner-mode fc-entries-pre-planner-mode--live',
            },
            sentAt: sentAt,
        };
        var message =
            'Send planner {planner} to the {target} now?' + (sentAt ? ' It was last sent on {sentAt}.' : '');

        if (global.FcAdminModal && typeof global.FcAdminModal.confirm === 'function') {
            return global.FcAdminModal.confirm({
                title: 'Send Pre-Planner submission',
                message: message,
                emphasis: emphasis,
                icon: false,
                confirmLabel: 'Send now',
            });
        }

        return Promise.resolve(global.confirm(message.replace(/\{(\w+)\}/g, function (token, key) {
            return typeof emphasis[key] === 'object' ? emphasis[key].text : emphasis[key];
        })));
    }

    // Copy-all is rebuilt from the rows the same way the Presenter joins them.
    function showSentAt(sentAt) {
        var panel = document.querySelector('[data-fc-entries-detail-panel="planner"]');
        var row = panel && panel.querySelector('[data-fc-entries-detail-row="webhook_sent_at"]');
        if (!row) {
            return;
        }
        var value = row.querySelector('.fc-entries-modal__value');
        var copyBtn = row.querySelector('[data-fc-copy-text]');
        if (value) {
            value.textContent = sentAt;
        }
        if (copyBtn) {
            copyBtn.setAttribute('data-fc-copy-text', sentAt);
        }

        var allBtn = panel.querySelector('.fc-entries-detail-copy-btn--all');
        if (!allBtn) {
            return;
        }
        var lines = [];
        panel.querySelectorAll('[data-fc-entries-detail-row]').forEach(function (detailRow) {
            var label = detailRow.querySelector('.fc-entries-modal__label');
            var copy = detailRow.querySelector('[data-fc-copy-text]');
            lines.push(
                (label ? label.textContent : '') + ': ' + (copy ? copy.getAttribute('data-fc-copy-text') : '')
            );
        });
        allBtn.setAttribute('data-fc-copy-text', lines.join('\n'));
    }

    function sendPrePlanner(menu, item) {
        if (item.disabled) {
            return;
        }

        confirmSend(item).then(function (ok) {
            if (!ok) {
                return;
            }
            item.disabled = true;
            toast('saving', 'Sending Pre-Planner submission…');

            fetch(apiUrl(menu, 'send-pre-planner'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: parseInt(menu.getAttribute('data-fc-entry-id') || '0', 10),
                    csrf: menu.getAttribute('data-fc-entries-csrf') || '',
                }),
            })
                .then(function (res) {
                    // A PHP fatal answers with HTML; surface the generic message, not a JSON parse error.
                    return res.json().catch(function () {
                        return null;
                    }).then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data || result.data.ok === false) {
                        throw new Error(
                            (result.data && (result.data.error || result.data.message)) ||
                                'Could not send the Pre-Planner submission.'
                        );
                    }
                    if (result.data.sent_at) {
                        item.setAttribute('data-fc-webhook-sent-at', result.data.sent_at);
                        showSentAt(result.data.sent_at);
                    }
                    toast('success', result.data.message || 'Pre-Planner submission sent.');
                })
                .catch(function (err) {
                    toast('error', err.message || 'Could not send the Pre-Planner submission.');
                })
                .then(function () {
                    item.disabled = false;
                });
        });
    }

    function bindMenu(menu) {
        var toggle = menu.querySelector('[data-fc-entries-detail-menu-toggle]');
        var panel = menu.querySelector('[data-fc-entries-detail-menu-panel]');
        var sendItem = menu.querySelector('[data-fc-entries-send-pre-planner]');
        var registry = global.FC.components.DropdownRegistry;
        if (!toggle || !panel) {
            return;
        }

        function close() {
            registry.notifyClosed(menu);
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        // openExclusive also shuts the cart's fence-style dropdown, which our stopPropagation hides clicks from.
        function open() {
            registry.openExclusive(menu, close);
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (panel.hidden) {
                open();
            } else {
                close();
            }
        });

        if (sendItem) {
            sendItem.addEventListener('click', function () {
                close();
                sendPrePlanner(menu, sendItem);
            });
        }

        document.addEventListener('click', function (e) {
            if (!panel.hidden && !menu.contains(e.target)) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) {
                close();
                toggle.focus();
            }
        });
    }

    document.querySelectorAll('[data-fc-entries-detail-menu]').forEach(bindMenu);
})(window);
