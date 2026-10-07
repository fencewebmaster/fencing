<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use DateTimeImmutable;
use Fc\Admin\Helpers\HtmlSanitizer;
use Fc\Admin\Helpers\SemverHelper;
use Fc\Admin\Models\VersionModel;

/**
 * Version Manager rules over the version history store (VersionModel, writable/versions.csv).
 * The only way into that file: callers never read the CSV themselves.
 *
 * Records are arrays with the CSV's columns, typed: id int, published bool, the rest strings
 * (date_time/created_at/updated_at as 'Y-m-d H:i:s'). Writes return ['ok' => bool, ...] and
 * never throw: 'version' carries the saved record, 'errors' field => message for the form,
 * 'status' an HTTP code for the API when it is not a plain 400.
 *
 * The app has one number line: Automatic continues the highest version of any type, and no
 * two versions may share a number whatever their type (per-type lines gave three v13.1.0s).
 * Numbers are worked out inside the write lock so two saves can never both take the same one.
 */
final class VersionService
{
    public const TYPES = [
        'frontend' => 'Frontend',
        'backend'  => 'Backend',
        'full'     => 'Full Application',
    ];

    public const LEVELS = [
        'major' => 'Major',
        'minor' => 'Minor',
        'patch' => 'Patch',
    ];

    public const MODES = [
        'automatic' => 'Automatic',
        'manual'    => 'Manual',
    ];

    /** Raw HTML as posted; generous for release notes, small enough to keep the CSV quick to read. */
    public const DESCRIPTION_MAX_BYTES = 100000;

    /**
     * Every version, newest release first, plus whether the store could be read.
     *
     * @return array{ok:bool,versions:list<array<string,mixed>>,error?:string}
     */
    public static function load(): array
    {
        $read = VersionModel::read();
        $versions = self::sortNewestFirst(self::toRecords($read['rows']));

        return $read['ok']
            ? ['ok' => true, 'versions' => $versions]
            : ['ok' => false, 'versions' => $versions, 'error' => (string) ($read['error'] ?? 'versions.csv is not readable.')];
    }

    /**
     * @return list<array<string,mixed>> newest release first
     */
    public static function all(): array
    {
        return self::load()['versions'];
    }

    /**
     * Published versions only, newest release first — what the public page may show.
     *
     * @return list<array<string,mixed>>
     */
    public static function published(?string $type = null): array
    {
        return array_values(array_filter(
            self::all(),
            static fn (array $v): bool => $v['published'] && ($type === null || $v['type'] === $type)
        ));
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        foreach (self::all() as $version) {
            if ($version['id'] === $id) {
                return $version;
            }
        }

        return null;
    }

    /**
     * The most recent release by date, of any type.
     *
     * @return array<string,mixed>|null
     */
    public static function latest(bool $publishedOnly = false): ?array
    {
        foreach (self::all() as $version) {
            if (!$publishedOnly || $version['published']) {
                return $version;
            }
        }

        return null;
    }

    /**
     * The highest version number of one type.
     *
     * @return array<string,mixed>|null
     */
    public static function latestByType(string $type, bool $publishedOnly = false): ?array
    {
        $records = array_filter(
            self::toRecords(VersionModel::read()['rows']),
            static fn (array $v): bool => !$publishedOnly || $v['published']
        );

        return self::highestOfType($records, $type, 0);
    }

