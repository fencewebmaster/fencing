<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\StoreProductModel;
use Fc\Admin\Presenters\StoreProductPresenter;

/**
 * Researched SKU proposals for the Missing SKUs page, held in writable/missing-products.csv.
 *
 * A row with an empty SKU is a finding too: it records why that colour cannot be filled, so the
 * page can say so instead of leaving the field to nag forever.
 *
 * SOURCE guards the file. A row marked `manual` was decided by a person and a re-scan leaves it
 * alone; only `scan` rows and gaps with no row yet are rewritten. The catalogue itself is the union
 * of both store exports, so a JG product matching a GO SKU is normal and needs no special case.
 */
final class MissingSkuScanService
{
    private const FIELDS = ['SLUG', 'COLUMN', 'CURRENT', 'SKU', 'CONFIDENCE', 'MATCHED_NAME', 'NOTE', 'SOURCE'];

    /** @var array<string, array{sku:string,confidence:string,name:string,note:string,source:string}>|null */
    private static ?array $cache = null;

    public static function csvPath(): string
    {
        return dirname(__DIR__, 3) . '/writable/missing-products.csv';
    }

    public static function isAvailable(): bool
    {
        return is_readable(self::csvPath());
    }

    /**
     * Throws the researched proposals away. An absent file is a valid state — every gap simply
     * reads as unresearched again — so this only starts the research over; products.csv, where
     * the SKUs people have actually saved live, is never touched.
     *
     * Rows a person marked `manual` go with it, which is why the page asks first.
     *
     * @return array{ok:bool,removed:bool,error?:string}
     */
    public static function clear(): array
    {
        $path = self::csvPath();
        // Dropped before the unlink, so a caller reading back cannot be served the old map.
        self::$cache = null;

        if (!is_file($path)) {
            return ['ok' => true, 'removed' => false];
        }
        if (!@unlink($path)) {
            return ['ok' => false, 'removed' => false, 'error' => 'Could not delete missing-products.csv.'];
        }

        return ['ok' => true, 'removed' => true];
    }

    /**
     * Proposals keyed "<slug>|<COLUMN>", the same key the page builds per field.
     *
     * @return array<string, array{sku:string,confidence:string,name:string,note:string,source:string}>
     */
    public static function proposals(): array
    {
        if (is_array(self::$cache)) {
            return self::$cache;
        }

        $cache = [];
        foreach (self::readRows() as $row) {
            $slug = trim((string) ($row['SLUG'] ?? ''));
            $column = strtoupper(trim((string) ($row['COLUMN'] ?? '')));
            if ($slug === '' || $column === '') {
                continue;
            }

            $cache[$slug . '|' . $column] = [
                'sku'        => trim((string) ($row['SKU'] ?? '')),
                'confidence' => strtolower(trim((string) ($row['CONFIDENCE'] ?? ''))),
                'name'       => trim((string) ($row['MATCHED_NAME'] ?? '')),
                'note'       => trim((string) ($row['NOTE'] ?? '')),
                'source'     => strtolower(trim((string) ($row['SOURCE'] ?? ''))) ?: 'scan',
            ];
        }
        self::$cache = $cache;

        return $cache;
    }

    /**
     * Matches every gap in products.csv against the catalogue and rewrites the CSV, keeping rows a
     * person decided. Runs over the whole file, never just the rows a page filter is showing.
     *
     * @return array{ok:bool,written:int,kept:int,total:int,error?:string}
     */
    public static function scan(): array
    {
        $payload = StoreProductModel::all();
        if (empty($payload['ok'])) {
            return ['ok' => false, 'written' => 0, 'kept' => 0, 'total' => 0, 'error' => 'Could not read products.csv.'];
        }

        $catalogue = MissingSkuMatcher::prepareCatalogue();
        if ($catalogue === []) {
            return ['ok' => false, 'written' => 0, 'kept' => 0, 'total' => 0, 'error' => 'The store catalogue is empty.'];
        }

        $skuSet = WcProductSkuIndex::skuLookup();
        $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];
        $styleColors = StoreProductPresenter::styleColorsMap();
        $initials = StoreProductPresenter::colorInitialsMap();
        $existing = self::proposals();

        $byKey = [];
        $written = 0;
        $kept = 0;

