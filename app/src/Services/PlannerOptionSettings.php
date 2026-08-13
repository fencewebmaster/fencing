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
     * Hardcoded seed for the fixed set of extra-item slugs (never derived from saved
     * overrides — this is what defines which slugs exist at all).
     *
     * @return array<string, string>
     */
    private static function legacyDefaultExtraOptions(): array
    {
        return [
            'pump-enclosure' => 'Pump Enclosure',
        ];
    }

    /**
     * Default label + conventional image path per fixed extra-item slug.
     *
     * @return list<array{slug:string,label:string,image:string}>
     */
    public static function defaultExtraItems(): array
    {
        $items = [];
        foreach (self::legacyDefaultExtraOptions() as $slug => $label) {
            $items[] = [
                'slug' => $slug,
                'label' => $label,
                'image' => 'public/assets/img/plans/webp/' . $slug . '.webp',
            ];
        }

        return $items;
    }

    /**
     * Normalize a submitted extra-item slug (lowercase letters/digits/hyphens/underscores).
     */
    public static function normalizeSlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            return null;
        }
        if (mb_strlen($slug) > 80) {
            $slug = mb_substr($slug, 0, 80);
        }

        return $slug;
    }

    /**
     * Extra items shown on the planner's "Anything Else" step: the fixed seed slugs (always
     * present, label/image overridable) plus any admin-added items beyond the seed.
     * 'image' is the raw stored override (blank = no override); 'imageDefault' is always
     * populated so callers can fall back to it without saving it (same split as the
     * Integration tab's per-site logo field). 'isOriginal' marks seed items, which can't be
     * removed or re-slugged through the admin UI.
     *
     * Saved order is authoritative — saveExtraItems() writes rows in the admin's dragged
     * order, and theme.json (a JSON object) preserves that order on read-back, so seed items
     * can be repositioned relative to admin-added ones, not just re-labelled.
     *
     * @return list<array{slug:string,label:string,image:string,imageDefault:string,isOriginal:bool}>
     */
    public static function extraItems(): array
    {
        $defaults = self::defaultExtraItems();
        $defaultsBySlug = [];
        foreach ($defaults as $default) {
            $defaultsBySlug[$default['slug']] = $default;
        }

        $file = ThemeSettings::readFile();
        $saved = isset($file['plannerExtraOptions']) && is_array($file['plannerExtraOptions'])
            ? $file['plannerExtraOptions']
            : [];

        $items = [];
        $seen = [];

        foreach ($saved as $slug => $row) {
            $slug = is_string($slug) ? self::normalizeSlug($slug) : null;
            if ($slug === null || isset($seen[$slug]) || !is_array($row)) {
                continue;
            }
            $isOriginal = isset($defaultsBySlug[$slug]);
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '' && $isOriginal) {
                $label = $defaultsBySlug[$slug]['label'];
            }
            if ($label === '' && !$isOriginal) {
                // An admin-added item needs a label to be worth showing.
                continue;
            }
            $items[] = [
                'slug' => $slug,
                'label' => $label,
                'image' => trim((string) ($row['image'] ?? '')),
                'imageDefault' => $isOriginal
                    ? $defaultsBySlug[$slug]['image']
                    : 'public/assets/img/plans/webp/' . $slug . '.webp',
                'isOriginal' => $isOriginal,
            ];
            $seen[$slug] = true;
        }

        // A seed slug never saved yet (fresh install, or never touched) still needs to show up.
        foreach ($defaults as $default) {
            if (isset($seen[$default['slug']])) {
                continue;
            }
            $items[] = [
                'slug' => $default['slug'],
                'label' => $default['label'],
                'image' => '',
                'imageDefault' => $default['image'],
                'isOriginal' => true,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{label:string,image:string}
     */
    private static function normalizeExtraItemInput(array $row): array
    {
        $label = trim((string) ($row['label'] ?? ''));
        if (mb_strlen($label) > 120) {
            $label = mb_substr($label, 0, 120);
        }
        $image = trim((string) ($row['image'] ?? ''));
        if (mb_strlen($image) > 500) {
            $image = mb_substr($image, 0, 500);
        }

        return ['label' => $label, 'image' => $image];
    }

    /**
     * Persist the extra-item list: label/image overrides for the fixed seed slugs, plus any
     * admin-added items (each needs a valid slug and a label to be kept). Seed slugs can't be
     * removed — a submission missing one is rejected rather than silently dropping it.
     *
     * @param list<array<string, mixed>> $rows
     * @return array{ok:bool,extraItems?:list<array{slug:string,label:string,image:string,imageDefault:string,isOriginal:bool}>,error?:string}
     */
    public static function saveExtraItems(array $rows): array
    {
        $requiredSlugs = array_column(self::defaultExtraItems(), 'slug');
        $bySlug = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $slug = isset($row['slug']) ? self::normalizeSlug((string) $row['slug']) : null;
            if ($slug === null) {
                continue;
            }
            $normalized = self::normalizeExtraItemInput($row);
            if (!in_array($slug, $requiredSlugs, true) && $normalized['label'] === '') {
                // A brand-new item needs at least a label to be worth keeping.
                continue;
            }
            $bySlug[$slug] = $normalized;
        }

        foreach ($requiredSlugs as $requiredSlug) {
            if (!isset($bySlug[$requiredSlug])) {
                return [
                    'ok' => false,
                    'error' => 'Cannot remove required item: ' . $requiredSlug,
                ];
            }
        }

        $path = ThemeSettings::filePath();
        $dir = dirname($path);

        if (!is_writable($dir)) {
            return ['ok' => false, 'error' => 'writable/ directory is not writable.'];
        }
        if (file_exists($path) && !is_writable($path)) {
            return ['ok' => false, 'error' => 'theme.json is not writable.'];
        }

        $existing = ThemeSettings::readFile();
        $payload = $existing;
        $payload['plannerExtraOptions'] = $bySlug;
        $payload['updatedAt'] = gmdate('c');

        $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        if (file_put_contents($tmp, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            return ['ok' => false, 'error' => 'Unable to write settings file.'];
        }
        if (!rename($tmp, $path)) {
            @unlink($tmp);
            return ['ok' => false, 'error' => 'Unable to save theme.json.'];
        }

        return ['ok' => true, 'extraItems' => self::extraItems()];
    }

    /**
     * @return array{ok:bool,extraItems:list<array{slug:string,label:string,image:string,imageDefault:string}>,defaults:list<array{slug:string,label:string,image:string}>,updatedAt?:string|null}
     */
    public static function apiPayload(): array
    {
        $file = ThemeSettings::readFile();

        return [
            'ok' => true,
            'extraItems' => self::extraItems(),
            'defaults' => self::defaultExtraItems(),
            'updatedAt' => isset($file['updatedAt']) ? (string) $file['updatedAt'] : null,
        ];
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
        $out = [];
        foreach (self::extraItems() as $item) {
            $out[$item['slug']] = $item['label'];
        }

        return $out;
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
