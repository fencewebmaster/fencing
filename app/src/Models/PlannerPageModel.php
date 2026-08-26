<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Services\Database;
use Fc\Admin\Services\PlannerRecordService;
use Fc\Admin\Services\PlannerSessionService;
use Fc\Admin\Services\SiteRegistryService;

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
        if (!empty($_SESSION['site'])) {
            return;
        }

        $_SESSION['site'] = SiteRegistryService::all($_SERVER['HTTP_HOST'] ?? '', 'domain', true);
    }

    /**
     * Load a saved quote by its public Quote ID and hydrate the session from it.
     *
     * @return array{res:object|array<mixed>,failed:bool,error:string,attempt:string}
     */
    public static function loadQuote(string $qid): array
    {
        $qid = trim($qid);

        $db  = new Database();
        $res = PlannerRecordService::isValidPlannerId($qid)
            ? $db->select_where('planners', '`planner_id`="' . $qid . '"')
            : [];

        if ($res && is_object($res) && !PlannerRecordService::rowIsTrashed($res)) {
            // Clear fence session data
            PlannerSessionService::clearPlannerSessions();

            $_SESSION['planner_id'] = $qid;
            $_SESSION['site'] = SiteRegistryService::all($_SERVER['HTTP_HOST'] ?? '', 'domain', true);

            PlannerSessionService::hydrateFromRow($res);
            PlannerRecordService::markReloaded($qid);

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
}
