<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Core\FrontendApplication;
use Fc\Admin\Core\NotFoundHandler;
use Fc\Admin\Models\ProjectPlanPageModel;
use Fc\Admin\Services\Database;
use Fc\Admin\Services\PlannerRecordService;
use Fc\Admin\Services\PlannerSessionService;
use Fc\Admin\Services\StorePushService;

/**
 * Early-submission share link (/share-cart-url/{plannerId}) — the URL the pre-checkout
 * Zapier payload carries as share_cart_url. Stateless by design: nothing is minted or
 * stored when the link is issued, and every click re-materialises the store cart from
 * the saved quote row, so the link always resolves to the latest saved state (and
 * survives the store deleting its one-shot cache file after each visit).
 *
 * Two-step interstitial: a plain GET only validates the id and renders the loader page,
 * whose JS/meta-refresh immediately re-requests with ?go=1; that request rebuilds the
 * cart server-side, pushes it to the store (?fc_action=push) and 302s to the store's
 * cart loader with fc_silent=1 — the store fills the cart but skips the share-cart post
 * mint, the customer email and the checkout webhooks, because a CRM link gets clicked
 * repeatedly. Also deliberately skipped here: markReloaded (quote_load_count means "the
 * customer reopened their quote") and markSubmitted (a sales click must not flip status).
 */
final class ShareCartUrlController extends BaseFrontendController
{
    public function index(): void
    {
        $requestedId = trim((string) $this->request->query('planner_id', ''));

        // isValidPlannerId is the only injection defence on this WHERE string — keep it first.
        $row = null;
        if (PlannerRecordService::isValidPlannerId($requestedId)) {
            $db  = new Database();
            $row = $db->select_where('planners', '`planner_id`="' . $requestedId . '"');
        }

        if (!$row || !is_object($row) || PlannerRecordService::rowIsTrashed($row)) {
            NotFoundHandler::abort('frontend', 'This quote could not be found.');
        }

        // The row's own spelling, not the URL's: the id doubles as the store cache token,
        // and a case-insensitive DB match must not fork a second cache file.
        $plannerId = (string) $row->planner_id;

        if (empty($this->request->query('go', ''))) {
            $this->renderPage('');
            return;
        }

        $error = $this->materialiseAndRedirect($plannerId, $row);
        $this->renderPage($error);
    }

    /**
     * Rebuild the quote's cart into the session, push it to the store and redirect into
     * the store's cart loader. Returns an error message instead when it can't.
     */
    private function materialiseAndRedirect(string $plannerId, object $row): string
    {
        $this->startSession();

        // The clicker may carry their own planner session, and hydrateFromRow() only
        // overwrites non-empty columns — without this wipe a cart-less quote would push
        // the visitor's leftover items under this quote's token. In-memory only: the
        // session_abort() below keeps their on-disk session exactly as it was.
        PlannerSessionService::clearPlannerSessions();

        try {
            // Publishes $GLOBALS['fences'], which postProductSkus() dereferences.
            $this->fences();

            // hydrateFromRow() never sets planner_id, and the store keys its cache by it.
            $_SESSION['planner_id'] = $plannerId;
            PlannerSessionService::hydrateFromRow($row);

            $info = isset($_SESSION['fc_data']) && is_array($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
            $cart = isset($_SESSION['fc_cart']) && is_array($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];

            $hydratedItems = isset($cart['items']) && is_array($cart['items']) ? $cart['items'] : [];

            try {
                ProjectPlanPageModel::refreshCartFromPlan($info, $cart);
            } catch (\Throwable $e) {
                // A failed SKU refresh (e.g. postProductSkus' rand() ValueError on a 1-line
                // cart) still leaves the row's saved cart_data hydrated — push that instead.
                error_log('FC share-cart: cart refresh failed for ' . $plannerId . ' — ' . $e->getMessage());
            }

            $items = isset($_SESSION['fc_cart']['items']) ? $_SESSION['fc_cart']['items'] : null;
            if ((!is_array($items) || $items === []) && $hydratedItems !== []) {
                // postProductSkus() overwrites fc_cart even when it resolved nothing (legacy
                // rows with empty products_data) — an empty rebuild must not beat saved cart_data.
                $_SESSION['fc_cart']['items'] = $hydratedItems;
                $items = $hydratedItems;
            }
            if (!is_array($items) || $items === []) {
                return 'This quote has no materials list yet.';
            }

            $fc_site = isset($_SESSION['site']) && is_array($_SESSION['site']) ? $_SESSION['site'] : null;
            if (!$fc_site || empty($fc_site['url'])) {
                return 'Site configuration is missing.';
            }

            $push = StorePushService::push(StorePushService::storeUrl($fc_site), (string) json_encode($_SESSION));
            if (empty($push['ok'])) {
                return (string) (($push['message'] ?? '') !== '' ? $push['message'] : 'The store could not be reached.');
            }

            $decoded = json_decode((string) $push['body'], true);
            $cartUrl = is_array($decoded) ? (string) ($decoded['url'] ?? '') : '';
            if ($cartUrl === '') {
                return 'The store returned an invalid cart URL.';
            }
        } catch (\Throwable $e) {
            error_log('FC share-cart: could not load quote ' . $plannerId . ' — ' . $e->getMessage());

            return 'We could not load this quote right now.';
        } finally {
            // Stateless: the hydrated quote must not replace whatever planner session the
            // clicker already had. The pushed payload was snapshotted above.
            session_abort();
        }

        // The store reads fc_silent from $_GET, so it must land in the query string
        // whatever URL shape the push response carries — never after a bare path.
        $cartUrl .= (strpos($cartUrl, '?') === false ? '?' : '&') . 'fc_silent=1';

        header('Location: ' . $cartUrl);
        exit;
    }

    /**
     * Loader/error interstitial. The plain GET stays session-less; ?go=1 owns the work.
     */
    private function renderPage(string $error): void
    {
        $appBase = FrontendApplication::basePath();

        view('frontend.share-cart.index', [
            'fcShareCartError' => $error,
            'fcShareCartGoUrl' => '?go=1',
            // Nested route: basePath()-built asset URLs, never the one-segment asset()
            // helper. Edit in pairs with LookupController::index()'s copy of this closure.
            'fcShareCartAsset' => static function (string $rel) use ($appBase): string {
                $rel  = ltrim($rel, '/');
                $path = FC_ROOT . '/' . $rel;
                $url  = $appBase !== '' ? $appBase . '/' . $rel : '/' . $rel;

                if (is_file($path)) {
                    return $url . '?v=' . filemtime($path);
                }

                return $url;
            },
        ]);
    }
}
