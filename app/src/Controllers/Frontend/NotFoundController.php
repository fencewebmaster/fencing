<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Core\NotFoundHandler;

/**
 * Frontend 404 (Apache rewrite / ErrorDocument target).
 */
final class NotFoundController extends BaseFrontendController
{
    public function index(): void
    {
        NotFoundHandler::abort('frontend');
    }
}
