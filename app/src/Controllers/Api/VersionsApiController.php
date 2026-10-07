<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Presenters\VersionPresenter;
use Fc\Admin\Services\PermissionService;
use Fc\Admin\Services\VersionService;
use Fc\Admin\Settings\SystemSettings;

/**
 * Version Manager JSON API (api.php?module=versions). GET get / next-version; POST create,
 * update, delete, publish, unpublish — each POST carries the session CSRF token as
 * payload.csrf. GroupPermissionsPresenter::keysForApi() gates each action by its permission;
 * create/update also need version_manager.publish to set or change the Published flag, so
 * an editor without it cannot publish through the form.
 */
final class VersionsApiController extends BaseApiController
{
    public function handle(): void
    {
        $action = (string) $this->request->query('action', '');
        $method = $this->request->method();

        if ($method === 'GET') {
            switch ($action) {
                case 'get':
                    $this->get();
                    break;
                case 'next-version':
                    $this->nextVersion();
                    break;
                default:
                    JsonResponse::error('Unknown action.', 400);
            }
        }

        if ($method !== 'POST') {
            JsonResponse::error('Method not allowed.', 405);
        }

        $payload = $this->request->jsonBody();
        if (!is_array($payload)) {
            JsonResponse::error('Invalid JSON body.', 400);
        }
        if (!self::csrfOk($payload)) {
            JsonResponse::error('Invalid security token. Refresh and try again.', 403);
        }

        switch ($action) {
            case 'create':
                $this->create($payload);
                break;
            case 'update':
                $this->update($payload);
                break;
            case 'delete':
                $this->respond(VersionService::delete(self::id($payload)), 'Deleted');
                break;
            case 'publish':
                $this->respond(VersionService::publish(self::id($payload)), 'Published');
                break;
            case 'unpublish':
                $this->respond(VersionService::unpublish(self::id($payload)), 'Unpublished');
                break;
            default:
                JsonResponse::error('Unknown action.', 400);
        }
    }

    private function get(): void
    {
        $version = VersionService::find((int) $this->request->query('id', 0));
        if ($version === null) {
            JsonResponse::error('This version no longer exists.', 404);
        }

        JsonResponse::ok(['ok' => true, 'version' => VersionPresenter::record($version, SystemSettings::dateFormatPhp())]);
    }

    private function nextVersion(): void
    {
        $result = VersionService::nextVersion(
            strtolower(trim((string) $this->request->query('type', ''))),
            strtolower(trim((string) $this->request->query('level', ''))),
            (int) $this->request->query('exclude', 0)
        );
        if (empty($result['ok'])) {
            JsonResponse::error((string) ($result['error'] ?? 'Could not work out the next version.'), 400);
        }

        JsonResponse::ok($result);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function create(array $payload): void
    {
        if (!empty($payload['published']) && !PermissionService::can('version_manager.publish')) {
            JsonResponse::error('You do not have permission to publish versions. Save it unpublished.', 403);
        }

        $this->respond(VersionService::add($payload), 'Added');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function update(array $payload): void
    {
        $id = self::id($payload);
        $current = VersionService::find($id);
        if ($current === null) {
            JsonResponse::error('This version no longer exists.', 404);
        }
        if (!empty($payload['published']) !== $current['published'] && !PermissionService::can('version_manager.publish')) {
            JsonResponse::error('You do not have permission to publish or unpublish versions.', 403);
        }

        $this->respond(VersionService::update($id, $payload, (string) ($payload['expected_updated_at'] ?? '')), 'Saved');
    }

    /**
     * @param array<string, mixed> $result VersionService write result
     */
    private function respond(array $result, string $verb): void
    {
        if (empty($result['ok'])) {
            JsonResponse::send([
                'ok' => false,
                'error' => (string) ($result['error'] ?? 'Could not save the version.'),
                'errors' => (array) ($result['errors'] ?? []),
            ], (int) ($result['status'] ?? 400));
        }

        $record = VersionPresenter::record((array) $result['version'], SystemSettings::dateFormatPhp());
        JsonResponse::ok([
            'ok' => true,
            'message' => $verb . ' ' . $record['type_label'] . ' ' . $record['version_label'] . '.',
            'version' => $record,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function id(array $payload): int
    {
        $id = $payload['id'] ?? 0;

        return is_int($id) || (is_string($id) && preg_match('/^\d{1,9}$/', $id) === 1) ? (int) $id : 0;
    }
}
