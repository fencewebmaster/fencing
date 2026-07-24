<?php
/**
 * FC Admin — media gallery API (assets/uploads).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

final class GalleryController
{
    private const UPLOAD_REL = 'assets/uploads';

    /** @return list<string> */
    private static function allowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    }

    /** @return list<string> */
    private static function allowedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    }

    private static function uploadDir(): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
    }

    private static function ensureUploadDir(): bool
    {
        $dir = self::uploadDir();
        if (is_dir($dir)) {
            return is_writable($dir);
        }

        return mkdir($dir, 0755, true);
    }

    private static function isAllowedFile(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($ext, self::allowedExtensions(), true);
    }

    private static function sanitizeFilename(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $name) ?? 'file';
        $name = trim($name, '.-_');

        return $name !== '' ? $name : 'file';
    }

    private static function uniqueFilename(string $dir, string $filename): string
    {
        if (!is_file($dir . DIRECTORY_SEPARATOR . $filename)) {
            return $filename;
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $n = 1;

        while (true) {
            $candidate = $base . '-' . $n . ($ext !== '' ? '.' . $ext : '');
            if (!is_file($dir . DIRECTORY_SEPARATOR . $candidate)) {
                return $candidate;
            }
            $n += 1;
        }
    }

    /** @return array<string, mixed> */
    private static function fileMeta(string $name, string $full): array
    {
        $stat = stat($full);
        $mime = is_readable($full) ? (mime_content_type($full) ?: 'application/octet-stream') : 'application/octet-stream';
        $meta = [
            'name' => $name,
            'path' => self::UPLOAD_REL . '/' . $name,
            'size' => $stat ? (int) $stat['size'] : 0,
            'modified' => $stat ? (int) $stat['mtime'] : 0,
            'mime' => $mime,
        ];

        if (strpos($mime, 'image/') === 0) {
            if ($mime === 'image/svg+xml') {
                return $meta;
            }
            $info = @getimagesize($full);
            if (is_array($info)) {
                $meta['width'] = (int) $info[0];
                $meta['height'] = (int) $info[1];
            }
        }

        return $meta;
    }

    /** @return array{ok:bool,items?:list<array<string,mixed>>,uploadRel?:string,error?:string} */
    public static function listItems(): array
    {
        if (!self::ensureUploadDir()) {
            return ['ok' => false, 'error' => 'Uploads directory is not writable.', 'items' => []];
        }

        $dir = self::uploadDir();
        $files = scandir($dir);
        if ($files === false) {
            return ['ok' => false, 'error' => 'Unable to read uploads directory.', 'items' => []];
        }

        $items = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($full) || !self::isAllowedFile($file)) {
                continue;
            }
            $items[] = self::fileMeta($file, $full);
        }

        usort($items, static function (array $a, array $b): int {
            return ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0);
        });

        $can = static function (string $key): bool {
            return !function_exists('fc_auth_user_can') || \fc_auth_user_can($key);
        };

        return [
            'ok' => true,
            'items' => $items,
            'uploadRel' => self::UPLOAD_REL,
            'canList' => $can('media_library.view_list'),
            'canUpload' => $can('media_library.upload'),
            'canDelete' => $can('media_library.delete'),
        ];
    }

    /** @return array{ok:bool,item?:array<string,mixed>,message?:string,error?:string} */
    public static function upload(): array
    {
        if (!self::ensureUploadDir()) {
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

        $original = self::sanitizeFilename((string) ($file['name'] ?? 'file'));
        if (!self::isAllowedFile($original)) {
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

        if ($detected === '' || !in_array($detected, self::allowedMimeTypes(), true)) {
            return ['ok' => false, 'error' => 'Invalid image file.'];
        }

        $dir = self::uploadDir();
        $filename = self::uniqueFilename($dir, $original);
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'error' => 'Could not save uploaded file.'];
        }

        return [
            'ok' => true,
            'item' => self::fileMeta($filename, $dest),
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

        if (!preg_match('#^assets/uploads/[^/]+$#', $path)) {
            return ['ok' => false, 'error' => 'Invalid path.'];
        }

        $full = FC_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $uploadDir = realpath(self::uploadDir());
        $fileReal = realpath($full);

        if ($uploadDir === false || $fileReal === false || strpos($fileReal, $uploadDir) !== 0) {
            return ['ok' => false, 'error' => 'File not found.'];
        }

        if (!is_file($fileReal)) {
            return ['ok' => false, 'error' => 'File not found.'];
        }

        if (!@unlink($fileReal)) {
            return ['ok' => false, 'error' => 'Could not delete file.'];
        }

        return ['ok' => true, 'message' => 'File deleted.'];
    }

    public static function handleApiRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : '';

        switch ($action) {
            case 'list':
                echo json_encode(self::listItems(), JSON_UNESCAPED_UNICODE);
                break;

            case 'upload':
                if ($method !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
                    break;
                }
                echo json_encode(self::upload(), JSON_UNESCAPED_UNICODE);
                break;

            case 'delete':
                if ($method !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
                    break;
                }
                $raw = file_get_contents('php://input');
                $payload = is_string($raw) ? json_decode($raw, true) : null;
                $path = is_array($payload) ? (string) ($payload['path'] ?? '') : '';
                echo json_encode(self::delete($path), JSON_UNESCAPED_UNICODE);
                break;

            default:
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Unknown action.'], JSON_UNESCAPED_UNICODE);
        }
    }
    public static function dispatch(): void
    {
        self::handleApiRequest();
    }
}
