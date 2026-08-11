<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Helpers\UrlHelper;

/**
 * App root (/) — the planner is the only landing page.
 */
final class HomeController extends BaseFrontendController
{
    public function index(): void
    {
        $redirect_to = UrlHelper::baseUrl('planner');

        $query_vars = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

        if ($query_vars) {
            header('Location: ' . $redirect_to . $query_vars);
            exit;
        }

        header('Location: ' . $redirect_to . '?site=' . $_SERVER['SERVER_NAME']);
    }
}
