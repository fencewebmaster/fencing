/**
 * Planner Ctrl shortcuts: each presses the planner's own button for its action, so every rule the
 * button carries still holds — the Reset and Delete confirm dialogs (events.js gates those clicks
 * in the capture phase and re-fires them once confirmed), the gate-only locks on the Step 3 option
 * buttons, and which options a fence style has at all. A shortcut whose button is not on screen or
 * is disabled does nothing and leaves the key to the browser, so Ctrl+R still reloads when there is
 * no right side to edit.
 *
 * Ctrl on every platform, the Mac included: browsers there keep their own shortcuts on ⌘ and leave
 * Ctrl+letter free. Letters are read from e.key, so AZERTY and QWERTZ users press the letter printed
 * on the key; e.code stands in on non-Latin layouts, where e.key is not a Latin letter. Shift now
 * rules a combo out, so Ctrl+Shift+P is left to Firefox's private window. Windows
 * reports AltGr as Ctrl+Alt, so the four Ctrl+Alt combos below intercept AltGr+letter.
 *
 * Listed in #fc-shortcuts-modal (planner/modals.php); keep the two in step. Planner only.
 */
(function fcPlannerShortcuts() {
    if (!document.body || !document.body.classList.contains('fc-planner-page')) {
        return;
    }

    /* Per combo, where to look for its button, in order; the first match that is on screen and
       enabled is pressed. Step 3's copies come first, and Step 1 carries its own Delete and Reset
       (but not the style card's ×, which is also a .fc-fence-reset-all). Glass pool labels its
       post_options button "Spigot Options", so that button answers Ctrl+Alt+S there and
       Ctrl+Alt+P only where it really is Post Options. */
    var SPIGOT = /spigot/i;

    var SHORTCUTS = {
        'a': ['[data-section="3"] .fencing-tab-add', '.fencing-tab-add'],
        'alt+d': ['[data-section="3"] .js-btn-delete-fence', '.js-btn-delete-fence'],
        'alt+r': ['[data-section="3"] .fc-fence-reset-all', '.fc-fence-reset-all:not(.js-fencing-style-btn)'],
        'l': ['#btn-left_side'],
        'g': ['#btn-gate'],
        's': ['#btn-edit_spacing'],
        'alt+s': ['#btn-spigot_options', { selector: '#btn-post_options', label: SPIGOT }],
        'p': ['#btn-panel_options', '#btn-panel_options_custom'],
        'alt+p': [{ selector: '#btn-post_options', notLabel: SPIGOT }],
        'r': ['#btn-right_side'],
        'm': ['#btn-planner-summary']
    };

    /* Where Ctrl+A is select-all, and has to stay that. */
    var TYPING = 'input, textarea, select, [contenteditable]:not([contenteditable="false"])';

    function modalOpen() {
        return Array.prototype.some.call(document.querySelectorAll('.fencing-modal, .modal.show'), function(el) {
            return el.getClientRects().length > 0;
        });
    }

    /* On screen (no display: none on it or above it) and not switched off in any of the ways the
       planner switches a button off. */
    function usable(el) {
        return !el.disabled &&
            !el.classList.contains('disabled') &&
            !el.classList.contains('fc-panel-control--gate-only-disabled') &&
            el.getAttribute('aria-disabled') !== 'true' &&
            el.getClientRects().length > 0;
    }

    function find(targets) {
        for (var i = 0; i < targets.length; i++) {
            var target = typeof targets[i] === 'string' ? { selector: targets[i] } : targets[i];
            var matches = document.querySelectorAll(target.selector);

            for (var j = 0; j < matches.length; j++) {
                var text = matches[j].textContent || '';

                if ((target.label && !target.label.test(text)) || (target.notLabel && target.notLabel.test(text))) {
                    continue;
                }
                if (usable(matches[j])) {
                    return matches[j];
                }
            }
        }
        return null;
    }

    function letterOf(e) {
        if (/^[a-z]$/i.test(e.key || '')) {
            return e.key.toLowerCase();
        }
        return /^Key[A-Z]$/.test(e.code || '') ? e.code.charAt(3).toLowerCase() : '';
    }

    document.addEventListener('keydown', function(e) {
        /* A held combo auto-repeats: thirty new sections a second from one Ctrl+A. */
        if (!e.ctrlKey || e.shiftKey || e.metaKey || e.repeat || e.isComposing || e.defaultPrevented) {
            return;
        }

        var letter = letterOf(e);
        var combo = (e.altKey ? 'alt+' : '') + letter;

        if (!letter || !Object.prototype.hasOwnProperty.call(SHORTCUTS, combo)) {
            return;
        }

        if (combo === 'a' && e.target && e.target.closest && e.target.closest(TYPING)) {
            return;
        }

        /* A dialog's own keys come first, and an open Select2 list would stay open behind the next one. */
        if (modalOpen() || document.querySelector('.select2-container--open')) {
            return;
        }

        var button = find(SHORTCUTS[combo]);

        if (!button) {
            return;
        }

        e.preventDefault();
        button.click();
    });
})();
