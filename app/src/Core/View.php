<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class View
{
    /**
     * Render a view from app/views/.
     *
     * Accepts a dot name ('frontend.planner.index' ->
     * views/frontend/planner/index.php) or a legacy slash path ending in .php
     * ('frontend/planner/index.php') — the extension is what disambiguates the
     * two forms, since every dot in a dot name is a directory separator.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): void
    {
        self::includeFile(self::resolve($template), $data);
    }

    /**
     * Render a view (same name forms as render()) and capture it as a string.
     *
     * @param array<string, mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        ob_start();
        self::includeFile(self::resolve($template), $data);

        return (string) ob_get_clean();
    }

    /**
     * Resolved absolute path for a view name (same dot/legacy forms as render()).
     * For templates that `include` a partial into their own scope via the global
     * view_path() helper — render()'s isolated scope would break the frontend
     * partials' shared-variable contract (head.php writing $info back, etc.).
     */
    public static function path(string $template): string
    {
        return self::resolve($template);
    }

    private static function resolve(string $template): string
    {
        // Same traversal/backslash strip that always ran on slash paths.
        // Running it before dot conversion just makes a stray '..' in a dot
        // name collapse ('a..b' -> 'ab') rather than leave an empty '//'
        // segment; traversal cannot escape views/ in either ordering.
        $template = str_replace(['\\', '..'], ['/', ''], $template);

        if (strlen($template) <= 4 || strcasecmp(substr($template, -4), '.php') !== 0) {
            $template = str_replace('.', '/', $template) . '.php';
        }

        $file = FC_ADMIN_ROOT . '/views/' . ltrim($template, '/');

        if (!is_readable($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        return $file;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function includeFile(string $file, array $data): void
    {
        extract($data, EXTR_SKIP);
        require $file;
    }
}
