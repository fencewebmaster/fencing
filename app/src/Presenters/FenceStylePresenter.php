<?php

declare(strict_types=1);

namespace Fc\Admin\Presenters;

use Fc\Admin\Helpers\ViewHelper;
use Fc\Admin\Models\FenceStyleModel;
use Fc\Admin\Services\AuthService;
use Fc\Admin\Services\PermissionService;

/**
 * Fence style row shaping — pure, DB-free formatting/view-model helpers, plus the
 * page-level viewData() orchestrator. No dependency on FenceStyleModel (kept
 * one-directional: Model depends on this class, not the other way around) —
 * mirrors StoreProductModel / StoreProductPresenter.
 */
final class FenceStylePresenter
{
    /**
     * @param array<string, mixed> $info
     * @return array<string, mixed>
     */
    public static function stylePayload(string $slug, array $info, string $sourceFile, string $appBase): array
    {
        $imagePath = isset($info['image']) ? ltrim((string) $info['image'], '/') : '';
        $sortOrder = 0;
        if ($sourceFile !== '' && preg_match('/^(\d+)-/', $sourceFile, $m)) {
            $sortOrder = (int) $m[1];
        }

        return [
            'slug' => (string) ($info['slug'] ?? $slug),
            'title' => (string) ($info['title'] ?? $slug),
            'name' => (string) ($info['name'] ?? ''),
            'image' => $imagePath,
            'imageUrl' => $imagePath !== '' ? $appBase . '/' . $imagePath : '',
            'live' => !empty($info['live']),
            'file' => $sourceFile,
            'sortOrder' => $sortOrder,
            'panel_group' => isset($info['panel_group']) ? (string) $info['panel_group'] : '',
            'panel_count' => isset($info['panel_count']) ? (string) $info['panel_count'] : '',
            'color' => isset($info['color']) && is_array($info['color']) ? array_values($info['color']) : [],
        ];
    }

    /** @return array{ok:bool,styles:list<array<string,mixed>>,total:int,canEdit:bool,canView:bool} */
    public static function listPayload(string $appBase): array
    {
        $catalog = FenceStyleModel::catalog();
        $styles = [];

        foreach ($catalog['fences'] as $slug => $info) {
            if (!is_array($info)) {
                continue;
            }
            $sourceFile = isset($catalog['fileSlugMap'][$slug]) ? basename($catalog['fileSlugMap'][$slug]) : '';
            $styles[] = self::stylePayload($slug, $info, $sourceFile, $appBase);
        }

        usort($styles, static function (array $a, array $b): int {
            if ($a['sortOrder'] !== $b['sortOrder']) {
                return $a['sortOrder'] <=> $b['sortOrder'];
            }

            return strcasecmp($a['title'], $b['title']);
        });

        return [
            'ok' => true,
            'styles' => $styles,
            'total' => count($styles),
            'canEdit' => PermissionService::can('products.fence_styles.edit'),
            'canView' => PermissionService::can('products.fence_styles.view')
                || PermissionService::can('products.fence_styles.edit'),
            'csrf' => AuthService::csrfToken(),
        ];
    }

    /**
     * @return array{ok:bool,style?:array<string,mixed>,config?:array<string,mixed>,fileMeta?:array<string,mixed>,canEdit?:bool,canView?:bool,csrf?:string,error?:string}
     */
    public static function getStylePayload(string $slug, string $appBase): array
    {
        $catalog = FenceStyleModel::catalog();
        if (!isset($catalog['fences'][$slug]) || !is_array($catalog['fences'][$slug])) {
            return ['ok' => false, 'error' => 'Fence style not found.'];
        }

        $filePath = $catalog['fileSlugMap'][$slug] ?? '';
        $sourceFile = $filePath !== '' ? basename($filePath) : '';
        $style = self::stylePayload($slug, $catalog['fences'][$slug], $sourceFile, $appBase);
        $fileMeta = $filePath !== '' ? FenceStyleModel::fileMeta($filePath, $slug) : ['fileType' => 'unknown', 'parentSlug' => ''];

        return [
            'ok' => true,
            'style' => $style,
            'config' => $catalog['fences'][$slug],
            'fileMeta' => $fileMeta,
            'canEdit' => PermissionService::can('products.fence_styles.edit'),
            'canView' => PermissionService::can('products.fence_styles.view')
                || PermissionService::can('products.fence_styles.edit'),
            'csrf' => AuthService::csrfToken(),
        ];
    }

    /**
     * @param array<string, string> $query
     * @return array<string, mixed>
     */
    public static function listViewData(string $adminBase, string $appBase, array $query = []): array
    {
        $payload = self::listPayload($appBase);
        $styles = is_array($payload['styles'] ?? null) ? $payload['styles'] : [];
        $error = !empty($payload['ok']) ? '' : (string) ($payload['error'] ?? 'Could not load fence styles.');

        $cards = [];
        $canEdit = PermissionService::can('products.fence_styles.edit');
        $canView = $canEdit || PermissionService::can('products.fence_styles.view');
        foreach ($styles as $style) {
            if (!is_array($style)) {
                continue;
            }
            $slug = (string) ($style['slug'] ?? '');
            $title = (string) ($style['title'] ?? $slug);
            $imageUrl = (string) ($style['imageUrl'] ?? '');
            $editRoute = 'products/fence-styles/edit/' . rawurlencode($slug);
            $cards[] = [
                'slug'       => $slug,
                'title'      => $title,
                'image_url'  => $imageUrl,
                'has_image'  => $imageUrl !== '',
                'is_live'    => !empty($style['live']),
                'badge_class'=> !empty($style['live']) ? 'fc-admin-fence-style-badge--live' : 'fc-admin-fence-style-badge--draft',
                'badge_label'=> !empty($style['live']) ? 'Live' : 'Draft',
                'edit_route' => $editRoute,
                'edit_href'  => ViewHelper::adminUrl($adminBase, $editRoute),
                'can_view'   => $canView,
                'can_edit'   => $canEdit,
            ];
        }

        return [
            'error'          => $error,
            'has_styles'     => $cards !== [],
            'cards'          => $cards,
            'can_view'       => $canView,
            'can_edit'       => $canEdit,
            'bootstrap_json' => ViewHelper::bootstrapJson([
                'styles'  => $styles,
                'total'   => count($cards),
                'canView' => $canView,
                'canEdit' => $canEdit,
                'csrf'    => AuthService::csrfToken(),
            ]),
        ];
    }
}
