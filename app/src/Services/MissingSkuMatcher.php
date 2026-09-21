<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Matches a System Product's blank/unknown colour SKU against the store catalogue.
 *
 * The rules come from researching the gaps by hand: catalogue SKUs wrap a core in a range prefix
 * and store suffix (FS-<range>-<core>-<supplier>), and the colour's code is the last segment.
 *
 * The colour code is a HARD constraint, never a fuzzy one — XP-2400-FP-B (Black) is one edit away
 * from XP-2400-FP-BS (Basalt), so string distance alone picks the wrong colour with confidence.
 * The exception is a product that does not vary by colour at all, which shows up as the same SKU
 * typed into every colour column of its row.
 */
final class MissingSkuMatcher
{
    /** products.csv STYLE -> the catalogue range prefix that style's SKUs live under. */
    private const STYLE_PREFIX = [
        'slat'        => 'FS-SL-',
        'slat_infill' => 'FS-SL-',
        'flat_top'    => 'FS-FT-',
        'blade'       => 'FS-BL-',
        'barr'        => 'FS-BR-',
    ];

    /**
     * One decision for one gap.
     *
     * @param array<string, mixed> $gap    slug, column, value, initial, product, style, siblings
     * @param list<array{sku:string,name:string,upper:string,code:string}> $catalogue
     * @param bool $colorAgnostic Every colour column of the row carries this same SKU.
     * @return array{sku:string,confidence:string,name:string,note:string}
     */
    public static function match(array $gap, array $catalogue, bool $colorAgnostic): array
    {
        $typed = strtoupper(trim((string) ($gap['value'] ?? '')));
        $initial = strtoupper(trim((string) ($gap['initial'] ?? '')));
        $product = trim((string) ($gap['product'] ?? ''));
        $style = strtolower(trim((string) ($gap['style'] ?? '')));
        $siblings = is_array($gap['siblings'] ?? null) ? $gap['siblings'] : [];

        // 0. The row's own working SKUs, recoloured. Nothing else in the catalogue knows as much
        //    about this product as the colours of it that are already filled in and valid.
        if ($initial !== '' && !$colorAgnostic) {
            foreach ($siblings as $sibling) {
                $sibling = strtoupper(trim((string) $sibling));
                $code = self::colorCodeOf($sibling);
                if ($code === '' || $code === $initial) {
                    continue;
                }
                $pos = strrpos($sibling, '-' . $code);
                if ($pos === false) {
                    continue;
                }
                $candidate = substr($sibling, 0, $pos) . '-' . $initial . substr($sibling, $pos + strlen($code) + 1);
                $exact = self::findExact($candidate, $catalogue);
                if ($exact !== null) {
                    return self::hit($exact, 'high', 'Same SKU as this row\'s other colours, in this colour.');
                }
            }
        }

        // 1. A name that matches the product title outright is the strongest signal there is.
        $byName = self::byExactName($product, $catalogue, $initial, $colorAgnostic);
        if ($byName !== null) {
            return self::hit($byName, 'high', 'Catalogue name matches the product title exactly.');
        }

        if ($typed === '') {
            return self::miss(self::familyNote($siblings, $catalogue)
                ?? 'No SKU to match on, and no catalogue product shares this title.');
        }

        // 2. A stray trailing counter, e.g. SS-POSTPLUG-4PK-1 for SS-POSTPLUG-4PK.
        $trimmed = preg_replace('/-\d+$/', '', $typed) ?? $typed;
        if ($trimmed !== $typed) {
            $exact = self::findExact($trimmed, $catalogue);
            if ($exact !== null) {
                return self::hit($exact, 'high', 'Typed value carried a stray "' . substr($typed, strlen($trimmed)) . '".');
            }
        }

        // 3. The typed value as the core of a wrapped SKU, colour code permitting.
        $contains = [];
        foreach ($catalogue as $row) {
            if (str_contains($row['upper'], $typed)
                && ($colorAgnostic || $initial === '' || $row['code'] === $initial)
            ) {
                $contains[] = $row;
            }
        }
        if (count($contains) === 1) {
            // A generic part (a screw, a bolt) is filed under whatever range the catalogue chose, but
            // a colour-varying product in another range is a different product - do not assume.
            $crossRange = !$colorAgnostic && self::isCrossRange($contains[0]['upper'], $style);

            return $crossRange
                ? self::miss(
                    'Only match is ' . $contains[0]['sku'] . ', which is a different range to this "'
                    . $style . '" product - confirm before using.'
                )
                : self::hit($contains[0], 'high', 'Typed value is the core of this catalogue SKU.');
        }
        if (count($contains) > 1) {
            // Several ranges carry the same core; the product's own style decides which.
            $prefix = self::STYLE_PREFIX[$style] ?? '';
            $sameRange = $prefix === ''
                ? []
                : array_values(array_filter(
                    $contains,
                    static fn (array $r): bool => str_starts_with($r['upper'], $prefix)
                ));
            if (count($sameRange) === 1) {
                return self::hit($sameRange[0], 'medium', 'Matched on the ' . $style . ' range.');
            }

            return self::miss(
                'Several ranges carry this core and none is the "' . $style . '" range - confirm before using: '
                . implode(', ', array_map(static fn (array $r): string => $r['sku'], array_slice($contains, 0, 4)))
            );
        }

        // 4. Right family, wrong colour code typed.
        if ($initial !== '') {
            $stem = self::stemOf($typed);
            $exact = self::findExact($stem . '-' . $initial, $catalogue);
            if ($exact !== null) {
                return self::hit($exact, 'medium', 'Same family, corrected to this colour\'s code.');
            }
        }

        // 5. The family exists but not in this colour - a real answer, not a failure.
        $stem = self::stemOf($typed);
        $family = [];
        foreach ($catalogue as $row) {
            if ($row['code'] !== '' && str_starts_with($row['upper'], $stem . '-')) {
                $family[$row['code']] = true;
            }
        }
        if ($family !== []) {
            $codes = array_keys($family);
            sort($codes);

            return self::miss($stem . ' is stocked in ' . implode(', ', $codes) . ' only.');
        }

        return self::miss('No catalogue product matches "' . $typed . '".');
    }

