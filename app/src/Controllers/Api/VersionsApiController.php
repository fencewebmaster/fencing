<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Presenters\VersionPresenter;
use Fc\Admin\Services\VersionService;
use Fc\Admin\Settings\SystemSettings;

/**
 * Changelogs JSON API (api.php?module=versions): GET get only. Read-only like the page:
 * writable/versions.csv ships with the code, and a site that edited its copy could no longer
 * git pull. GroupPermissionsPresenter::keysForApi() gates it on version_manager.view_list.
 */
final class VersionsApiController extends BaseApiController
{
    public function handle(): void
    {
        $action = (string) $this->request->query('action', '');

        if ($this->request->method() !== 'GET') {
            JsonResponse::error('Method not allowed.', 405);
        }

        switch ($action) {
            case 'get':
                $this->get();
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
}
