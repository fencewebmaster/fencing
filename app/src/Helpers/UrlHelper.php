<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Pure $_SERVER-derived URL utilities (config/helpers.php migration).
 */
final class UrlHelper
{
    public static function baseUrl(string $param = ''): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
        $path = dirname($_SERVER['REQUEST_URI'] . '?');

        if ($path === '\\' || $path === '.') {
            $path = '';
        }

        return sprintf(
            '%s://%s%s/%s',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $host,
            $path,
            $param
        );
    }

    public static function toUrl(string $url): string
    {
        if (isset($_SERVER['HTTPS'])) {
            $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
        } else {
            $protocol = 'https';
        }

        return $protocol . '://' . $url;
    }

    /**
     * @param list<string> $keys
     */
    public static function inUriSegment(array $keys): bool
    {
        $uriSegments = explode('/', trim((string) parse_url((string) $_SERVER['PHP_SELF'], PHP_URL_PATH), '/'));
        foreach ($uriSegments as $segment) {
            if (in_array($segment, $keys, false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string|array<string, mixed>
     */
    public static function queryVars(string $query = '')
    {
        $qs = $_SERVER['QUERY_STRING'];
        $vars = [];

        if ($query == '') {
            return $qs;
        }

        parse_str($_SERVER['QUERY_STRING'], $qs);

        foreach ($qs as $key => $value) {
            $vars[$key] = $value;

            if ($value == '0') {
                unset($vars[$key]);
            }
        }

        return $vars;
    }

    /**
     * Resolve the externally-visible admin mount base (e.g. "/wp/fence/fc/backend"),
     * recognizing known mount segments in the request URI itself rather than relying
     * on SCRIPT_NAME. This matters because /backend is served via an internal
     * `.htaccess` rewrite to `public/index.php` — Apache always reports SCRIPT_NAME as
     * the file it actually executed (`.../public/index.php`), never the URL the browser
     * used, while REQUEST_URI always preserves the original request. Deriving the base
     * from SCRIPT_NAME here would silently resolve every admin link/asset to /public/
     * instead of /backend/.
     */
    public static function resolveAdminMountBase(): string
    {
        $uri = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        $uri = str_replace('\\', '/', $uri);

        foreach (['/backend', '/public'] as $mount) {
            $pos = strpos($uri, $mount);
            if ($pos === false) {
                continue;
            }
            $end = $pos + strlen($mount);
            if ($end === strlen($uri) || $uri[$end] === '/') {
                return rtrim(substr($uri, 0, $end), '/');
            }
        }

        // Fallback for contexts with no REQUEST_URI (e.g. CLI): derive from the
        // actually-executed script, same as before this method existed.
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/fc/public/index.php'));

        return rtrim(dirname($script), '/');
    }

    /**
     * Cache-busting query string for a static asset under public/, keyed to
     * the file's own mtime so a stale browser-cached <script>/<link> tag
     * only serves outdated content until the next request after a deploy —
     * without this, editing a JS/CSS file's content (without touching the
     * HTML that references it) can leave already-loaded sessions on the old
     * version indefinitely, since the URL never changes.
     *
     * @param string $relativePath path relative to the public/ directory, e.g. "assets/js/admin/settings.js"
     */
    public static function assetVersion(string $relativePath): string
    {
        $full = dirname(__DIR__, 3) . '/public/' . ltrim($relativePath, '/');
        $mtime = @filemtime($full);

        return $mtime !== false ? (string) $mtime : '0';
    }

    /**
     * Fifth base-URL builder: the planner app base derived from the ADMIN front controller's
     * SCRIPT_NAME by climbing two directories (admin mount -> app root). Distinct from the other
     * four — baseUrl()/AssetHelper::assetUrl() use dirname(REQUEST_URI); FrontendApplication::basePath()
     * uses a single dirname(SCRIPT_NAME); resolveAdminMountBase() is REQUEST_URI-based;
     * GalleryPresenter::assetUrl()/BrandingSettings::logoUrl() are cache-bust-free media paths. Used by
     * the two admin API endpoints (fence styles, dashboard) that resolve planner asset URLs from /backend.
     */
    public static function plannerAppBaseFromAdminScript(): string
    {
        $adminBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');

        return rtrim(str_replace('\\', '/', dirname($adminBase)), '/');
    }
}
