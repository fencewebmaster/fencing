/**
 * FC Admin — Settings → Site Health tab (Super Admin only). Runs the read-only check groups
 * through settings&action=site-health, one request per card, and paints the overview from them.
 */
(function (global) {
    'use strict';

    var API_SITE_HEALTH = global.fcApiUrl('settings', 'action=site-health');

    // Problems first, then warnings and passes; unscored notes last.
    var STATE_ORDER = { bad: 0, warn: 1, good: 2, info: 3 };
    var STATE_ICONS = {
        bad: 'fa-circle-xmark',
        warn: 'fa-triangle-exclamation',
        good: 'fa-circle-check',
        info: 'fa-circle-info'
    };
    var STATE_WORDS = { bad: 'Problem', warn: 'Warning', good: 'Passed', info: 'Note' };

    function stateOf(check) {
        return Object.prototype.hasOwnProperty.call(STATE_ORDER, check && check.state) ? check.state : 'info';
    }

    function tally(checks) {
        var counts = { bad: 0, warn: 0, good: 0, info: 0 };
        checks.forEach(function (check) {
            counts[stateOf(check)] += 1;
        });
        return counts;
    }

    function plural(count, word) {
        return count + ' ' + word + (count === 1 ? '' : 's');
    }

    function summary(counts) {
        var parts = [];
        if (counts.bad) {
            parts.push(plural(counts.bad, 'problem'));
        }
        if (counts.warn) {
            parts.push(plural(counts.warn, 'warning'));
        }
        return parts.length ? parts.join(' · ') : 'All good';
    }

    function worst(counts) {
        if (counts.bad) {
            return 'bad';
        }
        return counts.warn ? 'warn' : 'good';
    }

    class SiteHealthTabController extends global.FC.Settings.TabController {
        constructor() {
            super();
            this.results = {};
            this.pending = 0;
            this.started = false;
            this.bound = false;
            this.checkedAt = null;
        }

        /** @returns {HTMLElement|null} rendered for the Super Admin only */
        panel() {
            return document.getElementById('fc-settings-panel-site-health');
        }

        groups() {
            return Array.prototype.map.call(document.querySelectorAll('[data-fc-health-group]'), function (card) {
                return card.getAttribute('data-fc-health-group');
            });
        }

        bind() {
            var self = this;
            if (this.bound || !this.panel()) {
                return;
            }
            this.bound = true;

            var run = document.getElementById('fc-health-run');
            if (run) {
                run.addEventListener('click', function () {
                    self.run();
                });
            }
            var copy = document.getElementById('fc-health-copy');
            if (copy) {
                copy.addEventListener('click', function () {
                    self.copyReport();
                });
            }
            document.querySelectorAll('[data-fc-health-tile]').forEach(function (tile) {
                tile.addEventListener('click', function () {
                    self.jumpTo(tile.getAttribute('data-fc-health-tile'));
                });
            });
        }

        /** The first visit runs the checks; coming back keeps the results until Run Checks Again. */
        ensureRun() {
            if (!this.started) {
                this.run();
            }
        }

        run() {
            var self = this;
            var groups = this.groups();
            if (!this.panel() || this.pending > 0 || !groups.length) {
                return;
            }
            this.started = true;
            this.results = {};
            this.pending = groups.length;
            global.FC.util.setSaving(document.getElementById('fc-health-run'), true);

            groups.forEach(function (group) {
                self.paintGroup(group);
                self.fetchGroup(group)
                    .then(function (checks) {
                        self.results[group] = { ok: true, checks: checks };
                    })
                    .catch(function (err) {
                        self.results[group] = { ok: false, error: (err && err.message) || 'The checks could not run.' };
                    })
                    .then(function () {
                        self.pending -= 1;
                        if (self.pending === 0) {
                            self.checkedAt = new Date();
                            global.FC.util.setSaving(document.getElementById('fc-health-run'), false);
                        }
                        self.paintGroup(group);
                        self.paintOverview();
                    });
            });
            this.paintOverview();
        }

        fetchGroup(group) {
            return fetch(API_SITE_HEALTH + '&group=' + encodeURIComponent(group), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res
                    .json()
                    .catch(function () {
                        return {};
                    })
                    .then(function (body) {
                        if (!res.ok || !body.ok) {
                            throw new Error(body.error || 'The checks could not run (HTTP ' + res.status + ').');
                        }
                        return Array.isArray(body.checks) ? body.checks : [];
                    });
            });
        }

        sorted(checks) {
            return checks.slice().sort(function (a, b) {
                return STATE_ORDER[stateOf(a)] - STATE_ORDER[stateOf(b)];
            });
        }

        rowHtml(check) {
            var escapeHtml = global.FC.util.escapeHtml;
            var state = stateOf(check);
            return (
                '<li class="fc-health-row" data-state="' + state + '">' +
                '<span class="fc-health-row__icon" aria-hidden="true"><i class="fa-solid ' + STATE_ICONS[state] + '"></i></span>' +
                '<div class="fc-health-row__text">' +
                '<div class="fc-health-row__head">' +
                '<span class="fc-health-row__label"><span class="sr-only">' + STATE_WORDS[state] + ': </span>' +
                escapeHtml(check.label || '') + '</span>' +
                (check.value ? '<span class="fc-health-row__value">' + escapeHtml(check.value) + '</span>' : '') +
                '</div>' +
                (check.detail ? '<p class="fc-health-row__detail">' + escapeHtml(check.detail) + '</p>' : '') +
                '</div>' +
                '</li>'
            );
        }

        paintChip(chip, state, text) {
            if (chip) {
                chip.setAttribute('data-state', state);
                chip.textContent = text;
            }
        }

        paintGroup(group) {
            var card = document.querySelector('[data-fc-health-group="' + group + '"]');
            var list = card ? card.querySelector('[data-fc-health-list]') : null;
            if (!list) {
                return;
            }
            var chip = card.querySelector('[data-fc-health-chip]');
            var result = this.results[group];

            if (!result) {
                list.setAttribute('aria-busy', 'true');
                list.innerHTML =
                    '<li class="fc-health-row fc-health-row--loading" data-state="info">' +
                    '<span class="fc-health-row__icon" aria-hidden="true"><i class="fa-solid fa-circle-notch fa-spin"></i></span>' +
                    '<div class="fc-health-row__text"><span class="fc-health-row__label">Running checks…</span></div>' +
                    '</li>';
                this.paintChip(chip, 'info', 'Checking…');
                return;
            }

            list.removeAttribute('aria-busy');
            if (!result.ok) {
                list.innerHTML = this.rowHtml({ state: 'bad', label: 'Not checked', detail: result.error });
                this.paintChip(chip, 'bad', 'Not checked');
                return;
            }

            list.innerHTML = this.sorted(result.checks).map(this.rowHtml.bind(this)).join('');
            var counts = tally(result.checks);
            this.paintChip(chip, worst(counts), summary(counts));
        }

        paintOverview() {
            var self = this;
            var totals = { bad: 0, warn: 0, good: 0, info: 0 };
            var failed = 0;

            this.groups().forEach(function (group) {
                var tile = document.querySelector('[data-fc-health-tile="' + group + '"]');
                var result = self.results[group];
                var state = 'info';
                var text = 'Checking…';
                if (result && result.ok) {
                    var counts = tally(result.checks);
                    Object.keys(totals).forEach(function (key) {
                        totals[key] += counts[key];
                    });
                    state = worst(counts);
                    text = summary(counts);
                } else if (result) {
                    failed += 1;
                    state = 'bad';
                    text = 'Not checked';
                }
                if (!tile) {
                    return;
                }
                tile.setAttribute('data-state', state);
                var icon = tile.querySelector('[data-fc-health-tile-icon]');
                if (icon) {
                    icon.className = 'fa-solid ' + (result ? STATE_ICONS[state] : 'fa-circle-notch fa-spin');
                }
                var value = tile.querySelector('[data-fc-health-tile-value]');
                if (value) {
                    value.textContent = text;
                }
            });

            var running = this.pending > 0;
            var scored = totals.good + totals.warn + totals.bad;
            var score = document.querySelector('[data-fc-health-score]');
            var scoreText = document.querySelector('[data-fc-health-score-text]');
            var fill = document.querySelector('[data-fc-health-score-fill]');
            if (score) {
                score.setAttribute('data-state', running ? 'info' : failed ? 'bad' : worst(totals));
            }
            if (scoreText) {
                if (running) {
                    scoreText.textContent = 'Running checks…';
                } else {
                    scoreText.textContent = scored ? totals.good + ' of ' + scored + ' checks passed' : 'The checks could not run';
                }
            }
            if (fill) {
                fill.style.width = running || !scored ? '0%' : Math.round((totals.good / scored) * 100) + '%';
            }

            var checked = document.querySelector('[data-fc-health-checked]');
            if (checked) {
                checked.textContent = this.checkedAt && !running
                    ? 'Checked at ' + this.checkedAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
                    : '';
            }
            var copy = document.getElementById('fc-health-copy');
            if (copy) {
                copy.disabled = running || !this.checkedAt;
            }
        }

        jumpTo(group) {
            var card = document.querySelector('[data-fc-health-group="' + group + '"]');
            if (!card) {
                return;
            }
            var reduceMotion = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;
            card.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
            card.classList.remove('fc-seo-flash');
            void card.offsetWidth;
            card.classList.add('fc-seo-flash');
            setTimeout(function () {
                card.classList.remove('fc-seo-flash');
            }, 1600);
        }

        /** Plain text, problems first, for pasting to a developer or the host. */
        reportText() {
            var self = this;
            var scoreText = document.querySelector('[data-fc-health-score-text]');
            var lines = [
                'Site Health: ' + global.location.host + (this.checkedAt ? ', ' + this.checkedAt.toLocaleString() : ''),
                scoreText ? scoreText.textContent : ''
            ];

            this.groups().forEach(function (group) {
                var title = document.getElementById('fc-health-title-' + group);
                var result = self.results[group];
                lines.push('', (title ? title.textContent : group).toUpperCase());
                if (!result || !result.ok) {
                    lines.push('[' + STATE_WORDS.bad + '] Not checked' + (result && result.error ? ': ' + result.error : ''));
                    return;
                }
                self.sorted(result.checks).forEach(function (check) {
                    lines.push(
                        '[' + STATE_WORDS[stateOf(check)] + '] ' + check.label +
                        (check.value ? ': ' + check.value : '') +
                        (check.detail ? ' (' + check.detail + ')' : '')
                    );
                });
            });

            return lines.join('\n');
        }

        copyReport() {
            global.FC.util
                .copyToClipboard(this.reportText())
                .then(function () {
                    global.FC.util.toast('success', 'Site Health report copied.');
                })
                .catch(function () {
                    global.FC.util.toast('error', 'Could not copy the report.');
                });
        }
    }

    global.FC.Settings = global.FC.Settings || {};
    global.FC.Settings.tabs = global.FC.Settings.tabs || {};
    global.FC.Settings.tabs.siteHealth = new SiteHealthTabController();
})(window);
