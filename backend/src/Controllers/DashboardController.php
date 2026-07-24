<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Services\AdminContext;

final class DashboardController extends Controller
{
    public function index(AdminContext $context): void
    {
        require_once FC_ROOT . '/config/dashboard_admin.php';

        $context->pageTitle   = 'Dashboard';
        $context->route       = 'dashboard';
        $context->isDashboard = true;
        $context->dashboardPage = fc_dashboard_admin_page_data(
            $context->adminBase,
            $context->appBase,
            $_GET
        );
    }
}
