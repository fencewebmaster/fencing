<?php
/**
 * FC — global view helpers.
 *
 * Loaded by app/bootstrap.php: a bare function cannot be autoloaded, and the
 * helpers must exist before any controller runs (same precedent as the fc_*()
 * helpers in fc_functions.php, which FenceSettingsService loads explicitly).
 */

declare(strict_types=1);

use Fc\Admin\Core\View;

if (!function_exists('e')) {
    /**
     * HTML-escape a value for output.
     *
     * Replaces the `$h = static fn(...) => StringHelper::escapeHtml(...)` closure
     * each view used to declare for itself — templates read, they don't define.
     */
    function e(string $value): string
    {
        return \Fc\Admin\Helpers\StringHelper::escapeHtml($value);
    }
}

if (!function_exists('view_path')) {
    /**
     * Absolute path of a view by dot name, for same-scope partial includes:
     *
     *     include view_path('frontend.partials.head');
     *
     * Deliberately include-based rather than view(): the frontend partials share
     * (and write back into) the including template's scope — head.php reassigning
     * $info is load-bearing — which an isolated view() render would break.
     */
    function view_path(string $name): string
    {
        return \Fc\Admin\Core\View::path($name);
    }
}

if (!function_exists('asset')) {
    /**
     * Cache-busted asset URL. The path shape picks the contract:
     *
     *   asset('public/assets/…')  frontend: absolute URL via AssetHelper::assetUrl() —
     *                             REQUEST_URI-derived like url(), so correct only on
     *                             routes one segment deep.
     *   asset('assets/…')         admin: the bare path plus its ?v= stamp, resolved by
     *                             the browser against the layout's <base href>.
     *
     * The two outputs are NOT interchangeable — the app has five deliberately distinct
     * base-URL builders, and pasting a frontend call into an admin view (or vice versa)
     * produces a silently wrong URL. The dispatch is deterministic: same argument, same
     * output, everywhere.
     */
    function asset(string $file): string
    {
        if (str_starts_with($file, 'public/')) {
            return \Fc\Admin\Helpers\AssetHelper::assetUrl($file);
        }

        return $file . '?v=' . \Fc\Admin\Helpers\UrlHelper::assetVersion($file);
    }
}

if (!function_exists('url')) {
    /**
     * Absolute URL off the app base — UrlHelper::baseUrl().
     * dirname(REQUEST_URI)-derived: correct only on frontend routes one segment
     * deep (/planner, /checkout); nested routes must use
     * FrontendApplication::basePath() instead.
     */
    function url(string $param = ''): string
    {
        return \Fc\Admin\Helpers\UrlHelper::baseUrl($param);
    }
}

if (!function_exists('cell')) {
    /**
     * Table-cell formatter: '—' for null/empty, HTML-escaped otherwise
     * (ViewHelper::cell). Replaces the per-view `$cell` closures the admin
     * table templates used to declare.
     */
    function cell(mixed $value): string
    {
        return \Fc\Admin\Helpers\ViewHelper::cell($value);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view by its dot name from app/views/, e.g.
     *
     *     view('frontend.planner.index', $data);
     *
     * Void by design: this app's route handlers are declared `: void` and
     * echo directly, so the helper is called as a statement, never with
     * `return`.
     *
     * @param array<string, mixed> $data
     */
    function view(string $name, array $data = []): void
    {
        View::render($name, $data);
    }
}
