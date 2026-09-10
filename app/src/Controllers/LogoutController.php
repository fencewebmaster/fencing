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
        $loggedOut = AuthService::consumeOneTimeToken('logout', $token);
        if ($loggedOut) {
            AuthService::logout();
        }

        // ?logged_out=1 tells admin/login.js this was a deliberate sign-out
        // rather than any other route onto the login page, so it clears the
        // whole fc-admin envelope (theme included) instead of only the
        // session-shaped keys. Omitted when the CSRF token failed and no logout
        // actually happened. login.js strips the flag from the URL once read.
        Response::redirect(
            rtrim($context->adminBase, '/') . '/login' . ($loggedOut ? '?logged_out=1' : '')
        );
    }
}
