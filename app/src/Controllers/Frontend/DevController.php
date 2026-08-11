<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Helpers\FileHelper;
use Fc\Admin\Helpers\UrlHelper;

/**
 * Developer utilities (/dev) — deploy pull and CSS minification.
 */
final class DevController extends BaseFrontendController
{
    public function index(): void
    {
        $redirect_to = UrlHelper::baseUrl('planner');

        $action = (string) ($_GET['action'] ?? '');

        if ($action === 'git-pull') {
            echo exec('git pull');
        }

        if ($action === 'minify-css') {
            foreach (glob(FC_ROOT . '/public/assets/css/*[!{.min}].css') ?: [] as $file) {
                FileHelper::minifyCss((string) realpath($file));
            }
        }

        header('Location: ' . $redirect_to);
    }
}
