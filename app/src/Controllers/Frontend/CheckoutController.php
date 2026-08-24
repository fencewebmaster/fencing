<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Frontend;

use Fc\Admin\Models\CheckoutCartModel;
use Fc\Admin\Models\PlannerSubmissionModel;
use Fc\Admin\Services\PlannerRecordService;
use Fc\Admin\Services\PlannerSessionService;
use Fc\Admin\Services\SiteRegistryService;
use Fc\Admin\Services\StorePushService;

/**
 * Project-plan AJAX endpoint (/checkout).
 *
 * Every action either returns JSON or an HTML fragment that checkout.js swaps into
 * the page — there is no full-page checkout view.
 */
final class CheckoutController extends BaseFrontendController
{
    public function index(): void
    {
        $this->startSession();

        $fences = $this->fences();
        $action = (string) $this->request->post('action', '');

        switch ($action) {
            case 'push_order':
                $this->pushOrder($fences);
                break;

            case 'save_planner':
                $this->savePlanner($fences);
                break;

            case 'update_details':
            case 'update_project_details':
                $this->updateProjectDetails($fences);
                break;

            case 'rebuild_cart_from_plans':
                $this->rebuildCartFromPlans($fences);
                break;

            case 'toggle_optional_cart':
                $this->toggleOptionalCart($fences);
                break;

            case 'update_cart':
                $this->updateCart($fences);
                break;
        }

        exit;
    }

    /**
     * Save the quote, then hand it off to the WooCommerce store.
     *
     * @param array<string, mixed> $fences
     */
    private function pushOrder(array $fences): void
    {
        // Snapshot before the save — this is what gets pushed to the store.
        $info = $_SESSION;

        // Never mint an id here: an order push must attach to the quote that was already saved.
        $planner_ref = PlannerRecordService::resolveSubmissionPlannerId($this->request->post('planner_id'), false);
        $planner_id  = $planner_ref['planner_id'] !== '' ? $planner_ref['planner_id'] : null;

        if (!$planner_id) {
            $this->fail('Missing planner ID. Please save your project first.');
        }

        $fc_site = isset($info['site']) ? $info['site'] : null;

        if (!$fc_site || empty($fc_site['url'])) {
            $this->fail('Site configuration is missing.');
        }

        $store_url = StorePushService::storeUrl($fc_site);

        $payload = PlannerSubmissionModel::payload($fences, $planner_id, ['site_url' => $store_url]);
        $res     = PlannerSubmissionModel::save($payload, $planner_id, $planner_ref['exists']);

        if (!$res['success']) {
            $this->fail('Could not save planner: ' . ($res['message'] ?? 'Unknown error'));
        }

        $push = StorePushService::push($store_url, (string) json_encode($info));

        if (!$push['ok']) {
            $this->fail($push['message']);
        }

        // Push succeeded — the quote has actually been handed off to the store, so mark it submitted.
        PlannerSubmissionModel::markSubmitted($planner_id);

        echo $push['body'];

        // Same server-side reset the "Clear All" button uses (PlannerController's
        // ?action=clear-all) — a successful push starts the next session exactly as
        // clean as an explicit clear, not a second, separately-maintained unset list.
        PlannerSessionService::clearPlannerSessions();

        exit;
    }

    /**
     * Save the quote without pushing it to the store.
     *
     * @param array<string, mixed> $fences
     */
    private function savePlanner(array $fences): void
    {
        $planner_ref = PlannerRecordService::resolveSubmissionPlannerId($this->request->post('planner_id'));
        $planner_id  = $planner_ref['planner_id'];

        $_SESSION['planner_id'] = $planner_id;

        $payload = PlannerSubmissionModel::payload($fences, $planner_id, ['location' => false]);
        $res     = PlannerSubmissionModel::save($payload, $planner_id, $planner_ref['exists']);

        if (empty($res['success'])) {
            echo json_encode([
                'error'   => true,
                'message' => 'Error: An error occurred while saving planner.',
                'url'     => '',
            ]);

            return;
        }

        echo json_encode([
            'error'   => false,
            'message' => 'Planner has been successfully saved!',
            'id'      => $planner_id,
        ]);
    }

    /**
     * Merge edited customer/project details into the session and re-render the details card.
     *
     * @param array<string, mixed> $fences
     */
    private function updateProjectDetails(array $fences): void
    {
        CheckoutCartModel::applyProjectDetails($this->request->allPost());

        // Render before persisting: persistSession() rewrites fc_data['project_plans'] from
        // the client payload, which must not leak into the markup echoed back for this request.
        $html = $this->renderToString('sections/your-project-details.php', ['fences' => $fences]);

        PlannerRecordService::persistSession($fences);

        echo $html;
    }

    /**
     * @param array<string, mixed> $fences
     */
    private function rebuildCartFromPlans(array $fences): void
    {
        CheckoutCartModel::rebuildFromPlans($this->request->allPost());

        PlannerRecordService::persistSession($fences);

        echo $this->renderToString('sections/cart-table.php', ['fences' => $fences]);

        exit;
    }

    /**
     * @param array<string, mixed> $fences
     */
    private function toggleOptionalCart(array $fences): void
    {
        $optional_key = trim((string) $this->request->post('optional_key', ''));
        $include      = (string) $this->request->post('include', '') === '1';

        CheckoutCartModel::toggleOptional($optional_key, $include);

        echo $this->renderToString('sections/cart-table.php', ['fences' => $fences]);

        PlannerRecordService::persistSession($fences);

        exit;
    }

    /**
     * @param array<string, mixed> $fences
     */
    private function updateCart(array $fences): void
    {
        $cart = $this->request->post('cart');
        CheckoutCartModel::updateQuantities(is_array($cart) ? $cart : []);

        PlannerRecordService::persistSession($fences);

        echo $this->renderToString('sections/cart-table.php', ['fences' => $fences]);
    }

    /**
     * Emit a JSON error envelope and stop (never returns).
     */
    private function fail(string $message): void
    {
        echo json_encode([
            'error'   => true,
            'message' => $message,
        ]);

        exit;
    }
}
