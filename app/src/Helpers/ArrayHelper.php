<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

/**
 * Generic array utilities consolidated out of config/.
 */
final class ArrayHelper
{
    /**
     * Dedupe rows by $key; when a duplicate is found, sum the given numeric fields
     * from the duplicate into the first-seen row instead of discarding them.
     *
     * @param list<array<string, mixed>> $rows
     * @param list<string> $sumFields
     * @return array<int, array<string, mixed>>
     */
    public static function dedupeSummingFields(array $rows, string $key, array $sumFields): array
    {
        $out = [];
        $keyValues = [];
        $i = 0;

        foreach ($rows as $val) {
            // Loose comparison preserved verbatim from the original unique_multidim_array().
            if (!in_array($val[$key], $keyValues)) {
                $keyValues[$i] = $val[$key];
                $out[$i] = $val;
            } else {
                $existingIndex = array_search($val[$key], $keyValues);
                foreach ($sumFields as $sumField) {
                    $out[$existingIndex][$sumField] += $val[$sumField];
                }
            }
            $i++;
        }

        return $out;
    }

    /**
     * Normalize a scalar or list into a deduped list of positive ints.
     *
     * @return list<int>
     */
    public static function toIntList(mixed $value): array
    {
        if (!is_array($value)) {
            if ($value === null || $value === '') {
                return [];
            }
            $value = [$value];
        }

        $out = [];
        foreach ($value as $item) {
            $n = (int) $item;
            if ($n > 0) {
                $out[] = $n;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Normalize a scalar or list into a deduped list of slug-safe strings.
     *
     * @return list<string>
     */
    public static function toStringList(mixed $value): array
    {
        if (!is_array($value)) {
            if ($value === null || $value === '') {
                return [];
            }
            $value = [$value];
        }

        $out = [];
        foreach ($value as $item) {
            $s = StringHelper::slug((string) $item);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Map a callable over one value or a list (FC-prefixed to avoid WP plugin collisions).
     *
     * @return string|array<int, mixed>
     */
    public static function mapCallable(string $callable, mixed $items, bool $asList = false): string|array
    {
        if (!is_array($items)) {
            return call_user_func_array($callable, [$items]);
        }

        if ($asList) {
            $data = '';
            foreach ($items as $row) {
                $data .= '<li>' . call_user_func_array($callable, [$row]) . '</li>';
            }

            return $data;
        }

        foreach ($items as $row) {
            $data[] = call_user_func_array($callable, [$row]);
        }

        return implode(', ', $data);
    }
}
