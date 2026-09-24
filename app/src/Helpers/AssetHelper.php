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
        $file = self::minified($file);

        return UrlHelper::baseUrl($file) . '?v=' . filemtime((string) realpath($file));
    }

    /**
     * The public/assets/min/ copy of a frontend JS or CSS file (build/minify/build.php) when it is at
     * least as new as its source, else the source itself: an edit that skipped the rebuild is served
     * as written, never a stale copy. Takes and returns an app-relative 'public/assets/…' path.
     */
    public static function minified(string $file): string
    {
        if (!preg_match('#^public/assets/((?:js|css)/frontend/.+\.(?:js|css))$#', $file, $m) || preg_match('#\.min\.(?:js|css)$#', $file)) {
            return $file;
        }

        $min = 'public/assets/min/' . $m[1];
        $minTime = @filemtime(FC_ROOT . '/' . $min);

        return $minTime !== false && $minTime >= (int) @filemtime(FC_ROOT . '/' . $file) ? $min : $file;
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
