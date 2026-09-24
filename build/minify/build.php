<?php
/**
 * Minify the public site's own JS and CSS into public/assets/min/ (same relative paths), using the
 * esbuild standalone binary. Run from the project root after editing frontend JS/CSS:
 *
 *     php build/minify/build.php
 *
 * Each file is minified on its own — no bundling — so every <script> keeps its own global scope and
 * load order exactly as before. asset() serves the copy only while it is at least as new as its source
 * (AssetHelper::minified), so an edit that skipped this build is served as written, never stale.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$sourceRoot = $root . '/public/assets';
$minRoot = $sourceRoot . '/min';

$esbuild = getenv('ESBUILD_BIN') ?: '';
foreach ([__DIR__ . '/esbuild.exe', __DIR__ . '/esbuild'] as $candidate) {
    if ($esbuild === '' && is_file($candidate)) {
        $esbuild = $candidate;
    }
}
if ($esbuild === '' || !is_file($esbuild)) {
    fwrite(STDERR, "esbuild binary not found: see build/minify/README.md (or set ESBUILD_BIN).\n");
    exit(1);
}

// The frontend's own sources; vendor files and anything already *.min.* ship minified.
$sources = [];
$scan = static function (string $dir, string $ext) use (&$sources, $sourceRoot): void {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        if (str_ends_with($path, '.' . $ext) && !str_ends_with($path, '.min.' . $ext)) {
            $sources[] = substr($path, strlen($sourceRoot) + 1);
        }
    }
};
$scan('js/frontend', 'js');
$scan('css/frontend', 'css');
sort($sources);

$totals = ['src' => 0, 'min' => 0, 'srcGz' => 0, 'minGz' => 0];
$failed = 0;

foreach ($sources as $rel) {
    $src = $sourceRoot . '/' . $rel;
    $out = $minRoot . '/' . $rel;
    if (!is_dir(dirname($out)) && !mkdir(dirname($out), 0775, true) && !is_dir(dirname($out))) {
        fwrite(STDERR, "Cannot create " . dirname($out) . "\n");
        exit(1);
    }

    $cmd = escapeshellarg($esbuild) . ' ' . escapeshellarg($src)
        . ' --minify --sourcemap --sources-content=false --log-level=error --outfile=' . escapeshellarg($out);
    exec($cmd . ' 2>&1', $output, $code);
    if ($code !== 0) {
        fwrite(STDERR, "FAILED $rel\n" . implode("\n", $output) . "\n");
        $failed++;
        $output = [];
        continue;
    }
    $output = [];

    if (str_ends_with($rel, '.css')) {
        // The copy sits one folder deeper (min/), so relative url()s need one more "../".
        $css = (string) file_get_contents($out);
        $css = preg_replace_callback('#url\(\s*(["\']?)(?![a-z][a-z0-9+.-]*:|/|\#)([^"\')]+)\1\s*\)#i', static fn (array $m): string => 'url(' . $m[1] . '../' . $m[2] . $m[1] . ')', $css);
        file_put_contents($out, $css);
    }

    $a = (string) file_get_contents($src);
    $b = (string) file_get_contents($out);
    $totals['src'] += strlen($a);
    $totals['min'] += strlen($b);
    $totals['srcGz'] += strlen((string) gzencode($a, 9));
    $totals['minGz'] += strlen((string) gzencode($b, 9));
}

// Drop copies whose source is gone, so public/assets/min/ mirrors the sources exactly.
foreach (['js/frontend', 'css/frontend'] as $dir) {
    if (!is_dir($minRoot . '/' . $dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($minRoot . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        $rel = preg_replace('#\.map$#', '', substr($path, strlen($minRoot) + 1));
        if (!in_array($rel, $sources, true)) {
            unlink($path);
            echo "removed stale min/$rel\n";
        }
    }
}

// Font Awesome trimmed to the icons the same sources use (skipped, with a warning, without Python + fontTools).
require __DIR__ . '/icons.php';

printf(
    "%d files: %d KB -> %d KB (gzip %d KB -> %d KB)%s\n",
    count($sources) - $failed,
    $totals['src'] / 1024,
    $totals['min'] / 1024,
    $totals['srcGz'] / 1024,
    $totals['minGz'] / 1024,
    $failed ? ", $failed FAILED" : ''
);
exit($failed ? 1 : 0);
