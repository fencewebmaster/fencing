// Post Options swatches: each data/post-finishes.js entry drawn as SVG from its pattern and colours. In-page
// swatches share one hidden <defs>; data: URIs for CSS backgrounds carry their own copy of the defs they use.
(function(global) {
    'use strict';

    var DEFS_ID = 'fc-post-finish-defs';

    //----------------------------------------------------------------------------------
    // Numbers and colour

    // Seeded, so a swatch lays its stones out the same way on every render.
    function hashString(str) {
        var h = 2166136261;
        for (var i = 0; i < str.length; i++) {
            h ^= str.charCodeAt(i);
            h = Math.imul(h, 16777619);
        }
        return h >>> 0;
    }

    function makeRng(seed) {
        var a = seed >>> 0;
        return function() {
            a = (a + 0x6D2B79F5) | 0;
            var t = Math.imul(a ^ (a >>> 15), 1 | a);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }

    function n(v) {
        return Math.round(v * 10) / 10;
    }

    function hexToRgb(hex) {
        var m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(String(hex || '').trim());
        if (!m) {
            return [128, 128, 128];
        }
        var h = m[1].length === 3 ? m[1].replace(/./g, '$&$&') : m[1];
        return [0, 2, 4].map(function(i) {
            return parseInt(h.substr(i, 2), 16);
        });
    }

    function rgbToHex(rgb) {
        return '#' + rgb.map(function(c) {
            var v = Math.max(0, Math.min(255, Math.round(c)));
            return (v < 16 ? '0' : '') + v.toString(16);
        }).join('');
    }

    function mix(a, b, t) {
        var x = hexToRgb(a);
        var y = hexToRgb(b);
        return rgbToHex([0, 1, 2].map(function(i) {
            return x[i] + (y[i] - x[i]) * t;
        }));
    }

    // Positive lightens towards white, negative darkens towards black.
    function shade(hex, amt) {
        return amt >= 0 ? mix(hex, '#ffffff', amt) : mix(hex, '#000000', -amt);
    }

    //----------------------------------------------------------------------------------
    // Shared defs: colourless noise masks and light/shade gradients

    // A noise mask paints its shape's own fill where alpha = k * noise + b is above zero.
    var NOISE = {
        grain: { freq: '0.9', oct: 2, k: 1.8, b: -0.6, seed: 2 },
        fine: { freq: '1.5', oct: 1, k: 2.4, b: -0.9, seed: 5 },
        sand: { freq: '1.1', oct: 1, k: 9, b: -5.6, seed: 7 },
        speck: { freq: '0.5', oct: 1, k: 10, b: -6.4, seed: 11 },
        dense: { freq: '1.2', oct: 1, k: 5, b: -2.2, seed: 13 },
        mottle: { freq: '0.07', oct: 3, k: 2.4, b: -0.95, seed: 3 },
        cloud: { freq: '0.028', oct: 3, k: 2.2, b: -0.85, seed: 17 },
        blotch: { freq: '0.05', oct: 2, k: 6, b: -3.1, seed: 19 },
        grainV: { freq: '0.3 0.014', oct: 2, k: 3.2, b: -1.35, seed: 23 },
        grainV2: { freq: '0.22 0.02', oct: 2, k: 3.4, b: -1.5, seed: 61 },
        grainH: { freq: '0.014 0.3', oct: 2, k: 3.2, b: -1.35, seed: 29 },
        brushV: { freq: '1.3 0.006', oct: 1, k: 2.6, b: -0.95, seed: 31 },
        brushH: { freq: '0.006 1.3', oct: 1, k: 2.6, b: -0.95, seed: 37 },
        streakV: { freq: '0.08 0.005', oct: 2, k: 4, b: -1.9, seed: 41 },
        pits: { freq: '0.4', oct: 1, k: 14, b: -9.6, seed: 43 }
    };

    // Objectbox gradients in white/black alpha, so one copy shades any colour.
    var GRADIENTS = {
        sheenV: ['x', [[0, '#fff', 0], [0.28, '#fff', 0.38], [0.5, '#fff', 0], [0.78, '#000', 0.08], [1, '#000', 0.16]]],
        sheenD: ['d', [[0, '#fff', 0.55], [0.42, '#fff', 0], [1, '#000', 0.14]]],
        cyl: ['x', [[0, '#000', 0.42], [0.3, '#fff', 0.22], [0.45, '#fff', 0.05], [0.8, '#000', 0.22], [1, '#000', 0.5]]],
        rib: ['x', [[0, '#fff', 0.38], [0.45, '#fff', 0.04], [0.8, '#000', 0.18], [1, '#000', 0.4]]],
        topLight: ['y', [[0, '#fff', 0.3], [0.35, '#fff', 0], [1, '#000', 0.22]]],
        convex: ['d', [[0, '#fff', 0.4], [0.5, '#fff', 0], [1, '#000', 0.35]]],
        concave: ['d', [[0, '#000', 0.32], [0.5, '#000', 0], [1, '#fff', 0.36]]],
        mirror: ['d', [[0, '#fff', 0.9], [0.18, '#000', 0.4], [0.34, '#fff', 0.75], [0.52, '#000', 0.55], [0.7, '#fff', 0.6], [0.86, '#000', 0.3], [1, '#fff', 0.5]]],
        iris: ['d', [[0, '#ffd6ec', 0.35], [0.35, '#fff', 0], [0.6, '#d2f2ff', 0.3], [1, '#fff2c8', 0.3]]],
        dome: ['r', [[0, '#fff', 0.55], [0.45, '#fff', 0], [1, '#000', 0.35]]]
    };

    var BLURS = { blur05: 0.5, blur12: 1.2 };

    function defMarkup(name) {
        var id = 'fcpf-' + name;
        if (NOISE[name]) {
            var s = NOISE[name];
            return '<filter id="' + id + '" x="0" y="0" width="100%" height="100%" color-interpolation-filters="sRGB">' +
                '<feTurbulence type="fractalNoise" baseFrequency="' + s.freq + '" numOctaves="' + s.oct + '" seed="' + s.seed + '" result="n"/>' +
                '<feColorMatrix in="n" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 ' + s.k + ' 0 0 0 ' + s.b + '" result="a"/>' +
                '<feComposite in="SourceGraphic" in2="a" operator="in"/></filter>';
        }
        if (BLURS[name]) {
            // A fixed user-space region: a thin vein's own box is narrower than its stroke and clipped the blur.
            return '<filter id="' + id + '" filterUnits="userSpaceOnUse" x="-20" y="-20" width="320" height="320"><feGaussianBlur stdDeviation="' + BLURS[name] + '"/></filter>';
        }
        var g = GRADIENTS[name];
        if (!g) {
            return '';
        }
        var stops = g[1].map(function(st) {
            return '<stop offset="' + st[0] + '" stop-color="' + st[1] + '" stop-opacity="' + st[2] + '"/>';
        }).join('');
        if (g[0] === 'r') {
            return '<radialGradient id="' + id + '" cx="0.36" cy="0.32" r="0.75">' + stops + '</radialGradient>';
        }
        var dir = { x: 'x1="0" y1="0" x2="1" y2="0"', y: 'x1="0" y1="0" x2="0" y2="1"', d: 'x1="0" y1="0" x2="1" y2="1"' }[g[0]];
        return '<linearGradient id="' + id + '" ' + dir + '>' + stops + '</linearGradient>';
    }

    function allDefNames() {
        return Object.keys(NOISE).concat(Object.keys(GRADIENTS), Object.keys(BLURS));
    }

    /** Puts the shared defs in the page once; in-page swatches point at them by id. */
    function ensureDefs() {
        if (typeof document === 'undefined' || document.getElementById(DEFS_ID)) {
            return;
        }
        var holder = document.createElement('div');
        // Not display:none: a filter inside an undisplayed SVG stops rendering in some browsers.
        holder.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" id="' + DEFS_ID + '" aria-hidden="true" focusable="false" style="position:absolute;width:0;height:0;overflow:hidden"><defs>' +
            allDefNames().map(defMarkup).join('') + '</defs></svg>';
        document.body.appendChild(holder.firstChild);
    }

    //----------------------------------------------------------------------------------
    // Drawing context

    function Ctx(entry, w, h) {
        var c = (entry && entry.colors) || {};
        this.W = w;
        this.H = h;
        this.base = c.base || '#9a9a9a';
        this.accent = c.accent || shade(this.base, -0.25);
        this.joint = c.joint || shade(this.base, -0.45);
        this.finish = String((entry && entry.finish) || '');
        this.r = makeRng(hashString(String((entry && entry.id) || (entry && entry.pattern) || 'x')));
        this.used = {};
        this.out = [];
    }

    Ctx.prototype.url = function(name) {
        this.used[name] = true;
        return 'url(#fcpf-' + name + ')';
    };
    Ctx.prototype.rand = function(a, b) {
        return a + this.r() * (b - a);
    };
    Ctx.prototype.pick = function(list) {
        return list[Math.floor(this.r() * list.length) % list.length];
    };
    Ctx.prototype.tone = function(spread) {
        return mix(this.base, this.accent, this.r() * (spread == null ? 0.5 : spread));
    };
    Ctx.prototype.add = function(markup) {
        this.out.push(markup);
    };
    Ctx.prototype.rect = function(x, y, w, h, fill, extra) {
        this.add('<rect x="' + n(x) + '" y="' + n(y) + '" width="' + n(w) + '" height="' + n(h) + '" fill="' + fill + '"' + (extra ? ' ' + extra : '') + '/>');
    };
    Ctx.prototype.fill = function(color) {
        this.rect(0, 0, this.W, this.H, color);
    };
    // A noise mask over a box (the whole swatch by default), painted in `color`.
    Ctx.prototype.noise = function(name, color, opacity, box) {
        var b = box || [0, 0, this.W, this.H];
        this.rect(b[0], b[1], b[2], b[3], color, 'filter="' + this.url(name) + '" opacity="' + opacity + '"');
    };
    Ctx.prototype.shadeBox = function(name, opacity, box, extra) {
        var b = box || [0, 0, this.W, this.H];
        this.rect(b[0], b[1], b[2], b[3], this.url(name), 'opacity="' + opacity + '"' + (extra ? ' ' + extra : ''));
    };
    // One filter pass over many pieces instead of one per piece: the turbulence sits in user space, so
    // the grain is identical, and N tiny filter regions cost far more at paint time than a single one.
    Ctx.prototype.filterGroup = function(name, opacity, draw) {
        this.add('<g filter="' + this.url(name) + '" opacity="' + opacity + '">');
        draw();
        this.add('</g>');
    };
    Ctx.prototype.shape = function(d, fill, extra) {
        this.add('<path d="' + d + '" fill="' + fill + '"' + (extra ? ' ' + extra : '') + '/>');
    };
    Ctx.prototype.line = function(x1, y1, x2, y2, stroke, width, opacity) {
        this.add('<line x1="' + n(x1) + '" y1="' + n(y1) + '" x2="' + n(x2) + '" y2="' + n(y2) + '" stroke="' + stroke + '" stroke-width="' + width + '" opacity="' + opacity + '"/>');
    };
    Ctx.prototype.ellipse = function(cx, cy, rx, ry, fill, extra) {
        this.add('<ellipse cx="' + n(cx) + '" cy="' + n(cy) + '" rx="' + n(rx) + '" ry="' + n(ry) + '" fill="' + fill + '"' + (extra ? ' ' + extra : '') + '/>');
    };

    //----------------------------------------------------------------------------------
    // Geometry helpers

    function polyPath(pts) {
        return 'M' + pts.map(function(p) {
            return n(p[0]) + ' ' + n(p[1]);
        }).join('L') + 'Z';
    }

    // Rounds a polygon's corners by curving through its edge midpoints.
    function blobPath(pts) {
        var mid = function(a, b) {
            return [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2];
        };
        var len = pts.length;
        var start = mid(pts[len - 1], pts[0]);
        var d = 'M' + n(start[0]) + ' ' + n(start[1]);
        for (var i = 0; i < len; i++) {
            var m = mid(pts[i], pts[(i + 1) % len]);
            d += 'Q' + n(pts[i][0]) + ' ' + n(pts[i][1]) + ' ' + n(m[0]) + ' ' + n(m[1]);
        }
        return d + 'Z';
    }

    // An irregular stone inside a box: corners and edge midpoints pulled in by up to `j`.
    function stonePoints(ctx, x, y, w, h, j) {
        var r = function() {
            return ctx.rand(0, j);
        };
        return [
            [x + r(), y + r()], [x + w / 2 + ctx.rand(-j, j), y + r() * 0.6],
            [x + w - r(), y + r()], [x + w - r() * 0.6, y + h / 2 + ctx.rand(-j, j)],
            [x + w - r(), y + h - r()], [x + w / 2 + ctx.rand(-j, j), y + h - r() * 0.6],
            [x + r(), y + h - r()], [x + r() * 0.6, y + h / 2 + ctx.rand(-j, j)]
        ];
    }

    // Courses of stones: each row its own height, each stone its own width.
    function courses(ctx, o) {
        var out = [];
        var y = -ctx.rand(0, o.hMin * 0.5);
        while (y < ctx.H) {
            var h = ctx.rand(o.hMin, o.hMax);
            var x = -ctx.rand(0, o.wMax * 0.6);
            while (x < ctx.W) {
                var w = ctx.rand(o.wMin, o.wMax);
                out.push({ x: x, y: y, w: w, h: h });
                x += w + o.gap;
            }
            y += h + o.gap;
        }
        return out;
    }

    // A jittered grid cut into irregular cells, each shrunk by half the joint (crazy paving).
    function cells(ctx, size, jitter, gap, split) {
        var nx = Math.ceil(ctx.W / size) + 2;
        var ny = Math.ceil(ctx.H / size) + 2;
        var p = [];
        for (var i = 0; i <= nx; i++) {
            p[i] = [];
            for (var j = 0; j <= ny; j++) {
                p[i][j] = [(i - 1) * size + ctx.rand(-jitter, jitter), (j - 1) * size + ctx.rand(-jitter, jitter)];
            }
        }
        var out = [];
        for (i = 0; i < nx; i++) {
            for (j = 0; j < ny; j++) {
                var quad = [p[i][j], p[i + 1][j], p[i + 1][j + 1], p[i][j + 1]];
                var parts = split && ctx.r() < split
                    ? (ctx.r() < 0.5 ? [[quad[0], quad[1], quad[2]], [quad[0], quad[2], quad[3]]] : [[quad[0], quad[1], quad[3]], [quad[1], quad[2], quad[3]]])
                    : [quad];
                parts.forEach(function(poly) {
                    out.push(shrink(poly, gap / 2));
                });
            }
        }
        return out;
    }

    function shrink(poly, d) {
        var cx = 0;
        var cy = 0;
        poly.forEach(function(q) {
            cx += q[0];
            cy += q[1];
        });
        cx /= poly.length;
        cy /= poly.length;
        return poly.map(function(q) {
            var dx = cx - q[0];
            var dy = cy - q[1];
            var len = Math.sqrt(dx * dx + dy * dy) || 1;
            var k = Math.min(d, len * 0.45) / len;
            return [q[0] + dx * k, q[1] + dy * k];
        });
    }

    // Loosely packed discs (rejection sampling), largest first.
    function discs(ctx, count, rMin, rMax, overlap) {
        var out = [];
        var tries = count * 25;
        while (out.length < count && tries-- > 0) {
            var r = ctx.rand(rMin, rMax);
            var x = ctx.rand(-r * 0.5, ctx.W + r * 0.5);
            var y = ctx.rand(-r * 0.5, ctx.H + r * 0.5);
            var ok = out.every(function(d) {
                var dx = d.x - x;
                var dy = d.y - y;
                return Math.sqrt(dx * dx + dy * dy) > (d.r + r) * (1 - (overlap || 0));
            });
            if (ok) {
                out.push({ x: x, y: y, r: r });
            }
        }
        return out;
    }

    // A thin wavy vertical line through the whole height (wood grain, marble veins).
    function wavePath(ctx, x, amp, steps, lean) {
        var d = 'M' + n(x) + ' -2';
        var seg = (ctx.H + 4) / steps;
        for (var i = 1; i <= steps; i++) {
            var y = -2 + i * seg;
            var nx = x + (lean || 0) * i + ctx.rand(-amp, amp);
            d += 'S' + n(nx + ctx.rand(-amp, amp)) + ' ' + n(y - seg / 2) + ' ' + n(nx) + ' ' + n(y);
        }
        return d;
    }

    //----------------------------------------------------------------------------------
    // Pattern families

    function concrete(ctx, o) {
        o = o || {};
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, o.mottle == null ? 0.55 : o.mottle);
        ctx.noise('grain', ctx.accent, o.grain == null ? 0.4 : o.grain);
        ctx.noise('sand', shade(ctx.accent, -0.35), o.pores == null ? 0.35 : o.pores);
    }

    function plasterFamily(ctx, kind) {
        ctx.fill(ctx.base);
        if (kind === 'plaster') {
            ctx.noise('cloud', ctx.accent, 0.45);
            ctx.noise('fine', ctx.accent, 0.18);
            ctx.shadeBox('sheenD', 0.15);
            return;
        }
        if (kind === 'sand-float') {
            ctx.noise('cloud', ctx.accent, 0.25);
            ctx.noise('dense', ctx.accent, 0.75);
            ctx.noise('sand', shade(ctx.base, 0.5), 0.6);
            return;
        }
        if (kind === 'microcement') {
            ctx.noise('cloud', ctx.accent, 0.6);
            ctx.noise('mottle', shade(ctx.base, 0.2), 0.35);
            for (var i = 0; i < 6; i++) {
                var x = ctx.rand(-20, ctx.W);
                var y = ctx.rand(0, ctx.H);
                ctx.shape('M' + n(x) + ' ' + n(y) + 'q' + n(ctx.rand(15, 30)) + ' ' + n(ctx.rand(-12, -4)) + ' ' + n(ctx.rand(40, 60)) + ' ' + n(ctx.rand(-2, 6)), 'none', 'stroke="' + ctx.accent + '" stroke-width="' + n(ctx.rand(2, 5)) + '" opacity="0.18" filter="' + ctx.url('blur12') + '"');
            }
            ctx.noise('fine', ctx.accent, 0.12);
            return;
        }
        if (kind === 'venetian') {
            ctx.noise('cloud', ctx.accent, 0.75);
            ctx.noise('mottle', ctx.joint, 0.55);
            var vSteps = Math.max(4, Math.round(ctx.H / 30));
            for (var v = 0; v < 5; v++) {
                ctx.shape(wavePath(ctx, ctx.rand(0, ctx.W), 6, vSteps, ctx.rand(-16, 16) / vSteps), 'none', 'stroke="' + shade(ctx.accent, -0.2) + '" stroke-width="' + n(ctx.rand(0.6, 1.6)) + '" opacity="0.35" filter="' + ctx.url('blur12') + '"');
            }
            ctx.shadeBox(isPostBox(ctx) ? 'sheenV' : 'sheenD', 0.4);
            return;
        }
        if (kind === 'skip-trowel' || kind === 'knockdown') {
            ctx.noise('fine', ctx.accent, 0.2);
            var blobs = kind === 'skip-trowel' ? 16 : 34;
            for (var b = 0; b < blobs; b++) {
                var cx = ctx.rand(0, ctx.W);
                var cy = ctx.rand(0, ctx.H);
                var s = kind === 'skip-trowel' ? ctx.rand(5, 11) : ctx.rand(2.5, 6);
                var pts = [];
                for (var a = 0; a < 7; a++) {
                    var ang = a / 7 * Math.PI * 2;
                    var rr = s * ctx.rand(0.55, 1.1);
                    pts.push([cx + Math.cos(ang) * rr * 1.35, cy + Math.sin(ang) * rr * 0.8]);
                }
                var d = blobPath(pts);
                // Raised plaster: shadow under and right, lit face on top.
                ctx.shape(d, shade(ctx.accent, -0.12), 'opacity="0.55" transform="translate(0.8 1)"');
                ctx.shape(d, kind === 'skip-trowel' ? shade(ctx.base, 0.08) : shade(ctx.base, 0.12));
            }
        }
    }

    // A painted wall: the colour itself, with a faint roller stipple and a low sheen so it reads as paint.
    function paint(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('cloud', ctx.accent, 0.3);
        ctx.noise('fine', ctx.accent, ctx.finish === 'gloss' ? 0.2 : 0.4);
        ctx.noise('grain', shade(ctx.base, 0.25), 0.18);
        // The finish sets the sheen: matt barely catches the light, gloss carries a clear highlight.
        ctx.shadeBox('sheenD', { matt: 0.05, satin: 0.2, gloss: 0.4 }[ctx.finish] || 0.14);
        if (ctx.finish === 'gloss' && isPostBox(ctx)) {
            ctx.shadeBox('sheenV', 0.45);
        }
    }

    function aggregate(ctx, kind) {
        ctx.fill(kind === 'pebble' ? shade(ctx.accent, -0.2) : ctx.base);
        ctx.noise('grain', ctx.accent, 0.4);
        var big = kind === 'pebble';
        var dash = kind === 'pebble-dash';
        var list = discs(ctx, big ? 70 : dash ? 190 : 150, big ? 3 : dash ? 0.9 : 1.4, big ? 6.2 : dash ? 2 : 3.6, big ? 0.05 : 0.2);
        var palette = [ctx.accent, ctx.joint, shade(ctx.base, -0.15), mix(ctx.accent, ctx.joint, 0.5), shade(ctx.joint, -0.12)];
        list.forEach(function(d) {
            var rx = d.r * ctx.rand(0.75, 1.1);
            var ry = d.r * ctx.rand(0.6, 0.95);
            var rot = 'transform="rotate(' + n(ctx.rand(0, 180)) + ' ' + n(d.x) + ' ' + n(d.y) + ')"';
            ctx.ellipse(d.x + 0.5, d.y + 0.6, rx, ry, '#000', 'opacity="0.25" ' + rot);
            ctx.ellipse(d.x, d.y, rx, ry, ctx.pick(palette), rot);
            ctx.ellipse(d.x, d.y, rx, ry, ctx.url('dome'), 'opacity="' + (big ? 0.8 : 0.55) + '" ' + rot);
        });
        if (dash) {
            ctx.noise('dense', ctx.base, 0.25);
        }
    }

    function boardFormed(ctx) {
        var y = -ctx.rand(0, 6);
        while (y < ctx.H) {
            var h = ctx.rand(9, 14);
            ctx.rect(0, y, ctx.W, h, mix(ctx.base, ctx.accent, ctx.rand(0, 0.8)));
            ctx.noise('grainH', ctx.accent, 0.65, [0, y, ctx.W, h]);
            // Each board's end joint, staggered from course to course like real formwork.
            var ex = ctx.rand(10, ctx.W - 10);
            ctx.line(ex, y, ex, y + h, ctx.joint, 0.8, 0.6);
            ctx.line(0, y + h, ctx.W, y + h, ctx.joint, 1.1, 0.75);
            ctx.line(0, y + h + 1, ctx.W, y + h + 1, '#fff', 0.6, 0.25);
            y += h;
        }
        ctx.noise('mottle', ctx.accent, 0.3);
        ctx.noise('sand', ctx.joint, 0.3);
    }

    function formTie(ctx) {
        concrete(ctx, { mottle: 0.5 });
        var post = isPostBox(ctx);
        var step = post ? 82 : 26;
        for (var y = 0; y < ctx.H + step; y += step) {
            ctx.line(0, y + step / 2, ctx.W, y + step / 2, ctx.joint, 0.6, 0.3);
        }
        if (!post) {
            for (var x = 0; x < ctx.W + 40; x += 40) {
                ctx.line(x + 30, 0, x + 30, ctx.H, ctx.joint, 0.6, 0.25);
            }
        }
        for (var ty = post ? step * 0.35 : 6; ty < ctx.H; ty += step) {
            for (var tx = post ? ctx.W / 2 : 10; tx < ctx.W; tx += post ? ctx.W : 20) {
                ctx.ellipse(tx + 0.4, ty + 0.5, 2.6, 2.6, '#fff', 'opacity="0.25"');
                ctx.ellipse(tx, ty, 2.4, 2.4, ctx.joint);
                ctx.ellipse(tx - 0.4, ty - 0.4, 1.3, 1.3, '#000', 'opacity="0.4"');
            }
        }
    }

    function broom(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, 0.35);
        for (var y = 0.5; y < ctx.H; y += ctx.rand(1.2, 2.4)) {
            ctx.shape('M-2 ' + n(y) + 'Q' + n(ctx.W / 2) + ' ' + n(y + ctx.rand(-0.8, 0.8)) + ' ' + n(ctx.W + 2) + ' ' + n(y + ctx.rand(-0.6, 0.6)), 'none', 'stroke="' + (ctx.r() < 0.5 ? ctx.accent : shade(ctx.base, 0.25)) + '" stroke-width="' + n(ctx.rand(0.4, 0.9)) + '" opacity="' + n(ctx.rand(0.35, 0.8)) + '"');
        }
        ctx.noise('sand', ctx.accent, 0.3);
    }

    function sandblasted(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, 0.3);
        ctx.noise('dense', ctx.accent, 0.8);
        ctx.noise('sand', ctx.joint, 0.8);
        ctx.noise('speck', ctx.joint, 0.6);
    }

    function stampedSlate(ctx) {
        ctx.fill(ctx.joint);
        var slateCell = isPostBox(ctx) ? 15 : 28;
        cells(ctx, slateCell, slateCell * 0.32, 2.4, 0.5).forEach(function(poly) {
            var d = polyPath(poly);
            ctx.shape(d, mix(ctx.base, ctx.accent, ctx.rand(0, 0.8)));
            ctx.shape(d, ctx.accent, 'filter="' + ctx.url('grainH') + '" opacity="0.5"');
            ctx.shape(d, ctx.url('convex'), 'opacity="0.35"');
        });
        ctx.noise('fine', ctx.joint, 0.2);
    }

    function acidStain(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('cloud', ctx.accent, 0.8);
        ctx.noise('blotch', ctx.joint, 0.6);
        ctx.noise('mottle', shade(ctx.base, -0.3), 0.4);
        ctx.noise('grain', ctx.accent, 0.2);
        ctx.shadeBox('sheenD', 0.2);
    }

    function terrazzo(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('fine', shade(ctx.base, -0.12), 0.35);
        var palette = [ctx.accent, ctx.joint, shade(ctx.accent, 0.35), '#2c2c2e', '#fbfaf7', mix(ctx.joint, '#e8c9a0', 0.5)];
        for (var i = 0; i < Math.round(ctx.W * ctx.H / 95); i++) {
            var cx = ctx.rand(0, ctx.W);
            var cy = ctx.rand(0, ctx.H);
            var s = ctx.r() < 0.18 ? ctx.rand(3.5, 6) : ctx.rand(1, 2.8);
            var pts = [];
            var sides = 3 + Math.floor(ctx.rand(0, 3));
            for (var a = 0; a < sides; a++) {
                var ang = a / sides * Math.PI * 2 + ctx.rand(-0.4, 0.4);
                pts.push([cx + Math.cos(ang) * s * ctx.rand(0.6, 1.1), cy + Math.sin(ang) * s * ctx.rand(0.6, 1.1)]);
            }
            ctx.shape(polyPath(pts), ctx.pick(palette));
        }
        ctx.shadeBox(isPostBox(ctx) ? 'sheenV' : 'sheenD', 0.3);
    }

    function blockRender(ctx) {
        concrete(ctx, { mottle: 0.45, grain: 0.35, pores: 0.25 });
        var bh = 20;
        for (var row = 0, y = 0; y < ctx.H + bh; row++, y += bh) {
            ctx.line(0, y, ctx.W, y, ctx.joint, 1, 0.28);
            for (var x = (row % 2) * 20 - 40; x < ctx.W; x += 40) {
                ctx.line(x, y, x, y + bh, ctx.joint, 1, 0.22);
            }
        }
        ctx.noise('cloud', ctx.base, 0.4);
    }

    function fluted(ctx, w, o) {
        o = o || {};
        ctx.fill(ctx.base);
        if (o.wood) {
            ctx.noise('grainV', ctx.accent, 0.45);
        } else {
            ctx.noise('mottle', ctx.accent, 0.35);
        }
        for (var x = 0; x < ctx.W; x += w) {
            ctx.shadeBox('rib', 0.9, [x, 0, w, ctx.H]);
            if (o.groove) {
                ctx.rect(x + w - 1.2, 0, 1.2, ctx.H, ctx.accent, 'opacity="0.85"');
            }
        }
        ctx.noise('grain', ctx.accent, 0.25);
    }

    //----------------------------------------------------------------------------------
    // Stone

    function granite(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', mix(ctx.base, ctx.accent, 0.35), 0.5);
        ctx.noise('dense', ctx.accent, 0.5);
        ctx.noise('speck', ctx.accent, 0.95);
        ctx.noise('sand', ctx.joint, 0.8);
        ctx.shadeBox('sheenD', 0.25);
    }

    function marble(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('cloud', ctx.accent, 0.2);
        var lean = ctx.rand(8, 14);
        for (var i = 0; i < 4; i++) {
            ctx.shape(wavePath(ctx, ctx.rand(-30, ctx.W - 10), 5, 5, lean), 'none', 'stroke="' + ctx.accent + '" stroke-width="' + n(ctx.rand(1.2, 3)) + '" opacity="0.28" filter="' + ctx.url('blur12') + '"');
            ctx.shape(wavePath(ctx, ctx.rand(-30, ctx.W - 10), 4, 7, lean), 'none', 'stroke="' + ctx.accent + '" stroke-width="' + n(ctx.rand(0.35, 0.9)) + '" opacity="' + n(ctx.rand(0.6, 0.95)) + '" filter="' + ctx.url('blur05') + '"');
        }
        ctx.shadeBox('sheenD', 0.3);
    }

    function banded(ctx, o) {
        ctx.fill(ctx.base);
        var y = 0;
        while (y < ctx.H) {
            var h = ctx.rand(o.hMin, o.hMax);
            var d = 'M-2 ' + n(y);
            for (var x = 0; x <= ctx.W + 10; x += 10) {
                d += 'L' + n(x) + ' ' + n(y + ctx.rand(-o.wave, o.wave));
            }
            d += 'L' + n(ctx.W + 2) + ' ' + n(y + h) + 'L-2 ' + n(y + h) + 'Z';
            ctx.shape(d, mix(ctx.base, ctx.accent, ctx.rand(0, o.spread)), 'opacity="0.8"');
            y += h;
        }
    }

    function travertine(ctx) {
        banded(ctx, { hMin: 4, hMax: 11, wave: 1.2, spread: 0.6 });
        ctx.noise('grainH', ctx.accent, 0.35);
        for (var i = 0; i < 22; i++) {
            var x = ctx.rand(0, ctx.W);
            var y = ctx.rand(0, ctx.H);
            var w = ctx.rand(1.5, 5);
            ctx.ellipse(x, y + 0.4, w, ctx.rand(0.5, 1.1), '#fff', 'opacity="0.3"');
            ctx.ellipse(x, y, w, ctx.rand(0.4, 1), ctx.joint, 'opacity="0.85"');
        }
    }

    function limestone(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, 0.45);
        ctx.noise('cloud', shade(ctx.base, 0.15), 0.5);
        ctx.noise('sand', ctx.joint, 0.45);
        for (var i = 0; i < 10; i++) {
            var x = ctx.rand(0, ctx.W);
            var y = ctx.rand(0, ctx.H);
            ctx.shape('M' + n(x) + ' ' + n(y) + 'q1.5 -2 3 0', 'none', 'stroke="' + ctx.joint + '" stroke-width="0.6" opacity="0.55"');
        }
    }

    function sandstone(ctx) {
        banded(ctx, { hMin: 3, hMax: 9, wave: 2.2, spread: 0.7 });
        ctx.noise('dense', ctx.accent, 0.45);
        ctx.noise('sand', shade(ctx.base, 0.25), 0.5);
        ctx.noise('mottle', ctx.accent, 0.25);
    }

    function slate(ctx, multi) {
        ctx.fill(ctx.base);
        if (multi) {
            ctx.noise('blotch', ctx.accent, 0.75);
            ctx.noise('cloud', ctx.joint, 0.6);
        }
        ctx.noise('grainH', multi ? shade(ctx.base, -0.3) : ctx.accent, 0.7);
        var post = isPostBox(ctx);
        var flakes = post ? Math.round(ctx.H / 16) : 9;
        for (var i = 0; i < flakes; i++) {
            var y = ctx.rand(0, ctx.H);
            var w = post ? ctx.rand(10, 22) : ctx.rand(20, 50);
            var x = post ? ctx.rand(-2, ctx.W - w * 0.7) : ctx.rand(-10, ctx.W - 20);
            ctx.shape('M' + n(x) + ' ' + n(y) + 'l' + n(w) + ' ' + n(ctx.rand(-2, 2)) + 'l-' + n(ctx.rand(2, 6)) + ' ' + n(ctx.rand(2, 5)) + 'l-' + n(w - 6) + ' ' + n(ctx.rand(-1, 1)) + 'Z', shade(ctx.base, multi ? 0.08 : 0.1), 'opacity="0.55"');
            ctx.line(x, y + 0.4, x + w, y + 0.4, '#000', 0.5, 0.35);
        }
        ctx.shadeBox('sheenD', 0.12);
    }

    function basalt(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, 0.4);
        ctx.noise('fine', shade(ctx.base, 0.12), 0.4);
        ctx.noise('sand', ctx.accent, 0.9);
        ctx.noise('pits', '#0d1013', 0.9);
    }

    function quartzite(ctx) {
        banded(ctx, { hMin: 5, hMax: 14, wave: 3.5, spread: 0.55 });
        ctx.noise('cloud', ctx.accent, 0.35);
        ctx.noise('sand', '#ffffff', 0.9);
        ctx.shadeBox('sheenD', 0.25);
    }

    function stones(ctx, kind) {
        var cfg = {
            'river-stone': { bg: ctx.joint, round: true },
            fieldstone: { bg: ctx.joint, hMin: 12, hMax: 22, wMin: 14, wMax: 30, gap: 2.8, jit: 3.5, round: true },
            ledgestone: { bg: ctx.joint, hMin: 3, hMax: 7.5, wMin: 14, wMax: 42, gap: 1.1, jit: 0.9, ledge: true },
            drystack: { bg: ctx.joint, hMin: 8, hMax: 16, wMin: 12, wMax: 30, gap: 1.2, jit: 2.6 },
            rubble: { bg: ctx.joint, hMin: 9, hMax: 20, wMin: 10, wMax: 26, gap: 3, jit: 3.4 },
            'split-face': { bg: ctx.joint, hMin: 9, hMax: 14, wMin: 16, wMax: 32, gap: 1.4, jit: 0.8, facets: true },
            tumbled: { bg: ctx.joint, hMin: 12, hMax: 16, wMin: 18, wMax: 28, gap: 2.6, jit: 0.8, round: true },
            'stone-veneer': { bg: ctx.joint, hMin: 6, hMax: 13, wMin: 14, wMax: 34, gap: 2.2, jit: 1.8, ledge: true },
            'faux-stone': { bg: ctx.joint, hMin: 14, hMax: 24, wMin: 18, wMax: 34, gap: 2.4, jit: 3.2, round: true, deep: true }
        }[kind];
        ctx.fill(cfg.bg);
        if (kind === 'river-stone') {
            discs(ctx, 40, 5, 10, 0).forEach(function(d) {
                var rx = d.r * ctx.rand(1, 1.3);
                var ry = d.r * ctx.rand(0.7, 0.9);
                var rot = 'transform="rotate(' + n(ctx.rand(-30, 30)) + ' ' + n(d.x) + ' ' + n(d.y) + ')"';
                ctx.ellipse(d.x + 0.8, d.y + 1, rx, ry, '#000', 'opacity="0.35" ' + rot);
                ctx.ellipse(d.x, d.y, rx, ry, mix(ctx.base, ctx.accent, ctx.rand(0, 0.9)), rot);
                ctx.ellipse(d.x, d.y, rx, ry, ctx.url('dome'), 'opacity="0.9" ' + rot);
            });
            return;
        }
        if (kind === 'fieldstone') {
            // Loose rounded rocks of mixed size and colour, bedded in mortar rather than coursed.
            var palette = [ctx.base, ctx.accent, mix(ctx.base, '#b59a6e', 0.5), mix(ctx.accent, '#7a7f86', 0.45), shade(ctx.base, 0.12)];
            discs(ctx, 34, 5, 12, -0.08).forEach(function(d) {
                var pts = [];
                for (var a = 0; a < 7; a++) {
                    var ang = a / 7 * Math.PI * 2 + ctx.rand(-0.3, 0.3);
                    var rr = d.r * ctx.rand(0.72, 1.05);
                    pts.push([d.x + Math.cos(ang) * rr * 1.15, d.y + Math.sin(ang) * rr * 0.85]);
                }
                var path = blobPath(pts);
                ctx.shape(path, '#000', 'opacity="0.3" transform="translate(0.8 1)"');
                ctx.shape(path, ctx.pick(palette));
                ctx.shape(path, ctx.accent, 'filter="' + ctx.url('mottle') + '" opacity="0.45"');
                ctx.shape(path, ctx.url('convex'), 'opacity="0.6"');
            });
            return ctx.noise('fine', '#000', 0.1);
        }
        if (kind === 'ledgestone' || kind === 'stone-veneer') {
            ctx.noise('mottle', shade(cfg.bg, -0.2), 0.5);
        }
        courses(ctx, cfg).forEach(function(s) {
            var pts = stonePoints(ctx, s.x, s.y, s.w, s.h, cfg.jit);
            var d = cfg.round ? blobPath(pts) : polyPath(pts);
            var tone = mix(ctx.base, ctx.accent, ctx.rand(0, 0.9));
            if (cfg.deep) {
                ctx.shape(d, '#000', 'opacity="0.45" transform="translate(1.4 1.8)"');
            }
            ctx.shape(d, tone);
            ctx.shape(d, ctx.accent, 'filter="' + ctx.url(cfg.ledge ? 'grainH' : 'mottle') + '" opacity="0.5"');
            if (cfg.facets) {
                for (var f = 0; f < 4; f++) {
                    var fx = s.x + ctx.rand(0, s.w - 6);
                    var fy = s.y + ctx.rand(0, s.h - 4);
                    ctx.shape(polyPath([[fx, fy], [fx + ctx.rand(4, 9), fy + ctx.rand(-1, 2)], [fx + ctx.rand(2, 7), fy + ctx.rand(3, 6)]]), ctx.r() < 0.5 ? '#fff' : '#000', 'opacity="0.14"');
                }
            }
            ctx.shape(d, ctx.url(cfg.ledge ? 'topLight' : 'convex'), 'opacity="' + (cfg.deep ? 0.8 : 0.6) + '"');
        });
        ctx.noise('fine', '#000', 0.12);
    }

    function flagstone(ctx) {
        ctx.fill(ctx.joint);
        ctx.noise('dense', shade(ctx.joint, 0.15), 0.4);
        var pave = isPostBox(ctx) ? 14 : 24;
        cells(ctx, pave, pave * 0.29, 2.6, 0.35).forEach(function(poly) {
            var d = polyPath(poly);
            ctx.shape(d, mix(ctx.base, ctx.accent, ctx.rand(0, 0.9)));
            ctx.shape(d, ctx.accent, 'filter="' + ctx.url('grainH') + '" opacity="0.45"');
            ctx.shape(d, ctx.url('convex'), 'opacity="0.45"');
        });
    }

    function ashlar(ctx, o) {
        o = o || {};
        ctx.fill(ctx.joint);
        var bh = 13;
        for (var row = 0, y = 0; y < ctx.H; row++, y += bh + 1.4) {
            var x = -ctx.rand(4, 20);
            while (x < ctx.W) {
                var w = ctx.rand(20, 32);
                ctx.rect(x, y, w, bh, mix(ctx.base, ctx.accent, ctx.rand(0, 0.7)));
                ctx.noise(o.bands ? 'grainH' : 'mottle', ctx.accent, o.bands ? 0.6 : 0.45, [x, y, w, bh]);
                ctx.shadeBox('topLight', 0.35, [x, y, w, bh]);
                x += w + 1.4;
            }
        }
        ctx.noise('sand', ctx.accent, 0.35);
        if (o.pits) {
            ctx.noise('pits', shade(ctx.accent, -0.5), 0.9);
        }
    }

    function coral(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, 0.5);
        discs(ctx, 55, 0.6, 2.4, 0).forEach(function(d) {
            var rx = d.r * ctx.rand(0.8, 1.6);
            ctx.ellipse(d.x + 0.3, d.y + 0.4, rx, d.r, '#fff', 'opacity="0.4"');
            ctx.ellipse(d.x, d.y, rx, d.r * ctx.rand(0.6, 1), shade(ctx.accent, -0.3));
        });
        ctx.noise('sand', ctx.accent, 0.5);
    }

    function lava(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', shade(ctx.base, 0.12), 0.5);
        discs(ctx, 110, 0.5, 2.2, 0.1).forEach(function(d) {
            ctx.ellipse(d.x + 0.3, d.y + 0.35, d.r * 1.1, d.r, shade(ctx.base, 0.25), 'opacity="0.55"');
            ctx.ellipse(d.x, d.y, d.r * 1.1, d.r * ctx.rand(0.6, 1), ctx.accent);
        });
        ctx.noise('sand', shade(ctx.base, 0.35), 0.4);
    }

    //----------------------------------------------------------------------------------
    // Brick and block

    // Laid bricks as boxes; `bond` picks the layout, sizes are in swatch units (80 = one swatch).
    function brickBoxes(ctx, o) {
        var out = [];
        var j = o.joint;
        var row = 0;
        for (var y = j / 2; y < ctx.H; y += o.h + j, row++) {
            if (o.bond === 'soldier' && row % 3 === 1) {
                for (var sx = -ctx.rand(0, 6); sx < ctx.W; sx += o.h + j) {
                    out.push({ x: sx, y: y, w: o.h, h: o.w, row: row, soldier: true });
                }
                y += o.w - o.h;
                continue;
            }
            var off = o.bond === 'stack' ? 0 : (row % 2) * ((o.bond === 'flemish' ? (o.w + o.hw) / 2 : o.w / 2) + j / 2);
            var x = -off;
            var header = row % 2 === 1;
            while (x < ctx.W) {
                var w = o.bond === 'flemish' && header ? o.hw : o.w;
                out.push({ x: x, y: y, w: w, h: o.h, row: row, header: o.bond === 'flemish' && header });
                x += w + j;
                if (o.bond === 'flemish') {
                    header = !header;
                }
            }
        }
        return out;
    }

    function brick(ctx, o) {
        o = Object.assign({ w: 23, h: 7.2, hw: 11, joint: 1.8, bond: 'running', spread: 0.6, rx: 0.4 }, o || {});
        ctx.fill(ctx.joint);
        ctx.noise('dense', shade(ctx.joint, -0.2), 0.35);
        var dark = shade(ctx.accent, -0.35);
        var attrs = o.rx ? 'rx="' + o.rx + '"' : '';
        // Bricks never overlap, so each per-brick layer runs as a pass over the laid boxes instead:
        // the filtered passes share one <g> each, where a filter per brick made this the slowest grid.
        var laid = [];
        var soots = [];
        var residues = [];
        var boxes = brickBoxes(ctx, o);
        boxes.forEach(function(b) {
            var x = b.x + (o.wobble ? ctx.rand(-o.wobble, o.wobble) : 0);
            var y = b.y + (o.wobble ? ctx.rand(-o.wobble * 0.6, o.wobble * 0.6) : 0);
            var tone = o.palette ? ctx.pick(o.palette) : mix(ctx.base, ctx.accent, ctx.rand(0, o.spread));
            if (b.header) {
                tone = mix(ctx.accent, dark, ctx.rand(0, 0.5));
            }
            if (o.burnt && ctx.r() < o.burnt) {
                tone = mix(tone, '#1c1412', ctx.rand(0.35, 0.7));
            }
            ctx.rect(x, y, b.w, b.h, tone, attrs);
            laid.push([x, y, b.w, b.h]);
            if (o.soot && ctx.r() < 0.35) {
                soots.push([x, y, b.w, b.h]);
            }
            if (o.residue && ctx.r() < 0.4) {
                residues.push([x, y, b.w, b.h]);
            }
        });
        var pass = function(boxes, fill, extra) {
            boxes.forEach(function(b) {
                ctx.rect(b[0], b[1], b[2], b[3], fill, extra);
            });
        };
        ctx.filterGroup(o.face || 'mottle', o.faceOpacity == null ? 0.45 : o.faceOpacity, function() {
            pass(laid, ctx.accent, attrs);
        });
        if (o.glaze) {
            pass(laid, ctx.url('sheenD'), (attrs ? attrs + ' ' : '') + 'opacity="0.8"');
        } else {
            pass(laid, ctx.url('topLight'), (attrs ? attrs + ' ' : '') + 'opacity="' + (o.flat ? 0.25 : 0.5) + '"');
        }
        if (o.raked) {
            laid.forEach(function(b) {
                ctx.rect(b[0], b[1] + b[3], b[2], 0.9, '#000', 'opacity="0.55"');
            });
        }
        if (soots.length) {
            ctx.filterGroup('mottle', 0.6, function() {
                pass(soots, '#1a1614');
            });
        }
        if (residues.length) {
            ctx.filterGroup('blotch', 0.8, function() {
                pass(residues, ctx.joint);
            });
        }
        if (o.fleck) {
            ctx.filterGroup('sand', 0.9, function() {
                pass(laid, shade(ctx.accent, -0.45));
            });
            ctx.filterGroup('speck', 0.85, function() {
                pass(laid, shade(ctx.base, 0.5));
            });
        }
        // Tuck-pointing: a fine lime line ruled along the centre of every joint.
        if (o.tuck) {
            var rows = {};
            boxes.forEach(function(b) {
                rows[n(b.y)] = true;
                ctx.rect(b.x - o.joint / 2 - 0.35, b.y, 0.7, b.h, o.tuck);
            });
            Object.keys(rows).forEach(function(k) {
                ctx.rect(0, Number(k) - o.joint / 2 - 0.35, ctx.W, 0.7, o.tuck);
            });
        }
        ctx.noise('fine', '#000', o.flat ? 0.06 : 0.14);
    }

    function brickFamily(ctx, kind) {
        switch (kind) {
            case 'brick-smooth':
                return brick(ctx, { spread: 0.25, face: 'fine', faceOpacity: 0.2, flat: true, rx: 0.2 });
            case 'brick-tumbled':
                return brick(ctx, { spread: 0.9, rx: 1.6, joint: 2.2, wobble: 0.5 });
            case 'brick-reclaimed':
                return brick(ctx, { spread: 0.9, rx: 1, soot: true, residue: true, joint: 2 });
            case 'brick-clinker':
                return brick(ctx, { spread: 1, burnt: 0.45, wobble: 0.7, rx: 1.2, joint: 2, face: 'blotch', faceOpacity: 0.6 });
            case 'brick-wirecut':
                return brick(ctx, { spread: 0.5, face: 'brushV', faceOpacity: 0.7 });
            case 'brick-glazed':
                return brick(ctx, { spread: 0.35, glaze: true, face: 'cloud', faceOpacity: 0.4, rx: 0.6 });
            case 'brick-blend':
                return brick(ctx, { palette: [ctx.base, ctx.accent, mix(ctx.base, '#c7a57a', 0.5), shade(ctx.base, -0.3), mix(ctx.accent, '#3b2b24', 0.35), mix(ctx.base, '#d98b5f', 0.3)] });
            case 'brick-whitewash':
                brick(ctx, { palette: [ctx.accent, shade(ctx.accent, -0.15), shade(ctx.accent, 0.1)] });
                ctx.rect(0, 0, ctx.W, ctx.H, ctx.base, 'opacity="0.55"');
                ctx.noise('mottle', ctx.base, 0.75);
                return;
            case 'brick-smear':
                brick(ctx, { palette: [ctx.accent, shade(ctx.accent, -0.15), shade(ctx.accent, 0.12)], joint: 2.4 });
                ctx.noise('blotch', ctx.joint, 1);
                ctx.noise('mottle', ctx.base, 0.7);
                return;
            case 'brick-painted':
                return brick(ctx, { spread: 0.15, face: 'fine', faceOpacity: 0.25, flat: true, joint: 2 });
            case 'brick-roman':
                return brick(ctx, { w: 36, h: 4.6, joint: 1.6, spread: 0.7 });
            case 'brick-stack':
                return brick(ctx, { bond: 'stack', spread: 0.5 });
            case 'brick-flemish':
                return brick(ctx, { bond: 'flemish', spread: 0.35 });
            case 'brick-soldier':
                return brick(ctx, { bond: 'soldier', spread: 0.6 });
            case 'brick-raked':
                return brick(ctx, { spread: 0.55, joint: 2.2, raked: true });
            case 'brick-tuckpoint':
                return brick(ctx, { spread: 0.4, joint: 2.2, tuck: '#f3efe6' });
            case 'brick-flecked':
                return brick(ctx, { spread: 0.35, fleck: true });
            case 'brick-slip':
                return brick(ctx, { spread: 0.5, joint: 1.5, face: 'grain', faceOpacity: 0.3, flat: true, rx: 0.2 });
            default:
                return brick(ctx);
        }
    }

    function herringbone(ctx) {
        ctx.fill(ctx.joint);
        var u = 6.5;
        var j = 1.4;
        var c = ctx.W / 2;
        var reach = Math.ceil(Math.max(ctx.W, ctx.H) / u) + 6;
        var parts = [];
        for (var band = -reach; band <= reach; band += 1) {
            for (var k = -reach; k <= reach; k++) {
                var ox = k * u + band * 2 * u;
                var oy = k * u - band * 2 * u;
                parts.push([ox, oy, 2 * u, u], [ox - u, oy, u, 2 * u]);
            }
        }
        ctx.add('<g transform="rotate(45 ' + n(c) + ' ' + n(ctx.H / 2) + ') translate(' + n(c) + ' ' + n(ctx.H / 2) + ')">');
        // Only bricks whose rotated box meets the swatch: a tall pier box would otherwise carry ~12x off-canvas bricks.
        var k45 = Math.SQRT1_2;
        var onCanvas = function(p) {
            var xs = [];
            var ys = [];
            [[p[0], p[1]], [p[0] + p[2], p[1]], [p[0], p[1] + p[3]], [p[0] + p[2], p[1] + p[3]]].forEach(function(q) {
                xs.push(c + (q[0] - q[1]) * k45);
                ys.push(ctx.H / 2 + (q[0] + q[1]) * k45);
            });
            return Math.max.apply(null, xs) >= 0 && Math.min.apply(null, xs) <= ctx.W &&
                Math.max.apply(null, ys) >= 0 && Math.min.apply(null, ys) <= ctx.H;
        };
        parts.forEach(function(p) {
            if (!onCanvas(p)) {
                return;
            }
            ctx.rect(p[0] + j / 2, p[1] + j / 2, p[2] - j, p[3] - j, mix(ctx.base, ctx.accent, ctx.rand(0, 0.7)), 'rx="0.4"');
            ctx.rect(p[0] + j / 2, p[1] + j / 2, p[2] - j, p[3] - j, ctx.url('convex'), 'opacity="0.35"');
        });
        ctx.add('</g>');
        ctx.noise('mottle', ctx.accent, 0.3);
    }

    function block(ctx, split) {
        ctx.fill(ctx.joint);
        var bw = 38;
        var bh = 18;
        var j = 2;
        for (var row = 0, y = j / 2; y < ctx.H; row++, y += bh + j) {
            for (var x = -(row % 2) * (bw + j) / 2; x < ctx.W; x += bw + j) {
                var tone = mix(ctx.base, ctx.accent, ctx.rand(0, 0.45));
                ctx.rect(x, y, bw, bh, tone);
                ctx.noise(split ? 'mottle' : 'grain', ctx.accent, split ? 0.7 : 0.45, [x, y, bw, bh]);
                ctx.noise(split ? 'speck' : 'sand', shade(ctx.accent, -0.3), split ? 0.9 : 0.5, [x, y, bw, bh]);
                if (split) {
                    for (var f = 0; f < 6; f++) {
                        var fx = x + ctx.rand(0, bw - 8);
                        var fy = y + ctx.rand(0, bh - 6);
                        ctx.shape(polyPath([[fx, fy], [fx + ctx.rand(5, 12), fy + ctx.rand(-2, 3)], [fx + ctx.rand(2, 8), fy + ctx.rand(4, 8)]]), ctx.r() < 0.5 ? '#fff' : '#000', 'opacity="0.13"');
                    }
                }
                ctx.shadeBox('topLight', split ? 0.5 : 0.3, [x, y, bw, bh]);
            }
        }
    }

    function breeze(ctx) {
        ctx.fill(ctx.joint);
        var s = ctx.W / Math.max(1, Math.round(ctx.W / 26)) - 1.6;
        var j = 1.6;
        for (var y = -s / 3; y < ctx.H; y += s + j) {
            for (var x = -s / 3; x < ctx.W; x += s + j) {
                ctx.rect(x, y, s, s, ctx.base);
                ctx.noise('grain', ctx.accent, 0.35, [x, y, s, s]);
                var cx = x + s / 2;
                var cy = y + s / 2;
                // Four petal openings round a solid centre: the vent reads as holes through the block.
                [[0, -1], [1, 0], [0, 1], [-1, 0]].forEach(function(dir) {
                    var ex = cx + dir[0] * s * 0.23;
                    var ey = cy + dir[1] * s * 0.23;
                    var rx = dir[0] ? s * 0.17 : s * 0.1;
                    var ry = dir[0] ? s * 0.1 : s * 0.17;
                    ctx.ellipse(ex, ey, rx, ry, shade(ctx.joint, -0.35));
                    ctx.ellipse(ex - 0.6, ey - 0.6, rx, ry, 'none', 'stroke="' + ctx.accent + '" stroke-width="1" opacity="0.8"');
                });
                ctx.rect(x, y, s, s, 'none', 'stroke="' + ctx.accent + '" stroke-width="1" opacity="0.6"');
            }
        }
    }

    //----------------------------------------------------------------------------------
    // Wood

    function grainLines(ctx, x0, w, count, color, opacity, amp) {
        // Segments scale with height so tall pier renders keep timber-straight grain, not squiggle.
        var steps = Math.max(4, Math.round(ctx.H / 18));
        for (var i = 0; i < count; i++) {
            ctx.shape(wavePath(ctx, x0 + ctx.rand(0, w), amp, steps, ctx.rand(-1.6, 1.6) / steps), 'none', 'stroke="' + color + '" stroke-width="' + n(ctx.rand(0.35, 0.9)) + '" opacity="' + n(ctx.rand(opacity * 0.5, opacity)) + '"');
        }
    }

    /** True for the tall narrow box the drawn piers use, where board seams would read as a split post. */
    function isPostBox(ctx) {
        return ctx.W <= 60 && ctx.H >= ctx.W * 2.5;
    }

    function wood(ctx, o) {
        o = Object.assign({ board: 26, seam: true, lines: 6, amp: 1.2, grain: 0.55, spread: 0.3 }, o || {});
        // One piece of timber on a pier: no seams, an even tone and tight grain.
        var post = isPostBox(ctx);
        if (post) {
            o.board = ctx.W + 2;
            o.seam = false;
            o.spread = Math.min(o.spread, 0.2);
            o.amp = Math.min(o.amp, 0.9);
            o.lines = Math.max(o.lines, 8);
        }
        ctx.fill(ctx.base);
        var x = -ctx.rand(0, o.board * 0.5);
        var idx = 0;
        while (x < ctx.W) {
            var w = o.board * ctx.rand(0.85, 1.15);
            var tone = o.palette ? ctx.pick(o.palette) : mix(ctx.base, ctx.accent, ctx.rand(0, o.spread));
            ctx.rect(x, 0, w, ctx.H, tone);
            ctx.noise(idx % 2 ? 'grainV2' : 'grainV', o.grainColor || ctx.accent, o.grain, [x, 0, w, ctx.H]);
            grainLines(ctx, x, w, o.lines, o.lineColor || ctx.joint, o.lineOpacity || 0.4, o.amp);
            if (o.seam) {
                ctx.rect(x + w - 0.8, 0, 0.8, ctx.H, ctx.joint, 'opacity="0.75"');
                ctx.rect(x + w, 0, 0.6, ctx.H, '#fff', 'opacity="0.12"');
            }
            x += w;
            idx++;
        }
        if (post) {
            ctx.noise('streakV', ctx.accent, 0.45);
            ctx.shadeBox('sheenV', 0.3);
        }
    }

    function knots(ctx, count) {
        // Sized to the face and kept off the arrises, so a pier gets a couple of believable knots.
        var kr = Math.min(3.6, Math.max(1.6, ctx.W * 0.09));
        if (isPostBox(ctx)) {
            count = Math.min(count, 2);
        }
        for (var i = 0; i < count; i++) {
            var cx = ctx.rand(ctx.W * 0.25, ctx.W * 0.75);
            var cy = ctx.rand(ctx.H * 0.12, ctx.H * 0.88);
            var rx = ctx.rand(kr * 0.6, kr);
            var ry = rx * ctx.rand(0.65, 0.9);
            for (var k = 3; k >= 1; k--) {
                ctx.ellipse(cx, cy, rx * (1 + k * 0.7), ry * (1 + k * 0.9), 'none', 'stroke="' + ctx.accent + '" stroke-width="0.6" opacity="' + n(0.18 + 0.1 * (3 - k)) + '"');
            }
            ctx.ellipse(cx, cy, rx, ry, ctx.joint);
            ctx.ellipse(cx - 0.4, cy - 0.3, rx * 0.5, ry * 0.5, shade(ctx.joint, -0.35));
        }
    }

    function woodFamily(ctx, kind) {
        switch (kind) {
            case 'wood-knotty':
                wood(ctx, { lines: 5 });
                return knots(ctx, 3);
            case 'wood-oak':
                wood(ctx, { lines: 8, lineColor: ctx.accent, lineOpacity: 0.55 });
                for (var i = 0; i < 40; i++) {
                    var x = ctx.rand(0, ctx.W);
                    var y = ctx.rand(0, ctx.H);
                    ctx.shape('M' + n(x) + ' ' + n(y) + 'l' + n(ctx.rand(1.5, 3.5)) + ' ' + n(ctx.rand(-0.6, 0.6)), 'none', 'stroke="' + ctx.joint + '" stroke-width="0.8" opacity="0.7"');
                }
                return;
            case 'wood-spotted':
                wood(ctx, { lines: 5, amp: 2.4, spread: 0.45 });
                for (var s = 0; s < 26; s++) {
                    ctx.ellipse(ctx.rand(0, ctx.W), ctx.rand(0, ctx.H), ctx.rand(0.8, 2.2), ctx.rand(1.5, 3.5), ctx.accent, 'opacity="0.35"');
                }
                return;
            case 'wood-cedar':
                // Cedar boards run from pale tan to deep red-brown within one batch.
                return wood(ctx, { palette: [ctx.base, ctx.accent, mix(ctx.base, '#dcae86', 0.55), mix(ctx.base, ctx.accent, 0.5)], lines: 6 });
            case 'wood-acacia':
                wood(ctx, { palette: [ctx.base, ctx.accent, mix(ctx.base, ctx.accent, 0.5), ctx.joint], lines: 5, grain: 0.7 });
                ctx.noise('streakV', ctx.accent, 0.7);
                return;
            case 'wood-weathered':
                wood(ctx, { lines: 9, lineColor: ctx.joint, lineOpacity: 0.7, amp: 1.8, spread: 0.4 });
                for (var c = 0; c < 8; c++) {
                    var cx = ctx.rand(0, ctx.W);
                    var cy = ctx.rand(0, ctx.H - 12);
                    ctx.line(cx, cy, cx + ctx.rand(-0.6, 0.6), cy + ctx.rand(6, 14), shade(ctx.joint, -0.3), 0.7, 0.7);
                }
                return ctx.noise('mottle', shade(ctx.base, 0.2), 0.3);
            case 'barnwood':
                wood(ctx, { palette: [ctx.base, ctx.accent, ctx.joint, mix(ctx.base, '#7d7a72', 0.6)], board: 18, lines: 7, lineOpacity: 0.7, amp: 1.6, grain: 0.7 });
                for (var h = 0; h < 6; h++) {
                    var hx = ctx.rand(4, ctx.W - 4);
                    var hy = ctx.rand(4, ctx.H - 4);
                    ctx.ellipse(hx, hy, 0.9, 0.9, '#1d1814');
                    ctx.ellipse(hx + 3.2, hy + ctx.rand(-0.5, 0.5), 0.9, 0.9, '#1d1814');
                }
                return ctx.noise('blotch', '#3a3129', 0.35);
            case 'wood-whitewash':
                wood(ctx, { lines: 6, lineOpacity: 0.55 });
                ctx.rect(0, 0, ctx.W, ctx.H, ctx.base, 'opacity="0.6"');
                ctx.noise('grainV', ctx.base, 0.55);
                return grainLines(ctx, 0, ctx.W, 9, ctx.accent, 0.35, 1.2);
            case 'wood-rough':
                wood(ctx, { lines: 6, grain: 0.75, amp: 1.6 });
                var flatArcs = isPostBox(ctx);
                for (var a = 0; a < (flatArcs ? Math.round(ctx.H / 16) : 7); a++) {
                    var ay = ctx.rand(0, ctx.H);
                    ctx.shape('M-4 ' + n(ay) + 'Q' + n(ctx.W / 2) + ' ' + n(ay - (flatArcs ? 2 : 10)) + ' ' + n(ctx.W + 4) + ' ' + n(ay), 'none', 'stroke="' + ctx.joint + '" stroke-width="' + (flatArcs ? 0.8 : 1.2) + '" opacity="0.2"');
                }
                return ctx.noise('dense', ctx.accent, 0.35);
            case 'wood-dressed':
                wood(ctx, { board: 40, lines: 7, amp: 0.8, grain: 0.45, spread: 0.2 });
                return ctx.shadeBox('sheenV', 0.3);
            case 'woodgrain-spotted':
                wood(ctx, { board: 90, seam: false, lines: 10, amp: 1.6, grain: 0.5, spread: 0.1, lineColor: ctx.accent, lineOpacity: 0.55 });
                for (var gs = 0; gs < 22; gs++) {
                    ctx.ellipse(ctx.rand(0, ctx.W), ctx.rand(0, ctx.H), ctx.rand(0.8, 2), ctx.rand(1.5, 3.2), ctx.accent, 'opacity="0.35"');
                }
                return ctx.shadeBox('sheenV', 0.3);
            case 'woodgrain-print':
                wood(ctx, { board: 90, seam: false, lines: 14, amp: 0.7, grain: 0.5, spread: 0.1, lineColor: ctx.accent, lineOpacity: 0.6 });
                return ctx.shadeBox('sheenV', 0.35);
            default:
                return wood(ctx);
        }
    }

    function bamboo(ctx) {
        ctx.fill(ctx.joint);
        var w = 13;
        for (var x = -ctx.rand(0, 6); x < ctx.W; x += w + 1) {
            ctx.rect(x, 0, w, ctx.H, mix(ctx.base, ctx.accent, ctx.rand(0, 0.35)));
            ctx.noise('brushV', ctx.accent, 0.45, [x, 0, w, ctx.H]);
            ctx.shadeBox('cyl', 0.75, [x, 0, w, ctx.H]);
            for (var y = ctx.rand(4, 26); y < ctx.H; y += ctx.rand(24, 32)) {
                ctx.rect(x, y - 1.2, w, 2.4, ctx.accent, 'opacity="0.55"');
                ctx.rect(x, y - 0.4, w, 0.8, ctx.joint);
                ctx.rect(x, y + 0.6, w, 0.7, '#fff', 'opacity="0.35"');
            }
        }
    }

    function charred(ctx) {
        ctx.fill(ctx.joint);
        cells(ctx, 7, 2.2, 1.1, 0).forEach(function(poly) {
            var d = polyPath(poly);
            ctx.shape(d, mix(ctx.base, ctx.accent, ctx.rand(0, 0.55)));
            ctx.shape(d, ctx.url('convex'), 'opacity="0.55"');
        });
        ctx.noise('grainV', ctx.accent, 0.3);
        ctx.shadeBox('sheenV', 0.25);
    }

    function logPole(ctx) {
        ctx.fill(ctx.joint);
        // A pier is one round post; square swatches show the pair.
        var w = isPostBox(ctx) ? ctx.W : (ctx.W - 4) / 2;
        (isPostBox(ctx) ? [0] : [1, w + 3]).forEach(function(x) {
            ctx.rect(x, 0, w, ctx.H, ctx.base, 'rx="1"');
            ctx.noise('grainV', ctx.accent, 0.6, [x, 0, w, ctx.H]);
            grainLines(ctx, x + 2, w - 4, 5, ctx.accent, 0.5, 1.4);
            var ky = ctx.rand(10, ctx.H - 10);
            ctx.ellipse(x + w * ctx.rand(0.3, 0.7), ky, 2.4, 1.8, ctx.joint, 'opacity="0.85"');
            ctx.shadeBox('cyl', 1, [x, 0, w, ctx.H], 'rx="1"');
        });
    }

    //----------------------------------------------------------------------------------
    // Metal

    function metal(ctx, kind) {
        ctx.fill(ctx.base);
        switch (kind) {
            case 'brushed':
                ctx.noise('brushV', ctx.accent, 0.6);
                ctx.noise('brushV', shade(ctx.base, 0.35), 0.35, [0.5, 0, ctx.W, ctx.H]);
                return ctx.shadeBox('sheenV', 0.8);
            case 'mirror':
                if (isPostBox(ctx)) {
                    var mx = 0;
                    while (mx < ctx.W) {
                        var mw = ctx.rand(2.5, 7);
                        ctx.rect(mx, 0, mw, ctx.H, mix(ctx.base, ctx.r() < 0.5 ? '#ffffff' : shade(ctx.accent, -0.25), ctx.rand(0.15, 0.75)));
                        mx += mw;
                    }
                    ctx.rect(ctx.W * 0.24, 0, 1.6, ctx.H, '#ffffff', 'opacity="0.8"');
                    ctx.noise('brushV', '#ffffff', 0.2);
                    return ctx.shadeBox('cyl', 0.45);
                }
                ctx.shadeBox('mirror', 0.85);
                ctx.rect(0, 0, ctx.W, ctx.H, ctx.accent, 'opacity="0.12"');
                return ctx.shadeBox('sheenV', 0.4);
            case 'spangle':
                var spCell = isPostBox(ctx) ? 9 : 15;
                cells(ctx, spCell, spCell * 0.4, 0, 0.3).forEach(function(poly) {
                    var d = polyPath(poly);
                    ctx.shape(d, mix(ctx.base, ctx.r() < 0.5 ? ctx.accent : ctx.joint, ctx.rand(0, 0.8)));
                    ctx.shape(d, ctx.url(ctx.r() < 0.5 ? 'convex' : 'concave'), 'opacity="0.3"');
                    ctx.shape(d, 'none', 'stroke="' + ctx.accent + '" stroke-width="0.4" opacity="0.6"');
                });
                return ctx.noise('fine', ctx.accent, 0.2);
            case 'galv-matte':
                ctx.noise('mottle', ctx.accent, 0.6);
                ctx.noise('cloud', ctx.joint, 0.5);
                ctx.noise('grain', ctx.accent, 0.3);
                return ctx.noise('sand', '#ffffff', 0.25);
            case 'rust':
                ctx.noise('cloud', ctx.joint, 0.65);
                ctx.noise('mottle', ctx.accent, 0.7);
                ctx.noise('dense', ctx.accent, 0.5);
                ctx.noise('speck', shade(ctx.accent, -0.4), 0.8);
                return ctx.noise('sand', ctx.joint, 0.6);
            case 'mill-scale':
                ctx.noise('blotch', ctx.accent, 0.6);
                ctx.noise('mottle', ctx.joint, 0.55);
                ctx.noise('brushH', ctx.accent, 0.25);
                return ctx.noise('speck', ctx.joint, 0.7);
            case 'blackened':
                ctx.noise('cloud', ctx.accent, 0.5);
                ctx.noise('mottle', shade(ctx.base, -0.35), 0.45);
                ctx.noise('brushV', ctx.accent, 0.2);
                return ctx.shadeBox('sheenD', 0.22);
            case 'primer':
                ctx.noise('grain', ctx.accent, 0.5);
                ctx.noise('mottle', ctx.accent, 0.35);
                return ctx.noise('sand', shade(ctx.base, 0.2), 0.3);
            case 'powder':
                ctx.noise('fine', ctx.accent, 0.45);
                return ctx.shadeBox('sheenV', 0.3);
            case 'gloss':
                if (isPostBox(ctx)) {
                    ctx.shadeBox('cyl', 0.55);
                    ctx.rect(ctx.W * 0.24, 0, ctx.W * 0.07, ctx.H, '#ffffff', 'opacity="0.5"');
                    ctx.rect(ctx.W * 0.34, 0, ctx.W * 0.03, ctx.H, '#ffffff', 'opacity="0.22"');
                    return ctx.rect(ctx.W * 0.74, 0, ctx.W * 0.04, ctx.H, '#ffffff', 'opacity="0.16"');
                }
                ctx.shadeBox('sheenD', 0.75);
                ctx.rect(ctx.W * 0.2, 0, ctx.W * 0.08, ctx.H, ctx.accent, 'opacity="0.35"');
                return ctx.shadeBox('sheenV', 0.45);
            case 'powder-texture':
                ctx.noise('dense', ctx.accent, 0.85);
                ctx.noise('sand', shade(ctx.base, 0.2), 0.6);
                return ctx.shadeBox('sheenV', 0.15);
            case 'wrought':
            case 'hammertone':
                var ham = kind === 'hammertone';
                discs(ctx, ham ? 80 : 45, ham ? 2.6 : 2, ham ? 6 : 4, 0.35).forEach(function(d) {
                    ctx.ellipse(d.x, d.y, d.r, d.r * ctx.rand(0.85, 1), ctx.url('concave'), 'opacity="' + (ham ? 0.95 : 0.7) + '"');
                    ctx.ellipse(d.x, d.y, d.r, d.r, 'none', 'stroke="' + ctx.accent + '" stroke-width="0.4" opacity="0.35"');
                });
                if (!ham) {
                    ctx.noise('speck', ctx.joint, 0.7);
                    return ctx.noise('grain', ctx.accent, 0.3);
                }
                return ctx.shadeBox('sheenV', 0.3);
            case 'metallic':
                ctx.noise('cloud', ctx.accent, 0.4);
                ctx.noise('sand', '#ffffff', 0.4);
                ctx.noise('fine', ctx.accent, 0.3);
                return ctx.shadeBox('sheenV', 0.6);
            case 'oil-rubbed':
                ctx.noise('cloud', ctx.accent, 0.6);
                ctx.noise('mottle', shade(ctx.base, -0.4), 0.5);
                ctx.noise('brushV', ctx.accent, 0.25);
                return ctx.shadeBox('sheenV', 0.3);
            case 'patina':
                ctx.fill(ctx.accent);
                ctx.noise('blotch', ctx.base, 0.95);
                ctx.noise('cloud', ctx.joint, 0.6);
                ctx.noise('mottle', ctx.base, 0.5);
                return ctx.noise('speck', shade(ctx.accent, -0.4), 0.5);
            case 'checker':
                ctx.noise('brushV', shade(ctx.base, -0.15), 0.4);
                for (var y = 0, row = 0; y < ctx.H + 10; y += 9, row++) {
                    for (var x = (row % 2) * 9 - 9; x < ctx.W + 10; x += 18) {
                        [[x, y, 45], [x + 9, y, -45]].forEach(function(l) {
                            var rot = 'transform="rotate(' + l[2] + ' ' + n(l[0]) + ' ' + n(l[1]) + ')"';
                            ctx.ellipse(l[0] + 0.8, l[1] + 0.9, 4.2, 1.2, ctx.joint, 'opacity="0.8" ' + rot);
                            ctx.ellipse(l[0], l[1], 4.2, 1.2, ctx.accent, rot);
                        });
                    }
                }
                return ctx.shadeBox('sheenV', 0.35);
            case 'perforated':
                ctx.noise('brushV', ctx.joint, 0.4);
                for (var py = 3, pr = 0; py < ctx.H + 4; py += 7, pr++) {
                    for (var px = (pr % 2) * 4 + 1; px < ctx.W + 4; px += 8) {
                        ctx.ellipse(px, py, 2.3, 2.3, ctx.accent);
                        ctx.shape('M' + n(px - 2.3) + ' ' + n(py) + 'a2.3 2.3 0 0 0 4.6 0', 'none', 'stroke="' + ctx.joint + '" stroke-width="0.7" opacity="0.8"');
                    }
                }
                return ctx.shadeBox('sheenV', 0.4);
            case 'mill-alu':
                ctx.noise('brushH', ctx.accent, 0.55);
                ctx.noise('mottle', ctx.accent, 0.3);
                return ctx.shadeBox('sheenV', 0.35);
            case 'anodized':
                ctx.noise('fine', ctx.accent, 0.3);
                ctx.noise('brushV', ctx.accent, 0.2);
                return ctx.shadeBox('sheenV', 0.55);
            case 'pearl':
                ctx.shadeBox('iris', 0.9);
                ctx.noise('cloud', ctx.accent, 0.35);
                ctx.noise('sand', '#ffffff', 0.5);
                return ctx.shadeBox('sheenD', 0.35);
        }
    }

    //----------------------------------------------------------------------------------
    // Composite and vinyl

    function composite(ctx, kind) {
        ctx.fill(ctx.base);
        switch (kind) {
            case 'wpc':
                ctx.noise('grainV', ctx.accent, 0.55);
                for (var x = 0.5; x < ctx.W; x += 2.4) {
                    ctx.rect(x, 0, 0.5, ctx.H, ctx.accent, 'opacity="' + n(ctx.rand(0.12, 0.35)) + '"');
                }
                grainLines(ctx, 0, ctx.W, 8, ctx.accent, 0.45, 1);
                return ctx.shadeBox('sheenV', 0.15);
            case 'wpc-streak':
                ctx.noise('streakV', ctx.accent, 0.95);
                ctx.noise('grainV', ctx.accent, 0.4);
                return grainLines(ctx, 0, ctx.W, 6, shade(ctx.base, -0.25), 0.4, 1.2);
            case 'wpc-embossed':
                ctx.noise('grainV', ctx.accent, 0.8);
                ctx.add('<g transform="translate(0.9 0.4)">');
                ctx.noise('grainV', '#fff', 0.28);
                ctx.add('</g>');
                return grainLines(ctx, 0, ctx.W, 7, ctx.accent, 0.6, 1.8);
            case 'wpc-3d':
                ctx.noise('grainV2', ctx.accent, 0.45);
                for (var i = 0; i < 10; i++) {
                    var d = wavePath(ctx, ctx.rand(0, ctx.W), 3, 4, ctx.rand(-1, 1));
                    ctx.shape(d, 'none', 'stroke="#fff" stroke-width="1.2" opacity="0.25" transform="translate(-0.7 0)"');
                    ctx.shape(d, 'none', 'stroke="' + ctx.accent + '" stroke-width="1.2" opacity="0.75"');
                }
                return ctx.shadeBox('sheenV', 0.2);
            case 'wpc-brushed':
                ctx.noise('brushV', ctx.accent, 0.75);
                ctx.noise('grainV', ctx.accent, 0.3);
                return ctx.noise('brushV', shade(ctx.base, 0.2), 0.3, [0.6, 0, ctx.W, ctx.H]);
            case 'wpc-sanded':
                ctx.noise('dense', ctx.accent, 0.4);
                ctx.noise('grainV2', ctx.accent, 0.3);
                return ctx.noise('mottle', shade(ctx.base, 0.12), 0.35);
            case 'vinyl':
                ctx.noise('fine', ctx.accent, 0.12);
                return ctx.shadeBox('sheenV', 0.5);
            case 'vinyl-grain':
                ctx.noise('grainV', ctx.accent, 0.4);
                grainLines(ctx, 0, ctx.W, 6, ctx.accent, 0.3, 0.8);
                return ctx.shadeBox('sheenV', 0.35);
            case 'fibre-cement':
                ctx.noise('mottle', ctx.accent, 0.3);
                ctx.noise('fine', ctx.accent, 0.4);
                return ctx.noise('sand', shade(ctx.accent, -0.25), 0.5);
            case 'fibre-grain':
                ctx.noise('grainV', ctx.accent, 0.7);
                ctx.add('<g transform="translate(0.8 0)">');
                ctx.noise('grainV', '#fff', 0.25);
                ctx.add('</g>');
                ctx.rect(ctx.W / 2, 0, 1, ctx.H, shade(ctx.accent, -0.3), 'opacity="0.6"');
                return ctx.noise('fine', ctx.accent, 0.25);
            case 'recycled':
                var palette = [ctx.accent, ctx.joint, '#1d1f22', '#9aa0a6', '#4d6a8a', '#8a8f5a', '#e9e9e6'];
                for (var f = 0; f < Math.round(ctx.W * ctx.H / 45); f++) {
                    var fx = ctx.rand(0, ctx.W);
                    var fy = ctx.rand(0, ctx.H);
                    var s = ctx.rand(0.5, 1.8);
                    ctx.shape(polyPath([[fx, fy], [fx + s * ctx.rand(0.8, 2), fy + s * ctx.rand(-0.5, 0.8)], [fx + s * ctx.rand(-0.2, 1), fy + s * ctx.rand(0.8, 1.8)]]), ctx.pick(palette));
                }
                return ctx.noise('fine', ctx.accent, 0.2);
        }
    }

    //----------------------------------------------------------------------------------
    // Tile and cladding

    function tileGrid(ctx, size, grout, face) {
        ctx.fill(ctx.joint);
        for (var y = grout / 2; y < ctx.H; y += size + grout) {
            for (var x = grout / 2; x < ctx.W; x += size + grout) {
                face(x, y, size, size);
            }
        }
    }

    function tiles(ctx, kind) {
        switch (kind) {
            case 'tile-stone':
            case 'tile-concrete':
            case 'tile-slate':
                // Grouped passes (one filter each) rather than two filters per tile; per-tile order is unchanged.
                var cells = [];
                tileGrid(ctx, 38, 1.6, function(x, y, w, h) {
                    ctx.rect(x, y, w, h, mix(ctx.base, ctx.accent, ctx.rand(0, 0.5)));
                    cells.push([x, y, w, h]);
                });
                ctx.filterGroup(kind === 'tile-slate' ? 'grainH' : 'mottle', kind === 'tile-slate' ? 0.8 : 0.55, function() {
                    cells.forEach(function(b) {
                        ctx.rect(b[0], b[1], b[2], b[3], ctx.accent);
                    });
                });
                ctx.filterGroup(kind === 'tile-concrete' ? 'sand' : 'speck', 0.5, function() {
                    cells.forEach(function(b) {
                        ctx.rect(b[0], b[1], b[2], b[3], shade(ctx.accent, -0.3));
                    });
                });
                cells.forEach(function(b) {
                    ctx.shadeBox('sheenD', 0.2, b);
                });
                return;
            case 'tile-marble':
                return tileGrid(ctx, 38, 1.2, function(x, y, w, h) {
                    ctx.rect(x, y, w, h, ctx.base);
                    ctx.noise('cloud', ctx.accent, 0.2, [x, y, w, h]);
                    for (var v = 0; v < 3; v++) {
                        var vx = x + ctx.rand(0, w);
                        var vd = 'M' + n(vx) + ' ' + n(y) + 'Q' + n(vx + ctx.rand(-12, 12)) + ' ' + n(y + h / 2) + ' ' + n(vx + ctx.rand(-6, 16)) + ' ' + n(y + h);
                        ctx.shape(vd, 'none', 'stroke="' + ctx.accent + '" stroke-width="2.4" opacity="0.25" filter="' + ctx.url('blur12') + '"');
                        ctx.shape(vd, 'none', 'stroke="' + shade(ctx.accent, -0.15) + '" stroke-width="' + n(ctx.rand(0.6, 1.3)) + '" opacity="0.95" filter="' + ctx.url('blur05') + '"');
                    }
                    ctx.shadeBox('sheenD', 0.3, [x, y, w, h]);
                });
            case 'tile-glazed':
            case 'terracotta':
            case 'tile-metallic':
                var glz = [];
                tileGrid(ctx, 18.5, 1.5, function(x, y, w, h) {
                    ctx.rect(x, y, w, h, mix(ctx.base, ctx.accent, ctx.rand(0, kind === 'terracotta' ? 0.9 : 0.4)), 'rx="0.6"');
                    glz.push([x, y, w, h]);
                });
                ctx.filterGroup(kind === 'terracotta' ? 'mottle' : kind === 'tile-metallic' ? 'brushV' : 'cloud', kind === 'tile-glazed' ? 0.35 : 0.5, function() {
                    glz.forEach(function(b) {
                        ctx.rect(b[0], b[1], b[2], b[3], ctx.accent);
                    });
                });
                if (kind === 'terracotta') {
                    ctx.filterGroup('sand', 0.4, function() {
                        glz.forEach(function(b) {
                            ctx.rect(b[0], b[1], b[2], b[3], shade(ctx.accent, -0.3));
                        });
                    });
                } else {
                    glz.forEach(function(b) {
                        if (kind === 'tile-metallic') {
                            ctx.shadeBox('sheenV', 0.8, b);
                        } else {
                            ctx.shadeBox('sheenD', 0.75, b, 'rx="0.6"');
                        }
                    });
                }
                return;
            case 'tile-wood':
                ctx.fill(ctx.joint);
                var planks = [];
                for (var cx = 0.6; cx < ctx.W; cx += 13.2) {
                    var y = -ctx.rand(0, 40);
                    while (y < ctx.H) {
                        var len = ctx.rand(36, 52);
                        ctx.rect(cx, y, 12.6, len - 1, mix(ctx.base, ctx.accent, ctx.rand(0, 0.5)));
                        planks.push([cx, y, 12.6, len - 1]);
                        y += len;
                    }
                }
                ctx.filterGroup('grainV', 0.6, function() {
                    planks.forEach(function(b) {
                        ctx.rect(b[0], b[1], b[2], b[3], ctx.accent);
                    });
                });
                return ctx.shadeBox('sheenD', 0.15);
            case 'subway':
                ctx.fill(ctx.joint);
                for (var sy = 0.6, sr = 0; sy < ctx.H; sy += 9.4, sr++) {
                    for (var sx = -(sr % 2) * 9.6; sx < ctx.W; sx += 19.2) {
                        ctx.rect(sx + 0.6, sy, 18, 8.8, mix(ctx.base, ctx.accent, ctx.rand(0, 0.3)), 'rx="0.8"');
                        ctx.rect(sx + 0.6, sy, 18, 8.8, ctx.url('topLight'), 'rx="0.8" opacity="0.8"');
                        ctx.rect(sx + 1.4, sy + 0.8, 16.4, 1, '#fff', 'opacity="0.7"');
                    }
                }
                return;
            case 'hexagon':
                ctx.fill(ctx.joint);
                var s = 6.8;
                var hh = Math.sqrt(3) * s;
                for (var col = 0, hx = 0; hx < ctx.W + s * 2; col++, hx += s * 1.5) {
                    for (var hy = (col % 2) * hh / 2 - hh; hy < ctx.H + hh; hy += hh) {
                        var pts = [];
                        for (var a = 0; a < 6; a++) {
                            var ang = Math.PI / 3 * a;
                            pts.push([hx + Math.cos(ang) * (s - 0.7), hy + Math.sin(ang) * (s - 0.7)]);
                        }
                        ctx.shape(polyPath(pts), ctx.r() < 0.2 ? ctx.accent : mix(ctx.base, ctx.accent, ctx.rand(0, 0.08)));
                        ctx.shape(polyPath(pts), ctx.url('sheenD'), 'opacity="0.4"');
                    }
                }
                return;
            case 'chevron':
                ctx.fill(ctx.joint);
                var cw = isPostBox(ctx) ? ctx.W / Math.max(2, Math.round(ctx.W / 13)) : 13;
                for (var ccol = 0, chx = 0; chx < ctx.W; ccol++, chx += cw) {
                    var dir = ccol % 2 ? -1 : 1;
                    for (var chy = -26; chy < ctx.H + 26; chy += 9) {
                        var y0 = chy + (dir < 0 ? cw : 0);
                        var p = [[chx + 0.5, y0], [chx + cw - 0.5, y0 - dir * cw], [chx + cw - 0.5, y0 - dir * cw + 8.2], [chx + 0.5, y0 + 8.2]];
                        ctx.shape(polyPath(p), mix(ctx.base, ctx.accent, ctx.rand(0, 0.8)));
                        ctx.shape(polyPath(p), ctx.accent, 'filter="' + ctx.url('grainH') + '" opacity="0.35"');
                    }
                }
                return;
            case 'fish-scale':
                ctx.fill(ctx.joint);
                var R = 8;
                for (var fy = -R, fr = 0; fy < ctx.H + R * 2; fy += R * 0.9, fr++) {
                    for (var fx = (fr % 2) * R - R; fx < ctx.W + R * 2; fx += R * 2) {
                        ctx.ellipse(fx, fy, R - 0.5, R - 0.5, mix(ctx.base, ctx.accent, ctx.rand(0, 0.8)), 'stroke="' + ctx.joint + '" stroke-width="1"');
                        ctx.ellipse(fx, fy, R - 0.5, R - 0.5, ctx.url('dome'), 'opacity="0.55"');
                    }
                }
                return;
            case 'encaustic':
                return tileGrid(ctx, 26, 0.8, function(x, y, w, h) {
                    var cx = x + w / 2;
                    var cy = y + h / 2;
                    ctx.rect(x, y, w, h, ctx.base);
                    [[x, y], [x + w, y], [x, y + h], [x + w, y + h]].forEach(function(c) {
                        ctx.shape('M' + n(c[0]) + ' ' + n(c[1] + (c[1] > y ? -7 : 7)) + 'A7 7 0 0 ' + ((c[0] > x) === (c[1] > y) ? 1 : 0) + ' ' + n(c[0] + (c[0] > x ? -7 : 7)) + ' ' + n(c[1]) + 'L' + n(c[0]) + ' ' + n(c[1]) + 'Z', ctx.joint);
                    });
                    [[0, -1], [1, 0], [0, 1], [-1, 0]].forEach(function(dir) {
                        ctx.ellipse(cx + dir[0] * 4.6, cy + dir[1] * 4.6, dir[0] ? 4.4 : 2.6, dir[0] ? 2.6 : 4.4, ctx.accent);
                    });
                    ctx.shape(polyPath([[cx, cy - 2.6], [cx + 2.6, cy], [cx, cy + 2.6], [cx - 2.6, cy]]), ctx.joint);
                    ctx.noise('fine', '#000', 0.12, [x, y, w, h]);
                });
            case 'zellige':
                ctx.fill(ctx.joint);
                for (var zy = 0.8; zy < ctx.H; zy += 13.4) {
                    for (var zx = 0.8; zx < ctx.W; zx += 13.4) {
                        var zrot = 'transform="rotate(' + n(ctx.rand(-3, 3)) + ' ' + n(zx + 6) + ' ' + n(zy + 6) + ')"';
                        ctx.rect(zx + ctx.rand(-0.4, 0.4), zy + ctx.rand(-0.4, 0.4), 12, 12, mix(ctx.base, ctx.accent, ctx.rand(0, 0.9)), 'rx="1" ' + zrot);
                        ctx.rect(zx, zy, 12, 12, ctx.url(ctx.r() < 0.5 ? 'sheenD' : 'dome'), 'rx="1" opacity="0.7" ' + zrot);
                    }
                }
                return;
            case 'mosaic':
            case 'glass-mosaic':
                var glass = kind === 'glass-mosaic';
                var m = glass ? 9 : 7;
                var palette = [ctx.base, ctx.accent, mix(ctx.base, ctx.accent, 0.5), shade(ctx.base, -0.18), shade(ctx.accent, 0.12)];
                ctx.fill(ctx.joint);
                for (var my = 0.5; my < ctx.H; my += m + 0.9) {
                    for (var mx = 0.5; mx < ctx.W; mx += m + 0.9) {
                        ctx.rect(mx, my, m, m, ctx.pick(palette), glass ? 'rx="0.8"' : '');
                        if (glass) {
                            ctx.rect(mx, my, m, m, ctx.url('sheenD'), 'rx="0.8" opacity="0.9"');
                            ctx.rect(mx + 1, my + 1, m * 0.35, 1, '#fff', 'opacity="0.8"');
                        } else {
                            ctx.noise('grain', '#000', 0.2, [mx, my, m, m]);
                        }
                    }
                }
                return;
            case 'pebble-mosaic':
                ctx.fill(ctx.joint);
                ctx.noise('dense', shade(ctx.joint, -0.15), 0.5);
                discs(ctx, 130, 1.8, 3.8, 0).forEach(function(d) {
                    var rot = 'transform="rotate(' + n(ctx.rand(0, 180)) + ' ' + n(d.x) + ' ' + n(d.y) + ')"';
                    ctx.ellipse(d.x, d.y, d.r * 1.25, d.r * 0.7, mix(ctx.base, ctx.accent, ctx.rand(0, 1)), rot);
                    ctx.ellipse(d.x, d.y, d.r * 1.25, d.r * 0.7, ctx.url('dome'), 'opacity="0.7" ' + rot);
                });
                return;
            case 'panel-3d':
                var p3 = isPostBox(ctx) ? ctx.W / Math.max(1, Math.round(ctx.W / 20)) : 20;
                for (var ty = 0; ty < ctx.H; ty += p3) {
                    for (var tx = 0; tx < ctx.W; tx += p3) {
                        var c = [tx + p3 / 2, ty + p3 / 2];
                        ctx.shape(polyPath([[tx, ty], [tx + p3, ty], c]), shade(ctx.base, 0.25));
                        ctx.shape(polyPath([[tx + p3, ty], [tx + p3, ty + p3], c]), mix(ctx.base, ctx.accent, 0.55));
                        ctx.shape(polyPath([[tx, ty + p3], [tx + p3, ty + p3], c]), shade(ctx.accent, -0.12));
                        ctx.shape(polyPath([[tx, ty], [tx, ty + p3], c]), ctx.base);
                    }
                }
                return ctx.noise('fine', ctx.accent, 0.15);
            case 'shiplap':
                ctx.fill(ctx.base);
                for (var ly = 0; ly < ctx.H; ly += 13) {
                    ctx.rect(0, ly, ctx.W, 13, mix(ctx.base, ctx.accent, ctx.rand(0, 0.35)));
                    ctx.noise('grainH', ctx.accent, 0.3, [0, ly, ctx.W, 13]);
                    ctx.rect(0, ly, ctx.W, 1.4, ctx.joint);
                    ctx.rect(0, ly + 1.4, ctx.W, 1.6, '#000', 'opacity="0.12"');
                }
                return;
            case 'acp':
                ctx.fill(ctx.joint);
                for (var ay = 0; ay < ctx.H; ay += 40) {
                    for (var ax = 0; ax < ctx.W; ax += 40) {
                        ctx.rect(ax + 1.5, ay + 1.5, 37, 37, ctx.base);
                        ctx.noise('brushV', ctx.accent, 0.35, [ax + 1.5, ay + 1.5, 37, 37]);
                        ctx.shadeBox('sheenD', 0.6, [ax + 1.5, ay + 1.5, 37, 37]);
                    }
                }
                return;
        }
    }


    //----------------------------------------------------------------------------------
    // Australian walls, claddings and screens

    // Bagged brick: the courses still read through a thin painted coat of render.
    function bagged(ctx) {
        ctx.fill(shade(ctx.base, -0.1));
        brickBoxes(ctx, { w: 23, h: 7.2, hw: 11, joint: 1.6, bond: 'running' }).forEach(function(b) {
            ctx.rect(b.x, b.y, b.w, b.h, mix(ctx.base, ctx.accent, ctx.rand(0, 0.25)), 'rx="0.8"');
        });
        ctx.noise('dense', ctx.accent, 0.4);
        ctx.noise('mottle', shade(ctx.base, 0.15), 0.5);
        ctx.noise('cloud', ctx.accent, 0.25);
    }

    // Panel walls (AAC, lightweight render): a fine rendered face with the panel joints just showing.
    function panelJoints(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('dense', ctx.accent, 0.35);
        ctx.noise('cloud', ctx.accent, 0.3);
        if (isPostBox(ctx)) {
            for (var y = ctx.rand(55, 95); y < ctx.H; y += 95) {
                ctx.rect(0, y, ctx.W, 0.9, ctx.joint, 'opacity="0.5"');
                ctx.rect(0, y + 0.9, ctx.W, 0.7, '#fff', 'opacity="0.4"');
            }
            return;
        }
        for (var x = ctx.rand(8, 20); x < ctx.W; x += 30) {
            ctx.rect(x, 0, 0.9, ctx.H, ctx.joint, 'opacity="0.55"');
            ctx.rect(x + 0.9, 0, 0.7, ctx.H, '#fff', 'opacity="0.4"');
        }
    }

    // Precast (tilt-up) panels: smooth concrete, panel joints and cast-in lifting points.
    function precast(ctx) {
        concrete(ctx, { mottle: 0.45, grain: 0.3, pores: 0.25 });
        var post = isPostBox(ctx);
        if (!post) {
            for (var x = 44; x < ctx.W; x += 50) {
                ctx.rect(x, 0, 1.4, ctx.H, ctx.joint, 'opacity="0.75"');
                ctx.rect(x + 1.4, 0, 0.8, ctx.H, '#fff', 'opacity="0.3"');
            }
        }
        for (var y = post ? 80 : 58; y < ctx.H; y += post ? 85 : 70) {
            ctx.rect(0, y, ctx.W, 1.4, ctx.joint, 'opacity="0.75"');
            if (post) {
                ctx.rect(0, y + 1.4, ctx.W, 0.8, '#fff', 'opacity="0.3"');
            }
        }
        if (!post) {
            for (var ly = 14; ly < ctx.H; ly += 70) {
                for (var lx = 12; lx < ctx.W; lx += 50) {
                    ctx.ellipse(lx, ly, 2.2, 2.2, shade(ctx.base, -0.2));
                    ctx.ellipse(lx, ly, 1.2, 1.2, ctx.joint);
                }
            }
        }
    }

    // Off-form concrete: each plywood sheet's outline and tone cast into a smooth face.
    function formPly(ctx) {
        for (var y = 0, row = 0; y < ctx.H; y += 60, row++) {
            for (var x = (row % 2) * -20; x < ctx.W; x += 40) {
                ctx.rect(x, y, 40, 60, mix(ctx.base, ctx.accent, ctx.rand(0, 0.35)));
                ctx.rect(x, y, 40, 0.8, ctx.joint, 'opacity="0.45"');
                ctx.rect(x, y, 0.8, 60, ctx.joint, 'opacity="0.45"');
            }
        }
        ctx.noise('mottle', ctx.accent, 0.4);
        ctx.noise('sand', shade(ctx.accent, -0.3), 0.3);
    }

    // Stencilled concrete: a brick pattern pressed into coloured concrete, the grout lines left pale.
    function stencilBrick(ctx) {
        ctx.fill(ctx.joint);
        ctx.noise('dense', shade(ctx.joint, -0.15), 0.4);
        brickBoxes(ctx, { w: 24, h: 11, hw: 12, joint: 2.2, bond: 'running' }).forEach(function(b) {
            ctx.rect(b.x, b.y, b.w, b.h, mix(ctx.base, ctx.accent, ctx.rand(0, 0.3)));
        });
        ctx.noise('mottle', ctx.accent, 0.5);
        ctx.noise('sand', shade(ctx.accent, -0.3), 0.45);
    }

    // Limewash: chalky, cloudy colour with the brush's sweeps left in it.
    function limewash(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('cloud', ctx.accent, 0.65);
        for (var i = 0; i < 14; i++) {
            var x = ctx.rand(-20, ctx.W - 20);
            var y = ctx.rand(-10, ctx.H);
            ctx.shape('M' + n(x) + ' ' + n(y) + 'q' + n(ctx.rand(10, 20)) + ' ' + n(ctx.rand(-14, -6)) + ' ' + n(ctx.rand(30, 50)) + ' ' + n(ctx.rand(-4, 4)), 'none', 'stroke="' + (ctx.r() < 0.5 ? ctx.accent : shade(ctx.base, 0.2)) + '" stroke-width="' + n(ctx.rand(4, 9)) + '" stroke-linecap="round" opacity="0.22" filter="' + ctx.url('blur12') + '"');
        }
        ctx.noise('fine', ctx.accent, 0.2);
    }

    // Honed concrete: ground back so the aggregate shows cut flat, with no pebble relief.
    function honed(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, 0.35);
        var palette = [ctx.accent, ctx.joint, shade(ctx.accent, -0.2), mix(ctx.base, ctx.joint, 0.5), shade(ctx.base, -0.25)];
        discs(ctx, isPostBox(ctx) ? 150 : 90, isPostBox(ctx) ? 0.9 : 1.2, isPostBox(ctx) ? 2.2 : 4, 0.1).forEach(function(d) {
            var pts = [];
            for (var a = 0; a < 6; a++) {
                var ang = a / 6 * Math.PI * 2 + ctx.rand(-0.35, 0.35);
                pts.push([d.x + Math.cos(ang) * d.r * ctx.rand(0.6, 1.1), d.y + Math.sin(ang) * d.r * ctx.rand(0.6, 1.1)]);
            }
            ctx.shape(polyPath(pts), ctx.pick(palette));
        });
        ctx.shadeBox(isPostBox(ctx) ? 'sheenV' : 'sheenD', 0.3);
    }

    // Retaining-wall blocks: split faces under a bevelled lip, laid in staggered courses.
    function retainingBlock(ctx) {
        ctx.fill(ctx.joint);
        var bw = 30;
        var bh = 15;
        var j = 1.6;
        for (var row = 0, y = j / 2; y < ctx.H; row++, y += bh + j) {
            for (var x = -(row % 2) * (bw + j) / 2 - 4; x < ctx.W; x += bw + j) {
                ctx.rect(x, y, bw, bh, mix(ctx.base, ctx.accent, ctx.rand(0, 0.5)), 'rx="1.5"');
                ctx.noise('dense', shade(ctx.accent, -0.2), 0.7, [x, y, bw, bh]);
                ctx.noise('speck', shade(ctx.accent, -0.4), 0.8, [x, y, bw, bh]);
                ctx.rect(x, y, bw, 3, '#fff', 'opacity="0.22" rx="1.5"');
                ctx.rect(x, y + bh - 2.5, bw, 2.5, '#000', 'opacity="0.25"');
                ctx.shadeBox('convex', 0.35, [x, y, bw, bh]);
            }
        }
    }

    // Sleepers stacked on edge: long timbers with dark gaps between them.
    function sleepers(ctx) {
        ctx.fill(ctx.joint);
        for (var y = -ctx.rand(0, 8); y < ctx.H; y += 16) {
            ctx.rect(0, y + 0.8, ctx.W, 14.4, mix(ctx.base, ctx.accent, ctx.rand(0, 0.5)), 'rx="1.5"');
            ctx.noise('grainH', ctx.accent, 0.6, [0, y + 0.8, ctx.W, 14.4]);
            ctx.rect(0, y + 0.8, ctx.W, 2, '#fff', 'opacity="0.18"');
            ctx.rect(0, y + 13, ctx.W, 2.2, '#000', 'opacity="0.22"');
        }
        ctx.noise('mottle', shade(ctx.base, -0.3), 0.3);
    }

    // Corrugated sheet: rounded ribs every few millimetres, lit on one side; `rusty` weathers it first.
    function corrugated(ctx, rusty) {
        ctx.fill(ctx.base);
        if (rusty) {
            ctx.noise('cloud', ctx.joint, 0.7);
            ctx.noise('blotch', ctx.accent, 0.7);
            ctx.noise('speck', shade(ctx.accent, -0.4), 0.7);
            ctx.noise('sand', ctx.joint, 0.5);
        } else {
            ctx.noise('mottle', ctx.accent, 0.35);
        }
        for (var x = -ctx.rand(0, 7); x < ctx.W; x += 7.6) {
            ctx.shadeBox('cyl', 0.95, [x, 0, 7.6, ctx.H]);
        }
        ctx.noise('fine', ctx.accent, 0.2);
    }

    function circlePath(cx, cy, r) {
        return 'M' + n(cx - r) + ' ' + n(cy) + 'a' + n(r) + ' ' + n(r) + ' 0 1 0 ' + n(2 * r) + ' 0a' + n(r) + ' ' + n(r) + ' 0 1 0 ' + n(-2 * r) + ' 0Z';
    }

    // Laser-cut screen: a plate cut with repeating leaves and dots, the wall showing through the holes.
    function lasercut(ctx) {
        ctx.fill(ctx.joint);
        var d = 'M0 0H' + ctx.W + 'V' + ctx.H + 'H0Z';
        for (var y = 6, row = 0; y < ctx.H + 16; y += 16, row++) {
            for (var x = (row % 2) * 8 + 4; x < ctx.W + 16; x += 16) {
                var a = (row % 2 ? 35 : -35) * Math.PI / 180;
                var dx = Math.cos(a);
                var dy = Math.sin(a);
                var nx = -dy * 4.8;
                var ny = dx * 4.8;
                d += 'M' + n(x - dx * 6.2) + ' ' + n(y - dy * 6.2) + 'Q' + n(x + nx) + ' ' + n(y + ny) + ' ' + n(x + dx * 6.2) + ' ' + n(y + dy * 6.2) +
                    'Q' + n(x - nx) + ' ' + n(y - ny) + ' ' + n(x - dx * 6.2) + ' ' + n(y - dy * 6.2) + 'Z' + circlePath(x + 8, y + 5, 1.3);
            }
        }
        ctx.shape(d, ctx.base, 'fill-rule="evenodd"');
        ctx.shape(d, ctx.accent, 'fill-rule="evenodd" filter="' + ctx.url('mottle') + '" opacity="0.6"');
    }

    // Weldmesh: a square grid of wire welded at every crossing, the wall behind it.
    function mesh(ctx) {
        ctx.fill(ctx.joint);
        ctx.noise('mottle', shade(ctx.joint, -0.1), 0.4);
        var x;
        var y;
        for (x = 3; x < ctx.W; x += 10) {
            ctx.rect(x + 0.6, 0, 1.3, ctx.H, '#000', 'opacity="0.18"');
            ctx.rect(x, 0, 1.3, ctx.H, ctx.base);
            ctx.rect(x, 0, 0.5, ctx.H, '#fff', 'opacity="0.5"');
        }
        for (y = 3; y < ctx.H; y += 10) {
            ctx.rect(0, y + 0.6, ctx.W, 1.3, '#000', 'opacity="0.18"');
            ctx.rect(0, y, ctx.W, 1.3, ctx.base);
            ctx.rect(0, y, ctx.W, 0.5, '#fff', 'opacity="0.5"');
        }
        for (x = 3; x < ctx.W; x += 10) {
            for (y = 3; y < ctx.H; y += 10) {
                ctx.ellipse(x + 0.65, y + 0.65, 1.2, 1.2, ctx.accent);
            }
        }
    }

    // Expanded metal: slit-and-stretched sheet, diamond openings in staggered rows.
    function expanded(ctx) {
        ctx.fill(ctx.joint);
        var w = 12;
        var h = 6.5;
        for (var row = 0, y = -h; y < ctx.H + h; row++, y += h) {
            var d = '';
            for (var x = -(row % 2) * w / 2 - w; x < ctx.W + w; x += w) {
                d += (d ? 'L' : 'M') + n(x) + ' ' + n(y) + 'L' + n(x + w / 2) + ' ' + n(y + h);
            }
            ctx.shape(d, 'none', 'stroke="#000" stroke-opacity="0.25" stroke-width="2.2" transform="translate(0.6 0.8)"');
            ctx.shape(d, 'none', 'stroke="' + ctx.base + '" stroke-width="2.2" stroke-linejoin="bevel"');
            ctx.shape(d, 'none', 'stroke="#fff" stroke-opacity="0.45" stroke-width="0.6"');
        }
    }

    // Cast-iron lace (Victorian verandahs): each repeat a framed ring with four scrolls.
    function ironLace(ctx) {
        ctx.fill(ctx.joint);
        var s = ctx.W / Math.max(1, Math.round(ctx.W / 20));
        var k = s / 20;
        var stroke = 'fill="none" stroke="' + ctx.base + '" stroke-width="' + n(1.5 * k) + '" stroke-linecap="round"';
        for (var y = 0; y < ctx.H + s; y += s) {
            for (var x = 0; x < ctx.W + s; x += s) {
                var cx = x + s / 2;
                var cy = y + s / 2;
                ctx.add('<rect x="' + n(x) + '" y="' + n(y) + '" width="' + n(s) + '" height="' + n(s) + '" ' + stroke + '/>');
                ctx.add('<path d="' + circlePath(cx, cy, 3.2 * k) + '" ' + stroke + '/>');
                [[-1, -1], [1, -1], [1, 1], [-1, 1]].forEach(function(q) {
                    ctx.add('<path d="M' + n(cx + q[0] * 3.2 * k) + ' ' + n(cy) + 'Q' + n(cx + q[0] * 7.5 * k) + ' ' + n(cy + q[1] * 1 * k) + ' ' + n(cx + q[0] * 6.5 * k) + ' ' + n(cy + q[1] * 5.5 * k) +
                        'q' + n(-q[0] * 2 * k) + ' ' + n(q[1] * 2 * k) + ' ' + n(-q[0] * 3.2 * k) + ' ' + n(-q[1] * 0.6 * k) + '" ' + stroke + '/>');
                });
            }
        }
    }

    // Slat screen: horizontal slats with open gaps showing the dark behind; `timber` adds grain.
    function slatScreen(ctx, timber) {
        ctx.fill(ctx.joint);
        for (var y = 1; y < ctx.H; y += 11) {
            ctx.rect(0, y, ctx.W, 8, mix(ctx.base, ctx.accent, ctx.rand(0, timber ? 0.5 : 0.1)));
            ctx.noise(timber ? 'grainH' : 'fine', ctx.accent, timber ? 0.55 : 0.3, [0, y, ctx.W, 8]);
            ctx.shadeBox('topLight', 0.55, [0, y, ctx.W, 8]);
        }
    }

    // Grooved cladding panels: vertical V-grooves (Axon style) or wide horizontal ones (Stria style).
    function groovedPanel(ctx, horizontal) {
        ctx.fill(ctx.base);
        ctx.noise('fine', ctx.accent, 0.3);
        ctx.noise('cloud', ctx.accent, 0.2);
        for (var p = 6; p < (horizontal ? ctx.H : ctx.W); p += horizontal ? 20 : 13) {
            if (horizontal) {
                ctx.rect(0, p, ctx.W, 1.2, ctx.joint, 'opacity="0.7"');
                ctx.rect(0, p + 1.2, ctx.W, 0.8, '#fff', 'opacity="0.45"');
            } else {
                ctx.rect(p, 0, 1.2, ctx.H, ctx.joint, 'opacity="0.7"');
                ctx.rect(p + 1.2, 0, 0.8, ctx.H, '#fff', 'opacity="0.45"');
            }
        }
    }

    // Weatherboards: horizontal lapped boards, each shadowing the one below; `rough` for sawn texture.
    function weatherboard(ctx, rough) {
        ctx.fill(ctx.base);
        for (var y = 0; y < ctx.H; y += 12) {
            ctx.rect(0, y, ctx.W, 12, mix(ctx.base, ctx.accent, ctx.rand(0, rough ? 0.35 : 0.12)));
            ctx.noise(rough ? 'grainH' : 'fine', ctx.accent, rough ? 0.6 : 0.2, [0, y, ctx.W, 12]);
            ctx.rect(0, y, ctx.W, 1.6, ctx.joint, 'opacity="0.75"');
            ctx.rect(0, y + 1.6, ctx.W, 2.5, '#000', 'opacity="0.12"');
            ctx.rect(0, y + 10.5, ctx.W, 1.5, '#fff', 'opacity="0.3"');
        }
    }

    // Square-set panels with expressed joints (Matrix style).
    function panelGrid(ctx) {
        ctx.fill(ctx.joint);
        var s = 25;
        var j = 1.4;
        var x0 = isPostBox(ctx) ? (ctx.W - s) / 2 : j / 2;
        for (var y = j / 2; y < ctx.H; y += s + j) {
            for (var x = x0; x < ctx.W - (isPostBox(ctx) ? x0 : 0); x += s + j) {
                ctx.rect(x, y, s, s, mix(ctx.base, ctx.accent, ctx.rand(0, 0.12)));
                ctx.noise('fine', ctx.accent, 0.3, [x, y, s, s]);
                ctx.shadeBox('topLight', 0.25, [x, y, s, s]);
            }
        }
    }

    // Compressed fibre-cement sheet: a flat face on a grid of screw fixings, one sheet joint.
    function cfcSheet(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('mottle', ctx.accent, 0.35);
        ctx.noise('fine', ctx.accent, 0.3);
        for (var y = 8; y < ctx.H; y += 24) {
            for (var x = 7; x < ctx.W; x += 22) {
                ctx.ellipse(x + 0.3, y + 0.3, 1.3, 1.3, '#fff', 'opacity="0.4"');
                ctx.ellipse(x, y, 1.2, 1.2, ctx.joint);
            }
        }
        ctx.rect(ctx.W * 0.5, 0, 1, ctx.H, ctx.joint, 'opacity="0.45"');
    }

    // Standing-seam cladding: flat pans between raised seams.
    function standingSeam(ctx) {
        ctx.fill(ctx.base);
        ctx.noise('fine', ctx.accent, 0.25);
        for (var x = -ctx.rand(0, 10); x < ctx.W; x += 20) {
            ctx.shadeBox('sheenV', 0.4, [x, 0, 20, ctx.H]);
            ctx.rect(x + 18.2, 0, 1.2, ctx.H, '#fff', 'opacity="0.45"');
            ctx.rect(x + 19.4, 0, 1.6, ctx.H, '#000', 'opacity="0.35"');
        }
    }

    // Timber battens: narrow vertical boards with open gaps between them.
    function battens(ctx) {
        ctx.fill(ctx.joint);
        for (var x = -ctx.rand(0, 6); x < ctx.W; x += 12) {
            ctx.rect(x + 1.5, 0, 9, ctx.H, mix(ctx.base, ctx.accent, ctx.rand(0, 0.45)));
            ctx.noise('grainV', ctx.accent, 0.6, [x + 1.5, 0, 9, ctx.H]);
            ctx.shadeBox('rib', 0.5, [x + 1.5, 0, 9, ctx.H]);
        }
    }

    // Terracotta baguettes: round-faced rods in a row, gaps dark between them.
    function baguette(ctx) {
        ctx.fill(ctx.joint);
        for (var x = -ctx.rand(0, 5); x < ctx.W; x += 11) {
            ctx.rect(x + 1.5, 0, 8, ctx.H, mix(ctx.base, ctx.accent, ctx.rand(0, 0.6)), 'rx="4"');
            ctx.noise('dense', shade(ctx.accent, -0.2), 0.35, [x + 1.5, 0, 8, ctx.H]);
            ctx.shadeBox('cyl', 0.9, [x + 1.5, 0, 8, ctx.H]);
        }
    }

    //----------------------------------------------------------------------------------
    // Pattern registry (the `pattern` values the catalog may use)

    var PATTERNS = {
        concrete: function(c) { concrete(c); },
        polished: function(c) {
            concrete(c, { mottle: 0.45, grain: 0.2, pores: 0 });
            c.noise('speck', c.accent, 0.55);
            c.noise('sand', c.joint, 0.5);
            c.shadeBox(isPostBox(c) ? 'sheenV' : 'sheenD', 0.45);
        },
        'board-formed': boardFormed,
        'form-tie': formTie,
        sandblasted: sandblasted,
        broom: broom,
        aggregate: function(c) { aggregate(c, 'aggregate'); },
        pebble: function(c) { aggregate(c, 'pebble'); },
        'pebble-dash': function(c) { aggregate(c, 'pebble-dash'); },
        fluted: function(c) { fluted(c, 8); },
        'fluted-fine': function(c) { fluted(c, 5); },
        'fluted-wpc': function(c) { fluted(c, 7, { wood: true, groove: true }); },
        'stamped-slate': stampedSlate,
        'acid-stain': acidStain,
        terrazzo: terrazzo,
        microcement: function(c) { plasterFamily(c, 'microcement'); },
        plaster: function(c) { plasterFamily(c, 'plaster'); },
        'sand-float': function(c) { plasterFamily(c, 'sand-float'); },
        'skip-trowel': function(c) { plasterFamily(c, 'skip-trowel'); },
        knockdown: function(c) { plasterFamily(c, 'knockdown'); },
        venetian: function(c) { plasterFamily(c, 'venetian'); },
        'block-render': blockRender,
        paint: paint,

        granite: granite,
        marble: marble,
        travertine: travertine,
        limestone: limestone,
        sandstone: sandstone,
        slate: function(c) { slate(c, false); },
        'slate-multi': function(c) { slate(c, true); },
        basalt: basalt,
        quartzite: quartzite,
        'river-stone': function(c) { stones(c, 'river-stone'); },
        fieldstone: function(c) { stones(c, 'fieldstone'); },
        ledgestone: function(c) { stones(c, 'ledgestone'); },
        drystack: function(c) { stones(c, 'drystack'); },
        flagstone: flagstone,
        rubble: function(c) { stones(c, 'rubble'); },
        ashlar: function(c) { ashlar(c); },
        'ashlar-pitted': function(c) { ashlar(c, { pits: true }); },
        'ashlar-sandstone': function(c) { ashlar(c, { bands: true }); },
        bagged: bagged,
        'panel-joints': panelJoints,
        precast: precast,
        'form-ply': formPly,
        'stencil-brick': stencilBrick,
        limewash: limewash,
        honed: honed,
        'retaining-block': retainingBlock,
        sleepers: sleepers,
        corrugated: function(c) { corrugated(c, false); },
        'corrugated-rust': function(c) { corrugated(c, true); },
        lasercut: lasercut,
        mesh: mesh,
        expanded: expanded,
        'iron-lace': ironLace,
        'slat-screen': function(c) { slatScreen(c, false); },
        'slat-screen-timber': function(c) { slatScreen(c, true); },
        'grooved-v': function(c) { groovedPanel(c, false); },
        'grooved-h': function(c) { groovedPanel(c, true); },
        weatherboard: function(c) { weatherboard(c, false); },
        'weatherboard-rough': function(c) { weatherboard(c, true); },
        'panel-grid': panelGrid,
        'cfc-sheet': cfcSheet,
        'standing-seam': standingSeam,
        battens: battens,
        baguette: baguette,
        'split-face': function(c) { stones(c, 'split-face'); },
        tumbled: function(c) { stones(c, 'tumbled'); },
        coral: coral,
        lava: lava,

        brick: function(c) { brickFamily(c, 'brick'); },
        'brick-herringbone': herringbone,
        block: function(c) { block(c, false); },
        'block-split': function(c) { block(c, true); },
        breeze: breeze,

        wood: function(c) { woodFamily(c, 'wood'); },
        bamboo: bamboo,
        charred: charred,
        log: logPole,

        wpc: function(c) { composite(c, 'wpc'); }
    };

    ['brick-smooth', 'brick-tumbled', 'brick-reclaimed', 'brick-clinker', 'brick-wirecut', 'brick-glazed', 'brick-blend',
        'brick-whitewash', 'brick-smear', 'brick-painted', 'brick-roman', 'brick-stack', 'brick-flemish', 'brick-soldier',
        'brick-raked', 'brick-slip', 'brick-tuckpoint', 'brick-flecked'].forEach(function(kind) {
        PATTERNS[kind] = function(c) { brickFamily(c, kind); };
    });
    ['wood-knotty', 'wood-oak', 'wood-spotted', 'wood-cedar', 'wood-acacia', 'wood-weathered', 'barnwood', 'wood-whitewash',
        'wood-rough', 'wood-dressed', 'woodgrain-print', 'woodgrain-spotted'].forEach(function(kind) {
        PATTERNS[kind] = function(c) { woodFamily(c, kind); };
    });
    ['brushed', 'mirror', 'spangle', 'galv-matte', 'rust', 'mill-scale', 'blackened', 'primer', 'powder', 'gloss',
        'powder-texture', 'wrought', 'hammertone', 'metallic', 'oil-rubbed', 'patina', 'checker', 'perforated',
        'mill-alu', 'anodized', 'pearl'].forEach(function(kind) {
        PATTERNS[kind] = function(c) { metal(c, kind); };
    });
    ['wpc-streak', 'wpc-embossed', 'wpc-3d', 'wpc-brushed', 'wpc-sanded', 'vinyl', 'vinyl-grain', 'fibre-cement',
        'fibre-grain', 'recycled'].forEach(function(kind) {
        PATTERNS[kind] = function(c) { composite(c, kind); };
    });
    ['tile-stone', 'tile-concrete', 'tile-slate', 'tile-marble', 'tile-glazed', 'terracotta', 'tile-metallic',
        'tile-wood', 'subway', 'hexagon', 'chevron', 'fish-scale', 'encaustic', 'zellige', 'mosaic', 'glass-mosaic',
        'pebble-mosaic', 'panel-3d', 'shiplap', 'acp', 'stone-veneer', 'faux-stone'].forEach(function(kind) {
        PATTERNS[kind] = kind === 'stone-veneer' || kind === 'faux-stone'
            ? function(c) { stones(c, kind); }
            : function(c) { tiles(c, kind); };
    });

    //----------------------------------------------------------------------------------
    // Output

    var cache = {};

    function body(entry, w, h) {
        var key = (entry && entry.id) + '|' + w + '|' + h;
        if (!cache[key]) {
            var ctx = new Ctx(entry, w, h);
            (PATTERNS[entry && entry.pattern] || PATTERNS.concrete)(ctx);
            cache[key] = { markup: ctx.out.join(''), used: Object.keys(ctx.used) };
        }
        return cache[key];
    }

    /** An in-page <svg> drawn in a w x h box (default 80), cropped to fill its element; uses the shared defs. */
    function svg(entry, opts) {
        opts = opts || {};
        var w = opts.w || 80;
        var h = opts.h || 80;
        ensureDefs();
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="xMidYMid slice"' +
            (opts.className ? ' class="' + opts.className + '"' : '') + ' aria-hidden="true" focusable="false">' + body(entry, w, h).markup + '</svg>';
    }

    /** The finish's drawing only, for nesting inside another SVG (the drawer's match preview). */
    function inner(entry, w, h) {
        ensureDefs();
        return body(entry, w, h).markup;
    }

    /** A self-contained data: URI (its defs copied in), for CSS backgrounds such as the drawn posts. */
    function dataUri(entry, opts) {
        opts = opts || {};
        var w = opts.w || 80;
        var h = opts.h || 80;
        var b = body(entry, w, h);
        var doc = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + w + ' ' + h + '" width="' + w + '" height="' + h + '" preserveAspectRatio="xMidYMid slice">' +
            '<defs>' + b.used.map(defMarkup).join('') + '</defs>' + b.markup + '</svg>';
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(doc);
    }

    global.FCPostFinishSwatches = {
        svg: svg,
        inner: inner,
        dataUri: dataUri,
        ensureDefs: ensureDefs,
        patterns: function() {
            return Object.keys(PATTERNS);
        }
    };
})(typeof window !== 'undefined' ? window : this);
