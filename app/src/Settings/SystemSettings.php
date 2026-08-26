<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

/**
 * FC System settings — date defaults (saved to writable/theme.json as system).
 */
final class SystemSettings
{
    /**
     * @return array{
     *   dashboardDefaultDatePeriod:string,
     *   entriesDefaultDatePeriod:string,
     *   entriesDefaultDateField:string,
     *   dateFormat:string,
     *   presenceUpdateIntervalSeconds:int,
     *   presenceOnlineWindowMinutes:int,
     *   activityRelativeHours:int
     * }
     */
    public static function defaults(): array
    {
        return [
            'dashboardDefaultDatePeriod' => 'today',
            'entriesDefaultDatePeriod' => 'today',
            'entriesDefaultDateField' => 'updated_at',
            'dateFormat' => 'M. j, Y h:i A',
            'presenceUpdateIntervalSeconds' => 20,
            'presenceOnlineWindowMinutes' => 3,
            'activityRelativeHours' => 24,
        ];
    }

    /**
     * Date period choices for the default filter (excludes Custom Range).
     *
     * @return array<string, string>
     */
    public static function datePeriodChoices(): array
    {
        $choices = [
            'all' => 'All dates',
        ];
        foreach (\Fc\Admin\Presenters\PlannerEntryPresenter::datePeriodOptions() as $key => $label) {
            if ($key === 'custom') {
                continue;
            }
            $choices[$key] = $label;
        }

        return $choices;
    }

    /**
     * @return array<string, string>
     */
    public static function dateFieldChoices(): array
    {
        return \Fc\Admin\Presenters\PlannerEntryPresenter::dateFieldOptions();
    }

    /**
     * Display labels are live examples; values are PHP date() format strings.
     *
     * @return array<string, string>
     */
    public static function dateFormatChoices(): array
    {
        return [
            'M. j, Y' => 'Jul. 20, 2026',
            'F j, Y' => 'July 20, 2026',
            'M. j, Y H:i' => 'Jul. 20, 2026 17:04',
            'M. j, Y h:i A' => 'Jul. 20, 2026 05:04 PM',
            'F j, Y H:i' => 'July 20, 2026 17:04',
            'F j, Y h:i A' => 'July 20, 2026 05:04 PM',
            'm/d/Y H:i' => '07/20/2026 17:04',
            'm/d/Y h:i A' => '07/20/2026 05:04 PM',
            'm-d-Y H:i' => '07-20-2026 17:04',
            'm-d-Y h:i A' => '07-20-2026 05:04 PM',
            'Y-m-d H:i' => '2026-07-20 17:04',
            'Y-m-d h:i A' => '2026-07-20 05:04 PM',
            'Y/m/d H:i' => '2026/07/20 17:04',
            'Y/m/d h:i A' => '2026/07/20 05:04 PM',
            'Y-d-m H:i' => '2026-20-07 17:04',
            'Y-d-m h:i A' => '2026-20-07 05:04 PM',
            'Y/d/m H:i' => '2026/20/07 17:04',
            'Y/d/m h:i A' => '2026/20/07 05:04 PM',
        ];
    }

    /**
     * Migrate legacy single defaultDatePeriod / defaultDateField keys.
     *
     * @param array<string, mixed> $saved
     * @return array<string, mixed>
     */
    public static function migrateLegacyKeys(array $saved): array
    {
        if (
            !isset($saved['entriesDefaultDatePeriod'])
            && isset($saved['defaultDatePeriod'])
            && is_string($saved['defaultDatePeriod'])
        ) {
            $saved['entriesDefaultDatePeriod'] = $saved['defaultDatePeriod'];
            if (!isset($saved['dashboardDefaultDatePeriod'])) {
                $saved['dashboardDefaultDatePeriod'] = $saved['defaultDatePeriod'];
            }
        }

        if (
            !isset($saved['entriesDefaultDateField'])
            && isset($saved['defaultDateField'])
            && is_string($saved['defaultDateField'])
        ) {
            $saved['entriesDefaultDateField'] = $saved['defaultDateField'];
        }

        unset($saved['defaultDatePeriod'], $saved['defaultDateField']);

        return $saved;
    }

