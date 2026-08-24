<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Response;
use Fc\Admin\Models\UserPresenter;
use Fc\Admin\Services\AdminContext;
use Fc\Admin\Services\AdminSiteRegistry;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\DatabaseConfigService;
use Fc\Admin\Services\ImpersonationService;

final class UsersPageController extends BaseController
{
    public function index(AdminContext $context): void
    {
        $context->pageTitle = 'Users';
        $context->route     = 'users';
        $context->isUsers   = true;

        $page = UserPresenter::listViewData($context->adminBase, $context->appBase, $this->request->allQuery());
        if (isset($page['redirect_url'])) {
            Response::redirect((string) $page['redirect_url']);
        }

        $context->usersPage = $page;
    }

    public function loginAs(AdminContext $context, int $userId): void
    {
        $this->requireCsrfOrRedirect(rtrim($context->adminBase, '/') . '/users', '', 'login-as:' . $userId);

        $result = ImpersonationService::switchToUser($userId);
        $redirect = !empty($result['ok']) && !empty($result['redirect'])
            ? (string) $result['redirect']
            : rtrim($context->adminBase, '/') . '/users';

        Response::redirect($redirect);
    }

    public function switchBack(AdminContext $context): void
    {
        $this->requireCsrfOrRedirect(rtrim($context->adminBase, '/') . '/users', '', 'switch-back');

        $result = ImpersonationService::switchBack();
        $redirect = !empty($result['redirect'])
            ? (string) $result['redirect']
            : rtrim($context->adminBase, '/') . '/users';

        Response::redirect($redirect);
    }

    public function switchSite(AdminContext $context): void
    {
        $dashboard = rtrim($context->adminBase, '/') . '/dashboard';
        $this->requireCsrfOrRedirect($dashboard, (string) $this->request->post('_token', ''));

        // Keep the login/auth DB pinned — never clear or overwrite it on site switch.
        $hostKey = DatabaseConfigService::hostMysqlKey();
        if (AdminSiteRegistry::authDbKey() === '') {
            AdminSiteRegistry::setAuthDbKey($hostKey);
        }

        $siteKey = trim((string) $this->request->input('site', ''));
        if ($siteKey === '' || !AdminSiteRegistry::setSiteKey($siteKey)) {
            Response::redirect($dashboard);
        }

        // Always land on dashboard so restricted sites never leave the user on a forbidden page.
        Response::redirect($dashboard);
    }

    /**
     * Verifies the `_token` CSRF query param (falling back to $postToken when given, for
     * POST-capable routes), redirecting to $fallbackUrl and halting on failure. When
     * $oneTimePurpose is given, checks against a single-use mintOneTimeToken() value
     * instead of the reusable session-wide CSRF token (see AuthService::mintOneTimeToken()).
     */
    private function requireCsrfOrRedirect(string $fallbackUrl, string $postToken = '', ?string $oneTimePurpose = null): void
    {
        $token = (string) $this->request->query('_token', $postToken);
        $ok = $oneTimePurpose !== null
            ? AuthService::consumeOneTimeToken($oneTimePurpose, $token)
            : AuthService::verifyCsrf($token);
        if (!$ok) {
            Response::redirect($fallbackUrl);
        }
    }
}
