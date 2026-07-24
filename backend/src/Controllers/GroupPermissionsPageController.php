<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Services\AdminContext;

final class GroupPermissionsPageController extends Controller
{
    public function index(AdminContext $context): void
    {
        if (!function_exists('fc_group_permissions_admin_view_data')) {
            require_once FC_ROOT . '/config/permissions.php';
        }

        $context->pageTitle = 'Group Permissions';
        $context->route = 'users/group-permissions';
        $context->isGroupPermissions = true;
        $context->groupPermissionsPage = fc_group_permissions_admin_view_data($context->adminBase);
    }
}
