<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Core\Request;
use Fc\Admin\Filters\AuthFilter;
use Fc\Admin\Models\DashboardModel;
use Fc\Admin\Models\PlannerEntryPresenter;

final class DashboardApiController
{
    public static function dispatch(): void
    {
        (new AuthFilter())->before(new Request());

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = isset($_GET['action']) ? (string) $_GET['action'] : 'summary';

        if ($method !== 'GET') {
            JsonResponse::error('Method not allowed.', 405);
        }

        switch ($action) {
            case 'summary':
                JsonResponse::ok(DashboardModel::summaryStats());
                break;
            case 'system':
                JsonResponse::ok(DashboardModel::systemCounts());
                break;
            case 'charts':
                $period = isset($_GET['date_period']) ? (string) $_GET['date_period'] : '';
                $from = isset($_GET['date_from']) ? (string) $_GET['date_from'] : '';
                $to = isset($_GET['date_to']) ? (string) $_GET['date_to'] : '';
                JsonResponse::ok(DashboardModel::chartPayload($period, $from, $to));
                break;
            case 'health':
                JsonResponse::ok(DashboardModel::health());
                break;
            case 'recent':
                $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;
                $period = isset($_GET['date_period']) ? (string) $_GET['date_period'] : '';
                $from = isset($_GET['date_from']) ? (string) $_GET['date_from'] : '';
                $to = isset($_GET['date_to']) ? (string) $_GET['date_to'] : '';
                $parsed = PlannerEntryPresenter::parseDateFilter($period, $from, $to);
                JsonResponse::ok([
                    'ok' => true,
                    'items' => DashboardModel::recentEntries($limit, $parsed['bounds'] ?? null),
                ]);
                break;
            default:
                JsonResponse::error('Unknown action.', 400);
        }
    }
}
