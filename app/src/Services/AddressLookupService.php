<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Australian street suggestions for the planner's Address fields, from FC's own copy of the national
 * address file (G-NAF), in place of Google Places, whose project had no billing and refused every lookup.
 *
 * writable/au.jsonl is built by build/address/build.php: one JSON object per line, one per street in a
 * suburb, sorted by its search key (`k`, normalize()d "STREET TYPE SUFFIX SUBURB STATE POSTCODE"). Its
 * first line is a meta record keyed "!" (sorts first) carrying the release and the street-type
 * abbreviations. A lookup binary-searches the first typed word and reads only that word's lines, so the
 * file is never loaded whole. Gitignored and per site, like products.csv.
 */
final class AddressLookupService
{
    public const DATA_FILE = 'au.jsonl';
    public const META_KEY = '!';
    public const STATES = ['ACT', 'NSW', 'NT', 'OT', 'QLD', 'SA', 'TAS', 'VIC', 'WA'];

    private const MIN_CHARS = 3;
    private const MAX_RESULTS = 6;
    // Lines read from one first-word range: a three-letter start covers a few thousand streets at most.
    private const MAX_SCAN = 40000;

    // Typed before the street; dropped from the search, kept by the browser for the filled address.
    private const UNIT_WORDS = ['UNIT', 'U', 'APT', 'APARTMENT', 'FLAT', 'SHOP', 'SUITE', 'LOT', 'LEVEL', 'LVL'];

    // Short forms customers type for words G-NAF spells out in street and suburb names.
    private const NAME_ABBR = ['MT' => 'MOUNT', 'PT' => 'POINT', 'NTH' => 'NORTH', 'STH' => 'SOUTH'];

    // Host fragments to the state their visitors mostly live in: ranks that state's streets first.
    private const HOST_STATES = [
        'perth'     => 'WA',
        'brisbane'  => 'QLD',
        'goldcoast' => 'QLD',
        'adelaide'  => 'SA',
        'sydney'    => 'NSW',
        'newcastle' => 'NSW',
        'melbourne' => 'VIC',
    ];

