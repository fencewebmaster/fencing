<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Services\CaptureImageService;

/**
 * Same-origin product images for the project plan's PNG/PDF capture (/capture-image?src=...).
 *
 * Session-less: modern-screenshot fetches one per item-list thumbnail. See CaptureImageService.
 */
final class CaptureImageController extends BaseFrontendController
{
    public function index(): void
    {
        $result = CaptureImageService::resolve((string) $this->request->query('src', ''));

        if ($result['status'] !== 200 || !isset($result['path'], $result['type'])) {
            http_response_code($result['status']);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
            echo 'Image not available.';
            return;
        }

        header('Content-Type: ' . $result['type']);
        header('Content-Length: ' . (string) filesize($result['path']));
        header('Cache-Control: public, max-age=86400');
        readfile($result['path']);
    }
}
