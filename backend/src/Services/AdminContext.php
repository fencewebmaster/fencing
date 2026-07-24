<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Shared admin request context for layout and page controllers.
 */
final class AdminContext
{
    public string $adminBase;

    public string $appBase;

    public string $route = 'dashboard';

    public ?int $entryId = null;

    public string $pageTitle = 'Dashboard';

    public string $plannerEntriesRoute = 'planner-entries';

    public string $plannerEntriesTitle = 'Planner Entries';

    public string $plannerEntriesDetailTitle = 'Planner Entry';

    /** @var array<string, mixed> */
    public array $branding = [];

  /** @var array<string, mixed>|null */
    public ?array $entriesPage = null;

    /** @var array<string, mixed>|null */
    public ?array $entriesDetailPage = null;

    /** @var array<string, mixed>|null */
    public ?array $settingsPage = null;

    /** @var array<string, mixed>|null */
    public ?array $galleryPage = null;

    /** @var array<string, mixed>|null */
    public ?array $loginPage = null;

    /** @var array<string, mixed>|null */
    public ?array $fenceStylesPage = null;

    /** @var array<string, mixed>|null */
    public ?array $storeProductsPage = null;

    /** @var array<string, mixed>|null */
    public ?array $systemProductsPage = null;

    /** @var array<string, mixed>|null */
    public ?array $dashboardPage = null;

    /** @var array<string, mixed>|null */
    public ?array $usersPage = null;

    /** @var array<string, mixed>|null */
    public ?array $groupPermissionsPage = null;

    public bool $isEntries = false;

    public bool $isDashboard = false;

    public bool $isSettings = false;

    public bool $isGallery = false;

    public bool $isProductsPage = false;

    public bool $isUsers = false;

    public bool $isGroupPermissions = false;

    public bool $isLogin = false;

    /** @var array{id:int,login:string,email:string,display_name:string}|null */
    public ?array $authUser = null;

    /** @var array{id:int,login:string,email:string,display_name:string}|null */
    public ?array $authSwitchFrom = null;

    public string $fontsHref = '';

    public string $dateFormat = 'M. j, Y h:i A';

    public function __construct()
    {
        $this->adminBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');
        $this->appBase   = rtrim(str_replace('\\', '/', dirname($this->adminBase)), '/');
        $this->branding  = function_exists('fc_branding_get') ? (array) fc_branding_get() : [];
        $this->authUser  = function_exists('fc_auth_user') ? fc_auth_user() : null;
        $this->authSwitchFrom = function_exists('fc_auth_switch_from') ? fc_auth_switch_from() : null;
        $this->fontsHref = $this->resolveFontsHref();
        $this->dateFormat = function_exists('fc_system_date_format_php')
            ? fc_system_date_format_php()
            : 'M. j, Y h:i A';
    }

    /**
     * @return array<string, mixed>
     */
    public function toLayoutVars(): array
    {
        return [
            'fcBranding'                  => $this->branding,
            'pageTitle'                   => $this->pageTitle,
            'fcAdminBase'                 => $this->adminBase,
            'fcAdminRoute'                => $this->route,
            'fcAdminEntryId'              => $this->entryId,
            'fcPlannerEntriesRoute'       => $this->plannerEntriesRoute,
            'fcPlannerEntriesTitle'       => $this->plannerEntriesTitle,
            'fcPlannerEntriesDetailTitle' => $this->plannerEntriesDetailTitle,
            'fcAppBase'                   => $this->appBase,
            'fcDateFormat'                => $this->dateFormat,
            'fcEntriesPage'               => $this->entriesPage,
            'fcEntriesDetailPage'         => $this->entriesDetailPage,
            'fcSettingsPage'              => $this->settingsPage,
            'fcGalleryPage'               => $this->galleryPage,
            'fcLoginPage'                 => $this->loginPage,
            'fcFenceStylesPage'           => $this->fenceStylesPage,
            'fcStoreProductsPage'         => $this->storeProductsPage,
            'fcSystemProductsPage'        => $this->systemProductsPage,
            'fcDashboardPage'             => $this->dashboardPage,
            'fcUsersPage'                 => $this->usersPage,
            'fcGroupPermissionsPage'      => $this->groupPermissionsPage,
            'fcAdminIsEntries'            => $this->isEntries,
            'fcAdminIsDashboard'          => $this->isDashboard,
            'fcAdminIsSettings'           => $this->isSettings,
            'fcAdminIsGallery'            => $this->isGallery,
            'fcAdminIsProductsPage'       => $this->isProductsPage,
            'fcAdminIsUsers'              => $this->isUsers,
            'fcAdminIsGroupPermissions'   => $this->isGroupPermissions,
            'fcAdminIsLogin'              => $this->isLogin,
            'fcAuthUser'                  => $this->authUser,
            'fcAuthSwitchFrom'            => $this->authSwitchFrom,
            'fcFontsHref'                 => $this->fontsHref,
        ];
    }

    private function resolveFontsHref(): string
    {
        $fontsFile = FC_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'fonts.css';
        $href      = $this->appBase . '/assets/css/fonts.css';

        if (is_readable($fontsFile)) {
            $href .= '?v=' . filemtime($fontsFile);
        }

        return $href;
    }
}