    /**
     * Preview of the automatic number for $type at $level. $excludeId leaves the version being
     * edited out of the "latest" it continues from; base_type names the type that holds it.
     *
     * @return array{ok:bool,version?:string,base?:string,base_type?:string,error?:string}
     */
    public static function nextVersion(string $type, string $level, int $excludeId = 0): array
    {
        if (!isset(self::TYPES[$type]) || !isset(self::LEVELS[$level])) {
            return ['ok' => false, 'error' => 'Unknown version type or level.'];
        }

        $base = self::highestOfType(self::toRecords(VersionModel::read()['rows']), null, $excludeId);
        $next = SemverHelper::bump($base['version'] ?? '', $level);
        if (!SemverHelper::isValid($next)) {
            return ['ok' => false, 'error' => 'The next version number is out of range.'];
        }

        return [
            'ok' => true,
            'version' => $next,
            'base' => (string) ($base['version'] ?? ''),
            'base_type' => (string) ($base['type'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $input date_time, type, version_level, version_mode, version (manual), published, description
     * @return array<string,mixed>
     */
    public static function add(array $input): array
    {
        return self::afterWrite(VersionModel::mutate(static function (array $rows) use ($input): array {
            $built = self::buildRecord($input, $rows, null);
            if (!$built['ok']) {
                return $built;
            }

            $now = date('Y-m-d H:i:s');
            $record = ['id' => self::nextId($rows)] + $built['record'] + ['created_at' => $now, 'updated_at' => $now];
            $rows[] = self::toRow($record);

            return ['ok' => true, 'rows' => $rows, 'version' => $record];
        }));
    }

    /**
     * $expectedUpdatedAt (the updated_at the editor opened) turns a save over someone else's
     * newer change into a 409 instead of a silent overwrite.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function update(int $id, array $input, string $expectedUpdatedAt = ''): array
    {
        return self::afterWrite(VersionModel::mutate(static function (array $rows) use ($id, $input, $expectedUpdatedAt): array {
            $index = self::indexOf($rows, $id);
            if ($index === null) {
                return ['ok' => false, 'error' => 'This version no longer exists.', 'status' => 404];
            }

            $existing = self::toRecord($rows[$index]);
            if ($expectedUpdatedAt !== '' && $expectedUpdatedAt !== $existing['updated_at']) {
                return [
                    'ok' => false,
                    'error' => 'Someone else changed this version after you opened it. Reload the page to see their changes.',
                    'status' => 409,
                ];
            }

            $built = self::buildRecord($input, $rows, $existing);
            if (!$built['ok']) {
                return $built;
            }

            $record = ['id' => $id] + $built['record'] + [
                'created_at' => $existing['created_at'],
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            // array_merge keeps any column a newer build added to the row.
            $rows[$index] = array_merge($rows[$index], self::toRow($record));

            return ['ok' => true, 'rows' => $rows, 'version' => $record];
        }));
    }

    /**
     * @return array<string,mixed>
     */
    public static function delete(int $id): array
    {
        return self::afterWrite(VersionModel::mutate(static function (array $rows) use ($id): array {
            $index = self::indexOf($rows, $id);
            if ($index === null) {
                return ['ok' => false, 'error' => 'This version no longer exists.', 'status' => 404];
            }

            $record = self::toRecord($rows[$index]);
            array_splice($rows, $index, 1);

            return ['ok' => true, 'rows' => $rows, 'version' => $record];
        }));
    }

    /**
     * @return array<string,mixed>
     */
    public static function publish(int $id): array
    {
        return self::setPublished($id, true);
    }

    /**
     * @return array<string,mixed>
     */
    public static function unpublish(int $id): array
    {
        return self::setPublished($id, false);
    }

    /**
     * @return array<string,mixed>
     */
    private static function setPublished(int $id, bool $published): array
    {
        return self::afterWrite(VersionModel::mutate(static function (array $rows) use ($id, $published): array {
            $index = self::indexOf($rows, $id);
            if ($index === null) {
                return ['ok' => false, 'error' => 'This version no longer exists.', 'status' => 404];
            }

            $record = self::toRecord($rows[$index]);
            if ($record['published'] === $published) {
                return ['ok' => true, 'version' => $record]; // nothing to write
            }

            $record['published'] = $published;
            $record['updated_at'] = date('Y-m-d H:i:s');
            $rows[$index] = array_merge($rows[$index], self::toRow($record));

            return ['ok' => true, 'rows' => $rows, 'version' => $record];
        }));
    }

    /**
     * Refresh the app version label (AppVersionService) after every successful write.
     *
     * @param array<string,mixed> $result
     * @return array<string,mixed>
     */
    private static function afterWrite(array $result): array
    {
        if (!empty($result['ok'])) {
            AppVersionService::forget();
        }

        return $result;
    }

    /**
     * Validate and normalize one submission against the rows it will join.
     *
     * @param array<string,mixed> $input
     * @param list<array<string,string>> $rows
     * @param array<string,mixed>|null $existing the record being edited
     * @return array{ok:bool,record?:array<string,mixed>,error?:string,errors?:array<string,string>,status?:int}
     */
    private static function buildRecord(array $input, array $rows, ?array $existing): array
    {
        $errors = [];

        $type = strtolower(trim(self::inputString($input, 'type')));
        if (!isset(self::TYPES[$type])) {
            $errors['type'] = 'Choose Frontend, Backend or Full Application.';
        }

        $level = strtolower(trim(self::inputString($input, 'version_level')));
        if (!isset(self::LEVELS[$level])) {
            $errors['version_level'] = 'Choose Major, Minor or Patch.';
        }

        $mode = strtolower(trim(self::inputString($input, 'version_mode')));
        if (!isset(self::MODES[$mode])) {
            $errors['version_mode'] = 'Choose Automatic or Manual.';
        }

        $dateTime = self::normalizeDateTime(self::inputString($input, 'date_time'));
        if ($dateTime === null) {
            $errors['date_time'] = 'Enter a valid release date and time.';
        }

        $rawDescription = self::inputString($input, 'description');
        if (strlen($rawDescription) > self::DESCRIPTION_MAX_BYTES) {
            $errors['description'] = 'The release notes are too long (100 KB at most).';
        }

        $version = '';
        if ($mode === 'manual') {
            $version = SemverHelper::normalize(self::inputString($input, 'version')) ?? '';
            if ($version === '') {
                $errors['version'] = 'Use the MAJOR.MINOR.PATCH format, for example 1.4.0.';
            }
        } elseif ($mode === 'automatic' && !isset($errors['type']) && !isset($errors['version_level'])) {
            // Editing an automatic version without changing its level keeps its number; the type has no line of its own.
            $keep = $existing !== null
                && $existing['version_mode'] === 'automatic'
                && $existing['version_level'] === $level
                && SemverHelper::isValid($existing['version']);
            if ($keep) {
                $version = $existing['version'];
            } else {
                $base = self::highestOfType(self::toRecords($rows), null, (int) ($existing['id'] ?? 0));
                $version = SemverHelper::bump($base['version'] ?? '', $level);
                if (!SemverHelper::isValid($version)) {
                    $errors['version'] = 'The next version number is out of range.';
                    $version = '';
                }
            }
        }

        if ($version !== '') {
            foreach (self::toRecords($rows) as $other) {
                if ($existing !== null && $other['id'] === $existing['id']) {
                    continue;
                }
                if (SemverHelper::compare($other['version'], $version) === 0) {
                    $errors['version'] = (self::TYPES[$other['type']] ?? 'Version') . ' ' . $version . ' already exists.';
                    break;
                }
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'error' => 'Please check the highlighted fields.', 'errors' => $errors, 'status' => 422];
        }

        return [
            'ok' => true,
            'record' => [
                'date_time'     => $dateTime,
                'type'          => $type,
                'version'       => $version,
                'version_level' => $level,
                'version_mode'  => $mode,
                'published'     => self::toBool($input['published'] ?? false),
                'description'   => HtmlSanitizer::clean($rawDescription),
            ],
        ];
    }

    /**
     * 'Y-m-d H:i:s' from the forms' 'Y-m-d\TH:i' (datetime-local) or the CSV's own format; null
     * for anything else, including dates PHP would roll over (Feb 30 → Mar 2).
     */
    public static function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($parsed !== false && $parsed->format($format) === $value) {
                $year = (int) $parsed->format('Y');

                return $year >= 1970 && $year <= 2999 ? $parsed->format('Y-m-d H:i:s') : null;
            }
        }

        return null;
    }

    /**
     * @param list<array<string,string>> $rows
     * @return list<array<string,mixed>>
     */
    private static function toRecords(array $rows): array
    {
        $records = [];
        foreach ($rows as $row) {
            $record = self::toRecord($row);
            if ($record['id'] > 0) {
                $records[] = $record; // a hand-added row without an id cannot be edited, so it is not listed
            }
        }

        return $records;
    }

    /**
     * @param array<string,string> $row
     * @return array<string,mixed>
     */
    private static function toRecord(array $row): array
    {
        $id = trim((string) ($row['id'] ?? ''));

        return [
            'id'            => preg_match('/^\d{1,9}$/', $id) === 1 ? (int) $id : 0,
            'date_time'     => trim((string) ($row['date_time'] ?? '')),
            'type'          => strtolower(trim((string) ($row['type'] ?? ''))),
            'version'       => trim((string) ($row['version'] ?? '')),
            'version_level' => strtolower(trim((string) ($row['version_level'] ?? ''))),
            'version_mode'  => strtolower(trim((string) ($row['version_mode'] ?? ''))) === 'automatic' ? 'automatic' : 'manual',
            'published'     => self::toBool($row['published'] ?? ''),
            'description'   => (string) ($row['description'] ?? ''),
            'created_at'    => trim((string) ($row['created_at'] ?? '')),
            'updated_at'    => trim((string) ($row['updated_at'] ?? '')),
        ];
    }

    /**
     * @param array<string,mixed> $record
     * @return array<string,string>
     */
    private static function toRow(array $record): array
    {
        $row = [];
        foreach (VersionModel::COLUMNS as $column) {
            $value = $record[$column] ?? '';
            $row[$column] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }

        return $row;
    }

    /**
     * @param list<array<string,mixed>> $records
     * @return list<array<string,mixed>>
     */
    private static function sortNewestFirst(array $records): array
    {
        usort($records, static function (array $a, array $b): int {
            return strcmp($b['date_time'], $a['date_time'])
                ?: SemverHelper::compare($b['version'], $a['version'])
                ?: $b['id'] <=> $a['id'];
        });

        return $records;
    }

    /**
     * @param array<int,array<string,mixed>> $records
     * @param string|null $type null for the highest of any type
     * @return array<string,mixed>|null
     */
    private static function highestOfType(array $records, ?string $type, int $excludeId): ?array
    {
        $best = null;
        foreach ($records as $record) {
            if (($type !== null && $record['type'] !== $type) || $record['id'] === $excludeId || !SemverHelper::isValid($record['version'])) {
                continue;
            }
            if ($best === null || SemverHelper::compare($record['version'], $best['version']) > 0) {
                $best = $record;
            }
        }

        return $best;
    }

    /**
     * @param list<array<string,string>> $rows
     */
    private static function indexOf(array $rows, int $id): ?int
    {
        foreach ($rows as $index => $row) {
            if (self::toRecord($row)['id'] === $id) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<array<string,string>> $rows
     */
    private static function nextId(array $rows): int
    {
        $max = 0;
        foreach ($rows as $row) {
            $max = max($max, self::toRecord($row)['id']);
        }

        return $max + 1;
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return is_scalar($value) && in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * A posted field as a string; '' for a missing or non-scalar one (a JSON array would
     * otherwise become "Array" with a warning).
     *
     * @param array<string,mixed> $input
     */
    private static function inputString(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }
}