        foreach (is_array($payload['rows'] ?? null) ? $payload['rows'] : [] as $product) {
            $slug = trim((string) ($product['SLUG'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $allowed = StoreProductPresenter::allowedColorColumns($product, $columns, $styleColors);
            $values = [];
            $siblings = [];
            $gaps = [];

            foreach ($allowed as $column) {
                $value = trim((string) ($product[$column] ?? ''));
                $values[] = $value;
                if ($value !== '' && strtoupper($value) !== 'OFF' && isset($skuSet[$value])) {
                    $siblings[] = $value;
                    continue;
                }
                if (strtoupper($value) === 'OFF') {
                    continue;
                }
                $gaps[$column] = $value;
            }

            if ($gaps === []) {
                continue;
            }
            $agnostic = MissingSkuMatcher::isColorAgnostic($values);

            foreach ($gaps as $column => $value) {
                $key = $slug . '|' . $column;
                $prior = $existing[$key] ?? null;

                if (isset($byKey[$key]) && $byKey[$key]['SKU'] !== '') {
                    continue;
                }

                if ($prior !== null && $prior['source'] === 'manual') {
                    if (!isset($byKey[$key])) {
                        $kept++;
                    }
                    $byKey[$key] = self::rowFrom($slug, $column, $value, $prior);
                    continue;
                }

                $decision = MissingSkuMatcher::match([
                    'value'    => $value,
                    'initial'  => $initials[$column] ?? '',
                    'product'  => (string) ($product['PRODUCT'] ?? ''),
                    'style'    => (string) ($product['STYLE'] ?? ''),
                    'siblings' => $siblings,
                ], $catalogue, $agnostic);

                // A SKU that is not in the catalogue never reaches the file, however it was decided.
                if ($decision['sku'] !== '' && !isset($skuSet[$decision['sku']])) {
                    $decision = [
                        'sku'        => '',
                        'confidence' => 'not-stocked',
                        'name'       => '',
                        'note'       => 'Proposed ' . $decision['sku'] . ' is not in the catalogue - rejected.',
                    ];
                }

                if (!isset($byKey[$key])) {
                    $written++;
                }
                $byKey[$key] = self::rowFrom($slug, $column, $value, $decision + ['source' => 'scan']);
            }
        }

        $rows = array_values($byKey);
        usort($rows, static fn (array $a, array $b): int => [$a['SLUG'], $a['COLUMN']] <=> [$b['SLUG'], $b['COLUMN']]);

        if (!self::write($rows)) {
            return ['ok' => false, 'written' => 0, 'kept' => 0, 'total' => 0, 'error' => 'Could not write missing-products.csv.'];
        }
        // The memoised map is the pre-write one; a caller reading back now must see the new file.
        self::$cache = null;

        return ['ok' => true, 'written' => $written, 'kept' => $kept, 'total' => count($rows)]
;
    }

    /**
     * @param array{sku:string,confidence:string,name:string,note:string,source?:string} $decision
     * @return array<string, string>
     */
    private static function rowFrom(string $slug, string $column, string $current, array $decision): array
    {
        return [
            'SLUG'         => $slug,
            'COLUMN'       => $column,
            'CURRENT'      => $current,
            'SKU'          => $decision['sku'],
            'CONFIDENCE'   => $decision['confidence'],
            'MATCHED_NAME' => $decision['name'],
            'NOTE'         => $decision['note'],
            'SOURCE'       => (string) ($decision['source'] ?? 'scan'),
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private static function readRows(): array
    {
        $path = self::csvPath();
        if (!is_readable($path)) {
            return [];
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);

            return [];
        }
        $header = array_map(static fn ($h): string => strtoupper(trim((string) $h)), $header);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param list<array<string, string>> $rows
     */
    private static function write(array $rows): bool
    {
        $path = self::csvPath();
        $tmp = $path . '.tmp';
        $handle = fopen($tmp, 'w');
        if ($handle === false) {
            return false;
        }

        fputcsv($handle, self::FIELDS);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(static fn (string $f): string => (string) ($row[$f] ?? ''), self::FIELDS));
        }
        fclose($handle);

        // Swap in one move so a reader never sees a half-written file.
        return rename($tmp, $path);
    }
}
