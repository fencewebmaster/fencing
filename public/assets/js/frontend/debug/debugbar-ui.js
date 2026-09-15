/**
 * FC Debugbar — UI: the docked bar, panels, keyboard controls and actions.
 * Loads after debugbar-core.js (same conditional footer block) and renders exclusively
 * from FCDebugbar state + read-only localStorage/session readers. All DOM is built with
 * createElement/textContent — no innerHTML for captured data, ever.
 */
(function (window) {
    'use strict';

    var doc = window.document;
    var core = window.FCDebugbar;
    if (!core) { return; }

    var state = core.state;
    var prefs = core.prefs;

    // ------------------------------------------------------------------
    // DOM helpers (textContent only)
    // ------------------------------------------------------------------

    /** Which screen the bar is on. Classified in core so the suite can assert it. */
    function page() {
        return core.pageInfo();
    }

    /** Where a calculation is triggered from, or null when this page has no such control. */
    function calcTrigger() {
        return doc.querySelector('.btn-fc-calculate');
    }

    function el(tag, className, text) {
        var node = doc.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined && text !== null) { node.textContent = String(text); }
        return node;
    }

    function btn(label, title, onClick) {
        var b = el('button', 'fc-debugbar__btn', label);
        b.type = 'button';
        b.title = title;
        b.addEventListener('click', function (e) {
            e.preventDefault();
            try { onClick(e); } catch (err) {}
        });
        return b;
    }

    function table(headers, rows) {
        var wrap = el('div', 'fc-debugbar__tablewrap');
        var t = el('table', 'fc-debugbar__table');
        var thead = el('thead');
        var tr = el('tr');
        headers.forEach(function (h) { tr.appendChild(el('th', null, h)); });
        thead.appendChild(tr);
        t.appendChild(thead);
        var tbody = el('tbody');
        rows.forEach(function (cells) {
            var row = el('tr');
            cells.forEach(function (cell) {
                var td = el('td');
                if (cell && cell.nodeType) { td.appendChild(cell); } else { td.textContent = cell === undefined || cell === null ? '' : String(cell); }
                row.appendChild(td);
            });
            tbody.appendChild(row);
        });
        t.appendChild(tbody);
        wrap.appendChild(t);
        return wrap;
    }

    function jsonBlock(value) {
        var pre = el('pre', 'fc-debugbar__pre');
        try { pre.textContent = JSON.stringify(value, null, 2); } catch (e) { pre.textContent = String(value); }
        return pre;
    }

    // Every <details> carries a key so restorePanelState can reopen the ones the reader had
    // expanded: the panel is rebuilt from scratch on every refresh, and without this a
    // captured entry snapped shut ~4x/sec while the calculator was busy.
    //
    // List rows pass `key` derived from the entry itself, never its position — Log and
    // Requests render newest-first, so one arriving entry shifts every index and a
    // positional key would reopen the wrong row (or none).
    var detailsSeq = 0;

    /**
     * @param {*} value either the value, or a thunk returning it. Thunks matter on the Data
     *   panel: a row's parsed/pretty-printed JSON costs more than the row itself, and almost
     *   every row stays collapsed, so nothing is parsed, snapshotted or stringified until the
     *   reader actually opens it. Programmatic `.open = true` fires `toggle` too, so
     *   restorePanelState() still fills what it reopens.
     */
    function details(summaryText, value, key) {
        var d = el('details', 'fc-debugbar__details');
        d.setAttribute('data-fc-dkey', ui.tab + ':' + (key !== undefined && key !== null ? key : 'n' + (detailsSeq++)) + ':' + summaryText);
        d.appendChild(el('summary', null, summaryText));

        var built = false;
        d.addEventListener('toggle', function () {
            if (!d.open || built) { return; }
            built = true;
            try {
                d.appendChild(jsonBlock(typeof value === 'function' ? value() : value));
            } catch (e) {
                d.appendChild(el('pre', 'fc-debugbar__pre', 'Could not render: ' + e.message));
            }
        });

        return d;
    }

    var SVG_NS = 'http://www.w3.org/2000/svg';

    /**
     * Chevron for the expand/collapse control. Built with createElementNS rather than an
     * innerHTML string so this file keeps its "no innerHTML" property, and inline rather
     * than an icon font because the Debugbar loads no external resources.
     */
    function chevron() {
        var svg = doc.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('viewBox', '0 0 16 16');
        svg.setAttribute('width', '13');
        svg.setAttribute('height', '13');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        var path = doc.createElementNS(SVG_NS, 'path');
        path.setAttribute('d', 'M3 10.5L8 5.5l5 5');
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', 'currentColor');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(path);
        return svg;
    }

    function fmtMs(ms) {
        return ms === null || ms === undefined ? '—' : (Math.round(ms * 10) / 10) + 'ms';
    }

    function fmtTime(at) {
        try {
            var d = new Date(at);
            return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2) + ':' + ('0' + d.getSeconds()).slice(-2);
        } catch (e) { return ''; }
    }

    // ------------------------------------------------------------------
    // Bar skeleton
    // ------------------------------------------------------------------

    var TABS = ['Inputs', 'Trace', 'BOM', 'State', 'Data', 'Log', 'Timing', 'Requests', 'Environment'];

    // Sub-tabs of the Data panel. 'db' is read-only by design — see the renderer.
    var DATA_TABS = [
        { key: 'db', label: 'Database' },
        { key: 'local', label: 'Local Storage' },
        { key: 'session', label: 'Session Storage' },
        { key: 'cookies', label: 'Cookies' },
        { key: 'php', label: 'PHP Session' }
    ];

    var ui = {
        root: null,
        panel: null,
        tabButtons: {},
        badge: { calc: null, bom: null, warn: null, err: null, paused: null },
        open: prefs.open === true,
        height: typeof prefs.height === 'number' ? prefs.height : 300,
        tab: TABS.indexOf(prefs.tab) !== -1 ? prefs.tab : 'Trace',
        theme: prefs.theme === 'dark' ? 'dark' : 'light',
        logFilter: 'all',
        logSearch: '',
        traceShowPrev: false,
        dataTab: DATA_TABS.some(function (t) { return t.key === prefs.dataTab; }) ? prefs.dataTab : 'local',
        dataSearch: '',
        dataShowAll: false,
        dataAppOnly: prefs.dataAppOnly !== false,
        confirmKey: null,
        body: null,
        viewer: null
    };

    var DATA_ROW_LIMIT = 60;

    function persist() {
        prefs.open = ui.open;
        // Retired switch; dropped so a stored hidden:true cannot keep the bar down.
        delete prefs.hidden;
        prefs.height = ui.height;
        prefs.tab = ui.tab;
        prefs.theme = ui.theme;
        prefs.dataTab = ui.dataTab;
        prefs.dataAppOnly = ui.dataAppOnly;
        core.savePrefs();
    }

    function build() {
        var root = el('div', 'fc-debugbar');
        root.setAttribute('data-fc-debugbar-theme', ui.theme);

        var grip = el('div', 'fc-debugbar__grip');
        grip.title = 'Drag to resize';
        root.appendChild(grip);

        // Collapsed strip
        var strip = el('div', 'fc-debugbar__strip');
        strip.appendChild(el('span', 'fc-debugbar__brand', 'FC Debug'));
        // Both pages render the same bar, so name the one being inspected.
        strip.appendChild(el('span', 'fc-debugbar__page', page().label));
        ui.badge.calc = el('span', 'fc-debugbar__stat', 'calc —');
        ui.badge.bom = el('span', 'fc-debugbar__stat', 'BOM —');
        ui.badge.warn = el('span', 'fc-debugbar__stat fc-debugbar__stat--warn', '');
        ui.badge.err = el('span', 'fc-debugbar__stat fc-debugbar__stat--err', '');
        ui.badge.paused = el('span', 'fc-debugbar__stat fc-debugbar__stat--paused', 'paused');
        ui.badge.paused.hidden = true;
        strip.appendChild(ui.badge.calc);
        strip.appendChild(ui.badge.bom);
        strip.appendChild(ui.badge.warn);
        strip.appendChild(ui.badge.err);
        strip.appendChild(ui.badge.paused);
        strip.appendChild(el('span', 'fc-debugbar__spacer'));
        var toggle = el('button', 'fc-debugbar__btn fc-debugbar__toggle');
        toggle.type = 'button';
        toggle.appendChild(chevron());
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            setOpen(!ui.open);
        });
        ui.toggleBtn = toggle;
        strip.appendChild(toggle);

        // The whole strip is the affordance, the way a docked drawer header usually is — the
        // button alone is a small target on a 23px bar, and the badges beside it are inert
        // text, so they may as well toggle too. Only real buttons are excluded, so they keep
        // their own behaviour.
        strip.addEventListener('click', function (e) {
            if (e.target.closest('button')) { return; }
            setOpen(!ui.open);
        });
        strip.style.cursor = 'pointer';
        root.appendChild(strip);

        // Body: tabs + actions + panel
        var body = el('div', 'fc-debugbar__body');

        var bar = el('div', 'fc-debugbar__topbar');
        var tabs = el('div', 'fc-debugbar__tabs');
        tabs.setAttribute('role', 'tablist');
        tabs.setAttribute('aria-label', 'Debugbar panels');
        TABS.forEach(function (name) {
            var b = el('button', 'fc-debugbar__tab', name);
            b.type = 'button';
            b.id = 'fc-debugbar-tab-' + name.toLowerCase();
            b.setAttribute('role', 'tab');
            b.setAttribute('aria-controls', 'fc-debugbar-panel');
            b.addEventListener('click', function () { setTab(name); });
            b.addEventListener('keydown', function (e) {
                var idx = TABS.indexOf(ui.tab);
                if (e.key === 'ArrowRight') { setTab(TABS[(idx + 1) % TABS.length]); ui.tabButtons[ui.tab].focus(); e.preventDefault(); }
                if (e.key === 'ArrowLeft') { setTab(TABS[(idx - 1 + TABS.length) % TABS.length]); ui.tabButtons[ui.tab].focus(); e.preventDefault(); }
            });
            ui.tabButtons[name] = b;
            tabs.appendChild(b);
        });
        bar.appendChild(tabs);

        var actions = el('div', 'fc-debugbar__actions');
        actions.appendChild(btn('Copy', 'Copy the current panel as JSON', copyActivePanel));
        actions.appendChild(btn('Snapshot', 'Download a complete debug snapshot (JSON)', downloadSnapshot));
        ui.pauseBtn = btn('Pause', 'Pause / resume capture', function () {
            core.pause();
            ui.pauseBtn.textContent = state.paused ? 'Resume' : 'Pause';
            refresh();
        });
        actions.appendChild(ui.pauseBtn);
        actions.appendChild(btn('Clear', 'Clear captured trace, logs and requests', function () { core.clear(); refresh(); }));
        // Re-run drives the page's real Calculate button. The project plan has none, so rather
        // than offering a control that can only fail, it is shown disabled and says why.
        var rerun = btn('Re-run', 'Run the calculation again (clicks Calculate)', function () {
            var calc = calcTrigger();
            if (calc) { calc.click(); } else { window.debugbar.log('warn', 'No Calculate button on this page'); }
        });
        ui.rerunBtn = rerun;
        actions.appendChild(rerun);
        ui.verboseBtn = btn(verboseLabel(), 'Toggle verbose trace (per-stage calc + per-rule cart entries)', function () {
            core.setVerbose(!state.verbose);
            ui.verboseBtn.textContent = verboseLabel();
        });
        actions.appendChild(ui.verboseBtn);
        actions.appendChild(btn('Theme', 'Toggle light / dark', function () {
            ui.theme = ui.theme === 'dark' ? 'light' : 'dark';
            ui.root.setAttribute('data-fc-debugbar-theme', ui.theme);
            persist();
        }));
        bar.appendChild(actions);
        body.appendChild(bar);

        var panel = el('div', 'fc-debugbar__panel');
        panel.id = 'fc-debugbar-panel';
        panel.setAttribute('role', 'tabpanel');
        panel.tabIndex = 0;
        ui.panel = panel;
        // Flush a refresh that was held back while the reader was in the search box or an
        // open <select>; without this the panel could sit stale after they clicked away.
        panel.addEventListener('focusout', function () {
            window.setTimeout(function () {
                if (ui.panelRefreshDeferred && !panelHasFocusedControl()) { renderActivePanel(); }
            }, 0);
        });
        body.appendChild(panel);

        ui.body = body;
        root.appendChild(body);
        ui.root = root;
        doc.body.appendChild(root);

        installResize(grip);
        applyLayout();
        setTab(ui.tab);
        refresh();
    }

    function verboseLabel() {
        return state.verbose ? 'Verbose ✓' : 'Verbose';
    }

    // A height dragged tall on a big window is persisted verbatim; re-clamp on every layout
    // pass or an undocked laptop reopens the bar over the whole viewport.
    function clampHeight() {
        var max = Math.max(140, Math.round(window.innerHeight * 0.8));
        ui.height = Math.max(140, Math.min(max, ui.height));
    }

    function applyLayout() {
        if (!ui.root) { return; }
        clampHeight();
        ui.root.classList.toggle('fc-debugbar--open', ui.open);
        ui.root.style.setProperty('--fc-debugbar-h', ui.height + 'px');
        // The chevron rotates in CSS off .fc-debugbar--open; the accessible name carries the
        // state in words, because the old control's only name was the arrow glyph itself.
        var action = ui.open ? 'Collapse' : 'Expand';
        ui.toggleBtn.setAttribute('aria-expanded', ui.open ? 'true' : 'false');
        ui.toggleBtn.setAttribute('aria-label', action + ' Debugbar');
        ui.toggleBtn.title = action + ' Debugbar  ·  Esc collapses';
        // Reserve space under the page so the bar never covers calculator controls.
        var pad = ui.open ? ui.height + 28 : 28;
        doc.body.classList.add('fc-debugbar-padded');
        doc.body.style.setProperty('--fc-debugbar-pad', pad + 'px');
        persist();
    }

    function setOpen(open) {
        if (!open) { closeViewer(); }
        ui.open = !!open;
        applyLayout();
        if (ui.open) { renderActivePanel(); }
    }

    function setTab(name) {
        closeViewer();
        ui.tab = name;
        TABS.forEach(function (t) {
            var b = ui.tabButtons[t];
            b.classList.toggle('fc-debugbar__tab--active', t === name);
            b.setAttribute('aria-selected', t === name ? 'true' : 'false');
            b.tabIndex = t === name ? 0 : -1;
        });
        ui.panel.setAttribute('aria-labelledby', 'fc-debugbar-tab-' + name.toLowerCase());
        persist();
        renderActivePanel();
    }

    function installResize(grip) {
        var dragging = false;
        var startY = 0;
        var startH = 0;
        grip.addEventListener('pointerdown', function (e) {
            dragging = true;
            startY = e.clientY;
            startH = ui.height;
            grip.setPointerCapture(e.pointerId);
            e.preventDefault();
        });
        grip.addEventListener('pointermove', function (e) {
            if (!dragging) { return; }
            var next = startH + (startY - e.clientY);
            ui.height = Math.max(140, Math.min(Math.round(window.innerHeight * 0.8), next));
            if (!ui.open) { ui.open = true; }
            applyLayout();
        });
        grip.addEventListener('pointerup', function () { dragging = false; persist(); });
    }

    // ------------------------------------------------------------------
    // Badges
    // ------------------------------------------------------------------

    function refresh() {
        if (!ui.root) { return; }
        ui.badge.calc.textContent = state.lastCalc ? 'calc ' + fmtMs(state.lastCalc.ms) : 'calc —';
        var bom = core.readClientBom();
        ui.badge.bom.textContent = 'BOM ' + bom.length;
        ui.badge.warn.textContent = state.warnCount ? '⚠ ' + state.warnCount : '';
        ui.badge.err.textContent = state.errorCount ? '✕ ' + state.errorCount : '';
        ui.badge.paused.hidden = !state.paused;
        // The Data panel reads the browser's own stores, which no capture changes, and it
        // carries interactive rows plus its own Refresh button — rebuilding it four times a
        // second while the calculator works only fought the reader.
        syncRerun();
        if (ui.open && ui.tab !== 'Data') { renderActivePanel(true); }
    }

    // ------------------------------------------------------------------
    // Panels
    // ------------------------------------------------------------------

    /** True while the reader is typing in / has opened a control inside the panel. */
    function panelHasFocusedControl() {
        var active = doc.activeElement;
        if (!active || !ui.panel || !ui.panel.contains(active)) { return false; }
        var tag = active.tagName;
        return tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA';
    }

    function capturePanelState() {
        var open = {};
        var nodes = ui.panel.querySelectorAll('details[data-fc-dkey]');
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].open) { open[nodes[i].getAttribute('data-fc-dkey')] = true; }
        }
        return { scrollTop: ui.panel.scrollTop, open: open };
    }

    function restorePanelState(snap) {
        if (!snap) { return; }
        var nodes = ui.panel.querySelectorAll('details[data-fc-dkey]');
        for (var i = 0; i < nodes.length; i++) {
            if (snap.open[nodes[i].getAttribute('data-fc-dkey')]) { nodes[i].open = true; }
        }
        ui.panel.scrollTop = snap.scrollTop;
    }

    // Re-checked rather than decided once at build: the planner renders its Calculate button
    // in markup, but a page could swap views underneath us and a stale enabled button would
    // silently do nothing.
    function syncRerun() {
        if (!ui.rerunBtn) { return; }
        var has = !!calcTrigger();
        ui.rerunBtn.disabled = !has;
        ui.rerunBtn.title = has
            ? 'Run the calculation again (clicks Calculate)'
            : 'Nothing to re-run: the ' + page().label + ' has no Calculate button';
    }

    function renderActivePanel(auto) {
        if (!ui.panel) { return; }
        // An automatic refresh never yanks a control out from under the reader — a rebuild
        // mid-keystroke replaced the focused search box and dropped the caret, and closed an
        // open <select>. The deferred repaint runs on blur.
        if (auto && panelHasFocusedControl()) {
            ui.panelRefreshDeferred = true;
            return;
        }
        ui.panelRefreshDeferred = false;

        var snap = capturePanelState();
        var render = panelRenderers[ui.tab];
        detailsSeq = 0;
        ui.panel.textContent = '';
        try {
            render(ui.panel);
        } catch (e) {
            ui.panel.appendChild(el('p', 'fc-debugbar__empty', 'Panel failed to render: ' + e.message));
        }
        restorePanelState(snap);
    }

    function empty(panel, message) {
        panel.appendChild(el('p', 'fc-debugbar__empty', message));
    }

    // ------------------------------------------------------------------
    // Data panel renderers
    // ------------------------------------------------------------------

    function fmtBytes(n) {
        if (n === null || n === undefined) { return '—'; }
        if (n < 1024) { return n + ' B'; }
        if (n < 1048576) { return (Math.round(n / 102.4) / 10) + ' KB'; }
        return (Math.round(n / 104857.6) / 10) + ' MB';
    }

    function dataSearchBox(onChange) {
        var wrap = el('div', 'fc-debugbar__logcontrols');
        var search = doc.createElement('input');
        search.type = 'search';
        search.className = 'fc-debugbar__search';
        search.placeholder = 'Filter keys and values…';
        search.value = ui.dataSearch;
        search.addEventListener('input', function () {
            ui.dataSearch = search.value;
            onChange();
        });
        wrap.appendChild(search);
        return { wrap: wrap, input: search };
    }

    function matchesSearch(row) {
        var q = ui.dataSearch.toLowerCase();
        if (!q) { return true; }
        return String(row.key).toLowerCase().indexOf(q) !== -1 ||
            String(row.value).toLowerCase().indexOf(q) !== -1;
    }

    // ------------------------------------------------------------------
    // Full-size value viewer
    // ------------------------------------------------------------------

    // A parsed blob is unreadable in a table column a few hundred pixels wide, so opening one
    // takes over the whole bar instead. It is mounted on __body rather than __panel so a panel
    // re-render (sub-tab switch, Refresh, a row delete) cannot yank it out from under the
    // reader. It covers the bar completely, so there is no backdrop to click: closing is
    // Close or Escape, and Escape hits the viewer before the bar's own close.
    function closeViewer() {
        if (!ui.viewer) { return; }
        try { ui.viewer.parentNode.removeChild(ui.viewer); } catch (e) {}
        ui.viewer = null;
        try { if (ui.panel) { ui.panel.focus(); } } catch (e) {}
    }

    /**
     * @param {string} title    what is being shown, e.g. the storage key
     * @param {string} meta     secondary line (size, type, store)
     * @param {function(): *} produce  builds the value. Still lazy: nothing is parsed or
     *   stringified until the reader asks for this specific row.
     */
    function openViewer(title, meta, produce) {
        closeViewer();
        if (!ui.body) { return; }

        var v = el('div', 'fc-debugbar__viewer');
        v.setAttribute('role', 'dialog');
        v.setAttribute('aria-modal', 'true');
        v.setAttribute('aria-label', title);

        var head = el('div', 'fc-debugbar__viewerhead');
        var titles = el('div', 'fc-debugbar__viewertitles');
        titles.appendChild(el('strong', 'fc-debugbar__viewertitle', title));
        if (meta) { titles.appendChild(el('span', 'fc-debugbar__viewermeta', meta)); }
        head.appendChild(titles);

        var pre = el('pre', 'fc-debugbar__pre fc-debugbar__viewerpre');
        var text = '';
        try {
            var value = produce();
            text = typeof value === 'string' ? value : JSON.stringify(value, null, 2);
        } catch (e) {
            text = 'Could not render: ' + e.message;
        }
        pre.textContent = text;

        var actions = el('div', 'fc-debugbar__vieweractions');
        actions.appendChild(btn('Copy', 'Copy this value to the clipboard', function () {
            copyText(text);
            window.debugbar.log('info', 'Copied ' + title);
        }));
        actions.appendChild(btn('Wrap', 'Toggle line wrapping', function () {
            pre.classList.toggle('fc-debugbar__viewerpre--nowrap');
        }));
        var close = btn('Close', 'Close this view (Esc)', closeViewer);
        close.className += ' fc-debugbar__btn--on';
        actions.appendChild(close);
        head.appendChild(actions);

        v.appendChild(head);
        v.appendChild(pre);
        ui.body.appendChild(v);
        ui.viewer = v;
        try { pre.focus(); } catch (e) {}
    }

    /**
     * Diagonal expand arrows. Drawn rather than typed: the glyph for this (U+2922) is missing
     * from Consolas and only arrives via font fallback, and an escape for it in the stylesheet
     * is one mangled backslash away from printing its own code point at the reader.
     */
    function expandIcon() {
        var svg = doc.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('viewBox', '0 0 16 16');
        svg.setAttribute('width', '11');
        svg.setAttribute('height', '11');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        var g = doc.createElementNS(SVG_NS, 'path');
        g.setAttribute('d', 'M9.5 2.5h4v4M13.5 2.5L9 7M6.5 13.5h-4v-4M2.5 13.5L7 9');
        g.setAttribute('fill', 'none');
        g.setAttribute('stroke', 'currentColor');
        g.setAttribute('stroke-width', '1.6');
        g.setAttribute('stroke-linecap', 'round');
        g.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(g);
        return svg;
    }

    /** The affordance that opens it. Reads like the old <details> summary, acts like a button. */
    function viewerLink(label, title, meta, produce) {
        var b = el('button', 'fc-debugbar__expand');
        b.type = 'button';
        b.appendChild(expandIcon());
        b.appendChild(el('span', null, label));
        b.title = 'Open ' + title + ' full size';
        b.addEventListener('click', function (e) {
            e.preventDefault();
            openViewer(title, meta, produce);
        });
        return b;
    }

    function valueCell(row) {
        var wrap = el('div', 'fc-debugbar__valuecell');
        var raw = String(row.value);
        // Collapse whitespace on the slice, not the whole value: the preview is 120 chars and
        // some of these rows are hundreds of kilobytes.
        var head = raw.length > 400 ? raw.slice(0, 400) : raw;
        var oneLine = head.replace(/\s+/g, ' ');
        wrap.appendChild(el('span', 'fc-debugbar__valuepeek', oneLine.length > 120 ? oneLine.slice(0, 120) + '…' : oneLine));
        // Structured values open parsed, long plain strings open raw - both full size.
        var where = ui.dataTab === 'cookies' ? 'cookie' : (ui.dataTab === 'php' ? 'PHP session' : ui.dataTab + ' storage');
        if (row.type === 'json object' || row.type === 'json array') {
            wrap.appendChild(viewerLink('parsed (' + fmtBytes(row.bytes) + ')', row.key,
                where + ' · ' + row.type + ' · ' + fmtBytes(row.bytes),
                function () { return core.snapshot(JSON.parse(raw), 0); }));
        } else if (raw.length > 120) {
            wrap.appendChild(viewerLink('full value', row.key,
                where + ' · ' + row.type + ' · ' + fmtBytes(row.bytes),
                function () { return raw; }));
        }
        return wrap;
    }

    /** Row actions. Deletes ask once, in place - no window.confirm to steal focus. */
    function rowActions(row, kind, rerender) {
        var wrap = el('div', 'fc-debugbar__rowactions');

        wrap.appendChild(btn('Copy', 'Copy this value to the clipboard', function () {
            copyText(String(row.value));
            window.debugbar.log('info', 'Copied ' + row.key);
        }));

        if (kind === 'php') { return wrap; }

        var confirmId = kind + ':' + row.key;
        if (ui.confirmKey === confirmId) {
            var yes = btn('Confirm', 'Delete ' + row.key, function () {
                var ok = kind === 'cookies' ? core.removeCookie(row.key) : core.removeStorageKey(kind, row.key);
                ui.confirmKey = null;
                window.debugbar.log(ok ? 'warn' : 'error', (ok ? 'Deleted ' : 'Could not delete ') + kind + ' key "' + row.key + '"');
                rerender();
            });
            yes.className += ' fc-debugbar__btn--danger';
            wrap.appendChild(yes);
            wrap.appendChild(btn('Cancel', 'Keep this key', function () {
                ui.confirmKey = null;
                rerender();
            }));
        } else {
            wrap.appendChild(btn('Delete', 'Delete this key (asks to confirm)', function () {
                ui.confirmKey = confirmId;
                rerender();
            }));
        }

        return wrap;
    }

    function renderStoreTable(panel, kind) {
        var rerender = function () { renderActivePanel(); };
        var all = kind === 'cookies' ? core.readCookies() : core.readStorage(kind);
        // Third-party keys (Chatra, Google, Clarity) outnumber FC's own on a real page, so the
        // default scope is app-only. Never a silent hide: the out-of-scope count is always shown.
        var rows = ui.dataAppOnly ? all.filter(function (r) { return r.app; }) : all;
        var outOfScope = all.length - rows.length;
        var shown = rows.filter(matchesSearch);
        var totalBytes = 0;
        rows.forEach(function (r) { totalBytes += r.bytes; });

        var controls = dataSearchBox(rerender);
        var scope = btn(ui.dataAppOnly ? 'App only' : 'All keys',
            ui.dataAppOnly
                ? 'Showing only FC keys. Click to include third-party keys.'
                : 'Showing every key on this origin. Click to narrow to FC keys.',
            function () {
                ui.dataAppOnly = !ui.dataAppOnly;
                ui.dataShowAll = false;
                persist();
                rerender();
            });
        scope.setAttribute('aria-pressed', ui.dataAppOnly ? 'true' : 'false');
        if (ui.dataAppOnly) { scope.className += ' fc-debugbar__btn--on'; }
        controls.wrap.appendChild(scope);
        controls.wrap.appendChild(btn('Copy all', 'Copy every listed key as JSON', function () {
            var out = {};
            shown.forEach(function (r) { out[r.key] = r.value; });
            copyText(JSON.stringify(out, null, 2));
            window.debugbar.log('info', 'Copied ' + shown.length + ' ' + kind + ' entries');
        }));
        controls.wrap.appendChild(btn('Refresh', 'Re-read this store', rerender));
        panel.appendChild(controls.wrap);

        panel.appendChild(el('p', 'fc-debugbar__meta',
            rows.length + (ui.dataAppOnly ? ' app key(s), ' : ' key(s), ') + fmtBytes(totalBytes) + ' total' +
            (outOfScope ? ' · ' + outOfScope + ' third-party key(s) hidden' : '') +
            (shown.length !== rows.length ? ' · ' + shown.length + ' match the filter' : '') +
            (kind === 'cookies' ? ' · HttpOnly cookies (fc_admin_sess, fc_admin_remember) are invisible to scripts and cannot be listed here' : '')));

        if (!shown.length) {
            empty(panel, rows.length ? 'Nothing matches that filter.'
                : (outOfScope ? 'No FC keys here — the ' + outOfScope + ' key(s) on this origin all belong to third parties. Switch to "All keys" to see them.'
                    : 'This store is empty.'));
            return;
        }

        // Render a page at a time. A store with hundreds of keys otherwise builds hundreds of
        // rows on every sub-tab click; the cap is stated rather than silent, per the rule that
        // a truncated view must never read as a complete one.
        var limit = ui.dataShowAll ? shown.length : Math.min(shown.length, DATA_ROW_LIMIT);
        var page = shown.slice(0, limit);

        panel.appendChild(table(['Key', 'Type', 'Size', 'Value', ''], page.map(function (r) {
            return [
                r.key,
                r.redacted ? 'redacted' : r.type,
                fmtBytes(r.bytes),
                valueCell(r),
                rowActions(r, kind, rerender)
            ];
        })));

        if (limit < shown.length) {
            var more = el('p', 'fc-debugbar__meta', 'Showing ' + limit + ' of ' + shown.length + ' — ');
            more.appendChild(btn('Show all', 'Render every matching row', function () {
                ui.dataShowAll = true;
                rerender();
            }));
            panel.appendChild(more);
        }
    }

    function renderDatabase(panel) {
        var db = (state.server && state.server.database) || {};
        var queries = (state.server && state.server.queries) || [];
        var timing = (state.server && state.server.timing) || {};

        panel.appendChild(el('p', 'fc-debugbar__note',
            'Read-only. The Debugbar never queries the database on its own - everything here comes from ' +
            'state the request had already resolved, so turning debug on adds no queries. There is ' +
            'deliberately no query runner: this panel is served to any visitor while Debug Mode is on.'));

        panel.appendChild(el('h4', 'fc-debugbar__h', 'Connection'));
        panel.appendChild(table(['Item', 'Value'], [
            ['Planners table', (db.table || '—') + (db.demoSegment ? '   ← _demo, matched URL segment "' + db.demoSegment + '"' : '')],
            ['Table prefix', db.prefix || '—'],
            ['mysqli', db.driver && db.driver.mysqli ? 'loaded' + (db.driver.clientVersion ? ' (' + db.driver.clientVersion + ')' : '') : 'not loaded'],
            ['pdo_mysql', db.driver && db.driver.pdo_mysql ? 'loaded' : 'not loaded'],
            ['Current quote', db.plannerId || '(none in session)']
        ]));

        panel.appendChild(el('h4', 'fc-debugbar__h', 'wp_planners payload columns'));
        panel.appendChild(el('p', 'fc-debugbar__meta',
            'Sizes as they stand in the session, i.e. what the next save writes. Values are not shown - these columns carry the customer\u2019s own details.'));
        panel.appendChild(table(['Column', 'Written from', 'Size', 'Present'], (db.columns || []).map(function (c) {
            return [c.column, c.source, fmtBytes(c.bytes), c.present ? 'yes' : 'empty'];
        })));

        panel.appendChild(el('h4', 'fc-debugbar__h', 'Queries this page load'));
        panel.appendChild(el('p', 'fc-debugbar__meta',
            (timing.queryCount || 0) + ' query/queries, ' + fmtMs(timing.queryMs) +
            (timing.droppedQueries ? ' · ' + timing.droppedQueries + ' beyond the buffer were not logged' : '') +
            ' · literals are masked unless Verbose Trace is on'));
        if (!queries.length) {
            empty(panel, 'No queries ran on this page load.');
            return;
        }
        panel.appendChild(table(['#', 'Kind', 'Time', 'SQL'], queries.map(function (q, i) {
            var sql = el('div', 'fc-debugbar__sql' + (q.error ? ' fc-debugbar__sqlerr' : ''), q.sql + (q.error ? '  - ' + q.error : ''));
            return [i + 1, q.kind, fmtMs(q.ms), sql];
        })));
    }

    function renderPhpSession(panel) {
        var sess = (state.server && state.server.session) || {};
        if (!sess.active) {
            empty(panel, 'No PHP session on this page load.');
            return;
        }

        panel.appendChild(el('p', 'fc-debugbar__note',
            'Server-side session as it stood when the page was rendered. This is your own session and ' +
            'the redaction keys still apply at every depth, so JSON fields are decoded here — open one to ' +
            'read it full size. Fields over 64 KB stay collapsed to a byte count rather than weighing down ' +
            'every page load.'));

        panel.appendChild(el('h4', 'fc-debugbar__h', '$_SESSION keys'));
        panel.appendChild(table(['Key'], (sess.keys || []).map(function (k) { return [k]; })));

        var fcData = sess.fc_data || {};
        var rows = Object.keys(fcData).map(function (k) {
            var v = fcData[k];
            // A JSON field arrives decoded and already redacted, wrapped with the byte size it
            // occupies in the session. Re-serialising it into the same shape the storage rows
            // use means the expand link and the full-size viewer work here unchanged. `bytes`
            // stays the server's original count, not the re-encoded length: it is what the
            // session actually holds, which is the number worth reporting.
            if (v && typeof v === 'object' && typeof v.__fcJson === 'number') {
                var text;
                try { text = JSON.stringify(v.parsed); } catch (e) { text = String(v.parsed); }
                return {
                    key: k,
                    value: text,
                    redacted: false,
                    bytes: v.__fcJson,
                    type: Object.prototype.toString.call(v.parsed) === '[object Array]' ? 'json array' : 'json object'
                };
            }
            return {
                key: k,
                value: v === null ? 'null' : String(v),
                redacted: v === '[redacted]',
                bytes: core.byteLength(String(v)),
                type: typeof v
            };
        }).filter(matchesSearch);

        var controls = dataSearchBox(function () { renderActivePanel(); });
        controls.wrap.appendChild(btn('Copy all', 'Copy the session summary as JSON', function () {
            copyText(JSON.stringify(sess, null, 2));
            window.debugbar.log('info', 'Copied PHP session summary');
        }));
        panel.appendChild(el('h4', 'fc-debugbar__h', 'fc_data fields'));
        panel.appendChild(controls.wrap);
        panel.appendChild(table(['Field', 'Value', ''], rows.map(function (r) {
            return [r.key, valueCell(r), rowActions(r, 'php', function () { renderActivePanel(); })];
        })));

        panel.appendChild(el('p', 'fc-debugbar__meta',
            'planner_id: ' + (sess.planner_id || '(none)') + ' · server cart lines: ' +
            (sess.fc_cart_lines === undefined ? '?' : sess.fc_cart_lines)));
    }

    var panelRenderers = {
        Inputs: function (panel) {
            var v = state.lastValidation;
            if (!v) {
                empty(panel, page().calc
                    ? 'No validation captured yet — run a calculation (Step 2 → Calculate).'
                    : 'No validation captured yet. The ' + page().label + ' has no Calculate step of its own — entries appear here when it recalculates a section.');
                return;
            }
            panel.appendChild(el('p', 'fc-debugbar__meta',
                fmtTime(v.at) + ' · validation ' + (v.ok ? 'PASS' : 'FAIL') +
                (v.messages.length ? ' · ' + v.messages.length + ' message(s)' : '')));
            if (v.messages.length) {
                var list = el('ul', 'fc-debugbar__msgs');
                v.messages.forEach(function (m) { list.appendChild(el('li', null, m)); });
                panel.appendChild(list);
            }
            panel.appendChild(table(['Field', 'Value', 'Type'], v.fields.map(function (f) {
                return [f.name, f.value, typeof f.value];
            })));
        },

        Trace: function (panel) {
            var meta = el('p', 'fc-debugbar__meta',
                'runs ' + state.counters.calcRuns + ' · calc calls ' + state.counters.calcCalls +
                ' · renders ' + state.counters.renders +
                ' · swallowed hook errors ' + state.counters.swallowed +
                (state.trace.dropped ? ' · ' + state.trace.dropped + ' entries dropped (ring buffer ' + state.trace.cap + ')' : ''));
            panel.appendChild(meta);

            var switcher = btn(ui.traceShowPrev ? 'Showing: previous run' : 'Showing: current run',
                'Compare against the previous calculation run', function () {
                    ui.traceShowPrev = !ui.traceShowPrev;
                    renderActivePanel();
                });
            panel.appendChild(switcher);

            var entries = ui.traceShowPrev ? state.prevRun : state.currentRun;
            if (!entries.length && !ui.traceShowPrev) { entries = state.trace.items; }
            if (!entries.length) {
                empty(panel, page().calc
                    ? 'No trace yet — interact with the calculator, or enable Verbose for per-stage entries.'
                    : 'No trace yet — edit or reload a section on the ' + page().label + ', or enable Verbose for per-stage entries.');
                return;
            }
            var listWrap = el('div', 'fc-debugbar__list');
            entries.slice(-MAX_LIST).forEach(function (entry) {
                var line = el('div', 'fc-debugbar__traceline' + (entry.stage ? ' fc-debugbar__traceline--stage' : ''));
                var head = el('div', 'fc-debugbar__tracehead');
                head.appendChild(el('span', 'fc-debugbar__tracename', entry.name));
                if (entry.dispatch) { head.appendChild(el('span', 'fc-debugbar__tracemeta', entry.dispatch)); }
                if (entry.lines !== undefined) { head.appendChild(el('span', 'fc-debugbar__tracemeta', 'lines: ' + entry.lines)); }
                if (entry.ok !== undefined) { head.appendChild(el('span', 'fc-debugbar__tracemeta', entry.ok ? 'PASS' : 'FAIL')); }
                head.appendChild(el('span', 'fc-debugbar__spacer'));
                head.appendChild(el('span', 'fc-debugbar__tracems', fmtMs(entry.ms)));
                line.appendChild(head);
                if (entry.input !== undefined || entry.output !== undefined || entry.result) {
                    // Condensed here rather than inside the thunk: entry.result is a live
                    // reference to the object calculate_fences returned, so it is summarised
                    // while it still matches this line. summarizeCalcEntry memoises, so the
                    // repeat renders during a busy calculation cost nothing.
                    var out = entry.result ? core.summarizeCalcEntry(entry) : entry.output;
                    var payload = { input: entry.input, output: out };
                    var bits = [];
                    if (entry.dispatch) { bits.push(entry.dispatch); }
                    if (entry.lines !== undefined) { bits.push('lines: ' + entry.lines); }
                    if (entry.ok !== undefined) { bits.push(entry.ok ? 'PASS' : 'FAIL'); }
                    if (entry.ms !== null && entry.ms !== undefined) { bits.push(fmtMs(entry.ms)); }
                    line.appendChild(viewerLink('data', entry.name, bits.join(' · '), function () {
                        return payload;
                    }));
                }
                listWrap.appendChild(line);
            });
            panel.appendChild(listWrap);
        },

        BOM: function (panel) {
            var rows = core.readClientBom();
            var srv = state.server && state.server.session ? state.server.session : {};
            panel.appendChild(el('p', 'fc-debugbar__meta',
                rows.length + ' client BOM line(s) from localStorage cart_items-* · server cart holds ' +
                (srv.fc_cart_lines !== undefined ? srv.fc_cart_lines : '?') + ' SKU-resolved line(s) (page load)' +
                (state.lastProcess ? ' · last scrape: section ' + (state.lastProcess.tabIndex === null ? '?' : state.lastProcess.tabIndex + 1) + ', ' + state.lastProcess.lines + ' lines, ' + fmtMs(state.lastProcess.ms) : '')));
            panel.appendChild(el('p', 'fc-debugbar__note',
                'This is the exact scraped BOM the app stores and submits — not a recomputation. Row order and the "stock" flag are nondeterministic between rebuilds; compare by slug + qty.'));
            if (!rows.length) {
                empty(panel, 'No cart_items-* entries in localStorage yet.');
                return;
            }
            panel.appendChild(table(['Section', 'Fence', 'Slug', 'Qty', 'Unit', 'Optional'], rows.map(function (r) {
                return [r.bucket + 1, r.fence, r.slug, r.qty, 'each', r.optional ? ('yes (suggested ' + (r.suggested_qty === undefined ? '—' : r.suggested_qty) + ')') : ''];
            })));
        },

        State: function (panel) {
            var ps = core.readPlannerState();
            panel.appendChild(el('p', 'fc-debugbar__meta',
                'planner_id: ' + (ps.plannerId || '(none)') + ' · sections: ' + (ps.sectionCount || '0')));
            panel.appendChild(table(['Section', 'Fence style', 'Length (mm)', 'Calculated', 'Gate ONLY'], ps.sections.map(function (s) {
                return [s.section, s.style, s.length_mm, s.calculated ? 'yes' : 'no', s.gateOnly ? 'yes' : 'no'];
            })));
            if (ps.projectPlans) { panel.appendChild(details('project-plans (localStorage, redacted)', ps.projectPlans)); }
            if (state.server && state.server.session) { panel.appendChild(details('PHP session summary (page load, redacted)', state.server.session)); }
            if (state.server && state.server.config) {
                panel.appendChild(details('Effective Debugbar settings (source: Settings → Console)', state.server.config));
            }
            if (state.lastCalc) {
                panel.appendChild(details('Last calculation summary', {
                    ms: state.lastCalc.ms,
                    tab: state.lastCalc.tab,
                    item: state.lastCalc.item,
                    summary: core.summarizeCalcEntry(state.lastCalc)
                }));
            }
        },

        Data: function (panel) {
            var strip = el('div', 'fc-debugbar__subtabs');
            strip.setAttribute('role', 'tablist');
            strip.setAttribute('aria-label', 'Data stores');
            DATA_TABS.forEach(function (t) {
                var b = el('button', 'fc-debugbar__subtab' + (ui.dataTab === t.key ? ' fc-debugbar__subtab--active' : ''), t.label);
                b.type = 'button';
                b.setAttribute('role', 'tab');
                b.setAttribute('aria-selected', ui.dataTab === t.key ? 'true' : 'false');
                b.addEventListener('click', function () {
                    closeViewer();
                    ui.dataTab = t.key;
                    ui.confirmKey = null;
                    ui.dataShowAll = false;
                    persist();
                    renderActivePanel();
                });
                strip.appendChild(b);
            });
            panel.appendChild(strip);

            if (ui.dataTab === 'db') { return renderDatabase(panel); }
            if (ui.dataTab === 'php') { return renderPhpSession(panel); }
            return renderStoreTable(panel, ui.dataTab);
        },

        Log: function (panel) {
            var controls = el('div', 'fc-debugbar__logcontrols');
            var select = doc.createElement('select');
            select.className = 'fc-debugbar__select';
            ['all', 'info', 'warn', 'error'].forEach(function (lvl) {
                var opt = doc.createElement('option');
                opt.value = lvl;
                opt.textContent = lvl;
                if (ui.logFilter === lvl) { opt.selected = true; }
                select.appendChild(opt);
            });
            select.addEventListener('change', function () { ui.logFilter = select.value; renderActivePanel(); });
            controls.appendChild(select);
            var search = doc.createElement('input');
            search.type = 'search';
            search.placeholder = 'Search…';
            search.className = 'fc-debugbar__search';
            search.value = ui.logSearch;
            search.addEventListener('input', function () { ui.logSearch = search.value; renderLogList(list); });
            controls.appendChild(search);
            controls.appendChild(btn('Clear log', 'Clear captured log entries', function () {
                state.logs.clear();
                state.warnCount = 0;
                state.errorCount = 0;
                refresh();
            }));
            panel.appendChild(controls);

            var phpErrors = (state.server && state.server.phpErrors) || [];
            if (phpErrors.length) {
                panel.appendChild(details('PHP errors captured server-side this page load (' + phpErrors.length + ')', phpErrors));
            }

            var list = el('div', 'fc-debugbar__list');
            panel.appendChild(list);
            renderLogList(list);
        },

        Timing: function (panel) {
            var calcEntries = state.trace.items.filter(function (e) { return e.name === 'calculate_fences' && e.ms !== null; });
            var total = 0;
            calcEntries.forEach(function (e) { total += e.ms; });
            var reqEntries = state.requests.items;
            var reqTotal = 0;
            reqEntries.forEach(function (r) { if (r.ms) { reqTotal += r.ms; } });

            var rows = [
                ['Last calculation', state.lastCalc ? fmtMs(state.lastCalc.ms) : '—'],
                ['Calculation calls (top-level shown)', state.counters.calcCalls],
                ['Total calc time captured', fmtMs(total)],
                ['Recalculation runs', state.counters.calcRuns],
                ['Render passes', state.counters.renders],
                ['Requests captured', reqEntries.length + ' (' + fmtMs(reqTotal) + ' total)']
            ];
            panel.appendChild(el('h4', 'fc-debugbar__h', 'Frontend'));
            panel.appendChild(table(['Metric', 'Value'], rows));

            var t = state.server && state.server.timing;
            panel.appendChild(el('h4', 'fc-debugbar__h', 'PHP (this page load)'));
            if (!t) {
                empty(panel, 'No server timing island on this page.');
                return;
            }
            panel.appendChild(table(['Metric', 'Value'], [
                ['PHP time to footer', fmtMs(t.phpMsToFooter)],
                ['Peak memory', Math.round((t.memoryPeakBytes || 0) / 1048576 * 10) / 10 + ' MB'],
                ['DB queries', t.queryCount + (t.droppedQueries ? ' (' + t.droppedQueries + ' not logged)' : '')],
                ['DB time (timed queries)', fmtMs(t.queryMs)]
            ]));
            var queries = (state.server && state.server.queries) || [];
            if (queries.length) {
                var qWrap = el('div', 'fc-debugbar__list');
                queries.forEach(function (q) {
                    var line = el('div', 'fc-debugbar__traceline' + (q.error ? ' fc-debugbar__traceline--err' : ''));
                    var head = el('div', 'fc-debugbar__tracehead');
                    head.appendChild(el('span', 'fc-debugbar__tracename', q.kind));
                    head.appendChild(el('span', 'fc-debugbar__spacer'));
                    head.appendChild(el('span', 'fc-debugbar__tracems', fmtMs(q.ms)));
                    line.appendChild(head);
                    var sql = el('div', 'fc-debugbar__sql', q.sql);
                    line.appendChild(sql);
                    if (q.error) { line.appendChild(el('div', 'fc-debugbar__sqlerr', q.error)); }
                    qWrap.appendChild(line);
                });
                panel.appendChild(details('Query log (' + queries.length + ')', null));
                panel.lastChild.replaceChild(qWrap, panel.lastChild.lastChild);
            }
        },

        Requests: function (panel) {
            var items = state.requests.items;
            panel.appendChild(el('p', 'fc-debugbar__meta',
                items.length + ' captured' + (state.requests.dropped ? ' · ' + state.requests.dropped + ' dropped' : '') +
                ' — jQuery ajax + fetch, previews redacted'));
            if (!items.length) {
                empty(panel, 'No requests captured yet on this page.');
                return;
            }
            var list = el('div', 'fc-debugbar__list');
            items.slice(-MAX_LIST).reverse().forEach(function (r) {
                var line = el('div', 'fc-debugbar__traceline' + (r.status >= 400 || r.status === 0 ? ' fc-debugbar__traceline--err' : ''));
                var head = el('div', 'fc-debugbar__tracehead');
                head.appendChild(el('span', 'fc-debugbar__tracename', r.method + ' ' + r.url));
                head.appendChild(el('span', 'fc-debugbar__tracemeta', String(r.status)));
                head.appendChild(el('span', 'fc-debugbar__tracemeta', r.via));
                if (r.responseSize !== null && r.responseSize !== undefined) {
                    head.appendChild(el('span', 'fc-debugbar__tracemeta', r.responseSize + ' B'));
                }
                head.appendChild(el('span', 'fc-debugbar__spacer'));
                head.appendChild(el('span', 'fc-debugbar__tracems', fmtMs(r.ms)));
                line.appendChild(head);
                line.appendChild(details(fmtTime(r.at) + ' request / response preview', {
                    request: r.requestPreview,
                    response: r.responsePreview
                }, r.seq));
                list.appendChild(line);
            });
            panel.appendChild(list);
        },

        Environment: function (panel) {
            var env = (state.server && state.server.environment) || {};
            var rows = [
                ['App version (config)', env.appVersion || '—'],
                ['App version (branding)', env.brandingVersion || '—'],
                ['PHP', env.phpVersion || '(no server island)'],
                ['Debug Mode', env.debugMode ? 'ON (Settings → Console)' : 'on (client)'],
                ['Legacy app.debug flag', env.appDebugLegacy === undefined ? '—' : String(env.appDebugLegacy) + ' (false = error_reporting(0) on frontend pages)'],
                ['Planners table', (env.plannersTable || '—') + (env.demoSegment ? '  ← _demo via URL segment "' + env.demoSegment + '"' : '')],
                ['Page', page().label + (page().cls ? ' (body.' + page().cls + ')' : ' (unrecognised body class)')],
                ['Calculate control', calcTrigger() ? 'present — Re-run enabled' : 'none on this page — Re-run disabled'],
                ['window.FC_DEBUG', String(window.FC_DEBUG)],
                ['Browser', window.navigator.userAgent],
                ['Viewport', window.innerWidth + '×' + window.innerHeight],
                ['Platform', window.navigator.platform || '—']
            ];
            panel.appendChild(table(['Item', 'Value'], rows));
            panel.appendChild(el('p', 'fc-debugbar__note',
                'Server values come from the page-load island; AJAX responses are never modified, so their server timings are not collected (documented limitation).'));
        }
    };

    var MAX_LIST = 200;

    function renderLogList(list) {
        list.textContent = '';
        var q = ui.logSearch.toLowerCase();
        var items = state.logs.items.filter(function (entry) {
            if (ui.logFilter !== 'all' && entry.level !== ui.logFilter) { return false; }
            if (q && entry.message.toLowerCase().indexOf(q) === -1) { return false; }
            return true;
        });
        if (!items.length) {
            list.appendChild(el('p', 'fc-debugbar__empty', 'No log entries match.'));
            return;
        }
        items.slice(-MAX_LIST).reverse().forEach(function (entry) {
            var line = el('div', 'fc-debugbar__logline fc-debugbar__logline--' + entry.level);
            var head = el('div', 'fc-debugbar__tracehead');
            head.appendChild(el('span', 'fc-debugbar__loglevel', entry.level));
            head.appendChild(el('span', 'fc-debugbar__logmsg', entry.message));
            head.appendChild(el('span', 'fc-debugbar__spacer'));
            head.appendChild(el('span', 'fc-debugbar__tracems', fmtTime(entry.at)));
            line.appendChild(head);
            if (entry.context) { line.appendChild(details('context', entry.context, entry.seq)); }
            list.appendChild(line);
        });
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    // Copy/Snapshot must never ship the raw calc results the trace now holds by reference:
    // condense them the same way the panels do, so exports stay summarized and redacted.
    function exportableCalc(holder) {
        if (!holder) { return null; }
        return {
            ms: holder.ms,
            at: holder.at,
            tab: holder.tab,
            item: holder.item,
            summary: core.summarizeCalcEntry(holder)
        };
    }

    function exportableTrace(entries) {
        return entries.map(function (entry) {
            if (!entry.result) { return entry; }
            var copy = {};
            for (var k in entry) {
                if (Object.prototype.hasOwnProperty.call(entry, k) && k !== 'result' && k !== '_summary') {
                    copy[k] = entry[k];
                }
            }
            copy.output = core.summarizeCalcEntry(entry);
            return copy;
        });
    }

    function panelData() {
        switch (ui.tab) {
            case 'Inputs': return state.lastValidation;
            case 'Trace': return { current: exportableTrace(state.currentRun), previous: exportableTrace(state.prevRun), all: exportableTrace(state.trace.items) };
            case 'BOM': return core.readClientBom();
            case 'State': return { planner: core.readPlannerState(), session: state.server.session, lastCalc: exportableCalc(state.lastCalc) };
            case 'Data': return (function () {
                if (ui.dataTab === 'db') { return { database: state.server.database, queries: state.server.queries }; }
                if (ui.dataTab === 'php') { return state.server.session; }
                if (ui.dataTab === 'cookies') { return core.readCookies(); }
                return core.readStorage(ui.dataTab);
            })();
            case 'Log': return { logs: state.logs.items, phpErrors: state.server.phpErrors };
            case 'Timing': return { counters: state.counters, lastCalc: exportableCalc(state.lastCalc), server: state.server.timing, queries: state.server.queries };
            case 'Requests': return state.requests.items;
            case 'Environment': return state.server.environment;
            default: return null;
        }
    }

    function copyText(text) {
        try {
            if (window.navigator.clipboard && window.navigator.clipboard.writeText) {
                window.navigator.clipboard.writeText(text);
                return;
            }
        } catch (e) {}
        try {
            var ta = doc.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            doc.body.appendChild(ta);
            ta.select();
            doc.execCommand('copy');
            doc.body.removeChild(ta);
        } catch (e) {}
    }

    function copyActivePanel() {
        try {
            copyText(JSON.stringify({ panel: ui.tab, at: new Date().toISOString(), data: panelData() }, null, 2));
            window.debugbar.log('info', 'Copied ' + ui.tab + ' panel as JSON');
        } catch (e) {
            window.debugbar.log('error', 'Copy failed: ' + e.message);
        }
    }

    function downloadSnapshot() {
        try {
            var snap = {
                at: new Date().toISOString(),
                url: window.location.href,
                environment: state.server.environment,
                config: state.server.config,
                timing: { counters: state.counters, lastCalc: exportableCalc(state.lastCalc), server: state.server.timing },
                inputs: state.lastValidation,
                trace: exportableTrace(state.trace.items),
                bom: core.readClientBom(),
                plannerState: core.readPlannerState(),
                data: {
                    database: state.server.database,
                    localStorage: core.readStorage('local'),
                    sessionStorage: core.readStorage('session'),
                    cookies: core.readCookies()
                },
                session: state.server.session,
                logs: state.logs.items,
                phpErrors: state.server.phpErrors,
                queries: state.server.queries,
                requests: state.requests.items
            };
            var blob = new Blob([JSON.stringify(snap, null, 2)], { type: 'application/json' });
            var a = doc.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'fc-debug-snapshot-' + new Date().toISOString().replace(/[:.]/g, '-') + '.json';
            doc.body.appendChild(a);
            a.click();
            doc.body.removeChild(a);
            window.setTimeout(function () { URL.revokeObjectURL(a.href); }, 5000);
        } catch (e) {
            window.debugbar.log('error', 'Snapshot failed: ' + e.message);
        }
    }

    // ------------------------------------------------------------------
    // Keyboard: Esc collapses the bar. The bar itself has no hide key - it shows whenever
    // Debug Mode is on - and Esc never preventDefaults the app's own handlers.
    // ------------------------------------------------------------------

    function anyModalVisible() {
        var modals = doc.querySelectorAll('.fencing-modal');
        for (var i = 0; i < modals.length; i++) {
            if (window.getComputedStyle(modals[i]).display !== 'none') { return true; }
        }
        return false;
    }

    doc.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ui.viewer) {
            // The viewer is the innermost layer: Escape dismisses it, not the whole bar.
            e.stopPropagation();
            closeViewer();
            return;
        }
        if (e.key === 'Escape' && ui.open) {
            // Yield to the app's own Escape handling when a calculator modal is up,
            // unless focus is inside the bar itself.
            var focusInBar = ui.root && ui.root.contains(doc.activeElement);
            if (focusInBar || !anyModalVisible()) {
                setOpen(false);
            }
        }
    });

    // ------------------------------------------------------------------
    // Boot
    // ------------------------------------------------------------------

    function boot() {
        if (!doc.body) { return; }
        build();
        core.onChange(refresh);
        // Shrinking the window (or rotating a phone) must not leave a persisted tall bar
        // covering the viewport — applyLayout re-clamps against the new innerHeight.
        window.addEventListener('resize', function () {
            applyLayout();
        });
    }

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window);
