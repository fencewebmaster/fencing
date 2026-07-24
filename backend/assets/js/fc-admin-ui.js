/**
 * FC Admin — shared UI class names (planner button styles).
 */
(function (global) {
    'use strict';

    global.FcAdminBtn = {
        primary: 'btn btn-sm btn-orange fw-semibold',
        secondary: 'btn btn-sm btn-dark fw-semibold',
        outline: 'btn btn-sm btn-orange-outline fw-semibold'
    };

    /** Resolve admin-relative URLs (works on nested routes like /admin/settings). */
    global.fcAdminUrl = function fcAdminUrl(path) {
        var base =
            (document.body && document.body.getAttribute('data-fc-admin-base')) ||
            (function () {
                var baseEl = document.querySelector('base[href]');
                return baseEl ? baseEl.getAttribute('href') || '' : '';
            })();
        base = String(base || '').replace(/\/+$/, '');
        path = String(path || '').replace(/^\/+/, '');
        return path ? base + '/' + path : base + '/';
    };

    /** Unified JSON API: api.php?module=products&action=store-products */
    global.fcApiUrl = function fcApiUrl(module, query) {
        var url = 'api.php?module=' + encodeURIComponent(String(module || ''));
        query = String(query || '').replace(/^\?/, '');
        return query ? fcAdminUrl(url + '&' + query) : fcAdminUrl(url);
    };

    /**
     * PHP date()-style format from System → Date display format.
     */
    global.fcAdminDateFormat = function fcAdminDateFormat() {
        var fromBody =
            document.body && document.body.getAttribute('data-fc-date-format');
        return String(fromBody || 'M. j, Y h:i A');
    };

    /**
     * Format a Date / timestamp / datetime string with a PHP date() pattern.
     * Supports tokens used by System date formats: Y m d j F M H h i A g
     *
     * @param {Date|number|string|null|undefined} value
     * @param {string} [format]
     * @returns {string}
     */
    global.fcFormatAdminDate = function fcFormatAdminDate(value, format) {
        if (value == null || value === '') {
            return '—';
        }

        var date;
        if (value instanceof Date) {
            date = value;
        } else if (typeof value === 'number') {
            // Unix seconds if small; milliseconds if large.
            date = new Date(value < 1e12 ? value * 1000 : value);
        } else {
            var raw = String(value).trim();
            if (/^\d+$/.test(raw)) {
                var n = parseInt(raw, 10);
                date = new Date(n < 1e12 ? n * 1000 : n);
            } else {
                date = new Date(raw.replace(' ', 'T'));
            }
        }

        if (!date || isNaN(date.getTime())) {
            return String(value);
        }

        var fmt = String(format || global.fcAdminDateFormat() || 'M. j, Y h:i A');
        var monthsShort = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        var monthsLong = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        var pad = function (num, width) {
            var s = String(num);
            while (s.length < width) {
                s = '0' + s;
            }
            return s;
        };

        var hours24 = date.getHours();
        var hours12 = hours24 % 12;
        if (hours12 === 0) {
            hours12 = 12;
        }
        var tokens = {
            Y: String(date.getFullYear()),
            m: pad(date.getMonth() + 1, 2),
            d: pad(date.getDate(), 2),
            j: String(date.getDate()),
            F: monthsLong[date.getMonth()] || '',
            M: monthsShort[date.getMonth()] || '',
            H: pad(hours24, 2),
            h: pad(hours12, 2),
            g: String(hours12),
            i: pad(date.getMinutes(), 2),
            A: hours24 >= 12 ? 'PM' : 'AM'
        };

        return fmt.replace(/Y|m|d|j|F|M|H|h|g|i|A/g, function (tok) {
            return Object.prototype.hasOwnProperty.call(tokens, tok) ? tokens[tok] : tok;
        });
    };
})(window);
