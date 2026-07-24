<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;

final class DashboardApiController
{
    public static function dispatch(): void
    {
        fc_auth_require_login();

        require_once FC_ROOT . '/config/dashboard_admin.php';

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : 'summary';

        if ($method !== 'GET') {
            JsonResponse::error('Method not allowed.', 405);
        }

        switch ($action) {
            case 'summary':
                JsonResponse::ok(fc_dashboard_admin_summary_stats());
                break;
            case 'system':
                JsonResponse::ok(fc_dashboard_admin_system_counts());
                break;
            case 'charts':
                $period = isset($_GET['date_period']) ? (string) $_GET['date_period'] : '';
                $from = isset($_GET['date_from']) ? (string) $_GET['date_from'] : '';
                $to = isset($_GET['date_to']) ? (string) $_GET['date_to'] : '';
                JsonResponse::ok(fc_dashboard_admin_chart_payload($period, $from, $to));
                break;
            case 'health':
                JsonResponse::ok(fc_dashboard_admin_health());
                break;
            case 'recent':
                $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;
                require_once FC_ROOT . '/config/entries_admin.php';
                $period = isset($_GET['date_period']) ? (string) $_GET['date_period'] : '';
                $from = isset($_GET['date_from']) ? (string) $_GET['date_from'] : '';
                $to = isset($_GET['date_to']) ? (string) $_GET['date_to'] : '';
                $parsed = fc_entries_admin_parse_date_filter($period, $from, $to);
                JsonResponse::ok([
                    'ok' => true,
                    'items' => fc_dashboard_admin_recent_entries($limit, $parsed['bounds'] ?? null),
                ]);
                break;
            default:
                JsonResponse::error('Unknown action.', 400);
        }
    }
}
