<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Services\AdminContext;

final class GalleryPageController extends Controller
{
    private const TABS = ['library', 'upload'];

    public function index(AdminContext $context): void
    {
        if (!function_exists('fc_gallery_admin_view_data')) {
            require_once FC_ROOT . '/config/gallery_admin.php';
        }

        $initialTab = $this->resolveInitialTab();

        $context->pageTitle  = 'Media Library';
        $context->route      = 'gallery';
        $context->isGallery  = true;
        $context->galleryPage = $this->buildViewData($context, $initialTab);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewData(AdminContext $context, string $initialTab): array
    {
        if (!function_exists('fc_gallery_admin_view_data')) {
            require_once FC_ROOT . '/config/gallery_admin.php';
        }

        return fc_gallery_admin_view_data(
            $context->adminBase,
            $context->appBase,
            $initialTab
        );
    }

    private function resolveInitialTab(): string
    {
        $tab = strtolower(trim((string) $this->request->query('tab', 'library')));

        return in_array($tab, self::TABS, true) ? $tab : 'library';
    }
}
