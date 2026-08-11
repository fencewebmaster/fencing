<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

/**
 * Front controller for the public (non-admin) side of the app.
 *
 * Every public URL is served by the single web-root index.php: .htaccess rewrites
 * anything that isn't a real file onto it, and the path is matched against the
 * 'frontend' group in app/routes/web.php. There are no per-page scripts in the web root.
 *
 * index.php deliberately stays at the app root rather than moving under public/:
 * several helpers derive the app's mount path from dirname(SCRIPT_NAME)
 * (LookupPageModel::appBase(), NotFoundHandler::plannerUrl(), basePath() below), and a
 * front controller one directory deeper would resolve all of them to ".../public".
 */
final class FrontendApplication
{
    private Router $router;

    private static string $route = '';

    public function __construct()
    {
        $this->router = new Router();

        RouteLoader::apply('frontend', $this->router);
    }

    public static function handleWebRequest(): void
    {
        (new self())->dispatch();
    }

    /**
     * The route the current request resolved to — 'planner', 'project-plan', … and
     * '' for the home route. Views use this where they used to compare script filenames.
     */
    public static function currentRoute(): string
    {
        return self::$route;
    }

    /**
     * Web path the app is mounted at, e.g. "/wp/fence/fc" ('' when it is the domain root).
     *
     * Derived from the executing script, which is always the web-root index.php, so it is
     * unaffected by how deep the *requested* route is (/lookup/view/{slug} and /planner
     * both yield the same base — REQUEST_URI cannot do that).
     */
    public static function basePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir    = rtrim(dirname($script), '/');

        return ($dir === '' || $dir === '.') ? '' : $dir;
    }

    public function dispatch(): void
    {
        $request     = new Request();
        self::$route = self::routeTail();

        $match = $this->router->match($request->method(), self::$route);
        if ($match === null) {
            NotFoundHandler::abort('frontend');
        }

        ($match['handler'])($request, $match['params']);
    }

    /**
     * Requested path relative to the app root, without a .php suffix.
     */
    private static function routeTail(): string
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
        $base = self::basePath();

        if ($base !== '' && strncmp($path, $base, strlen($base)) === 0) {
            $path = substr($path, strlen($base));
        }

        $path = trim($path, '/');

        // Legacy "/planner.php" URLs — and any browser still running a cached copy of
        // checkout.js that POSTs to "checkout.php" — resolve to the same route.
        if (strlen($path) > 4 && strcasecmp(substr($path, -4), '.php') === 0) {
            $path = substr($path, 0, -4);
        }

        return $path === 'index' ? '' : $path;
    }
}
