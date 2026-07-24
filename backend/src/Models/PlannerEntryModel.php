<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

/**
 * Planner entries data access (wp_planners).
 */
final class PlannerEntryModel
{
    public static function ensureLoaded(): void
    {
        if (!function_exists('fc_planners_list_entries')) {
            require_once FC_ROOT . '/config/planners.php';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function list(
        string $search = '',
        string $status = '',
        int $limit = 50,
        int $offset = 0,
        bool $withStatuses = false,
        bool $withTotal = false,
        string $timeframe = '',
        string $state = '',
        array $fenceTypes = [],
        ?array $dateBounds = null,
        string $trashView = 'all',
        string $dateField = 'created_at',
        string $postcode = '',
        array $devices = [],
        array $browsers = [],
        ?int $sectionsMin = null,
        ?int $sectionsMax = null,
        ?int $quoteLoadsMin = null,
        ?int $quoteLoadsMax = null
    ): array {
        self::ensureLoaded();

        return fc_planners_list_entries(
            $search,
            $status,
            $limit,
            $offset,
            $withStatuses,
            $withTotal,
            $timeframe,
            $state,
            $fenceTypes,
            $dateBounds,
            $trashView,
            $dateField,
            $postcode,
            $devices,
            $browsers,
            $sectionsMin,
            $sectionsMax,
            $quoteLoadsMin,
            $quoteLoadsMax
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getByPlannerId(string $plannerId): array
    {
        self::ensureLoaded();

        return fc_planners_get_entry($plannerId);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getById(int $entryId): array
    {
        self::ensureLoaded();

        return fc_planners_get_entry_by_id($entryId);
    }

    /**
     * @return array<string, mixed>
     */
    public static function statuses(): array
    {
        self::ensureLoaded();

        return fc_planners_distinct_statuses();
    }

    /**
     * @return list<string>
     */
    public static function statusList(): array
    {
        self::ensureLoaded();

        return fc_planners_get_statuses();
    }
}
