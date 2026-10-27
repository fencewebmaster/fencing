<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Response;
use Fc\Admin\Presenters\VersionPresenter;
use Fc\Admin\Services\AdminContext;

/**
 * Changelogs (route changelogs; the page was called Version Manager, then Releases, and its
 * internal names still say Version Manager): the version history in writable/versions.csv.
 * Server-rendered and read-only, because that file ships with the code.
 */
final class VersionManagerPageController extends BaseController
{
    public function index(AdminContext $context): void
    {
        $context->pageTitle        = 'Changelogs';
        $context->route            = 'changelogs';
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
