<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): void
    {
        self::includeFile(self::resolve($template), $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        ob_start();
        self::includeFile(self::resolve($template), $data);

        return (string) ob_get_clean();
    }

    private static function resolve(string $template): string
    {
        $template = str_replace(['\\', '..'], ['/', ''], $template);
        $file     = FC_ADMIN_ROOT . '/views/' . ltrim($template, '/');

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
