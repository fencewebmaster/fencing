<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Core\Request;

/**
 * App-wide base controller (CodeIgniter 4 convention). Every page/API controller
 * should extend this — not Core\Controller directly — so shared setup has one place to live.
 */
abstract class BaseController extends Controller
{
    public function initController(Request $request): void
    {
        // Caution: do not put anything above this call — mirrors CI4's own convention.
        parent::initController($request);

        // App-wide init point for future shared setup (preloading services, etc.) — currently empty.
    }
}
