<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Core\FrontendApplication;
use Fc\Admin\Services\BrandingSettings;
use Fc\Admin\Services\CatalogSettings;
use Fc\Admin\Services\ProductLookupService;

/**
 * Data layer for the public product lookup page (/lookup).
 */
final class LookupPageModel
{
    /**
     * Build every value app/views/frontend/lookup/index.php renders from.
     *
     * @param array<string, mixed> $query Raw $_GET.
     * @return array{page:array<string,mixed>,catalog:array<string,mixed>,title:string,appBase:string,logoUrl:string}
     */
    public static function build(array $query): array
    {
        $page    = ProductLookupService::buildPage($query);
        $catalog = is_array($page['catalog'] ?? null) ? $page['catalog'] : CatalogSettings::get();

        $title = trim((string) ($catalog['sidebarTitle'] ?? 'Product Lookup'));
        if ($title === '') {
            $title = 'Product Lookup';
        }

        $appBase = self::appBase();

        return [
            'page'    => $page,
            'catalog' => $catalog,
            'title'   => $title,
            'appBase' => $appBase,
            'logoUrl' => BrandingSettings::logoUrl($appBase),
        ];
    }

    /**
     * Web path the app is mounted at, used to build asset URLs (no trailing slash).
     */
    public static function appBase(): string
    {
        return FrontendApplication::basePath();
    }
}
