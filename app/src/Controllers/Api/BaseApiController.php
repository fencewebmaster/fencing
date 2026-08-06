<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Controllers\BaseController;

/**
 * Base for instance-based API controllers (CodeIgniter 4 convention).
 * Most API controllers in this app are still static utility classes (dispatch()-only) —
 * only the ones already instance-shaped extend this. See the restructuring plan for scope.
 */
abstract class BaseApiController extends BaseController
{
}
