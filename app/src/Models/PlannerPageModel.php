<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Services\Database;
use Fc\Admin\Services\PlannerRecordService;
use Fc\Admin\Services\PlannerSessionService;
use Fc\Admin\Services\SiteRegistryService;
use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\SeoSettings;

/**
 * Data layer for the public planner page (/planner).
 *
 * Owns everything the page needs from the session, the `planners` table and the
 * site registry; Controllers\Frontend\PlannerController only decides redirects
 * and hands the result to app/views/frontend/planner/index.php.
 */
final class PlannerPageModel
{
    /**
     * Resolve the site the visitor asked for via ?site=domain / ?sid=id.
     *
     * @return array<string, mixed>|null
     */
    public static function findRequestedSite(?string $sid, ?string $domain): ?array
    {
        if ($sid !== null && $sid !== '') {
            $site = SiteRegistryService::all($sid, 'id', true);
        } else {
            $site = SiteRegistryService::all($domain, 'domain', true);
        }

        return is_array($site) && $site !== [] ? $site : null;
    }

    /**
     * Pin the current host's site into the session when nothing is selected yet.
     */
    public static function ensureSessionSite(): void
    {
        PlannerSessionService::ensureSite();
    }

    /**
     * Load a saved quote by its public Quote ID and hydrate the session from it.
     *
     * `$silent` skips markReloaded() — neither quote_load_count nor status moves, because
     * an admin viewing the quote is not the customer reopening it.
     *
     * @return array{res:object|array<mixed>,failed:bool,error:string,attempt:string}
     */
    public static function loadQuote(string $qid, bool $silent = false): array
    {
        $qid = trim($qid);

        $db  = new Database();
        $res = PlannerRecordService::isValidPlannerId($qid)
            ? $db->select_where('planners', '`planner_id`="' . $qid . '"')
            : [];

        if ($res && is_object($res) && !PlannerRecordService::rowIsTrashed($res)) {
            PlannerSessionService::clearPlannerSessions();

            $_SESSION['planner_id'] = $qid;
            $_SESSION['site'] = SiteRegistryService::all($_SERVER['HTTP_HOST'] ?? '', 'domain', true);

            PlannerSessionService::hydrateFromRow($res);
            if (!$silent) {
                PlannerRecordService::markReloaded($qid);
            }

            return [
                'res'     => PlannerSessionService::rowToJsFenceInfo($res),
                'failed'  => false,
                'error'   => '',
                'attempt' => $qid,
            ];
        }

        $trashed = $res && is_object($res) && PlannerRecordService::rowIsTrashed($res);

        return [
            'res'     => (object) [],
            'failed'  => true,
            'error'   => $trashed
                ? 'This quote is no longer available.'
                : 'No quote found for that Quote ID. Please check the ID and try again.',
            'attempt' => $qid,
        ];
    }

    /**
     * Re-read the session's active quote from the DB (e.g. returning from project-plan)
     * so session + JS reflect the latest edits.
     *
     * @return array{res:object|array<mixed>,failed:bool,error:string,attempt:string}|null
     *         Null when there is nothing to reload.
     */
    public static function reloadSessionQuote(): ?array
    {
        $plannerId = (string) ($_SESSION['planner_id'] ?? '');
        if ($plannerId === '' || !PlannerRecordService::isValidPlannerId($plannerId)) {
            return null;
        }

        $db  = new Database();
        $row = $db->select_where('planners', '`planner_id`="' . $plannerId . '"');

        if (!$row || !is_object($row)) {
            return null;
        }

        if (!PlannerRecordService::rowIsTrashed($row)) {
            PlannerSessionService::hydrateFromRow($row);

            return [
                'res'     => PlannerSessionService::rowToJsFenceInfo($row),
                'failed'  => false,
                'error'   => '',
                'attempt' => '',
            ];
        }

        PlannerSessionService::clearPlannerSessions();
        unset($_SESSION['planner_id']);

        return [
            'res'     => (object) [],
            'failed'  => true,
            'error'   => 'This quote is no longer available.',
            'attempt' => $plannerId,
        ];
    }

    /**
     * Rebuild the JS `fc_fence_info` payload from session data alone.
     *
     * p1.js reloadFencingData() only repopulates localStorage when fc_fence_info.fence_data
     * is set; without this, Overall Length / mbn in custom_fence-* can be missing when the
     * user navigates back from project-plan.
     *
     * @param array<string, mixed> $info
     */
    public static function fenceInfoFromSession(array $info): object
    {
        $fences_raw    = $info['fences'];
        $fence_ary     = is_string($fences_raw) ? json_decode($fences_raw, true) : $fences_raw;
        $section_count = is_array($fence_ary) ? count($fence_ary) : 0;

        return (object) [
            'fence_data'         => is_string($fences_raw) ? $fences_raw : json_encode($fence_ary ?: []),
            'cart_items_data'    => $info['cart_items'] ?? '[]',
            'section_count'      => $section_count,
            'project_plans_data' => $info['project_plans'] ?? '',
        ];
    }