    /**
     * @return array{
     *   dashboardDefaultDatePeriod:string,
     *   entriesDefaultDatePeriod:string,
     *   entriesDefaultDateField:string,
     *   dateFormat:string,
     *   presenceUpdateIntervalSeconds:int,
     *   presenceOnlineWindowMinutes:int,
     *   activityRelativeHours:int
     * }
     */
    public static function get(): array
    {
        $defaults = self::defaults();
        $saved = ThemeSettings::section('system');
        $saved = self::migrateLegacyKeys($saved);

        return self::normalize(array_merge($defaults, $saved));
    }

    /**
     * @param array<string, mixed> $input
     * @return array{
     *   dashboardDefaultDatePeriod:string,
     *   entriesDefaultDatePeriod:string,
     *   entriesDefaultDateField:string,
     *   dateFormat:string,
     *   presenceUpdateIntervalSeconds:int,
     *   presenceOnlineWindowMinutes:int,
     *   activityRelativeHours:int
     * }
     */
    public static function normalize(array $input): array
    {
        $input = self::migrateLegacyKeys($input);
        $defaults = self::defaults();
        $periodChoices = self::datePeriodChoices();
        $fieldChoices = self::dateFieldChoices();
        $formatChoices = self::dateFormatChoices();

        $dashboardPeriod = trim((string) ($input['dashboardDefaultDatePeriod'] ?? $defaults['dashboardDefaultDatePeriod']));
        if (!array_key_exists($dashboardPeriod, $periodChoices)) {
            $dashboardPeriod = $defaults['dashboardDefaultDatePeriod'];
        }

        $entriesPeriod = trim((string) ($input['entriesDefaultDatePeriod'] ?? $defaults['entriesDefaultDatePeriod']));
        if (!array_key_exists($entriesPeriod, $periodChoices)) {
            $entriesPeriod = $defaults['entriesDefaultDatePeriod'];
        }

        $field = trim((string) ($input['entriesDefaultDateField'] ?? $defaults['entriesDefaultDateField']));
        if (!array_key_exists($field, $fieldChoices)) {
            $field = $defaults['entriesDefaultDateField'];
        }

        $format = (string) ($input['dateFormat'] ?? $defaults['dateFormat']);
        if (!array_key_exists($format, $formatChoices)) {
            $format = $defaults['dateFormat'];
        }

        $updateSeconds = (int) ($input['presenceUpdateIntervalSeconds'] ?? $defaults['presenceUpdateIntervalSeconds']);
        if ($updateSeconds < 5) {
            $updateSeconds = (int) $defaults['presenceUpdateIntervalSeconds'];
        }
        if ($updateSeconds > 300) {
            $updateSeconds = 300;
        }

        $onlineMinutes = (int) ($input['presenceOnlineWindowMinutes'] ?? $defaults['presenceOnlineWindowMinutes']);
        if ($onlineMinutes < 1) {
            $onlineMinutes = (int) $defaults['presenceOnlineWindowMinutes'];
        }
        if ($onlineMinutes > 60) {
            $onlineMinutes = 60;
        }

        $relativeHours = (int) ($input['activityRelativeHours'] ?? $defaults['activityRelativeHours']);
        if ($relativeHours < 1) {
            $relativeHours = (int) $defaults['activityRelativeHours'];
        }
        if ($relativeHours > 168) {
            $relativeHours = 168;
        }

        return [
            'dashboardDefaultDatePeriod' => $dashboardPeriod,
            'entriesDefaultDatePeriod' => $entriesPeriod,
            'entriesDefaultDateField' => $field,
            'dateFormat' => $format,
            'presenceUpdateIntervalSeconds' => $updateSeconds,
            'presenceOnlineWindowMinutes' => $onlineMinutes,
            'activityRelativeHours' => $relativeHours,
        ];
    }

