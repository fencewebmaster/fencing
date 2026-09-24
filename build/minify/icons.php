<?php
/**
 * Font Awesome subset for the public pages. Run by build.php (or on its own:
 * php build/minify/icons.php). Needs Python 3 with fontTools (pip install fonttools).
 *
 * The public pages use a few dozen of Font Awesome's ~1,900 icons, but loaded the whole thing: a 100 KB
 * render-blocking stylesheet and 170 KB of fonts. This writes, beside the originals:
 *
 * - css/subset.min.css           render-blocking: the base rules, the icons the sources use, and a subset
 *                                font face declared after the full one with a unicode-range, so browsers
 *                                fetch the small file and only touch the full font for an unlisted icon.
 * - css/icons.min.css            every icon's class rule, loaded deferred: an icon added since the last
 *                                build still appears (from the full font), just after first paint.
 * - webfonts/*.subset.woff       the subset fonts (WOFF, as fontTools needs the brotli module for WOFF2).
 *
 * The admin keeps css/all.min.css untouched.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$faDir = $root . '/public/assets/fonts/fa';
$python = getenv('PYTHON_BIN') ?: 'python';

exec(escapeshellarg($python) . ' -c "import fontTools" 2>&1', $probe, $probeCode);
if ($probeCode !== 0) {
    fwrite(STDERR, "icons: skipped - Python with fontTools not found (pip install fonttools, or set PYTHON_BIN). The existing subset is kept.\n");
    return;
}

// 1. Split the stock stylesheet into its top-level blocks.
$css = (string) file_get_contents($faDir . '/css/all.min.css');
$blocks = [];
$depth = 0;
$start = 0;
$quote = '';
for ($i = 0, $n = strlen($css); $i < $n; $i++) {
    $c = $css[$i];
    if ($quote !== '') {
        if ($c === '\\') {
            $i++;
        } elseif ($c === $quote) {
            $quote = '';
        }
        continue;
    }
    if ($c === '"' || $c === "'") {
        $quote = $c;
    } elseif ($c === '/' && ($css[$i + 1] ?? '') === '*') {
        $end = strpos($css, '*/', $i + 2);
        $i = $end === false ? $n : $end + 1;
    } elseif ($c === '{') {
        $depth++;
    } elseif ($c === '}' && --$depth === 0) {
        $blocks[] = trim(substr($css, $start, $i - $start + 1));
        $start = $i + 1;
    }
}
preg_match('#^/\*!.*?\*/#s', ltrim($css), $license);
$license = $license[0] ?? '';

// 2. Which icons the public pages can show: class names in the frontend sources, plus glyphs CSS draws by codepoint.
$iconRules = [];
$nameToCode = [];
foreach ($blocks as $idx => $block) {
    $block = preg_replace('#^/\*!.*?\*/#s', '', $block);
    if (preg_match('/^((?:\.fa-[a-z0-9-]+:(?:before|after),?)+)\{content:"\\\\([0-9a-f]+)"\}$/', $block, $m)) {
        preg_match_all('/\.fa-([a-z0-9-]+):/', $m[1], $names);
        $iconRules[$idx] = ['names' => $names[1], 'code' => $m[2]];
        foreach ($names[1] as $name) {
            $nameToCode[$name] = $m[2];
        }
    }
}

$usedNames = [];
$usedCodes = [];
foreach (['app/views/frontend', 'public/assets/js/frontend', 'public/assets/css/frontend'] as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $path = (string) $file;
        if (!preg_match('/\.(php|js|css)$/', $path)) {
            continue;
        }
        $src = (string) file_get_contents($path);
        preg_match_all('/\bfa-([a-z0-9]+(?:-[a-z0-9]+)*)/', $src, $m);
        foreach ($m[1] as $name) {
            if (isset($nameToCode[$name])) {
                $usedNames[$name] = true;
                $usedCodes[$nameToCode[$name]] = true;
            }
        }
        if (str_ends_with($path, '.css')) {
            preg_match_all('/content:\s*"\\\\(f[0-9a-f]{3,4})"/i', $src, $m);
            foreach ($m[1] as $code) {
                $usedCodes[strtolower($code)] = true;
            }
        }
    }
}
$codes = array_keys($usedCodes);
sort($codes);

