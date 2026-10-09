<?php
/**
 * Build writable/au.jsonl, the street list behind the planner's Address suggestions
 * (AddressLookupService), from a G-NAF release zip. Run from the project root:
 *
 *     php build/address/build.php path/to/g-naf_<release>_allstates_gda2020_psv_<n>.zip [--unzip=path/to/unzip]
 *
 * The tables are streamed out of the zip with Info-ZIP `unzip -p` (Git for Windows ships it), so the
 * ~5 GB release is never unpacked. One line per street in a suburb: current streets that have a
 * current address or are confirmed, each with the postcode most of its addresses carry. See README.md.
 */

declare(strict_types=1);

use Fc\Admin\Services\AddressLookupService;

// CLI only: a web request to this file would run the build for anyone.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/app/bootstrap.php';

ini_set('memory_limit', '2G');

$zip = null;
$unzip = 'unzip';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--unzip=')) {
        $unzip = substr($arg, 8);
    } elseif ($zip === null) {
        $zip = $arg;
    }
}
if ($zip === null || !is_file($zip)) {
    fwrite(STDERR, "Usage: php build/address/build.php <g-naf psv zip> [--unzip=path/to/unzip]\n");
    exit(1);
}

$started = microtime(true);
$say = static function (string $message) use ($started): void {
    printf("[%6.1fs] %s\n", microtime(true) - $started, $message);
};

/** Every entry name in the zip. */
$list = static function () use ($unzip, $zip): array {
    $out = [];
    exec(escapeshellarg($unzip) . ' -Z1 ' . escapeshellarg($zip), $out, $code);
    if ($code !== 0 || $out === []) {
        fwrite(STDERR, "Could not list the zip with '$unzip' (exit $code). Pass --unzip=path/to/unzip.\n");
        exit(1);
    }

    return $out;
};

/** Calls $row(array $columnsByName) for each data row of one pipe-separated table in the zip; returns the row count. */
$each = static function (string $entry, callable $row) use ($unzip, $zip): int {
    $pipe = popen(escapeshellarg($unzip) . ' -p ' . escapeshellarg($zip) . ' ' . escapeshellarg($entry), 'r');
    if (!$pipe) {
        fwrite(STDERR, "Could not read $entry\n");
        exit(1);
    }
    $header = fgets($pipe);
    if ($header === false) {
        pclose($pipe);
        fwrite(STDERR, "Empty table $entry\n");
        exit(1);
    }
    $names = explode('|', rtrim($header, "\r\n"));
    $width = count($names);
    $rows = 0;
    while (($line = fgets($pipe)) !== false) {
        $values = explode('|', rtrim($line, "\r\n"));
        if (count($values) !== $width) {
            continue;
        }
        $row(array_combine($names, $values));
        $rows++;
    }
    pclose($pipe);

    return $rows;
};

$entries = $list();
$find = static function (string $pattern) use ($entries): array {
    return array_values(array_filter($entries, static fn (string $name): bool => (bool) preg_match($pattern, $name)));
};

$release = '';
foreach ($entries as $name) {
    if (preg_match('#^G-NAF/(G-NAF [A-Z]+ \d{4})/#', $name, $m)) {
        $release = $m[1];
        break;
    }
}

// Street types: CODE is the word (STREET), NAME its abbreviation (ST). Customers type either, plus a few forms G-NAF does not list.
$abbr = [];
$typeWords = [];
foreach ($find('#/Authority_Code_STREET_TYPE_AUT_psv\.psv$#') as $entry) {
    $each($entry, static function (array $r) use (&$abbr, &$typeWords): void {
        $typeWords[$r['CODE']] = true;
        if ($r['NAME'] !== '' && $r['NAME'] !== $r['CODE']) {
            $abbr[$r['NAME']][] = $r['CODE'];
        }
    });
}
$extra = ['AVE' => 'AVENUE', 'CRES' => 'CRESCENT', 'CRS' => 'CRESCENT', 'BLVD' => 'BOULEVARD', 'STR' => 'STREET',
    'TER' => 'TERRACE', 'CRT' => 'COURT', 'PKWY' => 'PARKWAY', 'HWAY' => 'HIGHWAY', 'RDWY' => 'ROADWAY'];
foreach ($extra as $short => $word) {
    if (isset($typeWords[$word]) && !in_array($word, $abbr[$short] ?? [], true)) {
        $abbr[$short][] = $word;
    }
}

$suffixes = [];
foreach ($find('#/Authority_Code_STREET_SUFFIX_AUT_psv\.psv$#') as $entry) {
    $each($entry, static function (array $r) use (&$suffixes): void {
        $suffixes[$r['CODE']] = $r['NAME'];
    });
}
$say(count($typeWords) . ' street types, ' . count($suffixes) . ' suffixes');

$states = [];
foreach ($find('#/Standard/[A-Z]+_STATE_psv\.psv$#') as $entry) {
    $each($entry, static function (array $r) use (&$states): void {
        if ($r['DATE_RETIRED'] === '') {
            $states[$r['STATE_PID']] = $r['STATE_ABBREVIATION'];
        }
    });
}

