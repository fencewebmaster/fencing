<?php
/**
 * FC — global view helper (Laravel-style).
 *
 * Loaded by app/bootstrap.php: a bare function cannot be autoloaded, and the
 * helper must exist before any controller runs (same precedent as the fc_*()
 * helpers in fc_functions.php, which FenceSettingsService loads explicitly).
 */

declare(strict_types=1);

use Fc\Admin\Core\View;

if (!function_exists('e')) {
    /**
     * HTML-escape a value for output (Laravel's escape helper name).
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
     * Cache-busted asset URL (Laravel's helper name) — AssetHelper::assetUrl().
     * The path stays relative to the app root, e.g. asset('public/assets/js/frontend/p1.js').
     * Like url(), REQUEST_URI-derived: correct only on routes one segment deep.
     */
    function asset(string $file): string
    {
        return \Fc\Admin\Helpers\AssetHelper::assetUrl($file);
    }
}

if (!function_exists('url')) {
    /**
     * Absolute URL off the app base (Laravel's helper name) — UrlHelper::baseUrl().
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
     * Void rather than Laravel's returnable view object: this app's route
     * handlers are declared `: void` and echo directly, so the helper is
     * called as a statement, never with `return`.
     *
     * @param array<string, mixed> $data
     */
    function view(string $name, array $data = []): void
    {
        View::render($name, $data);
    }
}
