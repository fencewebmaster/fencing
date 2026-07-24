<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Core\Response;
use Fc\Admin\Services\AdminContext;

final class UsersPageController extends Controller
{
    public function index(AdminContext $context): void
    {
        if (!function_exists('fc_users_admin_page_data')) {
            require_once FC_ROOT . '/config/users_admin.php';
        }

        $context->pageTitle = fc_users_admin_page_title();
        $context->route     = fc_users_admin_route_slug();
        $context->isUsers   = true;
        $context->usersPage = fc_users_admin_build_list_view(
            fc_users_admin_page_data($context->adminBase, $context->appBase)
        );
    }

    public function loginAs(AdminContext $context, int $userId): void
    {
        require_once FC_ROOT . '/config/auth.php';

        $token = (string) ($_GET['_token'] ?? '');
        if (!fc_auth_verify_csrf($token)) {
            Response::redirect(rtrim($context->adminBase, '/') . '/users');
        }

        $result = fc_auth_switch_to_user($userId);
        $redirect = !empty($result['ok']) && !empty($result['redirect'])
            ? (string) $result['redirect']
            : rtrim($context->adminBase, '/') . '/users';

        Response::redirect($redirect);
    }

    public function switchBack(AdminContext $context): void
    {
        require_once FC_ROOT . '/config/auth.php';

        $token = (string) ($_GET['_token'] ?? '');
        if (!fc_auth_verify_csrf($token)) {
            Response::redirect(rtrim($context->adminBase, '/') . '/users');
        }

        $result = fc_auth_switch_back();
        $redirect = !empty($result['redirect'])
            ? (string) $result['redirect']
            : rtrim($context->adminBase, '/') . '/users';

        Response::redirect($redirect);
    }

    public function switchSite(AdminContext $context): void
    {
        require_once FC_ROOT . '/config/auth.php';
        require_once FC_ROOT . '/config/admin_sites.php';

        $token = (string) ($_GET['_token'] ?? ($_POST['_token'] ?? ''));
        if (!fc_auth_verify_csrf($token)) {
            Response::redirect(rtrim($context->adminBase, '/') . '/dashboard');
        }

        // Keep the login/auth DB pinned — never clear or overwrite it on site switch.
        $hostKey = function_exists('fc_db_host_mysql_key') ? fc_db_host_mysql_key() : 'localhost';
        if (fc_admin_auth_db_key() === '') {
            fc_admin_set_auth_db_key($hostKey);
        }

        $siteKey = trim((string) ($_GET['site'] ?? ($_POST['site'] ?? '')));
        if ($siteKey === '' || !fc_admin_set_site_key($siteKey)) {
            Response::redirect(rtrim($context->adminBase, '/') . '/dashboard');
        }

        // Always land on dashboard so restricted sites never leave the user on a forbidden page.
        Response::redirect(rtrim($context->adminBase, '/') . '/dashboard');
    }
}
