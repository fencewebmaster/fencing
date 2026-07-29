<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

final class UsersApiController
{
    public static function dispatch(): void
    {
        require_once FC_ROOT . '/config/auth.php';
        require_once FC_ROOT . '/config/presence.php';
        require_once FC_ROOT . '/config/users_admin.php';

        $action = isset($_GET['action']) ? (string) $_GET['action'] : 'presence';
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if (($action === 'presence' || $action === '') && $method === 'GET') {
            $ids = [];
            $rawIds = isset($_GET['ids']) ? (string) $_GET['ids'] : '';
            if ($rawIds !== '') {
                foreach (explode(',', $rawIds) as $part) {
                    $id = (int) trim($part);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }

            $payload = fc_presence_api_payload($ids);

            // Format last_login for display using users admin formatter.
            $formatted = [];
            foreach (($payload['last_login'] ?? []) as $userId => $ts) {
                $formatted[(string) $userId] = fc_users_admin_format_activity_datetime((string) (int) $ts);
            }
            $payload['last_login_formatted'] = $formatted;

            $activityFormatted = [];
            foreach (($payload['last_activity'] ?? []) as $userId => $ts) {
                $activityFormatted[(string) $userId] = fc_users_admin_format_activity_datetime((string) (int) $ts);
            }
            $payload['last_activity_formatted'] = $activityFormatted;

            $devicesFormatted = [];
            foreach (($payload['devices'] ?? []) as $userId => $client) {
                if (!is_array($client)) {
                    continue;
                }
                $fields = fc_users_admin_device_fields(
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
