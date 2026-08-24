<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Models\PlannerEntryModel;
use Fc\Admin\Models\PlannerEntryPresenter;
use Fc\Admin\Services\AdminContext;

final class EntriesPageController extends BaseController
{
    public function index(AdminContext $context): void
    {
        $this->bootEntries();
        $context->pageTitle = $context->plannerEntriesTitle;
        $context->route     = $context->plannerEntriesRoute;
        $context->isEntries = true;

        $detailParam = (string) $this->request->query('detail', '');
        $redirectUrl = PlannerEntryPresenter::resolveLegacyDetailRedirect($context->adminBase, $detailParam);
        if ($redirectUrl !== null) {
            header('Location: ' . $redirectUrl, true, 301);
            exit;
        }

        $page = PlannerEntryPresenter::listViewData($context->adminBase, $context->appBase, $this->request->allQuery());
        if (isset($page['redirect_url'])) {
            header('Location: ' . $page['redirect_url']);
            exit;
        }

        $context->entriesPage = $page;
    }

    public function show(AdminContext $context, int $id): void
    {
        $this->bootEntries();
        $context->pageTitle   = $context->plannerEntriesDetailTitle;
        $context->route       = $context->plannerEntriesRoute;
        $context->entryId     = $id;
        $context->isEntries   = true;

        $returnParam = (string) $this->request->query('return', '');
        $context->entriesDetailPage = PlannerEntryPresenter::detailViewData(
            $context->adminBase,
            $context->appBase,
            $id,
            $returnParam
        );
    }

    private function bootEntries(): void
    {
        PlannerEntryModel::ensureLoaded();
        PlannerEntryPresenter::fenceCatalog();
    }
}
