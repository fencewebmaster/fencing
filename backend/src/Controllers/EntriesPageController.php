<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Models\PlannerEntryModel;
use Fc\Admin\Models\PlannerEntryPresenter;
use Fc\Admin\Services\AdminContext;

final class EntriesPageController extends Controller
{
    public function index(AdminContext $context): void
    {
        $this->bootEntries($context);
        $context->pageTitle = $context->plannerEntriesTitle;
        $context->route     = $context->plannerEntriesRoute;
        $context->isEntries = true;

        if (!function_exists('fc_entries_admin_redirect_legacy_detail')) {
            require_once FC_ROOT . '/config/entries_admin.php';
        }

        fc_entries_admin_redirect_legacy_detail($context->adminBase);
        $context->entriesPage = $this->buildListView(
            fc_entries_admin_page_data($context->adminBase, $context->appBase)
        );
    }

    public function show(AdminContext $context, int $id): void
    {
        $this->bootEntries($context);
        $context->pageTitle   = $context->plannerEntriesDetailTitle;
        $context->route       = $context->plannerEntriesRoute;
        $context->entryId     = $id;
        $context->isEntries   = true;
        $context->entriesDetailPage = $this->buildDetailView(
            fc_entries_admin_detail_page_data($context->adminBase, $context->appBase, $id)
        );
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private function buildListView(array $page): array
    {
        return fc_entries_admin_build_list_view($page);
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private function buildDetailView(array $page): array
    {
        return fc_entries_admin_build_detail_view($page);
    }

    private function bootEntries(AdminContext $context): void
    {
        PlannerEntryModel::ensureLoaded();

        if (!function_exists('fc_entries_admin_page_data')) {
            require_once FC_ROOT . '/config/entries_admin.php';
        }

        PlannerEntryPresenter::fenceCatalog();
    }
}
