<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Core\Response;
use Fc\Admin\Services\AdminContext;

final class LoginPageController extends Controller
{
    public function index(AdminContext $context): void
    {
        if (!function_exists('fc_auth_boot')) {
            require_once FC_ROOT . '/config/auth.php';
        }

        fc_auth_boot();

        if (fc_auth_is_logged_in()) {
            Response::redirect(fc_auth_dashboard_url());
        }

        $context->pageTitle  = 'Sign in';
        $context->route      = 'login';
        $context->isLogin    = true;
        $context->loginPage  = fc_auth_login_view_data($context->adminBase, $context->appBase);
    }
}
