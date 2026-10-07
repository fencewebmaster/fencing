<?php

declare(strict_types=1);

namespace Fc\Admin\Presenters;

use DateTimeImmutable;
use Fc\Admin\Helpers\HtmlSanitizer;
use Fc\Admin\Helpers\SemverHelper;
use Fc\Admin\Helpers\ViewHelper;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\PermissionService;
use Fc\Admin\Services\VersionService;

/**
 * Releases view-models: the admin list page (releases) and the record payloads its editor,
 * View dialog and the versions API share.
 */
final class VersionPresenter
{
    private const ROUTE = 'releases';

    private const PER_PAGE_OPTIONS = [20, 50, 100];

    private const DEFAULT_PER_PAGE = 20;

    private const STATUSES = ['published' => 'Published', 'unpublished' => 'Unpublished'];

    /** Sort keys, and the direction a first click on each header sorts by. */
    private const SORTS = [
        'date'    => 'desc',
        'version' => 'desc',
        'type'    => 'asc',
        'level'   => 'asc',
        'status'  => 'asc',
    ];

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function listViewData(string $adminBase, string $appBase, array $query, string $dateFormat): array
    {
        $request = self::parseRequest($query);
        $url = static fn (array $overrides = []): string => self::url($adminBase, $request, $overrides);

        $loaded = VersionService::load();
        $all = $loaded['versions'];

        // Tab counts follow the other filters, so a search shows how its matches split by type.
        $matchingExceptType = array_values(array_filter(
            $all,
            static fn (array $v): bool => self::matches($v, array_merge($request, ['type' => '']))
        ));
        $typeCounts = array_fill_keys(array_keys(VersionService::TYPES), 0);
        foreach ($matchingExceptType as $version) {
            if (isset($typeCounts[$version['type']])) {
                $typeCounts[$version['type']]++;
            }
        }

        $tabs = [[
            'label' => 'All',
            'count' => count($matchingExceptType),
            'href' => $url(['type' => '', 'page' => 1]),
            'is_active' => $request['type'] === '',
        ]];
        foreach (VersionService::TYPES as $key => $label) {
            $tabs[] = [
                'label' => $label,
                'count' => $typeCounts[$key],
                'href' => $url(['type' => $key, 'page' => 1]),
                'is_active' => $request['type'] === $key,
            ];
        }

        $filtered = array_values(array_filter($matchingExceptType, static fn (array $v): bool => self::matches($v, $request)));
        $filtered = self::sort($filtered, $request['sort'], $request['dir']);

        $total = count($filtered);
        $perPage = $request['per_page'];
        $totalPages = $request['is_all'] || $perPage <= 0 ? 1 : max(1, (int) ceil($total / $perPage));
        if (!$request['is_all'] && $request['page'] > $totalPages) {
            return ['redirect_url' => $url(['page' => $totalPages])];
        }
        $offset = $request['is_all'] ? 0 : ($request['page'] - 1) * $perPage;
        $pageItems = $request['is_all'] ? $filtered : array_slice($filtered, $offset, $perPage);

        $canEdit = PermissionService::can('version_manager.edit');
        $canDelete = PermissionService::can('version_manager.delete');
        $canPublish = PermissionService::can('version_manager.publish');

        $rows = [];
        $records = [];
        foreach ($pageItems as $version) {
            $record = self::record($version, $dateFormat);
            $records[(string) $version['id']] = $record;
            $rows[] = self::tableRow($record);
        }

        $currentPage = $request['page'];
        $pagination = [
            'show' => !$request['is_all'] && $totalPages > 1,
            'pages' => ViewHelper::paginationWindow($currentPage, $totalPages),
            'prev_url' => $currentPage > 1 ? $url(['page' => $currentPage - 1]) : '',
            'next_url' => $currentPage < $totalPages ? $url(['page' => $currentPage + 1]) : '',
        ];

        $hasFilters = $request['q'] !== '' || $request['level'] !== '' || $request['status'] !== '' || $request['type'] !== '';
        $publicUrl = rtrim($appBase, '/') . '/versions';

        return [
            'redirect_url' => null,
            'request' => $request,
            'form_action' => self::ROUTE,
            'tabs' => $tabs,
            'level_options' => self::options(VersionService::LEVELS, $request['level'], 'All levels'),
            'status_options' => self::options(self::STATUSES, $request['status'], 'All statuses'),
            'hidden_fields' => self::hiddenFields($request, ['type', 'sort', 'dir', 'per_page']),
            'per_page_hidden_fields' => self::hiddenFields($request, ['q', 'type', 'level', 'status', 'sort', 'dir']),
            'has_active_filters' => $hasFilters,
            'clear_filters_url' => $url(['q' => '', 'type' => '', 'level' => '', 'status' => '', 'page' => 1]),
            'columns' => self::columns($request, $url),
            'table_rows' => $rows,
            'has_table_rows' => $rows !== [],
            'empty_state' => $all === []
                ? [
                    'title' => 'No versions yet',
                    'text' => $canEdit
                        ? 'Add the first release to start the version history.'
                        : 'Releases appear here once an editor adds them.',
                    'show_add' => $canEdit,
                    'show_clear' => false,
                ]
                : [
                    'title' => 'No versions match',
                    'text' => 'Try another search or clear the filters.',
                    'show_add' => false,
                    'show_clear' => true,
                ],
            'count_label' => self::countLabel($total, $offset, count($pageItems)),
            'per_page_options' => self::PER_PAGE_OPTIONS,
            'per_page_value' => $request['per_page_value'],
            'pagination' => $pagination,
            'pagination_links' => ViewHelper::paginationLinks(
                $pagination['pages'],
                $currentPage,
                static fn (int $num): string => $url(['page' => $num])
            ),
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_publish' => $canPublish,
            'public_url' => $publicUrl,
            'error' => $loaded['ok'] ? '' : (string) ($loaded['error'] ?? ''),
            'type_options' => self::options(VersionService::TYPES, 'full', ''),
            'editor_level_options' => self::options(VersionService::LEVELS, 'patch', ''),
            'bootstrap_json' => ViewHelper::bootstrapJson([
                'csrf' => AuthService::csrfToken(),
                'canEdit' => $canEdit,
                'canDelete' => $canDelete,
                'canPublish' => $canPublish,
                'records' => $records,
                'types' => VersionService::TYPES,
                'levels' => VersionService::LEVELS,
                'publicUrl' => $publicUrl,
            ]),
        ];
    }

