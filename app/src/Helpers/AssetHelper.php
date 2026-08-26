<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Cache-busted asset URLs + deferred stylesheet tags.
 */
final class AssetHelper
{
    public static function assetUrl(string $file): string
    {
        return UrlHelper::baseUrl($file) . '?v=' . filemtime((string) realpath($file));
    }

    /**
     * Load a stylesheet without blocking first paint (noscript fallback included).
     */
    public static function deferStylesheet(string $href, bool $crossorigin = false): void
    {
        $href = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        $cross = $crossorigin ? ' crossorigin="anonymous"' : '';
        echo '<link rel="stylesheet" href="' . $href . '" media="print" onload="this.media=\'all\'"' . $cross . '>' . "\n";
        echo '<noscript><link rel="stylesheet" href="' . $href . '"' . $cross . '></noscript>' . "\n";
    }
}
