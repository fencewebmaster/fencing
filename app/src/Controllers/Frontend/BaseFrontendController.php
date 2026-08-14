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

    /**
     * Start the public PHP session.
     */
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
     * Render a view from app/views/frontend/.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = []): void
    {
        View::render('frontend/' . ltrim($template, '/'), $data);
    }

    /**
     * Render a view from app/views/frontend/ and capture it.
     *
     * @param array<string, mixed> $data
     */
    protected function renderToString(string $template, array $data = []): string
    {
        return View::partial('frontend/' . ltrim($template, '/'), $data);
    }
}
