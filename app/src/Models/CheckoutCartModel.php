<?php

declare(strict_types=1);

namespace Fc\Admin\Models;

use Fc\Admin\Services\CartBuilderService;
use Fc\Admin\Services\FenceCatalogService;
use Fc\Admin\Services\PlannerSessionService;

/**
 * Session-cart mutations behind the project-plan AJAX endpoints (the /checkout route).
 *
 * Each method leaves `$_SESSION['fc_data']` / `$_SESSION['fc_cart']` in the state the
 * corresponding view fragment (sections/your-project-details.php, sections/cart-table.php)
 * is then rendered from.
 */
final class CheckoutCartModel
{
    /**
     * Merge posted customer/project details into the session and rebuild the item list
     * from the stored BOM whenever colours changed.
     *
     * @param array<string, mixed> $post Raw POST body (already stripped of `cart` / `action`).
     */
    public static function applyProjectDetails(array $post): void
    {
        $posted_colors = $post['color'] ?? null;

        unset($post['cart'], $post['action']);

        if (!empty($post['nothing_extra'])) {
            $_SESSION['fc_data']['nothing_extra'] = (string) $post['nothing_extra'];
            $post['extra'] = '[]';
        } else {
            unset($_SESSION['fc_data']['nothing_extra'], $post['nothing_extra']);
            $post['extra'] = CartBuilderService::convertInputs($post['extra'] ?? '[]');
            if ($post['extra'] === '' || $post['extra'] === null) {
                $post['extra'] = '[]';
            }
        }

        $post['color'] = CartBuilderService::convertInputs($post['color'] ?? null);

        if (isset($post['project_plans']) && (string) $post['project_plans'] !== '') {
            $_SESSION['fc_data']['project_plans'] = (string) $post['project_plans'];
        }

        $_SESSION['fc_data'] = array_merge($_SESSION['fc_data'], $post);

        if (isset($_SESSION['fc_data']['mobile'])) {
            $_SESSION['fc_data']['mobile'] = CartBuilderService::normalizeMobileForStorage($_SESSION['fc_data']['mobile']);
        }

        /* Rebuild item list & cart SKUs from stored BOM + updated colour rows */
        if (!$posted_colors) {
            return;
        }

        $colors             = PlannerSessionService::colorRowsFromSession($posted_colors);
        $cart_items_raw     = $_SESSION['fc_data']['cart_items'] ?? '[]';
        $cart_items_grouped = is_array($cart_items_raw)
            ? $cart_items_raw
            : json_decode((string) $cart_items_raw, true);

        if (is_array($cart_items_grouped) && count($cart_items_grouped) && is_array($colors) && count($colors)) {
            $cart_items_regrouped = FenceCatalogService::regroupPlannerCartItemsForSkus($cart_items_grouped, $colors);
            $cart_items_data      = FenceCatalogService::formatRegroupedCartItemsForProductSkus(
                $cart_items_regrouped,
                $_SESSION['fc_data']['fences'] ?? '[]'
            );
            if (!empty($cart_items_data)) {
                CartBuilderService::postProductSkus($cart_items_data);
            }
        }
    }

    /**
     * Rebuild the whole cart from the project plans posted by the browser.
     *
     * @param array<string, mixed> $post
     */
    public static function rebuildFromPlans(array $post): void
    {
        $color_override = null;
        if (isset($post['color']) && $post['color'] !== '') {
            $color_override = is_array($post['color'])
                ? $post['color']
                : json_decode((string) $post['color'], true);
        }

        $colors = PlannerSessionService::colorRowsFromSession(
            is_array($color_override) ? $color_override : null
        );

        $cart_items_raw       = isset($post['cart_items']) ? (string) $post['cart_items'] : '[]';
        $cart_items_grouped   = json_decode($cart_items_raw, true);
        $cart_items_regrouped = FenceCatalogService::regroupPlannerCartItemsForSkus($cart_items_grouped, $colors);
        $cart_items_data      = FenceCatalogService::formatRegroupedCartItemsForProductSkus(
            $cart_items_regrouped,
            $_SESSION['fc_data']['fences'] ?? '[]'
        );

        if (!isset($_SESSION['fc_data']) || !is_array($_SESSION['fc_data'])) {
            $_SESSION['fc_data'] = [];
        }
        $_SESSION['fc_data']['cart_items'] = $cart_items_raw;

        if (isset($post['project_plans']) && (string) $post['project_plans'] !== '') {
            $_SESSION['fc_data']['project_plans'] = (string) $post['project_plans'];
        }

        if (is_array($color_override) && $color_override !== []) {
            $_SESSION['fc_data']['color'] = CartBuilderService::convertInputs($color_override);
        }

        CartBuilderService::postProductSkus($cart_items_data);
    }

