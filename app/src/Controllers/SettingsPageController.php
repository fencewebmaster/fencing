<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Presenters\SettingsPresenter;
use Fc\Admin\Services\AdminContext;

final class SettingsPageController extends BaseController
{
    private const TABS = ['theme', 'branding', 'fence-colors', 'catalog', 'system', 'integration', 'project-plan', 'console'];

    // Legacy/alternate ?tab= spellings still linked from old bookmarks and JS.
    private const TAB_ALIASES = [
        'fencecolors' => 'fence-colors',
        'catalog-settings' => 'catalog',
        'catalogsettings' => 'catalog',
        'integrations' => 'integration',
        'dev-mode' => 'console',
        'devmode' => 'console',
        'dev' => 'console',
    ];

    public function index(AdminContext $context): void
    {
        $initialTab = $this->resolveInitialTab();

        $context->pageTitle    = 'Settings';
        $context->route        = 'settings';
        $context->isSettings   = true;
        $context->settingsPage = SettingsPresenter::viewData(
            $context->adminBase,
            $context->appBase,
            $initialTab
        );
    }

    private function resolveInitialTab(): string
    {
        return $this->resolveTabParam(self::TABS, 'theme', self::TAB_ALIASES, true);
    }
}
