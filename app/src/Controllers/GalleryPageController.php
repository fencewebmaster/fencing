<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Presenters\GalleryPresenter;
use Fc\Admin\Services\AdminContext;

final class GalleryPageController extends BaseController
{
    private const TABS = ['library', 'upload'];

    public function index(AdminContext $context): void
    {
        $context->pageTitle   = 'Media Library';
        $context->route       = 'gallery';
        $context->isGallery   = true;
        $context->galleryPage = GalleryPresenter::viewData(
            $context->adminBase,
            $context->appBase,
            $this->resolveInitialTab()
        );
    }

    private function resolveInitialTab(): string
    {
        return $this->resolveTabParam(self::TABS, 'library');
    }
}
