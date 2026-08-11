<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Fixed option lists for the planner quote form — delivery state, project timeframe
 * and the "other items needed" extras.
 *
 * These used to be inline arrays inside three global functions in writable/settings.php,
 * which is a data directory. The data lives here now; the legacy `fc_state()`,
 * `fc_timeframe()` and `fc_extra_needed()` functions still exist as thin shims over this
 * class (app/src/Helpers/fc_functions.php) because the planner views call them by name —
 * including indirectly through ArrayHelper::mapCallable().
 */
final class PlannerOptionSettings
{
    /**
     * Australian states/territories, keyed by the code stored on the planner row.
     *
     * @return array<string, string>
     */
    public static function states(): array
    {
        return [
            'ACT' => 'Australian Capital Territory',
            'NSW' => 'New South Wales',
            'NT'  => 'Northern Territory',
            'QLD' => 'Queensland',
            'SA'  => 'South Australia',
            'TAS' => 'Tasmania',
            'VIC' => 'Victoria',
            'WA'  => 'Western Australia',
        ];
    }

    /**
     * Display name for a stored state code, or null when the code is unknown.
     */
    public static function stateLabel(string $code): ?string
    {
        return self::states()[$code] ?? null;
    }

    /**
     * How soon the customer wants the job done.
     *
     * @return array<string, string>
     */
    public static function timeframes(): array
    {
        return [
            'asap'    => 'ASAP - Within 24hrs',
            'soon'    => 'SOON - This Week',
            'later'   => 'LATER - This Month',
            'looking' => 'NIL - Just Looking',
        ];
    }

    /**
     * Display name for a stored timeframe slug, or null when the slug is unknown.
     */
    public static function timeframeLabel(string $slug): ?string
    {
        return self::timeframes()[$slug] ?? null;
    }

    /**
     * Extras offered as checkboxes in the planner / Download Your Project Plans.
     *
     * "Nothing extra" is a separate radio (see modal/submit/form/other-items-needed.php),
     * not a member of this list.
     *
     * @return array<string, string>
     */
    public static function extraOptions(): array
    {
        return [
            'pump-enclosure' => 'Pump Enclosure',
        ];
    }

    /**
     * Every extra label the app can render, including retired choices that older saved
     * quotes and emails still reference.
     *
     * @return array<string, string>
     */
    public static function extraLabels(): array
    {
        return array_merge(self::extraOptions(), [
            'pool-covers'       => 'Pool Covers',
            'decking'           => 'Decking',
            'pergola'           => 'Pergola',
            'shed'              => 'Shed',
            'outdoor-furniture' => 'Outdoor Furniture',
            'outdoor-kitchen'   => 'Outdoor Kitchen',
        ]);
    }

    /**
     * Human-readable summary for a stored `extra` value (a comma-separated slug list).
     *
     * Unknown slugs are skipped; when nothing resolves, the caller gets the same
     * "nothing extra" wording the planner has always shown.
     */
    public static function extraLabel(string $csv): string
    {
        $labels = self::extraLabels();
        $found  = [];

        foreach (array_map('trim', explode(',', $csv)) as $slug) {
            if (array_key_exists($slug, $labels)) {
                $found[] = $labels[$slug];
            }
        }

        return $found !== [] ? implode(', ', $found) : 'Nothing Extra, Just Fencing';
    }
}
