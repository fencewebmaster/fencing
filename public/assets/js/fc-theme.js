/**
 * FC — apply theme CSS variables to document root.
 */
(function (global) {
    'use strict';

    function hexToRgb(hex) {
        if (!hex || typeof hex !== 'string') {
            return null;
        }
        var value = hex.trim();
        if (value.charAt(0) === '#') {
            value = value.slice(1);
        }
        if (value.length === 3) {
            value = value.replace(/./g, function (ch) {
                return ch + ch;
            });
        }
        if (!/^[0-9a-fA-F]{6}$/.test(value)) {
            return null;
        }
        return {
            r: parseInt(value.slice(0, 2), 16),
            g: parseInt(value.slice(2, 4), 16),
            b: parseInt(value.slice(4, 6), 16)
        };
    }

    function rgba(rgb, alpha) {
        var alphaStr = String(alpha);
        if (alphaStr.indexOf('.') !== -1) {
            alphaStr = alphaStr.replace(/0+$/, '').replace(/\.$/, '');
        }
        return 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + alphaStr + ')';
    }

    function expandFcTheme(colors) {
        var expanded = Object.assign({}, colors);
        var accent = colors['--fc-princeton-orange'];
        var rgb = hexToRgb(accent);

        if (rgb) {
            expanded['--fc-a-orange-3'] = rgba(rgb, 0.031);
            expanded['--fc-a-orange-20'] = rgba(rgb, 0.2);
            expanded['--fc-a-orange-22'] = rgba(rgb, 0.22);
            expanded['--fc-a-orange-25'] = rgba(rgb, 0.25);
            expanded['--fc-a-orange-35'] = rgba(rgb, 0.35);
            expanded['--fc-a-orange-38'] = rgba(rgb, 0.38);
        }

        return expanded;
    }

    function applyFcTheme(colors) {
        if (!colors || typeof colors !== 'object') {
            return;
        }
        var root = document.documentElement;
        var expanded = expandFcTheme(colors);

        Object.keys(expanded).forEach(function (key) {
            if (key.indexOf('--fc-') === 0) {
                root.style.setProperty(key, expanded[key]);
            }
        });
    }

    function clearFcThemeInline(colors) {
        if (!colors || typeof colors !== 'object') {
            return;
        }
        var root = document.documentElement;
        var expanded = expandFcTheme(colors);

        Object.keys(expanded).forEach(function (key) {
            if (key.indexOf('--fc-') === 0) {
                root.style.removeProperty(key);
            }
        });
    }

    global.FcTheme = {
        apply: applyFcTheme,
        clearInline: clearFcThemeInline,
        expand: expandFcTheme
    };
})(window);