    public static function dataPath(): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . self::DATA_FILE;
    }

    /** Uppercase, apostrophes dropped (O'CONNOR = OCONNOR), anything else not a letter or digit a space. */
    public static function normalize(string $text): string
    {
        $text = strtoupper(str_replace(["'", "\u{2019}", '`'], '', $text));
        $text = (string) preg_replace('/[^A-Z0-9]+/', ' ', $text);

        return trim($text);
    }

    /** G-NAF's capitals as a street or suburb is written: O'Connor, McDonald, St Kilda, Bay of Isles. */
    public static function titleCase(string $upper): string
    {
        $text = ucwords(strtolower($upper), " -'(/");
        $text = (string) preg_replace_callback('/\bMc([a-z])/', static fn (array $m): string => 'Mc' . strtoupper($m[1]), $text);

        return (string) preg_replace_callback('/(?<=\s)(Of|And)\b/', static fn (array $m): string => strtolower($m[1]), $text);
    }

    /** The state a site's visitors mostly live in, from its host, or ''. */
    public static function stateHintForHost(string $host): string
    {
        $host = strtolower($host);
        foreach (self::HOST_STATES as $fragment => $state) {
            if (str_contains($host, $fragment)) {
                return $state;
            }
        }

        return '';
    }

    /** The data file's meta record (release, street count, built date), or null when it is missing or unreadable. */
    public static function meta(): ?array
    {
        $fh = @fopen(self::dataPath(), 'rb');
        if (!$fh) {
            return null;
        }
        try {
            $meta = self::readMeta($fh);
        } finally {
            fclose($fh);
        }
        if ($meta === null) {
            return null;
        }
        unset($meta['k'], $meta['abbr'], $meta['_start']);

        return $meta;
    }

    /**
     * Streets matching what was typed, best first: [['street' => 'Hay Street', 'locality' => 'Perth',
     * 'state' => 'WA', 'postcode' => '6000'], …]. Words match in order, each as the start of a word in
     * the street's key, so "hay st per" finds Hay Street, Perth; a state hint ranks that state first.
     *
     * @return list<array{street:string, locality:string, state:string, postcode:string}>
     */
    public static function search(string $query, string $stateHint = ''): array
    {
        $tokens = self::queryTokens($query);
        if ($tokens === [] || strlen(implode(' ', $tokens)) < self::MIN_CHARS) {
            return [];
        }
        $stateHint = in_array($stateHint, self::STATES, true) ? $stateHint : '';

        $fh = @fopen(self::dataPath(), 'rb');
        if (!$fh) {
            return [];
        }

        try {
            $meta = self::readMeta($fh);
            if ($meta === null) {
                return [];
            }
            $abbr = is_array($meta['abbr'] ?? null) ? $meta['abbr'] : [];
            $start = (int) $meta['_start'];
            $size = (int) (fstat($fh)['size'] ?? 0);

            // A first word typed short ("mt …") is also looked up spelt out, since lines sort by the street's own spelling.
            $firsts = [$tokens[0]];
            if (isset(self::NAME_ABBR[$tokens[0]])) {
                $firsts[] = self::NAME_ABBR[$tokens[0]];
            }

            $matches = [];
            foreach ($firsts as $first) {
                fseek($fh, self::lowerBound($fh, $start, $size, $first));
                $length = strlen($first);
                for ($scanned = 0; $scanned < self::MAX_SCAN && ($line = fgets($fh)) !== false; $scanned++) {
                    $key = self::lineKey($line);
                    if (strncmp($key, $first, $length) !== 0) {
                        break;
                    }
                    $score = self::score($tokens, explode(' ', $key), $abbr);
                    if ($score === null) {
                        continue;
                    }
                    if ($stateHint !== '' && str_contains($line, '"s":"' . $stateHint . '"')) {
                        $score += 15;
                    }
                    $matches[$key] = [$score, preg_match('/"c":(\d+)/', $line, $c) ? (int) $c[1] : 0, $key, $line];
                }
            }
        } finally {
            fclose($fh);
        }

        // Best match first, then the busier street (more addresses), then A-Z.
        usort($matches, static function (array $a, array $b): int {
            return [$b[0], $b[1], $a[2]] <=> [$a[0], $a[1], $b[2]];
        });

        $results = [];
        foreach (array_slice($matches, 0, self::MAX_RESULTS) as $match) {
            $row = json_decode($match[3], true);
            if (!is_array($row)) {
                continue;
            }
            $results[] = [
                'street'   => (string) ($row['n'] ?? ''),
                'locality' => (string) ($row['l'] ?? ''),
                'state'    => (string) ($row['s'] ?? ''),
                'postcode' => (string) ($row['p'] ?? ''),
            ];
        }

        return $results;
    }

    /**
     * The typed words to match, without a leading house, unit or lot number ("3/12", "unit 4", "lot 9").
     * A unit word only goes when a number follows it, so Flat Rock Road keeps its "Flat".
     *
     * @return list<string>
     */
    private static function queryTokens(string $query): array
    {
        $normalized = self::normalize(substr($query, 0, 120));
        $tokens = $normalized === '' ? [] : explode(' ', $normalized);
        $isNumber = static fn (string $token): bool => (bool) preg_match('/^\d+[A-Z]?$/', $token);

        while ($tokens !== []) {
            if ($isNumber($tokens[0])) {
                array_shift($tokens);
                continue;
            }
            if (in_array($tokens[0], self::UNIT_WORDS, true) && isset($tokens[1]) && $isNumber($tokens[1])) {
                array_splice($tokens, 0, 2);
                continue;
            }
            break;
        }

        return $tokens;
    }

    /**
     * How well a street's key words take the typed words, in order; null when one is left over. A word
     * spelt out in full or by its abbreviation ("st" for STREET) scores more than one only begun.
     *
     * @param list<string> $typed
     * @param list<string> $words
     * @param array<string, list<string>> $abbr
     */
    private static function score(array $typed, array $words, array $abbr): ?int
    {
        $count = count($typed);
        $at = 0;
        $exact = 0;
        $firstExact = false;

        foreach ($words as $index => $word) {
            if ($at >= $count) {
                break;
            }
            $token = $typed[$at];
            if ($word === $token || in_array($word, $abbr[$token] ?? [], true) || (self::NAME_ABBR[$token] ?? '') === $word) {
                $exact++;
                $firstExact = $firstExact || $index === 0;
                $at++;
            } elseif (str_starts_with($word, $token)) {
                $at++;
            } elseif ($index === 0) {
                return null;
            }
        }

        if ($at < $count) {
            return null;
        }

        return $exact * 10 + ($firstExact ? 20 : 0);
    }

    /** Byte offset of the first line whose key sorts at or after $target ($size when none does). */
    private static function lowerBound($fh, int $start, int $size, string $target): int
    {
        $lo = $start;
        $hi = $size;
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            $line = self::lineAtOrAfter($fh, $mid, $start);
            if ($line === null || strcmp(self::lineKey($line), $target) >= 0) {
                $hi = $mid;
            } else {
                $lo = $mid + 1;
            }
        }

        return self::lineStartAtOrAfter($fh, $lo, $start);
    }

    private static function lineStartAtOrAfter($fh, int $pos, int $start): int
    {
        if ($pos <= $start) {
            return $start;
        }
        // Finishing the line that holds $pos - 1 lands on the first line starting at or after $pos.
        fseek($fh, $pos - 1);
        fgets($fh);

        return (int) ftell($fh);
    }

    private static function lineAtOrAfter($fh, int $pos, int $start): ?string
    {
        fseek($fh, self::lineStartAtOrAfter($fh, $pos, $start));
        $line = fgets($fh);

        return $line === false ? null : $line;
    }

    /** The `k` of a data line, read without decoding the line: keys hold only A-Z, 0-9 and spaces. */
    private static function lineKey(string $line): string
    {
        if (strncmp($line, '{"k":"', 6) !== 0) {
            return '';
        }
        $end = strpos($line, '"', 6);

        return $end === false ? '' : substr($line, 6, $end - 6);
    }

    /** The meta record, plus `_start` (where the data lines begin), or null. */
    private static function readMeta($fh): ?array
    {
        fseek($fh, 0);
        $line = fgets($fh);
        if ($line === false || self::lineKey($line) !== self::META_KEY) {
            return null;
        }
        $meta = json_decode($line, true);
        if (!is_array($meta)) {
            return null;
        }
        $meta['_start'] = strlen($line);

        return $meta;
    }
}
