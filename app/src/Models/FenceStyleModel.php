<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\FenceFileService;

/**
 * Fence style definitions (writable/fences/*.php) data access.
 */
final class FenceStyleModel
{
    /**
     * @return array{fences: array<string, array<string, mixed>>, fileSlugMap: array<string, string>}
     */
    public static function catalog(): array
    {
        $fencesDir = FC_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'fences';
        $files = glob($fencesDir . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_NATURAL);

        $fences = [];
        $fileSlugMap = [];

        foreach ($files as $fenceFile) {
            if (!is_readable($fenceFile)) {
                continue;
            }
            $beforeKeys = array_keys($fences);
            include $fenceFile;
            $newKeys = array_diff(array_keys($fences), $beforeKeys);
            foreach ($newKeys as $key) {
                $fileSlugMap[$key] = $fenceFile;
            }
        }

        return [
            'fences' => $fences,
            'fileSlugMap' => $fileSlugMap,
        ];
    }

    public static function filePath(string $slug): string
    {
        $catalog = self::catalog();

        return $catalog['fileSlugMap'][$slug] ?? '';
    }

    /**
     * @return array{fileType: string, parentSlug: string}
     */
    public static function fileMeta(string $filePath, string $slug): array
    {
        $meta = [
            'fileType' => 'unknown',
            'parentSlug' => '',
        ];

        if (!is_readable($filePath)) {
            return $meta;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return $meta;
        }

        $meta['fileType'] = FenceFileService::detectFileType($content, $slug);
        $parentSlug = FenceFileService::detectParentSlug($content, $slug);
        if ($parentSlug !== null) {
            $meta['parentSlug'] = $parentSlug;
        }

        return $meta;
    }
}
