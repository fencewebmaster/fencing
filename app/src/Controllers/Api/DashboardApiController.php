<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Core\JsonResponse;
use Fc\Admin\Core\Request;
use Fc\Admin\Filters\AuthFilter;
use Fc\Admin\Models\DashboardModel;
use Fc\Admin\Models\PlannerEntryPresenter;

final class DashboardApiController extends BaseApiController
{
    public static function dispatch(): void
    {
        (new self(new Request()))->handle();
    }

    public function handle(): void
    {
        (new AuthFilter())->before($this->request);

        $method = $this->request->method();
        $action = (string) $this->request->query('action', 'summary');

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
                $period = (string) $this->request->query('date_period', '');
                $from = (string) $this->request->query('date_from', '');
                $to = (string) $this->request->query('date_to', '');
                JsonResponse::ok(DashboardModel::chartPayload($period, $from, $to));
                break;
            case 'health':
                JsonResponse::ok(DashboardModel::health());
                break;
            case 'recent':
                $limit = (int) $this->request->query('limit', 8);
                $period = (string) $this->request->query('date_period', '');
                $from = (string) $this->request->query('date_from', '');
                $to = (string) $this->request->query('date_to', '');
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
