<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Services\AdminContext;

final class SettingsPageController extends Controller
{
    private const TABS = ['theme', 'branding', 'fence-colors', 'catalog', 'system', 'integration', 'dev-mode'];

    public function index(AdminContext $context): void
    {
        if (!function_exists('fc_settings_admin_view_data')) {
            require_once FC_ROOT . '/config/settings_admin.php';
        }

        $initialTab = $this->resolveInitialTab();

        $context->pageTitle    = 'Settings';
        $context->route        = 'settings';
        $context->isSettings   = true;
        $context->settingsPage = $this->buildViewData($context, $initialTab);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewData(AdminContext $context, string $initialTab): array
    {
        if (!function_exists('fc_settings_admin_view_data')) {
            require_once FC_ROOT . '/config/settings_admin.php';
        }

        return fc_settings_admin_view_data(
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
        if ($tab === 'devmode' || $tab === 'dev') {
            $tab = 'dev-mode';
        }

        return in_array($tab, self::TABS, true) ? $tab : 'theme';
    }
}
