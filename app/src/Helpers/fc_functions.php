<?php
/**
 * Legacy global helpers used by the planner views.
 *
 * These four functions have been called by name from the templates since long before the
 * app had a service layer — and `fc_extra_needed` is also invoked dynamically through
 * ArrayHelper::mapCallable(), so it cannot simply become a method call. They stay as
 * global shims; every one of them now delegates to the service that owns the data:
 *
 *   fc_color()        -> Settings\FenceColorSettings
 *   fc_state()        -> Settings\PlannerOptionSettings
 *   fc_timeframe()    -> Settings\PlannerOptionSettings
 *   fc_extra_needed() -> Settings\PlannerOptionSettings
 *
 * PHP class code should call those settings classes directly rather than these functions.
 * Loaded by Services\FenceSettingsService::boot(); not autoloadable (functions, not classes).
 */

declare(strict_types=1);

use Fc\Admin\Settings\FenceColorSettings;
use Fc\Admin\Settings\PlannerOptionSettings;

if (!function_exists('fc_color')) {
    /**
     * @return array<string, mixed>|null Full colour map, or one row when $val is given.
     */
    function fc_color(mixed $val = '')
    {
        $data = FenceColorSettings::legacyMap();

        return $val ? ($data[$val] ?? null) : $data;
    }
}

if (!function_exists('fc_state')) {
    /**
     * @return array<string, string>|string|null All states, or one label when $val is given.
     */
    function fc_state(mixed $val = '')
    {
        return $val
            ? PlannerOptionSettings::stateLabel((string) $val)
            : PlannerOptionSettings::states();
    }
}

if (!function_exists('fc_timeframe')) {
    /**
     * @return array<string, string>|string|null All timeframes, or one label when $val is given.
     */
    function fc_timeframe(mixed $val = '')
    {
        return $val
            ? PlannerOptionSettings::timeframeLabel((string) $val)
            : PlannerOptionSettings::timeframes();
    }
}

if (!function_exists('fc_extra_needed')) {
    /**
     * @return array<string, string>|string Selectable extras, or a label summary when $val is given.
     */
    function fc_extra_needed(mixed $val = '')
    {
        if (empty($val)) {
            return PlannerOptionSettings::extraOptions();
        }

        return PlannerOptionSettings::extraLabel((string) $val);
    }
}
