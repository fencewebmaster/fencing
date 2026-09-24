<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Services\PlannerSessionService;

/**
 * App root (/) — the planner is the only landing page, one permanent redirect away.
 */
final class HomeController extends BaseFrontendController
{
    public function index(): void
    {
        $redirect_to = UrlHelper::baseUrl('planner');

        $query_vars = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

        if ($query_vars) {
            header('Location: ' . $redirect_to . $query_vars, true, 301);
            exit;
        }

        // A bare root visit still starts a fresh planner (this used to be a second hop via ?site=), so
        // the 301 must never be cached or a returning visitor would skip the reset.
        $this->startSession();
        PlannerSessionService::clearPlannerSessions();

        header('Cache-Control: no-store');
        header('Location: ' . $redirect_to, true, 301);
    }
}
