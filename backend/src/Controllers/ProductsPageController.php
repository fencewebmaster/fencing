<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers;

use Fc\Admin\Core\Controller;
use Fc\Admin\Services\AdminContext;

final class ProductsPageController extends Controller
{
    public function fenceStyles(AdminContext $context): void
    {
        $this->bootProductsAdmin();
        $context->pageTitle       = 'Fence Styles';
        $context->route           = 'products/fence-styles';
        $context->isProductsPage  = true;
        $context->fenceStylesPage = fc_fence_styles_admin_view_data(
            $context->adminBase,
            $this->request->allQuery()
        );
    }

    public function storeProducts(AdminContext $context): void
    {
        $this->bootProductsAdmin();
        $context->pageTitle          = 'Store Products';
        $context->route              = 'products/store-products';
        $context->isProductsPage     = true;
        $context->systemProductsPage = fc_system_products_admin_view_data(
            $context->adminBase,
            $this->request->allQuery()
        );
    }

    public function systemProducts(AdminContext $context): void
    {
        $this->bootProductsAdmin();
        $context->pageTitle        = 'System Products';
        $context->route            = 'products/system-products';
        $context->isProductsPage   = true;
        $context->storeProductsPage = fc_store_products_admin_view_data(
            $context->adminBase,
            $this->request->allQuery()
        );
    }

    private function bootProductsAdmin(): void
    {
        if (!function_exists('fc_fence_styles_admin_view_data')) {
            require_once FC_ROOT . '/config/products_admin.php';
        }
    }
}
