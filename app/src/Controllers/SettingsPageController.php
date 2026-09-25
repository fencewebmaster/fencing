<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Presenters\SettingsPresenter;
use Fc\Admin\Services\AdminContext;
use Fc\Admin\Services\PermissionService;

final class SettingsPageController extends BaseController
{
    private const TABS = ['theme', 'branding', 'fence-colors', 'catalog', 'system', 'integration', 'project-plan', 'seo', 'console', 'minify'];

    // Legacy/alternate ?tab= spellings still linked from old bookmarks and JS.
    private const TAB_ALIASES = [
        'fencecolors' => 'fence-colors',
        'catalog-settings' => 'catalog',
        'catalogsettings' => 'catalog',
        'integrations' => 'integration',
        'dev-mode' => 'console',
        'devmode' => 'console',
        'dev' => 'console',
        'health' => 'site-health',
        'sitehealth' => 'site-health',
        'minified' => 'minify',
        'minify-css-js' => 'minify',
    ];

    public function index(AdminContext $context): void
    {
        // Site Health is the Super Admin's alone; anyone else asking for it lands on Theme.
        $siteHealth = PermissionService::isSuperAdmin();
        $initialTab = $this->resolveInitialTab($siteHealth);
        // The Integration tab's per-site Cloudflare purge needs the topbar purge's permission too.
        $cloudflarePurge = PermissionService::can('settings.cache');

        $context->pageTitle    = 'Settings';
        $context->route        = 'settings';
        $context->isSettings   = true;
        $context->settingsPage = SettingsPresenter::viewData(
            $context->adminBase,
            $context->appBase,
            $initialTab,
            $siteHealth,
            $cloudflarePurge
        );
    }

    private function resolveInitialTab(bool $siteHealth): string
    {
        $tabs = $siteHealth ? [...self::TABS, 'site-health'] : self::TABS;

        return $this->resolveTabParam($tabs, 'theme', self::TAB_ALIASES, true);
    }
}
