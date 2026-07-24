<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\Controller;
use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Core\Request;
use Fc\Admin\Models\PlannerEntryModel;

final class EntriesApiController extends Controller
{
    public function handle(): void
    {
        header('Cache-Control: private, max-age=0, must-revalidate');

        $action = $this->request->action();

        if ($this->request->isPost()) {
            switch ($action) {
                case 'trash':
                    $this->bulkTrash(true);
                    break;
                case 'restore':
                    $this->bulkTrash(false);
                    break;
                case 'delete':
                    $this->bulkDelete();
                    break;
                case 'export':
                    $this->exportSelected();
                    break;
                case 'import':
                    $this->importEntries();
                    break;
                default:
                    JsonResponse::error('Unknown action.', 400);
            }
        }

        if (!$this->request->isGet()) {
            JsonResponse::error('Method not allowed.', 405);
        }

        switch ($action) {
            case 'list':
                $this->list();
                break;
            case 'get':
                $this->get();
                break;
            case 'statuses':
                $this->statuses();
                break;
            default:
                JsonResponse::error('Unknown action.', 400);
        }
    }

    private function list(): void
    {
        $search  = (string) $this->request->query('q', '');
        $status  = (string) $this->request->query('status', '');
        $limitRaw = strtolower(trim((string) $this->request->query('limit', '50')));
        $limit   = $limitRaw === 'all' ? 0 : (int) $limitRaw;
        $offset  = (int) $this->request->query('offset', 0);
        $withStatuses = (string) $this->request->query('statuses', '') === '1';
        $withTotal    = (string) $this->request->query('total', '') === '1';
        $view = strtolower(trim((string) $this->request->query('view', 'all'))) === 'trash' ? 'trash' : 'all';

        $result = PlannerEntryModel::list($search, $status, $limit, $offset, $withStatuses, $withTotal, '', '', [], null, $view);

        $headers = [];
        if (!empty($result['ok']) && $search === '' && $status === '' && $offset === 0 && $limit > 0 && $view === 'all') {
            $headers['Cache-Control'] = 'private, max-age=30';
        }

        JsonResponse::ok($result, !empty($result['ok']) ? 200 : 500, $headers);
    }

    private function get(): void
    {
        $plannerId = (string) $this->request->query('planner_id', '');
        $result    = PlannerEntryModel::getByPlannerId($plannerId);

        JsonResponse::ok($result, !empty($result['ok']) ? 200 : ($plannerId === '' ? 400 : 404));
    }

    private function statuses(): void
    {
        $result = PlannerEntryModel::statuses();
        JsonResponse::ok($result, !empty($result['ok']) ? 200 : 500);
    }

    private function bulkTrash(bool $trash): void
    {
        PlannerEntryModel::ensureLoaded();

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        $ids = $payload['ids'] ?? [];
        if (!is_array($ids)) {
            JsonResponse::error('Invalid entry ids.', 400);
        }

        $result = fc_planners_bulk_set_trashed($ids, $trash);
        if (empty($result['ok'])) {
            JsonResponse::error((string) ($result['error'] ?? 'Could not update entries.'), 400);
        }

        $updated = (int) ($result['updated'] ?? 0);
        $noun = $updated === 1 ? 'entry' : 'entries';
        $message = $trash
            ? ($updated . ' ' . $noun . ' moved to trash.')
            : ($updated . ' ' . $noun . ' restored.');

        JsonResponse::ok([
            'ok' => true,
            'updated' => $updated,
            'message' => $message,
        ]);
    }

    private function bulkDelete(): void
    {
        PlannerEntryModel::ensureLoaded();

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        $ids = $payload['ids'] ?? [];
        if (!is_array($ids)) {
            JsonResponse::error('Invalid entry ids.', 400);
        }

        $result = fc_planners_bulk_delete_permanently($ids);
        if (empty($result['ok'])) {
            JsonResponse::error((string) ($result['error'] ?? 'Could not delete entries.'), 400);
        }

        $updated = (int) ($result['updated'] ?? 0);
        $noun = $updated === 1 ? 'entry' : 'entries';

        JsonResponse::ok([
            'ok' => true,
            'updated' => $updated,
            'message' => $updated . ' ' . $noun . ' permanently deleted.',
        ]);
    }

    private function exportSelected(): void
    {
        PlannerEntryModel::ensureLoaded();

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            $payload = [];
        }

        $ids = $payload['ids'] ?? [];
        if (!is_array($ids)) {
            JsonResponse::error('Invalid entry ids.', 400);
        }

        $result = fc_planners_export_entries_by_ids($ids);
        if (empty($result['ok'])) {
            JsonResponse::error((string) ($result['error'] ?? 'Could not export entries.'), 400);
        }

        $exported = (int) ($result['exported'] ?? 0);
        $noun = $exported === 1 ? 'entry' : 'entries';

        JsonResponse::ok([
            'ok' => true,
            'exported' => $exported,
            'message' => $exported . ' ' . $noun . ' exported.',
            'filename' => 'fc-planner-entries-' . date('Ymd-His') . '.json',
            'payload' => $result['payload'] ?? [],
        ]);
    }

    private function importEntries(): void
    {
        PlannerEntryModel::ensureLoaded();

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            JsonResponse::error('Invalid JSON body.', 400);
        }

        if (
            function_exists('fc_auth_verify_csrf')
            && !fc_auth_verify_csrf(isset($payload['csrf']) ? (string) $payload['csrf'] : null)
        ) {
            JsonResponse::error('Invalid security token. Refresh and try again.', 403);
        }

        $document = is_array($payload['document'] ?? null) ? $payload['document'] : $payload;
        $format = (string) ($document['format'] ?? '');
        $version = (int) ($document['version'] ?? 0);
        if ($format !== 'fc-planner-entries' || $version < 1) {
            JsonResponse::error('Invalid import file. Expected fc-planner-entries JSON.', 400);
        }

        $entries = $document['entries'] ?? null;
        if (!is_array($entries)) {
            JsonResponse::error('Import file has no entries.', 400);
        }

        $mode = isset($payload['mode']) ? (string) $payload['mode'] : 'overwrite';
        $result = fc_planners_import_entries($entries, $mode);
        if (empty($result['ok'])) {
            JsonResponse::error((string) ($result['error'] ?? 'Could not import entries.'), 400);
        }

        JsonResponse::ok($result);
    }

    public static function dispatch(): void
    {
        (new self(new Request()))->handle();
    }
}
