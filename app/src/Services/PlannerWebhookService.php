<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\RequestHelper;
use Fc\Admin\Helpers\UrlHelper;
use Fc\Admin\Presenters\PlannerEntryPresenter;
use Fc\Admin\Settings\IntegrationsSettings;
use Fc\Admin\Settings\PlannerOptionSettings;

/**
 * Early Zapier notification — fires before checkout, independent of the WooCommerce cart
 * push (which the sibling WP plugin's `push_order()` always fires unconditionally on its
 * own, regardless of this class). Only fires when IntegrationsSettings::webhookPrePlannerEnabled
 * is on, right after the "Download Your Project Plans" modal's final submit. Guarded by
 * `wp_planners.webhook_sent_at` so a given planner only notifies once per calendar day when
 * IntegrationsSettings::webhookSameDayDedup is on. IntegrationsSettings::webhookMode ('live'|'test')
 * picks which URL actually gets posted to — the real Webhook URL or the separate Test Webhook
 * URL — so this can be exercised without notifying Zapier. Planner Entries can also resend a
 * saved entry on demand (sendForEntry()).
 */
final class PlannerWebhookService
{
    /**
     * Called from SubmitController when the "Download Your Project Plans" modal's
     * final step (tab 4) is what was just submitted — not a routine autosave.
     */
    public static function maybeFireForFormSubmission(string $plannerId): void
    {
        $plannerId = trim($plannerId);
        if ($plannerId === '' || !PlannerRecordService::isValidPlannerId($plannerId)) {
            return;
        }

        $integrations = IntegrationsSettings::get();
        if (empty($integrations['webhookPrePlannerEnabled'])) {
            return;
        }

        $webhookUrl = self::modeWebhookUrl($integrations);
        if ($webhookUrl === '') {
            return;
        }

        if (!self::claimSlot($plannerId, (bool) ($integrations['webhookSameDayDedup'] ?? true))) {
            return;
        }

        // A genuinely failed delivery (network/SSL/HTTP error) shouldn't burn the dedup
        // claim — release it so the next form submission for this planner can retry today,
        // instead of silently blocking for the rest of the calendar day.
        if (!self::send($webhookUrl, self::buildZapierPayload($plannerId))) {
            self::releaseSlot($plannerId);
        }
    }

