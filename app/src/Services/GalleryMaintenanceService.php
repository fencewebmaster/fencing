<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\FileHelper;
use Fc\Admin\Helpers\FormatHelper;
use Fc\Admin\Models\GalleryModel;

/**
 * Media library (public/assets/uploads) mutation operations. CSRF verification stays in the
 * controller/dispatch layer — these methods take $_FILES / already-parsed values directly.
 */
final class GalleryMaintenanceService
{
    // Public for Settings → Site Health, which warns when PHP's own upload limits sit below it.
    public const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10MB
    private const MAX_FILE_COUNT = 2000;

    /**
     * The largest file that can actually be uploaded: the Media Library's cap, or PHP's when that is
     * lower. The page and the media picker check it before uploading; the server's errors reuse it.
     *
     * @return array{bytes:int,label:string,message:string}
     */
    public static function uploadLimit(): array
    {
        $php = FileHelper::phpUploadCap();
        if ($php > 0 && $php < self::MAX_UPLOAD_BYTES) {
            return [
                'bytes' => $php,
                'label' => FormatHelper::bytes($php),
                'message' => 'This file is over the server\'s ' . FormatHelper::bytes($php) . ' upload limit. Use a smaller file, or ask your host to raise PHP\'s upload limit.',
            ];
        }

        return [
            'bytes' => self::MAX_UPLOAD_BYTES,
            'label' => FormatHelper::bytes(self::MAX_UPLOAD_BYTES),
            'message' => 'This file is over the Media Library\'s ' . FormatHelper::bytes(self::MAX_UPLOAD_BYTES) . ' limit. Use a smaller file.',
        ];
    }

    /**
     * Over post_max_size PHP throws the whole request away, fields and files alike, so it arrives
     * without its CSRF token either; the controller answers with the size error instead.
     */
    public static function requestTooLarge(): bool
    {
        $max = ini_parse_quantity((string) ini_get('post_max_size'));

        return $max > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $max;
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => self::uploadLimit()['message'],
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted before it finished. Try again.',
            UPLOAD_ERR_NO_FILE => 'No file uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder for uploads. Ask your host to check PHP\'s upload_tmp_dir.',
            UPLOAD_ERR_CANT_WRITE => 'The server couldn\'t save the upload; its disk may be full.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension on the server stopped the upload.',
            default => 'Upload failed (code ' . $code . ').',
        };
    }

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
        $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($code !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage($code)];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return ['ok' => false, 'error' => 'This file is empty.'];
        }
        if ($size > self::MAX_UPLOAD_BYTES) {
            return ['ok' => false, 'error' => self::uploadLimit()['message']];
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
