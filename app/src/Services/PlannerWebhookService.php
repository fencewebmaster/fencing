<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\UrlHelper;

/**
 * Early Zapier notification — fires before checkout, independent of the WooCommerce cart
 * push (which the sibling WP plugin's `push_order()` always fires unconditionally on its
 * own, regardless of this class). Only fires when IntegrationsSettings::webhookPrePlannerEnabled
 * is on, right after the "Download Your Project Plans" modal's final submit. Guarded by
 * `wp_planners.webhook_sent_at` so a given planner only notifies once per calendar day when
 * IntegrationsSettings::webhookSameDayDedup is on. IntegrationsSettings::webhookMode ('live'|'test')
 * picks which URL actually gets posted to — the real Webhook URL or the separate Test Webhook
 * URL — so this can be exercised without notifying Zapier.
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

        $mode = (string) ($integrations['webhookMode'] ?? 'live');
        $webhookUrl = trim((string) (
            $mode === 'test' ? ($integrations['webhookTestUrl'] ?? '') : ($integrations['webhookUrl'] ?? '')
        ));
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
     * already populated by the time either trigger point runs. `share_cart_url` is always
     * empty here — no WooCommerce cart exists yet at this stage. `installer` is always
     * empty — this app's modal never collects an installer preference.
     *
     * @return array<string, mixed>
     */
    private static function buildZapierPayload(string $plannerId): array
    {
        $fcData = isset($_SESSION['fc_data']) && is_array($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];

        $name = trim((string) ($fcData['name'] ?? ''));
        $email = trim((string) ($fcData['email'] ?? ''));
        $mobile = trim((string) ($fcData['mobile'] ?? ''));
        $state = trim((string) ($fcData['state'] ?? ''));
        $notes = trim((string) ($fcData['notes'] ?? ''));

        $timeframeSlug = (string) ($fcData['timeframe'] ?? '');
        $timeframeLabel = PlannerOptionSettings::timeframeLabel($timeframeSlug) ?? $timeframeSlug;

        $extraJson = PlannerRecordService::extraForDb(
            $fcData['extra'] ?? null,
            isset($fcData['nothing_extra']) ? (string) $fcData['nothing_extra'] : null
        );
        $otherItems = self::extraItemLabels($extraJson);

        $fencingType = self::fencingTypeInfo();

        $submissionUrl = UrlHelper::baseUrl('planner?qid=' . rawurlencode($plannerId));

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
                    'city' => '',
                    'state' => $state,
                    'country' => 'AU',
                ],
            ],
            'cookies' => $_COOKIE,
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
                    'planner_url' => $submissionUrl,
                    'fencing_type' => $fencingType,
                    'timeframe' => $timeframeLabel,
                    'share_cart_url' => '',
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

    /**
     * "fence:color, fence:color, ..." — same shape as the plugin's fence_color_info.
     */
    private static function fencingTypeInfo(): string
    {
        $rows = PlannerSessionService::colorRowsFromSession();
        if (!is_array($rows)) {
            return '';
        }

        $parts = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $fence = trim((string) ($row['fence'] ?? ''));
            $color = trim((string) ($row['color'] ?? ''));
            if ($fence === '' && $color === '') {
                continue;
            }
            $parts[] = $fence . ':' . $color;
        }

        return implode(', ', $parts);
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