    /**
     * Rows in a products.csv record that carry the same SKU in every colour column do not vary by
     * colour (a black drop bolt, a stainless screw), so their colour code must not be enforced.
     *
     * @param list<string> $values every colour column's value for one product row
     */
    public static function isColorAgnostic(array $values): bool
    {
        $seen = [];
        foreach ($values as $value) {
            $value = strtoupper(trim($value));
            if ($value === '' || $value === 'OFF') {
                continue;
            }
            $seen[$value] = true;
        }

        return count($seen) === 1 && count($values) > 1;
    }

    /**
     * The catalogue prepared once for matching: upper-cased SKU plus its colour code.
     *
     * @return list<array{sku:string,name:string,upper:string,code:string}>
     */
    public static function prepareCatalogue(): array
    {
        $out = [];
        foreach (WcProductSkuIndex::catalogueUnion() as $entry) {
            $sku = trim((string) ($entry['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $out[] = [
                'sku'   => $sku,
                'name'  => (string) ($entry['name'] ?? ''),
                'upper' => strtoupper($sku),
                'code'  => self::colorCodeOf(strtoupper($sku)),
            ];
        }

        return $out;
    }

    /**
     * The colour code a SKU ends on, ignoring the store suffix, a CTS marker and a trailing counter.
     * XP-2400-FP-BS -> BS; FS-FT-XP-2400-FP-B-GO -> B; XP-6100-GB65-BS-1 -> BS.
     */
    public static function colorCodeOf(string $upperSku): string
    {
        $parts = explode('-', trim($upperSku));
        while ($parts !== []) {
            $last = end($parts);
            if ($last === 'GO' || $last === 'JG' || $last === 'CTS' || preg_match('/^\d+$/', $last)) {
                array_pop($parts);
                continue;
            }
            break;
        }
        $last = $parts === [] ? '' : (string) end($parts);

        return preg_match('/^[A-Z]{1,3}$/', $last) === 1 ? $last : '';
    }

    /**
     * What the row's family is stocked in, phrased for a gap that had nothing typed in it.
     *
     * @param list<string> $siblings
     * @param list<array{sku:string,name:string,upper:string,code:string}> $catalogue
     */
    private static function familyNote(array $siblings, array $catalogue): ?string
    {
        foreach ($siblings as $sibling) {
            $stem = self::stemOf(strtoupper(trim((string) $sibling)));
            if ($stem === '') {
                continue;
            }
            $codes = [];
            foreach ($catalogue as $row) {
                if ($row['code'] !== '' && str_starts_with($row['upper'], $stem . '-')) {
                    $codes[$row['code']] = true;
                }
            }
            if ($codes !== []) {
                $list = array_keys($codes);
                sort($list);

                return $stem . ' is stocked in ' . implode(', ', $list) . ' only.';
            }
        }

        return null;
    }

    /** True when the SKU sits in a range this product's style says it should not. */
    private static function isCrossRange(string $upperSku, string $style): bool
    {
        $want = self::STYLE_PREFIX[$style] ?? '';
        if ($want === '' || str_starts_with($upperSku, $want)) {
            return false;
        }
        foreach (self::STYLE_PREFIX as $prefix) {
            if ($prefix !== $want && str_starts_with($upperSku, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** The SKU with its trailing colour code removed, for family comparisons. */
    private static function stemOf(string $upperSku): string
    {
        $code = self::colorCodeOf($upperSku);
        if ($code === '') {
            return $upperSku;
        }
        $pos = strrpos($upperSku, '-' . $code);

        return $pos === false ? $upperSku : substr($upperSku, 0, $pos);
    }

    /**
     * @param list<array{sku:string,name:string,upper:string,code:string}> $catalogue
     * @return array{sku:string,name:string,upper:string,code:string}|null
     */
    private static function findExact(string $upperSku, array $catalogue): ?array
    {
        foreach ($catalogue as $row) {
            if ($row['upper'] === $upperSku) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param list<array{sku:string,name:string,upper:string,code:string}> $catalogue
     * @return array{sku:string,name:string,upper:string,code:string}|null
     */
    private static function byExactName(
        string $product,
        array $catalogue,
        string $initial,
        bool $colorAgnostic
    ): ?array {
        $want = self::normalizeName($product);
        if ($want === '') {
            return null;
        }

        $hits = [];
        foreach ($catalogue as $row) {
            if (self::normalizeName($row['name']) === $want) {
                $hits[] = $row;
            }
        }
        if ($hits === []) {
            return null;
        }
        if (count($hits) === 1) {
            // One product, one SKU: either colour-agnostic or the only colour stocked.
            return ($colorAgnostic || $initial === '' || $hits[0]['code'] === '' || $hits[0]['code'] === $initial)
                ? $hits[0]
                : null;
        }
        foreach ($hits as $row) {
            if ($row['code'] === $initial && $initial !== '') {
                return $row;
            }
        }

        return null;
    }

    /** Names differ by punctuation and spacing between the two files, so compare on the words. */
    private static function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    /**
     * @param array{sku:string,name:string,upper:string,code:string} $row
     * @return array{sku:string,confidence:string,name:string,note:string}
     */
    private static function hit(array $row, string $confidence, string $note): array
    {
        return ['sku' => $row['sku'], 'confidence' => $confidence, 'name' => $row['name'], 'note' => $note];
    }

    /**
     * @return array{sku:string,confidence:string,name:string,note:string}
     */
    private static function miss(string $note): array
    {
        return ['sku' => '', 'confidence' => 'not-stocked', 'name' => '', 'note' => $note];
    }
}
