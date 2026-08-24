<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Models\ProjectPlanPageModel;

/**
 * Public project plan / quote summary page (/project-plan).
 */
final class ProjectPlanController extends BaseFrontendController
{
    public function index(): void
    {
        $this->startSession();

        $info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
        $cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];

        if (empty($info) && !empty($this->request->query('qid', ''))) {
            $info = ProjectPlanPageModel::restoreFromQuote((string) $this->request->query('qid', ''));
        }

        if (empty($info)) {
            header('Location: ./');
            die();
        }

        date_default_timezone_set('Asia/Manila');

        $fences = $this->fences();

        $cart = ProjectPlanPageModel::refreshCartFromPlan($info, $cart);
        ProjectPlanPageModel::ensureCartImages($cart);

        $this->view('project-plan/index.php', [
            'fences'        => $fences,
            'info'          => $info,
            'cart'          => $cart,
            'site_info'     => ProjectPlanPageModel::sessionSiteInfo(),
            'fc_fence_info' => ProjectPlanPageModel::fenceInfo($info),
        ]);
    }
}