    /**
     * One version as the editor, the View dialog and the API read it.
     *
     * @param array<string, mixed> $version VersionService record
     * @return array<string, mixed>
     */
    public static function record(array $version, string $dateFormat): array
    {
        $date = self::parseDate((string) $version['date_time']);

        return [
            'id' => (int) $version['id'],
            'version' => (string) $version['version'],
            'version_label' => 'v' . $version['version'],
            'type' => (string) $version['type'],
            'type_label' => VersionService::TYPES[$version['type']] ?? (string) $version['type'],
            'version_level' => (string) $version['version_level'],
            'level_label' => VersionService::LEVELS[$version['version_level']] ?? (string) $version['version_level'],
            'version_mode' => (string) $version['version_mode'],
            'mode_label' => VersionService::MODES[$version['version_mode']] ?? '',
            'published' => (bool) $version['published'],
            'date_time' => (string) $version['date_time'],
            'date_input' => $date !== null ? $date->format('Y-m-d\TH:i') : '',
            'date_label' => $date !== null ? $date->format($dateFormat) : (string) $version['date_time'],
            // Already clean when saved; cleaned again in case the CSV was edited by hand.
            'description' => HtmlSanitizer::clean((string) $version['description']),
            'created_label' => self::formatStamp((string) $version['created_at'], $dateFormat),
            'updated_label' => self::formatStamp((string) $version['updated_at'], $dateFormat),
            'updated_at' => (string) $version['updated_at'],
        ];
    }

