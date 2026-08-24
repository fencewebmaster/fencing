<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Helpers\StringHelper;
use Fc\Admin\Models\LookupPageModel;

/**
 * Public, server-rendered WooCommerce product search (/lookup).
 *
 * Deliberately session-less — this page is cacheable and must not set a session cookie.
 */
final class LookupController extends BaseFrontendController
{
    public function index(): void
    {
        $data = LookupPageModel::build($this->request->allQuery());

        $appBase = $data['appBase'];

        $this->view('lookup/index.php', [
            'page'             => $data['page'],
            'catalog'          => $data['catalog'],
            'fcLookupPageTitle' => $data['title'],
            'fcLookupAppBase'  => $appBase,
            'fcLookupLogoUrl'  => $data['logoUrl'],
            'h'                => static fn (string $value): string => StringHelper::escapeHtml(StringHelper::decodeHtmlEntities($value)),
            'asset'            => static function (string $rel) use ($appBase): string {
                $rel  = ltrim($rel, '/');
                $path = FC_ROOT . '/' . $rel;
                $url  = $appBase !== '' ? $appBase . '/' . $rel : '/' . $rel;

                if (is_file($path)) {
                    return $url . '?v=' . filemtime($path);
                }

                return $url;
            },
        ]);
    }
}