    /**
     * Include/exclude an optional cart line (and its custom-fence product twin).
     */
    public static function toggleOptional(string $optionalKey, bool $include): void
    {
        if ($optionalKey !== '' && !empty($_SESSION['fc_cart']['items']) && is_array($_SESSION['fc_cart']['items'])) {
            foreach ($_SESSION['fc_cart']['items'] as $idx => $row) {
                if (!is_array($row) || empty($row['optional'])) {
                    continue;
                }
                $row_key = !empty($row['optional_key'])
                    ? (string) $row['optional_key']
                    : CartBuilderService::optionalCartItemKey($row);
                if ($row_key !== $optionalKey) {
                    continue;
                }
                $suggested = (int) ($row['suggested_qty'] ?? 0);
                $_SESSION['fc_cart']['items'][$idx]['optional_included'] = $include;
                $_SESSION['fc_cart']['items'][$idx]['qty']               = $include ? $suggested : 0;
                $_SESSION['fc_cart']['items'][$idx]['original_qty']      = $_SESSION['fc_cart']['items'][$idx]['qty'];
            }
        }

        if (empty($_SESSION['custom_fence_products']) || !is_array($_SESSION['custom_fence_products'])) {
            return;
        }

        foreach ($_SESSION['custom_fence_products'] as $pk => $prod) {
            if (!is_array($prod) || empty($prod['optional'])) {
                continue;
            }
            if (CartBuilderService::optionalCartItemKey($prod) !== $optionalKey) {
                continue;
            }
            $suggested = (int) ($prod['suggested_qty'] ?? 0);
            $_SESSION['custom_fence_products'][$pk]['qty'] = $include ? $suggested : 0;
        }
    }

    /**
     * Apply the quantity inputs posted from the item list.
     *
     * @param array<string, mixed> $postedCart The `cart` POST array (`qty` keyed by cart index).
     */
    public static function updateQuantities(array $postedCart): void
    {
        $cart_items_data = [];
        // optional_key => saved qty, for the custom_fence_products sync after the loop.
        $optional_qty_by_key = [];

        foreach ($_SESSION['fc_cart']['items'] as $cart_item_k => $cart_item) {
            $posted = $postedCart['qty'][$cart_item_k] ?? null;
            // Quantity must be a plain non-negative integer — it's stored in the session and
            // later rendered back into the cart view, so anything else (including markup) is
            // rejected outright rather than trying to sanitize it.
            $quantity = (is_int($posted) || (is_string($posted) && ctype_digit($posted)))
                ? (int) $posted
                : (int) ($cart_item['qty'] ?? 0);

            $cart_items_data[$cart_item_k] = $cart_item;

            $cart_items_data[$cart_item_k]['qty'] = $quantity;

            // On an optional line the quantity is what decides whether it is in the cart, so a
            // save re-derives that rather than leaving it to the Add/Remove button. Editing a
            // pending line up from 0 includes it; taking an included line back to 0 drops it to
            // pending. Either way the row stays in the list — nothing here removes it — so a line
            // taken to 0 keeps its place and offers Add to cart again. The button itself is
            // rendered from optional_included, and the save response re-renders the table, so it
            // swaps without any client-side help.
            if (!empty($cart_item['optional'])) {
                $was_included = !empty($cart_item['optional_included']);
                $included     = $quantity > 0;
                $cart_items_data[$cart_item_k]['optional_included'] = $included;

                // Adding re-baselines original_qty, the same way the Add to cart button does:
                // the line was not in the cart, so the quantity it arrives with is its starting
                // point, not an edit of one. Without this the row would come back carrying the
                // edited pencil and a Reset that empties it again.
                // Only on the way in. Taking a line to 0 leaves original_qty where it was, so
                // Reset still restores the quantity it had before it was removed.
                if ($included && !$was_included) {
                    $cart_items_data[$cart_item_k]['original_qty'] = $quantity;
                }

                $optional_key = !empty($cart_item['optional_key'])
                    ? (string) $cart_item['optional_key']
                    : CartBuilderService::optionalCartItemKey($cart_item);
                if ($optional_key !== '') {
                    $optional_qty_by_key[$optional_key] = $quantity;
                }
            }
        }

        $_SESSION['fc_cart'] = [
            'items' => $cart_items_data,
        ];

        // The same line exists a second time in custom_fence_products, which is what gets posted
        // to the store. toggleOptional keeps the two in step; a quantity save has to as well, or
        // an item added this way would show in the cart and be missing from the order.
        if ($optional_qty_by_key !== [] && !empty($_SESSION['custom_fence_products'])
            && is_array($_SESSION['custom_fence_products'])) {
            foreach ($_SESSION['custom_fence_products'] as $pk => $prod) {
                if (!is_array($prod) || empty($prod['optional'])) {
                    continue;
                }
                $prod_key = CartBuilderService::optionalCartItemKey($prod);
                if (array_key_exists($prod_key, $optional_qty_by_key)) {
                    $_SESSION['custom_fence_products'][$pk]['qty'] = $optional_qty_by_key[$prod_key];
                }
            }
        }
    }
}
