/* Project plan full screen: a card per section along the foot (the style's Step 1 image, number, style, length), to move between sections
   without leaving full screen. A pick clicks that section's own full-screen button, which step3-fullscreen.js turns into a move. */
(function fcPlanFilmstrip() {
    var list = document.getElementById('fc-fence-list');

    if (!list) {
        return;
    }

    var root = document.documentElement;
    var ON = 'fc-plan-filmstrip-on';

    var bar = null;
    var track = null;
    var tip = null;
    var prev = null;
    var next = null;
    var sections = [];
    var current = null;

    var CHEVRON = '<svg class="fc-plan-filmstrip__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7"/></svg>';

    function shown() {
        return root.classList.contains(ON);
    }

    function sectionsNow() {
        return Array.prototype.slice.call(list.querySelectorAll('.fc-project-plan-section'));
    }

    function sameSet(a, b) {
        return a.length === b.length && a.every(function(section, i) {
            return section === b[i];
        });
    }

    function styleSlug(i) {
        try {
            var tab = JSON.parse(localStorage.getItem('custom_fence-' + i) || 'null');
            var slug = tab && tab[0] ? String(tab[0].style || tab[0].fence || '') : '';

            return slug && typeof normalizeFenceStyleSlug === 'function' ? normalizeFenceStyleSlug(slug) : slug;
        } catch (e) {
            return '';
        }
    }

    /* The catalogue's feature image, as Step 1's style tile shows it; resolved like functions.js resolves catalogue images. */
    function styleImage(slug) {
        var fence = slug && typeof fc_data !== 'undefined' && fc_data ? fc_data[slug] : null;

        if (!fence || !fence.image) {
            return '';
        }
        try {
            return new URL(String(fence.image), document.baseURI).href;
        } catch (e) {
            return '';
        }
    }

    /* The section's colour swatch: a hex, a gradient or a url(), as the planner's colour tiles draw it. */
    function swatch(slug) {
        if (!slug || typeof fcFenceColor !== 'function' || typeof fcFenceColorCode !== 'function') {
            return '';
        }
        try {
            var colour = fcFenceColor(slug);

            return colour ? fcFenceColorCode(colour) : '';
        } catch (e) {
            return '';
        }
    }

    /* Read off the section's own head, so the card always says what the head says. */
    function details(section) {
        var i = parseInt(section.getAttribute('data-section-index'), 10);
        var styleEl = section.querySelector('.fc-project-plan-section-style');
        var colourEl = styleEl ? styleEl.querySelector('.fc-project-plan-section-style__color') : null;
        var overallEl = section.querySelector('.fc-project-plan-section-head__overall');
        var colour = colourEl ? colourEl.textContent : '';
        var style = styleEl ? styleEl.textContent.replace(colour, '') : '';
        var figure = overallEl ? /\d[\d,.]*/.exec(overallEl.textContent) : null;

        if (!isFinite(i)) {
            i = sections.indexOf(section);
        }
        var slug = styleSlug(i);

        return {
            n: i + 1,
            style: style.replace(/\s+/g, ' ').trim(),
            colour: colour.replace(/^\s*-\s*/, '').trim(),
            overall: figure ? figure[0] + ' mm' : (overallEl ? overallEl.textContent.trim() : ''),
            swatch: swatch(slug),
            image: styleImage(slug)
        };
    }

    function label(d) {
        return ['Section ' + d.n, d.style, d.colour, d.overall ? d.overall + ' overall' : ''].filter(Boolean).join(', ');
    }

    function thumbs() {
        return track ? Array.prototype.slice.call(track.children) : [];
    }

    function thumbFor(section) {
        return thumbs().filter(function(thumb) {
            return thumb.fcSection === section;
        })[0] || null;
    }

    function modalOpen() {
        return Array.prototype.some.call(document.querySelectorAll('.modal.show, .fc-cart-image-modal.is-open'), function(el) {
            return el.getClientRects().length > 0;
        });
    }

    /* ---- Details card ---- */

    function showTip(thumb) {
        var d = details(thumb.fcSection);
        var rows = tip.querySelectorAll('[data-k]');

        tip.querySelector('.fc-plan-filmstrip__tip-num').textContent = String(d.n);
        tip.classList.toggle('is-current', thumb.fcSection === current);
        Array.prototype.forEach.call(rows, function(dd) {
            var key = dd.getAttribute('data-k');

            dd.parentNode.hidden = !d[key];
            dd.querySelector('.fc-plan-filmstrip__tip-value').textContent = d[key];
        });

        var dot = tip.querySelector('.fc-plan-filmstrip__swatch');

        dot.hidden = !d.swatch;
        dot.style.background = d.swatch;

        var box = thumb.getBoundingClientRect();
        var width = tip.offsetWidth;
        var centre = box.left + box.width / 2;
        var left = Math.min(Math.max(8, centre - width / 2), window.innerWidth - width - 8);

        tip.style.left = Math.round(left) + 'px';
        tip.style.top = Math.round(box.top - tip.offsetHeight - 10) + 'px';
        tip.style.setProperty('--fc-film-arrow', Math.round(centre - left) + 'px');
        tip.classList.add('is-on');
    }

    function hideTip() {
        if (tip) {
            tip.classList.remove('is-on');
        }
    }

    /* ---- Moving ---- */

    function go(section) {
        var button = section ? section.querySelector('.js-fc-plan-fullscreen') : null;

        if (button && section !== current) {
            hideTip();
            button.click();
        }
    }

    function step(by) {
        go(sections[sections.indexOf(current) + by]);
    }

    /* The track scrolls itself, never the page: scrollIntoView would also scroll the hidden-overflow page behind full screen. */
    function bringIntoView(thumb, smooth) {
        var left = thumb.offsetLeft - (track.clientWidth - thumb.offsetWidth) / 2;

        track.scrollTo({ left: Math.max(0, left), behavior: smooth ? 'smooth' : 'auto' });
    }

    function markActive(smooth) {
        var at = sections.indexOf(current);

        thumbs().forEach(function(thumb) {
            var on = thumb.fcSection === current;

            thumb.classList.toggle('is-active', on);
            if (on) {
                thumb.setAttribute('aria-current', 'true');
            } else {
                thumb.removeAttribute('aria-current');
            }
        });
        prev.disabled = at <= 0;
        next.disabled = at < 0 || at >= sections.length - 1;

        var active = thumbFor(current);

        if (active) {
            window.requestAnimationFrame(function() {
                bringIntoView(active, smooth && !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches));
            });
        }
    }

    /* A missing or broken image leaves the frame's own placeholder. */
    function addImage(frame, src) {
        if (!src) {
            frame.classList.replace('is-loading', 'is-empty');
            return;
        }
        var img = new Image();

        img.className = 'fc-plan-filmstrip__img';
        img.alt = '';
        img.decoding = 'async';
        img.onload = function() {
            frame.classList.remove('is-loading');
        };
        img.onerror = function() {
            img.remove();
            frame.classList.replace('is-loading', 'is-empty');
        };
        img.src = src;
        frame.appendChild(img);
    }

    function build() {
        if (!bar) {
            bar = document.createElement('nav');
            bar.className = 'fc-plan-filmstrip';
            bar.setAttribute('aria-label', 'Sections');
            bar.innerHTML =
                '<button type="button" class="fc-plan-filmstrip__step" data-step="-1" aria-label="Previous section" title="Previous section (Page Up)">' + CHEVRON + '</button>' +
                '<div class="fc-plan-filmstrip__track"></div>' +
                '<button type="button" class="fc-plan-filmstrip__step fc-plan-filmstrip__step--next" data-step="1" aria-label="Next section" title="Next section (Page Down)">' + CHEVRON + '</button>' +
                '<div class="fc-plan-filmstrip__tip" aria-hidden="true">' +
                    '<div class="fc-plan-filmstrip__tip-head"><span class="fc-plan-filmstrip__tip-title">Section <span class="fc-plan-filmstrip__tip-num"></span></span><span class="fc-plan-filmstrip__tip-now">Viewing</span></div>' +
                    '<dl class="fc-plan-filmstrip__tip-rows">' +
                        '<div><dt>Style</dt><dd data-k="style"><span class="fc-plan-filmstrip__tip-value"></span></dd></div>' +
                        '<div><dt>Colour</dt><dd data-k="colour"><span class="fc-plan-filmstrip__swatch"></span><span class="fc-plan-filmstrip__tip-value"></span></dd></div>' +
                        '<div><dt>Overall</dt><dd data-k="overall"><span class="fc-plan-filmstrip__tip-value"></span></dd></div>' +
                    '</dl>' +
                '</div>';
            document.body.appendChild(bar);

            track = bar.querySelector('.fc-plan-filmstrip__track');
            tip = bar.querySelector('.fc-plan-filmstrip__tip');
            prev = bar.querySelector('[data-step="-1"]');
            next = bar.querySelector('[data-step="1"]');

            prev.addEventListener('click', function() {
                step(-1);
            });
            next.addEventListener('click', function() {
                step(1);
            });
            track.addEventListener('click', function(e) {
                var thumb = e.target.closest('.fc-plan-filmstrip__thumb');

                if (thumb) {
                    go(thumb.fcSection);
                }
            });
            /* Keyboard focus shows the card; a tap's focus does not, or a phone would flash it on every pick. */
            track.addEventListener('focusin', function(e) {
                var thumb = e.target.closest('.fc-plan-filmstrip__thumb');
                var keyboard = false;

                try {
                    keyboard = thumb && thumb.matches(':focus-visible');
                } catch (err) {}
                if (keyboard) {
                    showTip(thumb);
                }
            });
            track.addEventListener('focusout', hideTip);
            track.addEventListener('scroll', hideTip, { passive: true });
        }

        track.innerHTML = '';
        sections.forEach(function(section) {
            var d = details(section);
            var thumb = document.createElement('button');

            thumb.type = 'button';
            thumb.className = 'fc-plan-filmstrip__thumb';
            thumb.setAttribute('aria-label', label(d));
            thumb.innerHTML =
                '<span class="fc-plan-filmstrip__frame is-loading"></span>' +
                '<span class="fc-plan-filmstrip__text">' +
                    '<span class="fc-plan-filmstrip__cap"><span class="fc-plan-filmstrip__num"></span><span class="fc-plan-filmstrip__style"></span></span>' +
                    '<span class="fc-plan-filmstrip__len"></span>' +
                '</span>';
            thumb.querySelector('.fc-plan-filmstrip__num').textContent = String(d.n);
            thumb.querySelector('.fc-plan-filmstrip__style').textContent = d.style;
            thumb.querySelector('.fc-plan-filmstrip__len').textContent = d.overall;
            addImage(thumb.querySelector('.fc-plan-filmstrip__frame'), d.image);
            thumb.fcSection = section;
            thumb.addEventListener('mouseenter', function() {
                showTip(thumb);
            });
            thumb.addEventListener('mouseleave', hideTip);
            track.appendChild(thumb);
        });
    }

    document.addEventListener('fc-plan-fullscreen', function(e) {
        var section = e.detail ? e.detail.section : null;
        var now = sectionsNow();
        var first = !shown();

        current = section;
        hideTip();

        /* One section has nowhere to move to. */
        if (!section || now.length < 2) {
            root.classList.remove(ON);
            return;
        }
        if (!bar || !sameSet(now, sections)) {
            sections = now;
            build();
        }
        root.classList.add(ON);
        markActive(!first);
    });

    window.addEventListener('resize', function() {
        if (!shown()) {
            return;
        }
        hideTip();
        markActive(false);
    });

    /* Page Up / Page Down, as a presentation clicker sends them; ← / → stay with the drawing's own scroll. */
    document.addEventListener('keydown', function(e) {
        var by = e.key === 'PageDown' ? 1 : (e.key === 'PageUp' ? -1 : 0);

        if (!by || !shown() || e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey || modalOpen()) {
            return;
        }
        if (e.target && e.target.closest && e.target.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"])')) {
            return;
        }
        e.preventDefault();
        if (!e.repeat) {
            step(by);
        }
    });
})();
