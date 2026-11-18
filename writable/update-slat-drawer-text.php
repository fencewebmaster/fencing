<?php

/**
 * One-off: Slat's drawer help text (Nov 2026).
 *
 * writable/fences/4-SLAT.php is per site and gitignored, so a deploy cannot carry its wording. Run this once on each
 * site after pulling:
 *
 *     php writable/update-slat-drawer-text.php --dry-run     (lists what would change)
 *     php writable/update-slat-drawer-text.php
 *
 * It replaces text that was copied from the tubular fences and never matched Slat:
 *   - Gate drawer: "65mm x 16.5mm Slats ONLY" / "9mm or 20mm Slat Spacing" (the planner makes 90mm and every gap).
 *   - Post Options note: "1200mm", "Cemented In Posts = 50x25mm Posts" (Barr's; Slat posts are 50x50).
 *   - Edit Left / Right Side: "When To Use Swivel Brackets" (Slat has no swivel bracket option).
 *   - "Center-line" spelling beside "Centre To Centre".
 *
 * Only those texts are touched, each only while it still reads as the old wording, so a site that has reworded one
 * keeps its own. Safe to run twice: the second run reports nothing to change. The file is written by the same writer
 * the Fence Styles editor uses.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use Fc\Admin\Services\FenceFileService;

$dryRun = in_array('--dry-run', $argv, true);
$paths  = array_values(array_filter(array_slice($argv, 1), static fn(string $a): bool => $a !== '' && $a[0] !== '-'));
$file   = $paths[0] ?? FC_ROOT . '/writable/fences/4-SLAT.php';

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

$config  = $fences['slat'];
$changes = [];

const FC_SLAT_POST_NOTE_TITLE = 'Post Size';
const FC_SLAT_POST_NOTE_TEXT  = 'Slat posts are 50x50mm aluminium posts for every post option.';
const FC_SLAT_GATE_SLAT_LINE  = '&#9989; Slat size and spacing match your fence';

/** The swivel bracket note, whatever its capitalisation. */
$isSwivelNote = static function ($notes): bool {
    return is_array($notes) && stripos((string) ($notes['title'] ?? ''), 'swivel bracket') !== false;
};

/** Barr's post-size note: the 50x25 cemented post is the giveaway. */
$isTubularPostNote = static function ($notes): bool {
    return is_array($notes) && stripos((string) ($notes['description'] ?? ''), '50x25mm') !== false;
};

// Step 2 guide.
foreach ($config['form'] ?? [] as $i => $field) {
    if (is_array($field) && is_string($field['description'] ?? null) && str_contains($field['description'], 'Center-Line')) {
        $config['form'][$i]['description'] = str_replace('Center-Line', 'Centre-Line', $field['description']);
        $changes[] = 'Step 2 guide: "Center-Line" becomes "Centre-Line"';
    }
}

foreach (['left_side', 'right_side', 'post_options'] as $drawer) {
    if (!isset($config['settings'][$drawer]) || !is_array($config['settings'][$drawer])) {
        continue;
    }
    if ($isSwivelNote($config['settings'][$drawer]['notes'] ?? null)) {
        unset($config['settings'][$drawer]['notes']);
        $changes[] = "{$drawer}: swivel bracket note removed";
    }
    foreach ($config['settings'][$drawer]['fields'] ?? [] as $i => $field) {
        if (!is_array($field)) {
            continue;
        }
        if ($isSwivelNote($field['notes'] ?? null)) {
            unset($config['settings'][$drawer]['fields'][$i]['notes']);
            $changes[] = "{$drawer} / " . ($field['slug'] ?? '?') . ': swivel bracket note removed';
        }
        if (($field['slug'] ?? '') === 'post_option' && $isTubularPostNote($field['notes'] ?? null)) {
            $config['settings'][$drawer]['fields'][$i]['notes'] = [
                'title'       => FC_SLAT_POST_NOTE_TITLE,
                'description' => FC_SLAT_POST_NOTE_TEXT,
            ];
            $changes[] = "{$drawer}: post note now reads \"" . FC_SLAT_POST_NOTE_TEXT . '"';
        }
    }
}

// Gate drawer: the two lines that named one slat and two gaps become one that is true of every Slat gate.
$gateText = $config['settings']['gate']['custom_gate_details']['description'] ?? null;
if (is_string($gateText)) {
    $newGateText = preg_replace(
        '/&#9989;\s*65mm x 16\.5mm Slats ONLY\s*<br>\s*&#9989;\s*9mm or 20mm Slat Spacing/i',
        FC_SLAT_GATE_SLAT_LINE,
        $gateText
    );
    if (is_string($newGateText) && $newGateText !== $gateText) {
        $config['settings']['gate']['custom_gate_details']['description'] = $newGateText;
        $changes[] = 'gate: "65mm x 16.5mm Slats ONLY" and "9mm or 20mm Slat Spacing" become "Slat size and spacing match your fence"';
    }
}

// Width Dimension From tiles, in Gate Options and Panel Options.
foreach (['gate', 'panel_options'] as $drawer) {
    foreach ($config['settings'][$drawer]['fields'] ?? [] as $i => $field) {
        foreach ((is_array($field) ? ($field['options'] ?? []) : []) as $o => $option) {
            if (is_array($option) && ($option['title'] ?? '') === 'Center-line Width') {
                $config['settings'][$drawer]['fields'][$i]['options'][$o]['title'] = 'Centre-line Width';
                $changes[] = "{$drawer}: \"Center-line Width\" becomes \"Centre-line Width\"";
            }
        }
    }
}

echo basename($file), ': ';
if (!$changes) {
    echo "nothing to change.\n";
    exit(0);
}

echo count($changes), $dryRun ? " change(s) to make (dry run, nothing written):\n" : " change(s):\n";
foreach ($changes as $line) {
    echo '  - ', $line, "\n";
}

if ($dryRun) {
    exit(0);
}

$result = FenceFileService::writeConfig($file, 'slat', $config);
if (empty($result['ok'])) {
    fwrite(STDERR, 'Not saved: ' . ($result['error'] ?? 'unknown error') . "\n");
    exit(1);
}

echo "Saved.\n";
