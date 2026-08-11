<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\CartBuilderService;
use Fc\Admin\Services\Database;
use Fc\Admin\Services\FenceCatalogService;
use Fc\Admin\Services\PlannerRecordService;
use Fc\Admin\Services\PlannerSessionService;

/**
 * Writes the public planner session into the `planners` table.
 *
 * /submit, checkout's `save_planner` and checkout's `push_order` all built the
 * same row by hand with small divergences; the two that actually matter are kept
 * as explicit options rather than baked in:
 *
 *  - `location`  postcode + state columns (omitted by the legacy save_planner path)
 *  - `status`    status + status_updated_at (dropped on a quote-reload autosave, where
 *                the planner route has already written status='reloaded' this same request)
 */
final class PlannerSubmissionModel
{
    /**
     * Re-post the session BOM to the store so cart SKUs/prices match the saved colours.
     */
    public static function syncCartFromSession(): void
    {
        $colors               = PlannerSessionService::colorRowsFromSession();
        $cart_items_grouped   = json_decode($_SESSION['fc_data']['cart_items'] ?? '', true);
        $cart_items_regrouped = FenceCatalogService::regroupPlannerCartItemsForSkus($cart_items_grouped, $colors);
        $cart_items_data      = FenceCatalogService::formatRegroupedCartItemsForProductSkus(
            $cart_items_regrouped,
            $_SESSION['fc_data']['fences'] ?? '[]'
        );

        CartBuilderService::postProductSkus($cart_items_data);
    }

    /**
     * Build the planners-table payload from the current session.
     *
     * @param array<string, mixed> $fences  Fence catalog (writable/settings.php).
     * @param array{location?:bool,status?:bool,site_url?:string} $options
     *        `site_url` overrides the session site URL — the store push resolves the live
     *        WooCommerce URL first (SiteRegistryService::wpSiteUrl()) and records that one.
     * @return array<string, mixed>
     */
    public static function payload(array $fences, string $plannerId, array $options = []): array
    {
        $withLocation = $options['location'] ?? true;
        $withStatus   = $options['status'] ?? true;

        $fc_data     = isset($_SESSION['fc_data']) && is_array($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
        $fc_products = $_SESSION['custom_fence_products'] ?? null;
        $fc_cart     = isset($_SESSION['fc_cart']) && is_array($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];
        $fc_site     = isset($_SESSION['site']) && is_array($_SESSION['site']) ? $_SESSION['site'] : [];

        $payload = [
            'planner_id'         => $plannerId,
            'site_id'            => $fc_site['id'] ?? null,
            'site_url'           => $fc_site['url'] ?? null,
            'order_id'           => 0,
            'status'             => 'planning',
            'status_updated_at'  => date('Y-m-d H:i:s'),
            'section_count'      => self::sectionCount($fc_data),
            'notes'              => $fc_data['notes'] ?? null,
            'name'               => $fc_data['name'] ?? null,
            'mobile'             => CartBuilderService::normalizeMobileForStorage($fc_data['mobile'] ?? null),
            'email'              => $fc_data['email'] ?? null,
            'address'            => $fc_data['address'] ?? null,
            'postcode'           => $fc_data['postcode'] ?? null,
            'state'              => $fc_data['state'] ?? null,
            'fence_type'         => PlannerSessionService::selectedFences($fences, 'slug'),
            'timeframe'          => $fc_data['timeframe'] ?? null,
            'extra'              => PlannerRecordService::extraForDb(
                $fc_data['extra'] ?? null,
                isset($fc_data['nothing_extra']) ? (string) $fc_data['nothing_extra'] : null
            ),
            'color_data'         => $fc_data['color'] ?? null,
            'products_data'      => $fc_products,
            'fence_data'         => $fc_data['fences'] ?? null,
            'cart_data'          => $fc_cart['items'] ?? null,
            'cart_items_data'    => $fc_data['cart_items'] ?? null,
            'project_plans_data' => $fc_data['project_plans'] ?? null,
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        if (!$withLocation) {
            unset($payload['postcode'], $payload['state']);
        }

        if (!$withStatus) {
            unset($payload['status'], $payload['status_updated_at']);
        }

        if (isset($options['site_url']) && $options['site_url'] !== '') {
            $payload['site_url'] = $options['site_url'];
        }

        return array_merge($payload, PlannerRecordService::submissionMeta());
    }

    /**
     * Insert or update the planner row, stamping created_at on first write.
     *
     * @param array<string, mixed> $payload
     * @return array{success:bool,message?:string}
     */
    public static function save(array $payload, string $plannerId, bool $exists): array
    {
        if (!$exists) {
            // Keep the original creation time when updating an existing quote.
            $payload['created_at'] = date('Y-m-d H:i:s');
        }

        return (new Database())->updateOrCreate('planners', $payload, ['planner_id' => $plannerId]);
    }

    /**
     * Flag the quote as handed off to the store.
     */
    public static function markSubmitted(string $plannerId): void
    {
        (new Database())->update('planners', [
            'status'            => 'submitted',
            'status_updated_at' => date('Y-m-d H:i:s'),
        ], ['planner_id' => $plannerId]);
    }

    /**
     * @param array<string, mixed> $fc_data
     */
    private static function sectionCount(array $fc_data): int
    {
        $raw = $fc_data['fences'] ?? null;
        if (empty($raw)) {
            return 0;
        }

        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($decoded) ? count($decoded) : 0;
    }
}
