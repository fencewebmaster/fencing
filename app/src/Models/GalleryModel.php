<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\PermissionService;

/**
 * Media library (public/assets/uploads) filesystem data access.
 */
final class GalleryModel
{
    private const UPLOAD_REL = 'public/assets/uploads';

    /** @return list<string> */
    public static function allowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    }

    /** @return list<string> */
    public static function allowedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    }

    public static function uploadDir(): string
    {
        return FC_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
    }

    public static function ensureUploadDir(): bool
    {
        $dir = self::uploadDir();
        if (is_dir($dir)) {
            return is_writable($dir);
        }

        return mkdir($dir, 0755, true);
    }

    public static function isAllowedFile(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($ext, self::allowedExtensions(), true);
    }

    /**
     * Rejects Windows reserved device names (CON, NUL, COM1, LPT1, ...), with or without an
     * extension — the classic Win32 namespace treats "con.jpg" as the CON device too.
     */
    public static function isReservedWindowsName(string $basename): bool
    {
        $stem = pathinfo($basename, PATHINFO_FILENAME);

        return (bool) preg_match('/^(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i', $stem);
    }

    public static function sanitizeFilename(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $name) ?? 'file';
        $name = trim($name, '.-_');

        return $name !== '' ? $name : 'file';
    }

    public static function uniqueFilename(string $dir, string $filename): string
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
    public static function fileMeta(string $name, string $full): array
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

    /**
     * Cheap file count (no stat/mime/getimagesize) — used only to enforce the upload
     * file-count cap without paying listItems()'s full per-file metadata cost.
     */
    public static function countUploadedFiles(): int
    {
        if (!is_dir(self::uploadDir())) {
            return 0;
        }

        $dir = self::uploadDir();
        $files = scandir($dir);
        if ($files === false) {
            return 0;
        }

        $count = 0;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (is_file($dir . DIRECTORY_SEPARATOR . $file) && self::isAllowedFile($file)) {
                $count++;
            }
        }

        return $count;
    }

    /** @return array{list:bool,upload:bool,delete:bool} */
    private static function caps(): array
    {
        $can = static function (string $key): bool {
            return PermissionService::can($key);
        };

        return [
            'list' => $can('media_library.view_list'),
            'upload' => $can('media_library.upload'),
            'delete' => $can('media_library.delete'),
        ];
    }

    /** @return array{ok:bool,items?:list<array<string,mixed>>,uploadRel?:string,canList?:bool,canUpload?:bool,canDelete?:bool,error?:string} */
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

        $caps = self::caps();

        return [
            'ok' => true,
            'items' => $items,
            'uploadRel' => self::UPLOAD_REL,
            'canList' => $caps['list'],
            'canUpload' => $caps['upload'],
            'canDelete' => $caps['delete'],
        ];
    }
}
