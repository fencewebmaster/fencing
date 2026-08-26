<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Presenters\DashboardPresenter;
use Fc\Admin\Services\AdminContext;

final class DashboardController extends BaseController
{
    public function index(AdminContext $context): void
    {
        $context->pageTitle   = 'Dashboard';
        $context->route       = 'dashboard';
        $context->isDashboard = true;
        $context->dashboardPage = DashboardPresenter::pageData(
            $context->adminBase,
            $context->appBase,
            $this->request->allQuery()
        );
    }
}
