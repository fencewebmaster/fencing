<?php

declare(strict_types=1);

namespace Fc\Admin\Presenters;

use Fc\Admin\Models\GalleryModel;
use Fc\Admin\Services\AuthService;

/**
 * Media library row shaping — pure, filesystem-I/O-free formatting/view-model helpers, plus
 * the page-level viewData() orchestrator, which calls GalleryModel to fetch data before
 * shaping it (mirrors StoreProductPresenter::viewData() calling StoreProductModel::query()).
 */
final class GalleryPresenter
{
    public static function normalizeTab(string $tab): string
    {
        $tab = strtolower(trim($tab));

        return $tab === 'upload' ? 'upload' : 'library';
    }

    public static function assetUrl(string $appBase, string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $appBase), '/');
        $rel = ltrim(str_replace('\\', '/', $path), '/');

        return $base !== '' ? $base . '/' . $rel : $rel;
    }

    /** @param array<string, mixed> $item */
    public static function fileTypeBadge(array $item): string
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

    /**
     * @param array<string, mixed> $item
     * @return array{path:string,name:string,asset_url:string,type_badge:string}
     */
    private static function itemRow(array $item, string $appBase): array
    {
        $path = (string) ($item['path'] ?? '');

        return [
            'path' => $path,
            'name' => (string) ($item['name'] ?? ''),
            'asset_url' => self::assetUrl($appBase, $path),
            'type_badge' => self::fileTypeBadge($item),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function viewData(string $adminBase, string $appBase, string $initialTab): array
    {
        $activeTab = self::normalizeTab($initialTab);
        $listResult = GalleryModel::listItems();
        $items = is_array($listResult['items'] ?? null) ? $listResult['items'] : [];
        $error = !empty($listResult['ok']) ? '' : (string) ($listResult['error'] ?? 'Could not load gallery.');

        $itemRows = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $itemRows[] = self::itemRow($item, $appBase);
        }

        $canUpload = (bool) ($listResult['canUpload'] ?? true);
        $canDelete = (bool) ($listResult['canDelete'] ?? true);

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
            'items' => $items,
            'appBase' => $appBase,
            'uploadRel' => (string) ($listResult['uploadRel'] ?? 'public/assets/uploads'),
            'canUpload' => $canUpload,
            'canDelete' => $canDelete,
            'csrf' => AuthService::csrfToken(),
        ];

        $pageClasses = ['fc-gallery-page', 'fc-gallery-page--grid'];
        if ($activeTab === 'upload' && $canUpload) {
            $pageClasses[] = 'fc-gallery-page--upload';
        }

        return [
            'initial_tab' => $activeTab,
            'tabs' => $tabs,
            'item_rows' => $itemRows,
            'has_items' => $itemRows !== [],
            'error' => $error,
            'accept_types' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
            'bootstrap_json' => json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            'page_class' => implode(' ', $pageClasses),
            'is_library_tab' => $activeTab === 'library',
            'can_delete' => $canDelete,
            'count_label' => count($itemRows) . ' item' . (count($itemRows) === 1 ? '' : 's'),
        ];
    }
}
