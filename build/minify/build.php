<?php
/**
 * Minify the frontend's and the admin's own JS and CSS into public/assets/min/ (same relative
 * paths), using the esbuild standalone binary. Run from the project root after editing them:
 *
 *     php build/minify/build.php
 *
 * The work itself is MinifyService::build(), which Settings → Minify CSS & JS runs from the admin
 * too, so both produce the same copies. Each file is minified on its own — no bundling — so every
 * <script> keeps its own global scope and load order exactly as before. asset() serves the copy
 * only while it is at least as new as its source (AssetHelper::minified), so an edit that skipped
 * this build is served as written, never stale.
 */

declare(strict_types=1);

use Fc\Admin\Services\MinifyService;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$result = MinifyService::build(array_keys(MinifyService::GROUPS));

foreach ($result['failed'] as $rel => $error) {
    fwrite(STDERR, "FAILED $rel\n$error\n");
}
foreach ($result['removed'] as $rel) {
    echo "removed stale min/$rel\n";
}
if (isset($result['error']) && $result['built'] === 0 && $result['failed'] === []) {
    fwrite(STDERR, $result['error'] . "\n");
    exit(1);
}

// Font Awesome trimmed to the icons the same sources use (skipped, with a warning, without Python + fontTools).
require __DIR__ . '/icons.php';

$failed = count($result['failed']);
printf(
    "%d files: %d KB -> %d KB (gzip %d KB -> %d KB)%s\n",
    $result['built'],
    $result['bytes']['source'] / 1024,
    $result['bytes']['copy'] / 1024,
    $result['bytes']['sourceGzip'] / 1024,
    $result['bytes']['copyGzip'] / 1024,
    $failed ? ", $failed FAILED" : ''
);
exit($failed ? 1 : 0);
