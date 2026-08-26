<?php
/**
 * FC Admin — products API (writable/products.csv and writable/wc-products-{GO,JG}.csv).
 */

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Models\StoreProductModel;
use Fc\Admin\Models\SystemProductModel;
use Fc\Admin\Services\AdminSiteRegistry;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\StoreProductMaintenanceService;
use Fc\Admin\Services\WcProductSkuIndex;
use Fc\Admin\Services\WooCommerceProductExportService;

final class ProductsController extends BaseApiController
{
    public function handle(): void
    {
        $this->sendJsonHeaders();

        $method = $this->request->method();
        $action = (string) $this->request->query('action', '');

        if ($method === 'POST') {
            if ($action === 'import-products-csv') {
                $this->importProductsCsv();
                return;
            }
            if ($action === 'import-store-products-csv') {
                $this->importStoreProductsCsv();
                return;
            }

            $payload = $this->request->jsonBody();

            if (!is_array($payload)) {
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Invalid JSON body.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (in_array($action, [
                'download-products-start',
                'download-products-step',
                'download-products-cancel',
            ], true)) {
                if (
                    !self::csrfOk($payload)
                ) {
                    http_response_code(403);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Invalid security token. Refresh and try again.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $source = isset($payload['source']) ? (string) $payload['source'] : '';
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                $jobId = isset($payload['jobId']) ? (string) $payload['jobId'] : '';
                $result = match ($action) {
                    'download-products-start' => WooCommerceProductExportService::start($source),
                    'download-products-cancel' => WooCommerceProductExportService::cancel($source, $jobId),
                    default => WooCommerceProductExportService::step($source, $jobId),
                };
                if (empty($result['ok'])) {
                    http_response_code(500);
                } elseif (($result['job']['status'] ?? '') === 'running') {
                    http_response_code(202);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return;
            }

            if ($action === 'reorder-store-products' || $action === 'update-store-product') {
                if (
                    !self::csrfOk($payload)
                ) {
                    http_response_code(403);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Invalid security token. Refresh and try again.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
            }

            if ($action === 'reorder-store-products') {
                if (!isset($payload['order']) || !is_array($payload['order'])) {
                    http_response_code(400);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Invalid JSON. Expected { "order": [0, 2, 1, ...] }.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $result = StoreProductMaintenanceService::reorder($payload['order']);
                if (!$result['ok']) {
                    http_response_code(500);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($action === 'update-store-product') {
                if (!isset($payload['rowIndex']) || !isset($payload['fields']) || !is_array($payload['fields'])) {
                    http_response_code(400);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Invalid JSON. Expected { "rowIndex": 0, "fields": { ... } }.',
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $result = StoreProductMaintenanceService::update((int) $payload['rowIndex'], $payload['fields']);
                if (!$result['ok']) {
                    http_response_code(500);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                return;
            }

            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Unknown POST action.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'ok' => false,
                'error' => 'Method not allowed.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        switch ($action) {
            case 'store-products':
                echo json_encode(StoreProductModel::all(), JSON_UNESCAPED_UNICODE);
                break;
            case 'system-products':
                $source = (string) $this->request->query('source', '');
                $result = SystemProductModel::all($source);
                if (!$result['ok']) {
                    http_response_code($source === '' ? 400 : 404);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
            case 'download-products-status':
                $source = (string) $this->request->query('source', '');
                $result = WooCommerceProductExportService::status($source);
                if (empty($result['ok'])) {
                    http_response_code(400);
                }
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case 'download-products-csv':
                self::downloadProductsCsv(
                    (string) $this->request->query('source', '')
                );
                break;
            case 'download-store-products-csv':
                self::downloadStoreProductsCsv();
                break;
            case 'wc-sku-index':
                echo json_encode(
                    WcProductSkuIndex::indexPayload(),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                break;
            default:
                http_response_code(400);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Unknown action.',
                ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Stream a CSV file as an attachment — shared by the two (inverted) download handlers.
     * $label names the file in the 404/500 JSON errors; $downloadFilename is the browser save-as
     * name (for the store download that is deliberately '{site}-system-products.csv' — the inversion).
     */
    private static function streamCsvAttachment(string $path, string $downloadFilename, string $label): void
    {
        if (!is_readable($path) || !is_file($path)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => $label . ' not found or not readable.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $size = @filesize($path);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        if (is_int($size) && $size >= 0) {
            header('Content-Length: ' . $size);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to open ' . $label . '.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        fpassthru($handle);
        fclose($handle);
        exit;
    }

    private static function downloadStoreProductsCsv(): void
    {
        $downloadFilename = AdminSiteRegistry::currentSiteFilenameSlug() . '-system-products.csv';
        self::streamCsvAttachment(StoreProductModel::csvPath(), $downloadFilename, 'products.csv');
    }

    private function importStoreProductsCsv(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $csrf = (string) $this->request->post('csrf', '');
        if (!AuthService::verifyCsrf($csrf)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid security token. Refresh and try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Choose a CSV file to import.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $file = $_FILES['file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Upload failed. Please try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'upload.csv');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Uploaded file is not readable.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($size <= 0 || $size > 50 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must be between 1 byte and 50MB.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Only .csv files can be imported.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $handle = fopen($tmp, 'rb');
        if ($handle === false) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to read the uploaded CSV.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $header = fgetcsv($handle);
        $rowCount = 0;
        $hasSlug = false;
        if (is_array($header)) {
            while (($row = fgetcsv($handle)) !== false) {
                if (!is_array($row) || $row === [null]) {
                    continue;
                }
                $slug = trim((string) ($row[0] ?? ''));
                if ($slug === '') {
                    continue;
                }
                $rowCount++;
                $hasSlug = true;
            }
        }
        fclose($handle);

        $normalizedHeader = is_array($header)
            ? array_map(static fn($col): string => trim((string) $col), $header)
            : [];
        if ($normalizedHeader !== [] && isset($normalizedHeader[0])) {
            $normalizedHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', $normalizedHeader[0]) ?? $normalizedHeader[0];
        }

        $required = ['SLUG', 'PRODUCT', 'DESCRIPTION', 'SUPPLIER', 'STYLE'];
        $missing = [];
        foreach ($required as $column) {
            if (!in_array($column, $normalizedHeader, true)) {
                $missing[] = $column;
            }
        }
        if ($missing !== []) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV header must include: ' . implode(', ', $required) . '.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($rowCount < 1 || !$hasSlug) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must contain at least one product row with a SLUG.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filename = 'products.csv';
        $dataDir = FC_ROOT . DIRECTORY_SEPARATOR . 'writable';
        if (!is_dir($dataDir) && !@mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to access the data directory.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $final = StoreProductModel::csvPath();
        $tmpDest = $dataDir . DIRECTORY_SEPARATOR . '.products-import-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.csv';
        if (!@move_uploaded_file($tmp, $tmpDest)) {
            if (!@copy($tmp, $tmpDest)) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Unable to store the uploaded CSV.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            @unlink($tmp);
        }

        $backup = $final . '.backup';
        @unlink($backup);
        $hadFinal = is_file($final);
        if ($hadFinal && !@rename($final, $backup)) {
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to prepare the existing products.csv for replacement.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!@rename($tmpDest, $final)) {
            if ($hadFinal && is_file($backup)) {
                @rename($backup, $final);
            }
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to replace products.csv.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        @unlink($backup);

        StoreProductMaintenanceService::invalidateCache();

        echo json_encode([
            'ok' => true,
            'message' => 'Imported ' . number_format($rowCount) . ' products into ' . $filename . '.',
            'file' => $filename,
            'total' => $rowCount,
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function downloadProductsCsv(string $source): void
    {
        $source = strtoupper(trim($source));
        if (!in_array($source, ['GO', 'JG'], true)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid source. Use GO or JG.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filename = 'wc-products-' . $source . '.csv';
        self::streamCsvAttachment(SystemProductModel::csvPath($source), $filename, $filename);
    }

    private function importProductsCsv(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $csrf = (string) $this->request->post('csrf', '');
        if (!AuthService::verifyCsrf($csrf)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid security token. Refresh and try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $source = strtoupper(trim((string) $this->request->post('source', '')));
        if (!in_array($source, ['GO', 'JG'], true)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Invalid source. Use GO or JG.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Choose a CSV file to import.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $file = $_FILES['file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Upload failed. Please try again.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'upload.csv');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Uploaded file is not readable.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($size <= 0 || $size > 50 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must be between 1 byte and 50MB.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Only .csv files can be imported.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $handle = fopen($tmp, 'rb');
        if ($handle === false) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to read the uploaded CSV.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $header = fgetcsv($handle);
        $rowCount = 0;
        $hasValidId = false;
        if (is_array($header)) {
            while (($row = fgetcsv($handle)) !== false) {
                if (!is_array($row) || $row === [null]) {
                    continue;
                }
                $first = trim((string) ($row[0] ?? ''));
                if ($first === '') {
                    continue;
                }
                $rowCount++;
                if (ctype_digit($first)) {
                    $hasValidId = true;
                }
            }
        }
        fclose($handle);

        $normalizedHeader = is_array($header)
            ? array_map(static fn($col): string => trim((string) $col), $header)
            : [];
        // Strip UTF-8 BOM from first column when present.
        if ($normalizedHeader !== [] && isset($normalizedHeader[0])) {
            $normalizedHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', $normalizedHeader[0]) ?? $normalizedHeader[0];
        }

        $required = ['ID', 'SKU', 'Name', 'Images'];
        $missing = [];
        foreach ($required as $column) {
            if (!in_array($column, $normalizedHeader, true)) {
                $missing[] = $column;
            }
        }
        if ($missing !== []) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV header must include: ' . implode(', ', $required) . '.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($rowCount < 1 || !$hasValidId) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'CSV must contain at least one product row with a numeric ID.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filename = 'wc-products-' . $source . '.csv';
        $dataDir = FC_ROOT . DIRECTORY_SEPARATOR . 'writable';
        if (!is_dir($dataDir) && !@mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to access the data directory.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $final = SystemProductModel::csvPath($source);
        $tmpDest = $dataDir . DIRECTORY_SEPARATOR . '.wc-products-' . $source . '-import-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.csv';
        if (!@move_uploaded_file($tmp, $tmpDest)) {
            // Fallback for environments where move_uploaded_file is restricted after validation reads.
            if (!@copy($tmp, $tmpDest)) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Unable to store the uploaded CSV.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            @unlink($tmp);
        }

        $backup = $final . '.backup';
        @unlink($backup);
        $hadFinal = is_file($final);
        if ($hadFinal && !@rename($final, $backup)) {
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to prepare the existing products CSV for replacement.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!@rename($tmpDest, $final)) {
            if ($hadFinal && is_file($backup)) {
                @rename($backup, $final);
            }
            @unlink($tmpDest);
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Unable to replace the products CSV file.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        @unlink($backup);

        SystemProductModel::invalidateCache($source);
        WcProductSkuIndex::invalidate($source);

        echo json_encode([
            'ok' => true,
            'message' => 'Imported ' . number_format($rowCount) . ' products into ' . $filename . '.',
            'source' => $source,
            'file' => $filename,
            'total' => $rowCount,
        ], JSON_UNESCAPED_UNICODE);
    }
}
