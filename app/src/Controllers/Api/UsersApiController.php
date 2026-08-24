<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\Request;
use Fc\Admin\Models\UserPresenter;
use Fc\Admin\Services\PresenceService;

final class UsersApiController extends BaseApiController
{
    public static function dispatch(): void
    {
        (new self(new Request()))->handle();
    }

    public function handle(): void
    {
        $action = (string) $this->request->query('action', 'presence');
        $method = $this->request->method();

        if (($action === 'presence' || $action === '') && $method === 'GET') {
            $ids = [];
            $rawIds = (string) $this->request->query('ids', '');
            if ($rawIds !== '') {
                foreach (explode(',', $rawIds) as $part) {
                    $id = (int) trim($part);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }

            $payload = PresenceService::apiPayload($ids);

            // Format last_login for display using users admin formatter.
            $formatted = [];
            foreach (($payload['last_login'] ?? []) as $userId => $ts) {
                $formatted[(string) $userId] = UserPresenter::formatActivityDatetime((string) (int) $ts);
            }
            $payload['last_login_formatted'] = $formatted;

            $activityFormatted = [];
            foreach (($payload['last_activity'] ?? []) as $userId => $ts) {
                $activityFormatted[(string) $userId] = UserPresenter::formatActivityDatetime((string) (int) $ts);
            }
            $payload['last_activity_formatted'] = $activityFormatted;

            $devicesFormatted = [];
            foreach (($payload['devices'] ?? []) as $userId => $client) {
                if (!is_array($client)) {
                    continue;
                }
                $fields = UserPresenter::deviceFields(
                    (string) ($client['device'] ?? ''),
                    (string) ($client['user_agent'] ?? '')
                );
                $devicesFormatted[(string) $userId] = [
                    'device' => $fields['device'],
                    'device_icon' => $fields['device_icon'],
                    'browser' => $fields['browser'],
                    'browser_icon' => $fields['browser_icon'],
                ];
            }
            $payload['devices_formatted'] = $devicesFormatted;

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Unknown action.'], JSON_UNESCAPED_UNICODE);
    }
}
