<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Models\PlannerSubmissionModel;
use Fc\Admin\Services\CartBuilderService;
use Fc\Admin\Services\PlannerRecordService;

/**
 * Planner autosave endpoint (/submit) — writes the posted planner state to the
 * `planners` table and answers with a bare `SUCCESS:<id>` / `ERROR` string.
 */
final class SubmitController extends BaseFrontendController
{
    public function index(): void
    {
        $this->startSession();

        $fences = $this->fences();

        if ($_POST) {
            $_SESSION['fc_data'] = $_POST;
            if (isset($_SESSION['fc_data']['mobile'])) {
                $_SESSION['fc_data']['mobile'] = CartBuilderService::normalizeMobileForStorage($_SESSION['fc_data']['mobile']);
            }
        }

        PlannerSubmissionModel::syncCartFromSession();

        $planner_ref = PlannerRecordService::resolveSubmissionPlannerId($_POST['planner_id'] ?? null);
        $planner_id  = $planner_ref['planner_id'];

        $_SESSION['planner_id'] = $planner_id;

        // This save fires automatically right after a `?qid=` reload (see p1.js: fcRunQuoteReloadSubmit).
        // The planner page already set status='reloaded' for this same request cycle — don't clobber
        // it back to 'planning'.
        $is_quote_reload = !empty($_POST['is_quote_reload']);

        $payload = PlannerSubmissionModel::payload($fences, $planner_id, ['status' => !$is_quote_reload]);
        $res     = PlannerSubmissionModel::save($payload, $planner_id, $planner_ref['exists']);

        if (empty($res['success'])) {
            error_log('FC submit: could not save planner ' . $planner_id . ' — ' . (string) ($res['message'] ?? 'unknown error'));
            http_response_code(500);
            echo 'ERROR';
            exit;
        }

        echo 'SUCCESS:' . $planner_id;
        exit;
    }
}