    /**
     * @param array<string, mixed> $record self::record()
     * @return array<string, mixed>
     */
    private static function tableRow(array $record): array
    {
        $excerpt = HtmlSanitizer::toPlainText($record['description']);
        if (mb_strlen($excerpt) > 140) {
            $excerpt = rtrim(mb_substr($excerpt, 0, 139)) . '…';
        }

        return [
            'id' => $record['id'],
            'version_label' => $record['version_label'],
            'mode_label' => $record['mode_label'],
            'type' => $record['type'],
            'type_label' => $record['type_label'],
            'level' => $record['version_level'],
            'level_label' => $record['level_label'],
            'date_label' => $record['date_label'],
            'date_title' => $record['date_time'],
            'excerpt' => $excerpt,
            'is_published' => $record['published'],
            'status_label' => $record['published'] ? 'Published' : 'Unpublished',
            'publish_action' => $record['published'] ? 'unpublish' : 'publish',
            'publish_label' => $record['published'] ? 'Unpublish' : 'Publish',
            'publish_icon' => $record['published'] ? 'fa-solid fa-eye-slash' : 'fa-solid fa-globe',
            'publish_meta' => $record['published'] ? 'Hide it from the public Version History page' : 'Show it on the public Version History page',
            'row_label' => $record['type_label'] . ' ' . $record['version_label'],
            'menu_hint' => $record['published'] ? 'Published on the public Version History page' : 'Unpublished, so only admins can see it',
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private static function parseRequest(array $query): array
    {
        $pick = static function (string $key, array $allowed) use ($query): string {
            $value = strtolower(trim(is_scalar($query[$key] ?? null) ? (string) $query[$key] : ''));

            return in_array($value, $allowed, true) ? $value : '';
        };

        $sort = $pick('sort', array_keys(self::SORTS));
        if ($sort === '') {
            $sort = 'date';
        }
        $dir = $pick('dir', ['asc', 'desc']);
        if ($dir === '') {
            $dir = self::SORTS[$sort];
        }

        $q = trim(is_scalar($query['q'] ?? null) ? (string) $query['q'] : '');
        $paging = ViewHelper::parseListPagination($query, self::PER_PAGE_OPTIONS, self::DEFAULT_PER_PAGE);

        return [
            'q' => mb_substr($q, 0, 100),
            'type' => $pick('type', array_keys(VersionService::TYPES)),
            'level' => $pick('level', array_keys(VersionService::LEVELS)),
            'status' => $pick('status', array_keys(self::STATUSES)),
            'sort' => $sort,
            'dir' => $dir,
            'page' => $paging['page_or_first'],
            'per_page' => $paging['per_page'],
            'per_page_value' => $paging['per_page_value'],
            'is_all' => $paging['is_all'],
        ];
    }

    /**
     * @param array<string, mixed> $version
     * @param array<string, mixed> $request
     */
    private static function matches(array $version, array $request): bool
    {
        if ($request['type'] !== '' && $version['type'] !== $request['type']) {
            return false;
        }
        if ($request['level'] !== '' && $version['version_level'] !== $request['level']) {
            return false;
        }
        if ($request['status'] !== '' && $version['published'] !== ($request['status'] === 'published')) {
            return false;
        }
        if ($request['q'] === '') {
            return true;
        }

        $haystack = mb_strtolower(implode(' ', [
            'v' . $version['version'],
            VersionService::TYPES[$version['type']] ?? $version['type'],
            VersionService::LEVELS[$version['version_level']] ?? $version['version_level'],
            HtmlSanitizer::toPlainText((string) $version['description']),
        ]));

        return str_contains($haystack, mb_strtolower($request['q']));
    }

    /**
     * @param list<array<string, mixed>> $versions newest release first
     * @return list<array<string, mixed>>
     */
    private static function sort(array $versions, string $sort, string $dir): array
    {
        if ($sort === 'date') {
            return $dir === 'desc' ? $versions : array_reverse($versions);
        }

        $typeOrder = array_flip(array_keys(VersionService::TYPES));
        $levelOrder = array_flip(array_keys(VersionService::LEVELS));
        $key = static function (array $v) use ($sort, $typeOrder, $levelOrder): array {
            return match ($sort) {
                'version' => [$v['version']],
                'type' => [$typeOrder[$v['type']] ?? 99],
                'level' => [$levelOrder[$v['version_level']] ?? 99],
                default => [$v['published'] ? 0 : 1],
            };
        };

        // usort is stable: ties keep the newest-first order underneath.
        usort($versions, static function (array $a, array $b) use ($sort, $dir, $key): int {
            $cmp = $sort === 'version'
                ? SemverHelper::compare($a['version'], $b['version'])
                : $key($a) <=> $key($b);

            return $dir === 'desc' ? -$cmp : $cmp;
        });

        return $versions;
    }

    /**
     * Sortable column headers: link, aria-sort and icon per column.
     *
     * @param array<string, mixed> $request
     * @return array<string, array<string, mixed>>
     */
    private static function columns(array $request, callable $url): array
    {
        $columns = [];
        foreach (array_keys(self::SORTS) as $key) {
            $active = $request['sort'] === $key;
            $nextDir = $active ? ($request['dir'] === 'asc' ? 'desc' : 'asc') : self::SORTS[$key];
            $columns[$key] = [
                'href' => $url(['sort' => $key, 'dir' => $nextDir, 'page' => 1]),
                'aria_sort' => $active ? ($request['dir'] === 'asc' ? 'ascending' : 'descending') : 'none',
                'icon' => $active
                    ? ($request['dir'] === 'asc' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down')
                    : 'fa-solid fa-sort',
                'is_active' => $active,
            ];
        }

        return $columns;
    }

    /**
     * @param array<string, string> $choices value => label
     * @return list<array{value:string,label:string,is_selected:bool}>
     */
    private static function options(array $choices, string $selected, string $allLabel): array
    {
        $options = $allLabel !== '' ? [['value' => '', 'label' => $allLabel, 'is_selected' => $selected === '']] : [];
        foreach ($choices as $value => $label) {
            $options[] = ['value' => (string) $value, 'label' => $label, 'is_selected' => $selected === (string) $value];
        }

        return $options;
    }

    /**
     * The filter state a form must resend so submitting it changes only its own fields.
     *
     * @param array<string, mixed> $request
     * @param list<string> $keys
     * @return list<array{name:string,value:string}>
     */
    private static function hiddenFields(array $request, array $keys): array
    {
        $fields = [];
        foreach (self::query($request) as $name => $value) {
            if (in_array($name, $keys, true)) {
                $fields[] = ['name' => $name, 'value' => (string) $value];
            }
        }

        return $fields;
    }

    /**
     * The non-default parts of the request, in URL form.
     *
     * @param array<string, mixed> $request
     * @return array<string, string|int>
     */
    private static function query(array $request): array
    {
        $query = [];
        foreach (['q', 'type', 'level', 'status'] as $key) {
            if ($request[$key] !== '') {
                $query[$key] = $request[$key];
            }
        }
        if ($request['sort'] !== 'date' || $request['dir'] !== 'desc') {
            $query['sort'] = $request['sort'];
            $query['dir'] = $request['dir'];
        }
        if ((string) $request['per_page_value'] !== (string) self::DEFAULT_PER_PAGE) {
            $query['per_page'] = $request['per_page_value'];
        }
        if ($request['page'] > 1) {
            $query['page'] = $request['page'];
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $overrides
     */
    private static function url(string $adminBase, array $request, array $overrides): string
    {
        $merged = array_merge($request, $overrides);
        $merged['page'] = max(1, (int) ($merged['page'] ?? 1));
        $qs = http_build_query(self::query($merged), '', '&', PHP_QUERY_RFC3986);
        $base = ViewHelper::adminUrl($adminBase, self::ROUTE);

        return $qs === '' ? $base : $base . '?' . $qs;
    }

    private static function countLabel(int $total, int $offset, int $shown): string
    {
        if ($total === 0) {
            return '0 versions';
        }
        if ($shown >= $total) {
            return $total === 1 ? '1 version' : number_format($total) . ' versions';
        }

        return 'Showing ' . number_format($offset + 1) . '–' . number_format($offset + $shown) . ' of ' . number_format($total) . ' versions';
    }

    private static function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);

        return $date !== false ? $date : null;
    }

    private static function formatStamp(string $value, string $dateFormat): string
    {
        $date = self::parseDate($value);

        return $date !== null ? $date->format($dateFormat) : $value;
    }
}
