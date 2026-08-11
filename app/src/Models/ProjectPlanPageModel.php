<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\CartBuilderService;
use Fc\Admin\Services\Database;
use Fc\Admin\Services\FenceCatalogService;
use Fc\Admin\Services\FenceSettingsService;
use Fc\Admin\Services\PlannerRecordService;
use Fc\Admin\Services\PlannerSessionService;
use Fc\Admin\Services\SiteRegistryService;
use Fc\Admin\Services\WcProductCsvService;

/**
 * Data layer for the public project plan page (/project-plan).
 */
final class ProjectPlanPageModel
{
    /**
     * Restore a saved quote into the session when the page is opened cold with ?qid=.
     *
     * @return array<string, mixed> The (possibly refreshed) fc_data session array.
     */
    public static function restoreFromQuote(string $qid): array
    {
        $qid = trim($qid);

        $db  = new Database();
        $row = PlannerRecordService::isValidPlannerId($qid)
            ? $db->select_where('planners', '`planner_id`="' . $qid . '"')
            : null;

        if ($row && is_object($row) && !PlannerRecordService::rowIsTrashed($row)) {
            $_SESSION['planner_id'] = $qid;

            $site = SiteRegistryService::all($_SERVER['HTTP_HOST'] ?? '', 'domain', true);
            if ($site) {
                $_SESSION['site'] = $site;
            }

            PlannerSessionService::hydrateFromRow($row);
            PlannerRecordService::markReloaded($qid);
        }

        return isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
    }

    /**
     * Re-post the planner BOM to the store so cart SKUs/prices match the saved colours.
     *
     * @param array<string, mixed> $info Session fc_data.
     * @param array<string, mixed> $cart Session fc_cart.
     * @return array<string, mixed> The refreshed cart.
     */
    public static function refreshCartFromPlan(array $info, array $cart): array
    {
        if (empty($info['cart_items'])) {
            return $cart;
        }

        $cart_items_grouped = json_decode($info['cart_items'], true);
        if (!is_array($cart_items_grouped) || !count($cart_items_grouped)) {
            return $cart;
        }

        $colors               = PlannerSessionService::colorRowsFromSession();
        $cart_items_regrouped = FenceCatalogService::regroupPlannerCartItemsForSkus($cart_items_grouped, $colors);
        $cart_items_data      = FenceCatalogService::formatRegroupedCartItemsForProductSkus(
            $cart_items_regrouped,
            isset($info['fences']) ? $info['fences'] : '[]'
        );

        if (!empty($cart_items_data)) {
            CartBuilderService::postProductSkus($cart_items_data);
            $cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : $cart;
        }

        return $cart;
    }

    /**
     * Backfill missing product images on the session cart.
     *
     * @param array<string, mixed> $cart
     */
    public static function ensureCartImages(array $cart): void
    {
        if (empty($cart['items']) || !is_array($cart['items'])) {
            return;
        }

        WcProductCsvService::ensureItemsHaveImages($cart['items']);
        $_SESSION['fc_cart']['items'] = $cart['items'];
    }

    /**
     * Site registry row for the session's selected site.
     *
     * @return array<string, mixed>|null
     */
    public static function sessionSiteInfo(): ?array
    {
        $site = SiteRegistryService::all($_SESSION['site']['id'] ?? null, 'id', true);

        return is_array($site) ? $site : null;
    }

    /**
     * Same shape as the planner's `fc_fence_info` — p2.js hydrates localStorage from it so
     * Project Plans render without the planner tab ever being opened.
     *
     * @param array<string, mixed> $info Session fc_data.
     */
    public static function fenceInfo(array $info): object
    {
        $fc_fence_info = PlannerSessionService::rowToJsFenceInfo(
            (object) [
                'fence_data'         => isset($info['fences']) ? $info['fences'] : '',
                'cart_items_data'    => isset($info['cart_items']) ? $info['cart_items'] : '[]',
                'project_plans_data' => isset($info['project_plans']) ? $info['project_plans'] : '',
                'section_count'      => 0,
            ]
        );

        if (!empty($info['fences'])) {
            $decoded_fences = is_array($info['fences']) ? $info['fences'] : json_decode($info['fences'], true);
            if (is_array($decoded_fences) && $fc_fence_info->section_count < 1) {
                $fc_fence_info->section_count = count($decoded_fences);
            }
        }

        return $fc_fence_info;
    }

    /**
     * Fence catalog from writable/settings.php.
     *
     * @return array<string, mixed>
     */
    public static function fences(): array
    {
        return FenceSettingsService::fences();
    }
}
