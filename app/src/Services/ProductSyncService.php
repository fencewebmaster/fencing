<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\StoreProductModel;
use Fc\Admin\Presenters\StoreProductPresenter;

/**
 * Fills the DESCRIPTION column of writable/products.csv with the WooCommerce product description,
 * matched to each row through its own colour SKUs.
 *
 * The description is taken as the store wrote it — sales copy, emoji bullets and all. Where a row
 * has several SKUs, one they share came from the parent product and so describes every colour of
 * it; otherwise the first SKU that carries a description answers.
 *
 * Where the store wrote no description at all, the product's NAME stands in instead, because the
 * text already in the column is worse: it was copied from one colour, so a row spanning Black,
 * White and Monument reads "PoolSafe Panel Black 1200H x 2400W". Two or three SKUs keeping only
 * what they agree on cancels the colour out ("PoolSafe Panel 1200H x 2400W"); a row with a single
 * SKU has its colour removed by name, since the column the SKU sits in says what that colour is.
 *
 * A row with no usable SKU at all keeps what it has. Nothing is ever blanked.
 */
final class ProductSyncService
{
    /** "2-3 SKUs": enough for a colour to cancel out, few enough to stay cheap. */
    private const MAX_SKUS = 3;

    /** A derived name shorter than this is a fragment, not a description. */
    private const MIN_NAME_WORDS = 2;

    /** Words a colour column carries that are a finish or a material rather than a colour. */
    private const NON_COLOUR_WORDS = [
        'matt', 'matte', 'gloss', 'satin', 'textured',
        'steel', 'stainless', 'polished', 'red',
    ];

    /**
     * One slice of the file, so the page can show real progress over it.
     *
     * @return array{ok:bool,total:int,next:int,done:bool,scanned:int,updates:int,from_description:int,from_name:int,changes:list<array{index:int,slug:string,product:string,was:string,now:string,source:string}>,error?:string}
     */
    public static function preview(int $offset, int $limit): array
    {
        $load = StoreProductModel::all();
        if (empty($load['ok'])) {
            return self::failed((string) ($load['error'] ?? 'Could not read products.csv.'));
        }

        $rows = is_array($load['rows'] ?? null) ? $load['rows'] : [];
        $total = count($rows);
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        $derived = self::derive($load, $offset, $limit);
        $next = min($total, $offset + $limit);

        return [
            'ok'               => true,
            'total'            => $total,
            'next'             => $next,
            'done'             => $next >= $total,
            'scanned'          => $derived['scanned'],
            'updates'          => count($derived['changes']),
            'names'            => $derived['names'],
            'from_description' => $derived['fromDescription'],
            'from_name'        => $derived['fromName'],
            'changes'          => $derived['changes'],
        ];
    }

    /**
     * Derives the whole file again and writes it. The proposals are never taken from the browser:
     * a preview is something to read, not the payload.
     *
     * @return array{ok:bool,total:int,updated:int,from_description:int,from_name:int,skipped:int,error?:string}
     */
    public static function apply(): array
    {
        $load = StoreProductModel::all();
        if (empty($load['ok'])) {
            return self::failed((string) ($load['error'] ?? 'Could not read products.csv.')) + ['updated' => 0, 'skipped' => 0];
        }

        $rows = is_array($load['rows'] ?? null) ? $load['rows'] : [];
        $derived = self::derive($load, 0, PHP_INT_MAX);
        if ($derived['changes'] === []) {
            return [
                'ok'               => true,
                'total'            => count($rows),
                'updated'          => 0,
                'rows_changed'     => 0,
                'names'            => 0,
                'from_description' => 0,
                'from_name'        => 0,
                'skipped'          => count($rows),
            ];
        }

        $byIndex = [];
        foreach ($derived['changes'] as $change) {
            $byIndex[$change['index']][$change['field']] = $change['now'];
        }

        $written = StoreProductMaintenanceService::updateFields($byIndex);
        if (empty($written['ok'])) {
            return self::failed((string) ($written['error'] ?? 'Could not save products.csv.')) + ['updated' => 0, 'skipped' => 0];
        }

        return [
            'ok'               => true,
            'total'            => count($rows),
            'updated'          => count($derived['changes']),
            'rows_changed'     => count($byIndex),
            'names'            => $derived['names'],
            'from_description' => $derived['fromDescription'],
            'from_name'        => $derived['fromName'],
            'skipped'          => count($rows) - count($byIndex),
        ];
    }

