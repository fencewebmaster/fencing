<?php

/**
 * One-off: GO's fence heights into the Slat settings (Nov 2026).
 *
 * writable/fences/4-SLAT.php is per site and gitignored, so a deploy cannot carry its settings. Run this once on each
 * site after pulling:
 *
 *     php writable/add-slat-heights.php --dry-run     (says what would change)
 *     php writable/add-slat-heights.php
 *
 * It adds one setting, `fence_heights_mm`: every height GO's cut-to-size order form (V9.03) offers in its Height
 * dropdowns, as [slat size][gap][number of slats] => height in mm. Gaps 0, 5, 9 and 20 are its Xpress sheet, 12, 15
 * and 30 its Quickscreen sheet: the split the planner's panel height already follows. The Slat gate reads the same
 * list, and so does Slat Infill wherever it has no list of its own (it can be given one in the Fence Styles editor), so
 * this writes the Slat file only.
 *
 * The setting is the switch. With it, Fence Height (Slat, Slat Infill) and the Slat gate's Gate Height are dropdowns of
 * these heights, and a listed height is made with its listed number of slats. Without it they stay number boxes. A
 * quote saved with a typed height keeps it, as its own item in the dropdown, until another height is picked.
 *
 * Only that setting is touched. Safe to run twice: the second run reports nothing to change. A site that has edited
 * its list keeps it unless --replace is given. The file is written by the same writer the Fence Styles editor uses.
 *
 * The editor can do the same from the admin: Fence Styles, Slat, Step 2, Fence Height, Actions, Load GO's heights.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use Fc\Admin\Services\FenceFileService;
use Fc\Admin\Services\SlatHeightListService;

$dryRun  = in_array('--dry-run', $argv, true);
$replace = in_array('--replace', $argv, true);
$paths   = array_values(array_filter(array_slice($argv, 1), static fn(string $a): bool => $a !== '' && $a[0] !== '-'));
$file    = $paths[0] ?? FC_ROOT . '/writable/fences/4-SLAT.php';

if (!is_file($file)) {
    fwrite(STDERR, "Fence file not found: {$file}\n");
    exit(2);
}

$fences = [];
include $file;
if (!isset($fences['slat']) || !is_array($fences['slat'])) {
    fwrite(STDERR, "No \$fences['slat'] definition in {$file}\n");
    exit(2);
}

$config = $fences['slat'];

const FC_SLAT_HEIGHTS_KEY = SlatHeightListService::SETTING_KEY;

// GO's list is built in one place: the Fence Styles editor loads the same one (Step 2, Fence Height).
$heights = SlatHeightListService::goHeights();
$counts  = [];
foreach ($heights as $size => $byGap) {
    $counts[] = $size . 'mm slats ' . implode(' / ', array_map('count', $byGap));
}
$total   = array_sum(array_map(static fn(array $byGap): int => array_sum(array_map('count', $byGap)), $heights));
$summary = $total . ' heights (' . implode('; ', $counts) . ' for gaps ' . implode(' / ', array_keys($heights[65])) . ')';

echo basename($file), ': ';

$current = $config[FC_SLAT_HEIGHTS_KEY] ?? null;
if ($current == $heights) {
    echo "nothing to change, the setting already holds {$summary}.\n";
    exit(0);
}

if ($current !== null && !$replace) {
    echo FC_SLAT_HEIGHTS_KEY, " is already set and differs from GO's list, so it was left alone. Add --replace to overwrite it.\n";
    exit(0);
}

if ($current !== null) {
    // In place, so the setting keeps its position in the file.
    $config[FC_SLAT_HEIGHTS_KEY] = $heights;
} else {
    // Beside the gap settings it is read with; at the end where a site has none.
    $ordered = [];
    foreach ($config as $key => $value) {
        $ordered[$key] = $value;
        if ($key === 'slat_gap_pitch_mm') {
            $ordered[FC_SLAT_HEIGHTS_KEY] = $heights;
        }
    }
    $ordered[FC_SLAT_HEIGHTS_KEY] ??= $heights;
    $config = $ordered;
}

$exists = $current !== null;
echo FC_SLAT_HEIGHTS_KEY, $dryRun
    ? ($exists ? ' to replace' : ' to add') . ' (dry run, nothing written): '
    : ($exists ? ' replaced: ' : ' added: '), $summary, "\n";

if ($dryRun) {
    exit(0);
}

$result = FenceFileService::writeConfig($file, 'slat', $config);
if (empty($result['ok'])) {
    fwrite(STDERR, 'Not saved: ' . ($result['error'] ?? 'unknown error') . "\n");
    exit(1);
}

echo "Saved.\n";
