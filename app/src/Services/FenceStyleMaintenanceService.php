<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Fence style definitions (writable/fences/*.php) mutation operations. CSRF verification stays
 * in the controller/dispatch layer — this method takes clean, already-validated values.
 */
final class FenceStyleMaintenanceService
{
    /**
     * @param array<string, mixed> $config
     * @return array{ok:bool,message?:string,fileType?:string,error?:string}
     */
    public static function save(string $filePath, string $slug, array $config): array
    {
        $result = FenceFileService::writeConfig($filePath, $slug, $config);
        if (empty($result['ok'])) {
            return [
                'ok' => false,
                'error' => $result['error'] ?? 'Could not save fence configuration.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'Fence style saved.',
            'fileType' => $result['fileType'] ?? '',
        ];
    }
}
