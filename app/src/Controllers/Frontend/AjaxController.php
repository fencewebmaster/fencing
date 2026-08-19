<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Helpers\FileHelper;

/**
 * Small planner AJAX helpers (/ajax) that don't belong to a page.
 */
final class AjaxController extends BaseFrontendController
{
    public function index(): void
    {
        $this->startSession();

        $this->fences();

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'get-size') {
            $this->getSize();
        }
    }

    /**
     * Look up the last size row whose $key column is <= the requested value.
     */
    private function getSize(): void
    {
        $name  = basename((string) ($_POST['name'] ?? ''));
        $key   = (string) ($_POST['key'] ?? '');
        $value = $_POST['value'] ?? null;

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
