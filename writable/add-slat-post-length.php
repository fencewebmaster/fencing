<?php

/**
 * One-off: Post Length in Slat's Post Options drawer (Nov 2026).
 *
 * writable/fences/4-SLAT.php is per site and gitignored, so a deploy cannot carry its settings. Run this once on each
 * site after pulling:
 *
 *     php writable/add-slat-post-length.php --dry-run     (says what would change)
 *     php writable/add-slat-post-length.php
 *
 * It adds one field, `post_length`, as card B of Post Options: the stock posts the store sells (1800, 2400 and 6000),
 * each with its picture. The planner marks the post the rule gives as recommended, greys out the ones that cannot
 * stand for the section (too short, or a 6000 while cutting is off), and the cart bills the customer's own picks
 * (FenceCatalogService::slatPostCutForPlannerLine()). Post Options itself becomes card A.
 *
 * The field brings the choice, not the rule. With or without it a post is one whole post unless no capped post is
 * long enough; with it the customer can pick another post for a section. The Cut posts on site switch above the
 * tiles is the site's own to offer: Fence Styles, Slat, Step 3, Post Options, Post Length, Cutting (`cut_on_site`
 * on the field, off until ticked). This script never changes that choice.
 *
 * Only Post Options is touched. Safe to run twice: the second run reports nothing to change. A site that has edited
 * the field keeps it unless --replace is given. The file is written by the same writer the Fence Styles editor uses.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use Fc\Admin\Services\FenceCatalogService;
use Fc\Admin\Services\FenceFileService;

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
$fields = $config['settings']['post_options']['fields'] ?? null;
if (!is_array($fields) || !isset($fields[0]) || ($fields[0]['slug'] ?? '') !== 'post_option') {
    fwrite(STDERR, "Post Options has no post_option field first in {$file}, so the file was left alone.\n");
    exit(2);
}

const FC_SLAT_POST_LENGTH_SLUG = 'post_length';

// One tile a stock post the cutting rule knows, so the drawer never offers a length the cart cannot bill.
$options = [];
$missing = [];
foreach (FenceCatalogService::slatPostStockRule()['stocks'] as [$stock]) {
    $image = 'public/assets/img/post-options/slat-post-length-' . $stock . '.webp';
    if (!is_file(FC_ROOT . '/' . $image)) {
        $missing[] = $image;
    }
    $options[] = ['slug' => (string) $stock, 'title' => $stock . 'mm', 'image' => $image, 'extra' => ''];
}

$field = [
    'title' => 'Post Length',
    'marker' => 'B',
    'slug' => FC_SLAT_POST_LENGTH_SLUG,
    'type' => 'image_option',
    'label' => '',
    'notes' => [
        'title' => 'Choosing a post length',
        'description' => 'A post has to cover the fence height plus whatever goes in the ground. A post too short for this fence cannot be picked, and a longer one is cut or trimmed on site.',
    ],
    'options' => $options,
];

echo basename($file), ': ';

$at = null;
foreach ($fields as $index => $existing) {
    if (is_array($existing) && ($existing['slug'] ?? '') === FC_SLAT_POST_LENGTH_SLUG) {
        $at = $index;
    }
}

// The site's own Cut posts on site choice (Fence Styles) is not this script's: it rides along, so a site that only
// ticked it still reads "nothing to change" and --replace cannot switch it off.
if ($at !== null && array_key_exists('cut_on_site', $fields[$at])) {
    $field['cut_on_site'] = $fields[$at]['cut_on_site'];
}

if ($at !== null && $fields[$at] == $field) {
    echo "nothing to change, Post Options already has the Post Length card.\n";
    exit(0);
}

if ($at !== null && !$replace) {
    echo "Post Length is already in Post Options and differs from this one, so it was left alone. Add --replace to overwrite it.\n";
    exit(0);
}

if ($at !== null) {
    $fields[$at] = $field;
} else {
    $fields[] = $field;
}

// Two cards now, so Post Options takes its letter, after the title as the other drawers' fields have it.
if (($fields[0]['marker'] ?? '') === '') {
    $first = [];
    foreach ($fields[0] as $key => $value) {
        if ($key === 'marker') {
            continue;
        }
        $first[$key] = $value;
        if ($key === 'title') {
            $first['marker'] = 'A';
        }
    }
    $first['marker'] ??= 'A';
    $fields[0] = $first;
}

$config['settings']['post_options']['fields'] = $fields;

$summary = 'the ' . implode(', ', array_column($options, 'title')) . ' posts as card B of Post Options';
echo $dryRun
    ? ($at !== null ? 'Post Length to replace' : 'Post Length to add') . ' (dry run, nothing written): '
    : ($at !== null ? 'Post Length replaced: ' : 'Post Length added: '), $summary, "\n";

foreach ($missing as $image) {
    echo "  Note: {$image} is not on this site yet, so that tile would show no picture.\n";
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
