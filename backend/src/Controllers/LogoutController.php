<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Core\Response;
use Fc\Admin\Services\AdminContext;

final class LogoutController extends Controller
{
    public function index(AdminContext $context): void
    {
        if (!function_exists('fc_auth_logout')) {
            require_once FC_ROOT . '/config/auth.php';
        }

        fc_auth_logout();
        Response::redirect(rtrim($context->adminBase, '/') . '/login');
    }
}
