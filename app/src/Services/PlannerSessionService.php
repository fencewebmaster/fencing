<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * Planner/cart $_SESSION read-write + DB-row↔session hydration
 * (config/helpers.php migration).
 */
final class PlannerSessionService
{
    /**
     * @param array<string, array<string, mixed>> $fences
     * @return array<string, mixed>
     */
    public static function selectedFences(array $fences, string $column = 'slug'): array
    {
        $info = $_SESSION['fc_data'];

        $fenceData = [];

        foreach (CartBuilderService::convertInputs($info['fences']) as $fence) {
            $slug = $fence['form'][0]['fence'];
            $fenceData[$slug] = $fences[$slug][$column];
        }

        return $fenceData;
    }

    /**
     * Normalise planner colour rows from session / POST (never pass arrays through convertInputs).
     *
     * @return array<int|string, mixed>
     */
    public static function colorRowsFromSession(mixed $overrideColors = null): array
    {
        if (is_array($overrideColors)) {
            return $overrideColors;
        }

        if (is_string($overrideColors) && $overrideColors !== '') {
            $decoded = json_decode($overrideColors, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $info = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
        $colors = [];

        if (!empty($info['color'])) {
            if (is_array($info['color'])) {
                $colors = $info['color'];
            } else {
                $colors = CartBuilderService::convertInputs($info['color']);
                if (is_string($colors)) {
                    $decoded = json_decode($colors, true);
                    $colors = is_array($decoded) ? $decoded : [];
                }
            }
        }

        if ((!is_array($colors) || $colors === []) && !empty($info['project_plans'])) {
            $pp = is_array($info['project_plans']) ? $info['project_plans'] : json_decode((string) $info['project_plans'], true);
            if (is_array($pp) && !empty($pp['color']) && is_array($pp['color'])) {
                $colors = $pp['color'];
            }
        }

        return is_array($colors) ? $colors : [];
    }

    public static function clearPlannerSessions(): void
    {
        $sessions = [
            'fc_data',
            'custom_fence_products',
            'fc_cart',
            'planner_id',
            'site',
        ];

        foreach ($sessions as $session) {
            unset($_SESSION[$session]);
        }
    }

    /**
     * Restore $_SESSION['fc_data'] (and cart) from a saved planners row so load-quote + project-plan work.
     */
    public static function hydrateFromRow(mixed $row): void
    {
        if (!$row || !is_object($row)) {
            return;
        }

        if (empty($_SESSION['site'])) {
            $site = SiteRegistryService::all($_SERVER['HTTP_HOST'], 'domain', true);
            if ($site) {
                $_SESSION['site'] = $site;
            }
        }

        if (empty($_SESSION['fc_data']) || !is_array($_SESSION['fc_data'])) {
            $_SESSION['fc_data'] = [];
        }

        $fd = isset($row->fence_data) ? $row->fence_data : '';
        if ($fd !== '' && $fd !== null) {
            $_SESSION['fc_data']['fences'] = is_string($fd) ? $fd : json_encode($fd);
        }

        $cart = isset($row->cart_items_data) ? $row->cart_items_data : '';
        if ($cart !== '' && $cart !== null) {
            $_SESSION['fc_data']['cart_items'] = is_string($cart) ? $cart : json_encode($cart);
        }

        $cartData = isset($row->cart_data) ? $row->cart_data : '';
        if ($cartData !== '' && $cartData !== null) {
            $decodedCart = is_string($cartData) ? json_decode($cartData, true) : $cartData;
            if (is_array($decodedCart)) {
                $_SESSION['fc_cart']['items'] = $decodedCart;
            }
        }

        $pp = isset($row->project_plans_data) ? $row->project_plans_data : '';
        if ($pp !== '' && $pp !== null) {
            $_SESSION['fc_data']['project_plans'] = is_string($pp) ? $pp : json_encode($pp);
        }

        $color = isset($row->color_data) ? $row->color_data : '';
        if ($color !== '' && $color !== null) {
            $_SESSION['fc_data']['color'] = is_string($color) ? $color : json_encode($color);
        }

        foreach (['name', 'mobile', 'email', 'address', 'postcode', 'state', 'notes', 'timeframe', 'extra'] as $col) {
            if (isset($row->$col) && $row->$col !== '' && $row->$col !== null) {
                $val = $row->$col;
                if ($col === 'mobile') {
                    $val = CartBuilderService::normalizeMobileForStorage($val);
                }
                $_SESSION['fc_data'][$col] = $val;
            }
        }

        $products = isset($row->products_data) ? $row->products_data : '';
        if ($products !== '' && $products !== null) {
            $decodedProducts = is_string($products) ? json_decode($products, true) : $products;
            if (is_array($decodedProducts)) {
                $_SESSION['custom_fence_products'] = $decodedProducts;
            }
        }
    }

    /**
     * Build `project-plans` localStorage JSON from the active PHP session (planner ← project-plan sync).
     */
    public static function clientProjectPlansFromSession(): string
    {
        $fc = isset($_SESSION['fc_data']) && is_array($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
        $pp = [];

        if (!empty($fc['project_plans'])) {
            $raw = $fc['project_plans'];
            $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $pp = $decoded;
            }
        }

        foreach (['name', 'mobile', 'email', 'address', 'postcode', 'state', 'notes', 'timeframe'] as $k) {
            if (array_key_exists($k, $fc) && $fc[$k] !== null && $fc[$k] !== '') {
                $pp[$k] = ($k === 'mobile')
                    ? CartBuilderService::normalizeMobileForStorage($fc[$k])
                    : $fc[$k];
            }
        }

        if (!empty($fc['nothing_extra'])) {
            $pp['nothing_extra'] = $fc['nothing_extra'];
        } elseif (!empty($fc['extra'])) {
            $extra = $fc['extra'];
            if (is_array($extra)) {
                $pp['extra'] = $extra;
            } elseif (is_string($extra)) {
                $trimmed = trim($extra);
                if ($trimmed === 'nothing') {
                    $pp['nothing_extra'] = 'nothing';
                } else {
                    $decodedExtra = json_decode($extra, true);
                    $pp['extra'] = is_array($decodedExtra) ? $decodedExtra : $extra;
                }
            } else {
                $pp['extra'] = $extra;
            }
        }

        if (!empty($pp['nothing_extra'])) {
            unset($pp['extra']);
        }

        if (!empty($fc['color'])) {
            $color = $fc['color'];
            $colors = is_array($color) ? $color : json_decode((string) $color, true);
            if (is_array($colors)) {
                $pp['color'] = $colors;
            }
        }

        return json_encode($pp, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Shareable planner URL for a saved quote (e.g. https://fencesperth.com/fc?qid=2C3A4M).
     */
    public static function qidShareUrl(?string $plannerId = null): string
    {
        $plannerId = $plannerId !== null && $plannerId !== ''
            ? $plannerId
            : (string) ($_SESSION['planner_id'] ?? '');
        $plannerId = trim($plannerId);
        if ($plannerId === '') {
            return '';
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $fcRoot = rtrim(dirname($script), '/');

        return $scheme . '://' . $host . $fcRoot . '?qid=' . rawurlencode($plannerId);
    }

    /**
     * Shape expected by planner / project-plan JS (`fc_fence_info`).
     *
     * @param object|null $row planners table row.
     */
    public static function rowToJsFenceInfo(mixed $row): object
    {
        if (!$row || !is_object($row)) {
            return (object) [];
        }

        $fenceData = isset($row->fence_data) ? $row->fence_data : '';
        if (is_array($fenceData)) {
            $fenceData = json_encode($fenceData);
        }

        $cartItems = isset($row->cart_items_data) ? $row->cart_items_data : '[]';
        if (is_array($cartItems)) {
            $cartItems = json_encode($cartItems);
        }

        $projectPlans = isset($row->project_plans_data) ? $row->project_plans_data : '';
        if (is_array($projectPlans)) {
            $projectPlans = json_encode($projectPlans);
        }

        $sectionCount = isset($row->section_count) ? (int) $row->section_count : 0;
        if ($sectionCount < 1 && is_string($fenceData) && $fenceData !== '') {
            $decoded = json_decode($fenceData, true);
            if (is_array($decoded)) {
                $sectionCount = count($decoded);
            }
        }

        return (object) [
            'fence_data' => $fenceData,
            'cart_items_data' => $cartItems ? $cartItems : '[]',
            'project_plans_data' => $projectPlans,
            'section_count' => $sectionCount,
        ];
    }
}
