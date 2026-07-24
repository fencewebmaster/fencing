<?php
/**
 * FC Admin — gallery page data and view helpers.
 */

declare(strict_types=1);

use Fc\Admin\Controllers\Api\GalleryController;

/**
 * Media Library capability flags for UI (gallery page + select-image modal).
 *
 * @return array{list:bool,upload:bool,delete:bool}
 */
function fc_media_library_caps(): array
{
    $can = static function (string $key): bool {
        return !function_exists('fc_auth_user_can') || fc_auth_user_can($key);
    };

    return [
        'list'   => $can('media_library.view_list'),
        'upload' => $can('media_library.upload'),
        'delete' => $can('media_library.delete'),
    ];
}

/**
 * @return array<string, mixed>
 */
function fc_gallery_admin_view_data(string $adminBase, string $appBase, string $initialTab): array
{
    $activeTab = fc_gallery_admin_normalize_tab($initialTab);
    $listResult = GalleryController::listItems();
    $items = is_array($listResult['items'] ?? null) ? $listResult['items'] : [];
    $error = !empty($listResult['ok']) ? '' : (string) ($listResult['error'] ?? 'Could not load gallery.');

    $itemRows = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $itemRows[] = fc_gallery_admin_prepare_item_row($item, $appBase);
    }

    $caps = fc_media_library_caps();
    $canUpload = $caps['upload'];
    $canDelete = $caps['delete'];

    $tabs = [
        ['id' => 'library', 'label' => 'Media Library', 'is_active' => $activeTab === 'library'],
    ];
    if ($canUpload) {
        $tabs[] = ['id' => 'upload', 'label' => 'Add New', 'is_active' => $activeTab === 'upload'];
    } elseif ($activeTab === 'upload') {
        $activeTab = 'library';
    }

    $bootstrap = [
        'activeTab' => $activeTab,
        'items'     => $items,
        'appBase'   => $appBase,
        'uploadRel' => (string) ($listResult['uploadRel'] ?? 'assets/uploads'),
        'canUpload' => $canUpload,
        'canDelete' => $canDelete,
    ];

    $pageClasses = ['fc-gallery-page', 'fc-gallery-page--grid'];
    if ($activeTab === 'upload' && $canUpload) {
        $pageClasses[] = 'fc-gallery-page--upload';
    }

    return [
        'admin_base'     => $adminBase,
        'app_base'       => $appBase,
        'initial_tab'    => $activeTab,
        'active_tab'     => $activeTab,
        'tabs'           => $tabs,
        'items'          => $items,
        'item_rows'      => $itemRows,
        'item_count'     => count($itemRows),
        'has_items'      => $itemRows !== [],
        'error'          => $error,
        'accept_types'   => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
        'bootstrap_json' => json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        'page_class'     => implode(' ', $pageClasses),
        'is_library_tab' => $activeTab === 'library',
        'is_upload_tab'  => $activeTab === 'upload' && $canUpload,
        'can_upload'     => $canUpload,
        'can_delete'     => $canDelete,
        'count_label'    => count($itemRows) . ' item' . (count($itemRows) === 1 ? '' : 's'),
    ];
}

function fc_gallery_admin_normalize_tab(string $tab): string
{
    $tab = strtolower(trim($tab));

    return $tab === 'upload' ? 'upload' : 'library';
}

/**
 * @param array<string, mixed> $item
 * @return array<string, mixed>
 */
function fc_gallery_admin_prepare_item_row(array $item, string $appBase): array
{
    $path = (string) ($item['path'] ?? '');
    $name = (string) ($item['name'] ?? '');
    $width = (int) ($item['width'] ?? 0);
    $height = (int) ($item['height'] ?? 0);

    return [
        'path'            => $path,
        'name'            => $name,
        'asset_url'       => fc_gallery_admin_asset_url($appBase, $path),
        'type_badge'      => fc_gallery_admin_file_type_badge($item),
        'formatted_size'  => fc_gallery_admin_format_bytes((int) ($item['size'] ?? 0)),
        'formatted_date'  => fc_gallery_admin_format_date((int) ($item['modified'] ?? 0)),
        'dimensions'      => $width > 0 && $height > 0 ? $width . ' × ' . $height : '—',
        'dimensions_long' => $width > 0 && $height > 0 ? $width . ' × ' . $height . ' pixels' : '—',
        'search_haystack' => strtolower($name),
        'mime'            => (string) ($item['mime'] ?? ''),
        'size'            => (int) ($item['size'] ?? 0),
        'modified'        => (int) ($item['modified'] ?? 0),
        'width'           => $width,
        'height'          => $height,
    ];
}

function fc_gallery_admin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fc_gallery_admin_asset_url(string $appBase, string $path): string
{
    $base = rtrim(str_replace('\\', '/', $appBase), '/');
    $rel = ltrim(str_replace('\\', '/', $path), '/');

    return $base !== '' ? $base . '/' . $rel : $rel;
}

/**
 * @param array<string, mixed> $item
 */
function fc_gallery_admin_file_type_badge(array $item): string
{
    $mime = strtolower((string) ($item['mime'] ?? ''));
    if (str_contains($mime, 'jpeg') || $mime === 'image/jpg') {
        return 'JPG';
    }
    if (str_contains($mime, 'png')) {
        return 'PNG';
    }
    if (str_contains($mime, 'gif')) {
        return 'GIF';
    }
    if (str_contains($mime, 'webp')) {
        return 'WEBP';
    }
    if (str_contains($mime, 'svg')) {
        return 'SVG';
    }

    $name = (string) ($item['name'] ?? '');
    $ext = pathinfo($name, PATHINFO_EXTENSION);

    return $ext !== '' ? strtoupper($ext) : 'IMG';
}

function fc_gallery_admin_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return number_format($bytes / (1024 * 1024), 1) . ' MB';
}

function fc_gallery_admin_format_date(int $timestamp): string
{
    if ($timestamp <= 0) {
        return '—';
    }

    $format = function_exists('fc_system_date_format_php')
        ? fc_system_date_format_php()
        : 'M. j, Y h:i A';

    return date($format, $timestamp);
}
