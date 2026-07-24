<?php
/**
 * FC System settings — date defaults (saved to data/theme.json as system).
 */

declare(strict_types=1);

require_once __DIR__ . '/theme.php';

/**
 * @return array{
 *   dashboardDefaultDatePeriod:string,
 *   entriesDefaultDatePeriod:string,
 *   entriesDefaultDateField:string,
 *   dateFormat:string
 * }
 */
function fc_system_defaults(): array
{
    return [
        'dashboardDefaultDatePeriod' => 'today',
        'entriesDefaultDatePeriod' => 'today',
        'entriesDefaultDateField' => 'updated_at',
        'dateFormat' => 'M. j, Y h:i A',
    ];
}

/**
 * Date period choices for the default filter (excludes Custom Range).
 *
 * @return array<string, string>
 */
function fc_system_date_period_choices(): array
{
    if (!function_exists('fc_entries_admin_date_period_options')) {
        require_once __DIR__ . '/entries_admin.php';
    }

    $choices = [
        'all' => 'All dates',
    ];
    foreach (fc_entries_admin_date_period_options() as $key => $label) {
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
function fc_system_date_field_choices(): array
{
    if (!function_exists('fc_entries_admin_date_field_options')) {
        require_once __DIR__ . '/entries_admin.php';
    }

    return fc_entries_admin_date_field_options();
}

/**
 * Display labels are live examples; values are PHP date() format strings.
 *
 * @return array<string, string>
 */
function fc_system_date_format_choices(): array
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
function fc_system_migrate_legacy_keys(array $saved): array
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
 *   dateFormat:string
 * }
 */
function fc_system_get(): array
{
    $defaults = fc_system_defaults();
    $file = fc_theme_read_file();
    $saved = isset($file['system']) && is_array($file['system']) ? $file['system'] : [];
    $saved = fc_system_migrate_legacy_keys($saved);

    return fc_system_normalize(array_merge($defaults, $saved));
}

/**
 * @param array<string, mixed> $input
 * @return array{
 *   dashboardDefaultDatePeriod:string,
 *   entriesDefaultDatePeriod:string,
 *   entriesDefaultDateField:string,
 *   dateFormat:string
 * }
 */
function fc_system_normalize(array $input): array
{
    $input = fc_system_migrate_legacy_keys($input);
    $defaults = fc_system_defaults();
    $periodChoices = fc_system_date_period_choices();
    $fieldChoices = fc_system_date_field_choices();
    $formatChoices = fc_system_date_format_choices();

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

    return [
        'dashboardDefaultDatePeriod' => $dashboardPeriod,
        'entriesDefaultDatePeriod' => $entriesPeriod,
        'entriesDefaultDateField' => $field,
        'dateFormat' => $format,
    ];
}

/**
 * Resolve stored period key to filter value (`''` = all dates).
 */
function fc_system_resolve_period_key(string $period): string
{
    if ($period === 'all' || $period === '') {
        return '';
    }

    return $period;
}

/**
 * Period key used by Planner Entries when no date_period is in the URL.
 */
function fc_system_resolved_entries_default_date_period(): string
{
    $period = (string) (fc_system_get()['entriesDefaultDatePeriod'] ?? 'today');

    return fc_system_resolve_period_key($period);
}

/**
 * Period key used by Dashboard charts when no date filter is active.
 */
function fc_system_resolved_dashboard_default_date_period(): string
{
    $period = (string) (fc_system_get()['dashboardDefaultDatePeriod'] ?? 'today');

    return fc_system_resolve_period_key($period);
}

/**
 * @deprecated Use fc_system_resolved_entries_default_date_period()
 */
function fc_system_resolved_default_date_period(): string
{
    return fc_system_resolved_entries_default_date_period();
}

/**
 * PHP date() format for admin datetime display.
 */
function fc_system_date_format_php(): string
{
    $format = (string) (fc_system_get()['dateFormat'] ?? '');
    $choices = fc_system_date_format_choices();
    if ($format !== '' && array_key_exists($format, $choices)) {
        return $format;
    }

    return (string) (fc_system_defaults()['dateFormat'] ?? 'M. j, Y h:i A');
}

/**
 * @param array<string, mixed> $system
 * @return array{ok:bool,system?:array<string,mixed>,error?:string}
 */
function fc_system_save(array $system): array
{
    $next = fc_system_normalize($system);

    $path = fc_theme_file_path();
    $dir = dirname($path);

    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'data/ directory is not writable.'];
    }
    if (file_exists($path) && !is_writable($path)) {
        return ['ok' => false, 'error' => 'theme.json is not writable.'];
    }

    $existing = fc_theme_read_file();
    $payload = [
        'colors' => isset($existing['colors']) && is_array($existing['colors']) ? $existing['colors'] : fc_theme_get(),
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
function fc_system_api_payload(): array
{
    $file = fc_theme_read_file();

    return [
        'ok' => true,
        'system' => fc_system_get(),
        'defaults' => fc_system_defaults(),
        'datePeriodChoices' => fc_system_date_period_choices(),
        'dateFieldChoices' => fc_system_date_field_choices(),
        'dateFormatChoices' => fc_system_date_format_choices(),
        'updatedAt' => isset($file['updatedAt']) ? (string) $file['updatedAt'] : null,
    ];
}
