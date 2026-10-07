<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Response;
use Fc\Admin\Presenters\VersionPresenter;
use Fc\Admin\Services\AdminContext;

/**
 * Releases (route releases; the page was called Version Manager, and its internal names still
 * are): the version history in writable/versions.csv. The page is server-rendered; every write
 * goes through api.php?module=versions.
 */
final class VersionManagerPageController extends BaseController
{
    public function index(AdminContext $context): void
    {
        $context->pageTitle        = 'Releases';
        $context->route            = 'releases';
        $context->isVersionManager = true;

        $page = VersionPresenter::listViewData(
            $context->adminBase,
            $context->appBase,
            $this->request->allQuery(),
            $context->dateFormat
        );
        if (isset($page['redirect_url'])) {
            Response::redirect((string) $page['redirect_url']);
        }

        $context->versionManagerPage = $page;
    }
}
