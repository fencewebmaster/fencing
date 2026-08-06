<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Models\GalleryModel;

/**
 * Media library (public/assets/uploads) mutation operations. CSRF verification stays in the
 * controller/dispatch layer — these methods take $_FILES / already-parsed values directly.
 */
final class GalleryMaintenanceService
{
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10MB
    private const MAX_FILE_COUNT = 2000;

    /** @return array{ok:bool,item?:array<string,mixed>,message?:string,error?:string} */
    public static function upload(): array
    {
        if (!GalleryModel::ensureUploadDir()) {
            return ['ok' => false, 'error' => 'Could not create or write to uploads directory.'];
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            return ['ok' => false, 'error' => 'No file uploaded.'];
        }

        $file = $_FILES['file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed (code ' . (int) ($file['error'] ?? 0) . ').'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_UPLOAD_BYTES) {
            return ['ok' => false, 'error' => 'File must be between 1 byte and 10MB.'];
        }

        if (GalleryModel::countUploadedFiles() >= self::MAX_FILE_COUNT) {
            return ['ok' => false, 'error' => 'Media library is full. Delete some files before uploading more.'];
        }

        $original = GalleryModel::sanitizeFilename((string) ($file['name'] ?? 'file'));
        if (GalleryModel::isReservedWindowsName($original)) {
            return ['ok' => false, 'error' => 'File name is not allowed.'];
        }
        if (!GalleryModel::isAllowedFile($original)) {
            return ['ok' => false, 'error' => 'File type not allowed. Use JPG, PNG, GIF, WebP, or SVG.'];
        }

        $detected = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = (string) finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }

        if ($detected === '' || !in_array($detected, GalleryModel::allowedMimeTypes(), true)) {
            return ['ok' => false, 'error' => 'Invalid image file.'];
        }

        $dir = GalleryModel::uploadDir();
        $filename = GalleryModel::uniqueFilename($dir, $original);
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;

        // Atomically claim the filename slot (O_CREAT|O_EXCL) to close the TOCTOU race between
        // uniqueFilename()'s is_file() probe and the actual write below.
        $handle = @fopen($dest, 'x');
        if ($handle === false) {
            return ['ok' => false, 'error' => 'Could not save uploaded file.'];
        }
        fclose($handle);

        if (!move_uploaded_file($tmp, $dest)) {
            @unlink($dest);

            return ['ok' => false, 'error' => 'Could not save uploaded file.'];
        }

        return [
            'ok' => true,
            'item' => GalleryModel::fileMeta($filename, $dest),
            'message' => 'File uploaded.',
        ];
    }

    /** @return array{ok:bool,message?:string,error?:string} */
    public static function delete(string $path): array
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || strpos($path, '..') !== false) {
            return ['ok' => false, 'error' => 'Invalid path.'];
        }

        if (!preg_match('#^public/assets/uploads/[^/]+$#', $path)) {
            return ['ok' => false, 'error' => 'Invalid path.'];
        }

        $full = FC_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $uploadDir = realpath(GalleryModel::uploadDir());
        $fileReal = realpath($full);

        if ($uploadDir === false || $fileReal === false || strpos($fileReal, $uploadDir) !== 0) {
            return ['ok' => false, 'error' => 'File not found.'];
        }

        if (!is_file($fileReal) || !GalleryModel::isAllowedFile($fileReal)) {
            return ['ok' => false, 'error' => 'File not found.'];
        }

        if (!@unlink($fileReal)) {
            return ['ok' => false, 'error' => 'Could not delete file.'];
        }

        return ['ok' => true, 'message' => 'File deleted.'];
    }
}
