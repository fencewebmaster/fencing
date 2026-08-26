<?php

declare(strict_types=1);

namespace Fc\Admin\Filters;

use Fc\Admin\Core\Request;

/**
 * Contract for cross-cutting request gating around dispatch.
 *
 * before()/after() are deliberately void: an implementation that needs to short-circuit
 * a request does so internally via exit()/header()+exit(). There is no
 * "return a Response to stop dispatch" mechanism — adding one would be a real
 * control-flow change to Application.php.
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
