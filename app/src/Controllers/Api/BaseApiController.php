<?php

declare(strict_types=1);

namespace Fc\Admin\Controllers\Api;

use Fc\Admin\Controllers\BaseController;
use Fc\Admin\Core\Request;
use Fc\Admin\Services\AuthService;

/**
 * Base for the instance-based API controllers (CodeIgniter 4 convention). Owns the shared
 * dispatch entry point, the standard JSON response headers, and the CSRF-verification helper;
 * each concrete controller implements handle() and keeps its own action routing, field names,
 * and response idiom (the API's CSRF fields and exit-vs-return emission are deliberately
 * non-uniform — see CLAUDE.md).
 */
abstract class BaseApiController extends BaseController
{
    /**
     * Route entry point: build the controller from the current request and run it.
     */
    public static function dispatch(): void
    {
        (new static(new Request()))->handle();
    }

    /**
     * The five controllers that emit a plain JSON + no-store header pair at handle() entry.
     */
    protected function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }

    /**
     * Verify the reusable session CSRF token carried in a decoded JSON payload under $field.
     * Only the boolean check is shared: token field names and the failure response stay at
     * each call site.
     *
     * @param array<string, mixed>|null $payload
     */
    protected static function csrfOk(?array $payload, string $field = 'csrf'): bool
    {
        return AuthService::verifyCsrf(isset($payload[$field]) ? (string) $payload[$field] : null);
    }

    abstract public function handle(): void;
}