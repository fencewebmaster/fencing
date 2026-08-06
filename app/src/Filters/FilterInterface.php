<?php

declare(strict_types=1);

namespace Fc\Admin\Filters;

use Fc\Admin\Core\Request;

/**
 * CodeIgniter 4 convention for cross-cutting request gating (replaces the old Middleware/ classes).
 *
 * Narrower than CI4's real interface: before()/after() here are void — implementations still
 * exit()/header()+exit() internally exactly as the code they replace did. Reproducing CI4's
 * "return a Response to short-circuit" pattern would be a real control-flow change to
 * Application.php, out of scope for this pass.
 */
interface FilterInterface
{
    /**
     * @param array<int, string>|string|null $arguments
     */
    public function before(Request $request, array|string|null $arguments = null): void;

    /**
     * @param array<int, string>|string|null $arguments
     */
    public function after(Request $request, array|string|null $arguments = null): void;
}