    /**
     * @param array{columns:list<string>,rows:list<array<string,string>>} $load
     * @return array{scanned:int,names:int,fromDescription:int,fromName:int,changes:list<array{index:int,slug:string,field:string,was:string,now:string,source:string}>}
     */
    private static function derive(array $load, int $offset, int $limit): array
    {
        $columns = is_array($load['columns'] ?? null) ? $load['columns'] : [];
        $rows = is_array($load['rows'] ?? null) ? $load['rows'] : [];
        $styleColors = StoreProductPresenter::styleColorsMap();

        $catalogue = [];
        foreach (WcProductSkuIndex::catalogueUnion() as $entry) {
            $catalogue[(string) $entry['sku']] = $entry;
        }

        $scanned = 0;
        $names = 0;
        $fromDescription = 0;
        $fromName = 0;
        $changes = [];

        $end = $limit === PHP_INT_MAX ? count($rows) : min(count($rows), $offset + $limit);
        for ($i = $offset; $i < $end; $i++) {
            $row = $rows[$i] ?? null;
            if (!is_array($row)) {
                continue;
            }
            $scanned++;

            $columnOf = [];
            foreach (StoreProductPresenter::allowedColorColumns($row, $columns, $styleColors) as $column) {
                $value = trim((string) ($row[$column] ?? ''));
                if ($value !== '' && strtoupper($value) !== 'OFF' && isset($catalogue[$value])) {
                    // Keyed, so a colour-agnostic row of one repeated SKU counts as the one it is.
                    // Every column it sits under is kept: one SKU serving both Monument and Black
                    // is identified by neither, so both words have to come out of its name.
                    $columnOf[$value][] = $column;
                }
            }
            $skus = array_slice(array_keys($columnOf), 0, self::MAX_SKUS);
            if ($skus === []) {
                continue;
            }

            $index = (int) ($row['_rowIndex'] ?? $i);
            $slug = (string) ($row['SLUG'] ?? '');

            // The product's name, with whatever separates this row's colours taken out of it:
            // several SKUs cancel the colour between them, one has it removed by the column it
            // sits in. This is also the description's fallback, so it is built either way.
            $name = count($skus) === 1
                ? self::nameWithoutColour(
                    (string) ($catalogue[$skus[0]]['name'] ?? ''),
                    $columnOf[$skus[0]]
                )
                : self::commonName($skus, $catalogue);

            $currentName = trim((string) ($row['PRODUCT'] ?? ''));
            if ($name !== '' && $name !== $currentName) {
                $names++;
                $changes[] = [
                    'index'  => $index,
                    'slug'   => $slug,
                    'field'  => 'PRODUCT',
                    'was'    => $currentName,
                    'now'    => $name,
                    'source' => 'name',
                ];
            }

            // What WooCommerce says about these SKUs, taken as the store wrote it. Only where it
            // wrote nothing at all does the product's name stand in, so a row is never left
            // holding text that belongs to a colour it no longer has.
            $source = 'description';
            $built = self::descriptionFor($skus, $catalogue);
            if ($built === '') {
                $source = 'name';
                $built = $name;
            }

            $currentDescription = trim((string) ($row['DESCRIPTION'] ?? ''));
            if ($built === '' || $built === $currentDescription) {
                continue;
            }

            if ($source === 'description') {
                $fromDescription++;
            } else {
                $fromName++;
            }
            $changes[] = [
                'index'  => $index,
                'slug'   => $slug,
                'field'  => 'DESCRIPTION',
                'was'    => $currentDescription,
                'now'    => $built,
                'source' => $source,
            ];
        }

        return [
            'scanned'         => $scanned,
            'names'           => $names,
            'fromDescription' => $fromDescription,
            'fromName'        => $fromName,
            'changes'         => $changes,
        ];
    }

    /**
     * The WooCommerce description for this row's SKUs.
     *
     * A description two of them carry identically came from the parent product and describes all
     * of its colours, so it wins. Failing that the first SKU that has one answers, because a row
     * with a single SKU still has a description worth copying.
     *
     * @param list<string> $skus
     * @param array<string, array{sku:string,name:string,description:string}> $catalogue
     */
    private static function descriptionFor(array $skus, array $catalogue): string
    {
        $groups = [];
        $first = '';
        foreach ($skus as $sku) {
            $text = self::collapse((string) ($catalogue[$sku]['description'] ?? ''));
            if ($text === '') {
                continue;
            }
            if ($first === '') {
                $first = $text;
            }
            $groups[mb_strtolower($text)][] = $text;
        }
        foreach ($groups as $group) {
            if (count($group) >= 2) {
                return $group[0];
            }
        }

        return $first;
    }

