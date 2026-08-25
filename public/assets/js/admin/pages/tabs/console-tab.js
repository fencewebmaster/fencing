/**
 * FC Admin — Settings → Console tab (debug-mode toggle + dev console).
 * Extracted verbatim from settings.js. The dev console executes a
 * restricted set of shell/git commands via API_DEV_CONSOLE — this is
 * intentionally moved with zero logic changes given what it does.
 */
(function (global) {
    'use strict';

    var API_CONSOLE = global.fcApiUrl('settings', 'action=console');
    var API_DEV_CONSOLE = global.fcApiUrl('settings', 'action=dev-console');
    var TOAST_CONSOLE = 'fc-console-save';

    class ConsoleTabController extends global.FC.Settings.TabController {
        bind() {
            this.bindDebugModeToggle();
            this.bindDevConsole();
        }

        applyDebugModeFlag(enabled) {
            var on = !!enabled;
            try {
                document.documentElement.setAttribute('data-fc-debug', on ? '1' : '0');
            } catch (e) {
                /* ignore */
            }
            try {
                global.FC_DEBUG = on;
            } catch (e2) {
                /* ignore */
            }
        }

        paintDebugModeToggle() {
            var state = this.state;
            var enabled = !!(state.console && state.console.debugMode);
            document.querySelectorAll('[data-fc-debug-mode]').forEach(function (btn) {
                var value = btn.getAttribute('data-fc-debug-mode') === '1';
                var active = value === enabled;
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                btn.classList.toggle('fc-debug-toggle--active', active);
                btn.classList.toggle('shadow-sm', active);
                btn.classList.toggle('text-slate-600', !active);
                btn.classList.toggle('hover:text-slate-900', !active);
            });
            this.applyDebugModeFlag(enabled);
        }

        saveDebugMode(enabled) {
            var self = this;
            var state = this.state;
            if (state.consoleSaving) {
                return;
            }
            var next = !!enabled;
            if (!!(state.console && state.console.debugMode) === next) {
                this.paintDebugModeToggle();
                return;
            }

            state.consoleSaving = true;
            state.console = Object.assign({}, state.console || {}, { debugMode: next });
            this.paintDebugModeToggle();
            global.FC.util.toast('saving', next ? 'Turning Debug Mode on…' : 'Turning Debug Mode off…', TOAST_CONSOLE);

            fetch(API_CONSOLE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    console: state.console,
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
                    state.console = Object.assign(
                        {},
                        state.consoleDefaults || { debugMode: false },
                        body.console || state.console
                    );
                    self.paintDebugModeToggle();
                    self.flash.set(
                        body.message ||
                            (state.console.debugMode ? 'Debug Mode turned on.' : 'Debug Mode turned off.'),
                        'success'
                    );
                    try {
                        var url = new URL(window.location.href);
                        window.location.assign(url.pathname + url.search);
                    } catch (e) {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    state.console = Object.assign({}, state.console || {}, { debugMode: !next });
                    self.paintDebugModeToggle();
                    global.FC.util.toast('error', err.message || 'Could not update Debug Mode.', TOAST_CONSOLE);
                })
                .then(function () {
                    state.consoleSaving = false;
                });
        }

        bindDebugModeToggle() {
            var self = this;
            var state = this.state;
            if (state.consoleFormBound) {
                return;
            }
            var buttons = document.querySelectorAll('[data-fc-debug-mode]');
            if (!buttons.length) {
                return;
            }
            state.consoleFormBound = true;
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.saveDebugMode(btn.getAttribute('data-fc-debug-mode') === '1');
                });
            });
            this.paintDebugModeToggle();
        }

        bindDevConsole() {
            var state = this.state;
            var form = document.getElementById('fc-dev-console-form');
            var input = document.getElementById('fc-dev-console-input');
            var output = document.getElementById('fc-dev-console-output');
            if (!form || !input || !output || form.getAttribute('data-fc-dev-console-bound') === '1') {
                return;
            }
            form.setAttribute('data-fc-dev-console-bound', '1');

            var history = [];
            var historyIndex = -1;
            var busy = false;

            function appendLine(text, kind) {
                var line = document.createElement('div');
                line.className = 'fc-dev-console__line' + (kind ? ' fc-dev-console__line--' + kind : '');
                line.textContent = text;
                output.appendChild(line);
                output.scrollTop = output.scrollHeight;
            }

            function clearOutput() {
                output.innerHTML = '';
            }

            function runLocal(command) {
                var normalized = String(command || '')
                    .trim()
                    .replace(/\s+/g, ' ')
                    .toLowerCase();
                if (normalized === 'clear') {
                    clearOutput();
                    return true;
                }
                if (normalized === 'help' || normalized === '?') {
                    appendLine('$ ' + command, 'cmd');
                    appendLine(
                        [
                            'Commands:',
                            '  help                 Show this help',
                            '  clear                Clear the console',
                            '  pwd                  Show project root',
                            '  git <args>           Any git command in the project root',
                            '',
                            'Shell operators and -C / --git-dir overrides are blocked.'
                        ].join('\n'),
                        'out'
                    );
                    return true;
                }
                return false;
            }

            function tokenizeCommand(command) {
                var raw = String(command || '');
                if (/[;|&`$(){}<>\\]/.test(raw)) {
                    return null;
                }
                var tokens = [];
                var current = '';
                var quote = '';
                for (var i = 0; i < raw.length; i++) {
                    var ch = raw.charAt(i);
                    if (quote) {
                        if (ch === quote) {
                            quote = '';
                        } else {
                            current += ch;
                        }
                        continue;
                    }
                    if (ch === '"' || ch === "'") {
                        quote = ch;
                        continue;
                    }
                    if (ch === ' ' || ch === '\t') {
                        if (current) {
                            tokens.push(current);
                            current = '';
                        }
                        continue;
                    }
                    current += ch;
                }
                if (quote) {
                    return null;
                }
                if (current) {
                    tokens.push(current);
                }
                return tokens;
            }

            function gitNeedsConfirm(command) {
                var argv = tokenizeCommand(command);
                if (!argv || !argv.length || String(argv[0]).toLowerCase() !== 'git') {
                    return false;
                }
                var i = 1;
                while (i < argv.length && String(argv[i]).charAt(0) === '-') {
                    if (argv[i] === '-c') {
                        i += 2;
                        continue;
                    }
                    i += 1;
                }
                var sub = String(argv[i] || '').toLowerCase();
                var always = {
                    pull: 1,
                    push: 1,
                    fetch: 1,
                    merge: 1,
                    rebase: 1,
                    reset: 1,
                    clean: 1,
                    commit: 1,
                    checkout: 1,
                    switch: 1,
                    am: 1,
                    'cherry-pick': 1,
                    revert: 1,
                    gc: 1,
                    prune: 1,
                    'filter-branch': 1,
                    replace: 1,
                    submodule: 1,
                    worktree: 1
                };
                if (always[sub]) {
                    return true;
                }
                var rest = argv.slice(i + 1);
                if (sub === 'stash') {
                    var stashAction = String(rest[0] || 'push').toLowerCase();
                    return stashAction !== 'list' && stashAction !== 'show';
                }
                if (sub === 'branch') {
                    return rest.some(function (arg) {
                        return (
                            arg === '-d' ||
                            arg === '-D' ||
                            arg === '--delete' ||
                            arg === '-m' ||
                            arg === '-M' ||
                            arg === '--move' ||
                            arg === '-c' ||
                            arg === '-C' ||
                            arg === '--copy'
                        );
                    });
                }
                if (sub === 'tag') {
                    if (
                        rest.some(function (arg) {
                            return arg === '-d' || arg === '--delete' || arg === '-f' || arg === '--force';
                        })
                    ) {
                        return true;
                    }
                    return rest.length > 0 && String(rest[0]).charAt(0) !== '-';
                }
                if (sub === 'remote') {
                    var remoteAction = String(rest[0] || '').toLowerCase();
                    return (
                        remoteAction === 'add' ||
                        remoteAction === 'remove' ||
                        remoteAction === 'rm' ||
                        remoteAction === 'rename' ||
                        remoteAction === 'set-url' ||
                        remoteAction === 'prune'
                    );
                }
                return false;
            }

            function executeCommand(command) {
                var trimmed = String(command || '').trim();
                if (!trimmed || busy) {
                    return;
                }

                history.push(trimmed);
                historyIndex = history.length;

                if (runLocal(trimmed)) {
                    return;
                }

                busy = true;
                input.disabled = true;
                appendLine('$ ' + trimmed, 'cmd');

                var body = {
                    csrf: state.csrf || '',
                    command: trimmed
                };
                if (gitNeedsConfirm(trimmed)) {
                    body.confirm = 'CONFIRM';
                }

                fetch(API_DEV_CONSOLE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body)
                })
                    .then(function (res) {
                        return res.json().then(function (payload) {
                            return { res: res, body: payload || {} };
                        });
                    })
                    .then(function (result) {
                        var payload = result.body;
                        if (payload.clear) {
                            clearOutput();
                            return;
                        }
                        var text = String(payload.output || payload.error || '').trim();
                        if (!result.res.ok || !payload.ok) {
                            appendLine(text || payload.error || 'Command failed.', 'err');
                            return;
                        }
                        appendLine(text || '(no output)', 'out');
                    })
                    .catch(function (err) {
                        appendLine((err && err.message) || 'Could not run command.', 'err');
                    })
                    .finally(function () {
                        busy = false;
                        input.disabled = false;
                        input.focus();
                    });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var value = input.value;
                input.value = '';
                executeCommand(value);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!history.length) {
                        return;
                    }
                    historyIndex = Math.max(0, historyIndex - 1);
                    input.value = history[historyIndex] || '';
                    input.setSelectionRange(input.value.length, input.value.length);
                    return;
                }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!history.length) {
                        return;
                    }
                    historyIndex = Math.min(history.length, historyIndex + 1);
                    input.value = historyIndex >= history.length ? '' : history[historyIndex] || '';
                    input.setSelectionRange(input.value.length, input.value.length);
                }
            });

            appendLine('FC Dev Console — type "help" for commands.', 'muted');
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.console = new ConsoleTabController();
})(window);