    /**
     * Site registry row for the current host.
     *
     * @return array<string, mixed>|null
     */
    public static function currentSiteInfo(): ?array
    {
        $site = SiteRegistryService::all($_SERVER['HTTP_HOST'] ?? '', 'domain', true);

        return is_array($site) ? $site : null;
    }

    /**
     * Header logo: Settings → Integrations override for this site, else the registry asset.
     *
     * @param array<string, mixed>|null $siteInfo
     */
    public static function siteLogoUrl(?array $siteInfo): string
    {
        return SiteRegistryService::logoUrl(
            is_array($siteInfo) ? $siteInfo : (string) ($_SERVER['HTTP_HOST'] ?? '')
        );
    }

    /**
     * Demo/staging URLs run the planner in non-live mode.
     */
    public static function isLiveMode(): bool
    {
        return !UrlHelper::inUriSegment(SiteRegistryService::demoStages());
    }

    /**
     * Intrinsic size of each Step 1 style image, printed on its <img> so a tile holds its height
     * before the image arrives. Remote or unreadable images are left out.
     *
     * @param array<string, array<string, mixed>> $fences
     * @return array<string, array{0:int, 1:int}> slug => [width, height]
     */
    public static function styleImageSizes(array $fences): array
    {
        $sizes = [];

        foreach ($fences as $fence) {
            $image = (string) ($fence['image'] ?? '');
            $file  = FC_ROOT . '/' . ltrim($image, '/');

            if ($image === '' || preg_match('#^(https?:)?//#i', $image) === 1 || !is_file($file)) {
                continue;
            }

            $size = @getimagesize($file);
            if (is_array($size) && $size[0] > 0 && $size[1] > 0) {
                $sizes[(string) ($fence['slug'] ?? '')] = [(int) $size[0], (int) $size[1]];
            }
        }

        return $sizes;
    }

