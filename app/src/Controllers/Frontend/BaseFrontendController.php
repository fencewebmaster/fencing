<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Controllers\BaseController;
use Fc\Admin\Core\Request;
use Fc\Admin\Core\SessionBootstrap;
use Fc\Admin\Core\View;
use Fc\Admin\Services\FenceSettingsService;

/**
 * Base controller for the public (non-admin) pages.
 *
 * Admin controllers get their session + auth context from Core\AdminBootstrap; the
 * frontend has no auth layer, so session start and fence-catalog loading are opt-in
 * per controller (the product lookup page, for one, must not start a session).
 */
abstract class BaseFrontendController extends BaseController
{
    public function __construct(?Request $request = null)
    {
        parent::__construct($request ?? new Request());
    }

    protected function startSession(): void
    {
        SessionBootstrap::start();
    }

    /**
     * Assemble (via FenceSettingsService) and return the fence catalog.
     *
     * @return array<string, mixed>
     */
    protected function fences(): array
    {
        return FenceSettingsService::fences();
    }

    /**
     * Render a view by its full dot name and capture it as a string — for the
     * HTML fragments the AJAX endpoints echo (e.g. 'frontend.partials.sections.cart-table').
     * Full pages render via the global view() helper instead.
     *
     * @param array<string, mixed> $data
     */
    protected function renderToString(string $template, array $data = []): string
    {
        return View::partial($template, $data);
    }
}
