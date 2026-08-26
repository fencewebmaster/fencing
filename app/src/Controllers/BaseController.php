<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Core\Request;

/**
 * App-wide base controller. Every page/API controller should extend this —
 * not Core\Controller directly — so shared setup has one place to live.
 */
abstract class BaseController extends Controller
{
    public function initController(Request $request): void
    {
        // Do not put anything above this call — the parent must initialize first.
        parent::initController($request);

        // App-wide init point for future shared setup (preloading services, etc.) — currently empty.
    }

    /**
     * Resolve a ?tab= query param against an allowed list: lowercase/trim, optionally
     * dashify underscores, apply aliases, and fall back to $default when unknown.
     *
     * @param list<string> $allowed
     * @param array<string, string> $aliases
     */
    protected function resolveTabParam(array $allowed, string $default, array $aliases = [], bool $dashifyUnderscores = false): string
    {
        $tab = strtolower(trim((string) $this->request->query('tab', $default)));
        if ($dashifyUnderscores) {
            $tab = str_replace('_', '-', $tab);
        }
        $tab = $aliases[$tab] ?? $tab;

        return in_array($tab, $allowed, true) ? $tab : $default;
    }
}