$localities = [];
foreach ($find('#/Standard/[A-Z]+_LOCALITY_psv\.psv$#') as $entry) {
    $each($entry, static function (array $r) use (&$localities): void {
        if ($r['DATE_RETIRED'] === '') {
            $localities[$r['LOCALITY_PID']] = [$r['LOCALITY_NAME'], $r['STATE_PID'], $r['PRIMARY_POSTCODE']];
        }
    });
}
$say(count($states) . ' states, ' . count($localities) . ' localities');

// Packed as one string per street: a few hundred thousand small arrays would cost several times the memory.
$streets = [];
foreach ($find('#/Standard/[A-Z]+_STREET_LOCALITY_psv\.psv$#') as $entry) {
    $each($entry, static function (array $r) use (&$streets): void {
        if ($r['DATE_RETIRED'] === '') {
            $streets[$r['STREET_LOCALITY_PID']] = implode("\t", [
                $r['STREET_NAME'], $r['STREET_TYPE_CODE'], $r['STREET_SUFFIX_CODE'], $r['LOCALITY_PID'], $r['STREET_CLASS_CODE'],
            ]);
        }
    });
}
$say(count($streets) . ' current streets');

// Postcodes come from the addresses themselves: most localities carry no primary postcode.
$streetCounts = [];
$localityCounts = [];
foreach ($find('#/Standard/[A-Z]+_ADDRESS_DETAIL_psv\.psv$#') as $entry) {
    $rows = $each($entry, static function (array $r) use (&$streetCounts, &$localityCounts): void {
        if ($r['DATE_RETIRED'] !== '' || $r['ALIAS_PRINCIPAL'] !== 'P' || $r['POSTCODE'] === '') {
            return;
        }
        if ($r['STREET_LOCALITY_PID'] !== '') {
            $key = $r['STREET_LOCALITY_PID'] . '|' . $r['POSTCODE'];
            $streetCounts[$key] = ($streetCounts[$key] ?? 0) + 1;
        }
        $key = $r['LOCALITY_PID'] . '|' . $r['POSTCODE'];
        $localityCounts[$key] = ($localityCounts[$key] ?? 0) + 1;
    });
    $say(basename($entry) . ": $rows addresses");
}

/** The postcode most addresses carry, per street or locality pid, and each pid's address total. */
$best = static function (array $counts): array {
    $top = [];
    $winner = [];
    $total = [];
    foreach ($counts as $key => $count) {
        [$pid, $postcode] = explode('|', (string) $key);
        $total[$pid] = ($total[$pid] ?? 0) + $count;
        if (!isset($top[$pid]) || $count > $top[$pid]) {
            $top[$pid] = $count;
            $winner[$pid] = $postcode;
        }
    }

    return [$winner, $total];
};
[$streetPostcode, $streetAddresses] = $best($streetCounts);
[$localityPostcode] = $best($localityCounts);
unset($streetCounts, $localityCounts);

$records = [];
$skipped = 0;
foreach ($streets as $pid => $packed) {
    [$name, $type, $suffix, $localityPid, $class] = explode("\t", $packed);
    $locality = $localities[$localityPid] ?? null;
    $state = $locality ? ($states[$locality[1]] ?? '') : '';
    // A street nobody lives on yet is kept only when G-NAF has confirmed it.
    if ($locality === null || $state === '' || $name === '' || (!isset($streetPostcode[$pid]) && $class !== 'C')) {
        $skipped++;
        continue;
    }
    $postcode = $streetPostcode[$pid] ?? $localityPostcode[$localityPid] ?? $locality[2];
    $street = trim($name . ' ' . $type . ' ' . ($suffix !== '' ? ($suffixes[$suffix] ?? $suffix) : ''));
    $key = AddressLookupService::normalize("$street {$locality[0]} $state $postcode");
    if ($key === '' || isset($records[$key])) {
        continue;
    }
    $records[$key] = json_encode([
        'k' => $key,
        'n' => AddressLookupService::titleCase($street),
        'l' => AddressLookupService::titleCase($locality[0]),
        's' => $state,
        'p' => $postcode,
        // Addresses on the street: among equal matches the busier street ranks first, as Google's list did.
        'c' => $streetAddresses[$pid] ?? 0,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
unset($streets);
// The search compares keys with strcmp, so the lines are sorted by key, not by line (the closing quote would sort before a space).
ksort($records, SORT_STRING);
$say(count($records) . " streets kept, $skipped skipped");

$meta = json_encode([
    'k'       => AddressLookupService::META_KEY,
    'source'  => $release,
    'built'   => date('Y-m-d'),
    'streets' => count($records),
    'licence' => 'Incorporates or developed using G-NAF (c) Geoscape Australia licensed by the Commonwealth of Australia under the Open Geo-coded National Address File (G-NAF) End User Licence Agreement.',
    'abbr'    => $abbr,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$out = AddressLookupService::dataPath();
$tmp = $out . '.tmp.' . bin2hex(random_bytes(4));
$fh = fopen($tmp, 'wb');
if (!$fh) {
    fwrite(STDERR, "Could not write $tmp\n");
    exit(1);
}
fwrite($fh, $meta . "\n");
foreach ($records as $line) {
    fwrite($fh, $line . "\n");
}
fclose($fh);
if (!rename($tmp, $out)) {
    @unlink($tmp);
    fwrite(STDERR, "Could not replace $out\n");
    exit(1);
}

$say(sprintf('wrote %s (%.1f MB) from %s', $out, filesize($out) / 1048576, $release ?: basename($zip)));
