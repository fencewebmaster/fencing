<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Models\GroupPermissionsPresenter;
use Fc\Admin\Services\AdminContext;

final class GroupPermissionsPageController extends BaseController
{
    public function index(AdminContext $context): void
    {
        $context->pageTitle = 'Group Permissions';
        $context->route = 'users/group-permissions';
        $context->isGroupPermissions = true;
        $context->groupPermissionsPage = GroupPermissionsPresenter::adminViewData(
            $context->adminBase,
            (string) $this->request->query('role', '')
        );
    }
}
