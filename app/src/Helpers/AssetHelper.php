<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

use Fc\Admin\Services\MinifyService;
use Fc\Admin\Settings\MinifySettings;

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
     * The public/assets/min/ copy of a frontend or admin JS/CSS file (MinifyService) when it is at
     * least as new as its source, else the source itself: an edit that skipped the rebuild is served
     * as written, never a stale copy. Takes either contract's path and keeps its shape: app-relative
     * 'public/assets/…' (frontend) or public/-relative 'assets/…' (admin). Settings → Minify CSS & JS
     * can switch each group's copies off, which serves the sources.
     */
    public static function minified(string $file): string
    {
        if (!preg_match('#^(public/)?assets/(.+)$#', $file, $m)) {
            return $file;
        }
        $group = MinifyService::groupOf($m[2]);
        if ($group === '' || !MinifySettings::enabled($group)) {
            return $file;
        }

        $assets = FC_ROOT . '/public/assets/';
        $minTime = @filemtime($assets . 'min/' . $m[2]);

        return $minTime !== false && $minTime >= (int) @filemtime($assets . $m[2]) ? $m[1] . 'assets/min/' . $m[2] : $file;
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