    /**
     * <head> SEO fields, from Settings → SEO. Quote links (?qid=) and hosts that may not be indexed
     * get noindex and no canonical; every other variant (?fence=, ?section=, ?action=) canonicalises
     * to the bare /planner URL, or to the custom canonical URL when one is set.
     *
     * @param array<string, mixed>|null $siteInfo
     * @return array{title:string, description:string, canonical:string, robots:string, meta:list<array{attr:string, key:string, content:string}>, json_ld:string}
     */
    public static function seo(?array $siteInfo, bool $isQuote): array
    {
        $seo      = SeoSettings::get();
        $branding = BrandingSettings::get();
        $siteName = SeoSettings::siteName($seo, $siteInfo);
        $vars     = ['app_name' => $branding['appName'], 'site_name' => $siteName, 'tagline' => $branding['tagline']];

        $title       = SeoSettings::renderTemplate($seo['titleTemplate'], $vars);
        $description = SeoSettings::renderTemplate($seo['descriptionTemplate'], $vars);
        $indexable   = !$isQuote && $seo['indexPlanner'] && SeoSettings::siteIndexable($seo);
        $pageUrl     = $seo['canonicalUrl'] !== '' ? $seo['canonicalUrl'] : UrlHelper::baseUrl('planner');

        $meta = $seo['socialEnabled'] ? self::socialMeta($seo, $siteInfo, $siteName, $pageUrl, $title, $description) : [];
        foreach (SeoSettings::verificationServices() as $key => $service) {
            if ($seo[$key] !== '') {
                $meta[] = ['attr' => 'name', 'key' => $service['meta'], 'content' => $seo[$key]];
            }
        }

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => $indexable && $seo['canonicalEnabled'] ? $pageUrl : '',
            'robots'      => SeoSettings::robots($seo, $seo['indexPlanner'], $isQuote),
            'meta'        => $meta,
            'json_ld'     => $seo['schemaEnabled']
                ? self::structuredData($seo, $siteInfo, (string) $branding['appName'], $siteName, $pageUrl, $description)
                : '',
        ];
    }

    /**
     * Open Graph and X card tags. og:url is the canonical planner address, so a shared quote link
     * (?qid=) previews as the planner and never carries the Quote ID.
     *
     * @param array<string, mixed>      $seo
     * @param array<string, mixed>|null $siteInfo
     * @return list<array{attr:string, key:string, content:string}>
     */
    private static function socialMeta(array $seo, ?array $siteInfo, string $siteName, string $pageUrl, string $title, string $description): array
    {
        $shareTitle = $seo['socialTitle'] !== '' ? $seo['socialTitle'] : $title;
        $image      = self::seoImage($seo['socialImage'] !== '' ? $seo['socialImage'] : $seo['siteLogo'], $siteInfo);

        $tags = [
            ['property', 'og:type', 'website'],
            ['property', 'og:site_name', $siteName],
            ['property', 'og:title', $shareTitle],
            ['property', 'og:description', $seo['socialDescription'] !== '' ? $seo['socialDescription'] : $description],
            ['property', 'og:url', $pageUrl],
            ['property', 'og:locale', str_replace('-', '_', (string) $seo['language'])],
        ];

        if ($image['url'] !== '') {
            $tags[] = ['property', 'og:image', $image['url']];
            if ($image['width'] > 0) {
                $tags[] = ['property', 'og:image:width', (string) $image['width']];
                $tags[] = ['property', 'og:image:height', (string) $image['height']];
            }
            $tags[] = ['property', 'og:image:alt', $shareTitle];
        }

        $tags[] = ['name', 'twitter:card', $seo['twitterCard']];
        if ($seo['twitterSite'] !== '') {
            $tags[] = ['name', 'twitter:site', '@' . $seo['twitterSite']];
        }

        return array_map(
            static fn (array $tag): array => ['attr' => $tag[0], 'key' => $tag[1], 'content' => $tag[2]],
            $tags
        );
    }

    /**
     * JSON-LD: the planner as a free web app published by the business behind this store. The
     * organisation's @id follows the {home}/#organization shape WordPress SEO plugins give the
     * store's own graph, so both describe the same node.
     *
     * @param array<string, mixed>      $seo
     * @param array<string, mixed>|null $siteInfo
     */
    private static function structuredData(array $seo, ?array $siteInfo, string $appName, string $siteName, string $pageUrl, string $description): string
    {
        $orgUrl = $seo['schemaUrl'] !== '' ? $seo['schemaUrl'] : trim((string) ($siteInfo['url'] ?? ''));

        $organization = array_filter([
            '@type'     => 'Organization',
            '@id'       => $orgUrl !== '' ? rtrim($orgUrl, '/') . '/#organization' : '',
            'name'      => $siteName,
            'url'       => $orgUrl,
            'logo'      => self::seoImage($seo['schemaLogo'] !== '' ? $seo['schemaLogo'] : $seo['siteLogo'], $siteInfo)['url'],
            'telephone' => $seo['schemaPhone'],
            'email'     => $seo['schemaEmail'],
            'sameAs'    => $seo['schemaSameAs'],
        ], static fn (mixed $value): bool => $value !== '' && $value !== []);

        $app = [
            '@type'               => 'WebApplication',
            '@id'                 => $pageUrl . '#app',
            'name'                => $appName,
            'url'                 => $pageUrl,
            'description'         => $description,
            'applicationCategory' => 'DesignApplication',
            'operatingSystem'     => 'Any',
            'browserRequirements' => 'Requires JavaScript.',
            'inLanguage'          => $seo['language'],
            'isAccessibleForFree' => true,
            'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => SeoSettings::CURRENCY],
            'publisher'           => isset($organization['@id']) ? ['@id' => $organization['@id']] : $organization,
        ];

        return (string) json_encode(
            ['@context' => 'https://schema.org', '@graph' => isset($organization['@id']) ? [$app, $organization] : [$app]],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
        );
    }

    /**
     * A Settings → SEO image as an absolute URL, since social sites and search engines fetch it from
     * outside, with its pixel size when it is a local file. Blank falls back to this site's logo.
     *
     * @param array<string, mixed>|null $siteInfo
     * @return array{url:string, width:int, height:int}
     */
    private static function seoImage(string $path, ?array $siteInfo): array
    {
        if ($path === '') {
            $path = SiteRegistryService::logoForDomain(
                (string) ($siteInfo['domain'] ?? ($_SERVER['HTTP_HOST'] ?? '')),
                trim((string) ($siteInfo['logo'] ?? ''))
            );
        }

        $none = ['url' => '', 'width' => 0, 'height' => 0];
        if ($path === '' || preg_match('/^data:/i', $path)) {
            return $none;
        }
        if (preg_match('#^https?://#i', $path)) {
            return ['url' => $path, 'width' => 0, 'height' => 0];
        }
        if (str_starts_with($path, '//')) {
            return ['url' => 'https:' . $path, 'width' => 0, 'height' => 0];
        }

        $rel  = ltrim(str_replace('\\', '/', $path), '/');
        $file = FC_ROOT . '/' . $rel;
        $size = is_file($file) ? @getimagesize($file) : false;

        return [
            'url'    => UrlHelper::baseUrl($rel),
            'width'  => is_array($size) ? (int) $size[0] : 0,
            'height' => is_array($size) ? (int) $size[1] : 0,
        ];
    }
}
