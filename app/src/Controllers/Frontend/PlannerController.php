<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Models\PlannerPageModel;
use Fc\Admin\Services\PlannerSessionService;

/**
 * Public fence planner page (/planner).
 */
final class PlannerController extends BaseFrontendController
{
    public function index(): void
    {
        $this->startSession();

        $action = (string) $this->request->input('action', '');
        $site   = $this->request->input('site', '');
        $sid    = $this->request->input('sid', '');

        if ($action === 'clear-all' || $site || $sid) {
            PlannerSessionService::clearPlannerSessions();
        }

        if ($site || $sid) {
            $this->switchSite((string) $sid, (string) $site);
        }

        PlannerPageModel::ensureSessionSite();

        // Fence catalog + fc_color()/fc_state() helpers must exist before any session hydration.
        $fences = $this->fences();

        $quote = [
            'res'     => [],
            'failed'  => false,
            'error'   => '',
            'attempt' => '',
        ];

        $qid = $this->request->input('qid', '');
        if ($qid) {
            // &silent=1 comes only from the admin entry page's planner links — see PlannerEntryPresenter::plannerUrl().
            $quote = PlannerPageModel::loadQuote((string) $qid, !empty($this->request->input('silent', '')));
        } elseif (!empty($_SESSION['planner_id'])) {
            // Returning from project-plan: reload the saved quote so session + JS match latest edits.
            $reloaded = PlannerPageModel::reloadSessionQuote();
            if ($reloaded !== null) {
                $quote = $reloaded;
            }
        }

        $res  = $quote['res'];
        $info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

        if (!$quote['failed'] && (!is_object($res) || empty($res->fence_data)) && !empty($info['fences'])) {
            $res = PlannerPageModel::fenceInfoFromSession($info);
        }

        $site_info        = PlannerPageModel::currentSiteInfo();
        $fc_site_logo_url = PlannerPageModel::siteLogoUrl($site_info);

        $fc_session_project_plans = PlannerSessionService::clientProjectPlansFromSession();
        if (is_object($res) && $fc_session_project_plans !== '') {
            $res->project_plans_data = $fc_session_project_plans;
        }

        $_SESSION['live_mode'] = PlannerPageModel::isLiveMode();

        view('frontend.planner.index', [
            'fences'                   => $fences,
            'info'                     => $info,
            'res'                      => $res,
            'site_info'                => $site_info,
            'fc_site_logo_url'         => $fc_site_logo_url,
            'fc_session_project_plans' => $fc_session_project_plans,
            'load_quote_failed'        => $quote['failed'],
            'load_quote_error'         => $quote['error'],
            'load_quote_attempt'       => $quote['attempt'],
            'seo'                      => PlannerPageModel::seo($site_info, (string) $qid !== ''),
            'style_image_sizes'        => PlannerPageModel::styleImageSizes($fences),
        ]);
    }

    /**
     * ?site=domain / ?sid=id — pin the requested site into the session and bounce back
     * to a clean planner URL (never returns).
     */
    private function switchSite(string $sid, string $domain): void
    {
        $redirect_to    = UrlHelper::baseUrl('planner');
        $query_vars     = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
        $new_query_vars = array_diff_key(UrlHelper::queryVars($query_vars), ['sid' => 1, 'site' => 1]);

        $site = PlannerPageModel::findRequestedSite($sid, $domain);

        if ($site !== null) {
            $_SESSION['site'] = $site;
            header('Location: ' . $redirect_to . '?' . http_build_query($new_query_vars));
        } else {
            header('Location: ' . UrlHelper::toUrl((string) $this->request->input('url', '')));
        }

        exit;
    }
}
