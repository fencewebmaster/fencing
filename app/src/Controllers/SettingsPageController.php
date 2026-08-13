<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Models\SettingsPresenter;
use Fc\Admin\Services\AdminContext;

final class SettingsPageController extends BaseController
{
    private const TABS = ['theme', 'branding', 'fence-colors', 'catalog', 'system', 'integration', 'project-plan', 'console'];

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
        $tab = strtolower(trim((string) $this->request->query('tab', 'theme')));
        $tab = str_replace('_', '-', $tab);

        if ($tab === 'fencecolors') {
            $tab = 'fence-colors';
        }
        if ($tab === 'catalog-settings' || $tab === 'catalogsettings') {
            $tab = 'catalog';
        }
        if ($tab === 'integrations') {
            $tab = 'integration';
        }
        if ($tab === 'dev-mode' || $tab === 'devmode' || $tab === 'dev') {
            $tab = 'console';
        }

        return in_array($tab, self::TABS, true) ? $tab : 'theme';
    }
}
