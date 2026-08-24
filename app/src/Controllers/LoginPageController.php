<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Response;
use Fc\Admin\Models\AuthPresenter;
use Fc\Admin\Services\AdminContext;
use Fc\Admin\Services\AuthService;

final class LoginPageController extends BaseController
{
    public function index(AdminContext $context): void
    {
        AuthService::boot();

        if (AuthService::isLoggedIn()) {
            Response::redirect(AuthService::dashboardUrl());
        }

        $context->pageTitle  = 'Sign in';
        $context->route      = 'login';
        $context->isLogin    = true;
        $context->loginPage  = AuthPresenter::loginViewData(
            $context->adminBase,
            $context->appBase,
            (string) $this->request->query('redirect', '')
        );
    }
}
