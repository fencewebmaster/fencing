<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Core\Response;
use Fc\Admin\Models\VersionsPageModel;

/**
 * Public version history (/versions): the published entries of the admin's Version Manager.
 *
 * Session-less, like /lookup: the page sets no cookie and loads no planner assets.
 */
final class VersionsController extends BaseFrontendController
{
    public function index(): void
    {
        // asset() and url() are dirname(REQUEST_URI)-based: on /versions/ every link would resolve one folder too deep.
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '');
        if (strlen($path) > 1 && str_ends_with($path, '/')) {
            $query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
            Response::redirect(rtrim($path, '/') . ($query !== '' ? '?' . $query : ''), 301);
        }

        view('frontend.versions.index', VersionsPageModel::build($this->request->allQuery()));
    }
}
