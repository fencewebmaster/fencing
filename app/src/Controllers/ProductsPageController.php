<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\NotFoundHandler;
use Fc\Admin\Presenters\FenceStylePresenter;
use Fc\Admin\Presenters\StoreProductPresenter;
use Fc\Admin\Presenters\SystemProductPresenter;
use Fc\Admin\Services\AdminContext;

final class ProductsPageController extends BaseController
{
    public function fenceStyles(AdminContext $context): void
    {
        $context->pageTitle       = 'Fence Styles';
        $context->route           = 'products/fence-styles';
        $context->isProductsPage  = true;
        $context->fenceStylesPage = FenceStylePresenter::listViewData(
            $context->adminBase,
            $context->appBase,
            $this->request->allQuery()
        );
    }

    public function fenceStyleEdit(AdminContext $context, string $slug): void
    {
        $slug = trim(rawurldecode($slug));
        if ($slug === '' || str_contains($slug, '/') || str_contains($slug, '..')) {
            NotFoundHandler::abort('admin', 'Fence style not found.');
        }

        $context->pageTitle      = 'Edit Fence Style';
        $context->route          = 'products/fence-styles/edit/' . rawurlencode($slug);
        $context->isProductsPage = true;
    }

    /**
     * Store Products page. The class/property names are deliberately inverted vs the
     * route: this route is served by SystemProductPresenter over wc-products-{GO,JG}.csv,
     * and systemProducts() below by StoreProductPresenter over products.csv. The
     * inversion is consistent end-to-end; never "fix" one layer.
     */
    public function storeProducts(AdminContext $context): void
    {
        $context->pageTitle          = 'Store Products';
        $context->route              = 'products/store-products';
        $context->isProductsPage     = true;
        $context->systemProductsPage = SystemProductPresenter::viewData(
            $context->adminBase,
            $this->request->allQuery()
        );
    }

    public function systemProducts(AdminContext $context): void
    {
        $context->pageTitle        = 'System Products';
        $context->route            = 'products/system-products';
        $context->isProductsPage   = true;
        $context->storeProductsPage = StoreProductPresenter::viewData(
            $context->adminBase,
            $this->request->allQuery()
        );
    }

}
