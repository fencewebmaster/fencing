<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Response;
use Fc\Admin\Services\AdminContext;
use Fc\Admin\Services\AuthService;

final class LogoutController extends BaseController
{
    public function index(AdminContext $context): void
    {
        $token = (string) $this->request->query('_token', '');
        if (AuthService::consumeOneTimeToken('logout', $token)) {
            AuthService::logout();
        }

        Response::redirect(rtrim($context->adminBase, '/') . '/login');
    }
}