    /**
     * Admin "Send Pre-Planner Submission" on a Planner Entries detail page: posts the same
     * payload built from the saved row. The Enable Pre-Planner toggle and same-day dedup are
     * deliberately skipped — they govern the automatic customer trigger, not a confirmed resend.
     *
     * @return array{ok:bool,error?:string,message?:string,sent_at?:string}
     */
    public static function sendForEntry(int $entryId): array
    {
        // The payload links are built on this admin host, which isn't the switched site's domain.
        if (AdminSiteRegistry::isSiteSwitched()) {
            return ['ok' => false, 'error' => 'Switch back to your own site to send this entry. Its links would point to the wrong site.'];
        }

        $integrations = IntegrationsSettings::get();
        $isTestMode = ($integrations['webhookMode'] ?? 'live') === 'test';
        $webhookUrl = self::modeWebhookUrl($integrations);
        if ($webhookUrl === '') {
            return [
                'ok' => false,
                'error' => ($isTestMode ? 'Test mode is on but no Test Webhook URL is set.' : 'No Webhook URL is set.')
                    . ' Add one in Settings → Integrations.',
            ];
        }

        try {
            $row = $entryId > 0 ? self::entryRow($entryId) : null;
        } catch (\RuntimeException $e) {
            error_log('FC webhook: could not load planner entry ' . $entryId . ' — ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Could not load this entry. Try again.'];
        }
        if ($row === null) {
            return ['ok' => false, 'error' => 'Entry not found.'];
        }

        $plannerId = trim((string) ($row['planner_id'] ?? ''));
        if (!PlannerRecordService::isValidPlannerId($plannerId)) {
            return ['ok' => false, 'error' => 'This entry has no valid planner ID.'];
        }
        // Trashed quotes 404 on both ?qid= and /share-cart-url, so the CRM would get dead links.
        if (PlannerRecordService::rowIsTrashed($row)) {
            return ['ok' => false, 'error' => 'Restore this entry from the trash before sending it.'];
        }

        // Same fc_data keys hydrateFromRow() restores; extra falls back like the detail page shows it.
        $fcData = [
            'name' => $row['name'] ?? '',
            'email' => $row['email'] ?? '',
            'mobile' => $row['mobile'] ?? '',
            'address' => $row['address'] ?? '',
            'postcode' => $row['postcode'] ?? '',
            'state' => $row['state'] ?? '',
            'notes' => $row['notes'] ?? '',
            'timeframe' => $row['timeframe'] ?? '',
            'extra' => PlannerEntryPresenter::resolveExtraValue($row['extra'] ?? null, $row['project_plans_data'] ?? null),
        ];
        $colorRows = json_decode((string) ($row['color_data'] ?? ''), true);

        // UrlHelper::baseUrl() would resolve to the /backend mount from here.
        $appUrl = (RequestHelper::isHttps() ? 'https' : 'http') . '://'
            . (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')
            . UrlHelper::plannerAppBaseFromAdminScript() . '/';

        // Empty cookies: the customer's aren't stored, and the admin's (session id included) must not leak.
        $payload = self::buildPayload(
            $plannerId,
            $fcData,
            is_array($colorRows) ? $colorRows : [],
            [],
            $appUrl,
            (string) ($row['status'] ?? '')
        );
        if (!self::send($webhookUrl, $payload)) {
            return ['ok' => false, 'error' => 'The webhook could not be delivered. Check the URL in Settings → Integrations and try again.'];
        }

        $sentAt = (new \DateTime('now'))->format('Y-m-d H:i:s');
        self::stampSentAt($entryId, $sentAt);

        return [
            'ok' => true,
            'message' => $isTestMode
                ? 'Pre-Planner submission sent to the Test Webhook URL.'
                : 'Pre-Planner submission sent.',
            'sent_at' => $sentAt,
        ];
    }

    /**
     * The URL IntegrationsSettings::webhookMode currently selects ('' when that one is unset).
     *
     * @param array<string, mixed> $integrations
     */
    private static function modeWebhookUrl(array $integrations): string
    {
        $mode = (string) ($integrations['webhookMode'] ?? 'live');

        return trim((string) (
            $mode === 'test' ? ($integrations['webhookTestUrl'] ?? '') : ($integrations['webhookUrl'] ?? '')
        ));
    }

    /**
     * The saved columns sendForEntry() needs, or null for an unknown id. Throws on DB failure.
     *
     * @return array<string, mixed>|null
     */
    private static function entryRow(int $entryId): ?array
    {
        $ctx = PlannerRecordService::openDb();
        $stmt = $ctx['conn']->prepare(
            'SELECT `planner_id`, `status`, `name`, `email`, `mobile`, `address`, `postcode`, `state`, `notes`,'
            . ' `timeframe`, `extra`, `color_data`, `project_plans_data`, `trashed_at`'
            . ' FROM `' . $ctx['table'] . '` WHERE `id` = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new \RuntimeException('Could not prepare the entry query.');
        }

        $stmt->bind_param('i', $entryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? $row : null;
    }

    /**
     * Record a manual send against the entry's own row — the detail page's "Webhook sent" line.
     */
    private static function stampSentAt(int $entryId, string $sentAt): void
    {
        try {
            $ctx = PlannerRecordService::openDb();
            $stmt = $ctx['conn']->prepare(
                'UPDATE `' . $ctx['table'] . '` SET `webhook_sent_at` = ? WHERE `id` = ? LIMIT 1'
            );
            if (!$stmt) {
                throw new \RuntimeException('Could not prepare the update.');
            }
            $stmt->bind_param('si', $sentAt, $entryId);
            $stmt->execute();
            $stmt->close();
        } catch (\RuntimeException $e) {
            // The webhook already went out; only the "last sent" display is left stale.
            error_log('FC webhook: sent entry ' . $entryId . ' but could not stamp webhook_sent_at — ' . $e->getMessage());
        }
    }

    /**
     * Atomically claim the right to notify for this planner: true only if this call
     * actually updated the row (i.e. it was never sent, or — when same-day dedup is on —
     * the last send was on an earlier calendar day). A single conditional UPDATE avoids a
     * check-then-set race between near-simultaneous requests for the same planner id.
     *
     * "Now"/"today" are computed once here in PHP (date_default_timezone_get() — the same
     * idiom UserPresenter.php already uses) rather than via SQL's NOW(), so the calendar-day
     * boundary honors the application's configured timezone rather than the DB server's.
     */
    private static function claimSlot(string $plannerId, bool $sameDayDedup): bool
    {
        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return false;
        }

        $table = $ctx['table'];
        $conn = $ctx['conn'];

        $now = new \DateTime('now');
        $nowStr = $now->format('Y-m-d H:i:s');

        if ($sameDayDedup) {
            $today = $now->format('Y-m-d');
            $sql = 'UPDATE `' . $table . '`
                    SET `webhook_sent_at` = ?
                    WHERE `planner_id` = ?
                      AND (`webhook_sent_at` IS NULL OR DATE(`webhook_sent_at`) <> ?)
                    LIMIT 1';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('sss', $nowStr, $plannerId, $today);
            $stmt->execute();
            // Safe to trust affected_rows here: the WHERE guarantees the old date differs
            // from today (or was NULL), so the SET value always genuinely changes on a match.
            $claimed = $stmt->affected_rows === 1;
            $stmt->close();

            return $claimed;
        }

        // No date restriction, but still requires a genuine known planner row, and still
        // stamps webhook_sent_at — Planner Entries' detail page shows this as "last sent",
        // so it must stay accurate even with the restriction turned off. Existence is checked
        // directly rather than trusting the UPDATE's affected_rows: mysqli reports rows
        // CHANGED, not rows MATCHED, so two calls within the same second (identical $nowStr)
        // would otherwise report 0 and be misread as "no such planner."
        $existsStmt = $conn->prepare('SELECT 1 FROM `' . $table . '` WHERE `planner_id` = ? LIMIT 1');
        if (!$existsStmt) {
            return false;
        }
        $existsStmt->bind_param('s', $plannerId);
        $existsStmt->execute();
        $exists = (bool) $existsStmt->get_result()->fetch_row();
        $existsStmt->close();

        if (!$exists) {
            return false;
        }

        $updateStmt = $conn->prepare('UPDATE `' . $table . '` SET `webhook_sent_at` = ? WHERE `planner_id` = ? LIMIT 1');
        if ($updateStmt) {
            $updateStmt->bind_param('ss', $nowStr, $plannerId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        return true;
    }

    /**
     * Undo a claim after a failed delivery attempt, so the next form submission for this
     * planner isn't blocked by the dedup window for a webhook that never actually went out.
     */
    private static function releaseSlot(string $plannerId): void
    {
        try {
            $ctx = PlannerRecordService::openDb();
        } catch (\RuntimeException $e) {
            return;
        }

        $stmt = $ctx['conn']->prepare(
            'UPDATE `' . $ctx['table'] . '` SET `webhook_sent_at` = NULL WHERE `planner_id` = ? LIMIT 1'
        );
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('s', $plannerId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Same contacts/addresses/opportunities/cookies shape as the existing checkout-time
     * push (advanced-form-integration.php's `push_order()`), built from session data that's
     * already populated by the time either trigger point runs. `share_cart_url` is the
     * lazy /share-cart-url/{id} link (no Woo cart exists yet at this stage — the
     * ShareCartUrlController materialises it on click). `installer` is always
     * empty — this app's modal never collects an installer preference. `fencing_type` holds the
     * readable fence/colour names (FenceCatalogService::fenceColorLabels()); the plugin's push sends
     * the same text, from the label CheckoutController adds to the store push. `planner_status`,
     * `fence_types` (same names, kept for existing Zap mappings), `address_1` and `zipcode` are
     * FC-only additions; the plugin's push doesn't send them.
     *
     * @return array<string, mixed>
     */
    private static function buildZapierPayload(string $plannerId): array
    {
        $fcData = isset($_SESSION['fc_data']) && is_array($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

        // Safe from /submit (one segment deep).
        return self::buildPayload(
            $plannerId,
            $fcData,
            PlannerSessionService::colorRowsFromSession(),
            $_COOKIE,
            UrlHelper::baseUrl(),
            self::savedStatus($plannerId)
        );
    }

    /**
     * Status of the row /submit just saved — read back rather than assumed ('' if unreadable).
     */
    private static function savedStatus(string $plannerId): string
    {
        try {
            $ctx = PlannerRecordService::openDb();
            $stmt = $ctx['conn']->prepare(
                'SELECT `status` FROM `' . $ctx['table'] . '` WHERE `planner_id` = ? ORDER BY `id` DESC LIMIT 1'
            );
            if (!$stmt) {
                return '';
            }
            $stmt->bind_param('s', $plannerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_row() : null;
            $stmt->close();
        } catch (\RuntimeException $e) {
            return '';
        }

        return is_array($row) ? (string) $row[0] : '';
    }

    /**
     * The payload shape itself, from fc_data-shaped fields — the live session on /submit, or a
     * saved row's columns for sendForEntry(). $appUrl is the absolute app root with a trailing slash.
     *
     * @param array<string, mixed> $fcData
     * @param array<mixed> $colorRows
     * @param array<string, mixed> $cookies
     * @return array<string, mixed>
     */
    private static function buildPayload(
        string $plannerId,
        array $fcData,
        array $colorRows,
        array $cookies,
        string $appUrl,
        string $plannerStatus
    ): array {
        $name = trim((string) ($fcData['name'] ?? ''));
        $email = trim((string) ($fcData['email'] ?? ''));
        $mobile = trim((string) ($fcData['mobile'] ?? ''));
        $address = trim((string) ($fcData['address'] ?? ''));
        $postcode = trim((string) ($fcData['postcode'] ?? ''));
        $state = trim((string) ($fcData['state'] ?? ''));
        $notes = trim((string) ($fcData['notes'] ?? ''));

        $timeframeSlug = (string) ($fcData['timeframe'] ?? '');
        $timeframeLabel = PlannerOptionSettings::timeframeLabel($timeframeSlug) ?? $timeframeSlug;

        $extraJson = PlannerRecordService::extraForDb(
            $fcData['extra'] ?? null,
            isset($fcData['nothing_extra']) ? (string) $fcData['nothing_extra'] : null
        );
        $otherItems = self::extraItemLabels($extraJson);

        $fenceTypes = FenceCatalogService::fenceColorLabels($colorRows);

        $submissionUrl = $appUrl . 'planner?qid=' . rawurlencode($plannerId);
        // The id is validated alnum so it is path-safe.
        $shareCartUrl  = $appUrl . 'share-cart-url/' . rawurlencode($plannerId);

        $summary = self::buildSummary($timeframeLabel, $notes, $otherItems);

        return [
            'contacts' => [
                [
                    'name' => $name,
                    'email' => $email,
                    'phones' => $mobile,
                ],
            ],
            'addresses' => [
                [
                    'address_1' => $address,
                    'city' => '',
                    'state' => $state,
                    'zipcode' => $postcode,
                    'country' => 'AU',
                ],
            ],
            'cookies' => $cookies,
            'opportunities' => [
                [
                    'value' => 0,
                    'date_won' => date('Y-m-d'),
                    'form_name' => 'Fencing Calculator',
                    'note' => $notes,
                    'summary' => $summary,
                    'other_items' => $otherItems,
                    'installer' => '',
                    'quote_id' => $plannerId,
                    'planner_status' => strtoupper(trim($plannerStatus)),
                    'planner_url' => $submissionUrl,
                    'fencing_type' => $fenceTypes,
                    'fence_types' => $fenceTypes,
                    'timeframe' => $timeframeLabel,
                    'share_cart_url' => $shareCartUrl,
                    'submission_url' => $submissionUrl,
                ],
            ],
        ];
    }

    /**
     * Human-readable "Other Items Needed" label list from the canonical extra-slugs JSON.
     */
    private static function extraItemLabels(string $extraJson): string
    {
        $slugs = json_decode($extraJson, true);
        if (!is_array($slugs) || $slugs === []) {
            return 'Nothing Extra, Just Fencing';
        }

        $labelsBySlug = [];
        foreach (PlannerOptionSettings::extraItems() as $item) {
            $labelsBySlug[(string) ($item['slug'] ?? '')] = (string) ($item['label'] ?? '');
        }

        $labels = [];
        foreach ($slugs as $slug) {
            $slug = (string) $slug;
            if (isset($labelsBySlug[$slug])) {
                $labels[] = $labelsBySlug[$slug];
            }
        }

        return $labels !== [] ? implode(', ', $labels) : 'Nothing Extra, Just Fencing';
    }

    private static function buildSummary(string $timeframeLabel, string $notes, string $otherItems): string
    {
        $summary = 'Fencing Calculator - ' . date('Y-m-d') . PHP_EOL;
        $summary .= 'FORM NOTES/DETAILS: ' . PHP_EOL . $timeframeLabel . PHP_EOL;
        $summary .= $notes . PHP_EOL;
        $summary .= 'Other Items Needed: ' . $otherItems . PHP_EOL;

        return $summary;
    }

    /**
     * POST the payload; must never throw or block the customer-facing save/redirect.
     * Failure is logged and returned so the caller can release the dedup claim.
     *
     * @param array<string, mixed> $payload
     */
    private static function send(string $webhookUrl, array $payload): bool
    {
        $url = preg_match('#^https?://#i', $webhookUrl) ? $webhookUrl : 'https://' . $webhookUrl;

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($response === false || $httpCode >= 400) {
                error_log('FC webhook: Zapier push failed (' . ($error ?: 'HTTP ' . $httpCode) . ')');

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            error_log('FC webhook: exception pushing to Zapier — ' . $e->getMessage());

            return false;
        }
    }
}