    /**
     * The words every one of these SKUs' names shares, in order. What differs between a product's
     * colours is the colour, so what survives is the product.
     *
     * @param list<string> $skus
     * @param array<string, array{sku:string,name:string,description:string}> $catalogue
     */
    private static function commonName(array $skus, array $catalogue): string
    {
        $shortest = PHP_INT_MAX;
        $common = null;
        foreach ($skus as $sku) {
            $words = self::words((string) ($catalogue[$sku]['name'] ?? ''));
            if ($words === []) {
                return '';
            }
            $shortest = min($shortest, count($words));
            $common = $common === null ? $words : self::commonWords($common, $words);
        }
        if ($common === null || $common === []) {
            return '';
        }

        // Half the shortest name must survive, or what is left is a word two products happen to
        // share ("back", "Clamp (") rather than the product they both are.
        if (count($common) < (int) ceil($shortest * 0.5)) {
            return '';
        }

        $built = self::tidy($common);

        return count(self::words($built)) < self::MIN_NAME_WORDS ? '' : $built;
    }

    /**
     * A single SKU's name with this row's own colour taken out of it — the column the SKU sits in
     * names the colour, so there is no guessing.
     *
     * Only words that genuinely name a colour are removed. A finish or a material reaches the
     * column name too ("SATIN_STAINLESS_STEEL"), and stripping those would turn "Stainless Steel
     * Screw" into "Screw"; MissingSkuDeepScan keeps the same list out of its colour map.
     *
     * @param list<string> $columns every colour column this SKU is used in
     */
    private static function nameWithoutColour(string $name, array $columns): string
    {
        $name = self::collapse($name);
        if ($name === '') {
            return '';
        }

        $skip = array_fill_keys(self::NON_COLOUR_WORDS, true);
        $stripped = $name;
        foreach ($columns as $column) {
            foreach (preg_split('/[^A-Za-z0-9]+/', strtolower((string) $column)) ?: [] as $word) {
                if (strlen($word) < 3 || isset($skip[$word])) {
                    continue;
                }
                $stripped = (string) preg_replace('/\b' . preg_quote($word, '/') . '\b/i', '', $stripped);
            }
        }

        $stripped = self::tidy(self::words($stripped));

        // Stripping a name down to nothing means the colour was all it said; keep the name.
        return count(self::words($stripped)) < self::MIN_NAME_WORDS ? $name : $stripped;
    }

    /**
     * Word-level longest common subsequence. Compared case-insensitively because the catalogue
     * writes both "1200H x 2400W" and "1200H X 2400W"; the first name's casing is the one kept.
     *
     * @param list<string> $a
     * @param list<string> $b
     * @return list<string>
     */
    private static function commonWords(array $a, array $b): array
    {
        $la = array_map('mb_strtolower', $a);
        $lb = array_map('mb_strtolower', $b);
        $n = count($a);
        $m = count($b);

        $dp = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $dp[$i][$j] = $la[$i] === $lb[$j]
                    ? $dp[$i + 1][$j + 1] + 1
                    : max($dp[$i + 1][$j], $dp[$i][$j + 1]);
            }
        }

        $out = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($la[$i] === $lb[$j]) {
                $out[] = $a[$i];
                $i++;
                $j++;
            } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
                $i++;
            } else {
                $j++;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function words(string $text): array
    {
        $text = self::collapse($text);

        return $text === '' ? [] : explode(' ', $text);
    }

    /**
     * @param list<string> $words
     */
    private static function tidy(array $words): string
    {
        $text = self::collapse(implode(' ', $words));
        // The dropped colour leaves its separators behind: "PoolSafe Post - - 1300L".
        $text = (string) preg_replace('/\s*([-\x{2013}|\/])\s*(?=[-\x{2013}|\/]|$)/u', '', $text);
        $text = (string) preg_replace('/^[\s\-\x{2013}|\/,]+|[\s\-\x{2013}|\/,]+$/u', '', $text);
        // The catalogue writes both "1200H x 2400W" and "1200H X 2400W", and which one a row ends
        // up with is just whichever colour it read first. One casing, so the column reads evenly.
        $text = (string) preg_replace('/(?<=\s)X(?=\s)/u', 'x', $text);

        return self::collapse($text);
    }

    private static function collapse(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @return array{ok:bool,total:int,next:int,done:bool,scanned:int,updates:int,from_description:int,from_name:int,changes:list<array{index:int,slug:string,product:string,was:string,now:string,source:string}>,error:string}
     */
    private static function failed(string $error): array
    {
        return [
            'ok'               => false,
            'total'            => 0,
            'next'             => 0,
            'done'             => true,
            'scanned'          => 0,
            'updates'          => 0,
            'names'            => 0,
            'from_description' => 0,
            'from_name'        => 0,
            'changes'          => [],
            'error'            => $error,
        ];
    }
}