    /**
     * How often online activity is refreshed while a user uses the admin (seconds).
     */
    public static function presenceUpdateIntervalSeconds(): int
    {
        $seconds = (int) (self::get()['presenceUpdateIntervalSeconds'] ?? 20);

        return max(5, min(300, $seconds));
    }

    /**
     * Seconds a user stays "online" after last activity.
     */
    public static function presenceOnlineWindowSeconds(): int
    {
        $minutes = (int) (self::get()['presenceOnlineWindowMinutes'] ?? 3);

        return max(30, $minutes * 60);
    }

    /**
     * Seconds to show relative activity labels (just now / X ago) before switching to a timestamp.
     */
    public static function activityRelativeSeconds(): int
    {
        $hours = (int) (self::get()['activityRelativeHours'] ?? 24);

        return max(3600, $hours * 3600);
    }

    /**
     * Resolve stored period key to filter value (`''` = all dates).
     */
    public static function resolvePeriodKey(string $period): string
    {
        if ($period === 'all' || $period === '') {
            return '';
        }

        return $period;
    }

    /**
     * Period key used by Planner Entries when no date_period is in the URL.
     */
    public static function resolvedEntriesDefaultDatePeriod(): string
    {
        $period = (string) (self::get()['entriesDefaultDatePeriod'] ?? 'today');

        return self::resolvePeriodKey($period);
    }

    /**
     * Period key used by Dashboard charts when no date filter is active.
     */
    public static function resolvedDashboardDefaultDatePeriod(): string
    {
        $period = (string) (self::get()['dashboardDefaultDatePeriod'] ?? 'today');

        return self::resolvePeriodKey($period);
    }

    /**
     * PHP date() format for admin datetime display.
     */
    public static function dateFormatPhp(): string
    {
        $format = (string) (self::get()['dateFormat'] ?? '');
        $choices = self::dateFormatChoices();
        if ($format !== '' && array_key_exists($format, $choices)) {
            return $format;
        }

        return (string) (self::defaults()['dateFormat'] ?? 'M. j, Y h:i A');
    }

    /**
     * @param array<string, mixed> $system
     * @return array{ok:bool,system?:array<string,mixed>,error?:string}
     */
    public static function save(array $system): array
    {
        $next = self::normalize($system);

        $path = ThemeSettings::filePath();
        $dir = dirname($path);

        if (!is_writable($dir)) {
            return ['ok' => false, 'error' => 'writable/ directory is not writable.'];
        }
        if (file_exists($path) && !is_writable($path)) {
            return ['ok' => false, 'error' => 'theme.json is not writable.'];
        }

        $existing = ThemeSettings::readFile();
        $payload = [
            'colors' => isset($existing['colors']) && is_array($existing['colors']) ? $existing['colors'] : ThemeSettings::get(),
            'system' => $next,
            'updatedAt' => gmdate('c'),
        ];
        if (isset($existing['branding']) && is_array($existing['branding'])) {
            $payload['branding'] = $existing['branding'];
        }
        if (isset($existing['fenceColors']) && is_array($existing['fenceColors'])) {
            $payload['fenceColors'] = $existing['fenceColors'];
        }
        if (isset($existing['catalog']) && is_array($existing['catalog'])) {
            $payload['catalog'] = $existing['catalog'];
        }

        $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $written = file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            return ['ok' => false, 'error' => 'Unable to write settings file.'];
        }
        if (!rename($tmp, $path)) {
            @unlink($tmp);

            return ['ok' => false, 'error' => 'Unable to save theme.json.'];
        }

        return [
            'ok' => true,
            'system' => $next,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function apiPayload(): array
    {
        return [
            'ok' => true,
            'system' => self::get(),
            'defaults' => self::defaults(),
            'datePeriodChoices' => self::datePeriodChoices(),
            'dateFieldChoices' => self::dateFieldChoices(),
            'dateFormatChoices' => self::dateFormatChoices(),
            'updatedAt' => ThemeSettings::updatedAt(),
        ];
    }
}
