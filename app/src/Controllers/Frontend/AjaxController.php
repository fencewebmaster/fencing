<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Helpers\FileHelper;
use Fc\Admin\Services\PlannerRecordService;

/**
 * Small planner AJAX helpers (/ajax) that don't belong to a page.
 */
final class AjaxController extends BaseFrontendController
{
    public function index(): void
    {
        $this->startSession();

        $this->fences();

        $action = (string) $this->request->post('action', '');

        if ($action === 'get-size') {
            $this->getSize();
        }

        if ($action === 'save-post-finish') {
            $this->savePostFinish();
        }

        if ($action === 'save-fence-colors') {
            $this->saveFenceColors();
        }
    }

    /**
     * A Step 3 colour pick, saved into this session's own quote so a reload keeps it; before the first
     * save the pick waits in the browser and rides the next /submit, exactly like the post finish.
     */
    private function saveFenceColors(): void
    {
        $rows = PlannerRecordService::normalizeColorRows($this->request->post('color', ''));
        if ($rows === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid colour selection.']);
            exit;
        }

        // The session's quote only, never a posted ID: a request cannot write someone else's quote.
        $plannerId = trim((string) ($_SESSION['planner_id'] ?? ''));
        if ($plannerId === '') {
            echo json_encode(['success' => false, 'skipped' => true, 'message' => 'No saved quote yet.']);
            exit;
        }

        echo json_encode(PlannerRecordService::saveFenceColors($plannerId, $rows));
        exit;
    }

    /**
     * Step 3 Post Finish pick, saved into this session's own quote so a reload keeps it. Before the
     * first save there is no quote yet: the pick waits in the browser and rides the next /submit.
     */
    private function savePostFinish(): void
    {
        $rows = PlannerRecordService::normalizePostFinishRows($this->request->post('post_finish', ''));
        if ($rows === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid post finish.']);
            exit;
        }

        // The session's quote only, never a posted ID: a request cannot write someone else's quote.
        $plannerId = trim((string) ($_SESSION['planner_id'] ?? ''));
        if ($plannerId === '') {
            echo json_encode(['success' => false, 'skipped' => true, 'message' => 'No saved quote yet.']);
            exit;
        }

        echo json_encode(PlannerRecordService::savePostFinish($plannerId, $rows));
        exit;
    }

    /**
     * Look up the last size row whose $key column is <= the requested value.
     */
    private function getSize(): void
    {
        $name  = basename((string) $this->request->post('name', ''));
        $key   = (string) $this->request->post('key', '');
        $value = $this->request->post('value');

        if ($name === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
            echo json_encode([]);
            exit;
        }

        $rows = FileHelper::loadCsv(FC_ROOT . '/writable/sizes/' . $name . '.csv');
        $data = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if ($row[$key] <= $value) {
                $data = $row;
                continue;
            }
        }

        echo json_encode($data);
        exit;
    }
}