// 3. Subset the two faces the public pages use (solid 900, regular 400); read back which glyphs each kept.
$faces = [];
foreach (['fa-solid-900', 'fa-regular-400'] as $font) {
    $out = $faDir . '/webfonts/' . $font . '.subset.woff';
    $cmd = escapeshellarg($python) . ' -m fontTools.subset ' . escapeshellarg($faDir . '/webfonts/' . $font . '.ttf')
        . ' --unicodes=' . escapeshellarg(implode(',', $codes)) . ' --flavor=woff --output-file=' . escapeshellarg($out) . ' 2>&1';
    exec($cmd, $output, $code);
    if ($code !== 0) {
        fwrite(STDERR, "icons: subsetting $font failed\n" . implode("\n", $output) . "\n");
        exit(1);
    }
    $output = [];
    // No " % or ! in the snippet: Windows escapeshellarg() blanks those characters.
    $read = escapeshellarg($python) . ' -c ' . escapeshellarg("import sys; from fontTools.ttLib import TTFont; print(','.join(format(c, 'x') for c in sorted(TTFont(sys.argv[1]).getBestCmap())))") . ' ' . escapeshellarg($out);
    exec($read, $cmap, $code);
    $faces[$font] = ['kept' => array_filter(explode(',', trim(implode('', $cmap)))), 'bytes' => filesize($out)];
    $cmap = [];
}

// 4. Write the two stylesheets.
$fontFor = ['900' => 'fa-solid-900', '400' => 'fa-regular-400'];
$subset = [];
$all = [];
foreach ($blocks as $idx => $block) {
    $bare = preg_replace('#^/\*!.*?\*/#s', '', $block);
    if (isset($iconRules[$idx])) {
        $all[] = $bare;
        if (array_intersect($iconRules[$idx]['names'], array_keys($usedNames)) || in_array($iconRules[$idx]['code'], $codes, true)) {
            $subset[] = $bare;
        }
        continue;
    }
    $subset[] = $bare;
    // The subset face goes after the full one: for its codepoints the later face wins, and the full font
    // is only fetched if something draws a glyph outside that range.
    if (str_starts_with($bare, '@font-face') && str_contains($bare, '"Font Awesome 6 Free"')
        && preg_match('/font-weight:(900|400)/', $bare, $w) && isset($fontFor[$w[1]]) && $faces[$fontFor[$w[1]]]['kept']) {
        $font = $fontFor[$w[1]];
        $range = implode(',', array_map(static fn (string $c): string => 'U+' . $c, $faces[$font]['kept']));
        $subset[] = '@font-face{font-family:"Font Awesome 6 Free";font-style:normal;font-weight:' . $w[1]
            . ';font-display:block;src:url(../webfonts/' . $font . '.subset.woff) format("woff");unicode-range:' . $range . '}';
    }
}
$note = "/* Generated by build/minify/icons.php - do not edit. */\n";
file_put_contents($faDir . '/css/subset.min.css', $license . "\n" . $note . implode('', $subset) . "\n");
file_put_contents($faDir . '/css/icons.min.css', $license . "\n" . $note . implode('', $all) . "\n");

printf(
    "icons: %d icons (%d codepoints) - subset.min.css %d KB, icons.min.css %d KB, fonts %s\n",
    count($usedNames),
    count($codes),
    filesize($faDir . '/css/subset.min.css') / 1024,
    filesize($faDir . '/css/icons.min.css') / 1024,
    implode(', ', array_map(static fn (string $f, array $d): string => $f . ' ' . round($d['bytes'] / 1024, 1) . ' KB (' . count($d['kept']) . ' glyphs)', array_keys($faces), $faces))
);
