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

    public function fenceStyleEdit(AdminContext $context, string $slug): void
    {
        $slug = trim(rawurldecode($slug));
        if ($slug === '' || str_contains($slug, '/') || str_contains($slug, '..')) {
            if (function_exists('fc_abort_404')) {
                fc_abort_404('admin', 'Fence style not found.');
            }
            http_response_code(404);
            exit;
        }

        $this->bootProductsAdmin();
        $context->pageTitle      = 'Edit Fence Style';
        $context->route          = 'products/fence-styles/edit/' . rawurlencode($slug);
        $context->isProductsPage = true;
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
