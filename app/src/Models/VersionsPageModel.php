<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use DateTimeImmutable;
use Fc\Admin\Core\FrontendApplication;
use Fc\Admin\Helpers\HtmlSanitizer;
use Fc\Admin\Services\VersionService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\SeoSettings;

/**
 * Assembles the public version history page (/versions): published versions only, newest
 * release first, PER_PAGE more per "Show older releases" click. The link reloads the page
 * with the next batch appended (no script), jumping to the first new release.
 */
final class VersionsPageModel
{
    private const PER_PAGE = 10;

    /** A ?page= deep enough to list any real history; it only bounds the work a crafted URL can ask for. */
    private const MAX_PAGE = 500;

    /**
     * @param array<string, mixed> $query Raw $_GET.
     * @return array<string, mixed>
     */
    public static function build(array $query): array
    {
        $type = strtolower(trim(is_scalar($query['type'] ?? null) ? (string) $query['type'] : ''));
        if (!isset(VersionService::TYPES[$type])) {
            $type = '';
        }
        $page = min(self::MAX_PAGE, max(1, (int) (is_scalar($query['page'] ?? null) ? $query['page'] : 1)));

        $published = VersionService::published();
        $counts = array_fill_keys(array_keys(VersionService::TYPES), 0);
        foreach ($published as $version) {
            if (isset($counts[$version['type']])) {
                $counts[$version['type']]++;
            }
        }

        $listed = $type === ''
            ? $published
            : array_values(array_filter($published, static fn (array $v): bool => $v['type'] === $type));
        $shown = min(count($listed), $page * self::PER_PAGE);
        $latestId = $published[0]['id'] ?? 0;

        $releases = [];
        foreach (array_slice($listed, 0, $shown) as $version) {
            $releases[] = self::release($version, $version['id'] === $latestId);
        }

        $moreUrl = '';
        if ($shown < count($listed)) {
            $params = array_filter(['type' => $type, 'page' => $page + 1]);
            $moreUrl = url('versions') . '?' . http_build_query($params) . '#release-' . $listed[$shown]['id'];
        }

        $filters = [[
            'label' => 'All',
            'count' => count($published),
            'href' => url('versions'),
            'is_active' => $type === '',
        ]];
        foreach (VersionService::TYPES as $key => $label) {
            $filters[] = [
                'label' => $label,
                'count' => $counts[$key],
                'href' => url('versions') . '?type=' . rawurlencode($key),
                'is_active' => $type === $key,
            ];
        }

        $appBase = FrontendApplication::basePath();
        $branding = BrandingSettings::get();
        $appName = trim((string) ($branding['appName'] ?? '')) ?: 'Fencing Calculator';
        $seo = SeoSettings::get();

        return [
            'fcVersionsTitle' => 'Version History | ' . $appName,
            'fcVersionsDescription' => 'Release notes and version history for ' . $appName . ', newest first.',
            'fcVersionsRobots' => SeoSettings::robots($seo, true),
            'fcVersionsLang' => SeoSettings::language(),
            'fcVersionsCanonical' => url('versions'),
            'fcVersionsAppName' => $appName,
            'fcVersionsLogoUrl' => BrandingSettings::logoUrl($appBase, $branding),
            'fcVersionsFaviconUrl' => BrandingSettings::faviconUrl($appBase, $branding),
            'fcVersionsPlannerUrl' => url('planner'),
            'fcVersionsFilters' => $filters,
            'fcVersionsShowFilters' => count(array_filter($counts)) > 1,
            'fcVersionsReleases' => $releases,
            'fcVersionsEmptyText' => $type === ''
                ? 'No releases have been published yet.'
                : 'No ' . VersionService::TYPES[$type] . ' releases have been published yet.',
            'fcVersionsMoreUrl' => $moreUrl,
            'fcVersionsRemaining' => count($listed) - $shown,
            'fcVersionsYear' => date('Y'),
        ];
    }

    /**
     * @param array<string, mixed> $version VersionService record
     * @return array<string, mixed>
     */
    private static function release(array $version, bool $isLatest): array
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $version['date_time']);

        return [
            'anchor' => 'release-' . $version['id'],
            'version_label' => 'v' . $version['version'],
            'type' => (string) $version['type'],
            'type_label' => VersionService::TYPES[$version['type']] ?? (string) $version['type'],
            'level' => (string) $version['version_level'],
            'level_label' => VersionService::LEVELS[$version['version_level']] ?? (string) $version['version_level'],
            'date_label' => $date !== false ? $date->format('F j, Y') : (string) $version['date_time'],
            'date_iso' => $date !== false ? $date->format('Y-m-d\TH:i') : '',
            'is_latest' => $isLatest,
            // Sanitized on save; cleaned again here because this HTML is printed unescaped.
            'notes_html' => HtmlSanitizer::clean((string) $version['description']),
        ];
    }
}
